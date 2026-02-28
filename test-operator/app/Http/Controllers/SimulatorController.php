<?php

namespace App\Http\Controllers;

use App\Models\SimulatorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimulatorController extends Controller
{
    /**
     * Receive a collection request from Payin (DIGIVAS EPG format).
     * POST /api/ussd/collection
     */
    public function collection(Request $request): JsonResponse
    {
        return $this->handleRequest($request, 'collection');
    }

    /**
     * Receive a disbursement request from Payin (DIGIVAS EPG format).
     * POST /api/ussd/disbursement
     */
    public function disbursement(Request $request): JsonResponse
    {
        return $this->handleRequest($request, 'disbursement');
    }

    /**
     * Handle incoming operator request from Payin.
     */
    private function handleRequest(Request $request, string $type): JsonResponse
    {
        Log::info("Test Operator: Received {$type} request", $request->all());

        $header = $request->input('header', []);
        $body   = $request->input('body.request', []);

        $spId         = $header['spId'] ?? '';
        $merchantCode = $header['merchantCode'] ?? '';
        $spPassword   = $header['spPassword'] ?? '';
        $timestamp    = $header['timestamp'] ?? '';

        // Validate spPassword
        $authValid = $this->validateSpPassword($spId, $spPassword, $timestamp);

        // Extract request fields
        $command       = $body['command'] ?? ($type === 'collection' ? 'UssdPush' : 'Disbursement');
        $reference     = $body['reference'] ?? $body['transactionID'] ?? '';
        $transactionId = $body['transactionID'] ?? '';
        $msisdn        = $body['msisdn'] ?? '';
        $amount        = $body['amount'] ?? 0;
        $currency      = $body['currency'] ?? 'TZS';
        $callbackUrl   = $body['callbackUrl'] ?? '';

        // Generate a unique gateway ID
        $gatewayId = rand(1000000000, 9999999999);

        // Store the request
        $simRequest = SimulatorRequest::create([
            'type'           => $type,
            'command'        => $command,
            'reference'      => $reference,
            'transaction_id' => $transactionId,
            'msisdn'         => $msisdn,
            'amount'         => $amount,
            'currency'       => $currency,
            'callback_url'   => $callbackUrl,
            'sp_id'          => $spId,
            'merchant_code'  => $merchantCode,
            'auth_valid'     => $authValid,
            'gateway_id'     => $gatewayId,
            'response_code'  => $authValid ? '0' : '2',
            'raw_request'    => $request->all(),
        ]);

        // If auth failed, reject
        if (!$authValid) {
            $responsePayload = $this->buildResponse($simRequest, '2', 'Invalid credentials');
            $simRequest->update(['raw_response' => $responsePayload]);

            return response()->json($responsePayload, 403);
        }

        // Build success acknowledgment
        $responsePayload = $this->buildResponse($simRequest, '0', 'Payment Request has been Accepted Successfully. Waiting for customer confirmation.');
        $simRequest->update(['raw_response' => $responsePayload]);

        // Auto-callback if enabled
        if (config('operator.auto_callback')) {
            $result = config('operator.auto_callback_result', 'success');

            // Send callback after response using Laravel's terminating middleware
            // This runs AFTER the response is sent to Payin
            app()->terminating(function () use ($simRequest, $result) {
                $delay = (int) config('operator.auto_callback_delay', 3);
                if ($delay > 0) {
                    sleep($delay);
                }
                $this->sendCallback($simRequest, $result);
            });
        }

        return response()->json($responsePayload, 200);
    }

    /**
     * Validate spPassword: base64(sha256(spId + password + timestamp))
     */
    private function validateSpPassword(string $spId, string $receivedPassword, string $timestamp): bool
    {
        $configSpId = config('operator.sp_id');
        $configPassword = config('operator.sp_password');

        // Check spId matches
        if ($spId !== $configSpId) {
            Log::warning("Test Operator: spId mismatch. Expected [{$configSpId}], got [{$spId}]");
            return false;
        }

        // Compute expected password
        $raw = $spId . $configPassword . $timestamp;
        $expected = base64_encode(hash('sha256', $raw, true));

        if ($receivedPassword !== $expected) {
            Log::warning("Test Operator: spPassword mismatch", [
                'expected' => $expected,
                'received' => $receivedPassword,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Build DIGIVAS EPG acknowledgment response.
     */
    private function buildResponse(SimulatorRequest $simRequest, string $responseCode, string $responseStatus): array
    {
        $timestamp = now()->format('YmdHis');
        $spId = config('operator.sp_id');
        $merchantCode = config('operator.merchant_code');
        $spPassword = config('operator.sp_password');

        return [
            'header' => [
                'spId'         => $spId,
                'merchantCode' => $merchantCode,
                'spPassword'   => base64_encode(hash('sha256', $spId . $spPassword . $timestamp, true)),
                'timestamp'    => $timestamp,
            ],
            'body' => [
                'response' => [
                    'transactionNumber' => (string) $simRequest->id,
                    'gatewayId'         => $simRequest->gateway_id,
                    'responseCode'      => $responseCode,
                    'responseStatus'    => $responseStatus,
                    'reference'         => $simRequest->reference,
                    'apiVersion'        => '5.0',
                ],
            ],
        ];
    }

    /**
     * Send callback to Payin's callback URL.
     * Called either automatically (via queue) or manually (via dashboard).
     */
    public function sendCallback(SimulatorRequest $simRequest, string $result = 'success'): bool
    {
        if (empty($simRequest->callback_url)) {
            Log::warning("Test Operator: No callback URL for request #{$simRequest->id}");
            $simRequest->update([
                'callback_status' => 'failed',
                'callback_result' => $result,
            ]);
            return false;
        }

        $timestamp = now()->format('YmdHis');
        $spId = config('operator.sp_id');
        $merchantCode = config('operator.merchant_code');
        $spPassword = config('operator.sp_password');

        $header = [
            'spId'         => $spId,
            'merchantCode' => $merchantCode,
            'spPassword'   => base64_encode(hash('sha256', $spId . $spPassword . $timestamp, true)),
            'timestamp'    => $timestamp,
        ];

        $receiptNumber = ($result === 'success') ? 'RCT-' . strtoupper(substr(md5(uniqid()), 0, 10)) : null;
        $isSuccess = ($result === 'success');

        if ($simRequest->type === 'collection') {
            // Collection callback format: body.request
            $callbackPayload = [
                'header' => $header,
                'body' => [
                    'request' => [
                        'command'       => 'Collection',
                        'reference'     => $simRequest->reference,
                        'gatewayId'     => $simRequest->gateway_id,
                        'receiptNumber' => $receiptNumber,
                        'msisdn'        => $simRequest->msisdn,
                        'amount'        => (string) $simRequest->amount,
                        'network'       => config('operator.name', 'TestOperator'),
                    ],
                ],
            ];
        } else {
            // Disbursement callback format: body.result
            $callbackPayload = [
                'header' => $header,
                'body' => [
                    'result' => [
                        'resultCode'        => $isSuccess ? '0' : '1',
                        'resultStatus'      => $isSuccess ? 'SUCCESS' : 'FAILED',
                        'receiptNumber'     => $receiptNumber,
                        'amount'            => (string) $simRequest->amount,
                        'date'              => now()->toDateTimeString(),
                        'referenceNumber'   => $simRequest->reference,
                        'transactionNumber' => (string) $simRequest->gateway_id,
                        'message'           => $isSuccess ? 'SUCCESS' : 'Transaction failed',
                    ],
                ],
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post($simRequest->callback_url, $callbackPayload);

            $simRequest->update([
                'callback_status'   => $response->successful() ? 'sent' : 'failed',
                'callback_result'   => $result,
                'receipt_number'    => $receiptNumber,
                'callback_sent_at'  => now(),
                'callback_response' => $response->json() ?? ['status' => $response->status(), 'body' => $response->body()],
            ]);

            Log::info("Test Operator: Callback sent for #{$simRequest->id}", [
                'url'    => $simRequest->callback_url,
                'result' => $result,
                'status' => $response->status(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Test Operator: Callback failed for #{$simRequest->id}: " . $e->getMessage());
            $simRequest->update([
                'callback_status'  => 'failed',
                'callback_result'  => $result,
                'callback_sent_at' => now(),
            ]);
            return false;
        }
    }

    // ===================================================================
    //  DASHBOARD API ENDPOINTS
    // ===================================================================

    /**
     * Dashboard: List all requests.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SimulatorRequest::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('callback_status')) {
            $query->where('callback_status', $request->callback_status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('msisdn', 'like', "%{$search}%")
                  ->orWhere('gateway_id', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($requests);
    }

    /**
     * Dashboard: Get single request details.
     */
    public function show(int $id): JsonResponse
    {
        $simRequest = SimulatorRequest::findOrFail($id);
        return response()->json($simRequest);
    }

    /**
     * Dashboard: Manually trigger callback (approve or reject).
     */
    public function triggerCallback(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'result' => 'required|in:success,failed',
        ]);

        $simRequest = SimulatorRequest::findOrFail($id);

        if ($simRequest->callback_status === 'sent') {
            return response()->json(['message' => 'Callback already sent for this request.'], 422);
        }

        $sent = $this->sendCallback($simRequest, $request->result);

        $simRequest->refresh();

        return response()->json([
            'message' => $sent ? 'Callback sent successfully.' : 'Failed to send callback.',
            'request' => $simRequest,
        ]);
    }

    /**
     * Dashboard: Get stats.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total'        => SimulatorRequest::count(),
            'collections'  => SimulatorRequest::where('type', 'collection')->count(),
            'disbursements' => SimulatorRequest::where('type', 'disbursement')->count(),
            'pending'      => SimulatorRequest::where('callback_status', 'pending')->count(),
            'sent'         => SimulatorRequest::where('callback_status', 'sent')->count(),
            'failed'       => SimulatorRequest::where('callback_status', 'failed')->count(),
            'auto_callback' => config('operator.auto_callback'),
            'auto_delay'    => config('operator.auto_callback_delay'),
            'auto_result'   => config('operator.auto_callback_result'),
            'operator_name' => config('operator.name'),
            'operator_code' => config('operator.code'),
            'sp_id'         => config('operator.sp_id'),
        ]);
    }

    /**
     * Dashboard: Clear all requests.
     */
    public function clear(): JsonResponse
    {
        SimulatorRequest::truncate();
        return response()->json(['message' => 'All requests cleared.']);
    }

    /**
     * Test endpoint: Verify the operator simulator is running.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status'  => 'ok',
            'service' => 'Test Operator Simulator',
            'name'    => config('operator.name'),
            'code'    => config('operator.code'),
            'time'    => now()->toIso8601String(),
        ]);
    }
}
