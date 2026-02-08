@extends('frontend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-Checkout
@endsection
@section('subcss')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
@endsection

<style>
    #couponoption { display: none; }
    #coupontext { display: none; }
    #coupontext1 { display: none; }
    .spinner { display: none; }
    
    @media only screen and (min-width: 768px) {
        #proName { font-size: 18px; }
        #proPrice { font-size: 18px; padding: 6px; padding-left: 0; }
        .input-number { height: 39px; }
        #proDelCart { width: 30px; padding-top: 2px; font-size: 20px; }
        #proImgDiv { max-width: 110px; }
        #proImg { max-width: 100px; }
    }
    
    @media only screen and (max-width: 767px) {
        .input-group--style-2 .input-group-btn>.btn {
            background: 0 0; border-color: #e6e6e6; color: #818a91; 
            font-size: 8px; padding-top: 6px; padding-bottom: 6px; cursor: pointer;
        }
        .input-number { height: 26px; }
        #proDelCart { width: 30px; font-size: 18px; }
        #proImg { max-width: 50px; }
    }
</style>

@php
    $shippingin = \App\Models\Basicinfo::first();
@endphp

{{-- No Cart --}}
@if (!Session::has('cart') || Cart::count() == 0)
    <div class="container pb-5 mb-sm-4">
        <div class="pt-5">
            <div class="py-3 card mt-sm-3" style="min-height: 309px;">
                <div class="text-center card-body">
                    <h2 class="pb-3 h4">Dear Customer, No items have been added to your cart. Please, select the product of your choice and place your order at Selfshop.com.bd. Please call directly for any need:- <a href="tel:{{App\Models\Basicinfo::first()->phone_one}}">{{App\Models\Basicinfo::first()->phone_one}}</a></h2>
                    <a class="mt-3 btn btn-primary" href="{{ url('/') }}" style="color:white">Go To Home Page</a>
                </div>
            </div>
        </div>
    </div>
@else
    <br>
    <section class="section-content padding-y bg slidetop">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <aside class="mb-4 card">
                        <article class="card-body" style="padding: 0.5rem;">
                            <header class="mb-4">
                                <p class="text-center" style="font-size: 16px;">To confirm the order, enter the Customer name, Customer address, Phone, and click on the Confirm Order button<span class="text-danger">অর্ডার কনফার্ম করুন</span> বাটনে ক্লিক করুন</p>
                            </header>
                            
                            {{-- Online Payment Form (SSLCommerz Hosted) --}}
                            <form id="onlinePaymentForm" action="{{ route('sslcommerz.payment') }}" method="POST" style="display: none;">
                                @csrf
                                <input type="hidden" name="customer_name" id="ssl_customer_name">
                                <input type="hidden" name="customer_phone" id="ssl_customer_phone">
                                <input type="hidden" name="customer_email" id="ssl_customer_email">
                                <input type="hidden" name="customer_address" id="ssl_customer_address">
                                <input type="hidden" name="amount" id="ssl_total_amount">
                                <input type="hidden" name="delivery_charge" id="ssl_delivery_charge">
                                <input type="hidden" name="sub_total" id="ssl_sub_total">
                                <input type="hidden" name="customer_note" id="ssl_customer_note">
                                <input type="hidden" name="user_id" id="ssl_user_id">
                                <input type="hidden" name="cart_data" id="ssl_cart_data">
                            </form>
                            
                            {{-- Main Form (Account Balance) --}}
                            <form id="mainForm" action="{{ url('press/order') }}" method="POST" class="from-prevent-multiple-submits">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-sm-12">
                                        <label>Customer Name</label>
                                        <input type="text" id="customerName" name="customerName" placeholder="Customer Name" required class="form-control">
                                    </div>
                                    
                                    @if (Auth::id())
                                        <input type="hidden" id="user_id" name="user_id" value="{{ Auth::guard('web')->user()->id }}">
                                    @endif
                                    
                                    <div class="form-group col-sm-12">
                                        <label>Customer Address</label>
                                        <input type="text" id="customerAddress" name="customerAddress" placeholder="Customer Address" required class="form-control">
                                    </div>
                                    
                                    <div class="form-group col-sm-12">
                                        <label>Customer Phone</label>
                                        <input type="tel" minlength="10" id="customerPhone" name="customerPhone" required class="form-control" placeholder="Customer Phone">
                                    </div>
                                    
                                    <textarea id="ordersubtotalprice" name="subTotal" cols="10" rows="5" hidden>{{ Cart::subtotalFloat() }}</textarea>
                                    
                                    <div class="form-group col-sm-12">
                                        <label>Select Area</label>
                                        <select id="deliveryCharge" name="deliveryCharge" class="form-control" onchange="setdeliverychargr()">
                                            @if (isset($product->inside_dhaka) && isset($product->outside_dhaka))
                                                <option value="{{ $product->inside_dhaka }}">Inside Dhaka ({{ $product->inside_dhaka }})</option>
                                                <option value="{{ $product->outside_dhaka }}">Outside Dhaka ({{ $product->outside_dhaka }})</option>
                                            @else
                                                <option value="{{ $shippingin->inside_dhaka_charge }}">Inside Dhaka ({{ $shippingin->inside_dhaka_charge }})</option>
                                                <option value="{{ $shippingin->near_dhaka_charge }}">Surrounding Dhaka ({{ $shippingin->near_dhaka_charge }})</option>
                                                <option value="{{ $shippingin->outside_dhaka_charge }}">Outside Dhaka ({{ $shippingin->outside_dhaka_charge }})</option>
                                            @endif
                                        </select>
                                    </div>
                                    
                                    <div class="form-group col-sm-12">
                                        <label>Custom Note</label>
                                        <textarea name="customerNote" id="customerNote" cols="10" rows="2" class="form-control"></textarea>
                                    </div>
                                    
                                    <div class="pb-4 text-center section-tab check-mark-tab" id="paysection" style="width: 100%;">
                                        <p style="color: deeppink; font-weight: bold;">Please pay the delivery charge before confirming the order.</p>
                                        <ul class="m-0 nav nav-tabs justify-content-around" id="myTab" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link" id="coin-tab" data-toggle="tab" href="#coin" onclick="showbtn('orderConfirmCoin')" role="tab" aria-controls="coin" style="padding: 8px;" aria-selected="true">
                                                    <img src="{{ asset('public/payment/1.png') }}" style="width: 65px;" alt="">
                                                    <span class="pt-2 d-block">Account Balance</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="paypal-tab" data-toggle="tab" href="#paypal" onclick="showbtn('sslczPayBtn')" role="tab" aria-controls="paypal" style="padding: 8px;" aria-selected="false">
                                                    <img src="{{ asset('public/payment/2.png') }}" style="width: 65px;" alt="">
                                                    <span class="pt-2 d-block">Online Pay</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mt-3 form-group">
                                    <label>
                                        <input type="checkbox" name="agree_terms" id="agree_terms" required>
                                        I have read and agree to the <a href="{{url('/venture/terms_codition')}}">Terms & Conditions</a>, <a href="{{url('/venture/privacy-policy')}}">Privacy Policy</a>, and <a href="{{url('venture/return-refund-policy')}}">Return & Refund Policy</a>.
                                    </label>
                                </div>
                                
                                <input type="hidden" name="blance_from" id="blance_from" value="from_account">
                                
                                <div class="row">
                                    <div class="text-center col-12">
                                        {{-- Account Balance Tab --}}
                                        <div class="tab-pane fade show active" id="coin" role="tabpanel" aria-labelledby="coin-tab">
                                            @if (Auth::id())
                                                @if (Auth::user()->account_balance > 70)
                                                    <div class="mb-3 d-flex">
                                                        <i class="fas fa-check-circle" style="font-size: 22px; margin-top: 3px; margin-right: 8px; color: green;"></i>
                                                        <label style="font-size: 20px;">You have <span style="color: green; font-weight: bold">{{ Auth::user()->account_balance }}</span> TK for payment.</label>
                                                    </div>
                                                    <button type="submit" id="orderConfirmCoin" class="btn btn-lg btn-styled from-prevent-multiple-submits btn-base-1 btn-block btn-icon-left strong-500 hov-bounce hov-shaddow buy-now" style="background: green; color: white; font-size: 20px !important; width: 100%;">
                                                        Confirm Order
                                                    </button>
                                                @else
                                                    <div class="mb-3 d-flex">
                                                        <i class="fas fa-check-circle" style="font-size: 22px; margin-top: 3px; margin-right: 8px; color: gray;"></i>
                                                        <label style="font-size: 20px; color: gray;">You don't have enough balance. Please keep at least 70 TK before payment.</label>
                                                    </div>
                                                    <button type="submit" id="orderConfirmCoin" disabled class="btn btn-lg btn-styled from-prevent-multiple-submits btn-base-1 btn-block btn-icon-left strong-500 hov-bounce hov-shaddow buy-now" style="background: green; color: white; font-size: 20px !important; width: 100%;">
                                                        Confirm Order
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                        
                                        {{-- Online Payment Tab --}}
                                        <div class="tab-pane fade" id="paypal" role="tabpanel" aria-labelledby="paypal-tab">
                                            <div class="contact-form-action">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="text-center btn-box">
                                                            <button type="button" id="sslczPayBtn" class="btn btn-lg btn-styled btn-base-1 btn-block btn-icon-left strong-500 hov-bounce hov-shaddow" style="background: green; color: white; font-size: 20px !important; width: 100%; padding: 8px; border: 1px solid;">
                                                                Confirm Order (Online Payment)
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </article>
                    </aside>
                </div>
                
                {{-- Order Summary --}}
                <div class="col-md-6 orderDetails">
                    <aside class="card">
                        <article class="card-body">
                            <header class="mb-4">
                                <h4 class="card-title" style="font-size: 16px;">Customer Orders</h4>
                            </header>
                            <div class="row">
                                <table class="table border-bottom">
                                    @forelse ($cartProducts as $cartProduct)
                                        <tr class="cart-item" id="productcart{{ $cartProduct->rowId }}">
                                            <td class="product-image" id="proImgDiv">
                                                <a href="#" class="mr-3">
                                                    <img class="ls-is-cached lazyloaded" src="{{ asset($cartProduct->options['image']) }}" id="proImg" style="max-width: 80px;">
                                                </a>
                                            </td>
                                            <td class="product-total" hidden>
                                                <span>৳ <span id="pricetotal{{ $cartProduct->rowId }}" class="price">{{ $cartProduct->qty * $cartProduct->price }}</span></span>
                                            </td>
                                            <td class="product-name">
                                                <span class="pr-4 d-block w-100" id="proName">{{ $cartProduct->name }}</span>
                                                <div class="ext w-100">
                                                    <div class="price">
                                                        <span class="pr-3 d-block" id="proPrice">৳ {{ $cartProduct->price }}</span>
                                                    </div>
                                                    <div class="qtyinfo">
                                                        <div class="pr-4 input-group input-group--style-2" style="width: 140px; float: left;">
                                                            <span class="input-group-btn">
                                                                <button class="btn btn-number" onclick="remnum('{{ $cartProduct->rowId }}')" type="button">
                                                                    <i class="fas fa-minus"></i>
                                                                </button>
                                                            </span>
                                                            <input type="text" name="quantity[{{ $cartProduct->id }}]" id="QuantityPeo{{ $cartProduct->rowId }}" class="form-control input-number" placeholder="1" value="{{ $cartProduct->qty }}" min="1" max="10" onchange="updateQuantity('{{ $cartProduct->rowId }}', this)">
                                                            <span class="input-group-btn">
                                                                <button class="btn btn-number" onclick="updatenum('{{ $cartProduct->rowId }}')" type="button">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                        <a type="button" style="width: 30px; font-size: 18px;" onclick="removeFromCart('{{ $cartProduct->rowId }}')" class="pl-4 text-right">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <input type="hidden" name="productP" id="priceOf{{ $cartProduct->rowId }}" value="{{ $cartProduct->price }}">
                                        </tr>
                                    @empty
                                    @endforelse
                                </table>
                            </div>
                        </article>
                        
                        <article class="card-body border-top">
                            <dl class="row">
                                <dt class="col-8">Sub Total:</dt>
                                <dd class="text-right col-4"><strong>৳ <span id="subtotalprice">{{ Cart::subtotalFloat() }}</span></strong></dd>
                                <dt class="col-8">Delivery Charge:</dt>
                                <dd class="text-right col-4 text-danger"><strong>৳ <span id="dinamicdalivery">{{ $shippingin->inside_dhaka_charge }}</span></strong></dd>
                                <dt class="col-8">Total:</dt>
                                <dd class="text-right col-4"><strong class="h5 text-dark">৳ <span id="totalamount"></span></strong></dd>
                            </dl>
                        </article>
                    </aside>
                </div>
            </div>
        </div>
    </section>
    <br>
@endif

@section('subjs')
@endsection

<script>
    function showbtn(idname) {
        if(idname === 'sslczPayBtn') {
            $('#orderConfirmCoin').hide();
            $('#sslczPayBtn').show();
            $('#paymentType').val(3);
            $('#blance_from').val('online_payment');
        } else if(idname === 'orderConfirmCoin') {
            $('#sslczPayBtn').hide();
            $('#orderConfirmCoin').show();
            $('#paymentType').val(1);
            $('#blance_from').val('from_account');
        }
    }

    $(document).ready(function() {
        $('#sslczPayBtn').hide();
        
        // Initialize total amount
        var subPrice = $('#subtotalprice').html();
        var deliverycharge = $('#deliveryCharge').val();
        var totalprice = parseFloat(subPrice) + parseFloat(deliverycharge);
        $('#totalamount').html(totalprice.toFixed(2));
        
        // Handle SSLCommerz button click
        $('#sslczPayBtn').click(function(e) {
            e.preventDefault();
            
            if(!validateForm()) {
                return false;
            }
            
            // Prepare data for SSLCommerz hosted payment
            prepareSSLCommerzData();
            
            // Submit the hidden SSLCommerz form
            $('#onlinePaymentForm').submit();
        });
        
        // Handle main form submission (account balance)
        $('#mainForm').submit(function(e) {
            if($('#blance_from').val() === 'online_payment') {
                e.preventDefault();
                return false;
            }
            
            // For account balance, validate and allow submission
            if(!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading
            $(this).find('button[type="submit"]').prop('disabled', true).html('Processing...');
        });
        
        // Initialize toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
    });

    function validateForm() {
        var isValid = true;
        
        if(!$('#customerName').val()) {
            toastr.error('Please enter customer name');
            $('#customerName').focus();
            isValid = false;
        }
        
        if(!$('#customerPhone').val()) {
            toastr.error('Please enter customer phone');
            $('#customerPhone').focus();
            isValid = false;
        }
        
        if(!$('#customerAddress').val()) {
            toastr.error('Please enter customer address');
            $('#customerAddress').focus();
            isValid = false;
        }
        
        if(!$('#agree_terms').is(':checked')) {
            toastr.error('Please agree to terms and conditions');
            $('#agree_terms').focus();
            isValid = false;
        }
        
        return isValid;
    }

function prepareSSLCommerzData() {
    // Get cart data
    var cartData = {!! json_encode(Cart::content()->toArray()) !!};
    
    // Calculate totals
    var subTotal = parseFloat($('#ordersubtotalprice').val());
    var deliveryCharge = parseFloat($('#deliveryCharge').val());
    var totalAmount = subTotal + deliveryCharge;
    
    // Get user email correctly
    var userEmail = '{{ Auth::check() ? Auth::user()->email : "customer@selfshop.com" }}';
    
    // If user is not logged in, use a placeholder email
    if (userEmail === '' || userEmail.includes('Auth::user()->email')) {
        userEmail = 'customer@selfshop.com';
    }
    
    // If it's still a phone number (happens sometimes), create a proper email
    if (userEmail.match(/^[0-9]+$/)) {
        userEmail = 'customer' + Date.now() + '@selfshop.com';
    }
    
    // Populate hidden form for SSLCommerz
    $('#ssl_customer_name').val($('#customerName').val());
    $('#ssl_customer_phone').val($('#customerPhone').val());
    $('#ssl_customer_email').val(userEmail); // Fixed: Use proper email
    $('#ssl_customer_address').val($('#customerAddress').val());
    $('#ssl_total_amount').val(totalAmount.toFixed(2));
    $('#ssl_delivery_charge').val(deliveryCharge);
    $('#ssl_sub_total').val(subTotal);
    $('#ssl_customer_note').val($('#customerNote').val());
    $('#ssl_user_id').val($('#user_id').val() || '0');
    $('#ssl_cart_data').val(JSON.stringify(cartData));
}
    function setdeliverychargr() {
        var deliverycharge = $('#deliveryCharge').val();
        $('#dinamicdalivery').html(deliverycharge);
        
        var subprice = $('#subtotalprice').html();
        var totalprice = parseFloat(subprice) + parseFloat(deliverycharge);
        $('#totalamount').html(totalprice.toFixed(2));
    }

    function updatenum(id) {
        var num = $('#QuantityPeo' + id).val();
        var fv = Number(num) + 1;
        if (fv > 9) {
            return;
        }
        
        $('#QuantityPeo' + id).val(fv);
        $.ajax({
            type: 'POST',
            url: '{{ url("update-cart") }}',
            data: {
                _token: '{{ csrf_token() }}',
                rowId: id,
                qty: fv,
            },
            success: function(data) {
                $('#QuantityPeo' + id).val(data.qty);
                updateQuantity(id);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }

    function remnum(id) {
        var num = $('#QuantityPeo' + id).val();
        var fv = Number(num) - 1;
        if (fv < 1) {
            return;
        }
        
        $('#QuantityPeo' + id).val(fv);
        $.ajax({
            type: 'POST',
            url: '{{ url("update-cart") }}',
            data: {
                _token: '{{ csrf_token() }}',
                rowId: id,
                qty: fv,
            },
            success: function(data) {
                $('#QuantityPeo' + id).val(data.qty);
                updateQuantity(id);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }

    function updateQuantity(rowId) {
        var quantity = $('#QuantityPeo' + rowId).val();
        var price = $('#priceOf' + rowId).val();
        var producttotal = quantity * price;

        var prevPrice = $('#pricetotal' + rowId).html();
        if (producttotal > prevPrice) {
            var subPrice = $('#subtotalprice').html();
            var updatesubprice = parseFloat(subPrice) + parseFloat(price);
            $('#subtotalprice').html(updatesubprice.toFixed(2));
            $('#ordersubtotalprice').val(updatesubprice.toFixed(2));
        } else {
            var subPrice = $('#subtotalprice').html();
            var updatesubprice = parseFloat(subPrice) - parseFloat(price);
            $('#subtotalprice').html(updatesubprice.toFixed(2));
            $('#ordersubtotalprice').val(updatesubprice.toFixed(2));
        }
        
        var deliverycharge = $('#deliveryCharge').val();
        var totalprice = parseFloat(updatesubprice) + parseFloat(deliverycharge);
        $('#totalamount').html(totalprice.toFixed(2));

        $('#pricetotal' + rowId).html(producttotal);

        $.ajax({
            type: 'POST',
            url: '{{ url("update-cart") }}',
            data: {
                _token: '{{ csrf_token() }}',
                rowId: rowId,
                qty: quantity,
            },
            success: function(data) {
                $('#QuantityPeo' + rowId).val(data.qty);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }

    function removeFromCart(rowId) {
        var thisprice = $('#pricetotal' + rowId).html();
        var subPrice = $('#subtotalprice').html();
        var updatesubprice = parseFloat(subPrice) - parseFloat(thisprice);
        $('#subtotalprice').html(updatesubprice.toFixed(2));
        $('#ordersubtotalprice').val(updatesubprice.toFixed(2));

        var deliverycharge = $('#deliveryCharge').val();
        var totalprice = parseFloat(updatesubprice) + parseFloat(deliverycharge);
        $('#totalamount').html(totalprice.toFixed(2));

        $.ajax({
            type: 'POST',
            url: '{{ url("remove-cart") }}',
            data: {
                _token: '{{ csrf_token() }}',
                rowId: rowId,
            },
            success: function(data) {
                $('#productcart' + rowId).hide();
                if (data == 'empty') {
                    location.reload();
                }
            },
            error: function(error) {
                console.log('error');
            }
        });
    }
</script>
@endsection