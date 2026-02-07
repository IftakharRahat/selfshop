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
use App\Models\Shopproduct;
use App\Models\Slider;
use App\Models\Subcategory;
use App\Models\Tikit;
use App\Models\User;
use App\Models\Varient;
use App\Models\Withdrew;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Str;

class FrontendApiController extends Controller
{

    public function packages()
    {
        $invoice = Resellerinvoice::where('user_id', Auth::user()->id)->first();
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
        $invoice = new Resellerinvoice();
        $invoice->invoiceID = $this->uniqueinvoiceID();
        $invoice->user_id = Auth::user()->id;
        $invoice->package_id = $request->package_id;
        $invoice->resellerid = Auth::user()->my_referral_code;
        $invoice->amount = $request->amount;
        $invoice->payable_amount = $request->amount;
        $invoice->invoiceDate = date('Y-m-d');
        $invoice->save();
        $user = User::where('id', Auth::user()->id)->first();
        $user->isInvoice = 'yes';
        $user->update();
        return response()->json([
            'status' => true,
            'message' => 'Purchese Package',
            'data' => [
                'invoice' => $invoice,
            ],
        ], 200);
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


        if ($categories->count() > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Category Information',
                'data' => $categories
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Category Information Not Found',
        ], 404);
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

        if ($sliders) {
            return response()->json([
                'status' => true,
                'message' => 'Slider Information',
                'data' => $sliders
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Slider Information Not Found',
        ], 404);
    }

    public function bottombanners()
    {
        $adsbanner = Addbanner::where('status', 'Inactive')->select('icon')->get();

        if ($adsbanner) {
            return response()->json([
                'status' => true,
                'message' => 'Slider Bottom Banners',
                'data' => $adsbanner
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'lider Bottom Banner Not Found',
        ], 404);
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
        $limit = $request->limit;
        if ($slug == 'hot_selling') {
            $title = 'Hot Selling Products';
            $total = Product::where('status', 'Active')->where('hot_list', 'On')->count();
            $searchcontents = Product::where('status', 'Active')->where('hot_list', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'ready_to_bost') {
            $title = 'Ready To Bost Products';
            $total = Product::where('status', 'Active')->where('ready_bost', 'On')->count();
            $searchcontents = Product::where('status', 'Active')->where('ready_bost', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'profitable_product') {
            $title = 'Profitable Products';
            $total = Product::where('status', 'Active')->where('profitable', 'On')->count();
            $searchcontents = Product::where('status', 'Active')->where('profitable', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'new_arrivel') {
            $title = 'New Arrivel Products';
            $total = Product::where('status', 'Active')->where('show_new_product', 'On')->count();
            $searchcontents = Product::where('status', 'Active')->where('show_new_product', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'limited_offer') {
            $title = 'Limited Offer Products';
            $total = Product::where('status', 'Active')->where('limited', 'On')->count();
            $searchcontents = Product::where('status', 'Active')->where('limited', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } elseif ($slug == 'summer_collection') {
            $title = 'Summer Collection Products';
            $total = Product::where('status', 'Active')->where('summer', 'On')->count();
            $searchcontents = Product::where('status', 'Active')->where('summer', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);
        } else {
            $title = 'Product Not Found';
            $searchcontents = [];
        }

        if ($searchcontents->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Product Found',
                'total' => $total,
                'data' => []
            ], 404);
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
        $limit = $request->limit;
        $total = Product::where('status', 'Active')->where('show_new_product', 'On')->count();

        $searchcontents = Product::where('status', 'Active')->where('show_new_product', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);

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
        $limit = $request->limit;
        $total = Product::where('status', 'Active')->where('show_new_product', 'On')->count();

        $searchcontents = Product::where('status', 'Active')->where('show_new_product', 'On')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);

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
        $limit = $request->limit;
        $total = Product::where('status', 'Active')->where('frature', '0')->count();

        $searchcontents = Product::where('status', 'Active')->where('frature', '0')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);

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
        $limit = $request->limit;
        $total = Product::where('status', 'Active')->where('top_rated', '1')->count();
        $searchcontents = Product::where('status', 'Active')->where('top_rated', '1')->select('id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->paginate($limit);


        if ($searchcontents->count() == 0) {
            return response()->json([
                'status' => false,
                'total' => $total,
                'message' => 'No big selling products Found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Big selling products found successfully',
            'total' => $total,
            'data' => $searchcontents
        ], 200);
    }

    public function productbycategory($slug)
    {
        $category = Category::where('slug', $slug)->first();
        $categoryproducts = Product::where('status', 'Active')->where('category_id', $category->id)->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->get();

        if ($categoryproducts->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No products found with this category',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Products found with this category successfully',
            'data' => $categoryproducts
        ], 200);
    }

    public function productbysubcategory($slug)
    {
        $subcategory = Subcategory::where('slug', $slug)->first();
        $subcategoryproducts = Product::where('status', 'Active')->where('subcategory_id', $subcategory->id)->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->get();

        if ($subcategoryproducts->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No products found with this sub-category',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Products found with this sub-category successfully',
            'data' => $subcategoryproducts
        ], 200);
    }

    public function productbybrand($slug)
    {
        $brand = Brand::where('slug', $slug)->first();
        $brandproducts = Product::where('status', 'Active')->where('brand_id', $brand->id)->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->get();

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
        $products = Product::where('status', 'Active')->where('ProductName', 'LIKE', '%' . $request->keywords . '%')->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductRegularPrice', 'ProductSalePrice', 'ProductResellerPrice', 'Discount', 'ViewProductImage')->get();

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
        $product = Product::with('varients')->where('ProductSlug', $slug)->first();
        $relatedproducts = Product::where('category_id', $product->category_id)->where('status', 'Active')->latest()->paginate(12);

        if ($product->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Products Details & Related Products',
            ], 404);
        }

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


        if (isset($request->refer_by)) {
            $validity = User::where('my_referral_code', $request->refer_by)->first();
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
                if (isset($request->refer_by)) {
                    $user->refer_by = $request->refer_by;
                } else {
                    $user->refer_by = $validity->my_referral_code;;
                }
                $otp = random_int(100000, 999999);
                $user->otp = $otp;
                $otppass = $otp;
                $user->password = Hash::make($request->password);
                $success = $user->save();

                if ($success) {
                    if (isset($request->refer_by)) {
                        $createreferral = User::where('my_referral_code', $request->refer_by)->first();
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

    public function orders($slug)
    {
        $id = Auth::user()->id;
        $orders = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])->where('user_id', $id)->where('status', $slug)->paginate(30);

        if ($orders) {
            return response()->json([
                'status' => true,
                'message' => 'Order list',
                'total' => Order::where('user_id', $id)->where('status', $slug)->get()->count(),
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
                'packageing' => Order::where('user_id', $id)->where('status', 'Packageing')->get()->count(),
                'ontheway' => Order::where('user_id', $id)->where('status', 'Ontheway')->get()->count(),
                'delivered' => Order::where('user_id', $id)->where('status', 'Delivered')->get()->count(),
                'return' => Order::where('user_id', $id)->where('status', 'Return')->get()->count(),
            ]
        ], 200);
    }

    public function trackorder(Request $request)
    {
        $orders = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])->where('invoiceID', $request->invoiceID)->first();

        if ($orders) {
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
        $lists = Productrequest::where('from_id', $id)->latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Requested Product Lists',
            'data' => $lists
        ], 200);
    }


    public function productrequest(Request $request)
    {
        $product = new Productrequest();
        $productImg = $request->file('attachment');
        $time = microtime('.') * 10000;
        if ($productImg) {
            $imgname = $time . $productImg->getClientOriginalName();
            $imguploadPath = ('public/images/user/profile/');
            $productImg->move($imguploadPath, $imgname);
            $productImgUrl = $imguploadPath . $imgname;
            $product->attachment = $productImgUrl;
        }
        $id = Auth::user()->id;
        $product->from_id = $id;
        $product->p_name = $request->p_name;
        $product->save();
        return response()->json([
            'status' => true,
            'message' => 'Product request give successfully',
            'data' => $product
        ], 200);
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

    public function withdrawrequest(Request $request)
    {
        $id = Auth::user()->id;
        $user = User::where('id', $id)->where('status', 'Active')->first();

        if (isset($user)) {
            if ($user->account_balance >= intval($request->withdrew_amount)) {
                $paymenttypes = Paymenttype::where('id', $request->paymenttype_id)->first();
                $withdrew = new Withdrew();
                $withdrew->user_id = $id;
                $withdrew->paymenttype_id = $request->paymenttype_id;
                $withdrew->paymenttype_name = $paymenttypes->paymentTypeName;
                $withdrew->to_account_number = $request->to_account_number;
                $withdrew->withdrew_amount = $request->withdrew_amount;
                $success = $withdrew->save();

                $user->account_balance = $user->account_balance - $request->withdrew_amount;
                $user->pending_cashout_balance = $user->pending_cashout_balance + $request->withdrew_amount;
                $user->update();


                $comment = new Comment();
                $comment->comment = 'You have sent a payment request via ' . $paymenttypes->paymentTypeName . ' Invoice ID: #IN00' . $withdrew->id;
                $comment->user_id = $id;
                $comment->status = 1;
                $comment->type = 'Withdraw';
                $comment->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Withdraw request send successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Not enough balance',
                ], 404);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Not enough balance',
            ], 404);
        }
    }


    public function transferlists()
    {
        $id = Auth::user()->id;
        $transferlists = Withdrew::where('type', 'Transfer')->where('user_id', $id)->get();
        return response()->json([
            'status' => true,
            'message' => 'Transfer lists',
            'data' => $transferlists
        ], 200);
    }

    public function transfernow(Request $request)
    {
        $id = Auth::user()->id;
        $user = User::where('id', $id)->first();

        if ($user->account_balance >= intval($request->withdrew_amount)) {
            $withdrew = new Withdrew();
            $withdrew->user_id = $id;
            $withdrew->type = 'Transfer';
            $withdrew->to_account_number = $request->to_account_number;
            $withdrew->withdrew_amount = $request->withdrew_amount;
            $withdrew->status = 'Paid';
            $success = $withdrew->save();
            if ($success) {
                if (strlen($request->to_account_number) == '11') {
                    $traccount = User::where('email', $request->to_account_number)->first();
                    if ($traccount) {
                        $traccount = User::where('email', $request->to_account_number)->first();
                    } else {
                        $ema = '88' . $request->to_account_number;
                        $traccount = User::where('email', $ema)->first();
                    }
                } else {
                    $traccount = User::where('email', $request->to_account_number)->first();
                }

                if (isset($traccount)) {
                    $traccount->account_balance = $traccount->account_balance + $request->withdrew_amount;
                    $traccount->update();

                    $commentss = new Comment();
                    $commentss->comment = 'Received ' . $request->withdrew_amount . 'TK From User: ' . $user->name . ' (' . $user->email . ') Invoice ID: #IN00' . $withdrew->id;
                    $commentss->user_id = $traccount->id;
                    $commentss->status = 1;
                    $commentss->type = 'Transfer';
                    $commentss->save();
                }

                $user->account_balance = $user->account_balance - $request->withdrew_amount;
                $user->update();
            }

            $comment = new Comment();
            $comment->comment = 'You sent ' . $request->withdrew_amount . 'TK Invoice ID: #IN00' . $withdrew->id;
            $comment->user_id = $id;
            $comment->status = 1;
            $comment->type = 'Transfer';
            $comment->save();

            return response()->json([
                'status' => true,
                'message' => 'Tranfer request send successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Not enough balance',
            ], 404);
        }
    }


    public function incomehistory()
    {
        $user = User::where('id', Auth::user()->id)->first();
        $messages = Income::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($income) {
                // Find order by invoice_id
                $order = Order::where('invoiceID', $income->invoice_id)->first();

                if ($order) {
                    // Sum product prices from OrderProducts table
                    $totalProductPrice = Orderproduct::where('order_id', $order->id)->sum('productPrice');

                    // Add new field to array
                    $income->product_price = $totalProductPrice;
                } else {
                    $income->product_price = 0;
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

    public function dashboarddata()
    {
        $id = Auth::user()->id;

        $myorders = Order::where('user_id', Auth::user()->id)->get()->groupBy('orderDate');
        $sales = [];
        foreach ($myorders as $key => $myorder) {
            $sales[] = array(
                'y' => $myorder->sum('subTotal') + $myorder->sum('paymentAmount'),
                'label' => $key,
            );
        }



        return response()->json([
            'status' => true,
            'message' => 'Dashboard data',
            'data' => [
                'total_sales' => Order::where('user_id', $id)->where('status', '!=', 'Canceled')->get()->sum('subTotal') + Order::where('user_id', $id)->where('status', '!=', 'Canceled')->get()->sum('paymentAmount') - Order::where('user_id', $id)->where('status', '!=', 'Canceled')->get()->sum('deliveryCharge'),
                'total_profit' => Order::where('user_id', $id)->where('status', 'Delivered')->get()->sum('profit'),
                'blance' => Auth::user()->account_balance,
                'withdraw' => Auth::user()->cashout_balance,
                'shop_products' => Shopproduct::where('user_id', Auth::user()->id)->get()->count(),
                'total_orders' => Order::where('user_id', Auth::user()->id)->get()->count(),
                'sales' => $sales
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
        $orders = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])->where('invoiceID', $id)->first();

        if ($orders) {
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
}
