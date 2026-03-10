@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }} - Edit Promotional Section
@endsection

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title"><i class="bi bi-pencil-square me-2"></i>Edit: {{ $section->title }}</h6>
                    <div class="admin-card-actions">
                        <a href="{{ route('admin.promotional-sections.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <form action="{{ route('admin.promotional-sections.update', $section->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required value="{{ old('title', $section->title) }}">
                            @error('title')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Banner Image</label>
                            @if($section->banner_image)
                                <div class="mb-2">
                                    <img src="{{ $section->banner_image }}" alt="Current Banner"
                                        style="max-height:180px;border-radius:8px;border:1px solid #dee2e6;"
                                        id="currentBanner">
                                    <small class="d-block text-muted mt-1">Current banner. Upload a new one to replace it.</small>
                                </div>
                            @endif
                            <input type="file" name="banner_image" class="form-control" accept="image/*" onchange="previewBanner(event)">
                            <small class="text-muted">Recommended size: 800×400px. Max 5MB.</small>
                            <div id="bannerPreview" class="mt-2" style="display:none;">
                                <img id="prevBanner" src="" style="max-height:200px;border-radius:8px;border:1px solid #dee2e6;">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Assign Products</label>
                            <select name="product_ids[]" id="productSelect" class="form-control" multiple="multiple">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ in_array($product->id, $selectedProductIds) ? 'selected' : '' }}>
                                        {{ $product->ProductName }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Search and select products to display in this section. Currently {{ count($selectedProductIds) }} products assigned.</small>
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" {{ $section->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Active (visible on homepage)</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.promotional-sections.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#productSelect').select2({
            placeholder: 'Search and select products...',
            allowClear: true,
            width: '100%'
        });
    });

    function previewBanner(event) {
        var output = document.getElementById('prevBanner');
        output.src = URL.createObjectURL(event.target.files[0]);
        document.getElementById('bannerPreview').style.display = 'block';
        var current = document.getElementById('currentBanner');
        if (current) current.style.opacity = '0.3';
        output.onload = function() { URL.revokeObjectURL(output.src); }
    }
</script>

@endsection
