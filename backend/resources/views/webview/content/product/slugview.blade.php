<div class="row pt-2 pb-2" style="background: white;">

    @forelse ($slugproducts as $categoryproduct)
        <div class="col-6 col-lg-3 mb-4">
            <div class="product">
                <div class="product-micro">
                    <div class="row product-micro-row">
                        <div class="col-12">
                            <div class="product-image">
                                <div class="image text-center">
                                    <a href="{{ url('product/' . $categoryproduct->ProductSlug) }}">
                                        <img src="{{ asset($categoryproduct->ProductImage) }}"
                                            alt="{{ $categoryproduct->ProductName }}" id="featureimage">
                                    </a>
                                </div>
                                <!-- /.image -->
                            </div>
                            <!-- /.product-image -->
                        </div>
                        <!-- /.col -->
                        <div class="col-12">
                            <div class="infofe p-md-3 p-2" style="padding-bottom: 4px !important;">
                                <div class="product-info p-0">
                                    <h2 class="name text-truncate" id="f_name"><a
                                            href="{{ url('product/' . $categoryproduct->ProductSlug) }}"
                                            id="f_pro_name">{{ $categoryproduct->ProductName }}s</a></h2>
                                </div>
                                <div class="price-box">
                                    <del
                                        class="old-product-price strong-400">৳{{ round($categoryproduct->ProductRegularPrice) }}</del>
                                    <span
                                        class="product-price strong-600">৳{{ round($categoryproduct->ProductSalePrice) }}</span>
                                </div>
                            </div>
                            <button class="btn btn-danger btn-sm mb-0 btn-block"
                                onclick="buynow({{ $categoryproduct->id }})" style="width: 100%;border-radius: 0%;color: white;"
                                id="purcheseBtn">Purchese
                                Now</button>

                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.product-micro-row -->
                </div>
                <!-- /.product-micro -->

            </div>
        </div>
    @empty
        <h2 class="p-4 text-center"><b>No Products found...</b></h2>
    @endforelse
</div>
