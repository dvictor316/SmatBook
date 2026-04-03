@php
    $user = Auth::user();
    $notifications = [];
    $unreadNotificationCount = 0;

    if ($user && Schema::hasTable('notifications')) {
        $notifications = \Illuminate\Support\Facades\Cache::remember(
            'ui:header:notifications:' . $user->id,
            now()->addSeconds(30),
            function () use ($user) {
                return DB::table('notifications')
                    ->where('notifiable_id', $user->id)
                    ->where('notifiable_type', 'App\\Models\\User')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get();
            }
        );

        $unreadNotificationCount = \Illuminate\Support\Facades\Cache::remember(
            'ui:header:notifications:count:' . $user->id,
            now()->addSeconds(30),
            function () use ($user) {
                return (int) DB::table('notifications')
                    ->where('notifiable_id', $user->id)
                    ->where('notifiable_type', 'App\\Models\\User')
                    ->whereNull('read_at')
                    ->count();
            }
        );
    }

    $defaultAvatar    = asset('assets/img/profiles/avatar-07.jpg');
    $profileImagePath = $user?->avatar_url ?: $defaultAvatar;

    $currentSubdomain = request()->route('subdomain')
        ?? optional($user?->company)->subdomain
        ?? 'admin';
    $routeParams = ['subdomain' => $currentSubdomain];
    $headerLogoUrl = asset('assets/img/logos.png');
    $isWorkspaceSwitcherVisible = $user && (
        in_array(strtolower((string) ($user->role ?? '')), ['super_admin', 'superadmin'], true)
        || $user->email === 'donvictorlive@gmail.com'
    );
    $workspaceContext = session('workspace_context', 'platform');
    $isBusinessWorkspace = $workspaceContext === 'business';
    $headerHomeUrl = $isWorkspaceSwitcherVisible
        ? ($isBusinessWorkspace && Route::has('workspace.business.dashboard')
            ? route('workspace.business.dashboard')
            : route('super_admin.dashboard', $routeParams))
        : route('home');
    $headerBranchOptions = collect();
    $activeBranchId = session('active_branch_id');
    $activeBranchName = session('active_branch_name');

    if ($user && !empty($user->company_id)) {
        $branchKey = 'branches_json_company_' . $user->company_id;
        $headerBranchOptions = collect(json_decode((string) \App\Models\Setting::where('key', $branchKey)->value('value'), true) ?: [])
            ->filter(fn ($branch) => !empty($branch['id']) && !empty($branch['name']))
            ->values();

        if (!$activeBranchName && $headerBranchOptions->isNotEmpty()) {
            $matchedBranch = $headerBranchOptions->firstWhere('id', $activeBranchId) ?: $headerBranchOptions->first();
            $activeBranchId = $matchedBranch['id'] ?? null;
            $activeBranchName = $matchedBranch['name'] ?? null;
        }
    }

    $headerSearchPlaceholder = 'Search customers, invoices, products...';

    if (request()->routeIs('customers.*') || request()->is('customers*', 'vendors*')) {
        $headerSearchPlaceholder = 'Search customers, vendors, ledgers...';
    } elseif (request()->routeIs('invoices.*') || request()->routeIs('sales.*') || request()->is('pos*')) {
        $headerSearchPlaceholder = 'Search invoices, POS, receipts, customers...';
    } elseif (request()->routeIs('products.*') || request()->is('products*', 'inventory*')) {
        $headerSearchPlaceholder = 'Search products, inventory, stock, shelves...';
    } elseif (request()->routeIs('purchases.*') || request()->routeIs('expenses.*')) {
        $headerSearchPlaceholder = 'Search purchases, expenses, vendors...';
    } elseif (request()->routeIs('quotations*') || request()->is('quotations*', 'add-quotations*', 'edit-quotations*')) {
        $headerSearchPlaceholder = 'Search quotations, proposals, customers...';
    } elseif (request()->routeIs('payroll.*') || request()->is('payroll*')) {
        $headerSearchPlaceholder = 'Search payroll, staff, salary runs...';
    } elseif (request()->routeIs('notifications.*')) {
        $headerSearchPlaceholder = 'Search notifications, alerts, updates...';
    } elseif (request()->routeIs('settings*') || request()->is('settings*')) {
        $headerSearchPlaceholder = 'Search settings, company profile, templates...';
    }

    $headerSearchItems = array_values(array_filter([
        [
            'title' => 'Dashboard',
            'subtitle' => 'Workspace overview and analytics',
            'url' => url('/'),
            'icon' => 'fa-th-large',
            'keywords' => ['dashboard', 'home', 'overview', 'analytics'],
        ],
        Route::has('customers.index') ? [
            'title' => 'Customers',
            'subtitle' => 'Customer list and relationships',
            'url' => route('customers.index'),
            'icon' => 'fa-users',
            'keywords' => ['customers', 'clients', 'accounts', 'crm'],
        ] : null,
        Route::has('vendors.index') ? [
            'title' => 'Vendors',
            'subtitle' => 'Suppliers and vendor ledger',
            'url' => route('vendors.index'),
            'icon' => 'fa-truck',
            'keywords' => ['vendors', 'suppliers', 'procurement'],
        ] : null,
        Route::has('suppliers.index') ? [
            'title' => 'Suppliers',
            'subtitle' => 'Supplier directory and procurement contacts',
            'url' => route('suppliers.index'),
            'icon' => 'fa-warehouse',
            'keywords' => ['suppliers', 'procurement', 'vendors'],
        ] : null,
        Route::has('products.index') ? [
            'title' => 'Products',
            'subtitle' => 'Products, stock, and shelves',
            'url' => route('products.index'),
            'icon' => 'fa-box-open',
            'keywords' => ['products', 'inventory', 'stock', 'items'],
        ] : null,
        Route::has('invoices.index') ? [
            'title' => 'Invoices',
            'subtitle' => 'Invoice list and receivables',
            'url' => route('invoices.index'),
            'icon' => 'fa-file-invoice',
            'keywords' => ['invoices', 'billing', 'receipts', 'sales'],
        ] : null,
        Route::has('sales.showPos') ? [
            'title' => 'POS',
            'subtitle' => 'Point of sale terminal',
            'url' => route('sales.showPos'),
            'icon' => 'fa-cash-register',
            'keywords' => ['pos', 'terminal', 'checkout', 'cashier'],
        ] : null,
        Route::has('purchases.index') ? [
            'title' => 'Purchases',
            'subtitle' => 'Purchase orders and procurement',
            'url' => route('purchases.index'),
            'icon' => 'fa-shopping-bag',
            'keywords' => ['purchases', 'procurement', 'orders', 'supply'],
        ] : null,
        Route::has('expenses.index') ? [
            'title' => 'Expenses',
            'subtitle' => 'Expense tracking and payments',
            'url' => route('expenses.index'),
            'icon' => 'fa-receipt',
            'keywords' => ['expenses', 'costs', 'spend', 'payments'],
        ] : null,
        Route::has('quotations') ? [
            'title' => 'Quotations',
            'subtitle' => 'Quotes and proposals',
            'url' => route('quotations'),
            'icon' => 'fa-file-signature',
            'keywords' => ['quotations', 'quotes', 'proposals'],
        ] : null,
        Route::has('payroll.index') ? [
            'title' => 'Payroll',
            'subtitle' => 'Salary runs and staff payments',
            'url' => route('payroll.index'),
            'icon' => 'fa-money-check-alt',
            'keywords' => ['payroll', 'salary', 'staff', 'wages'],
        ] : null,
        Route::has('notifications.index') ? [
            'title' => 'Notifications',
            'subtitle' => 'System alerts and updates',
            'url' => route('notifications.index'),
            'icon' => 'fa-bell',
            'keywords' => ['notifications', 'alerts', 'updates', 'messages'],
        ] : null,
        Route::has('settings') ? [
            'title' => 'Settings',
            'subtitle' => 'Workspace and company configuration',
            'url' => route('settings'),
            'icon' => 'fa-cog',
            'keywords' => ['settings', 'configuration', 'company', 'profile'],
        ] : [
            'title' => 'Settings',
            'subtitle' => 'Workspace and company configuration',
            'url' => url('/settings'),
            'icon' => 'fa-cog',
            'keywords' => ['settings', 'configuration', 'company', 'profile'],
        ],
    ]));
@endphp

<style>
    /* ============================================
       HEADER
       ============================================ */
    .header {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding: 0 20px;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        height: 76px;
        position: sticky;
        top: 0;
        z-index: 1040;
        gap: 0;
        margin-bottom: 14px;
    }

    .header-logo {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        width: 270px;
        flex-shrink: 0;
        order: 1;
        gap: 8px;
        min-width: 0;
        flex: 1 1 auto;
    }
    .header-logo a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 0;
        flex-shrink: 0;
    }

    .header-logo img { height: 56px; width: auto; }
    .spb-wordmark {
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -0.3px;
        line-height: 1;
        color: #0b2a63;
        white-space: nowrap;
        flex: 0 1 auto;
    }
    .spb-wordmark .book { color: #dc2626; }

    /* ── Mobile Hamburger ── */
    #mobile_btn {
        display: none;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        color: #64748b;
        font-size: 22px;
        cursor: pointer;
        margin-right: 12px;
        background: none;
        border: none;
        padding: 0;
        border-radius: 8px;
        transition: background 0.2s;
        flex-shrink: 0;
        position: relative;
        z-index: 1042;
        align-self: center;
        top: 0;
    }
    #mobile_btn:hover { background: #f1f5f9; }

    /* ── Desktop Sidebar Toggle ── */
    .header-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        color: #64748b;
        flex-shrink: 0;
        cursor: pointer;
        text-decoration: none;
        margin-left: 0;
        margin-right: 14px;
        transform: none;
        transition: all 0.2s;
        order: 2;
        position: relative;
        top: 0;
    }
    /* Override theme #toggle_btn spacing so the icon sits near sidebar edge line */
    #toggle_btn.header-toggle {
        width: 40px !important;
        height: 40px !important;
        margin: 0 !important;
        font-size: inherit !important;
        position: absolute !important;
        left: 282px !important;
        top: calc(50% + 8px) !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1041;
    }
    body.sidebar-collapsed #toggle_btn.header-toggle,
    body.mini-sidebar #toggle_btn.header-toggle {
        left: 96px !important;
    }
    .header-toggle:hover { background: #f1f5f9; color: #1e293b; }

    .toggle-bars {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 22px;
        height: 18px;
    }

    .bar-icon {
        width: 100%;
        height: 2.5px;
        background: currentColor;
        border-radius: 3px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: center;
        display: block;
    }

    body.sidebar-collapsed #toggle_btn .bar-icon:nth-child(1) { transform: translateY(7.5px) rotate(45deg); }
    body.sidebar-collapsed #toggle_btn .bar-icon:nth-child(2) { opacity: 0; transform: scaleX(0); }
    body.sidebar-collapsed #toggle_btn .bar-icon:nth-child(3) { transform: translateY(-7.5px) rotate(-45deg); }

    #mobile_btn.is-open .bar-icon:nth-child(1) { transform: translateY(7.5px) rotate(45deg); }
    #mobile_btn.is-open .bar-icon:nth-child(2) { opacity: 0; transform: scaleX(0); }
    #mobile_btn.is-open .bar-icon:nth-child(3) { transform: translateY(-7.5px) rotate(-45deg); }

    /* ── Search ── */
    .header-search-container {
        flex: 1;
        display: flex;
        justify-content: center;
        max-width: 600px;
        margin: 0 auto;
        order: 3;
    }

    .header-search {
        position: relative;
        width: 100%;
        max-width: 450px;
    }

    .header-search input {
        width: 100%;
        padding: 10px 40px 10px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        background: #f8fafc;
        color: #64748b;
        transition: all 0.3s;
    }
    .header-search input:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }

    .header-search .search-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    .search-results {
        position: absolute;
        top: calc(100% + 8px);
        left: 0; right: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        max-height: 400px;
        overflow-y: auto;
        display: none;
        z-index: 1100;
    }
    .search-results.show { display: block; }
    .search-result-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 14px;
        text-decoration: none;
        color: #0f172a;
        border-bottom: 1px solid #eef2f7;
        transition: background 0.2s ease;
    }
    .search-result-item:last-child { border-bottom: 0; }
    .search-result-item:hover {
        background: #f8fbff;
        color: #0b2a63;
    }
    .search-result-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(59, 130, 246, 0.1);
        color: #2f56ff;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .search-result-body {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .search-result-title {
        font-size: 13px;
        font-weight: 700;
        color: inherit;
        line-height: 1.3;
    }
    .search-result-subtitle {
        font-size: 11px;
        color: #64748b;
        line-height: 1.35;
    }
    .search-no-results {
        padding: 16px;
        text-align: center;
        font-size: 12px;
        color: #64748b;
    }

    /* ── Right Actions ── */
    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
        margin-left: 20px;
        order: 4;
    }
    .header-actions > * {
        flex-shrink: 0;
    }

    .workspace-switcher {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px;
        border-radius: 999px;
        background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
        border: 1px solid #c7d2fe;
    }

    .workspace-switcher a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        text-decoration: none;
        color: #475569;
    }

    .workspace-switcher a.is-active {
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        color: #fff;
        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22);
    }

    .branch-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 0 12px;
        border-radius: 999px;
        text-decoration: none;
        border: 1px solid #dbe7ff;
        background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
        color: #1e293b;
        font-size: 12px;
        font-weight: 700;
    }

    .branch-pill small {
        display: block;
        font-size: 9px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        line-height: 1.1;
    }

    .country-selector {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        color: #64748b;
        font-size: 13px;
        text-decoration: none;
    }
    .country-selector img { height: 18px; width: 27px; border-radius: 3px; }
    .country-currency {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.2px;
    }

    .notification-bell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        color: #64748b;
        font-size: 19px;
    }
    .notification-bell .badge {
        position: absolute;
        top: 7px; right: 7px;
        font-size: 9px;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .user-profile {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 6px 12px 6px 6px;
        border-radius: 8px;
        color: inherit;
        text-decoration: none;
    }
    .user-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        border: 2px solid #e2e8f0;
        object-fit: cover;
    }
    .user-info  { display: flex; flex-direction: column; }
    .user-role  { font-size: 9px; color: #94a3b8; font-weight: 700; text-transform: uppercase; }
    .user-name  { font-size: 13px; font-weight: 600; color: #1e293b; }

    .mobile-search-btn {
        display: none;
        width: 40px; height: 40px;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 17px;
        cursor: pointer;
        background: none;
        border: none;
    }

    /* ============================================
       MOBILE SIDEBAR OVERLAY + SLIDE-IN
       ============================================ */

    /* Dark overlay behind the open sidebar */
    #sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1035;
        transition: opacity 0.3s;
    }
    #sidebar-overlay.active { display: block; }

    /* On mobile: sidebar starts off-screen left, slides in when .mobile-open */
    @media (max-width: 991.98px) {

        /* ── Header layout ── */
        .header-logo    { width: auto; margin-right: auto; max-width: 196px; }
        .header-toggle  { display: none; }
        #mobile_btn     { display: flex; }
        .header-search-container { display: none; }
        .mobile-search-btn       { display: flex; }
        .user-info, .country-name { display: none; }
        .country-currency { display: none; }
        .header-actions { margin-left: auto; gap: 4px; }
        .workspace-switcher { display: none; }
        .branch-pill { display: none; }
        .country-selector {
            padding: 4px 6px;
            gap: 3px;
        }
        .country-selector img {
            height: 16px;
            width: 24px;
        }
        .notification-bell,
        .mobile-search-btn {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }
        .user-profile {
            gap: 0;
            padding: 1px;
            min-width: 32px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
        }

        /* ── Regular sidebar (#sidebar) ── */
        #sidebar {
            position: fixed !important;
            top: 70px !important;
            left: -280px !important;   /* off-screen by default */
            margin-left: 0 !important; /* cancel theme mobile -575px offset */
            width: 280px !important;
            height: calc(100vh - 70px) !important;
            z-index: 1045 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: none;
            transform: none !important;
        }

        #sidebar.mobile-open {
            left: 0 !important;
            box-shadow: 4px 0 24px rgba(0,0,0,0.18) !important;
        }

        /* ── Deployment manager sidebar (#deploymentSidebar) ── */
        #deploymentSidebar,
        .deployment-sidebar {
            position: fixed !important;
            top: 70px !important;
            left: -280px !important;
            margin-left: 0 !important;
            width: 280px !important;
            height: calc(100vh - 70px) !important;
            z-index: 1045 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: none;
            transform: none !important;
        }

        #deploymentSidebar.mobile-open,
        .deployment-sidebar.mobile-open {
            left: 0 !important;
            box-shadow: 4px 0 24px rgba(0,0,0,0.18) !important;
        }

        /* Prevent body scroll when sidebar open */
        body.sidebar-mobile-open {
            overflow: hidden;
        }

        /* Reset the body-level sidebar-icon-only that desktop uses */
        body.sidebar-icon-only #sidebar,
        body.sidebar-icon-only #deploymentSidebar,
        body.sidebar-icon-only .deployment-sidebar {
            left: -280px !important;
        }
        body.sidebar-icon-only #sidebar.mobile-open,
        body.sidebar-icon-only #deploymentSidebar.mobile-open,
        body.sidebar-icon-only .deployment-sidebar.mobile-open {
            left: 0 !important;
        }
    }

    /* Mobile search overlay */
    .mobile-search-overlay {
        position: fixed;
        top: 70px; left: 0; right: 0;
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 15px 20px;
        z-index: 1050;
        display: none;
    }
    .mobile-search-overlay.active { display: block; }

    /* Desktop collapsed state */
    body.sidebar-collapsed .header-logo { width: 80px; }
    body.sidebar-collapsed .spb-wordmark,
    body.mini-sidebar .spb-wordmark { display: none; }

    @media (max-width: 991px) {
        .header {
            padding: 0 9px;
            margin-bottom: 10px;
            height: 70px;
        }
        .header-logo {
            gap: 7px;
            min-width: 0;
        }
        .header-logo img { height: 42px; }
        .spb-wordmark {
            font-size: 0.96rem;
            letter-spacing: -0.24px;
            white-space: nowrap;
            line-height: 1;
        }
        #mobile_btn {
            top: 7px;
        }
    }

    @media (max-width: 767px) {
        .header {
            padding: 0 8px;
            height: 68px;
        }
        .header-logo {
            max-width: 172px;
            gap: 6px;
        }
        .header-logo img { height: 38px; }
        .spb-wordmark {
            font-size: 0.88rem;
            letter-spacing: -0.22px;
        }
        .header-actions {
            gap: 3px;
            margin-left: 3px;
        }
        .country-selector {
            padding: 3px 5px;
        }
        .country-selector img {
            height: 15px;
            width: 22px;
        }
        .notification-bell,
        .mobile-search-btn,
        .user-avatar {
            width: 31px;
            height: 31px;
        }
        .user-profile {
            min-width: 31px;
        }
    }

    @media (max-width: 480px) {
        .header {
            padding: 0 8px;
        }
        .header-logo {
            gap: 5px;
            width: auto;
            max-width: 154px;
        }
        .header-logo img { height: 34px; }
        .spb-wordmark {
            display: block;
            font-size: 0.78rem;
            letter-spacing: -0.18px;
            overflow: visible;
            text-overflow: clip;
            max-width: none;
        }
        .header-actions {
            gap: 2px;
            margin-left: 3px;
        }
        #mobile_btn {
            width: 34px;
            height: 34px;
            margin-right: 4px;
            top: 7px;
        }
        .country-selector {
            padding: 3px 4px;
        }
        .country-selector img {
            height: 14px;
            width: 20px;
        }
        .notification-bell,
        .mobile-search-btn,
        .user-avatar {
            width: 29px;
            height: 29px;
        }
        .notification-bell,
        .mobile-search-btn {
            font-size: 13px;
        }
        .user-profile {
            min-width: 29px;
            padding: 1px;
        }
    }

    @media (max-width: 430px) {
        .header {
            padding: 0 7px;
            height: 66px;
        }
        .header-logo {
            max-width: 144px;
            gap: 4px;
        }
        .header-logo img { height: 31px; }
        .spb-wordmark {
            font-size: 0.69rem;
            letter-spacing: -0.16px;
        }
        .header-actions {
            gap: 1px;
            margin-left: 2px;
        }
        #mobile_btn {
            width: 32px;
            height: 32px;
            margin-right: 3px;
            top: 6px;
        }
        .notification-bell,
        .mobile-search-btn,
        .user-avatar {
            width: 28px;
            height: 28px;
        }
        .notification-bell,
        .mobile-search-btn {
            font-size: 12px;
        }
        .user-profile {
            min-width: 28px;
        }
    }

    @media (max-width: 389px) {
        .header-logo {
            max-width: 124px;
            gap: 3px;
        }
        .header-logo img { height: 28px; }
        .spb-wordmark {
            font-size: 0.6rem;
            letter-spacing: -0.1px;
        }
        .country-selector {
            padding: 3px;
        }
        .country-selector img {
            height: 13px;
            width: 18px;
        }
        .mobile-search-btn {
            display: none;
        }
    }

    @media (max-width: 360px) {
        .header {
            padding: 0 6px;
        }
        .header-logo {
            gap: 2px;
            max-width: 132px;
        }
        .header-logo img { height: 29px; }
        .spb-wordmark {
            font-size: 0.62rem;
            letter-spacing: -0.1px;
        }
        .header-actions {
            gap: 1px;
            margin-left: 2px;
        }
        #mobile_btn {
            top: 5px;
        }
        .country-selector {
            padding: 3px 4px;
        }
        .country-selector img {
            height: 12px;
            width: 17px;
        }
        .mobile-search-btn {
            display: none;
        }
        .notification-bell,
        .user-avatar {
            width: 28px;
            height: 28px;
        }
        .user-profile {
            min-width: 28px;
        }
    }

    @media print { .header { display: none !important; } }
</style>

{{-- ── Sidebar Overlay (tap to close) ── --}}
<div id="sidebar-overlay"></div>

<div class="header d-print-none">

    {{-- 1. Mobile Hamburger --}}
    <button id="mobile_btn" aria-label="Open menu" aria-expanded="false">
        <div class="toggle-bars">
            <span class="bar-icon"></span>
            <span class="bar-icon"></span>
            <span class="bar-icon"></span>
        </div>
    </button>

    {{-- 2. Logo --}}
    <div class="header-logo">
        <a href="{{ $headerHomeUrl }}">
            <img src="{{ $headerLogoUrl }}" alt="Logo">
        </a>
        <span class="spb-wordmark">SmartPro<span class="book">book</span></span>
    </div>

    {{-- 3. Desktop Toggle --}}
    <a href="javascript:void(0);" id="toggle_btn" class="header-toggle">
        <span class="toggle-bars">
            <span class="bar-icon"></span>
            <span class="bar-icon"></span>
            <span class="bar-icon"></span>
        </span>
    </a>

    {{-- 4. Search --}}
    <div class="header-search-container">
        <div class="header-search">
            <input type="text" id="globalSearch"
                placeholder="{{ $headerSearchPlaceholder }}"
                autocomplete="off">
            <i class="fas fa-search search-icon"></i>
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>

    {{-- 5. Right Actions --}}
    <div class="header-actions">
        @if($isWorkspaceSwitcherVisible)
            <div class="workspace-switcher">
                <a href="{{ route('workspace.platform') }}" class="{{ !$isBusinessWorkspace ? 'is-active' : '' }}">Partnership</a>
                <a href="{{ Route::has('workspace.business.dashboard') ? route('workspace.business.dashboard') : route('workspace.business') }}" class="{{ $isBusinessWorkspace ? 'is-active' : '' }}">Business</a>
            </div>
        @endif

        @if($headerBranchOptions->isNotEmpty())
            <div class="dropdown">
                <a href="#" class="branch-pill" data-bs-toggle="dropdown">
                    <i class="fe fe-git-branch"></i>
                    <span>
                        <small>Active Branch</small>
                        {{ $activeBranchName ?: 'Select Branch' }}
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    @foreach($headerBranchOptions as $branch)
                        <form method="POST" action="{{ route('settings.branches.activate') }}">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $branch['id'] }}">
                            <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center">
                                <span>{{ $branch['name'] }}</span>
                                @if(($activeBranchId ?? null) === ($branch['id'] ?? null))
                                    <i class="fe fe-check text-success"></i>
                                @endif
                            </button>
                        </form>
                    @endforeach
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('branches.index') }}" class="dropdown-item">Manage Branches</a>
                </div>
            </div>
        @endif

        <button class="mobile-search-btn" id="mobileSearchToggle" aria-label="Search">
            <i class="fas fa-search"></i>
        </button>

        {{-- Language --}}
        <div class="dropdown">
            <a href="#" class="country-selector" data-bs-toggle="dropdown" id="geoCountryToggle">
                <img id="geoCountryFlag" src="{{ asset('assets/img/flags/ng.png') }}" alt="NG" width="20" height="14">
                <span class="country-name" id="geoCountryCode">NG</span>
                <span class="country-currency" id="geoCurrencyCode">{{ $geoCurrency ?? 'NGN' }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" id="geoCountryMenu">
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="NG"><img class="me-2" src="{{ asset('assets/img/flags/ng.png') }}" alt="NG" width="18" height="12">Nigeria (NGN)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="US"><img class="me-2" src="{{ asset('assets/img/flags/us.png') }}" alt="US" width="18" height="12">United States (USD)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="CN"><img class="me-2" src="{{ asset('assets/img/flags/cn.png') }}" alt="CN" width="18" height="12">China (CNY)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="GB"><img class="me-2" src="{{ asset('assets/img/flags/gb.png') }}" alt="GB" width="18" height="12">United Kingdom (GBP)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="EU"><img class="me-2" src="{{ asset('assets/img/flags/eu.svg') }}" alt="EU" width="18" height="12">Europe (EUR)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="CA"><img class="me-2" src="{{ asset('assets/img/flags/ca.png') }}" alt="CA" width="18" height="12">Canada (CAD)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="IN"><img class="me-2" src="{{ asset('assets/img/flags/in.png') }}" alt="IN" width="18" height="12">India (INR)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="AE"><img class="me-2" src="{{ asset('assets/img/flags/ae.png') }}" alt="AE" width="18" height="12">UAE (AED)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="ZA"><img class="me-2" src="{{ asset('assets/img/flags/za.png') }}" alt="ZA" width="18" height="12">South Africa (ZAR)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="KE"><img class="me-2" src="{{ asset('assets/img/flags/ke.png') }}" alt="KE" width="18" height="12">Kenya (KES)</a>
                <a href="javascript:void(0);" class="dropdown-item geo-country-item" data-country="GH"><img class="me-2" src="{{ asset('assets/img/flags/gh.png') }}" alt="GH" width="18" height="12">Ghana (GHS)</a>
            </div>
        </div>

        {{-- Notifications --}}
        <div class="dropdown">
            <a href="#" class="notification-bell" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                @if($unreadNotificationCount > 0)
                    <span class="badge rounded-pill bg-danger">{{ $unreadNotificationCount }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="min-width:320px">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Notifications</span>
                    <a href="javascript:void(0)" class="small text-primary text-decoration-none" id="markAllNotificationsRead">
                        Mark all as read
                    </a>
                </div>
                <div style="max-height: 300px;overflow-y:auto">
                    @forelse($notifications as $notification)
                        @php $data = json_decode($notification->data, true); @endphp
                        <a href="{{ route('notifications.index') }}" class="dropdown-item p-3 border-bottom header-notification-item" data-notification-id="{{ $notification->id }}">
                            <div class="small text-wrap">
                                {{ $data['message'] ?? 'New system update available' }}
                            </div>
                            <div class="text-muted" style="font-size:10px">
                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-center text-muted">No new notifications</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- User Profile --}}
        @auth
        <div class="dropdown">
            <a href="#" class="user-profile" data-bs-toggle="dropdown">
                <img src="{{ $profileImagePath }}" alt="{{ $user->name }}" class="user-avatar" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';">
                <div class="user-info">
                    <div class="user-role">{{ $user->role ?? 'Staff' }}</div>
                    <div class="user-name">{{ $user->name }}</div>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="{{ url('profile') }}">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
                <a class="dropdown-item" href="{{ url('settings') }}">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="javascript:void(0);"
                    onclick="document.getElementById('logout-form-header').submit();">
                    <i class="fas fa-sign-out-alt me-2"></i> Log Out
                </a>
            </div>
        </div>
        @endauth

    </div>
</div>

{{-- Mobile Search Overlay --}}
<div class="mobile-search-overlay" id="mobileSearchOverlay">
    <div class="header-search" style="max-width:100%">
        <input type="text" id="mobileGlobalSearch" placeholder="{{ $headerSearchPlaceholder }}" autocomplete="off">
        <i class="fas fa-search search-icon"></i>
        <div class="search-results" id="mobileSearchResults"></div>
    </div>
</div>

<form id="logout-form-header" action="{{ route('emergency.logout') }}" method="POST" class="d-none">
    @csrf
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const geoFlag = document.getElementById('geoCountryFlag');
    const geoCode = document.getElementById('geoCountryCode');
    const geoCurrencyCode = document.getElementById('geoCurrencyCode');
    const geoItems = document.querySelectorAll('.geo-country-item');
    const geoFlags = {
        NG: @json(asset('assets/img/flags/ng.png')),
        US: @json(asset('assets/img/flags/us.png')),
        CN: @json(asset('assets/img/flags/cn.png')),
        GB: @json(asset('assets/img/flags/gb.png')),
        EU: @json(asset('assets/img/flags/eu.svg')),
        CA: @json(asset('assets/img/flags/ca.png')),
        IN: @json(asset('assets/img/flags/in.png')),
        AE: @json(asset('assets/img/flags/ae.png')),
        ZA: @json(asset('assets/img/flags/za.png')),
        KE: @json(asset('assets/img/flags/ke.png')),
        GH: @json(asset('assets/img/flags/gh.png'))
    };
    const geoCurrencies = {
        NG: 'NGN',
        US: 'USD',
        CN: 'CNY',
        GB: 'GBP',
        EU: 'EUR',
        CA: 'CAD',
        IN: 'INR',
        AE: 'AED',
        ZA: 'ZAR',
        KE: 'KES',
        GH: 'GHS'
    };

    const euRegions = ['FR', 'DE', 'ES', 'IT', 'PT', 'NL', 'BE', 'AT', 'IE', 'FI', 'SE', 'DK', 'PL', 'CZ', 'GR', 'RO', 'HU'];
    const normalizeGeoCountry = (rawCode) => {
        const code = String(rawCode || '').toUpperCase();
        if (euRegions.includes(code)) return 'EU';
        return geoFlags[code] ? code : 'NG';
    };

    const localeCountry = () => {
        try {
            const locale = Intl.DateTimeFormat().resolvedOptions().locale || navigator.language || 'en-NG';
            const region = locale.split('-').pop();
            if (region && region.length >= 2) {
                return normalizeGeoCountry(region);
            }
        } catch (e) {}

        try {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
            if (tz.includes('Lagos')) return 'NG';
            if (tz.includes('Nairobi')) return 'KE';
            if (tz.includes('Accra')) return 'GH';
            if (tz.includes('Johannesburg')) return 'ZA';
            if (tz.includes('Dubai')) return 'AE';
            if (tz.includes('Kolkata')) return 'IN';
            if (tz.includes('London')) return 'GB';
            if (tz.includes('Toronto') || tz.includes('Vancouver')) return 'CA';
            if (tz.includes('New_York') || tz.includes('Chicago') || tz.includes('Los_Angeles')) return 'US';
            if (tz.includes('Shanghai') || tz.includes('Hong_Kong')) return 'CN';
            if (tz.includes('Paris') || tz.includes('Berlin') || tz.includes('Rome') || tz.includes('Madrid')) return 'EU';
        } catch (e) {
            return 'NG';
        }

        return 'NG';
    };

    const setGeoCookie = (country) => {
        const oneYear = 60 * 60 * 24 * 365;
        document.cookie = `smat_country=${country}; path=/; max-age=${oneYear}; SameSite=Lax`;
    };

    const applyGeoCountryUi = (country) => {
        const code = normalizeGeoCountry(country);
        if (geoFlag) geoFlag.src = geoFlags[code] || geoFlags.NG;
        if (geoCode) geoCode.textContent = code;
        if (geoCurrencyCode) geoCurrencyCode.textContent = geoCurrencies[code] || 'NGN';
        localStorage.setItem('smat_country', code);
        setGeoCookie(code);
        document.dispatchEvent(new CustomEvent('smat:geo-change', {
            detail: {
                country: code,
                currency: geoCurrencies[code] || 'NGN'
            }
        }));
    };

    const cookieMatch = document.cookie.match(/(?:^|;\s*)smat_country=([^;]+)/);
    const cookieCountry = cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
    const geoSaved = localStorage.getItem('smat_country');
    const serverDefault = @json($geoCountry ?? 'NG');
    applyGeoCountryUi(geoSaved || cookieCountry || localeCountry() || serverDefault || 'NG');

    geoItems.forEach((item) => {
        item.addEventListener('click', function () {
            const country = this.getAttribute('data-country');
            applyGeoCountryUi(country);
        });
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const markAllRead = document.getElementById('markAllNotificationsRead');
    const notificationItems = document.querySelectorAll('.header-notification-item');

    if (markAllRead) {
        markAllRead.addEventListener('click', async function (e) {
            e.preventDefault();
            try {
                await fetch(@json(route('notifications.mark-all-read')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                window.location.reload();
            } catch (error) {
                console.error('Notification mark-all-read failed', error);
            }
        });
    }

    notificationItems.forEach((item) => {
        item.addEventListener('click', async function () {
            const notificationId = this.getAttribute('data-notification-id');
            if (!notificationId) return;

            try {
                await fetch(@json(url('/notifications/mark-read')) + '/' + notificationId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
            } catch (error) {
                console.error('Notification mark-read failed', error);
            }
        });
    });

    /* ════════════════════════════════════════════
       SIDEBAR DETECTION
       Supports both sidebar types:
         - Regular:    #sidebar  (div.sidebar)
         - Deployment: #deploymentSidebar  (aside.deployment-sidebar)
       ════════════════════════════════════════════ */
    function getSidebar() {
        return document.getElementById('sidebar')
            || document.getElementById('deploymentSidebar')
            || document.querySelector('.deployment-sidebar')
            || document.querySelector('.sidebar');
    }

    const overlay      = document.getElementById('sidebar-overlay');
    const mobileBtn    = document.getElementById('mobile_btn');
    const desktopBtn   = document.getElementById('toggle_btn');

    /* ── Open sidebar on mobile ── */
    function openMobileSidebar() {
        const sb = getSidebar();
        if (!sb) return;
        sb.classList.add('mobile-open');
        overlay.classList.add('active');
        document.body.classList.add('sidebar-mobile-open');
        document.querySelector('.main-wrapper')?.classList.add('slide-nav');
        document.documentElement.classList.add('menu-opened');
        document.querySelector('.sidebar-overlay')?.classList.add('opened');
        mobileBtn.setAttribute('aria-expanded', 'true');
        mobileBtn.setAttribute('aria-label', 'Close menu');
        // Animate hamburger → X
        mobileBtn.classList.add('is-open');
    }

    /* ── Close sidebar on mobile ── */
    function closeMobileSidebar() {
        const sb = getSidebar();
        if (!sb) return;
        sb.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.classList.remove('sidebar-mobile-open');
        document.querySelector('.main-wrapper')?.classList.remove('slide-nav');
        document.documentElement.classList.remove('menu-opened');
        document.querySelector('.sidebar-overlay')?.classList.remove('opened');
        mobileBtn.setAttribute('aria-expanded', 'false');
        mobileBtn.setAttribute('aria-label', 'Open menu');
        mobileBtn.classList.remove('is-open');
    }

    /* ── Hamburger click ── */
    if (mobileBtn) {
        mobileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const sb = getSidebar();
            if (!sb) return;
            if (sb.classList.contains('mobile-open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        });
    }

    /* ── Overlay click → close ── */
    if (overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
    }

    /* ── Close on ESC ── */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobileSidebar();
    });

    /* ── Close sidebar when a nav link is tapped on mobile ── */
    // This prevents the sidebar staying open after navigation
    document.addEventListener('click', function (e) {
        const sb = getSidebar();
        if (!sb || !sb.classList.contains('mobile-open')) return;
        const link = e.target.closest('a[href]');
        if (link && sb.contains(link) && link.getAttribute('href') !== '#'
            && link.getAttribute('href') !== 'javascript:void(0);') {
            // Small delay so the page starts navigating before sidebar closes
            setTimeout(closeMobileSidebar, 80);
        }
    });

    /* ════════════════════════════════════════════
       DESKTOP SIDEBAR COLLAPSE TOGGLE
       ════════════════════════════════════════════ */
    if (desktopBtn) {
        desktopBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.innerWidth <= 991) {
                openMobileSidebar();
                return;
            }
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            if (isCollapsed) {
                document.body.classList.remove('sidebar-collapsed', 'mini-sidebar', 'sidebar-icon-only');
                localStorage.setItem('sidebarCollapsed', 'false');
            } else {
                document.body.classList.add('sidebar-collapsed');
                document.body.classList.remove('mini-sidebar', 'sidebar-icon-only');
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        });

        // Restore desktop collapsed state on load
        if (window.innerWidth > 991 && localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    }

    function normalizeSidebarState() {
        // Always clear any stale mobile-open state first
        closeMobileSidebar();
        if (window.innerWidth <= 991) {
            document.body.classList.remove('sidebar-collapsed', 'mini-sidebar', 'sidebar-icon-only');
        } else {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
                document.body.classList.remove('mini-sidebar', 'sidebar-icon-only');
            } else {
                document.body.classList.remove('sidebar-collapsed', 'mini-sidebar', 'sidebar-icon-only');
            }
        }
    }

    window.addEventListener('resize', function () {
        normalizeSidebarState();
    });

    normalizeSidebarState();

    // Close mobile sidebar if user clicks anywhere outside it (e.g., branch dropdown)
    document.addEventListener('click', function (e) {
        const sb = getSidebar();
        if (!sb || !sb.classList.contains('mobile-open')) return;
        if (mobileBtn && (e.target === mobileBtn || mobileBtn.contains(e.target))) return;
        if (sb.contains(e.target)) return;
        closeMobileSidebar();
    });

    /* ════════════════════════════════════════════
       SEARCH
       ════════════════════════════════════════════ */
    const searchConfig = { minChars: 2 };
    const searchableData = @json($headerSearchItems);

    function renderSearchResults(query, container) {
        if (!container) return;

        const trimmedQuery = String(query || '').trim().toLowerCase();
        if (trimmedQuery.length < searchConfig.minChars) {
            container.classList.remove('show');
            container.innerHTML = '';
            return;
        }

        const results = searchableData.filter((item) => {
            const haystack = [
                item.title || '',
                item.subtitle || '',
                ...(item.keywords || [])
            ].join(' ').toLowerCase();

            return haystack.includes(trimmedQuery);
        }).slice(0, 8);

        if (!results.length) {
            container.innerHTML = '<div class="search-no-results">No matching pages found</div>';
            container.classList.add('show');
            return;
        }

        container.innerHTML = results.map((item) => `
            <a href="${item.url}" class="search-result-item">
                <span class="search-result-icon"><i class="fas ${item.icon}"></i></span>
                <span class="search-result-body">
                    <span class="search-result-title">${item.title}</span>
                    <span class="search-result-subtitle">${item.subtitle || ''}</span>
                </span>
            </a>
        `).join('');
        container.classList.add('show');
    }

    function bindSearchInput(inputId, resultsId) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        if (!input || !results) return;

        input.addEventListener('input', function () {
            renderSearchResults(this.value, results);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                const firstResult = results.querySelector('.search-result-item');
                if (firstResult) {
                    window.location.href = firstResult.getAttribute('href');
                }
            }
        });

        document.addEventListener('click', function (e) {
            if (!input.closest('.header-search')?.contains(e.target) && !results.contains(e.target)) {
                results.classList.remove('show');
            }
        });
    }

    bindSearchInput('globalSearch', 'searchResults');
    bindSearchInput('mobileGlobalSearch', 'mobileSearchResults');

    /* ── Mobile search overlay toggle ── */
    const mSearchBtn = document.getElementById('mobileSearchToggle');
    const mOverlay   = document.getElementById('mobileSearchOverlay');
    if (mSearchBtn && mOverlay) {
        mSearchBtn.addEventListener('click', function () {
            mOverlay.classList.toggle('active');
            if (mOverlay.classList.contains('active')) {
                document.getElementById('mobileGlobalSearch')?.focus();
            }
        });
    }
});
</script>
