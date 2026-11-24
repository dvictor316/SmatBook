<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\Transaction;
use App\Support\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillSalesLedger extends Command
{
    protected $signature = 'ledger:backfill-sales
                            {--company= : Only backfill sales for this company_id}
                            {--rebuild-existing : Rebuild ledger entries even for sales that already have postings}
                            {--dry-run : Preview what would be posted without writing anything}
                            {--chunk=100 : Process sales in chunks of this size}';

    protected $description = 'Re-post LedgerService journal entries for sales, with optional rebuild of existing postings';

    public function handle(): int
    {
        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            $this->error('accounts or transactions table does not exist. Nothing to do.');
            return self::FAILURE;
        }

        $isDryRun  = $this->option('dry-run');
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $rebuildExisting = (bool) $this->option('rebuild-existing');
        $chunk     = (int) ($this->option('chunk') ?: 100);

        $this->info($isDryRun ? '-- DRY RUN (no writes) --' : 'Starting ledger backfill...');

        $alreadyPostedIds = [];
        if (!$rebuildExisting) {
            $alreadyPostedIds = Transaction::query()
                ->where('related_type', Sale::class)
                ->whereIn('transaction_type', [Transaction::TYPE_SALE, Transaction::TYPE_RECEIPT, Transaction::TYPE_JOURNAL])
                ->pluck('related_id')
                ->unique()
                ->toArray();
        }

        $query = Sale::query()
            ->where('total', '>', 0)
            ->orderBy('id');

        if (!$rebuildExisting) {
            $query->whereNotIn('id', $alreadyPostedIds);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info($rebuildExisting
                ? 'No eligible sales found to rebuild.'
                : 'All sales already have journal entries. Nothing to backfill.');
            return self::SUCCESS;
        }

        $this->info($rebuildExisting
            ? "Found {$total} sales to rebuild."
            : "Found {$total} sales without journal entries.");

        if ($isDryRun) {
            $this->table(['company_id', 'branch_id', 'invoice_no', 'total', 'paid', 'payment_method'], 
                $query->limit(20)->get(['company_id', 'branch_id', 'invoice_no', 'total', 'paid', 'payment_method'])->toArray()
            );
            $this->comment('Run without --dry-run to post these entries.');
            return self::SUCCESS;
        }

        $posted  = 0;
        $errors  = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunk, function ($sales) use (
            &$posted, &$errors, $bar
        ) {
            foreach ($sales as $sale) {
                try {
                    DB::transaction(fn () => LedgerService::postSale($sale));
                    $posted++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Sale #{$sale->id} failed: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Done. Posted: {$posted} | Errors: {$errors}");

        if ($errors > 0) {
            $this->warn('Some sales failed. Check output above for details.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
