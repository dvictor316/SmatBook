<?php

namespace App\Http\Controllers;

use App\Models\ReportSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportScheduleController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $schedules = ReportSchedule::forCompany($companyId)
            ->orderBy('report_type')
            ->paginate(20);
        return view('report-schedules.index', compact('schedules'));
    }

    public function create()
    {
        return view('report-schedules.create');
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'report_type'     => 'required|string|max:100',
            'frequency'       => 'required|in:daily,weekly,monthly,quarterly,annually',
            'day_of_week'     => 'nullable|integer|between:0,6',
            'day_of_month'    => 'nullable|integer|between:1,31',
            'time_of_day'     => 'nullable|date_format:H:i',
            'format'          => 'required|in:pdf,excel,csv',
            'recipients'      => 'required|string',
            'parameters'      => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $data['company_id']  = $companyId;
        $data['branch_id']   = Auth::user()->branch_id;
        $data['is_active']   = $request->boolean('is_active', true);
        $data['created_by']  = Auth::id();
        $data['next_run_at'] = $this->calculateNextRun($data);

        ReportSchedule::create($data);

        return redirect()->route('report-schedules.index')
            ->with('success', 'Report schedule created.');
    }

    public function edit(ReportSchedule $reportSchedule)
    {
        $this->authorizeReportScheduleAccess($reportSchedule);
        return view('report-schedules.edit', compact('reportSchedule'));
    }

    public function update(Request $request, ReportSchedule $reportSchedule)
    {
        $this->authorizeReportScheduleAccess($reportSchedule);

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'frequency'    => 'required|in:daily,weekly,monthly,quarterly,annually',
            'format'       => 'required|in:pdf,excel,csv',
            'recipients'   => 'required|string',
            'is_active'    => 'boolean',
        ]);

        $data['is_active']   = $request->boolean('is_active', true);
        $data['next_run_at'] = $this->calculateNextRun(array_merge($reportSchedule->toArray(), $data));
        $reportSchedule->update($data);

        return redirect()->route('report-schedules.index')
            ->with('success', 'Report schedule updated.');
    }

    public function destroy(ReportSchedule $reportSchedule)
    {
        $this->authorizeReportScheduleAccess($reportSchedule);
        $reportSchedule->delete();
        return redirect()->route('report-schedules.index')
            ->with('success', 'Schedule deleted.');
    }

    private function calculateNextRun(array $data): string
    {
        return match ($data['frequency']) {
            'daily'     => now()->addDay()->format('Y-m-d H:i:s'),
            'weekly'    => now()->next('Monday')->format('Y-m-d H:i:s'),
            'monthly'   => now()->startOfMonth()->addMonth()->format('Y-m-d H:i:s'),
            'quarterly' => now()->startOfQuarter()->addQuarter()->format('Y-m-d H:i:s'),
            default     => now()->addYear()->startOfYear()->format('Y-m-d H:i:s'),
        };
    }

    private function authorizeReportScheduleAccess(ReportSchedule $rs): void
    {
        abort_unless($rs->company_id === Auth::user()->company_id, 403);
    }
}
