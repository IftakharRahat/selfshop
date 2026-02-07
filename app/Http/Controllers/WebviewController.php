<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information;
use App\Models\Product;
use App\Models\Menu;
use App\Models\User;
use App\Models\Subcategory;
use App\Models\Category;
use App\Models\Attrvalue;
use App\Models\Basicinfo;
use App\Models\Order;
use App\Models\Resellerinvoice;
use App\Models\Package;
use App\Models\Withdrew;
use App\Models\Shopproduct;
use App\Models\Resellerapi;
use App\Models\Course;
use App\Models\Coursecategory;
use App\Models\Minicategory;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Session;
use Illuminate\Support\Str;


class WebviewController extends Controller
{

    public function loadfooter()
    {
        return view('frontend.content.bottomajax');
    }

    public function withdrews()
    {
        $withdrewlists = Withdrew::where('type', 'Withdrew')->where('user_id', Auth::user()->id)->get();
        return view('auth.withdrewlist', ['withdrewlists' => $withdrewlists]);
    }

    public function developersapi()
    {
        $api = Resellerapi::where('user_id', Auth::user()->id)->first();
        return view('auth.api', ['api' => $api]);
    }

    public function generatedevelopersapi()
    {
        $key = md5(microtime(true) . mt_Rand());
        $secret = Str::uuid()->toString();
        $api = new Resellerapi();
        $api->user_id = Auth::user()->id;
        $api->api_key = $key;
        $api->api_secret = $secret;
        $api->date = date('Y-m-d');
        $api->save();
        return redirect()->back()->with('success', 'API Generated Successfully');
    }

    public function checkuser(Request $request)
    {
        if (strlen($request->email) == '11') {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user = User::where('email', $request->email)->first();
            } else {
                $ema = '88' . $request->email;
                $user = User::where('email', $ema)->first();
            }
        } else {
            $user = User::where('email', $request->email)->first();
        }
        if (isset($user)) {
            return view('auth.userinfo', ['user' => $user]);
        } else {
            $user = '';
            return view('auth.userinfo');
        }
    }

    public function balancetransfer()
    {
        $transferlists = Withdrew::where('type', 'Transfer')->where('user_id', Auth::user()->id)->get();
        return view('auth.transferlist', ['transferlists' => $transferlists]);
    }

    public function transfernow(Request $request)
    {
        $user = User::where('id', Auth::user()->id)->first();

        if ($user->account_balance >= intval($request->withdraw_amount)) {
            $withdrew = new Withdrew();
            $withdrew->user_id = Auth::user()->id;
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
            $comment->user_id = Auth::guard('web')->user()->id;
            $comment->status = 1;
            $comment->type = 'Transfer';
            $comment->save();

            return response()->json($withdrew, 200);
        } else {
            return response()->json('lessblance', 200);
        }
    }
    public function course()
    {
        return view('auth.course');
    }

    public function coursedetails($slug)
    {
        $coursecategory = Coursecategory::where('slug', $slug)->first();
        $courses = Course::where('status', 'Active')->where('coursecategory_id', $coursecategory->id)->get();
        return view('auth.coursedetails', ['courses' => $courses, 'coursecategory' => $coursecategory]);
    }

    public function coursedetailsid($id)
    {
        $coursedetails = Course::where('id', $id)->first();
        $courses = Course::where('status', 'Active')->where('coursecategory_id', $coursedetails->coursecategory_id)->get();
        return view('auth.coursedetailsid', ['courses' => $courses, 'coursedetails' => $coursedetails]);
    }


    public function orders()
    {
        $orders =  Order::with(
            [
                'orderproducts' => function ($query) {
                    $query->select('id', 'order_id', 'product_id', 'productName', 'quantity', 'color', 'size');
                },
                'comments' => function ($query) {
                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                },
            ]
        )->where('user_id', Auth::guard('web')->user()->id)
            ->join('customers', 'customers.order_id', '=', 'orders.id')
            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')
            ->latest()
            ->paginate(30);
        return view('auth.orderhistory', ['orders' => $orders]);
    }

    public function slugorder($slug)
    {

        $orders =  Order::with(
            [
                'orderproducts' => function ($query) {
                    $query->select('id', 'order_id', 'product_id', 'productName', 'quantity', 'color', 'size');
                },
                'comments' => function ($query) {
                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                }
            ]
        )->where('user_id', Auth::guard('web')->user()->id)->where('status', $slug)
            ->join('customers', 'customers.order_id', '=', 'orders.id')
            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')
            ->paginate(20);

        return view('auth.orderhistory', ['orders' => $orders]);
    }

    public function packages(Request $request)
    {
        $invoice = Resellerinvoice::where('user_id', Auth::user()->id)->first();
        $packages = Package::where('status', 'Active')->get();
        return view('auth.package', ['invoice' => $invoice, 'packages' => $packages]);
    }

    public function teams()
    {
        $teams = User::where('refer_by', Auth::user()->my_referral_code)->latest()->paginate(18);
        return view('frontend.content.page.teams', ['teams' => $teams]);
    }

    public function updatebank()
    {
        $teams = User::where('refer_by', Auth::user()->my_referral_code)->get();
        return view('frontend.content.page.teams', ['teams' => $teams]);
    }

    public function support()
    {
        return view('frontend.content.page.support');
    }

    public function faq()
    {
        return view('frontend.content.page.faq');
    }

    public function profile()
    {
        $id = Auth::user()->id;
        $userprofile = User::findOrfail($id);
        return view('auth.profile', ['userprofile' => $userprofile]);
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
        return redirect()->back()->with('success', 'Profile update successfully');
    }

    public function orderhistory()
    {
        $date = \Carbon\Carbon::now();
        $orders =  Order::with(
            [
                'orderproducts' => function ($query) {
                    $query->select('id', 'order_id', 'productName', 'quantity', 'color', 'size');
                },
                'comments' => function ($query) {
                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                },
            ]
        )->where('user_id', Auth::guard('web')->user()->id)
            ->join('customers', 'customers.order_id', '=', 'orders.id')
            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')
            ->get();
        return view('auth.orderhistory', ['date' => $date, 'orders' => $orders]);
    }

    public function index($slug)
    {
        if ($slug == 'about_us') {
            $title = 'About US';
        } else if ($slug == 'contact_us') {
            $title = 'Contact Us';
        } else if ($slug == 'privacy-policy') {
            $title = 'Privacy Policy';
        } else if ($slug == 'investor_relation') {
            $title = 'Investor Relation';
        } else if ($slug == 'return-refund-policy') {
            $title = 'Return & Refund Policy';
        } else if ($slug == 'customer_service') {
            $title = 'Customer Service';
        } else if ($slug == 'help_center') {
            $title = 'Help Center';
        } else if ($slug == 'faq') {
            $title = 'FAQ';
        } else if ($slug == 'terms_codition') {
            $title = 'Terms & Conditions';
        } else {
        }

        $value = Information::where('key', $slug)->first();
        return view('webview.content.information.info', ['title' => $title, 'slug' => $slug, 'value' => $value]);
    }
    public function aboutus()
    {
        return view('webview.content.information.about');
    }
    public function contactus()
    {
        return view('webview.content.information.contact');
    }



public function productdetails($slug)
{
    $basicinfo = Basicinfo::first();
    $product = Product::where('ProductSlug', $slug)->first();
    
    // Check if product exists
    if (!$product) {
        // Product not found - redirect to 404 or home page
        abort(404, 'Product not found');
        // Or return a 404 view
        // return response()->view('errors.404', [], 404);
    }
    
    Session::put('relcategory_id', $product->category_id);
    $relatedproducts = Product::where('category_id', $product->category_id)
        ->where('status', 'Active')
        ->where('id', '!=', $product->id) // Exclude current product
        ->latest()
        ->paginate(12);
        
    return view('frontend.content.product.productdetails', [
        'basicinfo' => $basicinfo, 
        'relatedproducts' => $relatedproducts, 
        'product' => $product
    ]);
}

    public function relatedinfoajax(Request $request)
    {
        $category_id = Session::get('relcategory_id');
        $relatedproducts = Product::where('category_id', $category_id)->where('status', 'Active')->latest()->paginate(12);

        if ($request->ajax()) {
            $view = view('frontend.content.product.related', compact('relatedproducts'))->render();

            return response()->json(['html' => $view]);
        }
    }

    public function menuindex($slug)
    {
        $menus = Menu::where('slug', $slug)->select('id', 'menu_name', 'slug', 'status')->first();
        $value = Information::where('key', $slug)->first();
        return view('frontend.content.information.menuinfo', ['menus' => $menus, 'value' => $value]);
    }

    public function allcategories()
    {
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'slug', 'category_icon')->get();

        return view('frontend.content.product.categorylist', ['categories' => $categories]);
    }

    public function categoryall()
    {
        $categorylists = Category::where('status', 'Active')->select('id', 'category_name', 'slug', 'category_icon')->get();

        return view('frontend.content.categorylist', ['categorylists' => $categorylists]);
    }

    public function downloadimage(Request $request, $key)
    {
        $pro = Product::where('id', $request->product_id)->first();

        $imagePath = 'public/images/product/slider/' . json_decode($pro->PostImage)[$key];

        return response()->download($imagePath);
    }

    public function downloadimagesingle($id)
    {
        $product = Product::findOrfail($id);
        $imagePath = $product->ViewProductImage;

        return response()->download($imagePath);
    }

    public function addtoshop($id)
    {
        if (Auth::id()) {
            $ex = Shopproduct::where('product_id', $id)->where('user_id', Auth::user()->id)->first();
            if (isset($ex)) {
                return response()->json('exist', 200);
            } else {
                $add = new Shopproduct();
                $add->user_id = Auth::user()->id;
                $add->product_id = $id;
                $add->save();
                return response()->json('success');
            }
        } else {
            return response()->json('unverify', 200);
        }
    }

    public function removefromshop($id)
    {
        if (Auth::id()) {
            $ex = Shopproduct::where('product_id', $id)->where('user_id', Auth::user()->id)->first();
            if (isset($ex)) {
                $ex->delete();
                return response()->json('success');
            } else {
                return response()->json('notexist');
            }
        } else {
            return response()->json('unverify', 200);
        }
    }

    public function categoryproduct($slug)
    {
        $category_id = Category::where('slug', $slug)->first();
        Session::put('category_id', $category_id->id);
        if ($category_id) {
            $categoryproducts = Product::where('category_id', $category_id->id)->where('status', 'Active')->latest()->paginate(12);
        } else {
            $categoryproducts = [];
        }

        if (empty($categoryproducts)) {
            $categoryproducts = [];
            return view('frontend.content.product.categoryproduct', ['category_id' => $category_id, 'categoryproducts' => $categoryproducts]);
        }
        return view('frontend.content.product.categoryproduct', ['category_id' => $category_id, 'categoryproducts' => $categoryproducts]);
    }

    public function seeallproducts()
    {
        $categoryproducts = Product::where('status', 'Active')->latest()->paginate(12);

        if (empty($categoryproducts)) {
            $categoryproducts = [];
            return view('frontend.content.product.allproduct', ['categoryproducts' => $categoryproducts]);
        }
        return view('frontend.content.product.allproduct', ['categoryproducts' => $categoryproducts]);
    }

    public function allajaxproduct(Request $request)
    {
        $categoryproducts = Product::where('status', 'Active')->latest()->paginate(12);

        if ($request->ajax()) {
            $view = view('frontend.content.product.view', compact('categoryproducts'))->render();

            return response()->json(['html' => $view]);
        }
    }

    public function categoryinfoajax(Request $request)
    {
        $category_id = Session::get('category_id');
        $categoryproducts = Product::where('category_id', $category_id)->where('status', 'Active')->latest()->paginate(12);

        if ($request->ajax()) {
            $view = view('frontend.content.product.view', compact('categoryproducts'))->render();

            return response()->json(['html' => $view]);
        }
    }

    public function getcategoryproduct(Request $request)
    {
        $category = Category::where('slug', $request->category)->select('id', 'category_name', 'slug', 'status')->first();
        if (isset($request->price_range)) {
            $num = preg_split("/[,]/", $request->price_range);
            $categoryproducts = Product::where('category_id', $category->id)->where('status', 'Active')->whereBetween('ProductSalePrice', $num)->get();
        } else {
            $categoryproducts = Product::where('category_id', $category->id)->where('status', 'Active')->get();
        }
        return view('frontend.content.product.view', ['categoryproducts' => $categoryproducts, 'category' => $category]);
    }
// In your Controller (e.g., TestController.php)
public function testMinicategories()
{
    // Get all minicategories with their relationships
    $minicategories = Minicategory::with([
        'subcategory:id,sub_category_name,category_id',
        'subcategory.category:id,category_name'
    ])->get();
    
    // Or if you want to get all separately
    $categories = Category::with(['subcategories', 'subcategories.minicategories'])->get();
    
    return view('test.minicategories', compact('minicategories', 'categories'));
}
    public function slugProduct($slug)
    {
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'slug', 'category_icon')->get();
        if ($slug == 'best') {
            return view('frontend.content.product.slugproduct', ['categories' => $categories, 'slug' => $slug]);
        } elseif ($slug == 'featured') {
            return view('frontend.content.product.slugproduct', ['categories' => $categories, 'slug' => $slug]);
        } elseif ($slug == 'promotional') {
            return view('frontend.content.product.slugproduct', ['categories' => $categories, 'slug' => $slug]);
        } else {
            abort(404);
        }
        return view('frontend.content.product.slugproduct', ['categories' => $categories, 'slug' => $slug]);
    }

    public function getslugproduct(Request $request)
    {
        $categories = Category::where('status', 'Active')->select('id', 'category_name', 'slug', 'category_icon')->get();
        if ($request->slug == 'best') {
            $slugproducts = Product::where('best_selling', '0')->where('status', 'Active')->get();
        } elseif ($request->slug == 'featured') {
            $slugproducts = Product::where('frature', '0')->where('status', 'Active')->get();
        } elseif ($request->slug == 'promotional') {
            $slugproducts = Product::where('top_rated', '1')->where('status', 'Active')->get();
        } else {
            abort(404);
        }
        return view('frontend.content.product.slugview', ['categories' => $categories, 'slugproducts' => $slugproducts]);
    }

    public function getsubcategoryproduct(Request $request)
    {
        $subcategory = Subcategory::where('slug', $request->subcategory)->select('id', 'sub_category_name', 'slug', 'status')->first();
        if (isset($request->price_range)) {
            $num = preg_split("/[,]/", $request->price_range);
            $subcategoryproducts = Product::where('subcategory_id', $subcategory->id)->where('status', 'Active')->whereBetween('ProductSalePrice', $num)->get();
        } else {
            $subcategoryproducts = Product::where('subcategory_id', $subcategory->id)->where('status', 'Active')->get();
        }
        return view('frontend.content.product.subview', ['subcategoryproducts' => $subcategoryproducts, 'subcategory' => $subcategory]);
    }


    public function subcategoryproduct($slug)
    {
        $subcategory = Subcategory::where('slug', $slug)->select('id', 'sub_category_name', 'subcategory_icon', 'slug', 'status')->first();
        Session::put('subcategory_id', $subcategory->id);
        $categoryproducts = Product::where('subcategory_id', $subcategory->id)->where('status', 'Active')->get();

        return view('frontend.content.product.subcategoryproduct', ['categoryproducts' => $categoryproducts, 'subcategory' => $subcategory]);
    }

    public function subcategoryinfoajax(Request $request)
    {
        $category_id = Session::get('subcategory_id');
        $categoryproducts = Product::where('subcategory_id', $category_id)->where('status', 'Active')->latest()->paginate(12);

        if ($request->ajax()) {
            $view = view('frontend.content.product.view', compact('categoryproducts'))->render();

            return response()->json(['html' => $view]);
        }
    }


    public function minicategoryproduct($slug)
    {
        $minicategory = Minicategory::where('slug', $slug)->select('id', 'mini_category_name', 'minicategory_icon', 'slug', 'status')->first();
        $categoryproducts = Product::where('minicategory_id', $minicategory->id)->where('status', 'Active')->get();
        Session::put('minicategory_id', $minicategory->id);
        return view('frontend.content.product.minicategoryproduct', ['categoryproducts' => $categoryproducts, 'minicategory' => $minicategory]);
    }

    public function minicategoryinfoajax(Request $request)
    {
        $category_id = Session::get('minicategory_id');
        $categoryproducts = Product::where('minicategory_id', $category_id)->where('status', 'Active')->latest()->paginate(12);

        if ($request->ajax()) {
            $view = view('frontend.content.product.view', compact('categoryproducts'))->render();

            return response()->json(['html' => $view]);
        }
    }



    public function searchcontent(Request $request)
    {

        $searchcontents = Product::where('ProductName', 'LIKE', '%' . $request->search . '%')->get()->reverse();
        Session::put('searchkeyword', $request->search);
        return view('frontend.content.product.search', ['searchcontents' => $searchcontents]);
    }

    public function myshop($code)
    {
        $shops = Shopproduct::where('user_id', Auth::user()->id)->get();
        if (count($shops) > 0) {
            foreach ($shops as $shop) {
                $ex = Product::where('id', $shop->product_id)->first();
                if ($ex) {
                    $searchcontents[] = $ex;
                }
            }
        } else {
            $searchcontents = [];
        }


        return view('frontend.content.product.shop', ['searchcontents' => $searchcontents]);
    }


    public function loadproduct($slug)
    {
        if ($slug == 'hot_selling') {
            $title = 'Hot Selling Products';
            $searchcontents = Product::where('status', 'Active')->where('hot_list', 'On')->get();
        } elseif ($slug == 'ready_to_bost') {
            $title = 'Ready To Bost Products';
            $searchcontents = Product::where('status', 'Active')->where('ready_bost', 'On')->get();
        } elseif ($slug == 'profitable_product') {
            $title = 'Profitable Products';
            $searchcontents = Product::where('status', 'Active')->where('profitable', 'On')->get();
        } elseif ($slug == 'new_arrivel') {
            $title = 'New Arrivel Products';
            $searchcontents = Product::where('status', 'Active')->where('show_new_product', 'On')->get();
        } elseif ($slug == 'limited_offer') {
            $title = 'Limited Offer Products';
            $searchcontents = Product::where('status', 'Active')->where('limited', 'On')->get();
        } elseif ($slug == 'summer_collection') {
            $title = 'Summer Collection Products';
            $searchcontents = Product::where('status', 'Active')->where('summer', 'On')->get();
        } else {
            $title = 'Product List';
            $searchcontents = [];
        }

        return view('frontend.content.product.listwiseproduct', ['searchcontents' => $searchcontents, 'title' => $title]);
    }

    public function orderTraking(Request $request)
    {
        $orders = 'Nothing';
        return view('frontend.content.cart.trackorder', ['orders' => $orders]);
    }

    public function orderTrakingNow(Request $request)
    {
        $orders = Order::with(['customers', 'orderproducts', 'couriers', 'cities', 'zones', 'admins'])->where('invoiceID', $request->invoiceID)->first();
        return view('frontend.content.cart.trackorder', ['orders' => $orders]);
    }

    public function orderTrakingLoad(Request $request)
    {
        $orders = Order::where('invoiceID', $request->invoiceID)->first();
        return view('frontend.content.cart.loadtrackorder', ['orders' => $orders]);
    }

    public function packagelist(Request $request)
    {
        $packages = Package::where('status', 'Active')->get();
        $pack = Package::where('id', $request->pack_id)->first();
        return view('auth.pack', ['pack' => $pack, 'packages' => $packages]);
    }

    public function purchese(Request $request)
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
        return redirect()->back()->with('success', 'Invoice Created. Please pay your invoice now');
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
}
