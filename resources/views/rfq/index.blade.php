@extends('layout.app')

@section('title', 'Request for Quotation')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Request for Quotation</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">RFQ</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('rfq.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New RFQ
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>RFQ #</th>
                            <th>Title</th>
                            <th>Required Date</th>
                            <th>Suppliers</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rfqs as $rfq)
                            <tr>
                                <td><a href="{{ route('rfq.show', $rfq) }}">{{ $rfq->rfq_number }}</a></td>
                                <td>{{ $rfq->title }}</td>
                                <td>{{ $rfq->required_date ? $rfq->required_date->format('d M Y') : '—' }}</td>
                                <td>{{ $rfq->rfqSuppliers->count() }}</td>
                                <td>
                                    <span class="badge bg-{{ match($rfq->status) {
                                        'draft' => 'secondary',
                                        'sent' => 'primary',
                                        'received' => 'info',
                                        'awarded' => 'success',
                                        default => 'secondary'
                                    } }}">
                                        {{ ucfirst($rfq->status) }}
                                    </span>
                                </td>
                                <td>{{ $rfq->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('rfq.show', $rfq) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($rfq->status === 'draft')
                                        <a href="{{ route('rfq.edit', $rfq) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    @endif
                                    @if(in_array($rfq->status, ['received']))
                                        <a href="{{ route('rfq.compare', $rfq) }}" class="btn btn-sm btn-outline-info me-1">Compare</a>
                                    @endif
                                    @if($rfq->status === 'draft')
                                        <form action="{{ route('rfq.destroy', $rfq) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this RFQ?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No RFQs found. <a href="{{ route('rfq.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rfqs->hasPages())
            <div class="card-footer">{{ $rfqs->links() }}</div>
        @endif
    </div>
</div>
@endsection
