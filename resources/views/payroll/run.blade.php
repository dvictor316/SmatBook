@extends('layout.app')
@section('title', 'Run Payroll')

@section('content')
<style>
:root { --blue-deep:#002347; --gold:#c5a059; --gold-bright:#ffdf91; --red:#bc002d; }
.payroll-shell { width:100%; max-width:100%; padding:1.5rem 0.75rem; overflow-x:hidden; }
.payroll-shell .row { margin-left:0; margin-right:0; }
.payroll-shell .row > * { padding-left:calc(var(--bs-gutter-x, 1.5rem) * 0.5); padding-right:calc(var(--bs-gutter-x, 1.5rem) * 0.5); }
.page-header { background:linear-gradient(135deg,var(--blue-deep),#003d6b); border-radius:16px; padding:28px 32px; color:white; margin-bottom:28px; }
.page-header h1 { font-size:1.5rem; font-weight:800; margin:0; color:#ffffff; }
.run-card { background:#fff; border:1px solid #e8ecf4; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,35,71,0.05); margin-bottom:24px; }
.run-card-header { padding:16px 24px; border-bottom:1px solid #e8ecf4; background:#f8faff; display:flex; align-items:center; gap:10px; }
.run-card-header h6 { font-weight:800; color:var(--blue-deep); margin:0; font-size:0.9rem; }
.run-card-body { padding:24px; }
.form-label { font-weight:700; font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; color:#6b7280; margin-bottom:6px; display:block; }
.form-control, .form-select { border:1.5px solid #e8ecf4; border-radius:8px; padding:11px 14px; font-size:0.88rem; color:var(--blue-deep); width:100%; transition:all 0.2s; }
.form-control:focus, .form-select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(197,160,89,0.15); outline:none; }
.staff-check-item {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 16px; border:1.5px solid #e8ecf4; border-radius:10px;
    margin-bottom:8px; transition:all 0.2s; cursor:pointer;
}
.staff-check-item:hover { border-color:var(--gold); background:#fffbf0; }
.staff-check-item.selected { border-color:var(--gold); background:#fffbf0; }
.staff-check-item.disabled { opacity:0.55; cursor:not-allowed; background:#f8faff; }
.staff-check-item.disabled:hover { border-color:#e8ecf4; background:#f8faff; }
.staff-avatar { width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,var(--blue-deep),#004080); color:white; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.78rem; flex-shrink:0; }
.check-custom { width:20px; height:20px; border:2px solid #e8ecf4; border-radius:5px; display:flex; align-items:center; justify-content:center; transition:all 0.2s; flex-shrink:0; }
.staff-check-item.selected .check-custom { background:var(--gold); border-color:var(--gold); color:white; }
.summary-box { background:linear-gradient(135deg,var(--blue-deep),#003d6b); border-radius:12px; padding:24px; color:white; position:sticky; top:20px; }
.summary-box h6 { color:var(--gold); font-weight:800; font-size:0.75rem; text-transform:uppercase; letter-spacing:2px; margin-bottom:16px; }
.summary-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:0.85rem; }
.summary-row:last-child { border-bottom:none; font-weight:900; font-size:1rem; }
.btn-gold { background:linear-gradient(135deg,var(--gold),var(--gold-bright)); color:var(--blue-deep)!important; border:none; padding:14px 28px; font-weight:800; border-radius:8px; font-size:0.88rem; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s; cursor:pointer; display:inline-flex; align-items:center; gap:8px; width:100%; justify-content:center; }
.btn-gold:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(197,160,89,0.4); }
.btn-outline { background:transparent; color:var(--blue-deep)!important; border:1.5px solid #e8ecf4; padding:12px 24px; font-weight:700; border-radius:8px; font-size:0.85rem; transition:all 0.3s; cursor:pointer; display:inline-flex; align-items:center; gap:7px; text-decoration:none; }
.btn-outline:hover { border-color:var(--gold); color:var(--gold)!important; }
.warning-box { background:#fff8e1; border:1px solid #f59e0b; border-radius:10px; padding:14px 18px; margin-bottom:20px; font-size:0.83rem; color:#854d0e; }
.step-indicator { display:flex; align-items:center; gap:0; margin-bottom:28px; }
.step { display:flex; align-items:center; gap:8px; flex:1; }
.step-num { width:32px; height:32px; border-radius:50%; background:#f0f4f8; color:#8a92a0; font-weight:800; font-size:0.8rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.step.active .step-num { background:var(--blue-deep); color:white; }
.step.done .step-num { background:#22c55e; color:white; }
.step-label { font-size:0.75rem; font-weight:700; color:#8a92a0; }
.step.active .step-label { color:var(--blue-deep); }
.step-line { flex:1; height:2px; background:#e8ecf4; margin:0 8px; }
.step.done .step-line { background:#22c55e; }
@media(max-width:768px){ .page-header h1{font-size:1.2rem;} }
@media(min-width:768px){ .payroll-shell{ padding-left:1rem; padding-right:1rem; } }
</style>

<div class="payroll-shell">

    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('payroll.index') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1><i class="fas fa-play-circle me-2" style="color:var(--gold-bright);"></i>Run Payroll</h1>
                <p style="color:rgba(255,255,255,0.7);margin:6px 0 0;font-size:0.88rem;">Process salary payments for your team</p>
            </div>
        </div>
    </div>

    {{-- Step Indicator --}}
    <div class="step-indicator no-print">
        <div class="step done"><div class="step-num"><i class="fas fa-check" style="font-size:0.7rem;"></i></div><div class="step-label">Setup</div></div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-num">2</div><div class="step-label">Select Staff</div></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">3</div><div class="step-label">Review</div></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">4</div><div class="step-label">Process</div></div>
    </div>

    <form action="{{ route('payroll.process') }}" method="POST" id="runPayrollForm">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">

                {{-- Pay Period --}}
                <div class="run-card">
                    <div class="run-card-header">
                        <i class="fas fa-calendar" style="color:var(--gold);"></i>
                        <h6>Pay Period & Settings</h6>
                    </div>
                    <div class="run-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Pay Period *</label>
                                <input type="month" name="pay_period" id="payPeriodInput" class="form-control" value="{{ now()->format('Y-m') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pay Date *</label>
                                <input type="date" name="pay_date" class="form-control" value="{{ now()->endOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. February 2026 payroll run including bonuses..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Warning --}}
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Please review all salary figures carefully before processing. Payroll runs cannot be easily reversed once processed.
                </div>

                {{-- Staff Selection --}}
                <div class="run-card">
                    <div class="run-card-header">
                        <i class="fas fa-users" style="color:var(--gold);"></i>
                        <h6>Select Staff to Pay</h6>
                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn-outline" style="padding:5px 12px;font-size:0.75rem;" onclick="selectAll()">Select All</button>
                            <button type="button" class="btn-outline" style="padding:5px 12px;font-size:0.75rem;" onclick="deselectAll()">Deselect All</button>
                        </div>
                    </div>
                    <div class="run-card-body">
                        <div class="warning-box" id="lockedNotice" style="background:#eef2ff;border-color:#6366f1;color:#4338ca;display:{{ !empty($existingPayrollEmployeeIds) ? 'block' : 'none' }};">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="lockedCount">{{ count($existingPayrollEmployeeIds ?? []) }}</span> employee(s) already have payroll for <span id="lockedPeriod">{{ now()->format('F Y') }}</span> and are locked.
                        </div>
                        <input type="text" class="form-control mb-3" placeholder="🔍 Search staff..." id="staffSearch" oninput="filterStaff()">
                        <div id="staffList">
                            @forelse($employees ?? [] as $emp)
                            @php $locked = in_array($emp->id, $existingPayrollEmployeeIds ?? []); @endphp
                            <label class="staff-check-item {{ $locked ? 'disabled' : '' }}" id="staff_item_{{ $emp->id }}" onclick="toggleStaff({{ $emp->id }}, {{ $emp->net_pay ?? 0 }})">
                                <div class="d-flex align-items-center gap-3" style="flex:1;">
                                    <div class="staff-avatar">{{ strtoupper(substr($emp->name, 0, 2)) }}</div>
                                    <div style="flex:1;">
                                        <div style="font-weight:700;color:var(--blue-deep);font-size:0.88rem;">{{ $emp->name }}</div>
                                        <div style="font-size:0.72rem;color:#8a92a0;">{{ $emp->job_title }} · {{ $emp->department }}</div>
                                        <div id="lock_msg_{{ $emp->id }}" style="font-size:0.7rem;color:#4338ca;font-weight:700;margin-top:2px;display:{{ $locked ? 'block' : 'none' }};">Already processed for {{ now()->format('F Y') }}</div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:800;color:var(--blue-deep);font-size:0.9rem;">₦{{ number_format($emp->net_pay ?? 0) }}</div>
                                        <div style="font-size:0.7rem;color:#8a92a0;">Net Pay</div>
                                    </div>
                                </div>
                                <div class="check-custom ms-3" id="check_{{ $emp->id }}">
                                    <i class="fas fa-check" style="font-size:0.65rem;"></i>
                                </div>
                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" id="chk_{{ $emp->id }}" style="display:none;" {{ $locked ? 'disabled' : 'checked' }}>
                            </label>
                            @empty
                            <div class="text-center py-5" style="color:#8a92a0;">
                                <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:10px;opacity:0.3;"></i>
                                No employees found. <a href="{{ route('payroll.create') }}" style="color:var(--gold);">Add employees first.</a>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar Summary --}}
            <div class="col-lg-4">
                <div class="summary-box">
                    <h6>📊 Payroll Summary</h6>
                    <div class="summary-row">
                        <span style="color:rgba(255,255,255,0.65);">Staff Selected</span>
                        <span id="summaryCount">{{ max(count($employees ?? []) - count($existingPayrollEmployeeIds ?? []), 0) }}</span>
                    </div>
                    <div class="summary-row">
                        <span style="color:rgba(255,255,255,0.65);">Total Gross</span>
                        <span id="summaryGross">₦{{ number_format($employees->sum('gross_pay') ?? 0) }}</span>
                    </div>
                    <div class="summary-row">
                        <span style="color:#f87171;">Total Deductions</span>
                        <span id="summaryDeductions" style="color:#f87171;">₦{{ number_format($employees->sum('total_deductions') ?? 0) }}</span>
                    </div>
                    <div class="summary-row" style="border-top:1px solid rgba(255,255,255,0.2);margin-top:8px;padding-top:12px;">
                        <span>Total Net Pay</span>
                        <span id="summaryNet" style="color:var(--gold-bright);">₦{{ number_format($employees->sum('net_pay') ?? 0) }}</span>
                    </div>

                    <div style="margin-top:24px;">
                        <button type="submit" class="btn-gold">
                            <i class="fas fa-paper-plane"></i> Process Payroll
                        </button>
                        <a href="{{ route('payroll.index') }}" class="btn-outline mt-3 w-100 justify-content-center">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                    <div style="margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.1);font-size:0.72rem;color:rgba(255,255,255,0.45);text-align:center;">
                        <i class="fas fa-lock me-1"></i> Secured & encrypted payroll processing
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const employees = @json($employees ?? []);
const lockedUrl = "{{ route('payroll.run.locked') }}";
let existingPayrollIds = new Set(@json($existingPayrollEmployeeIds ?? []));
let selectedIds = new Set(employees.filter(e => !existingPayrollIds.has(e.id)).map(e => e.id));
let netPays = {};
let grossPays = {};
let deductionTotals = {};
employees.forEach(e => {
    netPays[e.id] = parseFloat(e.net_pay || 0);
    grossPays[e.id] = parseFloat(e.gross_pay || 0);
    deductionTotals[e.id] = parseFloat(e.total_deductions || 0);
});

function toggleStaff(id, net) {
    if (existingPayrollIds.has(id)) return;
    const item = document.getElementById('staff_item_' + id);
    const chk = document.getElementById('chk_' + id);
    if (selectedIds.has(id)) {
        selectedIds.delete(id);
        item.classList.remove('selected');
        chk.checked = false;
    } else {
        selectedIds.add(id);
        item.classList.add('selected');
        chk.checked = true;
    }
    updateSummary();
}

function selectAll() {
    employees.forEach(e => {
        if (existingPayrollIds.has(e.id)) return;
        selectedIds.add(e.id);
        document.getElementById('staff_item_' + e.id).classList.add('selected');
        document.getElementById('chk_' + e.id).checked = true;
    });
    updateSummary();
}

function deselectAll() {
    employees.forEach(e => {
        if (existingPayrollIds.has(e.id)) return;
        selectedIds.delete(e.id);
        document.getElementById('staff_item_' + e.id).classList.remove('selected');
        document.getElementById('chk_' + e.id).checked = false;
    });
    updateSummary();
}

function updateSummary() {
    let totalNet = 0;
    let totalGross = 0;
    let totalDeductions = 0;
    selectedIds.forEach(id => {
        totalNet += (netPays[id] || 0);
        totalGross += (grossPays[id] || 0);
        totalDeductions += (deductionTotals[id] || 0);
    });
    document.getElementById('summaryCount').textContent = selectedIds.size;
    document.getElementById('summaryGross').textContent = '₦' + totalGross.toLocaleString('en-NG', {maximumFractionDigits:0});
    document.getElementById('summaryDeductions').textContent = '₦' + totalDeductions.toLocaleString('en-NG', {maximumFractionDigits:0});
    document.getElementById('summaryNet').textContent = '₦' + totalNet.toLocaleString('en-NG', {maximumFractionDigits:0});
}

function filterStaff() {
    const q = document.getElementById('staffSearch').value.toLowerCase();
    document.querySelectorAll('.staff-check-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Initialize selected state
document.addEventListener('DOMContentLoaded', function() {
    applyLocks(Array.from(existingPayrollIds), "{{ now()->format('F Y') }}");

    const periodInput = document.getElementById('payPeriodInput');
    if (periodInput) {
        periodInput.addEventListener('change', refreshLocked);
    }

    refreshLocked();
});

function applyLocks(ids, periodLabel) {
    existingPayrollIds = new Set(ids || []);
    selectedIds = new Set();

    const notice = document.getElementById('lockedNotice');
    const countEl = document.getElementById('lockedCount');
    const periodEl = document.getElementById('lockedPeriod');
    if (notice && countEl && periodEl) {
        const count = existingPayrollIds.size;
        if (count > 0) {
            countEl.textContent = count;
            periodEl.textContent = periodLabel;
            notice.style.display = 'block';
        } else {
            notice.style.display = 'none';
        }
    }

    employees.forEach(e => {
        const locked = existingPayrollIds.has(e.id);
        const item = document.getElementById('staff_item_' + e.id);
        const chk = document.getElementById('chk_' + e.id);
        const lockMsg = document.getElementById('lock_msg_' + e.id);
        if (!item || !chk) return;

        if (locked) {
            item.classList.add('disabled');
            item.classList.remove('selected');
            chk.checked = false;
            chk.disabled = true;
            if (lockMsg) {
                lockMsg.textContent = 'Already processed for ' + periodLabel;
                lockMsg.style.display = 'block';
            }
        } else {
            item.classList.remove('disabled');
            chk.disabled = false;
            chk.checked = true;
            item.classList.add('selected');
            selectedIds.add(e.id);
            if (lockMsg) {
                lockMsg.style.display = 'none';
            }
        }
    });

    updateSummary();
}

function refreshLocked() {
    const periodInput = document.getElementById('payPeriodInput');
    if (!periodInput) return;
    const month = periodInput.value || '';
    const url = lockedUrl + '?month=' + encodeURIComponent(month);

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(resp => resp.json())
        .then(data => {
            const ids = data.employee_ids || [];
            const label = data.period || month;
            applyLocks(ids, label);
        })
        .catch(() => {
            // Leave current lock state as-is on error.
        });
}
</script>
@endsection
