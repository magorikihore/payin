<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\PaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicPaymentController extends Controller
{
    /**
     * Show the public payment page for an invoice.
     */
    public function show(string $token)
    {
        $invoice = PaymentRequest::where('payment_token', $token)
            ->where('type', 'manual_c2b')
            ->first();

        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }

        return view('pay', ['token' => $token]);
    }

    /**
     * API: Get invoice details by payment token (public, no auth).
     */
    public function details(string $token): JsonResponse
    {
        $invoice = PaymentRequest::where('payment_token', $token)
            ->where('type', 'manual_c2b')
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        // Check expiry
        $expiresAt = $invoice->error_message; // expiry stored in error_message
        if ($expiresAt && now()->gt($expiresAt)) {
            return response()->json(['message' => 'This invoice has expired.'], 410);
        }

        if (!in_array($invoice->status, ['waiting', 'processing', 'completed'])) {
            return response()->json(['message' => 'This invoice is no longer active.'], 410);
        }

        // Get business name from auth-service
        $businessName = null;
        try {
            $authServiceUrl = config('services.auth_service.url');
            $res = \Illuminate\Support\Facades\Http::timeout(5)
                ->get("{$authServiceUrl}/api/internal/accounts/{$invoice->account_id}");
            if ($res->ok()) {
                $businessName = $res->json('business_name');
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch business name: ' . $e->getMessage());
        }

        return response()->json([
            'reference' => $invoice->external_ref,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency ?? 'TZS',
            'description' => $invoice->description,
            'status' => $invoice->status,
            'business_name' => $businessName,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * API: Initiate USSD push payment for an invoice (public, no auth).
     */
    public function initiate(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
        ]);

        $invoice = PaymentRequest::where('payment_token', $token)
            ->where('type', 'manual_c2b')
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        if ($invoice->status !== 'waiting') {
            return response()->json(['message' => 'This invoice is no longer waiting for payment.'], 422);
        }

        // Check expiry
        $expiresAt = $invoice->error_message;
        if ($expiresAt && now()->gt($expiresAt)) {
            $invoice->update(['status' => 'expired']);
            return response()->json(['message' => 'This invoice has expired.'], 410);
        }

        // Normalize phone and detect operator
        $phone = $this->normalizePhone($request->phone, $invoice->currency ?? 'TZS');
        $operator = Operator::detectByPhone($phone);

        if (!$operator) {
            return response()->json(['message' => 'Could not detect mobile operator for this phone number.'], 422);
        }

        // Update the invoice with phone and operator info
        $invoice->update([
            'phone' => $phone,
            'operator_code' => $operator->code,
            'operator_name' => $operator->name,
            'status' => 'pending',
        ]);

        // Push to operator (USSD/STK push) — reuse PaymentController logic
        $paymentController = app(PaymentController::class);
        $pushResult = $this->pushToOperator($operator, $invoice);

        $invoice->update([
            'operator_request'  => $pushResult['request_payload'],
            'operator_response' => $pushResult['response'],
            'operator_ref'      => $pushResult['operator_ref'] ?? null,
            'gateway_id'        => $pushResult['gateway_id'] ?? null,
            'status'            => $pushResult['success'] ? 'processing' : 'failed',
            'error_message'     => $pushResult['success'] ? $invoice->error_message : ($pushResult['error'] ?? 'Operator rejected the request.'),
        ]);

        if (!$pushResult['success']) {
            // Revert to waiting so customer can try again
            $invoice->update([
                'status' => 'waiting',
                'error_message' => $expiresAt,
            ]);
            return response()->json([
                'message' => 'Failed to send USSD prompt. Please try again.',
                'error' => $pushResult['error'] ?? 'Operator rejected the request.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'USSD prompt sent. Please confirm payment on your phone.',
            'status' => 'processing',
        ]);
    }

    /**
     * API: Check invoice payment status (public, no auth).
     */
    public function status(string $token): JsonResponse
    {
        $invoice = PaymentRequest::where('payment_token', $token)
            ->where('type', 'manual_c2b')
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        return response()->json([
            'status' => $invoice->status,
            'receipt_number' => $invoice->receipt_number,
        ]);
    }

    /**
     * Push payment request to operator gateway.
     */
    private function pushToOperator(Operator $operator, PaymentRequest $paymentRequest): array
    {
        try {
            $gateway = \App\Gateways\GatewayFactory::make($operator->gateway_type ?? 'digivas');
            return $gateway->push($operator, $paymentRequest, 'collection');
        } catch (\Throwable $e) {
            Log::error("Push to operator failed [{$operator->code}]: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment gateway error.',
                'request_payload' => null,
                'response' => null,
            ];
        }
    }

    private const CURRENCY_PHONE_MAP = [
        'TZS' => '255', 'KES' => '254', 'UGX' => '256', 'RWF' => '250',
        'BIF' => '257', 'CDF' => '243', 'MZN' => '258', 'MWK' => '265',
        'ZMW' => '260', 'ZAR' => '27',  'ETB' => '251', 'NGN' => '234',
        'GHS' => '233',
    ];

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
}
