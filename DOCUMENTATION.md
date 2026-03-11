# Payin — Comprehensive Application Documentation

> **Version:** 1.0  
> **Domain:** payin.co.tz  
> **Architecture:** Laravel PHP Microservices  
> **Last Updated:** July 2025

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Architecture](#2-architecture)
3. [Service Directory](#3-service-directory)
4. [Auth Service](#4-auth-service)
5. [Payment Service](#5-payment-service)
6. [Transaction Service](#6-transaction-service)
7. [Wallet Service](#7-wallet-service)
8. [Settlement Service](#8-settlement-service)
9. [Test Operator (MNO Simulator)](#9-test-operator-mno-simulator)
10. [Security Features](#10-security-features)
11. [Payment Flow](#11-payment-flow)
12. [Gateway Adapters](#12-gateway-adapters)
13. [Database Schema](#13-database-schema)
14. [Deployment](#14-deployment)
15. [API Reference Summary](#15-api-reference-summary)

---

## 1. System Overview

**Payin** is a payment aggregation platform that enables businesses to collect and disburse mobile money payments across multiple African mobile network operators (MNOs). The platform supports M-Pesa (Tanzania & Kenya), Tigo Pesa, Airtel Money, Halopesa, and MTN Mobile Money.

### Key Capabilities

| Feature | Description |
|---------|-------------|
| **Collections (C2B)** | USSD push payments — customer receives a prompt on their phone to confirm payment |
| **Disbursements (B2C)** | Send money to mobile wallets — payouts, refunds, salary payments |
| **Multi-Operator** | Single API for M-Pesa, Tigo, Airtel, Halopesa, Safaricom, MTN |
| **Multi-Country** | Tanzania (TZS), Kenya (KES), Uganda, Rwanda, Ghana, and more |
| **Multi-Currency** | Exchange between currency wallets with configurable rates |
| **Dual Wallets** | Separate collection and disbursement wallets per operator |
| **Settlements** | Bank settlement requests with admin approval workflow |
| **Maker-Checker** | Payout approval system requiring admin authorization |
| **Batch Payments** | Send up to 500 disbursements in a single API call |
| **Webhooks** | Real-time callback notifications to merchant systems |
| **Reversals** | Transaction reversal workflow with admin approval |
| **Referral System** | Agent referral with configurable commission structures |
| **KYC Workflow** | Document-based business verification process |
| **API Key Auth** | Public/secret key pairs with IP whitelisting |

---

## 2. Architecture

### Microservices Topology

```
                         ┌─────────────────────────────┐
                         │        Nginx Reverse Proxy    │
                         │     SSL / Security Headers    │
                         └──────────┬──────────────────┘
                                    │
        ┌───────────────┬───────────┼───────────┬───────────────┐
        │               │           │           │               │
        ▼               ▼           ▼           ▼               ▼
  ┌───────────┐  ┌───────────┐  ┌────────┐  ┌────────┐  ┌───────────┐
  │   Auth    │  │  Payment  │  │  Txn   │  │ Wallet │  │Settlement │
  │  :8001    │  │  :8002    │  │ :8003  │  │ :8004  │  │  :8005    │
  └─────┬─────┘  └─────┬─────┘  └───┬────┘  └───┬────┘  └─────┬─────┘
        │               │           │           │               │
        │               ▼           │           │               │
        │        ┌───────────┐      │           │               │
        │        │   MNO     │      │           │               │
        │        │ Gateways  │      │           │               │
        │        └───────────┘      │           │               │
        │                           │           │               │
        └───── Token Validation ────┴───────────┴───────────────┘
```

### Subdomains

| Subdomain | Service | Port |
|-----------|---------|------|
| `auth.payin.co.tz` | Auth Service | 8001 |
| `login.payin.co.tz` | Payment Service | 8002 |
| `api.payin.co.tz` | Payment Service (Merchant API) | 8002 |
| `tx.payin.co.tz` | Transaction Service | 8003 |
| `wallet.payin.co.tz` | Wallet Service | 8004 |
| `settle.payin.co.tz` | Settlement Service | 8005 |
| `payin.co.tz` / `www.payin.co.tz` | Static Landing Page | Nginx direct |

### Inter-Service Communication

| Pattern | Mechanism | Use Case |
|---------|-----------|----------|
| **User → Service** | Bearer token (Passport OAuth2) | Dashboard API access |
| **Merchant → Service** | `X-API-Key` + `X-API-Secret` headers | Collection/Disbursement API |
| **Service → Service** | `X-Service-Key` header | Internal operations (charge calc, wallet credit/debit) |
| **Service → Auth** | Bearer token forwarding | Token validation (`GET /api/user`) |
| **Service → MNO** | Gateway-specific protocols | USSD push, STK push, B2C transfers |
| **MNO → Service** | POST callback | Payment result notifications |

### Service Dependency Map

```
auth-service ◄── (token validation) ── ALL services
auth-service ◄── (bank accounts, notifications, callback URLs) ── settlement & wallet services

transaction-service ◄── (charge calculation, txn recording) ── wallet-service
transaction-service ◄── (charge calculation, txn recording) ── settlement-service
transaction-service ◄── (referral commission) ── payment-service

wallet-service ◄── (credit/debit/summary) ── payment-service
wallet-service ◄── (debit-settlement, refund) ── settlement-service

payment-service → (operators list) → wallet-service (cached 5 min)
payment-service → (USSD push) → MNO gateways
MNO gateways → (callback) → payment-service
```

---

## 3. Service Directory

| Service | Purpose | Tech Stack |
|---------|---------|------------|
| **auth-service** | Authentication, authorization, KYC, API keys, user management | Laravel 11 + Passport |
| **payment-service** | Payment initiation, operator gateway integration, callback handling | Laravel 11 |
| **transaction-service** | Transaction recording, charge configuration, reversals, exports | Laravel 11 |
| **wallet-service** | Wallet management, balance tracking, internal transfers, currency exchange | Laravel 11 |
| **settlement-service** | Bank settlement requests and approval workflow | Laravel 11 |
| **test-operator** | MNO simulator for development/testing (DIGIVAS EPG protocol) | Laravel 11 |

---

## 4. Auth Service

### Overview

Central authentication and authorization hub. All other services validate user identity through this service. Manages user accounts, API keys, KYC verification, 2FA, IP whitelisting, and notifications.

### Models

| Model | Purpose | Key Fields |
|-------|---------|------------|
| `User` | Platform users | `email`, `password`, `role`, `permissions`, `two_factor_enabled`, `failed_login_attempts`, `locked_until`, `is_banned` |
| `Account` | Business accounts | `business_name`, `paybill`, `callback_url`, `status`, `rate_limit`, `referral_code`, `referred_by`, `commission_type`, KYC fields |
| `ApiKey` | API credentials | `api_key` (plaintext), `api_secret` (hashed), `status`, `expires_at` |
| `BankAccount` | Settlement bank details | `bank_name`, `account_number`, `swift_code`, `is_default` |
| `CryptoWallet` | Cryptocurrency wallets | `currency`, `network`, `wallet_address`, `is_default` |
| `IpWhitelist` | IP access control | `ip_address`, `status` (pending/approved/rejected/suspended) |
| `EmailTemplate` | Customizable email templates | `key`, `subject`, `body`, `is_active` |
| `AdminSetting` | Platform configuration | `key`, `value` (includes SMTP settings, notification emails) |
| `ActivityLog` | Audit trail | `action`, `description`, `ip_address`, `metadata` |

### User Roles

| Role | Scope | Description |
|------|-------|-------------|
| `owner` | Business | Account creator — all business permissions implicitly granted |
| `admin` | Business | Account-level admin for KYC, bank accounts, user management |
| `viewer` | Business | Read-only access, permissions controlled by owner |
| `super_admin` | Platform | Full platform administrator — all permissions |
| `admin_user` | Platform | Admin with specific module permissions assigned by super_admin |

### Business Permissions (10)

`view_transactions`, `create_settlement`, `view_settlements`, `wallet_transfer`, `create_payout`, `approve_payout`, `add_user`, `view_users`, `view_account_info`, `view_settings`

### Admin Permissions (12)

| Permission | Module |
|-----------|--------|
| `admin_overview` | Dashboard & Stats |
| `admin_accounts` | Accounts & KYC |
| `admin_transactions` | Transaction Viewing |
| `admin_wallets` | Wallet Management |
| `admin_settlements` | Settlement Approval |
| `admin_charges` | Charge Configuration |
| `admin_ip_whitelist` | IP Whitelist Management |
| `admin_transfers` | Transfer Approval |
| `admin_users` | User Management |
| `admin_reversals` | Transaction Reversals |
| `admin_operators` | Operator Configuration |
| `admin_payments` | Payment Requests |

### KYC Workflow

```
Registration → Account status: "pending"
     │
     ▼
Login → Returns kyc_required: true → Frontend shows KYC form
     │
     ▼
Submit KYC (POST /account/kyc):
  - Upload: ID document, business license, certificate of incorporation, TIN certificate
  - Optional: tax clearance, company memorandum, company resolution
  - Admins notified via email
     │
     ▼
Admin Review (PUT /admin/accounts/{id}/status):
  ├── Approve → Status: "active", KYC approved, referral code generated
  │              Sends KycApprovedNotification
  │
  └── Reject → Status: "suspended"
               Sends KycRejectedNotification with reason
     │
     ▼
Post-Approval KYC Updates:
  - Owner can update only if admin toggles kyc_update_allowed = true
  - After update, flag auto-reverts to false
```

**KYC Document Types:** `jpg`, `jpeg`, `png`, `pdf` — max 5MB each  
**ID Types:** `national_id`, `passport`, `drivers_license`  
**Completeness:** Calculated as percentage of 9 core fields filled

### API Key Management

| Step | Detail |
|------|--------|
| **Generation** | Owner or super_admin only; account must be `active` |
| **Limit** | Max 5 active keys per account |
| **Key Format** | `pk_` + 32 random characters |
| **Secret Format** | `sk_` + 48 random characters |
| **Storage** | Key stored in plaintext (for lookup); secret **hashed** with `Hash::make()` |
| **Secret Disclosure** | Shown only once at creation — never retrievable again |
| **Validation** | Lookup by key → check active + not expired → IP whitelist check → `Hash::check()` secret |
| **Revocation** | Soft revoke by setting `status=revoked` |

### Referral System

- **Code Format:** `REF-` + 8 uppercase random characters (e.g., `REF-A3B9K2X1`)
- **Auto-generated** when admin approves KYC and activates account
- **Registration:** Optional `referral_code` field sets `referred_by` and `referred_at`
- **Commission Config:** Per-account `commission_type` (fixed/percentage) and `commission_value`
- **Self-referral prevention** enforced

### Notification System (19 Email Notifications)

| Notification | Trigger |
|-------------|---------|
| `WelcomeNotification` | New user registration |
| `TwoFactorCodeNotification` | 2FA code generated for login |
| `ResetPasswordNotification` | Password reset OTP requested |
| `AccountOpeningNotification` | Admin creates business account |
| `AccountLockedNotification` | 5 failed login attempts — alerts user + admins |
| `NewIpLoginNotification` | Login from a new IP address |
| `FailedTwoFactorNotification` | 3+ failed 2FA attempts — security alert |
| `KycApprovedNotification` | Admin approves KYC |
| `KycRejectedNotification` | Admin rejects/suspends account |
| `IpWhitelistApprovedNotification` | Admin approves IP whitelist request |
| `TransferApprovedNotification` | Wallet transfer approved |
| `SettlementApprovedNotification` | Settlement approved |
| `CustomTemplateNotification` | Admin sends custom template email |
| `BulkEmailNotification` | Admin sends bulk email |
| `AdminNewRegistrationNotification` | New user → alerts all admins |
| `AdminKycSubmittedNotification` | KYC submitted → alerts all admins |
| `AdminIpWhitelistRequestedNotification` | IP whitelist request → alerts admins |
| `AdminSettlementRequestedNotification` | Settlement request → alerts admins |
| `AdminTransferRequestedNotification` | Transfer request → alerts admins |

### API Endpoints

#### Public Routes (Rate limited: 5 req/min)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/register` | User registration |
| `POST` | `/api/login` | User login |
| `POST` | `/api/verify-two-factor` | 2FA verification |
| `POST` | `/api/resend-two-factor` | Resend 2FA code |
| `POST` | `/api/forgot-password` | Request password reset |
| `POST` | `/api/verify-reset-code` | Verify reset OTP |
| `POST` | `/api/reset-password` | Reset password |
| `GET` | `/api/referral-code/{code}` | Referral code lookup |

#### Internal Service-to-Service Routes

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/internal/validate-api-key` | Validate API key + secret + IP |
| `POST` | `/api/internal/send-notification` | Send email notifications |
| `GET` | `/api/internal/bank-accounts/{accountId}` | Get bank account details |
| `POST` | `/api/internal/bank-accounts/create` | Create bank account |
| `GET` | `/api/internal/referral-code/{code}` | Internal referral lookup |

#### Authenticated User Routes

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/user` | Current user + permissions |
| `POST` | `/api/logout` | Revoke token |
| `POST` | `/api/change-password` | Change password |
| `GET/POST` | `/api/two-factor/status`, `/toggle` | 2FA management |
| `GET/PUT` | `/api/account/callback` | Callback URL management |
| `GET/POST` | `/api/account/kyc` | KYC submission |
| `GET/POST/DELETE` | `/api/account/api-keys` | API key management |
| `GET/POST/PUT/DELETE` | `/api/account/bank-accounts` | Bank account management |
| `GET/POST/PUT/DELETE` | `/api/account/crypto-wallets` | Crypto wallet management |
| `GET/POST/DELETE` | `/api/account/ips` | IP whitelist management |
| `GET/POST/PUT/DELETE` | `/api/account/users` | Account user management |

#### Admin Routes (50+ endpoints)

Account management, KYC review, user management, admin user management, IP whitelist approval, log viewing, SMTP configuration, email templates, bulk email, referral settings, activity logs — all scoped by admin permissions.

---

## 5. Payment Service

### Overview

Core payment processing engine. Handles collection (C2B) and disbursement (B2C) requests, interfaces with MNO gateway adapters, processes callbacks, and orchestrates wallet credits/debits across services.

### Models

| Model | Purpose | Key Fields |
|-------|---------|------------|
| `PaymentRequest` | Payment transaction record | `request_ref`, `type`, `phone`, `amount`, `status`, `operator_code`, `gateway_id`, `receipt_number`, `callback_data`, `callback_status` |
| `Operator` | MNO configuration | `code`, `prefixes`, `api_url`, `sp_id`, `sp_password`, `gateway_type`, `callback_url`, `country`, `currency` |

### Middleware

| Alias | Class | Purpose |
|-------|-------|---------|
| `auth.apikey` | `ApiKeyAuthenticate` | Validates `X-API-Key` + `X-API-Secret` against auth-service; IP whitelisting |
| `auth.service` | `AuthServiceAuthenticate` | Validates Bearer token against auth-service |
| `throttle.account` | `AccountRateLimit` | Per-account rate limiting (default 60 req/min, configurable) |

### API Endpoints

#### Merchant API (auth.apikey + throttle.account)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/v1/collection` | Initiate USSD push collection |
| `POST` | `/api/v1/disbursement` | Initiate disbursement |
| `GET` | `/api/v1/status/{request_ref}` | Check payment status |
| `GET` | `/api/v1/operators` | List active operators |

#### Callback (Public, no auth)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/callback` | Receive operator callbacks (auto-detect format) |
| `POST` | `/api/callback/{operator_code}` | Backward-compatible operator callback |

#### Dashboard API (auth.service)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/payment-requests` | List user's payment requests |
| `GET` | `/api/payment-requests/{ref}` | Payment request detail |
| `POST` | `/api/collection` | Dashboard collection initiation |
| `POST` | `/api/disbursement` | Dashboard disbursement |
| `POST` | `/api/disbursement/batch` | Batch disbursement (up to 500) |
| `GET` | `/api/payouts/pending` | Pending payout approvals |
| `PUT` | `/api/payouts/{id}/approve` | Approve payout (maker-checker) |
| `PUT` | `/api/payouts/{id}/reject` | Reject payout |
| `POST` | `/api/payouts/bulk-approve` | Bulk approve payouts |
| `POST` | `/api/payouts/bulk-reject` | Bulk reject payouts |
| `GET` | `/api/operators` | List active operators |
| `POST` | `/api/detect-operator` | Detect operator from phone number |

#### Admin Routes

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/admin/payment-requests` | All payment requests (filtered) |
| `POST` | `/api/admin/payment-requests/{id}/repush` | Re-push failed payment to operator |
| `POST` | `/api/admin/payment-requests/{id}/retry-callback` | Retry merchant callback |
| `GET/POST/PUT/DELETE` | `/api/admin/operators` | Operator CRUD management |
| `POST` | `/api/admin/operators/{id}/test` | Test operator connection |
| `GET/DELETE` | `/api/admin/logs` | View/clear logs |

### Callback Processing

The callback endpoint uses **auto-detection** to identify the payload format regardless of `gateway_type` configuration:

| Detected Format | Identifying Pattern | Source |
|----------------|---------------------|--------|
| `digivas_collection` | `body.request.receiptNumber` present | Digivas EPG |
| `digivas_result` | `body.result.resultCode` present | Digivas EPG |
| `daraja_stk` | `Body.stkCallback` present | Safaricom M-Pesa STK Push |
| `daraja_b2c` | `Result.ResultParameters` present | Safaricom M-Pesa B2C |
| `mtn_momo` | `financialTransactionId` present | MTN MoMo |
| `airtel_africa` | `transaction.airtel_money_id` present | Airtel Africa |
| `airtel_africa_flat` | `airtel_money_id` at top level | Airtel Africa (flat format) |

**Reference Matching:** Extracts references from all known field paths and searches by `request_ref`, `gateway_id`, and `operator_ref` with a deep-scan fallback.

---

## 6. Transaction Service

### Overview

Records all financial transactions, manages charge configurations, handles reversals, referral commissions, platform withdrawals, and provides data export capabilities.

### Models

| Model | Purpose | Key Fields |
|-------|---------|------------|
| `Transaction` | Financial transaction record | `transaction_ref`, `amount`, `platform_charge`, `operator_charge`, `type`, `status`, `operator`, `operator_receipt` |
| `ChargeConfig` | Fee configuration | `operator`, `transaction_type`, `charge_type` (fixed/percentage/dynamic), `tiers`, `applies_to` |
| `Reversal` | Transaction reversal | `reversal_ref`, `original_ref`, `amount`, `status`, `reason` |
| `PlatformWithdrawal` | Platform revenue withdrawal | `reference`, `amount`, `bank_name`, `status` |
| `ReferralCommissionConfig` | Commission rules | `operator`, `commission_type`, `tiers`, `max_commission` |
| `ReferralEarning` | Commission earnings | `referrer_account_id`, `transaction_ref`, `commission_amount`, `status` |

### Charge Calculation System

```
Request: { amount, operator, type (collection/disbursement) }
     │
     ▼
1. Check account-specific charge config
2. Fall back to global config
3. Support three charge types:
   ├── Fixed: flat fee (e.g., 500 TZS)
   ├── Percentage: % of transaction (e.g., 2.5%)
   └── Dynamic (tiered): amount-based tiers
       e.g., 0-10000: 200 TZS, 10001-50000: 1.5%, etc.
     │
     ▼
Return: { platform_charge, operator_charge }
  - platform_charge: deducted from user's net amount
  - operator_charge: platform's cost (not deducted from user)
```

### API Endpoints

#### User Routes

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/transactions` | List transactions (paginated, filterable) |
| `GET` | `/api/transactions/stats` | Total/completed/pending/failed counts |
| `GET` | `/api/transactions/export/excel` | Export as Excel |
| `GET` | `/api/transactions/export/pdf` | Export as PDF |
| `POST` | `/api/transactions` | Create transaction record |
| `GET` | `/api/transactions/{id}` | Transaction detail |
| `GET` | `/api/my-charges` | Account charge summary |
| `POST` | `/api/charges/calculate` | Calculate charges for amount |
| `POST` | `/api/reversals` | Request reversal |
| `GET` | `/api/reversals` | List reversals |

#### Admin Routes

Transaction viewing, charge config CRUD, reversal approval/rejection, direct reversals, platform withdraw management, referral commission config, referral earnings — all protected by admin permissions.

#### Internal Service-to-Service Routes

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/internal/transactions` | Record transaction (from wallet/payment services) |
| `POST` | `/api/internal/charges/calculate` | Calculate charges (from wallet/settlement) |
| `POST` | `/api/internal/referral-commission/calculate` | Calculate referral commission |
| `POST` | `/api/internal/referral-commission/record` | Record referral earning |

---

## 7. Wallet Service

### Overview

Manages dual-wallet system (collection + disbursement per operator), handles balance tracking, internal transfers requiring admin approval, and multi-currency exchange.

### Dual-Wallet Architecture

```
Each Account has per-operator wallet pairs:

Account "ABC Corp"
├── M-Pesa
│   ├── Collection Wallet: 1,500,000 TZS  (C2B payins land here)
│   └── Disbursement Wallet: 800,000 TZS  (B2C payouts come from here)
├── Tigo Pesa
│   ├── Collection Wallet: 320,000 TZS
│   └── Disbursement Wallet: 150,000 TZS
└── Airtel Money
    ├── Collection Wallet: 45,000 TZS
    └── Disbursement Wallet: 20,000 TZS
```

### Models

| Model | Purpose | Key Fields |
|-------|---------|------------|
| `Wallet` | Balance holder | `account_id`, `operator`, `wallet_type` (collection/disbursement), `balance`, `currency`, `status` |
| `WalletTransaction` | Ledger entries | `wallet_id`, `type` (credit/debit), `amount`, `reference`, `balance_before`, `balance_after` |
| `InternalTransfer` | Collection → Disbursement transfers | `account_id`, `operator`, `amount`, `status` (pending/approved/rejected) |
| `ExchangeRate` | Currency conversion rates | `from_currency`, `to_currency`, `buy_rate`, `sell_rate`, `conversion_fee_percent` |
| `CurrencyExchange` | Exchange transaction history | `from_currency`, `to_currency`, `rate_applied`, `platform_revenue` |

### API Endpoints

#### User Routes

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/wallet` | All wallets with balances |
| `POST` | `/api/wallet/credit` | Credit collection wallet (auto-calculates charges) |
| `POST` | `/api/wallet/transfer` | Request collection → disbursement transfer |
| `POST` | `/api/wallet/debit-settlement` | Debit for settlement |
| `GET` | `/api/wallet/transactions` | Wallet transaction history |
| `GET` | `/api/wallet/transfers` | Transfer request history |
| `GET` | `/api/exchange/rates` | Available exchange rates |
| `POST` | `/api/exchange/preview` | Preview currency exchange |
| `POST` | `/api/exchange/execute` | Execute currency exchange |
| `GET` | `/api/exchange/history` | Exchange history |

#### Admin Routes

Admin wallet viewing, refunds, top-ups (fund disbursement wallet), reversals, transfer approval/rejection, exchange rate management.

#### Internal Service-to-Service Routes

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/internal/wallet/credit` | Credit wallet (from payment-service callbacks) |
| `POST` | `/api/internal/wallet/debit` | Debit wallet (for disbursements) |
| `GET` | `/api/internal/wallet/summary` | Balance check for account |

### Multi-Currency Exchange

- Accounts with `multi_currency_enabled` can exchange between wallets of different currencies
- Admin-configured exchange rates with conversion fee percentage
- Atomic execution: source wallet debited, destination wallet credited in DB transaction
- Platform revenue tracked per exchange

---

## 8. Settlement Service

### Overview

Handles bank settlement requests. Users request settlements from their collection wallet balance, which go through an admin approval workflow before funds are released to the business's bank account.

### Settlement Flow

```
User Request (POST /api/settlements):
  - Minimum amount: 1,000 (currency units)
  - Fetches bank account from auth-service
  - Calculates charges from transaction-service
  - Debits collection wallet via wallet-service
  - Records transaction in transaction-service
  - Creates settlement record (status: "pending")
  - Sends webhook + admin notification
     │
     ├── Admin Approves:
     │   - Status → "approved"
     │   - Webhook: payout.approved
     │   - User notified
     │
     └── Admin Rejects:
         - Status → "rejected"
         - Full amount refunded to collection wallet (settlement + charges)
         - Webhook: payout.rejected
```

### API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/settlements` | List user's settlements |
| `POST` | `/api/settlements` | Create settlement request |
| `GET` | `/api/settlements/{id}` | Settlement detail |
| `GET` | `/api/admin/settlements` | Admin: all settlements |
| `PUT` | `/api/admin/settlements/{id}/approve` | Approve settlement |
| `PUT` | `/api/admin/settlements/{id}/reject` | Reject + refund |

---

## 9. Test Operator (MNO Simulator)

### Overview

Development/testing tool that simulates an MNO's DIGIVAS EPG interface. Receives USSD push requests from the payment-service, validates authentication, and sends configurable success/failure callbacks.

### Configuration

| Setting | Default | Purpose |
|---------|---------|---------|
| `sp_id` | `600100` | Operator service provider ID |
| `merchant_code` | `6001001` | Merchant code |
| `sp_password` | `TestOperator@2025` | Shared secret |
| `auto_callback` | `true` | Auto-send callback after receiving request |
| `auto_callback_delay` | `3` seconds | Simulates customer interaction time |
| `auto_callback_result` | `success` | Default callback result |

### API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/ussd/collection` | Receive USSD collection request |
| `POST` | `/api/ussd/disbursement` | Receive disbursement request |
| `GET` | `/api/ping` | Health check |
| `GET` | `/api/dashboard/requests` | List simulator requests |
| `POST` | `/api/dashboard/requests/{id}/callback` | Manually trigger callback |
| `GET` | `/api/dashboard/stats` | Simulator statistics |
| `DELETE` | `/api/dashboard/requests` | Clear all requests |

---

## 10. Security Features

### 10.1 Authentication

| Layer | Mechanism | Details |
|-------|-----------|---------|
| **OAuth2** | Laravel Passport personal access tokens | 1-hour access tokens, 7-day refresh tokens |
| **API Keys** | `X-API-Key` + `X-API-Secret` headers | Secret hashed with bcrypt; shown once at creation |
| **Service-to-Service** | `X-Service-Key` header | Shared secret (`INTERNAL_SERVICE_KEY` env var) |
| **MNO Authentication** | Gateway-specific | spPassword (DIGIVAS), OAuth2 (Safaricom/Airtel), Basic Auth (MTN) |

### 10.2 Two-Factor Authentication (2FA)

| Feature | Implementation |
|---------|---------------|
| **Type** | Email-based OTP |
| **Code** | 6-digit random integer (`random_int(0, 999999)`) |
| **Storage** | Hashed with `Hash::make()` — never stored in plaintext |
| **Expiry** | 10 minutes |
| **Verification** | `Hash::check()` against stored hash |
| **Toggle** | Requires password confirmation |
| **Lockout Integration** | Failed 2FA attempts count toward account lockout |

### 10.3 Login Protection

| Feature | Value |
|---------|-------|
| **Max Failed Attempts** | 5 |
| **Lockout Duration** | 30 minutes |
| **Mechanism** | `failed_login_attempts` counter + `locked_until` timestamp |
| **Reset** | Counter resets on successful login |
| **Alerts** | `AccountLockedNotification` sent to user AND all admins |
| **2FA Alert** | `FailedTwoFactorNotification` after 3+ failed 2FA attempts |
| **New IP Alert** | `NewIpLoginNotification` on login from unrecognized IP |

### 10.4 Bot Protection

| Technique | Implementation |
|-----------|---------------|
| **Honeypot** | Hidden `website` field in registration — must be empty |
| **Timing Check** | `_form_loaded_at` timestamp — rejects submissions faster than 3 seconds |
| **Rate Limiting** | `throttle:5,1` on all public auth endpoints |

### 10.5 IP Whitelisting

- Per-account IP access control enforced during API key validation
- Workflow: User requests IP → `pending` → Admin approves/rejects/suspends/reactivates
- If an account has ANY whitelist entries, only `approved` IPs are allowed
- Checked before API secret verification in the validation chain

### 10.6 SSRF Protection (Callback URLs)

| Check | Detail |
|-------|--------|
| **Protocol** | Must start with `https://` |
| **DNS Resolution** | Hostname resolved to IP address |
| **Private IP Blocking** | Blocks RFC 1918 ranges: `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.0/8`, `169.254.0.0/16` |
| **Scope** | Applied when updating callback URL (`PUT /account/callback`) |

### 10.7 Rate Limiting

| Scope | Limit | Details |
|-------|-------|---------|
| **Auth Endpoints** | 5 req/min | Login, register, 2FA, password reset |
| **Merchant API** | Configurable per account | Default 60 req/min; sliding 1-minute window |
| **Response Headers** | `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After` | Standard rate limit headers |

### 10.8 Nginx Security Headers

Applied to all service proxies:

| Header | Value |
|--------|-------|
| `X-Frame-Options` | `SAMEORIGIN` — prevents clickjacking |
| `X-Content-Type-Options` | `nosniff` — prevents MIME sniffing |
| `Cache-Control` | `no-store, no-cache, must-revalidate, max-age=0` — no caching for financial data |
| `Pragma` | `no-cache` |
| `Expires` | `0` |

### 10.9 Password Security

- Minimum 8 characters with mixed case, numbers, and symbols (enforced on password change)
- All existing tokens revoked on password change
- Fresh token issued for current session after password change

### 10.10 Data Protection

| Feature | Detail |
|---------|--------|
| **Sensitive Field Hiding** | `sp_password` hidden in Operator model responses |
| **API Secret Hashing** | bcrypt hash — never stored or returned in plaintext |
| **2FA Code Hashing** | bcrypt hash — never stored in plaintext |
| **OAuth Keys** | File permissions set to `600` (owner read/write only) |
| **Input Validation** | Laravel Form Request classes on all endpoints |
| **Enum Prevention** | Forgot-password and 2FA resend return generic messages regardless of user existence |

### 10.11 Activity Logging

All security-relevant actions are logged with IP address, user ID, account ID, and metadata:
- Login/logout events
- 2FA verification attempts
- Password changes
- Callback URL updates
- Admin actions (KYC approval, account status changes, etc.)
- API key creation/revocation

### 10.12 Maker-Checker Controls

| Operation | Workflow |
|-----------|----------|
| **Payouts** | Created → pending → admin approves/rejects |
| **Internal Transfers** | User requests → admin approves/rejects |
| **Settlements** | User requests → admin approves (or rejects with refund) |
| **IP Whitelist** | User requests → admin approves/rejects |

---

## 11. Payment Flow

### Collection (C2B — Customer to Business)

```
┌──────────┐    POST /api/v1/collection     ┌─────────────────┐
│ Merchant │ ──────────────────────────────► │ Payment Service  │
│  System  │   {phone, amount, ref, code}    │   (port 8002)   │
└──────────┘                                 └────────┬────────┘
                                                      │
                                             1. Detect operator from phone prefix
                                             2. Resolve gateway adapter
                                             3. Create PaymentRequest (status: pending)
                                                      │
                                                      ▼
                                             ┌─────────────────┐
                                             │  MNO Gateway     │
                                             │  (USSD Push)     │
                                             └────────┬────────┘
                                                      │
                                             Customer receives USSD prompt
                                             Customer enters PIN to confirm
                                                      │
                                                      ▼
                                             ┌─────────────────┐
                                             │  MNO Callback    │
                                             │  POST /callback  │
                                             └────────┬────────┘
                                                      │
                                             1. Auto-detect callback format
                                             2. Find matching PaymentRequest
                                             3. Parse receipt number & status
                                                      │
                                         ┌────────────┴────────────┐
                                         │ Success                  │ Failure
                                         ▼                          ▼
                              Credit wallet via              Update status → failed
                              wallet-service                 Notify merchant
                              Record transaction             (POST callback_url)
                              Calculate referral commission
                              Notify merchant
                              (POST callback_url)
```

### Disbursement (B2C — Business to Customer)

```
┌──────────┐    POST /api/v1/disbursement    ┌─────────────────┐
│ Merchant │ ──────────────────────────────► │ Payment Service  │
│  System  │   {phone, amount, ref, code}    │   (port 8002)   │
└──────────┘                                 └────────┬────────┘
                                                      │
                                             1. Detect operator
                                             2. Check disbursement wallet balance
                                             3. Debit wallet via wallet-service
                                             4. Create PaymentRequest (status: pending)
                                             5. Push to MNO gateway
                                                      │
                                                      ▼
                                             ┌─────────────────┐
                                             │  MNO Gateway     │
                                             │  (B2C Transfer)  │
                                             └────────┬────────┘
                                                      │
                                             MNO processes transfer
                                             Customer receives money
                                                      │
                                                      ▼
                                             ┌─────────────────┐
                                             │  MNO Callback    │
                                             │  POST /callback  │
                                             └────────┬────────┘
                                                      │
                                         ┌────────────┴────────────┐
                                         │ Success                  │ Failure
                                         ▼                          ▼
                              Update status → completed     Refund wallet
                              Record transaction            Update status → failed
                              Notify merchant               Notify merchant
```

---

## 12. Gateway Adapters

### Adapter Architecture

All gateways implement `GatewayInterface`:

```php
interface GatewayInterface {
    public function push(Operator $operator, PaymentRequest $paymentRequest): array;
    public function parseCallback(array $data): array;
    public function validateCallback(array $data, Operator $operator): bool;
    public function normalizePhone(string $phone, Operator $operator): string;
    public function capabilities(): array;
}
```

### Digivas Gateway (Tanzania)

| Feature | Detail |
|---------|--------|
| **Operators** | M-Pesa TZ, Tigo Pesa, Airtel Money TZ, Halopesa |
| **Protocol** | DIGIVAS EPG (Electronic Payment Gateway) |
| **Auth** | `spPassword = Base64(SHA-256(spId + secret + timestamp + amount + msisdn))` |
| **Collection** | Command: `UssdPush` — customer receives USSD prompt |
| **Disbursement** | Command: `Disbursement` — direct B2C transfer |
| **Response** | JSON primary, XML fallback |
| **Success Code** | `responseCode === '0'` |

### Safaricom Daraja Gateway (Kenya)

| Feature | Detail |
|---------|--------|
| **Operator** | Safaricom M-Pesa Kenya |
| **Auth** | OAuth2 with Consumer Key/Secret |
| **Collection** | STK Push (Lipa Na M-Pesa) |
| **Disbursement** | B2C Payment Request |
| **STK Password** | `Base64(ShortCode + Passkey + Timestamp)` |
| **B2C Credentials** | `InitiatorName` + `SecurityCredential` from `extra_config` |

### Airtel Africa Gateway

| Feature | Detail |
|---------|--------|
| **Countries** | Kenya, Uganda, Tanzania, Rwanda, DRC, and more |
| **Auth** | OAuth2 with `client_id` / `client_secret` |
| **Collection** | USSD Push via `POST merchant/v2/payments/` |
| **Disbursement** | B2C via `POST standard/v2/payments/` |
| **Phone Format** | Strips country code — sends subscriber number only |
| **Headers** | `X-Country` and `X-Currency` per-request |
| **Disbursement PIN** | Encrypted PIN from `extra_config` |

### MTN MoMo Gateway

| Feature | Detail |
|---------|--------|
| **Countries** | Uganda, Rwanda, Ghana, Cameroon, and more |
| **Auth** | Basic Auth per product with Subscription Key |
| **Collection** | Request to Pay (`POST collection/v1_0/requesttopay`) |
| **Disbursement** | Transfer (`POST disbursement/v1_0/transfer`) |
| **Success** | HTTP 202 Accepted |
| **Headers** | `X-Reference-Id`, `X-Target-Environment`, `Ocp-Apim-Subscription-Key`, `X-Callback-Url` |

### Gateway Factory

```php
GatewayFactory::make('digivas');          // → DigivasGateway
GatewayFactory::make('safaricom_daraja'); // → SafaricomDarajaGateway
GatewayFactory::make('airtel_africa');    // → AirtelAfricaGateway
GatewayFactory::make('mtn_momo');        // → MtnMomoGateway

// Custom adapters
GatewayFactory::register('custom', CustomGateway::class);
```

---

## 13. Database Schema

### Auth Service

| Table | Key Fields |
|-------|------------|
| `users` | id, firstname, lastname, email, password, account_id, role, permissions (JSON), two_factor_enabled, failed_login_attempts, locked_until, is_banned |
| `accounts` | id, account_ref, business_name, paybill, callback_url, status, rate_limit, referral_code, referred_by, KYC fields (8 document URLs), multi_currency_enabled |
| `api_keys` | id, account_id, api_key, api_secret (hashed), status, expires_at |
| `bank_accounts` | id, account_id, bank_name, account_number, swift_code, is_default |
| `crypto_wallets` | id, account_id, currency, network, wallet_address, is_default |
| `ip_whitelists` | id, account_id, ip_address, status, requested_by, approved_by |
| `email_templates` | id, key, name, subject, body, is_active |
| `admin_settings` | id, key, value |
| `activity_logs` | id, user_id, account_id, action, description, ip_address, metadata (JSON) |
| `oauth_*` | Passport OAuth tables (clients, tokens, auth_codes, etc.) |

### Payment Service

| Table | Key Fields |
|-------|------------|
| `payment_requests` | id, account_id, request_ref, external_ref, operator_ref, receipt_number, gateway_id, type, phone, amount, platform_charge, operator_charge, status, callback_data (JSON), callback_status, transaction_id, approved_by, batch_name |
| `operators` | id, name, code, prefixes (JSON), api_url, sp_id, sp_password, gateway_type, callback_url, country, currency, extra_config (JSON) |

### Transaction Service

| Table | Key Fields |
|-------|------------|
| `transactions` | id, account_id, transaction_ref, amount, platform_charge, operator_charge, type (collection/disbursement/topup/settlement), status, operator, operator_receipt, phone_number |
| `charge_configs` | id, account_id (null=global), operator, transaction_type, charge_type (fixed/percentage/dynamic), charge_value, tiers (JSON), applies_to (platform/operator) |
| `reversals` | id, transaction_id, reversal_ref, original_ref, amount, status (pending/approved/rejected), reason |
| `platform_withdrawals` | id, reference, amount, bank_name, account_number, status (pending/completed/cancelled) |
| `referral_commission_configs` | id, operator, transaction_type, commission_type, commission_value, tiers (JSON), max_commission |
| `referral_earnings` | id, referrer_account_id, referred_account_id, transaction_ref, commission_amount, status |

### Wallet Service

| Table | Key Fields |
|-------|------------|
| `wallets` | id, account_id, operator, wallet_type (collection/disbursement), balance, currency, status |
| `wallet_transactions` | id, wallet_id, type (credit/debit), amount, reference, balance_before, balance_after, status |
| `internal_transfers` | id, account_id, operator, amount, reference, status (pending/approved/rejected) |
| `exchange_rates` | id, from_currency, to_currency, buy_rate, sell_rate, conversion_fee_percent, is_active |
| `currency_exchanges` | id, account_id, from_currency, to_currency, from_amount, to_amount, rate_applied, platform_revenue |

### Settlement Service

| Table | Key Fields |
|-------|------------|
| `settlements` | id, account_id, settlement_ref, amount, operator, status, bank_name, account_number, metadata (JSON with charges) |

### Test Operator

| Table | Key Fields |
|-------|------------|
| `simulator_requests` | id, type, command, reference, msisdn, amount, callback_url, auth_valid, callback_status, receipt_number |

---

## 14. Deployment

### Server Configuration

| Component | Detail |
|-----------|--------|
| **Server** | Ubuntu Linux |
| **Web Server** | Nginx (reverse proxy) |
| **PHP** | 8.3 (PHP-FPM) |
| **SSL** | Certbot (Let's Encrypt) |
| **Project Path** | `/var/www/payment/` |
| **Repository** | `github.com/magorikihore/payin.git` (branch: main) |

### Deployment Process

The `deploy.sh` script performs the following for each service:

1. `git pull origin main` — Pull latest code
2. `composer install --no-dev --optimize-autoloader` — Install production dependencies
3. `php artisan migrate --force` — Run database migrations
4. Clear all caches: config, route, view, cache, event
5. Set permissions: `www-data:www-data` ownership, `775` on storage/cache
6. Protect OAuth keys: `chmod 600` on Passport key files
7. Restart PHP-FPM and reload Nginx

### Nginx Configuration

Each service runs via `php artisan serve` on localhost and is reverse-proxied through Nginx:

```
auth.payin.co.tz      → 127.0.0.1:8001
login.payin.co.tz     → 127.0.0.1:8002
api.payin.co.tz       → 127.0.0.1:8002
tx.payin.co.tz        → 127.0.0.1:8003
wallet.payin.co.tz    → 127.0.0.1:8004
settle.payin.co.tz    → 127.0.0.1:8005
payin.co.tz           → static /var/www/payment/www/index.html
```

All proxied services include:
- Security headers (X-Frame-Options, X-Content-Type-Options)
- No-cache directives for real-time financial data
- Client IP forwarding (X-Real-IP, X-Forwarded-For, X-Forwarded-Proto)
- 60-second proxy read timeout

### Environment Variables

Each service requires a `.env` file with:

| Variable | Purpose |
|----------|---------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Application encryption key |
| `DB_*` | Database connection settings |
| `AUTH_SERVICE_URL` | URL to auth-service (for token validation) |
| `PAYMENT_SERVICE_URL` | URL to payment-service |
| `TRANSACTION_SERVICE_URL` | URL to transaction-service |
| `WALLET_SERVICE_URL` | URL to wallet-service |
| `INTERNAL_SERVICE_KEY` | Shared secret for service-to-service auth |
| `MAIL_*` | SMTP configuration |
| `PASSPORT_PRIVATE_KEY` | RSA private key (auth-service only) |
| `PASSPORT_PUBLIC_KEY` | RSA public key (auth-service only) |

---

## 15. API Reference Summary

### Quick Reference — Merchant Integration

#### Authentication

```
Headers:
  X-API-Key: pk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
  X-API-Secret: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

#### Collection Request

```http
POST https://api.payin.co.tz/api/v1/collection
Content-Type: application/json

{
  "phone": "0712345678",
  "amount": 10000,
  "external_ref": "ORDER-001",
  "operator_code": "mpesa",
  "description": "Payment for Order #001"
}
```

#### Disbursement Request

```http
POST https://api.payin.co.tz/api/v1/disbursement
Content-Type: application/json

{
  "phone": "0712345678",
  "amount": 5000,
  "external_ref": "PAYOUT-001",
  "operator_code": "mpesa",
  "description": "Salary payment"
}
```

#### Status Check

```http
GET https://api.payin.co.tz/api/v1/status/PAY-XXXXXXXXXXXX
```

#### Webhook Callback (sent to merchant)

```json
{
  "event": "payment.completed",
  "request_ref": "PAY-XXXXXXXXXXXX",
  "external_ref": "ORDER-001",
  "status": "completed",
  "amount": 10000,
  "receipt_number": "QK83HFMR2V",
  "operator": "mpesa",
  "phone": "255712345678",
  "timestamp": "2025-07-01T12:00:00Z"
}
```

### Total API Endpoint Count

| Service | Public | User | Admin | Internal | Total |
|---------|--------|------|-------|----------|-------|
| Auth | 8 | 25+ | 40+ | 5 | ~80 |
| Payment | 2 | 14 | 8 | — | ~24 |
| Transaction | — | 10 | 20+ | 4 | ~34 |
| Wallet | — | 10 | 12 | 3 | ~25 |
| Settlement | — | 3 | 4 | — | ~7 |
| Test Operator | 3 | — | 5 | — | ~8 |
| **Total** | **13** | **62+** | **89+** | **12** | **~178** |

---

*This document provides a comprehensive overview of the Payin payment platform architecture, features, security controls, and API surface. For specific endpoint request/response schemas, refer to the individual service API documentation files (OPERATOR-API.md, USER-DASHBOARD-API.md).*
