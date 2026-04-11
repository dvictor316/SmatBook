<?php $page = 'stock-report'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        
        {{-- Header & Multi-Export Actions --}}
        <div class="page-header mb-3 no-print">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="fw-bold mb-1 report-page-title">Warehouse Stock Report</h4>
                    <p class="text-muted mb-0 report-page-subtitle">Live stock on hand for the active branch warehouse.</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm shadow-sm">
                        <button onclick="window.print()" class="btn btn-white border report-action-btn">
                            <i class="feather-printer me-1"></i> Print
                        </button>
                        <button id="export_pdf" class="btn btn-white border text-danger report-action-btn">
                            <i class="feather-file-text me-1"></i> PDF
                        </button>
                        <button id="export_excel" class="btn btn-white border text-success report-action-btn">
                            <i class="feather-file me-1"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Warehouse Inventory Report',
            'periodLabel' => 'As At: ' . $toDate,
        ])

        {{-- Filters --}}
        <div class="card shadow-none border mb-3 no-print">
            <div class="card-body p-2">
                @php
                    $stockRouteName = \Illuminate\Support\Facades\Route::has('stock')
                        ? 'stock'
                        : (\Illuminate\Support\Facades\Route::has('stock-report') ? 'stock-report' : null);
                @endphp
                <form action="{{ $stockRouteName ? route($stockRouteName) : url('/stock-report') }}" method="GET">
                    <div class="row gx-2 align-items-end">
                        <div class="col-md-10">
                            <label class="report-filter-label">Product Filter</label>
                            <select name="product_id" class="form-select form-select-sm border-0 bg-light">
                                <option value="">All Inventory Items</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold report-filter-action">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @php
            $stockrows = collect($stockreports)->map(function ($report) {
                $row = is_array($report) ? $report : (array) $report;

                return [
                    'Product' => (string) ($row['Product'] ?? $row['product'] ?? $row['name'] ?? 'Unnamed Product'),
                    'Sku' => (string) ($row['Sku'] ?? $row['sku'] ?? ''),
                    'QtyOnHand' => (float) ($row['QtyOnHand'] ?? $row['qty_on_hand'] ?? $row['quantity'] ?? 0),
                    'PurchasePrice' => (float) ($row['PurchasePrice'] ?? $row['purchase_price'] ?? 0),
                    'SalesPrice' => (float) ($row['SalesPrice'] ?? $row['sales_price'] ?? 0),
                    'CostValue' => (float) ($row['CostValue'] ?? $row['cost_value'] ?? 0),
                    'SalesValue' => (float) ($row['SalesValue'] ?? $row['sales_value'] ?? 0),
                    'ReorderLevel' => (float) ($row['ReorderLevel'] ?? $row['reorder_level'] ?? 0),
                    'Status' => (string) ($row['Status'] ?? $row['status'] ?? 'In Stock'),
                ];
            });

            $totalUnits = $stockrows->sum('QtyOnHand');
            $totalCostValue = $stockrows->sum('CostValue');
            $totalSalesValue = $stockrows->sum('SalesValue');
            $lowStockCount = $stockrows->where('Status', 'Low Stock')->count();
        @endphp

        {{-- Summary Cards --}}
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 report-metric-card"><div class="card-body p-3">
                    <p class="text-muted mb-1 fw-bold uppercase report-metric-label">Products In Warehouse</p>
                    <h4 class="text-success fw-bold mb-0 report-metric-value">{{ number_format($stockrows->count()) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 report-metric-card"><div class="card-body p-3">
                    <p class="text-muted mb-1 fw-bold uppercase report-metric-label">Units On Hand</p>
                    <h4 class="text-danger fw-bold mb-0 report-metric-value">{{ number_format($totalUnits, 2) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-indigo mb-0 shadow-sm report-metric-card report-metric-card--emphasis">
                    <div class="card-body p-3 text-white">
                        <p class="text-white mb-1 fw-bold uppercase report-metric-label report-metric-label--light">Warehouse Cost Value</p>
                        <h4 class="text-white fw-bold mb-0 report-metric-value">₦{{ number_format($totalCostValue, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 report-metric-card"><div class="card-body p-3">
                    <p class="text-muted mb-1 fw-bold uppercase report-metric-label">Warehouse Selling Value</p>
                    <h4 class="text-primary fw-bold mb-0 report-metric-value">₦{{ number_format($totalSalesValue, 2) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 report-metric-card"><div class="card-body p-3">
                    <p class="text-muted mb-1 fw-bold uppercase report-metric-label">Low Stock Items</p>
                    <h4 class="text-warning fw-bold mb-0 report-metric-value">{{ number_format($lowStockCount) }}</h4>
                </div></div>
            </div>
        </div>

        {{-- Warehouse Table --}}
        <div class="card border shadow-none overflow-hidden">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="stockTable">
                    <thead>
                        <tr>
                            <th class="ps-3 py-2 text-muted">Product</th>
                            <th class="py-2 text-muted">SKU</th>
                            <th class="py-2 text-end text-muted">Qty On Hand</th>
                            <th class="py-2 text-end text-muted">Purchase Price</th>
                            <th class="py-2 text-end text-muted">Selling Price</th>
                            <th class="py-2 text-end text-muted">Cost Value</th>
                            <th class="py-2 text-end text-muted">Selling Value</th>
                            <th class="py-2 text-end text-muted">Reorder Level</th>
                            <th class="pe-3 py-2 text-end text-muted">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockrows as $report)
                        <tr class="accounting-row">
                            <td class="ps-3 py-2 fw-bold text-dark">{{ $report['Product'] }}</td>
                            <td class="py-2 text-muted">{{ $report['Sku'] ?: 'N/A' }}</td>
                            <td class="py-2 text-end fw-bold {{ $report['QtyOnHand'] <= 0 ? 'text-danger' : 'text-dark' }}">{{ number_format($report['QtyOnHand'], 2) }}</td>
                            <td class="py-2 text-end text-success">₦{{ number_format($report['PurchasePrice'], 2) }}</td>
                            <td class="py-2 text-end text-primary">₦{{ number_format($report['SalesPrice'], 2) }}</td>
                            <td class="py-2 text-end text-success">₦{{ number_format($report['CostValue'], 2) }}</td>
                            <td class="py-2 text-end text-primary">₦{{ number_format($report['SalesValue'], 2) }}</td>
                            <td class="py-2 text-end text-muted">{{ number_format($report['ReorderLevel'], 2) }}</td>
                            <td class="pe-3 py-2 text-end fw-bold {{ $report['Status'] === 'Out of Stock' ? 'text-danger' : ($report['Status'] === 'Low Stock' ? 'text-warning' : 'text-indigo') }}">
                                {{ $report['Status'] }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted">No stock records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-indigo { background: #1b2559 !important; }
    .text-indigo { color: #1b2559 !important; }
    .accounting-row:hover { background-color: #f8faff !important; cursor: pointer; }
    .report-page-title {
        color: #102a5a;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
    }
    .report-page-subtitle {
        font-size: 0.95rem;
    }
    .report-action-btn {
        font-size: 0.82rem;
    }
    .report-filter-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #102a5a;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .report-filter-action {
        min-height: 38px;
    }
    .report-metric-card {
        min-height: 100%;
    }
    .report-metric-label {
        font-size: 0.74rem;
        letter-spacing: 0.08em;
    }
    .report-metric-label--light {
        color: rgba(255, 255, 255, 0.86);
    }
    .report-metric-value {
        font-size: clamp(0.92rem, 1.7vw, 1.05rem);
        letter-spacing: -0.02em;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    #stockTable thead th {
        font-size: 0.74rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    #stockTable tbody td {
        font-size: 0.94rem;
    }
    @media (max-width: 767.98px) {
        .report-metric-value {
            font-size: 0.88rem;
        }
    }
    @media print { .no-print { display: none !important; } }
</style>
@endsection

@push('scripts')
{{-- Export Libraries --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<script>
    // Excel Logic
    document.getElementById('export_excel').addEventListener('click', function() {
        const table = document.getElementById("stockTable");
        const wb = XLSX.utils.table_to_book(table, { sheet: "StockValuation" });
        XLSX.writeFile(wb, "Stock_Report_{{ date('Y-m-d') }}.xlsx");
    });

    // PDF Logic
    document.getElementById('export_pdf').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4');
        
        doc.setFontSize(16);
        doc.text("Warehouse Stock Report", 40, 40);
        doc.setFontSize(10);
        doc.text("As At: {{ $toDate }}", 40, 60);

        doc.autoTable({
            html: '#stockTable',
            startY: 80,
            theme: 'striped',
            headStyles: { fillColor: [27, 37, 89], fontSize: 9 },
            bodyStyles: { fontSize: 8 },
            margin: { left: 40, right: 40 }
        });

        doc.save("Stock_Report_{{ date('Y-m-d') }}.pdf");
    });
</script>
@endpush
