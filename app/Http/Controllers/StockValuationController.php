<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockValuationController extends Controller
{
    /**
     * Show the stock valuation report.
     * Supports FIFO and Weighted Average costing methods.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = (int) ($user?->company_id ?? session('current_tenant_id') ?? 0);
        $method = $request->input('method', 'weighted_avg'); // fifo|weighted_avg
        $asOf = $request->input('as_of', now()->toDateString());
        $branchId = (string) ($request->input('branch_id') ?: session('active_branch_id') ?: '');
        $branchName = (string) session('active_branch_name', '');

        $stockColumn = Schema::hasColumn('products', 'stock')
            ? 'stock'
            : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : (Schema::hasColumn('products', 'quantity') ? 'quantity' : null));
        $unitCostColumn = Schema::hasColumn('products', 'purchase_price')
            ? 'purchase_price'
            : (Schema::hasColumn('products', 'cost_price') ? 'cost_price' : (Schema::hasColumn('products', 'cost') ? 'cost' : 'price'));
        $hasBranchStocks = Schema::hasTable('product_branch_stocks');
        $hasBranchId = $hasBranchStocks && Schema::hasColumn('product_branch_stocks', 'branch_id');
        $hasBranchName = $hasBranchStocks && Schema::hasColumn('product_branch_stocks', 'branch_name');

        $rows = collect();
        $grandTotal = 0;

        if ($stockColumn || $hasBranchStocks) {
            $stockExpr = $stockColumn ? "products.{$stockColumn}" : '0';
            $costExpr = Schema::hasColumn('products', $unitCostColumn) ? "products.{$unitCostColumn}" : '0';

            $query = Product::query()
                ->select([
                    'products.*',
                    DB::raw("COALESCE({$costExpr}, 0) as valuation_unit_cost"),
                ])
                ->when($companyId > 0 && Schema::hasColumn('products', 'company_id'), function ($query) use ($companyId, $user) {
                    $query->where(function ($sub) use ($companyId, $user) {
                        $sub->where('products.company_id', $companyId);

                        if ($user && Schema::hasColumn('products', 'user_id')) {
                            $sub->orWhere(function ($fallback) use ($user) {
                                $fallback->whereNull('products.company_id')
                                    ->where('products.user_id', $user->id);
                            });
                        }
                    });
                })
                ->when($companyId <= 0 && $user && Schema::hasColumn('products', 'user_id'), fn ($query) => $query->where('products.user_id', $user->id));

            if ($hasBranchStocks && ($branchId !== '' || $branchName !== '')) {
                $query->leftJoin('product_branch_stocks', function ($join) use ($branchId, $branchName, $hasBranchId, $hasBranchName) {
                    $join->on('product_branch_stocks.product_id', '=', 'products.id');

                    if ($branchId !== '' && $hasBranchId) {
                        $join->where('product_branch_stocks.branch_id', '=', $branchId);
                    } elseif ($branchName !== '' && $hasBranchName) {
                        $join->where('product_branch_stocks.branch_name', '=', $branchName);
                    }
                });

                $query->addSelect(DB::raw("COALESCE(product_branch_stocks.quantity, {$stockExpr}, 0) as valuation_quantity"));
            } else {
                $query->addSelect(DB::raw("COALESCE({$stockExpr}, 0) as valuation_quantity"));
            }

            $rows = $query
                ->orderBy('products.name')
                ->get()
                ->map(function ($product) {
                    $quantity = max(0, (float) ($product->valuation_quantity ?? 0));
                    $unitCost = max(0, (float) ($product->valuation_unit_cost ?? 0));

                    return [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'total' => $quantity * $unitCost,
                    ];
                })->filter(fn ($row) => $row['quantity'] > 0)->values();

            $grandTotal = (float) $rows->sum('total');
        }

        return view('Inventory.stock-valuation', compact('rows', 'grandTotal', 'method', 'asOf'));
    }
}
