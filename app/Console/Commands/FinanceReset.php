<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Support\FinancialDataReset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FinanceReset extends Command
{
    protected $signature = 'finance:reset
                            {--company= : The company_id to reset (required)}
                            {--force   : Skip interactive confirmation prompt}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Safely wipe all transactional / accounting data for a company and zero out running balances.';

    public function handle(): int
    {
        $companyId = (int) $this->option('company');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($companyId <= 0) {
            $this->error('--company=ID is required.');

            return self::FAILURE;
        }

        // Verify company exists
        if (! Schema::hasTable('companies') || ! DB::table('companies')->where('id', $companyId)->exists()) {
            $this->error("Company #{$companyId} not found.");

            return self::FAILURE;
        }

        $companyName = DB::table('companies')->where('id', $companyId)->value('name') ?? "#{$companyId}";

        $this->warn('======================================================');
        $this->warn("  FINANCIAL RESET for company: {$companyName} (ID={$companyId})");
        $this->warn('======================================================');

        if ($dryRun) {
            $this->info('[DRY-RUN MODE — nothing will be deleted]');
        }

        $preview = app(FinancialDataReset::class)->preview($companyId);

        $grandTotal = array_sum($preview);

        $this->table(['Table', 'Rows to Delete'], array_map(
            fn ($t, $c) => [$t, number_format($c)],
            array_keys($preview), $preview
        ));

        $this->line('');
        $this->line("Grand total: <fg=red>{$grandTotal} rows</> will be deleted.");

        if ($dryRun) {
            $this->info('Dry-run complete. No changes made.');

            return self::SUCCESS;
        }

        if (! $force) {
            $confirmed = $this->confirm(
                "⚠️  This CANNOT be undone. Delete all financial data for \"{$companyName}\"?",
                false
            );
            if (! $confirmed) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }

            $phrase = $this->ask('Type exactly  RESET FINANCIAL DATA  to proceed');
            if ($phrase !== 'RESET FINANCIAL DATA') {
                $this->error('Phrase does not match. Aborted.');

                return self::FAILURE;
            }
        }

        $this->line('Starting reset...');
        $deletedLog = [];
        $errors = [];

        try {
            $deletedLog = app(FinancialDataReset::class)->execute($companyId);

            $totalRows = array_sum(array_filter($deletedLog, 'is_int'));

            // Log it
            try {
                ActivityLog::record(
                    'FinancialReset',
                    'reset',
                    "Artisan CLI executed full financial reset for company #{$companyId}. {$totalRows} rows removed/zeroed.",
                    [
                        'source' => 'artisan finance:reset',
                        'company_id' => $companyId,
                        'executed_at' => now()->toDateTimeString(),
                        'deleted_counts' => $deletedLog,
                        'errors' => $errors,
                    ]
                );
            } catch (\Throwable $logEx) {
                $this->warn('Could not write ActivityLog: '.$logEx->getMessage());
            }

            $this->newLine();
            $this->info("✔ Financial reset complete. {$totalRows} total rows removed/zeroed.");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Reset failed and was rolled back: '.$e->getMessage());
            Log::error('FinanceReset CLI: rolled back — '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
