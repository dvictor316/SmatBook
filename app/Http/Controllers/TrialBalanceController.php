<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Transaction;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrialBalanceExport;

class TrialBalanceController extends Controller
{
    /**
     * Display the trial balance
     */
    public function index(Request $request)
    {
        // 1. Set Date Range (Default: latest transaction month)
        $start = $request->start_date ? Carbon::parse($request->start_date) : null;
        $end = $request->end_date ? Carbon::parse($request->end_date) : null;

        // 2. Safety Check: Verify tables exist
        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.trial-balance', ['message' => 'Accounting tables are missing.']);
        }

        if (!$start || !$end) {
            $latestTxnDate = Transaction::max('transaction_date');
            $effectiveEnd = $latestTxnDate
                ? Carbon::parse($latestTxnDate)->endOfDay()
                : Carbon::now()->endOfDay();
            $end = $end ?: $effectiveEnd;
            $start = $start ?: $end->copy()->startOfMonth();
        }

        // 3. Get Account Data with Summed Transactions (Optimized)
        $accounts = Account::get();
        $accountIds = $accounts->pluck('id')->all();

        $txnTotals = Transaction::selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->when(!empty($accountIds), function ($query) use ($accountIds) {
                $query->whereIn('account_id', $accountIds);
            })
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // 4. Calculate Net Position for each account
        $accounts = $accounts->map(function ($account) use ($txnTotals) {
            $totals = $txnTotals->get($account->id);
            $dr = (float) ($totals->total_debit ?? 0);
            $cr = (float) ($totals->total_credit ?? 0);
            $openingBalance = (float) ($account->opening_balance ?? 0);
            $type = strtolower((string) ($account->type ?? ''));

            $account->debit_balance = 0;
            $account->credit_balance = 0;

            // Debit-Normal accounts (Asset/Expense) vs Credit-Normal (Liability/Equity/Revenue)
            if (in_array($type, ['asset', 'expense'], true)) {
                $net = $openingBalance + $dr - $cr;
                if ($net >= 0) {
                    $account->debit_balance = $net;
                } else {
                    $account->credit_balance = abs($net);
                }
            } else {
                $net = $openingBalance + $cr - $dr;
                if ($net <= 0) {
                    $account->credit_balance = abs($net);
                } else {
                    $account->debit_balance = $net;
                }
            }

            $account->has_activity = ($dr > 0) || ($cr > 0) || (abs($openingBalance) > 0);
            return $account;
        })
        ->filter(fn($acc) => $acc->has_activity)
        ->sortBy('code');

        // 5. Final variables exactly as requested by your Blade View
        return view('Reports.Reports.trial-balance', [
            'startDate'    => $start->toDateString(),
            'endDate'      => $end->toDateString(),
            'reportDate'   => $end, // Added fallback for header logic
            'accounts'     => $accounts,
            'totalDebits'  => $accounts->sum('debit_balance'),
            'totalCredits' => $accounts->sum('credit_balance')
        ]);
    }

    /**
     * Export to Excel
     */
    public function export(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        if (!$startDate || !$endDate) {
            $latestTxnDate = Transaction::max('transaction_date');
            $effectiveEnd = $latestTxnDate
                ? Carbon::parse($latestTxnDate)->endOfDay()
                : Carbon::now()->endOfDay();
            $endDate = $endDate ?: $effectiveEnd;
            $startDate = $startDate ?: $endDate->copy()->startOfMonth();
        }

        return Excel::download(
            new TrialBalanceExport($startDate, $endDate),
            'trial_balance_' . $startDate->format('Y-m-d') . '.xlsx'
        );
    }




    /**
     * Get trial balance data using a robust aggregation
     */
    private function getTrialBalanceData($startDate, $endDate)
    {
        // Check if tables exist to prevent migration errors
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return collect([]);
        }

        // Fetch accounts with summed transactions in the given date range
        // This is more efficient than loading every single transaction into memory
        return Account::with(['transactions' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($account) {
                // IMPORTANT: We calculate the net position of the account
                // If your transactions table uses 'amount', we use that. 
                // If it uses 'debit' and 'credit' columns, swap the logic below:
                
                $totalDebit = $account->transactions->sum('debit'); 
                $totalCredit = $account->transactions->sum('credit');
                $netBalance = $totalDebit - $totalCredit;

                $debitBalance = 0;
                $creditBalance = 0;

                // Standard Accounting Logic:
                // Assets & Expenses usually have Debit balances
                if (in_array($account->type, ['Asset', 'Expense'])) {
                    if ($netBalance >= 0) {
                        $debitBalance = $netBalance;
                    } else {
                        $creditBalance = abs($netBalance);
                    }
                } 
                // Liabilities, Equity, Revenue usually have Credit balances
                else {
                    if ($netBalance <= 0) {
                        $creditBalance = abs($netBalance);
                    } else {
                        $debitBalance = $netBalance;
                    }
                }

                return (object)[
                    'code' => $account->code ?? 'N/A',
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                ];
            })
            // Only show accounts that actually have a balance
            ->filter(function($account) {
                return $account->debit_balance > 0 || $account->credit_balance > 0;
            })
            ->sortBy('code')
            ->values();
    }
}
