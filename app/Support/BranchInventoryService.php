<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductBranchStock;
use Illuminate\Database\Eloquent\Builder;
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

        if (!Schema::hasTable('product_branch_stocks') || empty($branch['id'])) {
            return (float) ($product->stock ?? $product->stock_quantity ?? 0);
        }

        $branchStock = $product->relationLoaded('branchStocks')
            ? $product->branchStocks->firstWhere('branch_id', (string) $branch['id'])
            : $product->branchStocks()->where('branch_id', (string) $branch['id'])->first();

        if ($branchStock) {
            return (float) $branchStock->quantity;
        }

        return (float) ($product->stock ?? $product->stock_quantity ?? 0);
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

        $branchStock->company_id = $resolvedCompanyId > 0 ? $resolvedCompanyId : null;
        $branchStock->branch_name = $branch['name'];
        $branchStock->quantity = round(max(0, ((float) ($branchStock->quantity ?? 0)) + $delta), 2);
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
            ->where($stockColumn, '>', 0)
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
