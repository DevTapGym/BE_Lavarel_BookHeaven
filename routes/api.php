<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookFeatureController;
use App\Http\Controllers\BookImageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;

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

    Route::prefix('/book')->group(function () {
        Route::get('/', [BookController::class, 'indexPaginated']);
        Route::get('/{book}', [BookController::class, 'show']);
        Route::get('/category/{category_id}', [BookController::class, 'getBooksByCategory']);
        Route::get('/feature/{book_id}', [BookFeatureController::class, 'index'])->name('view.book.features');
        Route::get('/images/{book_id}', [BookImageController::class, 'getBookImages'])->name('view.book.images');
    });

    Route::prefix('/category')->group(function () {
        Route::get('/', [CategoryController::class, 'indexPaginated']);
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
        Route::post('/add', [CartController::class, 'addItemCart'])->name('add.cart.item');
        Route::put('/update/{cart_item_id}', [CartController::class, 'updateCartItem'])->name('update.cart.item');
        Route::delete('/remove/{cart_item_id}', [CartController::class, 'removeItemCart'])->name('remove.cart.item');
    });

    Route::prefix('/book')->group(function () {
        Route::post('/', [BookController::class, 'store'])->name('create.book');
        Route::put('/', [BookController::class, 'update'])->name('update.book');
        Route::delete('/{book}', [BookController::class, 'destroy'])->name('delete.book');
        // category
        Route::post('/attach-categories', [BookController::class, 'attachCategories'])->name('attach.book.categories');
        Route::put('/sync-categories', [BookController::class, 'syncCategories'])->name('sync.book.categories');
        Route::delete('/detach-categories', [BookController::class, 'detachCategories'])->name('detach.book.categories');
        // images
        Route::post('/images', [BookImageController::class, 'addBookImages'])->name('add.book.images');
        Route::delete('/images/{image_id}', [BookImageController::class, 'deleteBookImage'])->name('delete.book.image');
        Route::delete('/images/book/{book_id}', [BookImageController::class, 'deleteAllBookImages'])->name('delete.book.all.images');
        // featured
        Route::post('/feature', [BookFeatureController::class, 'store'])->name('add.book.feature');
        Route::put('/feature', [BookFeatureController::class, 'update'])->name('update.book.feature');
        Route::delete('/feature/{feature_id}', [BookFeatureController::class, 'destroy'])->name('delete.book.feature');
        Route::delete('/feature/book/{book_id}', [BookFeatureController::class, 'destroyAll'])->name('delete.all.book.features');
    });

    Route::prefix('/category')->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->name('create.category');
        Route::put('/', [CategoryController::class, 'update'])->name('update.category');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('delete.category');
    });
});
