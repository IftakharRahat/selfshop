@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}-Create New User Role
    @endsection

<style>
    .permission-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .perm-group-card { background: var(--admin-bg, #f8fafc); border: 1px solid var(--admin-border, #f1f5f9); border-radius: 10px; padding: 16px; }
    .perm-group-header { display: flex; align-items: center; gap: 8px; padding-bottom: 10px; border-bottom: 1px solid var(--admin-border, #f1f5f9); margin-bottom: 10px; text-transform: capitalize; font-weight: 600; font-size: 13px; }
    .perm-item { display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: 13px; }
    .check-all-bar { display: flex; align-items: center; gap: 10px; padding: 10px 16px; background: var(--admin-primary-lighter, #eef2ff); border-radius: 8px; font-weight: 600; font-size: 13px; }
    @media (max-width: 768px) { .permission-grid { grid-template-columns: 1fr; } }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.userroles.index') }}">User Roles</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>

    <form name="form" id="CreateRole" method="POST" action="{{ route("admin.userroles.store") }}" enctype="multipart/form-data">
        @csrf
        <div class="admin-content-card mb-3">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Create New User Role</h6>
            </div>
            <div class="admin-card-body">
                <div class="row align-items-end">
                    <div class="col-md-9">
                        <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Role Name</label>
                        <input type="text" class="form-control" id="roleName" name="roleName" placeholder="Enter role name">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 8px 16px; font-weight: 600;">Create User Role</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Permission List</h6>
            </div>
            <div class="admin-card-body">
                <div class="check-all-bar mb-3">
                    <input class="form-check-input m-0" type="checkbox" id="checkAllPermission">
                    <span>Check All</span>
                </div>
                <div class="permission-grid">
                    @php $i=1; @endphp
                    @forelse ($permission_groups as $permission_group)
                        <div class="perm-group-card">
                            <div class="perm-group-header">
                                <input class="form-check-input m-0" type="checkbox" id="{{ $i }}Management" value="{{ $permission_group->name }}" onclick="chekPermissionsByGroup('role-{{ $i }}-management-checkbox',this)">
                                <span>{{ $permission_group->name }}</span>
                            </div>
                            <div class="role-{{ $i }}-management-checkbox">
                                @php
                                    $permissions = App\Models\User::getPermissionsByGroupName($permission_group->name);
                                    $j = 1;
                                @endphp
                                @forelse ($permissions as $permission)
                                    <div class="perm-item">
                                        <input class="form-check-input m-0" type="checkbox" name="permission[]" id="permission{{ $permission->id }}" value="{{ $permission->name }}">
                                        <span>{{ $permission->name }}</span>
                                    </div>
                                    @php $j++; @endphp
                                @empty
                                @endforelse
                            </div>
                        </div>
                        @php $i++; @endphp
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$('#checkAllPermission').click(function(){
    if($(this).is(':checked')){
        $('input[type=checkbox]').prop('checked',true);
    }else{
        $('input[type=checkbox]').prop('checked',false);
    }
});

function chekPermissionsByGroup(className, checkthis){
    const groupIdName =$('#'+checkthis.id);
    const classCheckBox = $('.'+className+' input');
    if(groupIdName.is(':checked')){
        classCheckBox.prop('checked',true);
    }else{
        classCheckBox.prop('checked',false);
    }
}
</script>

@endsection
