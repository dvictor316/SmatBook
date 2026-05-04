<?php

namespace App\Http\Controllers;

use App\Models\{Product, Expense, Sale, Company, Subscription, User, Plan, ProductBranchStock};
use App\Support\InventoryQuantity;
use Illuminate\Support\Facades\{DB, Auth, Schema, Cache, Log};
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function switchToBusinessWorkspace(Request $request)
    {
        $request->session()->put('workspace_context', 'business');

        return $this->index($request);
    }

    public function switchToPlatformWorkspace(Request $request)
    {
        $request->session()->put('workspace_context', 'platform');

        return redirect()->route('super_admin.dashboard')
            ->with('success', 'Partnership workspace is now active.');
    }

    public function businessDashboard(Request $request)
    {
        $request->session()->put('workspace_context', 'business');

        return $this->index($request);
    }

    /**
     * Main Dashboard View (Multi-Tenant & Subdomain Optimized)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentHost = $request->getHost();
        $mainDomain = ltrim(config('app.domain', 'smartprobook.com'), '.');
        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $centralHosts = [
            $mainDomain,
            'www.' . $mainDomain,
            'localhost',
            '127.0.0.1',
        ];

        if ($appUrlHost) {
            $centralHosts[] = $appUrlHost;
            $centralHosts[] = preg_replace('/^www\./i', '', $appUrlHost);
            $centralHosts[] = 'www.' . preg_replace('/^www\./i', '', $appUrlHost);
        }

        $centralHosts = collect($centralHosts)
            ->filter(fn ($host) => filled($host))
            ->map(fn ($host) => Str::lower((string) $host))
            ->unique()
            ->values()
            ->all();

        $userCompany = null;
        if (!empty($user?->company_id)) {
            $userCompany = Company::find($user->company_id);
        }
        if ($userCompany && !empty($userCompany->domain_prefix)) {
            $expectedSubdomain = Str::lower($userCompany->domain_prefix);
            $currentSubdomain = null;
            if (!in_array(Str::lower($currentHost), $centralHosts, true)) {
                $currentSubdomain = explode('.', $currentHost)[0];
            }
            if ($currentSubdomain && $currentSubdomain !== $expectedSubdomain) {
                $hostParts = explode('.', $currentHost);
                $hostParts[0] = $expectedSubdomain;
                $correctHost = implode('.', $hostParts);
                $scheme = $request->getScheme();
                $uri = $scheme . '://' . $correctHost . $request->getRequestUri();
                return redirect()->to($uri);
            }
        }

        $currentSubscription = Subscription::resolveCurrentForUser($user);
        $hasBusinessWorkspace = (int) ($user->company_id ?? 0) > 0
            || (int) ($currentSubscription?->company_id ?? 0) > 0
            || (int) session('current_tenant_id', 0) > 0;
        $canUsePlatformWorkspace = in_array((string) ($user->role ?? ''), ['superadmin'], true);
        $defaultWorkspaceContext = $hasBusinessWorkspace ? 'business' : 'platform';
        $workspaceContext = (string) $request->session()->get('workspace_context', $defaultWorkspaceContext);

        if (!$canUsePlatformWorkspace) {
            $workspaceContext = 'business';
            $request->session()->put('workspace_context', 'business');
        }

        $isBusinessWorkspace = $workspaceContext === 'business';
        $activeBranch = $isBusinessWorkspace ? $this->activeBranchContext() : ['id' => null, 'name' => null];
        // 1. TENANT IDENTIFICATION VIA SUBDOMAIN
        $subdomain = in_array(Str::lower($currentHost), $centralHosts, true) ? null : explode('.', $currentHost)[0];

        if ($subdomain) {
            $subscription = Subscription::where('domain_prefix', $subdomain)->first();

            $companyQuery = Company::query()
                ->when(
                    $subscription?->company_id,
                    fn ($q) => $q->where('id', $subscription->company_id),
                    fn ($q) => $q->where(function ($fallback) use ($subdomain, $user) {
                        $tenantId = (int) ($user->company_id ?? session('current_tenant_id') ?? 0);

                        if ($tenantId > 0) {
                            $fallback->where('id', $tenantId);
                        }

                        $fallback->orWhere('user_id', $user->id)
                            ->orWhere('owner_id', $user->id)
                            ->orWhere('subdomain', $subdomain)
                            ->orWhere('domain_prefix', $subdomain);
                    })
                );

            if ($subscription) {
                $companyQuery->orWhere(function ($q) use ($subdomain, $subscription) {
                    $q->where('user_id', $subscription->user_id)
                      ->where(function ($match) use ($subdomain) {
                          $match->where('subdomain', $subdomain)
                                ->orWhere('domain_prefix', $subdomain);
                      });
                });
            }

            $company = $companyQuery->first();
        } else {
            $tenantId = (int) ($user->company_id ?? session('current_tenant_id') ?? 0);
            $company = Company::where('id', $tenantId)
                ->orWhere('user_id', $user->id)
                ->orWhere('owner_id', $user->id)
                ->first();
        }

        if (!$company && !empty($currentSubscription?->company_id)) {
            $company = Company::query()->find((int) $currentSubscription->company_id);
        }

        if ($subdomain && $company) {
            $allowedUserIds = $this->companyUserIds($company);
            if (!in_array((int) $user->id, $allowedUserIds, true) && !in_array($user->role, ['superadmin', 'admin'])) {
                abort(403, 'Unauthorized workspace.');
            }
        }

        // Redirect if setup isn't finished (Handshake check)
        if ($subdomain && !$company) {
            return redirect()->route('saas.setup')->with('info', 'Handshake incomplete. Please set your URL.');
        }
        if (!$company && !in_array($user->role, ['superadmin', 'admin'])) {
            return redirect()->route('saas.setup')->with('info', 'Handshake incomplete. Please set your URL.');
        }

        // 2. PERMISSION LOGIC (Cached for performance)
        $userRole = $user->role; 
        $permissions = Cache::remember("perms_{$userRole}", 3600, function() use ($userRole) {
            if (!Schema::hasTable('permissions')) return [];
            return DB::table('permissions')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
                ->where('roles.name', $userRole)
                ->pluck('permissions.name')
                ->toArray();
        });

        $plan = Plan::normalizeTier(
            $currentSubscription?->plan_name
            ?? $currentSubscription?->plan
            ?? ($company->plan ?? (($userRole === 'superadmin' || $user->email === 'donvictorlive@gmail.com') ? 'enterprise' : 'basic'))
        );
        $dashboardBranchLabel = $activeBranch['name'] ?? null;

        if ($isBusinessWorkspace && $plan === 'enterprise' && (!empty($activeBranch['id']) || !empty($activeBranch['name']))) {
            $activeBranch = ['id' => null, 'name' => null];
            $dashboardBranchLabel = 'All Branches';
        }

        // 3. CACHED ANALYTICS (Scoped to Company)
        $resolvedCompanyScopeId = (int) (($company?->id ?? null) ?? $currentSubscription?->company_id ?? $user->company_id ?? session('current_tenant_id') ?? 0);
        $branchCacheKey = $isBusinessWorkspace ? ('_branch_' . ($activeBranch['id'] ?? 'all')) : '_global';
        $cacheKey = 'metrics_co_' . ($resolvedCompanyScopeId > 0 ? $resolvedCompanyScopeId : ('user_' . $user->id)) . $branchCacheKey;
        $metrics = Cache::remember($cacheKey, 300, function() use ($company, $activeBranch) {
            $todayRevenue = $this->getTodayRevenue($company, $activeBranch);
            $totalSales = $this->getTotalSales($company, $activeBranch);
            $totalProfit = $this->getTotalProfit($company, $activeBranch);
            $totalExpenses = $this->getTotalExpenses($company, $activeBranch);
            $activeStock = $this->getTotalStock($company, $activeBranch);
            $inventoryValue = $this->getInventoryValue($company, $activeBranch);
            $totalInvoices = $this->getTotalInvoices($company, $activeBranch);
            $activeCustomers = $this->getActiveCustomers($company);
            $lowStockCount = $this->getLowStockCount($company, $activeBranch);
            $pendingBalance = $this->getPendingBalance($company, $activeBranch);
            $itemsSold = $this->getItemsSold($company, $activeBranch);
            $todayOrders = $this->getTodayOrders($company, $activeBranch);
            $paymentStatus = $this->getPaymentStatusCounts($company, $activeBranch);
            $currentMonthSales = $this->getCurrentMonthSales($company, $activeBranch);
            $previousMonthSales = $this->getPreviousMonthSales($company, $activeBranch);
            $avgOrderValue = $totalInvoices > 0 ? ($totalSales / $totalInvoices) : 0;
            $profitMargin = $totalSales > 0 ? (($totalProfit / $totalSales) * 100) : 0;
            $expenseRatio = $totalSales > 0 ? (($totalExpenses / $totalSales) * 100) : 0;
            $revenueProgress = $this->getRevenueProgress($todayRevenue, $totalSales);
            $salesGrowthRate = $previousMonthSales > 0
                ? (($currentMonthSales - $previousMonthSales) / $previousMonthSales) * 100
                : ($currentMonthSales > 0 ? 100 : 0);

            return [
                'todayRevenue'    => $todayRevenue,
                'totalSales'      => $totalSales,
                'totalProfit'     => $totalProfit,
                'netProfit'       => $totalProfit, // alias used by pro dashboard
                'totalExpenses'   => $totalExpenses,
                'activeStock'     => $activeStock,
                'inventoryValue'  => $inventoryValue,
                'totalInvoices'   => $totalInvoices, // alias used by basic dashboard
                'activeCustomers' => $activeCustomers, // alias used by basic dashboard
                'lowStockCount'   => $lowStockCount,
                'pendingBalance'  => $pendingBalance,
                'itemsSoldToday'  => $itemsSold,
                'totalOrders'     => $todayOrders,
                'avgOrderValue'   => $avgOrderValue,
                'profitMargin'    => $profitMargin,
                'expenseRatio'    => $expenseRatio,
                'currentMonthSales' => $currentMonthSales,
                'previousMonthSales' => $previousMonthSales,
                'salesGrowthRate' => $salesGrowthRate,
                'paidInvoices'    => $paymentStatus['paid'],
                'partialInvoices' => $paymentStatus['partial'],
                'unpaidInvoices'  => $paymentStatus['unpaid'],
                'revenueProgress' => $revenueProgress,
                'countryHeatMap'  => $this->getHeatMapData($company),
            ];
        });

        $shouldFallbackToTenantWide = $isBusinessWorkspace
            && !empty($activeBranch['id'])
            && $this->shouldUseTenantWideDashboardFallback($metrics);

        if ($shouldFallbackToTenantWide) {
            $activeBranch = ['id' => null, 'name' => null];
            $dashboardBranchLabel = 'All Branches';
            $fallbackCacheKey = 'metrics_co_' . ($resolvedCompanyScopeId > 0 ? $resolvedCompanyScopeId : ('user_' . $user->id)) . '_branch_all';
            $metrics = Cache::remember($fallbackCacheKey, 300, function() use ($company) {
                $todayRevenue = $this->getTodayRevenue($company, null);
                $totalSales = $this->getTotalSales($company, null);
                $totalProfit = $this->getTotalProfit($company, null);
                $totalExpenses = $this->getTotalExpenses($company, null);
                $activeStock = $this->getTotalStock($company, null);
                $inventoryValue = $this->getInventoryValue($company, null);
                $totalInvoices = $this->getTotalInvoices($company, null);
                $activeCustomers = $this->getActiveCustomers($company);
                $lowStockCount = $this->getLowStockCount($company, null);
                $pendingBalance = $this->getPendingBalance($company, null);
                $itemsSold = $this->getItemsSold($company, null);
                $todayOrders = $this->getTodayOrders($company, null);
                $paymentStatus = $this->getPaymentStatusCounts($company, null);
                $currentMonthSales = $this->getCurrentMonthSales($company, null);
                $previousMonthSales = $this->getPreviousMonthSales($company, null);
                $avgOrderValue = $totalInvoices > 0 ? ($totalSales / $totalInvoices) : 0;
                $profitMargin = $totalSales > 0 ? (($totalProfit / $totalSales) * 100) : 0;
                $expenseRatio = $totalSales > 0 ? (($totalExpenses / $totalSales) * 100) : 0;
                $revenueProgress = $this->getRevenueProgress($todayRevenue, $totalSales);
                $salesGrowthRate = $previousMonthSales > 0
                    ? (($currentMonthSales - $previousMonthSales) / $previousMonthSales) * 100
                    : ($currentMonthSales > 0 ? 100 : 0);

                return [
                    'todayRevenue'    => $todayRevenue,
                    'totalSales'      => $totalSales,
                    'totalProfit'     => $totalProfit,
                    'netProfit'       => $totalProfit,
                    'totalExpenses'   => $totalExpenses,
                    'activeStock'     => $activeStock,
                    'inventoryValue'  => $inventoryValue,
                    'totalInvoices'   => $totalInvoices,
                    'activeCustomers' => $activeCustomers,
                    'lowStockCount'   => $lowStockCount,
                    'pendingBalance'  => $pendingBalance,
                    'itemsSoldToday'  => $itemsSold,
                    'totalOrders'     => $todayOrders,
                    'avgOrderValue'   => $avgOrderValue,
                    'profitMargin'    => $profitMargin,
                    'expenseRatio'    => $expenseRatio,
                    'currentMonthSales' => $currentMonthSales,
                    'previousMonthSales' => $previousMonthSales,
                    'salesGrowthRate' => $salesGrowthRate,
                    'paidInvoices'    => $paymentStatus['paid'],
                    'partialInvoices' => $paymentStatus['partial'],
                    'unpaidInvoices'  => $paymentStatus['unpaid'],
                    'revenueProgress' => $revenueProgress,
                    'countryHeatMap'  => $this->getHeatMapData($company),
                ];
            });
        }

     // 4. PACKAGING DATA FOR DEEP SAPPHIRE VIEW
        $monthlySalesData = $this->getMonthlySalesData($company, $activeBranch);
        $monthlyExpenseData = $this->getMonthlyExpensesData($company, $activeBranch);
        $monthlyProfitData = $this->getMonthlyProfitData($monthlySalesData, $monthlyExpenseData);
        $salesCountByMonth = $this->getSalesCountByMonth($company, $activeBranch);
        $topCustomers = $this->getTopCustomers($company, $activeBranch);

        $seatLimit = $currentSubscription?->resolvedUserLimit();
        $seatCount = $company?->users()->count() ?? ($user->company_id ? User::where('company_id', $user->company_id)->count() : 1);

        $data = [
            'company'          => $company,
            'metrics'          => $metrics,
            'monthlySalesData' => $monthlySalesData,
            'monthlyExpenseData' => $monthlyExpenseData,
            'monthlyProfitData' => $monthlyProfitData,
            'salesCountByMonth' => $salesCountByMonth,
            'topProducts'      => $this->getTopProducts($company, $activeBranch),
            'topCustomers'     => $topCustomers,
            'latestInvoices'   => $this->getLatestInvoices($company, $activeBranch),
            'lowStockProducts' => $this->getLowStockProducts($company, $activeBranch),
            'activities'       => $this->getRecentActivity($company, $activeBranch),
            'dashboardHealth'  => $this->getDashboardHealth($metrics),
            'userRole'         => $userRole,
            'permissions'      => $permissions,
            'currentSubscription' => $currentSubscription,
            'subscriptionStatus' => $this->subscriptionStatusPayload($currentSubscription, $seatCount, $seatLimit),
            'activeBranch'     => $activeBranch,
            'dashboardBranchLabel' => $dashboardBranchLabel,
            // FIX: Added '?? []' to handle missing keys in old cache
            'countryHeatMap'   => $metrics['countryHeatMap'] ?? [], 
        ];

        // 5. THEME-BASED VIEW SELECTION
        if (($userRole === 'superadmin' || $user->email === 'donvictorlive@gmail.com') && !$isBusinessWorkspace) {
            return view('SuperAdmin.dashboards.enterprise', $data);
        }

        return match($plan) {
            'professional' => view('SuperAdmin.dashboards.pro', $data),
            'enterprise' => view('SuperAdmin.dashboards.enterprise', $data),
            default      => view('SuperAdmin.dashboards.basic', $data),
        };
    }

    /**
     * Data fetcher for Global HeatMap (Enterprise Only)
     */
    private function getHeatMapData($company) {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'country')) {
            return $this->scopeByCompany(Company::query(), 'companies', $company)
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->select('country', DB::raw('COUNT(*) as total'))
                ->groupBy('country')
                ->pluck('total', 'country')
                ->map(fn ($count) => (int) $count)
                ->toArray();
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'country')) {
            try {
                return $this->scopeByCompany(DB::table('customers'), 'customers', $company)
                    ->whereNotNull('country')
                    ->where('country', '!=', '')
                    ->select('country', DB::raw('COUNT(*) as total'))
                    ->groupBy('country')
                    ->pluck('total', 'country')
                    ->map(fn ($count) => (int) $count)
                    ->toArray();
            } catch (QueryException $e) {
                Log::warning('Customer heatmap scope skipped due to schema mismatch.', [
                    'error' => $e->getMessage(),
                    'company_id' => $company->id ?? null,
                ]);
            }
        }

        return [];
    }

    private function subscriptionStatusPayload(?Subscription $subscription, int $seatCount, ?int $seatLimit): ?array
    {
        if (!$subscription || !$subscription->end_date) {
            return null;
        }

        $usage = $seatLimit === null
            ? "Users in workspace: {$seatCount} (unlimited on current plan)."
            : "Users in workspace: {$seatCount}/{$seatLimit}.";

        if ($subscription->isExpired()) {
            $date = optional($subscription->end_date)->format('M d, Y');

            return [
                'state' => 'expired',
                'title' => 'Plan expired: workspace access is locked.',
                'message' => "Your {$subscription->planLabel()} plan expired on {$date}. Renew to resume using the app modules.",
                'usage' => $usage,
            ];
        }

        if ($subscription->isExpiringSoon(7)) {
            $days = max(0, $subscription->daysRemaining());
            $date = optional($subscription->end_date)->format('M d, Y');

            return [
                'state' => 'warning',
                'title' => $days === 0 ? 'Plan expires today.' : "Plan expires in {$days} day" . ($days === 1 ? '' : 's') . '.',
                'message' => "Your {$subscription->planLabel()} plan will expire on {$date}. Renew now to avoid workspace interruption.",
                'usage' => $usage,
            ];
        }

        return null;
    }

    /* --- REST OF SCOPED HELPERS (Kept from your original logic) --- */
    private function getTodayRevenue($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->whereDate('created_at', Carbon::today())
            ->sum('total') ?? 0;
    }

    private function getTotalSales($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)->sum('total') ?? 0;
    }

    private function getTotalProfit($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return 0;
        $salesQuery = $this->scopeSalesByContext(Sale::query(), $company, $activeBranch);
        $totalRevenue = (clone $salesQuery)->sum('total') ?? 0;
        $totalCost = 0;

        if (Schema::hasColumn('sales', 'purchase_price')) {
            $totalCost = (clone $salesQuery)->sum('purchase_price') ?? 0;
        } elseif (Schema::hasTable('sale_items') && Schema::hasTable('products')) {
            $salesTableHasCompany = Schema::hasColumn('sales', 'company_id');
            $salesTableHasUser = Schema::hasColumn('sales', 'user_id');
            $companyUserIds = $this->companyUserIds($company);
            $costColumn = Schema::hasColumn('products', 'purchase_price') ? 'products.purchase_price' : '0';

            $costQuery = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id');

            $costQuery = $this->scopeSalesByContext($costQuery, $company, $activeBranch, 'sales');

            $totalCost = (float) ($costQuery->selectRaw("SUM(" . InventoryQuantity::saleStockUnitsExpression('sale_items', 'products') . " * COALESCE({$costColumn}, 0)) as total_cost")->value('total_cost') ?? 0);
        }

        return $totalRevenue - $totalCost;
    }

    private function getTotalExpenses($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('expenses')) return 0;
        return $this->scopeExpensesByContext(Expense::query(), $company, $activeBranch, 'expenses')
            ->where(function ($q) {
                // Count all expenses except explicitly rejected ones
                $q->whereRaw('LOWER(COALESCE(status, \'pending\')) != ?', ['rejected']);
            })
            ->sum('amount') ?? 0;
    }

    private function getTotalStock($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('products')) return 0;
        if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
            $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');

            return (float) ($this->scopeByCompany(Product::query(), 'products', $company)
                ->leftJoin('product_branch_stocks', function ($join) use ($activeBranch) {
                    $join->on('product_branch_stocks.product_id', '=', 'products.id')
                        ->where('product_branch_stocks.branch_id', (string) $activeBranch['id']);
                })
                ->selectRaw("SUM(COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0)) as total_stock")
                ->value('total_stock') ?? 0);
        }

        $stockColumn = Schema::hasColumn('products', 'stock') ? 'stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : null);
        if (!$stockColumn) {
            return 0;
        }
        return $this->scopeByCompany(Product::query(), 'products', $company)->sum($stockColumn) ?? 0;
    }

    private function getMonthlySalesData($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->select(DB::raw('MONTHNAME(created_at) as month'), DB::raw('SUM(total) as total_sales'), DB::raw('MONTH(created_at) as month_num'))
            ->whereYear('created_at', date('Y'))
            ->groupBy('month', 'month_num')->orderBy('month_num', 'asc')->get();
    }

    private function getTopProducts($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('products') || !Schema::hasTable('sale_items')) return collect();
        $query = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id');

        return $this->scopeSalesByContext($query, $company, $activeBranch, 'sales')
            ->select('products.id', 'products.name', DB::raw("SUM(" . InventoryQuantity::saleStockUnitsExpression('sale_items', 'products') . ") as total_qty"))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();
    }

    private function getLowStockProducts($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('products')) return collect();
        if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
            $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');
            $reorderColumn = Schema::hasColumn('products', 'reorder_level') ? 'products.reorder_level' : '0';

            return $this->scopeByCompany(Product::query(), 'products', $company)
                ->leftJoin('product_branch_stocks', function ($join) use ($activeBranch) {
                    $join->on('product_branch_stocks.product_id', '=', 'products.id')
                        ->where('product_branch_stocks.branch_id', (string) $activeBranch['id']);
                })
                ->select('products.id', 'products.name')
                ->selectRaw("COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) as stock")
                ->whereRaw("COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) <= COALESCE(NULLIF({$reorderColumn}, 0), 15)")
                ->orderBy('product_branch_stocks.quantity', 'asc')
                ->limit(10)
                ->get();
        }

        $stockColumn = Schema::hasColumn('products', 'stock') ? 'stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : null);
        if (!$stockColumn) {
            return collect();
        }
        return $this->scopeByCompany(Product::query(), 'products', $company)
            ->select('id', 'name', $stockColumn . ' as stock')
            ->where($stockColumn, '<=', 15)
            ->orderBy($stockColumn, 'asc')
            ->limit(10)
            ->get();
    }

    private function getLowStockCount($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('products')) return 0;
        if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
            $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');
            $reorderColumn = Schema::hasColumn('products', 'reorder_level') ? 'products.reorder_level' : '0';

            return (int) ($this->scopeByCompany(Product::query(), 'products', $company)
                ->leftJoin('product_branch_stocks', function ($join) use ($activeBranch) {
                    $join->on('product_branch_stocks.product_id', '=', 'products.id')
                        ->where('product_branch_stocks.branch_id', (string) $activeBranch['id']);
                })
                ->whereRaw("COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) <= COALESCE(NULLIF({$reorderColumn}, 0), 15)")
                ->count() ?? 0);
        }

        $stockColumn = Schema::hasColumn('products', 'stock') ? 'stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : null);
        if (!$stockColumn) {
            return 0;
        }
        return $this->scopeByCompany(Product::query(), 'products', $company)
            ->where($stockColumn, '<=', 15)
            ->count();
    }

    private function getTotalInvoices($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)->count();
    }

    private function getActiveCustomers($company) {
        if (Schema::hasTable('customers')) {
            try {
                return $this->scopeByCompany(DB::table('customers'), 'customers', $company)->count();
            } catch (QueryException $e) {
                // Fallback for legacy schemas where customers.company_id does not exist.
                Log::warning('Customer company scope skipped due to schema mismatch.', [
                    'error' => $e->getMessage(),
                    'company_id' => $company->id ?? null,
                ]);

                if (Schema::hasColumn('customers', 'user_id')) {
                    return DB::table('customers')
                        ->whereIn('user_id', $this->companyUserIds($company))
                        ->count();
                }

                return 0;
            }
        }
        if (Schema::hasColumn('users', 'company_id')) {
            return User::where('company_id', $company->id ?? 0)->count();
        }
        return 0;
    }

    private function getLatestInvoices($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->select('id', 'order_number', 'invoice_no', 'customer_name', 'total', 'balance', 'payment_status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    private function getRecentActivity($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($sale) {
                $customer = $sale->customer_name ?: 'Walk-in customer';
                $ref = $sale->invoice_no ?: $sale->order_number ?: ('Sale #' . $sale->id);
                $status = strtoupper((string) ($sale->payment_status ?: 'paid'));

                return (object) [
                    'id' => $sale->id,
                    'description' => "{$ref} for {$customer} marked {$status}",
                    'created_at' => $sale->created_at,
                    'amount' => (float) ($sale->total ?? 0),
                    'status' => strtolower((string) ($sale->payment_status ?? 'paid')),
                ];
            });
    }

    private function getMonthlyExpensesData($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('expenses')) return collect();
        return $this->scopeExpensesByContext(Expense::query(), $company, $activeBranch, 'expenses')
            ->select(
                DB::raw('MONTHNAME(created_at) as month'),
                DB::raw('MONTH(created_at) as month_num'),
                DB::raw('SUM(amount) as total_expenses')
            )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month', 'month_num')
            ->orderBy('month_num', 'asc')
            ->get();
    }

    private function getMonthlyProfitData($monthlySalesData, $monthlyExpenseData) {
        $salesByMonth = $monthlySalesData->keyBy('month_num');
        $expensesByMonth = $monthlyExpenseData->keyBy('month_num');
        $rows = collect();

        for ($month = 1; $month <= 12; $month++) {
            $sales = (float) ($salesByMonth[$month]->total_sales ?? 0);
            $expenses = (float) ($expensesByMonth[$month]->total_expenses ?? 0);
            $rows->push((object) [
                'month' => date('M', mktime(0, 0, 0, $month, 1)),
                'month_num' => $month,
                'total_profit' => $sales - $expenses,
            ]);
        }

        return $rows;
    }

    private function getSalesCountByMonth($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->select(
                DB::raw('MONTHNAME(created_at) as month'),
                DB::raw('MONTH(created_at) as month_num'),
                DB::raw('COUNT(*) as total_orders')
            )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month', 'month_num')
            ->orderBy('month_num', 'asc')
            ->get();
    }

    private function getTopCustomers($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->whereNotNull('customer_name')
            ->select('customer_name', DB::raw('SUM(total) as total_spend'), DB::raw('COUNT(*) as invoices_count'))
            ->groupBy('customer_name')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();
    }

    private function getPendingBalance($company, ?array $activeBranch = null): float
    {
        if (!Schema::hasTable('sales')) return 0;

        return (float) ($this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->where('balance', '>', 0)
            ->sum('balance') ?? 0);
    }

    private function getItemsSold($company, ?array $activeBranch = null): int
    {
        if (!Schema::hasTable('sale_items') || !Schema::hasTable('sales')) return 0;
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereDate('sales.created_at', Carbon::today());

        $query = $this->scopeSalesByContext($query, $company, $activeBranch, 'sales');

        return (int) ($query->sum(DB::raw(InventoryQuantity::saleStockUnitsExpression('sale_items', 'products'))) ?? 0);
    }

    private function getTodayOrders($company, ?array $activeBranch = null): int
    {
        if (!Schema::hasTable('sales')) return 0;

        return (int) ($this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->whereDate('created_at', Carbon::today())
            ->count() ?? 0);
    }

    private function getPaymentStatusCounts($company, ?array $activeBranch = null): array
    {
        if (!Schema::hasTable('sales')) {
            return ['paid' => 0, 'partial' => 0, 'unpaid' => 0];
        }

        $statusData = $this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->select('payment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status');

        $normalized = [];
        foreach ($statusData as $status => $total) {
            $key = strtolower(trim((string) $status));
            $normalized[$key] = ($normalized[$key] ?? 0) + (int) $total;
        }

        return [
            'paid' => (int) (($normalized['paid'] ?? 0) + ($normalized['completed'] ?? 0) + ($normalized['success'] ?? 0)),
            'partial' => (int) (($normalized['partial'] ?? 0) + ($normalized['partially_paid'] ?? 0)),
            'unpaid' => (int) (($normalized['unpaid'] ?? 0) + ($normalized['pending'] ?? 0) + ($normalized['outstanding'] ?? 0) + ($normalized['overdue'] ?? 0)),
        ];
    }

    private function getRevenueProgress(float $todayRevenue, float $totalSales): float
    {
        if ($totalSales <= 0) {
            return 0;
        }

        $daysElapsed = max(1, now()->day);
        $projectedMonthRevenue = ($totalSales / $daysElapsed) * now()->daysInMonth;

        if ($projectedMonthRevenue <= 0) {
            return 0;
        }

        return round(min(100, max(0, ($todayRevenue / $projectedMonthRevenue) * 100)), 1);
    }

    private function getInventoryValue($company, ?array $activeBranch = null): float
    {
        if (!Schema::hasTable('products')) {
            return 0;
        }

        if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
            $priceColumn = Schema::hasColumn('products', 'price') ? 'products.price' : (Schema::hasColumn('products', 'product_price') ? 'products.product_price' : null);
            $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');
            if (!$priceColumn) {
                return 0;
            }

            return (float) ($this->scopeByCompany(Product::query(), 'products', $company)
                ->leftJoin('product_branch_stocks', function ($join) use ($activeBranch) {
                    $join->on('product_branch_stocks.product_id', '=', 'products.id')
                        ->where('product_branch_stocks.branch_id', (string) $activeBranch['id']);
                })
                ->selectRaw("SUM(COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) * COALESCE({$priceColumn}, 0)) as inventory_value")
                ->value('inventory_value') ?? 0);
        }

        $stockColumn = Schema::hasColumn('products', 'stock') ? 'stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : null);
        $priceColumn = Schema::hasColumn('products', 'price') ? 'price' : (Schema::hasColumn('products', 'product_price') ? 'product_price' : null);

        if (!$stockColumn || !$priceColumn) {
            return 0;
        }

        return (float) ($this->scopeByCompany(Product::query(), 'products', $company)
            ->selectRaw("SUM(COALESCE({$stockColumn}, 0) * COALESCE({$priceColumn}, 0)) as inventory_value")
            ->value('inventory_value') ?? 0);
    }

    private function getCurrentMonthSales($company, ?array $activeBranch = null): float
    {
        if (!Schema::hasTable('sales')) {
            return 0;
        }

        return (float) ($this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total') ?? 0);
    }

    private function getPreviousMonthSales($company, ?array $activeBranch = null): float
    {
        if (!Schema::hasTable('sales')) {
            return 0;
        }

        $previousMonth = now()->copy()->subMonth();

        return (float) ($this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->sum('total') ?? 0);
    }

    private function getDashboardHealth(array $metrics): array
    {
        $profitMargin = (float) ($metrics['profitMargin'] ?? 0);
        $lowStock = (int) ($metrics['lowStockCount'] ?? 0);
        $pendingBalance = (float) ($metrics['pendingBalance'] ?? 0);

        return [
            'cashflow' => $pendingBalance > 0 ? 'Attention needed' : 'Healthy',
            'inventory' => $lowStock > 0 ? 'Restock soon' : 'Healthy',
            'margin' => $profitMargin >= 20 ? 'Strong' : ($profitMargin > 0 ? 'Stable' : 'Thin'),
        ];
    }

    private function scopeByCompany($query, string $table, $company, ?string $column = null)
    {
        if (!Schema::hasTable($table)) {
            return $query;
        }

        $user = Auth::user();
        $isSuperAdmin = $user && (in_array(strtolower((string)$user->role), ['superadmin', 'super_admin'], true) || strtolower((string)$user->email) === 'donvictorlive@gmail.com');

        // Global Platform Dashboard for Super Admins bypasses all company scoping to show total metrics across all tenants
        if ($isSuperAdmin && session('workspace_context') === 'platform') {
            return $query;
        }

        $fallbackCompanyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $resolvedCompanyId = (int) (($company?->id ?? null) ?? $fallbackCompanyId);
        $targetColumn = $column ?? 'company_id';
        $qualifiedTargetColumn = str_contains($targetColumn, '.') ? $targetColumn : ($table . '.' . $targetColumn);
        $hasCompanyColumn = Schema::hasColumn($table, 'company_id');
        $hasUserColumn = Schema::hasColumn($table, 'user_id');

        if ($resolvedCompanyId <= 0) {
            if ($hasUserColumn && Auth::id()) {
                return $query->where($table . '.user_id', (int) Auth::id());
            }

            return $query;
        }

        // Primary scope: direct company link.
        if ($hasCompanyColumn) {
            // Legacy fallback: include records created by company users where company_id is null.
            if ($hasUserColumn) {
                $userIds = $this->companyUserIds($company) ?: (Auth::id() ? [(int) Auth::id()] : []);
                return $query->where(function ($q) use ($qualifiedTargetColumn, $resolvedCompanyId, $table, $userIds) {
                    $q->where($qualifiedTargetColumn, $resolvedCompanyId)
                      ->orWhere(function ($legacy) use ($table, $userIds) {
                          $legacy->whereNull($table . '.company_id')
                                 ->whereIn($table . '.user_id', $userIds);
                      });
                });
            }

            return $query->where($qualifiedTargetColumn, $resolvedCompanyId);
        }

        // Fallback for legacy tables with no company_id.
        if ($hasUserColumn) {
            $userIds = $this->companyUserIds($company) ?: (Auth::id() ? [(int) Auth::id()] : []);
            return $query->whereIn($table . '.user_id', $userIds);
        }

        return $query;
    }

    private function companyUserIds($company): array
    {
        $ids = [];
        
        if (!empty($company?->user_id)) {
            $ids[] = (int) $company->user_id;
        }

        if (!empty($company?->owner_id)) {
            $ids[] = (int) $company->owner_id;
        }

        if ($company?->id && Schema::hasColumn('users', 'company_id')) {
            $memberIds = User::where('company_id', $company->id)->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            $ids = array_merge($ids, $memberIds);
        }

        if (empty($ids) && Auth::id()) {
            $ids[] = (int) Auth::id();
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function activeBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    private function scopeSalesByContext($query, $company, ?array $activeBranch = null, string $table = 'sales')
    {
        $query = $this->scopeByCompany($query, $table, $company);

        if (empty($activeBranch['id']) && empty($activeBranch['name'])) {
            // Always include POS sales in all metrics
            return $this->addPosSalesScope($query, $table);
        }

        $branchId = (string) ($activeBranch['id'] ?? '');
        $branchName = (string) ($activeBranch['name'] ?? '');

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($table, $branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                    $sub->where($table . '.branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                    $sub->orWhere($table . '.branch_name', $branchName);
                }
            });
        }

        // Always include POS sales in all metrics
        return $this->addPosSalesScope($query, $table);
    }

    private function scopeExpensesByContext($query, $company, ?array $activeBranch = null, string $table = 'expenses')
    {
        $query = $this->scopeByCompany($query, $table, $company);

        if (empty($activeBranch['id']) && empty($activeBranch['name'])) {
            return $query;
        }

        $branchId = (string) ($activeBranch['id'] ?? '');
        $branchName = (string) ($activeBranch['name'] ?? '');

        return $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where($table . '.branch_id', $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->orWhere($table . '.branch_name', $branchName);
            }
        });
    }

    /**
     * Ensures POS sales are always included in dashboard metrics.
     * Applies to all sales queries for metrics.
     */
    private function addPosSalesScope($query, string $table = 'sales')
    {
        // If payment_method or payment_details->source is present, include POS
        if (Schema::hasColumn($table, 'payment_method')) {
            $query->orWhere($table . '.payment_method', 'pos');
        }
        // For JSON payment_details->source
        if (Schema::hasColumn($table, 'payment_details')) {
            $query->orWhere(function ($q) use ($table) {
                $q->where($table . '.payment_details', 'like', '%"source":"pos"%');
            });
        }
        return $query;
    }

    private function shouldUseTenantWideDashboardFallback(array $metrics): bool
    {
        $signals = [
            (float) ($metrics['totalSales'] ?? 0),
            (float) ($metrics['totalInvoices'] ?? 0),
            (float) ($metrics['activeStock'] ?? 0),
            (float) ($metrics['activeCustomers'] ?? 0),
        ];

        return collect($signals)->every(fn ($value) => $value <= 0);
    }
}
