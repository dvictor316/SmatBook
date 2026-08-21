
<ul>
    <li class="menu-title"><span>Main</span></li>

    <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
        <a href="{{ route('home') }}">
            <i class="fe fe-home"></i>
            <span>Dashboard</span>
        </a>
    </li>

    @if(\App\Support\HotelAccess::userIsHotelTenant(auth()->user()))
    <li class="submenu {{ Request::is('hotel*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-briefcase"></i><span>Hotel</span><span class="menu-arrow"></span></a>
        <ul>
            @include('hotel.partials.tenant-sidebar-menu')
        </ul>
    </li>
    @endif

    <li class="submenu {{ Request::is('pos*', 'sales*', 'invoices*', 'add-invoice*', 'quotations*', 'customers*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-dollar-sign"></i><span>Sales</span><span class="menu-arrow"></span></a>
        <ul>
            @if(Route::has('sales.showPos'))
                <li><a href="{{ route('sales.showPos') }}">POS Terminal</a></li>
            @endif
            @if(Route::has('pos.sales'))
                <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
            @endif
            @if(Route::has('invoices.index'))
                <li><a href="{{ route('invoices.index') }}">Invoices</a></li>
            @endif
            @if(Route::has('add-invoice'))
                <li><a href="{{ route('add-invoice') }}">Create Invoice</a></li>
            @endif
            @if(Route::has('quotations'))
                <li><a href="{{ route('quotations') }}">Quotations</a></li>
            @endif
            @if(Route::has('customers.index'))
                <li><a href="{{ route('customers.index') }}">Customers</a></li>
            @endif
            @if(Route::has('customers.add'))
                <li><a href="{{ route('customers.add') }}">Add Customer</a></li>
            @endif
        </ul>
    </li>

    <li class="submenu {{ Request::is('suppliers*', 'purchases*', 'purchase-transaction') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-shopping-bag"></i><span>Purchases</span><span class="menu-arrow"></span></a>
        <ul>
            @if(Route::has('purchases.index'))
                <li><a href="{{ route('purchases.index') }}">Purchases</a></li>
            @endif
            @if(Route::has('purchases.create'))
                <li><a href="{{ route('purchases.create') }}">Bills</a></li>
            @endif
            @if(Route::has('purchase-transaction'))
                <li><a href="{{ route('purchase-transaction') }}">Purchase Ledger</a></li>
            @endif
            @if(Route::has('suppliers.index'))
                <li><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
            @endif
            @if(Route::has('suppliers.create'))
                <li><a href="{{ route('suppliers.create') }}">Add Supplier</a></li>
            @endif
        </ul>
    </li>

    <li class="submenu {{ Request::is('product-list*', 'add-products*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-package"></i><span>Inventory</span><span class="menu-arrow"></span></a>
        <ul>
            @if(Route::has('product-list'))
                <li><a href="{{ route('product-list') }}">Products</a></li>
            @endif
            @if(Route::has('add-products'))
                <li><a href="{{ route('add-products') }}">Add Product</a></li>
            @endif
        </ul>
    </li>

    <li class="submenu {{ Request::is('payments*', 'expenses*', 'advance-payments*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-credit-card"></i><span>Money</span><span class="menu-arrow"></span></a>
        <ul>
            @if(Route::has('payments.index'))
                <li><a href="{{ route('payments.index') }}">Payments</a></li>
            @endif
            @if(Route::has('expenses.index'))
                <li><a href="{{ route('expenses.index') }}">Expenses</a></li>
            @endif
            @include('layout.partials.sidebars.advance-payments-menu')
        </ul>
    </li>

    <li class="submenu {{ Request::is('reports*', 'trial-balance', 'balance-sheet', 'cash-flow') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
        <ul>
            @include('layout.partials.sidebars.reports-menu', ['reportAccess' => 'basic', 'nested' => true])
        </ul>
    </li>

    <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-grid"></i><span>Workspace</span><span class="menu-arrow"></span></a>
        <ul>
            @if(Route::has('chat.index'))
                <li><a href="{{ route('chat.index') }}">Chat</a></li>
            @endif
            @if(Route::has('calendar'))
                <li><a href="{{ route('calendar') }}">Calendar</a></li>
            @endif
            @if(Route::has('messages.index'))
                <li><a href="{{ route('messages.index') }}">Messages</a></li>
            @endif
        </ul>
    </li>

    <li class="submenu">
        <a href="#"><i class="fe fe-lock"></i><span>Upgrade</span><span class="menu-arrow"></span></a>
        <ul>
            <li>
                <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'pro']) : url('/membership-plans?plan=pro') }}">Purchase Orders <span class="badge bg-info">Pro</span></a>
            </li>
            <li>
                <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'pro']) : url('/membership-plans?plan=pro') }}">Debit Notes & Returns <span class="badge bg-info">Pro</span></a>
            </li>
            <li>
                <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'pro']) : url('/membership-plans?plan=pro') }}">Supplier Analytics <span class="badge bg-info">Pro</span></a>
            </li>
            <li>
                <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">Cash Flow Reports <span class="badge bg-warning">Enterprise</span></a>
            </li>
            <li>
                <a href="javascript:void(0);" onclick="showUpgradeModal('Pro', 'Advanced procurement & supplier analytics')">Why Upgrade? <span class="badge bg-light text-dark">Info</span></a>
            </li>
        </ul>
    </li>

    <li class="submenu {{ Request::is('users*', 'settings*', 'roles*', 'activity-log*', 'audit*', 'profile*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-settings"></i><span>Settings</span><span class="menu-arrow"></span></a>
        <ul>
            @if(Route::has('users.index'))
                <li><a href="{{ route('users.index') }}">Users</a></li>
            @endif
            @if(Route::has('settings.index'))
                <li><a href="{{ route('settings.index') }}">Settings</a></li>
            @endif
            @if(Route::has('roles.index'))
                <li><a href="{{ route('roles.index') }}">Roles & Permission</a></li>
            @endif
            @if(Route::has('activity-log.index'))
                <li><a href="{{ route('activity-log.index') }}">Activity Log</a></li>
            @endif
            @if(Route::has('audit.index'))
                <li><a href="{{ route('audit.index') }}">Audit Trail</a></li>
            @endif
            @if(Route::has('profile'))
                <li><a href="{{ route('profile') }}">Profile</a></li>
            @endif
        </ul>
    </li>
</ul>

@push('scripts')
<script>
function showUpgradeModal(planName, featureName) {
    const normalizedPlan = String(planName || '')
        .toLowerCase()
        .includes('enterprise') ? 'enterprise' : 'pro';
    Swal.fire({
        title: '🚀 Upgrade to ' + planName,
        html: 'Unlock <strong>' + featureName + '</strong> and many more features!<br><br>' +
              '<ul style="text-align: left; display: inline-block; margin: 0 auto;">' +
              '<li>Supplier Management</li>' +
              '<li>Full Inventory Control</li>' +
              '<li>Purchase Orders</li>' +
              '<li>Recurring Invoices</li>' +
              '<li>Estimates</li>' +
              '<li>Advanced Reports</li>' +
              '</ul>',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '✨ Upgrade Now',
        cancelButtonText: 'Maybe Later',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            @if(Route::has('membership-plans'))
                window.location.href = '{{ route("membership-plans") }}?plan=' + encodeURIComponent(normalizedPlan);
            @else
                window.location.href = '/membership-plans?plan=' + encodeURIComponent(normalizedPlan);
            @endif
        }
    });
}
</script>
@endpush
