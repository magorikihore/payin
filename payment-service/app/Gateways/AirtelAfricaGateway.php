<?php

namespace App\Gateways;

use App\Models\Operator;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Airtel Africa Gateway Adapter.
 * Used for Airtel Money across Africa: Kenya, Uganda, Tanzania, Rwanda, DRC, etc.
 *
 * Collection: USSD Push (customer confirms via USSD prompt)
 * Disbursement: B2C transfer
 *
 * Credentials stored in operator:
 *   sp_id       = Client ID
 *   sp_password = Client Secret
 *   extra_config = {
 *     "pin": "...",               // Encrypted PIN for disbursement
 *     "environment": "staging"    // 'staging' or 'production'
 *   }
 */
class AirtelAfricaGateway implements GatewayInterface
{
    public function push(Operator $operator, PaymentRequest $paymentRequest, string $type): array
    {
        try {
            $token = $this->getAccessToken($operator);
            if (!$token) {
                return $this->fail('Failed to obtain Airtel Africa access token.');
            }

            if ($type === 'collection') {
                return $this->ussdPush($operator, $paymentRequest, $token);
            } else {
                return $this->b2cTransfer($operator, $paymentRequest, $token);
            }
        } catch (\Exception $e) {
            Log::error("Airtel Africa push failed [{$operator->code}]: " . $e->getMessage());
            return $this->fail('Failed to connect to Airtel: ' . $e->getMessage());
        }
    }

    private function ussdPush(Operator $operator, PaymentRequest $paymentRequest, string $token): array
    {
        $url = rtrim($operator->api_url, '/') . '/' . ltrim($operator->collection_path ?: 'merchant/v2/payments/', '/');

        $payload = [
            'reference'   => $paymentRequest->request_ref,
            'subscriber'  => [
                'country'  => $operator->country,
                'currency' => $paymentRequest->currency,
                'msisdn'   => $this->stripCountryCode($paymentRequest->phone, $operator->country_code),
            ],
            'transaction' => [
                'amount'   => (int) $paymentRequest->amount,
                'country'  => $operator->country,
                'currency' => $paymentRequest->currency,
                'id'       => $paymentRequest->request_ref,
            ],
        ];

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['X-Country' => $operator->country, 'X-Currency' => $paymentRequest->currency])
            ->post($url, $payload);

        $data = $response->json() ?? [];
        $statusCode = data_get($data, 'status.code');
        $statusMessage = data_get($data, 'status.message', '');

        if ($statusCode === '200' || strtolower($statusMessage) === 'ess_req_success' || $response->successful()) {
            return [
                'success' => true,
                'operator_ref' => data_get($data, 'data.transaction.id'),
                'gateway_id' => data_get($data, 'data.transaction.id'),
                'request_payload' => $payload,
                'response' => $data,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'error' => data_get($data, 'status.message', 'Airtel collection failed'),
            'request_payload' => $payload,
            'response' => $data,
            'operator_ref' => null,
            'gateway_id' => null,
        ];
    }

    private function b2cTransfer(Operator $operator, PaymentRequest $paymentRequest, string $token): array
    {
        $url = rtrim($operator->api_url, '/') . '/' . ltrim($operator->disbursement_path ?: 'standard/v2/payments/', '/');
        $extra = $operator->extra_config ?? [];

        $payload = [
            'payee' => [
                'msisdn'  => $this->stripCountryCode($paymentRequest->phone, $operator->country_code),
                'name'    => 'Customer',
            ],
            'reference' => $paymentRequest->request_ref,
            'pin'       => $extra['pin'] ?? '',
            'transaction' => [
                'amount' => (int) $paymentRequest->amount,
                'id'     => $paymentRequest->request_ref,
            ],
        ];

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['X-Country' => $operator->country, 'X-Currency' => $paymentRequest->currency])
            ->post($url, $payload);

        $data = $response->json() ?? [];
        $statusCode = data_get($data, 'status.code');

        if ($statusCode === '200' || $response->successful()) {
            return [
                'success' => true,
                'operator_ref' => data_get($data, 'data.transaction.id'),
                'gateway_id' => data_get($data, 'data.transaction.reference_id'),
                'request_payload' => $payload,
                'response' => $data,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'error' => data_get($data, 'status.message', 'Airtel disbursement failed'),
            'request_payload' => $payload,
            'response' => $data,
            'operator_ref' => null,
            'gateway_id' => null,
        ];
    }

    public function parseCallback(Operator $operator, array $payload): array
    {
        $transaction = data_get($payload, 'transaction', $payload);
        $statusCode = data_get($transaction, 'status_code', data_get($payload, 'status_code', ''));

        $isSuccess = in_array(strtoupper($statusCode), ['TS', 'TIP', '200']);
        $isFailed = in_array(strtoupper($statusCode), ['TF', 'TE']);
        $type = data_get($transaction, 'message', '') === 'Paid In' ? 'collection' : 'disbursement';

        return [
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

    public function validateCallback(Operator $operator, array $payload): bool
    {
        // Airtel Africa callbacks are authenticated via callback URL registration
        return true;
    }

    public function normalizePhone(string $phone, string $countryCode): string
    {
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, $countryCode)) {
            return $phone;
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

    private function getAccessToken(Operator $operator): ?string
    {
        $authUrl = rtrim($operator->api_url, '/') . '/auth/oauth2/token';

        $response = Http::timeout(15)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($authUrl, [
                'client_id' => $operator->sp_id,
                'client_secret' => $operator->sp_password,
                'grant_type' => 'client_credentials',
            ]);

        return $response->json('access_token');
    }

    private function stripCountryCode(string $phone, string $countryCode): string
    {
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, $countryCode)) {
            return substr($phone, strlen($countryCode));
        }
        if (str_starts_with($phone, '0')) {
            return substr($phone, 1);
        }
        return $phone;
    }

    private function fail(string $error): array
    {
        return [
            'success' => false, 'error' => $error,
            'request_payload' => null, 'response' => null,
            'operator_ref' => null, 'gateway_id' => null,
        ];
    }
}
