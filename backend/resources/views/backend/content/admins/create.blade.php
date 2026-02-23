@extends('backend.master')

@section('maincontent')

    @section('title')
        {{ env('APP_NAME') }}-Create New Admin
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
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>

    <form name="form" id="CreateRole" method="POST" action="{{ route("admin.admins.store") }}" enctype="multipart/form-data">
        @csrf
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Create New Shop Admin</h6>
            </div>
            <div class="admin-card-body admin-form-wrapper">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="name" id="floatingInput" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: #ef4444;">*</span></label>
                            <input type="email" class="form-control" name="email" id="floatingInput" placeholder="name@example.com" required>
                        </div>
                        <div class="form-group">
                            <label>Password <span style="color: #ef4444;">*</span></label>
                            <input type="password" class="form-control" name="password" id="floatingPassword" placeholder="Password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password <span style="color: #ef4444;">*</span></label>
                            <input type="password" class="form-control" onchange="checkpassword()" name="confirmpassword" id="floatingConfirmPassword" placeholder="Confirm password" required>
                            <div class="password-mismatch" id="checkText">Password does not match!</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="phone" id="floatingInput" placeholder="Phone number" required>
                        </div>
                        <div class="form-group">
                            <label>Assign Roles</label>
                            <select class="form-select" name="roles[]" id="role" multiple style="min-height: 80px;">
                                <option value="">Select Roles</option>
                                @if (Auth::guard('admin')->user()->id == 1)
                                    @forelse ($roles as $role)
                                        @if ($role->id == 2)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @else
                                        @endif
                                    @empty
                                    @endforelse
                                @else
                                    @forelse ($roles as $role)
                                        @if ($role->id == 1 || $role->id == 2)
                                        @else
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endif
                                    @empty
                                    @endforelse
                                @endif
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 10px 16px; font-weight: 600;">Create Admin</button>
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
