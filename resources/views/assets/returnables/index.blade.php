<?php $page = 'returnable-assets'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title') Returnable Assets @endslot
        @endcomponent

        <div class="row g-3 mb-4">
            @foreach([
                ['Asset Types', $summary['asset_types'], 'fa-boxes-stacked', '#2563eb'],
                ['Registered Units', $summary['total_units'], 'fa-layer-group', '#0f766e'],
                ['Units in Custody', $summary['issued_units'], 'fa-hand-holding', '#7c3aed'],
                ['Overdue Units', $summary['overdue_units'], 'fa-clock', '#dc2626'],
                ['Deposits Held', \App\Support\GeoCurrency::format($summary['deposit_held']), 'fa-vault', '#b77900'],
            ] as [$label, $value, $icon, $color])
                <div class="col-6 col-lg">
                    <div class="card h-100 border-0 shadow-sm" style="border-top:4px solid {{ $color }} !important;border-radius:6px;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div><small class="text-uppercase text-muted fw-bold">{{ $label }}</small><div class="fs-4 fw-bold text-dark mt-1">{{ $value }}</div></div>
                                <i class="fas {{ $icon }}" style="color:{{ $color }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h4 class="mb-1">Custody Register</h4>
                <p class="text-muted mb-0">Track crates, cylinders, pallets, devices, tools, and other assets expected back.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#issueAssetModal">
                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Issue Asset
                </button>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                    <i class="fas fa-plus me-1"></i> Add Asset
                </button>
            </div>
        </div>

        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-5"><input class="form-control" name="q" value="{{ $search }}" placeholder="Search code, name, serial number, or category"></div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'retired' => 'Retired'] as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto"><button class="btn btn-dark" type="submit"><i class="fas fa-filter me-1"></i> Filter</button></div>
            @if($search !== '' || $status !== '')<div class="col-md-auto"><a class="btn btn-light" href="{{ route('returnable-assets.index') }}">Clear</a></div>@endif
        </form>

        <div class="table-responsive border rounded mb-5">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Asset</th><th>Category / Serial</th><th>Total</th><th>Available</th><th>In Custody</th><th>Deposit</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr>
                            <td><strong>{{ $asset->name }}</strong><div class="small text-muted">{{ $asset->asset_code }}</div></td>
                            <td>{{ $asset->category ?: 'Uncategorised' }}<div class="small text-muted">{{ $asset->serial_number ?: 'No serial number' }}</div></td>
                            <td>{{ number_format($asset->total_quantity) }}</td>
                            <td class="fw-bold text-success">{{ number_format($asset->available_quantity) }}</td>
                            <td class="fw-bold text-primary">{{ number_format(max(0, $asset->total_quantity - $asset->available_quantity)) }}</td>
                            <td>{{ \App\Support\GeoCurrency::format((float) $asset->deposit_amount) }}</td>
                            <td><span class="badge {{ $asset->status === 'active' ? 'bg-success' : ($asset->status === 'retired' ? 'bg-secondary' : 'bg-warning text-dark') }}">{{ ucfirst($asset->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No returnable assets match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $assets->links() }}

        <div class="d-flex justify-content-between align-items-end mb-3 mt-5">
            <div><h4 class="mb-1">Open Custody</h4><p class="text-muted mb-0">Outstanding issues remain here until every unit is returned or recorded as damaged/lost.</p></div>
        </div>
        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Asset</th><th>Custodian</th><th>Issued / Due</th><th>Outstanding</th><th>Deposit Held</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($openIssues as $issue)
                        @php($overdue = $issue->due_on && $issue->due_on->isPast())
                        <tr class="{{ $overdue ? 'table-danger' : '' }}">
                            <td><strong>{{ $issue->asset?->name }}</strong><div class="small text-muted">{{ $issue->asset?->asset_code }}</div></td>
                            <td>{{ $issue->customer?->customer_name ?: $issue->custodian_name }}<div class="small text-muted">{{ $issue->custodian_phone ?: $issue->reference }}</div></td>
                            <td>{{ optional($issue->issued_on)->format('d M Y') }}<div class="small {{ $overdue ? 'text-danger fw-bold' : 'text-muted' }}">Due: {{ optional($issue->due_on)->format('d M Y') ?: 'Not set' }}</div></td>
                            <td><strong>{{ $issue->outstanding_quantity }}</strong> of {{ $issue->quantity }}</td>
                            <td>{{ \App\Support\GeoCurrency::format((float) $issue->deposit_held) }}</td>
                            <td class="text-end"><button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal" data-bs-target="#returnModal{{ $issue->id }}"><i class="fas fa-rotate-left me-1"></i> Record Return</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No assets are currently outstanding.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="{{ route('returnable-assets.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Add Returnable Asset</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label">Asset Code *</label><input class="form-control" name="asset_code" value="{{ old('asset_code') }}" required></div>
            <div class="col-md-8"><label class="form-label">Asset Name *</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
            <div class="col-md-6"><label class="form-label">Category</label><input class="form-control" name="category" value="{{ old('category') }}" placeholder="Crate, cylinder, pallet, device"></div>
            <div class="col-md-6"><label class="form-label">Serial Number</label><input class="form-control" name="serial_number" value="{{ old('serial_number') }}"></div>
            <div class="col-md-4"><label class="form-label">Quantity *</label><input class="form-control" type="number" min="1" name="total_quantity" value="{{ old('total_quantity', 1) }}" required></div>
            <div class="col-md-4"><label class="form-label">Deposit per Unit</label><input class="form-control" type="number" min="0" step="0.01" name="deposit_amount" value="{{ old('deposit_amount', 0) }}"></div>
            <div class="col-md-4"><label class="form-label">Replacement Cost</label><input class="form-control" type="number" min="0" step="0.01" name="replacement_cost" value="{{ old('replacement_cost', 0) }}"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea></div>
        </div></div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save Asset</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="issueAssetModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" id="issueAssetForm">@csrf
        <div class="modal-header"><h5 class="modal-title">Issue Asset to Custody</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-8"><label class="form-label">Asset *</label><select class="form-select" id="issueAssetSelect" required><option value="">Select available asset</option>@foreach($availableAssets as $asset)<option value="{{ $asset->id }}" data-action="{{ route('returnable-assets.issue', $asset) }}">{{ $asset->asset_code }} - {{ $asset->name }} ({{ $asset->available_quantity }} available)</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Quantity *</label><input class="form-control" type="number" min="1" name="quantity" required></div>
            <div class="col-md-6"><label class="form-label">Linked Customer</label><select class="form-select" name="customer_id"><option value="">No linked customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->customer_name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Custodian Name *</label><input class="form-control" name="custodian_name" required></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="custodian_phone"></div>
            <div class="col-md-4"><label class="form-label">Issued On *</label><input class="form-control" type="date" name="issued_on" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4"><label class="form-label">Due On</label><input class="form-control" type="date" name="due_on"></div>
            <div class="col-md-6"><label class="form-label">Deposit Held</label><input class="form-control" type="number" min="0" step="0.01" name="deposit_held" value="0"></div>
            <div class="col-md-6"><label class="form-label">Reference</label><input class="form-control" name="reference"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div></div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Record Issue</button></div>
    </form>
</div></div></div>

@foreach($openIssues as $issue)
<div class="modal fade" id="returnModal{{ $issue->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('returnable-assets.return', $issue) }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Return {{ $issue->asset?->name }}</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-6"><label class="form-label">Quantity *</label><input class="form-control" type="number" min="1" max="{{ $issue->outstanding_quantity }}" name="quantity" value="{{ $issue->outstanding_quantity }}" required></div>
            <div class="col-6"><label class="form-label">Return Date *</label><input class="form-control" type="date" min="{{ optional($issue->issued_on)->toDateString() }}" name="returned_on" value="{{ now()->toDateString() }}" required></div>
            <div class="col-12"><label class="form-label">Condition *</label><select class="form-select" name="condition" required><option value="good">Good - return to available stock</option><option value="damaged">Damaged - remove from service</option><option value="lost">Lost - record replacement exposure</option></select></div>
            <div class="col-12"><label class="form-label">Reference</label><input class="form-control" name="reference"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div></div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Confirm Return</button></div>
    </form>
</div></div></div>
@endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('issueAssetForm');
    const select = document.getElementById('issueAssetSelect');
    if (!form || !select) return;
    const syncAction = () => {
        const option = select.options[select.selectedIndex];
        form.action = option?.dataset?.action || '';
    };
    select.addEventListener('change', syncAction);
    form.addEventListener('submit', function (event) {
        syncAction();
        if (!form.action) event.preventDefault();
    });
});
</script>
@endpush
