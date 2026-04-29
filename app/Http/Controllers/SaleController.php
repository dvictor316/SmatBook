<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Bank;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\HasUniqueReceiptNumber;
use App\Events\NewSaleRegistered; // The Pusher event we created
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Support\BranchInventoryService;
use App\Support\GeoCurrency;
use App\Support\InventoryQuantity;
use App\Support\LedgerService;

class SaleController extends Controller
{
    use HasUniqueReceiptNumber;

    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

    private function hasDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            Log::error('SaleController database connection failure', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return false;
        }
    }

    private function hasTableColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function customerNameColumn(): ?string
    {
        if ($this->hasTableColumn('customers', 'customer_name')) {
            return 'customer_name';
        }

        if ($this->hasTableColumn('customers', 'name')) {
            return 'name';
        }

        return null;
    }

    private function bankLabelColumn(): string
    {
        if ($this->hasTableColumn('banks', 'name')) {
            return 'name';
        }

        if ($this->hasTableColumn('banks', 'account_number')) {
            return 'account_number';
        }

        return 'id';
    }

    private function applyCustomerNameFilter($query, string $term): void
    {
        $customerNameColumn = $this->customerNameColumn();
        if (!$customerNameColumn) {
            return;
        }

        $query->whereHas('customer', function ($customerQuery) use ($term, $customerNameColumn) {
            $customerQuery->where($customerNameColumn, 'like', '%' . $term . '%');
        });
    }

    private function posFallbackView(array $activeBranch, string $message)
    {
        session()->flash('error', $message);

        return view('pos.index', [
            'products' => collect(),
            'customers' => collect(),
            'sales' => collect(),
            'activeBranch' => $activeBranch,
            'bankAccounts' => collect(),
            'depositAccounts' => collect(),
        ]);
    }

    private function decrementSellableStock(Product $product, float $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $updates = [
            'stock' => DB::raw('stock - ' . $quantity),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('products', 'stock_quantity')) {
            $updates['stock_quantity'] = DB::raw('stock_quantity - ' . $quantity);
        }

        $updated = DB::table('products')
            ->where('id', $product->id)
            ->where('stock', '>=', $quantity)
            ->update($updates);

        if ($updated === 0) {
            throw new \RuntimeException("Insufficient stock for {$product->name}. Negative stock is not allowed.");
        }
    }

    private function tenantCompanyId(): int
    {
        return (int) (auth()->user()?->company_id
            ?? session('current_tenant_id')
            ?? optional(auth()->user()?->company)->id
            ?? 0);
    }

    private function applyTenantScope($query, string $table)
    {
        $companyId = $this->tenantCompanyId();
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            return $query->where(function ($sub) use ($table, $companyId, $userId) {
                $sub->where("{$table}.company_id", $companyId);

                if ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
                    $sub->orWhere(function ($fallback) use ($table, $userId) {
                        $fallback->whereNull("{$table}.company_id")
                            ->where("{$table}.user_id", $userId);
                    });
                }
            });
        }

        if ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            return $query->where("{$table}.user_id", $userId);
        }

        return $query;
    }

    private function applyBranchScope($query, string $table)
    {
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        return $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->orWhere("{$table}.branch_name", $branchName);
            }
        });
    }

    private function findScopedSale(int|string $saleId, array $with = []): ?Sale
    {
        $baseQuery = Sale::query()->with($with);
        $this->applyTenantScope($baseQuery, 'sales');

        $branchQuery = clone $baseQuery;
        $this->applyBranchScope($branchQuery, 'sales');
        $sale = $branchQuery->find($saleId);

        return $sale ?: $baseQuery->find($saleId);
    }

    private function scopedCustomers()
    {
        $query = Customer::query();
        $this->applyTenantScope($query, 'customers');
        $this->applyBranchScope($query, 'customers');

        $customerNameColumn = $this->customerNameColumn() ?: 'id';
        return $query->orderBy($customerNameColumn, 'asc');
    }

    private function scopedProducts()
    {
        $query = Product::query()->orderBy('name', 'asc');
        $this->applyTenantScope($query, 'products');
        $this->applyBranchScope($query, 'products');

        return $query;
    }

    private function clearDashboardMetricsCache(?string $branchId = null): void
    {
        $companyId = $this->tenantCompanyId();
        if ($companyId <= 0) {
            return;
        }

        Cache::forget('metrics_co_' . $companyId);
        Cache::forget('metrics_co_' . $companyId . '_global');

        $activeBranchId = $branchId ?: ($this->getActiveBranchContext()['id'] ?? null);
        if ($activeBranchId) {
            Cache::forget('metrics_co_' . $companyId . '_branch_' . $activeBranchId);
        }
    }

    private function companyScopedSettingKey(string $baseKey): string
    {
        $companyId = $this->tenantCompanyId();

        return $companyId > 0 ? "{$baseKey}_company_{$companyId}" : $baseKey;
    }

    private function getAvailableBranches(): array
    {
        $raw = Setting::where('key', $this->companyScopedSettingKey('branches_json'))->value('value');
        $decoded = json_decode((string) $raw, true);

        return collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($branch) => !empty($branch['id']) && !empty($branch['name']))
            ->values()
            ->all();
    }

    public function index(Request $request)
    {
        // 1. Start the query with relationships
        $query = Sale::with(['customer', 'user']);
        $activeBranch = $this->getActiveBranchContext();
        $this->applyTenantScope($query, 'sales');
        $this->applyBranchScope($query, 'sales');
        $salesDateColumn = Schema::hasColumn('sales', 'order_date') ? 'order_date' : 'created_at';

        // 2. Apply Filters
        if ($request->invoice_no) {
            $query->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }
        if ($request->customer_name) {
            $this->applyCustomerNameFilter($query, (string) $request->customer_name);
        }
        if ($request->sale_date) {
            $query->whereDate($salesDateColumn, $request->sale_date);
        }
        if ($request->filled('date_from')) {
            $query->whereDate($salesDateColumn, '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate($salesDateColumn, '<=', $request->date_to);
        }

        // 3. Calculate Stats before pagination
        $totalRevenue = $query->sum('total');
        $totalSalesCount = $query->count();

        // 4. Paginate
        $sales = $query->orderByDesc($salesDateColumn)->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('Sales.index', compact('sales', 'totalRevenue', 'totalSalesCount', 'activeBranch'));
    }

    public function report(Request $request)
    {
        $baseStockExpression = Schema::hasColumn('products', 'stock')
            ? 'COALESCE(products.stock, 0)'
            : (Schema::hasColumn('products', 'stock_quantity') ? 'COALESCE(products.stock_quantity, 0)' : '0');
        $priceExpression = Schema::hasColumn('products', 'product_price')
            ? 'COALESCE(products.product_price, 0)'
            : (Schema::hasColumn('products', 'price') ? 'COALESCE(products.price, 0)' : '0');

        $branchOptions = collect($this->getAvailableBranches())
            ->mapWithKeys(fn ($branch) => [(string) $branch['id'] => (string) $branch['name']])
            ->all();
        $activeBranch = $this->getActiveBranchContext();
        $selectedBranchId = trim((string) ($request->branch_id ?: ($activeBranch['id'] ?? '')));
        $selectedBranchName = $selectedBranchId !== ''
            ? (string) ($branchOptions[$selectedBranchId] ?? ($activeBranch['name'] ?? ''))
            : trim((string) ($activeBranch['name'] ?? ''));

        $staffOptions = [];
        if (Schema::hasTable('users')) {
            $staffQuery = User::query()->select(['id', 'name', 'email']);
            $this->applyTenantScope($staffQuery, 'users');
            $staffOptions = $staffQuery->orderBy('name')->get()
                ->mapWithKeys(function ($user) {
                    $label = trim((string) ($user->name ?? ''));
                    $label = $label !== '' ? $label : (string) ($user->email ?? ('User #' . $user->id));
                    if (!empty($user->email) && $label !== $user->email) {
                        $label .= ' (' . $user->email . ')';
                    }
                    return [(string) $user->id => $label];
                })
                ->all();
        }

        $salesAgg = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products as sale_products', 'sale_items.product_id', '=', 'sale_products.id')
            ->selectRaw("
                sale_items.product_id,
                COALESCE(SUM(" . InventoryQuantity::saleStockUnitsExpression('sale_items', 'sale_products') . "), 0) as total_sold_qty,
                COALESCE(SUM(
                    CASE
                        WHEN sale_items.total_price IS NOT NULL THEN sale_items.total_price
                        WHEN sale_items.subtotal IS NOT NULL THEN sale_items.subtotal
                        ELSE " . InventoryQuantity::saleItemQuantityColumn('sale_items') . " * COALESCE(sale_items.unit_price, 0)
                    END
                ), 0) as total_sold_amount
            ")
            ->groupBy('sale_items.product_id');
        $this->applyTenantScope($salesAgg, 'sales');

        if ($selectedBranchId !== '' || $selectedBranchName !== '') {
            $salesAgg->where(function ($builder) use ($selectedBranchId, $selectedBranchName) {
                $matched = false;

                if ($selectedBranchId !== '' && Schema::hasColumn('sales', 'branch_id')) {
                    $builder->where('sales.branch_id', $selectedBranchId);
                    $matched = true;
                }

                if ($selectedBranchName !== '' && Schema::hasColumn('sales', 'branch_name')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $builder->{$method}('sales.branch_name', $selectedBranchName);
                }
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
        } else {
            $search = '';
        }

        if ($request->filled('date_from') && Schema::hasColumn('sales', 'created_at')) {
            $salesAgg->whereDate('sales.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to') && Schema::hasColumn('sales', 'created_at')) {
            $salesAgg->whereDate('sales.created_at', '<=', $request->date_to);
        }

        if ($request->filled('staff_id') && Schema::hasColumn('sales', 'user_id')) {
            $salesAgg->where('sales.user_id', (int) $request->staff_id);
        }

        if ($request->filled('payment_status') && Schema::hasColumn('sales', 'payment_status')) {
            $salesAgg->where('sales.payment_status', (string) $request->payment_status);
        }

        $branchStockValueExpression = $baseStockExpression;
        $branchStockSelect = "CASE WHEN {$branchStockValueExpression} < 0 THEN 0 ELSE {$branchStockValueExpression} END as instock_qty";

        $query = Product::query()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id');

        if (Schema::hasTable('product_branch_stocks') && ($selectedBranchId !== '' || $selectedBranchName !== '')) {
            $query->leftJoin('product_branch_stocks', function ($join) use ($selectedBranchId, $selectedBranchName) {
                $join->on('product_branch_stocks.product_id', '=', 'products.id');

                if ($selectedBranchId !== '' && Schema::hasColumn('product_branch_stocks', 'branch_id')) {
                    $join->where('product_branch_stocks.branch_id', $selectedBranchId);
                } elseif ($selectedBranchName !== '' && Schema::hasColumn('product_branch_stocks', 'branch_name')) {
                    $join->where('product_branch_stocks.branch_name', $selectedBranchName);
                }
            });

            $branchStockValueExpression = "COALESCE(product_branch_stocks.quantity, {$baseStockExpression}, 0)";
            $branchStockSelect = "CASE WHEN {$branchStockValueExpression} < 0 THEN 0 ELSE {$branchStockValueExpression} END as instock_qty";
        }

        $query->leftJoinSub($salesAgg, 'sales_report', function ($join) {
                $join->on('products.id', '=', 'sales_report.product_id');
            })
            ->selectRaw("
                products.id,
                products.name as product_name,
                products.sku,
                categories.name as category_name,
                {$priceExpression} as product_price,
                {$branchStockSelect},
                COALESCE(sales_report.total_sold_qty, 0) as total_sold_qty,
                COALESCE(sales_report.total_sold_amount, 0) as total_sold_amount
            ")
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'categories.name',
                DB::raw($priceExpression),
                DB::raw($branchStockValueExpression),
                'sales_report.total_sold_qty',
                'sales_report.total_sold_amount'
            );
        $this->applyTenantScope($query, 'products');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('categories.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->integer('category_id'));
        }

        $reports = $query
            // Keep report rows stable by product creation order so new items append at the end.
            ->orderBy('products.id')
            ->paginate(15)
            ->withQueryString();

        return view('pos.reports', compact('reports', 'branchOptions', 'staffOptions'));
    }

    public function posSales(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'items']);
        $activeBranch = $this->getActiveBranchContext();
        $this->applyTenantScope($query, 'sales');
        $this->applyBranchScope($query, 'sales');

        if (Schema::hasColumn('sales', 'terminal_id')) {
            $query->whereNotNull('terminal_id');
        } elseif (Schema::hasColumn('sales', 'payment_details')) {
            $query->where(function ($builder) {
                $builder->where('payment_details->source', 'pos')
                    ->orWhere('payment_details', 'like', '%"source":"pos"%');
            });
        }

        if ($request->invoice_no) {
            $query->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }
        if ($request->customer_name) {
            $this->applyCustomerNameFilter($query, (string) $request->customer_name);
        }
        if ($request->sale_date) {
            $query->whereDate('created_at', $request->sale_date);
        }

        $totalRevenue = (clone $query)->sum('total');
        $totalSalesCount = (clone $query)->count();
        $sales = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('pos.sales', compact('sales', 'totalRevenue', 'totalSalesCount', 'activeBranch'));
    }


public function customerDetails($id = null)
{
    // 1. Safety Check: If no ID, go back to list
    if (!$id) {
        return redirect()->route('customers.index')->with('error', 'Please select a customer.');
    }

    // 2. Fetch the Customer
    $customer = \App\Models\Customer::findOrFail($id);
    
    // 3. Fetch the Invoices (This was missing!)
    // Assuming your relationship is defined, or query directly:
    $invoices = \App\Models\Invoice::where('customer_id', $id)
                                   ->orderBy('created_at', 'desc')
                                   ->get();

    // 4. Prepare the Statistics for the invoices-card component
    $invoicescards = [
        'total_amount'   => $invoices->sum('total_amount'),
        'paid_amount'    => $invoices->sum('paid_amount'),
        'balance_amount' => $invoices->sum('balance'),
        'overdue_amount' => $invoices->where('status', 'overdue')->sum('balance'),
    ];

    // 5. Return View with all defined variables
    return view('Customers.customer-details', compact('customer', 'invoices', 'invoicescards'));
}


    public function showPos()
    {
        $activeBranch = $this->getActiveBranchContext();
        if (!$this->hasDatabaseConnection()) {
            return $this->posFallbackView(
                $activeBranch,
                'POS is temporarily unavailable because the database connection failed.'
            );
        }

        try {
            $products = collect();
            if (Schema::hasTable('products')) {
                $this->branchInventory->backfillMissingBranchStocks($activeBranch, $this->tenantCompanyId());

                $hasCategories = Schema::hasTable('categories') && Schema::hasColumn('products', 'category_id');
                $hasBranchStocksBranchId = Schema::hasTable('product_branch_stocks')
                    && Schema::hasColumn('product_branch_stocks', 'branch_id');
                $productsQuery = Product::query();
                if ($hasCategories) {
                    $productsQuery->with('category');
                }

                $orderColumn = Schema::hasColumn('products', 'name')
                    ? 'name'
                    : (Schema::hasColumn('products', 'id') ? 'id' : null);
                if ($orderColumn) {
                    $productsQuery->orderBy($orderColumn, 'asc');
                }

                $this->applyTenantScope($productsQuery, 'products');
                if (!empty($activeBranch['id']) && $hasBranchStocksBranchId) {
                    $branchId = (string) $activeBranch['id'];
                    $branchName = (string) ($activeBranch['name'] ?? '');
                    $productsQuery->where(function ($q) use ($branchId, $branchName) {
                        $q->whereHas('branchStocks', fn ($sub) => $sub->where('branch_id', $branchId));
                        $q->orWhereDoesntHave('branchStocks');
                        if (Schema::hasColumn('products', 'branch_id')) {
                            $q->orWhere('products.branch_id', $branchId);
                        }
                        if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                            $q->orWhere('products.branch_name', $branchName);
                        }
                    });
                } else {
                    $this->applyBranchScope($productsQuery, 'products');
                }

                if ($hasBranchStocksBranchId && !empty($activeBranch['id'])) {
                    $productsQuery->with(['branchStocks' => function ($query) use ($activeBranch) {
                        $query->where('branch_id', $activeBranch['id']);
                    }]);
                }

                $products = $productsQuery
                    ->get()
                    ->map(function ($product) use ($activeBranch) {
                        $availableStock = $this->branchInventory->getAvailableStock($product, $activeBranch);
                        $product->setAttribute('available_stock', $availableStock);

                        return $product;
                    })
                    ->sortByDesc(fn ($product) => (float) ($product->available_stock ?? 0) > 0 ? 1 : 0)
                    ->values();
            }

            $customers = collect();
            if (Schema::hasTable('customers')) {
                $customerOrderColumn = $this->customerNameColumn() ?? 'id';
                $customers = Customer::query()
                    ->orderBy($customerOrderColumn, 'asc')
                    ->tap(fn ($query) => $this->applyTenantScope($query, 'customers'))
                    ->tap(fn ($query) => $this->applyBranchScope($query, 'customers'))
                    ->get();
            }

            $sales = Schema::hasTable('sales')
                ? Sale::with('customer')
                    ->tap(fn ($query) => $this->applyTenantScope($query, 'sales'))
                    ->tap(fn ($query) => $this->applyBranchScope($query, 'sales'))
                    ->latest()
                    ->take(10)
                    ->get()
                : collect();

            $bankAccounts = collect();
            if (Schema::hasTable('banks')) {
                $bankOrderColumn = $this->bankLabelColumn();
                $bankQuery = Bank::query()->orderBy($bankOrderColumn);
                $this->applyTenantScope($bankQuery, 'banks');
                $this->applyBranchScope($bankQuery, 'banks');
                $bankAccounts = $bankQuery->get();
            }

            // Active Asset accounts from Chart of Accounts — used as deposit/collection accounts
            // These drive journal entries (Moniepoint, First Bank, Petty Cash, etc.)
            $depositAccounts = collect();
            if (Schema::hasTable('accounts')) {
                $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
                $depositQuery = Account::withoutGlobalScopes()
                    ->where('type', Account::TYPE_ASSET)
                    ->where('is_active', true)
                    ->orderBy('name');
                if ($companyId > 0) {
                    $depositQuery->where('company_id', $companyId);
                }
                $depositAccounts = $depositQuery->get();
            }

            return view('pos.index', compact('products', 'customers', 'sales', 'activeBranch', 'bankAccounts', 'depositAccounts'));
        } catch (\Throwable $e) {
            Log::error('POS page failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return $this->posFallbackView(
                $activeBranch,
                'POS page hit a compatibility error and has been recovered with a safe fallback.'
            );
        }
    }

    public function showSale($id)
    {
        $sale = $this->findScopedSale($id, ['customer', 'items.product', 'user']);
        abort_if(!$sale, 404);
        $activeBranch = $this->getActiveBranchContext();
        $bankAccounts = Schema::hasTable('banks')
            ? Bank::query()->orderBy('name')->get()
            : collect();

        return view('Sales.show', compact('sale', 'activeBranch', 'bankAccounts'));
    }

    public function destroy($id)
    {
        $sale = $this->findScopedSale($id, ['items.product', 'payments']);
        if (!$sale) {
            return redirect()->route('pos.sales')->with('error', 'Sale record not found.');
        }

        DB::beginTransaction();

        try {
            $activeBranch = $this->getActiveBranchContext();

            foreach ($sale->items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (!$product) {
                    continue;
                }

                $quantity = $this->resolveStockUnitsForSale(
                    $product,
                    [
                        'unitType' => $item->unit_type ?? 'unit',
                        'stockUnits' => $item->stock_units ?? null,
                    ],
                    (float) ($item->qty ?? $item->quantity ?? 0)
                );

                if ($quantity <= 0) {
                    continue;
                }

                $product->increment('stock', $quantity);
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $product->increment('stock_quantity', $quantity);
                }

                $this->branchInventory->adjustBranchStock(
                    $product,
                    $quantity,
                    [
                        'id' => $sale->branch_id ?? $activeBranch['id'],
                        'name' => $sale->branch_name ?? $activeBranch['name'],
                    ],
                    (int) ($product->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0)
                );
            }

            if (method_exists($sale, 'payments')) {
                $paymentIds = $sale->payments->pluck('id')->filter()->all();
                if (!empty($paymentIds)) {
                    Transaction::query()
                        ->whereIn('related_id', $paymentIds)
                        ->where('related_type', Payment::class)
                        ->delete();
                    Payment::query()->whereIn('id', $paymentIds)->delete();
                }
            }

            Transaction::query()
                ->where('related_id', $sale->id)
                ->where('related_type', Sale::class)
                ->delete();

            $sale->items()->delete();
            $sale->delete();

            DB::commit();

            $this->clearDashboardMetricsCache($sale->branch_id ?? null);

            return redirect()->route('pos.sales')->with('success', 'POS sale deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to delete POS sale: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
{
    $request->validate([
        'customer_id'    => 'nullable|exists:customers,id',
        'payment_method' => 'required|string|in:Cash,cash,Split,split',
        'total'          => 'required|numeric|min:0',
        'paid'           => 'required|numeric|min:0',
        'items'          => 'required|array|min:1',
        'items.*.id'     => 'required|exists:products,id',
        'items.*.qty'    => 'required|numeric|gt:0',
        'items.*.unitType' => 'nullable|in:unit,roll,carton',
        'items.*.stockUnits' => 'nullable|numeric|gt:0',
        'items.*.priceLevel' => 'nullable|in:retail,wholesale,special',
        'deposit_account_id' => 'nullable|exists:accounts,id',
        'payment_account_id' => 'nullable|exists:accounts,id',
        'split_details.card_account_id' => 'nullable|exists:accounts,id',
        'split_details.transfer_account_id' => 'nullable|exists:accounts,id',
    ]);

    $paidAmount = (float) $request->paid;
    $totalAmount = (float) $request->total;
    $paymentMethod = strtolower((string) $request->payment_method);
    $splitDetails = $this->normalizeSplitDetails($request->input('split_details', []));

    if (!in_array($paymentMethod, ['cash', 'split'], true)) {
        return response()->json(['success' => false, 'message' => 'POS only accepts cash or split (cash + transfer) sales. Use Invoices for credit sales.'], 422);
    }

    if ($paymentMethod === 'split') {
        $splitPaid = round(((float) $splitDetails['cash']) + ((float) $splitDetails['transfer']) + ((float) $splitDetails['card']), 2);
        if ($splitPaid <= 0) {
            return response()->json(['success' => false, 'message' => 'Enter split payment amounts before processing this sale.'], 422);
        }
        if ($splitPaid < $totalAmount) {
            return response()->json(['success' => false, 'message' => 'POS split sales must be fully paid. Use Invoices for credit sales.'], 422);
        }
        $paidAmount = $splitPaid;
    } else {
        if ($paidAmount < $totalAmount) {
            return response()->json(['success' => false, 'message' => 'POS cash sales must be fully paid. Use Invoices for credit sales.'], 422);
        }
    }

    DB::beginTransaction();

    try {
        // --- 1. GENERATE REQUIRED NUMBERS ---
        $invoiceNo = $this->generateSaleInvoiceNo();
        $receiptNo = $this->generateSaleReceiptNo();
        $orderNumber = $this->generateSaleOrderNo();
        
        $totalAmount = (float) $request->total;
        $amountPaid = (float) $request->paid;
        $changeAmount = $amountPaid > $totalAmount ? $amountPaid - $totalAmount : 0;
        $actualPaymentKept = $amountPaid - $changeAmount;
        $balance = $totalAmount > $actualPaymentKept ? $totalAmount - $actualPaymentKept : 0;

        $paymentStatus = ($balance <= 0) ? 'paid' : (($actualPaymentKept > 0) ? 'partial' : 'unpaid');

        $selectedCustomer = $request->customer_id ? Customer::find($request->customer_id) : null;
        $resolvedCustomerName = $selectedCustomer?->customer_name
            ?? $selectedCustomer?->name
            ?? 'Walk-in Customer';

        $activeBranch = $this->getActiveBranchContext();
        $splitDetails = $this->normalizeSplitDetails($request->input('split_details', []));
        // Resolve deposit/collection accounts from Chart of Accounts
        $depositAccountId = (int) ($request->deposit_account_id ?? $request->payment_account_id ?? 0);
        $paymentAccount = $depositAccountId > 0
            ? Account::withoutGlobalScopes()->find($depositAccountId)
            : null;
        $cardSplitAccount = !empty($splitDetails['card_account_id'])
            ? Account::withoutGlobalScopes()->find((int) $splitDetails['card_account_id'])
            : null;
        $transferSplitAccount = !empty($splitDetails['transfer_account_id'])
            ? Account::withoutGlobalScopes()->find((int) $splitDetails['transfer_account_id'])
            : null;

        // --- 2. CREATE THE SALE RECORD ---
$sale = Sale::create([
    'company_id'     => auth()->user()?->company_id ?? session('current_tenant_id'),
    'branch_id'      => $activeBranch['id'],
    'branch_name'    => $activeBranch['name'],
    'order_number'   => $orderNumber,
    'invoice_no'     => $invoiceNo,
    'receipt_no'     => $receiptNo,
    'customer_id'    => $request->customer_id,
    'customer_name'  => $resolvedCustomerName,
    'user_id'        => auth()->id() ?? 1,
    'terminal_id'    => 'POS1',
    'subtotal'       => 0, 
    'discount'       => 0, 
    'tax'            => 0,
    'total'          => $totalAmount,
    'paid'           => $actualPaymentKept,
    'amount_paid'    => $actualPaymentKept,
    'change_amount'  => $changeAmount,
    'balance'        => $balance,
    'currency'       => 'NGN',
    'payment_method' => $paymentMethod,
    'payment_status' => $paymentStatus,
        'payment_details' => [
        'source' => 'pos',
        'cashier_id' => auth()->id(),
        'cashier_name' => auth()->user()?->name,
        'branch_id' => $activeBranch['id'],
        'branch_name' => $activeBranch['name'],
        'payment_account_id' => $paymentAccount?->id,
        'payment_account_name' => $paymentAccount?->name,
        'split' => $splitDetails,
    ],
]);

        $runningSubtotal = 0;
        $runningTax = 0;
        $runningDiscount = 0;

        // --- 3. PROCESS ITEMS ---
        foreach ($request->items as $itemData) {
            $product = Product::lockForUpdate()->find($itemData['id']);
            $availableStock = $this->branchInventory->getAvailableStock($product, $activeBranch);
            $qty = (float) $itemData['qty'];
            $requestedStockUnits = $this->resolveStockUnitsForSale($product, $itemData, $qty);

            if ($availableStock <= 0) {
                throw new \Exception("{$product->name} is out of stock and cannot be sold.");
            }

            if ($availableStock < $requestedStockUnits) {
                throw new \Exception("Insufficient stock for {$product->name}.");
            }

            $unitPrice   = (float) ($itemData['price'] ?? $product->price);
            $discountType = strtolower((string) ($itemData['discountType'] ?? $itemData['discount_type'] ?? 'percent'));
            $discountValue = (float) ($itemData['discountValue'] ?? $itemData['discount_value'] ?? ($itemData['discount'] ?? 0));
            $discPercent = (float) ($itemData['discount'] ?? 0);
            $taxPercent  = (float) ($itemData['tax'] ?? 0);

            $itemSubtotal   = $unitPrice * $qty;
            if ($discountType === 'fixed') {
                $itemDiscAmount = min($discountValue, $itemSubtotal);
                $discPercent = $itemSubtotal > 0 ? ($itemDiscAmount / $itemSubtotal) * 100 : 0;
            } else {
                $discPercent = (float) ($itemData['discount'] ?? $discountValue);
                $itemDiscAmount = $itemSubtotal * ($discPercent / 100);
            }
            $afterDisc      = $itemSubtotal - $itemDiscAmount;
            $itemTaxAmount  = $afterDisc * ($taxPercent / 100);
            $itemTotal      = $afterDisc + $itemTaxAmount;

            $itemPayload = [
                'sale_id'     => $sale->id,
                'product_id'  => $product->id,
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'discount'    => $discPercent,
                'tax'         => $taxPercent,
                'subtotal'    => $itemSubtotal,
                'total_price' => $itemTotal, 
            ];
            if (Schema::hasColumn('sale_items', 'unit_type')) {
                $itemPayload['unit_type'] = strtolower((string) ($itemData['unitType'] ?? $product->unit_type ?? 'unit'));
            }
            if (Schema::hasColumn('sale_items', 'stock_units')) {
                $itemPayload['stock_units'] = $requestedStockUnits;
            }
            if (Schema::hasColumn('sale_items', 'discount_type')) {
                $itemPayload['discount_type'] = $discountType === 'fixed' ? 'fixed' : 'percent';
            }
            if (Schema::hasColumn('sale_items', 'discount_value')) {
                $itemPayload['discount_value'] = $discountValue;
            }
            if (Schema::hasColumn('sale_items', 'company_id')) {
                $itemPayload['company_id'] = $sale->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id');
            }
            if (Schema::hasColumn('sale_items', 'branch_id')) {
                $itemPayload['branch_id'] = $sale->branch_id ?? $activeBranch['id'];
            }
            if (Schema::hasColumn('sale_items', 'branch_name')) {
                $itemPayload['branch_name'] = $sale->branch_name ?? $activeBranch['name'];
            }

            SaleItem::create($itemPayload);

            $runningSubtotal += $itemSubtotal;
            $runningDiscount += $itemDiscAmount;
            $runningTax      += $itemTaxAmount;

            $this->decrementSellableStock($product, $requestedStockUnits);
            $this->branchInventory->adjustBranchStock(
                $product,
                -$requestedStockUnits,
                $activeBranch,
                (int) ($product->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0)
            );
        }

        // --- 4. UPDATE TOTALS & LOG PAYMENT ---
        $calculatedTotal = max(0, ($runningSubtotal - $runningDiscount) + $runningTax);
        $finalChange = $amountPaid > $calculatedTotal ? $amountPaid - $calculatedTotal : 0;
        $finalPaid = max(0, $amountPaid - $finalChange);
        $finalBalance = max(0, $calculatedTotal - $finalPaid);
        $finalPaymentStatus = $finalBalance <= 0 ? 'paid' : ($finalPaid > 0 ? 'partial' : 'unpaid');

        $sale->update([
            'branch_id'       => $activeBranch['id'],
            'branch_name'     => $activeBranch['name'],
            'subtotal'       => $runningSubtotal,
            'discount'       => $runningDiscount,
            'tax'            => $runningTax,
            'total'          => $calculatedTotal,
            'paid'           => $finalPaid,
            'amount_paid'    => $finalPaid,
            'change_amount'  => $finalChange,
            'balance'        => $finalBalance,
            'payment_status' => $finalPaymentStatus,
            'payment_details' => [
                'source' => 'pos',
                'cashier_id' => auth()->id(),
                'cashier_name' => auth()->user()?->name,
                'branch_id' => $activeBranch['id'],
                'branch_name' => $activeBranch['name'],
                'split' => $splitDetails,
                'payment_account_id' => $paymentAccount?->id,
                'payment_account_name' => $paymentAccount?->name,
                'card_account_id' => $cardSplitAccount?->id,
                'card_account_name' => $cardSplitAccount?->name,
                'transfer_account_id' => $transferSplitAccount?->id,
                'transfer_account_name' => $transferSplitAccount?->name,
                'tendered' => round($amountPaid, 2),
                'applied' => round($finalPaid, 2),
                'change' => round($finalChange, 2),
            ],
        ]);

        if ($finalPaid > 0) {
            $paymentRecordStatus = $finalBalance <= 0 ? 'Completed' : 'Pending';
            $paymentPayload = [
                'sale_id' => $sale->id,
                'branch_id' => $activeBranch['id'],
                'branch_name' => $activeBranch['name'],
                'amount'  => $finalPaid,
                'method'  => $request->payment_method,
                'status'  => $paymentRecordStatus,
                'note'    => $finalBalance <= 0
                    ? ($paymentAccount?->name ? 'Initial POS Payment via ' . $paymentAccount->name : 'Initial POS Payment')
                    : ($paymentAccount?->name ? 'Deposit received via ' . $paymentAccount->name : 'Deposit received'),
                'receipt_no' => $this->generatePaymentReceiptNo(),
                'created_by' => auth()->id(),
            ];
            if (Schema::hasColumn('payments', 'payment_account_id')) {
                $paymentPayload['payment_account_id'] = $paymentAccount?->id;
            }
            Payment::create($paymentPayload);
        }

        // Use the explicitly selected deposit account for journal entries
        $primaryDepositAccount = $paymentAccount ?? $transferSplitAccount ?? $cardSplitAccount;
        LedgerService::postSale($sale->fresh(), $primaryDepositAccount?->id);

        // Broadcast Real-time event
        broadcast(new NewSaleRegistered($sale))->toOthers();

        DB::commit();

        $this->clearDashboardMetricsCache($activeBranch['id'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Sale processed successfully',
            'sale_id' => $sale->id
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    }
}

    public function showInvoice($id)
    {
        $sale = Sale::with(['items.product', 'customer'])->findOrFail($id);
        $company = Company::find($sale->company_id)
            ?? auth()->user()?->company
            ?? new Company();
        $currencySymbol = GeoCurrency::currentSymbol();
        $activeBranch = $this->getActiveBranchContext();

        return view('Sales.Invoices.index', compact('sale', 'company', 'currencySymbol', 'activeBranch'));
    }

    public function printInvoice($id)
    {
        $sale = Sale::with(['items.product', 'customer', 'user'])->findOrFail($id);

        return view('Sales.Invoices.print', [
            'sale' => $sale,
            'backUrl' => route('sales.invoice.show', $sale->id),
        ]);
    }

    private function resolveStockUnitsForSale(Product $product, array $itemData, float $qty): float
    {
        return InventoryQuantity::resolveSaleStockUnits(
            $product,
            $qty,
            $itemData['unitType'] ?? null,
            isset($itemData['stockUnits']) ? (float) $itemData['stockUnits'] : null
        );
    }

    private function generateInvoiceNo() {
        return $this->generateUniqueSaleReference('invoice_no', 'INV-' . strtoupper(Carbon::now()->format('ymd')) . '-');
    }

    private function generateReceiptNo() {
        return $this->generateUniqueSaleReference('receipt_no', 'REC-' . strtoupper(Carbon::now()->format('ymd')) . '-');
    }

    private function generateUniqueSaleReference(string $column, string $prefix): string
    {
        do {
            $candidate = $prefix . strtoupper(Str::random(6));
        } while (Sale::withTrashed()->where($column, $candidate)->exists());

        return $candidate;
    }

    public function convertNumberToWords($number) {
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $dictionary  = [
            0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen',
            20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
            100 => 'hundred', 1000 => 'thousand', 1000000 => 'million'
        ];

        if (!is_numeric($number)) return "";
        $number = (int) round($number);
        if ($number < 0) return $negative . $this->convertNumberToWords(abs($number));

        $string = $fraction = null;

        switch (true) {
            case $number < 21: $string = $dictionary[$number]; break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) $string .= $hyphen . $dictionary[$units];
                break;
            case $number < 1000:
                $hundreds  = (int)($number / 100);
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) $string .= $conjunction . $this->convertNumberToWords($remainder);
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertNumberToWords($remainder);
                }
                break;
        }
        
        $string = str_replace(" Naira Only", "", $string);
        return ucfirst(trim($string)) . " Naira Only";
    }

    public function returnToPos()
    {
        session()->forget(['cart', 'current_customer', 'applied_discount']);
        return redirect()->route('sales.showPos')->with('success', 'Cart cleared for new transaction.');
    }


public function create()
{
    $customers = $this->scopedCustomers()->get();
    $products = $this->scopedProducts()->get();

    return view('Sales.Invoices.create-invoices', compact('customers', 'products'));
}

    public function edit($id)
    {
        $sale = $this->findScopedSale($id, ['items.product']);
        abort_if(!$sale, 404);

        $customers = $this->scopedCustomers()->get();
        if ($sale->customer_id && !$customers->contains('id', $sale->customer_id)) {
            $currentCustomer = Customer::query()->find($sale->customer_id);
            if ($currentCustomer) {
                $customers = $customers->prepend($currentCustomer)->unique('id')->values();
            }
        }

        $products = $this->scopedProducts()->get();

        return view('Sales.edit', compact('sale', 'customers', 'products'));
    }

    public function update(Request $request, $id)
    {
        $sale = $this->findScopedSale($id);
        abort_if(!$sale, 404);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.rate' => 'required|numeric',
            'items.*.price_level' => 'nullable|in:retail,wholesale,special',
        ]);

        DB::transaction(function () use ($request, $sale) {
            $sale->update([
                'customer_id' => $request->customer_id,
                'reference_no' => trim((string) $request->reference_no) !== ''
                    ? trim((string) $request->reference_no)
                    : ('REF-' . now()->format('ymd') . '-' . strtoupper(Str::random(4))),
                'total' => $request->final_total,
            ]);

            $sale->items()->delete();

            foreach ($request->items as $item) {
                $qty = $item['quantity'];
                $rate = $item['rate'];
                $discPercent = $item['discount'] ?? 0;
                
                $subtotal = $qty * $rate;
                $totalPrice = $subtotal - ($subtotal * ($discPercent / 100));

                $sale->items()->create([
                    'product_id'  => $item['product_id'],
                    'qty'         => $qty,
                    'unit_price'  => $rate,
                    'discount'    => $discPercent,
                    'subtotal'    => $subtotal,
                    'total_price' => $totalPrice,
                ]);
            }

            LedgerService::postSale($sale->fresh());
        });

        return redirect()->route('sales.index')->with('success', "Invoice #{$sale->invoice_no} updated.");
    }

    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id');
        $branchName = session('active_branch_name');

        if (!$branchId && !$branchName && Schema::hasTable('settings')) {
            $companyId = $this->tenantCompanyId();
            if ($companyId > 0) {
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
            'id' => $branchId !== null ? (string) $branchId : null,
            'name' => $branchName ? (string) $branchName : null,
        ];
    }

    private function normalizeSplitDetails(mixed $splitDetails): array
    {
        if (!is_array($splitDetails)) {
            return [];
        }

        return [
            'cash' => (float) ($splitDetails['cash'] ?? 0),
            'card' => (float) ($splitDetails['pos'] ?? $splitDetails['card'] ?? 0),
            'transfer' => (float) ($splitDetails['bank'] ?? $splitDetails['transfer'] ?? 0),
            'card_account_id' => !empty($splitDetails['card_account_id']) ? (int) $splitDetails['card_account_id'] : null,
            'transfer_account_id' => !empty($splitDetails['transfer_account_id']) ? (int) $splitDetails['transfer_account_id'] : null,
        ];
    }
}
