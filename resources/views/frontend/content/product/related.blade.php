@forelse ($relatedproducts as $relatedproduct)
    <div class="mb-4 col-lg-3 col-6" style="padding: 4px">
        <div class="vendor-items"  style="box-shadow: none;">
            <div class="border-0 card product-item"  style="box-shadow: none;">
                <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative" >
                    @if (empty($relatedproduct->ViewProductImage))
                        <a href="{{ url('/product/' . $relatedproduct->ProductSlug) }}"><img
                                class="img-fluid w-100"
                                src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                alt="" id="proimg"></a>
                    @else
                        <a href="{{ url('/product/' . $relatedproduct->ProductSlug) }}"><img
                                class="img-fluid w-100"
                                src="{{ asset($relatedproduct->ViewProductImage) }}"
                                alt="{{ $relatedproduct->productName }}" id="proimg"></a>
                    @endif
                    @if($relatedproduct->Discount>0)
                        <span id="dispos">{{$relatedproduct->Discount}}% off</span>
                    @endif
                </div>
                <div class="p-0 card-body pt-lg-2">
                    <a href="{{ url('/product/' . $relatedproduct->ProductSlug) }}" class="">
                        <h6 class="mb-2" style="padding-top:6px;height: 40px;overflow: hidden;font-size: 14px;">{{ $relatedproduct->ProductName }}</h6>
                    </a>
                    @include('frontend.includes.price',['price'=>$relatedproduct])
                </div>
            </div>
        </div>
    </div>
@empty
    empty
@endforelse