@extends('layout.app')

@section('title', 'Serial Numbers')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Serial Numbers</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Serial Numbers</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('inventory.serials.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> Add Serial
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Serial #</th><th>Product</th><th>Lot</th><th>Status</th><th>Sold To</th><th>Warranty Expiry</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serials as $serial)
                            <tr>
                                <td>{{ $serial->serial_number }}</td>
                                <td>{{ $serial->product->name ?? '—' }}</td>
                                <td>{{ $serial->lot->lot_number ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($serial->status) {
                                        'available' => 'success', 'sold' => 'primary', 'defective' => 'danger', 'returned' => 'warning', default => 'secondary'
                                    } }}">{{ ucfirst($serial->status) }}</span>
                                </td>
                                <td>{{ $serial->soldToCustomer->name ?? '—' }}</td>
                                <td>{{ $serial->warranty_expiry ? $serial->warranty_expiry->format('d M Y') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.serials.show', $serial) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <a href="{{ route('inventory.serials.edit', $serial) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('inventory.serials.destroy', $serial) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this serial?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No serial numbers found.</td></tr>
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
@endsection
