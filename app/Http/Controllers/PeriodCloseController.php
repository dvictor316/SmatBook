<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Models\ActivityLog;
use App\Models\CloseApproval;
use App\Models\CloseTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PeriodCloseController extends Controller
{
    public function index()
    {
        $periods = AccountingPeriod::with(['tasks.owner', 'approvals.requester', 'approvals.approver'])
            ->latest('start_date')
            ->paginate(15);

        $stats = [
            'total' => AccountingPeriod::query()->count(),
            'open' => AccountingPeriod::query()->where('status', 'open')->count(),
            'closed' => AccountingPeriod::query()->where('status', 'closed')->count(),
            'pending_tasks' => CloseTask::query()->where('status', '!=', 'completed')->count(),
        ];

        return view('close.index', compact('periods', 'stats'));
    }

    public function storePeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $period = AccountingPeriod::create($validated + [
            'status' => 'open',
            'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
        ]);

        $this->logCloseActivity('period_close', 'create_period', 'Created accounting period ' . $period->name . '.');

        return back()->with('success', 'Accounting period created.');
    }

    public function storeTask(Request $request, $periodId)
    {
        $period = AccountingPeriod::findOrFail($periodId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        if ($period->status === 'closed') {
            return back()->with('error', 'You cannot add tasks to a closed period.');
        }

        $task = CloseTask::create($validated + [
            'accounting_period_id' => $period->id,
            'owner_id' => auth()->id(),
            'status' => 'pending',
            'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
        ]);

        $this->logCloseActivity('period_close', 'create_task', 'Added close task "' . $task->title . '" to period ' . $period->name . '.');

        return back()->with('success', 'Close task added.');
    }

    public function completeTask($id)
    {
        $task = CloseTask::findOrFail($id);
        if ($task->period?->status === 'closed') {
            return back()->with('error', 'This period is already closed.');
        }
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->logCloseActivity('period_close', 'complete_task', 'Completed close task "' . $task->title . '".');

        return back()->with('success', 'Task completed.');
    }

    public function requestClose(Request $request, $periodId)
    {
        $period = AccountingPeriod::findOrFail($periodId);

        if ($period->status === 'closed') {
            return back()->with('info', 'This period is already closed.');
        }

        $incompleteTasks = $period->tasks()->where('status', '!=', 'completed')->count();
        if ($incompleteTasks > 0) {
            return back()->with('error', 'Complete all close tasks before requesting approval.');
        }

        $pendingApproval = $period->approvals()->where('status', 'pending')->exists();
        if ($pendingApproval) {
            return back()->with('info', 'A close approval request is already pending for this period.');
        }

        $approval = CloseApproval::create([
            'accounting_period_id' => $period->id,
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'notes' => trim((string) $request->input('notes', '')),
            'requested_at' => now(),
            'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
        ]);

        $this->logCloseActivity('period_close', 'request_approval', 'Requested close approval for period ' . $period->name . '.');

        return back()->with('success', 'Close request submitted for approval.');
    }

    public function approve($approvalId)
    {
        $approval = CloseApproval::findOrFail($approvalId);
        if ($approval->status === 'approved') {
            return back()->with('info', 'This close request is already approved.');
        }

        $approval->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $period = AccountingPeriod::findOrFail($approval->accounting_period_id);
        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        $this->logCloseActivity('period_close', 'approve_close', 'Approved and closed period ' . $period->name . '.');

        return back()->with('success', 'Period approved and closed.');
    }

    private function logCloseActivity(string $module, string $action, string $description): void
    {
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
