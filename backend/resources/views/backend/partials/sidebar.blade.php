<div class="pb-3 sidebar" style="background-color: #f3f0f0 !important;">
    <nav class="pt-0 navbar bg-info navbar-dark" style="background-color: #f3f0f0 !important;">
        @php
            $adm = App\Models\Admin::where('id', Auth::guard('admin')->user()->id)->first();
            $admin = $adm && $adm->add_by ? App\Models\Admin::where('id', Auth::guard('admin')->user()->id)->where('add_by', 1)->first() : $adm;
            $admin = $admin ?? $adm;
            $isFullAdmin = $adm && $adm->isFullAdmin();
            if ($adm && $adm->isShopAdmin()) {
                $orders =  App\Models\Order::where('store_id', Auth::guard('admin')->user()->id);
            } elseif ($adm->hasRole('Manager') || $adm->hasRole('manager')) {
                $orders =  App\Models\Order::where('store_id', $adm->add_by);
            } elseif ($adm->hasRole('Superadmin') || $adm->hasRole('superadmin')) {
                $orders =  App\Models\Order::where('status','!=','');
            } else {
                $orders =  App\Models\Order::where('admin_id', Auth::guard('admin')->user()->id);
            }
        @endphp

        <div class="navbar-nav w-100">
            <a href="{{ url('admin/dashboard') }}" class="nav-item nav-link">Dashboard</a>
            @if($isFullAdmin)

            <small style="text-align: center;text-transform: uppercase;font-size: 12px;background: aliceblue;font-weight: bold; color: red;">-Information-</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Category</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.categorys.index') }}" class="dropdown-item">Category</a>
                    <a href="{{ route('admin.subcategorys.index') }}" class="dropdown-item">Sub-Category</a>
                    <a href="{{ route('admin.minicategorys.index') }}" class="dropdown-item">Mini-Category</a>
                    <a href="{{ route('admin.brands.index') }}" class="dropdown-item">Brand</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Banners</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.sliders.index') }}" class="dropdown-item">Banners</a>
                    <a href="{{ route('admin.addbanners.index') }}" class="dropdown-item">Front Banners</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Attributes</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.attributes.index') }}" class="dropdown-item">Attributes</a>
                    <a href="{{ route('admin.attrvalues.index') }}" class="dropdown-item">Values</a>
                </div>
            </div>
            @endif
            <a href="{{ route('admin.products.index') }}" class="nav-item nav-link">Products</a>
            @if($isFullAdmin)
            <a href="{{ url('admin/shop/products') }}" class="nav-item nav-link">Shops Products</a>
            @endif

            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Orders</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin_order/Pending') }}" class="dropdown-item">Pending <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Pending')->get()->count()}})</span></a>
                <a href="{{ url('admin_order/Canceled') }}" class="dropdown-item">Canceled <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Canceled')->get()->count()}})</span></a>
                <a href="{{ url('admin_order/Confirmed') }}" class="dropdown-item">Confirmed <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Confirmed')->get()->count()}})</span></a>
                <a href="{{ url('admin_order/Processing') }}" class="dropdown-item">Processing <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Processing')->get()->count()}})</span></a>
                <a href="{{ url('admin_order/Packageing') }}" class="dropdown-item">Packageing <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Packageing')->get()->count()}})</span></a>
                <a href="{{ url('admin_order/Ontheway') }}" class="dropdown-item">Ontheway <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Ontheway')->get()->count()}})</span></a>
                <a href="{{ url('admin_order/Delivered') }}" class="dropdown-item">Delivered <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Delivered')->get()->count()}})</span></a>
                <a href="{{ url('admin_order/Return') }}" class="dropdown-item">Return <span style="color:red;font-weight:bold;font-size:14px">({{$orders->where('status','Return')->get()->count()}})</span></a>
                </div>
            </div>



                @if($adm->isFullAdmin())
                    <a href="{{ route('admin.basicinfos.index') }}" class="nav-item nav-link">Settings</a>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Shops</a>
                        <div class="bg-transparent border-0 dropdown-menu">
                            <a href="{{ route('admin.roles.index') }}" class="dropdown-item">Roles & Permissions</a>
                            <a href="{{ route('admin.admins.index') }}" class="dropdown-item">Shops</a>
                            <a href="{{ url('admin/executive') }}" class="dropdown-item">H.R / Executive</a>
                        </div>
                    </div>
                @elseif($adm->isShopAdmin())
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Accounts</a>
                        <div class="bg-transparent border-0 dropdown-menu">
                            <a href="{{ url('admin/accounts') }}" class="dropdown-item">Payments</a>
                            <a href="{{ url('admin/withdraws') }}" class="dropdown-item">Withdraws</a>
                        </div>
                    </div>
                @endif
                @if($isFullAdmin)
                <a href="{{ route('admin.users.index') }}" class="nav-item nav-link">Users</a>
                <a href="{{ url('admin/view-active/user') }}" class="nav-item nav-link">Active User</a>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                    <div class="bg-transparent border-0 dropdown-menu">

                        <a href="{{ url('admin/information/about_us') }}" class="dropdown-item">About Us</a>
                        <a href="{{ url('admin/information/contact_us') }}" class="dropdown-item">Contact Us</a>
                        <a href="{{ url('admin/information/terms_codition') }}" class="dropdown-item">Terms Conditions</a>
                        <a href="{{ url('admin/information/privacy-policy') }}" class="dropdown-item">Privacy Policy</a>
                        <a href="{{ url('admin/information/return-refund-policy') }}" class="dropdown-item">Return & Refund Policy</a>
                    </div>
                </div>
                @endif

            @if($isFullAdmin)

            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Others</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('couriers.index') }}" class="dropdown-item">Courier</a>
                    <a href="{{ route('cities.index') }}" class="dropdown-item">City</a>
                    <a href="{{ route('zones.index') }}" class="dropdown-item">Zone</a>
                    <a href="{{ route('payments.index') }}" class="dropdown-item">Bank</a>
                    <a href="{{ route('packages.index') }}" class="dropdown-item">Account Package</a>
                    <a href="{{ url('resellerinvoice/Unpaid') }}" class="dropdown-item">Reseller-Invoices</a>
                    <a href="{{ route('paymenttypes.index') }}" class="dropdown-item">Accounts</a>
                </div>
            </div>

            @endif
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Reports</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    @if($adm->isShopAdmin())
                        <a href="{{ route('courieruserreport') }}" class="dropdown-item">Sales Report</a>
                    @else
                    <a href="{{ route('courieruserreport') }}" class="dropdown-item">Sales Report</a>
                    <a href="{{ route('courierreport') }}" class="dropdown-item">Courier Report</a>
                    <a href="{{ route('userreport') }}" class="dropdown-item">User Report</a>
                    <a href="{{ route('productreport') }}" class="dropdown-item">Product</a>
                    <a href="{{ route('paymentreport') }}" class="dropdown-item">Payment</a>
                    @endif
                </div>
            </div>
            @if($isFullAdmin)
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Tickets</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/supporttikits') }}" class="dropdown-item">Ticket</a>
                </div>
            </div>
            @endif
            @if($isFullAdmin)
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Frauds</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/fraud/Pending') }}" class="dropdown-item">Pending</a>
                    <a href="{{ url('admin/fraud/Accepted') }}" class="dropdown-item">Accepted</a>
                    <a href="{{ url('admin/fraud/Cancel') }}" class="dropdown-item">Cancel</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Product RQ</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/product-request/Pending') }}" class="dropdown-item">Pending</a>
                    <a href="{{ url('admin/product-request/Accepted') }}" class="dropdown-item">Accepted</a>
                    <a href="{{ url('admin/product-request/Done') }}" class="dropdown-item">Done</a>
                    <a href="{{ url('admin/product-request/Cancel') }}" class="dropdown-item">Cancel</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Faq</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('faqs.index') }}" class="dropdown-item">Faq</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Withdrew</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('withdrew/Pending') }}" class="dropdown-item">Reseller</a>
                    <a href="{{ url('admin/view-withdraws/Pending') }}" class="dropdown-item">Vendor</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Courses</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('coursecategories.index') }}" class="dropdown-item">Course Category</a>
                    <a href="{{ route('courses.index') }}" class="dropdown-item">Courses</a>
                </div>
            </div>
            @if($isFullAdmin)
            <a href="{{ url('admin/vendors') }}" class="nav-item nav-link">Vendor Requests</a>
            @endif
            <br>
            @endif

            {{-- <a href="{{ url('download/info') }}" class="nav-item nav-link">Download Order</a> --}}

            <a href="{{ url('admin/profile') }}" class="nav-item nav-link">My Profile</a>

            <a href="{{ route('admin.logout') }}"
                style="font-size: 16px;padding: 16px;color: red; font-weight: bold; text-transform: uppercase;text-align: center;"><i
                    class="fa fa-sign-out-alt" aria-hidden="true" style="margin-right: 6px"></i>Log Out</a>
        </div>
    </nav>
</div>

<style>
    ::-webkit-scrollbar {
        display: none;
    }

    #orderinfodata{
        display: none;
    }
    #setting{
        display: none
    }

    #settinghide{
        display: none
    }

    #other{
        display: none;
    }
    #otherhide{
        display: none
    }

    #setting{
        display: none
    }

    #hide{
        display: none;
    }
</style>

<script>
    function showsetting(){
        $('#setting').css('display','inline-block');
        $('#settingshow').css('display','none');
        $('#settinghide').css('display','inline-block');
    }

    function hidesetting(){
        $('#setting').css('display','none');
        $('#settingshow').css('display','inline-block');
        $('#settinghide').css('display','none');
    }

    function showorder(){
        $('#orderinfodata').css('display','inline-block');
        $('#show').css('display','none');
        $('#hide').css('display','inline-block');
    }

    function hideorder(){
        $('#orderinfodata').css('display','none');
        $('#show').css('display','inline-block');
        $('#hide').css('display','none');
    }

    function showother(){
        $('#other').css('display','inline-block');
        $('#othershow').css('display','none');
        $('#otherhide').css('display','inline-block');
    }

    function hideother(){
        $('#other').css('display','none');
        $('#othershow').css('display','inline-block');
        $('#otherhide').css('display','none');
    }

</script>
