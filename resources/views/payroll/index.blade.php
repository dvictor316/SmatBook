@extends('layout.app')

@section('title', 'Payroll Management')

@section('content')
<style>
:root {
    --blue-deep: #002347;
    --gold: #c5a059;
    --gold-bright: #ffdf91;
    --red: #bc002d;
    --blue-light: #f4f8ff;
}

/* 
   --------------------------------------------------
   SIDEBAR & LAYOUT LOGIC 
   --------------------------------------------------
*/
.payroll-shell {
    padding: 1.5rem;
    transition: all 0.3s ease; /* Smooth transition when toggling */
    width: 100%;
    overflow-x: hidden;
}

.payroll-shell .row {
    margin-left: 0;
    margin-right: 0;
}
.payroll-shell .row > * {
    padding-left: calc(var(--bs-gutter-x, 1.5rem) * 0.5);
    padding-right: calc(var(--bs-gutter-x, 1.5rem) * 0.5);
}

/* Desktop: Standard State (Sidebar is 270px) */
@media (min-width: 992px) {
    /* When sidebar is OPEN (Default) */
    body:not(.sidebar-icon-only):not(.sidebar-collapse):not(.sidebar-collapsed) .payroll-shell {
        margin-left: 270px;
        width: calc(100% - 270px);
    }

    /* When sidebar is COLLAPSED (Mini/Toggled) */
    body.sidebar-icon-only .payroll-shell,
    body.sidebar-collapse .payroll-shell,
    body.sidebar-collapsed .payroll-shell {
        margin-left: 70px; /* Standard collapsed sidebar width */
        width: calc(100% - 70px);
    }
}

/* Mobile/Tablet: Full Width */
@media (max-width: 991.98px) {
    .payroll-shell {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 1rem 0.75rem;
    }
}

/* 
   --------------------------------------------------
   COMPONENT STYLES
   --------------------------------------------------
*/

.payroll-header {
    background: linear-gradient(135deg, var(--blue-deep) 0%, #003d6b 100%);
    border-radius: 16px;
    padding: 32px 36px;
    color: white;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.payroll-header::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    border-radius: 50%;
    border: 1px solid rgba(197,160,89,0.15);
}
.payroll-header::after {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 140px; height: 140px;
    border-radius: 50%;
    border: 1px solid rgba(197,160,89,0.25);
}
.payroll-header h1 { font-size: 1.8rem; font-weight: 800; margin: 0; }
.payroll-header p { color: rgba(255,255,255,0.7); margin: 6px 0 0; font-size: 0.9rem; }
.header-badge {
    background: rgba(197,160,89,0.2);
    border: 1px solid rgba(197,160,89,0.4);
    color: var(--gold-bright);
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: inline-block;
    margin-bottom: 12px;
}

/* KPI Cards */
.kpi-card {
    background: #fff;
    border: 1px solid #e8ecf4;
    border-radius: 14px;
    padding: 22px 24px;
    height: 100%;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 12px rgba(0,35,71,0.05);
}
.kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,35,71,0.10); }
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 4px; height: 100%;
    background: var(--gold);
}
.kpi-card.blue::before { background: var(--blue-deep); }
.kpi-card.red::before { background: var(--red); }
.kpi-card.green::before { background: #22c55e; }
.kpi-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 14px;
}
.kpi-label { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #8a92a0; margin-bottom: 6px; }
.kpi-value { font-size: 1.7rem; font-weight: 900; color: var(--blue-deep); line-height: 1; margin-bottom: 6px; }
.kpi-sub { font-size: 0.75rem; font-weight: 600; }
.kpi-sub.up { color: #22c55e; }
.kpi-sub.down { color: var(--red); }
.kpi-sub.neutral { color: #f59e0b; }

/* Table */
.payroll-table-wrap {
    background: #fff;
    border: 1px solid #e8ecf4;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,35,71,0.05);
}
.payroll-table-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e8ecf4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.payroll-table-header h6 { font-weight: 800; color: var(--blue-deep); margin: 0; font-size: 0.95rem; }
.table-search {
    border: 1px solid #e8ecf4;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 0.82rem;
    color: var(--blue-deep);
    outline: none;
    width: 220px;
}
.table-search:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(197,160,89,0.15); }
.payroll-table { width: 100%; border-collapse: collapse; }
.payroll-table th {
    padding: 12px 16px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #8a92a0;
    border-bottom: 1px solid #e8ecf4;
    background: #f8faff;
    text-align: left;
    white-space: nowrap;
}
.payroll-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f0f4f8;
    font-size: 0.85rem;
    color: #3d4a5c;
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
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-bright) 100%);
    color: var(--blue-deep) !important;
    border: none;
    padding: 10px 22px;
    font-weight: 800;
    border-radius: 8px;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
}
.btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(197,160,89,0.4); color: var(--blue-deep) !important; }
.btn-blue {
    background: var(--blue-deep);
    color: white !important;
    border: none;
    padding: 10px 22px;
    font-weight: 800;
    border-radius: 8px;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
}
.btn-blue:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,35,71,0.3); color: white !important; }
.btn-outline {
    background: transparent;
    color: var(--blue-deep) !important;
    border: 1.5px solid #e8ecf4;
    padding: 8px 16px;
    font-weight: 700;
    border-radius: 8px;
    font-size: 0.78rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}
.btn-outline:hover { border-color: var(--gold); color: var(--gold) !important; }

/* Payroll Cycle Card */
.cycle-card {
    background: linear-gradient(135deg, #001529 0%, var(--blue-deep) 100%);
    border-radius: 14px;
    padding: 24px;
    color: white;
    height: 100%;
}
.cycle-card h6 { color: var(--gold); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 16px; }
.cycle-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.cycle-item:last-child { border-bottom: none; }
.cycle-label { font-size: 0.82rem; color: rgba(255,255,255,0.65); }
.cycle-value { font-size: 0.88rem; font-weight: 700; color: white; }

/* Progress ring */
.progress-ring-wrap { text-align: center; padding: 10px 0; }
.progress-ring-label { font-size: 0.75rem; color: rgba(255,255,255,0.55); margin-top: 8px; }

/* Chart bar */
.bar-chart-wrap { padding: 4px 0; }
.bar-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.bar-label { font-size: 0.75rem; color: #6b7280; width: 80px; flex-shrink: 0; text-align: right; }
.bar-track { flex: 1; height: 8px; background: #f0f4f8; border-radius: 99px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 99px; background: linear-gradient(to right, var(--blue-deep), var(--gold)); transition: width 1.2s ease; }
.bar-val { font-size: 0.75rem; font-weight: 700; color: var(--blue-deep); width: 55px; }

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
    .payroll-header { padding: 22px 20px; }
    .payroll-header h1 { font-size: 1.35rem; }
    .kpi-value { font-size: 1.35rem; }
    .table-search { width: 100%; }
    .payroll-table-header { flex-direction: column; align-items: flex-start; }
    .payroll-table td, .payroll-table th { padding: 10px 12px; font-size: 0.78rem; }
}
</style>

<div class="payroll-shell">

    {{-- Header --}}
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

    {{-- KPI Row --}}
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

    {{-- Main Content Row --}}
    <div class="row g-4 mb-4">

        {{-- Payroll Cycle Info --}}
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
                    <span class="cycle-value" style="color:var(--gold-bright);">{{ now()->endOfMonth()->format('M d, Y') }}</span>
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
                    <span class="cycle-value" style="color:#4ade80; font-size:1rem;">₦{{ number_format($netPayable ?? 0) }}</span>
                </div>
                <div class="mt-4">
                    <a href="{{ ($schemaReady ?? true) ? route('payroll.run') : 'javascript:void(0);' }}"
                       class="btn-gold w-100 justify-content-center {{ ($schemaReady ?? true) ? '' : 'opacity-50 pe-none' }}">
                        <i class="fas fa-bolt"></i> Process Payroll Now
                    </a>
                </div>
            </div>
        </div>

        {{-- Salary Distribution Chart --}}
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

                    {{-- Summary Row --}}
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div style="font-size:0.7rem;color:#8a92a0;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Basic Salary</div>
                            <div style="font-size:1.1rem;font-weight:900;color:var(--blue-deep);">₦{{ number_format($totalBasic ?? 0) }}</div>
                        </div>
                        <div class="col-4">
                            <div style="font-size:0.7rem;color:#8a92a0;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Allowances</div>
                            <div style="font-size:1.1rem;font-weight:900;color:#22c55e;">+₦{{ number_format($totalAllowances ?? 0) }}</div>
                        </div>
                        <div class="col-4">
                            <div style="font-size:0.7rem;color:#8a92a0;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Deductions</div>
                            <div style="font-size:1.1rem;font-weight:900;color:var(--red);">-₦{{ number_format($totalDeductions ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Staff Payroll Table --}}
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

    {{-- Recent Payroll Runs --}}
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
                        <span style="font-weight:800;font-size:0.9rem;color:var(--blue-deep);">Total Deductions</span>
                        <span style="font-weight:900;font-size:1rem;color:var(--red);">₦{{ number_format($totalDeductions ?? 0) }}</span>
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
@endsection