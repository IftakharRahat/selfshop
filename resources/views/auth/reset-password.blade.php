@extends('frontend.master')

@section('maincontent')
@section('meta')
    <title>{{\App\Models\Basicinfo::first()->title}}-Reset Password</title>
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
        background: linear-gradient(45deg, #8000FF 0%, #028FA4 100%);
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
                <!-- Sign-in -->
                <div class="col-md-3 col-sm-2 create-new-account">

                </div>
                <div class="col-md-6 col-sm-8 sign-in mb-4 mt-4">
                    <div class="card" style="padding: 0px 18px;border-radius: 6px;">
                        <div class="d-flex justify-content-center">
                            <img src="{{asset('public/reset.png')}}" style="    width: 50%;">
                        </div>
                        <h4 class="mt-2">Set Your new Password</h4>
                        <div class="mb-4 pt-3 text-sm text-gray-600">
                            {{ __('Please type your new Password & Confirm it.') }}
                        </div>

                        <!-- Session Status -->
                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <!-- Validation Errors -->
                        <x-auth-validation-errors class="mb-0" :errors="$errors" />

                         <form method="POST" action="{{ route('password.update') }}">
                            @csrf

                            <!-- Phone Address -->
                            <div class="form-group" hidden>
                                <x-label for="phone" :value="__('Phone')" />

                                <x-input id="phone" class="form-control block mt-1 w-full" type="text" name="email" :value="old('phone', Session::get('phone'))" required autofocus />
                            </div>
                            <div class="form-group">
                                <x-label for="phone" :value="__('OTP')" />

                                <x-input id="otp" class="form-control block mt-1 w-full" type="text" name="otp" required autofocus />
                            </div>
                            <!-- Password -->
                            <div class="form-group mt-4">
                                <x-label for="password" :value="__('Password')" />

                                <x-input id="password" class="form-control block mt-1 w-full" type="password" name="password" required />
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group mt-4">
                                <x-label for="password_confirmation" :value="__('Confirm Password')" />

                                <x-input id="password_confirmation" class="form-control block mt-1 w-full"
                                                    type="password"
                                                    name="password_confirmation" required />
                            </div>

                            <div class="flex items-center text-center mt-3 mb-3">
                                <x-button class="btn btn-info">
                                    {{ __('Reset Password') }}
                                </x-button>
                            </div>
                        </form>

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
                    <a href="{{ url('support') }}">
                        <img src="{{ asset('public/support.png') }}" alt="" style="width: 100%;margin-bottom: 60px;">
                    </a>
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
</script>
@endsection
