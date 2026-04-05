<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BalanceSheetExport;

class BalanceSheetController extends Controller
{
    private function resolveActiveBranch(Request $request): array
    {
        $branchScope = (string) $request->get('branch_scope', '');
        $branchId = (string) $request->get('branch_id', '');
        $allBranches = $request->boolean('all_branches')
            || strtolower($branchScope) === 'all'
            || strtolower($branchId) === 'all';

        if ($allBranches) {
            return ['id' => null, 'name' => null, 'scope' => 'all'];
        }

        $activeBranchId = trim((string) session('active_branch_id', ''));
        $activeBranchName = trim((string) session('active_branch_name', ''));

        if ($branchId !== '') {
            $activeBranchId = trim($branchId);
            $activeBranchName = '';
        }

        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        if (($activeBranchId === '' || $activeBranchName === '') && $companyId > 0 && Schema::hasTable('settings')) {
            $branchKey = 'branches_json_company_' . $companyId;
            $rawBranches = (string) (DB::table('settings')->where('key', $branchKey)->value('value') ?? '');
            $branches = json_decode($rawBranches, true) ?: [];

            if ($activeBranchId !== '') {
                $match = collect($branches)->firstWhere('id', $activeBranchId);
                $activeBranchName = trim((string) ($match['name'] ?? $activeBranchName));
            } else {
                $first = collect($branches)->first();
                $activeBranchId = trim((string) ($first['id'] ?? $activeBranchId));
                $activeBranchName = trim((string) ($first['name'] ?? $activeBranchName));
            }
        }

        if ($activeBranchId !== '') {
            session(['active_branch_id' => $activeBranchId]);
        }
        if ($activeBranchName !== '') {
            session(['active_branch_name' => $activeBranchName]);
        }

        return ['id' => $activeBranchId ?: null, 'name' => $activeBranchName ?: null, 'scope' => 'branch'];
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

    public function index(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $reportDate = $request->date ? Carbon::parse($request->date) : Carbon::now();
        Log::info('Balance sheet accessed', [
            'host' => $request->getHost(),
            'user_id' => $request->user()?->id,
            'role' => $request->user()?->role,
            'date' => $reportDate->toDateString(),
        ]);

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

        // 1. Get all accounts with sums up to the report date (branch-safe)
        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        $txnTotals = Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $reportDate)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $branchId = trim((string) ($activeBranch['id'] ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));

                return $query->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '') {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '') {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $ledgerTotalsQuery = Transaction::query()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $reportDate)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $branchId = trim((string) ($activeBranch['id'] ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));

                return $query->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '') {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '') {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            });

        $ledgerTotals = $ledgerTotalsQuery->first();
        $ledgerDebits = (float) ($ledgerTotals->total_debit ?? 0);
        $ledgerCredits = (float) ($ledgerTotals->total_credit ?? 0);
        $ledgerDifference = $ledgerDebits - $ledgerCredits;

        $imbalancedEntries = Transaction::query()
            ->selectRaw('related_type, related_id, transaction_type, reference, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $reportDate)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $branchId = trim((string) ($activeBranch['id'] ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));

                return $query->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '') {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '') {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            })
            ->groupBy('related_type', 'related_id', 'transaction_type', 'reference')
            ->havingRaw('ABS(SUM(debit) - SUM(credit)) > 0.01')
            ->orderByRaw('ABS(SUM(debit) - SUM(credit)) DESC')
            ->limit(10)
            ->get();

        $accountIds = $txnTotals->keys()->all();
        $accountsQuery = Account::query()
            ->where(function ($query) use ($accountIds) {
                if (!empty($accountIds)) {
                    $query->whereIn('id', $accountIds);
                }
                $query->orWhere('opening_balance', '!=', 0);
            })
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $branchId = trim((string) ($activeBranch['id'] ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));

                return $query->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '') {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '') {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            });

        $accounts = $accountsQuery->get();

        $accounts->transform(function ($account) use ($txnTotals) {
            $totals = $txnTotals->get($account->id);
            $account->total_debit = (float) ($totals->total_debit ?? 0);
            $account->total_credit = (float) ($totals->total_credit ?? 0);
            return $account;
        });

        // 2. Transform balances based on Account Type
        $accounts->transform(function ($account) {
            $dr = ($account->total_debit ?? 0);
            $cr = ($account->total_credit ?? 0);
            $opening = $account->opening_balance ?? 0;
            $type = $this->normalizeAccountType($account->type ?? null);

            if (in_array($type, ['asset', 'expense'], true)) {
                $account->balance = ($opening + $dr) - $cr;
            } else {
                $account->balance = ($opening + $cr) - $dr;
            }
            return $account;
        });

        // 3. Calculate Retained Earnings (Revenue - Expenses)
        $totalRevenue = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'revenue')->sum('balance');
        $totalExpenses = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'expense')->sum('balance');
        $retainedEarnings = $totalRevenue - $totalExpenses; // View expects $retainedEarnings

        // 4. Group Accounts specifically for your View variables
        $assetAccounts = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'asset');
        $currentAssets = $assetAccounts->filter(function ($a) {
            $subType = strtolower(trim((string) ($a->sub_type ?? '')));
            return $subType !== '' && str_contains($subType, 'current');
        });
        $fixedAssets = $assetAccounts->filter(function ($a) {
            $subType = strtolower(trim((string) ($a->sub_type ?? '')));
            return $subType !== '' && str_contains($subType, 'fixed');
        });
        if ($currentAssets->isEmpty() && $fixedAssets->isEmpty()) {
            // Fallback for ledgers where asset sub_type has not been categorized yet
            $currentAssets = $assetAccounts;
        }
        $currentLiabilities = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'liability');
        $equity = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'equity'); // Changed from equityAccounts to equity

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
            'retainedEarnings',
            'ledgerDebits',
            'ledgerCredits',
            'ledgerDifference',
            'imbalancedEntries',
            'activeBranch'
        ));
    }


    /**
     * Export balance sheet to Excel.
     */
    public function export(Request $request)
    {
        $reportDate = $request->date ? Carbon::parse($request->date) : Carbon::now();
        $activeBranch = $this->resolveActiveBranch($request);
        return Excel::download(
            new BalanceSheetExport(
                $reportDate,
                (int) ($request->user()?->company_id ?? 0),
                (int) ($request->user()?->id ?? 0),
                $activeBranch['id'] ?? null,
                $activeBranch['name'] ?? null,
                $activeBranch['scope'] ?? 'branch'
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
