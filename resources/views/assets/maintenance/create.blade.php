@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Log Asset Maintenance</h5>
                    <p class="text-muted mb-0">Capture scheduled, preventive, or corrective maintenance activity.</p>
                </div>
                <div>
                    <a href="{{ route('assets.maintenance.index') }}" class="btn btn-outline-primary">Back to Logs</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('assets.maintenance.store') }}" class="row g-3">
                    @csrf
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Asset</label>
                        <select name="fixed_asset_id" class="form-select" required>
                            <option value="">Select asset</option>
                            @foreach($assets as $asset)
                                <option value="{{ $asset->id }}" @selected((string) $assetId === (string) $asset->id)>{{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Maintenance Type</label>
                        <select name="maintenance_type" class="form-select" required>
                            @foreach(['preventive', 'corrective', 'inspection', 'upgrade', 'overhaul'] as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach(['scheduled', 'in_progress', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6"><label class="form-label">Maintenance Date</label><input type="date" name="maintenance_date" class="form-control" required></div>
                    <div class="col-xl-3 col-md-6"><label class="form-label">Next Maintenance Date</label><input type="date" name="next_maintenance_date" class="form-control"></div>
                    <div class="col-xl-3 col-md-6"><label class="form-label">Performed By</label><input type="text" name="performed_by" class="form-control"></div>
                    <div class="col-xl-3 col-md-6"><label class="form-label">Vendor</label><input type="text" name="vendor_name" class="form-control"></div>
                    <div class="col-xl-3 col-md-6"><label class="form-label">Cost</label><input type="number" step="0.01" min="0" name="cost" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
                    <div class="col-lg-6"><label class="form-label">Findings</label><textarea name="findings" class="form-control" rows="3"></textarea></div>
                    <div class="col-lg-6"><label class="form-label">Parts Replaced</label><textarea name="parts_replaced" class="form-control" rows="3"></textarea></div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary">Save Maintenance Log</button>
                        <a href="{{ route('assets.maintenance.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
