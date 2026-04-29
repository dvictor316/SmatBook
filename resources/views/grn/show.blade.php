@extends('layout.mainlayout')

@section('title', 'GRN #' . $grn->grn_number)

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">GRN #{{ $grn->grn_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('grn.index') }}">GRNs</a></li>
                    <li class="breadcrumb-item active">#{{ $grn->grn_number }}</li>
                </ul>
            </div>
            <div class="col-auto d-flex gap-2">
                <form action="{{ route('grn.destroy', $grn) }}" method="POST" onsubmit="return confirm('Delete this GRN?')">
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
                <div class="card-header"><h5 class="card-title mb-0">GRN Details</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="45%">GRN Number</th><td>{{ $grn->grn_number }}</td></tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @php $sc = match($grn->status ?? 'draft') { 'posted' => 'success', 'draft' => 'warning', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $sc }}">{{ ucfirst($grn->status ?? 'draft') }}</span>
                            </td>
                        </tr>
                        <tr><th>Supplier</th><td>{{ $grn->supplier?->name ?? '—' }}</td></tr>
                        <tr><th>PO Reference</th><td>{{ $grn->purchaseOrder?->purchase_no ?? ($grn->purchase_order_id ?? '—') }}</td></tr>
                        <tr><th>Received Date</th><td>{{ $grn->received_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th>Received By</th><td>{{ $grn->createdBy?->name ?? '—' }}</td></tr>
                        @if($grn->notes)
                        <tr><th>Notes</th><td>{{ $grn->notes }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-muted small">Total Items</div>
                            <div class="fs-5 fw-bold">{{ $grn->items->count() }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Total Qty Received</div>
                            <div class="fs-5 fw-bold">{{ number_format($grn->items->sum('received_quantity'), 2) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Total Value</div>
                            <div class="fs-5 fw-bold">{{ number_format($grn->items->sum(fn($i) => $i->received_quantity * ($i->unit_cost ?? 0)), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card mt-3">
        <div class="card-header"><h5 class="card-title mb-0">Received Items</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Ordered Qty</th>
                            <th>Received Qty</th>
                            <th>Unit Cost</th>
                            <th>Total Value</th>
                            <th>Lot #</th>
                            <th>Serial #</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotal = 0; @endphp
                        @foreach($grn->items as $idx => $item)
                        @php $lineVal = ($item->received_quantity ?? 0) * ($item->unit_cost ?? 0); $grandTotal += $lineVal; @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                {{ $item->product_name }}
                                @if($item->product)
                                    <br><small class="text-muted">{{ $item->product->sku ?? '' }}</small>
                                @endif
                            </td>
                            <td>{{ $item->ordered_quantity ? number_format($item->ordered_quantity, 2) : '—' }}</td>
                            <td><strong>{{ number_format($item->received_quantity, 2) }}</strong></td>
                            <td>{{ $item->unit_cost ? number_format($item->unit_cost, 2) : '—' }}</td>
                            <td>{{ $lineVal > 0 ? number_format($lineVal, 2) : '—' }}</td>
                            <td>{{ $item->lot_number ?? '—' }}</td>
                            <td>{{ $item->serial_number ?? '—' }}</td>
                            <td>{{ $item->expiry_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">Grand Total Value:</td>
                            <td>{{ number_format($grandTotal, 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
