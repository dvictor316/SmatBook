@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Stock Valuation</h5>
                    <p class="text-muted mb-0">Review current stock value using the available inventory cost basis.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('inventory.stock-valuation') }}" class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">Valuation Method</label>
                        <select name="method" class="form-select">
                            <option value="weighted_avg" @selected($method === 'weighted_avg')>Weighted Average</option>
                            <option value="fifo" @selected($method === 'fifo')>FIFO</option>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">As Of Date</label>
                        <input type="date" name="as_of" class="form-control" value="{{ $asOf }}">
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Products With Stock</p>
                        <h4 class="mb-0">{{ $rows->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Units On Hand</p>
                        <h4 class="mb-0">{{ number_format((float) $rows->sum('quantity'), 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Stock Value</p>
                        <h4 class="mb-0">{{ number_format((float) $grandTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row['product']->name ?? 'N/A' }}</td>
                                    <td>{{ $row['product']->sku ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format((float) $row['quantity'], 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $row['unit_cost'], 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $row['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No stocked products found for this workspace.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
