<footer id="footer" class="footer color-bg">


    <div class="footer-bottom">
        <div class="container">
            <div class="row" id="d-sm-none">
                <div class="col-12 col-md-3" id="left">
                    <div class="module-heading">
                        <h4 class="module-title">Contact Us</h4>
                    </div>
                    <!-- /.module-heading -->

                    <div class="module-body">
                        <ul class="toggle-footer" style="font-size: 13px;">
                            <li class="media">
                                <small style="color: #beb7b7;">Address:</small>
                                <div class="media-body" style="color: rgba(255, 255, 255, 0.8);">
                                    {{ $basicinfo->address }}
                                </div>
                            </li>

                            <li class="media">
                                <small style="color: #beb7b7;">Phone:</small>
                                <div class="media-body" style="color: rgba(255, 255, 255, 0.8);">
                                    +(88) {{ $basicinfo->phone_one }}<br> +(88) {{ $basicinfo->phone_two }}
                                </div>
                            </li>

                            <li class="media">
                                <small style="color: #beb7b7;">Email:</small>
                                <div class="media-body">
                                    <span><a href="mailto:{{ $basicinfo->email }}">{{ $basicinfo->email }}</a></span>
                                </div>
                            </li>

                        </ul>
                    </div>
                    <!-- /.module-body -->
                </div>
                <!-- /.col -->

                <div class="col-12 col-md-3" id="left">
                    <div class="module-heading">
                        <h4 class="module-title">Informations</h4>
                    </div>
                    <!-- /.module-heading -->

                    <div class="module-body">
                        <ul class='list-unstyled' style="font-size: 13px;">
                            <li class="first"><a title="About Us" href="{{ url('about-us') }}">About us</a>
                            </li>
                            <li><a href="{{ url('venture/contact_us') }}" title="Suppliers">Contact
                                    Us</a></li>
                            <li><a href="{{ url('venture/terms_codition') }}" title="Terms & Conditions">Terms &
                                    Conditions</a></li>
                            <li><a href="{{ url('venture/faq') }}" title="faq">FAQ</a></li>
                            <li class="last"><a title="Investor
                                    Relations"
                                    href="{{ url('venture/investor_relation') }}">Investor
                                    Relations</a></li>
                        </ul>
                    </div>
                    <!-- /.module-body -->
                </div>
                <!-- /.col -->

                <div class="col-12 col-md-3" id="left">
                    <div class="module-heading">
                        <h4 class="module-title">My Account</h4>
                    </div>
                    <!-- /.module-heading -->

                    <div class="module-body">
                        <ul class='list-unstyled' style="font-size: 13px;">
                            @if (Auth::id())
                                <li class="first"><a href="#" title="Dashboard">Dashboard</a></li>
                            @else
                                <li class="first"><a href="#" title="Login">Login</a></li>
                            @endif
                            <li><a href="{{ url('track-order') }}" title="Order History">Order History</a></li>
                            <li><a href="#" title="Blog">Blog</a></li>
                            <li class="last"><a href="{{ url('venture/company') }}" title="Company">Company</a></li>
                        </ul>
                    </div>
                    <!-- /.module-body -->
                </div>
                <!-- /.col -->

                <div class="col-12 col-md-3" id="left">
                    <div class="module-heading">
                        <h4 class="module-title">Why Choose Us</h4>
                    </div>
                    <!-- /.module-heading -->

                    <div class="module-body">
                        <ul class='list-unstyled' style="font-size: 13px;">
                            <li class="first"><a href="{{ url('venture/help_center') }}" title="Help Center">Help
                                    Center</a>
                            </li>
                            <li><a title="Customer
                                    Service"
                                    href="{{ url('venture/customer_service') }}">Customer
                                    Service</a>
                            </li>
                            <li><a href="{{ url('venture/shipping_guide') }}"
                                    title="Shopping
                                    Guide">Shopping
                                    Guide</a></li>

                            <li class="last"><a title="Orders History" href="#">Advanced Search</a></li>
                        </ul>
                    </div>
                    <!-- /.module-body -->
                </div>
            </div>
            <div class="row" id="d-lg-none">

                <div class="col-xs-12 col-sm-12 col-md-3">
                    <div class="module-heading">
                        <p class="text-center module-title">Copyright © 2022 -  Sarajat.com</p>
                    </div>
                    <!-- /.module-heading -->
                    <ul id="footerul" style="font-size: 13px;">
                        <li id="footerli"><a id="footera" href="#home">Sell Products</a></li>
                        <li id="footerli"><a id="footera" href="#home">Earn Money</a></li>
                        <li id="footerli"><a id="footera" href="{{ url('venture/terms_codition') }}">Terms &
                                Conditions</a></li>
                        <li id="footerli"><a id="footera" href="{{ url('venture/about_us') }}">About Us</a></li>
                        <li id="footerli"><a id="footera" href="{{ url('venture/contact_us') }}">Contact Us</a>
                        </li>
                    </ul>
                    <!-- /.module-body -->
                </div>
            </div>
        </div>
    </div>
    <div class="copyright-bar">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 no-padding social d-sm-none" style="text-align: center;">
                    <ul class="link">
                        <li class="fb pull-left">
                            <a target="_blank" rel="nofollow" href="{{ $basicinfo->facebook }}"
                                title="Facebook"></a>
                        </li>
                        <li class="tw pull-left">
                            <a target="_blank" rel="nofollow" href="{{ $basicinfo->twitter }}" title="Twitter"></a>
                        </li>
                        <li class="googleplus pull-left">
                            <a target="_blank" rel="nofollow" href="{{ $basicinfo->google }}"
                                title="GooglePlus"></a>
                        </li>
                        <li class="rss pull-left">
                            <a target="_blank" rel="nofollow" href="{{ $basicinfo->rss }}" title="RSS"></a>
                        </li>
                        <li class="pintrest pull-left">
                            <a target="_blank" rel="nofollow" href="{{ $basicinfo->pinterest }}"
                                title="PInterest"></a>
                        </li>
                        <li class="linkedin pull-left">
                            <a target="_blank" rel="nofollow" href="{{ $basicinfo->linkedin }}"
                                title="Linkedin"></a>
                        </li>
                        <li class="youtube pull-left">
                            <a target="_blank" rel="nofollow" href="{{ $basicinfo->youtube }}" title="Youtube"></a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-12 col-lg-6 no-padding" style="text-align: center;">
                    <div class="clearfix payment-methods">
                        <ul>
                            <li><img src="{{ asset('public/webview/assets') }}/images/payments/1.png" alt="">
                            </li>
                            <li><img src="{{ asset('public/webview/assets') }}/images/payments/2.png" alt="">
                            </li>
                            <li><img src="{{ asset('public/webview/assets') }}/images/payments/3.png" alt="">
                            </li>
                            <li><img src="{{ asset('public/webview/assets') }}/images/payments/4.png" alt="">
                            </li>
                            <li><img src="{{ asset('public/webview/assets') }}/images/payments/5.png" alt="">
                            </li>
                        </ul>
                    </div>
                    <!-- /.payment-methods -->
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- /.footer  bottom nav bar mobile-->
<div class="bottom-navbar d-block d-lg-none">
    <div class="container" style="padding-right: 0px !important;">
        <div class="row">
            <div class="logo-bar-icons col-lg-12 col" style="margin: 0px">
                <ul class="inline-links d-lg-inline-block d-flex justify-content-between">
                    <li class="text-center">
                        <a class="nav-cart-box" href="{{ url('/') }}">
                            <i class="nav-box-icon fas fa-home"></i>
                            <div style="font-size: 14px;">Home</div>
                        </a>
                    </li>
                    <li class="text-center">
                        <a id="" href="" class="nav-cart-box">
                            <i class="nav-box-icon fa-solid fa-bookmark"></i>
                            <span class="nav-box-number">0</span>
                            <div style="font-size: 14px;">Saved</div>
                        </a>
                    </li>
                    <li class="text-center">
                        <a class="nav-cart-box" href="">
                            <i class=" nav-box-icon fas fa-th"></i>
                            <div style="font-size: 14px;">Categories</div>
                        </a>
                    </li>
                    <li class="text-center">
                        <a class="nav-cart-box" href="">
                            <i class=" nav-box-icon fas fa-shopping-bag"></i>
                            <div style="font-size: 14px;">Shop</div>
                        </a>
                    </li>
                    <li class="text-center" onclick="sideMenuOpen()">
                        <a class="nav-cart-box" href="#">
                            <i class="nav-box-icon fas fa-user"></i>
                            <div style="font-size: 14px;">Account</div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
