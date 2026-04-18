<?php $page = 'permission'; ?>
@extends('layout.mainlayout')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════

@push('styles')
<style>
.permission-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(37,99,235,0.04);
    margin-bottom: 24px;
    padding: 0;
    transition: box-shadow .15s;
}
.permission-section:hover {
    box-shadow: 0 4px 16px rgba(37,99,235,0.08);
}
.permission-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px 10px 24px;
    border-bottom: 1px solid #f1f4f9;
    background: #fafbfc;
}
.permission-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}
.permission-select-all {
    font-size: 0.98rem;
    font-weight: 500;
    color: #2563eb;
}
.permission-options {
    padding: 18px 24px 10px 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 24px 40px;
}
.permission-group {
    min-width: 220px;
    margin-bottom: 12px;
}
.permission-radio-group label,
.permission-checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    margin-bottom: 8px;
    cursor: pointer;
}
.permission-radio-group input[type=radio],
.permission-checkbox-group input[type=checkbox] {
    accent-color: #2563eb;
    width: 18px;
    height: 18px;
}
@media (max-width: 768px) {
    .permission-options { flex-direction: column; gap: 0; }
    .permission-group { min-width: 100%; }
}
</style>
@endpush
@section('content')
<div class="container-fluid py-3" style="background:#f1f4f9;min-height:100vh;">
    <form action="{{ route('roles.permissions.update') }}" method="POST" id="permForm">
        @csrf
        <input type="hidden" name="role_id" value="{{ $role->id }}">

        @foreach($modules as $mod)
        @php
            $section = $mod['section'];
            $isAllChecked = true;
            foreach ($mod['items'] as $item) {
                if (isset($item['p']) && !in_array($item['p'], $assigned ?? [], true)) $isAllChecked = false;
            }
        @endphp
        <div class="permission-section" data-section="{{ $section }}">
            <div class="permission-section-header">
                <span class="permission-section-title">
                    <i class="fas {{ $mod['icon'] }} me-2" style="color:#2563eb;"></i>
                    {{ $mod['group'] }}
                </span>
                <label class="permission-select-all">
                    <input type="checkbox" class="select-all" data-section="{{ $section }}" {{ $isAllChecked ? 'checked' : '' }}>
                    Select All
                </label>
            </div>
            <div class="permission-options">
                @php
                    // Group items by type for layout
                    $radios = [];
                    $checkboxes = [];
                    $radioGroups = [];
                    foreach ($mod['items'] as $item) {
                        if ($item['t'] === 'rd') {
                            $radioGroups[$item['rn']][] = $item;
                        } elseif ($item['t'] === 'cb') {
                            $checkboxes[] = $item;
                        }
                    }
                @endphp

                {{-- Render radio groups --}}
                @foreach($radioGroups as $groupName => $groupItems)
                <div class="permission-group permission-radio-group">
                    @foreach($groupItems as $item)
                        <label>
                            <input type="radio"
                                name="{{ $item['rn'] }}"
                                value="{{ $item['p'] }}"
                                class="perm-input"
                                data-section="{{ $section }}"
                                {{ in_array($item['p'], $assigned ?? [], true) ? 'checked' : '' }}>
                            {{ $item['l'] }}
                        </label>
                    @endforeach
                </div>
                @endforeach

                {{-- Render checkboxes --}}
                @if(count($checkboxes))
                <div class="permission-group permission-checkbox-group">
                    @foreach($checkboxes as $item)
                        <label>
                            <input type="checkbox"
                                name="permissions[{{ implode('][', explode('.', $item['p'])) }}]"
                                value="1"
                                class="perm-input"
                                data-section="{{ $section }}"
                                {{ in_array($item['p'], $assigned ?? [], true) ? 'checked' : '' }}>
                            {{ $item['l'] }}
                        </label>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-end gap-3 mt-4">
            <a href="{{ route('roles.index') }}" class="btn btn-light border">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fa fa-save me-1"></i> Save Permissions
            </button>
        </div>
    </form>
</div>
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
                                    <label class="perm-select-all-wrap" onclick="event.stopPropagation()" style="margin-top:2px;">
                                        <input type="checkbox"
                                               class="perm-select-all"
                                               data-section="{{ $mod['section'] }}"
                                               style="accent-color:#2563eb;border-radius:4px;width:16px;height:16px;box-shadow:0 1px 2px #2563eb22;"
                                               {{ $isFull ? 'checked' : '' }}>
                                        <span style="font-weight:600;color:#2563eb;">Select All</span>
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
            <a href="{{ route('roles.index') }}" class="btn-perm-cancel" style="font-weight:700;font-size:13px;border-radius:6px;padding:9px 24px;">Cancel</a>
            <button type="submit" class="btn-perm-save" style="background:#2563eb;color:#fff;font-weight:700;font-size:13px;border-radius:6px;padding:9px 32px;box-shadow:0 2px 8px rgba(37,99,235,.10);">
                <i class="fa fa-save"></i> Save Permissions
            </button>
        </div>

    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.select-all').forEach(function(selectAll) {
    selectAll.addEventListener('change', function() {
        var section = this.dataset.section;
        var checked = this.checked;
        document.querySelectorAll('.permission-section[data-section="' + section + '"] .perm-input').forEach(function(input) {
            if (input.type === 'checkbox') {
                input.checked = checked;
            } else if (input.type === 'radio' && checked) {
                // Only check the first radio in the group if selecting all
                var group = input.name;
                if (!document.querySelector('.permission-section[data-section="' + section + '"] .perm-input[name="' + group + '"]:checked')) {
                    input.checked = true;
                }
            } else if (input.type === 'radio' && !checked) {
                input.checked = false;
            }
        });
    });
});
</script>
@endpush
