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
    #lgbtnv{
        background: linear-gradient(45deg, #3b9634 0%, #10e345 100%);
        box-shadow: 0px 4px 4px 0px rgba(0, 0, 0, 0.25) inset;
        font-size: 20px;
        border-radius: 6px;
        border: #10e345;
    }

    @media only screen and (max-width: 600px) {
        .nav-item{
            font-size: 14px
        }
    }
</style>

@php
    $sliders=App\Models\Addbanner::where('status','Active')->get();
@endphp

<div class="body-content">
    <div class="container">
        <div class="sign-in-page m-b-10">
            <div class="row">
                <div class="m-auto col-lg-6 col-12">
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
                <!-- Sign-in -->
                <div class="col-md-3 col-sm-2 create-new-account">
                    <!-- Empty space for alignment -->
                </div>
                <div class="mt-4 mb-4 col-md-6 col-sm-8 sign-in">
                    <div class="card" style="padding: 0px 18px;border-radius: 0px;">
                        <p class="pt-4 text-center"> <b>Hello Dear, Welcome to our Reseller Point.</b> </p>

                        <form class="register-form outer-top-xs" method="POST" action="{{ url('login') }}"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label class="info-title" for="exampleInputEmail1">Phone<span>*</span></label>
                                <input type="text" name="email" class="form-control unicase-form-control text-input"
                                    id="exampleInputEmail1" required>
                            </div>
                            <div class="form-group">
                                <label class="info-title" for="exampleInputPassword1">Password <span>*</span></label>
                                <input type="password" name="password" class="form-control unicase-form-control text-input"
                                    id="exampleInputPassword1" required>
                            </div>
                            <div class="radio outer-xs">
                                <label>
                                    <input type="radio" name="optionsRadios" id="optionsRadios2" value="option2">Remember me!
                                </label>
                            </div>
                            <button type="submit" id="lgbtn"
                                class="btn-block btn-upper btn btn-dark checkout-page-button" >Login</button>
                            <div class="text-right radio outer-xs" style="margin-top: 10px;margin-bottom:10px;">
                                <a href="{{ url('forgot-password') }}" class="forgot-password pull-right"
                                    style="color: #07b5fc">Forgot your Password?</a>
                            </div>
                        </form>

                        <h4 class="text-center" style="margin-bottom:20px;">
                            <a href="{{ url('register') }}" class="btn btn-info btn-sm text-light" id="lgbtn">Become a Reseller</a>
                        </h4>

                    </div>
                </div>
                <!-- Sign-in -->

                <!-- create a new account -->
                <div class="col-md-3 col-sm-2 create-new-account">
                    <!-- Empty space for alignment -->
                </div>
                <!-- create a new account -->
            </div><!-- /.row -->
            <div class="row">
                <div class="m-auto col-lg-6 col-12">
                    <a href="{{ url('support') }}">
                        <img src="{{ asset('public/support.png') }}" alt="" style="width: 100%;margin-bottom: 60px;">
                    </a>
                </div>
            </div>
        </div><!-- /.sigin-in-->
    </div><!-- /.sigin-in-->
</div>

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
</script>
@endsection