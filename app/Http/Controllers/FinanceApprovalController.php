<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\FinanceApproval;
use App\Models\Purchase;
use App\Support\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceApprovalController extends Controller
{
    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
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

    public function index(Request $request)
    {
        $status = strtolower(trim((string) $request->get('status', '')));
        $type = strtolower(trim((string) $request->get('type', '')));
        $search = trim((string) $request->string('q'));
        $month = trim((string) $request->string('month'));
        $fromDate = trim((string) $request->string('from_date'));
        $toDate = trim((string) $request->string('to_date'));

        $query = FinanceApproval::with(['requester', 'approver'])->latest('submitted_at');
        $this->applyTenantScope($query, 'finance_approvals');
        $this->applyBranchScope($query, 'finance_approvals');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if (in_array($type, ['expense', 'purchase'], true)) {
            $query->where('approval_type', $type);
        }
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', '%' . $search . '%')
                    ->orWhere('reference_no', 'like', '%' . $search . '%')
                    ->orWhereHas('requester', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
            });
        }
        if ($month !== '') {
            $query->whereBetween('submitted_at', [
                now()->parse($month . '-01')->startOfMonth()->toDateString(),
                now()->parse($month . '-01')->endOfMonth()->toDateString(),
            ]);
        } else {
            if ($fromDate !== '') {
                $query->whereDate('submitted_at', '>=', $fromDate);
            }
            if ($toDate !== '') {
                $query->whereDate('submitted_at', '<=', $toDate);
            }
        }

        $approvals = $query->paginate(20)->appends($request->query());

        return view('Finance.approvals', compact('approvals', 'status', 'type', 'search', 'month', 'fromDate', 'toDate'));
    }

    public function submitExpense(Expense $expense)
    {
        $expense = $this->scopeExpenseQuery()->findOrFail($expense->id);

        $existing = $this->pendingApprovalFor(Expense::class, $expense->id);
        if ($existing) {
            return back()->with('info', 'This expense is already awaiting approval.');
        }

        FinanceApproval::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $expense->branch_id,
            'branch_name' => $expense->branch_name,
            'requested_by' => Auth::id(),
            'approval_type' => 'expense',
            'approvable_type' => Expense::class,
            'approvable_id' => $expense->id,
            'reference_no' => $expense->expense_id,
            'title' => 'Expense approval for ' . ($expense->company_name ?: ($expense->expense_id ?? ('Expense #' . $expense->id))),
            'amount' => $expense->amount,
            'status' => 'pending',
            'submitted_at' => now(),
            'snapshot' => ['defer_posting' => false],
        ]);

        return back()->with('success', 'Expense submitted for approval.');
    }

    public function submitPurchase(Purchase $purchase)
    {
        $purchase = $this->scopePurchaseQuery()->findOrFail($purchase->id);

        $existing = $this->pendingApprovalFor(Purchase::class, $purchase->id);
        if ($existing) {
            return back()->with('info', 'This purchase is already awaiting approval.');
        }

        FinanceApproval::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $purchase->branch_id,
            'branch_name' => $purchase->branch_name,
            'requested_by' => Auth::id(),
            'approval_type' => 'purchase',
            'approvable_type' => Purchase::class,
            'approvable_id' => $purchase->id,
            'reference_no' => $purchase->purchase_no,
            'title' => 'Purchase approval for ' . ($purchase->purchase_no ?: ('Purchase #' . $purchase->id)),
            'amount' => $purchase->total_amount ?? 0,
            'status' => 'pending',
            'submitted_at' => now(),
            'snapshot' => ['defer_posting' => false],
        ]);

        return back()->with('success', 'Purchase submitted for approval.');
    }

    public function approve(Request $request, FinanceApproval $financeApproval)
    {
        $approval = $this->scopeApprovalQuery()->findOrFail($financeApproval->id);

        if ($approval->status !== 'pending') {
            return back()->with('info', 'This approval has already been actioned.');
        }

        $validated = $request->validate([
            'decision_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($approval, $validated) {
            $approval->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'acted_at' => now(),
                'decision_notes' => $validated['decision_notes'] ?? null,
            ]);

            $snapshot = (array) ($approval->snapshot ?? []);
            if (!empty($snapshot['defer_posting'])) {
                $approvable = $approval->approvable;
                if ($approvable instanceof Expense && strtolower((string) ($approvable->status ?? '')) === 'paid') {
                    LedgerService::postExpense($approvable->fresh());
                }
                if ($approvable instanceof Purchase) {
                    LedgerService::postPurchase($approvable->fresh());
                }
            }
        });

        return back()->with('success', 'Approval marked as approved.');
    }

    public function reject(Request $request, FinanceApproval $financeApproval)
    {
        $approval = $this->scopeApprovalQuery()->findOrFail($financeApproval->id);

        if ($approval->status !== 'pending') {
            return back()->with('info', 'This approval has already been actioned.');
        }

        $validated = $request->validate([
            'decision_notes' => 'nullable|string|max:1000',
        ]);

        $approval->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'acted_at' => now(),
            'decision_notes' => $validated['decision_notes'] ?? null,
        ]);

        return back()->with('success', 'Approval marked as rejected.');
    }

    private function pendingApprovalFor(string $type, int $id): ?FinanceApproval
    {
        return $this->scopeApprovalQuery()
            ->where('approvable_type', $type)
            ->where('approvable_id', $id)
            ->where('status', 'pending')
            ->first();
    }

    private function scopeApprovalQuery()
    {
        $query = FinanceApproval::query();
        $this->applyTenantScope($query, 'finance_approvals');
        $this->applyBranchScope($query, 'finance_approvals');

        return $query;
    }

    private function scopeExpenseQuery()
    {
        $query = Expense::query();
        $this->applyTenantScope($query, 'expenses');
        $this->applyBranchScope($query, 'expenses');

        return $query;
    }

    private function scopePurchaseQuery()
    {
        $query = Purchase::query();
        $this->applyTenantScope($query, 'purchases');
        $this->applyBranchScope($query, 'purchases');

        return $query;
    }
}
