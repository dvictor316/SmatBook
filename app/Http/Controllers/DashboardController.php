<?php

namespace App\Http\Controllers;

use App\Models\{Product, Expense, Sale, Company, Subscription, User, Plan, ProductBranchStock};
use App\Models\Customer;
use App\Support\InventoryQuantity;
use App\Support\StockValuation;
use Illuminate\Support\Facades\{DB, Auth, Schema, Cache, Log};
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    private ?\App\Support\ReportingPeriod $reportingPeriod = null;
    public function __construct()
    {
        $this->middleware('permission:dashboard.overview.view')->only(['index', 'businessDashboard', 'switchToBusinessWorkspace']);
    }

    public function switchToBusinessWorkspace(Request $request)
    {
        $request->session()->put('workspace_context', 'business');
        return $this->index($request);
    }

    public function switchToPlatformWorkspace(Request $request)
    {
        app(\App\Support\BusinessWorkspaceResolver::class)->clearContext(Auth::user());
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
        $this->reportingPeriod = \App\Support\ReportingPeriod::fromRequest($request);
        $user = Auth::user();
        if (in_array(strtolower((string) $user->role), ['super_admin', 'superadmin'], true)
            && session('workspace_context') !== 'platform') {
            app(\App\Support\SuperAdminBusinessWorkspace::class)->activate($user);
        }
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

        $assignedStaffCompany = $this->assignedStaffCompany($user);
        if ($assignedStaffCompany) {
            session([
                'user_plan' => strtolower((string) ($assignedStaffCompany->plan ?? 'basic')),
                'current_tenant_id' => $assignedStaffCompany->id,
                'current_tenant_name' => $assignedStaffCompany->name ?? $assignedStaffCompany->company_name ?? 'Workspace',
                'workspace_context' => 'business',
            ]);
        }

        $tenantId = $this->resolvedDashboardCompanyId();
        $userCompany = $tenantId > 0 ? Company::withoutGlobalScope('tenant')->find($tenantId) : null;
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
        $demoPreviewCustomer = $this->resolveDemoPreviewCustomer($request, $user);
        $demoPreviewPlan = $user?->isDemoUser() ? $this->resolveDemoPreviewPlan($request, $user) : null;
        $hasBusinessWorkspace = (int) ($user->company_id ?? 0) > 0
            || (int) ($currentSubscription?->company_id ?? 0) > 0
            || (int) session('current_tenant_id', 0) > 0;
        $isSuperAdmin = $this->isSuperAdminUser($user);
        $canUsePlatformWorkspace = $isSuperAdmin;
        $defaultWorkspaceContext = ($hasBusinessWorkspace || $isSuperAdmin) ? 'business' : 'platform';
        $workspaceContext = (string) $request->session()->get('workspace_context', $defaultWorkspaceContext);

        if (!$canUsePlatformWorkspace) {
            $workspaceContext = 'business';
            $request->session()->put('workspace_context', 'business');
        }

        $isBusinessWorkspace = $workspaceContext === 'business';
        if ($isSuperAdmin && $isBusinessWorkspace) {
            $request->session()->put('user_plan', 'enterprise');
        }
        $activeBranch = $isBusinessWorkspace ? $this->activeBranchContext() : ['id' => null, 'name' => null];
        // 1. TENANT IDENTIFICATION VIA SUBDOMAIN
        $subdomain = in_array(Str::lower($currentHost), $centralHosts, true) ? null : explode('.', $currentHost)[0];

        if ($subdomain) {
            $subscription = Subscription::where('domain_prefix', $subdomain)->first();

            $company = $this->resolveDashboardCompany($user, $subdomain, $subscription);
        } else {
            $company = $this->resolveDashboardCompany($user, null, $currentSubscription);
        }

        if ($subdomain && $company) {
            $allowedUserIds = $this->companyUserIds($company);
            if (!in_array((int) $user->id, $allowedUserIds, true) && !in_array($user->role, ['superadmin', 'admin'])) {
                abort(403, 'Unauthorized workspace.');
            }
        }

        // Redirect if setup isn't finished (Handshake check)
        // Super admin is exempt — they have no company but should access all pages freely.
        if (!$isSuperAdmin) {
            if ($subdomain && !$company) {
                if ($this->shouldShowMissingWorkspaceError($user, $currentSubscription)) {
                    return $this->missingWorkspaceLoginError();
                }

                return redirect()->route('saas.setup')->with('info', 'Handshake incomplete. Please set your URL.');
            }
            if (!$company && !in_array(strtolower((string) $user->role), ['superadmin', 'super_admin', 'admin', 'administrator'], true)) {
                if ($this->shouldShowMissingWorkspaceError($user, $currentSubscription)) {
                    return $this->missingWorkspaceLoginError();
                }

                return redirect()->route('saas.setup')->with('info', 'Handshake incomplete. Please set your URL.');
            }
        }

        $userRole = $user->role;
        // Resolve current grants on every request so revocations apply immediately.
        $permissions = Schema::hasTable('permissions')
            ? \App\Models\Permission::pluck('name')->filter(fn ($name) => $user->hasPermissionTo($name))->values()->all()
            : [];

        $plan = ($isSuperAdmin && $isBusinessWorkspace)
            ? 'enterprise'
            : ($demoPreviewPlan ?: Plan::normalizeTier(
                $currentSubscription?->plan_name
                ?? $currentSubscription?->plan
                ?? ($company?->plan ?? 'basic')
            ));
        $dashboardBranchLabel = ($activeBranch['scope'] ?? 'branch') === 'all'
            ? 'All Branches'
            : ($activeBranch['name'] ?? null);

        // Recompute sales figures on every request: new sales and midnight must be visible immediately.
        $resolvedCompanyScopeId = (int) (($company?->id ?? null) ?: $this->resolvedDashboardCompanyId());
        $metrics = (function() use ($company, $activeBranch) {
            $todayRevenue = $this->getTodayRevenue($company, $activeBranch);
            $totalSales = $this->getTotalSales($company, $activeBranch);
            $totalProfit = $this->getTotalProfit($company, $activeBranch);
            $totalExpenses = $this->getTotalExpenses($company, $activeBranch);
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
                'netProfit'       => $totalProfit - $totalExpenses, // alias used by pro dashboard
                'totalExpenses'   => $totalExpenses,
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
        })();

        $valuation = app(StockValuation::class);
        $stockSummary = $valuation->summary($valuation->rowsQuery(
            $resolvedCompanyScopeId,
            (int) $user->id,
            $activeBranch
        ));
        $metrics['activeStock'] = $stockSummary['quantity'];
        $metrics['inventoryValue'] = $stockSummary['value'];

     // 4. PACKAGING DATA FOR DEEP SAPPHIRE VIEW
        $monthlySalesData = $this->getMonthlySalesData($company, $activeBranch);
        $monthlyExpenseData = $this->getMonthlyExpensesData($company, $activeBranch);
        $monthlyProfitData = $this->getMonthlyProfitData($monthlySalesData, $monthlyExpenseData);
        $salesCountByMonth = $this->getSalesCountByMonth($company, $activeBranch);
        $topCustomers = $this->getTopCustomers($company, $activeBranch);
        $expiringProducts = $this->getExpiringProducts($company);

        $seatLimit = $currentSubscription?->resolvedUserLimit();
        $seatCount = $company?->users()->count() ?? ($user->company_id ? User::where('company_id', $user->company_id)->count() : 1);

        $data = [
            'reportingPeriod' => $this->reportingPeriod,
            'periodSales' => $this->periodSales($company, $activeBranch),
            'company'          => $company,
            'metrics'          => $metrics,
            'monthlySalesData' => $monthlySalesData,
            'monthlyExpenseData' => $monthlyExpenseData,
            'monthlyProfitData' => $monthlyProfitData,
            'salesCountByMonth' => $salesCountByMonth,
            'topProducts'      => $this->getTopProducts($company, $activeBranch),
            'topCustomers'     => $topCustomers,
            'latestInvoices'   => $this->getLatestInvoices($company, $activeBranch),
            'lowStockProducts'    => $this->getLowStockProducts($company, $activeBranch),
            'expiringProducts'   => $expiringProducts,
            'expiredProducts'    => $expiringProducts['expired'] ?? collect(),
            'expiringSoonProducts' => $expiringProducts['expiring_soon'] ?? collect(),
            'activities'         => $this->getRecentActivity($company, $activeBranch),
            'dashboardHealth'  => $this->getDashboardHealth($metrics),
            'userRole'         => $userRole,
            'permissions'      => $permissions,
            'currentSubscription' => $currentSubscription,
            'subscriptionStatus' => $this->subscriptionStatusPayload($currentSubscription, $seatCount, $seatLimit),
            'activeBranch'     => $activeBranch,
            'dashboardBranchLabel' => $dashboardBranchLabel,
            'demoPreviewCustomer' => $demoPreviewCustomer,
            'demoPreviewPlan' => $demoPreviewPlan,
            'demoPreviewMode' => $demoPreviewCustomer !== null,
            // FIX: Added '?? []' to handle missing keys in old cache
            'countryHeatMap'   => $metrics['countryHeatMap'] ?? [], 
        ];

        // 5. THEME-BASED VIEW SELECTION
        if (($userRole === 'superadmin' || $user->email === 'donvictorlive@gmail.com') && !$isBusinessWorkspace) {
            return view('SuperAdmin.dashboards.enterprise', $data);
        }

        return match($plan) {
            'starter' => view('SuperAdmin.dashboards.starter', $data),
            'professional' => view('SuperAdmin.dashboards.pro', $data),
            'enterprise' => view('SuperAdmin.dashboards.enterprise', $data),
            default      => view('SuperAdmin.dashboards.basic', $data),
        };
    }

    private function resolveDemoPreviewPlan(Request $request, ?User $user): ?string
    {
        if (! $user?->isDemoUser()) {
            return null;
        }

        return Plan::normalizeTier((string) $request->session()->get('demo_customer_preview_plan', 'basic'));
    }

    private function resolveDemoPreviewCustomer(Request $request, ?User $user): ?Customer
    {
        if (! $user?->isDemoUser()) {
            return null;
        }

        $customerId = (int) $request->session()->get('demo_customer_preview_id', 0);
        if ($customerId <= 0) {
            return null;
        }

        return Customer::query()
            ->where('company_id', $user->company_id)
            ->find($customerId);
    }

    private function isSuperAdminUser(?User $user): bool
    {
        return $user && (
            in_array(strtolower((string) ($user->role ?? '')), ['super_admin', 'superadmin'], true)
            || strtolower((string) ($user->email ?? '')) === 'donvictorlive@gmail.com'
        );
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

        if ($subscription->isTrial()) {
            $days = max(0, $subscription->daysRemaining());
            $date = optional($subscription->end_date)->format('M d, Y');

            return [
                'state' => 'trial',
                'title' => $days === 0 ? 'Your free trial ends today.' : "Free trial: {$days} day" . ($days === 1 ? '' : 's') . ' remaining.',
                'message' => "Full access to {$subscription->planLabel()} is active until {$date}. No payment is required during the trial.",
                'usage' => $usage,
            ];
        }

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
            ->whereRaw(\App\Support\SalesReportingDate::expression().' = ?', [\App\Support\ReportingPeriod::today()->toDateString()])
            ->sum('total') ?? 0;
    }

    private function getTotalSales($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopePeriodSalesByContext(Sale::query(), $company, $activeBranch)->sum('total') ?? 0;
    }

    private function getTotalProfit($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return 0;
        $salesQuery = $this->scopePeriodSalesByContext(Sale::query(), $company, $activeBranch);
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

            $costQuery = $this->scopePeriodSalesByContext($costQuery, $company, $activeBranch, 'sales');

            $totalCost = (float) ($costQuery->selectRaw("SUM(" . InventoryQuantity::saleStockUnitsExpression('sale_items', 'products') . " * COALESCE({$costColumn}, 0)) as total_cost")->value('total_cost') ?? 0);
        }

        return $totalRevenue - $totalCost;
    }

    private function getTotalExpenses($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('expenses')) return 0;
        return ($this->reportingPeriod ?? \App\Support\ReportingPeriod::preset('all'))->apply($this->scopeExpensesByContext(Expense::query(), $company, $activeBranch, 'expenses'), \App\Support\SalesReportingDate::timestampExpression('expenses.created_at'), 'expenses.created_at')
            ->where(function ($q) {
                // Count all expenses except explicitly rejected ones
                $q->whereRaw('LOWER(COALESCE(status, \'pending\')) != ?', ['rejected']);
            })
            ->sum('amount') ?? 0;
    }

    private function getTotalStock($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('products')) return 0;
        if (($activeBranch['scope'] ?? 'branch') === 'all' && Schema::hasTable('product_branch_stocks')) {
            return (float) ($this->scopeByCompany(DB::table('product_branch_stocks'), 'product_branch_stocks', $company)
                ->sum('quantity') ?? 0);
        }

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
        return $this->calendarMonthlyData($this->scopeSalesByContext(Sale::query(), $company, $activeBranch), \App\Support\SalesReportingDate::expression(), 'SUM(total)', 'total_sales');
    }

    private function getTopProducts($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('products') || !Schema::hasTable('sale_items')) return collect();
        $query = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id');

        return $this->scopePeriodSalesByContext($query, $company, $activeBranch, 'sales')
            ->select('products.id', 'products.name', DB::raw("SUM(" . InventoryQuantity::saleStockUnitsExpression('sale_items', 'products') . ") as total_qty"))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();
    }

    private function getLowStockProducts($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('products')) return collect();
        try {
            if (($activeBranch['scope'] ?? 'branch') === 'all' && Schema::hasTable('product_branch_stocks')) {
                $hasReorderLevel = $this->hasProductReorderLevelColumn();
                $reorderExpr = $hasReorderLevel ? 'COALESCE(NULLIF(products.reorder_level, 0), 15)' : '15';
                $groupByCols = $hasReorderLevel
                    ? ['products.id', 'products.name', 'products.reorder_level']
                    : ['products.id', 'products.name'];

                return $this->scopeByCompany(DB::table('products'), 'products', $company)
                    ->join('product_branch_stocks', 'product_branch_stocks.product_id', '=', 'products.id')
                    ->select('products.id', 'products.name')
                    ->selectRaw('SUM(COALESCE(product_branch_stocks.quantity, 0)) as stock')
                    ->groupBy($groupByCols)
                    ->havingRaw("SUM(COALESCE(product_branch_stocks.quantity, 0)) <= {$reorderExpr}")
                    ->orderByRaw('SUM(COALESCE(product_branch_stocks.quantity, 0)) asc')
                    ->limit(10)
                    ->get();
            }

            if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
                $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');
                $reorderColumn = $this->hasProductReorderLevelColumn() ? 'products.reorder_level' : '0';

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
        } catch (QueryException $e) {
            Log::warning('Low stock dashboard query fell back to default threshold.', [
                'company_id' => $company->id ?? null,
                'branch_scope' => $activeBranch['scope'] ?? 'branch',
                'branch_id' => $activeBranch['id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            if (($activeBranch['scope'] ?? 'branch') === 'all' && Schema::hasTable('product_branch_stocks')) {
                return $this->scopeByCompany(DB::table('products'), 'products', $company)
                    ->join('product_branch_stocks', 'product_branch_stocks.product_id', '=', 'products.id')
                    ->select('products.id', 'products.name')
                    ->selectRaw('SUM(COALESCE(product_branch_stocks.quantity, 0)) as stock')
                    ->groupBy('products.id', 'products.name')
                    ->havingRaw('SUM(COALESCE(product_branch_stocks.quantity, 0)) <= 15')
                    ->orderByRaw('SUM(COALESCE(product_branch_stocks.quantity, 0)) asc')
                    ->limit(10)
                    ->get();
            }

            if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
                $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');

                return $this->scopeByCompany(Product::query(), 'products', $company)
                    ->leftJoin('product_branch_stocks', function ($join) use ($activeBranch) {
                        $join->on('product_branch_stocks.product_id', '=', 'products.id')
                            ->where('product_branch_stocks.branch_id', (string) $activeBranch['id']);
                    })
                    ->select('products.id', 'products.name')
                    ->selectRaw("COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) as stock")
                    ->whereRaw("COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) <= 15")
                    ->orderBy('product_branch_stocks.quantity', 'asc')
                    ->limit(10)
                    ->get();
            }
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
        try {
            if (($activeBranch['scope'] ?? 'branch') === 'all' && Schema::hasTable('product_branch_stocks')) {
                $hasReorderLevel = $this->hasProductReorderLevelColumn();
                $reorderExpr = $hasReorderLevel ? 'COALESCE(NULLIF(products.reorder_level, 0), 15)' : '15';

                return (int) ($this->scopeByCompany(DB::table('products'), 'products', $company)
                    ->join('product_branch_stocks', 'product_branch_stocks.product_id', '=', 'products.id')
                    ->select('products.id')
                    ->groupBy('products.id')
                    ->havingRaw("SUM(COALESCE(product_branch_stocks.quantity, 0)) <= {$reorderExpr}")
                    ->get()
                    ->count() ?? 0);
            }

            if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
                $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');
                $reorderColumn = $this->hasProductReorderLevelColumn() ? 'products.reorder_level' : '0';

                return (int) ($this->scopeByCompany(Product::query(), 'products', $company)
                    ->leftJoin('product_branch_stocks', function ($join) use ($activeBranch) {
                        $join->on('product_branch_stocks.product_id', '=', 'products.id')
                            ->where('product_branch_stocks.branch_id', (string) $activeBranch['id']);
                    })
                    ->whereRaw("COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) <= COALESCE(NULLIF({$reorderColumn}, 0), 15)")
                    ->count() ?? 0);
            }
        } catch (QueryException $e) {
            Log::warning('Low stock count dashboard query fell back to default threshold.', [
                'company_id' => $company->id ?? null,
                'branch_scope' => $activeBranch['scope'] ?? 'branch',
                'branch_id' => $activeBranch['id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            if (($activeBranch['scope'] ?? 'branch') === 'all' && Schema::hasTable('product_branch_stocks')) {
                return (int) ($this->scopeByCompany(DB::table('products'), 'products', $company)
                    ->join('product_branch_stocks', 'product_branch_stocks.product_id', '=', 'products.id')
                    ->select('products.id')
                    ->groupBy('products.id')
                    ->havingRaw('SUM(COALESCE(product_branch_stocks.quantity, 0)) <= 15')
                    ->get()
                    ->count() ?? 0);
            }

            if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
                $stockColumn = Schema::hasColumn('products', 'stock') ? 'products.stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'products.stock_quantity' : '0');

                return (int) ($this->scopeByCompany(Product::query(), 'products', $company)
                    ->leftJoin('product_branch_stocks', function ($join) use ($activeBranch) {
                        $join->on('product_branch_stocks.product_id', '=', 'products.id')
                            ->where('product_branch_stocks.branch_id', (string) $activeBranch['id']);
                    })
                    ->whereRaw("COALESCE(product_branch_stocks.quantity, {$stockColumn}, 0) <= 15")
                    ->count() ?? 0);
            }
        }

        $stockColumn = Schema::hasColumn('products', 'stock') ? 'stock' : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : null);
        if (!$stockColumn) {
            return 0;
        }
        return $this->scopeByCompany(Product::query(), 'products', $company)
            ->where($stockColumn, '<=', 15)
            ->count();
    }

    private function getExpiringProducts($company, int $daysAhead = 30): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'expiry_date')) {
            return ['expired' => collect(), 'expiring_soon' => collect()];
        }

        $base = $this->scopeByCompany(Product::query(), 'products', $company)
            ->whereNotNull('expiry_date')
            ->select('id', 'name', 'expiry_date',
                DB::raw(Schema::hasColumn('products', 'stock') ? 'stock' : '0 as stock'));

        $expired    = (clone $base)->whereDate('expiry_date', '<', now()->toDateString())
                                    ->orderBy('expiry_date')
                                    ->limit(20)
                                    ->get();

        $expiringSoon = (clone $base)
                            ->whereDate('expiry_date', '>=', now()->toDateString())
                            ->whereDate('expiry_date', '<=', now()->addDays($daysAhead)->toDateString())
                            ->orderBy('expiry_date')
                            ->limit(20)
                            ->get();

        return [
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
        ];
    }

    private function getTotalInvoices($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopePeriodSalesByContext(Sale::query(), $company, $activeBranch)->count();
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
        return $this->scopePeriodSalesByContext(Sale::query(), $company, $activeBranch)
            ->select('id', 'order_number', 'invoice_no', 'customer_name', 'total', 'balance', 'payment_status', 'order_date', 'created_at')
            ->orderByRaw(\App\Support\SalesReportingDate::expression().' DESC')->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    private function getRecentActivity($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopePeriodSalesByContext(Sale::query(), $company, $activeBranch)
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
        return $this->calendarMonthlyData($this->scopeExpensesByContext(Expense::query(), $company, $activeBranch, 'expenses'), \App\Support\SalesReportingDate::timestampExpression('expenses.created_at'), 'SUM(amount)', 'total_expenses');
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
        return $this->calendarMonthlyData($this->scopeSalesByContext(Sale::query(), $company, $activeBranch), \App\Support\SalesReportingDate::expression(), 'COUNT(*)', 'total_orders');
    }

    private function getTopCustomers($company, ?array $activeBranch = null) {
        if (!Schema::hasTable('sales')) return collect();
        $query = $this->scopePeriodSalesByContext(Sale::query(), $company, $activeBranch)
            ->whereNotNull('customer_name')
            ->select('customer_name', DB::raw('SUM(total) as total_spend'), DB::raw('COUNT(*) as invoices_count'));

        if (auth()->user()?->isDemoUser()) {
            $previewCustomerId = (int) session('demo_customer_preview_id', 0);
            if ($previewCustomerId > 0 && Schema::hasColumn('sales', 'customer_id')) {
                $query->where('customer_id', $previewCustomerId);
            }
        }

        return $query
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
            ->whereRaw(\App\Support\SalesReportingDate::expression().' = ?', [\App\Support\ReportingPeriod::today()->toDateString()]);

        $query = $this->scopeSalesByContext($query, $company, $activeBranch, 'sales');

        return (int) ($query->sum(DB::raw(InventoryQuantity::saleStockUnitsExpression('sale_items', 'products'))) ?? 0);
    }

    private function getTodayOrders($company, ?array $activeBranch = null): int
    {
        if (!Schema::hasTable('sales')) return 0;

        return (int) ($this->scopeSalesByContext(Sale::query(), $company, $activeBranch)
            ->whereRaw(\App\Support\SalesReportingDate::expression().' = ?', [\App\Support\ReportingPeriod::today()->toDateString()])
            ->count() ?? 0);
    }

    private function getPaymentStatusCounts($company, ?array $activeBranch = null): array
    {
        if (!Schema::hasTable('sales')) {
            return ['paid' => 0, 'partial' => 0, 'unpaid' => 0];
        }

        $statusData = $this->scopePeriodSalesByContext(Sale::query(), $company, $activeBranch)
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

    private function getCurrentMonthSales($company, ?array $activeBranch = null): float
    {
        if (!Schema::hasTable('sales')) return 0;
        return (float) \App\Support\ReportingPeriod::preset('month_to_date')->apply(
            $this->scopeSalesByContext(Sale::query(), $company, $activeBranch),
            \App\Support\SalesReportingDate::expression()
        )->sum('total');
    }

    private function getPreviousMonthSales($company, ?array $activeBranch = null): float
    {
        if (!Schema::hasTable('sales')) return 0;
        $today = \App\Support\ReportingPeriod::today();
        $range = new \App\Support\ReportingPeriod('custom', $today->startOfMonth()->subMonth()->toDateString(), $today->startOfMonth()->subDay()->toDateString());
        return (float) $range->apply($this->scopeSalesByContext(Sale::query(), $company, $activeBranch), \App\Support\SalesReportingDate::expression())->sum('total');
    }

    private function calendarMonthlyData($query, string $date, string $aggregate, string $alias)
    {
        $month = match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(STRFTIME('%m', {$date}) AS INTEGER)",
            'pgsql' => "EXTRACT(MONTH FROM {$date})",
            default => "MONTH({$date})",
        };
        return \App\Support\ReportingPeriod::preset('year_to_date')->apply($query, $date)
            ->selectRaw("{$month} as month_num, {$aggregate} as {$alias}")->groupBy('month_num')->orderBy('month_num')->get()
            ->map(function ($row) { $row->month = \Carbon\CarbonImmutable::create(2000, (int) $row->month_num, 1)->format('F'); return $row; });
    }

    private function periodSales($company, ?array $branch): array
    {
        $periods = [];
        $selects = [];
        $bindings = [];
        $dateExpression = \App\Support\SalesReportingDate::expression();

        foreach (['today', 'week_to_date', 'month_to_date', 'year_to_date'] as $key) {
            $period = \App\Support\ReportingPeriod::preset($key);
            $conditions = [];
            $periodBindings = [];
            if ($period->from) {
                $conditions[] = $dateExpression.' >= ?';
                $periodBindings[] = $period->from;
            }
            if ($period->to) {
                $conditions[] = $dateExpression.' <= ?';
                $periodBindings[] = $period->to;
            }
            $condition = $conditions ? implode(' AND ', $conditions) : '1 = 1';
            $selects[] = "COALESCE(SUM(CASE WHEN ({$condition}) THEN total ELSE 0 END), 0) as revenue_{$key}";
            $selects[] = "COUNT(CASE WHEN ({$condition}) THEN 1 END) as orders_{$key}";
            $bindings = array_merge($bindings, $periodBindings, $periodBindings);
            $periods[$key] = $period;
        }

        $row = Schema::hasTable('sales')
            ? $this->scopeSalesByContext(Sale::query(), $company, $branch)
                ->selectRaw(implode(', ', $selects), $bindings)
                ->first()
            : null;

        $result = [];
        foreach ($periods as $key => $period) {
            $result[$key] = [
                'period' => $period,
                'revenue' => (float) ($row->{'revenue_'.$key} ?? 0),
                'orders' => (int) ($row->{'orders_'.$key} ?? 0),
            ];
        }
        return $result;
    }

    private function scopePeriodSalesByContext($query, $company, ?array $branch = null, string $table = 'sales')
    {
        return ($this->reportingPeriod ?? \App\Support\ReportingPeriod::preset('all'))->apply(
            $this->scopeSalesByContext($query, $company, $branch, $table),
            \App\Support\SalesReportingDate::expression($table),
            $table.'.created_at'
        );
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

    private function resolvedDashboardCompanyId(): int
    {
        $user = Auth::user();
        return $user ? app(\App\Support\BusinessWorkspaceResolver::class)->activeCompanyId($user) : 0;
    }

    private function shouldShowMissingWorkspaceError($user, ?Subscription $subscription): bool
    {
        if (!$user || (int) ($user->company_id ?? 0) > 0) {
            return false;
        }

        if ($subscription && (int) ($subscription->user_id ?? 0) === (int) $user->id) {
            return false;
        }

        return true;
    }

    private function assignedStaffCompany($user): ?Company
    {
        if (!$user || (int) ($user->company_id ?? 0) <= 0) {
            return null;
        }

        $role = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($role, ['super_admin', 'superadmin', 'state_manager', 'deployment_manager'], true)) {
            return null;
        }

        $company = Company::withoutGlobalScope('tenant')->find((int) $user->company_id);
        if (!$company) {
            return null;
        }

        $ownsWorkspace = in_array((int) $user->id, array_filter([
            (int) ($company->user_id ?? 0),
            (int) ($company->owner_id ?? 0),
        ]), true);

        return $ownsWorkspace ? null : $company;
    }

    private function missingWorkspaceLoginError()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('saas-login')->withErrors([
            'login' => 'No workspace is attached to this user account. Please contact your workspace administrator.',
        ]);
    }

    private function resolveDashboardCompany($user, ?string $subdomain = null, ?Subscription $subscription = null): ?Company
    {
        $tenantId = $this->resolvedDashboardCompanyId();

        if ($tenantId > 0) {
            return Company::withoutGlobalScope('tenant')->find($tenantId);
        }

        return null;
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

        $fallbackCompanyId = $this->resolvedDashboardCompanyId();
        $resolvedCompanyId = (int) (($company?->id ?? null) ?? $fallbackCompanyId);
        $targetColumn = $column ?? 'company_id';
        $qualifiedTargetColumn = str_contains($targetColumn, '.') ? $targetColumn : ($table . '.' . $targetColumn);
        $hasCompanyColumn = Schema::hasColumn($table, 'company_id');
        $hasUserColumn = Schema::hasColumn($table, 'user_id');

        if ($resolvedCompanyId <= 0) {
            if ($hasUserColumn && Auth::id()) {
                return $query->where($table . '.user_id', (int) Auth::id());
            }

            return $query->whereRaw('1 = 0');
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
        $resolvedCompanyId = (int) (($company?->id ?? null) ?: $this->resolvedDashboardCompanyId());
        
        if (!empty($company?->user_id)) {
            $ids[] = (int) $company->user_id;
        }

        if (!empty($company?->owner_id)) {
            $ids[] = (int) $company->owner_id;
        }

        if ($resolvedCompanyId > 0 && Schema::hasColumn('users', 'company_id')) {
            $memberIds = User::where('company_id', $resolvedCompanyId)->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            $ids = array_merge($ids, $memberIds);
        }

        if (empty($ids) && Auth::id()) {
            $ids[] = (int) Auth::id();
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function hasProductReorderLevelColumn(): bool
    {
        try {
            return Schema::hasTable('products') && Schema::hasColumn('products', 'reorder_level');
        } catch (\Throwable $e) {
            Log::warning('Failed to inspect products.reorder_level column; defaulting dashboard low-stock threshold.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function activeBranchContext(): array
    {
        $branchScope = strtolower(trim((string) request()->get('branch_scope', '')));
        $requestBranchId = strtolower(trim((string) request()->get('branch_id', '')));
        $allBranches = request()->boolean('all_branches')
            || $branchScope === 'all'
            || $requestBranchId === 'all'
            || session('active_branch_scope') === 'all';

        if ($allBranches) {
            // Persist the "all branches" scope in session so it survives navigation
            request()->session()->put('active_branch_scope', 'all');
            return [
                'id' => null,
                'name' => 'All Branches',
                'scope' => 'all',
            ];
        }

        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
            'scope' => 'branch',
        ];
    }

    private function scopeSalesByContext($query, $company, ?array $activeBranch = null, string $table = 'sales')
    {
        $query = $this->scopeByCompany($query, $table, $company);
        if ($table === 'sales' && Schema::hasColumn('sales', 'deleted_at')) $query->whereNull('sales.deleted_at');

        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return $query;
        }

        if (empty($activeBranch['id']) && empty($activeBranch['name'])) {
            return $query;
        }

        $branchId = (string) ($activeBranch['id'] ?? '');
        $branchName = (string) ($activeBranch['name'] ?? '');

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($table, $branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                    $sub->where($table . '.branch_id', $branchId);
                    return;
                }
                if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                    $sub->where($table . '.branch_name', $branchName);
                }
            });
        }

        return $query;
    }

    private function scopeExpensesByContext($query, $company, ?array $activeBranch = null, string $table = 'expenses')
    {
        $query = $this->scopeByCompany($query, $table, $company);

        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return $query;
        }

        if (empty($activeBranch['id']) && empty($activeBranch['name'])) {
            return $query;
        }

        $branchId = (string) ($activeBranch['id'] ?? '');
        $branchName = (string) ($activeBranch['name'] ?? '');

        return $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where($table . '.branch_id', $branchId);
                return;
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->where($table . '.branch_name', $branchName);
            }
        });
    }

}
