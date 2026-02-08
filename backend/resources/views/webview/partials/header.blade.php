<header class="header-style-1">

    <!-- ============================================== TOP MENU ============================================== -->
    <div class="top-barhead animate-dropdown" id="d-sm-none">
        <div class="container">
            <div class="header-top-inner">
                <div class="cnt-account">
                    <ul class="list-unstyled">
                        <li><a href="{{ url('track-order') }}"><i class="icon fas fa-shopping-cart"></i>Track Order</a>
                        </li>
                        @if (Auth::id())
                            <li><a href="{{ url('user/dashboard') }}"><i class="icon fas fa-user"></i>Dashboard</a></li>
                        @else
                            <li><a href="{{ url('login') }}"><i class="icon fas fa-user"></i>Login</a></li>
                            <li><a href="{{ url('register') }}"><i class="icon fas fa-user"></i>Registration</a></li>
                        @endif
                    </ul>
                </div>
                <!-- /.cnt-account -->
                <div class="cnt-block">
                    <marquee behavior="" direction="" style="color:#000000">{{ $basicinfo->marquee_text }}
                    </marquee>
                    <!-- /.list-unstyled -->
                </div>

                <!-- /.cnt-cart -->
                <div class="clearfix"></div>
            </div>
            <!-- /.header-top-inner -->
        </div>
        <!-- /.container -->
    </div>
    <!-- /.header-top -->
    <!-- ============================================== TOP MENU : END ============================================== -->
    <div class="col-12">
        <marquee behavior="" direction="" style="color:#000000"> {{ $basicinfo->marquee_text }}</marquee>
    </div>
    <div class="main-header" id="myHeader" style="background: #fff;border-bottom: 1px solid #e9e9e9;">
        <div class="container">
            <div class="row" style="margin: 0">
                <div class="col-9 col-sm-9 col-md-9 col-lg-3 logo-holder ps-0">
                    <!-- ============================================================= LOGO ============================================================= -->
                    <div class="logo">
                        <button type="button" onclick="openNav()" id="menubutton" class="d-lg-none">
                            <div id="nemuicon"></div>
                            <div id="nemuicon"></div>
                            <div id="nemuicon"></div>
                        </button>

                        <a href="{{ url('/') }}" id="logoimage">
                            <img src="{{ asset($basicinfo->logo) }}" alt="" id="logosm" style="    width: 80%;">
                        </a>
                    </div>
                    <!-- /.logo -->
                    <!-- ============================================================= LOGO : END ============================================================= -->
                </div>
                <!-- /.logo-holder -->

                <div class="col-2 col-sm-2 col-md-2 col-lg-6 top-search-holder" id="d-sm-none">
                    <!-- /.contact-row -->
                    <!-- ============================================================= SEARCH AREA ============================================================= -->
                    <div class="search-area" id="d-sm-none">
                        <form>
                            <div class="control-group">

                                <input class="search-field" placeholder="Search here...">

                                <a class="search-button" href="#"></a>

                            </div>
                        </form>
                    </div>
                    <!-- /.search-area -->
                    <!-- ============================================================= SEARCH AREA : END ============================================================= -->
                </div>
                <!-- /.top-search-holder -->

                <div class="p-0 col-3 col-sm-3 col-md-3 col-lg-3 animate-dropdown top-cart-row">
                    <!-- ============================================================= SHOPPING CART DROPDOWN ============================================================= -->


                    <div class=" dropdown-cart" style="padding-left: 14px;">
                        <a href="#" class="dropdown" onclick="checkcart(this)" data-bs-toggle="dropdown"
                            id="smcarticon">
                            <div class="items-cart-inner">
                                <div class="basket" style="padding: 0;padding-top: 7px;display:flex;">
                                    <i class="fa-solid fa-cart-plus" style="color: #8d8989;font-size: 28px;"></i>
                                    <span class="d-none d-lg-block lbl"
                                        style="color: black;font-size: 13px;margin-top:10px">Cart</span>
                                </div>
                                <div class="nav-box-number" id="d-sm-none"><span
                                        class="count">{{ count(Cart::content()) }}</span></div>
                            </div>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="checkcartview">
                            </li>
                        </ul>
                        <!-- /.dropdown-menu-->
                    </div>
                    <!-- /.dropdown-cart -->

                    <div class="d-none d-lg-inline-block" id="d-sm-none" style="float:right;padding-right: 38px;">
                        <div class="nav-wishlist-box" id="wishlist" style="    float: right;">
                            <a href="tel:09613100400" class="nav-box-link">
                                <i class="fa-solid fa-phone" id="bookmarkicon"></i>
                                <span class="nav-box-text" style="    color: black;font-size: 12px;float: right;">Need
                                    any
                                    help?<br>
                                    <span class="text-sm strong-700"
                                        style="font-size: 12px;">{{ $basicinfo->phone_one }}</span>
                                </span>
                            </a>
                        </div>
                    </div>

                    <a type="button" class="search-button d-lg-none" data-bs-toggle="modal"
                        data-bs-target="#searchPopup" style="float: right;font-size: 23px; color: #b9b9b9;"
                        href="#" id="smsericon"> <i class="fas fa-search"
                            style="margin-top: 6px;margin-left: 7px;"></i></a>
                    <!-- ============================================================= SHOPPING CART DROPDOWN : END============================================================= -->
                </div>
                <!-- /.top-cart-row -->
            </div>
            <!-- /.row -->

        </div>
        <!-- /.container -->

    </div>


    <!-- side bar panel start -->
    <div id="mySidepanel" class="sidepanel d-lg-none">
        <div class="side-menu-header ">
            <div class="side-menu-close" onclick="closeNav()">
                <i class="fas fa-close"></i>
            </div>
            <div class="px-3 pb-3 side-login" style="padding-top: 12px;padding-bottom: 15px; padding-left: 10px;">
                <a href=""></a>
                <a style="font-size: 16px" href="#">Categories</a>
            </div>
        </div>
        <ul class="level1-styles collapse show" id="id0">

            @forelse ($categories as $category)
                @if (count($category->subcategories) > 0)
                    <li>
                        <a href="{{ url('products/category/' . $category->slug) }}" class="collapsed"
                            data-bs-toggle="collapse"
                            data-bs-target="#id{{ $category->id }}">{{ $category->category_name }}<i
                                class="fas fa-plus" aria-hidden="true" id="plusicon"></i></a>
                        <ul class="collapse level2-styles" id="id{{ $category->id }}">
                            @foreach ($category->subcategories as $subcategory)
                                <li>
                                    <a
                                        href="{{ url('products/sub/category/' . $subcategory->slug) }}">{{ $subcategory->sub_category_name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li>
                        <a
                            href="{{ url('products/category/' . $category->slug) }}">{{ $category->category_name }}</a>
                    </li>
                @endif
            @empty
            @endforelse
        </ul>
    </div>
    <!-- side bar panel end -->
</header>

<!-- Search Popup Modal -->
<div class="modal fade" id="searchPopup" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 0px !important">
            <div class="modal-body" style="padding: 0px;">
                <div class="modalsearch-area">
                    <div class="control-group d-flex justify-content-between">
                        <input class="mb-0 search-field" id="modalsearchinput" onkeyup="searchproduct()"
                            placeholder="Search here...">
                        <a class="search-button" data-bs-dismiss="modal" href="#"></a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="searchproductlist" style="background: white;margin: 10px;height: auto;overflow: scroll;">

    </div>
</div>

<style>
    .modalsearch-area .search-field {
        border: medium none;
        padding: 10px;
        border-right: none;
        float: left;
    }

    .modalsearch-area .search-button {
        display: inline-block;
        float: left;
        margin-top: -1px;
        padding: 6px 15px 7px;
        text-align: center;
        background-color: #e62e04;
        border: 1px solid #e62e04;
    }

    .modalsearch-area .search-button:after {
        color: #fff;
        content: "\f00d";
        font-family: fontawesome;
        font-size: 24px;
        line-height: 9px;
        vertical-align: middle;
    }
</style>
