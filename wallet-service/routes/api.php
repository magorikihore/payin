<?php

use App\Http\Controllers\WalletController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.service')->group(function () {
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/credit', [WalletController::class, 'credit']);
    Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
    Route::post('/wallet/debit-settlement', [WalletController::class, 'debitSettlement']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/transfers', [WalletController::class, 'myTransfers']);

    // Admin: all wallets across all accounts
    Route::get('/admin/wallets', [WalletController::class, 'adminWallets']);
    Route::post('/admin/wallet/refund', [WalletController::class, 'adminRefund']);
    Route::post('/admin/wallet/fund', [WalletController::class, 'adminFund']);
    Route::post('/admin/wallet/reverse', [WalletController::class, 'adminReverse']);
    Route::get('/admin/internal-transfers', [WalletController::class, 'adminTransfers']);
    Route::put('/admin/internal-transfers/{id}/approve', [WalletController::class, 'approveTransfer']);
    Route::put('/admin/internal-transfers/{id}/reject', [WalletController::class, 'rejectTransfer']);

    // Logs (super_admin only)
    Route::get('/admin/logs', [LogController::class, 'index']);
    Route::delete('/admin/logs', [LogController::class, 'clear']);
});
