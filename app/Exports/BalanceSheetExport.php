<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Account;

class BalanceSheetExport implements FromArray, WithHeadings
{
    protected $reportDate;
    protected $companyId;
    protected $userId;
    protected $branchId;
    protected $branchName;
    protected $branchScope;

    public function __construct(Carbon $reportDate, int $companyId = 0, int $userId = 0, ?string $branchId = null, ?string $branchName = null, string $branchScope = 'branch')
    {
        $this->reportDate = $reportDate;
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->branchId = $branchId;
        $this->branchName = $branchName;
        $this->branchScope = $branchScope;
    }

    public function array(): array
    {
        // Fetch account balances similar to your controller's method
        $accounts = $this->getAccountBalances($this->reportDate);

        // Categorize accounts
        $currentAssets = $accounts->filter(function ($account) {
            return $this->normalizeAccountType($account->type ?? null) === 'asset'
                && str_contains(strtolower((string) ($account->sub_type ?? '')), 'current');
        });
        $fixedAssets = $accounts->filter(function ($account) {
            return $this->normalizeAccountType($account->type ?? null) === 'asset'
                && str_contains(strtolower((string) ($account->sub_type ?? '')), 'fixed');
        });
        $currentLiabilities = $accounts->filter(function ($account) {
            return $this->normalizeAccountType($account->type ?? null) === 'liability'
                && str_contains(strtolower((string) ($account->sub_type ?? '')), 'current');
        });
        $longTermLiabilities = $accounts->filter(function ($account) {
            return $this->normalizeAccountType($account->type ?? null) === 'liability'
                && str_contains(strtolower((string) ($account->sub_type ?? '')), 'long');
        });
        $equity = $accounts->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'equity');

        if ($currentAssets->isEmpty() && $fixedAssets->isEmpty()) {
            $currentAssets = $accounts->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'asset');
        }

        $customerOB = $this->customerOpeningBalance();
        if ($customerOB > 0.01) {
            $currentAssets = $currentAssets->concat([(object) [
                'name' => 'Accounts Receivable',
                'type' => 'Asset',
                'sub_type' => 'Current Asset',
                'balance' => $customerOB,
            ]]);
            $equity = $equity->concat([(object) [
                'name' => 'Opening Balance Equity (Customers)',
                'type' => 'Equity',
                'balance' => $customerOB,
            ]]);
        }

        $supplierOB = $this->supplierOpeningBalance();
        if ($supplierOB > 0.01) {
            $currentLiabilities = $currentLiabilities->concat([(object) [
                'name' => 'Accounts Payable',
                'type' => 'Liability',
                'sub_type' => 'Current Liability',
                'balance' => $supplierOB,
            ]]);
            $equity = $equity->concat([(object) [
                'name' => 'Opening Balance Equity (Suppliers)',
                'type' => 'Equity',
                'balance' => -1 * $supplierOB,
            ]]);
        }

        $inventoryBridge = $this->inventoryBridge($accounts);
        if ($inventoryBridge > 0.01) {
            $currentAssets = $currentAssets->concat([(object) [
                'name' => 'Inventory',
                'type' => 'Asset',
                'sub_type' => 'Current Asset',
                'balance' => $inventoryBridge,
            ]]);
            $equity = $equity->concat([(object) [
                'name' => 'Opening Balance Equity (Inventory)',
                'type' => 'Equity',
                'balance' => $inventoryBridge,
            ]]);
        }

        // Prepare data rows
        $rows = [];

        // Assets
        $rows[] = ['Assets', '', ''];
        foreach ($currentAssets as $account) {
            $rows[] = [$account->name, 'Current Asset', $account->balance];
        }
        $rows[] = ['Total Current Assets', '', $currentAssets->sum('balance')];

        foreach ($fixedAssets as $account) {
            $rows[] = [$account->name, 'Fixed Asset', $account->balance];
        }
        $rows[] = ['Total Fixed Assets', '', $fixedAssets->sum('balance')];

        $rows[] = ['Total Assets', '', $currentAssets->sum('balance') + $fixedAssets->sum('balance')];

        // Liabilities
        $rows[] = ['Liabilities', '', ''];
        foreach ($currentLiabilities as $account) {
            $rows[] = [$account->name, 'Current Liability', $account->balance];
        }
        $rows[] = ['Total Current Liabilities', '', $currentLiabilities->sum('balance')];

        foreach ($longTermLiabilities as $account) {
            $rows[] = [$account->name, 'Long-term Liability', $account->balance];
        }
        $rows[] = ['Total Long-term Liabilities', '', $longTermLiabilities->sum('balance')];

        $rows[] = ['Total Liabilities', '', $currentLiabilities->sum('balance') + $longTermLiabilities->sum('balance')];

        // Equity
        $rows[] = ['Equity', '', ''];
        foreach ($equity as $account) {
            $rows[] = [$account->name, 'Equity', $account->balance];
        }
        // Add Retained Earnings if available
        $retainedEarnings = $this->calculateRetainedEarnings($this->reportDate);
        $rows[] = ['Retained Earnings', '', $retainedEarnings];

        $totalEquity = $equity->sum('balance') + $retainedEarnings;
        $rows[] = ['Total Equity', '', $totalEquity];

        // Total Assets = Total Liabilities + Total Equity
        $rows[] = ['Total Assets', '', $currentAssets->sum('balance') + $fixedAssets->sum('balance')];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Account Name',
            'Type',
            'Balance',
        ];
    }

    private function getAccountBalances($date)
    {
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return collect([]);
        }

        $txnQuery = \App\Models\Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $date)
            ->groupBy('account_id');
        $this->applyCompanyScope($txnQuery, 'transactions');

        if ($this->branchScope !== 'all') {
            $branchId = trim((string) ($this->branchId ?? ''));
            $branchName = trim((string) ($this->branchName ?? ''));
            $txnQuery->where(function ($sub) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $sub->where('branch_id', $branchId);
                }
                if ($branchName !== '') {
                    $sub->orWhere('branch_name', $branchName);
                }
            });
        }

        $txnTotals = $txnQuery->get()->keyBy('account_id');
        $accountIds = $txnTotals->keys()->all();

        $accountsQuery = Account::withoutGlobalScope('tenant')
            ->where(function ($query) use ($accountIds) {
                if (!empty($accountIds)) {
                    $query->whereIn('id', $accountIds);
                }
                $query->orWhere('opening_balance', '!=', 0);
            });
        $this->applyCompanyScope($accountsQuery, 'accounts');
        $accounts = $accountsQuery->get();

        return $accounts->map(function($account) use ($txnTotals) {
            $totals = $txnTotals->get($account->id);
            $debits = (float) ($totals->total_debit ?? 0);
            $credits = (float) ($totals->total_credit ?? 0);
            $opening = (float) ($account->opening_balance ?? 0);

            $type = $this->normalizeAccountType($account->type ?? null);
            if (in_array($type, ['asset', 'expense'], true)) {
                $balance = $opening + $debits - $credits;
            } else {
                $balance = $opening + $credits - $debits;
            }

            $account->balance = $balance;
            return $account;
        })->filter(fn ($account) => abs((float) $account->balance) > 0.01);
    }

    private function calculateRetainedEarnings($date)
    {
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return 0;
        }

        $txnQuery = \App\Models\Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $date)
            ->groupBy('account_id');
        $this->applyCompanyScope($txnQuery, 'transactions');

        if ($this->branchScope !== 'all') {
            $branchId = trim((string) ($this->branchId ?? ''));
            $branchName = trim((string) ($this->branchName ?? ''));
            $txnQuery->where(function ($sub) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $sub->where('branch_id', $branchId);
                }
                if ($branchName !== '') {
                    $sub->orWhere('branch_name', $branchName);
                }
            });
        }

        $txnTotals = $txnQuery->get()->keyBy('account_id');
        $accountIds = $txnTotals->keys()->all();

        if (empty($accountIds)) {
            return 0;
        }

        $accounts = Account::withoutGlobalScope('tenant')
            ->whereIn('id', $accountIds)
            ->get();

        $revenue = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'revenue')
            ->sum(function($account) use ($txnTotals) {
                $totals = $txnTotals->get($account->id);
                return (float) ($totals->total_credit ?? 0) - (float) ($totals->total_debit ?? 0);
            });

        $expenses = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'expense')
            ->sum(function($account) use ($txnTotals) {
                $totals = $txnTotals->get($account->id);
                return (float) ($totals->total_debit ?? 0) - (float) ($totals->total_credit ?? 0);
            });

        $dividends = $accounts
            ->filter(fn ($account) => str_contains(strtolower((string) $account->name), 'dividend'))
            ->sum(function($account) use ($txnTotals) {
                $totals = $txnTotals->get($account->id);
                return (float) ($totals->total_debit ?? 0) - (float) ($totals->total_credit ?? 0);
            });

        return $revenue - $expenses - $dividends;
    }

    private function normalizeAccountType(?string $type): string
    {
        $value = strtolower(trim((string) $type));
        if ($value === '') {
            return 'other';
        }

        $map = [
            'asset' => ['asset', 'assets'],
            'liability' => ['liability', 'liabilities', 'payable', 'payables', 'current liability', 'long term liability', 'long-term liability'],
            'equity' => ['equity', 'capital', 'owner equity', 'owners equity', "owner's equity", 'share capital', 'shareholder equity'],
            'revenue' => ['revenue', 'income', 'sales', 'turnover'],
            'expense' => ['expense', 'expenses', 'cost', 'cogs', 'cost of sales', 'cost of goods sold'],
        ];

        foreach ($map as $key => $aliases) {
            if (in_array($value, $aliases, true)) {
                return $key;
            }
        }

        return $value;
    }

    private function applyCompanyScope($target, string $table): void
    {
        if ($this->companyId > 0 && \Schema::hasColumn($table, 'company_id')) {
            $target->where('company_id', $this->companyId);
        } elseif ($this->userId > 0 && \Schema::hasColumn($table, 'user_id')) {
            $target->where('user_id', $this->userId);
        }
    }

    private function scopedTable(string $table)
    {
        $query = DB::table($table);
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
                        ->orWhere('opening_balance_date', '<=', $this->reportDate->toDateString());
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
                        ->orWhere('opening_balance_date', '<=', $this->reportDate->toDateString());
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

    private function inventoryBridge($accounts): float
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

        $ledgerInventory = (float) $accounts
            ->filter(fn ($account) => str_contains(strtolower((string) ($account->name ?? '')), 'inventory')
                || str_contains(strtolower((string) ($account->name ?? '')), 'stock'))
            ->sum('balance');

        return max(0.0, round($inventoryValue - max(0.0, $ledgerInventory), 2));
    }
}
