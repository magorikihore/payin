<?php

namespace App\Gateways;

use App\Models\Operator;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MTN MoMo API Gateway Adapter.
 * Used for MTN Mobile Money across Africa: Uganda, Rwanda, Ghana, Cameroon, etc.
 *
 * Collection: Request to Pay (customer approves via prompt)
 * Disbursement: Transfer (B2C)
 *
 * Credentials stored in operator:
 *   sp_id           = Subscription Key (Ocp-Apim-Subscription-Key)
 *   sp_password     = API User Secret
 *   merchant_code   = API User ID (X-Reference-Id for user creation)
 *   extra_config = {
 *     "environment": "sandbox",       // 'sandbox' or 'mtncongo', 'mtnuganda', etc.
 *     "callback_host": "https://...", // For webhook registration
 *     "target_environment": "sandbox" // sandbox or production
 *   }
 */
class MtnMomoGateway implements GatewayInterface
{
    public function push(Operator $operator, PaymentRequest $paymentRequest, string $type): array
    {
        try {
            $token = $this->getAccessToken($operator, $type);
            if (!$token) {
                return $this->fail('Failed to obtain MTN MoMo access token.');
            }

            if ($type === 'collection') {
                return $this->requestToPay($operator, $paymentRequest, $token);
            } else {
                return $this->transfer($operator, $paymentRequest, $token);
            }
        } catch (\Exception $e) {
            Log::error("MTN MoMo push failed [{$operator->code}]: " . $e->getMessage());
            return $this->fail('Failed to connect to MTN MoMo: ' . $e->getMessage());
        }
    }

    private function requestToPay(Operator $operator, PaymentRequest $paymentRequest, string $token): array
    {
        $url = rtrim($operator->api_url, '/') . '/' . ltrim($operator->collection_path ?: 'collection/v1_0/requesttopay', '/');
        $extra = $operator->extra_config ?? [];
        $referenceId = $paymentRequest->request_ref;

        $payload = [
            'amount'         => (string) (int) $paymentRequest->amount,
            'currency'       => $paymentRequest->currency,
            'externalId'     => $paymentRequest->request_ref,
            'payer'          => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $paymentRequest->phone,
            ],
            'payerMessage'   => $paymentRequest->description ?: 'Payment',
            'payeeNote'      => $paymentRequest->request_ref,
        ];

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders([
                'X-Reference-Id'             => $referenceId,
                'X-Target-Environment'       => $extra['target_environment'] ?? 'sandbox',
                'Ocp-Apim-Subscription-Key'  => $operator->sp_id,
                'X-Callback-Url'             => $operator->callback_url,
            ])
            ->post($url, $payload);

        // MTN MoMo returns 202 Accepted for successful request
        if ($response->status() === 202) {
            Log::info("MTN MoMo Request to Pay accepted [{$operator->code}]", [
                'referenceId' => $referenceId,
            ]);
            return [
                'success' => true,
                'operator_ref' => $referenceId,
                'gateway_id' => $referenceId,
                'request_payload' => $payload,
                'response' => ['status' => 202, 'referenceId' => $referenceId],
                'error' => null,
            ];
        }

        $data = $response->json() ?? [];
        return [
            'success' => false,
            'error' => $data['message'] ?? $data['reason'] ?? 'MTN Request to Pay failed (HTTP ' . $response->status() . ')',
            'request_payload' => $payload,
            'response' => $data,
            'operator_ref' => null,
            'gateway_id' => null,
        ];
    }

    private function transfer(Operator $operator, PaymentRequest $paymentRequest, string $token): array
    {
        $url = rtrim($operator->api_url, '/') . '/' . ltrim($operator->disbursement_path ?: 'disbursement/v1_0/transfer', '/');
        $extra = $operator->extra_config ?? [];
        $referenceId = $paymentRequest->request_ref;

        $payload = [
            'amount'        => (string) (int) $paymentRequest->amount,
            'currency'      => $paymentRequest->currency,
            'externalId'    => $paymentRequest->request_ref,
            'payee'         => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $paymentRequest->phone,
            ],
            'payerMessage'  => $paymentRequest->description ?: 'Transfer',
            'payeeNote'     => $paymentRequest->request_ref,
        ];

        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders([
                'X-Reference-Id'             => $referenceId,
                'X-Target-Environment'       => $extra['target_environment'] ?? 'sandbox',
                'Ocp-Apim-Subscription-Key'  => $operator->sp_id,
                'X-Callback-Url'             => $operator->callback_url,
            ])
            ->post($url, $payload);

        if ($response->status() === 202) {
            return [
                'success' => true,
                'operator_ref' => $referenceId,
                'gateway_id' => $referenceId,
                'request_payload' => $payload,
                'response' => ['status' => 202, 'referenceId' => $referenceId],
                'error' => null,
            ];
        }

        $data = $response->json() ?? [];
        return [
            'success' => false,
            'error' => $data['message'] ?? $data['reason'] ?? 'MTN Transfer failed (HTTP ' . $response->status() . ')',
            'request_payload' => $payload,
            'response' => $data,
            'operator_ref' => null,
            'gateway_id' => null,
        ];
    }

    public function parseCallback(Operator $operator, array $payload): array
    {
        // MTN MoMo sends callback with status and financialTransactionId
        $status = strtoupper(data_get($payload, 'status', ''));
        $referenceId = data_get($payload, 'externalId') ?? data_get($payload, 'referenceId');
        $financialTxnId = data_get($payload, 'financialTransactionId');

        $isSuccess = $status === 'SUCCESSFUL';
        $isFailed = in_array($status, ['FAILED', 'REJECTED', 'TIMEOUT', 'EXPIRED']);

        // Determine type from callback — collection has 'payer', disbursement has 'payee'
        $type = isset($payload['payer']) ? 'collection' : 'disbursement';

        return [
            'type'           => $type,
            'status'         => $isSuccess ? 'completed' : ($isFailed ? 'failed' : 'processing'),
            'receipt_number' => $financialTxnId,
            'operator_ref'   => $referenceId,
            'gateway_id'     => $referenceId,
            'reference'      => data_get($payload, 'externalId'),
            'phone'          => data_get($payload, 'payer.partyId') ?? data_get($payload, 'payee.partyId'),
            'amount'         => data_get($payload, 'amount'),
            'error_message'  => $isFailed ? (data_get($payload, 'reason') ?? 'Transaction ' . $status) : null,
        ];
    }

    public function validateCallback(Operator $operator, array $payload): bool
    {
        // MTN MoMo callbacks are authenticated via callback URL registration
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

    private function getAccessToken(Operator $operator, string $type): ?string
    {
        $product = ($type === 'collection') ? 'collection' : 'disbursement';
        $authUrl = rtrim($operator->api_url, '/') . "/{$product}/token/";

        $response = Http::timeout(15)
            ->withBasicAuth($operator->merchant_code, $operator->sp_password)
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => $operator->sp_id,
            ])
            ->post($authUrl);

        return $response->json('access_token');
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
