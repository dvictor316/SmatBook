@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Requisition {{ $purchaseRequisition->requisition_number }}</h5>
                    <p class="text-muted mb-0">Review requisition details and approval status.</p>
                </div>
                <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr><th>Request Date</th><td>{{ $purchaseRequisition->request_date ? $purchaseRequisition->request_date->format('d M Y') : 'N/A' }}</td></tr>
                            <tr><th>Required Date</th><td>{{ $purchaseRequisition->required_date ? $purchaseRequisition->required_date->format('d M Y') : 'N/A' }}</td></tr>
                            <tr><th>Priority</th><td>{{ ucfirst($purchaseRequisition->priority ?? 'normal') }}</td></tr>
                            <tr><th>Status</th><td>{{ ucfirst($purchaseRequisition->status ?? 'submitted') }}</td></tr>
                            <tr><th>Department</th><td>{{ $purchaseRequisition->department?->name ?? 'N/A' }}</td></tr>
                            <tr><th>Cost Center</th><td>{{ $purchaseRequisition->costCenter?->name ?? 'N/A' }}</td></tr>
                            <tr><th>Requested By</th><td>{{ $purchaseRequisition->requestedBy?->name ?? 'N/A' }}</td></tr>
                            <tr><th>Approved By</th><td>{{ $purchaseRequisition->approvedBy?->name ?? 'N/A' }}</td></tr>
                            <tr><th>Justification</th><td>{{ $purchaseRequisition->justification ?: 'N/A' }}</td></tr>
                            <tr><th>Rejection Reason</th><td>{{ $purchaseRequisition->rejection_reason ?: 'N/A' }}</td></tr>
                        </table>
                    </div>
                </div>

                @if($purchaseRequisition->status === 'submitted')
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex gap-2 mb-3">
                                <form action="{{ route('purchase-requisitions.approve', $purchaseRequisition) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Approve</button>
                                </form>
                            </div>
                            <form action="{{ route('purchase-requisitions.reject', $purchaseRequisition) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Rejection Reason</label>
                                    <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-danger">Reject</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Requested Items</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Estimated Unit Price</th>
                                        <th>Estimated Total</th>
                                        <th>Specification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($purchaseRequisition->items as $item)
                                        <tr>
                                            <td>{{ $item->product_name ?: ($item->product?->name ?? 'N/A') }}</td>
                                            <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                            <td>{{ $item->unit ?: 'N/A' }}</td>
                                            <td>{{ number_format((float) ($item->estimated_unit_price ?? $item->estimated_unit_cost ?? 0), 2) }}</td>
                                            <td>{{ number_format((float) ($item->estimated_total ?? $item->total_cost ?? 0), 2) }}</td>
                                            <td>{{ $item->specification ?? $item->specifications ?? 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No items on this requisition.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
