@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }} - Promotional Sections
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title"><i class="bi bi-megaphone me-2"></i>Promotional Sections</h6>
                    <div class="admin-card-actions">
                        <a class="btn btn-primary btn-sm" href="{{ route('admin.promotional-sections.create') }}">
                            <i class="bi bi-plus-lg"></i> Create Section
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    @if(session('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="data-tables">
                        <table class="table" id="sectionsTable" width="100%">
                            <thead>
                                <tr>
                                    <th style="width:60px">Order</th>
                                    <th>Banner</th>
                                    <th>Title</th>
                                    <th>Slug</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sections as $section)
                                    <tr data-id="{{ $section->id }}">
                                        <td>
                                            <span class="badge bg-secondary">{{ $section->sort_order }}</span>
                                        </td>
                                        <td>
                                            @if($section->banner_image)
                                                <img src="{{ $section->banner_image }}" alt="{{ $section->title }}"
                                                    style="height:45px;width:80px;object-fit:cover;border-radius:6px;"
                                                    onerror="this.outerHTML='<div style=\'width:80px;height:45px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px\'><i class=\'bi bi-image\'></i></div>'">
                                            @else
                                                <div style="width:80px;height:45px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px">
                                                    <i class="bi bi-image"></i> No Image
                                                </div>
                                            @endif
                                        </td>
                                        <td><strong>{{ $section->title }}</strong></td>
                                        <td><code>{{ $section->slug }}</code></td>
                                        <td>
                                            <span class="badge bg-info">{{ $section->products_count }} products</span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.promotional-sections.toggle-status', $section->id) }}"
                                                method="POST" style="display:inline;">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm {{ $section->is_active ? 'btn-success' : 'btn-warning' }}">
                                                    {{ $section->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.promotional-sections.edit', $section->id) }}"
                                                class="btn btn-primary btn-sm" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('admin.promotional-sections.destroy', $section->id) }}"
                                                method="POST" style="display:inline;"
                                                onsubmit="return confirm('Delete this section? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No promotional sections found. Click "Create Section" to add one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
