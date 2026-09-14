<?php

namespace App\Console\Commands;

use App\Models\DeploymentManager;
use App\Support\DeploymentCommissionPayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProcessAgentPayouts extends Command
{
    protected $signature = 'agents:process-payouts {--manager= : Process one manager/agent user ID}';

    protected $description = 'Process eligible automatic agent and deployment manager commission payouts.';

    public function handle(DeploymentCommissionPayoutService $payouts): int
    {
        if (!Schema::hasTable('deployment_managers') || !Schema::hasTable('deployment_commissions')) {
            $this->warn('Agent payout tables are not ready.');

            return self::SUCCESS;
        }

        $query = DeploymentManager::query()
            ->where('auto_payout_enabled', true)
            ->whereNotNull('payout_bank_code')
            ->whereNotNull('payout_account_number');

        if ($managerId = (int) $this->option('manager')) {
            $query->where('user_id', $managerId);
        }

        $processed = 0;
        $created = 0;

        $query->chunkById(50, function ($managers) use ($payouts, &$processed, &$created) {
            foreach ($managers as $manager) {
                $processed++;

                try {
                    $payout = $payouts->attemptAutoPayout((int) $manager->user_id);
                    if ($payout) {
                        $created++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Automatic agent payout processing failed for one manager.', [
                        'manager_id' => $manager->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }, 'id');

        $this->info("Checked {$processed} payout profile(s); created or resumed {$created} payout(s).");

        return self::SUCCESS;
    }
}
