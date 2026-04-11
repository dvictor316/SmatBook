{{-- ============================================
     BASIC PLAN SIDEBAR (CLEANED)
     File: resources/views/layout/partials/sidebars/basic.blade.php
     ============================================ --}}

<ul>
    <li class="menu-title"><span>Main</span></li>

    {{-- Dashboard --}}
    <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
        <a href="{{ route('home') }}">
            <i class="fe fe-home"></i>
            <span>Dashboard</span>
        </a>
    </li>

    {{-- POS Terminal --}}
    @if(Route::has('sales.showPos'))
    <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-shopping-cart"></i><span>POS Terminal</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('sales.showPos') }}">Sales Terminal</a></li>
            @if(Route::has('pos.sales'))
                <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
            @endif
            @if(Route::has('pos.reports'))
                <li><a href="{{ route('pos.reports') }}">Items Sold</a></li>
            @endif
        </ul>
    </li>
    @endif

    <li class="menu-title"><span>Inventory</span></li>

    {{-- Products --}}
    @if(Route::has('product-list'))
    <li class="submenu {{ Request::is('product-list*', 'add-products*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('product-list') }}">Product List</a></li>
            @if(Route::has('add-products'))
                <li><a href="{{ route('add-products') }}">Add Product</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- Customers --}}
    @if(Route::has('customers.index'))
    <li class="submenu {{ Request::is('customers*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('customers.index') }}">All Customers</a></li>
            @if(Route::has('customers.add'))
                <li><a href="{{ route('customers.add') }}">Add Customer</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- Suppliers --}}
    @if(Route::has('suppliers.index'))
    <li class="submenu {{ Request::is('suppliers*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-briefcase"></i><span>Suppliers</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('suppliers.index') }}">All Suppliers</a></li>
            @if(Route::has('suppliers.create'))
                <li><a href="{{ route('suppliers.create') }}">Add Supplier</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- Purchases (Stock In) --}}
    @if(Route::has('purchases.index'))
    <li class="submenu {{ Request::is('purchases*', 'purchase-*', 'add-purchases*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-shopping-bag"></i><span>Purchases</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('purchases.index') }}">All Purchases</a></li>
            @if(Route::has('purchases.create'))
                <li><a href="{{ route('purchases.create') }}">Add Purchase</a></li>
            @endif
            @if(Route::has('purchase-transaction'))
                <li><a href="{{ route('purchase-transaction') }}">Purchase Ledger</a></li>
            @endif
        </ul>
    </li>
    @endif

    <li class="menu-title"><span>Sales</span></li>

    {{-- Invoices --}}
    @if(Route::has('invoices.index'))
    <li class="submenu {{ Request::is('invoices*', 'add-invoice*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
        <ul>
            @if(Route::has('add-invoice'))
                <li><a href="{{ route('add-invoice') }}">Create Invoice</a></li>
            @endif
            <li><a href="{{ route('invoices.index') }}">View Invoices</a></li>
            @if(Route::has('invoices-paid'))
                <li><a href="{{ route('invoices-paid') }}">Paid Invoices</a></li>
            @endif
            @if(Route::has('invoices-unpaid'))
                <li><a href="{{ route('invoices-unpaid') }}">Unpaid Invoices</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- Applications --}}
    @if(Route::has('chat.index'))
    <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
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
    @endif

    {{-- Quotations --}}
    @if(Route::has('quotations'))
    <li class="submenu {{ Request::is('quotations*') ? 'active subdrop' : '' }}">
        <a href="#"><i class="fe fe-file-text"></i><span>Quotations</span><span class="menu-arrow"></span></a>
        <ul>
            <li><a href="{{ route('quotations') }}">All Quotations</a></li>
            @if(Route::has('add-quotations'))
                <li><a href="{{ route('add-quotations') }}">Add Quotation</a></li>
            @endif
        </ul>
    </li>
    @endif

    <li class="menu-title"><span>Finance</span></li>

    {{-- Payments --}}
    @if(Route::has('payments.index'))
        <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
    @endif

    {{-- Expenses --}}
    @if(Route::has('expenses.index'))
        <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>
    @endif

    <li class="menu-title"><span>Reports</span></li>

    {{-- Sales Report --}}
    @if(Route::has('reports.sales'))
        <li><a href="{{ route('reports.sales') }}"><i class="fe fe-trending-up"></i><span>Sales Report</span></a></li>
    @endif

    {{-- Payment Summary --}}
    @if(Route::has('reports.payment-summary'))
        <li><a href="{{ route('reports.payment-summary') }}"><i class="fe fe-dollar-sign"></i><span>Payment Summary</span></a></li>
    @endif

    @if(Route::has('reports.accounts-receivable'))
        <li><a href="{{ route('reports.accounts-receivable') }}"><i class="fe fe-briefcase"></i><span>Accounts Receivable</span></a></li>
    @endif

    {{-- LOCKED FEATURES (Upgrade to Pro/Enterprise) --}}
    <li class="menu-title"><span>Upgrade for More</span></li>
    
    <li>
        <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'pro']) : url('/membership-plans?plan=pro') }}">
            <i class="fe fe-lock"></i>
            <span>Purchase Orders</span>
            <span class="badge bg-info">Pro</span>
        </a>
    </li>
    <li>
        <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'pro']) : url('/membership-plans?plan=pro') }}">
            <i class="fe fe-lock"></i>
            <span>Debit Notes & Returns</span>
            <span class="badge bg-info">Pro</span>
        </a>
    </li>
    <li>
        <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'pro']) : url('/membership-plans?plan=pro') }}">
            <i class="fe fe-lock"></i>
            <span>Advanced Supplier Analytics</span>
            <span class="badge bg-info">Pro</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0);" onclick="showUpgradeModal('Pro', 'Advanced procurement & supplier analytics')">
            <i class="fe fe-info"></i>
            <span>Why Upgrade?</span>
            <span class="badge bg-light text-dark">Info</span>
        </a>
    </li>
    <li>
        <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
            <i class="fe fe-lock"></i>
            <span>Cash Flow Reports</span>
            <span class="badge bg-warning">Enterprise</span>
        </a>
    </li>
    <li class="menu-title"><span>Settings</span></li>
    
    @if(Route::has('settings.index'))
        <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>
    @endif
    @if(Route::has('roles.index'))
        <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles & Permission</span></a></li>
    @endif

    @if(Route::has('profile'))
        <li><a href="{{ route('profile') }}"><i class="fe fe-user"></i><span>Profile</span></a></li>
    @endif

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
