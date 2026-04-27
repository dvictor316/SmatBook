<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Support\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RepairPurchaseLedger extends Command
{
    protected $signature = 'ledger:repair-purchases {purchase_id? : Optional purchase ID to repair a single purchase} {--chunk=200 : Chunk size for bulk repair}';

    protected $description = 'Re-post purchase ledger entries so credit purchases land in the correct Accounts Payable account.';

    public function handle(): int
    {
        if (!Schema::hasTable('purchases') || !Schema::hasTable('transactions') || !Schema::hasTable('accounts')) {
            $this->error('Missing required tables: purchases, transactions, or accounts.');
            return self::FAILURE;
        }

        $purchaseId = $this->argument('purchase_id');

        if ($purchaseId !== null) {
            $purchase = Purchase::query()->find($purchaseId);
            if (!$purchase) {
                $this->error("Purchase {$purchaseId} was not found.");
                return self::FAILURE;
            }

            LedgerService::postPurchase($purchase);
            $this->info("Purchase {$purchase->id} repaired successfully.");
            return self::SUCCESS;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $count = 0;

        Purchase::query()
            ->orderBy('id')
            ->chunkById($chunk, function ($purchases) use (&$count) {
                foreach ($purchases as $purchase) {
                    LedgerService::postPurchase($purchase);
                    $count++;
                }
            });

        $this->info("Repaired {$count} purchase ledger entr" . ($count === 1 ? 'y' : 'ies') . '.');

        return self::SUCCESS;
    }
}
