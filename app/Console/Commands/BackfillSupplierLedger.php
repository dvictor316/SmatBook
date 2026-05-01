<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Transaction;
use App\Support\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BackfillSupplierLedger extends Command
{
    protected $signature = 'suppliers:backfill-ledger
                            {--company= : Limit to one company_id}
                            {--all-companies : Process every company}
                            {--branch-id= : Limit to one branch_id}
                            {--branch-name= : Limit to one branch_name}
                            {--dry-run : Preview affected rows without writing}';

    protected $description = 'Backfill supplier opening-balance and supplier-payment ledger entries.';

    public function handle(): int
    {
        if (!Schema::hasTable('suppliers') || !Schema::hasTable('transactions') || !Schema::hasTable('accounts')) {
            $this->error('Required tables (suppliers / transactions / accounts) are missing.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->normalizeOption($this->option('branch-id'));
        $branchName = $this->normalizeOption($this->option('branch-name'));
        $companyIds = $this->resolveCompanyIds();

        if ($companyIds->isEmpty()) {
            $this->warn('No companies matched the supplied options.');
            return self::SUCCESS;
        }

        $this->info($dryRun ? '-- DRY RUN (no writes) --' : 'Backfilling supplier ledger entries...');

        $summaryRows = [];
        $totalOpeningInserted = 0;
        $totalPaymentRebuilt = 0;

        foreach ($companyIds as $companyId) {
            $summary = $this->summarizeCompany((int) $companyId, $branchId, $branchName);
            $summaryRows[] = [
                'company_id' => $companyId,
                'suppliers' => $summary['suppliers'],
                'supplier_payments' => $summary['supplier_payments'],
                'opening_pairs_before' => $summary['opening_pairs_before'],
                'payment_pairs_before' => $summary['payment_pairs_before'],
            ];

            if ($dryRun) {
                continue;
            }

            $openingInserted = LedgerService::backfillSupplierOpeningBalanceEntries((int) $companyId, null, $branchId, $branchName);
            $paymentRebuilt = LedgerService::backfillSupplierPaymentLedgerEntries((int) $companyId, null, $branchId, $branchName);
            $after = $this->summarizeCompany((int) $companyId, $branchId, $branchName);

            $summaryRows[array_key_last($summaryRows)] += [
                'opening_pairs_after' => $after['opening_pairs_after'],
                'payment_pairs_after' => $after['payment_pairs_after'],
                'opening_inserted' => $openingInserted,
                'payment_rebuilt' => $paymentRebuilt,
            ];

            $totalOpeningInserted += $openingInserted;
            $totalPaymentRebuilt += $paymentRebuilt;
        }

        $this->table(
            $dryRun
                ? ['Company', 'Suppliers', 'Payments', 'OB Pairs Before', 'Payment Pairs Before']
                : ['Company', 'Suppliers', 'Payments', 'OB Before', 'OB After', 'OB Added', 'Pay Before', 'Pay After', 'Pay Rebuilt'],
            collect($summaryRows)->map(function (array $row) use ($dryRun) {
                if ($dryRun) {
                    return [
                        $row['company_id'],
                        $row['suppliers'],
                        $row['supplier_payments'],
                        $row['opening_pairs_before'],
                        $row['payment_pairs_before'],
                    ];
                }

                return [
                    $row['company_id'],
                    $row['suppliers'],
                    $row['supplier_payments'],
                    $row['opening_pairs_before'],
                    $row['opening_pairs_after'] ?? 0,
                    $row['opening_inserted'] ?? 0,
                    $row['payment_pairs_before'],
                    $row['payment_pairs_after'] ?? 0,
                    $row['payment_rebuilt'] ?? 0,
                ];
            })->all()
        );

        if ($dryRun) {
            $this->warn('Dry run complete. Re-run without --dry-run to apply the backfill.');
            return self::SUCCESS;
        }

        $this->info("Done. Opening entries added/rebuilt: {$totalOpeningInserted}. Payment groups rebuilt: {$totalPaymentRebuilt}.");
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
                return collect();
            }

            return Company::query()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->values();
        }

        return collect([(int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0)])
            ->filter(fn ($id) => $id > 0)
            ->values();
    }

    private function summarizeCompany(int $companyId, ?string $branchId, ?string $branchName): array
    {
        $suppliersQuery = Supplier::withoutGlobalScopes()->where('company_id', $companyId);
        $paymentsQuery = SupplierPayment::withoutGlobalScopes()->where('company_id', $companyId);
        $openingTransactions = Transaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->where('reference', 'like', 'SUPP-OB-%');
        $paymentTransactions = Transaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('transaction_type', Transaction::TYPE_PAYMENT)
            ->where(function ($query) {
                $query->where('related_type', Supplier::class)
                    ->orWhere('related_type', Purchase::class);
            });

        $this->applyBranchScope($suppliersQuery, 'suppliers', $branchId, $branchName);
        $this->applyBranchScope($paymentsQuery, 'supplier_payments', $branchId, $branchName);
        $this->applyBranchScope($openingTransactions, 'transactions', $branchId, $branchName);
        $this->applyBranchScope($paymentTransactions, 'transactions', $branchId, $branchName);

        return [
            'suppliers' => (clone $suppliersQuery)->count(),
            'supplier_payments' => (clone $paymentsQuery)->count(),
            'opening_pairs_before' => (clone $openingTransactions)->count() / 2,
            'payment_pairs_before' => (clone $paymentTransactions)->count() / 2,
            'opening_pairs_after' => (clone $openingTransactions)->count() / 2,
            'payment_pairs_after' => (clone $paymentTransactions)->count() / 2,
        ];
    }

    private function applyBranchScope($query, string $table, ?string $branchId, ?string $branchName): void
    {
        $branchId = trim((string) ($branchId ?? ''));
        $branchName = trim((string) ($branchName ?? ''));

        if ($branchId === '' && $branchName === '') {
            return;
        }

        $hasBranchId = Schema::hasColumn($table, 'branch_id');
        $hasBranchName = Schema::hasColumn($table, 'branch_name');

        if (!$hasBranchId && !$hasBranchName) {
            return;
        }

        $query->where(function ($sub) use ($table, $branchId, $branchName, $hasBranchId, $hasBranchName) {
            if ($branchId !== '' && $hasBranchId) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && $hasBranchName) {
                $method = ($branchId !== '' && $hasBranchId) ? 'orWhere' : 'where';
                $sub->{$method}("{$table}.branch_name", $branchName);
            }
        });
    }

    private function normalizeOption(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
