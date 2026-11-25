<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Support\ScopesFinancialStatements;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GeneralLedgerController extends Controller
{
    use ScopesFinancialStatements;

    public function index(Request $request)
    {
        $activeBranch = $this->resolveActiveBranch($request);
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'account_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);
        $endDate = Carbon::parse($filters['end_date'] ?? now())->endOfDay();
        $startDate = Carbon::parse($filters['start_date'] ?? $endDate->copy()->startOfMonth())->startOfDay();
        if ($endDate->lt($startDate)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_date' => 'The end date must be on or after the start date.']);
        }

        if (! Schema::hasTable('accounts') || ! Schema::hasTable('transactions')) {
            return view('Reports.Reports.general-ledger', [
                'message' => 'Accounting tables are missing. Run migrations to enable General Ledger.',
                'entries' => collect(),
                'accounts' => collect(),
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
                'totals' => ['debit' => 0, 'credit' => 0],
                'selectedAccountId' => null,
                'search' => '',
            ]);
        }

        $query = Transaction::withoutGlobalScopes()->whereNull('transactions.deleted_at')
            ->where('transaction_date', '>=', $startDate->toDateString())
            ->where('transaction_date', '<', $endDate->copy()->addDay()->toDateString());
        $this->applyTransactionScope($query, $request);
        $this->applyLegacyOpeningBalanceBranchScope($query, $activeBranch);

        $accountsQuery = Account::withoutGlobalScopes()->select('accounts.id', 'accounts.code', 'accounts.name');
        $this->applyAccountScope($accountsQuery, $request);
        $this->applyReportAccountVisibility($accountsQuery, $query, $activeBranch);
        $accounts = $accountsQuery->orderBy('code')->orderBy('name')->get();

        $query->whereIn('account_id', $accounts->modelKeys())
            ->with(['account' => fn ($account) => $account->withoutGlobalScopes()->whereIn('accounts.id', $accounts->modelKeys())]);
        $selectedAccountId = $filters['account_id'] ?? null;
        if ($selectedAccountId) {
            $query->where('account_id', $selectedAccountId);
        }

        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $searchTerm = mb_strtolower($search);
            $matchingAccounts = $accounts->filter(fn ($account) => str_contains(mb_strtolower((string) $account->name), $searchTerm)
                || str_contains(mb_strtolower((string) $account->code), $searchTerm));
            $query->where(function ($q) use ($search, $matchingAccounts) {
                $q->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereIn('account_id', $matchingAccounts->modelKeys());
            });
        }

        $totals = [
            'debit' => (float) (clone $query)->sum('debit'),
            'credit' => (float) (clone $query)->sum('credit'),
        ];

        $entries = $query->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(\App\Support\PageSize::resolve())->withQueryString();

        return view('Reports.Reports.general-ledger', [
            'entries' => $entries,
            'accounts' => $accounts,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totals' => $totals,
            'selectedAccountId' => $selectedAccountId,
            'search' => $search,
            'activeBranch' => $activeBranch,
        ]);
    }
}
