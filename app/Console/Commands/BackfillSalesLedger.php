<?php

namespace App\Console\Commands;

use App\Models\Account;
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

        // Pre-load a default Asset account per company for fallback
        // This is used when payment_details has no account_id and resolveCashAccount can't find one
        $defaultAccountByCompany = [];

        $posted  = 0;
        $skipped = 0;
        $errors  = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunk, function ($sales) use (
            &$posted, &$skipped, &$errors,
            $bar, $defaultAccountByCompany
        ) {
            foreach ($sales as $sale) {
                try {
                    // Extract deposit account from stored payment_details JSON
                    $depositAccountId = null;
                    $details = $sale->payment_details;
                    if (is_string($details)) {
                        $details = json_decode($details, true) ?: [];
                    }
                    if (is_array($details)) {
                        // Prefer the explicitly selected deposit account
                        $depositAccountId = (int) ($details['payment_account_id']
                            ?? $details['transfer_account_id']
                            ?? $details['card_account_id']
                            ?? 0) ?: null;
                    }

                    // If no stored account, find the best Asset account for this company
                    if (!$depositAccountId && $sale->company_id) {
                        $cid = (int) $sale->company_id;
                        if (!isset($defaultAccountByCompany[$cid])) {
                            $defaultAccountByCompany[$cid] = Account::withoutGlobalScopes()
                                ->where('company_id', $cid)
                                ->where('type', Account::TYPE_ASSET)
                                ->where('is_active', 1)
                                ->where(function ($q) {
                                    $q->whereRaw('LOWER(name) LIKE ?', ['%bank%'])
                                      ->orWhereRaw('LOWER(name) LIKE ?', ['%cash%'])
                                      ->orWhereRaw('LOWER(name) LIKE ?', ['%moniepoint%'])
                                      ->orWhereRaw('LOWER(name) LIKE ?', ['%pos%']);
                                })
                                ->value('id');

                            // If still none, take any active asset account
                            if (!$defaultAccountByCompany[$cid]) {
                                $defaultAccountByCompany[$cid] = Account::withoutGlobalScopes()
                                    ->where('company_id', $cid)
                                    ->where('type', Account::TYPE_ASSET)
                                    ->where('is_active', 1)
                                    ->value('id');
                            }
                        }
                        $depositAccountId = $defaultAccountByCompany[$cid] ?: null;
                    }

                    LedgerService::postSale($sale, $depositAccountId);
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
