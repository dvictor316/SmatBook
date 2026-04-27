<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $date      = $request->query('date', now()->toDateString());

        $records = AttendanceRecord::forCompany($companyId)
            ->with('employee')
            ->forDate($date)
            ->orderBy('check_in_time')
            ->paginate(50);

        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('hr.attendance.index', compact('records', 'date', 'employees'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'employee_id'       => 'required|exists:employees,id',
            'attendance_date'   => 'required|date',
            'check_in_time'     => 'nullable|date_format:H:i',
            'check_out_time'    => 'nullable|date_format:H:i|after:check_in_time',
            'status'            => 'required|in:present,absent,late,half_day,on_leave,holiday,remote',
            'check_in_method'   => 'nullable|in:manual,biometric,mobile,web',
            'notes'             => 'nullable|string|max:255',
        ]);

        $hoursWorked = 0;
        if ($data['check_in_time'] && $data['check_out_time'] ?? null) {
            $in  = \Carbon\Carbon::parse($data['check_in_time']);
            $out = \Carbon\Carbon::parse($data['check_out_time']);
            $hoursWorked = round($in->diffInMinutes($out) / 60, 2);
        }

        AttendanceRecord::updateOrCreate(
            [
                'company_id'      => $companyId,
                'employee_id'     => $data['employee_id'],
                'attendance_date' => $data['attendance_date'],
            ],
            array_merge($data, [
                'company_id'  => $companyId,
                'branch_id'   => Auth::user()->branch_id,
                'hours_worked' => $hoursWorked,
                'created_by'  => Auth::id(),
            ])
        );

        return back()->with('success', 'Attendance recorded.');
    }

    public function bulkStore(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'attendance_date'       => 'required|date',
            'records'               => 'required|array',
            'records.*.employee_id' => 'required|exists:employees,id',
            'records.*.status'      => 'required|in:present,absent,late,half_day,on_leave,holiday,remote',
        ]);

        foreach ($data['records'] as $record) {
            AttendanceRecord::updateOrCreate(
                [
                    'company_id'      => $companyId,
                    'employee_id'     => $record['employee_id'],
                    'attendance_date' => $data['attendance_date'],
                ],
                [
                    'branch_id'   => Auth::user()->branch_id,
                    'status'      => $record['status'],
                    'created_by'  => Auth::id(),
                ]
            );
        }

        return back()->with('success', 'Attendance saved for ' . count($data['records']) . ' employees.');
    }

    public function report(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $records = AttendanceRecord::forCompany($companyId)
            ->with('employee')
            ->whereBetween('attendance_date', [$from, $to])
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('employee_id');

        return view('hr.attendance.report', compact('records', 'from', 'to'));
    }
}
