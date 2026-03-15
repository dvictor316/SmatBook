<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductBranchStock;
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
}
