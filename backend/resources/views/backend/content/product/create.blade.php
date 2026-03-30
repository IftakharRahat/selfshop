@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Products
@endsection

<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/css/bootstrap-switch-button.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/dist/bootstrap-switch-button.min.js"></script>
{{-- summernote --}}
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">

<style>
    /* ---- Product Create Page ---- */
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
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(239,68,68,0.9);
        color: #fff;
        border-radius: 50%;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        margin: 0 !important;
    }

    /* Attribute checkboxes */
    .attr-checkbox-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 4px;
    }
    .attr-checkbox-grid label {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: var(--admin-bg, #f8fafc);
        border: 1px solid var(--admin-border, #e2e8f0);
        border-radius: 6px;
        font-size: 13px;
        font-weight: 400;
        cursor: pointer;
        transition: all 0.15s ease;
        margin-bottom: 0;
    }
    .attr-checkbox-grid label:hover {
        border-color: var(--admin-primary, #2d2a5d);
        background: #f0f0ff;
    }
    .attr-checkbox-grid input[type="checkbox"]:checked + span {
        color: var(--admin-primary, #2d2a5d);
        font-weight: 600;
    }
    .attr-checkbox-grid label:has(input:checked) {
        border-color: var(--admin-primary, #2d2a5d);
        background: var(--admin-primary-lighter, #eef0ff);
    }

    /* Toggle row */
    .toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .toggle-row:last-child {
        border-bottom: none;
    }
    .toggle-row label {
        margin-bottom: 0;
    }

    /* Price tier row */
    .tier-row {
        background: var(--admin-bg, #f8fafc) !important;
        border: 1px solid var(--admin-border, #e2e8f0) !important;
        border-radius: 8px !important;
    }
    .tier-row label {
        color: var(--admin-text, #1e293b) !important;
        font-size: 12px !important;
    }

    /* Submit area */
    .product-submit-area {
        padding: 20px 0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    .product-submit-area .btn-submit-product {
        padding: 10px 40px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        background: var(--admin-primary, #2d2a5d);
        border: none;
        color: #fff;
        transition: all 0.2s ease;
    }
    .product-submit-area .btn-submit-product:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(45, 42, 93, 0.3);
    }

    /* Note/summernote override */
    .note-editor.note-frame {
        border: 1px solid var(--admin-border, #e2e8f0) !important;
        border-radius: 8px !important;
        overflow: hidden;
    }
    .note-editor .note-toolbar {
        background: var(--admin-bg, #f8fafc) !important;
        border-bottom: 1px solid var(--admin-border, #e2e8f0) !important;
    }

    /* DataTable filter override for this page */
    #roleinfo_length, #roleinfo_filter, #roleinfo_info { color: var(--admin-text, #1e293b); }

    /* Search input hide */
    #orderinfo_filter input[type="search"] { display: none; }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="product-create-wrapper">

        {{-- Breadcrumb --}}
        <div class="pagetitle row mb-3">
            <div class="col-12">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('admin/shop/products') }}">Products</a></li>
                        <li class="breadcrumb-item active">Create New Product</li>
                    </ol>
                </nav>
            </div>
        </div>

        <form name="form" id="myForm" action="{{ url('admin/products') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                {{-- ============ LEFT COLUMN ============ --}}
                <div class="col-lg-7">

                    {{-- Product Information --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Product Information</h6>
                        </div>
                        <div class="admin-card-body">
                            @if ($shop=='Yes')
                                <div class="form-group">
                                    <label>Choose Shop <span class="required-star">*</span></label>
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
                            <div class="form-group">
                                <label>Product Name <span class="required-star">*</span></label>
                                <input type="text" name="ProductName" id="ProductName" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Categories <span class="required-star">*</span></label>
                                        <select class="form-control" onchange="setsubcategory()" id="category_id" name="category_id" required>
                                            <option>Select Category</option>
                                            @forelse ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Sub Category</label>
                                        <select name="subcategory_id" id="subcategory_id" onchange="setminicategory()" class="form-control">
                                            <option value="">Choose Sub-Category</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mini Category</label>
                                        <select name="minicategory_id" id="minicategory_id" class="form-control">
                                            <option value="">Choose Mini-Category</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Brand <span class="required-star">*</span></label>
                                        <select class="form-control" id="brand_id" name="brand_id">
                                            <option>Select Brand</option>
                                            @forelse ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Weight (in kg) <span class="required-star">*</span></label>
                                        <input type="text" id="product_weight" name="product_weight" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Minimum Purchase Qty <span class="required-star">*</span></label>
                                        <input type="text" id="minimum_qty" value="1" name="minimum_qty" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Product Images --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Product Images</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Thumbnail Image <span class="required-star">*</span></label>
                                        <div class="file-upload-zone">
                                            <input type="file" name="ProductImage" id="ProductImage" onchange="loadFile(event)">
                                            <div class="upload-icon"><i class="bi bi-image"></i></div>
                                            <div class="upload-text"><strong>Click to upload</strong> thumbnail</div>
                                        </div>
                                        <div class="thumbnail-preview">
                                            <img id="prevImage" style="display:none;" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gallery Images</label>
                                        <div class="file-upload-zone">
                                            <input type="file" onchange="prevPost_Img()" name="PostImage[]" id="PostImage" multiple>
                                            <div class="upload-icon"><i class="bi bi-images"></i></div>
                                            <div class="upload-text"><strong>Click to upload</strong> gallery images</div>
                                        </div>
                                        <div id="prevFile"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Product Video --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Product Video</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>YouTube Embed Code</label>
                                <input type="text" name="youtube_link" id="youtube_link" class="form-control" placeholder="Paste YouTube embed URL here">
                            </div>
                        </div>
                    </div>

                    {{-- Product Attributes --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Product Attributes</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Colours</label>
                                <div class="attr-checkbox-grid">
                                    @forelse ($colors as $color)
                                        <label>
                                            <input type="checkbox" name="color[]" value="{{ $color->value }}">
                                            <span>{{ $color->value }}</span>
                                        </label>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Sizes</label>
                                <div class="attr-checkbox-grid">
                                    @forelse ($sizes as $size)
                                        <label>
                                            <input type="checkbox" name="size[]" value="{{ $size->value }}">
                                            <span>{{ $size->value }}</span>
                                        </label>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Weights</label>
                                <div class="attr-checkbox-grid">
                                    @forelse ($weights as $weight)
                                        <label>
                                            <input type="checkbox" name="weight[]" value="{{ $weight->value }}">
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
                            <h6 class="admin-card-title">Product Description</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Product Description</label>
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
                                <textarea class="form-control" id="ProductDetails" name="ProductDetails" rows="5"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- SEO Meta --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">SEO Meta</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Meta Title</label>
                                <input type="text" name="MetaTitle" id="MetaTitle" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Meta Description</label>
                                <textarea class="form-control" name="MetaDescription" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Keywords</label>
                                <textarea class="form-control" name="MetaKey" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Image</label>
                                <input type="file" name="meta_image" class="form-control">
                            </div>
                        </div>
                    </div>

                    <script type="text/javascript">
                        $(document).ready(function() {
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

                            // Custom font size control
                            $('#fontSizeSelect').on('change', function() {
                                var val = $(this).val();
                                if (val) {
                                    $('#ProductDetails').summernote('focus');
                                    document.execCommand('fontSize', false, val);
                                    $(this).val('');
                                }
                            });

                            // Custom color picker control
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

                {{-- ============ RIGHT COLUMN ============ --}}
                <div class="col-lg-5">

                    {{-- Product Price + Stock --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Price & Stock</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Wholesale Price <span class="required-star">*</span></label>
                                        <input type="text" id="ProductWholesalePrice" name="ProductWholesalePrice" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Reseller Price <span class="required-star">*</span></label>
                                        <input type="text" id="ProductResellerPrice" name="ProductResellerPrice" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Regular Price <span class="required-star">*</span></label>
                                        <input type="number" id="ProductRegularPrice" name="ProductRegularPrice" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Sell Offer Price <span class="required-star">*</span></label>
                                        <input type="number" id="ProductSalePrice" name="ProductSalePrice" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Discount Percent (%)</label>
                                        <input type="number" id="Discount" name="Discount" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Minimum Selling Price <span class="required-star">*</span></label>
                                        <input type="number" id="min_sell_price" name="min_sell_price" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Quantity <span class="required-star">*</span></label>
                                <input type="number" id="qty" name="qty" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Selling Type <span class="required-star">*</span></label>
                                <select name="selling_type" id="selling_type" class="form-control" required>
                                    <option value="both" selected>Both (Wholesale + Dropshipping)</option>
                                    <option value="wholesale">Wholesale Only</option>
                                    <option value="dropshipping">Dropshipping Only</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Wholesale Price Tiers --}}
                    <div class="admin-content-card" id="priceTiersCard">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Wholesale Price Tiers</h6>
                            <div class="admin-card-actions">
                                <button type="button" class="btn btn-sm btn-success" onclick="addTierRow()">+ Add Tier</button>
                            </div>
                        </div>
                        <div class="admin-card-body">
                            <p class="text-muted mb-3" style="font-size: 13px;">Add quantity-based pricing tiers for wholesale orders.</p>
                            <div id="tierRows"></div>
                        </div>
                    </div>

                    <script>
                        var tierCount = 0;
                        function addTierRow(data) {
                            data = data || {};
                            tierCount++;
                            var html = '<div class="tier-row mb-3 p-3" id="tierRow' + tierCount + '">' +
                                '<div class="row align-items-end">' +
                                '<div class="col-md-3 mb-2"><label>Variant / Label</label>' +
                                '<input type="text" name="tiers[' + tierCount + '][variant_title]" class="form-control form-control-sm" placeholder="e.g. Base, M, L" value="' + (data.variant_title || '') + '"></div>' +
                                '<div class="col-md-2 mb-2"><label>Min Qty <span class="required-star">*</span></label>' +
                                '<input type="number" name="tiers[' + tierCount + '][min_qty]" class="form-control form-control-sm" placeholder="1" value="' + (data.min_qty || '') + '" required></div>' +
                                '<div class="col-md-2 mb-2"><label>Max Qty</label>' +
                                '<input type="number" name="tiers[' + tierCount + '][max_qty]" class="form-control form-control-sm" placeholder="Optional" value="' + (data.max_qty || '') + '"></div>' +
                                '<div class="col-md-2 mb-2"><label>Unit Price <span class="required-star">*</span></label>' +
                                '<input type="number" step="0.01" name="tiers[' + tierCount + '][unit_price]" class="form-control form-control-sm" placeholder="0.00" value="' + (data.unit_price || '') + '" required></div>' +
                                '<div class="col-md-2 mb-2"><label>Delivery ৳</label>' +
                                '<input type="number" step="0.01" name="tiers[' + tierCount + '][delivery_charge]" class="form-control form-control-sm" placeholder="0" value="' + (data.delivery_charge || '') + '"></div>' +
                                '<div class="col-md-1 mb-2"><label style="color:transparent">X</label>' +
                                '<button type="button" class="btn btn-danger btn-sm btn-block" onclick="removeTierRow(' + tierCount + ')" title="Remove tier">&times;</button></div>' +
                                '</div></div>';
                            document.getElementById('tierRows').insertAdjacentHTML('beforeend', html);
                        }
                        function removeTierRow(id) { var el = document.getElementById('tierRow' + id); if (el) el.remove(); }
                        function toggleTiersCard() {
                            var type = document.getElementById('selling_type').value;
                            document.getElementById('priceTiersCard').style.display = (type === 'wholesale' || type === 'both') ? 'block' : 'none';
                        }
                        document.getElementById('selling_type').addEventListener('change', toggleTiersCard);
                        document.addEventListener('DOMContentLoaded', toggleTiersCard);
                    </script>

                    {{-- Packing & Shipping --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Packing & Shipping</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Extra Packing Charge</label>
                                        <input type="text" id="ex_pack" name="ex_pack" class="form-control" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Extra Delivery Charge</label>
                                        <input type="text" id="ex_dvc" name="ex_dvc" class="form-control" placeholder="0">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Low Stock Warning Quantity</label>
                                <input type="text" id="low_stock" name="low_stock" class="form-control" placeholder="Enter low stock threshold">
                            </div>
                            <div class="form-group">
                                <label>Estimated Shipping Days</label>
                                <input type="text" name="shipping_days" class="form-control" placeholder="e.g. 3-5">
                            </div>
                        </div>
                    </div>

                    {{-- Visibility & Status --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Visibility & Status</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="toggle-row">
                                <label>Show Stock Quantity</label>
                                <input type="checkbox" name="show_stock" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Show Stock Text Only</label>
                                <input type="checkbox" name="show_stock_text" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Any Web</label>
                                <input type="checkbox" name="mart_status" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Only Reseller</label>
                                <input type="checkbox" name="reseller_status" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                        </div>
                    </div>

                    {{-- Reseller Bonus --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Reseller Order Bonus</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="text" name="reseller_bonus" class="form-control" placeholder="0">
                            </div>
                        </div>
                    </div>

                    {{-- Product Lists --}}
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <h6 class="admin-card-title">Featured Lists</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="toggle-row">
                                <label>Show In New Product List</label>
                                <input type="checkbox" name="show_new_product" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Show In Hot Selling List</label>
                                <input type="checkbox" name="hot_list" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Show In Ready To Boost List</label>
                                <input type="checkbox" name="ready_bost" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Show In Profitable Product List</label>
                                <input type="checkbox" name="profitable" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Show In Limited Offer List</label>
                                <input type="checkbox" name="limited" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                            <div class="toggle-row">
                                <label>Show In Summer Collection List</label>
                                <input type="checkbox" name="summer" data-toggle="switchbutton" data-onstyle="success">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="product-submit-area">
                <button type="submit" name="btn" class="btn btn-submit-product from-prevent-multiple-submits">
                    <i class="bi bi-check-lg me-1"></i> Save Product
                </button>
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
        output.style.display = 'block';
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
