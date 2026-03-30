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

    public function __construct(Carbon $reportDate, int $companyId = 0, int $userId = 0)
    {
        $this->reportDate = $reportDate;
        $this->companyId = $companyId;
        $this->userId = $userId;
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

        $accountsQuery = Account::query();
        if ($this->companyId > 0 && \Schema::hasColumn('accounts', 'company_id')) {
            $accountsQuery->where('company_id', $this->companyId);
        } elseif ($this->userId > 0 && \Schema::hasColumn('accounts', 'user_id')) {
            $accountsQuery->where('user_id', $this->userId);
        }

        $accounts = $accountsQuery->with(['transactions' => function($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
            if ($this->companyId > 0 && \Schema::hasColumn('transactions', 'company_id')) {
                $query->where('company_id', $this->companyId);
            } elseif ($this->userId > 0 && \Schema::hasColumn('transactions', 'user_id')) {
                $query->where('user_id', $this->userId);
            }
        }])->get();

        return $accounts->map(function($account) {
            $debits = $account->transactions->sum('debit');
            $credits = $account->transactions->sum('credit');

            $type = $this->normalizeAccountType($account->type ?? null);
            if (in_array($type, ['asset', 'expense'], true)) {
                $balance = $debits - $credits;
            } else {
                $balance = $credits - $debits;
            }

            $account->balance = abs($balance);
            return $account;
        })->where('balance', '>', 0);
    }

    private function calculateRetainedEarnings($date)
    {
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return 0;
        }

        $accountsQuery = Account::query();
        if ($this->companyId > 0 && \Schema::hasColumn('accounts', 'company_id')) {
            $accountsQuery->where('company_id', $this->companyId);
        } elseif ($this->userId > 0 && \Schema::hasColumn('accounts', 'user_id')) {
            $accountsQuery->where('user_id', $this->userId);
        }

        $accounts = (clone $accountsQuery)->with(['transactions' => function($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
            if ($this->companyId > 0 && \Schema::hasColumn('transactions', 'company_id')) {
                $query->where('company_id', $this->companyId);
            } elseif ($this->userId > 0 && \Schema::hasColumn('transactions', 'user_id')) {
                $query->where('user_id', $this->userId);
            }
        }])->get();

        $revenue = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'revenue')
            ->sum(function($account) {
                return $account->transactions->sum('credit') - $account->transactions->sum('debit');
            });

        $expenses = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'expense')
            ->sum(function($account) {
                return $account->transactions->sum('debit') - $account->transactions->sum('credit');
            });

        $dividends = $accounts
            ->filter(fn ($account) => str_contains(strtolower((string) $account->name), 'dividend'))
            ->sum(function($account) {
                return $account->transactions->sum('debit') - $account->transactions->sum('credit');
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
}
