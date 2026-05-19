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
        $txnQuery = \App\Models\Transaction::withoutGlobalScopes()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereNull('deleted_at')
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

        $trialDifference = round((float) $rows->sum(fn ($row) => (float) ($row[3] ?? 0)) - (float) $rows->sum(fn ($row) => (float) ($row[4] ?? 0)), 2);
        $operationalNet = $this->computeOperationalProfitLossNet();
        if ($operationalNet !== null && abs($trialDifference) >= 0.01) {
            $ledgerIncomeNet = $this->rowIncomeExpenseNet($rows);
            $availableOperationalDelta = round($operationalNet - $ledgerIncomeNet, 2);

            if ($trialDifference > 0.005 && $availableOperationalDelta > 0.005) {
                $rows->push([
                    'OPS-REV-REC',
                    'Operational Sales Revenue Reconciliation',
                    'Revenue',
                    0.0,
                    round(min($trialDifference, $availableOperationalDelta), 2),
                ]);
            } elseif ($trialDifference < -0.005 && $availableOperationalDelta < -0.005) {
                $rows->push([
                    'OPS-EXP-REC',
                    'Operational Expense Reconciliation',
                    'Expense',
                    round(min(abs($trialDifference), abs($availableOperationalDelta)), 2),
                    0.0,
                ]);
            }
        }

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
            $sub->where(function ($exact) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $exact->where('transactions.branch_id', $branchId);
                }
                if ($branchName !== '') {
                    $method = $branchId !== '' ? 'orWhere' : 'where';
                    $exact->{$method}('transactions.branch_name', $branchName);
                }
            })->orWhere(function ($pairedBlankLeg) use ($branchId, $branchName) {
                $pairedBlankLeg->where(function ($missing) {
                    $missing->where(function ($branchIdGap) {
                        $branchIdGap->whereNull('transactions.branch_id')
                            ->orWhere('transactions.branch_id', '');
                    })->where(function ($branchNameGap) {
                        $branchNameGap->whereNull('transactions.branch_name')
                            ->orWhere('transactions.branch_name', '');
                    });
                })->whereExists(function ($anchor) use ($branchId, $branchName) {
                    $anchor->select(\DB::raw('1'))
                        ->from('transactions as branch_anchor')
                        ->whereNull('branch_anchor.deleted_at')
                        ->whereColumn('branch_anchor.transaction_type', 'transactions.transaction_type')
                        ->where(function ($groupMatch) {
                            $groupMatch->where(function ($byReference) {
                                $byReference->whereNotNull('transactions.reference')
                                    ->where('transactions.reference', '<>', '')
                                    ->whereColumn('branch_anchor.reference', 'transactions.reference');
                            })->orWhere(function ($byRelatedModel) {
                                $byRelatedModel->whereNotNull('transactions.related_id')
                                    ->whereNotNull('transactions.related_type')
                                    ->whereColumn('branch_anchor.related_id', 'transactions.related_id')
                                    ->whereColumn('branch_anchor.related_type', 'transactions.related_type');
                            });
                        });

                    if (\Schema::hasColumn('transactions', 'company_id')) {
                        $anchor->where(function ($sameCompany) {
                            $sameCompany->whereColumn('branch_anchor.company_id', 'transactions.company_id')
                                ->orWhere(function ($bothNull) {
                                    $bothNull->whereNull('branch_anchor.company_id')
                                        ->whereNull('transactions.company_id');
                                });
                        });
                    }

                    if (\Schema::hasColumn('transactions', 'user_id')) {
                        $anchor->where(function ($sameUser) {
                            $sameUser->whereColumn('branch_anchor.user_id', 'transactions.user_id')
                                ->orWhere(function ($bothNull) {
                                    $bothNull->whereNull('branch_anchor.user_id')
                                        ->whereNull('transactions.user_id');
                                });
                        });
                    }

                    $anchor->where(function ($anchorBranch) use ($branchId, $branchName) {
                        if ($branchId !== '') {
                            $anchorBranch->where('branch_anchor.branch_id', $branchId);
                        }
                        if ($branchName !== '') {
                            $method = $branchId !== '' ? 'orWhere' : 'where';
                            $anchorBranch->{$method}('branch_anchor.branch_name', $branchName);
                        }
                    });
                });
            });
        });
    }

    private function rowIncomeExpenseNet($rows): float
    {
        $revenue = 0.0;
        $expenses = 0.0;

        foreach ($rows as $row) {
            $type = strtolower(trim((string) ($row[2] ?? '')));
            if (in_array($type, ['revenue', 'income', 'sales', 'turnover'], true)) {
                $revenue += (float) ($row[4] ?? 0) - (float) ($row[3] ?? 0);
            } elseif (in_array($type, ['expense', 'expenses', 'cost', 'cogs', 'cost of sales', 'cost of goods sold'], true)) {
                $expenses += (float) ($row[3] ?? 0) - (float) ($row[4] ?? 0);
            }
        }

        return round($revenue - $expenses, 2);
    }

    private function computeOperationalProfitLossNet(): ?float
    {
        $startDate = $this->startDate instanceof \Carbon\Carbon ? $this->startDate->toDateString() : (string) $this->startDate;
        $endDate = $this->endDate instanceof \Carbon\Carbon ? $this->endDate->toDateString() : (string) $this->endDate;
        $hasOperationalSource = false;

        $salesTotal = 0.0;
        if (\Schema::hasTable('sales')) {
            $hasOperationalSource = true;
            $salesDateExpr = \Schema::hasColumn('sales', 'order_date')
                ? 'COALESCE(DATE(sales.order_date), DATE(sales.created_at))'
                : 'DATE(sales.created_at)';
            $salesAmountExpr = \Schema::hasColumn('sales', 'total')
                ? 'COALESCE(NULLIF(sales.total, 0), sales.total_amount, sales.amount_paid, 0)'
                : (\Schema::hasColumn('sales', 'total_amount')
                    ? 'COALESCE(NULLIF(sales.total_amount, 0), sales.amount_paid, 0)'
                    : 'COALESCE(sales.amount_paid, 0)');

            $salesQuery = \DB::table('sales')
                ->whereBetween(\DB::raw($salesDateExpr), [$startDate, $endDate]);
            $this->applyCompanyScope($salesQuery, 'sales');
            $this->applyOperationalBranchScope($salesQuery, 'sales');
            if (\Schema::hasColumn('sales', 'deleted_at')) {
                $salesQuery->whereNull('sales.deleted_at');
            }
            if (\Schema::hasColumn('sales', 'order_status')) {
                $salesQuery->where(function ($query) {
                    $query->whereNull('sales.order_status')
                        ->orWhereRaw('LOWER(sales.order_status) <> ?', ['draft']);
                });
            }

            $salesTotal = (float) $salesQuery->sum(\DB::raw($salesAmountExpr));
        }

        $purchaseTotal = 0.0;
        if (\Schema::hasTable('purchases')) {
            $hasOperationalSource = true;
            $purchaseDateExpr = \Schema::hasColumn('purchases', 'purchase_date')
                ? 'COALESCE(DATE(purchases.purchase_date), DATE(purchases.created_at))'
                : 'DATE(purchases.created_at)';
            $purchaseAmountExpr = \Schema::hasColumn('purchases', 'total_amount')
                ? 'ABS(COALESCE(purchases.total_amount, 0))'
                : (\Schema::hasColumn('purchases', 'amount')
                    ? 'ABS(COALESCE(purchases.amount, 0))'
                    : '0');

            $purchaseQuery = \DB::table('purchases')
                ->whereBetween(\DB::raw($purchaseDateExpr), [$startDate, $endDate]);
            $this->applyCompanyScope($purchaseQuery, 'purchases');
            $this->applyOperationalBranchScope($purchaseQuery, 'purchases');
            if (\Schema::hasColumn('purchases', 'purchase_type')) {
                $purchaseQuery->where(function ($query) {
                    $query->whereNull('purchases.purchase_type')
                        ->orWhereRaw('LOWER(purchases.purchase_type) <> ?', ['fixed_asset']);
                });
            }
            if (\Schema::hasColumn('purchases', 'status')) {
                $purchaseQuery->where(function ($query) {
                    $query->whereNull('purchases.status')
                        ->orWhereRaw('LOWER(purchases.status) not in (?, ?, ?, ?, ?)', [
                            'draft',
                            'cancelled',
                            'canceled',
                            'rejected',
                            'returned',
                        ]);
                });
            }

            $purchaseTotal = (float) $purchaseQuery->sum(\DB::raw($purchaseAmountExpr));
        }

        $expenseTotal = 0.0;
        if (\Schema::hasTable('expenses') && \Schema::hasColumn('expenses', 'amount')) {
            $hasOperationalSource = true;
            $expenseDateExpr = \Schema::hasColumn('expenses', 'expense_date')
                ? 'COALESCE(DATE(expenses.expense_date), DATE(expenses.created_at))'
                : 'DATE(expenses.created_at)';

            $expenseQuery = \DB::table('expenses')
                ->whereBetween(\DB::raw($expenseDateExpr), [$startDate, $endDate]);
            $this->applyCompanyScope($expenseQuery, 'expenses');
            $this->applyOperationalBranchScope($expenseQuery, 'expenses');
            if (\Schema::hasColumn('expenses', 'status')) {
                $expenseQuery->whereRaw("LOWER(COALESCE(expenses.status, 'pending')) <> ?", ['rejected']);
            }

            $expenseTotal = (float) $expenseQuery->sum(\DB::raw('COALESCE(expenses.amount, 0)'));
        }

        if (!$hasOperationalSource) {
            return null;
        }

        return round($salesTotal - ($purchaseTotal + $expenseTotal), 2);
    }

    private function applyOperationalBranchScope($query, string $table): void
    {
        if ($this->branchScope === 'all') {
            return;
        }

        $branchId = trim((string) ($this->branchId ?? ''));
        $branchName = trim((string) ($this->branchName ?? ''));
        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($branchQuery) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && \Schema::hasColumn($table, 'branch_id')) {
                $branchQuery->where("{$table}.branch_id", $branchId);
            }

            if ($branchName !== '' && \Schema::hasColumn($table, 'branch_name')) {
                $method = ($branchId !== '' && \Schema::hasColumn($table, 'branch_id')) ? 'orWhere' : 'where';
                $branchQuery->{$method}("{$table}.branch_name", $branchName);
            }

            if (\Schema::hasColumn($table, 'branch_id')) {
                $branchQuery->orWhereNull("{$table}.branch_id")
                    ->orWhere("{$table}.branch_id", '');
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
