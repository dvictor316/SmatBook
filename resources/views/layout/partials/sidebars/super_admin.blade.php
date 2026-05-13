
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

{{-- Super Admin sidebar: blue and gold theme --}}
<style>
    .spb-super-admin-sidebar {
        --sa-navy: #061a44;
        --sa-blue: #0f3a8a;
        --sa-blue-bright: #2563eb;
        --sa-gold: #d7a928;
        --sa-gold-soft: #ffe8a3;
        --sa-text: #f8fbff;
        --sa-muted: #a9bce3;
        --sa-panel: rgba(255, 255, 255, 0.08);
        --sa-panel-strong: rgba(255, 255, 255, 0.14);
        --sa-line: rgba(215, 169, 40, 0.26);
        background:
            radial-gradient(circle at 16% 8%, rgba(215, 169, 40, 0.22), transparent 28%),
            radial-gradient(circle at 84% 22%, rgba(37, 99, 235, 0.24), transparent 34%),
            linear-gradient(180deg, var(--sa-navy) 0%, var(--sa-blue) 52%, #071635 100%) !important;
        border-right: 1px solid rgba(215, 169, 40, 0.22) !important;
        box-shadow: 10px 0 32px rgba(6, 26, 68, 0.22) !important;
        overflow: hidden;
    }

    .spb-super-admin-sidebar::before {
        content: "" !important;
        position: absolute !important;
        inset: 0 !important;
        background: url('{{ asset('assets/img/logos.png') }}') center 54% / 150px auto no-repeat !important;
        opacity: 0.035 !important;
        pointer-events: none !important;
    }

    .spb-super-admin-sidebar .sidebar-inner,
    .spb-super-admin-sidebar .sidebar-menu {
        background: transparent !important;
        position: relative;
        z-index: 1;
    }

    .spb-super-admin-sidebar .sidebar-menu {
        padding: 14px 10px 22px !important;
    }

    .spb-super-admin-sidebar .sidebar-menu .menu-title {
        margin: 18px 8px 7px !important;
        padding: 0 !important;
    }

    .spb-super-admin-sidebar .sidebar-menu .menu-title span {
        color: var(--sa-gold-soft) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.12em !important;
        text-transform: uppercase !important;
        opacity: 0.95;
    }

    .spb-super-admin-sidebar .sidebar-menu ul li > a {
        color: var(--sa-text) !important;
        background: transparent !important;
        border: 1px solid transparent !important;
        border-radius: 14px !important;
        margin: 3px 4px !important;
        padding: 10px 12px !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em;
        transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease !important;
    }

    .spb-super-admin-sidebar .sidebar-menu ul li > a span,
    .spb-super-admin-sidebar .sidebar-menu ul li > a i,
    .spb-super-admin-sidebar .sidebar-menu ul li > a .menu-arrow {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }

    .spb-super-admin-sidebar .sidebar-menu ul li > a i {
        color: var(--sa-gold-soft) !important;
        opacity: 0.98;
    }

    .spb-super-admin-sidebar .sidebar-menu ul li > a:hover,
    .spb-super-admin-sidebar .sidebar-menu ul li > a.subdrop {
        color: #ffffff !important;
        background: var(--sa-panel) !important;
        border-color: var(--sa-line) !important;
        box-shadow: inset 3px 0 0 var(--sa-gold), 0 10px 22px rgba(0, 0, 0, 0.14) !important;
        transform: translateX(2px);
    }

    .spb-super-admin-sidebar .sidebar-menu ul li.active > a,
    .spb-super-admin-sidebar .sidebar-menu ul li > a.active {
        color: #ffffff !important;
        background: linear-gradient(135deg, rgba(215, 169, 40, 0.24), rgba(37, 99, 235, 0.2)) !important;
        border-color: rgba(215, 169, 40, 0.45) !important;
        box-shadow: inset 3px 0 0 var(--sa-gold), 0 12px 26px rgba(0, 0, 0, 0.18) !important;
    }

    .spb-super-admin-sidebar .sidebar-menu ul li.submenu ul {
        background: rgba(3, 13, 33, 0.42) !important;
        border: 1px solid rgba(215, 169, 40, 0.12);
        border-radius: 16px !important;
        margin: 6px auto 10px !important;
        width: calc(100% - 24px) !important;
        padding: 6px 0 !important;
    }

    .spb-super-admin-sidebar .sidebar-menu ul li.submenu ul li a {
        color: #dbe8ff !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 12px !important;
        margin: 2px 6px !important;
        padding: 8px 10px 8px 28px !important;
        font-size: 0.86rem !important;
        font-weight: 650 !important;
        box-shadow: none !important;
    }

    .spb-super-admin-sidebar .sidebar-menu ul li.submenu ul li a:hover,
    .spb-super-admin-sidebar .sidebar-menu ul li.submenu ul li a.active,
    .spb-super-admin-sidebar .sidebar-menu ul li.submenu ul li.active > a {
        color: var(--sa-gold-soft) !important;
        background: rgba(215, 169, 40, 0.12) !important;
        box-shadow: inset 2px 0 0 var(--sa-gold) !important;
        transform: none;
    }

    .spb-super-admin-sidebar .badge {
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .spb-super-admin-sidebar .slimScrollBar {
        background: rgba(215, 169, 40, 0.42) !important;
        border-radius: 999px !important;
    }

    body.spb-super-admin-theme {
        --spb-theme-navy: #061a44;
        --spb-theme-blue: #0f3a8a;
        --spb-theme-blue-bright: #2563eb;
        --spb-theme-gold: #d7a928;
        --spb-theme-gold-soft: #ffe8a3;
        --spb-theme-ink: #10264f;
        --spb-theme-muted: #64748b;
        --spb-theme-surface: #f7faff;
    }

    body.spb-super-admin-theme:not(.login-body):not(.landing-page-body) .page-wrapper {
        background:
            radial-gradient(circle at top right, rgba(215, 169, 40, 0.08), transparent 32%),
            linear-gradient(180deg, #f8fbff 0%, #f3f7ff 100%) !important;
    }

    body.spb-super-admin-theme .header {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%) !important;
        border-bottom: 1px solid rgba(15, 58, 138, 0.12) !important;
        box-shadow: 0 12px 28px rgba(6, 26, 68, 0.08) !important;
    }

    body.spb-super-admin-theme .page-title,
    body.spb-super-admin-theme .content-page-header h5,
    body.spb-super-admin-theme .card-title,
    body.spb-super-admin-theme h1,
    body.spb-super-admin-theme h2,
    body.spb-super-admin-theme h3,
    body.spb-super-admin-theme h4,
    body.spb-super-admin-theme h5 {
        color: var(--spb-theme-ink) !important;
    }

    body.spb-super-admin-theme .breadcrumb a,
    body.spb-super-admin-theme .breadcrumb-item a {
        color: var(--spb-theme-blue) !important;
        font-weight: 700;
    }

    body.spb-super-admin-theme .breadcrumb-item.active,
    body.spb-super-admin-theme .text-muted {
        color: var(--spb-theme-muted) !important;
    }

    body.spb-super-admin-theme .card,
    body.spb-super-admin-theme .estimate-panel,
    body.spb-super-admin-theme .ri-wizard-shell {
        border-color: rgba(15, 58, 138, 0.12) !important;
        box-shadow: 0 14px 34px rgba(6, 26, 68, 0.07) !important;
    }

    body.spb-super-admin-theme .card-header,
    body.spb-super-admin-theme .estimate-panel-head,
    body.spb-super-admin-theme .ri-wizard-tabs {
        background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%) !important;
        border-color: rgba(15, 58, 138, 0.10) !important;
    }

    body.spb-super-admin-theme .btn-primary,
    body.spb-super-admin-theme .btn-success,
    body.spb-super-admin-theme .btn-info,
    body.spb-super-admin-theme .btn-upload {
        background: linear-gradient(135deg, var(--spb-theme-blue) 0%, var(--spb-theme-blue-bright) 100%) !important;
        border-color: var(--spb-theme-blue) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        box-shadow: 0 10px 24px rgba(15, 58, 138, 0.18) !important;
    }

    body.spb-super-admin-theme .btn-primary:hover,
    body.spb-super-admin-theme .btn-primary:focus-visible,
    body.spb-super-admin-theme .btn-success:hover,
    body.spb-super-admin-theme .btn-success:focus-visible,
    body.spb-super-admin-theme .btn-info:hover,
    body.spb-super-admin-theme .btn-info:focus-visible,
    body.spb-super-admin-theme .btn-upload:hover,
    body.spb-super-admin-theme .btn-upload:focus-visible {
        background: #ffffff !important;
        border-color: var(--spb-theme-gold) !important;
        color: var(--spb-theme-blue) !important;
        -webkit-text-fill-color: var(--spb-theme-blue) !important;
        box-shadow: 0 0 0 1px rgba(215, 169, 40, 0.42) inset, 0 12px 26px rgba(6, 26, 68, 0.12) !important;
    }

    body.spb-super-admin-theme .btn-primary:hover *,
    body.spb-super-admin-theme .btn-primary:focus-visible *,
    body.spb-super-admin-theme .btn-success:hover *,
    body.spb-super-admin-theme .btn-success:focus-visible *,
    body.spb-super-admin-theme .btn-info:hover *,
    body.spb-super-admin-theme .btn-info:focus-visible *,
    body.spb-super-admin-theme .btn-upload:hover *,
    body.spb-super-admin-theme .btn-upload:focus-visible * {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }

    body.spb-super-admin-theme .btn-outline-primary,
    body.spb-super-admin-theme .btn-outline-secondary {
        border-color: rgba(15, 58, 138, 0.28) !important;
        color: var(--spb-theme-blue) !important;
        -webkit-text-fill-color: var(--spb-theme-blue) !important;
        background: #ffffff !important;
    }

    body.spb-super-admin-theme .btn-outline-primary:hover,
    body.spb-super-admin-theme .btn-outline-primary:focus-visible,
    body.spb-super-admin-theme .btn-outline-secondary:hover,
    body.spb-super-admin-theme .btn-outline-secondary:focus-visible {
        background: var(--spb-theme-blue) !important;
        border-color: var(--spb-theme-blue) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .btn-secondary,
    body.spb-super-admin-theme .btn-dark {
        background: #334155 !important;
        border-color: #334155 !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .btn-secondary:hover,
    body.spb-super-admin-theme .btn-dark:hover {
        background: #ffffff !important;
        border-color: var(--spb-theme-gold) !important;
        color: #1f2937 !important;
        -webkit-text-fill-color: #1f2937 !important;
    }

    body.spb-super-admin-theme .nav-tabs .nav-link.active,
    body.spb-super-admin-theme .nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--spb-theme-blue) 0%, var(--spb-theme-blue-bright) 100%) !important;
        border-color: var(--spb-theme-gold) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .badge.bg-primary,
    body.spb-super-admin-theme .bg-primary {
        background-color: var(--spb-theme-blue) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .text-primary {
        color: var(--spb-theme-blue) !important;
        -webkit-text-fill-color: var(--spb-theme-blue) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper {
        --primary-600: var(--spb-theme-blue-bright);
        --primary-700: var(--spb-theme-blue);
        --indigo-600: #174ea6;
        --success-500: #1f9254;
        --success-600: #167246;
        --warning-500: var(--spb-theme-gold);
        --text-primary: var(--spb-theme-ink);
        --text-secondary: #536681;
        --border: rgba(15, 58, 138, 0.14);
        background:
            radial-gradient(circle at top right, rgba(215, 169, 40, 0.12), transparent 32%),
            radial-gradient(circle at 14% 0%, rgba(37, 99, 235, 0.12), transparent 34%),
            linear-gradient(180deg, #f8fbff 0%, #f3f7ff 100%) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .header-stage,
    body.spb-super-admin-theme .pos-full-page-wrapper .pos-header-bar {
        background:
            radial-gradient(circle at 14% 0%, rgba(215, 169, 40, 0.24), transparent 30%),
            linear-gradient(135deg, var(--spb-theme-navy) 0%, var(--spb-theme-blue) 58%, #174ea6 100%) !important;
        border-color: rgba(215, 169, 40, 0.28) !important;
        box-shadow: 0 18px 36px rgba(6, 26, 68, 0.18) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .pos-header-title,
    body.spb-super-admin-theme .pos-full-page-wrapper .pos-header-title *,
    body.spb-super-admin-theme .pos-full-page-wrapper .header-stage .text-muted,
    body.spb-super-admin-theme .pos-full-page-wrapper .header-stage span,
    body.spb-super-admin-theme .pos-full-page-wrapper .header-stage small {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .gradient-text,
    body.spb-super-admin-theme .pos-full-page-wrapper .header-stage i {
        color: var(--spb-theme-gold-soft) !important;
        -webkit-text-fill-color: var(--spb-theme-gold-soft) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .header-util-bar,
    body.spb-super-admin-theme .pos-full-page-wrapper .pos-card,
    body.spb-super-admin-theme .pos-full-page-wrapper .controls-card,
    body.spb-super-admin-theme .pos-full-page-wrapper .summary-panel {
        background: #ffffff !important;
        border-color: rgba(15, 58, 138, 0.14) !important;
        box-shadow: 0 14px 34px rgba(6, 26, 68, 0.08) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .category-pill {
        background: #ffffff !important;
        border-color: rgba(15, 58, 138, 0.18) !important;
        color: var(--spb-theme-blue) !important;
        -webkit-text-fill-color: var(--spb-theme-blue) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .category-pill:hover,
    body.spb-super-admin-theme .pos-full-page-wrapper .category-pill.active {
        background: linear-gradient(135deg, var(--spb-theme-blue) 0%, var(--spb-theme-blue-bright) 100%) !important;
        border-color: var(--spb-theme-gold) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .product-card:hover,
    body.spb-super-admin-theme .pos-full-page-wrapper .product-card.active,
    body.spb-super-admin-theme .pos-full-page-wrapper .product-card.last-picked {
        border-color: var(--spb-theme-gold) !important;
        box-shadow: 0 14px 30px rgba(6, 26, 68, 0.14), inset 0 0 0 1px rgba(215, 169, 40, 0.34) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .scanner-input:focus,
    body.spb-super-admin-theme .pos-full-page-wrapper .form-control:focus,
    body.spb-super-admin-theme .pos-full-page-wrapper .form-select:focus {
        border-color: var(--spb-theme-gold) !important;
        box-shadow: 0 0 0 0.2rem rgba(215, 169, 40, 0.16) !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .btn-add-cart,
    body.spb-super-admin-theme .pos-full-page-wrapper .btn-process {
        background: linear-gradient(135deg, var(--spb-theme-blue) 0%, var(--spb-theme-blue-bright) 100%) !important;
        border: 1px solid rgba(215, 169, 40, 0.32) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .pos-full-page-wrapper .btn-add-cart:hover,
    body.spb-super-admin-theme .pos-full-page-wrapper .btn-process:hover {
        background: #ffffff !important;
        border-color: var(--spb-theme-gold) !important;
        color: var(--spb-theme-blue) !important;
        -webkit-text-fill-color: var(--spb-theme-blue) !important;
    }

    body.spb-super-admin-theme.report-workspace {
        --report-primary: var(--spb-theme-blue);
        --report-card-border: rgba(15, 58, 138, 0.14);
        --report-card-bg: #ffffff;
        --report-heading: var(--spb-theme-ink);
        --report-muted: #64748b;
    }

    body.spb-super-admin-theme.report-workspace .page-wrapper {
        background:
            radial-gradient(circle at top right, rgba(215, 169, 40, 0.10), transparent 30%),
            linear-gradient(180deg, #f8fbff 0%, #f3f7ff 100%) !important;
    }

    body.spb-super-admin-theme.report-workspace .page-header > .content-page-header,
    body.spb-super-admin-theme.report-workspace .page-header > .row,
    body.spb-super-admin-theme.report-workspace .card,
    body.spb-super-admin-theme.report-workspace .card-table,
    body.spb-super-admin-theme.report-workspace .report-container,
    body.spb-super-admin-theme.report-workspace .filter-card,
    body.spb-super-admin-theme.report-workspace .smart-filter-card,
    body.spb-super-admin-theme.report-workspace .report-card {
        border-color: rgba(15, 58, 138, 0.14) !important;
        box-shadow: 0 14px 34px rgba(6, 26, 68, 0.07) !important;
    }

    body.spb-super-admin-theme.report-workspace .table thead th {
        background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%) !important;
        color: var(--spb-theme-ink) !important;
        border-bottom-color: rgba(15, 58, 138, 0.16) !important;
    }

    body.spb-super-admin-theme.report-workspace .table tbody tr:hover {
        background: rgba(215, 169, 40, 0.08) !important;
    }

    body.spb-super-admin-theme .rh-tab.active,
    body.spb-super-admin-theme .rh-tab:hover,
    body.spb-super-admin-theme .rl-run,
    body.spb-super-admin-theme .report-btn.bg-blue-600,
    body.spb-super-admin-theme .report-btn:hover {
        background: linear-gradient(135deg, var(--spb-theme-blue) 0%, var(--spb-theme-blue-bright) 100%) !important;
        border-color: var(--spb-theme-gold) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.spb-super-admin-theme .rl-name,
    body.spb-super-admin-theme .rh-sec-title,
    body.spb-super-admin-theme .report-workspace a:not(.btn):not(.dropdown-item) {
        color: var(--spb-theme-blue) !important;
        -webkit-text-fill-color: var(--spb-theme-blue) !important;
    }
</style>

<script>
    document.body?.classList.add('spb-super-admin-theme');
</script>

<div class="sidebar spb-super-admin-sidebar" id="sidebar">
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

                <li class="menu-title"><span>Platform Control</span></li>

                <li class="submenu {{ Request::is('superadmin*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-command"></i><span>Platform Admin</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('super_admin.dashboard', $routeParams) }}" class="{{ Request::is('superadmin/dashboard') ? 'active' : '' }}">Dashboard</a></li>
                        <li><a href="{{ route('super_admin.companies.index', $routeParams) }}" class="{{ Request::is('superadmin/companies*') ? 'active' : '' }}">Companies</a></li>
                        <li><a href="{{ route('super_admin.subscription', $routeParams) }}" class="{{ Request::is('superadmin/subscription*') ? 'active' : '' }}">Subscriptions</a></li>
                        <li><a href="{{ route('super_admin.packages.index', $routeParams) }}" class="{{ Request::is('superadmin/packages*') ? 'active' : '' }}">Packages</a></li>
                        <li><a href="{{ route('super_admin.domains.index', $routeParams) }}" class="{{ Request::is('superadmin/domains*') ? 'active' : '' }}">Domains</a></li>
                        <li class="{{ Request::is('superadmin/managers*') ? 'active' : '' }}">
                            <a href="{{ route('super_admin.managers.list', $routeParams) }}">Deployment Managers</a>
                        </li>
                        <li class="{{ Request::is('superadmin/users*') ? 'active' : '' }}">
                            <a href="{{ route('super_admin.users.index', $routeParams) }}">Registered Users</a>
                        </li>
                    </ul>
                </li>

                <li class="menu-title"><span>Sales &amp; Customers</span></li>

                <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-cart"></i><span>POS</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">Sales Terminal</a></li>
                        <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li><a href="{{ route('pos.reports') }}">Items Sold</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('customers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
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

                <li><a href="{{ route('inventory.Products') }}"><i class="fe fe-archive"></i><span>Inventory</span></a></li>
                @if(Route::has('inventory.transfer-audit'))
                    <li><a href="{{ route('inventory.transfer-audit') }}"><i class="fe fe-shuffle"></i><span>Transfer Audit</span></a></li>
                @endif
                <li><a href="{{ route('inventory.stock-valuation') }}"><i class="fe fe-bar-chart-2"></i><span>Stock Valuation</span></a></li>

                <li class="submenu {{ Request::is('inventory/lots*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-layers"></i><span>Lot Tracking</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.lots.index') }}">All Lots</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('inventory/serials*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-hash"></i><span>Serial Numbers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.serials.index') }}">All Serials</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('inventory/barcodes*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-tag"></i><span>Barcodes</span><span class="menu-arrow"></span></a>
                    <ul>
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

                <li class="submenu {{ Request::is('quotations*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file-text"></i><span>Quotations</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('quotations') }}">All Quotations</a></li>
                        <li><a href="{{ route('add-quotations') }}">Add Quotation</a></li>
                    </ul>
                </li>

                <li class="{{ Request::is('estimates*') ? 'active' : '' }}">
                    <a href="{{ route('estimates.index') }}"><i class="fe fe-file-text"></i><span>Sales Orders</span></a>
                </li>

                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('sales.recurring-invoices.index') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

                <li class="menu-title"><span>Purchases &amp; Suppliers</span></li>

                <li class="submenu {{ request()->routeIs('purchases.index', 'purchases.create') || Request::is('purchase-orders*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-bag"></i><span>Purchasing</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchases.index') }}">Purchases</a></li>
                        @if(Route::has('purchases.create'))
                            <li><a href="{{ route('purchases.create') }}">Bills</a></li>
                        @endif
                        <li><a href="{{ route('purchase-orders') }}">Purchase Orders</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('purchase-requisitions*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-clipboard"></i><span>Purchase Requisitions</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchase-requisitions.index') }}">All PRs</a></li>
                        <li><a href="{{ route('purchase-requisitions.create') }}">New PR</a></li>
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

                <li class="submenu {{ Request::is('suppliers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-briefcase"></i><span>Suppliers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('suppliers.index') }}">All Suppliers</a></li>
                        @if(Route::has('suppliers.create'))
                            <li><a href="{{ route('suppliers.create') }}">Add Supplier</a></li>
                        @endif
                    </ul>
                </li>

                <li class="menu-title"><span>Inventory &amp; Operations</span></li>

                <li class="submenu {{ Request::is('product-list*', 'categories*', 'units*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-package"></i><span>Products</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('product-list') }}">Product List</a></li>
                        <li><a href="{{ route('add-products') }}">Add Product</a></li>
                        <li><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li><a href="{{ route('units') }}">Units</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('inventory.Products') }}"><i class="fe fe-archive"></i><span>Inventory</span></a></li>
                @if(Route::has('inventory.transfer-audit'))
                    <li><a href="{{ route('inventory.transfer-audit') }}"><i class="fe fe-shuffle"></i><span>Transfer Audit</span></a></li>
                @endif
                <li><a href="{{ route('inventory.stock-valuation') }}"><i class="fe fe-bar-chart-2"></i><span>Stock Valuation</span></a></li>

                <li class="submenu {{ Request::is('inventory/lots*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-layers"></i><span>Lot Tracking</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.lots.index') }}">All Lots</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('inventory/serials*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-hash"></i><span>Serial Numbers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.serials.index') }}">All Serials</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('inventory/barcodes*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-tag"></i><span>Barcodes</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('inventory.barcodes.index') }}">Barcode Management</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Money &amp; Accounting</span></li>

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
                @if(Route::has('finance.budgets.index'))
                    <li><a href="{{ route('finance.budgets.index') }}"><i class="fe fe-target"></i><span>Budgets</span></a></li>
                @endif

                <li class="submenu {{ request()->routeIs('chart-of-accounts', 'bank-reconciliation', 'manual-journal') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-book-open"></i><span>Accounting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chart-of-accounts') }}" class="{{ request()->routeIs('chart-of-accounts') ? 'active' : '' }}">Chart of Accounts</a></li>
                        <li><a href="{{ route('bank-reconciliation') }}" class="{{ request()->routeIs('bank-reconciliation') ? 'active' : '' }}">Bank Reconciliation</a></li>
                        <li><a href="{{ route('manual-journal') }}" class="{{ request()->routeIs('manual-journal') ? 'active' : '' }}">Manual Journal</a></li>
                        <li><a href="{{ route('exchange-rates.index') }}">Exchange Rates</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('intercompany.index') }}"><i class="fe fe-link"></i><span>Intercompany</span></a></li>

                <li class="submenu {{ Request::is('cost-centers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-layers"></i><span>Cost Centers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('cost-centers.index') }}">All Cost Centers</a></li>
                        <li><a href="{{ route('cost-centers.create') }}">New Cost Center</a></li>
                    </ul>
                </li>

                @php
                    $planNameForTax = strtolower((string) (optional($user->company)->plan ?? 'basic'));
                    $canViewTaxation = in_array($roleNormalized, ['super_admin', 'superadmin'], true)
                        || $user?->email === 'donvictorlive@gmail.com'
                        || in_array($planNameForTax, ['enterprise'], true);
                @endphp
                @if($canViewTaxation)

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

                <li class="menu-title"><span>People, Projects &amp; Planning</span></li>

                <li class="menu-title"><span>Manufacturing &amp; BOM</span></li>

                <li class="submenu {{ Request::is('bom*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-git-merge"></i><span>Bill of Materials</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('bom.index') }}">All BOMs</a></li>
                        <li><a href="{{ route('bom.create') }}">New BOM</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('manufacturing*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-settings"></i><span>Manufacturing Orders</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('manufacturing.index') }}">All Orders</a></li>
                        <li><a href="{{ route('manufacturing.create') }}">New Order</a></li>
                    </ul>
                </li>

                <li class="submenu {{ request()->routeIs('employees.*', 'payroll.*', 'salary-structures.*', 'departments.*', 'hr.leave.*', 'hr.attendance.*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>HR Workspace</span><span class="menu-arrow"></span></a>
                    <ul>
                        @if(Route::has('employees.index'))
                            <li><a href="{{ route('employees.index') }}">Employees</a></li>
                        @endif
                        @if(Route::has('departments.index'))
                            <li><a href="{{ route('departments.index') }}">Departments</a></li>
                        @endif
                        @if(Route::has('departments.create'))
                            <li><a href="{{ route('departments.create') }}">New Department</a></li>
                        @endif
                        @if(Route::has('payroll.index'))
                            <li><a href="{{ route('payroll.index') }}">Payroll</a></li>
                        @endif
                        @if(Route::has('salary-structures.index'))
                            <li><a href="{{ route('salary-structures.index') }}">Salary Structures</a></li>
                        @endif
                        <li><a href="{{ route('hr.leave.requests') }}">Leave Requests</a></li>
                        <li><a href="{{ route('hr.leave.create') }}">New Request</a></li>
                        <li><a href="{{ route('hr.leave.types') }}">Leave Types</a></li>
                        <li><a href="{{ route('hr.attendance.index') }}">Attendance Log</a></li>
                        <li><a href="{{ route('hr.attendance.report') }}">Report</a></li>
                    </ul>
                </li>
                @if(Route::has('finance.fixed-assets.index'))
                    <li><a href="{{ route('finance.fixed-assets.index') }}"><i class="fe fe-archive"></i><span>Asset Register</span></a></li>
                @endif
                <li><a href="{{ route('assets.maintenance.index') }}"><i class="fe fe-tool"></i><span>Asset Maintenance</span></a></li>

                <li><a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a></li>
                <li><a href="{{ route('timesheets.index') }}"><i class="fe fe-clock"></i><span>Timesheets</span></a></li>
                <li><a href="{{ route('milestones.index') }}"><i class="fe fe-flag"></i><span>Milestone Billing</span></a></li>
                <li class="submenu {{ Request::is('forecasting*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-trending-up"></i><span>Forecasting</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('forecasting.index') }}">All Forecasts</a></li>
                        <li><a href="{{ route('forecasting.create') }}">New Forecast</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Reports &amp; Compliance</span></li>

                @include('layout.partials.sidebars.reports-menu', ['reportAccess' => 'full'])
                <li><a href="{{ route('report-schedules.index') }}"><i class="fe fe-clock"></i><span>Scheduled Reports</span></a></li>
                <li><a href="{{ route('reports.financial-ratios') }}"><i class="fe fe-percent"></i><span>Financial Ratios</span></a></li>

                @if(Route::has('activity-log.index'))
                    <li><a href="{{ route('activity-log.index') }}"><i class="fe fe-activity"></i><span>Activity Log</span></a></li>
                @endif
                @if(Route::has('close.index'))
                    <li><a href="{{ route('close.index') }}"><i class="fe fe-lock"></i><span>Period Close</span></a></li>
                @endif
                @if(Route::has('audit.index'))
                    <li><a href="{{ route('audit.index') }}"><i class="fe fe-clipboard"></i><span>Audit Trail</span></a></li>
                @endif

                <li class="menu-title"><span>Workspace</span></li>

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'inbox*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('chat.index', $routeParams) }}" class="{{ Request::is('chat*') ? 'active' : '' }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}" class="{{ Request::is('calendar*') ? 'active' : '' }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}" class="{{ Request::is('messages*', 'inbox*') ? 'active' : '' }}">Messages</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Settings &amp; Control</span></li>
                @if(Route::has('users.index'))
                    <li><a href="{{ route('users.index') }}"><i class="fe fe-user"></i><span>Users</span></a></li>
                @endif
                <li class="{{ request()->routeIs('branches.index') ? 'active' : '' }}">
                    <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.index') ? 'active' : '' }}">
                        <i class="fe fe-git-branch"></i><span>Branches</span>
                    </a>
                </li>

                <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles & Permission</span></a></li>

                @if(Route::has('profile'))
                    <li><a href="{{ route('profile') }}"><i class="fe fe-user-check"></i><span>Profile</span></a></li>
                @endif

                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
@endif

@if(in_array($roleNormalized, ['deployment_manager']))
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>Main</span></li>

                <li class="{{ Request::is('deployment/dashboard') ? 'active' : '' }}">
                    <a href="{{ route('deployment.dashboard') }}">
                        <i class="fe fe-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>

                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Client Management</span></li>

                <li class="submenu {{ Request::is('deployment/companies*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-briefcase"></i><span>My Clients</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('deployment.companies.index') }}">All Clients</a></li>
                        <li><a href="{{ route('deployment.companies.active') }}">Active</a></li>
                        <li><a href="{{ route('deployment.companies.pending') }}">Pending</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Subscriptions</span></li>

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

                <li><a href="{{ route('deployment.users.index') }}"><i class="fe fe-users"></i><span>All Users</span></a></li>

                <li class="menu-title"><span>Financial</span></li>

                <li class="submenu {{ Request::is('deployment/commissions*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-dollar-sign"></i><span>My Commissions</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('deployment.commissions.index') }}">All Commissions</a></li>
                        <li><a href="{{ route('deployment.commissions.pending') }}">Pending</a></li>
                        <li><a href="{{ route('deployment.commissions.paid') }}">Paid</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('deployment.invoices.index') }}"><i class="fe fe-file"></i><span>Invoices</span></a></li>

                <li><a href="{{ route('deployment.payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>

                <li class="menu-title"><span>Reports</span></li>

                <li class="submenu {{ Request::is('deployment/reports*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('deployment.reports.performance') }}">Performance</a></li>
                        <li><a href="{{ route('deployment.reports.client-activity') }}">Client Activity</a></li>
                        <li><a href="{{ route('deployment.reports.revenue') }}">Revenue</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Support</span></li>

                <li><a href="{{ route('deployment.support.tickets') }}"><i class="fe fe-help-circle"></i><span>Support Tickets</span></a></li>

                <li><a href="{{ route('deployment.settings') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
@endif

@php
    $plan = $user?->company?->plan ?? 'Basic';
@endphp

@if($plan === 'Enterprise' && !in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin', 'deployment_manager']))
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

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'inbox*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>

                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('customers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
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
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
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

                <li><a href="{{ route('inventory.Products') }}"><i class="fe fe-archive"></i><span>Inventory</span></a></li>

                <li class="menu-title"><span>Sales</span></li>

                <li class="{{ Request::is('estimates*') ? 'active' : '' }}">
                    <a href="{{ route('estimates.index') }}"><i class="fe fe-file-text"></i><span>Sales Orders</span></a>
                </li>

                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('sales.recurring-invoices.index') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

                <li class="menu-title"><span>Purchases</span></li>

                <li><a href="{{ route('purchases.index') }}"><i class="fe fe-shopping-bag"></i><span>Purchases</span></a></li>
                @if(Route::has('purchases.create'))
                    <li><a href="{{ route('purchases.create') }}"><i class="fe fe-file-text"></i><span>Bills</span></a></li>
                @endif
                <li><a href="{{ route('purchase-orders') }}"><i class="fe fe-file-text"></i><span>Purchase Orders</span></a></li>

                <li class="menu-title"><span>Finance</span></li>

                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>

                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
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

                <li><a href="{{ route('quotations') }}"><i class="fe fe-file-text"></i><span>Quotations</span></a></li>

                <li class="menu-title"><span>Reports</span></li>

                @include('layout.partials.sidebars.reports-menu', ['reportAccess' => 'full'])

                <li class="menu-title"><span>Management</span></li>

                <li><a href="{{ route('projects.index') }}"><i class="fe fe-briefcase"></i><span>Project Management</span></a></li>
                <li><a href="{{ route('projects.index') }}#profitability"><i class="fe fe-trending-up"></i><span>Project Profitability</span></a></li>

                <li><a href="{{ route('roles.index') }}"><i class="fe fe-shield"></i><span>Roles & Permission</span></a></li>

                <li><a href="{{ route('settings.index') }}"><i class="fe fe-settings"></i><span>Settings</span></a></li>

            </ul>
        </div>
    </div>
</div>
@endif

@if($plan === 'Professional' && !in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin', 'deployment_manager']))
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

                <li class="submenu {{ Request::is('chat*', 'calendar*', 'messages*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-grid"></i><span>Applications</span><span class="menu-arrow"></span></a>
                    <ul>

                        <li><a href="{{ route('chat.index', $routeParams) }}">Chat</a></li>
                        <li><a href="{{ route('calendar', $routeParams) }}">Calendar</a></li>
                        <li><a href="{{ route('messages.index', $routeParams) }}">Messages</a></li>
                    </ul>
                </li>

                <li class="submenu {{ Request::is('customers*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-users"></i><span>Customers</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('customers.index') }}">Customers</a></li>
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

                <li><a href="{{ route('inventory.Products') }}"><i class="fe fe-archive"></i><span>Inventory</span></a></li>

                <li class="menu-title"><span>Sales</span></li>

                <li class="{{ Request::is('estimates*') ? 'active' : '' }}">
                    <a href="{{ route('estimates.index') }}"><i class="fe fe-file-text"></i><span>Sales Orders</span></a>
                </li>

                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('sales.recurring-invoices.index') }}"><i class="fe fe-clipboard"></i><span>Recurring Invoices</span></a></li>

                <li class="submenu {{ Request::is('pos*', 'sales*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-shopping-cart"></i><span>POS</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.showPos') }}">Sales Terminal</a></li>
                        <li><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li><a href="{{ route('pos.reports') }}">Items Sold</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Purchases</span></li>

                <li><a href="{{ route('purchases.index') }}"><i class="fe fe-shopping-bag"></i><span>Purchases</span></a></li>
                @if(Route::has('purchases.create'))
                    <li><a href="{{ route('purchases.create') }}"><i class="fe fe-file-text"></i><span>Bills</span></a></li>
                @endif
                <li><a href="{{ route('purchase-orders') }}"><i class="fe fe-file-text"></i><span>Purchase Orders</span></a></li>

                <li class="menu-title"><span>Finance</span></li>

                <li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-plus"></i><span>Expenses</span></a></li>

                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
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

                <li><a href="{{ route('quotations') }}"><i class="fe fe-file-text"></i><span>Quotations</span></a></li>

                <li class="menu-title"><span>Reports</span></li>

                <li><a href="{{ route('reports.payment-summary') }}"><i class="fe fe-dollar-sign"></i><span>Payment Summary</span></a></li>

                <li class="submenu {{ Request::is('*-report*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-bar-chart"></i><span>Reports</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
                        <li><a href="{{ route('reports.expense') }}">Expense Report</a></li>
                        <li><a href="{{ route('reports.purchase') }}">Purchase Report</a></li>
                        <li><a href="{{ route('reports.income') }}">Income Report</a></li>
                        <li><a href="{{ route('reports.payment') }}">Payment Report</a></li>
                        <li><a href="{{ route('reports.sales-return') }}">Sales Return Report</a></li>
                        <li><a href="{{ route('reports.quotation') }}">Quotation Report</a></li>
                        <li><a href="{{ route('reports.accounts-receivable') }}">Accounts Receivable</a></li>
                        <li><a href="{{ route('reports.low-stock') }}">Low Stock Report</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Advanced Features</span></li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Profit & Loss</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Trial Balance</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Balance Sheet</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Cash Flow</span>
                        <span class="badge bg-warning">Enterprise</span>
                    </a>
                </li>

                <li class="menu-title"><span>Settings</span></li>

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
            window.location.href = '{{ route("membership-plans") }}';
        }
    });
}
</script>
@endif

@if($plan === 'Basic' && !in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin', 'deployment_manager']))
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

                <li><a href="{{ route('customers.index') }}"><i class="fe fe-users"></i><span>Customers</span></a></li>

                <li class="menu-title"><span>Inventory</span></li>

                <li><a href="{{ route('product-list') }}"><i class="fe fe-package"></i><span>Product List</span></a></li>

                <li class="menu-title"><span>Sales</span></li>

                <li class="{{ Request::is('estimates*') ? 'active' : '' }}">
                    <a href="{{ route('estimates.index') }}"><i class="fe fe-file-text"></i><span>Sales Orders</span></a>
                </li>

                <li class="submenu {{ Request::is('invoices*') ? 'active subdrop' : '' }}">
                    <a href="#"><i class="fe fe-file"></i><span>Invoices</span><span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('invoices.index') }}">Invoices List</a></li>
                        <li><a href="{{ route('add-invoice') }}">Add Invoice</a></li>
                    </ul>
                </li>

                <li><a href="{{ route('sales.showPos') }}"><i class="fe fe-shopping-cart"></i><span>POS</span></a></li>

                <li><a href="{{ route('payments.index') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>

                <li class="menu-title"><span>Premium Features</span></li>

                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Suppliers</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Categories & Units</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Inventory Management</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Recurring Invoices</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Purchases</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Expenses</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Quotations</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>
                <li>
                    <a href="{{ Route::has('membership-plans') ? route('membership-plans') : url('/membership-plans') }}">
                        <i class="fe fe-lock"></i>
                        <span>Reports</span>
                        <span class="badge bg-warning">Pro</span>
                    </a>
                </li>

                <li class="menu-title"><span>Settings</span></li>

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
            window.location.href = '{{ route("membership-plans") }}';
        }
    });
}
</script>
@endif
