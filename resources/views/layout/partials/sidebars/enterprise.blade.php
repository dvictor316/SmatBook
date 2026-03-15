{{-- ============================================
     ENTERPRISE PLAN SIDEBAR (FULL ACCESS)
     File: resources/views/layouts/partials/sidebar-enterprise.blade.php
     ============================================ --}}

@php
    $user = auth()->user();
    $currentSubdomain = request()->route('subdomain');
    $showPlatformReturn = $user && (
        in_array(strtolower((string) ($user->role ?? '')), ['super_admin', 'superadmin', 'administrator', 'admin'], true)
        || $user->email === 'donvictorlive@gmail.com'
    ) && session('workspace_context') === 'business';
    
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
                @if($showPlatformReturn)
                    <li class="menu-title"><span>Workspace</span></li>
                    <li>
                        <a href="{{ route('workspace.platform') }}">
                            <i class="fe fe-command"></i>
                            <span>Back to Partnership</span>
                        </a>
                    </li>
                @endif
                <li class="menu-title"><span>Main</span></li>

                {{-- Dashboard --}}
                <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">
                        <i class="fe fe-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="{{ Request::is('projects*') ? 'active' : '' }}">
                    <a href="{{ route('projects.index') }}">
                        <i class="fe fe-briefcase"></i>
                        <span>Project Management</span>
                    </a>
                </li>
                <li class="{{ Request::is('projects*') ? 'active' : '' }}">
                    <a href="{{ route('projects.index') }}#profitability">
                        <i class="fe fe-trending-up"></i>
                        <span>Project Profitability</span>
                    </a>
                </li>

                {{-- Applications --}}
                <li class="submenu {{ Request::is('chat*', 'calendar*', 'inbox*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                {{-- Customers & Vendors --}}
                <li class="submenu {{ Request::is('customers*', 'vendors*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">All Customers</a></li>
                        <li><a href="{{ route('active-customers') }}">Active</a></li>
                        <li><a href="{{ route('deactive-customers') }}">Inactive</a></li>
                        <li><a href="{{ route('vendors.index') }}">Vendors</a></li>
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

                {{-- Inventory --}}
                <li class="submenu {{ Request::is('inventory*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-archive"></i><span>Inventory</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.Products') }}">Stock Overview</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Alert</a></li>
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
                        <li><a href="{{ route('invoices-draft') }}">Draft</a></li>
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

                {{-- POS --}}
                <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-cart"></i><span>POS</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">Sales Terminal</a></li>
                        <li><a href="{{ route('pos.reports') }}">POS Reports</a></li>
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

                <li class="menu-title"><span>Finance</span></li>

                {{-- Expenses --}}
                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>

                {{-- Payments --}}
                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
                <li class="{{ request()->routeIs('payroll.*') ? 'active' : '' }}">
                    <a href="{{ route('payroll.index') }}" class="{{ request()->routeIs('payroll.*') ? 'active' : '' }}">
                        <i class="fe fe-dollar-sign"></i><span>Payroll</span>
                    </a>
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
                    </ul>
                </li>

                {{-- Taxation --}}
                <li class="submenu {{ Request::is('compliance/tax-center*', 'compliance/tax-filings*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-percent"></i><span>Taxation</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('compliance.tax-center.index') }}">Tax Center</a></li>
                        <li><a href="{{ route('compliance.tax-filings.index') }}">Tax Filings</a></li>
                        <li><a href="{{ route('reports.tax-sales') }}">Sales Tax Report</a></li>
                        <li><a href="{{ route('reports.tax-purchase') }}">Purchase Tax Report</a></li>
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

                {{-- Reports (FULL ACCESS) --}}
                <li class="submenu {{ Request::is('*-report*', 'profit-loss*', 'trial-balance*', 'general-ledger*', 'balance-sheet*', 'cash-flow*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.purchase') }}">Purchase Report</a></li>
                        <li><a href="{{ route('reports.expense') }}">Expense Report</a></li>
                        <li><a href="{{ route('reports.income') }}">Income Report</a></li>
                        <li><a href="{{ route('reports.payment') }}">Payment Report</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Report</a></li>
                        <li><a href="{{ route('reports.profit-loss') }}">Profit & Loss</a></li>
                        <li><a href="{{ route('general-ledger') }}">General Ledger</a></li>
                        <li><a href="{{ route('trial-balance') }}">Trial Balance</a></li>
                        <li><a href="{{ route('balance-sheet') }}">Balance Sheet</a></li>
                        <li><a href="{{ route('reports.cash-flow') }}">Cash Flow</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Management</span></li>

                {{-- Users --}}
                <li><a href="{{ route('users.index') }}"><i class="fe fe-user"></i><span>Users</span></a></li>
                <li><a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a></li>
                <li><a href="{{ route('projects.index') }}#profitability"><i class="fe fe-trending-up"></i><span>Project Profitability</span></a></li>

                {{-- Roles & Permission --}}
                <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles & Permission</span></a></li>

                {{-- Activity Log --}}
                <li><a href="{{ route('activity-log.index') }}"><i class="fe fe-activity"></i><span>Activity Log</span></a></li>

                {{-- Settings --}}
                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
