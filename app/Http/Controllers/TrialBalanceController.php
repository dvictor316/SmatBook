<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Transaction;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrialBalanceExport;
use App\Support\ScopesFinancialStatements;

class TrialBalanceController extends Controller
{
    use ScopesFinancialStatements;

    private function isDebitNormalAccount(object $account): bool
    {
        $type = $this->normalizeAccountType($account->type ?? null);
        return !in_array($type, ['liability', 'equity', 'revenue'], true);
    }

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

    public function index(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $dates = $request->validate([
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $end = isset($dates['end_date']) ? Carbon::parse($dates['end_date'])->endOfDay() : now()->endOfDay();
        $start = isset($dates['start_date']) ? Carbon::parse($dates['start_date']) : $end->copy()->startOfMonth();

        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return view('Reports.Reports.trial-balance', [
                'message' => 'Accounting tables are missing.',
                'startDate' => $start->toDateString(),
                'endDate' => $end->toDateString(),
                'accounts' => collect(),
                'totalDebits' => 0,
                'totalCredits' => 0,
                'activeBranch' => $activeBranch,
            ]);
        }

        // 3. Get Account Data with Summed Transactions (Optimized + Branch-safe)
        $txnTotalsQuery = Transaction::withoutGlobalScopes()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereNull('deleted_at')
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch, 'transactions');
            });
        $this->applyTransactionScope($txnTotalsQuery, $request);

        $txnTotals = $txnTotalsQuery
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $ledgerTotalsQuery = Transaction::withoutGlobalScopes()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereNull('deleted_at')
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch, 'transactions');
            });
        $this->applyTransactionScope($ledgerTotalsQuery, $request);

        $ledgerTotals = $ledgerTotalsQuery->first();
        $ledgerDebits = (float) ($ledgerTotals->total_debit ?? 0);
        $ledgerCredits = (float) ($ledgerTotals->total_credit ?? 0);
        $ledgerDifference = $ledgerDebits - $ledgerCredits;

        $imbalancedEntriesQuery = Transaction::withoutGlobalScopes()
            ->selectRaw('MIN(id) as transaction_id, related_type, related_id, transaction_type, reference, MIN(description) as description, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereNull('deleted_at')
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->when(($activeBranch['scope'] ?? 'branch') !== 'all', function ($query) use ($activeBranch) {
                $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch, 'transactions');
            });
        $this->applyTransactionScope($imbalancedEntriesQuery, $request);

        $imbalancedEntries = $imbalancedEntriesQuery
            ->groupBy('related_type', 'related_id', 'transaction_type', 'reference')
            ->havingRaw('ABS(SUM(debit) - SUM(credit)) > 0.01')
            ->orderByRaw('ABS(SUM(debit) - SUM(credit)) DESC')
            ->limit(10)
            ->get();

        $unpostedOpeningAccounts = $this->unpostedAccountOpenings($request, $end, $activeBranch);
        $accountIds = $txnTotals->keys()->all();
        // Use withoutGlobalScopes() to bypass TenantScoped's strict branch filter.
        // System-created accounts (AR, Revenue, Petty Cash) may have branch_id = ''
        // and would otherwise be excluded. We include them as global accounts.
        $accountsQuery = Account::withoutGlobalScopes()
            ->where(function ($query) use ($accountIds) {
                if (!empty($accountIds)) {
                    $query->whereIn('id', $accountIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            });
        $this->applyAccountScope($accountsQuery, $request);

        $accounts = $accountsQuery->get()->map(function ($account) use ($txnTotals) {
            $totals = $txnTotals->get($account->id);
            $dr = (float) ($totals->total_debit ?? 0);
            $cr = (float) ($totals->total_credit ?? 0);
            $isDebitNormal = $this->isDebitNormalAccount($account);

            $net = $isDebitNormal ? $dr - $cr : $cr - $dr;

            $netSide = $this->calculateSideBalances($net, $isDebitNormal);
            $account->debit_balance = $netSide['debit'];
            $account->credit_balance = $netSide['credit'];
            $account->has_activity = $dr != 0 || $cr != 0;
            return $account;
        })
        ->filter(fn ($account) => $account->has_activity)
        ->sortBy('code')
        ->values();

        $totalDebits = $accounts->sum('debit_balance');
        $totalCredits = $accounts->sum('credit_balance');
        return view('Reports.Reports.trial-balance', [
            'startDate'    => $start->toDateString(),
            'endDate'      => $end->toDateString(),
            'reportDate'   => $end, // Added fallback for header logic
            'accounts'     => $accounts,
            'totalDebits'  => $totalDebits,
            'totalCredits' => $totalCredits,
            'ledgerDebits' => $ledgerDebits,
            'ledgerCredits' => $ledgerCredits,
            'ledgerDifference' => $ledgerDifference,
            'statementDifference' => round($totalDebits - $totalCredits, 2),
            'imbalancedEntries' => $imbalancedEntries,
            'unpostedOpeningAccounts' => $unpostedOpeningAccounts,
            'activeBranch' => $activeBranch,
            'branchScope'  => $activeBranch['scope'] ?? 'branch',
        ]);
    }

    public function export(Request $request)
    {
        $report = $this->index($request)->getData();

        return Excel::download(
            new TrialBalanceExport(
                $report['accounts'],
                $report['totalDebits'],
                $report['totalCredits'],
                $report['unpostedOpeningAccounts'] ?? collect(),
            ),
            'trial_balance_' . ($report['endDate'] ?? now()->toDateString()) . '.xlsx'
        );
    }
}
