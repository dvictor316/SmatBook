<?php $page = 'recurring-invoice-create'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    .ri-wizard-shell { border: 1px solid #e5edf7; border-radius: 14px; background: #fff; box-shadow: 0 14px 32px rgba(15, 23, 42, .06); overflow: hidden; }
    .ri-wizard-tabs { border-bottom: 1px solid #edf2f7; background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%); padding: 12px 14px 0; }
    .ri-wizard-tabs .nav-link { border: 0; border-bottom: 3px solid transparent; color: #64748b; font-weight: 700; font-size: 13px; }
    .ri-wizard-tabs .nav-link.active { color: #1d4ed8; border-bottom-color: #2563eb; background: transparent; }
    .ri-step-panel { padding: 18px; }
    .automation-card { min-height: 154px; transition: border-color .15s, box-shadow .15s, transform .15s; }
    .automation-card:hover { border-color: #93c5fd; box-shadow: 0 10px 24px rgba(37, 99, 235, .08); transform: translateY(-1px); }
</style>
<div class="page-wrapper">
<div class="content container-fluid">

    @component('components.page-header')
        @slot('title') New Recurring Invoice Template @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sales.recurring-invoices.store') }}" id="recurringForm">
        @csrf

        {{-- ── Wizard nav ─────────────────────────────────────────── --}}
        <div class="ri-wizard-shell">
        <ul class="nav nav-tabs nav-tabs-solid ri-wizard-tabs mb-0" id="wizardTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#step1">1. Invoice Details</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#step2">2. Recurrence</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#step3">3. Reminders</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#step4">4. Automation</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#step5">5. Review</a></li>
        </ul>

        <div class="tab-content ri-step-panel">

            {{-- ────────────────────────────────────────────────────── --}}
            {{-- STEP 1 – Invoice Details                              --}}
            {{-- ────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade show active" id="step1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header"><strong>Template Info</strong></div>
                            <div class="card-body row g-3">
                                <div class="col-12">
                                    <label class="form-label">Template Name <span class="text-danger">*</span></label>
                                    <input type="text" name="template_name" class="form-control" value="{{ old('template_name') }}" required placeholder="e.g. Monthly Retainer – Acme Corp">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Customer</label>
                                    <select name="customer_id" class="form-select" id="customerSelect">
                                        <option value="">Walk-in / No customer</option>
                                        @foreach($customers as $c)
                                            <option value="{{ $c->id }}"
                                                data-currency="{{ $c->currency ?? 'NGN' }}"
                                                {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->customer_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Currency</label>
                                    <input type="text" name="currency" id="currencyInput" class="form-control" value="{{ old('currency', 'NGN') }}" maxlength="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Terms</label>
                                    <select name="terms" class="form-select">
                                        <option value="">None</option>
                                        @foreach(['Net 15','Net 30','Net 45','Net 60','Due on Receipt'] as $t)
                                            <option value="{{ $t }}" {{ old('terms') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Due Days After Invoice Date</label>
                                    <input type="number" name="due_days" class="form-control" value="{{ old('due_days', 30) }}" min="0" max="365">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Timezone</label>
                                    <select name="timezone" class="form-select">
                                        @foreach(['Africa/Lagos','UTC','Europe/London','America/New_York','America/Toronto','Africa/Accra','Africa/Nairobi','Africa/Johannesburg','Asia/Dubai'] as $tz)
                                            <option value="{{ $tz }}" @selected(old('timezone', config('app.timezone', 'Africa/Lagos')) === $tz)>{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Payment Instructions</label>
                                    <textarea name="payment_instructions" class="form-control" rows="2">{{ old('payment_instructions') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes (visible on invoice)</label>
                                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Internal Memo</label>
                                    <textarea name="internal_memo" class="form-control" rows="2" placeholder="Not shown to customer">{{ old('internal_memo') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong>Invoice Items</strong>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                                    <i class="fe fe-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" id="itemsTable">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="min-width:180px">Description</th>
                                                <th style="width:80px">Qty</th>
                                                <th style="width:110px">Unit Price</th>
                                                <th style="width:90px">Tax</th>
                                                <th style="width:90px">Discount</th>
                                                <th style="width:100px">Total</th>
                                                <th style="width:40px"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            {{-- rows injected by JS --}}
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-end fw-semibold">Subtotal:</td>
                                                <td id="sumSubtotal">0.00</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end fw-semibold">Tax:</td>
                                                <td id="sumTax">0.00</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end fw-semibold">Discount:</td>
                                                <td id="sumDiscount">0.00</td>
                                                <td></td>
                                            </tr>
                                            <tr class="table-light">
                                                <td colspan="5" class="text-end fw-bold">Total:</td>
                                                <td id="sumTotal" class="fw-bold">0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        @if($prefillSale)
                        <div class="alert alert-info mt-3">
                            <i class="fe fe-info me-1"></i>
                            Pre-filled from Invoice {{ $prefillSale->invoice_no }}.
                            Items loaded below.
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-primary next-tab" data-target="step2">Next: Recurrence →</button>
                </div>
            </div>

            {{-- ────────────────────────────────────────────────────── --}}
            {{-- STEP 2 – Recurrence                                   --}}
            {{-- ────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="step2">
                <div class="card border-0 shadow-sm">
                    <div class="card-header"><strong>Recurrence Schedule</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Frequency <span class="text-danger">*</span></label>
                            <select name="frequency" class="form-select" id="frequencySelect" required>
                                <option value="daily"      {{ old('frequency')=='daily'      ? 'selected' : '' }}>Daily</option>
                                <option value="weekly"     {{ old('frequency')=='weekly'     ? 'selected' : '' }}>Weekly</option>
                                <option value="biweekly"   {{ old('frequency')=='biweekly'   ? 'selected' : '' }}>Every 2 Weeks</option>
                                <option value="monthly"    {{ old('frequency','monthly')=='monthly'   ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly"  {{ old('frequency')=='quarterly'  ? 'selected' : '' }}>Quarterly</option>
                                <option value="semi_annual"{{ old('frequency')=='semi_annual'? 'selected' : '' }}>Semi-Annual</option>
                                <option value="annual"     {{ old('frequency')=='annual'     ? 'selected' : '' }}>Annual</option>
                                <option value="custom"     {{ old('frequency')=='custom'     ? 'selected' : '' }}>Custom Interval</option>
                            </select>
                        </div>

                        <div class="col-md-4" id="customIntervalGroup" style="display:none">
                            <label class="form-label">Every</label>
                            <div class="input-group">
                                <input type="number" name="interval_value" class="form-control" value="{{ old('interval_value', 1) }}" min="1" max="365">
                                <select name="interval_unit" class="form-select">
                                    <option value="days"   {{ old('interval_unit')=='days'   ? 'selected' : '' }}>Days</option>
                                    <option value="weeks"  {{ old('interval_unit')=='weeks'  ? 'selected' : '' }}>Weeks</option>
                                    <option value="months" {{ old('interval_unit','months')=='months' ? 'selected' : '' }}>Months</option>
                                    <option value="years"  {{ old('interval_unit')=='years'  ? 'selected' : '' }}>Years</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Date Rule</label>
                            <select name="date_rule" class="form-select" id="dateRuleSelect">
                                <option value="specific_day"   {{ old('date_rule','specific_day')=='specific_day'   ? 'selected' : '' }}>Specific Day of Month</option>
                                <option value="first_of_month" {{ old('date_rule')=='first_of_month' ? 'selected' : '' }}>1st of Month</option>
                                <option value="last_of_month"  {{ old('date_rule')=='last_of_month'  ? 'selected' : '' }}>Last of Month</option>
                                <option value="business_day"   {{ old('date_rule')=='business_day'   ? 'selected' : '' }}>Business Day Nearest</option>
                            </select>
                        </div>

                        <div class="col-md-2" id="specificDayGroup">
                            <label class="form-label">Day of Month</label>
                            <input type="number" name="specific_day" class="form-control" value="{{ old('specific_day', 1) }}" min="1" max="28">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="starts_on" class="form-control" value="{{ old('starts_on', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">End Type</label>
                            <select name="end_type" class="form-select" id="endTypeSelect">
                                <option value="never" {{ old('end_type','never')=='never' ? 'selected' : '' }}>No End Date</option>
                                <option value="date"  {{ old('end_type')=='date'  ? 'selected' : '' }}>End On Date</option>
                                <option value="count" {{ old('end_type')=='count' ? 'selected' : '' }}>End After N Invoices</option>
                            </select>
                        </div>

                        <div class="col-md-3" id="endsOnGroup" style="display:none">
                            <label class="form-label">End Date</label>
                            <input type="date" name="ends_on" class="form-control" value="{{ old('ends_on') }}">
                        </div>

                        <div class="col-md-3" id="maxOccGroup" style="display:none">
                            <label class="form-label">Max Invoices</label>
                            <input type="number" name="max_occurrences" class="form-control" value="{{ old('max_occurrences') }}" min="1" max="9999">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="skip_weekends" value="1" id="skipWeekends" {{ old('skip_weekends') ? 'checked' : '' }}>
                                <label class="form-check-label" for="skipWeekends">Skip weekends (move to next Monday if due date falls on Sat/Sun)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary prev-tab" data-target="step1">← Back</button>
                    <button type="button" class="btn btn-primary next-tab" data-target="step3">Next: Reminders →</button>
                </div>
            </div>

            {{-- ────────────────────────────────────────────────────── --}}
            {{-- STEP 3 – Reminders                                    --}}
            {{-- ────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="step3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header"><strong>Notification & Reminder Settings</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmail" {{ old('send_email', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="sendEmail">Send invoice by email</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Email Subject</label>
                            <input type="text" name="email_subject" class="form-control" value="{{ old('email_subject') }}" placeholder="Invoice {number} from {company}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Send Reminder X Days <strong>Before</strong> Due Date</label>
                            <div class="d-flex flex-wrap gap-3" id="beforeDays">
                                @foreach([1,3,7,14] as $d)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="reminder_before_days[]" value="{{ $d }}" id="before_{{ $d }}"
                                        {{ in_array($d, old('reminder_before_days', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="before_{{ $d }}">{{ $d }} day{{ $d > 1 ? 's' : '' }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Send Overdue Reminder X Days <strong>After</strong> Due Date</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach([1,3,7,14,30] as $d)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="reminder_after_days[]" value="{{ $d }}" id="after_{{ $d }}"
                                        {{ in_array($d, old('reminder_after_days', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="after_{{ $d }}">{{ $d }} day{{ $d > 1 ? 's' : '' }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary prev-tab" data-target="step2">← Back</button>
                    <button type="button" class="btn btn-primary next-tab" data-target="step4">Next: Automation →</button>
                </div>
            </div>

            {{-- ────────────────────────────────────────────────────── --}}
            {{-- STEP 4 – Automation + Review                          --}}
            {{-- ────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="step4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header"><strong>Automation Mode</strong></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach([
                                ['draft',         'Draft (Review Before Send)',  'Generate as draft. You approve before sending.', 'fe-file'],
                                ['auto_send',      'Auto Send',                  'Auto-generate and email invoice on schedule.',   'fe-send'],
                                ['reminder_only',  'Reminder Only',              'Send reminder notice; no invoice generated.',    'fe-bell'],
                                ['manual',         'Manual Trigger',             'Template waits for you to run it manually.',     'fe-settings'],
                            ] as [$val, $label, $desc, $icon])
                            <div class="col-md-6 col-lg-3">
                                <div class="card border automation-card {{ old('automation_mode', 'draft') === $val ? 'border-primary' : '' }}" role="button"
                                     onclick="selectMode('{{ $val }}', this)">
                                    <div class="card-body text-center py-4">
                                        <i class="fe {{ $icon }} fs-3 mb-2 d-block text-primary"></i>
                                        <div class="fw-semibold">{{ $label }}</div>
                                        <div class="text-muted small mt-1">{{ $desc }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="automation_mode" id="automationModeInput" value="{{ old('automation_mode', 'draft') }}">
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="payment_link_enabled" value="1" id="paymentLinkEnabled" @checked(old('payment_link_enabled', true))>
                                    <label class="form-check-label" for="paymentLinkEnabled">Include payment link when available</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_payment_enabled" value="1" id="autoPaymentEnabled" @checked(old('auto_payment_enabled'))>
                                    <label class="form-check-label" for="autoPaymentEnabled">Mark as subscription-ready for saved payment methods</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary prev-tab" data-target="step3">← Back</button>
                    <button type="button" class="btn btn-primary next-tab" data-target="step5">Next: Review →</button>
                </div>
            </div>

            <div class="tab-pane fade" id="step5">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header"><strong>Review & Save</strong></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><div class="text-muted small">Template</div><div class="fw-semibold" id="reviewTemplate">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Customer</div><div class="fw-semibold" id="reviewCustomer">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Currency</div><div class="fw-semibold" id="reviewCurrency">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Frequency</div><div class="fw-semibold" id="reviewFrequency">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Date Rule</div><div class="fw-semibold" id="reviewDateRule">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Automation</div><div class="fw-semibold" id="reviewAutomation">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Start Date</div><div class="fw-semibold" id="reviewStart">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">End Rule</div><div class="fw-semibold" id="reviewEnd">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Email</div><div class="fw-semibold" id="reviewEmail">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Reminders Before</div><div class="fw-semibold" id="reviewBefore">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Reminders After</div><div class="fw-semibold" id="reviewAfter">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Payment Link</div><div class="fw-semibold" id="reviewPaymentLink">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Auto Payment</div><div class="fw-semibold" id="reviewAutoPayment">-</div></div>
                            <div class="col-md-4"><div class="text-muted small">Estimated Total</div><div class="fw-semibold" id="reviewTotal">0.00</div></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary prev-tab" data-target="step4">← Back</button>
                    <button type="submit" class="btn btn-success px-5">
                        <i class="fe fe-check me-2"></i> Create Recurring Template
                    </button>
                </div>
            </div>

        </div>{{-- /tab-content --}}
        </div>
    </form>
</div>
</div>

@push('scripts')
<script>
const recurringForm = document.getElementById('recurringForm');

// ── Wizard tab navigation ──────────────────────────────────────────────────
function showWizardStep(stepId) {
    const tab = document.querySelector(`[href="#${stepId}"]`);
    const pane = document.getElementById(stepId);
    if (!tab || !pane) {
        return;
    }

    if (window.bootstrap?.Tab) {
        bootstrap.Tab.getOrCreateInstance(tab).show();
        return;
    }

    document.querySelectorAll('#wizardTabs .nav-link').forEach(link => link.classList.remove('active'));
    document.querySelectorAll('.tab-content .tab-pane').forEach(panel => panel.classList.remove('show', 'active'));
    tab.classList.add('active');
    pane.classList.add('show', 'active');
}

document.querySelectorAll('.next-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        updateReview();
        showWizardStep(btn.dataset.target);
    });
});
document.querySelectorAll('.prev-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        showWizardStep(btn.dataset.target);
    });
});

// ── Frequency / date rule toggles ────────────────────────────────────────
function toggleRecurrenceUI() {
    const freq = document.getElementById('frequencySelect')?.value;
    const customIntervalGroup = document.getElementById('customIntervalGroup');
    if (customIntervalGroup) customIntervalGroup.style.display = freq === 'custom' ? '' : 'none';
    const rule = document.getElementById('dateRuleSelect')?.value;
    const specificDayGroup = document.getElementById('specificDayGroup');
    if (specificDayGroup) specificDayGroup.style.display = rule === 'specific_day' ? '' : 'none';
    updateReview();
}
document.getElementById('frequencySelect')?.addEventListener('change', toggleRecurrenceUI);
document.getElementById('dateRuleSelect')?.addEventListener('change', toggleRecurrenceUI);
toggleRecurrenceUI();

// ── End type toggles ─────────────────────────────────────────────────────
function toggleEndType() {
    const v = document.getElementById('endTypeSelect')?.value;
    const endsOnGroup = document.getElementById('endsOnGroup');
    const maxOccGroup = document.getElementById('maxOccGroup');
    if (endsOnGroup) endsOnGroup.style.display = v === 'date' ? '' : 'none';
    if (maxOccGroup) maxOccGroup.style.display = v === 'count' ? '' : 'none';
    updateReview();
}
document.getElementById('endTypeSelect')?.addEventListener('change', toggleEndType);
toggleEndType();

// ── Customer → currency auto-fill ─────────────────────────────────────────
document.getElementById('customerSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const cur = opt?.dataset.currency || 'NGN';
    const currencyInput = document.getElementById('currencyInput');
    if (currencyInput) currencyInput.value = cur;
    updateReview();
});

// ── Line items engine ─────────────────────────────────────────────────────
let rowIdx = 0;

function escapeAttr(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));
}

function addRow(data = {}) {
    const i = rowIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text"   name="items[${i}][product_name]" class="form-control form-control-sm item-text" value="${escapeAttr(data.product_name)}" required placeholder="Description">
            <input type="hidden" name="items[${i}][product_id]"   value="${escapeAttr(data.product_id)}"></td>
        <td><input type="number" name="items[${i}][qty]"          class="form-control form-control-sm item-calc" value="${escapeAttr(data.qty ?? 1)}" min="0.01" step="0.01" required></td>
        <td><input type="number" name="items[${i}][unit_price]"   class="form-control form-control-sm item-calc" value="${escapeAttr(data.unit_price ?? 0)}" min="0" step="0.01" required></td>
        <td><input type="number" name="items[${i}][tax]"          class="form-control form-control-sm item-calc" value="${escapeAttr(data.tax ?? 0)}" min="0" step="0.01"></td>
        <td><input type="number" name="items[${i}][discount]"     class="form-control form-control-sm item-calc" value="${escapeAttr(data.discount ?? 0)}" min="0" step="0.01"></td>
        <td class="row-total fw-semibold">0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fe fe-x"></i></button></td>`;
    document.getElementById('itemsBody')?.appendChild(tr);
    tr.querySelectorAll('.item-calc').forEach(inp => inp.addEventListener('input', recalc));
    tr.querySelectorAll('.item-text').forEach(inp => inp.addEventListener('input', updateReview));
    tr.querySelector('.remove-row').addEventListener('click', () => { tr.remove(); recalc(); });
    recalc();
}

function recalc() {
    let subtotal = 0, tax = 0, discount = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const qty   = parseFloat(tr.querySelector('[name$="[qty]"]')?.value) || 0;
        const price = parseFloat(tr.querySelector('[name$="[unit_price]"]')?.value) || 0;
        const t     = parseFloat(tr.querySelector('[name$="[tax]"]')?.value) || 0;
        const d     = parseFloat(tr.querySelector('[name$="[discount]"]')?.value) || 0;
        const line  = qty * price;
        const total = line + t - d;
        const rowTotal = tr.querySelector('.row-total');
        if (rowTotal) rowTotal.textContent = total.toFixed(2);
        subtotal += line;
        tax      += t;
        discount += d;
    });
    const setTotal = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value.toFixed(2); };
    setTotal('sumSubtotal', subtotal);
    setTotal('sumTax', tax);
    setTotal('sumDiscount', discount);
    setTotal('sumTotal', subtotal + tax - discount);
    updateReview();
}

document.getElementById('addItemBtn')?.addEventListener('click', () => addRow());

// ── Automation mode card select ───────────────────────────────────────────
function selectMode(val, card) {
    document.querySelectorAll('.automation-card').forEach(c => c.classList.remove('border-primary'));
    card?.classList.add('border-primary');
    const automationModeInput = document.getElementById('automationModeInput');
    if (automationModeInput) automationModeInput.value = val;
    updateReview();
}

function updateReview() {
    const named = (name) => document.querySelector(`[name="${name}"]`);
    const selectedText = (selector) => {
        const el = document.querySelector(selector);
        return el && el.selectedOptions && el.selectedOptions[0] ? el.selectedOptions[0].textContent.trim() : '-';
    };
    const checkedValues = (name) => Array.from(document.querySelectorAll(`[name="${name}[]"]:checked`)).map(el => `${el.value} day${el.value === '1' ? '' : 's'}`).join(', ');
    const yesNo = (id) => document.getElementById(id)?.checked ? 'Enabled' : 'Disabled';
    const automationLabels = {
        draft: 'Draft (Review Before Send)',
        auto_send: 'Auto Send',
        reminder_only: 'Reminder Only',
        manual: 'Manual Trigger',
    };
    const set = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value || '-'; };
    set('reviewTemplate', named('template_name')?.value || '-');
    set('reviewCustomer', selectedText('[name="customer_id"]'));
    set('reviewCurrency', named('currency')?.value || 'NGN');
    set('reviewFrequency', selectedText('[name="frequency"]'));
    set('reviewDateRule', selectedText('[name="date_rule"]'));
    const mode = document.getElementById('automationModeInput')?.value || 'draft';
    set('reviewAutomation', automationLabels[mode] || mode);
    set('reviewStart', named('starts_on')?.value || '-');
    const endType = named('end_type')?.value;
    const endDetail = endType === 'date' ? named('ends_on')?.value : (endType === 'count' ? `${named('max_occurrences')?.value || '-'} invoices` : '');
    set('reviewEnd', `${selectedText('[name="end_type"]')}${endDetail ? `: ${endDetail}` : ''}`);
    set('reviewEmail', yesNo('sendEmail'));
    set('reviewBefore', checkedValues('reminder_before_days') || 'None');
    set('reviewAfter', checkedValues('reminder_after_days') || 'None');
    set('reviewPaymentLink', yesNo('paymentLinkEnabled'));
    set('reviewAutoPayment', yesNo('autoPaymentEnabled'));
    set('reviewTotal', document.getElementById('sumTotal')?.textContent || '0.00');
}

function validItemRows() {
    return Array.from(document.querySelectorAll('#itemsBody tr')).filter((tr) => {
        const name = tr.querySelector('[name$="[product_name]"]')?.value.trim();
        const qty = parseFloat(tr.querySelector('[name$="[qty]"]')?.value);
        const price = parseFloat(tr.querySelector('[name$="[unit_price]"]')?.value);
        return name && qty > 0 && price >= 0;
    });
}

// ── Pre-fill from sale if present ─────────────────────────────────────────
@php
    $initialItems = old('items');
    if ($initialItems === null && $prefillSale && $prefillSale->items) {
        $initialItems = $prefillSale->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'qty' => $item->qty,
            'unit_price' => $item->unit_price,
            'tax' => $item->tax ?? 0,
            'discount' => $item->discount ?? 0,
        ])->values();
    }
    $initialItems = collect($initialItems ?? [])->values();
@endphp
const initialItems = @json($initialItems);

if (Array.isArray(initialItems) && initialItems.length) {
    initialItems.forEach(item => addRow(item));
} else {
    addRow();
}
document.querySelectorAll('#recurringForm input, #recurringForm select, #recurringForm textarea').forEach((field) => {
    field.addEventListener('input', updateReview);
    field.addEventListener('change', updateReview);
});

recurringForm?.addEventListener('submit', function (event) {
    if (validItemRows().length === 0) {
        event.preventDefault();
        event.stopPropagation();
        showWizardStep('step1');
        if (!recurringForm.querySelector('#itemsBody tr')) {
            addRow({ qty: 1, unit_price: 0 });
        }
        const firstName = recurringForm.querySelector('#itemsBody [name$="[product_name]"]');
        firstName?.setCustomValidity('Add at least one invoice item.');
        firstName?.reportValidity();
        window.setTimeout(() => firstName?.setCustomValidity(''), 500);
        return;
    }

    if (!recurringForm.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();

        const firstInvalid = recurringForm.querySelector(':invalid');
        const pane = firstInvalid?.closest('.tab-pane');
        if (pane?.id) {
            showWizardStep(pane.id);
        }

        window.setTimeout(() => {
            firstInvalid?.focus({ preventScroll: false });
            firstInvalid?.reportValidity();
        }, 180);

        return;
    }

    const submitButton = recurringForm.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fe fe-loader me-2"></i> Creating Template...';
    }
});
updateReview();
</script>
@endpush
@endsection
