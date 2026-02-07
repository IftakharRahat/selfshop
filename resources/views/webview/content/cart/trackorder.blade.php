@extends('frontend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-Track Order
@endsection

<!-- /.breadcrumb -->
<div class="body-content outer-top-xs">
    <div class="breadcrumb pt-2">
        <div class="container">
            <div class="row">
                <div class="breadcrumb-inner p-0">
                    <ul class="list-inline list-unstyled mb-0">
                        <li><a href="#"
                                style="text-transform: capitalize !important;color: #888;padding-right: 12px;font-size: 12px;">Home
                                > Track > <span class="active"></span>Order</span>
                            </a></li>
                    </ul>
                </div>
                <!-- /.breadcrumb-inner -->
            </div>
        </div>
        <!-- /.container -->
    </div>
    <section class="mt-1 mb-3">
        <div class="container">
            <div class="px-2 py-1 p-md-3 bg-white shadow-sm">
                <div class="search-area pb-4">
                    <h4 class="m-0 text-center pb-4"> <b>Track Your Order Now</b> </h4>
                    <form method="POST" action="{{ url('track-now') }}">
                        @csrf
                        <div class="control-group d-flex">
                            <input class="search-field m-0" name="invoiceID" placeholder="Enter your ORDER ID" style="    width: 100%;">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($orders == 'Nothing')
            @else
                @if (isset($orders))
                    {{-- track list --}}
                    <div class="card mt-4">
                        <div class="card-header py-2 px-3 heading-6 strong-600 clearfix">
                            <div class="float-center" style="color: red;text-align:center"> <b>Order History</b> </div>
                        </div>
                        <div class="card-header py-2 px-3 heading-6 strong-600 clearfix">
                            <ul class="process-steps clearfix">

                                @if ($orders->status == 'Pending')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">1</div>
                                        <div class="title" style="color:red">Pending</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">1</div>
                                        <div class="title">Pending</div>
                                    </li>
                                @endif
                                @if ($orders->status == 'Confirmed')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">2</div>
                                        <div class="title" style="color:red">Confirmed</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">2</div>
                                        <div class="title">Confirmed</div>
                                    </li>
                                @endif
                                @if ($orders->status == 'Processing')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">3</div>
                                        <div class="title" style="color:red">Processing</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">3</div>
                                        <div class="title">Processing</div>
                                    </li>
                                @endif

                                @if ($orders->status == 'Packageing')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">4</div>
                                        <div class="title" style="color:red">Packageing</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">4</div>
                                        <div class="title">Packageing</div>
                                    </li>
                                @endif


                                @if ($orders->status == 'Ontheway')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">5</div>
                                        <div class="title" style="color:red">On the way</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">5</div>
                                        <div class="title">On the way</div>
                                    </li>
                                @endif

                                @if ($orders->status == 'Delivered')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">6</div>
                                        <div class="title" style="color:red">Delivered</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">6</div>
                                        <div class="title">Delivered</div>
                                    </li>
                                @endif
                                @if ($orders->status == 'Canceled')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">7</div>
                                        <div class="title" style="color:red">Canceled</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">7</div>
                                        <div class="title">Canceled</div>
                                    </li>
                                @endif

                                @if ($orders->status == 'Return')
                                    <li>
                                        <div class="icon" style="background:#e62e04;color:white">8</div>
                                        <div class="title" style="color:red">Return</div>
                                    </li>
                                @else
                                    <li>
                                        <div class="icon">8</div>
                                        <div class="title">Return</div>
                                    </li>
                                @endif

                            </ul>
                        </div>

                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-lg-6">
                                    <table class="details-table table">
                                        <tbody>
                                            <tr>
                                                <td class="w-50 strong-600">Order ID:</td>
                                                <td>{{ $orders->invoiceID }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Customer:</td>
                                                <td>{{ $orders->customers->customerName }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Phone:</td>
                                                <td>*******{{ substr ($orders->customers->customerPhone, -4) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Shipping address:</td>
                                                <td>{{ $orders->customers->customerAddress }},@if (isset($orders->zones))
                                                        {{ $orders->zones->zoneName }},
                                                    @else
                                                        @endif @if (isset($orders->cities))
                                                            {{ $orders->cities->cityName }},
                                                        @else
                                                        @endif
                                                </td>
                                            </tr>
                                             <tr>
                                                <td class="w-50 strong-600">Shipping company:</td>
                                                <td>
                                                    @if (isset($orders->couriers))
                                                        {{ $orders->couriers->courierName }}
                                                    @else
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-lg-6">
                                    <table class="details-table table">
                                        <tbody>
                                            <tr>
                                                <td class="w-50 strong-600">Order date:</td>
                                                <td>{{ $orders->created_at->format('Y-m-d') }} ,
                                                    {{ date('h:i A', strtotime($orders->created_at)) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Total order amount:</td>
                                                <td>৳ {{ ($orders->subTotal+$orders->paymentAmount )-$orders->deliveryCharge}} + <span style="color: red">( Charge : {{ $orders->deliveryCharge}} ৳)</span> </td>
                                            </tr>

                                            <tr>
                                                <td class="w-50 strong-600">Payment method:</td>
                                                <td>
                                                    @if ($orders->Payment == 'C-O-D')
                                                        Cash On Delivery
                                                    @else
                                                        Online Payment
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Paid:</td>
                                                <td>
                                                    @if($orders->paymentAmount>0)
                                                        {{$orders->paymentAmount}} TK
                                                    @else
                                                        00 TK
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="w-50 strong-600">Due:</td>
                                                <td>
                                                    {{$orders->subTotal}} TK
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-4">

                        <div class="card-body p-4">
                            <div class="col-12">
                                <table class="details-table table">
                                    <tbody>
                                        @forelse ($orders->orderproducts as $products)
                                            <tr>
                                                <td class="w-50 strong-600">Product Name:</td>
                                                <td>{{ $products->productName }} &nbsp; <span style="color: red">(
                                                        {{ $products->quantity }}
                                                        pics )</span>
                                                </td>
                                            </tr>
                                        @empty
                                        @endforelse
                                        <tr>
                                            <td class="w-50 strong-600">Completed By:</td>
                                            <td>{{ $orders->admins->name }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                @else
                    <div class="card mt-4">
                        <div class="card-header py-2 px-3 heading-6 strong-600 clearfix">
                            <div class="float-left" style="color: red;text-align:center">No Records Found.Please call
                                our customer care or use Live Chat
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>

</div>

<style>
    .process-steps {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .process-steps li {
        width: 11%;
        float: left;
        text-align: center;
        position: relative;
    }

    .process-steps li .icon {
        height: 30px;
        width: 30px;
        margin: auto;
        background: #fff;
        border-radius: 50%;
        line-height: 30px;
        font-size: 14px;
        font-weight: 700;
        color: #adadad;
        position: relative;
    }

    .process-steps li .title {
        font-weight: 600;
        font-size: 13px;
        color: #777;
        margin-top: 8px;
        margin-bottom: 0;
    }

    .process-steps li+li:after {
        position: absolute;
        content: "";
        height: 3px;
        width: calc(100% - 30px);
        background: #fff;
        top: 14px;
        z-index: 0;
        right: calc(50% + 15px);
    }

    .breadcrumb {
        padding: 5px 0;
        border-bottom: 1px solid #e9e9e9;
        background-color: #fafafa;
    }

    .search-area .search-button {
        border-radius: 0px 3px 3px 0px;
        display: inline-block;
        float: left;
        margin: 0px;
        padding: 5px 15px 6px;
        text-align: center;
        background-color: #e62e04;
        border: 1px solid #e62e04;
    }

    .search-area .search-button:after {
        color: #fff;
        content: "\f002";
        font-family: fontawesome;
        font-size: 16px;
        line-height: 9px;
        vertical-align: middle;
    }
</style>

<style>
    /* Premium iPhone-style mobile footer */
    .bottom-navbar {
        background: #fdf0f6;
        backdrop-filter: saturate(180%) blur(20px);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 12px 0 10px 0;
        height: 75px;
        box-shadow: 0 -4px 25px rgba(0, 0, 0, 0.2);
        border-radius: 25px 25px 0 0;
        overflow: hidden;
    }

    .nav-icons-container {
        display: flex;
        justify-content: space-around;
        align-items: center;
        height: 100%;
        padding: 0 10px;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        position: relative;
        padding: 8px 12px;
        border-radius: 14px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 65px;
        flex: 1;
    }

    .nav-item:hover, .nav-item.active {
        background: rgb(230, 0, 76);
    }

    .nav-icon-container {
        position: relative;
        width: 28px;
        height: 28px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-icon {
        font-size: 20px;
        color: #8E8E93;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 2;
    }

    .nav-icon-bg {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1;
    }

    .nav-item:hover .nav-icon-bg,
    .nav-item.active .nav-icon-bg {
        background: rgba(255, 255, 255, 0.1);
        width: 40px;
        height: 40px;
    }

    .nav-label {
        color: #8E8E93;
        font-size: 10px;
        font-weight: 500;
        opacity: 0.9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.2px;
        font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
    }

    .nav-item:hover .nav-icon,
    .nav-item.active .nav-icon {
        color: #FFFFFF;
        transform: scale(1.1);
    }

    .nav-item:hover .nav-label,
    .nav-item.active .nav-label {
        color: #FFFFFF;
        opacity: 1;
        transform: translateY(-1px);
    }

    /* Special cart button styling (iPhone-like floating) */
    .cart-nav-item {
        margin-top: -25px;
        background: linear-gradient(135deg, #E5005F 0%, #5856D6 100%);
        border-radius: 50%;
        padding: 18px;
        box-shadow: 
            0 6px 20px rgba(0, 122, 255, 0.4),
            0 0 0 1px rgba(255, 255, 255, 0.1);
        min-width: auto;
        width: 48px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cart-nav-item .nav-icon-container {
        margin-bottom: 0;
        width: 24px;
        height: 24px;
    }

    .cart-nav-item .nav-icon {
        font-size: 18px;
        color: #FFFFFF;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }

    .cart-nav-item .nav-label {
        position: absolute;
        bottom: -20px;
        font-size: 9px;
        color: #8E8E93;
        background: transparent;
        opacity: 0.9;
    }

    .cart-nav-item:hover .nav-label {
        color: #FFFFFF;
    }

    /* Cart badge (iOS style) */
    .cart-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #FF3B30;
        color: white;
        font-size: 11px;
        font-weight: 600;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(29, 29, 31, 0.95);
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
        z-index: 3;
    }

    /* Active state indicators */
    .nav-item.active::before {
        content: '';
        position: absolute;
        top: 4px;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #E5005F;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .nav-item.active::before {
        opacity: 1;
    }

    /* Home icon active state */
    .nav-item.active[href*="/"] .nav-icon {
        color: #E5005F;
    }

    /* Dashboard icon active state */
    .nav-item.active[href*="dashboard"] .nav-icon {
        color: #34C759;
    }

    /* Profile/Login icon active state */
    .nav-item.active[href*="login"] .nav-icon,
    .nav-item.active[onclick*="profile"] .nav-icon {
        color: #FF9500;
    }

    /* Menu icon active state */
    .nav-item[onclick*="openNav"]:hover .nav-icon,
    .nav-item[onclick*="openNav"]:active .nav-icon {
        color: #AF52DE;
    }

    /* Touch feedback */
    .nav-item:active .nav-icon-container {
        transform: scale(0.95);
    }

    /* Glass morphism effect for the navbar */
    .bottom-navbar::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(
            to bottom,
            rgba(255, 255, 255, 0.05) 0%,
            rgba(255, 255, 255, 0.02) 100%
        );
        z-index: -1;
    }


    /* Responsive adjustments */
    @media (max-width: 360px) {
        .bottom-navbar {
            height: 70px;
            padding: 10px 0 8px 0;
        }
        
        .nav-item {
            min-width: 55px;
            padding: 6px 8px;
        }
        
        .nav-icon {
            font-size: 18px;
        }
        
        .nav-label {
            font-size: 9px;
        }
        
        .cart-nav-item {
            width: 60px;
            height: 60px;
            padding: 16px;
        }
    }

    /* iPhone X and later safe area */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
        .bottom-navbar {
            padding-bottom: calc(10px + env(safe-area-inset-bottom));
        }
    }
</style>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- iPhone-style mobile footer -->
<div class="bottom-navbar b-block d-lg-none">
    <div class="container">
        <div class="nav-icons-container">
            <!-- Home -->
            <a href="{{url('/')}}" class="nav-item {{ Request::url() == env('APP_URL').'/' ? 'active' : '' }}">
                <div class="nav-icon-container">
                    <div class="nav-icon-bg"></div>
                    <i class="nav-icon fas fa-home"></i>
                </div>
                <span class="nav-label">Home</span>
            </a>

            <!-- Menu -->
            <a href="javascript:void(0);" onclick="openNav()" class="nav-item">
                <div class="nav-icon-container">
                    <div class="nav-icon-bg"></div>
                    <i class="nav-icon fas fa-bars"></i>
                </div>
                <span class="nav-label">Menu</span>
            </a>

            <!-- Cart (Floating iPhone-style) -->
            <a href="{{ url('checkout') }}" class="nav-item cart-nav-item">
                 <div class="nav-icon-container">
                    <i class="nav-icon fas fa-shopping-bag"></i>
                </div>
                <span class="nav-label">Cart</span>
            </a>

            <!-- Dashboard -->
            <a href="{{ url('user/dashboard') }}" class="nav-item {{ Request::url() == env('APP_URL').'/user/dashboard' ? 'active' : '' }}">
                <div class="nav-icon-container">
                    <div class="nav-icon-bg"></div>
                    <i class="nav-icon fas fa-chart-line"></i>
                </div>
                <span class="nav-label">Dashboard</span>
            </a>

            <!-- Profile/Login -->
            @if(Auth::id())
                <a href="javascript:void(0);" onclick="openprofile()" class="nav-item">
                    <div class="nav-icon-container">
                        <div class="nav-icon-bg"></div>
                        <i class="nav-icon fas fa-user-circle"></i>
                    </div>
                    <span class="nav-label">Profile</span>
                </a>
            @else
                <a href="{{url('login')}}#vendor" class="nav-item {{ Request::url() == env('APP_URL').'/login' ? 'active' : '' }}">
                    <div class="nav-icon-container">
                        <div class="nav-icon-bg"></div>
                        <i class="nav-icon fas fa-user-alt"></i>
                    </div>
                    <span class="nav-label">Login</span>
                </a>
            @endif
        </div>
    </div>
</div>

<script>
    // iPhone-style touch feedback
    document.addEventListener('DOMContentLoaded', function() {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            // Touch start effect
            item.addEventListener('touchstart', function(e) {
                this.style.transform = 'scale(0.95)';
            }, { passive: true });
            
            // Touch end effect
            item.addEventListener('touchend', function(e) {
                this.style.transform = 'scale(1)';
                
                // Update active state for regular links (not JavaScript ones)
                if (!this.hasAttribute('onclick')) {
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                }
            }, { passive: true });
            
            // Touch cancel effect
            item.addEventListener('touchcancel', function(e) {
                this.style.transform = 'scale(1)';
            }, { passive: true });
        });
        
        // Set initial active state based on current URL
        const currentUrl = window.location.href;
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && href !== 'javascript:void(0);' && currentUrl.includes(href)) {
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            }
        });
    });
    
    // Add subtle parallax effect on scroll (iPhone-like)
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.bottom-navbar');
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop) {
            // Scrolling down - hide navbar slightly
            navbar.style.transform = 'translateY(10px)';
            navbar.style.opacity = '0.9';
        } else {
            // Scrolling up - show navbar
            navbar.style.transform = 'translateY(0)';
            navbar.style.opacity = '1';
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });
</script>

<script>
    function setpaymenttype(id,text){
        $('.copays').css('border','2px solid');
        $('#copay'+id).css('border','3px solid red');
        $('#account').text(text);
    }

    $(document).ready(function() {


        $('#NewWithdraw').submit(function(e) {
            e.preventDefault();
            var am=$('#withdrew_amount').val();
            if(am<=199){
                swal({
                    icon: 'error',
                    title: 'Less withdrew balance',
                    text: 'Please withdrew atleast 200 TK',
                    buttons: true,
                    buttons: "Thanks",
                });
            }else{
                $.ajax({
                    type: 'POST',
                    url: '{{ route('withdrews.store') }}',
                    processData: false,
                    contentType: false,
                    data: new FormData(this),

                    success: function(data) {
                        if (data == 'error') {
                            swal({
                                icon: 'error',
                                title: 'Password Incorrect',
                                text: 'Please enter a valid password',
                                buttons: true,
                                buttons: "Thanks",
                            });
                        } else if (data == 'lessblance') {
                            swal({
                                icon: 'error',
                                title: 'Not enough blance',
                                text: 'Please keep required blance to withdraw',
                                buttons: true,
                                buttons: "Thanks",
                            });
                        }else {
                            $('#to_additional_info').val('');
                            $('#withdraw_amount').val('');
                            $('#to_account_number').val('');
                            $('#paymenttype_id').val('');

                            swal({
                                title: "Withdraw Request Sent.Success!",
                                icon: "success",
                                showCancelButton: true,
                                focusConfirm: false,
                                confirmButtonColor: "#DD6B55",
                                confirmButtonText: "Yes",
                                cancelButtonText: "No",
                            });
                            location.reload();
                        }

                    },
                    error: function(error) {
                        console.log('error');
                    }
                });

            }
        });

    });

    (function() {
        $('.from-prevent-multiple-submits').on('submit', function() {
            $('.from-prevent-multiple-submits').attr('disabled', 'true');
            $('.spinner').css('display', 'inline');
        })
    })();
</script>
@endsection
