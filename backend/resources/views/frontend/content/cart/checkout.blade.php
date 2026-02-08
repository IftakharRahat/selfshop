@extends('frontend.master')

@section('maincontent')
        {{-- //no cart --}}
    @if(!Session::has('cart'))
        <div class="container pb-5 mb-sm-4">
            <div class="pt-5">
                <div class="py-3 card mt-sm-3" style="min-height: 309px;">
                    <div class="text-center card-body">
                        <h2 class="pb-3 h4">কোন প্রোডাক্ট নেই</h2>
                        <a class="mt-3 btn btn-primary" href="{{url('/')}}">প্রোডাক্ট বাছাই করুন</a>
                    </div>
                </div>
            </div>
        </div>

    @elseif(Cart::count() == 0)
        <div class="container pb-5 mb-sm-4">
            <div class="pt-5">
                <div class="py-3 card mt-sm-3" style="min-height: 309px;">
                    <div class="text-center card-body">
                        <h2 class="pb-3 h4">কোন প্রোডাক্ট নেই</h2>
                        <a class="mt-3 btn btn-primary" href="{{url('/')}}">প্রোডাক্ট বাছাই করুন</a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <br>
        <section class="section-content padding-y bg slidetop">
            <div class="container-fluid">
                <div class="py-3 row px-xl-5">
                    <div class="col-md-6">
                        <aside class="mb-4 card">
                            <article class="card-body">
                                <header class="mb-4">
                                    <p class="text-center" style="font-size: 16px;">অর্ডারটি কনফার্ম করতে কাস্টমারের নাম, ঠিকানা, মোবাইল নাম্বার, লিখে <span class="text-danger">অর্ডার কনফার্ম করুন</span> বাটনে ক্লিক করুন
                                    </p>
                                </header>
                                <form action="{{url('/press-order')}}" method="POST" class="from-prevent-multiple-submits">
                                    @csrf
                                    <div class="row">
                                        <div class="form-group col-sm-12">
                                            <label>কাস্টমারের নাম </label>
                                            <input type="text" id="userName" name="userName" placeholder="কাস্টমারের নাম লিখুন" required class="form-control" style=" background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%; cursor: auto;">
                                        </div>
                                        <div class="form-group col-sm-12">
                                            <label>কাস্টমারের ঠিকানা </label>
                                            <input type="text" id="userAddress" name="userAddress" placeholder="কাস্টমারের ঠিকানা লিখুন" required class="form-control" style=" background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%; cursor: auto;">
                                        </div>
                                        <div class="form-group col-sm-12">
                                            <label>কাস্টমারের মোবাইল </label>
                                            <input type="tel" minlength="10" id="userPhone" name="userPhone" required class="form-control" placeholder="কাস্টমারের মোবাইল লিখুন">
                                        </div>
                                        <textarea id="ordersubtotalprice" name="subTotal" cols="10" rows="5" hidden>{{Cart::subtotalFloat()}}</textarea>
                                        <div class="form-group col-sm-12">
                                            <label>Select Area </label>
                                            <select id="selectCourier" name="selectCourier" class="form-control" onchange="setdeliverychargr()">
                                                <option value="{{App\Models\Basicinfo::first()->inside_dhaka_charge}}">ঢাকার ভিতর ({{App\Models\Basicinfo::first()->inside_dhaka_charge}})</option>
                                                <option value="{{App\Models\Basicinfo::first()->near_dhaka_charge}}">ঢাকার কাছাকাছি ({{App\Models\Basicinfo::first()->near_dhaka_charge}}) </option>
                                                <option value="{{App\Models\Basicinfo::first()->outside_dhaka_charge}}">ঢাকার বাহির ({{App\Models\Basicinfo::first()->outside_dhaka_charge}})</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-sm-12">
                                            <label>কাস্টমার নোট</label>
                                            <textarea id="ordersubtotalprice" name="customerNote" cols="10" rows="5" ></textarea>
                                            <input type="tel" minlength="10" id="userPhone" name="customerNote" required class="form-control" placeholder="আপনার মোবাইল লিখুন">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" id="orderConfirm" class="btn btn-success btn-lg btn-styled from-prevent-multiple-submits btn-base-1 btn-block btn-icon-left strong-500 hov-bounce hov-shaddow buy-now" style="font-size:20px !important;"> অর্ডার কনফার্ম করুন  </button>
                                        </div>
                                    </div>
                                </form>
                            </article> <!-- card-body.// -->
                        </aside>
                    </div>
                    <div class="col-md-6 orderDetails">
                        <aside class="card">
                            <article class="card-body">
                                <header class="mb-4">
                                    <h4 class="card-title" style="font-size: 16px;">আপনার অর্ডার</h4>
                                </header>
                                <div class="row">
                                    <div class="p-4 bg-white table-responsive">
                                        <table class="table border-bottom">
                                            <thead>
                                            <tr>
                                                <th class="product-image"></th>
                                                <th class="product-name">Product</th>
                                                <th class="product-price">Price</th>
                                                <th class="product-quanity">Quantity</th>
                                                <th class="product-total">Total</th>
                                                <th class="product-remove"></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($cartProducts as $cartProduct)
                                                    <tr class="cart-item" id= "productcart{{$cartProduct->rowId}}">
                                                        <td class="product-image">
                                                            <a href="#" class="mr-3">
                                                                <img class=" ls-is-cached lazyloaded" src="{{asset($cartProduct->image)}}" style="max-width: 50px">
                                                            </a>
                                                        </td>

                                                        <td class="product-name">
                                                            <span class="pr-4 d-block">{{$cartProduct->name}}</span>
                                                        </td>

                                                        <td class="product-price" style="width: 80px;">
                                                            <span class="pr-3 d-block">৳ {{$cartProduct->price}}</span>
                                                        </td>

                                                        <td class="product-quantity">
                                                            <div class="pr-4 input-group--style-2" style="width: 130px;">

                                                                <input type="number" name="quantity[{{$cartProduct->id}}]" id="QuantityPeo{{$cartProduct->rowId}}" class="text-center form-control bg-secondary" placeholder="1" value="{{$cartProduct->qty}}" min="1" max="10" onchange="updateQuantity('{{$cartProduct->rowId}}', this)">

                                                            </div>
                                                        </td>
                                                        <input type="text" name="productP" id="priceOf{{$cartProduct->rowId}}" value="{{$cartProduct->price}}" hidden>
                                                        <td class="product-total" style="width: 80px;">
                                                            <span>৳ <span id="pricetotal{{$cartProduct->rowId}}" class="price">{{$cartProduct->qty*$cartProduct->price}}</span></span>
                                                        </td>
                                                        <td class="product-remove" style="width: 30px;">
                                                            <a type="button" style="width: 30px;" onclick="removeFromCart('{{$cartProduct->rowId}}')" class="pl-4 text-right">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty

                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </article>

                            <input type="text" name="size" value="{{$cartProduct->options->size}}" hidden>
                            <input type="text" name="color" value="{{$cartProduct->options->color}}" hidden>

                            <article class="card-body border-top">
                                <dl class="row">
                                    <dt class="col-sm-8">Subtotal: </dt>
                                    <dd class="text-right col-sm-4"><strong>৳ <span id="subtotalprice">{{Cart::subtotalFloat()}}</span> </strong></dd>

                                    <dt class="col-sm-8">Delivery charge: </dt>
                                    <dd class="text-right col-sm-4 text-danger"><strong>৳ <span id="dinamicdalivery"></span></strong></dd>

                                    <dt class="col-sm-8">Total:</dt>
                                    <dd class="text-right col-sm-4"><strong class="h5 text-dark">৳ <span id="totalamount"></span></strong></dd>
                                </dl>

                            </article>

                        </aside>
                    </div>

                    </div>
            </div>
        </section>
        <br>
    @endif


    <script>

        function setdeliverychargr(){
            var deliverycharge = $('#selectCourier').val();
            $('#dinamicdalivery').html(deliverycharge);

            var subprice = $('#subtotalprice').html();
            var totalprice =subprice-(-deliverycharge);
            $('#totalamount').html(totalprice)
        }

        function updateQuantity(rowId){
            var quantity = $('#QuantityPeo'+rowId).val();
            var price = $('#priceOf'+rowId).val();
            var producttotal = quantity*price;

            var prevPrice= $('#pricetotal'+rowId).html();
            if(producttotal>prevPrice){
                var subPrice= $('#subtotalprice').html();
                var updatesubprice = subPrice-(-price);
                $('#subtotalprice').html(updatesubprice);
                //ordersubtotal
                $('#ordersubtotalprice').html(updatesubprice);
                //cart number
                var prevcart = $('#cartNumber').html();
                var cartUpdate = prevcart-(-1);
                $('#cartNumber').html(cartUpdate);

            }else{
                //cart number
                var prevcart = $('#cartNumber').html();
                var cartUpdate = prevcart-1;
                $('#cartNumber').html(cartUpdate);

                var subPrice= $('#subtotalprice').html();
                var updatesubprice = subPrice-price;
                $('#subtotalprice').html(updatesubprice);
                $('#ordersubtotalprice').html(updatesubprice);

            }
            //mincart
            $('#minQty'+rowId).html(quantity);
            $('#minsubtotalprice').html(updatesubprice);
            //total price part
            var deliverycharge = $('#selectCourier').val();
            var totalprice =updatesubprice-(-deliverycharge);
            $('#totalamount').html(totalprice);


            $('#pricetotal'+rowId).html(producttotal);

            $.ajax({
                type:'POST',
                url:'update-cart',

                data:{
                        _token:'{{ csrf_token() }}',
                        rowId: rowId,
                        qty: quantity,
                    },

                success: function (data) {
                    $('#QuantityPeo'+rowId).val(data.qty);

                },
                error: function(error){
                    console.log('error');
                }
            });

        }

        function removeFromCart(rowId){
            var thisprice= $('#pricetotal'+rowId).html();
            var subPrice= $('#subtotalprice').html();
            var updatesubprice =subPrice-thisprice;
            $('#subtotalprice').html(updatesubprice);

            //order subtotal
            $('#ordersubtotalprice').html(updatesubprice);

            var deliverycharge = $('#selectCourier').val();
            var totalprice =updatesubprice-(-deliverycharge);
            $('#totalamount').html(totalprice);
            //cart number
            var quantity = $('#QuantityPeo'+rowId).val();
            var prevcart = $('#cartNumber').html();
            var cartUpdate = prevcart-quantity;
            $('#cartNumber').html(cartUpdate);

            $.ajax({
                type:'DELETE',
                url:'remove-cart',
                data:{
                        _token:'{{ csrf_token() }}',
                        rowId: rowId,
                    },

                success: function (data) {
                    $('#productcart'+rowId).css('display','none');
                    $('#prod'+rowId).css('display','none');
                    if(data == 'empty'){
                        location.reload();
                    }
                },
                error: function(error){
                    console.log('error');
                }
            });
        }

        window.onload = (event) => {
            var subPrice= $('#subtotalprice').html();
            //total price part
            var deliverycharge = $('#selectCourier').val();
            var totalprice =subPrice-(-deliverycharge);
            $('#totalamount').html(totalprice)

        };

    </script>

    <script type="text/javascript">
        (function(){
        $('.from-prevent-multiple-submits').on('submit', function(){
            $('.from-prevent-multiple-submits').attr('disabled','true');
        })
        })();
    </script>
@endsection
