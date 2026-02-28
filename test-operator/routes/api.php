<?php

use App\Http\Controllers\SimulatorController;
use Illuminate\Support\Facades\Route;

// =====================================================================
//  OPERATOR ENDPOINTS  These receive requests from Payin
// =====================================================================

// Collection: Payin pushes USSD collection request here
Route::post('/ussd/collection', [SimulatorController::class, 'collection']);

// Disbursement: Payin pushes disbursement request here
Route::post('/ussd/disbursement', [SimulatorController::class, 'disbursement']);

// Health check
Route::get('/ping', [SimulatorController::class, 'ping']);

// =====================================================================
//  DASHBOARD API  Used by the web dashboard
// =====================================================================
Route::prefix('dashboard')->group(function () {
    Route::get('/requests', [SimulatorController::class, 'index']);
    Route::get('/requests/{id}', [SimulatorController::class, 'show']);
    Route::post('/requests/{id}/callback', [SimulatorController::class, 'triggerCallback']);
    Route::get('/stats', [SimulatorController::class, 'stats']);
    Route::delete('/requests', [SimulatorController::class, 'clear']);
});
