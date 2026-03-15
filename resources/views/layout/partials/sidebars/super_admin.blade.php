

{{-- ============================================
     SIDEBAR 1: SUPER ADMIN SIDEBAR
     ============================================ --}}
@php
    $user = auth()->user();
    $userRole = $user?->role ?? 'guest';
    $roleNormalized = strtolower($userRole);

    // FIX: Determine subdomain to prevent 'Missing parameter: subdomain' error
    $currentSubdomain = request()->route('subdomain');
    
    if (!$currentSubdomain && $user && optional($user->company)->subdomain) {
        $currentSubdomain = $user->company->subdomain;
    }
    
    // Fallback if still null
    $currentSubdomain = $currentSubdomain ?? 'admin'; 

    // Route parameters array
    $routeParams = ['subdomain' => $currentSubdomain];
@endphp

@if(in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin']))
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

                {{-- Applications --}}
                <li class="submenu {{ Request::is('chat*', 'calendar*', 'inbox*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        {{-- FIX: Added $routeParams to these routes --}}
                        <li><a href="{{ route('chat.index', $routeParams) }}" class="{{ Request::is('chat*') ? 'active' : '' }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}" class="{{ Request::is('calendar*') ? 'active' : '' }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}" class="{{ Request::is('messages*', 'inbox*') ? 'active' : '' }}">Messages</a></li>
                    </ul>
                </li>

                {{-- Master Window --}}
                <li class="submenu {{ Request::is('superadmin*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-command"></i><span>Super Admin</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('super_admin.dashboard', $routeParams) }}" class="{{ Request::is('superadmin/dashboard') ? 'active' : '' }}">Dashboard</a></li>
                        <li><a href="{{ route('super_admin.companies.index', $routeParams) }}" class="{{ Request::is('superadmin/companies*') ? 'active' : '' }}">Companies</a></li>
                        <li><a href="{{ route('super_admin.subscription', $routeParams) }}" class="{{ Request::is('superadmin/subscription*') ? 'active' : '' }}">Subscriptions</a></li>
                        <li><a href="{{ route('super_admin.packages.index', $routeParams) }}" class="{{ Request::is('superadmin/packages*') ? 'active' : '' }}">Packages</a></li>
                        <li><a href="{{ route('super_admin.domains.index', $routeParams) }}" class="{{ Request::is('superadmin/domains*') ? 'active' : '' }}">Domains</a></li>
                        <li class="{{ Request::is('superadmin/managers*') ? 'active' : '' }}">
                            <a href="{{ route('super_admin.managers.list', $routeParams) }}">Deployment Managers</a>
                        </li>
                        <li><a href="{{ route('projects.index') }}" class="{{ Request::is('projects*') ? 'active' : '' }}">Project Management</a></li>
                        <li><a href="{{ route('projects.index') }}#profitability" class="{{ Request::is('projects*') ? 'active' : '' }}">Project Profitability</a></li>
                    </ul>
                </li>

                {{-- Customers --}}
                <li class="submenu {{ Request::is('customers*', 'vendors*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li><a href="{{ route('vendors.index') }}">Vendors</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Inventory</span></li>

                {{-- Products --}}
                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Product List</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                    </ul>
                </li>

                {{-- Inventory --}}
                <li><a href="{{ route('inventory.Products') }}"><i class="fe fe-archive"></i><span>Inventory</span></a></li>

                <li class="menu-title"><span>Sales</span></li>

                {{-- Invoices --}}
                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                {{-- Recurring Invoices --}}
                <li><a href="{{ route('recuring-invoices') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

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
                <li><a href="{{ route('purchases.index') }}"><i class="fe fe-shopping-bag"></i><span>Purchases</span></a></li>
                <li><a href="{{ route('purchase-orders') }}"><i class="fe fe-file-text"></i><span>Purchase Orders</span></a></li>

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
                <li class="submenu {{ request()->routeIs('chart-of-accounts', 'bank-reconciliation', 'manual-journal') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-book-open"></i><span>Accounting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chart-of-accounts') }}" class="{{ request()->routeIs('chart-of-accounts') ? 'active' : '' }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('bank-reconciliation') }}" class="{{ request()->routeIs('bank-reconciliation') ? 'active' : '' }}">Bank Reconciliation</a></li>
                        <li><a href="{{ route('manual-journal') }}" class="{{ request()->routeIs('manual-journal') ? 'active' : '' }}">Manual Journal</a></li>
                    </ul>
                </li>

                @php
                    $planNameForTax = strtolower((string) (optional($user->company)->plan ?? 'basic'));
                    $canViewTaxation = in_array($roleNormalized, ['super_admin', 'superadmin'], true)
                        || $user?->email === 'donvictorlive@gmail.com'
                        || in_array($planNameForTax, ['enterprise'], true);
                @endphp
                @if($canViewTaxation)
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
                @endif

                {{-- Quotations --}}
                <li><a href="{{ route('quotations') }}"><i class="fe fe-file-text"></i><span>Quotations</span></a></li>

                <li class="menu-title"><span>Reports</span></li>

                {{-- Payment Summary --}}
                <li><a href="{{ route('reports.payment-summary') }}"><i class="fe fe-dollar-sign"></i><span>Payment Summary</span></a></li>

                {{-- Reports --}}
                <li class="submenu {{ Request::is('*-report*', 'profit-loss*', 'trial-balance*', 'general-ledger*', 'balance-sheet*', 'cash-flow*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.expense') }}">Expense Report</a></li>
                        <li><a href="{{ route('reports.purchase') }}">Purchase Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Report</a></li>
                        <li><a href="{{ route('reports.profit-loss') }}">Profit & Loss</a></li>
                        <li><a href="{{ route('trial-balance') }}">Trial Balance</a></li>
                        <li><a href="{{ route('general-ledger') }}">General Ledger</a></li>
                        <li><a href="{{ route('balance-sheet') }}">Balance Sheet</a></li>
                        <li><a href="{{ route('reports.cash-flow') }}">Cash Flow</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Management</span></li>

                <li><a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a></li>

                {{-- Roles & Permission --}}
                <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles & Permission</span></a></li>

                {{-- Settings --}}
                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
@endif


{{-- ============================================
     SIDEBAR 2: DEPLOYMENT MANAGER SIDEBAR
     ============================================ --}}
@if(in_array($roleNormalized, ['deployment_manager', 'manager']))
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>Main</span></li>

                {{-- Dashboard --}}
                <li class="{{ Request::is('deployment/dashboard') ? 'active' : '' }}">
                    <a href="{{ route('deployment.dashboard') }}">
                        <i class="fe fe-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- Applications --}}
                <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        {{-- FIX: Added $routeParams --}}
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Client Management</span></li>

                {{-- My Clients --}}
                <li class="submenu {{ Request::is('deployment/companies*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-briefcase"></i><span>My Clients</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('deployment.companies.index') }}">All Clients</a></li>
                        <li><a href="{{ route('deployment.companies.active') }}">Active</a></li>
                        <li><a href="{{ route('deployment.companies.pending') }}">Pending</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Subscriptions</span></li>

                {{-- Subscriptions --}}
                <li class="submenu {{ Request::is('deployment/subscription*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-credit-card"></i><span>Subscriptions</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('deployment.customers.create') }}">Register New Customer</a></li>
                        <li><a href="{{ route('deployment.subscription.overview') }}">Overview</a></li>
                        <li><a href="{{ route('deployment.subscription.renewals') }}">Renewals</a></li>
                        <li><a href="{{ route('deployment.subscription.expiring') }}">Expiring Soon</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>User Management</span></li>

                {{-- All Users --}}
                <li><a href="{{ route('deployment.users.index') }}"><i class="fe fe-users"></i><span>All Users</span></a></li>

                <li class="menu-title"><span>Financial</span></li>

                {{-- My Commissions --}}
                <li class="submenu {{ Request::is('deployment/commissions*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-dollar-sign"></i><span>My Commissions</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('deployment.commissions.index') }}">All Commissions</a></li>
                        <li><a href="{{ route('deployment.commissions.pending') }}">Pending</a></li>
                        <li><a href="{{ route('deployment.commissions.paid') }}">Paid</a></li>
                    </ul>
                </li>

                {{-- Invoices --}}
                <li><a href="{{ route('deployment.invoices.index') }}"><i class="fe fe-file"></i><span>Invoices</span></a></li>

                {{-- Payments --}}
                <li><a href="{{ route('deployment.payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>

                <li class="menu-title"><span>Reports</span></li>

                {{-- Reports --}}
                <li class="submenu {{ Request::is('deployment/reports*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('deployment.reports.performance') }}">Performance</a></li>
                        <li><a href="{{ route('deployment.reports.client-activity') }}">Client Activity</a></li>
                        <li><a href="{{ route('deployment.reports.revenue') }}">Revenue</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Support</span></li>

                {{-- Support Tickets --}}
                <li><a href="{{ route('deployment.support.tickets') }}"><i class="fe fe-help-circle"></i><span>Support Tickets</span></a></li>

                {{-- Settings --}}
                <li><a href="{{ route('deployment.settings') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
@endif


{{-- ============================================
     SIDEBAR 3: ENTERPRISE PLAN SIDEBAR
     ============================================ --}}
@php
    $plan = $user?->company?->plan ?? 'Basic';
@endphp

@if($plan === 'Enterprise' && !in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin', 'deployment_manager', 'manager']))
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

                {{-- Applications --}}
                <li class="submenu {{ Request::is('chat*', 'calendar*', 'inbox*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        {{-- FIX: Added $routeParams --}}
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                {{-- Customers --}}
                <li class="submenu {{ Request::is('customers*', 'vendors*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li><a href="{{ route('vendors.index') }}">Vendors</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Inventory</span></li>

                {{-- Products --}}
                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Product List</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                    </ul>
                </li>

                {{-- Inventory --}}
                <li><a href="{{ route('inventory.Products') }}"><i class="fe fe-archive"></i><span>Inventory</span></a></li>

                <li class="menu-title"><span>Sales</span></li>

                {{-- Invoices --}}
                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                {{-- Recurring Invoices --}}
                <li><a href="{{ route('recuring-invoices') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

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
                <li><a href="{{ route('purchases.index') }}"><i class="fe fe-shopping-bag"></i><span>Purchases</span></a></li>
                <li><a href="{{ route('purchase-orders') }}"><i class="fe fe-file-text"></i><span>Purchase Orders</span></a></li>

                <li class="menu-title"><span>Finance</span></li>

                {{-- Expenses --}}
                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>

                {{-- Payments --}}
                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
                <li class="submenu {{ request()->routeIs('chart-of-accounts', 'bank-reconciliation', 'manual-journal') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-book-open"></i><span>Accounting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chart-of-accounts') }}" class="{{ request()->routeIs('chart-of-accounts') ? 'active' : '' }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('bank-reconciliation') }}" class="{{ request()->routeIs('bank-reconciliation') ? 'active' : '' }}">Bank Reconciliation</a></li>
                        <li><a href="{{ route('manual-journal') }}" class="{{ request()->routeIs('manual-journal') ? 'active' : '' }}">Manual Journal</a></li>
                    </ul>
                </li>

                {{-- Quotations --}}
                <li><a href="{{ route('quotations') }}"><i class="fe fe-file-text"></i><span>Quotations</span></a></li>

                <li class="menu-title"><span>Reports</span></li>

                {{-- Payment Summary --}}
                <li><a href="{{ route('reports.payment-summary') }}"><i class="fe fe-dollar-sign"></i><span>Payment Summary</span></a></li>

                {{-- Reports --}}
                <li class="submenu {{ Request::is('*-report*', 'profit-loss*', 'trial-balance*', 'general-ledger*', 'balance-sheet*', 'cash-flow*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.expense') }}">Expense Report</a></li>
                        <li><a href="{{ route('reports.purchase') }}">Purchase Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Report</a></li>
                        <li><a href="{{ route('reports.profit-loss') }}">Profit & Loss</a></li>
                        <li><a href="{{ route('trial-balance') }}">Trial Balance</a></li>
                        <li><a href="{{ route('general-ledger') }}">General Ledger</a></li>
                        <li><a href="{{ route('balance-sheet') }}">Balance Sheet</a></li>
                        <li><a href="{{ route('reports.cash-flow') }}">Cash Flow</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Management</span></li>

                <li><a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a></li>
                <li><a href="{{ route('projects.index') }}#profitability"><i class="fe fe-trending-up"></i><span>Project Profitability</span></a></li>

                {{-- Roles & Permission --}}
                <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles & Permission</span></a></li>

                {{-- Settings --}}
                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
@endif


{{-- ============================================
     SIDEBAR 4: PROFESSIONAL PLAN SIDEBAR
     ============================================ --}}
@if($plan === 'Professional' && !in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin', 'deployment_manager', 'manager']))
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

                {{-- Applications --}}
                <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        {{-- FIX: Added $routeParams --}}
                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                {{-- Customers --}}
                <li class="submenu {{ Request::is('customers*', 'vendors*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li><a href="{{ route('vendors.index') }}">Vendors</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Inventory</span></li>

                {{-- Products --}}
                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Product List</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                    </ul>
                </li>

                {{-- Inventory --}}
                <li><a href="{{ route('inventory.Products') }}"><i class="fe fe-archive"></i><span>Inventory</span></a></li>

                <li class="menu-title"><span>Sales</span></li>

                {{-- Invoices --}}
                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                {{-- Recurring Invoices --}}
                <li><a href="{{ route('recuring-invoices') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

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
                <li><a href="{{ route('purchases.index') }}"><i class="fe fe-shopping-bag"></i><span>Purchases</span></a></li>
                <li><a href="{{ route('purchase-orders') }}"><i class="fe fe-file-text"></i><span>Purchase Orders</span></a></li>

                <li class="menu-title"><span>Finance</span></li>

                {{-- Expenses --}}
                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>

                {{-- Payments --}}
                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
                <li class="submenu {{ request()->routeIs('chart-of-accounts', 'bank-reconciliation', 'manual-journal') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-book-open"></i><span>Accounting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chart-of-accounts') }}" class="{{ request()->routeIs('chart-of-accounts') ? 'active' : '' }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('bank-reconciliation') }}" class="{{ request()->routeIs('bank-reconciliation') ? 'active' : '' }}">Bank Reconciliation</a></li>
                        <li><a href="{{ route('manual-journal') }}" class="{{ request()->routeIs('manual-journal') ? 'active' : '' }}">Manual Journal</a></li>
                    </ul>
                </li>

                {{-- Quotations --}}
                <li><a href="{{ route('quotations') }}"><i class="fe fe-file-text"></i><span>Quotations</span></a></li>

                <li class="menu-title"><span>Reports</span></li>

                {{-- Payment Summary --}}
                <li><a href="{{ route('reports.payment-summary') }}"><i class="fe fe-dollar-sign"></i><span>Payment Summary</span></a></li>

                {{-- Reports --}}
                <li class="submenu {{ Request::is('*-report*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.expense') }}">Expense Report</a></li>
                        <li><a href="{{ route('reports.purchase') }}">Purchase Report</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Report</a></li>
                    </ul>
                </li>

                {{-- LOCKED FEATURES - Upgrade to Enterprise --}}
                <li class="menu-title"><span>Advanced Features</span></li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Enterprise')">
                        <i class="fe fe-lock"></i>
                        <span>Profit & Loss</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Enterprise')">
                        <i class="fe fe-lock"></i>
                        <span>Trial Balance</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Enterprise')">
                        <i class="fe fe-lock"></i>
                        <span>Balance Sheet</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Enterprise')">
                        <i class="fe fe-lock"></i>
                        <span>Cash Flow</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>

                <li class="menu-title"><span>Settings</span></li>

                {{-- Settings --}}
                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>

<script>
function showUpgradeModal(planName) {
    Swal.fire({
        title: 'Upgrade to ' + planName,
        html: 'This feature is available in the <strong>' + planName + '</strong> plan.<br><br>Upgrade now to unlock advanced financial reports!',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Upgrade to ' + planName,
        cancelButtonText: 'Maybe Later',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '{{ route("plan-billing") }}';
        }
    });
}
</script>
@endif


{{-- ============================================
     SIDEBAR 5: BASIC PLAN SIDEBAR
     ============================================ --}}
@if($plan === 'Basic' && !in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin', 'deployment_manager', 'manager']))
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

                {{-- Customers --}}
                <li><a href="{{ route('customers.index') }}"><i class="fe fe-users"></i><span>Customers</span></a></li>

                <li class="menu-title"><span>Inventory</span></li>

                {{-- Products --}}
                <li><a href="{{ route('product-list') }}"><i class="fe fe-package"></i><span>Product List</span></a></li>

                <li class="menu-title"><span>Sales</span></li>

                {{-- Invoices --}}
                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                {{-- POS --}}
                <li><a href="{{ route('sales.showPos') }}"><i class="fe fe-shopping-cart"></i><span>POS</span></a></li>

                {{-- Payments --}}
                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>

                {{-- LOCKED FEATURES - Upgrade to Professional --}}
                <li class="menu-title"><span>Premium Features</span></li>
                
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Vendors</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Categories & Units</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Inventory Management</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Recurring Invoices</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Purchases</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Expenses</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Quotations</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="showUpgradeModal('Professional')">
                        <i class="fe fe-lock"></i>
                        <span>Reports</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>

                <li class="menu-title"><span>Settings</span></li>

                {{-- Settings --}}
                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>

<script>
function showUpgradeModal(planName) {
    Swal.fire({
        title: 'Upgrade to ' + planName,
        html: 'This feature is available in the <strong>' + planName + '</strong> plan.<br><br>Upgrade now to unlock more powerful features!',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Upgrade to ' + planName,
        cancelButtonText: 'Maybe Later',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '{{ route("plan-billing") }}';
        }
    });
}
</script>
@endif
