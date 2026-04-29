
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
                {{-- ── MAIN ────────────────────────────────────────────── --}}
                <li class="menu-title"><span>Main</span></li>

                <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">
                        <i class="fe fe-home"></i><span>Dashboard</span>
                    </a>
                </li>

                {{-- ── SALES & RECEIVABLES ─────────────────────────────── --}}
                <li class="menu-title"><span>Sales &amp; Receivables</span></li>

                <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-cart"></i><span>POS</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">Sales Terminal</a></li>
                        <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li><a href="{{ route('pos.reports') }}">Items Sold</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('quotations*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Quotations</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('quotations') }}">All Quotations</a></li>
                        <li><a href="{{ route('add-quotations') }}">Add Quotation</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('estimates*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Sales Orders</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('estimates.index') }}">All Orders</a></li>
                        <li><a href="{{ route('estimates.create') }}">New Order</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('add-invoice') }}">Create Invoice</a></li>
                        <li><a href="{{ route('invoices.index') }}">All Invoices</a></li>
                        <li><a href="{{ route('invoices-paid') }}">Paid</a></li>
                        <li><a href="{{ route('invoices-unpaid') }}">Unpaid</a></li>
                        <li><a href="{{ route('invoices-overdue') }}">Overdue</a></li>
                        <li><a href="{{ route('invoices-draft') }}">Draft</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('recuring-invoices') }}"><i class="fe fe-repeat"></i><span>Recurring Invoices</span></a></li>

                @if(Route::has('credit-notes.index'))
                    <li class="{{ request()->routeIs('credit-notes.*') ? 'active' : '' }}">
                        <a href="{{ route('credit-notes.index') }}"><i class="fe fe-minus-circle"></i><span>Credit Notes</span></a>
                    </li>
                @endif

                @if(Route::has('customer-deposits.index'))
                    <li class="{{ request()->routeIs('customer-deposits.*') ? 'active' : '' }}">
                        <a href="{{ route('customer-deposits.index') }}"><i class="fe fe-download"></i><span>Customer Deposits</span></a>
                    </li>
                @endif

                <li class="{{ request()->routeIs('price-lists.*') ? 'active' : '' }}">
                    <a href="{{ route('price-lists.index') }}"><i class="fe fe-tag"></i><span>Price Lists</span></a>
                </li>

                <li class="submenu {{ Request::is('customers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">All Customers</a></li>
                        <li><a href="{{ route('active-customers') }}">Active</a></li>
                        <li><a href="{{ route('deactive-customers') }}">Inactive</a></li>
                    </ul>
                </li>

                {{-- ── PURCHASES & PAYABLES ────────────────────────────── --}}
                <li class="menu-title"><span>Purchases &amp; Payables</span></li>

                <li class="{{ request()->routeIs('purchases.index') ? 'active' : '' }}">
                    <a href="{{ route('purchases.index') }}"><i class="fe fe-shopping-bag"></i><span>Purchases</span></a>
                </li>

                @if(Route::has('purchases.create'))
                    <li class="{{ request()->routeIs('purchases.create') ? 'active' : '' }}">
                        <a href="{{ route('purchases.create') }}"><i class="fe fe-file-text"></i><span>Bills</span></a>
                    </li>
                @endif

                <li class="{{ request()->routeIs('purchase-requisitions.*') ? 'active' : '' }}">
                    <a href="{{ route('purchase-requisitions.index') }}"><i class="fe fe-send"></i><span>Purchase Requisitions</span></a>
                </li>

                <li class="{{ request()->routeIs('rfq.*') ? 'active' : '' }}">
                    <a href="{{ route('rfq.index') }}"><i class="fe fe-search"></i><span>Request for Quotation</span></a>
                </li>

                <li class="submenu {{ Request::is('purchase-orders*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Purchase Orders</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchase-orders') }}">All Orders</a></li>
                        <li><a href="{{ route('add-purchases-order') }}">New Order</a></li>
                    </ul>
                </li>

                <li class="{{ request()->routeIs('grn.*') ? 'active' : '' }}">
                    <a href="{{ route('grn.index') }}"><i class="fe fe-truck"></i><span>Goods Received Notes</span></a>
                </li>

                <li class="{{ request()->routeIs('landed-costs.*') ? 'active' : '' }}">
                    <a href="{{ route('landed-costs.index') }}"><i class="fe fe-anchor"></i><span>Landed Costs</span></a>
                </li>

                @if(Route::has('purchase-returns.index'))
                    <li><a href="{{ route('purchase-returns.index') }}"><i class="fe fe-corner-up-left"></i><span>Purchase Returns</span></a></li>
                @endif
                @if(Route::has('debit-notes.index'))
                    <li><a href="{{ route('debit-notes.index') }}"><i class="fe fe-minus-square"></i><span>Debit Notes</span></a></li>
                @endif
                @if(Route::has('supplier-payments.index'))
                    <li><a href="{{ route('supplier-payments.index') }}"><i class="fe fe-credit-card"></i><span>Supplier Payments</span></a></li>
                @endif

                <li class="submenu {{ Request::is('suppliers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-briefcase"></i><span>Suppliers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('suppliers.index') }}">All Suppliers</a></li>
                        @if(Route::has('suppliers.create'))
                            <li><a href="{{ route('suppliers.create') }}">Add Supplier</a></li>
                        @endif
                    </ul>
                </li>

                {{-- ── INVENTORY ───────────────────────────────────────── --}}
                <li class="menu-title"><span>Inventory</span></li>

                <li class="submenu {{ Request::is('product-list*', 'add-product*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Product List</a></li>
                        <li><a href="{{ route('add-products') }}">Add Product</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('inventory*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-archive"></i><span>Stock</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.Products') }}">Stock Overview</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Alert</a></li>
                        @if(Route::has('inventory.transfer-audit'))
                            <li><a href="{{ route('inventory.transfer-audit') }}">Transfer Audit</a></li>
                        @endif
                        <li><a href="{{ route('inventory.stock-valuation') }}">Stock Valuation</a></li>
                    </ul>
                </li>

                <li class="{{ request()->routeIs('inventory.lots.*') ? 'active' : '' }}">
                    <a href="{{ route('inventory.lots.index') }}"><i class="fe fe-layers"></i><span>Lot Tracking</span></a>
                </li>

                <li class="{{ request()->routeIs('inventory.serials.*') ? 'active' : '' }}">
                    <a href="{{ route('inventory.serials.index') }}"><i class="fe fe-hash"></i><span>Serial Numbers</span></a>
                </li>

                <li class="{{ request()->routeIs('inventory.barcodes.*') ? 'active' : '' }}">
                    <a href="{{ route('inventory.barcodes.index') }}"><i class="fe fe-maximize-2"></i><span>Barcode Management</span></a>
                </li>

                <li class="{{ request()->routeIs('bom.*') ? 'active' : '' }}">
                    <a href="{{ route('bom.index') }}"><i class="fe fe-list"></i><span>Bill of Materials</span></a>
                </li>

                <li class="{{ request()->routeIs('manufacturing.*') ? 'active' : '' }}">
                    <a href="{{ route('manufacturing.index') }}"><i class="fe fe-settings"></i><span>Manufacturing Orders</span></a>
                </li>

                {{-- ── BANKING & CASH ───────────────────────────────────── --}}
                <li class="menu-title"><span>Banking &amp; Cash</span></li>

                @if(Route::has('bank-accounts.index'))
                    <li><a href="{{ route('bank-accounts.index') }}"><i class="fe fe-credit-card"></i><span>Bank Accounts</span></a></li>
                @endif
                <li><a href="{{ route('bank-reconciliation') }}"><i class="fe fe-check-square"></i><span>Bank Reconciliation</span></a></li>
                @if(Route::has('statement-import.index'))
                    <li><a href="{{ route('statement-import.index') }}"><i class="fe fe-upload"></i><span>Statement Import</span></a></li>
                @endif
                @if(Route::has('cashbook.index'))
                    <li><a href="{{ route('cashbook.index') }}"><i class="fe fe-book"></i><span>Cashbook</span></a></li>
                @endif
                @if(Route::has('petty-cash.index'))
                    <li><a href="{{ route('petty-cash.index') }}"><i class="fe fe-dollar-sign"></i><span>Petty Cash</span></a></li>
                @endif
                @if(Route::has('fund-transfers.index'))
                    <li><a href="{{ route('fund-transfers.index') }}"><i class="fe fe-shuffle"></i><span>Fund Transfers</span></a></li>
                @endif

                <li class="{{ request()->routeIs('cheques.*') ? 'active' : '' }}">
                    <a href="{{ route('cheques.index') }}"><i class="fe fe-edit-3"></i><span>Cheque Register</span></a>
                </li>

                <li class="{{ request()->routeIs('loans.*') ? 'active' : '' }}">
                    <a href="{{ route('loans.index') }}"><i class="fe fe-trending-up"></i><span>Loans &amp; Overdraft</span></a>
                </li>

                {{-- ── FINANCE ─────────────────────────────────────────── --}}
                <li class="menu-title"><span>Finance</span></li>

                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>
                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
                @if(Route::has('finance.expense-claims.index'))
                    <li><a href="{{ route('finance.expense-claims.index') }}"><i class="fe fe-wallet"></i><span>Expense Claims</span></a></li>
                @endif
                @if(Route::has('finance.collections.index'))
                    <li><a href="{{ route('finance.collections.index') }}"><i class="fe fe-layers"></i><span>Collections Hub</span></a></li>
                @endif
                @if(Route::has('finance.follow-ups.index'))
                    <li><a href="{{ route('finance.follow-ups.index') }}"><i class="fe fe-calendar"></i><span>Follow-Ups</span></a></li>
                @endif
                @if(Route::has('finance.recurring.index'))
                    <li><a href="{{ route('finance.recurring.index') }}"><i class="fe fe-repeat"></i><span>Recurring Transactions</span></a></li>
                @endif
                @if(Route::has('finance.approvals.index'))
                    <li><a href="{{ route('finance.approvals.index') }}"><i class="fe fe-check-square"></i><span>Approval Queue</span></a></li>
                @endif

                {{-- ── ACCOUNTING ───────────────────────────────────────── --}}
                <li class="menu-title"><span>Accounting</span></li>

                <li class="submenu {{ request()->routeIs('chart-of-accounts', 'manual-journal') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-book-open"></i><span>Accounting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chart-of-accounts') }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('manual-journal') }}">Manual Journal</a></li>
                        @if(Route::has('recurring-journals.index'))
                            <li><a href="{{ route('recurring-journals.index') }}">Recurring Journals</a></li>
                        @endif
                        @if(Route::has('general-ledger.index'))
                            <li><a href="{{ route('general-ledger.index') }}">General Ledger</a></li>
                        @endif
                    </ul>
                </li>

                <li class="{{ request()->routeIs('exchange-rates.*') ? 'active' : '' }}">
                    <a href="{{ route('exchange-rates.index') }}"><i class="fe fe-refresh-cw"></i><span>Exchange Rates</span></a>
                </li>
                @if(Route::has('fx-revaluation.index'))
                    <li><a href="{{ route('fx-revaluation.index') }}"><i class="fe fe-trending-up"></i><span>FX Revaluation</span></a></li>
                @endif

                <li class="{{ request()->routeIs('intercompany.*') ? 'active' : '' }}">
                    <a href="{{ route('intercompany.index') }}"><i class="fe fe-git-merge"></i><span>Intercompany</span></a>
                </li>

                <li class="{{ request()->routeIs('cost-centers.*') ? 'active' : '' }}">
                    <a href="{{ route('cost-centers.index') }}"><i class="fe fe-sliders"></i><span>Cost Centers</span></a>
                </li>

                {{-- ── FIXED ASSETS ─────────────────────────────────────── --}}
                <li class="menu-title"><span>Fixed Assets</span></li>

                @if(Route::has('finance.fixed-assets.index'))
                    <li><a href="{{ route('finance.fixed-assets.index') }}"><i class="fe fe-archive"></i><span>Asset Register</span></a></li>
                @endif
                @if(Route::has('depreciation.index'))
                    <li><a href="{{ route('depreciation.index') }}"><i class="fe fe-trending-down"></i><span>Depreciation</span></a></li>
                @endif
                @if(Route::has('asset-disposal.index'))
                    <li><a href="{{ route('asset-disposal.index') }}"><i class="fe fe-trash-2"></i><span>Disposal &amp; Transfer</span></a></li>
                @endif

                <li class="{{ request()->routeIs('assets.maintenance.*') ? 'active' : '' }}">
                    <a href="{{ route('assets.maintenance.index') }}"><i class="fe fe-tool"></i><span>Maintenance Logs</span></a>
                </li>

                {{-- ── PAYROLL & HR ─────────────────────────────────────── --}}
                <li class="menu-title"><span>Payroll &amp; HR</span></li>

                <li class="submenu {{ request()->routeIs('employees.*', 'payroll.*', 'salary-structures.*', 'departments.*', 'hr.leave.*', 'hr.attendance.*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>HR Workspace</span><span class="menu-arrow"></span></a>
                    <ul>
                        @if(Route::has('employees.index'))
                            <li><a href="{{ route('employees.index') }}">Employees</a></li>
                        @endif
                        @if(Route::has('departments.index'))
                            <li><a href="{{ route('departments.index') }}">Departments</a></li>
                        @endif
                        @if(Route::has('payroll.index'))
                            <li><a href="{{ route('payroll.index') }}">Payroll</a></li>
                        @endif
                        @if(Route::has('salary-structures.index'))
                            <li><a href="{{ route('salary-structures.index') }}">Salary Structures</a></li>
                        @endif
                        <li><a href="{{ route('hr.leave.requests') }}">Leave Management</a></li>
                        <li><a href="{{ route('hr.attendance.index') }}">Attendance</a></li>
                    </ul>
                </li>

                {{-- ── BUDGETING & PLANNING ─────────────────────────────── --}}
                <li class="menu-title"><span>Budgeting &amp; Planning</span></li>

                @if(Route::has('finance.budgets.index'))
                    <li><a href="{{ route('finance.budgets.index') }}"><i class="fe fe-target"></i><span>Budgets</span></a></li>
                @endif

                <li class="{{ request()->routeIs('forecasting.*') ? 'active' : '' }}">
                    <a href="{{ route('forecasting.index') }}"><i class="fe fe-bar-chart-2"></i><span>Forecasting</span></a>
                </li>

                @if(Route::has('cash-flow-forecast.index'))
                    <li><a href="{{ route('cash-flow-forecast.index') }}"><i class="fe fe-trending-up"></i><span>Cash Flow Forecast</span></a></li>
                @endif

                {{-- ── PROJECTS ─────────────────────────────────────────── --}}
                <li class="menu-title"><span>Projects</span></li>

                <li class="{{ Request::is('projects*') ? 'active' : '' }}">
                    <a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a>
                </li>

                <li class="{{ request()->routeIs('timesheets.*') ? 'active' : '' }}">
                    <a href="{{ route('timesheets.index') }}"><i class="fe fe-clock"></i><span>Timesheets</span></a>
                </li>

                <li class="{{ request()->routeIs('milestones.*') ? 'active' : '' }}">
                    <a href="{{ route('milestones.index') }}"><i class="fe fe-flag"></i><span>Milestone Billing</span></a>
                </li>

                <li>
                    <a href="{{ route('projects.index') }}#profitability"><i class="fe fe-trending-up"></i><span>Project Profitability</span></a>
                </li>

                {{-- ── TAXATION ─────────────────────────────────────────── --}}
                <li class="menu-title"><span>Taxation</span></li>

                <li class="submenu {{ Request::is('compliance/tax*', 'reports/tax*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-percent"></i><span>Taxation</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('compliance.tax-center.index') }}">Tax Center</a></li>
                        <li><a href="{{ route('compliance.tax-filings.index') }}">Tax Filings</a></li>
                        <li><a href="{{ route('reports.tax-sales') }}">Sales Tax Report</a></li>
                        <li><a href="{{ route('reports.tax-purchase') }}">Purchase Tax Report</a></li>
                    </ul>
                </li>

                {{-- ── REPORTS ──────────────────────────────────────────── --}}
                <li class="menu-title"><span>Reports</span></li>

                @include('layout.partials.sidebars.reports-menu', ['reportAccess' => 'enterprise'])

                <li class="{{ request()->routeIs('report-schedules.*') ? 'active' : '' }}">
                    <a href="{{ route('report-schedules.index') }}"><i class="fe fe-calendar"></i><span>Scheduled Reports</span></a>
                </li>

                <li class="{{ request()->routeIs('reports.financial-ratios') ? 'active' : '' }}">
                    <a href="{{ route('reports.financial-ratios') }}"><i class="fe fe-activity"></i><span>Financial Ratios</span></a>
                </li>

                {{-- ── COMPLIANCE ───────────────────────────────────────── --}}
                <li class="menu-title"><span>Compliance</span></li>

                @if(Route::has('audit.index'))
                    <li><a href="{{ route('audit.index') }}"><i class="fe fe-clipboard"></i><span>Audit Trail</span></a></li>
                @endif
                @if(Route::has('close.index'))
                    <li><a href="{{ route('close.index') }}"><i class="fe fe-lock"></i><span>Period Close</span></a></li>
                @endif
                <li><a href="{{ route('activity-log.index') }}"><i class="fe fe-activity"></i><span>Activity Log</span></a></li>

                {{-- ── APPLICATIONS ─────────────────────────────────────── --}}
                <li class="menu-title"><span>Applications</span></li>

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'inbox*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                {{-- ── MANAGEMENT ───────────────────────────────────────── --}}
                <li class="menu-title"><span>Management</span></li>

                <li><a href="{{ route('users.index') }}"><i class="fe fe-user"></i><span>Users</span></a></li>
                <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles &amp; Permissions</span></a></li>
                <li><a href="{{ route('branches.index') }}"><i class="fe fe-git-branch"></i><span>Branches</span></a></li>
                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
