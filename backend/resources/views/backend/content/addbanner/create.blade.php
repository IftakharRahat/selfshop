@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Create Banner
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Create Banner</h6>
                    <div class="admin-card-actions">
                        <a href="{{ route('admin.addbanners.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <form name="form" action="{{ url('admin/addbanners') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="title" placeholder="Enter banner title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Text</label>
                            <textarea name="text" class="form-control" id="text" cols="30" rows="3" placeholder="Enter banner text"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon <span class="text-danger">*</span></label>
                            <input class="form-control" name="icon" id="icon" type="file" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Background Image <span class="text-danger">*</span></label>
                            <input class="form-control" name="bg_img" id="bg_img" type="file" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2 pt-3">
                            <a href="{{ route('admin.addbanners.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" name="btn"
                                class="btn btn-primary btn-sm">Save Banner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
