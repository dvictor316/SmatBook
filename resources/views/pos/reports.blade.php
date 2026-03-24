@extends('layout.mainlayout')

@section('content')
@php
    $grandTotalStockValue = 0;
    $totalSoldAmount = 0;
    $totalSoldUnits = 0;
@endphp

<style>
/* 1. NUCLEAR OVERRIDE: KILL GOLD TINT */
:root {
    --brand-navy: #0f172a;
    --brand-blue: #2563eb;
    --brand-slate: #64748b;
    --brand-bg: #f4f7fa !important;  /* Cool Slate Grey */
    --brand-card: #ffffff !important; /* Pure White */
    --brand-success: #059669;
    --brand-danger: #dc2626;
    --border-vibrant: #cbd5e1;
    --border-light: #e2e8f0;
}

/* Force the entire wrapper ecosystem to follow the neutral palette */
body, 
.main-wrapper, 
.page-wrapper, 
.content-wrapper,
.report-page-wrapper { 
    background-color: var(--brand-bg) !important; 
    background: var(--brand-bg) !important;
}

/* 2. PAGE LAYOUT */
.report-page-wrapper { 
    margin-left: var(--sb-sidebar-w, 270px); 
    padding: 25px; 
    min-height: 100vh; 
    margin-top: 60px;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    transition: all 0.3s ease;
}

body.mini-sidebar .report-page-wrapper { margin-left: var(--sb-sidebar-collapsed, 80px); }

/* 3. COMMAND HEADER */
.report-header-bar {
    background: var(--brand-card);
    height: 75px;
    padding: 0 25px;
    border-radius: 12px;
    border: 1px solid var(--border-light);
    border-bottom: 4px solid var(--brand-navy);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.report-header-bar h5 { 
    color: var(--brand-navy) !important; 
    font-weight: 800; 
    font-size: 1.2rem; 
    margin: 0; 
    letter-spacing: -0.5px;
}

.report-node-id {
    background: #f1f5f9;
    color: var(--brand-slate);
    font-size: 11px;
    font-weight: 800;
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid var(--border-light);
}

/* 4. METRIC GRID */
.metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
.metric-node {
    background: var(--brand-card);
    border: 1px solid var(--border-light);
    border-left: 6px solid var(--brand-navy);
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    transition: transform 0.2s;
}
.metric-node:hover { transform: translateY(-3px); }
.metric-label { font-size: 11px; font-weight: 800; color: var(--brand-slate); text-transform: uppercase; letter-spacing: 1.2px; }
.metric-value { font-size: 1.7rem; font-weight: 900; color: var(--brand-navy); margin-top: 8px; font-family: 'Courier New', monospace; }

/* 5. TABLE CARD */
.report-card { 
    background: var(--brand-card); 
    border-radius: 16px; 
    border: 1px solid var(--border-light); 
    overflow: hidden; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
}

.table thead th {
    background: #f8fafc !important;
    color: var(--brand-navy) !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    font-size: 11px;
    padding: 20px 15px;
    border-bottom: 2px solid var(--brand-navy) !important;
}

.table tbody td { padding: 18px 15px; vertical-align: middle; color: var(--brand-navy); font-weight: 600; font-size: 14px; }
.table tbody tr:hover { background: #fcfcfc; }

/* 6. UTILITY UI */
.filter-container { 
    background: var(--brand-card); 
    border: 1px solid var(--border-light); 
    border-radius: 12px; 
    padding: 20px; 
    margin-bottom: 25px; 
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
}

.sku-badge { 
    font-family: 'Courier New', monospace; 
    font-weight: 800; 
    color: var(--brand-blue); 
    background: #f1f5f9; 
    padding: 6px 12px; 
    border-radius: 6px;
    border: 1px solid var(--border-vibrant);
}

.stock-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid currentColor;
}

@media(max-width: 1199.98px) { 
    .report-page-wrapper { margin-left: 0 !important; padding: 15px; } 
}
</style>

<div class="report-page-wrapper">
    <!-- Institutional Command Header -->
    <div class="report-header-bar">
        <div class="d-flex align-items-center">
            <h5 class="mb-0">SALES ANALYTICAL COMMAND</h5>
            <div class="vr mx-3 opacity-25" style="height: 30px; width: 2px; background: #000;"></div>
            <span class="report-node-id">VALUATION-NODE: {{ strtoupper(date('M-Y')) }}</span>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div class="badge bg-white text-navy border border-2 border-primary px-3 py-2 fw-bold shadow-sm" style="color: var(--brand-navy);">
                <i class="fas fa-calendar-alt me-2 text-primary"></i> {{ date('l, d M Y') }}
            </div>
        </div>
    </div>

    @php
        // Loop through for top box calculations
        foreach($reports as $report) {
            $grandTotalStockValue += ($report->instock_qty ?? 0) * ($report->product_price ?? 0);
            $totalSoldAmount += ($report->total_sold_amount ?? 0);
            $totalSoldUnits += ($report->total_sold_qty ?? 0);
        }
    @endphp

    <!-- Metrics Grid -->
    <div class="metric-grid">
        <div class="metric-node">
            <div class="metric-label">Structural Units Sold</div>
            <div class="metric-value">{{ number_format($totalSoldUnits) }}</div>
        </div>
        <div class="metric-node" style="border-left-color: var(--brand-blue);">
            <div class="metric-label">Gross Revenue Valuation</div>
            <div class="metric-value text-primary">₦{{ number_format($totalSoldAmount, 2) }}</div>
        </div>
        <div class="metric-node" style="border-left-color: var(--brand-success);">
            <div class="metric-label">Liquidity Stock Valuation</div>
            <div class="metric-value text-success">₦{{ number_format($grandTotalStockValue, 2) }}</div>
        </div>
    </div>

    <!-- Search/Filter Container -->
    <div class="filter-container">
        @component('components.search-filter') @endcomponent
    </div>

    <!-- Main Analytics Table -->
    <div class="report-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Product Entity Configuration</th>
                        <th>SKU Node</th>
                        <th>Classification</th>
                        <th class="text-end">Node Price</th>
                        <th class="text-center">Sold Units</th>
                        <th class="text-end">Sold Valuation</th>
                        <th class="text-center">Stock Node</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $key => $report)
                        <tr>
                            <td class="text-center text-muted small">{{ $reports->firstItem() + $key }}</td>
                            <td><div class="fw-bold" style="font-size: 15px;">{{ $report->product_name ?? 'N/A' }}</div></td>
                            <td><span class="sku-badge">{{ $report->sku ?? 'N/A' }}</span></td>
                            <td><span class="small fw-bold text-secondary uppercase">{{ $report->category_name ?? 'N/A' }}</span></td>
                            <td class="text-end fw-bold">₦{{ number_format($report->product_price ?? 0, 2) }}</td>
                            <td class="text-center"><span class="fw-bold text-primary">{{ $report->total_sold_qty ?? 0 }}</span></td>
                            <td class="text-end fw-bold text-primary">₦{{ number_format($report->total_sold_amount ?? 0, 2) }}</td>
                            <td class="text-center">
                                @php $stock = $report->instock_qty ?? 0; @endphp
                                <span class="stock-status {{ $stock > 5 ? 'text-success' : 'text-danger' }}">
                                    {{ $stock }} Units
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-database fa-3x mb-3 text-light opacity-50"></i>
                                <h6 class="text-muted fw-bold">NO STRUCTURAL PERFORMANCE DATA FOUND</h6>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-top bg-light d-flex justify-content-between align-items-center">
            <span class="small text-muted fw-bold text-uppercase px-2"><i class="fas fa-shield-check me-2"></i>Data integrity verified by core terminal</span>
            <div class="pagination-institutional">{{ $reports->links() }}</div>
        </div>
    </div>

    <!-- Bottom Financial Summary -->
    <div class="row mt-5 mb-4">
        <div class="col-md-7">
            <div class="p-4 rounded-4 bg-white border-2 border h-100 d-flex flex-column justify-content-center" style="border-style: dashed; border-color: var(--brand-slate);">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-microchip me-2 text-primary"></i> Analytical Logic Summary</h6>
                <p class="small text-muted mb-0 leading-relaxed">
                    This report summarizes sold units, gross sales value, and current stock value across your products based on recorded sales and live inventory balances.
                </p>
            </div>
        </div>
        <div class="col-md-5">
            <div class="p-4 rounded-4 bg-white border-2 border text-end shadow-sm" style="border-color: var(--brand-navy);">
                <div class="metric-label mb-2">Total System Liquidity Valuation</div>
                <div class="display-6 fw-bold text-primary mb-0" style="font-family: 'Courier New', monospace;">₦{{ number_format($grandTotalStockValue, 2) }}</div>
                <div class="mt-2 small text-muted fw-bold">BASED ON CURRENT MARKET INVENTORY</div>
            </div>
        </div>
    </div>
</div>
@endsection
