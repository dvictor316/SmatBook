<?php

namespace App\Http\Controllers;

use App\Models\{Product, Expense, Sale, Company, Subscription, User};
use Illuminate\Support\Facades\{DB, Auth, Schema, Cache, Log};
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Main Dashboard View (Multi-Tenant & Subdomain Optimized)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentHost = $request->getHost();
        $mainDomain = ltrim(config('app.domain', 'smatbook.com'), '.');

        // 1. TENANT IDENTIFICATION VIA SUBDOMAIN
        $subdomain = ($currentHost === $mainDomain) ? null : explode('.', $currentHost)[0];

        if ($subdomain) {
            $subscription = Subscription::where('domain_prefix', $subdomain)->first();
            if (!$subscription) abort(404, 'Node not found.');
            
            // Link company via subdomain/domain_prefix and fallback to subscription relation.
            $company = Company::where('subdomain', $subdomain)
                ->orWhere('domain_prefix', $subdomain)
                ->orWhere('id', $subscription->company_id)
                ->orWhere('user_id', $subscription->user_id)
                ->first();
        } else {
            $company = Company::where('id', $user->company_id)
                ->orWhere('user_id', $user->id)
                ->orWhere('owner_id', $user->id)
                ->first();
        }

        // Redirect if setup isn't finished (Handshake check)
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

        // 3. CACHED ANALYTICS (Scoped to Company)
        $cacheKey = "metrics_co_" . ($company->id ?? 'global');
        $metrics = Cache::remember($cacheKey, 300, function() use ($company) {
            $todayRevenue = $this->getTodayRevenue($company);
            $totalSales = $this->getTotalSales($company);
            $totalProfit = $this->getTotalProfit($company);
            $totalExpenses = $this->getTotalExpenses($company);
            $activeStock = $this->getTotalStock($company);
            $totalInvoices = $this->getTotalInvoices($company);
            $activeCustomers = $this->getActiveCustomers($company);
            $lowStockCount = $this->getLowStockCount($company);
            $avgOrderValue = $totalInvoices > 0 ? ($totalSales / $totalInvoices) : 0;
            $profitMargin = $totalSales > 0 ? (($totalProfit / $totalSales) * 100) : 0;

            return [
                'todayRevenue'    => $todayRevenue,
                'totalSales'      => $totalSales,
                'totalProfit'     => $totalProfit,
                'netProfit'       => $totalProfit, // alias used by pro dashboard
                'totalExpenses'   => $totalExpenses,
                'activeStock'     => $activeStock,
                'totalInvoices'   => $totalInvoices, // alias used by basic dashboard
                'activeCustomers' => $activeCustomers, // alias used by basic dashboard
                'lowStockCount'   => $lowStockCount,
                'avgOrderValue'   => $avgOrderValue,
                'profitMargin'    => $profitMargin,
                'revenueProgress' => 1000000,
                // FIX: Initialize Heatmap data to prevent undefined variable error
                'countryHeatMap'  => $this->getHeatMapData($company),
            ];
        });

     // 4. PACKAGING DATA FOR DEEP SAPPHIRE VIEW
        $monthlySalesData = $this->getMonthlySalesData($company);
        $monthlyExpenseData = $this->getMonthlyExpensesData($company);
        $monthlyProfitData = $this->getMonthlyProfitData($monthlySalesData, $monthlyExpenseData);
        $salesCountByMonth = $this->getSalesCountByMonth($company);
        $topCustomers = $this->getTopCustomers($company);

        $data = [
            'company'          => $company,
            'metrics'          => $metrics,
            'monthlySalesData' => $monthlySalesData,
            'monthlyExpenseData' => $monthlyExpenseData,
            'monthlyProfitData' => $monthlyProfitData,
            'salesCountByMonth' => $salesCountByMonth,
            'topProducts'      => $this->getTopProducts($company),
            'topCustomers'     => $topCustomers,
            'latestInvoices'   => $this->getLatestInvoices($company),
            'lowStockProducts' => $this->getLowStockProducts($company),
            'activities'       => $this->getRecentActivity($company),
            'userRole'         => $userRole,
            'permissions'      => $permissions,
            // FIX: Added '?? []' to handle missing keys in old cache
            'countryHeatMap'   => $metrics['countryHeatMap'] ?? [], 
        ];

        // 5. THEME-BASED VIEW SELECTION
        if ($userRole === 'superadmin' || $user->email === 'donvictorlive@gmail.com') {
            return view('SuperAdmin.dashboards.enterprise', $data);
        }

        $activeSubscription = Subscription::query()
            ->where(function ($q) use ($company, $user) {
                if ($company?->id && Schema::hasColumn('subscriptions', 'company_id')) {
                    $q->where('company_id', $company->id);
                }
                $q->orWhere('user_id', $user->id);
            })
            ->where('payment_status', 'paid')
            ->where('status', 'Active')
            ->latest('paid_at')
            ->latest('id')
            ->first();

        $plan = strtolower(
            $activeSubscription?->plan_name
            ?? $activeSubscription?->plan
            ?? ($company->plan ?? 'basic')
        );

        if ($plan === 'professional') {
            $plan = 'pro';
        }

        return match($plan) {
            'pro'        => view('SuperAdmin.dashboards.pro', $data),
            'enterprise' => view('SuperAdmin.dashboards.enterprise', $data),
            default      => view('SuperAdmin.dashboards.basic', $data),
        };
    }

    /**
     * Data fetcher for Global HeatMap (Enterprise Only)
     */
    private function getHeatMapData($company) {
        // Mock data or actual DB query if you track customer locations
        return [
            'Nigeria' => $this->scopeByCompany(Sale::query(), 'sales', $company)->count(),
            'USA'     => 0,
            'UK'      => 0
        ];
    }

    /* --- REST OF SCOPED HELPERS (Kept from your original logic) --- */
    private function getTodayRevenue($company) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopeByCompany(Sale::query(), 'sales', $company)
            ->whereDate('created_at', Carbon::today())
            ->sum('total') ?? 0;
    }

    private function getTotalSales($company) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopeByCompany(Sale::query(), 'sales', $company)->sum('total') ?? 0;
    }

    private function getTotalProfit($company) {
        if (!Schema::hasTable('sales')) return 0;
        $salesQuery = $this->scopeByCompany(Sale::query(), 'sales', $company);
        $totalRevenue = (clone $salesQuery)->sum('total') ?? 0;
        $totalCost    = (clone $salesQuery)->sum('purchase_price') ?? 0;
        return $totalRevenue - $totalCost;
    }

    private function getTotalExpenses($company) {
        if (!Schema::hasTable('expenses')) return 0;
        return $this->scopeByCompany(Expense::query(), 'expenses', $company)
            ->where(function ($q) {
                $q->whereRaw('LOWER(status) = ?', ['approved'])->orWhereNull('status');
            })
            ->sum('amount') ?? 0;
    }

    private function getTotalStock($company) {
        if (!Schema::hasTable('products')) return 0;
        return $this->scopeByCompany(Product::query(), 'products', $company)->sum('stock') ?? 0;
    }

    private function getMonthlySalesData($company) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeByCompany(Sale::query(), 'sales', $company)
            ->select(DB::raw('MONTHNAME(created_at) as month'), DB::raw('SUM(total) as total_sales'), DB::raw('MONTH(created_at) as month_num'))
            ->whereYear('created_at', date('Y'))
            ->groupBy('month', 'month_num')->orderBy('month_num', 'asc')->get();
    }

    private function getTopProducts($company) {
        if (!Schema::hasTable('products') || !Schema::hasTable('sale_items')) return collect();
        return $this->scopeByCompany(Product::query(), 'products', $company, 'products.company_id')
            ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->select('products.id', 'products.name', DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->groupBy('products.id', 'products.name')->orderBy('total_qty', 'desc')->take(5)->get();
    }

    private function getLowStockProducts($company) {
        if (!Schema::hasTable('products')) return collect();
        return $this->scopeByCompany(Product::query(), 'products', $company)
            ->select('id', 'name', 'stock')
            ->where('stock', '<=', 15)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();
    }

    private function getLowStockCount($company) {
        if (!Schema::hasTable('products')) return 0;
        return $this->scopeByCompany(Product::query(), 'products', $company)
            ->where('stock', '<=', 15)
            ->count();
    }

    private function getTotalInvoices($company) {
        if (!Schema::hasTable('sales')) return 0;
        return $this->scopeByCompany(Sale::query(), 'sales', $company)->count();
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
                return DB::table('customers')->count();
            }
        }
        if (Schema::hasColumn('users', 'company_id')) {
            return User::where('company_id', $company->id ?? 0)->count();
        }
        return 0;
    }

    private function getLatestInvoices($company) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeByCompany(Sale::query(), 'sales', $company)
            ->select('id', 'order_number', 'customer_name', 'total', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    private function getRecentActivity($company) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeByCompany(Sale::query(), 'sales', $company)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn($s) => (object)['id' => $s->id, 'description' => "Order #{$s->order_number} confirmed", 'created_at' => $s->created_at]);
    }

    private function getMonthlyExpensesData($company) {
        if (!Schema::hasTable('expenses')) return collect();
        return $this->scopeByCompany(Expense::query(), 'expenses', $company)
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

    private function getSalesCountByMonth($company) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeByCompany(Sale::query(), 'sales', $company)
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

    private function getTopCustomers($company) {
        if (!Schema::hasTable('sales')) return collect();
        return $this->scopeByCompany(Sale::query(), 'sales', $company)
            ->whereNotNull('customer_name')
            ->select('customer_name', DB::raw('SUM(total) as total_spend'), DB::raw('COUNT(*) as invoices_count'))
            ->groupBy('customer_name')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();
    }

    private function scopeByCompany($query, string $table, $company, ?string $column = null)
    {
        if (!$company?->id) {
            return $query;
        }

        if (!Schema::hasTable($table)) {
            return $query;
        }

        $targetColumn = $column ?? 'company_id';
        $hasCompanyColumn = Schema::hasColumn($table, 'company_id');
        $hasUserColumn = Schema::hasColumn($table, 'user_id');

        // Primary scope: direct company link.
        if ($hasCompanyColumn) {
            // Legacy fallback: include records created by company users where company_id is null.
            if ($hasUserColumn) {
                $userIds = $this->companyUserIds($company);
                return $query->where(function ($q) use ($targetColumn, $company, $table, $userIds) {
                    $q->where($targetColumn, $company->id)
                      ->orWhere(function ($legacy) use ($table, $userIds) {
                          $legacy->whereNull($table . '.company_id')
                                 ->whereIn($table . '.user_id', $userIds);
                      });
                });
            }

            return $query->where($targetColumn, $company->id);
        }

        // Fallback for legacy tables with no company_id.
        if ($hasUserColumn) {
            return $query->whereIn($table . '.user_id', $this->companyUserIds($company));
        }

        return $query;
    }

    private function companyUserIds($company): array
    {
        $ids = [];

        if (!empty($company->user_id)) {
            $ids[] = (int) $company->user_id;
        }

        if (!empty($company->owner_id)) {
            $ids[] = (int) $company->owner_id;
        }

        if (Schema::hasColumn('users', 'company_id')) {
            $memberIds = User::where('company_id', $company->id)->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            $ids = array_merge($ids, $memberIds);
        }

        if (empty($ids) && Auth::id()) {
            $ids[] = (int) Auth::id();
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
