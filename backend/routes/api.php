<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FrontendApiController;
use App\Http\Controllers\VendorApiController;
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

    // Vendor (Wholesale) – for Next.js vendor subdomain / bulk order matrix (verified wholesalers only)
    Route::middleware('verified.wholesaler')->group(function () {
        Route::get('/vendor/product-details/{slug}', [VendorApiController::class, 'productDetails'])->name('api.vendor.product-details');
        Route::post('/vendor/bulk-add-to-cart', [VendorApiController::class, 'bulkAddToCart'])->name('api.vendor.bulk-add-to-cart');
    });
});





Route::get('/products', [OrderController::class, 'getproduct']);

Route::get('/products/mine', [OrderController::class, 'getmyproduct']);
Route::get('/order', [OrderController::class, 'getorder']);