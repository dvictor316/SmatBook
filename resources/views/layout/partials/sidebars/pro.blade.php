
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

                <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">
                        <i class="fe fe-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="submenu {{ Request::is('pos*', 'sales*', 'quotations*', 'invoices*', 'estimates*', 'customers*', 'price-lists*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-dollar-sign"></i><span>Sales</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">POS Terminal</a></li>
                        <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li><a href="{{ route('quotations') }}">Quotations</a></li>
                        <li><a href="{{ route('invoices.index') }}">Invoices</a></li>
                        <li><a href="{{ route('add-invoice') }}">Create Invoice</a></li>
                        <li><a href="{{ route('sales.recurring-invoices.index') }}">Recurring Invoices</a></li>
                        <li><a href="{{ route('estimates.index') }}">Estimates</a></li>
                        <li><a href="{{ route('price-lists.index') }}">Price Lists</a></li>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('purchases*', 'purchase-orders*', 'rfq*', 'grn*', 'suppliers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-bag"></i><span>Purchases</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchases.index') }}">Purchases</a></li>
                        @if(Route::has('purchases.create'))
                            <li><a href="{{ route('purchases.create') }}">Bills</a></li>
                        @endif
                        <li><a href="{{ route('purchase-orders') }}">Purchase Orders</a></li>
                        <li><a href="{{ route('rfq.index') }}">RFQ</a></li>
                        <li><a href="{{ route('grn.index') }}">Goods Received Notes</a></li>
                        <li><a href="{{ route('landed-costs.index') }}">Landed Costs</a></li>
                        <li><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*', 'inventory*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Inventory</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Products</a></li>
                        <li><a href="{{ route('add-products') }}">Add Product</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                        <li><a href="{{ route('inventory.Products') }}">Stock Overview</a></li>
                        <li><a href="{{ route('inventory.stock-valuation') }}">Stock Valuation</a></li>
                        @if(Route::has('inventory.transfer-audit'))
                            <li><a href="{{ route('inventory.transfer-audit') }}">Transfer Audit</a></li>
                        @endif
                        <li><a href="{{ route('inventory.lots.index') }}">Lot Tracking</a></li>
                        <li><a href="{{ route('inventory.serials.index') }}">Serial Numbers</a></li>
                        <li><a href="{{ route('inventory.barcodes.index') }}">Barcode Management</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('expenses*', 'payments*', 'finance/*', 'cheques*', 'loans*', 'chart-of-accounts', 'bank-reconciliation', 'manual-journal') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-credit-card"></i><span>Money</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('expenses.index') }}">Expenses</a></li>
                        <li><a href="{{ route('payments.index') }}">Payments</a></li>
                        @if(Route::has('finance.recurring.index'))
                            <li><a href="{{ route('finance.recurring.index') }}">Recurring Transactions</a></li>
                        @endif
                        @if(Route::has('finance.approvals.index'))
                            <li><a href="{{ route('finance.approvals.index') }}">Approval Queue</a></li>
                        @endif
                        @if(Route::has('finance.expense-claims.index'))
                            <li><a href="{{ route('finance.expense-claims.index') }}">Expense Claims</a></li>
                        @endif
                        @if(Route::has('finance.collections.index'))
                            <li><a href="{{ route('finance.collections.index') }}">Collections Hub</a></li>
                        @endif
                        @if(Route::has('finance.follow-ups.index'))
                            <li><a href="{{ route('finance.follow-ups.index') }}">Follow-Ups</a></li>
                        @endif
                        <li><a href="{{ route('cheques.index') }}">Cheque Register</a></li>
                        <li><a href="{{ route('loans.index') }}">Loans & Overdraft</a></li>
                        <li><a href="{{ route('chart-of-accounts') }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('bank-reconciliation') }}">Bank Reconciliation</a></li>
                        <li><a href="{{ route('manual-journal') }}">Manual Journal</a></li>
                        <li><a href="{{ route('exchange-rates.index') }}">Exchange Rates</a></li>
                    </ul>
                </li>

                <li class="submenu {{ request()->routeIs('payroll.*', 'departments.*', 'hr.leave.*', 'hr.attendance.*', 'projects.*', 'timesheets.*', 'milestones.*', 'forecasting.*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>People & Projects</span><span class="menu-arrow"></span></a>
                    <ul>
                        @if(Route::has('departments.index'))
                            <li><a href="{{ route('departments.index') }}">Departments</a></li>
                        @endif
                        @if(Route::has('payroll.index'))
                            <li><a href="{{ route('payroll.index') }}">Payroll</a></li>
                        @endif
                        <li><a href="{{ route('hr.leave.requests') }}">Leave Requests</a></li>
                        <li><a href="{{ route('hr.attendance.index') }}">Attendance</a></li>
                        <li><a href="{{ route('projects.index') }}">Projects</a></li>
                        <li><a href="{{ route('timesheets.index') }}">Timesheets</a></li>
                        <li><a href="{{ route('milestones.index') }}">Milestones</a></li>
                        <li><a href="{{ route('forecasting.index') }}">Forecasting</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('reports*', 'trial-balance', 'balance-sheet', 'cash-flow') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        @include('layout.partials.sidebars.reports-menu', ['reportAccess' => 'pro', 'nested' => true])
                        <li><a href="{{ route('report-schedules.index') }}">Scheduled Reports</a></li>
                        <li><a href="{{ route('reports.financial-ratios') }}">Financial Ratios</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Workspace</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="submenu">
                    <a href="#"><i class="fe fe-lock"></i><span>Enterprise</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">Fixed Assets <span class="badge bg-warning">Enterprise</span></a></li>
                        <li><a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">Budgets <span class="badge bg-warning">Enterprise</span></a></li>
                        <li><a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">Trial Balance <span class="badge bg-warning">Enterprise</span></a></li>
                        <li><a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">Balance Sheet <span class="badge bg-warning">Enterprise</span></a></li>
                    </ul>
                </li>

                <li class="submenu {{ request()->routeIs('branches.index') || Request::is('settings*', 'roles*', 'activity-log*', 'audit*', 'profile*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-settings"></i><span>Settings</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('branches.index') }}">Branches</a></li>
                        <li><a href="{{ route('settings.index') }}">Settings</a></li>
                        <li><a href="{{ route('roles.index') }}">Roles & Permission</a></li>
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
