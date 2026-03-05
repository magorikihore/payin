<?php

namespace App\Gateways;

use App\Models\Operator;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DIGIVAS EPG Gateway Adapter.
 * Used by Tanzania operators: M-Pesa TZ, Tigo Pesa, Airtel Money TZ, Halopesa.
 * Uses header/body format with spId, merchantCode, spPassword authentication.
 */
class DigivasGateway implements GatewayInterface
{
    public function push(Operator $operator, PaymentRequest $paymentRequest, string $type): array
    {
        $path = ($type === 'collection') ? $operator->collection_path : $operator->disbursement_path;

        if (!$path) {
            return [
                'success' => false,
                'error' => "Operator [{$operator->name}] has no {$type} path configured.",
                'request_payload' => null,
                'response' => null,
                'operator_ref' => null,
                'gateway_id' => null,
            ];
        }

        $url = rtrim($operator->api_url, '/') . '/' . ltrim($path, '/');
        $command = ($type === 'collection') ? 'UssdPush' : 'Disbursement';

        // Build DIGIVAS header with spPassword
        $timestamp = now()->format('YmdHis');
        $apiHeader = [
            'spId'         => $operator->sp_id,
            'merchantCode' => $operator->merchant_code,
            'spPassword'   => $this->generateSpPassword($operator, $timestamp),
            'timestamp'    => $timestamp,
            'apiVersion'   => $operator->api_version ?? '5.0',
        ];

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
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post($url, $payload);

            $responseData = $response->json() ?? [];

            // Handle HTTP-level errors (401, 403, 500, etc.) before parsing body
            if ($response->status() >= 400) {
                $httpError = $responseData['error'] ?? $responseData['message'] ?? ('HTTP ' . $response->status());
                Log::error("DIGIVAS push HTTP error [{$operator->code}]: {$response->status()} - {$httpError}", [
                    'url' => $url, 'response' => $responseData,
                ]);
                return [
                    'success' => false,
                    'error' => "Gateway error ({$response->status()}): {$httpError}",
                    'request_payload' => $payload,
                    'response' => $responseData,
                    'operator_ref' => null,
                    'gateway_id' => null,
                ];
            }

            $body = $responseData['body']['response'] ?? $responseData['body'] ?? $responseData;
            $responseCode = (string) ($body['responseCode'] ?? '');
            $gatewayId = $body['gatewayId'] ?? null;
            $reference = $body['reference'] ?? null;
            $transactionNumber = $body['transactionNumber'] ?? null;
            $responseStatus = $body['responseStatus'] ?? '';

            if ($responseCode === '0') {
                Log::info("DIGIVAS push accepted [{$operator->code}]", [
                    'gatewayId' => $gatewayId, 'transactionNumber' => $transactionNumber,
                ]);
                return [
                    'success' => true,
                    'operator_ref' => (string) ($transactionNumber ?: $reference),
                    'gateway_id' => $gatewayId,
                    'request_payload' => $payload,
                    'response' => $responseData,
                    'error' => null,
                ];
            }

            Log::warning("DIGIVAS push rejected [{$operator->code}]", [
                'responseCode' => $responseCode, 'responseStatus' => $responseStatus, 'response' => $responseData,
            ]);

            return [
                'success' => false,
                'error' => $responseStatus ?: ($responseCode ? 'Operator error code: ' . $responseCode : 'Unknown operator error'),
                'request_payload' => $payload,
                'response' => $responseData,
                'operator_ref' => null,
                'gateway_id' => null,
            ];
        } catch (\Exception $e) {
            Log::error("DIGIVAS push failed [{$operator->code}]: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to connect to operator: ' . $e->getMessage(),
                'request_payload' => $payload ?? null,
                'response' => null,
                'operator_ref' => null,
                'gateway_id' => null,
            ];
        }
    }

    public function parseCallback(Operator $operator, array $payload): array
    {
        $bodyRequest  = data_get($payload, 'body.request');
        $bodyResult   = data_get($payload, 'body.result');
        $bodyResponse = data_get($payload, 'body.response');

        $isCollectionCallback = $bodyRequest && strtolower($bodyRequest['command'] ?? '') === 'collection';

        if ($isCollectionCallback) {
            $receiptNumber = $bodyRequest['receiptNumber'] ?? null;
            return [
                'type'           => 'collection',
                'status'         => $receiptNumber ? 'completed' : 'failed',
                'receipt_number' => $receiptNumber,
                'operator_ref'   => $bodyRequest['gatewayId'] ?? null,
                'gateway_id'     => $bodyRequest['gatewayId'] ?? null,
                'reference'      => $bodyRequest['reference'] ?? null,
                'phone'          => $bodyRequest['msisdn'] ?? null,
                'amount'         => $bodyRequest['amount'] ?? null,
                'error_message'  => $receiptNumber ? null : 'Collection callback without receipt number',
            ];
        }

        // Disbursement callback (body.result) or fallback
        $body = $bodyResult ?? $bodyResponse ?? data_get($payload, 'body') ?? $payload;
        $resultCode = (string) ($body['resultCode'] ?? $body['responseCode'] ?? '');
        $callbackMessage = strtolower($body['message'] ?? '');

        $status = 'processing';
        if ($resultCode === '0' || $callbackMessage === 'success') {
            $status = 'completed';
        } elseif ($resultCode !== '' && $resultCode !== '0') {
            $status = 'failed';
        }

        return [
            'type'           => 'disbursement',
            'status'         => $status,
            'receipt_number' => $body['receiptNumber'] ?? null,
            'operator_ref'   => $body['transactionNumber'] ?? ($body['referenceNumber'] ?? null),
            'gateway_id'     => $body['gatewayId'] ?? null,
            'reference'      => $body['referenceNumber'] ?? ($body['reference'] ?? null),
            'phone'          => null,
            'amount'         => $body['amount'] ?? null,
            'error_message'  => $status === 'failed' ? ($body['resultStatus'] ?? $body['responseStatus'] ?? 'Operator error: ' . $resultCode) : null,
        ];
    }

    public function validateCallback(Operator $operator, array $payload): bool
    {
        $headerData = data_get($payload, 'header');
        if (!$headerData || empty($headerData['spPassword']) || empty($headerData['timestamp'])) {
            return true; // No auth header sent — allow (some operators skip it on callbacks)
        }

        $expectedPassword = $this->generateSpPassword($operator, $headerData['timestamp']);
        return $headerData['spPassword'] === $expectedPassword;
    }

    public function normalizePhone(string $phone, string $countryCode): string
    {
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, $countryCode) && strlen($phone) >= (strlen($countryCode) + 9)) {
            return $phone; // Already in international format
        }
        if (str_starts_with($phone, '0')) {
            return $countryCode . substr($phone, 1);
        }
        if (strlen($phone) === 9) {
            return $countryCode . $phone;
        }
        return $phone;
    }

    public function capabilities(): array
    {
        return ['collection' => true, 'disbursement' => true, 'status_check' => true];
    }

    private function generateSpPassword(Operator $operator, string $timestamp): string
    {
        $raw = $operator->sp_id . $operator->sp_password . $timestamp;
        return base64_encode(hash('sha256', $raw, true));
    }
}
