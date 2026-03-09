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
     * Operator Callback: Receives payment status updates from operator.
     * This endpoint is PUBLIC (no auth) — operators call it with their response.
     * Uses the gateway adapter to parse and validate the callback payload.
     */
    public function callback(Request $request, $operator_code): JsonResponse
    {
        Log::info("Operator callback received for [{$operator_code}]", $request->all());

        $operator = Operator::where('code', $operator_code)->first();
        if (!$operator) {
            Log::warning("Callback from unknown operator: {$operator_code}");
            return response()->json(['message' => 'Unknown operator.'], 404);
        }

        $payload = $request->all();

        // Resolve gateway adapter
        try {
            $gateway = GatewayFactory::make($operator->gateway_type ?? 'digivas');
        } catch (\InvalidArgumentException $e) {
            Log::error("Unsupported gateway type [{$operator->gateway_type}] for callback [{$operator_code}]");
            return response()->json(['message' => 'Unsupported gateway type.'], 500);
        }

        // Validate the callback (spPassword, signature, etc.)
        if (!$gateway->validateCallback($operator, $payload)) {
            Log::warning("Callback validation failed for [{$operator_code}]");
            return response()->json(['message' => 'Invalid credentials.'], 403);
        }

        // Parse the callback into a normalized format
        $parsed = $gateway->parseCallback($operator, $payload);

        // Find payment request by gateway_id, operator_ref, or reference
        $paymentRequest = null;
        $searchFields = [
            'gateway_id'   => $parsed['gateway_id'] ?? null,
            'operator_ref' => $parsed['operator_ref'] ?? null,
            'request_ref'  => $parsed['reference'] ?? null,
        ];

        foreach ($searchFields as $field => $value) {
            if ($value && !$paymentRequest) {
                $paymentRequest = PaymentRequest::where($field, (string) $value)
                    ->where('operator_code', $operator_code)->first();
            }
        }

        // Fallback: search by reference in operator_ref column
        if (!$paymentRequest && !empty($parsed['reference'])) {
            $paymentRequest = PaymentRequest::where('operator_ref', (string) $parsed['reference'])
                ->where('operator_code', $operator_code)->first();
        }

        // Last resort: match by phone + operator + processing status
        if (!$paymentRequest && !empty($parsed['phone'])) {
            $paymentRequest = PaymentRequest::where('phone', $parsed['phone'])
                ->where('operator_code', $operator_code)
                ->where('status', 'processing')
                ->orderBy('created_at', 'desc')->first();
        }

        if (!$paymentRequest) {
            Log::warning("Callback: payment request not found", [
                'parsed' => $parsed, 'operator' => $operator_code,
            ]);
            return response()->json(['message' => 'Payment request not found.'], 404);
        }

        $newStatus = $parsed['status'] ?? 'processing';

        $paymentRequest->update([
            'callback_data'  => $payload,
            'receipt_number' => $parsed['receipt_number'] ?: $paymentRequest->receipt_number,
            'operator_ref'   => $parsed['operator_ref'] ?: $paymentRequest->operator_ref,
            'gateway_id'     => $parsed['gateway_id'] ? (string) $parsed['gateway_id'] : $paymentRequest->gateway_id,
            'status'         => $newStatus,
            'error_message'  => ($newStatus === 'failed') ? ($parsed['error_message'] ?? 'Operator returned failure') : null,
        ]);

        // If completed, record the transaction and update wallet
        if ($newStatus === 'completed') {
            $this->recordTransaction($paymentRequest);
            $this->updateWallet($paymentRequest);
            $this->processReferralCommission($paymentRequest);
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
                  ->orWhere('receipt_number', 'like', "%{$search}%")
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
