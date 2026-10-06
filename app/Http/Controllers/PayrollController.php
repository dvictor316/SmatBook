<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    /**
     * Payroll Dashboard
     */
    public function index()
    {
        $hasEmployeesTable = Schema::hasTable('employees');
        $hasPayrollsTable = Schema::hasTable('payrolls');
        $hasPayrollRunsTable = Schema::hasTable('payroll_runs');
        $schemaReady = $hasEmployeesTable && $hasPayrollsTable && $hasPayrollRunsTable;

        if (! $schemaReady) {
            $payrolls = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            $totalStaff = 0;
            $activeStaff = 0;
            $monthlyPayroll = 0;
            $payrollChange = 0;
            $paidCount = 0;
            $paidAmount = 0;
            $pendingCount = 0;
            $pendingAmount = 0;
            $totalBasic = 0;
            $totalAllowances = 0;
            $totalDeductions = 0;
            $netPayable = 0;
            $deptBreakdown = [];
            $recentRuns = collect();
            $deductionSummary = [];
            $cycleProgress = min(100, round((Carbon::now()->day / Carbon::now()->daysInMonth) * 100));

            $missing = collect([
                'employees' => $hasEmployeesTable,
                'payrolls' => $hasPayrollsTable,
                'payroll_runs' => $hasPayrollRunsTable,
            ])->filter(fn ($exists) => ! $exists)->keys()->implode(', ');

            session()->flash('error', 'Payroll schema is incomplete. Missing table(s): '.$missing.'. Run database migrations to enable Payroll fully.');

            return view('payroll.index', compact(
                'payrolls', 'totalStaff', 'activeStaff', 'monthlyPayroll', 'payrollChange',
                'paidCount', 'paidAmount', 'pendingCount', 'pendingAmount',
                'totalBasic', 'totalAllowances', 'totalDeductions', 'netPayable',
                'deptBreakdown', 'recentRuns', 'deductionSummary', 'cycleProgress', 'schemaReady'
            ));
        }

        $schemaReady = true;
        $businessId = $this->currentBusinessId();
        $currentMonth = Carbon::now()->startOfMonth();

        // Staff counts
        $totalStaff = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->count();
        $activeStaff = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->where('status', 'active')->count();

        // Current month payroll totals
        $payrolls = $this->scopeByBusiness(Payroll::with('employee'), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->paginate(15);

        $monthlyPayroll = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
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

        $paidCount = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'paid')
            ->count();
        $paidAmount = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'paid')
            ->sum('net_pay');
        $pendingCount = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'pending')
            ->count();
        $pendingAmount = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->where('status', 'pending')
            ->sum('net_pay');

        $totalBasic = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->sum('basic_salary');
        $totalAllowances = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->sum('total_allowances');
        $totalDeductions = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->sum('total_deductions');
        $netPayable = $monthlyPayroll;

        // Department breakdown
        $deptBreakdown = $this->scopeByBusiness(Payroll::with('employee'), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->get()
            ->groupBy(fn ($p) => $p->employee->department ?? 'Unknown')
            ->map(fn ($group, $dept) => [
                'name' => $dept,
                'amount' => $group->sum('net_pay'),
                'pct' => 0,
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

        $deductionColors = ['#ef4444', '#f59e0b', '#8b5cf6', '#06b6d4', '#6b7280'];
        $deductionSummary = $this->scopeByBusiness(Payroll::query(), 'payrolls', $businessId)
            ->whereYear('pay_period', $currentMonth->year)
            ->whereMonth('pay_period', $currentMonth->month)
            ->get(['deductions_json'])
            ->flatMap(fn (Payroll $payroll) => $payroll->deduction_details)
            ->filter(fn ($item) => filled($item['name'] ?? null) && (float) ($item['amount'] ?? 0) > 0)
            ->groupBy(fn ($item) => strtolower(trim((string) $item['name'])))
            ->values()
            ->map(fn ($items, $index) => [
                'name' => (string) ($items->first()['name'] ?? 'Deduction'),
                'amount' => (float) $items->sum(fn ($item) => (float) ($item['amount'] ?? 0)),
                'color' => $deductionColors[$index % count($deductionColors)],
            ])
            ->all();

        $cycleProgress = min(100, round((Carbon::now()->day / Carbon::now()->daysInMonth) * 100));

        return view('payroll.index', compact(
            'payrolls', 'totalStaff', 'activeStaff', 'monthlyPayroll', 'payrollChange',
            'paidCount', 'paidAmount', 'pendingCount', 'pendingAmount',
            'totalBasic', 'totalAllowances', 'totalDeductions', 'netPayable',
            'deptBreakdown', 'recentRuns', 'deductionSummary', 'cycleProgress', 'schemaReady'
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
        $businessId = $this->currentBusinessId();
        $data = $this->validatedEmployeeData($request, $businessId);

        DB::transaction(function () use ($data, $businessId) {
            Employee::create([
                ...$data,
                'employee_id' => $data['employee_id'] ?? $this->generateEmployeeId(),
                'status' => 'active',
                'business_id' => $businessId,
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
        $businessId = $this->currentBusinessId();
        $employee = $this->scopeByBusiness(Employee::query(), 'employees', $businessId)->findOrFail($id);
        $employee->update($this->validatedEmployeeData($request, $businessId, $employee));

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
        $payPeriod = Carbon::parse($month.'-01');

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
        $businessId = $this->currentBusinessId();
        $request->validate([
            'pay_period' => 'required|date_format:Y-m',
            'pay_date' => 'required|date',
            'payment_method' => 'nullable|in:bank_transfer,cash,cheque',
            'notes' => 'nullable|string|max:2000',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => [
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('business_id', $businessId)->where('status', 'active')),
            ],
        ]);

        $payPeriod = Carbon::parse($request->pay_period.'-01');
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
            return back()->with('warning', 'All selected employees already have payroll for '.$payPeriod->format('F Y').'.');
        }

        DB::transaction(function () use ($request, $businessId, $payPeriod, $processableIds) {

            // Create payroll run record
            $run = PayrollRun::create([
                'period' => $payPeriod->format('F Y'),
                'pay_date' => $request->pay_date,
                'payment_method' => $request->payment_method ?? 'bank_transfer',
                'notes' => $request->notes,
                'status' => 'processing',
                'staff_count' => 0,
                'business_id' => $businessId,
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

                if (! $existing) {
                    Payroll::create([
                        'employee_id' => $empId,
                        'payroll_run_id' => $run->id,
                        'pay_period' => $payPeriod,
                        'basic_salary' => $employee->basic_salary,
                        'total_allowances' => $employee->total_allowances,
                        'total_deductions' => $employee->total_deductions,
                        'gross_pay' => $employee->gross_pay,
                        'net_pay' => $employee->net_pay,
                        'allowances_json' => json_encode($employee->allowances ?? []),
                        'deductions_json' => json_encode($employee->deductions ?? []),
                        'status' => 'pending',
                        'reference' => 'SB-'.strtoupper(uniqid()),
                        'business_id' => $businessId,
                    ]);
                    $totalAmount += $employee->net_pay;
                    $processedCount++;
                }
            }

            $run->update([
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'staff_count' => $processedCount,
            ]);
        });

        $successMessage = 'Payroll processed successfully for '.count($processableIds).' employees.';
        if ($skippedCount > 0) {
            $successMessage .= ' Skipped '.$skippedCount.' already processed for '.$payPeriod->format('F Y').'.';
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

        return Pdf::loadView('payroll.slip', compact('payroll', 'netPayInWords'))
            ->setPaper('a4')
            ->download('payslip-'.($payroll->reference ?: $payroll->id).'.pdf');
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
        $filename = 'payroll-'.now()->format('Y-m').'.csv';

        if (! empty($runId)) {
            $run = $this->scopeByBusiness(PayrollRun::query(), 'payroll_runs', $businessId)->find($runId);
            $payrolls = $payrollsQuery->where('payroll_run_id', $runId)->get();
            if ($run) {
                $filename = 'payroll-run-'.str_replace(' ', '-', strtolower($run->period)).'.csv';
            }
        } else {
            $month = $request->month ?? now()->format('Y-m');
            $date = Carbon::parse($month.'-01');
            $payrolls = $payrollsQuery
                ->whereYear('pay_period', $date->year)
                ->whereMonth('pay_period', $date->month)
                ->get();
            $filename = 'payroll-'.$date->format('Y-m').'.csv';
        }
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];

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
        if ($payroll->status === 'paid') {
            return back()->with('info', 'Payroll is already marked as paid.');
        }
        abort_unless(in_array($payroll->status, ['pending', 'processing'], true), 422, 'Only pending payroll can be marked as paid.');
        $payroll->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', 'Payroll marked as paid.');
    }

    // ─── Helpers ───────────────────────────────────────────
    private function generateEmployeeId(): string
    {
        $businessId = $this->currentBusinessId();
        $query = Employee::query()->orderBy('id', 'desc');
        $last = $this->scopeByBusiness($query, 'employees', $businessId)->first();
        $num = $last ? ($last->id + 1) : 1;

        return 'EMP-'.str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    private function currentBusinessId(): int
    {
        $tenantId = session('current_tenant_id');
        if (! empty($tenantId)) {
            return (int) $tenantId;
        }

        $user = auth()->user();
        if ($user && ! empty($user->company_id)) {
            return (int) $user->company_id;
        }

        abort(403, 'Select a company workspace before accessing payroll.');
    }

    private function scopeByBusiness($query, string $table, ?int $businessId)
    {
        if (! $businessId) {
            return $query;
        }

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'business_id')) {
            return $query;
        }

        return $query->where($table.'.business_id', $businessId);
    }

    private function numberToWords(float $amount): string
    {
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $whole = (int) $amount;
        $decimal = round(($amount - $whole) * 100);
        $words = ucfirst($formatter->format($whole)).' Naira';
        if ($decimal > 0) {
            $words .= ' and '.ucfirst($formatter->format($decimal)).' Kobo';
        }

        return $words.' Only';
    }

    private function validatedEmployeeData(Request $request, int $businessId, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_id' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('employees', 'employee_id')
                    ->where(fn ($query) => $query->where('business_id', $businessId))
                    ->ignore($employee?->id),
            ],
            'department' => ['required', 'string', 'max:120'],
            'job_title' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'employment_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:32'],
            'tax_id' => ['nullable', 'string', 'max:120'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'array'],
            'allowances.*.name' => ['nullable', 'string', 'max:120'],
            'allowances.*.amount' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'array'],
            'deductions.*.name' => ['nullable', 'string', 'max:120'],
            'deductions.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach (['allowances', 'deductions'] as $key) {
            $data[$key] = collect($data[$key] ?? [])
                ->filter(fn ($item) => filled($item['name'] ?? null) || (float) ($item['amount'] ?? 0) > 0)
                ->map(fn ($item) => [
                    'name' => trim((string) ($item['name'] ?? '')),
                    'amount' => round((float) ($item['amount'] ?? 0), 2),
                ])
                ->values()
                ->all();

            if (collect($data[$key])->contains(fn ($item) => $item['name'] === '')) {
                throw ValidationException::withMessages([$key => 'Every payroll line with an amount must have a name.']);
            }
        }

        $basicSalary = round((float) $data['basic_salary'], 2);
        $totalAllowances = round((float) collect($data['allowances'])->sum('amount'), 2);
        $totalDeductions = round((float) collect($data['deductions'])->sum('amount'), 2);
        $grossPay = round($basicSalary + $totalAllowances, 2);
        if ($totalDeductions > $grossPay) {
            throw ValidationException::withMessages(['deductions' => 'Total deductions cannot exceed gross pay.']);
        }

        return array_merge($data, [
            'basic_salary' => $basicSalary,
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'gross_pay' => $grossPay,
            'net_pay' => round($grossPay - $totalDeductions, 2),
        ]);
    }
}
