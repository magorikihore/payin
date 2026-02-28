<?php

namespace App\Http\Controllers;

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
        $requestRef = 'PAY-' . strtoupper(Str::random(12));
        $paymentRequest = PaymentRequest::create([
            'account_id'      => $accountId,
            'request_ref'     => $requestRef,
            'external_ref'    => $request->reference,
            'type'            => 'collection',
            'phone'           => $this->normalizePhone($request->phone),
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
                'success'     => false,
                'message'     => 'Failed to push collection to operator.',
                'error'       => $pushResult['error'] ?? 'Operator rejected the request.',
                'request_ref' => $requestRef,
            ], 422);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Collection request sent to operator. Waiting for customer confirmation.',
            'request_ref'  => $requestRef,
            'operator_ref' => $pushResult['operator_ref'] ?? null,
            'gateway_id'   => $pushResult['gateway_id'] ?? null,
            'status'       => 'processing',
            'phone'        => $paymentRequest->phone,
            'amount'       => $paymentRequest->amount,
            'operator'     => $operator->name,
        ], 201);
    }

    /**
     * Push Disbursement: Send money to a phone number.
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

        // Check wallet balance (has enough for disbursement + charges)
        $charges = $this->calculateCharges($accountId, $request->amount, $operator->code, 'disbursement');
        $totalDebit = $request->amount + ($charges['platform_charge'] ?? 0) + ($charges['operator_charge'] ?? 0);

        $balanceCheck = $this->checkWalletBalance($accountId, $totalDebit, $request->bearerToken());
        if (!$balanceCheck['sufficient']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.',
                'required' => $totalDebit,
                'available' => $balanceCheck['balance'] ?? 0,
            ], 422);
        }

        // Create payment request record
        $requestRef = 'PAY-' . strtoupper(Str::random(12));
        $paymentRequest = PaymentRequest::create([
            'account_id'      => $accountId,
            'request_ref'     => $requestRef,
            'external_ref'    => $request->reference,
            'type'            => 'disbursement',
            'phone'           => $this->normalizePhone($request->phone),
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
                'success'     => false,
                'message'     => 'Failed to push disbursement to operator.',
                'error'       => $pushResult['error'] ?? 'Operator rejected the request.',
                'request_ref' => $requestRef,
            ], 422);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Disbursement request sent to operator.',
            'request_ref'  => $requestRef,
            'operator_ref' => $pushResult['operator_ref'] ?? null,
            'gateway_id'   => $pushResult['gateway_id'] ?? null,
            'status'       => 'processing',
            'phone'        => $paymentRequest->phone,
            'amount'       => $paymentRequest->amount,
            'operator'     => $operator->name,
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
     * Operator Callback: Receives payment status updates from operator.
     * This endpoint is PUBLIC (no auth) — operators call it with their response.
     * 
     * Operator may send header/body format or flat JSON.
     * We look for: gatewayId, reference, responseCode, responseStatus, receipt, status
     */
    public function callback(Request $request, $operator_code): JsonResponse
    {
        Log::info("Operator callback received for [{$operator_code}]", $request->all());

        $operator = Operator::where('code', $operator_code)->first();
        if (!$operator) {
            Log::warning("Callback from unknown operator: {$operator_code}");
            return response()->json(['message' => 'Unknown operator.'], 404);
        }

        // Validate operator spPassword if header is present
        $headerData = $request->input('header');
        if ($headerData && !empty($headerData['spPassword']) && !empty($headerData['timestamp'])) {
            $expectedPassword = $operator->generateSpPassword($headerData['timestamp']);
            if ($headerData['spPassword'] !== $expectedPassword) {
                Log::warning("Callback spPassword mismatch for [{$operator_code}]", [
                    'received_spId' => $headerData['spId'] ?? null,
                ]);
                return response()->json(['message' => 'Invalid credentials.'], 403);
            }
        }

        // Parse operator callback — DIGIVAS EPG uses TWO callback formats:
        //
        // COLLECTION callback: body.request.{ command:"Collection", reference, gatewayId, receiptNumber, msisdn, amount, network }
        //   → receiptNumber present with command "Collection" = successful collection
        //
        // DISBURSEMENT callback: body.result.{ resultCode, resultStatus, receiptNumber, amount, date, referenceNumber, transactionNumber, message }
        //   → resultCode "0" / message "SUCCESS" = successful disbursement
        //
        // Also supports body.response (acknowledgment format) as fallback

        $bodyRequest  = $request->input('body.request');  // Collection callback
        $bodyResult   = $request->input('body.result');    // Disbursement callback
        $bodyResponse = $request->input('body.response');  // Acknowledgment fallback

        // Detect which callback format we received
        $isCollectionCallback = $bodyRequest && strtolower($bodyRequest['command'] ?? '') === 'collection';

        if ($isCollectionCallback) {
            // ── COLLECTION CALLBACK (body.request) ──
            $gatewayId      = $bodyRequest['gatewayId'] ?? null;
            $reference      = $bodyRequest['reference'] ?? null;
            $receiptNumber  = $bodyRequest['receiptNumber'] ?? null;
            $msisdn         = $bodyRequest['msisdn'] ?? null;
            $callbackAmount = $bodyRequest['amount'] ?? null;
            $network        = $bodyRequest['network'] ?? null;

            // Find payment request
            $paymentRequest = null;
            if ($gatewayId) {
                $paymentRequest = PaymentRequest::where('gateway_id', (string) $gatewayId)
                    ->where('operator_code', $operator_code)->first();
            }
            if (!$paymentRequest && $reference) {
                $paymentRequest = PaymentRequest::where('request_ref', $reference)
                    ->where('operator_code', $operator_code)->first();
                if (!$paymentRequest) {
                    $paymentRequest = PaymentRequest::where('operator_ref', $reference)
                        ->where('operator_code', $operator_code)->first();
                }
            }
            if (!$paymentRequest && $msisdn) {
                // Last resort: match by phone + operator + processing status
                $paymentRequest = PaymentRequest::where('phone', $msisdn)
                    ->where('operator_code', $operator_code)
                    ->where('status', 'processing')
                    ->orderBy('created_at', 'desc')->first();
            }

            if (!$paymentRequest) {
                Log::warning("Collection callback: payment request not found", [
                    'gateway_id' => $gatewayId, 'reference' => $reference,
                    'receiptNumber' => $receiptNumber, 'msisdn' => $msisdn,
                    'operator' => $operator_code,
                ]);
                return response()->json(['message' => 'Payment request not found.'], 404);
            }

            // Collection callback with receiptNumber = successful
            $newStatus = $receiptNumber ? 'completed' : 'failed';

            $paymentRequest->update([
                'callback_data' => $request->all(),
                'operator_ref'  => $receiptNumber ?: ($reference ?: $paymentRequest->operator_ref),
                'gateway_id'    => $gatewayId ? (string) $gatewayId : $paymentRequest->gateway_id,
                'status'        => $newStatus,
                'error_message' => ($newStatus === 'failed') ? 'Collection callback without receipt number' : null,
            ]);

        } else {
            // ── DISBURSEMENT CALLBACK (body.result) or fallback ──
            $body = $bodyResult ?? $bodyResponse ?? $request->input('body') ?? $request->all();

            $resultCode        = (string) ($body['resultCode'] ?? $body['responseCode'] ?? '');
            $resultStatus      = $body['resultStatus'] ?? $body['responseStatus'] ?? '';
            $receiptNumber     = $body['receiptNumber'] ?? null;
            $referenceNumber   = $body['referenceNumber'] ?? $body['reference'] ?? null;
            $transactionNumber = $body['transactionNumber'] ?? null;
            $gatewayId         = $body['gatewayId'] ?? null;
            $callbackAmount    = $body['amount'] ?? null;
            $callbackMessage   = strtolower($body['message'] ?? '');

            // Find payment request
            $paymentRequest = null;
            if ($gatewayId) {
                $paymentRequest = PaymentRequest::where('gateway_id', (string) $gatewayId)
                    ->where('operator_code', $operator_code)->first();
            }
            if (!$paymentRequest && $transactionNumber) {
                $paymentRequest = PaymentRequest::where('operator_ref', (string) $transactionNumber)
                    ->where('operator_code', $operator_code)->first();
            }
            if (!$paymentRequest && $referenceNumber) {
                $paymentRequest = PaymentRequest::where('request_ref', $referenceNumber)
                    ->where('operator_code', $operator_code)->first();
                if (!$paymentRequest) {
                    $paymentRequest = PaymentRequest::where('operator_ref', $referenceNumber)
                        ->where('operator_code', $operator_code)->first();
                }
            }

            if (!$paymentRequest) {
                Log::warning("Disbursement callback: payment request not found", [
                    'gateway_id' => $gatewayId, 'transactionNumber' => $transactionNumber,
                    'referenceNumber' => $referenceNumber, 'receiptNumber' => $receiptNumber,
                    'operator' => $operator_code,
                ]);
                return response()->json(['message' => 'Payment request not found.'], 404);
            }

            // resultCode "0" or message "SUCCESS" = completed
            $newStatus = 'processing';
            if ($resultCode === '0' || $callbackMessage === 'success') {
                $newStatus = 'completed';
            } elseif ($resultCode !== '' && $resultCode !== '0') {
                $newStatus = 'failed';
            } elseif ($callbackMessage) {
                $newStatus = $this->mapOperatorStatus($callbackMessage);
            }

            $paymentRequest->update([
                'callback_data' => $request->all(),
                'operator_ref'  => $receiptNumber ?: ($transactionNumber ?: ($referenceNumber ?: $paymentRequest->operator_ref)),
                'gateway_id'    => $gatewayId ? (string) $gatewayId : $paymentRequest->gateway_id,
                'status'        => $newStatus,
                'error_message' => ($newStatus === 'failed') ? ($resultStatus ?: 'Operator returned error code: ' . $resultCode) : null,
            ]);
        }

        // If completed, record the transaction and update wallet
        if ($newStatus === 'completed') {
            $this->recordTransaction($paymentRequest);
            $this->updateWallet($paymentRequest);
        }

        // Send callback to merchant
        $this->sendMerchantCallback($paymentRequest);

        Log::info("Callback processed for [{$paymentRequest->request_ref}] => {$newStatus}");

        return response()->json([
            'message' => 'Callback processed successfully.',
            'request_ref' => $paymentRequest->request_ref,
            'status' => $newStatus,
        ]);
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
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('type')) { $query->where('type', $request->type); }
        if ($request->filled('operator')) { $query->where('operator_code', $request->operator); }
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

            // Check wallet balance
            $charges = $this->calculateCharges($accountId, $item['amount'], $operator->code, 'disbursement');
            $totalDebit = $item['amount'] + ($charges['platform_charge'] ?? 0) + ($charges['operator_charge'] ?? 0);

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

            // Create payment request
            $requestRef = 'PAY-' . strtoupper(Str::random(12));
            $paymentRequest = PaymentRequest::create([
                'account_id'      => $accountId,
                'request_ref'     => $requestRef,
                'external_ref'    => $item['reference'] ?? null,
                'type'            => 'disbursement',
                'phone'           => $this->normalizePhone($item['phone']),
                'amount'          => $item['amount'],
                'platform_charge' => $charges['platform_charge'] ?? 0,
                'operator_charge' => $charges['operator_charge'] ?? 0,
                'currency'        => 'TZS',
                'operator_code'   => $operator->code,
                'operator_name'   => $operator->name,
                'status'          => 'pending',
                'description'     => $item['description'] ?? null,
                'batch_name'      => $batchName,
            ]);

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

        return response()->json([
            'success' => $failCount === 0,
            'message' => "Batch complete: {$successCount} sent, {$failCount} failed.",
            'sent' => $successCount,
            'failed' => $failCount,
            'total' => count($request->items),
            'results' => $results,
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
    private function pushToOperator(Operator $operator, PaymentRequest $paymentRequest, string $type): array
    {
        $path = ($type === 'collection') ? $operator->collection_path : $operator->disbursement_path;

        if (!$path) {
            return [
                'success' => false,
                'error' => "Operator [{$operator->name}] has no {$type} path configured.",
                'request_payload' => null,
                'response' => null,
            ];
        }

        $url = rtrim($operator->api_url, '/') . '/' . ltrim($path, '/');

        // Build operator header with spId, merchantCode, spPassword, timestamp, apiVersion
        $apiHeader = $operator->buildApiHeader();

        // Map type to DIGIVAS command
        $command = ($type === 'collection') ? 'UssdPush' : 'Disbursement';

        $payload = [
            'header' => $apiHeader,
            'body' => [
                'request' => [
                    'command'            => $command,
                    'command1'           => $command,
                    'reference'          => $paymentRequest->request_ref,
                    'transactionID'      => $paymentRequest->request_ref,
                    'msisdn'             => $paymentRequest->phone,
                    'amount'             => (string) $paymentRequest->amount,
                    'currency'           => $paymentRequest->currency,
                    'transactionChannel' => 'MOBAPP',
                    'callbackUrl'        => $operator->callback_url,
                ],
            ],
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $responseData = $response->json() ?? [];

            // Extract the operator acknowledgment: body.response
            $body = $responseData['body']['response'] ?? $responseData['body'] ?? $responseData;
            $responseCode = (string) ($body['responseCode'] ?? '');
            $gatewayId = $body['gatewayId'] ?? null;
            $reference = $body['reference'] ?? null;
            $transactionNumber = $body['transactionNumber'] ?? null;
            $responseStatus = $body['responseStatus'] ?? '';

            // responseCode "0" means operator accepted the request
            if ($responseCode === '0') {
                Log::info("Operator push accepted [{$operator->code}]", [
                    'gatewayId' => $gatewayId,
                    'transactionNumber' => $transactionNumber,
                    'reference' => $reference,
                    'responseStatus' => $responseStatus,
                ]);

                return [
                    'success' => true,
                    'operator_ref' => (string) ($transactionNumber ?: $reference),
                    'gateway_id' => $gatewayId,
                    'request_payload' => $payload,
                    'response' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $responseStatus ?: ('Operator error code: ' . $responseCode),
                'request_payload' => $payload,
                'response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error("Operator push failed [{$operator->code}]: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to connect to operator: ' . $e->getMessage(),
                'request_payload' => $payload,
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
            $response = Http::post("{$txnServiceUrl}/api/charges/calculate", [
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
            $response = Http::withToken($token)
                ->get("{$walletServiceUrl}/api/wallet");

            if ($response->successful()) {
                $data = $response->json();
                $disbursementBalance = (float) ($data['disbursement_total'] ?? 0);
                return [
                    'sufficient' => $disbursementBalance >= $totalAmount,
                    'balance' => $disbursementBalance,
                ];
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
                    'operator_receipt' => $paymentRequest->operator_ref,
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
                $totalDebit = $paymentRequest->amount + $paymentRequest->platform_charge + $paymentRequest->operator_charge;
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
                'type'           => $paymentRequest->type,
                'phone'          => $paymentRequest->phone,
                'gross_amount'   => (float) $paymentRequest->amount,
                'platform_charge' => (float) $paymentRequest->platform_charge,
                'operator_charge' => (float) $paymentRequest->operator_charge,
                'net_amount'     => $paymentRequest->type === 'collection'
                    ? (float) $paymentRequest->amount - (float) $paymentRequest->platform_charge - (float) $paymentRequest->operator_charge
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
     * Normalize phone number to standard format.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\.]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        }

        $phone = ltrim($phone, '+');

        return $phone;
    }
}
