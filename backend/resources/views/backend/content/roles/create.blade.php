@extends('backend.master')

@section('maincontent')

    @section('title')
        {{ env('APP_NAME') }}-Create New Role
    @endsection

<style>
    .permission-grid .perm-group-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: var(--admin-primary-lighter, #eef0ff);
        border-radius: 8px;
        margin-bottom: 8px;
        text-transform: capitalize;
        font-weight: 600;
        font-size: 13px;
        color: var(--admin-primary, #2d2a5d);
    }
    .permission-grid .perm-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-bottom: 1px solid var(--admin-border, #f1f5f9);
        font-size: 13px;
    }
    .permission-grid .perm-item:last-child {
        border-bottom: none;
    }
    .check-all-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--admin-bg, #f8fafc);
        border: 1px solid var(--admin-border, #e2e8f0);
        border-radius: 8px;
        margin-bottom: 16px;
        font-weight: 600;
        font-size: 13px;
    }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>

    <form name="form" id="CreateRole" method="POST" action="{{ route("admin.roles.store") }}" enctype="multipart/form-data">
        @csrf

        {{-- Role Name --}}
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Create New Role</h6>
            </div>
            <div class="admin-card-body">
                <div class="row align-items-end">
                    <div class="col-md-9">
                        <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Role Name</label>
                        <input type="text" class="form-control" id="roleName" name="roleName" placeholder="Enter role name">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 8px 16px; font-weight: 600;">Create Role</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Permissions</h6>
            </div>
            <div class="admin-card-body">
                <div class="check-all-bar">
                    <input class="form-check-input m-0" type="checkbox" id="checkAllPermission">
                    <span>Check All Permissions</span>
                </div>

                <div class="permission-grid">
                    <div class="row">
                        @php $i=1; @endphp
                        @forelse ($permission_groups as $permission_group)
                            <div class="col-lg-6 col-xl-4 mb-3">
                                <div class="perm-group-header">
                                    <input class="form-check-input m-0" type="checkbox" id="{{ $i }}Management" value="{{ $permission_group->name }}" onclick="chekPermissionsByGroup('role-{{ $i }}-management-checkbox',this)">
                                    <span>{{ $permission_group->name }}</span>
                                </div>
                                <div class="role-{{ $i }}-management-checkbox">
                                    @php
                                        $permissions = App\Models\Admin::getPermissionsByGroupName($permission_group->name);
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
        </div>
    </form>
</div>

<script>

$('#checkAllPermission').click(function(){
    if($(this).is(':checked')){
        // checked all the checkbox
        $('input[type=checkbox]').prop('checked',true);
    }else{
        // unchecked all the checkbox
        $('input[type=checkbox]').prop('checked',false);
    }
});

function chekPermissionsByGroup(className , checkthis){
    const groupIdName =$('#'+checkthis.id);
    const classCheckBox = $('.'+className+' input');
    if(groupIdName.is(':checked')){
        // checked all the checkbox
        classCheckBox.prop('checked',true);
    }else{
        // unchecked all the checkbox
        classCheckBox.prop('checked',false);
    }
}

</script>

@endsection
