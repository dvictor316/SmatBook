@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Purchase Requisitions</h5>
                    <p class="text-muted mb-0">Internal requests for purchasing, filtered to the current company.</p>
                </div>
                <a href="{{ route('purchase-requisitions.create') }}" class="btn btn-primary">New Requisition</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Request Date</th>
                                <th>Required Date</th>
                                <th>Department</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Requested By</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requisitions as $requisition)
                                <tr>
                                    <td>{{ $requisition->requisition_number }}</td>
                                    <td>{{ $requisition->request_date ? $requisition->request_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $requisition->required_date ? $requisition->required_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $requisition->department?->name ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($requisition->priority ?? 'normal') }}</td>
                                    <td>{{ ucfirst($requisition->status ?? 'submitted') }}</td>
                                    <td>{{ $requisition->requestedBy?->name ?? 'N/A' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('purchase-requisitions.show', $requisition) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No purchase requisitions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($requisitions->hasPages())
                <div class="card-footer">{{ $requisitions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
