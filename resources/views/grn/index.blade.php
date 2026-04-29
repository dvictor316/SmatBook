@extends('layout.mainlayout')

@section('title', 'Goods Received Notes')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Goods Received Notes</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">GRN</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('grn.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New GRN
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
                            <th>GRN #</th><th>Supplier</th><th>PO #</th><th>Received Date</th><th>Received By</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grns as $grn)
                            <tr>
                                <td><a href="{{ route('grn.show', $grn) }}">{{ $grn->grn_number }}</a></td>
                                <td>{{ $grn->supplier?->name ?? '—' }}</td>
                                <td>{{ $grn->purchaseOrder?->purchase_no ?? ($grn->purchase_order_id ?? '—') }}</td>
                                <td>{{ $grn->received_date->format('d M Y') }}</td>
                                <td>{{ $grn->createdBy?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($grn->status) {
                                        'received' => 'success', 'pending' => 'warning', 'complete' => 'success', 'partial' => 'info', 'rejected' => 'danger', default => 'secondary'
                                    } }}">{{ ucfirst($grn->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('grn.show', $grn) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($grn->status === 'pending' || $grn->status === 'draft')
                                        <a href="{{ route('grn.edit', $grn) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                        <form action="{{ route('grn.destroy', $grn) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this GRN?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No GRNs found. <a href="{{ route('grn.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($grns->hasPages())
            <div class="card-footer">{{ $grns->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
