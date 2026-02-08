@extends('frontend.master')

@section('meta')
    <title>{{$minicategory->mini_category_name}}</title>
    <meta name="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta name="keywords" content="{{\App\Models\Basicinfo::first()->meta_keyword}}">

    <meta property="og:image" content="{{ url($minicategory->minicategory_icon) }}" />

    <meta itemprop="name" content="{{$minicategory->mini_category_name}}">
    <meta itemprop="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta itemprop="image" content="{{ url($minicategory->minicategory_icon) }}">

    <meta property="og:url" content="{{url('/')}}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{$minicategory->mini_category_name}}">
    <meta property="og:description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta property="og:image" content="{{ url($minicategory->minicategory_icon) }}">
    <meta property="image" content="{{ url($minicategory->minicategory_icon) }}" />
    <meta property="url" content="{{url('/')}}">
    <meta name="robots" content="index, follow" />
    <meta itemprop="image" content="{{ url($minicategory->minicategory_icon) }}">
    <meta property="twitter:card" content="{{ url($minicategory->minicategory_icon) }}" />
    <meta property="twitter:title" content="{{$minicategory->mini_category_name}}" />
    <meta property="twitter:url" content="{{url('/')}}">
    <meta name="twitter:image" content="{{ url($minicategory->minicategory_icon) }}">
@endsection


@section('maincontent')
    <div class="container py-2">
        <div class="mb-0" style="display: flex;justify-content: space-between;">
            <h2 class="titlelg" style=""><span class="" style="border-bottom: none !important;text-transform: uppercase;font-weight: bold;">{{ $minicategory->mini_category_name }}</span></h2>
        </div>
        <div class="row px-xl-5" id="data-wrapper-minicategory">
            @include('frontend.content.product.view',['categoryproducts'=>$categoryproducts]) 
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
        var ENDPOINTCAT = "{{ url('dataminicategory-load-ajax') }}";
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
                $("#data-wrapper-minicategory").append(response.html);
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
