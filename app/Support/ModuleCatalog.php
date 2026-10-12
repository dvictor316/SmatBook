<?php

namespace App\Support;

class ModuleCatalog
{
    public static function registrationOperations(): array
    {
        return [
            'general' => ['label' => 'General Business', 'plan' => null],
            'retail' => ['label' => 'Retail / POS', 'plan' => null],
            'services' => ['label' => 'Professional Services', 'plan' => null],
            'manufacturing' => ['label' => 'Manufacturing', 'plan' => null],
            'hotel' => ['label' => 'Hotel / Hospitality', 'plan' => 'hotel'],
            'livestock' => ['label' => 'Livestock / Layer Farm', 'plan' => 'livestock'],
        ];
    }

    public static function operationKeys(): array
    {
        return array_keys(self::registrationOperations());
    }

    public static function dedicatedPlanForOperation(?string $operation): ?string
    {
        $key = strtolower(trim((string) $operation));

        return self::registrationOperations()[$key]['plan'] ?? null;
    }

    public static function modules(): array
    {
        return [
            ['key' => 'pos-sales', 'title' => 'POS & Sales', 'description' => 'Checkout, invoices, receipts, customers, and sales reporting.', 'tier' => 'Starter', 'scope' => 'Licensed accounting tenants', 'registration' => 'General Business or Retail / POS', 'icon' => 'fas fa-cash-register', 'route' => 'pos.sales'],
            ['key' => 'accounting-inventory', 'title' => 'Accounting & Inventory', 'description' => 'Purchases, expenses, banking, stock controls, and core reports.', 'tier' => 'Basic', 'scope' => 'Licensed accounting tenants', 'registration' => 'General, Retail, Services, or Manufacturing', 'icon' => 'fas fa-boxes-stacked', 'route' => 'inventory.Products'],
            ['key' => 'projects', 'title' => 'Projects & Profitability', 'description' => 'Projects, tasks, costs, time, claims, and profitability controls.', 'tier' => 'Professional', 'scope' => 'Tenant and branch scoped', 'registration' => 'Included from Professional tier', 'icon' => 'fas fa-diagram-project', 'route' => 'projects.index'],
            ['key' => 'tax-compliance', 'title' => 'Tax & Compliance', 'description' => 'Jurisdictions, tax codes, workpapers, filings, approvals, and exports.', 'tier' => 'Enterprise', 'scope' => 'Tenant and branch scoped', 'registration' => 'Included with Enterprise licence', 'icon' => 'fas fa-file-shield', 'route' => 'compliance.tax-center.index'],
            ['key' => 'payroll', 'title' => 'Payroll & HR', 'description' => 'Employees, payroll runs, payslips, attendance, and leave workflows.', 'tier' => 'Enterprise', 'scope' => 'Tenant scoped', 'registration' => 'Included with Enterprise licence', 'icon' => 'fas fa-people-group', 'route' => 'payroll.index'],
            ['key' => 'manufacturing', 'title' => 'Production & BOM', 'description' => 'Bills of materials, component costing, production orders, and output control.', 'tier' => 'Enterprise', 'scope' => 'Tenant and branch scoped', 'registration' => 'Select Manufacturing and use Enterprise', 'icon' => 'fas fa-industry', 'route' => 'manufacturing.index'],
            ['key' => 'returnable-assets', 'title' => 'Returnable Asset Custody', 'description' => 'Issues, returns, due dates, deposits, losses, damage, and overdue custody exposure.', 'tier' => 'Enterprise', 'scope' => 'Tenant and branch scoped', 'registration' => 'Included with Enterprise licence', 'icon' => 'fas fa-rotate', 'route' => 'returnable-assets.index'],
            ['key' => 'online-store', 'title' => 'PWA Online Store', 'description' => 'Public product catalogue, custom-domain routing, stock reservation, and online order capture.', 'tier' => 'Professional', 'scope' => 'Tenant scoped public storefront', 'registration' => 'Included from Professional tier', 'icon' => 'fas fa-store', 'route' => 'online-store.settings'],
            ['key' => 'developer-api', 'title' => 'Developer API', 'description' => 'Versioned business-bound API tokens with expiry, revocation, and granular read permissions.', 'tier' => 'Enterprise', 'scope' => 'Tenant scoped', 'registration' => 'Included with Enterprise licence', 'icon' => 'fas fa-code', 'route' => 'developer-api.index'],
            ['key' => 'bill-payments', 'title' => 'Bill Payments & Commissions', 'description' => 'Provider-backed bill validation, payment status, fees, tokens, and earned commission reconciliation.', 'tier' => 'Enterprise', 'scope' => 'Tenant scoped; provider KYC required', 'registration' => 'Included with Enterprise licence', 'icon' => 'fas fa-file-invoice-dollar', 'route' => 'bill-payments.index'],
            ['key' => 'hotel', 'title' => 'Hotel Management', 'description' => 'Reservations, front desk, folios, housekeeping, maintenance, and night audit.', 'tier' => 'Hotel', 'scope' => 'Hotel tenants only', 'registration' => 'Select Hotel / Hospitality', 'icon' => 'fas fa-hotel', 'route' => 'super_admin.hotels.index'],
            ['key' => 'livestock', 'title' => 'Livestock & Layer Farm', 'description' => 'Flocks, production, feed, farm costs, inventory, revenue, and returns.', 'tier' => 'Livestock', 'scope' => 'Livestock tenants only', 'registration' => 'Select Livestock / Layer Farm', 'icon' => 'fas fa-cow', 'route' => 'super_admin.livestock.index'],
        ];
    }
}
