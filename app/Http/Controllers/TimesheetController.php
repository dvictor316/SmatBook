<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimesheetController extends Controller
{
    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id', Auth::user()->branch_id ?? null),
            'name' => session('active_branch_name', null),
        ];
    }
    public function index(Request $request)
    {
        $companyId  = Auth::user()->company_id;
        $timesheets = Timesheet::forCompany($companyId)
            ->with(['employee', 'approvedBy'])
            ->latest('week_start_date')
            ->paginate(25);

        return view('timesheets.index', compact('timesheets'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $employees = Employee::where('company_id', $companyId)->orderBy('name')->get();
        return view('timesheets.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'week_start_date' => 'required|date',
            'notes'           => 'nullable|string',
            'entries'         => 'required|array|min:1',
            'entries.*.entry_date'   => 'required|date',
            'entries.*.activity_description' => 'required|string|max:255',
            'entries.*.hours'        => 'required|numeric|min:0.1|max:24',
            'entries.*.is_billable'  => 'boolean',
            'entries.*.hourly_rate'  => 'nullable|numeric|min:0',
            'entries.*.notes'        => 'nullable|string|max:500',
        ]);

        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($data, $companyId, $branch) {
            $totalHours = collect($data['entries'])->sum('hours');
            $billableHours = collect($data['entries'])
                ->filter(fn ($entry) => (bool) ($entry['is_billable'] ?? false))
                ->sum('hours');
            $billableAmount = collect($data['entries'])->sum(function ($entry) {
                if (! (bool) ($entry['is_billable'] ?? false)) {
                    return 0;
                }

                return (float) $entry['hours'] * (float) ($entry['hourly_rate'] ?? 0);
            });

            $timesheet = Timesheet::create([
                'company_id'      => $companyId,
                'branch_id'       => $branch['id'],
                'employee_id'     => $data['employee_id'],
                'week_start_date' => $data['week_start_date'],
                'total_hours'     => $totalHours,
                'billable_hours'  => $billableHours,
                'billable_amount' => $billableAmount,
                'status'          => 'draft',
                'notes'           => $data['notes'] ?? null,
                'user_id'         => Auth::id(),
            ]);

            foreach ($data['entries'] as $entry) {
                $billable = (bool) ($entry['is_billable'] ?? false);
                $hours    = $entry['hours'];
                $rate     = $entry['hourly_rate'] ?? 0;

                $timesheet->entries()->create([
                    'entry_date'            => $entry['entry_date'],
                    'activity_description'  => $entry['activity_description'],
                    'hours'                 => $hours,
                    'is_billable'           => $billable,
                    'hourly_rate'           => $rate,
                    'line_total'            => $billable ? ($hours * $rate) : 0,
                ]);
            }
        });

        return redirect()->route('timesheets.index')->with('success', 'Timesheet created.');
    }

    public function show(Timesheet $timesheet)
    {
        $this->authorizeTimesheetAccess($timesheet);
        $timesheet->load(['employee', 'entries', 'approvedBy']);
        return view('timesheets.show', compact('timesheet'));
    }

    public function submit(Timesheet $timesheet)
    {
        $this->authorizeTimesheetAccess($timesheet);
        abort_unless($timesheet->status === 'draft', 422, 'Only drafts can be submitted.');
        $timesheet->update(['status' => 'submitted']);
        return back()->with('success', 'Timesheet submitted for approval.');
    }

    public function approve(Timesheet $timesheet)
    {
        $this->authorizeTimesheetAccess($timesheet);
        abort_unless($timesheet->status === 'submitted', 422, 'Not submitted.');

        $timesheet->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Timesheet approved.');
    }

    public function destroy(Timesheet $timesheet)
    {
        $this->authorizeTimesheetAccess($timesheet);
        abort_if($timesheet->status === 'approved', 422, 'Cannot delete approved timesheet.');
        $timesheet->delete();
        return redirect()->route('timesheets.index')->with('success', 'Timesheet deleted.');
    }

    private function authorizeTimesheetAccess(Timesheet $ts): void
    {
        abort_unless($ts->company_id === Auth::user()->company_id, 403);
    }
}
