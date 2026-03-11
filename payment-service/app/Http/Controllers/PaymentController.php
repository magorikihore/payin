<?php

namespace App\Http\Controllers;

use App\Gateways\GatewayFactory;
use App\Models\Operator;
use App\Models\PaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Manual C2B Invoice: Create a payment request that waits for the customer
     * to pay manually via paybill/till number with a reference.
     * No USSD push — customer initiates payment on their own.
     */
    public function invoice(Request $request): JsonResponse
    {
        $request->validate([
            'amount'       => 'required|numeric|min:100',
            'reference'    => 'required|string|max:100',
            'description'  => 'nullable|string|max:255',
            'currency'     => 'nullable|string|max:10',
            'phone'        => 'nullable|string|min:10|max:15',
            'expires_in'   => 'nullable|integer|min:1|max:43200', // minutes, max 30 days
        ]);

        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        // Create payment request with status "waiting" — no push to operator
        // Operator will be determined when the callback arrives from Digivas
        $requestRef = 'INV' . strtoupper(Str::random(12));
        $expiresAt = $request->expires_in
            ? now()->addMinutes($request->expires_in)->toDateTimeString()
            : now()->addDays(7)->toDateTimeString();

        $paymentRequest = PaymentRequest::create([
            'account_id'      => $accountId,
            'request_ref'     => $requestRef,
            'external_ref'    => $request->reference,
            'type'            => 'manual_c2b',
            'phone'           => $request->phone ? $this->normalizePhone($request->phone, $request->currency ?? 'TZS') : null,
            'amount'          => $request->amount,
            'platform_charge' => 0,
            'operator_charge' => 0,
            'currency'        => $request->currency ?? 'TZS',
            'operator_code'   => null,
            'operator_name'   => null,
            'status'          => 'waiting',
            'description'     => $request->description,
            'error_message'   => $expiresAt, // store expiry in error_message temporarily
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Invoice created. Customer should pay using reference: ' . $requestRef,
            'request_ref' => $requestRef,
            'amount'      => $paymentRequest->amount,
            'currency'    => $paymentRequest->currency,
            'status'      => 'waiting',
            'expires_at'  => $expiresAt,
        ], 201);
    }

    /**
     * Push USSD Collection: Merchant initiates a collection request.
     * Phone user receives a USSD prompt to confirm the payment.
     */
    public function collection(Request $request): JsonResponse
    {
        $request->validate([
            'phone'        => 'required|string|min:10|max:15',
            'amount'       => 'required|numeric|min:100',
            'operator'     => 'required|string',
            'reference'    => 'nullable|string|max:100',
            'description'  => 'nullable|string|max:255',
            'currency'     => 'nullable|string|max:10',
        ]);

        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        // Find the operator
        $operator = Operator::where('code', strtolower($request->operator))
            ->where('status', 'active')
            ->first();

        if (!$operator) {
            return response()->json(['message' => 'Operator not found or inactive.'], 422);
        }

        // Calculate charges
        $charges = $this->calculateCharges($accountId, $request->amount, $operator->code, 'collection');

        // Create payment request record
        $requestRef = 'PAY' . strtoupper(Str::random(12));
        $paymentRequest = PaymentRequest::create([
            'account_id'      => $accountId,
            'request_ref'     => $requestRef,
            'external_ref'    => $request->reference,
            'type'            => 'collection',
            'phone'           => $this->normalizePhone($request->phone, $request->currency ?? 'TZS'),
            'amount'          => $request->amount,
            'platform_charge' => $charges['platform_charge'] ?? 0,
            'operator_charge' => $charges['operator_charge'] ?? 0,
            'currency'        => $request->currency ?? 'TZS',
            'operator_code'   => $operator->code,
            'operator_name'   => $operator->name,
            'status'          => 'pending',
            'description'     => $request->description,
        ]);

        // Push to operator
        $pushResult = $this->pushToOperator($operator, $paymentRequest, 'collection');

        // Update with operator response
        $paymentRequest->update([
            'operator_request'  => $pushResult['request_payload'],
            'operator_response' => $pushResult['response'],
            'operator_ref'      => $pushResult['operator_ref'] ?? null,
            'gateway_id'        => $pushResult['gateway_id'] ?? null,
            'status'            => $pushResult['success'] ? 'processing' : 'failed',
            'error_message'     => $pushResult['error'] ?? null,
        ]);

        if (!$pushResult['success']) {
            return response()->json([
                'success'           => false,
                'message'           => 'Failed to push collection to operator.',
                'error'             => $pushResult['error'] ?? 'Operator rejected the request.',
                'request_ref'       => $requestRef,
            ], 422);
        }

        return response()->json([
            'success'           => true,
            'message'           => 'Collection request sent to operator. Waiting for customer confirmation.',
            'request_ref'       => $requestRef,
            'operator_ref'      => $pushResult['operator_ref'] ?? null,
            'gateway_id'        => $pushResult['gateway_id'] ?? null,
            'status'            => 'processing',
            'phone'             => $paymentRequest->phone,
            'amount'            => $paymentRequest->amount,
            'operator'          => $operator->name,
        ], 201);
    }

    /**
     * Push Disbursement: Send money to a phone number.
     * If the user has create_payout but not approve_payout, the payout goes to pending_approval.
     */
    public function disbursement(Request $request): JsonResponse
    {
        $request->validate([
            'phone'       => 'required|string|min:10|max:15',
            'amount'      => 'required|numeric|min:100',
            'operator'    => 'nullable|string',
            'reference'   => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'currency'    => 'nullable|string|max:10',
        ]);

        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        // Auto-detect operator from phone if not provided
        if ($request->filled('operator')) {
            $operator = Operator::where('code', strtolower($request->operator))
                ->where('status', 'active')
                ->first();
        } else {
            $operator = Operator::detectByPhone($request->phone);
        }

        if (!$operator) {
            return response()->json(['message' => 'Could not detect operator from phone number. Please check the number.'], 422);
        }

        // Check wallet balance (has enough for disbursement + platform charge)
        // Operator charge is our cost, deducted from platform profit — NOT charged to user
        $charges = $this->calculateCharges($accountId, $request->amount, $operator->code, 'disbursement');
        $totalDebit = $request->amount + ($charges['platform_charge'] ?? 0);

        $balanceCheck = $this->checkWalletBalance($accountId, $totalDebit, $request->bearerToken());
        if (!$balanceCheck['sufficient']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.',
                'required' => $totalDebit,
                'available' => $balanceCheck['balance'] ?? 0,
            ], 422);
        }

        // Determine if this payout requires approval (maker-checker)
        $requiresApproval = $this->payoutRequiresApproval($user);

        // Create payment request record
        $requestRef = 'PAY' . strtoupper(Str::random(12));
        $paymentRequest = PaymentRequest::create([
            'account_id'      => $accountId,
            'request_ref'     => $requestRef,
            'external_ref'    => $request->reference,
            'type'            => 'disbursement',
            'phone'           => $this->normalizePhone($request->phone, $request->currency ?? 'TZS'),
            'amount'          => $request->amount,
            'platform_charge' => $charges['platform_charge'] ?? 0,
            'operator_charge' => $charges['operator_charge'] ?? 0,
            'currency'        => $request->currency ?? 'TZS',
            'operator_code'   => $operator->code,
            'operator_name'   => $operator->name,
            'status'          => $requiresApproval ? 'pending_approval' : 'pending',
            'description'     => $request->description,
            'created_by'      => $user->id ?? null,
        ]);

        // If requires approval, return immediately without pushing to operator
        if ($requiresApproval) {
            return response()->json([
                'success'      => true,
                'message'      => 'Payout request submitted for approval.',
                'request_ref'  => $requestRef,
                'status'       => 'pending_approval',
                'phone'        => $paymentRequest->phone,
                'amount'       => $paymentRequest->amount,
                'operator'     => $operator->name,
                'requires_approval' => true,
            ], 201);
        }

        // Push to operator
        $pushResult = $this->pushToOperator($operator, $paymentRequest, 'disbursement');

        // Update with operator response
        $paymentRequest->update([
            'operator_request'  => $pushResult['request_payload'],
            'operator_response' => $pushResult['response'],
            'operator_ref'      => $pushResult['operator_ref'] ?? null,
            'gateway_id'        => $pushResult['gateway_id'] ?? null,
            'status'            => $pushResult['success'] ? 'processing' : 'failed',
            'error_message'     => $pushResult['error'] ?? null,
        ]);

        if (!$pushResult['success']) {
            return response()->json([
                'success'           => false,
                'message'           => 'Failed to push disbursement to operator.',
                'error'             => $pushResult['error'] ?? 'Operator rejected the request.',
                'request_ref'       => $requestRef,
            ], 422);
        }

        return response()->json([
            'success'           => true,
            'message'           => 'Disbursement request sent to operator.',
            'request_ref'       => $requestRef,
            'operator_ref'      => $pushResult['operator_ref'] ?? null,
            'gateway_id'        => $pushResult['gateway_id'] ?? null,
            'status'            => 'processing',
            'phone'             => $paymentRequest->phone,
            'amount'            => $paymentRequest->amount,
            'operator'          => $operator->name,
        ], 201);
    }

    /**
     * Check payment status.
     */
    public function status(Request $request, $request_ref): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        $paymentRequest = PaymentRequest::where('request_ref', $request_ref)
            ->when($accountId, fn($q) => $q->where('account_id', $accountId))
            ->first();

        if (!$paymentRequest) {
            return response()->json(['message' => 'Payment request not found.'], 404);
        }

        return response()->json([
            'request_ref'  => $paymentRequest->request_ref,
            'external_ref' => $paymentRequest->external_ref,
            'operator_ref' => $paymentRequest->operator_ref,
            'type'         => $paymentRequest->type,
            'phone'        => $paymentRequest->phone,
            'amount'       => $paymentRequest->amount,
            'charges'      => [
                'platform' => $paymentRequest->platform_charge,
                'operator' => $paymentRequest->operator_charge,
            ],
            'currency'     => $paymentRequest->currency,
            'operator'     => $paymentRequest->operator_name,
            'status'       => $paymentRequest->status,
            'error'        => $paymentRequest->error_message,
            'created_at'   => $paymentRequest->created_at,
            'updated_at'   => $paymentRequest->updated_at,
        ]);
    }

    /**
     * Operator Callback: Receives payment status updates from any operator.
     * This endpoint is PUBLIC (no auth) — operators call it with their response.
     * Auto-detects the payload format and parses accordingly.
     */
    public function callback(Request $request, $operator_code = null): JsonResponse
    {
        $payload = $request->all();
        Log::info('Operator callback received' . ($operator_code ? " for [{$operator_code}]" : ''), $payload);

        // Step 1: Auto-detect payload format and parse into normalized data
        $parsed = $this->detectAndParseCallback($payload);
        Log::info('Callback parsed result', $parsed);

        // Step 2: Find payment request (check both processing and waiting statuses)
        $paymentRequest = $this->findPaymentRequestFromCallback($payload, $operator_code);

        if (!$paymentRequest) {
            // Manual C2B: customer paid with a reference that doesn't exist — REJECT
            Log::warning('Callback: payment request not found — rejecting', [
                'parsed' => $parsed, 'operator_code' => $operator_code,
            ]);
            return response()->json([
                'header' => ['responseCode' => '999', 'responseStatus' => 'FAILED'],
                'body' => ['response' => ['responseCode' => '999', 'responseStatus' => 'Reference not found']],
                'message' => 'Payment request not found.',
            ], 200);
        }

        // Step 3: Manual C2B validation — verify amount matches
        if ($paymentRequest->type === 'manual_c2b' && $paymentRequest->status === 'waiting') {
            $callbackAmount = (float) ($parsed['amount'] ?? 0);
            $expectedAmount = (float) $paymentRequest->amount;

            // Check expiry (stored in error_message field)
            $expiresAt = $paymentRequest->error_message;
            if ($expiresAt && now()->gt($expiresAt)) {
                Log::warning("Manual C2B: invoice expired [{$paymentRequest->request_ref}]");
                $paymentRequest->update(['status' => 'expired', 'callback_data' => $payload]);
                return response()->json([
                    'header' => ['responseCode' => '999', 'responseStatus' => 'FAILED'],
                    'body' => ['response' => ['responseCode' => '999', 'responseStatus' => 'Invoice expired']],
                    'message' => 'Invoice expired.',
                ], 200);
            }

            // Validate amount matches
            if ($callbackAmount > 0 && abs($callbackAmount - $expectedAmount) > 0.01) {
                Log::warning("Manual C2B: amount mismatch [{$paymentRequest->request_ref}] expected={$expectedAmount} got={$callbackAmount}");
                $paymentRequest->update(['callback_data' => $payload]);
                return response()->json([
                    'header' => ['responseCode' => '999', 'responseStatus' => 'FAILED'],
                    'body' => ['response' => ['responseCode' => '999', 'responseStatus' => 'Amount mismatch']],
                    'message' => 'Amount does not match invoice.',
                ], 200);
            }

            Log::info("Manual C2B: invoice [{$paymentRequest->request_ref}] validated — accepting payment");

            // Fill operator info from callback network field or URL operator_code
            if (!$paymentRequest->operator_code) {
                $networkMap = [
                    'vodacom' => 'mpesa',
                    'airtel'  => 'airtel',
                    'tigo'    => 'tigopesa',
                    'halotel' => 'halopesa',
                ];
                $network = strtolower(trim($parsed['network'] ?? ''));
                $resolvedCode = $networkMap[$network] ?? $operator_code;

                if ($resolvedCode) {
                    $op = Operator::where('code', strtolower($resolvedCode))->first();
                    if ($op) {
                        $paymentRequest->operator_code = $op->code;
                        $paymentRequest->operator_name = $op->name;
                        $charges = $this->calculateCharges($paymentRequest->account_id, $paymentRequest->amount, $op->code, 'collection');
                        $paymentRequest->platform_charge = $charges['platform_charge'] ?? 0;
                        $paymentRequest->operator_charge = $charges['operator_charge'] ?? 0;
                    }
                }
            }
        }

        $newStatus = $parsed['status'] ?? 'processing';

        $updateData = [
            'callback_data'  => $payload,
            'receipt_number' => $parsed['receipt_number'] ?: $paymentRequest->receipt_number,
            'operator_ref'   => $parsed['operator_ref'] ?: $paymentRequest->operator_ref,
            'gateway_id'     => $parsed['gateway_id'] ? (string) $parsed['gateway_id'] : $paymentRequest->gateway_id,
            'phone'          => $parsed['phone'] ?: $paymentRequest->phone,
            'status'         => $newStatus,
            'error_message'  => ($newStatus === 'failed') ? ($parsed['error_message'] ?? 'Operator returned failure') : null,
        ];

        // Persist operator info if it was set from callback
        if ($paymentRequest->isDirty('operator_code')) {
            $updateData['operator_code'] = $paymentRequest->operator_code;
            $updateData['operator_name'] = $paymentRequest->operator_name;
            $updateData['platform_charge'] = $paymentRequest->platform_charge;
            $updateData['operator_charge'] = $paymentRequest->operator_charge;
        }

        $paymentRequest->update($updateData);

        // If completed, record the transaction and update wallet
        if ($newStatus === 'completed') {
            $this->recordTransaction($paymentRequest);
            $this->updateWallet($paymentRequest);
            $this->processReferralCommission($paymentRequest);
        }

        // Send callback to merchant
        $this->sendMerchantCallback($paymentRequest);

        Log::info("Callback processed for [{$paymentRequest->request_ref}] => {$newStatus} (format: {$parsed['format']})");

        // Digivas acknowledgment codes: 0=accept/charge, 999=reject, 100=reconcile
        $ackCode = ($newStatus === 'completed') ? '0' : (($newStatus === 'failed') ? '999' : '100');
        $ackStatus = ($ackCode === '0') ? 'ACCEPTED' : (($ackCode === '999') ? 'FAILED' : 'RECONCILE');

        return response()->json([
            'header' => ['responseCode' => $ackCode, 'responseStatus' => $ackStatus],
            'body' => ['response' => ['responseCode' => $ackCode, 'responseStatus' => $ackStatus]],
            'message' => 'Callback processed successfully.',
            'request_ref' => $paymentRequest->request_ref,
            'status' => $newStatus,
        ]);
    }

    /**
     * Auto-detect callback format from payload structure and parse into normalized data.
     * Supports: Digivas, Safaricom Daraja, Airtel Africa, MTN MoMo.
     */
    private function detectAndParseCallback(array $payload): array
    {
        // --- Digivas collection: body.request with command ---
        $bodyRequest = data_get($payload, 'body.request');
        if ($bodyRequest && is_array($bodyRequest) && isset($bodyRequest['command'])) {
            $receiptNumber = $bodyRequest['receiptNumber'] ?? null;
            return [
                'format'         => 'digivas_collection',
                'type'           => 'collection',
                'status'         => $receiptNumber ? 'completed' : 'failed',
                'receipt_number' => $receiptNumber,
                'operator_ref'   => $bodyRequest['gatewayId'] ?? null,
                'gateway_id'     => $bodyRequest['gatewayId'] ?? null,
                'reference'      => $bodyRequest['reference'] ?? null,
                'phone'          => $bodyRequest['msisdn'] ?? null,
                'amount'         => $bodyRequest['amount'] ?? null,
                'network'        => $bodyRequest['network'] ?? null,
                'error_message'  => $receiptNumber ? null : 'Collection callback without receipt number',
            ];
        }

        // --- Digivas result/disbursement: body.result ---
        $bodyResult = data_get($payload, 'body.result');
        if ($bodyResult && is_array($bodyResult)) {
            $resultCode = (string) ($bodyResult['resultCode'] ?? '');
            $message = strtolower($bodyResult['message'] ?? '');
            $status = 'processing';
            if ($resultCode === '0' || $message === 'success') $status = 'completed';
            elseif ($resultCode !== '' && $resultCode !== '0') $status = 'failed';

            return [
                'format'         => 'digivas_result',
                'type'           => 'disbursement',
                'status'         => $status,
                'receipt_number' => $bodyResult['receiptNumber'] ?? null,
                'operator_ref'   => $bodyResult['transactionNumber'] ?? ($bodyResult['referenceNumber'] ?? null),
                'gateway_id'     => $bodyResult['gatewayId'] ?? null,
                'reference'      => $bodyResult['referenceNumber'] ?? ($bodyResult['reference'] ?? null),
                'phone'          => null,
                'amount'         => $bodyResult['amount'] ?? null,
                'error_message'  => $status === 'failed' ? ($bodyResult['resultStatus'] ?? 'Operator error: ' . $resultCode) : null,
            ];
        }

        // --- Digivas body.response fallback ---
        $bodyResponse = data_get($payload, 'body.response');
        if ($bodyResponse && is_array($bodyResponse) && isset($bodyResponse['responseCode'])) {
            $responseCode = (string) ($bodyResponse['responseCode'] ?? '');
            $status = $responseCode === '0' ? 'completed' : 'failed';

            return [
                'format'         => 'digivas_response',
                'type'           => 'collection',
                'status'         => $status,
                'receipt_number' => $bodyResponse['receiptNumber'] ?? null,
                'operator_ref'   => $bodyResponse['transactionNumber'] ?? null,
                'gateway_id'     => $bodyResponse['gatewayId'] ?? null,
                'reference'      => $bodyResponse['reference'] ?? null,
                'phone'          => null,
                'amount'         => $bodyResponse['amount'] ?? null,
                'error_message'  => $status === 'failed' ? ($bodyResponse['responseStatus'] ?? 'Error: ' . $responseCode) : null,
            ];
        }

        // --- Safaricom Daraja STK Push: Body.stkCallback ---
        $stkCallback = data_get($payload, 'Body.stkCallback');
        if ($stkCallback && is_array($stkCallback)) {
            $resultCode = (string) ($stkCallback['ResultCode'] ?? '');
            $items = collect($stkCallback['CallbackMetadata']['Item'] ?? []);
            $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
            $amount = $items->firstWhere('Name', 'Amount')['Value'] ?? null;
            $phone = $items->firstWhere('Name', 'PhoneNumber')['Value'] ?? null;

            return [
                'format'         => 'daraja_stk',
                'type'           => 'collection',
                'status'         => $resultCode === '0' ? 'completed' : 'failed',
                'receipt_number' => $receipt,
                'operator_ref'   => $stkCallback['CheckoutRequestID'] ?? null,
                'gateway_id'     => $stkCallback['MerchantRequestID'] ?? null,
                'reference'      => null,
                'phone'          => $phone ? (string) $phone : null,
                'amount'         => $amount ? (string) $amount : null,
                'error_message'  => $resultCode !== '0' ? ($stkCallback['ResultDesc'] ?? 'Payment failed') : null,
            ];
        }

        // --- Safaricom Daraja B2C: Result ---
        $darajaResult = data_get($payload, 'Result');
        if ($darajaResult && is_array($darajaResult) && isset($darajaResult['ResultCode'])) {
            $resultCode = (string) ($darajaResult['ResultCode'] ?? '');
            $params = collect($darajaResult['ResultParameters']['ResultParameter'] ?? []);
            $receipt = $params->firstWhere('Key', 'TransactionReceipt')['Value'] ?? null;
            $amount = $params->firstWhere('Key', 'TransactionAmount')['Value'] ?? null;
            $phone = $params->firstWhere('Key', 'ReceiverPartyPublicName')['Value'] ?? null;

            return [
                'format'         => 'daraja_b2c',
                'type'           => 'disbursement',
                'status'         => $resultCode === '0' ? 'completed' : 'failed',
                'receipt_number' => $receipt,
                'operator_ref'   => $darajaResult['ConversationID'] ?? null,
                'gateway_id'     => $darajaResult['OriginatorConversationID'] ?? null,
                'reference'      => $darajaResult['OriginatorConversationID'] ?? null,
                'phone'          => $phone,
                'amount'         => $amount ? (string) $amount : null,
                'error_message'  => $resultCode !== '0' ? ($darajaResult['ResultDesc'] ?? 'Disbursement failed') : null,
            ];
        }

        // --- MTN MoMo: has financialTransactionId or externalId + status ---
        if (isset($payload['financialTransactionId']) || (isset($payload['externalId']) && isset($payload['status']))) {
            $status = strtoupper(data_get($payload, 'status', ''));
            $isSuccess = $status === 'SUCCESSFUL';
            $isFailed = in_array($status, ['FAILED', 'REJECTED', 'TIMEOUT', 'EXPIRED']);
            $type = isset($payload['payer']) ? 'collection' : 'disbursement';

            return [
                'format'         => 'mtn_momo',
                'type'           => $type,
                'status'         => $isSuccess ? 'completed' : ($isFailed ? 'failed' : 'processing'),
                'receipt_number' => $payload['financialTransactionId'] ?? null,
                'operator_ref'   => $payload['externalId'] ?? ($payload['referenceId'] ?? null),
                'gateway_id'     => $payload['externalId'] ?? ($payload['referenceId'] ?? null),
                'reference'      => $payload['externalId'] ?? ($payload['referenceId'] ?? null),
                'phone'          => data_get($payload, 'payer.partyId') ?? data_get($payload, 'payee.partyId'),
                'amount'         => $payload['amount'] ?? null,
                'error_message'  => $isFailed ? ($payload['reason'] ?? 'Transaction failed') : null,
            ];
        }

        // --- Airtel Africa: has transaction object ---
        $transaction = data_get($payload, 'transaction');
        if ($transaction && is_array($transaction)) {
            $statusCode = data_get($transaction, 'status_code', data_get($payload, 'status_code', ''));
            $isSuccess = in_array(strtoupper((string) $statusCode), ['TS', 'TIP', '200']);
            $isFailed = in_array(strtoupper((string) $statusCode), ['TF', 'TE']);
            $type = data_get($transaction, 'message', '') === 'Paid In' ? 'collection' : 'disbursement';

            return [
                'format'         => 'airtel_africa',
                'type'           => $type,
                'status'         => $isSuccess ? 'completed' : ($isFailed ? 'failed' : 'processing'),
                'receipt_number' => data_get($transaction, 'airtel_money_id'),
                'operator_ref'   => data_get($transaction, 'id'),
                'gateway_id'     => data_get($transaction, 'id'),
                'reference'      => data_get($transaction, 'reference', data_get($payload, 'reference')),
                'phone'          => data_get($transaction, 'msisdn'),
                'amount'         => data_get($transaction, 'amount') ? (string) data_get($transaction, 'amount') : null,
                'error_message'  => $isFailed ? data_get($transaction, 'message', 'Transaction failed') : null,
            ];
        }

        // --- Airtel Africa flat format (no transaction wrapper) ---
        if (isset($payload['status_code']) || isset($payload['airtel_money_id'])) {
            $statusCode = (string) ($payload['status_code'] ?? '');
            $isSuccess = in_array(strtoupper($statusCode), ['TS', 'TIP', '200']);
            $isFailed = in_array(strtoupper($statusCode), ['TF', 'TE']);

            return [
                'format'         => 'airtel_africa_flat',
                'type'           => ($payload['message'] ?? '') === 'Paid In' ? 'collection' : 'disbursement',
                'status'         => $isSuccess ? 'completed' : ($isFailed ? 'failed' : 'processing'),
                'receipt_number' => $payload['airtel_money_id'] ?? null,
                'operator_ref'   => $payload['id'] ?? null,
                'gateway_id'     => $payload['id'] ?? null,
                'reference'      => $payload['reference'] ?? null,
                'phone'          => $payload['msisdn'] ?? null,
                'amount'         => isset($payload['amount']) ? (string) $payload['amount'] : null,
                'error_message'  => $isFailed ? ($payload['message'] ?? 'Transaction failed') : null,
            ];
        }

        // --- Unknown format ---
        Log::warning('Callback: unknown payload format', ['payload' => $payload]);
        return [
            'format'         => 'unknown',
            'type'           => 'unknown',
            'status'         => 'processing',
            'receipt_number' => null,
            'operator_ref'   => null,
            'gateway_id'     => null,
            'reference'      => null,
            'phone'          => null,
            'amount'         => null,
            'error_message'  => null,
        ];
    }

    /**
     * Admin: List all payment requests across accounts.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = PaymentRequest::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_ref', 'like', "%{$search}%")
                  ->orWhere('external_ref', 'like', "%{$search}%")
                  ->orWhere('operator_ref', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('type')) { $query->where('type', $request->type); }
        if ($request->filled('operator')) { $query->where('operator_code', $request->operator); }
        if ($request->filled('callback_status')) { $query->where('callback_status', $request->callback_status); }
        if ($request->filled('account_id')) { $query->where('account_id', $request->account_id); }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($requests);
    }

    /**
     * Batch Disbursement: Send money to multiple recipients at once.
     * Accepts an array of disbursement items or a CSV-style input.
     */
    public function batchDisbursement(Request $request): JsonResponse
    {
        $request->validate([
            'batch_name'        => 'nullable|string|max:100',
            'items'             => 'required|array|min:1|max:500',
            'items.*.phone'     => 'required|string|min:10|max:15',
            'items.*.amount'    => 'required|numeric|min:100',
            'items.*.operator'  => 'nullable|string',
            'items.*.reference' => 'nullable|string|max:100',
            'items.*.description' => 'nullable|string|max:255',
        ]);

        $batchName = $request->input('batch_name');

        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        // Determine if this batch requires approval (maker-checker)
        $requiresApproval = $this->payoutRequiresApproval($user);

        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($request->items as $index => $item) {
            // Auto-detect operator from phone if not provided
            if (!empty($item['operator'])) {
                $operatorCode = strtolower($item['operator']);
                $operator = Operator::where('code', $operatorCode)
                    ->where('status', 'active')
                    ->first();
            } else {
                $operator = Operator::detectByPhone($item['phone']);
            }

            if (!$operator) {
                $results[] = [
                    'index' => $index,
                    'phone' => $item['phone'],
                    'amount' => $item['amount'],
                    'success' => false,
                    'error' => 'Could not detect operator from phone number.',
                ];
                $failCount++;
                continue;
            }

            // Check wallet balance (user pays only platform charge, operator charge is our cost)
            $charges = $this->calculateCharges($accountId, $item['amount'], $operator->code, 'disbursement');
            $totalDebit = $item['amount'] + ($charges['platform_charge'] ?? 0);

            if (!$requiresApproval) {
                $balanceCheck = $this->checkWalletBalance($accountId, $totalDebit, $request->bearerToken());
                if (!$balanceCheck['sufficient']) {
                    $results[] = [
                        'index' => $index,
                        'phone' => $item['phone'],
                        'amount' => $item['amount'],
                        'success' => false,
                        'error' => 'Insufficient wallet balance.',
                        'required' => $totalDebit,
                        'available' => $balanceCheck['balance'] ?? 0,
                    ];
                    $failCount++;
                    continue;
                }
            }

            // Create payment request
            $requestRef = 'PAY' . strtoupper(Str::random(12));
            $paymentRequest = PaymentRequest::create([
                'account_id'      => $accountId,
                'request_ref'     => $requestRef,
                'external_ref'    => $item['reference'] ?? null,
                'type'            => 'disbursement',
                'phone'           => $this->normalizePhone($item['phone'], 'TZS'),
                'amount'          => $item['amount'],
                'platform_charge' => $charges['platform_charge'] ?? 0,
                'operator_charge' => $charges['operator_charge'] ?? 0,
                'currency'        => 'TZS',
                'operator_code'   => $operator->code,
                'operator_name'   => $operator->name,
                'status'          => $requiresApproval ? 'pending_approval' : 'pending',
                'description'     => $item['description'] ?? null,
                'batch_name'      => $batchName,
                'created_by'      => $user->id ?? null,
            ]);

            if ($requiresApproval) {
                $successCount++;
                $results[] = [
                    'index' => $index,
                    'phone' => $paymentRequest->phone,
                    'amount' => $paymentRequest->amount,
                    'success' => true,
                    'request_ref' => $requestRef,
                    'status' => 'pending_approval',
                ];
                continue;
            }

            // Push to operator
            $pushResult = $this->pushToOperator($operator, $paymentRequest, 'disbursement');

            $paymentRequest->update([
                'operator_request'  => $pushResult['request_payload'],
                'operator_response' => $pushResult['response'],
                'operator_ref'      => $pushResult['operator_ref'] ?? null,
                'gateway_id'        => $pushResult['gateway_id'] ?? null,
                'status'            => $pushResult['success'] ? 'processing' : 'failed',
                'error_message'     => $pushResult['error'] ?? null,
            ]);

            if ($pushResult['success']) {
                $successCount++;
                $results[] = [
                    'index' => $index,
                    'phone' => $paymentRequest->phone,
                    'amount' => $paymentRequest->amount,
                    'success' => true,
                    'request_ref' => $requestRef,
                    'operator_ref' => $pushResult['operator_ref'] ?? null,
                    'status' => 'processing',
                ];
            } else {
                $failCount++;
                $results[] = [
                    'index' => $index,
                    'phone' => $item['phone'],
                    'amount' => $item['amount'],
                    'success' => false,
                    'error' => $pushResult['error'] ?? 'Operator rejected the request.',
                    'request_ref' => $requestRef,
                ];
            }
        }

        $message = $requiresApproval
            ? "Batch submitted for approval: {$successCount} queued, {$failCount} failed."
            : "Batch complete: {$successCount} sent, {$failCount} failed.";

        return response()->json([
            'success' => $failCount === 0,
            'message' => $message,
            'sent' => $successCount,
            'failed' => $failCount,
            'total' => count($request->items),
            'results' => $results,
            'requires_approval' => $requiresApproval,
        ], $successCount > 0 ? 201 : 422);
    }

    /**
     * List active operators for dashboard use.
     */
    public function activeOperators(Request $request): JsonResponse
    {
        $operators = Operator::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'prefixes']);

        return response()->json(['operators' => $operators]);
    }

    /**
     * Detect operator from phone number.
     */
    public function detectOperator(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
        ]);

        $operator = Operator::detectByPhone($request->phone);

        if (!$operator) {
            return response()->json([
                'detected' => false,
                'message'  => 'Could not detect operator from this phone number.',
            ]);
        }

        return response()->json([
            'detected' => true,
            'operator' => [
                'id'   => $operator->id,
                'name' => $operator->name,
                'code' => $operator->code,
            ],
        ]);
    }

    /**
     * Cancel a waiting invoice (manual_c2b).
     */
    public function cancelInvoice(Request $request, string $requestRef): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        $paymentRequest = PaymentRequest::where('account_id', $accountId)
            ->where('request_ref', $requestRef)
            ->where('type', 'manual_c2b')
            ->first();

        if (!$paymentRequest) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        if ($paymentRequest->status !== 'waiting') {
            return response()->json(['message' => 'Only waiting invoices can be cancelled.'], 422);
        }

        $paymentRequest->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Invoice cancelled.']);
    }

    /**
     * User: List own payment requests.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        $query = PaymentRequest::where('account_id', $accountId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_ref', 'like', "%{$search}%")
                  ->orWhere('external_ref', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('type')) { $query->where('type', $request->type); }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($requests);
    }

    // ===================================================================
    //  PRIVATE HELPERS
    // ===================================================================

    /**
     * Push request to operator's API using DIGIVAS EPG header/body format.
     * 
     * Sends:
     * {
     *   "header": { "spId", "merchantCode", "spPassword", "timestamp", "apiVersion" },
     *   "body": {
     *     "request": {
     *       "command": "UssdPush|Disbursement",
     *       "command1": "UssdPush|Disbursement",
     *       "reference": "...",
     *       "transactionID": "...",
     *       "msisdn": "255...",
     *       "amount": "1000",
     *       "currency": "TZS",
     *       "transactionChannel": "MOBAPP"
     *     }
     *   }
     * }
     * 
     * Receives acknowledgment:
     * {
     *   "header": { ... },
     *   "body": {
     *     "response": {
     *       "transactionNumber": "12345",
     *       "gatewayId": 5979976,
     *       "responseCode": "0",
     *       "responseStatus": "Payment Request has been Accepted Successfully...",
     *       "reference": "234"
     *     }
     *   }
     * }
     */
    /**
     * Extract reference IDs from a raw callback payload and find the matching PaymentRequest.
     * Checks all known field paths across gateway formats (Digivas, Daraja, Airtel, MTN).
     */
    private function findPaymentRequestFromCallback(array $payload, ?string $operatorCode): ?PaymentRequest
    {
        // Collect all possible reference values from the raw payload
        $references = array_values(array_unique(array_filter([
            // Digivas collection: body.request.reference
            data_get($payload, 'body.request.reference'),
            // Digivas result/disbursement: body.result.referenceNumber, body.result.reference
            data_get($payload, 'body.result.referenceNumber'),
            data_get($payload, 'body.result.reference'),
            // Airtel Africa: transaction.reference, transaction.id (top-level or nested)
            data_get($payload, 'transaction.reference'),
            data_get($payload, 'transaction.id'),
            data_get($payload, 'reference'),
            data_get($payload, 'id'),
            // MTN MoMo: externalId, referenceId
            data_get($payload, 'externalId'),
            data_get($payload, 'referenceId'),
            // Safaricom B2C: Result.OriginatorConversationID
            data_get($payload, 'Result.OriginatorConversationID'),
        ])));

        $gatewayIds = array_values(array_unique(array_filter([
            // Digivas: body.request.gatewayId, body.result.gatewayId
            data_get($payload, 'body.request.gatewayId'),
            data_get($payload, 'body.result.gatewayId'),
            // Safaricom STK: Body.stkCallback.MerchantRequestID
            data_get($payload, 'Body.stkCallback.MerchantRequestID'),
            // Safaricom B2C: Result.OriginatorConversationID
            data_get($payload, 'Result.OriginatorConversationID'),
            // Airtel Africa: transaction.id, id (top-level)
            data_get($payload, 'transaction.id'),
            data_get($payload, 'id'),
        ])));

        $operatorRefs = array_values(array_unique(array_filter([
            // Digivas: body.result.transactionNumber
            data_get($payload, 'body.result.transactionNumber'),
            // Safaricom STK: Body.stkCallback.CheckoutRequestID
            data_get($payload, 'Body.stkCallback.CheckoutRequestID'),
            // Safaricom B2C: Result.ConversationID
            data_get($payload, 'Result.ConversationID'),
            // Airtel Africa: transaction.id, id (top-level)
            data_get($payload, 'transaction.id'),
            data_get($payload, 'id'),
            // MTN MoMo: externalId, referenceId
            data_get($payload, 'externalId'),
            data_get($payload, 'referenceId'),
        ])));

        Log::info('Callback lookup: extracted identifiers', [
            'references' => $references,
            'gateway_ids' => $gatewayIds,
            'operator_refs' => $operatorRefs,
            'operator_code' => $operatorCode,
        ]);

        // Search by request_ref (our internal reference — most reliable)
        foreach ($references as $ref) {
            $pr = PaymentRequest::where('request_ref', (string) $ref)->first();
            if ($pr) return $pr;
        }

        // Search by external_ref (customer-facing reference, e.g. 8-digit invoice number)
        foreach ($references as $ref) {
            $pr = PaymentRequest::where('external_ref', (string) $ref)
                ->whereIn('status', ['processing', 'waiting'])
                ->first();
            if ($pr) return $pr;
        }

        // Search by gateway_id
        foreach ($gatewayIds as $gid) {
            $pr = PaymentRequest::where('gateway_id', (string) $gid)->first();
            if ($pr) return $pr;
        }

        // Search by operator_ref
        foreach ($operatorRefs as $oref) {
            $pr = PaymentRequest::where('operator_ref', (string) $oref)->first();
            if ($pr) return $pr;
        }

        // Cross-match: reference in operator_ref column
        foreach ($references as $ref) {
            $pr = PaymentRequest::where('operator_ref', (string) $ref)->first();
            if ($pr) return $pr;
        }

        // Cross-match: gateway_id values in request_ref column
        foreach ($gatewayIds as $gid) {
            $pr = PaymentRequest::where('request_ref', (string) $gid)->first();
            if ($pr) return $pr;
        }

        // Last resort: match by phone + operator + processing/waiting status
        $phone = data_get($payload, 'body.request.msisdn')
            ?? data_get($payload, 'transaction.msisdn')
            ?? data_get($payload, 'msisdn')
            ?? data_get($payload, 'Body.stkCallback.CallbackMetadata.Item.2.Value');
        if ($phone) {
            $query = PaymentRequest::where('phone', (string) $phone)
                ->whereIn('status', ['processing', 'waiting'])
                ->orderBy('created_at', 'desc');
            if ($operatorCode) {
                $query->where('operator_code', $operatorCode);
            }
            $pr = $query->first();
            if ($pr) return $pr;
        }

        // Deep scan: extract ALL string values from the payload and try matching
        $allValues = $this->extractAllValues($payload);
        Log::info('Callback lookup: deep scan values', ['values' => $allValues]);

        foreach ($allValues as $val) {
            $pr = PaymentRequest::where('request_ref', $val)->first();
            if ($pr) return $pr;
        }
        foreach ($allValues as $val) {
            $pr = PaymentRequest::where('gateway_id', $val)->first();
            if ($pr) return $pr;
        }
        foreach ($allValues as $val) {
            $pr = PaymentRequest::where('operator_ref', $val)->first();
            if ($pr) return $pr;
        }

        return null;
    }

    /**
     * Recursively extract all non-empty string/numeric values from a nested array.
     */
    private function extractAllValues(array $data): array
    {
        $values = [];
        array_walk_recursive($data, function ($value) use (&$values) {
            if (is_string($value) && strlen($value) >= 3 && strlen($value) <= 100) {
                $values[] = $value;
            } elseif (is_numeric($value) && $value > 0) {
                $values[] = (string) $value;
            }
        });
        return array_values(array_unique($values));
    }

    private function pushToOperator(Operator $operator, PaymentRequest $paymentRequest, string $type): array
    {
        try {
            $gateway = GatewayFactory::make($operator->gateway_type ?? 'digivas');
            return $gateway->push($operator, $paymentRequest, $type);
        } catch (\InvalidArgumentException $e) {
            Log::error("Unsupported gateway type [{$operator->gateway_type}] for operator [{$operator->code}]");
            return [
                'success' => false,
                'error' => 'Unsupported gateway type: ' . ($operator->gateway_type ?? 'unknown'),
                'request_payload' => null,
                'response' => null,
            ];
        }
    }

    /**
     * Calculate charges for a transaction.
     */
    private function calculateCharges($accountId, $amount, $operatorCode, $type): array
    {
        try {
            $txnServiceUrl = config('services.transaction_service.url');
            $serviceKey = config('services.internal_service_key');
            $response = Http::withHeaders([
                'X-Service-Key' => $serviceKey,
            ])->post("{$txnServiceUrl}/api/internal/charges/calculate", [
                'amount' => $amount,
                'operator' => $operatorCode,
                'transaction_type' => $type,
                'account_id' => $accountId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'platform_charge' => $data['platform_charge'] ?? 0,
                    'operator_charge' => $data['operator_charge'] ?? 0,
                ];
            } else {
                Log::warning('Charge calculation failed with status: ' . $response->status() . ' body: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::warning("Charge calculation failed: " . $e->getMessage());
        }

        return ['platform_charge' => 0, 'operator_charge' => 0];
    }

    /**
     * Check wallet balance for disbursement.
     */
    private function checkWalletBalance($accountId, $totalAmount, $token): array
    {
        try {
            $walletServiceUrl = config('services.wallet_service.url');
            $serviceKey = config('services.internal_service_key');

            // Use internal API with service key (reliable, no user token needed)
            $response = Http::withHeaders(['X-Service-Key' => $serviceKey])
                ->get("{$walletServiceUrl}/api/internal/wallet/summary", ['account_id' => $accountId]);

            if ($response->successful()) {
                $data = $response->json();
                $overallBalance = (float) ($data['overall_balance'] ?? 0);
                return [
                    'sufficient' => $overallBalance >= $totalAmount,
                    'balance' => $overallBalance,
                ];
            }

            Log::warning("Wallet balance check: non-successful response", [
                'status' => $response->status(),
                'body' => $response->body(),
                'account_id' => $accountId,
            ]);

            // Fallback: try user token against wallet API
            if ($token) {
                $userRes = Http::withToken($token)
                    ->get("{$walletServiceUrl}/api/wallet");

                if ($userRes->successful()) {
                    $data = $userRes->json();
                    $overallBalance = (float) ($data['overall_balance'] ?? 0);
                    return [
                        'sufficient' => $overallBalance >= $totalAmount,
                        'balance' => $overallBalance,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Wallet balance check failed: " . $e->getMessage());
        }

        return ['sufficient' => false, 'balance' => 0];
    }

    /**
     * Record a completed transaction in the transaction service.
     */
    private function recordTransaction(PaymentRequest $paymentRequest): void
    {
        try {
            $txnServiceUrl = config('services.transaction_service.url');
            $serviceKey = config('services.internal_service_key');

            $response = Http::withHeaders(['X-Service-Key' => $serviceKey])
                ->post("{$txnServiceUrl}/api/internal/transactions", [
                    'account_id'       => $paymentRequest->account_id,
                    'transaction_ref'  => $paymentRequest->request_ref,
                    'amount'           => $paymentRequest->amount,
                    'type'             => $paymentRequest->type,
                    'operator'         => $paymentRequest->operator_name,
                    'status'           => 'completed',
                    'platform_charge'  => $paymentRequest->platform_charge,
                    'operator_charge'  => $paymentRequest->operator_charge,
                    'currency'         => $paymentRequest->currency,
                    'description'      => $paymentRequest->description ?? ($paymentRequest->type === 'collection' ? 'USSD Collection' : 'Disbursement'),
                    'payment_method'   => 'mobile_money',
                    'operator_receipt' => $paymentRequest->receipt_number ?: $paymentRequest->operator_ref,
                    'phone_number'     => $paymentRequest->phone,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $paymentRequest->update(['transaction_id' => $data['transaction']['id'] ?? null]);
            } else {
                Log::error("Failed to record transaction for {$paymentRequest->request_ref}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Transaction recording failed for {$paymentRequest->request_ref}: " . $e->getMessage());
        }
    }

    /**
     * Update wallet balance after a completed payment.
     */
    private function updateWallet(PaymentRequest $paymentRequest): void
    {
        try {
            $walletServiceUrl = config('services.wallet_service.url');
            $serviceKey = config('services.internal_service_key');

            if ($paymentRequest->type === 'collection') {
                // Credit the collection wallet via internal API
                Http::withHeaders(['X-Service-Key' => $serviceKey])
                    ->post("{$walletServiceUrl}/api/internal/wallet/credit", [
                        'account_id'  => (string) $paymentRequest->account_id,
                        'amount'      => $paymentRequest->amount,
                        'operator'    => $paymentRequest->operator_name,
                        'wallet_type' => 'collection',
                        'reference'   => $paymentRequest->request_ref,
                        'description' => 'Collection via ' . $paymentRequest->operator_name,
                    ]);
            } elseif ($paymentRequest->type === 'disbursement') {
                // Debit the disbursement wallet via internal API
                // User pays only platform charge; operator charge is our cost deducted from platform profit
                $totalDebit = $paymentRequest->amount + $paymentRequest->platform_charge;
                Http::withHeaders(['X-Service-Key' => $serviceKey])
                    ->post("{$walletServiceUrl}/api/internal/wallet/debit", [
                        'account_id'  => (string) $paymentRequest->account_id,
                        'amount'      => $totalDebit,
                        'operator'    => $paymentRequest->operator_name,
                        'wallet_type' => 'disbursement',
                        'reference'   => $paymentRequest->request_ref,
                        'description' => 'Disbursement via ' . $paymentRequest->operator_name,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error("Wallet update failed for {$paymentRequest->request_ref}: " . $e->getMessage());
        }
    }

    /**
     * Process referral commission: if the transacting account was referred by another,
     * read the referrer's commission settings and credit their wallet.
     */
    private function processReferralCommission(PaymentRequest $paymentRequest): void
    {
        try {
            $authServiceUrl = config('services.auth_service.url');
            $txnServiceUrl = config('services.transaction_service.url');
            $walletServiceUrl = config('services.wallet_service.url');
            $serviceKey = config('services.internal_service_key');

            // 1. Get the transacting account to check if it has a referrer
            $accountRes = Http::withHeaders(['X-Service-Key' => $serviceKey])
                ->get("{$authServiceUrl}/api/admin/accounts/{$paymentRequest->account_id}");

            if (!$accountRes->successful()) return;

            $account = $accountRes->json()['account'] ?? null;
            $referrerAccountId = $account['referred_by'] ?? null;

            if (!$referrerAccountId) return; // No referrer, nothing to do

            // 2. Get the referrer account to read commission settings
            $referrerRes = Http::withHeaders(['X-Service-Key' => $serviceKey])
                ->get("{$authServiceUrl}/api/admin/accounts/{$referrerAccountId}");

            if (!$referrerRes->successful()) return;

            $referrer = $referrerRes->json()['account'] ?? null;
            $commissionType = $referrer['commission_type'] ?? null;
            $commissionValue = (float) ($referrer['commission_value'] ?? 0);

            if (!$commissionType || $commissionValue <= 0) return; // No commission configured

            // 3. Calculate the commission amount
            $commissionAmount = 0;
            if ($commissionType === 'fixed') {
                $commissionAmount = $commissionValue;
            } elseif ($commissionType === 'percentage') {
                $commissionAmount = round(($commissionValue / 100) * $paymentRequest->amount, 2);
            }

            if ($commissionAmount <= 0) return;

            // 4. Credit the referrer's wallet
            $walletRef = 'COMM-' . $paymentRequest->request_ref;
            $walletRes = Http::withHeaders(['X-Service-Key' => $serviceKey])
                ->post("{$walletServiceUrl}/api/internal/wallet/credit", [
                    'account_id' => (string) $referrerAccountId,
                    'amount' => $commissionAmount,
                    'operator' => $paymentRequest->operator_name,
                    'wallet_type' => 'collection',
                    'reference' => $walletRef,
                    'description' => 'Referral commission from ' . $paymentRequest->request_ref,
                ]);

            $walletStatus = $walletRes->successful() ? 'credited' : 'failed';

            // 5. Record the earning in transaction-service
            Http::withHeaders(['X-Service-Key' => $serviceKey])
                ->post("{$txnServiceUrl}/api/internal/referral-commission/record", [
                    'referrer_account_id' => $referrerAccountId,
                    'referred_account_id' => $paymentRequest->account_id,
                    'transaction_ref' => $paymentRequest->request_ref,
                    'transaction_amount' => $paymentRequest->amount,
                    'operator' => $paymentRequest->operator_name,
                    'transaction_type' => $paymentRequest->type,
                    'commission_type' => $commissionType,
                    'commission_rate' => $commissionValue,
                    'commission_amount' => $commissionAmount,
                    'status' => $walletStatus,
                    'wallet_reference' => $walletRef,
                ]);

            Log::info("Referral commission {$walletStatus}: {$commissionAmount} to account {$referrerAccountId} from {$paymentRequest->request_ref}");
        } catch (\Exception $e) {
            Log::error("Referral commission failed for {$paymentRequest->request_ref}: " . $e->getMessage());
        }
    }

    /**
     * Send callback to merchant's callback URL.
     */
    private function sendMerchantCallback(PaymentRequest $paymentRequest): void
    {
        try {
            $authServiceUrl = config('services.auth_service.url');
            $accountRes = Http::get("{$authServiceUrl}/api/admin/accounts/{$paymentRequest->account_id}");

            if (!$accountRes->successful()) return;

            $account = $accountRes->json()['account'] ?? null;
            $callbackUrl = $account['callback_url'] ?? null;

            if (!$callbackUrl) {
                $paymentRequest->update(['callback_status' => 'pending']);
                return;
            }

            $payload = [
                'event'          => $paymentRequest->type === 'collection' ? 'payin.completed' : 'payout.completed',
                'request_ref'    => $paymentRequest->request_ref,
                'external_ref'   => $paymentRequest->external_ref,
                'operator_ref'   => $paymentRequest->operator_ref,
                'receipt_number' => $paymentRequest->receipt_number,
                'type'           => $paymentRequest->type,
                'phone'          => $paymentRequest->phone,
                'gross_amount'   => (float) $paymentRequest->amount,
                'platform_charge' => (float) $paymentRequest->platform_charge,
                'operator_charge' => (float) $paymentRequest->operator_charge,
                'net_amount'     => $paymentRequest->type === 'collection'
                    ? (float) $paymentRequest->amount - (float) $paymentRequest->platform_charge
                    : (float) $paymentRequest->amount,
                'currency'       => $paymentRequest->currency,
                'operator'       => $paymentRequest->operator_name,
                'status'         => $paymentRequest->status,
                'timestamp'      => now()->toIso8601String(),
            ];

            $response = Http::timeout(10)->post($callbackUrl, $payload);

            $paymentRequest->update([
                'callback_status' => $response->successful() ? 'sent' : 'failed',
                'callback_attempts' => $paymentRequest->callback_attempts + 1,
                'callback_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Merchant callback failed for {$paymentRequest->request_ref}: " . $e->getMessage());
            $paymentRequest->update([
                'callback_status' => 'failed',
                'callback_attempts' => $paymentRequest->callback_attempts + 1,
            ]);
        }
    }

    /**
     * Map operator status string to our internal status.
     */
    private function mapOperatorStatus(string $operatorStatus): string
    {
        $successStatuses = ['success', 'successful', 'completed', 'approved', 'paid'];
        $failedStatuses = ['failed', 'failure', 'rejected', 'declined', 'error', 'cancelled'];
        $pendingStatuses = ['pending', 'processing', 'initiated', 'sent'];

        if (in_array($operatorStatus, $successStatuses)) {
            return 'completed';
        }
        if (in_array($operatorStatus, $failedStatuses)) {
            return 'failed';
        }
        if (in_array($operatorStatus, $pendingStatuses)) {
            return 'processing';
        }

        return 'processing';
    }

    /**
     * Currency to country calling code mapping.
     */
    private const CURRENCY_PHONE_MAP = [
        'TZS' => '255',
        'KES' => '254',
        'UGX' => '256',
        'RWF' => '250',
        'BIF' => '257',
        'CDF' => '243',
        'MZN' => '258',
        'MWK' => '265',
        'ZMW' => '260',
        'ZAR' => '27',
        'ETB' => '251',
        'NGN' => '234',
        'GHS' => '233',
    ];

    /**
     * Normalize phone number to international format based on currency.
     */
    private function normalizePhone(string $phone, string $currency = 'TZS'): string
    {
        $phone = preg_replace('/[\s\-\.]/', '', $phone);

        $code = self::CURRENCY_PHONE_MAP[$currency] ?? '255';

        if (str_starts_with($phone, '0')) {
            $phone = $code . substr($phone, 1);
        }

        $phone = ltrim($phone, '+');

        return $phone;
    }

    // ================================================================
    //  PAYOUT APPROVAL (Maker-Checker)
    // ================================================================

    /**
     * Determine if a payout requires approval based on user permissions.
     * API key requests (no role/permissions) bypass approval.
     * Owner always has both create+approve, so payouts go directly.
     * Users with only create_payout need approval from approve_payout users.
     */
    private function payoutRequiresApproval(object $user): bool
    {
        $role = $user->role ?? null;

        // API key requests have no role — bypass approval
        if (!$role) {
            return false;
        }

        // Owner always has full authority — no approval needed
        if ($role === 'owner') {
            return false;
        }

        // Get user's effective permissions
        $perms = $user->effective_permissions ?? $user->permissions ?? [];
        if (is_string($perms)) {
            $perms = json_decode($perms, true) ?? [];
        }

        // If user has approve_payout, they can send directly (they ARE the approver)
        if (in_array('approve_payout', $perms)) {
            return false;
        }

        // If user has create_payout only, requires approval
        if (in_array('create_payout', $perms)) {
            return true;
        }

        // Legacy: users with wallet_transfer but no create/approve — send directly
        return false;
    }

    /**
     * List pending payout approvals for account users with approve_payout permission.
     */
    public function pendingPayouts(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        $query = PaymentRequest::where('account_id', $accountId)
            ->where('type', 'disbursement')
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc');

        $payouts = $query->get();

        // Also get summary counts
        $pendingCount = PaymentRequest::where('account_id', $accountId)
            ->where('type', 'disbursement')
            ->where('status', 'pending_approval')
            ->count();

        $pendingTotal = PaymentRequest::where('account_id', $accountId)
            ->where('type', 'disbursement')
            ->where('status', 'pending_approval')
            ->sum('amount');

        return response()->json([
            'payouts' => $payouts,
            'pending_count' => $pendingCount,
            'pending_total' => (float) $pendingTotal,
        ]);
    }

    /**
     * Approve a pending payout and push it to the operator.
     */
    public function approvePayout(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        // Check user has approve_payout permission
        $role = $user->role ?? null;
        $perms = $user->effective_permissions ?? $user->permissions ?? [];
        if (is_string($perms)) $perms = json_decode($perms, true) ?? [];

        if ($role !== 'owner' && !in_array('approve_payout', $perms)) {
            return response()->json(['message' => 'You do not have permission to approve payouts.'], 403);
        }

        $payout = PaymentRequest::where('id', $id)
            ->where('account_id', $accountId)
            ->where('type', 'disbursement')
            ->where('status', 'pending_approval')
            ->first();

        if (!$payout) {
            return response()->json(['message' => 'Payout not found or already processed.'], 404);
        }

        // Prevent self-approval (maker cannot be checker) — except owner
        if ($role !== 'owner' && $payout->created_by && $payout->created_by == ($user->id ?? null)) {
            return response()->json(['message' => 'You cannot approve your own payout request.'], 403);
        }

        // Re-check wallet balance before execution
        $totalDebit = $payout->amount + $payout->platform_charge;
        $balanceCheck = $this->checkWalletBalance($accountId, $totalDebit, $request->bearerToken());
        if (!$balanceCheck['sufficient']) {
            return response()->json([
                'message' => 'Insufficient wallet balance.',
                'required' => $totalDebit,
                'available' => $balanceCheck['balance'] ?? 0,
            ], 422);
        }

        // Get operator
        $operator = Operator::where('code', $payout->operator_code)
            ->where('status', 'active')
            ->first();

        if (!$operator) {
            return response()->json(['message' => 'Operator is no longer active.'], 422);
        }

        // Push to operator
        $pushResult = $this->pushToOperator($operator, $payout, 'disbursement');

        $payout->update([
            'operator_request'  => $pushResult['request_payload'],
            'operator_response' => $pushResult['response'],
            'operator_ref'      => $pushResult['operator_ref'] ?? null,
            'gateway_id'        => $pushResult['gateway_id'] ?? null,
            'status'            => $pushResult['success'] ? 'processing' : 'failed',
            'error_message'     => $pushResult['error'] ?? null,
            'approved_by'       => $user->id ?? null,
            'approved_at'       => now(),
            'approval_notes'    => $request->notes ?? null,
        ]);

        if (!$pushResult['success']) {
            return response()->json([
                'message' => 'Payout approved but failed to send to operator.',
                'error' => $pushResult['error'] ?? 'Operator rejected the request.',
                'payout' => $payout->fresh(),
            ], 422);
        }

        return response()->json([
            'message' => 'Payout approved and sent to operator.',
            'payout' => $payout->fresh(),
        ]);
    }

    /**
     * Reject a pending payout.
     */
    public function rejectPayout(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        // Check user has approve_payout permission
        $role = $user->role ?? null;
        $perms = $user->effective_permissions ?? $user->permissions ?? [];
        if (is_string($perms)) $perms = json_decode($perms, true) ?? [];

        if ($role !== 'owner' && !in_array('approve_payout', $perms)) {
            return response()->json(['message' => 'You do not have permission to reject payouts.'], 403);
        }

        $payout = PaymentRequest::where('id', $id)
            ->where('account_id', $accountId)
            ->where('type', 'disbursement')
            ->where('status', 'pending_approval')
            ->first();

        if (!$payout) {
            return response()->json(['message' => 'Payout not found or already processed.'], 404);
        }

        $payout->update([
            'status'         => 'rejected',
            'approved_by'    => $user->id ?? null,
            'approved_at'    => now(),
            'approval_notes' => $request->notes ?? 'Rejected by approver.',
        ]);

        return response()->json([
            'message' => 'Payout rejected.',
            'payout' => $payout->fresh(),
        ]);
    }

    /**
     * Bulk approve multiple pending payouts.
     */
    public function bulkApprovePayout(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        $role = $user->role ?? null;
        $perms = $user->effective_permissions ?? $user->permissions ?? [];
        if (is_string($perms)) $perms = json_decode($perms, true) ?? [];

        if ($role !== 'owner' && !in_array('approve_payout', $perms)) {
            return response()->json(['message' => 'You do not have permission to approve payouts.'], 403);
        }

        $payouts = PaymentRequest::where('account_id', $accountId)
            ->where('type', 'disbursement')
            ->where('status', 'pending_approval')
            ->whereIn('id', $request->ids)
            ->get();

        $approved = 0;
        $failed = 0;
        $results = [];

        foreach ($payouts as $payout) {
            // Prevent self-approval — except owner
            if ($role !== 'owner' && $payout->created_by && $payout->created_by == ($user->id ?? null)) {
                $results[] = ['id' => $payout->id, 'ref' => $payout->request_ref, 'success' => false, 'error' => 'Cannot approve own request.'];
                $failed++;
                continue;
            }

            $totalDebit = $payout->amount + $payout->platform_charge;
            $balanceCheck = $this->checkWalletBalance($accountId, $totalDebit, $request->bearerToken());
            if (!$balanceCheck['sufficient']) {
                $results[] = ['id' => $payout->id, 'ref' => $payout->request_ref, 'success' => false, 'error' => 'Insufficient balance.'];
                $failed++;
                continue;
            }

            $operator = Operator::where('code', $payout->operator_code)->where('status', 'active')->first();
            if (!$operator) {
                $results[] = ['id' => $payout->id, 'ref' => $payout->request_ref, 'success' => false, 'error' => 'Operator inactive.'];
                $failed++;
                continue;
            }

            $pushResult = $this->pushToOperator($operator, $payout, 'disbursement');

            $payout->update([
                'operator_request'  => $pushResult['request_payload'],
                'operator_response' => $pushResult['response'],
                'operator_ref'      => $pushResult['operator_ref'] ?? null,
                'gateway_id'        => $pushResult['gateway_id'] ?? null,
                'status'            => $pushResult['success'] ? 'processing' : 'failed',
                'error_message'     => $pushResult['error'] ?? null,
                'approved_by'       => $user->id ?? null,
                'approved_at'       => now(),
            ]);

            if ($pushResult['success']) {
                $approved++;
                $results[] = ['id' => $payout->id, 'ref' => $payout->request_ref, 'success' => true, 'status' => 'processing'];
            } else {
                $failed++;
                $results[] = ['id' => $payout->id, 'ref' => $payout->request_ref, 'success' => false, 'error' => $pushResult['error'] ?? 'Operator error.'];
            }
        }

        return response()->json([
            'message' => "Bulk approval: {$approved} approved, {$failed} failed.",
            'approved' => $approved,
            'failed' => $failed,
            'results' => $results,
        ]);
    }

    /**
     * Bulk reject multiple pending payouts.
     */
    /**
     * Admin: Re-push a failed payment request to the operator.
     * Only super_admin can do this. Only failed requests can be re-pushed.
     */
    public function repush(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $paymentRequest = PaymentRequest::find($id);
        if (!$paymentRequest) {
            return response()->json(['message' => 'Payment request not found.'], 404);
        }

        if (!in_array($paymentRequest->status, ['failed', 'timeout'])) {
            return response()->json(['message' => 'Only failed or timed-out requests can be re-pushed.'], 422);
        }

        if ($paymentRequest->receipt_number) {
            return response()->json(['message' => 'This transaction already has an operator receipt. It may have been processed. Re-push is not allowed.'], 422);
        }

        $operator = Operator::where('code', $paymentRequest->operator_code)
            ->where('status', 'active')
            ->first();

        if (!$operator) {
            return response()->json(['message' => 'Operator is no longer active.'], 422);
        }

        // For disbursements, re-check wallet balance
        if ($paymentRequest->type === 'disbursement') {
            $totalDebit = $paymentRequest->amount + $paymentRequest->platform_charge;
            $balanceCheck = $this->checkWalletBalance($paymentRequest->account_id, $totalDebit, $request->bearerToken());
            if (!$balanceCheck['sufficient']) {
                return response()->json([
                    'message' => 'Insufficient wallet balance.',
                    'required' => $totalDebit,
                    'available' => $balanceCheck['balance'] ?? 0,
                ], 422);
            }
        }

        // Reset status and re-push
        $paymentRequest->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        $pushResult = $this->pushToOperator($operator, $paymentRequest, $paymentRequest->type);

        $paymentRequest->update([
            'operator_request'  => $pushResult['request_payload'],
            'operator_response' => $pushResult['response'],
            'operator_ref'      => $pushResult['operator_ref'] ?? $paymentRequest->operator_ref,
            'gateway_id'        => $pushResult['gateway_id'] ?? $paymentRequest->gateway_id,
            'status'            => $pushResult['success'] ? 'processing' : 'failed',
            'error_message'     => $pushResult['error'] ?? null,
        ]);

        Log::info("Admin re-push [{$paymentRequest->request_ref}] by [{$user->email}] => " . ($pushResult['success'] ? 'processing' : 'failed'));

        if (!$pushResult['success']) {
            return response()->json([
                'message' => 'Re-push failed. Operator rejected the request.',
                'error' => $pushResult['error'] ?? 'Unknown error.',
                'payment_request' => $paymentRequest->fresh(),
            ], 422);
        }

        return response()->json([
            'message' => 'Payment request re-pushed to operator successfully.',
            'payment_request' => $paymentRequest->fresh(),
        ]);
    }

    /**
     * Admin: Retry sending the merchant callback for a payment request.
     */
    public function retryCallback(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $paymentRequest = PaymentRequest::find($id);
        if (!$paymentRequest) {
            return response()->json(['message' => 'Payment request not found.'], 404);
        }

        if (!in_array($paymentRequest->status, ['completed', 'failed'])) {
            return response()->json(['message' => 'Callback can only be retried for completed or failed requests.'], 422);
        }

        $this->sendMerchantCallback($paymentRequest);

        $paymentRequest->refresh();

        Log::info("Admin retry callback [{$paymentRequest->request_ref}] by [{$user->email}] => {$paymentRequest->callback_status}");

        return response()->json([
            'message' => $paymentRequest->callback_status === 'sent'
                ? 'Merchant callback sent successfully.'
                : 'Callback retry attempted but delivery failed.',
            'callback_status' => $paymentRequest->callback_status,
            'callback_attempts' => $paymentRequest->callback_attempts,
        ]);
    }

    public function bulkRejectPayout(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
            'notes' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $accountId = $user->account_id ?? null;

        if (!$accountId) {
            return response()->json(['message' => 'No account associated.'], 403);
        }

        $role = $user->role ?? null;
        $perms = $user->effective_permissions ?? $user->permissions ?? [];
        if (is_string($perms)) $perms = json_decode($perms, true) ?? [];

        if ($role !== 'owner' && !in_array('approve_payout', $perms)) {
            return response()->json(['message' => 'You do not have permission to reject payouts.'], 403);
        }

        $count = PaymentRequest::where('account_id', $accountId)
            ->where('type', 'disbursement')
            ->where('status', 'pending_approval')
            ->whereIn('id', $request->ids)
            ->update([
                'status' => 'rejected',
                'approved_by' => $user->id ?? null,
                'approved_at' => now(),
                'approval_notes' => $request->notes ?? 'Bulk rejected.',
            ]);

        return response()->json([
            'message' => "{$count} payout(s) rejected.",
            'rejected' => $count,
        ]);
    }
}
