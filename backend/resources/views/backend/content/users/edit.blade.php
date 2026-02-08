@extends('backend.master')

@section('maincontent')

    @section('title')
        {{ env('APP_NAME') }}-Edit User
    @endsection

<div class="container-fluid pt-4 px-4">
    <form name="form" id="EditRole" method="POST" action="{{ route("admin.users.update",$user->id) }}" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <div class="bg-secondary rounded h-100 p-4">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <h6 class="mb-4">Edit User - {{ $user->name }}</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="name" id="floatingInput" placeholder="Your name here" value="{{ $user->name }}" required>
                                <label for="floatingInput" style="color: red">Name</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="email" id="floatingInput" placeholder=" " value="{{ $user->email }}" required>
                                <label for="floatingInput" style="color: red">Email address</label>
                            </div> 
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" name="password" id="floatingPassword" placeholder="Password" >
                                <label for="floatingPassword" style="color: red">Password</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" onchange="checkpassword()" name="confirmpassword" id="floatingConfirmPassword" placeholder="Confirm Password" >
                                <label for="floatingPassword" style="color: red">Confirm Password</label>
                                <label for="floatingPassword" id="checkText" style="color: red;display:none">Password does not match !</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="shop_name" id="floatingInput" placeholder="Shop Name" value="{{ $user->shop_name }}">
                                <label for="floatingInput" style="color: red">Shop Name</label>
                            </div>
                            <div class="form-group">
                                <label>Membership Status</label>
                                <select class="form-control" name="membership_status">
                                    <option value="Unpaid" @if($user->membership_status=='Unpaid') selected @endif>Unpaid</option>
                                    <option value="Paid" @if($user->membership_status=='Paid') selected @endif>Paid</option>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="floatingInput" style="color: red">Expire Date</label>
                                <input type="date" class="form-control" name="expire_date" id="expire_date" value="{{ $user->expire_date }}" > 
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="Active" @if($user->status=='Active') selected @endif>Active</option>
                                    <option value="Inactive" @if($user->status=='Inactive') selected @endif>Inactive</option>
                                </select>
                            </div>
                            

                            <div class="form-floating mb-3 mt-4 pt-4">
                                <button type="submit" class="btn btn-primary w-100 mt-3">Update User</button>
                            </div>
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
            $('#floatingConfirmPassword').css('border','none');
        }else{

            $('#floatingConfirmPassword').css('border','1px solid white');
        }
    }

</script>

@endsection
