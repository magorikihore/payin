<?php

use App\Http\Controllers\WalletController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.service')->group(function () {
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/credit', [WalletController::class, 'credit']);
    Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
    Route::post('/wallet/debit-settlement', [WalletController::class, 'debitSettlement']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/transfers', [WalletController::class, 'myTransfers']);

    // Currency exchange (user)
    Route::get('/exchange/rates', [ExchangeRateController::class, 'availableRates']);
    Route::post('/exchange/preview', [ExchangeRateController::class, 'preview']);
    Route::post('/exchange/execute', [ExchangeRateController::class, 'exchange']);
    Route::get('/exchange/history', [ExchangeRateController::class, 'myExchanges']);

    // Admin: all wallets across all accounts
    Route::get('/admin/wallets', [WalletController::class, 'adminWallets']);
    Route::post('/admin/wallet/refund', [WalletController::class, 'adminRefund']);
    Route::post('/admin/wallet/fund', [WalletController::class, 'adminFund']);
    Route::post('/admin/wallet/reverse', [WalletController::class, 'adminReverse']);
    Route::get('/admin/internal-transfers', [WalletController::class, 'adminTransfers']);
    Route::put('/admin/internal-transfers/{id}/approve', [WalletController::class, 'approveTransfer']);
    Route::put('/admin/internal-transfers/{id}/reject', [WalletController::class, 'rejectTransfer']);

    // Admin: exchange rate management
    Route::get('/admin/exchange-rates', [ExchangeRateController::class, 'index']);
    Route::post('/admin/exchange-rates', [ExchangeRateController::class, 'upsert']);
    Route::put('/admin/exchange-rates/{id}/toggle', [ExchangeRateController::class, 'toggle']);
    Route::delete('/admin/exchange-rates/{id}', [ExchangeRateController::class, 'destroy']);
    Route::get('/admin/exchange-history', [ExchangeRateController::class, 'adminExchangeHistory']);

    // Logs (super_admin only)
    Route::get('/admin/logs', [LogController::class, 'index']);
    Route::delete('/admin/logs', [LogController::class, 'clear']);
});

// Internal service-to-service routes (protected by service key)
Route::middleware('internal.service')->group(function () {
    Route::post('/internal/wallet/credit', [WalletController::class, 'internalCredit']);
    Route::post('/internal/wallet/debit', [WalletController::class, 'internalDebit']);
});
