@extends('frontend.master')

@section('meta')
    <title>{{$product->ProductName}}</title>
    <meta name="description" content="{{$product->ProductBreaf}}">
    <meta name="keywords" content="{{\App\Models\Basicinfo::first()->meta_keyword}}">

    <meta property="og:image" content="{{ url($product->ViewProductImage) }}" />

    <meta itemprop="name" content="{{$product->ProductName}}">
    <meta itemprop="description" content="{{$product->ProductBreaf}}">
    <meta itemprop="image" content="{{ url($product->ViewProductImage) }}">

    <meta property="og:url" content="{{url('/')}}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{$product->ProductName}}">
    <meta property="og:description" content="{{$product->ProductBreaf}}">
    <meta property="og:image" content="{{ url($product->ViewProductImage) }}">
    <meta property="image" content="{{ url($product->ViewProductImage) }}" />
    <meta property="url" content="{{url('/')}}">
    <meta name="robots" content="index, follow" />
    <meta itemprop="image" content="{{ url($product->ViewProductImage) }}">
    <meta property="twitter:card" content="{{ url($product->ViewProductImage) }}" />
    <meta property="twitter:title" content="{{$product->ProductName}}" />
    <meta property="twitter:url" content="{{url('/')}}">
    <meta name="twitter:image" content="{{ url($product->ViewProductImage) }}">
@endsection


@section('maincontent')
<style>
    #byenow{
        padding: 6px;
    }
    @media only screen and (max-width: 600px) {
        #byenow {
            padding: 0px;
        }
    }

    .table th, .table td {
        padding: 0.23rem;
        vertical-align: top;
        border-top: 1px solid #EDF1FF;
        text-align: center;
    }
    .table-bordered th, .table-bordered td {
        border: 1px solid #a0a0a0;
        color: #2A74B8;
        font-weight: bold;
    }

    #buttonplus{
        font-size: 18px;
        border: 1px solid;
        padding: 3px 8px;
        border-radius: 0px;
        height: 28px;
        margin: 0;
        line-height: 4px;
        background: green;
        color: white;
        border: 1px solid green;
    }
    #buttonminus{
        font-size: 18px;
        border: 1px solid;
        padding: 3px 8px;
        border-radius: 0px;
        height: 28px;
        margin: 0;
        line-height: 4px;
        background: red;
        color: white;
        border: 1px solid red;
    }


@media only screen and (min-width: 768px) {
 .item img{
     padding-left:50px;
 }
}
</style>

    <div class="container pt-2 mt-0">
        <div class="row px-xl-5">
            <div class="pb-2 col-lg-5">
                @if (isset($product->PostImage))
                    <div id="sync1" class="owl-carousel owl-theme">
                        @forelse (json_decode($product->PostImage) as $key=>$productImage)
                            <div class="items">
                                <img class="w-100 h-100" src="{{ asset('public/images/product/slider/') }}/{{ $productImage }}" alt="" style="border-radius: 4px;">
                                @if(Auth::id())
                                    @if(Auth::user()->status=='Active')
                                    <div class="d-flex justify-content-between" style="margin-top: -20px;">
                                        @if(App\Models\Shopproduct::where('user_id',Auth::user()->id)->where('product_id',$product->id)->first())
                                            <button onclick="removefromshop({{ $product->id }})" class="d-flex btn btn-info btn-sm" style="background:#ff5d01;border:1px solid #ff5d01;padding-top: 9px;border-radius:4px;">
                                                <i class="fa fa-store" style="padding-top: 3px;padding-right: 5px;"></i>
                                                <span>Remove From Shop</span>
                                            </button>
                                        @else
                                            <button onclick="addtoshop({{ $product->id }})" class="d-flex btn btn-info btn-sm" style="padding-top: 9px;border-radius:4px;">
                                                <img src="{{ asset('public/hert.png') }}" alt="" style="width:20px;">
                                                <span>Add To Shop</span>
                                            </button>
                                        @endif
                                        <a href="{{ url('download/image',$key) }}?product_id={{$product->id}}" class="btn btn-info btn-sm" style="padding-top: 9px;border-radius:4px;height:40px;background:#14BF7D;border:1px solid #14BF7D">Download Image <i class="fas fa-arrow-down"></i></a>
                                    </div>
                                    @endif
                                @endif
                            </div>
                        @empty
                        @endforelse
                    </div>
                    <div id="sync2" class="owl-carousel owl-theme" style="padding-top: 10px;">
                        @forelse (json_decode($product->PostImage) as $productImage)
                            <div class="items">
                                <img class="w-100 h-100" style="padding:10px;border:1px solid;border-radius: 4px;"
                                    src="{{ asset('public/images/product/slider/') }}/{{ $productImage }}" alt="">

                            </div>
                        @empty
                        @endforelse
                    </div>
                @else
                    <div class="items">
                        <img class="w-100 h-100" src="{{ asset($product->ViewProductImage) }}" alt="" style="border-radius: 4px;">
                        @if(Auth::id())
                            @if(Auth::user()->status=='Active')
                                <div class="d-flex justify-content-between" style="margin-top: -20px;">
                                    @if(App\Models\Shopproduct::where('user_id',Auth::user()->id)->where('product_id',$product->id)->first())
                                        <button onclick="removefromshop({{ $product->id }})" class="d-flex btn btn-info btn-sm" style="background:#ff5d01;border:1px solid #ff5d01;padding-top: 9px;border-radius:4px;">
                                            <i class="fa fa-store" style="padding-top: 3px;padding-right: 5px;"></i>
                                            <span>Remove From Shop</span>
                                        </button>
                                    @else
                                        <button onclick="addtoshop({{ $product->id }})" class="d-flex btn btn-info btn-sm" style="padding-top: 9px;border-radius:4px;">
                                            <img src="{{ asset('public/hert.png') }}" alt="" style="width:20px;">
                                            <span>Add To Shop</span>
                                        </button>
                                    @endif
                                    <a href="{{ url('download/image-single',$product->id) }}" class="btn btn-info btn-sm" style="padding-top: 9px;border-radius:4px;height:40px;background:#14BF7D;border:1px solid #14BF7D">Download Image <i class="fas fa-arrow-down"></i></a>
                                </div>
                            @endif
                        @endif
                    </div>
                @endif

            </div>
            <div class="col-lg-1"></div>
            <div class="pb-2 col-lg-6">
                <div class="p-2 card card-body" style="border: none;">
                    <h3 class="font-weight-semi-bold" style="text-transform: capitalize;font-size:1.25rem">{{ $product->ProductName }}</h3>
                    <div class="info" style="margin-left: 10px;border-left: 4px solid; padding: 6px;">
                        <p class="p-0 m-0">Category : {{ App\Models\Category::where('id',$product->category_id)->first()->category_name }}</p>
                        <p class="p-0 m-0">Quantity : {{ $product->qty }}</p>
                        <input type="hidden" name="maxqty" id="maxqty" value="{{ $product->qty }}">
                        <p class="p-0 m-0">SKU : {{ $product->ProductSku }}</p>
                        <p class="p-0 m-0">Minimum Sell Price : @if(Auth::id()) @if(Auth::user()->status=='Active') {{ $product->min_sell_price }} @else {{ $product->ProductSalePrice }} @endif @endif</p>
                    </div>
                    <p class="p-2 pb-0 mb-0">{{ $product->ProductBreaf }}</p>
                    <div class="d-flex justify-content-between" style="margin-top: 10px;margin-bottom: 12px;">
                        <div class="price">
                            <div class="d-flex justify-content-center">
                                @if(Auth::id())
                                    @if(Auth::user()->status=='Active')
                                    <h6 class="mb-0 mb-lg-2" style="font-weight: bold; margin: 0; padding-top: 22px;"><del style="color: gray;font-weight: 400;font-size: 18px;">৳{{ intval($product->ProductSalePrice) }}</del> &nbsp;<span style="color: #ee3902;font-size: 22px;">৳{{ intval($product->ProductResellerPrice) }}</span></h6>
                                    @else
                                        <h6 class="mb-0 mb-lg-2" style="font-weight: bold; margin: 0; padding-top: 22px;"> <span style="color: #ee3902;font-size: 22px;">৳***</span></h6>
                                    @endif
                                @else
                                    <h6 class="mb-0 mb-lg-2" style="font-weight: bold; margin: 0; padding-top: 22px;"> <span style="color: #ee3902;font-size: 22px;">৳***</span></h6>
                                @endif
                            </div>
                        </div>
                        @if (Auth::id())
                            @if (Auth::user()->status=='Active')
                                <input type="text" name="buyprice" id="buyprice" @if(Auth::user()->status=='Active') value="{{ intval($product->ProductResellerPrice) }}" @else  @endif hidden>
                                <input type="text" name="minprice" id="minprice" @if(Auth::user()->status=='Active') value="{{ intval($product->min_sell_price) }}" @else  @endif hidden>
                            @endif
                        @endif
                        <span class="text-center">
                            <label for="" style=" margin: 0; padding-top: 1px; font-weight: bold;">Quantity</label>
                            <div class="d-flex">
                                <button class="btn btn-sm" id="buttonminus" onclick="minus()">-</button>
                                <div class="cart-quantity">
                                    <div class="quant-input">
                                        <input type="text" class="form-control" style="height: fit-content;height: 28px;padding:0px;width: 80px;text-align: center;border-radius:0px;" value="1" id="qtyval">
                                    </div>
                                </div>
                                <button class="btn btn-sm" id="buttonplus" onclick="plus()">+</button>
                            </div>
                        </span>
                    </div>

                    @if($product->qty>0 && $product->status=='Active')
                    <div class="mt-2 mb-2 row">
                        @if (empty($product->color))
                        @else
                            <div class="col-12 col-md-12 colorpart">
                                <div class="d-flex justify-content-between">
                                    <h4 id="resellerprice"><b style="font-size:20px">COLOUR:</b></h4>
                                    <div class="colorinfo">
                                        @forelse (json_decode($product->color) as $color)
                                            <input type="radio" class="m-0" id="color{{ $color }}" hidden name="color" onclick="getcolor('{{ $color }}')">
                                            <label class="colortext ms-0" id="colortext{{ $color }}" for="color{{ $color }}" style="height: 36px;width: 36px;background: {{ $color }};border-radius: 50%;" onclick="getcolor('{{ $color }}')"></label>
                                        @empty
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (empty($product->size))
                        @else
                            <div class="col-12 col-md-12 colorpart">
                                <div class="d-flex justify-content-between">
                                    <h4 id="resellerprice"><b style="font-size:20px">SIZE:</b></h4>
                                    <div class="sizeinfo">
                                        @forelse (json_decode($product->size) as $size)
                                            <input type="radio" class="m-0" hidden id="size{{ $size }}" name="size" onclick="getsize('{{ $size }}')">
                                            <label class="sizetext ms-0" id="sizetext{{ $size }}" for="size{{ $size }}" style="border: 1px solid #613EEA;font-size:20px;font-weight:bold;padding: 0px 12px;color: white;border-radius: 4px;" onclick="getsize('{{ $size }}')">{{ $size }}</label>
                                        @empty
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="mt-3 col-12 col-md-12">
                            <h4 id="resellerprice"><b style="font-size:20px">My Selling Price:</b></h4>
                            <input type="text" class="form-control" style="border-radius:4px;" name="my_selling_price" id="my_selling_price" placeholder="৳ 000" onkeyup="setprofit()" @if(Auth::id()) @else disabled @endif>
                            <small class="d-none" style="color: #EF486A;float: right;padding-top: 6px;"> <span id="orderbonus">0</span></small>
                        </div>
                        <div class="mt-3 col-12 col-md-12">
                            <h6 class="m-0 text-success">My total earnings on this order <b>৳ <span id="profitview">0.00</span></b></h6>
                        </div>
                    </div>
                    <div class="mt-3 mb-4 align-items-center" style="width:100%;float:left;">
                        <form id="AddToCartForm" name="form" method="POST" enctype="multipart/form-data"
                            style="width: 50%;float: left;">
                            @method('POST')
                            @csrf
                            <input type="text" name="product_id" value="{{ $product->id }}" hidden>
                            <input type="text" name="color" id="product_colorad" hidden>
                            <input type="text" name="price" id="priceor" hidden>
                            <input type="text" name="size" id="product_sizead" hidden>
                            <input type="text" name="qty" value="1" id="qtyor" hidden>
                            <button type="submit" disabled id="bnow" class="btn btn-primary add-to-cart "
                                style="width:100%;padding: 6px 0px;border-radius:6px">
                                <i class="fa fa-shopping-cart" style="color:white;"></i>
                                <span class="d-md-inline-block" style="color:white;font-weight:bold;">Add To Cart</span>
                            </button>
                        </form>


                        <form name="form" method="POST" action="{{ url('/add-to-cart') }}" enctype="multipart/form-data"
                            style="width: 45%;float: right;">
                            @csrf
                            <input type="text" name="product_id" value=" {{ $product->id }}" hidden>
                            <input type="text" name="price" id="price" hidden>
                            <input type="text" name="color" id="product_coloror" hidden>
                            <input type="text" name="size" id="product_sizeor" hidden>
                            <input type="text" name="qty" value="1" id="qty" hidden>
                            <button type="submit" disabled id="byenow" class="btn btn-primary" style="font-size: 22px;border-radius:6px;background-color: #14BF7D;border-color: #14BF7D;width: 100%;color:white;font-weight:bold">Order Now</button>
                        </form>
                    </div>
                    @else
                    <div class="text-center">
                        <img src="{{asset('public/stockout.png')}}" alt="" style="width: 70%">
                    </div>
                    @endif

                    <div style="padding: 4px;margin-top:10px;float: left;width: 100%; margin-bottom: 0px;">

                        <div class="p-2 text-center card card-body" style="font-weight: bold;border:1px solid #613EEA;color:#ff5000;border-radius:6px">
                            Please pay the delivery chagre before confirm the order.
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="pt-4 row px-xl-5">
            <div class="col">
                <div class="mb-4 border-none nav nav-tabs justify-content-between" style="border:none;">
                    <a class="nav-item nav-link active"  style="border: none;padding-left: 0px;font-size: 20px;color: #e00060;border-bottom: 2px solid;" data-toggle="tab" href="#tab-pane-1"><b>Product Description</b></a><button id="copydescription"  onclick="myFunction()" class="bt btn-info" style="border: none;border-radius: 6px;height: 36px;">COPY</button>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-pane-1">
                        <div id="productDescriptionCopy">{!! $product->ProductDetails !!}</div>
                        <br>
                        <div id="productDescription">
                            @if (isset($product->youtube_link))
                                <iframe id="ifrem" src="https://www.youtube.com/embed/{{ $product->youtube_link }}">
                                </iframe>
                            @else
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="mb-0" style="display: flex;justify-content: space-between;">
            <h2 class="section-title" style=""><span class="titlelg" style="border-bottom: none !important;">RELATED PRODUCTS</span></h2>

        </div>
        <div class="row px-xl-5" id="data-wrapper-related">
            @include('frontend.content.product.related',['relatedproducts'=>$relatedproducts])
        </div>
    </div>

    <br>


    <style>
        #orderConfirm {
            font-size: 18px !important;
            padding: 12px;
            border-radius: 5px;
        }

        #closebtn {
            font-size: 18px;
            padding: 12px;
            float: right;
            border-radius: 5px;
        }

        #poductview {
            padding-left: 4px;
            padding-right: 6px !important;
        }

        #ordernowmodal {
            padding-left: 4px;
            padding-right: 6px !important;
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
                font-size: 22px !important;
                padding: 10px;
            }
        }
    </style>

    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

    <script>
var ENDPOINTCAT = "{{ url('datarelated-load-ajax') }}";
var page = 1;
var isLoading = false;
var hasMore = true;
var initialCount = {{ $relatedproducts->count() }};

// Hide load more button if initial products are less than 8
$(document).ready(function() {
    if (initialCount < 8) {
        hasMore = false;
        $('#load-more-container').html('<p class="text-muted">No more products to load.</p>');
    }
});

// Load more products function
function loadMoreProducts() {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    page++;
    
    // Show loading spinner, hide button
    $('#load-more-btn').hide();
    $('.auto-load').show();
    
    $.ajax({
        url: ENDPOINTCAT + "?page=" + page + "&product_id={{ $product->id }}",
        datatype: "html",
        type: "get"
    })
    .done(function (response) {
        $('.auto-load').hide();
        
        if (response.html && response.html.trim() !== '' && response.html !== 'empty') {
            $("#data-wrapper-related").append(response.html);
            
            // Check if we got exactly 8 products (meaning there might be more)
            // Count the number of product items in the response
            var productCount = $(response.html).filter('.col-lg-3').length;
            
            if (productCount < 8) {
                hasMore = false;
                $('#load-more-container').html('<p class="text-muted">No more products to load.</p>');
            } else {
                $('#load-more-btn').show();
            }
        } else {
            hasMore = false;
            $('#load-more-container').html('<p class="text-muted">No more products to load.</p>');
        }
        
        isLoading = false;
    })
    .fail(function (jqXHR, ajaxOptions, thrownError) {
        console.log('Server error occurred');
        $('.auto-load').hide();
        $('#load-more-btn').show();
        isLoading = false;
    });
}

        function myFunction() {
            var range = document.createRange();
            range.selectNode(document.getElementById("productDescriptionCopy"));
            window.getSelection().removeAllRanges(); // clear current selection
            window.getSelection().addRange(range); // to select text
            document.execCommand("copy");
            window.getSelection().removeAllRanges();// to deselect
            alert("Copied Successfully ");
        }
        function minus(){
            var avqty=$('#qtyval').val();
            if(avqty==1){

            }else{
                qty=Number(avqty)-1;
                $('#qtyval').val(qty);
                $('#qtyor').val(qty);
                $('#qty').val(qty);
            }
        }

        function plus(){
            var max=$('#maxqty').val();
            var avqty=$('#qtyval').val();
            if(avqty==10){

            }else{
                qty=Number(avqty)+1;
                if(qty<=max){
                    $('#qtyval').val(qty);
                    $('#qtyor').val(qty);
                    $('#qty').val(qty);
                }else{

                }

            }
        }

        function setprofit(){
            var sell=$('#my_selling_price').val();
            var buy=$('#buyprice').val();
            var min=$('#minprice').val();

            if(sell==''){
                $('#byenow').prop('disabled', true);
                $('#bnow').prop('disabled', true);
                $('#price').val('');
                $('#priceor').val('');
                var profit=0;
                $('#profitview').text(profit);
            }else if(sell==0){
                $('#byenow').prop('disabled', false);
                $('#bnow').prop('disabled', false);
                var bonus=$('#orderbonus').text();
                var profit=Number(min)-Number(buy)+Number(bonus);
                $('#price').val(min);
                $('#priceor').val(min);
                $('#profitview').text(profit);
                console.log('4545');
            }else if(Number(sell)>=min){
                $('#byenow').prop('disabled', false);
                $('#bnow').prop('disabled', false);
                var bonus=$('#orderbonus').text();
                $('#price').val(Number(sell));
                $('#priceor').val(Number(sell));
                var profit=Number(sell)+Number(bonus)-Number(buy);
                $('#profitview').text(profit);
            }else{
                $('#byenow').prop('disabled', true);
                $('#bnow').prop('disabled', true);
                $('#price').val('');
                $('#priceor').val('');
                var profit=0;
                $('#profitview').text(profit);
                console.log('s');
            }

        }

        function addtoshop(id){
            $.ajax({
                type: 'GET',
                url: '{{ url("add/to-shop") }}/' + id,

                success: function(data) {
                    if(data=='success'){
                        swal("Thanks! Product id added to your store!", {
                            icon: "success",
                        });
                    }else if(data=='unverify'){
                        swal("Opps! Please login now!", {
                            icon: "error",
                        });
                    }else{
                        swal("Product Already Exist!", {
                            icon: "info",
                        });
                    }
                },
                error: function(error) {
                    console.log('error');
                }

            });
        }

        function removefromshop(id){
            $.ajax({
                type: 'GET',
                url: '{{ url("remove/from-shop") }}/' + id,

                success: function(data) {
                    if(data=='success'){
                        swal("Thanks! Product id remove from your store!", {
                            icon: "success",
                        });
                    }else if(data=='unverify'){
                        swal("Opps! Please login now!", {
                            icon: "error",
                        });
                    }else if(data=='notexist'){
                        swal("Opps! Please login now!", {
                            icon: "error",
                        });
                    }else{
                        swal("Product Already Exist!", {
                            icon: "info",
                        });
                    }
                },
                error: function(error) {
                    console.log('error');
                }

            });
        }

        $(document).on('click', '#productDetails', function() {
            var productId = $(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'productview/' + productId,

                success: function(data) {
                    $('#productNameTitle').text(data.ProductName);
                    $('#ProductViewDetails').find('#productName').text(data.ProductName);
                    $('#ProductViewDetails').find('#productCode').text(data.ProductSku);
                    $('#ProductViewDetails').find('#productPrice').text(data.ProductSalePrice);
                    $('#ProductViewDetails').find('#delproductPrice').text(data.ProductRegularPrice);
                    $('#ProductViewDetails').find('#productDescription').html(data.ProductDetails);

                    $('#productbutton').html('');
                    $('#productbutton').html(
                        `   <button type="button" data-id="` + data.id + `" data-toggle="modal" data-target="#ordernowmodal" id="orderNow" class="btn btn-primary" style="background-color: #0848af;border-color: #1645b5;width: 140px;color:white;font-weight:bold"">এখনই কিনুন</button>
                        <form name="form" method="POST" action="{{ url('/add-to-cart') }}" enctype="multipart/form-data" style="width: 50%;float: right;text-align: right;">
                            @csrf
                            <input type="text" name="product_id" value="` + data.id + `" hidden>
                            <input type="text" name="color" id="product_color" hidden>
                            <input type="text" name="size" id="product_size" hidden>
                            <input type="text" name="qty" value="1" id="qty" hidden>
                            <button type="submit"
                                    class="btn btn-primary add-to-cart" style="background-color: #098d07;border-color: #098d07;"
                            >
                                <i class="fa fa-shopping-cart" style="color:white;font-weight:bold"></i>
                                <span class="d-md-inline-block" style="color:white;font-weight:bold"> Add to cart</span>
                            </button>
                        </form>
                    `
                    );

                    $('#previmage').html(
                        ` <div id="sync1" class="owl-carousel owl-theme" >

                        </div>
                    `
                    );

                    $('#prevsmallimage').html(
                        ` <div id="sync2" class="owl-carousel owl-theme" style="padding-top: 10px;">

                        </div>
                    `
                    );

                    var image = JSON.parse(data.postImage);

                    if (image == null) {
                        $('#previmage').html('');

                        $('#previmage').append(`
                        <div class="item">
                            <img class="w-100 h-100" src="{{ asset('') }}` + data.feature_image + `" alt="" >
                        </div>
                    `);
                    } else {
                        $('#sync1').html('');
                        $('#sync2').html('');

                        $.each(image, function(key, value) {
                            $('#sync1').append(`
                            <div class="item">
                                <img class="w-100 h-100" src="{{ asset('public/images/product/slider/') }}/` + value +
                                `" alt="` + value + `" >
                            </div>
                        `);
                            $('#sync2').append(
                                `
                            <div class="item">
                                <img style="padding:10px;border:1px solid;" class="w-100 h-100" src="{{ asset('public/images/product/slider/') }}/` +
                                value + `" alt="` + value + `" >
                            </div>
                        `);
                        });

                        var slidesPerPage = 4; //globaly define number of elements per page
                        var syncedSecondary = true;

                        $("#sync1").owlCarousel({
                            items: 1,
                            slideSpeed: 2000,
                            autoplay: true,
                            dots: true,
                            loop: true,
                            responsiveRefreshRate: 200,
                            navText: [
                                '<svg width="100%" height="100%" viewBox="0 0 11 20"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M9.554,1.001l-8.607,8.607l8.607,8.606"/></svg>',
                                '<svg width="100%" height="100%" viewBox="0 0 11 20" version="1.1"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M1.054,18.214l8.606,-8.606l-8.606,-8.607"/></svg>'
                            ],
                        }).on('changed.owl.carousel', syncPosition);

                        $("#sync2")
                            .on('initialized.owl.carousel', function() {
                                $("#sync2").find(".owl-item").eq(0).addClass("current");
                            })
                            .owlCarousel({
                                items: slidesPerPage,
                                dots: true,
                                nav: true,
                                smartSpeed: 200,
                                slideSpeed: 500,
                                slideBy: slidesPerPage, //alternatively you can slide by 1, this way the active slide will stick to the first item in the second carousel
                                responsiveRefreshRate: 100
                            }).on('changed.owl.carousel', syncPosition2);

                        function syncPosition(el) {
                            //if you set loop to false, you have to restore this next line
                            //var current = el.item.index;

                            //if you disable loop you have to comment this block
                            var count = el.item.count - 1;
                            var current = Math.round(el.item.index - (el.item.count / 2) - .5);

                            if (current < 0) {
                                current = count;
                            }
                            if (current > count) {
                                current = 0;
                            }

                            //end block

                            $("#sync2")
                                .find(".owl-item")
                                .removeClass("current")
                                .eq(current)
                                .addClass("current");
                            var onscreen = $("#sync2").find('.owl-item.active').length - 1;
                            var start = $("#sync2").find('.owl-item.active').first().index();
                            var end = $("#sync2").find('.owl-item.active').last().index();

                            if (current > end) {
                                $("#sync2").data('owl.carousel').to(current, 100, true);
                            }
                            if (current < start) {
                                $("#sync2").data('owl.carousel').to(current - onscreen, 100, true);
                            }
                        };

                        function syncPosition2(el) {
                            if (syncedSecondary) {
                                var number = el.item.index;
                                $("#sync1").data('owl.carousel').to(number, 100, true);
                            }
                        };

                        $("#sync2").on("click", ".owl-item", function(e) {
                            e.preventDefault();
                            var number = $(this).index();
                            $("#sync1").data('owl.carousel').to(number, 300, true);
                        });

                    }
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });


        $(document).on('click', '#orderNow', function() {
            var productId = $(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'productview/' + productId,

                success: function(data) {
                    $('#productTopTitle').text(data.ProductName);
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
            var token = $("input[name='_token']").val();

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
                    url: '{{ url('add-cart') }}',
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        updatecart();
                        viewcart();
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




            var sync1 = $("#sync1");
            var sync2 = $("#sync2");
            var slidesPerPage = 4; //globaly define number of elements per page
            var syncedSecondary = true;

            sync1.owlCarousel({
                items: 1,
                slideSpeed: 2000,
                autoplay: true,
                dots: true,
                loop: true,
                responsiveRefreshRate: 200,
                navText: [
                    '<svg width="100%" height="100%" viewBox="0 0 11 20"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M9.554,1.001l-8.607,8.607l8.607,8.606"/></svg>',
                    '<svg width="100%" height="100%" viewBox="0 0 11 20" version="1.1"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M1.054,18.214l8.606,-8.606l-8.606,-8.607"/></svg>'
                ],
            }).on('changed.owl.carousel', syncPosition);

            sync2
                .on('initialized.owl.carousel', function() {
                    sync2.find(".owl-item").eq(0).addClass("current");
                })
                .owlCarousel({
                    margin:6,
                    items: slidesPerPage,
                    dots: true,
                    nav: true,
                    smartSpeed: 200,
                    slideSpeed: 500,
                    slideBy: slidesPerPage, //alternatively you can slide by 1, this way the active slide will stick to the first item in the second carousel
                    responsiveRefreshRate: 100
                }).on('changed.owl.carousel', syncPosition2);

            function syncPosition(el) {
                //if you set loop to false, you have to restore this next line
                //var current = el.item.index;

                //if you disable loop you have to comment this block
                var count = el.item.count - 1;
                var current = Math.round(el.item.index - (el.item.count / 2) - .5);

                if (current < 0) {
                    current = count;
                }
                if (current > count) {
                    current = 0;
                }

                //end block

                sync2
                    .find(".owl-item")
                    .removeClass("current")
                    .eq(current)
                    .addClass("current");
                var onscreen = sync2.find('.owl-item.active').length - 1;
                var start = sync2.find('.owl-item.active').first().index();
                var end = sync2.find('.owl-item.active').last().index();

                if (current > end) {
                    sync2.data('owl.carousel').to(current, 100, true);
                }
                if (current < start) {
                    sync2.data('owl.carousel').to(current - onscreen, 100, true);
                }
            }

            function syncPosition2(el) {
                if (syncedSecondary) {
                    var number = el.item.index;
                    sync1.data('owl.carousel').to(number, 100, true);
                }
            }

            sync2.on("click", ".owl-item", function(e) {
                e.preventDefault();
                var number = $(this).index();
                sync1.data('owl.carousel').to(number, 300, true);
            });
        });

        $(document).on('click', '#BuyNow', function() {
            var productId = $(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'productview/' + productId,

                success: function(data) {
                    $('#productBuyTopTitle').text(data.ProductName);
                    $('#ProductBuyInfo').find('#productName').text(data.ProductName);
                    $('#ProductBuyInfo').find('#productCode').text(data.ProductSku);
                    $('#ProductBuyInfo').find('#productPrice').text(data.ProductSalePrice);
                    $('#ProductBuyInfo').find('#delproductPrice').text(data.ProductRegularPrice);
                    $('#ProductBuyInfo').find('#productDescription').html(data.ProductDetails);
                    $('#ProductBuyInfo').find('#subTotal').val(data.ProductSalePrice);
                    $('#ProductBuyInfo').find('#productId').val(data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        function getcolor(color) {
            $('#product_colorad').val(color);
            $('#product_coloror').val(color);
            $('.colortext').css('opacity','1');
            $('.colortext').css('height','36px');
            $('.colortext').css('width','36px');
            $('#colortext'+color).css('opacity','.7');
            $('#colortext'+color).css('width','42px');
            $('#colortext'+color).css('height','42px');
        }

        function getsize(size) {
            $('#product_sizead').val(size);
            $('#product_sizeor').val(size);
            $('.sizetext').css('color','#fff');
            $('.sizetext').css('background','#613EEA');
            $('#sizetext'+size).css('color','#000');
            $('#sizetext'+size).css('background','#fff');
        }

        function upQuantity() {
            var qty = $('#proQuantity').val();
            $('#qty').val(qty);
            $('#qtyor').val(qty);
        }

    </script>

    <script type="text/javascript">
        (function() {
            $('.from-prevent-multiple-submits').on('submit', function() {
                $('.from-prevent-multiple-submits').attr('disabled', 'true');
            })
        })();
    </script>

@endsection
