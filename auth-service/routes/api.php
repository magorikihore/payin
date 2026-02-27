<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\IpWhitelistController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Password reset (throttled separately — 5 attempts per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyCode']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// Internal: API key validation (called by other services)
Route::post('/internal/validate-api-key', [ApiKeyController::class, 'validate']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/account/callback', [AuthController::class, 'getCallback']);
    Route::put('/account/callback', [AuthController::class, 'updateCallback']);
    Route::get('/account/kyc', [AuthController::class, 'getKyc']);
    Route::post('/account/kyc', [AuthController::class, 'updateKyc']);

    // API Keys management (owner)
    Route::get('/account/api-keys', [ApiKeyController::class, 'index']);
    Route::post('/account/api-keys', [ApiKeyController::class, 'store']);
    Route::delete('/account/api-keys/{id}', [ApiKeyController::class, 'destroy']);

    // IP Whitelist (user)
    Route::get('/account/ips', [IpWhitelistController::class, 'index']);
    Route::post('/account/ips', [IpWhitelistController::class, 'store']);
    Route::delete('/account/ips/{id}', [IpWhitelistController::class, 'destroy']);

    // Account user management (owner/admin)
    Route::get('/account/users', [AccountUserController::class, 'index']);
    Route::post('/account/users', [AccountUserController::class, 'store']);
    Route::put('/account/users/{id}/role', [AccountUserController::class, 'updateRole']);
    Route::put('/account/users/{id}/permissions', [AccountUserController::class, 'updatePermissions']);
    Route::delete('/account/users/{id}', [AccountUserController::class, 'destroy']);

    // Super admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/accounts', [AdminController::class, 'accounts']);
        Route::get('/accounts/{id}', [AdminController::class, 'accountDetail']);
        Route::put('/accounts/{id}/status', [AdminController::class, 'updateAccountStatus']);
        Route::put('/accounts/{id}/kyc-notes', [AdminController::class, 'updateKycNotes']);
        Route::put('/accounts/{id}/rate-limit', [AdminController::class, 'updateRateLimit']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}/reset-password', [AdminController::class, 'resetPassword']);

        // Admin user management (super_admin only)
        Route::get('/admin-users', [AdminController::class, 'adminUsers']);
        Route::post('/admin-users', [AdminController::class, 'createAdminUser']);
        Route::put('/admin-users/{id}', [AdminController::class, 'updateAdminUser']);
        Route::delete('/admin-users/{id}', [AdminController::class, 'deleteAdminUser']);

        // IP Whitelist (admin)
        Route::get('/ip-whitelist', [IpWhitelistController::class, 'adminIndex']);
        Route::put('/ip-whitelist/{id}/approve', [IpWhitelistController::class, 'approve']);
        Route::put('/ip-whitelist/{id}/reject', [IpWhitelistController::class, 'reject']);

        // Logs (super_admin only)
        Route::get('/logs', [LogController::class, 'index']);
        Route::delete('/logs', [LogController::class, 'clear']);
    });
});
