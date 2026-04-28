@extends('layout.app')
@section('title', isset($employee) ? 'Edit Employee Payroll' : 'Add Employee')

@section('content')
<style>
:root { --blue-deep:#002347; --gold:#c5a059; --gold-bright:#ffdf91; --red:#bc002d; }
.payroll-shell { width:100%; max-width:1560px; margin:0 auto; padding:1.5rem 0.75rem; min-width:0; overflow-x:hidden; }
.payroll-shell .row { margin-left:0; margin-right:0; }
.payroll-shell .row > * { padding-left:calc(var(--bs-gutter-x, 1.5rem) * 0.5); padding-right:calc(var(--bs-gutter-x, 1.5rem) * 0.5); }
.page-header { background:linear-gradient(135deg,var(--blue-deep),#003d6b); border-radius:16px; padding:28px 32px; color:white; margin-bottom:28px; }
.page-header h1 { font-size:1.5rem; font-weight:800; margin:0; color:#ffffff; }
.page-header p { color:rgba(255,255,255,0.7); margin:6px 0 0; font-size:0.88rem; }
.form-card { background:#fff; border:1px solid #e8ecf4; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,35,71,0.05); margin-bottom:24px; }
.form-card-header { padding:16px 24px; border-bottom:1px solid #e8ecf4; background:#f8faff; display:flex; align-items:center; gap:10px; }
.form-card-header h6 { font-weight:800; color:var(--blue-deep); margin:0; font-size:0.9rem; }
.form-card-body { padding:24px; }
.form-label { font-weight:700; font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; color:#6b7280; margin-bottom:6px; display:block; }
.form-control, .form-select { border:1.5px solid #e8ecf4; border-radius:8px; padding:11px 14px; font-size:0.88rem; color:var(--blue-deep); width:100%; transition:all 0.2s; }
.form-control:focus, .form-select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(197,160,89,0.15); outline:none; }
.input-prefix { display:flex; }
.input-prefix .prefix { background:#f8faff; border:1.5px solid #e8ecf4; border-right:none; border-radius:8px 0 0 8px; padding:11px 14px; font-weight:800; font-size:0.82rem; color:#8a92a0; white-space:nowrap; }
.input-prefix .form-control { border-radius:0 8px 8px 0; }
.section-divider { border:none; border-top:1px solid #e8ecf4; margin:8px 0 20px; }
.allowance-row, .deduction-row { background:#f8faff; border:1px solid #e8ecf4; border-radius:10px; padding:14px 16px; margin-bottom:10px; position:relative; }
.remove-btn { position:absolute; top:10px; right:10px; background:none; border:none; color:var(--red); font-size:0.85rem; cursor:pointer; padding:4px 8px; border-radius:6px; transition:all 0.2s; }
.remove-btn:hover { background:#fee2e2; }
.btn-gold { background:linear-gradient(135deg,var(--gold),var(--gold-bright)); color:var(--blue-deep)!important; border:none; padding:12px 28px; font-weight:800; border-radius:8px; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
.btn-gold:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(197,160,89,0.4); }
.btn-outline { background:transparent; color:var(--blue-deep)!important; border:1.5px solid #e8ecf4; padding:12px 28px; font-weight:700; border-radius:8px; font-size:0.85rem; transition:all 0.3s; cursor:pointer; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
.btn-outline:hover { border-color:var(--gold); color:var(--gold)!important; }
.btn-add { background:#f0f4ff; color:var(--blue-deep); border:1.5px dashed #c5d3e8; padding:8px 18px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:6px; }
.btn-add:hover { background:#e8f0ff; border-color:var(--gold); color:var(--gold); }
.net-preview { background:linear-gradient(135deg,var(--blue-deep),#003d6b); border-radius:12px; padding:20px 24px; color:white; }
.net-preview .label { font-size:0.72rem; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,0.6); margin-bottom:4px; }
.net-preview .amount { font-size:1.8rem; font-weight:900; color:var(--gold-bright); }
.calc-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:0.83rem; }
.calc-row:last-child { border-bottom:none; font-weight:900; font-size:0.95rem; }
@media(max-width:768px){
    .page-header h1{font-size:1.2rem;}
    .btn-gold, .btn-outline, .btn-add { width:100%; justify-content:center; }
    .form-card-body { padding:16px; }
}
@media(min-width:768px){ .payroll-shell{ padding-left:1rem; padding-right:1rem; } }
</style>

<div class="page-wrapper">
<div class="payroll-shell">

    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('payroll.index') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ isset($employee) ? 'Edit Employee Payroll' : 'Add New Employee' }}</h1>
                <p>{{ isset($employee) ? 'Update salary structure for ' . $employee->name : 'Set up payroll for a new team member' }}</p>
            </div>
        </div>
    </div>

    <form action="{{ isset($employee) ? route('payroll.update', $employee->id) : route('payroll.store') }}" method="POST" id="payrollForm">
        @csrf
        @if(isset($employee)) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">

                <div class="form-card">
                    <div class="form-card-header">
                        <i class="fas fa-user" style="color:var(--gold);"></i>
                        <h6>Employee Information</h6>
                    </div>
                    <div class="form-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Adaobi Nwosu" value="{{ old('name', $employee->name ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Employee ID</label>
                                <input type="text" name="employee_id" class="form-control" placeholder="e.g. EMP-001" value="{{ old('employee_id', $employee->employee_id ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department *</label>
                                <select name="department" class="form-select" required>
                                    <option value="">Select Department</option>
                                    @foreach(['Management','Sales','IT','Finance','Operations','HR','Marketing','Logistics'] as $dept)
                                    <option value="{{ $dept }}" {{ old('department', $employee->department ?? '') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Job Title *</label>
                                <input type="text" name="job_title" class="form-control" placeholder="e.g. Senior Accountant" value="{{ old('job_title', $employee->job_title ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="staff@company.com" value="{{ old('email', $employee->email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="+234 080 0000 0000" value="{{ old('phone', $employee->phone ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Employment</label>
                                <input type="date" name="employment_date" class="form-control" value="{{ old('employment_date', $employee->employment_date ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <select name="bank_name" class="form-select">
                                    <option value="">Select Bank</option>
                                    @foreach(['Access Bank','GTBank','First Bank','Zenith Bank','UBA','Fidelity Bank','Sterling Bank','Polaris Bank','Stanbic IBTC','Wema Bank'] as $bank)
                                    <option value="{{ $bank }}" {{ old('bank_name', $employee->bank_name ?? '') == $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="account_number" class="form-control" placeholder="0123456789" maxlength="10" value="{{ old('account_number', $employee->account_number ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax ID (TIN)</label>
                                <input type="text" name="tax_id" class="form-control" placeholder="e.g. 1234567-0001" value="{{ old('tax_id', $employee->tax_id ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header">
                        <i class="fas fa-money-bill-wave" style="color:var(--gold);"></i>
                        <h6>Salary Structure</h6>
                    </div>
                    <div class="form-card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Basic Salary (Monthly) *</label>
                                <div class="input-prefix">
                                    <span class="prefix">₦</span>
                                    <input type="number" name="basic_salary" id="basicSalary" class="form-control" placeholder="0.00" min="0" step="0.01" value="{{ old('basic_salary', $employee->basic_salary ?? '') }}" required oninput="recalculate()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pay Frequency</label>
                                <select name="pay_frequency" class="form-select">
                                    <option value="monthly" {{ old('pay_frequency','monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="weekly" {{ old('pay_frequency') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="biweekly" {{ old('pay_frequency') == 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
                                </select>
                            </div>
                        </div>

                        <hr class="section-divider">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <label class="form-label mb-0" style="color:var(--blue-deep);font-size:0.82rem;">➕ Allowances</label>
                            <button type="button" class="btn-add" onclick="addAllowance()">
                                <i class="fas fa-plus"></i> Add Allowance
                            </button>
                        </div>
                        <div id="allowancesContainer">
                            @php $allowances = old('allowances', $employee->allowances ?? [['name'=>'Housing Allowance','amount'=>'']]); @endphp
                            @foreach($allowances as $i => $a)
                            <div class="allowance-row" id="allowance_{{ $i }}">
                                <button type="button" class="remove-btn" onclick="removeRow('allowance_{{ $i }}')"><i class="fas fa-times"></i></button>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input type="text" name="allowances[{{ $i }}][name]" class="form-control" placeholder="Allowance name" value="{{ $a['name'] ?? '' }}">
                                    </div>
                                    <div class="col-5">
                                        <div class="input-prefix">
                                            <span class="prefix">₦</span>
                                            <input type="number" name="allowances[{{ $i }}][amount]" class="form-control allowance-amount" placeholder="0.00" min="0" step="0.01" value="{{ $a['amount'] ?? '' }}" oninput="recalculate()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <hr class="section-divider">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <label class="form-label mb-0" style="color:var(--red);font-size:0.82rem;">➖ Deductions</label>
                            <button type="button" class="btn-add" onclick="addDeduction()">
                                <i class="fas fa-plus"></i> Add Deduction
                            </button>
                        </div>
                        <div id="deductionsContainer">
                            @php $deductions = old('deductions', $employee->deductions ?? [['name'=>'PAYE Tax','amount'=>''],['name'=>'Pension (8%)','amount'=>'']]); @endphp
                            @foreach($deductions as $i => $d)
                            <div class="deduction-row" id="deduction_{{ $i }}">
                                <button type="button" class="remove-btn" onclick="removeRow('deduction_{{ $i }}')"><i class="fas fa-times"></i></button>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input type="text" name="deductions[{{ $i }}][name]" class="form-control" placeholder="Deduction name" value="{{ $d['name'] ?? '' }}">
                                    </div>
                                    <div class="col-5">
                                        <div class="input-prefix">
                                            <span class="prefix">₦</span>
                                            <input type="number" name="deductions[{{ $i }}][amount]" class="form-control deduction-amount" placeholder="0.00" min="0" step="0.01" value="{{ $d['amount'] ?? '' }}" oninput="recalculate()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3 flex-wrap">
                    <button type="submit" class="btn-gold">
                        <i class="fas fa-save"></i> {{ isset($employee) ? 'Update Employee' : 'Save Employee' }}
                    </button>
                    <a href="{{ route('payroll.index') }}" class="btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="net-preview mb-4">
                    <div class="label">Net Pay Preview</div>
                    <div class="amount" id="netPayDisplay">₦0.00</div>
                    <div style="margin-top:16px;">
                        <div class="calc-row">
                            <span style="color:rgba(255,255,255,0.65);">Basic Salary</span>
                            <span id="calcBasic">₦0</span>
                        </div>
                        <div class="calc-row">
                            <span style="color:#4ade80;">+ Allowances</span>
                            <span id="calcAllowances" style="color:#4ade80;">₦0</span>
                        </div>
                        <div class="calc-row">
                            <span style="color:#f87171;">− Deductions</span>
                            <span id="calcDeductions" style="color:#f87171;">₦0</span>
                        </div>
                        <div class="calc-row" style="border-top:1px solid rgba(255,255,255,0.2);margin-top:8px;padding-top:8px;">
                            <span>Net Pay</span>
                            <span id="calcNet" style="color:var(--gold-bright);">₦0</span>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header">
                        <i class="fas fa-info-circle" style="color:var(--gold);"></i>
                        <h6>Statutory Rates (Nigeria)</h6>
                    </div>
                    <div class="form-card-body p-3">
                        <div style="font-size:0.78rem;color:#6b7280;line-height:1.9;">
                            <div class="d-flex justify-content-between py-1 border-bottom" style="border-color:#f0f4f8!important;">
                                <span>Pension (Employee)</span><strong style="color:var(--blue-deep);">8%</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom" style="border-color:#f0f4f8!important;">
                                <span>Pension (Employer)</span><strong style="color:var(--blue-deep);">10%</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom" style="border-color:#f0f4f8!important;">
                                <span>NHF</span><strong style="color:var(--blue-deep);">2.5%</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>PAYE</span><strong style="color:var(--blue-deep);">Progressive</strong>
                            </div>
                        </div>
                        <button type="button" class="btn-add w-100 justify-content-center mt-3" onclick="autoCalcStatutory()">
                            <i class="fas fa-magic"></i> Auto-Calculate Statutory
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let allowanceCount = {{ count($allowances ?? [['name'=>'','amount'=>'']]) }};
let deductionCount = {{ count($deductions ?? [['name'=>'','amount'=>'']]) }};

function addAllowance() {
    const container = document.getElementById('allowancesContainer');
    const div = document.createElement('div');
    div.className = 'allowance-row';
    div.id = 'allowance_' + allowanceCount;
    div.innerHTML = `
        <button type="button" class="remove-btn" onclick="removeRow('allowance_${allowanceCount}')"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-7">
                <input type="text" name="allowances[${allowanceCount}][name]" class="form-control" placeholder="Allowance name">
            </div>
            <div class="col-5">
                <div class="input-prefix">
                    <span class="prefix">₦</span>
                    <input type="number" name="allowances[${allowanceCount}][amount]" class="form-control allowance-amount" placeholder="0.00" min="0" step="0.01" oninput="recalculate()">
                </div>
            </div>
        </div>`;
    container.appendChild(div);
    allowanceCount++;
}

function addDeduction() {
    const container = document.getElementById('deductionsContainer');
    const div = document.createElement('div');
    div.className = 'deduction-row';
    div.id = 'deduction_' + deductionCount;
    div.innerHTML = `
        <button type="button" class="remove-btn" onclick="removeRow('deduction_${deductionCount}')"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-7">
                <input type="text" name="deductions[${deductionCount}][name]" class="form-control" placeholder="Deduction name">
            </div>
            <div class="col-5">
                <div class="input-prefix">
                    <span class="prefix">₦</span>
                    <input type="number" name="deductions[${deductionCount}][amount]" class="form-control deduction-amount" placeholder="0.00" min="0" step="0.01" oninput="recalculate()">
                </div>
            </div>
        </div>`;
    container.appendChild(div);
    deductionCount++;
}

function removeRow(id) {
    const el = document.getElementById(id);
    if (el) { el.remove(); recalculate(); }
}

function fmt(n) {
    return '₦' + Number(n).toLocaleString('en-NG', {minimumFractionDigits:0, maximumFractionDigits:0});
}

function recalculate() {
    const basic = parseFloat(document.getElementById('basicSalary').value) || 0;
    let totalAllowances = 0, totalDeductions = 0;
    document.querySelectorAll('.allowance-amount').forEach(el => totalAllowances += parseFloat(el.value) || 0);
    document.querySelectorAll('.deduction-amount').forEach(el => totalDeductions += parseFloat(el.value) || 0);
    const net = basic + totalAllowances - totalDeductions;
    document.getElementById('calcBasic').textContent = fmt(basic);
    document.getElementById('calcAllowances').textContent = fmt(totalAllowances);
    document.getElementById('calcDeductions').textContent = fmt(totalDeductions);
    document.getElementById('calcNet').textContent = fmt(net);
    document.getElementById('netPayDisplay').textContent = fmt(net);
}

function autoCalcStatutory() {
    const basic = parseFloat(document.getElementById('basicSalary').value) || 0;
    if (!basic) { alert('Please enter basic salary first.'); return; }
    const pension = basic * 0.08;
    const nhf = basic * 0.025;
    // Simple PAYE estimate
    let annualIncome = basic * 12;
    let paye = 0;
    if (annualIncome > 3200000) paye = (annualIncome - 3200000) * 0.24 + 508000;
    else if (annualIncome > 1600000) paye = (annualIncome - 1600000) * 0.18 + 220000;
    else if (annualIncome > 800000) paye = (annualIncome - 800000) * 0.15 + 100000;
    else if (annualIncome > 400000) paye = (annualIncome - 400000) * 0.11 + 56000;
    else if (annualIncome > 200000) paye = (annualIncome - 200000) * 0.07 + 14000;
    else paye = annualIncome * 0.07;
    const monthlyPaye = paye / 12;

    // Add to deductions
    const container = document.getElementById('deductionsContainer');
    container.innerHTML = '';
    deductionCount = 0;
    const statutoryDeductions = [
        { name: 'PAYE Tax', amount: monthlyPaye.toFixed(0) },
        { name: 'Pension (Employee 8%)', amount: pension.toFixed(0) },
        { name: 'NHF (2.5%)', amount: nhf.toFixed(0) },
    ];
    statutoryDeductions.forEach(d => {
        const div = document.createElement('div');
        div.className = 'deduction-row';
        div.id = 'deduction_' + deductionCount;
        div.innerHTML = `
            <button type="button" class="remove-btn" onclick="removeRow('deduction_${deductionCount}')"><i class="fas fa-times"></i></button>
            <div class="row g-2">
                <div class="col-7">
                    <input type="text" name="deductions[${deductionCount}][name]" class="form-control" value="${d.name}">
                </div>
                <div class="col-5">
                    <div class="input-prefix">
                        <span class="prefix">₦</span>
                        <input type="number" name="deductions[${deductionCount}][amount]" class="form-control deduction-amount" value="${d.amount}" oninput="recalculate()">
                    </div>
                </div>
            </div>`;
        container.appendChild(div);
        deductionCount++;
    });
    recalculate();
}

document.addEventListener('DOMContentLoaded', recalculate);
</script>
</div>
@endsection
