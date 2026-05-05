<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AuditBranchAccountingScope extends Command
{
    protected $signature = 'accounting:audit-branch-scope
                            {--company= : Limit to one company_id}
                            {--all-companies : Process every company}
                            {--dry-run : Preview and report without changing source rows}
                            {--apply : Reassign confidently matched rows}
                            {--purge-orphans : Soft-delete confirmed orphan transactions after backup}
                            {--chunk=200 : Chunk size}
                            {--batch= : Optional batch id override}
                            {--path= : Optional backup/report directory under storage/app}';

    protected $description = 'Audit, reassign, and isolate accounting records that have no valid branch assignment.';

    private array $columnCache = [];
    private array $branchCache = [];

    public function handle(): int
    {
        if (!Schema::hasTable('accounting_branch_audit_findings')) {
            $this->error('The accounting_branch_audit_findings table is missing. Run migrations first.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $purgeOrphans = (bool) $this->option('purge-orphans');
        $dryRun = (bool) $this->option('dry-run') || (!$apply && !$purgeOrphans);
        $chunk = max(50, (int) $this->option('chunk'));
        $batchId = trim((string) ($this->option('batch') ?: now()->format('Ymd_His')));
        $reportPath = trim((string) ($this->option('path') ?: 'branch-cleanup/' . $batchId));
        $companyIds = $this->resolveCompanyIds();

        if ($companyIds->isEmpty()) {
            $this->warn('No companies matched the supplied options.');
            return self::SUCCESS;
        }

        Storage::disk('local')->makeDirectory($reportPath);
        DB::table('accounting_branch_audit_findings')->where('batch_id', $batchId)->delete();

        $summary = [];
        $jsonlPath = storage_path('app/' . trim($reportPath, '/') . '/audit-report.jsonl');
        if (file_exists($jsonlPath)) {
            @unlink($jsonlPath);
        }

        foreach ($companyIds as $companyId) {
            $validBranches = $this->validBranchesForCompany((int) $companyId);
            $companySummary = [
                'company_id' => (int) $companyId,
                'rows_flagged' => 0,
                'rows_reassigned' => 0,
                'rows_orphaned' => 0,
                'transactions_purged' => 0,
            ];

            foreach ($this->tableDefinitions() as $table => $definition) {
                if (!Schema::hasTable($table) || !in_array('id', $this->columns($table), true)) {
                    continue;
                }

                $columns = $this->columns($table);
                if (!in_array('branch_id', $columns, true) && !in_array('branch_name', $columns, true)) {
                    continue;
                }

                $query = DB::table($table)->orderBy('id');
                if (in_array('company_id', $this->columns($table), true)) {
                    $query->where('company_id', $companyId);
                }

                $query->chunkById($chunk, function ($rows) use (
                    $table,
                    $definition,
                    $companyId,
                    $validBranches,
                    $dryRun,
                    $apply,
                    $purgeOrphans,
                    $batchId,
                    $jsonlPath,
                    &$companySummary
                ) {
                    foreach ($rows as $row) {
                        if (!$this->rowNeedsAudit($table, $row, $validBranches)) {
                            continue;
                        }

                        $companySummary['rows_flagged']++;

                        $issueType = $this->issueType($table, $row, $validBranches);
                        $candidate = $this->resolveBranchCandidate($table, $row, (int) $companyId, $validBranches);
                        $status = $candidate ? 'reassignable' : 'orphaned';
                        $actionTaken = 'reported';

                        if ($apply && $candidate) {
                            $this->updateSourceBranch($table, (int) $row->id, $candidate);
                            $status = 'reassigned';
                            $actionTaken = 'branch_reassigned';
                            $companySummary['rows_reassigned']++;
                        } elseif ($status === 'orphaned') {
                            $companySummary['rows_orphaned']++;
                        }

                        if ($purgeOrphans && !$candidate && $table === 'transactions') {
                            $this->softDeleteTransaction((int) $row->id);
                            $status = 'purged_orphan';
                            $actionTaken = 'soft_deleted';
                            $companySummary['transactions_purged']++;
                        }

                        $finding = $this->buildFindingRow($table, $row, $definition, $issueType, $candidate, $status, $actionTaken);
                        DB::table('accounting_branch_audit_findings')->insert(array_merge($finding, [
                            'batch_id' => $batchId,
                            'company_id' => $companyId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]));

                        $this->appendJsonLine($jsonlPath, array_merge($finding, [
                            'batch_id' => $batchId,
                            'company_id' => $companyId,
                            'source_snapshot' => (array) $row,
                        ]));
                    }
                });
            }

            $summary[] = $companySummary;
        }

        Storage::disk('local')->put(
            trim($reportPath, '/') . '/summary.json',
            json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->table(
            ['Company', 'Flagged', 'Reassigned', 'Orphaned', 'Purged Transactions'],
            collect($summary)->map(fn (array $row) => [
                $row['company_id'],
                $row['rows_flagged'],
                $row['rows_reassigned'],
                $row['rows_orphaned'],
                $row['transactions_purged'],
            ])->all()
        );

        $this->info('Audit report: storage/app/' . trim($reportPath, '/') . '/audit-report.jsonl');
        $this->info('Summary report: storage/app/' . trim($reportPath, '/') . '/summary.json');

        if ($dryRun) {
            $this->warn('Dry run complete. Source records were not modified.');
        }

        return self::SUCCESS;
    }

    private function resolveCompanyIds(): Collection
    {
        $companyOption = trim((string) $this->option('company'));
        if ($companyOption !== '') {
            return collect([(int) $companyOption])->filter(fn ($id) => $id > 0)->values();
        }

        if ((bool) $this->option('all-companies')) {
            return Company::query()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->values();
        }

        $companyIds = Company::query()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->values();
        if ($companyIds->count() <= 1) {
            return $companyIds;
        }

        $this->warn('Multiple companies were found. Re-run with --company=<id> or --all-companies.');
        return collect();
    }

    private function tableDefinitions(): array
    {
        return [
            'transactions' => ['date' => 'transaction_date', 'reference' => 'reference', 'account_id' => 'account_id', 'debit' => 'debit', 'credit' => 'credit', 'amount' => 'amount'],
            'sales' => ['date' => 'order_date', 'reference' => 'invoice_no', 'amount' => 'total'],
            'purchases' => ['date' => 'purchase_date', 'reference' => 'purchase_no', 'amount' => 'total_amount'],
            'payments' => ['date' => 'created_at', 'reference' => 'reference', 'amount' => 'amount'],
            'expenses' => ['date' => 'created_at', 'reference' => 'reference', 'amount' => 'amount'],
            'supplier_payments' => ['date' => 'payment_date', 'reference' => 'reference', 'amount' => 'amount'],
            'customers' => ['date' => 'opening_balance_date', 'reference' => 'customer_name', 'amount' => 'balance'],
            'suppliers' => ['date' => 'opening_balance_date', 'reference' => 'name', 'amount' => 'opening_balance'],
            'products' => ['date' => 'created_at', 'reference' => 'sku', 'amount' => 'stock'],
            'product_branch_stocks' => ['date' => 'created_at', 'reference' => 'product_id', 'amount' => 'quantity'],
            'banks' => ['date' => 'created_at', 'reference' => 'account_number', 'amount' => 'balance'],
            'purchase_returns' => ['date' => 'created_at', 'reference' => 'return_no', 'amount' => 'amount'],
            'vendor_ledger_transactions' => ['date' => 'created_at', 'reference' => 'reference', 'amount' => 'amount'],
        ];
    }

    private function rowNeedsAudit(string $table, object $row, Collection $validBranches): bool
    {
        [$branchId, $branchName] = $this->extractBranch($table, $row);
        if ($branchId === '' && $branchName === '') {
            return true;
        }

        if ($branchId !== '' && $validBranches->pluck('id')->contains($branchId)) {
            return false;
        }

        if ($branchName !== '' && $validBranches->pluck('name_lc')->contains(strtolower($branchName))) {
            return $branchId === '';
        }

        return true;
    }

    private function issueType(string $table, object $row, Collection $validBranches): string
    {
        [$branchId, $branchName] = $this->extractBranch($table, $row);
        $validIds = $validBranches->pluck('id');
        $validNames = $validBranches->pluck('name_lc');

        if ($branchId === '' && $branchName === '') {
            return 'missing_branch';
        }
        if ($branchId !== '' && !$validIds->contains($branchId)) {
            return 'invalid_branch_id';
        }
        if ($branchName !== '' && !$validNames->contains(strtolower($branchName))) {
            return 'invalid_branch_name';
        }

        return 'missing_branch_id';
    }

    private function resolveBranchCandidate(string $table, object $row, int $companyId, Collection $validBranches): ?array
    {
        [$branchId, $branchName] = $this->extractBranch($table, $row);
        $normalized = $this->normalizeBranch($branchId, $branchName, $validBranches);
        if ($normalized) {
            return $normalized;
        }

        return match ($table) {
            'transactions' => $this->resolveTransactionBranch($row, $companyId, $validBranches),
            'payments' => $this->firstResolvedBranch($validBranches, [
                fn () => $this->resolveFromTableRecord('sales', (int) ($row->sale_id ?? 0), $validBranches),
                fn () => $this->resolveFromTableRecord('customers', (int) ($row->customer_id ?? 0), $validBranches),
                fn () => $this->resolveFromTableRecord('accounts', (int) (($row->payment_account_id ?? 0) ?: ($row->account_id ?? 0)), $validBranches),
            ]),
            'sales' => $this->firstResolvedBranch($validBranches, [
                fn () => $this->resolveFromTableRecord('customers', (int) ($row->customer_id ?? 0), $validBranches),
                fn () => $this->singleBranchFallback($validBranches),
            ]),
            'purchases' => $this->firstResolvedBranch($validBranches, [
                fn () => $this->resolveFromTableRecord('suppliers', (int) ($row->supplier_id ?? 0), $validBranches),
                fn () => $this->resolveFromTableRecord('banks', (int) ($row->bank_id ?? 0), $validBranches),
                fn () => $this->singleBranchFallback($validBranches),
            ]),
            'expenses' => $this->singleBranchFallback($validBranches),
            'supplier_payments' => $this->firstResolvedBranch($validBranches, [
                fn () => $this->resolveFromTableRecord('purchases', (int) ($row->purchase_id ?? 0), $validBranches),
                fn () => $this->resolveFromTableRecord('suppliers', (int) ($row->supplier_id ?? 0), $validBranches),
                fn () => $this->resolveFromTableRecord('banks', (int) ($row->bank_id ?? 0), $validBranches),
                fn () => $this->resolveFromTableRecord('accounts', (int) ($row->account_id ?? 0), $validBranches),
            ]),
            'customers' => $this->resolveUniqueBranchFromRelatedQueries($validBranches, [
                DB::table('sales')->select('branch_id', 'branch_name')->where('customer_id', (int) $row->id),
                DB::table('payments')->select('branch_id', 'branch_name')->where('customer_id', (int) $row->id),
                DB::table('transactions')->select('branch_id', 'branch_name')->where('related_type', Customer::class)->where('related_id', (int) $row->id),
            ]),
            'suppliers' => $this->resolveUniqueBranchFromRelatedQueries($validBranches, [
                DB::table('purchases')->select('branch_id', 'branch_name')->where('supplier_id', (int) $row->id),
                DB::table('supplier_payments')->select('branch_id', 'branch_name')->where('supplier_id', (int) $row->id),
            ]),
            'products' => $this->resolveUniqueBranchFromRelatedQueries($validBranches, [
                DB::table('product_branch_stocks')->select('branch_id', 'branch_name')->where('product_id', (int) $row->id),
            ]),
            'banks' => $this->resolveUniqueBranchFromRelatedQueries($validBranches, [
                DB::table('purchases')->select('branch_id', 'branch_name')->where('bank_id', (int) $row->id),
                DB::table('supplier_payments')->select('branch_id', 'branch_name')->where('bank_id', (int) $row->id),
            ]),
            'purchase_returns' => $this->resolveFromTableRecord('purchases', (int) ($row->purchase_id ?? 0), $validBranches),
            'vendor_ledger_transactions' => $this->resolveFromTableRecord('vendors', (int) ($row->vendor_id ?? 0), $validBranches),
            default => $this->singleBranchFallback($validBranches),
        };
    }

    private function resolveTransactionBranch(object $row, int $companyId, Collection $validBranches): ?array
    {
        $relatedType = trim((string) ($row->related_type ?? ''));
        $relatedId = (int) ($row->related_id ?? 0);

        return $this->firstResolvedBranch($validBranches, [
            fn () => $this->resolveFromRelatedModel($relatedType, $relatedId, $validBranches),
            fn () => $this->resolveFromTableRecord('accounts', (int) ($row->account_id ?? 0), $validBranches),
            fn () => $this->singleBranchFallback($validBranches),
        ]);
    }

    private function resolveFromRelatedModel(string $relatedType, int $relatedId, Collection $validBranches): ?array
    {
        if ($relatedId <= 0 || $relatedType === '' || !class_exists($relatedType) || !is_subclass_of($relatedType, Model::class)) {
            return null;
        }

        $record = $relatedType::withoutGlobalScopes()->find($relatedId);
        if (!$record) {
            return null;
        }

        return $this->normalizeBranch(
            trim((string) ($record->branch_id ?? '')),
            trim((string) ($record->branch_name ?? $record->branch_label ?? '')),
            $validBranches
        );
    }

    private function resolveFromTableRecord(string $table, int $id, Collection $validBranches): ?array
    {
        if ($id <= 0 || !Schema::hasTable($table)) {
            return null;
        }

        $columns = $this->columns($table);
        if (!in_array('id', $columns, true)) {
            return null;
        }

        $select = array_values(array_intersect(['id', 'branch_id', 'branch_name'], $columns));
        if (empty($select)) {
            return null;
        }

        $record = DB::table($table)->where('id', $id)->first($select);
        if (!$record) {
            return null;
        }

        return $this->normalizeBranch(
            trim((string) ($record->branch_id ?? '')),
            trim((string) ($record->branch_name ?? '')),
            $validBranches
        );
    }

    private function resolveUniqueBranchFromRelatedQueries(Collection $validBranches, array $queries): ?array
    {
        $matches = collect();

        foreach ($queries as $query) {
            $table = $query->from;
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = $this->columns($table);
            if (!in_array('branch_id', $columns, true) && !in_array('branch_name', $columns, true)) {
                continue;
            }

            $rows = $query->get();
            foreach ($rows as $row) {
                $branch = $this->normalizeBranch(
                    trim((string) ($row->branch_id ?? '')),
                    trim((string) ($row->branch_name ?? '')),
                    $validBranches
                );
                if ($branch) {
                    $matches->push($branch['id'] . '|' . $branch['name']);
                }
            }
        }

        $unique = $matches->unique()->values();
        if ($unique->count() !== 1) {
            return $this->singleBranchFallback($validBranches);
        }

        [$branchId, $branchName] = explode('|', (string) $unique->first(), 2);
        return ['id' => $branchId, 'name' => $branchName];
    }

    private function singleBranchFallback(Collection $validBranches): ?array
    {
        return $validBranches->count() === 1
            ? ['id' => (string) $validBranches->first()['id'], 'name' => (string) $validBranches->first()['name']]
            : null;
    }

    private function firstResolvedBranch(Collection $validBranches, array $resolvers): ?array
    {
        foreach ($resolvers as $resolver) {
            $resolved = $resolver();
            if ($resolved) {
                return $this->normalizeBranch($resolved['id'] ?? null, $resolved['name'] ?? null, $validBranches);
            }
        }

        return null;
    }

    private function normalizeBranch(?string $branchId, ?string $branchName, Collection $validBranches): ?array
    {
        $branchId = trim((string) $branchId);
        $branchName = trim((string) $branchName);

        if ($branchId !== '') {
            $match = $validBranches->first(fn ($branch) => (string) $branch['id'] === $branchId);
            if ($match) {
                return ['id' => (string) $match['id'], 'name' => (string) $match['name']];
            }
        }

        if ($branchName !== '') {
            $match = $validBranches->first(fn ($branch) => (string) $branch['name_lc'] === strtolower($branchName));
            if ($match) {
                return ['id' => (string) $match['id'], 'name' => (string) $match['name']];
            }
        }

        return null;
    }

    private function validBranchesForCompany(int $companyId): Collection
    {
        if (isset($this->branchCache[$companyId])) {
            return collect($this->branchCache[$companyId]);
        }

        if (!Schema::hasTable('settings')) {
            return collect();
        }

        $raw = (string) (DB::table('settings')->where('key', 'branches_json_company_' . $companyId)->value('value') ?? '');
        $branches = collect(json_decode($raw, true) ?: [])
            ->filter(fn ($branch) => !empty($branch['id']) || !empty($branch['name']))
            ->map(fn ($branch) => [
                'id' => trim((string) ($branch['id'] ?? '')),
                'name' => trim((string) ($branch['name'] ?? '')),
                'name_lc' => strtolower(trim((string) ($branch['name'] ?? ''))),
            ])
            ->values()
            ->all();

        $this->branchCache[$companyId] = $branches;

        return collect($branches);
    }

    private function buildFindingRow(
        string $table,
        object $row,
        array $definition,
        string $issueType,
        ?array $candidate,
        string $status,
        string $actionTaken
    ): array {
        [$branchId, $branchName] = $this->extractBranch($table, $row);
        [$debit, $credit, $amount] = $this->extractAmounts($definition, $row);

        return [
            'source_table' => $table,
            'source_id' => (int) ($row->id ?? 0) ?: null,
            'status' => $status,
            'issue_type' => $issueType,
            'transaction_date' => $this->extractDate($definition, $row),
            'reference' => $this->extractReference($definition, $row),
            'account_name' => $this->extractAccountName($table, $row),
            'debit' => $debit,
            'credit' => $credit,
            'amount' => $amount,
            'current_branch_id' => $branchId !== '' ? $branchId : null,
            'current_branch_name' => $branchName !== '' ? $branchName : null,
            'proposed_branch_id' => $candidate['id'] ?? null,
            'proposed_branch_name' => $candidate['name'] ?? null,
            'related_summary' => $this->relatedSummary($table, $row),
            'action_taken' => $actionTaken,
            'snapshot' => json_encode((array) $row, JSON_UNESCAPED_SLASHES),
        ];
    }

    private function extractBranch(string $table, object $row): array
    {
        $columns = $this->columns($table);

        return [
            in_array('branch_id', $columns, true) ? trim((string) ($row->branch_id ?? '')) : '',
            in_array('branch_name', $columns, true) ? trim((string) ($row->branch_name ?? '')) : '',
        ];
    }

    private function extractDate(array $definition, object $row): ?string
    {
        $column = $definition['date'] ?? null;
        if (!$column || !isset($row->{$column}) || $row->{$column} === null || $row->{$column} === '') {
            return null;
        }

        return Carbon::parse($row->{$column})->toDateString();
    }

    private function extractReference(array $definition, object $row): ?string
    {
        $column = $definition['reference'] ?? null;
        $value = $column ? trim((string) ($row->{$column} ?? '')) : '';
        return $value !== '' ? $value : null;
    }

    private function extractAmounts(array $definition, object $row): array
    {
        $debit = isset($definition['debit']) ? (float) ($row->{$definition['debit']} ?? 0) : null;
        $credit = isset($definition['credit']) ? (float) ($row->{$definition['credit']} ?? 0) : null;
        $amount = isset($definition['amount']) ? (float) ($row->{$definition['amount']} ?? 0) : null;

        if ($amount === null && $debit !== null && $credit !== null) {
            $amount = max(abs((float) $debit), abs((float) $credit));
        }

        return [$debit, $credit, $amount];
    }

    private function extractAccountName(string $table, object $row): ?string
    {
        return match ($table) {
            'transactions' => $this->lookupAccountName((int) ($row->account_id ?? 0)),
            'supplier_payments' => $this->lookupAccountName((int) ($row->account_id ?? 0)),
            default => null,
        };
    }

    private function lookupAccountName(int $accountId): ?string
    {
        if ($accountId <= 0 || !Schema::hasTable('accounts')) {
            return null;
        }

        return (string) (DB::table('accounts')->where('id', $accountId)->value('name') ?? '') ?: null;
    }

    private function relatedSummary(string $table, object $row): ?string
    {
        $parts = [];
        foreach (['customer_id', 'supplier_id', 'vendor_id', 'sale_id', 'purchase_id', 'bank_id', 'account_id', 'product_id', 'related_type', 'related_id'] as $key) {
            if (isset($row->{$key}) && $row->{$key} !== null && $row->{$key} !== '') {
                $parts[] = $key . '=' . $row->{$key};
            }
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    private function updateSourceBranch(string $table, int $id, array $candidate): void
    {
        $payload = [];
        if (in_array('branch_id', $this->columns($table), true)) {
            $payload['branch_id'] = $candidate['id'];
        }
        if (in_array('branch_name', $this->columns($table), true)) {
            $payload['branch_name'] = $candidate['name'];
        }
        if (!empty($payload)) {
            DB::table($table)->where('id', $id)->update($payload);
        }
    }

    private function softDeleteTransaction(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $query = DB::table('transactions')->where('id', $id);
        if (in_array('deleted_at', $this->columns('transactions'), true)) {
            $query->update(['deleted_at' => now(), 'updated_at' => now()]);
            return;
        }

        $query->delete();
    }

    private function appendJsonLine(string $path, array $payload): void
    {
        file_put_contents($path, json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    private function columns(string $table): array
    {
        if (!isset($this->columnCache[$table])) {
            $this->columnCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->columnCache[$table];
    }
}
