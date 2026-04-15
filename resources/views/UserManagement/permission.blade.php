<?php $page = 'permission'; ?>
@extends('layout.mainlayout')

@push('styles')
<style>
/* ─── page header ─────────────────────────────────────────────── */
.perm-page-header {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 16px;
    margin-bottom: 0;
}

/* ─── section rows ────────────────────────────────────────────── */
.perm-section {
    display: flex;
    align-items: flex-start;
    padding: 22px 24px;
    border-bottom: 1px solid #e2e8f0;
    gap: 20px;
}
.perm-section:last-child { border-bottom: none; }

/* ─── columns ─────────────────────────────────────────────────── */
.perm-group-col {
    width: 170px;
    min-width: 170px;
    font-weight: 700;
    font-size: 0.9rem;
    color: #1e293b;
    padding-top: 2px;
}
.perm-selectall-col {
    width: 110px;
    min-width: 110px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding-top: 2px;
}
.perm-selectall-col span {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 500;
}
.perm-selectall-col input[type=checkbox] {
    width: 17px;
    height: 17px;
    accent-color: #2d2a6e;
    cursor: pointer;
}
.perm-items-col { flex: 1; }

/* ─── permission items ────────────────────────────────────────── */
.perm-item {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
    padding: 3px 0;
    line-height: 1.3;
}
.perm-item input[type=checkbox],
.perm-item input[type=radio] {
    width: 16px;
    height: 16px;
    accent-color: #2d2a6e;
    flex-shrink: 0;
    cursor: pointer;
    margin: 0;
}
.perm-sep {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 8px 0;
}

/* ─── sticky save bar ─────────────────────────────────────────── */
.perm-action-bar {
    position: sticky;
    bottom: 0;
    background: #fff;
    border-top: 2px solid #e2e8f0;
    padding: 14px 24px;
    display: flex;
    justify-content: center;
    gap: 12px;
    z-index: 20;
    box-shadow: 0 -2px 8px rgba(0,0,0,.05);
}
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="perm-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-0">
            <div>
                <h5 class="mb-1">Permission Settings</h5>
                <p class="text-muted mb-0 small">Configure what the <strong>{{ $role->name }}</strong> role can access.</p>
            </div>
            <div>
                <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back to Roles
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('roles.permissions.update') }}" method="POST" id="permForm">
            @csrf
            <input type="hidden" name="role_id" value="{{ $role->id }}">

            <div class="card mt-3 mb-0" style="border-radius:10px;overflow:hidden;">
                <div class="card-body p-0">

                @php
                    $assigned     = $assignedPermissions ?? [];
                    $permChecked  = fn (string $p) => in_array($p, $assigned, true);

                    $sections = [

                        ['group'=>'Dashboard','section'=>'dashboard','items'=>[
                            ['type'=>'checkbox','perm'=>'dashboard.overview.view','label'=>'View Dashboard'],
                        ]],

                        ['group'=>'User Management','section'=>'user_mgmt','items'=>[
                            ['type'=>'checkbox','perm'=>'user_management.users.view',  'label'=>'View Users'],
                            ['type'=>'checkbox','perm'=>'user_management.users.create','label'=>'Add User'],
                            ['type'=>'checkbox','perm'=>'user_management.users.edit',  'label'=>'Edit User'],
                            ['type'=>'checkbox','perm'=>'user_management.users.delete','label'=>'Delete User'],
                        ]],

                        ['group'=>'Roles','section'=>'roles','items'=>[
                            ['type'=>'checkbox','perm'=>'roles.roles.view',  'label'=>'View Role'],
                            ['type'=>'checkbox','perm'=>'roles.roles.create','label'=>'Add Role'],
                            ['type'=>'checkbox','perm'=>'roles.roles.edit',  'label'=>'Edit Role'],
                            ['type'=>'checkbox','perm'=>'roles.roles.delete','label'=>'Delete Role'],
                        ]],

                        ['group'=>'Supplier','section'=>'supplier','items'=>[
                            ['type'=>'radio','radio_name'=>'perm_radio[vendors_view]','perm'=>'vendors.vendors.view_all','label'=>'View All Supplier'],
                            ['type'=>'radio','radio_name'=>'perm_radio[vendors_view]','perm'=>'vendors.vendors.view_own','label'=>'View Own Supplier'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'vendors.vendors.create','label'=>'Add Supplier'],
                            ['type'=>'checkbox','perm'=>'vendors.vendors.edit',  'label'=>'Edit Supplier'],
                            ['type'=>'checkbox','perm'=>'vendors.vendors.delete','label'=>'Delete Supplier'],
                        ]],

                        ['group'=>'Customer','section'=>'customer','items'=>[
                            ['type'=>'radio','radio_name'=>'perm_radio[customers_view]','perm'=>'customers.customers.view_all','label'=>'View All Customer'],
                            ['type'=>'radio','radio_name'=>'perm_radio[customers_view]','perm'=>'customers.customers.view_own','label'=>'View Own Customer'],
                            ['type'=>'sep'],
                            ['type'=>'radio','radio_name'=>'perm_radio[customers_no_sell]','perm'=>'customers.customers.view_no_sell_1month', 'label'=>'View Customers With No Sell From One Month Only'],
                            ['type'=>'radio','radio_name'=>'perm_radio[customers_no_sell]','perm'=>'customers.customers.view_no_sell_3months','label'=>'View Customers With No Sell From Three Months Only'],
                            ['type'=>'radio','radio_name'=>'perm_radio[customers_no_sell]','perm'=>'customers.customers.view_no_sell_6months','label'=>'View Customers With No Sell From Six Months Only'],
                            ['type'=>'radio','radio_name'=>'perm_radio[customers_no_sell]','perm'=>'customers.customers.view_no_sell_1year',  'label'=>'View Customers With No Sell From One Year Only'],
                            ['type'=>'radio','radio_name'=>'perm_radio[customers_no_sell]','perm'=>'customers.customers.view_irrespective',   'label'=>'View Customers Irrespective Of Their Sell'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'customers.customers.create','label'=>'Add Customer'],
                            ['type'=>'checkbox','perm'=>'customers.customers.edit',  'label'=>'Edit Customer'],
                            ['type'=>'checkbox','perm'=>'customers.customers.delete','label'=>'Delete Customer'],
                        ]],

                        ['group'=>'Product','section'=>'product','items'=>[
                            ['type'=>'checkbox','perm'=>'inventory.products.view',               'label'=>'View Product'],
                            ['type'=>'checkbox','perm'=>'inventory.products.create',             'label'=>'Add Product'],
                            ['type'=>'checkbox','perm'=>'inventory.products.edit',               'label'=>'Edit Product'],
                            ['type'=>'checkbox','perm'=>'inventory.products.delete',             'label'=>'Delete Product'],
                            ['type'=>'checkbox','perm'=>'inventory.products.add_opening_stock',  'label'=>'Add Opening Stock'],
                            ['type'=>'checkbox','perm'=>'inventory.products.view_purchase_price','label'=>'View Purchase Price'],
                        ]],

                        ['group'=>'Stock Manager','section'=>'stock','items'=>[
                            ['type'=>'checkbox','perm'=>'inventory.stock.view','label'=>'View Stock'],
                            ['type'=>'checkbox','perm'=>'inventory.stock.edit','label'=>'Edit Stock'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'inventory.categories.view',  'label'=>'View Categories'],
                            ['type'=>'checkbox','perm'=>'inventory.categories.create','label'=>'Add Category'],
                            ['type'=>'checkbox','perm'=>'inventory.categories.edit',  'label'=>'Edit Category'],
                            ['type'=>'checkbox','perm'=>'inventory.categories.delete','label'=>'Delete Category'],
                        ]],

                        ['group'=>'Purchase','section'=>'purchase','items'=>[
                            ['type'=>'radio','radio_name'=>'perm_radio[purchases_view]','perm'=>'purchases.purchases.view_all','label'=>'View All Purchase'],
                            ['type'=>'radio','radio_name'=>'perm_radio[purchases_view]','perm'=>'purchases.purchases.view_own','label'=>'View Own Purchase'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'purchases.purchases.create',        'label'=>'Add Purchase'],
                            ['type'=>'checkbox','perm'=>'purchases.purchases.edit',          'label'=>'Edit Purchase'],
                            ['type'=>'checkbox','perm'=>'purchases.purchases.delete',        'label'=>'Delete Purchase'],
                            ['type'=>'checkbox','perm'=>'purchases.purchases.add_payment',   'label'=>'Add Purchase Payment'],
                            ['type'=>'checkbox','perm'=>'purchases.purchases.edit_payment',  'label'=>'Edit Purchase Payment'],
                            ['type'=>'checkbox','perm'=>'purchases.purchases.delete_payment','label'=>'Delete Purchase Payment'],
                        ]],

                        ['group'=>'Sales / Invoice','section'=>'invoices','items'=>[
                            ['type'=>'radio','radio_name'=>'perm_radio[invoices_view]','perm'=>'sales.invoices.view_all','label'=>'View All Invoice'],
                            ['type'=>'radio','radio_name'=>'perm_radio[invoices_view]','perm'=>'sales.invoices.view_own','label'=>'View Own Invoice'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'sales.invoices.create','label'=>'Add Invoice'],
                            ['type'=>'checkbox','perm'=>'sales.invoices.edit',  'label'=>'Edit Invoice'],
                            ['type'=>'checkbox','perm'=>'sales.invoices.delete','label'=>'Delete Invoice'],
                        ]],

                        ['group'=>'POS Sales','section'=>'pos','items'=>[
                            ['type'=>'checkbox','perm'=>'sales.pos.view',  'label'=>'View POS Sales'],
                            ['type'=>'checkbox','perm'=>'sales.pos.create','label'=>'Create POS Sale'],
                        ]],

                        ['group'=>'Quotations','section'=>'quotations','items'=>[
                            ['type'=>'radio','radio_name'=>'perm_radio[quotations_view]','perm'=>'sales.quotations.view_all','label'=>'View All Quotation'],
                            ['type'=>'radio','radio_name'=>'perm_radio[quotations_view]','perm'=>'sales.quotations.view_own','label'=>'View Own Quotation'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'sales.quotations.create','label'=>'Add Quotation'],
                            ['type'=>'checkbox','perm'=>'sales.quotations.edit',  'label'=>'Edit Quotation'],
                            ['type'=>'checkbox','perm'=>'sales.quotations.delete','label'=>'Delete Quotation'],
                        ]],

                        ['group'=>'Expenses','section'=>'expenses','items'=>[
                            ['type'=>'checkbox','perm'=>'finance.expenses.view',  'label'=>'View Expenses'],
                            ['type'=>'checkbox','perm'=>'finance.expenses.create','label'=>'Add Expense'],
                            ['type'=>'checkbox','perm'=>'finance.expenses.edit',  'label'=>'Edit Expense'],
                            ['type'=>'checkbox','perm'=>'finance.expenses.delete','label'=>'Delete Expense'],
                        ]],

                        ['group'=>'Payments','section'=>'payments','items'=>[
                            ['type'=>'checkbox','perm'=>'finance.payments.view',  'label'=>'View Payments'],
                            ['type'=>'checkbox','perm'=>'finance.payments.create','label'=>'Add Payment'],
                            ['type'=>'checkbox','perm'=>'finance.payments.edit',  'label'=>'Edit Payment'],
                        ]],

                        ['group'=>'Projects','section'=>'projects','items'=>[
                            ['type'=>'checkbox','perm'=>'projects.projects.view',  'label'=>'View Projects'],
                            ['type'=>'checkbox','perm'=>'projects.projects.create','label'=>'Add Project'],
                            ['type'=>'checkbox','perm'=>'projects.projects.edit',  'label'=>'Edit Project'],
                            ['type'=>'checkbox','perm'=>'projects.projects.delete','label'=>'Delete Project'],
                        ]],

                        ['group'=>'Payroll','section'=>'payroll','items'=>[
                            ['type'=>'checkbox','perm'=>'payroll.payroll.view',  'label'=>'View Payroll'],
                            ['type'=>'checkbox','perm'=>'payroll.payroll.create','label'=>'Create Payroll'],
                            ['type'=>'checkbox','perm'=>'payroll.payroll.edit',  'label'=>'Edit Payroll'],
                        ]],

                        ['group'=>'Reports','section'=>'reports','items'=>[
                            ['type'=>'checkbox','perm'=>'reports.reports.view','label'=>'View Reports'],
                        ]],

                        ['group'=>'Tax','section'=>'tax','items'=>[
                            ['type'=>'checkbox','perm'=>'tax.filings.view',  'label'=>'View Tax Filings'],
                            ['type'=>'checkbox','perm'=>'tax.filings.create','label'=>'Add Tax Filing'],
                            ['type'=>'checkbox','perm'=>'tax.filings.edit',  'label'=>'Edit Tax Filing'],
                        ]],

                        ['group'=>'Settings','section'=>'settings','items'=>[
                            ['type'=>'checkbox','perm'=>'settings.settings.view','label'=>'View Settings'],
                            ['type'=>'checkbox','perm'=>'settings.settings.edit','label'=>'Edit Settings'],
                        ]],

                        ['group'=>'Recurring Invoices','section'=>'recurring_invoices','items'=>[
                            ['type'=>'checkbox','perm'=>'recurring_invoices.recurring_invoices.view',  'label'=>'View Recurring Invoices'],
                            ['type'=>'checkbox','perm'=>'recurring_invoices.recurring_invoices.create','label'=>'Add Recurring Invoice'],
                            ['type'=>'checkbox','perm'=>'recurring_invoices.recurring_invoices.edit',  'label'=>'Edit Recurring Invoice'],
                            ['type'=>'checkbox','perm'=>'recurring_invoices.recurring_invoices.delete','label'=>'Delete Recurring Invoice'],
                        ]],

                        ['group'=>'Estimates','section'=>'estimates','items'=>[
                            ['type'=>'checkbox','perm'=>'estimates.estimates.view',  'label'=>'View Estimates'],
                            ['type'=>'checkbox','perm'=>'estimates.estimates.create','label'=>'Add Estimate'],
                            ['type'=>'checkbox','perm'=>'estimates.estimates.edit',  'label'=>'Edit Estimate'],
                            ['type'=>'checkbox','perm'=>'estimates.estimates.delete','label'=>'Delete Estimate'],
                        ]],

                        ['group'=>'Purchase Orders','section'=>'purchase_orders','items'=>[
                            ['type'=>'checkbox','perm'=>'purchase_orders.purchase_orders.view',  'label'=>'View Purchase Orders'],
                            ['type'=>'checkbox','perm'=>'purchase_orders.purchase_orders.create','label'=>'Add Purchase Order'],
                            ['type'=>'checkbox','perm'=>'purchase_orders.purchase_orders.edit',  'label'=>'Edit Purchase Order'],
                            ['type'=>'checkbox','perm'=>'purchase_orders.purchase_orders.delete','label'=>'Delete Purchase Order'],
                        ]],

                        ['group'=>'Applications','section'=>'applications','items'=>[
                            ['type'=>'checkbox','perm'=>'applications.chat.view',    'label'=>'Access Chat'],
                            ['type'=>'checkbox','perm'=>'applications.calendar.view','label'=>'Access Calendar'],
                            ['type'=>'checkbox','perm'=>'applications.messages.view','label'=>'Access Messages'],
                        ]],

                        ['group'=>'Recurring Transactions','section'=>'recurring_transactions','items'=>[
                            ['type'=>'checkbox','perm'=>'recurring_transactions.recurring_transactions.view',  'label'=>'View Recurring Transactions'],
                            ['type'=>'checkbox','perm'=>'recurring_transactions.recurring_transactions.create','label'=>'Add Recurring Transaction'],
                            ['type'=>'checkbox','perm'=>'recurring_transactions.recurring_transactions.edit',  'label'=>'Edit Recurring Transaction'],
                            ['type'=>'checkbox','perm'=>'recurring_transactions.recurring_transactions.delete','label'=>'Delete Recurring Transaction'],
                        ]],

                        ['group'=>'Approval Queue','section'=>'approval_queue','items'=>[
                            ['type'=>'checkbox','perm'=>'approval_queue.approval_queue.view','label'=>'View Approval Queue'],
                            ['type'=>'checkbox','perm'=>'approval_queue.approval_queue.edit','label'=>'Approve / Reject Requests'],
                        ]],

                        ['group'=>'Expense Claims','section'=>'expense_claims','items'=>[
                            ['type'=>'checkbox','perm'=>'expense_claims.expense_claims.view',  'label'=>'View Expense Claims'],
                            ['type'=>'checkbox','perm'=>'expense_claims.expense_claims.create','label'=>'Submit Expense Claim'],
                            ['type'=>'checkbox','perm'=>'expense_claims.expense_claims.edit',  'label'=>'Edit Expense Claim'],
                            ['type'=>'checkbox','perm'=>'expense_claims.expense_claims.delete','label'=>'Delete Expense Claim'],
                        ]],

                        ['group'=>'Collections Hub','section'=>'collections_hub','items'=>[
                            ['type'=>'checkbox','perm'=>'collections_hub.collections_hub.view',  'label'=>'View Collections Hub'],
                            ['type'=>'checkbox','perm'=>'collections_hub.collections_hub.create','label'=>'Add Collection'],
                            ['type'=>'checkbox','perm'=>'collections_hub.collections_hub.edit',  'label'=>'Edit Collection'],
                        ]],

                        ['group'=>'Follow-Ups','section'=>'follow_ups','items'=>[
                            ['type'=>'checkbox','perm'=>'follow_ups.follow_ups.view',  'label'=>'View Follow-Ups'],
                            ['type'=>'checkbox','perm'=>'follow_ups.follow_ups.create','label'=>'Add Follow-Up'],
                            ['type'=>'checkbox','perm'=>'follow_ups.follow_ups.edit',  'label'=>'Edit Follow-Up'],
                            ['type'=>'checkbox','perm'=>'follow_ups.follow_ups.delete','label'=>'Delete Follow-Up'],
                        ]],

                        ['group'=>'Fixed Assets','section'=>'fixed_assets','items'=>[
                            ['type'=>'checkbox','perm'=>'fixed_assets.fixed_assets.view',  'label'=>'View Fixed Assets'],
                            ['type'=>'checkbox','perm'=>'fixed_assets.fixed_assets.create','label'=>'Add Fixed Asset'],
                            ['type'=>'checkbox','perm'=>'fixed_assets.fixed_assets.edit',  'label'=>'Edit Fixed Asset'],
                            ['type'=>'checkbox','perm'=>'fixed_assets.fixed_assets.delete','label'=>'Delete Fixed Asset'],
                        ]],

                        ['group'=>'Budgets','section'=>'budgets','items'=>[
                            ['type'=>'checkbox','perm'=>'budgets.budgets.view',  'label'=>'View Budgets'],
                            ['type'=>'checkbox','perm'=>'budgets.budgets.create','label'=>'Add Budget'],
                            ['type'=>'checkbox','perm'=>'budgets.budgets.edit',  'label'=>'Edit Budget'],
                            ['type'=>'checkbox','perm'=>'budgets.budgets.delete','label'=>'Delete Budget'],
                        ]],

                        ['group'=>'Branches','section'=>'branches','items'=>[
                            ['type'=>'checkbox','perm'=>'branches.branches.view',  'label'=>'View Branches'],
                            ['type'=>'checkbox','perm'=>'branches.branches.create','label'=>'Add Branch'],
                            ['type'=>'checkbox','perm'=>'branches.branches.edit',  'label'=>'Edit Branch'],
                            ['type'=>'checkbox','perm'=>'branches.branches.delete','label'=>'Delete Branch'],
                        ]],

                        ['group'=>'Accounting','section'=>'accounting','items'=>[
                            ['type'=>'checkbox','perm'=>'accounting.chart_of_accounts.view',  'label'=>'View Chart of Accounts'],
                            ['type'=>'checkbox','perm'=>'accounting.chart_of_accounts.create','label'=>'Add Account'],
                            ['type'=>'checkbox','perm'=>'accounting.chart_of_accounts.edit',  'label'=>'Edit Account'],
                            ['type'=>'checkbox','perm'=>'accounting.chart_of_accounts.delete','label'=>'Delete Account'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'accounting.bank_reconciliation.view','label'=>'View Bank Reconciliation'],
                            ['type'=>'checkbox','perm'=>'accounting.bank_reconciliation.edit','label'=>'Edit Bank Reconciliation'],
                            ['type'=>'sep'],
                            ['type'=>'checkbox','perm'=>'accounting.manual_journal.view',  'label'=>'View Manual Journal'],
                            ['type'=>'checkbox','perm'=>'accounting.manual_journal.create','label'=>'Add Journal Entry'],
                            ['type'=>'checkbox','perm'=>'accounting.manual_journal.edit',  'label'=>'Edit Journal Entry'],
                            ['type'=>'checkbox','perm'=>'accounting.manual_journal.delete','label'=>'Delete Journal Entry'],
                        ]],

                        ['group'=>'Activity Log','section'=>'activity_log','items'=>[
                            ['type'=>'checkbox','perm'=>'activity_log.activity_log.view','label'=>'View Activity Log'],
                        ]],

                        ['group'=>'Period Close','section'=>'period_close','items'=>[
                            ['type'=>'checkbox','perm'=>'period_close.period_close.view',   'label'=>'View Period Close'],
                            ['type'=>'checkbox','perm'=>'period_close.period_close.execute','label'=>'Execute Period Close'],
                        ]],

                        ['group'=>'Payment Summary','section'=>'payment_summary','items'=>[
                            ['type'=>'checkbox','perm'=>'payment_summary.payment_summary.view','label'=>'View Payment Summary'],
                        ]],

                    ]; // end $sections
                @endphp

                @foreach ($sections as $sec)
                <div class="perm-section">

                    {{-- Group label --}}
                    <div class="perm-group-col">{{ $sec['group'] }}</div>

                    {{-- Select All --}}
                    <div class="perm-selectall-col">
                        <span>Select All</span>
                        <input type="checkbox"
                               class="perm-select-all"
                               data-section="{{ $sec['section'] }}"
                               title="Select all permissions in this group">
                    </div>

                    {{-- Permissions --}}
                    <div class="perm-items-col">
                        @foreach ($sec['items'] as $item)

                            @if ($item['type'] === 'sep')
                                <hr class="perm-sep">

                            @elseif ($item['type'] === 'checkbox')
                                @php
                                    $parts     = explode('.', $item['perm']);
                                    $inputName = "permissions[{$parts[0]}][{$parts[1]}][{$parts[2]}]";
                                @endphp
                                <label class="perm-item">
                                    <input type="checkbox"
                                           class="perm-input"
                                           name="{{ $inputName }}"
                                           value="1"
                                           data-section="{{ $sec['section'] }}"
                                           {{ $permChecked($item['perm']) ? 'checked' : '' }}>
                                    {{ $item['label'] }}
                                </label>

                            @elseif ($item['type'] === 'radio')
                                <label class="perm-item">
                                    <input type="radio"
                                           class="perm-input"
                                           name="{{ $item['radio_name'] }}"
                                           value="{{ $item['perm'] }}"
                                           data-section="{{ $sec['section'] }}"
                                           {{ $permChecked($item['perm']) ? 'checked' : '' }}>
                                    {{ $item['label'] }}
                                </label>

                            @endif
                        @endforeach
                    </div>

                </div>
                @endforeach

                </div>{{-- card-body --}}
            </div>{{-- card --}}

            <div class="perm-action-bar">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5">
                    <i class="fa fa-save me-1"></i> Save Permissions
                </button>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Select-All per section
    document.querySelectorAll('.perm-select-all').forEach(function (selectAll) {
        selectAll.addEventListener('change', function () {
            var section = this.dataset.section;
            var checked = this.checked;
            var inputs  = document.querySelectorAll('.perm-input[data-section="' + section + '"]');

            if (checked) {
                inputs.forEach(function (inp) {
                    if (inp.type === 'checkbox') inp.checked = true;
                });
                // Select first radio in each radio group within this section
                var radioGroups = {};
                inputs.forEach(function (inp) {
                    if (inp.type === 'radio' && !radioGroups[inp.name]) {
                        radioGroups[inp.name] = inp;
                    }
                });
                Object.values(radioGroups).forEach(function (r) { r.checked = true; });
            } else {
                inputs.forEach(function (inp) { inp.checked = false; });
            }
        });
    });

    // Sync Select-All state when individual inputs change
    document.querySelectorAll('.perm-input').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var section   = this.dataset.section;
            var allInputs = document.querySelectorAll('.perm-input[data-section="' + section + '"]');
            var selectAll = document.querySelector('.perm-select-all[data-section="' + section + '"]');
            if (!selectAll) return;

            var allChecked    = true;
            var radioGroupOk  = {};

            allInputs.forEach(function (i) {
                if (i.type === 'checkbox' && !i.checked) allChecked = false;
                if (i.type === 'radio') radioGroupOk[i.name] = radioGroupOk[i.name] || i.checked;
            });

            var allRadiosOk = Object.keys(radioGroupOk).length === 0
                || Object.values(radioGroupOk).every(Boolean);

            selectAll.checked = allChecked && allRadiosOk;
        });
    });
}());
</script>
@endpush

