<?php

namespace App\Http\Controllers;

use App\Models\ProductBarcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarcodeController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $barcodes  = ProductBarcode::where('company_id', $companyId)
            ->with('product')
            ->latest()
            ->paginate(25);

        return view('inventory.barcodes.index', compact('barcodes'));
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

        ProductBarcode::create([
            'company_id'   => $companyId,
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
