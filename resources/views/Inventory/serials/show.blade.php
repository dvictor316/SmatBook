@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Serial {{ $serialNumber->serial_number }}</h5>
                    <p class="text-muted mb-0">Serialized inventory detail record.</p>
                </div>
                <a href="{{ route('inventory.serials.index') }}" class="btn btn-outline-primary">Back to Serials</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th width="30%">Serial Number</th><td>{{ $serialNumber->serial_number }}</td></tr>
                    <tr><th>Product</th><td>{{ $serialNumber->product?->name ?? 'N/A' }}</td></tr>
                    <tr><th>Lot</th><td>{{ $serialNumber->lot?->lot_number ?? 'N/A' }}</td></tr>
                    <tr><th>Status</th><td>{{ ucfirst($serialNumber->status ?? 'unknown') }}</td></tr>
                    <tr><th>Sold Date</th><td>{{ $serialNumber->sold_date ? $serialNumber->sold_date->format('d M Y') : 'N/A' }}</td></tr>
                    <tr><th>Warranty Expiry</th><td>{{ $serialNumber->warranty_expiry ? $serialNumber->warranty_expiry->format('d M Y') : 'N/A' }}</td></tr>
                    <tr><th>Notes</th><td>{{ $serialNumber->notes ?: 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
