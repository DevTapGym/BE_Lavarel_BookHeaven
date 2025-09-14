<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivationController;

// ---------------------
// Public routes 
// ---------------------
Route::prefix('/v1')->group(function () {

    Route::prefix('/auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
        Route::post('/activate/send-code', [ActivationController::class, 'sendActivationCode']);
        Route::post('/activate/verify-code', [ActivationController::class, 'verifyActivationCode']);
    });
});

// ---------------------
// Protected routes 
// ---------------------
Route::prefix('/v1')->middleware(['jwt.auth', 'check.permission', 'active'])->group(function () {

    // Auth protected
    Route::prefix('/auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    });
});
