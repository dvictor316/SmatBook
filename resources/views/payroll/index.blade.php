@extends('layout.app')

@section('title', 'Payroll Management')

@section('content')
<style>
:root {
    --blue-deep: #0f172a;
    --gold: #2563eb;
    --gold-bright: #93c5fd;
    --red: #dc2626;
    --blue-light: #f0f7ff;
}

.payroll-shell {
    padding: 1.5rem;
    width: 100%;
    max-width: 1560px;
    margin: 0 auto;
    min-width: 0;
    overflow-x: hidden;
}
.payroll-shell .row { margin-left: 0; margin-right: 0; }
.payroll-shell .row > * {
    padding-left: calc(var(--bs-gutter-x, 1.5rem) * 0.5);
    padding-right: calc(var(--bs-gutter-x, 1.5rem) * 0.5);
}
@media (max-width: 991.98px) {
    .payroll-shell { padding: 1rem 0.75rem; }
}

/* 
   --------------------------------------------------
   COMPONENT STYLES
   --------------------------------------------------
*/

.payroll-header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 18px 24px;
    color: var(--blue-deep);
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(15,23,42,0.04);
}
.payroll-header h1 { font-size: 0.92rem; font-weight: 800; margin: 0; color: var(--blue-deep); }
.payroll-header p { color: #64748b; margin: 4px 0 0; font-size: 0.78rem; }
.header-badge {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #2563eb;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    display: inline-block;
    margin-bottom: 8px;
}

/* KPI Cards */
.kpi-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    height: 100%;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
    box-shadow: 0 2px 8px rgba(15,23,42,0.04);
}
.kpi-card:hover { box-shadow: 0 6px 20px rgba(15,23,42,0.08); }
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 4px; height: 100%;
    background: #2563eb;
}
.kpi-card.blue::before { background: #2563eb; }
.kpi-card.red::before { background: var(--red); }
.kpi-card.green::before { background: #16a34a; }
.kpi-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    margin-bottom: 10px;
}
.kpi-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 4px; }
.kpi-value { font-size: 0.88rem; font-weight: 800; color: var(--blue-deep); line-height: 1.2; margin-bottom: 4px; }
.kpi-sub { font-size: 0.72rem; font-weight: 600; }
.kpi-sub.up { color: #16a34a; }
.kpi-sub.down { color: var(--red); }
.kpi-sub.neutral { color: #d97706; }

/* Table */
.payroll-table-wrap {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow-x: auto;
    overflow-y: hidden;
    box-shadow: 0 2px 8px rgba(15,23,42,0.04);
}
.payroll-table-header {
    padding: 12px 18px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.payroll-table-header h6 { font-weight: 700; color: var(--blue-deep); margin: 0; font-size: 0.8rem; }
.table-search {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 0.78rem;
    color: var(--blue-deep);
    outline: none;
    width: 200px;
}
.table-search:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.payroll-table { width: 100%; border-collapse: collapse; }
.payroll-table th {
    padding: 9px 13px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    text-align: left;
    white-space: nowrap;
}
.payroll-table td {
    padding: 10px 13px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.8rem;
    color: #334155;
    vertical-align: middle;
}
.payroll-table tr:last-child td { border-bottom: none; }
.payroll-table tr:hover td { background: #f8faff; }
.staff-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--blue-deep), #004080);
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.75rem;
    flex-shrink: 0;
}
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}
.status-paid { background: #dcfce7; color: #15803d; }
.status-pending { background: #fef9c3; color: #854d0e; }
.status-processing { background: #dbeafe; color: #1d4ed8; }
.status-failed { background: #fee2e2; color: #991b1b; }

/* Action Buttons */
.btn-gold {
    background: #2563eb;
    color: #fff !important;
    border: none;
    padding: 8px 18px;
    font-weight: 700;
    border-radius: 8px;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    transition: background 0.2s, box-shadow 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}
.btn-gold:hover { background: #1d4ed8; color: #fff !important; box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
.btn-blue {
    background: #0f172a;
    color: white !important;
    border: none;
    padding: 8px 18px;
    font-weight: 700;
    border-radius: 8px;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    transition: background 0.2s, box-shadow 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}
.btn-blue:hover { background: #1e293b; color: white !important; box-shadow: 0 4px 12px rgba(15,23,42,0.25); }
.btn-outline {
    background: transparent;
    color: #334155 !important;
    border: 1.5px solid #e2e8f0;
    padding: 6px 13px;
    font-weight: 600;
    border-radius: 8px;
    font-size: 0.74rem;
    transition: border-color 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
}
.btn-outline:hover { border-color: #2563eb; color: #2563eb !important; }

/* Payroll Cycle Card */
.cycle-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 14px;
    padding: 18px;
    color: white;
    height: 100%;
}
.cycle-card h6 { color: #93c5fd; font-weight: 700; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 12px; }
.cycle-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.cycle-item:last-child { border-bottom: none; }
.cycle-label { font-size: 0.74rem; color: rgba(255,255,255,0.6); }
.cycle-value { font-size: 0.78rem; font-weight: 700; color: white; }

/* Progress ring */
.progress-ring-wrap { text-align: center; padding: 8px 0; }
.progress-ring-label { font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-top: 6px; }

/* Chart bar */
.bar-chart-wrap { padding: 4px 0; }
.bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.bar-label { font-size: 0.7rem; color: #64748b; width: 80px; flex-shrink: 0; text-align: right; }
.bar-track { flex: 1; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 99px; background: linear-gradient(to right, #2563eb, #60a5fa); transition: width 1.2s ease; }
.bar-val { font-size: 0.7rem; font-weight: 700; color: var(--blue-deep); width: 50px; }

/* Modal */
.modal-content { border: none; border-radius: 16px; overflow: hidden; }
.modal-header {
    background: linear-gradient(135deg, var(--blue-deep), #003d6b);
    color: white;
    border: none;
    padding: 20px 28px;
}
.modal-header .modal-title { font-weight: 800; font-size: 1rem; }
.modal-header .btn-close { filter: invert(1); }
.modal-body { padding: 28px; }
.modal-footer { border-top: 1px solid #e8ecf4; padding: 16px 28px; }
.form-label { font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 6px; }
.form-control, .form-select {
    border: 1.5px solid #e8ecf4;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.88rem;
    color: var(--blue-deep);
    transition: all 0.2s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(197,160,89,0.15);
    outline: none;
}
.input-group-text {
    background: #f8faff;
    border: 1.5px solid #e8ecf4;
    border-radius: 8px 0 0 8px;
    color: #8a92a0;
    font-weight: 700;
    font-size: 0.82rem;
}

@media (max-width: 768px) {
    .payroll-header { padding: 14px 16px; }
    .payroll-header h1 { font-size: 0.88rem; }
    .kpi-value { font-size: 0.88rem; }
    .table-search { width: 100%; }
    .payroll-table-header { flex-direction: column; align-items: flex-start; }
    .payroll-header .btn-gold,
    .payroll-header .btn-blue,
    .payroll-table-header .btn-outline,
    .payroll-table-header .form-select {
        width: 100% !important;
        justify-content: center;
    }
    .payroll-table td, .payroll-table th { padding: 8px 10px; font-size: 0.74rem; }
}
</style>

<div class="page-wrapper">
<div class="payroll-shell">

    <div class="payroll-header mb-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <span class="header-badge">💰 Payroll Module</span>
                <h1>Payroll Management</h1>
                <p>Manage staff salaries, deductions, bonuses and payment runs</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ ($schemaReady ?? true) ? route('payroll.create') : 'javascript:void(0);' }}"
                   class="btn-gold {{ ($schemaReady ?? true) ? '' : 'opacity-50 pe-none' }}">
                    <i class="fas fa-plus"></i> Add Employee
                </a>
                <a href="{{ ($schemaReady ?? true) ? route('payroll.run') : 'javascript:void(0);' }}"
                   class="btn-blue {{ ($schemaReady ?? true) ? '' : 'opacity-50 pe-none' }}">
                    <i class="fas fa-play-circle"></i> Run Payroll
                </a>
            </div>
        </div>
    </div>

    @if(!($schemaReady ?? true))
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <strong>Payroll setup incomplete:</strong> Some payroll tables are missing in the database. Run migrations, then refresh this page.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#f0f4ff;">💼</div>
                <div class="kpi-label">Total Staff</div>
                <div class="kpi-value">{{ $totalStaff ?? 0 }}</div>
                <div class="kpi-sub neutral">{{ $activeStaff ?? 0 }} active</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card blue">
                <div class="kpi-icon" style="background:#f0f4ff;">💳</div>
                <div class="kpi-label">Monthly Payroll</div>
                <div class="kpi-value">₦{{ number_format($monthlyPayroll ?? 0) }}</div>
                <div class="kpi-sub up">↑ {{ $payrollChange ?? 0 }}% vs last</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card green">
                <div class="kpi-icon" style="background:#dcfce7;">✅</div>
                <div class="kpi-label">Paid This Month</div>
                <div class="kpi-value">{{ $paidCount ?? 0 }}</div>
                <div class="kpi-sub up">₦{{ number_format($paidAmount ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card red">
                <div class="kpi-icon" style="background:#fee2e2;">⏳</div>
                <div class="kpi-label">Pending</div>
                <div class="kpi-value">{{ $pendingCount ?? 0 }}</div>
                <div class="kpi-sub down">₦{{ number_format($pendingAmount ?? 0) }} due</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-lg-4">
            <div class="cycle-card h-100">
                <h6>📅 Current Pay Cycle</h6>
                <div class="progress-ring-wrap mb-3">
                    <svg viewBox="0 0 100 100" style="width:110px;height:110px;">
                        <circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="10"/>
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#c5a059" stroke-width="10"
                            stroke-dasharray="{{ ($cycleProgress ?? 65) * 2.51 }} 251"
                            stroke-dashoffset="62.75" transform="rotate(-90 50 50)" stroke-linecap="round"/>
                        <text x="50" y="46" text-anchor="middle" font-size="16" font-weight="900" fill="white">{{ $cycleProgress ?? 65 }}%</text>
                        <text x="50" y="58" text-anchor="middle" font-size="8" fill="rgba(255,255,255,0.6)">complete</text>
                    </svg>
                    <div class="progress-ring-label">{{ now()->format('F Y') }} Payroll Cycle</div>
                </div>
                <div class="cycle-item">
                    <span class="cycle-label">Pay Period</span>
                    <span class="cycle-value">{{ now()->startOfMonth()->format('M 1') }} – {{ now()->endOfMonth()->format('M d') }}</span>
                </div>
                <div class="cycle-item">
                    <span class="cycle-label">Pay Date</span>
                    <span class="cycle-value" style="color:#93c5fd;">{{ now()->endOfMonth()->format('M d, Y') }}</span>
                </div>
                <div class="cycle-item">
                    <span class="cycle-label">Total Gross</span>
                    <span class="cycle-value">₦{{ number_format($monthlyPayroll ?? 0) }}</span>
                </div>
                <div class="cycle-item">
                    <span class="cycle-label">Total Deductions</span>
                    <span class="cycle-value" style="color:#f87171;">-₦{{ number_format($totalDeductions ?? 0) }}</span>
                </div>
                <div class="cycle-item">
                    <span class="cycle-label">Net Payable</span>
                    <span class="cycle-value" style="color:#4ade80; font-size:0.82rem;">₦{{ number_format($netPayable ?? 0) }}</span>
                </div>
                <div class="mt-4">
                    <a href="{{ ($schemaReady ?? true) ? route('payroll.run') : 'javascript:void(0);' }}"
                       class="btn-gold w-100 justify-content-center {{ ($schemaReady ?? true) ? '' : 'opacity-50 pe-none' }}">
                        <i class="fas fa-bolt"></i> Process Payroll Now
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="payroll-table-wrap h-100">
                <div class="payroll-table-header">
                    <h6><i class="fas fa-chart-bar me-2" style="color:var(--gold);"></i>Salary Distribution by Department</h6>
                    <span class="status-badge status-processing">{{ now()->format('M Y') }}</span>
                </div>
                <div class="p-4">
                    <div class="bar-chart-wrap" id="deptChart">
                        @php
                        $departments = $deptBreakdown ?? [
                            ['name'=>'Management','amount'=>450000,'pct'=>90],
                            ['name'=>'Sales','amount'=>320000,'pct'=>64],['name'=>'IT','amount'=>380000,'pct'=>76],['name'=>'Finance','amount'=>280000,'pct'=>56],['name'=>'Operations','amount'=>210000,'pct'=>42],['name'=>'HR','amount'=>175000,'pct'=>35],
                        ];
                        @endphp
                        @foreach($departments as $dept)
                        <div class="bar-row">
                            <div class="bar-label">{{ $dept['name'] }}</div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:0;" data-width="{{ $dept['pct'] }}%"></div>
                            </div>
                            <div class="bar-val">₦{{ number_format($dept['amount']/1000) }}K</div>
                        </div>
                        @endforeach
                    </div>

                    <hr style="border-color:#e8ecf4; margin:20px 0;">

                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div style="font-size:0.68rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">Basic Salary</div>
                            <div style="font-size:0.82rem;font-weight:800;color:var(--blue-deep);">₦{{ number_format($totalBasic ?? 0) }}</div>
                        </div>
                        <div class="col-4">
                            <div style="font-size:0.68rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">Allowances</div>
                            <div style="font-size:0.82rem;font-weight:800;color:#16a34a;">+₦{{ number_format($totalAllowances ?? 0) }}</div>
                        </div>
                        <div class="col-4">
                            <div style="font-size:0.68rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">Deductions</div>
                            <div style="font-size:0.82rem;font-weight:800;color:var(--red);">-₦{{ number_format($totalDeductions ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="payroll-table-wrap mb-4">
        <div class="payroll-table-header">
            <h6><i class="fas fa-users me-2" style="color:var(--gold);"></i>Staff Payroll — {{ now()->format('F Y') }}</h6>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <input type="text" class="table-search" id="payrollSearch" placeholder="🔍 Search staff...">
                <select class="form-select" style="width:auto;font-size:0.8rem;padding:8px 12px;" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                </select>
                <a href="{{ route('payroll.export') }}" class="btn-outline">
                    <i class="fas fa-download"></i> Export
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="payroll-table" id="payrollTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls ??[] as $index => $payroll)
                    <tr data-status="{{ strtolower($payroll->status) }}">
                        <td style="color:#8a92a0;font-weight:700;">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="staff-avatar">{{ strtoupper(substr($payroll->employee->name ?? 'N', 0, 2)) }}</div>
                                <div>
                                    <div style="font-weight:700;color:var(--blue-deep);font-size:0.85rem;">{{ $payroll->employee->name ?? 'N/A' }}</div>
                                    <div style="font-size:0.72rem;color:#8a92a0;">{{ $payroll->employee->employee_id ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="background:#f0f4ff;color:var(--blue-deep);padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;">
                                {{ $payroll->employee->department ?? 'N/A' }}
                            </span>
                        </td>
                        <td style="font-weight:700;">₦{{ number_format($payroll->basic_salary) }}</td>
                        <td style="color:#22c55e;font-weight:700;">+₦{{ number_format($payroll->total_allowances) }}</td>
                        <td style="color:var(--red);font-weight:700;">-₦{{ number_format($payroll->total_deductions) }}</td>
                        <td style="font-weight:900;color:var(--blue-deep);font-size:0.95rem;">₦{{ number_format($payroll->net_pay) }}</td>
                        <td>
                            <span class="status-badge status-{{ strtolower($payroll->status) }}">
                                {{ ucfirst($payroll->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('payroll.show', $payroll->id) }}" class="btn-outline" style="padding:5px 10px;font-size:0.72rem;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('payroll.slip', $payroll->id) }}" class="btn-outline" style="padding:5px 10px;font-size:0.72rem;" title="Download Slip">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div style="color:#8a92a0;">
                                <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:10px;opacity:0.3;"></i>
                                No payroll records found.<br>
                                <a href="{{ route('payroll.create') }}" class="btn-gold mt-3 d-inline-flex">
                                    <i class="fas fa-plus"></i> Add First Employee
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($payrolls) && $payrolls->hasPages())
        <div class="p-3 border-top d-flex justify-content-center">
            {{ $payrolls->links() }}
        </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="payroll-table-wrap">
                <div class="payroll-table-header">
                    <h6><i class="fas fa-history me-2" style="color:var(--gold);"></i>Recent Payroll Runs</h6>
                    <a href="{{ route('payroll.history') }}" class="btn-outline" style="padding:6px 14px;font-size:0.75rem;">View All</a>
                </div>
                <table class="payroll-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Staff Paid</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRuns ??[] as $run)
                        <tr>
                            <td style="font-weight:700;">{{ $run->period }}</td>
                            <td>{{ $run->staff_count }} staff</td>
                            <td style="font-weight:700;">₦{{ number_format($run->total_amount) }}</td>
                            <td><span class="status-badge status-{{ strtolower($run->status) }}">{{ ucfirst($run->status) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4" style="color:#8a92a0;">No payroll runs yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="payroll-table-wrap">
                <div class="payroll-table-header">
                    <h6><i class="fas fa-calculator me-2" style="color:var(--gold);"></i>Deduction Summary</h6>
                </div>
                <div class="p-4">
                    @php
                    $deductions = $deductionSummary ?? [['name'=>'PAYE Tax','amount'=>0,'color'=>'#ef4444'],['name'=>'Pension (Employee 8%)','amount'=>0,'color'=>'#f59e0b'],['name'=>'Pension (Employer 10%)','amount'=>0,'color'=>'#8b5cf6'],['name'=>'NHF (2.5%)','amount'=>0,'color'=>'#06b6d4'],
                        ['name'=>'Other Deductions','amount'=>0,'color'=>'#6b7280'],
                    ];
                    @endphp
                    @foreach($deductions as $d)
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="border-color:#f0f4f8!important;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:10px;height:10px;border-radius:50%;background:{{ $d['color'] }};flex-shrink:0;"></div>
                            <span style="font-size:0.83rem;color:#3d4a5c;">{{ $d['name'] }}</span>
                        </div>
                        <span style="font-weight:800;font-size:0.85rem;color:var(--blue-deep);">₦{{ number_format($d['amount']) }}</span>
                    </div>
                    @endforeach
                    <div class="d-flex justify-content-between mt-3 pt-2">
                        <span style="font-weight:700;font-size:0.8rem;color:var(--blue-deep);">Total Deductions</span>
                        <span style="font-weight:800;font-size:0.82rem;color:var(--red);">₦{{ number_format($totalDeductions ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Bar chart animation on scroll
document.addEventListener('DOMContentLoaded', function() {
    const fills = document.querySelectorAll('.bar-fill');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.width = entry.target.dataset.width;
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    fills.forEach(f => observer.observe(f));

    // Table search
    const search = document.getElementById('payrollSearch');
    const filter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('#payrollTable tbody tr[data-status]');

    function filterTable() {
        const q = search.value.toLowerCase();
        const s = filter.value.toLowerCase();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const status = row.dataset.status;
            const matchSearch = !q || text.includes(q);
            const matchStatus = !s || status === s;
            row.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });
    }
    if (search) search.addEventListener('input', filterTable);
    if (filter) filter.addEventListener('change', filterTable);
});
</script>
</div>
@endsection
