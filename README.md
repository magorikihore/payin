# Payin — Mobile Money Payment Platform

A microservices-based mobile money payment platform for Tanzania (M-Pesa, Tigo Pesa, Airtel Money, Halopesa). Built with Laravel 11 + Passport, Alpine.js, and Tailwind CSS.

## Architecture

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────────────┐
│  Dashboard   │────▶│  Payment Service  │────▶│  Operator Gateway   │
│  (Blade+JS)  │     │  (API + UI)       │     │  (M-Pesa, Tigo...)  │
└─────────────┘     └──────────────────┘     └─────────────────────┘
       │                     │
       ▼                     ▼
┌─────────────┐     ┌──────────────────┐     ┌─────────────────────┐
│ Auth Service │     │ Transaction Svc   │     │  Settlement Service │
│ (Passport)   │     │ (History+Charges) │     │  (Withdrawals)      │
└─────────────┘     └──────────────────┘     └─────────────────────┘
                            │
                            ▼
                    ┌──────────────────┐
                    │  Wallet Service   │
                    │  (Balances)       │
                    └──────────────────┘
```

## Services

| Service              | Port | Description                                              |
|----------------------|------|----------------------------------------------------------|
| **auth-service**     | 8001 | User auth (Passport), accounts, KYC, API keys, users     |
| **payment-service**  | 8002 | Payment initiation, operator gateway, merchant API, UI   |
| **transaction-service** | 8003 | Transaction history, stats, charges, reversals         |
| **wallet-service**   | 8004 | Per-operator wallets, balances, internal transfers        |
| **settlement-service** | 8005 | Settlement requests, bank withdrawals                  |

## Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- MySQL / MariaDB
- WAMP / LAMP / Nginx

### Running Locally

```bash
# Start each service in a separate terminal
cd auth-service && php artisan serve --host=127.0.0.1 --port=8001
cd payment-service && php artisan serve --host=127.0.0.1 --port=8002
cd transaction-service && php artisan serve --host=127.0.0.1 --port=8003
cd wallet-service && php artisan serve --host=127.0.0.1 --port=8004
cd settlement-service && php artisan serve --host=127.0.0.1 --port=8005
```

### Environment Setup

Each service needs its own `.env` file with database credentials and service URLs:

```env
# Example for payment-service/.env
AUTH_SERVICE_URL=http://127.0.0.1:8001
TRANSACTION_SERVICE_URL=http://127.0.0.1:8003
WALLET_SERVICE_URL=http://127.0.0.1:8004
SETTLEMENT_SERVICE_URL=http://127.0.0.1:8005
```

### Database Migration

```bash
cd <service-name>
php artisan migrate --seed
```

## API Documentation

| Document | Description |
|----------|-------------|
| [User Dashboard API](payment-service/USER-DASHBOARD-API.md) | Complete reference for all 45 user-facing endpoints |
| [Operator API](payment-service/OPERATOR-API.md) | Integration guide for mobile money operators |

## User Interfaces

| URL | Service | Description |
|-----|---------|-------------|
| `/login` | payment-service | Merchant login |
| `/dashboard` | payment-service | Merchant dashboard |
| `/admin` | payment-service | Admin panel |
| `/login` | wallet-service | Wallet dashboard login |
| `/dashboard` | wallet-service | Wallet dashboard |
| `/login` | settlement-service | Settlement dashboard login |
| `/dashboard` | settlement-service | Settlement dashboard |

## Key Features

- **Multi-operator support** — M-Pesa, Tigo Pesa, Airtel Money, Halopesa
- **Collection (Payin)** — Push USSD to customer phone for payment
- **Disbursement (Payout)** — Send money to customer mobile wallet
- **Per-operator wallets** — Separate collection & disbursement balances
- **Settlements** — Withdraw from collection wallet to bank account
- **KYC management** — Account verification with document uploads
- **API key authentication** — Secure merchant API integration
- **IP whitelisting** — Restrict API access by IP
- **Role-based access** — Owner, Admin, Viewer roles with granular permissions
- **Admin panel** — Full platform management with 14+ tabs
- **Webhook callbacks** — Real-time payment status notifications
- **Charge management** — Configurable platform & operator charges
- **Transaction reversals** — Request and approve reversals
- **Internal transfers** — Move funds between collection & disbursement wallets
- **Error logs viewer** — Cross-service log monitoring from admin panel

## Production Deployment

See `deploy.sh` and `nginx.conf` for server configuration. Services are deployed as separate Nginx vhosts:

```
auth.yourdomain.com     → auth-service
api.yourdomain.com      → payment-service
tx.yourdomain.com       → transaction-service
wallet.yourdomain.com   → wallet-service
settle.yourdomain.com   → settlement-service
```

### Cache Clear (after deploy)

```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
```
