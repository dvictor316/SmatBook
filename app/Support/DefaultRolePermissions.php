<?php

namespace App\Support;

class DefaultRolePermissions
{
    public static function forRole(string $roleName, array $availablePermissions): array
    {
        $roleName = strtolower(trim($roleName));
        if ($roleName === 'administrator') {
            return array_values($availablePermissions);
        }

        $permissions = match ($roleName) {
            'state manager', 'deployment manager' => [
                'dashboard.overview.view',
                'deployment.managers.view',
            ],
            'agent' => [
                'dashboard.overview.view',
                'customers.customers.view',
                'customers.customers.create',
                'sales.quotations.view',
                'sales.quotations.create',
                'follow_ups.follow_ups.view',
                'follow_ups.follow_ups.create',
            ],
            'finance manager' => [
                'dashboard.overview.view',
                'finance.expenses.view',
                'finance.expenses.create',
                'finance.expenses.edit',
                'finance.payments.view',
                'finance.payments.create',
                'reports.reports.view',
                'tax.filings.view',
                'tax.filings.create',
                'payroll.payroll.view',
            ],
            'store manager' => [
                'dashboard.overview.view',
                'inventory.products.view',
                'inventory.products.create',
                'inventory.products.edit',
                'inventory.categories.view',
                'inventory.stock.view',
                'inventory.stock.edit',
                'purchases.purchases.view',
                'purchases.purchases.create',
                'purchases.purchases.edit',
                'reports.reports.view',
            ],
            'sales manager' => [
                'dashboard.overview.view',
                'sales.invoices.view',
                'sales.invoices.create',
                'sales.invoices.edit',
                'sales.pos.view',
                'sales.pos.create',
                'sales.quotations.view',
                'sales.quotations.create',
                'sales.quotations.edit',
                'customers.customers.view',
                'reports.reports.view',
            ],
            'account officer' => [
                'dashboard.overview.view',
                'finance.expenses.view',
                'finance.expenses.create',
                'finance.payments.view',
                'finance.payments.create',
                'sales.invoices.view',
                'reports.reports.view',
            ],
            'cashier' => [
                'dashboard.overview.view',
                'sales.pos.view',
                'sales.pos.create',
                'sales.invoices.view',
                'customers.customers.view',
            ],
            default => [],
        };

        return array_values(array_intersect($availablePermissions, $permissions));
    }
}
