<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionExportController;
use App\Http\Controllers\ChargeConfigController;
use App\Http\Controllers\ReversalController;
use App\Http\Controllers\PlatformWithdrawalController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.service')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/stats', [TransactionController::class, 'stats']);
    Route::get('/transactions/export/excel', [TransactionExportController::class, 'exportExcel']);
    Route::get('/transactions/export/pdf', [TransactionExportController::class, 'exportPdf']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::get('/my-charges', [TransactionController::class, 'myCharges']);

    // Reversals
    Route::post('/reversals', [ReversalController::class, 'store']);
    Route::get('/reversals', [ReversalController::class, 'index']);

    // Admin: all transactions across all accounts
    Route::get('/admin/transactions', [TransactionController::class, 'adminIndex']);
    Route::get('/admin/transactions/export/excel', [TransactionExportController::class, 'adminExportExcel']);
    Route::get('/admin/transactions/export/pdf', [TransactionExportController::class, 'adminExportPdf']);
    Route::get('/admin/charge-revenue', [TransactionController::class, 'chargeRevenue']);

    // Admin: reversals
    Route::get('/admin/reversals', [ReversalController::class, 'adminIndex']);
    Route::post('/admin/reversals/direct', [ReversalController::class, 'directReverse']);
    Route::put('/admin/reversals/{id}/approve', [ReversalController::class, 'approve']);
    Route::put('/admin/reversals/{id}/reject', [ReversalController::class, 'reject']);

    // Charge calculation (any authenticated user)
    Route::post('/charges/calculate', [ChargeConfigController::class, 'calculate']);

    // Charge config management (super_admin only)
    Route::get('/charges', [ChargeConfigController::class, 'index']);
    Route::post('/charges', [ChargeConfigController::class, 'store']);
    Route::put('/charges/{id}', [ChargeConfigController::class, 'update']);
    Route::delete('/charges/{id}', [ChargeConfigController::class, 'destroy']);

    // Logs (super_admin only)
    Route::get('/admin/logs', [LogController::class, 'index']);
    Route::delete('/admin/logs', [LogController::class, 'clear']);

    // Platform Profit Withdrawals (super_admin only)
    Route::get('/admin/platform-withdrawals/summary', [PlatformWithdrawalController::class, 'summary']);
    Route::get('/admin/platform-withdrawals', [PlatformWithdrawalController::class, 'index']);
    Route::post('/admin/platform-withdrawals', [PlatformWithdrawalController::class, 'store']);
    Route::put('/admin/platform-withdrawals/{id}/complete', [PlatformWithdrawalController::class, 'complete']);
    Route::put('/admin/platform-withdrawals/{id}/cancel', [PlatformWithdrawalController::class, 'cancel']);
});

// Internal service-to-service routes (protected by service key)
Route::middleware('internal.service')->group(function () {
    Route::post('/internal/transactions', [TransactionController::class, 'internalStore']);
    Route::post('/internal/charges/calculate', [ChargeConfigController::class, 'calculate']);
});
