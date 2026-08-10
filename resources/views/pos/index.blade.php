@extends('layout.mainlayout')

@section('content')
@php
    $products = $products ?? collect();
    $customers = $customers ?? collect();
    $sales = $sales ?? collect();
    $activeBranch = $activeBranch ?? ['name' => 'Main Workspace'];
    $bankAccounts = $bankAccounts ?? collect();
    $depositAccounts = $depositAccounts ?? collect();
    $priceLists = $priceLists ?? collect();
    $priceListData = $priceListData ?? [];
    $isStarterPos = $isStarterPos ?? (\App\Support\PlanAccess::resolveTierForUser(auth()->user()) === 'starter');
    $defaultAvatar = $defaultAvatar ?? asset('assets/img/profiles/avatar-07.jpg');
    $profileImagePath = $profileImagePath ?? (auth()->user()?->avatar_url ?: $defaultAvatar);
    $posReturnToPosUrl = $posReturnToPosUrl ?? (\Illuminate\Support\Facades\Route::has('sales.returnToPos')
        ? route('sales.returnToPos')
        : (\Illuminate\Support\Facades\Route::has('returnToPos') ? route('returnToPos') : url('/pos')));
    $posSalesLogUrl = $posSalesLogUrl ?? (\Illuminate\Support\Facades\Route::has('pos.sales') ? route('pos.sales') : url('/pos/sales'));
    $posReturnUrl = $posReturnUrl ?? (\Illuminate\Support\Facades\Route::has('pos.return.show') ? route('pos.return.show') : url('/pos/return'));
    $posHomeUrl = $posHomeUrl ?? (\Illuminate\Support\Facades\Route::has('home') ? route('home') : url('/home'));
    $posChartAccountsUrl = $posChartAccountsUrl ?? (\Illuminate\Support\Facades\Route::has('chart-of-accounts') ? route('chart-of-accounts') : url('/settings/chart-of-accounts'));
    $posSaleStoreUrl = $posSaleStoreUrl ?? (\Illuminate\Support\Facades\Route::has('sales.store') ? route('sales.store') : url('/sales'));
    $posAddProductUrl = $posAddProductUrl ?? (\Illuminate\Support\Facades\Route::has('add-products') ? route('add-products') : url('/add-products'));
    $posInventoryUrl = $posInventoryUrl ?? (\Illuminate\Support\Facades\Route::has('product-list') ? route('product-list') : url('/product-list'));
    $posReceiveItemsUrl = $posReceiveItemsUrl ?? (\Illuminate\Support\Facades\Route::has('grn.create') ? route('grn.create') : url('/grn/create'));
    $posReportsUrl = $posReportsUrl ?? (\Illuminate\Support\Facades\Route::has('pos.reports') ? route('pos.reports') : url('/pos/reports'));
    $posUser = auth()->user();
    $posRole = strtolower((string) ($posUser->role ?? ''));
    $posIsAdmin = in_array($posRole, ['super_admin', 'superadmin', 'administrator', 'admin'], true)
        || ($posUser && method_exists($posUser, 'hasRole') && ($posUser->hasRole('super_admin') || $posUser->hasRole('administrator')));
    $posCan = function (array|string $permissions) use ($posUser, $posIsAdmin): bool {
        if ($posIsAdmin) {
            return true;
        }

        if (!$posUser || !method_exists($posUser, 'hasPermissionTo')) {
            return false;
        }

        foreach ((array) $permissions as $permission) {
            if ($posUser->hasPermissionTo((string) $permission)) {
                return true;
            }
        }

        return false;
    };
    $posCanAddProduct = $posCan('inventory.products.create');
    $posCanViewInventory = $posCan(['inventory.products.view', 'inventory.stock.view']);
    $posCanReceiveItems = $posCan(['purchases.purchases.create', 'inventory.stock.edit']);
    $posCanViewReports = $posCan(['reports.reports.view', 'sales.sales.view_all', 'sales.sales.view_own']);
    $posCanReturn = $posCan(['sales.sales.return', 'sales.sales.edit', 'sales.sales.view_all', 'sales.sales.view_own']);
    $posCanSell = $posCan(['sales.sales.create', 'sales.invoices.create', 'pos.sales.create']);
    $posCanDiscount = $posCan(['sales.sales.discount', 'sales.sales.edit', 'sales.invoices.edit']);
    $posCanManageCustomers = $posCan(['customers.customers.view', 'customers.customers.create', 'sales.sales.create']);
@endphp
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
    /* Streamlined SmartProbook POS palette */
    --primary-600: #0f3a8a;
    --primary-700: #061a44;
    --success-500: #0f3a8a;
    --success-600: #061a44;
    --spb-gold: #d7a928;
    --spb-gold-soft: #fff7dc;
    --danger-500: #dc2626;
    --warning-500: #b45309;
    --indigo-600: #1d4ed8;

    /* Neutral Palette */
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;

    /* Design Tokens */
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --text-tertiary: #9ca3af;
    --border: #e5e7eb;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    -webkit-font-smoothing: antialiased;
}

.pos-full-page-wrapper { 
    margin-left: 0; 
    width: 100vw;
    max-width: 100vw;
    margin-inline: 0;
    padding: 8px clamp(8px, 0.8vw, 14px) 24px;
    background:
        linear-gradient(90deg, rgba(6, 26, 68, 0.035) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(6, 26, 68, 0.035) 0 1px, transparent 1px),
        radial-gradient(1100px 340px at 8% 0%, rgba(37, 99, 235, 0.16) 0%, rgba(37, 99, 235, 0) 58%),
        radial-gradient(900px 300px at 94% 4%, rgba(215, 169, 40, 0.13) 0%, rgba(215, 169, 40, 0) 58%),
        linear-gradient(180deg, #f6f9ff 0%, #eef4ff 54%, #f8fbff 100%);
    background-size: 34px 34px, 34px 34px, auto, auto, auto;
    min-height: 100vh; 
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 0;
}

@media(max-width: 991.98px) { 
    .pos-full-page-wrapper { 
        margin-left: 0 !important; 
        margin-inline: 0;
        width: 100%;
        max-width: 100%;
        padding: 12px; 
        margin-top: 0; 
    } 
}

.header-util-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 16px;
    border: 1px solid rgba(37, 99, 235, 0.24);
    border-radius: var(--radius-md);
    background:
        linear-gradient(90deg, rgba(238, 244, 255, 0.96) 0%, rgba(255, 255, 255, 0.98) 52%, rgba(248, 251, 255, 0.98) 100%);
    margin-bottom: 12px;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.92),
        0 0 0 1px rgba(37, 99, 235, 0.10);
}

.header-stage {
    position: relative;
    padding: 12px;
    border-radius: 20px;
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(255, 255, 255, 0.10) 0%, rgba(255, 255, 255, 0) 62%),
        linear-gradient(135deg, rgba(6, 26, 68, 0.96) 0%, rgba(15, 58, 138, 0.98) 72%, rgba(37, 99, 235, 0.92) 100%);
    border: 1px solid rgba(37, 99, 235, 0.30);
    margin-bottom: 18px;
    box-shadow: 0 14px 30px rgba(6, 26, 68, 0.18);
}

.header-stage::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 20px;
    pointer-events: none;
    box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.34);
}

.header-stage.plan-starter,
.header-stage.plan-basic {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(215, 169, 40, 0.08) 0%, rgba(215, 169, 40, 0) 62%),
        linear-gradient(135deg, rgba(6, 26, 68, 0.98) 0%, rgba(15, 58, 138, 0.96) 100%);
    border-color: rgba(37, 99, 235, 0.30);
}

.header-stage.plan-pro {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(37, 99, 235, 0.16) 0%, rgba(37, 99, 235, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(215, 169, 40, 0.08) 0%, rgba(215, 169, 40, 0) 62%),
        linear-gradient(135deg, #061a44 0%, #0f3a8a 100%);
    border-color: rgba(37, 99, 235, 0.38);
}

.header-stage.plan-enterprise {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(37, 99, 235, 0.16) 0%, rgba(37, 99, 235, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(215, 169, 40, 0.08) 0%, rgba(215, 169, 40, 0) 62%),
        linear-gradient(135deg, #061a44 0%, #0f3a8a 100%);
    border-color: rgba(37, 99, 235, 0.38);
}

.header-stage.plan-super {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(15, 23, 42, 0.14) 0%, rgba(15, 23, 42, 0) 62%),
        radial-gradient(900px 220px at 100% 0%, rgba(51, 65, 85, 0.14) 0%, rgba(51, 65, 85, 0) 62%),
        linear-gradient(135deg, #061a44 0%, #0f3a8a 72%, #2563eb 100%);
    border-color: rgba(37, 99, 235, 0.38);
}

.util-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.util-pill {
    font-size: 0.7rem;
    font-weight: 700;
    color: #ffffff;
    background: linear-gradient(135deg, #eaf2ff 0%, #ffffff 100%);
    color: #0f3a8a;
    border: 1px solid rgba(255, 255, 255, 0.38);
    border-radius: 999px;
    padding: 4px 10px;
    white-space: nowrap;
    box-shadow: 0 8px 18px rgba(6, 26, 68, 0.16);
}

.header-util-note {
    font-size: 0.84rem;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: -0.01em;
    text-align: right;
}

.header-util-note .accent {
    color: var(--spb-gold-soft);
    font-weight: 700;
}

.pos-header-bar {
    background: #ffffff;
    min-height: 78px; 
    padding: 12px clamp(18px, 2vw, 34px);
    border-radius: 18px;
    border: 1px solid rgba(37, 99, 235, 0.20);
    box-shadow: 0 8px 20px rgba(6, 26, 68, 0.12);
    margin-bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: clamp(16px, 2vw, 30px);
    position: relative;
}

.pos-header-bar::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-700) 0%, var(--primary-600) 82%, var(--spb-gold) 100%);
}

.pos-header-title { 
    color: var(--text-primary);
    font-weight: 700;
    font-size: 1rem;
    margin: 0;
    letter-spacing: -0.02em;
    white-space: nowrap;
}

.pos-header-title .gradient-text {
    background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-600) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.pos-terminal-workspace #mobile_btn,
body.pos-terminal-workspace #toggle_btn {
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

body.pos-terminal-workspace #mobile_btn:hover,
body.pos-terminal-workspace #mobile_btn:focus-visible,
body.pos-terminal-workspace #toggle_btn:hover,
body.pos-terminal-workspace #toggle_btn:focus-visible {
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 10px 22px rgba(6, 26, 68, 0.12);
    transform: translateY(-1px);
}

/* Clock Badge */
.clock-badge {
    background: linear-gradient(135deg, #f4d37a 0%, #d4af37 100%);
    border: 1px solid rgba(180, 134, 12, 0.18);
    color: #1f2937;
    padding: 6px 12px;
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.3px;
    white-space: nowrap;
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.20);
}

/* Search Bar - FIXED ICON POSITIONING */
.search-wrapper { 
    position: relative; 
    flex: 1 1 auto;
    min-width: 360px;
    max-width: 860px;
}

.search-icon-wrapper {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 3;
}

.search-icon {
    color: var(--text-tertiary);
    font-size: 0.875rem;
}

.search-input { 
    height: 46px;
    border: 1.5px solid rgba(96, 165, 250, 0.20);
    border-radius: var(--radius-md);
    padding: 0 78px 0 50px;
    width: 100%;
    background: var(--gray-50);
    color: var(--text-primary);
    font-weight: 500;
    font-size: 0.8125rem;
    transition: var(--transition);
}

/* Prevent generic .form-control padding from collapsing search icon spacing */
.search-wrapper .search-input {
    padding: 0 78px 0 50px !important;
    height: 46px !important;
}

.search-input:focus { 
    border-color: var(--primary-600);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
    background: #ffffff;
}

.search-input::placeholder {
    color: var(--text-tertiary);
}

.search-kbd {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-tertiary);
    border: 1px solid var(--border);
    border-radius: 6px;
    background: #fff;
    padding: 2px 7px;
    line-height: 1.2;
}

/* User Profile */
.user-info {
    text-align: right;
    margin-right: 10px;
    min-width: 0;
    max-width: 180px;
}

.user-label {
    font-size: 0.625rem;
    color: #64748b;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1;
    margin-bottom: 3px;
}

.user-name {
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(6, 26, 68, 0.24);
    transition: var(--transition);
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}

.user-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 14px rgba(6, 26, 68, 0.32);
}

/* Card Panels */
.sticky-panel { 
    position: sticky; 
    top: 84px; 
}

.pos-shell {
    display: flex;
    gap: clamp(12px, 1.1vw, 20px);
    align-items: stretch;
    width: 100%;
    max-width: 100%;
    min-height: calc(100vh - 112px);
    overflow: hidden;
}

.pos-action-rail {
    position: sticky;
    top: 88px;
    flex: 0 0 clamp(190px, 12vw, 240px);
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 5;
    min-height: 0;
    align-self: flex-start;
}

.pos-rail-panel {
    border: 1px solid rgba(120, 132, 150, 0.28);
    border-radius: 8px;
    padding: 10px;
    background:
        linear-gradient(180deg, rgba(247, 249, 252, 0.98) 0%, rgba(230, 235, 242, 0.98) 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.85), 0 12px 28px rgba(45, 55, 72, 0.10);
    width: 100%;
}

.pos-rail-panel:first-child {
    flex: 0 0 auto;
}

.pos-rail-panel:last-child {
    flex: 0 0 auto;
}

.pos-rail-title {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.45px;
    color: #607086;
    text-align: left;
    margin: 2px 4px 8px;
}

.pos-rail-divider {
    height: 1px;
    margin: 12px 4px 10px;
    background: linear-gradient(90deg, transparent, rgba(15, 58, 138, 0.18), transparent);
}

.pos-rail-btn {
    width: 100%;
    border: 1px solid rgba(126, 140, 158, 0.38);
    background: linear-gradient(180deg, #f7f8fa 0%, #d9dee6 100%);
    border-radius: 3px;
    min-height: 54px;
    padding: 8px 10px;
    color: #39465a;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0;
    transition: var(--transition);
    text-decoration: none;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.92), inset 0 -1px 0 rgba(94, 106, 124, 0.12);
}

.pos-rail-btn i {
    display: none;
}

.pos-rail-btn:hover,
.pos-rail-btn:focus {
    border-color: rgba(49, 105, 170, 0.45);
    background: linear-gradient(180deg, #ffffff 0%, #e7eef8 100%);
    color: #0f3a8a;
    transform: none;
}

.pos-main-stage {
    min-width: 0;
    flex: 1 1 auto;
    width: 100%;
    max-width: 100%;
    align-self: stretch;
    overflow: hidden;
}

.pos-main-stage > .header-stage {
    grid-area: header;
}

.pos-main-stage > .pos-product-shelf-card {
    grid-area: shelf;
}

.pos-main-stage > .row.g-4 {
    grid-area: work;
}

.pos-rail-backdrop {
    display: none;
}

@media(max-width: 1199.98px) {
    .pos-shell {
        display: block;
        min-height: 0;
        overflow: visible;
    }

    .pos-action-rail {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1055;
        width: min(320px, 84vw);
        height: 100dvh;
        padding: 14px;
        overflow-y: auto;
        background: linear-gradient(180deg, #eef4ff 0%, #ffffff 100%);
        box-shadow: 18px 0 42px rgba(6, 26, 68, 0.22);
        transform: translateX(-110%);
        transition: transform 0.25s ease;
        display: block;
    }

    .pos-rail-panel {
        padding: 10px;
    }

    .pos-rail-panel:first-child,
    .pos-rail-panel:last-child {
        flex: none;
    }

    .pos-rail-btn {
        min-height: 68px;
    }

    body.pos-rail-open .pos-action-rail {
        transform: translateX(0);
    }

    body.pos-rail-open .pos-rail-backdrop {
        display: block;
        position: fixed;
        inset: 0;
        z-index: 1050;
        background: rgba(6, 26, 68, 0.38);
        backdrop-filter: blur(2px);
    }
}

@media(max-width: 767.98px) {
    .pos-rail-panel {
        display: block;
    }

    .pos-rail-title {
        margin-bottom: 0;
    }

    .pos-rail-btn {
        min-height: 62px;
        padding: 8px 6px;
    }
}

.pos-card { 
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: #ffffff;
    box-shadow: var(--shadow-lg);
}

/* Scanner Section */
.scanner-section {
    background: linear-gradient(135deg, #eef4ff 0%, #ffffff 100%);
    border: 2px solid rgba(37, 99, 235, 0.24);
    border-radius: var(--radius-lg);
    padding: 14px;
    margin-bottom: 18px;
}

.scanner-label {
    color: var(--primary-600);
    font-weight: 700;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: block;
}

.scanner-input {
    border: none;
    background: transparent;
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--text-primary);
    padding: 0;
}

.scanner-input:focus {
    outline: none;
    box-shadow: none;
}

/* Image Frame */
.image-frame {
    height: 140px;
    background: var(--gray-50);
    border: 2px dashed var(--border);
    border-radius: var(--radius-md);
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-frame:hover {
    border-color: var(--primary-600);
    background: #ffffff;
}

/* Product Browser */
.product-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}

.toolbar-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 0;
}

.category-pills-wrap {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
}

.category-pills.collapsed .category-pill:nth-child(n + 8) {
    display: none;
}

.category-toggle-btn {
    border: 1px solid var(--border);
    background: #fff;
    color: var(--text-secondary);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
    transition: var(--transition);
}

.category-toggle-btn:hover {
    border-color: var(--primary-600);
    color: var(--primary-600);
    background: #eef4ff;
}

.category-pill {
    border: 1px solid var(--border);
    background: #fff;
    color: var(--text-secondary);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 0.72rem;
    font-weight: 700;
    transition: var(--transition);
}

.category-pill:hover,
.category-pill.active {
    border-color: var(--primary-600);
    color: var(--primary-600);
    background: #eef4ff;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
    grid-auto-rows: minmax(104px, auto);
    gap: 12px;
    max-height: clamp(260px, calc(100vh - 405px), 430px);
    overflow-y: auto;
    padding: 2px 6px 2px 2px;
}

.pos-product-shelf-card .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: minmax(118px, auto);
    max-height: none;
    min-height: 0;
    align-content: start;
}

.product-card {
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 7px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    cursor: pointer;
    transition: var(--transition);
    min-height: 104px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 30px rgba(6, 26, 68, 0.13);
    border-color: var(--primary-600);
}

.product-card.active {
    border-color: var(--primary-600);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.14);
    background: #eef4ff;
}

.product-card.last-picked {
    border-color: var(--primary-600);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.14);
}

.product-card-img {
    height: 78px;
    width: 100%;
    border-radius: 10px;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-card-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.product-card-name {
    width: 100%;
    color: #061a44;
    font-size: .72rem;
    font-weight: 800;
    line-height: 1.15;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card-measure {
    width: 100%;
    color: rgba(6, 26, 68, .62);
    font-size: .64rem;
    font-weight: 700;
    line-height: 1.1;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hidden-product-select {
    display: none;
}

.pos-card .select2-container {
    width: 100% !important;
    margin-bottom: .5rem;
}
.pos-card .select2-container--default .select2-selection--single {
    min-height: 46px;
    border: 1px solid rgba(18, 52, 98, .18);
    border-radius: 14px;
    display: flex;
    align-items: center;
    background: #fff;
}
.pos-card .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--text-primary);
    line-height: 46px;
    padding-left: 1rem;
    padding-right: 2.25rem;
}
.pos-card .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px;
    right: .65rem;
}
.select2-dropdown.pos-product-dropdown {
    border-color: rgba(18, 52, 98, .18);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 18px 45px rgba(15, 43, 85, .14);
}

/* ── POS searchable product combobox ── */
.pos-product-combo { position: relative; }
.pos-product-combo__input-wrap { position: relative; display: flex; align-items: center; }
.pos-product-combo__icon {
    position: absolute; right: 14px;
    color: #7a90b3; font-size: .85rem; pointer-events: none; z-index: 2;
}
.pos-product-combo__caret {
    position: absolute; right: 38px;
    color: #7a90b3; font-size: .72rem; pointer-events: none; z-index: 2;
    transition: transform .18s;
}
.pos-product-combo__caret.open { transform: rotate(180deg); }
.pos-product-combo__input {
    width: 100%; min-height: 46px;
    border: 1px solid rgba(18,52,98,.18); border-radius: 14px;
    padding: 0 64px 0 16px;
    font-size: .9rem; color: var(--text-primary, #1e3a5f);
    background: #fff; outline: none; cursor: pointer;
    transition: border-color .18s, box-shadow .18s;
}
.pos-product-combo__input:focus {
    border-color: #0f3a8a;
    box-shadow: 0 0 0 3px rgba(15,58,138,.10);
    cursor: text;
}
.pos-product-combo__input::placeholder { color: #9db1ce; }
.pos-product-combo__clear {
    position: absolute; right: 11px;
    background: none; border: none; color: #9db1ce;
    cursor: pointer; padding: 4px; line-height: 1; font-size: .8rem; z-index: 2;
}
.pos-product-combo__clear:hover { color: #0f3a8a; }
/* Dropdown is appended to body and positioned via JS */
#pos-product-dropdown-portal {
    display: none;
    position: fixed;
    background: linear-gradient(160deg, rgba(220,234,252,0.94) 0%, rgba(245,236,210,0.91) 100%);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(15,58,138,.18);
    border-top: 2px solid rgba(215,169,40,.60);
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(6,26,68,.22), 0 0 0 1px rgba(215,169,40,.08), inset 0 1px 0 rgba(255,255,255,.70);
    z-index: 99999;
    max-height: min(540px, calc(100vh - 150px));
    min-width: 360px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(215,169,40,.40) transparent;
}
#pos-product-dropdown-portal::-webkit-scrollbar { width: 6px; }
#pos-product-dropdown-portal::-webkit-scrollbar-track { background: transparent; }
#pos-product-dropdown-portal::-webkit-scrollbar-thumb { background: rgba(215,169,40,.40); border-radius: 99px; }
#pos-product-dropdown-portal ul {
    list-style: none; margin: 0; padding: 6px 0;
}
#pos-product-dropdown-portal li {
    padding: 11px 16px; cursor: pointer; font-size: .9rem;
    color: #061a44;
    font-weight: 600;
    letter-spacing: .01em;
    display: flex; flex-direction: column;
    transition: background .12s;
    border-left: 3px solid rgba(215,169,40,.20);
}
#pos-product-dropdown-portal li:hover,
#pos-product-dropdown-portal li.kb-focus {
    background: rgba(215,169,40,.10);
    border-left-color: #d7a928;
    color: #061a44;
}
#pos-product-dropdown-portal li .combo-sku {
    font-size: .72rem; color: rgba(6,26,68,.55); font-weight: 400; margin-top: 1px;
}
#pos-product-dropdown-portal li .combo-measure {
    font-size: .7rem;
    color: rgba(6,26,68,.62);
    font-weight: 700;
    margin-top: 1px;
}
#pos-product-dropdown-portal .combo-no-results {
    padding: 12px 16px; color: rgba(6,26,68,.50); font-size: .875rem;
    text-align: center; list-style: none;
}

@media (max-width: 1199px) {
    .product-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
        grid-auto-rows: minmax(86px, auto);
        max-height: 300px;
    }
}

.controls-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: linear-gradient(180deg, #fcfdff 0%, #ffffff 100%);
    padding: 12px;
}

.quick-fill-panel {
    border: 1px dashed rgba(37, 99, 235, 0.30);
    background: linear-gradient(135deg, #eef4ff 0%, #ffffff 100%);
    border-radius: var(--radius-md);
    padding: 10px;
}

.quick-fill-title {
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--primary-700);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.quick-fill-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.quick-fill-row strong {
    color: var(--text-primary);
    font-weight: 700;
}

.stock-chip {
    font-size: 0.67rem;
    font-weight: 800;
    border-radius: 999px;
    padding: 3px 8px;
    border: 1px solid transparent;
}

.stock-chip.ok {
    color: #166534;
    background: #dcfce7;
    border-color: #86efac;
}

.stock-chip.low {
    color: #92400e;
    background: #fef3c7;
    border-color: #fcd34d;
}

/* Form Controls */
.form-control, .form-select {
    border: 1.5px solid rgba(37, 99, 235, 0.18);
    border-radius: var(--radius-md);
    font-weight: 500;
    color: var(--text-primary);
    padding: 9px 12px;
    font-size: 0.8125rem;
    transition: var(--transition);
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-600);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
    outline: none;
}

/* Unit Type Grid */
.unit-grid { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 10px; 
    margin-bottom: 18px; 
}

.unit-btn {
    position: relative;
    overflow: hidden;
    min-height: 82px;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px solid #dbe3ef;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    color: #334155;
    -webkit-text-fill-color: #334155;
    font-weight: 800;
    font-size: 0.75rem;
    padding: 13px 12px;
    border-radius: 999px;
    transition: var(--transition);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    box-shadow: 0 10px 24px rgba(15, 58, 138, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.90);
}

.unit-btn:hover {
    border-color: #2563eb;
    background: #ffffff;
    color: #0f3a8a;
    -webkit-text-fill-color: #0f3a8a;
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(15, 58, 138, 0.12), inset 0 0 0 1px rgba(37, 99, 235, 0.18);
}

.unit-btn.disabled {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #94a3b8;
    -webkit-text-fill-color: #94a3b8;
    opacity: 0.72;
    pointer-events: none;
    box-shadow: none;
}

.btn-check:checked + .unit-btn {
    background: linear-gradient(135deg, #0f3a8a 0%, #2563eb 100%);
    border-color: #bfdbfe;
    color: #ffffff;
    -webkit-text-fill-color: #ffffff;
    box-shadow: 0 16px 30px rgba(15, 58, 138, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.24);
}

.btn-check:focus + .unit-btn,
.btn-check:checked:focus + .unit-btn {
    border-color: #2563eb;
    box-shadow: 0 0 0 0.22rem rgba(37, 99, 235, 0.18), 0 16px 30px rgba(15, 58, 138, 0.18);
}

.unit-btn small {
    display: block;
    margin-top: 5px;
    color: inherit;
    -webkit-text-fill-color: inherit;
    font-size: 0.64rem;
    font-weight: 700;
    opacity: 0.9;
    letter-spacing: 0.3px;
    text-transform: none;
}

.unit-helper {
    font-size: 0.68rem;
    color: var(--text-secondary);
    margin: -6px 0 12px;
    line-height: 1.45;
}

/* Subtotal Display */
.subtotal-box { 
    background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
    border: 2px solid var(--primary-600);
    border-left: 4px solid var(--primary-600);
    border-radius: var(--radius-md); 
    padding: 16px;
    text-align: center;
    margin-top: 14px;
    box-shadow: var(--shadow-sm);
}

.subtotal-label { 
    font-size: 0.625rem;
    color: var(--text-secondary);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.subtotal-amount { 
    font-size: 1.375rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

/* Cart Table */
.cart-wrapper { 
    position: relative;
    min-height: 360px;
    max-height: 480px; 
    overflow-y: auto; 
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: 18px;
    background:
        linear-gradient(180deg, #fdfefe 0%, #f8fbff 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
}

.cart-wrapper::-webkit-scrollbar {
    width: 10px;
}

.cart-wrapper::-webkit-scrollbar-track {
    background: #eef4ff;
    border-radius: 999px;
}

.cart-wrapper::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #c7d2fe 0%, #93c5fd 100%);
    border-radius: 999px;
    border: 2px solid #eef4ff;
}

.cart-wrapper::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #93c5fd 0%, #60a5fa 100%);
}

.cart-table thead th {
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    color: #ffffff;
    font-weight: 700;
    border-bottom: 2px solid #d4af37;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 10px;
    position: sticky;
    top: 0;
    z-index: 10;
}

.cart-table {
    position: relative;
    z-index: 2;
    background: transparent;
}

.cart-table tbody {
    position: relative;
    z-index: 2;
}

.cart-table thead th:first-child {
    border-top-left-radius: 14px;
}

.cart-table thead th:last-child {
    border-top-right-radius: 14px;
}

.cart-table tbody tr {
    background: rgba(255, 255, 255, 0.82);
    transition: var(--transition);
}

.cart-table tbody tr:hover {
    background: #ffffff;
}

.cart-table td {
    padding: 10px;
    font-size: 0.8125rem;
}

.cart-qty-input {
    width: 58px;
    min-width: 58px;
    text-align: center;
    border: 1px solid rgba(96, 165, 250, 0.26);
    border-radius: 10px;
    padding: 6px 8px;
    font-weight: 700;
    color: var(--primary-600);
    background: #ffffff;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
}

.cart-qty-input:focus {
    outline: none;
    border-color: var(--primary-600);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
}

.cart-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cart-edit {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(234, 242, 255, 0.96) 0%, rgba(37, 99, 235, 0.14) 100%);
    color: var(--primary-600);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
}

.btn-cart-edit:hover {
    background: linear-gradient(135deg, rgba(234, 242, 255, 1) 0%, rgba(37, 99, 235, 0.16) 100%);
    color: var(--primary-700);
}

.cart-empty-state {
    position: absolute;
    top: 54px;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 12px;
    z-index: 0;
    background:
        radial-gradient(circle at top, rgba(250, 204, 21, 0.10) 0%, rgba(250, 204, 21, 0) 58%),
        linear-gradient(180deg, #fffefb 0%, #fffcf6 100%);
    opacity: 1;
    visibility: visible;
    transition: opacity 0.18s ease, visibility 0.18s ease;
}

.cart-empty-state[hidden] {
    display: none !important;
}

.cart-empty-shell {
    width: min(100%, 250px);
    padding: 14px 16px;
    text-align: center;
    border: 1px dashed #bfdbfe;
    border-radius: 10px;
    background: linear-gradient(180deg, rgba(255, 253, 244, 0.98) 0%, rgba(255, 250, 236, 0.96) 100%);
    box-shadow: 0 14px 34px rgba(212, 175, 55, 0.10);
}

.cart-empty-icon {
    width: 42px;
    height: 42px;
    margin: 0 auto 8px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #eaf2ff 0%, #ffffff 100%);
    color: #0f3a8a;
    font-size: 1.4rem;
    border: 1px solid rgba(37, 99, 235, 0.20);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.95);
}

.cart-empty-icon svg {
    width: 22px;
    height: 22px;
    display: block;
}

.cart-empty-title {
    color: var(--text-primary);
    font-size: 0.95rem;
    font-weight: 800;
    margin-bottom: 4px;
    letter-spacing: -0.02em;
}

.cart-empty-copy {
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-bottom: 0;
    line-height: 1.55;
}

.cart-wrapper.has-items .cart-empty-state {
    display: none;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.pos-toast-sm .swal2-title {
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    letter-spacing: 0 !important;
}

/* Summary Panel */
.summary-panel {
    background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-md);
    padding: 18px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
}

.summary-label {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 0.875rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

/* Grand Total */
.grand-total { 
    font-size: 1.5rem;
    font-weight: 900;
    background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-600) 86%, var(--spb-gold) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

/* Buttons */
.btn-add-cart { 
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    color: #ffffff;
    --spb-btn-hover-color: #ffffff;
    border: none;
    font-weight: 800;
    font-size: 0.8125rem;
    border-radius: var(--radius-md);
    padding: 12px;
    transition: var(--transition);
    box-shadow: 0 4px 10px rgba(6, 26, 68, 0.24);
    letter-spacing: 0.3px;
}

.btn-add-cart:hover { 
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(6, 26, 68, 0.32);
}

.btn-add-cart:hover *,
.btn-add-cart:focus-visible * {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}

.btn-process { 
    background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-600) 100%);
    color: #ffffff;
    --spb-btn-hover-color: #ffffff;
    border: none;
    font-weight: 800;
    padding: 16px;
    font-size: 0.875rem;
    border-radius: var(--radius-lg);
    transition: var(--transition);
    box-shadow: 0 6px 14px rgba(6, 26, 68, 0.24);
    letter-spacing: 0.3px;
}

.btn-process:hover { 
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(6, 26, 68, 0.32);
}

.btn-process:hover *,
.btn-process:focus-visible * {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}

.btn-process:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-remove { 
    color: var(--danger-500);
    background: transparent;
    border: 1.5px solid transparent;
    border-radius: var(--radius-sm);
    padding: 5px 9px;
    transition: var(--transition);
}

.btn-remove:hover { 
    background: #fef2f2;
    border-color: var(--danger-500);
    color: var(--danger-600) !important;
    -webkit-text-fill-color: var(--danger-600) !important;
}

/* Labels */
label { 
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    margin-bottom: 5px;
    display: block;
    letter-spacing: 0.5px;
}

/* Badge */
.qty-badge {
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.6875rem;
    font-variant-numeric: tabular-nums;
}

/* Tabular Numbers */
.tabular-nums {
    font-variant-numeric: tabular-nums;
}

/* Loading State */
.processing::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    animation: shimmer 1.2s infinite;
}

@keyframes shimmer {
    to { left: 100%; }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.cart-table tbody tr {
    animation: fadeIn 0.3s ease;
}

/* Responsive */
@media (max-width: 1199.98px) and (min-width: 992px) {
    .pos-header-bar {
        padding: 10px 18px;
        gap: 14px;
    }

    .pos-header-bar > .d-flex:first-child,
    .pos-header-bar > .d-flex:last-child {
        min-width: 0;
        flex: 1 1 0;
    }

    .pos-header-bar > .d-flex:first-child {
        gap: 10px !important;
    }

    .pos-header-bar > .d-flex:last-child {
        justify-content: flex-end;
        gap: 10px !important;
    }

    .search-wrapper {
        min-width: 0;
        max-width: 360px;
    }

    .user-info {
        display: block !important;
        margin-right: 0;
        max-width: 120px;
    }

    .clock-badge {
        padding: 6px 10px;
        font-size: 0.7rem;
    }
}

@media (max-width: 768px) {
    .pos-header-bar {
        height: auto;
        padding: 10px;
        flex-wrap: wrap;
    }

    .search-wrapper {
        order: 3;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        margin: 8px 0 0 0;
    }

    .user-info {
        display: none;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        max-height: 260px;
        grid-auto-rows: minmax(84px, auto);
    }

    .header-util-bar {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-util-note {
        text-align: left;
    }

    .header-stage {
        padding: 8px;
    }

    .category-pills-wrap {
        flex-direction: column;
        align-items: stretch;
    }

    .category-toggle-btn {
        align-self: flex-end;
    }
}

/* Super-admin theme parity for POS across every subscription plan. */
.pos-full-page-wrapper {
    --sa-navy: #061a44;
    --sa-blue: #0f3a8a;
    --sa-blue-bright: #2563eb;
    --sa-gold: #d7a928;
    --sa-gold-soft: #ffe8a3;
    --sa-blue-soft: #eaf2ff;
    --sa-line: rgba(37, 99, 235, 0.24);
    background:
        radial-gradient(circle at top right, rgba(37, 99, 235, 0.14), transparent 34%),
        radial-gradient(circle at bottom left, rgba(215, 169, 40, 0.05), transparent 30%),
        linear-gradient(180deg, #f8fbff 0%, #f3f7ff 100%) !important;
}

.pos-full-page-wrapper .header-stage,
.pos-full-page-wrapper .header-stage.plan-starter,
.pos-full-page-wrapper .header-stage.plan-basic,
.pos-full-page-wrapper .header-stage.plan-pro,
.pos-full-page-wrapper .header-stage.plan-enterprise,
.pos-full-page-wrapper .header-stage.plan-super {
    background:
        radial-gradient(circle at 16% 8%, rgba(37, 99, 235, 0.30), transparent 30%),
        radial-gradient(circle at 84% 22%, rgba(215, 169, 40, 0.10), transparent 28%),
        linear-gradient(180deg, var(--sa-navy) 0%, var(--sa-blue) 52%, #071635 100%) !important;
    border: 1px solid rgba(37, 99, 235, 0.28) !important;
    box-shadow: 0 18px 42px rgba(6, 26, 68, 0.22) !important;
}

.pos-full-page-wrapper .header-stage::after {
    box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.34) !important;
}

.pos-full-page-wrapper .header-util-bar {
    background: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(191, 219, 254, 0.30) !important;
    box-shadow: inset 3px 0 0 var(--sa-gold), 0 10px 22px rgba(0, 0, 0, 0.14) !important;
}

.pos-full-page-wrapper .util-pill,
.pos-full-page-wrapper .clock-badge {
    background: linear-gradient(135deg, var(--sa-blue-soft) 0%, #ffffff 100%) !important;
    color: var(--sa-blue) !important;
    border-color: rgba(191, 219, 254, 0.72) !important;
    -webkit-text-fill-color: var(--sa-blue) !important;
}

.pos-full-page-wrapper .header-util-note,
.pos-full-page-wrapper .header-util-note .accent {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}

.pos-full-page-wrapper .pos-header-bar,
.pos-full-page-wrapper .pos-card,
.pos-full-page-wrapper .controls-card,
.pos-full-page-wrapper .summary-panel {
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%) !important;
    border: 1px solid rgba(15, 58, 138, 0.12) !important;
    box-shadow: 0 12px 28px rgba(6, 26, 68, 0.08) !important;
}

.pos-full-page-wrapper .pos-header-bar::after,
.pos-full-page-wrapper .cart-table thead th {
    background: linear-gradient(135deg, var(--sa-blue) 0%, var(--sa-blue-bright) 100%) !important;
    border-bottom-color: var(--sa-gold) !important;
}

.pos-full-page-wrapper .scanner-section,
.pos-full-page-wrapper .quick-fill-panel,
.pos-full-page-wrapper .subtotal-box {
    background: linear-gradient(135deg, #eef4ff 0%, #ffffff 100%) !important;
    border-color: rgba(37, 99, 235, 0.22) !important;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.72), 0 10px 22px rgba(6, 26, 68, 0.08) !important;
}

.pos-full-page-wrapper .category-pill:hover,
.pos-full-page-wrapper .category-pill.active,
.pos-full-page-wrapper .category-toggle-btn:hover,
.pos-full-page-wrapper .product-card.active,
.pos-full-page-wrapper .product-card.last-picked,
.pos-full-page-wrapper .btn-cart-edit,
.pos-full-page-wrapper .cart-empty-shell {
    color: var(--sa-blue) !important;
    background: rgba(37, 99, 235, 0.10) !important;
    border-color: rgba(37, 99, 235, 0.32) !important;
    box-shadow: inset 2px 0 0 var(--sa-gold), 0 10px 22px rgba(6, 26, 68, 0.08) !important;
}

.pos-full-page-wrapper .unit-btn {
    background: #ffffff !important;
    border-color: rgba(15, 58, 138, 0.18) !important;
    color: #10264f !important;
    -webkit-text-fill-color: #10264f !important;
}

.pos-full-page-wrapper .unit-btn:hover,
.pos-full-page-wrapper .btn-check:checked + .unit-btn {
    background: linear-gradient(135deg, var(--sa-blue) 0%, var(--sa-blue-bright) 100%) !important;
    border-color: rgba(37, 99, 235, 0.64) !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    box-shadow: inset 3px 0 0 var(--sa-gold), 0 12px 26px rgba(15, 58, 138, 0.18) !important;
}

.pos-full-page-wrapper .btn-add-cart,
.pos-full-page-wrapper .btn-process {
    background: linear-gradient(135deg, var(--sa-blue) 0%, var(--sa-blue-bright) 100%) !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    border: 1px solid transparent !important;
    box-shadow: 0 10px 22px rgba(15, 58, 138, 0.22) !important;
}

.pos-full-page-wrapper .btn-add-cart:hover,
.pos-full-page-wrapper .btn-process:hover {
    background: #ffffff !important;
    color: var(--sa-blue) !important;
    -webkit-text-fill-color: var(--sa-blue) !important;
    border-color: var(--sa-gold) !important;
}

.pos-full-page-wrapper .btn-add-cart:hover *,
.pos-full-page-wrapper .btn-process:hover * {
    color: var(--sa-blue) !important;
    -webkit-text-fill-color: var(--sa-blue) !important;
}

.pos-full-page-wrapper .form-control:focus,
.pos-full-page-wrapper .form-select:focus,
.pos-full-page-wrapper .search-input:focus,
.pos-full-page-wrapper .cart-qty-input:focus {
    border-color: var(--sa-gold) !important;
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.16) !important;
}

body.pos-terminal-workspace .page-wrapper .content,
body.pos-terminal-workspace .page-wrapper .content.container-fluid {
    padding: 0 !important;
    width: 100vw !important;
    max-width: 100% !important;
    margin: 0 !important;
    overflow-x: hidden !important;
}

body.pos-terminal-workspace .pos-full-page-wrapper {
    position: relative;
    left: 0;
    width: 100vw !important;
    max-width: 100vw !important;
    margin: 0 !important;
    overflow-x: hidden;
    box-sizing: border-box;
}

@media (min-width: 1200px) {
    .pos-main-stage {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(300px, 0.78fr);
        grid-template-areas:
            "header header"
            "work shelf";
        gap: clamp(14px, 1.2vw, 22px);
        align-items: stretch;
    }

    .pos-main-stage > .header-stage,
    .pos-main-stage > .pos-product-shelf-card,
    .pos-main-stage > .row.g-4 {
        margin-bottom: 0 !important;
    }

    .pos-main-stage > .pos-product-shelf-card {
        height: 100%;
        min-height: calc(100vh - 300px);
        align-self: stretch;
        overflow: hidden;
    }

    .pos-product-shelf-card .category-pills-wrap {
        align-items: flex-start;
        gap: 10px;
    }

    .pos-product-shelf-card .product-grid {
        height: calc(100% - 88px);
        overflow-y: auto;
    }
}

.pos-full-page-wrapper .pos-card {
    border-radius: 22px !important;
    padding: clamp(18px, 1.6vw, 28px) !important;
}

.pos-full-page-wrapper .controls-card,
.pos-full-page-wrapper .summary-panel {
    border-radius: 20px !important;
}

.pos-full-page-wrapper .row.g-4 {
    --bs-gutter-x: clamp(18px, 1.8vw, 30px);
    --bs-gutter-y: clamp(18px, 1.8vw, 30px);
}

.pos-full-page-wrapper .search-wrapper {
    flex: 1 1 min(46vw, 680px);
    max-width: 720px;
}

.pos-full-page-wrapper .search-input {
    height: 52px;
    border-radius: 16px;
}

.pos-full-page-wrapper .product-toolbar {
    margin-bottom: 14px;
}

.pos-full-page-wrapper .toolbar-title,
.pos-full-page-wrapper label,
.pos-full-page-wrapper .scanner-label {
    letter-spacing: 0.04em;
}

@media (min-width: 1500px) {
    .pos-shell {
        gap: 12px;
    }

    .pos-action-rail {
        flex-basis: clamp(176px, 10vw, 220px);
    }

    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(126px, 1fr));
    }

}

@media (min-width: 1200px) {
    .pos-full-page-wrapper .row.g-4 {
        display: grid;
        grid-template-columns: minmax(340px, 0.88fr) minmax(0, 1.62fr);
        gap: clamp(18px, 1.8vw, 30px);
        margin-left: 0;
        margin-right: 0;
    }

    .pos-full-page-wrapper .row.g-4 > .col-xl-4,
    .pos-full-page-wrapper .row.g-4 > .col-xl-8 {
        width: 100%;
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
    }
}

@media (min-width: 1500px) {
    .pos-main-stage {
        grid-template-columns: minmax(0, 2.08fr) minmax(340px, 0.82fr);
    }

    .pos-full-page-wrapper .row.g-4 {
        grid-template-columns: minmax(360px, 0.82fr) minmax(0, 1.8fr);
    }
}

@media (max-width: 1199.98px) {
    .pos-full-page-wrapper .row.g-4 > .col-xl-4,
    .pos-full-page-wrapper .row.g-4 > .col-xl-8 {
        width: 100%;
        flex-basis: 100%;
    }

    .pos-full-page-wrapper .search-wrapper {
        max-width: 100%;
    }
}

@media (max-width: 767.98px) {
    .pos-product-shelf-card {
        padding: 12px !important;
    }

    .pos-product-shelf-card .product-grid {
        grid-template-columns: repeat(8, minmax(0, 1fr)) !important;
        grid-auto-rows: minmax(48px, auto);
        gap: 6px;
        max-height: 220px;
        overflow-y: auto;
        padding: 2px;
    }

    .pos-product-shelf-card .product-card {
        min-height: 48px;
        border-radius: 10px;
        padding: 3px;
    }

    .pos-product-shelf-card .product-card-img {
        height: 38px;
        border-radius: 8px;
    }
}

@media (max-width: 575.98px) {
    .pos-product-shelf-card .product-toolbar {
        gap: 6px;
        margin-bottom: 8px;
    }

    .pos-product-shelf-card .toolbar-title {
        font-size: 0.76rem;
    }

    .pos-product-shelf-card .category-pills-wrap {
        margin-bottom: 8px;
    }

    .pos-product-shelf-card .category-pill,
    .pos-product-shelf-card .category-toggle-btn {
        min-height: 28px;
        padding: 4px 8px;
        font-size: 0.68rem;
    }

    .pos-product-shelf-card .product-grid {
        grid-template-columns: repeat(10, minmax(0, 1fr)) !important;
        grid-auto-rows: minmax(36px, auto);
        gap: 4px;
        max-height: 180px;
    }

    .pos-product-shelf-card .product-card {
        min-height: 36px;
        border-radius: 8px;
        padding: 2px;
    }

    .pos-product-shelf-card .product-card-img {
        height: 30px;
        border-radius: 6px;
    }

    .pos-product-shelf-card .product-card-img i {
        font-size: 0.78rem;
    }
}

/* QuickBooks POS inspired terminal skin */
body.pos-terminal-workspace .pos-full-page-wrapper {
    background:
        linear-gradient(180deg, #dbe6f2 0%, #eef3f8 42%, #d8e1ec 100%) !important;
    padding: 6px 8px 12px !important;
    min-height: calc(100vh - 76px) !important;
}

.pos-shell {
    gap: 8px;
    min-height: calc(100vh - 92px);
    align-items: stretch;
}

.pos-action-rail {
    flex-basis: clamp(122px, 8vw, 150px);
    top: 82px;
    align-self: stretch;
}

.pos-rail-panel {
    padding: 8px 6px;
    border-radius: 2px;
    border-color: #aab6c4;
    background: linear-gradient(180deg, #eef1f5 0%, #d7dce3 100%);
    box-shadow: inset -1px 0 0 #c4ccd6, 1px 0 0 rgba(255,255,255,0.7);
    min-height: 100%;
}

.pos-rail-btn {
    min-height: 34px;
    justify-content: flex-start;
    padding: 6px 9px;
    border-radius: 3px;
    border-color: #5478b9;
    background: linear-gradient(180deg, #4e83d5 0%, #2857b2 100%);
    color: #ffffff;
    font-size: 0.68rem;
    line-height: 1.12;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.35), 0 1px 1px rgba(0,0,0,0.16);
}

.pos-rail-btn:hover,
.pos-rail-btn:focus {
    background: linear-gradient(180deg, #6d9bea 0%, #315fbd 100%);
    color: #ffffff;
    border-color: #2857b2;
}

.pos-rail-btn.is-disabled,
.pos-rail-btn[aria-disabled="true"] {
    cursor: not-allowed;
    opacity: 0.52;
    background: linear-gradient(180deg, #e5e8ed 0%, #cbd2dc 100%);
    border-color: #a9b4c2;
    color: #6b7280;
    pointer-events: none;
}

.pos-rail-btn-primary {
    min-height: 38px;
    border-color: #579950;
    background: linear-gradient(180deg, #90cf61 0%, #4ea13d 100%);
}

.pos-rail-btn-primary:hover,
.pos-rail-btn-primary:focus {
    background: linear-gradient(180deg, #a5db78 0%, #58ab45 100%);
    border-color: #3f8734;
}

.pos-main-stage {
    gap: 8px !important;
    height: 100%;
}

.header-stage,
.pos-full-page-wrapper .header-stage,
.pos-full-page-wrapper .header-stage.plan-starter,
.pos-full-page-wrapper .header-stage.plan-basic,
.pos-full-page-wrapper .header-stage.plan-pro,
.pos-full-page-wrapper .header-stage.plan-enterprise,
.pos-full-page-wrapper .header-stage.plan-super {
    padding: 0 !important;
    border-radius: 2px !important;
    border: 1px solid #8ea7c2 !important;
    background: linear-gradient(180deg, #f8fbff 0%, #dce7f4 100%) !important;
    box-shadow: inset 0 1px 0 #ffffff, 0 1px 2px rgba(0,0,0,0.12) !important;
}

.header-stage::after {
    display: none !important;
}

.header-util-bar {
    margin: 0 !important;
    padding: 5px 10px !important;
    border: 0 !important;
    border-bottom: 1px solid #c7d3e0 !important;
    border-radius: 0 !important;
    background: #f7f9fc !important;
    box-shadow: none !important;
}

.header-util-note {
    font-size: 0.68rem;
    color: #31435c;
}

.util-pill {
    min-height: 22px;
    padding: 2px 8px;
    border-radius: 2px;
    background: #ffffff;
    color: #24476f;
    border-color: #c8d5e4;
}

.pos-header-bar {
    min-height: 42px !important;
    height: auto !important;
    border-radius: 0 !important;
    border: 0 !important;
    border-bottom: 1px solid #8ea7c2 !important;
    padding: 6px 10px !important;
    background: linear-gradient(180deg, #ffffff 0%, #edf3fa 100%) !important;
    box-shadow: none !important;
}

.pos-header-title {
    min-width: 150px;
    font-size: 0.86rem !important;
    color: #1c2430 !important;
}

.clock-badge {
    min-height: 28px;
    border-radius: 2px !important;
    background: #fdfefe !important;
    color: #1c2430 !important;
    border-color: #b8c7d8 !important;
    box-shadow: inset 0 1px 0 #ffffff !important;
}

.search-wrapper {
    border-radius: 2px !important;
    min-height: 34px;
    border-color: #b8c7d8 !important;
    background: #ffffff !important;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.06) !important;
}

.search-input {
    height: 34px !important;
    border-radius: 2px !important;
    font-size: 0.82rem !important;
}

.pos-full-page-wrapper .pos-card,
.controls-card,
.summary-panel {
    border-radius: 2px !important;
    border: 1px solid #aebdcb !important;
    background: #f8fbff !important;
    box-shadow: inset 0 1px 0 #ffffff, 0 1px 2px rgba(0,0,0,0.10) !important;
}

.pos-full-page-wrapper .pos-product-shelf-card {
    background: linear-gradient(180deg, #f7f9fc 0%, #e9eef4 100%) !important;
}

.product-toolbar {
    padding-bottom: 6px;
    border-bottom: 1px solid #cbd6e2;
}

.product-card {
    border-radius: 2px !important;
    border-color: #c0cad6 !important;
    background: linear-gradient(180deg, #ffffff 0%, #eef3f8 100%) !important;
    box-shadow: inset 0 1px 0 #ffffff;
}

.controls-card {
    padding: 12px !important;
}

.scanner-section,
.image-frame,
.quick-fill-panel,
.subtotal-box {
    border-radius: 2px !important;
    border-color: #c2cfdd !important;
    background: #f7f9fc !important;
    box-shadow: inset 0 1px 0 #ffffff !important;
}

.unit-btn,
.btn-add-cart,
.btn-process {
    border-radius: 3px !important;
}

@media (min-width: 1200px) {
    .pos-main-stage {
        grid-template-columns: minmax(0, 2.65fr) minmax(240px, 0.78fr);
        grid-template-rows: auto minmax(0, 1fr);
        grid-template-areas:
            "header header"
            "work shelf";
        min-height: calc(100vh - 92px);
    }

    .pos-full-page-wrapper .row.g-4 {
        grid-template-columns: minmax(0, 2.1fr) minmax(250px, 0.8fr);
        grid-template-rows: minmax(0, 1fr);
        gap: 8px;
        min-height: 0;
    }

    .pos-full-page-wrapper .row.g-4 > .col-xl-8 {
        grid-column: 1;
        grid-row: 1;
    }

    .pos-full-page-wrapper .row.g-4 > .col-xl-4 {
        grid-column: 2;
        grid-row: 1;
        min-height: 0;
    }

    .pos-full-page-wrapper .row.g-4 > .col-xl-8 {
        min-height: 0;
    }

    .pos-full-page-wrapper .row.g-4 > .col-xl-4 > .controls-card,
    .pos-full-page-wrapper .row.g-4 > .col-xl-8 > .pos-card,
    .pos-main-stage > .pos-product-shelf-card {
        height: 100%;
    }
}

.pos-full-page-wrapper .row.g-4 > .col-xl-8 > .pos-card {
    position: relative;
    padding-top: 42px !important;
}

.pos-full-page-wrapper .row.g-4 > .col-xl-8 > .pos-card::before {
    content: "Sales Receipt";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 34px;
    display: flex;
    align-items: center;
    padding: 0 12px;
    color: #1c2430;
    font-size: 0.86rem;
    font-weight: 800;
    background: linear-gradient(180deg, #fdfefe 0%, #dce8f5 100%);
    border-bottom: 1px solid #aebdcb;
}

.cart-wrapper {
    min-height: clamp(300px, 46vh, 560px);
    max-height: none;
    border-radius: 0 !important;
    border: 1px solid #aebdcb !important;
    background:
        repeating-linear-gradient(
            180deg,
            #ffffff 0,
            #ffffff 33px,
            #eef5fc 33px,
            #eef5fc 66px
        ) !important;
    box-shadow: inset 0 1px 0 #ffffff !important;
}

@media (min-width: 1200px) {
    .cart-wrapper {
        height: clamp(340px, 50vh, 620px);
    }
}

.cart-table thead th,
.pos-full-page-wrapper .cart-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    padding: 7px 8px !important;
    color: #3a4658 !important;
    font-size: 0.64rem !important;
    letter-spacing: 0;
    text-transform: none;
    background: linear-gradient(180deg, #f6f8fb 0%, #dce6f1 100%) !important;
    border-bottom: 1px solid #aebdcb !important;
}

.cart-table tbody tr:nth-child(odd) {
    background: rgba(255,255,255,0.92) !important;
}

.cart-table tbody tr:nth-child(even) {
    background: rgba(234, 242, 250, 0.92) !important;
}

.cart-table td {
    padding: 7px 8px !important;
    border-color: rgba(174, 189, 203, 0.55) !important;
}

.cart-empty-state {
    background: transparent !important;
}

.cart-empty-shell {
    border-radius: 2px;
    background: rgba(255,255,255,0.82);
    border-color: #c7d3df;
}

.summary-panel {
    margin-top: 8px;
    padding: 10px 12px !important;
    background: linear-gradient(180deg, #f2f5f8 0%, #e2e7ed 100%) !important;
}

.summary-panel .grand-total {
    color: #111827 !important;
    -webkit-text-fill-color: #111827 !important;
    background: none !important;
    font-size: 1.5rem !important;
}

.summary-panel .row.g-3 {
    align-items: end;
}

#payment-method {
    border-radius: 3px;
    background: #ffffff;
}

#process-btn {
    min-height: 38px;
    border: 1px solid #5478b9 !important;
    background: linear-gradient(180deg, #6d9bea 0%, #315fbd 100%) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.36), 0 1px 2px rgba(0,0,0,0.18) !important;
}

/* One-card QuickBooks-style operating console */
body.pos-terminal-workspace .pos-full-page-wrapper {
    padding: 0 !important;
    background: linear-gradient(180deg, #cfd9e5 0%, #edf3f9 46%, #d9e2ec 100%) !important;
}

body.pos-terminal-workspace .pos-shell {
    gap: 0;
    min-height: calc(100vh - 82px);
    border-top: 1px solid #879ab0;
    background: linear-gradient(180deg, #f4f7fb 0%, #dce5ee 100%);
}

body.pos-terminal-workspace .pos-action-rail {
    top: 0;
    position: relative;
    flex: 0 0 138px;
    align-self: stretch;
    z-index: 4;
}

body.pos-terminal-workspace .pos-rail-panel {
    min-height: 100%;
    height: 100%;
    border-radius: 0;
    border-width: 0 1px 0 0;
    border-color: #9cadbf;
    background: linear-gradient(180deg, #eef2f7 0%, #d5dce5 100%);
    box-shadow: inset -1px 0 0 rgba(255,255,255,0.55);
}

body.pos-terminal-workspace .pos-main-stage {
    margin: 8px 8px 8px 0;
    padding: 0;
    border: 1px solid #7d96b1;
    border-radius: 2px;
    background: #f8fbff;
    box-shadow: inset 0 1px 0 #ffffff, 0 2px 5px rgba(35, 48, 67, 0.16);
    overflow: hidden;
}

body.pos-terminal-workspace .header-stage,
body.pos-terminal-workspace .pos-full-page-wrapper .header-stage {
    border-width: 0 0 1px 0 !important;
    border-color: #9cadbf !important;
    box-shadow: none !important;
}

body.pos-terminal-workspace .header-util-bar {
    display: none !important;
}

body.pos-terminal-workspace .pos-header-bar {
    min-height: 38px !important;
    padding: 4px 8px !important;
    border-bottom: 1px solid #9cadbf !important;
}

body.pos-terminal-workspace .pos-header-title {
    font-size: 0.82rem !important;
    min-width: 118px;
}

body.pos-terminal-workspace .clock-badge {
    min-height: 26px;
    padding: 3px 8px;
    font-size: 0.72rem;
}

body.pos-terminal-workspace .search-wrapper {
    min-height: 30px;
}

body.pos-terminal-workspace .search-input {
    height: 30px !important;
}

body.pos-terminal-workspace .search-kbd,
body.pos-terminal-workspace .user-info {
    display: none !important;
}

body.pos-terminal-workspace .user-avatar {
    width: 32px;
    height: 32px;
}

body.pos-terminal-workspace .pos-product-shelf-card,
body.pos-terminal-workspace .row.g-4 > .col-xl-8 > .pos-card,
body.pos-terminal-workspace .controls-card {
    border-radius: 0 !important;
    border-color: #a8b8c9 !important;
    box-shadow: none !important;
}

body.pos-terminal-workspace .pos-product-shelf-card {
    padding: 8px !important;
    border-width: 0 0 0 1px !important;
}

body.pos-terminal-workspace .row.g-4 {
    --bs-gutter-x: 0;
    --bs-gutter-y: 0;
}

body.pos-terminal-workspace .row.g-4 > .col-xl-8 > .pos-card {
    border-width: 0 1px 0 0 !important;
}

body.pos-terminal-workspace .row.g-4 > .col-xl-4 > .controls-card {
    border-width: 0 !important;
    background: linear-gradient(180deg, #f8fbff 0%, #eef3f8 100%) !important;
}

body.pos-terminal-workspace .cart-wrapper {
    height: clamp(250px, calc(100vh - 405px), 560px);
    min-height: 240px;
    margin-bottom: 6px;
}

body.pos-terminal-workspace .summary-panel {
    margin-top: 0;
    border-radius: 0 !important;
    padding: 8px 10px !important;
}

.pos-receipt-toolbar {
    display: grid;
    grid-template-columns: minmax(220px, 0.75fr) minmax(280px, 1fr);
    align-items: center;
    gap: 6px;
    padding: 6px 8px;
    margin: -6px -6px 6px;
    border: 1px solid #aebdcb;
    background: linear-gradient(180deg, #fdfefe 0%, #e4edf7 100%);
    box-shadow: inset 0 1px 0 #ffffff;
}

.pos-customer-compact {
    display: grid;
    grid-template-columns: auto minmax(160px, 1fr);
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.pos-customer-compact #customer-search-input {
    display: none;
}

.receipt-toolbar-label {
    color: #334155;
    font-size: 0.64rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

.pos-customer-compact .form-control,
.pos-customer-compact .form-select {
    min-height: 28px;
    height: 28px;
    border-radius: 2px;
    padding: 3px 8px;
    font-size: 0.72rem;
}

.pos-payment-tabs {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 5px;
    min-width: 0;
}

.pos-pay-tab {
    min-width: 70px;
    min-height: 28px;
    border: 1px solid #5478b9;
    border-radius: 3px;
    color: #ffffff;
    background: linear-gradient(180deg, #6d9bea 0%, #315fbd 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.36), 0 1px 1px rgba(0,0,0,0.12);
    font-size: 0.68rem;
    font-weight: 900;
    text-transform: uppercase;
}

.pos-pay-tab.active {
    border-color: #3f8734;
    background: linear-gradient(180deg, #90cf61 0%, #4ea13d 100%);
}

.receipt-wallet-hint {
    grid-column: 1 / -1;
    margin-top: -2px;
    color: #64748b;
    font-size: 0.64rem;
}

body.pos-terminal-workspace .summary-panel .summary-row {
    min-height: 22px;
    margin-bottom: 2px;
}

body.pos-terminal-workspace .summary-panel .row.g-3 {
    --bs-gutter-x: 6px;
    --bs-gutter-y: 5px;
    padding-top: 6px !important;
}

body.pos-terminal-workspace .summary-panel label {
    margin-bottom: 2px;
    font-size: 0.6rem;
}

body.pos-terminal-workspace .summary-panel .form-control,
body.pos-terminal-workspace .summary-panel .form-select {
    min-height: 30px;
    padding: 4px 8px;
    font-size: 0.72rem !important;
    border-radius: 2px;
}

body.pos-terminal-workspace .summary-panel small {
    display: none !important;
}

body.pos-terminal-workspace .summary-panel .grand-total {
    font-size: clamp(1.45rem, 2.2vw, 2.35rem) !important;
}

body.pos-terminal-workspace #process-btn {
    margin-top: 6px !important;
}

@media (max-width: 991.98px) {
    .pos-receipt-toolbar {
        grid-template-columns: 1fr;
    }

    .pos-payment-tabs {
        justify-content: stretch;
    }

    .pos-pay-tab {
        flex: 1 1 0;
    }
}

@media (max-width: 575.98px) {
    .pos-customer-compact {
        grid-template-columns: 1fr;
    }

    .receipt-toolbar-label {
        display: none;
    }
}

body.pos-terminal-workspace .product-toolbar {
    padding: 0 0 5px;
    margin-bottom: 6px;
}

body.pos-terminal-workspace .category-pills-wrap {
    margin-bottom: 7px;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
    height: calc(100% - 74px);
    max-height: none;
}

body.pos-terminal-workspace .product-card {
    min-height: 92px;
    align-items: stretch;
    padding: 6px;
    gap: 4px;
}

body.pos-terminal-workspace .product-card-img {
    height: 42px;
}

.product-card-meta-row {
    display: flex;
    justify-content: space-between;
    gap: 6px;
    color: #35506f;
    font-size: 0.62rem;
    font-weight: 700;
    line-height: 1.1;
}

.product-card-meta-row strong {
    color: #0f3a8a;
    white-space: nowrap;
}

body.pos-terminal-workspace .product-card::after {
    content: "dbl-click add";
    color: rgba(6, 26, 68, 0.42);
    font-size: 0.56rem;
    font-weight: 800;
    text-transform: uppercase;
    text-align: center;
}

body.pos-terminal-workspace .controls-card label,
body.pos-terminal-workspace .scanner-label {
    font-size: 0.62rem;
}

body.pos-terminal-workspace .scanner-section,
body.pos-terminal-workspace .image-frame,
body.pos-terminal-workspace .quick-fill-panel,
body.pos-terminal-workspace .subtotal-box {
    margin-bottom: 6px !important;
    padding: 8px !important;
}

body.pos-terminal-workspace .image-frame {
    height: 72px;
}

body.pos-terminal-workspace .unit-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
    margin-bottom: 8px;
}

body.pos-terminal-workspace .unit-btn {
    min-height: 50px;
    border-radius: 3px !important;
    padding: 6px 4px;
    font-size: 0.62rem;
}

body.pos-terminal-workspace .unit-btn small {
    font-size: 0.52rem;
}

body.pos-terminal-workspace .quick-fill-panel {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 4px 8px;
}

body.pos-terminal-workspace .quick-fill-title {
    grid-column: 1 / -1;
    margin-bottom: 0;
}

body.pos-terminal-workspace .quick-fill-row {
    margin-bottom: 0;
    gap: 6px;
}

@media (min-width: 1200px) {
    body.pos-terminal-workspace .pos-main-stage {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 21vw);
        grid-template-rows: auto minmax(0, 1fr);
        grid-template-areas:
            "header header"
            "work shelf";
        min-height: calc(100vh - 98px);
    }

    body.pos-terminal-workspace .pos-full-page-wrapper .row.g-4 {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(255px, 20vw);
        min-height: 0;
    }
}

@media (max-width: 1199.98px) {
    body.pos-terminal-workspace .pos-shell {
        display: block;
    }

    body.pos-terminal-workspace .pos-action-rail {
        position: fixed;
        top: 0;
        width: min(320px, 84vw);
    }

    body.pos-terminal-workspace .pos-main-stage {
        display: grid;
        grid-template-columns: 1fr;
        grid-template-areas:
            "header"
            "shelf"
            "controls"
            "receipt";
        gap: 8px;
        margin: 8px;
        border: 0;
        background: transparent;
        box-shadow: none;
        overflow: visible;
    }

    body.pos-terminal-workspace .pos-main-stage > .header-stage {
        grid-area: header;
    }

    body.pos-terminal-workspace .pos-main-stage > .pos-product-shelf-card {
        grid-area: shelf;
        border: 1px solid #a8b8c9 !important;
    }

    body.pos-terminal-workspace .pos-main-stage > .row.g-4 {
        display: contents;
    }

    body.pos-terminal-workspace .pos-main-stage > .row.g-4 > .col-xl-4 {
        grid-area: controls;
    }

    body.pos-terminal-workspace .pos-main-stage > .row.g-4 > .col-xl-8 {
        grid-area: receipt;
    }
}

@media (max-width: 767.98px) {
    body.pos-terminal-workspace .pos-main-stage {
        border-left: 0;
        border-right: 0;
        margin: 0;
    }

    body.pos-terminal-workspace .pos-header-bar {
        flex-wrap: wrap;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
        grid-template-columns: repeat(8, minmax(0, 1fr)) !important;
        max-height: 190px;
    }

    body.pos-terminal-workspace .product-card-name,
    body.pos-terminal-workspace .product-card-measure,
    body.pos-terminal-workspace .product-card-meta-row,
    body.pos-terminal-workspace .product-card::after {
        display: none;
    }
}

@media (max-width: 575.98px) {
    body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
        grid-template-columns: repeat(10, minmax(0, 1fr)) !important;
    }
}

/* Ultra-compact desktop POS: wider receipt, tiny product shelf */
@media (min-width: 1200px) {
    body.pos-terminal-workspace .pos-action-rail {
        flex-basis: 118px;
    }

    body.pos-terminal-workspace .pos-rail-panel {
        padding: 5px;
    }

    body.pos-terminal-workspace .pos-rail-btn {
        min-height: 28px;
        padding: 4px 7px;
        font-size: 0.58rem;
        margin-top: 4px !important;
    }

    body.pos-terminal-workspace .pos-rail-btn-primary {
        min-height: 30px;
    }

    body.pos-terminal-workspace .pos-main-stage {
        grid-template-columns: minmax(0, 1fr) 156px !important;
        min-height: calc(100vh - 92px);
        margin: 5px 5px 5px 0;
    }

    body.pos-terminal-workspace .pos-full-page-wrapper .row.g-4 {
        grid-template-columns: minmax(0, 1fr) 225px !important;
    }

    body.pos-terminal-workspace .pos-header-bar {
        min-height: 32px !important;
        padding: 3px 7px !important;
    }

    body.pos-terminal-workspace .pos-header-title {
        font-size: 0.72rem !important;
        min-width: 96px;
    }

    body.pos-terminal-workspace .clock-badge {
        min-height: 22px;
        padding: 2px 6px;
        font-size: 0.64rem;
    }

    body.pos-terminal-workspace .search-input {
        height: 26px !important;
        font-size: 0.7rem !important;
    }

    body.pos-terminal-workspace .pos-product-shelf-card {
        padding: 5px !important;
    }

    body.pos-terminal-workspace .product-toolbar {
        margin-bottom: 4px;
        padding-bottom: 3px;
    }

    body.pos-terminal-workspace .toolbar-title,
    body.pos-terminal-workspace #product-count {
        font-size: 0.6rem;
    }

    body.pos-terminal-workspace .category-pills-wrap {
        display: none;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
        grid-template-columns: 1fr !important;
        grid-auto-rows: minmax(50px, auto);
        gap: 4px;
        height: calc(100% - 24px);
        padding: 1px;
    }

    body.pos-terminal-workspace .product-card {
        min-height: 50px;
        padding: 4px;
        gap: 2px;
    }

    body.pos-terminal-workspace .product-card-img {
        height: 20px;
        border-radius: 2px;
    }

    body.pos-terminal-workspace .product-card-name {
        font-size: 0.58rem;
        line-height: 1.05;
    }

    body.pos-terminal-workspace .product-card-meta-row {
        font-size: 0.52rem;
        gap: 3px;
    }

    body.pos-terminal-workspace .product-card-measure,
    body.pos-terminal-workspace .product-card::after {
        display: none;
    }

    body.pos-terminal-workspace .row.g-4 > .col-xl-8 > .pos-card {
        padding: 36px 6px 6px !important;
    }

    body.pos-terminal-workspace .row.g-4 > .col-xl-8 > .pos-card::before {
        height: 28px;
        font-size: 0.72rem;
        padding: 0 8px;
    }

    body.pos-terminal-workspace .pos-receipt-toolbar {
        grid-template-columns: minmax(180px, 0.55fr) minmax(250px, 1fr);
        padding: 4px 6px;
        margin: -4px -2px 4px;
        gap: 4px;
    }

    body.pos-terminal-workspace .pos-customer-compact {
        grid-template-columns: auto minmax(130px, 1fr);
        gap: 4px;
    }

    body.pos-terminal-workspace .pos-customer-compact .form-select {
        height: 24px;
        min-height: 24px;
        padding: 2px 6px;
        font-size: 0.64rem;
    }

    body.pos-terminal-workspace .receipt-toolbar-label,
    body.pos-terminal-workspace .receipt-wallet-hint {
        font-size: 0.56rem;
    }

    body.pos-terminal-workspace .pos-payment-tabs {
        gap: 3px;
    }

    body.pos-terminal-workspace .pos-pay-tab {
        min-width: 54px;
        min-height: 24px;
        font-size: 0.56rem;
        padding: 2px 5px;
    }

    body.pos-terminal-workspace .cart-wrapper {
        height: clamp(180px, calc(100vh - 385px), 360px);
        min-height: 180px;
        margin-bottom: 4px;
    }

    body.pos-terminal-workspace .cart-table thead th,
    body.pos-terminal-workspace .pos-full-page-wrapper .cart-table thead th {
        padding: 5px 6px !important;
        font-size: 0.58rem !important;
    }

    body.pos-terminal-workspace .cart-table td {
        padding: 5px 6px !important;
        font-size: 0.68rem;
    }

    body.pos-terminal-workspace .cart-qty-input {
        width: 52px;
        min-height: 24px;
        padding: 2px 4px;
        font-size: 0.68rem;
    }

    body.pos-terminal-workspace .summary-panel {
        padding: 5px 7px !important;
    }

    body.pos-terminal-workspace .summary-panel .summary-row {
        min-height: 18px;
        margin-bottom: 0;
        font-size: 0.68rem;
    }

    body.pos-terminal-workspace .summary-panel .grand-total {
        font-size: clamp(1.25rem, 1.8vw, 1.85rem) !important;
    }

    body.pos-terminal-workspace .summary-panel .row.g-3 {
        --bs-gutter-x: 4px;
        --bs-gutter-y: 3px;
        padding-top: 4px !important;
    }

    body.pos-terminal-workspace .summary-panel .form-control,
    body.pos-terminal-workspace .summary-panel .form-select {
        min-height: 25px;
        padding: 2px 6px;
        font-size: 0.64rem !important;
    }

    body.pos-terminal-workspace #process-btn {
        min-height: 30px;
        margin-top: 4px !important;
        font-size: 0.68rem;
    }

    body.pos-terminal-workspace .controls-card {
        padding: 6px !important;
    }

    body.pos-terminal-workspace .scanner-section,
    body.pos-terminal-workspace .image-frame,
    body.pos-terminal-workspace .quick-fill-panel,
    body.pos-terminal-workspace .subtotal-box {
        padding: 5px !important;
        margin-bottom: 4px !important;
    }

    body.pos-terminal-workspace .scanner-input,
    body.pos-terminal-workspace .pos-product-combo__input,
    body.pos-terminal-workspace .form-control,
    body.pos-terminal-workspace .form-select {
        min-height: 25px;
        font-size: 0.64rem;
        padding: 3px 6px;
    }

    body.pos-terminal-workspace .image-frame {
        height: 42px;
    }

    body.pos-terminal-workspace .unit-grid {
        gap: 3px;
        margin-bottom: 4px;
    }

    body.pos-terminal-workspace .unit-btn {
        min-height: 34px;
        padding: 3px 2px;
        font-size: 0.52rem;
    }

    body.pos-terminal-workspace .unit-btn small {
        display: none;
    }

    body.pos-terminal-workspace .unit-helper,
    body.pos-terminal-workspace .quick-fill-title,
    body.pos-terminal-workspace .quick-fill-row {
        font-size: 0.56rem;
    }

    body.pos-terminal-workspace .subtotal-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: left;
    }

    body.pos-terminal-workspace .subtotal-label {
        margin-bottom: 0;
        font-size: 0.52rem;
    }

    body.pos-terminal-workspace .subtotal-amount {
        font-size: 0.9rem;
    }

    body.pos-terminal-workspace .btn-add-cart {
        min-height: 28px;
        padding: 4px 6px !important;
        font-size: 0.64rem;
        margin-top: 4px !important;
    }
}

body.pos-terminal-workspace .controls-card .scanner-section {
    background: linear-gradient(135deg, #f7fbff 0%, #eef5ff 100%);
    border: 2px solid #b9cdf3;
    border-radius: 4px;
    box-shadow: inset 4px 0 0 #1459d9, 0 10px 22px rgba(15, 58, 138, 0.08);
}

body.pos-terminal-workspace .controls-card .scanner-label,
body.pos-terminal-workspace .controls-card > label {
    color: #09265c;
    letter-spacing: 0.08em;
}

body.pos-terminal-workspace .controls-card .scanner-input {
    border: 1px solid #b8ccf3;
    border-radius: 999px;
    background: #ffffff;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
}

body.pos-terminal-workspace .controls-card .image-frame {
    width: 100%;
    min-height: 78px;
    border: 2px dashed #b7c7df;
    background:
        linear-gradient(135deg, rgba(255,255,255,0.96), rgba(239,246,255,0.9)),
        repeating-linear-gradient(45deg, rgba(20,89,217,0.05) 0 8px, transparent 8px 16px);
    border-radius: 4px;
    overflow: hidden;
}

body.pos-terminal-workspace .controls-card #no-img {
    color: #8190a7 !important;
}

body.pos-terminal-workspace .pos-product-combo__input-wrap {
    width: 100%;
}

body.pos-terminal-workspace .pos-product-combo__input {
    width: 100%;
    min-height: 38px;
    padding-left: 12px !important;
    padding-right: 58px !important;
    border-radius: 999px;
    border: 1px solid #b9cdf3;
    background: #ffffff;
}

body.pos-terminal-workspace .pos-product-combo__icon {
    right: 13px;
    color: #1459d9;
}

body.pos-terminal-workspace .pos-product-combo__caret {
    right: 35px;
    color: #31598d;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card {
    position: relative;
    min-height: 72px;
    padding: 4px;
    background: linear-gradient(180deg, #ffffff 0%, #eef5ff 100%);
    border-color: #b7c9e6;
    overflow: hidden;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card-img {
    flex: 1 1 auto;
    width: 100%;
    height: auto;
    min-height: 48px;
    border-radius: 3px;
    background: #ffffff;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card-name {
    position: absolute;
    left: 4px;
    right: 4px;
    bottom: 4px;
    width: auto;
    padding: 3px 4px;
    border-radius: 3px;
    background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(239,246,255,0.94));
    color: #061a44;
    font-size: 0.6rem;
    line-height: 1;
    box-shadow: 0 -6px 14px rgba(6, 26, 68, 0.08);
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card-measure,
body.pos-terminal-workspace .pos-product-shelf-card .product-card-meta-row,
body.pos-terminal-workspace .pos-product-shelf-card .product-card::after {
    display: none !important;
}

.pos-secondary-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
    margin-top: 6px;
}

.pos-secondary-action {
    min-height: 30px;
    border-radius: 4px;
    border: 1px solid #b8ccf3;
    background: linear-gradient(180deg, #ffffff 0%, #edf4ff 100%);
    color: #0b2b65;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    box-shadow: 0 6px 12px rgba(15, 58, 138, 0.08);
}

.category-toggle-btn,
.category-pill {
    font-weight: 800;
}

.pos-secondary-action:hover {
    border-color: #1459d9;
    color: #063078;
}

@media (min-width: 1200px) {
    body.pos-terminal-workspace .controls-card .scanner-section,
    body.pos-terminal-workspace .controls-card .image-frame {
        padding: 8px !important;
        margin-bottom: 7px !important;
    }

    body.pos-terminal-workspace .controls-card .image-frame {
        height: 78px;
    }

    body.pos-terminal-workspace .controls-card .scanner-input,
    body.pos-terminal-workspace .controls-card .pos-product-combo__input {
        min-height: 34px;
        font-size: 0.72rem;
    }

    body.pos-terminal-workspace .pos-secondary-actions {
        gap: 4px;
        margin-top: 4px;
    }

    body.pos-terminal-workspace .pos-secondary-action {
        min-height: 26px;
        padding: 3px 5px;
        font-size: 0.56rem;
    }
}

@media (max-width: 575.98px) {
    .pos-secondary-actions {
        grid-template-columns: 1fr;
    }
}

/* Professional POS shelf sizing: keep iPad close to desktop and never let product art balloon. */
body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
    align-items: start !important;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card {
    flex: 0 0 auto;
    justify-content: flex-start;
    min-height: 64px !important;
    max-height: 82px;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card-img {
    flex: 0 0 auto !important;
    height: 40px !important;
    min-height: 40px !important;
    max-height: 40px !important;
}

body.pos-terminal-workspace .pos-product-shelf-card .product-card-img img {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: contain !important;
}

@media (min-width: 900px) and (max-width: 1199.98px) {
    body.pos-terminal-workspace .pos-shell {
        display: flex;
    }

    body.pos-terminal-workspace .pos-action-rail {
        position: relative;
        flex: 0 0 118px;
        width: 118px;
    }

    body.pos-terminal-workspace .pos-main-stage {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 156px;
        grid-template-rows: auto minmax(0, 1fr);
        grid-template-areas:
            "header header"
            "work shelf";
        align-items: stretch;
        gap: 0;
        min-height: calc(100vh - 92px);
        margin: 5px 5px 5px 0;
        overflow: hidden;
    }

    body.pos-terminal-workspace .pos-main-stage > .pos-product-shelf-card {
        grid-area: shelf;
        width: 156px;
        max-width: 156px;
        min-height: 0;
        height: 100%;
        padding: 5px !important;
        border-width: 0 0 0 1px !important;
        overflow: hidden;
    }

    body.pos-terminal-workspace .pos-main-stage > .row.g-4 {
        grid-area: work;
        display: grid !important;
        grid-template-columns: minmax(210px, 255px) minmax(0, 1fr);
        min-height: 0;
        margin: 0;
    }

    body.pos-terminal-workspace .pos-main-stage > .row.g-4 > .col-xl-4,
    body.pos-terminal-workspace .pos-main-stage > .row.g-4 > .col-xl-8 {
        grid-area: auto;
        width: 100%;
        max-width: 100%;
        padding: 0;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .category-pills-wrap {
        display: none;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
        grid-template-columns: 1fr !important;
        grid-auto-rows: minmax(54px, auto) !important;
        gap: 4px !important;
        height: calc(100% - 24px) !important;
        max-height: none !important;
        padding: 1px !important;
        overflow-y: auto;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card {
        min-height: 54px !important;
        max-height: 64px;
        padding: 4px !important;
        gap: 2px;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card-img {
        height: 24px !important;
        min-height: 24px !important;
        max-height: 24px !important;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card-name {
        position: static;
        width: 100%;
        padding: 0;
        background: transparent;
        box-shadow: none;
        font-size: 0.58rem;
        line-height: 1.05;
    }
}

@media (max-width: 899.98px) {
    body.pos-terminal-workspace .pos-product-shelf-card {
        max-height: 220px;
        overflow: hidden;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(58px, 1fr)) !important;
        grid-auto-rows: minmax(56px, auto) !important;
        max-height: 170px !important;
        gap: 5px !important;
        overflow-y: auto;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card {
        min-height: 56px !important;
        max-height: 64px;
        padding: 3px !important;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card-img {
        height: 32px !important;
        min-height: 32px !important;
        max-height: 32px !important;
    }
}

/* POS action rail: iPad portrait and smaller use the drawer so the workspace gets more room. */
@media (min-width: 1024.02px) and (max-width: 1199.98px) {
    body.pos-terminal-workspace .pos-shell {
        display: flex !important;
        gap: 0;
        min-height: calc(100vh - 82px);
        overflow: hidden;
    }

    body.pos-terminal-workspace .pos-action-rail {
        position: relative !important;
        inset: auto !important;
        transform: none !important;
        flex: 0 0 118px !important;
        width: 118px !important;
        height: auto !important;
        min-height: 100%;
        padding: 0 !important;
        overflow-y: auto;
        background: transparent !important;
        box-shadow: none !important;
        display: flex !important;
        align-self: stretch;
    }

    body.pos-terminal-workspace .pos-rail-panel {
        min-height: 100%;
        padding: 5px !important;
    }

    body.pos-terminal-workspace .pos-rail-btn {
        min-height: 32px;
        padding: 5px 7px;
        font-size: 0.58rem;
        margin-top: 4px !important;
    }

    body.pos-terminal-workspace .pos-rail-backdrop,
    body.pos-terminal-workspace.pos-rail-open .pos-rail-backdrop {
        display: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
}

@media (max-width: 1024px) {
    body.pos-terminal-workspace .header-util-bar,
    body.pos-terminal-workspace .pos-header-bar {
        gap: 12px;
    }

    body.pos-terminal-workspace .pos-header-bar {
        padding-left: 14px;
        padding-right: 14px;
    }

    body.pos-terminal-workspace .pos-shell {
        display: block !important;
        min-height: 0;
        overflow: visible;
    }

    body.pos-terminal-workspace .pos-action-rail {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: min(320px, 84vw) !important;
        height: 100dvh !important;
        padding: 12px !important;
        background: linear-gradient(180deg, #eef4ff 0%, #ffffff 100%) !important;
        box-shadow: 18px 0 42px rgba(6, 26, 68, 0.22) !important;
        transform: translateX(-110%) !important;
        transition: transform 0.25s ease;
        display: block !important;
        z-index: 1055;
    }

    body.pos-terminal-workspace.pos-rail-open .pos-action-rail {
        transform: translateX(0) !important;
    }

    body.pos-terminal-workspace.pos-rail-open .pos-rail-backdrop {
        display: block !important;
        position: fixed;
        inset: 0;
        z-index: 1050;
        background: rgba(6, 26, 68, 0.18);
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
}

/* POS readability tune-up: design-only sizing for desktop terminal. */
@media (min-width: 1200px) {
    body.pos-terminal-workspace .pos-action-rail {
        flex: 0 0 clamp(158px, 9.5vw, 184px) !important;
        width: clamp(158px, 9.5vw, 184px) !important;
    }

    body.pos-terminal-workspace .pos-rail-panel {
        padding: 9px 7px !important;
    }

    body.pos-terminal-workspace .pos-rail-btn {
        min-height: 42px !important;
        padding: 8px 10px !important;
        font-size: 0.74rem !important;
        line-height: 1.18 !important;
        font-weight: 900 !important;
        margin-top: 6px !important;
    }

    body.pos-terminal-workspace .pos-rail-btn-primary {
        min-height: 46px !important;
        font-size: 0.78rem !important;
    }

    body.pos-terminal-workspace .pos-main-stage {
        grid-template-columns: minmax(0, 1fr) clamp(210px, 13vw, 250px) !important;
        min-height: calc(100vh - 92px) !important;
        margin: 5px 5px 5px 0 !important;
    }

    body.pos-terminal-workspace .pos-main-stage > .pos-product-shelf-card {
        width: clamp(210px, 13vw, 250px) !important;
        max-width: clamp(210px, 13vw, 250px) !important;
        padding: 7px !important;
    }

    body.pos-terminal-workspace .pos-full-page-wrapper .row.g-4 {
        grid-template-columns: minmax(0, 1.28fr) clamp(300px, 19vw, 355px) !important;
        gap: 0 !important;
    }

    body.pos-terminal-workspace .row.g-4 > .col-xl-4 > .controls-card {
        padding: 10px !important;
    }

    body.pos-terminal-workspace .controls-card .scanner-section,
    body.pos-terminal-workspace .controls-card .image-frame,
    body.pos-terminal-workspace .quick-fill-panel,
    body.pos-terminal-workspace .subtotal-box {
        padding: 8px !important;
        margin-bottom: 7px !important;
    }

    body.pos-terminal-workspace .controls-card label,
    body.pos-terminal-workspace .scanner-label,
    body.pos-terminal-workspace .unit-helper,
    body.pos-terminal-workspace .quick-fill-title,
    body.pos-terminal-workspace .quick-fill-row {
        font-size: 0.72rem !important;
        font-weight: 900 !important;
    }

    body.pos-terminal-workspace .scanner-input,
    body.pos-terminal-workspace .pos-product-combo__input,
    body.pos-terminal-workspace .form-control,
    body.pos-terminal-workspace .form-select {
        min-height: 34px !important;
        padding: 6px 10px !important;
        font-size: 0.78rem !important;
        font-weight: 800 !important;
    }

    body.pos-terminal-workspace .image-frame {
        height: 76px !important;
    }

    body.pos-terminal-workspace .unit-grid {
        gap: 6px !important;
    }

    body.pos-terminal-workspace .unit-btn {
        min-height: 46px !important;
        padding: 7px 6px !important;
        font-size: 0.72rem !important;
        font-weight: 900 !important;
    }

    body.pos-terminal-workspace .unit-btn small {
        display: none !important;
    }

    body.pos-terminal-workspace .btn-add-cart {
        min-height: 38px !important;
        padding: 8px 10px !important;
        font-size: 0.78rem !important;
        font-weight: 900 !important;
    }

    body.pos-terminal-workspace .cart-wrapper {
        height: clamp(210px, calc(100vh - 420px), 410px) !important;
        min-height: 210px !important;
    }

    body.pos-terminal-workspace .cart-empty-shell {
        width: min(320px, 48%) !important;
        min-height: 132px !important;
        padding: 18px 20px !important;
    }

    body.pos-terminal-workspace .cart-empty-icon {
        width: 48px !important;
        height: 48px !important;
        margin-bottom: 10px !important;
    }

    body.pos-terminal-workspace .cart-empty-title {
        font-size: 1rem !important;
        font-weight: 900 !important;
    }

    body.pos-terminal-workspace .cart-empty-copy {
        max-width: 240px !important;
        font-size: 0.78rem !important;
        line-height: 1.45 !important;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-grid {
        grid-auto-rows: minmax(62px, auto) !important;
        gap: 6px !important;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card {
        min-height: 62px !important;
        max-height: 74px !important;
        padding: 6px !important;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card-img {
        height: 30px !important;
        min-height: 30px !important;
        max-height: 30px !important;
    }

    body.pos-terminal-workspace .pos-product-shelf-card .product-card-name {
        font-size: 0.66rem !important;
        font-weight: 900 !important;
        line-height: 1.12 !important;
    }
}

/* POS terminal polish: keep controls sharp and icons clear of text. */
body.pos-terminal-workspace .search-icon-wrapper {
    left: auto !important;
    right: 14px !important;
    width: 24px !important;
}

body.pos-terminal-workspace .search-icon {
    color: #2563eb !important;
    font-size: 1rem !important;
}

body.pos-terminal-workspace .search-wrapper .search-input,
body.pos-terminal-workspace .search-input {
    padding-left: 16px !important;
    padding-right: 48px !important;
    color: #0b1733 !important;
    font-weight: 800 !important;
    -webkit-font-smoothing: antialiased;
    text-rendering: geometricPrecision;
}

body.pos-terminal-workspace .search-input::placeholder {
    color: #7b8aa5 !important;
    opacity: 1 !important;
    font-weight: 800 !important;
}

body.pos-terminal-workspace .pos-customer-compact {
    grid-template-columns: auto minmax(180px, 260px) !important;
    max-width: 360px !important;
    min-width: 0 !important;
}

body.pos-terminal-workspace .pos-customer-compact .form-select,
body.pos-terminal-workspace #customer-select {
    min-width: 180px !important;
    max-width: 260px !important;
    padding-left: 12px !important;
    padding-right: 34px !important;
    color: #071635 !important;
    font-weight: 900 !important;
    background-position: right 12px center !important;
    background-size: 13px 10px !important;
    text-rendering: geometricPrecision;
}

body.pos-terminal-workspace .receipt-toolbar-label {
    color: #0f2147 !important;
    font-weight: 900 !important;
}

body.pos-terminal-workspace .pos-payment-tabs {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(68px, 1fr)) !important;
    gap: 6px !important;
    justify-content: stretch !important;
    min-width: 0 !important;
    width: 100% !important;
}

body.pos-terminal-workspace .pos-pay-tab {
    min-width: 0 !important;
    width: 100% !important;
    min-height: 34px !important;
    padding: 6px 8px !important;
    color: #ffffff !important;
    font-size: 0.78rem !important;
    font-weight: 900 !important;
    letter-spacing: 0 !important;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.22);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.42), 0 2px 3px rgba(6, 26, 68, 0.18) !important;
}

body.pos-terminal-workspace .pos-pay-tab.active {
    color: #ffffff !important;
}

@media (min-width: 1200px) {
    body.pos-terminal-workspace .pos-receipt-toolbar {
        grid-template-columns: minmax(285px, 0.78fr) minmax(300px, 1fr) !important;
        gap: 8px !important;
        padding: 7px 8px !important;
        overflow: hidden !important;
    }
}

@media (min-width: 1200px) and (max-width: 1399.98px) {
    body.pos-terminal-workspace .pos-receipt-toolbar {
        grid-template-columns: minmax(260px, 0.72fr) minmax(272px, 1fr) !important;
    }

    body.pos-terminal-workspace .pos-customer-compact {
        grid-template-columns: auto minmax(160px, 220px) !important;
        max-width: 310px !important;
    }

    body.pos-terminal-workspace .pos-customer-compact .form-select,
    body.pos-terminal-workspace #customer-select {
        min-width: 160px !important;
        max-width: 220px !important;
        font-size: 0.72rem !important;
    }

    body.pos-terminal-workspace .pos-payment-tabs {
        grid-template-columns: repeat(4, minmax(58px, 1fr)) !important;
        gap: 5px !important;
    }

    body.pos-terminal-workspace .pos-pay-tab {
        min-height: 32px !important;
        padding: 5px 6px !important;
        font-size: 0.68rem !important;
    }
}
</style>

<div class="pos-full-page-wrapper">
    @php
        $rawPlan = strtolower(
            (string) (
                optional($currentSubscription ?? null)->plan_name
                ?? optional($currentSubscription ?? null)->plan
                ?? optional(auth()->user()?->company)->plan
                ?? 'basic'
            )
        );
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $headerStagePlanClass = match (true) {
            in_array($role, ['super_admin', 'administrator'], true) => 'plan-super',
            str_contains($rawPlan, 'starter') => 'plan-starter',
            str_contains($rawPlan, 'enterprise') => 'plan-enterprise',
            str_contains($rawPlan, 'pro') || str_contains($rawPlan, 'professional') => 'plan-pro',
            default => 'plan-basic',
        };
    @endphp

    <div class="pos-shell">
        <div class="pos-rail-backdrop" id="pos-rail-backdrop" aria-hidden="true"></div>
        <aside class="pos-action-rail" id="pos-action-rail" aria-label="POS quick actions">
            <div class="pos-rail-panel">
                <button type="button" class="pos-rail-btn pos-rail-btn-primary" id="rail-want-btn">
                    <i class="fas fa-robot"></i>
                    <span>AI Assistant</span>
                </button>
                @if($posCanSell)
                    <button type="button" class="pos-rail-btn mt-2" id="rail-scan-btn">
                        <i class="fas fa-barcode"></i>
                        <span>Scan Item</span>
                    </button>
                    <button type="button" class="pos-rail-btn mt-2" id="rail-search-btn">
                        <i class="fas fa-search"></i>
                        <span>Quick Pick Items</span>
                    </button>
                    <button type="button" class="pos-rail-btn mt-2" id="rail-misc-btn">
                        <i class="fas fa-tag"></i>
                        <span>Sell Misc Item</span>
                    </button>
                @else
                    <span class="pos-rail-btn mt-2 is-disabled" aria-disabled="true" title="You do not have permission to sell items.">
                        <i class="fas fa-barcode"></i>
                        <span>Scan Item</span>
                    </span>
                    <span class="pos-rail-btn mt-2 is-disabled" aria-disabled="true" title="You do not have permission to sell items.">
                        <i class="fas fa-search"></i>
                        <span>Quick Pick Items</span>
                    </span>
                    <span class="pos-rail-btn mt-2 is-disabled" aria-disabled="true" title="You do not have permission to sell items.">
                        <i class="fas fa-tag"></i>
                        <span>Sell Misc Item</span>
                    </span>
                @endif
                <button type="button" class="pos-rail-btn mt-2" id="rail-calculator-btn">
                    <i class="fas fa-calculator"></i>
                    <span>Calculator</span>
                </button>
                @if($posCanDiscount)
                    <button type="button" class="pos-rail-btn mt-2" id="rail-discount-btn">
                        <i class="fas fa-percent"></i>
                        <span>Give Discount</span>
                    </button>
                @else
                    <span class="pos-rail-btn mt-2 is-disabled" aria-disabled="true" title="You do not have permission to give discounts.">
                        <i class="fas fa-percent"></i>
                        <span>Give Discount</span>
                    </span>
                @endif
                @if($posCanManageCustomers)
                    <button type="button" class="pos-rail-btn mt-2" id="rail-customer-btn">
                        <i class="fas fa-user"></i>
                        <span>Customer</span>
                    </button>
                @else
                    <span class="pos-rail-btn mt-2 is-disabled" aria-disabled="true" title="You do not have permission to manage customers.">
                        <i class="fas fa-user"></i>
                        <span>Customer</span>
                    </span>
                @endif
                <button type="button" class="pos-rail-btn mt-2" id="rail-reprint-btn">
                    <i class="fas fa-receipt"></i>
                    <span>Reprint Receipt</span>
                </button>
                <button type="button" class="pos-rail-btn mt-2" id="rail-price-check-btn">
                    <i class="fas fa-tags"></i>
                    <span>Price Check</span>
                </button>
                <button type="button" class="pos-rail-btn mt-2" id="rail-clear-cart-btn">
                    <i class="fas fa-eraser"></i>
                    <span>Clear Cart</span>
                </button>
                @if($posCanSell)
                    <button type="button" class="pos-rail-btn mt-2" id="rail-checkout-btn">
                        <i class="fas fa-cash-register"></i>
                        <span>Checkout</span>
                    </button>
                    <button type="button" class="pos-rail-btn mt-2" id="rail-messages-btn">
                        <i class="fas fa-comment-alt"></i>
                        <span>Show Messages</span>
                    </button>
                @else
                    <span class="pos-rail-btn mt-2 is-disabled" aria-disabled="true" title="You do not have permission to checkout sales.">
                        <i class="fas fa-cash-register"></i>
                        <span>Checkout</span>
                    </span>
                    <span class="pos-rail-btn mt-2 is-disabled" aria-disabled="true" title="You do not have permission to view POS sales messages.">
                        <i class="fas fa-comment-alt"></i>
                        <span>Show Messages</span>
                    </span>
                @endif
            </div>
        </aside>

        <div class="pos-main-stage">
    <div class="header-stage {{ $headerStagePlanClass }}">
        <div class="header-util-bar">
            <div class="util-pills">
                <span class="util-pill">Shelf: <span id="hdr-shelf-count">{{ ($products ?? collect())->count() }}</span></span>
                <span class="util-pill">Selected: <span id="hdr-selected-product">None</span></span>
                <span class="util-pill">Cart Items: <span id="hdr-cart-count">0</span></span>
                <span class="util-pill">
                    Branch:
                    <span id="hdr-branch-name">{{ $activeBranch['name'] ?? 'Main Workspace' }}</span>
                </span>
            </div>
            <div class="header-util-note">Use <span class="accent">search</span> to quickly find products not visible on shelf.</div>
        </div>

        
        <div class="pos-header-bar">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="pos-header-title">SALES <span class="gradient-text">TERMINAL</span></h5>
                    <div class="clock-badge">
                        <i class="far fa-clock me-1"></i><span id="live-clock" class="tabular-nums">00:00:00</span>
                    </div>
                    <div class="clock-badge" style="background: linear-gradient(135deg, #eaf2ff 0%, #ffffff 100%); color: #0f3a8a; border-color: rgba(37, 99, 235, 0.28); box-shadow: 0 8px 18px rgba(6, 26, 68, 0.14);">
                        <i class="fas fa-code-branch me-1"></i>{{ $activeBranch['name'] ?? 'Main Workspace' }}
                    </div>
                </div>

            <div class="d-flex align-items-center gap-3">
                <div class="search-wrapper">
                    <div class="search-icon-wrapper">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <input type="text" id="quick-search" class="form-control search-input" placeholder="Search product by name, SKU or category...">
                    <span class="search-kbd">Ctrl + K</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="user-info d-none d-md-block">
                        <div class="user-label">Cashier</div>
                        <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
                    </div>
                    <div class="user-avatar">
                        <img src="{{ $profileImagePath ?? asset('assets/img/profiles/avatar-07.jpg') }}" alt="{{ auth()->user()->name ?? 'User' }}" onerror="this.onerror=null;this.src='{{ $defaultAvatar ?? asset('assets/img/profiles/avatar-07.jpg') }}';">
                    </div>
                </div>
            </div>        
        </div>        
    </div>

    <div class="card pos-card pos-product-shelf-card p-4 mb-4">
        <div class="product-toolbar">
            <span class="toolbar-title">Product Shelf</span>
            <span class="small text-muted" id="product-count">{{ ($products ?? collect())->count() }} item(s)</span>
        </div>

        @php
            $shelfCategories = collect($products ?? [])->pluck('category.name')->filter()->unique()->values();
        @endphp
        <div class="category-pills-wrap">
            <div class="category-pills collapsed" id="category-pills">
                <button type="button" class="category-pill active" data-category="all">All</button>
                @foreach($shelfCategories as $categoryName)
                <button type="button" class="category-pill" data-category="{{ strtolower($categoryName) }}">{{ $categoryName }}</button>
                @endforeach
            </div>
            <button type="button" class="category-toggle-btn" id="category-toggle">Show More</button>
        </div>

        <div class="product-grid" id="product-grid">
            @foreach(($products ?? []) as $p)
            @php
                $retailPrice = (float) ($p->retail_price ?? $p->price ?? 0);
                $wholesalePrice = (float) ($p->wholesale_price ?? 0);
                $specialPrice = (float) ($p->special_price ?? 0);
                $rollsPerCarton = max((int) ($p->units_per_carton ?? 0), 0);
                $unitsPerRoll = max((int) ($p->units_per_roll ?? 0), 0);
                $baseUnitName = strtolower(trim((string) (method_exists($p, 'stockUnitSymbol') ? $p->stockUnitSymbol() : ($p->base_unit_name ?? 'unit')))) ?: 'unit';
                $unitType = strtolower(trim((string) ($p->unit_type ?? 'unit'))) ?: 'unit';
                $cartonUnitCount = $rollsPerCarton > 0 ? ($unitsPerRoll > 0 ? $rollsPerCarton * $unitsPerRoll : $rollsPerCarton) : 0;
                $measurementParts = ['1 ' . $baseUnitName];
                if ($unitsPerRoll > 0) {
                    $measurementParts[] = $unitsPerRoll . ' ' . $baseUnitName . ($unitsPerRoll === 1 ? '' : 's') . ' / roll';
                }
                if ($cartonUnitCount > 0) {
                    $measurementParts[] = $cartonUnitCount . ' ' . $baseUnitName . ($cartonUnitCount === 1 ? '' : 's') . ' / carton';
                }
                if (!empty($p->purchaseUnit?->symbol) && (float) ($p->conversion_rate ?? 0) > 0) {
                    $measurementParts[] = 'Buy ' . $p->purchaseUnit->symbol . ' = ' . rtrim(rtrim(number_format((float) $p->conversion_rate, 2), '0'), '.') . ' ' . $baseUnitName;
                }
                $measurementLabel = implode(' | ', $measurementParts);
                $categoryName = $p->category->name ?? 'Uncategorized';
                $minStockLevel = (int) ($p->min_stock_level ?? 15);
                $availableStock = (float) ($p->available_stock ?? $p->stock ?? 0);
                $isOutOfStock = $availableStock <= 0;
                $barcodeList = collect($p->barcodes ?? [])
                    ->pluck('barcode')
                    ->push($p->barcode ?? null)
                    ->filter(fn ($barcode) => filled($barcode))
                    ->map(fn ($barcode) => strtolower(trim((string) $barcode)))
                    ->unique()
                    ->implode('|');
            @endphp
            <div class="product-card"
                title="{{ $p->name }}"
                data-id="{{ $p->id }}"
                data-name="{{ $p->name }}"
                data-search-name="{{ strtolower($p->name) }}"
                data-sku="{{ strtolower($p->sku ?? '') }}"
                data-barcode="{{ strtolower($p->barcode ?? '') }}"
                data-barcodes="{{ $barcodeList }}"
                data-category="{{ strtolower($categoryName) }}"
                data-category-name="{{ $categoryName }}"
                data-price="{{ $retailPrice }}"
                data-retail="{{ $retailPrice }}"
                data-wholesale="{{ $wholesalePrice }}"
                data-special="{{ $specialPrice }}"
                data-stock="{{ $availableStock }}"
                data-upc="{{ $rollsPerCarton }}"
                data-upr="{{ $unitsPerRoll }}"
                data-base-unit="{{ $baseUnitName }}"
                data-unit-type="{{ $unitType }}"
                data-purchase-unit="{{ $p->purchaseUnit->symbol ?? '' }}"
                data-conversion-rate="{{ (float) ($p->conversion_rate ?? 0) }}"
                data-measurement="{{ $measurementLabel }}"
                data-min-stock="{{ $minStockLevel }}"
                data-img="{{ $p->image_url }}"
                data-out-of-stock="{{ $isOutOfStock ? '1' : '0' }}"
                data-expiry="{{ $p->earliest_expiry ?? '' }}"
                @if($isOutOfStock) aria-disabled="true" style="opacity:.55; filter:grayscale(.15);" @endif>
                <div class="product-card-img">
                    @if($p->image_url)
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" onerror="this.style.display='none'; var fallback = this.parentElement.querySelector('.product-fallback-icon'); if (fallback) fallback.classList.remove('d-none');">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 w-100 text-primary bg-light product-fallback-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                    @endif
                    <div class="d-none align-items-center justify-content-center h-100 w-100 text-primary bg-light product-fallback-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                </div>
                <div class="product-card-name">{{ $p->name }}</div>
                <div class="product-card-measure">{{ $measurementLabel }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="controls-card">
                <div class="scanner-section mb-2">
                    <label class="scanner-label"><i class="fas fa-barcode me-1"></i> Barcode Scanner</label>
                    <input type="text" id="barcode-input" class="form-control scanner-input" placeholder="Scan product..." autofocus>
                </div>

                <div id="image-frame" class="image-frame mb-2">
                    <img id="product-img" src="" style="display:none; max-height: 90%; width: auto; border-radius: 8px;">
                    <div id="no-img" class="text-center" style="color: var(--text-tertiary);">
                        <i class="fas fa-image fa-2x mb-2 opacity-50"></i>
                        <p class="small fw-bold mb-0" style="font-size: 0.6875rem;">NO IMAGE</p>
                    </div>
                </div>

                <label>Select Product</label>
                <div class="pos-product-combo mb-2" id="product-combo-wrapper">
                    <div class="pos-product-combo__input-wrap">
                        <i class="fas fa-search pos-product-combo__icon"></i>
                        <input type="text" id="product-search-input" class="pos-product-combo__input"
                               placeholder="Click or type to search products..." autocomplete="off">
                        <i class="fas fa-chevron-down pos-product-combo__caret" id="product-combo-caret"></i>
                        <button type="button" id="product-search-clear" class="pos-product-combo__clear"
                                style="display:none" aria-label="Clear selection">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <select id="product-select" class="form-select hidden-product-select">
                    <option value="">-- Choose Product --</option>
                    @foreach(($products ?? []) as $p)
                    @php
                        $retailPrice = (float) ($p->retail_price ?? $p->price ?? 0);
                        $wholesalePrice = (float) ($p->wholesale_price ?? 0);
                        $specialPrice = (float) ($p->special_price ?? 0);
                        $rollsPerCarton = max((int) ($p->units_per_carton ?? 0), 0);
                        $unitsPerRoll = max((int) ($p->units_per_roll ?? 0), 0);
                        $baseUnitName = strtolower(trim((string) (method_exists($p, 'stockUnitSymbol') ? $p->stockUnitSymbol() : ($p->base_unit_name ?? 'unit')))) ?: 'unit';
                        $unitType = strtolower(trim((string) ($p->unit_type ?? 'unit'))) ?: 'unit';
                        $cartonUnitCount = $rollsPerCarton > 0 ? ($unitsPerRoll > 0 ? $rollsPerCarton * $unitsPerRoll : $rollsPerCarton) : 0;
                        $measurementParts = ['1 ' . $baseUnitName];
                        if ($unitsPerRoll > 0) {
                            $measurementParts[] = $unitsPerRoll . ' ' . $baseUnitName . ($unitsPerRoll === 1 ? '' : 's') . ' / roll';
                        }
                        if ($cartonUnitCount > 0) {
                            $measurementParts[] = $cartonUnitCount . ' ' . $baseUnitName . ($cartonUnitCount === 1 ? '' : 's') . ' / carton';
                        }
                        if (!empty($p->purchaseUnit?->symbol) && (float) ($p->conversion_rate ?? 0) > 0) {
                            $measurementParts[] = 'Buy ' . $p->purchaseUnit->symbol . ' = ' . rtrim(rtrim(number_format((float) $p->conversion_rate, 2), '0'), '.') . ' ' . $baseUnitName;
                        }
                        $measurementLabel = implode(' | ', $measurementParts);
                        $categoryName = $p->category->name ?? 'Uncategorized';
                        $minStockLevel = (int) ($p->min_stock_level ?? 15);
                        $barcodeList = collect($p->barcodes ?? [])
                            ->pluck('barcode')
                            ->push($p->barcode ?? null)
                            ->filter(fn ($barcode) => filled($barcode))
                            ->map(fn ($barcode) => strtolower(trim((string) $barcode)))
                            ->unique()
                            ->implode('|');
                    @endphp
                    <option value="{{ $p->id }}" 
                        data-sku="{{ $p->sku }}" 
                        data-barcode="{{ $p->barcode }}" 
                        data-barcodes="{{ $barcodeList }}"
                        data-name="{{ $p->name }}" 
                        data-price="{{ $retailPrice }}" 
                        data-retail="{{ $retailPrice }}"
                        data-wholesale="{{ $wholesalePrice }}"
                        data-special="{{ $specialPrice }}"
                        data-stock="{{ (float) ($p->available_stock ?? $p->stock) }}" 
                        data-upc="{{ $rollsPerCarton }}"
                        data-upr="{{ $unitsPerRoll }}"
                        data-base-unit="{{ $baseUnitName }}"
                        data-unit-type="{{ $unitType }}"
                        data-purchase-unit="{{ $p->purchaseUnit->symbol ?? '' }}"
                        data-conversion-rate="{{ (float) ($p->conversion_rate ?? 0) }}"
                        data-measurement="{{ $measurementLabel }}"
                        data-category="{{ strtolower($categoryName) }}"
                        data-category-name="{{ $categoryName }}"
                        data-min-stock="{{ $minStockLevel }}"
                        data-img="{{ $p->image_url }}"
                        data-expiry="{{ $p->earliest_expiry ?? '' }}">
                        {{ $p->sku }} | {{ $p->name }}
                    </option>
                    @endforeach
                </select>

                <label class="mt-2">Unit Type</label>
                <div class="unit-grid mb-2">
                    <input type="radio" class="btn-check" name="unit_type" id="unit-type-unit" value="unit" checked>
                    <label class="btn unit-btn" for="unit-type-unit">Unit<small id="unit-meta-unit">1 unit</small></label>

                    <input type="radio" class="btn-check" name="unit_type" id="unit-type-roll" value="roll">
                    <label class="btn unit-btn" for="unit-type-roll">Roll<small id="unit-meta-roll">Set by product</small></label>

                    <input type="radio" class="btn-check" name="unit_type" id="unit-type-carton" value="carton">
                    <label class="btn unit-btn" for="unit-type-carton">Carton<small id="unit-meta-carton">Set by product</small></label>
                </div>
                <div class="unit-helper" id="unit-helper-copy">Select a product to unlock the right unit packs and live pricing.</div>

                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <label>Price List</label>
                        <select id="price-list-select" class="form-select">
                            <option value="">No price list</option>
                            @foreach($priceLists ?? [] as $priceList)
                                <option value="{{ $priceList->id }}" @selected((bool) ($priceList->is_default ?? false))>
                                    {{ $priceList->name }}{{ $priceList->currency ? ' - ' . $priceList->currency : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label>Price Level</label>
                        <select id="price-tier" class="form-select">
                            <option value="list">Selected Price List</option>
                            <option value="retail">Retail / Default</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="special">Special Discount</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label>Price</label>
                        <input type="number" id="unit-price-input" class="form-control bg-light fw-bold tabular-nums" readonly>
                    </div>
                    <div class="col-6">
                        <label id="qty-label">Quantity</label>
                        <input type="number" id="quantity" class="form-control fw-bold tabular-nums" value="1" min="0.01" step="1" inputmode="decimal">
                    </div>
                    <div class="col-6">
                        <label style="color: var(--danger-500);">Discount</label>
                        <div class="input-group">
                            <select id="discount-type" class="form-select">
                                <option value="percent">%</option>
                                <option value="fixed">₦</option>
                            </select>
                            <input type="number" id="discount" class="form-control tabular-nums" value="0" min="0" step="0.01" inputmode="decimal">
                        </div>
                        <small class="text-muted" id="discount-helper">Percent of item subtotal</small>
                    </div>
                    <div class="col-6">
                        <label style="color: var(--primary-600);">Tax %</label>
                        <input type="number" id="tax" class="form-control tabular-nums" value="0" min="0" max="100">
                    </div>
                </div>

                <div class="subtotal-box mt-0">
                    <div class="subtotal-label">Item Subtotal</div>
                    <div class="subtotal-amount" id="item-total">₦0.00</div>
                </div>

                <button id="add-btn" type="button" class="btn btn-add-cart w-100 py-2 mt-2">
                    <i class="fas fa-plus-circle me-2"></i> ADD TO CART
                </button>

                <div class="quick-fill-panel mt-2">
                    <div class="quick-fill-title">Quick Summary</div>
                    <div class="quick-fill-row"><span>Selected Product</span><strong id="quick-selected-name">None</strong></div>
                    <div class="quick-fill-row"><span>SKU</span><strong id="quick-selected-sku">-</strong></div>
                    <div class="quick-fill-row"><span>Category</span><strong id="quick-selected-category">-</strong></div>
                    <div class="quick-fill-row"><span>Min Stock Level</span><strong id="quick-selected-min-stock">15</strong></div>
                    <div class="quick-fill-row"><span>Available Stock</span><strong id="quick-selected-stock">0</strong></div>
                    <div class="quick-fill-row mb-0">
                        <span>Stock Health</span>
                        <span id="quick-stock-health" class="stock-chip ok">OK</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card pos-card p-4">
                <div class="pos-receipt-toolbar">
                    <div class="pos-customer-compact">
                        <span class="receipt-toolbar-label">Customer</span>
                        <input type="text" id="customer-search-input" class="form-control" placeholder="Search customer..." aria-label="Search customer">
                        <select id="customer-select" class="form-select">
                            <option value="">Walk-in Customer</option>
                            @foreach(($customers ?? []) as $c)
                            <option value="{{ $c->id }}" data-wallet="{{ (float) ($c->wallet_balance ?? 0) }}">{{ $c->name ?? $c->customer_name ?? ('Customer #' . $c->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pos-payment-tabs" aria-label="Payment method">
                        <button type="button" class="pos-pay-tab active" data-payment-method="Cash">Cash</button>
                        <button type="button" class="pos-pay-tab" data-payment-method="Split">Split</button>
                        <button type="button" class="pos-pay-tab" data-payment-method="Split" id="pos-pay-card-shortcut">POS</button>
                        <button type="button" class="pos-pay-tab" data-payment-method="Split" id="pos-pay-transfer-shortcut">Transfer</button>
                    </div>
                    <small id="customer-wallet-hint" class="receipt-wallet-hint">Select a customer to apply wallet credit automatically.</small>
                </div>

                
                <div class="cart-wrapper">
                    <div id="cart-empty-state" class="cart-empty-state">
                        <div class="cart-empty-shell">
                            <div class="cart-empty-icon">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 4h2l1.4 7.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.75L19 7H7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="10" cy="18" r="1.6" fill="currentColor"/>
                                    <circle cx="17" cy="18" r="1.6" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="cart-empty-title">Cart Empty</div>
                            <p class="cart-empty-copy">Select products from the catalog and they will appear here in a smooth scrollable cart.</p>
                        </div>
                    </div>
                    <table class="table cart-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cart-body"></tbody>
                    </table>
                </div>

                
                <div class="summary-panel">
                    <div class="summary-row">
                        <span class="summary-label" style="color: var(--text-secondary);">Subtotal</span>
                        <span class="summary-value" style="color: var(--text-primary);" id="sum-subtotal">₦0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label" style="color: var(--danger-500);">Discount</span>
                        <span class="summary-value" style="color: var(--danger-500);" id="sum-discount">₦0.00</span>
                    </div>
                    <div class="summary-row pb-3 border-bottom">
                        <span class="summary-label" style="color: var(--primary-600);">Tax</span>
                        <span class="summary-value" style="color: var(--primary-600);" id="sum-tax">₦0.00</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                        <h6 class="mb-0 fw-bold" style="color: var(--text-primary); font-size: 0.875rem;">Total Amount</h6>
                        <div class="grand-total" id="grand-total">₦0.00</div>
                    </div>

                    
                    <div class="row g-3 border-top pt-3">
                        <select id="payment-method" class="form-select fw-bold d-none" aria-hidden="true">
                            <option value="Cash">Cash</option>
                            <option value="Split">Split (Cash + Transfer + POS)</option>
                        </select>
                        @unless($isStarterPos ?? false)
                            <div class="col-md-6">
                                <label class="fw-semibold">Cash / Deposit Account <span class="text-danger">*</span></label>
                                <select id="deposit-account" class="form-select">
                                    <option value="">-- Select Account --</option>
                                    @foreach(($depositAccounts ?? []) as $acct)
                                        <option value="{{ $acct->id }}">{{ $acct->name }}@if($acct->code) ({{ $acct->code }})@endif</option>
                                    @endforeach
                                </select>
                                @if(collect($depositAccounts ?? [])->isEmpty())
                                    <small class="text-danger d-block mt-1">
                                        No active asset accounts found.
                                        <a href="{{ $posChartAccountsUrl ?? url('/settings/chart-of-accounts') }}" class="fw-bold">Add one in Chart of Accounts</a>
                                    </small>
                                @else
                                    <small class="text-muted">This COA account will be debited in the journal entry.</small>
                                @endif
                            </div>
                        @endunless
                        <div class="col-md-6">
                            <label>Cash Amount</label>
                            <input type="number" min="0" step="0.01" id="amount-paid" class="form-control form-control-lg fw-bold text-end tabular-nums" style="font-size: 1rem; color: var(--success-500);">
                        </div>
                        @unless($isStarterPos ?? false)
                            <div class="col-md-6 d-none" id="split-transfer-account-wrap">
                                <label>Bank Account (COA)</label>
                                <select id="transfer-account" class="form-select">
                                    <option value="">-- Select Account --</option>
                                    @foreach(($depositAccounts ?? []) as $acct)
                                        <option value="{{ $acct->id }}">{{ $acct->name }}@if($acct->code) ({{ $acct->code }})@endif</option>
                                    @endforeach
                                </select>
                                @if(collect($depositAccounts ?? [])->isEmpty())
                                    <small class="text-muted d-block mt-2">
                                        No asset accounts yet.
                                        <a href="{{ $posChartAccountsUrl ?? url('/settings/chart-of-accounts') }}" class="fw-bold text-primary">Add in Chart of Accounts</a>
                                    </small>
                                @endif
                            </div>
                        @endunless
                        <div class="col-md-6 d-none" id="split-transfer-wrap">
                            <label>Bank Amount</label>
                            <input type="number" min="0" step="0.01" id="transfer-amount" class="form-control form-control-lg fw-bold text-end tabular-nums" style="font-size: 1rem; color: var(--primary-600);">
                        </div>
                        @unless($isStarterPos ?? false)
	                        <div class="col-md-6 d-none" id="split-card-account-wrap">
                                <label>POS Account (COA)</label>
                                <select id="card-account" class="form-select">
                                    <option value="">-- Select Account --</option>
                                    @foreach(($depositAccounts ?? []) as $acct)
                                        <option value="{{ $acct->id }}">{{ $acct->name }}@if($acct->code) ({{ $acct->code }})@endif</option>
                                    @endforeach
                                </select>
                                @if(collect($depositAccounts ?? [])->isEmpty())
                                    <small class="text-muted d-block mt-2">
                                        No asset accounts yet.
                                        <a href="{{ $posChartAccountsUrl ?? url('/settings/chart-of-accounts') }}" class="fw-bold text-primary">Add in Chart of Accounts</a>
                                    </small>
	                            @endif
	                        </div>
                        @endunless
                        <div class="col-md-6 d-none" id="split-card-wrap">
                            <label>POS Amount</label>
                            <input type="number" min="0" step="0.01" id="card-amount" class="form-control form-control-lg fw-bold text-end tabular-nums" style="font-size: 1rem; color: var(--primary-600);">
                        </div>
	                        <div class="col-md-6 d-none" id="wallet-payment-wrap">
	                            <label>Wallet Credit Applied</label>
	                            <input type="number" id="wallet-amount" class="form-control form-control-lg fw-bold text-end tabular-nums" value="0.00" readonly style="font-size: 1rem; color: var(--primary-700);">
	                            <small id="wallet-balance-hint" class="text-muted">Customer advance balance available for this sale.</small>
	                        </div>
	                    </div>

                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="summary-label" style="color: var(--text-secondary);">Change</span>
                        <span id="change-amount" class="fw-bold tabular-nums" style="font-size: 1.125rem; color: var(--success-500);">₦0.00</span>
                    </div>
                </div>

                
                <button type="button" id="process-btn" class="btn btn-process w-100 mt-3">
                    <span id="btn-text"><i class="fas fa-check-circle me-2"></i> PROCESS SALE</span>
                    <span id="btn-loading" style="display:none;"><i class="fas fa-sync fa-spin me-2"></i> PROCESSING...</span>
                </button>
                <div class="pos-secondary-actions">
                    <button type="button" id="save-invoice-btn" class="btn pos-secondary-action">
                        <i class="fas fa-save me-1"></i> Save Invoice
                    </button>
                    <a href="{{ ($posSalesLogUrl ?? url('/pos/sales')) . '?reprint=1' }}" class="btn pos-secondary-action">
                        <i class="fas fa-list me-1"></i> Receipt Lookup
                    </a>
                    <button type="button" id="new-sale-btn" class="btn pos-secondary-action">
                        <i class="fas fa-plus me-1"></i> New Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>

<script>
window.POS_USE_SAFE_TERMINAL = true;
window.POS_FALLBACK_REQUESTED = false;
window.requestPosFallback = function(reason) {
    if (window.POS_FALLBACK_REQUESTED) return;
    window.POS_FALLBACK_REQUESTED = true;
    if (typeof window.POS_ENABLE_FALLBACK === 'function') {
        window.POS_ENABLE_FALLBACK();
    }
};
window.addEventListener('error', function() {
    window.requestPosFallback('js-error');
});

$(document).ready(function() {
    if (window.POS_USE_SAFE_TERMINAL) {
        if (typeof window.POS_ENABLE_FALLBACK === 'function') {
            window.POS_ENABLE_FALLBACK();
        }
        return;
    }

    let cart = [];
    let lastSelectedProductId = null;
    let isSyncingProductSearch = false;
    const fmt = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' });
    const isStarterPos = @json($isStarterPos ?? false);
    const posExpiryAlertsEnabled = @json($posExpiryAlertsEnabled ?? false);
    const posExpiryAlertMonths = Number(@json($posExpiryAlertMonths ?? 1)) || 1;
    const posPriceLists = @json($priceListData ?? []);
    const posPriceListById = new Map(posPriceLists.map(list => [String(list.id), list]));
    const showAlert = (options) => {
        if (window.Swal && typeof Swal.fire === 'function') {
            return Swal.fire(options);
        }
        if (options?.icon === 'success') {
            return;
        }
        const message = options?.text || options?.title || 'Action required';
        window.alert(message);
    };

    function maybeShowExpiryAlert(payload) {
        const expiry = String(payload?.expiry || '').trim();
        if (!expiry) {
            return;
        }

        const expiryDate = new Date(expiry + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (Number.isNaN(expiryDate.getTime())) {
            return;
        }

        const diffMs = expiryDate.getTime() - today.getTime();
        const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays <= 0) {
            const key = `${payload?.id || payload?.name || 'product'}:${expiry}:expired`;
            if (window.__posLastExpiryAlertKey === key) {
                return;
            }
            window.__posLastExpiryAlertKey = key;
            showAlert({
                icon: 'error',
                title: 'Product Expired!',
                html: `<b>${payload?.name || 'This product'}</b> expired on <b>${expiry}</b>.<br>Please check your inventory before selling.`,
                confirmButtonText: 'Understood',
            });
            return;
        }

        if (!posExpiryAlertsEnabled) {
            return;
        }

        const notifyFrom = new Date(expiryDate.getTime());
        notifyFrom.setMonth(notifyFrom.getMonth() - posExpiryAlertMonths);

        if (today < notifyFrom) {
            return;
        }

        const key = `${payload?.id || payload?.name || 'product'}:${expiry}:near:${posExpiryAlertMonths}`;
        if (window.__posLastExpiryAlertKey === key) {
            return;
        }
        window.__posLastExpiryAlertKey = key;

        showAlert({
            icon: 'warning',
            title: 'Near Expiry!',
            html: `<b>${payload?.name || 'This product'}</b> expires on <b>${expiry}</b> (<b>${diffDays}</b> day${diffDays === 1 ? '' : 's'} left). Notification window: <b>${posExpiryAlertMonths} month${posExpiryAlertMonths === 1 ? '' : 's'}</b>.`,
            timer: 5000,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
        });
    }

    // Clock
    setInterval(() => $('#live-clock').text(new Date().toLocaleTimeString('en-US', { hour12: false })), 1000);

    const hasSelect2 = !!($.fn && $.fn.select2);
    if (hasSelect2) {
        $('#customer-select').select2({
            width: '100%',
            placeholder: 'Search customer name...',
            allowClear: true
        });
    }
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $('#quick-search').trigger('focus');
        }
    });

    function syncProductSearchValue(value) {
        // Sync the custom product combobox from external triggers (card clicks, barcode, etc.)
        if (typeof window._posComboSync === 'function') {
            window._posComboSync(value);
        }
    }

    function syncActiveProductCard(productId) {
        $('.product-card').removeClass('active');
        if (productId) {
            $(`.product-card[data-id="${productId}"]`).addClass('active');
            lastSelectedProductId = String(productId);
            keepLastSelectedVisible();
        }
    }

    function keepLastSelectedVisible() {
        if (!lastSelectedProductId) return;
        const card = $(`.product-card[data-id="${lastSelectedProductId}"]`);
        if (!card.length) return;
        $('.product-card').removeClass('last-picked');
        card.show().addClass('last-picked').prependTo('#product-grid');
    }

    function filterProductCards() {
        const keyword = ($('#quick-search').val() || '').toLowerCase().trim();
        const activeCategory = $('.category-pill.active').data('category') || 'all';
        let visibleCount = 0;

        $('.product-card').each(function() {
            const card = $(this);
            const name = card.data('search-name') || (card.data('name') || '').toString().toLowerCase();
            const sku = String(card.data('sku') || '').toLowerCase();
            const barcode = String(card.data('barcode') || '').toLowerCase();
            const barcodes = String(card.data('barcodes') || '').toLowerCase();
            const category = String(card.data('category') || '').toLowerCase();

            const matchesKeyword = !keyword || name.includes(keyword) || sku.includes(keyword) || barcode.includes(keyword) || barcodes.includes(keyword) || category.includes(keyword);
            const matchesCategory = activeCategory === 'all' || category === activeCategory;
            const show = matchesKeyword && matchesCategory;

            card.toggle(show);
            if (show) visibleCount += 1;
        });

        keepLastSelectedVisible();
        visibleCount = $('.product-card:visible').length;
        $('#product-count').text(`${visibleCount} item(s)`);
        $('#hdr-shelf-count').text(visibleCount);
    }

    function setUnitTypeAvailability(selectedOption) {
        const hasProduct = !!selectedOption && !!selectedOption.val();
        const unitsPerCarton = hasProduct ? (parseInt(selectedOption.data('upc')) || 0) : 0;
        const unitsPerRoll = hasProduct ? (parseInt(selectedOption.data('upr')) || 0) : 0;

        const cartonInput = $('#unit-type-carton');
        const rollInput = $('#unit-type-roll');
        const cartonLabel = $('label[for="unit-type-carton"]');
        const rollLabel = $('label[for="unit-type-roll"]');
        const unitLabel = $('label[for="unit-type-unit"]');

        const baseUnit = hasProduct ? String(selectedOption.data('base-unit') || 'unit') : 'unit';
        const cartonEnabled = !hasProduct || unitsPerCarton > 0;
        const rollEnabled = !hasProduct || unitsPerRoll > 0;

        cartonInput.prop('disabled', !cartonEnabled);
        rollInput.prop('disabled', !rollEnabled);
        cartonLabel.toggleClass('disabled', !cartonEnabled);
        rollLabel.toggleClass('disabled', !rollEnabled);

        $('#unit-meta-unit').text(hasProduct ? `1 ${baseUnit}` : '1 unit');
        $('#unit-meta-roll').text(rollEnabled ? `${unitsPerRoll} ${baseUnit}${unitsPerRoll === 1 ? '' : 's'} / roll` : 'Unavailable');
        $('#unit-meta-carton').text(
            cartonEnabled
                ? (unitsPerRoll > 0
                    ? `${unitsPerCarton * unitsPerRoll} ${baseUnit}${(unitsPerCarton * unitsPerRoll) === 1 ? '' : 's'} / carton`
                    : `${unitsPerCarton} ${baseUnit}${unitsPerCarton === 1 ? '' : 's'} / carton`)
                : 'Unavailable'
        );
        unitLabel.toggleClass('disabled', false);

        const currentType = $('input[name="unit_type"]:checked').val();
        if ((currentType === 'carton' && !cartonEnabled) || (currentType === 'roll' && !rollEnabled)) {
            $('#unit-type-unit').prop('checked', true);
        }
    }

    function resolveUnitMetrics(selectedOption) {
        const type = $('input[name="unit_type"]:checked').val() || 'unit';
        const stock = parseInt(selectedOption.data('stock')) || 0;
        const rollsPerCarton = Math.max(parseInt(selectedOption.data('upc')) || 0, 0);
        const unitsPerRoll = Math.max(parseInt(selectedOption.data('upr')) || 0, 0);
        const baseUnit = String(selectedOption.data('base-unit') || 'unit');
        const cartonUnits = rollsPerCarton > 0
            ? (unitsPerRoll > 0 ? (rollsPerCarton * unitsPerRoll) : rollsPerCarton)
            : 0;

        let multiplier = 1;
        let unitName = `${baseUnit}s`;

        if (type === 'carton' && cartonUnits > 0) {
            multiplier = cartonUnits;
            unitName = 'cartons';
        } else if (type === 'roll' && unitsPerRoll > 0) {
            multiplier = unitsPerRoll;
            unitName = 'rolls';
        } else {
            unitName = `${baseUnit}${baseUnit.endsWith('s') ? '' : 's'}`;
        }

        const maxQty = multiplier > 0 ? Math.max(Math.floor(stock / multiplier), 0) : stock;

        return {
            type,
            stock,
            multiplier,
            unitName,
            maxQty,
            baseUnit,
            rollsPerCarton,
            unitsPerRoll,
            cartonUnits
        };
    }

    function getPriceListProductPrice(listId, productId, quantity, retailPrice) {
        const list = posPriceListById.get(String(listId || ''));
        if (!list) return null;

        const productItems = list.items && list.items[String(productId)];
        const hasAnyItems = list.items && Object.keys(list.items).length > 0;

        if (hasAnyItems && !productItems) return null;

        if (productItems) {
            let match = null;
            productItems.forEach(row => {
                if (quantity >= (parseFloat(row.min_quantity) || 1)) match = row;
            });
            const itemPrice = match ? parseFloat(match.price) || 0 : 0;
            if (itemPrice > 0) return itemPrice;
        }

        const discountValue = parseFloat(list.discount_value) || 0;
        const baseRetail = parseFloat(retailPrice) || 0;
        if (discountValue > 0 && baseRetail > 0) {
            if (list.discount_type === 'fixed') {
                return Math.max(0, baseRetail - discountValue);
            }
            if (list.discount_type === 'percentage') {
                return Math.max(0, baseRetail - (baseRetail * discountValue / 100));
            }
        }

        if (productItems) {
            let match = null;
            productItems.forEach(row => {
                if (quantity >= (parseFloat(row.min_quantity) || 1)) match = row;
            });
            return match ? parseFloat(match.price) || 0 : null;
        }

        return null;
    }

    function getSelectedBasePrice(selectedOption) {
        const tier = $('#price-tier').val() || 'retail';
        const priceListId = $('#price-list-select').val() || '';
        const quantity = parseFloat($('#quantity').val()) || 1;
        const retailPrice = parseFloat(selectedOption.data('retail')) || parseFloat(selectedOption.data('price')) || 0;
        const listPrice = getPriceListProductPrice(priceListId, selectedOption.val(), quantity, retailPrice);
        const wholesalePrice = parseFloat(selectedOption.data('wholesale')) || 0;
        const specialPrice = parseFloat(selectedOption.data('special')) || 0;

        if (tier === 'list' && listPrice !== null) {
            const list = posPriceListById.get(String(priceListId));
            return { value: listPrice, key: 'list', label: list ? list.name : 'Selected Price List' };
        }

        if (tier === 'wholesale' && wholesalePrice > 0) {
            return { value: wholesalePrice, key: 'wholesale', label: 'Wholesale' };
        }

        if (tier === 'special' && specialPrice > 0) {
            return { value: specialPrice, key: 'special', label: 'Special Discount' };
        }

        return { value: retailPrice, key: 'retail', label: 'Retail / Default' };
    }

    function applySelectedProductPricing(selectedOption) {
        if (!selectedOption || !selectedOption.val()) {
            $('#unit-price-input').val('');
            return null;
        }

        const unitMeta = resolveUnitMetrics(selectedOption);
        const basePrice = getSelectedBasePrice(selectedOption);
        const computedPrice = unitMeta.multiplier > 1 ? (basePrice.value * unitMeta.multiplier) : basePrice.value;
        $('#unit-price-input').val(computedPrice.toFixed(2));

        return { unitMeta, basePrice, computedPrice };
    }

    $(document).on('click', '.category-pill', function() {
        $('.category-pill').removeClass('active');
        $(this).addClass('active');
        filterProductCards();
    });

    function syncCategoryToggle() {
        const pillsCount = $('#category-pills .category-pill').length;
        if (pillsCount <= 7) {
            $('#category-pills').removeClass('collapsed');
            $('#category-toggle').hide();
            return;
        }

        $('#category-toggle').show().text(
            $('#category-pills').hasClass('collapsed') ? 'Show More' : 'Show Less'
        );
    }

    $('#category-toggle').on('click', function() {
        $('#category-pills').toggleClass('collapsed');
        syncCategoryToggle();
    });

    $('#quick-search').on('input', filterProductCards);

    $(document).on('click', '.product-card', function() {
        applyProductSelection($(this));
    });

    $('#product-search').on('change select2:select', function() {
        if (isSyncingProductSearch) {
            return;
        }
        const productId = $(this).val();
        if (!productId) return;
        const option = $(`#product-select option[value="${productId}"]`);
        $('#product-select').val(String(productId));
        applyProductSelection(option);
    });

    // ── Searchable product combobox (body-portal – never clipped) ─────────────
    (function () {
        const $input = $('#product-search-input');
        const $clear = $('#product-search-clear');
        const $caret = $('#product-combo-caret');

        // Append dropdown portal directly to body so it is never clipped
        const $portal = $('<div id="pos-product-dropdown-portal"></div>');
        const $list   = $('<ul id="product-search-list"></ul>');
        $portal.append($list);
        $('body').append($portal);

        let selId  = null;
        let isOpen = false;

        function buildCache() {
            return $('#product-select option[value!=""]').map(function () {
                const $o = $(this);
                return {
                    id  : String($o.val()),
                    name: String($o.data('name') || $o.text() || '').trim(),
                    sku : String($o.data('sku')  || '').trim(),
                    category: String($o.data('category-name') || '').trim(),
                    measurement: String($o.data('measurement') || '').trim(),
                    stock: String($o.data('stock') || '').trim(),
                };
            }).get();
        }
        let cache = buildCache();

        function esc(s) {
            return String(s)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function highlight(text, kw) {
            if (!kw) return esc(text);
            const idx = text.toLowerCase().indexOf(kw);
            if (idx === -1) return esc(text);
            return esc(text.slice(0, idx))
                 + '<strong>' + esc(text.slice(idx, idx + kw.length)) + '</strong>'
                 + esc(text.slice(idx + kw.length));
        }

        function renderList(items, kw) {
            $list.empty();
            if (!items.length) {
                $list.append('<li class="combo-no-results">No products found</li>');
            } else {
                items.slice(0, 500).forEach(function (item) {
                    const $li = $('<li>').attr('data-id', item.id);
                    $li.html(highlight(item.name, kw));
                    if (item.sku) {
                        $li.append($('<span class="combo-sku">').text('SKU: ' + item.sku));
                    }
                    if (item.measurement) {
                        $li.append($('<span class="combo-measure">').text(item.measurement));
                    }
                    if (item.category || item.stock) {
                        $li.append($('<span class="combo-sku">').text(`${item.category || 'Product'}${item.stock ? ' • Stock: ' + item.stock : ''}`));
                    }
                    if (item.id === selId) $li.addClass('kb-focus');
                    $list.append($li);
                });
            }
        }

        function filter(kw) {
            const k = (kw || '').toLowerCase().trim();
            if (!k) return cache;
            return cache.filter(function (item) {
                return item.name.toLowerCase().includes(k)
                    || item.sku.toLowerCase().includes(k)
                    || item.category.toLowerCase().includes(k)
                    || item.measurement.toLowerCase().includes(k);
            });
        }

        function positionPortal() {
            const rect = $input[0].getBoundingClientRect();
            const width = Math.min(Math.max(rect.width, 380), window.innerWidth - 24);
            const left = Math.min(Math.max(12, rect.left), window.innerWidth - width - 12);
            $portal.css({
                top   : rect.bottom + 2,
                left  : left,
                width : width,
            });
        }

        function openWith(kw) {
            // Re-build cache lazily in case products were late-loaded
            if (!cache.length) cache = buildCache();
            renderList(filter(kw), (kw || '').toLowerCase().trim());
            positionPortal();
            $portal.show();
            $caret.addClass('open');
            isOpen = true;
        }

        function close() {
            $portal.hide();
            $caret.removeClass('open');
            isOpen = false;
        }

        function pick(productId) {
            const $opt = $('#product-select option[value="' + productId + '"]');
            if (!$opt.length || !$opt.val()) return;
            selId = String(productId);
            $input.val(($opt.data('name') || $opt.text() || '').trim());
            $clear.show();
            $('#product-select').val(selId);
            close();
            applyProductSelection($opt);
            syncActiveProductCard(selId);
        }

        function clearCombo() {
            selId = null;
            $input.val('');
            $clear.hide();
            $('#product-select').val('');
            close();
        }

        // External sync hook — called by syncProductSearchValue / card clicks
        window._posComboSync = function (productId) {
            if (!productId) { clearCombo(); return; }
            const $opt = $('#product-select option[value="' + productId + '"]');
            if ($opt.length) {
                selId = String(productId);
                $input.val(($opt.data('name') || $opt.text() || '').trim());
                $clear.show();
                close();
            }
        };
        window._posComboOpen = function (keyword = '') {
            $input.val(keyword);
            $clear.toggle(Boolean(keyword) || !!selId);
            $input.trigger('focus');
            openWith(keyword);
        };

        // Typing → filter in real time
        $input.on('input', function () {
            const kw = $(this).val().trim();
            $clear.toggle(kw.length > 0 || !!selId);
            openWith(kw);
        });

        // Click / focus → open with current filter
        $input.on('focus click', function () {
            openWith($(this).val().trim());
        });

        // Click on a portal list item
        $list.on('click', 'li[data-id]', function () {
            pick($(this).data('id'));
        });

        // Clear button
        $clear.on('click', function (e) {
            e.stopPropagation();
            clearCombo();
            $input.focus();
        });

        // Close when clicking outside combo or portal
        $(document).on('click.posCombo', function (e) {
            if (!$(e.target).closest('#product-combo-wrapper').length &&
                !$(e.target).closest('#pos-product-dropdown-portal').length) {
                close();
            }
        });

        // Re-position on scroll / resize
        $(window).on('scroll.posCombo resize.posCombo', function () {
            if (isOpen) positionPortal();
        });

        // Keyboard navigation
        $input.on('keydown', function (e) {
            const $items   = $list.find('li[data-id]');
            const $focused = $items.filter('.kb-focus');

            if (!isOpen) {
                if (e.key === 'ArrowDown' || e.key === 'Enter') {
                    openWith($(this).val().trim());
                }
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const $next = $focused.length ? $focused.next('li[data-id]') : $items.first();
                $items.removeClass('kb-focus'); $next.addClass('kb-focus');
                kbScrollTo($next);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const $prev = $focused.length ? $focused.prev('li[data-id]') : $items.last();
                $items.removeClass('kb-focus'); $prev.addClass('kb-focus');
                kbScrollTo($prev);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if ($focused.length) pick($focused.data('id'));
            } else if (e.key === 'Escape') {
                close(); $input.blur();
            }
        });

        function kbScrollTo($item) {
            if (!$item.length) return;
            const t = $item[0].offsetTop, h = $item[0].offsetHeight,
                  dh = $portal[0].clientHeight, st = $portal[0].scrollTop;
            if (t < st) $portal[0].scrollTop = t;
            else if (t + h > st + dh) $portal[0].scrollTop = t + h - dh;
        }
    })();
    // ─────────────────────────────────────────────────────────────────────────

    // Barcode
    let barcodeBuffer = '';
    let barcodeTimeout;

    function optionMatchesBarcodeScan(option, normalizedCode) {
        const barcode = String(option.data('barcode') || '').trim().toLowerCase();
        const sku = String(option.data('sku') || '').trim().toLowerCase();
        const barcodeList = String(option.data('barcodes') || '')
            .split('|')
            .map(code => code.trim().toLowerCase())
            .filter(Boolean);

        return (barcode && barcode === normalizedCode)
            || barcodeList.includes(normalizedCode)
            || (sku && sku === normalizedCode);
    }

    function commitBarcodeScan(rawCode) {
        const normalizedCode = String(rawCode || '').trim().toLowerCase();
        if (!normalizedCode) {
            return false;
        }

        let matchedOption = null;
        $('#product-select option').each(function() {
            const option = $(this);
            if (option.val() && optionMatchesBarcodeScan(option, normalizedCode)) {
                matchedOption = option;
                return false;
            }
        });

        if (matchedOption && matchedOption.val()) {
            $('#product-select').val(matchedOption.val());
            applyProductSelection(matchedOption);
            $('#barcode-input').val('');
            barcodeBuffer = '';
            return true;
        }

        showAlert({
            icon: 'error',
            title: 'Product Not Found',
            text: `No product matched "${rawCode}"`,
            timer: 1800,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
        $('#barcode-input').select();
        return false;
    }

    $('#barcode-input').on('input', function() {
        clearTimeout(barcodeTimeout);
        barcodeBuffer = $(this).val();

        barcodeTimeout = setTimeout(() => {
            if (barcodeBuffer && barcodeBuffer.trim().length >= 3) {
                commitBarcodeScan(barcodeBuffer);
            }
        }, 220);
    });

    $('#barcode-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(barcodeTimeout);
            barcodeBuffer = $(this).val();
            commitBarcodeScan(barcodeBuffer);
        }
    });

    // Calculate
    function calculate() {
        let price = parseFloat($('#unit-price-input').val()) || 0;
        let qty = parseFloat($('#quantity').val()) || 1;
        let disc = parseFloat($('#discount').val()) || 0;
        let tax = parseFloat($('#tax').val()) || 0;
        let discType = $('#discount-type').val() || 'percent';

        let sub = price * qty;
        let discVal = discType === 'fixed' ? Math.min(disc, sub) : (sub * (disc / 100));
        let afterDisc = sub - discVal;
        let taxVal = afterDisc * (tax / 100);
        let total = afterDisc + taxVal;

        $('#item-total').text(fmt.format(total));
        return { sub, discVal, taxVal, total, discType, disc };
    }

    function calculateCartLine(item) {
        const qty = parseFloat(item.qty) || 1;
        const price = parseFloat(item.price) || 0;
        const discount = parseFloat(item.discountValue ?? item.discount) || 0;
        const discountType = item.discountType || 'percent';
        const tax = parseFloat(item.tax) || 0;

        const sub = price * qty;
        const discVal = discountType === 'fixed'
            ? Math.min(discount, sub)
            : (sub * (discount / 100));
        const afterDisc = sub - discVal;
        const taxVal = afterDisc * (tax / 100);
        const total = afterDisc + taxVal;

        item.sub = sub;
        item.discVal = discVal;
        item.taxVal = taxVal;
        item.total = total;
        item.discountType = discountType;
        item.discountValue = discount;

        return item;
    }

    // Product Change
    function applyProductSelection(source) {
        const sourceEl = source && source.jquery ? source : $(source);
        const productId = sourceEl.val() || sourceEl.data('id') || '';
        if (productId && $('#product-select').val() !== String(productId)) {
            $('#product-select').val(String(productId));
        }
        const selectOption = productId ? $(`#product-select option[value="${productId}"]`) : $();
        const opt = selectOption.length ? selectOption : sourceEl;
        if (!productId) {
            $('#unit-price-input').val('');
            $('#qty-label').text('Quantity');
            $('#unit-helper-copy').text('Select a product to unlock the right unit packs and live pricing.');
            $('#product-img').hide();
            $('#no-img').show();
            syncProductSearchValue(null);
            $('#hdr-selected-product').text('None');
            $('#quick-selected-name').text('None');
            $('#quick-selected-sku').text('-');
            $('#quick-selected-category').text('-');
            $('#quick-selected-min-stock').text('15');
            $('#quick-selected-stock').text('0');
            $('#quick-stock-health').removeClass('low').addClass('ok').text('OK');
            setUnitTypeAvailability(null);
            syncActiveProductCard(null);
            calculate();
            return;
        }

        const previousUnitProduct = $('#product-select').data('last-unit-product') || '';
        setUnitTypeAvailability(opt);
        if (String(previousUnitProduct) !== String(productId)) {
            const preferredUnitType = String(opt.data('unit-type') || 'unit').toLowerCase();
            const preferredInput = $(`#unit-type-${preferredUnitType}`);
            if (preferredInput.length && !preferredInput.prop('disabled')) {
                preferredInput.prop('checked', true);
            } else {
                $('#unit-type-unit').prop('checked', true);
            }
            $('#product-select').data('last-unit-product', String(productId));
        }
        const pricingState = applySelectedProductPricing(opt);
        const unitMeta = pricingState ? pricingState.unitMeta : resolveUnitMetrics(opt);
        const basePrice = pricingState ? pricingState.basePrice : getSelectedBasePrice(opt);
        $('#qty-label').text(`Quantity (${unitMeta.maxQty || 0} ${unitMeta.unitName} available)`);
        $('#quantity').attr('max', Math.max(unitMeta.maxQty, 0.01));
        if ((parseFloat($('#quantity').val()) || 0.01) > Math.max(unitMeta.maxQty, 0.01)) {
            $('#quantity').val(Math.max(unitMeta.maxQty, 0.01));
        }
        $('#unit-helper-copy').text(
            unitMeta.type === 'unit'
                ? `Selling in single ${unitMeta.baseUnit}${unitMeta.baseUnit.endsWith('s') ? '' : 's'} using the ${basePrice.label.toLowerCase()} price.`
                : `Each ${unitMeta.type} uses ${unitMeta.multiplier} unit(s) and the ${basePrice.label.toLowerCase()} price. Available: ${unitMeta.maxQty} ${unitMeta.unitName}.`
        );
        syncProductSearchValue(productId);
        $('#hdr-selected-product').text(opt.data('name'));
        $('#quick-selected-name').text(opt.data('name'));
        $('#quick-selected-sku').text(opt.data('sku') || '-');
        $('#quick-selected-category').text(opt.data('category-name') || 'Uncategorized');
        const minStock = parseInt(opt.data('min-stock')) || 15;
        const stock = parseInt(opt.data('stock')) || 0;
        $('#quick-selected-min-stock').text(minStock);
        $('#quick-selected-stock').text(stock);
        if (stock <= minStock) {
            $('#quick-stock-health').removeClass('ok').addClass('low').text('LOW');
        } else {
            $('#quick-stock-health').removeClass('low').addClass('ok').text('OK');
        }
        syncActiveProductCard(productId);

        if (opt.data('img')) {
            $('#product-img').attr('src', opt.data('img')).show();
            $('#no-img').hide();
        } else {
            $('#product-img').hide();
            $('#no-img').show();
        }

        maybeShowExpiryAlert({
            id: productId,
            name: opt.data('name'),
            expiry: opt.data('expiry'),
        });

        calculate();
    }

    $('#product-select').on('change', function() {
        applyProductSelection($(this).find(':selected'));
    });

    $('input[name="unit_type"]').on('change', () => $('#product-select').trigger('change'));
    $('#price-tier').on('change', () => $('#product-select').trigger('change'));
    $('#price-list-select').on('change', function () {
        if ($(this).val()) {
            $('#price-tier').val('list');
        }
        $('#product-select').trigger('change');
    });
    $(document).on('input', '#quantity, #discount, #tax', calculate);
    $(document).on('change', '#discount-type', function() {
        const type = $('#discount-type').val();
        $('#discount-helper').text(type === 'fixed' ? 'Fixed amount off item subtotal' : 'Percent of item subtotal');
        $('#discount').attr('max', type === 'fixed' ? '' : '100');
        calculate();
    });

    // Add to Cart
    $('#add-btn').on('click', function(e) {
        if (window.POS_VANILLA_BOUND) {
            return;
        }

        e.preventDefault();
        let opt = $('#product-select').find(':selected');
        if(!opt.val()) {
            showAlert({ icon: 'warning', title: 'No Product', text: 'Select a product', confirmButtonColor: '#2563eb' });
            return;
        }

        let qty = parseFloat($('#quantity').val());
        if (!Number.isFinite(qty) || qty <= 0) {
            showAlert({ icon: 'warning', title: 'Invalid Quantity', text: 'Quantity must be greater than zero.', confirmButtonColor: '#2563eb' });
            $('#quantity').val(1);
            return;
        }
        const unitMeta = resolveUnitMetrics(opt);
        let stock = opt.data('stock');
        const maxAllowed = Math.max(unitMeta.maxQty, 0);

        if(maxAllowed <= 0) {
            showAlert({ icon: 'error', title: 'Unavailable', text: `No ${unitMeta.unitName} available for this product`, confirmButtonColor: '#ef4444' });
            return;
        }

        if(qty > maxAllowed) {
            showAlert({ icon: 'error', title: 'Low Stock', text: `Only ${maxAllowed} ${unitMeta.unitName} available`, confirmButtonColor: '#ef4444' });
            return;
        }

        let res = calculate();
        const selectedPriceLevel = getSelectedBasePrice(opt);

        cart.push({
            id: opt.val(),
            name: opt.data('name'),
            qty: qty,
            priceLevel: selectedPriceLevel.key,
            priceLevelLabel: selectedPriceLevel.label,
            unitType: unitMeta.type,
            unitLabel: unitMeta.type === 'unit' ? unitMeta.baseUnit : unitMeta.type,
            stockUnits: unitMeta.multiplier * qty,
            price: parseFloat($('#unit-price-input').val()),
            discount: res.discType === 'fixed' ? 0 : (parseFloat($('#discount').val()) || 0),
            discountType: res.discType,
            discountValue: parseFloat($('#discount').val()) || 0,
            tax: parseFloat($('#tax').val()) || 0,
            sub: res.sub,
            discVal: res.discVal,
            taxVal: res.taxVal,
            total: res.total
        });

        renderCart();
        $('#product-select').val('');
        applyProductSelection($('#product-select').find(':selected'));
        $('#quantity').val(1);
        $('#discount, #tax').val(0);
        $('#discount-type').val('percent');
        $('#discount-helper').text('Percent of item subtotal');
        $('#discount').attr('max', '100');
        $('#price-tier').val($('#price-list-select').val() ? 'list' : 'retail');
        $('#barcode-input').val('').focus();

        showAlert({ icon: 'success', title: 'Added', timer: 1000, toast: true, position: 'top-end', showConfirmButton: false });
    });

    // Render Cart
    function renderCart() {
        let html = '';
        let totSub = 0, totDisc = 0, totTax = 0, totGrand = 0;

        if(cart.length) {
            cart.forEach((item, i) => {
                totSub += item.sub;
                totDisc += item.discVal;
                totTax += item.taxVal;
                totGrand += item.total;

                html += `
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold" style="color: var(--text-primary);">${item.name}</div>
                            <small style="color: var(--text-secondary); font-size: 0.75rem;">${item.qty} ${item.unitLabel || 'unit'}${item.qty === 1 ? '' : 's'} × ${fmt.format(item.price)}</small>
                            <small style="display:block; color: var(--text-tertiary); font-size: 0.7rem;">${item.priceLevelLabel || 'Retail / Default'} pricing</small>
                        </td>
                        <td class="text-center">
                            <input
                                type="number"
                                min="0.01"
                                step="1"
                                value="${item.qty}"
                                class="cart-qty-input"
                                inputmode="decimal"
                                onchange="updateCartQty(${i}, this.value)"
                                onblur="updateCartQty(${i}, this.value)"
                                onkeydown="if (event.key === 'Enter') { event.preventDefault(); updateCartQty(${i}, this.value); this.blur(); }"
                            >
                        </td>
                        <td class="text-end fw-bold tabular-nums" style="color: var(--text-primary);">${fmt.format(item.total)}</td>
                        <td class="text-center">
                            <div class="cart-actions">
                                <button class="btn btn-sm btn-cart-edit" onclick="editCartItem(${i})" title="Load item back into editor">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-remove" onclick="removeItem(${i})"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        $('#cart-body').html(html);
        const hasItems = cart.length > 0;
        const cartEmptyState = document.getElementById('cart-empty-state');

        $('.cart-wrapper').toggleClass('has-items', hasItems);

        if (cartEmptyState) {
            cartEmptyState.hidden = hasItems;
            cartEmptyState.style.display = hasItems ? 'none' : 'flex';
        }

        $('#sum-subtotal').text(fmt.format(totSub));
        $('#sum-discount').text(totDisc > 0 ? '- ' + fmt.format(totDisc) : fmt.format(0));
        $('#sum-tax').text(totTax > 0 ? '+ ' + fmt.format(totTax) : fmt.format(0));
        $('#grand-total').text(fmt.format(totGrand));
        $('#hdr-cart-count').text(cart.length);

        $('#amount-paid').val(totGrand.toFixed(2));

        updateChange();
        $('.cart-wrapper').scrollTop($('.cart-wrapper')[0].scrollHeight);
    }

    window.removeItem = function(i) {
        cart.splice(i, 1);
        renderCart();
    };

    window.updateCartQty = function(i, value) {
        if (!cart[i]) return;

        const parsedQty = parseFloat(value);
        const nextQty = Number.isFinite(parsedQty) ? Math.max(0.01, parsedQty) : Math.max(0.01, parseFloat(cart[i].qty) || 0.01);

        cart[i].qty = nextQty;
        calculateCartLine(cart[i]);
        renderCart();
    };

    window.editCartItem = function(i) {
        const item = cart[i];
        if (!item) return;

        $(`#unit-type-${item.unitType || 'unit'}`).prop('checked', true);
        $('#price-tier').val(item.priceLevel || 'retail');
        const option = $(`#product-select option[value="${item.id}"]`);
        $('#product-select').val(String(item.id));
        applyProductSelection(option);
        $('#quantity').val(item.qty);
        $('#discount-type').val(item.discountType || 'percent');
        $('#discount').val(item.discountValue ?? item.discount || 0);
        $('#discount-helper').text($('#discount-type').val() === 'fixed' ? 'Fixed amount off item subtotal' : 'Percent of item subtotal');
        $('#discount').attr('max', $('#discount-type').val() === 'fixed' ? '' : '100');
        $('#tax').val(item.tax || 0);
        $('#unit-price-input').val(item.price);
        calculate();
        $('#barcode-input').focus();
    };

    $(document).on('input', '#amount-paid, #transfer-amount, #card-amount', updateChange);
    $(document).on('change', '#payment-method', function() {
        const method = $('#payment-method').val();
        const isSplit = method === 'Split';
        $('#split-transfer-wrap').toggleClass('d-none', !isSplit);
        $('#split-transfer-account-wrap').toggleClass('d-none', !isSplit);
        $('#split-card-wrap').toggleClass('d-none', !isSplit);
        $('#split-card-account-wrap').toggleClass('d-none', !isSplit);
        updateChange();
    });

    function updateChange() {
        let total = parseFloat($('#grand-total').text().replace(/[^\d.]/g, '')) || 0;
        let cashPaid = parseFloat($('#amount-paid').val()) || 0;
        let transferPaid = parseFloat($('#transfer-amount').val()) || 0;
        let cardPaid = parseFloat($('#card-amount').val()) || 0;
        let method = $('#payment-method').val();
        let paid = method === 'Split' ? (cashPaid + transferPaid + cardPaid) : cashPaid;
        let change = paid - total;
        $('#change-amount').text(fmt.format(change)).css('color', change < 0 ? 'var(--danger-500)' : 'var(--success-500)');
    }

    function restoreProcessButton() {
        $('#process-btn').prop('disabled', false).removeClass('processing');
        $('#btn-text').show();
        $('#btn-loading').hide();
    }

    function resetPosWorkspace() {
        cart = [];
        lastSelectedProductId = null;

        $('#customer-select').val(null).trigger('change');
        $('#customer-search-input').val('');
        $('#payment-method').val('Cash');
        $('#amount-paid').prop('readonly', false).val('0.00');
        $('#transfer-amount').val('0.00');
        $('#transfer-account').val('');
        $('#card-amount').val('0.00');
        $('#card-account').val('');
        $('#deposit-account').val('');
        $('#split-transfer-wrap').addClass('d-none');
        $('#split-transfer-account-wrap').addClass('d-none');
        $('#split-card-wrap').addClass('d-none');
        $('#split-card-account-wrap').addClass('d-none');

        $('#product-select').val('');
        applyProductSelection($('#product-select').find(':selected'));
        syncProductSearchValue(null);
        $('#quick-search').val('');
        $('#barcode-input').val('');
        $('#quantity').val(1);
        $('#discount, #tax').val(0);
        $('#discount-type').val('percent');
        $('#discount-helper').text('Percent of item subtotal');
        $('#discount').attr('max', '100');
        $('#price-tier').val($('#price-list-select').val() ? 'list' : 'retail');
        $('#unit-type-unit').prop('checked', true).trigger('change');

        filterProductCards();
        renderCart();
        restoreProcessButton();

        setTimeout(() => $('#barcode-input').trigger('focus'), 50);
    }

    function submitPosSale(total, paid) {
        $('#process-btn').prop('disabled', true).addClass('processing');
        $('#btn-text').hide();
        $('#btn-loading').show();

        $.ajax({
            url: "{{ $posSaleStoreUrl ?? url('/sales') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                customer_id: $('#customer-select').val(),
                payment_method: $('#payment-method').val(),
                deposit_account_id: isStarterPos ? null : ($('#deposit-account').val() || null),
                items: cart,
                subtotal: cart.reduce((s, i) => s + i.sub, 0),
                tax: cart.reduce((s, i) => s + i.taxVal, 0),
                discount: cart.reduce((s, i) => s + i.discVal, 0),
                total: total,
                paid: paid,
                split_details: {
                    cash: parseFloat($('#amount-paid').val()) || 0,
                    transfer: parseFloat($('#transfer-amount').val()) || 0,
                    transfer_account_id: isStarterPos ? null : ($('#transfer-account').val() || null),
                    card: parseFloat($('#card-amount').val()) || 0,
                    card_account_id: isStarterPos ? null : ($('#card-account').val() || null)
                }
            },
            success: function(res) {
                const invoiceUrl = "{{ url('/sales/invoice') }}/" + res.sale_id + "/print?autoprint=1";
                const balanceDue = Math.max(0, total - paid);
                window.open(invoiceUrl, '_blank');

                resetPosWorkspace();

                showAlert({
                    icon: 'success',
                    title: balanceDue > 0 ? 'Deposit recorded' : 'Sale completed',
                    text: balanceDue > 0
                        ? 'Receipt opened. Remaining balance: ' + fmt.format(balanceDue)
                        : 'Receipt opened. POS is ready for the next sale.',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            },
            error: function(xhr) {
                restoreProcessButton();
                showAlert({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed', confirmButtonColor: '#ef4444' });
            }
        });
    }

    // Process Sale
    $('#process-btn').on('click', function(e) {
        if (window.POS_VANILLA_BOUND) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        if(!cart.length) {
            showAlert({ icon: 'warning', title: 'Cart Empty', confirmButtonColor: '#f59e0b' });
            return;
        }

        let total = parseFloat($('#grand-total').text().replace(/[^\d.]/g, '')) || 0;
        let cashPaid = parseFloat($('#amount-paid').val()) || 0;
        let transferPaid = parseFloat($('#transfer-amount').val()) || 0;
        let method = $('#payment-method').val();
        let paid = method === 'Split' ? (cashPaid + transferPaid) : cashPaid;

        if(paid <= 0) {
            showAlert({ icon: 'warning', title: 'Enter Payment', text: 'Enter the amount received before processing this sale.', confirmButtonColor: '#f59e0b' });
            return;
        }

        submitPosSale(total, paid);
    });

    syncCategoryToggle();
    filterProductCards();
    setUnitTypeAvailability(null);
});
</script>

<script>
window.POS_ENABLE_FALLBACK = function () {
    if (window.POS_VANILLA_BOUND) return;
    window.POS_VANILLA_BOUND = true;

    if (window.jQuery) {
        window.jQuery('#add-btn').off('click');
        window.jQuery('#process-btn').off('click');
    }

    const productCards = document.querySelectorAll('.product-card');
    const categoryPills = document.querySelectorAll('.category-pill');
    const categoryToggle = document.getElementById('category-toggle');
    const categoryPillsWrap = document.getElementById('category-pills');
    const isStarterPos = @json($isStarterPos ?? false);
    const posExpiryAlertsEnabled = @json($posExpiryAlertsEnabled ?? false);
    const posExpiryAlertMonths = Number(@json($posExpiryAlertMonths ?? 1)) || 1;
    const quickSearch = document.getElementById('quick-search');
    const barcodeInput = document.getElementById('barcode-input');
    const productSelect = document.getElementById('product-select');
    const productSearch = document.getElementById('product-search');
    const productSearchInput = document.getElementById('product-search-input');
    const unitTypeInputs = document.querySelectorAll('input[name="unit_type"]');
    const priceTierInput = document.getElementById('price-tier');
    const priceListInput = document.getElementById('price-list-select');
    const priceInput = document.getElementById('unit-price-input');
    const qtyInput = document.getElementById('quantity');
    const discountInput = document.getElementById('discount');
    const discountTypeInput = document.getElementById('discount-type');
    const taxInput = document.getElementById('tax');
    let addBtn = document.getElementById('add-btn');
    const cartBody = document.getElementById('cart-body');
    const productImg = document.getElementById('product-img');
    const noImg = document.getElementById('no-img');
    const quickName = document.getElementById('quick-selected-name');
    const quickSku = document.getElementById('quick-selected-sku');
    const quickCategory = document.getElementById('quick-selected-category');
    const quickMinStock = document.getElementById('quick-selected-min-stock');
    const quickStock = document.getElementById('quick-selected-stock');
    const quickHealth = document.getElementById('quick-stock-health');
    const hdrSelected = document.getElementById('hdr-selected-product');
    const qtyLabel = document.getElementById('qty-label');
    const unitHelperCopy = document.getElementById('unit-helper-copy');

    function maybeShowExpiryAlert(payload) {
        const expiry = String(payload?.expiry || '').trim();
        if (!expiry) {
            return;
        }

        const expiryDate = new Date(expiry + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (Number.isNaN(expiryDate.getTime())) {
            return;
        }

        const diffMs = expiryDate.getTime() - today.getTime();
        const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays <= 0) {
            const key = `${payload?.id || payload?.name || 'product'}:${expiry}:expired`;
            if (window.__posLastExpiryAlertKey === key) {
                return;
            }
            window.__posLastExpiryAlertKey = key;
            showAlert({
                icon: 'error',
                title: 'Product Expired!',
                html: `<b>${payload?.name || 'This product'}</b> expired on <b>${expiry}</b>.<br>Please check your inventory before selling.`,
                confirmButtonText: 'Understood',
            });
            return;
        }

        if (!posExpiryAlertsEnabled) {
            return;
        }

        const notifyFrom = new Date(expiryDate.getTime());
        notifyFrom.setMonth(notifyFrom.getMonth() - posExpiryAlertMonths);

        if (today < notifyFrom) {
            return;
        }

        const key = `${payload?.id || payload?.name || 'product'}:${expiry}:near:${posExpiryAlertMonths}`;
        if (window.__posLastExpiryAlertKey === key) {
            return;
        }
        window.__posLastExpiryAlertKey = key;

        showAlert({
            icon: 'warning',
            title: 'Near Expiry!',
            html: `<b>${payload?.name || 'This product'}</b> expires on <b>${expiry}</b> (<b>${diffDays}</b> day${diffDays === 1 ? '' : 's'} left). Notification window: <b>${posExpiryAlertMonths} month${posExpiryAlertMonths === 1 ? '' : 's'}</b>.`,
            timer: 5000,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
        });
    }
    const vanillaPriceLists = @json($priceListData ?? []);
    const vanillaPriceListById = new Map(vanillaPriceLists.map(list => [String(list.id), list]));
    const hdrShelfCount = document.getElementById('hdr-shelf-count');
    const productCount = document.getElementById('product-count');
    const sumSubtotal = document.getElementById('sum-subtotal');
    const sumDiscount = document.getElementById('sum-discount');
    const sumTax = document.getElementById('sum-tax');
    const grandTotal = document.getElementById('grand-total');
    const hdrCartCount = document.getElementById('hdr-cart-count');
    const amountPaid = document.getElementById('amount-paid');
    const paymentMethod = document.getElementById('payment-method');
    const paymentTabs = document.querySelectorAll('.pos-pay-tab[data-payment-method]');
    const depositAccount = document.getElementById('deposit-account');
    const transferAmount = document.getElementById('transfer-amount');
    const transferAccount = document.getElementById('transfer-account');
    const cardAmount = document.getElementById('card-amount');
	    const cardAccount = document.getElementById('card-account');
	    const walletAmount = document.getElementById('wallet-amount');
	    const walletPaymentWrap = document.getElementById('wallet-payment-wrap');
	    const walletBalanceHint = document.getElementById('wallet-balance-hint');
	    const customerWalletHint = document.getElementById('customer-wallet-hint');
	    const splitTransferWrap = document.getElementById('split-transfer-wrap');
    const splitTransferAccountWrap = document.getElementById('split-transfer-account-wrap');
    const splitCardWrap = document.getElementById('split-card-wrap');
    const splitCardAccountWrap = document.getElementById('split-card-account-wrap');
    const changeAmount = document.getElementById('change-amount');
    const customerSelect = document.getElementById('customer-select');
    const customerSearchInput = document.getElementById('customer-search-input');
    const railWantBtn = document.getElementById('rail-want-btn');
    const railScanBtn = document.getElementById('rail-scan-btn');
    const railSearchBtn = document.getElementById('rail-search-btn');
    const railMiscBtn = document.getElementById('rail-misc-btn');
    const railDiscountBtn = document.getElementById('rail-discount-btn');
    const railCustomerBtn = document.getElementById('rail-customer-btn');
    const railCalculatorBtn = document.getElementById('rail-calculator-btn');
    const railReprintBtn = document.getElementById('rail-reprint-btn');
    const railPriceCheckBtn = document.getElementById('rail-price-check-btn');
    const railClearCartBtn = document.getElementById('rail-clear-cart-btn');
    const railCheckoutBtn = document.getElementById('rail-checkout-btn');
    const railMessagesBtn = document.getElementById('rail-messages-btn');
    let processBtn = document.getElementById('process-btn');
    const saveInvoiceBtn = document.getElementById('save-invoice-btn');
    const newSaleBtn = document.getElementById('new-sale-btn');
    let btnText = document.getElementById('btn-text');
    let btnLoading = document.getElementById('btn-loading');
    const itemTotal = document.getElementById('item-total');
    const fmt = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' });
    const cart = [];
    let currentProductId = '';
    let activePaymentTab = 'Cash';
    let saveOnlyMode = false;

    const alertFallback = (message) => window.alert(message);
    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[char]);
	    const showAlert = (options) => {
        if (window.Swal && typeof Swal.fire === 'function') {
            return Swal.fire(options);
        }
        if (options?.icon === 'success') {
            return;
        }
        return alertFallback(options?.text || options?.title || 'Action required');
    };
	    const saleStoreUrl = @json($posSaleStoreUrl ?? url('/sales'));
	    const invoicePrintBaseUrl = @json(url('/sales/invoice'));
    const posSalesLogUrl = @json($posSalesLogUrl ?? url('/pos/sales'));
    const posPermissions = {
        sell: @json((bool) $posCanSell),
        discount: @json((bool) $posCanDiscount),
        customers: @json((bool) $posCanManageCustomers),
        addProduct: @json((bool) $posCanAddProduct),
    };

    function requirePosPermission(flag, message) {
        if (posPermissions[flag]) {
            return true;
        }

        showAlert({
            icon: 'warning',
            title: 'Permission required',
            text: message || 'You do not have permission to perform this POS action.',
            confirmButtonColor: '#0f3a8a',
        });

        return false;
    }

	    function selectedCustomerWalletBalance() {
	        const option = customerSelect?.options[customerSelect.selectedIndex];
	        return Math.max(0, parseFloat(option?.dataset?.wallet || '0') || 0);
	    }

	    function cartGrandTotalValue() {
	        const totalText = grandTotal?.textContent || '0';
	        return parseFloat(totalText.replace(/[^\d.]/g, '')) || 0;
	    }

    function moneyValue(input) {
        return Math.max(0, parseFloat(input?.value || '0') || 0);
    }

    function normalizeMoneyInput(input) {
        if (!input) return 0;

        const value = moneyValue(input);
        input.value = value.toFixed(2);

        return value;
    }

	    function refreshWalletApplication() {
	        const total = cartGrandTotalValue();
	        const walletBalance = selectedCustomerWalletBalance();
	        const applied = Math.min(walletBalance, total);

	        if (walletAmount) {
	            walletAmount.value = applied.toFixed(2);
	        }
	        walletPaymentWrap?.classList.toggle('d-none', applied <= 0);
	        if (walletBalanceHint) {
	            walletBalanceHint.textContent = `Available wallet balance: ${fmt.format(walletBalance)}. Applying: ${fmt.format(applied)}.`;
	        }
	        if (customerWalletHint) {
	            customerWalletHint.textContent = walletBalance > 0
	                ? `Wallet credit available: ${fmt.format(walletBalance)}. It will apply before collecting cash/transfer.`
	                : 'Select a customer to apply available wallet credit automatically.';
	        }

	        if (paymentMethod?.value !== 'Split' && amountPaid) {
	            amountPaid.value = Math.max(0, total - applied).toFixed(2);
	        }

	        return applied;
    }
    const csrfToken = @json(csrf_token());
	    const salesOrderPrefill = @json(session('pos_prefill'));
	    const posSourceContext = salesOrderPrefill && salesOrderPrefill.source
	        ? {
            source: salesOrderPrefill.source,
            source_id: salesOrderPrefill.source_id || null,
            reference: salesOrderPrefill.reference || null,
	        }
	        : null;
	    let splitAutoSync = false;

    function replaceNodeWithClone(node) {
        if (!node || !node.parentNode) {
            return node;
        }

        const clone = node.cloneNode(true);
        node.parentNode.replaceChild(clone, node);
        return clone;
    }

    if (isStarterPos) {
        // Starter POS uses its own streamlined flow, so strip any legacy listeners
        // by rebinding on fresh nodes before attaching the active handlers below.
        addBtn = replaceNodeWithClone(addBtn);
        processBtn = replaceNodeWithClone(processBtn);
        btnText = document.getElementById('btn-text');
        btnLoading = document.getElementById('btn-loading');
    }

    railWantBtn?.addEventListener('click', () => {
        const offcanvasEl = document.getElementById('ai-quick-agent-offcanvas');
        const input = document.getElementById('ai-agent-input');
        const starters = document.getElementById('ai-agent-starters');

        if (starters && starters.dataset.posPrompts !== 'ready') {
            const aiPerms = window.SPB_AI_PERMISSIONS || {};
            const posAiPrompts = [
                aiPerms.sales ? ['Sales Today', 'total sales today'] : null,
                aiPerms.customers ? ['Customers Today', 'customer count today'] : null,
                aiPerms.payments ? ['Payments Today', 'payments today'] : null,
                aiPerms.products ? ['Products Count', 'product count'] : null,
            ].filter(Boolean);

            posAiPrompts.forEach(([label, prompt]) => {
                if (starters.querySelector(`[data-prompt="${prompt}"]`)) {
                    return;
                }

                const button = document.createElement('button');
                button.className = 'btn btn-sm btn-light border ai-starter-chip';
                button.type = 'button';
                button.dataset.prompt = prompt;
                button.textContent = label;
                starters.prepend(button);
            });
            starters.dataset.posPrompts = 'ready';
        }

        if (typeof window.openSmartProbookAiAssistant === 'function') {
            window.openSmartProbookAiAssistant();
            return;
        }

        if (offcanvasEl && window.bootstrap?.Offcanvas) {
            window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).show();
            setTimeout(() => input?.focus(), 250);
            return;
        }

        document.getElementById('ai-agent-trigger')?.click();
    });
    function openQuickPickItems(keyword = '') {
        if (!requirePosPermission('sell', 'You do not have permission to pick or sell POS items.')) {
            return;
        }

        if (quickSearch) {
            quickSearch.value = keyword;
        }
        document.querySelectorAll('.category-pill').forEach((node) => {
            node.classList.toggle('active', node.dataset.category === 'all');
        });
        filterProductCards();
        document.querySelector('.controls-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });

        window.setTimeout(() => {
            if (typeof window._posComboOpen === 'function') {
                window._posComboOpen(keyword);
                return;
            }
            productSearchInput?.focus();
        }, 180);
    }

    async function openSellMiscItem() {
        if (!requirePosPermission('sell', 'You do not have permission to sell miscellaneous items.')) {
            return;
        }

        const options = productSelect
            ? Array.from(productSelect.options).filter((option) => {
                if (!option.value) {
                    return false;
                }
                const haystack = [
                    option.dataset.name,
                    option.dataset.sku,
                    option.dataset.categoryName,
                    option.textContent,
                ].join(' ').toLowerCase();
                return haystack.includes('misc') || haystack.includes('custom') || haystack.includes('service');
            })
            : [];

        if (!options.length) {
            showAlert({
                icon: 'info',
                title: 'No misc item found',
                text: 'Search existing products or ask an admin to create the miscellaneous item first.',
                confirmButtonColor: '#0f3a8a',
            });
            openQuickPickItems('misc');
            return;
        }

        if (!window.Swal || typeof Swal.fire !== 'function') {
            const first = options[0];
            productSelect.value = first.value;
            applyVanillaSelection({ dataset: { ...first.dataset, id: first.value }});
            productSearchInput?.focus();
            return;
        }

        const optionHtml = options.map((option) => {
            const label = `${option.dataset.name || option.textContent || 'Misc item'}${option.dataset.sku ? ' - ' + option.dataset.sku : ''}`;
            return `<option value="${option.value}">${escapeHtml(label)}</option>`;
        }).join('');

        const result = await Swal.fire({
            title: 'Sell Misc Item',
            html: `
                <div class="text-start">
                    <label class="form-label fw-bold">Catalog item</label>
                    <select id="misc-product-id" class="form-select mb-2">${optionHtml}</select>
                    <label class="form-label fw-bold">Quantity</label>
                    <input id="misc-qty" type="number" min="0.01" step="1" value="1" class="form-control mb-2">
                    <label class="form-label fw-bold">Selling price</label>
                    <input id="misc-price" type="number" min="0" step="0.01" class="form-control mb-2" placeholder="Leave blank to use product price">
                    <label class="form-label fw-bold">Discount</label>
                    <input id="misc-discount" type="number" min="0" step="0.01" value="0" class="form-control">
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Add to cart',
            confirmButtonColor: '#0f3a8a',
            preConfirm: () => {
                const productId = document.getElementById('misc-product-id')?.value || '';
                const qty = parseFloat(document.getElementById('misc-qty')?.value || '1') || 0;
                const price = parseFloat(document.getElementById('misc-price')?.value || '0') || 0;
                const discount = parseFloat(document.getElementById('misc-discount')?.value || '0') || 0;
                if (!productId || qty <= 0) {
                    Swal.showValidationMessage('Choose an item and enter a valid quantity.');
                    return false;
                }
                return { productId, qty, price, discount };
            },
        });

        if (!result.isConfirmed || !result.value) {
            return;
        }

        const option = findOptionById(result.value.productId);
        if (!option) {
            return;
        }
        productSelect.value = option.value;
        applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
        if (qtyInput) {
            qtyInput.value = String(result.value.qty);
        }
        if (result.value.price > 0 && priceInput) {
            priceInput.value = result.value.price.toFixed(2);
        }
        if (discountInput) {
            discountInput.value = String(result.value.discount);
        }
        updateItemTotal();
        addBtn?.click();
    }

    async function openDiscountPanel() {
        if (!requirePosPermission('discount', 'You do not have permission to give discounts.')) {
            return;
        }

        if (!discountInput || !discountTypeInput) {
            return;
        }

        if (!window.Swal || typeof Swal.fire !== 'function') {
            discountInput.focus();
            return;
        }

        const result = await Swal.fire({
            title: 'Give Discount',
            html: `
                <div class="text-start">
                    <label class="form-label fw-bold">Discount type</label>
                    <select id="pos-discount-type-prompt" class="form-select mb-2">
                        <option value="percent">Percent (%)</option>
                        <option value="fixed">Fixed amount (₦)</option>
                    </select>
                    <label class="form-label fw-bold">Discount value</label>
                    <input id="pos-discount-value-prompt" type="number" min="0" step="0.01" value="${escapeHtml(discountInput.value || '0')}" class="form-control">
                    <small class="text-muted d-block mt-2">Applies to the selected product entry. If no product is selected, it updates the last cart item.</small>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Apply Discount',
            confirmButtonColor: '#0f3a8a',
            preConfirm: () => {
                const type = document.getElementById('pos-discount-type-prompt')?.value || 'percent';
                const value = parseFloat(document.getElementById('pos-discount-value-prompt')?.value || '0') || 0;
                if (value < 0 || (type === 'percent' && value > 100)) {
                    Swal.showValidationMessage('Enter a valid discount value.');
                    return false;
                }
                return { type, value };
            },
        });

        if (!result.isConfirmed || !result.value) {
            return;
        }

        const { type, value } = result.value;
        if (currentProductId || productSelect?.value) {
            discountTypeInput.value = type;
            discountInput.value = String(value);
            updateItemTotal();
            discountInput.focus();
            return;
        }

        const lastItem = cart[cart.length - 1];
        if (lastItem) {
            lastItem.discountType = type;
            lastItem.discountValue = value;
            calculateCartItem(lastItem);
            renderCart();
        }
    }

    async function openCustomerPicker() {
        if (!requirePosPermission('customers', 'You do not have permission to select or manage customers.')) {
            return;
        }

        if (!customerSelect) {
            return;
        }

        const options = Array.from(customerSelect.options || []);
        if (!window.Swal || typeof Swal.fire !== 'function') {
            customerSelect.focus();
            return;
        }

        const optionHtml = options.map((option) => {
            const wallet = parseFloat(option.dataset.wallet || '0') || 0;
            const walletLabel = wallet > 0 ? ` - Wallet ${fmt.format(wallet)}` : '';
            return `<option value="${escapeHtml(option.value)}" ${option.selected ? 'selected' : ''}>${escapeHtml(option.textContent || 'Walk-in Customer')}${escapeHtml(walletLabel)}</option>`;
        }).join('');

        const result = await Swal.fire({
            title: 'Select Customer',
            html: `
                <div class="text-start">
                    <label class="form-label fw-bold">Customer</label>
                    <select id="pos-customer-prompt" class="form-select">${optionHtml}</select>
                    <small class="text-muted d-block mt-2">Wallet credit applies automatically when available.</small>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Use Customer',
            confirmButtonColor: '#0f3a8a',
            focusConfirm: false,
            preConfirm: () => document.getElementById('pos-customer-prompt')?.value || '',
        });

        if (!result.isConfirmed) {
            return;
        }

        customerSelect.value = result.value || '';
        customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
        if (customerSearchInput) {
            customerSearchInput.value = customerSelect.options[customerSelect.selectedIndex]?.textContent || '';
        }
    }

    function openCheckoutPanel() {
        if (!requirePosPermission('sell', 'You do not have permission to checkout POS sales.')) {
            return;
        }

        if (!cart.length) {
            alertFallback('Add an item to cart before checkout.');
            return;
        }

        document.querySelector('.summary-panel')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        updateChange();
        window.setTimeout(() => {
            if (!isStarterPos && depositAccount && !depositAccount.value) {
                depositAccount.focus();
                return;
            }
            amountPaid?.focus();
        }, 180);
    }

    function showPosMessages() {
        if (!requirePosPermission('sell', 'You do not have permission to view POS sale messages.')) {
            return;
        }

        const visibleProducts = Array.from(productCards || []).filter((card) => card.style.display !== 'none');
        const lowStockItems = visibleProducts.filter((card) => {
            const stock = parseFloat(card.dataset.stock || '0') || 0;
            const minStock = parseFloat(card.dataset.minStock || '0') || 0;
            return minStock > 0 && stock <= minStock;
        }).slice(0, 4);
        const customerName = customerSelect?.options[customerSelect.selectedIndex]?.textContent || 'Walk-in Customer';
        const walletBalance = selectedCustomerWalletBalance();
        const total = cartGrandTotalValue();
        const lowStockText = lowStockItems.length
            ? `<li><strong>Low stock:</strong> ${lowStockItems.map((card) => escapeHtml(card.dataset.name || 'Product')).join(', ')}</li>`
            : '<li><strong>Stock:</strong> No visible low-stock alert.</li>';

        showAlert({
            icon: 'info',
            title: 'POS Messages',
            html: `
                <ul class="text-start mb-0 ps-3">
                    <li><strong>Cart:</strong> ${cart.length} item(s), ${fmt.format(total)}</li>
                    <li><strong>Customer:</strong> ${escapeHtml(customerName)}${walletBalance > 0 ? `, wallet ${fmt.format(walletBalance)}` : ''}</li>
                    ${lowStockText}
                    <li><strong>Tip:</strong> Double-click a shelf item to add it quickly.</li>
                </ul>
            `,
            confirmButtonColor: '#0f3a8a'
        });
    }

    function openPosCalculator() {
        if (!window.Swal || typeof Swal.fire !== 'function') {
            alertFallback('Calculator is available when the POS alert panel is loaded.');
            return;
        }

        Swal.fire({
            title: 'Calculator',
            html: `
                <div class="text-start">
                    <label class="form-label fw-bold">Calculation</label>
                    <input id="pos-calculator-expression" class="form-control" inputmode="decimal" placeholder="Example: 3400 * 5">
                    <div class="mt-3 p-3 rounded border bg-light">
                        <div class="small text-muted fw-bold text-uppercase">Result</div>
                        <div id="pos-calculator-result" class="fs-4 fw-bold">0</div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Close',
            confirmButtonColor: '#0f3a8a',
            didOpen: () => {
                const input = document.getElementById('pos-calculator-expression');
                const result = document.getElementById('pos-calculator-result');
                const update = () => {
                    const expression = String(input?.value || '').trim();
                    if (!expression) {
                        result.textContent = '0';
                        return;
                    }
                    if (!/^[0-9+\-*/().\s]+$/.test(expression)) {
                        result.textContent = 'Use numbers and + - * / only';
                        return;
                    }
                    try {
                        const value = Function(`"use strict"; return (${expression});`)();
                        result.textContent = Number.isFinite(value) ? new Intl.NumberFormat('en-NG').format(value) : 'Invalid';
                    } catch (error) {
                        result.textContent = 'Invalid';
                    }
                };
                input?.addEventListener('input', update);
                window.setTimeout(() => input?.focus(), 80);
            },
        });
    }

    function openReprintLookup() {
        if (!requirePosPermission('sell', 'You do not have permission to reprint POS receipts.')) {
            return;
        }

        if (!window.Swal || typeof Swal.fire !== 'function') {
            window.location.href = posSalesLogUrl;
            return;
        }

        Swal.fire({
            title: 'Reprint Receipt',
            input: 'text',
            inputLabel: 'Invoice or receipt number',
            inputPlaceholder: 'Type invoice number',
            showCancelButton: true,
            confirmButtonText: 'Find Receipt',
            confirmButtonColor: '#0f3a8a',
            inputValidator: (value) => (!String(value || '').trim() ? 'Enter the invoice or receipt number.' : undefined),
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            const invoiceNo = String(result.value || '').trim();
            const url = new URL(posSalesLogUrl, window.location.origin);
            url.searchParams.set('invoice_no', invoiceNo);
            url.searchParams.set('reprint', '1');
            window.location.href = url.toString();
        });
    }

    function openPriceCheck() {
        if (!requirePosPermission('sell', 'You do not have permission to check POS prices.')) {
            return;
        }

        openQuickPickItems('');
        showAlert({
            icon: 'info',
            title: 'Price check',
            text: 'Search and select a product to view its price. It will not enter the cart until Add to Cart is pressed.',
            timer: 2200,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
        });
    }

    function confirmClearCart() {
        if (!requirePosPermission('sell', 'You do not have permission to clear POS carts.')) {
            return;
        }

        if (!cart.length) {
            showAlert({
                icon: 'info',
                title: 'Cart already empty',
                timer: 1200,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
            return;
        }

        if (!window.Swal || typeof Swal.fire !== 'function') {
            if (window.confirm('Clear the current cart?')) {
                resetVanillaPosWorkspace();
            }
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Clear current cart?',
            text: 'This only clears the unsaved POS cart.',
            showCancelButton: true,
            confirmButtonText: 'Clear Cart',
            confirmButtonColor: '#0f3a8a',
        }).then((result) => {
            if (result.isConfirmed) {
                resetVanillaPosWorkspace();
            }
        });
    }

    railSearchBtn?.addEventListener('click', () => openQuickPickItems(''));
    railMiscBtn?.addEventListener('click', openSellMiscItem);
    railDiscountBtn?.addEventListener('click', openDiscountPanel);
    railCustomerBtn?.addEventListener('click', openCustomerPicker);
    railCalculatorBtn?.addEventListener('click', openPosCalculator);
    railReprintBtn?.addEventListener('click', openReprintLookup);
    railPriceCheckBtn?.addEventListener('click', openPriceCheck);
    railClearCartBtn?.addEventListener('click', confirmClearCart);
    railCheckoutBtn?.addEventListener('click', openCheckoutPanel);
    railMessagesBtn?.addEventListener('click', showPosMessages);
    railScanBtn?.addEventListener('click', () => {
        if (!requirePosPermission('sell', 'You do not have permission to scan POS items.')) {
            return;
        }

        barcodeInput?.focus();
        showAlert({
            icon: 'info',
            title: 'Scanner ready',
            text: 'Scan or type the barcode, then press Enter.',
            timer: 1800,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
        });
    });
	    const hasProductSelect2 = Boolean(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && productSearch);
	    if (hasProductSelect2) {
	        window.jQuery(productSearch).select2({
	            width: '100%',
	            placeholder: 'Search product by name, SKU or barcode...',
	            allowClear: true,
	            dropdownCssClass: 'pos-product-dropdown',
	            matcher: function (params, data) {
	                const term = String(params.term || '').toLowerCase().trim();
	                if (!term || !data.id) {
	                    return data;
	                }

	                const option = findOptionById(data.id);
	                const meta = option?.dataset || {};
	                const haystack = [
	                    data.text,
	                    meta.name,
	                    meta.sku,
	                    meta.barcode,
	                    meta.barcodes,
	                    meta.categoryName,
	                ].join(' ').toLowerCase();

	                return haystack.includes(term) ? data : null;
	            },
	        });
	    }

	    function syncProductSearchDropdown(value = '') {
	        // Sync the custom combo input whenever a product is selected externally
	        if (typeof window._posComboSync === 'function') {
	            window._posComboSync(value ? String(value) : '');
	        }
	        if (!productSearch) {
	            return;
	        }
	        productSearch.value = value ? String(value) : '';
	        if (hasProductSelect2) {
	            window.jQuery(productSearch).val(value ? String(value) : null).trigger('change.select2');
	        }
	    }
	    const customerOptionsSnapshot = customerSelect
        ? Array.from(customerSelect.options).map((option) => ({
            value: option.value,
            label: option.textContent || '',
        }))
        : [];

    function filterCustomerOptions(keyword) {
        if (!customerSelect) {
            return [];
        }

        const query = String(keyword || '').toLowerCase().trim();
        const selectedValue = customerSelect.value;
        const filteredOptions = customerOptionsSnapshot.filter((option) => {
            if (option.value === '') {
                return true;
            }

            return query === '' || option.label.toLowerCase().includes(query);
        });

        customerSelect.innerHTML = '';
        filteredOptions.forEach((option) => {
            const node = document.createElement('option');
            node.value = option.value;
            node.textContent = option.label;
            if (option.value === selectedValue) {
                node.selected = true;
            }
            customerSelect.appendChild(node);
        });

        if (selectedValue && !filteredOptions.some((option) => option.value === selectedValue)) {
            customerSelect.value = '';
        }

        return filteredOptions;
    }

    function selectCustomerMatch(keyword, preferExact = false) {
        if (!customerSelect) {
            return false;
        }

        const query = String(keyword || '').toLowerCase().trim();
        if (query === '') {
            customerSelect.value = '';
            return false;
        }

        const matches = filterCustomerOptions(query).filter((option) => option.value !== '');
        if (!matches.length) {
            customerSelect.value = '';
            return false;
        }

        const exactMatch = matches.find((option) => option.label.toLowerCase() === query);
        const startsWithMatch = matches.find((option) => option.label.toLowerCase().startsWith(query));
        const selectedMatch = preferExact ? (exactMatch || startsWithMatch || matches[0]) : (startsWithMatch || exactMatch || matches[0]);

        if (!selectedMatch) {
            return false;
        }

        customerSelect.value = selectedMatch.value;
        customerSelect.dispatchEvent(new Event('change', { bubbles: true }));

        return true;
    }

    function getSelectedUnitType() {
        const active = document.querySelector('input[name="unit_type"]:checked');
        return active ? active.value : 'unit';
    }

    function getVanillaPriceListProductPrice(listId, productId, quantity, retailPrice) {
        const list = vanillaPriceListById.get(String(listId || ''));
        if (!list) return null;

        const productItems = list.items && list.items[String(productId)];
        const hasAnyItems = list.items && Object.keys(list.items).length > 0;

        // If price list has specific items but this product is not among them, skip
        if (hasAnyItems && !productItems) return null;

        // Use item-specific price if set to a positive value
        if (productItems) {
            let match = null;
            productItems.forEach(row => {
                if (quantity >= (parseFloat(row.min_quantity) || 1)) match = row;
            });
            const itemPrice = match ? parseFloat(match.price) || 0 : 0;
            if (itemPrice > 0) return itemPrice;
        }

        // Apply global discount_type / discount_value to the retail price
        const discountValue = parseFloat(list.discount_value) || 0;
        const baseRetail = parseFloat(retailPrice) || 0;
        if (discountValue > 0 && baseRetail > 0) {
            if (list.discount_type === 'fixed') {
                return Math.max(0, baseRetail - discountValue);
            }
            if (list.discount_type === 'percentage') {
                return Math.max(0, baseRetail - (baseRetail * discountValue / 100));
            }
        }

        // Product is in items but has no price and no global discount — return 0
        if (productItems) {
            let match = null;
            productItems.forEach(row => {
                if (quantity >= (parseFloat(row.min_quantity) || 1)) match = row;
            });
            return match ? parseFloat(match.price) || 0 : null;
        }

        return null;
    }

    function getBasePrice(data) {
        const tier = priceTierInput?.value || 'retail';
        const productId = data.id || '';
        const quantity = parseFloat(qtyInput?.value || '1') || 1;
        const priceListId = priceListInput?.value || '';
        const retail = parseFloat(data.retail || data.price || '0') || 0;
        const listPrice = getVanillaPriceListProductPrice(priceListId, productId, quantity, retail);
        const wholesale = parseFloat(data.wholesale || '0') || 0;
        const special = parseFloat(data.special || '0') || 0;

        if (tier === 'list' && listPrice !== null) {
            const list = vanillaPriceListById.get(String(priceListId));
            return { value: listPrice, key: 'list', label: list ? list.name : 'Selected Price List' };
        }

        if (tier === 'wholesale' && wholesale > 0) {
            return { value: wholesale, key: 'wholesale', label: 'Wholesale' };
        }

        if (tier === 'special' && special > 0) {
            return { value: special, key: 'special', label: 'Special Discount' };
        }

        return { value: retail, key: 'retail', label: 'Retail / Default' };
    }

    function getUnitMetrics(data) {
        const type = getSelectedUnitType();
        const stock = parseFloat(data.stock || '0') || 0;
        const rollsPerCarton = Math.max(parseFloat(data.upc || '0') || 0, 0);
        const unitsPerRoll = Math.max(parseFloat(data.upr || '0') || 0, 0);
        const baseUnit = String(data.baseUnit || 'unit');
        const cartonUnits = rollsPerCarton > 0
            ? (unitsPerRoll > 0 ? (rollsPerCarton * unitsPerRoll) : rollsPerCarton)
            : 0;

        let multiplier = 1;
        let unitLabel = baseUnit;

        if (type === 'carton' && cartonUnits > 0) {
            multiplier = cartonUnits;
            unitLabel = 'carton';
        } else if (type === 'roll' && unitsPerRoll > 0) {
            multiplier = unitsPerRoll;
            unitLabel = 'roll';
        }

        const maxQty = multiplier > 0 ? stock / multiplier : stock;

        return {
            type,
            stock,
            multiplier,
            unitLabel,
            baseUnit,
            maxQty,
            cartonUnits,
            unitsPerRoll,
        };
    }

    function findOptionById(id) {
        if (!productSelect || !id) return null;
        return Array.from(productSelect.options).find((option) => option.value === String(id)) || null;
    }

    function optionData(option) {
        return option ? option.dataset || {} : {};
    }

    function datasetMatchesBarcodeScan(data, normalizedCode) {
        const barcode = String(data?.barcode || '').trim().toLowerCase();
        const sku = String(data?.sku || '').trim().toLowerCase();
        const barcodeList = String(data?.barcodes || '')
            .split('|')
            .map(code => code.trim().toLowerCase())
            .filter(Boolean);

        return (barcode && barcode === normalizedCode)
            || barcodeList.includes(normalizedCode)
            || (sku && sku === normalizedCode);
    }

    let vanillaBarcodeBuffer = '';
    let vanillaBarcodeTimer = null;

    function commitVanillaBarcodeScan(rawCode) {
        const normalizedCode = String(rawCode || '').trim().toLowerCase();
        if (!normalizedCode || !productSelect) {
            return false;
        }

        const matchedOption = Array.from(productSelect.options)
            .find((option) => option.value && datasetMatchesBarcodeScan(option.dataset || {}, normalizedCode));

        if (matchedOption) {
            applyVanillaSelection({ dataset: { ...matchedOption.dataset, id: matchedOption.value }});
            if (barcodeInput) barcodeInput.value = '';
            vanillaBarcodeBuffer = '';
            return true;
        }

        showAlert({
            icon: 'error',
            title: 'Product Not Found',
            text: `No product matched "${rawCode}"`,
            timer: 1800,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
        });
        barcodeInput?.select();
        return false;
    }

    function applyVanillaSelection(source) {
        const data = source?.dataset || {};
        const productId = data.id || '';
        if (!productId) {
            if (priceInput) priceInput.value = '';
            if (productImg) productImg.style.display = 'none';
            if (noImg) noImg.style.display = 'block';
            if (quickName) quickName.textContent = 'None';
            if (quickSku) quickSku.textContent = '-';
            if (quickCategory) quickCategory.textContent = '-';
            if (quickMinStock) quickMinStock.textContent = '15';
            if (quickStock) quickStock.textContent = '0';
            if (quickHealth) { quickHealth.classList.remove('low'); quickHealth.classList.add('ok'); quickHealth.textContent = 'OK'; }
            if (hdrSelected) hdrSelected.textContent = 'None';
            currentProductId = '';
            return;
        }

        const availableStock = parseFloat(data.stock || '0') || 0;
        if (availableStock <= 0 || String(data.outOfStock || '0') === '1') {
            alertFallback(`${data.name || 'This product'} is out of stock and cannot be sold.`);
            if (productSelect) {
                productSelect.value = '';
            }
	            syncProductSearchDropdown('');
            currentProductId = '';
            return;
        }

        const isNewProductSelection = currentProductId !== String(productId);
        currentProductId = String(productId);
        if (productSelect) {
            productSelect.value = productId;
        }
	        syncProductSearchDropdown(productId);

        if (isNewProductSelection) {
            const preferredUnitType = String(data.unitType || 'unit').toLowerCase();
            const preferredInput = document.getElementById(`unit-type-${preferredUnitType}`);
            if (preferredInput && !preferredInput.disabled) {
                preferredInput.checked = true;
            } else {
                const unitInput = document.getElementById('unit-type-unit');
                if (unitInput) {
                    unitInput.checked = true;
                }
            }
        }

        const unitMeta = getUnitMetrics(data);
        const basePrice = getBasePrice(data);
        const computedPrice = basePrice.value * unitMeta.multiplier;
        if (priceInput) priceInput.value = computedPrice.toFixed(2);
        if (qtyInput) {
            const currentQty = parseFloat(qtyInput.value || '0') || 0;
            if (currentQty <= 0 || currentQty > Math.max(unitMeta.maxQty, 0.01)) {
                qtyInput.value = unitMeta.maxQty >= 1 ? '1' : String(Math.max(unitMeta.maxQty, 0.01));
            }
        }
        if (qtyLabel) {
            qtyLabel.textContent = `Quantity (${unitMeta.maxQty > 0 ? unitMeta.maxQty.toFixed(2).replace(/\.00$/, '') : '0'} ${unitMeta.unitLabel}${unitMeta.maxQty === 1 ? '' : 's'} available)`;
        }
        if (unitHelperCopy) {
            unitHelperCopy.textContent = unitMeta.type === 'unit'
                ? `Selling in single ${unitMeta.baseUnit}${unitMeta.baseUnit.endsWith('s') ? '' : 's'} using the ${basePrice.label.toLowerCase()} price.`
                : `Each ${unitMeta.type} uses ${unitMeta.multiplier} unit(s) and the ${basePrice.label.toLowerCase()} price.`;
        }

        if (productImg && data.img) {
            productImg.src = data.img;
            productImg.style.display = 'block';
            if (noImg) noImg.style.display = 'none';
        } else {
            if (productImg) productImg.style.display = 'none';
            if (noImg) noImg.style.display = 'block';
        }

        if (quickName) quickName.textContent = data.name || 'Product';
        if (quickSku) quickSku.textContent = data.sku || '-';
        if (quickCategory) quickCategory.textContent = data.categoryName || 'Uncategorized';
        if (quickMinStock) quickMinStock.textContent = data.minStock || '15';
        if (quickStock) quickStock.textContent = data.stock || '0';

        const stock = availableStock;
        const minStock = parseFloat(data.minStock || '15') || 15;
        if (quickHealth) {
            if (stock <= minStock) {
                quickHealth.classList.remove('ok');
                quickHealth.classList.add('low');
                quickHealth.textContent = 'LOW';
            } else {
                quickHealth.classList.remove('low');
                quickHealth.classList.add('ok');
                quickHealth.textContent = 'OK';
            }
        }
        if (hdrSelected) hdrSelected.textContent = data.name || 'Product';
        productCards.forEach((card) => card.classList.toggle('active', card.dataset.id === String(productId)));

        maybeShowExpiryAlert({
            id: productId,
            name: data.name,
            expiry: data.expiry,
        });

        updateItemTotal();
    }

    productCards.forEach((card) => {
        card.addEventListener('click', function () {
            applyVanillaSelection(card);
        });
        card.addEventListener('dblclick', function (event) {
            event.preventDefault();
            applyVanillaSelection(card);
            window.setTimeout(() => addBtn?.click(), 0);
        });
    });

    if (productSearch) {
        productSearch.addEventListener('change', function () {
            const option = findOptionById(productSearch.value);
            if (!option) return;
            applyVanillaSelection({ dataset: {
                ...option.dataset,
                id: option.value,
            }});
        });
    }

    if (productSelect) {
        productSelect.addEventListener('change', function () {
            const option = productSelect.options[productSelect.selectedIndex];
            if (!option) return;
            applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
        });
    }

    barcodeInput?.addEventListener('input', function () {
        window.clearTimeout(vanillaBarcodeTimer);
        vanillaBarcodeBuffer = barcodeInput.value;

        vanillaBarcodeTimer = window.setTimeout(() => {
            if (vanillaBarcodeBuffer && vanillaBarcodeBuffer.trim().length >= 3) {
                commitVanillaBarcodeScan(vanillaBarcodeBuffer);
            }
        }, 220);
    });

    barcodeInput?.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        window.clearTimeout(vanillaBarcodeTimer);
        vanillaBarcodeBuffer = barcodeInput.value;
        commitVanillaBarcodeScan(vanillaBarcodeBuffer);
    });

    function updateItemTotal() {
        const price = parseFloat(priceInput?.value || '0') || 0;
        const qty = parseFloat(qtyInput?.value || '1') || 1;
        const discount = parseFloat(discountInput?.value || '0') || 0;
        const discountType = discountTypeInput?.value || 'percent';
        const tax = parseFloat(taxInput?.value || '0') || 0;
        const sub = price * qty;
        const discVal = discountType === 'fixed' ? Math.min(discount, sub) : (sub * (discount / 100));
        const afterDisc = sub - discVal;
        const taxVal = afterDisc * (tax / 100);
        const total = afterDisc + taxVal;
        if (itemTotal) itemTotal.textContent = fmt.format(total);
        return { sub, discVal, taxVal, total, discountType, discount };
    }

    function calculateCartItem(item) {
        const qty = Math.max(0.01, parseFloat(item.qty || '0') || 0.01);
        const price = Math.max(0, parseFloat(item.price || '0') || 0);
        const discountValue = Math.max(0, parseFloat(item.discountValue ?? item.discount ?? '0') || 0);
        const discountType = item.discountType || 'percent';
        const tax = Math.max(0, parseFloat(item.tax || '0') || 0);
        const multiplier = Math.max(0.01, parseFloat(item.unitMultiplier || '1') || 1);
        const sub = price * qty;
        const discVal = discountType === 'fixed' ? Math.min(discountValue, sub) : (sub * (discountValue / 100));
        const afterDisc = Math.max(0, sub - discVal);
        const taxVal = afterDisc * (tax / 100);

        item.qty = qty;
        item.stockUnits = multiplier * qty;
        item.sub = sub;
        item.discVal = discVal;
        item.taxVal = taxVal;
        item.total = afterDisc + taxVal;
        item.discountType = discountType;
        item.discountValue = discountValue;

        return item;
    }

    function cartMergeKey(item) {
        return [
            item.id,
            item.unitType || 'unit',
            Number(item.price || 0).toFixed(2),
            item.priceLevel || 'retail',
            item.discountType || 'percent',
            Number(item.discountValue ?? item.discount ?? 0).toFixed(2),
            Number(item.tax || 0).toFixed(2),
        ].join('|');
    }

    function addOrMergeCartItem(newItem) {
        const preparedItem = calculateCartItem(newItem);
        const existingIndex = cart.findIndex((item) => cartMergeKey(item) === cartMergeKey(preparedItem));
        const maxStockUnits = Math.max(0, parseFloat(preparedItem.stockAvailable || '0') || 0);

        if (existingIndex >= 0) {
            const existingItem = cart[existingIndex];
            const nextStockUnits = (parseFloat(existingItem.stockUnits || '0') || 0) + (parseFloat(preparedItem.stockUnits || '0') || 0);

            if (maxStockUnits > 0 && nextStockUnits > maxStockUnits + 0.0001) {
                alertFallback(`Only ${maxStockUnits.toFixed(2).replace(/\.00$/, '')} ${preparedItem.baseUnit || 'units'} available for ${preparedItem.name}.`);
                return false;
            }

            existingItem.qty = (parseFloat(existingItem.qty || '0') || 0) + preparedItem.qty;
            calculateCartItem(existingItem);
            return true;
        }

        if (maxStockUnits > 0 && preparedItem.stockUnits > maxStockUnits + 0.0001) {
            alertFallback(`Only ${maxStockUnits.toFixed(2).replace(/\.00$/, '')} ${preparedItem.baseUnit || 'units'} available for ${preparedItem.name}.`);
            return false;
        }

        cart.push(preparedItem);
        return true;
    }

    customerSearchInput?.addEventListener('input', function () {
        const query = customerSearchInput.value;
        filterCustomerOptions(query);

        if (query.trim() !== '') {
            selectCustomerMatch(query);
        } else if (customerSelect) {
            customerSelect.value = '';
        }
    });

    customerSearchInput?.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        selectCustomerMatch(customerSearchInput.value, true);
    });

	    function updateChange() {
	        const total = cartGrandTotalValue();
	        const walletPaid = moneyValue(walletAmount);
	        const cashPaid = moneyValue(amountPaid);
	        const transferPaid = moneyValue(transferAmount);
	        const cardPaid = moneyValue(cardAmount);
	        const isSplit = paymentMethod?.value === 'Split';
	        const externalPaid = isSplit ? (cashPaid + transferPaid + cardPaid) : cashPaid;
	        const paid = externalPaid + walletPaid;
	        const change = paid - total;

        if (changeAmount) {
            changeAmount.textContent = fmt.format(change);
            changeAmount.style.color = change < 0 ? 'var(--danger-500)' : 'var(--success-500)';
        }

	        return { total, paid, change, externalPaid, walletPaid };
    }

    function syncSplitCounterpart(changedField) {
        if (splitAutoSync || paymentMethod?.value !== 'Split') {
            return;
        }

        const totalText = grandTotal?.textContent || '0';
        const total = parseFloat(totalText.replace(/[^\d.]/g, '')) || 0;
        const cashPaid = moneyValue(amountPaid);
        const transferPaid = moneyValue(transferAmount);
        const cardPaid = moneyValue(cardAmount);

        splitAutoSync = true;
        const remainingBase = Math.max(0, total - cashPaid);

        if (changedField === 'transfer' && cardAmount) {
            cardAmount.value = Math.max(0, remainingBase - transferPaid).toFixed(2);
        }

        if (changedField === 'card' && transferAmount) {
            transferAmount.value = Math.max(0, remainingBase - cardPaid).toFixed(2);
        }

        splitAutoSync = false;
        updateChange();
    }

    function toggleSplitFields() {
        const isSplit = paymentMethod?.value === 'Split';
        paymentTabs.forEach((button) => {
            button.classList.toggle('active', (button.id || button.textContent.trim()) === activePaymentTab);
        });
        splitTransferWrap?.classList.toggle('d-none', !isSplit);
        splitTransferAccountWrap?.classList.toggle('d-none', !isSplit);
        splitCardWrap?.classList.toggle('d-none', !isSplit);
        splitCardAccountWrap?.classList.toggle('d-none', !isSplit);

        if (isSplit && amountPaid) {
            const currentCash = moneyValue(amountPaid);
            const totalText = grandTotal?.textContent || '0';
            const total = parseFloat(totalText.replace(/[^\d.]/g, '')) || 0;
            if (Math.abs(currentCash - total) < 0.01) {
                amountPaid.value = '0.00';
            }
            if (transferAmount && !transferAmount.value) {
                transferAmount.value = '0.00';
            }
            if (cardAmount && !cardAmount.value) {
                cardAmount.value = total.toFixed(2);
            }
        }

        updateChange();
    }

    function resetVanillaPosWorkspace() {
        cart.length = 0;
        currentProductId = '';

        renderCart();

        if (customerSelect) {
            customerSelect.value = '';
        }
        if (customerSearchInput) {
            customerSearchInput.value = '';
        }
        filterCustomerOptions('');

        activePaymentTab = 'Cash';
        if (paymentMethod) paymentMethod.value = 'Cash';
        if (depositAccount) depositAccount.value = '';
        if (amountPaid) amountPaid.value = '0.00';
        if (transferAmount) transferAmount.value = '0.00';
        if (transferAccount) transferAccount.value = '';
	        if (cardAmount) cardAmount.value = '0.00';
	        if (cardAccount) cardAccount.value = '';
	        if (walletAmount) walletAmount.value = '0.00';
	        walletPaymentWrap?.classList.add('d-none');

        if (productSelect) productSelect.value = '';
	        syncProductSearchDropdown('');
        if (quickSearch) quickSearch.value = '';
        if (barcodeInput) barcodeInput.value = '';

        if (qtyInput) qtyInput.value = '1';
        if (discountInput) discountInput.value = '0';
        if (taxInput) taxInput.value = '0';
        if (discountTypeInput) discountTypeInput.value = 'percent';
        if (priceTierInput) priceTierInput.value = priceListInput?.value ? 'list' : 'retail';
        if (priceInput) priceInput.value = '';

        const unitTypeUnit = document.getElementById('unit-type-unit');
        if (unitTypeUnit) {
            unitTypeUnit.checked = true;
        }

        applyVanillaSelection({ dataset: {} });
        filterProductCards();
        toggleSplitFields();
        updateItemTotal();

        window.setTimeout(() => {
            if (barcodeInput) {
                barcodeInput.focus();
            } else if (quickSearch) {
                quickSearch.focus();
            }
        }, 50);
    }

    function renderCart() {
        if (!cartBody) return;
        let html = '';
        let totSub = 0;
        let totDisc = 0;
        let totTax = 0;
        let totGrand = 0;

        cart.forEach((item, i) => {
            totSub += item.sub;
            totDisc += item.discVal;
            totTax += item.taxVal;
            totGrand += item.total;

            html += `
                <tr>
                    <td class="ps-3">
                        <div class="fw-bold" style="color: var(--text-primary);">${item.name}</div>
                        <small style="color: var(--text-secondary); font-size: 0.75rem;">${item.qty} ${item.unitLabel || 'unit'} × ${fmt.format(item.price)}</small>
                    </td>
                    <td class="text-center">
                        <input type="number" min="0.01" step="1" value="${item.qty}" class="cart-qty-input" data-index="${i}" inputmode="decimal">
                    </td>
                    <td class="text-end fw-bold tabular-nums" style="color: var(--text-primary);">${fmt.format(item.total)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-remove" data-remove="${i}"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
        });

        cartBody.innerHTML = html;
        const hasItems = cart.length > 0;
        const cartEmptyState = document.getElementById('cart-empty-state');
        const cartWrapper = document.querySelector('.cart-wrapper');

        if (cartWrapper) {
            cartWrapper.classList.toggle('has-items', hasItems);
        }

        if (cartEmptyState) {
            cartEmptyState.hidden = hasItems;
            cartEmptyState.style.display = hasItems ? 'none' : 'flex';
        }

        if (sumSubtotal) sumSubtotal.textContent = fmt.format(totSub);
        if (sumDiscount) sumDiscount.textContent = totDisc > 0 ? '- ' + fmt.format(totDisc) : fmt.format(0);
        if (sumTax) sumTax.textContent = totTax > 0 ? '+ ' + fmt.format(totTax) : fmt.format(0);
        if (grandTotal) grandTotal.textContent = fmt.format(totGrand);
        if (hdrCartCount) hdrCartCount.textContent = String(cart.length);
	        if (paymentMethod?.value === 'Split') {
	            if (amountPaid && !amountPaid.value) {
	                amountPaid.value = '0.00';
	            }
	        } else if (amountPaid) {
	            const walletApplied = refreshWalletApplication();
	            amountPaid.value = Math.max(0, totGrand - walletApplied).toFixed(2);
	        }
	        refreshWalletApplication();
	        updateChange();
	    }

    function applySalesOrderPrefill(prefill) {
        if (!prefill || !Array.isArray(prefill.items) || !prefill.items.length) {
            return;
        }

        const sourceLabel = prefill.source === 'quotation' ? 'Quotation' : 'Sales order';
        const skippedItems = [];
        cart.length = 0;

        if (customerSelect && prefill.customer_id) {
            customerSelect.value = String(prefill.customer_id);
            customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (customerSearchInput && prefill.customer_name) {
            customerSearchInput.value = prefill.customer_name;
        }

        prefill.items.forEach((sourceItem) => {
            const productId = sourceItem.product_id ? String(sourceItem.product_id) : '';
            const option = findOptionById(productId);
            if (!option) {
                skippedItems.push(sourceItem.name || `Product #${productId || 'unknown'}`);
                return;
            }

            const data = option.dataset || {};
            const qty = Math.max(0.01, parseFloat(sourceItem.qty || '1') || 1);
            const price = Math.max(0, parseFloat(sourceItem.rate || data.retail || data.price || '0') || 0);
            const discountValue = Math.max(0, parseFloat(sourceItem.discount || '0') || 0);
            const taxValue = Math.max(0, parseFloat(sourceItem.tax || '0') || 0);
            const sub = price * qty;
            const discVal = Math.min(discountValue, sub);
            const afterDisc = Math.max(0, sub - discVal);
            const taxPercent = afterDisc > 0 ? (taxValue / afterDisc) * 100 : 0;
            const total = afterDisc + taxValue;

            const priceLevel = sourceItem.price_level || 'retail';
            const priceLevelLabel = priceLevel === 'wholesale'
                ? 'Wholesale'
                : (priceLevel === 'special' ? 'Special Discount' : (priceLevel === 'list' ? 'Selected price list' : 'Retail / Default'));

            cart.push({
                id: productId,
                name: data.name || sourceItem.name || option.textContent || 'Product',
                qty,
                unitType: 'unit',
                unitLabel: data.baseUnit || 'unit',
                stockUnits: qty,
                priceLevel,
                priceLevelLabel,
                price,
                discountType: 'fixed',
                discountValue,
                tax: taxPercent,
                sub,
                discVal,
                taxVal: taxValue,
                total,
            });
        });

        renderCart();

        if (prefill.reference) {
            showAlert({
                icon: cart.length ? 'success' : 'warning',
                title: cart.length ? `${sourceLabel} loaded` : `${sourceLabel} needs review`,
                text: skippedItems.length
                    ? `Loaded ${cart.length} item(s) from ${prefill.reference}. ${skippedItems.length} custom/unavailable item(s) were skipped because POS cash sales require catalog products.`
                    : `Loaded ${cart.length} item(s) from ${prefill.reference}.`,
                timer: 3500,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
        }
    }

    if (cartBody) {
        const commitCartQtyInput = function (target) {
            if (!target || !target.classList.contains('cart-qty-input')) return;
            const index = parseInt(target.getAttribute('data-index') || '0', 10);
            if (Number.isNaN(index) || !cart[index]) return;
            const parsedQty = parseFloat(target.value);
            const previousQty = Math.max(0.01, parseFloat(cart[index].qty || '1') || 0.01);
            const qty = Number.isFinite(parsedQty) ? Math.max(0.01, parsedQty) : Math.max(0.01, parseFloat(cart[index].qty || '1') || 0.01);
            target.value = String(qty);
            const price = cart[index].price;
            const discount = cart[index].discountValue ?? cart[index].discount ?? 0;
            const discountType = cart[index].discountType || 'percent';
            const tax = cart[index].tax || 0;
            const multiplier = Math.max(0.01, parseFloat(cart[index].unitMultiplier || '1') || 1);
            const stockAvailable = Math.max(0, parseFloat(cart[index].stockAvailable || '0') || 0);
            if (stockAvailable > 0 && qty * multiplier > stockAvailable + 0.0001) {
                alertFallback(`Only ${stockAvailable.toFixed(2).replace(/\.00$/, '')} ${cart[index].baseUnit || 'units'} available for ${cart[index].name}.`);
                target.value = String(previousQty);
                return;
            }

            cart[index].qty = qty;
            cart[index].discountValue = discount;
            cart[index].discountType = discountType;
            cart[index].tax = tax;
            calculateCartItem(cart[index]);
            renderCart();
        };

        cartBody.addEventListener('change', function (e) {
            const target = e.target;
            commitCartQtyInput(target);
        });
        cartBody.addEventListener('focusout', function (e) {
            const target = e.target;
            commitCartQtyInput(target);
        });
        cartBody.addEventListener('keydown', function (e) {
            const target = e.target;
            if (!target || !target.classList.contains('cart-qty-input') || e.key !== 'Enter') return;
            e.preventDefault();
            commitCartQtyInput(target);
            target.blur();
        });
        cartBody.addEventListener('click', function (e) {
            const target = e.target.closest('button[data-remove]');
            if (!target) return;
            const index = parseInt(target.getAttribute('data-remove') || '0', 10);
            if (Number.isNaN(index)) return;
            cart.splice(index, 1);
            renderCart();
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!requirePosPermission('sell', 'You do not have permission to add POS sale items.')) {
                return;
            }

            const option = productSelect?.options[productSelect.selectedIndex];
            if (!option || !option.value) {
                alertFallback('Select a product');
                return;
            }
            const data = option.dataset || {};
            const unitMeta = getUnitMetrics(data);
            const priceMeta = getBasePrice(data);
            const price = parseFloat(priceInput?.value || (priceMeta.value * unitMeta.multiplier) || '0') || 0;
            const qty = parseFloat(qtyInput?.value || '0');
            if (!Number.isFinite(qty) || qty <= 0) {
                alertFallback('Quantity must be greater than zero.');
                if (qtyInput) qtyInput.value = '1';
                return;
            }
            if (unitMeta.maxQty <= 0) {
                alertFallback('No stock is available for this product.');
                return;
            }
            if (qty > unitMeta.maxQty) {
                alertFallback(`Only ${unitMeta.maxQty.toFixed(2).replace(/\.00$/, '')} ${unitMeta.unitLabel}${unitMeta.maxQty === 1 ? '' : 's'} available.`);
                return;
            }
            if (price <= 0) {
                alertFallback('Price is required for this product.');
                return;
            }
            const calc = updateItemTotal();
            const added = addOrMergeCartItem({
                id: option.value,
                name: data.name || option.textContent || 'Product',
                qty,
                unitType: unitMeta.type,
                unitLabel: unitMeta.unitLabel,
                unitMultiplier: unitMeta.multiplier,
                stockAvailable: unitMeta.stock,
                baseUnit: unitMeta.baseUnit,
                priceLevel: priceMeta.key,
                priceLevelLabel: priceMeta.label,
                price,
                discountType: calc.discountType,
                discountValue: calc.discount,
                tax: parseFloat(taxInput?.value || '0') || 0,
                sub: calc.sub,
                discVal: calc.discVal,
                taxVal: calc.taxVal,
                total: calc.total
            });
            if (!added) {
                return;
            }
            renderCart();
            if (productSelect) productSelect.value = '';
	            syncProductSearchDropdown('');
            currentProductId = '';
            if (qtyInput) qtyInput.value = '1';
            if (discountInput) discountInput.value = '0';
            if (taxInput) taxInput.value = '0';
            if (discountTypeInput) discountTypeInput.value = 'percent';
            if (priceTierInput) priceTierInput.value = priceListInput?.value ? 'list' : 'retail';
            applyVanillaSelection({ dataset: {} });
            showAlert({
                icon: 'success',
                title: 'Item added to cart',
                timer: 1000,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                customClass: {
                    popup: 'pos-toast-sm'
                }
            });
        });
    }

    function filterProductCards() {
        const keyword = (quickSearch?.value || '').toLowerCase().trim();
        const activeCategory = document.querySelector('.category-pill.active')?.dataset.category || 'all';
        let visible = 0;

        productCards.forEach((card) => {
            const name = (card.dataset.searchName || card.dataset.name || '').toLowerCase();
            const sku = (card.dataset.sku || '').toLowerCase();
            const barcode = (card.dataset.barcode || '').toLowerCase();
            const barcodes = (card.dataset.barcodes || '').toLowerCase();
            const category = (card.dataset.category || '').toLowerCase();
            const matchesKeyword = !keyword || name.includes(keyword) || sku.includes(keyword) || barcode.includes(keyword) || barcodes.includes(keyword) || category.includes(keyword);
            const matchesCategory = activeCategory === 'all' || category === activeCategory;
            const show = matchesKeyword && matchesCategory;
            card.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });

        if (hdrShelfCount) hdrShelfCount.textContent = String(visible);
        if (productCount) productCount.textContent = `${visible} item(s)`;
    }

    categoryPills.forEach((pill) => {
        pill.addEventListener('click', function () {
            categoryPills.forEach((node) => node.classList.remove('active'));
            pill.classList.add('active');
            filterProductCards();
        });
    });

    quickSearch?.addEventListener('input', filterProductCards);
    categoryToggle?.addEventListener('click', function () {
        categoryPillsWrap?.classList.toggle('collapsed');
        categoryToggle.textContent = categoryPillsWrap?.classList.contains('collapsed') ? 'Show More' : 'Show Less';
    });

    unitTypeInputs.forEach((input) => {
        input.addEventListener('change', function () {
            const option = findOptionById(currentProductId);
            if (option) {
                applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
            } else {
                updateItemTotal();
            }
        });
    });

    priceTierInput?.addEventListener('change', function () {
        const option = findOptionById(currentProductId);
        if (option) {
            applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
        }
    });
    priceListInput?.addEventListener('change', function () {
        if (priceTierInput && priceListInput.value) {
            priceTierInput.value = 'list';
        }
        const option = findOptionById(currentProductId);
        if (option) {
            applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
        }
    });

    [qtyInput, discountInput, taxInput].forEach((input) => {
        input?.addEventListener('input', updateItemTotal);
    });
    discountTypeInput?.addEventListener('change', updateItemTotal);
    amountPaid?.addEventListener('input', function () {
        if (paymentMethod?.value === 'Split') {
            const lastEdited = document.activeElement === transferAmount ? 'transfer' : (document.activeElement === cardAmount ? 'card' : null);
            if (lastEdited) {
                syncSplitCounterpart(lastEdited);
                return;
            }
        }
        updateChange();
    });
    [amountPaid, transferAmount, cardAmount].forEach((input) => {
        input?.addEventListener('blur', function () {
            normalizeMoneyInput(input);
            updateChange();
        });
    });
    transferAmount?.addEventListener('input', function () {
        syncSplitCounterpart('transfer');
    });
    cardAmount?.addEventListener('input', function () {
        syncSplitCounterpart('card');
    });
	    paymentMethod?.addEventListener('change', toggleSplitFields);
    paymentTabs.forEach((button) => {
        button.addEventListener('click', function () {
            if (!paymentMethod) {
                return;
            }

            activePaymentTab = button.id || button.textContent.trim();
            paymentMethod.value = button.dataset.paymentMethod || 'Cash';
            paymentMethod.dispatchEvent(new Event('change', { bubbles: true }));

            if (button.id === 'pos-pay-card-shortcut' && cardAmount) {
                cardAmount.focus();
                return;
            }

            if (button.id === 'pos-pay-transfer-shortcut' && transferAmount) {
                transferAmount.focus();
                return;
            }

            amountPaid?.focus();
        });
    });
	    customerSelect?.addEventListener('change', function () {
	        refreshWalletApplication();
	        updateChange();
	    });

    processBtn?.addEventListener('click', async function (e) {
        e.preventDefault();
        if (!requirePosPermission('sell', 'You do not have permission to process POS sales.')) {
            return;
        }

        if (!cart.length) {
            alertFallback('Cart is empty.');
            return;
        }

        if (cart.some((item) => !Number.isFinite(parseFloat(item.qty)) || (parseFloat(item.qty) || 0) <= 0)) {
            alertFallback('All cart quantities must be greater than zero.');
            return;
        }

	        const { total, paid, externalPaid } = updateChange();
        const walletApplied = moneyValue(walletAmount);
	        if (paid <= 0 && walletApplied <= 0) {
	            alertFallback('Enter payment before processing the sale.');
	            return;
	        }

        const cashValue = moneyValue(amountPaid);
        if (!isStarterPos && cashValue > 0 && !depositAccount?.value) {
            alertFallback('Choose the cash/deposit account.');
            return;
        }

        if (paymentMethod?.value === 'Split') {
            const transferValue = moneyValue(transferAmount);
            const cardValue = moneyValue(cardAmount);
            if (!isStarterPos && transferValue > 0 && !transferAccount?.value) {
                alertFallback('Choose the transfer account.');
                return;
            }
            if (!isStarterPos && cardValue > 0 && !cardAccount?.value) {
                alertFallback('Choose the POS account.');
                return;
            }
        }

        processBtn.disabled = true;
        if (saveInvoiceBtn) saveInvoiceBtn.disabled = true;
        processBtn.classList.add('processing');
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = '';

        try {
            const response = await fetch(saleStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    customer_id: customerSelect?.value || null,
                    payment_method: paymentMethod?.value || 'Cash',
                    source: posSourceContext?.source || null,
                    source_id: posSourceContext?.source_id || null,
                    source_reference: posSourceContext?.reference || null,
                    items: cart,
                    subtotal: cart.reduce((sum, item) => sum + item.sub, 0),
                    tax: cart.reduce((sum, item) => sum + item.taxVal, 0),
                    discount: cart.reduce((sum, item) => sum + item.discVal, 0),
                    total,
                    deposit_account_id: isStarterPos ? null : (depositAccount?.value || null),
	                    paid: externalPaid,
	                    wallet_amount: walletApplied,
	                    split_details: {
                        cash: moneyValue(amountPaid),
                        transfer: moneyValue(transferAmount),
                        transfer_account_id: isStarterPos ? null : (transferAccount?.value || null),
                        card: moneyValue(cardAmount),
                        card_account_id: isStarterPos ? null : (cardAccount?.value || null),
                    },
                }),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(result.message || 'Failed to process sale.');
            }

            if (result.sale_id && !saveOnlyMode) {
                const invoiceUrl = `${invoicePrintBaseUrl}/${result.sale_id}/print?autoprint=1`;
                window.open(invoiceUrl, '_blank');
            }

            if (saveOnlyMode) {
                showAlert({
                    icon: 'success',
                    title: 'Invoice saved',
                    text: 'Sale has been saved without opening the print receipt.',
                    timer: 2200,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                });
            }

            resetVanillaPosWorkspace();
        } catch (error) {
            alertFallback(error.message || 'Failed to process sale.');
        } finally {
            saveOnlyMode = false;
            processBtn.disabled = false;
            if (saveInvoiceBtn) saveInvoiceBtn.disabled = false;
            processBtn.classList.remove('processing');
            if (btnText) btnText.style.display = '';
            if (btnLoading) btnLoading.style.display = 'none';
        }
    });

    saveInvoiceBtn?.addEventListener('click', function () {
        saveOnlyMode = true;
        processBtn?.click();
    });

    newSaleBtn?.addEventListener('click', function () {
        resetVanillaPosWorkspace();
        barcodeInput?.focus();
    });

    filterProductCards();
    toggleSplitFields();
    updateItemTotal();
    applySalesOrderPrefill(salesOrderPrefill);

    // ── Searchable product combo (vanilla JS – runs inside active fallback) ──
    (function initPosCombo() {
        const input  = document.getElementById('product-search-input');
        const clear  = document.getElementById('product-search-clear');
        const caret  = document.getElementById('product-combo-caret');
        const sel    = document.getElementById('product-select');
        if (!input || !sel) return;

        // Re-use existing portal or create it
        let portal = document.getElementById('pos-product-dropdown-portal');
        if (!portal) {
            portal = document.createElement('div');
            portal.id = 'pos-product-dropdown-portal';
            document.body.appendChild(portal);
        }
        portal.innerHTML = '<ul id="product-search-list"></ul>';
        const list = portal.querySelector('ul');

        let selId  = '';
        let isOpen = false;

        function buildCache() {
            return Array.from(sel.options)
                .filter(function(o) { return o.value !== ''; })
                .map(function(o) {
                    return {
                        id  : String(o.value),
                        name: String(o.dataset.name || o.text || '').trim(),
                        sku : String(o.dataset.sku  || '').trim(),
                        category: String(o.dataset.categoryName || '').trim(),
                        measurement: String(o.dataset.measurement || '').trim(),
                        stock: String(o.dataset.stock || '').trim(),
                    };
                });
        }
        let cache = buildCache();

        function esc(s) {
            return String(s)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        function hlite(text, kw) {
            if (!kw) return esc(text);
            const idx = text.toLowerCase().indexOf(kw);
            if (idx === -1) return esc(text);
            return esc(text.slice(0, idx))
                 + '<strong>' + esc(text.slice(idx, idx + kw.length)) + '</strong>'
                 + esc(text.slice(idx + kw.length));
        }

        function renderList(items, kw) {
            list.innerHTML = '';
            if (!items.length) {
                list.innerHTML = '<li class="combo-no-results">No products found</li>';
                return;
            }
            items.slice(0, 500).forEach(function(item) {
                const li = document.createElement('li');
                li.dataset.id = item.id;
                li.innerHTML = hlite(item.name, kw);
                if (item.sku) {
                    const span = document.createElement('span');
                    span.className = 'combo-sku';
                    span.textContent = 'SKU: ' + item.sku;
                    li.appendChild(span);
                }
                if (item.measurement) {
                    const measure = document.createElement('span');
                    measure.className = 'combo-measure';
                    measure.textContent = item.measurement;
                    li.appendChild(measure);
                }
                if (item.category || item.stock) {
                    const meta = document.createElement('span');
                    meta.className = 'combo-sku';
                    meta.textContent = `${item.category || 'Product'}${item.stock ? ' • Stock: ' + item.stock : ''}`;
                    li.appendChild(meta);
                }
                if (item.id === selId) li.classList.add('kb-focus');
                list.appendChild(li);
            });
        }

        function filterItems(kw) {
            const k = (kw || '').toLowerCase().trim();
            if (!k) return cache;
            return cache.filter(function(item) {
                return item.name.toLowerCase().includes(k)
                    || item.sku.toLowerCase().includes(k)
                    || item.category.toLowerCase().includes(k)
                    || item.measurement.toLowerCase().includes(k);
            });
        }

        function reposition() {
            const r = input.getBoundingClientRect();
            const width = Math.min(Math.max(r.width, 380), window.innerWidth - 24);
            const left = Math.min(Math.max(12, r.left), window.innerWidth - width - 12);
            portal.style.top   = (r.bottom + 2) + 'px';
            portal.style.left  = left + 'px';
            portal.style.width = width + 'px';
        }

        function openWith(kw) {
            if (!cache.length) cache = buildCache();
            renderList(filterItems(kw), (kw || '').toLowerCase().trim());
            reposition();
            portal.style.display = 'block';
            if (caret) caret.classList.add('open');
            isOpen = true;
        }

        function close() {
            portal.style.display = 'none';
            if (caret) caret.classList.remove('open');
            isOpen = false;
        }

        function pick(productId) {
            const opt = Array.from(sel.options).find(function(o) { return String(o.value) === String(productId); });
            if (!opt || !opt.value) return;
            selId = String(productId);
            input.value = (opt.dataset.name || opt.text || '').trim();
            if (clear) clear.style.display = '';
            sel.value = selId;
            close();
            applyVanillaSelection({ dataset: Object.assign({}, opt.dataset, { id: opt.value }) });
        }

        function clearCombo() {
            selId = '';
            input.value = '';
            if (clear) clear.style.display = 'none';
            sel.value = '';
            close();
        }

        // Called by syncProductSearchDropdown (card clicks, barcode scans, etc.)
        window._posComboSync = function(productId) {
            if (!productId) { clearCombo(); return; }
            const opt = Array.from(sel.options).find(function(o) { return String(o.value) === String(productId); });
            if (opt) {
                selId = String(productId);
                input.value = (opt.dataset.name || opt.text || '').trim();
                if (clear) clear.style.display = '';
                close();
            }
        };
        window._posComboOpen = function(keyword = '') {
            input.value = keyword;
            if (clear) clear.style.display = (keyword || selId) ? '' : 'none';
            input.focus();
            openWith(keyword);
        };

        input.addEventListener('input', function() {
            const kw = this.value.trim();
            if (clear) clear.style.display = (kw.length > 0 || !!selId) ? '' : 'none';
            openWith(kw);
        });
        input.addEventListener('focus', function() { openWith(this.value.trim()); });
        input.addEventListener('click', function() { openWith(this.value.trim()); });

        list.addEventListener('click', function(e) {
            const li = e.target.closest('li[data-id]');
            if (li) pick(li.dataset.id);
        });

        if (clear) {
            clear.addEventListener('click', function(e) {
                e.stopPropagation();
                clearCombo();
                input.focus();
            });
        }

        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('product-combo-wrapper');
            if (wrap && !wrap.contains(e.target) && !portal.contains(e.target)) close();
        });

        window.addEventListener('scroll', function() { if (isOpen) reposition(); }, true);
        window.addEventListener('resize', function() { if (isOpen) reposition(); });

        input.addEventListener('keydown', function(e) {
            const items   = Array.from(list.querySelectorAll('li[data-id]'));
            const focused = list.querySelector('li[data-id].kb-focus');

            if (!isOpen) {
                if (e.key === 'ArrowDown' || e.key === 'Enter') openWith(this.value.trim());
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const idx = focused ? items.indexOf(focused) : -1;
                const next = items[idx + 1] || items[0];
                if (next) { items.forEach(function(i){ i.classList.remove('kb-focus'); }); next.classList.add('kb-focus'); kbScroll(next); }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const idx = focused ? items.indexOf(focused) : items.length;
                const prev = items[idx - 1] || items[items.length - 1];
                if (prev) { items.forEach(function(i){ i.classList.remove('kb-focus'); }); prev.classList.add('kb-focus'); kbScroll(prev); }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (focused) pick(focused.dataset.id);
            } else if (e.key === 'Escape') {
                close(); input.blur();
            }
        });

        function kbScroll(item) {
            const t = item.offsetTop, h = item.offsetHeight,
                  dh = portal.clientHeight, st = portal.scrollTop;
            if (t < st) portal.scrollTop = t;
            else if (t + h > st + dh) portal.scrollTop = t + h - dh;
        }
    })();
    // ────────────────────────────────────────────────────────────────────────
};

document.addEventListener('DOMContentLoaded', function () {
    const headerMenuButtons = [
        document.getElementById('mobile_btn'),
        document.getElementById('toggle_btn')
    ].filter(Boolean);
    const posRailBackdrop = document.getElementById('pos-rail-backdrop');
    const posRail = document.getElementById('pos-action-rail');
    const isPosDrawerMode = () => window.matchMedia('(max-width: 1024px)').matches;

    headerMenuButtons.forEach((button) => {
        button.setAttribute('aria-controls', 'pos-action-rail');
        button.setAttribute('aria-label', 'Open POS menu');
    });

    const closePosRail = () => {
        document.body.classList.remove('pos-rail-open');
        headerMenuButtons.forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
            button.classList.remove('is-open');
            button.setAttribute('aria-label', 'Open POS menu');
        });
    };

    const togglePosRail = (button) => {
        const isOpen = document.body.classList.toggle('pos-rail-open');
        headerMenuButtons.forEach((menuButton) => {
            menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            menuButton.classList.toggle('is-open', isOpen);
            menuButton.setAttribute('aria-label', isOpen ? 'Close POS menu' : 'Open POS menu');
        });
        button?.focus?.();
    };

    headerMenuButtons.forEach((button) => {
        button.addEventListener('click', function (event) {
            if (!isPosDrawerMode()) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            togglePosRail(this);
        }, true);
    });

    posRailBackdrop?.addEventListener('click', closePosRail);
    posRail?.querySelectorAll('button, a').forEach((item) => {
        item.addEventListener('click', closePosRail);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closePosRail();
        }
    });

    window.addEventListener('resize', function () {
        if (!isPosDrawerMode()) {
            closePosRail();
        }
    });

    window.POS_ENABLE_FALLBACK();
});
</script>
@endsection
