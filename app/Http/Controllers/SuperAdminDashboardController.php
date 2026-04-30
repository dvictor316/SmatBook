<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Product; 
use App\Models\Sale;
use App\Models\DeploymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Support\SystemEventMailer;
use App\Support\InventoryQuantity;

class SuperAdminDashboardController extends Controller
{
    private function resolveDeploymentManager(string|int $id): DeploymentManager
    {
        return DeploymentManager::withoutGlobalScopes()
            ->where('id', $id)
            ->orWhere('user_id', $id)
            ->firstOrFail();
    }

    private function customerUsersQuery()
    {
        $query = User::query();

        if (Schema::hasColumn('users', 'role')) {
            $query->whereNotIn(
                DB::raw("LOWER(COALESCE(role, ''))"),
                ['super_admin', 'superadmin', 'deployment_manager']
            );
        }

        return $query;
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
            Subscription::query()
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
                ? Subscription::query()->where(function ($query) use ($paidPaymentStatuses) {
                    $query->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), $paidPaymentStatuses);

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
                ? ((float) ($salesBranchScope(DB::table('sales'))->sum('total') ?? 0))
                : 0.0;
            $subscriptionRevenue = $paidSubscriptionsQuery
                ? ((float) ((clone $paidSubscriptionsQuery)->sum('amount') ?? 0))
                : 0.0;
            $paidSubscriptionsCount = $paidSubscriptionsQuery
                ? (int) ((clone $paidSubscriptionsQuery)->count() ?? 0)
                : 0;
            $platformRevenue = !empty($activeBranch['id']) || !empty($activeBranch['name'])
                ? $salesRevenue
                : ($subscriptionRevenue > 0 ? $subscriptionRevenue : $salesRevenue);

            $itemSalesTodayRevenue = 0.0;
            $itemSalesOrders = 0;
            $itemSalesUnits = 0.0;
            if (Schema::hasTable('sales')) {
                $itemSalesTodayRevenue = (float) ($salesBranchScope(DB::table('sales'))
                    ->whereDate('created_at', today())
                    ->sum('total') ?? 0);
                $itemSalesOrders = (int) ($salesBranchScope(DB::table('sales'))
                    ->count() ?? 0);
            }
            if (Schema::hasTable('sale_items') && Schema::hasTable('products') && Schema::hasTable('sales')) {
                $itemSalesUnitsQuery = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products as sale_products', 'sale_items.product_id', '=', 'sale_products.id')
                    ->selectRaw('COALESCE(SUM(' . InventoryQuantity::saleStockUnitsExpression('sale_items', 'sale_products') . '), 0) as total_units');

                $salesBranchScope($itemSalesUnitsQuery, 'sales');
                $itemSalesUnits = (float) ($itemSalesUnitsQuery->value('total_units') ?? 0);
            }

            $activeSubs = Schema::hasTable('subscriptions')
                ? Subscription::query()
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
            if ($paidSubscriptionsQuery) {
                $deploymentSubscriptionsQuery = (clone $paidSubscriptionsQuery)
                    ->leftJoin('companies as source_companies', 'subscriptions.company_id', '=', 'source_companies.id')
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

                $deploymentSubscriptionRevenue = (float) ($deploymentSubscriptionsQuery->sum('subscriptions.amount') ?? 0);
                $deploymentPaidSubs = (int) ($deploymentSubscriptionsQuery->distinct()->count('subscriptions.id') ?? 0);
            }

            $directSubscriptionRevenue = max(0, $subscriptionRevenue - $deploymentSubscriptionRevenue);
            $directPaidSubs = max(0, $paidSubscriptionsCount - $deploymentPaidSubs);

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

            $planSalesBaseQuery = Schema::hasTable('subscriptions') ? clone $paidSubscriptionsQuery : null;
            $planSalesToday = $planSalesBaseQuery ? (clone $planSalesBaseQuery)->whereDate('created_at', today())->count() : 0;
            $planSalesMonth = $planSalesBaseQuery ? (clone $planSalesBaseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count() : 0;
            $planSalesValueMonth = $planSalesBaseQuery ? (float) ((clone $planSalesBaseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount') ?? 0) : 0;
            $avgPlanSale = $planSalesMonth > 0 ? ($planSalesValueMonth / $planSalesMonth) : 0;

            // METRICS
            $metrics = [
                'total_companies'  => $totalCompanies, 
                'total_tenants'    => $activeCompanies > 0 ? $activeCompanies : $totalCompanies,
                'total_users'      => $totalCustomerUsers,
                'registered_user_revenue' => $subscriptionRevenue,
                'verified_users'   => Schema::hasColumn('users', 'is_verified')
                                      ? (clone $customerUsersBaseQuery)->where('is_verified', 1)->count()
                                      : 0,
                'active_subs'      => $activeSubs > 0 ? $activeSubs : $activeCompanies,
                'paid_subs'        => $paidSubscriptionsCount,
                'direct_paid_subs' => $directPaidSubs,
                'deployment_paid_subs' => $deploymentPaidSubs,
                'total_subs'       => Schema::hasTable('subscriptions')
                                      ? Subscription::count()
                                      : 0,
                'platform_revenue' => $platformRevenue,
                'owner_subscription_revenue' => $subscriptionRevenue,
                'direct_subscription_revenue' => $directSubscriptionRevenue,
                'deployment_subscription_revenue' => $deploymentSubscriptionRevenue,
                'pending_setups'   => Schema::hasTable('subscriptions')
                                      ? Subscription::query()->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), $pendingSubscriptionStatuses)->count()
                                      : 0,
                'pending_managers' => Schema::hasTable('deployment_managers') 
                                      ? DB::table('deployment_managers')->whereIn('status', ['pending', 'pending_info'])->count() 
                                      : 0,
                'active_managers'  => Schema::hasTable('deployment_managers') 
                                      ? DB::table('deployment_managers')->where('status', 'active')->count() 
                                      : 0,
                'suspended_managers'  => Schema::hasTable('deployment_managers') 
                                      ? DB::table('deployment_managers')->where('status', 'suspended')->count() 
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
                                      ? Subscription::expiringSoon(7)->count()
                                      : 0,
                'expired_subs'       => Schema::hasTable('subscriptions')
                                      ? Subscription::whereRaw("LOWER(COALESCE(status, '')) = 'expired'")->count()
                                      : 0,
            ];

            $stats = $metrics;

            // REVENUE TRENDS
            $revenueTrends = collect();
            if ($paidSubscriptionsQuery) {
                $revenueTrends = (clone $paidSubscriptionsQuery)
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'), 
                        DB::raw('SUM(amount) as total'), 
                        DB::raw('COUNT(*) as subscriptions_count'),
                        DB::raw('MONTH(created_at) as month_num')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }
            if ($revenueTrends->isEmpty() && Schema::hasTable('sales')) {
                $revenueTrends = $salesBranchScope(DB::table('sales'))
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
                $planStats = Subscription::selectRaw("{$planExpr} as plan_label, COUNT(*) as total")
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
                        DB::raw('SUM(amount) as revenue')
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
            if (Schema::hasTable('deployment_managers')) {
                $deployments = DB::table('deployment_managers')
                    ->join('users', 'deployment_managers.user_id', '=', 'users.id')
                    ->select('deployment_managers.*', 'users.email', 'users.name as manager_name', 'users.is_verified')
                    ->latest('deployment_managers.created_at')
                    ->get();
            }

            // TOP DEPLOYMENT MANAGERS (for compact authorization progress bars)
            $managerPerformance = ['rows' => [], 'max' => 1];
            if (Schema::hasTable('deployment_managers')) {
                $managerRows = collect();
                if (Schema::hasTable('deployment_commissions')) {
                    $managerRows = DB::table('deployment_managers')
                        ->leftJoin('deployment_commissions', 'deployment_managers.user_id', '=', 'deployment_commissions.manager_id')
                        ->select(
                            'deployment_managers.id',
                            'deployment_managers.business_name',
                            'deployment_managers.status',
                            DB::raw("COALESCE(SUM(CASE WHEN deployment_commissions.status IN ('pending','paid') THEN deployment_commissions.amount ELSE 0 END), 0) as perf_score")
                        )
                        ->groupBy('deployment_managers.id', 'deployment_managers.business_name', 'deployment_managers.status')
                        ->orderByDesc('perf_score')
                        ->limit(3)
                        ->get();
                }

                // Fallback when commission rows are missing: rank by manager status weight.
                if ($managerRows->isEmpty()) {
                    $statusScore = ['active' => 3, 'pending' => 2, 'suspended' => 1];
                    $managerRows = DB::table('deployment_managers')
                        ->select('id', 'business_name', 'status')
                        ->latest('created_at')
                        ->limit(3)
                        ->get()
                        ->map(function ($m) use ($statusScore) {
                            return (object) [
                                'id' => $m->id,
                                'business_name' => $m->business_name,
                                'status' => $m->status,
                                'perf_score' => (float) ($statusScore[strtolower((string) ($m->status ?? ''))] ?? 1.0),
                            ];
                        })
                        ->sortByDesc('perf_score')
                        ->take(3)
                        ->values();
                }

                $rows = $managerRows->map(function ($row) {
                    $name = trim((string) ($row->business_name ?? ''));
                    if ($name === '') {
                        $name = 'Manager #' . ($row->id ?? 'N/A');
                    }
                    return [
                        'name' => $name,
                        'score' => (float) ($row->perf_score ?? 0),
                        'status' => (string) ($row->status ?? 'pending'),
                    ];
                })->values();

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

            $monthlyRevenueMap = [];
            $monthlyOrdersMap = [];
            foreach ($revenueTrends as $row) {
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

            $activityHeatmap = [];
            $forceBranchHeatmap = !empty($activeBranch['id']) || !empty($activeBranch['name']);
            if (!$forceBranchHeatmap && Schema::hasTable('subscriptions')) {
                $heatRows = Subscription::select(
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
                $heatRows = $salesBranchScope(DB::table('sales'))
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
            if ($paidSubscriptionsQuery) {
                $platformActivity = (clone $paidSubscriptionsQuery)
                    ->with(['company', 'company.user'])
                    ->latest()
                    ->limit(10)
                    ->get();
            }
            if ($platformActivity->isEmpty() && Schema::hasTable('sales')) {
                $platformActivity = $salesBranchScope(Sale::query())
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($sale) {
                        return (object) [
                            'id' => $sale->id,
                            'subscriber_name' => $sale->customer_name ?: ('Order ' . ($sale->order_number ?? $sale->id)),
                            'amount' => $sale->total ?? 0,
                            'created_at' => $sale->created_at,
                            'company' => null,
                        ];
                    });
            }

            $expiringSubscriptions = collect();
            if (Schema::hasTable('subscriptions')) {
                $expiringSubscriptions = Subscription::with(['company', 'user'])
                    ->expiringSoon(7)
                    ->orderBy('end_date', 'asc')
                    ->limit(10)
                    ->get();
            }

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
                'activeBranch',
                'expiringSubscriptions'
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
            
            $manager->update([
                'status' => 'active',
                'updated_at' => now()
            ]);

            $user = User::find($manager->user_id);
            if ($user) {
                $user->update([
                    'is_verified' => 1,
                    'verified_at' => now(),
                    'role' => 'deployment_manager'
                ]);

                $this->ensureManagerHasWorkspace($user);

                DB::afterCommit(function () use ($user) {
                    SystemEventMailer::notifyManagerApproved($user, Auth::user());
                });

                Log::info("Deployment Manager Approved, Workspace Created, & Notification Triggered: {$user->email}");
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
                    'Deployment Manager Rejected',
                    'Manager Rejection',
                    'A deployment manager account has been rejected.',
                    [
                        'Manager' => $manager->user?->name ?? $manager->user?->email ?? 'N/A',
                        'Email' => $manager->user?->email ?? 'N/A',
                        'Time' => now()->toDateTimeString(),
                    ]
                );
            }
            return redirect()->back()->with('success', "Manager rejected.");
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
                    'Deployment Manager Suspended',
                    'Manager Suspension',
                    'A deployment manager account has been suspended.',
                    [
                        'Manager' => $manager->user?->name ?? $manager->user?->email ?? 'N/A',
                        'Email' => $manager->user?->email ?? 'N/A',
                        'Time' => now()->toDateTimeString(),
                    ]
                );
            }
            return redirect()->back()->with('success', "Manager suspended successfully.");
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
            $message = 'This deployment manager does not have a valid email address.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $displayName = $manager->business_name
            ?? $manager->user?->name
            ?? 'Deployment Manager';

        $sent = SystemEventMailer::sendMessage(
            $recipient,
            'SmartProbook Partner Update',
            'Deployment Manager Notification',
            'A new update has been sent to your deployment manager account.',
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
        $hasCompany = Company::where('user_id', $user->id)->exists();
        if (!$hasCompany) {
            $dmInfo = DeploymentManager::where('user_id', $user->id)->first();
            $prefix = strtolower(preg_replace('/[^A-Za-z0-9]/', '', ($dmInfo->business_name ?? $user->name))) . $user->id;

            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan' => 'Enterprise',
                    'subscriber_name' => $user->name,
                    'amount' => 0,
                    'status' => 'Active',
                    'payment_status' => 'paid',
                    'domain_prefix' => $prefix,
                    'start_date' => now(),
                    'end_date' => now()->addYear()
                ]
            );

            Company::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'owner_id' => $user->id,
                    'subdomain' => $prefix,
                    'name' => ($dmInfo->business_name ?? $user->name),
                    'status' => 'active',
                    'plan' => 'Enterprise'
                ]
            );
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
        $query = Subscription::query()
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
        $query = User::query()->with('company');

        if (Schema::hasColumn('users', 'role')) {
            $query->whereNotIn('role', ['super_admin', 'superadmin', 'deployment_manager']);
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

        $base = User::query();
        if (Schema::hasColumn('users', 'role')) {
            $base->whereNotIn('role', ['super_admin', 'superadmin', 'deployment_manager']);
        }

        $metrics = [
            'total' => (clone $base)->count(),
            'active' => Schema::hasColumn('users', 'status')
                ? (clone $base)->where('status', 'active')->count()
                : (clone $base)->where('is_verified', 1)->count(),
            'suspended' => Schema::hasColumn('users', 'status')
                ? (clone $base)->where('status', 'suspended')->count()
                : 0,
            'admins' => Schema::hasColumn('users', 'role')
                ? (clone $base)->whereIn('role', ['admin', 'administrator'])->count()
                : 0,
            'users' => Schema::hasColumn('users', 'role')
                ? (clone $base)->whereIn('role', ['user', 'staff', 'manager'])->count()
                : (clone $base)->count(),
        ];

        return view('SuperAdmin.users.index', compact('users', 'metrics'));
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

        return back()->with('success', 'User activated successfully.');
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
                'revenue' => Subscription::where('payment_status', 'paid')->with('company')->get()->map(fn($s) => [
                    'ID' => $s->id,
                    'Company' => $s->company->name ?? $s->company->company_name ?? 'N/A',
                    'Amount' => $s->amount,
                    'Source' => (!empty($s->deployed_by) || !empty($s->company?->deployed_by)) ? 'Deployment Manager' : 'Direct',
                    'Date' => $s->created_at,
                ]),
                'managers' => DB::table('deployment_managers')->join('users', 'deployment_managers.user_id', '=', 'users.id')->select('users.name', 'users.email', 'deployment_managers.status')->get(),
                'tenants' => Company::with(['subscription'])->get()->map(fn($c) => [
                    'Company' => $c->name ?? $c->company_name ?? 'N/A',
                    'Plan' => $c->subscription->plan_name ?? $c->plan ?? 'N/A',
                    'Source' => !empty($c->deployed_by) ? 'Deployment Manager' : 'Direct',
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
}
