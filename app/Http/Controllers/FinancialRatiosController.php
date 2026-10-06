<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialRatiosController extends Controller
{
    public function index(Request $request)
    {
        $period = in_array($request->input('period'), ['monthly', 'quarterly', 'ytd', 'annual'], true)
            ? $request->input('period')
            : 'ytd';
        [$periodStart, $periodEnd] = $this->periodDates($period);
        $companyId = (int) (session('current_tenant_id') ?: Auth::user()?->company_id ?: 0);
        $activeBranch = $this->activeBranchContext();
        $balances = $this->ledgerBalances($companyId, $periodStart, $periodEnd, $activeBranch);

        $revenue = $this->sumCategory($balances, 'revenue');
        $cogs = $this->sumCategory($balances, 'cogs');
        $opex = $this->sumCategory($balances, 'operating_expense');
        $interestExpense = $this->sumCategory($balances, 'interest_expense');
        $netIncome = $revenue - $cogs - $opex - $interestExpense;
        $ebit = $revenue - $cogs - $opex;
        $totalAssets = $this->sumCategory($balances, 'assets');
        $totalDebt = $this->sumCategory($balances, 'liabilities');
        $equity = $totalAssets - $totalDebt;
        $currentAssets = $this->sumCategory($balances, 'current_assets');
        $currentLiabilities = $this->sumCategory($balances, 'current_liabilities');
        $cashEquivalents = $this->sumCategory($balances, 'cash');
        $inventory = $this->sumCategory($balances, 'inventory');
        $receivables = $this->sumCategory($balances, 'receivables');
        $payables = $this->sumCategory($balances, 'payables');
        $periodDays = max(1, $periodStart->diffInDays($periodEnd) + 1);

        $ratios = [
            'current_ratio' => $this->divide($currentAssets, $currentLiabilities),
            'quick_ratio' => $this->divide($currentAssets - $inventory, $currentLiabilities),
            'cash_ratio' => $this->divide($cashEquivalents, $currentLiabilities),
            'working_capital' => $currentAssets - $currentLiabilities,
            'gross_margin' => $this->percent($revenue - $cogs, $revenue),
            'net_margin' => $this->percent($netIncome, $revenue),
            'roa' => $this->percent($netIncome, $totalAssets),
            'roe' => $this->percent($netIncome, $equity),
            'debt_to_equity' => $this->divide($totalDebt, $equity),
            'debt_ratio' => $this->divide($totalDebt, $totalAssets),
            'interest_coverage' => $this->divide($ebit, $interestExpense),
            'equity_multiplier' => $this->divide($totalAssets, $equity),
            'inventory_turnover' => $this->divide($cogs, $inventory),
            'dso' => $revenue > 0 ? round(($receivables / $revenue) * $periodDays, 1) : 0.0,
            'ap_days' => $cogs > 0 ? round(($payables / $cogs) * $periodDays, 1) : 0.0,
            'asset_turnover' => $this->divide($revenue, $totalAssets),
        ];

        return view('Reports.financial-ratios', compact(
            'ratios', 'period', 'periodStart', 'periodEnd', 'revenue', 'netIncome', 'activeBranch'
        ));
    }

    private function periodDates(string $period): array
    {
        $end = now()->endOfDay();
        $start = match ($period) {
            'monthly' => $end->copy()->startOfMonth(),
            'quarterly' => $end->copy()->startOfQuarter(),
            'annual' => $end->copy()->subYear()->addDay()->startOfDay(),
            default => $end->copy()->startOfYear(),
        };

        return [$start, $end];
    }

    private function ledgerBalances(int $companyId, Carbon $start, Carbon $end, array $activeBranch): Collection
    {
        if ($companyId <= 0 || ! Schema::hasTable('accounts') || ! Schema::hasTable('transactions')) {
            return collect();
        }

        $accounts = Account::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->get(['id', 'name', 'type', 'sub_type', 'opening_balance']);

        if ($accounts->isEmpty()) {
            return collect();
        }

        $transactionQuery = DB::table('transactions')
            ->where('transactions.company_id', $companyId)
            ->whereIn('transactions.account_id', $accounts->pluck('id'))
            ->where('transactions.transaction_date', '<=', $end->toDateString());

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $transactionQuery->whereNull('transactions.deleted_at');
        }
        $this->applyBranchScope($transactionQuery, 'transactions', $activeBranch);

        $totals = $transactionQuery
            ->selectRaw('account_id')
            ->selectRaw('SUM(CASE WHEN transaction_date >= ? THEN COALESCE(debit, 0) ELSE 0 END) as period_debit', [$start->toDateString()])
            ->selectRaw('SUM(CASE WHEN transaction_date >= ? THEN COALESCE(credit, 0) ELSE 0 END) as period_credit', [$start->toDateString()])
            ->selectRaw('SUM(COALESCE(debit, 0)) as closing_debit')
            ->selectRaw('SUM(COALESCE(credit, 0)) as closing_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return $accounts->map(function (Account $account) use ($totals) {
            $row = $totals->get($account->id);
            $type = strtolower(trim((string) $account->type));
            $periodDebit = (float) ($row->period_debit ?? 0);
            $periodCredit = (float) ($row->period_credit ?? 0);
            $closingDebit = (float) ($row->closing_debit ?? 0);
            $closingCredit = (float) ($row->closing_credit ?? 0);
            $opening = (float) ($account->opening_balance ?? 0);
            $periodAmount = in_array($type, ['revenue', 'income'], true)
                ? $periodCredit - $periodDebit
                : $periodDebit - $periodCredit;
            $closingAmount = in_array($type, ['asset', 'expense'], true)
                ? $opening + $closingDebit - $closingCredit
                : $opening + $closingCredit - $closingDebit;

            return (object) [
                'categories' => $this->accountCategories($account),
                'period_amount' => $periodAmount,
                'closing_amount' => $closingAmount,
            ];
        });
    }

    private function accountCategories(Account $account): array
    {
        $type = strtolower(trim((string) $account->type));
        $descriptor = strtolower(trim(($account->sub_type ?? '').' '.($account->name ?? '')));
        $categories = [];

        if ($type === 'asset') {
            $categories[] = 'assets';
            if (! str_contains($descriptor, 'fixed') && ! str_contains($descriptor, 'non-current') && ! str_contains($descriptor, 'non current')) {
                $categories[] = 'current_assets';
            }
            if (str_contains($descriptor, 'cash') || str_contains($descriptor, 'bank')) {
                $categories[] = 'cash';
            }
            if (str_contains($descriptor, 'inventory') || str_contains($descriptor, 'stock')) {
                $categories[] = 'inventory';
            }
            if (str_contains($descriptor, 'receivable') || str_contains($descriptor, 'debtor')) {
                $categories[] = 'receivables';
            }
        } elseif ($type === 'liability') {
            $categories[] = 'liabilities';
            if (! str_contains($descriptor, 'long-term') && ! str_contains($descriptor, 'long term') && ! str_contains($descriptor, 'non-current')) {
                $categories[] = 'current_liabilities';
            }
            if (str_contains($descriptor, 'payable') || str_contains($descriptor, 'creditor')) {
                $categories[] = 'payables';
            }
        } elseif (in_array($type, ['revenue', 'income'], true)) {
            $categories[] = 'revenue';
        } elseif ($type === 'expense') {
            if (str_contains($descriptor, 'cost of sales') || str_contains($descriptor, 'cost of goods') || str_contains($descriptor, 'cogs')) {
                $categories[] = 'cogs';
            } elseif (str_contains($descriptor, 'interest') || str_contains($descriptor, 'finance cost')) {
                $categories[] = 'interest_expense';
            } else {
                $categories[] = 'operating_expense';
            }
        }

        return array_values(array_unique($categories));
    }

    private function sumCategory(Collection $balances, string $category): float
    {
        $usePeriodAmount = in_array($category, ['revenue', 'cogs', 'operating_expense', 'interest_expense'], true);

        return (float) $balances
            ->filter(fn ($row) => in_array($category, $row->categories, true))
            ->sum($usePeriodAmount ? 'period_amount' : 'closing_amount');
    }

    private function applyBranchScope($query, string $table, array $activeBranch): void
    {
        if (($activeBranch['scope'] ?? 'branch') === 'all') {
            return;
        }

        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));
        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($branchQuery) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $branchQuery->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $branchQuery->orWhere("{$table}.branch_name", $branchName);
            }
        });
    }

    private function activeBranchContext(): array
    {
        if (session('active_branch_scope') === 'all') {
            return ['id' => null, 'name' => 'All Branches', 'scope' => 'all'];
        }

        return [
            'id' => session('active_branch_id'),
            'name' => session('active_branch_name'),
            'scope' => 'branch',
        ];
    }

    private function divide(float $numerator, float $denominator): float
    {
        return abs($denominator) > 0.0001 ? round($numerator / $denominator, 2) : 0.0;
    }

    private function percent(float $numerator, float $denominator): float
    {
        return abs($denominator) > 0.0001 ? round(($numerator / $denominator) * 100, 2) : 0.0;
    }
}
