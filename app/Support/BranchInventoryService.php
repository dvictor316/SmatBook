<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductBranchStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchInventoryService
{
    public function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    public function getAvailableStock(Product $product, ?array $branch = null): float
    {
        $branch = $branch ?: $this->getActiveBranchContext();
        $savedStock = (float) ($product->stock ?? $product->stock_quantity ?? 0);

        if (!Schema::hasTable('product_branch_stocks') || empty($branch['id'])) {
            if ($savedStock !== 0.0) {
                return $savedStock;
            }

            return (float) ($this->calculateTransactionalStock($product, $branch) ?? 0);
        }

        $branchStock = $product->relationLoaded('branchStocks')
            ? $product->branchStocks->firstWhere('branch_id', (string) $branch['id'])
            : $product->branchStocks()->where('branch_id', (string) $branch['id'])->first();

        if ($branchStock) {
            $branchQuantity = (float) ($branchStock->quantity ?? 0);

            if ($branchQuantity === 0.0 && $savedStock > 0.0) {
                return $savedStock;
            }

            return $branchQuantity;
        }

        if ($savedStock !== 0.0) {
            return $savedStock;
        }

        $transactionalStock = $this->calculateTransactionalStock($product, $branch);

        if ($transactionalStock !== null) {
            return $transactionalStock;
        }

        return $savedStock;
    }

    private function calculateTransactionalStock(Product $product, ?array $branch = null): ?float
    {
        if (
            !Schema::hasTable('products')
            || (
                !Schema::hasTable('inventory_history')
                && !Schema::hasTable('purchase_items')
                && !Schema::hasTable('sale_items')
            )
        ) {
            return null;
        }

        $branch = $branch ?: $this->getActiveBranchContext();
        $historyTotal = $this->inventoryHistoryNet($product, $branch);
        $purchaseTotal = $this->purchaseNet($product, $branch);
        $saleTotal = $this->saleNet($product, $branch);

        return round($historyTotal + $purchaseTotal - $saleTotal, 2);
    }

    private function inventoryHistoryNet(Product $product, array $branch): float
    {
        if (!Schema::hasTable('inventory_history')) {
            return 0.0;
        }

        $branchId = trim((string) ($branch['id'] ?? ''));
        $branchName = trim((string) ($branch['name'] ?? ''));

        $query = DB::table('inventory_history')
            ->where('product_id', $product->id);

        if (Schema::hasColumn('inventory_history', 'company_id') && !empty($product->company_id)) {
            $query->where('company_id', $product->company_id);
        }

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($branchId, $branchName) {
                $matched = false;

                if ($branchId !== '' && Schema::hasColumn('inventory_history', 'branch_id')) {
                    $sub->where('inventory_history.branch_id', $branchId);
                    $matched = true;
                }

                if ($branchName !== '' && Schema::hasColumn('inventory_history', 'branch_name')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}('inventory_history.branch_name', $branchName);
                    $matched = true;
                }

                if (Schema::hasColumn('inventory_history', 'branch_id') || Schema::hasColumn('inventory_history', 'branch_name')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}(function ($fallback) {
                        if (Schema::hasColumn('inventory_history', 'branch_id')) {
                            $fallback->whereNull('inventory_history.branch_id');
                        }
                        if (Schema::hasColumn('inventory_history', 'branch_name')) {
                            $fallback->whereNull('inventory_history.branch_name');
                        }
                    });
                }
            });
        }

        return (float) $query->sum(DB::raw("
            CASE
                WHEN LOWER(COALESCE(type, '')) IN ('out', 'stock out') THEN -1 * COALESCE(quantity, 0)
                ELSE COALESCE(quantity, 0)
            END
        "));
    }

    private function purchaseNet(Product $product, array $branch): float
    {
        if (!Schema::hasTable('purchase_items') || !Schema::hasTable('purchases')) {
            return 0.0;
        }

        $qtyColumn = Schema::hasColumn('purchase_items', 'qty')
            ? 'qty'
            : (Schema::hasColumn('purchase_items', 'quantity') ? 'quantity' : null);

        if ($qtyColumn === null) {
            return 0.0;
        }

        $query = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.product_id', $product->id);

        if (Schema::hasColumn('purchases', 'purchase_no')) {
            $query->where(function ($sub) {
                $sub->whereNull('purchases.purchase_no')
                    ->orWhere('purchases.purchase_no', 'not like', 'AUTO-STK-%');
            });
        }

        if (Schema::hasColumn('purchases', 'company_id') && !empty($product->company_id)) {
            $query->where('purchases.company_id', $product->company_id);
        }

        $this->applyDualTableBranchScope($query, 'purchase_items', 'purchases', $branch);

        return (float) $query->sum("purchase_items.{$qtyColumn}");
    }

    private function saleNet(Product $product, array $branch): float
    {
        if (!Schema::hasTable('sale_items') || !Schema::hasTable('sales')) {
            return 0.0;
        }

        $qtyColumn = Schema::hasColumn('sale_items', 'qty')
            ? 'qty'
            : (Schema::hasColumn('sale_items', 'quantity') ? 'quantity' : null);

        if ($qtyColumn === null) {
            return 0.0;
        }

        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sale_items.product_id', $product->id);

        if (Schema::hasColumn('sales', 'company_id') && !empty($product->company_id)) {
            $query->where('sales.company_id', $product->company_id);
        }

        $this->applyDualTableBranchScope($query, 'sale_items', 'sales', $branch);

        return (float) $query->sum(DB::raw(InventoryQuantity::saleStockUnitsExpression('sale_items', 'products')));
    }

    private function applySingleTableBranchScope($query, string $table, array $branch): void
    {
        $branchId = trim((string) ($branch['id'] ?? ''));
        $branchName = trim((string) ($branch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($sub) use ($table, $branchId, $branchName) {
            $matched = false;

            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
                $matched = true;
            }

            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $method = $matched ? 'orWhere' : 'where';
                $sub->{$method}("{$table}.branch_name", $branchName);
            }
        });
    }

    private function applyDualTableBranchScope($query, string $itemTable, string $parentTable, array $branch): void
    {
        $branchId = trim((string) ($branch['id'] ?? ''));
        $branchName = trim((string) ($branch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($sub) use ($itemTable, $parentTable, $branchId, $branchName) {
            $matched = false;

            if ($branchId !== '') {
                if (Schema::hasColumn($itemTable, 'branch_id')) {
                    $sub->where("{$itemTable}.branch_id", $branchId);
                    $matched = true;
                }
                if (Schema::hasColumn($parentTable, 'branch_id')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}("{$parentTable}.branch_id", $branchId);
                    $matched = true;
                }
            }

            if ($branchName !== '') {
                if (Schema::hasColumn($itemTable, 'branch_name')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}("{$itemTable}.branch_name", $branchName);
                    $matched = true;
                }
                if (Schema::hasColumn($parentTable, 'branch_name')) {
                    $method = $matched ? 'orWhere' : 'where';
                    $sub->{$method}("{$parentTable}.branch_name", $branchName);
                }
            }
        });
    }

    public function adjustBranchStock(Product|int $product, float $delta, ?array $branch = null, ?int $companyId = null): ?ProductBranchStock
    {
        $branch = $branch ?: $this->getActiveBranchContext();

        if (!Schema::hasTable('product_branch_stocks') || empty($branch['id'])) {
            return null;
        }

        $productModel = $product instanceof Product ? $product : Product::query()->findOrFail($product);
        $resolvedCompanyId = $companyId ?? (int) ($productModel->company_id ?? auth()->user()?->company_id ?? 0);

        $branchStock = ProductBranchStock::query()->firstOrNew([
            'product_id' => $productModel->id,
            'branch_id' => (string) $branch['id'],
        ]);
        $seededFromCurrentProductStock = false;

        if (!$branchStock->exists) {
            // Callers update product stock first, then sync the branch row.
            // Seed a brand-new branch row from the already-updated product stock
            // so we do not apply the same delta twice.
            $branchStock->quantity = round((float) ($productModel->stock ?? $productModel->stock_quantity ?? 0), 2);
            $seededFromCurrentProductStock = true;
        } elseif (((float) ($branchStock->quantity ?? 0)) === 0.0) {
            $productStock = (float) ($productModel->stock ?? $productModel->stock_quantity ?? 0);
            if ($productStock !== 0.0) {
                // Same rule here: quantity zero plus a non-zero product stock usually
                // means the branch row needs a reset to the current product stock,
                // not another delta layered on top of it.
                $branchStock->quantity = round($productStock, 2);
                $seededFromCurrentProductStock = true;
            }
        }

        $branchStock->company_id = $resolvedCompanyId > 0 ? $resolvedCompanyId : null;
        $branchStock->branch_name = $branch['name'];
        if (!$seededFromCurrentProductStock) {
            $branchStock->quantity = round(((float) ($branchStock->quantity ?? 0)) + $delta, 2);
        }
        $branchStock->save();

        return $branchStock;
    }

    public function calculateBranchStock(Product|int $product, ?array $branch = null): ?float
    {
        $productModel = $product instanceof Product ? $product : Product::query()->findOrFail($product);
        $branch = $branch ?: $this->getActiveBranchContext();

        return $this->calculateTransactionalStock($productModel, $branch);
    }

    public function setBranchStock(Product|int $product, float $quantity, ?array $branch = null, ?int $companyId = null): ?ProductBranchStock
    {
        $branch = $branch ?: $this->getActiveBranchContext();

        if (!Schema::hasTable('product_branch_stocks') || empty($branch['id'])) {
            return null;
        }

        $productModel = $product instanceof Product ? $product : Product::query()->findOrFail($product);
        $resolvedCompanyId = $companyId ?? (int) ($productModel->company_id ?? auth()->user()?->company_id ?? 0);

        $branchStock = ProductBranchStock::query()->firstOrNew([
            'product_id' => $productModel->id,
            'branch_id' => (string) $branch['id'],
        ]);

        $branchStock->company_id = $resolvedCompanyId > 0 ? $resolvedCompanyId : null;
        $branchStock->branch_name = $branch['name'];
        $branchStock->quantity = round($quantity, 2);
        $branchStock->save();

        return $branchStock;
    }

    public function seedOpeningStock(Product $product, float $quantity, ?array $branch = null, ?int $companyId = null): ?ProductBranchStock
    {
        if ($quantity <= 0) {
            return null;
        }

        $branch = $branch ?: $this->getActiveBranchContext();

        if (empty($branch['id']) || !Schema::hasTable('product_branch_stocks')) {
            return null;
        }

        $existing = ProductBranchStock::query()
            ->where('product_id', $product->id)
            ->where('branch_id', (string) $branch['id'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->adjustBranchStock($product, $quantity, $branch, $companyId);
    }

    public function backfillMissingBranchStocks(?array $branch = null, ?int $companyId = null): int
    {
        $branch = $branch ?: $this->getActiveBranchContext();
        $branchId = trim((string) ($branch['id'] ?? ''));
        $branchName = trim((string) ($branch['name'] ?? ''));

        if ($branchId === '' || !Schema::hasTable('product_branch_stocks') || !Schema::hasTable('products')) {
            return 0;
        }

        $stockColumn = Schema::hasColumn('products', 'stock')
            ? 'stock'
            : (Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : null);

        if ($stockColumn === null) {
            return 0;
        }

        $created = 0;

        Product::query()
            ->when(
                $companyId && Schema::hasColumn('products', 'company_id'),
                fn (Builder $query) => $query->where('company_id', $companyId)
            )
            ->where($stockColumn, '!=', 0)
            ->whereDoesntHave('branchStocks', fn (Builder $query) => $query->where('branch_id', $branchId))
            ->where(function (Builder $query) use ($branchId, $branchName) {
                $query->doesntHave('branchStocks');

                if (Schema::hasColumn('products', 'branch_id')) {
                    $query->orWhere('branch_id', $branchId);
                }

                if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                    $query->orWhere('branch_name', $branchName);
                }
            })
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($branch, $companyId, &$created, $stockColumn) {
                foreach ($products as $product) {
                    $stock = (float) ($product->{$stockColumn} ?? 0);
                    if ($stock <= 0) {
                        continue;
                    }

                    $existing = ProductBranchStock::query()
                        ->where('product_id', $product->id)
                        ->where('branch_id', (string) ($branch['id'] ?? ''))
                        ->exists();

                    if ($existing) {
                        continue;
                    }

                    ProductBranchStock::query()->create([
                        'product_id' => $product->id,
                        'company_id' => $companyId ?: ($product->company_id ?? null),
                        'branch_id' => (string) ($branch['id'] ?? ''),
                        'branch_name' => $branch['name'] ?? null,
                        'quantity' => $stock,
                    ]);

                    $created++;
                }
            });

        return $created;
    }
}
