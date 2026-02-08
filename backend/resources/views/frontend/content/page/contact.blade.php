@extends('frontend.master')

@section('maincontent')
    {{-- //contact us page --}}
    <?php

    $pagebanner =App\Models\Pagebanner::where('id',2)->where('status','Active')->first();

    ?>
    @if (isset($pagebanner->page_image))
        <section class="mb-2 my-hm-cat">
            <div class="">
                <div class="arrow-round gutters-5" >
                    <img src="{{asset($pagebanner->page_image)}}" alt="" style="height: 300px;width:100%">
                </div>
            </div>

        </section>
    @else

    @endif


    <section class="mb-4 my-hm-cat pt-4 mt-4">
        <div class="container">
            <br>
            <div class="row" style="text-align: center">
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-group wow fadeInUp animated" style="visibility: visible; animation-name: fadeInUp;">
                        <div class="contact-info-icon">
                            <img src="{{ asset('/') }}public/frontend/images/contact-info-icon-1.png" alt="contact info icon" draggable="false">
                        </div>
                        <h4 class="xs-title"><a href="#">Find us</a></h4>
                        <span>{!! \App\Models\Setting::get('address') !!}</span>
                    </div><!-- .contact-info-group END -->
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-group wow fadeInUp active animated" data-wow-duration="1.5s" style="visibility: visible; animation-duration: 1.5s; animation-name: fadeInUp;">
                        <div class="contact-info-icon">
                            <img src="{{ asset('/') }}public/frontend/images/contact-info-icon-2.png" alt="contact info icon" draggable="false">
                        </div>
                        <h4 class="xs-title"><a href="#">Make a Call</a></h4>
                        <span>{{ \App\Models\Setting::get('phone_number') }}</span>
                    </div><!-- .contact-info-group END -->
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-group wow fadeInUp animated" data-wow-duration="2s" style="visibility: visible; animation-duration: 2s; animation-name: fadeInUp;">
                        <div class="contact-info-icon">
                            <img src="{{ asset('/') }}public/frontend/images/contact-info-icon-3.png" alt="contact info icon" draggable="false">
                        </div>
                        <h4 class="xs-title"><a href="#">Send Mail</a></h4>
                        <a href="mailto:{{ \App\Models\Setting::get('email') }}" style="color: black">{{ \App\Models\Setting::get('email') }}</a>
                    </div><!-- .contact-info-group END -->
                </div>
            </div>
        </div>


    </section>

    <section class="xs-section-padding xs-bg-gray">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mx-auto">
                    <div class="xs-heading wow fadeIn animated" style="visibility: visible; animation-name: fadeIn;">
                        <h2 class="heading-sub-title">Have question?</h2>
                        <h3 class="heading-title">SEND <span style="color: {{ \App\Models\Setting::get('buttonColor') }}">A MESSAGE</span></h3>
                    </div><!-- .xs-heading END -->
                </div>
            </div><!-- .row END -->
            <div class="row">
                <div class="col-lg-8 mx-auto">

                    <div class="xs-form-group wow fadeInUp" style="visibility: visible; animation-name: none;">
                        <form action="" method="POST" id="form" class="xs-form">
                            <input type="hidden" name="_token" value="6ZKKLEXHW8kWyDeZdeDrda4Pc3Bki9Z5VzlfA7lC" autocomplete="off" autocorrect="off">                        <div class="form-group">
                                <input type="text" class="form-control" name="name" placeholder="Name" id="name" autocomplete="off" autocorrect="off">
                                <input type="email" class="form-control" name="email" placeholder="Email" id="email" autocomplete="off" autocorrect="off">
                                <input type="url" class="form-control" name="website" placeholder="Website" id="website" autocomplete="off" autocorrect="off">
                                <textarea name="message" placeholder="Question" id="message" class="form-control" cols="30" rows="10"></textarea>
                            </div>
                            <div class="xs-btn-wraper" style="text-align: center;">
                                <input type="button" class="btn btn-primary" style="background: {{ \App\Models\Setting::get('buttonColor') }}" id="submit" value="Submit Now" autocomplete="off" autocorrect="off">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- .container END -->
    </section>

    <style>
        .xs-heading .heading-title {
            font-size: 2.11765rem;
            font-weight: 700;
        }

        .contact-info-group.active {
            -webkit-box-shadow: 0px 3px 33.6px 1.4px rgb(0 0 0 / 10%);
            box-shadow: 0px 3px 33.6px 1.4px rgb(0 0 0 / 10%);
        }
        .contact-info-group:hover {
            -webkit-box-shadow: 0px 3px 33.6px 1.4px rgb(0 0 0 / 10%);
            box-shadow: 0px 3px 33.6px 1.4px rgb(0 0 0 / 10%);
        }


        .contact-info-group {
            padding: 27px 35px 35px;
            min-height: 275px;
            text-align: center;
            background-color: #FFFFFF;
            -webkit-transition: all 0.4s ease;
            -o-transition: all 0.4s ease;
            transition: all 0.4s ease;
        }
        .xs-bg-gray {
            background-color: #f8fafe;
        }

        .xs-section-padding {
            padding: 100px 0;
        }.xs-heading {
            margin-bottom: 75px;
            text-align: center;
        }
        .xs-form-group {
            padding: 0 48px;
        }

        .xs-form .form-control, .xs-form .select {
            background-color: #FFFFFF;
            border: 1px solid #efefef;
            border-radius: 0px;
            padding: 0 30px;
            margin-bottom: 20px;
            height: 57px;
            width: 100%;
            -webkit-transition: all 0.4s ease;
            -o-transition: all 0.4s ease;
            transition: all 0.4s ease;
        }


        .xs-btn-wraper .btn:last-child {
            margin-right: 0px;
        }
        .btn:not(:disabled):not(.disabled) {
            cursor: pointer;
        }
        .xs-btn-wraper .btn {
            margin-right: 15px;
        }
        .btn:not([class*=btn-outline]) {
            border: 0px;
        }
        .btn {
            border-radius: 1.61765rem;
            padding: 8px 36px;
            position: relative;
            letter-spacing: -.4px;
            overflow: hidden;
            -webkit-transition: all 0.6s;
            -o-transition: all 0.6s;
            transition: all 0.6s;
            z-index: 1;
        }


    </style>
@endsection
