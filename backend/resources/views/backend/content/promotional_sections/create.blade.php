@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }} - Create Promotional Section
@endsection

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
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
                    <form action="{{ route('admin.promotional-sections.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Hot Selling, New Arrivals" required value="{{ old('title') }}">
                            @error('title')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Banner Image</label>
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
                                    <option value="{{ $product->id }}">{{ $product->ProductName }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Search and select products to display in this section.</small>
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                <label class="form-check-label" for="isActive">Active (visible on homepage)</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.promotional-sections.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Create Section
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
        output.onload = function() { URL.revokeObjectURL(output.src); }
    }
</script>

@endsection
