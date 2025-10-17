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
use App\Http\Controllers\UploadController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\InventoryHistoryController;


// ---------------------
// Public routes 
// ---------------------
Route::prefix('/v1')->group(function () {

    Route::prefix('/auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::get('/refresh', [AuthController::class, 'refreshToken']);
        Route::post('/send-code', [ActivationController::class, 'sendActivationCode']);
        Route::post('/verify-code', [ActivationController::class, 'verifyActivationCode']);
        Route::post('/forgot-password', [ActivationController::class, 'forgotPassword']);
        Route::post('/reset-password', [ActivationController::class, 'resetPassword']);
    });

    Route::prefix('/book')->group(function () {
        Route::get('/booksNoPagination', [BookController::class, 'getAllBooks'])->name('get.all.books.no.pagination');
        Route::get('/', [BookController::class, 'indexPaginated']);
        Route::get('/books', [BookController::class, 'indexPaginatedForWeb']);
        Route::get('/search/{search}', [BookController::class, 'search']);
        Route::get('/popular', [BookController::class, 'getPopularBooks']);
        Route::get('/random', [BookController::class, 'getRandomBooks']);
        Route::get('/sale-off', [BookController::class, 'getBookSaleOff']);
        Route::get('/banner', [BookController::class, 'getBookBanner']);
        Route::get('/top-selling', [BookController::class, 'getTop3BestSellingBooksByYear']);
        Route::get('/{book}', [BookController::class, 'show']);
        Route::get('/web/{book}', [BookController::class, 'showForWeb']);
        Route::get('/category/{category_id}', [BookController::class, 'getBooksByCategory']);
        Route::get('/feature/{book_id}', [BookFeatureController::class, 'index'])->name('view.book.features');
        Route::get('/images/{book_id}', [BookImageController::class, 'getBookImages'])->name('view.book.images');
    });

    Route::prefix('/categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/page', [CategoryController::class, 'indexPaginated']);
    });

    Route::prefix('/promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'indexPaginated']);
        Route::get('/{promotion}', [PromotionController::class, 'show']);
    });

    Route::get('/inventory-histories', [InventoryHistoryController::class, 'index']);
    Route::get('/inventory-histories/stats', [InventoryHistoryController::class, 'stats']);
});

// ---------------------
// Protected routes 
// ---------------------
Route::prefix('/v1')->middleware(['jwt.auth', 'check.permission', 'active'])->group(function () {

    Route::prefix('/auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('get.info');
        Route::get('/account', [AuthController::class, 'account'])->name('get.account');
        Route::put('/edit-profile', [AuthController::class, 'updateProfile'])->name('edit.profile');
        Route::put('/change-password', [AuthController::class, 'changePassword'])->name('change.password');
    });

    Route::prefix('/upload')->group(function () {
        Route::post('/avatar', [UploadController::class, 'uploadAvatar'])->name('upload.avatar');
        Route::post('/book-image', [UploadController::class, 'uploadImageBook'])->name('upload.book.image');
        Route::post('/thumbnail', [UploadController::class, 'uploadThumbnailBook'])->name('upload.thumbnail.book');
        Route::post('/logo-payment', [UploadController::class, 'uploadLogoPaymentMethod'])->name('upload.logo.payment.method');
    });

    Route::prefix('/customers')->group(function () {
        Route::get('/', [CustomerController::class, 'indexPaginated'])->name('view.customers');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show.customer');
        Route::post('/', [CustomerController::class, 'store'])->name('create.customer');
        Route::put('/', [CustomerController::class, 'update'])->name('update.customer');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('delete.customer');
    });

    Route::prefix('/employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'indexPaginated'])->name('view.employees');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show.employee');
        Route::post('/', [EmployeeController::class, 'store'])->name('create.employee');
        Route::put('/', [EmployeeController::class, 'update'])->name('update.employee');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('delete.employee');
    });

    Route::prefix('/cart')->group(function () {
        Route::get('/my-cart', [CartController::class, 'getMyCart'])->name('view.my.cart');
        Route::get('/{customer_id}', [CartController::class, 'getCartItemsByCustomer'])->name('view.cart.items');
        Route::put('/item/toggle-is-select', [CartController::class, 'toggleIsSelect'])->name('toggle.cart.item.is.select');
        Route::post('/', [CartController::class, 'store'])->name('create.cart');
        Route::post('/add', [CartController::class, 'addItemCart'])->name('add.cart.item');
        Route::post('/web/add', [CartController::class, 'addItemCartForWeb'])->name('add.cart.item.for.web');
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

    Route::prefix('/categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->name('create.category');
        Route::put('/', [CategoryController::class, 'update'])->name('update.category');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('delete.category');
    });

    Route::prefix('/suppliers')->group(function () {
        Route::get('/pagination', [SupplierController::class, 'indexPaginated'])->name('view.suppliers');
        Route::get('/', [SupplierController::class, 'index'])->name('view.suppliers.no.pagination');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show.supplier');
        Route::get('/{id}/books', [SupplierController::class, 'getBooksBySupplier'])->name('show.supplier.books');
        Route::get('/{id}/supplies', [SupplierController::class, 'getSuppliesBySupplier'])->name('show.supplier.supplies');
        Route::post('/', [SupplierController::class, 'store'])->name('create.supplier');
        Route::put('/', [SupplierController::class, 'update'])->name('update.supplier');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('delete.supplier');
    });

    Route::prefix('/supply')->group(function () {
        Route::get('/', [SupplyController::class, 'indexPaginated'])->name('view.supplies');
        Route::post('/fetch-supply', [SupplyController::class, 'getByBookAndSupplier'])->name('show.supply');
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
        Route::get('/customer', [ShippingAddressController::class, 'getAddressesByCustomer'])->name('view.customer.addresses');
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
        Route::get('/downloadPdf/{id}', [OrderController::class, 'downloadOrderPdf'])->name('download.order.pdf');
        Route::get('/user', [OrderController::class, 'getOrdersByUser'])->name('view.user.orders');
        Route::post('/create', [OrderController::class, 'createOrderFromWebPayload'])->name('create.order');
        Route::post('/create/web', [OrderController::class, 'createOrderFromWebPayload'])->name('create.order.for.web');
        Route::get('/history/{userId}', [OrderController::class, 'getOrdersByUserForWeb'])->name('view.orders.history');
        Route::post('/create', [OrderController::class, 'createOrder'])->name('create.order');
        Route::post('/place', [OrderController::class, 'placeOrder'])->name('place.order');
        Route::post('/place/web', [OrderController::class, 'placeOrderForWeb'])->name('place.order.for.web');
        Route::post('/return/{id}', [OrderController::class, 'returnOrder'])->name('return.order');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show.order');
        Route::put('/', [OrderController::class, 'updateOrder'])->name('update.order');
    });


    Route::prefix('/shippingStatus')->group(function () {
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
        Route::get('/no-pagination', [RoleController::class, 'index'])->name('view.roles.no.pagination');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show.role');
        Route::post('/', [RoleController::class, 'store'])->name('create.role');
        Route::put('/', [RoleController::class, 'update'])->name('update.role');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('delete.role');
    });

    Route::prefix('/permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'indexPaginated'])->name('view.permissions');
        Route::get('/permissions-no-pagination', [PermissionController::class, 'index'])->name('view.permissions.no.pagination');
        Route::get('/permissions-name', [PermissionController::class, 'showByName'])->name('show.permission.by.name');
        Route::get('/{id}', [PermissionController::class, 'showById'])->name('show.permission.by.id');
        Route::post('/', [PermissionController::class, 'store'])->name('create.permission');
        Route::put('/', [PermissionController::class, 'update'])->name('update.permission');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('delete.permission');
    });

    Route::prefix('/promotions')->group(function () {
        Route::post('/', [PromotionController::class, 'store'])->name('create.promotion');
        Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update.promotion');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('delete.promotion');
    });

    Route::prefix('/accounts')->group(function () {
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
        Route::get('/top-category', [DashboardController::class, 'getTopCategoriesByYear'])->name('view.dashboard.top.categories');
        Route::get('/top-book', [DashboardController::class, 'getTopBooksByYear'])->name('view.dashboard.top.books');
        Route::get('/monthly-revenue', [DashboardController::class, 'getMonthlyRevenue'])->name('view.dashboard.monthly.revenue');
        Route::get('/view9', [DashboardController::class, 'view9'])->name('view.dashboard.9');
        Route::get('/view6', [DashboardController::class, 'view6'])->name('view.dashboard.6');
        Route::get('/view1', [DashboardController::class, 'view1'])->name('view.dashboard.1');
        Route::get('/get-top5-books-sold', [DashboardController::class, 'getTop5BookSold'])->name('view.dashboard.top.5.books.sold');
    });
});
