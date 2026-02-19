<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Models\CloseApproval;
use App\Models\CloseTask;
use Illuminate\Http\Request;

class PeriodCloseController extends Controller
{
    public function index()
    {
        $periods = AccountingPeriod::with(['tasks.owner', 'approvals.requester', 'approvals.approver'])
            ->latest('start_date')
            ->paginate(15);

        return view('close.index', compact('periods'));
    }

    public function storePeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        AccountingPeriod::create($validated + ['status' => 'open']);

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

        CloseTask::create($validated + [
            'accounting_period_id' => $period->id,
            'owner_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Close task added.');
    }

    public function completeTask($id)
    {
        $task = CloseTask::findOrFail($id);
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Task completed.');
    }

    public function requestClose($periodId)
    {
        $period = AccountingPeriod::findOrFail($periodId);

        CloseApproval::create([
            'accounting_period_id' => $period->id,
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return back()->with('success', 'Close request submitted for approval.');
    }

    public function approve($approvalId)
    {
        $approval = CloseApproval::findOrFail($approvalId);
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

        return back()->with('success', 'Period approved and closed.');
    }
}

