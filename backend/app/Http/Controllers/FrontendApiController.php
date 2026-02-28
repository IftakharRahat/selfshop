<?php

namespace App\Http\Controllers;

use App\Models\Addbanner;
use App\Models\Admin;
use App\Models\Bank;
use App\Models\Basicinfo;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Chargededuct;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Coursecategory;
use App\Models\Customer;
use App\Models\Faq;
use App\Models\FlashSale;
use App\Models\Fraud;
use App\Models\Income;
use App\Models\Message;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Package;
use App\Models\Paymenttype;
use App\Models\Product;
use App\Models\Productrequest;
use App\Models\Replay;
use App\Models\Resellerapi;
use App\Models\Resellerinvoice;
use App\Models\SalesTarget;
use App\Models\SalesTargetParticipant;
use App\Models\Shopproduct;
use App\Models\Slider;
use App\Models\Subcategory;
use App\Models\Tikit;
use App\Models\User;
use App\Models\Varient;
use App\Models\Vendor;
use App\Models\Withdrew;
use App\Models\Review;
use App\Notifications\AdminBroadcastNotification;
use App\Services\SteadfastOrderStatusService;
use App\Services\VendorAdminNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Notifications\DatabaseNotification;
use Str;

class FrontendApiController extends Controller
{

    public function packages()
    {
        $invoice = Resellerinvoice::where('user_id', Auth::id())
            ->latest('id')
            ->first();
        $packages = Package::where('status', 'Active')->get();
        return response()->json([
            'status' => true,
            'message' => 'Our packages',
            'data' => [
                'invoice' => $invoice,
                'packages' => $packages
            ],
        ], 200);
    }

    public function purchesepackage(Request $request)
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = Auth::user();
        $package = Package::find($validated['package_id']);

        if (!$package) {
            return response()->json([
                'status' => false,
                'message' => 'Package not found',
            ], 404);
        }

        $discountPrice = (float) ($package->discount_price ?? 0);
        $basePrice = (float) ($package->price ?? 0);
        $resolvedAmount = $discountPrice > 0
            ? $discountPrice
            : ($basePrice > 0 ? $basePrice : (float) ($validated['amount'] ?? 0));

        if ($resolvedAmount <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid package amount',
            ], 422);
        }

        // Reuse latest unpaid-like invoice when available; otherwise create new.
        $invoice = Resellerinvoice::where('user_id', $user->id)
            ->whereIn('status', ['Unpaid', 'Pending', 'Failed', 'Canceled'])
            ->latest('id')
            ->first();

        if (!$invoice) {
            $invoice = new Resellerinvoice();
            $invoice->invoiceID = $this->uniqueinvoiceID();
            $invoice->user_id = $user->id;
            $invoice->invoiceDate = date('Y-m-d');
        }

        $invoice->package_id = $package->id;
        $invoice->resellerid = $user->my_referral_code;
        $invoice->amount = $resolvedAmount;
        $invoice->payable_amount = $resolvedAmount;
        $invoice->status = 'Unpaid';
        $invoice->save();

        $user->isInvoice = 'yes';
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Package selected successfully',
            'data' => [
                'invoice' => $invoice,
                'package' => $package,
            ],
        ], 200);
    }

    public function initiatePackagePayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:resellerinvoices,id'],
        ]);

        try {
            $user = Auth::user();
            $invoice = Resellerinvoice::where('id', $validated['invoice_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invoice not found for this account',
                ], 404);
            }

            if (strcasecmp((string) $invoice->status, 'Paid') === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'This invoice is already paid',
                ], 422);
            }

            $package = Package::find($invoice->package_id);
            if (!$package) {
                return response()->json([
                    'status' => false,
                    'message' => 'Package information not found for invoice',
                ], 404);
            }

            $amount = (float) ($invoice->payable_amount ?: $invoice->amount);
            if ($amount <= 0) {
                $amount = (float) (($package->discount_price ?? 0) ?: ($package->price ?? 0));
            }

            if ($amount <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid payable amount for this invoice',
                ], 422);
            }

            $tranId = 'PKG_' . time() . '_' . strtoupper(substr(md5(uniqid((string) $user->id, true)), 0, 8));

            $postData = [];
            $postData['total_amount'] = $amount;
            $postData['currency'] = 'BDT';
            $postData['tran_id'] = $tranId;
            $postData['cus_name'] = $user->name ?? 'Customer';
            $postData['cus_email'] = $user->email ?? 'customer@selfshop.com';
            $postData['cus_add1'] = $user->address ?? 'N/A';
            $postData['cus_add2'] = '';
            $postData['cus_city'] = '';
            $postData['cus_state'] = '';
            $postData['cus_postcode'] = '';
            $postData['cus_country'] = 'Bangladesh';
            $postData['cus_phone'] = $user->phone ?? 'N/A';
            $postData['cus_fax'] = '';
            $postData['ship_name'] = $user->name ?? 'Customer';
            $postData['ship_add1'] = $user->address ?? 'N/A';
            $postData['ship_add2'] = '';
            $postData['ship_city'] = '';
            $postData['ship_state'] = '';
            $postData['ship_postcode'] = '';
            $postData['ship_phone'] = $user->phone ?? 'N/A';
            $postData['ship_country'] = 'Bangladesh';
            $postData['shipping_method'] = 'NO';
            $postData['product_name'] = ($package->package_name ?? 'Package') . ' - Selfshop';
            $postData['product_category'] = 'Digital';
            $postData['product_profile'] = 'non-physical-goods';
            $postData['value_a'] = $user->id;
            $postData['value_b'] = $invoice->id;
            $postData['value_c'] = $package->id;
            $postData['value_d'] = json_encode([
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->invoiceID,
                'package_name' => $package->package_name,
                'amount' => $amount,
            ]);

            $appUrl = rtrim((string) config('app.url'), '/');
            if ($appUrl === '') {
                $appUrl = rtrim(url('/'), '/');
            }

            // Route package callbacks to dedicated handlers instead of generic /cancel|/fail.
            $postData['success_url'] = $appUrl . '/sslcommerz/package/success';
            $postData['fail_url'] = $appUrl . '/sslcommerz/package/fail';
            $postData['cancel_url'] = $appUrl . '/sslcommerz/package/cancel';
            $postData['ipn_url'] = $appUrl . '/sslcommerz/package/ipn';

            $invoice->payment_id = $tranId;
            $invoice->status = 'Pending';
            $invoice->save();

            $ssl = new SslCommerzNotification();
            $paymentResponseRaw = $ssl->makePayment($postData, 'checkout');

            $paymentResponse = is_string($paymentResponseRaw)
                ? json_decode($paymentResponseRaw, true)
                : (is_array($paymentResponseRaw) ? $paymentResponseRaw : null);

            $gatewayUrl = $paymentResponse['data'] ?? null;
            $gatewayStatus = strtolower((string) ($paymentResponse['status'] ?? ''));

            if (!$gatewayUrl || !in_array($gatewayStatus, ['success', 'successful'], true)) {
                \Log::error('Package gateway URL not returned', [
                    'invoice_id' => $invoice->id,
                    'response' => $paymentResponseRaw,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Failed to initialize payment gateway',
                ], 502);
            }

            return response()->json([
                'status' => true,
                'message' => 'Payment initialized',
                'data' => [
                    'gateway_url' => $gatewayUrl,
                    'tran_id' => $tranId,
                    'invoice' => $invoice,
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Package payment init API failed', [
                'user_id' => Auth::id(),
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to initialize payment right now',
            ], 500);
        }
    }

    public function uniqueinvoiceID()
    {
        $lastOrder = Resellerinvoice::latest('id')->first();
        if ($lastOrder) {
            $orderID = $lastOrder->id + 1;
        } else {
            $orderID = 1;
        }

        return 'SSINV' . $orderID;
    }

    public function basicInfo(Request $request)
    {
        $basicInfo = Basicinfo::first();


        if ($basicInfo) {
            return response()->json([
                'status' => true,
                'message' => 'Basic Information',
                'data' => $basicInfo
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Basic Information Not Found',
        ], 404);
    }

    public function categoryData()
    {
        try {
            $categories = Category::where('status', 'Active')
                ->select('id', 'category_name', 'slug', 'category_icon', 'status')
                ->with([
                    'subcategories' => function ($query) {
                        $query->where('status', 'Active')
                            ->select('id', 'sub_category_name', 'slug', 'category_id', 'subcategory_icon');
                    },
                    'subcategories.minicategories'
                ])
                ->get();
        } catch (\Throwable $e) {
            $categories = Category::where('status', 'Active')
                ->select('id', 'category_name', 'slug', 'category_icon', 'status')
                ->with([
                    'subcategories' => function ($query) {
                        $query->where('status', 'Active')
                            ->select('id', 'sub_category_name', 'slug', 'category_id', 'subcategory_icon');
                    }
                ])
                ->get();
        }

        if ($categories->count() > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Category & Sub category Information',
                'data' => $categories
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Category & Sub category Information Not Found',
        ], 404);
    }

    public function headercategories()
    {
        $categories = Category::where('status', 'Active')->select(
            'id',
            'category_name',
            'slug',
            'category_icon',
            'status'
        )->get();

        return response()->json([
            'status' => true,
            'message' => 'Category Information',
            'data' => $categories->count() > 0 ? $categories : []
        ], 200);
    }

    public function menusData()
    {
        $categories = Category::where('status', 'Active')->where('front_status', 0)->select(
            'id',
            'category_name',
            'slug',
            'category_icon',
            'front_status',
            'status'
        )->get();


        if ($categories->count() > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Menu Information',
                'data' => $categories
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Menu Information Not Found',
        ], 404);
    }

    public function sliderData()
    {
        $sliders = Slider::where('status', 'Active')->select('slider_title', 'slider_btn_link', 'slider_image')->get();

        return response()->json([
            'status' => true,
            'message' => 'Slider Information',
            'data' => $sliders->count() > 0 ? $sliders : []
        ], 200);
    }

    public function bottombanners()
    {
        $adsbanner = Addbanner::where('status', 'Inactive')->select('icon')->get();

        return response()->json([
            'status' => true,
            'message' => 'Slider Bottom Banners',
            'data' => $adsbanner->count() > 0 ? $adsbanner : []
        ], 200);
    }

    public function brands()
    {
        $brands = Brand::where('status', 'Active')->select('id', 'brand_name', 'slug', 'brand_icon', 'status')->get();


        if ($brands->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Brand Found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Brands Found successfully',
            'data' => $brands
        ], 200);
    }

    public function collection(Request $request, $slug)
    {
        $limit = $request->limit ?? 15;
        $total = 0;
        $searchcontents = null;
        $title = 'Products';

        if ($slug == 'hot_selling') {
            $title = 'Hot Selling Products';
            $total = Product::visibleOnStorefront()->where('hot_list', 'On')->count();
            $searchcontents = Product::visibleOnStorefront()->where('hot_list', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'ready_to_bost') {
            $title = 'Ready To Bost Products';
            $total = Product::visibleOnStorefront()->where('ready_bost', 'On')->count();
            $searchcontents = Product::visibleOnStorefront()->where('ready_bost', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'profitable_product') {
            $title = 'Profitable Products';
            $total = Product::visibleOnStorefront()->where('profitable', 'On')->count();
            $searchcontents = Product::visibleOnStorefront()->where('profitable', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'new_arrivel') {
            $title = 'New Arrivel Products';
            $total = Product::visibleOnStorefront()->where('show_new_product', 'On')->count();
            $searchcontents = Product::visibleOnStorefront()->where('show_new_product', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'limited_offer') {
            $title = 'Limited Offer Products';
            $total = Product::visibleOnStorefront()->where('limited', 'On')->count();
            $searchcontents = Product::visibleOnStorefront()->where('limited', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'summer_collection') {
            $title = 'Summer Collection Products';
            $total = Product::visibleOnStorefront()->where('summer', 'On')->count();
            $searchcontents = Product::visibleOnStorefront()->where('summer', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        }

        if ($searchcontents === null || $searchcontents->count() == 0) {
            return response()->json([
                'status' => true,
                'message' => $title,
                'total' => $total,
                'data' => $searchcontents ? $searchcontents->items() : []
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => $title . ' Found successfully',
            'total' => $total,
            'data' => $searchcontents
        ], 200);
    }

    public function newarrivels(Request $request)
    {
        $limit = $request->limit ?? 15;
        $total = Product::visibleOnStorefront()->where('show_new_product', 'On')->count();

        $searchcontents = Product::visibleOnStorefront()->where('show_new_product', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);

        if ($searchcontents->count() == 0) {
            return response()->json([
                'status' => false,
                'total' => $total,
                'message' => 'No new arrivels products Found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'total' => $total,
            'message' => 'New arrivels products found successfully',
            'data' => $searchcontents
        ], 200);
    }

    public function newproducts(Request $request)
    {
        $limit = $request->limit ?? 15;
        $total = Product::visibleOnStorefront()->where('show_new_product', 'On')->count();

        $searchcontents = Product::visibleOnStorefront()->where('show_new_product', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);

        if ($searchcontents->count() == 0) {
            return response()->json([
                'status' => false,
                'total' => $total,
                'message' => 'No new products Found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'total' => $total,
            'message' => 'New products found successfully',
            'data' => $searchcontents
        ], 200);
    }

    public function featuredproducts(Request $request)
    {
        $limit = $request->limit ?? 15;
        $total = Product::visibleOnStorefront()->where('frature', '0')->count();

        $searchcontents = Product::visibleOnStorefront()->where('frature', '0')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);

        if ($searchcontents->count() == 0) {
            return response()->json([
                'status' => false,
                'total' => $total,
                'message' => 'No featured products Found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'total' => $total,
            'message' => 'Featured products found successfully',
            'data' => $searchcontents
        ], 200);
    }

    public function categoryproducts(Request $request)
    {
        $limit = $request->limit;

        // Get categories
        $categories = Category::where('status', 'Active')
            ->where('front_status', 0)
            ->select('id', 'category_name', 'slug')
            ->get();

        // Add paginated products and total product count for each category
        foreach ($categories as $category) {
            $category->products = Product::where('category_id', $category->id)
                ->where('status', 'Active')
                ->select(
                    'id',
                    'category_id',
                    'ProductName',
                    'ProductSlug',
                    'ProductRegularPrice',
                    'ProductSalePrice',
                    'ProductResellerPrice',
                    'Discount',
                    'ViewProductImage'
                )
                ->paginate($limit);

            // Total active products in this category
            $category->totalproduct = Product::where('category_id', $category->id)
                ->where('status', 'Active')
                ->count();
        }

        if ($categories->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No category products Found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Category products found successfully',
            'data' => $categories
        ], 200);
    }

    public function bigselling(Request $request)
    {
        $limit = $request->limit ?? 15;
        $total = Product::visibleOnStorefront()->where('top_rated', '1')->count();
        $searchcontents = Product::visibleOnStorefront()->where('top_rated', '1')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);


        if ($searchcontents->count() == 0) {
            return response()->json([
                'status' => true,
                'message' => 'No big selling products Found',
                'total' => 0,
                'data' => [],
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Big selling products found successfully',
            'total' => $total,
            'data' => $searchcontents
        ], 200);
    }

    public function productbycategory(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)->first();
        if (!$category) {
            return response()->json([
                'status' => true,
                'message' => 'No products found',
                'data' => []
            ], 200);
        }

        $products = Product::visibleOnStorefront()->where('category_id', $category->id)->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'created_at')->get();

        // Attach avg_rating and review_count to each product
        foreach ($products as $product) {
            $reviews = Review::where('product_id', $product->id)->where('status', 'Active');
            $product->avg_rating = round($reviews->avg('rating') ?? 0, 1);
            $product->review_count = $reviews->count();
        }

        // Sort
        $sort = $request->input('sort', 'rating');
        $sorted = $this->sortProducts($products, $sort);

        return response()->json([
            'status' => true,
            'message' => 'Products found with this category successfully',
            'data' => $sorted->values()
        ], 200);
    }

    public function productbysubcategory(Request $request, $slug)
    {
        $selects = ['id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'created_at'];

        if (empty($slug)) {
            $products = Product::visibleOnStorefront()->select(...$selects)->latest()->get();
            foreach ($products as $product) {
                $reviews = Review::where('product_id', $product->id)->where('status', 'Active');
                $product->avg_rating = round($reviews->avg('rating') ?? 0, 1);
                $product->review_count = $reviews->count();
            }
            $sort = $request->input('sort', 'rating');
            $sorted = $this->sortProducts($products, $sort);
            return response()->json([
                'status' => true,
                'message' => 'All products',
                'data' => $sorted->values()
            ], 200);
        }

        $subcategory = Subcategory::where('slug', $slug)->first();
        if (!$subcategory) {
            return response()->json([
                'status' => true,
                'message' => 'No products found',
                'data' => []
            ], 200);
        }

        $products = Product::visibleOnStorefront()->where('subcategory_id', $subcategory->id)->select(...$selects)->get();
        foreach ($products as $product) {
            $reviews = Review::where('product_id', $product->id)->where('status', 'Active');
            $product->avg_rating = round($reviews->avg('rating') ?? 0, 1);
            $product->review_count = $reviews->count();
        }
        $sort = $request->input('sort', 'rating');
        $sorted = $this->sortProducts($products, $sort);

        return response()->json([
            'status' => true,
            'message' => 'Products found with this sub-category successfully',
            'data' => $sorted->values()
        ], 200);
    }

    protected function sortProducts($products, $sort)
    {
        switch ($sort) {
            case 'rating':
                return $products->sortByDesc('avg_rating');
            case 'newest':
                return $products->sortByDesc('created_at');
            case 'oldest':
                return $products->sortBy('created_at');
            case 'price_asc':
                return $products->sortBy('ProductSalePrice');
            case 'price_desc':
                return $products->sortByDesc('ProductSalePrice');
            default:
                return $products->sortByDesc('avg_rating'); // Default sort
        }
    }

    public function productbybrand($slug)
    {
        $brand = Brand::where('slug', $slug)->first();
        $brandproducts = Product::visibleOnStorefront()->where('brand_id', $brand->id)->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->get();

        if ($brandproducts->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No products fuound with this brand',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Products found with this brand successfully',
            'data' => $brandproducts
        ], 200);
    }

    public function search(Request $request)
    {
        $products = Product::visibleOnStorefront()->where('ProductName', 'LIKE', '%' . $request->keywords . '%')->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->get();

        if ($products->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No products fuound with this keywords',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Products found with this keywords successfully',
            'data' => $products
        ], 200);
    }

    public function productdetails($slug)
    {
        $product = Product::with([
            'varients.sizes.bulkPrices',
            'priceTiers',
            'vendor:id,user_id,company_name,slug,approval_type,is_verified_badge',
        ])->where('ProductSlug', $slug)->first();
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
        // Hide unapproved vendor products from storefront
        if ($product->vendor_id && ($product->vendor_approval_status ?? '') !== 'approved') {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        // Mask vendor identity for privately approved suppliers
        if ($product->vendor && $product->vendor->approval_type === 'private') {
            $product->vendor->company_name = $product->vendor->public_name;
            $product->vendor->slug = $product->vendor->public_slug;
        }

        $relatedproducts = Product::where('category_id', $product->category_id)->visibleOnStorefront()->latest()->paginate(12);

        return response()->json([
            'status' => true,
            'message' => 'Products Details & Related Products',
            'data' => [
                'product_details' => $product,
                'relatedproducts' => $relatedproducts
            ]
        ], 200);
    }


    // login and reg part

    public function userRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['string', 'max:255'],
        ]);

        // Accept both refer_by (expected) and refer_code (legacy frontend key).
        $referralCode = strtoupper(trim((string) ($request->refer_by ?? $request->refer_code ?? '')));

        if (strlen($request->email) == '11') {
            $olduser = User::where('email', $request->email)->first();
            if ($olduser) {
                $olduseremail = $olduser;
            } else {
                $ema = '88' . $request->email;
                $olduser = User::where('email', $ema)->first();
                $olduseremail = $olduser;
            }
        } else {
            $olduser = User::where('email', $request->email)->first();
            $olduseremail = $olduser;
        }


        if ($referralCode !== '') {
            $validity = User::where('my_referral_code', $referralCode)->first();
        } else {
            $validity = User::first();
        }

        if (isset($validity)) {
            if (isset($olduseremail)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email or phone already exist !',
                ], 409);
            } else {
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->phone = $request->email;
                $string = str_replace(' ', '', $request->name);
                $code = substr($string, 0, 3);

                $user->my_referral_code = strtoupper($code) . $this->uniqueID();
                if ($referralCode !== '') {
                    $user->refer_by = $referralCode;
                } else {
                    $user->refer_by = $validity->my_referral_code;;
                }
                $otp = random_int(100000, 999999);
                $user->otp = $otp;
                $otppass = $otp;
                $user->password = Hash::make($request->password);
                $success = $user->save();

                if ($success) {
                    if ($referralCode !== '') {
                        $createreferral = User::where('my_referral_code', $referralCode)->first();
                        if (isset($createreferral)) {
                            $createreferral->my_referral = $createreferral->my_referral + 1;
                            $createreferral->update();
                        }
                    }
                    Auth::login($user);
                    $us = User::where('email', $user->email)->first();
                    return response()->json([
                        'status' => true,
                        'message' => 'Authentication Successful',
                        'token' => $us->createToken('authToken')->plainTextToken,
                        'token_type' => 'Bearer',
                    ], 200);
                }

                return response()->json([
                    'status' => false,
                    'message' => 'User Registration Failed !',
                ], 500);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Refer Code is not valid. Please enter a valid Refer code.',
            ], 404);
        }
    }

    public function uniqueID()
    {
        $lastReseller = User::latest('id')->first();
        if ($lastReseller) {
            $resellerID = $lastReseller->id + 1;
        } else {
            $resellerID = 1;
        }

        return 'SS00' . $resellerID;
    }

    public function userLogin(Request $request)
    {

        if (strlen($request->email) == '11') {
            $user = User::whereIn('status', ['Active', 'Inactive'])->where('email', $request->email)
                ->first();
            if ($user) {
                $user = User::whereIn('status', ['Active', 'Inactive'])->where('email', $request->email)
                    ->first();
            } else {
                $ema = '88' . $request->email;
                $user = User::whereIn('status', ['Active', 'Inactive'])->where('email', $ema)
                    ->first();
            }
        } else {
            $user = User::whereIn('status', ['Active', 'Inactive'])->where('email', $request->email)->first();
        }

        if (isset($user)) {
            if ($user->status == 'Active') {
                if ($user->expire_date >= date('Y-m-d')) {
                    if (Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])) {
                        $us = User::where('email', $user->email)->first();
                        return response()->json([
                            'status' => true,
                            'message' => 'Authentication Successful',
                            'token' => $us->createToken('authToken')->plainTextToken,
                            'token_type' => 'Bearer',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Password Does not Match',
                        ], 404);
                    }
                } else {
                    if (isset($user->expire_date)) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Your account is expire please contact support',
                        ], 200);
                    } else {
                        if (Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])) {
                            $us = User::where('email', $user->email)->first();
                            return response()->json([
                                'status' => true,
                                'message' => 'Authentication Successful',
                                'token' => $us->createToken('authToken')->plainTextToken,
                                'token_type' => 'Bearer',
                            ], 200);
                        }
                    }
                }
            } else {
                if (Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])) {
                    $us = User::where('email', $user->email)->first();
                    return response()->json([
                        'status' => true,
                        'message' => 'Authentication Successful',
                        'token' => $us->createToken('authToken')->plainTextToken,
                        'token_type' => 'Bearer',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Password Does not Match',
                    ], 404);
                }
            }
        } else {
            $user = User::where('email', $request->email)
                ->first();
            if (isset($user)) {
                if (!Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Password Does Not Match',
                    ], 404);
                }
                return response()->json([
                    'status' => false,
                    'message' => 'You are blocked by authority',
                ], 404);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Information Does Not Match',
                ], 401);
            }
        }
    }


    public function userLogout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        return response()->json(
            [
                'status' => true,
                'message' => 'Logout Successful',
            ],
            200
        );
    }


    public function userForgotPassword(Request $request)
    {
        // Validate the email field
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Attempt to send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Return a JSON response based on the status
        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => true,
                'message' => __($status),
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send password reset link',
            ], 400);
        }
    }


    public function userResetPassword(Request $request)
    {
        // Validate the email field
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        if (strlen($request->phone) == '11') {
            $user = User::where('email', $request->phone)->first();
            if ($user) {
                $user = User::where('email', $request->phone)->first();
            } else {
                $ema = '88' . $request->phone;
                $user = User::where('email', $ema)->first();
            }
        } else {
            $user = User::where('email', $request->phone)->first();
        }

        if (isset($user)) {
            $otp = random_int(100000, 999999);
            $user->otp = $otp;
            $user->update();
            $otpcode = $otp;
            Session::put('phone', $request->phone);
            $status = Http::get('http://bulksmsbd.net/api/smsapi?api_key=PwokJ9JcGrHVqm0Vmqp9&type=text&number=' . $user->email . '&senderid=8809604902839&message=Dear ' . $user->name . ' Your password reset OTP is : ' . $otpcode . '');
            // Return a JSON response based on the status
            if ($status) {
                return response()->json([
                    'status' => true,
                    'message' => __($status),
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send otp',
                ], 400);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send otp',
            ], 400);
        }
    }

    public function userConfirmPassword(Request $request)
    {
        // Validate the provided password for the currently authenticated user
        if (!Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            // Return a JSON response for invalid password
            return response()->json([
                'status' => false,
                'message' => __('auth.password'), // Use translation for the error message
            ], 422);
        }

        // Store the password confirmation time in the session
        $request->session()->put('auth.password_confirmed_at', time());

        // Return a success response
        return response()->json([
            'status' => true,
            'message' => 'Password confirmed successfully.',
        ], 200);
    }

    public function userProfile()
    {
        $id = Auth::user()->id;
        $userprofile = User::findOrfail($id);

        // Enforce renewal after expiry: auto-mark expired active users as unpaid/inactive.
        if (
            $userprofile->status === 'Active' &&
            !empty($userprofile->expire_date) &&
            $userprofile->expire_date < date('Y-m-d')
        ) {
            $userprofile->status = 'Inactive';
            $userprofile->membership_status = 'Unpaid';
            $userprofile->save();
            $userprofile->refresh();
        }

        if ($userprofile) {
            $bank = Bank::where('user_id', $id)->first();
            $amount = Order::where('user_id', $id)->whereIn('status', ['Delivered', 'Paid'])->get()->sum('subTotal') + Order::where('user_id', $id)->whereIn('status', ['Delivered', 'Paid'])->get()->sum('paymentAmount');

            return response()->json([
                'status' => true,
                'message' => 'User Profile Information',
                'data' => [
                    'profile' => $userprofile,
                    'bankinfo' => $bank,
                    'shopproducts' => Shopproduct::where('user_id', $id)->get()->count(),
                    'totalorders' => Order::where('user_id', $id)->get()->count(),
                    'soldamount' => $amount,
                    'walletbalance' => Auth::user()->account_balance,
                ],
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'User Profile Information Not Found',
        ], 404);
    }

    public function updateprofile(Request $request)
    {
        $time = microtime('.') * 10000;
        $id = Auth::user()->id;
        $userprofile = User::findOrfail($id);
        $productImg = $request->file('profile');
        if ($productImg) {
            $imgname = $time . $productImg->getClientOriginalName();
            $imguploadPath = ('public/images/user/profile/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $userprofile->profile = $productImgUrl;
        }
        $nidImg = $request->file('nid');
        if ($nidImg) {
            $imgname = $time . $nidImg->getClientOriginalName();
            $imguploadPath = ('public/images/user/nid/');
            $nidImg->move($imguploadPath, $imgname);
            $nidImgUrl = $imguploadPath . $imgname;
            $userprofile->nid = $nidImgUrl;
        }
        $userprofile->name = $request->name;
        $userprofile->dob = $request->dob;
        $userprofile->address = $request->address;
        $userprofile->shop_name = $request->shop_name;
        $userprofile->update();
        return response()->json([
            'status' => true,
            'message' => 'Profile update successfully',
            'data' => $userprofile
        ], 200);
    }

    public function developersapi()
    {
        $api = Resellerapi::where('user_id', Auth::user()->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Developer api data',
            'data' => $api
        ], 200);
    }

    public function generatedevelopersapi()
    {
        $id = Auth::user()->id;
        $api = Resellerapi::where('user_id', $id)->first();

        if ($api) {
            return response()->json([
                'status' => true,
                'message' => 'Already have an api to this user',
                'data' => $api
            ], 200);
        } else {
            $key = md5(microtime(true) . mt_Rand());
            $secret = Str::uuid()->toString();
            $api = new Resellerapi();
            $api->user_id = Auth::user()->id;
            $api->api_key = $key;
            $api->api_secret = $secret;
            $api->date = date('Y-m-d');
            $api->save();

            return response()->json([
                'status' => true,
                'message' => 'Developer api created succesfully',
                'data' => $api
            ], 200);
        }
    }

    public function faqs()
    {
        $faqs = Faq::where('status', 'Active')->get();
        return response()->json([
            'status' => true,
            'message' => 'FAQ found successfully',
            'data' => $faqs
        ], 200);
    }

    private function hydrateOrderStatus(Order $order, bool $sync = false): Order
    {
        /** @var SteadfastOrderStatusService $service */
        $service = app(SteadfastOrderStatusService::class);

        $meta = $sync
            ? $service->syncOrderStatus($order, false)
            : $service->getStatusPayload($order);

        $order->setAttribute('customer_status', $meta['customer_status']);
        $order->setAttribute('display_status', $meta['customer_status']);
        $order->setAttribute('steadfast_status', $meta['steadfast_status']);
        $order->setAttribute('steadfast_last_synced_at', $meta['steadfast_last_synced_at']);
        $order->setAttribute('warehouse_sent_at', $meta['warehouse_sent_at']);

        return $order;
    }

    private function normalizeInvoiceId(?string $invoiceId): ?string
    {
        $invoiceId = trim((string) $invoiceId);
        if ($invoiceId === '') {
            return null;
        }

        $normalized = preg_replace('/^[^A-Za-z0-9]+/', '', $invoiceId);
        $normalized = trim((string) $normalized);

        return $normalized !== '' ? $normalized : null;
    }

    public function orders($slug)
    {
        $id = Auth::user()->id;
        $query = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])
            ->where('user_id', $id);

        $slugLower = strtolower((string) $slug);
        if (!in_array($slugLower, ['all', ''], true)) {
            if ($slugLower === 'accepted') {
                $query->where('status', 'Confirmed');
            } elseif ($slugLower === 'rejected') {
                $query->whereIn('status', ['Canceled', 'Cancelled', 'Rejected']);
            } else {
                $query->where('status', $slug);
            }
        }

        $totalQuery = clone $query;
        $orders = $query->orderByDesc('id')->paginate(30);
        $orders->getCollection()->transform(function ($order) {
            return $this->hydrateOrderStatus($order, false);
        });

        if ($orders) {
            return response()->json([
                'status' => true,
                'message' => 'Order list',
                'total' => $totalQuery->count(),
                'data' => $orders
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'No order found with this status',
        ], 404);
    }

    public function ordercount()
    {
        $id = Auth::user()->id;

        return response()->json([
            'status' => true,
            'message' => 'Order Count',
            'data' => [
                'total' => Order::where('user_id', $id)->get()->count(),
                'pending' => Order::where('user_id', $id)->where('status', 'Pending')->get()->count(),
                'canceled' => Order::where('user_id', $id)->where('status', 'Canceled')->get()->count(),
                'confirmed' => Order::where('user_id', $id)->where('status', 'Confirmed')->get()->count(),
                'accepted' => Order::where('user_id', $id)->where('status', 'Confirmed')->get()->count(),
                'rejected' => Order::where('user_id', $id)->whereIn('status', ['Canceled', 'Cancelled', 'Rejected'])->get()->count(),
                'packageing' => Order::where('user_id', $id)->where('status', 'Packageing')->get()->count(),
                'ontheway' => Order::where('user_id', $id)->where('status', 'Ontheway')->get()->count(),
                'shipped_to_warehouse' => Order::where('user_id', $id)->where('status', 'Ontheway')->get()->count(),
                'delivered' => Order::where('user_id', $id)->where('status', 'Delivered')->get()->count(),
                'return' => Order::where('user_id', $id)->where('status', 'Return')->get()->count(),
            ]
        ], 200);
    }

    public function trackorder(Request $request)
    {
        $rawInvoiceId = trim((string) $request->invoiceID);
        $invoiceId = $this->normalizeInvoiceId($request->invoiceID);

        if (!$invoiceId) {
            return response()->json([
                'status' => false,
                'message' => 'No order found with this invoice id',
            ], 404);
        }

        $orders = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])
            ->where(function ($q) use ($rawInvoiceId, $invoiceId) {
                $q->where('invoiceID', $invoiceId);
                if ($rawInvoiceId !== '' && $rawInvoiceId !== $invoiceId) {
                    $q->orWhere('invoiceID', $rawInvoiceId);
                }
            })
            ->first();

        if ($orders) {
            $orders = $this->hydrateOrderStatus($orders, true);
            return response()->json([
                'status' => true,
                'message' => 'Order found succesfully',
                'data' => $orders
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'No order found with this invoice id',
        ], 404);
    }

    public function orderTrackingNow(Request $request)
    {
        $rawInvoiceId = trim((string) $request->invoiceID);
        $invoiceId = $this->normalizeInvoiceId($request->invoiceID);

        if (!$invoiceId) {
            return response()->json([
                'status' => false,
                'message' => 'Order Not Found',
            ], 404);
        }

        $orders = Order::with([
            'customers',
            'orderproducts',
            'couriers',
            'cities',
            'zones',
            'admins'
        ])->where('user_id', Auth::id())
            ->where(function ($q) use ($rawInvoiceId, $invoiceId) {
                $q->where('invoiceID', $invoiceId);
                if ($rawInvoiceId !== '' && $rawInvoiceId !== $invoiceId) {
                    $q->orWhere('invoiceID', $rawInvoiceId);
                }
            })
            ->first();

        if (!$orders) {
            return response()->json([
                'status' => false,
                'message' => 'Order Not Found',
            ], 404);
        }

        $orders = $this->hydrateOrderStatus($orders, true);

        return response()->json([
            'status' => true,
            'message' => 'Order Found successfully',
            'data' => $orders
        ], 200);
    }

    public function bankinfo(Request $request)
    {
        $id = Auth::user()->id;
        $bank = Bank::where('user_id', $id)->first();
        if (isset($bank)) {
            $bank->bank_name = $request->bank_name;
            $bank->account_name = $request->account_name;
            $bank->account_number = $request->account_number;
            $bank->routing_number = $request->routing_number;
            $bank->update();
        } else {
            $bank = new Bank();
            $bank->user_id = $id;
            $bank->bank_name = $request->bank_name;
            $bank->account_name = $request->account_name;
            $bank->account_number = $request->account_number;
            $bank->routing_number = $request->routing_number;
            $bank->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Bank data info updated',
            'data' => $bank
        ], 200);
    }

    public function supportticket()
    {
        $id = Auth::user()->id;
        $tikits = Tikit::where('from_id', $id)->get()->reverse();
        return response()->json([
            'status' => true,
            'message' => 'Support ticket list',
            'data' => $tikits
        ], 200);
    }

    public function createticket(Request $request)
    {
        $id = Auth::user()->id;
        $tts = Tikit::where('from_id', $id)->get();
        foreach ($tts as $tt) {
            $t = Tikit::where('id', $tt->id)->first();
            $t->status = 'Closed';
            $t->update();
        }
        $tikit = new Tikit();
        $tikit->from_id = $id;
        $tikit->name = Auth::user()->name;
        $tikit->email = Auth::user()->email;
        $tikit->subject = $request->subject;
        $tikit->department = $request->department;
        $tikit->priority = $request->priority;
        $tikit->message = $request->message;

        $time = microtime('.') * 10000;
        $productImg = $request->file('attachment');
        if ($productImg) {
            $imgname = $time . $productImg->getClientOriginalName();
            $imguploadPath = ('public/images/tikit/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $tikit->attachment = $productImgUrl;
        }
        $tikit->save();

        return response()->json([
            'status' => true,
            'message' => 'Support ticket submit successfully',
            'data' => $tikit
        ], 200);
    }

    public function viewticket($id)
    {
        $tikit = Tikit::findOrfail($id);
        $replays = Replay::with('users')->where('tikit_id', $id)->get();

        return response()->json([
            'status' => true,
            'message' => 'View ticket by id',
            'data' => [
                'ticket' => $tikit,
                'replays' => $replays
            ],
        ], 200);
    }

    public function replayticket(Request $request, $id)
    {
        $userid = Auth::user()->id;
        $tikit = Tikit::where('id', $id)->first();
        $tikit->status = 'Customer-Replay';
        $tikit->update();

        $replay = new Replay();
        $replay->tikit_id = $id;
        $replay->replay = $request->replay;
        $replay->type = 'User';
        $replay->from_user_id = $userid;
        $replay->status = 'Customer-Replay';
        $time = microtime('.') * 10000;
        $productImg = $request->file('replayatt');
        if ($productImg) {
            $imgname = $time . $productImg->getClientOriginalName();
            $imguploadPath = ('public/images/tikit/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $replay->replayatt = $productImgUrl;
        }

        $replay->save();
        return response()->json([
            'status' => true,
            'message' => 'Ticket replay successfully',
            'data' => $replay
        ], 200);
    }


    // fraud list
    public function storefraud(Request $request)
    {
        $id = Auth::user()->id;
        $product = new Fraud();
        $product->from_id = $id;
        $product->phone = $request->phone;
        $product->message = $request->message;
        $product->save();
        return response()->json([
            'status' => true,
            'message' => 'Number added to fraudlist',
            'data' => $product
        ], 200);
    }

    public function checkfraud(Request $request)
    {
        $slug = $request->number;
        $frauds = Fraud::where('phone', 'LIKE', "%{$slug}%")->get();
        return response()->json([
            'status' => true,
            'message' => 'Fraud list matching with this number',
            'data' => $frauds
        ], 200);
    }

    public function course()
    {
        $coursecategory = Coursecategory::where('status', 'Active')->get();

        $coursecategory->map(function ($course) {
            $course->totalcourse = Course::where('status', 'Active')
                ->where('coursecategory_id', $course->id)
                ->count();
            return $course;
        });

        return response()->json([
            'status' => true,
            'message' => 'Course category list',
            'data' => $coursecategory
        ], 200);
    }

    public function coursedetails($slug)
    {
        $coursecategory = Coursecategory::where('slug', $slug)->first();
        $courses = Course::where('status', 'Active')->where('coursecategory_id', $coursecategory->id)->get();

        return response()->json([
            'status' => true,
            'message' => 'Course details',
            'data' => [
                'courses' => $courses,
                'coursecategory' => $coursecategory
            ],
        ], 200);
    }


    public function teams()
    {
        $id = Auth::user()->my_referral_code;
        $teams = User::where('refer_by', $id)->latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Team member lists',
            'data' => $teams
        ], 200);
    }

    public function productlist()
    {
        $id = Auth::user()->id;
        $lists = Productrequest::where('from_id', $id)
            ->latest()
            ->get()
            ->map(function ($item) {
                // Backward compatibility for old rows where description lived in "message".
                if (empty($item->p_description) && !empty($item->message)) {
                    $item->p_description = $item->message;
                }
                return $item;
            });
        return response()->json([
            'status' => true,
            'message' => 'Requested Product Lists',
            'data' => $lists
        ], 200);
    }


    public function productrequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'p_name' => ['required', 'string', 'max:255'],
            'p_quantity' => ['nullable', 'string', 'max:50'],
            'p_description' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = new Productrequest();
            $productImg = $request->file('attachment');
            $time = microtime('.') * 10000;
            if ($productImg) {
                $imgname = $time . $productImg->getClientOriginalName();
                $imguploadPath = public_path('images/user/profile/');
                if (!file_exists($imguploadPath)) {
                    mkdir($imguploadPath, 0755, true);
                }
                $productImg->move($imguploadPath, $imgname);
                $productImgUrl = 'public/images/user/profile/' . $imgname;
                $product->attachment = $productImgUrl;
            }
            $id = Auth::user()->id;
            $product->from_id = $id;
            $product->p_name = $request->p_name;
            if (Schema::hasColumn('productrequests', 'p_quantity')) {
                $product->p_quantity = $request->p_quantity;
            }
            if (Schema::hasColumn('productrequests', 'p_description')) {
                $product->p_description = $request->p_description;
            }
            if (Schema::hasColumn('productrequests', 'message')) {
                $product->message = $request->p_description ?? $request->message;
            }
            $product->save();
            return response()->json([
                'status' => true,
                'message' => 'Product request give successfully',
                'data' => $product
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to submit product request: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function withdrawlist()
    {
        $id = Auth::user()->id;
        $withdrawlists = Withdrew::where('type', 'Withdrew')->where('user_id', $id)->latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Withdraw lists',
            'data' => $withdrawlists
        ], 200);
    }


    public function paymenttypes()
    {
        $paymenttypes = Paymenttype::where('status', 'Active')->get();
        return response()->json([
            'status' => true,
            'message' => 'Payment types',
            'data' => $paymenttypes
        ], 200);
    }

    private function findTransferReceiver(string $identifier, bool $lockForUpdate = false): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $variants = [];
        $addVariant = function (string $value) use (&$variants) {
            $value = trim($value);
            if ($value !== '' && !in_array($value, $variants, true)) {
                $variants[] = $value;
            }
        };

        $addVariant($identifier);
        $addVariant(strtoupper($identifier));

        if (preg_match('/^\d{11}$/', $identifier)) {
            $addVariant('88' . $identifier);
        }

        if (preg_match('/^88\d{11}$/', $identifier)) {
            $addVariant(substr($identifier, 2));
        }

        foreach ($variants as $candidate) {
            $query = User::query();
            if ($lockForUpdate) {
                $query->lockForUpdate();
            }

            $receiver = $query
                ->where('status', 'Active')
                ->where(function ($q) use ($candidate) {
                    $q->where('email', $candidate)
                        ->orWhere('phone', $candidate)
                        ->orWhere('my_referral_code', $candidate);
                })
                ->first();

            if ($receiver) {
                return $receiver;
            }
        }

        return null;
    }

    public function withdrawrequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdrew_amount' => ['required', 'numeric', 'min:0.01'],
            'paymenttype_id' => ['required', 'integer'],
            'to_account_number' => ['required', 'string', 'max:255'],
            'to_additional_info' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $id = Auth::id();
        $amount = (float) $request->withdrew_amount;

        try {
            DB::beginTransaction();

            $user = User::where('id', $id)->where('status', 'Active')->lockForUpdate()->first();
            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'User is not active',
                ], 403);
            }

            if ((float) $user->account_balance < $amount) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Not enough balance',
                ], 422);
            }

            $paymenttypes = Paymenttype::where('id', (int) $request->paymenttype_id)
                ->where('status', 'Active')
                ->first();

            if (!$paymenttypes) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid payment method',
                ], 422);
            }

            $withdrew = new Withdrew();
            $withdrew->user_id = $id;
            $withdrew->type = 'Withdrew';
            $withdrew->paymenttype_id = $paymenttypes->id;
            $withdrew->paymenttype_name = $paymenttypes->paymentTypeName;
            $withdrew->to_account_number = $request->to_account_number;
            $withdrew->to_additional_info = $request->to_additional_info;
            $withdrew->withdrew_amount = $amount;
            $withdrew->status = 'Pending';
            $withdrew->save();

            $user->account_balance = (float) $user->account_balance - $amount;
            $user->pending_cashout_balance = (float) $user->pending_cashout_balance + $amount;
            $user->save();

            $comment = new Comment();
            $comment->comment = 'You have sent a payment request via ' . $paymenttypes->paymentTypeName . ' Invoice ID: #IN00' . $withdrew->id;
            $comment->user_id = $id;
            $comment->status = 1;
            $comment->type = 'Withdraw';
            $comment->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Withdraw request sent successfully',
                'data' => [
                    'invoice_id' => $withdrew->id,
                    'status' => $withdrew->status,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to submit withdraw request',
            ], 500);
        }
    }


    public function transferlists()
    {
        $user = Auth::user();
        $id = $user->id;

        $identifiers = [];
        $pushIdentifier = function (?string $value) use (&$identifiers) {
            $value = trim((string) $value);
            if ($value !== '' && !in_array($value, $identifiers, true)) {
                $identifiers[] = $value;
            }
        };

        $pushIdentifier($user->email ?? null);
        $pushIdentifier($user->phone ?? null);
        $pushIdentifier($user->my_referral_code ?? null);

        if (!empty($user->phone) && preg_match('/^\d{11}$/', (string) $user->phone)) {
            $pushIdentifier('88' . $user->phone);
        }
        if (!empty($user->phone) && preg_match('/^88\d{11}$/', (string) $user->phone)) {
            $pushIdentifier(substr((string) $user->phone, 2));
        }

        $sentTransfers = Withdrew::where('type', 'Transfer')
            ->where('user_id', $id)
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->transfer_direction = 'Sent';
                $item->counterparty_label = 'To';
                $item->counterparty = $item->to_account_number;
                return $item;
            });

        $receivedTransfers = collect();
        if (!empty($identifiers)) {
            $receivedTransfers = Withdrew::where('type', 'Transfer')
                ->where('user_id', '!=', $id)
                ->where(function ($query) use ($identifiers) {
                    foreach ($identifiers as $identifier) {
                        $query->orWhere('to_account_number', $identifier);
                    }
                })
                ->latest()
                ->get()
                ->map(function ($item) {
                    $sender = User::select('id', 'name', 'email', 'phone', 'my_referral_code')
                        ->find($item->user_id);

                    $counterparty = 'Unknown sender';
                    if ($sender) {
                        $counterparty = $sender->name
                            ?: ($sender->email ?: ($sender->my_referral_code ?: ($sender->phone ?: 'Unknown sender')));
                    }

                    $item->transfer_direction = 'Received';
                    $item->counterparty_label = 'From';
                    $item->counterparty = $counterparty;
                    return $item;
                });
        }

        $transferlists = $sentTransfers
            ->merge($receivedTransfers)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Transfer lists',
            'data' => $transferlists
        ], 200);
    }

    public function transfernow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdrew_amount' => ['required', 'numeric', 'min:0.01'],
            'to_account_number' => ['required', 'string', 'max:255'],
            'to_additional_info' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $id = Auth::id();
        $amount = (float) $request->withdrew_amount;
        $targetAccount = trim((string) $request->to_account_number);

        try {
            DB::beginTransaction();

            $user = User::where('id', $id)->where('status', 'Active')->lockForUpdate()->first();
            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'User is not active',
                ], 403);
            }

            if ((float) $user->account_balance < $amount) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Not enough balance',
                ], 422);
            }

            $traccount = $this->findTransferReceiver($targetAccount, true);
            if (!$traccount) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Receiver account not found',
                ], 404);
            }

            if ((int) $traccount->id === (int) $user->id) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot transfer to your own account',
                ], 422);
            }

            $withdrew = new Withdrew();
            $withdrew->user_id = $id;
            $withdrew->type = 'Transfer';
            $withdrew->to_account_number = $targetAccount;
            $withdrew->to_additional_info = $request->to_additional_info;
            $withdrew->withdrew_amount = $amount;
            $withdrew->status = 'Paid';
            $withdrew->save();

            $traccount->account_balance = (float) $traccount->account_balance + $amount;
            $traccount->save();

            $user->account_balance = (float) $user->account_balance - $amount;
            $user->save();

            $receiverComment = new Comment();
            $receiverComment->comment = 'Received ' . $amount . 'TK From User: ' . $user->name . ' (' . $user->email . ') Invoice ID: #IN00' . $withdrew->id;
            $receiverComment->user_id = $traccount->id;
            $receiverComment->status = 1;
            $receiverComment->type = 'Transfer';
            $receiverComment->save();

            $senderComment = new Comment();
            $senderComment->comment = 'You sent ' . $amount . 'TK Invoice ID: #IN00' . $withdrew->id;
            $senderComment->user_id = $id;
            $senderComment->status = 1;
            $senderComment->type = 'Transfer';
            $senderComment->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transfer completed successfully',
                'data' => [
                    'invoice_id' => $withdrew->id,
                    'receiver' => $traccount->name,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to complete transfer',
            ], 500);
        }
    }


    public function incomehistory()
    {
        $user = User::where('id', Auth::user()->id)->first();
        $messages = Income::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($income) {
                $order = null;
                $canUseOrderId = Schema::hasColumn('incomes', 'order_id');
                $canUseInvoiceCode = Schema::hasColumn('incomes', 'invoice_code');

                if ($canUseOrderId && !empty($income->order_id)) {
                    $order = Order::find((int) $income->order_id);
                }

                if (!$order && $canUseInvoiceCode && !empty($income->invoice_code)) {
                    $order = Order::where('invoiceID', $income->invoice_code)->first();
                }

                // Legacy fallback where invoice_id stores order primary key.
                if (!$order && !empty($income->invoice_id) && is_numeric($income->invoice_id)) {
                    $order = Order::find((int) $income->invoice_id);
                }

                if ($order) {
                    // Sum product prices from OrderProducts table
                    $totalProductPrice = Orderproduct::where('order_id', $order->id)->sum('productPrice');

                    // Add new field to array
                    $income->product_price = $totalProductPrice;
                    $income->order_invoice = $order->invoiceID;
                } else {
                    $income->product_price = 0;
                    $income->order_invoice = $canUseInvoiceCode && !empty($income->invoice_code)
                        ? $income->invoice_code
                        : $income->invoice_id;
                }

                return $income;
            });
        return response()->json([
            'status' => true,
            'message' => 'Income History',
            'data' => $messages
        ], 200);
    }


    public function referral()
    {
        $user = User::where('id', Auth::user()->id)->first();
        $messages = Message::where('user_id', $user->id)->latest()->paginate(30);
        return response()->json([
            'status' => true,
            'message' => 'Income History',
            'data' => [
                'referal_bonus' => $user->referal_bonus,
                'my_referral' => User::where('refer_by', $user->my_referral_code)->get()->count(),
                'active_member' => User::where('refer_by', $user->my_referral_code)->where('status', 'Active')->get()->count(),
                'paid_member' => User::where('refer_by', $user->my_referral_code)->where('status', 'Active')->where('membership_status', 'Paid')->get()->count(),
                'history' => $messages,
            ],
        ], 200);
    }


    public function slugorder($slug)
    {

        $total =  Order::where('user_id', Auth::user()->id)->get()->count();

        if ($slug == 'all') {
            $orders =  Order::with(
                [
                    'orderproducts' => function ($query) {
                        $query->select('id', 'order_id', 'product_id', 'productName', 'quantity', 'color', 'size');
                    },
                    'comments' => function ($query) {
                        $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                    },
                ]
            )->where('user_id', Auth::user()->id)
                ->join('customers', 'customers.order_id', '=', 'orders.id')
                ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')
                ->latest()
                ->paginate(30);
        } else {
            $orders =  Order::with(
                [
                    'orderproducts' => function ($query) {
                        $query->select('id', 'order_id', 'product_id', 'productName', 'quantity', 'color', 'size');
                    },
                    'comments' => function ($query) {
                        $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                    }
                ]
            )->where('user_id', Auth::user()->id)->where('status', $slug)
                ->join('customers', 'customers.order_id', '=', 'orders.id')
                ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')
                ->paginate(30);
        }
        return response()->json([
            'status' => true,
            'message' => 'Order History',
            'total' => $total,
            'data' => $orders,
        ], 200);
    }

    public function participateSalesTarget(Request $request)
    {
        $userId = (int) Auth::id();
        $targetId = $request->input('sales_target_id');

        if (!$targetId) {
            return response()->json([
                'status' => false,
                'message' => 'sales_target_id is required.',
            ], 422);
        }

        $activeSalesTarget = SalesTarget::query()
            ->activeNow()
            ->where('id', $targetId)
            ->first();

        if (!$activeSalesTarget) {
            return response()->json([
                'status' => false,
                'message' => 'No active challenge found with the given ID.',
            ], 404);
        }

        $participant = SalesTargetParticipant::query()->firstOrCreate(
            [
                'sales_target_id' => $activeSalesTarget->id,
                'user_id' => $userId,
            ],
            [
                'joined_at' => now(),
            ]
        );

        $joinedNow = false;
        if ($participant->wasRecentlyCreated) {
            $joinedNow = true;
        } elseif (!$participant->joined_at) {
            $participant->joined_at = now();
            $participant->save();
            $joinedNow = true;
        }

        $salesTargetProgress = $activeSalesTarget->getProgressForUser($userId);

        return response()->json([
            'status' => true,
            'message' => $joinedNow
                ? 'Challenge participation successful.'
                : 'You already joined this challenge.',
            'data' => [
                'active_sales_target' => $activeSalesTarget,
                'sales_target_progress' => $salesTargetProgress,
                'sales_target_participation' => [
                    'joined' => true,
                    'joined_at' => $participant->joined_at,
                    'reward_claimed' => !is_null($participant->reward_claimed_at),
                    'reward_claimed_at' => $participant->reward_claimed_at,
                    'can_claim' => empty($participant->reward_claimed_at) && !empty($salesTargetProgress['completed']),
                ],
            ],
        ], 200);
    }

    public function claimSalesTargetReward(Request $request)
    {
        $userId = (int) Auth::id();
        $targetId = $request->input('sales_target_id');

        if (!$targetId) {
            return response()->json([
                'status' => false,
                'message' => 'sales_target_id is required.',
            ], 422);
        }

        $activeSalesTarget = SalesTarget::query()
            ->activeNow()
            ->where('id', $targetId)
            ->first();

        if (!$activeSalesTarget) {
            return response()->json([
                'status' => false,
                'message' => 'No active challenge found with the given ID.',
            ], 404);
        }

        $participant = SalesTargetParticipant::query()
            ->where('sales_target_id', $activeSalesTarget->id)
            ->where('user_id', $userId)
            ->first();

        if (!$participant || !$participant->joined_at) {
            return response()->json([
                'status' => false,
                'message' => 'Please participate in the challenge first.',
            ], 422);
        }

        if (!is_null($participant->reward_claimed_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Reward already claimed for this challenge.',
            ], 409);
        }

        $salesTargetProgress = $activeSalesTarget->getProgressForUser($userId);

        if (empty($salesTargetProgress['completed'])) {
            return response()->json([
                'status' => false,
                'message' => 'Target not completed yet. Complete the challenge first.',
            ], 422);
        }

        $participant->completed_at = $participant->completed_at ?: now();
        $participant->reward_claimed_at = now();
        $participant->achieved_value = (float) ($salesTargetProgress['achieved'] ?? 0);
        $participant->progress_percent = (float) ($salesTargetProgress['progress_percent'] ?? 0);
        $participant->claimed_reward_type = $activeSalesTarget->reward_type;
        $participant->claimed_reward_value = $activeSalesTarget->reward_value;
        $participant->claimed_reward_note = $activeSalesTarget->reward_note;
        $participant->save();

        return response()->json([
            'status' => true,
            'message' => 'Reward claimed successfully.',
            'data' => [
                'active_sales_target' => $activeSalesTarget,
                'sales_target_progress' => $salesTargetProgress,
                'sales_target_participation' => [
                    'joined' => true,
                    'joined_at' => $participant->joined_at,
                    'reward_claimed' => true,
                    'reward_claimed_at' => $participant->reward_claimed_at,
                    'can_claim' => false,
                ],
            ],
        ], 200);
    }

    public function dashboarddata()
    {
        $id = (int) Auth::id();

        $myorders = Order::where('user_id', $id)->get()->groupBy('orderDate');
        $sales = [];
        foreach ($myorders as $key => $myorder) {
            $sales[] = array(
                'y' => $myorder->sum('subTotal') + $myorder->sum('paymentAmount'),
                'label' => $key,
            );
        }

        $activeSalesTargets = SalesTarget::query()
            ->activeNow()
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();

        $salesTargetsData = $activeSalesTargets->map(function ($target) use ($id) {
            $progress = $target->getProgressForUser($id);

            $participant = SalesTargetParticipant::query()
                ->where('sales_target_id', $target->id)
                ->where('user_id', $id)
                ->first();

            $participation = [
                'joined' => !is_null($participant),
                'joined_at' => optional($participant)->joined_at,
                'reward_claimed' => !is_null(optional($participant)->reward_claimed_at),
                'reward_claimed_at' => optional($participant)->reward_claimed_at,
                'can_claim' => !is_null($participant)
                    && empty(optional($participant)->reward_claimed_at)
                    && !empty($progress['completed']),
            ];

            return [
                'target' => $target,
                'progress' => $progress,
                'participation' => $participation,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Dashboard data',
            'data' => [
                'total_sales' => Order::where('user_id', $id)->where('status', '!=', 'Canceled')->get()->sum('subTotal') + Order::where('user_id', $id)->where('status', '!=', 'Canceled')->get()->sum('paymentAmount') - Order::where('user_id', $id)->where('status', '!=', 'Canceled')->get()->sum('deliveryCharge'),
                'total_profit' => Order::where('user_id', $id)->where('status', 'Delivered')->get()->sum('profit'),
                'blance' => Auth::user()->account_balance,
                'withdraw' => Auth::user()->cashout_balance,
                'shop_products' => Shopproduct::where('user_id', $id)->get()->count(),
                'total_orders' => Order::where('user_id', $id)->get()->count(),
                'sales' => $sales,
                'active_sales_targets' => $salesTargetsData,
            ],
        ], 200);
    }

    public function shopproducts()
    {
        $products = Shopproduct::where('user_id', Auth::user()->id)->get();
        return response()->json([
            'status' => true,
            'message' => 'Shop product list',
            'data' => $products
        ], 200);
    }

    public function addtoshop($id)
    {
        $ex = Shopproduct::where('product_id', $id)->where('user_id', Auth::user()->id)->first();
        if (isset($ex)) {
            return response()->json([
                'status' => false,
                'message' => 'Already exist this product to your shop',
            ], 404);
        } else {
            $add = new Shopproduct();
            $add->user_id = Auth::user()->id;
            $add->product_id = $id;
            $add->save();
            return response()->json([
                'status' => true,
                'message' => 'Product successfully add to shop',
                'data' => $add
            ], 200);
        }
    }

    public function removefromshop($id)
    {
        $ex = Shopproduct::where('product_id', $id)->where('user_id', Auth::user()->id)->first();
        if (isset($ex)) {
            $ex->delete();
            return response()->json([
                'status' => true,
                'message' => 'Product removed from shop',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Product not found in your shop',
            ], 404);
        }
    }

    // guest cart

    public function guestAddToCart(Request $request)
    {
        $pid = $request->product_id;
        $cartProduct = Product::where('id', $pid)->first();
        if (!$cartProduct) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $cart = Cart::updateOrCreate(
            [
                'session_id' => $request->session_id,
                'product_id' => $request->product_id,
            ],
            [
                'product_id' => $request->product_id,
                'name' => $cartProduct->ProductName,
                'code' => $cartProduct->ProductSku,
                'price' => $request->price,
                'qty' => $request->qty,
                'shop_id' => $cartProduct->shop_id,
                'image' => $cartProduct->ProductImage,
                'options' => [
                    'size' => $request->size,
                    'color' => $request->color,
                    'image' => $cartProduct->ProductImage,
                    'code' => $cartProduct->ProductSku,
                ],
                'session_id' => $request->session_id,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Added to Cart Successfully',
            'data' => $cart,
        ], 200);
    }

    public function guestUpdateCart(Request $request)
    {
        $cart = Cart::where('session_id', $request->session_id)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cart->update([
            'qty' => $request->qty ?? $cart->qty, // Update quantity or keep it as is
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully',
            'data' => [
                'qty' => $cart->qty,
            ],
        ], 200);
    }

    public function guestDestroyCart(Request $request)
    {
        // Find the cart item based on IP address and product ID
        $cart = Cart::where('session_id', $request->session_id)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        // Remove the specific cart item
        $cart->delete();

        // Check if there are any remaining items in the cart
        $remainingItems = Cart::where('session_id', $request->session_id)->count();

        if ($remainingItems === 0) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is now empty',
            ], 200);
        }

        // Fetch updated cart items
        $cartProducts = Cart::where('session_id', $request->session_id)->get();

        return response()->json([
            'status' => true,
            'message' => 'Cart item removed successfully',
            'data' => $cartProducts,
        ], 200);
    }

    public function guestCartContent(Request $request)
    {
        $carts = Cart::where('session_id', $request->session_id)->get();


        if (!$carts) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart item found Successfully',
            'data' => $carts
        ], 200);
    }

    // user add cart

    public function userAddToCart(Request $request)
    {
        $pid = $request->product_id;
        $cartProduct = Product::where('id', $pid)->first();
        if (!$cartProduct) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        // Server-side Price Validation
        $submittedPrice = (float) $request->price;
        $minAllowedPrice = (float) $cartProduct->ProductResellerPrice;

        if ($submittedPrice < $minAllowedPrice) {
            return response()->json([
                'status' => false,
                'message' => 'Selling price (' . number_format($submittedPrice, 2) . ') cannot be lower than the product price (' . number_format($minAllowedPrice, 2) . ').'
            ], 422);
        }

        $cart = Cart::updateOrCreate(
            [
                'session_id' => $request->session_id,
                'product_id' => $request->product_id,
                'size' => $request->size,
                'color' => $request->color,
            ],
            [
                'product_id' => $request->product_id,
                'name' => $cartProduct->ProductName,
                'code' => $cartProduct->ProductSku,
                'price' => $request->price,
                'qty' => $request->qty,
                'size' => $request->size,
                'color' => $request->color,
                'shop_id' => $cartProduct->shop_id,
                'image' => $cartProduct->ProductImage,
                'options' => [
                    'size' => $request->size,
                    'color' => $request->color,
                    'image' => $cartProduct->ProductImage,
                    'code' => $cartProduct->ProductSku,
                ],
                'user_id' => Auth::user()->id,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Added to Cart Successfully',
            'data' => $cart,
        ], 200);
    }

    public function userUpdateCart(Request $request)
    {
        $cart = Cart::where('id', $request->cart_id)
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cart->update([
            'qty' => $request->qty ?? $cart->qty, // Update quantity or keep it as is
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully',
            'data' => [
                'qty' => $cart->qty,
            ],
        ], 200);
    }

    public function userDestroyCart(Request $request)
    {
        // Find the cart item based on IP address and product ID
        $cart = Cart::where('id', $request->cart_id)
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        // Remove the specific cart item
        $cart->delete();

        // Check if there are any remaining items in the cart
        $remainingItems = Cart::where('user_id', Auth::user()->id)->count();

        if ($remainingItems === 0) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is now empty',
            ], 200);
        }

        // Fetch updated cart items
        $cartProducts = Cart::where('user_id', Auth::user()->id)->get();

        return response()->json([
            'status' => true,
            'message' => 'Cart item removed successfully',
            'data' => $cartProducts,
        ], 200);
    }

    public function userCartContent(Request $request)
    {
        $carts = Cart::where('user_id', Auth::user()->id)->get();

        $cartgroups = Cart::where('user_id', Auth::id())
            ->get()
            ->groupBy('product_id');
        $extdv = 0;
        foreach ($cartgroups as $product_id => $items) {
            $product = Product::find($product_id);
            if ($product->bulk_status == 'on') {
                $qtycrtgrp = Cart::where('user_id', Auth::user()->id)->where('product_id', $product_id)->sum('qty');
                $variant = Varient::where('product_id', $product_id)
                    ->where('qty', '>=', $qtycrtgrp)
                    ->orderBy('qty', 'asc')
                    ->first();
                $extdv += ($variant->extra_delivery_charge * $qtycrtgrp);
            } else {
            }
        }

        if (!$carts) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart item found Successfully',
            'data' => $carts,
            'extra_delivery_charge' => $extdv,
        ], 200);
    }


    public function viewbulkprice(Request $request)
    {
        $carts = Cart::where('user_id', Auth::user()->id)->where('product_id', $request->product_id)->sum('qty');

        if (!$carts) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found',
            ], 404);
        }
        $variant = Varient::where('product_id', $request->product_id)
            ->where('qty', '>=', $carts)
            ->orderBy('qty', 'asc')
            ->first();
        $cartdatas = Cart::where('user_id', Auth::user()->id)->where('product_id', $request->product_id)->get();

        foreach ($cartdatas as $cartdata) {
            $cartdata->price = $variant->price;
            $cartdata->update();
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart item found Successfully',
            'data' => [
                'total' => $carts,
                'price_per_pice' => $variant->price,
                'total_price' => $carts * $variant->price,
            ]
        ], 200);
    }

    public function orderNow(Request $request)
    {
        // Validate required fields
        $request->validate([
            'customerName' => 'required|string',
            'customerPhone' => 'required|string',
            'customerAddress' => 'required|string',
            'subTotal' => 'required|numeric',
            'deliveryCharge' => 'required|numeric',
        ]);

        $shopproducts = Cart::where('user_id', Auth::user()->id)
            ->get()
            ->groupBy('shop_id');

        if ($shopproducts->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart is empty'
            ], 400);
        }

        if ($request->balance_from == 'online_pay') {

            $shop = count($shopproducts);
            $chargeamount = $shop * $request->deliveryCharge;
            $post_data = array();
            $post_data['total_amount'] = 10; # You cant not pay less than 10
            $post_data['currency'] = "BDT";
            $post_data['tran_id'] = uniqid(); // tran_id must be unique

            # CUSTOMER INFORMATION
            $post_data['cus_name'] = $request->customerName;
            $post_data['cus_email'] = 'customer@mail.com';
            $post_data['cus_add1'] = $request->customerAddress;
            $post_data['cus_add2'] = "";
            $post_data['cus_city'] = "";
            $post_data['cus_state'] = "";
            $post_data['cus_postcode'] = "";
            $post_data['cus_country'] = "Bangladesh";
            $post_data['cus_phone'] =  $request->customerPhone;
            $post_data['cus_fax'] = "";

            # SHIPMENT INFORMATION
            $post_data['ship_name'] = $request->customerName;
            $post_data['ship_add1'] =  $request->customerAddress;
            $post_data['ship_add2'] = "";
            $post_data['ship_city'] = "";
            $post_data['ship_state'] = "";
            $post_data['ship_postcode'] = "";
            $post_data['ship_phone'] = "";
            $post_data['ship_country'] = "Bangladesh";

            $post_data['shipping_method'] = "NO";
            $post_data['product_name'] = "Products";
            $post_data['product_category'] = "Goods";
            $post_data['product_profile'] = "physical-goods";

            #Before  going to initiate the payment order status need to update as Pending.
            $update_product = DB::table('orders')
                ->where('transaction_id', $post_data['tran_id'])
                ->updateOrInsert([
                    'store_id' => 1,
                    'invoiceID' => $this->uniqueID(),
                    'subTotal' => $request->subTotal,
                    'deliveryCharge' => $request->deliveryCharge,
                    'data' => json_encode($request),
                    'cart' => json_encode($shopproducts),
                    'orderDate' => date('Y-m-d'),
                    'courier_id' => 26,
                    'transaction_id' => $post_data['tran_id'],
                    'user_id' => Auth::id(),
                    'status' => 'Pending',

                ]);

            $sslc = new SslCommerzNotification();
            # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
            return  $payment_options = $sslc->makePayment($post_data, 'checkout', 'json');

            if (!is_array($payment_options)) {
                print_r($payment_options);
                $payment_options = array();
            }
        }

        // Get cart items for this session


        $ordersCreated = [];

        foreach ($shopproducts as $shopproduct) {

            // Assign an active executive admin
            $admin = Admin::whereHas('roles', function ($q) {
                $q->where('name', 'Executive');
            })
                ->where('add_by', 1)
                ->where('status', 'Active')
                ->inRandomOrder()
                ->first();

            $order = new Order();
            $buy = $bonus = $sellprice = 0;

            foreach ($shopproduct as $product) {
                $productData = Product::find($product->product_id);
                $sellprice += $product->price * $product->qty;
                $buy += $productData->ProductResellerPrice * $product->qty;
                $bonus += $productData->reseller_bonus;
            }

            $order->profit = $sellprice - $buy;
            $order->order_bonus = $bonus;
            $order->user_id = Auth::id() ?? null; // if using API auth
            $order->courier_id = 26;
            $order->store_id = $shopproduct[0]->shop_id;
            $order->invoiceID = $this->uniqueIDN();
            $order->subTotal = $request->subTotal;
            $order->deliveryCharge = $request->deliveryCharge;
            $order->customerNote = $request->customerNote ?? null;
            $order->status = 'Pending';

            if ($request->balance_from == 'from_account') {
                $order->paymentAmount = $request->deliveryCharge;
                $order->payment_type_id = 5;
            }

            $order->orderDate = Carbon::today()->format('Y-m-d');
            $order->admin_id = $admin->id ?? 1;

            $result = $order->save();

            if (!$result) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create order'
                ], 500);
            }

            // Save customer info
            $customer = new Customer();
            $customer->order_id = $order->id;
            $customer->customerName = $request->customerName;
            $customer->customerPhone = $request->customerPhone;
            $customer->customerAddress = $request->customerAddress;
            $customer->save();

            // Save order products
            $vendorIds = [];
            foreach ($shopproduct as $product) {
                $orderProduct = new Orderproduct();
                $orderProduct->order_id = $order->id;
                $orderProduct->product_id = $product->product_id;
                $orderProduct->productCode = $product->code;
                $orderProduct->productName = $product->name;
                $orderProduct->quantity = $product->qty;
                $orderProduct->productPrice = $product->price;

                if (!empty($product->options['color']) && $product->options['color'] != 'undefined') {
                    $orderProduct->color = $product->options['color'];
                }

                if (!empty($product->options['size']) && $product->options['size'] != 'undefined') {
                    $orderProduct->size = $product->options['size'];
                }

                $orderProduct->save();

                $vendorId = Product::where('id', $product->product_id)->value('vendor_id');
                if ($vendorId) {
                    $vendorIds[(int) $vendorId] = true;
                }
            }

            // Deduct account balance if needed
            if ($request->balance_from == 'from_account') {
                $accountuser = User::find(Auth::id());
                if ($accountuser) {
                    $accountuser->account_balance -= $request->deliveryCharge;
                    $accountuser->save();
                    $chargededucts = new Chargededuct();
                    $chargededucts->user_id = $accountuser->id;
                    $chargededucts->comment = 'You have charged ' . $request->deliveryCharge . ' TK for delivery charge.';
                    $chargededucts->amount = $request->deliveryCharge;
                    $chargededucts->status = 'Success';
                    $chargededucts->save();
                }
            }

            // Notification
            $notification = new Comment();
            $notification->order_id = $order->id;
            $notification->comment = $order->invoiceID . ' Order has been created for ' . ($admin->name ?? 'Admin');
            $notification->admin_id = $order->admin_id;
            $notification->save();

            if (!empty($vendorIds)) {
                /** @var VendorAdminNotificationService $vendorNotification */
                $vendorNotification = app(VendorAdminNotificationService::class);

                foreach (array_keys($vendorIds) as $vendorId) {
                    $vendorNotification->notifyVendorById(
                        (int) $vendorId,
                        'New order received',
                        'Order ' . $order->invoiceID . ' is pending your action (accept or reject).',
                        'info',
                        [
                            'event' => 'vendor_order_created',
                            'order_id' => $order->id,
                            'invoiceID' => $order->invoiceID,
                        ],
                        '/vendor/orders/' . $order->id
                    );
                }
            }

            $ordersCreated[] = [
                'order_id' => $order->id,
                'invoiceID' => $order->invoiceID
            ];
        }

        // Clear cart
        Cart::where('user_id', Auth::user()->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Order placed successfully',
            'orders' => $ordersCreated
        ], 200);
    }

    public function orderByinvoice($id)
    {
        $rawInvoiceId = trim((string) $id);
        $invoiceId = $this->normalizeInvoiceId((string) $id);

        if (!$invoiceId) {
            return response()->json([
                'status' => false,
                'message' => 'No order found with this invoice id',
            ], 404);
        }

        $orders = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])
            ->where(function ($q) use ($rawInvoiceId, $invoiceId) {
                $q->where('invoiceID', $invoiceId);
                if ($rawInvoiceId !== '' && $rawInvoiceId !== $invoiceId) {
                    $q->orWhere('invoiceID', $rawInvoiceId);
                }
            })
            ->first();

        if ($orders) {
            $orders = $this->hydrateOrderStatus($orders, true);
            return response()->json([
                'status' => true,
                'message' => 'Order found succesfully',
                'data' => $orders
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'No order found with this invoice id',
        ], 404);
    }

    public function uniqueIDN()
    {
        $lastOrder = Order::latest('id')->first();
        if ($lastOrder) {
            $orderID = $lastOrder->id + 1;
        } else {
            $orderID = 1;
        }

        return 'SS00' . $orderID;
    }

    public function userNotification(Request $request)
    {
        $user = Auth::user();
        $perPage = min(max((int) $request->input('per_page', 20), 5), 100);
        $unreadOnly = $request->boolean('unread_only', false);
        $page = max((int) $request->input('page', 1), 1);

        // Only user-facing notifications should appear in the user dashboard.
        // Vendor panel notifications are handled by the vendor notification API.
        $allUserNotifications = $user->notifications()
            ->where('type', AdminBroadcastNotification::class)
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn(DatabaseNotification $notification) => $this->isUserAudienceNotification($notification))
            ->values();

        if ($unreadOnly) {
            $allUserNotifications = $allUserNotifications
                ->filter(fn(DatabaseNotification $notification) => $notification->read_at === null)
                ->values();
        }

        $total = $allUserNotifications->count();
        $lastPage = (int) max(1, ceil($total / $perPage));
        $items = $allUserNotifications->slice(($page - 1) * $perPage, $perPage)->values();

        $notifications = $items
            ->map(fn(DatabaseNotification $notification) => $this->transformUserNotification($notification))
            ->values();

        return response()->json([
            'status' => true,
            'message' => $notifications->isEmpty() ? 'Notification Empty' : 'Notification Found',
            'data' => $notifications,
            'unread_count' => $this->countUnreadUserAudienceNotifications($user),
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ], 200);
    }

    public function markUserNotificationRead(string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()
            ->where('type', AdminBroadcastNotification::class)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        if (!$this->isUserAudienceNotification($notification)) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read',
            'unread_count' => $this->countUnreadUserAudienceNotifications($user->fresh()),
        ], 200);
    }

    public function markAllUserNotificationsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications()
            ->where('type', AdminBroadcastNotification::class)
            ->get()
            ->filter(fn(DatabaseNotification $notification) => $this->isUserAudienceNotification($notification))
            ->each(fn(DatabaseNotification $notification) => $notification->markAsRead());

        return response()->json([
            'status' => true,
            'message' => 'All notifications marked as read',
            'unread_count' => $this->countUnreadUserAudienceNotifications($user->fresh()),
        ], 200);
    }

    private function transformUserNotification(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $title = $data['title'] ?? 'Notification';
        $message = $data['message'] ?? ($data['description'] ?? '');
        $link = $data['action_url'] ?? ($data['link'] ?? ($data['url'] ?? null));
        $image = $data['image_url'] ?? ($data['image'] ?? null);

        return [
            'id' => $notification->id,
            'title' => $title,
            'description' => $message,
            'message' => $message,
            'image' => $image,
            'image_url' => $image,
            'link' => $link,
            'url' => $link,
            'type' => $data['type'] ?? 'notification',
            'is_read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'meta' => $data['meta'] ?? [],
        ];
    }

    private function isUserAudienceNotification(DatabaseNotification $notification): bool
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $audienceType = (string) ($data['audience_type'] ?? '');

        return $audienceType !== 'supplier';
    }

    private function countUnreadUserAudienceNotifications(User $user): int
    {
        return $user->unreadNotifications()
            ->where('type', AdminBroadcastNotification::class)
            ->get()
            ->filter(fn(DatabaseNotification $notification) => $this->isUserAudienceNotification($notification))
            ->count();
    }

    /**
 * Return approved vendors ordered by average product rating (from reviews).
 */
public function popularVendors()
{
    $vendors = Vendor::where('status', 'approved')
        ->withCount('products')
        ->get([
            'id',
            'user_id',
            'company_name',
            'slug',
            'approval_type',
            'logo_path',
            'banner_path',
            'business_type',
            'city',
            'is_verified_badge',
        ]);

    // Compute average rating across all products for each vendor
    foreach ($vendors as $vendor) {
        $productIds = Product::where('vendor_id', $vendor->id)->pluck('id');
        $reviews = Review::whereIn('product_id', $productIds)
            ->where('status', 'Active');
        $vendor->avg_product_rating = round($reviews->avg('rating') ?? 0, 1);
        $vendor->review_count = $reviews->count();

        // Mask vendor identity for privately approved suppliers
        $vendor->company_name = $vendor->public_name;
        $vendor->slug = $vendor->public_slug;
    }

    // Sort by average rating descending, then by product count
    $sorted = $vendors->sortByDesc('avg_product_rating')->values();

    return response()->json([
        'status' => true,
        'message' => 'Popular vendors',
        'data'   => $sorted,
    ]);
}

    /**
     * Return a single approved vendor's profile + their paginated products.
     * Supports ?category={id} query param for category filtering.
     */
    public function supplierDetails(Request $request, string $slug)
    {
        // Try matching by real slug first, then by private ID slug pattern (ss-XXXXX)
        $vendor = Vendor::where('status', 'approved')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                // Match private vendors by their generated slug pattern (ss-XXXXX)
                if (preg_match('/^ss-(\d+)$/i', $slug, $matches)) {
                    $vendorId = (int) ltrim($matches[1], '0');
                    $q->orWhere(function ($sub) use ($vendorId) {
                        $sub->where('id', $vendorId)->where('approval_type', 'private');
                    });
                }
            })
            ->withCount('products')
            ->first();

        if (!$vendor) {
            return response()->json([
                'status'  => false,
                'message' => 'Supplier not found',
            ], 404);
        }

        // Distinct categories that this vendor's products belong to
        $categoryIds = Product::visibleOnStorefront()
            ->where('vendor_id', $vendor->id)
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id');

        $categories = Category::whereIn('id', $categoryIds)
            ->select('id', 'category_name', 'slug')
            ->orderBy('category_name')
            ->get();

        // Products query — optionally filtered by category
        $productsQuery = Product::visibleOnStorefront()
            ->where('vendor_id', $vendor->id)
            ->select(
                'id',
                'ProductName',
                'ProductSlug',
                'ViewProductImage',
                'ProductRegularPrice',
                'ProductSalePrice',
                'Discount',
                'category_id',
                'status'
            );

        if ($request->filled('category')) {
            $productsQuery->where('category_id', $request->input('category'));
        }

        $products = $productsQuery->latest()->paginate(12);

        // Compute vendor average product rating
        $allProductIds = Product::where('vendor_id', $vendor->id)->pluck('id');
        $vendorReviews = Review::whereIn('product_id', $allProductIds)->where('status', 'Active');
        $vendor->avg_product_rating = round($vendorReviews->avg('rating') ?? 0, 1);
        $vendor->review_count = $vendorReviews->count();

        // Mask vendor identity for privately approved suppliers
        $vendor->company_name = $vendor->public_name;
        $vendor->slug = $vendor->public_slug;

        return response()->json([
            'status'  => true,
            'message' => 'Supplier details',
            'data'    => [
                'vendor'     => $vendor,
                'categories' => $categories,
                'products'   => $products,
            ],
        ]);
    }

    public function flashSale()
    {
        $flashSale = FlashSale::active()
            ->with(['flashSaleProducts.product'])
            ->orderBy('end_time', 'asc')
            ->first();

        if (!$flashSale) {
            return response()->json([
                'status' => false,
                'message' => 'No active flash sale',
                'data' => null,
            ]);
        }

        $products = $flashSale->flashSaleProducts->map(function ($fsp) {
            $product = $fsp->product;
            if (!$product) return null;

            $regularPrice = floatval($product->ProductRegularPrice ?? 0);
            $salePrice = floatval($product->ProductSalePrice ?? $regularPrice);
            $discount = floatval($fsp->discount_percentage);
            $flashPrice = $discount > 0
                ? round($salePrice * (1 - $discount / 100), 2)
                : $salePrice;

            return [
                'id' => $product->id,
                'ProductName' => $product->ProductName,
                'ProductSlug' => $product->ProductSlug,
                'ViewProductImage' => $product->ViewProductImage,
                'RegularPrice' => $regularPrice,
                'SalePrice' => $salePrice,
                'FlashPrice' => $flashPrice,
                'discount_percentage' => $discount,
            ];
        })->filter()->values();

        return response()->json([
            'status' => true,
            'message' => 'Active flash sale',
            'data' => [
                'id' => $flashSale->id,
                'title' => $flashSale->title,
                'start_time' => $flashSale->start_time,
                'end_time' => $flashSale->end_time,
                'products' => $products,
            ],
        ]);
    }

    // ── Product Review System ──

    /**
     * Store a product review. Only allowed if the user has a delivered order
     * containing the product and has not already reviewed it.
     */
    public function reviewStore(Request $request)
    {
        $userId = Auth::id();
        $productId = $request->input('product_id');

        // Check if user already reviewed this product
        $existing = Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'You have already submitted a review for this product',
            ], 422);
        }

        // Verify user has a delivered order with this product
        $deliveredOrder = Order::where('user_id', $userId)
            ->where('status', 'Delivered')
            ->whereHas('orderproducts', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->first();

        if (!$deliveredOrder) {
            return response()->json([
                'status' => false,
                'message' => 'You can only review products from delivered orders',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'rating'     => 'required|numeric|min:1|max:5',
            'messages'   => 'nullable|string|max:1000',
            'file'       => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $review = new Review();
        $review->user_id = $userId;
        $review->product_id = $productId;
        $review->order_id = $deliveredOrder->id;
        $review->messages = $request->messages;
        $review->rating = $request->rating;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $name = time() . '_' . $file->getClientOriginalName();
            $uploadPath = 'public/images/admin/profile/';
            $file->move($uploadPath, $name);
            $review->file = $uploadPath . $name;
        }

        $review->save();

        return response()->json([
            'status' => true,
            'message' => 'Review submitted successfully',
            'data' => $review,
        ], 200);
    }

    /**
     * Return products from delivered orders that the user hasn't yet reviewed.
     */
    public function reviewableProducts()
    {
        $userId = Auth::id();

        // Get all product IDs the user has already reviewed
        $reviewedProductIds = Review::where('user_id', $userId)->pluck('product_id')->toArray();

        // Get delivered orders with their products
        $deliveredOrders = Order::where('user_id', $userId)
            ->where('status', 'Delivered')
            ->with(['orderproducts.product:id,ProductName,ProductSlug,ViewProductImage'])
            ->orderByDesc('updated_at')
            ->get();

        $reviewable = [];
        $seenProducts = [];

        foreach ($deliveredOrders as $order) {
            foreach ($order->orderproducts as $op) {
                $pid = $op->product_id;
                if (in_array($pid, $reviewedProductIds) || in_array($pid, $seenProducts)) {
                    continue;
                }
                $seenProducts[] = $pid;
                $reviewable[] = [
                    'product_id'    => $pid,
                    'product_name'  => $op->product->ProductName ?? $op->productName,
                    'product_slug'  => $op->product->ProductSlug ?? null,
                    'product_image' => $op->product->ViewProductImage ?? null,
                    'order_id'      => $order->id,
                    'invoice_id'    => $order->invoiceID,
                    'delivery_date' => $order->deliveryDate,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => count($reviewable) > 0 ? 'Reviewable products found' : 'No products to review',
            'data' => $reviewable,
        ], 200);
    }

    /**
     * Check if the current user has already reviewed a product.
     */
    public function checkUserReview($productId)
    {
        $userId = Auth::id();

        $review = Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        // Also check if the user is eligible to review (has a delivered order)
        $canReview = Order::where('user_id', $userId)
            ->where('status', 'Delivered')
            ->whereHas('orderproducts', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();

        return response()->json([
            'status' => true,
            'data' => [
                'has_reviewed' => $review !== null,
                'can_review'   => $canReview && $review === null,
                'review'       => $review,
            ],
        ], 200);
    }

    /**
     * Get all active reviews for a product (public).
     */
    public function getProductReviews($productId)
    {
        $reviews = Review::where('status', 'Active')
            ->where('product_id', $productId)
            ->with(['user:id,name,email,profile'])
            ->orderByDesc('created_at')
            ->get();

        $avgRating = $reviews->avg('rating') ?? 0;

        return response()->json([
            'status' => true,
            'message' => $reviews->count() > 0 ? 'Reviews found' : 'No reviews yet',
            'data' => [
                'reviews' => $reviews,
                'review_count' => $reviews->count(),
                'average_rating' => round($avgRating, 1),
            ],
        ], 200);
    }

    /**
     * Update an existing review (only the owner can update).
     */
    public function updateReview(Request $request, $reviewId)
    {
        $userId = Auth::id();

        $review = Review::where('id', $reviewId)
            ->where('user_id', $userId)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found or you are not authorized to edit it',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rating'   => 'required|numeric|min:1|max:5',
            'messages' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $review->rating = $request->rating;
        $review->messages = $request->messages;
        $review->save();

        return response()->json([
            'status' => true,
            'message' => 'Review updated successfully',
            'data' => $review,
        ], 200);
    }
}
