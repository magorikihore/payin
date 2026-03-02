<?php

namespace App\Gateways;

use App\Models\Operator;
use App\Models\PaymentRequest;

/**
 * Gateway adapter interface.
 * Each operator gateway type (DIGIVAS, Safaricom Daraja, Airtel Africa, MTN MoMo, etc.)
 * must implement this interface to handle API-specific request/response formats.
 */
interface GatewayInterface
{
    /**
     * Push a collection (C2B) or disbursement (B2C) request to the operator.
     *
     * @param  Operator        $operator       The operator config (api_url, credentials, paths, etc.)
     * @param  PaymentRequest  $paymentRequest The payment request record
     * @param  string          $type           'collection' or 'disbursement'
     * @return array{
     *     success: bool,
     *     operator_ref: ?string,
     *     gateway_id: ?string,
     *     request_payload: ?array,
     *     response: ?array,
     *     error: ?string
     * }
     */
    public function push(Operator $operator, PaymentRequest $paymentRequest, string $type): array;

    /**
     * Parse an incoming callback from the operator and return normalized data.
     *
     * @param  Operator  $operator  The operator config
     * @param  array     $payload   The raw callback payload
     * @return array{
     *     type: string,
     *     status: string,
     *     receipt_number: ?string,
     *     operator_ref: ?string,
     *     gateway_id: ?string,
     *     reference: ?string,
     *     phone: ?string,
     *     amount: ?string,
     *     error_message: ?string
     * }
     */
    public function parseCallback(Operator $operator, array $payload): array;

    /**
     * Validate callback authenticity (e.g., verify signature, password, API key).
     *
     * @param  Operator  $operator  The operator config
     * @param  array     $payload   The raw callback payload
     * @return bool
     */
    public function validateCallback(Operator $operator, array $payload): bool;

    /**
     * Normalize a phone number to the operator's expected format.
     *
     * @param  string  $phone       The raw phone number
     * @param  string  $countryCode The country code (e.g., '255', '254')
     * @return string
     */
    public function normalizePhone(string $phone, string $countryCode): string;

    /**
     * Return the list of supported gateway features.
     *
     * @return array{collection: bool, disbursement: bool, status_check: bool}
     */
    public function capabilities(): array;
}
