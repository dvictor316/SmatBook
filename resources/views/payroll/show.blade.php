@extends('layout.app')
@section('title', 'Payroll Details')

@section('content')
<style>
:root { --blue-deep:#002347; --gold:#c5a059; --gold-bright:#ffdf91; --red:#bc002d; }
.payroll-shell { width:100%; max-width:100%; padding:1.5rem 0.75rem; overflow-x:hidden; }
.payroll-shell .row { margin-left:0; margin-right:0; }
.payroll-shell .row > * { padding-left:calc(var(--bs-gutter-x, 1.5rem) * 0.5); padding-right:calc(var(--bs-gutter-x, 1.5rem) * 0.5); }
.page-header { background:linear-gradient(135deg,var(--blue-deep),#003d6b); border-radius:16px; padding:28px 32px; color:white; margin-bottom:28px; }
.page-header h1 { font-size:1.5rem; font-weight:800; margin:0; }
.page-header p { color:rgba(255,255,255,0.7); margin:6px 0 0; font-size:0.88rem; }
.card-wrap { background:#fff; border:1px solid #e8ecf4; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,35,71,0.05); }
.card-header { padding:16px 24px; border-bottom:1px solid #e8ecf4; background:#f8faff; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.card-header h6 { font-weight:800; color:var(--blue-deep); margin:0; font-size:0.9rem; }
.card-body { padding:24px; }
.info-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.info-item { background:#f8faff; border:1px solid #e8ecf4; border-radius:10px; padding:12px 14px; }
.info-label { font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#8a92a0; }
.info-value { font-size:0.9rem; font-weight:700; color:var(--blue-deep); margin-top:4px; }
.table { width:100%; border-collapse:collapse; }
.table th { padding:10px 14px; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#8a92a0; border-bottom:1px solid #e8ecf4; background:#f8faff; text-align:left; }
.table td { padding:12px 14px; font-size:0.85rem; color:#3d4a5c; border-bottom:1px solid #f0f4f8; }
.table tr:last-child td { border-bottom:none; }
.status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:0.7rem; font-weight:800; letter-spacing:0.5px; }
.status-paid { background:#dcfce7; color:#15803d; }
.status-pending { background:#fef9c3; color:#854d0e; }
.status-processing { background:#dbeafe; color:#1d4ed8; }
.status-failed { background:#fee2e2; color:#991b1b; }
.btn-gold { background:linear-gradient(135deg,var(--gold),var(--gold-bright)); color:var(--blue-deep)!important; border:none; padding:10px 22px; font-weight:800; border-radius:8px; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s; text-decoration:none; display:inline-flex; align-items:center; gap:7px; cursor:pointer; }
.btn-outline { background:transparent; color:var(--blue-deep)!important; border:1.5px solid #e8ecf4; padding:9px 16px; font-weight:700; border-radius:8px; font-size:0.78rem; transition:all 0.3s; text-decoration:none; display:inline-flex; align-items:center; gap:6px; cursor:pointer; }
.btn-outline:hover { border-color:var(--gold); color:var(--gold)!important; }
.net-box { background:linear-gradient(135deg,var(--blue-deep),#003d6b); border-radius:12px; padding:18px 22px; color:white; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.net-amount { font-size:1.6rem; font-weight:900; color:var(--gold-bright); }
@media(max-width:768px){ .info-grid{grid-template-columns:1fr;} .page-header h1{font-size:1.2rem;} }
@media(min-width:768px){ .payroll-shell{ padding-left:1rem; padding-right:1rem; } }
</style>

<div class="payroll-shell">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="{{ route('payroll.index') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>Payroll Details</h1>
                <p>{{ $payroll->employee->name ?? 'Employee' }} · {{ \Carbon\Carbon::parse($payroll->pay_period)->format('F Y') }}</p>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('payroll.slip', $payroll->id) }}" class="btn-outline"><i class="fas fa-file-invoice"></i> View Payslip</a>
        <a href="{{ route('payroll.slip.download', $payroll->id) }}" class="btn-outline"><i class="fas fa-download"></i> Download Slip</a>
        @if($payroll->employee_id)
        <a href="{{ route('payroll.edit', $payroll->employee_id) }}" class="btn-outline"><i class="fas fa-user-edit"></i> Edit Employee</a>
        @endif
        @if(($payroll->status ?? '') !== 'paid')
        <form action="{{ route('payroll.mark-paid', $payroll->id) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn-gold"><i class="fas fa-check"></i> Mark Paid</button>
        </form>
        @endif
    </div>

    <div class="card-wrap mb-4">
        <div class="card-header">
            <h6><i class="fas fa-info-circle me-2" style="color:var(--gold);"></i>Payroll Summary</h6>
            <span class="status-badge status-{{ strtolower($payroll->status ?? 'pending') }}">{{ ucfirst($payroll->status ?? 'Pending') }}</span>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Employee</div>
                    <div class="info-value">{{ $payroll->employee->name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Employee ID</div>
                    <div class="info-value">{{ $payroll->employee->employee_id ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Department</div>
                    <div class="info-value">{{ $payroll->employee->department ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pay Period</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($payroll->pay_period)->format('M 1') }} – {{ \Carbon\Carbon::parse($payroll->pay_period)->endOfMonth()->format('M d, Y') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pay Date</div>
                    <div class="info-value">{{ $payroll->payrollRun?->pay_date ? \Carbon\Carbon::parse($payroll->payrollRun->pay_date)->format('d M Y') : 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Reference</div>
                    <div class="info-value">{{ $payroll->reference ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-wrap">
                <div class="card-header">
                    <h6><i class="fas fa-plus-circle me-2" style="color:#22c55e;"></i>Earnings</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th style="text-align:right;">Amount (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Basic Salary</td>
                                <td style="text-align:right;font-weight:700;">{{ number_format($payroll->basic_salary, 2) }}</td>
                            </tr>
                            @foreach($payroll->allowanceDetails ?? [] as $allowance)
                            <tr>
                                <td style="padding-left:24px;color:#6b7280;">{{ $allowance['name'] }}</td>
                                <td style="text-align:right;color:#22c55e;font-weight:600;">{{ number_format($allowance['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td style="font-weight:800;">Gross Pay</td>
                                <td style="text-align:right;font-weight:800;">{{ number_format($payroll->gross_pay ?? ($payroll->basic_salary + $payroll->total_allowances), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-wrap">
                <div class="card-header">
                    <h6><i class="fas fa-minus-circle me-2" style="color:var(--red);"></i>Deductions</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th style="text-align:right;">Amount (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payroll->deductionDetails ?? [] as $deduction)
                            <tr>
                                <td>{{ $deduction['name'] }}</td>
                                <td style="text-align:right;color:var(--red);font-weight:600;">{{ number_format($deduction['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td style="font-weight:800;">Total Deductions</td>
                                <td style="text-align:right;font-weight:800;color:var(--red);">{{ number_format($payroll->total_deductions, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card-wrap mt-4">
        <div class="card-body">
            <div class="net-box">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,0.65);">Net Pay</div>
                    <div class="net-amount">₦{{ number_format($payroll->net_pay, 2) }}</div>
                    <div style="font-size:0.78rem; color:rgba(255,255,255,0.55);">{{ $payroll->net_pay_words ?? '' }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.55);">Generated</div>
                    <div style="font-size:0.9rem;font-weight:700;">{{ now()->format('d M Y') }}</div>
                    @if($payroll->paid_at)
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.55);margin-top:8px;">Paid</div>
                    <div style="font-size:0.9rem;font-weight:700;color:var(--gold-bright);">{{ \Carbon\Carbon::parse($payroll->paid_at)->format('d M Y') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
