@if(Auth::id())
    @if(Auth::user()->status=='Active')
        <div class="d-flex" style="justify-content: space-between;">
            <h6 class="mb-0 mb-lg-2" style="font-weight: bold;">@if(intval($price->ProductSalePrice)>intval($price->ProductResellerPrice))<del style="color: gray;font-weight: 400;font-size: 14px;">৳{{ intval($price->ProductSalePrice) }}</del> &nbsp;@endif<span style="color: #ee3902">৳{{ intval($price->ProductResellerPrice) }}</span></h6>
            <img src="{{asset('public/icon/cart.png')}}" alt="" style="width:30px">
        </div>
    @else
        <div class="d-flex" style="justify-content: space-between;">
            <h6 class="mb-0 mb-lg-2" style="font-weight: bold;"><span style="color: #ee3902">৳ ***</span></h6>
            <img src="{{asset('public/icon/cart.png')}}" alt="" style="width:30px">
        </div>
    @endif
@else
    <div class="d-flex" style="justify-content: space-between;">
        <h6 class="mb-0 mb-lg-2" style="font-weight: bold;"><span style="color: #ee3902">৳ ***</span></h6>
        <img src="{{asset('public/icon/cart.png')}}" alt="" style="width:30px">
    </div>
@endif
