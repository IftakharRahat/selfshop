@extends('backend.master')

@section('maincontent')

    @section('title')
        {{ env('APP_NAME') }}-Edit Admin
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
</style>

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.admins.index') }}">Shops</a></li>
                <li class="breadcrumb-item active">Edit: {{ $admin->name }}</li>
            </ol>
        </nav>
    </div>

    <form name="form" id="EditRole" method="POST" action="{{ route("admin.admins.update",$admin->id) }}" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Edit Admin — {{ $admin->name }}</h6>
            </div>
            <div class="admin-card-body admin-form-wrapper">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="name" id="floatingInput" value="{{ $admin->name }}" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: #ef4444;">*</span></label>
                            <input type="email" class="form-control" name="email" id="floatingInput" value="{{ $admin->email }}" placeholder="name@example.com" required>
                        </div>
                        <div class="form-group">
                            <label>Password <span class="text-muted" style="font-size:11px;">(leave blank to keep current)</span></label>
                            <input type="password" class="form-control" name="password" id="floatingPassword" placeholder="New password">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" onchange="checkpassword()" name="confirmpassword" id="floatingConfirmPassword" placeholder="Confirm password">
                            <div class="password-mismatch" id="checkText">Password does not match!</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="phone" id="floatingInput" value="{{ $admin->phone }}" placeholder="Phone number" required>
                        </div>
                        <div class="form-group">
                            <label>Assign Roles</label>
                            <select class="form-select" name="roles[]" id="role" multiple style="min-height: 80px;">
                                <option value="">Assign Roles</option>
                                @if (Auth::guard('admin')->user()->id == 1)
                                    @forelse ($roles as $role)
                                        @if ($role->id == 2)
                                            <option value="{{ $role->id }}"
                                                {{ $admin->hasRole($role->name) ? 'selected' : '' }}>{{ $role->name }}
                                            </option>
                                        @else
                                        @endif
                                    @empty
                                    @endforelse
                                @else
                                    @forelse ($roles as $role)
                                        @if ($role->id == 1 || $role->id == 2)
                                        @else
                                            <option value="{{ $role->id }}"
                                                {{ $admin->hasRole($role->name) ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endif
                                    @empty
                                    @endforelse
                                @endif
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="Active" @if($admin->status=='Active') selected @endif>Active</option>
                                <option value="Inactive" @if($admin->status=='Inactive') selected @endif>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 10px 16px; font-weight: 600;">Update Admin</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>

    function checkpassword(){
        var pass =$('#floatingPassword').val();
        var confirmpass =$('#floatingConfirmPassword').val();
        if(pass==confirmpass){
            $('#checkText').hide();
            $('#floatingConfirmPassword').css('border','');
        }else{
            $('#checkText').show();
            $('#floatingConfirmPassword').css('border','1px solid #ef4444');
        }
    }

</script>

@endsection
