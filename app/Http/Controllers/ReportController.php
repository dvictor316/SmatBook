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
use App\Support\LedgerService;



    class ReportController extends Controller
    {
        // ... rest of the code
        private function applyTenantScope($query, string $table)
        {
            $companyId = (int) (Auth::user()?->company_id ?? 0);
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
            return [
                'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
                'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
            ];
        }

        private function applySaleBranchFilter($query, string $salesTable = 'sales')
        {
            $branchName = trim((string) ($this->getActiveBranchContext()['name'] ?? ''));

            if ($branchName === '') {
                return $query;
            }

            return $query->where(function ($sub) use ($salesTable, $branchName) {
                if (Schema::hasColumn('sales', 'branch_name')) {
                    $sub->where("{$salesTable}.branch_name", $branchName);
                }

                $sub->orWhereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(COALESCE({$salesTable}.payment_details, '{}'), '$.branch_name')) = ?",
                    [$branchName]
                )->orWhereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(COALESCE({$salesTable}.payment_details, '{}'), '$.branch.name')) = ?",
                    [$branchName]
                );
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

        public function index(Request $request)
    {
        $activeBranch = $this->getActiveBranchContext();
        // 1. Parse Dates
        $start = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : \Carbon\Carbon::now()->startOfMonth();
        $end = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : \Carbon\Carbon::now()->endOfDay();

        // 2. Fetch and Map Data as ARRAYS (Removed (object) cast)
        $accountsQuery = \App\Models\Account::with(['transactions' => function($query) use ($start, $end) {
                $query->whereBetween('transaction_date', [$start, $end]);
            }]);
        $this->applyTenantScope($accountsQuery, 'accounts');
        $accounts = $accountsQuery->get()
            ->map(function($account) {
                $totalDebit = $account->transactions->sum('debit'); 
                $totalCredit = $account->transactions->sum('credit');
                $netBalance = $totalDebit - $totalCredit;

                $debitBalance = 0;
                $creditBalance = 0;

                // Asset/Expense logic
                if (in_array($account->type, ['Asset', 'Expense'])) {
                    $netBalance >= 0 ? $debitBalance = $netBalance : $creditBalance = abs($netBalance);
                } 
                // Liability/Equity/Revenue logic
                else {
                    $netBalance <= 0 ? $creditBalance = abs($netBalance) : $debitBalance = $netBalance;
                }

                // Return a plain ARRAY to prevent the "stdClass" error
                return [
                    'code' => $account->code ?? 'N/A',
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                ];
            })
            ->filter(fn($acc) => $acc['debit_balance'] > 0 || $acc['credit_balance'] > 0)
            ->sortBy('code')
            ->values();

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
                DB::raw("COALESCE(purchases.{$purchaseAmountColumn}, 0) as Amount"),
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

            if (!empty($activeBranch['name']) && Schema::hasColumn('purchases', 'branch_name')) {
                $query->where('purchases.branch_name', $activeBranch['name']);
            }

            $hasPurchaseRows = (clone $query)->exists();

            if ($hasPurchaseRows) {
                $purchases = $query->orderByDesc("purchases.{$purchaseDateColumn}")->paginate(20);
                $totalSum = (float) ((clone $query)->sum("purchases.{$purchaseAmountColumn}") ?? 0);
            }
        }
    }

    if (!$hasPurchaseRows && Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
        $historyQuery = DB::table('inventory_history')
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

        if (Schema::hasColumn('products', 'company_id') && (int) (Auth::user()?->company_id ?? 0) > 0) {
            $historyQuery->where('products.company_id', (int) Auth::user()->company_id);
        } elseif (Schema::hasColumn('products', 'user_id') && Auth::id()) {
            $historyQuery->where('products.user_id', Auth::id());
        }

        if (!empty($activeBranch['name']) && Schema::hasColumn('inventory_history', 'branch_name')) {
            $historyQuery->where('inventory_history.branch_name', $activeBranch['name']);
        }

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
            $query = DB::table('expenses')
                ->leftJoin('users', 'expenses.created_by', '=', 'users.id')
                ->select(
                    'expenses.*',
                    DB::raw("COALESCE(expenses.category, 'General') as category_name"),
                    DB::raw("COALESCE(users.name, 'System') as user_name")
                );

            $this->applyTenantScope($query, 'expenses');

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
            $sales = DB::table('sales')->select([
                DB::raw('DATE(created_at) as log_date'),
                DB::raw('total as amount'),
                DB::raw("'Inflow' as type"),
                DB::raw("'Sales' as label")
            ])->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);

            $other = DB::table('payments')->select([
                DB::raw('DATE(created_at) as log_date'),
                DB::raw('amount as amount'),
                DB::raw("'Inflow' as type"),
                DB::raw("'Misc' as label")
            ])->where('payment_method', '!=', 'Sales Payment')
            ->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);

            $exp = DB::table('expenses')->select([
                DB::raw('DATE(created_at) as log_date'),
                DB::raw('amount as amount'),
                DB::raw("'Outflow' as type"),
                DB::raw("COALESCE(category, 'Expense') as label")
            ])->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);

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
            $products = DB::table('products')
                ->select(['id', 'name', 'sku', 'stock', 'purchase_price', 'unit_type'])
                ->where('stock', '<=', $threshold)
                ->orderBy('stock', 'asc')
                ->get();

            return view('Reports.Reports.low-stock-report', compact('products', 'threshold', 'target'));
        }

        // Email AJAX Logic
        public function send_low_stock_email(Request $request)
        {
            $threshold = $request->get('min_qty', 15);
            
            $products = DB::table('products')
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

            return view('Reports.Reports.payment-report', compact('payments', 'totalAmount', 'activeBranch'));
        }

        $query = Payment::with($this->paymentReportRelations());
        $activeBranch = $this->getActiveBranchContext();

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if (!empty($activeBranch['name'])) {
            $query->whereHas('sale', function ($saleQuery) {
                $this->applySaleBranchFilter($saleQuery, 'sales');
            });
        }

        $totalAmount = (clone $query)->sum('amount');
        
        // Increased to 25 for better report viewing, preserved filters
        $payments = $query->latest()->paginate(25)->withQueryString();
        $payments->getCollection()->transform(function ($payment) {
            $payment->resolved_channel = $this->resolvePaymentChannel($payment);
            return $payment;
        });

        return view('Reports.Reports.payment-report', compact('payments', 'totalAmount', 'activeBranch'));
    }
        public function process_report_data(Request $request)
    {
        return $this->process_report(DB::table('payments'), $request, 'created_at', ['reference_no', 'payment_method']);
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

    if (!empty($activeBranch['name'])) {
        $baseQuery->whereHas('sale', function ($saleQuery) {
            $this->applySaleBranchFilter($saleQuery, 'sales');
        });
    }

    $query = clone $baseQuery;

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
    $payment = \App\Models\Payment::with($this->paymentReportRelations())->findOrFail($id);
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

    $payment = \App\Models\Payment::findOrFail($id);
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

    $role = strtolower((string) ($user->role ?? ''));
    if (in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true)) {
        return;
    }

    $companyId = (int) ($user->company_id ?? 0);

    $query->where(function ($paymentQuery) use ($companyId, $user) {
        if ($companyId > 0) {
            $paymentQuery->whereHas('sale', function ($saleQuery) use ($companyId, $user) {
                if (Schema::hasColumn('sales', 'company_id')) {
                    $saleQuery->where('company_id', $companyId);
                } elseif (Schema::hasColumn('sales', 'user_id')) {
                    $saleQuery->where('user_id', $user->id);
                }
            });
        }

        if (Schema::hasColumn('payments', 'created_by')) {
            $paymentQuery->orWhere('created_by', $user->id);
        }
    });
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
            \App\Models\Payment::whereIn('id', $ids)->delete();
            return response()->json([
                'success' => true, 
                'message' => count($ids) . ' records deleted permanently.'
            ]);
        }

        // 3. Otherwise, perform a standard status update
        \App\Models\Payment::whereIn('id', $ids)->update([
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
    $payment = \App\Models\Payment::findOrFail($id);
    $payment->delete();

    return response()->json([
        'success' => true,
        'message' => 'Payment deleted successfully.',
    ]);
}


        /** 5. Purchase Report **/
        public function purchase_return(Request $request)
        {
            $query = DB::table('purchase_transactions')
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
                ->orderByDesc('SoldAmount')
                ->orderBy('products.name')
                ->paginate(15)
                ->withQueryString();

            return $this->renderReportView('sales-report', compact('salesreports', 'activeBranch'));
        }

        /** 8. Sales Return Report **/
    public function sales_return_report(Request $request)
    {
        // Join products to get meaningful report data
        $query = DB::table('sale_items')
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
        $fromStart = Carbon::parse($fromDate)->startOfDay()->toDateTimeString();
        $toEnd = Carbon::parse($toDate)->endOfDay()->toDateTimeString();

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

        $products = DB::table('products')
            ->select('id', 'name')
            ->orderBy('name')
            ->tap(fn ($q) => $applyTenantScope($q, 'products'))
            ->get();

        if (!Schema::hasTable('purchase_items') || !Schema::hasTable('sale_items')) {
            return view('Reports.Reports.stock-report', [
                'stockreports' => collect(),
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'products' => $products,
                'productId' => $productId,
                'activeBranch' => $activeBranch,
            ])->with('error', 'Inventory movement tables are missing.');
        }

        $purchaseDateColumn = Schema::hasColumn('purchases', 'purchase_date')
            ? 'purchase_date'
            : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');

        $saleDateColumn = Schema::hasColumn('sales', 'order_date')
            ? 'order_date'
            : (Schema::hasColumn('sales', 'date') ? 'date' : 'created_at');

        $hasPurchaseQty = Schema::hasColumn('purchase_items', 'qty');
        $hasPurchaseQuantity = Schema::hasColumn('purchase_items', 'quantity');
        $hasPurchaseUnitPrice = Schema::hasColumn('purchase_items', 'unit_price');
        $hasPurchaseRate = Schema::hasColumn('purchase_items', 'rate');
        $hasSaleQty = Schema::hasColumn('sale_items', 'qty');
        $hasSaleQuantity = Schema::hasColumn('sale_items', 'quantity');
        $hasSaleUnitPrice = Schema::hasColumn('sale_items', 'unit_price');
        $hasSaleRate = Schema::hasColumn('sale_items', 'rate');
        $saleTotalColumn = Schema::hasColumn('sale_items', 'total_price')
            ? 'total_price'
            : (Schema::hasColumn('sale_items', 'subtotal') ? 'subtotal' : null);

        $purchaseQtyExpr = match (true) {
            $hasPurchaseQty && $hasPurchaseQuantity =>
                'COALESCE(NULLIF(purchase_items.qty, 0), purchase_items.quantity, 0)',
            $hasPurchaseQty => 'COALESCE(purchase_items.qty, 0)',
            $hasPurchaseQuantity => 'COALESCE(purchase_items.quantity, 0)',
            default => '0',
        };
        $purchasePriceExpr = match (true) {
            $hasPurchaseUnitPrice && $hasPurchaseRate =>
                'COALESCE(NULLIF(purchase_items.unit_price, 0), purchase_items.rate, 0)',
            $hasPurchaseUnitPrice => 'COALESCE(purchase_items.unit_price, 0)',
            $hasPurchaseRate => 'COALESCE(purchase_items.rate, 0)',
            default => '0',
        };
        $saleQtyExpr = match (true) {
            $hasSaleQty && $hasSaleQuantity =>
                'COALESCE(NULLIF(sale_items.qty, 0), sale_items.quantity, 0)',
            $hasSaleQty => 'COALESCE(sale_items.qty, 0)',
            $hasSaleQuantity => 'COALESCE(sale_items.quantity, 0)',
            default => '0',
        };
        $saleUnitPriceExpr = match (true) {
            $hasSaleUnitPrice && $hasSaleRate =>
                'COALESCE(NULLIF(sale_items.unit_price, 0), sale_items.rate, 0)',
            $hasSaleUnitPrice => 'COALESCE(sale_items.unit_price, 0)',
            $hasSaleRate => 'COALESCE(sale_items.rate, 0)',
            default => '0',
        };
        $saleTotalExpr = $saleTotalColumn
            ? "COALESCE(sale_items.{$saleTotalColumn}, ({$saleQtyExpr} * {$saleUnitPriceExpr}))"
            : "({$saleQtyExpr} * {$saleUnitPriceExpr})";

        $stockIn = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->select([
                DB::raw('DATE(purchases.' . $purchaseDateColumn . ') as log_date'),
                DB::raw("SUM({$purchaseQtyExpr}) as qty_in"),
                DB::raw('0 as qty_out'),
                DB::raw("SUM({$purchaseQtyExpr} * {$purchasePriceExpr}) as val_in"),
                DB::raw('0 as val_out'),
            ])
            ->whereBetween('purchases.' . $purchaseDateColumn, [$fromStart, $toEnd])
            ->when(!empty($productId), fn ($q) => $q->where('purchase_items.product_id', $productId))
            ->tap(fn ($q) => $applyTenantScope($q, 'purchases'))
            ->groupBy('log_date');

        $stockInExists = (clone $stockIn)->exists();

        if (!$stockInExists) {
            if (Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
                $historyStockIn = DB::table('inventory_history')
                    ->join('products', 'inventory_history.product_id', '=', 'products.id')
                    ->select([
                        DB::raw('DATE(inventory_history.created_at) as log_date'),
                        DB::raw('SUM(COALESCE(inventory_history.quantity, 0)) as qty_in'),
                        DB::raw('0 as qty_out'),
                        DB::raw('SUM(COALESCE(inventory_history.quantity, 0) * COALESCE(products.purchase_price, products.price, 0)) as val_in'),
                        DB::raw('0 as val_out'),
                    ])
                    ->whereRaw("LOWER(COALESCE(inventory_history.type, '')) = 'in'")
                    ->whereBetween('inventory_history.created_at', [$fromStart, $toEnd])
                    ->when(!empty($productId), fn ($q) => $q->where('inventory_history.product_id', $productId))
                    ->when(
                        $companyId > 0 && Schema::hasColumn('products', 'company_id'),
                        fn ($q) => $q->where('products.company_id', $companyId)
                    )
                    ->groupBy('log_date');

                $this->applyInventoryHistoryBranchFilter($historyStockIn, 'inventory_history');

                $stockIn = DB::query()->fromSub($historyStockIn, 'stk_in');
            } elseif (Schema::hasTable('purchases')) {
                $headerStockIn = DB::table('purchases')
                    ->select([
                        DB::raw('DATE(purchases.' . $purchaseDateColumn . ') as log_date'),
                        DB::raw('COUNT(*) as qty_in'),
                        DB::raw('0 as qty_out'),
                        DB::raw('SUM(COALESCE(purchases.total_amount, 0)) as val_in'),
                        DB::raw('0 as val_out'),
                    ])
                    ->whereBetween('purchases.' . $purchaseDateColumn, [$fromStart, $toEnd])
                    ->tap(fn ($q) => $applyTenantScope($q, 'purchases'))
                    ->groupBy('log_date');

                $stockIn = DB::query()->fromSub($headerStockIn, 'stk_in');
            }
        }

        $stockOut = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select([
                DB::raw('DATE(sales.' . $saleDateColumn . ') as log_date'),
                DB::raw('0 as qty_in'),
                DB::raw("SUM({$saleQtyExpr}) as qty_out"),
                DB::raw('0 as val_in'),
                DB::raw("SUM({$saleTotalExpr}) as val_out"),
            ])
            ->whereBetween('sales.' . $saleDateColumn, [$fromStart, $toEnd])
            ->when(!empty($productId), fn ($q) => $q->where('sale_items.product_id', $productId))
            ->tap(fn ($q) => $applyTenantScope($q, 'sales'))
            ->groupBy('log_date');

        $this->applySaleBranchFilter($stockOut, 'sales');

        $stockOutExists = (clone $stockOut)->exists();

        if (!$stockOutExists) {
            if (Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
                $historyStockOut = DB::table('inventory_history')
                    ->join('products', 'inventory_history.product_id', '=', 'products.id')
                    ->select([
                        DB::raw('DATE(inventory_history.created_at) as log_date'),
                        DB::raw('0 as qty_in'),
                        DB::raw('SUM(COALESCE(inventory_history.quantity, 0)) as qty_out'),
                        DB::raw('0 as val_in'),
                        DB::raw('SUM(COALESCE(inventory_history.quantity, 0) * COALESCE(products.price, products.purchase_price, 0)) as val_out'),
                    ])
                    ->whereRaw("LOWER(COALESCE(inventory_history.type, '')) = 'out'")
                    ->whereBetween('inventory_history.created_at', [$fromStart, $toEnd])
                    ->when(!empty($productId), fn ($q) => $q->where('inventory_history.product_id', $productId))
                    ->when(
                        $companyId > 0 && Schema::hasColumn('products', 'company_id'),
                        fn ($q) => $q->where('products.company_id', $companyId)
                    )
                    ->groupBy('log_date');

                $this->applyInventoryHistoryBranchFilter($historyStockOut, 'inventory_history');

                $stockOut = DB::query()->fromSub($historyStockOut, 'stk_out');
            } elseif (Schema::hasTable('sales')) {
                $headerStockOut = DB::table('sales')
                    ->select([
                        DB::raw('DATE(sales.' . $saleDateColumn . ') as log_date'),
                        DB::raw('0 as qty_in'),
                        DB::raw('COUNT(*) as qty_out'),
                        DB::raw('0 as val_in'),
                        DB::raw('SUM(COALESCE(sales.total, 0)) as val_out'),
                    ])
                    ->whereBetween('sales.' . $saleDateColumn, [$fromStart, $toEnd])
                    ->tap(fn ($q) => $applyTenantScope($q, 'sales'))
                    ->groupBy('log_date');

                $stockOut = DB::query()->fromSub($headerStockOut, 'stk_out');
            }
        }

        $results = DB::table(DB::query()->fromSub($stockIn->unionAll($stockOut), 'stk'))
            ->select([
                'log_date',
                DB::raw('SUM(qty_in) as total_qty_in'),
                DB::raw('SUM(qty_out) as total_qty_out'),
                DB::raw('SUM(val_in) as total_val_in'),
                DB::raw('SUM(val_out) as total_val_out'),
            ])
            ->groupBy('log_date')
            ->orderBy('log_date', 'desc')
            ->get();

        $stockreports = $results->map(fn($item) => [
            'Date' => \Carbon\Carbon::parse($item->log_date)->format('d M y'),
            'QtyIn' => (float)$item->total_qty_in,
            'QtyOut' => (float)$item->total_qty_out,
            'ValIn' => (float)$item->total_val_in,
            'ValOut' => (float)$item->total_val_out,
            'NetValue' => (float)$item->total_val_in - (float)$item->total_val_out,
        ]);

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
            Mail::to('warehouse@yourcompany.com')->send(new LowStockReportMail($pdf->output()));
            return response()->json(['success' => true, 'message' => 'Report sent to Warehouse Manager successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()]);
        }
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
            $query = DB::table('purchase_return_items')
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
            $purchases = DB::table('purchases')
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
            $query = DB::table('credit_note_items')
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
        $invoices = DB::table('sales')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'sales.id', 
                'sales.id as display_name', 
                'customers.customer_name'
            )
            ->orderBy('sales.id', 'desc')
            ->get();

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
            $items = DB::table('purchase_items')
                ->join('products', 'purchase_items.product_id', '=', 'products.id')
                ->where('purchase_id', $id)
                ->select('products.id as product_id', 'products.name', 'purchase_items.qty', 'purchase_items.unit_price')
                ->get();

            return response()->json($items);
        }

    public function get_invoice_items($id)
    {
        // Fetch items linked to the sale_id
        $items = DB::table('sale_items')
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
        return DB::table('purchase_items')
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
        $query = DB::table('purchase_items')
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
        $salesQuery = DB::table('sales')
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
            ->groupBy(DB::raw("DATE(sales.{$salesDateColumn})"));

        // Base Expenses Query (Operational Expense)
        $expensesQuery = DB::table('expenses')
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

        $purchaseBase = DB::table('purchases')
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
                    DB::raw('SUM(COALESCE(purchases.total_amount, 0)) as purchase_expense'),
                ])
                ->groupBy(DB::raw("DATE(purchases.{$purchaseDateColumn})"));
        } elseif (Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
            $purchasesQuery = DB::table('inventory_history')
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
            $purchasesQuery = DB::table('purchases')
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
            $query = DB::table('purchases')->where('purchases.tax_amount', '>', 0);

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
                'purchases.created_at as Date',
                DB::raw("COALESCE(purchases.purchase_no, CONCAT('PUR-', purchases.id)) as RefNo"),
                'purchases.total_amount as TotalAmount',
                DB::raw("'N/A' as PaymentMethod"),
                DB::raw('0 as Discount'),
                'purchases.tax_amount as TaxAmount',
            ]);

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween(DB::raw('DATE(purchases.created_at)'), [$request->start_date, $request->end_date]);
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

            $taxpurchases = $query->orderByDesc('purchases.created_at')
                ->get()
                ->map(fn ($item) => (array) $item);

            if ($taxpurchases->isEmpty() && Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
                $fallback = DB::table('inventory_history')
                    ->join('products', 'inventory_history.product_id', '=', 'products.id')
                    ->select([
                        'inventory_history.id as Id',
                        DB::raw("'Inventory History' as Supplier"),
                        'inventory_history.created_at as Date',
                        DB::raw("CONCAT('HIST-IN-', inventory_history.id) as RefNo"),
                        DB::raw('COALESCE(inventory_history.quantity, 0) * COALESCE(products.purchase_price, products.price, 0) as TotalAmount'),
                        DB::raw("'N/A' as PaymentMethod"),
                        DB::raw('0 as Discount'),
                        DB::raw('0 as TaxAmount'),
                    ])
                    ->whereRaw("LOWER(COALESCE(inventory_history.type, '')) = 'in'");

                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $fallback->whereBetween(DB::raw('DATE(inventory_history.created_at)'), [$request->start_date, $request->end_date]);
                }

                if ($request->filled('search')) {
                    $search = trim((string) $request->search);
                    $fallback->where(function ($q) use ($search) {
                        $q->where('products.name', 'like', '%' . $search . '%')
                            ->orWhere('products.sku', 'like', '%' . $search . '%')
                            ->orWhere('inventory_history.id', 'like', '%' . $search . '%');
                    });
                }

                $taxpurchases = $fallback->orderByDesc('inventory_history.created_at')
                    ->get()
                    ->map(fn ($item) => (array) $item);
            }

            return $this->renderReportView('tax-purchase', compact('taxpurchases'));
        }


        

        public function tax_sales(Request $request)
        {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->select([
                    'sales.id as Id',
                    DB::raw("COALESCE(customers.customer_name, sales.customer_name, 'Walk-in Customer') as Customer"),
                    'sales.created_at as Date',
                    'sales.invoice_no as InvoiceNo',
                    'sales.total as TotalAmount',
                    DB::raw("COALESCE(sales.payment_method, 'N/A') as PaymentMethod"),
                    'sales.discount as Discount',
                    'sales.tax as TaxAmount',
                ])
                ->where('sales.tax', '>', 0);

            if ($start_date && $end_date) {
                $query->whereBetween(DB::raw('DATE(sales.created_at)'), [$start_date, $end_date]);
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('sales.invoice_no', 'like', '%' . $search . '%')
                      ->orWhere('sales.customer_name', 'like', '%' . $search . '%')
                      ->orWhere('customers.customer_name', 'like', '%' . $search . '%');
                });
            }

            $taxsales = $query->orderByDesc('sales.created_at')
                ->get()
                ->map(function ($item) {
                    $row = (array) $item;
                    $row['Image'] = 'avatar-01.jpg';
                    return $row;
                });

            return $this->renderReportView('tax-sales', compact('taxsales'));
        }


        
    }
