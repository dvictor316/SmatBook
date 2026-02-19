<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPurchasesFromInventoryHistory extends Command
{
    protected $signature = 'inventory:backfill-purchases {--dry-run : Show what would be created without writing}';
    protected $description = 'Backfill purchases and purchase_items from inventory_history IN movements';

    public function handle(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('inventory_history')) {
            $this->error('inventory_history table not found.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $createdPurchases = 0;
        $createdItems = 0;
        $skipped = 0;

        DB::table('inventory_history')
            ->whereRaw("LOWER(COALESCE(type, '')) = 'in'")
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$createdPurchases, &$createdItems, &$skipped, $dryRun) {
                foreach ($rows as $row) {
                    $purchaseNo = 'HIST-INV-' . $row->id;

                    if (Purchase::where('purchase_no', $purchaseNo)->exists()) {
                        $skipped++;
                        continue;
                    }

                    $product = DB::table('products')
                        ->where('id', $row->product_id)
                        ->first(['id', 'purchase_price', 'price']);

                    if (!$product) {
                        $skipped++;
                        continue;
                    }

                    $unitPrice = (float) ($product->purchase_price ?? $product->price ?? 0);
                    $qty = (float) $row->quantity;
                    $lineAmount = round($qty * $unitPrice, 2);

                    if ($dryRun) {
                        $createdPurchases++;
                        $createdItems++;
                        continue;
                    }

                    DB::transaction(function () use ($row, $purchaseNo, $product, $qty, $unitPrice, $lineAmount, &$createdPurchases, &$createdItems) {
                        $purchase = Purchase::create([
                            'purchase_no' => $purchaseNo,
                            'supplier_id' => null,
                            'total_amount' => $lineAmount,
                            'tax_amount' => 0,
                            'status' => 'received',
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at ?? $row->created_at,
                        ]);

                        PurchaseItem::create([
                            'purchase_id' => $purchase->id,
                            'product_id' => $product->id,
                            'qty' => $qty,
                            'unit_price' => $unitPrice,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at ?? $row->created_at,
                        ]);

                        $createdPurchases++;
                        $createdItems++;
                    });
                }
            });

        if ($dryRun) {
            $this->info("Dry-run complete. Would create purchases: {$createdPurchases}, items: {$createdItems}, skipped: {$skipped}.");
            return self::SUCCESS;
        }

        $this->info("Backfill complete. Created purchases: {$createdPurchases}, items: {$createdItems}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}

