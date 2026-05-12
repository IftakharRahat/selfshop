@extends('backend.master')

@section('maincontent')

@section('title')
{{ env('APP_NAME') }}-Edit H.R / Executive
@endsection

<style>
    .admin-form-wrapper label {
        font-size: 13px;
        font-weight: 500;
        color: var(--admin-text, #1e293b);
        margin-bottom: 5px;
        display: block;
    }
    .admin-form-wrapper .form-group {
        margin-bottom: 16px;
    }
    .password-mismatch {
        font-size: 12px;
        color: #ef4444;
        margin-top: 4px;
        display: none;
    }
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
                <li class="breadcrumb-item"><a href="{{ url('admin/executive') }}">H.R / Executive</a></li>
                <li class="breadcrumb-item active">Edit: {{ $admin->name }}</li>
            </ol>
        </nav>
    </div>

    <form name="form" id="EditHRExe" method="POST" action="{{ route('admin.executive.update', $admin->id) }}"
        enctype="multipart/form-data">
        @method('PUT')
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 8px; font-size: 13px;">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Basic Info --}}
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Edit H.R / Executive — {{ $admin->name }}</h6>
            </div>
            <div class="admin-card-body admin-form-wrapper">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ $admin->name }}" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: #ef4444;">*</span></label>
                            <input type="email" class="form-control" name="email" value="{{ $admin->email }}" placeholder="name@example.com" required>
                        </div>
                        <div class="form-group">
                            <label>Password <span class="text-muted" style="font-size:11px;">(leave blank to keep current)</span></label>
                            <input type="password" class="form-control" name="password" id="floatingPassword" placeholder="New password">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" onchange="checkpassword()"
                                name="confirmpassword" id="floatingConfirmPassword" placeholder="Confirm password">
                            <div class="password-mismatch" id="checkText">Password does not match!</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="phone" value="{{ $admin->phone }}" placeholder="Phone number" required>
                        </div>
                        <div class="form-group">
                            <label>Assign Role <span class="text-muted" style="font-size:11px;">(template — use button to reload permissions)</span></label>
                            <select class="form-select" name="roles[]" id="roleSelect">
                                <option value="">Select Role</option>
                                @forelse ($roles as $role)
                                    @if ($role->id == 1 || $role->id == 2)
                                    @else
                                        <option value="{{ $role->id }}" {{ $admin->hasRole($role->name) ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endif
                                @empty
                                @endforelse
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-select" name="status">
                                <option value="Active" @if($admin->status=='Active') selected @endif>Active</option>
                                <option value="Inactive" @if($admin->status=='Inactive') selected @endif>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 10px 16px; font-weight: 600;">Update H.R / Executive</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Permissions</h6>
                <span class="text-muted" style="font-size: 12px;">Select which modules this employee can access</span>
            </div>
            <div class="admin-card-body">
                <div class="check-all-bar">
                    <input class="form-check-input m-0" type="checkbox" id="checkAllPermission"
                        {{ $admin->getAllPermissions()->count() == $allpermissions->count() ? 'checked' : '' }}>
                    <span>Check All Permissions</span>
                </div>

                <div class="permission-grid">
                    <div class="row">
                        @php $i=1; @endphp
                        @forelse ($permission_groups as $permission_group)
                            @php
                                $permissions = App\Models\Admin::getPermissionsByGroupName($permission_group->name);
                                $allGroupChecked = $permissions->every(function($p) use ($admin) {
                                    return $admin->hasDirectPermission($p->name);
                                });
                            @endphp
                            <div class="col-lg-6 col-xl-4 mb-3">
                                <div class="perm-group-header">
                                    <input class="form-check-input m-0" type="checkbox" id="{{ $i }}Management" value="{{ $permission_group->name }}" onclick="chekPermissionsByGroup('role-{{ $i }}-management-checkbox',this)" {{ $allGroupChecked ? 'checked' : '' }}>
                                    <span>{{ $permission_group->name }}</span>
                                </div>
                                <div class="role-{{ $i }}-management-checkbox">
                                    @forelse ($permissions as $permission)
                                        <div class="perm-item">
                                            <input class="form-check-input m-0 perm-checkbox" type="checkbox" name="permission[]" id="permission{{ $permission->id }}" value="{{ $permission->name }}"
                                                {{ $admin->hasDirectPermission($permission->name) ? 'checked' : '' }}>
                                            <span>{{ $permission->name }}</span>
                                        </div>
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

    function checkpassword() {
        var pass = $('#floatingPassword').val();
        var confirmpass = $('#floatingConfirmPassword').val();
        if (pass == confirmpass) {
            $('#checkText').hide();
            $('#floatingConfirmPassword').css('border', '');
        } else {
            $('#checkText').show();
            $('#floatingConfirmPassword').css('border', '1px solid #ef4444');
        }
    }

    // Check All Permissions
    $('#checkAllPermission').click(function(){
        if($(this).is(':checked')){
            $('input[type=checkbox]').prop('checked',true);
        }else{
            $('input[type=checkbox]').prop('checked',false);
        }
    });

    // Check all permissions in a group
    function chekPermissionsByGroup(className, checkthis){
        const groupIdName = $('#'+checkthis.id);
        const classCheckBox = $('.'+className+' input');
        if(groupIdName.is(':checked')){
            classCheckBox.prop('checked',true);
        }else{
            classCheckBox.prop('checked',false);
        }
    }

    // Auto-fill permissions when a role is selected
    $('#roleSelect').change(function(){
        var roleId = $(this).val();
        if(!roleId) return;

        if(!confirm('Load permissions from this role? This will replace current permission selections.')) return;

        // Uncheck all first
        $('.perm-checkbox').prop('checked', false);
        $('[id$="Management"]').prop('checked', false);
        $('#checkAllPermission').prop('checked', false);

        $.ajax({
            url: '{{ url("admin/executive/role-permissions") }}/' + roleId,
            type: 'GET',
            success: function(permissions){
                permissions.forEach(function(permName){
                    $('input.perm-checkbox[value="'+permName+'"]').prop('checked', true);
                });
                updateGroupHeaders();
            }
        });
    });

    function updateGroupHeaders(){
        $('[id$="Management"]').each(function(){
            var groupClass = $(this).closest('.perm-group-header').next().attr('class');
            if(!groupClass) return;
            var total = $('.'+groupClass.split(' ')[0]+' input.perm-checkbox').length;
            var checked = $('.'+groupClass.split(' ')[0]+' input.perm-checkbox:checked').length;
            $(this).prop('checked', total > 0 && total === checked);
        });
    }

</script>

@endsection
