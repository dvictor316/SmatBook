<ul>
    <li class="menu-title"><span>Starter</span></li>

    <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
        <a href="{{ route('home') }}">
            <i class="fe fe-home"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-shopping-cart"></i><span>POS & Sales</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('sales.showPos') }}">New Sale</a></li>
            <li><a href="{{ route('pos.reports') }}">POS Reports</a></li>
        </ul>
    </li>

    <li class="submenu {{ Request::is('product-list*', 'add-products*', 'inventory*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-package"></i><span>Inventory</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('product-list') }}">Products</a></li>
            <li><a href="{{ route('inventory.Products') }}">Stock Overview</a></li>
        </ul>
    </li>

    @if(auth()->user()?->role === 'super_admin' || auth()->user()?->role === 'administrator')
        <li class="submenu {{ Request::is('users*', 'roles*') ? 'active subdrop' : '' }}">
            <a href="#"><i class="fe fe-user-plus"></i><span>Team</span><span class="menu-arrow"></span></a>
            <ul>
                <li><a href="{{ route('users.index') }}">Users</a></li>
                <li><a href="{{ route('roles.index') }}">Roles & Permission</a></li>
            </ul>
        </li>
    @endif

    <li class="submenu {{ Request::is('profile*', 'settings*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-settings"></i><span>Account</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('profile') }}">Profile</a></li>
            <li><a href="{{ route('membership-plans') }}">Billing & Subscription</a></li>
        </ul>
    </li>
</ul>
