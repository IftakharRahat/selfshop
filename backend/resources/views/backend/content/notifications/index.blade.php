@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Send Notification
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Send Notification</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">Total Users</div>
                    <div style="font-size: 22px; font-weight: 700;">{{ number_format($totalUsers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">Total Suppliers</div>
                    <div style="font-size: 22px; font-weight: 700;">{{ number_format($totalSuppliers) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Notification Details</h6>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.notifications.send') }}" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="target_type" id="target_type" class="form-select @error('target_type') is-invalid @enderror" required>
                        <option value="1" {{ old('target_type', '1') === '1' ? 'selected' : '' }}>1. All User</option>
                        <option value="2" {{ old('target_type') === '2' ? 'selected' : '' }}>2. User (Selected or All)</option>
                        <option value="3" {{ old('target_type') === '3' ? 'selected' : '' }}>3. Supplier</option>
                    </select>
                    @error('target_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea name="message" rows="4" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Image URL</label>
                    <input type="text" name="image_url" class="form-control @error('image_url') is-invalid @enderror" value="{{ old('image_url') }}" placeholder="https://example.com/image.jpg">
                    @error('image_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Link</label>
                    <input type="text" name="link" class="form-control @error('link') is-invalid @enderror" value="{{ old('link') }}" placeholder="https://example.com/page or /user/orders">
                    @error('link')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12" id="user_target_group" style="display: none;">
                    <label class="form-label">Select User(s)</label>
                    <div class="form-text mb-1">Leave empty to send to all users.</div>
                    <select name="user_ids[]" id="user_ids" class="form-select @error('user_ids') is-invalid @enderror @error('user_ids.*') is-invalid @enderror" multiple></select>
                    @error('user_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('user_ids.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12" id="supplier_target_group" style="display: none;">
                    <label class="form-label">Select Supplier(s)</label>
                    <select name="supplier_ids[]" id="supplier_ids" class="form-select @error('supplier_ids') is-invalid @enderror @error('supplier_ids.*') is-invalid @enderror" multiple></select>
                    @error('supplier_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('supplier_ids.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('subjs')
<script>
    (function () {
        const oldUserIds = @json(array_map('strval', (array) old('user_ids', [])));
        const oldSupplierIds = @json(array_map('strval', (array) old('supplier_ids', [])));

        function toggleRecipientFields() {
            const type = $('#target_type').val();
            $('#user_target_group').toggle(type === '2');
            $('#supplier_target_group').toggle(type === '3');
        }

        function preloadSelected($select, ids, endpoint) {
            if (!ids.length) {
                return;
            }

            $.ajax({
                url: endpoint,
                method: 'GET',
                dataType: 'json',
                data: { ids: ids },
                success: function (data) {
                    const results = (data && data.results) ? data.results : [];
                    results.forEach(function (item) {
                        const option = new Option(item.text, item.id, true, true);
                        $select.append(option);
                    });
                    $select.trigger('change');
                }
            });
        }

        $('#user_ids').select2({
            placeholder: 'Search users by name/email/phone',
            width: '100%',
            ajax: {
                url: '{{ route('admin.notifications.search-users') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            },
            minimumInputLength: 0
        });

        $('#supplier_ids').select2({
            placeholder: 'Search suppliers by company/contact/email',
            width: '100%',
            ajax: {
                url: '{{ route('admin.notifications.search-suppliers') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return data;
                },
                cache: true
            },
            minimumInputLength: 0
        });

        preloadSelected($('#user_ids'), oldUserIds, '{{ route('admin.notifications.search-users') }}');
        preloadSelected($('#supplier_ids'), oldSupplierIds, '{{ route('admin.notifications.search-suppliers') }}');

        $('#target_type').on('change', toggleRecipientFields);
        toggleRecipientFields();
    })();
</script>
@endsection
