@extends('layout.mainlayout')

@section('page-title', 'Tax Center')

@section('content')
<style>
    :root {
        --sidebar-w: 270px;
        --sidebar-collapsed: 80px;
    }
    #tax-center-wrapper {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        padding: 100px 1.5rem 2rem;
        min-height: 100vh;
        background: #f8fafc;
        transition: margin-left .3s, width .3s;
    }
    body.sidebar-icon-only #tax-center-wrapper,
    body.mini-sidebar #tax-center-wrapper {
        margin-left: var(--sidebar-collapsed);
        width: calc(100% - var(--sidebar-collapsed));
    }
    @media (max-width: 991.98px) {
        #tax-center-wrapper { margin-left: 0; width: 100%; }
    }
    .tc-card { border: 1px solid #e2e8f0; border-radius: 12px; }
</style>

<div id="tax-center-wrapper">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Tax Center</h4>
            <p class="text-muted mb-0 small">Manage jurisdictions, tax codes, and withholding rules.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('compliance.tax-filings.index') }}" class="btn btn-outline-primary btn-sm">Tax Filings</a>
            <a href="{{ route('reports.tax-sales') }}" class="btn btn-outline-secondary btn-sm">Sales Tax Report</a>
            <a href="{{ route('reports.tax-purchase') }}" class="btn btn-outline-secondary btn-sm">Purchase Tax Report</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!empty($taxSetupMissing))
        <div class="alert alert-warning">
            Taxation module tables are not available yet. Run <code>php artisan migrate</code> and reload this page.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card tc-card h-100">
                <div class="card-header bg-white"><strong>Add Jurisdiction</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('compliance.tax-center.jurisdictions.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Country Code</label>
                            <input type="text" name="country_code" class="form-control" value="{{ old('country_code') }}" maxlength="3">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Region</label>
                            <input type="text" name="region" class="form-control" value="{{ old('region') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Currency</label>
                            <input type="text" name="currency_code" class="form-control" value="{{ old('currency_code') }}" maxlength="3">
                        </div>
                        <button class="btn btn-primary btn-sm text-white">Save Jurisdiction</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card tc-card h-100">
                <div class="card-header bg-white"><strong>Add Tax Code</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('compliance.tax-center.codes.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Jurisdiction</label>
                            <select name="tax_jurisdiction_id" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($jurisdictions as $jurisdiction)
                                    <option value="{{ $jurisdiction->id }}" @selected(old('tax_jurisdiction_id') == $jurisdiction->id)>{{ $jurisdiction->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Description</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Rate (%)</label>
                            <input type="number" step="0.0001" min="0" max="100" name="rate" class="form-control" value="{{ old('rate') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="vat" @selected(old('type') === 'vat')>VAT</option>
                                <option value="gst" @selected(old('type') === 'gst')>GST</option>
                                <option value="sales_tax" @selected(old('type') === 'sales_tax')>Sales Tax</option>
                                <option value="withholding" @selected(old('type') === 'withholding')>Withholding</option>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm text-white">Save Tax Code</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card tc-card h-100">
                <div class="card-header bg-white"><strong>Add Withholding Rule</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('compliance.tax-center.withholding.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Jurisdiction</label>
                            <select name="tax_jurisdiction_id" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($jurisdictions as $jurisdiction)
                                    <option value="{{ $jurisdiction->id }}" @selected(old('tax_jurisdiction_id') == $jurisdiction->id)>{{ $jurisdiction->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Rule Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Counterparty Type</label>
                            <select name="counterparty_type" class="form-select" required>
                                <option value="vendor" @selected(old('counterparty_type') === 'vendor')>Supplier</option>
                                <option value="customer" @selected(old('counterparty_type') === 'customer')>Customer</option>
                                <option value="contractor" @selected(old('counterparty_type') === 'contractor')>Contractor</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Rate (%)</label>
                            <input type="number" step="0.0001" min="0" max="100" name="rate" class="form-control" value="{{ old('rate') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Threshold Amount</label>
                            <input type="number" step="0.01" min="0" name="threshold_amount" class="form-control" value="{{ old('threshold_amount') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Account Code</label>
                            <input type="text" name="account_code" class="form-control" value="{{ old('account_code') }}">
                        </div>
                        <button class="btn btn-primary btn-sm text-white">Save Rule</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card tc-card">
                <div class="card-header bg-white"><strong>Tax Codes</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Jurisdiction</th>
                                <th>Code</th>
                                <th>Rate</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($taxCodes as $taxCode)
                                <tr>
                                    <td>{{ $taxCode->jurisdiction?->name }}</td>
                                    <td>{{ $taxCode->code }}</td>
                                    <td>{{ number_format((float)$taxCode->rate, 4) }}%</td>
                                    <td>{{ strtoupper($taxCode->type) }}</td>
                                    <td>
                                        <span class="badge {{ $taxCode->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $taxCode->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#editTaxCode{{ $taxCode->id }}">Edit</button>
                                        <form method="POST" action="{{ route('compliance.tax-center.codes.destroy', $taxCode->id) }}" class="d-inline" onsubmit="return confirm('Delete this tax code?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="editTaxCode{{ $taxCode->id }}">
                                    <td colspan="6" class="bg-light">
                                        <form method="POST" action="{{ route('compliance.tax-center.codes.update', $taxCode->id) }}" class="row g-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-md-3">
                                                <select name="tax_jurisdiction_id" class="form-select form-select-sm" required>
                                                    @foreach($jurisdictions as $jurisdiction)
                                                        <option value="{{ $jurisdiction->id }}" @selected($taxCode->tax_jurisdiction_id == $jurisdiction->id)>{{ $jurisdiction->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2"><input type="text" name="code" class="form-control form-control-sm" value="{{ $taxCode->code }}" required></div>
                                            <div class="col-md-2"><input type="number" step="0.0001" min="0" max="100" name="rate" class="form-control form-control-sm" value="{{ $taxCode->rate }}" required></div>
                                            <div class="col-md-2">
                                                <select name="type" class="form-select form-select-sm" required>
                                                    <option value="vat" @selected($taxCode->type === 'vat')>VAT</option>
                                                    <option value="gst" @selected($taxCode->type === 'gst')>GST</option>
                                                    <option value="sales_tax" @selected($taxCode->type === 'sales_tax')>Sales Tax</option>
                                                    <option value="withholding" @selected($taxCode->type === 'withholding')>Withholding</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="taxCodeActive{{ $taxCode->id }}" @checked($taxCode->is_active)>
                                                    <label class="form-check-label" for="taxCodeActive{{ $taxCode->id }}">Active</label>
                                                </div>
                                            </div>
                                            <div class="col-md-10"><input type="text" name="description" class="form-control form-control-sm" value="{{ $taxCode->description }}" required></div>
                                            <div class="col-md-2 text-end"><button class="btn btn-primary btn-sm text-white">Update</button></div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-3">No tax codes yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card tc-card mb-3">
                <div class="card-header bg-white"><strong>Jurisdictions</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Country</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jurisdictions as $jurisdiction)
                                <tr>
                                    <td>{{ $jurisdiction->name }}</td>
                                    <td>{{ $jurisdiction->country_code ?? '-' }}</td>
                                    <td>{{ $jurisdiction->currency_code ?? '-' }}</td>
                                    <td><span class="badge {{ $jurisdiction->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $jurisdiction->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#editJur{{ $jurisdiction->id }}">Edit</button>
                                        <form method="POST" action="{{ route('compliance.tax-center.jurisdictions.destroy', $jurisdiction->id) }}" class="d-inline" onsubmit="return confirm('Delete this jurisdiction? This will remove related tax setup.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="editJur{{ $jurisdiction->id }}">
                                    <td colspan="5" class="bg-light">
                                        <form method="POST" action="{{ route('compliance.tax-center.jurisdictions.update', $jurisdiction->id) }}" class="row g-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm" value="{{ $jurisdiction->name }}" required></div>
                                            <div class="col-md-2"><input type="text" name="country_code" maxlength="3" class="form-control form-control-sm" value="{{ $jurisdiction->country_code }}"></div>
                                            <div class="col-md-3"><input type="text" name="region" class="form-control form-control-sm" value="{{ $jurisdiction->region }}"></div>
                                            <div class="col-md-2"><input type="text" name="currency_code" maxlength="3" class="form-control form-control-sm" value="{{ $jurisdiction->currency_code }}"></div>
                                            <div class="col-md-1 d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="jurActive{{ $jurisdiction->id }}" @checked($jurisdiction->is_active)>
                                                    <label class="form-check-label" for="jurActive{{ $jurisdiction->id }}">A</label>
                                                </div>
                                            </div>
                                            <div class="col-md-1 text-end"><button class="btn btn-primary btn-sm text-white">Save</button></div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">No jurisdictions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card tc-card">
                <div class="card-header bg-white"><strong>Withholding Rules</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Jurisdiction</th>
                                <th>Name</th>
                                <th>Counterparty</th>
                                <th>Rate</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($withholdingRules as $rule)
                                <tr>
                                    <td>{{ $rule->jurisdiction?->name }}</td>
                                    <td>{{ $rule->name }}</td>
                                    <td>{{ ucfirst($rule->counterparty_type) }}</td>
                                    <td>{{ number_format((float)$rule->rate, 4) }}%</td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#editRule{{ $rule->id }}">Edit</button>
                                        <form method="POST" action="{{ route('compliance.tax-center.withholding.destroy', $rule->id) }}" class="d-inline" onsubmit="return confirm('Delete this withholding rule?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="editRule{{ $rule->id }}">
                                    <td colspan="5" class="bg-light">
                                        <form method="POST" action="{{ route('compliance.tax-center.withholding.update', $rule->id) }}" class="row g-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-md-3">
                                                <select name="tax_jurisdiction_id" class="form-select form-select-sm" required>
                                                    @foreach($jurisdictions as $jurisdiction)
                                                        <option value="{{ $jurisdiction->id }}" @selected($rule->tax_jurisdiction_id == $jurisdiction->id)>{{ $jurisdiction->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2"><input type="text" name="name" class="form-control form-control-sm" value="{{ $rule->name }}" required></div>
                                            <div class="col-md-2">
                                                <select name="counterparty_type" class="form-select form-select-sm" required>
                                                    <option value="vendor" @selected($rule->counterparty_type === 'vendor')>Supplier</option>
                                                    <option value="customer" @selected($rule->counterparty_type === 'customer')>Customer</option>
                                                    <option value="contractor" @selected($rule->counterparty_type === 'contractor')>Contractor</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2"><input type="number" step="0.0001" min="0" max="100" name="rate" class="form-control form-control-sm" value="{{ $rule->rate }}" required></div>
                                            <div class="col-md-2"><input type="number" step="0.01" min="0" name="threshold_amount" class="form-control form-control-sm" value="{{ $rule->threshold_amount }}"></div>
                                            <div class="col-md-1 d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="ruleActive{{ $rule->id }}" @checked($rule->is_active)>
                                                    <label class="form-check-label" for="ruleActive{{ $rule->id }}">A</label>
                                                </div>
                                            </div>
                                            <div class="col-md-10"><input type="text" name="account_code" class="form-control form-control-sm" value="{{ $rule->account_code }}" placeholder="Account code"></div>
                                            <div class="col-md-2 text-end"><button class="btn btn-primary btn-sm text-white">Update</button></div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">No withholding rules yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
