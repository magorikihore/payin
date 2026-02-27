# Laravel Microservices Mobile Payment System

This project contains multiple Laravel microservices for a mobile payment system similar to Flutterwave.

## Services
- **auth-service**: Handles user registration, login, and authentication.
- **payment-service**: Handles payment processing and status queries.
- **transaction-service**: Manages transaction history and details.

## Running Services
Each service is a standalone Laravel app. To run a service:

```
cd <service-name>
php artisan serve --host=127.0.0.1 --port=<port>
```

Recommended ports:
- auth-service: 8001
- payment-service: 8002
- transaction-service: 8003

## API Endpoints

### Auth Service
- POST `/api/register` - Register a new user
- POST `/api/login` - Login
- GET `

` - Get current user (auth required)
- POST `/api/logout` - Logout (auth required)

### Payment Service
- POST `/api/pay` - Initiate payment (auth required)
- GET `/api/status/{transaction_id}` - Get payment status (auth required)

### Transaction Service
- GET `/api/transactions` - List transactions (auth required)
- GET `/api/transactions/{id}` - Get transaction details (auth required)

## Notes
- Each service can be containerized with Docker for production.
- API gateway and inter-service communication can be added as needed.
- See each service's README for more details.
