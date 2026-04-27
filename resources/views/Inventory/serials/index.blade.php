@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5 class="mb-1">Serial Numbers</h5>
                <p class="text-muted mb-0">Track serialized inventory records in the current workspace.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('inventory.serials.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select">
                            <option value="">All tracked products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach(['available', 'sold', 'defective', 'returned'] as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
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
                                <th>Serial Number</th>
                                <th>Product</th>
                                <th>Lot</th>
                                <th>Status</th>
                                <th>Warranty Expiry</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($serials as $serial)
                                <tr>
                                    <td>{{ $serial->serial_number }}</td>
                                    <td>{{ $serial->product?->name ?? 'N/A' }}</td>
                                    <td>{{ $serial->lot?->lot_number ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($serial->status ?? 'unknown') }}</td>
                                    <td>{{ $serial->warranty_expiry ? $serial->warranty_expiry->format('d M Y') : 'N/A' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('inventory.serials.show', $serial) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No serial numbers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($serials->hasPages())
                <div class="card-footer">{{ $serials->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
