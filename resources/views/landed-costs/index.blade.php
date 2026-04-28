@extends('layout.app')

@section('title', 'Landed Costs')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Landed Costs</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Landed Costs</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('landed-costs.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Landed Cost
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
                            <th>GRN</th><th>Cost Type</th><th>Description</th><th>Amount</th><th>Currency</th><th>Method</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($landedCosts as $lc)
                            <tr>
                                <td>{{ $lc->grn->grn_number ?? '—' }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($lc->cost_type)) }}</td>
                                <td>{{ $lc->description ?? '—' }}</td>
                                <td>{{ number_format($lc->amount, 2) }}</td>
                                <td>{{ $lc->currency ?? '—' }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($lc->allocation_method)) }}</td>
                                <td>
                                    <span class="badge bg-{{ $lc->status === 'allocated' ? 'success' : 'warning' }}">{{ ucfirst($lc->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('landed-costs.show', $lc) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($lc->status === 'pending')
                                        <form action="{{ route('landed-costs.allocate', $lc) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success me-1">Allocate</button>
                                        </form>
                                        <a href="{{ route('landed-costs.edit', $lc) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                        <form action="{{ route('landed-costs.destroy', $lc) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this landed cost?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No landed costs found. <a href="{{ route('landed-costs.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($landedCosts->hasPages())
            <div class="card-footer">{{ $landedCosts->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
