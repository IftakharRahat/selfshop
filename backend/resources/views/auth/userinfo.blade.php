@if(isset($user))
<div class="card mb-3" style="border-radius: 15px;">
    <div class="card-body p-2">
        <div class="d-flex">
            <img src="{{asset($user->profile)}}" style="width:80px;border-radius:50%;margin-right:20px">
            <div class="info">
                <p class="m-0 p-0 mb-2">
                    Name: {{$user->name}}
                </p>
                <p class="m-0 p-0 mb-2">
                    Shop: {{$user->shop_name}}
                </p>
                <p class="m-0 p-0 mb-2">
                    Phone: {{$user->email}}
                </p>
            </div>
        </div>
    </div>
</div>
@else
<div class="card mb-3" style="border-radius: 15px;">
    <div class="card-body p-2">
         <h4>No data found !</h4>
    </div>
</div>
@endif