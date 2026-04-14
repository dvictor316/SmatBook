<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Subscription;
use App\Support\InventoryQuantity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class AnalyticsDashboardController extends Controller
{
    private array $paidSaleStatuses = ['paid', 'completed', 'success'];
    private array $outstandingSaleStatuses = ['outstanding', 'partial', 'partially_paid', 'pending'];
    private array $overdueSaleStatuses = ['overdue', 'unpaid'];

    private function scopeCompanyId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if (in_array(strtolower($user->role ?? ''), ['super_admin', 'superadmin'])) {
            return null; // super admins get global analytics
        }

        return $user->company_id ?? session('current_tenant_id');
    }

    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

        if (!$branchId && !$branchName && Schema::hasTable('settings')) {
            $companyId = $this->scopeCompanyId();
            if ($companyId) {
                $key = 'branches_json_company_' . $companyId;
                $raw = (string) (DB::table('settings')->where('key', $key)->value('value') ?? '');
                $branches = json_decode($raw, true) ?: [];
                $first = collect($branches)->first();
                if ($first) {
                    $branchId = $branchId ?: ($first['id'] ?? null);
                    $branchName = $branchName ?: ($first['name'] ?? null);
                }
            }
        }

        return [
            'id' => $branchId,
            'name' => $branchName,
        ];
    }

    private function applySalesScope($query, string $salesTable = 'sales')
    {
        $companyId = $this->scopeCompanyId();
        if ($companyId && Schema::hasColumn($salesTable, 'company_id')) {
            $query->where($salesTable . '.company_id', $companyId);
        }

        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($salesTable, $branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn($salesTable, 'branch_id')) {
                    $sub->where($salesTable . '.branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn($salesTable, 'branch_name')) {
                    $sub->orWhere($salesTable . '.branch_name', $branchName);
                }
            });
        }

        return $query;
    }

    /**
     * Display the main analytics dashboard view with all required data.
     */
    public function index()
    {
        // --- 1. Initialize ALL variables with safe defaults ---
        $totalCompanies = $activeCompanies = $inactiveCompanies = $newTodayCompanies = 0;
        $totalSales = $totalReceipts = $totalExpenses = $totalEarnings = 0.00;
        $monthlySalesData = [];
        $saleStatusData = ['labels' => ['Paid', 'Outstanding', 'Overdue'], 'data' => []];
        $productSalesLabels = [];
        $productSalesData = [];
        $recentActivity = collect(); // Initialize as empty collection to avoid undefined variable

        // --- 2. Fetch Company Metrics ---
        try {
            $totalCompanies    = Company::count();
            $activeCompanies   = Company::whereIn(DB::raw("LOWER(COALESCE(status, 'active'))"), ['active', 'trial', 'enabled'])->count();
            $inactiveCompanies = Company::whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['inactive', 'suspended', 'disabled'])->count();
            $newTodayCompanies = Company::whereDate('created_at', today())->count();
        } catch (Exception $e) {
            Log::error("Company Metrics Error: " . $e->getMessage());
        }

        // --- 3. Fetch Financial Metrics ---
        try {
            $salesBaseQuery = Sale::query();
            $saleStatusColumn = Schema::hasColumn('sales', 'payment_status') ? 'payment_status' : 'status';
            $totalSales    = (float)(clone $salesBaseQuery)
                ->whereIn(DB::raw("LOWER(COALESCE({$saleStatusColumn}, ''))"), $this->paidSaleStatuses)
                ->sum('total');
            $totalReceipts = (float)Receipt::sum('amount');
            $totalExpenses = (float)Expense::sum('amount');
            $totalEarnings = ($totalSales + $totalReceipts) - $totalExpenses;
        } catch (Exception $e) {
            Log::error("Financial Metrics Error: " . $e->getMessage());
        }

        // --- 4. Chart Data Preparation ---
        try {
            $startDate = Carbon::now()->subMonths(5)->startOfMonth();

            $monthlySalesQuery = Sale::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(total) as total_sales')
            )
            ->whereIn(DB::raw("LOWER(COALESCE(" . (Schema::hasColumn('sales', 'payment_status') ? 'payment_status' : 'status') . ", ''))"), $this->paidSaleStatuses)
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderByRaw('MIN(created_at)')
            ->get();

            $monthlySalesData = $monthlySalesQuery->map(function ($item) {
                return ['period' => $item->period, 'total_sales' => (float)$item->total_sales];
            })->toArray();

            $topProductsQuery = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->select(
                    'products.name',
                    DB::raw('SUM(CASE WHEN sale_items.total_price IS NOT NULL THEN sale_items.total_price WHEN sale_items.subtotal IS NOT NULL THEN sale_items.subtotal ELSE COALESCE(sale_items.qty, sale_items.quantity, 0) * COALESCE(sale_items.unit_price, 0) END) as total_product_sales')
                )
                ->whereIn(DB::raw("LOWER(COALESCE(" . (Schema::hasColumn('sales', 'payment_status') ? 'sales.payment_status' : 'sales.status') . ", ''))"), $this->paidSaleStatuses)
                ->groupBy('products.name')
                ->orderByDesc('total_product_sales')
                ->take(5);
            $this->applySalesScope($topProductsQuery, 'sales');
            $topProductsQuery = $topProductsQuery->get();

            $productSalesLabels = $topProductsQuery->pluck('name')->toArray();
            $productSalesData   = $topProductsQuery->pluck('total_product_sales')->map('floatval')->toArray();

            $saleStatusData = $this->getSaleStatusDataForDoughnut();

        } catch (Exception $e) {
            Log::error("Analytics Chart Data Error: " . $e->getMessage());
            $monthlySalesData   = [];
            $productSalesLabels = [];
            $productSalesData   = [];
            // Fix: initialize with default data if error occurs
            $saleStatusData = ['labels' => ['Error Loading Data'], 'data' => []];
        }

        // --- 5. Fetch Recent Data Tables & Combine Activity ---
        try {
            $recentSales = Sale::with('customer')->orderBy('created_at', 'desc')->take(5)->get();
            $recentEstimates = Estimate::with('customer')->orderBy('created_at', 'desc')->take(5)->get();

            $saleActivity = $recentSales->map(function ($item) {
                return [
                    'company_name' => $item->customer->name ?? 'N/A',
                    'description'  => 'Sale #' . $item->id . ' created (₦' . number_format($item->total, 0) . ')',
                    'created_at'   => $item->created_at,
                ];
            });

            $estimateActivity = $recentEstimates->map(function ($item) {
                return [
                    'company_name' => $item->customer->name ?? 'N/A',
                    'description'  => 'Estimate #' . $item->id . ' status: ' . ucfirst($item->status),
                    'created_at'   => $item->created_at,
                ];
            });

            $recentActivity = $saleActivity
                ->merge($estimateActivity)
                ->sortByDesc('created_at')
                ->take(6);

        } catch (Exception $e) {
            Log::error("Recent Data/Totals Error: " . $e->getMessage());
            $recentActivity = collect(); // ensure it exists even if error
        }

        // ---------------------------------------------------------
        // 6. FINAL VIEW RETURN
        // ---------------------------------------------------------
        return view('superadmin.dashboard', compact(
            'totalCompanies',
            'activeCompanies',
            'inactiveCompanies',
            'newTodayCompanies',
            'totalSales',
            'totalReceipts',
            'totalExpenses',
            'totalEarnings',
            'monthlySalesData',
            'saleStatusData',
            'productSalesLabels',
            'productSalesData',
            'recentActivity'
        ));
    }

    /**
     * Helper method to get data for the sales status doughnut chart.
     */
    private function getSaleStatusDataForDoughnut()
    {
        $paidCount      = Sale::where('status', 'paid')->count();
        $statusColumn = Schema::hasColumn('sales', 'payment_status') ? 'payment_status' : 'status';
        $paidCount = Sale::whereIn(DB::raw("LOWER(COALESCE({$statusColumn}, ''))"), $this->paidSaleStatuses)->count();
        $outstandingCount = Sale::whereIn(DB::raw("LOWER(COALESCE({$statusColumn}, ''))"), $this->outstandingSaleStatuses)->count();
        $overdueCount   = Sale::whereIn(DB::raw("LOWER(COALESCE({$statusColumn}, ''))"), $this->overdueSaleStatuses)->count();

        return [
            'labels' => ['Paid', 'Outstanding', 'Overdue'],
            'data'   => [$paidCount, $outstandingCount, $overdueCount],
        ];
    }

    public function getCompanyStats()
    {
        $companyId = $this->scopeCompanyId();

        $companies = Company::query();
        $sales = Sale::query();
        $subscriptions = Subscription::query();

        if ($companyId) {
            $companies->where('id', $companyId);
            $subscriptions->where('company_id', $companyId);
        }
        $this->applySalesScope($sales, 'sales');

        return response()->json([
            'total_companies' => $companies->count(),
            'active_companies' => (clone $companies)->where('status', 'active')->count(),
            'total_sales' => (float) $sales->sum('total'),
            'active_subscriptions' => (clone $subscriptions)->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['active', 'trial'])->count(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function getSalesAnalytics(Request $request)
    {
        $companyId = $this->scopeCompanyId();
        $days = max(7, min((int) $request->get('days', 180), 730));
        $from = now()->subDays($days)->startOfDay();

        $sales = Sale::query()->where('created_at', '>=', $from);
        if ($companyId) {
            $sales->where('company_id', $companyId);
        }
        $activeBranch = $this->getActiveBranchContext();
        if (!empty($activeBranch['id']) && Schema::hasColumn('sales', 'branch_id')) {
            $sales->where('branch_id', $activeBranch['id']);
        } elseif (!empty($activeBranch['name']) && Schema::hasColumn('sales', 'branch_name')) {
            $sales->where('branch_name', $activeBranch['name']);
        }

        $rows = $sales
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('period')->values(),
            'values' => $rows->pluck('total')->map(fn ($v) => (float) $v)->values(),
            'total' => (float) $rows->sum('total'),
        ]);
    }

    public function getInvoiceAnalytics()
    {
        $companyId = $this->scopeCompanyId();
        $sales = Sale::query();
        if ($companyId) {
            $sales->where('company_id', $companyId);
        }
        $this->applySalesScope($sales, 'sales');

        $statusColumn = Schema::hasColumn('sales', 'payment_status') ? 'payment_status' : 'status';
        $statusData = $sales
            ->select($statusColumn, DB::raw('COUNT(*) as total'))
            ->groupBy($statusColumn)
            ->pluck('total', $statusColumn);

        return response()->json([
            'labels' => $statusData->keys()->values(),
            'values' => $statusData->values()->map(fn ($v) => (int) $v)->values(),
        ]);
    }

    public function getSalesData(Request $request)
    {
        $companyId = $this->scopeCompanyId();
        $limit = max(3, min((int) $request->get('limit', 10), 50));

        $query = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select(
                'products.name',
                DB::raw('SUM(' . InventoryQuantity::saleStockUnitsExpression('sale_items', 'products') . ') as qty'),
                DB::raw('SUM(CASE WHEN sale_items.total_price IS NOT NULL THEN sale_items.total_price WHEN sale_items.subtotal IS NOT NULL THEN sale_items.subtotal ELSE COALESCE(sale_items.qty, sale_items.quantity, 0) * COALESCE(sale_items.unit_price, 0) END) as amount')
            )
            ->groupBy('products.name')
            ->orderByDesc('amount')
            ->limit($limit);
        $this->applySalesScope($query, 'sales');

        $rows = $query->get();

        return response()->json([
            'labels' => $rows->pluck('name')->values(),
            'qty' => $rows->pluck('qty')->map(fn ($v) => (int) $v)->values(),
            'amount' => $rows->pluck('amount')->map(fn ($v) => (float) $v)->values(),
        ]);
    }

    public function exportAnalytics()
    {
        $response = $this->getSalesAnalytics(request());
        $payload = $response->getData(true);

        $filename = 'sales_analytics_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($payload) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['period', 'amount']);
            foreach (($payload['labels'] ?? []) as $idx => $label) {
                fputcsv($out, [$label, $payload['values'][$idx] ?? 0]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function revenueReport()
    {
        $analytics = $this->getSalesAnalytics(request())->getData(true);

        return response()->json([
            'summary' => [
                'total_revenue' => (float) ($analytics['total'] ?? 0),
                'periods' => count($analytics['labels'] ?? []),
            ],
            'series' => $analytics,
        ]);
    }
}
