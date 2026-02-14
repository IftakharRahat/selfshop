<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\RolesController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\UserRolesController;
use App\Http\Controllers\Backend\BasicinfoController;
use App\Http\Controllers\Backend\PolicymenuController;
use App\Http\Controllers\Backend\BrandController;
use App\Http\Controllers\Backend\SliderController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\SubcategoryController;
use App\Http\Controllers\Backend\MinicategoryController;
use App\Http\Controllers\Backend\MenuController;
use App\Http\Controllers\Backend\AddbannerController;
use App\Http\Controllers\Backend\AttributeController;
use App\Http\Controllers\Backend\AttrvalueController;
use App\Http\Controllers\Backend\ServicepackageController;
use App\Http\Controllers\Backend\PaymenticonController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\Backend\InformationController;
use App\Http\Controllers\Backend\OrderController;
use App\Http\Controllers\Backend\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ComplainController;
use App\Http\Controllers\ComplanenoteController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymenttypeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\FindorderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TikitController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProductrequestController;
use App\Http\Controllers\FraudController;
use App\Http\Controllers\ResellerinvoiceController;
use App\Http\Controllers\WithdrewController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\CoursecategoryController;
use App\Http\Controllers\Backend\CourseController;
use App\Http\Controllers\VarientController;
use App\Http\Controllers\VencommentController;
use App\Http\Controllers\Backend\VendorController;
use App\Http\Controllers\Backend\AdminReviewController;
use App\Http\Controllers\Backend\AdminVendorCategoryDiscountController;
use App\Http\Controllers\Backend\AdminVendorProductController;
use App\Http\Controllers\Backend\AdminVendorPayoutController;
use App\Http\Controllers\Backend\AdminVendorReportController;

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


Route::group(['prefix' => 'admin',], function () {
    // login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('admin.loginview');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('admin.login');

    // logout
    Route::get('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('admin.logout');
    // reset password

});

Route::group(['prefix' => 'admin', 'middleware' => ['auth.admin:admin']], function () {

    Route::get('accounts', [VencommentController::class, 'accounts']);
    Route::get('withdraws', [VencommentController::class, 'withdraws']);
    Route::post('withdrew-store', [VencommentController::class, 'withdrawstore']);

    Route::get('view-withdraws', [VencommentController::class, 'withdrawview']);
    Route::get('view-withdraws/{slug}', [VencommentController::class, 'withdrawviewslug']);
    Route::get('withdraw-edit/{id}', [VencommentController::class, 'withdrawedit']);
    Route::post('withdraw-update/{id}', [VencommentController::class, 'withdrawupdate']);


    Route::get('/dashboard', [AuthenticatedSessionController::class, 'dashboard']);
    Route::get('profile', [AdminController::class, 'profile']);
    Route::get('profile/{id}', [AdminController::class, 'profilebyid']);
    Route::post('update/profile', [AdminController::class, 'updateprofile']);
    Route::post('update/profile/{id}', [AdminController::class, 'updateprofileby']);

    // role & permission
    Route::resource('roles', RolesController::class, ['names' => 'admin.roles']);
    Route::resource('userroles', UserRolesController::class, ['names' => 'admin.userroles']);
    Route::resource('admins', AdminController::class, ['names' => 'admin.admins']);
    Route::resource('users', UserController::class, ['names' => 'admin.users']);
    Route::get('user/get/data', [UserController::class, 'userdata'])->name('admin.user.data');
    Route::get('activeuser/get/data', [UserController::class, 'activeuserdata'])->name('admin.activeuser.data');

    Route::get('view-active/user', [UserController::class, 'activeuser']);
    Route::get('executive', [AdminController::class, 'hrexe']);
    Route::get('executive/create', [AdminController::class, 'hrexecreate']);
    Route::get('executive/store', [AdminController::class, 'hrexestore']);
    Route::get('executive/update', [AdminController::class, 'hrexeupdate']);

    // supporttickets
    Route::get('supporttikits', [TikitController::class, 'admindex']);
    Route::get('supporttikit/edit/{id}', [TikitController::class, 'edit']);
    Route::post('supporttikit/update/{id}', [TikitController::class, 'update']);
    // pr and frd
    Route::get('product-request/{status}', [ProductrequestController::class, 'admindex']);
    Route::get('product-request/{id}/edit', [ProductrequestController::class, 'edit']);
    Route::get('product-request/data/{status}', [ProductrequestController::class, 'produtrqdata']);
    Route::post('product-request/update/{id}', [ProductrequestController::class, 'update']);
    // fraud
    Route::get('fraud/{status}', [FraudController::class, 'admindex']);
    Route::get('fraud/{id}/edit', [FraudController::class, 'edit']);
    Route::get('fraud/data/{status}', [FraudController::class, 'frauddata']);
    Route::post('fraud/update/{id}', [FraudController::class, 'update']);

    // basic info
    Route::resource('basicinfos', BasicinfoController::class, ['names' => 'admin.basicinfos']);
    Route::post('/pixel/analytics/{id}', [BasicinfoController::class, 'pixelanalytics']);
    Route::post('/basicinfo/update/{id}', [BasicinfoController::class, 'sociallink']);
    Route::put('/shippinginfo/update/{id}', [BasicinfoController::class, 'shippinginfo'])->name('admin.shipping.update');
    //payment icon
    Route::resource('paymenticons', PaymenticonController::class, ['names' => 'admin.paymenticons']);
    Route::get('paymenticon/get/data', [PaymenticonController::class, 'paymenticondata'])->name('admin.paymenticon.data');
    Route::post('paymenticon/{id}', [PaymenticonController::class, 'update']);
    Route::put('paymenticon/status', [PaymenticonController::class, 'statusupdate']);

    //policy menu
    Route::resource('policymenus', PolicymenuController::class, ['names' => 'admin.policymenus']);
    Route::get('policymenu/get/data', [PolicymenuController::class, 'policymenudata'])->name('admin.policymenu.data');
    Route::post('policymenu/{id}', [PolicymenuController::class, 'update']);
    Route::put('policymenu/status', [PolicymenuController::class, 'statusupdate']);

    //header menu
    Route::resource('menus', MenuController::class, ['names' => 'admin.menus']);
    Route::get('menu/get/data', [MenuController::class, 'menudata'])->name('admin.menu.data');
    Route::post('menu/{id}', [MenuController::class, 'update']);
    Route::put('menu/status', [MenuController::class, 'statusupdate']);

    //Sliders
    Route::resource('sliders', SliderController::class, ['names' => 'admin.sliders']);
    Route::get('slider/get/data', [SliderController::class, 'sliderdata'])->name('admin.slider.data');
    Route::post('slider/{id}', [SliderController::class, 'update']);
    Route::put('slider/status', [SliderController::class, 'statusupdate']);

    //add banners
    Route::resource('addbanners', AddbannerController::class, ['names' => 'admin.addbanners']);
    Route::post('addbanner/{id}', [AddbannerController::class, 'update']);
    Route::put('addbanner/status/{id}', [AddbannerController::class, 'statusupdate']);

    //Brands
    Route::resource('brands', BrandController::class, ['names' => 'admin.brands']);
    Route::get('brand/get/data', [BrandController::class, 'branddata'])->name('admin.brand.data');
    Route::post('brand/{id}', [BrandController::class, 'update']);
    Route::put('brand/status', [BrandController::class, 'statusupdate']);

    //Service Package
    Route::resource('servicepackages', ServicepackageController::class, ['names' => 'admin.servicepackages']);
    Route::get('servicepackage/get/data', [ServicepackageController::class, 'servicepackagedata'])->name('admin.servicepackage.data');
    Route::post('servicepackage/{id}', [ServicepackageController::class, 'update']);
    Route::put('servicepackage/status', [ServicepackageController::class, 'statusupdate']);

    //varients
    Route::resource('varients', VarientController::class, ['names' => 'admin.varients']);
    Route::post('varient/{id}', [VarientController::class, 'update']);

    //category
    Route::resource('categorys', CategoryController::class, ['names' => 'admin.categorys']);
    Route::get('category/get/data', [CategoryController::class, 'categorydata'])->name('admin.category.data');
    Route::post('category/{id}', [CategoryController::class, 'update']);
    Route::put('category/status', [CategoryController::class, 'statusupdate']);
    Route::get('/get/subcategory/{id}', [CategoryController::class, 'getsubcategory']);
    Route::get('/get/minicategory/{id}', [CategoryController::class, 'getminicategory']);

    //sub-category
    Route::resource('subcategorys', SubcategoryController::class, ['names' => 'admin.subcategorys']);
    Route::get('subcategory/get/data', [SubcategoryController::class, 'subcategorydata'])->name('admin.subcategory.data');
    Route::post('subcategory/{id}', [SubcategoryController::class, 'update']);
    Route::put('subcategory/status', [SubcategoryController::class, 'statusupdate']);

    //mini-category
    Route::resource('minicategorys', MinicategoryController::class, ['names' => 'admin.minicategorys']);
    Route::get('minicategory/get/data', [MinicategoryController::class, 'minicategorydata'])->name('admin.minicategory.data');
    Route::post('minicategory/{id}', [MinicategoryController::class, 'update']);
    Route::put('minicategory/status', [MinicategoryController::class, 'statusupdate']);


    //Attributes
    Route::resource('attributes', AttributeController::class, ['names' => 'admin.attributes']);
    Route::get('attribute/get/data', [AttributeController::class, 'attributedata'])->name('admin.attribute.data');
    Route::post('attribute/{id}', [AttributeController::class, 'update']);
    Route::put('attribute/status', [AttributeController::class, 'statusupdate']);

    //Attributes Values
    Route::resource('attrvalues', AttrvalueController::class, ['names' => 'admin.attrvalues']);
    Route::get('attrvalue/get/data', [AttrvalueController::class, 'attrvaluedata'])->name('admin.attrvalue.data');
    Route::post('attrvalue/{id}', [AttrvalueController::class, 'update']);
    Route::put('attrvalue/status', [AttrvalueController::class, 'statusupdate']);

    //faqs
    Route::resource('faqs', FaqController::class);
    Route::post('faq/{id}', [FaqController::class, 'update']);
    Route::put('faq/status', [FaqController::class, 'updatestatus']);
    Route::get('faq/data', [FaqController::class, 'faqdata'])->name('faq.data');

    //products
    Route::resource('products', ProductController::class, ['names' => 'admin.products']);
    Route::get('product/get/data', [ProductController::class, 'productdata'])->name('admin.product.data');
    Route::post('product/{id}', [ProductController::class, 'update']);
    Route::put('product/status', [ProductController::class, 'statusupdate']);
    Route::put('product/rated', [ProductController::class, 'ratedstatusupdate']);
    Route::put('product/featur', [ProductController::class, 'featurestatusupdate']);
    Route::put('product/best-selling', [ProductController::class, 'bestsellstatusupdate']);
    Route::get('product/add-varient/{id}', [ProductController::class, 'varients']);

    Route::get('shop/products', [ProductController::class, 'shopindex']);
    Route::get('product/get/shop-data', [ProductController::class, 'productshopdata'])->name('admin.shopproduct.data');
    Route::get('shop/product-create', [ProductController::class, 'createproduct']);
    Route::get('shop/product-edit/{id}', [ProductController::class, 'editproduct']);

    Route::get('information/{slug}', [InformationController::class, 'index']);
    Route::post('information/update/{slug}', [InformationController::class, 'update']);
    Route::get('menu/page/{slug}', [InformationController::class, 'create']);
    Route::post('menu/page/create/{slug}', [InformationController::class, 'createpage']);

    //coursecategories
    Route::resource('coursecategories', CoursecategoryController::class);
    Route::post('coursecategory/{id}', [CoursecategoryController::class, 'update']);
    Route::put('coursecategory/status', [CoursecategoryController::class, 'updatestatus']);
    Route::get('coursecategory/data', [CoursecategoryController::class, 'coursecategorydata'])->name('admin.coursecategory.data');
    //course
    Route::resource('courses', CourseController::class);
    Route::post('course/{id}', [CourseController::class, 'update']);
    Route::put('course/status', [CourseController::class, 'updatestatus']);
    Route::get('course/data', [CourseController::class, 'coursedata'])->name('course.data');

    // Vendors (supplier portal)
    Route::get('vendors', [VendorController::class, 'index'])->name('admin.vendors.index');
    Route::get('vendors/{vendor}', [VendorController::class, 'show'])->name('admin.vendors.show');
    Route::post('vendors/{vendor}/approve', [VendorController::class, 'approve'])->name('admin.vendors.approve');
    Route::post('vendors/{vendor}/reject', [VendorController::class, 'reject'])->name('admin.vendors.reject');

    // Product reviews (admin view & moderate)
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
    Route::post('reviews/{id}/status', [AdminReviewController::class, 'updateStatus'])->name('admin.reviews.status');

    // Vendor category discounts (admin view)
    Route::get('vendor-category-discounts', [AdminVendorCategoryDiscountController::class, 'index'])->name('admin.vendor-category-discounts.index');

    // Vendor products (verify, approve/reject, edit)
    Route::get('vendor-products', [AdminVendorProductController::class, 'index'])->name('admin.vendor-products.index');
    Route::post('vendor-products/{id}/approve', [AdminVendorProductController::class, 'approve'])->name('admin.vendor-products.approve');
    Route::post('vendor-products/{id}/reject', [AdminVendorProductController::class, 'reject'])->name('admin.vendor-products.reject');

    // Vendor payout requests (Phase 6.3 – admin processing)
    Route::get('view-vendor-payout-requests', [AdminVendorPayoutController::class, 'showPage'])->name('admin.view-vendor-payout-requests');
    Route::get('view-vendor-payout-requests/{status}', [AdminVendorPayoutController::class, 'showPage'])->name('admin.view-vendor-payout-requests.status');
    Route::get('vendor-payout-requests', [AdminVendorPayoutController::class, 'index'])->name('admin.vendor-payout-requests.index');
    Route::post('vendor-payout-requests/{id}/approve', [AdminVendorPayoutController::class, 'approve'])->name('admin.vendor-payout-requests.approve');
    Route::post('vendor-payout-requests/{id}/reject', [AdminVendorPayoutController::class, 'reject'])->name('admin.vendor-payout-requests.reject');

    // Vendor reports (Phase 7.2)
    Route::get('vendor-reports/profit-commission', [AdminVendorReportController::class, 'profitCommission'])->name('admin.vendor-reports.profit-commission');
    Route::get('vendor-reports/order-analytics', [AdminVendorReportController::class, 'orderAnalytics'])->name('admin.vendor-reports.order-analytics');
});

Route::group(['middleware' => ['auth.admin:admin']], function () {
    Route::get('admin/fraud-check-data', [OrderController::class, 'fraudcheck']);
    Route::get('admin_order/assign_courier', [OrderController::class, 'assigncourier']);
    //withdrew
    Route::get('withdrew/{status}', [WithdrewController::class, 'index']);
    Route::get('withdrew/data/{status}', [WithdrewController::class, 'withdrewdatas'])->name('withdrewdata.info');
    Route::get('withdrew/{id}/edit', [WithdrewController::class, 'edit']);
    Route::post('withdrew/update/{id}', [WithdrewController::class, 'update']);

    // invoices receller
    Route::get('resellerinvoice/{status}', [ResellerinvoiceController::class, 'index']);
    Route::get('resellerinvoice/data/{status}', [ResellerinvoiceController::class, 'invoicedata'])->name('invoicedata.info');
    Route::get('resellerinvoice/{id}/edit', [ResellerinvoiceController::class, 'edit']);
    Route::post('resellerinvoice/update/{id}', [ResellerinvoiceController::class, 'update']);
    Route::get('resellerinvoice/user/view-dashboard/{id}', [ResellerinvoiceController::class, 'autologin']);
    Route::get('user/view-incomehistory/{id}', [ResellerinvoiceController::class, 'incomehistory']);
Route::get('user/view-incomehistory/{id}', [ResellerinvoiceController::class, 'incomehistory'])
     ->name('admin.user.incomehistory');

// New route: AJAX endpoint to fetch seller's orders for the DataTable (server-side)
Route::get('user/view-incomehistory/{id}/orders', [ResellerinvoiceController::class, 'incomeHistoryOrders'])
     ->name('admin.incomehistory.orders');
    Route::get('admin_order/invoice-view/{id}', [OrderController::class, 'makeinvoicess']);
    Route::get('admin_order/view/{id}', [OrderController::class, 'vieworder']);
    // complain
    Route::resource('complain/complains', ComplainController::class);
    Route::post('complain/data/{id}', [ComplainController::class, 'update']);
    Route::get('complain/{status}', [ComplainController::class, 'subindex']);
    Route::get('complain/data/{status}', [ComplainController::class, 'complaindata'])->name('complain.info');
    Route::get('complain/complain/Sync', [ComplainController::class, 'complainSync']);
    Route::put('complain/complainstatus', [ComplainController::class, 'updatestatus']);
    Route::get('complain/comment/update', [ComplanenoteController::class, 'store']);
    Route::get('complain/comment/get', [ComplanenoteController::class, 'getComplainNote']);
    Route::get('complain/create/complain', [ComplanenoteController::class, 'createcomplain']);

    Route::get('assign_user_complain', [ComplanenoteController::class, 'assignusertocomplain']);
    Route::get('scanbarcode', [FindorderController::class, 'scanbarcode'])->name('orderchange.bybarcode');
    Route::get('getorder/bybarcode', [FindorderController::class, 'orderdetails']);
    Route::get('admin/getscan/order', [FindorderController::class, 'orderdetabybar']);
    Route::get('admin/orderbysearch', [FindorderController::class, 'searchdata']);
    Route::get('admin/courierUpdateByCheckbox', [FindorderController::class, 'courierUpdateByCheckbox']);
    //order by product
    Route::get('/orderby-product', [OrderController::class, 'orderByproductindex']);
    Route::get('/findorder-byproduct', [OrderController::class, 'findByproduct']);

    //invoiced
    Route::get('admin_order/store/Invoice', [OrderController::class, 'storeInvoice']);
    Route::get('admin_order/invoice/{id}', [OrderController::class, 'viewInvoice']);

    //download excel
    Route::get('download/info', [OrderController::class, 'downloadinfo']);
    Route::post('admin_order/download/excel', [InvoiceController::class, 'fileExport'])->name('file-export');
    Route::post('/download-excle', [InvoiceController::class, 'downloadexcle'])->name('orderdata-export');

    Route::resource('admin_orders', OrderController::class);
    Route::post('admin_order/{id}', [OrderController::class, 'update']);

    Route::get('admin_order/getComment', [OrderController::class, 'getComments']);
    Route::get('admin_order/updateComment', [OrderController::class, 'updateComments']);

    Route::get('admin_order/paymenttype', [OrderController::class, 'paymenttype']);
    Route::get('admin_order/paymentnumber', [OrderController::class, 'paymentnumber']);

    Route::get('admin_order/products', [OrderController::class, 'admproduct']);
    Route::get('admin_order/previous_orders', [OrderController::class, 'previous_orders']);

    Route::get('order/admin_order/status', [OrderController::class, 'updateorderstatus']);
    Route::get('admin_order/product/topsell/{id}', [OrderController::class, 'topsellpeoduct']);
    Route::get('admin_order/product/recentsell/{id}', [OrderController::class, 'recentsellpeoduct']);

    Route::get('admin_order/count', [OrderController::class, 'countorder']);
    Route::get('admin_order/count/{id}', [OrderController::class, 'countorderbyid']);
    Route::get('admin_order/couriers', [OrderController::class, 'couriers']);
    Route::get('admin_order/cities', [OrderController::class, 'city']);
    Route::get('admin_order/zones', [OrderController::class, 'zone']);
    Route::get('admin_order/courier', [OrderController::class, 'courier']);
    Route::get('admin_order/users', [OrderController::class, 'users']);
    Route::get('admin_order/shops', [OrderController::class, 'shops']);
    Route::get('admin_order/statusUpdateByCheckbox', [OrderController::class, 'statusUpdateByCheckbox']);
    Route::get('admin_order/{id}/delete', [OrderController::class, 'delete_selected_order']);
    Route::get('admin_order/assign_user', [OrderController::class, 'assignuser']);
    Route::get('admin/admin_order/{status}', [OrderController::class, 'orderdata'])->name('admin_order.info');
 Route::get('admin/info-count', [OrderController::class, 'infocount'])->name('admin.info.count');
 Route::get('admin/salse-count', [OrderController::class, 'salescount'])->name('admin.sales.count');
    Route::get('admin/create/order', [OrderController::class, 'createorder']);
    Route::post('admin/order/store', [OrderController::class, 'storeorder']); //order by status
    Route::get('admin_order/{status}', [OrderController::class, 'ordersByStatus']);

    Route::get('/file-import', [UserController::class, 'importView'])->name('import-view');
    Route::post('/import', [UserController::class, 'import'])->name('import');
    Route::get('/active-file-import', [UserController::class, 'activeimportView'])->name('activeimport-view');
    Route::post('/active-import', [UserController::class, 'activeimport'])->name('activeimport');

    Route::get('order/manager/dashboard', [AuthenticatedSessionController::class, 'managerdashboard']);
    Route::get('order/dashboard', [AuthenticatedSessionController::class, 'userdashboard']);

    //courier
    Route::resource('couriers', CourierController::class);
    Route::post('courier/{id}', [CourierController::class, 'update']);
    Route::put('courier/status', [CourierController::class, 'updatestatus']);
    Route::get('admin/couriers', [CourierController::class, 'courierdata'])->name('courier.info');
    //city
    Route::resource('cities', CityController::class);
    Route::post('city/{id}', [CityController::class, 'update']);
    Route::put('city/status', [CityController::class, 'updatestatus']);
    Route::get('admin/cities', [CityController::class, 'citydata'])->name('city.info');
    //zone
    Route::resource('zones', ZoneController::class);
    Route::post('zone/{id}', [ZoneController::class, 'update']);
    Route::put('zone/status', [ZoneController::class, 'updatestatus']);
    Route::get('admin/zones', [ZoneController::class, 'zonedata'])->name('zone.info');

    //purchess
    Route::resource('purchases', PurchaseController::class);
    Route::post('purchase/{id}', [PurchaseController::class, 'update']);
    Route::get('admin/purchase', [PurchaseController::class, 'purchasedata'])->name('purchese.info');

    //stocks
    Route::resource('stocks', StockController::class);
    Route::get('admin/stock', [StockController::class, 'stockdata'])->name('stock.info');
    //supplier
    Route::resource('suppliers', SupplierController::class);
    Route::post('supplier/{id}', [SupplierController::class, 'update']);
    Route::put('supplier/status', [SupplierController::class, 'updatestatus']);
    Route::get('admin/supplier', [SupplierController::class, 'supplierdata'])->name('supplier.info');
    //payment method
    Route::resource('paymenttypes', PaymenttypeController::class);
    Route::post('paymenttype/{id}', [PaymenttypeController::class, 'update']);
    Route::put('paymenttype/status', [PaymenttypeController::class, 'updatestatus']);
    Route::get('admin/paymenttype', [PaymenttypeController::class, 'paymenttypedata'])->name('paymenttype.info');
    //payment method
    Route::resource('payments', PaymentController::class);
    Route::post('payment/{id}', [PaymentController::class, 'update']);
    Route::put('payment/status', [PaymentController::class, 'updatestatus']);
    Route::put('payment/restatus', [PaymentController::class, 'updaterestatus']);
    Route::get('admin/payment', [PaymentController::class, 'paymentdata'])->name('payment.info');

    //account packages
    Route::resource('packages', PackageController::class);
    Route::post('package/{id}', [PackageController::class, 'update']);
    Route::put('package/status', [PackageController::class, 'updatestatus']);
    Route::get('admin/package', [PackageController::class, 'packagedata'])->name('package.info');

    //report section
    Route::get('admin/courier/user/report', [ReportController::class, 'courieruserreport'])->name('courieruserreport');
    Route::get('admin/courier/report', [ReportController::class, 'courierreport'])->name('courierreport');
    Route::get('admin/user/report', [ReportController::class, 'userreport'])->name('userreport');
    Route::get('admin/payment/report', [ReportController::class, 'paymentreport'])->name('paymentreport');
    Route::get('admin/product/report', [ReportController::class, 'productreport'])->name('productreport');

    //report data
    Route::get('admin/courier/user/report/data', [ReportController::class, 'courieruserreportdata']);
    Route::get('admin/courier/report/data', [ReportController::class, 'courierreportdata']);
    Route::get('admin/user/report/data', [ReportController::class, 'userreportdata']);
    Route::get('admin/payment/report/data', [ReportController::class, 'paymentreportdata']);
    Route::get('admin/product/report/data', [ReportController::class, 'productreportdata']);

    Route::get('/set-value/city/{id}', [StockController::class, 'getCityByCurier']);
});

Route::post('admin/replay/tikit/{id}', [TikitController::class, 'replay']);
