<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="{{ url(App\Models\Basicinfo::first()->first()->fav_icon) }}">
    
    
    <script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "vchyaba4lq");
</script>


 
    @yield('meta')

    {{-- Header link includes --}}
    @include('frontend.linkincludes.css')
    @yield('subcss')

    <style>
        .vendor-items {
            border-radius: 6px;
            box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
        }
        #viewall {
            background: #613EEA;
            color: white;
            padding: 4px;
            border-radius: 6px;
        }

        #pid {
            font-size: 12px;
            padding-left: 5px;
            margin: 0;
            padding-top: 4px;
        }

        .vendor-item {
            padding: 6px;
            border: 1px solid;
        }

        /* mobile menu */
        body.modal-open {
            height: 100vh;
            overflow-y: hidden;
        }

        .sidenav {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 100;
            top: 0px;
            left: 0;
            /* background-color: #111; */
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }

        .sidenav a {
            padding: 7px 7px 7px 20px;
            text-decoration: none;
            font-size: 15px;
            color: #818181;
            display: block;
            transition: 0.3s;
        }

        .sidenav a:hover {
            color: #f1f1f1;
        }

        .activebtn {
            background: #613EEA;
            color: white !important;
            font-size: 14px;
            margin-right: 20px;
            text-align: left;
        }

        .sidenav .closebtn {
            position: absolute;
            top: -15px;
            right: 0%;
            z-index: 1000;
            font-size: 36px;
            /*margin-left: 50px;*/
            color: #fff;
        }


        #posit {
            position: fixed;
            right: 0;
            z-index: 1111;
            top: 50%;
            background: #e00060;
            height: 50px;
            width: 64px;
            text-align: end;
            border-radius: 8px 0px 0px 8px;
        }

        .text-cart {
            color: #28a745 !important;
            text-align: center;
        }

        #checkIconCart {
            font-size: 18px !important;
            height: 50px;
            width: 50px;
            line-height: 48px;
            border: 2px solid #28a745;
            border-radius: 50px;
            margin-bottom: 5px;
            text-align: center;
        }

        .dc-image {
            display: inline-block;
            float: left;
            width: 70px;
        }

        .dc-content {
            display: inline-block;
            float: right;
            width: calc(100% - 70px);
            padding-left: 1.5rem;
        }

        #cartIconCloss {
            border: none;
            font-size: 30px;
            width: 6px !important;
            border-radius: 50%;
            background: none;
            color: red;
            margin-right: 15px;
        }

        .dc-item {
            padding: 15px 15px;
        }

        .subtotal-amount {
            display: inline-block;
            float: right;
            color: rgba(0, 0, 0, 0.5);
            font-size: 18px;
        }

        .subtotal-text {
            display: inline-block;
            float: left;
            color: rgba(0, 0, 0, 0.5);
            font-size: 18px;
        }

        #closebtn {
            position: absolute;
            top: 0;
            right: 8px;
            background: none;
            border: none;
            font-size: 34px;
        }

        #notification {
            padding-right: 0px !important;
            padding-top: 42px;
        }

        #notification .nav-tabs .nav-link.active,
        .nav-tabs .nav-item.show .nav-link {
            color: #ffffff;
            background-color: rgba(55, 0, 146, 0.67);
            padding: 4px 18px;
            border-radius: 35px;
            font-weight: bold;
            font-size: 16px;
        }

        #notification .nav-tabs .nav-link {
            color: #000;
            background-color: rgba(220, 199, 255, 0.29);
            padding: 4px 18px;
            border-radius: 35px;
            font-weight: bold;
            font-size: 16px;
        }

        #notificationmodal {
            margin: none;
        }
        .owl-item{
            margin-top: 2px;
            margin-bottom: 2px;
        }
        #proimg{
            height: 180px;
            border-radius: 8px;
        }
        #smallmenu{
            margin-bottom: 10px;width: 100%;border-radius: 4px;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
        }
        #dispos{
            position: absolute;
            top: 2px;
            right: 3px;
            font-size: 12px;
            background: #ffcbcb;
            color: red;
            padding: 0px 6px;
            border-radius: 30px;
        }
        #btmimg{
            border-radius: 6px;height: 150px;width: 180px;
        }
        @media screen and (max-width: 600px) {
            #btmimg{
                border-radius: 6px;height: 100px;width: 120px;    padding-right: 10px;
            }
            #hidesm{
                display: none;
            }
            #notificationmodal {
                margin: -1px;
            }
            #smallmenu{
                margin-bottom: 6px;width: 100%;border-radius: 4px;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
            }
            #proimg{
                height: 160px;
                border-radius: 8px;
            }
        }

        @media screen and (max-width: 450px) {
            .sidenav {
                padding-top: 10px;
            }

            #notification {
                padding-right: 0px !important;
                padding-top: 18px;
            }

            .sidenav a {
                font-size: 16px;
            }

            #myPronav.sidenav a {
                margin: 0px 0px;
                text-decoration: none;
                font-size: 25px;
                display: block;
                transition: 0.3s;
                padding: 6px;
                border-radius: 4px;
            }

        }

        #ncard {
            border-radius: 4px;
            background: rgba(222, 226, 230, 0.52);
            margin-bottom: 6px;
        }

        #nimg {
            width: 40px;
            float: left;
            margin-top: 5px;
        }

        #ncard .info {
            width: 84%;
            float: right;
        }
        #mySidenavsm .dropdown-menu {
            min-width: 300px !important;
        }

        #menu {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        }

        #menu li {
        margin-right: 14px;
        }

        #menu li a {
            text-decoration: none;
            color: #333;
            padding: 0px 0px;
            display: block;
            font-weight: 600;
            font-size: 14px;
        }

    </style>

    <!-- PWA  -->
    <meta name="theme-color" content="#6777ef" />
    <link rel="apple-touch-icon" href="{{ url(App\Models\Basicinfo::first()->first()->fav_icon) }}">
    <link rel="manifest" href="{{ asset('public/manifest.json') }}">


    {!! App\Models\Basicinfo::first()->facebook_pixel !!}
    {!! App\Models\Basicinfo::first()->google_analytics !!}
    <style>
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            float: right;
        }

        .card.product-item {
            padding: 6px !important;
            border-radius: 6px;
            box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
        }

        .card.product-item img.img-fluid.w-100 {
            border-radius: 4px;
            height:100%;
        }

    </style>
</head>

<body>

    <!-- Topbar Start -->
    @include('frontend.includes.header')
    <!-- Topbar End -->

    <!-- Main content start -->
    @yield('maincontent')
    <!-- main content end -->

    <!-- Footer Start -->
    @include('frontend.includes.footer')
    <!-- Footer End -->

    <div class="modal fade" id="notification" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" id="notificationmodal" style="margin-top: 36px;">
            <div class="modal-content" style="background: #F3F3FD;">
                <div class="modal-header" style="padding-bottom: 4px;">
                    <h5 class="modal-title" id="exampleModalLabel" style="color:#2E294E">Notifications</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true"><img src="{{ asset('public/filter-vertical.png') }}"></span>
                    </button>
                </div>
                <div class="pt-0 modal-body">
                    <div class="mb-0 sidebar single-content-sidebar">
                        <div class="sidebar-widget single-content-widget">
                            <div class="sidebar-widget-item">
                                <div class="mb-3 sidebar-book-title-wrap">
                                    <ul class="mb-3 nav nav-tabs" id="ex1" role="tablist"
                                        style="justify-content: left;border: none;">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active" id="ex1-tab-1" data-toggle="tab"
                                                href="#ex1-tabs-1" role="tab">Recent</a>
                                        </li>
                                        <li class="nav-item" role="presentation" style="margin-left: 26px;">
                                            <a class="nav-link" id="ex1-tab-2" data-toggle="tab" href="#ex1-tabs-2"
                                                role="tab">All</a>
                                        </li>
                                    </ul>
                                </div>
                            </div><!-- end sidebar-widget-item -->
                            <div class="sidebar-widget-item">
                                <div class="pt-3 contact-form-action">
                                    <div class="tab-content" id="ex1-content">
                                        <div class="tab-pane fade show active" id="ex1-tabs-1" role="tabpanel"
                                            aria-labelledby="ex1-tab-1">
                                            @php
                                                $commss = App\Models\Comment::where('user_id', Auth::id())
                                                    ->whereIn('type', [
                                                        'Delivered',
                                                        'Reassign',
                                                        'Return',
                                                        'Delivered',
                                                        'Canceled',
                                                        'Withdraw',
                                                        'Withdrawpaid',
                                                        'Withdrawcancel',
                                                    ])
                                                    ->latest()
                                                    ->take(10)
                                                    ->get();
                                                $allcommss = App\Models\Comment::where('user_id', Auth::id())
                                                    ->whereIn('type', [
                                                        'Delivered',
                                                        'Reassign',
                                                        'Return',
                                                        'Delivered',
                                                        'Canceled',
                                                        'Withdraw',
                                                        'Withdrawpaid',
                                                        'Withdrawcancel',
                                                    ])
                                                    ->latest()
                                                    ->limit(100)
                                                    ->paginate(20);
                                            @endphp

                                            @forelse ($commss as $commemt)
                                                <div class="p-1 card card-body" id="ncard">
                                                    @if (isset($commemt->order_id))
                                                        <a
                                                            href="{{ url('track-now?invoiceID=') }}{{ App\Models\Order::where('id', $commemt->order_id)->first()->invoiceID }}">
                                                        @else
                                                            <a href="#">
                                                    @endif
                                                    @if ($commemt->type == 'Delivered')
                                                        <img src="{{ asset('public/truck.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Return')
                                                        <img src="{{ asset('public/truck.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Canceled')
                                                        <img src="{{ asset('public/truckcn.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Reassign')
                                                        <img src="{{ asset('public/truckdv.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Withdraw')
                                                        <img src="{{ asset('public/withdrew.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Withdrawpaid')
                                                        <img src="{{ asset('public/withdrew.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Withdrawcancel')
                                                        <img src="{{ asset('public/withdrew.png') }}" id="nimg">
                                                    @else
                                                        <img src="{{ asset('public/truck.png') }}" id="nimg">
                                                    @endif
                                                    <div class="info">
                                                        <div class="title d-flex justify-content-between">
                                                            @if ($commemt->type == 'Delivered')
                                                                <h4
                                                                    style="font-size: 14px;font-weight: bold;margin:0;color:green">
                                                                    Successfully Delivered
                                                                @elseif ($commemt->type == 'Return')
                                                                    <h4
                                                                        style="font-size: 14px;font-weight: bold;margin:0;color:#f6451d">
                                                                        Opps ! Parcel Return
                                                                    @elseif ($commemt->type == 'Canceled')
                                                                        <h4
                                                                            style="font-size: 14px;font-weight: bold;margin:0;color:#f6451d">
                                                                            Opps ! Parcel Canceled
                                                                        @elseif ($commemt->type == 'Reassign')
                                                                            <h4
                                                                                style="font-size: 14px;font-weight: bold;margin:0;color:green">
                                                                                Successfully Reassign
                                                                            @elseif ($commemt->type == 'Withdraw')
                                                                                <h4
                                                                                    style="font-size: 14px;font-weight: bold;margin:0;color:rgb(26, 2, 161)">
                                                                                    Payment Request Sent !
                                                                                @elseif($commemt->type == 'Withdrawpaid')
                                                                                    <h4
                                                                                        style="font-size: 14px;font-weight: bold;margin:0;color:green">
                                                                                        Payment Request Accepted.
                                                                                    @elseif($commemt->type == 'Withdrawcancel')
                                                                                        <h4
                                                                                            style="font-size: 14px;font-weight: bold;margin:0;color:#f6451d">
                                                                                            Payment Request Cancel.
                                                                                        @else
                                                                                            <h4
                                                                                                style="font-size: 14px;font-weight: bold;margin:0;">
                                                            @endif
                                                            </h4>
                                                            <p style="font-size: 10px;color:#526BF1;margin:0;">
                                                                {{ $commemt->created_at->diffForHumans() }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="info">
                                                        <div class="title d-flex justify-content-between">
                                                            <p
                                                                style="font-size: 12px;margin: 0;height: 34px;overflow: hidden;color:black">
                                                                {{ $commemt->comment }}</p>
                                                            <img src="{{ asset('public/dot.png') }}"
                                                                style="width:8px;height:8px;margin-top: 8px;">
                                                        </div>
                                                    </div>
                                                    </a>
                                                </div>
                                            @empty
                                                <div class="p-1 card card-body" id="ncard">
                                                    <span class="text-center text-danger">Opps ! Nothing found
                                                        now.</span>
                                                </div>
                                            @endforelse
                                        </div>
                                        <div class="tab-pane fade" id="ex1-tabs-2" role="tabpanel"
                                            aria-labelledby="ex1-tab-2">
                                            @forelse ($allcommss as $commemt)
                                                <div class="p-1 card card-body" id="ncard">
                                                    @if (isset($commemt->order_id))
                                                        <a
                                                            href="{{ url('track-now?invoiceID=') }}{{ App\Models\Order::where('id', $commemt->order_id)->first()->invoiceID }}">
                                                        @else
                                                            <a href="#">
                                                    @endif
                                                    @if ($commemt->type == 'Delivered')
                                                        <img src="{{ asset('public/truck.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Return')
                                                        <img src="{{ asset('public/truck.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Canceled')
                                                        <img src="{{ asset('public/truckcn.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Reassign')
                                                        <img src="{{ asset('public/truckdv.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Withdraw')
                                                        <img src="{{ asset('public/withdrew.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Withdrawpaid')
                                                        <img src="{{ asset('public/withdrew.png') }}" id="nimg">
                                                    @elseif ($commemt->type == 'Withdrawcancel')
                                                        <img src="{{ asset('public/withdrew.png') }}" id="nimg">
                                                    @else
                                                        <img src="{{ asset('public/truck.png') }}" id="nimg">
                                                    @endif
                                                    <div class="info">
                                                        <div class="title d-flex justify-content-between">

                                                            @if ($commemt->type == 'Delivered')
                                                                <h4
                                                                    style="font-size: 14px;font-weight: bold;margin:0;color:green">
                                                                    Successfully Delivered
                                                                @elseif ($commemt->type == 'Return')
                                                                    <h4
                                                                        style="font-size: 14px;font-weight: bold;margin:0;color:#f6451d">
                                                                        Opps ! Parcel Return
                                                                    @elseif ($commemt->type == 'Canceled')
                                                                        <h4
                                                                            style="font-size: 14px;font-weight: bold;margin:0;color:#f6451d">
                                                                            Opps ! Parcel Canceled
                                                                        @elseif ($commemt->type == 'Reassign')
                                                                            <h4
                                                                                style="font-size: 14px;font-weight: bold;margin:0;color:green">
                                                                                Successfully Reassign
                                                                            @elseif ($commemt->type == 'Withdraw')
                                                                                <h4
                                                                                    style="font-size: 14px;font-weight: bold;margin:0;color:rgb(26, 2, 161)">
                                                                                    Payment Request Sent !
                                                                                    @elseif ($commemt->type == 'Withdrawpaid')
                                                                                    <h4
                                                                                        style="font-size: 14px;font-weight: bold;margin:0;color:green">
                                                                                        Payment Request Accepted.
                                                                                        @elseif ($commemt->type == 'Withdrawcancel')
                                                                                        <h4
                                                                                            style="font-size: 14px;font-weight: bold;margin:0;color:#f6451d">
                                                                                            Payment Request Cancel.
                                                                                        @else
                                                                                            <h4
                                                                                                style="font-size: 14px;font-weight: bold;margin:0;">
                                                            @endif
                                                            </h4>
                                                            <p style="font-size: 10px;color:#526BF1;margin:0;">
                                                                {{ $commemt->created_at->diffForHumans() }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="info">
                                                        <div class="title d-flex justify-content-between">
                                                            <p
                                                                style="font-size: 12px;margin: 0;height: 34px;overflow: hidden;color:black">
                                                                {{ $commemt->comment }}</p>
                                                            <img src="{{ asset('public/dot.png') }}"
                                                                style="width:8px;height:8px;margin-top: 8px;">
                                                        </div>
                                                    </div>
                                                    </a>
                                                </div>
                                            @empty
                                                <div class="p-1 card card-body" id="ncard">
                                                    <span class="text-center text-danger">Opps ! Nothing found
                                                        now.</span>
                                                </div>
                                            @endforelse
                                            {{ $allcommss->links('pagination::bootstrap-4') }}
                                        </div>
                                    </div>
                                </div>
                            </div><!-- end sidebar-widget-item -->

                        </div><!-- end sidebar-widget -->
                    </div><!-- end sidebar-widget -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <div class="modal" id="processing">
        <div class="modal-dialog">
            <div class="modal-content" style="text-align: center;background: none;border: none;top: 50px;">
                <i class="spinner fa fa-spinner fa-spin"
                    style="    color: #e62e04; font-size: 70px;  padding: 22px;"></i>
            </div>
        </div>
    </div>

    <div class="modal" id="install" style="top: 25%;">
        <div class="modal-dialog">
            <div class="modal-content" style="text-align: center;border: none;top: 50px;">
                <h4 class="pt-3">Want to install our Apps? Click Install</h4>
                <div class="modal-footer">
                    <button class="btn btn-danger" data-dismiss="modal" style="border-radius:6px">Cancel</button>
                    <button class="btn btn-info" id="installButton" style="border-radius:6px">Install</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal" id="cartViewModal">
        <div class="modal-dialog">
            <div class="modal-content" style="top: 55px;border-radius: 6px;">
                <div class="modal-body" id="AddToCartModel" style="padding-top: 0">

                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><span
                            aria-hidden="true">Add
                            More Products</span></button>
                    <a href="{{ url('checkout') }}" class="text-white btn btn-primary">Submit Order</a>
                </div>
            </div>
        </div>
    </div>
    <div id="cartcount">
        @if (count(Cart::content()) > 0)
            <div id="posit" type="button" onclick="checkcartview()">
                <span style="font-size: 22px;line-height: 50px;color: white;">{{ count(Cart::content()) }}</span><img
                    src="{{ asset('public/add-to-cart.png') }}"
                    style="margin-top: -7px;height:36px;padding-right: 12px;">
            </div>
        @endif
    </div>

    {{-- //footer link (js) include --}}
    @include('frontend.linkincludes.js')


    <script>
        function removeFromCartItem(rowId) {
            var token = $("input[name='_token']").val();

            $.ajax({
                type: 'POST',
                url: '{{ url('remove-cart') }}',
                data: {
                    _token: token,
                    rowId: rowId,
                },

                success: function(response) {

                    updatecart();
                    swal({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Product remove from your Cart',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    if (response == 'empty') {
                        $('#loadingreload').css({
                            'display': 'flex',
                            'justify-content': 'center',
                            'align-items': 'center'
                        })
                        $('#loadingreload').modal('show');
                        $('#cartViewModal').modal('hide');
                        location.reload();
                    } else {
                        $('#cartViewModal .modal-body').empty().append(
                            response);
                        $('#cartViewModal').modal('show');
                    }


                },
                error: function(error) {
                    console.log('error');
                }
            });
        }

        function updatecart() {
            $.ajax({
                type: 'get',
                url: '{{ url('update-cart') }}',

                success: function(response) {
                    $('.basket-item-count').html(response.item);
                    $('.cartamountvalue').html(response.amount);
                },
                error: function(error) {
                    console.log('error');
                }
            });
        }

        function viewcart() {
            $.ajax({
                type: 'get',
                url: '{{ url('view-cart') }}',

                success: function(response) {
                    $('#cartcount').empty().append(response);
                },
                error: function(error) {
                    console.log('error');
                }
            });
        }

        function checkcartview() {
            $.ajax({
                type: 'GET',
                url: '{{ url('get-cart-content') }}',

                success: function(response) {
                    $('#cartViewModal .modal-body').empty().append(
                        response);
                },
                error: function(error) {
                    console.log('error');
                }
            });
            $('#processing').modal('hide');
            $('#cartViewModal').modal('show');
        }
    </script>
    {{-- //sweetalert --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"
        integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    @yield('subjs')

    <script src="{{ asset('/sw.js') }}"></script>

    <script>
        // let deferredPrompt;

        // window.addEventListener('beforeinstallprompt', (event) => {
        //     event.preventDefault();
        //     deferredPrompt = event;
        //     showInstallButton();
        // });

        // function showInstallButton() {
        //     $('#install').modal('show');
        //     const installButton = document.getElementById('installButton');
        //     installButton.addEventListener('click', () => {
        //         deferredPrompt.prompt();
        //         deferredPrompt.userChoice.then((choiceResult) => {
        //             if (choiceResult.outcome === 'accepted') {
        //                 $('#install').modal('hide');
        //             } else {
        //                 $('#install').modal('hide');
        //             }
        //             deferredPrompt = null;
        //         });
        //     });
        // }
        // window.addEventListener('appinstalled', (event) => {
        //     console.log('App installed successfully');
        // });
    </script>

</body>

</html>
