<style>
    #footm.text-white{
        font-size: 14px !important;
    }
</style>
<div class="container pt-4">
        <div class="row">
            <div class="mb-3 col-md-12" style="text-align: center;">
                <h4><b>Most Popular Brands</b></h4>
            </div>

            <div class="col-lg-12">
                <div class="owl-carousel category-carousel" id="brandCarousel" >
                    @forelse (App\Models\Brand::where('status','Active')->get() as $brand)
                        <div class="items">
                            <div class="mb-2 cat-item d-flex flex-column border-new" id="categoryItem">

                                    <img class="img-fluid" src="{{ asset($brand->brand_icon) }}" alt=""
                                        style="height: 60px;width: 60px;border-radius: 50%;">
                            </div>
                        </div>

                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <br>
<div class="pt-4 mt-2 text-white container-fluid" id="footm" style="background:#2D2D2D !important">

    <div class="row">
        <div class="col-lg-12">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-12">
                        <a href="" class="text-decoration-none" style="width: 100%;float: left;">
                            <img src="{{ asset($basicinfo->logo) }}" alt="{{ env('APP_NAME') }}"
                                style="margin-left: -36px;width: 70%;margin-bottom: 20px;float: left;border-radius: 6px;" />
                        </a>
                        <div class="mb-3" style="text-align: justify;">

                            <p class="mb-2">
                                SelfShop is a B2B platform created for modern entrepreneurs and dropshippers. Here, you can purchase single wholesale products or buy in bulk—giving you the flexibility to grow your business your way. From trending items to essential goods, we make sourcing and scaling simple.
                            </p>
                            <p class="mb-2">Address : Bhabaniganj,Chawrasta, Sadar Lakshmipur ,Lakshmipur</p>
                            <p class="mb-2">Trade Licence number is : 1258</p>
                            <div class="d-none d-lg-block">
                                <div class="d-flex align-items-center" style="padding-top: 25px;">
                                    <a class="px-2 text-black" href="{{ $basicinfo->facebook }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-facebook-f" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 7px;"></i>
                                    </a>&nbsp;&nbsp;
                                    <a class="px-2 text-black" href="{{ $basicinfo->twitter }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-twitter" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 5px;"></i>
                                    </a>&nbsp;&nbsp;
                                    <a class="px-2 text-black" href="{{ $basicinfo->linkedin }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-instagram" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 5px;"></i>
                                    </a>&nbsp;&nbsp;
                                    <a class="px-2 text-black" href="{{ $basicinfo->linkedin }}" style="height: 40px;width: 40px;background: white;border-radius: 50%;">
                                        <i class="fab fa-linkedin-in" style="font-size: 16px;text-align: center;margin-top: 11px;margin-left: 5px;"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-12 d-none">
                        <div id="sociallinkmobile" class="d-block d-lg-none">
                            <hr style="margin: 0">
                            <div class="py-2 row bg-secondary px-xl-5">

                                <div class="text-center col-lg-6 text-lg-right">
                                    <div class="d-inline-flex align-items-center">
                                        <a class="px-2 text-white" href="{{ $basicinfo->facebook }}">
                                            <i class="fab fa-facebook-f" style="color:#fff"></i>
                                        </a>
                                        <a class="px-2 text-white" href="{{ $basicinfo->twitter }}">
                                            <i class="fab fa-twitter" style="color:#000000"></i>
                                        </a>
                                        <a class="px-2 text-white" href="{{ $basicinfo->linkedin }}">
                                            <i class="fab fa-instagram" style="color:#000000"></i>
                                        </a>
                                        <a class="px-2 text-white" href="{{ $basicinfo->linkedin }}">
                                            <i class="fab fa-linkedin-in" style="color:#000000"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 text-left col-lg-8 col-12">
                        <div class="row">
                            <div class="col-md-4 col-6">
                                <h5 class="mb-4 text-white font-weight-bold">INFORMATION</h5>
                                <div class="d-flex flex-column justify-content-start">
                                    <a class="mb-2 text-white" href="{{url('/')}}"><i class="mr-2 fa fa-angle-right"></i>Home</a>
                                    <a class="mb-2 text-white" href="{{url('/about-us')}}"><i class="mr-2 fa fa-angle-right"></i>About Us</a>
                                    <a class="mb-2 text-white" href="{{url('/contact-us')}}"><i class="mr-2 fa fa-angle-right"></i>Contact Us</a>
                                    <a class="mb-2 text-white" href="{{url('/venture/terms_codition')}}"><i class="mr-2 fa fa-angle-right"></i>Terms & Conditions</a>
                                    <a class="mb-2 text-white" href="{{url('/venture/privacy-policy')}}"><i class="mr-2 fa fa-angle-right"></i>Privacy Policy</a>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                    <h5 class="mb-4 text-white font-weight-bold">HELP CENTER</h5>
                                <div class="d-flex flex-column justify-content-start">
                                    <a class="mb-2 text-white" href="{{url('/faq')}}"><i class="mr-2 fa fa-angle-right"></i>FAQ</a>
                                    <a class="mb-2 text-white" href="{{url('/support')}}"><i class="mr-2 fa fa-angle-right"></i>Help & Support</a>
                                    <a class="mb-2 text-white" href="{{url('/venture/return-refund-policy')}}"><i class="mr-2 fa fa-angle-right"></i>Return & Refund Policy</a>
                                    <a class="mb-2 text-white" href="{{url('/track-order')}}"><i class="mr-2 fa fa-angle-right"></i>Track Order</a>
                                    <a class="mb-2 text-white" href="{{ url('/vendor-login') }}"><i class="mr-2 fa fa-angle-right"></i>Vendor Login</a>
                                </div>
                            </div>
                            <div class="col-md-4 col-12">
                                <h5 class="mt-3 mb-2 text-white mt-lg-0 font-weight-bold">FOLLOW US</h5>
                                <div class="d-flex flex-column justify-content-start">
                                    <a class="mb-2 text-white" href="{{ $basicinfo->facebook }}">
                                        <img src="{{ asset('public/pagefb.jpg') }}" alt="" style="width: 100%;border-radius: 8px;">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <img src="{{asset('public/pb.png')}}" alt="" style="width: 100%">
        </div>
    </div>
    <div class="py-2 row border-top border-light mx-xl-5" id="mobfot">
        <div class="col-12 col-md-6 px-xl-0">
            <p class="mb-2 text-white">
                © 2024 {{ $basicinfo->title }}
            </p>
        </div>
        <div class="col-12 col-md-6 px-xl-0">
            <p class="mb-2" style="text-align:right;color:#fa0051 !important;font-weight:bold">
                 Design & Developed By || Worker99 Ltd
            </p>
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
        transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                    opacity 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        transform: translateY(0);
        opacity: 1;
    }

    /* Hidden state for navbar */
    .bottom-navbar.navbar-hidden,
    .bottom-navbar.sidebar-active {
        transform: translateY(100%);
        opacity: 0;
        pointer-events: none;
    }

    /* Visible state for navbar */
    .bottom-navbar.navbar-visible {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    /* Add a smooth fade-in animation when appearing */
    @keyframes fadeInUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Add a smooth fade-out animation when disappearing */
    @keyframes fadeOutDown {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(100%);
            opacity: 0;
        }
    }

    /* When appearing */
    .bottom-navbar.navbar-visible {
        animation: fadeInUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    /* When disappearing */
    .bottom-navbar.navbar-hidden,
    .bottom-navbar.sidebar-active {
        animation: fadeOutDown 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
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
        color: #fff;
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
<div class="bottom-navbar b-block d-lg-none navbar-visible">
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
    // Global variable to track sidebar state
    let isSidebarActive = false;
    let navbarScrollEnabled = true;

    // Override your existing openprofile() function
    function openprofile() {
        closeNav();
        document.getElementById("myPronav").style.width = "100%";
        
        // Hide navbar when profile sidebar is open
        const navbar = document.querySelector('.bottom-navbar');
        if (navbar) {
            navbar.classList.add('sidebar-active');
            navbar.classList.remove('navbar-visible');
            isSidebarActive = true;
            navbarScrollEnabled = false;
        }
    }

    // Override your existing closeprofile() function
    function closeprofile() {
        document.getElementById("myPronav").style.width = "0";
        
        // Show navbar when profile sidebar is closed
        const navbar = document.querySelector('.bottom-navbar');
        if (navbar) {
            navbar.classList.remove('sidebar-active');
            navbar.classList.add('navbar-visible');
            isSidebarActive = false;
            navbarScrollEnabled = true;
            
            // Check if we should hide based on scroll position
            setTimeout(checkScrollPosition, 100);
        }
    }

    // Override your existing openNav() function
    function openNav() {
        closeprofile();
        document.getElementById("mySidenav").style.width = "100%";
        
        // Hide navbar when main menu is open
        const navbar = document.querySelector('.bottom-navbar');
        if (navbar) {
            navbar.classList.add('sidebar-active');
            navbar.classList.remove('navbar-visible');
            isSidebarActive = true;
            navbarScrollEnabled = false;
        }
    }

    // Override your existing closeNav() function
    function closeNav() {
        document.getElementById("mySidenav").style.width = "0";
        
        // Show navbar when main menu is closed
        const navbar = document.querySelector('.bottom-navbar');
        if (navbar) {
            navbar.classList.remove('sidebar-active');
            navbar.classList.add('navbar-visible');
            isSidebarActive = false;
            navbarScrollEnabled = true;
            
            // Check if we should hide based on scroll position
            setTimeout(checkScrollPosition, 100);
        }
    }

    // Check scroll position to determine if navbar should be hidden
    function checkScrollPosition() {
        if (!navbarScrollEnabled) return;
        
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const navbar = document.querySelector('.bottom-navbar');
        
        if (scrollTop > 100) {
            navbar.classList.remove('navbar-visible');
            navbar.classList.add('navbar-hidden');
        } else {
            navbar.classList.remove('navbar-hidden');
            navbar.classList.add('navbar-visible');
        }
    }

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
        
        // Initialize scroll behavior
        initNavbarScrollBehavior();
    });
    
    function initNavbarScrollBehavior() {
        const navbar = document.querySelector('.bottom-navbar');
        let lastScrollTop = 0;
        let isScrolling = false;
        let scrollTimeout;
        const scrollThreshold = 10;
        const hideDistance = 100;
        
        // Initially show the navbar
        navbar.classList.remove('navbar-hidden');
        navbar.classList.add('navbar-visible');
        
        window.addEventListener('scroll', function() {
            // Don't process scroll events if sidebar is active
            if (!navbarScrollEnabled || isSidebarActive) return;
            
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const scrollDelta = scrollTop - lastScrollTop;
                    
                    // Check if user is scrolling up or down
                    if (Math.abs(scrollDelta) > scrollThreshold) {
                        if (scrollDelta > 0) {
                            // Scrolling DOWN - Show navbar
                            navbar.classList.remove('navbar-hidden');
                            navbar.classList.add('navbar-visible');
                        } else {
                            // Scrolling UP - Hide navbar after a certain distance
                            if (scrollTop > hideDistance) {
                                navbar.classList.remove('navbar-visible');
                                navbar.classList.add('navbar-hidden');
                            } else {
                                // If near top of page, keep navbar visible
                                navbar.classList.remove('navbar-hidden');
                                navbar.classList.add('navbar-visible');
                            }
                        }
                    }
                    
                    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                    isScrolling = false;
                });
                isScrolling = true;
            }
            
            // Reset scrolling flag after a delay
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                isScrolling = false;
            }, 100);
        });
        
        // Show navbar when user touches the bottom of the screen
        let touchStartY = 0;
        let touchEndY = 0;
        
        document.addEventListener('touchstart', function(e) {
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });
        
        document.addEventListener('touchend', function(e) {
            // Don't process if sidebar is active
            if (isSidebarActive) return;
            
            touchEndY = e.changedTouches[0].screenY;
            const screenHeight = window.innerHeight;
            const touchDistance = touchEndY - touchStartY;
            
            // If user swipes up from near the bottom of the screen, show navbar
            if (touchStartY > screenHeight * 0.7 && touchDistance < -50) {
                navbar.classList.remove('navbar-hidden');
                navbar.classList.add('navbar-visible');
            }
        }, { passive: true });
        
        // Show navbar when mouse moves near the bottom
        let mouseMoveTimeout;
        document.addEventListener('mousemove', function(e) {
            // Don't process if sidebar is active
            if (isSidebarActive) return;
            
            const screenHeight = window.innerHeight;
            const mouseY = e.clientY;
            
            // If mouse is near bottom of screen (bottom 50px)
            if (mouseY > screenHeight - 50) {
                navbar.classList.remove('navbar-hidden');
                navbar.classList.add('navbar-visible');
            }
            
            // Clear previous timeout
            clearTimeout(mouseMoveTimeout);
            
            // Hide navbar after mouse inactivity (except when near bottom)
            mouseMoveTimeout = setTimeout(function() {
                if (mouseY <= screenHeight - 100 && !isSidebarActive) {
                    navbar.classList.remove('navbar-visible');
                    navbar.classList.add('navbar-hidden');
                }
            }, 2000);
        });
        
        // Listen for sidebar close events
        document.addEventListener('click', function(e) {
            // If click is on sidebar close button or outside sidebar
            if (e.target.classList.contains('closebtn') || 
                (isSidebarActive && e.target.classList.contains('sidenav'))) {
                // The sidebar close functions (closeprofile, closeNav) will handle showing navbar
                return;
            }
        });
    }
</script>