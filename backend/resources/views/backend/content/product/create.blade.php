@extends('backend.master')
@section('maincontent')
@section('title'){{ env('APP_NAME') }}- Products @endsection

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

<div class="container-fluid pt-4 px-4">
<div class="sp-wrap">
    <div class="pagetitle mb-3">
        <nav><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{url('/admindashboard')}}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ url('admin/products') }}">Products</a></li>
            <li class="breadcrumb-item active">Add New Product</li>
        </ol></nav>
    </div>

    <div class="sp-card"><h5 style="font-weight:700;margin:0">Add new product</h5><p class="text-muted mb-0" style="font-size:13px">Fill in the required fields to create a product.</p></div>

    <form id="productForm" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            {{-- LEFT COLUMN --}}
            <div class="col-lg-8">
                <div class="sp-card">
                    {{-- Selling Type Cards --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="selling-card active-wholesale" id="st-wholesale" onclick="setSellingType('wholesale')">
                                <input type="radio" name="selling_type_radio" value="wholesale" checked>
                                <div><strong style="font-size:14px">🏭 Wholesale</strong><br><span style="font-size:11px;color:#6b7280">Bulk pricing tiers</span></div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="selling-card" id="st-dropshipping" onclick="setSellingType('dropshipping')">
                                <input type="radio" name="selling_type_radio" value="dropshipping">
                                <div><strong style="font-size:14px">🚀 Dropshipping</strong><br><span style="font-size:11px;color:#6b7280">Single price & stock</span></div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="selling-card" id="st-both" onclick="setSellingType('both')">
                                <input type="radio" name="selling_type_radio" value="both">
                                <div><strong style="font-size:14px">🔄 Both</strong><br><span style="font-size:11px;color:#6b7280">Wholesale + Dropship</span></div>
                            </label>
                        </div>
                    </div>
                    <input type="hidden" name="selling_type" id="selling_type" value="wholesale">

                    {{-- Product Name --}}
                    <div class="mb-3">
                        <label class="sp-label">Product name *</label>
                        <input type="text" name="ProductName" class="sp-input" required>
                    </div>

                    {{-- Unit / Weight / Min Qty / Tags --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><label class="sp-label">Unit (e.g. Pc, Kg)</label><input type="text" name="unit" class="sp-input" placeholder="Pc"></div>
                        <div class="col-md-3"><label class="sp-label">Weight (kg)</label><input type="number" name="product_weight" class="sp-input" value="0" min="0" step="0.01"></div>
                        <div class="col-md-3"><label class="sp-label">Minimum purchase qty</label><input type="number" name="minimum_qty" class="sp-input" value="1" min="1"></div>
                        <div class="col-md-3"><label class="sp-label">Tags (comma separated)</label><input type="text" name="MetaKey" class="sp-input" placeholder="tag1, tag2"></div>
                    </div>
                </div>

                {{-- Price & Stock (conditional - matches supplier layout exactly) --}}
                <div class="sp-card" id="priceStockSection" style="display:none">
                    <h6 style="font-weight:700;font-size:14px">Product price & stock</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><label class="sp-label">Base price (reseller)</label><input type="number" name="ProductResellerPrice" class="sp-input" min="0" step="any" value="0"></div>
                        <div class="col-md-6"><label class="sp-label">Regular price (MSRP)</label><input type="number" name="ProductRegularPrice" class="sp-input" min="0" step="any" placeholder="Manual entry (optional)"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><label class="sp-label">Quantity</label><input type="number" name="qty" class="sp-input" min="0" value="0"></div>
                        <div class="col-md-6"><label class="sp-label">Low stock warning at</label><input type="number" name="low_stock" class="sp-input" min="0" value="0"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><label class="sp-label">SKU</label><input type="text" name="ProductSku" class="sp-input" placeholder="Auto-generated"></div>
                        <div class="col-md-6"><label class="sp-label">Discount</label><input type="number" name="Discount" class="sp-input" min="0" step="0.01" value="0"></div>
                    </div>
                    <p class="sp-label mt-2 mb-1">Stock visibility</p>
                    <div class="d-flex gap-3" style="font-size:13px">
                        <label class="d-inline-flex align-items-center gap-1"><input type="radio" name="stock_visibility" value="quantity" checked> Show stock quantity</label>
                        <label class="d-inline-flex align-items-center gap-1"><input type="radio" name="stock_visibility" value="text"> Show stock text only</label>
                        <label class="d-inline-flex align-items-center gap-1"><input type="radio" name="stock_visibility" value="hide"> Hide stock</label>
                    </div>
                </div>
                <div class="sp-card">
                    <label class="sp-label">Extra Delivery Per Item ৳ <small class="text-muted">(optional, charged for each additional unit beyond the first)</small></label>
                    <input type="number" name="extra_delivery_per_qty" class="sp-input" value="0" min="0" step="0.01">
                </div>
                {{-- Hidden admin-only price fields (auto-synced from base price) --}}
                <input type="hidden" name="ProductSalePrice" value="0">
                <input type="hidden" name="ProductWholesalePrice" value="0">
                <input type="hidden" name="min_sell_price" value="0">

                {{-- Product Description --}}
                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">Product description</h6>
                    <label class="sp-label">Description</label>
                    <textarea class="form-control" id="ProductDetails" name="ProductDetails" rows="5"></textarea>
                </div>

                {{-- VARIANT BUILDER (inside left column, right after description - matches supplier) --}}
                <div class="variant-section">
                    <h6 style="font-weight:700;font-size:14px;color:#312e81">🎨 Product Variants (Colors & Sizes)</h6>
                    <p style="font-size:12px;color:#4338ca">Add color variants first, then attach available sizes to each color.</p>
                    <div class="sp-card d-flex flex-wrap gap-3 align-items-end">
                        <div style="flex:1;min-width:140px"><label class="sp-label">Color Name *</label><input type="text" id="newColorName" class="sp-input" placeholder="e.g. Red"></div>
                        <div style="width:70px"><label class="sp-label">Color</label><input type="color" id="newColorCode" value="#000000" style="width:100%;height:36px;border:1px solid #d1d5db;border-radius:6px;cursor:pointer"></div>
                        <div style="flex:1;min-width:160px"><label class="sp-label">Variant Title (Optional)</label><input type="text" id="newColorTitle" class="sp-input" placeholder="defaults to color name"></div>
                        <div style="flex:1;min-width:160px"><label class="sp-label">Color Image (Optional)</label><input type="file" id="newColorImage" class="sp-input" accept="image/*"></div>
                        <div><button type="button" class="sp-btn sp-btn-indigo" onclick="addVariant()">Add Color</button></div>
                    </div>
                    <div id="variantsList" class="mt-3"></div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-lg-4">
                {{-- Category --}}
                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">Product category</h6>
                    <div class="mb-2"><label class="sp-label">Category *</label>
                        <select name="category_id" id="category_id" class="sp-select" required onchange="loadSubcategories()">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)<option value="{{$cat->id}}">{{$cat->category_name}}</option>@endforeach
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
                            @foreach($brands as $b)<option value="{{$b->id}}">{{$b->brand_name}}</option>@endforeach
                        </select>
                    </div>
                </div>

                {{-- Images --}}
                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">Product Images</h6>
                    <label class="sp-label">Gallery Images</label>
                    <div class="file-zone mb-2" onclick="document.getElementById('PostImage').click()">
                        <input type="file" name="PostImage[]" id="PostImage" multiple style="display:none" accept="image/*" onchange="previewGallery()">
                        <div>📁 Click or drag images to upload</div><small class="text-muted">Max 5MB per file</small>
                    </div>
                    <div id="galleryPreview" class="d-flex flex-wrap gap-1 mb-3"></div>
                    <label class="sp-label">Thumbnail Image</label>
                    <div class="file-zone" onclick="document.getElementById('ProductImage').click()">
                        <input type="file" name="ProductImage" id="ProductImage" style="display:none" accept="image/*" onchange="previewThumb()">
                        <div>📷 Click to upload thumbnail</div><small class="text-muted">Max 5MB</small>
                    </div>
                    <div id="thumbPreview" class="mt-2"></div>
                </div>

                {{-- YouTube --}}
                <div class="sp-card">
                    <label class="sp-label">YouTube Embed Code</label>
                    <input type="text" name="youtube_link" class="sp-input" placeholder="Paste YouTube embed URL here">
                </div>

                {{-- Admin Settings --}}
                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">⚙️ Admin Settings</h6>
                    <p class="text-muted" style="font-size:11px">These options are admin-only and not available to suppliers.</p>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="sp-label">Extra Packing ৳</label><input type="number" name="ex_pack" class="sp-input" value="0"></div>
                        <div class="col-6"><label class="sp-label">Extra Delivery ৳</label><input type="number" name="ex_dvc" class="sp-input" value="0"></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="sp-label">Reseller Bonus ৳</label><input type="number" name="reseller_bonus" class="sp-input" value="0"></div>
                        <div class="col-6"><label class="sp-label">Shipping Days</label><input type="text" name="shipping_days" class="sp-input"></div>
                    </div>
                </div>

                {{-- SEO --}}
                <div class="sp-card">
                    <h6 style="font-weight:700;font-size:14px">🔍 SEO Meta</h6>
                    <div class="mb-2"><label class="sp-label">Meta Title</label><input type="text" name="MetaTitle" class="sp-input"></div>
                    <div class="mb-2"><label class="sp-label">Meta Description</label><textarea name="MetaDescription" class="sp-input" rows="2"></textarea></div>
                    <div><label class="sp-label">Meta Image</label><input type="file" name="meta_image" class="sp-input"></div>
                </div>
            </div>
        </div>

        {{-- Variant builder has been moved inside the left column above --}}

        {{-- Submit --}}
        <div class="d-flex justify-content-end mt-3 mb-4">
            <a href="{{ url('admin/products') }}" class="sp-btn" style="background:#e5e7eb;color:#374151;margin-right:8px">Cancel</a>
            <button type="button" class="sp-btn sp-btn-primary" id="saveProductBtn" onclick="saveProduct()">
                <span id="saveBtnText">Save product</span>
                <span id="saveBtnSpinner" style="display:none">Saving...</span>
            </button>
        </div>
    </form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
// ─── State ───
var variants = []; // {id, title, color_name, color_code, imageFile, sizes:[{id,size_name,price,qty,bulkTiers:[{min_qty,max_qty,bulk_price}]}]}
var sellingType = 'wholesale';

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
    updatePriceVisibility();
});

// ─── Selling Type ───
function setSellingType(type) {
    sellingType = type;
    document.getElementById('selling_type').value = type;
    document.querySelectorAll('.selling-card').forEach(function(el){
        el.className = 'selling-card';
    });
    var el = document.getElementById('st-' + type);
    if(type==='wholesale') el.classList.add('active-wholesale');
    else if(type==='dropshipping') el.classList.add('active-dropshipping');
    else el.classList.add('active-both');
    el.querySelector('input[type=radio]').checked = true;
    updatePriceVisibility();
}

function updatePriceVisibility() {
    var show = sellingType === 'dropshipping' || sellingType === 'both';
    document.getElementById('priceStockSection').style.display = show ? 'block' : 'none';
}

// ─── Category cascading ───
function loadSubcategories() {
    var catId = document.getElementById('category_id').value;
    var sub = document.getElementById('subcategory_id');
    var mini = document.getElementById('minicategory_id');
    sub.innerHTML = '<option value="">Loading...</option>';
    mini.innerHTML = '<option value="">Select subcategory first</option>';
    if(!catId) { sub.innerHTML = '<option value="">Select subcategory</option>'; return; }
    $.get('/admin/get/subcategory/' + catId, function(data){
        sub.innerHTML = '<option value="">Select subcategory</option>';
        data.forEach(function(s){ sub.innerHTML += '<option value="'+s.id+'">'+s.sub_category_name+'</option>'; });
    });
}
function loadMinicategories() {
    var subId = document.getElementById('subcategory_id').value;
    var mini = document.getElementById('minicategory_id');
    mini.innerHTML = '<option value="">Loading...</option>';
    if(!subId) { mini.innerHTML = '<option value="">Select subcategory first</option>'; return; }
    $.get('/admin/get/minicategory/' + subId, function(data){
        if(data.length === 0) { mini.innerHTML = '<option value="">No child category</option>'; return; }
        mini.innerHTML = '<option value="">Select child category</option>';
        data.forEach(function(m){ mini.innerHTML += '<option value="'+m.id+'">'+m.mini_category_name+'</option>'; });
    });
}

// ─── Image Preview ───
function previewThumb() {
    var f = document.getElementById('ProductImage').files[0];
    if(f) document.getElementById('thumbPreview').innerHTML = '<img src="'+URL.createObjectURL(f)+'" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px">';
}
function previewGallery() {
    var files = document.getElementById('PostImage').files;
    var html = '';
    for(var i=0;i<files.length;i++) html += '<img src="'+URL.createObjectURL(files[i])+'" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">';
    document.getElementById('galleryPreview').innerHTML = html;
}

// ─── Variant Builder ───
function uid(){ return Math.random().toString(36).substr(2,8); }

function addVariant() {
    var name = document.getElementById('newColorName').value.trim();
    if(!name) { alert('Color name is required'); return; }
    variants.push({
        id: uid(),
        title: document.getElementById('newColorTitle').value.trim(),
        color_name: name,
        color_code: document.getElementById('newColorCode').value,
        imageFile: document.getElementById('newColorImage').files[0] || null,
        imagePreview: document.getElementById('newColorImage').files[0] ? URL.createObjectURL(document.getElementById('newColorImage').files[0]) : null,
        sizes: []
    });
    document.getElementById('newColorName').value = '';
    document.getElementById('newColorTitle').value = '';
    document.getElementById('newColorCode').value = '#000000';
    document.getElementById('newColorImage').value = '';
    renderVariants();
}

function removeVariant(vid) {
    variants = variants.filter(function(v){ return v.id !== vid; });
    renderVariants();
}

function addSize(vid) {
    var n = document.getElementById('sz_name_'+vid).value.trim();
    var p = document.getElementById('sz_price_'+vid).value;
    var q = document.getElementById('sz_qty_'+vid).value;
    if(!n||!p){ alert('Size name and price required'); return; }
    var v = variants.find(function(x){ return x.id===vid; });
    v.sizes.push({ id:uid(), size_name:n, price:p, qty:q||'0', bulkTiers:[] });
    document.getElementById('sz_name_'+vid).value='';
    document.getElementById('sz_price_'+vid).value='';
    document.getElementById('sz_qty_'+vid).value='0';
    renderVariants();
}

function removeSize(vid, sid) {
    var v = variants.find(function(x){ return x.id===vid; });
    v.sizes = v.sizes.filter(function(s){ return s.id!==sid; });
    renderVariants();
}

function addBulkTier(vid, sid) {
    var mn = document.getElementById('bt_min_'+sid).value;
    var mx = document.getElementById('bt_max_'+sid).value;
    var pr = document.getElementById('bt_price_'+sid).value;
    if(!mn||!pr){ alert('Min qty and bulk price required'); return; }
    var v = variants.find(function(x){ return x.id===vid; });
    var s = v.sizes.find(function(x){ return x.id===sid; });
    s.bulkTiers.push({ min_qty:mn, max_qty:mx, bulk_price:pr });
    document.getElementById('bt_min_'+sid).value='';
    document.getElementById('bt_max_'+sid).value='';
    document.getElementById('bt_price_'+sid).value='';
    renderVariants();
}

function removeBulkTier(vid, sid, btIdx) {
    var v = variants.find(function(x){ return x.id===vid; });
    var s = v.sizes.find(function(x){ return x.id===sid; });
    s.bulkTiers.splice(btIdx, 1);
    renderVariants();
}

function renderVariants() {
    var html = '';
    variants.forEach(function(v){
        html += '<div class="variant-item">';
        html += '<button type="button" class="sp-btn-danger" style="position:absolute;top:10px;right:10px" onclick="removeVariant(\''+v.id+'\')">🗑 Remove</button>';
        html += '<div class="d-flex gap-3 align-items-start" style="padding-right:80px">';
        // Color preview
        if(v.imagePreview) html += '<img src="'+v.imagePreview+'" style="width:56px;height:56px;border-radius:8px;object-fit:cover;border:1px solid #e5e7eb">';
        else html += '<div style="width:56px;height:56px;border-radius:50%;background:'+v.color_code+';border:1px solid #d1d5db"></div>';
        html += '<div style="flex:1">';
        html += '<h6 style="font-weight:700;margin:0">'+v.color_name+' <span style="font-weight:400;color:#6b7280;font-size:13px">('+(v.title||'no title')+')</span></h6>';
        html += '<p style="font-size:11px;color:#6b7280;margin:4px 0 12px">Add sizes and bulk pricing tiers for this color variant.</p>';

        // Sizes
        v.sizes.forEach(function(sz){
            html += '<div class="size-item">';
            html += '<div class="d-flex align-items-center gap-3 mb-2">';
            html += '<div style="flex:1"><small style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase">Size</small><div style="font-weight:600">'+sz.size_name+'</div></div>';
            html += '<div style="flex:1"><small style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase">Price</small><div style="font-weight:600;color:#4f46e5">৳'+Number(sz.price).toLocaleString()+'</div></div>';
            html += '<div style="flex:1"><small style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase">Qty</small><div style="font-weight:600">'+sz.qty+'</div></div>';
            html += '<button type="button" class="sp-btn-danger" onclick="removeSize(\''+v.id+'\',\''+sz.id+'\')">✕</button>';
            html += '</div>';

            // Bulk tiers
            html += '<div class="bulk-section">';
            html += '<small style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Bulk Pricing Tiers</small>';
            sz.bulkTiers.forEach(function(bt, btIdx){
                html += '<div class="bulk-row"><span style="flex:1;font-weight:500">Qty: '+bt.min_qty+' - '+(bt.max_qty||'∞')+'</span>';
                html += '<span style="font-weight:700;color:#4f46e5">৳'+Number(bt.bulk_price).toLocaleString()+'</span>';
                html += '<button type="button" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:14px" onclick="removeBulkTier(\''+v.id+'\',\''+sz.id+'\','+btIdx+')">✕</button></div>';
            });
            // Add tier form
            html += '<div class="d-flex gap-1 align-items-end mt-1">';
            html += '<input id="bt_min_'+sz.id+'" type="number" placeholder="Min" style="width:60px;font-size:11px;padding:4px;border:1px solid #d1d5db;border-radius:4px">';
            html += '<input id="bt_max_'+sz.id+'" type="number" placeholder="Max" style="width:60px;font-size:11px;padding:4px;border:1px solid #d1d5db;border-radius:4px">';
            html += '<input id="bt_price_'+sz.id+'" type="number" step="0.01" placeholder="Price" style="width:70px;font-size:11px;padding:4px;border:1px solid #d1d5db;border-radius:4px">';
            html += '<button type="button" class="sp-btn sp-btn-indigo" style="font-size:10px;padding:4px 8px" onclick="addBulkTier(\''+v.id+'\',\''+sz.id+'\')">Add Tier</button>';
            html += '</div></div></div>';
        });

        // Add size form
        html += '<div style="background:rgba(238,242,255,.5);padding:10px;border-radius:8px;border:1px solid #c7d2fe;margin-top:8px">';
        html += '<div class="mb-1"><label style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Size Name</label><input id="sz_name_'+v.id+'" type="text" class="sp-input" placeholder="e.g. S, 40, Free" style="font-size:12px;padding:6px 8px"></div>';
        html += '<div class="d-flex gap-2 align-items-end">';
        html += '<div style="flex:1"><label style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Price</label><input id="sz_price_'+v.id+'" type="number" step="0.01" class="sp-input" placeholder="Price" style="font-size:12px;padding:6px 8px"></div>';
        html += '<div style="flex:1"><label style="font-size:10px;font-weight:700;color:#312e81;text-transform:uppercase">Qty</label><input id="sz_qty_'+v.id+'" type="number" class="sp-input" placeholder="Qty" value="0" style="font-size:12px;padding:6px 8px"></div>';
        html += '<button type="button" class="sp-btn sp-btn-indigo" style="font-size:12px;white-space:nowrap" onclick="addSize(\''+v.id+'\')">Add Size</button>';
        html += '</div></div>';

        html += '</div></div></div>';
    });
    document.getElementById('variantsList').innerHTML = html;
}

// ─── Save Product ───
function saveProduct() {
    var btn = document.getElementById('saveProductBtn');
    btn.disabled = true;
    document.getElementById('saveBtnText').style.display = 'none';
    document.getElementById('saveBtnSpinner').style.display = 'inline';

    var form = document.getElementById('productForm');
    var fd = new FormData(form);
    fd.set('ProductDetails', $('#ProductDetails').summernote('code'));
    fd.set('selling_type', sellingType);

    // Handle stock visibility
    var sv = document.querySelector('input[name="stock_visibility"]:checked');
    if(sv) fd.set('stock_visibility', sv.value);

    // Handle switch buttons (checkboxes)
    ['mart_status','reseller_status','show_new_product','hot_list','ready_bost','profitable','limited','summer'].forEach(function(n){
        var cb = document.querySelector('input[name="'+n+'"]');
        if(cb && cb.checked) fd.set(n, 'on');
    });

    $.ajax({
        url: '/admin/products/ajax-store',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(res) {
            if(res.status && res.data && res.data.product) {
                var productId = res.data.product.id;
                // Save variants sequentially
                saveVariantsSequential(productId, 0, function(){
                    alert('Product created successfully!');
                    window.location.href = '/admin/products';
                });
            } else {
                alert('Product saved but no ID returned.');
                btn.disabled = false;
                document.getElementById('saveBtnText').style.display = 'inline';
                document.getElementById('saveBtnSpinner').style.display = 'none';
            }
        },
        error: function(xhr) {
            var msg = 'Failed to create product.';
            if(xhr.responseJSON && xhr.responseJSON.errors) {
                var errs = xhr.responseJSON.errors;
                msg = Object.values(errs).flat().join('\n');
            } else if(xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            alert(msg);
            btn.disabled = false;
            document.getElementById('saveBtnText').style.display = 'inline';
            document.getElementById('saveBtnSpinner').style.display = 'none';
        }
    });
}

function saveVariantsSequential(productId, idx, done) {
    if(idx >= variants.length) { done(); return; }
    var v = variants[idx];
    var vfd = new FormData();
    vfd.append('title', v.title || v.color_name || 'Variant');
    if(v.color_name) vfd.append('color_name', v.color_name);
    if(v.color_code) vfd.append('color_code', v.color_code);
    vfd.append('qty', '0');
    vfd.append('price', '0');
    if(v.imageFile) vfd.append('image', v.imageFile);

    $.ajax({
        url: '/admin/products/'+productId+'/variants-json',
        method: 'POST',
        data: vfd,
        processData: false,
        contentType: false,
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(res) {
            var variantId = res.data && res.data.variant ? res.data.variant.id : null;
            if(variantId && v.sizes.length > 0) {
                saveSizesSequential(productId, variantId, v.sizes, 0, function(){
                    saveVariantsSequential(productId, idx+1, done);
                });
            } else {
                saveVariantsSequential(productId, idx+1, done);
            }
        },
        error: function() {
            console.error('Failed to add variant: ' + v.color_name);
            saveVariantsSequential(productId, idx+1, done);
        }
    });
}

function saveSizesSequential(productId, variantId, sizes, idx, done) {
    if(idx >= sizes.length) { done(); return; }
    var s = sizes[idx];
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json/'+variantId+'/sizes',
        method: 'POST',
        data: JSON.stringify({ size_name: s.size_name, qty: parseInt(s.qty)||0, price: s.price?parseFloat(s.price):null, status:'Active' }),
        contentType: 'application/json',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function(res) {
            var sizeId = res.data && res.data.size ? res.data.size.id : null;
            if(sizeId && s.bulkTiers.length > 0) {
                saveBulkSequential(productId, variantId, sizeId, s.bulkTiers, 0, function(){
                    saveSizesSequential(productId, variantId, sizes, idx+1, done);
                });
            } else {
                saveSizesSequential(productId, variantId, sizes, idx+1, done);
            }
        },
        error: function() { saveSizesSequential(productId, variantId, sizes, idx+1, done); }
    });
}

function saveBulkSequential(productId, variantId, sizeId, tiers, idx, done) {
    if(idx >= tiers.length) { done(); return; }
    var t = tiers[idx];
    $.ajax({
        url: '/admin/products/'+productId+'/variants-json/'+variantId+'/sizes/'+sizeId+'/bulk-prices',
        method: 'POST',
        data: JSON.stringify({ min_qty: parseInt(t.min_qty), max_qty: t.max_qty?parseInt(t.max_qty):null, bulk_price: parseFloat(t.bulk_price) }),
        contentType: 'application/json',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        success: function() { saveBulkSequential(productId, variantId, sizeId, tiers, idx+1, done); },
        error: function() { saveBulkSequential(productId, variantId, sizeId, tiers, idx+1, done); }
    });
}
</script>
@endsection
