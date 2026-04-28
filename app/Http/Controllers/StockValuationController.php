<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StockValuationController extends Controller
{
    /**
     * Show the stock valuation report.
     * Supports FIFO and Weighted Average costing methods.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $method    = $request->input('method', 'weighted_avg'); // fifo|weighted_avg
        $asOf      = $request->input('as_of', now()->toDateString());
        $branchId  = $request->input('branch_id');

        $quantityColumn = Schema::hasColumn('products', 'quantity')
            ? 'quantity'
            : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : (Schema::hasColumn('products', 'stock') ? 'stock' : null));
        $unitCostColumn = Schema::hasColumn('products', 'purchase_price')
            ? 'purchase_price'
            : (Schema::hasColumn('products', 'cost_price') ? 'cost_price' : (Schema::hasColumn('products', 'cost') ? 'cost' : 'price'));

        $rows = collect();
        $grandTotal = 0;

        if ($quantityColumn) {
            $query = Product::where('company_id', $companyId)->orderBy('name');

            if ($branchId && Schema::hasColumn('products', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }

            $products = $query->get();

            $rows = $products->map(function ($product) use ($quantityColumn, $unitCostColumn) {
                $quantity = (float) ($product->{$quantityColumn} ?? 0);
                $unitCost = (float) ($product->{$unitCostColumn} ?? 0);

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $quantity * $unitCost,
                ];
            })->filter(fn ($row) => $row['quantity'] > 0)->values();

            $grandTotal = (float) $rows->sum('total');
        }

        return view('inventory.stock-valuation', compact('rows', 'grandTotal', 'method', 'asOf'));
    }
}
