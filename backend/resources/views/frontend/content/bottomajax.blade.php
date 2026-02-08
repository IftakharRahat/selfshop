<div class="row ">
    <div class="pb-4 col-lg-12 col-12">
        <div class="mb-0" style="display: flex;justify-content: space-between;">
            <h2 class="section-title" style=""><span class="" style="border-bottom: none !important;">BIG SELLING</span></h2>
        </div>
        <?php
        $bigsells = App\Models\Product::where('status', 'Active')
            ->inRandomOrder()->limit(6)->get();
        ?>

        @if (isset($bigsells))
        <div class="row">
            @foreach($bigsells->take(2) as $categoryproduct)
                <div class="col-lg-6">
                    <div class="p-3 d-flex" style="justify-content: space-between;border: 1px solid #dad9d9;margin-bottom: 6px;border-radius:6px;">
                        @if (empty($categoryproduct->ViewProductImage))
                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                    class="img-fluid" id="btmimg"
                                    src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                    alt=""></a>
                        @else
                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                    class="img-fluid" id="btmimg"
                                    src="{{ asset($categoryproduct->ViewProductImage) }}"
                                    alt="{{ $categoryproduct->productName }}"></a>
                        @endif
                        <div class="productinfo" style="width: 60%;padding-top: 6px;">
                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                <h6 class="mb-2" style="height: 40px;overflow: hidden;">{{ $categoryproduct->ProductName }}</h6>
                            </a>
                            @include('frontend.includes.bottomprice',['price'=>$categoryproduct])
                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}" class="btn btn-info" style="    border-radius: 30px;"> Add to cart </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="pt-4 row">
            @foreach($bigsells as $categoryproduct)
                <div class="mb-3 col-lg-4">
                    <div class="d-flex" style="padding: 6px;justify-content: space-between;border: 1px solid #dad9d9;border-radius:6px;">
                        @if (empty($categoryproduct->ViewProductImage))
                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                    class="img-fluid" style="width: 100px;height: 100px;border-radius: 6px;"
                                    src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                    alt=""></a>
                        @else
                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                    class="img-fluid" style="width: 100px;height: 100px;border-radius: 6px;"
                                    src="{{ asset($categoryproduct->ViewProductImage) }}"
                                    alt="{{ $categoryproduct->productName }}"></a>
                        @endif
                        <div class="mt-3 productinfo" style="width: 60%;padding-top: 6px;">
                            <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                <h6 class="mb-2 text-truncate"  style="height: 40px;overflow: hidden;">{{ $categoryproduct->ProductName }}</h6>
                            </a>
                            @include('frontend.includes.bottomprice',['price'=>$categoryproduct])
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        @endif
    </div>
    <div class="pb-4 col-lg-12 col-12">
        <div class="mb-0" style="display: flex;justify-content: space-between;">
            <h2 class="section-title" style=""><span class="" style="border-bottom: none !important;">NEW PRODUCTS</span></h2>
            </div>

        <?php
        $newarrivals = App\Models\Product::where('status', 'Active')->latest()->take(6)->get();
        ?>
        @if (isset($newarrivals))
            <div class="row">
                @foreach($newarrivals->take(2) as $categoryproduct)
                    <div class="col-lg-6">
                        <div class="p-3 d-flex" style="justify-content: space-between;border: 1px solid #dad9d9;margin-bottom: 6px;border-radius:6px;">
                            @if (empty($categoryproduct->ViewProductImage))
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                        class="img-fluid" id="btmimg"
                                        src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                        alt=""></a>
                            @else
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                        class="img-fluid" id="btmimg"
                                        src="{{ asset($categoryproduct->ViewProductImage) }}"
                                        alt="{{ $categoryproduct->productName }}"></a>
                            @endif
                            <div class="productinfo" style="width: 60%;padding-top: 6px;">
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                    <h6 class="mb-2" style="height: 40px;overflow: hidden;">{{ $categoryproduct->ProductName }}</h6>
                                </a>
                                @include('frontend.includes.bottomprice',['price'=>$categoryproduct])
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}" class="btn btn-info" style="    border-radius: 30px;"> Add to cart </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pt-4 row">
                @foreach($newarrivals as $categoryproduct)
                    <div class="mb-3 col-lg-4">
                        <div class="d-flex" style="padding: 6px;justify-content: space-between;border: 1px solid #dad9d9;border-radius:6px;">
                            @if (empty($categoryproduct->ViewProductImage))
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                        class="img-fluid" style="width: 100px;height: 100px;border-radius: 6px;"
                                        src="{{ asset('public/frontend/') }}/img/product-1.jpg"
                                        alt=""></a>
                            @else
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}"><img
                                        class="img-fluid" style="width: 100px;height: 100px;border-radius: 6px;"
                                        src="{{ asset($categoryproduct->ViewProductImage) }}"
                                        alt="{{ $categoryproduct->productName }}"></a>
                            @endif
                            <div class="mt-3 productinfo" style="width: 60%;padding-top: 6px;">
                                <a href="{{ url('/product/' . $categoryproduct->ProductSlug) }}">
                                    <h6 class="mb-2 text-truncate">{{ $categoryproduct->ProductName }}</h6>
                                </a>
                                @include('frontend.includes.bottomprice',['price'=>$categoryproduct])
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
        @endif
    </div>
</div>