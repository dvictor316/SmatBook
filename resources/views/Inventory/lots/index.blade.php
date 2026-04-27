@extends('layout.app')

@section('title', 'Lot Tracking')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Lot Tracking</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Lots</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('inventory.lots.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Lot
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
                            <th>Lot #</th><th>Product</th><th>Qty Received</th><th>Qty Available</th><th>Mfg Date</th><th>Expiry Date</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lots as $lot)
                            <tr>
                                <td>{{ $lot->lot_number }}</td>
                                <td>{{ $lot->product->name ?? '—' }}</td>
                                <td>{{ $lot->quantity_received }}</td>
                                <td>{{ $lot->quantity_available }}</td>
                                <td>{{ $lot->manufacture_date ? $lot->manufacture_date->format('d M Y') : '—' }}</td>
                                <td>{{ $lot->expiry_date ? $lot->expiry_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $lot->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($lot->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.lots.show', $lot) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <a href="{{ route('inventory.lots.edit', $lot) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('inventory.lots.destroy', $lot) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this lot?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No lots found.</td></tr>
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
@endsection
