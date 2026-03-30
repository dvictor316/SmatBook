<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BalanceSheetExport;

class BalanceSheetController extends Controller
{
    public function index(Request $request)
    {
        $reportDate = $request->date ? Carbon::parse($request->date) : Carbon::now();

        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.balance-sheet', [
                'reportDate' => $reportDate,
                'currentAssets' => collect(),
                'fixedAssets' => collect(),
                'currentLiabilities' => collect(),
                'equity' => collect(),
                'totalCurrentAssets' => 0,
                'totalFixedAssets' => 0,
                'totalAssets' => 0,
                'totalLiabilities' => 0,
                'totalEquity' => 0,
                'retainedEarnings' => 0,
                'message' => 'Accounting tables are missing.',
            ]);
        }

        // 1. Get all accounts with sums up to the report date
        $accountsQuery = $this->applyAccountScope(Account::query(), $request);
        $accounts = $accountsQuery->withSum(['transactions as total_debit' => function ($query) use ($reportDate) {
            $query->where('transaction_date', '<=', $reportDate);
        }], 'debit')
        ->withSum(['transactions as total_credit' => function ($query) use ($reportDate) {
            $query->where('transaction_date', '<=', $reportDate);
        }], 'credit')
        ->with(['transactions' => function ($query) use ($reportDate) {
            $query->where('transaction_date', '<=', $reportDate)
                ->select([
                    'id',
                    'account_id',
                    'transaction_date',
                    'reference',
                    'description',
                    'transaction_type',
                    'debit',
                    'credit',
                ])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc');
        }])
        ->get();

        // 2. Transform balances based on Account Type
        $accounts->transform(function ($account) {
            $dr = ($account->total_debit ?? 0);
            $cr = ($account->total_credit ?? 0);
            $opening = $account->opening_balance ?? 0;
            $type = strtolower((string) ($account->type ?? ''));

            if (in_array($type, ['asset', 'expense'], true)) {
                $account->balance = ($opening + $dr) - $cr;
            } else {
                $account->balance = ($opening + $cr) - $dr;
            }
            return $account;
        });

        // 3. Calculate Retained Earnings (Revenue - Expenses)
        $totalRevenue = $accounts->filter(fn ($a) => strtolower((string) ($a->type ?? '')) === 'revenue')->sum('balance');
        $totalExpenses = $accounts->filter(fn ($a) => strtolower((string) ($a->type ?? '')) === 'expense')->sum('balance');
        $retainedEarnings = $totalRevenue - $totalExpenses; // View expects $retainedEarnings

        // 4. Group Accounts specifically for your View variables
        $assetAccounts = $accounts->filter(fn ($a) => strtolower((string) ($a->type ?? '')) === 'asset');
        $currentAssets = $assetAccounts->filter(fn ($a) => strtolower((string) ($a->sub_type ?? '')) === 'current asset');
        $fixedAssets = $assetAccounts->filter(fn ($a) => strtolower((string) ($a->sub_type ?? '')) === 'fixed asset');
        if ($currentAssets->isEmpty() && $fixedAssets->isEmpty()) {
            // Fallback for ledgers where asset sub_type has not been categorized yet
            $currentAssets = $assetAccounts;
        }
        $currentLiabilities = $accounts->filter(fn ($a) => strtolower((string) ($a->type ?? '')) === 'liability');
        $equity = $accounts->filter(fn ($a) => strtolower((string) ($a->type ?? '')) === 'equity'); // Changed from equityAccounts to equity

        // 5. Final Totals
        $totalCurrentAssets = $currentAssets->sum('balance');
        $totalFixedAssets = $fixedAssets->sum('balance');
        $totalAssets = $totalCurrentAssets + $totalFixedAssets;
        
        $totalLiabilities = $currentLiabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $retainedEarnings;

        // 6. Map variables to match your Blade @foreach calls exactly
        return view('Reports.Reports.balance-sheet', compact(
            'reportDate',
            'currentAssets',
            'fixedAssets',
            'currentLiabilities',
            'equity',
            'totalCurrentAssets',
            'totalFixedAssets',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'retainedEarnings'
        ));
    }


    /**
     * Export balance sheet to Excel.
     */
    public function export(Request $request)
    {
        $reportDate = $request->date ? Carbon::parse($request->date) : Carbon::now();
        return Excel::download(
            new BalanceSheetExport(
                $reportDate,
                (int) ($request->user()?->company_id ?? 0),
                (int) ($request->user()?->id ?? 0)
            ), 
            'balance_sheet_' . $reportDate->format('Y-m-d') . '.xlsx'
        );
    }



    public function balanceSheet()
{
    // 1. Get balances for ALL accounts
    $allBalances = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
        ->select('accounts.name', 'accounts.type', 'accounts.category')
        ->selectRaw('SUM(debit) as total_debit')
        ->selectRaw('SUM(credit) as total_credit')
        ->groupBy('accounts.id', 'accounts.name', 'accounts.type', 'accounts.category')
        ->get();

    // 2. Filter for Balance Sheet Accounts (Permanent)
    // Assets usually = Debit - Credit
    $assets = $allBalances->where('type', 'Asset')->map(function($item) {
        $item->balance = $item->total_debit - $item->total_credit;
        return $item;
    });

    // Liabilities/Equity usually = Credit - Debit
    $liabilities = $allBalances->where('type', 'Liability')->map(function($item) {
        $item->balance = $item->total_credit - $item->total_debit;
        return $item;
    });

    $equity = $allBalances->where('type', 'Equity')->map(function($item) {
        $item->balance = $item->total_credit - $item->total_debit;
        return $item;
    });

    // 3. CALCULATE NET PROFIT (This is the key!)
    $totalRevenue = $allBalances->where('type', 'Revenue')->sum('total_credit') - 
                     $allBalances->where('type', 'Revenue')->sum('total_debit');
                     
    $totalExpenses = $allBalances->where('type', 'Expense')->sum('total_debit') - 
                      $allBalances->where('type', 'Expense')->sum('total_credit');
                      
    $netProfit = $totalRevenue - $totalExpenses;

    return view('Finance.balance_sheet', compact('assets', 'liabilities', 'equity', 'netProfit'));
}

    private function applyAccountScope($query, Request $request)
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('accounts', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    /**
     * Get account balances up to a specific date
     */
    private function getAccountBalances($date)
    {
        // Check if accounts and transactions tables exist
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return collect([]);
        }
        
        $accounts = Account::with(['transactions' => function($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
        }])->get();

        return $accounts->map(function($account) {
            // Calculate balance based on account type
            $debits = $account->transactions->sum('debit');
            $credits = $account->transactions->sum('credit');
            
            // Assets & Expenses: Debit increases, Credit decreases
            // Liabilities, Equity & Revenue: Credit increases, Debit decreases
            if (in_array($account->type, ['Asset', 'Expense'])) {
                $balance = $debits - $credits;
            } else {
                $balance = $credits - $debits;
            }

            $account->balance = abs($balance);
            return $account;
        })->where('balance', '>', 0);
    }

    /**
     * Calculate retained earnings
     */
    private function calculateRetainedEarnings($date)
    {
        if (!(\Schema::hasTable('accounts') && \Schema::hasTable('transactions'))) {
            return 0;
        }

        // Revenue
        $revenue = Account::where('type', 'Revenue')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('credit') - $account->transactions->sum('debit');
            });

        // Expenses
        $expenses = Account::where('type', 'Expense')
            ->with(['transactions' => function($query) use ($date) {
                $query->where('transaction_date', '<=', $date);
            }])
            ->get()
            ->sum(function($account) {
                return $account->transactions->sum('debit') - $account->transactions->sum('credit');
            });

        // Dividends
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
