<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseClaim;
use App\Models\Project;
use App\Support\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ExpenseClaimController extends Controller
{
    public function index(Request $request): View
    {
        $claims = ExpenseClaim::query()
            ->with(['claimant', 'project', 'approver', 'reimbursementAccount'])
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15);

        $projects = $this->availableProjects($request);
        $paymentAccounts = Schema::hasTable('accounts')
            ? Account::query()->where('type', 'Asset')->orderBy('name')->get(['id', 'name'])
            : collect();

        $stats = [
            'total' => ExpenseClaim::query()->count(),
            'pending' => ExpenseClaim::query()->where('status', 'pending')->count(),
            'approved' => ExpenseClaim::query()->where('status', 'approved')->count(),
            'reimbursed' => ExpenseClaim::query()->where('status', 'reimbursed')->count(),
            'pending_amount' => (float) ExpenseClaim::query()->whereIn('status', ['pending', 'approved'])->sum('amount'),
        ];

        return view('Finance.expense-claims', compact('claims', 'projects', 'paymentAccounts', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $activeBranch = $this->activeBranchContext();
        if ($activeBranch['id'] === null && $activeBranch['name'] === null) {
            return back()->withInput()->with('error', 'Please select a branch before submitting an expense claim.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'max:160'],
            'project_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!empty($data['project_id']) && !$this->availableProjects($request)->contains('id', (int) $data['project_id'])) {
            return back()->withInput()->with('error', 'The selected project is not available in your workspace.');
        }

        ExpenseClaim::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $activeBranch['id'],
            'branch_name' => $activeBranch['name'],
            'user_id' => Auth::id(),
            'project_id' => $data['project_id'] ?: null,
            'title' => $data['title'],
            'expense_date' => $data['expense_date'],
            'amount' => (float) $data['amount'],
            'category' => $data['category'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'reimbursement_status' => 'unpaid',
        ]);

        return back()->with('success', 'Expense claim submitted successfully.');
    }

    public function approve(ExpenseClaim $expenseClaim): RedirectResponse
    {
        if ($expenseClaim->status === 'reimbursed') {
            return back()->with('info', 'This claim has already been reimbursed.');
        }

        $expenseClaim->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        return back()->with('success', 'Expense claim approved.');
    }

    public function reject(ExpenseClaim $expenseClaim): RedirectResponse
    {
        if ($expenseClaim->status === 'reimbursed') {
            return back()->with('info', 'This claim has already been reimbursed.');
        }

        $expenseClaim->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
        ]);

        return back()->with('success', 'Expense claim rejected.');
    }

    public function reimburse(Request $request, ExpenseClaim $expenseClaim): RedirectResponse
    {
        $data = $request->validate([
            'payment_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        if (!in_array($expenseClaim->status, ['approved', 'reimbursed'], true)) {
            return back()->with('error', 'Only approved claims can be reimbursed.');
        }

        if ($expenseClaim->status === 'reimbursed' || !empty($expenseClaim->reimbursed_expense_id)) {
            return back()->with('info', 'This claim has already been reimbursed.');
        }

        $account = Account::query()->findOrFail((int) $data['payment_account_id']);

        DB::transaction(function () use ($expenseClaim, $account) {
            $claimantName = trim((string) ($expenseClaim->claimant?->name ?? 'Staff'));
            $expensePayload = [
                'expense_id' => 'EXP-' . now()->format('Y') . '-' . str_pad((string) ((int) Expense::max('id') + 1), 5, '0', STR_PAD_LEFT),
                'company_name' => $claimantName,
                'reference' => 'CLAIM-' . $expenseClaim->id,
                'email' => $expenseClaim->claimant?->email,
                'amount' => (float) $expenseClaim->amount,
                'payment_mode' => $account->name,
                'payment_status' => 'paid',
                'category' => $expenseClaim->category,
                'notes' => trim('Expense claim reimbursement: ' . $expenseClaim->title . "\n" . (string) $expenseClaim->notes),
                'status' => 'Paid',
                'created_by' => Auth::id(),
                'image' => null,
            ];

            if (Schema::hasColumn('expenses', 'company_id')) {
                $expensePayload['company_id'] = $expenseClaim->company_id;
            }
            if (Schema::hasColumn('expenses', 'branch_id')) {
                $expensePayload['branch_id'] = $expenseClaim->branch_id;
            }
            if (Schema::hasColumn('expenses', 'branch_name')) {
                $expensePayload['branch_name'] = $expenseClaim->branch_name;
            }
            if (Schema::hasColumn('expenses', 'user_id')) {
                $expensePayload['user_id'] = $expenseClaim->user_id;
            }
            if (Schema::hasColumn('expenses', 'project_id')) {
                $expensePayload['project_id'] = $expenseClaim->project_id;
            }
            if (Schema::hasColumn('expenses', 'expense_claim_id')) {
                $expensePayload['expense_claim_id'] = $expenseClaim->id;
            }

            $expense = Expense::create($expensePayload);
            LedgerService::postExpense($expense->fresh());

            $expenseClaim->update([
                'status' => 'reimbursed',
                'reimbursement_status' => 'paid',
                'approved_by' => $expenseClaim->approved_by ?: Auth::id(),
                'approved_at' => $expenseClaim->approved_at ?: now(),
                'reimbursement_account_id' => $account->id,
                'reimbursed_expense_id' => $expense->id,
                'reimbursed_by' => Auth::id(),
                'reimbursed_at' => now(),
            ]);
        });

        return back()->with('success', 'Expense claim reimbursed and posted successfully.');
    }

    private function availableProjects(Request $request)
    {
        $user = $request->user();

        if (!Schema::hasTable('projects')) {
            return collect();
        }

        return Project::query()
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id);

                if (!empty($user->company_id)) {
                    $query->orWhere('company_id', $user->company_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'client_name']);
    }

    private function activeBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }
}
