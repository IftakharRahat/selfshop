<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FrontendApiController;
use App\Http\Controllers\VendorApiController;
use App\Http\Controllers\VendorAccountController;
use App\Http\Controllers\VendorAuthController;
use App\Http\Controllers\VendorProductController;
use App\Http\Controllers\VendorOrderController;
use App\Http\Controllers\VendorCategoryDiscountController;
use App\Http\Controllers\VendorReviewController;
use App\Http\Controllers\VendorStockController;
use App\Http\Controllers\VendorWarehouseController;
use App\Http\Controllers\VendorShippingMethodController;
use App\Http\Controllers\VendorEarningsController;
use App\Http\Controllers\VendorPayoutAccountController;
use App\Http\Controllers\VendorPayoutRequestController;
use App\Http\Controllers\VendorReportsController;
use App\Http\Controllers\VendorDashboardController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/basic-info', [FrontendApiController::class, 'basicInfo'])->name('api.user.basic-info');
    Route::get('/categories', [FrontendApiController::class, 'categoryData'])->name('api.user.category-data');
    Route::get('/menus', [FrontendApiController::class, 'menusData'])->name('api.user.menusData');
    Route::get('/sliders', [FrontendApiController::class, 'sliderData'])->name('api.user.slider-info');
    Route::get('/header-categories', [FrontendApiController::class, 'headercategories'])->name('api.user.headercategories');
    // need to change live server db
    Route::get('/slider-bottom-banners', [FrontendApiController::class, 'bottombanners'])->name('api.user.bottombanners');
    Route::get('/brands', [FrontendApiController::class, 'brands'])->name('api.user.brands');
    Route::get('/collection/{slug}', [FrontendApiController::class, 'collection'])->name('api.user.collection');
    Route::get('/new-arrivels', [FrontendApiController::class, 'newarrivels'])->name('api.user.newarrivels');
    Route::get('/new-products', [FrontendApiController::class, 'newproducts'])->name('api.user.newarrivels');
    Route::get('/featured-products', [FrontendApiController::class, 'featuredproducts'])->name('api.user.featuredproducts');
    Route::get('/category-products', [FrontendApiController::class, 'categoryproducts'])->name('api.user.categoryproducts');
    Route::get('/big-selling', [FrontendApiController::class, 'bigselling'])->name('api.user.bigselling');
    Route::get('/products/{slug}', [FrontendApiController::class, 'productbycategory'])->name('api.user.productbycategory');
    Route::get('/subcategory-products/{slug?}', [FrontendApiController::class, 'productbysubcategory'])->name('api.user.productbysubcategory');
    Route::get('/brand-products/{slug}', [FrontendApiController::class, 'productbybrand'])->name('api.user.productbybrand');
    Route::get('/search', [FrontendApiController::class, 'search'])->name('api.user.search');
    Route::get('/product-details/{slug}', [FrontendApiController::class, 'productdetails'])->name('api.user.productdetails');

    Route::post('/register', [FrontendApiController::class, 'userRegister'])->name('api.user.register');
    Route::post('/login', [FrontendApiController::class, 'userLogin'])->name('api.user.login');
    // Vendor registration (separate vendor portal)
    Route::post('/vendor/register', [VendorAuthController::class, 'register'])->name('api.vendor.register');
    Route::post('/reset-password', [FrontendApiController::class, 'userResetPassword'])->name('api.user.reset-password');

    //Shops
    Route::get('/shops', [FrontendApiController::class, 'shopPage'])->name('api.shop');
    Route::get('/products/shops/{slug}', [FrontendApiController::class, 'shopProduct'])->name('api.shopProducts');

    // Cart Operations
    Route::post('/guest-add-to-cart', [FrontendApiController::class, 'guestAddToCart'])->name('api.guest-add-to-cart.store');
    Route::post('/guest-update-cart', [FrontendApiController::class, 'guestUpdateCart'])->name('api.guest-update-cart');
    Route::post('/guest-destroy-cart', [FrontendApiController::class, 'guestDestroyCart'])->name('api.guest-destroy-cart');
    Route::post('/guest-cart-content', [FrontendApiController::class, 'guestCartContent'])->name('api.guest-cart-content');
});


Route::middleware('auth:sanctum')->group(function () {

    Route::get('our-packages', [FrontendApiController::class, 'packages']);
    Route::post('purchese-package', [FrontendApiController::class, 'purchesepackage']);
    Route::get('/invbkash/create-payment', [App\Http\Controllers\BkashTokenizePaymentController::class, 'invcreatePayment'])->name('invbkash-create-payment');

    Route::get('dashboard-data', [FrontendApiController::class, 'dashboarddata']);
    Route::post('logout', [FrontendApiController::class, 'userLogout'])->name('api.user.logout');
    Route::post('/confirm-password', [FrontendApiController::class, 'userConfirmPassword'])->name('api.user.confirm-password');
    //Profile
    Route::get('/user-profile', [FrontendApiController::class, 'userProfile'])->name('api.user.profile');
    Route::post('/update-profile', [FrontendApiController::class, 'updateprofile'])->name('api.update.profile');
    // sidebar
    Route::get('developers-api', [FrontendApiController::class, 'developersapi']);
    Route::get('generate-developers-api', [FrontendApiController::class, 'generatedevelopersapi']);
    Route::get('faqs', [FrontendApiController::class, 'faqs']);
    Route::get('track-order', [FrontendApiController::class, 'trackorder']);
    Route::post('update-bank-info', [FrontendApiController::class, 'bankinfo']);
    // supportticket
    Route::get('get-supporttickets', [FrontendApiController::class, 'supportticket']);
    Route::post('create-supportticket', [FrontendApiController::class, 'createticket']);
    Route::get('view-tikit/{id}', [FrontendApiController::class, 'viewticket']);
    Route::post('replay-tikit/{id}', [FrontendApiController::class, 'replayticket']);
    // fraudlist
    Route::post('store-fraud-number', [FrontendApiController::class, 'storefraud']);
    Route::get('check-fraud', [FrontendApiController::class, 'checkfraud']);
    // course
    Route::get('view-course', [FrontendApiController::class, 'course']);
    Route::get('course-details/{slug}', [FrontendApiController::class, 'coursedetails']);

    // withdraw
    Route::get('get-payment-types', [FrontendApiController::class, 'paymenttypes']);
    Route::post('give-withdraw-request', [FrontendApiController::class, 'withdrawrequest']);
    Route::get('withdraw-list', [FrontendApiController::class, 'withdrawlist']);

    // blancetransfer
    Route::get('balance-transferlists', [FrontendApiController::class, 'transferlists']);
    Route::post('give-transfer-request', [FrontendApiController::class, 'transfernow']);

    // order income
    Route::get('income-history', [FrontendApiController::class, 'incomehistory']);
    Route::get('referral/data', [FrontendApiController::class, 'referral']);

    // order
    Route::get('order-data/{slug}', [FrontendApiController::class, 'orders']);
    Route::get('order-count', [FrontendApiController::class, 'ordercount']);

    // add to shop
    Route::get('shop-products', [FrontendApiController::class, 'shopproducts']);
    Route::get('add-to-shop/{id}', [FrontendApiController::class, 'addtoshop']);
    Route::get('remove-from-shop/{id}', [FrontendApiController::class, 'removefromshop']);

    // others
    Route::get('teams', [FrontendApiController::class, 'teams']);
    Route::get('request-product-list', [FrontendApiController::class, 'productlist']);
    Route::post('give-product-request', [FrontendApiController::class, 'productrequest']);

    // gurveg bellow

    Route::get('/user-order-history', [FrontendApiController::class, 'userOrderHistory'])->name('api.user.order-history');

    // Route::get('/user-wallets',[FrontendApiController::class,'userProfile'])->name('api.user.profile');

    //Review store
    Route::post('/review/store', [FrontendApiController::class, 'reviewStore'])->name('api.review.store');

    //Cart
    Route::post('/user-add-to-cart', [FrontendApiController::class, 'userAddToCart'])->name('api.user-add-to-cart.store');
    Route::post('/user-update-cart', [FrontendApiController::class, 'userUpdateCart'])->name('api.user-update-cart');
    Route::post('/user-destroy-cart', [FrontendApiController::class, 'userDestroyCart'])->name('api.user-destroy-cart');
    Route::get('/user-cart-content', [FrontendApiController::class, 'userCartContent'])->name('api.guest-cart-content');
    Route::get('/view-bulk-price', [FrontendApiController::class, 'viewbulkprice'])->name('api.guest-cart-content');

    //Dashboards
    Route::get('/dashboard/all-counts', [FrontendApiController::class, 'allCount'])->name('api.user.dashboard');

    //Notification
    Route::get('/user-notification', [FrontendApiController::class, 'userNotification'])->name('api.user.notification');

    //Coupons
    Route::get('check-coupon', [FrontendApiController::class, 'couponCheck']);
    Route::get('reset-coupon', [FrontendApiController::class, 'resetCoupon']);

    //Order
    Route::post('/order-now', [FrontendApiController::class, 'orderNow'])->name('api.orderNow');
    Route::get('order-by-invoice/{slug}', [FrontendApiController::class, 'orderByinvoice'])->name('api.order-tracking-now');

    //order Tracking
    Route::post('track-now', [FrontendApiController::class, 'orderTrackingNow'])->name('api.order-tracking-now');

    Route::post('/add-to-wishlist', [FrontendApiController::class, 'storeWishlist'])->name('api.store.wishlist');
    Route::get('/get-wishlist', [FrontendApiController::class, 'getWishlist'])->name('api.get.wishlist');
    Route::post('/remove-wishlist', [FrontendApiController::class, 'removeWishlist'])->name('api.destroy.wishlist');
    Route::post('/clear-wishlist', [FrontendApiController::class, 'clearWishlist'])->name('api.clear.wishlist');

    // Vendor (Wholesale / Supplier) – vendor portal APIs
    Route::prefix('vendor')->group(function () {
        // Dashboard
        Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('api.vendor.dashboard');

        // Account & profile
        Route::get('/profile', [VendorAccountController::class, 'profile'])->name('api.vendor.profile');
        Route::post('/profile', [VendorAccountController::class, 'upsertProfile'])->name('api.vendor.profile.upsert');

        // KYC documents
        Route::get('/kyc-documents', [VendorAccountController::class, 'kycDocuments'])->name('api.vendor.kyc.index');
        Route::post('/kyc-documents', [VendorAccountController::class, 'storeKycDocument'])->name('api.vendor.kyc.store');

        // Vendor products (CRUD)
        Route::get('/products', [VendorProductController::class, 'index'])->name('api.vendor.products.index');
        Route::post('/products', [VendorProductController::class, 'store'])->name('api.vendor.products.store');
        Route::get('/products/bulk-template', [VendorProductController::class, 'bulkTemplate'])->name('api.vendor.products.bulk-template');
        Route::post('/products/bulk-upload', [VendorProductController::class, 'bulkUpload'])->name('api.vendor.products.bulk-upload');
        Route::get('/products/{id}', [VendorProductController::class, 'show'])->name('api.vendor.products.show');
        Route::put('/products/{id}', [VendorProductController::class, 'update'])->name('api.vendor.products.update');
        Route::post('/products/{id}', [VendorProductController::class, 'update'])->name('api.vendor.products.update.post'); // POST for file uploads (PHP does not populate $_FILES for PUT)
        Route::put('/products/{id}/status', [VendorProductController::class, 'updateStatus'])->name('api.vendor.products.status');
        Route::put('/products/{id}/featured', [VendorProductController::class, 'updateFeatured'])->name('api.vendor.products.featured');
        Route::get('/products/{id}/variants', [VendorProductController::class, 'variants'])->name('api.vendor.products.variants');
        Route::post('/products/{id}/variants', [VendorProductController::class, 'storeVariant'])->name('api.vendor.products.variants.store');
        Route::put('/products/{id}/variants/{variantId}', [VendorProductController::class, 'updateVariant'])->name('api.vendor.products.variants.update');
        Route::delete('/products/{id}/variants/{variantId}', [VendorProductController::class, 'destroyVariant'])->name('api.vendor.products.variants.destroy');
        Route::get('/products/{id}/price-tiers', [VendorProductController::class, 'priceTiers'])->name('api.vendor.products.price-tiers');
        Route::post('/products/{id}/price-tiers', [VendorProductController::class, 'storePriceTier'])->name('api.vendor.products.price-tiers.store');
        Route::put('/products/{id}/price-tiers/{tierId}', [VendorProductController::class, 'updatePriceTier'])->name('api.vendor.products.price-tiers.update');
        Route::delete('/products/{id}/price-tiers/{tierId}', [VendorProductController::class, 'destroyPriceTier'])->name('api.vendor.products.price-tiers.destroy');
        Route::delete('/products/{id}', [VendorProductController::class, 'destroy'])->name('api.vendor.products.destroy');

        // Orders (Phase 4 – Order Management)
        Route::get('/orders', [VendorOrderController::class, 'index'])->name('api.vendor.orders.index');
        Route::get('/orders/{id}', [VendorOrderController::class, 'show'])->name('api.vendor.orders.show');
        Route::post('/orders/{id}/tracking', [VendorOrderController::class, 'addTracking'])->name('api.vendor.orders.tracking');

        // Shipping methods (Phase 5)
        Route::get('/shipping-methods', [VendorShippingMethodController::class, 'index'])->name('api.vendor.shipping-methods.index');
        Route::post('/shipping-methods', [VendorShippingMethodController::class, 'store'])->name('api.vendor.shipping-methods.store');
        Route::put('/shipping-methods/{id}', [VendorShippingMethodController::class, 'update'])->name('api.vendor.shipping-methods.update');
        Route::delete('/shipping-methods/{id}', [VendorShippingMethodController::class, 'destroy'])->name('api.vendor.shipping-methods.destroy');

        // Category-wise discount
        Route::get('/category-discounts', [VendorCategoryDiscountController::class, 'index'])->name('api.vendor.category-discounts.index');
        Route::post('/category-discounts/{categoryId}', [VendorCategoryDiscountController::class, 'set'])->name('api.vendor.category-discounts.set');

        // Product reviews (vendor view)
        Route::get('/reviews', [VendorReviewController::class, 'index'])->name('api.vendor.reviews.index');
        Route::get('/reviews/{productId}', [VendorReviewController::class, 'show'])->name('api.vendor.reviews.show');

        // Inventory & Stock (Phase 3)
        Route::get('/inventory', [VendorStockController::class, 'index'])->name('api.vendor.inventory.index');
        Route::get('/inventory/alerts', [VendorStockController::class, 'alerts'])->name('api.vendor.inventory.alerts');
        Route::get('/inventory/export', [VendorStockController::class, 'export'])->name('api.vendor.inventory.export');
        Route::get('/inventory/{productId}', [VendorStockController::class, 'show'])->name('api.vendor.inventory.show');
        Route::post('/inventory/{productId}/adjust', [VendorStockController::class, 'adjust'])->name('api.vendor.inventory.adjust');
        Route::post('/inventory/{productId}/update-threshold', [VendorStockController::class, 'updateThreshold'])->name('api.vendor.inventory.threshold');
        Route::get('/inventory/{productId}/warehouses', [VendorStockController::class, 'warehouseStock'])->name('api.vendor.inventory.warehouses');
        Route::post('/inventory/{productId}/allocate', [VendorStockController::class, 'allocate'])->name('api.vendor.inventory.allocate');
        Route::post('/inventory/dropship-sync', [VendorStockController::class, 'syncDropshipStock'])->name('api.vendor.inventory.dropship-sync');

        // Warehouses (Phase 3)
        Route::get('/warehouses', [VendorWarehouseController::class, 'index'])->name('api.vendor.warehouses.index');
        Route::post('/warehouses', [VendorWarehouseController::class, 'store'])->name('api.vendor.warehouses.store');
        Route::put('/warehouses/{id}', [VendorWarehouseController::class, 'update'])->name('api.vendor.warehouses.update');
        Route::delete('/warehouses/{id}', [VendorWarehouseController::class, 'destroy'])->name('api.vendor.warehouses.destroy');

        // Earnings & Payouts (Phase 6)
        Route::get('/earnings/summary', [VendorEarningsController::class, 'summary'])->name('api.vendor.earnings.summary');
        Route::get('/earnings', [VendorEarningsController::class, 'index'])->name('api.vendor.earnings.index');
        Route::get('/payout-accounts', [VendorPayoutAccountController::class, 'index'])->name('api.vendor.payout-accounts.index');
        Route::post('/payout-accounts', [VendorPayoutAccountController::class, 'store'])->name('api.vendor.payout-accounts.store');
        Route::put('/payout-accounts/{id}', [VendorPayoutAccountController::class, 'update'])->name('api.vendor.payout-accounts.update');
        Route::delete('/payout-accounts/{id}', [VendorPayoutAccountController::class, 'destroy'])->name('api.vendor.payout-accounts.destroy');
        Route::get('/payout-requests', [VendorPayoutRequestController::class, 'index'])->name('api.vendor.payout-requests.index');
        Route::post('/payout-requests', [VendorPayoutRequestController::class, 'store'])->name('api.vendor.payout-requests.store');
        Route::get('/payouts', [VendorPayoutRequestController::class, 'payouts'])->name('api.vendor.payouts.index');

        // Reports (Phase 7)
        Route::get('/reports/sales', [VendorReportsController::class, 'sales'])->name('api.vendor.reports.sales');
        Route::get('/reports/top-products', [VendorReportsController::class, 'topProducts'])->name('api.vendor.reports.top-products');
        Route::get('/reports/sales-breakdown', [VendorReportsController::class, 'salesBreakdown'])->name('api.vendor.reports.sales-breakdown');

        // Bulk order matrix (existing)
        Route::middleware('verified.wholesaler')->group(function () {
            Route::get('/product-details/{slug}', [VendorApiController::class, 'productDetails'])->name('api.vendor.product-details');
            Route::post('/bulk-add-to-cart', [VendorApiController::class, 'bulkAddToCart'])->name('api.vendor.bulk-add-to-cart');
        });
    });
});





Route::get('/products', [OrderController::class, 'getproduct']);

Route::get('/products/mine', [OrderController::class, 'getmyproduct']);
Route::get('/order', [OrderController::class, 'getorder']);