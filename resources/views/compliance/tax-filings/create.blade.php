@extends('layout.mainlayout')

@section('page-title', 'Create Tax Filing')

@section('content')
<style>
    :root {
        --sidebar-w: 270px;
        --sidebar-collapsed: 80px;
    }
    #tax-filing-create-wrapper {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        padding: 100px 1.5rem 2rem;
        min-height: 100vh;
        background: #f8fafc;
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
</style>

<div id="tax-filing-create-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Create Tax Filing</h4>
            <p class="text-muted mb-0 small">Set filing period and tax totals.</p>
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
            <form method="POST" action="{{ route('compliance.tax-filings.store') }}" id="taxFilingForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Filing Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jurisdiction</label>
                        <select name="tax_jurisdiction_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($jurisdictions as $jurisdiction)
                                <option value="{{ $jurisdiction->id }}" @selected(old('tax_jurisdiction_id') == $jurisdiction->id)>
                                    {{ $jurisdiction->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period Start</label>
                        <input type="date" name="period_start" id="period_start" class="form-control" value="{{ old('period_start') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period End</label>
                        <input type="date" name="period_end" id="period_end" class="form-control" value="{{ old('period_end') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Taxable</label>
                        <input type="number" step="0.01" min="0" name="total_taxable" id="total_taxable" class="form-control" value="{{ old('total_taxable', 0) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Tax</label>
                        <input type="number" step="0.01" min="0" name="total_tax" id="total_tax" class="form-control" value="{{ old('total_tax', 0) }}">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary" id="previewTotalsBtn">Auto Calculate from Transactions</button>
                    <button class="btn btn-primary text-white">Create Filing</button>
                </div>

                <div id="taxPreviewSummary" class="mt-3 small text-muted"></div>
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
        const summary = document.getElementById('taxPreviewSummary');

        if (!start || !end) {
            summary.innerHTML = '<span class="text-danger">Select period start and end first.</span>';
            return;
        }

        const url = new URL('{{ route('compliance.tax-filings.preview') }}', window.location.origin);
        url.searchParams.set('period_start', start);
        url.searchParams.set('period_end', end);

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

            summary.innerHTML =
                'Sales Taxable: <strong>' + Number(data.sales_taxable || 0).toLocaleString() + '</strong> | ' +
                'Purchase Taxable: <strong>' + Number(data.purchase_taxable || 0).toLocaleString() + '</strong> | ' +
                'Sales Tax: <strong>' + Number(data.sales_tax || 0).toLocaleString() + '</strong> | ' +
                'Purchase Tax: <strong>' + Number(data.purchase_tax || 0).toLocaleString() + '</strong>';
        } catch (err) {
            summary.innerHTML = '<span class="text-danger">' + err.message + '</span>';
        } finally {
            btn.disabled = false;
            btn.innerText = 'Auto Calculate from Transactions';
        }
    });
})();
</script>
@endsection
