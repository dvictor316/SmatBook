<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;
    protected $companyId;
    protected $userId;
    protected $branchId;
    protected $branchName;
    protected $branchScope;

    public function __construct($startDate, $endDate, int $companyId = 0, int $userId = 0, ?string $branchId = null, ?string $branchName = null, string $branchScope = 'branch')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->branchId = $branchId;
        $this->branchName = $branchName;
        $this->branchScope = $branchScope;
    }

    public function collection()
    {
        $txnQuery = \App\Models\Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $this->endDate)
            ->groupBy('account_id');

        $this->applyCompanyScope($txnQuery, 'transactions');

        $this->applyBranchTransactionVisibility($txnQuery);

        $txnTotals = $txnQuery->get()->keyBy('account_id');
        $accountIds = $txnTotals->keys()->all();

        $accountsQuery = Account::withoutGlobalScope('tenant')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($accountIds) {
                if (!empty($accountIds)) {
                    $query->whereIn('id', $accountIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            });
        $this->applyCompanyScope($accountsQuery, 'accounts');
        $accounts = $accountsQuery->get();

        $rows = $accounts->map(function ($account) use ($txnTotals) {
            $totals = $txnTotals->get($account->id);
            $dr = (float) ($totals->total_debit ?? 0);
            $cr = (float) ($totals->total_credit ?? 0);

            $debitBalance = 0.0;
            $creditBalance = 0.0;

            $isDebitNormal = in_array($account->type, ['Asset', 'Expense'], true);

            if ($isDebitNormal) {
                $net = $dr - $cr;
                if ($net >= 0) {
                    $debitBalance = $net;
                } else {
                    $creditBalance = abs($net);
                }
            } else {
                $net = $cr - $dr;
                if ($net >= 0) {
                    $creditBalance = $net;
                } else {
                    $debitBalance = abs($net);
                }
            }

            return [
                $account->code ?? 'N/A',
                $account->name,
                $account->type,
                $debitBalance,
                $creditBalance,
            ];
        })->filter(fn ($row) => ($row[3] > 0 || $row[4] > 0))->values();

        return $rows->sortBy(fn ($row) => $row[0])->values();
    }

    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Account Type',
            'Debit Balance',
            'Credit Balance',
        ];
    }

    private function mergeBalanceIntoRows($rows, string $accountName, string $accountType, float $debit, float $credit, string $fallbackCode): void
    {
        $existingIndex = $rows->search(function ($row) use ($accountName) {
            return strtolower(trim((string) ($row[1] ?? ''))) === strtolower($accountName);
        });

        if ($existingIndex !== false) {
            $existingRow = $rows->get($existingIndex);
            $existingRow[3] = (float) ($existingRow[3] ?? 0) + $debit;
            $existingRow[4] = (float) ($existingRow[4] ?? 0) + $credit;
            $rows->put($existingIndex, $existingRow);
            return;
        }

        $rows->push([$fallbackCode, $accountName, $accountType, $debit, $credit]);
    }

    private function applyCompanyScope($target, string $table): void
    {
        if ($this->companyId > 0 && \Schema::hasColumn($table, 'company_id')) {
            $target->where('company_id', $this->companyId);
        } elseif ($this->userId > 0 && \Schema::hasColumn($table, 'user_id')) {
            $target->where('user_id', $this->userId);
        }
    }

    private function applyBranchTransactionVisibility($query): void
    {
        if ($this->branchScope === 'all') {
            $this->applyConfiguredBranchUniverse($query, 'transactions');
            return;
        }

        $branchId = trim((string) ($this->branchId ?? ''));
        $branchName = trim((string) ($this->branchName ?? ''));
        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($sub) use ($branchId, $branchName) {
            if ($branchId !== '') {
                $sub->where('branch_id', $branchId);
            }
            if ($branchName !== '') {
                $method = $branchId !== '' ? 'orWhere' : 'where';
                $sub->{$method}('branch_name', $branchName);
            }
        });
    }

    private function configuredBranches(): \Illuminate\Support\Collection
    {
        if ($this->companyId <= 0 || !\Schema::hasTable('settings')) {
            return collect();
        }

        $rawBranches = (string) (\DB::table('settings')
            ->where('key', 'branches_json_company_' . $this->companyId)
            ->value('value') ?? '');

        return collect(json_decode($rawBranches, true) ?: [])
            ->map(function ($branch) {
                return [
                    'id' => trim((string) ($branch['id'] ?? '')),
                    'name' => trim((string) ($branch['name'] ?? '')),
                ];
            })
            ->filter(fn ($branch) => $branch['id'] !== '' || $branch['name'] !== '')
            ->values();
    }

    private function applyConfiguredBranchUniverse($query, string $table): void
    {
        $branches = $this->configuredBranches();
        if ($branches->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $branchIds = $branches->pluck('id')->filter()->unique()->values()->all();
        $branchNames = $branches->pluck('name')->filter()->unique()->values()->all();

        $query->where(function ($branchScoped) use ($table, $branchIds, $branchNames) {
            if (!empty($branchIds) && \Schema::hasColumn($table, 'branch_id')) {
                $branchScoped->whereIn('branch_id', $branchIds);
            }

            if (!empty($branchNames) && \Schema::hasColumn($table, 'branch_name')) {
                $method = (!empty($branchIds) && \Schema::hasColumn($table, 'branch_id')) ? 'orWhereIn' : 'whereIn';
                $branchScoped->{$method}('branch_name', $branchNames);
            }
        });
    }

    private function scopedTable(string $table)
    {
        $query = \DB::table($table);
        if ($this->companyId > 0 && \Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $this->companyId);
        } elseif ($this->userId > 0 && \Schema::hasColumn($table, 'user_id')) {
            $query->where('user_id', $this->userId);
        }

        return $query;
    }

    private function customerOpeningBalance(): float
    {
        if (!\Schema::hasTable('customers') || !\Schema::hasColumn('customers', 'balance')) {
            return 0.0;
        }

        $query = $this->scopedTable('customers')
            ->where('balance', '>', 0)
            ->when(\Schema::hasColumn('customers', 'opening_balance_date'), function ($query) {
                $query->where(function ($sub) {
                    $sub->whereNull('opening_balance_date')
                        ->orWhere('opening_balance_date', '<=', $this->endDate->toDateString());
                });
            });

        $postedIds = $this->postedOpeningBalanceIds('CUST-OB-%', 'debit');
        if (!empty($postedIds)) {
            $query->whereNotIn('id', $postedIds);
        }

        return (float) $query->sum('balance');
    }

    private function supplierOpeningBalance(): float
    {
        if (!\Schema::hasTable('suppliers') || !\Schema::hasColumn('suppliers', 'opening_balance')) {
            return 0.0;
        }

        $query = $this->scopedTable('suppliers')
            ->where('opening_balance', '>', 0)
            ->when(\Schema::hasColumn('suppliers', 'opening_balance_date'), function ($query) {
                $query->where(function ($sub) {
                    $sub->whereNull('opening_balance_date')
                        ->orWhere('opening_balance_date', '<=', $this->endDate->toDateString());
                });
            });

        $postedIds = $this->postedOpeningBalanceIds('SUPP-OB-%', 'credit');
        if (!empty($postedIds)) {
            $query->whereNotIn('id', $postedIds);
        }

        return (float) $query->sum('opening_balance');
    }

    private function postedOpeningBalanceIds(string $referencePattern, string $side): array
    {
        if (!\Schema::hasTable('transactions') || !\Schema::hasColumn('transactions', 'reference')) {
            return [];
        }

        $query = \App\Models\Transaction::withoutGlobalScopes()
            ->where('transaction_type', \App\Models\Transaction::TYPE_OPENING_BALANCE)
            ->where('reference', 'like', $referencePattern)
            ->where($side, '>', 0);
        $this->applyCompanyScope($query, 'transactions');

        return $query->distinct()->pluck('related_id')->filter()->map(fn ($v) => (int) $v)->all();
    }

    private function inventoryBridge($rows): float
    {
        if (!\Schema::hasTable('products') || !\Schema::hasColumn('products', 'stock')) {
            return 0.0;
        }

        $priceColumn = \Schema::hasColumn('products', 'purchase_price')
            ? 'purchase_price'
            : (\Schema::hasColumn('products', 'price') ? 'price' : null);
        if ($priceColumn === null) {
            return 0.0;
        }

        $inventoryValue = (float) $this->scopedTable('products')
            ->where('stock', '>', 0)
            ->selectRaw("SUM(COALESCE(stock, 0) * COALESCE({$priceColumn}, 0)) as inventory_value")
            ->value('inventory_value');

        $ledgerInventory = (float) $rows
            ->filter(fn ($row) => str_contains(strtolower((string) ($row[1] ?? '')), 'inventory')
                || str_contains(strtolower((string) ($row[1] ?? '')), 'stock'))
            ->sum(fn ($row) => (float) ($row[3] ?? 0) - (float) ($row[4] ?? 0));

        return max(0.0, round($inventoryValue - max(0.0, $ledgerInventory), 2));
    }
}
