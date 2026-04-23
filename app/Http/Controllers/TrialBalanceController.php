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
    private function calculateSideBalances(float $amount, bool $isDebitNormal): array
    {
        if ($isDebitNormal) {
            return $amount >= 0
                ? ['debit' => $amount, 'credit' => 0.0]
                : ['debit' => 0.0, 'credit' => abs($amount)];
        }

        return $amount >= 0
            ? ['debit' => 0.0, 'credit' => $amount]
            : ['debit' => abs($amount), 'credit' => 0.0];
    }

    private function applyTransactionScope($query, Request $request)
    {
        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function applyAccountScope($query, Request $request)
    {
        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('accounts', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

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

    /**
     * Display the trial balance
     */
    public function index(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        // 1. Set Date Range (Default: latest transaction month)
        $start = $request->start_date ? Carbon::parse($request->start_date) : null;
        $end = $request->end_date ? Carbon::parse($request->end_date) : null;

        // 2. Safety Check: Verify tables exist
        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.trial-balance', ['message' => 'Accounting tables are missing.']);
        }

        if (!$start || !$end) {
            $latestTransactionQuery = Transaction::query();
            $this->applyTransactionScope($latestTransactionQuery, $request);
            $latestTxnDate = $latestTransactionQuery->max('transaction_date');
            $effectiveEnd = $latestTxnDate
                ? Carbon::parse($latestTxnDate)->endOfDay()
                : Carbon::now()->endOfDay();
            $end = $end ?: $effectiveEnd;
            $start = $start ?: $end->copy()->startOfMonth();
        }

        // 3. Get Account Data with Summed Transactions (Optimized + Branch-safe)
        $txnTotalsQuery = Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereDate('transaction_date', '<=', $end->toDateString())
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
        $this->applyTransactionScope($txnTotalsQuery, $request);

        $txnTotals = $txnTotalsQuery
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $ledgerTotalsQuery = Transaction::query()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereDate('transaction_date', '<=', $end->toDateString())
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
        $this->applyTransactionScope($ledgerTotalsQuery, $request);

        $ledgerTotals = $ledgerTotalsQuery->first();
        $ledgerDebits = (float) ($ledgerTotals->total_debit ?? 0);
        $ledgerCredits = (float) ($ledgerTotals->total_credit ?? 0);
        $ledgerDifference = $ledgerDebits - $ledgerCredits;

        $imbalancedEntriesQuery = Transaction::query()
            ->selectRaw('related_type, related_id, transaction_type, reference, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereDate('transaction_date', '<=', $end->toDateString())
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
        $this->applyTransactionScope($imbalancedEntriesQuery, $request);

        $imbalancedEntries = $imbalancedEntriesQuery
            ->groupBy('related_type', 'related_id', 'transaction_type', 'reference')
            ->havingRaw('ABS(SUM(debit) - SUM(credit)) > 0.01')
            ->orderByRaw('ABS(SUM(debit) - SUM(credit)) DESC')
            ->limit(10)
            ->get();

        $accountIds = $txnTotals->keys()->all();
        // Use withoutGlobalScopes() to bypass TenantScoped's strict branch filter.
        // System-created accounts (AR, Revenue, Petty Cash) may have branch_id = ''
        // and would otherwise be excluded. We include them as global accounts.
        $accountsQuery = Account::withoutGlobalScopes()
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
                    // Include global accounts with no branch assignment
                    $sub->orWhereNull('branch_id')
                        ->orWhere('branch_id', '');
                });
            });
        $this->applyAccountScope($accountsQuery, $request);

        $accounts = $accountsQuery->get();
        $openingTotals = ['debit' => 0.0, 'credit' => 0.0];

        // 4. Calculate Net Position for each account
        $accounts = $accounts->map(function ($account) use ($txnTotals, &$openingTotals) {
            $totals = $txnTotals->get($account->id);
            $dr = (float) ($totals->total_debit ?? 0);
            $cr = (float) ($totals->total_credit ?? 0);
            $openingBalance = (float) ($account->opening_balance ?? 0);
            $type = strtolower((string) ($account->type ?? ''));
            $isDebitNormal = in_array($type, ['asset', 'expense'], true);

            $account->debit_balance = 0;
            $account->credit_balance = 0;

            if (abs($openingBalance) > 0.0001) {
                $openingSide = $this->calculateSideBalances($openingBalance, $isDebitNormal);
                $openingTotals['debit'] += $openingSide['debit'];
                $openingTotals['credit'] += $openingSide['credit'];
            }

            $net = $isDebitNormal
                ? $openingBalance + $dr - $cr
                : $openingBalance + $cr - $dr;

            $netSide = $this->calculateSideBalances($net, $isDebitNormal);
            $account->debit_balance = $netSide['debit'];
            $account->credit_balance = $netSide['credit'];
            $account->has_activity = ($dr > 0) || ($cr > 0) || (abs($openingBalance) > 0);
            return $account;
        })
        ->filter(fn($acc) => $acc->has_activity)
        ->sortBy('code');

        $openingDifference = round($openingTotals['debit'] - $openingTotals['credit'], 2);

        if (abs($openingDifference) >= 0.01) {
            $accounts->push((object) [
                'id' => null,
                'code' => 'SYS-OPENING-EQUITY',
                'name' => 'Opening Balance Equity',
                'type' => 'Equity',
                'debit_balance' => $openingDifference < 0 ? abs($openingDifference) : 0.0,
                'credit_balance' => $openingDifference > 0 ? abs($openingDifference) : 0.0,
                'has_activity' => true,
            ]);
        }

        // Inject unposted customer opening balances (customers created before the journal-entry workflow)
        $customerOBUnposted = $this->getUnpostedCustomerOpeningBalanceSum($request, $end);
        if ($customerOBUnposted > 0.01) {
            // Find existing AR account or create a virtual one (DR side)
            $arEntry = $accounts->first(
                fn ($a) => str_contains(strtolower((string) ($a->name ?? '')), 'receivable')
            );
            if ($arEntry) {
                $arEntry->debit_balance = (float) ($arEntry->debit_balance ?? 0) + $customerOBUnposted;
            } else {
                $accounts->push((object) [
                    'id'             => null,
                    'code'           => 'SYS-CUST-AR',
                    'name'           => 'Accounts Receivable',
                    'type'           => 'Asset',
                    'debit_balance'  => $customerOBUnposted,
                    'credit_balance' => 0.0,
                    'has_activity'   => true,
                ]);
            }
            // Balancing credit entry (Opening Balance Equity - Customers)
            $accounts->push((object) [
                'id'             => null,
                'code'           => 'SYS-CUST-OBE',
                'name'           => 'Opening Balance Equity (Customers)',
                'type'           => 'Equity',
                'debit_balance'  => 0.0,
                'credit_balance' => $customerOBUnposted,
                'has_activity'   => true,
            ]);
        }

        $accounts = $accounts->sortBy('code')->values();

        // 5. Final variables exactly as requested by your Blade View
        return view('Reports.Reports.trial-balance', [
            'startDate'    => $start->toDateString(),
            'endDate'      => $end->toDateString(),
            'reportDate'   => $end, // Added fallback for header logic
            'accounts'     => $accounts,
            'totalDebits'  => $accounts->sum('debit_balance'),
            'totalCredits' => $accounts->sum('credit_balance'),
            'ledgerDebits' => $ledgerDebits,
            'ledgerCredits' => $ledgerCredits,
            'ledgerDifference' => $ledgerDifference,
            'imbalancedEntries' => $imbalancedEntries,
            'activeBranch' => $activeBranch,
            'branchScope'  => $activeBranch['scope'] ?? 'branch',
        ]);
    }

    /**
     * Export to Excel
     */
    public function export(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        if (!$startDate || !$endDate) {
            $latestTransactionQuery = Transaction::query();
            $this->applyTransactionScope($latestTransactionQuery, $request);
            $latestTxnDate = $latestTransactionQuery->max('transaction_date');
            $effectiveEnd = $latestTxnDate
                ? Carbon::parse($latestTxnDate)->endOfDay()
                : Carbon::now()->endOfDay();
            $endDate = $endDate ?: $effectiveEnd;
            $startDate = $startDate ?: $endDate->copy()->startOfMonth();
        }

        return Excel::download(
            new TrialBalanceExport(
                $startDate,
                $endDate,
                (int) ($request->user()?->company_id ?? 0),
                (int) ($request->user()?->id ?? 0),
                $activeBranch['id'] ?? null,
                $activeBranch['name'] ?? null,
                $activeBranch['scope'] ?? 'branch'
            ),
            'trial_balance_' . $startDate->format('Y-m-d') . '.xlsx'
        );
    }




    /**
     * Sum customer opening balances (customers.balance > 0) that do NOT yet
     * have a journal entry posted (reference CUST-OB-*). Mirrors the same
     * method in BalanceSheetController for consistency.
     */
    private function getUnpostedCustomerOpeningBalanceSum(Request $request, $reportDate): float
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'balance')) {
            return 0.0;
        }

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId    = (int) ($request->user()?->id ?? 0);

        $postedCustomerIds = [];
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'reference')) {
            $postedQuery = Transaction::withoutGlobalScopes()
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'CUST-OB-%')
                ->where('debit', '>', 0);
            if ($companyId > 0) {
                $postedQuery->where('company_id', $companyId);
            } elseif ($userId > 0) {
                $postedQuery->where('user_id', $userId);
            }
            $postedCustomerIds = $postedQuery->distinct()->pluck('related_id')->filter()->toArray();
        }

        $customerQuery = DB::table('customers')
            ->where('balance', '>', 0)
            ->where(function ($q) use ($reportDate) {
                $q->whereNull('opening_balance_date')
                  ->orWhere('opening_balance_date', '<=', $reportDate->toDateString());
            });

        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $customerQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('customers', 'user_id')) {
            $customerQuery->where('user_id', $userId);
        }

        if (!empty($postedCustomerIds)) {
            $customerQuery->whereNotIn('id', $postedCustomerIds);
        }

        return (float) $customerQuery->sum('balance');
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
                    if ($netBalance >= 0) {
                        $creditBalance = $netBalance;
                    } else {
                        $debitBalance = abs($netBalance);
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
