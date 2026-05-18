<?php

namespace App\Console\Commands;

use App\Services\FixedAssetDepreciationService;
use Illuminate\Console\Command;

class RunFixedAssetDepreciation extends Command
{
    protected $signature = 'fixed-assets:depreciate-due {--date= : Post depreciation due on or before this date}';
    protected $description = 'Post scheduled depreciation for fixed assets that are due.';

    public function handle(FixedAssetDepreciationService $service): int
    {
        $summary = $service->runDueForAll($this->option('date'));

        $this->info(sprintf(
            'Depreciation posted for %d asset(s), %d period(s), total amount %.2f.',
            $summary['assets'],
            $summary['periods'],
            $summary['amount']
        ));

        return self::SUCCESS;
    }
}
