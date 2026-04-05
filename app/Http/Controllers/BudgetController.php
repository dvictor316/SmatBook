<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BudgetController extends Controller
{
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

    public function index()
    {
        $budgetQuery = Budget::with('account')->latest('start_date');
        $this->applyTenantScope($budgetQuery, 'budgets');
        $this->applyBranchScope($budgetQuery, 'budgets');
        $budgets = $budgetQuery->paginate(15);

        $budgets->getCollection()->transform(function (Budget $budget) {
            $budget->actual_amount = $this->calculateActualAmount($budget);
            $budget->variance_amount = round((float) $budget->amount - (float) $budget->actual_amount, 2);
            $budget->utilization_pct = (float) $budget->amount > 0
                ? round(((float) $budget->actual_amount / (float) $budget->amount) * 100, 1)
                : 0.0;

            return $budget;
        });

        $accountsQuery = Account::query()->where('is_active', true)->orderBy('type')->orderBy('name');
        $this->applyTenantScope($accountsQuery, 'accounts');
        $this->applyBranchScope($accountsQuery, 'accounts');
        $accounts = $accountsQuery->get();

        $summary = [
            'budget_count' => $budgets->total(),
            'budget_total' => (float) $budgets->getCollection()->sum(fn ($budget) => (float) $budget->amount),
            'actual_total' => (float) $budgets->getCollection()->sum(fn ($budget) => (float) ($budget->actual_amount ?? 0)),
            'variance_total' => (float) $budgets->getCollection()->sum(fn ($budget) => (float) ($budget->variance_amount ?? 0)),
        ];

        return view('Finance.budgets', compact('budgets', 'accounts', 'summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'account_id' => 'required|exists:accounts,id',
            'period_type' => 'required|in:monthly,quarterly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        $activeBranch = $this->getActiveBranchContext();

        Budget::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $activeBranch['id'],
            'branch_name' => $activeBranch['name'],
            'created_by' => Auth::id(),
            'name' => $validated['name'],
            'account_id' => (int) $validated['account_id'],
            'period_type' => $validated['period_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'amount' => round((float) $validated['amount'], 2),
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('finance.budgets.index')->with('success', 'Budget added successfully.');
    }

    public function toggleStatus(Budget $budget)
    {
        $budget = $this->scopeBudgetQuery()->findOrFail($budget->id);
        $budget->status = $budget->status === 'active' ? 'archived' : 'active';
        $budget->save();

        return redirect()->route('finance.budgets.index')->with('success', 'Budget status updated.');
    }

    private function calculateActualAmount(Budget $budget): float
    {
        $account = $budget->account;
        if (!$account) {
            return 0.0;
        }

        $query = Transaction::query()->where('account_id', $budget->account_id);
        $this->applyTenantScope($query, 'transactions');
        $this->applyBranchScope($query, 'transactions');
        $query->whereBetween('transaction_date', [$budget->start_date->toDateString(), $budget->end_date->toDateString()]);

        $debit = (float) $query->sum('debit');
        $credit = (float) $query->sum('credit');

        if (in_array($account->type, [Account::TYPE_ASSET, Account::TYPE_EXPENSE], true)) {
            return round(max(0, $debit - $credit), 2);
        }

        return round(max(0, $credit - $debit), 2);
    }

    private function scopeBudgetQuery()
    {
        $query = Budget::query();
        $this->applyTenantScope($query, 'budgets');
        $this->applyBranchScope($query, 'budgets');

        return $query;
    }
}
