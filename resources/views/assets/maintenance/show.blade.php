@extends('layout.app')

@section('title', 'Maintenance Log')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Maintenance Log</h3></div>
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Asset:</strong> {{ $maintenanceLog->asset->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Type:</strong> {{ ucfirst($maintenanceLog->maintenance_type) }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $maintenanceLog->status)) }}</div>
                <div class="col-md-4"><strong>Date:</strong> {{ $maintenanceLog->maintenance_date->format('d M Y') }}</div>
                <div class="col-md-4"><strong>Next Date:</strong> {{ $maintenanceLog->next_maintenance_date?->format('d M Y') ?: '—' }}</div>
                <div class="col-md-4"><strong>Cost:</strong> {{ number_format((float) ($maintenanceLog->cost ?? 0), 2) }}</div>
                <div class="col-12"><strong>Description:</strong> {{ $maintenanceLog->description }}</div>
                <div class="col-md-6"><strong>Findings:</strong> {{ $maintenanceLog->findings ?: '—' }}</div>
                <div class="col-md-6"><strong>Parts Replaced:</strong> {{ $maintenanceLog->parts_replaced ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
