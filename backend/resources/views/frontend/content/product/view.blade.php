@forelse ($categoryproducts as $relatedproduct)
    <div class="pb-2 col-lg-2 col-6" style="padding: 4px">
        <div class="card product-item">
            <div class="p-0 overflow-hidden bg-transparent border card-header product-img position-relative"
                id="cardimg">
                @if (empty($relatedproduct->ProductImage))
                    <a href="{{ url('/product/' . $relatedproduct->ProductSlug) }}"><img class="img-fluid w-100"
                            src="{{ asset('public/frontend/') }}/img/product-1.jpg" alt=""></a>
                @else
                    <a href="{{ url('/product/' . $relatedproduct->ProductSlug) }}"><img class="img-fluid w-100"
                            src="{{ asset($relatedproduct->ProductImage) }}"
                            alt="{{ $relatedproduct->productName }}"></a>
                @endif
            </div>
            <div class="p-0 pt-2 text-left card-body">
                <a href="{{ url('/product/' . $relatedproduct->ProductSlug) }}">
                    <h6 class="mb-2" style="padding-top: 6px;height: 40px;overflow: hidden;font-size: 14px;">{{ $relatedproduct->ProductName }}</h6>
                </a>
                @include('frontend.includes.price',['price'=>$relatedproduct])
            </div>
        </div>
    </div>
@empty
@endforelse
