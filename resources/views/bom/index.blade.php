@extends('layout.app')

@section('title', 'Bill of Materials')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Bill of Materials</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">BOM</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('bom.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New BOM
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
                            <th>BOM #</th><th>Product</th><th>Version</th><th>Qty Produced</th><th>Components</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($boms as $bom)
                            <tr>
                                <td>{{ $bom->bom_number }}</td>
                                <td>{{ $bom->product->name ?? '—' }}</td>
                                <td>{{ ucfirst($bom->bom_type ?? 'standard') }}</td>
                                <td>{{ number_format((float) $bom->output_quantity, 2) }}</td>
                                <td>{{ $bom->items->count() }}</td>
                                <td>
                                    <span class="badge bg-{{ match($bom->status) {
                                        'active' => 'success', 'draft' => 'secondary', 'inactive' => 'danger', default => 'secondary'
                                    } }}">{{ ucfirst($bom->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('bom.show', $bom) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <form action="{{ route('bom.destroy', $bom) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this BOM?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No BOMs found. <a href="{{ route('bom.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($boms->hasPages())
            <div class="card-footer">{{ $boms->links() }}</div>
        @endif
    </div>
</div>
@endsection
