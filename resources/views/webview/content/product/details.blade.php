@extends('webview.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-{{ $productdetails->ProductName }}
@endsection

<!-- Body -->

<div class="body-content mt-4" id="top-banner-and-menu">
    <div class='container'>
        <div class='row single-product'>
            <div class='col-md-12 p-0'>
                <div class="detail-block">
                    <div class="row  wow fadeInUp">

                        <div class="col-xs-12 col-sm-12 col-md-5 gallery-holder">
                            <div class="product-item-holder size-big single-product-gallery small-gallery">

                                <div class="owl-carousel owl-theme" id="owl-single-product">
                                    @if(isset($productdetails->PostImage))
                                        @forelse (json_decode($productdetails->PostImage) as $key =>$slider)
                                            <div class="single-product-gallery-item" id="slide{{ $key + 1 }}">
                                                <a data-lightbox="image-1" data-title="Gallery">
                                                    <img class="img-responsive" alt="{{ $productdetails->ProductName }}"
                                                        data-original="{{ url('public/images/product/slider/' . $slider) }}"
                                                        style="height: 350px;" />
                                                </a>
                                            </div>
                                        @empty
                                            <div class="single-product-gallery-item" id="slide1">
                                                <a data-lightbox="image-1" data-title="Gallery">
                                                    <img class="img-responsive" alt="{{ $productdetails->ProductName }}"
                                                        data-original="{{ url($productdetails->ProductImage) }}"
                                                        style="height: 350px;" />
                                                </a>
                                            </div>
                                        @endforelse
                                    @else
                                         <div class="single-product-gallery-item" id="slide1">
                                            <a data-lightbox="image-1" data-title="Gallery">
                                                <img class="img-responsive" alt="{{ $productdetails->ProductName }}"
                                                    data-original="{{ url($productdetails->ProductImage) }}"
                                                    style="height: 350px;" />
                                            </a>
                                        </div>
                                    @endif

                                </div>

                            </div>
                            <!-- /.single-product-gallery -->
                        </div>
                        <!-- /.gallery-holder -->
                        <div class="col-sm-12 col-md-4 product-info-block" id="paddingnone">
                            <div class="product-info">
                                <h1 class="name"
                                    style="margin-bottom:7px;padding-bottom: 6px;border-bottom: 1px solid #dfd6d6;font-size: 15px !important; line-height: 22px;">
                                    {{ $productdetails->ProductName }}</h1>

                                
                                <!-- /.rating-reviews -->

                                <div class="stock-container info-container m-t-10"
                                    style="margin-top:10px;border-bottom: 1px solid #dfd6d6;">
                                    <div class="row" style="margin-bottom:10px;">
                                        <div class="col-2 col-sm-2">
                                            <div class="product-description-label" id="productPricetitle">Price:</div>
                                        </div>
                                        <div class="col-9 col-sm-9">
                                            <div class="product-price strong-700" id="productPriceAmount">
                                                ৳{{ $productdetails->ProductSalePrice }}
                                                <span class="piece" style="color: black;font-size: 16px;">/1Pcs</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.row -->
                                </div>
                                <!-- /.stock-container -->
                                <div class="quantity-container info-container"
                                    style="border-bottom: 1px solid #dfd6d6;">
                                    <div class="row">

                                        <div class="col-3 col-sm-3">
                                            <span class="label bg-none">Quantity :</span>
                                        </div>

                                        <div class="col-3 col-sm-3">
                                            <div class="cart-quantity">
                                                <div class="quant-input">

                                                    <input type="number" value="1">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-6 col-sm-6">
                                            <div class="avialable-amount" style="margin-top: 8px;font-size: 13px;">
                                                (<span id="available-quantity">422</span> available)</div>
                                        </div>


                                    </div>
                                    <!-- /.row -->
                                </div>
                                <!-- /.stock-container -->
                                <div class="quantity-container info-container text-center"
                                    style="width: 100%;border-bottom: 1px solid #dfd6d6; float: left;">

                                    <form id="AddToCartForm" name="form" method="POST" enctype="multipart/form-data"
                                        style="width: 50%;float: left;">
                                        @method('POST')
                                        @csrf
                                        <input type="text" name="product_id" value="{{ $productdetails->id }}"
                                            hidden>
                                        <input type="text" name="color" id="product_color" hidden>
                                        <input type="text" name="size" id="product_size" hidden>
                                        <input type="text" name="qty" value="1" id="qty" hidden>
                                        <button type="submit"
                                            class="mb-0 btn btn-styled btn-alt-base-1 c-white btn-icon-left strong-700 hov-bounce hov-shaddow add-to-cart">
                                            <span class=" d-md-inline-block"> Add to cart</span>
                                        </button>
                                    </form>

                                    <form name="form" id="OrderNow" method="POST" enctype="multipart/form-data"
                                        style="width: 50%;float: left;">
                                        @method('POST')
                                        @csrf
                                        <input type="text" name="color" id="product_colorold" hidden>
                                        <input type="text" name="size" id="product_sizeold" hidden>
                                        <input type="text" name="product_id" value=" {{ $productdetails->id }}"
                                            hidden>
                                        <input type="text" name="qty" value="1" id="qtyor" hidden>
                                        <button type="submit"
                                            class=" mb-0  ml-2 btn btn-styled btn-base-1 btn-icon-left strong-700 hov-bounce hov-shaddow buy-now">
                                            Order Now
                                        </button>
                                    </form>

                                    <!-- /.row -->
                                </div>
                                
                                <div class="quantity-container info-container text-center" style="border-bottom: 1px solid #dfd6d6;">
                                    <div class="row no-gutters pt-2">
                                        <div class="col-12 col-sm-12" style="margin-top: -2px;">
                                            <div class="product-description-label mt-2" style="text-align: left;font-weight: bold;">বিস্তারিত জানতে কল করুন:</div>
                                        </div>
                                        <div class="col-12 col-sm-12">
                                            <div class="product-description-label" style="font-size: 13px;text-align: center;color: gray;">
                                                <a href="tel:{{ \App\Models\Basicinfo::first()->phone_one }}" style="    padding-right: 10px;" class="nav-box-link"><i class="fa-solid fa-phone" id="bookmarkicon" style="color:#f00"></i>
                                                    <span class="nav-box-text" style="    color: black;font-size: 12px;">
                                                        <span class="strong-700 text-sm" style="font-size: 30px;padding-left:15px;color:#f00;font-weight: bold;">{{ \App\Models\Basicinfo::first()->phone_one }}</span>
                                                    </span>
                                                </a>
                                                <br>
                                                <a href="tel:{{ \App\Models\Basicinfo::first()->phone_two }}" style="    padding-right: 10px;" class="nav-box-link"> <i class="fa-solid fa-phone" id="bookmarkicon" style="color:#f00"></i>
                                                    <span class="nav-box-text" style="    color: black;font-size: 12px;">
                                                        <span class="strong-700 text-sm" style="font-size: 30px;padding-left:15px;color:#f00;font-weight: bold;">{{ \App\Models\Basicinfo::first()->phone_two }}</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="quantity-container info-container text-center"
                                    style="border-bottom: 1px solid #dfd6d6;">
                                    <div class="row no-gutters pt-2">
                                        <div class="col-2 col-sm-2" style="margin-top: -2px;">
                                            <div class="product-description-label mt-2">Charge:</div>
                                        </div>
                                        <div class="col-10 col-sm-10">
                                            <div class="product-description-label"
                                                style="font-size: 13px;text-align: left;color: gray;">
                                                <i class="fas fa-dot-circle " style="padding-right: 4px;"></i>ঢাকা
                                                সিটির মধ্যে ডেলিভারি চার্জ
                                                {{ $numto->bnNum($shipping->inside_dhaka_charge) }}
                                                টাকা<br>
                                                <i class="fas fa-dot-circle" style="padding-right: 4px;"></i>ঢাকা
                                                সিটির বাইরে ডেলিভারি চার্জ
                                                {{ $numto->bnNum($shipping->outside_dhaka_charge) }} টাকা
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="quantity-container info-container text-center" style="padding-top: 0;">
                                    <div class="row no-gutters mt-2">
                                        <div class="col-2 col-sm-2" style="margin-top: 4px;">
                                            <div class="product-description-label mt-2">Share:</div>
                                        </div>
                                        <div class="col-10 col-sm-10">
                                            <div id="share" class="jssocials">
                                                <div class="jssocials-shares">

                                                    @forelse ($shareButtons as $key=>$shareButton)
                                                        <div
                                                            class="jssocials-share jssocials-share-{{ $key }}">
                                                            <a target="_self" href="{{ $shareButton }}"
                                                                class="jssocials-share-link"><i
                                                                    class="fa-brands fa-{{ $key }} jssocials-share-logo"
                                                                    style="color: white;"></i></a>
                                                        </div>
                                                    @empty
                                                    @endforelse

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <!-- /.product-info -->
                        </div>
                        <div class='p-0 col-sm-12 col-md-3 product-info-block d-sm-none' style="padding: 0;">
                            <div class="row no-gutters mt-2 ">
                                <div class="col-sm-1">
                                    <i class="fas fa-phone" aria-hidden="true"
                                        style="font-size: 18px;color: #8a8686;"></i>
                                </div>
                                <div class="col-sm-5 pe-0">
                                    <div class="product-description-label" id="textsize">
                                        Contact Us:</div>
                                </div>
                                <div class="col-sm-5" id="textsize">
                                    <a href="tel:{{ $shipping->contact }}" target="_blank" id="textsize">
                                        {{ $shipping->contact }}
                                    </a>
                                </div>
                            </div>
                            <div class="row no-gutters mt-2">
                                <div class="col-sm-1">
                                    <i class="fas fa-motorcycle" aria-hidden="true"
                                        style="font-size: 16px;col-smor: #8a8686;"></i>
                                </div>
                                <div class="col-sm-5 pe-0">
                                    <div class="product-description-label" id="textsize">

                                        Inside Dhaka:</div>
                                </div>
                                <div class="col-sm-5" id="textsize">
                                    {{ $shipping->insie_dhaka }}
                                </div>
                            </div>
                            <div class="row no-gutters mt-2">
                                <div class="col-sm-1">
                                    <i class="fas fa-truck" aria-hidden="true"
                                        style="font-size: 18px;col-smor: #8a8686;"></i>
                                </div>
                                <div class="col-sm-5 pe-0">
                                    <div class="product-description-label" id="textsize">

                                        Outside Dhaka:</div>
                                </div>
                                <div class="col-sm-5" id="textsize">
                                    {{ $shipping->outside_dhaka }}

                                </div>
                            </div>
                            <div class="row no-gutters mt-2">
                                <div class="col-sm-1">
                                    <i class="fas fa-money-bill-alt" aria-hidden="true"
                                        style="font-size: 18px;col-smor: #8a8686;"></i>
                                </div>

                                <div class="col-sm-5 pe-0">
                                    <div class="product-description-label" id="textsize"> Cash on Delivery :</div>
                                </div>
                                <div class="col-sm-5" id="textsize">
                                    @if ($shipping->cash_on_delivery == 'ON')
                                        Available
                                    @else
                                        Unavailable
                                    @endif

                                </div>

                            </div>

                            <div class="row no-gutters mt-2">
                                <div class="col-sm-1">
                                    <i class="fas fa-refresh" aria-hidden="true"
                                        style="font-size: 18px;col-smor: #8a8686;"></i>
                                </div>
                                <div class="col-sm-5 pe-0">
                                    <div class="product-description-label" id="textsize">Refund Rules:</div>
                                </div>
                                <div class="col-sm-5" id="textsize">
                                    {{ $shipping->refund_rule }}<a href="/returnpolicy" class="ml-2"
                                        target="_blank">View Policy</a>
                                </div>
                            </div>

                            <div class="row no-gutters mt-2">
                                <div class="col-sm-2" id="textsize">
                                    <div class="product-description-label pt-2">Payment:</div>
                                </div>
                                <div class="col-sm-10">
                                    <ul class="inline-links" style="list-style: none;
    margin: 0;">
                                        <li style="width: 90%;">
                                            <img data-original="{{ asset('public/webview/assets/images/Payment-Methods.gif') }}"
                                                width="98%" class="">
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="row no-gutters mt-2">
                                <div class="box-title"
                                    style="padding: 5px;font-size: 18px;background: #ddd;width: 100%;margin-bottom: 5px;">
                                    Recently viewed products
                                </div>


                                <div class="mb-3 product-box-3">
                                    <div class="clearfix">
                                        <div class="product-image float-left">
                                            <a href="{{ url('product/' . $hotproducts[0]->ProductSlug) }}">
                                                <img class="img-fit "
                                                    data-original="{{ asset($hotproducts[0]->ProductImage) }}"
                                                    alt="{{ $hotproducts[0]->ProductName }}">
                                            </a>
                                        </div>
                                        <div class="product-details float-left">
                                            <h4 class="title text-truncate">
                                                <a href="{{ url('product/' . $hotproducts[0]->ProductSlug) }}"
                                                    class="d-block">{{ $hotproducts[0]->ProductName }}</a>
                                            </h4>
                                            <div class="star-rating star-rating-sm mt-1">
                                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                                    class="fas fa-star"></i><i class="fas fa-star"></i><i
                                                    class="fas fa-star"></i>
                                            </div>
                                            <div class="price-box">
                                                <!--  -->
                                                <span
                                                    class="product-price strong-600">৳{{ round($hotproducts[0]->ProductSalePrice) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.col-sm-7 -->
                    </div>
                    <!-- /.row -->
                </div>
            </div>
            <!-- /.col -->
            <div class="clearfix"></div>
        </div>
        <div class="row single-product">
            <div class="col-md-3 ps-0" style="    margin-top: 30px;">
                <div class="seller-info-box mb-3">
                    <div class="sold-by position-relative">
                        <div class="title">Sold By</div>
                        Sarajat.com

                        <div class="rating text-center d-block">
                            <span class="star-rating star-rating-sm d-block">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                    class="fas fa-star"></i><i class="fas fa-star"></i>
                            </span>
                            <span class="rating-count d-block ml-0">(0 customer reviews)</span>
                        </div>
                    </div>
                    <div class="row no-gutters align-items-center">
                    </div>
                </div>
                <br>
                <div class="flash-deal-box bg-white h-100 d-sm-none">
                    <div class="title text-center p-2 ctry-bg mb-0">
                        <h3 class="heading-6 mb-0">
                            Top Selling Products This Shop
                            <span class="badge badge-danger">Hot</span>
                        </h3>
                    </div>
                    <div class="flash-content c-scrollbar"
                        style="background-color: #fff;height: 476px;max-height: 500px;">
                        @forelse ($hotproducts as $hotproduct)
                            <a href="{{ url('product/' . $hotproduct->ProductSlug) }}"
                                class="d-block flash-deal-item">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-md-4 p-0">
                                        <div class="img">
                                            <img class="img-fit "
                                                data-original="{{ asset($hotproduct->ProductImage) }}"
                                                alt="Five Spring Chest Puller"
                                                style="height: 74px;padding-left: 12px;">
                                        </div>
                                    </div>
                                    <div class="col-md-8 p-0">

                                        <div class="price p-0" style="width: 87%;padding-top: 4px !important;color: black;">
                                            <span class="title text-truncate">{{ $hotproduct->ProductName }}</span>
                                            <span class=""
                                                style="display: inline-flex;">৳{{ round($hotproduct->ProductRegularPrice) }}
                                                &nbsp;<del class="d-block">
                                                    ৳{{ round($hotproduct->ProductSalePrice) }}</del></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-9 p-0">
                <div class="product-tabs inner-bottom-xs  wow fadeInUp">
                    <div class="row">
                        <div class="col-sm-12">
                            <ul id="product-tabs" class="nav nav-tabs nav-tab-cell" style="display: inline-flex;">
                                <li class="active"><a data-bs-toggle="tab" id="istteb"
                                        href="#description">DESCRIPTION</a></li>
                                <li><a data-bs-toggle="tab" href="#review">REVIEW</a></li>
                                <li class="d-lg-none"><a data-bs-toggle="tab" href="#shipping-info">SHIPPING INFO</a>
                                </li>
                            </ul>
                            <!-- /.nav-tabs #product-tabs -->
                        </div>
                        <div class="col-sm-12">

                            <div class="tab-content">

                                <div id="description" class="tab-pane active">
                                    <div class="product-tab">
                                        <p class="text">{!! $productdetails->ProductDetails !!}</p>
                                    </div>
                                </div>
                                <!-- /.tab-pane -->

                                <div id="review" class="tab-pane">
                                    <div class="product-tab">

                                        <div class="product-reviews">

                                            <div class="row">
                                                <div class="review">

                                                </div>

                                            </div>
                                            <!-- /.reviews -->
                                        </div>

                                    </div>
                                    <!-- /.product-tab -->
                                </div>
                                <!-- /.tab-pane -->

                                <div id="shipping-info" class="tab-pane">
                                    <div class="product-tag">

                                        <div class="row">
                                            <div class='p-0 col-sm-12 col-md-3 product-info-block d-lg-none'
                                                style="padding: 0;">
                                                <div class="row no-gutters mt-2 ">
                                                    <div class="col-1 col-sm-1">
                                                        <i class="fas fa-phone" aria-hidden="true"
                                                            style="font-size: 18px;color: #8a8686;"></i>
                                                    </div>
                                                    <div class="col-5 col-sm-5">
                                                        <div class="product-description-label" id="textsize">
                                                            Contact Us:</div>
                                                    </div>
                                                    <div class="col-5 col-sm-5" id="textsize">
                                                        <a href="tel:" target="_blank" id="textsize">
                                                            {{ $shipping->contact }}
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="row no-gutters mt-2">
                                                    <div class="col-1 col-sm-1">
                                                        <i class="fas fa-motorcycle" aria-hidden="true"
                                                            style="font-size: 16px;col-smor: #8a8686;"></i>
                                                    </div>
                                                    <div class="col-5 col-sm-5 pe-0">
                                                        <div class="product-description-label" id="textsize">

                                                            Inside Dhaka:</div>
                                                    </div>
                                                    <div class="col-5 col-sm-5" id="textsize">
                                                        {{ $shipping->insie_dhaka }}
                                                    </div>
                                                </div>
                                                <div class="row no-gutters mt-2">
                                                    <div class="col-1 col-sm-1">
                                                        <i class="fas fa-truck" aria-hidden="true"
                                                            style="font-size: 18px;col-smor: #8a8686;"></i>
                                                    </div>
                                                    <div class="col-5 col-sm-5">
                                                        <div class="product-description-label" id="textsize">

                                                            Outside Dhaka:</div>
                                                    </div>
                                                    <div class="col-5 col-sm-5" id="textsize">
                                                        {{ $shipping->outside_dhaka }}

                                                    </div>
                                                </div>
                                                <div class="row no-gutters mt-2">
                                                    <div class="col-1 col-sm-1">
                                                        <i class="fas fa-money-bill-alt" aria-hidden="true"
                                                            style="font-size: 18px;col-smor: #8a8686;"></i>
                                                    </div>

                                                    <div class="col-5 col-sm-5">
                                                        <div class="product-description-label" id="textsize"> Cash on
                                                            Delivery :</div>
                                                    </div>
                                                    <div class="col-5 col-sm-5" id="textsize">
                                                        @if ($shipping->cash_on_delivery == 'ON')
                                                            Available
                                                        @else
                                                            Unavailable
                                                        @endif
                                                    </div>

                                                </div>
                                                <div class="row no-gutters mt-2">
                                                    <div class="col-1 col-sm-1">
                                                        <i class="fas fa-refresh" aria-hidden="true"
                                                            style="font-size: 18px;col-smor: #8a8686;"></i>
                                                    </div>
                                                    <div class="col-5 col-sm-5">
                                                        <div class="product-description-label" id="textsize">Refund
                                                            Rules:</div>
                                                    </div>
                                                    <div class="col-5 col-sm-5" id="textsize">
                                                        {{ $shipping->refund_rule }}<a
                                                            href="#" class="ml-2"
                                                            target="_blank">View Policy</a>
                                                    </div>
                                                </div>
                                                <div class="row no-gutters mt-2">
                                                    <div class="col-2 col-sm-2" id="textsize">
                                                        <div class="product-description-label pt-2"
                                                            style="padding-top: 14px;">Payment:</div>
                                                    </div>
                                                    <div class="col-10 col-sm-10">
                                                        <ul class="inline-links">
                                                            <li>
                                                                <img data-original="{{ asset('public/webview/assets/images/Payment-Methods.gif') }}"
                                                                    width="98%" class=" ">
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <!-- /.product-tab -->
                                </div>
                                <!-- /.tab-pane -->

                            </div>
                            <!-- /.tab-content -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.product-tabs -->

                <!-- ============================================== UPSELL PRODUCTS ============================================== -->
                <section class="pb-2 section featured-product wow fadeInUp" style="margin-bottom:0px !important">
                    <h3 class="section-title" style="border-bottom: 1px solid #e62e04;    padding: 8px;margin-bottom: 0;">Related
                        products</h3>
                    <div class="owl-carousel related-owl-carousel featured-carousel owl-theme outer-top-xs"
                        id="relatedCarousel">
                        @forelse ($relatedproducts as $relatedproduct)
                            <div class="item item-carousel">
                                <div class="products">

                                    <div class="product" id="featuredproduct">
                                        <div class="row product-micro-row">
                                        <div class="col-12">
                                            <div class="product-image">
                                                <div class="image text-center">
                                                    <a href="{{ url('product/' . $relatedproduct->ProductSlug) }}">
                                                        <img data-original="{{ asset($relatedproduct->ViewProductImage) }}"
                                                            alt="{{ $relatedproduct->ProductName }}" id="featureimage">
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
                                                            href="{{ url('product/' . $relatedproduct->ProductSlug) }}"
                                                            id="f_pro_name">{{ $relatedproduct->ProductName }}s</a></h2>
                                                </div>
                                                <div class="price-box">
                                                    <del
                                                        class="old-product-price strong-400">৳{{ round($relatedproduct->ProductRegularPrice) }}</del>
                                                    <span
                                                        class="product-price strong-600">৳{{ round($relatedproduct->ProductSalePrice) }}</span>
                                                </div>
                                            </div> 

                                        </div>
                                        <!-- /.col -->
                                    </div>
                                        <!-- /.cart -->
                                    </div>
                                    <!-- /.product -->

                                </div>
                                <!-- /.products -->
                            </div>
                        @empty
                        @endforelse
                    </div>
                    <!-- /.home-owl-carousel -->
                </section>
                <!-- ============================================== UPSELL PRODUCTS : END ============================================== -->

            </div>
        </div>
        <!-- ============================================== BRANDS CAROUSEL : END ============================================== -->
    </div>
    <!-- /.container -->
</div>
<!-- /.body-content -->

<div class="container mt-4">

    <div class="row">
        <div class="col-sm-12 p-0">
            <section class="pb-2 section featured-product wow fadeInUp"> 
                <div class="col-12" style="border-bottom: 1px solid #e62e04;padding-left: 0;display: flex;justify-content: space-between;">
                    <div class="px-2 p-md-3 pt-0 d-flex justify-content-between" style="padding-bottom:4px !important;padding-top: 8px !important;">
                        <h4 class="m-0"><b>Promotional Offers</b></h4>
                    </div>
                    <a href="{{ url('promotional/products') }}" class="btn btn-danger btn-sm mb-0" style="padding: 2px;height: 26px;color: white;font-weight: bold;margin-top:9px;">VIEW ALL</a>
                </div>
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
                                                            <img data-original="{{ asset($promotional->ProductImage) }}"
                                                                alt="{{ $promotional->ProductName }}"
                                                                id="featureimage">
                                                        </a>
                                                    </div>
                                                    <!-- /.image -->
                                                </div>
                                                <!-- /.product-image -->
                                            </div>
                                            <!-- /.col -->
                                            <div class="col-12">
                                                <div class="infofe p-md-3 p-2"
                                                    style="padding-bottom: 4px !important;">
                                                    <div class="product-info">
                                                        <h2 class="name text-truncate" id="f_name"><a
                                                                href="{{ url('product/' . $promotional->ProductSlug) }}"
                                                                id="f_pro_name">{{ $promotional->ProductName }}s</a>
                                                        </h2>
                                                    </div>
                                                    <div class="price-box">
                                                        <del
                                                            class="old-product-price strong-400">৳{{ round($promotional->ProductRegularPrice) }}</del>
                                                        <span
                                                            class="product-price strong-600">৳{{ round($promotional->ProductSalePrice) }}</span>
                                                    </div>
                                                </div>
                                                <button class="btn btn-danger btn-sm mb-0 btn-block"
                                                    style="width: 100%;border-radius: 0%;"
                                                    onclick="buynow({{ $promotional->id }})" id="purcheseBtn">Purchese
                                                    Now</button>

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
                <!-- /.home-owl-carousel -->
            </section>
            <div class="best-deal wow fadeInUp outer-bottom-xs">
                <div class="col-12" style="border-bottom: 1px solid #e62e04;padding-left: 0;display: flex;justify-content: space-between;">
                    <div class="px-2 p-md-3 pt-0 d-flex justify-content-between" style="padding-bottom:4px !important;padding-top: 8px !important;">
                        <h4 class="m-0"><b>BEST SELLING</b></h4>
                    </div>
                    <a href="{{ url('best/products') }}" class="btn btn-danger btn-sm mb-0" style="padding: 2px;height: 26px;color: white;font-weight: bold;margin-top:9px;">See More</a>
                </div>
                <div class="sidebar-widget-body outer-top-xs">
                    <div class="owl-carousel best-category" id="bestsellingproductSlide">
                        @forelse ($bestproducts as $bestproductlist)
                            <div class="item">
                                <div class="products best-product">
                                    @forelse ($bestproductlist as $bestproduct)
                                        <div class="product" id="categoryslider">
                                            <div class="product-micro">
                                                <div class="row product-micro-row">
                                                    <div class="col-4" style="padding-right:0;">
                                                        <div class="product-image">
                                                            <div class="image text-center">
                                                                <a
                                                                    href="{{ url('product/' . $bestproduct->ProductSlug) }}">
                                                                    <img data-original="{{ asset($bestproduct->ProductImage) }}"
                                                                        alt="{{ $bestproduct->ProductName }}"
                                                                        id="bestsellingimage">
                                                                </a>
                                                            </div>
                                                            <!-- /.image -->
                                                        </div>
                                                        <!-- /.product-image -->
                                                    </div>
                                                    <!-- /.col -->
                                                    <div class="col-8 text-center" style="padding-top: 8px;">
                                                        <div class="infofe">
                                                            <div class="product-info">
                                                                <h2 class="name text-truncate" id="f_name"><a
                                                                        href="#"
                                                                        id="f_pro_name">{{ $bestproduct->ProductName }}</a>
                                                                </h2>
                                                            </div>
                                                            <div class="price-box">
                                                                <del class="old-product-price strong-400">৳
                                                                    {{ round($bestproduct->ProductRegularPrice) }}</del>
                                                                <span class="product-price strong-600">৳
                                                                    {{ round($bestproduct->ProductSalePrice) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- /.col -->
                                                </div>
                                                <!-- /.product-micro-row -->
                                            </div>
                                            <!-- /.product-micro -->

                                        </div>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
                <!-- /.sidebar-widget-body -->
            </div>
        </div>
    </div>

</div>
<!-- Body end -->


{{-- modal for process and cart --}}




{{-- csrf --}}
<input type="hidden" name="_token" value="{{ csrf_token() }}" />

<script>
    $(document).ready(function() {

        $('#AddToCartForm').submit(function(e) {
            e.preventDefault();
            $('#processing').css({
                'display': 'flex',
                'justify-content': 'center',
                'align-items': 'center'
            })
            $('#processing').modal('show');
            $.ajax({
                type: 'POST',
                url: '{{ url('add-to-cart') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    updatecart();
                    $.ajax({
                        type: 'GET',
                        url: '{{ url('get-cart-content') }}',

                        success: function(response) {
                            $('#cartViewModal .modal-body').empty().append(
                                response);
                        },
                        error: function(error) {
                            console.log('error');
                        }
                    });
                    $('#processing').modal('hide');
                    $('#cartViewModal').modal('show');
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        $('#OrderNow').submit(function(e) {
            e.preventDefault();
            $('#processing').css({
                'display': 'flex',
                'justify-content': 'center',
                'align-items': 'center'
            })
            $('#processing').modal('show');
            $.ajax({
                type: 'POST',
                url: '{{ url('add-to-cart') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    updatecart();
                    if (data == 'success') {
                        window.location.href = 'https://sarajat.com/checkout';
                        $('#processing').modal('hide');
                    }
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });


        document.getElementById("istteb").click();
        $('#owl-single-product').owlCarousel({
            items: 1,
            itemsTablet: [768, 1],
            itemsDesktop: [1199, 1],
            autoplay: true,
            autoplayTimeout: 1000,
            autoplayHoverPause: true,
            responsiveClass: true,
            dots: true,

        });
        $('#relatedCarousel').owlCarousel({
            loop: true,
            margin: 10,
            autoplay: true,
            autoplayTimeout: 1000,
            autoplayHoverPause: true,
            responsiveClass: true,
            nav: true,
            dots: false,
            responsive: {
                0: {
                    items: 3,
                },
                600: {
                    items: 3,
                },
                1000: {
                    items: 4,
                }
            }
        });
        $('#featuredCarousel').owlCarousel({
            loop: true,
            margin: 10,
            autoplay: true,
            autoplayTimeout: 1000,
            autoplayHoverPause: true,
            responsiveClass: true,
            nav: true,
            dots: false,
            responsive: {
                0: {
                    items: 3,
                },
                600: {
                    items: 3,
                },
                1000: {
                    items: 6,
                }
            }
        });

        $('#BestSelling').owlCarousel({
            loop: true,
            margin: 10,
            autoplay: true,
            autoplayTimeout: 1000,
            autoplayHoverPause: true,
            responsiveClass: true,
            dots: false,
            nav: true,
            responsive: {
                0: {
                    items: 2,
                },
                600: {
                    items: 2,
                },
                1000: {
                    items: 6,
                }
            }
        });





    });
</script>

@endsection
