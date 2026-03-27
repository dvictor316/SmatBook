<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Support\BranchInventoryService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    private function companyScopedSettingKey(string $baseKey): string
    {
        $companyId = (int) (auth()->user()?->company_id ?? optional(auth()->user()?->company)->id ?? 0);

        return $companyId > 0 ? "{$baseKey}_company_{$companyId}" : $baseKey;
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

        $resolvedBranchId = $branchId ?: ($this->getActiveBranchContext()['id'] ?? null);
        if ($resolvedBranchId) {
            Cache::forget('metrics_co_' . $companyId . '_branch_' . $resolvedBranchId);
        }
    }

    private function sanitizeForProductColumns(array $data): array
    {
        $allowed = array_flip(Schema::getColumnListing('products'));

        return array_intersect_key($data, $allowed);
    }

    private function firstOrCreateImportCategory(string $categoryName): Category
    {
        $existing = Category::query()->where('name', $categoryName)->first();
        if ($existing) {
            return $existing;
        }

        $payload = ['name' => $categoryName];

        if (Schema::hasColumn('categories', 'description')) {
            $payload['description'] = 'Auto-created during product import';
        }

        if (Schema::hasColumn('categories', 'status')) {
            $payload['status'] = 1;
        }

        return Category::query()->create($payload);
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

    private function resolveBranchContext(?string $branchId = null): array
    {
        $branches = collect($this->getAvailableBranches());

        if ($branchId) {
            $branch = $branches->firstWhere('id', $branchId);
            if ($branch) {
                return [
                    'id' => (string) $branch['id'],
                    'name' => (string) ($branch['name'] ?? ''),
                ];
            }
        }

        return $this->getActiveBranchContext();
    }

    private function currentPlanName(): string
    {
        $currentPlan = strtolower((string) session('user_plan'));

        if ($currentPlan === '' && auth()->check() && Schema::hasTable('subscriptions')) {
            $subscription = Subscription::resolveCurrentForUser(auth()->user())
                ?? Subscription::where('user_id', auth()->id())->latest()->first();

            $currentPlan = strtolower((string) ($subscription?->plan ?? $subscription?->plan_name ?? ''));
        }

        return $currentPlan;
    }

    private function planSupportsStockTransfer(): bool
    {
        $plan = $this->currentPlanName();

        return $plan === '' || !str_contains($plan, 'basic');
    }

    private function calculateStockFromPackaging(array $validated): int
    {
        $unitsPerCarton = max((int) ($validated['units_per_carton'] ?? 0), 0);
        $unitsPerRoll = max((int) ($validated['units_per_roll'] ?? 0), 0);
        $stockCartons = (float) ($validated['stock_cartons'] ?? 0);
        $stockRolls = (float) ($validated['stock_rolls'] ?? 0);
        $stockUnits = (float) ($validated['stock_units'] ?? 0);

        $cartonUnits = $unitsPerRoll > 0
            ? ($stockCartons * $unitsPerCarton * $unitsPerRoll)
            : ($stockCartons * $unitsPerCarton);

        $rollUnits = $unitsPerRoll > 0
            ? ($stockRolls * $unitsPerRoll)
            : $stockRolls;

        return (int) round($cartonUnits + $rollUnits + $stockUnits);
    }

    private function spreadsheetRowIterator(UploadedFile $file): \Generator
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return;
            }

            try {
                $delimiter = $this->detectCsvDelimiter($handle);
                while (($line = fgets($handle)) !== false) {
                    $line = $this->normalizeCsvLine($line);
                    if ($line === '') {
                        continue;
                    }

                    $row = str_getcsv($line, $delimiter);
                    if ($row === [null] || $row === false) {
                        continue;
                    }

                    yield $row;
                }
            } finally {
                fclose($handle);
            }

            return;
        }

        $reader = IOFactory::createReaderForFile($file->getRealPath());
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $spreadsheet = $reader->load($file->getRealPath());

        try {
            $sheet = $spreadsheet->getActiveSheet();
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $cells[] = $cell?->getFormattedValue();
                }

                yield $cells;
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function detectCsvDelimiter($handle): string
    {
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            rewind($handle);
            return ',';
        }

        $firstLine = $this->normalizeCsvLine($firstLine);

        $candidates = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestScore = -1;

        foreach ($candidates as $candidate) {
            $score = substr_count($firstLine, $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $candidate;
            }
        }

        rewind($handle);

        return $bestDelimiter;
    }

    private function normalizeCsvLine(string $line): string
    {
        if (str_starts_with($line, "\xFF\xFE")) {
            $line = mb_convert_encoding(substr($line, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($line, "\xFE\xFF")) {
            $line = mb_convert_encoding(substr($line, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_contains($line, "\x00")) {
            $line = mb_convert_encoding($line, 'UTF-8', 'UTF-16LE');
        }

        $line = preg_replace('/^\x{FEFF}/u', '', $line) ?? $line;

        return trim($line);
    }

    private function normalizeImportHeaderCell($value): string
    {
        $header = strtolower(trim((string) $value));
        return preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;
    }

    /**
     * Product List View with Search and Database Falling Check
     */
    public function index(Request $request)
    {
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            Log::error("DB Connection Failure on " . env('SESSION_DOMAIN') . ": " . $e->getMessage());
            return response()->view('errors.db_error', [
                'message' => "Could not connect to database on " . env('SESSION_DOMAIN', 'localhost') . ". Check your .env file."
            ], 500);
        }

        if (!Schema::hasTable('products')) {
            return view('Inventory.Products.index', [
                'products' => collect(),
                'categories' => collect(),
                'availableBranches' => $this->getAvailableBranches(),
                'search' => trim((string) $request->input('search', '')),
                'session_domain' => env('SESSION_DOMAIN', null)
            ]);
        }

        $search = $request->input('search');
        $activeBranch = $this->getActiveBranchContext();
        $query = Product::query();
        $this->applyTenantScope($query, 'products');

        if (Schema::hasTable('categories') && Schema::hasColumn('products', 'category_id')) {
            $query->with('category');
        }

        if (Schema::hasTable('product_branch_stocks') && !empty($activeBranch['id'])) {
            $query->with(['branchStocks' => function ($branchQuery) use ($activeBranch) {
                $branchQuery->where('branch_id', $activeBranch['id']);
            }]);
        }

        $orderColumn = Schema::hasColumn('products', 'created_at') ? 'created_at' : 'id';
        $query->orderByDesc($orderColumn);

        if ($search) {
            $hasNameColumn = Schema::hasColumn('products', 'name');
            $hasSkuColumn = Schema::hasColumn('products', 'sku');

            if ($hasNameColumn || $hasSkuColumn) {
                $query->where(function($q) use ($search, $hasNameColumn, $hasSkuColumn) {
                    if ($hasNameColumn) {
                        $q->where('name', 'like', "%{$search}%");
                    }

                    if ($hasSkuColumn) {
                        $method = $hasNameColumn ? 'orWhere' : 'where';
                        $q->{$method}('sku', 'like', "%{$search}%");
                    }
                });
            }
        }

        $products = $query->paginate(15)->withQueryString();
        $products->getCollection()->transform(function ($product) use ($activeBranch) {
            $product->setAttribute('active_branch_stock', $this->branchInventory->getAvailableStock($product, $activeBranch));
            return $product;
        });
        $categories = Schema::hasTable('categories')
            ? Category::orderBy(Schema::hasColumn('categories', 'name') ? 'name' : 'id')->get()
            : collect();

        return view('Inventory.Products.index', [
            'products' => $products,
            'categories' => $categories,
            'availableBranches' => $this->getAvailableBranches(),
            'stockTransferEnabled' => $this->planSupportsStockTransfer(),
            'search' => $search,
            'activeBranch' => $activeBranch,
            'session_domain' => env('SESSION_DOMAIN', null)
        ]);
    }

    /**
     * Unit Definition Logic
     */
    public function units()
    {
        $units = [
            (object)['name' => 'Unit', 'short_name' => 'unit'],
            (object)['name' => 'Roll', 'short_name' => 'rl'],
            (object)['name' => 'Carton', 'short_name' => 'ctn'],
        ];

        $products = collect();
        return view('Inventory.Products.units', compact('products', 'units'));
    }

    /**
     * Create Product View
     */
    public function create()
    {
        $categories = Schema::hasTable('categories')
            ? Category::orderBy('name')->get()
            : collect();
        $availableBranches = $this->getAvailableBranches();
        return view('Inventory.Products.add-products', compact('categories', 'availableBranches'));
    }

    /**
     * Store New Product
     */
    public function store(Request $request)
    {
        try {
            $rules = [
                'name'             => 'required|string|max:191',
                'sku'              => 'nullable|string|max:191|unique:products,sku',
                'price'            => 'required|numeric|min:0', 
                'retail_price'     => 'nullable|numeric|min:0',
                'wholesale_price'  => 'nullable|numeric|min:0',
                'special_price'    => 'nullable|numeric|min:0',
                'purchase_price'   => 'required|numeric|min:0', 
                'stock'            => 'nullable|integer|min:0', 
                'stock_cartons'    => 'nullable|numeric|min:0',
                'stock_rolls'      => 'nullable|numeric|min:0',
                'stock_units'      => 'nullable|numeric|min:0',
                'units_per_carton' => 'nullable|integer|min:0',
                'units_per_roll'   => 'nullable|integer|min:0',
                'base_unit_name'   => 'required|string|max:100',
                'category_id'      => 'required|exists:categories,id',
                'unit_type'        => 'required|in:unit,sachet,roll,carton',
                'branch_id'        => 'nullable|string',
                'description'      => 'nullable|string',
                'barcode'          => 'nullable|string|max:191',
            ];

            $validated = Validator::make($request->except('image'), $rules)->validate();

            $uploadedImage = $request->file('image');

            $validated['units_per_carton'] = (int) ($validated['units_per_carton'] ?? 0);
            $validated['units_per_roll'] = (int) ($validated['units_per_roll'] ?? 0);
            $validated['stock_cartons'] = (float) ($validated['stock_cartons'] ?? 0);
            $validated['stock_rolls'] = (float) ($validated['stock_rolls'] ?? 0);
            $validated['stock_units'] = (float) ($validated['stock_units'] ?? 0);
            $retailPrice = (float) ($validated['retail_price'] ?? $validated['price']);
            $validated['price'] = $retailPrice;
            if (Schema::hasColumn('products', 'retail_price')) {
                $validated['retail_price'] = $retailPrice;
            } else {
                unset($validated['retail_price']);
            }
            if (Schema::hasColumn('products', 'wholesale_price')) {
                $validated['wholesale_price'] = isset($validated['wholesale_price']) && $validated['wholesale_price'] !== null && $validated['wholesale_price'] !== ''
                    ? (float) $validated['wholesale_price']
                    : null;
            } else {
                unset($validated['wholesale_price']);
            }
            if (Schema::hasColumn('products', 'special_price')) {
                $validated['special_price'] = isset($validated['special_price']) && $validated['special_price'] !== null && $validated['special_price'] !== ''
                    ? (float) $validated['special_price']
                    : null;
            } else {
                unset($validated['special_price']);
            }
            $validated['sku'] = $this->generateUniqueSku($validated['sku'] ?? null, $validated['name']);

            $calculatedStock = $validated['stock'] ?? null;

            if ($calculatedStock === null) {
                $calculatedStock = $this->calculateStockFromPackaging($validated);
            }
            $validated['stock'] = (int) ($calculatedStock ?? 0);

            if ($validated['unit_type'] === 'carton' && $validated['units_per_carton'] < 1) {
                return back()->withErrors([
                    'units_per_carton' => 'Enter the number of rolls per carton or the number of loose pieces per carton before saving this carton product.'
                ])->withInput();
            }
            if ($validated['unit_type'] === 'roll' && $validated['units_per_roll'] < 1) {
                return back()->withErrors([
                    'units_per_roll' => 'Enter the number of sachets or loose pieces inside one roll before saving this roll product.'
                ])->withInput();
            }
            if ($validated['unit_type'] === 'sachet' && $validated['stock_units'] < 1 && $validated['stock_rolls'] < 1 && $validated['stock_cartons'] < 1) {
                return back()->withErrors([
                    'stock_units' => 'Enter opening stock for sachets, rolls, or cartons before saving this sachet product.'
                ])->withInput();
            }

            if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
                $validated['image'] = $uploadedImage->store('products', 'public');
            }

            $resolvedCompanyId = auth()->user()?->company_id;
            $selectedBranch = $this->resolveBranchContext($validated['branch_id'] ?? null);

            $validated['status'] = 'active';
            $validated['stock_quantity'] = $validated['stock'];
            $validated['user_id'] = auth()->id();
            $validated['company_id'] = $resolvedCompanyId ?: null;
            unset($validated['branch_id']);
            $product = Product::create($validated);
            $this->branchInventory->seedOpeningStock(
                $product,
                (float) $validated['stock'],
                $selectedBranch,
                $product->company_id ?: ($resolvedCompanyId ?: null)
            );
            $this->clearDashboardMetricsCache($selectedBranch['id'] ?? null);

            return redirect()->route('product-list')
                ->with('success', 'Product added successfully.');
        } catch (\Throwable $e) {
            \Log::error('Product store failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'payload' => $request->except(['image']),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Product could not be added. Please check the highlighted fields and try again.');
        }
    }

    /**
     * THE FIX: Added Missing Edit Method
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        
        return view('Inventory.Products.edit', compact('product', 'categories'));
    }

public function inventory(Request $request)
{
    $fromDate = $request->input('from_date') ?: now()->startOfMonth()->toDateString();
    $toDate = $request->input('to_date') ?: now()->toDateString();
    $productId = $request->input('product_id');
    $fromStart = \Carbon\Carbon::parse($fromDate)->startOfDay()->toDateTimeString();
    $toEnd = \Carbon\Carbon::parse($toDate)->endOfDay()->toDateTimeString();

    $user = auth()->user();
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

    $products = Product::query()
        ->orderBy('name', 'asc')
        ->tap(fn ($q) => $applyTenantScope($q, 'products'))
        ->get(['id', 'name']);

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

    if (!(clone $stockIn)->exists()) {
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

    if (!(clone $stockOut)->exists()) {
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

    $rows = DB::table(DB::query()->fromSub($stockIn->unionAll($stockOut), 'stk'))
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

    $stockreports = $rows->map(fn($item) => [
        'Date' => \Carbon\Carbon::parse($item->log_date)->format('d M y'),
        'QtyIn' => (float) $item->total_qty_in,
        'QtyOut' => (float) $item->total_qty_out,
        'ValIn' => (float) $item->total_val_in,
        'ValOut' => (float) $item->total_val_out,
        'NetValue' => (float) $item->total_val_in - (float) $item->total_val_out,
    ]);

    return view('Reports.Reports.stock-report', compact('products', 'stockreports', 'fromDate', 'toDate', 'productId'));
}
    /**
     * Update Product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'required|string|max:191',
            'sku'              => 'nullable|string|max:191|unique:products,sku,' . $id,
            'price'            => 'required|numeric|min:0',
            'retail_price'     => 'nullable|numeric|min:0',
            'wholesale_price'  => 'nullable|numeric|min:0',
            'special_price'    => 'nullable|numeric|min:0',
            'purchase_price'   => 'required|numeric|min:0',
            'stock'            => 'nullable|integer|min:0',
            'stock_cartons'    => 'nullable|numeric|min:0',
            'stock_rolls'      => 'nullable|numeric|min:0',
            'stock_units'      => 'nullable|numeric|min:0',
            'units_per_carton' => 'nullable|integer|min:0',
            'units_per_roll'   => 'nullable|integer|min:0',
            'base_unit_name'   => 'required|string|max:100',
            'category_id'      => 'required|exists:categories,id',
            'unit_type'        => 'required|in:unit,sachet,roll,carton',
            'status'           => 'required|in:active,inactive',
            'description'      => 'nullable|string',
            'barcode'          => 'nullable|string|max:191',
        ]);

        $validated['units_per_carton'] = (int) ($validated['units_per_carton'] ?? 0);
        $validated['units_per_roll'] = (int) ($validated['units_per_roll'] ?? 0);
        $validated['stock_cartons'] = (float) ($validated['stock_cartons'] ?? 0);
        $validated['stock_rolls'] = (float) ($validated['stock_rolls'] ?? 0);
        $validated['stock_units'] = (float) ($validated['stock_units'] ?? 0);
        $retailPrice = (float) ($validated['retail_price'] ?? $validated['price']);
        $validated['price'] = $retailPrice;
        if (Schema::hasColumn('products', 'retail_price')) {
            $validated['retail_price'] = $retailPrice;
        } else {
            unset($validated['retail_price']);
        }
        if (Schema::hasColumn('products', 'wholesale_price')) {
            $validated['wholesale_price'] = isset($validated['wholesale_price']) && $validated['wholesale_price'] !== null && $validated['wholesale_price'] !== ''
                ? (float) $validated['wholesale_price']
                : null;
        } else {
            unset($validated['wholesale_price']);
        }
        if (Schema::hasColumn('products', 'special_price')) {
            $validated['special_price'] = isset($validated['special_price']) && $validated['special_price'] !== null && $validated['special_price'] !== ''
                ? (float) $validated['special_price']
                : null;
        } else {
            unset($validated['special_price']);
        }
        $validated['sku'] = $this->generateUniqueSku($validated['sku'] ?? null, $validated['name'], $product->id);

        $calculatedStock = $validated['stock'] ?? null;
        if ($calculatedStock === null) {
            $calculatedStock = $this->calculateStockFromPackaging($validated);
        }
        $validated['stock'] = (int) ($calculatedStock ?? (int) $product->stock);

        if ($validated['unit_type'] === 'carton' && $validated['units_per_carton'] < 1) {
            return back()->withErrors([
                'units_per_carton' => 'Enter the number of rolls per carton or the number of loose pieces per carton before saving this carton product.'
            ])->withInput();
        }
        if ($validated['unit_type'] === 'roll' && $validated['units_per_roll'] < 1) {
            return back()->withErrors([
                'units_per_roll' => 'Enter the number of sachets or loose pieces inside one roll before saving this roll product.'
            ])->withInput();
        }
        if ($validated['unit_type'] === 'sachet' && $validated['stock_units'] < 1 && $validated['stock_rolls'] < 1 && $validated['stock_cartons'] < 1) {
            return back()->withErrors([
                'stock_units' => 'Enter opening stock for sachets, rolls, or cartons before saving this sachet product.'
            ])->withInput();
        }

        if ($request->hasFile('image') && $request->file('image') instanceof UploadedFile && $request->file('image')->isValid()) {
            if ($product->image) { 
                Storage::disk('public')->delete($product->image); 
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['stock_quantity'] = $validated['stock']; 
        $product->update($validated);
        $this->clearDashboardMetricsCache();

        return redirect()->route('product-list')->with('success', 'Update pushed to ' . env('SESSION_DOMAIN'));
    }

    /**
     * Atomic Stock Adjustment (Fixed reference column error)
     */
    public function adjust_stock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:0.01',
            'type'       => 'required|in:in,out',
        ]);

        DB::transaction(function () use ($request) {
            $operator = ($request->type == 'in') ? '+' : '-';
            $activeBranch = $this->getActiveBranchContext();
            $product = Product::query()->lockForUpdate()->findOrFail($request->product_id);
            $availableBranchStock = $this->branchInventory->getAvailableStock($product, $activeBranch);

            if ($request->type === 'out' && $availableBranchStock < (float) $request->quantity) {
                throw new \RuntimeException("Insufficient stock for {$product->name} in {$activeBranch['name']}.");
            }
            
            DB::table('products')->where('id', $request->product_id)->update([
                'stock' => DB::raw("stock $operator " . $request->quantity),
                'stock_quantity' => DB::raw("stock_quantity $operator " . $request->quantity),
                'updated_at' => now()
            ]);

            $this->branchInventory->adjustBranchStock(
                $product,
                $request->type === 'in' ? (float) $request->quantity : -1 * (float) $request->quantity,
                $activeBranch,
                (int) ($product->company_id ?? auth()->user()?->company_id ?? 0)
            );

            $payload = [
                'product_id' => $request->product_id,
                'branch_id'  => $activeBranch['id'],
                'branch_name'=> $activeBranch['name'],
                'quantity'   => $request->quantity,
                'type'       => $request->type,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('inventory_history', 'user_id')) {
                $payload['user_id'] = auth()->id() ?? (int) DB::table('users')->min('id');
            }
            if (Schema::hasColumn('inventory_history', 'reference')) {
                $activeBranch = $this->getActiveBranchContext();
                $payload['reference'] = $activeBranch['name']
                    ? 'Manual Adjustment - ' . $activeBranch['name']
                    : 'Manual Adjustment';
            }

            $historyId = DB::table('inventory_history')->insertGetId($payload);

            // Raw DB stock update bypasses Eloquent observer; mirror stock-in to purchases here.
            if ($request->type === 'in') {
                if ($product && Schema::hasTable('purchases') && Schema::hasTable('purchase_items')) {
                    $purchaseNo = 'AUTO-STK-' . $historyId;
                    if (!Purchase::where('purchase_no', $purchaseNo)->exists()) {
                        $unitPrice = (float) ($product->purchase_price ?? $product->price ?? 0);
                        $amount = round(((float) $request->quantity) * $unitPrice, 2);

                        $purchase = Purchase::create([
                            'branch_id' => $activeBranch['id'],
                            'branch_name' => $activeBranch['name'],
                            'purchase_no' => $purchaseNo,
                            'supplier_id' => null,
                            'total_amount' => $amount,
                            'tax_amount' => 0,
                            'status' => 'received',
                        ]);

                        PurchaseItem::create([
                            'purchase_id' => $purchase->id,
                            'product_id' => $product->id,
                            'qty' => (float) $request->quantity,
                            'unit_price' => $unitPrice,
                        ]);
                    }
                }
            }
        });

        $this->clearDashboardMetricsCache();

        return redirect()->back()->with('success', 'Inventory state updated on ' . env('SESSION_DOMAIN'));
    }

    public function transferStock(Request $request)
    {
        if (!$this->planSupportsStockTransfer()) {
            return redirect()->back()->with('error', 'Stock transfer is available on higher plans only.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_branch_id' => 'required|string',
            'to_branch_id' => 'required|string|different:from_branch_id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $branches = collect($this->getAvailableBranches());
        $fromBranch = $branches->firstWhere('id', $validated['from_branch_id']);
        $toBranch = $branches->firstWhere('id', $validated['to_branch_id']);

        if (!$fromBranch || !$toBranch) {
            return redirect()->back()->with('error', 'Select valid source and destination branches.');
        }

        try {
            DB::transaction(function () use ($validated, $fromBranch, $toBranch) {
                $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
                $quantity = (float) $validated['quantity'];
                $sourceContext = ['id' => (string) $fromBranch['id'], 'name' => (string) ($fromBranch['name'] ?? '')];
                $destinationContext = ['id' => (string) $toBranch['id'], 'name' => (string) ($toBranch['name'] ?? '')];

                if ($this->branchInventory->getAvailableStock($product, $sourceContext) < $quantity) {
                    throw new \RuntimeException("Insufficient stock in {$sourceContext['name']} for this transfer.");
                }

                $companyId = (int) ($product->company_id ?? auth()->user()?->company_id ?? 0);
                $this->branchInventory->adjustBranchStock($product, -1 * $quantity, $sourceContext, $companyId);
                $this->branchInventory->adjustBranchStock($product, $quantity, $destinationContext, $companyId);

                if (Schema::hasTable('inventory_history')) {
                    $basePayload = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('inventory_history', 'user_id')) {
                        $basePayload['user_id'] = auth()->id() ?? (int) DB::table('users')->min('id');
                    }

                    $sourcePayload = $basePayload + ['type' => 'out'];
                    $destinationPayload = $basePayload + ['type' => 'in'];

                    if (Schema::hasColumn('inventory_history', 'branch_id')) {
                        $sourcePayload['branch_id'] = $sourceContext['id'];
                        $destinationPayload['branch_id'] = $destinationContext['id'];
                    }
                    if (Schema::hasColumn('inventory_history', 'branch_name')) {
                        $sourcePayload['branch_name'] = $sourceContext['name'];
                        $destinationPayload['branch_name'] = $destinationContext['name'];
                    }

                    if (Schema::hasColumn('inventory_history', 'reference')) {
                        $sourcePayload['reference'] = 'Branch Transfer to ' . $destinationContext['name'];
                        $destinationPayload['reference'] = 'Branch Transfer from ' . $sourceContext['name'];
                    }

                    DB::table('inventory_history')->insert($sourcePayload);
                    DB::table('inventory_history')->insert($destinationPayload);
                }
            });
        } catch (\Throwable $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        $this->clearDashboardMetricsCache($validated['from_branch_id'] ?? null);
        $this->clearDashboardMetricsCache($validated['to_branch_id'] ?? null);

        return redirect()->back()->with('success', 'Stock transferred successfully between branches.');
    }

    /**
     * Breakdown Logic (Carton to Units)
     */
    public function breakdown(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $cartons_to_break = $request->input('carton_qty');
        $total_units = $cartons_to_break * $product->units_per_carton;

        return response()->json([
            'product' => $product->name,
            'cartons' => $cartons_to_break,
            'resulting_units' => $total_units,
            'message' => "Broken down $cartons_to_break cartons into $total_units units on " . env('SESSION_DOMAIN')
        ]);
    }

    /**
     * Delete Product
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image) { 
            Storage::disk('public')->delete($product->image); 
        }
        $product->delete();
        $this->clearDashboardMetricsCache();

        return redirect()->route('product-list')->with('success', 'Product purged from ' . env('SESSION_DOMAIN'));
    }

    /**
     * Inventory History
     */
    public function inventory_history($id)
    {
        $activeBranch = $this->getActiveBranchContext();
        $inventoryHistories = DB::table('inventory_history')
            ->join('products', 'inventory_history.product_id', '=', 'products.id')
            ->select('inventory_history.*', 'products.name', 'products.sku', 'products.purchase_price')
            ->where('inventory_history.product_id', $id)
            ->orderByDesc('inventory_history.created_at')
            ->get();

        return view('inventory.inventory-history', compact('inventoryHistories', 'activeBranch'));
    }

    public function downloadImportTemplate()
    {
        $headers = [
            'name',
            'sku',
            'barcode',
            'category',
            'base_unit_name',
            'unit_type',
            'units_per_carton',
            'units_per_roll',
            'stock_cartons',
            'stock_rolls',
            'stock_units',
            'price',
            'retail_price',
            'wholesale_price',
            'special_price',
            'purchase_price',
            'stock',
            'description',
        ];

        $rows = [
            ['Indomie Chicken', '', '1234567890123', 'Noodles', 'pcs', 'carton', '40', '0', '10', '0', '0', '250', '250', '230', '220', '180', '400', 'Fast moving carton item'],
            ['Tissue Roll Premium', '', '8800112233445', 'Toiletries', 'roll', 'roll', '12', '1', '5', '12', '0', '1500', '1500', '1400', '1350', '1100', '120', 'Can be sold as roll or carton'],
        ];

        $content = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $content .= implode(',', array_map(function ($value) {
                $escaped = str_replace('"', '""', (string) $value);
                return '"' . $escaped . '"';
            }, $row)) . "\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product-import-template.csv"',
        ]);
    }

    public function import(Request $request)
    {
        Log::info('Product import request received.', [
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('import_file'),
            'filename' => $request->file('import_file')?->getClientOriginalName(),
            'size' => $request->file('import_file')?->getSize(),
            'branch_id' => $request->input('branch_id'),
        ]);

        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:20480',
            'branch_id' => 'nullable|string',
        ]);

        if (!Schema::hasTable('products') || !Schema::hasTable('categories')) {
            return redirect()->back()->with('error', 'Products and categories tables are required for import.');
        }

        try {
            $file = $request->file('import_file');
            $header = null;

            foreach ($this->spreadsheetRowIterator($file) as $row) {
                $header = $row;
                break;
            }

            if (!$header) {
                Log::warning('Product import file was empty after parsing.', [
                    'user_id' => auth()->id(),
                    'filename' => $file?->getClientOriginalName(),
                ]);
                return redirect()->back()->with('error', 'The import file is empty.');
            }

            $header = array_map(fn ($value) => $this->normalizeImportHeaderCell($value), $header);
            Log::info('Product import header parsed.', [
                'user_id' => auth()->id(),
                'header' => $header,
            ]);
            $required = ['name', 'category', 'base_unit_name', 'unit_type', 'price', 'purchase_price'];
            foreach ($required as $column) {
                if (!in_array($column, $header, true)) {
                    Log::warning('Product import missing required column.', [
                        'user_id' => auth()->id(),
                        'missing' => $column,
                        'header' => $header,
                    ]);
                    return redirect()->back()->with('error', 'Missing required import column: ' . $column);
                }
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;

            DB::transaction(function () use ($file, $header, &$created, &$updated, &$skipped, $request) {
                $activeBranch = $this->resolveBranchContext($request->input('branch_id'));

                foreach ($this->spreadsheetRowIterator($file) as $rowNumber => $row) {
                    if ($rowNumber === 0) {
                        continue;
                    }

                    $rowData = [];
                    foreach ($header as $index => $column) {
                        $rowData[$column] = trim((string) ($row[$index] ?? ''));
                    }

                    if (($rowData['name'] ?? '') === '') {
                        $skipped++;
                        continue;
                    }

                    try {
                        $categoryName = $rowData['category'] ?: 'General';
                        $category = $this->firstOrCreateImportCategory($categoryName);

                        $unitType = strtolower($rowData['unit_type'] ?: 'unit');
                        if (!in_array($unitType, ['unit', 'sachet', 'roll', 'carton'], true)) {
                            $unitType = 'unit';
                        }

                        $sku = $this->generateUniqueSku($rowData['sku'] ?? null, $rowData['name']);
                        $product = Product::query()
                            ->tap(fn ($query) => $this->applyTenantScope($query, 'products'))
                            ->where('sku', $sku)
                            ->first();
                        $isNew = $product === null;
                        $product = $product ?: new Product();

                        $stock = is_numeric($rowData['stock'] ?? null)
                            ? (int) $rowData['stock']
                            : $this->calculateStockFromPackaging([
                                'stock_cartons' => (float) ($rowData['stock_cartons'] ?? 0),
                                'stock_rolls' => (float) ($rowData['stock_rolls'] ?? 0),
                                'stock_units' => (float) ($rowData['stock_units'] ?? 0),
                                'units_per_carton' => max(0, (int) ($rowData['units_per_carton'] ?: 0)),
                                'units_per_roll' => max(0, (int) ($rowData['units_per_roll'] ?: 0)),
                            ]);

                        $payload = $this->sanitizeForProductColumns([
                            'name' => $rowData['name'],
                            'sku' => $sku,
                            'barcode' => $rowData['barcode'] ?: null,
                            'category_id' => $category->id,
                            'base_unit_name' => $rowData['base_unit_name'] ?: 'pcs',
                            'unit_type' => $unitType,
                            'units_per_carton' => max(0, (int) ($rowData['units_per_carton'] ?: 0)),
                            'units_per_roll' => max(0, (int) ($rowData['units_per_roll'] ?: 0)),
                            'price' => (float) (($rowData['retail_price'] ?? $rowData['price']) ?: 0),
                            'retail_price' => (float) (($rowData['retail_price'] ?? $rowData['price']) ?: 0),
                            'wholesale_price' => ($rowData['wholesale_price'] ?? '') !== '' ? (float) $rowData['wholesale_price'] : null,
                            'special_price' => ($rowData['special_price'] ?? '') !== '' ? (float) $rowData['special_price'] : null,
                            'purchase_price' => (float) ($rowData['purchase_price'] ?: 0),
                            'stock' => $stock,
                            'stock_quantity' => $stock,
                            'status' => 'active',
                            'description' => $rowData['description'] ?: null,
                            'company_id' => auth()->user()?->company_id ?: null,
                            'user_id' => auth()->id(),
                        ]);

                        $product->fill($payload);
                        $product->save();
                        $this->branchInventory->seedOpeningStock(
                            $product,
                            $stock,
                            $activeBranch,
                            (int) ($product->company_id ?? auth()->user()?->company_id ?? 0)
                        );

                        if ($isNew) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    } catch (\Throwable $rowException) {
                        $skipped++;
                        Log::warning('Product import row skipped.', [
                            'row' => $rowNumber + 1,
                            'name' => $rowData['name'] ?? null,
                            'error' => $rowException->getMessage(),
                        ]);
                    }
                }
            });
            $this->clearDashboardMetricsCache($request->input('branch_id'));

            Log::info('Product import completed.', [
                'user_id' => auth()->id(),
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
            ]);

            return redirect()->route('product-list')->with(
                'success',
                "Product import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}."
            );
        } catch (\Throwable $exception) {
            Log::error('Product import failed.', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return redirect()->back()->withInput()->with(
                'error',
                'The product import could not be completed. Please confirm the spreadsheet columns and try again.'
            );
        }
    }

    private function generateUniqueSku(?string $providedSku, string $name, ?int $ignoreId = null): string
    {
        $candidate = strtoupper(trim((string) $providedSku));
        if ($candidate !== '' && !$this->skuExists($candidate, $ignoreId)) {
            return $candidate;
        }

        $prefix = strtoupper(Str::of($name)->replaceMatches('/[^A-Za-z0-9]+/', '')->substr(0, 4)->value());
        $prefix = $prefix !== '' ? $prefix : 'PRD';

        do {
            $candidate = $prefix . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while ($this->skuExists($candidate, $ignoreId));

        return $candidate;
    }

    private function skuExists(string $sku, ?int $ignoreId = null): bool
    {
        return Product::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('sku', $sku)
            ->exists();
    }
}
