<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\TaxCode;
use App\Models\Bank;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PurchaseExport;
use App\Models\Transaction;
use App\Support\BranchInventoryService;
use App\Support\LedgerService;
// -----------------------------

class PurchaseController extends Controller
{
public function applyTenantScope($query, string $table)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        }

        return $query;
    }

public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

public function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

public function index()
    {
        $activeBranch = $this->getActiveBranchContext();
        // 1. Fetch Purchases (using 'vendor' or 'supplier' based on your model relation)
        $purchaseQuery = Purchase::with(['supplier', 'items.product']);
        $this->applyTenantScope($purchaseQuery, 'purchases');
        $purchases = $purchaseQuery
            ->when(!empty($activeBranch['name']) && Schema::hasColumn('purchases', 'branch_name'), function ($query) use ($activeBranch) {
                $query->where('branch_name', $activeBranch['name']);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // 2. Fetch Products (CRITICAL: This fixes the "Product data not loaded" error)
        $productQuery = Product::with('category')->latest();
        $this->applyTenantScope($productQuery, 'products');
        $products = $productQuery->paginate(10);
            
        // 3. Pass BOTH variables to the view
        return view('Purchases.purchases', compact('purchases', 'products', 'activeBranch'));
    }

    /**
     * Specific Report Method
     */
    public function purchaseReport(Request $request)
    {
        $activeBranch = $this->getActiveBranchContext();
        $search = $request->input('search');

        $productQuery = Product::with('category');
        $this->applyTenantScope($productQuery, 'products');
        $products = $productQuery
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('sku', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        $purchaseQuery = Purchase::with(['supplier', 'items.product']);
        $this->applyTenantScope($purchaseQuery, 'purchases');
        $purchases = $purchaseQuery
            ->when(!empty($activeBranch['name']) && Schema::hasColumn('purchases', 'branch_name'), function ($query) use ($activeBranch) {
                $query->where('branch_name', $activeBranch['name']);
            })
            ->latest()
            ->paginate(10);

        return view('Purchases.purchases', [
            'products'  => $products,
            'purchases' => $purchases,
            'search'    => $search,
            'page'      => 'products',
            'activeBranch' => $activeBranch,
        ]);
    }

    /**
     * Show the form for creating a new purchase.
     */
    public function create()
    {
        $activeBranch = $this->getActiveBranchContext();
        $vendorsQuery = Vendor::orderBy('name');
        $this->applyTenantScope($vendorsQuery, 'vendors');
        $vendors = $vendorsQuery->get();
        $productsQuery = Product::orderBy('name');
        $this->applyTenantScope($productsQuery, 'products');
        $products = $productsQuery->get();
        $taxOptions = collect();
        if (class_exists(TaxCode::class) && Schema::hasTable('tax_codes')) {
            $taxOptions = TaxCode::orderBy('name')->get();
        }
        $banksQuery = Bank::orderBy('name');
        $this->applyTenantScope($banksQuery, 'banks');
        $banks = $banksQuery->get();
        
        // Generate a unique purchase ID
        $purchaseId = 'PUR-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        
        return view('Purchases.add-purchases', compact('vendors', 'products', 'taxOptions', 'banks', 'purchaseId', 'activeBranch'));
    }

    /**
     * Store a newly created purchase in storage.
     */
    public function store(Request $request)
    {
        $activeBranch = $this->getActiveBranchContext();
        // Validate the request (schema-safe)
        $validated = $request->validate([
            'purchase_id' => 'nullable|string|max:50',
            'purchase_no' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|exists:vendors,id',
            'supplier_id' => 'nullable|integer',
            'purchase_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:purchase_date',
            'reference_no' => 'nullable|string|max:50',
            'invoice_serial_no' => 'nullable|string|max:50',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.rate' => 'required|numeric|min:0',
            'products.*.unit' => 'nullable|string|max:20',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax_id' => 'nullable|exists:taxes,id',
            'discount_type' => 'in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'bank_id' => 'nullable|exists:banks,id',
            'notes' => 'nullable|string|max:500',
            'terms_conditions' => 'nullable|string',
            'round_off' => 'boolean',
            'signature_name' => 'nullable|string|max:100',
            'signature_image' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();
        
        try {
            // Handle signature image upload
            $signaturePath = null;
            if ($request->hasFile('signature_image')) {
                $signaturePath = $request->file('signature_image')->store('signatures', 'public');
            }

            // Calculate totals
            $totals = $this->calculateTotals($request);
            
            // Create purchase using actual purchases schema
            $purchaseNo = $validated['purchase_no']
                ?? $validated['purchase_id']
                ?? ('PUR-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)));

            if (Purchase::where('purchase_no', $purchaseNo)->exists()) {
                $purchaseNo = 'PUR-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(3));
            }

            $purchasePayload = [
                'branch_id' => $activeBranch['id'],
                'branch_name' => $activeBranch['name'],
                'purchase_no' => $purchaseNo,
                'total_amount' => $totals['total_amount'],
                'tax_amount' => $totals['vat_amount'],
                'status' => 'received',
            ];

            if (Schema::hasColumn('purchases', 'supplier_id')) {
                $purchasePayload['supplier_id'] = $validated['supplier_id'] ?? $validated['vendor_id'] ?? null;
            }
            if (Schema::hasColumn('purchases', 'vendor_id')) {
                $purchasePayload['vendor_id'] = $validated['vendor_id'] ?? null;
            }
            if (Schema::hasColumn('purchases', 'company_id')) {
                $purchasePayload['company_id'] = auth()->user()?->company_id ?: null;
            }
            if (Schema::hasColumn('purchases', 'user_id')) {
                $purchasePayload['user_id'] = auth()->id();
            }

            $purchase = Purchase::create($purchasePayload);

            // Create purchase items
            foreach ($request->products as $item) {
                $itemAmount = ($item['quantity'] * $item['rate']) - ($item['discount'] ?? 0);
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);
                $quantity = (float) $item['quantity'];

                $itemPayload = [
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'qty' => $quantity,
                    'unit_price' => (float) $item['rate'],
                ];

                // Optional compatibility if extra columns exist in a different schema
                if (Schema::hasColumn('purchase_items', 'quantity')) {
                    $itemPayload['quantity'] = (float) $item['quantity'];
                }
                if (Schema::hasColumn('purchase_items', 'rate')) {
                    $itemPayload['rate'] = (float) $item['rate'];
                }
                if (Schema::hasColumn('purchase_items', 'discount')) {
                    $itemPayload['discount'] = (float) ($item['discount'] ?? 0);
                }
                if (Schema::hasColumn('purchase_items', 'tax_id')) {
                    $itemPayload['tax_id'] = $item['tax_id'] ?? null;
                }
                if (Schema::hasColumn('purchase_items', 'amount')) {
                    $itemPayload['amount'] = $itemAmount;
                }

                PurchaseItem::create($itemPayload);
                $product->increment('stock', $quantity);
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $product->increment('stock_quantity', $quantity);
                }
                $this->branchInventory->adjustBranchStock(
                    $product,
                    $quantity,
                    $activeBranch,
                    (int) ($product->company_id ?? auth()->user()?->company_id ?? 0)
                );
            }

            LedgerService::postPurchase($purchase->fresh());

            DB::commit();
            
            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', 'Purchase created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Failed to create purchase: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase.
 * Display the specified purchase detail.
 *
 * @param  int  $id
 * @return \Illuminate\View\View
 */
public function show($id)
{
    // Fetch purchase with vendor relationship
    $purchase = $this->applyTenantScope(Purchase::with(['vendor', 'bank', 'items.product']), 'purchases')->findOrFail($id);
    $activeBranch = $this->getActiveBranchContext();

    // Logic: Look for the vendor's specific logo first. 
    // If the vendor doesn't have a logo, fall back to the site-wide logo.
    $vendorLogo = $purchase->vendor->logo ?? null;
    
    if (!$vendorLogo) {
        $siteSetting = \App\Models\Setting::where('key', 'site_logo')->first();
        $logo = $siteSetting ? $siteSetting->value : 'assets/img/logo.png';
    } else {
        $logo = $vendorLogo;
    }

    return view('Purchases.purchase-details', [
        'purchase' => $purchase,
        'logo'     => $logo,
        'page'     => 'purchase-details',
        'activeBranch' => $activeBranch,
    ]);
}


    /**
     * Generate PDF for a specific purchase
     */
    public function downloadPDF($id)
    {
        $purchase = $this->applyTenantScope(Purchase::with(['vendor', 'bank', 'items.product']), 'purchases')->findOrFail($id);
        
        // Use the dynamic logo logic we built
        $vendorLogo = $purchase->vendor->logo ?? null;
        $logo = $vendorLogo ?: (\App\Models\Setting::where('key', 'site_logo')->value('value') ?: 'assets/img/logo.png');

        // Load the view and pass the data
        $pdf = Pdf::loadView('Purchases.purchase-details', compact('purchase', 'logo'));
        
        // Return the file for download
        return $pdf->download('Purchase_'.$purchase->purchase_no.'.pdf');
    }

    /**
     * Export Purchase to Excel
     */
    public function exportExcel($id)
    {
        return Excel::download(new PurchaseExport($id), 'Purchase_Export_'.time().'.xlsx');
    }


    /**
     * Show the form for editing the specified purchase.
     */
    public function edit($id)
    {
        $activeBranch = $this->getActiveBranchContext();
        $purchase = $this->applyTenantScope(Purchase::with('items.product'), 'purchases')->findOrFail($id);
        $vendorsQuery = Vendor::orderBy('name');
        $this->applyTenantScope($vendorsQuery, 'vendors');
        $vendors = $vendorsQuery->get();
        $productsQuery = Product::orderBy('name');
        $this->applyTenantScope($productsQuery, 'products');
        $products = $productsQuery->get();
        $taxOptions = Tax::orderBy('name')->get();
        $banksQuery = Bank::orderBy('name');
        $this->applyTenantScope($banksQuery, 'banks');
        $banks = $banksQuery->get();
        
        return view('Purchases.edit-purchases', compact('purchase', 'vendors', 'products', 'taxOptions', 'banks', 'activeBranch'));
    }

    /**
     * Update the specified purchase in storage.
     */
    public function update(Request $request, $id)
    {
        $activeBranch = $this->getActiveBranchContext();
        $purchase = $this->applyTenantScope(Purchase::query(), 'purchases')->findOrFail($id);
        
        $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'supplier_id' => 'nullable|integer',
            'purchase_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:purchase_date',
            'reference_no' => 'nullable|string|max:50',
            'invoice_serial_no' => 'nullable|string|max:50',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.rate' => 'required|numeric|min:0',
            'products.*.unit' => 'nullable|string|max:20',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax_id' => 'nullable|exists:taxes,id',
            'discount_type' => 'in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'bank_id' => 'nullable|exists:banks,id',
            'notes' => 'nullable|string|max:500',
            'terms_conditions' => 'nullable|string',
            'round_off' => 'boolean',
            'signature_name' => 'nullable|string|max:100',
            'signature_image' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();
        
        try {
            // Handle signature image upload
            if ($request->hasFile('signature_image')) {
                // Delete old signature if exists
                if ($purchase->signature_image) {
                    Storage::disk('public')->delete($purchase->signature_image);
                }
                $signaturePath = $request->file('signature_image')->store('signatures', 'public');
                $validated['signature_image'] = $signaturePath;
            } else {
                $validated['signature_image'] = $purchase->signature_image;
            }

            // Calculate totals
            $totals = $this->calculateTotals($request);
            
            // Update purchase
            $purchasePayload = [
                'branch_id' => $activeBranch['id'],
                'branch_name' => $activeBranch['name'],
                'total_amount' => $totals['total_amount'],
                'tax_amount' => $totals['vat_amount'],
            ];
            if (Schema::hasColumn('purchases', 'supplier_id')) {
                $purchasePayload['supplier_id'] = $validated['supplier_id'] ?? $validated['vendor_id'] ?? null;
            }
            if (Schema::hasColumn('purchases', 'vendor_id')) {
                $purchasePayload['vendor_id'] = $validated['vendor_id'] ?? null;
            }
            $purchase->update($purchasePayload);

            $previousItems = $purchase->items()->get();
            foreach ($previousItems as $previousItem) {
                $previousProduct = Product::query()->lockForUpdate()->find($previousItem->product_id);
                if (!$previousProduct) {
                    continue;
                }

                $previousQty = (float) ($previousItem->qty ?? $previousItem->quantity ?? 0);
                if ($previousQty <= 0) {
                    continue;
                }

                $previousProduct->decrement('stock', $previousQty);
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $previousProduct->decrement('stock_quantity', $previousQty);
                }
                $this->branchInventory->adjustBranchStock(
                    $previousProduct,
                    -$previousQty,
                    $activeBranch,
                    (int) ($previousProduct->company_id ?? auth()->user()?->company_id ?? 0)
                );
            }

            // Delete old items and create new ones
            $purchase->items()->delete();
            
            foreach ($request->products as $item) {
                $itemAmount = ($item['quantity'] * $item['rate']) - ($item['discount'] ?? 0);
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);
                $quantity = (float) $item['quantity'];

                $itemPayload = [
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'qty' => $quantity,
                    'unit_price' => (float) $item['rate'],
                ];
                if (Schema::hasColumn('purchase_items', 'quantity')) {
                    $itemPayload['quantity'] = (float) $item['quantity'];
                }
                if (Schema::hasColumn('purchase_items', 'rate')) {
                    $itemPayload['rate'] = (float) $item['rate'];
                }
                if (Schema::hasColumn('purchase_items', 'discount')) {
                    $itemPayload['discount'] = (float) ($item['discount'] ?? 0);
                }
                if (Schema::hasColumn('purchase_items', 'tax_id')) {
                    $itemPayload['tax_id'] = $item['tax_id'] ?? null;
                }
                if (Schema::hasColumn('purchase_items', 'amount')) {
                    $itemPayload['amount'] = $itemAmount;
                }

                PurchaseItem::create($itemPayload);
                $product->increment('stock', $quantity);
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $product->increment('stock_quantity', $quantity);
                }
                $this->branchInventory->adjustBranchStock(
                    $product,
                    $quantity,
                    $activeBranch,
                    (int) ($product->company_id ?? auth()->user()?->company_id ?? 0)
                );
            }

            LedgerService::postPurchase($purchase->fresh());

            DB::commit();
            
            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', 'Purchase updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Failed to update purchase: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase from storage.
     */
    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // Delete signature image if exists
            if ($purchase->signature_image) {
                Storage::disk('public')->delete($purchase->signature_image);
            }

            $activeBranch = $this->getActiveBranchContext();
            foreach ($purchase->items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (!$product) {
                    continue;
                }

                $quantity = (float) ($item->qty ?? $item->quantity ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $product->decrement('stock', $quantity);
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $product->decrement('stock_quantity', $quantity);
                }
                $this->branchInventory->adjustBranchStock(
                    $product,
                    -$quantity,
                    [
                        'id' => $purchase->branch_id ?? $activeBranch['id'],
                        'name' => $purchase->branch_name ?? $activeBranch['name'],
                    ],
                    (int) ($product->company_id ?? auth()->user()?->company_id ?? 0)
                );
            }
            
            // Delete purchase items
            $purchase->items()->delete();

            Transaction::query()
                ->where('related_id', $purchase->id)
                ->where('related_type', Purchase::class)
                ->delete();
            
            // Delete purchase
            $purchase->delete();
            
            DB::commit();
            
            return redirect()->route('purchases.index')
                ->with('success', 'Purchase deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Failed to delete purchase: ' . $e->getMessage());
        }
    }

    /**
     * Calculate purchase totals.
     */
    private function calculateTotals(Request $request)
    {
        $taxableAmount = 0;
        $itemDiscounts = 0;
        
        // Calculate item totals
        foreach ($request->products as $item) {
            $quantity = $item['quantity'];
            $rate = $item['rate'];
            $discount = $item['discount'] ?? 0;
            
            $taxableAmount += $quantity * $rate;
            $itemDiscounts += $discount;
        }
        
        // Apply global discount
        $globalDiscount = 0;
        if ($request->discount_type === 'percentage') {
            $globalDiscount = ($taxableAmount * ($request->discount_value ?? 0)) / 100;
        } else {
            $globalDiscount = $request->discount_value ?? 0;
        }
        
        $totalDiscount = $itemDiscounts + $globalDiscount;
        
        // Calculate tax
        $vatAmount = 0;
        if ($request->tax_id) {
            if (class_exists(TaxCode::class) && Schema::hasTable('tax_codes')) {
                $tax = TaxCode::find($request->tax_id);
                if ($tax && isset($tax->rate)) {
                    $vatAmount = (($taxableAmount - $totalDiscount) * $tax->rate) / 100;
                }
            }
        }
        
        // Calculate final total
        $subtotal = $taxableAmount - $totalDiscount;
        $totalAmount = $subtotal + $vatAmount;
        $roundOffAmount = 0;
        
        // Apply round off
        if ($request->boolean('round_off')) {
            $roundedTotal = round($totalAmount);
            $roundOffAmount = $roundedTotal - $totalAmount;
            $totalAmount = $roundedTotal;
        }
        
        return [
            'taxable_amount' => round($taxableAmount, 2),
            'total_discount' => round($totalDiscount, 2),
            'vat_amount' => round($vatAmount, 2),
            'round_off_amount' => round($roundOffAmount, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    // ========== CUSTOM ROUTE ALIASES ==========

    /**
     * Alias for create() - for your custom route
     */
    public function addPurchases()
    {
        return $this->create();
    }

    /**
     * Alias for edit() - for your custom route
     */
    public function editPurchases($id)
    {
        return $this->edit($id);
    }

    /**
     * Show purchase details (alias for show)
     */
    public function purchaseDetails($id)
    {
        return $this->show($id);
    }

      // File: app/Http/Controllers/PurchaseController.php


    // ========== PURCHASE RETURNS / DEBIT NOTES ==========

    /**
     * Show form to create purchase return
     */
    public function createReturn()
    {
        // Check role-based access
        $allowedRoles = ['super_admin', 'administrator', 'store_manager', 'accountant'];

        if (!in_array(auth()->user()->role, $allowedRoles)) {
            return redirect()->back()->with('error', 'Unauthorized! Only authorized roles can process returns.');
        }

        $purchases = Purchase::with('supplier')->orderBy('created_at', 'desc')->get();
        return view('Reports.Reports.create-purchase-return', compact('purchases'));
    }

    /**
     * Get purchase items for a specific purchase (AJAX)
     */
    public function getPurchaseItems($id)
    {
        $items = DB::table('purchase_items')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->where('purchase_items.purchase_id', $id)
            ->select(
                'products.id as product_id', 
                'products.name', 
                'purchase_items.qty',
                'purchase_items.unit_price'
            )
            ->get();

        return response()->json($items);
    }

    /**
     * Store purchase return
     */
    public function storeReturn(Request $request)
    {
        // Validate
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'return_date' => 'nullable|date',
            'items' => 'required|array',
            'items.*.qty' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Calculate the total return amount
        $totalAmount = 0;
        foreach ($request->items as $item) {
            if (isset($item['qty']) && $item['qty'] > 0) {
                $totalAmount += ($item['qty'] * $item['unit_price']);
            }
        }

        if ($totalAmount <= 0) {
            return back()->with('error', 'Please enter a quantity for at least one item.');
        }

        // Get the purchase and vendor
        $purchase = Purchase::findOrFail($request->purchase_id);

        // Create the Purchase Return (Debit Note)
        $purchaseReturn = PurchaseReturn::create([
            'purchase_id' => $purchase->id,
            'vendor_id' => null,
            'return_no' => 'RTN-' . strtoupper(Str::random(8)),
            'amount' => $totalAmount,
            'reason' => $request->reason ?? 'Item Return',
            'created_at' => $request->return_date ?? now(),
        ]);

        LedgerService::postPurchaseReturn(
            relatedId: $purchaseReturn->id,
            amount: (float) $totalAmount,
            reference: $purchaseReturn->return_no,
            date: $request->return_date,
            userId: auth()->id(),
            relatedType: PurchaseReturn::class
        );

        return redirect()->route('debit-notes')->with('success', 'Return processed successfully!');
    }

    /**
     * Edit purchase return
     */
    public function editReturn($id)
    {
        $return = PurchaseReturn::with(['purchase', 'vendor'])->findOrFail($id);
        $purchases = Purchase::with('supplier')->orderBy('created_at', 'desc')->get();
        
        return view('Purchases.edit-purchase-return', compact('return', 'purchases'));
    }

    /**
     * List all debit notes (purchase returns)
     */
    public function debitNotes(Request $request)
    {
        $query = PurchaseReturn::with(['purchase', 'vendor']);

        // Filter by Return Number
        if ($request->filled('return_no')) {
            $query->where('return_no', 'like', '%' . $request->return_no . '%');
        }

        // Filter by Vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $debit_notes = $query->orderBy('created_at', 'desc')->paginate(15);
        $vendors = Vendor::orderBy('name', 'asc')->get();

        return view('Purchases.debit-notes', compact('debit_notes', 'vendors'));
    }

    // ========== PURCHASE ORDERS ==========

    /**
     * List purchase orders
     */
    public function purchaseOrders()
    {
        if (Schema::hasTable('purchase_orders')) {
            $orders = DB::table('purchase_orders')
                ->leftJoin('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                ->select([
                    'purchase_orders.id',
                    DB::raw("COALESCE(purchase_orders.purchase_id, CONCAT('PO-', purchase_orders.id)) as purchase_id"),
                    DB::raw("COALESCE(vendors.name, 'N/A') as vendor_name"),
                    DB::raw("COALESCE(purchase_orders.total_amount, 0) as total_amount"),
                    DB::raw("COALESCE(purchase_orders.payment_mode, 'N/A') as payment_mode"),
                    DB::raw("COALESCE(purchase_orders.status, 'Pending') as status"),
                    DB::raw("DATE_FORMAT(COALESCE(purchase_orders.created_at, NOW()), '%d %b %Y') as row_date"),
                ])
                ->orderByDesc('purchase_orders.id')
                ->paginate(10);
        } else {
            $orders = Purchase::with('vendor')
                ->orderByDesc('created_at')
                ->paginate(10);
        }

        $purchase_orders = $orders->getCollection()->map(function ($row, $index) {
            $id = (int) ($row->id ?? $row['id'] ?? 0);
            $vendorName = $row->vendor_name ?? $row->vendor?->name ?? 'N/A';
            $amount = (float) ($row->total_amount ?? $row->total ?? 0);
            $status = (string) ($row->status ?? 'Pending');

            $statusClass = strcasecmp($status, 'Received') === 0 || strcasecmp($status, 'Completed') === 0
                ? 'badge bg-success-light text-success'
                : (strcasecmp($status, 'Pending') === 0
                    ? 'badge bg-warning-light text-warning'
                    : 'badge bg-info-light text-info');

            return [
                'Id' => $id,
                'PurchaseID' => $row->purchase_id ?? $row->purchase_no ?? ('PO-' . $id),
                'Vendor' => $vendorName,
                'Phone' => $row->vendor?->phone ?? '',
                'Amount' => number_format($amount, 2),
                'PaymentMode' => $row->payment_mode ?? $row->payment_method ?? 'N/A',
                'Date' => $row->row_date ?? optional($row->created_at)->format('d M Y'),
                'Status' => $status,
                'Class' => $statusClass,
            ];
        });

        return view('Purchases.purchase-orders', [
            'purchase_orders' => $purchase_orders,
            'orders' => $orders,
        ]);
    }

    /**
     * Create purchase order
     */
    public function createOrder()
    {
        $vendors = Vendor::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        return view('Purchases.add-purchases-order', compact('vendors', 'products'));
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'nullable|string|max:100',
            'vendor_id' => 'nullable|exists:vendors,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $purchaseNo = $validated['purchase_id'] ?? ('PO-' . date('Ymd') . '-' . strtoupper(Str::random(5)));
        $qty = (float) ($validated['quantity'] ?? 0);
        $rate = (float) ($validated['rate'] ?? 0);
        $total = max(0, $qty * $rate);

        DB::beginTransaction();
        try {
            $purchase = new Purchase();
            $purchase->purchase_no = $purchaseNo;
            if (Schema::hasColumn('purchases', 'vendor_id')) {
                $purchase->vendor_id = $validated['vendor_id'] ?? null;
            }
            if (Schema::hasColumn('purchases', 'supplier_id')) {
                $purchase->supplier_id = $validated['supplier_id'] ?? ($validated['vendor_id'] ?? null);
            }
            if (Schema::hasColumn('purchases', 'purchase_date')) {
                $purchase->purchase_date = $validated['purchase_date'] ?? now()->toDateString();
            }
            if (Schema::hasColumn('purchases', 'reference_no')) {
                $purchase->reference_no = $validated['reference_no'] ?? null;
            }
            if (Schema::hasColumn('purchases', 'notes')) {
                $purchase->notes = $validated['notes'] ?? null;
            }
            $purchase->total_amount = $total;
            $purchase->status = 'pending';
            $purchase->save();

            if (!empty($validated['product_id']) && $qty > 0 && $rate >= 0) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $validated['product_id'],
                    'qty' => $qty,
                    'unit_price' => $rate,
                ]);
            }

            DB::commit();
            return redirect()->route('purchase-orders')->with('success', 'Purchase order created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create purchase order: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Edit purchase order
     */
    public function editOrder($id)
    {
        $vendors = Vendor::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $order = Purchase::with(['vendor', 'items'])->find($id);

        return view('Purchases.edit-purchases-order', compact('order', 'vendors', 'products'));
    }

    // ========== PURCHASE TRANSACTIONS (SUPER ADMIN) ==========

    /**
     * Show purchase transactions for super admin
     */
    public function purchase_transaction(Request $request)
    {
        $activeBranch = $this->getActiveBranchContext();
        $query = Purchase::with('vendor');

        if (!empty($activeBranch['name']) && Schema::hasColumn('purchases', 'branch_name')) {
            $query->where('branch_name', $activeBranch['name']);
        }

        // Search by purchase number
        if ($request->filled('search')) {
            $query->where('purchase_no', 'like', '%' . $request->search . '%')
                  ->orWhere('purchase_id', 'like', '%' . $request->search . '%');
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by vendor
        if ($request->filled('vendor_id') && Schema::hasColumn('purchases', 'vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        } elseif ($request->filled('vendor_id') && Schema::hasColumn('purchases', 'supplier_id')) {
            $query->where('supplier_id', $request->vendor_id);
        }

        $hasPurchaseRows = (clone $query)->exists();

        if ($hasPurchaseRows) {
            $purchasereports = $query->orderBy('created_at', 'desc')->paginate(15);
        } elseif (Schema::hasTable('inventory_history') && Schema::hasTable('products')) {
            $historyQuery = DB::table('inventory_history')
                ->join('products', 'inventory_history.product_id', '=', 'products.id')
                ->select([
                    'inventory_history.id as id',
                    DB::raw("CONCAT('HIST-IN-', inventory_history.id) as purchase_no"),
                    'inventory_history.created_at as created_at',
                    'inventory_history.branch_name as branch_name',
                    DB::raw('COALESCE(inventory_history.quantity, 0) * COALESCE(products.purchase_price, products.price, 0) as total_amount'),
                    DB::raw("'received' as status"),
                    DB::raw("'inventory_history' as source_type"),
                ])
                ->whereRaw("LOWER(COALESCE(inventory_history.type, '')) = 'in'");

            if (!empty($activeBranch['name']) && Schema::hasColumn('inventory_history', 'branch_name')) {
                $historyQuery->where('inventory_history.branch_name', $activeBranch['name']);
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $historyQuery->where(function ($q) use ($search) {
                    $q->where('products.name', 'like', '%' . $search . '%')
                        ->orWhere('products.sku', 'like', '%' . $search . '%')
                        ->orWhere('inventory_history.id', 'like', '%' . $search . '%');
                });
            }

            if ($request->filled('start_date')) {
                $historyQuery->whereDate('inventory_history.created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $historyQuery->whereDate('inventory_history.created_at', '<=', $request->end_date);
            }

            $purchasereports = $historyQuery->orderByDesc('inventory_history.created_at')->paginate(15);
        } else {
            $purchasereports = $query->orderBy('created_at', 'desc')->paginate(15);
        }
        $vendors = Vendor::orderBy('name')->get();

        return view('SuperAdmin.purchase-transaction', compact('purchasereports', 'vendors', 'activeBranch'));
    }

}
