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
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ImportReceiptController;
use App\Http\Controllers\AddressTagController;
use App\Http\Controllers\ShippingAddressController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\OrderStatusHistoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;

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

    Route::prefix('/supplier')->group(function () {
        Route::get('/', [SupplierController::class, 'indexPaginated'])->name('view.suppliers');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show.supplier');
        Route::get('/{id}/books', [SupplierController::class, 'getBooksBySupplier'])->name('show.supplier.books');
        Route::get('/{id}/supplies', [SupplierController::class, 'getSuppliesBySupplier'])->name('show.supplier.supplies');
        Route::post('/', [SupplierController::class, 'store'])->name('create.supplier');
        Route::put('/', [SupplierController::class, 'update'])->name('update.supplier');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('delete.supplier');
    });

    Route::prefix('/supply')->group(function () {
        Route::get('/', [SupplyController::class, 'indexPaginated'])->name('view.supplies');
        Route::get('/fetch-supply', [SupplyController::class, 'getByBookAndSupplier'])->name('show.supply');
        Route::post('/', [SupplyController::class, 'store'])->name('create.supply');
        Route::put('/', [SupplyController::class, 'update'])->name('update.supply');
        Route::delete('/{supply}', [SupplyController::class, 'destroy'])->name('delete.supply');
    });

    Route::prefix('/import-receipt')->group(function () {
        Route::get('/', [ImportReceiptController::class, 'indexPaginated'])->name('view.import.receipts');
        Route::get('/{import_receipt}', [ImportReceiptController::class, 'show'])->name('show.import.receipt');
        Route::post('/', [ImportReceiptController::class, 'store'])->name('create.import.receipt');
        Route::put('/', [ImportReceiptController::class, 'update'])->name('update.import.receipt');
    });

    Route::prefix('/address-tag')->group(function () {
        Route::get('/', [AddressTagController::class, 'index'])->name('view.address.tags');
        Route::post('/', [AddressTagController::class, 'store'])->name('create.address.tag');
        Route::put('/', [AddressTagController::class, 'update'])->name('update.address.tag');
        Route::delete('/{addressTag}', [AddressTagController::class, 'destroy'])->name('delete.address.tag');
    });

    Route::prefix('/address')->group(function () {
        Route::get('/customer/{customer_id}', [ShippingAddressController::class, 'getAddressesByCustomer'])->name('view.customer.addresses');
        Route::post('/', [ShippingAddressController::class, 'store'])->name('create.shipping.address');
        Route::put('/', [ShippingAddressController::class, 'update'])->name('update.shipping.address');
        Route::delete('/{id}', [ShippingAddressController::class, 'destroy'])->name('delete.shipping.address');
    });

    Route::prefix('/payment-method')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])->name('view.payment.methods');
        Route::post('/', [PaymentMethodController::class, 'store'])->name('create.payment.method');
        Route::put('/', [PaymentMethodController::class, 'update'])->name('update.payment.method');
        Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('delete.payment.method');
    });

    Route::prefix('/order')->group(function () {
        Route::get('/', [OrderController::class, 'indexPaginated'])->name('view.orders');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show.order');
        Route::get('/user/{userId}', [OrderController::class, 'getOrdersByUser'])->name('view.user.orders');
        Route::post('/create', [OrderController::class, 'createOrder'])->name('create.order');
        Route::post('/place', [OrderController::class, 'placeOrder'])->name('place.order');
    });

    Route::prefix('/order-status')->group(function () {
        Route::get('/', [OrderStatusController::class, 'index'])->name('view.order.statuses');
        Route::post('/', [OrderStatusController::class, 'store'])->name('create.order.status');
        Route::put('/', [OrderStatusController::class, 'update'])->name('update.order.status');
        Route::delete('/{orderStatus}', [OrderStatusController::class, 'destroy'])->name('delete.order.status');
    });

    Route::prefix('/order-status-history')->group(function () {
        Route::get('/order/{orderId}', [OrderStatusHistoryController::class, 'indexByOrder'])->name('view.order.status.histories');
        Route::post('/', [OrderStatusHistoryController::class, 'store'])->name('create.order.status.history');
        Route::put('/', [OrderStatusHistoryController::class, 'update'])->name('update.order.status.history');
        Route::delete('/{orderStatusHistory}', [OrderStatusHistoryController::class, 'destroy'])->name('delete.order.status.history');
    });

    Route::prefix('/role')->group(function () {
        Route::get('/', [RoleController::class, 'getAllRoles'])->name('view.roles');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show.role');
        Route::post('/', [RoleController::class, 'store'])->name('create.role');
        Route::put('/', [RoleController::class, 'update'])->name('update.role');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('delete.role');
    });

    Route::prefix('/permission')->group(function () {
        Route::get('/', [PermissionController::class, 'indexPaginated'])->name('view.permissions');
        Route::get('/id/{id}', [PermissionController::class, 'showById'])->name('show.permission.by.id');
        Route::get('/name', [PermissionController::class, 'showByName'])->name('show.permission.by.name');
        Route::post('/', [PermissionController::class, 'store'])->name('create.permission');
        Route::put('/', [PermissionController::class, 'update'])->name('update.permission');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('delete.permission');
    });

    Route::prefix('/account')->group(function () {
        Route::get('/', [AccountController::class, 'indexPaginated'])->name('view.accounts');
        Route::get('/{user}', [AccountController::class, 'show'])->name('show.account');
        Route::post('/', [AccountController::class, 'store'])->name('create.account');
        Route::put('/toggle-status/{user}', [AccountController::class, 'toggleActiveStatus'])->name('toggle.account.status');
        Route::put('/', [AccountController::class, 'update'])->name('update.account');
        Route::delete('/{user}', [AccountController::class, 'destroy'])->name('delete.account');
    });

    Route::prefix('/dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('view.dashboard.stats');
        Route::get('/count', [DashboardController::class, 'getBasicCounts'])->name('view.dashboard.counts');
        Route::get('/monthly-revenue', [DashboardController::class, 'getMonthlyRevenue'])->name('view.dashboard.monthly.revenue');
        Route::get('/top-category', [DashboardController::class, 'getTopCategoriesByYear'])->name('view.dashboard.top.categories');
        Route::get('/top-book', [DashboardController::class, 'getTopBooksByYear'])->name('view.dashboard.top.books');
    });
});
