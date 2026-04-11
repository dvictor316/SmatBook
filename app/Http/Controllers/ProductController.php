<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Setting;
use App\Models\StockTransferAudit;
use App\Models\Subscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use App\Support\BranchInventoryService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

    private function hasDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            Log::error('ProductController database connection failure', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return false;
        }
    }

    private function renderProductIndexFallback(Request $request, string $message)
    {
        session()->flash('error', $message);

        return view('Inventory.Products.index', [
            'products' => collect(),
            'productRows' => collect(),
            'categories' => collect(),
            'availableBranches' => [],
            'stockTransferEnabled' => $this->planSupportsStockTransfer(),
            'search' => trim((string) $request->input('search', '')),
            'activeBranch' => [
                'id' => session('active_branch_id'),
                'name' => session('active_branch_name'),
            ],
            'session_domain' => env('SESSION_DOMAIN', null),
        ]);
    }

    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

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
            'id' => $branchId,
            'name' => $branchName,
        ];
    }

    private function companyScopedSettingKey(string $baseKey): string
    {
        $companyId = $this->tenantCompanyId();

        return $companyId > 0 ? "{$baseKey}_company_{$companyId}" : $baseKey;
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

    private function applyBranchScope($query, string $table, ?array $activeBranch = null)
    {
        $activeBranch = $activeBranch ?: $this->getActiveBranchContext();
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

    public function undoLastImport(Request $request): RedirectResponse
    {
        $cacheKey = 'product_import_last_' . auth()->id();
        $payload = Cache::get($cacheKey);
        if (empty($payload['ids'])) {
            return redirect()->route('product-list')->with('warning', 'No recent import found to undo.');
        }

        $deleted = 0;
        $failed = 0;
        $skipped = 0;
        $ids = array_unique((array) $payload['ids']);

        foreach ($ids as $id) {
            $product = Product::query()
                ->tap(fn ($query) => $this->applyTenantScope($query, 'products'))
                ->where('id', $id)
                ->first();

            if (!$product) {
                $skipped++;
                continue;
            }

            try {
                if (Schema::hasTable('inventory_history')) {
                    DB::table('inventory_history')->where('product_id', $product->id)->delete();
                }
                $product->delete();
                $deleted++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        Cache::forget($cacheKey);

        return redirect()->route('product-list')->with(
            'success',
            "Undo completed. Deleted: {$deleted}, Skipped: {$skipped}, Failed: {$failed}."
        );
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
                    $row = $this->expandEmbeddedDelimitedRow($row);
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

    private function expandEmbeddedDelimitedRow(array $row): array
    {
        if (count($row) !== 1) {
            return $row;
        }

        $cell = trim((string) ($row[0] ?? ''));
        if ($cell === '') {
            return $row;
        }

        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = null;
        $bestScore = 0;

        foreach ($delimiters as $delimiter) {
            $score = substr_count($cell, $delimiter);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $delimiter;
            }
        }

        if ($bestDelimiter === null || $bestScore === 0) {
            return $row;
        }

        $trimmedCell = preg_replace('/^"(.*)"$/s', '$1', $cell) ?? $cell;
        $expanded = str_getcsv($trimmedCell, $bestDelimiter);

        return is_array($expanded) && count($expanded) > 1 ? $expanded : $row;
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
        if (!$this->hasDatabaseConnection()) {
            return $this->renderProductIndexFallback(
                $request,
                'Product list is temporarily unavailable because the database connection failed.'
            );
        }

        if (!Schema::hasTable('products')) {
            $productRows = collect();
            return view('Inventory.Products.index', [
                'products' => collect(),
                'productRows' => $productRows,
                'categories' => collect(),
                'availableBranches' => $this->getAvailableBranches(),
                'search' => trim((string) $request->input('search', '')),
                'session_domain' => env('SESSION_DOMAIN', null)
            ]);
        }

        try {
            $search = $request->input('search');
            $activeBranch = $this->getActiveBranchContext();
            $this->branchInventory->backfillMissingBranchStocks($activeBranch, $this->tenantCompanyId());
            $hasCategories = Schema::hasTable('categories') && Schema::hasColumn('products', 'category_id');
            $hasBranchStocksTable = Schema::hasTable('product_branch_stocks');
            $hasBranchStocksBranchId = $hasBranchStocksTable && Schema::hasColumn('product_branch_stocks', 'branch_id');
            $hasProductBranchName = Schema::hasColumn('products', 'branch_name');
            $hasProductBranchId = Schema::hasColumn('products', 'branch_id');
            $query = Product::query();
            $this->applyTenantScope($query, 'products');

            if ($hasCategories) {
                $query->with('category');
            }

            if (!empty($activeBranch['id']) && $hasBranchStocksBranchId) {
                $query->whereHas('branchStocks', function ($branchQuery) use ($activeBranch) {
                    $branchQuery->where('branch_id', $activeBranch['id']);
                });
            } elseif (!empty($activeBranch['name']) && $hasProductBranchName) {
                $query->where('products.branch_name', $activeBranch['name']);
            } elseif (!empty($activeBranch['id']) && $hasProductBranchId) {
                $query->where('products.branch_id', $activeBranch['id']);
            }

            if ($hasBranchStocksBranchId && !empty($activeBranch['id'])) {
                $query->with(['branchStocks' => function ($branchQuery) use ($activeBranch) {
                    $branchQuery->where('branch_id', $activeBranch['id']);
                }]);
            }

            $orderColumn = Schema::hasColumn('products', 'created_at')
                ? 'created_at'
                : (Schema::hasColumn('products', 'id') ? 'id' : null);
            if ($orderColumn) {
                $query->orderByDesc($orderColumn);
            }

            if ($search) {
                $hasNameColumn = Schema::hasColumn('products', 'name');
                $hasSkuColumn = Schema::hasColumn('products', 'sku');

                if ($hasNameColumn || $hasSkuColumn) {
                    $query->where(function ($q) use ($search, $hasNameColumn, $hasSkuColumn) {
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
            $products->getCollection()->transform(function ($product) use ($activeBranch, $hasCategories) {
                $product->setAttribute('active_branch_stock', $this->branchInventory->getAvailableStock($product, $activeBranch));
                $product->setAttribute('category_name', $hasCategories ? ($product->category->name ?? null) : null);

                return $product;
            });
            $productRows = $products instanceof \Illuminate\Pagination\AbstractPaginator
                ? $products->getCollection()
                : $products;
            $categories = Schema::hasTable('categories')
                ? Category::orderBy(Schema::hasColumn('categories', 'name') ? 'name' : 'id')->get()
                : collect();

            return view('Inventory.Products.index', [
                'products' => $products,
                'productRows' => $productRows,
                'categories' => $categories,
                'availableBranches' => $this->getAvailableBranches(),
                'stockTransferEnabled' => $this->planSupportsStockTransfer(),
                'search' => $search,
                'activeBranch' => $activeBranch,
                'session_domain' => env('SESSION_DOMAIN', null)
            ]);
        } catch (\Throwable $e) {
            Log::error('Product list failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return $this->renderProductIndexFallback(
                $request,
                'Product list failed to load cleanly. The page has been recovered with a safe fallback.'
            );
        }
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

    public function serveImage(string $path)
    {
        $path = ltrim(trim($path), '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
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
                'price'            => 'nullable|numeric|min:0',
                'retail_price'     => 'nullable|numeric|min:0',
                'wholesale_price'  => 'nullable|numeric|min:0',
                'special_price'    => 'nullable|numeric|min:0',
                'purchase_price'   => 'nullable|numeric|min:0',
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
                'reorder_level'    => 'nullable|integer|min:0',
                'reorder_quantity' => 'nullable|integer|min:0',
                'description'      => 'nullable|string',
                'barcode'          => 'nullable|string|max:191',
            ];

            $validator = Validator::make($request->except('image'), $rules);
            $validator->after(function ($v) use ($request) {
                $price = trim((string) $request->input('price', ''));
                $purchase = trim((string) $request->input('purchase_price', ''));
                if ($price === '' && $purchase === '') {
                    $v->errors()->add('price', 'Enter a selling price or a purchase price before saving this product.');
                    $v->errors()->add('purchase_price', 'Enter a selling price or a purchase price before saving this product.');
                }
            });
            $validated = $validator->validate();

            $uploadedImage = $request->file('image');

            $validated['units_per_carton'] = (int) ($validated['units_per_carton'] ?? 0);
            $validated['units_per_roll'] = (int) ($validated['units_per_roll'] ?? 0);
            $validated['stock_cartons'] = (float) ($validated['stock_cartons'] ?? 0);
            $validated['stock_rolls'] = (float) ($validated['stock_rolls'] ?? 0);
            $validated['stock_units'] = (float) ($validated['stock_units'] ?? 0);
            $validated['reorder_level'] = max(0, (int) ($validated['reorder_level'] ?? 0));
            $validated['reorder_quantity'] = max(0, (int) ($validated['reorder_quantity'] ?? 0));
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

            $resolvedCompanyId = auth()->user()?->company_id ?? session('current_tenant_id');
            $selectedBranch = $this->resolveBranchContext($validated['branch_id'] ?? null);

            $validated['status'] = 'active';
            $validated['stock_quantity'] = $validated['stock'];
            $validated['user_id'] = auth()->id();
            if (Schema::hasColumn('products', 'company_id')) {
                $validated['company_id'] = $resolvedCompanyId ?: null;
            } else {
                unset($validated['company_id']);
            }
            if (Schema::hasColumn('products', 'branch_id')) {
                $validated['branch_id'] = $selectedBranch['id'] ?? null;
            } else {
                unset($validated['branch_id']);
            }
            if (Schema::hasColumn('products', 'branch_name')) {
                $validated['branch_name'] = $selectedBranch['name'] ?? null;
            } else {
                unset($validated['branch_name']);
            }
            if (!Schema::hasColumn('products', 'reorder_level')) {
                unset($validated['reorder_level']);
            }
            if (!Schema::hasColumn('products', 'reorder_quantity')) {
                unset($validated['reorder_quantity']);
            }
            $product = Product::create($validated);
            if (Schema::hasTable('product_branch_stocks')) {
                $branchId = $selectedBranch['id'] ?? null;
                if (!empty($branchId)) {
                    $alreadyHasStock = DB::table('product_branch_stocks')
                        ->where('product_id', $product->id)
                        ->where('branch_id', $branchId)
                        ->exists();
                    if (!$alreadyHasStock) {
                        DB::table('product_branch_stocks')->insert([
                            'product_id' => $product->id,
                            'branch_id' => $branchId,
                            'branch_name' => $selectedBranch['name'] ?? null,
                            'quantity' => (float) ($product->stock ?? 0),
                            'company_id' => $product->company_id ?? ($resolvedCompanyId ?: null),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            $this->branchInventory->seedOpeningStock(
                $product,
                (float) $validated['stock'],
                $selectedBranch,
                $product->company_id ?: ($resolvedCompanyId ?: null)
            );
            $this->clearDashboardMetricsCache($selectedBranch['id'] ?? null);

            return redirect()->route('product-list')
                ->with('success', 'Product added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Product store failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'payload' => $request->except(['image']),
            ]);

            $message = 'Product could not be added.';
            $showDetail = config('app.debug') || (auth()->user()?->role === 'super_admin');
            if ($showDetail) {
                $message .= ' ' . $e->getMessage();
            } else {
                $message .= ' Please check the highlighted fields and try again.';
            }

            return back()
                ->withInput()
                ->with('error', $message);
        }
    }

    /**
     * THE FIX: Added Missing Edit Method
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $activeBranch = $this->getActiveBranchContext();
        $product->setAttribute('active_branch_stock', $this->branchInventory->getAvailableStock($product, $activeBranch));
        $categories = Category::orderBy('name')->get();
        
        return view('Inventory.Products.edit', compact('product', 'categories', 'activeBranch'));
    }

public function inventory(Request $request)
{
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $productId = $request->input('product_id');
    $fromStart = $fromDate ? \Carbon\Carbon::parse($fromDate)->startOfDay()->toDateTimeString() : null;
    $toEnd = $toDate ? \Carbon\Carbon::parse($toDate)->endOfDay()->toDateTimeString() : null;

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

    $activeBranch = $this->getActiveBranchContext();
    $productsQuery = Product::query()
        ->orderBy('name', 'asc')
        ->tap(fn ($q) => $applyTenantScope($q, 'products'));

    if (!empty($activeBranch['id']) && Schema::hasTable('product_branch_stocks')) {
        $productsQuery->whereHas('branchStocks', fn ($q) => $q->where('branch_id', $activeBranch['id']));
    } elseif (!empty($activeBranch['id']) && Schema::hasColumn('products', 'branch_id')) {
        $productsQuery->where('products.branch_id', $activeBranch['id']);
    } elseif (!empty($activeBranch['name']) && Schema::hasColumn('products', 'branch_name')) {
        $productsQuery->where('products.branch_name', $activeBranch['name']);
    }

    $products = $productsQuery->get(['id', 'name']);

    $purchaseDateColumn = Schema::hasColumn('purchases', 'purchase_date')
        ? 'purchase_date'
        : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');

    $saleDateColumn = Schema::hasColumn('sales', 'order_date')
        ? 'order_date'
        : (Schema::hasColumn('sales', 'date') ? 'date' : 'created_at');

    if (!$fromDate || !$toDate) {
        $latestActivity = null;

        if (Schema::hasTable('inventory_history')) {
            $historyLatest = DB::table('inventory_history')
                ->when(
                    $companyId > 0 && Schema::hasColumn('products', 'company_id'),
                    function ($q) use ($companyId) {
                        $q->join('products', 'inventory_history.product_id', '=', 'products.id')
                          ->where('products.company_id', $companyId);
                    }
                )
                ->tap(fn ($q) => $this->applyBranchScope($q, 'inventory_history', $activeBranch))
                ->when(
                    $companyId === 0 && $user && Schema::hasColumn('inventory_history', 'user_id'),
                    fn ($q) => $q->where('inventory_history.user_id', $user->id)
                )
                ->max('inventory_history.created_at');
            $latestActivity = $historyLatest ?: $latestActivity;
        }

        if (Schema::hasTable('purchases')) {
            $purchaseLatest = DB::table('purchases')
                ->tap(fn ($q) => $applyTenantScope($q, 'purchases'))
                ->tap(fn ($q) => $this->applyBranchScope($q, 'purchases', $activeBranch))
                ->max('purchases.' . $purchaseDateColumn);
            if ($purchaseLatest && (!$latestActivity || $purchaseLatest > $latestActivity)) {
                $latestActivity = $purchaseLatest;
            }
        }

        if (Schema::hasTable('sales')) {
            $saleLatest = DB::table('sales')
                ->tap(fn ($q) => $applyTenantScope($q, 'sales'))
                ->tap(fn ($q) => $this->applyBranchScope($q, 'sales', $activeBranch))
                ->max('sales.' . $saleDateColumn);
            if ($saleLatest && (!$latestActivity || $saleLatest > $latestActivity)) {
                $latestActivity = $saleLatest;
            }
        }

        $effectiveEnd = $latestActivity
            ? \Carbon\Carbon::parse($latestActivity)->endOfDay()
            : now()->endOfDay();

        $toDate = $toDate ?: $effectiveEnd->toDateString();
        $fromDate = $fromDate ?: $effectiveEnd->copy()->startOfMonth()->toDateString();
        $fromStart = \Carbon\Carbon::parse($fromDate)->startOfDay()->toDateTimeString();
        $toEnd = \Carbon\Carbon::parse($toDate)->endOfDay()->toDateTimeString();
    }

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
        ->tap(fn ($q) => $this->applyBranchScope($q, 'purchases', $activeBranch))
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
                ->tap(fn ($q) => $this->applyBranchScope($q, 'inventory_history', $activeBranch))
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
                ->tap(fn ($q) => $this->applyBranchScope($q, 'purchases', $activeBranch))
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
        ->tap(fn ($q) => $this->applyBranchScope($q, 'sales', $activeBranch))
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
                ->tap(fn ($q) => $this->applyBranchScope($q, 'inventory_history', $activeBranch))
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
                ->tap(fn ($q) => $this->applyBranchScope($q, 'sales', $activeBranch))
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
        $activeBranch = $this->getActiveBranchContext();

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
            'reorder_level'    => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
        ]);

        $validated['units_per_carton'] = (int) ($validated['units_per_carton'] ?? 0);
        $validated['units_per_roll'] = (int) ($validated['units_per_roll'] ?? 0);
        $validated['stock_cartons'] = (float) ($validated['stock_cartons'] ?? 0);
        $validated['stock_rolls'] = (float) ($validated['stock_rolls'] ?? 0);
        $validated['stock_units'] = (float) ($validated['stock_units'] ?? 0);
        $validated['reorder_level'] = max(0, (int) ($validated['reorder_level'] ?? 0));
        $validated['reorder_quantity'] = max(0, (int) ($validated['reorder_quantity'] ?? 0));
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

        if (!Schema::hasColumn('products', 'reorder_level')) {
            unset($validated['reorder_level']);
        }
        if (!Schema::hasColumn('products', 'reorder_quantity')) {
            unset($validated['reorder_quantity']);
        }

        $validated['stock_quantity'] = $validated['stock'];
        $product->update($validated);

        if (!empty($activeBranch['id'])) {
            $this->branchInventory->setBranchStock(
                $product->fresh(),
                (float) ($validated['stock'] ?? 0),
                $activeBranch,
                $this->tenantCompanyId()
            );
        }

        $this->clearDashboardMetricsCache($activeBranch['id'] ?? null);

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
                (int) ($product->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0)
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
            if (Schema::hasColumn('inventory_history', 'company_id')) {
                $payload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
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
                        if (Schema::hasColumn('purchases', 'company_id')) {
                            $purchase->company_id = auth()->user()?->company_id ?? session('current_tenant_id');
                        }
                        if (Schema::hasColumn('purchases', 'user_id')) {
                            $purchase->user_id = auth()->id();
                        }
                        $purchase->save();

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

                $companyId = (int) ($product->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
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
                    if (Schema::hasColumn('inventory_history', 'company_id')) {
                        $basePayload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
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

                if (Schema::hasTable('stock_transfer_audits')) {
                    StockTransferAudit::query()->create([
                        'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                        'product_id' => $product->id,
                        'from_branch_id' => $sourceContext['id'],
                        'from_branch_name' => $sourceContext['name'],
                        'to_branch_id' => $destinationContext['id'],
                        'to_branch_name' => $destinationContext['name'],
                        'quantity' => $quantity,
                        'initiated_by' => auth()->id(),
                        'notes' => 'Branch stock transfer',
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        $this->clearDashboardMetricsCache($validated['from_branch_id'] ?? null);
        $this->clearDashboardMetricsCache($validated['to_branch_id'] ?? null);

        return redirect()->back()->with('success', 'Stock transferred successfully between branches.');
    }

    public function transferAudit(Request $request)
    {
        $search = trim((string) $request->string('q'));
        $month = trim((string) $request->string('month'));
        $fromDate = trim((string) $request->string('from_date'));
        $toDate = trim((string) $request->string('to_date'));

        $auditsQuery = StockTransferAudit::query()
            ->with(['product', 'initiator'])
            ->latest();

        if ($search !== '') {
            $auditsQuery->where(function ($query) use ($search) {
                $query->where('from_branch_name', 'like', '%' . $search . '%')
                    ->orWhere('to_branch_name', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('product', fn ($sub) => $sub->where('name', 'like', '%' . $search . '%')->orWhere('sku', 'like', '%' . $search . '%'))
                    ->orWhereHas('initiator', fn ($sub) => $sub->where('name', 'like', '%' . $search . '%'));
            });
        }
        if ($month !== '') {
            $auditsQuery->whereBetween('created_at', [
                now()->parse($month . '-01')->startOfMonth()->toDateString(),
                now()->parse($month . '-01')->endOfMonth()->toDateString(),
            ]);
        } else {
            if ($fromDate !== '') {
                $auditsQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate !== '') {
                $auditsQuery->whereDate('created_at', '<=', $toDate);
            }
        }

        $audits = $auditsQuery->paginate(20)->appends($request->query());

        return view('Inventory.transfer-audit', compact('audits', 'search', 'month', 'fromDate', 'toDate'));
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
        $inventoryHistories = collect();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if (Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
            $historyQuery = DB::table('inventory_history')
                ->join('products', 'inventory_history.product_id', '=', 'products.id')
                ->select(
                    'inventory_history.id',
                    'inventory_history.created_at',
                    'inventory_history.type',
                    'inventory_history.reference',
                    'inventory_history.quantity',
                    'products.name',
                    'products.sku',
                    'products.purchase_price'
                )
                ->where('inventory_history.product_id', $id)
                ->tap(fn ($q) => $this->applyTenantScope($q, 'products'));

            if ($branchId !== '' || $branchName !== '') {
                $historyQuery->where(function ($sub) use ($branchId, $branchName) {
                    $matched = false;
                    if ($branchId !== '' && Schema::hasColumn('inventory_history', 'branch_id')) {
                        $sub->where('inventory_history.branch_id', $branchId);
                        $matched = true;
                    }
                    if ($branchName !== '' && Schema::hasColumn('inventory_history', 'branch_name')) {
                        $method = $matched ? 'orWhere' : 'where';
                        $sub->{$method}('inventory_history.branch_name', $branchName);
                        $matched = true;
                    }

                    if ($branchId !== '' && Schema::hasColumn('products', 'branch_id')) {
                        $method = $matched ? 'orWhere' : 'where';
                        $sub->{$method}(function ($fallback) use ($branchId) {
                            if (Schema::hasColumn('inventory_history', 'branch_id')) {
                                $fallback->whereNull('inventory_history.branch_id');
                            }
                            if (Schema::hasColumn('inventory_history', 'branch_name')) {
                                $fallback->whereNull('inventory_history.branch_name');
                            }
                            $fallback->where('products.branch_id', $branchId);
                        });
                        $matched = true;
                    }
                    if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                        $method = $matched ? 'orWhere' : 'where';
                        $sub->{$method}(function ($fallback) use ($branchName) {
                            if (Schema::hasColumn('inventory_history', 'branch_id')) {
                                $fallback->whereNull('inventory_history.branch_id');
                            }
                            if (Schema::hasColumn('inventory_history', 'branch_name')) {
                                $fallback->whereNull('inventory_history.branch_name');
                            }
                            $fallback->where('products.branch_name', $branchName);
                        });
                    }
                });
            }

            $inventoryHistories = $inventoryHistories->merge($historyQuery->get());
        }

        if (Schema::hasTable('purchase_items') && Schema::hasTable('purchases') && Schema::hasTable('products')) {
            $purchaseQtyColumn = Schema::hasColumn('purchase_items', 'qty')
                ? 'qty'
                : (Schema::hasColumn('purchase_items', 'quantity') ? 'quantity' : null);
            $purchaseReferenceColumn = Schema::hasColumn('purchases', 'reference_no')
                ? 'reference_no'
                : (Schema::hasColumn('purchases', 'purchase_no') ? 'purchase_no' : null);

            if ($purchaseQtyColumn) {
                $purchaseHistoryQuery = DB::table('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->join('products', 'purchase_items.product_id', '=', 'products.id')
                    ->selectRaw("
                        CONCAT('purchase-', purchase_items.id) as id,
                        purchases.created_at as created_at,
                        'in' as type,
                        COALESCE(" . ($purchaseReferenceColumn ? "purchases.{$purchaseReferenceColumn}" : 'NULL') . ", purchases.purchase_no, CONCAT('PUR-', purchases.id)) as reference,
                        COALESCE(purchase_items.{$purchaseQtyColumn}, 0) as quantity,
                        products.name as name,
                        products.sku as sku,
                        products.purchase_price as purchase_price
                    ")
                    ->where('purchase_items.product_id', $id)
                    ->tap(fn ($q) => $this->applyTenantScope($q, 'products'))
                    ->tap(fn ($q) => $this->applyTenantScope($q, 'purchases'));

                if ($branchId !== '' || $branchName !== '') {
                    $purchaseHistoryQuery->where(function ($sub) use ($branchId, $branchName) {
                        $matched = false;
                        if ($branchId !== '' && Schema::hasColumn('purchase_items', 'branch_id')) {
                            $sub->where('purchase_items.branch_id', $branchId);
                            $matched = true;
                        }
                        if ($branchName !== '' && Schema::hasColumn('purchase_items', 'branch_name')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}('purchase_items.branch_name', $branchName);
                            $matched = true;
                        }
                        if ($branchId !== '' && Schema::hasColumn('purchases', 'branch_id')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}('purchases.branch_id', $branchId);
                            $matched = true;
                        }
                        if ($branchName !== '' && Schema::hasColumn('purchases', 'branch_name')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}('purchases.branch_name', $branchName);
                            $matched = true;
                        }
                        if ($branchId !== '' && Schema::hasColumn('products', 'branch_id')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}(function ($fallback) use ($branchId) {
                                if (Schema::hasColumn('purchase_items', 'branch_id')) {
                                    $fallback->whereNull('purchase_items.branch_id');
                                }
                                if (Schema::hasColumn('purchase_items', 'branch_name')) {
                                    $fallback->whereNull('purchase_items.branch_name');
                                }
                                if (Schema::hasColumn('purchases', 'branch_id')) {
                                    $fallback->whereNull('purchases.branch_id');
                                }
                                if (Schema::hasColumn('purchases', 'branch_name')) {
                                    $fallback->whereNull('purchases.branch_name');
                                }
                                $fallback->where('products.branch_id', $branchId);
                            });
                            $matched = true;
                        }
                        if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}(function ($fallback) use ($branchName) {
                                if (Schema::hasColumn('purchase_items', 'branch_id')) {
                                    $fallback->whereNull('purchase_items.branch_id');
                                }
                                if (Schema::hasColumn('purchase_items', 'branch_name')) {
                                    $fallback->whereNull('purchase_items.branch_name');
                                }
                                if (Schema::hasColumn('purchases', 'branch_id')) {
                                    $fallback->whereNull('purchases.branch_id');
                                }
                                if (Schema::hasColumn('purchases', 'branch_name')) {
                                    $fallback->whereNull('purchases.branch_name');
                                }
                                $fallback->where('products.branch_name', $branchName);
                            });
                        }
                    });
                }

                $inventoryHistories = $inventoryHistories->merge($purchaseHistoryQuery->get());
            }
        }

        if (Schema::hasTable('sale_items') && Schema::hasTable('sales') && Schema::hasTable('products')) {
            $saleQtyColumn = Schema::hasColumn('sale_items', 'qty')
                ? 'qty'
                : (Schema::hasColumn('sale_items', 'quantity') ? 'quantity' : null);
            $saleReferenceColumn = Schema::hasColumn('sales', 'invoice_no')
                ? 'invoice_no'
                : (Schema::hasColumn('sales', 'order_number') ? 'order_number' : null);

            if ($saleQtyColumn) {
                $saleHistoryQuery = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->selectRaw("
                        CONCAT('sale-', sale_items.id) as id,
                        sales.created_at as created_at,
                        'out' as type,
                        COALESCE(" . ($saleReferenceColumn ? "sales.{$saleReferenceColumn}" : 'NULL') . ", CONCAT('SALE-', sales.id)) as reference,
                        COALESCE(sale_items.{$saleQtyColumn}, 0) as quantity,
                        products.name as name,
                        products.sku as sku,
                        products.purchase_price as purchase_price
                    ")
                    ->where('sale_items.product_id', $id)
                    ->tap(fn ($q) => $this->applyTenantScope($q, 'products'))
                    ->tap(fn ($q) => $this->applyTenantScope($q, 'sales'));

                if ($branchId !== '' || $branchName !== '') {
                    $saleHistoryQuery->where(function ($sub) use ($branchId, $branchName) {
                        $matched = false;
                        if ($branchId !== '' && Schema::hasColumn('sale_items', 'branch_id')) {
                            $sub->where('sale_items.branch_id', $branchId);
                            $matched = true;
                        }
                        if ($branchName !== '' && Schema::hasColumn('sale_items', 'branch_name')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}('sale_items.branch_name', $branchName);
                            $matched = true;
                        }
                        if ($branchId !== '' && Schema::hasColumn('sales', 'branch_id')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}('sales.branch_id', $branchId);
                            $matched = true;
                        }
                        if ($branchName !== '' && Schema::hasColumn('sales', 'branch_name')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}('sales.branch_name', $branchName);
                            $matched = true;
                        }
                        if ($branchId !== '' && Schema::hasColumn('products', 'branch_id')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}(function ($fallback) use ($branchId) {
                                if (Schema::hasColumn('sale_items', 'branch_id')) {
                                    $fallback->whereNull('sale_items.branch_id');
                                }
                                if (Schema::hasColumn('sale_items', 'branch_name')) {
                                    $fallback->whereNull('sale_items.branch_name');
                                }
                                if (Schema::hasColumn('sales', 'branch_id')) {
                                    $fallback->whereNull('sales.branch_id');
                                }
                                if (Schema::hasColumn('sales', 'branch_name')) {
                                    $fallback->whereNull('sales.branch_name');
                                }
                                $fallback->where('products.branch_id', $branchId);
                            });
                            $matched = true;
                        }
                        if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                            $method = $matched ? 'orWhere' : 'where';
                            $sub->{$method}(function ($fallback) use ($branchName) {
                                if (Schema::hasColumn('sale_items', 'branch_id')) {
                                    $fallback->whereNull('sale_items.branch_id');
                                }
                                if (Schema::hasColumn('sale_items', 'branch_name')) {
                                    $fallback->whereNull('sale_items.branch_name');
                                }
                                if (Schema::hasColumn('sales', 'branch_id')) {
                                    $fallback->whereNull('sales.branch_id');
                                }
                                if (Schema::hasColumn('sales', 'branch_name')) {
                                    $fallback->whereNull('sales.branch_name');
                                }
                                $fallback->where('products.branch_name', $branchName);
                            });
                        }
                    });
                }

                $inventoryHistories = $inventoryHistories->merge($saleHistoryQuery->get());
            }
        }

        $inventoryHistories = $inventoryHistories
            ->sortByDesc(fn ($row) => strtotime((string) ($row->created_at ?? '1970-01-01 00:00:00')))
            ->values();

        return view('Inventory.inventory-history', compact('inventoryHistories', 'activeBranch'));
    }

    public function update_history(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:in,out',
        ]);

        if (!Schema::hasTable('inventory_history')) {
            return redirect()->back()->with('error', 'Inventory history table is not available.');
        }

        $history = $this->findEditableInventoryHistoryRecord((int) $validated['id']);
        if (!$history) {
            return redirect()->back()->with('error', 'Inventory history record not found for the active branch.');
        }

        DB::table('inventory_history')
            ->where('id', (int) $validated['id'])
            ->update([
                'quantity' => (float) $validated['quantity'],
                'type' => $validated['type'],
                'updated_at' => now(),
            ]);

        $this->clearDashboardMetricsCache();

        return redirect()->back()->with('success', 'Inventory history record updated successfully.');
    }

    public function delete_history(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        if (!Schema::hasTable('inventory_history')) {
            return redirect()->back()->with('error', 'Inventory history table is not available.');
        }

        $history = $this->findEditableInventoryHistoryRecord((int) $validated['id']);
        if (!$history) {
            return redirect()->back()->with('error', 'Inventory history record not found for the active branch.');
        }

        DB::table('inventory_history')->where('id', (int) $validated['id'])->delete();
        $this->clearDashboardMetricsCache();

        return redirect()->back()->with('success', 'Inventory history record deleted successfully.');
    }

    private function findEditableInventoryHistoryRecord(int $historyId): ?object
    {
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        $query = DB::table('inventory_history')
            ->join('products', 'inventory_history.product_id', '=', 'products.id')
            ->select('inventory_history.*')
            ->where('inventory_history.id', $historyId);

        $this->applyTenantScope($query, 'inventory_history');
        $this->applyTenantScope($query, 'products');

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($branchId, $branchName) {
                $matched = false;
                if ($branchId !== '' && Schema::hasColumn('inventory_history', 'branch_id')) {
                    $sub->where('inventory_history.branch_id', $branchId);
                    $matched = true;
                }
                if ($branchName !== '' && Schema::hasColumn('inventory_history', 'branch_name')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}('inventory_history.branch_name', $branchName);
                    $matched = true;
                }
                if ($branchId !== '' && Schema::hasColumn('products', 'branch_id')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}(function ($fallback) use ($branchId) {
                        if (Schema::hasColumn('inventory_history', 'branch_id')) {
                            $fallback->whereNull('inventory_history.branch_id');
                        }
                        if (Schema::hasColumn('inventory_history', 'branch_name')) {
                            $fallback->whereNull('inventory_history.branch_name');
                        }
                        $fallback->where('products.branch_id', $branchId);
                    });
                    $matched = true;
                }
                if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}(function ($fallback) use ($branchName) {
                        if (Schema::hasColumn('inventory_history', 'branch_id')) {
                            $fallback->whereNull('inventory_history.branch_id');
                        }
                        if (Schema::hasColumn('inventory_history', 'branch_name')) {
                            $fallback->whereNull('inventory_history.branch_name');
                        }
                        $fallback->where('products.branch_name', $branchName);
                    });
                }
            });
        }

        return $query->first();
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
            'retail_price',
            'wholesale_price',
            'special_price',
            'purchase_price',
            'stock',
            'description',
        ];

        $rows = [
            ['Indomie Chicken', '', '1234567890123', 'Noodles', 'pcs', 'carton', '40', '0', '10', '0', '0', '250', '230', '220', '180', '400', 'Fast moving carton item'],
            ['Tissue Roll Premium', '', '8800112233445', 'Toiletries', 'roll', 'roll', '12', '1', '5', '12', '0', '1500', '1400', '1350', '1100', '120', 'Can be sold as roll or carton'],
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
            'update_existing' => 'nullable|boolean',
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
            $required = ['name', 'category', 'base_unit_name', 'unit_type', 'purchase_price'];
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
            if (!in_array('retail_price', $header, true) && !in_array('price', $header, true)) {
                Log::warning('Product import missing required column.', [
                    'user_id' => auth()->id(),
                    'missing' => 'retail_price',
                    'header' => $header,
                ]);
                return redirect()->back()->with('error', 'Missing required import column: retail_price');
            }

            $created = 0;
            $updated = 0;
            $updatedExisting = 0;
            $skipped = 0;
            $duplicates = 0;
            $missingRequired = 0;
            $rowErrors = [];
            $updateExisting = $request->boolean('update_existing');
            $createdIds = [];

            DB::transaction(function () use ($file, $header, &$created, &$updated, &$updatedExisting, &$skipped, &$duplicates, &$missingRequired, &$rowErrors, $updateExisting, $request) {
                $activeBranch = $this->resolveBranchContext($request->input('branch_id'));

                foreach ($this->spreadsheetRowIterator($file) as $rowNumber => $row) {
                    if ($rowNumber === 0) {
                        continue;
                    }

                    $rowData = [];
                    foreach ($header as $index => $column) {
                        $rowData[$column] = trim((string) ($row[$index] ?? ''));
                    }

                    $requiredFields = ['name', 'category', 'base_unit_name', 'unit_type', 'purchase_price'];
                    $missing = [];
                    foreach ($requiredFields as $field) {
                        if (($rowData[$field] ?? '') === '') {
                            $missing[] = $field;
                        }
                    }
                    if (($rowData['retail_price'] ?? '') === '' && ($rowData['price'] ?? '') === '') {
                        $missing[] = 'retail_price';
                    }
                    if (!empty($missing)) {
                        $skipped++;
                        $missingRequired++;
                        if (count($rowErrors) < 10) {
                            $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': missing ' . implode(', ', $missing);
                        }
                        continue;
                    }

                    try {
                        $categoryName = $rowData['category'];
                        $category = $this->firstOrCreateImportCategory($categoryName);

                        $unitType = strtolower($rowData['unit_type'] ?: 'unit');
                        if (!in_array($unitType, ['unit', 'sachet', 'roll', 'carton'], true)) {
                            $skipped++;
                            if (count($rowErrors) < 10) {
                                $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': invalid unit_type ' . ($rowData['unit_type'] ?? '');
                            }
                            continue;
                        }

                        $providedSku = $rowData['sku'] ?? null;
                        $barcode = $rowData['barcode'] ?? null;
                        $productQuery = Product::query()
                            ->tap(fn ($query) => $this->applyTenantScope($query, 'products'))
                            ->when($providedSku, fn ($query) => $query->where('sku', $providedSku))
                            ->when(!$providedSku && $barcode, fn ($query) => $query->where('barcode', $barcode))
                            ->when(!$providedSku && !$barcode, fn ($query) => $query->where('name', $rowData['name'])->where('category_id', $category->id))
                            ->limit(1);
                        $existing = $productQuery->first();
                        if ($existing && !$updateExisting) {
                            $skipped++;
                            $duplicates++;
                            if (count($rowErrors) < 10) {
                                $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': duplicate product detected';
                            }
                            continue;
                        }

                        $sku = $existing?->sku ?: $this->generateUniqueSku($providedSku, $rowData['name']);
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

                        $retailPrice = ($rowData['retail_price'] ?? '') !== '' ? $rowData['retail_price'] : ($rowData['price'] ?? '');
                        $payload = $this->sanitizeForProductColumns([
                            'name' => $rowData['name'],
                            'sku' => $sku,
                            'barcode' => $rowData['barcode'] ?: null,
                            'category_id' => $category->id,
                            'base_unit_name' => $rowData['base_unit_name'] ?: 'pcs',
                            'unit_type' => $unitType,
                            'units_per_carton' => max(0, (int) ($rowData['units_per_carton'] ?: 0)),
                            'units_per_roll' => max(0, (int) ($rowData['units_per_roll'] ?: 0)),
                            'price' => (float) ($retailPrice ?: 0),
                            'retail_price' => (float) ($retailPrice ?: 0),
                            'wholesale_price' => ($rowData['wholesale_price'] ?? '') !== '' ? (float) $rowData['wholesale_price'] : null,
                            'special_price' => ($rowData['special_price'] ?? '') !== '' ? (float) $rowData['special_price'] : null,
                            'purchase_price' => (float) ($rowData['purchase_price'] ?: 0),
                            'stock' => $stock,
                            'stock_quantity' => $stock,
                            'status' => 'active',
                            'description' => $rowData['description'] ?: null,
                            'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                            'user_id' => auth()->id(),
                        ]);

                        $product->fill($payload);
                        $product->save();
                        $this->branchInventory->seedOpeningStock(
                            $product,
                            $stock,
                            $activeBranch,
                            (int) ($product->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0)
                        );

                        if ($isNew) {
                            $created++;
                            $createdIds[] = $product->id;
                        } else {
                            $updated++;
                            if ($existing) {
                                $updatedExisting++;
                            }
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
            if (!empty($createdIds)) {
                Cache::put('product_import_last_' . auth()->id(), [
                    'ids' => array_values(array_unique($createdIds)),
                    'created_at' => now(),
                ], now()->addHours(6));
            }

            Log::info('Product import completed.', [
                'user_id' => auth()->id(),
                'created' => $created,
                'updated' => $updated,
                'updated_existing' => $updatedExisting,
                'skipped' => $skipped,
                'duplicates' => $duplicates,
                'missing_required' => $missingRequired,
            ]);

            $summary = "Product import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";
            if ($updatedExisting > 0) {
                $summary .= " Updated existing: {$updatedExisting}.";
            }
            if ($duplicates > 0 || $missingRequired > 0) {
                $summary .= " Duplicates skipped: {$duplicates}, Missing required: {$missingRequired}.";
            }

            $redirect = redirect()->route('product-list')->with('success', $summary);
            if (!empty($rowErrors)) {
                $redirect->with('warning', 'Some rows were skipped: ' . implode(' | ', $rowErrors));
            }
            return $redirect;
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
