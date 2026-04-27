<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLot;
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
            'adjustment' => 'required|numeric',
            'reason'     => 'required|string|max:255',
        ]);

        $newQty = $productLot->quantity + $data['adjustment'];
        abort_if($newQty < 0, 422, 'Quantity cannot go below zero.');

        $productLot->update(['quantity' => $newQty]);

        return back()->with('success', 'Lot quantity adjusted.');
    }
}
