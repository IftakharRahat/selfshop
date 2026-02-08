<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebviewController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\TikitController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\ProductrequestController;
use App\Http\Controllers\FraudController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WithdrewController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SslCommerzPaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// web view
Route::get('support', [WebviewController::class, 'support']);
Route::get('faq', [WebviewController::class, 'faq']);
Route::get('see-all-products', [WebviewController::class, 'seeallproducts']);
Route::get('dataall-load-ajax', [WebviewController::class, 'allajaxproduct']);
//all
Route::get('datafooter-load-ajax', [WebviewController::class, 'loadfooter']);
Route::get('datacategory-load-ajax', [WebviewController::class, 'categoryinfoajax']);
Route::get('datasubcategory-load-ajax', [WebviewController::class, 'subcategoryinfoajax']);
Route::get('dataminicategory-load-ajax', [WebviewController::class, 'minicategoryinfoajax']);
Route::get('datarelated-load-ajax', [WebviewController::class, 'relatedinfoajax']);

Route::get('about-us', [WebviewController::class, 'aboutus']);
Route::get('contact-us', [WebviewController::class, 'contactus']);
Route::get('venture/{slug}', [WebviewController::class, 'index']);
Route::get('menu/{slug}', [WebviewController::class, 'menuindex']);
Route::get('product/{slug}', [WebviewController::class, 'productdetails']);
Route::get('product/category/{slug}', [WebviewController::class, 'categoryproduct']);
Route::get('get/products/by-category', [WebviewController::class, 'getcategoryproduct']);
Route::get('get/products/by-subcategory', [WebviewController::class, 'getsubcategoryproduct']);
Route::get('products/sub/category/{slug}', [WebviewController::class, 'subcategoryproduct']);
Route::get('products/mini/category/{slug}', [WebviewController::class, 'minicategoryproduct']);

Route::get('get/slug/products', [WebviewController::class, 'getslugproduct']);
Route::get('view/categories', [WebviewController::class, 'allcategories']);
// cart
Route::post('add-to-cart', [CartController::class, 'addtocart']);
Route::get('view-cart', [CartController::class, 'viewcart']);
Route::post('add-cart', [CartController::class, 'addcart']);
Route::post('/buy-now', [OrderController::class, 'orderstore']);
Route::get('/productview/{id}', [ProductController::class, 'edit']);
Route::get('/product/productview/{id}', [ProductController::class, 'edit']);
Route::get('/product/category/productview/{id}', [ProductController::class, 'edit']);
Route::get('order-received', [CartController::class, 'payment'])->name('payment.methood');
Route::get('/empty-cart', [CartController::class, 'emptycart']);

Route::post('order-to-cart', [CartController::class, 'ordertocart']);
Route::get('get-cart-content', [CartController::class, 'getcartcontent']);
Route::post('remove-cart', [CartController::class, 'destroy']);
Route::get('update-cart', [CartController::class, 'cartcontent']);
Route::get('get-checkcart-content', [CartController::class, 'getcheckcartcontent']);
Route::get('cart', [CartController::class, 'cart']);
Route::get('order/complete', [CartController::class, 'complete']);
Route::post('/update-cart', [CartController::class, 'updatecart']);
Route::get('load-cart', [CartController::class, 'loadcart']);
Route::post('press/order', [OrderController::class, 'pressorder']);
Route::post('update/paymentmethood', [OrderController::class, 'updatepaymentmethood']);
Route::get('get-search-content', [WebviewController::class, 'searchcontent']);
Route::get('track-order', [WebviewController::class, 'orderTraking']);
Route::get('track-now', [WebviewController::class, 'orderTrakingNow']);
Route::get('load-tracking', [WebviewController::class, 'orderTrakingLoad']);
Route::get('view/category/all', [WebviewController::class, 'categoryall']);
Route::get('download/image/{slug}', [WebviewController::class, 'downloadimage']);
Route::get('download/image-single/{slug}', [WebviewController::class, 'downloadimagesingle']);
Route::get('add/to-shop/{id}', [WebviewController::class, 'addtoshop']);
Route::get('remove/from-shop/{id}', [WebviewController::class, 'removefromshop']);



// new bkash
Route::get('/bkash/create-payment', [App\Http\Controllers\BkashTokenizePaymentController::class, 'createPayment'])->name('bkash-create-payment');
Route::get('/bkash/callback', [App\Http\Controllers\BkashTokenizePaymentController::class, 'callBack'])->name('bkash-callBack');

//search payment
Route::get('/bkash/search/{trxID}', [App\Http\Controllers\BkashTokenizePaymentController::class, 'searchTnx'])->name('bkash-serach');

// inv bkash
Route::get('/invbkash/create-payment', [App\Http\Controllers\BkashTokenizePaymentController::class, 'invcreatePayment'])->name('invbkash-create-payment');
Route::get('/invbkash/callback', [App\Http\Controllers\BkashTokenizePaymentController::class, 'invcallBack'])->name('invbkash-callBack');

//search payment
Route::get('/invbkash/search/{trxID}', [App\Http\Controllers\BkashTokenizePaymentController::class, 'invsearchTnx'])->name('invbkash-serach');




Route::get('/', function () {
    return view('frontend.content.maincontent');
});

Route::group(['middleware' => ['auth:web']], function () {
    Route::get('our-packages', [WebviewController::class, 'packages']);
    Route::post('purchese-package', [WebviewController::class, 'purchese']);
    Route::get('view-packages', [WebviewController::class, 'packagelist'])->name('admin.loadmenu');
    Route::group(['middleware' => ['resellact']], function () {
        Route::get('load-product/{slug}', [WebviewController::class, 'loadproduct']);
        Route::get('referral/income', [MessageController::class, 'referral']);
        Route::get('order/income-history', [MessageController::class, 'incomehistory']);
        Route::get('user/free/course', [WebviewController::class, 'course']);
        Route::get('user/course-details/{slug}', [WebviewController::class, 'coursedetails']);
        Route::get('user/coursedetails/{id}', [WebviewController::class, 'coursedetailsid']);
Route::get('checkout', [CartController::class, 'checkout'])->name('checkout');

        Route::get('shop/{slug}', [WebviewController::class, 'myshop']);
        Route::post('user/add-bank', [BankController::class, 'store']);
        Route::post('user/update-bank', [BankController::class, 'update']);
        Route::get('user/teams', [WebviewController::class, 'teams']);
        Route::get('user/profile', [WebviewController::class, 'profile']);
        Route::post('user/update/profile', [WebviewController::class, 'updateprofile']);
        Route::get('user/purchase_history', [WebviewController::class, 'orderhistory']);
        Route::get('balance/transfer', [WebviewController::class, 'balancetransfer']);
        Route::post('transfer/now', [WebviewController::class, 'transfernow']);
        Route::get('check/user', [WebviewController::class, 'checkuser']);
        Route::get('developers-api', [WebviewController::class, 'developersapi']);
        Route::get('generate-developers-api', [WebviewController::class, 'generatedevelopersapi']);

        Route::group(['prefix' => 'user'], function () {
            Route::resource('settings', SettingController::class);
            Route::resource('supporttikits', TikitController::class);
            Route::resource('productrequests', ProductrequestController::class);
            Route::resource('frauds', FraudController::class);
            Route::get('check-fraud/{slug}', [FraudController::class, 'checkfraud']);
            Route::get('orders', [WebviewController::class, 'orders']);
            Route::get('order/{slug}', [WebviewController::class, 'slugorder']);
            Route::resource('withdrews', WithdrewController::class);
            Route::get('withdrews', [WebviewController::class, 'withdrews']);
            Route::get('income/history/{slug}', [WebviewController::class, 'incomehistory']);
            Route::get('referral-incomes', [WebviewController::class, 'referralincome']);
        });
    });
});
Route::get('user/dashboard', function () {
    return view('dashboard');
})->middleware(['auth:web', 'resellact'])->name('dashboard');

// SSLCOMMERZ Start
Route::get('/example1', [SslCommerzPaymentController::class, 'exampleEasyCheckout']);
Route::get('/example2', [SslCommerzPaymentController::class, 'exampleHostedCheckout']);

Route::post('/pay', [SslCommerzPaymentController::class, 'index']);
Route::post('/pay-via-ajax', [SslCommerzPaymentController::class, 'payViaAjax']);

// SSLCommerz Routes for Hosted External Link
Route::post('/sslcommerz/payment', [SslCommerzPaymentController::class, 'initiatePayment'])->name('sslcommerz.payment');
Route::post('/success', [SslCommerzPaymentController::class, 'success']);
Route::post('/sslcommerz/fail', [SslCommerzPaymentController::class, 'fail'])->name('sslcommerz.fail');
Route::post('/sslcommerz/cancel', [SslCommerzPaymentController::class, 'cancel'])->name('sslcommerz.cancel');
Route::post('/sslcommerz/ipn', [SslCommerzPaymentController::class, 'ipn'])->name('sslcommerz.ipn');
//SSLCOMMERZ END
# Add these routes
Route::get('/payment/success-page', [SslCommerzPaymentController::class, 'paymentSuccessPage'])->name('payment.success.page');
Route::get('/checkout/payment-success', [SslCommerzPaymentController::class, 'paymentSuccessPage'])->name('checkout.success');


Route::post('/sslcommerz/package/payment', [SslCommerzPaymentController::class, 'initiatePackagePayment'])->name('sslcommerz.package.payment');
Route::match(['get', 'post'], '/sslcommerz/package/success', [SslCommerzPaymentController::class, 'packagePaymentSuccess'])->name('sslcommerz.package.success');
Route::match(['get', 'post'], '/sslcommerz/package/fail', [SslCommerzPaymentController::class, 'packagePaymentFail'])->name('sslcommerz.package.fail');
Route::match(['get', 'post'], '/sslcommerz/package/cancel', [SslCommerzPaymentController::class, 'packagePaymentCancel'])->name('sslcommerz.package.cancel');
Route::post('/sslcommerz/package/ipn', [SslCommerzPaymentController::class, 'packagePaymentIPN'])->name('sslcommerz.package.ipn');

// Also add a simpler route for the form action
Route::post('/package/payment', [SslCommerzPaymentController::class, 'initiatePackagePayment'])->name('package.payment');

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
Route::get('{slug}/products', [WebviewController::class, 'slugProduct'])->middleware(['resellact']);
