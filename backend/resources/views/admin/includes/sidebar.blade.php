<aside id="sidebar" class="sidebar">
    @php
        $admin = App\Models\Admin::where('id', Auth::guard('admin')->user()->id)->first();
    @endphp
    <ul class="sidebar-nav" id="sidebar-nav">

        {{-- ═══ MAIN ═══ --}}
        <li class="nav-heading">Main</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/dashboard') ? 'active-nav' : 'collapsed' }}" href="{{ url('admin/dashboard') }}">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        @if ($admin->hasrole('Executive'))
        @else
            {{-- ═══ STORE ═══ --}}
            <li class="nav-heading">Store</li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#store-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-box-seam"></i><span>Inventory</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="store-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('purchases.index') }}">
                            <span>Purchase</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('stocks.index') }}">
                            <span>Stock</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('suppliers.index') }}">
                            <span>Supplier</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#finance-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-wallet2"></i><span>Finance</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="finance-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('payments.index') }}">
                            <span>Payment</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('paymenttypes.index') }}">
                            <span>Payment Method</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('packages.index') }}">
                            <span>Packages</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- ═══ SHIPPING ═══ --}}
            <li class="nav-heading">Shipping</li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#courier-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-truck"></i><span>Courier</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="courier-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('couriers.index') }}">
                            <span>Courier</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cities.index') }}">
                            <span>City</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('zones.index') }}">
                            <span>Zone</span>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        {{-- ═══ ORDERS ═══ --}}
        <li class="nav-heading">Orders</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin_order/Pending') ? 'active-nav' : 'collapsed' }}" href="{{ url('admin_order/Pending') }}">
                <i class="bi bi-clock"></i>
                <span>Pending</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin_order/On Delivery') ? 'active-nav' : 'collapsed' }}" href="{{ url('admin_order/On Delivery') }}">
                <i class="bi bi-send"></i>
                <span>On Delivery</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin_order/Delivered') ? 'active-nav' : 'collapsed' }}" href="{{ url('admin_order/Delivered') }}">
                <i class="bi bi-check2-circle"></i>
                <span>Delivered</span>
            </a>
        </li>

        {{-- ═══ SUPPORT ═══ --}}
        <li class="nav-heading">Support</li>
        @if ($admin->hasRole('manager') || $admin->hasrole('Executive'))
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ url('order/complain') }}">
                    <i class="bi bi-chat-dots"></i>
                    <span>Complain</span>
                </a>
            </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ request()->is('complain/*') ? 'active-nav' : 'collapsed' }}" href="{{ url('complain/Pending') }}">
                <i class="bi bi-inbox"></i>
                <span>Complain Box</span>
            </a>
        </li>

        @if ($admin->hasRole('manager') || $admin->hasrole('Executive'))
        @else
            {{-- ═══ REPORTS ═══ --}}
            <li class="nav-heading">Reports</li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#report-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-bar-chart-line"></i><span>Reports</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="report-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('courieruserreport') }}">
                            <span>Courier User Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('courierreport') }}">
                            <span>Courier Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('userreport') }}">
                            <span>User Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('productreport') }}">
                            <span>Product</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('paymentreport') }}">
                            <span>Payment</span>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

    </ul>

</aside>
