<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Bank;
use App\Events\NewSaleRegistered; // The Pusher event we created
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Support\BranchInventoryService;
use App\Support\LedgerService;

class SaleController extends Controller
{
    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

    private function tenantCompanyId(): int
    {
        return (int) (auth()->user()?->company_id ?? optional(auth()->user()?->company)->id ?? 0);
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

    public function index(Request $request)
    {
        // 1. Start the query with relationships
        $query = Sale::with(['customer', 'user']);
        $activeBranch = $this->getActiveBranchContext();
        $this->applyTenantScope($query, 'sales');

        // 2. Apply Filters
        if ($request->invoice_no) {
            $query->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }
        if ($request->customer_name) {
            $query->whereHas('customer', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer_name . '%');
            });
        }
        if ($request->sale_date) {
            $query->whereDate('created_at', $request->sale_date);
        }

        // 3. Calculate Stats before pagination
        $totalRevenue = $query->sum('total');
        $totalSalesCount = $query->count();

        // 4. Paginate
        $sales = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('sales.index', compact('sales', 'totalRevenue', 'totalSalesCount', 'activeBranch'));
    }

    public function report(Request $request)
    {
        $stockExpression = Schema::hasColumn('products', 'stock')
            ? 'COALESCE(products.stock, 0)'
            : (Schema::hasColumn('products', 'stock_quantity') ? 'COALESCE(products.stock_quantity, 0)' : '0');
        $priceExpression = Schema::hasColumn('products', 'product_price')
            ? 'COALESCE(products.product_price, 0)'
            : (Schema::hasColumn('products', 'price') ? 'COALESCE(products.price, 0)' : '0');

        $query = Product::query()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw('
                products.id,
                products.name as product_name,
                products.sku,
                categories.name as category_name,
                ' . $priceExpression . ' as product_price,
                ' . $stockExpression . ' as instock_qty,
                COALESCE(SUM(sale_items.qty), 0) as total_sold_qty,
                COALESCE(SUM(
                    CASE
                        WHEN sale_items.total_price IS NOT NULL THEN sale_items.total_price
                        WHEN sale_items.subtotal IS NOT NULL THEN sale_items.subtotal
                        ELSE COALESCE(sale_items.qty, 0) * COALESCE(sale_items.unit_price, 0)
                    END
                ), 0) as total_sold_amount
            ')
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'categories.name',
                DB::raw($priceExpression),
                DB::raw($stockExpression)
            );
        $this->applyTenantScope($query, 'products');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('categories.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->integer('category_id'));
        }

        if ($request->filled('date_from')) {
            $query->where(function ($builder) use ($request) {
                $builder->whereNull('sales.created_at')
                    ->orWhereDate('sales.created_at', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->where(function ($builder) use ($request) {
                $builder->whereNull('sales.created_at')
                    ->orWhereDate('sales.created_at', '<=', $request->date_to);
            });
        }

        $reports = $query
            ->orderByDesc('total_sold_amount')
            ->orderBy('products.name')
            ->paginate(15)
            ->withQueryString();

        return view('pos.reports', compact('reports'));
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
        $productsQuery = Product::with('category')
            ->orderBy('name', 'asc');
        $this->applyTenantScope($productsQuery, 'products');

        if (Schema::hasTable('product_branch_stocks') && !empty($activeBranch['id'])) {
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
            ->filter(fn ($product) => (float) ($product->available_stock ?? 0) > 0)
            ->values();
        $customers = Customer::query()
            ->orderBy('customer_name', 'asc')
            ->tap(fn ($query) => $this->applyTenantScope($query, 'customers'))
            ->get();
        $sales = Sale::with('customer')
            ->tap(fn ($query) => $this->applyTenantScope($query, 'sales'))
            ->latest()
            ->take(10)
            ->get();
        $bankAccounts = Schema::hasTable('banks')
            ? Bank::query()->orderBy('name')->get()
            : collect();

        return view('pos.index', compact('products', 'customers', 'sales', 'activeBranch', 'bankAccounts'));
    }

    public function showSale($id)
    {
        $sale = Sale::with(['customer', 'items.product', 'user'])
            ->tap(fn ($query) => $this->applyTenantScope($query, 'sales'))
            ->findOrFail($id);
        $activeBranch = $this->getActiveBranchContext();
        $bankAccounts = Schema::hasTable('banks')
            ? Bank::query()->orderBy('name')->get()
            : collect();

        return view('sales.show', compact('sale', 'activeBranch', 'bankAccounts'));
    }

    public function store(Request $request)
{
    $request->validate([
        'customer_id'    => 'nullable|exists:customers,id',
        'payment_method' => 'required|string',
        'total'          => 'required|numeric|min:0',
        'paid'           => 'required|numeric|min:0',
        'items'          => 'required|array|min:1',
        'items.*.id'     => 'required|exists:products,id',
        'items.*.qty'    => 'required|numeric|gt:0',
        'items.*.unitType' => 'nullable|in:unit,roll,carton',
        'items.*.stockUnits' => 'nullable|numeric|gt:0',
        'items.*.priceLevel' => 'nullable|in:retail,wholesale,special',
        'payment_account_id' => 'nullable|exists:banks,id',
        'split_details.card_account_id' => 'nullable|exists:banks,id',
        'split_details.transfer_account_id' => 'nullable|exists:banks,id',
    ]);

    DB::beginTransaction();

    try {
        // --- 1. GENERATE REQUIRED NUMBERS ---
        $invoiceNo = $this->generateInvoiceNo();
        $receiptNo = $this->generateReceiptNo();
        // FIXED: Generating order_number to satisfy your DB constraint
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6)); 
        
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
        $paymentAccount = $request->filled('payment_account_id') && Schema::hasTable('banks')
            ? Bank::query()->find($request->input('payment_account_id'))
            : null;
        $cardSplitAccount = !empty($splitDetails['card_account_id']) && Schema::hasTable('banks')
            ? Bank::query()->find($splitDetails['card_account_id'])
            : null;
        $transferSplitAccount = !empty($splitDetails['transfer_account_id']) && Schema::hasTable('banks')
            ? Bank::query()->find($splitDetails['transfer_account_id'])
            : null;

        // --- 2. CREATE THE SALE RECORD ---
$sale = Sale::create([
    'company_id'     => auth()->user()?->company_id,
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
    'payment_method' => $request->payment_method,
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

            if ($availableStock < $requestedStockUnits) {
                throw new \Exception("Insufficient stock for {$product->name}.");
            }

            $unitPrice   = (float) ($itemData['price'] ?? $product->price);
            $discPercent = (float) ($itemData['discount'] ?? 0);
            $taxPercent  = (float) ($itemData['tax'] ?? 0);

            $itemSubtotal   = $unitPrice * $qty;
            $itemDiscAmount = $itemSubtotal * ($discPercent / 100);
            $afterDisc      = $itemSubtotal - $itemDiscAmount;
            $itemTaxAmount  = $afterDisc * ($taxPercent / 100);
            $itemTotal      = $afterDisc + $itemTaxAmount;

            SaleItem::create([
                'sale_id'     => $sale->id,
                'product_id'  => $product->id,
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'discount'    => $discPercent,
                'tax'         => $taxPercent,
                'subtotal'    => $itemSubtotal,
                'total_price' => $itemTotal, 
            ]);

            $runningSubtotal += $itemSubtotal;
            $runningDiscount += $itemDiscAmount;
            $runningTax      += $itemTaxAmount;

            $product->decrement('stock', $requestedStockUnits);
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $product->decrement('stock_quantity', $requestedStockUnits);
            }
            $this->branchInventory->adjustBranchStock(
                $product,
                -$requestedStockUnits,
                $activeBranch,
                (int) ($product->company_id ?? auth()->user()?->company_id ?? 0)
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
                'created_by' => auth()->id(),
            ];
            if (Schema::hasColumn('payments', 'payment_account_id')) {
                $paymentPayload['payment_account_id'] = $paymentAccount?->id;
            }
            Payment::create($paymentPayload);
        }

        LedgerService::postSale($sale->fresh());

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
        $currencySymbol = '₦'; 
        $activeBranch = $this->getActiveBranchContext();

        return view('Sales.Invoices.index', compact('sale', 'company', 'currencySymbol', 'activeBranch'));
    }

    private function resolveStockUnitsForSale(Product $product, array $itemData, float $qty): float
    {
        $type = strtolower((string) ($itemData['unitType'] ?? 'unit'));
        $rollsPerCarton = max((int) ($product->units_per_carton ?? 0), 0);
        $unitsPerRoll = max((int) ($product->units_per_roll ?? 0), 0);

        $multiplier = match ($type) {
            'carton' => ($rollsPerCarton > 0 && $unitsPerRoll > 0) ? ($rollsPerCarton * $unitsPerRoll) : 1,
            'roll' => $unitsPerRoll > 0 ? $unitsPerRoll : 1,
            default => 1,
        };

        $stockUnits = (float) ($itemData['stockUnits'] ?? 0);
        if ($stockUnits > 0) {
            return $stockUnits;
        }

        return max($qty * $multiplier, $qty);
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
    $customers = Customer::all();
    $products = Product::orderBy('name', 'asc')->get();

    return view('Sales.Invoices.create-invoices', compact('customers', 'products'));
}

    public function edit($id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);
     $customers = Customer::orderBy('customer_name', 'asc')->get();
        $products = Product::orderBy('name', 'asc')->get();

        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

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
                'reference_no' => $request->reference_no,
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
