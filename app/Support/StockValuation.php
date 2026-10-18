<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockValuation
{
    public function rowsQuery(int $companyId, int $userId, array $branch): ?Builder
    {
        if (!Schema::hasTable('products')) {
            return null;
        }

        $stockColumn = collect(['stock', 'stock_quantity', 'quantity'])
            ->first(fn (string $column) => Schema::hasColumn('products', $column));
        $hasBranchStocks = Schema::hasTable('product_branch_stocks')
            && Schema::hasColumn('product_branch_stocks', 'quantity');

        if (!$stockColumn && !$hasBranchStocks) {
            return null;
        }

        $stockExpression = $stockColumn ? "products.{$stockColumn}" : '0';
        $costColumns = collect(['purchase_price', 'cost_price'])
            ->filter(fn (string $column) => Schema::hasColumn('products', $column))
            ->map(fn (string $column) => "NULLIF(products.{$column}, 0)")
            ->values()
            ->all();
        $costExpression = 'COALESCE(' . implode(', ', [...$costColumns, '0']) . ')';
        $quantityExpression = "COALESCE({$stockExpression}, 0)";

        $query = DB::table('products')->select('products.*');
        if ($companyId > 0 && Schema::hasColumn('products', 'company_id')) {
            $query->where('products.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('products', 'user_id')) {
            $query->where('products.user_id', $userId);
        }
        if (Schema::hasColumn('products', 'deleted_at')) {
            $query->whereNull('products.deleted_at');
        }

        if ($hasBranchStocks && ($branch['scope'] ?? 'branch') === 'all') {
            $branchTotals = DB::table('product_branch_stocks')
                ->select('product_id')
                ->selectRaw('SUM(COALESCE(quantity, 0)) as quantity')
                ->groupBy('product_id');
            $query->leftJoinSub($branchTotals, 'branch_totals', 'branch_totals.product_id', '=', 'products.id');
            $quantityExpression = "COALESCE(branch_totals.quantity, {$stockExpression}, 0)";
        } elseif ($hasBranchStocks && (!empty($branch['id']) || !empty($branch['name']))) {
            $branchId = (string) ($branch['id'] ?? '');
            $branchName = (string) ($branch['name'] ?? '');
            $hasBranchId = Schema::hasColumn('product_branch_stocks', 'branch_id');
            $hasBranchName = Schema::hasColumn('product_branch_stocks', 'branch_name');

            if (($branchId !== '' && $hasBranchId) || ($branchName !== '' && $hasBranchName)) {
                $query->leftJoin('product_branch_stocks', function ($join) use ($branchId, $branchName, $hasBranchId) {
                    $join->on('product_branch_stocks.product_id', '=', 'products.id');
                    if ($branchId !== '' && $hasBranchId) {
                        $join->where('product_branch_stocks.branch_id', $branchId);
                    } else {
                        $join->where('product_branch_stocks.branch_name', $branchName);
                    }
                });
                $quantityExpression = "COALESCE(product_branch_stocks.quantity, {$stockExpression}, 0)";
            }
        }

        return $query
            ->selectRaw("{$quantityExpression} as qty, {$costExpression} as unit_cost, ({$quantityExpression}) * ({$costExpression}) as total_value")
            ->whereRaw("{$quantityExpression} > 0");
    }

    public function summary(?Builder $query): array
    {
        if (!$query) {
            return ['quantity' => 0.0, 'value' => 0.0];
        }

        $row = DB::query()
            ->fromSub($query, 'stock_valuation')
            ->selectRaw('COALESCE(SUM(qty), 0) as quantity, COALESCE(SUM(total_value), 0) as value')
            ->first();

        return ['quantity' => (float) $row->quantity, 'value' => (float) $row->value];
    }
}
