
@php
    $user = auth()->user();
    $currentSubdomain = request()->route('subdomain');
    if (!$currentSubdomain && $user && optional($user->company)->subdomain) {
        $currentSubdomain = $user->company->subdomain;
    }

    $currentSubdomain = $currentSubdomain ?? 'admin';
    $routeParams = ['subdomain' => $currentSubdomain];
@endphp

<div class="sidebar spb-enterprise-sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>Main</span></li>
                <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">
                        <i class="fe fe-home"></i><span>Dashboard</span>
                    </a>
                </li>

                @if(\App\Support\HotelAccess::userIsHotelTenant(auth()->user()))
                <li class="submenu {{ Request::is('hotel*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-briefcase"></i><span>Hotel</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('hotel.dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('hotel.frontdesk') }}">Front Desk</a></li>
                        <li><a href="{{ route('hotel.reservations.index') }}">Reservations</a></li>
                        <li><a href="{{ route('hotel.availability.index') }}">Availability</a></li>
                        <li><a href="{{ route('hotel.walkin.create') }}">Walk-In</a></li>
                        <li><a href="{{ route('hotel.in_house') }}">In-House Guests</a></li>
                        <li><a href="{{ route('hotel.guests') }}">Guests</a></li>
                        <li><a href="{{ route('hotel.folios.index') }}">Guest Folios</a></li>
                        <li><a href="{{ route('hotel.rooms.index') }}">Rooms</a></li>
                        <li><a href="{{ route('hotel.room_types.index') }}">Room Types</a></li>
                        <li><a href="{{ route('hotel.deposits') }}">Deposits</a></li>
                        <li><a href="{{ route('hotel.settings') }}">Hotel Settings</a></li>
                    </ul>
                </li>
                @endif

                <li class="submenu {{ Request::is('pos*', 'sales*', 'quotations*', 'invoices*', 'estimates*', 'customers*', 'price-lists*', 'customer-deposits*', 'credit-notes*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-dollar-sign"></i><span>Sales</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">POS Terminal</a></li>
                        <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li><a href="{{ route('quotations') }}">Quotations</a></li>
                        <li><a href="{{ route('estimates.index') }}">Sales Orders</a></li>
                        <li><a href="{{ route('invoices.index') }}">Invoices</a></li>
                        <li><a href="{{ route('add-invoice') }}">Create Invoice</a></li>
                        <li><a href="{{ route('sales.recurring-invoices.index') }}">Recurring Invoices</a></li>
                        @if(Route::has('credit-notes.index'))
                            <li><a href="{{ route('credit-notes.index') }}">Credit Notes</a></li>
                        @endif
                        @if(Route::has('customer-deposits.index'))
                            <li><a href="{{ route('customer-deposits.index') }}">Customer Deposits</a></li>
                        @endif
                        <li><a href="{{ route('price-lists.index') }}">Price Lists</a></li>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('purchases*', 'purchase-*', 'rfq*', 'grn*', 'suppliers*', 'debit-notes*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-bag"></i><span>Purchases</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchases.index') }}">Purchases</a></li>
                        @if(Route::has('purchases.create'))
                            <li><a href="{{ route('purchases.create') }}">Bills</a></li>
                        @endif
                        <li><a href="{{ route('purchase-requisitions.index') }}">Purchase Requisitions</a></li>
                        <li><a href="{{ route('rfq.index') }}">RFQ</a></li>
                        <li><a href="{{ route('purchase-orders') }}">Purchase Orders</a></li>
                        <li><a href="{{ route('grn.index') }}">Goods Received Notes</a></li>
                        <li><a href="{{ route('landed-costs.index') }}">Landed Costs</a></li>
                        @if(Route::has('purchase-returns.index'))
                            <li><a href="{{ route('purchase-returns.index') }}">Purchase Returns</a></li>
                        @endif
                        @if(Route::has('debit-notes.index'))
                            <li><a href="{{ route('debit-notes.index') }}">Debit Notes</a></li>
                        @endif
                        @if(Route::has('supplier-payments.index'))
                            <li><a href="{{ route('supplier-payments.index') }}">Supplier Payments</a></li>
                        @endif
                        <li><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*', 'inventory*', 'bom*', 'manufacturing*') ? 'active subdrop' : '' }}">
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
                        <li><a href="{{ route('bom.index') }}">Bill of Materials</a></li>
                        <li><a href="{{ route('manufacturing.index') }}">Manufacturing Orders</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('bank-*', 'cheques*', 'loans*', 'expenses*', 'payments*', 'advance-payments*', 'finance/*', 'chart-of-accounts', 'manual-journal', 'exchange-rates*', 'intercompany*', 'cost-centers*', 'compliance/tax*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-credit-card"></i><span>Money</span><span class="menu-arrow"></span></a>
                    <ul>
                        @if(Route::has('bank-accounts.index'))
                            <li><a href="{{ route('bank-accounts.index') }}">Bank Accounts</a></li>
                        @endif
                        <li><a href="{{ route('bank-reconciliation') }}">Bank Reconciliation</a></li>
                        @if(Route::has('statement-import.index'))
                            <li><a href="{{ route('statement-import.index') }}">Statement Import</a></li>
                        @endif
                        @if(Route::has('cashbook.index'))
                            <li><a href="{{ route('cashbook.index') }}">Cashbook</a></li>
                        @endif
                        @if(Route::has('petty-cash.index'))
                            <li><a href="{{ route('petty-cash.index') }}">Petty Cash</a></li>
                        @endif
                        @if(Route::has('fund-transfers.index'))
                            <li><a href="{{ route('fund-transfers.index') }}">Fund Transfers</a></li>
                        @endif
                        <li><a href="{{ route('cheques.index') }}">Cheque Register</a></li>
                        <li><a href="{{ route('loans.index') }}">Loans & Overdraft</a></li>
                        <li><a href="{{ route('expenses.index') }}">Expenses</a></li>
                        <li><a href="{{ route('payments.index') }}">Payments</a></li>
                        @include('layout.partials.sidebars.advance-payments-menu')
                        @if(Route::has('finance.expense-claims.index'))
                            <li><a href="{{ route('finance.expense-claims.index') }}">Expense Claims</a></li>
                        @endif
                        @if(Route::has('finance.collections.index'))
                            <li><a href="{{ route('finance.collections.index') }}">Collections Hub</a></li>
                        @endif
                        @if(Route::has('finance.follow-ups.index'))
                            <li><a href="{{ route('finance.follow-ups.index') }}">Follow-Ups</a></li>
                        @endif
                        @if(Route::has('finance.recurring.index'))
                            <li><a href="{{ route('finance.recurring.index') }}">Recurring Transactions</a></li>
                        @endif
                        @if(Route::has('finance.approvals.index'))
                            <li><a href="{{ route('finance.approvals.index') }}">Approval Queue</a></li>
                        @endif
                        <li><a href="{{ route('chart-of-accounts') }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('manual-journal') }}">Manual Journal</a></li>
                        @if(Route::has('general-ledger'))
                            <li><a href="{{ route('general-ledger') }}">General Ledger</a></li>
                        @endif
                        <li><a href="{{ route('exchange-rates.index') }}">Exchange Rates</a></li>
                        @if(Route::has('fx-revaluation.index'))
                            <li><a href="{{ route('fx-revaluation.index') }}">FX Revaluation</a></li>
                        @endif
                        <li><a href="{{ route('intercompany.index') }}">Intercompany</a></li>
                        <li><a href="{{ route('cost-centers.index') }}">Cost Centers</a></li>
                        @if(Route::has('finance.fixed-assets.index'))
                            <li><a href="{{ route('finance.fixed-assets.index') }}">Asset Register</a></li>
                        @endif
                        @if(Route::has('depreciation.index'))
                            <li><a href="{{ route('depreciation.index') }}">Depreciation</a></li>
                        @endif
                        @if(Route::has('asset-disposal.index'))
                            <li><a href="{{ route('asset-disposal.index') }}">Asset Disposal</a></li>
                        @endif
                        <li><a href="{{ route('assets.maintenance.index') }}">Maintenance Logs</a></li>
                        <li><a href="{{ route('compliance.tax-center.index') }}">Tax Center</a></li>
                        <li><a href="{{ route('compliance.tax-filings.index') }}">Tax Filings</a></li>
                    </ul>
                </li>

                <li class="submenu {{ request()->routeIs('employees.*', 'payroll.*', 'salary-structures.*', 'departments.*', 'hr.leave.*', 'hr.attendance.*', 'finance.budgets.*', 'forecasting.*', 'cash-flow-forecast.*', 'projects.*', 'timesheets.*', 'milestones.*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>People & Projects</span><span class="menu-arrow"></span></a>
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
                        @if(Route::has('finance.budgets.index'))
                            <li><a href="{{ route('finance.budgets.index') }}">Budgets</a></li>
                        @endif
                        <li><a href="{{ route('forecasting.index') }}">Forecasting</a></li>
                        @if(Route::has('cash-flow-forecast.index'))
                            <li><a href="{{ route('cash-flow-forecast.index') }}">Cash Flow Forecast</a></li>
                        @endif
                        <li><a href="{{ route('projects.index') }}">Projects</a></li>
                        <li><a href="{{ route('timesheets.index') }}">Timesheets</a></li>
                        <li><a href="{{ route('milestones.index') }}">Milestone Billing</a></li>
                        <li><a href="{{ route('projects.index') }}#profitability">Project Profitability</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('reports*', 'trial-balance', 'balance-sheet', 'cash-flow', 'activity-log*', 'close*', 'audit*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        @include('layout.partials.sidebars.reports-menu', ['reportAccess' => 'enterprise', 'nested' => true])
                        <li><a href="{{ route('report-schedules.index') }}">Scheduled Reports</a></li>
                        <li><a href="{{ route('reports.financial-ratios') }}">Financial Ratios</a></li>
                        <li><a href="{{ route('activity-log.index') }}">Activity Log</a></li>
                        @if(Route::has('close.index'))
                            <li><a href="{{ route('close.index') }}">Period Close</a></li>
                        @endif
                        @if(Route::has('audit.index'))
                            <li><a href="{{ route('audit.index') }}">Audit Trail</a></li>
                        @endif
                    </ul>
                </li>

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'inbox*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Workspace</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('users*', 'roles*', 'branches*', 'settings*', 'profile*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-settings"></i><span>Settings</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('users.index') }}">Users</a></li>
                        <li><a href="{{ route('roles.index') }}">Roles & Permissions</a></li>
                        <li><a href="{{ route('branches.index') }}">Branches</a></li>
                        <li><a href="{{ route('settings.index') }}">Settings</a></li>
                        @if(Route::has('profile'))
                            <li><a href="{{ route('profile') }}">Profile</a></li>
                        @endif
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
