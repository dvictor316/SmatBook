
@php
    $currentTab = request('tab', 'standard');
    $isReports = Request::is('reports*', 'trial-balance', 'balance-sheet', 'cash-flow')
        || request()->routeIs('reports.*', 'trial-balance', 'balance-sheet', 'balance-sheet-summary', 'balance-sheet-comparison');
    $reportAccess = $reportAccess ?? 'basic';
    $renderNested = (bool) ($nested ?? false);
    $canManagement = in_array($reportAccess, ['pro', 'professional', 'enterprise', 'full'], true);
    $canEnterprise = in_array($reportAccess, ['enterprise', 'full'], true);
    $routeActive = fn (...$routes) => request()->routeIs(...$routes) ? 'active' : '';
    $pathActive = fn (...$paths) => Request::is(...$paths) ? 'active' : '';
@endphp

@if(!$renderNested)
    <li class="submenu {{ $isReports ? 'active subdrop' : '' }}">
        <a href="#">
            <i class="fas fa-chart-simple"></i>
            <span>Reports</span>
            <span class="menu-arrow"></span>
        </a>
        <ul>
@endif

@if(Route::has('reports.hub'))
    <li>
        <a href="{{ route('reports.hub') }}"
           class="reports-hub-link {{ request()->routeIs('reports.hub') && !request()->has('tab') ? 'active' : '' }}">
            Reports Hub
        </a>
    </li>
@endif

<li class="reports-group-header">Standard</li>
@if(Route::has('reports.hub'))
    <li><a href="{{ route('reports.hub') }}?tab=standard" class="{{ request()->routeIs('reports.hub') && $currentTab === 'standard' ? 'active' : '' }}">Standard Reports</a></li>
@endif
@if(Route::has('reports.sales'))
    <li><a href="{{ route('reports.sales') }}" class="{{ $routeActive('reports.sales') }}">Sales Report</a></li>
@endif
@if(Route::has('reports.purchase'))
    <li><a href="{{ route('reports.purchase') }}" class="{{ $routeActive('reports.purchase') }}">Purchase Report</a></li>
@endif
@if(Route::has('reports.expense'))
    <li><a href="{{ route('reports.expense') }}" class="{{ $routeActive('reports.expense') }}">Expense Report</a></li>
@endif
@if(Route::has('reports.income'))
    <li><a href="{{ route('reports.income') }}" class="{{ $routeActive('reports.income') }}">Income Report</a></li>
@endif
@if(Route::has('reports.payment'))
    <li><a href="{{ route('reports.payment') }}" class="{{ $routeActive('reports.payment') }}">Payment Report</a></li>
@endif
@if(Route::has('reports.quotation'))
    <li><a href="{{ route('reports.quotation') }}" class="{{ $routeActive('reports.quotation') }}">Quotation Report</a></li>
@endif
@if(Route::has('reports.sales-return'))
    <li><a href="{{ route('reports.sales-return') }}" class="{{ $routeActive('reports.sales-return') }}">Sales Return Report</a></li>
@endif
@if(Route::has('reports.stock'))
    <li><a href="{{ route('reports.stock') }}" class="{{ $routeActive('reports.stock') }}">Stock Report</a></li>
@endif
@if(Route::has('reports.low-stock'))
    <li><a href="{{ route('reports.low-stock') }}" class="{{ $routeActive('reports.low-stock') }}">Low Stock Report</a></li>
@endif
@if(Route::has('reports.tax-sales'))
    <li><a href="{{ route('reports.tax-sales') }}" class="{{ $routeActive('reports.tax-sales') }}">Tax Sales Report</a></li>
@endif
@if(Route::has('reports.tax-purchase'))
    <li><a href="{{ route('reports.tax-purchase') }}" class="{{ $routeActive('reports.tax-purchase') }}">Tax Purchase Report</a></li>
@endif

@if($canManagement)
    <li class="reports-group-header">Management</li>
    @if(Route::has('reports.hub'))
        <li><a href="{{ route('reports.hub') }}?tab=management" class="{{ request()->routeIs('reports.hub') && $currentTab === 'management' ? 'active' : '' }}">Management Reports</a></li>
    @endif
    @if(Route::has('reports.payment-summary'))
        <li><a href="{{ route('reports.payment-summary') }}" class="{{ $routeActive('reports.payment-summary') }}">Payment Summary</a></li>
    @endif
    @if(Route::has('reports.accounts-receivable'))
        <li><a href="{{ route('reports.accounts-receivable') }}" class="{{ $routeActive('reports.accounts-receivable') }}">Accounts Receivable</a></li>
    @endif
    @if(Route::has('reports.ar-ageing-detail'))
        <li><a href="{{ route('reports.ar-ageing-detail') }}" class="{{ $routeActive('reports.ar-ageing-detail') }}">AR Ageing Detail</a></li>
    @endif
    @if(Route::has('reports.open-invoices'))
        <li><a href="{{ route('reports.open-invoices') }}" class="{{ $routeActive('reports.open-invoices') }}">Open Invoices</a></li>
    @endif
    @if(Route::has('reports.sales-by-customer'))
        <li><a href="{{ route('reports.sales-by-customer') }}" class="{{ $routeActive('reports.sales-by-customer') }}">Sales by Customer</a></li>
    @endif
    @if(Route::has('reports.sales-by-product'))
        <li><a href="{{ route('reports.sales-by-product') }}" class="{{ $routeActive('reports.sales-by-product') }}">Sales by Product</a></li>
    @endif
    @if(Route::has('reports.sales-summary'))
        <li><a href="{{ route('reports.sales-summary') }}" class="{{ $routeActive('reports.sales-summary') }}">Sales Summary</a></li>
    @endif
    @if(Route::has('reports.purchase-by-supplier'))
        <li><a href="{{ route('reports.purchase-by-supplier') }}" class="{{ $routeActive('reports.purchase-by-supplier') }}">Purchases by Supplier</a></li>
    @endif
    @if(Route::has('reports.purchase-summary'))
        <li><a href="{{ route('reports.purchase-summary') }}" class="{{ $routeActive('reports.purchase-summary') }}">Purchase Summary</a></li>
    @endif
    @if(Route::has('reports.profit-loss'))
        <li><a href="{{ route('reports.profit-loss') }}" class="{{ $routeActive('reports.profit-loss') }}">Profit &amp; Loss</a></li>
    @endif
    @if(Route::has('reports.profit-loss-comparison'))
        <li><a href="{{ route('reports.profit-loss-comparison') }}" class="{{ $routeActive('reports.profit-loss-comparison') }}">P&amp;L Comparison</a></li>
    @endif
    @if(Route::has('reports.profit-loss-by-month'))
        <li><a href="{{ route('reports.profit-loss-by-month') }}" class="{{ $routeActive('reports.profit-loss-by-month') }}">P&amp;L by Month</a></li>
    @endif
    @if(Route::has('reports.profit-loss-detail'))
        <li><a href="{{ route('reports.profit-loss-detail') }}" class="{{ $routeActive('reports.profit-loss-detail') }}">P&amp;L Detail</a></li>
    @endif
    @if(Route::has('reports.cash-flow'))
        <li><a href="{{ route('reports.cash-flow') }}" class="{{ $routeActive('reports.cash-flow') }}">Cash Flow</a></li>
    @endif
    @if(Route::has('reports.chart-of-accounts'))
        <li><a href="{{ route('reports.chart-of-accounts') }}" class="{{ $routeActive('reports.chart-of-accounts') }}">Chart of Accounts</a></li>
    @endif
    @if(Route::has('reports.stock-valuation'))
        <li><a href="{{ route('reports.stock-valuation') }}" class="{{ $routeActive('reports.stock-valuation') }}">Stock Valuation</a></li>
    @endif
    @if(Route::has('reports.stock-by-category'))
        <li><a href="{{ route('reports.stock-by-category') }}" class="{{ $routeActive('reports.stock-by-category') }}">Stock by Category</a></li>
    @endif
@endif

@if($canEnterprise)
    <li class="reports-group-header">Financial Statements</li>
    @if(Route::has('trial-balance'))
        <li><a href="{{ route('trial-balance') }}" class="{{ $pathActive('trial-balance') }}">Trial Balance</a></li>
    @endif
    @if(Route::has('balance-sheet'))
        <li><a href="{{ route('balance-sheet') }}" class="{{ $pathActive('balance-sheet') }}">Balance Sheet</a></li>
    @endif
    @if(Route::has('balance-sheet-summary'))
        <li><a href="{{ route('balance-sheet-summary') }}" class="{{ $pathActive('balance-sheet-summary') }}">Balance Sheet Summary</a></li>
    @endif
    @if(Route::has('balance-sheet-comparison'))
        <li><a href="{{ route('balance-sheet-comparison') }}" class="{{ $pathActive('balance-sheet-comparison') }}">Balance Sheet Comparison</a></li>
    @endif
    @if(Route::has('reports.tax-summary'))
        <li><a href="{{ route('reports.tax-summary') }}" class="{{ $routeActive('reports.tax-summary') }}">Tax Summary</a></li>
    @endif

    <li class="reports-group-header">Custom</li>
    @if(Route::has('reports.hub'))
        <li><a href="{{ route('reports.hub') }}?tab=custom" class="{{ request()->routeIs('reports.hub') && $currentTab === 'custom' ? 'active' : '' }}">Custom Reports</a></li>
    @endif
    @if(Route::has('custom-filed'))
        <li><a href="{{ route('custom-filed') }}" class="{{ $routeActive('custom-filed') }}">Custom Fields</a></li>
    @endif
@endif

@if(!$renderNested)
        </ul>
    </li>
@endif
