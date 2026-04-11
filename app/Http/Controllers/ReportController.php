<?php

namespace App\Http\Controllers;

// 1. Laravel Framework Shorthands
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

// 2. Third Party Packages
use Barryvdh\DomPDF\Facade\Pdf; 
use Carbon\Carbon;

// 3. Your Custom Models
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Account;
use App\Models\PurchaseReturn;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Setting;
use App\Support\LedgerService;
use App\Support\AppMailer;



    class ReportController extends Controller
    {
        private function calculateReportSideBalances(float $amount, bool $isDebitNormal): array
        {
            if ($isDebitNormal) {
                return $amount >= 0
                    ? ['debit' => $amount, 'credit' => 0.0]
                    : ['debit' => 0.0, 'credit' => abs($amount)];
            }

            return $amount >= 0
                ? ['debit' => 0.0, 'credit' => $amount]
                : ['debit' => abs($amount), 'credit' => 0.0];
        }

        private function ignoredAppliedPaymentStatuses(): array
        {
            return ['failed', 'cancelled', 'pending approval'];
        }

        private function resolveAppliedPaymentAmount(Sale $sale): ?float
        {
            if (!Schema::hasTable('payments') || empty($sale->id)) {
                return null;
            }

            $ignoredStatuses = $this->ignoredAppliedPaymentStatuses();

            if ($sale->relationLoaded('payments')) {
                $payments = collect($sale->payments)->filter(function ($payment) use ($ignoredStatuses) {
                    $status = strtolower(trim((string) ($payment->status ?? '')));
                    return !in_array($status, $ignoredStatuses, true);
                });

                if ($payments->isEmpty()) {
                    return null;
                }

                return (float) $payments->sum(fn ($payment) => (float) ($payment->amount ?? 0));
            }

            $paymentQuery = DB::table('payments')->where('sale_id', $sale->id);
            $this->applyTenantScope($paymentQuery, 'payments');

            if (Schema::hasColumn('payments', 'status')) {
                foreach ($ignoredStatuses as $ignoredStatus) {
                    $paymentQuery->whereRaw('LOWER(COALESCE(status, "")) <> ?', [$ignoredStatus]);
                }
            }

            $paymentSum = (float) $paymentQuery->sum('amount');

            return $paymentSum > 0 ? $paymentSum : null;
        }

        private function normalizeInvoiceFinancials(Sale $sale): array
        {
            $total = max(0, (float) ($sale->total ?? 0));
            $appliedPaymentAmount = $this->resolveAppliedPaymentAmount($sale);
            $storedPaid = max(0, (float) ($sale->amount_paid ?? $sale->paid ?? 0));
            $storedBalanceRaw = $sale->balance;
            $hasStoredBalance = $storedBalanceRaw !== null && $storedBalanceRaw !== '';
            $storedBalance = $hasStoredBalance ? max(0, (float) $storedBalanceRaw) : null;
            $computedBalance = max(0, $total - $storedPaid);

            if ($appliedPaymentAmount !== null) {
                $effectivePaid = min($total, max(0, $appliedPaymentAmount));
                $effectiveBalance = max(0, $total - $effectivePaid);
            } else {
                $effectiveBalance = $hasStoredBalance ? min($total, $storedBalance) : $computedBalance;
                $effectivePaid = min($total, max(0, $total - $effectiveBalance));

                if (!$hasStoredBalance && $storedPaid > 0) {
                    $effectivePaid = min($total, $storedPaid);
                    $effectiveBalance = max(0, $total - $effectivePaid);
                }
            }

            $effectiveStatus = $effectiveBalance <= 0.0001
                ? 'paid'
                : ($effectivePaid > 0.0001 ? 'partial' : 'unpaid');

            return [
                'total' => $total,
                'paid' => $effectivePaid,
                'balance' => $effectiveBalance,
                'status' => $effectiveStatus,
            ];
        }

        // ... rest of the code
        private function applyTenantScope($query, string $table)
        {
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);

            if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
                $query->where("{$table}.company_id", $companyId);
            } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
                $query->where("{$table}.user_id", $userId);
            } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
                $query->where("{$table}.created_by", $userId);
            }

            return $query;
        }

        private function getActiveBranchContext(): array
        {
            $branchScope = (string) request()->get('branch_scope', '');
            $requestBranchId = (string) request()->get('branch_id', '');
            $allBranches = request()->boolean('all_branches')
                || strtolower($branchScope) === 'all'
                || strtolower($requestBranchId) === 'all';

            if ($allBranches) {
                return [
                    'id' => null,
                    'name' => null,
                    'scope' => 'all',
                ];
            }

            $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
            $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

            if ($requestBranchId !== '') {
                $branchId = $requestBranchId;
                $branchName = null;
            }

            if ((!$branchId || !$branchName) && Schema::hasTable('settings')) {
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
                if ($companyId > 0) {
                    $key = 'branches_json_company_' . $companyId;
                    $raw = (string) (DB::table('settings')->where('key', $key)->value('value') ?? '');
                    $branches = json_decode($raw, true) ?: [];

                    if ($branchId) {
                        $match = collect($branches)->firstWhere('id', $branchId);
                        $branchName = $branchName ?: ($match['name'] ?? null);
                    } else {
                        $first = collect($branches)->first();
                        if ($first) {
                            $branchId = $branchId ?: ($first['id'] ?? null);
                            $branchName = $branchName ?: ($first['name'] ?? null);
                        }
                    }
                }
            }

            return [
                'id' => $branchId,
                'name' => $branchName,
                'scope' => 'branch',
            ];
        }

        private function applySalesScope($query, string $salesTable = 'sales')
        {
            $this->applyTenantScope($query, $salesTable);
            $this->applySaleBranchFilter($query, $salesTable);

            return $query;
        }

        private function applySaleBranchFilter($query, string $salesTable = 'sales')
        {
            $branchId = trim((string) ($this->getActiveBranchContext()['id'] ?? ''));
            $branchName = trim((string) ($this->getActiveBranchContext()['name'] ?? ''));

            if ($branchId === '' && $branchName === '') {
                return $query;
            }

        return $query->where(function ($sub) use ($salesTable, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn('sales', 'branch_id')) {
                $sub->where("{$salesTable}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn('sales', 'branch_name')) {
                $sub->orWhere("{$salesTable}.branch_name", $branchName);
            }

                if ($branchName !== '') {
                    $sub->orWhereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(COALESCE({$salesTable}.payment_details, '{}'), '$.branch_name')) = ?",
                        [$branchName]
                    )->orWhereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(COALESCE({$salesTable}.payment_details, '{}'), '$.branch.name')) = ?",
                        [$branchName]
                    );
                }
            });
        }

        private function applyPaymentBranchFilter($query, string $paymentsTable = 'payments')
        {
            $activeBranch = $this->getActiveBranchContext();
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));

            if ($branchId === '' && $branchName === '') {
                return $query;
            }

            return $query->where(function ($sub) use ($paymentsTable, $branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn('payments', 'branch_id')) {
                    $sub->where("{$paymentsTable}.branch_id", $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('payments', 'branch_name')) {
                    $sub->orWhere("{$paymentsTable}.branch_name", $branchName);
                }

                if (Schema::hasTable('sales')) {
                    $matchingSales = $this->scopedTable('sales')->select('sales.id');
                    $this->applySalesScope($matchingSales, 'sales');

                    $sub->orWhereIn("{$paymentsTable}.sale_id", $matchingSales);
                }
            });
        }

        private function applyInventoryHistoryBranchFilter($query, string $historyTable = 'inventory_history')
        {
            $branchName = trim((string) ($this->getActiveBranchContext()['name'] ?? ''));

            if ($branchName === '') {
                return $query;
            }

            return $query->where(function ($sub) use ($historyTable, $branchName) {
                if (Schema::hasColumn('inventory_history', 'branch_name')) {
                    $sub->where("{$historyTable}.branch_name", $branchName);
                }

                $sub->orWhere("{$historyTable}.reference", 'like', '%' . $branchName . '%');
            });
        }

        private function applyGenericBranchFilter($query, string $table)
        {
            $activeBranch = $this->getActiveBranchContext();
            if (($activeBranch['scope'] ?? 'branch') === 'all') {
                return $query;
            }

            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));

            if ($branchId === '' && $branchName === '') {
                return $query;
            }

            $query->where(function ($sub) use ($table, $branchId, $branchName) {
                if (Schema::hasColumn($table, 'branch_id') && $branchId !== '') {
                    $sub->where("{$table}.branch_id", $branchId);
                }
                if (Schema::hasColumn($table, 'branch_name') && $branchName !== '') {
                    $sub->orWhere("{$table}.branch_name", $branchName);
                }
            });

            return $query;
        }

        private function scopedTable(string $table)
        {
            $query = DB::table($table);
            $this->applyTenantScope($query, $table);
            $this->applyGenericBranchFilter($query, $table);

            return $query;
        }

        public function index(Request $request)
    {
        $activeBranch = $this->getActiveBranchContext();
        // 1. Parse Dates
        $start = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : null;
        $end = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : null;

        $accountsQuery = \App\Models\Account::query();
        $this->applyTenantScope($accountsQuery, 'accounts');
        $accountsBase = $accountsQuery->get();
        $accountIds = $accountsBase->pluck('id')->all();

        if (!$start || !$end) {
            $latestTxnQuery = \App\Models\Transaction::query();
            if (!empty($accountIds)) {
                $latestTxnQuery->whereIn('account_id', $accountIds);
            }
            $latestTxnDate = $latestTxnQuery->max('transaction_date');

            $effectiveEnd = $latestTxnDate
                ? \Carbon\Carbon::parse($latestTxnDate)->endOfDay()
                : \Carbon\Carbon::now()->endOfDay();

            $end = $end ?: $effectiveEnd;
            $start = $start ?: $end->copy()->startOfMonth();
        }

        // 2. Fetch and Map Data as ARRAYS (Removed (object) cast)
        // Trial Balance is an "as-of" report; include all transactions up to end date.
        $txnTotals = \App\Models\Transaction::selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->when(!empty($accountIds), function ($query) use ($accountIds) {
                $query->whereIn('account_id', $accountIds);
            })
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $openingTotals = ['debit' => 0.0, 'credit' => 0.0];

        $accounts = $accountsBase
            ->map(function($account) use ($txnTotals, &$openingTotals) {
                $totals = $txnTotals->get($account->id);
                $totalDebit = (float) ($totals->total_debit ?? 0);
                $totalCredit = (float) ($totals->total_credit ?? 0);
                $openingBalance = (float) ($account->opening_balance ?? 0);

                $debitBalance = 0;
                $creditBalance = 0;

                $normalizedType = strtolower(trim((string) ($account->type ?? '')));

                $isDebitNormal = in_array($normalizedType, ['asset', 'expense'], true);

                if (abs($openingBalance) > 0.0001) {
                    $openingSide = $this->calculateReportSideBalances($openingBalance, $isDebitNormal);
                    $openingTotals['debit'] += $openingSide['debit'];
                    $openingTotals['credit'] += $openingSide['credit'];
                }

                $netBalance = $isDebitNormal
                    ? $openingBalance + $totalDebit - $totalCredit
                    : $openingBalance + $totalCredit - $totalDebit;

                $netSide = $this->calculateReportSideBalances($netBalance, $isDebitNormal);
                $debitBalance = $netSide['debit'];
                $creditBalance = $netSide['credit'];

                // Return a plain ARRAY to prevent the "stdClass" error
                $hasActivity = ($totalDebit > 0) || ($totalCredit > 0) || (abs($openingBalance) > 0);

                return [
                    'code' => $account->code ?? 'N/A',
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                    'has_activity' => $hasActivity,
                ];
            })
            ->filter(fn($acc) => $acc['has_activity'])
            ->sortBy('code')
            ->values();

        $openingDifference = round($openingTotals['debit'] - $openingTotals['credit'], 2);

        if (abs($openingDifference) >= 0.01) {
            $accounts->push([
                'code' => 'SYS-OPENING-EQUITY',
                'name' => 'Opening Balance Equity',
                'type' => 'Equity',
                'debit_balance' => $openingDifference < 0 ? abs($openingDifference) : 0.0,
                'credit_balance' => $openingDifference > 0 ? abs($openingDifference) : 0.0,
                'has_activity' => true,
            ]);
        }

        $accounts = $accounts->sortBy('code')->values();

        // 3. Totals
        $totalDebits = $accounts->sum('debit_balance');
        $totalCredits = $accounts->sum('credit_balance');
        
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        return view('Reports.Reports.trial-balance', compact(
            'startDate', 'endDate', 'accounts', 'totalDebits', 'totalCredits', 'activeBranch'
        ));
    }
        /**
         * Helper to apply Date, Search filters and Paginate (10 per page)
         */
        private function process_report($query, Request $request, $dateColumn = 'created_at', $searchColumns = [])
        {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween($dateColumn, [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            if ($request->filled('search') && !empty($searchColumns)) {
                $query->where(function($q) use ($searchColumns, $request) {
                    foreach ($searchColumns as $column) {
                        $q->orWhere($column, 'like', '%' . $request->search . '%');
                    }
                });
            }

            return $query->orderBy($dateColumn, 'desc')->paginate(10);
        }

        /**
         * Helper to return views with 'Reports.Reports' prefix
         */
        private function renderReportView($viewName, $data = [])
        {
            $fullView = 'Reports.Reports.' . trim((string) $viewName, '.');
            if (view()->exists($fullView)) {
                return view($fullView, $data);
            }

            Log::warning('Missing report view fallback used', [
                'requested_view' => $viewName,
                'resolved_view'  => $fullView,
            ]);

            return view('Reports.Reports.profit-loss-list', $data);
        }


public function purchaseReport(Request $request)
{
    return $this->purchase_report($request);
}

public function purchase_report(Request $request) 
{
    $activeBranch = $this->getActiveBranchContext();
    $currentSubdomain = request()->route('subdomain') ?? 'admin';
    $routeParams = ['subdomain' => $currentSubdomain];

    $purchases = collect();
    $totalSum = 0;
    $hasPurchaseRows = false;

    if (Schema::hasTable('purchases')) {
        $purchaseRefColumn = Schema::hasColumn('purchases', 'purchase_no') ? 'purchase_no' : 'id';
        $purchaseAmountColumn = Schema::hasColumn('purchases', 'total_amount') ? 'total_amount' : (Schema::hasColumn('purchases', 'amount') ? 'amount' : null);
        $purchaseDateColumn = Schema::hasColumn('purchases', 'purchase_date') ? 'purchase_date' : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');
        $purchaseStatusColumn = Schema::hasColumn('purchases', 'status') ? 'status' : null;

        $supplierNameExpression = DB::raw("'N/A' as CompanyName");
        $supplierSearchColumn = null;

        if (
            Schema::hasTable('suppliers') &&
            Schema::hasColumn('purchases', 'supplier_id') &&
            Schema::hasColumn('suppliers', 'id')
        ) {
            if (Schema::hasColumn('suppliers', 'name')) {
                $supplierNameExpression = DB::raw("COALESCE(suppliers.name, 'N/A') as CompanyName");
                $supplierSearchColumn = 'suppliers.name';
            } elseif (Schema::hasColumn('suppliers', 'supplier_name')) {
                $supplierNameExpression = DB::raw("COALESCE(suppliers.supplier_name, 'N/A') as CompanyName");
                $supplierSearchColumn = 'suppliers.supplier_name';
            } elseif (Schema::hasColumn('suppliers', 'company_name')) {
                $supplierNameExpression = DB::raw("COALESCE(suppliers.company_name, 'N/A') as CompanyName");
                $supplierSearchColumn = 'suppliers.company_name';
            }
        }

        if ($purchaseAmountColumn) {
            $query = \App\Models\Purchase::query();
            $this->applyTenantScope($query, 'purchases');

            if (
                Schema::hasTable('suppliers') &&
                Schema::hasColumn('purchases', 'supplier_id') &&
                Schema::hasColumn('suppliers', 'id')
            ) {
                $query->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id');
            }

            $query->select([
                DB::raw("COALESCE(purchases.{$purchaseRefColumn}, CONCAT('PUR-', purchases.id)) as Reference"),
                $supplierNameExpression,
                Schema::hasColumn('purchases', 'branch_name')
                    ? DB::raw("COALESCE(purchases.branch_name, 'Workspace Default') as BranchName")
                    : DB::raw("'Workspace Default' as BranchName"),
                DB::raw("ABS(COALESCE(purchases.{$purchaseAmountColumn}, 0)) as Amount"),
                DB::raw("purchases.{$purchaseDateColumn} as Date"),
                $purchaseStatusColumn
                    ? DB::raw("COALESCE(purchases.{$purchaseStatusColumn}, 'received') as Type")
                    : DB::raw("'received' as Type"),
                'purchases.id',
            ]);

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween("purchases.{$purchaseDateColumn}", [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ]);
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($q) use ($search, $purchaseRefColumn, $supplierSearchColumn) {
                    $q->where("purchases.{$purchaseRefColumn}", 'like', '%' . $search . '%');
                    if ($supplierSearchColumn) {
                        $q->orWhere($supplierSearchColumn, 'like', '%' . $search . '%');
                    }
                });
            }

            if (!empty($activeBranch['id']) || !empty($activeBranch['name'])) {
                $query->where(function ($sub) use ($activeBranch) {
                    if (!empty($activeBranch['id']) && Schema::hasColumn('purchases', 'branch_id')) {
                        $sub->where('purchases.branch_id', $activeBranch['id']);
                    }
                    if (!empty($activeBranch['name']) && Schema::hasColumn('purchases', 'branch_name')) {
                        $sub->orWhere('purchases.branch_name', $activeBranch['name']);
                    }
                });
            }

            $hasPurchaseRows = (clone $query)->exists();

            if ($hasPurchaseRows) {
                $purchases = $query->orderByDesc("purchases.{$purchaseDateColumn}")->paginate(20);
                $totalSum = (float) ((clone $query)->sum(DB::raw("ABS(COALESCE(purchases.{$purchaseAmountColumn}, 0))")) ?? 0);
            }
        }
    }

    if (!$hasPurchaseRows && Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
        $historyQuery = $this->scopedTable('inventory_history')
            ->join('products', 'inventory_history.product_id', '=', 'products.id')
            ->select([
                DB::raw("CONCAT('HIST-IN-', inventory_history.id) as Reference"),
                DB::raw("'Inventory History' as CompanyName"),
                'inventory_history.branch_name as BranchName',
                DB::raw('COALESCE(inventory_history.quantity, 0) * COALESCE(products.purchase_price, products.price, 0) as Amount'),
                'inventory_history.created_at as Date',
                DB::raw("'received' as Type"),
                'inventory_history.id as id',
            ])
            ->whereRaw("LOWER(COALESCE(inventory_history.type, '')) = 'in'");

        $this->applyTenantScope($historyQuery, 'products');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $historyQuery->whereBetween('inventory_history.created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $historyQuery->where(function ($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%')
                    ->orWhere('products.sku', 'like', '%' . $search . '%')
                    ->orWhere('inventory_history.id', 'like', '%' . $search . '%');
            });
        }

        $purchases = $historyQuery->orderByDesc('inventory_history.created_at')->paginate(20);
        $totalSum = (clone $historyQuery)->sum(DB::raw('COALESCE(inventory_history.quantity, 0) * COALESCE(products.purchase_price, products.price, 0)'));
    } elseif (!$hasPurchaseRows) {
        $purchases = new LengthAwarePaginator([], 0, 20, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
        $totalSum = 0;
    }

    // 6. Return View
    return view('Reports.Reports.purchase-report', compact(
        'purchases', 
        'totalSum',
        'routeParams',
        'activeBranch'
    ));
}

        public function showProfitLossReport()
        {
            return $this->profit_loss_list(request());
        }

        /** 1. Expense Report **/
        public function expense_report(Request $request)
        {
            $query = $this->scopedTable('expenses')
                ->leftJoin('users', 'expenses.created_by', '=', 'users.id')
                ->select(
                    'expenses.*',
                    DB::raw("COALESCE(expenses.category, 'General') as category_name"),
                    DB::raw("COALESCE(users.name, 'System') as user_name")
                );

            if ($request->filled('status')) {
                $status = strtolower((string) $request->status);
                $query->where(function ($q) use ($status) {
                    $q->whereRaw('LOWER(expenses.status) = ?', [$status])
                        ->orWhereRaw('LOWER(expenses.payment_status) = ?', [$status]);
                });
            }

            $expenses = $this->process_report(
                $query,
                $request,
                'expenses.created_at',
                ['expenses.company_name', 'expenses.reference', 'expenses.notes', 'expenses.category', 'users.name']
            );
            return $this->renderReportView('expense-report', compact('expenses'));
        }
    public function income_report(Request $request)
    {
        $fromDate = $request->input('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $request->input('to_date') ?: now()->toDateString();
        $prevFrom = \Carbon\Carbon::parse($fromDate)->subYear()->toDateString();
        $prevTo = \Carbon\Carbon::parse($toDate)->subYear()->toDateString();

        $fetchData = function($start, $end) {
            $salesDateColumn = Schema::hasColumn('sales', 'order_date')
                ? 'order_date'
                : (Schema::hasColumn('sales', 'date') ? 'date' : 'created_at');

            $sales = $this->scopedTable('sales')->select([
                DB::raw("DATE({$salesDateColumn}) as log_date"),
                DB::raw('total as amount'),
                DB::raw("'Inflow' as type"),
                DB::raw("'Sales' as label")
            ])->whereBetween($salesDateColumn, [$start.' 00:00:00', $end.' 23:59:59']);
            $this->applySalesScope($sales, 'sales');

            $other = $this->scopedTable('payments')->select([
                DB::raw('DATE(created_at) as log_date'),
                DB::raw('amount as amount'),
                DB::raw("'Inflow' as type"),
                DB::raw("'Misc' as label")
            ])->where(function ($query) {
                $query->whereNull('sale_id')
                    ->orWhere('payment_method', '!=', 'Sales Payment');
            })
            ->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
            $this->applyPaymentBranchFilter($other, 'payments');

            $exp = $this->scopedTable('expenses')->select([
                DB::raw('DATE(created_at) as log_date'),
                DB::raw('amount as amount'),
                DB::raw("'Outflow' as type"),
                DB::raw("COALESCE(category, 'Expense') as label")
            ])->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
            $this->applyTenantScope($exp, 'expenses');
            $this->applyGenericBranchFilter($exp, 'expenses');

            return $sales->unionAll($other)->unionAll($exp);
        };

        $currentUnion = $fetchData($fromDate, $toDate);
        $results = DB::table(DB::query()->fromSub($currentUnion, 'cf'))
            ->select(['log_date', 
                DB::raw('SUM(CASE WHEN type="Inflow" THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN type="Outflow" THEN amount ELSE 0 END) as total_out'),
                DB::raw('GROUP_CONCAT(DISTINCT label SEPARATOR ", ") as labels')
            ])->groupBy('log_date')->orderBy('log_date', 'desc')->get();

        $prevIn = DB::table(DB::query()->fromSub($fetchData($prevFrom, $prevTo), 'pv'))
                    ->where('type', 'Inflow')->sum('amount');
        
        $tIn = $results->sum('total_in');
        $tOut = $results->sum('total_out');
        $growth = ($prevIn > 0) ? (($tIn - $prevIn) / $prevIn) * 100 : 0;

        $incomereports = collect($results)->map(function($item) {
            $in = (float)$item->total_in; $out = (float)$item->total_out;
            return [
                'Date' => \Carbon\Carbon::parse($item->log_date)->format('d M y'),
                'TypeLabel' => $item->labels,
                'IncomeAmount' => $in,
                'OutflowAmount' => $out,
                'NetProfit' => $in - $out,
                'Margin' => ($in > 0) ? round((($in - $out) / $in) * 100, 1) : 0,
            ];
        });

        return $this->renderReportView('income-report', compact('incomereports', 'fromDate', 'toDate', 'tIn', 'tOut', 'growth'));
    }
        /** 3. Low Stock Report **/
    public function low_stock_report(Request $request)
        {
            // 1. Setup safety defaults
            $threshold = $request->get('min_qty', 15);
            $target = $request->get('target_qty', 100);

            // 2. Query actual DB columns confirmed via MySQL: 'name' and 'stock'
            $products = $this->scopedTable('products')
                ->select(['id', 'name', 'sku', 'stock', 'purchase_price', 'unit_type', 'reorder_level', 'reorder_quantity'])
                ->whereRaw('stock <= COALESCE(NULLIF(reorder_level, 0), ?)', [$threshold])
                ->orderBy('stock', 'asc')
                ->get();

            return view('Reports.Reports.low-stock-report', compact('products', 'threshold', 'target'));
        }

        // Email AJAX Logic
        public function send_low_stock_email(Request $request)
        {
            $threshold = $request->get('min_qty', 15);
            
            $products = $this->scopedTable('products')
                ->where('stock', '<=', $threshold)
                ->get();

            if ($products->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No low stock items found to report.'], 400);
            }

            try {
                // Replace with actual recipient email
                Mail::to('admin@yourcompany.com')->send(new \App\Mail\LowStockReportMail($products));
                return response()->json(['status' => 'success', 'message' => 'Email sent successfully!']);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => 'Mail Error: ' . $e->getMessage()], 500);
            }
        }


    public function payment_report(Request $request)
    {
        if (!Schema::hasTable('payments')) {
            $payments = new LengthAwarePaginator([], 0, 25, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            $totalAmount = 0;
            $activeBranch = $this->getActiveBranchContext();
            $methodOptions = collect();
            $statusOptions = collect();

            return view('Reports.Reports.payment-report', compact('payments', 'totalAmount', 'activeBranch', 'methodOptions', 'statusOptions'));
        }

        $query = Payment::with($this->paymentReportRelations());
        $activeBranch = $this->getActiveBranchContext();

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->scopePaymentsForActor($query);
        $this->applyPaymentBranchFilter($query, 'payments');

        $methodOptions = Payment::query()
            ->select('method')
            ->whereNotNull('method')
            ->distinct()
            ->orderBy('method')
            ->pluck('method');
        $statusOptions = Payment::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $totalAmount = (clone $query)->sum('amount');
        
        // Increased to 25 for better report viewing, preserved filters
        $payments = $query->orderBy('created_at', 'asc')->paginate(25)->withQueryString();
        $payments->getCollection()->transform(function ($payment) {
            $payment->resolved_status = $this->resolvePaymentStatus($payment);
            $payment->resolved_channel = $this->resolvePaymentChannel($payment);
            return $payment;
        });

        return view('Reports.Reports.payment-report', compact('payments', 'totalAmount', 'activeBranch', 'methodOptions', 'statusOptions'));
    }
    public function process_report_data(Request $request)
    {
        return $this->process_report($this->scopedTable('payments'), $request, 'created_at', ['reference_no', 'payment_method']);
    }

 
 public function paymentSummary(Request $request)
{
    $activeBranch = $this->getActiveBranchContext();
    if (!Schema::hasTable('payments')) {
        $payments = new LengthAwarePaginator([], 0, 10, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
        $totalRevenue = 0;
        $summary = [
            'total_transactions' => 0,
            'completed_count' => 0,
            'pending_count' => 0,
            'partial_count' => 0,
            'failed_count' => 0,
            'completed_amount' => 0,
            'pending_amount' => 0,
            'partial_amount' => 0,
            'average_payment' => 0,
            'largest_payment' => 0,
            'top_method' => 'N/A',
            'top_channel' => 'N/A',
        ];
        $methodOptions = collect();
        $statusOptions = collect(['Completed', 'Pending', 'Partial', 'Failed', 'Cancelled']);

        return view('Reports.payment-summary', compact('payments', 'totalRevenue', 'summary', 'methodOptions', 'statusOptions', 'activeBranch'));
    }

    $paymentColumns = $this->paymentReportColumns();
    $baseQuery = Payment::with($this->paymentReportRelations());
    $this->scopePaymentsForActor($baseQuery);

    $branchQuery = clone $baseQuery;
    $this->applyPaymentBranchFilter($branchQuery, 'payments');
    $query = (clone $branchQuery)->exists() ? $branchQuery : $baseQuery;

    if ($request->filled('search')) {
        $search = trim((string) $request->search);
        $query->where(function($q) use ($search, $paymentColumns) {
            $hasPaymentSearch = false;

            foreach (['payment_id', 'reference', 'note', 'method', 'status'] as $column) {
                if (!empty($paymentColumns[$column])) {
                    if (!$hasPaymentSearch) {
                        $q->where($column, 'like', "%$search%");
                        $hasPaymentSearch = true;
                    } else {
                        $q->orWhere($column, 'like', "%$search%");
                    }
                }
            }

            if (Schema::hasTable('sales')) {
                if ($hasPaymentSearch) {
                    $q->orWhereHas('sale', function ($saleQuery) use ($search) {
                        if (Schema::hasColumn('sales', 'invoice_no')) {
                            $saleQuery->where('invoice_no', 'like', "%$search%");
                        }
                        if (Schema::hasColumn('sales', 'order_number')) {
                            $saleQuery->orWhere('order_number', 'like', "%$search%");
                        }
                        if (Schema::hasColumn('sales', 'customer_name')) {
                            $saleQuery->orWhere('customer_name', 'like', "%$search%");
                        }
                    });
                } else {
                    $q->whereHas('sale', function ($saleQuery) use ($search) {
                        if (Schema::hasColumn('sales', 'invoice_no')) {
                            $saleQuery->where('invoice_no', 'like', "%$search%");
                        }
                        if (Schema::hasColumn('sales', 'order_number')) {
                            $saleQuery->orWhere('order_number', 'like', "%$search%");
                        }
                        if (Schema::hasColumn('sales', 'customer_name')) {
                            $saleQuery->orWhere('customer_name', 'like', "%$search%");
                        }
                    });
                }
            }
        });
    }

    if ($request->filled('from')) {
        $query->whereDate('created_at', '>=', $request->from);
    }
    if ($request->filled('to')) {
        $query->whereDate('created_at', '<=', $request->to);
    }
    if ($request->filled('method') && !empty($paymentColumns['method'])) {
        $query->where('method', $request->method);
    }
    if ($request->filled('status')) {
        $status = strtolower(trim((string) $request->status));
        $query->where(function ($q) use ($status) {
            $hasStatusClause = false;

            if (Schema::hasColumn('payments', 'status')) {
                $q->whereRaw("LOWER(COALESCE(status, '')) = ?", [$status]);
                $hasStatusClause = true;
            }

            if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'payment_status')) {
                if ($hasStatusClause) {
                    $q->orWhereHas('sale', function ($saleQuery) use ($status) {
                        $saleQuery->whereRaw("LOWER(COALESCE(payment_status, '')) = ?", [$status]);
                    });
                } else {
                    $q->whereHas('sale', function ($saleQuery) use ($status) {
                        $saleQuery->whereRaw("LOWER(COALESCE(payment_status, '')) = ?", [$status]);
                    });
                }
            }
        });
    }

    $filteredPayments = (clone $query)->orderBy('created_at', 'desc')->get();
    $filteredPayments->transform(function ($payment) {
        $payment->resolved_status = $this->resolvePaymentStatus($payment);
        $payment->resolved_channel = $this->resolvePaymentChannel($payment);
        return $payment;
    });

    $totalRevenue = (float) $filteredPayments->sum('amount');
    $summary = [
        'total_transactions' => $filteredPayments->count(),
        'completed_count' => $filteredPayments->where('resolved_status', 'Completed')->count(),
        'pending_count' => $filteredPayments->where('resolved_status', 'Pending')->count(),
        'partial_count' => $filteredPayments->where('resolved_status', 'Partial')->count(),
        'failed_count' => $filteredPayments->whereIn('resolved_status', ['Failed', 'Cancelled'])->count(),
        'completed_amount' => (float) $filteredPayments->where('resolved_status', 'Completed')->sum('amount'),
        'pending_amount' => (float) $filteredPayments->where('resolved_status', 'Pending')->sum('amount'),
        'partial_amount' => (float) $filteredPayments->where('resolved_status', 'Partial')->sum('amount'),
        'average_payment' => $filteredPayments->count() > 0 ? ((float) $filteredPayments->sum('amount') / $filteredPayments->count()) : 0,
        'largest_payment' => (float) ($filteredPayments->max('amount') ?? 0),
        'top_method' => (string) ($filteredPayments->groupBy(fn ($payment) => $payment->method ?: 'Unknown')->sortByDesc->count()->keys()->first() ?? 'N/A'),
        'top_channel' => (string) ($filteredPayments->groupBy(fn ($payment) => $payment->resolved_channel ?: 'Not specified')->sortByDesc->count()->keys()->first() ?? 'N/A'),
    ];

    $methodOptions = !empty($paymentColumns['method'])
        ? (clone $baseQuery)
            ->get()
            ->pluck('method')
            ->filter()
            ->unique()
            ->sort()
            ->values()
        : collect();

    $statusOptions = collect(['Completed', 'Pending', 'Partial', 'Failed', 'Cancelled']);

    $payments = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
    $payments->getCollection()->transform(function ($payment) {
        $payment->resolved_status = $this->resolvePaymentStatus($payment);
        $payment->resolved_channel = $this->resolvePaymentChannel($payment);
        return $payment;
    });

    return view('Reports.payment-summary', compact('payments', 'totalRevenue', 'summary', 'methodOptions', 'statusOptions', 'activeBranch'));
}

public function show($id)
{
    $paymentQuery = \App\Models\Payment::with($this->paymentReportRelations())->whereKey($id);
    $this->scopePaymentsForActor($paymentQuery);
    $this->applyPaymentBranchFilter($paymentQuery, 'payments');
    $payment = $paymentQuery->firstOrFail();
    $payment->resolved_status = $this->resolvePaymentStatus($payment);
    $payment->resolved_channel = $this->resolvePaymentChannel($payment);
    return response()->json($payment);
}

public function update(Request $request, $id)
{
    $request->validate([
        'method' => 'required',
        'amount' => 'required|numeric',
        'status' => 'required',
        'reference' => 'nullable|string|max:255',
    ]);

    $paymentQuery = \App\Models\Payment::query()->whereKey($id);
    $this->scopePaymentsForActor($paymentQuery);
    $this->applyPaymentBranchFilter($paymentQuery, 'payments');
    $payment = $paymentQuery->firstOrFail();
    $payment->update([
        'method' => $request->method,
        'amount' => $request->amount,
        'status' => $request->status,
        'reference' => $request->reference,
        'note' => $request->reference,
    ]);

    return response()->json(['success' => true, 'message' => 'Payment updated!']);
}

private function resolvePaymentStatus(Payment $payment): string
{
    $raw = strtolower(trim((string) ($payment->status ?? '')));
    $sale = $payment->sale;

    if (in_array($raw, ['completed', 'paid', 'success', 'successful'], true)) {
        return 'Completed';
    }
    if (in_array($raw, ['pending', 'deposit'], true)) {
        return 'Pending';
    }
    if (in_array($raw, ['partial', 'partially paid'], true)) {
        return 'Partial';
    }
    if (in_array($raw, ['cancelled', 'canceled'], true)) {
        return 'Cancelled';
    }
    if (in_array($raw, ['failed', 'error'], true)) {
        return 'Failed';
    }

    if ($sale) {
        $salePaymentStatus = strtolower(trim((string) ($sale->payment_status ?? '')));
        $saleBalance = (float) ($sale->balance ?? 0);
        $saleTotal = (float) ($sale->total ?? 0);
        $salePaid = (float) ($sale->paid ?? ($sale->amount_paid ?? 0));

        if (in_array($salePaymentStatus, ['paid', 'completed', 'success', 'successful'], true) || $saleBalance <= 0 || ($saleTotal > 0 && $salePaid >= $saleTotal)) {
            return 'Completed';
        }

        if (in_array($salePaymentStatus, ['partial', 'partially paid'], true) || ($salePaid > 0 && $saleBalance > 0)) {
            return 'Partial';
        }
    }

    return 'Pending';
}

private function resolvePaymentChannel(Payment $payment): string
{
    if (!empty($payment->account?->name)) {
        return (string) $payment->account->name;
    }

    $details = $payment->sale?->payment_details;
    if (is_string($details)) {
        $decoded = json_decode($details, true);
        $details = is_array($decoded) ? $decoded : [];
    }

    if (is_array($details)) {
        $directChannel = trim((string) ($details['payment_account_name'] ?? ''));
        if ($directChannel !== '') {
            return $directChannel;
        }

        $splitParts = [];
        $cardChannel = trim((string) ($details['card_account_name'] ?? ''));
        $transferChannel = trim((string) ($details['transfer_account_name'] ?? ''));

        if ($cardChannel !== '') {
            $splitParts[] = 'Card: ' . $cardChannel;
        }
        if ($transferChannel !== '') {
            $splitParts[] = 'Transfer: ' . $transferChannel;
        }

        if ($splitParts !== []) {
            return implode(' | ', $splitParts);
        }
    }

    return 'Not specified';
}

private function paymentReportColumns(): array
{
    if (!Schema::hasTable('payments')) {
        return [];
    }

    return [
        'payment_id' => Schema::hasColumn('payments', 'payment_id'),
        'reference' => Schema::hasColumn('payments', 'reference'),
        'note' => Schema::hasColumn('payments', 'note'),
        'method' => Schema::hasColumn('payments', 'method'),
        'status' => Schema::hasColumn('payments', 'status'),
        'payment_account_id' => Schema::hasColumn('payments', 'payment_account_id'),
    ];
}

private function paymentReportRelations(): array
{
    $relations = ['creator'];

    if (Schema::hasTable('sales')) {
        $relations[] = 'sale.customer';
    }

    if (Schema::hasTable('accounts') && Schema::hasColumn('payments', 'payment_account_id')) {
        $relations[] = 'account';
    }

    return $relations;
}

    private function scopePaymentsForActor($query): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        if (
            $user->company_id
            && !Schema::hasColumn('payments', 'company_id')
        ) {
            $query->where(function ($sub) use ($user) {
                if (Schema::hasTable('sales') && Schema::hasColumn('payments', 'sale_id') && Schema::hasColumn('sales', 'company_id')) {
                    $sub->whereHas('sale', function ($saleQuery) use ($user) {
                        $saleQuery->where('company_id', $user->company_id);
                    });
                }

                if (Schema::hasTable('users') && Schema::hasColumn('payments', 'created_by') && Schema::hasColumn('users', 'company_id')) {
                    $sub->orWhereHas('creator', function ($creatorQuery) use ($user) {
                        $creatorQuery->where('company_id', $user->company_id);
                    });
                }
            });
            return;
        }

        $this->applyTenantScope($query, 'payments');
    }

    public function accountsReceivable(Request $request)
    {
        if (!Schema::hasTable('sales') || !Schema::hasColumn('sales', 'customer_id')) {
            return view('Reports.Reports.accounts-receivable', [
                'receivables' => collect(),
                'totalDue' => 0,
                'activeBranch' => $this->getActiveBranchContext(),
                'filters' => [
                    'start_date' => $request->input('start_date'),
                    'end_date' => $request->input('end_date'),
                    'type' => $request->input('type', 'all'),
                ],
            ]);
        }

        $salesQuery = Sale::query()
            ->whereNotNull('customer_id')
            ->where('balance', '>', 0)
            ->where(function ($query) {
                $query->whereNull('invoice_no')
                    ->orWhere('invoice_no', 'not like', 'OPENING-BAL-%');
            });

        if ($request->filled('start_date')) {
            $salesQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $salesQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $this->applyTenantScope($salesQuery, 'sales');
        $this->applySaleBranchFilter($salesQuery, 'sales');

        $salesSummary = $salesQuery
            ->selectRaw('customer_id, SUM(total) as total_invoiced, SUM(amount_paid) as total_paid, SUM(balance) as total_due, COUNT(*) as invoice_count, MIN(COALESCE(created_at, order_date)) as first_activity_at')
            ->groupBy('customer_id')
            ->get();

        $openingQuery = Customer::query();
        $this->applyTenantScope($openingQuery, 'customers');
        $openingCustomers = $openingQuery
            ->where('balance', '>', 0)
            ->get()
            ->keyBy('id');

        $customerMap = Customer::query()
            ->whereIn('id', $salesSummary->pluck('customer_id')->filter()->merge($openingCustomers->keys())->all())
            ->tap(fn ($query) => $this->applyTenantScope($query, 'customers'))
            ->get()
            ->keyBy('id');

        $openingReferenceMap = [];
        foreach ($openingCustomers as $customer) {
            $openingReferenceMap[$customer->id] = 'OPENING-BAL-' . $customer->id;
        }

        $openingSales = Sale::query()
            ->whereIn('customer_id', array_keys($openingReferenceMap))
            ->whereIn('invoice_no', array_values($openingReferenceMap));
        $this->applyTenantScope($openingSales, 'sales');
        $this->applySaleBranchFilter($openingSales, 'sales');
        $openingSales = $openingSales->get()->keyBy('customer_id');

        $receivableMap = [];
        foreach ($salesSummary as $row) {
            $customer = $customerMap->get($row->customer_id);
            $openingSale = $openingSales->get($row->customer_id);
            $receivableMap[$row->customer_id] = [
                'customer_id' => $row->customer_id,
                'customer_name' => $customer?->customer_name ?? $customer?->name ?? 'Walk-in Customer',
                'email' => $customer?->email,
                'phone' => $customer?->phone,
                'total_invoiced' => (float) $row->total_invoiced,
                'total_paid' => (float) $row->total_paid,
                'total_due' => (float) $row->total_due,
                'invoice_count' => (int) $row->invoice_count,
                'opening_balance' => $openingSale ? 0.0 : (float) ($customer?->balance ?? 0),
                'sort_at' => $row->first_activity_at ?: $customer?->created_at,
                'sort_id' => (int) ($customer?->id ?? $row->customer_id ?? 0),
            ];
        }

        foreach ($openingCustomers as $customer) {
            if (!isset($receivableMap[$customer->id])) {
                $receivableMap[$customer->id] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->customer_name ?? $customer->name ?? 'Walk-in Customer',
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'total_invoiced' => 0.0,
                    'total_paid' => 0.0,
                    'total_due' => 0.0,
                    'invoice_count' => 0,
                    'opening_balance' => (float) $customer->balance,
                    'sort_at' => $customer->created_at,
                    'sort_id' => (int) $customer->id,
                ];
            }
        }

        $receivables = collect(array_values($receivableMap))
            ->sortBy(function ($row) {
                $sortAt = $row['sort_at'] ?? null;
                $sortId = (int) ($row['sort_id'] ?? 0);

                return ($sortAt ? Carbon::parse($sortAt)->format('Y-m-d H:i:s.u') : '9999-12-31 23:59:59.999999')
                    . '|'
                    . str_pad((string) $sortId, 12, '0', STR_PAD_LEFT)
                    . '|'
                    . ($row['customer_name'] ?? '');
            })
            ->map(fn ($row) => (object) $row)
            ->values();

        $typeFilter = strtolower(trim((string) $request->input('type', 'all')));
        if ($typeFilter === 'invoices') {
            $receivables = $receivables->filter(fn ($row) => $row->invoice_count > 0)->values();
        } elseif ($typeFilter === 'opening') {
            $receivables = $receivables->filter(fn ($row) => $row->invoice_count === 0)->values();
        }

        $totalDue = (float) $receivables->sum('total_due') + (float) $receivables->sum('opening_balance');

        return view('Reports.Reports.accounts-receivable', [
            'receivables' => $receivables,
            'totalDue' => $totalDue,
            'activeBranch' => $this->getActiveBranchContext(),
            'filters' => [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'type' => $typeFilter ?: 'all',
            ],
        ]);
    }

    public function customerStatement(Request $request, $id)
    {
        $customer = Customer::query()
            ->tap(fn ($query) => $this->applyTenantScope($query, 'customers'))
            ->findOrFail($id);

        $salesQuery = Sale::query()
            ->where('customer_id', $customer->id);
        $this->applyTenantScope($salesQuery, 'sales');
        $this->applySaleBranchFilter($salesQuery, 'sales');

        $sales = $salesQuery->orderBy('created_at')->orderBy('id')->get();
        $salesIds = $sales->pluck('id')->all();
        $openingReference = 'OPENING-BAL-' . $customer->id;
        $openingSale = $sales->first(function ($sale) use ($openingReference) {
            return (string) ($sale->invoice_no ?? '') === $openingReference;
        });
        $regularSales = $sales->reject(function ($sale) use ($openingReference) {
            return (string) ($sale->invoice_no ?? '') === $openingReference;
        })->values();

        $payments = collect();
        if (Schema::hasTable('payments') && !empty($salesIds)) {
            $paymentQuery = Payment::query()->whereIn('sale_id', $salesIds);
            $this->applyTenantScope($paymentQuery, 'payments');
            $this->applyPaymentBranchFilter($paymentQuery, 'payments');
            $payments = $paymentQuery->orderBy('created_at')->get();
        }

        $ignoredStatuses = $this->ignoredAppliedPaymentStatuses();
        $payments = $payments->filter(function ($payment) use ($ignoredStatuses) {
            $status = strtolower(trim((string) ($payment->status ?? '')));
            return !in_array($status, $ignoredStatuses, true);
        })->values();

        $openingPayments = $openingSale
            ? $payments->where('sale_id', $openingSale->id)->values()
            : collect();
        $openingPaid = (float) $openingPayments->sum('amount');
        $openingOriginal = $openingSale
            ? max(
                (float) ($openingSale->total ?? 0),
                (float) ($openingSale->paid ?? $openingSale->amount_paid ?? 0) + (float) ($openingSale->balance ?? 0),
                (float) ($customer->balance ?? 0) + $openingPaid
            )
            : max(0, (float) ($customer->balance ?? 0) + $openingPaid);

        $entries = collect();
        if ($openingOriginal > 0) {
            $openingEventAt = $this->statementEntryTime(
                $openingSale?->created_at,
                $customer->opening_balance_date ?: $openingSale?->order_date
            ) ?: ($customer->created_at ? $customer->created_at->copy()->subSecond() : now()->copy()->subSecond());

            $entries->push([
                'date' => $customer->opening_balance_date
                    ?: ($openingSale?->order_date ?: ($openingSale?->created_at ?: ($customer->created_at ? $customer->created_at->copy()->startOfDay()->subSecond() : now()->copy()->startOfDay()->subSecond()))),
                'sort_at' => $openingEventAt,
                'sort_id' => 0,
                'visible' => true,
                'reference' => 'OPENING',
                'type' => 'Opening Balance',
                'description' => 'Opening balance on customer account',
                'debit' => $openingOriginal,
                'credit' => 0.0,
            ]);
        }

        $openingPayments
            ->values()
            ->each(function ($payment) use (&$entries, $openingSale) {
                $paymentEventAt = $payment->created_at ?: now();

                $entries->push([
                    'date' => $payment->created_at,
                    'sort_at' => $paymentEventAt,
                    'sort_id' => (int) ($payment->id ?? 0),
                    'visible' => true,
                    'reference' => $payment->payment_id ?: ('PAY-' . $payment->id),
                    'type' => 'Payment',
                    'description' => $payment->note
                        ?: (($openingSale && (int) $payment->sale_id === (int) $openingSale->id)
                            ? 'Opening balance payment received'
                            : 'Payment received'),
                    'debit' => 0.0,
                    'credit' => (float) ($payment->amount ?? 0),
                ]);
            });

        foreach ($regularSales as $sale) {
            $salePayments = $payments->where('sale_id', $sale->id)->values();
            $sale->setRelation('payments', $salePayments);
            $financials = $this->normalizeInvoiceFinancials($sale);
            $saleEventAt = $this->statementEntryTime(
                $sale->created_at,
                $sale->order_date
            ) ?: now();

            $entries->push([
                'date' => $sale->created_at ?: $sale->order_date,
                'sort_at' => $saleEventAt,
                'sort_id' => (int) ($sale->id ?? 0),
                'visible' => true,
                'reference' => $sale->invoice_no ?: ('SALE-' . $sale->id),
                'type' => 'Invoice',
                'description' => 'Invoice issued',
                'debit' => (float) ($financials['total'] ?? 0),
                'credit' => 0.0,
            ]);

            $salePayments
                ->values()
                ->each(function ($payment) use (&$entries, $sale) {
                    $paymentEventAt = $payment->created_at ?: now();

                    $entries->push([
                        'date' => $payment->created_at,
                        'sort_at' => $paymentEventAt,
                        'sort_id' => (int) ($payment->id ?? 0),
                        'visible' => true,
                        'reference' => $payment->payment_id ?: ('PAY-' . $payment->id),
                        'type' => 'Payment',
                        'description' => $payment->note ?: 'Customer payment received.',
                        'debit' => 0.0,
                        'credit' => (float) ($payment->amount ?? 0),
                    ]);
                });

            $untrackedPaid = max(0, round((float) ($financials['paid'] ?? 0) - (float) $salePayments->sum('amount'), 2));
            if ($untrackedPaid > 0) {
                $entries->push([
                    'date' => $sale->created_at ?: $sale->order_date,
                    'sort_at' => $this->statementSyntheticEntryTime($sale),
                    'sort_id' => (int) ($sale->id ?? 0),
                    'visible' => true,
                    'reference' => ($sale->invoice_no ?: ('SALE-' . $sale->id)) . '-APPLIED',
                    'type' => 'Payment',
                    'description' => 'Applied payment recorded on invoice',
                    'debit' => 0.0,
                    'credit' => $untrackedPaid,
                ]);
            }

        }

        $entries = $entries
            ->sortBy(function ($entry) {
                $sortAt = $entry['sort_at'] ?? $entry['date'];
                $sortId = (int) ($entry['sort_id'] ?? 0);

                return Carbon::parse($sortAt)->format('Y-m-d H:i:s.u')
                    . '|'
                    . str_pad((string) $sortId, 12, '0', STR_PAD_LEFT)
                    . '|'
                    . $entry['reference'];
            })
            ->values();

        $typeFilter = strtolower(trim((string) $request->input('type', 'all')));
        if ($typeFilter !== '' && $typeFilter !== 'all') {
            $entries = $entries->filter(function ($entry) use ($typeFilter) {
                return strtolower((string) $entry['type']) === $typeFilter;
            })->values();
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate || $endDate) {
            $entries = $entries->filter(function ($entry) use ($startDate, $endDate) {
                $entryDate = \Carbon\Carbon::parse($entry['sort_at'] ?? $entry['date']);
                if ($startDate && $entryDate->lt(\Carbon\Carbon::parse($startDate)->startOfDay())) {
                    return false;
                }
                if ($endDate && $entryDate->gt(\Carbon\Carbon::parse($endDate)->endOfDay())) {
                    return false;
                }
                return true;
            })->values();
        }

        $runningBalance = 0.0;
        $entries = $entries->map(function ($entry) use (&$runningBalance) {
                $runningBalance += (float) $entry['debit'] - (float) $entry['credit'];
                $entry['balance'] = $runningBalance;
                $entry['entry_at'] = $entry['sort_at'] ?? $entry['date'];
                unset($entry['sort_at'], $entry['sort_rank'], $entry['sort_sequence'], $entry['sort_id']);
                return $entry;
            });
        $totalInvoiced = (float) $entries->sum(fn ($entry) => (float) ($entry['debit'] ?? 0));
        $totalPaid = (float) $entries->sum(fn ($entry) => (float) ($entry['credit'] ?? 0));
        $balanceDue = (float) round($totalInvoiced - $totalPaid, 2);
        return view('Reports.Reports.customer-statement', [
            'customer' => $customer,
            'entries' => $entries,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'balanceDue' => $balanceDue,
            'activeBranch' => $this->getActiveBranchContext(),
            'filters' => [
                'type' => $typeFilter ?: 'all',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    private function statementEntryTime(mixed $createdAt, mixed $businessDate = null, bool $preferEarlierBusinessDate = false): ?Carbon
    {
        $created = $createdAt ? Carbon::parse($createdAt) : null;
        $business = $businessDate ? Carbon::parse($businessDate)->startOfDay() : null;

        if ($created && $business && $preferEarlierBusinessDate) {
            return $business->lt($created) ? $business : $created;
        }

        return $created ?: $business;
    }

    private function statementSyntheticEntryTime(Sale $sale): Carbon
    {
        $created = $sale->created_at ? Carbon::parse($sale->created_at) : null;
        $updated = $sale->updated_at ? Carbon::parse($sale->updated_at) : null;
        $business = $sale->order_date ? Carbon::parse($sale->order_date)->startOfDay() : null;

        if ($updated && $created && $updated->greaterThan($created)) {
            return $updated;
        }

        return $created ?: ($updated ?: ($business ?: now()));
    }


public function bulkUpdate(Request $request)
{
    // 1. Validate the incoming request
    $request->validate([
        'ids' => 'required|array',
        'status' => 'required|string'
    ]);

    $ids = $request->ids;
    $status = $request->status;

    try {
        // 2. Check if the action is a permanent deletion
        if ($status === 'DELETE_ACTION') {
            $deleteQuery = \App\Models\Payment::query()->whereIn('id', $ids);
            $this->scopePaymentsForActor($deleteQuery);
            $this->applyPaymentBranchFilter($deleteQuery, 'payments');
            $deleteQuery->delete();
            return response()->json([
                'success' => true, 
                'message' => count($ids) . ' records deleted permanently.'
            ]);
        }

        // 3. Otherwise, perform a standard status update
        $updateQuery = \App\Models\Payment::query()->whereIn('id', $ids);
        $this->scopePaymentsForActor($updateQuery);
        $this->applyPaymentBranchFilter($updateQuery, 'payments');
        $updateQuery->update([
            'status' => $status
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Status updated to ' . $status . ' for ' . count($ids) . ' records.'
        ]);

    } catch (\Exception $e) {
        // Handle database errors (like foreign key constraints)
        return response()->json([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

public function destroy($id)
{
    $paymentQuery = \App\Models\Payment::query()->whereKey($id);
    $this->scopePaymentsForActor($paymentQuery);
    $this->applyPaymentBranchFilter($paymentQuery, 'payments');
    $payment = $paymentQuery->firstOrFail();
    $payment->delete();

    return response()->json([
        'success' => true,
        'message' => 'Payment deleted successfully.',
    ]);
}


        /** 5. Purchase Report **/
        public function purchase_return(Request $request)
        {
            $query = $this->scopedTable('purchase_transactions')
                ->leftJoin('companies', 'purchase_transactions.company_id', '=', 'companies.id')
                ->where('purchase_transactions.transaction_type', 'purchase')
                ->select([
                    'purchase_transactions.id as Id',
                    'purchase_transactions.reference as Reference',
                    'companies.name as CompanyName',
                    'purchase_transactions.amount as Amount',
                    'purchase_transactions.transaction_type as Type',
                    'purchase_transactions.date as Date'
                ]);

            $purchases = $this->process_report($query, $request, 'purchase_transactions.date', ['purchase_transactions.reference', 'companies.name']);
            return $this->renderReportView('purchase-return', compact('purchases'));
        }

    public function quotation_report(Request $request)
    {
        // Using the full namespace prevents "Class not found" errors
        $query = \App\Models\Quotation::with('customer');
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);
        $activeBranch = $this->getActiveBranchContext();

        if ($companyId > 0 && Schema::hasColumn('quotations', 'company_id')) {
            $query->where('company_id', $companyId)
                ->orWhere(function ($sub) use ($userId) {
                    $sub->whereNull('company_id')
                        ->where('user_id', $userId);
                });
        } elseif ($companyId > 0 && Schema::hasTable('customers')) {
            $query->whereHas('customer', function ($sub) use ($companyId) {
                if (Schema::hasColumn('customers', 'company_id')) {
                    $sub->where('company_id', $companyId);
                }
            });
        }

        if (!empty($activeBranch['id']) || !empty($activeBranch['name'])) {
            $query->where(function ($sub) use ($activeBranch) {
                if (!empty($activeBranch['id']) && Schema::hasColumn('quotations', 'branch_id')) {
                    $sub->where('branch_id', $activeBranch['id']);
                }
                if (!empty($activeBranch['name']) && Schema::hasColumn('quotations', 'branch_name')) {
                    $sub->orWhere('branch_name', $activeBranch['name']);
                }
            })->orWhereHas('customer', function ($sub) use ($activeBranch) {
                if (!empty($activeBranch['id']) && Schema::hasColumn('customers', 'branch_id')) {
                    $sub->where('branch_id', $activeBranch['id']);
                }
                if (!empty($activeBranch['name']) && Schema::hasColumn('customers', 'branch_name')) {
                    $sub->orWhere('branch_name', $activeBranch['name']);
                }
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Get results with query string preserved for pagination
        $quotationreports = $query->latest()->paginate(20)->withQueryString();

        return view('Reports.Reports.quotation-report', compact('quotationreports'));
    }

        /** 7. Sales Report **/
        public function sales_report(Request $request)
        {
            $activeBranch = $this->getActiveBranchContext();
            $stockExpression = Schema::hasColumn('products', 'stock')
                ? 'COALESCE(products.stock, 0)'
                : (Schema::hasColumn('products', 'stock_quantity') ? 'COALESCE(products.stock_quantity, 0)' : '0');
            $saleQtyExpression = Schema::hasColumn('sale_items', 'qty')
                ? 'COALESCE(sale_items.qty, 0)'
                : (Schema::hasColumn('sale_items', 'quantity') ? 'COALESCE(sale_items.quantity, 0)' : '0');
            $saleLineTotalExpression = Schema::hasColumn('sale_items', 'total_price')
                ? 'COALESCE(sale_items.total_price, 0)'
                : (Schema::hasColumn('sale_items', 'subtotal')
                    ? 'COALESCE(sale_items.subtotal, 0)'
                    : '(' . $saleQtyExpression . ' * COALESCE(sale_items.unit_price, 0))');

            $query = \App\Models\Product::query()
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->selectRaw('
                    products.id as Id,
                    products.name as Product,
                    products.sku as SKU,
                    COALESCE(categories.name, "Uncategorized") as Category,
                    COALESCE(SUM(' . $saleLineTotalExpression . '), 0) as SoldAmount,
                    COALESCE(SUM(' . $saleQtyExpression . '), 0) as SoldQty,
                    ' . $stockExpression . ' as InstockQty,
                    MAX(sales.created_at) as DueDate
                ')
                ->groupBy(
                    'products.id',
                    'products.name',
                    'products.sku',
                    'categories.name',
                    DB::raw($stockExpression)
                );
            $this->applyTenantScope($query, 'products');
            $this->applySalesScope($query, 'sales');

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($builder) use ($search) {
                    $builder->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.sku', 'like', "%{$search}%")
                        ->orWhere('categories.name', 'like', "%{$search}%");
                });
            }

            $startDate = $request->input('start_date') ?: $request->input('from_date');
            $endDate = $request->input('end_date') ?: $request->input('to_date');

            if ($startDate) {
                $query->where(function ($builder) use ($startDate) {
                    $builder->whereNull('sales.created_at')
                        ->orWhereDate('sales.created_at', '>=', $startDate);
                });
            }

            if ($endDate) {
                $query->where(function ($builder) use ($endDate) {
                    $builder->whereNull('sales.created_at')
                        ->orWhereDate('sales.created_at', '<=', $endDate);
                });
            }

            $salesreports = $query
                ->orderBy('DueDate', 'asc')
                ->orderBy('products.name')
                ->paginate(15)
                ->withQueryString();

            return $this->renderReportView('sales-report', compact('salesreports', 'activeBranch'));
        }

        /** 8. Sales Return Report **/
    public function sales_return_report(Request $request)
    {
        // Join products to get meaningful report data
        $query = $this->scopedTable('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.payment_status', 'returned') // Filter for returns
            ->select([
                'sales.id as Id',
                'products.name as Product',
                'products.sku as SKU',
                'products.image as Image',
                'categories.name as Category',
                'sales.total as SoldAmount',
                'sale_items.qty as SoldQty',
                'products.stock as InstockQty',
                'sales.created_at as DueDate'
            ]);
        $this->applyTenantScope($query, 'products');
        $this->applySalesScope($query, 'sales');

        // Use your helper function for date filtering and search
        // We add withQueryString() to keep filters alive during pagination
        $salesreturnreports = $this->process_report($query, $request, 'sales.created_at', ['products.name', 'products.sku'])
                                ->withQueryString();

        // Calculate Total Refunded Amount specifically for the filtered results
        $totalRefunded = (clone $query)->sum('sales.total');

        return $this->renderReportView('sales-return', compact('salesreturnreports', 'totalRefunded'));
    }

    public function stock_report(Request $request)
    {
        $activeBranch = $this->getActiveBranchContext();
        $fromDate = $request->input('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $request->input('to_date') ?: now()->toDateString();
        $productId = $request->input('product_id');

        $user = Auth::user();
        $companyId = (int) ($user?->company_id ?? 0);

        $applyTenantScope = function ($query, string $table) use ($companyId, $user) {
            if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
                $query->where(function ($sub) use ($table, $companyId, $user) {
                    $sub->where("{$table}.company_id", $companyId);

                    if ($user && Schema::hasColumn($table, 'user_id')) {
                        $sub->orWhere(function ($fallback) use ($table, $user) {
                            $fallback->whereNull("{$table}.company_id")
                                ->where("{$table}.user_id", $user->id);
                        });
                    }
                });
            } elseif ($user && Schema::hasColumn($table, 'user_id')) {
                $query->where("{$table}.user_id", $user->id);
            }

            return $query;
        };

        $products = $this->scopedTable('products')
            ->select('id', 'name')
            ->orderBy('name')
            ->tap(fn ($q) => $applyTenantScope($q, 'products'))
            ->get();

        $stockColumn = Schema::hasColumn('products', 'stock')
            ? 'stock'
            : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : null);
        $purchasePriceColumn = Schema::hasColumn('products', 'purchase_price')
            ? 'purchase_price'
            : (Schema::hasColumn('products', 'cost_price') ? 'cost_price' : null);
        $salesPriceColumn = Schema::hasColumn('products', 'retail_price')
            ? 'retail_price'
            : (Schema::hasColumn('products', 'price') ? 'price' : null);
        $reorderColumn = Schema::hasColumn('products', 'reorder_level')
            ? 'reorder_level'
            : (Schema::hasColumn('products', 'min_stock_level') ? 'min_stock_level' : null);
        $hasBranchStocks = Schema::hasTable('product_branch_stocks');
        $hasBranchId = $hasBranchStocks && Schema::hasColumn('product_branch_stocks', 'branch_id');
        $hasBranchName = $hasBranchStocks && Schema::hasColumn('product_branch_stocks', 'branch_name');
        $branchId = (string) ($activeBranch['id'] ?? '');
        $branchName = (string) ($activeBranch['name'] ?? '');

        $purchaseExpr = $purchasePriceColumn ? "products.{$purchasePriceColumn}" : '0';
        $salesExpr = $salesPriceColumn ? "products.{$salesPriceColumn}" : $purchaseExpr;
        $reorderExpr = $reorderColumn ? "products.{$reorderColumn}" : '0';
        $stockExpr = $stockColumn ? "products.{$stockColumn}" : '0';

        $productsQuery = $this->scopedTable('products')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                DB::raw("COALESCE({$purchaseExpr}, 0) as purchase_price"),
                DB::raw("COALESCE({$salesExpr}, 0) as sales_price"),
                DB::raw("COALESCE({$reorderExpr}, 0) as reorder_level"),
            ])
            ->tap(fn ($q) => $applyTenantScope($q, 'products'))
            ->when(!empty($productId), fn ($q) => $q->where('products.id', $productId));

        if ($hasBranchStocks && ($branchId !== '' || $branchName !== '')) {
            $productsQuery->leftJoin('product_branch_stocks', function ($join) use ($branchId, $branchName, $hasBranchId, $hasBranchName) {
                $join->on('product_branch_stocks.product_id', '=', 'products.id');

                if ($branchId !== '' && $hasBranchId) {
                    $join->where('product_branch_stocks.branch_id', '=', $branchId);
                } elseif ($branchName !== '' && $hasBranchName) {
                    $join->where('product_branch_stocks.branch_name', '=', $branchName);
                }
            });

            $productsQuery->addSelect(DB::raw("COALESCE(product_branch_stocks.quantity, {$stockExpr}, 0) as stock_on_hand"));
        } else {
            $productsQuery->addSelect(DB::raw("COALESCE({$stockExpr}, 0) as stock_on_hand"));
        }

        $stockreports = $productsQuery
            ->orderBy('products.name')
            ->get()
            ->map(function ($product) {
                $qty = max(0, (float) ($product->stock_on_hand ?? 0));
                $purchasePrice = max(0, (float) ($product->purchase_price ?? 0));
                $salesPrice = max(0, (float) ($product->sales_price ?? 0));
                $reorderLevel = max(0, (float) ($product->reorder_level ?? 0));
                $costValue = $qty * $purchasePrice;
                $salesValue = $qty * $salesPrice;

                return [
                    'Product' => (string) ($product->name ?? 'Unnamed Product'),
                    'Sku' => (string) ($product->sku ?? ''),
                    'QtyOnHand' => $qty,
                    'PurchasePrice' => $purchasePrice,
                    'SalesPrice' => $salesPrice,
                    'CostValue' => $costValue,
                    'SalesValue' => $salesValue,
                    'ReorderLevel' => $reorderLevel,
                    'Status' => $qty <= 0
                        ? 'Out of Stock'
                        : ($reorderLevel > 0 && $qty <= $reorderLevel ? 'Low Stock' : 'In Stock'),
                ];
            });

        return view('Reports.Reports.stock-report', compact('stockreports', 'fromDate', 'toDate', 'products', 'productId', 'activeBranch'));
    }

    public function email_low_stock_report(Request $request)
    {
        // 1. Get the same data used in the report view
        $reports = $this->getLowStockData(); // Assuming you have a private method for this logic

        // 2. Generate PDF in memory
        $pdf = Pdf::loadView('reports.low-stock-pdf', compact('reports'));

        // 3. Send Email
        try {
            $recipient = $request->input('recipient')
                ?: auth()->user()?->email
                ?: Setting::mailFromAddress();

            if (!filter_var((string) $recipient, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['success' => false, 'message' => 'No valid recipient email found. Please update your profile email or mail settings.'], 422);
            }

            AppMailer::sendMailable($recipient, new LowStockReportMail($pdf->output()));
            return response()->json(['success' => true, 'message' => "Report sent to {$recipient} successfully!"]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()]);
        }
    }

    public function email_report(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'recipient' => 'nullable|email',
        ]);

        $recipient = $data['recipient']
            ?? auth()->user()?->email
            ?? Setting::mailFromAddress();

        if (!filter_var((string) $recipient, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'No valid recipient email found. Please update your profile email or mail settings.'], 422);
        }

        try {
            AppMailer::sendView('emails.system-event', [
                'title' => 'Report Delivery',
                'intro' => 'Your requested report summary is ready.',
                'details' => [
                    'Report' => $data['subject'],
                    'Generated By' => auth()->user()?->email ?? 'System',
                    'Generated At' => now()->toDateTimeString(),
                    'Summary' => $data['body'],
                ],
            ], function ($message) use ($recipient, $data) {
                $message->from(Setting::mailFromAddress(), Setting::mailFromName())
                    ->to($recipient)
                    ->subject($data['subject']);
            });

            return response()->json(['success' => true, 'message' => "Report emailed to {$recipient} successfully."]);
        } catch (\Throwable $e) {
            Log::error('Report email failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $this->friendlyMailErrorMessage($e),
            ], 500);
        }
    }

    public function email_summary_report(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'nullable|string',
            'lines' => 'nullable|array',
            'lines.*' => 'nullable|string|max:500',
            'recipient' => 'nullable|email',
        ]);

        $body = trim((string) ($validated['body'] ?? ''));

        if ($body === '') {
            $body = collect($validated['lines'] ?? [])
                ->filter(fn ($line) => filled($line))
                ->implode("\n");
        }

        $request->merge([
            'subject' => $validated['subject'],
            'body' => $body !== '' ? $body : 'Your requested report summary is ready.',
            'recipient' => $validated['recipient'] ?? null,
        ]);

        return $this->email_report($request);
    }

    private function friendlyMailErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (Str::contains($message, [
            'Username and Password not accepted',
            'BadCredentials',
            'Failed to authenticate on SMTP server',
            'Expected response code "235" but got code "535"',
        ])) {
            return 'Email failed: SMTP login was rejected. Update the Gmail address and App Password in mail settings or .env, then try again.';
        }

        return 'Email failed: ' . $message;
    }

    /*
        |--------------------------------------------------------------------------
        | 1. PURCHASE RETURNS (Debit Notes)
        |--------------------------------------------------------------------------
        */

        /**
         * Display the Purchase Return Report
         */
        public function purchase_return_report(Request $request)
        {
            $query = $this->scopedTable('purchase_return_items')
                ->join('purchase_returns', 'purchase_return_items.purchase_return_id', '=', 'purchase_returns.id')
                ->join('products', 'purchase_return_items.product_id', '=', 'products.id')
                ->leftJoin('vendors', 'purchase_returns.vendor_id', '=', 'vendors.id')
                ->select([
                    'purchase_returns.id as Id',
                    'purchase_returns.return_no as PurchaseNo', 
                    'products.name as Product',
                    DB::raw("COALESCE(vendors.name, 'N/A') as VendorName"),
                    'purchase_return_items.unit_price as ReturnAmount',
                    'purchase_return_items.qty as ReturnQty',
                    'purchase_returns.return_date as ReturnDate'
                ]);

            $purchasereturns = $this->process_report($query, $request, 'purchase_returns.return_date', ['products.name', 'vendors.name'])
                                    ->withQueryString();

            $totalRefunded = (clone $query)->sum(DB::raw('purchase_return_items.qty * purchase_return_items.unit_price'));

            return view('Reports.Reports.purchase-return', compact('purchasereturns', 'totalRefunded'));
        }

        /**
         * Show form to create a Purchase Return
         */
        public function create_purchase_return()
        {
            $purchases = $this->scopedTable('purchases')
                ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                ->select(
                    'purchases.id',
                    'purchases.purchase_no',
                    DB::raw("COALESCE(suppliers.name, 'N/A') as vendor_name")
                )
                ->orderBy('purchases.created_at', 'desc')
                ->get();

            return view('Reports.Reports.create-purchase-return', compact('purchases'));
        }

        /**
         * Store the Purchase Return data
         */
        public function store_purchase_return(Request $request)
        {
            $request->validate([
                'purchase_id' => 'required|exists:purchases,id',
                'return_date' => 'required|date',
                'items'       => 'required|array'
            ]);

            DB::beginTransaction();
            try {
                $purchase = DB::table('purchases')->where('id', $request->purchase_id)->first();
                
                $returnId = DB::table('purchase_returns')->insertGetId([
                    'purchase_id'  => $purchase->id,
                    'vendor_id'    => null,
                    'return_no'    => 'RET-' . time(),
                    'return_date'  => $request->return_date,
                    'reason'       => $request->reason,
                    'total_amount' => 0,
                    'created_at'   => now(),
                ]);

                $totalAmount = 0;
                foreach ($request->items as $productId => $data) {
                    if ($data['qty'] > 0) {
                        $subtotal = $data['qty'] * $data['unit_price'];
                        $totalAmount += $subtotal;

                        DB::table('purchase_return_items')->insert([
                            'purchase_return_id' => $returnId,
                            'product_id'         => $productId,
                            'qty'                => $data['qty'],
                            'unit_price'         => $data['unit_price'],
                            'subtotal'           => $subtotal,
                        ]);
                    }
                }

                DB::table('purchase_returns')->where('id', $returnId)->update(['total_amount' => $totalAmount]);

                if ($totalAmount > 0) {
                    LedgerService::postPurchaseReturn(
                        relatedId: (int) $returnId,
                        amount: (float) $totalAmount,
                        reference: 'RET-' . $returnId,
                        date: $request->return_date,
                        userId: Auth::id(),
                        relatedType: PurchaseReturn::class
                    );
                }

                DB::commit();
                return redirect()->route('reports.purchase')->with('success', 'Purchase Return processed successfully!');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'Error: ' . $e->getMessage());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. CREDIT NOTES (Sales Returns)
        |--------------------------------------------------------------------------
        */
        public function credit_notes(Request $request) 
        {
            if (!Schema::hasTable('credit_notes') || !Schema::hasTable('credit_note_items')) {
                $purchasereturns = new LengthAwarePaginator([], 0, 10, 1, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]);

                return view('Sales.credit-notes', [
                    'purchasereturns' => $purchasereturns,
                    'totalRefunded' => 0,
                ])->with('warning', 'Sales return item records are not available on this workspace yet.');
            }

            $query = $this->scopedTable('credit_note_items')
                ->join('credit_notes', 'credit_note_items.credit_note_id', '=', 'credit_notes.id')
                ->join('products', 'credit_note_items.product_id', '=', 'products.id')
                ->join('customers', 'credit_notes.customer_id', '=', 'customers.id')
                ->select([
                    'credit_notes.id as Id',
                    'credit_notes.credit_note_no as PurchaseNo',
                    'products.name as Product',
                    'customers.name as VendorName',
                    'credit_note_items.unit_price as ReturnAmount',
                    'credit_note_items.qty as ReturnQty',
                    'credit_notes.credit_date as ReturnDate'
                ]);

            $purchasereturns = $this->process_report($query, $request, 'credit_notes.credit_date', ['products.name', 'customers.name'])
                                    ->withQueryString();

            $totalRefunded = (clone $query)->sum(DB::raw('credit_note_items.qty * credit_note_items.unit_price'));

            return view('Sales.credit-notes', compact('purchasereturns', 'totalRefunded'));
        }

    /**
     * Show form to create a Sales Return
     */
    public function create_credit_note()
    {
        // Pointing to 'sales' where your 9 records actually are
        $invoices = $this->scopedTable('sales')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'sales.id', 
                'sales.id as display_name', 
                'customers.customer_name'
            )
            ->orderBy('sales.id', 'desc');
        $this->applySalesScope($invoices, 'sales');
        $invoices = $invoices->get();

        return view('Reports.Reports.create-sales-return', compact('invoices'));
    }

    /**
     * Store the Sales Return (Credit Note) data
     */
    public function store_credit_note(Request $request)
    {
        $request->validate([
            'invoice_id'  => 'required|exists:invoices,id',
            'credit_date' => 'required|date',
            'items'       => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            $invoice = DB::table('invoices')->where('id', $request->invoice_id)->first();
            
            // 1. Create the Credit Note Header
            $creditNoteId = DB::table('credit_notes')->insertGetId([
                'invoice_id'     => $invoice->id,
                'customer_id'    => $invoice->customer_id,
                'credit_note_no' => 'CN-' . strtoupper(Str::random(8)),
                'credit_date'    => $request->credit_date,
                'status'         => 'approved',
                'total_amount'   => 0, // Updated later
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $totalAmount = 0;

            // 2. Process each returned item
            foreach ($request->items as $productId => $data) {
                if ($data['qty'] > 0) {
                    $subtotal = $data['qty'] * $data['unit_price'];
                    $totalAmount += $subtotal;

                    // Save item details
                    DB::table('credit_note_items')->insert([
                        'credit_note_id' => $creditNoteId,
                        'product_id'     => $productId,
                        'qty'            => $data['qty'],
                        'unit_price'     => $data['unit_price'],
                        'subtotal'       => $subtotal,
                    ]);

                    // 3. Update Inventory: Increment stock because goods are returned
                    DB::table('products')->where('id', $productId)->increment('stock', $data['qty']);
                }
            }

            // 4. Finalize the total amount
            DB::table('credit_notes')->where('id', $creditNoteId)->update(['total_amount' => $totalAmount]);

            if ($totalAmount > 0) {
                LedgerService::postSalesReturn(
                    relatedId: (int) $creditNoteId,
                    amount: (float) $totalAmount,
                    reference: 'CN-' . $creditNoteId,
                    date: $request->credit_date,
                    userId: Auth::id(),
                    relatedType: 'credit_note'
                );
            }

            DB::commit();
            return redirect()->route('reports.sales-return')->with('success', 'Sales Return processed and stock updated successfully!');
            
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error processing return: ' . $e->getMessage());
        }
    }


        /*
        |--------------------------------------------------------------------------
        | 3. AJAX HELPERS
        |--------------------------------------------------------------------------
        */

        public function get_purchase_items($id)
        {
            $items = $this->scopedTable('purchase_items')
                ->join('products', 'purchase_items.product_id', '=', 'products.id')
                ->where('purchase_id', $id)
                ->select('products.id as product_id', 'products.name', 'purchase_items.qty', 'purchase_items.unit_price')
                ->get();

            return response()->json($items);
        }

    public function get_invoice_items($id)
    {
        // Fetch items linked to the sale_id
        $items = $this->scopedTable('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sale_items.sale_id', $id)
            ->select(
                'products.id as product_id',
                'products.name',
                'sale_items.quantity as qty', // Aliasing 'quantity' to 'qty' for the JS
                'sale_items.unit_price'
            )
            ->get();

        // This returns a JSON array that the JavaScript will "pour" into the table
        return response()->json($items);
    }
    /**
     * Private helper to keep the Database Query logic in one place
     */
    private function getReturnBaseQuery()
    {
        return $this->scopedTable('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id') 
            ->where('purchases.status', 'returned')
            ->select([
                'purchases.id as Id',
                'purchases.purchase_no as PurchaseNo',
                'products.name as Product',
                DB::raw("COALESCE(suppliers.name, 'N/A') as VendorName"), 
                'purchases.total_amount as ReturnAmount',
                'purchase_items.qty as ReturnQty',
                'purchases.created_at as ReturnDate'
            ]);
    }


    /**
     * Shared logic to prevent "Undefined Variable" errors
     */
    private function handleReturnReport($request, $viewPath)
    {
        $query = $this->scopedTable('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id') 
            ->where('purchases.status', 'returned')
            ->select([
                'purchases.id as Id',
                'purchases.purchase_no as PurchaseNo',
                'products.name as Product',
                DB::raw("COALESCE(suppliers.name, 'N/A') as VendorName"), 
                'purchases.total_amount as ReturnAmount',
                'purchase_items.qty as ReturnQty',
                'purchases.created_at as ReturnDate'
            ]);

        // This variable name MUST be 'purchasereturns' to match the view
        $purchasereturns = $this->process_report($query, $request, 'purchases.created_at', ['products.name', 'suppliers.name'])
                                ->withQueryString();

        $totalRefunded = (clone $query)->sum('purchases.total_amount');

        return view($viewPath, compact('purchasereturns', 'totalRefunded'));
    }

    /** 11. Profit & Loss List (Full Grand Totals + No Repeats) **/
    public function profit_loss_list(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $user = Auth::user();
        $companyId = (int) ($user?->company_id ?? 0);

        $salesDateColumn = Schema::hasColumn('sales', 'order_date')
            ? 'order_date'
            : (Schema::hasColumn('sales', 'date') ? 'date' : 'created_at');
        $salesAmountColumn = Schema::hasColumn('sales', 'total')
            ? 'total'
            : (Schema::hasColumn('sales', 'total_amount') ? 'total_amount' : null);

        $applyTenantScope = function ($query, string $table) {
            $user = Auth::user();
            $companyId = (int) ($user?->company_id ?? 0);

            if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
                $query->where(function ($sub) use ($table, $companyId, $user) {
                    $sub->where("{$table}.company_id", $companyId);
                    if ($user && Schema::hasColumn($table, 'user_id')) {
                        $sub->orWhere(function ($fallback) use ($table, $user) {
                            $fallback->whereNull("{$table}.company_id")
                                ->where("{$table}.user_id", $user->id);
                        });
                    }
                });
            } elseif ($user && Schema::hasColumn($table, 'user_id')) {
                $query->where("{$table}.user_id", $user->id);
            } elseif ($user && Schema::hasColumn($table, 'created_by')) {
                $query->where("{$table}.created_by", $user->id);
            }
        };

        // Base Sales Query (Income)
        $salesQuery = $this->scopedTable('sales')
            ->select([
                DB::raw("DATE(sales.{$salesDateColumn}) as report_date"),
                DB::raw('SUM(COALESCE(sales.' . ($salesAmountColumn ?? 'total') . ', 0)) as income'),
                DB::raw('0 as operating_expense'),
                DB::raw('0 as purchase_expense'),
            ])
            ->when(
                $start_date && $end_date,
                fn($q) => $q->whereBetween(DB::raw("DATE(sales.{$salesDateColumn})"), [$start_date, $end_date])
            )
            ->tap(fn($q) => $applyTenantScope($q, 'sales'))
            ->tap(fn($q) => $this->applySaleBranchFilter($q, 'sales'))
            ->groupBy(DB::raw("DATE(sales.{$salesDateColumn})"));

        // Base Expenses Query (Operational Expense)
        $expensesQuery = $this->scopedTable('expenses')
            ->select([
                DB::raw('DATE(expenses.created_at) as report_date'),
                DB::raw('0 as income'),
                DB::raw('SUM(COALESCE(expenses.amount, 0)) as operating_expense'),
                DB::raw('0 as purchase_expense'),
            ])
            ->when(
                $start_date && $end_date,
                fn($q) => $q->whereBetween(DB::raw('DATE(expenses.created_at)'), [$start_date, $end_date])
            )
            ->tap(fn($q) => $applyTenantScope($q, 'expenses'))
            ->groupBy(DB::raw('DATE(expenses.created_at)'));

        // Purchases Query (COGS/Purchase Cost)
        $purchaseDateColumn = Schema::hasColumn('purchases', 'purchase_date')
            ? 'purchase_date'
            : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');

        $purchaseBase = $this->scopedTable('purchases')
            ->when(
                $start_date && $end_date,
                fn($q) => $q->whereBetween(DB::raw("DATE(purchases.{$purchaseDateColumn})"), [$start_date, $end_date])
            )
            ->tap(fn($q) => $applyTenantScope($q, 'purchases'));

        $hasPurchaseRows = (clone $purchaseBase)->exists();

        if ($hasPurchaseRows) {
            $purchasesQuery = $purchaseBase
                ->select([
                    DB::raw("DATE(purchases.{$purchaseDateColumn}) as report_date"),
                    DB::raw('0 as income'),
                    DB::raw('0 as operating_expense'),
                    DB::raw('SUM(ABS(COALESCE(purchases.total_amount, 0))) as purchase_expense'),
                ])
                ->groupBy(DB::raw("DATE(purchases.{$purchaseDateColumn})"));
        } elseif (Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
            $purchasesQuery = $this->scopedTable('inventory_history')
                ->join('products', 'inventory_history.product_id', '=', 'products.id')
                ->select([
                    DB::raw('DATE(inventory_history.created_at) as report_date'),
                    DB::raw('0 as income'),
                    DB::raw('0 as operating_expense'),
                    DB::raw('SUM(COALESCE(inventory_history.quantity, 0) * COALESCE(products.purchase_price, products.price, 0)) as purchase_expense'),
                ])
                ->whereRaw("LOWER(COALESCE(inventory_history.type, '')) = 'in'")
                ->when(
                    $start_date && $end_date,
                    fn($q) => $q->whereBetween(DB::raw('DATE(inventory_history.created_at)'), [$start_date, $end_date])
                )
                ->when(
                    $companyId > 0 && Schema::hasColumn('products', 'company_id'),
                    fn($q) => $q->where('products.company_id', $companyId)
                )
                ->groupBy(DB::raw('DATE(inventory_history.created_at)'));
        } else {
            $purchasesQuery = $this->scopedTable('purchases')
                ->select([
                    DB::raw("DATE(NOW()) as report_date"),
                    DB::raw('0 as income'),
                    DB::raw('0 as operating_expense'),
                    DB::raw('0 as purchase_expense'),
                ])
                ->whereRaw('1 = 0');
        }

        $combined = DB::query()->fromSub(
            $salesQuery->unionAll($expensesQuery)->unionAll($purchasesQuery),
            'combined_data'
        );

        $totals = (clone $combined)->select([
            DB::raw('SUM(income) as total_income'),
            DB::raw('SUM(operating_expense) as total_operating_expense'),
            DB::raw('SUM(purchase_expense) as total_purchase_expense'),
            DB::raw('SUM(operating_expense + purchase_expense) as total_expense'),
        ])->first();

        $profitLossData = $combined->select([
            'report_date',
            DB::raw('SUM(income) as income'),
            DB::raw('SUM(operating_expense) as operating_expense'),
            DB::raw('SUM(purchase_expense) as purchase_expense'),
            DB::raw('SUM(operating_expense + purchase_expense) as expense'),
        ])
        ->groupBy('report_date')
        ->orderBy('report_date', 'desc')
        ->paginate(15);

        return $this->renderReportView('profit-loss-list', compact('profitLossData', 'totals'));
    }

        /** 12. Tax Purchase **/
        public function tax_purchase(Request $request)
        {
            $query = $this->scopedTable('purchases')->where('purchases.tax_amount', '>', 0);
            $purchaseDateExpression = Schema::hasColumn('purchases', 'purchase_date')
                ? 'purchases.purchase_date'
                : (Schema::hasColumn('purchases', 'date') ? 'purchases.date' : 'purchases.created_at');

            $supplierSelect = DB::raw("'N/A' as Supplier");
            $supplierSearchColumn = null;

            // Prefer vendor relation if available in current schema.
            if (
                Schema::hasColumn('purchases', 'vendor_id') &&
                Schema::hasTable('vendors') &&
                Schema::hasColumn('vendors', 'id') &&
                Schema::hasColumn('vendors', 'name')
            ) {
                $query->leftJoin('vendors', 'purchases.vendor_id', '=', 'vendors.id');
                $supplierSelect = DB::raw("COALESCE(vendors.name, 'N/A') as Supplier");
                $supplierSearchColumn = 'vendors.name';
            } elseif (
                Schema::hasColumn('purchases', 'supplier_id') &&
                Schema::hasTable('suppliers') &&
                Schema::hasColumn('suppliers', 'id')
            ) {
                $query->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id');

                if (Schema::hasColumn('suppliers', 'name')) {
                    $supplierSelect = DB::raw("COALESCE(suppliers.name, 'N/A') as Supplier");
                    $supplierSearchColumn = 'suppliers.name';
                } elseif (Schema::hasColumn('suppliers', 'supplier_name')) {
                    $supplierSelect = DB::raw("COALESCE(suppliers.supplier_name, 'N/A') as Supplier");
                    $supplierSearchColumn = 'suppliers.supplier_name';
                } elseif (Schema::hasColumn('suppliers', 'company_name')) {
                    $supplierSelect = DB::raw("COALESCE(suppliers.company_name, 'N/A') as Supplier");
                    $supplierSearchColumn = 'suppliers.company_name';
                }
            }

            $query->select([
                'purchases.id as Id',
                $supplierSelect,
                DB::raw("{$purchaseDateExpression} as Date"),
                DB::raw("COALESCE(purchases.purchase_no, CONCAT('PUR-', purchases.id)) as RefNo"),
                DB::raw('ABS(COALESCE(purchases.total_amount, 0)) as TotalAmount'),
                DB::raw("'N/A' as PaymentMethod"),
                DB::raw('0 as Discount'),
                'purchases.tax_amount as TaxAmount',
            ]);

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween(
                    DB::raw("DATE({$purchaseDateExpression})"),
                    [$request->start_date, $request->end_date]
                );
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($q) use ($search, $supplierSearchColumn) {
                    if ($supplierSearchColumn) {
                        $q->where($supplierSearchColumn, 'like', '%' . $search . '%');
                    }
                    $q->orWhere('purchases.purchase_no', 'like', '%' . $search . '%');
                });
            }

            $taxpurchases = $query->orderByDesc(DB::raw($purchaseDateExpression))
                ->get()
                ->map(fn ($item) => (array) $item);

            return $this->renderReportView('tax-purchase', compact('taxpurchases'));
        }


        

        public function tax_sales(Request $request)
        {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $salesDateExpression = Schema::hasColumn('sales', 'order_date')
                ? 'sales.order_date'
                : (Schema::hasColumn('sales', 'date') ? 'sales.date' : 'sales.created_at');

            if (!$start_date || !$end_date) {
                $latestTaxDate = $this->scopedTable('sales')
                    ->where('sales.tax', '>', 0)
                    ->tap(fn ($q) => $this->applyTenantScope($q, 'sales'))
                    ->tap(fn ($q) => $this->applySaleBranchFilter($q, 'sales'))
                    ->max(str_replace('sales.', '', $salesDateExpression));

                $effectiveEnd = $latestTaxDate
                    ? \Carbon\Carbon::parse($latestTaxDate)->endOfDay()
                    : now()->endOfDay();

                $end_date = $end_date ?: $effectiveEnd->toDateString();
                $start_date = $start_date ?: $effectiveEnd->copy()->startOfMonth()->toDateString();
            }

            $query = $this->scopedTable('sales')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->select([
                    'sales.id as Id',
                    DB::raw("COALESCE(customers.customer_name, sales.customer_name, 'Walk-in Customer') as Customer"),
                    DB::raw("{$salesDateExpression} as Date"),
                    'sales.invoice_no as InvoiceNo',
                    'sales.total as TotalAmount',
                    DB::raw("COALESCE(sales.payment_method, 'N/A') as PaymentMethod"),
                    'sales.discount as Discount',
                    'sales.tax as TaxAmount',
                ])
                ->where('sales.tax', '>', 0)
                ->tap(fn ($q) => $this->applyTenantScope($q, 'sales'))
                ->tap(fn ($q) => $this->applySaleBranchFilter($q, 'sales'));

            if ($start_date && $end_date) {
                $query->whereBetween(
                    DB::raw("DATE({$salesDateExpression})"),
                    [$start_date, $end_date]
                );
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('sales.invoice_no', 'like', '%' . $search . '%')
                      ->orWhere('sales.customer_name', 'like', '%' . $search . '%')
                      ->orWhere('customers.customer_name', 'like', '%' . $search . '%');
                });
            }

            $taxsales = $query->orderByDesc(DB::raw($salesDateExpression))
                ->limit(2000)
                ->get()
                ->map(function ($item) {
                    $row = (array) $item;
                    $row['Image'] = 'avatar-01.jpg';
                    return $row;
                });

            return $this->renderReportView('tax-sales', compact('taxsales'));
        }


        
    }
