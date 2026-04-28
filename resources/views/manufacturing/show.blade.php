@extends('layout.app')

@section('title', 'Manufacturing Order Details')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">{{ $manufacturingOrder->mo_number }}</h3></div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Product:</strong> {{ $manufacturingOrder->product->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Planned Quantity:</strong> {{ number_format((float) $manufacturingOrder->planned_quantity, 2) }}</div>
                <div class="col-md-4"><strong>Produced Quantity:</strong> {{ number_format((float) $manufacturingOrder->produced_quantity, 2) }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $manufacturingOrder->status)) }}</div>
                <div class="col-md-4"><strong>Planned Start:</strong> {{ $manufacturingOrder->planned_start_date?->format('d M Y') ?: '—' }}</div>
                <div class="col-md-4"><strong>Planned End:</strong> {{ $manufacturingOrder->planned_end_date?->format('d M Y') ?: '—' }}</div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Required Materials</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Item</th><th>Required Qty</th><th>Consumed Qty</th><th>Unit</th><th>Unit Cost</th></tr></thead>
                    <tbody>
                        @foreach($manufacturingOrder->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ number_format((float) $item->required_quantity, 2) }}</td>
                                <td>{{ number_format((float) $item->consumed_quantity, 2) }}</td>
                                <td>{{ $item->unit ?: '—' }}</td>
                                <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
