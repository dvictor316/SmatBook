@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Edit Maintenance Log</h5>
                    <p class="text-muted mb-0">Update the maintenance record without losing its asset history.</p>
                </div>
                <div>
                    <a href="{{ route('assets.maintenance.show', $maintenanceLog) }}" class="btn btn-outline-primary">View Log</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('assets.maintenance.update', $maintenanceLog) }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Asset</label>
                        <select class="form-select" disabled>
                            @foreach($assets as $asset)
                                <option @selected($asset->id === $maintenanceLog->fixed_asset_id)>{{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-4 col-md-6"><label class="form-label">Maintenance Date</label><input type="date" name="maintenance_date" class="form-control" value="{{ optional($maintenanceLog->maintenance_date)->format('Y-m-d') }}" required></div>
                    <div class="col-xl-4 col-md-6"><label class="form-label">Next Maintenance Date</label><input type="date" name="next_maintenance_date" class="form-control" value="{{ optional($maintenanceLog->next_maintenance_date)->format('Y-m-d') }}"></div>
                    <div class="col-xl-4 col-md-6"><label class="form-label">Performed By</label><input type="text" name="performed_by" class="form-control" value="{{ $maintenanceLog->performed_by }}"></div>
                    <div class="col-xl-4 col-md-6"><label class="form-label">Vendor</label><input type="text" name="vendor_name" class="form-control" value="{{ $maintenanceLog->vendor_name }}"></div>
                    <div class="col-xl-4 col-md-6"><label class="form-label">Cost</label><input type="number" step="0.01" min="0" name="cost" class="form-control" value="{{ $maintenanceLog->cost }}"></div>
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach(['scheduled', 'in_progress', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected($maintenanceLog->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" required>{{ $maintenanceLog->description }}</textarea></div>
                    <div class="col-lg-6"><label class="form-label">Findings</label><textarea name="findings" class="form-control" rows="3">{{ $maintenanceLog->findings }}</textarea></div>
                    <div class="col-lg-6"><label class="form-label">Parts Replaced</label><textarea name="parts_replaced" class="form-control" rows="3">{{ $maintenanceLog->parts_replaced }}</textarea></div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary">Update Log</button>
                        <a href="{{ route('assets.maintenance.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
