@extends('frontend.master')
    @section('meta')
        <title>{{\App\Models\Basicinfo::first()->title}}</title>
        <meta name="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
        <meta name="keywords" content="{{\App\Models\Basicinfo::first()->meta_keyword}}">
        <meta property="og:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />
        <meta itemprop="name" content="{{\App\Models\Basicinfo::first()->title}}">
        <meta itemprop="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
        <meta itemprop="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
        <meta property="og:url" content="{{url('/')}}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{\App\Models\Basicinfo::first()->title}}">
        <meta property="og:description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
        <meta property="og:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
        <meta property="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />
        <meta property="url" content="{{url('/')}}">
        <meta name="robots" content="index, follow" />
        <meta itemprop="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
        <meta property="twitter:card" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />
        <meta property="twitter:title" content="{{\App\Models\Basicinfo::first()->title}}" />
        <meta property="twitter:url" content="{{url('/')}}">
        <meta name="twitter:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
    @endsection

@section('maincontent')
<style>
#navbarCollapse:hover{
    overflow: auto !important;
}
.dropbtn {
    color: white;
    padding: 16px;
    font-size: 16px;
    border: none;
}
.dropdown {
  position: inherit;
  display: inline-block;
}
.dropdown-content {
  height: 100%;
  border-radius: 6px;
  display: none;
  position: absolute;
  background-color: #f1f1f1;
  min-width: 200px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
  top: 0;
  right: -80%;
  margin-left: 245px;
}
.dropdown-content a {
  color: black;
  padding: 6px 4px;
  font-size: 13px;
  border-bottom: 1px solid #EDF1FF;
  text-decoration: none;
}
.dropdown-content a:hover {color: #e00060;background: rgb(235 245 239);}
.dropdown:hover .dropdown-content {display: block;}
.minidropdown {
  display: inline-block;
}
.minidropdown-item {
  height: 100%;
  border-radius: 6px;
  display: none;
  position: absolute;
  background-color: #f1f1f1;
  min-width: 200px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
  top: 0;
  right: -80%;
}
.minidropdown-item a {
  color: black;
  padding: 6px 4px;
  font-size: 13px;
  border-bottom: 1px solid #EDF1FF;
  text-decoration: none;
  display: block;
}
.minidropdown-item a:hover {color: #e00060;background: rgb(235 245 239);}
.dropdown-content a:hover .minidropdown-item{
    display: block !important;
}

/* New Styles for 4 Products Layout */
.view-all-btn {
    background: linear-gradient(45deg, #E5005F, #ff3366);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.view-all-btn:hover {
    background: linear-gradient(45deg, #ff3366, #E5005F);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(229, 0, 95, 0.3);
    text-decoration: none;
}

.product-item {
    transition: transform 0.3s ease;
    height: 100%;
}

.product-item:hover {
    transform: translateY(-5px);
}

.section-title {
    position: relative;
    padding-bottom: 10px;
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: #E5005F;
}

.product-img {
    height: 200px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.product-img img {
    object-fit: cover;
    height: 100%;
    width: 100%;
    transition: transform 0.5s ease;
}

.product-item:hover .product-img img {
    transform: scale(1.05);
}

#dispos {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #E5005F;
    color: white;
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    z-index: 2;
}
</style>

    <?php
        $rpone = App\Models\Product::where('status', 'Active')
            ->inRandomOrder()->first();
        $rptwo = App\Models\Product::where('status', 'Active')
            ->inRandomOrder()->first();
        ?>

    <!-- Navbar Start -->
    <div class="container pt-2 mt-2 mb-2 pt-lg-4">
        <div class="row">
            <div class="owl-carousel category-carousel" id="categoryCarousel" >
                @forelse ($categories as $category)
                    <div class="items">
                        <div class="mb-2 cat-item d-flex flex-column border-new" id="categoryItem">
                            <a href="{{ url('/product/category/' . $category->slug) }}"
                                class="overflow-hidden text-center cat-img position-relative" style="display: flex;justify-content: center;">
                                <img class="img-fluid" src="{{ asset($category->category_icon) }}" alt=""
                                    style="height: 60px;width: 60px;border-radius: 50%;">
                            </a>
                            <a href="{{ url('/product/category/' . $category->slug) }}">
                                <h5 class="m-0 font-weight-semi-bold" id="categoryItemName">
                                    {{ $category->category_name }}</h5>
                            </a>
                        </div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
        <div class="pt-4 row">
            <div class="mb-3 mb-lg-0 col-lg-8 col-12">
                <div id="header-carousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        @forelse ($sliders as $slider)
                            <div class="carousel-item" id="carouselitem">
                                <a href="{{ $slider->slider_btn_link }}"><img class="img-fluid" style="border-radius: 6px;" src="{{ asset($slider->slider_image) }}" alt="{{ $slider->slider_alt }}"></a>
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
            <div class="col-lg-4 col-12">
                <div class="adsbanner">
                    <img src="{{App\Models\Addbanner::where('id',2)->first()->add_image}}" alt="" style="width: 100%;">
                </div>
            </div>
        </div>
    </div>

    <div class="container pt-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="container p-3" style="border-radius:6px;background-color: rgba(253, 240, 246, 1)">
                    <div class="row" >
                        <div class="mb-3 col-lg-4">
                            <img src="{{asset('public/icon/Seamless.png')}}" alt="" style="width:100%">
                        </div>
                        <div class="mb-3 col-lg-4">
                            <img src="{{asset('public/icon/smes.png')}}" alt="" style="width:100%">
                        </div>
                        <div class="mb-3 col-lg-4">
                            <img src="{{asset('public/icon/Value.png')}}" alt="" style="width:100%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pt-4">
        <div class="row">
            <div class="mb-3 text-center col-md-4 col-12">
                <a href="{{ url('load-product/hot_selling') }}">
                    <div class="p-lg-3 card card-body" style="border-radius:4px;border:1px solid #CFEDFF">
                        <img src="{{asset('public/icon/3.png')}}" alt="" id="smallmenu">
                        <div class="data">
                            <div class="d-flex" style="justify-content: space-between">
                                <h4 style="color:#E5005F;margin-top: 4px;margin-bottom: 0;"><b>HOT SELLING</b></h4>
                                <button class="btn btn-info" style="background: black;border-radius: 6px;padding: 4px 10px;">Explore</button>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-3 text-center col-md-4 col-12">
                <a href="{{ url('load-product/ready_to_bost') }}">
                    <div class="p-lg-3 card card-body" style="border-radius:4px;border:1px solid #CFEDFF">
                        <img src="{{asset('public/icon/Ready for boost.png')}}" alt="" id="smallmenu">
                        <div class="data">
                            <div class="d-flex" style="justify-content: space-between">
                                <h4 style="color:#E5005F;margin-top: 4px;margin-bottom: 0;"><b>READY TO BOOST</b></h4>
                                <button class="btn btn-info" style="background: black;border-radius: 6px;padding: 4px 10px;">Explore</button>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-3 text-center col-md-4 col-12">
                <a href="{{ url('load-product/profitable_product') }}">
                    <div class="p-lg-3 card card-body" style="border-radius:4px;border:1px solid #CFEDFF">
                        <img src="{{asset('public/icon/2.png')}}" alt="" id="smallmenu">
                        <div class="data">
                            <div class="d-flex" style="justify-content: space-between">
                                <h4 style="color:#E5005F;margin-top: 4px;margin-bottom: 0;"><b>LIMITED OFFERS</b></h4>
                                <button class="btn btn-info" style="background: black;border-radius: 6px;padding: 4px 10px;">Explore</button>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-3 text-center col-md-4 col-12">
                <a href="{{ url('load-product/new_arrivel') }}">
                    <div class="p-lg-3 card card-body" style="border-radius:4px;border:1px solid #CFEDFF">
                        <img src="{{asset('public/icon/subel112.png')}}" alt="" id="smallmenu">
                        <div class="data">
                            <div class="d-flex" style="justify-content: space-between">
                                <h4 style="color:#E5005F;margin-top: 4px;margin-bottom: 0;"><b>NEW ARRIVED</b></h4>
                                <button class="btn btn-info" style="background: black;border-radius: 6px;padding: 4px 10px;">Explore</button>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-3 text-center col-md-4 col-12">
                <a href="{{ url('load-product/limited_offer') }}">
                    <div class="p-lg-3 card card-body" style="border-radius:4px;border:1px solid #CFEDFF">
                        <img src="{{asset('public/icon/Profit.png')}}" alt="" id="smallmenu">
                        <div class="data">
                            <div class="d-flex" style="justify-content: space-between">
                                <h4 style="color:#E5005F;margin-top: 4px;margin-bottom: 0;"><b>PROFITABLE</b></h4>
                                <button class="btn btn-info" style="background: black;border-radius: 6px;padding: 4px 10px;">Explore</button>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="mb-3 text-center col-md-4 col-12">
                <a href="{{ url('load-product/summer_collection') }}">
                    <div class="p-lg-3 card card-body" style="border-radius:4px;border:1px solid #CFEDFF">
                        <img src="{{asset('public/icon/Winter.png')}}" alt="" id="smallmenu">
                        <div class="data">
                            <div class="d-flex" style="justify-content: space-between">
                                <h4 style="color:#E5005F;margin-top: 4px;margin-bottom: 0;"><b>WINTER PRODUCT</b></h4>
                                <button class="btn btn-info" style="background: black;border-radius: 6px;padding: 4px 10px;">Explore</button>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- New Arrival Section -->
    <div class="container py-0 pt-4">
        <div class="mb-3" style="display: flex;justify-content: space-between;align-items: center;">
            <h2 class="section-title"><span style="border-bottom: none !important;">NEW ARRIVAL</span></h2>
            <a href="{{ url('load-product/new_arrivel') }}" class="view-all-btn">View All</a>
        </div>
        <div class="row">
            @forelse ($newproducts->take(4) as $justproduct)
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 mb-3">
                <div class="border-0 card product-item" style="box-shadow: none;height: 100%;">
                    <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative">
                        @if (empty($justproduct->ViewProductImage))
                            <a href="{{ url('/product/' . $justproduct->ProductSlug) }}">
                                <img class="img-fluid w-100"
                                    src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                    alt="" id="proimg">
                            </a>
                        @else
                            <a href="{{ url('/product/' . $justproduct->ProductSlug) }}">
                                <img class="img-fluid w-100"
                                    src="{{ asset($justproduct->ViewProductImage) }}"
                                    alt="{{ $justproduct->productName }}" id="proimg">
                            </a>
                        @endif
                        @if($justproduct->Discount>0)
                            <span id="dispos">{{$justproduct->Discount}}% off</span>
                        @endif
                    </div>
                    <div class="p-0 card-body pt-lg-2">
                        <a href="{{ url('/product/' . $justproduct->ProductSlug) }}" class="text-decoration-none">
                            <h6 class="mb-2" style="padding-top:6px;height: 40px;overflow: hidden;font-size: 14px;color: #333;">{{ $justproduct->ProductName }}</h6>
                        </a>
                        @include('frontend.includes.price',['price'=>$justproduct])
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <p class="text-center text-muted">No new products available</p>
            </div>
            @endforelse
        </div>
    </div>
    <!-- End New Arrival Section -->

    <!-- Ads Banner -->
    <div class="container pt-2 pb-2">
        <div class="row px-xl-5">
            <div class="pb-1 col-md-6">
                <?php
                $addbanner2 = App\Models\Addbanner::where('id', 2)
                    ->where('status', 'Active')
                    ->first();
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
                $addbanner3 = App\Models\Addbanner::where('id', 3)
                    ->where('status', 'Active')
                    ->first();
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

    <!-- Featured Products Section -->
    <div class="container py-0 pt-2 pb-2">
        <div class="mb-3" style="display: flex;justify-content: space-between;align-items: center;">
            <h2 class="section-title"><span style="border-bottom: none !important;">FEATURED PRODUCTS</span></h2>
            <a href="{{ url('load-product/featured') }}" class="view-all-btn">View All</a>
        </div>
        <div class="row">
            @forelse ($featuredproducts->take(4) as $featuredproduct)
            <div class="col-lg-3 col-md-3 col-sm-6 col-6 mb-3">
                <div class="border-0 card product-item" style="box-shadow: none;height: 100%;">
                    <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative" >
                        @if (empty($featuredproduct->ViewProductImage))
                            <a href="{{ url('/product/' . $featuredproduct->ProductSlug) }}">
                                <img class="img-fluid w-100"
                                    src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                    alt="" id="proimg">
                            </a>
                        @else
                            <a href="{{ url('/product/' . $featuredproduct->ProductSlug) }}">
                                <img class="img-fluid w-100"
                                    src="{{ asset($featuredproduct->ViewProductImage) }}"
                                    alt="{{ $featuredproduct->productName }}" id="proimg">
                            </a>
                        @endif
                        @if($featuredproduct->Discount>0)
                            <span id="dispos">{{$featuredproduct->Discount}}% off</span>
                        @endif
                    </div>
                    <div class="p-0 card-body pt-lg-2">
                        <a href="{{ url('/product/' . $featuredproduct->ProductSlug) }}" class="text-decoration-none">
                            <h6 class="mb-2" style="padding-top:6px;height: 40px;overflow: hidden;font-size: 14px;color: #333;">{{ $featuredproduct->ProductName }}</h6>
                        </a>
                        @include('frontend.includes.price',['price'=>$featuredproduct])
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <p class="text-center text-muted">No featured products available</p>
            </div>
            @endforelse
        </div>
    </div>
    <!-- End Featured Products Section -->

    <!-- Center Banner -->
    <div class="container pt-1">
        <div class="row px-xl-5">
            <div class="pb-2 col-md-12">
                <?php
                $addbanner9 = App\Models\Addbanner::where('id', 4)
                    ->where('status', 'Active')
                    ->first();
                ?>
                @if (isset($addbanner9->add_image))
                        <div class="p-2 card">
                            <a href="{{ $addbanner9->add_link }}"><img src="{{ asset($addbanner9->add_image) }}"
                                alt="" style="width: 100%; max-height: 200px;"></a>
                        </div>
                @else
                @endif
            </div>
        </div>
    </div>

    <!-- Category Products Section -->
    @forelse ($categoryproducts as $key=>$category)
        @if (count($category->products) > 0)
            <div class="container pt-4">
                <div class="mb-3" style="display: flex;justify-content: space-between;align-items: center;">
                    <h2 class="section-title"><span style="border-bottom: none !important;">{{ $category->category_name }}</span></h2>
                    <a href="{{ url('/product/category/' . $category->slug) }}" class="view-all-btn">View All</a>
                </div>
                <div class="pb-0 row">
                    <div class="p-2 pt-0 pb-2 col-lg-12">
                        <div class="row">
                            @forelse ($category->products->take(4) as $categoryproduct)
                            <div class="col-lg-3 col-md-3 col-sm-6 col-6 mb-3">
                                <div class="border-0 card product-item" style="box-shadow: none;height: 100%;">
                                    <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative" >
                                        @if (empty($categoryproduct->ViewProductImage))
                                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                                <img class="img-fluid w-100"
                                                    src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                                    alt="" id="proimg">
                                            </a>
                                        @else
                                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                                <img class="img-fluid w-100"
                                                    src="{{ asset($categoryproduct->ViewProductImage) }}"
                                                    alt="{{ $categoryproduct->productName }}" id="proimg">
                                            </a>
                                        @endif
                                        @if($categoryproduct->Discount>0)
                                            <span id="dispos">{{$categoryproduct->Discount}}% off</span>
                                        @endif
                                    </div>
                                    <div class="p-0 card-body pt-lg-2">
                                        <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}" class="text-decoration-none">
                                            <h6 class="mb-2" style="padding-top:6px;height: 40px;overflow: hidden;font-size: 14px;color: #333;">{{ $categoryproduct->ProductName }}</h6>
                                        </a>
                                        @include('frontend.includes.price',['price'=>$categoryproduct])
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-12">
                                <p class="text-center text-muted">No products available in this category</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @empty
    <div class="container pt-4">
        <p class="text-center text-muted">No categories available</p>
    </div>
    @endforelse
    <!-- End Category Products Section -->

    <br>
    <div class="container pt-4" id="data-wrapper-footer">
    </div>
    <div class="text-center auto-load" style="display: none;">
        <svg version="1.1" id="L9" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
            x="0px" y="0px" height="60" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
            <path fill="#000"
                d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50">
                <animateTransform attributeName="transform" attributeType="XML" type="rotate" dur="1s"
                    from="0 50 50" to="360 50 50" repeatCount="indefinite" />
            </path>
        </svg>
    </div>

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
                [class*='owl-'] {
                    transition: all .3s ease;
                    &.disabled:hover {
                        background-color: #D6D6D6;
                    }
                }
            }
        }
        @media only screen and (max-width: 768px) {
            .section-title {
                font-size: 20px;
            }
            .view-all-btn {
                padding: 6px 15px;
                font-size: 12px;
            }
            #orderConfirm {
                font-size: 16px !important;
                padding: 8px;
            }
        }
    </style>

    <script>
        var ENDPOINT = "{{ url('datafooter-load-ajax') }}";
        var i=0;
        $(window).scroll(function () {
            if(i==0){
                infinteLoadMore(i);
                i=1;
            }
        });

        function infinteLoadMore(i) {
            $.ajax({
                url: ENDPOINT,
                datatype: "html",
                type: "get",
                beforeSend: function () {
                    $('.auto-load').show();
                }
            })
            .done(function (response) {
                $('.auto-load').hide();
                $("#data-wrapper-footer").append(response);
            })
            .fail(function (jqXHR, ajaxOptions, thrownError) {
                console.log('Server error occured');
            });
        }

        function showmini(id){
            $('#minidropdown-item'+id).css('display','inline');
        }
        function hidemini(id){
            $('#minidropdown-item'+id).css('display','none');
        }
        function modelupQuantity() {
            var qty = $('#proQuantity').val();
            $('#qty').val(qty);
            $('#qtyor').val(qty);
        };

        // Initialize Owl Carousel for category carousel only
        $(document).ready(function() {
            $('.category-carousel').owlCarousel({
                loop: true,
                margin: 10,
                autoplay: true,
                autoplayTimeout: 2000,
                lazyLoad: true,
                autoplayHoverPause: true,
                responsiveClass: true,
                nav: false,
                dots: false,
                responsive: {
                    0: {
                        items: 3,
                    },
                    600: {
                        items: 4,
                    },
                    1000: {
                        items: 8,
                    }
                }
            });
        });
    </script>

    <script type="text/javascript">
        (function() {
            $('.from-prevent-multiple-submits').on('submit', function() {
                $('.from-prevent-multiple-submits').attr('disabled', 'true');
            })
        })();
    </script>
@endsection