@extends('layout.app')
@section('title', 'Payslip — ' . ($payroll->employee->name ?? 'Employee'))

@section('content')
<style>
:root { --blue-deep:#002347; --gold:#c5a059; --gold-bright:#ffdf91; --red:#bc002d; }
.slip-wrap { max-width:780px; margin:0 auto; padding:20px; }
.btn-gold { background:linear-gradient(135deg,var(--gold),var(--gold-bright)); color:var(--blue-deep)!important; border:none; padding:11px 24px; font-weight:800; border-radius:8px; font-size:0.82rem; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s; cursor:pointer; display:inline-flex; align-items:center; gap:7px; text-decoration:none; }
.btn-gold:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(197,160,89,0.4); }
.btn-outline { background:transparent; color:var(--blue-deep)!important; border:1.5px solid #e8ecf4; padding:11px 24px; font-weight:700; border-radius:8px; font-size:0.82rem; transition:all 0.3s; cursor:pointer; display:inline-flex; align-items:center; gap:7px; text-decoration:none; }
.btn-outline:hover { border-color:var(--gold); color:var(--gold)!important; }

/* Payslip Card */
.payslip-card {
    background:#fff;
    border:1px solid #e8ecf4;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 4px 24px rgba(0,35,71,0.08);
}
.payslip-top {
    background:linear-gradient(135deg,var(--blue-deep) 0%,#003d6b 100%);
    padding:32px 36px;
    position:relative;
    overflow:hidden;
}
.payslip-top::before {
    content:'';
    position:absolute; top:-40px; right:-40px;
    width:160px;height:160px;border-radius:50%;
    border:1px solid rgba(197,160,89,0.2);
}
.payslip-top::after {
    content:'';
    position:absolute; top:-20px; right:-20px;
    width:100px;height:100px;border-radius:50%;
    border:1px solid rgba(197,160,89,0.3);
}
.company-name { font-size:1.4rem; font-weight:900; color:white; letter-spacing:1px; }
.company-sub { font-size:0.78rem; color:rgba(255,255,255,0.65); margin-top:4px; }
.payslip-badge {
    background:rgba(197,160,89,0.2);
    border:1px solid rgba(197,160,89,0.4);
    color:var(--gold-bright);
    padding:6px 16px; border-radius:20px;
    font-size:0.75rem; font-weight:800; letter-spacing:2px;
    text-transform:uppercase;
}
.payslip-period { color:white; font-size:0.88rem; font-weight:700; margin-top:6px; }
.payslip-body { padding:32px 36px; }
.employee-section {
    background:#f8faff;
    border:1px solid #e8ecf4;
    border-radius:12px;
    padding:20px 24px;
    margin-bottom:24px;
}
.emp-avatar {
    width:56px; height:56px; border-radius:50%;
    background:linear-gradient(135deg,var(--blue-deep),#004080);
    color:white; display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; font-weight:900;
}
.emp-name { font-size:1.1rem; font-weight:800; color:var(--blue-deep); }
.emp-title { font-size:0.82rem; color:#6b7280; margin-top:2px; }
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px; }
.info-item .info-label { font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#8a92a0; }
.info-item .info-value { font-size:0.85rem; font-weight:700; color:var(--blue-deep); margin-top:2px; }

/* Earnings / Deductions Table */
.slip-table { width:100%; border-collapse:collapse; margin-bottom:0; }
.slip-table th { padding:10px 16px; font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.5px; color:#8a92a0; border-bottom:2px solid #e8ecf4; text-align:left; background:#f8faff; }
.slip-table td { padding:12px 16px; font-size:0.88rem; border-bottom:1px solid #f0f4f8; color:#3d4a5c; }
.slip-table tr:last-child td { border-bottom:none; }
.slip-table .total-row td { font-weight:900; color:var(--blue-deep); background:#f8faff; font-size:0.9rem; }
.section-label { font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:2px; padding:10px 16px; color:var(--blue-deep); background:#f0f4ff; border-bottom:1px solid #e8ecf4; }

/* Net Pay Box */
.net-box {
    background:linear-gradient(135deg,var(--blue-deep),#003d6b);
    border-radius:12px; padding:24px 28px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:16px; margin-top:24px;
}
.net-label { font-size:0.75rem; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,0.65); margin-bottom:4px; }
.net-amount { font-size:2rem; font-weight:900; color:var(--gold-bright); }
.net-words { font-size:0.78rem; color:rgba(255,255,255,0.55); margin-top:4px; }
.status-paid { background:#dcfce7; color:#15803d; padding:6px 16px; border-radius:20px; font-size:0.75rem; font-weight:800; }
.status-pending { background:#fef9c3; color:#854d0e; padding:6px 16px; border-radius:20px; font-size:0.75rem; font-weight:800; }

.slip-footer { padding:20px 36px; border-top:1px solid #e8ecf4; background:#f8faff; text-align:center; font-size:0.75rem; color:#8a92a0; }

@media print {
    .no-print { display:none!important; }
    .payslip-card { box-shadow:none; border:1px solid #ddd; }
    body { padding:0; margin:0; }
}
@media(max-width:600px) {
    .payslip-top { padding:22px 20px; }
    .payslip-body { padding:20px; }
    .info-grid { grid-template-columns:1fr; }
    .net-amount { font-size:1.5rem; }
}
</style>

<div class="slip-wrap">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 no-print">
        <a href="{{ route('payroll.index') }}" class="btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Payroll
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn-outline">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="{{ route('payroll.slip.download', $payroll->id) }}" class="btn-gold">
                <i class="fas fa-download"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="payslip-card" id="payslipDocument">

        <div class="payslip-top">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="company-name">{{ config('app.name', 'SMAT GLOBAL BUSINESS LTD') }}</div>
                    <div class="company-sub">{{ config('company.address', '1 New Heaven, Suite 320, Enugu, Nigeria') }}</div>
                    <div class="company-sub">{{ config('company.email', 'accounts@globaltrading.com') }} · {{ config('company.phone', '+234 080 6464 6306') }}</div>
                </div>
                <div class="text-end">
                    <div class="payslip-badge">PAYSLIP</div>
                    <div class="payslip-period mt-2">{{ \Carbon\Carbon::parse($payroll->pay_period)->format('F Y') }}</div>
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.5);margin-top:4px;">Ref: {{ $payroll->reference ?? 'SB-' . str_pad($payroll->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
        </div>

        <div class="payslip-body">

            <div class="employee-section">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="emp-avatar">{{ strtoupper(substr($payroll->employee->name ?? 'E', 0, 2)) }}</div>
                    <div>
                        <div class="emp-name">{{ $payroll->employee->name ?? 'N/A' }}</div>
                        <div class="emp-title">{{ $payroll->employee->job_title ?? '' }} · {{ $payroll->employee->department ?? '' }}</div>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Employee ID</div>
                        <div class="info-value">{{ $payroll->employee->employee_id ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Pay Period</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($payroll->pay_period)->format('M 1') }} – {{ \Carbon\Carbon::parse($payroll->pay_period)->endOfMonth()->format('M d, Y') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Bank</div>
                        <div class="info-value">{{ $payroll->employee->bank_name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account Number</div>
                        <div class="info-value">{{ $payroll->employee->account_number ? '****' . substr($payroll->employee->account_number, -4) : 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tax ID (TIN)</div>
                        <div class="info-value">{{ $payroll->employee->tax_id ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value">
                            <span class="status-{{ strtolower($payroll->status ?? 'pending') }}">{{ ucfirst($payroll->status ?? 'Pending') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <table class="slip-table">
                <tr><td colspan="2" class="section-label">💰 Earnings</td></tr>
                <tr>
                    <th>Description</th>
                    <th style="text-align:right;">Amount (₦)</th>
                </tr>
                <tr>
                    <td>Basic Salary</td>
                    <td style="text-align:right;font-weight:700;">{{ number_format($payroll->basic_salary, 2) }}</td>
                </tr>
                @foreach($payroll->allowanceDetails ?? [] as $allowance)
                <tr>
                    <td style="padding-left:28px;color:#6b7280;">{{ $allowance['name'] }}</td>
                    <td style="text-align:right;color:#22c55e;font-weight:600;">{{ number_format($allowance['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>Gross Pay</td>
                    <td style="text-align:right;">{{ number_format($payroll->gross_pay ?? ($payroll->basic_salary + $payroll->total_allowances), 2) }}</td>
                </tr>
            </table>

            <div style="height:16px;"></div>

            <table class="slip-table">
                <tr><td colspan="2" class="section-label" style="background:#fff5f5;color:var(--red);">➖ Deductions</td></tr>
                <tr>
                    <th>Description</th>
                    <th style="text-align:right;">Amount (₦)</th>
                </tr>
                @foreach($payroll->deductionDetails ?? [] as $deduction)
                <tr>
                    <td>{{ $deduction['name'] }}</td>
                    <td style="text-align:right;color:var(--red);font-weight:600;">{{ number_format($deduction['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total Deductions</td>
                    <td style="text-align:right;color:var(--red);">{{ number_format($payroll->total_deductions, 2) }}</td>
                </tr>
            </table>

            <div class="net-box">
                <div>
                    <div class="net-label">Net Pay</div>
                    <div class="net-amount">₦{{ number_format($payroll->net_pay, 2) }}</div>
                    <div class="net-words">{{ $netPayInWords ?? '' }}</div>
                </div>
                <div class="text-end">
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.55);">Date Generated</div>
                    <div style="font-size:0.88rem;font-weight:700;color:white;">{{ now()->format('d M Y') }}</div>
                    @if($payroll->paid_at)
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.55);margin-top:8px;">Date Paid</div>
                    <div style="font-size:0.88rem;font-weight:700;color:var(--gold-bright);">{{ \Carbon\Carbon::parse($payroll->paid_at)->format('d M Y') }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="slip-footer">
            @php
                $payslipBrand = auth()->user()?->company?->company_name
                    ?? auth()->user()?->company?->name
                    ?? \App\Models\Setting::where('key', 'company_name')->value('value')
                    ?? 'SmartProbook';
            @endphp
            This payslip is computer generated and does not require a signature. · {{ $payslipBrand }} · {{ now()->year }}
        </div>
    </div>
</div>
@endsection
