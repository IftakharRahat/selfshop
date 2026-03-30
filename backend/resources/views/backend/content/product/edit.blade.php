@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Edit Products
@endsection

<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/css/bootstrap-switch-button.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/dist/bootstrap-switch-button.min.js"></script>
{{-- summernote --}}
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">

<style>
    /* ---- Product Edit Page ---- */
    .product-create-wrapper {
        max-width: 1400px;
        margin: 0 auto;
    }
    .product-create-wrapper label {
        font-size: 13px;
        font-weight: 500;
        color: var(--admin-text, #1e293b);
        margin-bottom: 4px;
    }
    .product-create-wrapper .admin-content-card .admin-card-body {
        padding: 20px;
    }
    .product-create-wrapper .admin-card-header .admin-card-title {
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .product-create-wrapper .form-group {
        margin-bottom: 16px;
    }
    .product-create-wrapper .form-group:last-child {
        margin-bottom: 0;
    }
    .required-star {
        color: #ef4444;
        margin-left: 2px;
    }

    /* File upload dropzone */
    .file-upload-zone {
        border: 2px dashed var(--admin-border, #e2e8f0);
        border-radius: 10px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: var(--admin-bg, #f8fafc);
        position: relative;
    }
    .file-upload-zone:hover {
        border-color: var(--admin-primary, #2d2a5d);
        background: #f0f0ff;
    }
    .file-upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .file-upload-zone .upload-icon {
        font-size: 28px;
        color: var(--admin-text-muted, #94a3b8);
        margin-bottom: 8px;
    }
    .file-upload-zone .upload-text {
        font-size: 13px;
        color: var(--admin-text-muted, #94a3b8);
    }
    .file-upload-zone .upload-text strong {
        color: var(--admin-primary, #2d2a5d);
    }
    .thumbnail-preview {
        margin-top: 12px;
    }
    .thumbnail-preview img {
        max-height: 120px;
        border-radius: 8px;
        border: 1px solid var(--admin-border, #e2e8f0);
    }

    /* Gallery previews */
    #prevFile {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    #prevFile .postImg {
        width: calc(25% - 6px) !important;
        float: none !important;
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--admin-border, #e2e8f0);
    }
    #prevFile .postImg img {
        width: 100%;
        padding: 0 !important;
        border-radius: 8px;
    }
    #prevFile .postImg span {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(239, 68, 68, 0.9);
        color: #fff;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        margin: 0 !important;
    }

    /* Checkbox & switch styling */
    .product-create-wrapper .switch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .product-create-wrapper .switch-row:last-child {
        border-bottom: none;
    }
    .product-create-wrapper .switch-row label {
        margin-bottom: 0;
    }

    /* Attribute checkboxes */
    .attr-checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 6px;
    }
    .attr-checkbox-group label {
        display: flex;
        align-items: center;
        gap: 4px;
        background: var(--admin-bg, #f8fafc);
        border: 1px solid var(--admin-border, #e2e8f0);
        border-radius: 6px;
        padding: 5px 12px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .attr-checkbox-group label:hover {
        border-color: var(--admin-primary, #2d2a5d);
    }
    .attr-checkbox-group input[type="checkbox"]:checked + span {
        color: var(--admin-primary, #2d2a5d);
        font-weight: 600;
    }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="product-create-wrapper">
        <div class="pagetitle mb-3">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{url('/admindashboard')}}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('admin/shop/products') }}">Products</a></li>
                    <li class="breadcrumb-item active">Edit — {{ $product->ProductName }}</li>
                </ol>
            </nav>
        </div>

        <form name="form" action="{{ url('admin/product/'.$product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                {{-- Left Column --}}
                <div class="col-lg-6">
                    {{-- Product Information --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-box me-2"></i>Product Information
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            @if ($shop=='Yes')
                                <div class="form-group">
                                    <label>Choose Shop <span class="required-star">*</span></label>
                                    <select class="form-select" id="shop_id" name="shop_id">
                                        <option>Select Shop</option>
                                        @forelse (App\Models\Admin::where('add_by',Auth::guard('admin')->user()->id)->where('type','Shop')->get() as $shop)
                                            <option @if($product->shop_id == $shop->id) selected @endif value="{{ $shop->id }}">
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
                            <div class="form-group">
                                <label>Product Name <span class="required-star">*</span></label>
                                <input type="text" value="{{ $product->ProductName }}" name="ProductName" id="ProductName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Categories <span class="required-star">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" onchange="setsubcategory()" required>
                                    <option>Select Category</option>
                                    @forelse ($categories as $category)
                                        <option @if($product->category_id == $category->id) selected @endif value="{{ $category->id }}">
                                            {{ $category->category_name }}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sub Category</label>
                                <select name="subcategory_id" id="subcategory_id" onchange="setminicategory()" class="form-select">
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Mini Category</label>
                                <select name="minicategory_id" id="minicategory_id" class="form-select">
                                    @if($product->minicategory_id)
                                    <option value="{{ $product->minicategory_id }}">{{ $product->minicategories->mini_category_name }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Brands <span class="required-star">*</span></label>
                                <select class="form-select" id="brand_id" name="brand_id">
                                    <option>Select Brand</option>
                                    @forelse ($brands as $brand)
                                        <option @if($product->brand_id == $brand->id) selected @endif value="{{ $brand->id }}">
                                            {{ $brand->brand_name }}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Weight (in kg) <span class="required-star">*</span></label>
                                        <input type="text" id="product_weight" name="product_weight" value="{{ $product->product_weight }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Minimum Purchase Qty <span class="required-star">*</span></label>
                                        <input type="text" id="minimum_qty" name="minimum_qty" value="{{ $product->minimum_qty }}" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Product Images --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-image me-2"></i>Product Images
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Thumbnail Image <span class="required-star">*</span></label>
                                <div class="file-upload-zone">
                                    <input type="file" name="ProductImage" id="ProductImage" onchange="loadFile(event)">
                                    <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <div class="upload-text"><strong>Click to upload</strong> or drag and drop</div>
                                </div>
                                <div class="thumbnail-preview">
                                    <img id="prevImage" src="{{ str_starts_with($product->ProductImage ?? '', 'http') ? $product->ProductImage : asset($product->ProductImage) }}" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Gallery Images</label>
                                <div class="file-upload-zone">
                                    <input type="file" onchange="prevPost_Img()" name="PostImage[]" id="PostImage" multiple>
                                    <div class="upload-icon"><i class="bi bi-images"></i></div>
                                    <div class="upload-text"><strong>Click to upload</strong> multiple images</div>
                                </div>
                                <input type="hidden" name="removed_gallery_images" id="removedGalleryImages" value="">
                                <div id="existingGallery">
                                    @if (isset($product->PostImage))
                                        @forelse (json_decode($product->PostImage) as $index => $post)
                                            <div class="postImg existing-gallery-item" id="existingImg{{ $index }}" style="width:25%;float:left;position:relative;">
                                                <img src="{{ str_starts_with($post, 'http') ? $post : asset('public/images/product/slider/' . $post) }}" alt="" id="previewImage"
                                                    style="border-radius: 10px;width:100%;padding:5px;">
                                                <span onclick="removeExistingGalleryImage({{ $index }}, '{{ addslashes($post) }}')" style="position: absolute;right: 0;cursor: pointer;font-size: 31px;color: red;margin-top: -8px;margin-right: 8px;top: 0;">&times;</span>
                                            </div>
                                        @empty
                                        @endforelse
                                    @endif
                                </div>
                                <div id="prevFile">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Product Videos --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-youtube me-2"></i>Product Videos
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Youtube Embed Code</label>
                                <input type="text" name="youtube_link" value="{{ $product->youtube_link }}" id="youtube_link" class="form-control">
                            </div>
                        </div>
                    </div>

                    {{-- Product Attributes --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-palette me-2"></i>Product Attributes
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Colour <span class="required-star">*</span></label>
                                <div class="attr-checkbox-group">
                                    @forelse ($colors as $color)
                                        <label>
                                            <input type="checkbox" name="color[]"
                                                value="{{ $color->value }}" @if(json_decode($product->color)) {{ in_array($color->value, json_decode($product->color))?'checked':'' }} @endif>
                                            <span>{{ $color->value }}</span>
                                        </label>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Size <span class="required-star">*</span></label>
                                <div class="attr-checkbox-group">
                                    @forelse ($sizes as $size)
                                        <label>
                                            <input type="checkbox" name="size[]"
                                                value="{{ $size->value }}" @if(isset($product->size)) {{ in_array($size->value, json_decode($product->size))?'checked':'' }} @endif>
                                            <span>{{ $size->value }}</span>
                                        </label>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Weights <span class="required-star">*</span></label>
                                <div class="attr-checkbox-group">
                                    @forelse ($weights as $weight)
                                        <label>
                                            <input type="checkbox" name="weight[]"
                                                value="{{ $weight->value }}" @if(json_decode($product->weight)) {{ in_array($weight->value, json_decode($product->weight))?'checked':'' }} @endif>
                                            <span>{{ $weight->value }}</span>
                                        </label>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Product Description --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-text-paragraph me-2"></i>Product Description
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Product Description <span class="required-star">*</span></label>
                                <div id="customToolbar" style="display:flex;gap:6px;padding:6px 8px;background:#f8f9fa;border:1px solid #d1d5db;border-bottom:none;border-radius:5px 5px 0 0;flex-wrap:wrap;align-items:center;">
                                    <select id="fontSizeSelect" title="Font Size" style="height:32px;border:1px solid #d1d5db;border-radius:4px;padding:0 8px;font-size:13px;cursor:pointer;background:#fff;">
                                        <option value="">Font Size</option>
                                        <option value="1">Small</option>
                                        <option value="3">Normal</option>
                                        <option value="5">Large</option>
                                        <option value="7">Huge</option>
                                    </select>
                                    <div style="position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:32px;border:1px solid #d1d5db;border-radius:4px;background:#fff;cursor:pointer;" title="Text Color">
                                        <span id="colorLabel" style="font-weight:800;font-size:16px;color:#374151;pointer-events:none;z-index:1;border-bottom:3px solid #000;padding-bottom:1px;">A</span>
                                        <input type="color" id="fontColorPicker" value="#000000" style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;top:0;left:0;">
                                    </div>
                                </div>
                                <textarea class="form-control" id="ProductDetails" name="ProductDetails" rows="5">{{ $product->ProductDetails }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- SEO Meta --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-search me-2"></i>SEO Meta
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Meta Title</label>
                                <input type="text" name="MetaTitle" value="{{ $product->MetaTitle }}" id="MetaTitle" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Meta Description</label>
                                <textarea class="form-control" name="MetaDescription" rows="2">{{ $product->MetaDescription }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Keywords</label>
                                <textarea class="form-control" name="MetaKey" rows="2">{{ $product->MetaKey }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Image</label>
                                <input type="file" name="meta_image" class="form-control">
                                @if($product->meta_image)
                                    <div class="thumbnail-preview">
                                        <img src="{{ asset($product->meta_image) }}" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="sssubcategory_id" value="{{ $product->subcategory_id }}">
                    <input type="hidden" id="ssminicategory_id" value="{{ $product->minicategory_id }}">

                    <script type="text/javascript">
                        $(document).ready(function() {

                            var sub_id = $('#category_id').val();
                            $.ajax({
                                type: 'GET',
                                url: '../../get/subcategory/' + sub_id,

                                success: function(data) {
                                    $('#subcategory_id').html('');

                                    for (var i = 0; i < data.length; i++) {
                                        $('#subcategory_id').append(`
                                                <option value="` + data[i].id + `" >` + data[i].sub_category_name + `</option>
                                            `)
                                    }

                                    var sub =$('#sssubcategory_id').val();
                                    if(sub!=''){
                                        $('#subcategory_id').val(sub)
                                    }
                                    console.log(sub);

                                },
                                error: function(error) {
                                    console.log('error');
                                }
                            });

                            var mini_id = $('#subcategory_id').val();
                            $.ajax({
                                type: 'GET',
                                url: '../../get/minicategory/' + mini_id,

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
                            var subs =$('#ssminicategory_id').val();
                            if(subs!=''){
                                $('#minicategory_id').val(sub)
                            }

                            $('#ProductDetails').summernote({
                                toolbar: [
                                    ['style', ['style']],
                                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                                    ['para', ['ul', 'ol', 'paragraph']],
                                    ['table', ['table']],
                                    ['insert', ['link', 'picture', 'video']],
                                    ['view', ['fullscreen', 'codeview', 'help']]
                                ]
                            });

                            $('#fontSizeSelect').on('change', function() {
                                var val = $(this).val();
                                if (val) {
                                    $('#ProductDetails').summernote('focus');
                                    document.execCommand('fontSize', false, val);
                                    $(this).val('');
                                }
                            });

                            $('#fontColorPicker').on('input', function() {
                                var color = $(this).val();
                                $('#colorLabel').css('border-bottom-color', color);
                                $('#ProductDetails').summernote('focus');
                                document.execCommand('foreColor', false, color);
                            });
                        });

                        function setsubcategory() {
                            var sub_id = $('#category_id').val();
                            $.ajax({
                                type: 'GET',
                                url: '../../get/subcategory/' + sub_id,

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
                                url: '../../get/minicategory/' + sub_id,

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

                {{-- Right Column --}}
                <div class="col-lg-6">
                    {{-- Product Price + Stock --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-currency-dollar me-2"></i>Product Price + Stock
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Wholesale Price <span class="required-star">*</span></label>
                                        <input type="text" id="ProductWholesalePrice" name="ProductWholesalePrice" value="{{ $product->ProductWholesalePrice }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Reseller Price <span class="required-star">*</span></label>
                                        <input type="text" id="ProductResellerPrice" name="ProductResellerPrice" value="{{ $product->ProductResellerPrice }}" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Regular Price <span class="required-star">*</span></label>
                                        <input type="number" id="ProductRegularPrice" name="ProductRegularPrice" value="{{ $product->ProductRegularPrice }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Sell Offer Price <span class="required-star">*</span></label>
                                        <input type="number" id="ProductSalePrice" value="{{ $product->ProductSalePrice }}" name="ProductSalePrice" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Discount Percent (%)</label>
                                        <input type="number" id="Discount" value="{{ $product->Discount }}" name="Discount" class="form-control">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Minimum Selling Price <span class="required-star">*</span></label>
                                        <input type="number" id="min_sell_price" name="min_sell_price" value="{{ $product->min_sell_price }}" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            @if($product->vendor_id)
                            <div class="form-group">
                                <label>Vendor Approval Status</label>
                                <select name="vendor_approval_status" id="vendor_approval_status" class="form-select">
                                    <option value="pending" {{ ($product->vendor_approval_status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ ($product->vendor_approval_status ?? '') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ ($product->vendor_approval_status ?? '') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                                <small class="text-muted">Vendor products need admin approval to appear on the storefront.</small>
                            </div>
                            @endif
                            <div class="form-group">
                                <label>Quantity <span class="required-star">*</span></label>
                                <input type="number" id="qty" name="qty" class="form-control" value="{{ $product->qty }}" required>
                            </div>
                            <div class="form-group">
                                <label>Selling Type <span class="required-star">*</span></label>
                                <select name="selling_type" id="selling_type" class="form-select" required>
                                    <option value="both" {{ ($product->selling_type ?? 'both') == 'both' ? 'selected' : '' }}>Both (Wholesale + Dropshipping)</option>
                                    <option value="wholesale" {{ ($product->selling_type ?? '') == 'wholesale' ? 'selected' : '' }}>Wholesale Only</option>
                                    <option value="dropshipping" {{ ($product->selling_type ?? '') == 'dropshipping' ? 'selected' : '' }}>Dropshipping Only</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Wholesale Price Tiers --}}
                    <div class="admin-content-card" id="priceTiersCard">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-graph-up me-2"></i>Wholesale Price Tiers
                            </h6>
                            <div class="admin-card-actions">
                                <button type="button" class="btn btn-sm btn-primary" onclick="addTierRow()">
                                    <i class="bi bi-plus me-1"></i>Add Tier
                                </button>
                            </div>
                        </div>
                        <div class="admin-card-body">
                            <p class="text-muted mb-3" style="font-size: 13px;">Manage quantity-based pricing tiers for wholesale orders.</p>
                            <div id="tierRows"></div>
                        </div>
                    </div>

                    <script>
                        var tierCount = 0;
                        function addTierRow(data) {
                            data = data || {};
                            tierCount++;
                            var html = '<div class="tier-row mb-3 p-3" style="background:var(--admin-bg, #f8f9fa);border-radius:8px;border:1px solid var(--admin-border, #dee2e6);" id="tierRow' + tierCount + '">' +
                                '<div class="row align-items-end">' +
                                '<div class="col-md-3 mb-2"><label style="font-size:13px">Variant / Label</label>' +
                                '<input type="text" name="tiers[' + tierCount + '][variant_title]" class="form-control form-control-sm" placeholder="e.g. Base, M, L" value="' + (data.variant_title || '') + '"></div>' +
                                '<div class="col-md-2 mb-2"><label style="font-size:13px">Min Qty <span class="required-star">*</span></label>' +
                                '<input type="number" name="tiers[' + tierCount + '][min_qty]" class="form-control form-control-sm" placeholder="1" value="' + (data.min_qty || '') + '" required></div>' +
                                '<div class="col-md-2 mb-2"><label style="font-size:13px">Max Qty</label>' +
                                '<input type="number" name="tiers[' + tierCount + '][max_qty]" class="form-control form-control-sm" placeholder="Optional" value="' + (data.max_qty || '') + '"></div>' +
                                '<div class="col-md-2 mb-2"><label style="font-size:13px">Unit Price <span class="required-star">*</span></label>' +
                                '<input type="number" step="0.01" name="tiers[' + tierCount + '][unit_price]" class="form-control form-control-sm" placeholder="0.00" value="' + (data.unit_price || '') + '" required></div>' +
                                '<div class="col-md-2 mb-2"><label style="font-size:13px">Delivery ৳</label>' +
                                '<input type="number" step="0.01" name="tiers[' + tierCount + '][delivery_charge]" class="form-control form-control-sm" placeholder="0" value="' + (data.delivery_charge || '') + '"></div>' +
                                '<div class="col-md-1 mb-2"><label style="color:transparent;font-size:13px">X</label>' +
                                '<button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="removeTierRow(' + tierCount + ')" title="Remove tier">&times;</button></div>' +
                                '</div></div>';
                            document.getElementById('tierRows').insertAdjacentHTML('beforeend', html);
                        }
                        function removeTierRow(id) { var el = document.getElementById('tierRow' + id); if (el) el.remove(); }
                        function toggleTiersCard() {
                            var type = document.getElementById('selling_type').value;
                            document.getElementById('priceTiersCard').style.display = (type === 'wholesale' || type === 'both') ? 'block' : 'none';
                        }
                        document.getElementById('selling_type').addEventListener('change', toggleTiersCard);

                        // Pre-populate existing tiers on page load
                        document.addEventListener('DOMContentLoaded', function() {
                            toggleTiersCard();
                            @if(isset($product) && $product->priceTiers && $product->priceTiers->count() > 0)
                                @foreach($product->priceTiers as $tier)
                                    addTierRow({
                                        variant_title: '{{ $tier->variant_title ?? '' }}',
                                        min_qty: '{{ $tier->min_qty }}',
                                        max_qty: '{{ $tier->max_qty ?? '' }}',
                                        unit_price: '{{ $tier->unit_price }}',
                                        delivery_charge: '{{ $tier->delivery_charge ?? '' }}'
                                    });
                                @endforeach
                            @endif
                        });
                    </script>

                    {{-- Packing & Shipping --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-truck me-2"></i>Packing & Shipping Config
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Extra Packing Charge</label>
                                        <input type="text" id="ex_pack" name="ex_pack" value="{{ $product->ex_pack }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Extra Delivery Charge</label>
                                        <input type="text" id="ex_dvc" value="{{ $product->ex_dvc }}" name="ex_dvc" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Low Stock Warning --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-exclamation-triangle me-2"></i>Low Stock Quantity Warning
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Quantity <span class="required-star">*</span></label>
                                <input type="text" id="low_stock" value="{{ $product->low_stock }}" name="low_stock" class="form-control">
                            </div>
                        </div>
                    </div>

                    {{-- Stock Visibility --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-eye me-2"></i>Stock Visibility State
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="switch-row">
                                <label>Show Stock Quantity</label>
                                <input type="checkbox" name="show_stock" @if($product->show_stock=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="switch-row">
                                <label>Show Stock Text Only</label>
                                <input type="checkbox" name="show_stock_text" @if($product->show_stock_text=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                        </div>
                    </div>

                    {{-- Activity Status --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-toggle-on me-2"></i>Activity Status
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="switch-row">
                                <label>Any Website</label>
                                <input type="checkbox" name="mart_status" @if($product->mart_status=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="switch-row">
                                <label>Only Reseller</label>
                                <input type="checkbox" name="reseller_status" @if($product->reseller_status=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                        </div>
                    </div>

                    {{-- Reseller Order Bonus --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-gift me-2"></i>Reseller Order Bonus
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="text" name="reseller_bonus" value="{{ $product->reseller_bonus }}" class="form-control">
                            </div>
                        </div>
                    </div>

                    {{-- Show Product List By Status --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-list-check me-2"></i>Show Product List By Status
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="switch-row">
                                <label>Show In New Product List?</label>
                                <input type="checkbox" name="show_new_product" @if($product->show_new_product=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="switch-row">
                                <label>Show In Hot Selling List?</label>
                                <input type="checkbox" name="hot_list" @if($product->hot_list=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="switch-row">
                                <label>Show In Ready To Boost List?</label>
                                <input type="checkbox" name="ready_bost" @if($product->ready_bost=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="switch-row">
                                <label>Show In Profitable Product List?</label>
                                <input type="checkbox" name="profitable" @if($product->profitable=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="switch-row">
                                <label>Show In Limited Offer List?</label>
                                <input type="checkbox" name="limited" @if($product->limited=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="switch-row">
                                <label>Show In Summer Collection List?</label>
                                <input type="checkbox" name="summer" @if($product->summer=='On') checked @endif data-toggle="switchbutton" data-onstyle="success">
                            </div>
                        </div>
                    </div>

                    {{-- Estimate Shipping Time --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">
                                <i class="bi bi-clock me-2"></i>Estimate Shipping Time
                            </h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Shipping Days</label>
                                <input type="text" name="shipping_days" value="{{ $product->shipping_days }}" class="form-control">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="d-flex justify-content-end mt-3 mb-4">
                <a href="{{ url('admin/product') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" name="btn" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i>Save Product
                </button>
            </div>
        </form>
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>


<script>
    var loadFile = function(event) {
        var output = document.getElementById('prevImage');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function() {
            URL.revokeObjectURL(output.src) // free memory
        }
    };
    var galleryloadFile = function(event) {
        var output = document.getElementById('galleryprevImage');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function() {
            URL.revokeObjectURL(output.src) // free memory
        }
    };
</script>

<script>
    var PostImages = [];
    var removedGalleryImages = [];

    function removeExistingGalleryImage(index, imageUrl) {
        document.getElementById('existingImg' + index).style.display = 'none';
        removedGalleryImages.push(imageUrl);
        document.getElementById('removedGalleryImages').value = JSON.stringify(removedGalleryImages);
    }

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
