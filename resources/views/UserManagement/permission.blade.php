<?php $page = 'permission'; ?>
@extends('layout.mainlayout')

@section('page-title', 'Role Permissions')

@push('styles')
<style>
/* ══════════════════════════════════════════════════════════════
   ERP Permission UI  —  clean card layout
══════════════════════════════════════════════════════════════ */

.perm-page { background: #f1f5f9; min-height: 100vh; }

/* ── Header bar ──────────────────────────────────────────── */
.perm-header-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.perm-role-label { font-size: 1.15rem; font-weight: 700; color: #1e293b; }
.perm-role-label small {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #94a3b8;
    margin-bottom: 2px;
}
.perm-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    border: 1px solid #dbeafe;
}

/* ── Category tabs ───────────────────────────────────────── */
.perm-cat-nav {
    display: flex;
    gap: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-bottom: none;
    border-radius: 10px 10px 0 0;
    padding: 0 16px;
    flex-wrap: wrap;
}
.perm-cat-tab {
    padding: 13px 18px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    letter-spacing: .03em;
    white-space: nowrap;
    transition: color .15s, border-color .15s;
}
.perm-cat-tab:hover { color: #2563eb; }
.perm-cat-tab.active { color: #2563eb; border-bottom-color: #2563eb; font-weight: 700; }
.perm-cat-cnt {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 16px;
    padding: 0 5px;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 10px;
    margin-left: 5px;
    background: #e2e8f0;
    color: #64748b;
}
.perm-cat-tab.active .perm-cat-cnt { background: #dbeafe; color: #1d4ed8; }

/* ── Tab panel ───────────────────────────────────────────── */
.perm-tab-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0 0 10px 10px;
    padding: 20px 20px 0;
}

/* ── Toolbar ─────────────────────────────────────────────── */
.perm-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}
.perm-toolbar-left  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.perm-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.perm-cat-label { font-size: 0.8rem; font-weight: 600; color: #475569; }
.perm-search-wrap { position: relative; }
.perm-search-box {
    padding: 7px 12px 7px 32px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.8rem;
    width: 200px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.perm-search-box:focus { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
.perm-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.78rem;
    pointer-events: none;
}
.btn-grant-all, .btn-revoke-all {
    padding: 6px 13px;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: background .15s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-grant-all  { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
.btn-grant-all:hover  { background: #dbeafe; }
.btn-revoke-all { background: #fff1f2; color: #e11d48; border-color: #ffe4e6; }
.btn-revoke-all:hover { background: #ffe4e6; }

/* ── Module grid ─────────────────────────────────────────── */
.perm-modules-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    padding-bottom: 24px;
}
@media (max-width: 960px) { .perm-modules-grid { grid-template-columns: 1fr; } }
.perm-card.search-hidden { display: none; }

/* ── Module card ─────────────────────────────────────────── */
.perm-card {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    transition: box-shadow .15s, border-color .15s;
}
.perm-card:hover     { box-shadow: 0 4px 14px rgba(37,99,235,.07); border-color: #bfdbfe; }
.perm-card.is-granted { border-color: #86efac; }

/* Card header */
.perm-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    user-select: none;
    gap: 8px;
}
.perm-card.collapsed .perm-card-head { border-bottom: none; }
.perm-card-head-left  { display: flex; align-items: center; gap: 10px; min-width: 0; }
.perm-card-icon {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    flex-shrink: 0;
}
.perm-card-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
.perm-card-head-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.perm-card-cnt {
    font-size: 0.7rem;
    font-weight: 600;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 10px;
    white-space: nowrap;
}
.perm-card.is-granted .perm-card-cnt { background: #dcfce7; color: #16a34a; }

/* Select-all label */
.perm-sel-all-lbl {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.74rem;
    font-weight: 700;
    color: #2563eb;
    cursor: pointer;
    padding: 3px 8px;
    border-radius: 5px;
    border: 1px solid #dbeafe;
    background: #eff6ff;
    transition: background .12s;
    white-space: nowrap;
}
.perm-sel-all-lbl:hover { background: #dbeafe; }
.perm-sel-all-lbl input { accent-color: #2563eb; width: 13px; height: 13px; }

/* Chevron */
.perm-chevron { color: #94a3b8; font-size: 0.72rem; transition: transform .2s; flex-shrink: 0; }
.perm-card.collapsed .perm-chevron { transform: rotate(-90deg); }

/* Card body */
.perm-card-body { padding: 14px 16px 12px; }
.perm-card.collapsed .perm-card-body { display: none; }

/* Sub-section header */
.perm-sub-head {
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #94a3b8;
    padding: 6px 0 4px;
    margin-top: 2px;
}
.perm-sub-head:first-child { padding-top: 0; margin-top: 0; }

/* Separator */
.perm-hr { border: none; border-top: 1px solid #f1f5f9; margin: 10px 0; }

/* ── Radio group (view / no-sell) ────────────────────────── */
.perm-radio-group {
    display: flex;
    flex-direction: column;
    gap: 1px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    overflow: hidden;
    margin-bottom: 6px;
}
.perm-radio-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 7px 12px;
    cursor: pointer;
    transition: background .1s;
}
.perm-radio-item:hover { background: #eff6ff; }
.perm-radio-item:has(input:checked) { background: #eff6ff; }
.perm-radio-item input[type=radio] {
    accent-color: #2563eb;
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    cursor: pointer;
}
.perm-radio-item span { font-size: 0.83rem; color: #374151; line-height: 1.3; }
.perm-radio-item:has(input:checked) span { color: #1d4ed8; font-weight: 600; }

/* ── Checkbox group (actions) ────────────────────────────── */
.perm-cb-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}
.perm-cb-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    transition: border-color .12s, background .12s;
}
.perm-cb-item:hover { border-color: #93c5fd; background: #f0f9ff; }
.perm-cb-item:has(input:checked) { border-color: #93c5fd; background: #eff6ff; }
.perm-cb-item input[type=checkbox] {
    accent-color: #2563eb;
    width: 13px;
    height: 13px;
    flex-shrink: 0;
    cursor: pointer;
}
.perm-cb-item span { font-size: 0.8rem; color: #374151; white-space: nowrap; }
.perm-cb-item:has(input:checked) span { color: #1d4ed8; font-weight: 600; }

/* ── Sticky action bar ───────────────────────────────────── */
.perm-action-bar {
    position: sticky;
    bottom: 0;
    z-index: 20;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    box-shadow: 0 -3px 12px rgba(0,0,0,.06);
    margin: 0 -20px;
    border-radius: 0 0 10px 10px;
}

/* ── Icon colour palettes ────────────────────────────────── */
.pal-blue   { background: #eff6ff; color: #2563eb; }
.pal-indigo { background: #eef2ff; color: #4f46e5; }
.pal-purple { background: #f5f3ff; color: #7c3aed; }
.pal-teal   { background: #f0fdfa; color: #0d9488; }
.pal-green  { background: #f0fdf4; color: #16a34a; }
.pal-amber  { background: #fffbeb; color: #d97706; }
.pal-sky    { background: #f0f9ff; color: #0284c7; }
.pal-lime   { background: #f7fee7; color: #65a30d; }
.pal-rose   { background: #fff1f2; color: #e11d48; }
.pal-orange { background: #fff7ed; color: #ea580c; }
.pal-pink   { background: #fdf2f8; color: #db2777; }
.pal-slate  { background: #f8fafc; color: #475569; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
<div class="perm-page py-4 px-3 px-md-4">

@php
    /* ─── Variables from controller ─────────────────────────────────────
     *  $role                → Role model
     *  $assignedPermissions → flat array of granted permission name strings
     * ─────────────────────────────────────────────────────────────── */
    $assignedPermissions = $assignedPermissions ?? $assigned ?? [];
    $assigned = $assignedPermissions; // backward-compat alias
    $permChecked = fn($p) => in_array($p, $assignedPermissions, true);

    /* ─── Full module / permission catalogue ─────────────────────── */
    $modules = [

        /* ── CORE ──────────────────────────────────────────────── */
        ['group'=>'Dashboard',            'section'=>'dashboard',              'icon'=>'fa-tachometer-alt',     'ic'=>'pal-blue',   'cat'=>'Core',
         'items'=>[
             ['t'=>'cb','p'=>'dashboard.overview.view','l'=>'View Dashboard'],
         ]],
        ['group'=>'User Management',      'section'=>'user_mgmt',              'icon'=>'fa-users',               'ic'=>'pal-indigo', 'cat'=>'Core',
         'items'=>[
             ['t'=>'cb','p'=>'user_management.users.view',  'l'=>'View Users'],
             ['t'=>'cb','p'=>'user_management.users.create','l'=>'Add User'],
             ['t'=>'cb','p'=>'user_management.users.edit',  'l'=>'Edit User'],
             ['t'=>'cb','p'=>'user_management.users.delete','l'=>'Delete User'],
         ]],
        ['group'=>'Roles',                'section'=>'roles',                  'icon'=>'fa-user-shield',         'ic'=>'pal-purple', 'cat'=>'Core',
         'items'=>[
             ['t'=>'cb','p'=>'roles.roles.view',  'l'=>'View Roles'],
             ['t'=>'cb','p'=>'roles.roles.create','l'=>'Add Role'],
             ['t'=>'cb','p'=>'roles.roles.edit',  'l'=>'Edit Role'],
             ['t'=>'cb','p'=>'roles.roles.delete','l'=>'Delete Role'],
         ]],

        /* ── SALES & CRM ────────────────────────────────────────── */
        ['group'=>'Customers',            'section'=>'customers',              'icon'=>'fa-address-book',        'ic'=>'pal-sky',    'cat'=>'Sales & CRM',
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
             ['t'=>'cb','p'=>'customers.customers.create','l'=>'Add'],
             ['t'=>'cb','p'=>'customers.customers.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'customers.customers.delete','l'=>'Delete'],
         ]],
        ['group'=>'Sales / Invoice',      'section'=>'invoices',               'icon'=>'fa-file-invoice-dollar', 'ic'=>'pal-green',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[invoices_view]','p'=>'sales.invoices.view_all','l'=>'View All Invoices'],
             ['t'=>'rd','rn'=>'perm_radio[invoices_view]','p'=>'sales.invoices.view_own','l'=>'View Own Invoices'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'sales.invoices.create','l'=>'Add'],
             ['t'=>'cb','p'=>'sales.invoices.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'sales.invoices.delete','l'=>'Delete'],
         ]],
        ['group'=>'POS Sales',            'section'=>'pos',                    'icon'=>'fa-cash-register',       'ic'=>'pal-amber',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'sales.pos.view',  'l'=>'View POS'],
             ['t'=>'cb','p'=>'sales.pos.create','l'=>'Create POS Sale'],
         ]],
        ['group'=>'Quotations',           'section'=>'quotations',             'icon'=>'fa-file-alt',            'ic'=>'pal-sky',    'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[quotations_view]','p'=>'sales.quotations.view_all','l'=>'View All Quotations'],
             ['t'=>'rd','rn'=>'perm_radio[quotations_view]','p'=>'sales.quotations.view_own','l'=>'View Own Quotations'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'sales.quotations.create','l'=>'Add'],
             ['t'=>'cb','p'=>'sales.quotations.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'sales.quotations.delete','l'=>'Delete'],
         ]],
        ['group'=>'Estimates',            'section'=>'estimates',              'icon'=>'fa-file-invoice',        'ic'=>'pal-lime',   'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'estimates.estimates.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'estimates.estimates.create','l'=>'Add'],
             ['t'=>'cb','p'=>'estimates.estimates.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'estimates.estimates.delete','l'=>'Delete'],
         ]],
        ['group'=>'Recurring Invoices',   'section'=>'recurring_invoices',     'icon'=>'fa-calendar-alt',        'ic'=>'pal-teal',   'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.create','l'=>'Add'],
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.delete','l'=>'Delete'],
         ]],
        ['group'=>'Follow-Ups',           'section'=>'follow_ups',             'icon'=>'fa-phone-alt',           'ic'=>'pal-pink',   'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'follow_ups.follow_ups.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'follow_ups.follow_ups.create','l'=>'Add'],
             ['t'=>'cb','p'=>'follow_ups.follow_ups.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'follow_ups.follow_ups.delete','l'=>'Delete'],
         ]],
        ['group'=>'Collections Hub',      'section'=>'collections_hub',        'icon'=>'fa-coins',               'ic'=>'pal-amber',  'cat'=>'Sales & CRM',
         'items'=>[
             ['t'=>'cb','p'=>'collections_hub.collections_hub.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'collections_hub.collections_hub.create','l'=>'Add'],
             ['t'=>'cb','p'=>'collections_hub.collections_hub.edit',  'l'=>'Edit'],
         ]],

        /* ── PURCHASING ─────────────────────────────────────────── */
        ['group'=>'Supplier',             'section'=>'supplier',               'icon'=>'fa-truck',               'ic'=>'pal-orange', 'cat'=>'Purchasing',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[vendors_view]','p'=>'vendors.vendors.view_all','l'=>'View All Suppliers'],
             ['t'=>'rd','rn'=>'perm_radio[vendors_view]','p'=>'vendors.vendors.view_own','l'=>'View Own Suppliers'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'vendors.vendors.create','l'=>'Add'],
             ['t'=>'cb','p'=>'vendors.vendors.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'vendors.vendors.delete','l'=>'Delete'],
         ]],
        ['group'=>'Purchase',             'section'=>'purchase',               'icon'=>'fa-shopping-cart',       'ic'=>'pal-rose',   'cat'=>'Purchasing',
         'items'=>[
             ['t'=>'rd','rn'=>'perm_radio[purchases_view]','p'=>'purchases.purchases.view_all','l'=>'View All Purchases'],
             ['t'=>'rd','rn'=>'perm_radio[purchases_view]','p'=>'purchases.purchases.view_own','l'=>'View Own Purchases'],
             ['t'=>'sp'],
             ['t'=>'cb','p'=>'purchases.purchases.create',        'l'=>'Add'],
             ['t'=>'cb','p'=>'purchases.purchases.edit',          'l'=>'Edit'],
             ['t'=>'cb','p'=>'purchases.purchases.delete',        'l'=>'Delete'],
             ['t'=>'cb','p'=>'purchases.purchases.add_payment',   'l'=>'Add Payment'],
             ['t'=>'cb','p'=>'purchases.purchases.edit_payment',  'l'=>'Edit Payment'],
             ['t'=>'cb','p'=>'purchases.purchases.delete_payment','l'=>'Delete Payment'],
         ]],
        ['group'=>'Purchase Orders',      'section'=>'purchase_orders',        'icon'=>'fa-clipboard-list',      'ic'=>'pal-indigo', 'cat'=>'Purchasing',
         'items'=>[
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.create','l'=>'Add'],
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'purchase_orders.purchase_orders.delete','l'=>'Delete'],
         ]],

        /* ── INVENTORY ──────────────────────────────────────────── */
        ['group'=>'Product',              'section'=>'product',                'icon'=>'fa-box',                 'ic'=>'pal-orange', 'cat'=>'Inventory',
         'items'=>[
             ['t'=>'cb','p'=>'inventory.products.view',               'l'=>'View'],
             ['t'=>'cb','p'=>'inventory.products.create',             'l'=>'Add'],
             ['t'=>'cb','p'=>'inventory.products.edit',               'l'=>'Edit'],
             ['t'=>'cb','p'=>'inventory.products.delete',             'l'=>'Delete'],
             ['t'=>'cb','p'=>'inventory.products.add_opening_stock',  'l'=>'Opening Stock'],
             ['t'=>'cb','p'=>'inventory.products.view_purchase_price','l'=>'Purchase Price'],
         ]],
        ['group'=>'Stock Manager',        'section'=>'stock',                  'icon'=>'fa-warehouse',           'ic'=>'pal-teal',   'cat'=>'Inventory',
         'items'=>[
             ['t'=>'hd','l'=>'Stock'],
             ['t'=>'cb','p'=>'inventory.stock.view','l'=>'View Stock'],
             ['t'=>'cb','p'=>'inventory.stock.edit','l'=>'Edit Stock'],
             ['t'=>'sp'],
             ['t'=>'hd','l'=>'Categories'],
             ['t'=>'cb','p'=>'inventory.categories.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'inventory.categories.create','l'=>'Add'],
             ['t'=>'cb','p'=>'inventory.categories.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'inventory.categories.delete','l'=>'Delete'],
         ]],

        /* ── FINANCE ────────────────────────────────────────────── */
        ['group'=>'Expenses',             'section'=>'expenses',               'icon'=>'fa-receipt',             'ic'=>'pal-rose',   'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'finance.expenses.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'finance.expenses.create','l'=>'Add'],
             ['t'=>'cb','p'=>'finance.expenses.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'finance.expenses.delete','l'=>'Delete'],
         ]],
        ['group'=>'Payments',             'section'=>'payments',               'icon'=>'fa-credit-card',         'ic'=>'pal-green',  'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'finance.payments.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'finance.payments.create','l'=>'Add'],
             ['t'=>'cb','p'=>'finance.payments.edit',  'l'=>'Edit'],
         ]],
        ['group'=>'Accounting',           'section'=>'accounting',             'icon'=>'fa-book',                'ic'=>'pal-indigo', 'cat'=>'Finance',
         'items'=>[
             ['t'=>'hd','l'=>'Chart of Accounts'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.create','l'=>'Add'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'accounting.chart_of_accounts.delete','l'=>'Delete'],
             ['t'=>'sp'],
             ['t'=>'hd','l'=>'Bank Reconciliation'],
             ['t'=>'cb','p'=>'accounting.bank_reconciliation.view','l'=>'View'],
             ['t'=>'cb','p'=>'accounting.bank_reconciliation.edit','l'=>'Edit'],
             ['t'=>'sp'],
             ['t'=>'hd','l'=>'Manual Journal'],
             ['t'=>'cb','p'=>'accounting.manual_journal.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'accounting.manual_journal.create','l'=>'Add Entry'],
             ['t'=>'cb','p'=>'accounting.manual_journal.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'accounting.manual_journal.delete','l'=>'Delete'],
         ]],
        ['group'=>'Recurring Transactions','section'=>'recurring_transactions','icon'=>'fa-sync-alt',            'ic'=>'pal-sky',    'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.create','l'=>'Add'],
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.delete','l'=>'Delete'],
         ]],
        ['group'=>'Payment Summary',      'section'=>'payment_summary',        'icon'=>'fa-wallet',              'ic'=>'pal-amber',  'cat'=>'Finance',
         'items'=>[
             ['t'=>'cb','p'=>'payment_summary.payment_summary.view','l'=>'View Payment Summary'],
         ]],

        /* ── BUDGETING ──────────────────────────────────────────── */
        ['group'=>'Budgets',              'section'=>'budgets',                'icon'=>'fa-chart-pie',           'ic'=>'pal-purple', 'cat'=>'Budgeting',
         'items'=>[
             ['t'=>'cb','p'=>'budgets.budgets.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'budgets.budgets.create','l'=>'Add'],
             ['t'=>'cb','p'=>'budgets.budgets.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'budgets.budgets.delete','l'=>'Delete'],
         ]],
        ['group'=>'Fixed Assets',         'section'=>'fixed_assets',           'icon'=>'fa-building',            'ic'=>'pal-slate',  'cat'=>'Budgeting',
         'items'=>[
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.create','l'=>'Add'],
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'fixed_assets.fixed_assets.delete','l'=>'Delete'],
         ]],
        ['group'=>'Expense Claims',       'section'=>'expense_claims',         'icon'=>'fa-file-medical',        'ic'=>'pal-rose',   'cat'=>'Budgeting',
         'items'=>[
             ['t'=>'cb','p'=>'expense_claims.expense_claims.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'expense_claims.expense_claims.create','l'=>'Submit'],
             ['t'=>'cb','p'=>'expense_claims.expense_claims.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'expense_claims.expense_claims.delete','l'=>'Delete'],
         ]],

        /* ── HR & PAYROLL ───────────────────────────────────────── */
        ['group'=>'Payroll',              'section'=>'payroll',                'icon'=>'fa-money-bill-wave',     'ic'=>'pal-green',  'cat'=>'HR & Payroll',
         'items'=>[
             ['t'=>'cb','p'=>'payroll.payroll.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'payroll.payroll.create','l'=>'Create'],
             ['t'=>'cb','p'=>'payroll.payroll.edit',  'l'=>'Edit'],
         ]],
        ['group'=>'Projects',             'section'=>'projects',               'icon'=>'fa-project-diagram',     'ic'=>'pal-blue',   'cat'=>'HR & Payroll',
         'items'=>[
             ['t'=>'cb','p'=>'projects.projects.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'projects.projects.create','l'=>'Add'],
             ['t'=>'cb','p'=>'projects.projects.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'projects.projects.delete','l'=>'Delete'],
         ]],

        /* ── COMPLIANCE ─────────────────────────────────────────── */
        ['group'=>'Tax',                  'section'=>'tax',                    'icon'=>'fa-percentage',          'ic'=>'pal-rose',   'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'tax.filings.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'tax.filings.create','l'=>'Add'],
             ['t'=>'cb','p'=>'tax.filings.edit',  'l'=>'Edit'],
         ]],
        ['group'=>'Approval Queue',       'section'=>'approval_queue',         'icon'=>'fa-check-double',        'ic'=>'pal-teal',   'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'approval_queue.approval_queue.view','l'=>'View'],
             ['t'=>'cb','p'=>'approval_queue.approval_queue.edit','l'=>'Approve / Reject'],
         ]],
        ['group'=>'Period Close',         'section'=>'period_close',           'icon'=>'fa-lock',                'ic'=>'pal-slate',  'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'period_close.period_close.view',   'l'=>'View'],
             ['t'=>'cb','p'=>'period_close.period_close.execute','l'=>'Execute'],
         ]],
        ['group'=>'Activity Log',         'section'=>'activity_log',           'icon'=>'fa-history',             'ic'=>'pal-slate',  'cat'=>'Compliance',
         'items'=>[
             ['t'=>'cb','p'=>'activity_log.activity_log.view','l'=>'View'],
         ]],

        /* ── ADMINISTRATIVE ─────────────────────────────────────── */
        ['group'=>'Reports',              'section'=>'reports',                'icon'=>'fa-chart-bar',           'ic'=>'pal-blue',   'cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'reports.reports.view','l'=>'View Reports'],
         ]],
        ['group'=>'Branches',             'section'=>'branches',               'icon'=>'fa-code-branch',         'ic'=>'pal-indigo', 'cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'branches.branches.view',  'l'=>'View'],
             ['t'=>'cb','p'=>'branches.branches.create','l'=>'Add'],
             ['t'=>'cb','p'=>'branches.branches.edit',  'l'=>'Edit'],
             ['t'=>'cb','p'=>'branches.branches.delete','l'=>'Delete'],
         ]],
        ['group'=>'Settings',             'section'=>'settings',               'icon'=>'fa-cog',                 'ic'=>'pal-slate',  'cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'settings.settings.view','l'=>'View Settings'],
             ['t'=>'cb','p'=>'settings.settings.edit','l'=>'Edit Settings'],
         ]],
        ['group'=>'Applications',         'section'=>'applications',           'icon'=>'fa-th-large',            'ic'=>'pal-purple', 'cat'=>'Administrative',
         'items'=>[
             ['t'=>'cb','p'=>'applications.chat.view',    'l'=>'Chat'],
             ['t'=>'cb','p'=>'applications.calendar.view','l'=>'Calendar'],
             ['t'=>'cb','p'=>'applications.messages.view','l'=>'Messages'],
         ]],

    ]; /* end $modules */

    /* ─── Helpers ─────────────────────────────────────────────────── */
    $countGranted = function (array $items) use ($permChecked) {
        $n = 0;
        foreach ($items as $i) { if (isset($i['p']) && $permChecked($i['p'])) $n++; }
        return $n;
    };
    $countTotal = function (array $items) {
        return count(array_filter($items, fn($i) => isset($i['p'])));
    };

    /* ─── Build category map ──────────────────────────────────────── */
    $categories = [];
    foreach ($modules as $m) { $categories[$m['cat']][] = $m; }

    $catSlugs = [];
    foreach (array_keys($categories) as $cat) {
        $catSlugs[$cat] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cat));
    }
    $firstSlug = reset($catSlugs);

    /* ─── Total granted count (radio groups counted once each) ───── */
    $totalGranted = 0;
    $radioSeen    = [];
    foreach ($assignedPermissions as $p) {
        if (!$p) continue;
        $isRadio = false;
        foreach ($modules as $mod) {
            foreach ($mod['items'] as $item) {
                if (isset($item['p']) && $item['p'] === $p && $item['t'] === 'rd') {
                    if (!isset($radioSeen[$item['rn']])) {
                        $radioSeen[$item['rn']] = true;
                        $totalGranted++;
                    }
                    $isRadio = true;
                    break 2;
                }
            }
        }
        if (!$isRadio) $totalGranted++;
    }
@endphp

    {{-- ── Page header ──────────────────────────────────────────────── --}}
    <div class="perm-header-card">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('roles.index') }}" class="btn btn-light border btn-sm" style="padding:6px 14px;">
                <i class="fas fa-arrow-left me-1"></i> Roles
            </a>
            <div class="perm-role-label">
                <small>Editing Permissions</small>
                {{ $role->name }}
            </div>
        </div>
        <span class="perm-count-badge">
            <i class="fas fa-shield-alt"></i>
            <span id="grantedCount">{{ $totalGranted }}</span> permissions granted
        </span>
    </div>

    {{-- ── Main form ─────────────────────────────────────────────────── --}}
    <form action="{{ route('roles.permissions.update') }}" method="POST" id="permForm">
        @csrf
        <input type="hidden" name="role_id" value="{{ $role->id }}">

        {{-- Category tab navigation --}}
        <div class="perm-cat-nav">
            @foreach ($categories as $catName => $catModules)
                @php $slug = $catSlugs[$catName]; @endphp
                <button type="button"
                        class="perm-cat-tab {{ $slug === $firstSlug ? 'active' : '' }}"
                        data-tab="{{ $slug }}">
                    {{ $catName }}
                    <span class="perm-cat-cnt">{{ count($catModules) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Tab panels (one per category) --}}
        @foreach ($categories as $catName => $catModules)
            @php $slug = $catSlugs[$catName]; @endphp
            <div class="perm-tab-panel {{ $slug !== $firstSlug ? 'd-none' : '' }}" data-panel="{{ $slug }}">

                {{-- Toolbar --}}
                <div class="perm-toolbar">
                    <div class="perm-toolbar-left">
                        <span class="perm-cat-label">{{ $catName }}</span>
                        <span style="color:#cbd5e1;">|</span>
                        <span style="font-size:.75rem;color:#94a3b8;">
                            {{ count($catModules) }} module{{ count($catModules) !== 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div class="perm-toolbar-right">
                        <div class="perm-search-wrap">
                            <i class="fas fa-search perm-search-icon"></i>
                            <input type="text"
                                   class="perm-search-box"
                                   data-panel="{{ $slug }}"
                                   placeholder="Search modules…"
                                   autocomplete="off">
                        </div>
                        <button type="button" class="btn-revoke-all" data-panel="{{ $slug }}">
                            <i class="fas fa-times-circle"></i> Revoke All
                        </button>
                        <button type="button" class="btn-grant-all" data-panel="{{ $slug }}">
                            <i class="fas fa-check-circle"></i> Grant All
                        </button>
                    </div>
                </div>

                {{-- Module cards --}}
                <div class="perm-modules-grid">
                    @foreach ($catModules as $mod)
                        @php
                            $section = $mod['section'];
                            $granted = $countGranted($mod['items']);
                            $total   = $countTotal($mod['items']);
                            $isFull  = ($total > 0 && $granted === $total);

                            // Pre-collect radio groups keyed by radio name attribute
                            $radioGroups = [];
                            foreach ($mod['items'] as $item) {
                                if ($item['t'] === 'rd') {
                                    $radioGroups[$item['rn']][] = $item;
                                }
                            }
                        @endphp

                        <div class="perm-card {{ $isFull ? 'is-granted' : '' }}"
                             data-section="{{ $section }}"
                             data-module-name="{{ strtolower($mod['group']) }}">

                            {{-- Card header --}}
                            <div class="perm-card-head" onclick="permToggleCard(this)">
                                <div class="perm-card-head-left">
                                    <span class="perm-card-icon {{ $mod['ic'] }}">
                                        <i class="fas {{ $mod['icon'] }}"></i>
                                    </span>
                                    <span class="perm-card-title">{{ $mod['group'] }}</span>
                                </div>
                                <div class="perm-card-head-right">
                                    <span class="perm-card-cnt" id="count-{{ $section }}">
                                        {{ $granted }}/{{ $total }}
                                    </span>
                                    <label class="perm-sel-all-lbl" onclick="event.stopPropagation()">
                                        <input type="checkbox"
                                               class="perm-select-all"
                                               data-section="{{ $section }}"
                                               {{ $isFull ? 'checked' : '' }}>
                                        All
                                    </label>
                                    <i class="fas fa-chevron-up perm-chevron"></i>
                                </div>
                            </div>

                            {{-- Card body — items rendered in original catalogue order --}}
                            <div class="perm-card-body">
                                @php
                                    $cbBuffer       = [];
                                    $radioGroupDone = [];
                                @endphp

                                @foreach ($mod['items'] as $item)

                                    @if ($item['t'] === 'hd')
                                        {{-- Flush pending checkboxes before sub-heading --}}
                                        @if (count($cbBuffer))
                                            <div class="perm-cb-group">
                                                @foreach ($cbBuffer as $ci)
                                                    @php $p3 = explode('.', $ci['p']); @endphp
                                                    <label class="perm-cb-item">
                                                        <input type="checkbox"
                                                               class="perm-input"
                                                               name="permissions[{{ $p3[0] }}][{{ $p3[1] }}][{{ $p3[2] }}]"
                                                               value="1"
                                                               data-section="{{ $section }}"
                                                               {{ $permChecked($ci['p']) ? 'checked' : '' }}>
                                                        <span>{{ $ci['l'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @php $cbBuffer = []; @endphp
                                        @endif
                                        <div class="perm-sub-head">{{ $item['l'] }}</div>

                                    @elseif ($item['t'] === 'sp')
                                        {{-- Flush pending checkboxes before separator --}}
                                        @if (count($cbBuffer))
                                            <div class="perm-cb-group">
                                                @foreach ($cbBuffer as $ci)
                                                    @php $p3 = explode('.', $ci['p']); @endphp
                                                    <label class="perm-cb-item">
                                                        <input type="checkbox"
                                                               class="perm-input"
                                                               name="permissions[{{ $p3[0] }}][{{ $p3[1] }}][{{ $p3[2] }}]"
                                                               value="1"
                                                               data-section="{{ $section }}"
                                                               {{ $permChecked($ci['p']) ? 'checked' : '' }}>
                                                        <span>{{ $ci['l'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @php $cbBuffer = []; @endphp
                                        @endif
                                        <hr class="perm-hr">

                                    @elseif ($item['t'] === 'rd')
                                        {{-- Render each radio group exactly once on first encounter --}}
                                        @if (!isset($radioGroupDone[$item['rn']]))
                                            @php $radioGroupDone[$item['rn']] = true; @endphp
                                            <div class="perm-radio-group">
                                                @foreach ($radioGroups[$item['rn']] as $ri)
                                                    <label class="perm-radio-item">
                                                        <input type="radio"
                                                               class="perm-input"
                                                               name="{{ $ri['rn'] }}"
                                                               value="{{ $ri['p'] }}"
                                                               data-section="{{ $section }}"
                                                               {{ $permChecked($ri['p']) ? 'checked' : '' }}>
                                                        <span>{{ $ri['l'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif

                                    @elseif ($item['t'] === 'cb')
                                        @php $cbBuffer[] = $item; @endphp

                                    @endif
                                @endforeach

                                {{-- Flush remaining checkboxes at end of card --}}
                                @if (count($cbBuffer))
                                    <div class="perm-cb-group">
                                        @foreach ($cbBuffer as $ci)
                                            @php $p3 = explode('.', $ci['p']); @endphp
                                            <label class="perm-cb-item">
                                                <input type="checkbox"
                                                       class="perm-input"
                                                       name="permissions[{{ $p3[0] }}][{{ $p3[1] }}][{{ $p3[2] }}]"
                                                       value="1"
                                                       data-section="{{ $section }}"
                                                       {{ $permChecked($ci['p']) ? 'checked' : '' }}>
                                                <span>{{ $ci['l'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif

                            </div>{{-- /.perm-card-body --}}
                        </div>{{-- /.perm-card --}}
                    @endforeach
                </div>{{-- /.perm-modules-grid --}}

                {{-- Sticky action bar (per panel so it sticks within the active tab) --}}
                <div class="perm-action-bar">
                    <a href="{{ route('roles.index') }}" class="btn btn-light border px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Save Permissions
                    </button>
                </div>

            </div>{{-- /.perm-tab-panel --}}
        @endforeach

    </form>

</div>{{-- /.perm-page --}}
</div>{{-- /.page-wrapper --}}
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── Tab switching ──────────────────────────────────────────── */
    document.querySelectorAll('.perm-cat-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = this.dataset.tab;
            document.querySelectorAll('.perm-cat-tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.perm-tab-panel').forEach(function (p) { p.classList.add('d-none'); });
            this.classList.add('active');
            document.querySelector('[data-panel="' + target + '"]').classList.remove('d-none');
        });
    });

    /* ── Collapse / expand card ─────────────────────────────────── */
    window.permToggleCard = function (head) {
        head.closest('.perm-card').classList.toggle('collapsed');
    };

    /* ── Count helpers ──────────────────────────────────────────── */
    function countSection(section) {
        var card = document.querySelector('.perm-card[data-section="' + section + '"]');
        if (!card) return { granted: 0, total: 0 };
        var inputs  = card.querySelectorAll('.perm-input');
        var granted = 0, total = inputs.length, radioSeen = {};
        inputs.forEach(function (inp) {
            if (inp.type === 'checkbox' && inp.checked) granted++;
            if (inp.type === 'radio'    && inp.checked && !radioSeen[inp.name]) {
                radioSeen[inp.name] = true; granted++;
            }
        });
        return { granted: granted, total: total };
    }

    function refreshSectionBadge(section) {
        var card = document.querySelector('.perm-card[data-section="' + section + '"]');
        if (!card) return;
        var c = countSection(section);
        var badge = card.querySelector('.perm-card-cnt');
        if (badge) badge.textContent = c.granted + '/' + c.total;
        var full = (c.total > 0 && c.granted === c.total);
        card.classList.toggle('is-granted', full);
        var selAll = card.querySelector('.perm-select-all');
        if (selAll) selAll.checked = full;
    }

    function refreshGrantedBadge() {
        var n = 0, radioSeen = {};
        document.querySelectorAll('#permForm .perm-input').forEach(function (inp) {
            if (inp.type === 'checkbox' && inp.checked) n++;
            if (inp.type === 'radio'    && inp.checked && !radioSeen[inp.name]) {
                radioSeen[inp.name] = true; n++;
            }
        });
        var el = document.getElementById('grantedCount');
        if (el) el.textContent = n;
    }

    /* ── Select-all toggle per module card ──────────────────────── */
    document.querySelectorAll('.perm-select-all').forEach(function (chk) {
        chk.addEventListener('change', function () {
            var section = this.dataset.section;
            var card    = document.querySelector('.perm-card[data-section="' + section + '"]');
            if (!card) return;
            var on = this.checked;

            // Last radio in each group = most-permissive option to select when granting all
            var lastRadio = {};
            card.querySelectorAll('.perm-input[type=radio]').forEach(function (inp) {
                lastRadio[inp.name] = inp;
            });

            card.querySelectorAll('.perm-input').forEach(function (inp) {
                if (inp.type === 'checkbox') {
                    inp.checked = on;
                } else if (inp.type === 'radio') {
                    inp.checked = on && (lastRadio[inp.name] === inp);
                }
            });

            refreshSectionBadge(section);
            refreshGrantedBadge();
        });
    });

    /* ── Live update on individual input change ─────────────────── */
    document.querySelectorAll('#permForm .perm-input').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var section = this.dataset.section;
            if (section) refreshSectionBadge(section);
            refreshGrantedBadge();
        });
    });

    /* ── Grant All / Revoke All per tab panel ───────────────────── */
    document.querySelectorAll('.btn-grant-all').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var container = document.querySelector('[data-panel="' + this.dataset.panel + '"]');
            if (!container) return;
            var lastRadio = {};
            container.querySelectorAll('.perm-input[type=radio]').forEach(function (inp) {
                lastRadio[inp.name] = inp;
            });
            container.querySelectorAll('.perm-input').forEach(function (inp) {
                if (inp.type === 'checkbox') inp.checked = true;
                else if (inp.type === 'radio') inp.checked = (lastRadio[inp.name] === inp);
            });
            container.querySelectorAll('.perm-card').forEach(function (c) {
                refreshSectionBadge(c.dataset.section);
            });
            refreshGrantedBadge();
        });
    });

    document.querySelectorAll('.btn-revoke-all').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var container = document.querySelector('[data-panel="' + this.dataset.panel + '"]');
            if (!container) return;
            container.querySelectorAll('.perm-input').forEach(function (inp) { inp.checked = false; });
            container.querySelectorAll('.perm-card').forEach(function (c) {
                refreshSectionBadge(c.dataset.section);
            });
            refreshGrantedBadge();
        });
    });

    /* ── Module search ──────────────────────────────────────────── */
    document.querySelectorAll('.perm-search-box').forEach(function (box) {
        box.addEventListener('input', function () {
            var q         = this.value.toLowerCase().trim();
            var container = document.querySelector('[data-panel="' + this.dataset.panel + '"]');
            if (!container) return;
            container.querySelectorAll('.perm-card').forEach(function (card) {
                var name = (card.dataset.moduleName || '').toLowerCase();
                card.classList.toggle('search-hidden', q !== '' && !name.includes(q));
            });
        });
    });

    /* ── Initial sync on page load ──────────────────────────────── */
    document.querySelectorAll('.perm-card').forEach(function (card) {
        refreshSectionBadge(card.dataset.section);
    });
    refreshGrantedBadge();

}());
</script>
@endpush
