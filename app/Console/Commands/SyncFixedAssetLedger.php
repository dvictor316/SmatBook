<?php

namespace App\Console\Commands;

use App\Models\FixedAsset;
use App\Support\LedgerService;
use Illuminate\Console\Command;

class SyncFixedAssetLedger extends Command
{
    protected $signature = 'fixed-assets:sync-ledger {--id= : Sync one fixed asset only}';
    protected $description = 'Sync fixed asset acquisition journals so financial reports include registered assets.';

    public function handle(): int
    {
        $query = FixedAsset::query()->orderBy('id');

        if ($this->option('id')) {
            $query->whereKey((int) $this->option('id'));
        }

        $synced = 0;

        $query->chunkById(100, function ($assets) use (&$synced) {
            foreach ($assets as $asset) {
                LedgerService::postFixedAssetAcquisition($asset);
                $synced++;
            }
        });

        $this->info("Synced {$synced} fixed asset acquisition journal(s).");

        return self::SUCCESS;
    }
}
