<?php

namespace App\Http\Controllers;

use App\Models\Addbanner;
use App\Models\Admin;
use App\Models\Announcement;
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
use App\Models\FlashSaleProduct;
use App\Models\Fraud;
use App\Models\Income;
use App\Models\Message;
use App\Models\Minicategory;
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
use App\Models\VariantSize;
use App\Models\Vendor;
use App\Models\VendorFollower;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Notifications\DatabaseNotification;
use Str;

class FrontendApiController extends Controller
{

    public function contactInfo()
    {
        $info = Basicinfo::first();
        return response()->json([
            'status' => true,
            'data' => [
                'phone_one'      => $info->phone_one ?? null,
                'phone_two'      => $info->phone_two ?? null,
                'email'          => $info->email ?? null,
                'address'        => $info->address ?? null,
                'wp_number'      => $info->wp_number ?? null,
                'wp_link'        => $info->wp_link ?? null,
                'messanger_link' => $info->messanger_link ?? null,
                'facebook'       => $info->facebook ?? null,
                'instagram'      => $info->linkedin ?? null,  // stored as linkedin in DB
                'youtube'        => $info->youtube ?? null,
                'tiktok'         => $info->rss ?? null,       // stored as rss in DB
            ],
        ], 200);
    }

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

    public function promotionalSections()
    {
        $sections = \App\Models\PromotionalSection::active()
            ->orderBy('sort_order')
            ->with(['products' => function ($query) {
                $query->where('status', 'Active')
                    ->select('products.id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'min_sell_price', 'Discount', 'ViewProductImage', 'vendor_id', 'category_id', 'selling_type')
                    ->orderByPivot('sort_order');
            }])
            ->get()
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'slug' => $section->slug,
                    'banner_image' => $section->banner_image,
                    'layout_type' => $section->layout_type ?? 'card',
                    'bg_color' => $section->bg_color,
                    'products' => $section->products,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Promotional sections',
            'data' => $sections,
        ], 200);
    }

    public function promotionalSectionBySlug(Request $request, $slug)
    {
        $section = \App\Models\PromotionalSection::where('slug', $slug)->first();

        if (!$section) {
            return response()->json([
                'status' => false,
                'message' => 'Section not found',
                'data' => [],
            ], 404);
        }

        $limit = $request->input('limit', 20);

        $products = $section->products()
            ->where('status', 'Active')
            ->select('products.id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'min_sell_price', 'Discount', 'ViewProductImage', 'vendor_id', 'category_id', 'selling_type')
            ->paginate($limit);

        return response()->json([
            'status' => true,
            'message' => $section->title,
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
                'slug' => $section->slug,
                'banner_image' => $section->banner_image,
            ],
            'data' => $products,
        ], 200);
    }

    public function collection(Request $request, $slug)
    {
        $limit = $request->input('limit', 15);
        $sort = $request->input('sort', 'rating');
        $total = 0;
        $searchcontents = null;
        $title = 'Products';
        $query = null;
        $productSelects = [
            'id',
            'ProductName',
            'ProductSlug',
            'ProductRegularPrice',
            'ProductSalePrice',
            'ProductResellerPrice',
            'min_sell_price',
            'Discount',
            'ViewProductImage',
            'vendor_id',
            'category_id',
            'selling_type',
            'created_at',
        ];

        if ($slug == 'hot_selling') {
            $title = 'Hot Selling Products';
            $query = Product::visibleOnStorefront()->where('hot_list', 'On');
        } elseif ($slug == 'ready_to_bost') {
            $title = 'Ready To Bost Products';
            $query = Product::visibleOnStorefront()->where('ready_bost', 'On');
        } elseif ($slug == 'profitable_product') {
            $title = 'Profitable Products';
            $query = Product::visibleOnStorefront()->where('profitable', 'On');
        } elseif ($slug == 'new_arrivel') {
            $title = 'New Arrivel Products';
            $query = Product::visibleOnStorefront()->where('show_new_product', 'On');
        } elseif ($slug == 'limited_offer') {
            $title = 'Limited Offer Products';
            $query = Product::visibleOnStorefront()->where('limited', 'On');
        } elseif ($slug == 'summer_collection') {
            $title = 'Summer Collection Products';
            $query = Product::visibleOnStorefront()->where('summer', 'On');
        }

        if ($query !== null) {
            $total = (clone $query)->count();
            $searchcontents = $this->applyProductSort($query->select($productSelects), $sort)->paginate($limit);
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
        $limit = $request->input('limit', 15);
        $sort = $request->input('sort', 'newest');
        $query = Product::visibleOnStorefront()->where('show_new_product', 'On');
        $total = (clone $query)->count();

        $searchcontents = $this->applyProductSort(
            $query->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'min_sell_price', 'Discount', 'ViewProductImage', 'vendor_id', 'category_id', 'selling_type', 'created_at'),
            $sort
        )->paginate($limit);

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

    protected function applyProductSort($query, $sort)
    {
        switch ($sort) {
            case 'newest':
                return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
            case 'oldest':
                return $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
            case 'price_asc':
                return $query->orderBy('ProductSalePrice', 'asc')->orderBy('id', 'desc');
            case 'price_desc':
                return $query->orderBy('ProductSalePrice', 'desc')->orderBy('id', 'desc');
            case 'rating':
            default:
                return $query->selectSub(
                    Review::selectRaw('COALESCE(AVG(rating), 0)')
                        ->whereColumn('product_id', 'products.id')
                        ->where('status', 'Active'),
                    'avg_rating'
                )->orderBy('avg_rating', 'desc')->orderBy('id', 'desc');
        }
    }

    public function newproducts(Request $request)
    {
        $limit = $request->limit ?? 15;
        $total = Product::visibleOnStorefront()->count();

        $searchcontents = Product::visibleOnStorefront()->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'min_sell_price', 'Discount', 'ViewProductImage', 'vendor_id', 'category_id', 'selling_type')->latest('id')->paginate($limit);

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

        $searchcontents = Product::visibleOnStorefront()->where('frature', '0')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'selling_type', 'vendor_id', 'category_id')->paginate($limit);

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
            $category->products = Product::visibleOnStorefront()
                ->where('category_id', $category->id)
                ->select(
                    'id',
                    'category_id',
                    'ProductName',
                    'ProductSlug',
                    'ProductRegularPrice',
                    'ProductSalePrice',
                    'ProductResellerPrice',
                    'Discount',
                    'ViewProductImage',
                    'selling_type',
                    'vendor_id'
                )
                ->paginate($limit);

            // Total visible products in this category
            $category->totalproduct = Product::visibleOnStorefront()
                ->where('category_id', $category->id)
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
        $searchcontents = Product::visibleOnStorefront()->where('top_rated', '1')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'selling_type', 'vendor_id', 'category_id')->paginate($limit);


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

        $perPage = $request->input('limit', 20);
        $sort = $request->input('sort', 'rating');

        $query = Product::visibleOnStorefront()
            ->where('category_id', $category->id)
            ->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'created_at', 'selling_type', 'vendor_id');

        // Apply DB-level sorting
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'price_asc':
                $query->orderBy('ProductSalePrice', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('ProductSalePrice', 'desc');
                break;
            case 'rating':
            default:
                $query->selectSub(
                    Review::selectRaw('COALESCE(AVG(rating), 0)')
                        ->whereColumn('product_id', 'products.id')
                        ->where('status', 'Active'),
                    'avg_rating'
                )->orderBy('avg_rating', 'desc');
                break;
        }

        $paginated = $query->paginate($perPage);

        // Attach avg_rating and review_count to each product
        foreach ($paginated->items() as $product) {
            $reviews = Review::where('product_id', $product->id)->where('status', 'Active');
            $product->avg_rating = round($reviews->avg('rating') ?? 0, 1);
            $product->review_count = $reviews->count();
        }

        return response()->json([
            'status' => true,
            'message' => 'Products found with this category successfully',
            'data' => $paginated
        ], 200);
    }

    public function productbysubcategory(Request $request, $slug)
    {
        $selects = ['id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'created_at', 'selling_type', 'vendor_id'];

        $perPage = $request->input('limit', 20);
        $sort = $request->input('sort', 'rating');

        if (empty($slug)) {
            $query = Product::visibleOnStorefront()->select(...$selects);
        } else {
            $subcategory = Subcategory::where('slug', $slug)->first();
            if (!$subcategory) {
                return response()->json([
                    'status' => true,
                    'message' => 'No products found',
                    'data' => []
                ], 200);
            }
            $query = Product::visibleOnStorefront()->where('subcategory_id', $subcategory->id)->select(...$selects);
        }

        // Apply DB-level sorting
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'price_asc':
                $query->orderBy('ProductSalePrice', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('ProductSalePrice', 'desc');
                break;
            case 'rating':
            default:
                $query->selectSub(
                    Review::selectRaw('COALESCE(AVG(rating), 0)')
                        ->whereColumn('product_id', 'products.id')
                        ->where('status', 'Active'),
                    'avg_rating'
                )->orderBy('avg_rating', 'desc');
                break;
        }

        $paginated = $query->paginate($perPage);

        // Attach avg_rating and review_count to each product
        foreach ($paginated->items() as $product) {
            $reviews = Review::where('product_id', $product->id)->where('status', 'Active');
            $product->avg_rating = round($reviews->avg('rating') ?? 0, 1);
            $product->review_count = $reviews->count();
        }

        return response()->json([
            'status' => true,
            'message' => empty($slug) ? 'All products' : 'Products found with this sub-category successfully',
            'data' => $paginated
        ], 200);
    }

    public function productbyminicategory(Request $request, $slug)
    {
        $selects = ['id', 'category_id', 'subcategory_id', 'minicategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'created_at', 'selling_type', 'vendor_id'];

        $perPage = $request->input('limit', 20);
        $sort = $request->input('sort', 'rating');

        if (empty($slug)) {
            $query = Product::visibleOnStorefront()->select(...$selects);
        } else {
            $minicategory = Minicategory::where('slug', $slug)->first();
            if (!$minicategory) {
                return response()->json([
                    'status' => true,
                    'message' => 'No products found',
                    'data' => []
                ], 200);
            }
            $query = Product::visibleOnStorefront()->where('minicategory_id', $minicategory->id)->select(...$selects);
        }

        // Apply DB-level sorting
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'price_asc':
                $query->orderBy('ProductSalePrice', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('ProductSalePrice', 'desc');
                break;
            case 'rating':
            default:
                $query->selectSub(
                    Review::selectRaw('COALESCE(AVG(rating), 0)')
                        ->whereColumn('product_id', 'products.id')
                        ->where('status', 'Active'),
                    'avg_rating'
                )->orderBy('avg_rating', 'desc');
                break;
        }

        $paginated = $query->paginate($perPage);

        // Attach avg_rating and review_count to each product
        foreach ($paginated->items() as $product) {
            $reviews = Review::where('product_id', $product->id)->where('status', 'Active');
            $product->avg_rating = round($reviews->avg('rating') ?? 0, 1);
            $product->review_count = $reviews->count();
        }

        return response()->json([
            'status' => true,
            'message' => empty($slug) ? 'All products' : 'Products found with this mini-category successfully',
            'data' => $paginated
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
        $brandproducts = Product::visibleOnStorefront()->where('brand_id', $brand->id)->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage', 'selling_type', 'vendor_id')->get();

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
        $rawSearch = trim($request->keywords ?? '');
        $limit = $request->input('limit', 20);

        if ($rawSearch === '') {
            return response()->json([
                'status' => false,
                'message' => 'Please enter a search term',
            ], 400);
        }

        // Split into individual keywords and filter out very short ones (1 char)
        $keywords = array_values(array_filter(
            explode(' ', $rawSearch),
            fn($w) => mb_strlen(trim($w)) >= 2
        ));
        $keywords = array_map('trim', $keywords);

        // If all keywords were too short, fall back to original search string
        if (empty($keywords)) {
            $keywords = [trim($rawSearch)];
        }

        $selects = [
            'products.id', 'products.category_id', 'products.subcategory_id',
            'products.brand_id', 'products.ProductName', 'products.ProductSlug',
            'products.ProductRegularPrice', 'products.ProductSalePrice',
            'products.ProductResellerPrice', 'products.min_sell_price',
            'products.Discount', 'products.ViewProductImage',
            'products.vendor_id', 'products.selling_type',
        ];

        $query = Product::visibleOnStorefront()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id');

        // WHERE: at least one keyword matches in any searchable field
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $word) {
                $escaped = addcslashes($word, '%_');
                $q->orWhere('products.ProductName', 'LIKE', "%{$escaped}%")
                  ->orWhere('categories.category_name', 'LIKE', "%{$escaped}%")
                  ->orWhere('subcategories.sub_category_name', 'LIKE', "%{$escaped}%")
                  ->orWhere('brands.brand_name', 'LIKE', "%{$escaped}%")
                  ->orWhere('products.MetaKey', 'LIKE', "%{$escaped}%")
                  ->orWhere('products.ProductDetails', 'LIKE', "%{$escaped}%");
            }
        });

        // Build relevance score expression
        // ProductName match = 3pts, Category/Sub/Brand/Meta = 2pts, Description = 1pt
        $scoreParts = [];
        foreach ($keywords as $word) {
            $escaped = addcslashes($word, '%_');
            $safe = str_replace("'", "''", $escaped);
            $scoreParts[] = "(CASE WHEN products.ProductName LIKE '%{$safe}%' THEN 3 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN categories.category_name LIKE '%{$safe}%' THEN 2 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN subcategories.sub_category_name LIKE '%{$safe}%' THEN 2 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN brands.brand_name LIKE '%{$safe}%' THEN 2 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN products.MetaKey LIKE '%{$safe}%' THEN 2 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN products.ProductDetails LIKE '%{$safe}%' THEN 1 ELSE 0 END)";
        }

        // Bonus: exact phrase match in product name gets extra 5 points
        $safeRaw = str_replace("'", "''", addcslashes($rawSearch, '%_'));
        $scoreParts[] = "(CASE WHEN products.ProductName LIKE '%{$safeRaw}%' THEN 5 ELSE 0 END)";

        $scoreExpr = implode('+', $scoreParts);

        $products = $query
            ->selectRaw(implode(', ', $selects) . ", ({$scoreExpr}) as relevance_score")
            ->orderByDesc('relevance_score')
            ->paginate($limit);

        if ($products->total() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No products found with this keywords',
                'total' => 0,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Products found with this keywords successfully',
            'total' => $products->total(),
            'data' => $products
        ], 200);
    }

    public function productdetails($slug)
    {
        $product = Product::with([
            'varients.sizes.bulkPrices',
            'priceTiers',
            'vendor:id,user_id,company_name,slug,approval_type,is_verified_badge',
            'categories',
        ])->where('ProductSlug', $slug)->first();
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
        // Hide unapproved vendor products from storefront
        if ($product->vendor_id && ($product->vendor_approval_status ?? '') !== 'approved') {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
        // Hide stock-out products from storefront
        if ($product->frature === 0 || $product->frature === '0') {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        // Mask vendor identity for privately approved suppliers
        if ($product->vendor && $product->vendor->approval_type === 'private') {
            $product->vendor->company_name = $product->vendor->public_name;
            $product->vendor->slug = $product->vendor->public_slug;
        }

        $relatedproducts = Product::where('category_id', $product->category_id)->where('id', '!=', $product->id)->visibleOnStorefront()->latest()->paginate(12);

        $flashSaleData = null;
        $activeFlashSale = FlashSale::active()->orderBy('end_time', 'asc')->first();
        if ($activeFlashSale) {
            $fsp = FlashSaleProduct::where('flash_sale_id', $activeFlashSale->id)
                ->where('product_id', $product->id)
                ->first();
            if ($fsp) {
                $regularPrice = floatval($product->ProductRegularPrice ?? 0);
                $salePrice = floatval($product->ProductSalePrice ?? $regularPrice);
                $discount = floatval($fsp->discount_percentage);
                $flashPrice = $discount > 0
                    ? round($salePrice * (1 - $discount / 100), 2)
                    : $salePrice;
                $flashSaleData = [
                    'flash_sale_title' => $activeFlashSale->title,
                    'flash_sale_end_time' => $activeFlashSale->end_time,
                    'discount_percentage' => $discount,
                    'flash_price' => $flashPrice,
                    'original_price' => $salePrice,
                ];
            }
        }

        $commissionService = app(\App\Services\VendorCommissionService::class);
        $commissionPercent = $commissionService->getRateForProduct($product->vendor_id, $product->category_id);
        
        return response()->json([
            'status' => true,
            'message' => 'Products Details & Related Products',
            'data' => [
                'product_details' => $product,
                'relatedproducts' => $relatedproducts,
                'flash_sale' => $flashSaleData,
                'commission_percent' => $commissionPercent
            ]
        ], 200);
    }


    // login and reg part

    public function userRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'size:11', 'regex:/^01[3-9]\d{8}$/'],
            'password' => ['required', 'string', 'min:6'],
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
                $user->campaign_code = $request->campaign_code ?? null;
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
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $request->phone;

        Log::info('[PasswordReset] Received request for phone: ' . $phone);

        if (strlen($phone) == '11') {
            $user = User::where('email', $phone)->first();
            if (!$user) {
                Log::info('[PasswordReset] User not found with phone: ' . $phone . ', trying with 88 prefix');
                $user = User::where('email', '88' . $phone)->first();
            }
        } else {
            $user = User::where('email', $phone)->first();
        }

        if (isset($user)) {
            Log::info('[PasswordReset] User found: ID=' . $user->id . ', email=' . $user->email . ', name=' . $user->name);

            $otp = random_int(100000, 999999);
            $user->otp = $otp;
            $user->update();

            Log::info('[PasswordReset] OTP generated: ' . $otp . ' for user ID=' . $user->id);

            $smsUrl = 'http://bulksmsbd.net/api/smsapi?api_key=' . env('BULKSMS_API_KEY') . '&type=text&number=' . $user->email . '&senderid=' . env('BULKSMS_SENDER_ID') . '&message=Dear ' . $user->name . ' Your password reset OTP is : ' . $otp . '';

            Log::info('[PasswordReset] SMS API URL: ' . $smsUrl);
            Log::info('[PasswordReset] BULKSMS_API_KEY set: ' . (env('BULKSMS_API_KEY') ? 'YES' : 'NO'));
            Log::info('[PasswordReset] BULKSMS_SENDER_ID: ' . env('BULKSMS_SENDER_ID'));

            $status = Http::get($smsUrl);

            Log::info('[PasswordReset] SMS API response status: ' . $status->status());
            Log::info('[PasswordReset] SMS API response body: ' . $status->body());

            if ($status->successful()) {
                return response()->json([
                    'status' => true,
                    'message' => 'OTP sent successfully to your phone number',
                ], 200);
            } else {
                Log::error('[PasswordReset] SMS API failed. Status: ' . $status->status() . ', Body: ' . $status->body());
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send OTP. Please try again.',
                ], 400);
            }
        } else {
            Log::warning('[PasswordReset] No user found for phone: ' . $phone);
            return response()->json([
                'status' => false,
                'message' => 'No account found with this phone number',
            ], 404);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string'],
        ]);

        $phone = $request->phone;

        if (strlen($phone) == '11') {
            $user = User::where('email', $phone)->where('otp', $request->otp)->first();
            if (!$user) {
                $user = User::where('email', '88' . $phone)->where('otp', $request->otp)->first();
            }
        } else {
            $user = User::where('email', $phone)->where('otp', $request->otp)->first();
        }

        if (isset($user)) {
            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully.',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        }
    }

    public function verifyOtpAndResetPassword(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $phone = $request->phone;

        if (strlen($phone) == '11') {
            $user = User::where('email', $phone)->where('otp', $request->otp)->first();
            if (!$user) {
                $user = User::where('email', '88' . $phone)->where('otp', $request->otp)->first();
            }
        } else {
            $user = User::where('email', $phone)->where('otp', $request->otp)->first();
        }

        if (isset($user)) {
            $user->password = Hash::make($request->password);
            $user->otp = null;
            $user->update();

            return response()->json([
                'status' => true,
                'message' => 'Password reset successfully. Please login with your new password.',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP. Please try again.',
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
        $validator = Validator::make($request->all(), [
            'profile' => ['nullable', 'image', 'max:5120'],
            'nid' => ['nullable', 'image', 'max:5120'],
        ], [
            'profile.max' => 'Profile image must be under 5MB.',
            'nid.max' => 'NID document must be under 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $id = Auth::user()->id;
        $userprofile = User::findOrfail($id);
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');

        $productImg = $request->file('profile');
        if ($productImg) {
            $safeName = Str::slug(pathinfo($productImg->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $productImg->getClientOriginalExtension();
            $path = $productImg->storeAs('users/profiles', $safeName, 'r2');
            $userprofile->profile = $r2BaseUrl . '/' . $path;
        }

        $nidImg = $request->file('nid');
        if ($nidImg) {
            $safeName = Str::slug(pathinfo($nidImg->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $nidImg->getClientOriginalExtension();
            $path = $nidImg->storeAs('users/nid', $safeName, 'r2');
            $userprofile->nid = $r2BaseUrl . '/' . $path;
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
        $order->setAttribute('carrybee_status', $meta['carrybee_status'] ?? null);
        $order->setAttribute('steadfast_last_synced_at', $meta['steadfast_last_synced_at']);
        $order->setAttribute('warehouse_sent_at', $meta['warehouse_sent_at']);
        $order->setAttribute(
            'total',
            (float) ($order->subTotal ?? 0)
            + (float) ($order->deliveryCharge ?? 0)
            - (float) ($order->discountCharge ?? 0)
        );

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
        $query = Order::with(['customers', 'orderproducts.product:id,ProductName,ViewProductImage', 'couriers', 'cities', 'zones', 'admins'])
            ->where('user_id', $id)
            ->where('status', '!=', 'Pending Payment');

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

        $search = trim((string) request()->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoiceID', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhereHas('customers', function ($customerQuery) use ($search) {
                        $customerQuery->where('customerName', 'like', '%' . $search . '%')
                            ->orWhere('customerPhone', 'like', '%' . $search . '%');
                    });
            });
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
        // Prefer lookup by unique order id if provided
        if ($request->filled('id')) {
            $orders = Order::with(['customers', 'orderproducts.product:id,ProductName,ViewProductImage', 'couriers', 'cities', 'zones', 'admins'])
                ->where('user_id', Auth::id())
                ->where('id', $request->id)
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
                'message' => 'No order found with this id',
            ], 404);
        }

        $rawInvoiceId = trim((string) $request->invoiceID);
        $invoiceId = $this->normalizeInvoiceId($request->invoiceID);

        if (!$invoiceId) {
            return response()->json([
                'status' => false,
                'message' => 'No order found with this invoice id',
            ], 404);
        }

        $orders = Order::with(['customers', 'orderproducts.product:id,ProductName,ViewProductImage', 'couriers', 'cities', 'zones', 'admins'])
            ->where('user_id', Auth::id())
            ->where(function ($q) use ($rawInvoiceId, $invoiceId) {
                $q->where('invoiceID', $invoiceId);
                if ($rawInvoiceId !== '' && $rawInvoiceId !== $invoiceId) {
                    $q->orWhere('invoiceID', $rawInvoiceId);
                }
            })
            ->latest('id')
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
            'orderproducts.product:id,ProductName,ViewProductImage',
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

    public function getBankInfo()
    {
        $id = Auth::user()->id;
        $bank = Bank::where('user_id', $id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Bank info',
            'data' => $bank
        ], 200);
    }

    public function announcements()
    {
        try {
            $announcements = Announcement::where('status', 'Active')
                ->orderBy('published_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            // Table may not exist yet
            $announcements = collect();
        }

        return response()->json([
            'status' => true,
            'message' => 'Announcements list',
            'data' => [
                'announcements' => $announcements,
            ],
        ], 200);
    }

    public function createticket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attachment' => ['nullable', 'file', 'max:5120'],
        ], [
            'attachment.max' => 'Attachment must be under 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

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

        $productImg = $request->file('attachment');
        if ($productImg) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $safeName = Str::slug(pathinfo($productImg->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $productImg->getClientOriginalExtension();
            $path = $productImg->storeAs('tickets', $safeName, 'r2');
            $tikit->attachment = $r2BaseUrl . '/' . $path;
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
            if ($productImg) {
                $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
                $safeName = Str::slug(pathinfo($productImg->getClientOriginalName(), PATHINFO_FILENAME))
                    . '_' . Str::random(8) . '.' . $productImg->getClientOriginalExtension();
                $path = $productImg->storeAs('products/images', $safeName, 'r2');
                $product->attachment = $r2BaseUrl . '/' . $path;
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
        $paymenttypes = Paymenttype::where('status', 'Active')
            ->whereRaw('LOWER(paymentTypeName) NOT LIKE ?', ['%wallet%'])
            ->get();
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
            'withdrew_amount' => ['required', 'numeric', 'min:50'],
            'paymenttype_id' => ['required', 'integer'],
            'to_account_number' => ['required', 'string', 'max:255'],
            'to_additional_info' => ['nullable', 'string', 'max:1000'],
        ], [
            'withdrew_amount.min' => 'Minimum withdrawal amount is ৳50.',
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

        // Check for existing pending withdrawal requests
        $pendingCount = Withdrew::where('user_id', $id)
            ->where('status', 'Pending')
            ->count();

        if ($pendingCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'You already have a pending withdrawal request. Please wait for it to be processed.',
            ], 422);
        }

        // Rate limit: max 3 requests per day
        $todayCount = Withdrew::where('user_id', $id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCount >= 3) {
            return response()->json([
                'status' => false,
                'message' => 'You have exceeded the maximum withdrawal requests for today. Please try again tomorrow.',
            ], 429);
        }

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
                ->whereRaw('LOWER(paymentTypeName) NOT LIKE ?', ['%wallet%'])
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
        
        $incomes = Income::where('user_id', $user->id)
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

        $refunds = Chargededuct::where('user_id', $user->id)
            ->where('status', 'Refund')
            ->latest()
            ->get()
            ->map(function ($cd) {
                $cd->product_price = 0;
                
                // Extract invoice from comment: "Delivery charge refund of ৳100 for cancelled order #SS00234"
                $invoice = null;
                if (preg_match('/#([a-zA-Z0-9_-]+)/', $cd->comment, $matches)) {
                    $invoice = $matches[1];
                }
                $cd->order_invoice = $invoice ? 'Refund: ' . $invoice : 'Delivery Refund';
                
                return $cd;
            });

        $messages = $incomes->concat($refunds)->sortByDesc('created_at')->values();

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
            'pending_amount' => Order::where('user_id', $id)->whereNotIn('status', ['Delivered', 'Canceled', 'Cancelled'])->get()->sum('profit'),
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
        $products = Shopproduct::where('user_id', Auth::user()->id)
            ->with(['product' => function ($q) {
                $q->select('id', 'ProductName', 'ProductSlug', 'ProductSku', 'ProductImage', 'ViewProductImage', 'ProductResellerPrice', 'ProductRegularPrice', 'qty', 'status', 'category_id');
            }])
            ->get()
            ->map(function ($item) {
                $p = $item->product;
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'status' => $item->status,
                    'added_at' => $item->created_at,
                    'product' => $p ? [
                        'id' => $p->id,
                        'name' => $p->ProductName,
                        'slug' => $p->ProductSlug,
                        'sku' => $p->ProductSku,
                        'image' => $p->ViewProductImage ?? $p->ProductImage,
                        'reseller_price' => $p->ProductResellerPrice,
                        'regular_price' => $p->ProductRegularPrice,
                        'qty' => $p->qty,
                        'product_status' => $p->status,
                    ] : null,
                ];
            });

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

    public function checkInShop($id)
    {
        $exists = Shopproduct::where('product_id', $id)
            ->where('user_id', Auth::user()->id)
            ->exists();

        return response()->json([
            'status' => true,
            'in_shop' => $exists,
        ], 200);
    }

    public function publicShop($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $products = Shopproduct::where('user_id', $userId)
            ->where('status', 'Active')
            ->with(['product' => function ($q) {
                $q->where('status', 'Active')
                  ->select('id', 'ProductName', 'ProductSlug', 'ProductSku', 'ProductImage', 'ViewProductImage', 'ProductResellerPrice', 'ProductRegularPrice', 'qty', 'status', 'category_id');
            }])
            ->get()
            ->filter(fn($item) => $item->product !== null)
            ->map(function ($item) {
                $p = $item->product;
                return [
                    'id' => $p->id,
                    'name' => $p->ProductName,
                    'slug' => $p->ProductSlug,
                    'image' => $p->ViewProductImage ?? $p->ProductImage,
                    'regular_price' => $p->ProductRegularPrice,
                    'qty' => $p->qty,
                ];
            })->values();

        return response()->json([
            'status' => true,
            'message' => 'Public shop products',
            'data' => [
                'shop_name' => $user->shop_name ?? $user->name,
                'user_id' => (int) $userId,
                'products' => $products,
            ],
        ], 200);
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
                    'selling_price' => $request->selling_price,
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

        // Server-side Price Calculation (Buy Price)
        $costPrice = (float) $cartProduct->ProductResellerPrice;

        // 1. Handle Variant/Size specific base price
        if ($request->size || $request->color) {
            $variant = $cartProduct->varients()->where(function ($q) use ($request) {
                $q->where('title', $request->color)->orWhere('color_name', $request->color);
            })->first();
            if ($variant) {
                $sizeRecord = $variant->sizes()->where('size_name', $request->size)->first();
                if ($sizeRecord && $sizeRecord->price > 0) {
                    $costPrice = (float) $sizeRecord->price;
                } elseif ($variant->price > 0) {
                    $costPrice = (float) $variant->price;
                }
            }
        }

        // 2. Handle Bulk Discounts
        $qty = (int) $request->qty;
        if ($request->size || $request->color) {
            // Check for size-specific bulk tiers first
            $variant = $cartProduct->varients()->where(function ($q) use ($request) {
                $q->where('title', $request->color)->orWhere('color_name', $request->color);
            })->first();
            if ($variant) {
                $sizeRecord = $variant->sizes()->where('size_name', $request->size)->first();
                if ($sizeRecord) {
                    $bulkTier = $sizeRecord->bulkPrices()
                        ->where('min_qty', '<=', $qty)
                        ->where(function ($q) use ($qty) {
                            $q->where('max_qty', '>=', $qty)->orWhereNull('max_qty');
                        })
                        ->orderBy('min_qty', 'desc')
                        ->first();
                    if ($bulkTier) {
                        $costPrice = (float) ($bulkTier->bulk_price ?: $bulkTier->unit_price);
                    }
                }
            }
        } else {
            // Check for product-level tiers
            $bulkTier = $cartProduct->priceTiers()
                ->where('min_qty', '<=', $qty)
                ->where(function ($q) use ($qty) {
                    $q->where('max_qty', '>=', $qty)->orWhereNull('max_qty');
                })
                ->orderBy('min_qty', 'desc')
                ->first();
            if ($bulkTier) {
                $costPrice = (float) $bulkTier->unit_price;
            }
        }

        // 3. Handle Flash Sale Discount
        $activeFlashSale = FlashSale::active()->orderBy('end_time', 'asc')->first();
        if ($activeFlashSale) {
            $fsp = FlashSaleProduct::where('flash_sale_id', $activeFlashSale->id)
                ->where('product_id', $cartProduct->id)
                ->first();
            if ($fsp) {
                $discount = floatval($fsp->discount_percentage);
                if ($discount > 0) {
                    $costPrice = round($costPrice * (1 - $discount / 100), 2);
                }
            }
        }
        // Application of Admin Commission markup
        $commissionService = app(\App\Services\VendorCommissionService::class);
        $commissionPercent = $commissionService->getRateForProduct(
            $cartProduct->vendor_id, 
            $cartProduct->category_id
        );
        $commissionFactor = 1 + ($commissionPercent / 100);
        $costPrice = round($costPrice * $commissionFactor, 2);

    $minAllowedPrice = $costPrice;
    $submittedPrice = (float) ($request->selling_price ?: $request->price);

        // Validation: If selling price provided, it must be >= cost
        if ($submittedPrice < $minAllowedPrice - 0.01) {
            return response()->json([
                'status' => false,
                'message' => 'Selling price (' . number_format($submittedPrice, 2) . ') cannot be lower than the product cost (' . number_format($minAllowedPrice, 2) . ').'
            ], 422);
        }

        // Final Cart Values
        // For dropshipping, 'price' in Cart table should be the cost (what they pay)
        // Their intended selling price is stored in options
        $cartPrice = $costPrice; 
        $options = [
            'size' => $request->size,
            'color' => $request->color,
            'image' => $cartProduct->ProductImage,
            'code' => $cartProduct->ProductSku,
            'original_price' => $cartProduct->ProductResellerPrice,
        ];

        if ($request->selling_price) {
            $options['selling_price'] = $request->selling_price;
        }

        // Check if same product+variant already in cart for this user
        $existing = Cart::where('user_id', Auth::user()->id)
            ->where('product_id', $request->product_id)
            ->where('size', $request->size)
            ->where('color', $request->color)
            ->first();

        if ($existing) {
            // Increment quantity instead of replacing
            $existing->qty += (int) ($request->qty ?: 1);
            $existing->price = $cartPrice;
            $existing->options = $options;
            $existing->save();
            $cart = $existing;
        } else {
            $cart = Cart::create([
                'session_id' => $request->session_id,
                'product_id' => $request->product_id,
                'name' => $cartProduct->ProductName,
                'code' => $cartProduct->ProductSku,
                'price' => $cartPrice,
                'qty' => $request->qty ?: 1,
                'size' => $request->size,
                'color' => $request->color,
                'shop_id' => $cartProduct->shop_id ?: $cartProduct->vendor_id ?: 1,
                'image' => $cartProduct->ProductImage,
                'options' => $options,
                'user_id' => Auth::user()->id,
            ]);
        }

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

        $newQty = $request->qty ?? $cart->qty;
        $cart->qty = $newQty;

        // ── Recalculate price based on new quantity ──
        $product = Product::find($cart->product_id);
        if ($product) {
            $costPrice = (float) $product->ProductResellerPrice;

            // 1. Variant/Size base price
            if ($cart->color || $cart->size) {
                $variant = $product->varients()->where(function ($q) use ($cart) {
                    $q->where('title', $cart->color)->orWhere('color_name', $cart->color);
                })->first();
                if ($variant) {
                    $sizeRecord = $variant->sizes()->where('size_name', $cart->size)->first();
                    if ($sizeRecord && $sizeRecord->price > 0) {
                        $costPrice = (float) $sizeRecord->price;
                    } elseif ($variant->price > 0) {
                        $costPrice = (float) $variant->price;
                    }
                }
            }

            // 2. Bulk tier lookup
            if ($cart->color || $cart->size) {
                // Size-specific bulk tiers
                $variant = $product->varients()->where(function ($q) use ($cart) {
                    $q->where('title', $cart->color)->orWhere('color_name', $cart->color);
                })->first();
                if ($variant) {
                    $sizeRecord = $variant->sizes()->where('size_name', $cart->size)->first();
                    if ($sizeRecord) {
                        $bulkTier = $sizeRecord->bulkPrices()
                            ->where('min_qty', '<=', $newQty)
                            ->where(function ($q) use ($newQty) {
                                $q->where('max_qty', '>=', $newQty)->orWhereNull('max_qty');
                            })
                            ->orderBy('min_qty', 'desc')
                            ->first();
                        if ($bulkTier) {
                            $costPrice = (float) ($bulkTier->bulk_price ?: $bulkTier->unit_price);
                        }
                    }
                }
            } else {
                // Product-level bulk tiers
                $bulkTier = $product->priceTiers()
                    ->where('min_qty', '<=', $newQty)
                    ->where(function ($q) use ($newQty) {
                        $q->where('max_qty', '>=', $newQty)->orWhereNull('max_qty');
                    })
                    ->orderBy('min_qty', 'desc')
                    ->first();
                if ($bulkTier) {
                    $costPrice = (float) $bulkTier->unit_price;
                }
            }

            // 3. Flash sale discount
            $activeFlashSale = FlashSale::active()->orderBy('end_time', 'asc')->first();
            if ($activeFlashSale) {
                $fsp = FlashSaleProduct::where('flash_sale_id', $activeFlashSale->id)
                    ->where('product_id', $product->id)
                    ->first();
                if ($fsp) {
                    $discount = floatval($fsp->discount_percentage);
                    if ($discount > 0) {
                        $costPrice = round($costPrice * (1 - $discount / 100), 2);
                    }
                }
            }

            // Application of Admin Commission markup
            $commissionService = app(\App\Services\VendorCommissionService::class);
            $commissionPercent = $commissionService->getRateForProduct(
                $product->vendor_id, 
                $product->category_id
            );
            $commissionFactor = 1 + ($commissionPercent / 100);
            $cart->price = round($costPrice * $commissionFactor, 2);
        }

        $cart->save();

        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully',
            'data' => [
                'qty' => $cart->qty,
                'price' => $cart->price,
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

        // Enrich cart items with vendor_id from product table
        // (shop_id is the admin who uploaded, vendor_id is the actual supplier)
        $carts->transform(function ($cart) {
            $product = Product::find($cart->product_id);
            $cart->vendor_id = $product ? ($product->vendor_id ?? $cart->shop_id) : $cart->shop_id;
            return $cart;
        });

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
            'advance_delivery' => 'nullable|string|in:yes,no',
        ]);

        $cartIds = collect(explode(',', (string) $request->input('cart_ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $cartQuery = Cart::where('user_id', Auth::user()->id);
        if ($cartIds->isNotEmpty()) {
            $cartQuery->whereIn('id', $cartIds->all());
        }

        $shopproducts = $cartQuery->get()->groupBy('shop_id');

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
            $post_data['total_amount'] = $request->deliveryCharge > 10 ? $request->deliveryCharge : 10;
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

            # Pass user_id so the success callback can identify the user
            $post_data['value_a'] = Auth::id();
            $post_data['value_d'] = json_encode([
                'user_id' => Auth::id(),
                'advance_delivery' => $request->advance_delivery,
                'balance_from' => 'online_pay',
                'customer_name' => $request->customerName,
                'customer_phone' => $request->customerPhone,
                'customer_address' => $request->customerAddress,
            ]);

            #Before  going to initiate the payment order status need to update as Pending.
            // Calculate profit from cart items
            $buy = $sell = $orderBonus = 0;
            foreach ($shopproducts->flatten() as $cartItem) {
                $opts = is_array($cartItem->options) ? $cartItem->options : (is_string($cartItem->options) ? json_decode($cartItem->options, true) : []);
                $costPrice = (float) $cartItem->price;
                $sellingPrice = !empty($opts['selling_price']) ? (float) $opts['selling_price'] : $costPrice;
                $buy  += $costPrice * $cartItem->qty;
                $sell += $sellingPrice * $cartItem->qty;
                $pd = Product::find($cartItem->product_id);
                $orderBonus += $pd->reseller_bonus ?? 0;
            }
            $profit = $sell - $buy;

            $update_product = DB::table('orders')
                ->where('transaction_id', $post_data['tran_id'])
                ->updateOrInsert([
                    'store_id' => 1,
                    'shop_count' => count($shopproducts),
                    'invoiceID' => $this->uniqueIDN(),
                    'subTotal' => $sell,
                    'profit' => $profit,
                    'order_bonus' => $orderBonus,
                    'deliveryCharge' => $request->deliveryCharge,
                    'advance_delivery' => $request->advance_delivery === 'yes' ? 1 : 0,
                    'data' => json_encode([
                        'customer_name' => $request->customerName,
                        'customer_phone' => $request->customerPhone,
                        'customer_address' => $request->customerAddress,
                        'customer_note' => $request->customerNote ?? '',
                        'delivery_charge_per_shop' => $request->deliveryCharge,
                        'cart_subtotal' => $request->subTotal,
                        'advance_delivery' => $request->advance_delivery,
                        'balance_from' => $request->balance_from,
                    ]),
                    'cart' => json_encode($shopproducts),
                    'orderDate' => date('Y-m-d'),
                    'transaction_id' => $post_data['tran_id'],
                    'user_id' => Auth::id(),
                    'status' => 'Pending Payment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            try {
                $sslc = new SslCommerzNotification();
                $payment_options = $sslc->makePayment($post_data, 'checkout', 'json');

                // The response is a JSON string with gateway URL
                $decoded = json_decode($payment_options, true);
                if ($decoded && isset($decoded['status']) && ($decoded['status'] === 'success' || $decoded['status'] === 'SUCCESS') && isset($decoded['data'])) {
                    return response()->json([
                        'status' => true,
                        'ssl_redirect' => true,
                        'gateway_url' => $decoded['data'],
                        'message' => 'Redirecting to payment gateway...',
                    ]);
                }

                // If we got a fail response from SSLCommerz
                $errorMessage = $decoded['message'] ?? 'Payment gateway error. Please try Account Wallet.';
                return response()->json([
                    'status' => false,
                    'message' => $errorMessage,
                ], 422);
            } catch (\Throwable $e) {
                \Log::error('SSLCommerz payment error: ' . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'Online payment is currently unavailable. Please use Account Wallet instead.',
                ], 503);
            }
        }

        // Validate wallet balance for from_account orders
        if ($request->balance_from == 'from_account') {
            $currentUser = User::find(Auth::id());
            if (!$currentUser || (float) $currentUser->account_balance < (float) $request->deliveryCharge) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient wallet balance. You need ৳' . $request->deliveryCharge . ' but have ৳' . ($currentUser->account_balance ?? 0) . '.',
                ], 422);
            }
        }

        // Get all cart items and group by vendor_id (actual supplier)
        $allCartItemsQuery = Cart::where('user_id', Auth::user()->id);
        if ($cartIds->isNotEmpty()) {
            $allCartItemsQuery->whereIn('id', $cartIds->all());
        }
        $allCartItems = $allCartItemsQuery->get();
        $vendorGroups = $allCartItems->groupBy(function ($item) {
            $product = Product::find($item->product_id);
            return $product ? ($product->vendor_id ?? $item->shop_id) : ($item->shop_id ?: 1);
        });
        $vendorCount = max(1, $vendorGroups->count());
        $perVendorDelivery = round($request->deliveryCharge / $vendorCount);
        $groupId = $vendorCount > 1 ? 'GRP-' . strtoupper(substr(uniqid(), -8)) : null;

        // Assign an active executive admin
        $admin = Admin::whereHas('roles', function ($q) {
            $q->where('name', 'Executive');
        })
            ->where('add_by', 1)
            ->where('status', 'Active')
            ->inRandomOrder()
            ->first();

        // Deduct wallet ONCE with the FULL delivery charge (before creating orders)
        if ($request->balance_from == 'from_account') {
            $accountuser = User::find(Auth::id());
            if ($accountuser) {
                $accountuser->account_balance -= $request->deliveryCharge;
                $accountuser->save();
                $chargededucts = new Chargededuct();
                $chargededucts->user_id = $accountuser->id;
                $chargededucts->comment = 'You have charged ' . $request->deliveryCharge . ' TK for delivery charge (' . $vendorCount . ' supplier' . ($vendorCount > 1 ? 's' : '') . ').';
                $chargededucts->amount = $request->deliveryCharge;
                $chargededucts->status = 'Success';
                $chargededucts->save();
            }
        }

        // Create one order per vendor/supplier
        $ordersCreated = [];

        foreach ($vendorGroups as $vendorId => $vendorItems) {
            // Calculate totals for this vendor's items only
            $buy = $sell = $bonus = 0;
            foreach ($vendorItems as $product) {
                $productData = Product::find($product->product_id);
                $options = is_array($product->options) ? $product->options : (is_string($product->options) ? json_decode($product->options, true) : []);
                $costPrice = (float) $product->price;
                $sellingPrice = !empty($options['selling_price']) ? (float) $options['selling_price'] : $costPrice;
                $buy  += $costPrice * $product->qty;
                $sell += $sellingPrice * $product->qty;
                $bonus += $productData->reseller_bonus ?? 0;
            }

            $order = new Order();
            $order->profit = $sell - $buy;
            $order->order_bonus = $bonus;
            $order->user_id = Auth::id() ?? null;
            $order->store_id = $vendorItems->first()->shop_id ?: 1;
            $order->shop_count = $vendorCount;
            $order->order_group_id = $groupId;
            $order->invoiceID = $this->uniqueIDN();
            $order->subTotal = $sell;
            $order->deliveryCharge = $perVendorDelivery;
            $order->customerNote = $request->customerNote ?? null;
            $order->status = 'Pending';
            $order->advance_delivery = $request->advance_delivery === 'yes' ? 1 : 0;

            if ($request->balance_from == 'from_account') {
                $order->paymentAmount = $perVendorDelivery;
                $order->payment_type_id = 5;
            }

            $order->orderDate = Carbon::today()->format('Y-m-d');
            $order->admin_id = $admin->id ?? 1;
            $order->city_id = $request->city_id ?? 0;
            $order->zone_id = $request->zone_id ?? 0;

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

            // Aggregate cart items: merge duplicates with same product_id + color + size
            $aggregatedItems = $vendorItems->groupBy(function ($item) {
                $opts = is_array($item->options) ? $item->options : (is_string($item->options) ? json_decode($item->options, true) : []);
                return $item->product_id . '|' . ($opts['color'] ?? '') . '|' . ($opts['size'] ?? '');
            })->map(function ($group) {
                $first = clone $group->first();
                $first->qty = $group->sum('qty');
                return $first;
            });

            // Save order products for this vendor only (aggregated)
            foreach ($aggregatedItems as $product) {
                $options = is_array($product->options) ? $product->options : (is_string($product->options) ? json_decode($product->options, true) : []);

                $orderProduct = new Orderproduct();
                $orderProduct->order_id = $order->id;
                $orderProduct->product_id = $product->product_id;
                $orderProduct->productCode = $product->code;
                $orderProduct->productName = $product->name;
                $orderProduct->quantity = $product->qty;
                $orderProduct->productPrice = $product->price;

                if (!empty($options['color']) && $options['color'] != 'undefined') {
                    $orderProduct->color = $options['color'];
                }

                if (!empty($options['size']) && $options['size'] != 'undefined') {
                    $orderProduct->size = $options['size'];
                }

                $sellingPrice = $options['selling_price'] ?? null;
                if ($sellingPrice && $sellingPrice !== 'undefined') {
                    $orderProduct->selling_price = (float) $sellingPrice;
                }

                $orderProduct->save();
            }

            // Decrement product stock for this order
            app(\App\Services\StockService::class)->decrementForOrder($order->id);

            // Notification
            $notification = new Comment();
            $notification->order_id = $order->id;
            $notification->comment = $order->invoiceID . ' Order has been created for ' . ($admin->name ?? 'Admin');
            $notification->admin_id = $order->admin_id;
            $notification->save();

            // Notify this vendor
            if ($vendorId) {
                try {
                    $vendorNotification = app(VendorAdminNotificationService::class);
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
                } catch (\Throwable $e) {
                    \Log::warning('Vendor notification failed', ['error' => $e->getMessage()]);
                }

                // Send SMS + Email to this supplier
                try {
                    $supplierNotification = app(\App\Services\SupplierOrderNotificationService::class);
                    $supplierNotification->notify($order, [(int) $vendorId], $request->customerName);
                } catch (\Throwable $e) {
                    \Log::warning('Supplier SMS/Email notification failed', ['error' => $e->getMessage()]);
                }
            }

            // Real-time push notification
            try {
                $pushService = app(\App\Services\PushNotificationService::class);
                $pushService->onNewOrder($order);
            } catch (\Throwable $e) {
                \Log::warning('Push notification failed for new order', ['error' => $e->getMessage()]);
            }

            $ordersCreated[] = [
                'order_id' => $order->id,
                'invoiceID' => $order->invoiceID,
            ];
        }

        // Clear only the items that were checked out. Buy Now passes cart_ids; cart checkout clears all.
        $clearCartQuery = Cart::where('user_id', Auth::user()->id);
        if ($cartIds->isNotEmpty()) {
            $clearCartQuery->whereIn('id', $cartIds->all());
        }
        $clearCartQuery->delete();

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
public function popularVendors(Request $request)
{
    $sort = $request->input('sort', 'popular');

    $vendors = Vendor::where('status', 'approved')
        ->withCount(['products' => function ($q) {
            $q->where('status', 'Active')
                ->where(function ($sub) {
                    $sub->whereNull('vendor_id')
                        ->orWhere('vendor_approval_status', 'approved');
                });
        }])
        ->withCount('followers')
        ->withCount('earnings as total_sales_count')
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
            'created_at',
        ]);

    // Compute average rating for each vendor
    foreach ($vendors as $vendor) {
        $productIds = Product::where('vendor_id', $vendor->id)->pluck('id');
        $reviews = Review::whereIn('product_id', $productIds)->where('status', 'Active');
        $vendor->avg_product_rating = round($reviews->avg('rating') ?? 0, 1);
        $vendor->review_count = $reviews->count();

        // Mask vendor identity for privately approved suppliers
        $vendor->company_name = $vendor->public_name;
        $vendor->slug = $vendor->public_slug;
    }

    // Apply sort
    switch ($sort) {
        case 'top_supplier':
            $sorted = $vendors->sortByDesc('products_count')->values();
            break;
        case 'recent':
            $sorted = $vendors->sortByDesc('created_at')->values();
            break;
        case 'best_rated':
            $sorted = $vendors->sortByDesc('avg_product_rating')->values();
            break;
        case 'popular':
        default:
            // ── Composite Popularity Score ──
            // Weights: rating 30%, sales 30%, followers 25%, products 15%
            $maxRating    = $vendors->max('avg_product_rating') ?: 1;
            $maxSales     = $vendors->max('total_sales_count')  ?: 1;
            $maxFollowers = $vendors->max('followers_count')     ?: 1;
            $maxProducts  = $vendors->max('products_count')      ?: 1;

            foreach ($vendors as $vendor) {
                $normRating    = $vendor->avg_product_rating / $maxRating;
                $normSales     = $vendor->total_sales_count  / $maxSales;
                $normFollowers = $vendor->followers_count     / $maxFollowers;
                $normProducts  = $vendor->products_count      / $maxProducts;

                $vendor->popularity_score = round(
                    ($normRating    * 0.30) +
                    ($normSales     * 0.30) +
                    ($normFollowers * 0.25) +
                    ($normProducts  * 0.15),
                    4
                );
            }

            $vendorArray = $vendors->all();
            usort($vendorArray, function ($a, $b) {
                // Primary: popularity_score desc
                $cmp = $b->popularity_score <=> $a->popularity_score;
                if ($cmp !== 0) return $cmp;
                // Tiebreaker 1: products_count desc
                $cmp = $b->products_count <=> $a->products_count;
                if ($cmp !== 0) return $cmp;
                // Tiebreaker 2: avg_product_rating desc
                return $b->avg_product_rating <=> $a->avg_product_rating;
            });
            $sorted = collect($vendorArray)->values();
            break;
    }

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
                // Match private vendors by their generated slug pattern (sid-XXXXX)
                if (preg_match('/^sid-(\d+)$/i', $slug, $matches)) {
                    $vendorId = (int) ltrim($matches[1], '0');
                    $q->orWhere(function ($sub) use ($vendorId) {
                        $sub->where('id', $vendorId)->where('approval_type', 'private');
                    });
                }
            })
            ->withCount(['products' => function ($q) {
                $q->where('status', 'Active')
                    ->where(function ($sub) {
                        $sub->whereNull('vendor_id')
                            ->orWhere('vendor_approval_status', 'approved');
                    });
            }])
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
                'ProductResellerPrice',
                'ProductSalePrice',
                'min_sell_price',
                'Discount',
                'category_id',
                'vendor_id',
                'selling_type',
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

        // Include followers count
        $vendor->followers_count = $vendor->followers()->count();

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

    /**
     * Follow a vendor (toggle). If already following, does nothing.
     */
    public function followVendor(Request $request, $vendorId)
    {
        $user = Auth::user();
        $vendor = Vendor::find($vendorId);

        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found',
            ], 404);
        }

        $existing = VendorFollower::where('user_id', $user->id)
            ->where('vendor_id', $vendorId)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => true,
                'message' => 'Already following',
                'data' => ['is_following' => true, 'followers_count' => $vendor->followers()->count()],
            ], 200);
        }

        VendorFollower::create([
            'user_id' => $user->id,
            'vendor_id' => $vendorId,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Followed successfully',
            'data' => ['is_following' => true, 'followers_count' => $vendor->followers()->count()],
        ], 200);
    }

    /**
     * Unfollow a vendor.
     */
    public function unfollowVendor(Request $request, $vendorId)
    {
        $user = Auth::user();

        $deleted = VendorFollower::where('user_id', $user->id)
            ->where('vendor_id', $vendorId)
            ->delete();

        $vendor = Vendor::find($vendorId);
        $followersCount = $vendor ? $vendor->followers()->count() : 0;

        return response()->json([
            'status' => true,
            'message' => $deleted ? 'Unfollowed successfully' : 'Was not following',
            'data' => ['is_following' => false, 'followers_count' => $followersCount],
        ], 200);
    }

    /**
     * Check if the authenticated user is following a vendor.
     */
    public function checkFollowStatus($vendorId)
    {
        $user = Auth::user();

        $isFollowing = VendorFollower::where('user_id', $user->id)
            ->where('vendor_id', $vendorId)
            ->exists();

        $vendor = Vendor::find($vendorId);
        $followersCount = $vendor ? $vendor->followers()->count() : 0;

        return response()->json([
            'status' => true,
            'data' => ['is_following' => $isFollowing, 'followers_count' => $followersCount],
        ], 200);
    }
}
