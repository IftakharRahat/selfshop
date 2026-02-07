@extends('user.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}-User Referral Bonus
@endsection

<style>
    #profileImage {
        border-radius: 50%;
        padding: 65px;
        padding-bottom: 8px;
        padding-top: 10px;
    }

    .sidebar-widget-title {
        position: relative;
    }

    .sidebar-widget-title:before {
        content: "";
        width: 100%;
        height: 1px;
        background: #eee;
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
    }

    .py-3 {
        padding-bottom: 1rem !important;
    }

    .sidebar-widget-title span {
        background: #fff;
        text-transform: uppercase;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.2em;
        position: relative;
        padding: 8px;
        color: #dadada;
    }

    ul.categories {
        padding: 0;
        margin: 0;
        list-style: none;
    }

    ul.categories--style-3>li {
        border: 0;
    }

    ul.categories>li {
        border-bottom: 1px solid #f1f1f1;
    }

    .widget-profile-menu a i {
        opacity: 0.6;
        font-size: 13px !important;
        top: 0 !important;
        width: 18px;
        height: 18px;
        text-align: center;
        line-height: 18px;
        display: inline-block;
        margin-right: 0.5rem !important;
    }

    .category-name {
        color: black;
        font-size: 18px;
    }

    .category-icon {
        font-size: 18px;
        color: black;
    }

    .bg-baguni{
        background-color: #613EEA !important;
    }
    .bg-return{
        background-color: #9C0000 !important;
    }

    .bg-delivered{
        background-color: #14BF7D !important;
    }
    .bg-confirmed{
        background-color: #004328 !important;
    }
    .bg-ondv{
        background-color: #D4911D !important;
    }
    .bg-primaryss {
        background-color: #0a296f !important;
    }

</style>

<div class="outer-top-xs outer-bottom-xs">
    <div class="container pt-4">
        <div class="row">
            <div class="p-0 m-auto col-lg-12">
                <div class="p-2 pt-0">
                    <div class="container" style="padding-bottom: 30px;">
                        <div class="mb-3 row">
                            <div class="mb-2 col-6 col-md-3">
                                <div class="pt-1 pb-1 card card-body widget-rounded-circle card-box order" style="border: 1px solid #613EEA;border-radius: 4px;">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="float-left">
                                                <h3 class="mt-1 mb-0 text-dark">
                                                    <span id="pending" data-plugin="counterup">{{Auth::user()->referal_bonus}}</span>
                                                </h3>
                                                <p class="mb-1 text-muted text-truncate" style="color: #613EEA !important;font-weight:bold;">Referral Bonus</p>
                                            </div>
                                        </div>
                                    </div> <!-- end row-->
                                </div> <!-- end widget-rounded-circle-->
                            </div>
                            <div class="mb-2 col-6 col-md-3">
                                <div class="pt-1 pb-1 card card-body widget-rounded-circle card-box order" style="border: 1px solid #613EEA;border-radius: 4px;">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="float-left">
                                                <h3 class="mt-1 mb-0 text-dark">
                                                    <span id="pending" data-plugin="counterup">{{App\Models\User::where('refer_by',Auth::user()->my_referral_code)->get()->count()}}</span>
                                                </h3>
                                                <p class="mb-1 text-muted text-truncate" style="color: #613EEA !important;font-weight:bold;">My Referral</p>
                                            </div>
                                        </div>
                                    </div> <!-- end row-->
                                </div> <!-- end widget-rounded-circle-->
                            </div>
                            <div class="mb-2 col-6 col-md-3">
                                <div class="pt-1 pb-1 card card-body widget-rounded-circle card-box order" style="border: 1px solid #27960d ;border-radius: 4px;">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="float-left">
                                                <h3 class="mt-1 mb-0 text-dark">
                                                    <span id="canceled" data-plugin="counterup">{{App\Models\User::where('refer_by',Auth::user()->my_referral_code)->where('status','Active')->get()->count()}}</span>
                                                </h3>
                                                <p class="mb-1 text-muted text-truncate" style="color:#27960d  !important;font-weight:bold;">Active Member</p>
                                            </div>
                                        </div>
                                    </div> <!-- end row-->
                                </div> <!-- end widget-rounded-circle-->
                            </div>
                            <div class="mb-2 col-6 col-md-3">
                                <div class="pt-1 pb-1 card card-body widget-rounded-circle card-box order" style="border: 1px solid #004328;border-radius: 4px;">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="float-left">
                                                <h3 class="mt-1 mb-0 text-dark">
                                                    <span id="confirmed" data-plugin="counterup">{{App\Models\User::where('refer_by',Auth::user()->my_referral_code)->where('status','Active')->where('membership_status','Paid')->get()->count()}}</span>
                                                </h3>
                                                <p class="mb-1 text-muted text-truncate" style="color:#004328 !important;font-weight:bold;">Paid Member</p>
                                            </div>
                                        </div>
                                    </div> <!-- end row-->
                                </div> <!-- end widget-rounded-circle-->
                            </div>

                        </div>
                        <div class="row">
                            @forelse ($messages as $message)

                                <div class="col-lg-12 col-12" id="mainitem">
                                    <div class="card" style="border-radius: 6px;margin-bottom: 6px;">
                                        <div style="display: flex;justify-content:space-between;padding: 4px;padding-top: 0;">

                                            <div class="info ps-2">
                                                <p class="pt-2 m-0" style="color:black ;">
                                                    <b>{{ $message->message_for }}</b>
                                                </p>
                                                <p class="m-0" style="color:rgb(26, 142, 214) ;">
                                                    <b>ID: <span>#00{{ $message->id }}</span></b>
                                                </p>
                                            </div>
                                            <div class="delivery"
                                                style="padding-top: 8px;text-align: right;">
                                                <p class="m-0" style="color:black ;">
                                                    <b>Amount : ৳ <span>{{ $message->amount}} </span></b>
                                                </p>
                                                <p class="m-0" style="color:rgb(26, 142, 214) ;">
                                                    <small style="color: gray">Date: {{ $message->date }}</small>
                                                </p>

                                            </div>
                                        </div>
                                        <p class="m-0" style="color:rgb(26, 142, 214) ;">
                                            <small style="color: gray;padding: 4px;">
                                                {{$message->message}}
                                            </small>

                                       </p>

                                    </div>
                                </div>
                            @empty
                            <div class="col-12">
                                <div class="card card-body">
                                    No Message Found
                                </div>
                            </div>
                            @endforelse
                        </div>
                        <div class="pt-4 mt-4 d-flex justify-content-center">
                            {!! $messages->links('pagination::bootstrap-4') !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
@endsection
