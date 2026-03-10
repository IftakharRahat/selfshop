@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }} - Create Promotional Section
@endsection

<style>
    .product-picker-search {
        position: relative;
        margin-bottom: 16px;
    }
    .product-picker-search input {
        width: 100%;
        padding: 10px 14px 10px 38px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    .product-picker-search input:focus {
        outline: none;
        border-color: var(--admin-primary, #2d2a5d);
        box-shadow: 0 0 0 3px rgba(45,42,93,0.08);
    }
    .product-picker-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 10px;
        max-height: 460px;
        overflow-y: auto;
        padding: 4px;
    }
    .product-grid-item {
        position: relative;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        background: #fff;
    }
    .product-grid-item:hover {
        border-color: var(--admin-primary, #2d2a5d);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .product-grid-item.selected {
        border-color: #10b981;
        background: #f0fdf4;
    }
    .product-grid-item.selected::after {
        content: '\2713';
        position: absolute;
        top: 4px;
        right: 6px;
        background: #10b981;
        color: #fff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
    }
    .product-grid-item img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 6px;
        background: #f8fafc;
    }
    .product-grid-item .product-name {
        font-size: 11px;
        color: #334155;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .selected-products-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
        min-height: 32px;
    }
    .selected-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e0f2fe;
        color: #0369a1;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .selected-tag img {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        object-fit: cover;
    }
    .selected-tag .remove-tag {
        cursor: pointer;
        margin-left: 2px;
        color: #0369a1;
        font-weight: bold;
        font-size: 14px;
    }
    .selected-tag .remove-tag:hover {
        color: #dc2626;
    }
    .product-count-badge {
        background: #10b981;
        color: #fff;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    /* Upload zone */
    .upload-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 24px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8fafc;
        position: relative;
    }
    .upload-zone:hover, .upload-zone.dragover {
        border-color: var(--admin-primary, #2d2a5d);
        background: #f1f5f9;
    }
    .upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .upload-zone .upload-icon {
        font-size: 32px;
        color: #94a3b8;
        margin-bottom: 8px;
    }
    .upload-zone .upload-text {
        color: #64748b;
        font-size: 13px;
        margin: 0;
    }
    .upload-zone .upload-text strong {
        color: var(--admin-primary, #2d2a5d);
    }
    .upload-preview {
        margin-top: 12px;
        position: relative;
        display: none;
    }
    .upload-preview img {
        max-height: 140px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .upload-preview .remove-preview {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #ef4444;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .color-preview {
        display: inline-block;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
        vertical-align: middle;
        cursor: pointer;
    }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title"><i class="bi bi-plus-lg me-2"></i>Create Promotional Section</h6>
                    <div class="admin-card-actions">
                        <a href="{{ route('admin.promotional-sections.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <form action="{{ route('admin.promotional-sections.store') }}" method="POST" enctype="multipart/form-data" id="sectionForm">
                        @csrf

                        <div class="row">
                            {{-- Left: Settings --}}
                            <div class="col-lg-4">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" placeholder="e.g. Hot Selling, New Arrivals" required value="{{ old('title') }}">
                                    @error('title')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold">Layout Type</label>
                                    <select name="layout_type" id="layoutType" class="form-select" onchange="toggleLayoutFields()">
                                        <option value="card" {{ old('layout_type') == 'card' ? 'selected' : '' }}>Card (Banner + Product Grid)</option>
                                        <option value="slider" {{ old('layout_type') == 'slider' ? 'selected' : '' }}>Slider (Swiper Product Row)</option>
                                    </select>
                                    <small class="text-muted">Card shows a banner with products below. Slider shows a horizontal product carousel.</small>
                                </div>

                                {{-- Banner Image (card only) --}}
                                <div class="form-group mb-3" id="bannerField">
                                    <label class="form-label fw-semibold">Banner Image</label>
                                    <div class="upload-zone" id="uploadZone">
                                        <input type="file" name="banner_image" accept="image/*" onchange="previewBanner(event)">
                                        <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                        <p class="upload-text"><strong>Click to upload</strong> or drag and drop</p>
                                        <p class="upload-text" style="font-size:11px;color:#94a3b8;margin-top:4px;">PNG, JPG up to 5MB · 800×400px recommended</p>
                                    </div>
                                    <div class="upload-preview" id="bannerPreview">
                                        <img id="prevBanner" src="">
                                        <button type="button" class="remove-preview" onclick="removeBannerPreview()">&times;</button>
                                    </div>
                                </div>

                                {{-- Background Color (slider only) --}}
                                <div class="form-group mb-3" id="bgColorField" style="display:none;">
                                    <label class="form-label fw-semibold">Background Color</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" name="bg_color" id="bgColorPicker" value="{{ old('bg_color', '#ffffff') }}"
                                            class="color-preview" oninput="document.getElementById('bgColorHex').value = this.value">
                                        <input type="text" id="bgColorHex" class="form-control" value="{{ old('bg_color', '#ffffff') }}" style="max-width:120px;"
                                            oninput="document.getElementById('bgColorPicker').value = this.value">
                                    </div>
                                    <small class="text-muted">Background color for the slider section. Use white (#ffffff) for no background.</small>
                                </div>

                                <div class="form-group mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                        <label class="form-check-label" for="isActive">Active (visible on homepage)</label>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold">Display Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" placeholder="0">
                                    <small class="text-muted">Lower numbers appear first on the homepage.</small>
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <a href="{{ route('admin.promotional-sections.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Create Section
                                    </button>
                                </div>
                            </div>

                            {{-- Right: Product picker --}}
                            <div class="col-lg-8">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold">
                                        Assign Products
                                        <span class="product-count-badge ms-2" id="selectedCount">0 selected</span>
                                    </label>

                                    <div class="selected-products-bar" id="selectedTags"></div>

                                    <div class="product-picker-search">
                                        <i class="bi bi-search"></i>
                                        <input type="text" id="productSearch" placeholder="Search products by name...">
                                    </div>

                                    <div class="product-grid" id="productGrid">
                                        @foreach($products as $product)
                                            @php
                                                $imgSrc = $product->ViewProductImage;
                                                if ($imgSrc && !str_starts_with($imgSrc, 'http')) {
                                                    $imgSrc = url(preg_replace('#^public/#', '', $imgSrc));
                                                }
                                            @endphp
                                            <div class="product-grid-item"
                                                 data-id="{{ $product->id }}"
                                                 data-name="{{ $product->ProductName }}"
                                                 data-img="{{ $imgSrc }}"
                                                 onclick="toggleProduct(this)">
                                                <img src="{{ $imgSrc }}" alt="{{ $product->ProductName }}"
                                                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23f1f5f9%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%2394a3b8%22 font-size=%2212%22>No Image</text></svg>'">
                                                <div class="product-name">{{ $product->ProductName }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="hiddenInputs"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var selectedProducts = {};

    function toggleProduct(el) {
        var id = el.dataset.id;
        if (selectedProducts[id]) {
            delete selectedProducts[id];
            el.classList.remove('selected');
        } else {
            selectedProducts[id] = { name: el.dataset.name, img: el.dataset.img };
            el.classList.add('selected');
        }
        renderSelectedTags();
        renderHiddenInputs();
    }

    function removeProduct(id) {
        delete selectedProducts[id];
        var el = document.querySelector('.product-grid-item[data-id="' + id + '"]');
        if (el) el.classList.remove('selected');
        renderSelectedTags();
        renderHiddenInputs();
    }

    function renderSelectedTags() {
        var container = document.getElementById('selectedTags');
        var count = Object.keys(selectedProducts).length;
        document.getElementById('selectedCount').textContent = count + ' selected';
        var html = '';
        for (var id in selectedProducts) {
            var p = selectedProducts[id];
            html += '<span class="selected-tag">' +
                '<img src="' + p.img + '" onerror="this.style.display=\'none\'">' +
                '<span>' + p.name.substring(0, 25) + (p.name.length > 25 ? '...' : '') + '</span>' +
                '<span class="remove-tag" onclick="removeProduct(\'' + id + '\')">&times;</span></span>';
        }
        container.innerHTML = html;
    }

    function renderHiddenInputs() {
        var container = document.getElementById('hiddenInputs');
        var html = '';
        for (var id in selectedProducts) {
            html += '<input type="hidden" name="product_ids[]" value="' + id + '">';
        }
        container.innerHTML = html;
    }

    document.getElementById('productSearch').addEventListener('input', function() {
        var query = this.value.toLowerCase();
        document.querySelectorAll('.product-grid-item').forEach(function(item) {
            item.style.display = item.dataset.name.toLowerCase().includes(query) ? '' : 'none';
        });
    });

    function previewBanner(event) {
        if (!event.target.files[0]) return;
        var output = document.getElementById('prevBanner');
        output.src = URL.createObjectURL(event.target.files[0]);
        document.getElementById('bannerPreview').style.display = 'inline-block';
        document.getElementById('uploadZone').style.display = 'none';
        output.onload = function() { URL.revokeObjectURL(output.src); }
    }

    function removeBannerPreview() {
        document.getElementById('bannerPreview').style.display = 'none';
        document.getElementById('uploadZone').style.display = 'block';
        document.querySelector('#uploadZone input[type="file"]').value = '';
    }

    // Layout type toggle
    function toggleLayoutFields() {
        var type = document.getElementById('layoutType').value;
        document.getElementById('bannerField').style.display = type === 'card' ? '' : 'none';
        document.getElementById('bgColorField').style.display = type === 'slider' ? '' : 'none';
    }
    toggleLayoutFields();

    // Drag and drop
    var zone = document.getElementById('uploadZone');
    zone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
    zone.addEventListener('dragleave', function() { this.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        var input = this.querySelector('input[type="file"]');
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
    });
</script>

@endsection
