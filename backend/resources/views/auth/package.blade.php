@extends('frontend.master')

@section('maincontent')
@section('meta')
    <title>{{\App\Models\Basicinfo::first()->title}}-Package</title>
    <meta name="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta name="keywords" content="{{\App\Models\Basicinfo::first()->meta_keyword}}">

    <meta property="og:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />

    <meta itemprop="name" content="{{\App\Models\Basicinfo::first()->title}}">
    <meta itemprop="description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta itemprop="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">

    <meta property="og:url" content="{{url('/')}}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{\App\Models\Basicinfo::first()->title}}">
    <meta property="og:description" content="{{\App\Models\Basicinfo::first()->meta_description}}">
    <meta property="og:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
    <meta property="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />
    <meta property="url" content="{{url('/')}}">
    <meta name="robots" content="index, follow" />
    <meta itemprop="image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
    <meta property="twitter:card" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}" />
    <meta property="twitter:title" content="{{\App\Models\Basicinfo::first()->title}}" />
    <meta property="twitter:url" content="{{url('/')}}">
    <meta name="twitter:image" content="{{ url(\App\Models\Basicinfo::first()->meta_image) }}">
@endsection
@section('subcss')

    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>


    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>

@endsection

<style>
    #carouselitem {
        height: 200px;
    }
    #header-carousel img {
        width: 100%;
        height: auto;
        object-fit: cover;
        border-radius: 6px;
    }
    #carouselitem {
        height: auto;
        padding-top: 10px;
    }
    #lgbtn{
        background: linear-gradient(45deg, #8000FF 0%, #028FA4 100%);
        box-shadow: 0px 4px 4px 0px rgba(0, 0, 0, 0.25) inset;
        font-size: 20px;
        border-radius: 6px;
    }
    #packagetab{
        border-radius: 15px;
        background: #0ABB75;
        box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.25);
    }
    #indtab{
        border-radius: 25.5px;
        background: #ECEFF7;
        box-shadow: 0px 0px 9px 0px rgba(0, 0, 0, 0.25);
    }
    #checkimg{
        height: 18px;
        width: 18px;
    }
    .sslcommerz-btn {
        background: linear-gradient(45deg, #FF6B00 0%, #FF8C00 100%);
        color: white;
        width: 100%;
        font-size: 20px !important;
        display: inline;
        padding: 12px;
        border: 1px solid;
        border-radius: 8px;
        font-weight: bold;
        margin-top: 10px;
        cursor: pointer;
    }
    .sslcommerz-btn:hover {
        background: linear-gradient(45deg, #FF8C00 0%, #FF6B00 100%);
    }
</style>

<div class="body-content">
    <div class="container">
        <div class="sign-in-page m-b-10">
            <div class="row">
                <!-- Sign-in -->
                <div class="col-md-3 col-sm-2 create-new-account">

                </div>
                <div class="col-md-6 col-sm-8 sign-in mb-4 mt-4">
                    @if (isset($invoice))
                        <div style="border-radius: 6px;">
                            @if ($invoice->status=='Paid')
                                <p class="pt-4 text-center"> ধন্যবাদ Selfshop.com.bd এর সাথে থাকার জন্য। আমরা আপনার পেমেন্ট টি পেয়েছি এবং আপনার একাউন্ট টি এক্টিভ করে দিয়েছি। নিচের " Start " বাটনে ক্লিক করে আপনার একাউন্টে প্রবেশ করুন। </p>
                            @else
                                <p class="pt-4 text-center">ধন্যবাদ আমাদের পছন্দের প্যাকেজটি সিলেক্ট করার জন্য।
আপনার জন্য একটি Invoice ID জেনারেট করা হয়েছে।
নিচে থাকা Pay Now বাটনে ক্লিক করে আপনার পছন্দের যেকোনো Payment Method সিলেক্ট করে পেমেন্ট সম্পন্ন করুন।
পেমেন্ট সফল হলেই আপনার অ্যাকাউন্টটি সঙ্গে সঙ্গে একটিভ হয়ে যাবে। </p>
                            @endif

                            <div class="card mt-3" id="packagetab" style="border-radius: 15px;border: 1px solid #613EEA;background: #F4F4F4;padding: 8px;border-radius: 6px;">
                                @if ($invoice->status=='Paid')
                                    <img src="{{asset('public/paid.png')}}" id="smw" style="width:100px">
                                    <h2 class="h4 pb-3" style="color:green;text-align:center"> INVOICE ID: {{$invoice->invoiceID}}
                                    <br><a href="{{ url('/') }}" class="btn btn-success mt-3" style="color: white;border-radius: 8px;font-weight: bold;" >Start</a> </h2>
                                    <p class="m-0 p-0 mb-2 text-center">Thanks for your payment.You are good to go now.</p>
                                @else
                                    <h2 class="h4 pb-3" style="color:red;text-align:center"> INVOICE ID: {{$invoice->invoiceID}}
                                    <input type="text" value="{{ $invoice->invoiceID }}" id="email{{$invoice->invoiceID}}" hidden >
                                    <button class="btn btn-primary" style="color: white;border-radius: 8px;font-weight: bold;" id="copyemail" data-id="{{ $invoice->invoiceID }}">COPY</button></h2>
                                    <p class="m-0 p-0 mb-2 text-center">Thanks for your order.Please copy the Invoice ID.</p>

                                    <!-- SSLCommerz Payment Form -->
<form id="sslcommerzPackageForm" method="POST" action="{{ route('package.payment') }}">
    @csrf
    <input type="hidden" name="package_id" value="{{ $invoice->package_id ?? '' }}">
    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
    <input type="hidden" name="amount" value="{{ $invoice->payable_amount }}">
    <input type="hidden" name="invoiceID" value="{{ $invoice->invoiceID }}">
   
    <!-- SSLCommerz Payment Button -->
    <div style="width: 100%;float: left;text-align:center">
        <button type="submit" id="sslcommerz_button" class="sslcommerz-btn">
            Pay Now with Online (bKash,Nagad,rocket etc)
        </button>
    </div>
</form>
                                @endif
                            </div>
                        </div>
                    @else
                        <div style="border-radius: 6px;">
                            <p class="pt-4 text-center"> ধন্যবাদ, রেজিস্ট্রেশন সম্পন্ন করার জন্য। অনুগ্রহ করে আপনার পছন্দের রিসেলার প্যাকেজ টি সিলেক্ট করে পেমেন্ট করুন। </p>

                            <div class="cardinfo">
                                <!-- Package cards will be loaded here via AJAX -->
                            </div>
                        </div>
                    @endif
                </div>
                <!-- Sign-in -->

                <!-- create a new account -->
                <div class="col-md-3 col-sm-2 create-new-account">

                </div>
                <!-- create a new account -->
            </div><!-- /.row -->
            <div class="row">
                <div class="col-lg-6 col-12 m-auto">
                    <a href="{{ url('support') }}">
                        <img src="{{ asset('public/support.png') }}" alt="" style="width: 100%;margin-bottom: 60px;">
                    </a>
                </div>
            </div>
        </div><!-- /.sigin-in-->
    </div><!-- /.row -->
</div><!-- /.sigin-in-->
<input type="text" name="silver" id="silver" value="{{App\Models\Package::where('status','Active')->first()->id}}" hidden>

<script>
    $(document).ready(function() {
        var pack_id=$('#silver').val();
        loadpack(pack_id);

        $(document).on('click', '#copyemail', function(e) {
            var id = $(this).attr("data-id");
            var divid = 'email' + id;
            var copyText = document.getElementById(divid);

            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard
                .writeText(copyText.value)
                .then(() => {
                    alert("Successfully copied Invoice ID");
                })
                .catch(() => {
                    alert("something went wrong");
                });
        });
    });

    function loadpack(id){
        $.ajax({
            type: 'GET',
            url: '{{ route('admin.loadmenu') }}',
            data:{
                pack_id:id
            },
            success: function(response) {
                $('.cardinfo').empty().append(response);
            },
            error: function(error) {
                console.log('error');
            }
        });
    }
</script>
{{-- //sweetalert --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"
    integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

@endsection