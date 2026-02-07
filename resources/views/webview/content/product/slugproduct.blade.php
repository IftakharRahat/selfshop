@extends('webview.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- @if($slug=='best') Best Selling  @elseif($slug=='featured') Featured @elseif($slug=='promotional') Promotional @else @endif Product
@endsection
{{-- category slug --}}
<input type="hidden" name="category" id="categoryslug" value="{{ $slug }}">

<!-- /.breadcrumb -->
<div class="body-content outer-top-xs">
 
    @if(count($categories)>0)
        <section class="mt-1 mb-3">
            <div class="container">
                <div class="row">
                    <div class="col-12" id="smviewc" style="padding-right:0;">
                        <div class="px-2 py-1 p-md-3 bg-white shadow-sm">
                            <div class="owl-carousel best-category" id="categorySlide">
                                @forelse ($categories as $ctlist)
                                    <div class="item">
                                        <div class="products best-product">
                                            <div class="product" id="categoryslider">
                                                <div class="product-micro">
                                                    <div class="row product-micro-row">
                                                        <div class="col-12">
                                                            <div class="product-image">
                                                                <div class="image text-center">
                                                                    <a onclick="viewcategoryproduct('{{ $ctlist->slug }}')"
                                                                        type="button">
                                                                        @if (isset($ctlist->category_icon))
                                                                            <img data-original="{{ asset($ctlist->category_icon) }}"
                                                                                alt="{{ $ctlist->category_name }}"
                                                                                id="categoryimage">
                                                                        @else
                                                                            <img data-original="{{ asset('public/webview/assets/images/categoryimage.jpg') }}"
                                                                                alt="{{ $ctlist->category_name }}"
                                                                                id="categoryimage">
                                                                        @endif
                                                                    </a>
                                                                </div>
                                                                <!-- /.image -->
                                                            </div>
                                                            <!-- /.product-image -->
                                                        </div>
                                                        <!-- /.col -->
                                                        <div class="col-12 text-center" style="padding-top: 8px;">
                                                            <div class="product-info">
                                                                <h3 class="name" id="categoryNameinfo"><a
                                                                        onclick="viewcategoryproduct('{{ $ctlist->slug }}')"
                                                                        type="button"
                                                                        id="category_name">{{ $ctlist->category_name }}</a>
                                                                </h3>
                                                            </div>
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
            </div>
        </section>
    @else
    
    @endif
    
    <div class='container'>
        <div class='row'> 
            <!-- /.sidebar -->
            <div class='col-md-12' id="cateoryPro">
                <div class="container" id="viewCategoryProduct">

                </div>
                <!-- /.category-product -->


                <!-- /.tab-content -->
                <div class="clearfix filters-container">
                    <div class="text-right">
                        <div class="pagination-container">

                        </div>
                        <!-- /.pagination-container -->
                    </div>
                    <!-- /.text-right -->

                </div>
                <!-- /.filters-container -->

            </div>
            <!-- /.col -->
        </div>

        <!-- ============================================== BRANDS CAROUSEL : END ============================================== -->
    </div>
    <!-- /.container -->

</div>



{{-- csrf --}}
<input type="hidden" name="_token" value="{{ csrf_token() }}" />

<script>
    var token = $("input[name='_token']").val();

    $(document).ready(function() {

        var slug = $('#categoryslug').val();
        $('#processing').modal('show');

        $.ajax({
            type: 'GET',
            url: '{{ url('get/slug/products/') }}',
            data: {
                _token: token,
                slug: slug,
            },

            success: function(response) {
                $('#processing').modal('hide');
                $('#viewCategoryProduct').empty().append(response);
            },
            error: function(error) {
                console.log('error');
            }
        });

    });

    function shownowpro() {
        $('#processing').modal('show');
        var pricerange = $('.price-slider').val();
        var categorynow = $('#categoryslug').val();
        $.ajax({
            type: 'GET',
            url: '{{ url('get/products/by-category') }}',
            data: {
                _token: token,
                category: categorynow,
                price_range: pricerange
            },

            success: function(response) {
                $('#processing').modal('hide');
                $('#viewCategoryProduct').empty().append(response);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }


    function viewcategoryproduct(categoryslug) {
        $('#processing').modal('show');

        $.ajax({
            type: 'GET',
            url: '{{ url('get/products/by-category') }}',
            data: {
                _token: token,
                category: categoryslug,
            },

            success: function(response) {
                $('#processing').modal('hide');
                $('#viewCategoryProduct').empty().append(response);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }

    function shownow() {
        $('#processing').modal('show');
        var pricerange = $('.price-slider').val();
        var categoryslg = $('#categoryslug').val();
        $.ajax({
            type: 'GET',
            url: '{{ url('get/products/by-category') }}',
            data: {
                _token: token,
                category: categoryslg,
                price_range: pricerange
            },

            success: function(response) {
                $('#processing').modal('hide');
                $('#viewCategoryProduct').empty().append(response);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }

    function viewsubcategoryproduct(subcategoryslug) {
        $('#processing').modal('show');
        var pricerange = $('.price-slider').val();

        $.ajax({
            type: 'GET',
            url: '{{ url('get/products/by-subcategory') }}',
            data: {
                _token: token,
                subcategory: subcategoryslug,
                price_range: pricerange
            },

            success: function(response) {
                $('#processing').modal('hide');
                $('#viewCategoryProduct').empty().append(response);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }

    function viewcategoryproduct(categoryslug) {
        $('#processing').modal('show');

        $.ajax({
            type: 'GET',
            url: '{{ url('get/products/by-category') }}',
            data: {
                _token: token,
                category: categoryslug,
            },

            success: function(response) {
                $('#processing').modal('hide');
                $('#viewCategoryProduct').empty().append(response);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }
</script>

<style>
    @media only screen and (max-width: 768px) {
        #cateoryProSidebar {
            padding-right: 0;
        }

        #cateoryPro {
            padding-left: 0;
        }
    }

    #cateoryProSidebar {
        padding-left: 0;
    }

    #cateoryPro {
        padding-right: 0px;
    }

    .sidebar-widget .sidebar-widget-body .accordion .accordion-group .accordion-heading .accordion-toggle.collapsed:after {
        color: #636363;
        content: "\f067";
        font-family: fontawesome;
        font-weight: normal;
    }

    .sidebar .sidebar-module-container .sidebar-widget .sidebar-widget-body .accordion .accordion-group .accordion-heading .accordion-toggle:after {
        content: "\f068";
        float: right;
        font-family: fontawesome;
    }

    .widget-title {
        font-size: 16px;
        text-align: center;
        border-bottom: 1px solid #e9e9e9;
        padding-bottom: 10px;
        margin: 0;
    }

    .list {
        list-style: none;
    }

    #liaside {
        color: #858585;
        font-weight: bold;
    }

    .breadcrumb {
        padding: 5px 0;
        border-bottom: 1px solid #e9e9e9;
        background-color: #fafafa;
    }
</style>

@endsection
