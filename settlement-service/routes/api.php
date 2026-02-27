<?php

use App\Http\Controllers\SettlementController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.service')->group(function () {
    Route::get('/settlements', [SettlementController::class, 'index']);
    Route::post('/settlements', [SettlementController::class, 'store']);
    Route::get('/settlements/{id}', [SettlementController::class, 'show']);

    // Admin routes
    Route::get('/admin/settlements', [SettlementController::class, 'adminIndex']);
    Route::put('/admin/settlements/{id}/approve', [SettlementController::class, 'approve']);
    Route::put('/admin/settlements/{id}/reject', [SettlementController::class, 'reject']);

    // Logs (super_admin only)
    Route::get('/admin/logs', [LogController::class, 'index']);
    Route::delete('/admin/logs', [LogController::class, 'clear']);
});
