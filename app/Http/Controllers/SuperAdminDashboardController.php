<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Product; 
use App\Models\Sale;
use App\Models\DeploymentManager;
use App\Models\PlatformPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Support\SystemEventMailer;
use App\Support\InventoryQuantity;

class SuperAdminDashboardController extends Controller
{
    private array $paidSaleStatuses = ['paid', 'completed', 'success', 'successful', 'verified'];
    private array $stateManagerRoles = ['state_manager', 'deployment_manager'];
    private array $agentRoles = ['agent', 'sales_agent', 'sales agent'];

    private function resolveDeploymentManager(string|int $id): DeploymentManager
    {
        return DeploymentManager::withoutGlobalScopes()
            ->whereKey($id)
            ->firstOrFail();
    }

    private function applyFinalizedSalesFilter($query, string $table = 'sales')
    {
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }

        $hasPaymentStatus = Schema::hasColumn($table, 'payment_status');
        $hasOrderStatus = Schema::hasColumn($table, 'order_status');
        $hasStatus = Schema::hasColumn($table, 'status');

        if (!$hasPaymentStatus && !$hasOrderStatus && !$hasStatus) {
            return $query;
        }

        return $query->where(function ($statusQuery) use ($table, $hasPaymentStatus, $hasOrderStatus, $hasStatus) {
            $hasAny = false;

            if ($hasPaymentStatus) {
                $statusQuery->whereIn(
                    DB::raw("LOWER(COALESCE({$table}.payment_status, ''))"),
                    $this->paidSaleStatuses
                );
                $hasAny = true;
            }

            if ($hasOrderStatus) {
                $method = $hasAny ? 'orWhereIn' : 'whereIn';
                $statusQuery->{$method}(
                    DB::raw("LOWER(COALESCE({$table}.order_status, ''))"),
                    ['completed', 'delivered', 'fulfilled']
                );
                $hasAny = true;
            }

            if ($hasStatus) {
                $method = $hasAny ? 'orWhereIn' : 'whereIn';
                $statusQuery->{$method}(
                    DB::raw("LOWER(COALESCE({$table}.status, ''))"),
                    $this->paidSaleStatuses
                );
            }
        });
    }

    private function customerUsersQuery()
    {
        $query = User::query();

        if (Schema::hasColumn('users', 'role')) {
            $query->whereNotIn(
                DB::raw("LOWER(COALESCE(role, ''))"),
                ['super_admin', 'superadmin', 'state_manager', 'deployment_manager']
            );
        }

        return $this->applyPlanUserScope($query);
    }

    private function bucketOverrideUserIds(string $bucket): array
    {
        if (!Schema::hasTable('super_admin_user_bucket_overrides')) {
            return [];
        }

        return DB::table('super_admin_user_bucket_overrides')
            ->where('bucket', $bucket)
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function stateManagerUserIds(): array
    {
        $overrideIds = $this->bucketOverrideUserIds('state_manager');
        $configuredIds = $this->namedUserIds([
            'thomas ogbodo',
            'ogbodo thomas',
            'dauda uche',
        ]);

        $ids = collect($overrideIds)->merge($configuredIds)->filter()->map(fn ($id) => (int) $id);

        if (Schema::hasColumn('users', 'role')) {
            $ids = $ids->merge(
                User::query()
                    ->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), $this->stateManagerRoles)
                    ->pluck('id')
            );
        }

        if (!Schema::hasTable('deployment_managers') || !Schema::hasColumn('deployment_managers', 'user_id')) {
            return $ids->unique()->values()->all();
        }

        $deploymentManagerIds = DB::table('deployment_managers')
            ->join('users', 'deployment_managers.user_id', '=', 'users.id')
            ->whereIn(DB::raw("LOWER(COALESCE(deployment_managers.status, ''))"), ['active', 'pending', 'pending_info'])
            ->whereIn(DB::raw("LOWER(COALESCE(users.role, ''))"), $this->stateManagerRoles)
            ->pluck('deployment_managers.user_id');

        return $ids->merge($deploymentManagerIds)
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
        ->all();
    }

    private function registeredBusinessUserIds(): array
    {
        $overrideIds = $this->bucketOverrideUserIds('registered_business');
        $explicitBusinessIds = $this->namedUserIds([
            'duke ogbodo',
            'ogbodo duke',
            'chigozie duke ogbodo',
            'mrs. eze florence',
            'eze florence',
            'florence eze',
            'ndeze2@gmail.com',
            'jaderahglobal2b@gmail.com',
        ]);

        $exactIds = collect($overrideIds)->merge($explicitBusinessIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($exactIds !== []) {
            return $exactIds;
        }

        if (!Schema::hasTable('subscriptions')) {
            return $explicitBusinessIds;
        }

        $paidStatuses = ['paid', 'completed', 'success', 'successful', 'verified'];
        $userIds = collect();

        if (Schema::hasColumn('subscriptions', 'user_id')) {
            $userIds = $userIds->merge(
                DB::table('subscriptions')
                    ->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), $paidStatuses)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->all()
            );
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id') && Schema::hasColumn('subscriptions', 'company_id')) {
            $userIds = $userIds->merge(
                DB::table('subscriptions')
                    ->join('companies', 'subscriptions.company_id', '=', 'companies.id')
                    ->whereIn(DB::raw("LOWER(COALESCE(subscriptions.payment_status, ''))"), $paidStatuses)
                    ->whereNotNull('companies.user_id')
                    ->pluck('companies.user_id')
                    ->all()
            );
        }

        return $userIds
            ->merge($explicitBusinessIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function namedUserIds(array $names): array
    {
        if ($names === []) {
            return [];
        }

        return DB::table('users')
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $normalized = strtolower(trim($name));
                    $pattern = '%' . str_replace(' ', '%', $normalized) . '%';

                    $query->orWhereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER(TRIM(email)) LIKE ?', [$pattern]);
                }
            })
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function stateManagersQuery()
    {
        $query = User::query();
        $stateManagerIds = $this->stateManagerUserIds();

        if ($stateManagerIds === []) {
            $query->whereRaw('1 = 0');
            return $query;
        }

        $query->whereIn('users.id', $stateManagerIds);

        return $query;
    }

    private function agentsQuery()
    {
        $query = User::query();
        $stateManagerIds = $this->stateManagerUserIds();
        $registeredBusinessIds = $this->registeredBusinessUserIds();

        if (Schema::hasColumn('users', 'role')) {
            $query->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), ['agent']);
        }

        if ($stateManagerIds !== []) {
            $query->whereNotIn('users.id', $stateManagerIds);
        }

        if ($registeredBusinessIds !== []) {
            $query->whereNotIn('users.id', $registeredBusinessIds);
        }

        return $query;
    }

    private function otherUsersQuery()
    {
        $query = User::query();
        $stateManagerIds = $this->stateManagerUserIds();
        $registeredBusinessIds = $this->registeredBusinessUserIds();

        if (Schema::hasColumn('users', 'role')) {
            $query->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), ['admin', 'administrator', 'accountant', 'cashier', 'store_manager', 'staff', 'user']);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($stateManagerIds !== []) {
            $query->whereNotIn('users.id', $stateManagerIds);
        }

        if ($registeredBusinessIds !== []) {
            $query->whereNotIn('users.id', $registeredBusinessIds);
        }

        return $query;
    }

    private function registeredBusinessesQuery()
    {
        $registeredBusinessIds = $this->registeredBusinessUserIds();
        $paidStatuses = ['paid', 'completed', 'success', 'successful', 'verified'];
        $exactMode = $this->bucketOverrideUserIds('registered_business') !== [];

        $query = User::query()
            ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
            ->select(
                'users.*',
                DB::raw("COALESCE(companies.name, companies.company_name, '') as company_name"),
                DB::raw("COALESCE(companies.domain_prefix, '') as company_domain_prefix"),
                DB::raw("COALESCE(companies.plan, '') as company_plan"),
                DB::raw('0 as total_paid'),
                DB::raw('NULL as last_paid_at')
        );

        if (Schema::hasTable('subscriptions')) {
            $subscriptionRevenueExpr = $this->subscriptionRevenueExpression('subscriptions');
            $subscriptionPaidCondition = $this->subscriptionPaidCondition('subscriptions');
            $subscriptionPaidAtExpr = $this->subscriptionPaidAtExpression('subscriptions');
            $query->addSelect(
                DB::raw("(SELECT COALESCE(SUM({$subscriptionRevenueExpr}), 0)
                    FROM subscriptions
                    WHERE {$subscriptionPaidCondition}
                      AND (
                        subscriptions.user_id = users.id
                        OR (users.company_id IS NOT NULL AND subscriptions.company_id = users.company_id)
                      )
                ) as total_paid"),
                DB::raw("(SELECT MAX({$subscriptionPaidAtExpr})
                    FROM subscriptions
                    WHERE {$subscriptionPaidCondition}
                      AND (
                        subscriptions.user_id = users.id
                        OR (users.company_id IS NOT NULL AND subscriptions.company_id = users.company_id)
                      )
                ) as last_paid_at")
            );
        }

        if ($exactMode) {
            $query->whereIn('users.id', $registeredBusinessIds);
        } else {
            $query->where(function ($innerQuery) use ($registeredBusinessIds) {
                if (Schema::hasTable('subscriptions')) {
                    $subscriptionPaidCondition = $this->subscriptionPaidCondition('subscriptions');
                    $innerQuery->whereExists(function ($subQuery) use ($subscriptionPaidCondition) {
                        $subQuery->select(DB::raw(1))
                            ->from('subscriptions')
                            ->whereRaw($subscriptionPaidCondition)
                            ->where(function ($matchQuery) {
                                $matchQuery->whereColumn('subscriptions.user_id', 'users.id')
                                    ->orWhere(function ($companyQuery) {
                                        $companyQuery->whereNotNull('users.company_id')
                                            ->whereColumn('subscriptions.company_id', 'users.company_id');
                                    });
                            });
                    });
                }

                if ($registeredBusinessIds !== []) {
                    $method = Schema::hasTable('subscriptions') ? 'orWhereIn' : 'whereIn';
                    $innerQuery->{$method}('users.id', $registeredBusinessIds);
                }
            });
        }

        return $query->distinct('users.id');
    }

    private function applyPlanUserScope($query)
    {
        if (Schema::hasTable('deployment_managers') && Schema::hasColumn('deployment_managers', 'user_id')) {
            $query->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('deployment_managers')
                    ->whereColumn('deployment_managers.user_id', 'users.id');
            });
        }

        $hasSubscriptions = Schema::hasTable('subscriptions');
        $hasCompanyPlan = Schema::hasTable('companies')
            && Schema::hasColumn('users', 'company_id')
            && Schema::hasColumn('companies', 'plan');

        if (!$hasSubscriptions && !$hasCompanyPlan) {
            return $query;
        }

        $query->where(function ($planQuery) use ($hasSubscriptions, $hasCompanyPlan) {
            if ($hasSubscriptions && Schema::hasColumn('subscriptions', 'user_id')) {
                $planQuery->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('subscriptions')
                        ->whereColumn('subscriptions.user_id', 'users.id');
                });
            }

            if ($hasSubscriptions && Schema::hasColumn('users', 'company_id') && Schema::hasColumn('subscriptions', 'company_id')) {
                $planQuery->orWhereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('subscriptions')
                        ->whereColumn('subscriptions.company_id', 'users.company_id');
                });
            }

            if ($hasCompanyPlan) {
                $planQuery->orWhereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('companies')
                        ->whereColumn('companies.id', 'users.company_id')
                        ->whereNotNull('companies.plan')
                        ->where('companies.plan', '!=', '');
                });
            }
        });

        return $query;
    }

    private function platformSubscriptionsQuery()
    {
        return Subscription::withoutGlobalScope('tenant');
    }

    private function subscriptionRevenueExpression(string $table = 'subscriptions'): string
    {
        $amountExpr = Schema::hasColumn('subscriptions', 'amount') ? "NULLIF({$table}.amount, 0)" : 'NULL';
        $planIdExpr = Schema::hasColumn('subscriptions', 'plan_id') && Schema::hasTable('plans')
            ? "(SELECT plans.price FROM plans WHERE plans.id = {$table}.plan_id LIMIT 1)"
            : 'NULL';
        $planNameExpr = Schema::hasColumn('subscriptions', 'plan') && Schema::hasTable('plans')
            ? "(SELECT plans.price FROM plans WHERE LOWER(plans.name) = LOWER({$table}.plan) LIMIT 1)"
            : 'NULL';
        $packageIdExpr = Schema::hasColumn('subscriptions', 'package_id') && Schema::hasTable('packages')
            ? "(SELECT packages.price FROM packages WHERE packages.id = {$table}.package_id LIMIT 1)"
            : 'NULL';

        return "COALESCE({$amountExpr}, {$planIdExpr}, {$planNameExpr}, {$packageIdExpr}, 0)";
    }

    private function subscriptionBuyerKeyExpression(string $table = 'subscriptions'): string
    {
        $columns = [];

        foreach (['company_id', 'user_id', 'id'] as $column) {
            if (Schema::hasColumn('subscriptions', $column)) {
                $columns[] = "{$table}.{$column}";
            }
        }

        return 'COALESCE(' . implode(', ', $columns ?: ["{$table}.id"]) . ')';
    }

    private function subscriptionPaidCondition(string $table = 'subscriptions'): string
    {
        $conditions = [];

        if (Schema::hasColumn('subscriptions', 'payment_status')) {
            $conditions[] = "LOWER(COALESCE({$table}.payment_status, '')) IN ('paid','completed','success','successful','verified')";
        }

        if (Schema::hasColumn('subscriptions', 'status')) {
            $conditions[] = "LOWER(COALESCE({$table}.status, '')) IN ('paid','completed','success','successful','verified','active')";
        }

        if (Schema::hasColumn('subscriptions', 'paid_at')) {
            $conditions[] = "{$table}.paid_at IS NOT NULL";
        }

        if (Schema::hasColumn('subscriptions', 'payment_date')) {
            $conditions[] = "{$table}.payment_date IS NOT NULL";
        }

        return '(' . implode(' OR ', $conditions ?: ['1 = 0']) . ')';
    }

    private function subscriptionPaidAtExpression(string $table = 'subscriptions'): string
    {
        $columns = [];

        foreach (['paid_at', 'payment_date', 'created_at'] as $column) {
            if (Schema::hasColumn('subscriptions', $column)) {
                $columns[] = "{$table}.{$column}";
            }
        }

        return 'COALESCE(' . implode(', ', $columns ?: ["{$table}.created_at"]) . ')';
    }

    private function payoutRecipientGroups(): array
    {
        $baseColumns = ['id', 'name', 'email', 'role'];
        foreach (['status', 'company_id', 'state_region', 'country'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $baseColumns[] = $column;
            }
        }

        return [
            'state_manager' => [
                'label' => 'State Managers',
                'users' => $this->stateManagersQuery()
                    ->select($baseColumns)
                    ->orderBy('name')
                    ->limit(250)
                    ->get(),
            ],
            'agent' => [
                'label' => 'Agents',
                'users' => $this->agentsQuery()
                    ->select($baseColumns)
                    ->orderBy('name')
                    ->limit(250)
                    ->get(),
            ],
            'app_user' => [
                'label' => 'App Users With Plans',
                'users' => $this->customerUsersQuery()
                    ->whereNotIn(DB::raw("LOWER(COALESCE(role, ''))"), ['agent', 'sales_agent'])
                    ->select($baseColumns)
                    ->orderBy('name')
                    ->limit(300)
                    ->get(),
            ],
        ];
    }

    private function resolvePayoutRecipient(string $recipientType, ?int $recipientUserId, ?string $recipientName): array
    {
        if ($recipientType === 'external') {
            $name = trim((string) $recipientName);

            if ($name === '') {
                throw new \InvalidArgumentException('Recipient name is required for external payouts.');
            }

            return ['name' => $name, 'user_id' => null];
        }

        if (!$recipientUserId) {
            throw new \InvalidArgumentException('Please select a recipient from the chosen category.');
        }

        $query = User::query()->whereKey($recipientUserId);

        match ($recipientType) {
            'state_manager' => $query->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), $this->stateManagerRoles),
            'agent' => $query->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), $this->agentRoles),
            'app_user' => $this->applyPlanUserScope(
                $query->whereNotIn(DB::raw("LOWER(COALESCE(role, ''))"), ['super_admin', 'superadmin', 'state_manager', 'deployment_manager', 'manager', 'agent'])
            ),
            default => throw new \InvalidArgumentException('Invalid recipient category selected.'),
        };

        $user = $query->first();
        if (!$user) {
            throw new \InvalidArgumentException('The selected recipient does not match the chosen category.');
        }

        return [
            'name' => trim((string) ($user->name ?: $user->email)),
            'user_id' => $user->id,
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $activeBranch = [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
        if (method_exists(Subscription::class, 'expireDueSubscriptions')) {
            Subscription::expireDueSubscriptions();
        } else {
            $this->platformSubscriptionsQuery()
                ->whereRaw("LOWER(COALESCE(status, '')) IN ('active','trial')")
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', now()->toDateString())
                ->update([
                    'status' => 'Expired',
                    'updated_at' => now(),
                ]);
        }

        // FIXED: More inclusive security check
        if (!$this->isSuperAdmin($user)) {
            Log::warning('Unauthorized super admin access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'role_lowercase' => strtolower($user->role)
            ]);
            
            abort(403, 'Unauthorized access to Master Panel.');
        }

        Log::info('Super admin dashboard accessed successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        try {
            $deploymentLimit = 50; 

            $paidPaymentStatuses = ['paid', 'completed', 'success', 'successful', 'verified'];
            $activeSubscriptionStatuses = ['active', 'trial'];
            $pendingSubscriptionStatuses = ['pending', 'awaiting payment', 'awaiting_payment', 'unpaid'];
            $activeCompanyStatuses = ['active', 'trial', 'enabled'];

            $paidSubscriptionsQuery = Schema::hasTable('subscriptions')
                ? $this->platformSubscriptionsQuery()->where(function ($query) use ($paidPaymentStatuses) {
                    $query->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), $paidPaymentStatuses);

                    if (Schema::hasColumn('subscriptions', 'status')) {
                        $query->orWhereIn(DB::raw("LOWER(COALESCE(status, ''))"), array_merge($paidPaymentStatuses, ['active']));
                    }

                    if (Schema::hasColumn('subscriptions', 'paid_at')) {
                        $query->orWhereNotNull('paid_at');
                    }

                    if (Schema::hasColumn('subscriptions', 'payment_date')) {
                        $query->orWhereNotNull('payment_date');
                    }
                })
                : null;

            $salesBranchScope = function ($query, string $table = 'sales') use ($activeBranch) {
                if (Schema::hasColumn($table, 'branch_id') && !empty($activeBranch['id'])) {
                    return $query->where($table . '.branch_id', (string) $activeBranch['id']);
                }
                if (Schema::hasColumn($table, 'branch_name') && !empty($activeBranch['name'])) {
                    return $query->where($table . '.branch_name', (string) $activeBranch['name']);
                }
                return $query;
            };

            $salesRevenue = Schema::hasTable('sales')
                ? ((float) ($this->applyFinalizedSalesFilter($salesBranchScope(DB::table('sales')))->sum('total') ?? 0))
                : 0.0;
            $subscriptionRevenueExpr = $this->subscriptionRevenueExpression('subscriptions');
            $subscriptionBuyerKeyExpr = $this->subscriptionBuyerKeyExpression('subscriptions');
            $subscriptionRevenue = $paidSubscriptionsQuery
                ? ((float) ((clone $paidSubscriptionsQuery)->selectRaw("SUM({$subscriptionRevenueExpr}) as total_revenue")->value('total_revenue') ?? 0))
                : 0.0;
            $paidSubscriptionsCount = $paidSubscriptionsQuery
                ? (int) ((clone $paidSubscriptionsQuery)->count() ?? 0)
                : 0;
            // Distinct buying companies (not transaction count)
            $paidBuyersCount = $paidSubscriptionsQuery
                ? (int) ((clone $paidSubscriptionsQuery)->selectRaw("COUNT(DISTINCT {$subscriptionBuyerKeyExpr}) as buyer_count")->value('buyer_count') ?? 0)
                : 0;
            $platformRevenue = !empty($activeBranch['id']) || !empty($activeBranch['name'])
                ? $salesRevenue
                : ($subscriptionRevenue > 0 ? $subscriptionRevenue : $salesRevenue);

            $itemSalesTodayRevenue = 0.0;
            $itemSalesOrders = 0;
            $itemSalesUnits = 0.0;
            if (Schema::hasTable('sales')) {
                $itemSalesTodayRevenueQuery = $salesBranchScope(DB::table('sales'));
                $this->applyFinalizedSalesFilter($itemSalesTodayRevenueQuery);
                $itemSalesTodayRevenue = (float) ($itemSalesTodayRevenueQuery
                    ->whereDate('created_at', today())
                    ->sum('total') ?? 0);

                $itemSalesOrdersQuery = $salesBranchScope(DB::table('sales'));
                $this->applyFinalizedSalesFilter($itemSalesOrdersQuery);
                $itemSalesOrders = (int) ($itemSalesOrdersQuery->count() ?? 0);
            }
            if (Schema::hasTable('sale_items') && Schema::hasTable('products') && Schema::hasTable('sales')) {
                $itemSalesUnitsQuery = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products as sale_products', 'sale_items.product_id', '=', 'sale_products.id')
                    ->selectRaw('COALESCE(SUM(' . InventoryQuantity::saleStockUnitsExpression('sale_items', 'sale_products') . '), 0) as total_units');

                $salesBranchScope($itemSalesUnitsQuery, 'sales');
                $this->applyFinalizedSalesFilter($itemSalesUnitsQuery, 'sales');
                $itemSalesUnits = (float) ($itemSalesUnitsQuery->value('total_units') ?? 0);
            }

            $activeSubs = Schema::hasTable('subscriptions')
                ? $this->platformSubscriptionsQuery()
                    ->where(function ($query) use ($activeSubscriptionStatuses, $paidPaymentStatuses) {
                        $query->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), $activeSubscriptionStatuses)
                            ->orWhereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), array_merge($paidPaymentStatuses, ['free']));
                    })
                    ->count()
                : 0;
            $activeCompanies = Company::query()
                ->where(function ($query) use ($activeCompanyStatuses) {
                    $query->whereNull('status')
                        ->orWhereIn(DB::raw("LOWER(COALESCE(status, ''))"), $activeCompanyStatuses);
                })
                ->count();
            $totalCompanies = Company::count();
            $customerUsersBaseQuery = $this->customerUsersQuery();
            $totalCustomerUsers = (clone $customerUsersBaseQuery)->count();
            $recentSignups = (clone $customerUsersBaseQuery)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            $registeredBusinessesRows = (clone $this->registeredBusinessesQuery())->get(['users.id', 'total_paid', 'last_paid_at']);
            $registeredBusinessesTotal = $registeredBusinessesRows
                ->pluck('id')
                ->filter()
                ->unique()
                ->count();
            $registeredBusinessRevenue = (float) $registeredBusinessesRows->sum(
                fn ($row) => (float) ($row->total_paid ?? 0)
            );

            $deploymentCustomerUsers = 0;
            if (
                Schema::hasTable('companies')
                && Schema::hasColumn('users', 'company_id')
                && Schema::hasColumn('companies', 'deployed_by')
            ) {
                $deploymentCustomerUsers = (clone $customerUsersBaseQuery)
                    ->leftJoin('companies as customer_companies', 'users.company_id', '=', 'customer_companies.id')
                    ->whereNotNull('customer_companies.deployed_by')
                    ->where('customer_companies.deployed_by', '!=', 0)
                    ->distinct()
                    ->count('users.id');
            }
            $directCustomerUsers = max(0, $totalCustomerUsers - $deploymentCustomerUsers);

            $deploymentSubscriptionRevenue = 0.0;
            $deploymentPaidSubs = 0;
            $deployedPaidSubscriptionsQuery = null;
            $stateManagerIds = $this->stateManagerUserIds();
            $managerStatusBaseQuery = User::query()->whereKey($stateManagerIds);
            if ($paidSubscriptionsQuery) {
                $deploymentSubscriptionsQuery = (clone $paidSubscriptionsQuery)
                    ->select('subscriptions.*')
                    ->leftJoin('companies as source_companies', 'subscriptions.company_id', '=', 'source_companies.id')
                    ->whereRaw("{$subscriptionRevenueExpr} > 0")
                    ->where(function ($query) {
                        $hasSource = false;

                        if (Schema::hasColumn('subscriptions', 'deployed_by')) {
                            $query->whereNotNull('subscriptions.deployed_by')
                                ->where('subscriptions.deployed_by', '!=', 0);
                            $hasSource = true;
                        }

                        if (Schema::hasColumn('companies', 'deployed_by')) {
                            $method = $hasSource ? 'orWhere' : 'where';
                            $query->{$method}(function ($subQuery) {
                                $subQuery->whereNotNull('source_companies.deployed_by')
                                    ->where('source_companies.deployed_by', '!=', 0);
                            });
                            $hasSource = true;
                        }

                        if (!$hasSource) {
                            $query->whereRaw('1 = 0');
                        }
                    });

                $deploymentSubscriptionRevenue = (float) ((clone $deploymentSubscriptionsQuery)->selectRaw("SUM({$subscriptionRevenueExpr}) as total_revenue")->value('total_revenue') ?? 0);
                $deploymentPaidSubs = (int) ((clone $deploymentSubscriptionsQuery)->selectRaw("COUNT(DISTINCT {$subscriptionBuyerKeyExpr}) as buyer_count")->value('buyer_count') ?? 0);
                $deployedPaidSubscriptionsQuery = clone $deploymentSubscriptionsQuery;
            }

            $directSubscriptionRevenue = max(0, $subscriptionRevenue - $deploymentSubscriptionRevenue);
            $directPaidSubs = max(0, $paidBuyersCount - $deploymentPaidSubs);

            $stockValue = 0;
            $lowStockItems = 0;
            if (Schema::hasTable('products')) {
                $hasProductPrice = Schema::hasColumn('products', 'product_price');
                $hasPrice = Schema::hasColumn('products', 'price');
                $priceExpr = $hasProductPrice ? 'product_price' : ($hasPrice ? 'price' : '0');

                if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
                    $stockValue = (float) (DB::table('product_branch_stocks')
                        ->join('products', 'products.id', '=', 'product_branch_stocks.product_id')
                        ->where('product_branch_stocks.branch_id', (string) $activeBranch['id'])
                        ->selectRaw("SUM(COALESCE(product_branch_stocks.quantity, 0) * COALESCE(products.{$priceExpr}, 0)) as total_stock_value")
                        ->value('total_stock_value') ?? 0);
                    $lowStockItems = (int) (DB::table('product_branch_stocks')
                        ->where('branch_id', (string) $activeBranch['id'])
                        ->whereNotNull('quantity')
                        ->where('quantity', '<=', 10)
                        ->count());
                } else {
                    $stockValue = (float) (DB::table('products')
                        ->selectRaw("SUM(COALESCE({$priceExpr}, 0) * COALESCE(stock, 0)) as total_stock_value")
                        ->value('total_stock_value') ?? 0);
                    if (Schema::hasColumn('products', 'stock')) {
                        $lowStockItems = (int) (DB::table('products')
                            ->whereNotNull('stock')
                            ->where('stock', '<=', 10)
                            ->count());
                    }
                }
            }

            $planSalesBaseQuery = $paidSubscriptionsQuery ? clone $paidSubscriptionsQuery : null;
            $planSalesToday = $planSalesBaseQuery ? (int) ((clone $planSalesBaseQuery)->whereDate('subscriptions.created_at', today())->selectRaw("COUNT(DISTINCT {$subscriptionBuyerKeyExpr}) as buyer_count")->value('buyer_count') ?? 0) : 0;
            $planSalesMonth = $planSalesBaseQuery ? (int) ((clone $planSalesBaseQuery)->whereMonth('subscriptions.created_at', now()->month)->whereYear('subscriptions.created_at', now()->year)->selectRaw("COUNT(DISTINCT {$subscriptionBuyerKeyExpr}) as buyer_count")->value('buyer_count') ?? 0) : 0;
            $planSalesValueMonth = $planSalesBaseQuery ? (float) ((clone $planSalesBaseQuery)->whereMonth('subscriptions.created_at', now()->month)->whereYear('subscriptions.created_at', now()->year)->selectRaw("SUM({$subscriptionRevenueExpr}) as total_revenue")->value('total_revenue') ?? 0) : 0;
            $avgPlanSale = $paidBuyersCount > 0 ? ($subscriptionRevenue / $paidBuyersCount) : 0;

            // METRICS
            $metrics = [
                'total_companies'  => $totalCompanies, 
                'total_tenants'    => $activeCompanies > 0 ? $activeCompanies : $totalCompanies,
                'total_users'      => $totalCustomerUsers,
                'registered_user_revenue' => $registeredBusinessRevenue,
                'verified_users'   => Schema::hasColumn('users', 'is_verified')
                                      ? (clone $customerUsersBaseQuery)->where('is_verified', 1)->count()
                                      : 0,
                'state_managers_total' => (clone $this->stateManagersQuery())->count(),
                'agents_total' => (clone $this->agentsQuery())->count(),
                'registered_businesses_total' => $registeredBusinessesTotal,
                'other_users_total' => (clone $this->otherUsersQuery())->count(),
                'active_subs'      => $activeSubs > 0 ? $activeSubs : $activeCompanies,
                'paid_subs'        => $paidBuyersCount,
                'direct_paid_subs' => $directPaidSubs,
                'deployment_paid_subs' => $deploymentPaidSubs,
                'total_subs'       => Schema::hasTable('subscriptions')
                                      ? $this->platformSubscriptionsQuery()->count()
                                      : 0,
                'platform_revenue' => $platformRevenue,
                'owner_subscription_revenue' => $subscriptionRevenue,
                'direct_subscription_revenue' => $directSubscriptionRevenue,
                'deployment_subscription_revenue' => $deploymentSubscriptionRevenue,
                'pending_setups'   => Schema::hasTable('subscriptions')
                                      ? $this->platformSubscriptionsQuery()->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), $pendingSubscriptionStatuses)->count()
                                      : 0,
                'pending_managers' => Schema::hasColumn('users', 'status')
                                      ? (clone $managerStatusBaseQuery)->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['pending', 'pending_info'])->count()
                                      : 0,
                'active_managers'  => $stateManagerIds !== []
                                      ? (Schema::hasColumn('users', 'status')
                                          ? (clone $managerStatusBaseQuery)->whereIn(DB::raw("LOWER(COALESCE(status, 'active'))"), ['active'])->count()
                                          : count($stateManagerIds))
                                      : 0,
                'suspended_managers'  => Schema::hasColumn('users', 'status')
                                      ? (clone $managerStatusBaseQuery)->whereRaw("LOWER(COALESCE(status, '')) = ?", ['suspended'])->count()
                                      : 0,
                'total_stock_val'  => $stockValue,
                'low_stock_items'  => $lowStockItems,
                'recent_signups'   => $recentSignups,
                'direct_customer_users' => $directCustomerUsers,
                'deployment_customer_users' => $deploymentCustomerUsers,
                'plan_sales_today' => $planSalesToday,
                'plan_sales_month' => $planSalesMonth,
                'plan_sales_value_month' => $planSalesValueMonth,
                'avg_plan_sale'    => $avgPlanSale,
                'item_sales_revenue' => $salesRevenue,
                'item_sales_today_revenue' => $itemSalesTodayRevenue,
                'item_sales_orders' => $itemSalesOrders,
                'item_sales_units' => $itemSalesUnits,
                'expiring_soon_subs' => Schema::hasTable('subscriptions')
                                      ? $this->platformSubscriptionsQuery()->expiringSoon(7)->count()
                                      : 0,
                'expired_subs'       => Schema::hasTable('subscriptions')
                                      ? $this->platformSubscriptionsQuery()->whereRaw("LOWER(COALESCE(status, '')) = 'expired'")->count()
                                      : 0,
                'total_payouts'      => Schema::hasTable('platform_payouts')
                                      ? (float) PlatformPayout::sum('amount')
                                      : 0,
                'net_platform_balance' => $subscriptionRevenue - (Schema::hasTable('platform_payouts')
                                      ? (float) PlatformPayout::sum('amount')
                                      : 0),
            ];

            $stats = $metrics;

            // REVENUE TRENDS
            $revenueTrends = collect();
            if ($paidSubscriptionsQuery) {
                $revenueTrends = (clone $paidSubscriptionsQuery)
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'), 
                        DB::raw("SUM({$subscriptionRevenueExpr}) as total"),
                        DB::raw('COUNT(*) as subscriptions_count'),
                        DB::raw('MONTH(created_at) as month_num')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }
            if ($revenueTrends->isEmpty() && Schema::hasTable('sales')) {
                $salesRevenueTrendsQuery = $salesBranchScope(DB::table('sales'));
                $this->applyFinalizedSalesFilter($salesRevenueTrendsQuery);
                $revenueTrends = $salesRevenueTrendsQuery
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('SUM(total) as total'),
                        DB::raw('COUNT(*) as subscriptions_count'),
                        DB::raw('MONTH(created_at) as month_num')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }

            // TENANT GROWTH
            $tenantGrowth = collect();
            if (Schema::hasTable('companies')) {
                $tenantGrowth = Company::query()
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('COUNT(*) as count'),
                        DB::raw('MONTH(created_at) as month_num')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }

            // PLAN DISTRIBUTION
            $planStats = [];
            if (Schema::hasTable('subscriptions')) {
                $planExpr = "COALESCE(NULLIF(plan_name, ''), plan, 'Basic')";
                $planStats = $this->platformSubscriptionsQuery()->selectRaw("{$planExpr} as plan_label, COUNT(*) as total")
                    ->groupByRaw($planExpr)
                    ->pluck('total', 'plan_label')
                    ->toArray();
            }
            if (empty($planStats)) {
                $companyPlanExpr = "COALESCE(NULLIF(plan, ''), 'Basic')";
                $planStats = Company::selectRaw("{$companyPlanExpr} as plan_label, COUNT(*) as total")
                    ->groupByRaw($companyPlanExpr)
                    ->pluck('total', 'plan_label')
                    ->toArray();
            }

            // REVENUE BY PLAN
            $revenueByPlan = collect();
            if ($paidSubscriptionsQuery) {
                $planExpr = "COALESCE(NULLIF(plan_name, ''), plan, 'Basic')";
                $revenueByPlan = (clone $paidSubscriptionsQuery)
                    ->whereYear('created_at', date('Y'))
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('MONTH(created_at) as month_num'),
                        DB::raw("{$planExpr} as plan_name"),
                        DB::raw("SUM({$subscriptionRevenueExpr}) as revenue")
                    )
                    ->groupByRaw("MONTHNAME(created_at), MONTH(created_at), {$planExpr}")
                    ->orderBy('month_num', 'asc')
                    ->get();
            }
            if ($revenueByPlan->isEmpty()) {
                $companyPlanExpr = "COALESCE(NULLIF(plan, ''), 'Basic')";
                $revenueByPlan = Company::select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('MONTH(created_at) as month_num'),
                        DB::raw("{$companyPlanExpr} as plan_name"),
                        DB::raw('COUNT(*) as revenue')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupByRaw("MONTHNAME(created_at), MONTH(created_at), {$companyPlanExpr}")
                    ->orderBy('month_num', 'asc')
                    ->get();
            }

            // DEPLOYMENTS
            $deployments = collect();
            if (Schema::hasTable('deployment_managers') && $stateManagerIds !== []) {
                $deployments = DB::table('deployment_managers')
                    ->join('users', 'deployment_managers.user_id', '=', 'users.id')
                    ->select('deployment_managers.*', 'users.email', 'users.name as manager_name', 'users.is_verified')
                    ->whereIn('deployment_managers.user_id', $stateManagerIds)
                    ->latest('deployment_managers.created_at')
                    ->get();
            }

            // TOP DEPLOYMENT MANAGERS (for compact authorization progress bars)
            $managerPerformance = ['rows' => [], 'max' => 1];
            if ($stateManagerIds !== []) {
                $stateManagerUsers = (clone $this->stateManagersQuery())
                    ->select('users.id', 'users.name', 'users.status')
                    ->get();

                $rows = $stateManagerUsers->map(function ($manager) use ($paidSubscriptionsQuery, $subscriptionRevenueExpr) {
                    $planSales = 0;
                    $revenue = 0.0;

                    if ($paidSubscriptionsQuery) {
                        $managerSalesQuery = (clone $paidSubscriptionsQuery)
                            ->leftJoin('companies as manager_companies', 'subscriptions.company_id', '=', 'manager_companies.id')
                            ->where(function ($query) use ($manager) {
                                $hasSource = false;

                                if (Schema::hasColumn('subscriptions', 'deployed_by')) {
                                    $query->where('subscriptions.deployed_by', $manager->id);
                                    $hasSource = true;
                                }

                                if (Schema::hasColumn('companies', 'deployed_by')) {
                                    $method = $hasSource ? 'orWhere' : 'where';
                                    $query->{$method}('manager_companies.deployed_by', $manager->id);
                                    $hasSource = true;
                                }

                                if (!$hasSource) {
                                    $query->whereRaw('1 = 0');
                                }
                            });

                        $planSales = (int) (clone $managerSalesQuery)->count();
                        $revenue = (float) ((clone $managerSalesQuery)->selectRaw("SUM({$subscriptionRevenueExpr}) as total_revenue")->value('total_revenue') ?? 0);
                    }

                    $deployments = (
                        Schema::hasTable('companies')
                        && Schema::hasColumn('companies', 'deployed_by')
                    )
                        ? (int) Company::query()->where('deployed_by', $manager->id)->count()
                        : 0;

                    $status = strtolower((string) ($manager->status ?? 'pending'));
                    $statusWeight = match ($status) {
                        'active' => 300,
                        'pending', 'pending_info' => 150,
                        'suspended', 'inactive' => 50,
                        default => 100,
                    };

                    $score = $revenue > 0
                        ? $revenue
                        : (($planSales * 1000) + ($deployments * 250) + $statusWeight);

                    return [
                        'name' => trim((string) ($manager->name ?: ('Manager #' . $manager->id))),
                        'score' => (float) $score,
                        'status' => (string) ($manager->status ?? 'pending'),
                        'revenue' => (float) $revenue,
                        'plan_sales' => (int) $planSales,
                        'deployments' => (int) $deployments,
                    ];
                })
                    ->sortByDesc('score')
                    ->take(3)
                    ->values();

                $managerPerformance = [
                    'rows' => $rows->toArray(),
                    'max' => max(1.0, (float) ($rows->max('score') ?? 1.0)),
                ];
            }

            // STATUS DISTRIBUTION
            $statusDistribution = [
                'labels' => ['Active', 'Pending', 'Suspended'],
                'values' => [
                    $metrics['active_managers'],
                    $metrics['pending_managers'],
                    $metrics['suspended_managers']
                ]
            ];

            $deployedMonthlyTrends = collect();
            if ($deployedPaidSubscriptionsQuery) {
                $deployedMonthlyTrends = (clone $deployedPaidSubscriptionsQuery)
                    ->select(
                        DB::raw('MONTH(subscriptions.created_at) as month_num'),
                        DB::raw("SUM({$subscriptionRevenueExpr}) as total"),
                        DB::raw("COUNT(DISTINCT {$subscriptionBuyerKeyExpr}) as subscriptions_count")
                    )
                    ->whereYear('subscriptions.created_at', date('Y'))
                    ->groupBy('month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }

            $monthlyRevenueMap = [];
            $monthlyOrdersMap = [];
            foreach ($deployedMonthlyTrends as $row) {
                $monthlyRevenueMap[(int) $row->month_num] = (float) $row->total;
                $monthlyOrdersMap[(int) $row->month_num] = (int) ($row->subscriptions_count ?? 0);
            }

            $companyRows = Company::select(
                    DB::raw('MONTH(created_at) as month_num'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereYear('created_at', date('Y'))
                ->groupBy('month_num')
                ->orderBy('month_num', 'asc')
                ->get();
            $monthlyCompaniesMap = $companyRows->pluck('total', 'month_num')->toArray();

            $userRows = (clone $customerUsersBaseQuery)
                ->select(
                    DB::raw('MONTH(created_at) as month_num'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereYear('created_at', date('Y'))
                ->groupBy('month_num')
                ->orderBy('month_num', 'asc')
                ->get();
            $monthlyUsersMap = $userRows->pluck('total', 'month_num')->toArray();

            $chartSeries = [
                'labels' => [],
                'revenue' => [],
                'orders' => [],
                'companies' => [],
                'users' => [],
            ];
            for ($month = 1; $month <= 12; $month++) {
                $chartSeries['labels'][] = date('M', mktime(0, 0, 0, $month, 1));
                $chartSeries['revenue'][] = (float) ($monthlyRevenueMap[$month] ?? 0);
                $chartSeries['orders'][] = (int) ($monthlyOrdersMap[$month] ?? 0);
                $chartSeries['companies'][] = (int) ($monthlyCompaniesMap[$month] ?? 0);
                $chartSeries['users'][] = (int) ($monthlyUsersMap[$month] ?? 0);
            }

            $visitorAnalytics = [
                'cards' => [
                    ['label' => 'Daily Visits', 'value' => 0, 'visitors' => 0, 'note' => 'Today', 'tone' => 'visit-blue', 'icon' => 'mdi-eye-outline'],
                    ['label' => 'Weekly Visits', 'value' => 0, 'visitors' => 0, 'note' => 'Last 7 days', 'tone' => 'visit-green', 'icon' => 'mdi-calendar-week'],
                    ['label' => 'Monthly Visits', 'value' => 0, 'visitors' => 0, 'note' => 'Current month', 'tone' => 'visit-violet', 'icon' => 'mdi-chart-timeline-variant'],
                    ['label' => 'Yearly Visits', 'value' => 0, 'visitors' => 0, 'note' => 'Current year', 'tone' => 'visit-amber', 'icon' => 'mdi-calendar-star'],
                ],
                'dailyLabels' => [],
                'dailyVisits' => [],
                'dailyVisitors' => [],
                'dailyUsers' => [],
                'dailyPaid' => [],
                'periodLabels' => ['Daily', 'Weekly', 'Monthly', 'Yearly'],
                'periodVisits' => [0, 0, 0, 0],
                'moduleLabels' => [],
                'moduleValues' => [],
                'decisionCards' => [],
            ];

            $activityAvailable = Schema::hasTable('activity_logs') && Schema::hasColumn('activity_logs', 'created_at');
            $activityHasIp = $activityAvailable && Schema::hasColumn('activity_logs', 'ip_address');
            $activityHasUser = $activityAvailable && Schema::hasColumn('activity_logs', 'user_id');
            $visitorColumn = $activityHasIp ? 'ip_address' : ($activityHasUser ? 'user_id' : null);
            $todayStart = now()->startOfDay();
            $weekStart = now()->subDays(6)->startOfDay();
            $monthStart = now()->startOfMonth();
            $yearStart = now()->startOfYear();

            if ($activityAvailable) {
                $activityBase = DB::table('activity_logs');
                $dailyVisitCount = (clone $activityBase)->where('created_at', '>=', $todayStart)->count();
                $weeklyVisitCount = (clone $activityBase)->where('created_at', '>=', $weekStart)->count();
                $monthlyVisitCount = (clone $activityBase)->where('created_at', '>=', $monthStart)->count();
                $yearlyVisitCount = (clone $activityBase)->where('created_at', '>=', $yearStart)->count();
                $dailyVisitorCount = $visitorColumn
                    ? (clone $activityBase)->where('created_at', '>=', $todayStart)->distinct()->count($visitorColumn)
                    : $dailyVisitCount;
                $weeklyVisitorCount = $visitorColumn
                    ? (clone $activityBase)->where('created_at', '>=', $weekStart)->distinct()->count($visitorColumn)
                    : $weeklyVisitCount;
                $monthlyVisitorCount = $visitorColumn
                    ? (clone $activityBase)->where('created_at', '>=', $monthStart)->distinct()->count($visitorColumn)
                    : $monthlyVisitCount;
                $yearlyVisitorCount = $visitorColumn
                    ? (clone $activityBase)->where('created_at', '>=', $yearStart)->distinct()->count($visitorColumn)
                    : $yearlyVisitCount;

                $dailyRows = (clone $activityBase)
                    ->selectRaw(
                        'DATE(created_at) as visit_date, COUNT(*) as visits' .
                        ($visitorColumn ? ", COUNT(DISTINCT {$visitorColumn}) as visitors" : ', COUNT(*) as visitors')
                    )
                    ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                    ->groupBy('visit_date')
                    ->orderBy('visit_date')
                    ->get()
                    ->keyBy('visit_date');

                $moduleRows = (clone $activityBase)
                    ->when(Schema::hasColumn('activity_logs', 'module'), fn ($query) => $query->whereNotNull('module'))
                    ->selectRaw((Schema::hasColumn('activity_logs', 'module') ? 'module' : "'Activity'") . ' as module_name, COUNT(*) as total')
                    ->where('created_at', '>=', $monthStart)
                    ->groupBy('module_name')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get();

                $visitorAnalytics['moduleLabels'] = $moduleRows->pluck('module_name')->map(fn ($label) => ucwords(str_replace(['_', '-'], ' ', (string) $label)))->toArray();
                $visitorAnalytics['moduleValues'] = $moduleRows->pluck('total')->map(fn ($total) => (int) $total)->toArray();
            } else {
                $currentMonthIndex = max(0, (int) date('n') - 1);
                $dailyVisitCount = (int) ($metrics['plan_sales_today'] ?? 0) + (int) ($metrics['recent_signups'] ?? 0);
                $weeklyVisitCount = (int) round(array_sum(array_slice($chartSeries['users'], -2)) + array_sum(array_slice($chartSeries['companies'], -2)));
                $monthlyVisitCount = (int) (
                    ($chartSeries['users'][$currentMonthIndex] ?? 0) +
                    ($chartSeries['companies'][$currentMonthIndex] ?? 0) +
                    ($chartSeries['orders'][$currentMonthIndex] ?? 0)
                );
                $yearlyVisitCount = (int) (array_sum($chartSeries['users']) + array_sum($chartSeries['companies']) + array_sum($chartSeries['orders']));
                $dailyVisitorCount = (int) ($metrics['recent_signups'] ?? 0);
                $weeklyVisitorCount = (int) array_sum(array_slice($chartSeries['users'], -2));
                $monthlyVisitorCount = (int) ($chartSeries['users'][$currentMonthIndex] ?? 0);
                $yearlyVisitorCount = (int) array_sum($chartSeries['users']);
                $dailyRows = collect();
            }

            $dailyUsersMap = (clone $customerUsersBaseQuery)
                ->selectRaw('DATE(created_at) as signup_date, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->groupBy('signup_date')
                ->pluck('total', 'signup_date')
                ->toArray();

            $dailyPaidMap = [];
            if ($deployedPaidSubscriptionsQuery) {
                $dailyPaidMap = (clone $deployedPaidSubscriptionsQuery)
                    ->selectRaw('DATE(subscriptions.created_at) as paid_date, COUNT(DISTINCT subscriptions.company_id) as total')
                    ->where('subscriptions.created_at', '>=', now()->subDays(13)->startOfDay())
                    ->groupBy('paid_date')
                    ->pluck('total', 'paid_date')
                    ->toArray();
            }

            for ($day = 13; $day >= 0; $day--) {
                $date = now()->subDays($day);
                $dateKey = $date->format('Y-m-d');
                $fallbackVisits = (int) (($dailyUsersMap[$dateKey] ?? 0) + ($dailyPaidMap[$dateKey] ?? 0));
                $visitRow = $dailyRows->get($dateKey);

                $visitorAnalytics['dailyLabels'][] = $date->format('M j');
                $visitorAnalytics['dailyVisits'][] = $activityAvailable ? (int) ($visitRow->visits ?? 0) : $fallbackVisits;
                $visitorAnalytics['dailyVisitors'][] = $activityAvailable ? (int) ($visitRow->visitors ?? 0) : max(0, $fallbackVisits - (int) ($dailyPaidMap[$dateKey] ?? 0));
                $visitorAnalytics['dailyUsers'][] = (int) ($dailyUsersMap[$dateKey] ?? 0);
                $visitorAnalytics['dailyPaid'][] = (int) ($dailyPaidMap[$dateKey] ?? 0);
            }

            $visitorAnalytics['cards'][0]['value'] = (int) $dailyVisitCount;
            $visitorAnalytics['cards'][1]['value'] = (int) $weeklyVisitCount;
            $visitorAnalytics['cards'][2]['value'] = (int) $monthlyVisitCount;
            $visitorAnalytics['cards'][3]['value'] = (int) $yearlyVisitCount;
            $visitorAnalytics['cards'][0]['visitors'] = (int) $dailyVisitorCount;
            $visitorAnalytics['cards'][1]['visitors'] = (int) $weeklyVisitorCount;
            $visitorAnalytics['cards'][2]['visitors'] = (int) $monthlyVisitorCount;
            $visitorAnalytics['cards'][3]['visitors'] = (int) $yearlyVisitorCount;
            $visitorAnalytics['periodVisits'] = [(int) $dailyVisitCount, (int) $weeklyVisitCount, (int) $monthlyVisitCount, (int) $yearlyVisitCount];

            if (empty($visitorAnalytics['moduleLabels'])) {
                $visitorAnalytics['moduleLabels'] = ['Users', 'Companies', 'Paid Plans', 'Revenue Events'];
                $visitorAnalytics['moduleValues'] = [
                    (int) array_sum($chartSeries['users']),
                    (int) array_sum($chartSeries['companies']),
                    (int) array_sum($chartSeries['orders']),
                    (int) count(array_filter($chartSeries['revenue'], fn ($value) => (float) $value > 0)),
                ];
            }

            $yearlyVisits = max(1, (int) $yearlyVisitCount);
            $visitorAnalytics['decisionCards'] = [
                ['label' => 'Visitor to User Signal', 'value' => round(((int) ($metrics['total_users'] ?? 0) / $yearlyVisits) * 100, 1) . '%', 'note' => 'Registered users against yearly visit signal'],
                ['label' => 'Paid Conversion Signal', 'value' => round(((int) ($metrics['paid_subs'] ?? 0) / $yearlyVisits) * 100, 1) . '%', 'note' => 'Paid businesses against yearly visit signal'],
                ['label' => 'Best Activity Module', 'value' => $visitorAnalytics['moduleLabels'][0] ?? 'No activity yet', 'note' => 'Highest activity area this month'],
                ['label' => 'Weekly Momentum', 'value' => $weeklyVisitCount > 0 ? round(($dailyVisitCount / max(1, $weeklyVisitCount)) * 100, 1) . '%' : '0%', 'note' => 'Today share of 7-day traffic'],
            ];

            $activityHeatmap = [];
            $forceBranchHeatmap = !empty($activeBranch['id']) || !empty($activeBranch['name']);
            if (!$forceBranchHeatmap && Schema::hasTable('subscriptions')) {
                $heatRows = $this->platformSubscriptionsQuery()->select(
                        DB::raw('DAYOFWEEK(created_at) as dow'),
                        DB::raw('HOUR(created_at) as hr'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('dow', 'hr')
                    ->get();

                $dayMap = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun'];
                foreach ($heatRows as $row) {
                    $dayKey = $dayMap[(int) $row->dow] ?? null;
                    if ($dayKey === null) {
                        continue;
                    }
                    $activityHeatmap[$dayKey][(int) $row->hr] = (int) $row->total;
                }
            }
            if (empty($activityHeatmap) && Schema::hasTable('sales')) {
                $salesHeatRowsQuery = $salesBranchScope(DB::table('sales'));
                $this->applyFinalizedSalesFilter($salesHeatRowsQuery);
                $heatRows = $salesHeatRowsQuery
                    ->select(
                        DB::raw('DAYOFWEEK(created_at) as dow'),
                        DB::raw('HOUR(created_at) as hr'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('dow', 'hr')
                    ->get();

                $dayMap = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun'];
                foreach ($heatRows as $row) {
                    $dayKey = $dayMap[(int) $row->dow] ?? null;
                    if ($dayKey === null) {
                        continue;
                    }
                    $activityHeatmap[$dayKey][(int) $row->hr] = (int) $row->total;
                }
            }

            $totalMgr = $metrics['active_managers'] + $metrics['pending_managers'] + $metrics['suspended_managers'];
            $systemHealth = [
                'company_provisioning_rate' => $metrics['total_tenants'] > 0 ? round(($metrics['active_subs'] / $metrics['total_tenants']) * 100, 1) : 0,
                'manager_verification_rate' => $totalMgr > 0 ? round(($metrics['active_managers'] / $totalMgr) * 100, 1) : 0,
                'payment_success_rate' => $metrics['total_subs'] > 0 ? round(($metrics['paid_subs'] / $metrics['total_subs']) * 100, 1) : 0,
                'user_verification_rate' => $metrics['total_users'] > 0 ? round(($metrics['verified_users'] / $metrics['total_users']) * 100, 1) : 0,
            ];

            // COUNTRY DATA
            $countryData = [];
            if (Schema::hasTable('companies')) {
                $countryData = Company::whereNotNull('country')
                    ->selectRaw('country, COUNT(*) as count')
                    ->groupBy('country')
                    ->pluck('count', 'country')
                    ->toArray();
            }

            // RECENT TENANTS
            $recentTenants = Company::with(['user', 'subscription'])
                ->latest()
                ->limit(5)
                ->get();

            // PLATFORM ACTIVITY
            $platformActivity = collect();
            if ($deployedPaidSubscriptionsQuery) {
                $platformActivity = (clone $deployedPaidSubscriptionsQuery)
                    ->with(['company', 'company.user'])
                    ->orderBy('subscriptions.created_at', 'desc')
                    ->limit(10)
                    ->get();
            }

            $expiringSubscriptions = collect();
            if (Schema::hasTable('subscriptions')) {
                $expiringSubscriptions = $this->platformSubscriptionsQuery()->with(['company', 'user'])
                    ->expiringSoon(7)
                    ->orderBy('end_date', 'asc')
                    ->limit(10)
                    ->get();
            }

            $payoutRecipientGroups = $this->payoutRecipientGroups();

            // VIEW ATTRIBUTES
            $userRole    = $user->role;
            $permissions = ['view_reports', 'manage_users', 'manage_domains', 'super_access', 'verify_managers'];
            $domain      = env('SESSION_DOMAIN', 'Default System');
            $isDeploymentView = false;
            $viewPath = 'SuperAdmin.dashboard';

            return view($viewPath, compact(
                'stats', 'metrics', 'revenueTrends', 'tenantGrowth', 'revenueByPlan', 
                'recentTenants', 'platformActivity', 'planStats', 'countryData', 
                'userRole', 'permissions', 'deployments', 'domain', 
                'deploymentLimit', 'statusDistribution', 'isDeploymentView',
                'chartSeries', 'activityHeatmap', 'systemHealth', 'managerPerformance',
                'visitorAnalytics',
                'activeBranch',
                'expiringSubscriptions',
                'payoutRecipientGroups'
            ));

        } catch (\Exception $e) {
            Log::error('SuperAdmin Dashboard Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            $emptyMetrics = [
                'total_companies' => 0, 'total_tenants' => 0, 'active_subs' => 0, 
                'platform_revenue' => 0, 'owner_subscription_revenue' => 0, 'total_users' => 0, 'pending_setups' => 0, 
                'pending_managers' => 0, 'active_managers' => 0, 'total_stock_val' => 0,
                'paid_subs' => 0, 'total_subs' => 0, 'verified_users' => 0, 'recent_signups' => 0,
                'direct_paid_subs' => 0, 'deployment_paid_subs' => 0,
                'direct_subscription_revenue' => 0, 'deployment_subscription_revenue' => 0,
                'direct_customer_users' => 0, 'deployment_customer_users' => 0,
                'low_stock_items' => 0, 'plan_sales_today' => 0, 'plan_sales_month' => 0,
                'plan_sales_value_month' => 0, 'avg_plan_sale' => 0,
                'item_sales_revenue' => 0, 'item_sales_today_revenue' => 0, 'item_sales_orders' => 0, 'item_sales_units' => 0,
                'expiring_soon_subs' => 0, 'expired_subs' => 0
            ];

            return view('SuperAdmin.dashboard', [
                'stats' => $emptyMetrics,
                'metrics' => $emptyMetrics,
                'deploymentLimit' => 0,
                'revenueTrends' => collect(),
                'tenantGrowth' => collect(),
                'revenueByPlan' => collect(),
                'recentTenants' => collect(),
                'platformActivity' => collect(),
                'deployments' => collect(),
                'planStats' => [],
                'countryData' => [],
                'statusDistribution' => ['labels' => [], 'values' => []],
                'chartSeries' => ['labels' => [], 'revenue' => [], 'orders' => [], 'companies' => [], 'users' => []],
                'visitorAnalytics' => [
                    'cards' => [],
                    'dailyLabels' => [],
                    'dailyVisits' => [],
                    'dailyVisitors' => [],
                    'dailyUsers' => [],
                    'dailyPaid' => [],
                    'periodLabels' => ['Daily', 'Weekly', 'Monthly', 'Yearly'],
                    'periodVisits' => [0, 0, 0, 0],
                    'moduleLabels' => [],
                    'moduleValues' => [],
                    'decisionCards' => [],
                ],
                'managerPerformance' => ['rows' => [], 'max' => 1],
                'activityHeatmap' => [],
                'systemHealth' => [
                    'company_provisioning_rate' => 0,
                    'manager_verification_rate' => 0,
                    'payment_success_rate' => 0,
                    'user_verification_rate' => 0,
                ],
                'userRole' => $user->role,
                'permissions' => [],
                'isDeploymentView' => false,
                'domain' => env('SESSION_DOMAIN', 'Error State'),
                'expiringSubscriptions' => collect(),
                'payoutRecipientGroups' => [],
            ])->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    /**
     * Check if user is a true platform super admin.
     * Plan administrators must not pass this gate.
     */
    private function isSuperAdmin($user): bool
    {
        $role = strtolower($user->role ?? '');

        $validRoles = [
            'super_admin',
            'superadmin',
        ];

        $isValidRole = in_array($role, $validRoles, true);
        $isVictorEmail = ($user->email === 'donvictorlive@gmail.com');

        return $isValidRole || $isVictorEmail;
    }


    public function create()
    {
        // 1. Define roles expected by the @foreach ($roles as ...) in your blade file
        $roles = [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'user' => 'Standard User'
        ];

        // 2. Fetch companies for the dropdown select seen in your stack trace
        $companies = Company::orderBy('name', 'asc')->get();

        // 3. Environment Context
        $domain = env('SESSION_DOMAIN', 'System');

        // 4. Return view with variables
        return view('deployment.users.create', compact('roles', 'companies', 'domain'));
    }


    public function approveManager($id)
    {
        try {
            DB::beginTransaction();

            $manager = $this->resolveDeploymentManager($id);

            if ($this->managerHasStateConflict($manager)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Another active state manager already owns this country and state/county.');
            }
            
            $manager->update([
                'status' => 'active',
                'updated_at' => now()
            ]);

            $user = User::find($manager->user_id);
            if ($user) {
                $user->update([
                    'is_verified' => 1,
                    'verified_at' => now(),
                    'role' => 'state_manager'
                ]);

                $this->ensureManagerHasWorkspace($user);

                DB::afterCommit(function () use ($user) {
                    SystemEventMailer::notifyManagerApproved($user, Auth::user());
                });

                Log::info("State Manager Approved, Workspace Created, & Notification Triggered: {$user->email}");
            }

            DB::commit();
            $displayName = $manager->business_name ?? ($user->name ?? 'Manager');
            return redirect()->back()->with('success', "Manager '{$displayName}' authorized and verified successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manager Approval Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Critical error during approval: ' . $e->getMessage());
        }
    }

    public function rejectManager($id)
    {
        try {
            DB::beginTransaction();
            $manager = $this->resolveDeploymentManager($id);
            
            $manager->update([
                'status' => 'rejected',
                'updated_at' => now()
            ]);

            User::where('id', $manager->user_id)->update(['is_verified' => 0]);

            DB::commit();
            if ($manager->user?->email) {
                SystemEventMailer::sendMessage(
                    [$manager->user->email, config('mail.admin_inbox')],
                    'State Manager Rejected',
                    'State Manager Rejection',
                    'A state manager account has been rejected.',
                    [
                        'State Manager' => $manager->user?->name ?? $manager->user?->email ?? 'N/A',
                        'Email' => $manager->user?->email ?? 'N/A',
                        'Time' => now()->toDateTimeString(),
                    ]
                );
            }
            return redirect()->back()->with('success', "State manager rejected.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function suspendManager($id)
    {
        try {
            DB::beginTransaction();
            $manager = $this->resolveDeploymentManager($id);
            
            $manager->update(['status' => 'suspended']);
            User::where('id', $manager->user_id)->update(['is_verified' => 0]);

            DB::commit();
            if ($manager->user?->email) {
                SystemEventMailer::sendMessage(
                    [$manager->user->email, config('mail.admin_inbox')],
                    'State Manager Suspended',
                    'State Manager Suspension',
                    'A state manager account has been suspended.',
                    [
                        'State Manager' => $manager->user?->name ?? $manager->user?->email ?? 'N/A',
                        'Email' => $manager->user?->email ?? 'N/A',
                        'Time' => now()->toDateTimeString(),
                    ]
                );
            }
            return redirect()->back()->with('success', "State manager suspended successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to suspend.');
        }
    }
    
    public function deleteManager($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $manager = $this->resolveDeploymentManager($id);
                $userId = $manager->user_id;

                $manager->delete();
                User::where('id', $userId)->delete();
            });

            $domain = env('SESSION_DOMAIN', 'the current environment');
            return back()->with('success', "Manager and associated user account have been purged from {$domain}.");

        } catch (\Exception $e) {
            \Log::error("Manager Deletion Failed: " . $e->getMessage());
            return back()->with('error', 'Deletion Failed: ' . $e->getMessage());
        }
    }

    public function reactivateManager($id)
    {
        try {
            DB::beginTransaction();
            $manager = $this->resolveDeploymentManager($id);
            
            $manager->update(['status' => 'active']);
            User::where('id', $manager->user_id)->update(['is_verified' => 1]);

            DB::commit();
            return redirect()->back()->with('success', "Manager reactivated successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to reactivate.');
        }
    }

    /**
     * Route alias used by /superadmin/managers/{id}/activate
     */
    public function activateManager($id)
    {
        return $this->reactivateManager($id);
    }

    public function emailManager(Request $request, $id)
    {
        $manager = $this->resolveDeploymentManager($id)->load('user');

        $recipient = $manager->user?->email;
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $message = 'This state manager does not have a valid email address.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $displayName = $manager->business_name
            ?? $manager->user?->name
            ?? 'State Manager';

        $sent = SystemEventMailer::sendMessage(
            $recipient,
            'SmartProbook Partner Update',
            'State Manager Notification',
            'A new update has been sent to your state manager account.',
            [
                'Manager' => $displayName,
                'Email' => $recipient,
                'Status' => ucfirst((string) ($manager->status ?? 'pending')),
                'Workspace Domain' => (string) env('SESSION_DOMAIN', config('app.url')),
                'Sent By' => Auth::user()?->name ?? Auth::user()?->email ?? 'System',
                'Time' => now()->format('d M Y h:i A'),
            ]
        );

        $message = $sent
            ? "Email sent to {$displayName} successfully."
            : 'Email could not be sent. Please confirm mail settings and try again.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => $sent, 'message' => $message], $sent ? 200 : 500);
        }

        return back()->with($sent ? 'success' : 'error', $message);
    }

    private function ensureManagerHasWorkspace($user) 
    {
        $dmInfo = DeploymentManager::where('user_id', $user->id)->first();
        $prefix = strtolower(preg_replace('/[^A-Za-z0-9]/', '', ($dmInfo->business_name ?? $user->name))) . $user->id;
        $workspaceName = $dmInfo->business_name ?? $user->name;

        $subscription = Subscription::withoutGlobalScope('tenant')
            ->where(function ($query) use ($user, $prefix) {
                $query->where('user_id', $user->id)
                    ->orWhere('domain_prefix', $prefix);
            })
            ->latest('id')
            ->first();

        if (!$subscription) {
            $subscription = new Subscription();
            $subscription->user_id = $user->id;
        }

        $subscription->fill([
            'user_id' => $user->id,
            'plan' => 'Enterprise',
            'subscriber_name' => $user->name,
            'amount' => 0,
            'status' => 'Active',
            'payment_status' => 'paid',
            'domain_prefix' => $subscription->domain_prefix ?: $prefix,
            'start_date' => $subscription->start_date ?: now(),
            'end_date' => now()->addYear(),
        ]);
        $subscription->save();

        $company = Company::withoutGlobalScope('tenant')
            ->where(function ($query) use ($user, $prefix) {
                $query->where('user_id', $user->id)
                    ->orWhere('owner_id', $user->id)
                    ->orWhere('domain_prefix', $prefix)
                    ->orWhere('subdomain', $prefix);
            })
            ->latest('id')
            ->first();

        if (!$company) {
            $company = new Company();
        }

        $company->fill([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'domain_prefix' => $company->domain_prefix ?: $prefix,
            'subdomain' => $company->subdomain ?: $prefix,
            'name' => $workspaceName,
            'company_name' => $company->company_name ?: $workspaceName,
            'status' => 'active',
            'plan' => 'Enterprise',
        ]);
        $company->save();

        if ((int) $subscription->company_id !== (int) $company->id) {
            $subscription->company_id = $company->id;
            $subscription->save();
        }

        if ((int) $user->company_id !== (int) $company->id) {
            $user->company_id = $company->id;
            $user->save();
        }
    }

public function pendingManagers()
{
    $pending = DB::table('deployment_managers')
        ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
        ->select(
            'deployment_managers.*',
            DB::raw("COALESCE(users.email, '') as email"),
            DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
        )
        ->whereIn('deployment_managers.status', ['pending', 'pending_info'])
        ->orderByDesc('deployment_managers.created_at')
        ->get();

    return view('SuperAdmin.managers.pending', compact('pending'));  // ← fixed
}
    public function approvedManagers(Request $request)
    {
        $query = DB::table('deployment_managers')
            ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
            ->select(
                'deployment_managers.*',
                DB::raw("COALESCE(users.email, '') as email"),
                DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
            )
            ->whereIn('deployment_managers.status', ['active', 'suspended']);

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('deployment_managers.business_name', 'like', $search)
                    ->orWhere('deployment_managers.phone', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('deployment_managers.status', trim((string) $request->status));
        }

        $managers = $query->orderBy('deployment_managers.business_name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.managers.approved', compact('managers'));
    }

    public function listManagers(Request $request)
    {
        $query = DB::table('deployment_managers')
            ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
            ->select(
                'deployment_managers.*',
                DB::raw("COALESCE(users.email, '') as email"),
                DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
            );

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('deployment_managers.business_name', 'like', $search)
                    ->orWhere('deployment_managers.phone', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('deployment_managers.status', trim((string) $request->status));
        }

        $managers = $query->orderByDesc('deployment_managers.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.managers.approved', compact('managers'));
    }

    public function suspendedManagers(Request $request)
    {
        $query = DB::table('deployment_managers')
            ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
            ->select(
                'deployment_managers.*',
                DB::raw("COALESCE(users.email, '') as email"),
                DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
            )
            ->where('deployment_managers.status', 'suspended');

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('deployment_managers.business_name', 'like', $search)
                    ->orWhere('deployment_managers.phone', 'like', $search);
            });
        }

        $managers = $query->orderBy('deployment_managers.business_name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.managers.approved', compact('managers'));
    }

    public function transferUsers(Request $request)
    {
        $query = $this->platformSubscriptionsQuery()
            ->leftJoin('users', 'subscriptions.user_id', '=', 'users.id')
            ->leftJoin('companies', 'subscriptions.company_id', '=', 'companies.id')
            ->select(
                'subscriptions.*',
                DB::raw("COALESCE(users.name, subscriptions.subscriber_name, 'N/A') as customer_name"),
                DB::raw("COALESCE(users.email, '') as customer_email"),
                DB::raw("COALESCE(companies.name, companies.company_name, '') as company_name")
            )
            ->whereRaw("LOWER(COALESCE(subscriptions.payment_gateway, '')) = 'bank_transfer'")
            ->where(function ($q) {
                $q->whereNull('subscriptions.deployed_by')
                    ->orWhere('subscriptions.deployed_by', 0);
            });

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('subscriptions.subscriber_name', 'like', $search)
                    ->orWhere('subscriptions.transfer_reference', 'like', $search)
                    ->orWhere('subscriptions.transaction_reference', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $status = strtolower(trim((string) $request->status));
            if ($status === 'pending') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.payment_status, '')) = 'pending_verification'");
            } elseif ($status === 'approved') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.status, '')) = 'active'");
            } elseif ($status === 'rejected') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.payment_status, '')) = 'failed'");
            } elseif ($status === 'suspended') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.status, '')) = 'suspended'");
            }
        }

        $transferUsers = $query->orderByDesc('subscriptions.transfer_submitted_at')
            ->orderByDesc('subscriptions.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.users.transfer', compact('transferUsers'));
    }

    public function approveSubscription($id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
            $subscription->update([
                'status' => 'Active',
                'payment_status' => 'paid',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);
            if ($subscription->user?->email) {
                SystemEventMailer::sendMessage(
                    [$subscription->user->email, config('mail.admin_inbox')],
                    'Subscription Approved',
                    'Subscription Approval',
                    'Your subscription has been approved and activated.',
                    [
                        'Subscriber' => $subscription->user?->name ?? $subscription->user?->email ?? 'N/A',
                        'Plan' => $subscription->plan_name ?? 'N/A',
                        'Amount' => $subscription->amount ?? 'N/A',
                        'Time' => now()->toDateTimeString(),
                    ]
                );
            }
            return redirect()->back()->with('success', 'Subscription approved!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve.');
        }
    }

    public function listUsers(Request $request)
    {
        $category = trim((string) $request->query('category', 'other_users'));

        if ($category === 'registered_businesses') {
            $query = $this->registeredBusinessesQuery();

            if ($request->filled('search')) {
                $search = '%' . trim((string) $request->search) . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', $search)
                        ->orWhere('users.email', 'like', $search)
                        ->orWhere('companies.name', 'like', $search)
                        ->orWhere('companies.company_name', 'like', $search)
                        ->orWhere('companies.domain_prefix', 'like', $search);
                });
            }

            if ($request->filled('status')) {
                $query->where('users.status', $request->status);
            }

            $businesses = $query->orderByDesc(DB::raw('COALESCE(last_paid_at, users.created_at)'))
                ->paginate(20)
                ->withQueryString();

            $baseBusinesses = $this->registeredBusinessesQuery();
            $metrics = [
                'total' => (clone $baseBusinesses)->count(),
                'active' => (clone $baseBusinesses)->where(function ($q) {
                    $q->whereNull('users.status')
                        ->orWhereRaw("LOWER(COALESCE(users.status, '')) IN ('active', 'trial', 'enabled')");
                })->count(),
                'inactive' => (clone $baseBusinesses)->whereRaw("LOWER(COALESCE(users.status, '')) IN ('inactive', 'suspended', 'disabled', 'expired')")->count(),
                'with_domains' => Schema::hasColumn('companies', 'domain_prefix')
                    ? (clone $baseBusinesses)->whereNotNull('companies.domain_prefix')->where('companies.domain_prefix', '!=', '')->count()
                    : 0,
                'revenue' => (float) collect((clone $baseBusinesses)->get(['total_paid']))->sum(fn ($row) => (float) ($row->total_paid ?? 0)),
            ];

            return view('SuperAdmin.users.businesses', compact('businesses', 'metrics'));
        }

        $pageTitle = 'Internal Users';
        $pageSubtitle = 'Platform and staff users who do not belong under state managers, agents, or registered businesses.';
        $createRoute = null;
        $query = $this->otherUsersQuery()->with('company');

        if ($category === 'state_managers') {
            $pageTitle = 'State Managers';
            $pageSubtitle = 'Only approved state managers appear here, and new state managers are created here by super admin.';
            $createRoute = route('super_admin.users.create', ['role' => 'state_manager']);
            $query = $this->stateManagersQuery()->with('company');
        } elseif ($category === 'agents') {
            $pageTitle = 'Agents';
            $pageSubtitle = 'All non-state-manager field users are classified here automatically unless they become registered business owners.';
            $query = $this->agentsQuery()->with('company');
        }

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $search)
                        ->orWhere('company_name', 'like', $search));
            });
        }

        if ($request->filled('status') && Schema::hasColumn('users', 'status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role') && Schema::hasColumn('users', 'role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $base = match ($category) {
            'state_managers' => $this->stateManagersQuery(),
            'agents' => $this->agentsQuery(),
            default => $this->otherUsersQuery(),
        };

        $metrics = [
            'total' => (clone $base)->count(),
            'active' => Schema::hasColumn('users', 'status')
                ? (clone $base)->where('status', 'active')->count()
                : (clone $base)->where('is_verified', 1)->count(),
            'suspended' => Schema::hasColumn('users', 'status')
                ? (clone $base)->where('status', 'suspended')->count()
                : 0,
            'admins' => Schema::hasColumn('users', 'role')
                ? (clone $base)->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), ['admin', 'administrator'])->count()
                : 0,
            'users' => Schema::hasColumn('users', 'role')
                ? (clone $base)->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), ['user', 'staff', 'manager'])->count()
                : (clone $base)->count(),
        ];

        return view('SuperAdmin.users.index', compact('users', 'metrics', 'category', 'pageTitle', 'pageSubtitle', 'createRoute'));
    }

    public function destroyPayout(PlatformPayout $platformPayout)
    {
        $amount = (float) $platformPayout->amount;
        $recipient = $platformPayout->recipient_name;
        $platformPayout->delete();

        return redirect()
            ->route('super_admin.platform_payouts.index')
            ->with('payout_success', 'Payout of ₦' . number_format($amount, 2) . ' to ' . $recipient . ' was reversed successfully.');
    }

    public function suspendUser($id)
    {
        $user = User::findOrFail($id);
        if (Schema::hasColumn('users', 'status')) {
            $user->update(['status' => 'suspended']);
        }
        if (Schema::hasColumn('users', 'is_verified')) {
            $user->update(['is_verified' => 0]);
        }

        if ($user->email) {
            SystemEventMailer::sendMessage(
                [$user->email, config('mail.admin_inbox')],
                'Account Suspended',
                'Account Suspension',
                'Your SmartProbook account has been suspended.',
                [
                    'User' => $user->name ?? $user->email,
                    'Email' => $user->email,
                    'Time' => now()->toDateTimeString(),
                ]
            );
        }

        return back()->with('success', 'User suspended successfully.');
    }

    public function activateUser($id)
    {
        $user = User::findOrFail($id);
        if (Schema::hasColumn('users', 'status')) {
            $user->update(['status' => 'active']);
        }
        if (Schema::hasColumn('users', 'is_verified')) {
            $user->update(['is_verified' => 1]);
        }

        if (strtolower((string) ($user->role ?? '')) === 'agent'
            && Schema::hasColumn('users', 'state_manager_id')
            && empty($user->state_manager_id)) {
            $manager = $this->findStateManagerForLocation($user->country ?? null, $user->state_region ?? null);
            if ($manager?->user_id) {
                $user->update(['state_manager_id' => $manager->user_id]);
            }
        }

        return back()->with('success', 'User activated successfully.');
    }

    private function managerHasStateConflict(DeploymentManager $manager): bool
    {
        if (!Schema::hasColumn('deployment_managers', 'country')
            || !Schema::hasColumn('deployment_managers', 'state_region')) {
            return false;
        }

        $country = strtolower(trim((string) ($manager->country ?? '')));
        $state = strtolower(trim((string) ($manager->state_region ?? '')));

        if ($country === '' || $state === '') {
            return false;
        }

        return DeploymentManager::withoutGlobalScopes()
            ->whereKeyNot($manager->id)
            ->whereRaw('LOWER(country) = ?', [$country])
            ->whereRaw('LOWER(state_region) = ?', [$state])
            ->whereRaw("LOWER(COALESCE(status, '')) = ?", ['active'])
            ->exists();
    }

    private function findStateManagerForLocation(?string $country, ?string $stateRegion): ?DeploymentManager
    {
        if (!Schema::hasColumn('deployment_managers', 'country')
            || !Schema::hasColumn('deployment_managers', 'state_region')) {
            return null;
        }

        $country = strtolower(trim((string) $country));
        $stateRegion = strtolower(trim((string) $stateRegion));

        if ($country === '' || $stateRegion === '') {
            return null;
        }

        return DeploymentManager::withoutGlobalScopes()
            ->whereRaw('LOWER(country) = ?', [$country])
            ->whereRaw('LOWER(state_region) = ?', [$stateRegion])
            ->whereRaw("LOWER(COALESCE(status, '')) = ?", ['active'])
            ->first();
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function emailUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $payload = $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        $recipient = $user->email;
        if (!filter_var((string) $recipient, FILTER_VALIDATE_EMAIL)) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'User has no valid email.'], 422)
                : back()->with('error', 'User has no valid email.');
        }

        $subject = $payload['subject'] ?: 'SmartProbook Update';
        $message = $payload['message'] ?: 'An update has been sent regarding your account.';

        $sent = SystemEventMailer::sendMessage(
            $recipient,
            $subject,
            'User Notification',
            $message,
            [
                'User' => $user->name ?? $user->email,
                'Email' => $user->email,
                'Sent By' => Auth::user()?->name ?? Auth::user()?->email ?? 'System',
                'Time' => now()->format('d M Y h:i A'),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => $sent, 'message' => $sent ? 'Email sent.' : 'Email failed.'], $sent ? 200 : 500);
        }

        return back()->with($sent ? 'success' : 'error', $sent ? 'Email sent.' : 'Email failed.');
    }

    public function exportAnalytics(Request $request)
    {
        $type = $request->get('type', 'revenue');
        $format = $request->get('format', 'csv');

        try {
            $data = match ($type) {
                'revenue' => $this->platformSubscriptionsQuery()->where('payment_status', 'paid')->with('company')->get()->map(fn($s) => [
                    'ID' => $s->id,
                    'Company' => $s->company->name ?? $s->company->company_name ?? 'N/A',
                    'Amount' => $s->amount,
                    'Source' => (!empty($s->deployed_by) || !empty($s->company?->deployed_by)) ? 'State Manager' : 'Direct',
                    'Date' => $s->created_at,
                ]),
                'managers' => DB::table('deployment_managers')->join('users', 'deployment_managers.user_id', '=', 'users.id')->select('users.name', 'users.email', 'deployment_managers.status')->get(),
                'tenants' => Company::with(['subscription'])->get()->map(fn($c) => [
                    'Company' => $c->name ?? $c->company_name ?? 'N/A',
                    'Plan' => $c->subscription->plan_name ?? $c->plan ?? 'N/A',
                    'Source' => !empty($c->deployed_by) ? 'State Manager' : 'Direct',
                    'Status' => $c->status,
                    'Joined' => $c->created_at,
                ]),
                default => throw new \Exception('Invalid type')
            };

            return $this->generateExport($data, "{$type}_report", $format);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function generateExport($data, $filename, $format)
    {
        $filename = $filename . '_' . date('Y-m-d') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];
        
        return response()->stream(function() use ($data) {
            $file = fopen('php://output', 'w');
            if ($data->isNotEmpty()) fputcsv($file, array_keys((array)$data->first()));
            foreach ($data as $row) fputcsv($file, (array)$row);
            fclose($file);
        }, 200, $headers);
    }

    public function exportStats()
    {
        return $this->exportAnalytics(request()->merge([
            'type' => 'revenue',
            'format' => 'csv',
        ]));
    }

    public function storePayout(Request $request)
    {
        if (!Schema::hasTable('platform_payouts')) {
            return redirect()->back()->with('payout_error', 'Platform payouts table not found. Please run: php artisan migrate --force on the server.');
        }

        $validated = $request->validate([
            'recipient_type' => 'required|in:state_manager,agent,app_user,external',
            'recipient_user_id' => 'nullable|integer|exists:users,id',
            'recipient_name' => 'nullable|string|max:255',
            'amount'         => 'required|numeric|min:0.01',
            'payout_type'    => 'required|in:dividend,commission,salary,refund,other',
            'description'    => 'nullable|string|max:500',
            'notes'          => 'nullable|string|max:1000',
            'paid_at'        => 'nullable|date',
        ]);

        try {
            $recipient = $this->resolvePayoutRecipient(
                $validated['recipient_type'],
                isset($validated['recipient_user_id']) ? (int) $validated['recipient_user_id'] : null,
                $validated['recipient_name'] ?? null
            );

            $payload = [
                'recipient_name' => $recipient['name'],
                'amount'         => $validated['amount'],
                'payout_type'    => $validated['payout_type'],
                'description'    => $validated['description'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'recorded_by'    => Auth::id(),
                'paid_at'        => $validated['paid_at'] ?? now(),
            ];

            if (Schema::hasColumn('platform_payouts', 'recipient_type')) {
                $payload['recipient_type'] = $validated['recipient_type'];
            }

            if (Schema::hasColumn('platform_payouts', 'recipient_user_id')) {
                $payload['recipient_user_id'] = $recipient['user_id'];
            }

            PlatformPayout::create($payload);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('payout_error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('payout_error', 'Failed to save payout: ' . $e->getMessage());
        }

        return redirect()->back()->with('payout_success', 'Payout of ₦' . number_format($validated['amount'], 2) . ' to ' . $recipient['name'] . ' recorded successfully.');
    }

    public function payoutHistory(\Illuminate\Http\Request $request)
    {
        $from      = $request->filled('from')      ? \Carbon\Carbon::parse($request->from)->startOfDay()      : null;
        $to        = $request->filled('to')        ? \Carbon\Carbon::parse($request->to)->endOfDay()          : null;
        $type      = $request->input('payout_type');
        $recipientType = $request->input('recipient_type');
        $recipient = $request->input('recipient');

        $query = PlatformPayout::query()
            ->with(['recipient', 'recorder'])
            ->when($from,      fn ($q) => $q->where('paid_at', '>=', $from))
            ->when($to,        fn ($q) => $q->where('paid_at', '<=', $to))
            ->when($type,      fn ($q) => $q->where('payout_type', $type))
            ->when($recipientType && Schema::hasColumn('platform_payouts', 'recipient_type'), fn ($q) => $q->where('recipient_type', $recipientType))
            ->when($recipient, fn ($q) => $q->where('recipient_name', 'like', '%' . $recipient . '%'))
            ->latest();

        $totalPayouts = (float) (clone $query)->sum('amount');
        $payouts      = $query->paginate(20)->withQueryString();
        $payoutRecipientGroups = $this->payoutRecipientGroups();

        return view('SuperAdmin.payout-history', compact('payouts', 'totalPayouts', 'from', 'to', 'type', 'recipientType', 'recipient', 'payoutRecipientGroups'));
    }
}
