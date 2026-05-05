<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PurgeUnassignedTransactions extends Command
{
    protected $signature = 'ledger:purge-unassigned-transactions
                            {--company= : Limit cleanup to one company_id}
                            {--all-companies : Process every company}
                            {--dry-run : Preview affected transactions without deleting}
                            {--chunk=200 : Number of transactions to process per chunk}';

    protected $description = 'Soft-delete ledger transactions that have no branch assigned, so consolidated reports match branch totals.';

    public function handle(): int
    {
        if (!Schema::hasTable('transactions')) {
            $this->error('The transactions table is missing.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(50, (int) $this->option('chunk'));
        $companyIds = $this->resolveCompanyIds();

        if ($companyIds->isEmpty()) {
            $this->warn('No companies matched the supplied options.');
            return self::SUCCESS;
        }

        $summaries = $companyIds->map(function (int $companyId) {
            $query = $this->baseQuery($companyId);

            return [
                'company_id' => $companyId,
                'count' => (clone $query)->count(),
                'oldest_date' => (clone $query)->min('transaction_date'),
                'latest_date' => (clone $query)->max('transaction_date'),
            ];
        });

        $this->table(
            ['Company', 'Unassigned Transactions', 'Oldest Date', 'Latest Date'],
            $summaries->map(fn (array $row) => [
                $row['company_id'],
                $row['count'],
                $row['oldest_date'] ?? '-',
                $row['latest_date'] ?? '-',
            ])->all()
        );

        if ($dryRun) {
            $this->warn('Dry run complete. Re-run without --dry-run to soft-delete the unassigned transactions.');
            return self::SUCCESS;
        }

        $deletedTotal = 0;

        foreach ($companyIds as $companyId) {
            $query = $this->baseQuery((int) $companyId)->orderBy('id');
            $deletedForCompany = 0;

            $query->chunkById($chunk, function ($transactions) use (&$deletedForCompany, &$deletedTotal) {
                foreach ($transactions as $transaction) {
                    $transaction->delete();
                    $deletedForCompany++;
                    $deletedTotal++;
                }
            });

            $this->info("Company {$companyId}: soft-deleted {$deletedForCompany} unassigned transaction(s).");
        }

        $this->info("Done. Total soft-deleted transactions: {$deletedTotal}.");

        return self::SUCCESS;
    }

    private function resolveCompanyIds(): Collection
    {
        $companyOption = $this->option('company');
        if ($companyOption !== null && $companyOption !== '') {
            return collect([(int) $companyOption])->filter(fn ($id) => $id > 0)->values();
        }

        if ((bool) $this->option('all-companies')) {
            if (!Schema::hasTable('companies')) {
                $this->warn('The companies table is missing, so --all-companies cannot be resolved.');
                return collect();
            }

            return Company::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        if (!Schema::hasTable('companies')) {
            $this->warn('Please pass --company=<id> because the companies table is missing.');
            return collect();
        }

        $companyIds = Company::query()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($companyIds->count() === 1) {
            return $companyIds;
        }

        $this->warn('Multiple companies were found. Re-run with --company=<id> or --all-companies.');

        return collect();
    }

    private function baseQuery(int $companyId): Builder
    {
        $query = Transaction::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($sub) {
                $sub->whereNull('branch_id')->orWhere('branch_id', '');
            });

        if (Schema::hasColumn('transactions', 'branch_name')) {
            $query->where(function ($sub) {
                $sub->whereNull('branch_name')->orWhere('branch_name', '');
            });
        }

        if (Schema::hasColumn('transactions', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }
}
