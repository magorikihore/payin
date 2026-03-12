<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\LogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =====================================================================
//  MERCHANT API — Authenticated with API Key (X-API-Key + X-API-Secret)
// =====================================================================
Route::middleware(['auth.apikey', 'throttle.account'])->prefix('v1')->group(function () {
    // Push USSD Collection (payin)
    Route::post('/collection', [PaymentController::class, 'collection']);

    // Manual C2B Invoice (customer pays with reference)
    Route::post('/invoice', [PaymentController::class, 'invoice']);

    // Push Disbursement (payout)
    Route::post('/disbursement', [PaymentController::class, 'disbursement']);

    // Check payment status
    Route::get('/status/{request_ref}', [PaymentController::class, 'status']);

    // List active operators
    Route::get('/operators', [OperatorController::class, 'active']);
});

// =====================================================================
//  OPERATOR CALLBACK — Public (no auth), operators POST results here
//  Single URL: matches payment request by reference/gateway_id from payload
// =====================================================================
Route::post('/callback', [PaymentController::class, 'callback']);
Route::post('/callback/{operator_code}', [PaymentController::class, 'callback']);

// =====================================================================
//  DASHBOARD API — Authenticated with Bearer Token (auth.service)
// =====================================================================
Route::middleware('auth.service')->group(function () {
    // User's own payment requests
    Route::get('/payment-requests', [PaymentController::class, 'myRequests']);
    Route::get('/payment-requests/{request_ref}', [PaymentController::class, 'status']);

    // Can also push collection/disbursement via bearer token (dashboard use)
    Route::post('/collection', [PaymentController::class, 'collection']);
    Route::post('/invoice', [PaymentController::class, 'invoice']);
    Route::put('/invoice/{request_ref}/cancel', [PaymentController::class, 'cancelInvoice']);
    Route::get('/callback-logs', [PaymentController::class, 'callbackLogs']);
    Route::post('/disbursement', [PaymentController::class, 'disbursement']);
    Route::post('/disbursement/batch', [PaymentController::class, 'batchDisbursement']);

    // Payout approval (maker-checker)
    Route::get('/payouts/pending', [PaymentController::class, 'pendingPayouts']);
    Route::put('/payouts/{id}/approve', [PaymentController::class, 'approvePayout']);
    Route::put('/payouts/{id}/reject', [PaymentController::class, 'rejectPayout']);
    Route::post('/payouts/bulk-approve', [PaymentController::class, 'bulkApprovePayout']);
    Route::post('/payouts/bulk-reject', [PaymentController::class, 'bulkRejectPayout']);

    // Active operators (for dashboard send-money form)
    Route::get('/operators', [PaymentController::class, 'activeOperators']);

    // Detect operator from phone number
    Route::post('/detect-operator', [PaymentController::class, 'detectOperator']);

    // Admin: payment requests
    Route::get('/admin/payment-requests', [PaymentController::class, 'adminIndex']);
    Route::post('/admin/payment-requests/{id}/repush', [PaymentController::class, 'repush']);
    Route::post('/admin/payment-requests/{id}/retry-callback', [PaymentController::class, 'retryCallback']);

    // Admin: operator management (super_admin only)
    Route::get('/admin/operators', [OperatorController::class, 'index']);
    Route::post('/admin/operators', [OperatorController::class, 'store']);
    Route::put('/admin/operators/{id}', [OperatorController::class, 'update']);
    Route::delete('/admin/operators/{id}', [OperatorController::class, 'destroy']);
    Route::post('/admin/operators/{id}/test', [OperatorController::class, 'test']);

    // Logs (super_admin only)
    Route::get('/admin/logs', [LogController::class, 'index']);
    Route::delete('/admin/logs', [LogController::class, 'clear']);
});
