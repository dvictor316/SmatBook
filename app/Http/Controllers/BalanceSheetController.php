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
        $txnTotalsQuery = Transaction::query()
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
            });
        $this->applyTransactionScope($txnTotalsQuery, $request);

        $txnTotals = $txnTotalsQuery
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
        $this->applyTransactionScope($ledgerTotalsQuery, $request);

        $ledgerTotals = $ledgerTotalsQuery->first();
        $ledgerDebits = (float) ($ledgerTotals->total_debit ?? 0);
        $ledgerCredits = (float) ($ledgerTotals->total_credit ?? 0);
        $ledgerDifference = $ledgerDebits - $ledgerCredits;

        $imbalancedEntriesQuery = Transaction::query()
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
            });
        $this->applyTransactionScope($imbalancedEntriesQuery, $request);

        $imbalancedEntries = $imbalancedEntriesQuery
            ->groupBy('related_type', 'related_id', 'transaction_type', 'reference')
            ->havingRaw('ABS(SUM(debit) - SUM(credit)) > 0.01')
            ->orderByRaw('ABS(SUM(debit) - SUM(credit)) DESC')
            ->limit(10)
            ->get();

        $accountIds = $txnTotals->keys()->all();
        // Use withoutGlobalScopes() to bypass TenantScoped's branch filter.
        // TenantScoped excludes accounts with empty/null branch_id, which misses
        // system-generated accounts (AR, Revenue, Cash) created without a branch.
        // We apply our own branch filter below that also includes those global accounts.
        $accountsQuery = Account::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($accountIds) {
                if (!empty($accountIds)) {
                    $query->whereIn('id', $accountIds);
                }
                // Include accounts with an opening balance (set at account creation).
                // Do NOT use current_balance here — it is a stale denormalized cache
                // that does not reset when transactions are deleted.
                $query->orWhere('opening_balance', '!=', 0);
            })
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $branchId = trim((string) ($activeBranch['id'] ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));

                // No branch resolved → show all accounts (same as transaction query).
                // Without this guard the query degrades to WHERE (branch_id IS NULL OR branch_id = '')
                // which silently excludes every COA account that has a branch assigned.
                if ($branchId === '' && $branchName === '') {
                    return;
                }

                return $query->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '') {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '') {
                        $sub->orWhere('branch_name', $branchName);
                    }
                    // Always include global/system accounts with no branch assignment
                    // (Accounts Receivable, Sales Revenue, Petty Cash, etc.)
                    $sub->orWhereNull('branch_id')
                        ->orWhere('branch_id', '');
                });
            });

        $this->applyAccountScope($accountsQuery, $request);

        $accounts = $accountsQuery->get();

        $accounts->transform(function ($account) use ($txnTotals) {
            $totals = $txnTotals->get($account->id);
            $account->total_debit = (float) ($totals->total_debit ?? 0);
            $account->total_credit = (float) ($totals->total_credit ?? 0);
            return $account;
        });

        // 2. Transform balances based on Account Type
        $openingTotals = ['debit' => 0.0, 'credit' => 0.0];

        $accounts->transform(function ($account) use (&$openingTotals) {
            $dr = ($account->total_debit ?? 0);
            $cr = ($account->total_credit ?? 0);
            // Balance = opening_balance + live transaction movement.
            // current_balance (DB column) is a stale cache — never used here.
            $opening = (float) ($account->opening_balance ?? 0);
            $type = $this->normalizeAccountType($account->type ?? null);
            $isDebitNormal = in_array($type, ['asset', 'expense'], true);

            if (abs($opening) > 0.0001) {
                $openingSide = $this->calculateSideBalances($opening, $isDebitNormal);
                $openingTotals['debit'] += $openingSide['debit'];
                $openingTotals['credit'] += $openingSide['credit'];
            }

            $account->balance = $isDebitNormal
                ? ($opening + $dr) - $cr
                : ($opening + $cr) - $dr;

            return $account;
        });

        // 3. Calculate Retained Earnings (Revenue - Expenses)
        $totalRevenue = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'revenue')->sum('balance');
        $totalExpenses = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'expense')->sum('balance');
        $retainedEarnings = $totalRevenue - $totalExpenses;
        $netIncome = $retainedEarnings;

        // 4. Group Accounts specifically for your View variables
        $assetAccounts = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'asset');
        $currentAssets = $assetAccounts->filter(function ($a) {
            $subType = strtolower(trim((string) ($a->sub_type ?? '')));
            return $subType !== '' && str_contains($subType, 'current');
        });
        $fixedAssets = $assetAccounts->filter(function ($a) {
            $subType = strtolower(trim((string) ($a->sub_type ?? '')));
            return $subType !== '' && (str_contains($subType, 'fixed') || str_contains($subType, 'non-current') || str_contains($subType, 'non current'));
        });
        $uncategorizedAssets = $assetAccounts->reject(function ($account) use ($currentAssets, $fixedAssets) {
            return $currentAssets->contains('id', $account->id) || $fixedAssets->contains('id', $account->id);
        });

        if ($currentAssets->isEmpty() && $fixedAssets->isEmpty()) {
            $currentAssets = $assetAccounts;
        } elseif ($uncategorizedAssets->isNotEmpty()) {
            $currentAssets = $currentAssets->concat($uncategorizedAssets)->unique('id')->values();
        }
        $currentLiabilities = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'liability');
        $equity = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'equity'); // Changed from equityAccounts to equity
        $openingDifference = round($openingTotals['debit'] - $openingTotals['credit'], 2);

        if (abs($openingDifference) >= 0.01) {
            $equity = $equity->concat([
                (object) [
                    'id' => null,
                    'code' => 'SYS-OPENING-EQUITY',
                    'name' => 'Opening Balance Equity',
                    'type' => 'Equity',
                    'balance' => $openingDifference,
                ],
            ]);
        }

        // Include customer opening balances not yet posted as journal entries.
        // This covers all existing customers (pre-dating the journal workflow) AND
        // ensures every future record with a balance reflects on the balance sheet.
        $customerOBUnposted = $this->getUnpostedCustomerOpeningBalanceSum($request, $reportDate);
        if ($customerOBUnposted > 0.01) {
            // Add to Accounts Receivable (current assets side)
            $arInCurrentAssets = $currentAssets->first(
                fn ($a) => str_contains(strtolower((string) ($a->name ?? '')), 'receivable')
            );
            if ($arInCurrentAssets) {
                $arInCurrentAssets->balance = (float) ($arInCurrentAssets->balance ?? 0) + $customerOBUnposted;
            } else {
                $currentAssets = $currentAssets->concat([(object) [
                    'id'       => null,
                    'code'     => 'SYS-CUST-AR',
                    'name'     => 'Accounts Receivable',
                    'type'     => 'Asset',
                    'sub_type' => 'Current Asset',
                    'balance'  => $customerOBUnposted,
                ]]);
            }
            $netIncome += $customerOBUnposted;
        }

        $supplierOBUnposted = $this->getUnpostedSupplierOpeningBalanceSum($request, $reportDate);
        if ($supplierOBUnposted > 0.01) {
            $apInLiabilities = $currentLiabilities->first(
                fn ($a) => str_contains(strtolower((string) ($a->name ?? '')), 'payable')
            );
            if ($apInLiabilities) {
                $apInLiabilities->balance = (float) ($apInLiabilities->balance ?? 0) + $supplierOBUnposted;
            } else {
                $currentLiabilities = $currentLiabilities->concat([(object) [
                    'id'       => null,
                    'code'     => 'SYS-SUPP-AP',
                    'name'     => 'Accounts Payable',
                    'type'     => 'Liability',
                    'sub_type' => 'Current Liability',
                    'balance'  => $supplierOBUnposted,
                ]]);
            }
            $netIncome -= $supplierOBUnposted;
        }

        $inventoryBridge = $this->getLegacyInventoryBridgeAmount($request, $reportDate, $accounts);
        if ($inventoryBridge > 0.01) {
            $inventoryInCurrentAssets = $currentAssets->first(
                fn ($a) => str_contains(strtolower((string) ($a->name ?? '')), 'inventory')
                    || str_contains(strtolower((string) ($a->name ?? '')), 'stock')
            );
            if ($inventoryInCurrentAssets) {
                $inventoryInCurrentAssets->balance = (float) ($inventoryInCurrentAssets->balance ?? 0) + $inventoryBridge;
            } else {
                $currentAssets = $currentAssets->concat([(object) [
                    'id'       => null,
                    'code'     => 'SYS-INV',
                    'name'     => 'Inventory',
                    'type'     => 'Asset',
                    'sub_type' => 'Current Asset',
                    'balance'  => $inventoryBridge,
                ]]);
            }

            $currentLiabilities = $currentLiabilities->concat([(object) [
                'id'       => null,
                'code'     => 'SYS-INV-OFFSET',
                'name'     => 'Inventory Offset',
                'type'     => 'Liability',
                'sub_type' => 'Current Liability',
                'balance'  => $inventoryBridge,
            ]]);
        }

        // 5. Final Totals
        $totalCurrentAssets = $currentAssets->sum('balance');
        $totalFixedAssets = $fixedAssets->sum('balance');
        $totalAssets = $totalCurrentAssets + $totalFixedAssets;
        
        $totalLiabilities = $currentLiabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $netIncome;
        $statementDifference = round($totalAssets - ($totalLiabilities + $totalEquity), 2);

        if (abs($statementDifference) >= 0.01) {
            $equity = $equity->concat([(object) [
                'id' => null,
                'code' => 'SYS-BS-RECON',
                'name' => 'Balance Sheet Reconciliation Reserve',
                'type' => 'Equity',
                'balance' => $statementDifference,
            ]]);

            $totalEquity = $equity->sum('balance') + $netIncome;
        }

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
            'netIncome',
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
     * Sum customer opening balances (customers.balance > 0) that do NOT yet
     * have a journal entry in the transactions table (reference CUST-OB-*).
     * This bridges existing customers who pre-date the journal-entry workflow.
     */
    private function getUnpostedCustomerOpeningBalanceSum(Request $request, $reportDate): float
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'balance')) {
            return 0.0;
        }

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId    = (int) ($request->user()?->id ?? 0);

        // Find customer IDs that already have journal entries posted (DR leg only).
        // Only exclude IDs that still exist as active customers — orphaned CUST-OB-*
        // transactions from deleted customers must not block real customers from showing.
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
            $rawPostedIds = $postedQuery->distinct()->pluck('related_id')->filter()->map(fn ($v) => (int) $v)->all();
            // Cross-check: keep only IDs that still exist in customers table
            if (!empty($rawPostedIds)) {
                $postedCustomerIds = DB::table('customers')
                    ->whereIn('id', $rawPostedIds)
                    ->pluck('id')
                    ->all();
            }
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

    private function getUnpostedSupplierOpeningBalanceSum(Request $request, $reportDate): float
    {
        if (!Schema::hasTable('suppliers') || !Schema::hasColumn('suppliers', 'opening_balance')) {
            return 0.0;
        }

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);

        $postedSupplierIds = [];
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'reference')) {
            $postedQuery = Transaction::withoutGlobalScopes()
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'SUPP-OB-%')
                ->where('credit', '>', 0);
            if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
                $postedQuery->where('company_id', $companyId);
            } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
                $postedQuery->where('user_id', $userId);
            }
            $postedSupplierIds = $postedQuery->distinct()->pluck('related_id')->filter()->map(fn ($v) => (int) $v)->all();
        }

        $supplierQuery = DB::table('suppliers')->where('opening_balance', '>', 0);
        if (Schema::hasColumn('suppliers', 'opening_balance_date')) {
            $supplierQuery->where(function ($q) use ($reportDate) {
                $q->whereNull('opening_balance_date')
                    ->orWhere('opening_balance_date', '<=', $reportDate->toDateString());
            });
        }
        if ($companyId > 0 && Schema::hasColumn('suppliers', 'company_id')) {
            $supplierQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('suppliers', 'user_id')) {
            $supplierQuery->where('user_id', $userId);
        }
        if (!empty($postedSupplierIds)) {
            $supplierQuery->whereNotIn('id', $postedSupplierIds);
        }

        return (float) $supplierQuery->sum('opening_balance');
    }

    private function getLegacyInventoryBridgeAmount(Request $request, $reportDate, $accounts): float
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'stock')) {
            return 0.0;
        }

        $priceColumn = Schema::hasColumn('products', 'purchase_price')
            ? 'purchase_price'
            : (Schema::hasColumn('products', 'price') ? 'price' : null);
        if ($priceColumn === null) {
            return 0.0;
        }

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);
        $productQuery = DB::table('products')
            ->where('stock', '>', 0)
            ->selectRaw("SUM(COALESCE(stock, 0) * COALESCE({$priceColumn}, 0)) as inventory_value");

        if ($companyId > 0 && Schema::hasColumn('products', 'company_id')) {
            $productQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('products', 'user_id')) {
            $productQuery->where('user_id', $userId);
        }

        $inventoryValue = (float) ($productQuery->value('inventory_value') ?? 0);
        if ($inventoryValue <= 0.01) {
            return 0.0;
        }

        $ledgerInventory = (float) $accounts
            ->filter(fn ($a) => str_contains(strtolower((string) ($a->name ?? '')), 'inventory')
                || str_contains(strtolower((string) ($a->name ?? '')), 'stock'))
            ->sum('balance');

        return max(0.0, round($inventoryValue - max(0.0, $ledgerInventory), 2));
    }

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

    /** Balance Sheet Summary — same data, summary-only view */
    public function summary(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $reportDate   = $request->date ? Carbon::parse($request->date) : Carbon::now();

        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.balance-sheet-summary', [
                'reportDate' => $reportDate, 'totalAssets' => 0, 'totalLiabilities' => 0,
                'totalEquity' => 0, 'retainedEarnings' => 0, 'activeBranch' => $activeBranch,
            ]);
        }

        $txnTotals = Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $reportDate)
            ->tap(fn ($q) => $this->applyTransactionScope($q, $request))
            ->groupBy('account_id')->get()->keyBy('account_id');

        $accounts = Account::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($txnTotals) {
                if (!$txnTotals->isEmpty()) {
                    $q->whereIn('id', $txnTotals->keys()->all());
                }
                $q->orWhere('opening_balance', '!=', 0);
            })
            ->tap(fn ($q) => $this->applyAccountScope($q, $request))->get()
            ->transform(function ($a) use ($txnTotals) {
                $t = $txnTotals->get($a->id);
                $a->total_debit  = (float)($t->total_debit ?? 0);
                $a->total_credit = (float)($t->total_credit ?? 0);
                $type = $this->normalizeAccountType($a->type ?? null);
                $isDebit = in_array($type, ['asset', 'expense'], true);
                $ob = (float)($a->opening_balance ?? 0);
                $a->balance = $isDebit
                    ? ($ob + $a->total_debit) - $a->total_credit
                    : ($ob + $a->total_credit) - $a->total_debit;
                return $a;
            });

        $totalRevenue    = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'revenue')->sum('balance');
        $totalExpenses   = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'expense')->sum('balance');
        $retainedEarnings = $totalRevenue - $totalExpenses;

        $totalAssets      = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'asset')->sum('balance');
        $totalLiabilities = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'liability')->sum('balance');
        $equityBase       = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type ?? null) === 'equity')->sum('balance');
        $totalEquity      = $equityBase + $retainedEarnings;

        return view('Reports.Reports.balance-sheet-summary', compact('reportDate', 'totalAssets', 'totalLiabilities', 'totalEquity', 'retainedEarnings', 'activeBranch'));
    }

    /** Balance Sheet Comparison — two dates side by side */
    public function comparison(Request $request)
    {
        $dateA = $request->input('date_a') ? Carbon::parse($request->input('date_a')) : Carbon::now();
        $dateB = $request->input('date_b') ? Carbon::parse($request->input('date_b')) : Carbon::now()->subYear();

        $build = function (Carbon $reportDate) use ($request) {
            if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
                return ['assets' => 0, 'liabilities' => 0, 'equity' => 0, 'retained' => 0];
            }
            $txnTotals = Transaction::query()
                ->selectRaw('account_id, SUM(debit) as td, SUM(credit) as tc')
                ->where('transaction_date', '<=', $reportDate)
                ->tap(fn ($q) => $this->applyTransactionScope($q, $request))
                ->groupBy('account_id')->get()->keyBy('account_id');

            $accounts = Account::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->tap(fn ($q) => $this->applyAccountScope($q, $request))->get()
                ->transform(function ($a) use ($txnTotals) {
                    $t = $txnTotals->get($a->id);
                    $type    = $this->normalizeAccountType($a->type ?? null);
                    $isDebit = in_array($type, ['asset', 'expense'], true);
                    $dr = (float)($t->td ?? 0); $cr = (float)($t->tc ?? 0);
                    $ob = (float)($a->opening_balance ?? 0);
                    $a->balance = $isDebit ? ($ob + $dr) - $cr : ($ob + $cr) - $dr;
                    return $a;
                });

            $revenue  = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type) === 'revenue')->sum('balance');
            $expenses = $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type) === 'expense')->sum('balance');
            return [
                'assets'      => $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type) === 'asset')->sum('balance'),
                'liabilities' => $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type) === 'liability')->sum('balance'),
                'equity'      => $accounts->filter(fn ($a) => $this->normalizeAccountType($a->type) === 'equity')->sum('balance') + ($revenue - $expenses),
                'retained'    => $revenue - $expenses,
            ];
        };

        $periodA = $build($dateA);
        $periodB = $build($dateB);

        $activeBranch = $this->resolveActiveBranch($request);
        return view('Reports.Reports.balance-sheet-comparison', compact('dateA', 'dateB', 'periodA', 'periodB', 'activeBranch'));
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
