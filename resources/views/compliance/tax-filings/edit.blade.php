@extends('layout.mainlayout')

@section('page-title', 'Edit Tax Filing')

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
    #tax-filing-edit-wrapper {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        padding: 100px 1.5rem 2rem;
        min-height: 100vh;
        background: var(--compliance-page-bg);
        color: var(--compliance-text);
        transition: margin-left .3s, width .3s;
    }
    body.sidebar-icon-only #tax-filing-edit-wrapper,
    body.mini-sidebar #tax-filing-edit-wrapper {
        margin-left: var(--sidebar-collapsed);
        width: calc(100% - var(--sidebar-collapsed));
    }
    @media (max-width: 991.98px) {
        #tax-filing-edit-wrapper { margin-left: 0; width: 100%; }
    }
    #tax-filing-edit-wrapper .text-muted { color: var(--compliance-muted) !important; }
    #tax-filing-edit-wrapper .card,
    #tax-filing-edit-wrapper .card-body,
    #tax-filing-edit-wrapper .form-label,
    #tax-filing-edit-wrapper h4,
    #tax-filing-edit-wrapper p,
    #tax-filing-edit-wrapper .table,
    #tax-filing-edit-wrapper .table th,
    #tax-filing-edit-wrapper .table td {
        color: var(--compliance-text);
    }
    #tax-filing-edit-wrapper .card {
        background: var(--compliance-card-bg);
        border: 1px solid var(--compliance-card-border) !important;
    }
    #tax-filing-edit-wrapper .form-control,
    #tax-filing-edit-wrapper .form-select {
        background: var(--compliance-input-bg);
        border-color: var(--compliance-input-border);
        color: var(--compliance-text);
    }
    #tax-filing-edit-wrapper .form-control::placeholder {
        color: #64748b;
    }
</style>

<div id="tax-filing-edit-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Edit Tax Filing</h4>
            <p class="text-muted mb-0 small">Update filing details before submission.</p>
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

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('compliance.tax-filings.update', $filing->id) }}" id="taxFilingEditForm">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Filing Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $filing->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jurisdiction</label>
                        <select name="tax_jurisdiction_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($jurisdictions as $jurisdiction)
                                <option value="{{ $jurisdiction->id }}" @selected(old('tax_jurisdiction_id', $filing->tax_jurisdiction_id) == $jurisdiction->id)>
                                    {{ $jurisdiction->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filing Type</label>
                        <select name="filing_type" id="filing_type" class="form-select" required>
                            <option value="vat" @selected(old('filing_type', $filing->filing_type) === 'vat')>VAT</option>
                            <option value="withholding" @selected(old('filing_type', $filing->filing_type) === 'withholding')>Withholding Tax</option>
                            <option value="paye" @selected(old('filing_type', $filing->filing_type) === 'paye')>PAYE</option>
                            <option value="all" @selected(old('filing_type', $filing->filing_type) === 'all')>Combined Compliance Snapshot</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Frequency</label>
                        <select name="filing_frequency" class="form-select">
                            <option value="">Use jurisdiction default</option>
                            <option value="monthly" @selected(old('filing_frequency', $filing->filing_frequency) === 'monthly')>Monthly</option>
                            <option value="quarterly" @selected(old('filing_frequency', $filing->filing_frequency) === 'quarterly')>Quarterly</option>
                            <option value="annually" @selected(old('filing_frequency', $filing->filing_frequency) === 'annually')>Annually</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency_code" class="form-control" value="{{ old('currency_code', $filing->currency_code ?: 'NGN') }}" maxlength="3">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period Start</label>
                        <input type="date" name="period_start" id="period_start" class="form-control" value="{{ old('period_start', optional($filing->period_start)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period End</label>
                        <input type="date" name="period_end" id="period_end" class="form-control" value="{{ old('period_end', optional($filing->period_end)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($filing->due_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Taxable</label>
                        <input type="number" step="0.01" min="0" name="total_taxable" id="total_taxable" class="form-control" value="{{ old('total_taxable', $filing->total_taxable) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Tax</label>
                        <input type="number" step="0.01" min="0" name="total_tax" id="total_tax" class="form-control" value="{{ old('total_tax', $filing->total_tax) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax Due</label>
                        <input type="number" step="0.01" min="0" name="tax_due" id="tax_due" class="form-control" value="{{ old('tax_due', $filing->tax_due) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Credits / Input Tax</label>
                        <input type="number" step="0.01" min="0" name="tax_credit" id="tax_credit" class="form-control" value="{{ old('tax_credit', $filing->tax_credit) }}">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary" id="previewTotalsBtn">Recalculate from Transactions</button>
                    <button class="btn btn-primary text-white">Update Filing</button>
                </div>

                <div id="taxPreviewSummary" class="mt-3 small text-muted"></div>
                <div id="taxPreviewLines" class="mt-3"></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const btn = document.getElementById('previewTotalsBtn');
    if (!btn) return;

    btn.addEventListener('click', async function () {
        const start = document.getElementById('period_start').value;
        const end = document.getElementById('period_end').value;
        const filingType = document.getElementById('filing_type').value;
        const jurisdictionId = document.querySelector('[name="tax_jurisdiction_id"]').value;
        const summary = document.getElementById('taxPreviewSummary');
        const lines = document.getElementById('taxPreviewLines');

        if (!start || !end) {
            summary.innerHTML = '<span class="text-danger">Select period start and end first.</span>';
            return;
        }

        const url = new URL('{{ route('compliance.tax-filings.preview') }}', window.location.origin);
        url.searchParams.set('period_start', start);
        url.searchParams.set('period_end', end);
        url.searchParams.set('filing_type', filingType);
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

            summary.innerHTML =
                'Sales Taxable: <strong>' + Number(data.sales_taxable || 0).toLocaleString() + '</strong> | ' +
                'Purchase Taxable: <strong>' + Number(data.purchase_taxable || 0).toLocaleString() + '</strong> | ' +
                'Sales Tax: <strong>' + Number(data.sales_tax || 0).toLocaleString() + '</strong> | ' +
                'Purchase Tax: <strong>' + Number(data.purchase_tax || 0).toLocaleString() + '</strong> | ' +
                'Tax Due: <strong>' + Number(data.tax_due || 0).toLocaleString() + '</strong>';

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
            btn.innerText = 'Recalculate from Transactions';
        }
    });
})();
</script>
@endsection
