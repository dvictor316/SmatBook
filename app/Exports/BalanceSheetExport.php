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
            return collect([]);
        }

        $accounts = Account::withoutGlobalScope('tenant')
            ->whereIn('id', $accountIds)
            ->where(function ($q) {
                if ($this->companyId > 0) {
                    $q->where('company_id', $this->companyId)
                      ->orWhere(function ($sub) {
                          $sub->whereNull('company_id')
                              ->where('user_id', $this->userId);
                      });
                } elseif ($this->userId > 0) {
                    $q->where('user_id', $this->userId);
                }
            })
            ->get();

        return $accounts->map(function($account) use ($txnTotals) {
            $totals = $txnTotals->get($account->id);
            $debits = (float) ($totals->total_debit ?? 0);
            $credits = (float) ($totals->total_credit ?? 0);

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

        $txnQuery = \App\Models\Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $date)
            ->groupBy('account_id');

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
            ->where(function ($q) {
                if ($this->companyId > 0) {
                    $q->where('company_id', $this->companyId)
                      ->orWhere(function ($sub) {
                          $sub->whereNull('company_id')
                              ->where('user_id', $this->userId);
                      });
                } elseif ($this->userId > 0) {
                    $q->where('user_id', $this->userId);
                }
            })
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
}
