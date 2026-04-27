<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLot;
use App\Models\SerialNumber;
use App\Models\ProductBarcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LotTrackingController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query     = ProductLot::forCompany($companyId)->with('product');

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $lots     = $query->latest()->paginate(25);
        $products = Product::where('company_id', $companyId)
            ->where('track_lots', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inventory.lots.index', compact('lots', 'products'));
    }

    public function show(ProductLot $productLot)
    {
        abort_unless($productLot->company_id === Auth::user()->company_id, 403);
        $productLot->load(['product', 'serials']);
        return view('inventory.lots.show', compact('productLot'));
    }

    public function adjust(Request $request, ProductLot $productLot)
    {
        abort_unless($productLot->company_id === Auth::user()->company_id, 403);

        $data = $request->validate([
            'adjustment'  => 'required|numeric',
            'reason'      => 'required|string|max:255',
        ]);

        $newQty = $productLot->quantity + $data['adjustment'];
        abort_if($newQty < 0, 422, 'Quantity cannot go below zero.');

        $productLot->update(['quantity' => $newQty]);

        return back()->with('success', 'Lot quantity adjusted.');
    }
}

class SerialNumberController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query     = SerialNumber::where('company_id', $companyId)->with('product');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $serials  = $query->latest()->paginate(25);
        $products = Product::where('company_id', $companyId)
            ->where('track_serials', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inventory.serials.index', compact('serials', 'products'));
    }

    public function show(SerialNumber $serialNumber)
    {
        abort_unless($serialNumber->company_id === Auth::user()->company_id, 403);
        $serialNumber->load('product');
        return view('inventory.serials.show', compact('serialNumber'));
    }
}

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
            'product_id'      => 'required|exists:products,id',
            'barcode'         => 'required|string|max:255',
            'barcode_type'    => 'nullable|in:EAN13,EAN8,UPC,QR,CODE128,CODE39',
            'is_primary'      => 'boolean',
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
