@extends('frontend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-Contact Us
@endsection
    @php
        $basicinfo = App\Models\Basicinfo::first();
    @endphp
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-6">
                <img src="{{asset('public/contact.png')}}" alt="" style="width:100%;border-radius:4px;">
            </div>
            <div class="mt-4 col-lg-6 mt-lg-0">
                <h2><b>Contact Us</b></h2>
                <p style="font-size: 14px;">Lorem ipsum dolor sit amet consectetur. Dignissim erat odio dictum curabitur donec at consequat arcu cursus. Eget quis cum amet iaculis orci non.</p>
                <div class="pt-4 row">
                    <div class="col-md-6">
                        <div class="form-group">Your Name * <input type="email" class="form-control" /></div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">Email Address * <input type="email" class="form-control" />
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">Title * <input type="email"  class="form-control"/></div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">Your Comments *
                            <textarea class="form-control" rows="6"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="py-4 row">
            <div class="mb-3 col-lg-4">
                <img src="{{asset('public/location.png')}}" alt="" style="width: 50px;"> &nbsp; &nbsp;{{ $basicinfo->address }}
            </div>
            <div class="mb-3 col-lg-4">
                <img src="{{asset('public/email.png')}}" alt="" style="width: 50px;"> &nbsp; &nbsp;{{ $basicinfo->email }}
            </div>
            <div class="mb-3 col-lg-4">
                <img src="{{asset('public/phone.png')}}" alt="" style="width: 50px;"> &nbsp; &nbsp;{{ $basicinfo->phone_one }}
            </div>
        </div>
    </div>
    <div class="container">
        <div class="contact-page">
            <div class="row">
                <div class="mb-3 col-12">
                    <h2><b>Live location</b></h2>
                </div>
                <div class="col-12 contact-map outer-bottom-vs">
                    <div  style="border-radius: 10px;border: 2px solid black;">
                        <iframe height="350"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d8996.063407227497!2d90.82241422127906!3d22.93143931296395!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3754c13106da1f47%3A0xeaf1078c82ab83db!2sBangladesh%20Food%20Safety%20Authority%2C%20District%20Office%2C%20Lakshmipur!5e1!3m2!1sen!2sbd!4v1754045101689!5m2!1sen!2sbd"
                        style="border:0" width="100%" ></iframe>
                    </div>
                </div>
            </div>
            <!-- /.contact-page -->
        </div>
        <!-- /.row -->
    </div>
    <br><br>
@endsection
