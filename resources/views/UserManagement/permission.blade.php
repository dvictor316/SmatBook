<?php $page = 'permission'; ?>
@extends('layout.mainlayout')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   ROLE PERMISSIONS PAGE — Reports Hub Style
═══════════════════════════════════════════════════════════════ */

.perm-page { background:#f1f4f9; min-height:100vh; }

/* ─── Role badge ─────────────────────────────────────────────── */
.perm-role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
    padding: 2px 10px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em;
}

/* ─── Tab bar (Reports Hub style) ───────────────────────────── */
.perm-tab-bar{display:flex;gap:0;border-bottom:2px solid #dee2e9;background:#fff;padding:0 24px;overflow-x:auto;scrollbar-width:none;}
.perm-tab-bar::-webkit-scrollbar{display:none;}
.perm-tab{padding:13px 18px;font-size:12.5px;font-weight:600;color:#64748b;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;background:none;border-top:none;border-left:none;border-right:none;white-space:nowrap;display:flex;align-items:center;gap:6px;flex-shrink:0;transition:color .15s,border-color .15s;}
.perm-tab:hover{color:#1e3a5f;}
.perm-tab.active{color:#2563eb;border-bottom-color:#2563eb;}
.perm-tab-cnt{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:9px;background:#e8eef8;color:#2563eb;font-size:10px;font-weight:800;padding:0 4px;}
.perm-tab.active .perm-tab-cnt{background:#2563eb;color:#fff;}

/* ─── Toolbar ────────────────────────────────────────────────── */
.perm-toolbar{background:#fff;border-bottom:1px solid #e4e8f0;padding:10px 24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.perm-toolbar-left{display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap;}
.perm-toolbar-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.perm-role-info{font-size:12.5px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:5px;}
.perm-count-badge{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:20px;border-radius:10px;background:#e8eef8;color:#2563eb;font-size:11px;font-weight:800;padding:0 8px;}
.perm-search-wrap{position:relative;}
.perm-search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;}
.perm-search-box{padding:7px 12px 7px 32px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;color:#1e293b;background:#fff;outline:none;width:200px;transition:border-color .15s,box-shadow .15s;}
.perm-search-box:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.btn-grant-all{padding:6px 14px;font-size:12px;font-weight:700;color:#fff;background:#2563eb;border:none;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:background .15s;}
.btn-grant-all:hover{background:#1d4ed8;}
.btn-revoke-all{padding:6px 14px;font-size:12px;font-weight:600;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .15s;}
.btn-revoke-all:hover{background:#fef2f2;border-color:#fca5a5;color:#dc2626;}

/* ─── Tab panels / body ──────────────────────────────────────── */
.perm-panel{display:none;}
.perm-panel.active{display:block;}
.perm-search-active .perm-panel{display:block!important;}
.perm-search-active .perm-tab-bar{display:none!important;}
.perm-body{padding:14px 16px;background:#f1f4f9;}

/* ─── Module section — left/right Prokip-style layout ───────── */
.perm-section{display:flex;flex-direction:row;align-items:stretch;background:#fff;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:6px;overflow:hidden;transition:box-shadow .12s;}
.perm-section:hover{box-shadow:0 2px 8px rgba(0,0,0,.07);}
.perm-section.perm-section--granted{border-color:#bbf7d0;}
.perm-section.perm-sec--hidden{display:none!important;}
.perm-section.collapsed .perm-col-grid{display:none!important;}

/* Left panel — icon, title, count, Select All */
.perm-sec-head{width:215px;min-width:215px;padding:14px 14px 12px 14px;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-start;gap:8px;border-right:1px solid #e8ecf2;border-bottom:none;background:#fafbfd;cursor:pointer;user-select:none;transition:background .12s;position:relative;}
.perm-sec-head:hover{background:#f3f6fb;}
.perm-section--granted .perm-sec-head{background:#f0fdf4;border-right-color:#bbf7d0;}
.perm-sec-icon-row{display:flex;align-items:center;gap:8px;width:100%;}
.perm-sec-icon{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
.perm-sec-title{font-size:13px;font-weight:800;color:#0f172a;line-height:1.3;flex:1;}
.perm-section--granted .perm-sec-title{color:#15803d;}
.perm-sec-count{font-size:11px;color:#94a3b8;font-weight:600;}
.perm-section--granted .perm-sec-count{color:#16a34a;font-weight:700;}
.perm-select-all-wrap{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#475569;cursor:pointer;user-select:none;padding:5px 10px;border:1px solid #d0d5de;border-radius:5px;background:#fff;transition:all .12s;width:100%;box-sizing:border-box;}
.perm-select-all-wrap:hover{border-color:#93c5fd;color:#2563eb;background:#eff6ff;}
.perm-select-all-wrap input{accent-color:#2563eb;cursor:pointer;width:13px;height:13px;margin:0;flex-shrink:0;}
.perm-sec-chevron{position:absolute;top:12px;right:10px;color:#cbd5e1;font-size:10px;transition:transform .2s;}
.perm-section.collapsed .perm-sec-chevron{transform:rotate(-90deg);}

/* Right panel — clean vertical permission list */
.perm-col-grid{display:block;flex:1;border-top:none;}
.perm-item{display:flex;align-items:center;padding:0 16px;border-bottom:1px solid #f3f4f8;min-height:43px;cursor:pointer;transition:background .1s;}
.perm-item:hover{background:#f8f9ff;}
.perm-item:last-child{border-bottom:none;}
.perm-item:has(input:checked){background:#f0f5ff;}
.perm-item-chk{width:36px;height:43px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.perm-item-chk input[type=checkbox],.perm-item-chk input[type=radio]{width:15px;height:15px;accent-color:#2563eb;cursor:pointer;margin:0;}
.perm-item-label{flex:1;font-size:13px;font-weight:500;color:#374151;padding-right:8px;line-height:1.4;}
.perm-item:has(input:checked) .perm-item-label{color:#1e40af;font-weight:600;}

/* Sub-heading row */
.perm-sub-row{display:flex;align-items:center;padding:5px 16px;background:#f8fafc;border-bottom:1px solid #eef0f5;}
.perm-sub-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;}
.perm-sep-row{border:none;border-top:1px solid #eaecf2;margin:0;display:block;}
@media(max-width:768px){.perm-section{flex-direction:column;}.perm-sec-head{width:100%;min-width:unset;flex-direction:row;flex-wrap:wrap;border-right:none;border-bottom:1px solid #e8ecf2;}.perm-sec-chevron{position:static;}}

/* ─── Sticky save bar ────────────────────────────────────────── */
.perm-action-bar{position:sticky;bottom:0;background:#fff;border-top:1px solid #e4e8f0;padding:12px 24px;display:flex;justify-content:flex-end;gap:10px;z-index:50;box-shadow:0 -4px 16px rgba(0,0,0,.06);}
.btn-perm-save{background:#2563eb;border:none;color:#fff;padding:9px 28px;border-radius:6px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:7px;cursor:pointer;transition:background .2s;}
.btn-perm-save:hover{background:#1d4ed8;}
.btn-perm-cancel{background:#fff;border:1px solid #d0d5e0;color:#6b7280;padding:9px 20px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;transition:all .2s;}
.btn-perm-cancel:hover{background:#f9fafb;color:#374151;}




/* ─── Palette helpers (same as Reports Hub) ──────────────────── */
.pal-blue{background:#eff6ff;color:#2563eb;}
.pal-green{background:#f0fdf4;color:#16a34a;}
.pal-orange{background:#fff7ed;color:#ea580c;}
.pal-purple{background:#fdf4ff;color:#7c3aed;}
.pal-red{background:#fef2f2;color:#dc2626;}
.pal-teal{background:#f0fdfa;color:#0d9488;}
.pal-amber{background:#fffbeb;color:#d97706;}
.pal-slate{background:#f8fafc;color:#475569;}
.pal-indigo{background:#eef2ff;color:#4f46e5;}
.pal-sky{background:#f0f9ff;color:#0284c7;}
.pal-lime{background:#f7fee7;color:#65a30d;}
.pal-rose{background:#fff1f2;color:#e11d48;}
.pal-pink{background:#fdf2f8;color:#db2777;}
</style>
@endpush

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Role Permissions</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">
                        Permissions &mdash;
                        <span class="perm-role-badge"><i class="fa fa-user-tag"></i> {{ $role->name }}</span>
                    </li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="fa fa-arrow-left me-1"></i> Back to Roles
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('roles.permissions.update') }}" method="POST" id="permForm">
        @csrf
        <input type="hidden" name="role_id" value="{{ $role->id }}">

        @php
            $assigned    = $assignedPermissions ?? [];
            $permChecked = fn (string $p) => in_array($p, $assigned, true);

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
            ]; // end $modules

            $countGranted = function(array $items) use ($permChecked) {
                $n = 0;
                foreach ($items as $i) { if (isset($i['p']) && $permChecked($i['p'])) $n++; }
                return $n;
            };
            $countTotal = function(array $items) {
                return count(array_filter($items, fn($i) => isset($i['p'])));
            };

            $categories = [];
            foreach ($modules as $m) { $categories[$m['cat']][] = $m; }

            // Build URL-safe slugs for each category
            $catSlugs = [];
            foreach (array_keys($categories) as $cat) {
                $catSlugs[$cat] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cat));
            }
            $firstSlug = reset($catSlugs);
        @endphp

        <div style="border:1px solid #dee2e9;border-radius:0 0 8px 8px;overflow:hidden;">

            {{-- Tab bar --}}
            <div class="perm-tab-bar">
                @foreach ($categories as $catName => $catModules)
                    @php $slug = $catSlugs[$catName]; @endphp
                    <button type="button" class="perm-tab {{ $slug === $firstSlug ? 'active' : '' }}"
                            data-cat="{{ $slug }}">
                        {{ $catName }}
                        <span class="perm-tab-cnt">{{ count($catModules) }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Toolbar --}}
            <div class="perm-toolbar">
                <div class="perm-toolbar-left">
                    <span class="perm-role-info">
                        <i class="fa fa-user-tag me-1" style="color:#2563eb;"></i>
                        {{ $role->name }}
                    </span>
                    <span class="perm-count-badge" id="grantedBadge">
                        {{ count(array_filter($assigned, fn($p) => $p)) }} granted
                    </span>
                </div>
                <div class="perm-toolbar-right">
                    <div class="perm-search-wrap">
                        <i class="fa fa-search perm-search-icon"></i>
                        <input type="text" class="perm-search-box" id="permSearch"
                               placeholder="Search modules…" autocomplete="off">
                    </div>
                    <button type="button" class="btn-revoke-all" id="btnRevokeAll">
                        <i class="fa fa-times-circle"></i> Revoke All
                    </button>
                    <button type="button" class="btn-grant-all" id="btnGrantAll">
                        <i class="fa fa-check-circle"></i> Grant All
                    </button>
                </div>
            </div>

            {{-- Tab panels --}}
            @foreach ($categories as $catName => $catModules)
                @php $slug = $catSlugs[$catName]; @endphp
                <div class="perm-panel {{ $slug === $firstSlug ? 'active' : '' }}"
                     data-panel="{{ $slug }}">
                    <div class="perm-body">
                        @foreach ($catModules as $mod)
                            @php
                                $granted  = $countGranted($mod['items']);
                                $total    = $countTotal($mod['items']);
                                $isFull   = ($granted === $total && $total > 0);
                                $palMap   = [
                                    'icon-blue'   => 'pal-blue',
                                    'icon-indigo' => 'pal-indigo',
                                    'icon-purple' => 'pal-purple',
                                    'icon-teal'   => 'pal-teal',
                                    'icon-green'  => 'pal-green',
                                    'icon-gold'   => 'pal-amber',
                                    'icon-sky'    => 'pal-sky',
                                    'icon-lime'   => 'pal-lime',
                                    'icon-rose'   => 'pal-rose',
                                    'icon-orange' => 'pal-orange',
                                    'icon-pink'   => 'pal-pink',
                                    'icon-slate'  => 'pal-slate',
                                ];
                                $palClass = $palMap[$mod['ic']] ?? 'pal-slate';
                            @endphp
                            <div class="perm-section {{ $isFull ? 'perm-section--granted' : '' }}"
                                 data-module-name="{{ strtolower($mod['group']) }}"
                                 data-section="{{ $mod['section'] }}"
                                 data-cat="{{ $slug }}">

                                <div class="perm-sec-head" onclick="togglePermSection(this)">
                                    <div class="perm-sec-icon-row">
                                        <span class="perm-sec-icon {{ $palClass }}">
                                            <i class="fas {{ $mod['icon'] }}"></i>
                                        </span>
                                        <span class="perm-sec-title">{{ $mod['group'] }}</span>
                                    </div>
                                    <span class="perm-sec-count" id="count-{{ $mod['section'] }}">{{ $granted }}/{{ $total }}</span>
                                    <label class="perm-select-all-wrap" onclick="event.stopPropagation()">
                                        <input type="checkbox"
                                               class="perm-select-all"
                                               data-section="{{ $mod['section'] }}"
                                               {{ $isFull ? 'checked' : '' }}>
                                        Select All
                                    </label>
                                    <i class="fas fa-chevron-up perm-sec-chevron"></i>
                                </div>

                                <div class="perm-col-grid">
                                    @foreach ($mod['items'] as $item)
                                        @if ($item['t'] === 'sp')
                                            <hr class="perm-sep-row">
                                        @elseif ($item['t'] === 'hd')
                                            <div class="perm-sub-row"><span class="perm-sub-title">{{ $item['l'] }}</span></div>
                                        @elseif ($item['t'] === 'cb')
                                            @php
                                                $parts = explode('.', $item['p']);
                                                $iname = "permissions[{$parts[0]}][{$parts[1]}][{$parts[2]}]";
                                            @endphp
                                            <label class="perm-item">
                                                <span class="perm-item-chk">
                                                    <input type="checkbox"
                                                           class="perm-input"
                                                           name="{{ $iname }}"
                                                           value="1"
                                                           data-section="{{ $mod['section'] }}"
                                                           {{ $permChecked($item['p']) ? 'checked' : '' }}>
                                                </span>
                                                <span class="perm-item-label">{{ $item['l'] }}</span>
                                            </label>
                                        @elseif ($item['t'] === 'rd')
                                            <label class="perm-item">
                                                <span class="perm-item-chk">
                                                    <input type="radio"
                                                           class="perm-input"
                                                           name="{{ $item['rn'] }}"
                                                           value="{{ $item['p'] }}"
                                                           data-section="{{ $mod['section'] }}"
                                                           {{ $permChecked($item['p']) ? 'checked' : '' }}>
                                                </span>
                                                <span class="perm-item-label">{{ $item['l'] }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>

                            </div>{{-- /.perm-section --}}
                        @endforeach
                    </div>{{-- /.perm-body --}}
                </div>{{-- /.perm-panel --}}
            @endforeach

        </div>

        <div class="perm-action-bar">
            <a href="{{ route('roles.index') }}" class="btn-perm-cancel">Cancel</a>
            <button type="submit" class="btn-perm-save">
                <i class="fa fa-save"></i> Save Permissions
            </button>
        </div>

    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
    window.togglePermSection = function(head) {
        head.closest('.perm-section').classList.toggle('collapsed');
    };

(function () {
    /* ── Helpers ─────────────────────────────────── */
    function inputs(s)    { return document.querySelectorAll('.perm-input[data-section="'+s+'"]'); }
    function selectAll(s) { return document.querySelector('.perm-select-all[data-section="'+s+'"]'); }

    function updateCount(s) {
        var ins = inputs(s), n = 0, tot = 0, rg = {};
        ins.forEach(function(i) {
            if (i.type === 'checkbox') { tot++; if (i.checked) n++; }
            else if (i.type === 'radio') {
                if (!rg[i.name]) { rg[i.name] = false; tot++; }
                if (i.checked) rg[i.name] = true;
            }
        });
        Object.values(rg).forEach(function(v){ if(v) n++; });
        var el = document.getElementById('count-'+s);
        if (el) el.textContent = n+'/'+tot;
        var sec = document.querySelector('.perm-section[data-section="'+s+'"]');
        if (sec) sec.classList.toggle('perm-section--granted', tot > 0 && n === tot);
    }

    function updateBadge() {
        var n = 0, rg = {};
        document.querySelectorAll('.perm-input').forEach(function(i) {
            if (i.type === 'checkbox' && i.checked) n++;
            if (i.type === 'radio') rg[i.name] = rg[i.name] || i.checked;
        });
        Object.values(rg).forEach(function(v){ if(v) n++; });
        var el = document.getElementById('grantedBadge');
        if (el) el.textContent = n+' granted';
    }

    function syncToggle(s) {
        var ins = inputs(s), sa = selectAll(s); if (!sa) return;
        var ok = true, rg = {};
        ins.forEach(function(i) {
            if (i.type === 'checkbox' && !i.checked) ok = false;
            if (i.type === 'radio') rg[i.name] = rg[i.name] || i.checked;
        });
        sa.checked = ok && (Object.keys(rg).length === 0 || Object.values(rg).every(Boolean));
    }

    /* ── Tab switching ─────────────────────────────────────────── */
    document.querySelectorAll('.perm-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            // clear search when switching tabs
            var searchEl = document.getElementById('permSearch');
            if (searchEl && searchEl.value) {
                searchEl.value = '';
                exitSearchMode();
            }
            document.querySelectorAll('.perm-tab').forEach(function(t){ t.classList.remove('active'); });
            document.querySelectorAll('.perm-panel').forEach(function(p){ p.classList.remove('active'); });
            this.classList.add('active');
            var panel = document.querySelector('.perm-panel[data-panel="'+this.dataset.cat+'"]');
            if (panel) panel.classList.add('active');
        });
    });

    /* ── Select-All toggles ─────────────────────────────────── */
    document.querySelectorAll('.perm-select-all').forEach(function(sa) {
        sa.addEventListener('change', function() {
            var s = this.dataset.section, on = this.checked, rg = {};
            inputs(s).forEach(function(i) {
                if (i.type === 'checkbox') i.checked = on;
                else if (on && i.type === 'radio' && !rg[i.name]) { rg[i.name] = i; }
                else if (!on && i.type === 'radio') i.checked = false;
            });
            if (on) Object.values(rg).forEach(function(r){ r.checked = true; });
            updateCount(s); updateBadge();
        });
    });

    /* ── Individual inputs ─────────────────────────────────────── */
    document.querySelectorAll('.perm-input').forEach(function(i) {
        i.addEventListener('change', function() {
            syncToggle(this.dataset.section);
            updateCount(this.dataset.section);
            updateBadge();
        });
    });

    /* ── Grant All ─────────────────────────────────────────────── */
    document.getElementById('btnGrantAll').addEventListener('click', function() {
        var rg = {};
        document.querySelectorAll('.perm-input').forEach(function(i) {
            if (i.type === 'checkbox') i.checked = true;
            if (i.type === 'radio' && !rg[i.name]) rg[i.name] = i;
        });
        Object.values(rg).forEach(function(r){ r.checked = true; });
        document.querySelectorAll('.perm-select-all').forEach(function(sa){ sa.checked = true; });
        document.querySelectorAll('.perm-section').forEach(function(c){ updateCount(c.dataset.section); });
        updateBadge();
    });

    /* ── Revoke All ────────────────────────────────────────────── */
    document.getElementById('btnRevokeAll').addEventListener('click', function() {
        document.querySelectorAll('.perm-input').forEach(function(i){ i.checked = false; });
        document.querySelectorAll('.perm-select-all').forEach(function(sa){ sa.checked = false; });
        document.querySelectorAll('.perm-section').forEach(function(c){ updateCount(c.dataset.section); });
        updateBadge();
    });

    /* ── Search ──────────────────────────────────────────────── */
    function exitSearchMode() {
        document.getElementById('permForm').classList.remove('perm-search-active');
        var activeTab = document.querySelector('.perm-tab.active');
        if (activeTab) {
            document.querySelectorAll('.perm-panel').forEach(function(p){ p.classList.remove('active'); });
            var panel = document.querySelector('.perm-panel[data-panel="'+activeTab.dataset.cat+'"]');
            if (panel) panel.classList.add('active');
        }
        document.querySelectorAll('.perm-section').forEach(function(s){ s.classList.remove('perm-sec--hidden'); });
        var tabBar = document.querySelector('.perm-tab-bar');
        if (tabBar) tabBar.style.display = '';
    }

    document.getElementById('permSearch').addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        var form = document.getElementById('permForm');
        var tabBar = document.querySelector('.perm-tab-bar');
        if (!q) { exitSearchMode(); return; }
        form.classList.add('perm-search-active');
        document.querySelectorAll('.perm-panel').forEach(function(p){ p.style.display = 'block'; });
        if (tabBar) tabBar.style.display = 'none';
        document.querySelectorAll('.perm-section').forEach(function(sec) {
            var match = sec.dataset.moduleName && sec.dataset.moduleName.includes(q);
            sec.classList.toggle('perm-sec--hidden', !match);
        });
        document.querySelectorAll('.perm-panel').forEach(function(panel) {
            var any = Array.from(panel.querySelectorAll('.perm-section')).some(function(s){
                return !s.classList.contains('perm-sec--hidden');
            });
            panel.style.display = any ? 'block' : 'none';
        });
    });

    /* ── Init ──────────────────────────────────────────────────── */
    document.querySelectorAll('.perm-section').forEach(function(c){ updateCount(c.dataset.section); });
    updateBadge();
}());
</script>
@endpush
