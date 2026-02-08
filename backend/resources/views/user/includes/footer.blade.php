<style>
    #footm.text-white{
        font-size: 14px !important;
    }
</style>
<div class="container pt-4">
        <div class="row">
            <div class="mb-3 col-md-12" style="text-align: center;">
                <h4><b>Most Popular Brands</b></h4>
            </div>

            <div class="col-lg-12">
                <div class="owl-carousel category-carousel" id="brandCarousel" >
                    @forelse (App\Models\Brand::where('status','Active')->get() as $brand)
                        <div class="items">
                            <div class="mb-2 cat-item d-flex flex-column border-new" id="categoryItem">

                                    <img class="img-fluid" src="{{ asset($brand->brand_icon) }}" alt=""
                                        style="height: 60px;width: 60px;border-radius: 50%;">
                            </div>
                        </div>

                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <br>
<div class="pt-4 mt-2 text-white container-fluid" id="footm" style="background:#2D2D2D !important">

    <div class="row">
        <div class="col-lg-12">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-12">
                        <a href="" class="text-decoration-none" style="width: 100%;float: left;">
                            <img src="{{ asset($basicinfo->logo) }}" alt="{{ env('APP_NAME') }}"
                                style="margin-left: -36px;width: 70%;margin-bottom: 20px;float: left;border-radius: 6px;" />
                        </a>
                        <div class="mb-3" style="text-align: justify;">

                            <p class="mb-2">
                                SelfShop is a B2B platform created for modern entrepreneurs and dropshippers. Here, you can purchase single wholesale products or buy in bulk—giving you the flexibility to grow your business your way. From trending items to essential goods, we make sourcing and scaling simple.
                            </p>

                            <div class="d-none d-lg-block">
                                <div class="d-flex align-items-center" style="padding-top: 25px;">
                                    <a class="px-2 text-black" href="{{ $basicinfo->facebook }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-facebook-f" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 7px;"></i>
                                    </a>&nbsp;&nbsp;
                                    <a class="px-2 text-black" href="{{ $basicinfo->twitter }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-twitter" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 5px;"></i>
                                    </a>&nbsp;&nbsp;
                                    <a class="px-2 text-black" href="{{ $basicinfo->linkedin }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-instagram" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 5px;"></i>
                                    </a>&nbsp;&nbsp;
                                    <a class="px-2 text-black" href="{{ $basicinfo->linkedin }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-linkedin-in" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 5px;"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-12 d-none">
                        <div id="sociallinkmobile" class="d-block d-lg-none">
                            <hr style="margin: 0">
                            <div class="py-2 row bg-secondary px-xl-5">

                                <div class="text-center col-lg-6 text-lg-right">
                                    <div class="d-inline-flex align-items-center">
                                        <a class="px-2 text-white" href="{{ $basicinfo->facebook }}">
                                            <i class="fab fa-facebook-f" style="color:#fff"></i>
                                        </a>
                                        <a class="px-2 text-white" href="{{ $basicinfo->twitter }}">
                                            <i class="fab fa-twitter" style="color:#000000"></i>
                                        </a>
                                        <a class="px-2 text-white" href="{{ $basicinfo->linkedin }}">
                                            <i class="fab fa-instagram" style="color:#000000"></i>
                                        </a>
                                        <a class="px-2 text-white" href="{{ $basicinfo->linkedin }}">
                                            <i class="fab fa-linkedin-in" style="color:#000000"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 text-left col-lg-8 col-12">
                        <div class="row">
                            <div class="col-md-4 col-6">
                                <h5 class="mb-4 text-white font-weight-bold">INFORMATION</h5>
                                <div class="d-flex flex-column justify-content-start">
                                    <a class="mb-2 text-white" href="{{url('/')}}"><i class="mr-2 fa fa-angle-right"></i>Home</a>
                                    <a class="mb-2 text-white" href="{{url('/about-us')}}"><i class="mr-2 fa fa-angle-right"></i>About Us</a>
                                    <a class="mb-2 text-white" href="{{url('/contact-us')}}"><i class="mr-2 fa fa-angle-right"></i>Contact Us</a>
                                    <a class="mb-2 text-white" href="{{url('/venture/terms_codition')}}"><i class="mr-2 fa fa-angle-right"></i>Terms & Conditions</a>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                    <h5 class="mb-4 text-white font-weight-bold">HELP CENTER</h5>
                                <div class="d-flex flex-column justify-content-start">
                                    <a class="mb-2 text-white" href="{{url('/faq')}}"><i class="mr-2 fa fa-angle-right"></i>FAQ</a>
                                    <a class="mb-2 text-white" href="{{url('/support')}}"><i class="mr-2 fa fa-angle-right"></i>Help & Support</a>
                                    <a class="mb-2 text-white" href="{{url('/venture/return_policy')}}"><i class="mr-2 fa fa-angle-right"></i>Return Policy</a>
                                    <a class="mb-2 text-white" href="{{url('/track-order')}}"><i class="mr-2 fa fa-angle-right"></i>Track Order</a>
                                    <a class="mb-2 text-white" href="{{ url('/vendor-login') }}"><i class="mr-2 fa fa-angle-right"></i>Vendor Login</a>
                                    
                                </div>
                            </div>
                            <div class="col-md-4 col-12">
                                <h5 class="mt-3 mb-2 text-white mt-lg-0 font-weight-bold">FOLLOW US</h5>
                                <div class="d-flex flex-column justify-content-start">
                                    <a class="mb-2 text-white" href="{{ $basicinfo->facebook }}">
                                        <img src="{{ asset('public/pagefb.jpg') }}" alt="" style="width: 100%;border-radius: 8px;">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="py-2 row border-top border-light mx-xl-5" id="mobfot">
        <div class="col-12 col-md-6 px-xl-0">
            <p class="mb-2 text-white">
                © 2024 {{ $basicinfo->title }}
            </p>
        </div>
        <div class="col-12 col-md-6 px-xl-0">
            <p class="mb-2" style="text-align:right;color:#fa0051 !important;font-weight:bold">
                 Design & Developed By || Worker99 Ltd
            </p>
        </div>
    </div>
</div>


<!-- /.footer  bottom nav bar mobile-->
<div class="bottom-navbar b-block d-lg-none">
    <div class="container" style="padding: 6px 0px !important;">
        <div class="row">
            <div class="logo-bar-icons col-lg-12 col" style="margin: 0px">
                <ul class="inline-links d-flex justify-content-between">
                    <li class="text-center">
                        <a class="nav-cart-box d-flex" href="{{url('/')}}">
                            <img src="{{ asset('public/home1.png') }}" alt="" width="30px">
                        </a>
                    </li>

                    <li class="text-center">
                        <a href="javascript:void(0);" onclick="openNav()" class="nav-cart-box">
                            <img src="{{ asset('public/menu2.png') }}" alt="" style="width: 30px;">
                        </a>
                    </li>

                    <li class="text-center">
                        <a href="{{ url('checkout') }}" class="nav-cart-box">
                            <img src="{{ asset('public/shopping-bag1.png') }}" alt="" style="width: 48px;padding: 10px;position: absolute;top: -11px;border-radius: 50%;right: -24px;box-shadow: 0px 2px 4px 1px #bbbbbb;">
                        </a>
                    </li>
                    @if (Request::url() == env('APP_URL').'/user/dashboard')
                        <li class="text-center">
                            <a class="nav-cart-box d-flex" id="viewall" href="{{url('user/dashboard')}}">
                                <img src="{{ asset('public/dashboard.png') }}" alt="" style="width: 30px;">
                            </a>
                        </li>
                    @else
                        <li class="text-center">
                            <a href="{{ url('user/dashboard') }}" class="nav-cart-box">
                                <img src="{{ asset('public/dashboard.png') }}" alt="" style="width: 30px;">
                            </a>
                        </li>
                    @endif
                    <li class="text-center">
                        @if(Auth::id())
                            <a class="nav-cart-box" href="javascript:void(0);" onclick="openprofile()">
                                <img src="{{ asset('public/user1.png') }}" alt="" style="width: 30px;">
                            </a>
                        @else
                            @if (Request::url() == env('APP_URL').'/login')
                                <li class="text-center">
                                    <a class="nav-cart-box d-flex" id="viewall" href="{{url('/login')}}">
                                        <img src="{{ asset('public/user.png') }}" alt="" style="width: 30px;">
                                    </a>
                                </li>
                            @else
                                <a class="nav-cart-box" href="{{url('login')}}">
                                    <img src="{{ asset('public/user.png') }}" alt="" style="width: 30px;">
                                </a>
                            @endif
                        @endif
                    </li>

                </ul>
            </div>
        </div>
    </div>
</div>
