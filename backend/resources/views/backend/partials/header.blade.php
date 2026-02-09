<div class="p-0 container-fluid position-relative">
    <nav class="px-4 py-0 navbar navbar-expand bg-info navbar-dark sticky-top" style="background-color: #f3f0f0 !important">

        <a href="{{ url('/admin/dashboard') }}" class="p-0 navbar-brand">
            <img src="{{ asset(preg_replace('#^public/#', '', \App\Models\Basicinfo::first()->logo ?? '')) }}" alt="logo"
                    style="width:150px">

        </a>
        <div class="p-2 mr-4 navbar-nav align-items-center">
            <a href="#" class="flex-shrink-0 sidebar-toggler">
                 <i class="bi bi-list toggle-sidebar-btn"></i>
            </a>
        </div>


        <h4 style="color:#000;margin:0;"></h4>
        <div class="p-1 mr-4 navbar-nav align-items-center ms-auto">
            <img src="{{ asset('backend/img/user.jpg') }}" style="border-radius: 4px;margin-right: 20px;" alt="noti">
            <div class="profile d-flex">
                 <img src="{{ asset('backend/img/user.jpg') }}" style="width:40px" alt="profile">
                 <div class="d-none d-lg-block name ps-2">
                     <h4 class="m-0" style="font-size: 16px;color:black;">{{Auth::user()->name}}</h4>
                     <small style="color:black;">admin</small>
                 </div>
            </div>
        </div>
    </nav>
</div>
