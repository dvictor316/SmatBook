@if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
    <div class="sidebar" id="sidebar">
        <div class="sidebar-inner slimscroll">
            <div id="sidebar-menu" class="sidebar-menu">

                {{-- Horizontal/Greedy Menu --}}
                <nav class="greedys sidebar-horizantal">
                    <ul class="list-inline-item list-unstyled links">
                        
                        {{-- Main Section --}}
                        <li class="menu-title"><span>Main</span></li>
                        
                        <li class="submenu">
                            <a href="#"><i class="fe fe-home"></i> <span>Dashboard</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('/') }}" class="{{ Request::is('index', '/') ? 'active' : '' }}">Admin Dashboard</a></li>
                            </ul>
                        </li>
                        
                        <li class="submenu">
                            <a href="#"><i class="fe fe-grid"></i> <span>Applications</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ route('chat.index') }}" class="{{ Request::is('chat') ? 'active' : '' }}">Chat</a></li>
                                <li><a href="{{ url('calendar') }}" class="{{ Request::is('calendar') ? 'active' : '' }}">Calendar</a></li>
                                <li><a href="{{ url('inbox') }}" class="{{ Request::is('inbox') ? 'active' : '' }}">Email</a></li>
                            </ul>
                        </li>
                        
                        <li class="submenu">
                            <a href="#"><i class="fe fe-user"></i> <span>Super Admin</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('dashboard') }}" class="{{ Request::is('dashboard') ? 'active' : '' }}">Dashboard</a></li>
                                <li><a href="{{ url('companies') }}" class="{{ Request::is('companies') ? 'active' : '' }}">Companies</a></li>
                                <li><a href="{{ url('subscription') }}" class="{{ Request::is('subscription') ? 'active' : '' }}">Subscription</a></li>
                                <li><a href="{{ url('packages') }}" class="{{ Request::is('packages', 'plans-list') ? 'active' : '' }}">Packages</a></li>
                                <li><a href="{{ url('domain') }}" class="{{ Request::is('domain') ? 'active' : '' }}">Domain</a></li>
                                <li><a href="{{ url('purchase-transaction') }}" class="{{ Request::is('purchase-transaction') ? 'active' : '' }}">Purchase Transaction</a></li>
                            </ul>
                        </li>

                        {{-- Customers Section --}}
                        <li class="submenu">
                            <a href="#"><i class="fe fe-users"></i><span>Customers</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('customers') }}" class="{{ Request::is('customers', 'add-customer', 'edit-customer', 'active-customers', 'deactive-customers') ? 'active' : '' }}">Customers</a></li>
                                <li><a href="{{ url('customer-details') }}" class="{{ Request::is('customer-details') ? 'active' : '' }}">Customer Details</a></li>
                                <li><a href="{{ url('suppliers') }}" class="{{ Request::is('suppliers*') ? 'active' : '' }}">Suppliers</a></li>
                            </ul>
                        </li>

                        {{-- Inventory Section --}}
                        <li class="menu-title"><span>Inventory</span></li>
                        
                        <li class="submenu">
                            <a href="#"><i class="fe fe-package"></i> <span>Products / Services</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('product-list') }}" class="{{ Request::is('product-list', 'add-products', 'edit-products') ? 'active' : '' }}">Product List</a></li>
                                <li><a href="{{ url('category') }}" class="{{ Request::is('category') ? 'active' : '' }}">Product Category</a></li>
                                <li><a href="{{ url('units') }}" class="{{ Request::is('units') ? 'active' : '' }}">Product Units</a></li>
                            </ul>
                        </li>
                        
                        <li>
                            <a href="{{ url('inventory') }}" class="{{ Request::is('inventory', 'inventory-history') ? 'active' : '' }}"><i class="fe fe-archive"></i> <span>Inventory</span></a>
                        </li>

                        <li class="submenu">
                            <a href="#"><i class="fe fe-file-plus"></i><span>Signature</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('signature-list') }}" class="{{ Request::is('signature-list') ? 'active' : '' }}">List of Signature</a></li>
                                <li><a href="{{ url('signature-invoice') }}" class="{{ Request::is('signature-invoice') ? 'active' : '' }}">Signature Invoice</a></li>
                            </ul>
                        </li>

                        {{-- Sales Section --}}
                        <li class="menu-title"><span>Sales</span></li>
                        
                        <li class="submenu">
                            <a href="#"><i class="fe fe-file"></i> <span>Invoices</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('invoices') }}" class="{{ Request::is('invoices', 'invoices-paid', 'invoices-overdue', 'invoices-cancelled', 'invoices-recurring', 'invoices-unpaid', 'invoices-refunded', 'invoices-draft') ? 'active' : '' }}">Invoices List</a></li>
                                <li><a href="{{ route('add-invoice') }}" class="{{ Request::is('add-invoice') ? 'active' : '' }}">Create Invoice</a></li>
                                <li><a href="{{ url('invoice-details-admin') }}" class="{{ Request::is('invoice-details-admin') ? 'active' : '' }}">Invoice Details (Admin)</a></li>
                                <li><a href="{{ url('invoice-details') }}" class="{{ Request::is('invoice-details') ? 'active' : '' }}">Invoice Details (Customer)</a></li>
                                <li><a href="{{ url('invoice-template') }}" class="{{ Request::is('invoice-template') ? 'active' : '' }}">Invoice Templates</a></li>
                            </ul>
                        </li>
                        
                        <li>
                            <a href="{{ url('pos') }}" class="{{ Request::is('pos*') ? 'active' : '' }}"><i class="fe fe-shopping-cart"></i> <span>POS</span></a>
                        </li>
                    </ul>
                    
                    <button class="viewmoremenu">More Menu</button>
                    
                    <ul class="hidden-links hidden">
                        <li>
                            <a href="{{ url('recurring-invoices') }}" class="{{ Request::is('recurring-invoices') ? 'active' : '' }}"><i class="fe fe-clipboard"></i> <span>Recurring Invoices</span></a>
                        </li>
                        <li>
                            <a href="{{ url('credit-notes') }}" class="{{ Request::is('credit-notes', 'add-credit-notes', 'edit-credit-notes') ? 'active' : '' }}"><i class="fe fe-edit"></i> <span>Credit Notes</span></a>
                        </li>
                        
                        {{-- Purchases Section --}}
                        <li class="menu-title"><span>Purchases</span></li>
                        <li>
                            <a href="{{ url('purchases') }}" class="{{ Request::is('purchases', 'add-purchases', 'edit-purchases', 'add-purchase-return', 'edit-purchase-return') ? 'active' : '' }}"><i class="fe fe-shopping-cart"></i> <span>Purchases</span></a>
                        </li>
                        <li>
                            <a href="{{ url('purchase-orders') }}" class="{{ Request::is('purchase-orders', 'add-purchases-order', 'edit-purchases-order') ? 'active' : '' }}"><i class="fe fe-shopping-bag"></i> <span>Purchase Orders</span></a>
                        </li>
                        <li>
                            <a href="{{ url('debit-notes') }}" class="{{ Request::is('debit-notes') ? 'active' : '' }}"><i class="fe fe-file-text"></i> <span>Debit Notes</span></a>
                        </li>

                        {{-- Finance & Accounts Section --}}
                        <li class="menu-title"><span>Finance & Accounts</span></li>
                        <li>
                            <a href="{{ url('expenses') }}" class="{{ Request::is('expenses') ? 'active' : '' }}"><i class="fe fe-file-plus"></i> <span>Expenses</span></a>
                        </li>
                        <li>
                            <a href="{{ url('payments') }}" class="{{ Request::is('payments') ? 'active' : '' }}"><i class="fe fe-credit-card"></i> <span>Payments</span></a>
                        </li>
                        @if(Route::has('finance.recurring.index'))
                        <li>
                            <a href="{{ route('finance.recurring.index') }}" class="{{ Request::is('finance/recurring-transactions*') ? 'active' : '' }}"><i class="fe fe-repeat"></i> <span>Recurring Transactions</span></a>
                        </li>
                        @endif
                        @if(Route::has('finance.approvals.index'))
                        <li>
                            <a href="{{ route('finance.approvals.index') }}" class="{{ Request::is('finance/approvals*') ? 'active' : '' }}"><i class="fe fe-check-square"></i> <span>Approval Queue</span></a>
                        </li>
                        @endif

                        {{-- Quotations Section --}}
                        <li class="menu-title"><span>Quotations</span></li>
                        <li>
                            <a href="{{ url('quotations') }}" class="{{ Request::is('quotations', 'add-quotations', 'edit-quotations') ? 'active' : '' }}"><i class="fe fe-clipboard"></i> <span>Quotations</span></a>
                        </li>
                        <li>
                            <a href="{{ url('delivery-challans') }}" class="{{ Request::is('delivery-challans', 'add-delivery-challans', 'edit-delivery-challans') ? 'active' : '' }}"><i class="fe fe-file-text"></i> <span>Delivery Challans</span></a>
                        </li>

                        {{-- Reports Section --}}
                        <li class="menu-title"><span>Reports</span></li>
                        <li>
                            <a href="{{ route('reports.payment-summary') }}" class="{{ Request::is('reports/payment-summary') ? 'active' : '' }}"><i class="fe fe-credit-card"></i> <span>Payment Summary</span></a>
                        </li>
                        
                        <li class="submenu">
                            <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ route('reports.expense') }}" class="{{ Request::is('reports/expense-report') ? 'active' : '' }}">Expense Report</a></li>
                                <li><a href="{{ route('reports.purchase') }}" class="{{ Request::is('reports/purchase-report') ? 'active' : '' }}">Purchase Report</a></li>
                                <li><a href="{{ route('reports.sales') }}" class="{{ Request::is('reports/sales-report') ? 'active' : '' }}">Sales Report</a></li>
                                <li><a href="{{ route('reports.sales-return') }}" class="{{ Request::is('reports/sales-return-report') ? 'active' : '' }}">Sales Return Report</a></li>
                                <li><a href="{{ route('reports.quotation') }}" class="{{ Request::is('reports/quotation-report') ? 'active' : '' }}">Quotation Report</a></li>
                                <li><a href="{{ route('reports.payment') }}" class="{{ Request::is('reports/payment-report') ? 'active' : '' }}">Payment Report</a></li>
                                <li><a href="{{ route('reports.stock') }}" class="{{ Request::is('reports/stock-report') ? 'active' : '' }}">Stock Report</a></li>
                                <li><a href="{{ route('reports.low-stock') }}" class="{{ Request::is('reports/low-stock-report') ? 'active' : '' }}">Low Stock Report</a></li>
                                <li><a href="{{ route('reports.income') }}" class="{{ Request::is('reports/income-report') ? 'active' : '' }}">Income Report</a></li>
                                <li><a href="{{ route('reports.tax-purchase') }}" class="{{ Request::is('reports/tax-purchase', 'reports/tax-sales') ? 'active' : '' }}">Tax Report</a></li>
                                <li><a href="{{ route('reports.profit-loss') }}" class="{{ Request::is('reports/profit-loss-list') ? 'active' : '' }}">Profit & Loss</a></li>
                                <li><a href="{{ route('trial-balance') }}" class="{{ Request::is('trial-balance') ? 'active' : '' }}">Trial Balance</a></li>
                                <li><a href="{{ route('balance-sheet') }}" class="{{ Request::is('balance-sheet') ? 'active' : '' }}">Balance Sheet</a></li>
                                <li><a href="{{ route('reports.cash-flow') }}" class="{{ Request::is('cash-flow') ? 'active' : '' }}">Cash Flow Statement</a></li>
                            </ul>
                        </li>

                        {{-- User Management Section --}}
                        <li class="menu-title"><span>User Management</span></li>
                        <li>
                            <a href="{{ url('users') }}" class="{{ Request::is('users') ? 'active' : '' }}"><i class="fe fe-user"></i> <span>Users</span></a>
                        </li>
                        <li>
                            <a href="{{ url('roles-permission') }}" class="{{ Request::is('roles-permission', 'permission') ? 'active' : '' }}"><i class="fe fe-clipboard"></i> <span>Roles & Permission</span></a>
                        </li>
                        <li>
                            <a href="{{ url('delete-account-request') }}" class="{{ Request::is('delete-account-request') ? 'active' : '' }}"><i class="fe fe-trash-2"></i> <span>Delete Account Request</span></a>
                        </li>

                        {{-- Membership Section --}}
                        <li class="menu-title"><span>Membership</span></li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-book"></i> <span>Membership</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('membership-plans') }}" class="{{ Request::is('membership-plans') ? 'active' : '' }}">Membership Plans</a></li>
                                <li><a href="{{ url('membership-addons') }}" class="{{ Request::is('membership-addons') ? 'active' : '' }}">Membership Addons</a></li>
                                <li><a href="{{ url('subscribers') }}" class="{{ Request::is('subscribers') ? 'active' : '' }}">Subscribers</a></li>
                                <li><a href="{{ url('transactions') }}" class="{{ Request::is('transactions') ? 'active' : '' }}">Transactions</a></li>
                            </ul>
                        </li>

                        {{-- Forms Section --}}
                        <li class="submenu">
                            <a href="#"><i class="fe fe-sidebar"></i> <span>Forms</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('form-basic-inputs') }}" class="{{ Request::is('form-basic-inputs') ? 'active' : '' }}">Basic Inputs</a></li>
                            </ul>
                        </li>
                        
                        {{-- Tables Section --}}
                        <li class="submenu">
                            <a href="#"><i class="fe fe-layout"></i> <span>Tables</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('tables-basic') }}" class="{{ Request::is('tables-basic') ? 'active' : '' }}">Basic Tables</a></li>
                                <li><a href="{{ url('data-tables') }}" class="{{ Request::is('data-tables') ? 'active' : '' }}">Data Table</a></li>
                            </ul>
                        </li>

                        {{-- Settings Section --}}
                        <li class="menu-title"><span>Settings</span></li>
                        <li>
                            <a href="{{ url('settings') }}" class="{{ Request::is('settings', 'company-settings', 'invoice-settings', 'template-invoice', 'payment-settings', 'bank-account', 'tax-rates', 'plan-billing', 'two-factor', 'custom-filed', 'email-settings', 'preferences', 'saas-settings', 'seo-settings', 'email-template') ? 'active' : '' }}"><i class="fe fe-settings"></i> <span>Settings</span></a>
                        </li>
                    </ul>
                </nav>

                {{-- Vertical Menu --}}
                <ul class="sidebar-vertical">
                    
                    {{-- Main Section --}}
                    <li class="menu-title"><span>Main</span></li>
                    
                    <li class="submenu">
                        <a href="#"><i class="fe fe-home"></i> <span>Dashboard</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="{{ url('/') }}" class="{{ Request::is('index', '/') ? 'active' : '' }}">Admin Dashboard</a></li>
                        </ul>
                    </li>
                    
                    <li class="submenu">
                        <a href="#"><i class="fe fe-grid"></i> <span>Applications</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="{{ url('chat') }}" class="{{ Request::is('chat') ? 'active' : '' }}">Chat</a></li>
                            <li><a href="{{ url('calendar') }}" class="{{ Request::is('calendar') ? 'active' : '' }}">Calendar</a></li>
                            <li><a href="{{ url('inbox') }}" class="{{ Request::is('inbox') ? 'active' : '' }}">Email</a></li>
                        </ul>
                    </li>
                    
                    <li class="submenu">
                        <a href="#"><i class="fe fe-user"></i> <span>Super Admin</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="{{ url('dashboard') }}" class="{{ Request::is('dashboard') ? 'active' : '' }}">Dashboard</a></li>
                            <li><a href="{{ url('companies') }}" class="{{ Request::is('companies') ? 'active' : '' }}">Companies</a></li>
                            <li><a href="{{ url('subscription') }}" class="{{ Request::is('subscription') ? 'active' : '' }}">Subscription</a></li>
                            <li><a href="{{ url('packages') }}" class="{{ Request::is('packages', 'plans-list') ? 'active' : '' }}">Packages</a></li>
                            <li><a href="{{ url('domain') }}" class="{{ Request::is('domain') ? 'active' : '' }}">Domain</a></li>
                            <li><a href="{{ url('purchase-transaction') }}" class="{{ Request::is('purchase-transaction') ? 'active' : '' }}">Purchase Transaction</a></li>
                        </ul>
                    </li>

                    {{-- POS Section --}}
                    <li class="menu-title"><span>POS</span></li>
                    <li class="submenu">
                        <a href="#"><i class="fe fe-shopping-cart"></i> <span>POS Terminal</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="{{ url('pos') }}" class="{{ Request::is('pos') ? 'active' : '' }}">Sales Point</a></li>
                            <li><a href="{{ url('pos/sales') }}" class="{{ Request::is('pos/sales') ? 'active' : '' }}">POS Sales</a></li>
                            <li><a href="{{ url('pos/reports') }}" class="{{ Request::is('pos/reports') ? 'active' : '' }}">Items Sold</a></li>
                        </ul>
                    </li>
                    
                    {{-- Customers Section --}}
                    <li class="menu-title"><span>Customers</span></li>
                    <li>
                        <a href="{{ url('customers') }}" class="{{ Request::is('customers', 'add-customer', 'edit-customer', 'active-customers', 'deactive-customers') ? 'active' : '' }}"><i class="fe fe-users"></i> <span>Customers</span></a>
                    </li>
                    <li>
                        <a href="{{ url('customer-details') }}" class="{{ Request::is('customer-details') ? 'active' : '' }}"><i class="fe fe-file"></i> <span>Customer Details</span></a>
                    </li>
                    <li>
                        <a href="{{ url('suppliers') }}" class="{{ Request::is('suppliers*') ? 'active' : '' }}"><i class="fe fe-user"></i> <span>Suppliers</span></a>
                    </li>
                    
                    {{-- Inventory Section --}}
                    <li class="menu-title"><span>Inventory</span></li>
                    <li class="submenu">
                        <a href="#"><i class="fe fe-package"></i> <span>Products / Services</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="{{ url('product-list') }}" class="{{ Request::is('product-list', 'add-products', 'edit-products') ? 'active' : '' }}">Product List</a></li>
                            <li><a href="{{ url('category') }}" class="{{ Request::is('category') ? 'active' : '' }}">Category</a></li>
                            <li><a href="{{ url('units') }}" class="{{ Request::is('units') ? 'active' : '' }}">Units</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="{{ url('inventory') }}" class="{{ Request::is('inventory', 'inventory-history') ? 'active' : '' }}"><i class="fe fe-archive"></i> <span>Inventory</span></a>
                    </li>
                    
                    {{-- Signature Section --}}
                    <li class="menu-title"><span>Signature</span></li>
                    <li>
                        <a href="{{ url('signature-list') }}" class="{{ Request::is('signature-list') ? 'active' : '' }}"><i class="fe fe-clipboard"></i> <span>List of Signature</span></a>
                    </li>
                    <li>
                        <a href="{{ url('signature-invoice') }}" class="{{ Request::is('signature-invoice') ? 'active' : '' }}"><i class="fe fe-box"></i> <span>Signature Invoice</span></a>
                    </li>
                    
                    {{-- Sales Section --}}
                    <li class="menu-title"><span>Sales</span></li>
                    <li class="submenu">
                        <a href="#"><i class="fe fe-file"></i> <span>Invoices</span><span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="{{ url('invoices') }}" class="{{ Request::is('invoices', 'invoices-paid', 'invoices-overdue', 'invoices-cancelled', 'invoices-recurring', 'invoices-unpaid', 'invoices-refunded', 'invoices-draft') ? 'active' : '' }}">Invoices List</a></li>
                            <li><a href="{{ route('add-invoice') }}" class="{{ Request::is('add-invoice') ? 'active' : '' }}">Create Invoice</a></li>
                            <li><a href="{{ url('invoice-details-admin') }}" class="{{ Request::is('invoice-details-admin') ? 'active' : '' }}">Invoice Details (Admin)</a></li>
                            <li><a href="{{ url('invoice-details') }}" class="{{ Request::is('invoice-details') ? 'active' : '' }}">Invoice Details (Customer)</a></li>
                            <li><a href="{{ url('invoice-template') }}" class="{{ Request::is('invoice-template') ? 'active' : '' }}">Invoice Templates</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="{{ url('recurring-invoices') }}" class="{{ Request::is('recurring-invoices') ? 'active' : '' }}"><i class="fe fe-clipboard"></i> <span>Recurring Invoices</span></a>
                    </li>
                    <li>
                        <a href="{{ url('credit-notes') }}" class="{{ Request::is('credit-notes', 'add-credit-notes', 'edit-credit-notes') ? 'active' : '' }}"><i class="fe fe-edit"></i> <span>Credit Notes</span></a>
                    </li>
                    
                    {{-- Purchases Section --}}
                    <li class="menu-title"><span>Purchases</span></li>
                    <li>
                        <a href="{{ url('purchases') }}" class="{{ Request::is('purchases', 'add-purchases', 'edit-purchases', 'add-purchase-return', 'edit-purchase-return') ? 'active' : '' }}"><i class="fe fe-shopping-cart"></i> <span>Purchases</span></a>
                    </li>
                    <li>
                        <a href="{{ url('purchase-orders') }}" class="{{ Request::is('purchase-orders', 'add-purchases-order', 'edit-purchases-order') ? 'active' : '' }}"><i class="fe fe-shopping-bag"></i> <span>Purchase Orders</span></a>
                    </li>
                    <li>
                        <a href="{{ url('debit-notes') }}" class="{{ Request::is('debit-notes') ? 'active' : '' }}"><i class="fe fe-file-text"></i> <span>Debit Notes</span></a>
                    </li>
                    
                    {{-- Finance & Accounts Section --}}
                    <li class="menu-title"><span>Finance & Accounts</span></li>
                    <li>
                        <a href="{{ url('expenses') }}" class="{{ Request::is('expenses') ? 'active' : '' }}"><i class="fe fe-file-plus"></i> <span>Expenses</span></a>
                    </li>
                        <li>
                            <a href="{{ url('payments') }}" class="{{ Request::is('payments') ? 'active' : '' }}"><i class="fe fe-credit-card"></i> <span>Payments</span></a>
                        </li>
                        @if(Route::has('finance.recurring.index'))
                        <li>
                            <a href="{{ route('finance.recurring.index') }}" class="{{ Request::is('finance/recurring-transactions*') ? 'active' : '' }}"><i class="fe fe-repeat"></i> <span>Recurring Transactions</span></a>
                        </li>
                        @endif
                        @if(Route::has('finance.approvals.index'))
                        <li>
                            <a href="{{ route('finance.approvals.index') }}" class="{{ Request::is('finance/approvals*') ? 'active' : '' }}"><i class="fe fe-check-square"></i> <span>Approval Queue</span></a>
                        </li>
                        @endif
                    
                    {{-- Quotations Section --}}
                    <li class="menu-title"><span>Quotations</span></li>
                    <li>
                        <a href="{{ url('quotations') }}" class="{{ Request::is('quotations', 'add-quotations', 'edit-quotations') ? 'active' : '' }}"><i class="fe fe-clipboard"></i> <span>Quotations</span></a>
                    </li>
                    <li>
                        <a href="{{ url('delivery-challans') }}" class="{{ Request::is('delivery-challans', 'add-delivery-challans', 'edit-delivery-challans') ? 'active' : '' }}"><i class="fe fe-file-text"></i> <span>Delivery Challans</span></a>
                    </li>
                    
                    {{-- Reports Section --}}
                    <li class="menu-title"><span>Reports</span></li>
                        <li>
                            <a href="{{ route('reports.payment-summary') }}" class="{{ Request::is('reports/payment-summary') ? 'active' : '' }}"><i class="fe fe-credit-card"></i> <span>Payment Summary</span></a>
                        </li>
                    <li class="submenu">
                        <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="{{ route('reports.expense') }}" class="{{ Request::is('reports/expense-report') ? 'active' : '' }}">Expense Report</a></li>
                            <li><a href="{{ route('reports.purchase') }}" class="{{ Request::is('reports/purchase-report') ? 'active' : '' }}">Purchase Report</a></li>
                            <li><a href="{{ route('reports.sales') }}" class="{{ Request::is('reports/sales-report') ? 'active' : '' }}">Sales Report</a></li>
                            <li><a href="{{ route('reports.sales-return') }}" class="{{ Request::is('reports/sales-return-report') ? 'active' : '' }}">Sales Return Report</a></li>
                            <li><a href="{{ route('reports.quotation') }}" class="{{ Request::is('reports/quotation-report') ? 'active' : '' }}">Quotation Report</a></li>
                            <li><a href="{{ route('reports.payment') }}" class="{{ Request::is('reports/payment-report') ? 'active' : '' }}">Payment Report</a></li>
                            <li><a href="{{ route('reports.stock') }}" class="{{ Request::is('reports/stock-report') ? 'active' : '' }}">Stock Report</a></li>
                            <li><a href="{{ route('reports.low-stock') }}" class="{{ Request::is('reports/low-stock-report') ? 'active' : '' }}">Low Stock Report</a></li>
                            <li><a href="{{ route('reports.income') }}" class="{{ Request::is('reports/income-report') ? 'active' : '' }}">Income Report</a></li>
                            <li><a href="{{ route('reports.tax-purchase') }}" class="{{ Request::is('reports/tax-purchase', 'reports/tax-sales') ? 'active' : '' }}">Tax Report</a></li>
                            <li><a href="{{ route('reports.profit-loss') }}" class="{{ Request::is('reports/profit-loss-list') ? 'active' : '' }}">Profit & Loss</a></li>
                            <li><a href="{{ route('trial-balance') }}" class="{{ Request::is('trial-balance') ? 'active' : '' }}">Trial Balance</a></li>
                            <li><a href="{{ route('balance-sheet') }}" class="{{ Request::is('balance-sheet') ? 'active' : '' }}">Balance Sheet</a></li>
                            <li><a href="{{ route('reports.cash-flow') }}" class="{{ Request::is('cash-flow') ? 'active' : '' }}">Cash Flow Statement</a></li>
                        </ul>
                    </li>
                    
                    {{-- User Management Section --}}
                    <li class="menu-title"><span>User Management</span></li>
                    <li>
                        <a class="{{ Request::is('users') ? 'active' : '' }}" href="{{ url('users') }}"><i
                                class="fe fe-user"></i> <span>Users</span></a>
                    </li>
                    <li>
                        <a class="{{ Request::is('roles-permission', 'permission') ? 'active' : '' }}"
                            href="{{ url('roles-permission') }}"><i class="fe fe-clipboard"></i> <span>Roles & Permission</span></a>
                    </li>
                    <li>
                        <a class="{{ Request::is('delete-account-request') ? 'active' : '' }}"
                            href="{{ url('delete-account-request') }}"><i class="fe fe-trash-2"></i> <span>Delete Account Request</span></a>
                    </li>
                    <li class="menu-title"><span>Membership</span></li>
                    <li class="submenu">
                        <a href="#"><i class="fe fe-book"></i> <span> Membership</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a class="{{ Request::is('membership-plans') ? 'active' : '' }}" href="{{ url('membership-plans') }}">Membership Plans</a></li>
                            <li><a class="{{ Request::is('membership-addons') ? 'active' : '' }}" href="{{ url('membership-addons') }}">Membership Addons</a></li>
                            <li><a class="{{ Request::is('subscribers') ? 'active' : '' }}" href="{{ url('subscribers') }}">Subscribers</a></li>
                            <li><a class="{{ Request::is('transactions') ? 'active' : '' }}" href="{{ url('transactions') }}">Transactions</a></li>
                        </ul>
                    </li>
                    <li class="submenu">
                        <a href="#"><i class="fe fe-sidebar"></i> <span> Forms </span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a class="{{ Request::is('form-basic-inputs') ? 'active' : '' }}" href="{{ url('form-basic-inputs') }}">Basic Inputs </a></li>
                        </ul>
                    </li>
                    <li class="submenu">
                        <a href="#"><i class="fe fe-layout"></i> <span> Tables </span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a class="{{ Request::is('tables-basic') ? 'active' : '' }}" href="{{ url('tables-basic') }}">Basic Tables </a></li>
                            <li><a class="{{ Request::is('data-tables') ? 'active' : '' }}" href="{{ url('data-tables') }}">Data Table </a></li>
                        </ul>
                    </li>
                    <li class="menu-title"><span>Settings</span></li>
                    <li>
                        <a class="{{ Request::is('settings', 'company-settings', 'invoice-settings', 'template-invoice', 'payment-settings', 'bank-account', 'tax-rates', 'plan-billing', 'two-factor', 'custom-filed', 'email-settings', 'preferences', 'saas-settings', 'seo-settings', 'email-template') ? 'active' : '' }}"
                            href="{{ url('settings') }}"><i class="fe fe-settings"></i>
                            <span>Settings</span></a>
                    </li>
                    </ul>
            </div>
        </div>
    </div>
@endif
