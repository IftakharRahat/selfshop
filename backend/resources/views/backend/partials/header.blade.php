<div class="p-0 container-fluid position-relative">
    <nav class="px-4 py-0 navbar navbar-expand navbar-light sticky-top" style="background-color: #fff !important; border-bottom: 1px solid #e2e8f0;">

        <div class="p-2 mr-4 navbar-nav align-items-center">
            <a href="#" class="flex-shrink-0 sidebar-toggler">
                 <i class="bi bi-list toggle-sidebar-btn" style="font-size:22px;color:#1e293b;"></i>
            </a>
        </div>

        <h4 style="color:#1e293b;margin:0;font-family:'Inter',sans-serif;font-size:16px;font-weight:600;">
            @yield('title', 'Dashboard')
        </h4>

        <div class="p-1 mr-4 navbar-nav align-items-center ms-auto">
            <div class="profile d-flex align-items-center">
                 <div class="d-none d-lg-block name ps-2 text-end pe-3">
                     <h4 class="m-0" style="font-size: 14px;color:#1e293b;font-family:'Inter',sans-serif;font-weight:600;">{{Auth::user()->name}}</h4>
                     <small style="color:#64748b;font-size:12px;">admin</small>
                 </div>
                 <img src="{{ asset('backend/img/user.jpg') }}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;" alt="profile">
            </div>
        </div>
    </nav>
</div>
