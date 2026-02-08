@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Products
@endsection

<style>
    a.paginate_button {
        background: gray;
    }
    div#roleinfo_length {
        color: red;
    }

    div#roleinfo_filter {
        color: red;
    }

    div#roleinfo_info {
        color: red;
    }

    label {
        color: white;
    }
</style>
<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/css/bootstrap-switch-button.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/dist/bootstrap-switch-button.min.js"></script>
{{-- summernote --}}
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">

<div class="px-4 pt-4 container-fluid">
    <div class="p-4 row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <h4 class="p-4 pt-0 text-center" style="font-size:38px">Create New Product</h4>
        </div>
        <form name="form" id="myForm" action="{{ url('admin/products') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-lg-6">
                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Product Information</h5>
                        </div>
                        <div class="card-body">
                            @if ($shop=='Yes')
                                <div class="mb-2 form-group">
                                    <label for="ProductCategory" style="width: 100%;">Choose Shop <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="shop_id" name="shop_id">
                                        <option>Select Shop</option>
                                        @forelse (App\Models\Admin::where('add_by',Auth::guard('admin')->user()->id)->where('type','Shop')->get() as $shop)
                                            <option value="{{ $shop->id }}">
                                                @if (isset($shop->shop_name))
                                                {{ $shop->shop_name }}
                                                @else
                                                {{ $shop->name }}
                                                @endif
                                            </option>
                                        @empty
                                        @endforelse
                                    </select>
                                </div>
                            @endif
                            <div class="mb-2 form-group">
                                <label for="ProductName">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="ProductName" id="ProductName" class="form-control"
                                    required>
                            </div> 
                            <div class="mb-2 form-group">
                                <label for="ProductCategory" style="width: 100%;">Categories <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" onchange="setsubcategory()" id="category_id" name="category_id" required>
                                    <option>Select Category</option>
                                    @forelse ($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->category_name }}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="mb-2 form-group">
                                <label for="ProductCategory" style="width: 100%;">Sub Category</label>
                                <select name="subcategory_id" id="subcategory_id" onchange="setminicategory()" class="mb-3 form-control" >
                                    <option value="">Choose Sub-Category</option>
                                </select>
                            </div>
                            <div class="mb-2 form-group">
                                <label for="ProductCategory" style="width: 100%;">Mini Category</label>
                                <select name="minicategory_id" id="minicategory_id" class="mb-3 form-control" >
                                    <option value="">Choose Mini-Category</option>
                                </select>
                            </div>
                            <div class="mb-2 form-group">
                                <label for="ProductCategory" style="width: 100%;">Brands <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="brand_id" name="brand_id">
                                    <option>Select Brand</option>
                                    @forelse ($brands as $brand)
                                        <option value="{{ $brand->id }}">
                                            {{ $brand->brand_name }}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Weight (in kg)<span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="product_weight"
                                            name="product_weight" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Minimum Purchese Qty<span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="minimum_qty" value="1"
                                            name="minimum_qty" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Product Images</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-2 form-group">
                                        <label for="ProductDetails">Thumbnail Image <span
                                                class="text-danger">*</span></label>
                                        <button type="button" class="mb-2 btn btn-danger d-block"
                                            style="background: red">
                                            <input type="file" name="ProductImage" id="ProductImage"
                                                onchange="loadFile(event)">
                                        </button>
                                        <div class="clearfix single-image image-holder-wrapper">
                                            <div class="image-holder placeholder">
                                                <img id="prevImage" style="max-height:100px;" />
                                                <i class="mdi mdi-folder-image"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4 col-12">
                                    <div class="form-group"
                                        style="padding: 10px;padding-top: 3px;margin:0;padding-bottom:3px;width:96%;margin-left: 8px;border-radius: 8px;padding-left: 0;margin-left: -0;">
                                        <label class="fileContainer">
                                            <span style="font-size: 20px;">Gallery Images</span>
                                        </label>
                                        <br>
                                        <button type="button" class="mb-2 btn btn-danger d-block"
                                            style="background: red">
                                            <input type="file" onchange="prevPost_Img()"
                                                name="PostImage[]" id="PostImage" multiple>
                                        </button>
                                    </div>
                                    <div class="file">
                                        <div id="prevFile"
                                            style="width: 100%;float:left;background: lightgray;">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Product Videos</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 form-group">
                                <label for="youtube_link">Youtube Embade code <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="youtube_link" id="youtube_link"
                                    class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Product Attributes</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Colour
                                            <span class="text-danger">*</span></label>
                                        <br>
                                        @forelse ($colors as $color)
                                            <input type="checkbox" name="color[]"
                                                value="{{ $color->value }}">
                                            {{ $color->value }} &nbsp;
                                        @empty
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-2 form-group">
                                        <label for="ProductSalePrice">Size <span
                                                class="text-danger">*</span></label>
                                        <br>
                                        @forelse ($sizes as $size)
                                            <input type="checkbox" name="size[]"
                                                value="{{ $size->value }}">
                                            {{ $size->value }} &nbsp;
                                        @empty
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-2 form-group">
                                        <label for="ProductSalePrice">Weights <span
                                                class="text-danger">*</span></label>
                                        <br>
                                        @forelse ($weights as $weight)
                                            <input type="checkbox" name="weight[]"
                                                value="{{ $weight->value }}"> {{ $weight->value }}
                                            &nbsp;
                                        @empty
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Product Description</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 form-group">
                                <label for="ProductRegularPrice">Product Short Description <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" name="ProductBreaf" rows="2"></textarea>
                            </div>

                            <div class="mb-2 form-group">
                                <label for="ProductDetailsss">Product Description <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="ProductDetails" name="ProductDetails" rows="5"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Seo Meta</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 form-group">
                                <label for="ProductName">Meta Title <span class="text-danger">*</span></label>
                                <input type="text" name="MetaTitle" id="MetaTitle" class="form-control">
                            </div>
                            <div class="mb-2 form-group">
                                <label for="ProductRegularPrice">Meta Description <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" name="MetaDescription" rows="2"></textarea>
                            </div>
                            <div class="mb-2 form-group">
                                <label for="ProductRegularPrice">Meta Keywords <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" name="MetaKey" rows="2"></textarea>
                            </div>
                            <div class="mb-2 form-group">
                                <label for="ProductRegularPrice">Meta Image <span
                                        class="text-danger">*</span></label>
                                <input type="file" name="meta_image" class="form-control">
                            </div>
                        </div>
                    </div>


                    <script type="text/javascript">
                        $(document).ready(function() {
                            $('#ProductDetails').summernote();
                        });

                        function setsubcategory() {
                            var sub_id = $('#category_id').val();
                            $.ajax({
                                type: 'GET',
                                url: '../get/subcategory/' + sub_id,

                                success: function(data) {
                                    $('#subcategory_id').html('');

                                    for (var i = 0; i < data.length; i++) {
                                        $('#subcategory_id').append(`
                                                <option value="` + data[i].id + `" >` + data[i].sub_category_name + `</option>
                                            `)
                                    }
                                },
                                error: function(error) {
                                    console.log('error');
                                }
                            });
                        }
                        function setminicategory() {
                            var sub_id = $('#subcategory_id').val();
                            $.ajax({
                                type: 'GET',
                                url: '../get/minicategory/' + sub_id,

                                success: function(data) {
                                    $('#minicategory_id').html('');

                                    for (var i = 0; i < data.length; i++) {
                                        $('#minicategory_id').append(`
                                                <option value="` + data[i].id + `" >` + data[i].mini_category_name + `</option>
                                            `)
                                    }
                                },
                                error: function(error) {
                                    console.log('error');
                                }
                            });
                        }

                    </script>

                </div>

                <div class="col-lg-6">
                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Product Price + Stock</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Wholesale Price <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="ProductWholesalePrice"
                                            name="ProductWholesalePrice" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Reseller Price <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="ProductResellerPrice"
                                            name="ProductResellerPrice" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Regular Price <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="ProductRegularPrice"
                                            name="ProductRegularPrice" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductSalePrice">Sell Offer Price <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="ProductSalePrice" name="ProductSalePrice"
                                            class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductSalePrice">Discount Percent (%)<span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="Discount" name="Discount"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Minimum Selling Price<span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="min_sell_price"
                                            name="min_sell_price" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2 form-group">
                                <label for="ProductRegularPrice">Quantity<span
                                        class="text-danger">*</span></label>
                                <input type="number" id="qty"
                                    name="qty" class="form-control" required>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Packing & Shipping Config:</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Extra Packing Charge</label>
                                        <input type="text" id="ex_pack"
                                            name="ex_pack" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Extra Delivery Charge</label>
                                        <input type="text" id="ex_dvc"
                                            name="ex_dvc" class="form-control">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Low Stock Quantity Warning:</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Quantity <span
                                            class="text-danger">*</span></label>
                                        <input type="text" id="low_stock"
                                            name="low_stock" class="form-control">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Stock Visibility State</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show Stock Quantity<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="show_stock" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show Stock Text Only<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="show_stock_text" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Activity Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Any Web<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="mart_status" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Only Reseller<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="reseller_status" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Reseller Order Bonus</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Amount<span
                                            class="text-danger">*</span></label>
                                        <input type="text" name="reseller_bonus" class="form-control">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Show In New Product List</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show In New Product List ?<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="show_new_product" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show In Hot Selling List ?<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="hot_list" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show In Ready To Bost List ?<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="ready_bost" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show In Profitable Product List ?<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="profitable" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show In Limited Offer List ?<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="limited" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                    <div class="mb-2 form-group d-flex justify-content-between">
                                        <label for="ProductRegularPrice">Show In Summer Collection List ?<span
                                            class="text-danger">*</span></label>
                                        <input type="checkbox" name="summer" data-toggle="switchbutton" data-onstyle="success">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="mb-3 card">
                        <div class="card-header">
                            <h5 class="m-0 mt-0 text-uppercase" style="color: black;">Estimate Shipping Time</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2 form-group">
                                        <label for="ProductRegularPrice">Shippinng Days<span
                                            class="text-danger">*</span></label>
                                        <input type="text" name="shipping_days" class="form-control">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
            <br>
            <div class="mt-2 form-group" style="text-align: right">
                <div class="submitBtnSCourse">
                    <button type="submit" name="btn"
                        class="btn btn-primary btn-block from-prevent-multiple-submits" style="width: 200px">Save</button>
                </div>
            </div>
        </form>
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

<script type="text/javascript">
    (function () {
        $(document).ready(function () {
            $("#myForm").on("submit", function () {
                $(".from-prevent-multiple-submits").prop("disabled", true);
            });
        });
    })();
</script>

<script>
    var loadFile = function(event) {
        var output = document.getElementById('prevImage');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function() {
            URL.revokeObjectURL(output.src) // free memory
        }
    };
    var galleryloadFile = function(event) {
        // document.getElementById("previmg").style.display = "none";
        var output = document.getElementById('galleryprevImage');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function() {
            URL.revokeObjectURL(output.src) // free memory
        }
    };
</script>

<script>
    var PostImages = [];

    function prevPost_Img() {
        var PostImage = document.getElementById('PostImage').files;

        for (i = 0; i < PostImage.length; i++) {
            if (check_duplicate(PostImage[i].name)) {
                PostImages.push({
                    "name": PostImage[i].name,
                    "url": URL.createObjectURL(PostImage[i]),
                    "file": PostImage[i],
                });
            } else {
                alert(PostImage[i].name + 'is already added to your list');
            }
        }

        document.getElementById("prevFile").innerHTML = PostImage_show();

    }

    function check_duplicate(name) {
        var PostImage = true;
        if (PostImages.length > 0) {
            for (e = 0; e < PostImages.length; e++) {
                if (PostImages[e].name == name) {
                    PostImage = false;
                    break;
                }
            }
        }
        return PostImage;
    }

    function PostImage_show() {
        var PostImage = "";
        PostImages.forEach((i) => {
            PostImage += `<div class="postImg" style="width:25%;float:left;position:relative;">
                                <img src="` + i.url + `" alt="" id="previewImage" style="border-radius: 10px;width:100%;padding:5px;">
                                <span onclick="removeSelectedPostImage(` + PostImages.indexOf(i) + `)" style="position: absolute;right: 0;cursor: pointer;font-size: 31px;color: red;margin-top: -8px;margin-right: 8px;">&times</span>
                            </div>`;
        })
        return PostImage;
    }

    function removeSelectedPostImage(e) {
        PostImages.splice(e, 1);
        document.getElementById("prevFile").innerHTML = PostImage_show();
    }

</script>
<!-- summernote css/js -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>



@endsection
