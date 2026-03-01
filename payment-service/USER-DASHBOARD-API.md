# Payin — User Dashboard API Reference

> Complete API reference for the Payin merchant dashboard. All endpoints used by the user-facing dashboard across all microservices.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Base URLs](#base-urls)
4. [Auth Service API](#auth-service-api)
   - [Register](#1-register)
   - [Login](#2-login)
   - [Forgot Password](#3-forgot-password)
   - [Verify Reset Code](#4-verify-reset-code)
   - [Reset Password](#5-reset-password)
   - [Get Current User](#6-get-current-user)
   - [Logout](#7-logout)
   - [Change Password](#8-change-password)
   - [Get Callback URL](#9-get-callback-url)
   - [Update Callback URL](#10-update-callback-url)
   - [Get KYC Data](#11-get-kyc-data)
   - [Submit KYC](#12-submit-kyc)
   - [List API Keys](#13-list-api-keys)
   - [Generate API Key](#14-generate-api-key)
   - [Revoke API Key](#15-revoke-api-key)
   - [List Whitelisted IPs](#16-list-whitelisted-ips)
   - [Add IP to Whitelist](#17-add-ip-to-whitelist)
   - [Remove Whitelisted IP](#18-remove-whitelisted-ip)
   - [List Account Users](#19-list-account-users)
   - [Add Account User](#20-add-account-user)
   - [Change User Role](#21-change-user-role)
   - [Update User Permissions](#22-update-user-permissions)
   - [Remove Account User](#23-remove-account-user)
5. [Payment Service API](#payment-service-api)
   - [Merchant API (API Key Auth)](#merchant-api-api-key-auth)
   - [Dashboard API (Bearer Token)](#dashboard-api-bearer-token)
6. [Transaction Service API](#transaction-service-api)
7. [Wallet Service API](#wallet-service-api)
8. [Settlement Service API](#settlement-service-api)
9. [Error Handling](#error-handling)
10. [Business Rules](#business-rules)
11. [Rate Limiting](#rate-limiting)

---

## Overview

Payin is a mobile money payment platform built as a microservices architecture. The merchant dashboard communicates with 5 independent services:

| Service               | Purpose                                         |
|-----------------------|-------------------------------------------------|
| **Auth Service**      | User authentication, accounts, KYC, API keys    |
| **Payment Service**   | Collection & disbursement initiation             |
| **Transaction Service** | Transaction history, stats, charges, reversals |
| **Wallet Service**    | Wallet balances, wallet transactions, transfers  |
| **Settlement Service** | Settlement requests and tracking                |

---

## Authentication

### Bearer Token (Dashboard)

All dashboard endpoints require a Bearer token obtained from the Login endpoint.

```
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

The token is a Laravel Passport OAuth2 access token. Store it securely (e.g. `localStorage`) and include it in every request.

### API Key (Merchant API)

The Payment Service merchant API (`/api/v1/*`) uses API key authentication:

```
X-API-Key: <api_key>
X-API-Secret: <api_secret>
Accept: application/json
Content-Type: application/json
```

API keys are generated from the dashboard via the Auth Service.

---

## Base URLs

| Service            | Local Dev                      | Production (example)              |
|--------------------|--------------------------------|-----------------------------------|
| Auth Service       | `http://127.0.0.1:8001`        | `https://auth.yourdomain.com`     |
| Payment Service    | `http://127.0.0.1:8002`        | `https://api.yourdomain.com`      |
| Transaction Service| `http://127.0.0.1:8003`        | `https://tx.yourdomain.com`       |
| Wallet Service     | `http://127.0.0.1:8004`        | `https://wallet.yourdomain.com`   |
| Settlement Service | `http://127.0.0.1:8005`        | `https://settle.yourdomain.com`   |

---

## Auth Service API

**Base path:** `/api`

### Public Endpoints

> Rate limited: 5 requests per minute per IP.

---

### 1. Register

Create a new merchant account and owner user.

```
POST /api/register
```

**Request Body:**

| Field           | Type   | Required | Description                         |
|-----------------|--------|----------|-------------------------------------|
| `business_name` | string | Yes      | Business/company name               |
| `email`         | string | Yes      | Must be unique                      |
| `password`      | string | Yes      | Minimum 6 characters                |
| `password_confirmation` | string | Yes | Must match `password`         |
| `country`       | string | No       | Country code                        |

**Response:** `201 Created`

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "owner",
        "account_id": 1,
        "account": {
            "id": 1,
            "account_ref": "ACC-XXXXXXXX",
            "business_name": "My Business",
            "status": "pending"
        }
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGci..."
}
```

---

### 2. Login

Authenticate and receive an access token.

```
POST /api/login
```

**Request Body:**

| Field      | Type   | Required | Description |
|------------|--------|----------|-------------|
| `email`    | string | Yes      | Email       |
| `password` | string | Yes      | Password    |

**Response:** `200 OK`

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "owner",
        "account_id": 1,
        "effective_permissions": ["dashboard", "transactions", "wallets", "settlements", ...],
        "admin_permissions": []
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGci..."
}
```

> **Note:** Response may include `kyc_required: true` if KYC is not submitted, or `pending: true` if KYC is pending review.

---

### 3. Forgot Password

Request a password reset OTP sent to email.

```
POST /api/forgot-password
```

**Request Body:**

| Field   | Type   | Required | Description |
|---------|--------|----------|-------------|
| `email` | string | Yes      | Account email |

**Response:** `200 OK`

```json
{
    "message": "If an account exists with that email, a reset code has been sent."
}
```

> Anti-enumeration: always returns success regardless of whether the email exists.

---

### 4. Verify Reset Code

Verify the 6-digit OTP from the reset email.

```
POST /api/verify-reset-code
```

**Request Body:**

| Field  | Type   | Required | Description            |
|--------|--------|----------|------------------------|
| `email`| string | Yes      | Account email          |
| `code` | string | Yes      | 6-digit code from email |

**Response:** `200 OK`

```json
{
    "message": "Code verified.",
    "verified": true
}
```

> Code expires after 30 minutes.

---

### 5. Reset Password

Set a new password using the verified reset code.

```
POST /api/reset-password
```

**Request Body:**

| Field                  | Type   | Required | Description           |
|------------------------|--------|----------|-----------------------|
| `email`                | string | Yes      | Account email         |
| `code`                 | string | Yes      | Verified reset code   |
| `password`             | string | Yes      | New password (min 6)  |
| `password_confirmation`| string | Yes      | Must match `password` |

**Response:** `200 OK`

```json
{
    "message": "Password has been reset successfully."
}
```

---

### Authenticated Endpoints

> All endpoints below require: `Authorization: Bearer <token>`

---

### 6. Get Current User

Retrieve the authenticated user's profile with account details.

```
GET /api/user
```

**Response:** `200 OK`

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "owner",
    "account_id": 1,
    "account": {
        "id": 1,
        "account_ref": "ACC-XXXXXXXX",
        "business_name": "My Business",
        "paybill": "123456",
        "email": "business@example.com",
        "status": "active",
        "kyc_submitted_at": "2026-01-15T10:00:00.000000Z",
        "kyc_approved_at": "2026-01-16T14:30:00.000000Z"
    },
    "effective_permissions": ["dashboard", "transactions", "wallets", "settlements", "api_keys", "ip_whitelist", "users", "payment_requests", "charges", "reversals", "internal_transfers"],
    "admin_permissions": []
}
```

---

### 7. Logout

Revoke the current access token.

```
POST /api/logout
```

**Response:** `200 OK`

```json
{
    "message": "Logged out successfully."
}
```

---

### 8. Change Password

Change the password for the current user.

```
POST /api/change-password
```

**Request Body:**

| Field                  | Type   | Required | Description              |
|------------------------|--------|----------|--------------------------|
| `current_password`     | string | Yes      | Current password         |
| `password`             | string | Yes      | New password (min 8)     |
| `password_confirmation`| string | Yes      | Must match `password`    |

**Response:** `200 OK`

```json
{
    "message": "Password changed successfully."
}
```

---

### 9. Get Callback URL

Retrieve the webhook callback URL configured for the account.

```
GET /api/account/callback
```

**Response:** `200 OK`

```json
{
    "callback_url": "https://merchant.example.com/webhooks/payin"
}
```

---

### 10. Update Callback URL

Set or update the webhook callback URL.

```
PUT /api/account/callback
```

**Required Role:** `owner` or `admin`

**Request Body:**

| Field          | Type   | Required | Description                   |
|----------------|--------|----------|-------------------------------|
| `callback_url` | string | No       | Valid URL, or null to clear    |

**Response:** `200 OK`

```json
{
    "message": "Callback URL updated.",
    "callback_url": "https://merchant.example.com/webhooks/payin"
}
```

---

### 11. Get KYC Data

Retrieve the KYC submission data for the account.

```
GET /api/account/kyc
```

**Response:** `200 OK`

```json
{
    "kyc": {
        "business_name": "My Business Ltd",
        "business_type": "limited_company",
        "registration_number": "REG-12345",
        "tin_number": "TIN-67890",
        "phone": "+255712345678",
        "address": "123 Main Street",
        "city": "Dar es Salaam",
        "country": "TZ",
        "bank_name": "CRDB Bank",
        "bank_account_name": "My Business Ltd",
        "bank_account_number": "0123456789",
        "bank_swift": "CORUTZTZ",
        "bank_branch": "Main Branch",
        "crypto_wallet_address": null,
        "crypto_network": null,
        "crypto_currency": null,
        "id_type": "national_id",
        "id_number": "ID-123456",
        "id_document_url": "/storage/kyc/id_doc.pdf",
        "business_license_url": "/storage/kyc/license.pdf",
        "status": "active",
        "kyc_submitted_at": "2026-01-15T10:00:00.000000Z",
        "kyc_approved_at": "2026-01-16T14:30:00.000000Z",
        "kyc_notes": null
    }
}
```

---

### 12. Submit KYC

Submit or update KYC information. Sets account status to `pending`.

```
POST /api/account/kyc
```

**Required Role:** `owner` or `admin`

**Content-Type:** `multipart/form-data` (when uploading documents)

**Request Body:**

| Field                  | Type   | Required | Description                                           |
|------------------------|--------|----------|-------------------------------------------------------|
| `business_name`        | string | No       | Business name                                         |
| `business_type`        | string | No       | e.g. sole_proprietor, limited_company, partnership     |
| `registration_number`  | string | No       | Business registration number                          |
| `tin_number`           | string | No       | Tax Identification Number                             |
| `phone`                | string | No       | Contact phone number                                  |
| `address`              | string | No       | Physical address                                      |
| `city`                 | string | No       | City                                                  |
| `country`              | string | No       | Country code                                          |
| `bank_name`            | string | No       | Bank name for settlements                             |
| `bank_account_name`    | string | No       | Bank account holder name                              |
| `bank_account_number`  | string | No       | Bank account number                                   |
| `bank_swift`           | string | No       | SWIFT/BIC code                                        |
| `bank_branch`          | string | No       | Bank branch name                                      |
| `crypto_wallet_address`| string | No       | Cryptocurrency wallet address                         |
| `crypto_network`       | string | No       | Crypto network (e.g. ERC-20, TRC-20)                  |
| `crypto_currency`      | string | No       | Crypto currency (e.g. USDT, USDC)                     |
| `id_type`              | string | No       | `national_id`, `passport`, or `drivers_license`       |
| `id_number`            | string | No       | ID document number                                    |
| `id_document`          | file   | No       | ID document scan (max 5 MB, pdf/jpg/png)              |
| `business_license`     | file   | No       | Business license scan (max 5 MB, pdf/jpg/png)         |

**Response:** `200 OK`

```json
{
    "message": "KYC submitted successfully. Pending admin review.",
    "kyc": { ... }
}
```

---

### 13. List API Keys

List all API keys for the account.

```
GET /api/account/api-keys
```

**Response:** `200 OK`

```json
{
    "api_keys": [
        {
            "id": 1,
            "label": "Production Key",
            "api_key": "pk_live_xxxxxxxxxxxxxxxx",
            "status": "active",
            "last_used_at": "2026-02-27T08:30:00.000000Z",
            "expires_at": null,
            "created_at": "2026-01-20T10:00:00.000000Z"
        }
    ]
}
```

---

### 14. Generate API Key

Generate a new API key pair.

```
POST /api/account/api-keys
```

**Required Role:** `owner`

**Request Body:**

| Field   | Type   | Required | Description              |
|---------|--------|----------|--------------------------|
| `label` | string | No       | Label for the key (max 100) |

**Response:** `201 Created`

```json
{
    "message": "API key generated successfully.",
    "id": 2,
    "label": "Production Key",
    "api_key": "pk_live_xxxxxxxxxxxxxxxx",
    "api_secret": "sk_live_yyyyyyyyyyyyyyyy"
}
```

> **Important:** The `api_secret` is shown **only once** at creation. Store it securely. Maximum 5 active keys per account.

---

### 15. Revoke API Key

Revoke (delete) an API key.

```
DELETE /api/account/api-keys/{id}
```

**Required Role:** `owner`

**Response:** `200 OK`

```json
{
    "message": "API key revoked successfully."
}
```

---

### 16. List Whitelisted IPs

List all whitelisted IP addresses for the account.

```
GET /api/account/ips
```

**Response:** `200 OK`

```json
{
    "ips": [
        {
            "id": 1,
            "ip_address": "203.0.113.50",
            "label": "Production Server",
            "status": "approved",
            "created_at": "2026-01-20T10:00:00.000000Z"
        }
    ]
}
```

---

### 17. Add IP to Whitelist

Add an IP address to the whitelist. Requires admin approval before becoming active.

```
POST /api/account/ips
```

**Required Role:** `owner` or `admin`

**Request Body:**

| Field        | Type   | Required | Description              |
|--------------|--------|----------|--------------------------|
| `ip_address` | string | Yes      | Valid IPv4 or IPv6        |
| `label`      | string | No       | Description label         |

**Response:** `201 Created`

```json
{
    "message": "IP added. Pending admin approval.",
    "ip": {
        "id": 2,
        "ip_address": "198.51.100.10",
        "label": "Staging Server",
        "status": "pending",
        "created_at": "2026-02-27T12:00:00.000000Z"
    }
}
```

---

### 18. Remove Whitelisted IP

Remove an IP from the whitelist.

```
DELETE /api/account/ips/{id}
```

**Required Role:** `owner` or `admin`

**Response:** `200 OK`

```json
{
    "message": "IP removed from whitelist."
}
```

---

### 19. List Account Users

List all users belonging to the account.

```
GET /api/account/users
```

**Required Role:** `owner` or `admin`

**Response:** `200 OK`

```json
{
    "users": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "owner",
            "permissions": null,
            "created_at": "2026-01-10T08:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "Jane Admin",
            "email": "jane@example.com",
            "role": "admin",
            "permissions": ["dashboard", "transactions", "wallets"],
            "created_at": "2026-01-15T10:00:00.000000Z"
        }
    ],
    "available_permissions": [
        "dashboard", "transactions", "wallets", "settlements",
        "api_keys", "ip_whitelist", "users", "payment_requests",
        "charges", "reversals", "internal_transfers"
    ]
}
```

---

### 20. Add Account User

Add a new user to the account.

```
POST /api/account/users
```

**Required Role:** `owner` or `admin`

**Request Body:**

| Field         | Type   | Required | Description                          |
|---------------|--------|----------|--------------------------------------|
| `name`        | string | Yes      | Full name                            |
| `email`       | string | Yes      | Must be unique across the system     |
| `role`        | string | Yes      | `admin` or `viewer`                  |
| `password`    | string | Yes      | Minimum 8 characters                 |
| `permissions` | array  | No       | Array of permission strings          |

**Response:** `201 Created`

```json
{
    "message": "User added to account.",
    "user": {
        "id": 3,
        "name": "New User",
        "email": "newuser@example.com",
        "role": "viewer",
        "permissions": ["dashboard", "transactions"],
        "created_at": "2026-02-27T12:00:00.000000Z"
    }
}
```

---

### 21. Change User Role

Change a user's role within the account.

```
PUT /api/account/users/{id}/role
```

**Required Role:** `owner` only

**Request Body:**

| Field  | Type   | Required | Description              |
|--------|--------|----------|--------------------------|
| `role` | string | Yes      | `admin` or `viewer`      |

**Response:** `200 OK`

```json
{
    "message": "User role updated.",
    "user": { ... }
}
```

---

### 22. Update User Permissions

Set granular permissions for a user.

```
PUT /api/account/users/{id}/permissions
```

**Required Role:** `owner` only

**Request Body:**

| Field         | Type  | Required | Description                |
|---------------|-------|----------|----------------------------|
| `permissions` | array | Yes      | Array of permission strings |

**Available Permissions:**

| Permission         | Description                    |
|--------------------|--------------------------------|
| `dashboard`        | View dashboard overview        |
| `transactions`     | View transactions              |
| `wallets`          | View wallet balances           |
| `settlements`      | Create & view settlements      |
| `api_keys`         | Manage API keys                |
| `ip_whitelist`     | Manage IP whitelist            |
| `users`            | Manage account users           |
| `payment_requests` | View payment requests          |
| `charges`          | View charge breakdown          |
| `reversals`        | Request transaction reversals  |
| `internal_transfers` | Request internal transfers   |

**Response:** `200 OK`

```json
{
    "message": "User permissions updated.",
    "user": { ... }
}
```

---

### 23. Remove Account User

Remove a user from the account.

```
DELETE /api/account/users/{id}
```

**Required Role:** `owner` only

**Response:** `200 OK`

```json
{
    "message": "User removed from account."
}
```

> You cannot remove the account owner.

---

## Payment Service API

**Base path:** `/api`

### Merchant API (API Key Auth)

These endpoints are for server-to-server integration. Authenticate using `X-API-Key` and `X-API-Secret` headers.

**Prefix:** `/api/v1`

---

### 24. Initiate Collection (Payin)

Push a USSD payment prompt to a customer's phone.

```
POST /api/v1/collection
```

**Headers:**

```
X-API-Key: pk_live_xxxxxxxxxxxxxxxx
X-API-Secret: sk_live_yyyyyyyyyyyyyyyy
```

**Request Body:**

| Field         | Type   | Required | Description                          |
|---------------|--------|----------|--------------------------------------|
| `phone`       | string | Yes      | Customer phone (10-15 digits)        |
| `amount`      | number | Yes      | Amount in TZS (min: 100)             |
| `operator`    | string | Yes      | `M-Pesa`, `Tigo Pesa`, `Airtel Money`, or `Halopesa` |
| `reference`   | string | No       | Your external reference              |
| `description` | string | No       | Payment description                  |
| `currency`    | string | No       | Default: `TZS`                       |

**Response:** `201 Created`

```json
{
    "success": true,
    "message": "Collection request submitted.",
    "request_ref": "PAY-XXXXXXXXXXXX",
    "operator_ref": "OPR-YYYYYYYY",
    "gateway_id": "GW-ZZZZZZZZ",
    "status": "pending",
    "phone": "0712345678",
    "amount": 10000,
    "operator": "M-Pesa"
}
```

---

### 25. Initiate Disbursement (Payout)

Send money to a customer's mobile money account.

```
POST /api/v1/disbursement
```

**Headers:** Same as collection.

**Request Body:** Same structure as collection.

**Response:** `201 Created` — Same structure as collection.

> Checks disbursement wallet balance before processing. Returns `422` if insufficient funds.

---

### 26. Check Payment Status

Query the status of a payment request.

```
GET /api/v1/status/{request_ref}
```

**Response:** `200 OK`

```json
{
    "request_ref": "PAY-XXXXXXXXXXXX",
    "external_ref": "YOUR-REF-123",
    "operator_ref": "OPR-YYYYYYYY",
    "type": "collection",
    "phone": "0712345678",
    "amount": 10000,
    "charges": {
        "platform": 150,
        "operator": 0
    },
    "currency": "TZS",
    "operator": "M-Pesa",
    "status": "completed",
    "error": null,
    "created_at": "2026-02-27T10:00:00.000000Z",
    "updated_at": "2026-02-27T10:00:30.000000Z"
}
```

**Status values:** `pending`, `processing`, `completed`, `failed`, `timeout`

---

### 27. List Operators

Get all active payment operators.

```
GET /api/v1/operators
```

**Response:** `200 OK`

```json
{
    "operators": [
        { "id": 1, "name": "M-Pesa", "code": "MPESA", "status": "active" },
        { "id": 2, "name": "Tigo Pesa", "code": "TIGOPESA", "status": "active" },
        { "id": 3, "name": "Airtel Money", "code": "AIRTELMONEY", "status": "active" },
        { "id": 4, "name": "Halopesa", "code": "HALOPESA", "status": "active" }
    ]
}
```

---

### Dashboard API (Bearer Token)

These endpoints are used by the merchant dashboard. Authenticate with Bearer token.

---

### 28. List Payment Requests

List payment requests for the authenticated user's account.

```
GET /api/payment-requests
```

**Query Parameters:**

| Param    | Type   | Description                       |
|----------|--------|-----------------------------------|
| `search` | string | Search by reference, phone, etc.  |
| `status` | string | Filter: pending, processing, completed, failed, timeout |
| `type`   | string | Filter: collection, disbursement  |
| `page`   | int    | Page number (15 items per page)   |

**Response:** `200 OK` (Paginated)

```json
{
    "data": [
        {
            "id": 1,
            "request_ref": "PAY-XXXXXXXXXXXX",
            "external_ref": "YOUR-REF-123",
            "type": "collection",
            "phone": "0712345678",
            "amount": 10000,
            "platform_charge": 150,
            "operator_charge": 0,
            "currency": "TZS",
            "operator_code": "MPESA",
            "operator_name": "M-Pesa",
            "status": "completed",
            "created_at": "2026-02-27T10:00:00.000000Z"
        }
    ],
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72,
    "from": 1,
    "to": 15,
    "prev_page_url": null,
    "next_page_url": "...?page=2"
}
```

---

### 29. View Payment Request

Get details of a single payment request.

```
GET /api/payment-requests/{request_ref}
```

**Response:** `200 OK` — Same structure as the status endpoint.

---

### 30. Dashboard Collection

Initiate a collection from the dashboard (same as merchant API but uses Bearer token).

```
POST /api/collection
```

**Request/Response:** Same as [Initiate Collection](#24-initiate-collection-payin).

---

### 31. Dashboard Disbursement

Initiate a disbursement from the dashboard.

```
POST /api/disbursement
```

**Request/Response:** Same as [Initiate Disbursement](#25-initiate-disbursement-payout).

---

## Transaction Service API

**Base path:** `/api`  
**Auth:** Bearer Token

---

### 32. List Transactions

List transactions for the authenticated user's account.

```
GET /api/transactions
```

**Query Parameters:**

| Param      | Type   | Description                                      |
|------------|--------|--------------------------------------------------|
| `search`   | string | Search by reference, phone, amount               |
| `status`   | string | Filter: pending, completed, failed, cancelled, reversed |
| `type`     | string | Filter: collection, disbursement, topup, settlement |
| `operator` | string | Filter: M-Pesa, Tigo Pesa, Airtel Money, Halopesa |
| `date_from`| string | Start date (YYYY-MM-DD) for date range filter     |
| `date_to`  | string | End date (YYYY-MM-DD) for date range filter        |
| `page`     | int    | Page number (15 items per page)                  |

**Response:** `200 OK` (Paginated)

```json
{
    "data": [
        {
            "id": 1,
            "transaction_ref": "TXN-XXXXXXXXXXXX",
            "amount": 10000,
            "platform_charge": 150,
            "operator_charge": 0,
            "currency": "TZS",
            "type": "collection",
            "status": "completed",
            "operator": "M-Pesa",
            "operator_receipt": "OPR-YYYYYYYY",
            "phone_number": "0712345678",
            "payment_method": "mobile_money",
            "description": "Payment for order #123",
            "created_at": "2026-02-27T10:00:00.000000Z"
        }
    ],
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150,
    "from": 1,
    "to": 15,
    "prev_page_url": null,
    "next_page_url": "...?page=2"
}
```

---

### 33. Transaction Stats

Get summary counts by status for the user's account.

```
GET /api/transactions/stats
```

**Response:** `200 OK`

```json
{
    "total": 150,
    "completed": 120,
    "pending": 15,
    "failed": 15
}
```

---

### 33b. Export Transactions as Excel

Download filtered transactions as an Excel (.xlsx) file.

```
GET /api/transactions/export/excel
```

**Query Parameters:**

| Param      | Type   | Description                                      |
|------------|--------|--------------------------------------------------|
| `search`   | string | Search by reference, amount, operator             |
| `status`   | string | Filter: pending, completed, failed, cancelled, reversed |
| `type`     | string | Filter: collection, disbursement, topup, settlement |
| `operator` | string | Filter by operator name                           |
| `date_from`| string | Start date (YYYY-MM-DD)                           |
| `date_to`  | string | End date (YYYY-MM-DD)                             |

**Response:** `200 OK` — Binary file download (`application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`)

---

### 33c. Export Transactions as PDF

Download filtered transactions as a PDF file.

```
GET /api/transactions/export/pdf
```

**Query Parameters:**

| Param      | Type   | Description                                      |
|------------|--------|--------------------------------------------------|
| `search`   | string | Search by reference, amount, operator             |
| `status`   | string | Filter: pending, completed, failed, cancelled, reversed |
| `type`     | string | Filter: collection, disbursement, topup, settlement |
| `operator` | string | Filter by operator name                           |
| `date_from`| string | Start date (YYYY-MM-DD)                           |
| `date_to`  | string | End date (YYYY-MM-DD)                             |

**Response:** `200 OK` — Binary file download (`application/pdf`)

---

### 34. Transaction Detail

Get a single transaction by ID.

```
GET /api/transactions/{id}
```

**Response:** `200 OK`

```json
{
    "transaction": {
        "id": 1,
        "transaction_ref": "TXN-XXXXXXXXXXXX",
        "amount": 10000,
        "platform_charge": 150,
        "operator_charge": 0,
        "currency": "TZS",
        "type": "collection",
        "status": "completed",
        "operator": "M-Pesa",
        "operator_receipt": "OPR-YYYYYYYY",
        "phone_number": "0712345678",
        "payment_method": "mobile_money",
        "description": "Payment for order #123",
        "created_at": "2026-02-27T10:00:00.000000Z",
        "updated_at": "2026-02-27T10:00:30.000000Z"
    }
}
```

---

### 35. My Charges

Get a breakdown of charges collected from the user's account.

```
GET /api/my-charges
```

**Response:** `200 OK`

```json
{
    "total_platform_charges": 15000,
    "total_operator_charges": 5000,
    "total_charges": 20000,
    "by_type": [
        {
            "type": "collection",
            "transaction_count": 80,
            "platform_charges": 12000,
            "operator_charges": 3000
        },
        {
            "type": "disbursement",
            "transaction_count": 40,
            "platform_charges": 3000,
            "operator_charges": 2000
        }
    ],
    "by_operator": [
        {
            "operator": "M-Pesa",
            "transaction_count": 60,
            "platform_charges": 9000,
            "operator_charges": 2500
        }
    ]
}
```

---

### 36. Request Reversal

Request a reversal for a completed transaction.

```
POST /api/reversals
```

**Request Body:**

| Field            | Type   | Required | Description                              |
|------------------|--------|----------|------------------------------------------|
| `transaction_id` | int    | Yes      | ID of the completed transaction to reverse |
| `reason`         | string | Yes      | Reason for reversal (max 255 chars)       |

**Response:** `201 Created`

```json
{
    "message": "Reversal request submitted. Pending admin approval.",
    "reversal": {
        "id": 1,
        "reversal_ref": "REV-XXXXXXXXXXXX",
        "original_ref": "TXN-YYYYYYYYYYYY",
        "amount": 10000,
        "platform_charge": 150,
        "operator_charge": 0,
        "type": "collection",
        "operator": "M-Pesa",
        "reason": "Customer double charged",
        "status": "pending",
        "created_at": "2026-02-27T12:00:00.000000Z"
    }
}
```

> Only `completed` transactions can be reversed. Requires admin approval.

---

### 37. List Reversals

List reversal requests for the user's account.

```
GET /api/reversals
```

**Response:** `200 OK`

```json
{
    "reversals": [
        {
            "id": 1,
            "reversal_ref": "REV-XXXXXXXXXXXX",
            "original_ref": "TXN-YYYYYYYYYYYY",
            "amount": 10000,
            "platform_charge": 150,
            "operator_charge": 0,
            "type": "collection",
            "operator": "M-Pesa",
            "reason": "Customer double charged",
            "status": "approved",
            "admin_notes": "Verified duplicate",
            "created_at": "2026-02-27T12:00:00.000000Z"
        }
    ]
}
```

---

### 38. Calculate Charges

Preview charges for a given transaction.

```
POST /api/charges/calculate
```

**Request Body:**

| Field              | Type   | Required | Description                                        |
|--------------------|--------|----------|----------------------------------------------------|
| `amount`           | number | Yes      | Transaction amount (min: 1)                        |
| `operator`         | string | Yes      | Operator name                                      |
| `transaction_type` | string | Yes      | `collection`, `disbursement`, `topup`, `settlement` |
| `account_id`       | int    | No       | Account ID (uses authenticated user's if omitted)  |

**Response:** `200 OK`

```json
{
    "platform_charge": 150,
    "operator_charge": 0,
    "total_charge": 150
}
```

---

## Wallet Service API

**Base path:** `/api`  
**Auth:** Bearer Token

---

### 39. Get Wallets

Retrieve all wallets for the authenticated user's account with balances.

```
GET /api/wallet
```

**Response:** `200 OK`

```json
{
    "collection_wallets": [
        {
            "id": 1,
            "operator": "M-Pesa",
            "type": "collection",
            "balance": 500000,
            "currency": "TZS"
        }
    ],
    "disbursement_wallets": [
        {
            "id": 5,
            "operator": "M-Pesa",
            "type": "disbursement",
            "balance": 200000,
            "currency": "TZS"
        }
    ],
    "collection_total": 1500000,
    "disbursement_total": 800000,
    "overall_balance": 2300000,
    "currency": "TZS",
    "operators": ["M-Pesa", "Tigo Pesa", "Airtel Money", "Halopesa"],
    "recent_transactions": [
        {
            "id": 1,
            "type": "credit",
            "amount": 10000,
            "reference": "TXN-XXXXXXXXXXXX",
            "description": "Collection from 0712345678",
            "balance_before": 490000,
            "balance_after": 500000,
            "operator": "M-Pesa",
            "wallet_type": "collection",
            "created_at": "2026-02-27T10:00:30.000000Z"
        }
    ]
}
```

---

### 40. List Wallet Transactions

List wallet transaction entries (credits and debits) with filters.

```
GET /api/wallet/transactions
```

**Query Parameters:**

| Param         | Type   | Description                         |
|---------------|--------|-------------------------------------|
| `type`        | string | Filter: credit, debit               |
| `operator`    | string | Filter by operator name             |
| `wallet_type` | string | Filter: collection, disbursement    |
| `search`      | string | Search by reference or description  |
| `page`        | int    | Page number (15 items per page)     |

**Response:** `200 OK` (Paginated)

```json
{
    "data": [
        {
            "id": 1,
            "wallet_id": 1,
            "type": "credit",
            "amount": 10000,
            "reference": "TXN-XXXXXXXXXXXX",
            "description": "Collection from 0712345678",
            "balance_before": 490000,
            "balance_after": 500000,
            "status": "completed",
            "metadata": null,
            "operator": "M-Pesa",
            "wallet_type": "collection",
            "created_at": "2026-02-27T10:00:30.000000Z"
        }
    ],
    "current_page": 1,
    "last_page": 20,
    "per_page": 15,
    "total": 300
}
```

---

### 41. Request Internal Transfer

Request a transfer from collection wallet to disbursement wallet for a specific operator. Requires admin approval.

```
POST /api/wallet/transfer
```

**Request Body:**

| Field         | Type   | Required | Description                                       |
|---------------|--------|----------|---------------------------------------------------|
| `amount`      | number | Yes      | Transfer amount (min: 1)                          |
| `operator`    | string | Yes      | `M-Pesa`, `Tigo Pesa`, `Airtel Money`, `Halopesa` |
| `description` | string | No       | Optional description                              |

**Response:** `201 Created`

```json
{
    "message": "Transfer request submitted. Pending admin approval.",
    "transfer": {
        "id": 1,
        "account_id": 1,
        "operator": "M-Pesa",
        "amount": 100000,
        "reference": "TRF-XXXXXXXXXXXX",
        "description": "Fund disbursement wallet",
        "status": "pending",
        "requested_by": 1,
        "created_at": "2026-02-27T12:00:00.000000Z"
    }
}
```

---

### 42. List Internal Transfers

List internal transfer requests.

```
GET /api/wallet/transfers
```

**Query Parameters:**

| Param    | Type   | Description                            |
|----------|--------|----------------------------------------|
| `status` | string | Filter: pending, approved, rejected    |

**Response:** `200 OK`

```json
{
    "transfers": [
        {
            "id": 1,
            "account_id": 1,
            "operator": "M-Pesa",
            "amount": 100000,
            "reference": "TRF-XXXXXXXXXXXX",
            "description": "Fund disbursement wallet",
            "status": "approved",
            "requested_by": 1,
            "approved_by": 99,
            "admin_notes": null,
            "created_at": "2026-02-27T12:00:00.000000Z",
            "updated_at": "2026-02-27T14:00:00.000000Z"
        }
    ]
}
```

---

## Settlement Service API

**Base path:** `/api`  
**Auth:** Bearer Token

---

### 43. List Settlements

List settlement requests for the authenticated user's account.

```
GET /api/settlements
```

**Query Parameters:**

| Param    | Type   | Description                              |
|----------|--------|------------------------------------------|
| `status` | string | Filter: pending, approved, processing, completed, rejected |
| `search` | string | Search by reference, bank name, etc.     |
| `page`   | int    | Page number (15 items per page)          |

**Response:** `200 OK` (Paginated)

```json
{
    "data": [
        {
            "id": 1,
            "settlement_ref": "STL-XXXXXXXXXXXX",
            "amount": 500000,
            "currency": "TZS",
            "operator": "M-Pesa",
            "status": "completed",
            "bank_name": "CRDB Bank",
            "account_number": "0123456789",
            "account_name": "My Business Ltd",
            "description": "Weekly settlement",
            "metadata": null,
            "created_at": "2026-02-20T10:00:00.000000Z",
            "updated_at": "2026-02-21T14:30:00.000000Z"
        }
    ],
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 35
}
```

---

### 44. Create Settlement

Request a settlement (withdrawal from collection wallet to bank account).

```
POST /api/settlements
```

**Request Body:**

| Field            | Type   | Required | Description                                       |
|------------------|--------|----------|---------------------------------------------------|
| `amount`         | number | Yes      | Settlement amount (min: 1,000 TZS)                |
| `operator`       | string | Yes      | `M-Pesa`, `Tigo Pesa`, `Airtel Money`, `Halopesa` |
| `bank_name`      | string | Yes      | Destination bank name                             |
| `account_number` | string | Yes      | Bank account number                               |
| `account_name`   | string | Yes      | Bank account holder name                          |
| `description`    | string | No       | Optional description                              |

**Response:** `201 Created`

```json
{
    "message": "Settlement request created. Pending admin approval.",
    "settlement": {
        "id": 2,
        "settlement_ref": "STL-XXXXXXXXXXXX",
        "amount": 500000,
        "currency": "TZS",
        "operator": "M-Pesa",
        "status": "pending",
        "bank_name": "CRDB Bank",
        "account_number": "0123456789",
        "account_name": "My Business Ltd",
        "description": "Weekly settlement",
        "created_at": "2026-02-27T12:00:00.000000Z"
    },
    "charges": {
        "platform_charge": 500,
        "operator_charge": 0,
        "total_charge": 500,
        "total_debited": 500500
    }
}
```

> The collection wallet is debited immediately (amount + charges). If the settlement is rejected, funds are refunded.

---

### 45. View Settlement Detail

Get details of a single settlement.

```
GET /api/settlements/{id}
```

**Response:** `200 OK`

```json
{
    "settlement": {
        "id": 1,
        "settlement_ref": "STL-XXXXXXXXXXXX",
        "amount": 500000,
        "currency": "TZS",
        "operator": "M-Pesa",
        "status": "completed",
        "bank_name": "CRDB Bank",
        "account_number": "0123456789",
        "account_name": "My Business Ltd",
        "description": "Weekly settlement",
        "metadata": null,
        "created_at": "2026-02-20T10:00:00.000000Z",
        "updated_at": "2026-02-21T14:30:00.000000Z"
    }
}
```

---

## Error Handling

All services return consistent error responses:

### Validation Error — `422 Unprocessable Entity`

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 6 characters."]
    }
}
```

### Unauthorized — `401 Unauthorized`

```json
{
    "message": "Unauthenticated."
}
```

### Forbidden — `403 Forbidden`

```json
{
    "message": "Unauthorized. Owner role required."
}
```

### Not Found — `404 Not Found`

```json
{
    "message": "Resource not found."
}
```

### Rate Limited — `429 Too Many Requests`

```json
{
    "message": "Too many requests."
}
```

### Server Error — `500 Internal Server Error`

```json
{
    "message": "Something went wrong."
}
```

---

## Business Rules

### Account Lifecycle

1. **Register** → Account created with status `pending`
2. **Submit KYC** → Account status remains `pending`, KYC data saved
3. **Admin approves KYC** → Account status changes to `active`
4. **Active account** → Can generate API keys, process payments

### Key Constraints

| Rule                              | Detail                                           |
|-----------------------------------|--------------------------------------------------|
| API Keys                          | Max 5 active keys per account. Owner role only.   |
| IP Whitelist                      | Pending admin approval before activation          |
| Internal Transfers                | Collection → Disbursement only. Admin approval.   |
| Settlements                       | Min 1,000 TZS. Wallet debited immediately.        |
| Reversals                         | Only `completed` transactions. Admin approval.    |
| Collection min amount             | 100 TZS                                          |
| Disbursement min amount           | 100 TZS                                          |
| Password (register)               | Min 6 characters                                 |
| Password (change)                 | Min 8 characters                                 |
| KYC file uploads                  | Max 5 MB per file (PDF, JPG, PNG)                |

### User Roles & Permissions

| Role     | Description                                    |
|----------|------------------------------------------------|
| `owner`  | Full access. Can manage users, API keys, etc.  |
| `admin`  | Can be assigned granular permissions            |
| `viewer` | Read-only access based on assigned permissions  |

---

## Rate Limiting

| Endpoint Group             | Limit                              |
|----------------------------|------------------------------------|
| Public (login, register)   | 5 requests/minute per IP           |
| Merchant API (`/v1/*`)     | Per-account configurable limit     |
| Dashboard endpoints        | Standard Laravel throttle          |

---

## Webhook Callbacks

When a payment completes (or fails), Payin sends a POST request to your configured callback URL:

```
POST <your_callback_url>
```

**Payload:**

```json
{
    "event": "payment.completed",
    "request_ref": "PAY-XXXXXXXXXXXX",
    "transaction_ref": "TXN-YYYYYYYYYYYY",
    "type": "collection",
    "status": "completed",
    "amount": 10000,
    "charges": {
        "platform": 150,
        "operator": 0
    },
    "currency": "TZS",
    "operator": "M-Pesa",
    "phone": "0712345678",
    "timestamp": "2026-02-27T10:00:30.000000Z"
}
```

> Verify the callback by checking the payment status via `GET /api/v1/status/{request_ref}`.
