@extends('frontend.master')

@section('meta')
    <title>{{ \App\Models\Basicinfo::first()->title }}</title>
    <meta name="description" content="{{ \App\Models\Basicinfo::first()->meta_description }}">
    <meta name="keywords" content="{{ \App\Models\Basicinfo::first()->meta_keyword }}">

    <meta property="og:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />

    <meta itemprop="name" content="{{ \App\Models\Basicinfo::first()->title }}">
    <meta itemprop="description" content="{{ \App\Models\Basicinfo::first()->meta_description }}">
    <meta itemprop="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">

    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ \App\Models\Basicinfo::first()->title }}">
    <meta property="og:description" content="{{ \App\Models\Basicinfo::first()->meta_description }}">
    <meta property="og:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
    <meta property="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />
    <meta property="url" content="{{ url('/') }}">
    <meta name="robots" content="index, follow" />
    <meta itemprop="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
    <meta property="twitter:card" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />
    <meta property="twitter:title" content="{{ \App\Models\Basicinfo::first()->title }}" />
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta name="twitter:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
@endsection

@section('maincontent')
    <style>
        #navbarCollapse:hover {
            overflow: auto !important;
        }
    </style>
    <?php
    $rpone = App\Models\Product::where('status', 'Active')->inRandomOrder()->first();
    $rptwo = App\Models\Product::where('status', 'Active')->inRandomOrder()->first();
    ?>

    <!-- Navbar Start -->
    <div class="mt-2 mb-2 container-fluid">
        <div class="row border-top px-xl-5">
            <div class="col-lg-3 col-md-3 d-none d-lg-block" style="padding-right: 6px;">
                <a class="text-white shadow-none btn d-flex align-items-center justify-content-between bg-primary w-100"
                    data-toggle="collapse" href="#navbar-vertical"
                    style="height: 44px; margin-top: -1px; padding: 0 20px;border-radius: 6px 6px 0px 0px;">
                    <h6 class="m-0 text-light">Categories</h6>
                    <i class="fa fa-angle-down text-light"></i>
                </a>
                <nav class="p-0 collapse show navbar navbar-vertical navbar-light align-items-start border-top-0 border-bottom-0"
                    id="navbar-vertical">
                    <div class="navbar-nav w-100" id="navbarCollapse" style="height: 368px;overflow: hidden;">
                        @forelse ($categories as $category)
                            <a href="{{ url('/product/category/' . $category->slug) }}" class="nav-item nav-link"> <img
                                    src="{{ asset($category->category_icon) }}" style="width: 20px">
                                {{ $category->category_name }}</a>
                        @empty
                        @endforelse
                    </div>
                </nav>
            </div>
            <div class="col-lg-9 col-12" id="">
                <div id="header-carousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        @forelse ($sliders as $slider)
                            <div class="carousel-item" id="carouselitem">
                                <a href="{{ $slider->slider_btn_link }}"><img class="img-fluid" style="border-radius: 6px;"
                                        src="{{ asset($slider->slider_image) }}" alt="{{ $slider->slider_alt }}"></a>
                            </div>
                        @empty
                        @endforelse
                    </div>
                    <script>
                        $(".carousel-item").first().addClass("active");
                    </script>
                    <a class="carousel-control-prev" href="#header-carousel" data-slide="prev">
                        <div class="btn btn-dark" style="width: 45px; height: 45px;border-radius: 50%;">
                            <span class="carousel-control-prev-icon mb-n2"></span>
                        </div>
                    </a>
                    <a class="carousel-control-next" href="#header-carousel" data-slide="next">
                        <div class="btn btn-dark" style="width: 45px; height: 45px;border-radius: 50%;">
                            <span class="carousel-control-next-icon mb-n2"></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Start -->
    <div class="container-fluid">
        <div class="mt-4 mb-1" style="display: flex;justify-content: space-between;">
            <h2 class="section-title" style="margin:0"><span class="titlelg"
                    style="border-bottom: none !important;background: linear-gradient(90deg, rgba(187, 9, 117, 1) 48%, rgba(136, 0, 255, 1) 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;">POPULAR
                    CATEGORY</span></h2>
            <a href="{{ url('view/category/all') }}" class="btn btn-primary" id="btnlgr">View All</a>
        </div>
        <div class="row px-xl-5" id="catcaro">
            <div class="owl-carousel category-carousel" id="categoryCarousel">
                @forelse ($categorylist as $cats)
                    <div class="vendor-items">
                        @foreach ($cats as $category)
                            <div class="mb-2 cat-item d-flex flex-column border-new" id="categoryItem">
                                <a href="{{ url('/product/category/' . $category->slug) }}"
                                    class="overflow-hidden text-center cat-img position-relative">
                                    <img class="img-fluid" src="{{ asset($category->category_icon) }}" alt=""
                                        style="min-height: 70px;">
                                </a>
                                <a href="{{ url('/product/category/' . $category->slug) }}">
                                    <h5 class="m-0 font-weight-semi-bold" id="categoryItemName">
                                        {{ $category->category_name }}</h5>
                                </a>
                            </div>
                        @endforeach
                    </div>

                @empty
                @endforelse
            </div>
        </div>
    </div>

    <div class="container py-0 pt-4 pb-2">
        <div class="mb-0" style="display: flex;justify-content: space-between;">
            <h2 class="section-title" style=""><span class="titlelg" style="border-bottom: none !important;"> New
                    Arrival</span></h2>
        </div>
        <div class="row px-xl-5">
            <div class="col">
                <div class="owl-carousel vendornew-carousel">
                    @forelse ($newproducts as $justproduct)
                        <div class="vendor-item">
                            <div class="border-0 card product-item">
                                <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative"
                                    id="cardimg">
                                    @if (empty($justproduct->ViewProductImage))
                                        <a href="{{ url('/product/' . $justproduct->ProductSlug) }}"><img
                                                class="img-fluid w-100"
                                                src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                                alt=""></a>
                                    @else
                                        <a href="{{ url('/product/' . $justproduct->ProductSlug) }}"><img
                                                class="img-fluid w-100" src="{{ asset($justproduct->ViewProductImage) }}"
                                                alt="{{ $justproduct->productName }}"></a>
                                    @endif
                                </div>
                                <div class="p-0 card-body pt-lg-2">
                                    <a href="{{ url('/product/' . $justproduct->ProductSlug) }}" class="">
                                        <h6 class="mb-2 text-truncate">{{ $justproduct->ProductName }}</h6>
                                    </a>
                                    <div class="d-flex justify-content-center">
                                        <h6 class="mb-0 mb-lg-2" style="font-weight: bold;">৳ @if (Auth::id())
                                                @if (Auth::user()->status == 'Active')
                                                    {{ intval($justproduct->ProductResellerPrice) }}
                                                @else
                                                    ***
                                                @endif
                                            @else
                                                ***
                                            @endif
                                        </h6>
                                    </div>
                                </div>
                                <div class="border card-footer justify-content-between bg-light" style="padding:0;">
                                    @if (Auth::id())
                                        <form name="form" method="POST" action="{{ url('/add-to-cart') }}"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <input type="text" name="product_id" value=" {{ $justproduct->id }}"
                                                hidden>
                                            <input type="text" name="color" id="product_color" hidden>
                                            <input type="text" name="size" id="product_size" hidden>
                                            <input type="text" name="qty" value="1" id="qty" hidden>
                                            <input type="text" name="price" id="price"
                                                value="{{ $justproduct->min_sell_price }}" hidden>
                                            <button type="submit" class="btn btn-info btn-sm btn-block text-dark"
                                                style="color: white !important;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">অর্ডার
                                                করুন</button>
                                        </form>
                                    @else
                                        <a href="{{ url('/product/' . $justproduct->ProductSlug) }}"
                                            class="btn btn-info btn-sm btn-block text-dark"
                                            style="border-radius: 6px;color: white !important;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">অর্ডার
                                            করুন</a>
                                    @endif
                                </div>
                            </div>
                        </div>

                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Categories End -->

    <div class="container pt-2 pb-2">
        <div class="row px-xl-5">
            <div class="pb-1 col-md-6">
                <?php
                $addbanner2 = App\Models\Addbanner::where('id', 2)->where('status', 'Active')->first();
                ?>
                @if (isset($addbanner2->add_image))
                    <div class="p-2 card">
                        <a href="{{ $addbanner2->add_link }}"><img src="{{ asset($addbanner2->add_image) }}"
                                alt="" style="width: 100%"></a>
                    </div>
                @else
                @endif
            </div>
            <div class="pb-1 col-md-6">
                <?php
                $addbanner3 = App\Models\Addbanner::where('id', 3)->where('status', 'Active')->first();
                ?>
                @if (isset($addbanner3->add_image))
                    <div class="p-2 card">
                        <a href="{{ $addbanner3->add_link }}"><img src="{{ asset($addbanner3->add_image) }}"
                                alt="" style="width: 100%"></a>
                    </div>
                @else
                @endif
            </div>
        </div>
    </div>


    <!-- Just arrive Products Start -->

    <div class="container py-0 pb-2">
        <div class="mb-0" style="display: flex;justify-content: space-between;">
            <h2 class="section-title" style=""><span class="titlelg"
                    style="border-bottom: none !important;">ফিচার্ড প্রোডাক্টস</span></h2>
        </div>
        <div class="row px-xl-5">
            <div class="col">
                <div class="owl-carousel vendor-carousel">
                    @forelse ($featuredproducts as $featuredproduct)
                        <div class="vendor-item">
                            <div class="border-0 card product-item">
                                <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative"
                                    id="cardsimg">
                                    @if (empty($featuredproduct->ViewProductImage))
                                        <a href="{{ url('/product/' . $featuredproduct->ProductSlug) }}"><img
                                                class="img-fluid w-100"
                                                src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                                alt=""></a>
                                    @else
                                        <a href="{{ url('/product/' . $featuredproduct->ProductSlug) }}"><img
                                                class="img-fluid w-100" src="{{ asset($featuredproduct->ViewProductImage) }}"
                                                alt="{{ $featuredproduct->productName }}"></a>
                                    @endif
                                </div>
                                <div class="p-0 card-body pt-lg-2">
                                    <a href="{{ url('/product/' . $featuredproduct->ProductSlug) }}"
                                        class="d-none d-lg-block">
                                        <h6 class="mb-2 text-truncate">{{ $featuredproduct->ProductName }}</h6>
                                    </a>
                                    <div class="d-flex justify-content-center">
                                        <h6 class="mb-0 mb-lg-2" style="font-weight: bold;">৳@if (Auth::id())
                                                @if (Auth::user()->status == 'Active')
                                                    {{ intval($featuredproduct->ProductResellerPrice) }}
                                                @else
                                                    ***
                                                @endif
                                            @else
                                                ***
                                            @endif
                                        </h6>
                                    </div>
                                </div>
                                <div class="border card-footer justify-content-between bg-light d-none d-lg-block"
                                    style="padding:0;">
                                    @if (Auth::id())
                                        <form name="form" method="POST" action="{{ url('/add-to-cart') }}"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <input type="text" name="product_id" value="{{ $justproduct->id }}"
                                                hidden>
                                            <input type="text" name="color" id="product_color" hidden>
                                            <input type="text" name="size" id="product_size" hidden>
                                            <input type="text" name="price" id="price"
                                                value="{{ $featuredproduct->min_sell_price }}" hidden>
                                            <input type="text" name="qty" value="1" id="qty" hidden>
                                            <button type="submit" class="btn btn-info btn-sm btn-block text-dark"
                                                style="color: white !important;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">অর্ডার
                                                করুন</button>
                                        </form>
                                    @else
                                        <a href="{{ url('/product/' . $featuredproduct->ProductSlug) }}"
                                            class="btn btn-info btn-sm btn-block text-dark"
                                            style="border-radius: 6px;color: white !important;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">অর্ডার
                                            করুন</a>
                                    @endif
                                </div>
                            </div>
                        </div>

                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <!-- Products End -->

    <div class="container pt-1">
        <div class="row px-xl-5">
            <div class="pb-2 col-md-12">
                <?php
                $addbanner9 = App\Models\Addbanner::where('id', 4)->where('status', 'Active')->first();
                ?>
                @if (isset($addbanner9->add_image))
                    <div class="p-2 card">
                        <a href="{{ $addbanner9->add_link }}"><img src="{{ asset($addbanner9->add_image) }}"
                                alt="" style="width: 100%;    max-height: 200px;"></a>
                    </div>
                @else
                @endif
            </div>
        </div>
    </div>



    <!-- Category Products Start -->

    @forelse ($categoryproducts as $key=>$category)
        @if (count($category->products) > 0)
            <div class="container pt-3">
                <div class="mb-0" style="display: flex;justify-content: space-between;">
                    <h2 class="section-title" style=""><span class="titlelg"
                            style="border-bottom: none !important;">{{ $category->category_name }}</span></h2>
                    <a href="{{ url('/product/category/' . $category->slug) }}" class="btn btn-primary"
                        id="btnlgr">আরো দেখুন</a>
                </div>

                <div class="pb-0 row px-xl-5">

                    @forelse ($category->products->reverse() as $categoryproduct)
                        <div class="p-2 pt-0 pb-2 col-lg-2 col-6">
                            <div class="card product-item" style="border:1px solid;padding: 4px;">
                                <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative"
                                    id="cardimg">
                                    @if (empty($categoryproduct->ViewProductImage))
                                        <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                                class="img-fluid w-100"
                                                src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                                alt=""></a>
                                    @else
                                        <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                                class="img-fluid w-100"
                                                src="{{ asset($categoryproduct->ViewProductImage) }}"
                                                alt="{{ $categoryproduct->productName }}"></a>
                                    @endif
                                </div>
                                <div class="p-0 pt-2 card-body">
                                    <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                        <h6 class="mb-2 text-truncate">{{ $categoryproduct->ProductName }}</h6>
                                    </a>
                                    <div class="d-flex justify-content-center">
                                        <h6 class="mb-0 mb-lg-2" style="font-weight: bold;">৳ @if (Auth::id())
                                                @if (Auth::user()->status == 'Active')
                                                    {{ intval($categoryproduct->ProductResellerPrice) }}
                                                @else
                                                    ***
                                                @endif
                                            @else
                                                ***
                                            @endif
                                        </h6>
                                    </div>
                                </div>
                                <div class="border card-footer justify-content-between bg-light" style="padding:0;">
                                    @if (Auth::id())
                                        <form name="form" method="POST" action="{{ url('/add-to-cart') }}"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <input type="text" name="product_id" value=" {{ $categoryproduct->id }}"
                                                hidden>
                                            <input type="text" name="color" id="product_color" hidden>
                                            <input type="text" name="size" id="product_size" hidden>
                                            <input type="text" name="qty" value="1" id="qty" hidden>
                                            <input type="text" name="price" id="price"
                                                value="{{ $categoryproduct->min_sell_price }}" hidden>
                                            <button type="submit" class="btn btn-info btn-sm btn-block text-dark"
                                                style="color: white !important;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">অর্ডার
                                                করুন</button>
                                        </form>
                                    @else
                                        <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"
                                            class="btn btn-info btn-sm btn-block text-dark"
                                            style="border-radius: 6px;color: white !important;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">অর্ডার
                                            করুন</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                    @endforelse

                </div>
            </div>
        @else
        @endif
    @empty
    @endforelse
    <!-- Products End -->

    <br>

    <div class="container pt-4">
        <div class="row px-xl-5">
            <div class="pb-4 col-lg-4 col-12">
                <div class="mb-0" style="display: flex;justify-content: space-between;">
                    <h2 class="section-title" style=""><span class=""
                            style="border-bottom: none !important;">বিগ সেলিং</span></h2>
                </div>
                <?php
                $bigsells = App\Models\Product::where('status', 'Active')->inRandomOrder()->limit(5)->get();
                ?>
                @if (isset($bigsells))
                    @foreach ($bigsells as $categoryproduct)
                        <div class="pb-2 d-flex"
                            style="justify-content: space-between;border-bottom: 1px solid #dad9d9;margin-bottom: 6px;">
                            @if (empty($categoryproduct->ViewProductImage))
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img class="img-fluid"
                                        style="width: 60px;height: 60px;"
                                        src="{{ asset('public/frontend/') }}/img/product-1.jpg" alt=""></a>
                            @else
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img class="img-fluid"
                                        style="width: 60px;height: 60px;"
                                        src="{{ asset($categoryproduct->ViewProductImage) }}"
                                        alt="{{ $categoryproduct->productName }}"></a>
                            @endif
                            <div class="productinfo" style="width: 80%;">
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                    <h6 class="mb-2 text-truncate">{{ $categoryproduct->ProductName }}</h6>
                                </a>
                                <div class="d-flex justify-content-left">
                                    <h6>৳ @if (Auth::id())
                                            @if (Auth::user()->status == 'Active')
                                                {{ intval($categoryproduct->ProductResellerPrice) }}
                                            @else
                                                ***
                                            @endif
                                        @else
                                            ***
                                        @endif
                                    </h6>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                @endif
            </div>
            <div class="pb-4 col-lg-4 col-12">
                <div class="mb-0" style="display: flex;justify-content: space-between;">
                    <h2 class="section-title" style=""><span class=""
                            style="border-bottom: none !important;">নিউ প্রোডাক্ট</span></h2>
                </div>

                <?php
                $newarrivals = App\Models\Product::where('status', 'Active')->latest()->take(5)->get();
                ?>
                @if (isset($newarrivals))
                    @foreach ($newarrivals as $categoryproduct)
                        <div class="pb-2 d-flex"
                            style="justify-content: space-between;border-bottom: 1px solid #dad9d9;margin-bottom: 6px;">
                            @if (empty($categoryproduct->ViewProductImage))
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img class="img-fluid"
                                        style="width: 60px;height: 60px;"
                                        src="{{ asset('public/frontend/') }}/img/product-1.jpg" alt=""></a>
                            @else
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img class="img-fluid"
                                        style="width: 60px;height: 60px;"
                                        src="{{ asset($categoryproduct->ViewProductImage) }}"
                                        alt="{{ $categoryproduct->productName }}"></a>
                            @endif
                            <div class="productinfo" style="width: 80%;">
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                    <h6 class="mb-2 text-truncate">{{ $categoryproduct->ProductName }}</h6>
                                </a>
                                <div class="d-flex justify-content-left">
                                    <h6>৳ @if (Auth::id())
                                            @if (Auth::user()->status == 'Active')
                                                {{ intval($categoryproduct->ProductResellerPrice) }}
                                            @else
                                                ***
                                            @endif
                                        @else
                                            ***
                                        @endif
                                    </h6>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                @endif
            </div>
            <div class="pb-4 col-lg-4 col-12">
                <div class="mb-0" style="display: flex;justify-content: space-between;">
                    <h2 class="section-title" style=""><span class=""
                            style="border-bottom: none !important;">টপ রেটেড</span></h2>
                </div>
                <?php
                $toprateds = App\Models\Product::where('status', 'Active')->inRandomOrder()->limit(5)->get();
                ?>
                @if (isset($toprateds))
                    @foreach ($toprateds as $categoryproduct)
                        <div class="pb-2 d-flex"
                            style="justify-content: space-between;border-bottom: 1px solid #dad9d9;margin-bottom: 6px;">
                            @if (empty($categoryproduct->ViewProductImage))
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img class="img-fluid"
                                        style="width: 60px;height: 60px;"
                                        src="{{ asset('public/frontend/') }}/img/product-1.jpg" alt=""></a>
                            @else
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img class="img-fluid"
                                        style="width: 60px;height: 60px;"
                                        src="{{ asset($categoryproduct->ViewProductImage) }}"
                                        alt="{{ $categoryproduct->productName }}"></a>
                            @endif
                            <div class="productinfo" style="width: 80%;">
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                    <h6 class="mb-2 text-truncate">{{ $categoryproduct->ProductName }}</h6>
                                </a>
                                <div class="d-flex justify-content-left">
                                    <h6>৳ @if (Auth::id())
                                            @if (Auth::user()->status == 'Active')
                                                {{ intval($categoryproduct->ProductResellerPrice) }}
                                            @else
                                                ***
                                            @endif
                                        @else
                                            ***
                                        @endif
                                    </h6>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                @endif
            </div>
        </div>
    </div>
    <br>

    {{-- //product details view --}}
    <div class="modal fade" id="poductview">
        <div class="modal-dialog" style="min-width: 85% !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="productNameTitle" style="text-transform: capitalize;"></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                        ×
                    </button>
                </div>

                <div class="p-3 modal-body">
                    <!-- Shop Detail Start -->
                    <div class="container" id="ProductViewDetails">
                        <div class="row px-xl-5">
                            <div class="pb-5 col-lg-6">
                                <div id="previmage">

                                </div>
                                <div id="prevsmallimage">

                                </div>
                            </div>

                            <div class="pb-5 col-lg-6">
                                <h3 class="font-weight-semi-bold" style="text-transform: capitalize;" id="productName">
                                </h3>
                                <div class="mb-3 d-flex">
                                    <span class="pt-1">Product Code : &nbsp;</span><strong class="pt-1"
                                        id="productCode"></strong>
                                </div>
                                <h3 class="mb-4 font-weight-semi-bold">৳ <span id="productPrice"></span> &nbsp;
                                    <small><del>৳ <span id="delproductPrice"></span></del></small>
                                </h3>

                                <div class="pt-2 mb-4 d-flex align-items-center">
                                    <div class="mr-3 input-group quantity" style="width: 130px;">

                                        <input type="number" name="quantity" style="background: lightgray;"
                                            class="text-center form-control input-number" id="proQuantity"
                                            placeholder="1" value="1" min="1" max="10"
                                            onchange="modelupQuantity()">

                                    </div>
                                </div>

                                <div class="pt-2 mb-4 align-items-center" id="productbutton">

                                </div>

                                <div
                                    style="background: #dc3545;border: 2px dashed #bdbdbd;padding: 20px;margin-top:10px;text-align: center;">
                                    <h6>Have question about this product ? please call:</h6>
                                    <h2 style="color: white; margin-bottom:0px;font-size: 22px;"><i
                                            class="fa fa-phone-alt" aria-hidden="true"></i> (+88)
                                        {{ $basicinfo->phone_one }}</h2>
                                </div>

                            </div>
                        </div>
                        <div class="row px-xl-5">
                            <div class="col">
                                <div class="mb-4 nav nav-tabs justify-content-center border-secondary">
                                    <a class="nav-item nav-link active" data-toggle="tab"
                                        href="#tab-pane-1">Description</a>
                                </div>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="tab-pane-1">
                                        <h4 class="mb-3">Product Description</h4>
                                        <div id="productDescription"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm waves-effect" data-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- order now --}}
    <div class="modal fade" id="ordernowmodal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="pt-1 pb-1 modal-header">
                    <h4 class="modal-title" id="productTopTitle" style="text-transform: capitalize;font-size: 18px;">
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"> × </button>
                </div>

                <div class="pb-3 modal-body">
                    <!-- Shop Detail Start -->
                    <div class="container p-0" id="ProductOrderInfo">
                        <div class="row">

                            <div class="p-0 pb-2 col-12">
                                <article class="pt-0 card-body">

                                    <form action="{{ url('/buy-now') }}" method="POST"
                                        class="from-prevent-multiple-submits">
                                        @csrf
                                        <div class="row">
                                            <div class="pb-2 form-group col-sm-12">
                                                <label>Your Name </label>
                                                <input type="text" id="userName" name="userName"
                                                    placeholder="আপনার নাম লিখুন" required class="form-control"
                                                    style=" background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%; cursor: auto;">
                                            </div>
                                            <input type="text" id="productId" name="productId" hidden>
                                            <input type="text" id="subTotal" name="subTotal" hidden>
                                            <div class="pb-2 form-group col-sm-12">
                                                <label>Full Address</label>
                                                <input type="text" id="userAddress" name="userAddress"
                                                    placeholder="আপনার ঠিকানা লিখুন" required class="form-control"
                                                    style=" background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%; cursor: auto;">
                                            </div>
                                            <div class="pb-2 form-group col-sm-12">
                                                <label>Your Phone </label>
                                                <input type="tel" pattern="[0-1]{2}[0-9]{6}[0-9]{3}" minlength="10"
                                                    id="userPhone" name="userPhone" required class="form-control"
                                                    placeholder="আপনার মোবাইল লিখুন">
                                            </div>
                                            <div class="pb-2 form-group col-sm-12">
                                                <label>Select Area </label>
                                                <select id="selectCourier" name="selectCourier" class="form-control"
                                                    onchange="setdeliverychargr()">
                                                    <option value="60">ঢাকার ভিতর (60 ৳) </option>
                                                    <option value="100">ঢাকার বাহির (100 ৳) </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="pt-4 row">
                                            <div class="col-8">
                                                <button type="submit" id="orderConfirm"
                                                    class="btn btn-success btn-styled from-prevent-multiple-submits btn-base-1 btn-block btn-icon-left strong-500 hov-bounce hov-shaddow buy-now">
                                                    Confirm Order </button>
                                            </div>
                                            <div class="col-4">
                                                <button type="button" class="btn btn-danger waves-effect"
                                                    data-dismiss="modal" id="closebtn"> Close </button>
                                            </div>
                                        </div>
                                    </form>
                                </article> <!-- card-body.// -->
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        #orderConfirm {
            font-size: 18px !important;
            padding: 10px;
            border-radius: 5px;
        }

        #closebtn {
            font-size: 18px;
            padding: 10px;
            float: right;
            border-radius: 5px;
        }

        #poductview {
            padding-left: 4px;
            padding-right: 20px !important;
        }

        #ordernowmodal {
            padding-left: 4px;
            padding-right: 20px !important;
        }

        #sync1 {
            .item {
                background: #0c83e7;
                padding: 80px 0px;
                margin: 5px;
                color: #FFF;
                -webkit-border-radius: 3px;
                -moz-border-radius: 3px;
                border-radius: 3px;
                text-align: center;
            }
        }

        #sync2 {
            .item {
                background: #C9C9C9;
                padding: 10px 0px;
                margin: 5px;
                color: #FFF;
                -webkit-border-radius: 3px;
                -moz-border-radius: 3px;
                border-radius: 3px;
                text-align: center;
                cursor: pointer;

                h1 {
                    font-size: 18px;
                }
            }

            .current .item {
                background: #0c83e7;
            }
        }



        .owl-theme {
            .owl-nav {

                /*default owl-theme theme reset .disabled:hover links */
                [class*='owl-'] {
                    transition: all .3s ease;

                    &.disabled:hover {
                        background-color: #D6D6D6;
                    }
                }

            }
        }



        @media only screen and (max-width: 768px) {
            #orderConfirm {
                font-size: 18px !important;
                padding: 10px;
            }
        }
    </style>

    <script>
        $(document).on('click', '#orderNow', function() {
            var productId = $(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'productview/' + productId,

                success: function(data) {
                    $('#productTopTitle').text(data.ProductName);
                    $('#ProductOrderInfo').find('#productName').text(data.ProductName);
                    $('#ProductOrderInfo').find('#productId').val(data.id);
                    $('#ProductOrderInfo').find('#subTotal').val(data.ProductSalePrice);
                    $('#poductview').modal('hide');
                    $('#ordernowmodal').modal('show');
                    $('.modal').css('overflow', 'auto');


                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        $(document).ready(function() {
            var idval = $('#CountSlider').val();
            for (let i = 0; i < idval; i++) {

                $('#CategoryProductSlide' + [i]).owlCarousel({
                    loop: true,
                    margin: 10,
                    autoplay: true,
                    autoplayTimeout: 1000,
                    lazyLoad: true,
                    autoplayHoverPause: true,
                    responsiveClass: true,
                    nav: false,
                    dots: false,
                    responsive: {
                        0: {
                            items: 2,
                        },
                        600: {
                            items: 2,
                        },
                        1000: {
                            items: 5,
                        }
                    }
                });
            }
        });

        function modelupQuantity() {
            var qty = $('#proQuantity').val();
            $('#qty').val(qty);
            $('#qtyor').val(qty);
        };
    </script>

    <script type="text/javascript">
        (function() {
            $('.from-prevent-multiple-submits').on('submit', function() {
                $('.from-prevent-multiple-submits').attr('disabled', 'true');
            })
        })();
    </script>
@endsection
