<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Addbanner;
use App\Models\Admin;
use App\Models\Basicinfo;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Information;
use App\Models\Like;
use App\Models\Mainproduct;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\React;
use App\Models\Review;
use App\Models\Share;
use App\Models\Size;
use App\Models\Slider;
use App\Models\Subcategory;
use App\Models\Usecoupon;
use App\Models\User;
use App\Models\Varient;
use App\Models\Weight;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class frontendapicon extends Controller
{
    public function allProducts(Request $request)
    {
        $products = Mainproduct::where('status', 'Active')
            ->select(
                'id',
                'ProductName',
                'ProductSlug',
                'ProductImage',
                'status',
                'position',
                'top_rated',
                'RelatedProductIds'
            )
            ->orderBy('position', 'asc')
            ->paginate(12)->withQueryString();

        $products->each(function ($product) {

            $product_id = json_decode($product->RelatedProductIds)[0]->productID ?? null;

            if ($product_id) {
                $regularPrice = Size::where('product_id', $product_id)->pluck('RegularPrice')->first();
                $salePrice = Size::where('product_id', $product_id)->pluck('SalePrice')->first();
                $discount = Size::where('product_id', $product_id)->pluck('Discount')->first();

                $product->RegularPrice = $regularPrice;
                $product->SalePrice = $salePrice;
                $product->Discount = $discount;
                $product->sold_qty = Size::where('product_id', $product_id)->pluck('sold')->first();

                // Calculate and attach average rating
                $product->averageRating = round(Review::where('product_id', $product_id)->avg('rating'));
            }
        });

        if ($products) {
            return response()->json([
                'status' => true,
                'message' => 'All Products Info',
                'data' => $products
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Front Categories Information Not Found',
        ], 404);
    }



    public function userRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['string', 'email', 'max:255'],
            'phone' => ['string', 'max:255'],
        ]);


        $olduseremail = User::where('email', $request->email)->first();
        if (isset($olduseremail)) {
            return response()->json([
                'status' => false,
                'message' => 'Email already exist !',
            ], 409);
        } else {
            $oldphone = User::where('phone', $request->phone)->first();
            if (isset($oldphone)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Phone number already exist !',
                ], 409);
            } else {
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $otp = random_int(100000, 999999);
                $user->otp = $otp;
                $otppass = $otp;
                $user->active_status = 0;
                $user->password = Hash::make($request->password);
                $success = $user->save();
            }
        }


        if ($success) {
            Auth::login($user);

            return response()->json([
                'status' => true,
                'message' => 'Authentication Successful',
                'token' => Auth::user()->createToken('authToken')->plainTextToken,
                'token_type' => 'Bearer',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'User Registration Failed !',
        ], 500);
    }


    public function userLogin(Request $request)
    {
        $user = DB::table('users')->whereIn('status', ['Active', 'Inactive'])->where('email', $request->email)
            ->first();

        if (isset($user)) {
            if (Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])) {
                return response()->json([
                    'status' => true,
                    'message' => 'Authentication Successful',
                    'token' => Auth::user()->createToken('authToken')->plainTextToken,
                    'token_type' => 'Bearer',
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Password Does not Match',
                ], 404);
            }
        } else {
            $user = DB::table('users')->where('email', $request->phone)
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


    public function sliderData()
    {
        $sliders = Slider::where('status', 'Active')->select('slider_image', 'slug', 'slider_btn_link')->get();

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

    public function adBanner()
    {
        $adBanners = Addbanner::where('status', 'Active')->select('add_image', 'add_link', 'category_slug as slug')->get();


        if ($adBanners) {
            return response()->json([
                'status' => true,
                'message' => 'Ad Banner Information',
                'data' => $adBanners
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Ad Banner Information Not Found',
        ], 404);
    }


    public function frontCategories(string $slug)
    {
        $categoryproducts = Category::where('status', 'Active')
            ->where('slug', $slug)
            ->where('front_status', 0)
            ->select('id', 'category_name', 'slug', 'position')
            ->first();

        if (!$categoryproducts)
            return response()->json([
                'status' => false,
                'message' => 'Front Category Products Not Found',
            ], 404);


        $mainproducts = $categoryproducts->mainproducts()
            ->join('categories', 'mainproducts.category_id', '=', 'categories.id')
            ->select(
                'mainproducts.id',
                //                'mainproducts.category_id',
                //                'categories.category_name',
                //                'categories.slug as category_slug',
                'mainproducts.ProductName',
                'mainproducts.ProductSlug',
                'mainproducts.ProductImage',
                'mainproducts.status',
                'mainproducts.position',
                'mainproducts.top_rated',
                'mainproducts.RelatedProductIds'
            )
            ->where('mainproducts.status', 'Active')
            ->orderBy('mainproducts.position', 'asc')
            ->paginate(12)->withQueryString();

        $mainproducts->transform(function ($mainproduct) {
            if (!empty($mainproduct->RelatedProductIds)) {
                $relatedProductIds = json_decode($mainproduct->RelatedProductIds);

                if (is_array($relatedProductIds) && isset($relatedProductIds[0]->productID)) {
                    $relatedProductId = $relatedProductIds[0]->productID;

                    $regularPrice = Size::where('product_id', $relatedProductId)->pluck('RegularPrice')->first();
                    $salePrice = Size::where('product_id', $relatedProductId)->pluck('SalePrice')->first();
                    $discount = Size::where('product_id', $relatedProductId)->pluck('Discount')->first();


                    $mainproduct->RegularPrice = $regularPrice;
                    $mainproduct->Discount = $salePrice;
                    $mainproduct->SalePrice =  $discount;
                    $mainproduct->sold_qty = Size::where('product_id', $relatedProductId)->pluck('sold')->first();

                    $mainproduct->averageRating = round(Review::where('product_id', $relatedProductId)->avg('rating'));
                } else {
                    $mainproduct->relatedProduct = null;
                    $mainproduct->averageRating = null;
                }
            } else {
                $mainproduct->relatedProduct = null;
                $mainproduct->averageRating = null;
            }

            return $mainproduct;
        });

        if ($mainproducts) {
            return response()->json([
                'status' => true,
                'message' => 'Front Category Products Found',
                'data' => $mainproducts
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Categories Information Not Found',
        ], 404);
    }


    public function categoryData()
    {
        //        $categoryData = Category::where('status', 'Active')->get();

        $categories = Category::where('status', 'Active')->select(
            'id',
            'category_name',
            'slug',
            'category_icon',
            'front_status',
            DB::raw("CASE WHEN front_status = 0 THEN 'active' ELSE 'inactive' END as front_status_label"),
        )

            //            ->with('subcategories', function ($query) {
            //                $query->select('id', 'sub_category_name', 'slug', 'category_id','subcategory_icon')->where('status', 'Active');
            //            }
            //


            ->get();


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


    public function subcategoryByCategory(string $slug)
    {
        $category = Category::where('slug', $slug)->first();

        $subcategories = Subcategory::where('category_id', $category->id)->where('status', 'Active')
            ->select('id', 'sub_category_name', 'slug', 'category_id', 'subcategory_icon')->get();

        if ($subcategories->count() > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Subcategory Information Found',
                'data' => $subcategories
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Subcategory Information Not Found',
        ], 404);
    }
    public function ProductsByCategory(string $slug)
    {
        $categorysingle = Category::where('slug', $slug)->select('id', 'category_name', 'slug', 'status')->first();

        $mainproducts = $categorysingle->mainproducts()
            ->select('id', 'category_id', 'ProductName', 'ProductSlug', 'ProductImage', 'status', 'position', 'top_rated', 'RelatedProductIds')
            ->where('status', 'Active')
            ->orderBy('position', 'desc')
            ->paginate(12)->withQueryString();

        $mainproducts->transform(function ($mainproduct) {
            if (!empty($mainproduct->RelatedProductIds)) {
                $relatedProductIds = json_decode($mainproduct->RelatedProductIds);

                if (is_array($relatedProductIds) && isset($relatedProductIds[0]->productID)) {
                    $relatedProductId = $relatedProductIds[0]->productID;

                    $regularPrice = Size::where('product_id', $relatedProductId)->pluck('RegularPrice')->first();
                    $salePrice = Size::where('product_id', $relatedProductId)->pluck('SalePrice')->first();
                    $discount = Size::where('product_id', $relatedProductId)->pluck('Discount')->first();


                    $mainproduct->RegularPrice = $regularPrice;
                    $mainproduct->Discount = $salePrice;
                    $mainproduct->SalePrice =  $discount;
                    $mainproduct->sold_qty = Size::where('product_id', $relatedProductId)->pluck('sold')->first();

                    $mainproduct->averageRating = round(Review::where('product_id', $relatedProductId)->avg('rating'));
                } else {
                    $mainproduct->relatedProduct = null;
                    $mainproduct->averageRating = null;
                }
            } else {
                $mainproduct->relatedProduct = null;
                $mainproduct->averageRating = null;
            }

            return $mainproduct;
        });

        if ($mainproducts) {
            return response()->json([
                'status' => true,
                'message' => 'Products Information by Category',
                'data' => $mainproducts
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Categories Information Not Found',
        ], 404);
    }



    public function ProductsBySubcategory(string $slug)
    {
        $categorysingle = Subcategory::where('slug', $slug)
            ->where('status', 'Active')
            ->select('id', 'sub_category_name', 'slug', 'category_id', 'status')->first();

        $mainproducts = $categorysingle->mainproducts()
            ->select('id', 'category_id', 'ProductName', 'ProductSlug', 'ProductImage', 'status', 'position', 'top_rated', 'RelatedProductIds')
            ->where('status', 'Active')
            ->orderBy('position', 'asc')
            ->paginate(12)->withQueryString();

        $mainproducts->getCollection()
            ->transform(function ($mainproduct) {
                if (!empty($mainproduct->RelatedProductIds)) {
                    $relatedProductIds = json_decode($mainproduct->RelatedProductIds);

                    if (is_array($relatedProductIds) && isset($relatedProductIds[0]->productID)) {
                        $relatedProductId = $relatedProductIds[0]->productID;

                        $regularPrice = Size::where('product_id', $relatedProductId)->pluck('RegularPrice')->first();
                        $salePrice = Size::where('product_id', $relatedProductId)->pluck('SalePrice')->first();
                        $discount = Size::where('product_id', $relatedProductId)->pluck('Discount')->first();


                        $mainproduct->RegularPrice = $regularPrice;
                        $mainproduct->SalePrice =  $discount;
                        $mainproduct->Discount = $salePrice;
                        $mainproduct->sold_qty = Size::where('product_id', $relatedProductId)->pluck('sold')->first();

                        $mainproduct->averageRating = round(Review::where('product_id', $relatedProductId)->avg('rating'));
                    } else {
                        $mainproduct->relatedProduct = null;
                        $mainproduct->averageRating = null;
                    }
                } else {
                    $mainproduct->relatedProduct = null;
                    $mainproduct->averageRating = null;
                }

                return $mainproduct;
            });

        if ($mainproducts) {
            return response()->json([
                'status' => true,
                'message' => 'Products Information by Subcategory',
                'data' => $mainproducts

            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Categories Information Not Found',
        ], 404);
    }

    public function productDetails(string $slug)
    {
        //        $productDetails = Product::where('ProductSlug', $slug)->with('sizes', 'weights', 'varients', 'categories',
        //            'subcategories')->first();

        $singlemain = Mainproduct::where('ProductSlug', $slug)->select('id', 'category_id', 'RelatedProductIds as varientIds')->first();

        $allIds = json_decode($singlemain->varientIds);
        //        dd($allIds);
        $varientImgs = [];
        foreach ($allIds as $key => $id) {
            $varientImgs[] = [

                'product_id' => $id->productID,
                'product_image' => Product::where('id', $id->productID)->pluck('ProductImage')->first()

            ];
        }





        $id = json_decode($singlemain->varientIds)[0]->productID;
        $productdetails = Product::with([

            'categories' => function ($query) {
                $query->select('id', 'category_name', 'category_icon', 'slug', 'status');
            },

            'subcategories' => function ($query) {
                $query->select('id', 'sub_category_name', 'slug', 'subcategory_icon', 'category_id', 'status');
            },

            'brands' => function ($query) {
                $query->select('id', 'brand_name', 'slug', 'brand_icon', 'status');
            },

            'sizes' => function ($query) {
                $query->select('id', 'product_id', 'size_id', 'size', 'Discount', 'RegularPrice', 'SalePrice', 'total_stock', 'available_stock', 'sold')->where('status', 'Active');
            },
            'weights' => function ($query) {
                $query->select('id', 'product_id', 'Discount', 'RegularPrice', 'SalePrice');
            },

            'colors' => function ($query) {
                $query->select('id', 'product_id', 'color_id', 'color', 'Image');
            },


        ])->where('id', $id)
            ->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductImage', 'ProductBreaf', 'ProductDetails', 'ProductSku', 'PostImage', 'ViewProductImage', 'youtube_embade', 'MetaTitle', 'MetaKey', 'MetaDescription', 'status')
            ->first();




        $productdetails->RegularPrice = $productdetails->sizes[0]->RegularPrice;
        $productdetails->SalePrice = $productdetails->sizes[0]->SalePrice;
        $productdetails->Discount = $productdetails->sizes[0]->Discount;
        $productdetails->total_stock = $productdetails->sizes[0]->total_stock;
        $productdetails->available_stock = $productdetails->sizes[0]->available_stock;
        $productdetails->sold_qty = $productdetails->sizes[0]->sold;
        //        $varients = Varient::where('product_id', $productdetails->id)->get();



        //        $productdetails->images=[
        //          'product_images'=>[$productdetails->ProductImage,
        //            $productdetails->ViewProductImage],
        //
        //            'slider_images'=>[]
        //            ];
        //
        //        $postImgString= json_decode($productdetails->PostImage,true);
        //
        //        foreach ($postImgString as $key=> $img)
        //        {
        //            $productdetails->images['slider_images'][]=$img;
        //        }

        $images = [

            $productdetails->ViewProductImage,
            $productdetails->ProductImage,

        ];

        // Decode PostImage and add to slider_images
        $postImgArray = json_decode($productdetails->PostImage, true); // Decode as array

        if (is_array($postImgArray)) {
            foreach ($postImgArray as $img) {
                $images[] = 'public/images/product/slider/' . $img;
            }
        }

        // Assign the complete array to the model property
        $productdetails->images = $images;


        $relatedproducts = Mainproduct::where('category_id', $singlemain->category_id)->where('status', 'Active')->where('top_rated', '1')->orderByRaw('ISNULL(`position`), `position` ASC')
            ->select('id', 'ProductName', 'ProductSlug', 'ProductImage', 'status', 'position', 'top_rated', 'RelatedProductIds')
            ->inRandomOrder()->limit(8)->get();



        $relatedproducts->each(function ($product) {

            $product_id = json_decode($product->RelatedProductIds)[0]->productID ?? null;

            if ($product_id) {
                $regularPrice = Size::where('product_id', $product_id)->pluck('RegularPrice')->first();
                $salePrice = Size::where('product_id', $product_id)->pluck('SalePrice')->first();
                $discount = Size::where('product_id', $product_id)->pluck('Discount')->first();

                $product->RegularPrice = $regularPrice;
                $product->SalePrice = $salePrice;
                $product->Discount = $discount;

                // Calculate and attach average rating
                $product->averageRating = round(Review::where('product_id', $product_id)->avg('rating'));
            }
        });


        //        $sizesolds = Size::where('product_id', $productdetails->id)->where('status', 'Active')->get();
        //        $weightolds = Weight::where('product_id', $productdetails->id)->get();


        if ($singlemain) {
            return response()->json([
                'status' => true,
                'message' => 'Product Information',
                'data' => [
                    'main_product' => $singlemain,
                    'productdetails' => $productdetails,
                    'varients' => $varientImgs,
                    'relatedproducts' => $relatedproducts,
                ]

            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Product Information Not Found',
        ], 404);
    }


    public function getVarientProducts(Request $request)
    {
        //        dd($request->all());
        $singlemain = Mainproduct::where('id', $request->mainproduct_id)->select('id', 'category_id', 'RelatedProductIds')->first();

        $productdetails = Product::with([
            'sizes' => function ($query) {
                $query->select('id', 'product_id', 'size_id', 'size', 'Discount', 'RegularPrice', 'SalePrice', 'total_stock', 'available_stock', 'sold')->where('status', 'Active');
            },
            'weights' => function ($query) {
                $query->select('id', 'product_id', 'Discount', 'RegularPrice', 'SalePrice');
            },

            'colors' => function ($query) {
                $query->select('id', 'product_id', 'color_id', 'color', 'Image');
            },
        ])->where('id', $request->product_id)
            ->select('id', 'category_id', 'subcategory_id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductImage', 'ProductBreaf', 'ProductDetails', 'ProductSku', 'PostImage', 'ViewProductImage', 'youtube_embade', 'MetaTitle', 'MetaKey', 'MetaDescription', 'status')
            ->first();

        $productdetails->RegularPrice = $productdetails->sizes[0]->RegularPrice;
        $productdetails->SalePrice = $productdetails->sizes[0]->SalePrice;
        $productdetails->Discount = $productdetails->sizes[0]->Discount;
        $productdetails->total_stock = $productdetails->sizes[0]->total_stock;
        $productdetails->available_stock = $productdetails->sizes[0]->available_stock;
        $productdetails->sold_qty = $productdetails->sizes[0]->sold;

        //        return view('webview.content.product.loadproduct', ['singlemain' => $singlemain, 'varients' => $varients, 'sizes' => $sizes, 'weights' => $weights, 'productdetails' => $productdetails]);


        $images = [

            $productdetails->ViewProductImage,
            $productdetails->ProductImage,

        ];

        // Decode PostImage and add to slider_images
        $postImgArray = json_decode($productdetails->PostImage, true); // Decode as array

        if (is_array($postImgArray)) {
            foreach ($postImgArray as $img) {
                $images[] = 'public/images/product/slider/' . $img;
            }
        }

        // Assign the complete array to the model property
        $productdetails->images = $images;

        if ($singlemain && $productdetails) {

            return response()->json([
                'status' => true,
                'message' => 'Products Variant Information',
                'data' => [
                    'main_product' => $singlemain,
                    'productdetails' => $productdetails,
                ]

            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Product Information Not Found',
        ], 404);
    }



    public function relatedProduct(string $slug)
    {
        $productDetails = Product::where('ProductSlug', $slug)->first();


        $relatedProducts = Product::where('category_id', $productDetails->category_id)->where(
            'status',
            'Active'
        )->inRandomOrder()->limit(15)->get();


        if ($relatedProducts) {
            return response()->json([
                'status' => true,
                'message' => 'Related Products Information',
                'data' => $relatedProducts
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Related Products Information Not Found',
        ], 404);
    }

    public function informationData(string $slug)
    {
        $info = Information::where('key', $slug)->first();


        if ($info) {
            return response()->json([
                'status' => true,
                'message' => 'Information Information',
                'data' => $info
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Information Information Not Found',
        ], 404);
    }

    public function userProfile()
    {
        $id = Auth::user()->id;
        $userprofile = User::findOrfail($id);


        if ($userprofile) {
            return response()->json([
                'status' => true,
                'message' => 'User Profile Information',
                'data' => $userprofile
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'User Profile Information Not Found',
        ], 404);
    }

    public function updateProfile(Request $request)
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

        $save = $userprofile->update();


        if ($save) {
            return response()->json([
                'status' => true,
                'message' => 'Profile update successfully',
                'data' => $userprofile
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Profile update failed',
        ], 404);
    }

    public function userOrderHistory()
    {

        $orders = Order::with(
            [
                'customers' => function ($query) {

                    $query->select('id', 'order_id', 'customerName', 'customerPhone', 'customerAddress');
                },

                'orderproducts' => function ($query) {
                    $query->with('product', function ($query) {
                        $query->select('id', 'ProductImage');
                    })
                        ->select('id', 'product_id', 'order_id', 'productName', 'quantity', 'color', 'size');
                },
                'comments' => function ($query) {
                    $query->select('id', 'order_id', 'comment', 'admin_id', 'status', 'created_at')->where('status', 0);
                },
                'shop' => function ($query) {
                    $query->select('id', 'name', 'phone', 'email', 'profile', 'address');
                },

            ]

        )->where('user_id', auth()->user()->id)
            ->select(
                'id',
                'invoiceID',
                'user_id',
                'customerNote',
                DB::raw('subTotal - deliveryCharge + vat as subTotal'),
                'subTotal as total',
                'deliveryCharge',
                'discountCharge',
                'orderDate',
                'deliveryDate',
                'completeDate',
                'status',
                'packing_by',
                'shipped_by',
                'completed_by',
                'vat',
                'courier_tracking_link',
                'store_id'
            )
            //            ->join('customers', 'customers.order_id', '=', 'orders.id')
            //            ->select('orders.*', 'customers.customerPhone', 'customers.customerName', 'customers.customerAddress')
            ->get();




        //         Transform the data
        $orders->transform(function ($order) {
            $order->orderproducts->transform(function ($orderproduct) {
                // Move ProductImage from 'product' relation to the main object
                if ($orderproduct->product) {
                    $orderproduct->ProductImage = $orderproduct->product->ProductImage;
                }
                // Remove the 'product' relation if no longer needed
                unset($orderproduct->product);
                return $orderproduct;
            });
            return $order;
        });



        if ($orders->count() > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Order History Information',
                'data' => $orders
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Order History Information Not Found',
        ], 404);
    }

    public function guestAddToCart(Request $request)
    {
        $pid = $request->product_id;
        $cartProduct = Product::where('id', $pid)->first();

        if (!$cartProduct) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        if ($request->price > 0) {
            $cart = Cart::updateOrCreate(
                [
                    'ip_address' => $request->ip(),
                    'product_id' => $request->product_id,
                ],

                [
                    'name' => $cartProduct->ProductName,
                    'code' => $cartProduct->ProductSku,
                    'price' => $request->price,
                    'qty' => $request->qty ?? 1,
                    'shop_id' => $cartProduct->shop_id,
                    'weight' => $cartProduct->shop_id,
                    'image' => $cartProduct->ProductImage,
                    'size' => $request->size,
                    'color' => $request->color,
                    'sigment' => $request->sigment,
                ]
            );
        } else {
            $price = Size::where('product_id', $cartProduct->id)->first()->SalePrice;

            Cart::updateOrCreate(
                [
                    'ip_address' => $request->ip(),
                    'product_id' => $request->product_id, // Ensure this matches the database schema
                ],

                [
                    'name' => $cartProduct->ProductName,
                    'code' => $cartProduct->ProductSku,
                    'price' => $price,
                    'qty' => $request->qty ?? 1,  // Increment the quantity if already exists
                    'shop_id' => $cartProduct->shop_id,
                    'weight' => $cartProduct->shop_id,
                    'image' => $cartProduct->ProductImage,
                    'size' => $request->size,
                    'color' => $request->color,
                    'sigment' => $request->sigment,
                ]
            );
        }

        //        return redirect('checkout');

        return response()->json([
            'status' => true,
            'message' => 'Added to Cart Successfully',
        ], 200);
        // return response()->json('success',200);
    }

    public function guestUpdateCart(Request $request)
    {
        $cart = Cart::where('ip_address', $request->ip())
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
        $cart = Cart::where('ip_address', $request->ip())
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
        $remainingItems = Cart::where('ip_address', $request->ip())->count();

        if ($remainingItems === 0) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is now empty',
            ], 200);
        }

        // Fetch updated cart items
        $cartProducts = Cart::where('ip_address', $request->ip())->get();

        return response()->json([
            'status' => true,
            'message' => 'Cart item removed successfully',
            'data' => $cartProducts,
        ], 200);
    }

    public function guestCartContent(Request $request)
    {
        $carts = Cart::where('ip_address', $request->ip())->get();


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


    public function userAddToCart(Request $request)
    {
        $pid = $request->product_id;
        $cartProduct = Product::where('id', $pid)->first();

        $user_id = Auth::user()->id;

        if (!$cartProduct) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $existingProduct = Cart::where('user_id', $user_id)->where('product_id', $request->product_id)
            ->where('qty', $request->qty ?? 1)
            ->where('size', $request->size)
            ->first();

        if ($existingProduct) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Product already exists in cart'
                ],
                404
            );
        }



        if ($request->price > 0) {
            $cart = Cart::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'product_id' => $request->product_id,
                    'size' => $request->size
                ],

                [
                    'name' => $cartProduct->ProductName,
                    'code' => $cartProduct->ProductSku,
                    'price' => $request->price,
                    'qty' => $request->qty ?? 1,
                    'shop_id' => $cartProduct->shop_id,
                    'weight' => $cartProduct->shop_id,
                    'image' => $cartProduct->ProductImage,
                    'size' => $request->size,
                    'color' => $request->color,
                    'sigment' => $request->sigment,
                ]
            );
        } else {
            $price = Size::where('product_id', $cartProduct->id)->first()->SalePrice;

            Cart::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'product_id' => $request->product_id, // Ensure this matches the database schema
                    'size' => $request->size
                ],

                [
                    'name' => $cartProduct->ProductName,
                    'code' => $cartProduct->ProductSku,
                    'price' => $price,
                    'qty' => $request->qty ?? 1,  // Increment the quantity if already exists
                    'shop_id' => $cartProduct->shop_id,
                    'weight' => $cartProduct->shop_id,
                    'image' => $cartProduct->ProductImage,
                    'size' => $request->size,
                    'color' => $request->color,
                    'sigment' => $request->sigment,
                ]
            );
        }
        //      return redirect('checkout');
        return response()->json([
            'status' => true,
            'message' => 'Added to Cart Successfully',
        ], 200);
    }

    public function userUpdateCart(Request $request)
    {
        $user_id = Auth::user()->id;
        $cart = Cart::where('user_id', $user_id)
            ->where('id', $request->id)
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
        $user_id = Auth::user()->id;
        // Find the cart item based on IP address and product ID
        $cart = Cart::where('user_id', $user_id)
            ->where('id', $request->id)
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
        $remainingItems = Cart::where('user_id', $user_id)->count();

        if ($remainingItems === 0) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is now empty',
            ], 200);
        }

        // Fetch updated cart items
        $cartProducts = Cart::where('user_id', $user_id)->get();

        return response()->json([
            'status' => true,
            'message' => 'Cart item removed successfully',
            'data' => $cartProducts,
        ], 200);
    }

    public function userCartContent(Request $request)
    {
        $user_id = Auth::user()->id;
        $carts = Cart::where('user_id', $user_id)->get();

        foreach ($carts as $cart) {

            $size = Size::where('product_id', $cart->product_id)
                ->where('size', $cart->size)->first();

            $shopname = Admin::where('id', $cart->shop_id)->first();
            if ($size) {

                $cart->available_stock = $size->available_stock;
                $cart->shop_name = $shopname->name;
            } else {
                $cart->available_stock = 0;
                $cart->shop_name = $shopname->name;
            }
        }
        if ($carts->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item Empty',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart item found Successfully',
            'data' => $carts
        ], 200);
    }

    public function orderNow(Request $request)
    {
        //        dd($request->all());
        $block = User::where('ip', $request->ip())->where('status', 'Block')->first();
        $user_id = Auth::user()->id;

        if ($block) {
            //       return redirect('ip-block');
            return response()->json([
                'status' => false,
                'message' => 'Your IP blocked',
            ], 404);
        }

        $shopproducts = Cart::where('user_id', $user_id)->get()->groupBy('shop_id');

        //       return $shopproducts;

        if ($shopproducts->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Shopping Cart is Empty',
            ], 404);
        } else {
            $admin = Admin::whereHas('roles', function ($q) {
                $q->where('name', 'user');
            })->where('status', 'Active')->inRandomOrder()->first();
            foreach ($shopproducts as $shopproduct) {
                $order = new Order();
                $exuser = User::where('email', $request->customerPhone)->first();
                if (isset($exuser)) {
                    //                    Auth::login($exuser);
                    $order->user_id = $user_id;
                } else {
                    $user = new User();
                    $user->name = $request->customerName;
                    $user->email = $request->customerPhone;
                    $otp = random_int(100000, 999999);
                    $user->otp = $otp;
                    $otppass = $otp;
                    $user->active_status = 0;
                    $user->ip = $request->ip();
                    $user->password = Hash::make($request->customerPhone);
                    $order->user_id = $user_id;
                    $user->save();

                    //                    Auth::login($user);
                }
                $order->store_id = $shopproduct[0]->weight ?? 1;
                $order->web_id = 'Website';
                $order->invoiceID = uniqid();
                $order->deliveryCharge = $request->deliveryCharge;
                $vat = Basicinfo::first();
                //                $sub = $shopproduct->sum('price');
                $sub = $shopproduct->sum(function ($product) {
                    return $product->price * $product->qty;
                });
                if ($vat->vat_status == 'On') {
                    $vat = intval($sub * ($vat->vat / 100));
                } else {
                    $vat = 0;
                }
                $total = $sub + $request->deliveryCharge + $vat ?? 0;

                //                dd($total);
                $order->vat = $vat;
                $order->orderDate = date('Y-m-d');
                if (isset($request->coupon_code)) {

                    $couponV = Coupon::where('code', $request->coupon_code)->first();


                    $couponuse = new Usecoupon();
                    $couponuse->user_id = Auth::id();
                    $couponuse->coupon_id = Coupon::where('code', $request->coupon_code)->first()->id;
                    $couponuse->code = $request->coupon_code;
                    $couponuse->date = date('Y-m-d');
                    $couponuse->save();

                    $order->coupon_code = $request->coupon_code;

                    if ($couponV->type == 'Amount') {
                        $discount = $couponV->amount;
                    } else {
                        $discount = $total * ($couponV->amount / 100);
                    }

                    $order->discountCharge = $discount;
                    $order->subTotal = $total - $discount;
                } else {
                    $order->subTotal = $total;
                }

                $order->customerNote = $request->customerNote;

                $result = $order->save();
                if ($result) {
                    $customer = new Customer();
                    $customer->order_id = $order->id;
                    $customer->customerName = $request->customerName;
                    $customer->customerPhone = $request->customerPhone;
                    $customer->customerAddress = $request->customerAddress;
                    $customer->save();


                    foreach ($shopproduct as $product) {
                        $orderProducts = new Orderproduct();
                        $orderProducts->order_id = $order->id;
                        $orderProducts->product_id = $product->product_id;
                        $orderProducts->productCode = $product->code;


                        if ($product->color == 'undefined') {
                        } else {
                            $orderProducts->color = $product->color;
                        }

                        if ($product->size == 'undefined') {
                        } else {
                            $orderProducts->size = $product->size;
                        }

                        if ($product->sigment == 'undefined') {
                        } else {
                            $orderProducts->sigment = $product->sigment;
                        }

                        $orderProducts->productName = $product->name;
                        $orderProducts->quantity = $product->qty;
                        $orderProducts->productPrice = $product->price;
                        $orderProducts->save();
                    }

                    $notification = new Comment();
                    $notification->order_id = $order->id;

                    $notification->title = 'Order Successfull';
                    $notification->comment = $order->invoiceID . ' Order Has Been Created';
                    $notification->admin_id = $order->store_id;
                    $notification->user_id = $user_id;
                    $notification->invoice = $order->invoiceID;

                    $notification->save();
                } else {
                    Customer::where('order_id', '=', $order->id)->delete();
                    Orderproduct::where('order_id', '=', $order->id)->delete();
                    Comment::where('order_id', '=', $order->id)->delete();
                    Order::where('id', '=', $order->id)->delete();
                    $response['status'] = 'failed';
                    $response['message'] = 'Unsuccessful to press order';
                }
            }


            Cart::where('user_id', $user_id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Order Submitted Successfully',
            ], 200);
        }
    }

    public function reviewStore(Request $request)
    {

        $pendingReview =   Review::where('user_id', Auth::user()->id)->where('product_id', $request->product_id)->first();

        if ($pendingReview) {
            return response()->json([
                'status' => false,
                'message' => 'You have already submitted a review for this product'
            ], 404);
        }


        $review = new Review();
        $review->user_id = Auth::user()->id;
        $review->product_id = $request->product_id;
        $review->messages = $request->messages;
        $review->rating = $request->rating;

        if ($request->file) {
            $file = $request->file;
            $name = time() . "_" . $file->getClientOriginalName();
            $uploadPath = ('public/images/admin/profile/');
            $file->move($uploadPath, $name);
            $imageUrl = $uploadPath . $name;
            $review->file = $imageUrl;
        }

        $review->save();
        return response()->json(['status' => true, 'message' => 'Review submitted successfully', 'data' => $review], 200);
    }

    public function getReview(Request $request)
    {
        $reviews = Review::where('status', 'Active')->where('product_id', $request->product_id)
            ->with('user', function ($query) {
                $query->select('id', 'name', 'email', 'phone', 'profile', 'ip');
            })
            ->select('product_id', 'user_id', 'messages', 'rating', 'file', 'status', 'created_at', 'updated_at')->get()->reverse();

        if ($reviews->count() > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Review found Successfully',
                'data' => $reviews
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Review not found',
        ], 200);
    }

    public function giveReact(Request $request, $slug)
    {
        if ($slug == 'like') {
            $ex = React::where('user_id', $request->ip())->where('product_id', $request->product_id)->where(
                'sigment',
                'like'
            )->first();
            if (isset($ex)) {
                $ex->delete();
                $data = [
                    'total' => React::where('product_id', $request->product_id)->where(
                        'sigment',
                        'like'
                    )->get()->count(),
                    'product_id' => $request->product_id,
                    'sigment' => 'unlike',
                ];
                return response()->json($data, 200);
            } else {
                $like = new React();
                $like->product_id = $request->product_id;
                $like->user_id = $request->ip();
                $like->sigment = $slug;
                $like->save();
                $data = [
                    'total' => React::where('product_id', $request->product_id)->where(
                        'sigment',
                        'like'
                    )->get()->count(),
                    'product_id' => $request->product_id,
                    'sigment' => 'like',
                ];
                return response()->json($data, 200);
            }
        } else {
            $ex = React::where('user_id', $request->ip())->where('product_id', $request->product_id)->where(
                'sigment',
                'love'
            )->first();
            if (isset($ex)) {
                $ex->delete();
                $data = [
                    'total' => React::where('product_id', $request->product_id)->where(
                        'sigment',
                        'love'
                    )->get()->count(),
                    'product_id' => $request->product_id,
                    'sigment' => 'unlove',
                ];
                return response()->json($data, 200);
            } else {
                $like = new React();
                $like->product_id = $request->product_id;
                $like->user_id = $request->ip();
                $like->sigment = $slug;
                $like->save();
                $data = [
                    'total' => React::where('product_id', $request->product_id)->where(
                        'sigment',
                        'love'
                    )->get()->count(),
                    'product_id' => $request->product_id,
                    'sigment' => 'love',
                ];
                return response()->json($data, 200);
            }
        }
    }

    public function giveLike(Request $request)
    {
        $ex = Like::where('user_id', $request->user_id)->where('product_id', $request->product_id)->where(
            'review_id',
            $request->review_id
        )->first();
        if (isset($ex)) {
            $ex->delete();
            $data = [
                'total' => Like::where('review_id', $request->review_id)->get()->count(),
                'review_id' => $request->review_id,
                'status' => 'unlike',
            ];
            return response()->json($data, 200);
        } else {
            $like = new Like();
            $like->product_id = $request->product_id;
            $like->user_id = $request->user_id;
            $like->review_id = $request->review_id;
            $like->like = 'Yes';
            $like->save();
            $data = [
                'total' => Like::where('review_id', $request->review_id)->get()->count(),
                'review_id' => $request->review_id,
                'status' => 'like',
            ];
            return response()->json($data, 200);
        }
    }

    public function giveshare(Request $request)
    {
        $ex = Share::where('user_id', $request->user_id)->where('product_id', $request->product_id)->where(
            'review_id',
            $request->review_id
        )->first();
        if (isset($ex)) {
            $ex->delete();
            $data = [
                'total' => Share::where('review_id', $request->review_id)->get()->count(),
                'review_id' => $request->review_id,
                'status' => 'unshare',
            ];
            return response()->json($data, 200);
        } else {
            $like = new Share();
            $like->product_id = $request->product_id;
            $like->user_id = $request->user_id;
            $like->review_id = $request->review_id;
            $like->share = 'Yes';
            $like->save();
            $data = [
                'total' => Share::where('review_id', $request->review_id)->get()->count(),
                'review_id' => $request->review_id,
                'status' => 'share',
            ];
            return response()->json($data, 200);
        }
    }

    public function orderTrackingNow(Request $request)
    {
        $orders = Order::with([
            'customers',
            'orderproducts',
            'couriers',
            'cities',
            'zones',
            'admins'
        ])->where('user_id', \auth()->user()->id)
            ->where('invoiceID', $request->invoiceID)
            ->get()->reverse();


        if (count($orders) == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Order Not Found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Order Found successfully',
            'data' => $orders
        ], 200);
        //        return view('webview.content.cart.trackorder', ['orders' => $orders]);
    }


    public function searchProduct($search)
    {
        $products = Mainproduct::where('status', 'Active')
            ->where('ProductName', 'LIKE', "%" . $search . " %")
            ->select(
                'id',
                'ProductName',
                'ProductSlug',
                'ProductImage',
                'status',
                'position',
                'top_rated',
                'RelatedProductIds'
            )
            ->orderBy('position', 'asc')
            ->paginate(12)->withQueryString();

        $products->each(function ($product) {

            $product_id = json_decode($product->RelatedProductIds)[0]->productID ?? null;

            if ($product_id) {
                $regularPrice = Size::where('product_id', $product_id)->pluck('RegularPrice')->first();
                $salePrice = Size::where('product_id', $product_id)->pluck('SalePrice')->first();
                $discount = Size::where('product_id', $product_id)->pluck('Discount')->first();

                $product->RegularPrice = $regularPrice;
                $product->SalePrice = $salePrice;
                $product->Discount = $discount;
                $product->sold_qty = Size::where('product_id', $product_id)->pluck('sold')->first();

                // Calculate and attach average rating
                $product->averageRating = round(Review::where('product_id', $product_id)->avg('rating'));
            }
        });

        if ($products->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Product Found',
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Search Product Found successfully',
            'data' => $products
        ]);
    }

    public function topProducts()
    {
        $products = Mainproduct::where('status', 'Active')->where(
            'top_rated',
            '1'
        )->orderByRaw('ISNULL(`position`), `position` ASC')->select(
            'id',
            'ProductName',
            'ProductSlug',
            'ProductImage',
            'status',
            'position',
            'top_rated',
            'RelatedProductIds'
        )
            ->orderBy('position', 'asc')
            ->paginate(12)->withQueryString();

        $products->each(function ($product) {

            $product_id = json_decode($product->RelatedProductIds)[0]->productID ?? null;

            if ($product_id) {
                $regularPrice = Size::where('product_id', $product_id)->pluck('RegularPrice')->first();
                $salePrice = Size::where('product_id', $product_id)->pluck('SalePrice')->first();
                $discount = Size::where('product_id', $product_id)->pluck('Discount')->first();

                $product->RegularPrice = $regularPrice;
                $product->SalePrice = $salePrice;
                $product->Discount = $discount;
                $product->sold_qty = Size::where('product_id', $product_id)->pluck('sold')->first();

                // Calculate and attach average rating
                $product->averageRating = round(Review::where('product_id', $product_id)->avg('rating'));
            }
        });


        if ($products->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Product Found',
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Top Products Found successfully',
            'data' => $products
        ]);
    }

    public function couponCheck(Request $request)
    {
        $available = Coupon::where('code', $request->coupon_code)->where('validity', '>=', date('Y-m-d'))->first();
        $blance = Cart::where('user_id', Auth::id())->sum('price');
        if (isset($available)) {
            $use = Usecoupon::where('user_id', Auth::id())->where('coupon_id', $available->id)->where(
                'code',
                $request->coupon_code
            )->first();
            if (isset($use)) {
                $response = [
                    'status' => false,
                    'message' => 'Coupon Already Used',
                    'discount' => 0,
                ];
                return response()->json($response, 200);
            } else {
                //                $blance = Cart::subtotalFloat();
                if ($available->type == 'Amount') {
                    $discount = $available->amount;
                } else {
                    $discount = intval($blance * ($available->amount / 100));
                }

                $response = [
                    'status' => true,
                    'message' => 'Coupon Applied Successfully',
                    'discount' => $discount,
                    'coupon_Code' => $request->coupon_code
                ];
                return response()->json($response, 200);
            }
        } else {

            $response = [
                'status' => false,
                'message' => 'Invalid Coupon',
                'discount' => 0,
            ];
            return response()->json($response, 404);
        }
    }


    public function shopPage()
    {
        $shops = Admin::where('status', 'Active')->whereHas('roles', function ($query) {
            $query->where('name', 'Shop');
        })->paginate(12)->withQueryString();


        if ($shops->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Shop Found',
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Shop Found successfully',
            'data' => $shops
        ]);
    }


    public function shopProduct(string $slug)
    {
        $shop = Admin::where('slug', $slug)->first();
        $products = Mainproduct::where('status', 'Active')
            ->where('shop_id', $shop->id)
            ->orderBy('position', 'asc')
            ->paginate(12)->withQueryString();

        $products->each(function ($product) {

            $product_id = json_decode($product->RelatedProductIds)[0]->productID ?? null;

            if ($product_id) {
                $regularPrice = Size::where('product_id', $product_id)->pluck('RegularPrice')->first();
                $salePrice = Size::where('product_id', $product_id)->pluck('SalePrice')->first();
                $discount = Size::where('product_id', $product_id)->pluck('Discount')->first();

                $product->RegularPrice = $regularPrice;
                $product->SalePrice = $salePrice;
                $product->Discount = $discount;
                $product->sold_qty = Size::where('product_id', $product_id)->pluck('sold')->first();

                // Calculate and attach average rating
                $product->averageRating = round(Review::where('product_id', $product_id)->avg('rating'));
            }
        });

        if ($products->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Product Found',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Shop Products Found successfully',
            'data' => $products
        ]);
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


    public function brandProducts(string $slug)
    {
        $brandSingle = Brand::where('slug', $slug)->select('id', 'brand_name', 'slug', 'brand_icon', 'status')->first();

        //        return $brandSingle;
        $mainproducts = $brandSingle->mainproducts()
            ->select('id', 'brand_id', 'ProductName', 'ProductSlug', 'ProductImage', 'status', 'position', 'top_rated', 'RelatedProductIds')
            ->where('status', 'Active')
            ->orderBy('position', 'desc')
            ->paginate(12)->withQueryString();

        $mainproducts->transform(function ($mainproduct) {
            if (!empty($mainproduct->RelatedProductIds)) {
                $relatedProductIds = json_decode($mainproduct->RelatedProductIds);

                if (is_array($relatedProductIds) && isset($relatedProductIds[0]->productID)) {
                    $relatedProductId = $relatedProductIds[0]->productID;

                    $regularPrice = Size::where('product_id', $relatedProductId)->pluck('RegularPrice')->first();
                    $salePrice = Size::where('product_id', $relatedProductId)->pluck('SalePrice')->first();
                    $discount = Size::where('product_id', $relatedProductId)->pluck('Discount')->first();


                    $mainproduct->RegularPrice = $regularPrice;
                    $mainproduct->Discount = $salePrice;
                    $mainproduct->SalePrice =  $discount;
                    $mainproduct->sold_qty = Size::where('product_id', $relatedProductId)->pluck('sold')->first();

                    $mainproduct->averageRating = round(Review::where('product_id', $relatedProductId)->avg('rating'));
                } else {
                    $mainproduct->relatedProduct = null;
                    $mainproduct->averageRating = null;
                }
            } else {
                $mainproduct->relatedProduct = null;
                $mainproduct->averageRating = null;
            }

            return $mainproduct;
        });

        if (count($mainproducts) > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Product Information by Brand Found successfully',
                'data' => $mainproducts
            ], 200);
        }


        return response()->json([
            'status' => false,
            'message' => 'Product Information Not Found',
        ], 404);
    }

    public function storeWishlist(Request $request)
    {
        $user_id = Auth::user()->id;
        $product_id = $request->mainProduct_id;

        $wishlist = Wishlist::where('user_id', $user_id)->where('mainproduct_id', $product_id)->first();
        if (isset($wishlist)) {
            $wishlist->delete();
            return response()->json(['status' => true, 'message' => 'Removed from wishlist'], 200);
        } else {
            $wishlist = new Wishlist();
            $wishlist->user_id = $user_id;
            $wishlist->mainproduct_id = $product_id;
            $wishlist->save();
            return response()->json(['status' => true, 'message' => 'Added to wishlist'], 200);
        }
    }

    public function getWishlist()
    {
        $user_id = Auth::user()->id;
        $wishlist = Wishlist::where('user_id', $user_id)->with('product', function ($query) {
            $query->select(
                'id',
                'ProductName',
                'ProductSlug',
                'ProductImage',
                'status',
                'position',
                'top_rated',
                'RelatedProductIds'
            )
                ->orderBy('position', 'asc');
        })->get();

        $wishlist->each(function ($wishlistItem) {

            $product = $wishlistItem->product;
            $product_id = json_decode($product->RelatedProductIds)[0]->productID ?? null;

            if ($product_id) {
                $regularPrice = Size::where('product_id', $product_id)->pluck('RegularPrice')->first();
                $salePrice = Size::where('product_id', $product_id)->pluck('SalePrice')->first();
                $discount = Size::where('product_id', $product_id)->pluck('Discount')->first();

                $product->RegularPrice = $regularPrice;
                $product->SalePrice = $salePrice;
                $product->Discount = $discount;
                $product->sold_qty = Size::where('product_id', $product_id)->pluck('sold')->first();

                // Calculate and attach average rating
                $product->averageRating = round(Review::where('product_id', $product_id)->avg('rating'));
            }
        });

        if ($wishlist->count() == 0) {
            return response()->json(['status' => false, 'message' => 'Wishlist Empty'], 200);
        }

        return response()->json(['status' => true, 'message' => 'Wishlist Found', 'data' => $wishlist], 200);
    }


    public function removeWishlist(Request  $request)
    {
        $product_id = $request->mainProduct_id;
        $user_id = Auth::user()->id;

        $deleteWish = Wishlist::where('user_id', $user_id)->where('mainproduct_id', $product_id)->delete();

        if ($deleteWish) {
            return response()->json(['status' => true, 'message' => 'Removed from wishlist'], 200);
        }

        return response()->json(['status' => false, 'message' => 'wishlist not found'], 404);
    }


    public function clearWishlist()
    {
        $user_id = Auth::user()->id;
        $count = Wishlist::where('user_id', $user_id)->count();

        if ($count == 0) {
            return response()->json(['status' => false, 'message' => 'wishlist already Cleared'], 200);
        }

        $deleteWish = Wishlist::where('user_id', $user_id)->delete();

        if ($deleteWish) {
            return response()->json(['status' => true, 'message' => 'wishlist cleared'], 200);
        }

        return response()->json(['status' => false, 'message' => 'Failed to clear wishlist'], 500);
    }


    public function allCount()
    {
        $activeCart = Cart::where('user_id', Auth::user()->id)->count();
        $activeWishlist = Wishlist::where('user_id', Auth::user()->id)->count();
        $activeOrder = Order::where('user_id', Auth::user()->id)->count();
        $activeNotification = Comment::where('user_id', Auth::user()->id)->count();

        return response()->json([
            'status' => true,
            'message' => 'All Count',
            'data' => [
                'active_cart' => $activeCart,
                'active_wishlist' => $activeWishlist,
                'active_order' => $activeOrder,
                'active_notification' => $activeNotification
            ]
        ], 200);
    }

    public function userNotification()
    {
        $notifications = Comment::where('user_id', Auth::user()->id)
            ->select('id', 'user_id', 'title', 'comment as description', 'image', 'link', 'url', 'created_at', 'updated_at', 'type', 'invoice')
            ->get();

        if ($notifications->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Notification Empty',
                'data' => []
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification Found',
            'data' => $notifications
        ], 200);
    }
}
