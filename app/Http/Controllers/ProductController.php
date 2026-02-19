<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
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

        $search = $request->input('search');
        $query = Product::with('category')->orderByDesc('created_at');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get(); 

        return view('inventory.Products.index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'session_domain' => env('SESSION_DOMAIN', null)
        ]);
    }

    /**
     * Unit Definition Logic
     */
    public function units()
    {
        $units = [
            (object)['name' => 'Unit', 'short_name' => 'pc'],
            (object)['name' => 'Roll', 'short_name' => 'rl'],
            (object)['name' => 'Carton', 'short_name' => 'ctn'],
        ];

        $products = Product::select('id', 'name', 'sku', 'base_unit_name')->get();
        return view('inventory.Products.units', compact('products', 'units'));
    }

    /**
     * Create Product View
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('inventory.Products.add-products', compact('categories'));
    }

    /**
     * Store New Product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:191',
            'sku'              => 'required|string|unique:products,sku',
            'price'            => 'required|numeric|min:0', 
            'purchase_price'   => 'required|numeric|min:0', 
            'stock'            => 'required|integer|min:0', 
            'units_per_carton' => 'nullable|integer|min:0',
            'units_per_roll'   => 'nullable|integer|min:0',
            'base_unit_name'   => 'required|string|max:100',
            'category_id'      => 'required|exists:categories,id',
            'unit_type'        => 'required|in:unit,sachet,roll,carton',
            'description'      => 'nullable|string',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $validated['units_per_carton'] = (int) ($validated['units_per_carton'] ?? 0);
        $validated['units_per_roll'] = (int) ($validated['units_per_roll'] ?? 0);

        if ($validated['unit_type'] === 'carton' && $validated['units_per_carton'] < 1) {
            return back()->withErrors([
                'units_per_carton' => 'Units per carton must be at least 1 when default unit type is Carton.'
            ])->withInput();
        }
        if ($validated['unit_type'] === 'roll' && $validated['units_per_roll'] < 1) {
            return back()->withErrors([
                'units_per_roll' => 'Units per roll must be at least 1 when default unit type is Roll.'
            ])->withInput();
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['status'] = 'active';
        $validated['stock_quantity'] = $request->stock; // Dual column sync

        Product::create($validated);

        return redirect()->route('product-list')
            ->with('success', 'Product synchronized successfully on ' . env('SESSION_DOMAIN', 'local'));
    }

    /**
     * THE FIX: Added Missing Edit Method
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        
        return view('inventory.Products.edit', compact('product', 'categories'));
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
            'sku'              => 'required|string|unique:products,sku,' . $id,
            'price'            => 'required|numeric|min:0',
            'purchase_price'   => 'required|numeric|min:0',
            'stock'            => 'required|integer|min:0',
            'units_per_carton' => 'nullable|integer|min:0',
            'units_per_roll'   => 'nullable|integer|min:0',
            'base_unit_name'   => 'required|string|max:100',
            'category_id'      => 'required|exists:categories,id',
            'unit_type'        => 'required|in:unit,sachet,roll,carton',
            'status'           => 'required|in:active,inactive',
            'description'      => 'nullable|string',
            'barcode'          => 'nullable|string|max:191',
            'image'            => 'nullable|image|max:5120',
        ]);

        $validated['units_per_carton'] = (int) ($validated['units_per_carton'] ?? 0);
        $validated['units_per_roll'] = (int) ($validated['units_per_roll'] ?? 0);

        if ($validated['unit_type'] === 'carton' && $validated['units_per_carton'] < 1) {
            return back()->withErrors([
                'units_per_carton' => 'Units per carton must be at least 1 when default unit type is Carton.'
            ])->withInput();
        }
        if ($validated['unit_type'] === 'roll' && $validated['units_per_roll'] < 1) {
            return back()->withErrors([
                'units_per_roll' => 'Units per roll must be at least 1 when default unit type is Roll.'
            ])->withInput();
        }

        if ($request->hasFile('image')) {
            if ($product->image) { 
                Storage::disk('public')->delete($product->image); 
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['stock_quantity'] = $request->stock; 
        $product->update($validated);

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
            
            DB::table('products')->where('id', $request->product_id)->update([
                'stock' => DB::raw("stock $operator " . $request->quantity),
                'stock_quantity' => DB::raw("stock_quantity $operator " . $request->quantity),
                'updated_at' => now()
            ]);

            $payload = [
                'product_id' => $request->product_id,
                'quantity'   => $request->quantity,
                'type'       => $request->type,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('inventory_history', 'user_id')) {
                $payload['user_id'] = auth()->id() ?? (int) DB::table('users')->min('id');
            }
            if (Schema::hasColumn('inventory_history', 'reference')) {
                $payload['reference'] = 'Manual Adjustment';
            }

            $historyId = DB::table('inventory_history')->insertGetId($payload);

            // Raw DB stock update bypasses Eloquent observer; mirror stock-in to purchases here.
            if ($request->type === 'in') {
                $product = Product::query()->find($request->product_id);
                if ($product && Schema::hasTable('purchases') && Schema::hasTable('purchase_items')) {
                    $purchaseNo = 'AUTO-STK-' . $historyId;
                    if (!Purchase::where('purchase_no', $purchaseNo)->exists()) {
                        $unitPrice = (float) ($product->purchase_price ?? $product->price ?? 0);
                        $amount = round(((float) $request->quantity) * $unitPrice, 2);

                        $purchase = Purchase::create([
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

        return redirect()->back()->with('success', 'Inventory state updated on ' . env('SESSION_DOMAIN'));
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

        return redirect()->route('product-list')->with('success', 'Product purged from ' . env('SESSION_DOMAIN'));
    }

    /**
     * Inventory History
     */
    public function inventory_history($id)
    {
        $inventoryHistories = DB::table('inventory_history')
            ->join('products', 'inventory_history.product_id', '=', 'products.id')
            ->select('inventory_history.*', 'products.name', 'products.sku', 'products.purchase_price')
            ->where('inventory_history.product_id', $id)
            ->orderByDesc('inventory_history.created_at')
            ->get();

        return view('inventory.inventory-history', compact('inventoryHistories'));
    }
}
