<div class="sidebar">
    {{-- Sidebar Brand / Logo --}}
    <div class="sidebar-brand">
        <a href="{{ url('admin/dashboard') }}">
            <img src="{{ asset(preg_replace('#^public/#', '', \App\Models\Basicinfo::first()->logo ?? '')) }}" alt="logo"
                style="max-width:140px;">
        </a>
    </div>

    <nav class="pt-0 navbar bg-transparent navbar-dark" style="background-color: transparent !important;">
        @php
            $adm = App\Models\Admin::where('id', Auth::guard('admin')->user()->id)->first();
            $admin = $adm && $adm->add_by ? App\Models\Admin::where('id', Auth::guard('admin')->user()->id)->where('add_by', 1)->first() : $adm;
            $admin = $admin ?? $adm;
            $isFullAdmin = $adm && $adm->isFullAdmin();
            $isStaffAdmin = $adm && $adm->isStaffAdmin();
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

            {{-- ═══ MAIN ═══ --}}
            <small class="nav-section-title">Main</small>
            <a href="{{ url('admin/dashboard') }}" class="nav-item nav-link {{ request()->is('admin/dashboard') ? 'active-nav' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle {{ request()->is('admin/crm-dashboard') || request()->is('admin/crm/*') ? 'active-nav' : '' }}" data-bs-toggle="dropdown">
                    <i class="bi bi-bar-chart-line"></i> CRM
                </a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.crm.dashboard') }}" class="dropdown-item">Dashboard</a>
                    <a href="{{ route('admin.crm.users') }}" class="dropdown-item">Users</a>
                    <a href="{{ route('admin.crm.suppliers') }}" class="dropdown-item">Suppliers</a>
                </div>
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="nav-item nav-link {{ request()->is('admin/notifications*') ? 'active-nav' : '' }}">
                <i class="bi bi-bell"></i> Send Notification
            </a>

            @if($isFullAdmin || $isStaffAdmin)
            {{-- ═══ CATALOG ═══ --}}
            <small class="nav-section-title">Catalog</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-layers"></i> Category</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.categorys.index') }}" class="dropdown-item">Category</a>
                    <a href="{{ route('admin.subcategorys.index') }}" class="dropdown-item">Sub-Category</a>
                    <a href="{{ route('admin.minicategorys.index') }}" class="dropdown-item">Mini-Category</a>
                    <a href="{{ route('admin.brands.index') }}" class="dropdown-item">Brand</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-images"></i> Banners</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.sliders.index') }}" class="dropdown-item">Banners</a>
                    <a href="{{ route('admin.addbanners.index') }}" class="dropdown-item">Front Banners</a>
                    <a href="{{ route('admin.flashsales.index') }}" class="dropdown-item">Flash Sale</a>
                    <a href="{{ route('admin.promotional-sections.index') }}" class="dropdown-item">Promotional Sections</a>
                </div>
            </div>
            @if($isFullAdmin)
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-palette"></i> Attributes</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.attributes.index') }}" class="dropdown-item">Attributes</a>
                    <a href="{{ route('admin.attrvalues.index') }}" class="dropdown-item">Values</a>
                </div>
            </div>
            @endif
            @endif

            <a href="{{ route('admin.products.index') }}" class="nav-item nav-link {{ request()->is('admin/products*') ? 'active-nav' : '' }}">
                <i class="bi bi-box-seam"></i> Products
            </a>
            @if($isFullAdmin || $isStaffAdmin)
            <a href="{{ url('admin/shop/products') }}" class="nav-item nav-link {{ request()->is('admin/shop/products*') ? 'active-nav' : '' }}">
                <i class="bi bi-shop-window"></i> Shops Products
            </a>
            @endif

            {{-- ═══ ORDERS ═══ --}}
            <small class="nav-section-title">Orders</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-bag-check"></i> Orders</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    @php
                        $orderCounts = (clone $orders)->selectRaw("
                            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                            SUM(CASE WHEN status = 'Canceled' THEN 1 ELSE 0 END) as canceled_count,
                            SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                            SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing_count,
                            SUM(CASE WHEN status = 'Packageing' THEN 1 ELSE 0 END) as packageing_count,
                            SUM(CASE WHEN status = 'Ontheway' THEN 1 ELSE 0 END) as ontheway_count,
                            SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_count,
                            SUM(CASE WHEN status = 'Return' THEN 1 ELSE 0 END) as return_count
                        ")->first();
                    @endphp
                    <a href="{{ url('admin_order/Pending') }}" class="dropdown-item">Pending @if($orderCounts->pending_count > 0)<span class="badge bg-danger ms-1">{{ $orderCounts->pending_count }}</span>@endif</a>
                    <a href="{{ url('admin_order/Canceled') }}" class="dropdown-item">Canceled @if($orderCounts->canceled_count > 0)<span class="badge bg-danger ms-1">{{ $orderCounts->canceled_count }}</span>@endif</a>
                    <a href="{{ url('admin_order/Confirmed') }}" class="dropdown-item">Confirmed @if($orderCounts->confirmed_count > 0)<span class="badge bg-success ms-1">{{ $orderCounts->confirmed_count }}</span>@endif</a>
                    <a href="{{ url('admin_order/Processing') }}" class="dropdown-item">Processing @if($orderCounts->processing_count > 0)<span class="badge bg-warning text-dark ms-1">{{ $orderCounts->processing_count }}</span>@endif</a>
                    <a href="{{ url('admin_order/Packageing') }}" class="dropdown-item">Packaging @if($orderCounts->packageing_count > 0)<span class="badge bg-primary ms-1">{{ $orderCounts->packageing_count }}</span>@endif</a>
                    <a href="{{ url('admin_order/Ontheway') }}" class="dropdown-item">On the Way @if($orderCounts->ontheway_count > 0)<span class="badge bg-info ms-1">{{ $orderCounts->ontheway_count }}</span>@endif</a>
                    <a href="{{ url('admin_order/Delivered') }}" class="dropdown-item">Delivered @if($orderCounts->delivered_count > 0)<span class="badge bg-success ms-1">{{ $orderCounts->delivered_count }}</span>@endif</a>
                    <a href="{{ url('admin_order/Return') }}" class="dropdown-item">Return @if($orderCounts->return_count > 0)<span class="badge bg-danger ms-1">{{ $orderCounts->return_count }}</span>@endif</a>
                </div>
            </div>

            @if($adm->isFullAdmin())
            {{-- ═══ MANAGEMENT (Full Admin Only) ═══ --}}
            <small class="nav-section-title">Management</small>
            <a href="{{ route('admin.basicinfos.index') }}" class="nav-item nav-link {{ request()->is('admin/basicinfos*') ? 'active-nav' : '' }}">
                <i class="bi bi-gear"></i> Settings
            </a>
            <a href="{{ route('admin.sales-targets.index') }}" class="nav-item nav-link {{ request()->is('admin/sales-targets*') ? 'active-nav' : '' }}">
                <i class="bi bi-bullseye"></i> Sales Targets
            </a>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-shop"></i> Shops</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.roles.index') }}" class="dropdown-item">Roles & Permissions</a>
                    <a href="{{ route('admin.admins.index') }}" class="dropdown-item">Shops</a>
                    <a href="{{ url('admin/executive') }}" class="dropdown-item">H.R / Executive</a>
                </div>
            </div>
            @elseif($adm->isShopAdmin())
            <small class="nav-section-title">Management</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-cash-stack"></i> Accounts</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/accounts') }}" class="dropdown-item">Payments</a>
                    <a href="{{ url('admin/withdraws') }}" class="dropdown-item">Withdraws</a>
                </div>
            </div>
            @endif

            @if($isFullAdmin || $isStaffAdmin)
            {{-- ═══ SUPPLIERS (Full Admin + Staff) ═══ --}}
            <small class="nav-section-title">Suppliers</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-truck"></i> Suppliers</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('admin.vendors.index') }}" class="dropdown-item">All Suppliers</a>
                    <a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="dropdown-item">Active Suppliers</a>
                    <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="dropdown-item">Supplier Requests</a>
                    <a href="{{ route('admin.vendor-products.index') }}" class="dropdown-item">Supplier Products</a>
                    <a href="{{ route('admin.reviews.index') }}" class="dropdown-item">Product Reviews</a>
                    <a href="{{ route('admin.vendor-category-discounts.index') }}" class="dropdown-item">Supplier Category Discounts</a>
                    <a href="{{ route('admin.vendor-category-commissions.index') }}" class="dropdown-item">Supplier Category Commissions</a>
                    <a href="{{ url('admin/view-vendor-payout-requests/pending') }}" class="dropdown-item">Supplier Payout Requests</a>
                </div>
            </div>
            @endif

            @if($isFullAdmin || $isStaffAdmin)
            {{-- ═══ USERS ═══ --}}
            <small class="nav-section-title">Users</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-people"></i> Users</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    @if($isFullAdmin)
                    <a href="{{ route('admin.users.index') }}" class="dropdown-item">All Users</a>
                    @endif
                    <a href="{{ route('admin.manage-users') }}" class="dropdown-item">Manage Users</a>
                    @if($isFullAdmin)
                    <a href="{{ url('admin/view-active/user') }}" class="dropdown-item">Active Users</a>
                    @endif
                </div>
            </div>
            @endif

            @if($isFullAdmin)
            {{-- ═══ CONTENT (Full Admin Only) ═══ --}}
            <small class="nav-section-title">Content</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-file-earmark-text"></i> Pages</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/information/about_us') }}" class="dropdown-item">About Us</a>
                    <a href="{{ url('admin/information/contact_us') }}" class="dropdown-item">Contact Us</a>
                    <a href="{{ url('admin/information/terms_codition') }}" class="dropdown-item">Terms Conditions</a>
                    <a href="{{ url('admin/information/privacy-policy') }}" class="dropdown-item">Privacy Policy</a>
                    <a href="{{ url('admin/information/return-refund-policy') }}" class="dropdown-item">Return & Refund Policy</a>
                </div>
            </div>
            @endif

            {{-- ═══ REPORTS ═══ --}}
            <small class="nav-section-title">Reports & Support</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-bar-chart-line"></i> Reports</a>
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
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-ticket-perforated"></i> Tickets</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/supporttikits') }}" class="dropdown-item">Ticket</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-exclamation-triangle"></i> Frauds</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/fraud/Pending') }}" class="dropdown-item">Pending</a>
                    <a href="{{ url('admin/fraud/Accepted') }}" class="dropdown-item">Accepted</a>
                    <a href="{{ url('admin/fraud/Cancel') }}" class="dropdown-item">Cancel</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-cart-plus"></i> Product RQ</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('admin/product-request/Pending') }}" class="dropdown-item">Pending</a>
                    <a href="{{ url('admin/product-request/Accepted') }}" class="dropdown-item">Accepted</a>
                    <a href="{{ url('admin/product-request/Done') }}" class="dropdown-item">Done</a>
                    <a href="{{ url('admin/product-request/Cancel') }}" class="dropdown-item">Cancel</a>
                </div>
            </div>

            {{-- ═══ OTHERS ═══ --}}
            <small class="nav-section-title">Others</small>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i> Others</a>
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
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-question-circle"></i> Faq</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('faqs.index') }}" class="dropdown-item">Faq</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-wallet2"></i> Withdrew</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ url('withdrew/Pending') }}" class="dropdown-item">Reseller</a>
                    <a href="{{ url('admin/view-withdraws/Pending') }}" class="dropdown-item">Supplier</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-mortarboard"></i> Courses</a>
                <div class="bg-transparent border-0 dropdown-menu">
                    <a href="{{ route('coursecategories.index') }}" class="dropdown-item">Course Category</a>
                    <a href="{{ route('courses.index') }}" class="dropdown-item">Courses</a>
                </div>
            </div>
            @endif

            {{-- ═══ ACCOUNT ═══ --}}
            <small class="nav-section-title">Account</small>
            <a href="{{ url('admin/profile') }}" class="nav-item nav-link {{ request()->is('admin/profile*') ? 'active-nav' : '' }}">
                <i class="bi bi-person"></i> My Profile
            </a>

            <a href="{{ route('admin.logout') }}" class="nav-item nav-link logout-link">
                <i class="fa fa-sign-out-alt"></i> Log Out
            </a>
        </div>
    </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var currentUrl = window.location.href;
    // Find the dropdown-item that matches the current URL
    document.querySelectorAll('.sidebar .dropdown-item').forEach(function(item) {
        if (item.href === currentUrl || currentUrl.indexOf(item.getAttribute('href')) !== -1) {
            item.classList.add('active');
            // Open the parent dropdown
            var dropdownMenu = item.closest('.dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.classList.add('show');
                var toggle = dropdownMenu.previousElementSibling;
                if (toggle) {
                    toggle.classList.add('active-nav');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
});
</script>
