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
        $search = trim((string) $request->input('q', ''));
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

        $rows = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        $productCount = 0;
        $totalQuantity = 0;
        $grandTotal = 0;

        if ($stockColumn || $hasBranchStocks) {
            $stockExpr = $stockColumn ? "products.{$stockColumn}" : '0';
            $costExpr = Schema::hasColumn('products', $unitCostColumn) ? "products.{$unitCostColumn}" : '0';
            $quantityExpr = "COALESCE({$stockExpr}, 0)";

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

                $quantityExpr = "COALESCE(product_branch_stocks.quantity, {$stockExpr}, 0)";
            }
            $query->addSelect(DB::raw("{$quantityExpr} as valuation_quantity"));

            $query->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('products.name', 'like', '%' . $search . '%')
                        ->orWhere('products.sku', 'like', '%' . $search . '%');
                });
            })
                ->whereRaw("{$quantityExpr} > 0");

            $summary = (clone $query)
                ->select([])
                ->selectRaw("COUNT(DISTINCT products.id) as product_count, COALESCE(SUM({$quantityExpr}), 0) as total_quantity, COALESCE(SUM({$quantityExpr} * COALESCE({$costExpr}, 0)), 0) as grand_total")
                ->first();

            $productCount = (int) ($summary->product_count ?? 0);
            $totalQuantity = (float) ($summary->total_quantity ?? 0);
            $grandTotal = (float) ($summary->grand_total ?? 0);

            $rows = $query
                ->orderBy('products.name')
                ->paginate(25)
                ->appends($request->query());

            $rows->setCollection($rows->getCollection()->map(function ($product) {
                    $quantity = max(0, (float) ($product->valuation_quantity ?? 0));
                    $unitCost = max(0, (float) ($product->valuation_unit_cost ?? 0));

                    return [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'total' => $quantity * $unitCost,
                    ];
                }));
        }

        return view('Inventory.stock-valuation', compact(
            'rows', 'grandTotal', 'productCount', 'totalQuantity', 'method', 'asOf', 'search'
        ));
    }
}
