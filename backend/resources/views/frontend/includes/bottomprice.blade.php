@if(Auth::id())
    @if(Auth::user()->status=='Active')
    <div class="pr-4 d-flex" style="justify-content: space-between;">
        <h6>৳ {{ intval($price->ProductResellerPrice) }}</h6>
        <img src="{{asset('public/icon/cart.png')}}" alt="" style="width:30px">
    </div>
    @else
    <div class="pr-4 d-flex" style="justify-content: space-between;">
        <h6>৳ ***</h6>
        <img src="{{asset('public/icon/cart.png')}}" alt="" style="width:30px">
    </div>
    @endif

@else
<div class="pr-4 d-flex" style="justify-content: space-between;">
    <h6>৳ ***</h6>
    <img src="{{asset('public/icon/cart.png')}}" alt="" style="width:30px">
</div>
@endif
