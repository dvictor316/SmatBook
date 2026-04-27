@extends('layout.app')

@section('title', 'PR #' . $requisition->pr_number)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Purchase Requisition #{{ $requisition->pr_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase-requisitions.index') }}">Requisitions</a></li>
                    <li class="breadcrumb-item active">#{{ $requisition->pr_number }}</li>
                </ul>
            </div>
            <div class="col-auto d-flex gap-2">
                @if($requisition->status === 'pending')
                    <form action="{{ route('purchase-requisitions.approve', $requisition) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this requisition?')">
                            <i class="fe fe-check me-1"></i> Approve
                        </button>
                    </form>
                    <form action="{{ route('purchase-requisitions.reject', $requisition) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this requisition?')">
                            <i class="fe fe-x me-1"></i> Reject
                        </button>
                    </form>
                @endif
                @if(in_array($requisition->status, ['pending','draft']))
                    <a href="{{ route('purchase-requisitions.edit', $requisition) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                @endif
                <form action="{{ route('purchase-requisitions.destroy', $requisition) }}" method="POST" onsubmit="return confirm('Delete this requisition?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Requisition Details</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="45%">PR Number</th><td>{{ $requisition->pr_number }}</td></tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @php
                                    $sc = match($requisition->status) {
                                        'approved' => 'success', 'rejected' => 'danger',
                                        'pending' => 'warning', 'draft' => 'secondary',
                                        'converted' => 'info', default => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $sc }}">{{ ucfirst($requisition->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Priority</th>
                            <td>
                                @php
                                    $pc = match($requisition->priority) { 'critical' => 'danger', 'urgent' => 'warning', 'low' => 'secondary', default => 'info' };
                                @endphp
                                <span class="badge bg-{{ $pc }}">{{ ucfirst($requisition->priority) }}</span>
                            </td>
                        </tr>
                        <tr><th>Required By</th><td>{{ $requisition->required_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th>Department</th><td>{{ $requisition->department?->name ?? '—' }}</td></tr>
                        <tr><th>Cost Center</th><td>{{ $requisition->costCenter?->name ?? '—' }}</td></tr>
                        <tr><th>Requested By</th><td>{{ $requisition->requestedBy?->name ?? '—' }}</td></tr>
                        @if($requisition->approved_by)
                        <tr><th>Approved By</th><td>{{ $requisition->approvedBy?->name }}</td></tr>
                        @endif
                        @if($requisition->justification)
                        <tr><th>Justification</th><td>{{ $requisition->justification }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Items</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Est. Price</th>
                                    <th>Est. Total</th>
                                    <th>Specification</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotal = 0; @endphp
                                @foreach($requisition->items as $idx => $item)
                                @php $lineTotal = ($item->quantity ?? 0) * ($item->estimated_unit_price ?? 0); $grandTotal += $lineTotal; @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->unit ?? '—' }}</td>
                                    <td>{{ $item->estimated_unit_price ? number_format($item->estimated_unit_price, 2) : '—' }}</td>
                                    <td>{{ $lineTotal > 0 ? number_format($lineTotal, 2) : '—' }}</td>
                                    <td>{{ $item->specification ?? '—' }}</td>
                                </tr>
                                @endforeach
                                @if($grandTotal > 0)
                                <tr class="table-light fw-bold">
                                    <td colspan="5" class="text-end">Est. Grand Total:</td>
                                    <td>{{ number_format($grandTotal, 2) }}</td>
                                    <td></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
