<?php

namespace App\Gateways;

use App\Models\Operator;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Safaricom Daraja API Gateway Adapter.
 * Used for M-Pesa Kenya (Safaricom).
 *
 * Collection: STK Push (Lipa Na M-Pesa Online)
 * Disbursement: B2C Payment
 *
 * Credentials stored in operator:
 *   sp_id       = Consumer Key
 *   sp_password = Consumer Secret
 *   merchant_code = Business ShortCode (Paybill/Till)
 *   extra_config = {
 *     "passkey": "...",          // Lipa Na M-Pesa passkey
 *     "initiator_name": "...",   // B2C initiator name
 *     "security_credential": "..." // B2C encrypted credential
 *   }
 */
class SafaricomDarajaGateway implements GatewayInterface
{
    public function push(Operator $operator, PaymentRequest $paymentRequest, string $type): array
    {
        try {
            // Step 1: Get OAuth token
            $token = $this->getAccessToken($operator);
            if (!$token) {
                return $this->fail('Failed to obtain Daraja access token.');
            }

            if ($type === 'collection') {
                return $this->stkPush($operator, $paymentRequest, $token);
            } else {
                return $this->b2cPayment($operator, $paymentRequest, $token);
            }
        } catch (\Exception $e) {
            Log::error("Daraja push failed [{$operator->code}]: " . $e->getMessage());
            return $this->fail('Failed to connect to Safaricom: ' . $e->getMessage());
        }
    }

    private function stkPush(Operator $operator, PaymentRequest $paymentRequest, string $token): array
    {
        $url = rtrim($operator->api_url, '/') . '/' . ltrim($operator->collection_path ?: 'mpesa/stkpush/v1/processrequest', '/');
        $timestamp = now()->format('YmdHis');
        $shortCode = $operator->merchant_code;
        $passkey = $operator->extra_config['passkey'] ?? '';
        $password = base64_encode($shortCode . $passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $shortCode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) $paymentRequest->amount,
            'PartyA'            => $paymentRequest->phone,
            'PartyB'            => $shortCode,
            'PhoneNumber'       => $paymentRequest->phone,
            'CallBackURL'       => $operator->callback_url,
            'AccountReference'  => $paymentRequest->request_ref,
            'TransactionDesc'   => $paymentRequest->description ?: 'Payment',
        ];

        $response = Http::timeout(30)
            ->withToken($token)
            ->post($url, $payload);

        $data = $response->json() ?? [];

        if (($data['ResponseCode'] ?? '') === '0') {
            Log::info("Daraja STK Push accepted [{$operator->code}]", [
                'CheckoutRequestID' => $data['CheckoutRequestID'] ?? null,
                'MerchantRequestID' => $data['MerchantRequestID'] ?? null,
            ]);
            return [
                'success' => true,
                'operator_ref' => $data['CheckoutRequestID'] ?? null,
                'gateway_id' => $data['MerchantRequestID'] ?? null,
                'request_payload' => $payload,
                'response' => $data,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'error' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'STK Push failed',
            'request_payload' => $payload,
            'response' => $data,
            'operator_ref' => null,
            'gateway_id' => null,
        ];
    }

    private function b2cPayment(Operator $operator, PaymentRequest $paymentRequest, string $token): array
    {
        $url = rtrim($operator->api_url, '/') . '/' . ltrim($operator->disbursement_path ?: 'mpesa/b2c/v3/paymentrequest', '/');
        $extra = $operator->extra_config ?? [];

        $payload = [
            'OriginatorConversationID' => $paymentRequest->request_ref,
            'InitiatorName'            => $extra['initiator_name'] ?? 'apiuser',
            'SecurityCredential'       => $extra['security_credential'] ?? '',
            'CommandID'                => 'BusinessPayment',
            'Amount'                   => (int) $paymentRequest->amount,
            'PartyA'                   => $operator->merchant_code,
            'PartyB'                   => $paymentRequest->phone,
            'Remarks'                  => $paymentRequest->description ?: 'Payment',
            'QueueTimeOutURL'          => $operator->callback_url,
            'ResultURL'                => $operator->callback_url,
            'Occasion'                 => $paymentRequest->request_ref,
        ];

        $response = Http::timeout(30)
            ->withToken($token)
            ->post($url, $payload);

        $data = $response->json() ?? [];

        if (($data['ResponseCode'] ?? '') === '0') {
            return [
                'success' => true,
                'operator_ref' => $data['ConversationID'] ?? null,
                'gateway_id' => $data['OriginatorConversationID'] ?? null,
                'request_payload' => $payload,
                'response' => $data,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'error' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'B2C payment failed',
            'request_payload' => $payload,
            'response' => $data,
            'operator_ref' => null,
            'gateway_id' => null,
        ];
    }

    public function parseCallback(Operator $operator, array $payload): array
    {
        // STK Push callback format
        $stkCallback = data_get($payload, 'Body.stkCallback');
        if ($stkCallback) {
            $resultCode = (string) ($stkCallback['ResultCode'] ?? '');
            $items = collect($stkCallback['CallbackMetadata']['Item'] ?? []);
            $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
            $amount = $items->firstWhere('Name', 'Amount')['Value'] ?? null;
            $phone = $items->firstWhere('Name', 'PhoneNumber')['Value'] ?? null;

            return [
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

        // B2C Result callback format
        $result = data_get($payload, 'Result');
        if ($result) {
            $resultCode = (string) ($result['ResultCode'] ?? '');
            $params = collect($result['ResultParameters']['ResultParameter'] ?? []);
            $receipt = $params->firstWhere('Key', 'TransactionReceipt')['Value'] ?? null;
            $amount = $params->firstWhere('Key', 'TransactionAmount')['Value'] ?? null;
            $phone = $params->firstWhere('Key', 'ReceiverPartyPublicName')['Value'] ?? null;

            return [
                'type'           => 'disbursement',
                'status'         => $resultCode === '0' ? 'completed' : 'failed',
                'receipt_number' => $receipt,
                'operator_ref'   => $result['ConversationID'] ?? null,
                'gateway_id'     => $result['OriginatorConversationID'] ?? null,
                'reference'      => $result['OriginatorConversationID'] ?? null,
                'phone'          => $phone,
                'amount'         => $amount ? (string) $amount : null,
                'error_message'  => $resultCode !== '0' ? ($result['ResultDesc'] ?? 'Disbursement failed') : null,
            ];
        }

        return [
            'type' => 'unknown', 'status' => 'failed', 'receipt_number' => null,
            'operator_ref' => null, 'gateway_id' => null, 'reference' => null,
            'phone' => null, 'amount' => null, 'error_message' => 'Unknown Daraja callback format',
        ];
    }

    public function validateCallback(Operator $operator, array $payload): bool
    {
        // Daraja callbacks are authenticated by being sent to a registered URL
        // Additional validation can be done via IP whitelisting
        return true;
    }

    public function normalizePhone(string $phone, string $countryCode): string
    {
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        $phone = ltrim($phone, '+');

        // Kenya: ensure 254XXXXXXXXX format
        if (str_starts_with($phone, '0') && strlen($phone) >= 10) {
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
        $authUrl = rtrim($operator->api_url, '/') . '/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::timeout(15)
            ->withBasicAuth($operator->sp_id, $operator->sp_password)
            ->get($authUrl);

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
