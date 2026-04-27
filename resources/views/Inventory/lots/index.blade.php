@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Lot Tracking</h5>
                    <p class="text-muted mb-0">Review tracked inventory lots for the active branch and company.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('inventory.lots.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select">
                            <option value="">All tracked products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Lot Number</th>
                                <th>Batch</th>
                                <th>Product</th>
                                <th>Received</th>
                                <th>Available</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lots as $lot)
                                <tr>
                                    <td>{{ $lot->lot_number }}</td>
                                    <td>{{ $lot->batch_number ?: 'N/A' }}</td>
                                    <td>{{ $lot->product?->name ?? 'N/A' }}</td>
                                    <td>{{ number_format((float) $lot->quantity_received, 2) }}</td>
                                    <td>{{ number_format((float) $lot->quantity_available, 2) }}</td>
                                    <td>{{ $lot->expiry_date ? $lot->expiry_date->format('d M Y') : 'N/A' }}</td>
                                    <td><span class="badge bg-{{ $lot->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($lot->status ?? 'unknown') }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('inventory.lots.show', $lot) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No lots found for this workspace.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($lots->hasPages())
                <div class="card-footer">{{ $lots->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
