@extends('backend.master')

@section('maincontent')
    @section('title')
        {{ env('APP_NAME') }}-Edit User
    @endsection

<style>
    .admin-form-wrapper label { font-size: 13px; font-weight: 500; color: var(--admin-text, #1e293b); margin-bottom: 5px; display: block; }
    .admin-form-wrapper .form-group { margin-bottom: 16px; }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                <li class="breadcrumb-item active">Edit: {{ $user->name }}</li>
            </ol>
        </nav>
    </div>

    <form name="form" id="EditRole" method="POST" action="{{ route("admin.users.update",$user->id) }}" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <div class="admin-content-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Edit User — {{ $user->name }}</h6>
            </div>
            <div class="admin-card-body admin-form-wrapper">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="name" id="floatingInput" value="{{ $user->name }}" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: #ef4444;">*</span></label>
                            <input type="text" class="form-control" name="email" id="floatingInput" value="{{ $user->email }}" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <label>Password <span class="text-muted" style="font-size:11px;">(leave blank to keep current)</span></label>
                            <input type="password" class="form-control" name="password" id="floatingPassword" placeholder="New password">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" onchange="checkpassword()" name="confirmpassword" id="floatingConfirmPassword" placeholder="Confirm password">
                            <div id="checkText" style="font-size: 12px; color: #ef4444; margin-top: 4px; display: none;">Password does not match!</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Shop Name</label>
                            <input type="text" class="form-control" name="shop_name" id="floatingInput" value="{{ $user->shop_name }}" placeholder="Shop name">
                        </div>
                        <div class="form-group">
                            <label>Membership Status</label>
                            <select class="form-select" name="membership_status">
                                <option value="Unpaid" @if($user->membership_status=='Unpaid') selected @endif>Unpaid</option>
                                <option value="Paid" @if($user->membership_status=='Paid') selected @endif>Paid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Expire Date</label>
                            <input type="date" class="form-control" name="expire_date" id="expire_date" value="{{ $user->expire_date }}">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-select" name="status">
                                <option value="Active" @if($user->status=='Active') selected @endif>Active</option>
                                <option value="Inactive" @if($user->status=='Inactive') selected @endif>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" class="btn w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 8px; padding: 10px 16px; font-weight: 600;">Update User</button>
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
