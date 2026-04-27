@extends('layout.app')

@section('title', 'Stock Valuation')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Stock Valuation</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Stock Valuation</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Valuation Method</label>
                    <select name="method" class="form-select form-select-sm">
                        <option value="weighted_avg" {{ request('method','weighted_avg') === 'weighted_avg' ? 'selected' : '' }}>Weighted Average</option>
                        <option value="fifo" {{ request('method') === 'fifo' ? 'selected' : '' }}>FIFO</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">As of Date</label>
                    <input type="date" name="as_of" class="form-control form-control-sm" value="{{ request('as_of', date('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="text-white-50 small mb-1">Total Stock Value</div>
                    <div class="fs-4 fw-bold">{{ number_format(collect($valuations ?? [])->sum('total_value'), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-success text-white">
                <div class="card-body">
                    <div class="text-white-50 small mb-1">Products with Stock</div>
                    <div class="fs-4 fw-bold">{{ collect($valuations ?? [])->where('quantity', '>', 0)->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-secondary text-white">
                <div class="card-body">
                    <div class="text-white-50 small mb-1">Method</div>
                    <div class="fs-4 fw-bold">{{ strtoupper(str_replace('_', ' ', request('method', 'weighted_avg'))) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th><th>SKU</th><th class="text-end">Qty on Hand</th><th class="text-end">Unit Cost</th><th class="text-end">Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($valuations ?? [] as $item)
                            <tr>
                                <td>{{ $item['product_name'] }}</td>
                                <td>{{ $item['sku'] ?? '—' }}</td>
                                <td class="text-end">{{ number_format($item['quantity'], 2) }}</td>
                                <td class="text-end">{{ number_format($item['unit_cost'], 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($item['total_value'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No stock data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
