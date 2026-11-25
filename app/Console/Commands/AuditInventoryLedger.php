<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditInventoryLedger extends Command
{
    protected $signature = 'ledger:audit-inventory
        {company : Exact company name or numeric ID}
        {--as-of= : Report cutoff in YYYY-MM-DD format}';

    protected $description = 'Inspect inventory postings and unmatched stock-in records for one business without making changes';

    public function handle(): int
    {
        $cutoff = $this->option('as-of') ?? now()->toDateString();
        if (Validator::make(['date' => $cutoff], ['date' => 'required|date_format:Y-m-d'])->fails()) {
            $this->error('Enter a valid as-of date in YYYY-MM-DD format.');

            return self::FAILURE;
        }

        $identifier = trim((string) $this->argument('company'));
        $companies = DB::table('companies')->whereNull('deleted_at')
            ->when(ctype_digit($identifier),
                fn ($query) => $query->where('id', $identifier),
                fn ($query) => $query->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($identifier)])
            )->limit(2)->get(['id', 'name']);
        if ($companies->count() !== 1) {
            $this->error('Enter one active company ID or a unique exact name.');

            return self::FAILURE;
        }

        $company = $companies->first();
        $inventoryAccounts = DB::table('accounts')->select('id')
            ->where('company_id', $company->id)->whereNull('deleted_at')
            ->whereRaw("LOWER(type) IN ('asset', 'assets', 'inventory', 'stock')")
            ->where(function ($query) {
                $query->where('code', 'like', 'AUTO-AST-INV%')
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%inventory%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%stock%']);
            });
        $postings = DB::table('transactions')
            ->where('company_id', $company->id)->whereNull('deleted_at')
            ->whereDate('transaction_date', '<=', $cutoff)
            ->whereIn('account_id', $inventoryAccounts);

        $this->info("{$company->name} (company {$company->id}) through {$cutoff}");
        $this->info('Posted inventory by branch and transaction type');
        $totals = (clone $postings)->select('branch_id', 'branch_name', 'transaction_type')
            ->selectRaw('SUM(debit) AS debits, SUM(credit) AS credits')
            ->groupBy('branch_id', 'branch_name', 'transaction_type')
            ->orderBy('branch_name')->orderBy('branch_id')->orderBy('transaction_type')->get();
        $this->table(['Branch ID', 'Branch', 'Type', 'Debits', 'Credits', 'Net'], $totals->map(fn ($row) => [
            $row->branch_id ?: '—',
            $row->branch_name ?: 'Unassigned',
            $row->transaction_type ?: 'Unspecified',
            $this->money($row->debits),
            $this->money($row->credits),
            $this->money($row->debits - $row->credits),
        ]));

        $unmatched = DB::table('purchases')
            ->where('company_id', $company->id)->whereNull('deleted_at')
            ->where('purchase_no', 'like', 'AUTO-STK-%')->where('total_amount', '>', 0)
            ->whereDate(DB::raw('COALESCE(purchase_date, created_at)'), '<=', $cutoff)
            ->whereNotExists((clone $postings)->selectRaw('1')->where('debit', '>', 0)
                ->where(function ($source) {
                    $source->where(function ($purchase) {
                        $purchase->where('related_type', Purchase::class)
                            ->whereColumn('related_id', 'purchases.id');
                    })->orWhereColumn('reference', 'purchases.purchase_no');
                }));
        $this->info('Stock-in records without a matching Inventory debit by purchase ID or reference');
        $candidates = (clone $unmatched)->select('branch_id', 'branch_name')
            ->selectRaw('COUNT(*) AS records, SUM(total_amount) AS recorded_value')
            ->groupBy('branch_id', 'branch_name')->orderBy('branch_name')->orderBy('branch_id')->get();
        $this->table(['Branch ID', 'Branch', 'Records', 'Recorded value'], $candidates->map(fn ($row) => [
            $row->branch_id ?: '—',
            $row->branch_name ?: 'Unassigned',
            $row->records,
            $this->money($row->recorded_value),
        ]));
        $examples = $unmatched->orderByDesc('total_amount')->orderBy('id')->limit(15)
            ->get(['purchase_no', 'branch_name', 'total_amount']);
        if ($examples->isNotEmpty()) {
            $this->table(['Stock-in reference', 'Branch', 'Recorded value'], $examples->map(fn ($row) => [
                $row->purchase_no,
                $row->branch_name ?: 'Unassigned',
                $this->money($row->total_amount),
            ]));
        }

        $this->line('Match these records to source documents before posting corrections. Separate journals may already cover some receipts.');

        return self::SUCCESS;
    }

    private function money(float|string $value): string
    {
        return number_format((float) $value, 2);
    }
}
