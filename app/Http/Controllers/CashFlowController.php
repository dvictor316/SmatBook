<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashFlowController extends Controller
{
    public function cashFlow(Request $request)
    {
        // 1. Setup Dates (Matches your $start->format calls in Blade)
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        // 2. Identify Cash/Bank Accounts
        $cashAccountIds = $this->resolveCashAccountIds();

        // 3. Calculate Opening Balance (Net sum of transactions before the period)
        $openingBalance = 0;
        if ($cashAccountIds->isNotEmpty()) {
            $openingBalance = $this->applyTenantScope(
                Transaction::whereIn('account_id', $cashAccountIds),
                'transactions'
            )
                ->tap(fn ($query) => $this->applyBranchScope($query, 'transactions'))
                ->where('transaction_date', '<', $start->toDateString())
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->first()->balance ?? 0;
        }

        // 4. Period Transactions
        $transactions = collect();
        if ($cashAccountIds->isNotEmpty()) {
            $transactions = $this->applyTenantScope(
                Transaction::whereIn('account_id', $cashAccountIds),
                'transactions'
            )
                ->tap(fn ($query) => $this->applyBranchScope($query, 'transactions'))
                ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->with('account')
                ->orderBy('transaction_date', 'asc')
                ->get();
        }

        // Separate Inflows and Outflows for the loops in your Blade
        $inflows = $transactions->where('debit', '>', 0);
        $outflows = $transactions->where('credit', '>', 0);

        if ($transactions->isEmpty()) {
            [$inflows, $outflows] = $this->buildOperationalFlows($start, $end);
        }

        // 5. Calculate Summary Totals
        $totalInflow = $inflows->sum('debit');
        $totalOutflow = $outflows->sum('credit');
        $netCashFlow = $totalInflow - $totalOutflow;
        $closingBalance = $openingBalance + $netCashFlow;

        // 6. Chart Logic (For the last 6 months)
        $chartData = $transactions->isEmpty()
            ? $this->getMonthlyTrendFromOperations()
            : $this->getMonthlyTrend($cashAccountIds);

        // Returning variables that match your Blade exactly
        return view('Reports.Reports.cash-flow', compact(
            'start',
            'end',
            'openingBalance',
            'inflows',
            'outflows',
            'totalInflow',
            'totalOutflow',
            'netCashFlow',
            'closingBalance',
            'chartData'
        ));
    }

    private function getMonthlyTrend($cashAccountIds)
    {
        $labels = [];
        $inflows = [];
        $outflows = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            
            $inflows[] = Transaction::whereIn('account_id', $cashAccountIds)
                ->tap(fn ($query) => $this->applyTenantScope($query, 'transactions'))
                ->tap(fn ($query) => $this->applyBranchScope($query, 'transactions'))
                ->whereMonth('transaction_date', $month->month)
                ->whereYear('transaction_date', $month->year)
                ->sum('debit');

            $outflows[] = Transaction::whereIn('account_id', $cashAccountIds)
                ->tap(fn ($query) => $this->applyTenantScope($query, 'transactions'))
                ->tap(fn ($query) => $this->applyBranchScope($query, 'transactions'))
                ->whereMonth('transaction_date', $month->month)
                ->whereYear('transaction_date', $month->year)
                ->sum('credit');
        }

        return ['labels' => $labels, 'inflows' => $inflows, 'outflows' => $outflows];
    }

    public function exportCashFlow(Request $request): StreamedResponse
    {
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        $cashAccountIds = $this->resolveCashAccountIds();

        $openingBalance = 0;
        if ($cashAccountIds->isNotEmpty()) {
            $openingBalance = $this->applyTenantScope(
                Transaction::whereIn('account_id', $cashAccountIds),
                'transactions'
            )
                ->tap(fn ($query) => $this->applyBranchScope($query, 'transactions'))
                ->where('transaction_date', '<', $start->toDateString())
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->first()->balance ?? 0;
        }

        $transactions = collect();
        if ($cashAccountIds->isNotEmpty()) {
            $transactions = $this->applyTenantScope(
                Transaction::whereIn('account_id', $cashAccountIds),
                'transactions'
            )
                ->tap(fn ($query) => $this->applyBranchScope($query, 'transactions'))
                ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->with('account')
                ->orderBy('transaction_date', 'asc')
                ->get();
        }

        $inflows = $transactions->where('debit', '>', 0);
        $outflows = $transactions->where('credit', '>', 0);

        if ($transactions->isEmpty()) {
            [$inflows, $outflows] = $this->buildOperationalFlows($start, $end);
        }
        $totalInflow = $inflows->sum('debit');
        $totalOutflow = $outflows->sum('credit');
        $netCashFlow = $totalInflow - $totalOutflow;
        $closingBalance = $openingBalance + $netCashFlow;

        $filename = 'cash_flow_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.csv';

        return response()->streamDownload(function () use (
            $start,
            $end,
            $openingBalance,
            $inflows,
            $outflows,
            $totalInflow,
            $totalOutflow,
            $netCashFlow,
            $closingBalance
        ) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Cash Flow Report']);
            fputcsv($out, ['Period', $start->toDateString() . ' to ' . $end->toDateString()]);
            fputcsv($out, []);
            fputcsv($out, ['Summary']);
            fputcsv($out, ['Opening Balance', $openingBalance]);
            fputcsv($out, ['Total Inflows', $totalInflow]);
            fputcsv($out, ['Total Outflows', $totalOutflow]);
            fputcsv($out, ['Net Cash Flow', $netCashFlow]);
            fputcsv($out, ['Closing Balance', $closingBalance]);
            fputcsv($out, []);

            fputcsv($out, ['Inflows']);
            fputcsv($out, ['Date', 'Description', 'Amount']);
            foreach ($inflows as $item) {
                fputcsv($out, [
                    Carbon::parse($item->transaction_date)->toDateString(),
                    $item->description ?? optional($item->account)->name ?? '',
                    $item->debit,
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Outflows']);
            fputcsv($out, ['Date', 'Description', 'Amount']);
            foreach ($outflows as $item) {
                fputcsv($out, [
                    Carbon::parse($item->transaction_date)->toDateString(),
                    $item->description ?? optional($item->account)->name ?? '',
                    $item->credit,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
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
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    private function applyBranchScope($query, string $table)
    {
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        return $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->orWhere("{$table}.branch_name", $branchName);
            }
        });
    }

    private function resolveCashAccountIds()
    {
        $baseQuery = Account::query();
        $baseQuery = $this->applyTenantScope($baseQuery, 'accounts');
        $baseQuery = $this->applyBranchScope($baseQuery, 'accounts');

        $cashAccountIds = (clone $baseQuery)
            ->whereRaw('LOWER(COALESCE(type, "")) = ?', ['asset'])
            ->where(function ($query) {
                $query->whereIn('sub_type', ['Bank', 'Cash', 'Cash and Bank', 'Cash & Bank', 'Cash/Bank'])
                    ->orWhere(function ($q) {
                        $q->whereNull('sub_type')
                            ->where(function ($inner) {
                                $inner->whereRaw('LOWER(name) like ?', ['%bank%'])
                                    ->orWhereRaw('LOWER(name) like ?', ['%cash%']);
                            });
                    })
                    ->orWhere('sub_type', '');
            })
            ->pluck('id');

        if ($cashAccountIds->isEmpty()) {
            $cashAccountIds = (clone $baseQuery)
                ->whereRaw('LOWER(COALESCE(type, "")) = ?', ['asset'])
                ->pluck('id');
        }

        return $cashAccountIds;
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

    private function getMonthlyTrendFromOperations(): array
    {
        $labels = [];
        $inflows = [];
        $outflows = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $inflows[] = $this->applyTenantScope(Payment::query(), 'payments')
                ->tap(fn ($query) => $this->applyBranchScope($query, 'payments'))
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->where(function ($query) {
                    $query->whereRaw('LOWER(status) in (?, ?, ?)', ['completed', 'success', 'successful'])
                        ->orWhereRaw('LOWER(status) = ?', ['paid']);
                })
                ->sum('amount');

            $outflows[] = $this->applyTenantScope(Expense::query(), 'expenses')
                ->tap(fn ($query) => $this->applyBranchScope($query, 'expenses'))
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->whereRaw('LOWER(status) = ?', ['paid'])
                ->sum('amount');
        }

        return ['labels' => $labels, 'inflows' => $inflows, 'outflows' => $outflows];
    }
}
