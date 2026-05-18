<?php

namespace App\Console\Commands;

use App\Models\FixedAsset;
use App\Support\LedgerService;
use Illuminate\Console\Command;

class BackfillFixedAssetJournals extends Command
{
    protected $signature   = 'backfill:fixed-asset-journals';
    protected $description = 'Re-post acquisition journal entries for all fixed assets and correct their account sub_type to Fixed Asset';

    public function handle(): int
    {
        $assets = FixedAsset::withoutGlobalScopes()->get();

        if ($assets->isEmpty()) {
            $this->info('No fixed assets found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$assets->count()} fixed asset(s)...");
        $bar = $this->output->createProgressBar($assets->count());
        $bar->start();

        $fixed  = 0;
        $errors = [];

        foreach ($assets as $asset) {
            try {
                LedgerService::postFixedAssetAcquisition($asset);
                $fixed++;
            } catch (\Throwable $e) {
                $errors[] = "Asset #{$asset->id} ({$asset->name}): " . $e->getMessage();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Done. {$fixed} asset(s) corrected.");

        if (!empty($errors)) {
            $this->warn('Errors encountered:');
            foreach ($errors as $err) {
                $this->line("  - {$err}");
            }
        }

        return self::SUCCESS;
    }
}
