@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Lot {{ $productLot->lot_number }}</h5>
                    <p class="text-muted mb-0">Lot detail and quantity control.</p>
                </div>
                <a href="{{ route('inventory.lots.index') }}" class="btn btn-outline-primary">Back to Lots</a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Lot Details</h6></div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr><th>Product</th><td>{{ $productLot->product?->name ?? 'N/A' }}</td></tr>
                            <tr><th>Lot Number</th><td>{{ $productLot->lot_number }}</td></tr>
                            <tr><th>Batch Number</th><td>{{ $productLot->batch_number ?: 'N/A' }}</td></tr>
                            <tr><th>Manufacture Date</th><td>{{ $productLot->manufacture_date ? $productLot->manufacture_date->format('d M Y') : 'N/A' }}</td></tr>
                            <tr><th>Expiry Date</th><td>{{ $productLot->expiry_date ? $productLot->expiry_date->format('d M Y') : 'N/A' }}</td></tr>
                            <tr><th>Received Qty</th><td>{{ number_format((float) $productLot->quantity_received, 2) }}</td></tr>
                            <tr><th>Available Qty</th><td>{{ number_format((float) $productLot->quantity_available, 2) }}</td></tr>
                            <tr><th>Used Qty</th><td>{{ number_format((float) $productLot->quantity_used, 2) }}</td></tr>
                            <tr><th>Status</th><td>{{ ucfirst($productLot->status ?? 'unknown') }}</td></tr>
                            <tr><th>Notes</th><td>{{ $productLot->notes ?: 'N/A' }}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Adjust Quantity</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('inventory.lots.adjust', $productLot) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">Adjustment</label>
                                <input type="number" step="0.01" name="adjustment" class="form-control" placeholder="Use negative to reduce stock" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Post Adjustment</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Linked Serial Numbers</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial Number</th>
                                        <th>Status</th>
                                        <th>Warranty Expiry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($productLot->serialNumbers as $serial)
                                        <tr>
                                            <td>{{ $serial->serial_number }}</td>
                                            <td>{{ ucfirst($serial->status ?? 'unknown') }}</td>
                                            <td>{{ $serial->warranty_expiry ? $serial->warranty_expiry->format('d M Y') : 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No serial numbers linked to this lot yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
