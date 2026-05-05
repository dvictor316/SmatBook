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
use Illuminate\Support\Collection;

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

        $activeBranch = $this->resolveActiveBranch($request);
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            $query->where(function ($branchScoped) {
                if (Schema::hasColumn('transactions', 'branch_id')) {
                    $branchScoped->whereNotNull('branch_id')
                        ->where('branch_id', '<>', '');
                }

                if (Schema::hasColumn('transactions', 'branch_name')) {
                    $method = Schema::hasColumn('transactions', 'branch_id') ? 'orWhere' : 'where';
                    $branchScoped->{$method}(function ($named) {
                        $named->whereNotNull('branch_name')
                            ->where('branch_name', '<>', '');
                    });
                }
            });
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

    private function accountLooksLikeLiability(object $account): bool
    {
        $type = $this->normalizeAccountType($account->type ?? null);
        $subType = $this->normalizeAccountType($account->sub_type ?? null);
        $name = strtolower(trim((string) ($account->name ?? '')));

        if ($type === 'liability' || $subType === 'liability') {
            return true;
        }

        return str_contains($name, 'payable')
            || str_contains($name, 'vat')
            || str_contains($name, 'tax')
            || str_contains($name, 'firs')
            || str_contains($name, 'withholding')
            || str_contains($name, 'paye');
    }

    private function accountLooksLikeCurrentAsset(object $account): bool
    {
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        return str_contains($subType, 'current') || $subType === '';
    }

    private function accountLooksLikeFixedAsset(object $account): bool
    {
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        return str_contains($subType, 'fixed')
            || str_contains($subType, 'non-current')
            || str_contains($subType, 'non current');
    }

    private function accountLooksLikeLongTermLiability(object $account): bool
    {
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));
        $name = strtolower(trim((string) ($account->name ?? '')));

        return str_contains($subType, 'long')
            || str_contains($subType, 'non-current')
            || str_contains($subType, 'non current')
            || str_contains($subType, 'term loan')
            || str_contains($subType, 'mortgage')
            || str_contains($subType, 'deferred')
            || str_contains($name, 'long-term')
            || str_contains($name, 'long term')
            || str_contains($name, 'deferred revenue')
            || str_contains($name, 'mortgage');
    }

    private function isBankOrCashAccount(object $account): bool
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        return str_contains($name, 'bank')
            || str_contains($name, 'cash')
            || str_contains($name, 'wallet')
            || str_contains($name, 'petty')
            || str_contains($subType, 'bank')
            || str_contains($subType, 'cash');
    }

    private function isOpeningBalanceEquityAccount(object $account): bool
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $code = strtoupper(trim((string) ($account->code ?? '')));

        return $code === 'SYS-OPENING-EQUITY'
            || str_contains($name, 'opening balance equity');
    }

    private function isDiagnosticReserveAccount(object $account): bool
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $code = strtoupper(trim((string) ($account->code ?? '')));

        return $code === 'SYS-BS-RECON'
            || str_contains($name, 'reconciliation reserve')
            || str_contains($name, 'reconciliation suspense');
    }

    private function equityBucketFor(object $account): string
    {
        $name = strtolower(trim((string) ($account->name ?? '')));
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));

        if (str_contains($name, 'retained') || str_contains($name, 'earnings')) {
            return 'Retained Earnings';
        }

        if (str_contains($name, 'reserve')
            || str_contains($name, 'revaluation')
            || str_contains($name, 'appropriation')
            || str_contains($name, 'opening balance')
            || str_contains($subType, 'reserve')
        ) {
            return 'Reserves';
        }

        return 'Capital';
    }

    private function duplicateAccountKey(object $account): string
    {
        $displayName = trim((string) ($account->_display_name ?? $account->name ?? ''));

        return strtolower($displayName)
            . '|' . strtolower(trim((string) ($account->type ?? '')))
            . '|' . strtolower(trim((string) ($account->_bs_group ?? '')));
    }

    private function branchDisplayLabel(object $account): string
    {
        $branchName = trim((string) ($account->branch_name ?? ''));
        if ($branchName !== '') {
            return $branchName;
        }

        $branchId = trim((string) ($account->branch_id ?? ''));
        if ($branchId !== '') {
            return $branchId;
        }

        return 'Shared';
    }

    private function withBalance(object $account, float $balance, array $extra = []): object
    {
        foreach ($extra as $key => $value) {
            $account->{$key} = $value;
        }

        $account->balance = round($balance, 2);

        return $account;
    }

    private function syntheticLine(string $name, string $type, float $balance, array $extra = []): object
    {
        return (object) array_merge([
            'id' => null,
            'code' => null,
            'name' => $name,
            'type' => $type,
            'sub_type' => null,
            'branch_id' => null,
            'branch_name' => null,
            'balance' => round($balance, 2),
        ], $extra);
    }

    private function applyBranchPresentation(array $collections, bool $isAllBranches, bool $consolidate): array
    {
        if (!$isAllBranches) {
            return $collections;
        }

        if ($consolidate) {
            return array_map(function (Collection $items) {
                return $items
                    ->groupBy(fn ($account) => $this->duplicateAccountKey($account))
                    ->map(function (Collection $group) {
                        $first = clone $group->first();
                        $first->balance = round((float) $group->sum(fn ($account) => (float) ($account->balance ?? 0)), 2);
                        $first->branch_id = null;
                        $first->branch_name = null;
                        return $first;
                    })
                    ->values();
            }, $collections);
        }

        $allItems = collect($collections)->reduce(
            fn (Collection $carry, Collection $items) => $carry->concat($items),
            collect()
        );
        $counts = $allItems->countBy(fn ($account) => $this->duplicateAccountKey($account));

        return array_map(function (Collection $items) use ($counts) {
            return $items->map(function ($account) use ($counts) {
                $key = $this->duplicateAccountKey($account);
                if (($counts[$key] ?? 0) > 1) {
                    $suffix = ' — ' . $this->branchDisplayLabel($account);
                    if (isset($account->_display_name) && trim((string) $account->_display_name) !== '') {
                        $account->_display_name = trim((string) $account->_display_name) . $suffix;
                    } else {
                        $account->name = trim((string) ($account->name ?? '')) . $suffix;
                    }
                }

                return $account;
            })->values();
        }, $collections);
    }

    private function prepareStatementPresentation(
        Collection $accounts,
        Request $request,
        Carbon $reportDate,
        array $activeBranch,
        string $method,
        float $openingDifference,
        bool $consolidate
    ): array {
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';
        $totalRevenue = $accounts->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'revenue')->sum('balance');
        $totalExpenses = $accounts->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'expense')->sum('balance');
        $retainedEarnings = round($totalRevenue - $totalExpenses, 2);
        $netIncome = $retainedEarnings;

        $assetAccounts = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'asset')
            ->reject(fn ($account) => $this->isDiagnosticReserveAccount($account))
            ->values();
        $liabilityAccounts = $accounts
            ->filter(fn ($account) => $this->accountLooksLikeLiability($account))
            ->reject(fn ($account) => $this->isDiagnosticReserveAccount($account))
            ->values();
        $equityAccounts = $accounts
            ->filter(fn ($account) => $this->normalizeAccountType($account->type ?? null) === 'equity')
            ->reject(fn ($account) => $this->isDiagnosticReserveAccount($account))
            ->values();

        $currentAssets = $assetAccounts->filter(fn ($account) => $this->accountLooksLikeCurrentAsset($account))->values();
        $fixedAssets = $assetAccounts->filter(fn ($account) => $this->accountLooksLikeFixedAsset($account))->values();
        $uncategorizedAssets = $assetAccounts->reject(
            fn ($account) => $currentAssets->contains('id', $account->id) || $fixedAssets->contains('id', $account->id)
        )->values();
        if ($currentAssets->isEmpty() && $fixedAssets->isEmpty()) {
            $currentAssets = $assetAccounts->values();
        } elseif ($uncategorizedAssets->isNotEmpty()) {
            $currentAssets = $currentAssets->concat($uncategorizedAssets)->values();
        }

        $currentLiabilities = $liabilityAccounts
            ->reject(fn ($account) => $this->accountLooksLikeLongTermLiability($account))
            ->values();
        $longTermLiabilities = $liabilityAccounts
            ->filter(fn ($account) => $this->accountLooksLikeLongTermLiability($account))
            ->values();

        $overdraftLines = collect();
        $currentAssets = $currentAssets->filter(function ($account) use (&$overdraftLines) {
            $balance = (float) ($account->balance ?? 0);
            if ($this->isBankOrCashAccount($account) && $balance < -0.005) {
                $overdraftLines->push($this->withBalance(
                    clone $account,
                    abs($balance),
                    ['_overdraft' => true, '_bs_group' => 'Current Liabilities']
                ));
                return false;
            }

            return true;
        })->values();
        if ($overdraftLines->isNotEmpty()) {
            $currentLiabilities = $currentLiabilities->concat($overdraftLines)->values();
        }

        $vendorAdvanceLines = collect();
        $currentLiabilities = $currentLiabilities->filter(function ($account) use (&$vendorAdvanceLines) {
            $balance = (float) ($account->balance ?? 0);
            $name = strtolower(trim((string) ($account->name ?? '')));
            if ($balance < -0.005 && (str_contains($name, 'payable') || str_contains($name, 'accounts pay'))) {
                $vendorAdvanceLines->push($this->withBalance(
                    clone $account,
                    abs($balance),
                    ['_vendor_credit' => true, '_display_name' => 'Supplier Advance', '_bs_group' => 'Current Assets']
                ));
                return false;
            }

            return true;
        })->values();
        if ($vendorAdvanceLines->isNotEmpty()) {
            $currentAssets = $currentAssets->concat($vendorAdvanceLines)->values();
        }

        $equityCapital = $equityAccounts->filter(
            fn ($account) => $this->equityBucketFor($account) === 'Capital'
        )->values();
        $equityRetained = $equityAccounts->filter(
            fn ($account) => $this->equityBucketFor($account) === 'Retained Earnings'
        )->values();
        $equityReserves = $equityAccounts->filter(
            fn ($account) => $this->equityBucketFor($account) === 'Reserves'
        )->values();

        $retainedEarningsLines = collect([
            $this->syntheticLine(
                $retainedEarnings < 0 ? 'Current Year Deficit' : 'Current Year Earnings',
                'Equity',
                $retainedEarnings,
                ['_bs_group' => 'Retained Earnings', '_deficit' => $retainedEarnings < 0]
            ),
        ]);

        [
            'currentAssets' => $currentAssets,
            'fixedAssets' => $fixedAssets,
            'currentLiabilities' => $currentLiabilities,
            'longTermLiabilities' => $longTermLiabilities,
            'equityCapital' => $equityCapital,
            'equityRetained' => $equityRetained,
            'equityReserves' => $equityReserves,
            'retainedEarningsLines' => $retainedEarningsLines,
        ] = $this->applyBranchPresentation([
            'currentAssets' => $currentAssets,
            'fixedAssets' => $fixedAssets,
            'currentLiabilities' => $currentLiabilities,
            'longTermLiabilities' => $longTermLiabilities,
            'equityCapital' => $equityCapital,
            'equityRetained' => $equityRetained,
            'equityReserves' => $equityReserves,
            'retainedEarningsLines' => $retainedEarningsLines,
        ], $isAllBranches, $consolidate);

        $equity = $equityCapital
            ->concat($equityRetained)
            ->concat($equityReserves)
            ->concat($retainedEarningsLines)
            ->values();

        $totalCurrentAssets = round((float) $currentAssets->sum('balance'), 2);
        $totalFixedAssets = round((float) $fixedAssets->sum('balance'), 2);
        $totalAssets = round($totalCurrentAssets + $totalFixedAssets, 2);
        $totalCurrentLiabilities = round((float) $currentLiabilities->sum('balance'), 2);
        $totalLongTermLiabilities = round((float) $longTermLiabilities->sum('balance'), 2);
        $totalLiabilities = round($totalCurrentLiabilities + $totalLongTermLiabilities, 2);
        $totalEquity = round((float) $equity->sum('balance'), 2);
        $statementDifference = round($totalAssets - ($totalLiabilities + $totalEquity), 2);
        $reviewThreshold = max(1000.0, round(abs($totalAssets) * 0.02, 2));

        return [
            'currentAssets' => $currentAssets,
            'fixedAssets' => $fixedAssets,
            'currentLiabilities' => $currentLiabilities,
            'longTermLiabilities' => $longTermLiabilities,
            'equity' => $equity,
            'equityCapital' => $equityCapital,
            'equityRetained' => $equityRetained,
            'equityReserves' => $equityReserves,
            'retainedEarningsLines' => $retainedEarningsLines,
            'totalCurrentAssets' => $totalCurrentAssets,
            'totalFixedAssets' => $totalFixedAssets,
            'totalAssets' => $totalAssets,
            'totalCurrentLiabilities' => $totalCurrentLiabilities,
            'totalLongTermLiabilities' => $totalLongTermLiabilities,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'retainedEarnings' => $retainedEarnings,
            'netIncome' => $retainedEarnings,
            'statementDifference' => $statementDifference,
            'isBalanced' => abs($statementDifference) < 0.01,
            'reconciliationReserveDiagnostic' => $statementDifference,
            'reconciliationReserveThreshold' => $reviewThreshold,
            'reconciliationReserveNeedsReview' => abs($statementDifference) >= $reviewThreshold,
        ];
    }

    public function index(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $reportDate = $request->date ? Carbon::parse($request->date) : Carbon::now();
        $method    = in_array($request->input('accounting_method'), ['cash', 'accrual'], true)
            ? $request->input('accounting_method') : 'accrual';
        $compareTo = in_array($request->input('compare_to'), ['previous_period', 'previous_year'], true)
            ? $request->input('compare_to') : 'none';
        $consolidate = $request->boolean('consolidate');

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
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch, $accountIds) {
                $branchId = trim((string) ($activeBranch['id'] ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));

                // No branch resolved → show all accounts (same as transaction query).
                if ($branchId === '' && $branchName === '') {
                    return;
                }

                return $query->where(function ($sub) use ($branchId, $branchName, $accountIds) {
                    if ($branchId !== '') {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '') {
                        $sub->orWhere('branch_name', $branchName);
                    }
                    // Include global/system accounts (branch_id IS NULL or '') ONLY when
                    // they already appear in the branch-scoped transaction totals.
                    // This prevents all un-branched COA accounts from bleeding into
                    // every branch's balance sheet with identical opening balances.
                    if (!empty($accountIds)) {
                        $sub->orWhere(function ($inner) use ($accountIds) {
                            $inner->where(function ($b) {
                                $b->whereNull('branch_id')->orWhere('branch_id', '');
                            })->whereIn('id', $accountIds);
                        });
                    }
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

        // 2. Transform balances based strictly on posted ledger movement
        $accounts->transform(function ($account) {
            $dr = ($account->total_debit ?? 0);
            $cr = ($account->total_credit ?? 0);
            $type = $this->normalizeAccountType($account->type ?? null);
            $isDebitNormal = in_array($type, ['asset', 'expense'], true);

            $account->balance = $isDebitNormal
                ? $dr - $cr
                : $cr - $dr;

            return $account;
        })->filter(fn ($account) => abs((float) ($account->balance ?? 0)) > 0.005)->values();

        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';
        $presentation = $this->prepareStatementPresentation(
            $accounts,
            $request,
            $reportDate,
            $activeBranch,
            $method,
            0.0,
            $consolidate
        );

        $currentAssets = $presentation['currentAssets'];
        $fixedAssets = $presentation['fixedAssets'];
        $currentLiabilities = $presentation['currentLiabilities'];
        $longTermLiabilities = $presentation['longTermLiabilities'];
        $equity = $presentation['equity'];
        $equityCapital = $presentation['equityCapital'];
        $equityRetained = $presentation['equityRetained'];
        $equityReserves = $presentation['equityReserves'];
        $retainedEarningsLines = $presentation['retainedEarningsLines'];
        $retainedEarnings = $presentation['retainedEarnings'];
        $netIncome = $presentation['netIncome'];
        $totalCurrentAssets = $presentation['totalCurrentAssets'];
        $totalFixedAssets = $presentation['totalFixedAssets'];
        $totalAssets = $presentation['totalAssets'];
        $totalCurrentLiabilities = $presentation['totalCurrentLiabilities'];
        $totalLongTermLiabilities = $presentation['totalLongTermLiabilities'];
        $totalLiabilities = $presentation['totalLiabilities'];
        $totalEquity = $presentation['totalEquity'];
        $statementDifference = $presentation['statementDifference'];
        $isBalanced = $presentation['isBalanced'];
        $reconciliationReserveDiagnostic = $presentation['reconciliationReserveDiagnostic'];
        $reconciliationReserveThreshold = $presentation['reconciliationReserveThreshold'];
        $reconciliationReserveNeedsReview = $presentation['reconciliationReserveNeedsReview'];

        // ── Unassigned-branch notice (All Branches view only) ─────────────────
        // Transactions entered before branches were configured have branch_id = NULL.
        // They appear in the consolidated view but not in any individual branch view,
        // which causes the consolidated total to exceed the sum of individual branches.
        $unassignedTxnCount = 0;
        if ($isAllBranches) {
            $unassignedQ = Transaction::query()
                ->where('transaction_date', '<=', $reportDate)
                ->where(function ($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', '');
                });
            $this->applyTransactionScope($unassignedQ, $request);
            $unassignedTxnCount = $unassignedQ->count();
        }

        // 6. Comparison period snapshot (if requested)
        $compareData        = null;
        $compareDate        = null;
        $comparePeriodLabel = null;
        if ($compareTo !== 'none') {
            $compareDate = $compareTo === 'previous_year'
                ? $reportDate->copy()->subYear()
                : $reportDate->copy()->startOfMonth()->subDay();
            $comparePeriodLabel = $compareDate->format('F j, Y');
            $compareData = $this->computeComparisonSnapshot($request, $compareDate, $activeBranch, $method);
        }

        if (abs($reconciliationReserveDiagnostic) >= 0.01) {
            Log::warning('BalanceSheet reconciliation diagnostic detected', [
                'report_date' => $reportDate->toDateString(),
                'difference' => $reconciliationReserveDiagnostic,
                'threshold' => $reconciliationReserveThreshold,
                'needs_review' => $reconciliationReserveNeedsReview,
                'branch_scope' => $activeBranch['scope'] ?? 'branch',
                'branch_id' => $activeBranch['id'] ?? null,
                'branch_name' => $activeBranch['name'] ?? null,
            ]);
        }

        $reserveSuspenseDiagnostics = $this->reserveAndSuspenseDiagnostics($request, $reportDate, $activeBranch);
        $openingBalanceValidation = $this->openingBalanceValidationReport($request, $reportDate, $activeBranch);

        // 7. Map variables to match your Blade @foreach calls exactly
        // Load branch list for the branch selector in the filter bar
        $companyIdForBranches = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $branchesJson = $companyIdForBranches > 0 && Schema::hasTable('settings')
            ? (string) (DB::table('settings')->where('key', 'branches_json_company_' . $companyIdForBranches)->value('value') ?? '')
            : '';
        $allBranches = collect(json_decode($branchesJson, true) ?: []);

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
            'activeBranch',
            'allBranches',
            'method',
            'compareTo',
            'compareData',
            'compareDate',
            'comparePeriodLabel',
            'consolidate',
            'isAllBranches',
            'unassignedTxnCount',
            'longTermLiabilities',
            'equityCapital',
            'equityRetained',
            'equityReserves',
            'retainedEarningsLines',
            'totalCurrentLiabilities',
            'totalLongTermLiabilities',
            'isBalanced',
            'reconciliationReserveDiagnostic',
            'reconciliationReserveThreshold',
            'reconciliationReserveNeedsReview',
            'reserveSuspenseDiagnostics',
            'openingBalanceValidation'
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

    private function computeComparisonSnapshot(Request $request, Carbon $date, array $activeBranch, string $method): array
    {
        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return $this->emptySnapshot();
        }

        $consolidate = $request->boolean('consolidate');

        $txnQuery = Transaction::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('transaction_date', '<=', $date)
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($q) use ($activeBranch) {
                $branchId   = trim((string) ($activeBranch['id']   ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));
                return $q->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId   !== '') $sub->where('branch_id',   $branchId);
                    if ($branchName !== '') $sub->orWhere('branch_name', $branchName);
                });
            });
        $this->applyTransactionScope($txnQuery, $request);
        $txnTotals = $txnQuery->groupBy('account_id')->get()->keyBy('account_id');

        $accountIds   = $txnTotals->keys()->all();
        $accountsQuery = Account::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($accountIds) {
                if (!empty($accountIds)) {
                    $q->whereIn('id', $accountIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($q) use ($activeBranch, $accountIds) {
                $branchId   = trim((string) ($activeBranch['id']   ?? ''));
                $branchName = trim((string) ($activeBranch['name'] ?? ''));
                if ($branchId === '' && $branchName === '') return;
                return $q->where(function ($sub) use ($branchId, $branchName, $accountIds) {
                    if ($branchId   !== '') $sub->where('branch_id',   $branchId);
                    if ($branchName !== '') $sub->orWhere('branch_name', $branchName);
                    // Global accounts (no branch) only if they have branch-tagged transactions.
                    if (!empty($accountIds)) {
                        $sub->orWhere(function ($inner) use ($accountIds) {
                            $inner->where(function ($b) {
                                $b->whereNull('branch_id')->orWhere('branch_id', '');
                            })->whereIn('id', $accountIds);
                        });
                    }
                });
            });
        $this->applyAccountScope($accountsQuery, $request);
        $accounts = $accountsQuery->get();

        $accounts->transform(function ($account) use ($txnTotals) {
            $totals  = $txnTotals->get($account->id);
            $dr      = (float) ($totals->total_debit  ?? 0);
            $cr      = (float) ($totals->total_credit ?? 0);
            $type    = $this->normalizeAccountType($account->type ?? null);
            $isDebitNormal = in_array($type, ['asset', 'expense'], true);
            $account->balance = $isDebitNormal ? ($dr - $cr) : ($cr - $dr);
            return $account;
        })->filter(fn ($account) => abs((float) ($account->balance ?? 0)) > 0.005)->values();

        $snapshot = $this->prepareStatementPresentation(
            $accounts,
            $request,
            $date,
            $activeBranch,
            $method,
            0.0,
            $consolidate
        );

        return [
            'currentAssets' => $snapshot['currentAssets'],
            'fixedAssets' => $snapshot['fixedAssets'],
            'currentLiabilities' => $snapshot['currentLiabilities'],
            'longTermLiabilities' => $snapshot['longTermLiabilities'],
            'liabilities' => $snapshot['currentLiabilities']->concat($snapshot['longTermLiabilities'])->values(),
            'equity' => $snapshot['equity'],
            'equityCapital' => $snapshot['equityCapital'],
            'equityRetained' => $snapshot['equityRetained'],
            'equityReserves' => $snapshot['equityReserves'],
            'retainedEarningsLines' => $snapshot['retainedEarningsLines'],
            'netIncome' => $snapshot['netIncome'],
            'totalCurrentAssets' => $snapshot['totalCurrentAssets'],
            'totalFixedAssets' => $snapshot['totalFixedAssets'],
            'totalAssets' => $snapshot['totalAssets'],
            'totalCurrentLiabilities' => $snapshot['totalCurrentLiabilities'],
            'totalLongTermLiabilities' => $snapshot['totalLongTermLiabilities'],
            'totalLiabilities' => $snapshot['totalLiabilities'],
            'totalEquity' => $snapshot['totalEquity'],
            'reconciliationReserveDiagnostic' => $snapshot['reconciliationReserveDiagnostic'],
            'reconciliationReserveNeedsReview' => $snapshot['reconciliationReserveNeedsReview'],
        ];
    }

    private function emptySnapshot(): array
    {
        $empty = collect();
        return [
            'currentAssets'      => $empty,
            'fixedAssets'        => $empty,
            'liabilities'        => $empty,
            'currentLiabilities' => $empty,
            'longTermLiabilities' => $empty,
            'equity'             => $empty,
            'equityCapital'      => $empty,
            'equityRetained'     => $empty,
            'equityReserves'     => $empty,
            'retainedEarningsLines' => $empty,
            'netIncome'          => 0.0,
            'totalCurrentAssets' => 0.0,
            'totalFixedAssets'   => 0.0,
            'totalAssets'        => 0.0,
            'totalCurrentLiabilities' => 0.0,
            'totalLongTermLiabilities' => 0.0,
            'totalLiabilities'   => 0.0,
            'totalEquity'        => 0.0,
            'reconciliationReserveDiagnostic' => 0.0,
            'reconciliationReserveNeedsReview' => false,
        ];
    }

    private function applyBranchScopeToTransactionsQuery($query, array $activeBranch): void
    {
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return;
        }

        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($sub) use ($branchId, $branchName) {
            if ($branchId !== '') {
                $sub->where('branch_id', $branchId);
            }
            if ($branchName !== '') {
                $method = $branchId !== '' ? 'orWhere' : 'where';
                $sub->{$method}('branch_name', $branchName);
            }
        });
    }

    private function reserveAndSuspenseDiagnostics(Request $request, Carbon $reportDate, array $activeBranch): Collection
    {
        if (!Schema::hasTable('transactions') || !Schema::hasTable('accounts')) {
            return collect();
        }

        $query = Transaction::query()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('transactions.transaction_date', '<=', $reportDate)
            ->where(function ($sub) {
                $sub->whereRaw('LOWER(accounts.name) like ?', ['%reconciliation%'])
                    ->orWhereRaw('LOWER(accounts.name) like ?', ['%suspense%'])
                    ->orWhereRaw('LOWER(accounts.sub_type) like ?', ['%reconciliation%'])
                    ->orWhereRaw('LOWER(accounts.sub_type) like ?', ['%suspense%'])
                    ->orWhereRaw('LOWER(accounts.code) like ?', ['%recon%'])
                    ->orWhereRaw('LOWER(accounts.code) like ?', ['%susp%']);
            })
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.reference',
                'transactions.transaction_type',
                'transactions.related_id',
                'transactions.related_type',
                'transactions.description',
                'transactions.debit',
                'transactions.credit',
                'transactions.branch_id',
                'transactions.branch_name',
                'accounts.name as account_name',
                'accounts.code as account_code',
            ])
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.id');

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($request->user()?->id ?? 0);
        if ($companyId > 0 && Schema::hasColumn('transactions', 'company_id')) {
            $query->where('transactions.company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('transactions', 'user_id')) {
            $query->where('transactions.user_id', $userId);
        }

        if (($activeBranch['scope'] ?? 'branch') !== 'all') {
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if ($branchId !== '' || $branchName !== '') {
                $query->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '') {
                        $sub->where('transactions.branch_id', $branchId);
                    }
                    if ($branchName !== '') {
                        $method = $branchId !== '' ? 'orWhere' : 'where';
                        $sub->{$method}('transactions.branch_name', $branchName);
                    }
                });
            }
        }

        return $query->get();
    }

    private function openingBalanceValidationReport(Request $request, Carbon $reportDate, array $activeBranch): array
    {
        $customerPostedRows = collect();
        $supplierPostedRows = collect();

        if (Schema::hasTable('transactions')) {
            $customerPostedQuery = Transaction::withoutGlobalScopes()
                ->selectRaw('reference, related_id, COUNT(*) as entry_count, SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'CUST-OB-%')
                ->where('transaction_date', '<=', $reportDate)
                ->groupBy('reference', 'related_id');
            $this->applyTransactionScope($customerPostedQuery, $request);
            $this->applyBranchScopeToTransactionsQuery($customerPostedQuery, $activeBranch);
            $customerPostedRows = $customerPostedQuery->get();

            $supplierPostedQuery = Transaction::withoutGlobalScopes()
                ->selectRaw('reference, related_id, COUNT(*) as entry_count, SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('reference', 'like', 'SUPP-OB-%')
                ->where('transaction_date', '<=', $reportDate)
                ->groupBy('reference', 'related_id');
            $this->applyTransactionScope($supplierPostedQuery, $request);
            $this->applyBranchScopeToTransactionsQuery($supplierPostedQuery, $activeBranch);
            $supplierPostedRows = $supplierPostedQuery->get();
        }

        return [
            'unposted_customer_opening_balance' => round($this->getUnpostedCustomerOpeningBalanceSum($request, $reportDate, $activeBranch), 2),
            'unposted_supplier_opening_balance' => round($this->getUnpostedSupplierOpeningBalanceSum($request, $reportDate, $activeBranch), 2),
            'legacy_inventory_bridge' => round($this->getLegacyInventoryBridgeAmount($request, $reportDate, collect(), $activeBranch), 2),
            'duplicate_customer_refs' => $customerPostedRows->filter(fn ($row) => (int) ($row->entry_count ?? 0) > 2)->values(),
            'duplicate_supplier_refs' => $supplierPostedRows->filter(fn ($row) => (int) ($row->entry_count ?? 0) > 2)->values(),
            'imbalanced_customer_refs' => $customerPostedRows->filter(fn ($row) => abs((float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0)) >= 0.01)->values(),
            'imbalanced_supplier_refs' => $supplierPostedRows->filter(fn ($row) => abs((float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0)) >= 0.01)->values(),
        ];
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

        $activeBranch = $this->resolveActiveBranch($request);
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            $query->where(function ($branchScoped) {
                if (Schema::hasColumn('accounts', 'branch_id')) {
                    $branchScoped->whereNotNull('branch_id')
                        ->where('branch_id', '<>', '');
                }

                if (Schema::hasColumn('accounts', 'branch_name')) {
                    $method = Schema::hasColumn('accounts', 'branch_id') ? 'orWhere' : 'where';
                    $branchScoped->{$method}(function ($named) {
                        $named->whereNotNull('branch_name')
                            ->where('branch_name', '<>', '');
                    });
                }
            });
        }

        return $query;
    }

    /**
     * Sum customer opening balances (customers.balance > 0) that do NOT yet
     * have a journal entry in the transactions table (reference CUST-OB-*).
     * This bridges existing customers who pre-date the journal-entry workflow.
     */
    private function getUnpostedCustomerOpeningBalanceSum(Request $request, $reportDate, array $activeBranch = []): float
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'balance')) {
            return 0.0;
        }

        $companyId = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId    = (int) ($request->user()?->id ?? 0);
        $branchId  = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';

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

        // Branch scope: filter by branch_id/branch_name if customers table supports it.
        if (!$isAllBranches && ($branchId !== '' || $branchName !== '') && Schema::hasColumn('customers', 'branch_id')) {
            $customerQuery->where(function ($q) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $q->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('customers', 'branch_name')) {
                    $q->orWhere('branch_name', $branchName);
                }
            });
        }

        if (!empty($postedCustomerIds)) {
            $customerQuery->whereNotIn('id', $postedCustomerIds);
        }

        return (float) $customerQuery->sum('balance');
    }

    private function getUnpostedSupplierOpeningBalanceSum(Request $request, $reportDate, array $activeBranch = []): float
    {
        if (!Schema::hasTable('suppliers') || !Schema::hasColumn('suppliers', 'opening_balance')) {
            return 0.0;
        }

        $companyId  = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId     = (int) ($request->user()?->id ?? 0);
        $branchId   = trim((string) ($activeBranch['id']   ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';

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

        // Branch scope: filter by branch_id/branch_name if suppliers table supports it.
        if (!$isAllBranches && ($branchId !== '' || $branchName !== '') && Schema::hasColumn('suppliers', 'branch_id')) {
            $supplierQuery->where(function ($q) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $q->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('suppliers', 'branch_name')) {
                    $q->orWhere('branch_name', $branchName);
                }
            });
        }

        if (!empty($postedSupplierIds)) {
            $supplierQuery->whereNotIn('id', $postedSupplierIds);
        }

        return (float) $supplierQuery->sum('opening_balance');
    }

    private function getLegacyInventoryBridgeAmount(Request $request, $reportDate, $accounts, array $activeBranch = []): float
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

        $companyId  = (int) ($request->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId     = (int) ($request->user()?->id ?? 0);
        $branchId   = trim((string) ($activeBranch['id']   ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        $isAllBranches = ($activeBranch['scope'] ?? 'branch') === 'all';
        $productQuery = DB::table('products')
            ->where('stock', '>', 0)
            ->selectRaw("SUM(COALESCE(stock, 0) * COALESCE({$priceColumn}, 0)) as inventory_value");

        if ($companyId > 0 && Schema::hasColumn('products', 'company_id')) {
            $productQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('products', 'user_id')) {
            $productQuery->where('user_id', $userId);
        }

        // Branch scope: filter products by branch_id if the column exists.
        if (!$isAllBranches && ($branchId !== '' || $branchName !== '') && Schema::hasColumn('products', 'branch_id')) {
            $productQuery->where(function ($q) use ($branchId, $branchName) {
                if ($branchId !== '') {
                    $q->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                    $q->orWhere('branch_name', $branchName);
                }
                // Include products with no branch assignment
                $q->orWhereNull('branch_id')->orWhere('branch_id', '');
            });
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
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->tap(fn ($q) => $this->applyAccountScope($q, $request))->get()
            ->transform(function ($a) use ($txnTotals) {
                $t = $txnTotals->get($a->id);
                $a->total_debit  = (float)($t->total_debit ?? 0);
                $a->total_credit = (float)($t->total_credit ?? 0);
                $type = $this->normalizeAccountType($a->type ?? null);
                $isDebit = in_array($type, ['asset', 'expense'], true);
                $a->balance = $isDebit
                    ? ($a->total_debit - $a->total_credit)
                    : ($a->total_credit - $a->total_debit);
                return $a;
            })->filter(fn ($a) => abs((float) ($a->balance ?? 0)) > 0.005)->values();

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
                    $a->balance = $isDebit ? ($dr - $cr) : ($cr - $dr);
                    return $a;
                })->filter(fn ($a) => abs((float) ($a->balance ?? 0)) > 0.005)->values();

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
