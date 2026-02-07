@extends('frontend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-Help & Support
@endsection
 <style>
    .about-section {
      background: url('public/aboutbanner.png') no-repeat center center;
      background-size: cover;
      position: relative;
      padding: 70px 0;
      color: #fff;
    }

    .about-footer-section {
      background: linear-gradient(0deg, rgba(132, 3, 56, 0.37) 0%, rgba(132, 3, 56, 0.37) 100%),
              url('public/aboutfooter.jpg');
      background-size: cover;
      position: relative;
      padding: 100px 0;
      color: #fff;
    }

    .about-section::before {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.6); /* Dark overlay */
      z-index: 0;
    }

    .about-content {
      position: relative;
      z-index: 1;
      max-width: 900px;
      margin: auto;
    }

    .about-title {
      font-weight: 700;
      font-size: 3rem;
    }

    .about-text {
        font-size: 1.2rem;
        line-height: 1.5;
        margin-top: 20px;
        text-align: justify;
    }

    @media (max-width: 768px) {
      .about-title {
        font-size: 2rem;
      }

      .about-text {
        font-size: 1rem;
      }
    }

    .line {
        position: absolute;
        left: 50%;
        top: 0;
        width: 2px;
        height: 100%;
        background-color: white; /* Or any color */
        opacity: 0.8;
    }

  </style>


    <div class="container py-5 mt-4">
        <div class="row">
            <div class="col-lg-6">
                <img src="{{asset('public/h1.png')}}" alt="" style="width:100%">
            </div>
            <div class="mt-4 col-lg-6">
                <h2 style="text-align:left"><b>Call Center</b></h2>
                <p style="font-size: 18px;color:black;text-align:justify">
                    Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.
                </p>
                <a href="tel:{{ App\Models\Basicinfo::first()->phone_one }}" class="btn btn-info" style="border-radius: 4.707px;">Contact Now</a>
            </div>
        </div>
    </div>
    <div class="container py-5 mt-4">
        <div class="row">
            <div class="mt-4 col-lg-6">
                <h2 style="text-align:left"><b>Mail Us</b></h2>
                <p style="font-size: 18px;color:black;text-align:justify">
                    Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.
                </p>
                <a href="mailto:{{ App\Models\Basicinfo::first()->email }}" class="btn btn-info" style="border-radius: 4.707px;">Contact Now</a>
            </div>
            <div class="col-lg-6">
                <img src="{{asset('public/h2.png')}}" alt="" style="width:100%">
            </div>
        </div>
    </div>

    <div class="container py-5 mt-4">
        <div class="row">
            <div class="col-lg-6">
                <img src="{{asset('public/h2.png')}}" alt="" style="width:100%">
            </div>
            <div class="mt-4 col-lg-6">
                <h2 style="text-align:left"><b>Our Page</b></h2>
                <p style="font-size: 18px;color:black;text-align:justify">
                    Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.
                </p>
                <a href="https://www.facebook.com/selfshop.reseller" class="btn btn-info" style="border-radius: 4.707px;">Contact Now</a>
            </div>
        </div>
    </div>
    <div class="container py-5 mt-4">
        <div class="row">
            <div class="mt-4 col-lg-6">
                <h2 style="text-align:left"><b>Facebook Group</b></h2>
                <p style="font-size: 18px;color:black;text-align:justify">
                    Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.
                </p>
                <a href="https://www.facebook.com/groups/selfshopltd" class="btn btn-info" style="border-radius: 4.707px;">Contact Now</a>
            </div>
            <div class="col-lg-6">
                <img src="{{asset('public/h3.png')}}" alt="" style="width:100%">
            </div>
        </div>
    </div>

    <div class="container py-5 mt-4">
        <div class="row">
            <div class="col-lg-6">
                <img src="{{asset('public/h4.png')}}" alt="" style="width:100%">
            </div>
            <div class="mt-4 col-lg-6">
                <h2 style="text-align:left"><b>Messanger Group</b></h2>
                <p style="font-size: 18px;color:black;text-align:justify">
                    Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.
                </p>
                <a href="https://www.facebook.com/selfshop.reseller" class="btn btn-info" style="border-radius: 4.707px;">Contact Now</a>
            </div>
        </div>
    </div>
    <div class="container py-5 mt-4">
        <div class="row">
            <div class="mt-4 col-lg-6">
                <h2 style="text-align:left"><b>What's App Group</b></h2>
                <p style="font-size: 18px;color:black;text-align:justify">
                    Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.
                </p>
                <a href="https://chat.whatsapp.com/KPCI0oMm3psBuTxKhtILPB" class="btn btn-info" style="border-radius: 4.707px;">Contact Now</a>
            </div>
            <div class="col-lg-6">
                <img src="{{asset('public/h5.png')}}" alt="" style="width:100%">
            </div>
        </div>
    </div>

     <div class="container py-5 mt-4">
        <div class="row">
            <div class="col-lg-6">
                <img src="{{asset('public/h6.png')}}" alt="" style="width:100%">
            </div>
            <div class="mt-4 col-lg-6">
                <h2 style="text-align:left"><b>What's App Community</b></h2>
                <p style="font-size: 18px;color:black;text-align:justify">
                    Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.
                </p>
                <a href="https://chat.whatsapp.com/E2yAfK1Aetb8I3D0alRPXO" class="btn btn-info" style="border-radius: 4.707px;">Contact Now</a>
            </div>
        </div>
    </div>

    <br>
    <br>
    <br>


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
