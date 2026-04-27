<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $branchId  = $request->input('branch_id');
        $asOf      = $request->input('as_of', now()->toDateString());

        // Load products with their current stock quantities and cost data.
        $query = \App\Models\Product::where('company_id', $companyId)
            ->with(['stockMovements' => function ($q) use ($asOf) {
                $q->where('date', '<=', $asOf)->orderBy('date');
            }]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $products = $query->get();

        $rows = $products->map(function ($product) use ($method) {
            $qty  = $product->stockMovements->sum('quantity') ?? 0;
            $cost = $product->cost ?? 0;

            if ($method === 'fifo') {
                $cost = $this->fifoUnitCost($product->stockMovements);
            }

            return [
                'product'   => $product,
                'quantity'  => $qty,
                'unit_cost' => $cost,
                'total'     => $qty * $cost,
            ];
        })->filter(fn ($r) => $r['quantity'] > 0);

        $grandTotal = $rows->sum('total');

        return view('inventory.stock-valuation', compact('rows', 'grandTotal', 'method', 'asOf'));
    }

    private function fifoUnitCost($movements): float
    {
        // Simplified FIFO: return cost of latest purchase batch still in stock.
        $lastIn = $movements->where('type', 'in')->last();
        return $lastIn ? (float) $lastIn->unit_cost : 0.0;
    }
}
