<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * PostResetSeeder — runs after a full database reset.
 * Seeds: permission catalog, default roles, and role-permission mappings.
 */
class PostResetSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  ▸ Seeding permission catalog…');
        $this->seedPermissions();

        $this->command->info('  ▸ Seeding default roles…');
        $this->seedDefaultRoles();

        $this->command->info('  ▸ Seeding plans…');
        $this->call(PlanSeeder::class);

        $this->command->info('  ▸ Post-reset seed complete.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Permission catalog — mirrors RoleController::permissionMatrix()
    // ──────────────────────────────────────────────────────────────────────────

    private function permissionMatrix(): array
    {
        return [
            'dashboard'      => ['overview'  => ['view']],
            'user_management'=> ['users'     => ['view', 'create', 'edit', 'delete']],
            'roles'          => ['roles'     => ['view', 'create', 'edit', 'delete']],
            'customers' => [
                'customers' => [
                    'view', 'view_all', 'view_own',
                    'view_no_sell_1month', 'view_no_sell_3months',
                    'view_no_sell_6months', 'view_no_sell_1year',
                    'view_irrespective',
                    'create', 'edit', 'delete',
                ],
            ],
            'vendors'        => ['vendors'   => ['view', 'view_all', 'view_own', 'create', 'edit', 'delete']],
            'inventory'      => [
                'products'   => ['view', 'create', 'edit', 'delete', 'add_opening_stock', 'view_purchase_price'],
                'categories' => ['view', 'create', 'edit', 'delete'],
                'stock'      => ['view', 'edit'],
            ],
            'sales'          => [
                'invoices'   => ['view', 'view_all', 'view_own', 'create', 'edit', 'delete'],
                'pos'        => ['view', 'create'],
                'quotations' => ['view', 'view_all', 'view_own', 'create', 'edit', 'delete'],
            ],
            'purchases'      => [
                'purchases'  => [
                    'view', 'view_all', 'view_own',
                    'create', 'edit', 'delete',
                    'add_payment', 'edit_payment', 'delete_payment',
                ],
            ],
            'finance'        => [
                'expenses'   => ['view', 'create', 'edit', 'delete'],
                'payments'   => ['view', 'create', 'edit'],
                'accounts'   => ['view', 'create', 'edit'],
            ],
            'reports'        => ['reports'   => ['view']],
            'payroll'        => ['payroll'   => ['view', 'create', 'edit']],
            'tax'            => ['filings'   => ['view', 'create', 'edit']],
            'settings'       => ['settings'  => ['view', 'edit']],
            'projects'       => ['projects'  => ['view', 'create', 'edit', 'delete']],
            'deployment'     => ['managers'  => ['view', 'create', 'edit']],
            'recurring_invoices'   => ['recurring_invoices'   => ['view', 'create', 'edit', 'delete']],
            'estimates'            => ['estimates'            => ['view', 'create', 'edit', 'delete']],
            'purchase_orders'      => ['purchase_orders'      => ['view', 'create', 'edit', 'delete']],
            'applications'         => [
                'chat'     => ['view'],
                'calendar' => ['view'],
                'messages' => ['view'],
            ],
            'recurring_transactions' => ['recurring_transactions' => ['view', 'create', 'edit', 'delete']],
            'approval_queue'       => ['approval_queue'       => ['view', 'edit']],
            'expense_claims'       => ['expense_claims'       => ['view', 'create', 'edit', 'delete']],
            'collections_hub'      => ['collections_hub'      => ['view', 'create', 'edit']],
            'follow_ups'           => ['follow_ups'           => ['view', 'create', 'edit', 'delete']],
            'fixed_assets'         => ['fixed_assets'         => ['view', 'create', 'edit', 'delete']],
            'budgets'              => ['budgets'              => ['view', 'create', 'edit', 'delete']],
            'branches'             => ['branches'             => ['view', 'create', 'edit', 'delete']],
            'accounting'           => [
                'chart_of_accounts'   => ['view', 'create', 'edit', 'delete'],
                'bank_reconciliation' => ['view', 'edit'],
                'manual_journal'      => ['view', 'create', 'edit', 'delete'],
            ],
            'activity_log'   => ['activity_log'  => ['view']],
            'period_close'   => ['period_close'  => ['view', 'execute']],
            'payment_summary'=> ['payment_summary'=> ['view']],
        ];
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ($this->permissionMatrix() as $module => $subModules) {
            foreach ($subModules as $subModule => $actions) {
                foreach ($actions as $action) {
                    $name = Str::snake($module) . '.' . Str::snake($subModule) . '.' . Str::snake($action);
                    Permission::firstOrCreate(['name' => $name]);
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Default roles with permission mappings
    // ──────────────────────────────────────────────────────────────────────────

    private function defaultRoles(): array
    {
        return [
            ['name' => 'Administrator',       'desc' => 'Full system access (Client Owner)', 'group' => 'Executive'],
            ['name' => 'Deployment Manager',  'desc' => 'Can deploy plans and monitor clients', 'group' => 'Partnership'],
            ['name' => 'Finance Manager',     'desc' => 'Manages accounts, taxes, and reports', 'group' => 'Finance'],
            ['name' => 'Store Manager',       'desc' => 'Manages inventory and stock levels', 'group' => 'Operations'],
            ['name' => 'Sales Manager',       'desc' => 'Manages sales teams and targets', 'group' => 'Sales'],
            ['name' => 'Account Officer',     'desc' => 'Handles daily bookkeeping', 'group' => 'Finance'],
            ['name' => 'Cashier',             'desc' => 'Point of Sale operations only', 'group' => 'Sales'],
        ];
    }

    private function defaultPermissionsForRole(string $roleName): array
    {
        $allPerms = Permission::query()->pluck('name')->all();

        return match ($roleName) {
            'Administrator' => $allPerms,

            'Finance Manager' => array_filter($allPerms, fn ($p) => str_starts_with($p, 'finance.')
                || str_starts_with($p, 'reports.')
                || str_starts_with($p, 'payroll.')
                || str_starts_with($p, 'tax.')
                || str_starts_with($p, 'accounting.')
                || str_starts_with($p, 'budgets.')
                || str_starts_with($p, 'period_close.')
                || str_starts_with($p, 'fixed_assets.')
                || str_starts_with($p, 'expense_claims.')
                || str_starts_with($p, 'approval_queue.')
                || str_starts_with($p, 'payment_summary.')
                || str_starts_with($p, 'dashboard.')
                || str_starts_with($p, 'activity_log.')
            ),

            'Store Manager' => array_filter($allPerms, fn ($p) => str_starts_with($p, 'inventory.')
                || str_starts_with($p, 'dashboard.')
                || str_starts_with($p, 'reports.')
                || str_starts_with($p, 'purchase_orders.')
                || str_starts_with($p, 'purchases.')
            ),

            'Sales Manager' => array_filter($allPerms, fn ($p) => str_starts_with($p, 'sales.')
                || str_starts_with($p, 'customers.')
                || str_starts_with($p, 'estimates.')
                || str_starts_with($p, 'quotations.')
                || str_starts_with($p, 'collections_hub.')
                || str_starts_with($p, 'follow_ups.')
                || str_starts_with($p, 'dashboard.')
                || str_starts_with($p, 'reports.')
                || str_starts_with($p, 'recurring_invoices.')
            ),

            'Account Officer' => array_filter($allPerms, fn ($p) => in_array($p, [
                'dashboard.overview.view',
                'finance.expenses.view', 'finance.expenses.create', 'finance.expenses.edit',
                'finance.payments.view', 'finance.payments.create', 'finance.payments.edit',
                'finance.accounts.view',
                'accounting.manual_journal.view', 'accounting.manual_journal.create',
                'customers.customers.view', 'customers.customers.view_all',
                'vendors.vendors.view',
                'reports.reports.view',
                'activity_log.activity_log.view',
            ])),

            'Cashier' => array_filter($allPerms, fn ($p) => in_array($p, [
                'dashboard.overview.view',
                'sales.pos.view', 'sales.pos.create',
                'sales.invoices.view', 'sales.invoices.view_own', 'sales.invoices.create',
                'customers.customers.view', 'customers.customers.create',
                'finance.payments.view', 'finance.payments.create',
                'inventory.products.view',
            ])),

            'Deployment Manager' => array_filter($allPerms, fn ($p) => str_starts_with($p, 'deployment.')
                || str_starts_with($p, 'dashboard.')
            ),

            default => [],
        };
    }

    private function seedDefaultRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        foreach ($this->defaultRoles() as $role) {
            $roleModel = Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['desc'], 'role_group' => $role['group'], 'is_system_role' => true]
            );

            $permissionNames = $this->defaultPermissionsForRole($role['name']);
            if (! empty($permissionNames)) {
                $permissionIds = Permission::query()
                    ->whereIn('name', array_values($permissionNames))
                    ->pluck('id')
                    ->all();
                $roleModel->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }
}
