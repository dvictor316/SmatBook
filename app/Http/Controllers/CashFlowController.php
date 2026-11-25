<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Transaction;
use App\Support\ScopesFinancialStatements;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashFlowController extends Controller
{
    use ScopesFinancialStatements;

    public function cashFlow(Request $request)
    {
        $report = $this->reportData($request);
        $ledger = $report['ledger'];
        $inflows = $report['inflows'];
        $outflows = $report['outflows'];

        $report['inflows'] = $ledger
            ? $inflows->paginate(\App\Support\PageSize::resolve(), ['*'], 'inflow_page')->withQueryString()
            : $this->paginateCollection($inflows, 'inflow_page', $request);
        $report['outflows'] = $ledger
            ? $outflows->paginate(\App\Support\PageSize::resolve(), ['*'], 'outflow_page')->withQueryString()
            : $this->paginateCollection($outflows, 'outflow_page', $request);
        $report['chartData'] = $ledger
            ? $this->getMonthlyTrend($ledger, $report['end'])
            : $this->getMonthlyTrendFromOperations($report['end']);

        return view('Reports.Reports.cash-flow', $report);
    }

    public function exportCashFlow(Request $request): StreamedResponse
    {
        [
            'start' => $start,
            'end' => $end,
            'openingBalance' => $openingBalance,
            'inflows' => $inflows,
            'outflows' => $outflows,
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'netCashFlow' => $netCashFlow,
            'closingBalance' => $closingBalance,
            'ledger' => $ledger,
            'accountLabel' => $accountLabel,
            'unpostedOpeningAccounts' => $unpostedOpeningAccounts,
        ] = $this->reportData($request);

        if ($ledger) {
            $inflows = $inflows->lazy();
            $outflows = $outflows->lazy();
        }

        $filename = 'cash_flow_'.$start->format('Ymd').'_'.$end->format('Ymd').'.csv';

        return response()->streamDownload(function () use (
            $start,
            $end,
            $openingBalance,
            $inflows,
            $outflows,
            $totalInflow,
            $totalOutflow,
            $netCashFlow,
            $closingBalance,
            $accountLabel,
            $ledger,
            $unpostedOpeningAccounts
        ) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Cash Flow Report']);
            fputcsv($out, ['Period', $start->toDateString().' to '.$end->toDateString()]);
            fputcsv($out, []);
            fputcsv($out, ['Account', $accountLabel]);
            fputcsv($out, ['Source', $ledger ? 'Posted ledger' : 'Estimate from payments and paid expenses']);
            fputcsv($out, ['Summary']);
            fputcsv($out, ['Opening Balance', $openingBalance]);
            fputcsv($out, ['Total Inflows', $totalInflow]);
            fputcsv($out, ['Total Outflows', $totalOutflow]);
            fputcsv($out, ['Net Cash Flow', $netCashFlow]);
            fputcsv($out, ['Closing Balance', $closingBalance]);
            foreach ($unpostedOpeningAccounts as $opening) {
                fputcsv($out, ['Unposted opening for review', $opening['code'], $opening['amount']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Inflows']);
            fputcsv($out, ['Date', 'Description', 'Amount', 'Account']);
            foreach ($inflows as $item) {
                fputcsv($out, [
                    Carbon::parse($item->transaction_date)->toDateString(),
                    $item->description ?? ($item->account ?? null)?->name ?? '',
                    $item->flow_amount ?? $item->debit,
                    ($item->account ?? null)?->name ?? '',
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Outflows']);
            fputcsv($out, ['Date', 'Description', 'Amount', 'Account']);
            foreach ($outflows as $item) {
                fputcsv($out, [
                    Carbon::parse($item->transaction_date)->toDateString(),
                    $item->description ?? ($item->account ?? null)?->name ?? '',
                    $item->flow_amount ?? $item->credit,
                    ($item->account ?? null)?->name ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
        $userId = (int) (Auth::id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
        }

        return $query;
    }

    private function getActiveBranchContext(): array
    {
        if (session('active_branch_scope') === 'all') {
            return ['id' => null, 'name' => 'All Branches', 'scope' => 'all'];
        }

        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
            'scope' => 'branch',
        ];
    }

    private function applyBranchScope($query, string $table, bool $includeCompanyWide = false)
    {
        $activeBranch = $this->getActiveBranchContext();
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return $query;
        }
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        $hasBranchId = Schema::hasColumn($table, 'branch_id');
        $hasBranchName = Schema::hasColumn($table, 'branch_name');

        return $query->where(function ($sub) use ($table, $branchId, $branchName, $includeCompanyWide, $hasBranchId, $hasBranchName) {
            if ($branchId !== '' && $hasBranchId) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && $hasBranchName) {
                $sub->orWhere(function ($legacy) use ($table, $branchId, $branchName, $hasBranchId) {
                    $legacy->where("{$table}.branch_name", $branchName);
                    if ($branchId !== '' && $hasBranchId) {
                        $legacy->whereRaw("COALESCE({$table}.branch_id, '') = ''");
                    }
                });
            }
            if ($includeCompanyWide) {
                $sub->orWhere(function ($companyWide) use ($table, $hasBranchId, $hasBranchName) {
                    if ($hasBranchId) {
                        $companyWide->whereRaw("COALESCE({$table}.branch_id, '') = ''");
                    }
                    if ($hasBranchName) {
                        $companyWide->whereRaw("COALESCE({$table}.branch_name, '') = ''");
                    }
                });
            }
        });
    }

    private function reportData(Request $request): array
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', ...($request->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            'account' => ['nullable', 'string', 'max:32'],
        ]);

        $end = Carbon::parse($filters['end_date'] ?? now())->endOfDay();
        $start = Carbon::parse($filters['start_date'] ?? $end->copy()->startOfMonth())->startOfDay();
        if ($end->lt($start)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_date' => 'The end date must be on or after the start date.']);
        }
        $accountFilter = $filters['account'] ?? '';
        $activeBranch = $this->resolveActiveBranch($request);
        $cashPostings = Transaction::withoutGlobalScopes()->whereNull('transactions.deleted_at');
        $this->applyTransactionScope($cashPostings, $request);
        $this->applyLegacyOpeningBalanceBranchScope($cashPostings, $activeBranch);
        $accounts = $this->cashAccounts($request, $activeBranch, (clone $cashPostings)->where('transaction_date', '<', $end->copy()->addDay()->toDateString()));

        $selectedAccounts = match ($accountFilter) {
            '' => $accounts,
            'cash' => $accounts->filter(fn (Account $account) => $account->cashOrBankType() === 'Cash'),
            'bank' => $accounts->filter(fn (Account $account) => $account->cashOrBankType() !== 'Cash'),
            default => $accounts->filter(fn (Account $account) => (string) $account->id === $accountFilter),
        };

        abort_if($accountFilter !== '' && !in_array($accountFilter, ['cash', 'bank'], true) && $selectedAccounts->isEmpty(), 404);
        $accountLabel = match ($accountFilter) {
            '' => 'All Cash & Bank Accounts',
            'cash' => 'Cash Accounts',
            'bank' => 'Bank & Clearing Accounts',
            default => $selectedAccounts->first()->name,
        };

        $ledger = null;
        $unpostedOpeningAccounts = collect();
        if ($accounts->isNotEmpty() || $accountFilter !== '') {
            $ledger = $cashPostings->whereIn('account_id', $selectedAccounts->modelKeys());
            $unpostedOpeningAccounts = $this->unpostedAccountOpenings($request, $end, $activeBranch)
                ->whereIn('id', $selectedAccounts->modelKeys());
            $priorEntries = (clone $ledger)->where('transaction_date', '<', $start->toDateString());
            $openingBalance = $this->sumMovement($priorEntries, 'debit - credit');

            $period = (clone $ledger)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<', $end->copy()->addDay()->toDateString())
                ->with(['account' => fn ($query) => $query->withoutGlobalScopes()->whereIn('accounts.id', $selectedAccounts->modelKeys())])
                ->orderBy('transaction_date')
                ->orderBy('id');

            $inflows = (clone $period)->whereColumn('debit', '>', 'credit')
                ->selectRaw('transactions.*, debit - credit AS flow_amount');
            $outflows = (clone $period)->whereColumn('credit', '>', 'debit')
                ->selectRaw('transactions.*, credit - debit AS flow_amount');

            $totalInflow = $this->sumMovement($inflows, 'debit - credit');
            $totalOutflow = $this->sumMovement($outflows, 'credit - debit');
        } else {
            $openingBalance = $this->operationalOpeningBalance($start);
            [$inflows, $outflows] = $this->buildOperationalFlows($start, $end);
            $totalInflow = $inflows->sum('debit');
            $totalOutflow = $outflows->sum('credit');
        }

        $netCashFlow = round($totalInflow - $totalOutflow, 2);
        $closingBalance = round($openingBalance + $netCashFlow, 2);

        return compact(
            'start', 'end', 'accountFilter', 'accountLabel', 'accounts', 'ledger', 'openingBalance',
            'inflows', 'outflows', 'totalInflow', 'totalOutflow', 'netCashFlow', 'closingBalance',
            'unpostedOpeningAccounts'
        );
    }

    private function cashAccounts(Request $request, array $branch, $postings): Collection
    {
        $accounts = Account::withoutGlobalScopes()->ofType(Account::TYPE_ASSET)->orderBy('name');
        $this->applyAccountScope($accounts, $request);
        $this->applyReportAccountVisibility($accounts, $postings, $branch);

        return $accounts->get()->filter(fn (Account $account) => $account->cashOrBankType() !== null);
    }

    private function sumMovement($query, string $expression): float
    {
        return (float) (clone $query)->reorder()->select(DB::raw("SUM($expression) AS total"))->toBase()->value('total');
    }

    private function getMonthlyTrend($ledger, Carbon $end): array
    {
        $labels = [];
        $inflows = [];
        $outflows = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $end->copy()->startOfMonth()->subMonths($i);
            $next = $i === 0 ? $end->copy()->addDay() : $month->copy()->addMonth();
            $labels[] = $month->format('M Y');
            $period = (clone $ledger)->where('transaction_date', '>=', $month->toDateString())
                ->where('transaction_date', '<', $next->toDateString());
            $inflows[] = $this->sumMovement((clone $period)->whereColumn('debit', '>', 'credit'), 'debit - credit');
            $outflows[] = $this->sumMovement((clone $period)->whereColumn('credit', '>', 'debit'), 'credit - debit');
        }

        return compact('labels', 'inflows', 'outflows');
    }

    private function operationalOpeningBalance(Carbon $start): float
    {
        $payments = $this->applyTenantScope(Payment::query(), 'payments')
            ->tap(fn ($query) => $this->applyBranchScope($query, 'payments'))
            ->where('created_at', '<', $start)
            ->where(function ($query) {
                $query->whereRaw('LOWER(COALESCE(status, "")) IN (?, ?, ?, ?)', ['completed', 'success', 'successful', 'paid']);
            })
            ->sum('amount');

        $expenses = $this->applyTenantScope(Expense::query(), 'expenses')
            ->tap(fn ($query) => $this->applyBranchScope($query, 'expenses'))
            ->where('created_at', '<', $start)
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['paid'])
            ->sum('amount');

        return (float) $payments - (float) $expenses;
    }

    private function buildOperationalFlows(Carbon $start, Carbon $end): array
    {
        $paymentsQuery = $this->applyTenantScope(Payment::query(), 'payments')
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()]);
        $this->applyBranchScope($paymentsQuery, 'payments');

        $paymentsQuery->where(function ($query) {
            $query->whereRaw('LOWER(status) in (?, ?, ?)', ['completed', 'success', 'successful'])
                ->orWhereRaw('LOWER(status) = ?', ['paid']);
        });

        $paymentRows = $paymentsQuery->get()->map(function ($payment) {
            return (object) [
                'transaction_date' => optional($payment->created_at)->toDateString(),
                'description' => $payment->reference ?? $payment->sale?->invoice_no ?? 'Payment received',
                'debit' => (float) $payment->amount,
                'credit' => 0,
            ];
        });

        $expenseRows = $this->applyTenantScope(Expense::query(), 'expenses')
            ->tap(fn ($query) => $this->applyBranchScope($query, 'expenses'))
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->whereRaw('LOWER(status) = ?', ['paid'])
            ->get()
            ->map(function ($expense) {
                return (object) [
                    'transaction_date' => optional($expense->created_at)->toDateString(),
                    'description' => $expense->reference ?? $expense->category ?? 'Expense payment',
                    'debit' => 0,
                    'credit' => (float) $expense->amount,
                ];
            });

        return [$paymentRows, $expenseRows];
    }

    private function paginateCollection($items, string $pageName, Request $request): LengthAwarePaginator
    {
        $perPage = \App\Support\PageSize::resolve();
        $page = (int) $request->query($pageName, 1);
        $items = collect($items);
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
                'query' => $request->query(),
            ]
        );
    }

    private function getMonthlyTrendFromOperations(Carbon $end): array
    {
        $labels = [];
        $inflows = [];
        $outflows = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $end->copy()->startOfMonth()->subMonths($i);
            $next = $i === 0 ? $end->copy()->addDay() : $month->copy()->addMonth();
            $labels[] = $month->format('M Y');

            $inflows[] = $this->applyTenantScope(Payment::query(), 'payments')
                ->tap(fn ($query) => $this->applyBranchScope($query, 'payments'))
                ->where('created_at', '>=', $month->toDateString())
                ->where('created_at', '<', $next->toDateString())
                ->where(function ($query) {
                    $query->whereRaw('LOWER(status) in (?, ?, ?)', ['completed', 'success', 'successful'])
                        ->orWhereRaw('LOWER(status) = ?', ['paid']);
                })
                ->sum('amount');

            $outflows[] = $this->applyTenantScope(Expense::query(), 'expenses')
                ->tap(fn ($query) => $this->applyBranchScope($query, 'expenses'))
                ->where('created_at', '>=', $month->toDateString())
                ->where('created_at', '<', $next->toDateString())
                ->whereRaw('LOWER(status) = ?', ['paid'])
                ->sum('amount');
        }

        return ['labels' => $labels, 'inflows' => $inflows, 'outflows' => $outflows];
    }
}
