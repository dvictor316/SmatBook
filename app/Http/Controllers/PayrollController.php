<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PayrollController extends Controller
{
    /**
     * Payroll Dashboard
     */
    public function index()
    {
        $businessId = $this->currentBusinessId();
        $currentMonth = Carbon::now()->startOfMonth();

        // Staff counts
        $totalStaff    = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->count();
        $activeStaff   = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->where('status', 'active')->count();

        // Current month payroll totals
        $payrolls = $this->scopeByBusiness(Payroll::with('employee'), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->paginate(15);

        $monthlyPayroll  = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->sum('net_pay');

        $lastMonthPayroll = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->copy()->subMonth()->year)
            ->whereMonth('pay_period', $currentMonth->copy()->subMonth()->month)
            ->sum('net_pay');

        $payrollChange = $lastMonthPayroll > 0
            ? round((($monthlyPayroll - $lastMonthPayroll) / $lastMonthPayroll) * 100, 1)
            : 0;

        $paidCount    = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'paid')
            ->count();
        $paidAmount   = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'paid')
            ->sum('net_pay');
        $pendingCount = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'pending')
            ->count();
        $pendingAmount= $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'pending')
            ->sum('net_pay');

        $totalBasic       = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->sum('basic_salary');
        $totalAllowances  = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->sum('total_allowances');
        $totalDeductions  = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->sum('total_deductions');
        $netPayable       = $monthlyPayroll;

        // Department breakdown
        $deptBreakdown = $this->scopeByBusiness(Payroll::with('employee'), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->get()
            ->groupBy(fn($p) => $p->employee->department ?? 'Unknown')
            ->map(fn($group, $dept) => [
                'name'   => $dept,
                'amount' => $group->sum('net_pay'),
                'pct'    => 0,
            ])->values()->toArray();

        if (count($deptBreakdown) > 0) {
            $maxAmount = max(array_column($deptBreakdown, 'amount'));
            if ($maxAmount > 0) {
                foreach ($deptBreakdown as &$d) {
                    $d['pct'] = round(($d['amount'] / $maxAmount) * 100);
                }
            }
        }

        // Recent payroll runs
        $recentRuns = $this->scopeByBusiness(PayrollRun::query(), 'payroll_runs', $businessId)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // Deduction summary
        $deductionSummary = [
            ['name' => 'PAYE Tax',              'amount' => $totalDeductions * 0.45, 'color' => '#ef4444'],
            ['name' => 'Pension (Employee 8%)', 'amount' => $totalBasic * 0.08,      'color' => '#f59e0b'],
            ['name' => 'Pension (Employer 10%)','amount' => $totalBasic * 0.10,      'color' => '#8b5cf6'],
            ['name' => 'NHF (2.5%)',            'amount' => $totalBasic * 0.025,     'color' => '#06b6d4'],
            ['name' => 'Other Deductions',       'amount' => $totalDeductions * 0.10, 'color' => '#6b7280'],
        ];

        $cycleProgress = min(100, round((Carbon::now()->day / Carbon::now()->daysInMonth) * 100));

        return view('payroll.index', compact(
            'payrolls','totalStaff','activeStaff','monthlyPayroll','payrollChange',
            'paidCount','paidAmount','pendingCount','pendingAmount',
            'totalBasic','totalAllowances','totalDeductions','netPayable',
            'deptBreakdown','recentRuns','deductionSummary','cycleProgress'
        ));
    }

    /**
     * Show Add Employee form
     */
    public function create()
    {
        return view('payroll.create');
    }

    /**
     * Store new employee + initial payroll record
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'department'   => 'required|string',
            'job_title'    => 'required|string|max:255',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        $businessId = $this->currentBusinessId();

        DB::transaction(function () use ($request, $businessId) {
            $employee = Employee::create([
                'name'            => $request->name,
                'employee_id'     => $request->employee_id ?? $this->generateEmployeeId(),
                'department'      => $request->department,
                'job_title'       => $request->job_title,
                'email'           => $request->email,
                'phone'           => $request->phone,
                'employment_date' => $request->employment_date,
                'bank_name'       => $request->bank_name,
                'account_number'  => $request->account_number,
                'tax_id'          => $request->tax_id,
                'status'          => 'active',
                'allowances'      => $request->allowances ?? [],
                'deductions'      => $request->deductions ?? [],
                'business_id'     => $businessId,
            ]);

            $totalAllowances = collect($request->allowances ?? [])->sum('amount');
            $totalDeductions = collect($request->deductions ?? [])->sum('amount');

            $employee->update([
                'basic_salary'      => $request->basic_salary,
                'total_allowances'  => $totalAllowances,
                'total_deductions'  => $totalDeductions,
                'gross_pay'         => $request->basic_salary + $totalAllowances,
                'net_pay'           => $request->basic_salary + $totalAllowances - $totalDeductions,
            ]);
        });

        return redirect()->route('payroll.index')
            ->with('success', 'Employee added successfully and payroll configured.');
    }

    /**
     * Show employee payroll details
     */
    public function show($id)
    {
        $businessId = $this->currentBusinessId();
        $payroll = $this->scopeByBusiness(Payroll::with(['employee', 'payrollRun']), 'payrolls', $businessId)->findOrFail($id);

        // Decode allowance/deduction details
        $payroll->allowanceDetails = json_decode($payroll->allowances_json ?? '[]', true);
        $payroll->deductionDetails = json_decode($payroll->deductions_json ?? '[]', true);

        // Convert net pay to words
        $payroll->net_pay_words = $this->numberToWords($payroll->net_pay);

        return view('payroll.show', compact('payroll'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $businessId = $this->currentBusinessId();
        $employee = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->findOrFail($id);
        return view('payroll.create', compact('employee'));
    }

    /**
     * Update employee payroll
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'department'   => 'required|string',
            'job_title'    => 'required|string|max:255',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        $businessId = $this->currentBusinessId();
        $employee = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->findOrFail($id);

        $totalAllowances = collect($request->allowances ?? [])->sum('amount');
        $totalDeductions = collect($request->deductions ?? [])->sum('amount');

        $employee->update([
            'name'            => $request->name,
            'department'      => $request->department,
            'job_title'       => $request->job_title,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'employment_date' => $request->employment_date,
            'bank_name'       => $request->bank_name,
            'account_number'  => $request->account_number,
            'tax_id'          => $request->tax_id,
            'basic_salary'    => $request->basic_salary,
            'total_allowances'=> $totalAllowances,
            'total_deductions'=> $totalDeductions,
            'gross_pay'       => $request->basic_salary + $totalAllowances,
            'net_pay'         => $request->basic_salary + $totalAllowances - $totalDeductions,
            'allowances'      => $request->allowances ?? [],
            'deductions'      => $request->deductions ?? [],
            'business_id'     => $businessId,
        ]);

        return redirect()->route('payroll.index')
            ->with('success', 'Employee payroll updated successfully.');
    }

    /**
     * Show Run Payroll page
     */
    public function runPage()
    {
        $businessId = $this->currentBusinessId();
        $employees = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)
            ->where('status', 'active')
            ->get();
        $currentPeriod = Carbon::now()->startOfMonth();
        $existingPayrollEmployeeIds = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentPeriod->year)
            ->whereMonth('pay_period', $currentPeriod->month)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        return view('payroll.run', compact('employees', 'existingPayrollEmployeeIds'));
    }

    /**
     * Locked employees for a pay period (AJAX)
     */
    public function lockedEmployees(Request $request)
    {
        $businessId = $this->currentBusinessId();
        $month = $request->month ?? now()->format('Y-m');
        $payPeriod = Carbon::parse($month . '-01');

        $employeeIds = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $payPeriod->year)
            ->whereMonth('pay_period', $payPeriod->month)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        return response()->json([
            'employee_ids' => $employeeIds,
            'period' => $payPeriod->format('F Y'),
        ]);
    }

    /**
     * Process payroll run
     */
    public function process(Request $request)
    {
        $request->validate([
            'pay_period'      => 'required',
            'pay_date'        => 'required|date',
            'employee_ids'    => 'required|array|min:1',
            'employee_ids.*'  => 'exists:employees,id',
        ]);

        $businessId = $this->currentBusinessId();
        $payPeriod = Carbon::parse($request->pay_period . '-01');
        $requestedIds = array_values(array_unique($request->employee_ids));
        $existingIds = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $payPeriod->year)
            ->whereMonth('pay_period', $payPeriod->month)
            ->whereIn('employee_id', $requestedIds)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $processableIds = array_values(array_diff($requestedIds, $existingIds));
        $skippedCount = count($existingIds);

        if (empty($processableIds)) {
            return back()->with('warning', 'All selected employees already have payroll for ' . $payPeriod->format('F Y') . '.');
        }

        DB::transaction(function () use ($request, $businessId, $payPeriod, $processableIds) {

            // Create payroll run record
            $run = PayrollRun::create([
                'period'         => $payPeriod->format('F Y'),
                'pay_date'       => $request->pay_date,
                'payment_method' => $request->payment_method ?? 'bank_transfer',
                'notes'          => $request->notes,
                'status'         => 'processing',
                'staff_count'    => 0,
                'business_id'    => $businessId,
            ]);

            $totalAmount = 0;
            $processedCount = 0;

            // Create individual payroll records
            foreach ($processableIds as $empId) {
                $employee = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->findOrFail($empId);

                // Check if payroll already exists for this period
                $existing = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
                    ->where('employee_id', $empId)
                    ->whereYear('pay_period', $payPeriod->year)
                    ->whereMonth('pay_period', $payPeriod->month)
                    ->first();

                if (!$existing) {
                    Payroll::create([
                        'employee_id'      => $empId,
                        'payroll_run_id'   => $run->id,
                        'pay_period'       => $payPeriod,
                        'basic_salary'     => $employee->basic_salary,
                        'total_allowances' => $employee->total_allowances,
                        'total_deductions' => $employee->total_deductions,
                        'gross_pay'        => $employee->gross_pay,
                        'net_pay'          => $employee->net_pay,
                        'allowances_json'  => json_encode($employee->allowances ?? []),
                        'deductions_json'  => json_encode($employee->deductions ?? []),
                        'status'           => 'pending',
                        'reference'        => 'SB-' . strtoupper(uniqid()),
                        'business_id'      => $businessId,
                    ]);
                    $totalAmount += $employee->net_pay;
                    $processedCount++;
                }
            }

            $run->update([
                'total_amount' => $totalAmount,
                'status'       => 'completed',
                'staff_count'  => $processedCount,
            ]);
        });

        $successMessage = 'Payroll processed successfully for ' . count($processableIds) . ' employees.';
        if ($skippedCount > 0) {
            $successMessage .= ' Skipped ' . $skippedCount . ' already processed for ' . $payPeriod->format('F Y') . '.';
        }

        return redirect()->route('payroll.index')->with('success', $successMessage);
    }

    /**
     * Show payslip
     */
    public function slip($id)
    {
        $businessId = $this->currentBusinessId();
        $payroll = $this->scopeByBusiness(Payroll::with('employee'), 'payrolls', $businessId)->findOrFail($id);
        $payroll->allowanceDetails = json_decode($payroll->allowances_json ?? '[]', true);
        $payroll->deductionDetails = json_decode($payroll->deductions_json ?? '[]', true);
        $netPayInWords = $this->numberToWords($payroll->net_pay);
        return view('payroll.slip', compact('payroll', 'netPayInWords'));
    }

    /**
     * Download payslip as PDF
     */
    public function slipDownload($id)
    {
        $businessId = $this->currentBusinessId();
        $payroll = $this->scopeByBusiness(Payroll::with('employee'), 'payrolls', $businessId)->findOrFail($id);
        $payroll->allowanceDetails = json_decode($payroll->allowances_json ?? '[]', true);
        $payroll->deductionDetails = json_decode($payroll->deductions_json ?? '[]', true);
        $netPayInWords = $this->numberToWords($payroll->net_pay);

        // If you have dompdf/snappy installed:
        // $pdf = PDF::loadView('payroll.slip', compact('payroll','netPayInWords'));
        // return $pdf->download('payslip-' . $payroll->reference . '.pdf');

        // Fallback: just show the slip with print prompt
        return view('payroll.slip', compact('payroll', 'netPayInWords'));
    }

    /**
     * Payroll history
     */
    public function history()
    {
        $businessId = $this->currentBusinessId();
        $runs = $this->scopeByBusiness(PayrollRun::query(), 'payroll_runs', $businessId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return view('payroll.history', compact('runs'));
    }

    /**
     * Export payroll to CSV
     */
    public function export(Request $request)
    {
        $businessId = $this->currentBusinessId();
        $runId = $request->run_id;
        $payrollsQuery = $this->scopeByBusiness(Payroll::with('employee'), 'payrolls', $businessId);
        $filename = 'payroll-' . now()->format('Y-m') . '.csv';

        if (!empty($runId)) {
            $run = $this->scopeByBusiness(PayrollRun::query(), 'payroll_runs', $businessId)->find($runId);
            $payrolls = $payrollsQuery->where('payroll_run_id', $runId)->get();
            if ($run) {
                $filename = 'payroll-run-' . str_replace(' ', '-', strtolower($run->period)) . '.csv';
            }
        } else {
            $month = $request->month ?? now()->format('Y-m');
            $date  = Carbon::parse($month . '-01');
            $payrolls = $payrollsQuery
                ->whereYear('pay_period', $date->year)
                ->whereMonth('pay_period', $date->month)
                ->get();
            $filename = 'payroll-' . $date->format('Y-m') . '.csv';
        }
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];

        $callback = function () use ($payrolls) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee', 'ID', 'Department', 'Basic Salary', 'Allowances', 'Deductions', 'Net Pay', 'Status']);
            foreach ($payrolls as $p) {
                fputcsv($file, [
                    $p->employee->name ?? '',
                    $p->employee->employee_id ?? '',
                    $p->employee->department ?? '',
                    $p->basic_salary,
                    $p->total_allowances,
                    $p->total_deductions,
                    $p->net_pay,
                    $p->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mark payroll as paid
     */
    public function markPaid($id)
    {
        $businessId = $this->currentBusinessId();
        $payroll = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)->findOrFail($id);
        $payroll->update(['status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', 'Payroll marked as paid.');
    }

    // ─── Helpers ───────────────────────────────────────────
    private function generateEmployeeId(): string
    {
        $businessId = $this->currentBusinessId();
        $query = Employee::query()->orderBy('id', 'desc');
        $last = $this->scopeByBusiness($query, 'employees', $businessId)->first();
        $num  = $last ? ($last->id + 1) : 1;
        return 'EMP-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    private function currentBusinessId(): ?int
    {
        $tenantId = session('current_tenant_id');
        if (!empty($tenantId)) {
            return (int) $tenantId;
        }

        $user = auth()->user();
        if ($user && !empty($user->company_id)) {
            return (int) $user->company_id;
        }

        return null;
    }

    private function scopeByBusiness($query, string $table, ?int $businessId)
    {
        if (!$businessId) {
            return $query;
        }

        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'business_id')) {
            return $query;
        }

        return $query->where($table . '.business_id', $businessId);
    }

    private function numberToWords(float $amount): string
    {
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $whole     = (int) $amount;
        $decimal   = round(($amount - $whole) * 100);
        $words     = ucfirst($formatter->format($whole)) . ' Naira';
        if ($decimal > 0) $words .= ' and ' . ucfirst($formatter->format($decimal)) . ' Kobo';
        return $words . ' Only';
    }
}
