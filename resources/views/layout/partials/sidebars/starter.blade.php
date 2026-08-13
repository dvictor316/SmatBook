<ul>
    <li class="menu-title"><span>Starter</span></li>

    <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
        <a href="{{ route('home') }}">
            <i class="fe fe-home"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('sales.showPos') ? 'active' : '' }}">
        <a href="{{ route('sales.showPos') }}">
            <i class="fe fe-shopping-cart"></i>
            <span>New Sale</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('pos.reports') ? 'active' : '' }}">
        <a href="{{ route('pos.reports') }}">
            <i class="fe fe-bar-chart-2"></i>
            <span>POS Reports</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('product-list') ? 'active' : '' }}">
        <a href="{{ route('product-list') }}">
            <i class="fe fe-package"></i>
            <span>Products</span>
        </a>
    </li>

    @if(\App\Support\HotelAccess::userIsHotelTenant(auth()->user()))
        <li class="menu-title"><span>Hotel</span></li>
        @include('hotel.partials.tenant-sidebar-menu-flat')
    @endif

    <li class="{{ Request::routeIs('inventory.Products', 'product-list') ? 'active' : '' }}">
        <a href="{{ route('product-list') }}">
            <i class="fe fe-archive"></i>
            <span>Stock Overview</span>
        </a>
    </li>

    @if(auth()->user()?->role === 'super_admin' || auth()->user()?->role === 'administrator')
        <li class="{{ Request::routeIs('users.index') ? 'active' : '' }}">
            <a href="{{ route('users.index') }}">
                <i class="fe fe-user-plus"></i>
                <span>Users</span>
            </a>
        </li>

        <li class="{{ Request::routeIs('roles.index') ? 'active' : '' }}">
            <a href="{{ route('roles.index') }}">
                <i class="fe fe-shield"></i>
                <span>Roles & Permission</span>
            </a>
        </li>
    @endif

    <li class="{{ Request::routeIs('profile') ? 'active' : '' }}">
        <a href="{{ route('profile') }}">
            <i class="fe fe-user"></i>
            <span>Profile</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('membership-plans') ? 'active' : '' }}">
        <a href="{{ route('membership-plans') }}">
            <i class="fe fe-settings"></i>
            <span>Billing & Subscription</span>
        </a>
    </li>
</ul>
