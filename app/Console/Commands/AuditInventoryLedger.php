<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $purchaseDate = Schema::hasColumn('purchases', 'purchase_date')
            ? DB::raw('COALESCE(purchases.purchase_date, purchases.created_at)')
            : 'purchases.created_at';
        $unmatched = DB::table('purchases')
            ->where('company_id', $company->id)
            ->when(Schema::hasColumn('purchases', 'deleted_at'), fn ($query) => $query->whereNull('purchases.deleted_at'))
            ->where('purchase_no', 'like', 'AUTO-STK-%')->where('total_amount', '>', 0)
            ->whereDate($purchaseDate, '<=', $cutoff)
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
        $examples = (clone $unmatched)->orderByDesc('total_amount')->orderBy('id')->limit(15)
            ->get(['purchase_no', 'branch_name', 'total_amount']);
        if ($examples->isNotEmpty()) {
            $this->table(['Stock-in reference', 'Branch', 'Recorded value'], $examples->map(fn ($row) => [
                $row->purchase_no,
                $row->branch_name ?: 'Unassigned',
                $this->money($row->total_amount),
            ]));
        }

        $this->traceStockHistory($unmatched, (int) $company->id, $cutoff);
        $this->line('Match these records to source documents before posting corrections. Separate journals may already cover some receipts.');

        return self::SUCCESS;
    }

    private function traceStockHistory(Builder $unmatched, int $companyId, string $cutoff): void
    {
        if (!Schema::hasColumns('inventory_history', ['company_id', 'branch_id', 'branch_name', 'reference'])) {
            return;
        }

        $groups = [];
        $examples = collect();
        $unmatched->reorder()->select(['id', 'purchase_no', 'total_amount'])
            ->chunkById(300, function ($purchases) use ($companyId, $cutoff, &$groups, &$examples) {
                $historyId = static function (string $reference): ?int {
                    return preg_match('/^AUTO-STK-([1-9][0-9]*)$/', $reference, $matches) ? (int) $matches[1] : null;
                };
                $history = DB::table('inventory_history')
                    ->where('company_id', $companyId)
                    ->whereDate('created_at', '<=', $cutoff)
                    ->whereRaw("LOWER(type) = 'in'")->where('quantity', '>', 0)
                    ->whereIn('id', $purchases->pluck('purchase_no')->map($historyId)->filter()->all())
                    ->get(['id', 'product_id', 'branch_id', 'branch_name', 'reference', 'created_at', 'quantity'])
                    ->keyBy('id');

                foreach ($purchases as $purchase) {
                    $movement = $history->get($historyId($purchase->purchase_no));
                    $branchId = trim((string) ($movement->branch_id ?? ''));
                    $branchName = trim((string) ($movement->branch_name ?? ''));
                    $branch = $branchName ?: ($branchId ? 'Unnamed' : 'Unassigned');
                    $reference = strtolower(trim((string) ($movement->reference ?? '')));
                    $source = match (true) {
                        !$movement => 'No matching stock-in history',
                        $reference === 'initial stock' => 'Initial stock',
                        str_starts_with($reference, 'manual adjustment') => 'Manual adjustment',
                        $reference === 'stock in' => 'Stock in',
                        default => 'Other stock-in reference',
                    };

                    $key = json_encode([$branchId, $branch, $source]);
                    $groups[$key] ??= ['branch_id' => $branchId ?: '—', 'branch' => $branch, 'source' => $source, 'count' => 0, 'amount' => 0.0];
                    $groups[$key]['count']++;
                    $groups[$key]['amount'] += (float) $purchase->total_amount;

                    $examples->push([
                        'reference' => $purchase->purchase_no,
                        'branch' => $branch,
                        'product' => $movement->product_id ?? '—',
                        'date' => $movement ? substr((string) $movement->created_at, 0, 10) : '—',
                        'description' => $movement->reference ?? $source,
                        'quantity' => $movement->quantity ?? '—',
                        'amount' => (float) $purchase->total_amount,
                    ]);
                }
                $examples = $examples->sortByDesc('amount')->take(15)->values();
            });

        if ($groups === []) {
            return;
        }

        $this->info('Unmatched stock-ins traced to original inventory history');
        $this->table(['History branch ID', 'History branch', 'Movement', 'Records', 'Recorded value'], collect($groups)
            ->sortBy(['branch', 'source'])->map(fn ($row) => [
                $row['branch_id'], $row['branch'], $row['source'], $row['count'], $this->money($row['amount']),
            ])->values());
        $this->table(['Stock-in reference', 'History branch', 'Product ID', 'Movement date', 'Movement reference', 'Quantity', 'Recorded value'],
            $examples->map(fn ($row) => [
                $row['reference'], $row['branch'], $row['product'], $row['date'],
                $row['description'], $row['quantity'], $this->money($row['amount']),
            ]));
    }

    private function money(float|string $value): string
    {
        return number_format((float) $value, 2);
    }
}
