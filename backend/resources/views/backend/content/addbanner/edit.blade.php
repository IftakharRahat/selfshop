@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Edit Banner
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Edit Banner</h6>
                    <div class="admin-card-actions">
                        <a href="{{ route('admin.addbanners.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <form name="form" action="{{ route('admin.addbanners.update', $addbanner->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" value="{{ $addbanner->title }}" class="form-control" name="title" id="title" placeholder="Enter banner title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Text</label>
                            <textarea name="text" class="form-control" id="text" cols="30" rows="3" placeholder="Enter banner text">{{ $addbanner->text }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon</label>
                            <input class="form-control" name="icon" id="icon" type="file">
                        </div>
                        <div class="mb-3 d-flex align-items-center gap-3">
                            <span class="form-label mb-0">Current Icon:</span>
                            <div>
                                @if($addbanner->icon)
                                    <img src="{{ asset($addbanner->icon) }}" alt="" style="height:80px;border-radius:6px;object-fit:cover;"
                                        onerror="this.outerHTML='<div style=\'width:80px;height:80px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:20px\'><i class=\'bi bi-image\'></i></div>'">
                                @else
                                    <div style="width:80px;height:80px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:20px">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Background Image</label>
                            <input class="form-control" name="bg_img" id="bg_img" type="file">
                        </div>
                        <div class="mb-3 d-flex align-items-center gap-3">
                            <span class="form-label mb-0">Current Background:</span>
                            <div>
                                @if($addbanner->bg_img)
                                    <img src="{{ asset($addbanner->bg_img) }}" alt="" style="height:80px;max-width:200px;border-radius:6px;object-fit:cover;"
                                        onerror="this.outerHTML='<div style=\'width:200px;height:80px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:20px\'><i class=\'bi bi-image\'></i></div>'">
                                @else
                                    <div style="width:200px;height:80px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:20px">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 pt-3">
                            <a href="{{ route('admin.addbanners.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" name="btn"
                                class="btn btn-primary btn-sm">Update Banner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
