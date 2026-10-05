@extends('layout.mainlayout')

@section('page-title', 'Create Tax Filing')

@section('content')
<style>
    :root {
        --sidebar-w: 270px;
        --sidebar-collapsed: 80px;
        --compliance-page-bg: #f8fafc;
        --compliance-card-bg: #ffffff;
        --compliance-card-border: #dbe4ee;
        --compliance-text: #0f172a;
        --compliance-muted: #475569;
        --compliance-input-bg: #ffffff;
        --compliance-input-border: #cbd5e1;
    }
    #tax-filing-create-wrapper {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        padding: 100px 1.5rem 2rem;
        min-height: 100vh;
        background: var(--compliance-page-bg);
        color: var(--compliance-text);
        transition: margin-left .3s, width .3s;
    }
    body.sidebar-icon-only #tax-filing-create-wrapper,
    body.mini-sidebar #tax-filing-create-wrapper {
        margin-left: var(--sidebar-collapsed);
        width: calc(100% - var(--sidebar-collapsed));
    }
    @media (max-width: 991.98px) {
        #tax-filing-create-wrapper { margin-left: 0; width: 100%; }
    }
    #tax-filing-create-wrapper .text-muted { color: var(--compliance-muted) !important; }
    #tax-filing-create-wrapper .card,
    #tax-filing-create-wrapper .card-body,
    #tax-filing-create-wrapper .form-label,
    #tax-filing-create-wrapper h4,
    #tax-filing-create-wrapper p,
    #tax-filing-create-wrapper .table,
    #tax-filing-create-wrapper .table th,
    #tax-filing-create-wrapper .table td {
        color: var(--compliance-text);
    }
    #tax-filing-create-wrapper .card {
        background: var(--compliance-card-bg);
        border: 1px solid var(--compliance-card-border) !important;
    }
    #tax-filing-create-wrapper .form-control,
    #tax-filing-create-wrapper .form-select {
        background: var(--compliance-input-bg);
        border-color: var(--compliance-input-border);
        color: var(--compliance-text);
    }
    #tax-filing-create-wrapper .form-control::placeholder {
        color: #64748b;
    }
    #tax-filing-create-wrapper .form-section {
        padding: 1.25rem 0;
        border-bottom: 1px solid var(--compliance-card-border);
    }
    #tax-filing-create-wrapper .form-section:first-child { padding-top: 0; }
    #tax-filing-create-wrapper .form-section:last-of-type { border-bottom: 0; }
    #tax-filing-create-wrapper .section-title { font-size: 1rem; font-weight: 700; margin-bottom: .25rem; }
    #tax-filing-create-wrapper .context-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #cbd5e1;
    }
    #tax-filing-create-wrapper .context-item { background: #eef4fb; padding: .75rem; min-width: 0; }
    #tax-filing-create-wrapper .context-label { color: #475569; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
    #tax-filing-create-wrapper .context-value { color: #0b3570; font-weight: 700; overflow-wrap: anywhere; }
    @media (max-width: 767.98px) {
        #tax-filing-create-wrapper .context-strip { grid-template-columns: 1fr 1fr; }
    }
</style>

<div id="tax-filing-create-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Create Tax Filing</h4>
            <p class="text-muted mb-0 small">Prepare a jurisdiction-specific return from configured tax records.</p>
        </div>
        <a href="{{ route('compliance.tax-filings.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($jurisdictions->isEmpty())
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <strong>Tax setup is required</strong>
                <div class="small mt-1">Install a verified country tax pack to load jurisdictions, return types, filing frequencies, and reporting currency.</div>
            </div>
            <form method="POST" action="{{ route('compliance.tax-center.bootstrap') }}" class="d-flex flex-wrap gap-2">
                @csrf
                <select name="country_code" class="form-select" aria-label="Country tax pack" required>
                    @foreach($presetCountries as $countryCode => $country)
                        <option value="{{ $countryCode }}">{{ $country['name'] }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary text-white text-nowrap">Install Tax Pack</button>
            </form>
        </div>
    @elseif($configuredReturnCount === 0)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <strong>No active return configuration</strong>
                <div class="small mt-1">The jurisdiction exists, but it has no active VAT, sales tax, company tax, PAYE, or withholding setup.</div>
            </div>
            <a href="{{ route('compliance.tax-center.index') }}" class="btn btn-outline-primary text-nowrap">Open Tax Center</a>
        </div>
    @endif

    @if($configuredReturnCount > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('compliance.tax-filings.store') }}" id="taxFilingForm">
                @csrf
                <section class="form-section">
                    <div class="section-title">Return Identity</div>
                    <p class="small text-muted mb-3">Choices are limited to active tax codes and rules configured for the jurisdiction.</p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label" for="tax_jurisdiction_id">Jurisdiction</label>
                            <select name="tax_jurisdiction_id" id="tax_jurisdiction_id" class="form-select" required>
                                <option value="">Select jurisdiction</option>
                                @foreach($jurisdictions as $jurisdiction)
                                    <option value="{{ $jurisdiction->id }}"
                                        data-country="{{ $jurisdiction->country_code }}"
                                        data-region="{{ $jurisdiction->region }}"
                                        data-authority="{{ $jurisdiction->tax_authority_name ?: $jurisdiction->name }}"
                                        data-currency="{{ $jurisdiction->currency_code }}"
                                        @selected(old('tax_jurisdiction_id') == $jurisdiction->id)>
                                        {{ $jurisdiction->country_code }} - {{ $jurisdiction->name }}{{ $jurisdiction->region ? ' (' . $jurisdiction->region . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="filing_type">Return Type</label>
                            <select name="filing_type" id="filing_type" class="form-select" required disabled>
                                <option value="">Select jurisdiction first</option>
                            </select>
                            <div id="filingTypeHelp" class="form-text"></div>
                        </div>
                        <div class="col-lg-8">
                            <label class="form-label" for="filing_name">Filing Name</label>
                            <input type="text" name="name" id="filing_name" class="form-control" value="{{ old('name') }}" placeholder="Generated from return type and period" required>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label" for="currency_code">Reporting Currency</label>
                            <input type="text" name="currency_code" id="currency_code" class="form-control" value="{{ old('currency_code') }}" maxlength="3" readonly required>
                        </div>
                    </div>
                    <div class="context-strip mt-3" id="jurisdictionContext" hidden>
                        <div class="context-item"><div class="context-label">Country</div><div class="context-value" id="contextCountry">-</div></div>
                        <div class="context-item"><div class="context-label">Region</div><div class="context-value" id="contextRegion">-</div></div>
                        <div class="context-item"><div class="context-label">Tax Authority</div><div class="context-value" id="contextAuthority">-</div></div>
                        <div class="context-item"><div class="context-label">Currency</div><div class="context-value" id="contextCurrency">-</div></div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="section-title">Filing Period</div>
                    <p class="small text-muted mb-3">The available frequency comes from the selected return configuration.</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="filing_frequency">Frequency</label>
                            <select name="filing_frequency" id="filing_frequency" class="form-select" required disabled>
                                <option value="">Select return type first</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="period_start">Period Start</label>
                            <input type="date" name="period_start" id="period_start" class="form-control" value="{{ old('period_start') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="period_end">Period End</label>
                            <input type="date" name="period_end" id="period_end" class="form-control" value="{{ old('period_end') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="due_date">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date') }}">
                        </div>
                    </div>
                    <div class="alert alert-info py-2 mt-3 mb-0" id="dueRuleNotice" hidden></div>
                </section>

                <section class="form-section" id="citWorkpaperFields" hidden>
                    <div class="row g-3">
                    <div class="col-12">
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h6 class="mb-1">Company Tax Workpaper</h6>
                                    <p class="small text-muted mb-0">Enter reviewed adjustments and rates for this accounting period.</p>
                                </div>
                                <span class="badge bg-warning text-dark">Reviewer input</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Accounting Profit</label><input type="number" step="0.01" min="0" name="accounting_profit" class="form-control cit-input" value="{{ old('accounting_profit', 0) }}"></div>
                                <div class="col-md-4"><label class="form-label">Disallowable Expenses</label><input type="number" step="0.01" min="0" name="disallowable_expenses" class="form-control cit-input" value="{{ old('disallowable_expenses', 0) }}"></div>
                                <div class="col-md-4"><label class="form-label">Loss Relief</label><input type="number" step="0.01" min="0" name="loss_relief" class="form-control cit-input" value="{{ old('loss_relief', 0) }}"></div>
                                <div class="col-md-4"><label class="form-label">Capital Allowances</label><input type="number" step="0.01" min="0" name="capital_allowances" class="form-control cit-input" value="{{ old('capital_allowances', 0) }}"></div>
                                <div class="col-md-4"><label class="form-label">Tax Credits</label><input type="number" step="0.01" min="0" name="cit_credits" class="form-control cit-input" value="{{ old('cit_credits', 0) }}"></div>
                                <div class="col-md-4"><label class="form-label">Company Tax Rate (%)</label><input type="number" step="0.0001" min="0" max="100" name="cit_rate" class="form-control cit-input" value="{{ old('cit_rate', 30) }}"></div>
                                <div class="col-md-4"><label class="form-label">Development Levy Rate (%)</label><input type="number" step="0.0001" min="0" max="100" name="development_levy_rate" class="form-control cit-input" value="{{ old('development_levy_rate', 0) }}"></div>
                            </div>
                        </div>
                    </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="section-title">Return Totals</div>
                            <p class="small text-muted mb-0">Calculate from posted transactions, then review before creating the draft.</p>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="previewTotalsBtn" disabled>Calculate from Transactions</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Taxable Base</label><input type="number" step="0.01" min="0" name="total_taxable" id="total_taxable" class="form-control" value="{{ old('total_taxable', 0) }}"></div>
                        <div class="col-md-4"><label class="form-label">Gross Tax</label><input type="number" step="0.01" min="0" name="total_tax" id="total_tax" class="form-control" value="{{ old('total_tax', 0) }}"></div>
                        <div class="col-md-4"><label class="form-label">Credits / Input Tax</label><input type="number" step="0.01" min="0" name="tax_credit" id="tax_credit" class="form-control" value="{{ old('tax_credit', 0) }}"></div>
                        <div class="col-md-4"><label class="form-label">Other Adjustments</label><input type="number" step="0.01" min="0" name="adjustments_total" id="adjustments_total" class="form-control" value="{{ old('adjustments_total', 0) }}"></div>
                        <div class="col-md-4"><label class="form-label">Tax Due</label><input type="number" step="0.01" min="0" name="tax_due" id="tax_due" class="form-control" value="{{ old('tax_due', 0) }}"></div>
                        <div class="col-md-4"><label class="form-label">Refund / Carry-forward</label><input type="number" step="0.01" min="0" name="tax_refund" id="tax_refund" class="form-control" value="{{ old('tax_refund', 0) }}"></div>
                    </div>
                    <div id="taxPreviewSummary" class="mt-3 small text-muted"></div>
                    <div id="taxPreviewLines" class="mt-3"></div>
                </section>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                    <a href="{{ route('compliance.tax-filings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-primary text-white" id="createFilingBtn" disabled>Create Draft Filing</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@section('script')
<script>
(function () {
    const optionsByJurisdiction = @json($filingOptionsByJurisdiction);
    const frequencyLabels = @json($frequencyLabels);
    let pendingOldType = @json(old('filing_type'));
    let pendingOldFrequency = @json(old('filing_frequency'));
    const oldDueDate = @json(old('due_date'));
    const jurisdictionField = document.getElementById('tax_jurisdiction_id');
    const filingTypeField = document.getElementById('filing_type');
    const frequencyField = document.getElementById('filing_frequency');
    const currencyField = document.getElementById('currency_code');
    const nameField = document.getElementById('filing_name');
    const periodStart = document.getElementById('period_start');
    const periodEnd = document.getElementById('period_end');
    const dueDate = document.getElementById('due_date');
    const context = document.getElementById('jurisdictionContext');
    const dueRuleNotice = document.getElementById('dueRuleNotice');
    const btn = document.getElementById('previewTotalsBtn');
    const createBtn = document.getElementById('createFilingBtn');
    const citFields = document.getElementById('citWorkpaperFields');
    let currentOption = null;
    let generatedName = '';

    if (!btn || !jurisdictionField) return;

    function appendOption(select, value, label) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        select.appendChild(option);
    }

    function selectedJurisdictionOption() {
        return jurisdictionField.options[jurisdictionField.selectedIndex] || null;
    }

    function updateContext() {
        const selected = selectedJurisdictionOption();
        const hasJurisdiction = Boolean(selected && selected.value);
        context.hidden = !hasJurisdiction;
        if (!hasJurisdiction) return;

        document.getElementById('contextCountry').textContent = selected.dataset.country || '-';
        document.getElementById('contextRegion').textContent = selected.dataset.region || 'National';
        document.getElementById('contextAuthority').textContent = selected.dataset.authority || '-';
        document.getElementById('contextCurrency').textContent = selected.dataset.currency || '-';
    }

    function updateJurisdiction() {
        const selectedId = jurisdictionField.value;
        const filingOptions = optionsByJurisdiction[selectedId] || {};
        filingTypeField.innerHTML = '';
        appendOption(filingTypeField, '', Object.keys(filingOptions).length ? 'Select return type' : 'No configured return types');
        Object.values(filingOptions).forEach(function (option) {
            appendOption(filingTypeField, option.value, option.label);
        });
        filingTypeField.disabled = !Object.keys(filingOptions).length;
        const preferredType = pendingOldType && filingOptions[pendingOldType] ? pendingOldType : '';
        filingTypeField.value = preferredType;
        pendingOldType = null;
        updateContext();
        updateFilingType();

        const help = document.getElementById('filingTypeHelp');
        help.textContent = Object.keys(filingOptions).length
            ? 'Only returns backed by active tax configuration are shown.'
            : (selectedId ? 'Configure active tax codes or withholding rules in Tax Center first.' : '');
    }

    function updateFilingType() {
        const filingOptions = optionsByJurisdiction[jurisdictionField.value] || {};
        currentOption = filingOptions[filingTypeField.value] || null;
        frequencyField.innerHTML = '';

        if (!currentOption) {
            appendOption(frequencyField, '', 'Select return type first');
            frequencyField.disabled = true;
            currencyField.value = selectedJurisdictionOption()?.dataset.currency || '';
            btn.disabled = true;
            createBtn.disabled = true;
            dueRuleNotice.hidden = true;
            citFields.hidden = true;
            return;
        }

        currentOption.frequencies.forEach(function (frequency) {
            appendOption(frequencyField, frequency, frequencyLabels[frequency] || frequency);
        });
        frequencyField.disabled = false;
        frequencyField.value = pendingOldFrequency && currentOption.frequencies.includes(pendingOldFrequency)
            ? pendingOldFrequency
            : currentOption.default_frequency;
        pendingOldFrequency = null;
        currencyField.value = currentOption.currency;
        btn.disabled = false;
        createBtn.disabled = false;
        citFields.hidden = filingTypeField.value !== 'corporate_income_tax';
        updateDueDate();
        updateGeneratedName();
    }

    function updateDueDate() {
        if (!currentOption) return;

        const messages = [];
        if (currentOption.deadline_months > 0) {
            messages.push('Configured deadline: ' + currentOption.deadline_months + ' months after the period end.');
            if (periodEnd.value && !oldDueDate) {
                const source = new Date(periodEnd.value + 'T12:00:00');
                const targetYear = source.getFullYear() + Math.floor((source.getMonth() + Number(currentOption.deadline_months)) / 12);
                const targetMonth = (source.getMonth() + Number(currentOption.deadline_months)) % 12;
                const lastDay = new Date(targetYear, targetMonth + 1, 0).getDate();
                const calculated = new Date(targetYear, targetMonth, Math.min(source.getDate(), lastDay), 12);
                dueDate.value = calculated.toISOString().slice(0, 10);
            }
        } else if (currentOption.deadline_days > 0) {
            messages.push('Configured deadline: ' + currentOption.deadline_days + ' days after the period end.');
            if (periodEnd.value && !oldDueDate) {
                const calculated = new Date(periodEnd.value + 'T12:00:00');
                calculated.setDate(calculated.getDate() + Number(currentOption.deadline_days));
                dueDate.value = calculated.toISOString().slice(0, 10);
            }
        } else {
            messages.push('Enter the authority due date for this filing period.');
        }
        if (currentOption.due_rule) messages.push(currentOption.due_rule);
        if (currentOption.requires_review) messages.push('Confirm nexus, sourcing, rates, and the assigned state or local authority before approval.');
        dueRuleNotice.textContent = messages.join(' ');
        dueRuleNotice.hidden = !messages.length;
    }

    function updateGeneratedName() {
        if (!currentOption || !periodEnd.value) return;
        const periodLabel = new Date(periodEnd.value + 'T12:00:00').toLocaleDateString(undefined, {
            month: frequencyField.value === 'annual' ? undefined : 'short',
            year: 'numeric'
        });
        const nextName = currentOption.label + ' - ' + periodLabel;
        if (!nameField.value || nameField.value === generatedName) {
            nameField.value = nextName;
            generatedName = nextName;
        }
    }

    jurisdictionField.addEventListener('change', updateJurisdiction);
    filingTypeField.addEventListener('change', updateFilingType);
    frequencyField.addEventListener('change', updateGeneratedName);
    periodEnd.addEventListener('change', function () {
        if (!oldDueDate) dueDate.value = '';
        updateDueDate();
        updateGeneratedName();
    });
    periodStart.addEventListener('change', updateGeneratedName);

    btn.addEventListener('click', async function () {
        const start = periodStart.value;
        const end = periodEnd.value;
        const jurisdictionId = jurisdictionField.value;
        const filingType = filingTypeField.value;
        const summary = document.getElementById('taxPreviewSummary');
        const lines = document.getElementById('taxPreviewLines');

        if (!jurisdictionId || !filingType || !start || !end) {
            summary.innerHTML = '<span class="text-danger">Select a jurisdiction, return type, and complete filing period first.</span>';
            return;
        }

        const url = new URL('{{ route('compliance.tax-filings.preview') }}', window.location.origin);
        url.searchParams.set('period_start', start);
        url.searchParams.set('period_end', end);
        url.searchParams.set('filing_type', filingType);
        document.querySelectorAll('.cit-input').forEach(function (input) {
            url.searchParams.set(input.name, input.value || '0');
        });
        if (jurisdictionId) {
            url.searchParams.set('tax_jurisdiction_id', jurisdictionId);
        }

        btn.disabled = true;
        btn.innerText = 'Calculating...';

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to preview totals');
            }

            document.getElementById('total_taxable').value = Number(data.total_taxable || 0).toFixed(2);
            document.getElementById('total_tax').value = Number(data.total_tax || 0).toFixed(2);
            document.getElementById('tax_due').value = Number(data.tax_due || 0).toFixed(2);
            document.getElementById('tax_credit').value = Number(data.tax_credit || 0).toFixed(2);
            document.getElementById('tax_refund').value = Number(data.tax_refund || 0).toFixed(2);
            document.getElementById('adjustments_total').value = Number(data.adjustments_total || 0).toFixed(2);

            summary.innerHTML =
                'Calculated in <strong>' + (currentOption?.currency || '') + '</strong> from posted records. ' +
                'Tax due: <strong>' + Number(data.tax_due || 0).toLocaleString() + '</strong>.';

            const previewLines = Array.isArray(data.lines) ? data.lines : [];
            lines.innerHTML = previewLines.length
                ? '<div class="table-responsive"><table class="table table-sm table-bordered bg-white"><thead><tr><th>Line</th><th>Type</th><th>Taxable Base</th><th>Tax</th><th>Credit</th><th>Net</th></tr></thead><tbody>' +
                    previewLines.map(function (line) {
                        return '<tr>' +
                            '<td>' + (line.label || line.line_key || 'Line') + '</td>' +
                            '<td>' + (line.tax_type || '-') + '</td>' +
                            '<td>' + Number(line.taxable_base || 0).toLocaleString() + '</td>' +
                            '<td>' + Number(line.tax_amount || 0).toLocaleString() + '</td>' +
                            '<td>' + Number(line.credit_amount || 0).toLocaleString() + '</td>' +
                            '<td>' + Number(line.net_amount || 0).toLocaleString() + '</td>' +
                        '</tr>';
                    }).join('') + '</tbody></table></div>'
                : '';
        } catch (err) {
            summary.innerHTML = '<span class="text-danger">' + err.message + '</span>';
            lines.innerHTML = '';
        } finally {
            btn.disabled = false;
            btn.innerText = 'Calculate from Transactions';
        }
    });

    updateJurisdiction();
})();
</script>
@endsection
