@extends('layout.app')

@section('title', 'Barcode Management')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Barcode Management</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Barcodes</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('inventory.barcodes.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> Add Barcode
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
                            <th>Barcode</th><th>Product</th><th>Type</th><th>Primary</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($barcodes as $barcode)
                            <tr>
                                <td><strong>{{ $barcode->barcode }}</strong></td>
                                <td>{{ $barcode->product->name ?? '—' }}</td>
                                <td>{{ strtoupper($barcode->barcode_type ?? 'EAN13') }}</td>
                                <td>
                                    @if($barcode->is_primary)
                                        <span class="badge bg-success">Primary</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.barcodes.edit', $barcode) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('inventory.barcodes.destroy', $barcode) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this barcode?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No barcodes found. <a href="{{ route('inventory.barcodes.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($barcodes->hasPages())
            <div class="card-footer">{{ $barcodes->links() }}</div>
        @endif
    </div>
</div>
@endsection
