@extends('frontend.master')

@section('maincontent')
<div class="container py-3">
    <div class="text-center mb-2 pb-0">
        <h2 class="section-title px-5"><img src="{{asset(Auth::user()->profile)}}" height="40px"><br><span class="px-2" style="border-bottom:none">{{ Auth::user()->shop_name }} </span>
        </h2>
        <hr class="mb-0">
    </div>
    <div class="row">
        @forelse ($searchcontents as $searchproduct)
             <div class="col-lg-2 col-6 p-2 pt-0 pb-2" >
                    <div class="card product-item" style="border:1px solid;padding: 4px;">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0"
                            id="cardimg">
                            @if (empty($searchproduct->ViewProductImage))
                                <a href="{{ url('/product/' . $searchproduct->ProductSlug) }}"><img
                                        class="img-fluid w-100"
                                        src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                        alt=""></a>
                            @else
                                <a href="{{ url('/product/' . $searchproduct->ProductSlug) }}"><img
                                        class="img-fluid w-100"
                                        src="{{ asset($searchproduct->ViewProductImage) }}"
                                        alt="{{ $searchproduct->productName }}"></a>
                            @endif
                        </div>
                        <div class="card-body p-0 pt-2">
                            <a href="{{ url('/product/' . $searchproduct->ProductSlug) }}">
                                <h6 class="text-truncate mb-2">{{ $searchproduct->ProductName }}</h6>
                            </a>
                            @include('frontend.includes.price',['price'=>$searchproduct])
                        </div>
                        <div class="card-footer justify-content-between bg-light border" style="padding:0;">
                            <form name="form" method="POST" action="{{ url('/add-to-cart') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="text" name="product_id" value=" {{ $searchproduct->id }}" hidden>
                                <input type="text" name="color" id="product_color" hidden>
                                <input type="text" name="size" id="product_size" hidden>
                                <input type="text" name="qty" value="1" id="qty" hidden>
                                <input type="text" name="price" id="price" value="{{ $searchproduct->min_sell_price }}" hidden>
                                <button type="submit"
                                class="btn btn-info btn-sm btn-block text-dark"
                                style="color: white !important;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">Order Now</button>
                            </form>
                        </div>
                    </div>
                </div>
        @empty
            <div class="col-md-3"></div>
            <div class="col-md-6 col-12" style="text-align: center; padding: 20px; border: dotted;">
                <h1>No Products Found !</h1>
            </div>
            <div class="col-md-3"></div>

        @endforelse
    </div>
</div>

<br>


<br>

        <style>

             #orderConfirm{
                font-size:18px !important;
                padding: 12px;
                border-radius: 5px;
            }
            #closebtn{
                font-size: 18px;
                padding: 12px;
                float: right;
                border-radius: 5px;
            }
                    #poductview{
                padding-left: 4px;
                padding-right: 6px !important;
            }

            #ordernowmodal{
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
            .current .item{
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
                #orderConfirm{
                    font-size:22px !important;
                    padding: 10px;
                }
            }



        </style>

        <script>
        $(document).on('click', '#productDetails', function() {
            var productId=$(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'productview/'+productId,

                success: function(data) {
                    $('#productNameTitle').text(data.ProductName);
                    $('#ProductViewDetails').find('#productName').text(data.ProductName);
                    $('#ProductViewDetails').find('#productCode').text(data.ProductSku);
                    $('#ProductViewDetails').find('#productPrice').text(data.ProductSalePrice);
                    $('#ProductViewDetails').find('#delproductPrice').text(data.ProductRegularPrice);
                    $('#ProductViewDetails').find('#productDescription').html(data.ProductDetails);

                    $('#productbutton').html('');
                    $('#productbutton').html(
                        `   <button type="button" data-id="`+data.id+`" data-toggle="modal" data-target="#ordernowmodal" id="orderNow" class="btn btn-primary" style="background-color: #0848af;border-color: #1645b5;width: 140px;color:white;font-weight:bold"">এখনই কিনুন</button>
                        <form name="form" method="POST" action="{{url('/add-to-cart')}}" enctype="multipart/form-data" style="width: 50%;float: right;text-align: right;">
                            @csrf
                            <input type="text" name="product_id" value="`+data.id+`" hidden>
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

                    var image =JSON.parse(data.postImage);

                    if(image == null){
                        $('#previmage').html('');

                        $('#previmage').append(`
                            <div class="item">
                                <img class="w-100 h-100" src="{{asset('')}}`+data.feature_image+`" alt="" >
                            </div>
                        `);
                    }else{
                        $('#sync1').html('');
                        $('#sync2').html('');

                        $.each(image, function (key, value) {
                            $('#sync1').append(`
                                <div class="item">
                                    <img class="w-100 h-100" src="{{asset('public/images/product/slider/')}}/`+value+`" alt="`+value+`" >
                                </div>
                            `);
                            $('#sync2').append(`
                                <div class="item">
                                    <img style="padding:10px;border:1px solid;" class="w-100 h-100" src="{{asset('public/images/product/slider/')}}/`+value+`" alt="`+value+`" >
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
                            navText: ['<svg width="100%" height="100%" viewBox="0 0 11 20"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M9.554,1.001l-8.607,8.607l8.607,8.606"/></svg>', '<svg width="100%" height="100%" viewBox="0 0 11 20" version="1.1"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M1.054,18.214l8.606,-8.606l-8.606,-8.607"/></svg>'],
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
                            var start =$("#sync2").find('.owl-item.active').first().index();
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
            var productId=$(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'productview/'+productId,

                success: function(data) {
                    $('#productTopTitle').text(data.ProductName);
                    $('#ProductOrderInfo').find('#productName').text(data.ProductName);
                    $('#ProductOrderInfo').find('#productCode').text(data.ProductSku);
                    $('#ProductOrderInfo').find('#productPrice').text(data.ProductSalePrice);
                    $('#ProductOrderInfo').find('#delproductPrice').text(data.ProductRegularPrice);
                    $('#ProductOrderInfo').find('#productDescription').html(data.ProductDetails);
                    $('#ProductOrderInfo').find('#productId').val(data.id);
                    $('#ProductOrderInfo').find('#subTotal').val(data.ProductSalePrice);
                    $('#poductview').modal('hide');
                    $('#ordernowmodal').modal('show');
                    $('.modal').css('overflow','auto');

                    var image =JSON.parse(data.postImage);

                    if(image == null){
                        $('#prevOrderimage').html('');

                        $('#prevOrderimage').append(`
                            <div class="item">
                                <img class="w-100" style="height: 400px;" src="{{ asset('') }}`+data.feature_image+`" alt="" >
                            </div>
                        `);
                    }else{
                        $('#prevOrderimage').html(
                            ` <div id="sync1" class="owl-carousel owl-theme" >

                                </div>
                            `
                        );

                        $('#sync1').html('');

                        $.each(image, function (key, value) {
                            $('#sync1').append(`
                                <div class="item">
                                    <img class="w-100"  style="height: 400px;" src="{{asset('public/images/product/slider/')}}/`+value+`" alt="`+value+`" >
                                </div>
                            `);
                        });

                        var slidesPerPage = 4; //globaly define number of elements per page
                        var syncedSecondary = true;

                        $("#sync1").owlCarousel({
                            items: 1,
                            slideSpeed: 1000,
                            autoplay: true,
                            dots: true,
                            loop: true,
                            responsiveRefreshRate: 200,
                            navText: ['<svg width="100%" height="100%" viewBox="0 0 11 20"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M9.554,1.001l-8.607,8.607l8.607,8.606"/></svg>', '<svg width="100%" height="100%" viewBox="0 0 11 20" version="1.1"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M1.054,18.214l8.606,-8.606l-8.606,-8.607"/></svg>'],
                        }).on('changed.owl.carousel', syncPosition);

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
                        };

                        function syncPosition2(el) {
                            if (syncedSecondary) {
                                var number = el.item.index;
                                $("#sync1").data('owl.carousel').to(number, 100, true);
                            }
                        };

                    }
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        $(document).ready(function() {

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
                navText: ['<svg width="100%" height="100%" viewBox="0 0 11 20"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M9.554,1.001l-8.607,8.607l8.607,8.606"/></svg>', '<svg width="100%" height="100%" viewBox="0 0 11 20" version="1.1"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M1.054,18.214l8.606,-8.606l-8.606,-8.607"/></svg>'],
            }).on('changed.owl.carousel', syncPosition);

            sync2
                .on('initialized.owl.carousel', function() {
                    sync2.find(".owl-item").eq(0).addClass("current");
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
    </script>

<script type="text/javascript">
    (function(){
    $('.from-prevent-multiple-submits').on('submit', function(){
        $('.from-prevent-multiple-submits').attr('disabled','true');
    })
    })();
</script>

@endsection
