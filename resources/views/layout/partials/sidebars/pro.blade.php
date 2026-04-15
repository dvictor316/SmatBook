{{-- ============================================
     PROFESSIONAL PLAN SIDEBAR
     File: resources/views/layouts/partials/sidebar-professional.blade.php
     ============================================ --}}

@php
    $user = auth()->user();
    $currentSubdomain = request()->route('subdomain');
    
    if (!$currentSubdomain && $user && optional($user->company)->subdomain) {
        $currentSubdomain = $user->company->subdomain;
    }
    
    $currentSubdomain = $currentSubdomain ?? 'admin'; 
    $routeParams = ['subdomain' => $currentSubdomain];
@endphp

<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>Main</span></li>

                {{-- Dashboard --}}
                <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">
                        <i class="fe fe-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- POS --}}
                <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-cart"></i><span>POS</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">Sales Terminal</a></li>
                        <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li><a href="{{ route('pos.reports') }}">Items Sold</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Inventory</span></li>

                {{-- Products --}}
                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Product List</a></li>
                        <li><a href="{{ route('add-products') }}">Add Product</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                    </ul>
                </li>

                {{-- Customers & Suppliers --}}
                <li class="submenu {{ Request::is('customers*', 'suppliers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">All Customers</a></li>
                        <li><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                    </ul>
                </li>

                {{-- Inventory Management --}}
                <li class="submenu {{ Request::is('inventory*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-archive"></i><span>Inventory</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.Products') }}">Stock Overview</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Alert</a></li>
                        @if(Route::has('inventory.transfer-audit'))
                            <li><a href="{{ route('inventory.transfer-audit') }}">Transfer Audit</a></li>
                        @endif
                    </ul>
                </li>

                <li class="menu-title"><span>Sales</span></li>

                {{-- Invoices --}}
                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('add-invoice') }}">Create Invoice</a></li>
                        <li><a href="{{ route('invoices.index') }}">All Invoices</a></li>
                        <li><a href="{{ route('invoices-paid') }}">Paid</a></li>
                        <li><a href="{{ route('invoices-unpaid') }}">Unpaid</a></li>
                        <li><a href="{{ route('invoices-overdue') }}">Overdue</a></li>
                    </ul>
                </li>

                {{-- Recurring Invoices --}}
                <li><a href="{{ route('recuring-invoices') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

                {{-- Estimates --}}
                <li class="submenu {{ Request::is('estimates*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Estimates</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('estimates.index') }}">All Estimates</a></li>
                        <li><a href="{{ route('estimates.create') }}">Create Estimate</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Purchases</span></li>

                {{-- Purchases --}}
                <li class="submenu {{ Request::is('purchases*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-bag"></i><span>Purchases</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchases.index') }}">Purchase List</a></li>
                        <li><a href="{{ route('purchases.create') }}">New Purchase</a></li>
                    </ul>
                </li>

                {{-- Purchase Orders --}}
                <li class="submenu {{ Request::is('purchase-orders*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Purchase Orders</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchase-orders') }}">All Orders</a></li>
                        <li><a href="{{ route('add-purchases-order') }}">New Order</a></li>
                    </ul>
                </li>

                {{-- Applications --}}
                <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Finance</span></li>

                {{-- Expenses --}}
                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>

                {{-- Payments --}}
                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
                @if(Route::has('finance.recurring.index'))
                    <li><a href="{{ route('finance.recurring.index') }}"><i class="fe fe-repeat"></i><span>Recurring Transactions</span></a></li>
                @endif
                @if(Route::has('finance.approvals.index'))
                    <li><a href="{{ route('finance.approvals.index') }}"><i class="fe fe-check-square"></i><span>Approval Queue</span></a></li>
                @endif
                @if(Route::has('finance.expense-claims.index'))
                    <li><a href="{{ route('finance.expense-claims.index') }}"><i class="fe fe-wallet"></i><span>Expense Claims</span></a></li>
                @endif
                @if(Route::has('finance.collections.index'))
                    <li><a href="{{ route('finance.collections.index') }}"><i class="fe fe-layers"></i><span>Collections Hub</span></a></li>
                @endif
                @if(Route::has('finance.follow-ups.index'))
                    <li><a href="{{ route('finance.follow-ups.index') }}"><i class="fe fe-calendar"></i><span>Follow-Ups</span></a></li>
                @endif
                <li class="{{ request()->routeIs('branches.index') ? 'active' : '' }}">
                    <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.index') ? 'active' : '' }}">
                        <i class="fe fe-git-branch"></i><span>Branches</span>
                    </a>
                </li>
                <li class="submenu {{ request()->routeIs('chart-of-accounts', 'bank-reconciliation', 'manual-journal') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-book-open"></i><span>Accounting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chart-of-accounts') }}" class="{{ request()->routeIs('chart-of-accounts') ? 'active' : '' }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('bank-reconciliation') }}" class="{{ request()->routeIs('bank-reconciliation') ? 'active' : '' }}">Bank Reconciliation</a></li>
                        <li><a href="{{ route('manual-journal') }}" class="{{ request()->routeIs('manual-journal') ? 'active' : '' }}">Manual Journal</a></li>
                    </ul>
                </li>

                {{-- Quotations --}}
                <li class="submenu {{ Request::is('quotations*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Quotations</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('quotations') }}">All Quotations</a></li>
                        <li><a href="{{ route('add-quotations') }}">Add Quotation</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Reports</span></li>

                {{-- Payment Summary --}}
                <li><a href="{{ route('reports.payment-summary') }}"><i class="fe fe-dollar-sign"></i><span>Payment Summary</span></a></li>

                {{-- Reports --}}
                <li class="submenu {{ Request::is('*-report*', 'profit-loss*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.purchase') }}">Purchase Report</a></li>
                        <li><a href="{{ route('reports.expense') }}">Expense Report</a></li>
                        <li><a href="{{ route('reports.income') }}">Income Report</a></li>
                        <li><a href="{{ route('reports.payment') }}">Payment Report</a></li>
                        <li><a href="{{ route('reports.sales-return') }}">Sales Return Report</a></li>
                        <li><a href="{{ route('reports.quotation') }}">Quotation Report</a></li>
                        <li><a href="{{ route('reports.accounts-receivable') }}">Accounts Receivable</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Report</a></li>
                        <li><a href="{{ route('reports.profit-loss') }}">Profit & Loss</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Growth & Projects</span></li>
                <li><a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a></li>
                <li><a href="{{ route('projects.index') }}#profitability"><i class="fe fe-trending-up"></i><span>Project Profitability</span></a></li>

                {{-- LOCKED FEATURES - Upgrade to Enterprise --}}
                <li class="menu-title"><span>Enterprise Features</span></li>
                
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                        <i class="fe fe-lock"></i>
                        <span>Fixed Assets</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                        <i class="fe fe-lock"></i>
                        <span>Budgets</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                        <i class="fe fe-lock"></i>
                        <span>Trial Balance</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                        <i class="fe fe-lock"></i>
                        <span>Balance Sheet</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                        <i class="fe fe-lock"></i>
                        <span>Cash Flow</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                        <i class="fe fe-lock"></i>
                        <span>User Management</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li class="menu-title"><span>Settings</span></li>
                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>
                <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles & Permission</span></a></li>

            </ul>
        </div>
    </div>
</div>

<script>
function showUpgradeModal(planName, featureName) {
    Swal.fire({
        title: '🚀 Upgrade to ' + planName,
        html: 'Unlock <strong>' + featureName + '</strong> and enterprise features!<br><br>' +
              '<ul style="text-align: left; display: inline-block; margin: 0 auto;">' +
              '<li>Advanced Financial Statements</li>' +
              '<li>User Management & Permissions</li>' +
              '<li>Activity Logs & Audit Trails</li>' +
              '<li>Full ERP Suite Access</li>' +
              '</ul>',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '✨ Upgrade to Enterprise',
        cancelButtonText: 'Maybe Later',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '{{ route("membership-plans") }}?plan=enterprise';
        }
    });
}
</script>
