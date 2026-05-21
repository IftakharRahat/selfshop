@extends('backend.master')
@section('maincontent')
@section('title'){{ env('APP_NAME') }}- Edit Product @endsection

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/css/bootstrap-switch-button.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/dist/bootstrap-switch-button.min.js"></script>

<style>
body{background:#f3f4f6}
.sp-wrap{max-width:1200px;margin:0 auto;padding-bottom:50px}
.sp-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:16px}
.sp-label{font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;display:block}
.sp-input{width:100%;padding:8px 12px;font-size:14px;border:1px solid #d1d5db;border-radius:8px;outline:none;transition:border .2s}
.sp-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.sp-select{width:100%;padding:8px 12px;font-size:14px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
.sp-btn{padding:8px 20px;border-radius:8px;font-size:14px;font-weight:600;border:none;cursor:pointer;transition:all .2s}
.sp-btn-primary{background:#2d2a5d;color:#fff}.sp-btn-primary:hover{background:#252947}
.sp-btn-indigo{background:#4f46e5;color:#fff}.sp-btn-indigo:hover{background:#4338ca}
.sp-btn-danger{background:#fee2e2;color:#dc2626;font-size:12px;padding:4px 8px}.sp-btn-danger:hover{background:#fecaca}
.selling-card{display:flex;align-items:center;gap:12px;border:2px solid #e5e7eb;border-radius:12px;padding:14px;cursor:pointer;transition:all .2s}
.selling-card:hover{border-color:#9ca3af}
.selling-card.active-wholesale{border-color:#22c55e;background:#f0fdf4}
.selling-card.active-dropshipping{border-color:#3b82f6;background:#eff6ff}
.selling-card.active-both{border-color:#f59e0b;background:#fffbeb}
.selling-card input[type=radio]{width:16px;height:16px}
.variant-section{background:rgba(238,242,255,.3);border:1px solid #c7d2fe;border-radius:12px;padding:20px;margin-top:16px}
.variant-item{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:12px;position:relative}
.size-item{background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:10px;margin-bottom:8px}
.bulk-section{padding-left:16px;border-left:2px solid #c7d2fe;margin-top:8px}
.bulk-row{display:flex;align-items:center;gap:8px;font-size:12px;background:#fff;padding:6px 8px;border-radius:6px;border:1px solid #eef2ff;margin-bottom:4px}
.switch-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f3f4f6}
.switch-row:last-child{border:none}
.file-zone{border:2px dashed #d1d5db;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:border .2s}
.file-zone:hover{border-color:#6366f1}
</style>

@php
    $currentSelling = $product->selling_type ?? 'both';
@endphp

<div class="container-fluid pt-4 px-4">
<div class="sp-wrap">
    <div class="pagetitle mb-3">
        <nav><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{url('/admindashboard')}}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ url('admin/products') }}">Products</a></li>
            <li class="breadcrumb-item active">Edit Product</li>
        </ol></nav>
    </div>

    <div class="sp-card"><h5 style="font-weight:700;margin:0">Edit product: {{ $product->ProductName }}</h5><p class="text-muted mb-0" style="font-size:13px">Update the product details below.</p></div>

    <form action="{{ url('admin/product/'.$product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            {{-- LEFT COLUMN --}}
            <div class="col-lg-8">
                <div class="sp-card">
                    {{-- Selling Type Cards --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="selling-card{{ $currentSelling=='wholesale'?' active-wholesale':'' }}" id="st-wholesale" onclick="setSellingType('wholesale')">
                                <input type="radio" name="selling_type_radio" value="wholesale" {{ $currentSelling=='wholesale'?'checked':'' }}>
                                <div><strong style="font-size:14px">🏭 Wholesale</strong><br><span style="font-size:11px;color:#6b7280">Bulk pricing tiers</span></div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="selling-card{{ $currentSelling=='dropshipping'?' active-dropshipping':'' }}" id="st-dropshipping" onclick="setSellingType('dropshipping')">
                                <input type="radio" name="selling_type_radio" value="dropshipping" {{ $currentSelling=='dropshipping'?'checked':'' }}>
                                <div><strong style="font-size:14px">🚀 Dropshipping</strong><br><span style="font-size:11px;color:#6b7280">Single price & stock</span></div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="selling-card{{ $currentSelling=='both'?' active-both':'' }}" id="st-both" onclick="setSellingType('both')">
                                <input type="radio" name="selling_type_radio" value="both" {{ $currentSelling=='both'?'checked':'' }}>
                                <div><strong style="font-size:14px">🔄 Both</strong><br><span style="font-size:11px;color:#6b7280">Wholesale + Dropship</span></div>
                            </label>
                        </div>
                    </div>
                    <input type="hidden" name="selling_type" id="selling_type" value="{{ $currentSelling }}">

                    {{-- Product Name --}}
                    <div class="mb-3">
                        <label class="sp-label">Product name *</label>
                        <input type="text" name="ProductName" class="sp-input" value="{{ $product->ProductName }}" required>
                    </div>

                    {{-- Unit / Weight / Min Qty / Tags --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><label class="sp-label">Unit (e.g. Pc, Kg)</label><input type="text" name="unit" class="sp-input" value="{{ $product->weight }}" placeholder="Pc"></div>
                        <div class="col-md-3"><label class="sp-label">Weight (kg)</label><input type="number" name="product_weight" class="sp-input" value="{{ $product->product_weight ?? 0 }}" min="0" step="0.01"></div>
                        <div class="col-md-3"><label class="sp-label">Minimum purchase qty</label><input type="number" name="minimum_qty" class="sp-input" value="{{ $product->minimum_qty ?? 1 }}" min="1"></div>
                        <div class="col-md-3"><label class="sp-label">Tags (comma separated)</label><input type="text" name="MetaKey" class="sp-input" value="{{ $product->MetaKey }}" placeholder="tag1, tag2"></div>
                    </div>
                </div>

                {{-- Price & Stock (conditional - matches supplier) --}}
                <div class="sp-card" id="priceStockSection" style="{{ $currentSelling=='wholesale'?'display:none':'' }}">
                    <h6 style="font-weight:700;font-size:14px">Product price & stock</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><label class="sp-label">Base price (reseller)</label><input type="number" name="ProductResellerPrice" class="sp-input" min="0" step="any" value="{{ $product->ProductResellerPrice }}"></div>
                        <div class="col-md-6"><label class="sp-label">Regular price (MSRP)</label><input type="number" name="ProductRegularPrice" class="sp-input" min="0" step="any" value="{{ $product->ProductRegularPrice }}" placeholder="Manual entry (optional)"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><label class="sp-label">Quantity</label><input type="number" name="qty" class="sp-input" min="0" value="{{ $product->qty }}"></div>
                        <div class="col-md-6"><label class="sp-label">Low stock warning at</label><input type="number" name="low_stock" class="sp-input" min="0" value="{{ $product->low_stock }}"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><label class="sp-label">SKU</label><input type="text" name="ProductSku" class="sp-input" value="{{ $product->ProductSku }}"></div>
                        <div class="col-md-6"><label class="sp-label">Discount</label><input type="number" name="Discount" class="sp-input" min="0" step="0.01" value="{{ $product->Discount }}"></div>
                    </div>
                    <p class="sp-label mt-2 mb-1">Stock visibility</p>
                    <div class="d-flex gap-3" style="font-size:13px">
                        <label class="d-inline-flex align-items-center gap-1"><input type="radio" name="stock_visibility" value="quantity" {{ $product->show_stock=='On'?'checked':'' }}> Show stock quantity</label>
                        <label class="d-inline-flex align-items-center gap-1"><input type="radio" name="stock_visibility" value="text" {{ $product->show_stock_text=='On'?'checked':'' }}> Show stock text only</label>
                        <label class="d-inline-flex align-items-center gap-1"><input type="radio" name="stock_visibility" value="hide" {{ $product->show_stock!='On'&&$product->show_stock_text!='On'?'checked':'' }}> Hide stock</label>
                    </div>
                </div>
                <input type="hidden" name="ProductSalePrice" value="{{ $product->ProductSalePrice }}">
                <input type="hidden" name="ProductWholesalePrice" value="{{ $product->ProductWholesalePrice }}">
                <input type="hidden" name="min_sell_price" value="{{ $product->min_sell_price }}">

                {{-- Product Description --}}
                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">Product description</h6>
                    <label class="sp-label">Description</label>
                    <textarea class="form-control" id="ProductDetails" name="ProductDetails" rows="5">{!! $product->ProductDetails !!}</textarea>
                </div>

                {{-- VARIANT BUILDER (AJAX-driven, loads saved variants) --}}
                <div class="variant-section">
                    <h6 style="font-weight:700;font-size:14px;color:#312e81">🎨 Product Variants (Colors & Sizes)</h6>
                    <p style="font-size:12px;color:#4338ca">Variants are saved instantly via AJAX. Add, remove colors and sizes below.</p>
                    <div class="sp-card d-flex flex-wrap gap-3 align-items-end">
                        <div style="flex:1;min-width:140px"><label class="sp-label">Color Name *</label><input type="text" id="newColorName" class="sp-input" placeholder="e.g. Red"></div>
                        <div style="width:70px"><label class="sp-label">Color</label><input type="color" id="newColorCode" value="#000000" style="width:100%;height:36px;border:1px solid #d1d5db;border-radius:6px;cursor:pointer"></div>
                        <div style="flex:1;min-width:160px"><label class="sp-label">Variant Title (Optional)</label><input type="text" id="newColorTitle" class="sp-input" placeholder="defaults to color name"></div>
                        <div style="flex:1;min-width:160px"><label class="sp-label">Color Image (Optional)</label><input type="file" id="newColorImage" class="sp-input" accept="image/*"></div>
                        <div><button type="button" class="sp-btn sp-btn-indigo" onclick="addVariantAjax()">Add Color</button></div>
                    </div>
                    <div id="variantsList" class="mt-3"><div class="text-center py-3 text-muted">Loading variants...</div></div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-lg-4">
                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">Product category</h6>
                    <div class="mb-2"><label class="sp-label">Category *</label>
                        <select name="category_id" id="category_id" class="sp-select" required onchange="loadSubcategories()">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)<option value="{{$cat->id}}" {{ $product->category_id==$cat->id?'selected':'' }}>{{$cat->category_name}}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="sp-label">Subcategory</label>
                        <select name="subcategory_id" id="subcategory_id" class="sp-select" onchange="loadMinicategories()"><option value="">Select subcategory</option></select>
                    </div>
                    <div class="mb-2"><label class="sp-label">Child category</label>
                        <select name="minicategory_id" id="minicategory_id" class="sp-select"><option value="">Select subcategory first</option></select>
                    </div>
                    <div class="mb-2"><label class="sp-label">Brand</label>
                        <select name="brand_id" class="sp-select">
                            <option value="">Select brand</option>
                            @foreach($brands as $b)<option value="{{$b->id}}" {{ $product->brand_id==$b->id?'selected':'' }}>{{$b->brand_name}}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">Product Images</h6>
                    <input type="hidden" name="removed_gallery_images" id="removed_gallery_images" value="">
                    <input type="hidden" name="remove_thumbnail" id="remove_thumbnail" value="0">

                    {{-- Thumbnail --}}
                    <label class="sp-label">Thumbnail image</label>
                    <div id="thumbExisting" class="mb-2">
                        @if($product->ViewProductImage)
                            @php
                                $thumbImg = $product->ViewProductImage;
                                if(Str::startsWith($thumbImg, 'http')) { $thumbUrl = $thumbImg; }
                                elseif(Str::startsWith($thumbImg, 'images/')) { $thumbUrl = asset($thumbImg); }
                                else { $thumbUrl = asset('storage/'.$thumbImg); }
                            @endphp
                            <div style="position:relative;display:inline-block" id="existingThumbWrap">
                                <img src="{{ $thumbUrl }}" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                                <button type="button" onclick="removeExistingThumb()" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:12px;line-height:1;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.2)">✕</button>
                            </div>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px">{{ basename($thumbImg) }}</div>
                        @endif
                    </div>
                    <div id="thumbPreview" class="mb-2"></div>
                    <div class="file-zone mb-3" onclick="document.getElementById('ProductImage').click()" id="thumbDropzone">
                        <input type="file" name="ProductImage" id="ProductImage" style="display:none" accept="image/*" onchange="previewThumb()">
                        <div>📷 Upload new thumbnail</div><small class="text-muted">Max 5MB</small>
                    </div>

                    {{-- Gallery --}}
                    <label class="sp-label">New gallery images <small class="text-muted">(optional, replaces existing)</small></label>
                    <div class="file-zone mb-2" onclick="document.getElementById('PostImage').click()" id="galleryDropzone" style="border:2px dashed #d1d5db;border-radius:10px;padding:20px;text-align:center;cursor:pointer">
                        <input type="file" name="PostImage[]" id="PostImage" multiple style="display:none" accept="image/*" onchange="previewGallery()">
                        <div style="font-size:20px;color:#9ca3af;margin-bottom:4px">☁</div>
                        <div>Click or drag images to upload</div>
                        <small class="text-muted">Max 5MB per file · Multiple files supported</small>
                    </div>
                    <div id="galleryPreview" class="d-flex flex-wrap gap-1 mb-2"></div>

                    {{-- Existing gallery images with red cross --}}
                    @if($product->PostImage)
                        <div id="existingGallery" class="d-flex flex-wrap gap-1 mb-2">
                            @foreach(json_decode($product->PostImage, true) ?? [] as $idx => $img)
                                @php
                                    if(Str::startsWith($img, 'http')) { $imgUrl = $img; }
                                    elseif(Str::startsWith($img, 'images/')) { $imgUrl = asset($img); }
                                    else { $imgUrl = asset('storage/'.$img); }
                                @endphp
                                <div style="position:relative;display:inline-block" id="gallery-item-{{ $idx }}">
                                    <img src="{{ $imgUrl }}" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">
                                    <button type="button" onclick="removeExistingGallery({{ $idx }}, '{{ addslashes($img) }}')" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:11px;line-height:1;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.2)">✕</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="sp-card">
                    <label class="sp-label">YouTube Embed Code</label>
                    <input type="text" name="youtube_link" class="sp-input" value="{{ $product->youtube_link }}" placeholder="Paste YouTube embed URL here">
                </div>

                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">⚙️ Admin Settings</h6>
                    <p class="text-muted" style="font-size:11px">Admin-only controls.</p>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="sp-label">Extra Packing ৳</label><input type="number" name="ex_pack" class="sp-input" value="{{ $product->ex_pack }}"></div>
                        <div class="col-6"><label class="sp-label">Extra Delivery ৳</label><input type="number" name="ex_dvc" class="sp-input" value="{{ $product->ex_dvc }}"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-12"><label class="sp-label">Extra Delivery Per Item ৳ <small class="text-muted">(charged for each additional unit beyond the first)</small></label><input type="number" name="extra_delivery_per_qty" class="sp-input" value="{{ $product->extra_delivery_per_qty ?? 0 }}" min="0" step="0.01"></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="sp-label">Reseller Bonus ৳</label><input type="number" name="reseller_bonus" class="sp-input" value="{{ $product->reseller_bonus }}"></div>
                        <div class="col-6"><label class="sp-label">Shipping Days</label><input type="text" name="shipping_days" class="sp-input" value="{{ $product->shipping_days }}"></div>
                    </div>
                </div>

                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">🔍 SEO Meta</h6>
                    <div class="mb-2"><label class="sp-label">Meta Title</label><input type="text" name="MetaTitle" class="sp-input" value="{{ $product->MetaTitle }}"></div>
                    <div class="mb-2"><label class="sp-label">Meta Description</label><textarea name="MetaDescription" class="sp-input" rows="2">{{ $product->MetaDescription }}</textarea></div>
                    <div><label class="sp-label">Meta Image</label><input type="file" name="meta_image" class="sp-input"></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3 mb-4">
            <a href="{{ url('admin/products') }}" class="sp-btn" style="background:#e5e7eb;color:#374151;margin-right:8px">Cancel</a>
            <button type="submit" class="sp-btn sp-btn-primary">Update Product</button>
        </div>
    </form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
var productId = {{ $product->id }};
var sellingType = '{{ $currentSelling }}';

// ─── Image Preview & Remove ───
var removedGalleryImages = [];

function previewThumb() {
    var f = document.getElementById('ProductImage').files[0];
    if(f) {
        // Hide existing thumbnail
        var ex = document.getElementById('existingThumbWrap');
        if(ex) ex.style.display = 'none';
        document.getElementById('thumbPreview').innerHTML = '<div style="position:relative;display:inline-block"><img src="'+URL.createObjectURL(f)+'" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb"><button type="button" onclick="cancelThumb()" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:12px;line-height:1;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.2)">✕</button></div>';
    }
}
function cancelThumb() {
    document.getElementById('ProductImage').value = '';
    document.getElementById('thumbPreview').innerHTML = '';
    var ex = document.getElementById('existingThumbWrap');
    if(ex) ex.style.display = 'inline-block';
}
function removeExistingThumb() {
    document.getElementById('existingThumbWrap').style.display = 'none';
    document.getElementById('remove_thumbnail').value = '1';
}

function previewGallery() {
    var files = document.getElementById('PostImage').files;
    var html = '';
    for(var i=0;i<files.length;i++) {
        html += '<div style="position:relative;display:inline-block"><img src="'+URL.createObjectURL(files[i])+'" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb"><button type="button" onclick="cancelGallery()" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:11px;line-height:1;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.2)">✕</button></div>';
    }
    document.getElementById('galleryPreview').innerHTML = html;
}
function cancelGallery() {
    document.getElementById('PostImage').value = '';
    document.getElementById('galleryPreview').innerHTML = '';
}
function removeExistingGallery(idx, imgPath) {
    document.getElementById('gallery-item-' + idx).remove();
    removedGalleryImages.push(imgPath);
    document.getElementById('removed_gallery_images').value = JSON.stringify(removedGalleryImages);
}

$(document).ready(function(){
    $('#ProductDetails').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
    loadSubcategoriesInit();
    loadVariants();
});

// ─── Selling Type ───
function setSellingType(type) {
    sellingType = type;
    document.getElementById('selling_type').value = type;
    document.querySelectorAll('.selling-card').forEach(function(el){ el.className = 'selling-card'; });
    var el = document.getElementById('st-' + type);
    if(type==='wholesale') el.classList.add('active-wholesale');
    else if(type==='dropshipping') el.classList.add('active-dropshipping');
    else el.classList.add('active-both');
    el.querySelector('input[type=radio]').checked = true;
    document.getElementById('priceStockSection').style.display = (type==='wholesale') ? 'none' : 'block';
}

// ─── Category cascading ───
function loadSubcategoriesInit() {
    var catId = '{{ $product->category_id }}';
    var savedSub = '{{ $product->subcategory_id }}';
    var savedMini = '{{ $product->minicategory_id }}';
    if(!catId) return;
    $.get('/admin/get/subcategory/' + catId, function(data){
        var sub = document.getElementById('subcategory_id');
        sub.innerHTML = '<option value="">Select subcategory</option>';
        data.forEach(function(s){ sub.innerHTML += '<option value="'+s.id+'" '+(s.id==savedSub?'selected':'')+'>'+s.sub_category_name+'</option>'; });
        if(savedSub) {
            $.get('/admin/get/minicategory/' + savedSub, function(data2){
                var mini = document.getElementById('minicategory_id');
                mini.innerHTML = '<option value="">Select child category</option>';
                data2.forEach(function(m){ mini.innerHTML += '<option value="'+m.id+'" '+(m.id==savedMini?'selected':'')+'>'+m.mini_category_name+'</option>'; });
            });
        }
    });
}
function loadSubcategories() {
    var catId = document.getElementById('category_id').value;
    var sub = document.getElementById('subcategory_id');
    sub.innerHTML = '<option value="">Loading...</option>';
    document.getElementById('minicategory_id').innerHTML = '<option value="">Select subcategory first</option>';
    if(!catId){sub.innerHTML='<option value="">Select subcategory</option>';return;}
    $.get('/admin/get/subcategory/' + catId, function(data){
        sub.innerHTML = '<option value="">Select subcategory</option>';
        data.forEach(function(s){ sub.innerHTML += '<option value="'+s.id+'">'+s.sub_category_name+'</option>'; });
    });
}
function loadMinicategories() {
    var subId = document.getElementById('subcategory_id').value;
    var mini = document.getElementById('minicategory_id');
    if(!subId){mini.innerHTML='<option value="">Select subcategory first</option>';return;}
    $.get('/admin/get/minicategory/' + subId, function(data){
        mini.innerHTML = '<option value="">Select child category</option>';
        data.forEach(function(m){ mini.innerHTML += '<option value="'+m.id+'">'+m.mini_category_name+'</option>'; });
    });
}

// ─── Variant AJAX CRUD ───
function loadVariants() {
    $.get('/admin/products/'+productId+'/variants-json', function(res){
        if(res.status) renderVariantsFromServer(res.data.variants);
        else document.getElementById('variantsList').innerHTML = '<p class="text-muted">No variants found.</p>';
    }).fail(function(){ document.getElementById('variantsList').innerHTML = '<p class="text-danger">Failed to load variants.</p>'; });
}

function addVariantAjax() {
    var name = document.getElementById('newColorName').value.trim();
    if(!name){ alert('Color name is required'); return; }
    var fd = new FormData();
    fd.append('title', document.getElementById('newColorTitle').value.trim() || name);
    fd.append('color_name', name);
    fd.append('color_code', document.getElementById('newColorCode').value);
    fd.append('qty', '0');
    fd.append('price', '0');
    var imgFile = document.getElementById('newColorImage').files[0];
    if(imgFile) fd.append('image', imgFile);
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json',
        method: 'POST', data: fd, processData: false, contentType: false,
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(){ document.getElementById('newColorName').value=''; document.getElementById('newColorTitle').value=''; document.getElementById('newColorImage').value=''; loadVariants(); },
        error: function(xhr){ alert(xhr.responseJSON?.message || 'Failed to add variant'); }
    });
}

function deleteVariant(variantId) {
    if(!confirm('Delete this color variant and all its sizes?')) return;
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json/'+variantId,
        method: 'DELETE', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(){ loadVariants(); },
        error: function(){ alert('Failed to delete variant'); }
    });
}

function addSizeAjax(variantId) {
    var n = document.getElementById('sz_name_'+variantId).value.trim();
    var p = document.getElementById('sz_price_'+variantId).value;
    var q = document.getElementById('sz_qty_'+variantId).value || '0';
    if(!n||!p){ alert('Size name and price required'); return; }
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json/'+variantId+'/sizes',
        method: 'POST', contentType: 'application/json',
        data: JSON.stringify({size_name:n, price:parseFloat(p), qty:parseInt(q), status:'Active'}),
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(){ loadVariants(); },
        error: function(xhr){ alert(xhr.responseJSON?.message || 'Failed to add size'); }
    });
}

function deleteSizeAjax(variantId, sizeId) {
    if(!confirm('Delete this size?')) return;
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json/'+variantId+'/sizes/'+sizeId,
        method: 'DELETE', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(){ loadVariants(); },
        error: function(){ alert('Failed to delete size'); }
    });
}

function addBulkAjax(variantId, sizeId) {
    var mn = document.getElementById('bt_min_'+sizeId).value;
    var mx = document.getElementById('bt_max_'+sizeId).value;
    var pr = document.getElementById('bt_price_'+sizeId).value;
    if(!mn||!pr){ alert('Min qty and bulk price required'); return; }
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json/'+variantId+'/sizes/'+sizeId+'/bulk-prices',
        method: 'POST', contentType: 'application/json',
        data: JSON.stringify({min_qty:parseInt(mn), max_qty:mx?parseInt(mx):null, bulk_price:parseFloat(pr)}),
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(){ loadVariants(); },
        error: function(){ alert('Failed to add bulk price'); }
    });
}

function deleteBulkAjax(variantId, sizeId, bulkId) {
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json/'+variantId+'/sizes/'+sizeId+'/bulk-prices/'+bulkId,
        method: 'DELETE', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(){ loadVariants(); },
        error: function(){ alert('Failed to delete bulk price'); }
    });
}

function renderVariantsFromServer(variants) {
    if(!variants || variants.length === 0) {
        document.getElementById('variantsList').innerHTML = '<p class="text-muted text-center py-3">No variants added yet. Add a color variant above.</p>';
        return;
    }
    var html = '';
    variants.forEach(function(v){
        html += '<div class="variant-item">';
        html += '<button type="button" class="sp-btn-danger" style="position:absolute;top:10px;right:10px" onclick="deleteVariant('+v.id+')">🗑 Remove</button>';
        html += '<div class="d-flex gap-3 align-items-start" style="padding-right:80px">';
        if(v.image) html += '<img src="/storage/'+v.image+'" style="width:56px;height:56px;border-radius:8px;object-fit:cover;border:1px solid #e5e7eb">';
        else if(v.color_code) html += '<div style="width:56px;height:56px;border-radius:50%;background:'+v.color_code+';border:1px solid #d1d5db"></div>';
        else html += '<div style="width:56px;height:56px;border-radius:50%;background:#ccc;border:1px solid #d1d5db"></div>';
        html += '<div style="flex:1">';
        html += '<h6 style="font-weight:700;margin:0">'+(v.color_name||v.title||'Variant')+' <span style="font-weight:400;color:#6b7280;font-size:13px">('+(v.title||'no title')+')</span></h6>';
        html += '<p style="font-size:11px;color:#6b7280;margin:4px 0 12px">Sizes & bulk pricing for this variant.</p>';

        // Render sizes
        var sizes = v.sizes || [];
        sizes.forEach(function(sz){
            html += '<div class="size-item">';
            html += '<div class="d-flex align-items-center gap-3 mb-2">';
            html += '<div style="flex:1"><small style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase">Size</small><div style="font-weight:600">'+sz.size_name+'</div></div>';
            html += '<div style="flex:1"><small style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase">Price</small><div style="font-weight:600;color:#4f46e5">৳'+Number(sz.price||0).toLocaleString()+'</div></div>';
            html += '<div style="flex:1"><small style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase">Qty</small><div style="font-weight:600">'+sz.qty+'</div></div>';
            html += '<button type="button" class="sp-btn-danger" onclick="deleteSizeAjax('+v.id+','+sz.id+')">✕</button>';
            html += '</div>';
            // Bulk tiers
            html += '<div class="bulk-section">';
            html += '<small style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Bulk Pricing Tiers</small>';
            var bulks = sz.bulk_prices || [];
            bulks.forEach(function(bt){
                html += '<div class="bulk-row"><span style="flex:1;font-weight:500">Qty: '+bt.min_qty+' - '+(bt.max_qty||'∞')+'</span>';
                html += '<span style="font-weight:700;color:#4f46e5">৳'+Number(bt.bulk_price).toLocaleString()+'</span>';
                html += '<button type="button" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:14px" onclick="deleteBulkAjax('+v.id+','+sz.id+','+bt.id+')">✕</button></div>';
            });
            html += '<div class="d-flex gap-1 align-items-end mt-1">';
            html += '<input id="bt_min_'+sz.id+'" type="number" placeholder="Min" style="width:60px;font-size:11px;padding:4px;border:1px solid #d1d5db;border-radius:4px">';
            html += '<input id="bt_max_'+sz.id+'" type="number" placeholder="Max" style="width:60px;font-size:11px;padding:4px;border:1px solid #d1d5db;border-radius:4px">';
            html += '<input id="bt_price_'+sz.id+'" type="number" step="0.01" placeholder="Price" style="width:70px;font-size:11px;padding:4px;border:1px solid #d1d5db;border-radius:4px">';
            html += '<button type="button" class="sp-btn sp-btn-indigo" style="font-size:10px;padding:4px 8px" onclick="addBulkAjax('+v.id+','+sz.id+')">Add Tier</button>';
            html += '</div></div></div>';
        });

        // Add size form
        html += '<div style="background:rgba(238,242,255,.5);padding:10px;border-radius:8px;border:1px solid #c7d2fe;margin-top:8px">';
        html += '<div class="mb-1"><label style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Size Name</label><input id="sz_name_'+v.id+'" type="text" class="sp-input" placeholder="e.g. S, 40, Free" style="font-size:12px;padding:6px 8px"></div>';
        html += '<div class="d-flex gap-2 align-items-end">';
        html += '<div style="flex:1"><label style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Price</label><input id="sz_price_'+v.id+'" type="number" step="0.01" class="sp-input" placeholder="Price" style="font-size:12px;padding:6px 8px"></div>';
        html += '<div style="flex:1"><label style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Qty</label><input id="sz_qty_'+v.id+'" type="number" class="sp-input" placeholder="Qty" value="0" style="font-size:12px;padding:6px 8px"></div>';
        html += '<button type="button" class="sp-btn sp-btn-indigo" style="font-size:12px;white-space:nowrap" onclick="addSizeAjax('+v.id+')">Add Size</button>';
        html += '</div></div>';

        html += '</div></div></div>';
    });
    document.getElementById('variantsList').innerHTML = html;
}
</script>
@endsection
