@extends('frontend.master')

@section('maincontent')
@section('meta')
    <title>{{\App\Models\Basicinfo::first()->title}}-Login</title>
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
        background: linear-gradient(45deg, #da058d 0%, #886fdc 100%);
        box-shadow: 0px 4px 4px 0px rgba(0, 0, 0, 0.25) inset;
        font-size: 20px;
        border-radius: 6px;
    }
</style>
@php
    $sliders=App\Models\Addbanner::where('status','Active')->get();
@endphp
<div class="body-content">
    <div class="container">
        <div class="sign-in-page m-b-10">
            <div class="row">
                <div class="col-lg-6 col-12 m-auto">
                    <div id="header-carousel" class="owl-carousel">
                        @forelse ($sliders as $slider)
                            <div class="item" id="carouselitem">
                                <a href="{{ $slider->add_link }}"><img class="img-fluid"
                                        src="{{ asset($slider->add_image) }}" alt="{{ $slider->slider_alt }}"></a>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- create a new account -->
                <div class="col-md-3 col-sm-2 create-new-account">

                </div>
                <!-- Sign-in -->
                <div class="col-md-6 col-sm-8 sign-in mb-4 mt-4">
                    <div class="card" style="padding: 0px 18px;border-radius: 6px;">
                        <h4 class="checkout-subtitle m-0 pb-2 pt-4 text-center"> <b>Fill Up The Form & Become a Reseller</b> </h4>
                        <form class="register-form outer-top-xs" method="POST" action="{{ url('register') }}" role="form">
                            @csrf
                            <div class="form-group">
                                <label class="info-title" for="exampleInputEmail1">Name <span>*</span></label>
                                <input type="text" name="name" class="form-control unicase-form-control text-input"
                                    required>
                            </div>
                            <div class="form-group">
                                <label class="info-title" for="exampleInputEmail2">Email / Phone <span>*</span></label>
                                <input type="text" name="email" class="form-control unicase-form-control text-input"
                                    required>
                            </div>

                            <div class="form-group">
                                <input type="hidden" name="phone" class="form-control unicase-form-control text-input" >
                            </div>
                            <div class="form-group">
                                <label class="info-title" for="exampleInputEmail2">Refer Code <span>*</span></label>
                                <input type="text" name="refer_by" value="{{ $referralID }}" readonly class="form-control unicase-form-control text-input"
                                    required>
                            </div>
                            <div class="form-group">
                                <label class="info-title" for="exampleInputEmail1">Password <span>*</span></label>
                                <input type="password" name="password" class="form-control unicase-form-control text-input"
                                    id="password" required>
                            </div>
                            <div class="form-group mb-4">
                                <label class="info-title" for="exampleInputEmail1">Confirm Password <span>*</span></label>
                                <input type="password" onkeyup="checkpass()" id="confirm_password"
                                    class="form-control unicase-form-control text-input m-0" required>
                                <small id="confirm_passwordtextmatch" style="color: deepskyblue;display:none">Password
                                    Matched</small>
                                <small id="confirm_passwordtext" style="color: red;display:none">Password Not
                                    Matched</small>
                            </div>
                            <button type="submit" id="lgbtn"
                                class="btn-block btn-upper btn btn-dark checkout-page-button" >Registration</button>
                        </form>
                        <h4 class="text-right" style="margin-top: 6px;margin-bottom:20px;">
                            <p style="font-size: 16px;margin-bottom:6px;color: #07b5fc">Already have an account? </p>
                            <a href="{{ url('login') }}"  class="btn btn-info btn-sm text-light" id="lgbtn"> <b>Login Now</b>
                            </a>
                        </h4>
                    </div>
                </div>
                <!-- Sign-in -->

                <!-- create a new account -->
                <div class="col-md-3 col-sm-2 create-new-account">

                </div>
                <!-- create a new account -->
            </div><!-- /.row -->
            <div class="row">
                <div class="col-lg-6 col-12 m-auto">
                    <img src="{{ asset('public/support.png') }}" alt="" style="width: 100%;">
                </div>
            </div>
        </div><!-- /.sigin-in-->
    </div><!-- /.row -->
</div><!-- /.sigin-in-->

<script>
    $(document).ready(function(){
        $('#header-carousel').owlCarousel({
            loop: true,
            margin: 10,
            nav: true,
            autoplay: true,
            smartSpeed: 1000,
            responsive: {
                0:{
                    items:1
                },
                576:{
                    items:1
                },
                768:{
                    items:1
                },
                992:{
                    items:1
                },
                1200:{
                    items:1
                }
            }

        });
    });
    function checkpass() {
        var pass = $('#password').val();
        var con_pass = $('#confirm_password').val();
        if (pass == con_pass) {
            $('#confirm_passwordtext').css('display', 'none');
            $('#confirm_passwordtextmatch').css('display', 'inline');
            $('#submit-button').prop('disabled', false);
        } else {
            $('#confirm_password').focus();
            $('#confirm_passwordtext').css('display', 'inline');
            $('#confirm_passwordtextmatch').css('display', 'none');
            $('#submit-button').prop('disabled', true);
        }
    }

    function checkunick() {
        var username = $('#username').val();
        $.ajax({
            type: "GET",
            url: "{{ url('check/username') }}/" + username,

            success: function(data) {

                if (data == 'taken') {
                    $('#username').focus();
                    $('#submit-button').prop('disabled', true);
                    $('#avaliableusername').css('display', 'none');
                    $('#unavaliableusername').css('display', 'inline');
                } else {
                    $('#avaliableusername').css('display', 'inline');
                    $('#unavaliableusername').css('display', 'none');
                    $('#submit-button').prop('disabled', false);
                }
            }
        });
    }
</script>
@endsection
