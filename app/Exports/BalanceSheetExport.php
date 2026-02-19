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

    public function __construct(Carbon $reportDate)
    {
        $this->reportDate = $reportDate;
    }

    public function array(): array
    {
        // Fetch account balances similar to your controller's method
        $accounts = $this->getAccountBalances($this->reportDate);

        // Categorize accounts
        $currentAssets = $accounts->where('type', 'Asset')
            ->where('sub_type', 'Current Asset');
        $fixedAssets = $accounts->where('type', 'Asset')
            ->where('sub_type', 'Fixed Asset');
        $currentLiabilities = $accounts->where('type', 'Liability')
            ->where('sub_type', 'Current Liability');
        $longTermLiabilities = $accounts->where('type', 'Liability')
            ->where('sub_type', 'Long-term Liability');
        $equity = $accounts->where('type', 'Equity');

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

        $accounts = Account::with(['transactions' => function($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
        }])->get();

        return $accounts->map(function($account) {
            $debits = $account->transactions->sum('debit');
            $credits = $account->transactions->sum('credit');

            if (in_array($account->type, ['Asset', 'Expense'])) {
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

        $revenue = Account::where('type', 'Revenue')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('credit') - $account->transactions->sum('debit');
            });

        $expenses = Account::where('type', 'Expense')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('debit') - $account->transactions->sum('credit');
            });

        $dividends = Account::where('name', 'like', '%dividend%')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('debit') - $account->transactions->sum('credit');
            });

        return $revenue - $expenses - $dividends;
    }
}