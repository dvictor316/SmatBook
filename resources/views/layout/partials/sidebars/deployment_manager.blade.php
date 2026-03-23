{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │     DEPLOYMENT MANAGER SIDEBAR WITH MOBILE HAMBURGER            │
    │     Professional, Responsive, Mobile-Ready                       │
    └─────────────────────────────────────────────────────────────────┘
--}}

@php
    $currentRoute = Route::currentRouteName();
    $manager = auth()->user();
    
    $deploymentStats = DB::table('deployment_managers')
        ->where('user_id', $manager->id)
        ->first();
    
    $managedCompanyIds = DB::table('deployment_companies')
        ->where('manager_id', $manager->id)
        ->pluck('company_id')
        ->toArray();

    $legacyCompanyIds = \App\Models\Company::where('deployed_by', $manager->id)
        ->pluck('id')
        ->toArray();

    $companyIds = collect(array_merge($managedCompanyIds, $legacyCompanyIds))
        ->unique()
        ->values();

    $deployedCompanies = $companyIds->count();
    $deploymentLimit = $deploymentStats->deployment_limit ?? 10;
    $commissionRate = $deploymentStats->commission_rate ?? 35;
    
    $totalUsers = \App\Models\User::whereIn('company_id', $companyIds->all())->count();
    
    $pendingCustomers = \App\Models\Company::whereIn('id', $companyIds->all())
        ->where('status', 'pending')
        ->count();
@endphp

<style>
    :root {
        --dm-primary: #1e40af;
        --dm-primary-light: #3b82f6;
        --dm-success: #10b981;
        --dm-warning: #f59e0b;
        --dm-danger: #ef4444;
        --dm-dark: #1f2937;
        --dm-gray: #6b7280;
        --dm-gray-light: #9ca3af;
        --dm-white: #ffffff;
        --dm-bg: #f9fafb;
        --dm-border: #e5e7eb;
        --dm-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        --dm-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    /* ============================================
       SIDEBAR CONTAINER
       ============================================ */
    .deployment-sidebar {
        position: fixed;
        left: 0;
        top: 70px;
        width: 270px;
        height: calc(100vh - 70px);
        background: var(--dm-white);
        z-index: 999;
        overflow-y: auto;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--dm-shadow-lg);
        border-right: 1px solid var(--dm-border);
    }

    .deployment-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .deployment-sidebar::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }

    /* MINI SIDEBAR */
    body.sidebar-collapsed .deployment-sidebar {
        width: 80px;
    }

    body.sidebar-collapsed .dm-brand-text,
    body.sidebar-collapsed .dm-menu-text,
    body.sidebar-collapsed .dm-stats-panel,
    body.sidebar-collapsed .dm-profile-details,
    body.sidebar-collapsed .dm-badge,
    body.sidebar-collapsed .dm-menu-arrow {
        opacity: 0;
        visibility: hidden;
    }

    body.sidebar-collapsed .dm-menu-icon {
        margin: 0 auto;
    }

    /* ============================================
       BRAND
       ============================================ */
    .dm-brand {
        padding: 20px;
        border-bottom: 1px solid var(--dm-border);
    }

    .dm-brand-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
    }

    .dm-brand-icon {
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, var(--dm-primary) 0%, var(--dm-primary-light) 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        flex-shrink: 0;
        overflow: hidden;
    }

    .dm-brand-icon img {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .dm-brand-text {
        transition: opacity 0.3s;
    }

    .dm-brand-title {
        font-size: 18px;
        font-weight: 800;
        color: var(--dm-dark);
        margin: 0;
        line-height: 1.2;
    }

    .dm-brand-title .book {
        color: #dc2626;
    }

    .dm-brand-subtitle {
        font-size: 10px;
        color: var(--dm-primary);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        margin: 0;
    }

    /* ============================================
       PROFILE
       ============================================ */
    .dm-profile {
        padding: 20px;
        border-bottom: 1px solid var(--dm-border);
        background: var(--dm-bg);
    }

    .dm-profile-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .dm-profile-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 18px;
        color: white;
        border: 3px solid #e0e7ff;
        flex-shrink: 0;
    }

    .dm-profile-details {
        flex: 1;
        min-width: 0;
        transition: opacity 0.3s;
    }

    .dm-profile-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--dm-dark);
        margin: 0 0 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dm-profile-role {
        font-size: 11px;
        color: var(--dm-primary);
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    /* ============================================
       STATS
       ============================================ */
    .dm-stats-panel {
        padding: 15px 20px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-bottom: 1px solid var(--dm-border);
        transition: opacity 0.3s;
    }

    .dm-stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }

    .dm-stat-item:not(:last-child) {
        border-bottom: 1px solid rgba(59, 130, 246, 0.15);
    }

    .dm-stat-label {
        font-size: 11px;
        color: var(--dm-gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .dm-stat-value {
        font-size: 14px;
        font-weight: 800;
        color: var(--dm-dark);
    }

    .dm-stat-value.success {
        color: var(--dm-success);
    }

    /* ============================================
       MENU
       ============================================ */
    .dm-nav {
        padding: 15px 0;
    }

    .dm-nav-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .dm-menu-item {
        position: relative;
    }

    .dm-menu-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        color: var(--dm-gray);
        text-decoration: none;
        transition: all 0.2s;
        font-size: 14px;
        font-weight: 600;
        position: relative;
    }

    .dm-menu-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--dm-primary);
        transform: scaleY(0);
        transition: transform 0.2s;
    }

    .dm-menu-link:hover {
        color: var(--dm-dark);
        background: var(--dm-bg);
    }

    .dm-menu-link:hover::before {
        transform: scaleY(1);
    }

    .dm-menu-link.active {
        color: var(--dm-primary);
        background: linear-gradient(90deg, #eff6ff 0%, transparent 100%);
        font-weight: 700;
    }

    .dm-menu-link.active::before {
        transform: scaleY(1);
    }

    .dm-menu-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: margin 0.3s;
        flex-shrink: 0;
    }

    .dm-menu-text {
        flex: 1;
        transition: opacity 0.3s;
    }

    .dm-badge {
        background: var(--dm-danger);
        color: white;
        font-size: 10px;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 10px;
        transition: opacity 0.3s;
    }

    .dm-badge.success { background: var(--dm-success); }
    .dm-badge.warning { background: var(--dm-warning); }
    .dm-badge.info { background: var(--dm-primary); }

    /* ============================================
       SUBMENU
       ============================================ */
    .dm-submenu {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background: var(--dm-bg);
    }

    .dm-menu-item.has-submenu.open .dm-submenu {
        max-height: 500px;
    }

    .dm-submenu-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 20px 10px 52px;
        color: var(--dm-gray);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .dm-submenu-link:hover {
        color: var(--dm-dark);
        background: white;
    }

    .dm-submenu-link.active {
        color: var(--dm-primary);
        background: white;
        font-weight: 600;
    }

    .dm-submenu-link i {
        width: 16px;
        text-align: center;
        font-size: 12px;
    }

    .dm-menu-arrow {
        margin-left: auto;
        transition: transform 0.3s, opacity 0.3s;
        font-size: 12px;
        color: var(--dm-gray-light);
    }

    .dm-menu-item.has-submenu.open .dm-menu-arrow {
        transform: rotate(90deg);
        color: var(--dm-primary);
    }

    /* ============================================
       FOOTER
       ============================================ */
    .dm-footer {
        padding: 20px;
        border-top: 1px solid var(--dm-border);
        margin-top: auto;
        background: var(--dm-bg);
    }

    .dm-footer-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        color: var(--dm-gray);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: color 0.2s;
    }

    .dm-footer-link:hover {
        color: var(--dm-dark);
    }

    .dm-footer-link i {
        width: 18px;
        text-align: center;
    }

    /* ============================================
       MOBILE HAMBURGER & OVERLAY
       ============================================ */
    .dm-hamburger {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1051;
        width: 45px;
        height: 45px;
        background: var(--dm-white);
        border: 2px solid var(--dm-primary);
        border-radius: 8px;
        cursor: pointer;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        box-shadow: var(--dm-shadow-lg);
        transition: all 0.3s;
    }

    .dm-hamburger:hover {
        background: var(--dm-primary);
    }

    .dm-hamburger span {
        width: 22px;
        height: 3px;
        background: var(--dm-primary);
        transition: all 0.3s;
        border-radius: 3px;
    }

    .dm-hamburger:hover span {
        background: white;
    }

    .dm-hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }

    .dm-hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .dm-hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    .dm-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 998;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .dm-overlay.active {
        display: block;
        opacity: 1;
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 992px) {
        .deployment-sidebar {
            transform: translateX(-100%);
            top: 0;
            height: 100vh;
            z-index: 1050;
        }

        .deployment-sidebar.mobile-open {
            transform: translateX(0);
        }

        .dm-hamburger {
            display: flex;
        }

        .dm-overlay.active {
            display: block;
        }
    }

    @media (max-width: 576px) {
        .deployment-sidebar {
            width: 280px;
        }

        .dm-brand {
            padding: 16px;
        }

        .dm-brand-logo {
            gap: 10px;
        }

        .dm-brand-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
        }

        .dm-brand-icon img {
            width: 23px;
            height: 23px;
        }

        .dm-brand-title {
            font-size: 15px;
        }

        .dm-brand-subtitle {
            font-size: 9px;
            letter-spacing: 0.7px;
        }

        .dm-hamburger {
            width: 40px;
            height: 40px;
            top: 12px;
            left: 12px;
        }

        .dm-hamburger span {
            width: 20px;
            height: 2.5px;
        }
    }
</style>

<!-- MOBILE HAMBURGER -->
<div class="dm-hamburger" id="dmHamburger" onclick="toggleMobileSidebar()">
    <span></span>
    <span></span>
    <span></span>
</div>

<!-- MOBILE OVERLAY -->
<div class="dm-overlay" id="dmOverlay" onclick="closeMobileSidebar()"></div>

<!-- DEPLOYMENT SIDEBAR -->
<aside class="deployment-sidebar" id="deploymentSidebar">
    
    <!-- BRAND -->
    <div class="dm-brand">
        <a href="{{ route('deployment.dashboard') }}" class="dm-brand-logo">
            <div class="dm-brand-icon">
                <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook">
            </div>
            <div class="dm-brand-text">
                <h1 class="dm-brand-title">SmartPro<span class="book">book</span></h1>
                <p class="dm-brand-subtitle">Deployment Hub</p>
            </div>
        </a>
    </div>

    <!-- PROFILE -->
    <div class="dm-profile">
        <div class="dm-profile-wrapper">
            <div class="dm-profile-avatar">
                {{ strtoupper(substr($manager->name ?? 'DM', 0, 1)) }}
            </div>
            <div class="dm-profile-details">
                <p class="dm-profile-name">{{ $manager->name ?? 'Manager' }}</p>
                <p class="dm-profile-role">Deployment Manager</p>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="dm-stats-panel">
        <div class="dm-stat-item">
            <span class="dm-stat-label">Deployed</span>
            <span class="dm-stat-value success">{{ $deployedCompanies }}/{{ $deploymentLimit }}</span>
        </div>
        <div class="dm-stat-item">
            <span class="dm-stat-label">Commission</span>
            <span class="dm-stat-value">{{ number_format($commissionRate, 0) }}%</span>
        </div>
    </div>

    <!-- NAVIGATION -->
    <nav class="dm-nav">
        <ul class="dm-nav-menu">
            
            <!-- Dashboard -->
            <li class="dm-menu-item">
                <a href="{{ route('deployment.dashboard') }}" class="dm-menu-link {{ request()->routeIs('deployment.dashboard') ? 'active' : '' }}">
                    <span class="dm-menu-icon"><i class="fas fa-th-large"></i></span>
                    <span class="dm-menu-text">Dashboard</span>
                </a>
            </li>

            <!-- Register Customer -->
            <li class="dm-menu-item">
                <a href="{{ route('deployment.users.create') }}" class="dm-menu-link {{ request()->routeIs('deployment.customers.create') || request()->routeIs('deployment.users.create') ? 'active' : '' }}">
                    <span class="dm-menu-icon"><i class="fas fa-user-plus"></i></span>
                    <span class="dm-menu-text">Register Customer</span>
                </a>
            </li>

            <!-- My Clients -->
            <li class="dm-menu-item has-submenu {{ request()->routeIs('deployment.companies.*') ? 'open' : '' }}">
                <a href="#" class="dm-menu-link {{ request()->routeIs('deployment.companies.*') ? 'active' : '' }}" onclick="toggleSubmenu(event, this)">
                    <span class="dm-menu-icon"><i class="fas fa-building"></i></span>
                    <span class="dm-menu-text">My Clients</span>
                    <span class="dm-badge info">{{ $deployedCompanies }}</span>
                    <span class="dm-menu-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <ul class="dm-submenu">
                    <li>
                        <a href="{{ route('deployment.companies.index') }}" class="dm-submenu-link {{ request()->routeIs('deployment.companies.index') ? 'active' : '' }}">
                            <i class="fas fa-list"></i> All Clients
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.companies.active') }}" class="dm-submenu-link {{ request()->routeIs('deployment.companies.active') ? 'active' : '' }}">
                            <i class="fas fa-check-circle"></i> Active
                        </a>
                    </li>
                    @if(Route::has('deployment.companies.pending'))
                    <li>
                        <a href="{{ route('deployment.companies.pending') }}" class="dm-submenu-link {{ request()->routeIs('deployment.companies.pending') ? 'active' : '' }}">
                            <i class="fas fa-clock"></i> Pending
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            <!-- Subscriptions -->
            <li class="dm-menu-item has-submenu {{ request()->routeIs('deployment.subscription.*') ? 'open' : '' }}">
                <a href="#" class="dm-menu-link {{ request()->routeIs('deployment.subscription.*') ? 'active' : '' }}" onclick="toggleSubmenu(event, this)">
                    <span class="dm-menu-icon"><i class="fas fa-sync-alt"></i></span>
                    <span class="dm-menu-text">Subscriptions</span>
                    @if($pendingCustomers > 0)
                    <span class="dm-badge warning">{{ $pendingCustomers }}</span>
                    @endif
                    <span class="dm-menu-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <ul class="dm-submenu">
                    <li>
                        <a href="{{ route('deployment.subscription.overview') }}" class="dm-submenu-link {{ request()->routeIs('deployment.subscription.overview') ? 'active' : '' }}">
                            <i class="fas fa-eye"></i> Overview
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.subscription.renewals') }}" class="dm-submenu-link {{ request()->routeIs('deployment.subscription.renewals') ? 'active' : '' }}">
                            <i class="fas fa-redo"></i> Renewals
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.subscription.expiring') }}" class="dm-submenu-link {{ request()->routeIs('deployment.subscription.expiring') ? 'active' : '' }}">
                            <i class="fas fa-exclamation-triangle"></i> Expiring Soon
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Users -->
            <li class="dm-menu-item">
                <a href="{{ route('deployment.users.index') }}" class="dm-menu-link {{ request()->routeIs('deployment.users.index') || request()->routeIs('deployment.users.view') ? 'active' : '' }}">
                    <span class="dm-menu-icon"><i class="fas fa-users"></i></span>
                    <span class="dm-menu-text">All Users</span>
                    <span class="dm-badge info">{{ $totalUsers }}</span>
                </a>
            </li>

            <li class="dm-menu-item has-submenu {{ request()->routeIs('deployment.invoices.*', 'deployment.payments.*') ? 'open' : '' }}">
                <a href="#" class="dm-menu-link {{ request()->routeIs('deployment.invoices.*', 'deployment.payments.*') ? 'active' : '' }}" onclick="toggleSubmenu(event, this)">
                    <span class="dm-menu-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <span class="dm-menu-text">Billing Desk</span>
                    <span class="dm-menu-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <ul class="dm-submenu">
                    <li>
                        <a href="{{ route('deployment.invoices.index') }}" class="dm-submenu-link {{ request()->routeIs('deployment.invoices.index', 'deployment.invoices.view', 'deployment.invoices.create') ? 'active' : '' }}">
                            <i class="fas fa-receipt"></i> Invoices
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.payments.index') }}" class="dm-submenu-link {{ request()->routeIs('deployment.payments.*') ? 'active' : '' }}">
                            <i class="fas fa-credit-card"></i> Payments
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Commissions -->
            <li class="dm-menu-item has-submenu {{ request()->routeIs('deployment.commissions.*') ? 'open' : '' }}">
                <a href="#" class="dm-menu-link {{ request()->routeIs('deployment.commissions.*') ? 'active' : '' }}" onclick="toggleSubmenu(event, this)">
                    <span class="dm-menu-icon"><i class="fas fa-dollar-sign"></i></span>
                    <span class="dm-menu-text">Commissions</span>
                    <span class="dm-menu-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <ul class="dm-submenu">
                    <li>
                        <a href="{{ route('deployment.commissions.index') }}" class="dm-submenu-link {{ request()->routeIs('deployment.commissions.index') ? 'active' : '' }}">
                            <i class="fas fa-list"></i> All Commissions
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.commissions.pending') }}" class="dm-submenu-link {{ request()->routeIs('deployment.commissions.pending') ? 'active' : '' }}">
                            <i class="fas fa-clock"></i> Pending
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.commissions.paid') }}" class="dm-submenu-link {{ request()->routeIs('deployment.commissions.paid') ? 'active' : '' }}">
                            <i class="fas fa-check"></i> Paid
                        </a>
                    </li>
                </ul>
            </li>

            <li class="dm-menu-item has-submenu {{ request()->routeIs('deployment.reports.*', 'deployment.stats') ? 'open' : '' }}">
                <a href="#" class="dm-menu-link {{ request()->routeIs('deployment.reports.*', 'deployment.stats') ? 'active' : '' }}" onclick="toggleSubmenu(event, this)">
                    <span class="dm-menu-icon"><i class="fas fa-chart-line"></i></span>
                    <span class="dm-menu-text">Reports</span>
                    <span class="dm-menu-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <ul class="dm-submenu">
                    <li>
                        <a href="{{ route('deployment.stats') }}" class="dm-submenu-link {{ request()->routeIs('deployment.stats') ? 'active' : '' }}">
                            <i class="fas fa-chart-area"></i> Analytics
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.reports.performance') }}" class="dm-submenu-link {{ request()->routeIs('deployment.reports.performance') ? 'active' : '' }}">
                            <i class="fas fa-tachometer-alt"></i> Performance
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.reports.client-activity') }}" class="dm-submenu-link {{ request()->routeIs('deployment.reports.client-activity') ? 'active' : '' }}">
                            <i class="fas fa-history"></i> Client Activity
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.reports.revenue') }}" class="dm-submenu-link {{ request()->routeIs('deployment.reports.revenue') ? 'active' : '' }}">
                            <i class="fas fa-coins"></i> Revenue
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.reports.custom') }}" class="dm-submenu-link {{ request()->routeIs('deployment.reports.custom') ? 'active' : '' }}">
                            <i class="fas fa-sliders-h"></i> Custom Summary
                        </a>
                    </li>
                </ul>
            </li>

            <li class="dm-menu-item has-submenu {{ request()->routeIs('deployment.notifications', 'deployment.support.*', 'deployment.help*') ? 'open' : '' }}">
                <a href="#" class="dm-menu-link {{ request()->routeIs('deployment.notifications', 'deployment.support.*', 'deployment.help*') ? 'active' : '' }}" onclick="toggleSubmenu(event, this)">
                    <span class="dm-menu-icon"><i class="fas fa-life-ring"></i></span>
                    <span class="dm-menu-text">Support Hub</span>
                    <span class="dm-menu-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <ul class="dm-submenu">
                    <li>
                        <a href="{{ route('deployment.notifications') }}" class="dm-submenu-link {{ request()->routeIs('deployment.notifications') ? 'active' : '' }}">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.support.tickets') }}" class="dm-submenu-link {{ request()->routeIs('deployment.support.tickets', 'deployment.support.view-ticket') ? 'active' : '' }}">
                            <i class="fas fa-ticket-alt"></i> Tickets
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.support.create-ticket') }}" class="dm-submenu-link {{ request()->routeIs('deployment.support.create-ticket') ? 'active' : '' }}">
                            <i class="fas fa-plus-circle"></i> New Ticket
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.help') }}" class="dm-submenu-link {{ request()->routeIs('deployment.help') || request()->routeIs('deployment.help.*') ? 'active' : '' }}">
                            <i class="fas fa-book-open"></i> Help Center
                        </a>
                    </li>
                </ul>
            </li>

            <li class="dm-menu-item has-submenu {{ request()->routeIs('deployment.profile', 'deployment.settings', 'deployment.settings.*') ? 'open' : '' }}">
                <a href="#" class="dm-menu-link {{ request()->routeIs('deployment.profile', 'deployment.settings', 'deployment.settings.*') ? 'active' : '' }}" onclick="toggleSubmenu(event, this)">
                    <span class="dm-menu-icon"><i class="fas fa-user-cog"></i></span>
                    <span class="dm-menu-text">My Account</span>
                    <span class="dm-menu-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <ul class="dm-submenu">
                    <li>
                        <a href="{{ route('deployment.profile') }}" class="dm-submenu-link {{ request()->routeIs('deployment.profile') ? 'active' : '' }}">
                            <i class="fas fa-id-badge"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('deployment.settings') }}" class="dm-submenu-link {{ request()->routeIs('deployment.settings', 'deployment.settings.*') ? 'active' : '' }}">
                            <i class="fas fa-cogs"></i> Settings
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </nav>

    <!-- FOOTER -->
    <div class="dm-footer">
        <a href="{{ route('logout') }}" class="dm-footer-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>

</aside>

<script>
    // Toggle submenu
    function toggleSubmenu(event, element) {
        event.preventDefault();
        const menuItem = element.closest('.dm-menu-item');
        
        // Close other submenus
        document.querySelectorAll('.dm-menu-item.has-submenu').forEach(item => {
            if (item !== menuItem) {
                item.classList.remove('open');
            }
        });
        
        menuItem.classList.toggle('open');
    }

    // Toggle mobile sidebar
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('deploymentSidebar');
        const hamburger = document.getElementById('dmHamburger');
        const overlay = document.getElementById('dmOverlay');
        
        sidebar.classList.toggle('mobile-open');
        hamburger.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    // Close mobile sidebar
    function closeMobileSidebar() {
        const sidebar = document.getElementById('deploymentSidebar');
        const hamburger = document.getElementById('dmHamburger');
        const overlay = document.getElementById('dmOverlay');
        
        sidebar.classList.remove('mobile-open');
        hamburger.classList.remove('active');
        overlay.classList.remove('active');
    }

    // Auto-open submenu if active route
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.dm-submenu-link.active').forEach(link => {
            const menuItem = link.closest('.dm-menu-item');
            if (menuItem) {
                menuItem.classList.add('open');
            }
        });

        // Close mobile sidebar when clicking on a link
        document.querySelectorAll('.dm-menu-link, .dm-submenu-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    setTimeout(closeMobileSidebar, 300);
                }
            });
        });
    });

    // Handle resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeMobileSidebar();
        }
    });
</script>
