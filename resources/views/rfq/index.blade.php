@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Request for Quotation</h5>
                    <p class="text-muted mb-0">Manage vendor quotation requests within the active company and branch.</p>
                </div>
                <a href="{{ route('rfq.create') }}" class="btn btn-primary">New RFQ</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>RFQ Number</th>
                                <th>Title</th>
                                <th>Required Date</th>
                                <th>Suppliers</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rfqs as $rfq)
                                <tr>
                                    <td>{{ $rfq->rfq_number }}</td>
                                    <td>{{ $rfq->title }}</td>
                                    <td>{{ $rfq->required_date ? $rfq->required_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $rfq->rfqSuppliers->count() }}</td>
                                    <td>{{ ucfirst($rfq->status ?? 'draft') }}</td>
                                    <td>{{ $rfq->created_at ? $rfq->created_at->format('d M Y') : 'N/A' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('rfq.show', $rfq) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @if($rfq->status === 'draft')
                                            <a href="{{ route('rfq.edit', $rfq) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        @endif
                                        @if(in_array($rfq->status, ['received', 'awarded']))
                                            <a href="{{ route('rfq.compare', $rfq) }}" class="btn btn-sm btn-outline-info">Compare</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No RFQs found yet.</td>
                                </tr>
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
</div>
@endsection
