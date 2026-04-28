@extends('layout.app')

@section('title', 'BOM Details')

@section('content')
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">{{ $bom->bom_number }}</h3></div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Product:</strong> {{ $bom->product->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Output Quantity:</strong> {{ number_format((float) $bom->output_quantity, 2) }} {{ $bom->unit }}</div>
                <div class="col-md-4"><strong>Standard Cost:</strong> {{ number_format((float) $bom->standard_cost, 2) }}</div>
                <div class="col-12"><strong>Instructions:</strong> {{ $bom->instructions ?: 'None' }}</div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Components</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Component</th><th>Qty</th><th>Unit</th><th>Unit Cost</th><th>Scrap %</th></tr></thead>
                    <tbody>
                        @foreach($bom->items as $item)
                            <tr>
                                <td>{{ $item->component_name }}</td>
                                <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                <td>{{ $item->unit ?: '—' }}</td>
                                <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                                <td>{{ number_format((float) $item->scrap_percentage, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
