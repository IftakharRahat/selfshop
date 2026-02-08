@extends('webview.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-Welcome To Our Buy And Selling Ecommerce.
@endsection
<div class="container">
    <div class="row bg-white">
        <div class="col-lg-2 d-none d-lg-block sidebar pe-0 ps-0">
            <div class="side-menu animate-dropdown outer-bottom-xs">
                <div class="head"><i class="icon fas fa-align-justify fa-fw"></i> Categories</div>
                <nav class="yamm megamenu-horizontal" role="navigation" style="padding-top: 6px;">
                    <ul class="nav m-0">
                        @forelse ($categories as $maincategory)
                            @if (count($maincategory->subcategories) > 0)
                                <li class="dropdown menu-item">
                                    <a href="{{ url('products/category/' . $maincategory->slug) }}"
                                        class="dropdown-toggle" data-bs-hover="dropdown"> <img
                                            src="{{ asset($maincategory->category_icon) }}"
                                            alt="{{ $maincategory->category_name }}"
                                            style="width: 22px !important;margin-top: -5px;">
                                        <span style="margin-left:6px">{{ $maincategory->category_name }}</span></a>
                                    <ul class="dropdown-menu mega-menu">
                                        <li class="yamm-content" style="padding-bottom: 5px;padding-top: 5px;">
                                            <ul class="links list-unstyled">
                                                <div class="row">
                                                    @foreach ($maincategory->subcategories as $subcategory)
                                                        <div class="col-sm-12 col-md-4 pt-1 pb-1" id="subcategoryhover">
                                                            <li><a href="{{ url('products/sub/category/' . $subcategory->slug) }}"
                                                                    style="color:#666666">{{ $subcategory->sub_category_name }}</a>
                                                            </li>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </ul>
                                            <!-- /.row -->
                                        </li>
                                        <!-- /.yamm-content -->
                                    </ul>
                                    <!-- /.dropdown-menu -->
                                </li>
                            @else
                                <li class="dropdown menu-item">
                                    <a href="{{ url('products/category/' . $maincategory->slug) }}"
                                        class="dropdown-toggle text-truncate" data-bs-hover="dropdown"><img
                                            src="{{ asset($maincategory->category_icon) }}"
                                            alt="{{ $maincategory->category_name }}"
                                            style="width: 22px !important;margin-top: -5px;"><span style="margin-left:6px">{{ $maincategory->category_name }}</span></a>
                                    <!-- /.dropdown-menu -->
                                </li>
                            @endif
                        @empty
                        @endforelse
                    </ul>
                </nav>
            </div>
        </div>
        <div class="col-lg-8 col-12 ps-0 pe-0" id="mainslider">
            <div class="col-lg-12 position-static order-2 order-lg-0 d-none d-lg-block" style="background: #f3f3f3;">
                <div id="menu">
                    <ul>
                        @forelse ($menus as $menu)
                            <li><a href="{{ url('menu/' . $menu->slug) }}">{{ $menu->menu_name }}</a></li>
                        @empty
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-12">
                <div class="owl-carousel owl-theme" id="slider">
                    @forelse ($sliders as $slider)
                        <div class="item" style="margin:0 !important;">
                            <img  src="{{ asset($slider->slider_image) }}"
                                alt="{{ $slider->slider_title }}">
                        </div>
                    @empty
                    @endforelse
                </div>

            </div>
        </div>
        <div class="col-lg-2 ps-0 pe-0 d-sm-none">
            <div class="flash-deal-box bg-white h-100">
                <div class="text-center p-2 ctry-bg">
                    <h3 class="heading-6 mb-0">
                        Todays Deal
                        <span class="badge badge-danger">Hot</span>
                    </h3>
                </div>
                <div class="flash-content c-scrollbar" style="background-color: #fff">
                    @forelse ($topproducts as $topproduct)
                        <a href="{{ url('product/' . $topproduct->ProductSlug) }}" class="d-block flash-deal-item">
                            <div class="row no-gutters align-items-center">
                                <div class="col-5">
                                    <img  src="{{ asset($topproduct->ViewProductImage) }}"
                                        style="height:50px">
                                </div>
                                <div class="col-7">
                                    <div class="price">
                                        <span class="d-block">৳ {{ $topproduct->ProductSalePrice }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@if(count($topproducts)>0)
<!-- Promotional Products -->
<div class="container pt-4 pb-4">
    <div class="row bg-white pb-4">
        <div class="col-12" style="border-bottom: 1px solid #e62e04;padding-left: 0;display: flex;justify-content: space-between;">
            <div class="px-2 p-md-3 pt-0 d-flex justify-content-between" style="padding-bottom:4px !important;padding-top: 8px !important;">
                <h4 class="m-0"><b>Promotional Offers</b></h4>
            </div>
            <a href="{{ url('promotional/products') }}" class="btn btn-danger btn-sm mb-0" style="padding: 2px;height: 26px;color: white;font-weight: bold;margin-top:9px;">VIEW ALL</a>
        </div>
        <div class="col-12">
            <div class="owl-carousel " id="promotionalofferSlide">
                @forelse ($topproducts as $promotional)
                    <div class="item" id="featuredproduct">
                        <div class="products best-product">
                            <div class="product">
                                <div class="product-micro">
                                    <div class="row product-micro-row">
                                        <div class="col-12">
                                            <div class="product-image">
                                                <div class="image text-center">
                                                    <a href="{{ url('product/' . $promotional->ProductSlug) }}">
                                                        <img src="{{ asset($promotional->ViewProductImage) }}"
                                                            alt="{{ $promotional->ProductName }}" id="featureimage">
                                                    </a>
                                                </div>
                                                <!-- /.image -->
                                            </div>
                                            <!-- /.product-image -->
                                        </div>
                                        <!-- /.col -->
                                        <div class="col-12">
                                            <div class="infofe p-md-3 p-2" style="padding-bottom: 4px !important;">
                                                <div class="product-info">
                                                    <h2 class="name text-truncate" id="f_name"><a
                                                            href="{{ url('product/' . $promotional->ProductSlug) }}"
                                                            id="f_pro_name">{{ $promotional->ProductName }}s</a></h2>
                                                </div>
                                                <div class="price-box">
                                                    <del
                                                        class="old-product-price strong-400">৳{{ round($promotional->ProductRegularPrice) }}</del>
                                                    <span
                                                        class="product-price strong-600">৳{{ round($promotional->ProductSalePrice) }}</span>
                                                </div>
                                            </div>
                                            <a class="btn btn-danger btn-sm mb-0 btn-block" href="{{ url('product/' . $promotional->ProductSlug) }}"
                                                style="width: 100%;border-radius: 0%;" id="purcheseBtn">Purchese
                                                Now</a>

                                        </div>
                                        <!-- /.col -->
                                    </div>
                                    <!-- /.product-micro-row -->
                                </div>
                                <!-- /.product-micro -->

                            </div>
                        </div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</div>
@else

@endif

<!-- category slider -->
<div class="container pt-4 pb-4">
    <div class="row bg-white pb-4">
        <div class="col-12">
            <div class="px-2 p-md-3 pt-2 d-flex justify-content-between" style="padding-bottom:4px !important">
                <h4 class="m-0">Categories</h4>
                <button class="btn btn-danger mb-0">See More</button>
            </div>
        </div>
        <div class="col-12">
            <div class="owl-carousel best-category" id="categorySlide">
                @forelse ($categorylist as $ctlists)
                    <div class="item">
                        <div class="products best-product">
                            @foreach ($ctlists as $ctlist)
                                <div class="product" id="categoryslider">
                                    <div class="product-micro">
                                        <div class="row product-micro-row">
                                            <div class="col-12">
                                                <div class="product-image">
                                                    <div class="image text-center">
                                                        <a href="{{ url('products/category/' . $ctlist->slug) }}">
                                                            <img data-original="{{ asset($ctlist->category_icon) }}"
                                                                alt="{{ $ctlist->category_name }}"
                                                                id="categoryimage">
                                                        </a>
                                                    </div>
                                                    <!-- /.image -->
                                                </div>
                                                <!-- /.product-image -->
                                            </div>
                                            <!-- /.col -->
                                            <div class="col-12 text-center" style="padding-top: 8px;">
                                                <div class="product-info">
                                                    <h3 class="text-truncate name" id="categoryNameinfo"><a
                                                            href="{{ url('products/category/' . $ctlist->slug) }}"
                                                            id="category_name">{{ $ctlist->category_name }}</a></h3>
                                                </div>
                                            </div>
                                            <!-- /.col -->
                                        </div>
                                        <!-- /.product-micro-row -->
                                    </div>
                                    <!-- /.product-micro -->

                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- add section -->
<div class="container">
    <div class="row gutters-10">
        @if (count($adds) == '2')
            @forelse ($adds as $add)
                <div class="col-lg-6 col-6 ps-0">
                    <div class="media-banner mb-1 mb-lg-0">
                        <a href="{{ $add->add_link }}" target="_blank" class="banner-container">
                            <img src="{{ asset($add->add_image) }}" alt="{{ env('APP_NAME') }}"
                                class="img-fluid ls-is-cached lazyloaded">
                        </a>
                    </div>
                </div>
            @empty
            @endforelse
        @else
            @forelse ($adds as $add)
                <div class="col-lg-12 col-12 ps-0">
                    <div class="media-banner mb-1 mb-lg-0">
                        <a href="{{ $add->add_link }}" target="_blank" class="banner-container">
                            <img src="{{ asset($add->add_image) }}" alt="{{ env('APP_NAME') }}"
                                class="img-fluid ls-is-cached lazyloaded">
                        </a>
                    </div>
                </div>
            @empty
            @endforelse
        @endif
    </div>
</div>






@forelse ($categoryproducts as $key=>$categoryproduct)
    @if (count($categoryproduct->products) > 0)
        <!-- Category Products -->
        <div class="container pt-4 pb-4">
            <div class="row bg-white pb-0">
                <div class="col-12" style="border-bottom: 1px solid #e62e04;padding-left: 0;display: flex;justify-content: space-between;">
                    <div class="px-2 p-md-3 pt-0 d-flex justify-content-between" style="padding-bottom:4px !important;padding-top: 8px !important;">
                        <h4 class="m-0"><b>{{ $categoryproduct->category_name }}</b></h4>
                    </div>
                    <a href="{{url('products/category/'.$categoryproduct->slug)}}" class="btn btn-danger btn-sm mb-0" style="padding: 2px;height: 26px;color: white;font-weight: bold;margin-top:9px;">VIEW ALL</a>
                </div>


                @forelse ($categoryproduct->products->take(12) as $product)
                    <div class="col-6 col-md-4 col-lg-2 mb-4">
                        <div class="product">
                                    <div class="product-micro">
                                        <div class="row product-micro-row">
                                            <div class="col-12">
                                                <div class="product-image">
                                                    <div class="image text-center">
                                                        <a href="{{ url('product/' . $product->ProductSlug) }}">
                                                            <img src="{{ asset($product->ViewProductImage) }}"
                                                                alt="{{ $product->ProductName }}"
                                                                id="featureimage">
                                                        </a>
                                                    </div>
                                                    <!-- /.image -->
                                                </div>
                                                <!-- /.product-image -->
                                            </div>
                                            <!-- /.col -->
                                            <div class="col-12">
                                                <div class="infofe p-md-3 p-2" style="border: 1px solid #e3e1e1;border-top:none;">
                                                    <div class="product-info">
                                                        <h2 class="name text-truncate" id="f_name"><a
                                                                href="{{ url('product/' . $product->ProductSlug) }}"
                                                                id="f_pro_name">{{ $product->ProductName }}</a>
                                                        </h2>
                                                    </div>
                                                    <div class="price-box">
                                                        <del class="old-product-price strong-400">৳
                                                            {{ round($product->ProductRegularPrice) }}</del>
                                                        <span class="product-price strong-600">৳
                                                            {{ round($product->ProductSalePrice) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- /.col -->
                                        </div>
                                        <!-- /.product-micro-row -->
                                    </div>
                                    <!-- /.product-micro -->

                                </div>
                    </div>
                @empty
                @endforelse

                </div>
            </div>
        </div>
    @else
    @endif

@empty
@endforelse

@endsection
