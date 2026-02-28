# Test Operator Simulator

A Laravel project that simulates a mobile money operator (like M-Pesa, Tigo Pesa) for testing the Payin payment platform end-to-end.

## What It Does

1. **Receives** collection/disbursement requests from Payin in DIGIVAS EPG header/body format
2. **Validates** the spPassword authentication (Base64 SHA256)
3. **Returns** immediate acknowledgment (responseCode: 0)
4. **Sends callbacks** back to Payin callback URL (auto or manual)
5. **Dashboard** at http://localhost:8006 to view requests and control callbacks

## Quick Start

```bash
cd test-operator
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve --port=8006
```

Open http://localhost:8006 in your browser.

## Configure in Payin Admin

Add this operator in the Payin admin panel -> Operators section:

| Field              | Value                        |
|--------------------|------------------------------|
| Name               | Test Operator                |
| Code               | testoperator                 |
| API URL            | http://localhost:8006/api     |
| SP ID              | 600100                       |
| Merchant Code      | 6001001                      |
| SP Password        | TestOperator@2025            |
| Collection Path    | /ussd/collection             |
| Disbursement Path  | /ussd/disbursement           |
| Callback URL       | http://localhost:8002/api/callback/testoperator |
| Prefixes           | ["099"]                      |
| API Version        | 5.0                          |
| Status             | active                       |

## Auto-Callback Settings (.env)

```
AUTO_CALLBACK=true          # true = auto-send, false = manual only
AUTO_CALLBACK_DELAY=3       # seconds to wait before sending
AUTO_CALLBACK_RESULT=success # success or failed
```

## Port: 8006
