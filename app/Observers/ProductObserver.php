<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductObserver
{
    // Automatically logs when a NEW product is added
    public function created(Product $product)
    {
        $payload = [
            'product_id' => $product->id,
            'quantity'   => $product->stock ?? 0,
            'type'       => 'in',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('inventory_history', 'reference')) {
            $payload['reference'] = 'Initial Stock';
        }
        if (Schema::hasColumn('inventory_history', 'user_id')) {
            $payload['user_id'] = auth()->id() ?? (int) DB::table('users')->min('id');
        }
        if (Schema::hasColumn('inventory_history', 'company_id')) {
            $payload['company_id'] = $product->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id');
        }
        if (Schema::hasColumn('inventory_history', 'branch_id')) {
            $payload['branch_id'] = $product->branch_id ?? session('active_branch_id');
        }
        if (Schema::hasColumn('inventory_history', 'branch_name')) {
            $payload['branch_name'] = $product->branch_name ?? session('active_branch_name');
        }

        $historyId = DB::table('inventory_history')->insertGetId($payload);

        if ((float) ($product->stock ?? 0) > 0) {
            $this->mirrorStockInToPurchase($product, (float) $product->stock, (int) $historyId);
        }
    }

    // Automatically logs when STOCK changes on an existing product
    public function updated(Product $product)
    {
        if ($product->isDirty('stock')) {
            $oldStock = $product->getOriginal('stock');
            $newStock = $product->stock;
            $diff = $newStock - $oldStock;

            $payload = [
                'product_id' => $product->id,
                'quantity'   => abs($diff),
                'type'       => $diff > 0 ? 'in' : 'out',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('inventory_history', 'reference')) {
                $payload['reference'] = 'Stock Update';
            }
            if (Schema::hasColumn('inventory_history', 'user_id')) {
                $payload['user_id'] = auth()->id() ?? (int) DB::table('users')->min('id');
            }
            if (Schema::hasColumn('inventory_history', 'company_id')) {
                $payload['company_id'] = $product->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id');
            }
            if (Schema::hasColumn('inventory_history', 'branch_id')) {
                $payload['branch_id'] = $product->branch_id ?? session('active_branch_id');
            }
            if (Schema::hasColumn('inventory_history', 'branch_name')) {
                $payload['branch_name'] = $product->branch_name ?? session('active_branch_name');
            }

            $historyId = DB::table('inventory_history')->insertGetId($payload);

            if ($diff > 0) {
                $this->mirrorStockInToPurchase($product, (float) abs($diff), (int) $historyId);
            }
        }
    }

    private function mirrorStockInToPurchase(Product $product, float $quantity, int $historyId): void
    {
        if (!Schema::hasTable('purchases') || !Schema::hasTable('purchase_items') || $quantity <= 0) {
            return;
        }

        $purchaseNo = 'AUTO-STK-' . $historyId;
        if (Purchase::where('purchase_no', $purchaseNo)->exists()) {
            return;
        }

        $unitPrice = (float) ($product->purchase_price ?? $product->price ?? 0);
        $amount = round($unitPrice * $quantity, 2);

        DB::transaction(function () use ($purchaseNo, $product, $quantity, $unitPrice, $amount) {
            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'supplier_id' => null,
                'total_amount' => $amount,
                'tax_amount' => 0,
                'status' => 'received',
            ]);

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'qty' => $quantity,
                'unit_price' => $unitPrice,
            ]);
        });
    }
}
