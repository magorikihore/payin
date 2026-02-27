# Payin — Operator API Integration Guide

> Complete reference for connecting mobile money operators (M-Pesa, Tigo Pesa, Airtel Money, Halotel, etc.) to the Payin payment platform.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication — Header Format](#authentication--header-format)
3. [spPassword Generation](#sppassword-generation)
4. [Collection API (Push USSD — Payin)](#collection-api-push-ussd--payin)
5. [Disbursement API (Payout)](#disbursement-api-payout)
6. [Operator Response Format](#operator-response-format)
7. [Callback — Operator to Payin](#callback--operator-to-payin)
8. [Response Codes](#response-codes)
9. [Callback URL for Operators](#callback-url-for-operators)
10. [Status Mapping](#status-mapping)
11. [Phone Number Format](#phone-number-format)
12. [Operator Configuration Fields](#operator-configuration-fields)
13. [Flow Diagrams](#flow-diagrams)
14. [Error Handling](#error-handling)
15. [Testing](#testing)

---

## Overview

Payin connects to operators using a **Push USSD** model:

- **Collection (Payin):** Merchant initiates a request → Payin pushes to operator → Operator sends USSD prompt to customer's phone → Customer confirms → Operator sends callback to Payin → Payin notifies merchant.
- **Disbursement (Payout):** Merchant initiates a request → Payin pushes to operator with phone number and amount → Operator sends money to customer → Operator sends callback to Payin → Payin notifies merchant.

All communication uses **JSON over HTTPS** with a `header/body` envelope format.

---

## Authentication — Header Format

Every request between Payin and the operator uses a standard header block for authentication.

```json
{
    "header": {
        "spId": "100100",
        "merchantCode": "1001001",
        "spPassword": "MAPid4MdunaomBiE3gcb/+jtFb9wmF5bHfyH3IcnUIY=",
        "timestamp": "20260226143755"
    }
}
```

| Field          | Type   | Description                                                                    |
|----------------|--------|--------------------------------------------------------------------------------|
| `spId`         | string | Service Provider ID assigned by the operator                                   |
| `merchantCode` | string | Merchant code assigned by the operator                                         |
| `spPassword`   | string | Encrypted password (see [spPassword Generation](#sppassword-generation) below) |
| `timestamp`    | string | Current date/time in format `YYYYMMDDHHmmss` (e.g. `20260226143755`)           |

---

## spPassword Generation

The `spPassword` is generated fresh for every request using this formula:

```
spPassword = Base64( SHA256( spId + spPassword_plain + timestamp ) )
```

### Step-by-step:

1. **Concatenate** the three values as a single string (no separators):
   - `spId` — your service provider ID
   - `spPassword_plain` — your original plain-text password (stored securely, never sent raw)
   - `timestamp` — the same timestamp value used in the header

2. **Hash** the concatenated string using **SHA-256** (binary output, not hex)

3. **Encode** the binary hash result to **Base64**

### PHP Example:

```php
$spId = '100100';
$spPasswordPlain = 'MySecretPassword';
$timestamp = '20260226143755';  // YYYYMMDDHHmmss

$raw = $spId . $spPasswordPlain . $timestamp;
$spPassword = base64_encode(hash('sha256', $raw, true));

// Result: "MAPid4MdunaomBiE3gcb/+jtFb9wmF5bHfyH3IcnUIY="
```

### Python Example:

```python
import hashlib, base64

sp_id = "100100"
sp_password_plain = "MySecretPassword"
timestamp = "20260226143755"

raw = sp_id + sp_password_plain + timestamp
sp_password = base64.b64encode(hashlib.sha256(raw.encode()).digest()).decode()
```

### JavaScript (Node.js) Example:

```javascript
const crypto = require('crypto');

const spId = '100100';
const spPasswordPlain = 'MySecretPassword';
const timestamp = '20260226143755';

const raw = spId + spPasswordPlain + timestamp;
const spPassword = crypto.createHash('sha256').update(raw).digest('base64');
```

> **Important:** The `timestamp` in the header and the one used to compute `spPassword` MUST be identical.

---

## Collection API (Push USSD — Payin)

Payin sends this request to the operator to initiate a USSD push to the customer's phone.

### Endpoint

```
POST {operator_api_url}/{collection_path}
```

Example: `POST https://api.operator.co.tz/ussd/collection`

### HTTP Headers

```
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
    "header": {
        "spId": "100100",
        "merchantCode": "1001001",
        "spPassword": "MAPid4MdunaomBiE3gcb/+jtFb9wmF5bHfyH3IcnUIY=",
        "timestamp": "20260226143755"
    },
    "body": {
        "phone": "255712345678",
        "amount": 10000.00,
        "reference": "PAY-ABCDEF123456",
        "currency": "TZS",
        "type": "collection",
        "callbackUrl": "https://api.payin.co.tz/api/callback/mpesa"
    }
}
```

### Request Body Fields

| Field                | Type   | Required | Description                                                    |
|----------------------|--------|----------|----------------------------------------------------------------|
| `header.spId`        | string | Yes      | Service Provider ID                                            |
| `header.merchantCode`| string | Yes      | Merchant code                                                  |
| `header.spPassword`  | string | Yes      | Computed password (Base64 SHA256)                               |
| `header.timestamp`   | string | Yes      | Timestamp `YYYYMMDDHHmmss`                                     |
| `body.phone`         | string | Yes      | Customer phone (international format, e.g. `255712345678`)     |
| `body.amount`        | number | Yes      | Amount to collect (minimum 100)                                |
| `body.reference`     | string | Yes      | Payin payment reference (e.g. `PAY-ABCDEF123456`)              |
| `body.currency`      | string | No       | Currency code (default: `TZS`)                                 |
| `body.type`          | string | Yes      | Transaction type: `collection`                                 |
| `body.callbackUrl`   | string | Yes      | URL where operator should POST the result                      |

### Expected Response

See [Operator Response Format](#operator-response-format).

---

## Disbursement API (Payout)

Payin sends this request to the operator to send money to a customer's phone number.

### Endpoint

```
POST {operator_api_url}/{disbursement_path}
```

Example: `POST https://api.operator.co.tz/ussd/disbursement`

### HTTP Headers

```
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
    "header": {
        "spId": "100100",
        "merchantCode": "1001001",
        "spPassword": "MAPid4MdunaomBiE3gcb/+jtFb9wmF5bHfyH3IcnUIY=",
        "timestamp": "20260226143755"
    },
    "body": {
        "phone": "255712345678",
        "amount": 5000.00,
        "reference": "PAY-XYZABC789012",
        "currency": "TZS",
        "type": "disbursement",
        "callbackUrl": "https://api.payin.co.tz/api/callback/mpesa"
    }
}
```

### Request Body Fields

| Field                | Type   | Required | Description                                                    |
|----------------------|--------|----------|----------------------------------------------------------------|
| `header.spId`        | string | Yes      | Service Provider ID                                            |
| `header.merchantCode`| string | Yes      | Merchant code                                                  |
| `header.spPassword`  | string | Yes      | Computed password (Base64 SHA256)                               |
| `header.timestamp`   | string | Yes      | Timestamp `YYYYMMDDHHmmss`                                     |
| `body.phone`         | string | Yes      | Recipient phone (international format, e.g. `255712345678`)    |
| `body.amount`        | number | Yes      | Amount to disburse (minimum 100)                               |
| `body.reference`     | string | Yes      | Payin payment reference (e.g. `PAY-XYZABC789012`)              |
| `body.currency`      | string | No       | Currency code (default: `TZS`)                                 |
| `body.type`          | string | Yes      | Transaction type: `disbursement`                               |
| `body.callbackUrl`   | string | Yes      | URL where operator should POST the result                      |

### Expected Response

See [Operator Response Format](#operator-response-format).

---

## Operator Response Format

When Payin pushes a request to the operator, the operator should respond immediately with an acknowledgment in this format:

```json
{
    "header": {
        "spId": "100100",
        "merchantCode": "1001001",
        "spPassword": "MAPid4MdunaomBiE3gcb/+jtFb9wmF5bHfyH3IcnUIY=",
        "timestamp": "20260226143755"
    },
    "body": {
        "response": {
            "gatewayId": 3000009866588,
            "reference": "PAY-ABCDEF123456",
            "responseCode": "0",
            "responseStatus": "Transaction Request Processed Successfully",
            "apiVersion": "5.0"
        }
    }
}
```

### Response Fields

| Field                           | Type          | Description                                                              |
|---------------------------------|---------------|--------------------------------------------------------------------------|
| `header`                        | object        | Same header format with spId, merchantCode, spPassword, timestamp        |
| `body.response.gatewayId`       | number/string | Unique gateway transaction ID assigned by operator                       |
| `body.response.reference`       | string        | The Payin reference that was sent in the request                         |
| `body.response.responseCode`    | string        | `"0"` = success (request accepted), any other value = error              |
| `body.response.responseStatus`  | string        | Human-readable status message                                           |
| `body.response.apiVersion`      | string        | API version (e.g. `"5.0"`)                                              |

> **Note:** `responseCode: "0"` means the operator has **accepted** the request for processing. The final transaction result comes via the [callback](#callback--operator-to-payin).

---

## Callback — Operator to Payin

After processing the transaction (customer confirms/rejects USSD, or disbursement completes), the operator POSTs the result to the `callbackUrl` that was provided in the original request.

### Callback Endpoint (Payin receives)

```
POST https://api.payin.co.tz/api/callback/{operator_code}
```

Where `{operator_code}` is the operator's code (e.g. `mpesa`, `tigopesa`, `airtelmoney`, `halopesa`).

### Callback Request Body

The operator should POST the result in the same `header/body` format:

```json
{
    "header": {
        "spId": "100100",
        "merchantCode": "1001001",
        "spPassword": "MAPid4MdunaomBiE3gcb/+jtFb9wmF5bHfyH3IcnUIY=",
        "timestamp": "20260226143755"
    },
    "body": {
        "response": {
            "gatewayId": 3000009866588,
            "reference": "PAY-ABCDEF123456",
            "responseCode": "0",
            "responseStatus": "Transaction Request Processed Successfully",
            "apiVersion": "5.0"
        }
    }
}
```

### Callback Fields

| Field                           | Type          | Description                                                               |
|---------------------------------|---------------|---------------------------------------------------------------------------|
| `header.spId`                   | string        | Service Provider ID (validated against our records)                       |
| `header.spPassword`             | string        | Computed password (validated for authenticity)                             |
| `header.timestamp`              | string        | Timestamp used for spPassword generation                                  |
| `body.response.gatewayId`       | number/string | Operator's gateway transaction ID (used to match the payment request)     |
| `body.response.reference`       | string        | The original Payin reference (PAY-xxx) or operator's own reference        |
| `body.response.responseCode`    | string        | `"0"` = transaction successful, other = failed                           |
| `body.response.responseStatus`  | string        | Human-readable result description                                        |
| `body.response.apiVersion`      | string        | API version                                                              |

### How Payin Matches the Callback

Payin matches the incoming callback to the original payment request using (in order):

1. **`gatewayId`** — matches against stored `gateway_id` + `operator_code`
2. **`reference`** — matches against stored `operator_ref` + `operator_code`
3. **`reference`** — matches against stored `request_ref` (PAY-xxx) + `operator_code`

### Callback Security

- If the callback includes a `header` with `spPassword` and `timestamp`, Payin **validates** the `spPassword` against the stored operator credentials. Invalid passwords return `403 Forbidden`.
- The callback URL is public (no bearer token required) since the operator authenticates via `spPassword`.

### Payin Callback Response

Payin responds to the operator callback with:

**Success:**
```json
{
    "message": "Callback processed successfully.",
    "request_ref": "PAY-ABCDEF123456",
    "status": "completed"
}
```

**Not Found:**
```json
{
    "message": "Payment request not found."
}
```
HTTP `404`

**Invalid Credentials:**
```json
{
    "message": "Invalid credentials."
}
```
HTTP `403`

---

## Response Codes

| Code  | Meaning                                 | Action                        |
|-------|-----------------------------------------|-------------------------------|
| `"0"` | Success — request accepted / completed  | Process as successful         |
| `"1"` | General error                           | Mark as failed                |
| `"2"` | Invalid credentials                     | Check spId/spPassword config  |
| `"3"` | Invalid phone / subscriber not found    | Mark as failed                |
| `"4"` | Insufficient balance (customer)         | Mark as failed                |
| `"5"` | Transaction timeout                     | Mark as timeout               |
| `"6"` | Duplicate transaction                   | Check existing reference      |
| `"7"` | Service unavailable                     | Retry later                   |
| Other | Operator-specific error                 | Mark as failed                |

> **Only `responseCode: "0"` is treated as success.** All other codes are treated as failure.

---

## Callback URL for Operators

When configuring an operator in the Payin admin panel, you provide a **callback URL** that the operator should use:

```
https://api.payin.co.tz/api/callback/{operator_code}
```

### Operator Codes

| Operator     | Code           | Callback URL                                            |
|--------------|----------------|---------------------------------------------------------|
| M-Pesa       | `mpesa`        | `https://api.payin.co.tz/api/callback/mpesa`            |
| Tigo Pesa    | `tigopesa`     | `https://api.payin.co.tz/api/callback/tigopesa`         |
| Airtel Money | `airtelmoney`  | `https://api.payin.co.tz/api/callback/airtelmoney`      |
| Halotel      | `halopesa`     | `https://api.payin.co.tz/api/callback/halopesa`         |

> The `operator_code` is set when adding the operator in the admin panel and must match exactly.

---

## Status Mapping

### Payin Internal Statuses

| Status       | Description                                                      |
|--------------|------------------------------------------------------------------|
| `pending`    | Payment request created, not yet pushed to operator              |
| `processing` | Pushed to operator, waiting for customer action or callback      |
| `completed`  | Transaction successful (money collected or disbursed)            |
| `failed`     | Transaction failed (operator rejected, customer declined, etc.)  |
| `cancelled`  | Transaction cancelled                                            |
| `timeout`    | Transaction timed out (no response from customer/operator)       |

### Operator Status → Payin Status Mapping

| Operator Status Value                                          | Maps To       |
|----------------------------------------------------------------|---------------|
| `success`, `successful`, `completed`, `approved`, `paid`       | `completed`   |
| `failed`, `failure`, `rejected`, `declined`, `error`,`cancelled`| `failed`      |
| `pending`, `processing`, `initiated`, `sent`                   | `processing`  |
| `responseCode: "0"`                                            | `completed`   |
| `responseCode: (any other value)`                              | `failed`      |

---

## Phone Number Format

All phone numbers are normalized to international format **without** the `+` prefix:

| Input            | Normalized       |
|------------------|------------------|
| `0712345678`     | `255712345678`   |
| `+255712345678`  | `255712345678`   |
| `255712345678`   | `255712345678`   |
| `0713 456 789`   | `255713456789`   |

- Country code for Tanzania: **255**
- Leading `0` is replaced with `255`
- Leading `+` is stripped
- Spaces, dashes, dots are removed

---

## Operator Configuration Fields

Each operator is configured in the Payin admin panel with these fields:

| Field               | Example                                    | Description                                              |
|---------------------|--------------------------------------------|---------------------------------------------------------|
| `name`              | `M-Pesa`                                   | Display name                                             |
| `code`              | `mpesa`                                    | Unique code (lowercase, used in callback URL)            |
| `api_url`           | `https://api.vodacom.co.tz`                | Operator's base API URL                                  |
| `sp_id`             | `100100`                                   | Service Provider ID (from operator)                      |
| `merchant_code`     | `1001001`                                  | Merchant code (from operator)                            |
| `sp_password`       | `MySecretPassword`                         | Plain-text password (stored encrypted, used for hashing) |
| `collection_path`   | `/ussd/collection`                         | Path appended to `api_url` for collection requests       |
| `disbursement_path` | `/ussd/disbursement`                       | Path appended to `api_url` for disbursement requests     |
| `callback_url`      | `https://api.payin.co.tz/api/callback/mpesa`| The URL shared with operator for callbacks               |
| `api_version`       | `5.0`                                      | Operator API version                                     |
| `status`            | `active`                                   | `active` or `inactive`                                   |

---

## Flow Diagrams

### Collection Flow (Payin — Customer Pays)

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Merchant │    │  Payin   │    │ Operator │    │ Customer │
│  Server  │    │   API    │    │  (MNO)   │    │  Phone   │
└────┬─────┘    └────┬─────┘    └────┬─────┘    └────┬─────┘
     │               │               │               │
     │ 1. POST /v1/collection        │               │
     │──────────────>│               │               │
     │               │               │               │
     │               │ 2. Push USSD (header/body)     │
     │               │──────────────>│               │
     │               │               │               │
     │               │ 3. Response (gatewayId,        │
     │               │    responseCode:"0")           │
     │               │<──────────────│               │
     │               │               │               │
     │ 4. Response   │               │               │
     │  (request_ref,│               │ 5. USSD Prompt│
     │   gateway_id) │               │──────────────>│
     │<──────────────│               │               │
     │               │               │ 6. Customer   │
     │               │               │    Confirms   │
     │               │               │<──────────────│
     │               │               │               │
     │               │ 7. Callback (responseCode:"0") │
     │               │<──────────────│               │
     │               │               │               │
     │ 8. Webhook    │               │               │
     │  (payin.      │               │               │
     │   completed)  │               │               │
     │<──────────────│               │               │
     │               │               │               │
```

### Disbursement Flow (Payout — Send Money)

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Merchant │    │  Payin   │    │ Operator │    │ Customer │
│  Server  │    │   API    │    │  (MNO)   │    │  Phone   │
└────┬─────┘    └────┬─────┘    └────┬─────┘    └────┬─────┘
     │               │               │               │
     │ 1. POST /v1/disbursement      │               │
     │──────────────>│               │               │
     │               │               │               │
     │               │ 2. Check wallet balance        │
     │               │ (must have enough funds)       │
     │               │               │               │
     │               │ 3. Push Disbursement           │
     │               │   (header/body, phone+amount)  │
     │               │──────────────>│               │
     │               │               │               │
     │               │ 4. Response (gatewayId,        │
     │               │    responseCode:"0")           │
     │               │<──────────────│               │
     │               │               │               │
     │ 5. Response   │               │               │
     │  (request_ref,│               │ 6. Money Sent │
     │   gateway_id) │               │──────────────>│
     │<──────────────│               │               │
     │               │               │               │
     │               │ 7. Callback (responseCode:"0") │
     │               │<──────────────│               │
     │               │               │               │
     │ 8. Webhook    │               │               │
     │  (payout.     │               │               │
     │   completed)  │               │               │
     │<──────────────│               │               │
     │               │               │               │
```

---

## Error Handling

### Timeout

- Payin waits **30 seconds** for the operator's initial response.
- If no response, the payment request is marked as `failed` with error: `"Failed to connect to operator"`.

### Duplicate Reference

- Each payment request has a unique `request_ref` (format `PAY-XXXXXXXXXXXX`).
- The merchant can also provide their own `reference` (stored as `external_ref`).

### Retry Logic

- Payin does **not** automatically retry failed operator pushes.
- Merchant callbacks are attempted once; if failed, `callback_status` is set to `failed` and `callback_attempts` is incremented.

### Idempotency

- Callbacks are matched by `gatewayId` → `operator_ref` → `request_ref` in order.
- If a callback is received for an already-completed transaction, the status remains `completed`.

---

## Testing

### Test Connection

Payin admin panel provides a **Test Connection** button for each operator. This sends a lightweight request to verify the operator's API URL is reachable and credentials are valid.

### Test Callback (cURL)

You can simulate an operator callback using cURL:

```bash
curl -X POST https://api.payin.co.tz/api/callback/mpesa \
  -H "Content-Type: application/json" \
  -d '{
    "header": {
        "spId": "100100",
        "merchantCode": "1001001",
        "spPassword": "MAPid4MdunaomBiE3gcb/+jtFb9wmF5bHfyH3IcnUIY=",
        "timestamp": "20260226143755"
    },
    "body": {
        "response": {
            "gatewayId": 3000009866588,
            "reference": "PAY-ABCDEF123456",
            "responseCode": "0",
            "responseStatus": "Transaction Request Processed Successfully",
            "apiVersion": "5.0"
        }
    }
}'
```

### Test Collection (cURL — Merchant API)

```bash
curl -X POST https://api.payin.co.tz/api/v1/collection \
  -H "Content-Type: application/json" \
  -H "X-API-Key: pk_live_xxxxxxxxxxxxxxxx" \
  -H "X-API-Secret: sk_live_xxxxxxxxxxxxxxxx" \
  -d '{
    "phone": "0712345678",
    "amount": 10000,
    "operator": "mpesa",
    "reference": "INV-001",
    "description": "Payment for Order #001"
}'
```

### Test Disbursement (cURL — Merchant API)

```bash
curl -X POST https://api.payin.co.tz/api/v1/disbursement \
  -H "Content-Type: application/json" \
  -H "X-API-Key: pk_live_xxxxxxxxxxxxxxxx" \
  -H "X-API-Secret: sk_live_xxxxxxxxxxxxxxxx" \
  -d '{
    "phone": "0712345678",
    "amount": 5000,
    "operator": "mpesa",
    "reference": "PAY-001",
    "description": "Salary payment"
}'
```

### Check Status (cURL — Merchant API)

```bash
curl -X GET https://api.payin.co.tz/api/v1/status/PAY-ABCDEF123456 \
  -H "X-API-Key: pk_live_xxxxxxxxxxxxxxxx" \
  -H "X-API-Secret: sk_live_xxxxxxxxxxxxxxxx"
```

---

## Summary

| Item                  | Value / Format                                              |
|-----------------------|-------------------------------------------------------------|
| **Auth Method**       | `spPassword = Base64(SHA256(spId + password + timestamp))`  |
| **Timestamp Format**  | `YYYYMMDDHHmmss`                                            |
| **Request Format**    | `{ "header": {...}, "body": {...} }`                        |
| **Response Format**   | `{ "header": {...}, "body": { "response": {...} } }`       |
| **Success Code**      | `responseCode: "0"`                                         |
| **Phone Format**      | International without `+` (e.g. `255712345678`)             |
| **Currency**          | `TZS` (default)                                             |
| **Callback URL**      | `https://api.payin.co.tz/api/callback/{operator_code}`      |
| **Merchant API Base** | `https://api.payin.co.tz/api/v1/`                           |
| **Merchant Auth**     | Headers: `X-API-Key` + `X-API-Secret`                       |
