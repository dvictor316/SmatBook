<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Transaction;
use App\Support\ScopesFinancialStatements;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditFinancialReports extends Command
{
    use ScopesFinancialStatements;

    protected $signature = 'ledger:audit-reports
        {--all-companies : Check all businesses}
        {--company= : Check one business ID and include journal references}
        {--as-of= : Statement cutoff in YYYY-MM-DD format}';

    protected $description = 'Check posted balances and their source journals without changing financial records';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $all = $this->option('all-companies');
        $date = $this->option('as-of') ?? now()->toDateString();
        if (($all ? $companyId !== null : !ctype_digit((string) $companyId) || !(int) $companyId)
            || Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->fails()) {
            $this->error('Supply --all-companies or --company=ID and an optional --as-of=YYYY-MM-DD.');

            return self::FAILURE;
        }

        $companies = DB::table('companies')->whereNull('deleted_at')
            ->when($companyId, fn ($query) => $query->where('id', $companyId))
            ->orderBy('id')->get(['id', 'name']);
        if ($companies->isEmpty()) {
            $this->error('No businesses matched.');

            return self::FAILURE;
        }

        $summary = [];
        foreach ($companies as $company) {
            $postings = DB::table('transactions')->where('transactions.company_id', $company->id)
                ->whereNull('transactions.deleted_at')->whereDate('transactions.transaction_date', '<=', $date);
            $ledger = (clone $postings)
                ->selectRaw('COALESCE(SUM(COALESCE(debit, 0) - COALESCE(credit, 0)), 0) AS difference')->first();
            $journalGaps = (clone $postings)->whereNotNull('reference')->where('reference', '<>', '')
                ->select('reference', 'transaction_type', 'related_type', 'related_id', 'transaction_date')
                ->selectRaw('SUM(COALESCE(debit, 0) - COALESCE(credit, 0)) AS difference')
                ->groupBy('reference', 'transaction_type', 'related_type', 'related_id', 'transaction_date')
                ->havingRaw('ABS(SUM(COALESCE(debit, 0) - COALESCE(credit, 0))) >= 0.01');
            $invalidAccounts = (clone $postings)->leftJoin('accounts', function ($join) {
                $join->on('accounts.id', '=', 'transactions.account_id')
                    ->on('accounts.company_id', '=', 'transactions.company_id');
            })->whereNull('accounts.id');
            $openings = $this->unpostedOpenings((int) $company->id, $date);
            $negativeAssets = $this->negativeAssetBalances($postings);
            $row = [
                $company->id, $company->name, number_format((float) $ledger->difference, 2),
                DB::query()->fromSub($journalGaps, 'journal_gaps')->count(),
                $invalidAccounts->count(), $openings->count(), $negativeAssets->count(),
            ];
            if ($companyId || abs((float) $ledger->difference) >= 0.01 || array_sum(array_slice($row, 3)) > 0) {
                $summary[] = $row;
            }

            if ($companyId) {
                $this->info("{$company->name} through {$date}");
                $this->table(['Journal reference', 'Date', 'Type', 'Debit less credit'], $journalGaps->orderByDesc('transaction_date')->orderBy('reference')->limit(30)->get()->map(fn ($entry) => [
                    $entry->reference, $entry->transaction_date, $entry->transaction_type, number_format((float) $entry->difference, 2),
                ]));
                $this->table(['Unassigned account ID', 'Journal reference'], $invalidAccounts->limit(30)
                    ->get(['transactions.account_id', 'transactions.reference'])->map(fn ($entry) => [$entry->account_id, $entry->reference]));
                $this->table(['Unposted opening account', 'Name', 'Saved amount'], $openings->get(['id', 'code', 'name', 'opening_balance'])
                    ->map(fn ($entry) => [$entry->code, $entry->name, number_format((float) $entry->opening_balance, 2)]));
                $this->table(['Negative asset account', 'Branch', 'Category', 'Posted balance'], $negativeAssets->map(fn ($entry) => [
                    $entry['code'], $entry['branch'], $entry['category'], number_format($entry['balance'], 2),
                ]));
            }
        }

        $this->info("Checked {$companies->count()} business(es) through {$date}. Negative balances may reflect real overdrafts or inventory movements.");
        $this->table(['Company ID', 'Business', 'Ledger difference', 'Journal gaps', 'Account scope issues', 'Unposted openings', 'Negative asset balances'], $summary);
        if ($all) {
            $unassigned = DB::table('transactions')->whereNull('deleted_at')->whereDate('transaction_date', '<=', $date)
                ->where(fn ($query) => $query->whereNull('company_id')
                    ->orWhereNotIn('company_id', DB::table('companies')->select('id')))
                ->count();
            if ($unassigned > 0) {
                $this->warn("{$unassigned} posted row(s) have no matching business ID and are outside the business totals above.");
            }
        }

        return self::SUCCESS;
    }

    private function unpostedOpenings(int $companyId, string $date): Builder
    {
        return DB::table('accounts')->where('company_id', $companyId)->whereNull('deleted_at')
            ->whereRaw('ABS(COALESCE(opening_balance, 0)) >= 0.005')
            ->where(fn ($account) => $account->whereNull('created_at')->orWhereDate('created_at', '<=', $date))
            ->whereNotExists(fn ($journal) => $journal->selectRaw('1')->from('transactions')
                ->whereColumn('transactions.account_id', 'accounts.id')->whereColumn('transactions.company_id', 'accounts.company_id')
                ->whereColumn('transactions.related_id', 'accounts.id')->whereNull('transactions.deleted_at')
                ->where('transactions.related_type', Account::class)
                ->where('transactions.transaction_type', Transaction::TYPE_OPENING_BALANCE));
    }

    private function negativeAssetBalances(Builder $postings): Collection
    {
        return (clone $postings)->join('accounts', function ($join) {
            $join->on('accounts.id', '=', 'transactions.account_id')->on('accounts.company_id', '=', 'transactions.company_id');
        })->select('accounts.code', 'accounts.name', 'accounts.type', 'accounts.sub_type', 'accounts.description', 'transactions.branch_id')
            ->selectRaw('MAX(transactions.branch_name) AS branch_name, SUM(COALESCE(transactions.debit, 0) - COALESCE(transactions.credit, 0)) AS balance')
            ->groupBy('transactions.account_id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.sub_type', 'accounts.description', 'transactions.branch_id')
            ->havingRaw('SUM(COALESCE(transactions.debit, 0) - COALESCE(transactions.credit, 0)) <= -0.01')
            ->get()->filter(fn ($row) => $this->normalizeAccountType($row->type) === 'asset')
            ->map(function ($row) {
                $account = new Account(['type' => Account::TYPE_ASSET] + (array) $row);
                $category = $account->cashOrBankType();
                if (!$category && (str_starts_with($row->code, 'AUTO-AST-INV')
                    || strtolower((string) $row->sub_type) === 'inventory'
                    || in_array(strtolower($row->name), ['inventory', 'stock', 'stock on hand'], true))) {
                    $category = 'Inventory';
                }

                return [
                    'code' => $row->code,
                    'branch' => $row->branch_name ?: ($row->branch_id ?: 'Unassigned'),
                    'category' => $category,
                    'balance' => (float) $row->balance,
                ];
            })->filter(fn ($row) => $row['category'] !== null);
    }
}
