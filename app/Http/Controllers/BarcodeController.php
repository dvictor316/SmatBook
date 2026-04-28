<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBarcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarcodeController extends Controller
{
    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

        if (!$branchId && !$branchName && \Schema::hasTable('settings')) {
            $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
            if ($companyId > 0) {
                $key = 'branches_json_company_' . $companyId;
                $raw = (string) (\DB::table('settings')->where('key', $key)->value('value') ?? '');
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
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $barcodes  = ProductBarcode::where('company_id', $companyId)
            ->with('product')
            ->latest()
            ->paginate(25);
        $products = Product::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('inventory.barcodes.index', compact('barcodes', 'products'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'barcode'      => 'required|string|max:255',
            'barcode_type' => 'nullable|in:EAN13,EAN8,UPC,QR,CODE128,CODE39',
            'is_primary'   => 'boolean',
        ]);

        $branch = $this->getActiveBranchContext();
        ProductBarcode::create([
            'company_id'   => $companyId,
            'branch_id'    => $branch['id'],
            'branch_name'  => $branch['name'],
            'product_id'   => $data['product_id'],
            'barcode'      => $data['barcode'],
            'barcode_type' => $data['barcode_type'] ?? 'EAN13',
            'is_primary'   => $request->boolean('is_primary', false),
        ]);

        return back()->with('success', 'Barcode added.');
    }

    public function lookup(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $barcode   = $request->validate(['barcode' => 'required|string'])['barcode'];

        $record = ProductBarcode::where('company_id', $companyId)
            ->where('barcode', $barcode)
            ->with('product')
            ->first();

        if (! $record) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found'      => true,
            'product_id' => $record->product_id,
            'product'    => $record->product?->only(['id', 'name', 'selling_price', 'sku']),
        ]);
    }

    public function destroy(ProductBarcode $productBarcode)
    {
        abort_unless($productBarcode->company_id === Auth::user()->company_id, 403);
        $productBarcode->delete();
        return back()->with('success', 'Barcode removed.');
    }
}
