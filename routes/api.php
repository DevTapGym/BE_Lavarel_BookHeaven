<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Models\Employee;

// ---------------------
// Public routes 
// ---------------------
Route::prefix('/v1')->group(function () {

    Route::prefix('/auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
        Route::post('/send-code', [ActivationController::class, 'sendActivationCode']);
        Route::post('/verify-code', [ActivationController::class, 'verifyActivationCode']);
        Route::post('/forgot-password', [ActivationController::class, 'forgotPassword']);
        Route::post('/reset-password', [ActivationController::class, 'resetPassword']);
    });
});

// ---------------------
// Protected routes 
// ---------------------
Route::prefix('/v1')->middleware(['jwt.auth', 'check.permission', 'active'])->group(function () {

    // Auth protected
    Route::prefix('/auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('get.info');
    });

    Route::prefix('/customer')->group(function () {
        Route::get('/', [CustomerController::class, 'indexPaginated'])->name('view.customers');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show.customer');
        Route::post('/', [CustomerController::class, 'store'])->name('create.customer');
        Route::put('/', [CustomerController::class, 'update'])->name('update.customer');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('delete.customer');
    });

    Route::prefix('/employee')->group(function () {
        Route::get('/', [EmployeeController::class, 'indexPaginated'])->name('view.employees');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show.employee');
        Route::post('/', [EmployeeController::class, 'store'])->name('create.employee');
        Route::put('/', [EmployeeController::class, 'update'])->name('update.employee');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('delete.employee');
    });

    Route::prefix('/cart')->group(function () {
        Route::get('/{customer_id}', [CartController::class, 'getCartItemsByCustomer'])->name('view.cart.items');
        Route::post('/', [CartController::class, 'store'])->name('create.cart');
        Route::post('/add-item', [CartController::class, 'addItemToCart'])->name('add.cart.item');
        Route::put('/update-item/{cart_item_id}', [CartController::class, 'updateCartItem'])->name('update.cart.item');
        Route::delete('/remove-item/{cart_item_id}', [CartController::class, 'removeCartItem'])->name('remove.cart.item');
    });
});
