@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Information {{ $menus->menu_name }}
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">{{ $menus->menu_name }} Page Info</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">{{ $menus->menu_name }} Page Info</h6>
        </div>
        <div class="admin-card-body">
            <form action="{{ url('/admin/menu/page/create', $slug) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="text" name="key" value="{{ $slug }}" hidden>
                <div class="mb-3">
                    <textarea class="form-control ckeditor" name="value" id="value" style="height: 150px;">
@if (isset($value->value))
{{ $value->value }}
@else
@endif
</textarea>
                </div>
                <button type="submit" class="btn w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 10px 16px; font-weight: 600;">Update</button>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    initSample();
    $(document).ready(function() {
        $('.ckeditor').ckeditor();
    });
</script>

@endsection
