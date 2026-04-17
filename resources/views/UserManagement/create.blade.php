@extends('layout.mainlayout')

@section('page-title', 'Create User')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════
   CREATE USER — full-page form
═══════════════════════════════════════════════════ */
.create-shell { background: #f1f4fb; min-height: 100vh; padding-bottom: 100px; }

/* Hero */
.perm-hero { background: linear-gradient(135deg,#1a2236 0%,#2d3a57 100%); padding: 28px 32px 24px; border-radius: 16px; color:#fff; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:24px; box-shadow:0 8px 32px rgba(26,34,54,.18); }
.perm-hero-left { display:flex; align-items:center; gap:18px; }
.perm-hero-icon { width:52px; height:52px; background:rgba(212,160,23,.18); border:2px solid rgba(212,160,23,.4); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:#d4a017; flex-shrink:0; }
.perm-hero-title { font-size:1.45rem; font-weight:700; color:#fff; margin-bottom:3px; line-height:1.2; }
.perm-hero-sub { font-size:.85rem; color:rgba(255,255,255,.65); }
.btn-perm-back { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.25); color:#fff; padding:8px 18px; border-radius:8px; font-size:.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:background .2s; }
.btn-perm-back:hover { background:rgba(255,255,255,.2); color:#fff; }

/* Left sticky panel */
.create-left-sticky { position:sticky; top:82px; max-height:calc(100vh - 100px); overflow-y:auto; }
.create-info-card { background:#fff; border:1px solid #e8edf5; border-radius:14px; padding:20px; margin-bottom:16px; }
.create-section-label { font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#7a869a; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.create-section-label::after { content:''; flex:1; height:1px; background:#e8edf5; }

/* Toggle */
.toggle-wrap-create { display:flex; align-items:center; gap:10px; }
.toggle-switch-c { position:relative; display:inline-block; width:44px; height:24px; }
.toggle-switch-c input { opacity:0; width:0; height:0; }
.toggle-slider-c { position:absolute; inset:0; background:#ccc; border-radius:24px; cursor:pointer; transition:background .2s; }
.toggle-slider-c:before { content:''; position:absolute; width:18px; height:18px; left:3px; top:3px; background:#fff; border-radius:50%; transition:transform .2s; }
.toggle-switch-c input:checked+.toggle-slider-c { background:#d4a017; }
.toggle-switch-c input:checked+.toggle-slider-c:before { transform:translateX(20px); }

/* Username input group */
.username-input-group { display:flex; border:1px solid #d0d5e0; border-radius:8px; overflow:hidden; }
.username-input-group input { border:none!important; border-radius:0!important; flex:1; box-shadow:none!important; }
.username-suffix { background:#f4f6fb; border-left:1px solid #d0d5e0; padding:8px 14px; font-size:.88rem; color:#7a869a; white-space:nowrap; display:flex; align-items:center; }
.username-preview { font-size:.82rem; color:#5b5b8b; margin-top:4px; }

/* Toolbar */
.perm-toolbar { background:#fff; border-radius:12px; padding:14px 20px; margin-bottom:22px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; box-shadow:0 2px 8px rgba(0,0,0,.05); border:1px solid #e8edf5; }
.perm-toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.perm-toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.perm-count-badge { background:#eef2ff; color:#2d2a6e; border-radius:20px; padding:4px 14px; font-size:.8rem; font-weight:700; border:1px solid #dde3ff; }
.btn-grant-all { background:#d4a017; border:none; color:#fff; padding:8px 18px; border-radius:8px; font-size:.85rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:background .2s; }
.btn-grant-all:hover { background:#b88b12; }
.btn-revoke-all { background:#fff; border:1px solid #e4e8f0; color:#7a869a; padding:8px 18px; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-revoke-all:hover { background:#fff5f5; border-color:#f87171; color:#dc2626; }
.perm-search-box { border:1px solid #e4e8f0; border-radius:8px; padding:8px 14px; font-size:.85rem; color:#1a2236; outline:none; width:220px; background:#f8fafc; transition:border-color .2s,box-shadow .2s; }
.perm-search-box:focus { border-color:#2d2a6e; box-shadow:0 0 0 3px rgba(45,42,110,.08); background:#fff; }

/* Category */
.perm-category-row { display:flex; align-items:center; gap:12px; margin:28px 0 14px; }
.perm-category-label { font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#7a869a; white-space:nowrap; }
.perm-category-line { flex:1; height:1px; background:#e4e8f0; }

/* Grid */
.perm-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
@media(max-width:1199px) { .perm-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:699px)  { .perm-grid { grid-template-columns:1fr; } }

/* Cards */
.perm-card { background:#fff; border:1px solid #e8edf5; border-radius:14px; overflow:hidden; transition:box-shadow .2s,border-color .2s; display:flex; flex-direction:column; }
.perm-card:hover { box-shadow:0 6px 20px rgba(26,34,54,.08); border-color:#d0d9f0; }
.perm-card.perm-card--hidden { display:none; }
.perm-card-head { display:flex; align-items:center; gap:12px; padding:14px 16px 12px; border-bottom:1px solid #f1f4fb; background:#fafbff; }
.perm-card-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.perm-card-name { font-weight:700; font-size:.9rem; color:#1a2236; flex:1; line-height:1.3; }
.perm-card-count { font-size:.72rem; color:#7a869a; background:#f1f4fb; border-radius:10px; padding:2px 9px; white-space:nowrap; }
.perm-card-toggle { display:flex; align-items:center; gap:6px; cursor:pointer; flex-shrink:0; }
.perm-card-toggle input[type=checkbox] { display:none; }
.perm-toggle-track { width:34px; height:19px; background:#d1d5db; border-radius:19px; position:relative; transition:background .2s; flex-shrink:0; }
.perm-toggle-thumb { width:13px; height:13px; background:#fff; border-radius:50%; position:absolute; top:3px; left:3px; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.perm-card-toggle input:checked ~ .perm-toggle-track { background:#d4a017; }
.perm-card-toggle input:checked ~ .perm-toggle-track .perm-toggle-thumb { transform:translateX(15px); }
.perm-toggle-label { font-size:.72rem; font-weight:600; color:#94a3b8; }
.perm-card-body { padding:14px 16px 16px; flex:1; }
.perm-item { display:flex; align-items:center; gap:9px; font-size:.84rem; color:#374151; cursor:pointer; padding:5px 6px; border-radius:7px; line-height:1.3; margin-bottom:1px; transition:background .15s; -webkit-user-select:none; user-select:none; }
.perm-item:hover { background:#f4f6fb; }
.perm-item input[type=checkbox], .perm-item input[type=radio] { width:16px; height:16px; accent-color:#2d2a6e; flex-shrink:0; cursor:pointer; margin:0; }
.perm-item input:checked+span { color:#1a2236; font-weight:600; }
.perm-sep { border:none; border-top:1px dashed #e8edf5; margin:8px 0; }
.perm-sub-title { font-size:.69rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin:8px 0 4px 4px; }

/* Action bar */
.perm-action-bar { position:sticky; bottom:0; background:#fff; border-top:2px solid #e4e8f0; padding:14px 24px; display:flex; justify-content:center; gap:12px; z-index:50; box-shadow:0 -4px 16px rgba(0,0,0,.07); }
.btn-perm-save { background:#1a2236; border:none; color:#fff; padding:11px 36px; border-radius:9px; font-size:.92rem; font-weight:700; display:inline-flex; align-items:center; gap:8px; transition:background .2s; cursor:pointer; }
.btn-perm-save:hover { background:#0f1520; }
.btn-perm-cancel { background:#fff; border:1px solid #d1d5db; color:#6b7280; padding:11px 26px; border-radius:9px; font-size:.92rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; transition:all .2s; }
.btn-perm-cancel:hover { background:#f9fafb; border-color:#9ca3af; color:#374151; }

/* Icon palettes */
.icon-blue { background:#e0e8ff; color:#3b5bdb; }
.icon-gold { background:#fef3c7; color:#d97706; }
.icon-green { background:#dcfce7; color:#16a34a; }
.icon-teal { background:#ccfbf1; color:#0d9488; }
.icon-purple { background:#f3e8ff; color:#7c3aed; }
.icon-rose { background:#ffe4e6; color:#e11d48; }
.icon-indigo { background:#e0e7ff; color:#4338ca; }
.icon-orange { background:#fff7ed; color:#ea580c; }
.icon-sky { background:#e0f2fe; color:#0284c7; }
.icon-pink { background:#fce7f3; color:#db2777; }
.icon-lime { background:#ecfccb; color:#65a30d; }
.icon-slate { background:#f1f5f9; color:#475569; }
</style>
@endpush

@section('content')
@php
    $isSuperAdminRoute = request()->routeIs('super_admin.*');
    $storeRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.store') ? 'super_admin.users.store' : 'users.store';

    $modules = [
        /* ── CORE ─────────────────────────────────────── */
        ['group'=>'Dashboard',       'section'=>'dashboard',           'icon'=>'fa-tachometer-alt',    'ic'=>'icon-blue',  'cat'=>'Core',
         'items'=>[
             ['t'=>'cb','p'=>'dashboard.overview.view','l'=>'View Dashboard'],
         ]],
        ['group'=>'User Management', 'section'=>'user_mgmt',           'icon'=>'fa-users',             'ic'=>'icon-indigo','cat'=>'Core',
         'items'=>[
             ['t'=>'cb','p'=>'user_management.users.view',  'l'=>'View Users'],
             ['t'=>'cb','p'=>'user_management.users.create','l'=>'Add User'],
             ['t'=>'cb','p'=>'user_management.users.edit',  'l'=>'Edit User'],
             ['t'=>'cb','p'=>'user_management.users.delete','l'=>'Delete User'],
         ]],
        ['group'=>'Roles',           'section'=>'roles',               'icon'=>'fa-user-shield',       'ic'=>'icon-purple','cat'=>'Core',
         'items'=>[
             ['t'=>'cb','p'=>'roles.roles.view',  'l'=>'View Roles'],
             ['t'=>'cb','p'=>'roles.roles.create','l'=>'Add Role'],
             ['t'=>'cb','p'=>'roles.roles.edit',  'l'=>'Edit Role'],
             ['t'=>'cb','p'=>'roles.roles.delete','l'=>'Delete Role'],
         ]],

        /* ── SALES & CRM ──────────────────────────────── */
        ['group'=>'Customer',        'section'=>'customer',            'icon'=>'fa-address-book',      'ic'=>'icon-teal',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'hd','l'=>'View Access'],
             ['t'=>'rd','rn'=>'perm_radio[customers_view]','p'=>'customers.customers.view_all','l'=>'View All Customers'],
             ['t'=>'rd','rn'=>'perm_radio[customers_view]','p'=>'customers.customers.view_own','l'=>'View Own Customers'],
             ['t'=>'sp'],
             ['t'=>'hd','l'=>'No-Sell Filter'],
             ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_1month', 'l'=>'No Sell — 1 Month'],
             ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_3months','l'=>'No Sell — 3 Months'],
             ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_6months','l'=>'No Sell — 6 Months'],
             ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_1year',  'l'=>'No Sell — 1 Year'],
             ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_irrespective',   'l'=>'Irrespective of Sell'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'customers.customers.create','l'=>'Add Customer'],
             ['t'=>'cb','p'=>'customers.customers.edit',  'l'=>'Edit Customer'],
             ['t'=>'cb','p'=>'customers.customers.delete','l'=>'Delete Customer'],
         ]],
        ['group'=>'Sales / Invoice', 'section'=>'invoices',            'icon'=>'fa-file-invoice-dollar','ic'=>'icon-green','cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[invoices_view]','p'=>'sales.invoices.view_all','l'=>'View All Invoices'],
             ['t'=>'rd','rn'=>'perm_radio[invoices_view]','p'=>'sales.invoices.view_own','l'=>'View Own Invoices'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'sales.invoices.create','l'=>'Add Invoice'],
             ['t'=>'cb','p'=>'sales.invoices.edit',  'l'=>'Edit Invoice'],
             ['t'=>'cb','p'=>'sales.invoices.delete','l'=>'Delete Invoice'],
         ]],
        ['group'=>'POS Sales',       'section'=>'pos',                 'icon'=>'fa-cash-register',     'ic'=>'icon-gold',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'sales.pos.view',  'l'=>'View POS Sales'],
             ['t'=>'cb','p'=>'sales.pos.create','l'=>'Create POS Sale'],
         ]],
        ['group'=>'Quotations',      'section'=>'quotations',          'icon'=>'fa-file-alt',          'ic'=>'icon-sky',   'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[quotations_view]','p'=>'sales.quotations.view_all','l'=>'View All Quotations'],
             ['t'=>'rd','rn'=>'perm_radio[quotations_view]','p'=>'sales.quotations.view_own','l'=>'View Own Quotations'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'sales.quotations.create','l'=>'Add Quotation'],
             ['t'=>'cb','p'=>'sales.quotations.edit',  'l'=>'Edit Quotation'],
             ['t'=>'cb','p'=>'sales.quotations.delete','l'=>'Delete Quotation'],
         ]],
        ['group'=>'Estimates',       'section'=>'estimates',           'icon'=>'fa-file-invoice',      'ic'=>'icon-lime',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'estimates.estimates.view',  'l'=>'View Estimates'],
             ['t'=>'cb','p'=>'estimates.estimates.create','l'=>'Add Estimate'],
             ['t'=>'cb','p'=>'estimates.estimates.edit',  'l'=>'Edit Estimate'],
             ['t'=>'cb','p'=>'estimates.estimates.delete','l'=>'Delete Estimate'],
         ]],
        ['group'=>'Recurring Invoices','section'=>'recurring_invoices','icon'=>'fa-calendar-alt',      'ic'=>'icon-teal',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.view',  'l'=>'View Recurring Invoices'],
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.create','l'=>'Add Recurring Invoice'],
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.edit',  'l'=>'Edit Recurring Invoice'],
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.delete','l'=>'Delete Recurring Invoice'],
         ]],
        ['group'=>'Follow-Ups',      'section'=>'follow_ups',          'icon'=>'fa-phone-alt',         'ic'=>'icon-pink',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'follow_ups.follow_ups.view',  'l'=>'View Follow-Ups'],
             ['t'=>'cb','p'=>'follow_ups.follow_ups.create','l'=>'Add Follow-Up'],
             ['t'=>'cb','p'=>'follow_ups.follow_ups.edit',  'l'=>'Edit Follow-Up'],
             ['t'=>'cb','p'=>'follow_ups.follow_ups.delete','l'=>'Delete Follow-Up'],
         ]],
        ['group'=>'Collections Hub', 'section'=>'collections_hub',     'icon'=>'fa-coins',             'ic'=>'icon-gold',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'collections_hub.collections_hub.view',  'l'=>'View Collections Hub'],
             ['t'=>'cb','p'=>'collections_hub.collections_hub.create','l'=>'Add Collection'],
             ['t'=>'cb','p'=>'collections_hub.collections_hub.edit',  'l'=>'Edit Collection'],
         ]],

        /* ── PURCHASING ────────────────────────────────── */
        ['group'=>'Supplier',        'section'=>'supplier',            'icon'=>'fa-truck',             'ic'=>'icon-orange','cat'=>'Purchasing',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[vendors_view]','p'=>'vendors.vendors.view_all','l'=>'View All Suppliers'],
             ['t'=>'rd','rn'=>'perm_radio[vendors_view]','p'=>'vendors.vendors.view_own','l'=>'View Own Suppliers'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'vendors.vendors.create','l'=>'Add Supplier'],
             ['t'=>'cb','p'=>'vendors.vendors.edit',  'l'=>'Edit Supplier'],
             ['t'=>'cb','p'=>'vendors.vendors.delete','l'=>'Delete Supplier'],
         ]],
        ['group'=>'Purchase',        'section'=>'purchase',            'icon'=>'fa-shopping-cart',     'ic'=>'icon-rose',  'cat'=>'Purchasing',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[purchases_view]','p'=>'purchases.purchases.view_all','l'=>'View All Purchases'],
             ['t'=>'rd','rn'=>'perm_radio[purchases_view]','p'=>'purchases.purchases.view_own','l'=>'View Own Purchases'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'purchases.purchases.create',        'l'=>'Add Purchase'],
             ['t'=>'cb','p'=>'purchases.purchases.edit',          'l'=>'Edit Purchase'],
             ['t'=>'cb','p'=>'purchases.purchases.delete',        'l'=>'Delete Purchase'],
             ['t'=>'cb','p'=>'purchases.purchases.add_payment',   'l'=>'Add Payment'],
             ['t'=>'cb','p'=>'purchases.purchases.edit_payment',  'l'=>'Edit Payment'],
             ['t'=>'cb','p'=>'purchases.purchases.delete_payment','l'=>'Delete Payment'],
         ]],
        ['group'=>'Purchase Orders', 'section'=>'purchase_orders',     'icon'=>'fa-clipboard-list',    'ic'=>'icon-indigo','cat'=>'Purchasing',
         'items'=>[
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.view',  'l'=>'View Purchase Orders'],
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.create','l'=>'Add Purchase Order'],
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.edit',  'l'=>'Edit Purchase Order'],
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.delete','l'=>'Delete Purchase Order'],
         ]],

        /* ── INVENTORY ─────────────────────────────────── */
        ['group'=>'Product',         'section'=>'product',             'icon'=>'fa-box',               'ic'=>'icon-orange','cat'=>'Inventory',
         'items'=>[
             ['t'=>'cb','p'=>'inventory.products.view',               'l'=>'View Products'],
             ['t'=>'cb','p'=>'inventory.products.create',             'l'=>'Add Product'],
             ['t'=>'cb','p'=>'inventory.products.edit',               'l'=>'Edit Product'],
             ['t'=>'cb','p'=>'inventory.products.delete',             'l'=>'Delete Product'],
             ['t'=>'cb','p'=>'inventory.products.add_opening_stock',  'l'=>'Add Opening Stock'],
             ['t'=>'cb','p'=>'inventory.products.view_purchase_price','l'=>'View Purchase Price'],
         ]],
        ['group'=>'Stock Manager',   'section'=>'stock',               'icon'=>'fa-warehouse',         'ic'=>'icon-teal',  'cat'=>'Inventory',
         'items'=>[
             ['t'=>'cb','p'=>'inventory.stock.view','l'=>'View Stock'],
             ['t'=>'cb','p'=>'inventory.stock.edit','l'=>'Edit Stock'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'inventory.categories.view',  'l'=>'View Categories'],
             ['t'=>'cb','p'=>'inventory.categories.create','l'=>'Add Category'],
             ['t'=>'cb','p'=>'inventory.categories.edit',  'l'=>'Edit Category'],
             ['t'=>'cb','p'=>'inventory.categories.delete','l'=>'Delete Category'],
         ]],

        /* ── FINANCE ───────────────────────────────────── */
        ['group'=>'Expenses',        'section'=>'expenses',            'icon'=>'fa-receipt',           'ic'=>'icon-rose',  'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'finance.expenses.view',  'l'=>'View Expenses'],
             ['t'=>'cb','p'=>'finance.expenses.create','l'=>'Add Expense'],
             ['t'=>'cb','p'=>'finance.expenses.edit',  'l'=>'Edit Expense'],
             ['t'=>'cb','p'=>'finance.expenses.delete','l'=>'Delete Expense'],
         ]],
        ['group'=>'Payments',        'section'=>'payments',            'icon'=>'fa-credit-card',       'ic'=>'icon-green', 'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'finance.payments.view',  'l'=>'View Payments'],
             ['t'=>'cb','p'=>'finance.payments.create','l'=>'Add Payment'],
             ['t'=>'cb','p'=>'finance.payments.edit',  'l'=>'Edit Payment'],
         ]],
        ['group'=>'Accounting',      'section'=>'accounting',          'icon'=>'fa-book',              'ic'=>'icon-indigo','cat'=>'Finance',
         'items'=>[
             ['t'=>'hd','l'=>'Chart of Accounts'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.view',  'l'=>'View Accounts'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.create','l'=>'Add Account'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.edit',  'l'=>'Edit Account'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.delete','l'=>'Delete Account'],
             ['t'=>'sp'],
             ['t'=>'hd','l'=>'Bank Reconciliation'],
             ['t'=>'cb','p'=>'accounting.bank_reconciliation.view','l'=>'View Reconciliation'],
             ['t'=>'cb','p'=>'accounting.bank_reconciliation.edit','l'=>'Edit Reconciliation'],
             ['t'=>'sp'],
             ['t'=>'hd','l'=>'Manual Journal'],
             ['t'=>'cb','p'=>'accounting.manual_journal.view',  'l'=>'View Journal'],
             ['t'=>'cb','p'=>'accounting.manual_journal.create','l'=>'Add Entry'],
             ['t'=>'cb','p'=>'accounting.manual_journal.edit',  'l'=>'Edit Entry'],
             ['t'=>'cb','p'=>'accounting.manual_journal.delete','l'=>'Delete Entry'],
         ]],
        ['group'=>'Recurring Transactions','section'=>'recurring_transactions','icon'=>'fa-sync-alt',  'ic'=>'icon-sky',   'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.view',  'l'=>'View Recurring Transactions'],
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.create','l'=>'Add Recurring Transaction'],
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.edit',  'l'=>'Edit Recurring Transaction'],
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.delete','l'=>'Delete Recurring Transaction'],
         ]],
        ['group'=>'Payment Summary',  'section'=>'payment_summary',    'icon'=>'fa-wallet',            'ic'=>'icon-gold',  'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'payment_summary.payment_summary.view','l'=>'View Payment Summary'],
         ]],

        /* ── BUDGETING ─────────────────────────────────── */
        ['group'=>'Budgets',         'section'=>'budgets',             'icon'=>'fa-chart-pie',         'ic'=>'icon-purple','cat'=>'Budgeting',
         'items'=>[
             ['t'=>'cb','p'=>'budgets.budgets.view',  'l'=>'View Budgets'],
             ['t'=>'cb','p'=>'budgets.budgets.create','l'=>'Add Budget'],
             ['t'=>'cb','p'=>'budgets.budgets.edit',  'l'=>'Edit Budget'],
             ['t'=>'cb','p'=>'budgets.budgets.delete','l'=>'Delete Budget'],
         ]],
        ['group'=>'Fixed Assets',    'section'=>'fixed_assets',        'icon'=>'fa-building',          'ic'=>'icon-slate', 'cat'=>'Budgeting',
         'items'=>[
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.view',  'l'=>'View Fixed Assets'],
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.create','l'=>'Add Fixed Asset'],
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.edit',  'l'=>'Edit Fixed Asset'],
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.delete','l'=>'Delete Fixed Asset'],
         ]],
        ['group'=>'Expense Claims',  'section'=>'expense_claims',      'icon'=>'fa-file-medical',      'ic'=>'icon-rose',  'cat'=>'Budgeting',
         'items'=>[
             ['t'=>'cb','p'=>'expense_claims.expense_claims.view',  'l'=>'View Expense Claims'],
             ['t'=>'cb','p'=>'expense_claims.expense_claims.create','l'=>'Submit Expense Claim'],
             ['t'=>'cb','p'=>'expense_claims.expense_claims.edit',  'l'=>'Edit Expense Claim'],
             ['t'=>'cb','p'=>'expense_claims.expense_claims.delete','l'=>'Delete Expense Claim'],
         ]],

        /* ── HR & PAYROLL ──────────────────────────────── */
        ['group'=>'Payroll',         'section'=>'payroll',             'icon'=>'fa-money-bill-wave',   'ic'=>'icon-green', 'cat'=>'HR & Payroll',
         'items'=>[
             ['t'=>'cb','p'=>'payroll.payroll.view',  'l'=>'View Payroll'],
             ['t'=>'cb','p'=>'payroll.payroll.create','l'=>'Create Payroll'],
             ['t'=>'cb','p'=>'payroll.payroll.edit',  'l'=>'Edit Payroll'],
         ]],
        ['group'=>'Projects',        'section'=>'projects',            'icon'=>'fa-project-diagram',   'ic'=>'icon-blue',  'cat'=>'HR & Payroll',
         'items'=>[
             ['t'=>'cb','p'=>'projects.projects.view',  'l'=>'View Projects'],
             ['t'=>'cb','p'=>'projects.projects.create','l'=>'Add Project'],
             ['t'=>'cb','p'=>'projects.projects.edit',  'l'=>'Edit Project'],
             ['t'=>'cb','p'=>'projects.projects.delete','l'=>'Delete Project'],
         ]],

        /* ── COMPLIANCE ────────────────────────────────── */
        ['group'=>'Tax',             'section'=>'tax',                 'icon'=>'fa-percentage',        'ic'=>'icon-rose',  'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'tax.filings.view',  'l'=>'View Tax Filings'],
             ['t'=>'cb','p'=>'tax.filings.create','l'=>'Add Tax Filing'],
             ['t'=>'cb','p'=>'tax.filings.edit',  'l'=>'Edit Tax Filing'],
         ]],
        ['group'=>'Approval Queue',  'section'=>'approval_queue',      'icon'=>'fa-check-double',      'ic'=>'icon-teal',  'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'approval_queue.approval_queue.view','l'=>'View Approval Queue'],
             ['t'=>'cb','p'=>'approval_queue.approval_queue.edit','l'=>'Approve / Reject Requests'],
         ]],
        ['group'=>'Period Close',    'section'=>'period_close',        'icon'=>'fa-lock',              'ic'=>'icon-slate', 'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'period_close.period_close.view',   'l'=>'View Period Close'],
             ['t'=>'cb','p'=>'period_close.period_close.execute','l'=>'Execute Period Close'],
         ]],
        ['group'=>'Activity Log',    'section'=>'activity_log',        'icon'=>'fa-history',           'ic'=>'icon-slate', 'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'activity_log.activity_log.view','l'=>'View Activity Log'],
         ]],

        /* ── ADMINISTRATIVE ────────────────────────────── */
        ['group'=>'Reports',         'section'=>'reports',             'icon'=>'fa-chart-bar',         'ic'=>'icon-blue',  'cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'reports.reports.view','l'=>'View Reports'],
         ]],
        ['group'=>'Branches',        'section'=>'branches',            'icon'=>'fa-code-branch',       'ic'=>'icon-indigo','cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'branches.branches.view',  'l'=>'View Branches'],
             ['t'=>'cb','p'=>'branches.branches.create','l'=>'Add Branch'],
             ['t'=>'cb','p'=>'branches.branches.edit',  'l'=>'Edit Branch'],
             ['t'=>'cb','p'=>'branches.branches.delete','l'=>'Delete Branch'],
         ]],
        ['group'=>'Settings',        'section'=>'settings',            'icon'=>'fa-cog',               'ic'=>'icon-slate', 'cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'settings.settings.view','l'=>'View Settings'],
             ['t'=>'cb','p'=>'settings.settings.edit','l'=>'Edit Settings'],
         ]],
        ['group'=>'Applications',    'section'=>'applications',        'icon'=>'fa-th-large',          'ic'=>'icon-purple','cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'applications.chat.view',    'l'=>'Access Chat'],
             ['t'=>'cb','p'=>'applications.calendar.view','l'=>'Access Calendar'],
             ['t'=>'cb','p'=>'applications.messages.view','l'=>'Access Messages'],
         ]],
    ];

    $countTotal = function(array $items) {
        return count(array_filter($items, fn($i) => isset($i['p'])));
    };
    $categories = [];
    foreach ($modules as $m) { $categories[$m['cat']][] = $m; }
@endphp

<div class="page-wrapper create-shell">
<div class="content container-fluid">

    {{-- ── Hero ── --}}
    <div class="perm-hero">
        <div class="perm-hero-left">
            <div class="perm-hero-icon"><i class="fa fa-user-plus"></i></div>
            <div>
                <div class="perm-hero-title">Create New User</div>
                <div class="perm-hero-sub">Fill in user details and configure module access permissions.</div>
            </div>
        </div>
        <div>
            <a href="{{ route('users.index') }}" class="btn-perm-back">
                <i class="fa fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route($storeRouteName) }}" method="POST" enctype="multipart/form-data" id="createUserForm">
        @csrf

        <div class="row g-4">

            {{-- ══════ LEFT PANEL ══════ --}}
            <div class="col-lg-4">
                <div class="create-left-sticky">

                    {{-- Personal Info card --}}
                    <div class="create-info-card">
                        <div class="create-section-label"><i class="fa fa-id-card text-muted me-1"></i> Personal Information</div>
                        <div class="row g-3">
                            <div class="col-12 text-center mb-2">
                                <div id="avatarPreview" style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#1a2236,#2d3a57);color:#fff;font-size:1.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                                    <i class="fa fa-user" style="opacity:.5;font-size:1.4rem;"></i>
                                </div>
                                <div>
                                    <label class="btn btn-sm btn-outline-secondary" style="cursor:pointer;font-size:.78rem;">
                                        <i class="fa fa-camera me-1"></i> Photo
                                        <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" style="display:none;">
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="firstNameInput" class="form-control form-control-sm" placeholder="Jane" required value="{{ old('first_name') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">Last Name</label>
                                <input type="text" name="last_name" class="form-control form-control-sm" placeholder="Doe" value="{{ old('last_name') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="jane@example.com" required value="{{ old('email') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">Phone</label>
                                <input type="text" name="phone" class="form-control form-control-sm" placeholder="+234 800 000 0000" value="{{ old('phone') }}">
                            </div>
                            <div class="col-12 d-flex align-items-center gap-2 pt-1">
                                <label class="toggle-switch-c">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <span class="toggle-slider-c"></span>
                                </label>
                                <span style="font-size:.84rem;color:#555;">Active account</span>
                            </div>
                        </div>
                    </div>

                    {{-- Account Settings card --}}
                    <div class="create-info-card">
                        <div class="create-section-label"><i class="fa fa-lock text-muted me-1"></i> Account Settings</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">Role <span class="text-danger">*</span></label>
                                <select name="role" id="roleSelect" class="form-select form-select-sm" required>
                                    <option value="" disabled selected>Select a role</option>
                                    @foreach(($roles ?? []) as $role)
                                        <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $role)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div style="font-size:.76rem;color:#94a3b8;margin-top:4px;">
                                    <i class="fa fa-info-circle"></i> Selecting a role will pre-fill permissions below.
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">Username</label>
                                <div class="username-input-group">
                                    <input type="text" name="username_base" id="usernameBase"
                                           class="form-control form-control-sm"
                                           placeholder="Leave blank to auto-generate"
                                           autocomplete="off"
                                           value="{{ old('username_base') }}">
                                    @if($suffix ?? '')
                                    <span class="username-suffix">-{{ $suffix }}</span>
                                    @endif
                                </div>
                                <div class="username-preview" id="usernamePreview">Leave blank to auto-generate</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control form-control-sm" placeholder="Password" required autocomplete="new-password">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.82rem;">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control form-control-sm" placeholder="Confirm Password" required autocomplete="new-password">
                            </div>
                            <div class="col-12 d-flex align-items-center gap-2 pt-1">
                                <label class="toggle-switch-c">
                                    <input type="checkbox" name="allow_login" value="1" checked>
                                    <span class="toggle-slider-c"></span>
                                </label>
                                <span style="font-size:.84rem;color:#555;">Allow login</span>
                            </div>
                        </div>
                    </div>

                    {{-- Branch Access card --}}
                    @if(($branches ?? collect())->isNotEmpty())
                    <div class="create-info-card">
                        <div class="create-section-label"><i class="fa fa-code-branch text-muted me-1"></i> Branch Access</div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="access_all_locations" value="1" id="accessAll" checked>
                            <label class="form-check-label" for="accessAll" style="font-size:.85rem;">All Locations</label>
                        </div>
                        <div id="branchCheckboxes">
                            @foreach($branches as $branch)
                            <div class="form-check mb-2">
                                <input class="form-check-input branch-check" type="checkbox"
                                       name="access_branches[]" value="{{ $branch['id'] }}"
                                       id="branch_{{ $branch['id'] }}" disabled>
                                <label class="form-check-label" for="branch_{{ $branch['id'] }}" style="font-size:.85rem;">
                                    {{ $branch['name'] }}{{ !empty($branch['code']) ? ' ('.$branch['code'].')' : '' }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
            </div>{{-- /col-lg-4 --}}

            {{-- ══════ RIGHT PANEL — PERMISSIONS ══════ --}}
            <div class="col-lg-8">

                {{-- Toolbar --}}
                <div class="perm-toolbar">
                    <div class="perm-toolbar-left">
                        <span style="font-weight:700;font-size:.9rem;color:#1a2236;">Module Access Permissions</span>
                        <span class="perm-count-badge" id="grantedBadge">0 granted</span>
                    </div>
                    <div class="perm-toolbar-right">
                        <input type="text" class="perm-search-box" id="permSearch" placeholder="Search modules…" autocomplete="off">
                        <button type="button" class="btn-revoke-all" id="btnRevokeAll">
                            <i class="fa fa-times-circle"></i> Revoke All
                        </button>
                        <button type="button" class="btn-grant-all" id="btnGrantAll">
                            <i class="fa fa-check-circle"></i> Grant All
                        </button>
                    </div>
                </div>

                {{-- Permission matrix --}}
                @foreach ($categories as $catName => $catModules)
                    <div class="perm-category-row">
                        <span class="perm-category-label">{{ $catName }}</span>
                        <div class="perm-category-line"></div>
                    </div>

                    <div class="perm-grid">
                        @foreach ($catModules as $mod)
                            @php $total = $countTotal($mod['items']); @endphp
                            <div class="perm-card"
                                 data-module-name="{{ strtolower($mod['group']) }}"
                                 data-section="{{ $mod['section'] }}">

                                <div class="perm-card-head">
                                    <div class="perm-card-icon {{ $mod['ic'] }}">
                                        <i class="fa {{ $mod['icon'] }}"></i>
                                    </div>
                                    <div class="perm-card-name">{{ $mod['group'] }}</div>
                                    <span class="perm-card-count" id="count-{{ $mod['section'] }}">0/{{ $total }}</span>
                                    <label class="perm-card-toggle ms-1" title="Toggle all">
                                        <input type="checkbox" class="perm-select-all" data-section="{{ $mod['section'] }}">
                                        <span class="perm-toggle-track"><span class="perm-toggle-thumb"></span></span>
                                        <span class="perm-toggle-label">All</span>
                                    </label>
                                </div>

                                <div class="perm-card-body">
                                    @foreach ($mod['items'] as $item)
                                        @if ($item['t'] === 'sp')
                                            <hr class="perm-sep">
                                        @elseif ($item['t'] === 'hd')
                                            <div class="perm-sub-title">{{ $item['l'] }}</div>
                                        @elseif ($item['t'] === 'cb')
                                            <label class="perm-item">
                                                <input type="checkbox"
                                                       class="perm-input"
                                                       name="permissions[]"
                                                       value="{{ $item['p'] }}"
                                                       data-section="{{ $mod['section'] }}">
                                                <span>{{ $item['l'] }}</span>
                                            </label>
                                        @elseif ($item['t'] === 'rd')
                                            <label class="perm-item">
                                                <input type="radio"
                                                       class="perm-input perm-radio"
                                                       name="{{ $item['rn'] }}"
                                                       value="{{ $item['p'] }}"
                                                       data-section="{{ $mod['section'] }}">
                                                <span>{{ $item['l'] }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endforeach

            </div>{{-- /col-lg-8 --}}
        </div>{{-- /row --}}

        {{-- Sticky action bar --}}
        <div class="perm-action-bar">
            <a href="{{ route('users.index') }}" class="btn-perm-cancel">
                <i class="fa fa-times me-1"></i> Cancel
            </a>
            <button type="submit" class="btn-perm-save">
                <i class="fa fa-user-plus"></i> Create User
            </button>
        </div>

    </form>

</div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    var rolePermUrl = '{{ route('roles.permissions.json') }}';
    var suffix = '{{ $suffix ? '-' . $suffix : '' }}';

    /* ── Count helpers ──────────────────────────────────── */
    function updateCardCount(section) {
        var card = document.querySelector('[data-section="' + section + '"]');
        if (!card) return;
        var inputs = card.querySelectorAll('.perm-input');
        var total = inputs.length;
        var checked = 0;
        inputs.forEach(function (inp) {
            if (inp.type === 'checkbox' && inp.checked) checked++;
            if (inp.type === 'radio' && inp.checked) checked++;
        });
        var countEl = document.getElementById('count-' + section);
        if (countEl) countEl.textContent = checked + '/' + total;
        // sync module master toggle
        var allToggle = card.querySelector('.perm-select-all');
        if (allToggle) allToggle.checked = (checked === total && total > 0);
    }

    function updateGrantedBadge() {
        var total = 0;
        document.querySelectorAll('.perm-input').forEach(function (inp) {
            if (inp.checked) total++;
        });
        var badge = document.getElementById('grantedBadge');
        if (badge) badge.textContent = total + ' granted';
    }

    function refreshAll() {
        document.querySelectorAll('.perm-card').forEach(function (card) {
            updateCardCount(card.dataset.section);
        });
        updateGrantedBadge();
    }

    /* ── Checkbox / radio change ────────────────────────── */
    document.addEventListener('change', function (e) {
        var inp = e.target;
        if (!inp.classList.contains('perm-input') && !inp.classList.contains('perm-select-all')) return;

        if (inp.classList.contains('perm-select-all')) {
            var section = inp.dataset.section;
            var card = document.querySelector('[data-section="' + section + '"]');
            card.querySelectorAll('.perm-input').forEach(function (i) {
                if (i.type === 'checkbox') i.checked = inp.checked;
                // radios: check the first one when enabling all, uncheck all when disabling
                if (i.type === 'radio') i.checked = false;
            });
            if (inp.checked) {
                // check first radio in each group within this card
                var groups = {};
                card.querySelectorAll('.perm-radio').forEach(function (r) {
                    if (!groups[r.name]) { r.checked = true; groups[r.name] = true; }
                });
            }
        }
        refreshAll();
    });

    /* ── Grant All / Revoke All ─────────────────────────── */
    document.getElementById('btnGrantAll').addEventListener('click', function () {
        document.querySelectorAll('.perm-input[type=checkbox]').forEach(function (c) { c.checked = true; });
        // Check first radio in each group
        var groups = {};
        document.querySelectorAll('.perm-radio').forEach(function (r) {
            if (!groups[r.name]) { r.checked = true; groups[r.name] = true; }
            else if (!r.checked) { r.checked = false; }
        });
        refreshAll();
    });

    document.getElementById('btnRevokeAll').addEventListener('click', function () {
        document.querySelectorAll('.perm-input').forEach(function (i) { i.checked = false; });
        refreshAll();
    });

    /* ── Module search ──────────────────────────────────── */
    document.getElementById('permSearch').addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('.perm-card').forEach(function (card) {
            var name = (card.dataset.moduleName || '').toLowerCase();
            card.classList.toggle('perm-card--hidden', q !== '' && !name.includes(q));
        });
        // Also toggle category rows if all cards hidden
        document.querySelectorAll('.perm-category-row').forEach(function (row) {
            var grid = row.nextElementSibling;
            if (!grid) return;
            var visible = grid.querySelectorAll('.perm-card:not(.perm-card--hidden)').length;
            row.style.display = visible ? '' : 'none';
            grid.style.display = visible ? '' : 'none';
        });
    });

    /* ── Role select → AJAX pre-fill ───────────────────── */
    document.getElementById('roleSelect').addEventListener('change', function () {
        var roleName = this.value;
        if (!roleName) return;
        fetch(rolePermUrl + '?role=' + encodeURIComponent(roleName))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var perms = (data.permissions || []).map(function (p) { return p.toLowerCase(); });
                // First revoke all
                document.querySelectorAll('.perm-input').forEach(function (i) { i.checked = false; });
                // Then grant matching
                document.querySelectorAll('.perm-input[type=checkbox]').forEach(function (cb) {
                    if (perms.includes(cb.value.toLowerCase())) cb.checked = true;
                });
                document.querySelectorAll('.perm-radio').forEach(function (rd) {
                    if (perms.includes(rd.value.toLowerCase())) rd.checked = true;
                });
                refreshAll();
            })
            .catch(function () { /* silent fail */ });
    });

    /* ── Username preview ───────────────────────────────── */
    var unBase = document.getElementById('usernameBase');
    var unPrev = document.getElementById('usernamePreview');
    if (unBase && unPrev) {
        function updatePreview(val) {
            val = val.trim().replace(/[^a-zA-Z0-9_]/g, '');
            unPrev.textContent = val ? 'Username: ' + val + suffix : 'Leave blank to auto-generate';
        }
        unBase.addEventListener('input', function () { updatePreview(this.value); });
        var fnInput = document.getElementById('firstNameInput');
        if (fnInput) {
            fnInput.addEventListener('input', function () {
                if (!unBase.value) updatePreview(this.value.replace(/\s+/g,''));
            });
        }
    }

    /* ── Avatar preview ─────────────────────────────────── */
    var photoInput = document.getElementById('profilePhotoInput');
    var avatarEl   = document.getElementById('avatarPreview');
    if (photoInput && avatarEl) {
        photoInput.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                avatarEl.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
            };
            reader.readAsDataURL(file);
        });
    }

    /* ── Branch access toggle ───────────────────────────── */
    var accessAll = document.getElementById('accessAll');
    if (accessAll) {
        function syncBranches() {
            document.querySelectorAll('.branch-check').forEach(function (c) {
                c.disabled = accessAll.checked;
                if (accessAll.checked) c.checked = false;
            });
        }
        accessAll.addEventListener('change', syncBranches);
        syncBranches();
    }

    /* ── Form submit: convert radio values → permissions[] ─ */
    document.getElementById('createUserForm').addEventListener('submit', function () {
        document.querySelectorAll('.perm-radio:checked').forEach(function (rd) {
            var hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'permissions[]';
            hidden.value = rd.value;
            document.getElementById('createUserForm').appendChild(hidden);
        });
    });

    /* ── Init counts ────────────────────────────────────── */
    refreshAll();

})();
</script>
@endpush

@endsection
            <div class="small text-muted">View file: <code>UserManagement/create.blade.php</code></div>
            <div class="mt-3">
                <a href="{{ url()->previous() }}" class="btn btn-light border">Go Back</a>
                <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
            </div>
        </div>
    </div>
</div>
@endsection
