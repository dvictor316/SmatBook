@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Maintenance Log</h5>
                    <p class="text-muted mb-0">Detailed service history for the selected fixed asset.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('assets.maintenance.edit', $maintenanceLog) }}" class="btn btn-primary">Edit Log</a>
                    <a href="{{ route('assets.maintenance.index') }}" class="btn btn-outline-primary">Back to Logs</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl-4 col-md-6"><strong>Asset:</strong> {{ $maintenanceLog->asset->name ?? '—' }}</div>
                    <div class="col-xl-4 col-md-6"><strong>Type:</strong> {{ ucfirst($maintenanceLog->maintenance_type) }}</div>
                    <div class="col-xl-4 col-md-6"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $maintenanceLog->status)) }}</div>
                    <div class="col-xl-4 col-md-6"><strong>Date:</strong> {{ $maintenanceLog->maintenance_date->format('d M Y') }}</div>
                    <div class="col-xl-4 col-md-6"><strong>Next Date:</strong> {{ $maintenanceLog->next_maintenance_date?->format('d M Y') ?: '—' }}</div>
                    <div class="col-xl-4 col-md-6"><strong>Cost:</strong> {{ number_format((float) ($maintenanceLog->cost ?? 0), 2) }}</div>
                    <div class="col-12"><strong>Description:</strong> {{ $maintenanceLog->description }}</div>
                    <div class="col-lg-6"><strong>Findings:</strong> {{ $maintenanceLog->findings ?: '—' }}</div>
                    <div class="col-lg-6"><strong>Parts Replaced:</strong> {{ $maintenanceLog->parts_replaced ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
