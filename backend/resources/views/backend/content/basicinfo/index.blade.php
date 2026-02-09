@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Basicinfo
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">

        <div class="col-sm-12 col-md-12 col-xl-6 mb-4">
            <div class="bg-secondary rounded h-100 p-4">
                <h2 class="mb-4" style="text-align: center;color:#0a296f">Settings Update</h2>
                <form action="{{ route('admin.basicinfos.update', $webinfo->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" name="email" value="{{ $webinfo->email }}"
                                    id="floatingInput" placeholder="name@example.com">
                                <label for="floatingInput">Email address</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="wp_number"
                                    value="{{ $webinfo->wp_number }}" id="floatingPassword" placeholder="What's Number">
                                <label for="floatingPassword">What's Number</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="phone_one"
                                    value="{{ $webinfo->phone_one }}" id="floatingPassword" placeholder="Phone One">
                                <label for="floatingPassword">Phone One</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="phone_two"
                                    value="{{ $webinfo->phone_two }}" id="floatingPassword" placeholder="Phone Two">
                                <label for="floatingPassword">Phone Two</label>
                            </div>
                            <div class="form-floating mb-3">
                                <textarea class="form-control" placeholder="Office Address" name="address" id="floatingTextarea" style="height: 100px;">{{ $webinfo->address }}</textarea>
                                <label for="floatingTextarea">Office Address</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="bonus_percent"
                                    value="{{ $webinfo->bonus_percent }}" id="floatingPassword" placeholder="Account Opening Bonus">
                                <label for="floatingPassword">Account Opening Bonus</label>
                            </div>
                            <div class="mb-3">
                                <input class="form-control form-control-lg bg-dark" name="logo" id="formFileLg"
                                    type="file">
                            </div>
                            <div class="m-3 ms-0" style="text-align: center;height: 50px;">
                                <h4 style="width:30%;float: left;text-align: left;">LOGO : </h4>
                                <img src="{{ asset(preg_replace('#^public/#', '', $webinfo->logo ?? '')) }}" alt="" srcset=""
                                    style="max-height: 50px;">
                            </div>
                            <br>
                            <br> 
                            <div class="mb-3">
                                <input class="form-control form-control-lg bg-dark" name="fav_icon" id="formFileLg"
                                    type="file">
                            </div>
                            <div class="m-3 ms-0" style="text-align: center;height: 50px;">
                                <h4 style="width:30%;float: left;text-align: left;">FAV ICON : </h4>
                                <img src="{{ asset(preg_replace('#^public/#', '', $webinfo->fav_icon ?? '')) }}" alt="" srcset=""
                                    style="max-height: 100px;">
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">Update</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-sm-12 col-md-12 col-xl-6 mb-4">
            <div class="bg-secondary rounded h-100 p-4">
                <h2 class="mb-4" style="text-align: center;color:#0a296f">Pixel & Analytics</h2>
                <form action="{{ url('/admin/pixel/analytics', $webinfo->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" placeholder="Invoice Footer Text" name="invoice_footer" id="floatingTextarea"
                                    style="height: 80px;">{{ $webinfo->invoice_footer }}</textarea>
                                <label for="floatingTextarea">Invoice Footer Text</label>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" placeholder="Facebook Pixel" name="facebook_pixel" id="floatingTextarea"
                                    style="height: 150px;">{{ $webinfo->facebook_pixel }}</textarea>
                                <label for="floatingTextarea">Facebook Pixel</label>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" placeholder="Google Analytics" name="google_analytics" id="floatingTextarea"
                                    style="height: 150px;">{{ $webinfo->google_analytics }}</textarea>
                                <label for="floatingTextarea">Google Analytics</label>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" placeholder="Marquee Text" name="marquee_text" id="marquee_text"
                                    style="height: 100px;">{{ $webinfo->marquee_text }}</textarea>
                                <label for="floatingTextarea">Marquee Text</label>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" placeholder="Chatbox Script" name="chat_box" id="chat_box" style="height: 100px;">{{ $webinfo->chat_box }}</textarea>
                                <label for="floatingTextarea">Chatbox Script</label>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-6">
            <div class="bg-secondary rounded h-100 p-4">
                <h2 class="mb-4" style="text-align: center;color:#0a296f">Social Links Update</h2>
                <form action="{{ url('/admin/basicinfo/update', $webinfo->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="wp_link"
                                    value="{{ $webinfo->wp_link }}" id="floatingInput"
                                    placeholder="">
                                <label for="floatingInput">What's App Link</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="messanger_link"
                                    value="{{ $webinfo->messanger_link }}" id="floatingInput"
                                    placeholder="">
                                <label for="floatingInput">messanger_link</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="facebook"
                                    value="{{ $webinfo->facebook }}" id="floatingInput"
                                    placeholder="https://www.facebook.com/">
                                <label for="floatingInput">Facebook</label>
                            </div>
                            <div class="form-floating mb-3" hidden>
                                <input type="text" class="form-control" name="twitter"
                                    value="{{ $webinfo->twitter }}" id="floatingInput"
                                    placeholder="https://www.twitter.com/">
                                <label for="floatingInput">Twitter</label>
                            </div>
                            <div class="form-floating mb-3" hidden>
                                <input type="text" class="form-control" name="google"
                                    value="{{ $webinfo->google }}" id="floatingInput"
                                    placeholder="https://google.com">
                                <label for="floatingInput">Google</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="rss"
                                    value="{{ $webinfo->rss }}" id="floatingInput"
                                    placeholder="https://www.tiktok.com/">
                                <label for="floatingInput">Tiktok</label>
                            </div>
                            <div class="form-floating mb-3" hidden>
                                <input type="text" class="form-control" name="pinterest"
                                    value="{{ $webinfo->pinterest }}" id="floatingInput"
                                    placeholder="https://www.pinterest.com/">
                                <label for="floatingInput">Penterest</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="linkedin"
                                    value="{{ $webinfo->linkedin }}" id="floatingInput"
                                    placeholder="https://www.Instagram.com/">
                                <label for="floatingInput">Instagram</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="youtube"
                                    value="{{ $webinfo->youtube }}" id="floatingInput"
                                    placeholder="https://www.Youtube.com/">
                                <label for="floatingInput">Youtube</label>
                            </div>


                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">Update</button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-6 mb-4">
            <div class="bg-secondary rounded h-100 p-4">
                <h2 class="mb-4" style="text-align: center;color:#0a296f">Shipping Information Update</h2>
                <form action="{{ route('admin.shipping.update', $webinfo->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="b_one"
                                    value="{{ $webinfo->b_one }}" id="floatingPassword" placeholder="Bkash One">
                                <label for="floatingPassword">Bkash One</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="b_two"
                                    value="{{ $webinfo->b_two }}" id="floatingPassword" placeholder="Nagad One">
                                <label for="floatingPassword">Nagad One</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="b_three"
                                    value="{{ $webinfo->b_three }}" id="floatingPassword" placeholder="Rocket One">
                                <label for="floatingPassword">Rocket One</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="inside_dhaka_charge"
                                    value="{{ $webinfo->inside_dhaka_charge }}" id="inside_dhaka_charge"
                                    placeholder="Inside Dhaka Charge">
                                <label for="floatingInput">Inside Dhaka Charge</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="near_dhaka_charge"
                                    value="{{ $webinfo->near_dhaka_charge }}" id="near_dhaka_charge"
                                    placeholder="Near Dhaka Charge">
                                <label for="floatingPassword">Near Dhaka Charge</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="outside_dhaka_charge"
                                    value="{{ $webinfo->outside_dhaka_charge }}" id="outside_dhaka_charge"
                                    placeholder="Outside Dhaka Charge">
                                <label for="floatingPassword">Outside Dhaka Charge</label>
                            </div>
                            <div class="form-floating mb-3" hidden>
                                <input type="text" class="form-control" name="insie_dhaka"
                                    value="{{ $webinfo->insie_dhaka }}" id="insie_dhaka"
                                    placeholder="Inside Dhaka Delivery Time">
                                <label for="floatingPassword">Inside Dhaka Delivery Time</label>
                            </div>
                            <div class="form-floating mb-3" hidden>
                                <input type="text" class="form-control" name="outside_dhaka"
                                    value="{{ $webinfo->outside_dhaka }}" id="outside_dhaka"
                                    placeholder="Outside Dhaka Delivery Time">
                                <label for="floatingPassword">Outside Dhaka Delivery Time</label>
                            </div>

                            <div class="form-floating mb-3" hidden>
                                <input type="text" class="form-control" name="contact"
                                    value="{{ $webinfo->contact }}" id="contact" placeholder="Contact Info">
                                <label for="floatingInput">Contact Info</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="title"
                                    value="{{ $webinfo->title }}" id="floatingInput" >
                                <label for="floatingInput">Meta Title</label>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" placeholder="Meta Description" name="meta_description" id="meta_description"
                                        style="height: 100px;">{{ $webinfo->meta_description }}</textarea>
                                    <label for="floatingTextarea">Meta Description</label>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" placeholder="Meta Keyword" name="meta_keyword" id="meta_keyword"
                                        style="height: 100px;">{{ $webinfo->meta_keyword }}</textarea>
                                    <label for="floatingTextarea">Meta Keyword</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input class="form-control form-control-lg bg-dark" name="meta_image" id="formFileLg"
                                    type="file">
                            </div>
                            <div class="m-3 ms-0" style="text-align: center;height: 85px;margin-top:50px !important">
                                <h4 style="width:30%;float: left;text-align: left;">Meta Image : </h4>
                                <img src="{{ asset(preg_replace('#^public/#', '', $webinfo->meta_image ?? '')) }}" alt="" srcset=""
                                    style="max-height: 100px;">
                            </div>

                            <div class="form-floating mb-3" hidden>
                                <input type="text" class="form-control" name="refund_rule"
                                    value="{{ $webinfo->refund_rule }}" id="refund_rule" placeholder="Refund Rules">
                                <label for="floatingPassword">Refund Rules</label>
                            </div>
                            <div class=" mb-4" hidden>
                                <select name="cash_on_delivery" id="cash_on_delivery" required
                                    class="form-select form-select-lg mb-3" aria-label=".form-select-lg example">
                                    @if ($webinfo->cash_on_delivery == 'ON')
                                        <option value="ON" selected>ON</option>
                                        <option value="OFF">OFF</option>
                                    @else
                                        <option value="ON">ON</option>
                                        <option value="OFF" selected>OFF</option>
                                    @endif

                                </select>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary btn-lg w-100">Update</button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
