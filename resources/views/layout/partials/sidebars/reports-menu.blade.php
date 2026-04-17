{{--
    QuickBooks-style Reports dropdown for all sidebars.
    Expects: $reportAccess — 'basic' | 'pro' | 'enterprise' | 'full'
    'full' = super_admin (no plan gate, all reports visible)
--}}
@php
    $ra = $reportAccess ?? 'basic';
    $isPro  = in_array($ra, ['pro', 'enterprise', 'full']);
    $isEnt  = in_array($ra, ['enterprise', 'full']);
    $isFull = $ra === 'full';

    // Active detection across all report URLs
    $reportsActive = Request::is(
        'reports*', '*-report*', '*-report',
        'profit-loss*', 'trial-balance*', 'general-ledger*',
        'balance-sheet*', 'cash-flow*'
    );
@endphp

{{-- ═══ QB-STYLE REPORTS DROPDOWN ═══════════════════════════════════════ --}}
<li class="submenu reports-nav {{ $reportsActive ? 'active subdrop' : '' }}">
    <a href="#">
        <i class="fe fe-bar-chart-2"></i>
        <span>Reports</span>
        <span class="menu-arrow"></span>
    </a>
    <ul>

        {{-- ── Reports Hub (top shortcut) ─────────────────────────────── --}}
        <li>
            <a href="{{ route('reports.hub') }}" class="reports-hub-link {{ request()->routeIs('reports.hub') ? 'active' : '' }}">
                <i class="fe fe-grid me-1"></i> All Reports Hub
            </a>
        </li>

        {{-- ══ STANDARD REPORTS ═════════════════════════════════════════ --}}
        <li class="reports-group-header">Standard Reports</li>

        @if(Route::has('reports.sales'))
        <li>
            <a href="{{ route('reports.sales') }}" class="{{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                Sales Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.purchase'))
        <li>
            <a href="{{ route('reports.purchase') }}" class="{{ request()->routeIs('reports.purchase') ? 'active' : '' }}">
                Purchase Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.expense'))
        <li>
            <a href="{{ route('reports.expense') }}" class="{{ request()->routeIs('reports.expense') ? 'active' : '' }}">
                Expense Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.income'))
        <li>
            <a href="{{ route('reports.income') }}" class="{{ request()->routeIs('reports.income') ? 'active' : '' }}">
                Income Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.payment'))
        <li>
            <a href="{{ route('reports.payment') }}" class="{{ request()->routeIs('reports.payment') ? 'active' : '' }}">
                Payment Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.accounts-receivable'))
        <li>
            <a href="{{ route('reports.accounts-receivable') }}" class="{{ request()->routeIs('reports.accounts-receivable') ? 'active' : '' }}">
                Accounts Receivable
            </a>
        </li>
        @endif

        @if(Route::has('reports.quotation'))
        <li>
            <a href="{{ route('reports.quotation') }}" class="{{ request()->routeIs('reports.quotation') ? 'active' : '' }}">
                Quotation Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.sales-return'))
        <li>
            <a href="{{ route('reports.sales-return') }}" class="{{ request()->routeIs('reports.sales-return') ? 'active' : '' }}">
                Sales Returns
            </a>
        </li>
        @endif

        @if(Route::has('reports.purchase-return'))
        <li>
            <a href="{{ route('reports.purchase-return') }}" class="{{ request()->routeIs('reports.purchase-return') ? 'active' : '' }}">
                Purchase Returns
            </a>
        </li>
        @endif

        {{-- ══ INVENTORY REPORTS ════════════════════════════════════════ --}}
        <li class="reports-group-header">Inventory Reports</li>

        @if(Route::has('reports.stock'))
        <li>
            <a href="{{ route('reports.stock') }}" class="{{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                Stock Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.low-stock'))
        <li>
            <a href="{{ route('reports.low-stock') }}" class="{{ request()->routeIs('reports.low-stock') ? 'active' : '' }}">
                Low Stock Alert
            </a>
        </li>
        @endif

        {{-- ══ MANAGEMENT REPORTS (Pro / Enterprise / Full) ════════════ --}}
        @if($isPro)
        <li class="reports-group-header">Management Reports</li>

        @if(Route::has('reports.profit-loss'))
        <li>
            <a href="{{ route('reports.profit-loss') }}" class="{{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}">
                Profit &amp; Loss
            </a>
        </li>
        @endif

        @if(Route::has('reports.payment-summary'))
        <li>
            <a href="{{ route('reports.payment-summary') }}" class="{{ request()->routeIs('reports.payment-summary') ? 'active' : '' }}">
                Payment Summary
            </a>
        </li>
        @endif

        @if(Route::has('balance-sheet'))
        <li>
            <a href="{{ route('balance-sheet') }}" class="{{ request()->routeIs('balance-sheet') ? 'active' : '' }}">
                Balance Sheet
            </a>
        </li>
        @endif

        @if(Route::has('reports.cash-flow'))
        <li>
            <a href="{{ route('reports.cash-flow') }}" class="{{ request()->routeIs('reports.cash-flow') ? 'active' : '' }}">
                Cash Flow Statement
            </a>
        </li>
        @endif

        @endif {{-- /isPro --}}

        {{-- ══ FINANCIAL & TAX (Enterprise / Full) ════════════════════ --}}
        @if($isEnt)
        <li class="reports-group-header">Financial &amp; Tax</li>

        @if(Route::has('trial-balance'))
        <li>
            <a href="{{ route('trial-balance') }}" class="{{ request()->routeIs('trial-balance') ? 'active' : '' }}">
                Trial Balance
            </a>
        </li>
        @endif

        @if(Route::has('general-ledger'))
        <li>
            <a href="{{ route('general-ledger') }}" class="{{ request()->routeIs('general-ledger') ? 'active' : '' }}">
                General Ledger
            </a>
        </li>
        @endif

        @if(Route::has('reports.tax-sales'))
        <li>
            <a href="{{ route('reports.tax-sales') }}" class="{{ request()->routeIs('reports.tax-sales') ? 'active' : '' }}">
                Sales Tax Report
            </a>
        </li>
        @endif

        @if(Route::has('reports.tax-purchase'))
        <li>
            <a href="{{ route('reports.tax-purchase') }}" class="{{ request()->routeIs('reports.tax-purchase') ? 'active' : '' }}">
                Purchase Tax Report
            </a>
        </li>
        @endif

        @endif {{-- /isEnt --}}

        {{-- ══ CUSTOM REPORTS ═══════════════════════════════════════════ --}}
        <li class="reports-group-header">Custom Reports</li>
        <li>
            <a href="{{ route('reports.hub') }}?tab=custom" class="{{ request()->is('reports*') && request('tab') === 'custom' ? 'active' : '' }}">
                Custom Report Builder
            </a>
        </li>
        <li>
            <a href="{{ route('reports.hub') }}?tab=favourites">
                Starred / Favourites
            </a>
        </li>

        {{-- Upgrade teaser for non-enterprise --}}
        @if($ra === 'basic')
        <li class="reports-group-header">Upgrade for More</li>
        <li>
            <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'pro']) : url('/membership-plans?plan=pro') }}">
                <span class="badge bg-info me-1">Pro</span> Management Reports
            </a>
        </li>
        <li>
            <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                <span class="badge bg-warning me-1">Ent</span> Tax &amp; Financial
            </a>
        </li>
        @elseif($ra === 'pro')
        <li class="reports-group-header">Upgrade for More</li>
        <li>
            <a href="{{ Route::has('membership-plans') ? route('membership-plans', ['plan' => 'enterprise']) : url('/membership-plans?plan=enterprise') }}">
                <span class="badge bg-warning me-1">Ent</span> Tax &amp; Financial Reports
            </a>
        </li>
        @endif

    </ul>
</li>
