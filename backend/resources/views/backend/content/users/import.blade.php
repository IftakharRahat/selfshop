@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}- Import Users
    @endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                <li class="breadcrumb-item active">Import</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Users Import</h6>
        </div>
        <div class="admin-card-body">
            <form action="{{ route('import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Choose file</label>
                    <input type="file" name="file" class="form-control" id="customFile">
                </div>
                <button type="submit" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 8px 24px; font-weight: 600;">Import File</button>
            </form>
        </div>
    </div>
</div>

@endsection
