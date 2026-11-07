<?php $page = 'stock-movements'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-3 no-print">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h4 class="fw-bold mb-1">Stock Movement Report</h4>
                    <p class="text-muted mb-0">Auditable inventory changes for the selected period and branch.</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm shadow-sm">
                        <button onclick="window.print()" class="btn btn-white border"><i class="feather-printer me-1"></i>Print</button>
                        <a href="{{ route('reports.stock-movements', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-white border text-success">
                            <i class="feather-download me-1"></i>CSV / Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Stock Movement Report',
            'periodLabel' => $fromDate . ' to ' . $toDate,
        ])

        <div class="card border shadow-none mb-3 no-print">
            <div class="card-body">
                <form action="{{ route('reports.stock-movements') }}" method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">From date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">To date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Product</label>
                            <select name="product_id" class="form-select form-select-sm">
                                <option value="">All products</option>
                                @foreach($products as $productOption)
                                    <option value="{{ $productOption->id }}" @selected((string) $productId === (string) $productOption->id)>
                                        {{ $productOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Movement</label>
                            <select name="movement_type" class="form-select form-select-sm">
                                @foreach(['all' => 'All movements', 'in' => 'Stock in', 'out' => 'Stock out', 'damage' => 'Damage', 'status' => 'Order status'] as $value => $label)
                                    <option value="{{ $value }}" @selected($movementType === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Search</label>
                            <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Product, SKU, reference">
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-primary btn-sm">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card border shadow-none h-100"><div class="card-body">
                    <div class="text-muted small text-uppercase">Movement records</div>
                    <div class="fs-4 fw-bold">{{ number_format((int) ($summary->movement_count ?? 0)) }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none h-100"><div class="card-body">
                    <div class="text-muted small text-uppercase">Products affected</div>
                    <div class="fs-4 fw-bold">{{ number_format((int) ($summary->product_count ?? 0)) }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none h-100"><div class="card-body">
                    <div class="text-muted small text-uppercase">Branch</div>
                    <div class="fs-5 fw-bold">{{ $activeBranch['name'] ?? 'Workspace default' }}</div>
                </div></div>
            </div>
        </div>

        @if($productId)
            @php
                $selectedProduct = $products->firstWhere('id', (int) $productId);
                $selectedUnit = $selectedProduct?->base_unit_name ?: $selectedProduct?->unit_type ?: 'pcs';
            @endphp
            <div class="row g-2 mb-3">
                <div class="col-md-4"><div class="card border shadow-none"><div class="card-body">
                    <div class="text-muted small">Stock received</div>
                    <strong>{{ \App\Support\CompactNumber::format((float) ($summary->total_in ?? 0), 2) }} {{ $selectedUnit }}</strong>
                </div></div></div>
                <div class="col-md-4"><div class="card border shadow-none"><div class="card-body">
                    <div class="text-muted small">Stock issued</div>
                    <strong>{{ \App\Support\CompactNumber::format((float) ($summary->total_out ?? 0), 2) }} {{ $selectedUnit }}</strong>
                </div></div></div>
                <div class="col-md-4"><div class="card border shadow-none"><div class="card-body">
                    <div class="text-muted small">Damaged / written off</div>
                    <strong>{{ \App\Support\CompactNumber::format((float) ($summary->total_damaged ?? 0), 2) }} {{ $selectedUnit }}</strong>
                </div></div></div>
            </div>
        @endif

        <div class="card border shadow-none">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Branch</th>
                            <th>Movement</th>
                            <th>Reference / Details</th>
                            <th class="text-end">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                            @php
                                $type = strtolower((string) $movement->type);
                                $isIn = in_array($type, ['in', 'stock in'], true);
                                $isOut = in_array($type, ['out', 'stock out', 'damage', 'damaged', 'waste', 'spoilage', 'write_off'], true);
                                $quantity = (float) $movement->quantity;
                                $unit = $movement->base_unit_name ?: $movement->unit_type ?: 'pcs';
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($movement->created_at)->format('d M Y, H:i') }}</td>
                                <td><a class="fw-semibold text-dark" href="{{ route('inventory.history', $movement->product_id) }}">{{ $movement->product_name }}</a><div class="small text-muted">{{ $movement->sku }}</div></td>
                                <td>{{ $movement->branch_name ?: 'Workspace' }}</td>
                                <td>
                                    @if($type === 'status')
                                        <span class="badge bg-light text-primary">Order status</span>
                                    @elseif($isIn)
                                        <span class="badge bg-light text-success">Stock in</span>
                                    @elseif(in_array($type, ['damage', 'damaged', 'waste', 'spoilage', 'write_off'], true))
                                        <span class="badge bg-light text-warning">Damage</span>
                                    @else
                                        <span class="badge bg-light text-danger">Stock out</span>
                                    @endif
                                </td>
                                <td>{{ $movement->reference ?: 'Inventory adjustment' }}@if($movement->remarks)<div class="small text-muted">{{ $movement->remarks }}</div>@endif</td>
                                <td class="text-end text-nowrap fw-semibold {{ $isIn ? 'text-success' : ($isOut ? 'text-danger' : 'text-muted') }}">
                                    @if($quantity > 0)
                                        {{ $isIn ? '+' : '-' }}{{ \App\Support\CompactNumber::format($quantity, 2) }} {{ $unit }}
                                    @else
                                        No quantity change
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No stock movements found for these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $movements->links() }}</div>
        </div>
        <p class="small text-muted mt-2">Quantities are shown in each product’s base stock unit. Order status entries are informational and do not change stock.</p>
    </div>
</div>
@endsection
