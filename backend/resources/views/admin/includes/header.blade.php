 <!-- ======= Header ======= -->
 <header id="header" class="header fixed-top d-flex align-items-center">

     <div class="d-flex align-items-center justify-content-between">
         <a href="{{ url('admin/dashboard') }}" class="text-center text-decoration-none" id="largelogotext">
             <h2>{{ env('APP_NAME') }} <span style="font-weight:400;font-size:13px;color:#64748b;margin-left:2px;">Admin</span></h2>
         </a>
         <a href="{{ url('admin/dashboard') }}" class="text-center text-decoration-none" id="small_logo">
             <h2>{{ substr(env('APP_NAME'), 0, 2) }}</h2>
         </a>
         <i class="bi bi-list toggle-sidebar-btn"></i>
     </div><!-- End Logo -->

     <div class="search-bar">
         <form class="search-form d-flex align-items-center" method="POST" action="#">
             <input type="text" name="query" placeholder="Search..." title="Enter search keyword">
             <button type="submit" title="Search"><i class="bi bi-search"></i></button>
         </form>
     </div><!-- End Search Bar -->

     <nav class="header-nav ms-auto">
         <ul class="d-flex align-items-center">

             <li class="nav-item d-block d-lg-none">
                 <a class="nav-link nav-icon search-bar-toggle " href="#">
                     <i class="bi bi-search"></i>
                 </a>
             </li><!-- End Search Icon-->

             {{-- Notification Bell (only show if real notifications exist) --}}
             <li class="nav-item dropdown">
                 <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                     <i class="bi bi-bell"></i>
                 </a>
                 <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
                     <li class="dropdown-header">
                         No new notifications
                     </li>
                 </ul>
             </li><!-- End Notification Nav -->

             {{-- Profile Dropdown --}}
             <li class="nav-item dropdown pe-3">

                 <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                     data-bs-toggle="dropdown">
                     @if (empty(Auth::user()->profile))
                         <img src="{{ asset('public/admin/assets/img/blanck-user.jpg') }}" alt="Profile"
                             class="rounded-circle">
                     @else
                         <img src="{{ asset(Auth::user()->profile) }}" alt="Profile" class="rounded-circle">
                     @endif

                     <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->name }}</span>
                 </a><!-- End Profile Image Icon -->

                 <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                     <li class="dropdown-header">
                         <h6>{{ Auth::user()->name }}</h6>
                         @if (Auth::user()->role == 2)
                             <span>Super Admin</span>
                         @elseif (Auth::user()->role == 1)
                             <span>Manager</span>
                         @else
                             <span>User</span>
                         @endif
                     </li>
                     <li>
                         <hr class="dropdown-divider">
                     </li>
                     @if (Auth::user()->role == 2)
                         <li>
                             <a class="dropdown-item d-flex align-items-center" href="{{ url('/my/profile') }}">
                                 <i class="bi bi-person"></i>
                                 <span>My Profile</span>
                             </a>
                         </li>
                         <li>
                             <a class="dropdown-item d-flex align-items-center" href="{{ url('/account/settings') }}">
                                 <i class="bi bi-gear"></i>
                                 <span>Account Settings</span>
                             </a>
                         </li>
                         <li>
                             <hr class="dropdown-divider">
                         </li>
                     @endif

                     <li>
                         <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.logout') }}">
                             <i class="bi bi-box-arrow-right"></i>
                             <span>Sign Out</span>
                         </a>
                     </li>
                 </ul><!-- End Profile Dropdown Items -->
             </li><!-- End Profile Nav -->

         </ul>
     </nav><!-- End Icons Navigation -->

 </header><!-- End Header -->
