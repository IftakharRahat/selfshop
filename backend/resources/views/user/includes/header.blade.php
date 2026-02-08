<div class="container-fluid" style="background: linear-gradient(to right, rgba(185, 0, 110, 1), rgba(231, 0, 94, 1))">
    <div class="row">
        <div class="container">
            <div class="row" style="font-size: 12px;padding: 6px 0px;">
                <div class="col-12 col-lg-4" style="color: #fff">
                    <img src="{{asset('public/icon/f.png')}}" alt="" style="width: 20px;">&nbsp;Explore Mega offer winter for getting hottest drops
                </div>
                <div class="col-lg-4"></div>
                <div class="text-lg-right text-sm-center col-12 col-lg-4">
                    <a href="" style="color: #fff">
                        <img src="{{asset('public/icon/f3.png')}}" alt="" style="width: 20px;">&nbsp;Track your order
                    </a>&nbsp;&nbsp;&nbsp;
                    <a href="" style="color: #fff">
                        <img src="{{asset('public/icon/f2.png')}}" alt="" style="width: 20px;">&nbsp;Become a dropshipper
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pt-2 container-fluid" style="    background-color: #FDF0F6;    border-radius: 0px 0px 6px 6px;">
    <div class="container pt-2" style="    background-color: #FDF0F6;    border-radius: 0px 0px 6px 6px;">
        <div class="row align-items-center">
            <div class="col-lg-3 d-none d-lg-block">
                <a href="{{ url('/') }}" class="text-decoration-none">
                    <img style="width: 236px;    margin-left: -32px;" src="{{ asset($basicinfo->logo) }}" alt="{{ env('APP_NAME') }}" />
                </a>
            </div>
            <div class="text-left col-lg-5 col-9 d-none d-lg-block" style="padding-right: 0;">
                @if (Request::url() == env('APP_URL').'/login' || Request::url() == env('APP_URL').'/register')
                @else
                <form action="{{url('get-search-content')}}" method="GET">
                    <div class="input-group" style="    background-color: #ffffff !important;border: 1px solid #d6d1d1;border-radius: 30px;padding-left: 18px;">
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="Search for products" style="border: none;height: 40px;">

                        <button type="submit" style="border: none;background: none;">
                            <img src="{{asset('public/icon/s1.png')}}" alt="" style="width: 58px;">
                        </button>

                    </div>
                </form>
                @endif
            </div>

            <div class="text-right col-lg-4 col-3 d-none d-lg-block">

                @if(Auth::id())
                    <a class="nav-cart-box text-dark" href="javascript:void(0);" onclick="openprofile()">
                        @if (isset(Auth::user()->profile))
                            <img src="{{ asset(Auth::user()->profile) }}" alt="" style="width:20px;">&nbsp;Dashboard
                        @else
                            <img src="{{ asset('public/icon/usr.png') }}" alt="" style="width:20px;">&nbsp;Hello, Sign in
                        @endif
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="text-dark">
                        <img src="{{ asset('public/icon/usr.png') }}" alt="" style="width:20px;">&nbsp;Hello, Sign in
                    </a>

                @endif
                    &nbsp;
                    &nbsp;
                    &nbsp;
                    &nbsp;
                <a href="{{url('checkout')}}" class="text-dark" >
                    <img src="{{ asset('public/icon/crt.png') }}" alt="" style="width:20px;">&nbsp;Cart
                </a>
            </div>
        </div>
        <hr class=" d-none d-lg-block" style="margin: 8px;">
        <div class="pt-2 pb-2 row align-items-center" id="hidesm">
            <div class="col-lg-2">
                <a href="javascript:void(0);" onclick="openprofile()" style="padding: 6px 10px !important;border: 1px solid #efefef;border-radius: 6px;background: white;color: black;">
                    <img style="width: 20px; " src="{{ asset('public/icon/menu.png') }}" alt="{{ env('APP_NAME') }}" />&nbsp;All Categories
                </a>
            </div>
            <div class="col-10 col-lg-10">
                <ul id="menu">
                    @forelse ($categories->take(5) as $category)
                        <li><a href="{{ url('/product/category/' . $category->slug) }}">{{ $category->category_name }}</a></li>
                    @empty
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
            <nav class="px-0 navbar navbar-expand-lg navbar-light py-lg-0" style="padding: 4px 0px 4px 0px !important;">
                <a href="{{ url('/') }}" class="text-decoration-none d-block d-lg-none" style="margin-left: -25px;width: 60%;text-align: left;">
                    <img style="width: 190px;max-height:60px;" src="{{ asset($basicinfo->logo) }}" alt="{{ env('APP_NAME') }}" />
                </a>

                <div class="mb-1 d-flex d-lg-none">
                    @if(Auth::id())
                        <a class="nav-cart-box text-dark" href="javascript:void(0);" onclick="openprofile()">
                            @if (isset(Auth::user()->profile))
                                <img src="{{ asset(Auth::user()->profile) }}" alt="" style="width:20px;">
                            @else
                                <img src="{{ asset('public/icon/usr.png') }}" alt="" style="width:20px;">
                            @endif
                        </a>
                    @else
                        <a href="{{ url('/login') }}" class="text-dark">
                            <img src="{{ asset('public/icon/usr.png') }}" alt="" style="width:20px;">
                        </a>

                    @endif
                        &nbsp;
                        &nbsp;
                    <a href="{{url('checkout')}}" class="text-dark" >
                        <img src="{{ asset('public/icon/crt.png') }}" alt="" style="width:20px;">
                    </a>
                </div>
                <input type="text" id="valcheck" value="0" hidden>
                <div id="mySidenav" class="sidenav" style="background: rgba(0, 0, 0, 0.7);padding: 0;">
                    <div id="mySidenavsm" @if (Auth::id()) class="bg-primary" style="width:300px;background:#fff;padding-top: 30px;    position: relative;height:max-content;" @else class="bg-white"  style="height:100%;width:250px;background:#fff;padding-top: 50px;"  @endif>
                        <a href="javascript:void(0)" class="closebtn" @if (Auth::id()) @else style="color: black;width:64%;float:right;" @endif onclick="closeNav()">&times;</a>
                        @if (Auth::id())
                            @forelse ($categories as $category)
                                <li class="nav-item dropdown" style="width: 100%;">
                                    <a href="{{ url('/product/category/' . $category->slug) }}" class="nav-link dropdown-toggle"
                                    id="navbarDropdown{{ $category->id }}" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color: white;width: 100%;float: left;">
                                        {{ $category->category_name }}
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown{{ $category->id }}">
                                        @forelse($category->subcategories as $subcategory)
                                            <a class="dropdown-item" href="{{ url('/product/subcategory/' . $subcategory->slug) }}">
                                                {{ $subcategory->sub_category_name }}
                                            </a>
                                        @empty
                                        <a class="dropdown-item">
                                            No data found
                                        </a>
                                        @endforelse
                                    </div>
                                </li>
                            @empty
                            @endforelse

                        @else
                            <a href="{{ url('/') }}" class="nav-item nav-link"
                                style="color: black">Home</a>
                            <a href="{{ url('/login') }}" class="nav-item nav-link"
                                style="color: black">Login</a>
                            <a href="{{ url('/faq') }}" class="nav-item nav-link"
                                style="color: black">FAQ's</a>
                            <a href="{{ url('/support') }}" class="nav-item nav-link"
                                style="color: black">Support</a>
                            <a href="{{ url('/register') }}" class="nav-item nav-link"
                                style="color: black">Become a Reseller</a>
                                
                                <a href="https://forms.gle/gGtUUh4XwNPCvnPk9" class="nav-item nav-link"
                                style="color: black">Account Deletion request</a>
                        @endif
                    </div>
                </div>

                @if (Auth::id())
                <div id="myPronav" class="sidenav" style="background: rgba(0, 0, 0, 0.7);padding: 0;">
                    <div id="myPronavsm" class="bg-white"  style="width:250px;background:#fff;padding-top: 30px;    position: relative;height:max-content;">
                        <a href="javascript:void(0)" class="closebtn" style="color: white;line-height: .6;padding: 4px;background: red;" onclick="closeprofile()">&times;</a>
                        <div class="d-flex" style="padding: 0px 20px;padding-left:6px;">

                            <div class="sideinfo">
                                <img src="{{ asset($basicinfo->logo) }}" style="width: 100%;margin-top: 4px;padding-left: 7px;">
                                <h4 class="p-0 m-0" style="height: 20px;overflow: hidden;font-size: 16px;margin-left: 10px !important;font-weight: bold;">{{ Auth::guard('web')->user()->name }}</h4>
                                <p class="p-0 m-0" style="margin-left: 10px !important;font-size:12px;">{{ Auth::guard('web')->user()->email }}</p>
                                <p class="p-0 m-0" style="margin-left: 10px !important;font-size:12px;">SHOP : {{ Auth::guard('web')->user()->shop_name }}</p>
                                <p class="p-0 m-0" style="margin-left: 10px !important;font-size:12px;">ID : {{ Auth::guard('web')->user()->my_referral_code }}</p>
                            </div>
                        </div>

                        <div class="card" style="border: none;">
                            <div class="card-header" id="income">
                            Your Income {{Auth::guard('web')->user()->account_balance}} TK
                            </div>
                        </div>
                        <br> 
                        <a href="{{ url('/user/dashboard') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/dashboard') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-dashboard" style="margin-right:10px"></i>Dashboard
                        </a>
                        <a href="{{ url('/user/orders') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/orders') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-list" style="margin-right:10px"></i> My Orders
                        </a>
                        <a href="{{ url('/referral/income') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/referral/income') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-ticket-alt" ></i> My Referral Income
                        </a>
                        <a href="{{ url('/order/income-history') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/referral/income') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-ticket-alt" ></i> Order Income
                        </a>
                        <a href="{{ url('/balance/transfer') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/balance/transfer') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-chart-bar" ></i> Balance Transfer
                        </a>
                        <a href="{{ url('user/withdrews') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/withdrews') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-credit-card" ></i> Withdraw
                        </a>
                        <a href="{{ url('/user/productrequests') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/productrequests') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-th" ></i> Product Request
                        </a>
                        <a href="{{ url('/user/teams') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/teams') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-users" ></i> My Team Members
                        </a>
                        <a href="{{ url('user/free/course') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/free/course') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-file-video" ></i> Free Course
                        </a>
                        <a href="{{ url('/user/frauds') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/frauds') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-snowflake"></i> Fraud Checker
                        </a>
                        <a href="{{ url('/user/supporttikits') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/supporttikits') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-ticket-alt" ></i> Ticket
                        </a>
                        <a href="{{ url('/user/settings') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/settings') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-cog" ></i> Settings
                        </a>
                        <a href="{{ url('/user/profile') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/user/profile') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-user" ></i> Profiles
                        </a>
                        <a href="{{ url('/track-order') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/track-order') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-location-arrow"></i> Track Orders
                        </a>
                        <a href="{{ url('/faq') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/faq') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-question-circle" ></i> FAQ
                        </a>
                        <a href="{{ url('/developers-api') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/developers-api') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-key" ></i> Developers API
                        </a>
                        <a href="{{ url('/logout') }}" class="nav-item nav-link" style="color: black;font-size:16px;font-weight: 500;">
                            <i class="fa fa-power-off" ></i> Logout
                        </a>
                        <a href="{{ url('support') }}" class="nav-item nav-link @if(Request::url() == env('APP_URL').'/support') activebtn @endif" style="color: black;font-size:16px;font-weight: 500;">
                            <img src="{{ asset('public/support.png') }}" alt="" style="width: 100%;padding-right: 15px;padding-bottom: 20px;">
                        </a>

                    </div>
                </div>
                @else
                <div id="myPronav" class="sidenav" style="background: rgba(0, 0, 0, 0.7);padding: 0;">
                </div>
                @endif
            </nav>
            </div>
            <style>
                .owl-nav{
                    display: none;
                }
                .carousel-control-prev {
                    display: none;
                    left: 0;
                }
                .carousel-control-next {
                    display: none;
                    left: 0;
                }
                #marqureetext {
                    display: none;
                }

                #sociallinkmobile {
                    display: none;
                }

                @media only screen and (max-width: 768px) {
                    #marqureetext {
                        display: inline;
                        position: fixed;
                        z-index: 9999;
                        top: 0;
                    }

                    #sociallink {
                        display: none;
                    }

                    #sociallinkmobile {
                        display: inline;
                    }
                }
            </style>
        </div>
        <div class="p-0 pb-2 mt-0 text-left col-lg-6 col-12 d-block d-lg-none" id="" >
            <form action="{{url('get-search-content')}}" method="GET">
                <div class="input-group" style="    background-color: #ffffff !important;border: 1px solid #d6d1d1;border-radius: 30px;padding-left: 18px;">
                    <input type="text" name="search" id="search" class="form-control"
                        placeholder="Search for products" style="border: none;height: 40px;">
                    <button type="submit" style="border: none;background: none;">
                        <img src="{{asset('public/icon/s1.png')}}" alt="" style="width: 58px;">
                    </button>
                </div>
            </form>
        </div>

        {{-- //mobile menu js --}}
        <script>
            function openNav() {
                closeprofile();
                document.getElementById("mySidenav").style.width = "100%";
            }

            function closeNav() {
                document.getElementById("mySidenav").style.width = "0";
            }

            function openprofile() {
                closeNav();
                document.getElementById("myPronav").style.width = "100%";
            }

            function closeprofile() {
                document.getElementById("myPronav").style.width = "0";
            }

            function showser() {
                var s=$('#valcheck').val();
                if(s=='0'){

                    $('#valcheck').val('1');
                    $('#hideser').css('display','inline');

                }else{

                    $('#valcheck').val('0');
                    $('#hideser').css('display','none');

                }
            }
        </script>

        </div>
    </div>
</div>
