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
            <li><a href="{{ route('pos.sales') }}">Sales History</a></li>
            <li><a href="{{ route('sales.index') }}">Sales Records</a></li>
            <li><a href="{{ route('pos.reports') }}">POS Reports</a></li>
        </ul>
    </li>

    <li class="submenu {{ Request::is('product-list*', 'add-products*', 'inventory*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-package"></i><span>Inventory</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('product-list') }}">Products</a></li>
            <li><a href="{{ route('add-products') }}">Add Product</a></li>
            <li><a href="{{ route('inventory.Products') }}">Stock Overview</a></li>
            <li><a href="{{ route('inventory.stock-valuation') }}">Stock Valuation</a></li>
            <li><a href="{{ route('inventory.transfer-audit') }}">Stock Movement</a></li>
            <li><a href="{{ route('categories.index') }}">Categories</a></li>
            <li><a href="{{ route('units') }}">Units</a></li>
        </ul>
    </li>

    <li class="submenu {{ Request::is('customers*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('customers.index') }}">Customer List</a></li>
            <li><a href="{{ route('customers.add') }}">Add Customer</a></li>
        </ul>
    </li>

    <li class="submenu {{ Request::is('reports*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('reports.hub') }}">Reports Hub</a></li>
            <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
            <li><a href="{{ route('reports.sales-summary') }}">Sales Summary</a></li>
            <li><a href="{{ route('reports.sales-by-product') }}">Sales by Product</a></li>
            <li><a href="{{ route('reports.sales-by-customer') }}">Sales by Customer</a></li>
            <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
            <li><a href="{{ route('reports.low-stock') }}">Low Stock</a></li>
            <li><a href="{{ route('reports.stock-valuation') }}">Stock Valuation</a></li>
            <li><a href="{{ route('reports.expiry-report') }}">Expiry Report</a></li>
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
