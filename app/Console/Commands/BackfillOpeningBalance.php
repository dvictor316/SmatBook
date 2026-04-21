<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillOpeningBalance extends Command
{
    protected $signature   = 'accounts:backfill-opening-balance
                              {--dry-run : Show what would be updated without writing}
                              {--company= : Limit to a specific company_id}';

    protected $description = 'Copy current_balance → opening_balance for accounts that have a stored '
                           . 'current_balance but opening_balance = 0 and no transactions '
                           . '(legacy accounts created before the transaction-ledger correction).';

    public function handle(): int
    {
        if (!Schema::hasTable('accounts')) {
            $this->error('accounts table does not exist.');
            return self::FAILURE;
        }

        $isDryRun  = (bool) $this->option('dry-run');
        $companyId = $this->option('company');

        $this->info($isDryRun ? '-- DRY RUN (no writes) --' : 'Backfilling opening_balance from current_balance...');

        // Accounts with no transactions and opening_balance = 0 but current_balance != 0
        $query = DB::table('accounts')
            ->where(function ($q) {
                $q->whereNull('opening_balance')
                  ->orWhere('opening_balance', 0);
            })
            ->where('current_balance', '!=', 0)
            ->whereNull('deleted_at');

        if ($companyId !== null) {
            $query->where('company_id', (int) $companyId);
        }

        // Only include accounts that have NO transactions posted against them
        if (Schema::hasTable('transactions')) {
            $query->whereNotIn('id', function ($sub) {
                $sub->select('account_id')->from('transactions')->distinct();
            });
        }

        $accounts = $query->get(['id', 'name', 'type', 'opening_balance', 'current_balance', 'company_id']);

        if ($accounts->isEmpty()) {
            $this->info('Nothing to backfill — all accounts with current_balance already have opening_balance set.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Type', 'Company', 'opening_balance (was)', 'current_balance (→ opening)'],
            $accounts->map(fn ($a) => [
                $a->id, $a->name, $a->type, $a->company_id ?? '—',
                number_format((float)$a->opening_balance, 2),
                number_format((float)$a->current_balance, 2),
            ])->all()
        );

        if ($isDryRun) {
            $this->warn("Dry run: {$accounts->count()} account(s) would be updated.");
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($accounts as $account) {
            DB::table('accounts')
                ->where('id', $account->id)
                ->update(['opening_balance' => $account->current_balance]);
            $updated++;
        }

        $this->info("Done. Updated opening_balance for {$updated} account(s).");
        return self::SUCCESS;
    }
}
