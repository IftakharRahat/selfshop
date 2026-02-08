@extends('frontend.master')

@section('meta')
    <title>{{$subcategory->sub_category_name}}</title>
    <meta name="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta name="keywords" content="{{\App\Models\Basicinfo::first()->meta_keyword}}">

    <meta property="og:image" content="{{ url($subcategory->subcategory_icon) }}" />

    <meta itemprop="name" content="{{$subcategory->sub_category_name}}">
    <meta itemprop="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta itemprop="image" content="{{ url($subcategory->subcategory_icon) }}">

    <meta property="og:url" content="{{url('/')}}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{$subcategory->sub_category_name}}">
    <meta property="og:description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta property="og:image" content="{{ url($subcategory->subcategory_icon) }}">
    <meta property="image" content="{{ url($subcategory->subcategory_icon) }}" />
    <meta property="url" content="{{url('/')}}">
    <meta name="robots" content="index, follow" />
    <meta itemprop="image" content="{{ url($subcategory->subcategory_icon) }}">
    <meta property="twitter:card" content="{{ url($subcategory->subcategory_icon) }}" />
    <meta property="twitter:title" content="{{$subcategory->sub_category_name}}" />
    <meta property="twitter:url" content="{{url('/')}}">
    <meta name="twitter:image" content="{{ url($subcategory->subcategory_icon) }}">
@endsection


@section('maincontent')
    <div class="container py-2">
        <div class="mb-0" style="display: flex;justify-content: space-between;">
            <h2 class="titlelg" style=""><span class="" style="border-bottom: none !important;text-transform: uppercase;font-weight: bold;">{{ $subcategory->sub_category_name }}</span></h2>
        </div>
        <div class="row px-xl-5" id="data-wrapper-subcategory"> 
            @include('frontend.content.product.view',['categoryproducts'=>$categoryproducts]) 
        </div>
        <div class="auto-load text-center" style="display: none;">
            <svg version="1.1" id="L9" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                x="0px" y="0px" height="60" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
                <path fill="#000"
                    d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50">
                    <animateTransform attributeName="transform" attributeType="XML" type="rotate" dur="1s"
                        from="0 50 50" to="360 50 50" repeatCount="indefinite" />
                </path>
            </svg>
        </div>
    </div>

    <br>

    <div class="container pt-4" id="data-wrapper-footer">
        
    </div>
</div>
<br>

 

    <style>
        #orderConfirm {
            font-size: 25px !important;
            padding: 15px;
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

    <script>
        var ENDPOINT = "{{ url('datafooter-load-ajax') }}"; 
        var ENDPOINTCAT = "{{ url('datasubcategory-load-ajax') }}";
        var page = 1;
        var i=0;
        
        $(window).scroll(function () {  
            page++;
            infinteLoadSubCategory(page);
            if(i==0){
                infinteLoadMore(i);  
                i=1;
            }
        });
        
        function infinteLoadSubCategory(page) {
            $.ajax({
                url: ENDPOINTCAT + "?page=" + page,
                datatype: "html",
                type: "get",
                beforeSend: function () {
                    $('.auto-load').show();
                }
            })
            .done(function (response) {
                if (response.html == '') {
                    $('.auto-load').html("");
                    return;
                }
  
                $('.auto-load').hide();
                $("#data-wrapper-subcategory").append(response.html);
            })
            .fail(function (jqXHR, ajaxOptions, thrownError) {
                console.log('Server error occured');
            });
        }
       
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
