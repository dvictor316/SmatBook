<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveManagementController extends Controller
{
    // --- Leave Types ---
    public function types()
    {
        $companyId  = Auth::user()->company_id;
        $leaveTypes = LeaveType::forCompany($companyId)->orderBy('name')->get();
        return view('hr.leave.types', compact('leaveTypes'));
    }

    public function storeType(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'name'                    => 'required|string|max:100',
            'code'                    => 'nullable|string|max:30',
            'days_allowed_per_year'   => 'required|integer|min:0',
            'is_paid'                 => 'boolean',
            'carry_forward'           => 'boolean',
            'max_carry_forward_days'  => 'nullable|integer|min:0',
            'requires_approval'       => 'boolean',
        ]);

        $data['company_id']        = $companyId;
        $data['is_paid']           = $request->boolean('is_paid', true);
        $data['carry_forward']     = $request->boolean('carry_forward', false);
        $data['requires_approval'] = $request->boolean('requires_approval', true);
        $data['is_active']         = true;

        LeaveType::create($data);
        return back()->with('success', 'Leave type created.');
    }

    // --- Leave Requests ---
    public function requests(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query     = LeaveRequest::where('company_id', $companyId)
            ->with(['employee', 'leaveType', 'approvedBy'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(25);
        return view('hr.leave.requests', compact('requests'));
    }

    public function createRequest()
    {
        $companyId  = Auth::user()->company_id;
        $employees  = Employee::where('company_id', $companyId)->orderBy('name')->get();
        $leaveTypes = LeaveType::forCompany($companyId)->active()->orderBy('name')->get();
        return view('hr.leave.create', compact('employees', 'leaveTypes'));
    }

    public function storeRequest(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'leave_type_id'  => 'required|exists:leave_types,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'reason'         => 'nullable|string|max:500',
        ]);

        $start = \Carbon\Carbon::parse($data['start_date']);
        $end   = \Carbon\Carbon::parse($data['end_date']);
        $days  = $start->diffInWeekdays($end) + 1;

        LeaveRequest::create([
            'company_id'     => $companyId,
            'branch_id'      => Auth::user()->branch_id,
            'employee_id'    => $data['employee_id'],
            'leave_type_id'  => $data['leave_type_id'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'days_requested' => $days,
            'reason'         => $data['reason'] ?? null,
            'status'         => 'pending',
            'created_by'     => Auth::id(),
        ]);

        return redirect()->route('hr.leave.requests')
            ->with('success', 'Leave request submitted.');
    }

    public function approveRequest(LeaveRequest $leaveRequest)
    {
        $this->authorize($leaveRequest);
        abort_unless($leaveRequest->isPending(), 422, 'Only pending requests can be approved.');

        $leaveRequest->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Leave request approved.');
    }

    public function rejectRequest(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize($leaveRequest);
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $leaveRequest->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    private function authorize(LeaveRequest $lr): void
    {
        abort_unless($lr->company_id === Auth::user()->company_id, 403);
    }
}
