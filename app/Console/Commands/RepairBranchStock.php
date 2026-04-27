<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Support\BranchInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RepairBranchStock extends Command
{
    protected $signature = 'inventory:repair-branch-stock {--chunk=200 : Chunk size for the repair run}';

    protected $description = 'Recalculate saved branch stock quantities from inventory transactions, purchases, and sales.';

    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!Schema::hasTable('product_branch_stocks') || !Schema::hasTable('products')) {
            $this->error('Required inventory tables are missing.');
            return self::FAILURE;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $updated = 0;

        ProductBranchStock::query()
            ->with('product')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$updated) {
                foreach ($rows as $row) {
                    if (!$row->product) {
                        continue;
                    }

                    $branch = [
                        'id' => $row->branch_id,
                        'name' => $row->branch_name,
                    ];

                    $calculated = $this->branchInventory->calculateBranchStock($row->product, $branch);
                    if ($calculated === null) {
                        continue;
                    }

                    $row->quantity = round($calculated, 2);
                    $row->save();
                    $updated++;
                }
            });

        Product::query()
            ->where(function ($query) {
                if (Schema::hasColumn('products', 'branch_id')) {
                    $query->whereNotNull('branch_id')->where('branch_id', '!=', '');
                }
                if (Schema::hasColumn('products', 'branch_name')) {
                    $method = Schema::hasColumn('products', 'branch_id') ? 'orWhere' : 'where';
                    $query->{$method}(function ($sub) {
                        $sub->whereNotNull('branch_name')->where('branch_name', '!=', '');
                    });
                }
            })
            ->orderBy('id')
            ->chunkById($chunk, function ($products) use (&$updated) {
                foreach ($products as $product) {
                    $branch = [
                        'id' => $product->branch_id ?? null,
                        'name' => $product->branch_name ?? null,
                    ];

                    if (empty($branch['id']) && empty($branch['name'])) {
                        continue;
                    }

                    $existing = ProductBranchStock::query()
                        ->where('product_id', $product->id)
                        ->where('branch_id', (string) ($branch['id'] ?? ''))
                        ->first();

                    if ($existing) {
                        continue;
                    }

                    $calculated = $this->branchInventory->calculateBranchStock($product, $branch);
                    if ($calculated === null) {
                        continue;
                    }

                    ProductBranchStock::query()->create([
                        'product_id' => $product->id,
                        'company_id' => $product->company_id ?? null,
                        'branch_id' => (string) ($branch['id'] ?? ''),
                        'branch_name' => $branch['name'] ?? null,
                        'quantity' => round($calculated, 2),
                    ]);
                    $updated++;
                }
            });

        $this->info("Repaired {$updated} branch stock record" . ($updated === 1 ? '' : 's') . '.');

        return self::SUCCESS;
    }
}
