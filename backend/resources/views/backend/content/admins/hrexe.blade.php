@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- H.R / Executive
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">H.R / Executive</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">H.R / Executive List</h6>
            <div class="admin-card-actions">
                <a href="{{ url('admin/executive/create') }}" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create H.R / Executive
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="roleinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($admins as $admin)
                            <tr>
                                <td>{{ $admin->id }}</td>
                                <td><strong>{{ $admin->name }}</strong></td>
                                <td>{{ $admin->email }}</td>
                                <td>
                                    @forelse ($admin->roles as $role)
                                        <span class="badge mb-1" style="background: var(--admin-primary-lighter, #eef0ff); color: var(--admin-primary, #2d2a5d); font-size: 11px; font-weight: 500;">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-muted" style="font-size: 12px;">No roles</span>
                                    @endforelse
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.admins.edit', $admin->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="{{ route('admin.admins.destroy', $admin->id) }}"
                                            onclick="event.preventDefault(); document.getElementById('delete-admin-{{ $admin->id }}').submit(); "
                                            class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>

                                        <form id="delete-admin-{{ $admin->id }}"
                                            action="{{ route('admin.admins.destroy', $admin->id) }}" method="post">
                                            @method('delete')
                                            @csrf
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#roleinfo').DataTable();
    });
</script>

@endsection
