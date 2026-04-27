
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

                <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-cart"></i><span>POS</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">Sales Terminal</a></li>
                        <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li><a href="{{ route('pos.reports') }}">Items Sold</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Inventory</span></li>

                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Product List</a></li>
                        <li><a href="{{ route('add-products') }}">Add Product</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('customers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">All Customers</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('suppliers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-briefcase"></i><span>Suppliers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('suppliers.index') }}">All Suppliers</a></li>
                        @if(Route::has('suppliers.create'))
                            <li><a href="{{ route('suppliers.create') }}">Add Supplier</a></li>
                        @endif
                    </ul>
                </li>

                <li class="submenu {{ Request::is('inventory*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-archive"></i><span>Inventory</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.Products') }}">Stock Overview</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Alert</a></li>
                        @if(Route::has('inventory.transfer-audit'))
                            <li><a href="{{ route('inventory.transfer-audit') }}">Transfer Audit</a></li>
                        @endif
                        <li><a href="{{ route('inventory.stock-valuation') }}">Stock Valuation</a></li>
                        <li><a href="{{ route('inventory.lots.index') }}">Lot Tracking</a></li>
                        <li><a href="{{ route('inventory.serials.index') }}">Serial Numbers</a></li>
                        <li><a href="{{ route('inventory.barcodes.index') }}">Barcode Management</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('price-lists*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-tag"></i><span>Price Lists</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('price-lists.index') }}">All Price Lists</a></li>
                        <li><a href="{{ route('price-lists.create') }}">New Price List</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Sales</span></li>

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

                <li><a href="{{ route('recuring-invoices') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

                <li class="submenu {{ Request::is('estimates*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Estimates</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('estimates.index') }}">All Estimates</a></li>
                        <li><a href="{{ route('estimates.create') }}">Create Estimate</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Purchases</span></li>

                <li class="submenu {{ Request::is('purchases*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-bag"></i><span>Purchases</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchases.index') }}">Purchase List</a></li>
                        <li><a href="{{ route('purchases.create') }}">New Purchase</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('purchase-orders*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Purchase Orders</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchase-orders') }}">All Orders</a></li>
                        <li><a href="{{ route('add-purchases-order') }}">New Order</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('rfq*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-send"></i><span>RFQ</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('rfq.index') }}">All RFQs</a></li>
                        <li><a href="{{ route('rfq.create') }}">New RFQ</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('grn*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-truck"></i><span>Goods Received Notes</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('grn.index') }}">All GRNs</a></li>
                        <li><a href="{{ route('grn.create') }}">New GRN</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('landed-costs.index') }}"><i class="fe fe-anchor"></i><span>Landed Costs</span></a></li>

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Finance</span></li>

                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>

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
                <li class="menu-title"><span>Banking & Cash</span></li>

                <li class="submenu {{ Request::is('cheques*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-credit-card"></i><span>Cheque Register</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('cheques.index') }}">All Cheques</a></li>
                        <li><a href="{{ route('cheques.create') }}">New Cheque</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('loans*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-dollar-sign"></i><span>Loans & Overdraft</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('loans.index') }}">All Loans</a></li>
                        <li><a href="{{ route('loans.create') }}">New Loan</a></li>
                    </ul>
                </li>

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
                        <li><a href="{{ route('exchange-rates.index') }}">Exchange Rates</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('quotations*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Quotations</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('quotations') }}">All Quotations</a></li>
                        <li><a href="{{ route('add-quotations') }}">Add Quotation</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Reports</span></li>

                @include('layout.partials.sidebars.reports-menu', ['reportAccess' => 'pro'])
                <li><a href="{{ route('report-schedules.index') }}"><i class="fe fe-clock"></i><span>Scheduled Reports</span></a></li>
                <li><a href="{{ route('reports.financial-ratios') }}"><i class="fe fe-percent"></i><span>Financial Ratios</span></a></li>

                <li class="menu-title"><span>Payroll & HR</span></li>
                <li class="submenu {{ Request::is('hr/leave*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-calendar"></i><span>Leave Management</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('hr.leave.requests') }}">Leave Requests</a></li>
                        <li><a href="{{ route('hr.leave.create') }}">New Request</a></li>
                        <li><a href="{{ route('hr.leave.types') }}">Leave Types</a></li>
                    </ul>
                </li>
                <li class="submenu {{ Request::is('hr/attendance*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-clock"></i><span>Attendance</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('hr.attendance.index') }}">Attendance Log</a></li>
                        <li><a href="{{ route('hr.attendance.report') }}">Report</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Growth & Projects</span></li>
                <li><a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a></li>
                <li><a href="{{ route('projects.index') }}#profitability"><i class="fe fe-trending-up"></i><span>Project Profitability</span></a></li>
                <li><a href="{{ route('timesheets.index') }}"><i class="fe fe-clock"></i><span>Timesheets</span></a></li>
                <li><a href="{{ route('milestones.index') }}"><i class="fe fe-flag"></i><span>Milestone Billing</span></a></li>

                <li class="menu-title"><span>Budgeting & Planning</span></li>
                <li class="submenu {{ Request::is('forecasting*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-trending-up"></i><span>Forecasting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('forecasting.index') }}">All Forecasts</a></li>
                        <li><a href="{{ route('forecasting.create') }}">New Forecast</a></li>
                    </ul>
                </li>

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
                @if(Route::has('activity-log.index'))
                    <li><a href="{{ route('activity-log.index') }}"><i class="fe fe-activity"></i><span>Activity Log</span></a></li>
                @endif
                @if(Route::has('audit.index'))
                    <li><a href="{{ route('audit.index') }}"><i class="fe fe-clipboard"></i><span>Audit Trail</span></a></li>
                @endif

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
