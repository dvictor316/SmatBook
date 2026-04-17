@extends('layout.mainlayout')

@section('content')
@php
    $grandTotalStockValue = (float) $reports->sum(fn ($report) => ((float) ($report->instock_qty ?? 0)) * ((float) ($report->product_price ?? 0)));
    $totalSoldAmount     = (float) $reports->sum(fn ($report) => (float) ($report->total_sold_amount ?? 0));
    $totalSoldUnits      = (float) $reports->sum(fn ($report) => (float) ($report->total_sold_qty ?? 0));
    $catalogItems        = (int) $reports->count();
    $companyName         = Auth::user()?->company?->name ?? config('app.name', 'My Business');
@endphp

<style>
/* ─── QuickBooks-style Report Page ─── */
.qb-report-page { background: #f7f8fc; min-height: 100vh; }

/* Report document wrapper */
.qb-report-doc {
    background: #fff;
    border: 1px solid #dee2e9;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    overflow: hidden;
}

/* Report header band */
.qb-report-header {
    background: #fff;
    border-bottom: 2px solid #e4e8f0;
    padding: 24px 28px 20px;
}
.qb-report-title {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -.3px;
    line-height: 1.25;
}
.qb-report-meta {
    font-size: 12.5px;
    color: #64748b;
    margin-top: 3px;
}
.qb-company-name {
    font-size: 13px;
    font-weight: 700;
    color: #1e3a5f;
    letter-spacing: .02em;
    text-transform: uppercase;
}

/* Action buttons */
.qb-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    border: 1px solid #dee2e9;
    background: #fff;
    color: #374151;
    cursor: pointer;
    transition: background .15s, box-shadow .15s;
    text-decoration: none;
}
.qb-btn:hover { background: #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,.08); color: #111; }
.qb-btn-primary { background: #2563eb; color: #fff; border-color: #1d4ed8; }
.qb-btn-primary:hover { background: #1d4ed8; color: #fff; }

/* Divider band */
.qb-filter-band {
    background: #f8fafd;
    border-bottom: 1px solid #e4e8f0;
    padding: 14px 28px;
}

/* KPI strip */
.qb-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-bottom: 1px solid #e4e8f0;
}
@media (max-width: 768px) {
    .qb-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
.qb-kpi-cell {
    padding: 18px 22px;
    border-right: 1px solid #e4e8f0;
    position: relative;
}
.qb-kpi-cell:last-child { border-right: 0; }
.qb-kpi-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .09em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 5px;
}
.qb-kpi-value {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}
.qb-kpi-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 3px;
}
.qb-kpi-icon {
    position: absolute;
    top: 16px;
    right: 18px;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

/* Table */
.qb-report-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.qb-report-table thead tr {
    background: #f1f5fb;
    border-top: 1px solid #e4e8f0;
    border-bottom: 1px solid #c8d3e8;
}
.qb-report-table thead th {
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #4b5e7a;
    white-space: nowrap;
}
.qb-report-table tbody tr {
    border-bottom: 1px solid #edf0f6;
    transition: background .1s;
}
.qb-report-table tbody tr:hover { background: #f5f8ff; }
.qb-report-table tbody td {
    padding: 11px 14px;
    vertical-align: middle;
    color: #1e293b;
}
.qb-report-table tfoot tr {
    background: #f1f5fb;
    border-top: 2px solid #c8d3e8;
}
.qb-report-table tfoot td {
    padding: 11px 14px;
    font-weight: 700;
    font-size: 12.5px;
    color: #0f172a;
}

/* Badges */
.qb-sku-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    font-family: monospace;
}
.qb-stock-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    min-width: 80px;
    text-align: center;
}
.qb-stock-ok  { background: #dcfce7; color: #15803d; }
.qb-stock-low { background: #fee2e2; color: #b91c1c; }

/* Footer / pagination bar */
.qb-report-footer {
    padding: 14px 28px;
    border-top: 1px solid #e4e8f0;
    background: #f8fafd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}
.qb-footer-note { font-size: 11.5px; color: #94a3b8; }

@media print {
    .qb-report-page .no-print { display: none !important; }
    .qb-report-doc { border: none; box-shadow: none; }
    .qb-report-table tbody tr:hover { background: transparent; }
}
</style>

<div class="page-wrapper qb-report-page">
    <div class="content container-fluid">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 no-print" style="gap:10px;">
            <div>
                <nav aria-label="breadcrumb" style="font-size:12.5px;">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active">POS Sales Report</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button onclick="window.print()" class="qb-btn">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="qb-btn">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="qb-report-doc">

            <div class="qb-report-header">
                <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:12px;">
                    <div>
                        <div class="qb-company-name mb-1">{{ $companyName }}</div>
                        <div class="qb-report-title">POS Sales Report</div>
                        <div class="qb-report-meta">
                            Items sold &amp; stock position &nbsp;·&nbsp;
                            Generated {{ now()->format('M d, Y') }}
                            @if(request('branch'))
                                &nbsp;·&nbsp; Branch: <strong>{{ request('branch') }}</strong>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 no-print" style="margin-top:2px;">
                        <span style="font-size:11px;color:#94a3b8;">Period:</span>
                        <span style="font-size:12px;font-weight:600;color:#1e3a5f;">
                            {{ request('from') ? \Carbon\Carbon::parse(request('from'))->format('M d, Y') : 'All time' }}
                            @if(request('to'))
                                – {{ \Carbon\Carbon::parse(request('to'))->format('M d, Y') }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="qb-filter-band no-print">
                @component('components.search-filter') @endcomponent
            </div>

            <div class="qb-kpi-strip">
                <div class="qb-kpi-cell">
                    <div class="qb-kpi-icon" style="background:#eef4ff;color:#3356c8;">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="qb-kpi-label">Units Sold</div>
                    <div class="qb-kpi-value">{{ number_format($totalSoldUnits) }}</div>
                    <div class="qb-kpi-sub">Total across all products</div>
                </div>
                <div class="qb-kpi-cell">
                    <div class="qb-kpi-icon" style="background:#eff6ff;color:#2563eb;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="qb-kpi-label">Gross Sales</div>
                    <div class="qb-kpi-value" style="color:#2563eb;">₦{{ number_format($totalSoldAmount, 2) }}</div>
                    <div class="qb-kpi-sub">Combined POS revenue</div>
                </div>
                <div class="qb-kpi-cell">
                    <div class="qb-kpi-icon" style="background:#f0fdf4;color:#16a34a;">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="qb-kpi-label">Stock Value</div>
                    <div class="qb-kpi-value" style="color:#16a34a;">₦{{ number_format($grandTotalStockValue, 2) }}</div>
                    <div class="qb-kpi-sub">Remaining inventory value</div>
                </div>
                <div class="qb-kpi-cell">
                    <div class="qb-kpi-icon" style="background:#fff7ed;color:#ea580c;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="qb-kpi-label">Products</div>
                    <div class="qb-kpi-value">{{ number_format($catalogItems) }}</div>
                    <div class="qb-kpi-sub">Items in this report</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="qb-report-table">
                    <thead>
                        <tr>
                            <th style="width:46px;">#</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Sold Units</th>
                            <th class="text-end">Sold Value</th>
                            <th class="text-center">Stock Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $key => $report)
                            @php $stock = (float) ($report->instock_qty ?? 0); @endphp
                            <tr>
                                <td class="text-muted" style="font-size:12px;">{{ $reports->firstItem() + $key }}</td>
                                <td>
                                    <span class="fw-semibold" style="color:#0f172a;">{{ $report->product_name ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="qb-sku-badge">{{ $report->sku ?? '—' }}</span>
                                </td>
                                <td style="color:#475569;">{{ $report->category_name ?? '—' }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">
                                    ₦{{ number_format((float) ($report->product_price ?? 0), 2) }}
                                </td>
                                <td class="text-end fw-semibold" style="color:#2563eb;font-variant-numeric:tabular-nums;">
                                    {{ number_format((float) ($report->total_sold_qty ?? 0)) }}
                                </td>
                                <td class="text-end fw-semibold" style="font-variant-numeric:tabular-nums;">
                                    ₦{{ number_format((float) ($report->total_sold_amount ?? 0), 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="qb-stock-badge {{ $stock > 5 ? 'qb-stock-ok' : 'qb-stock-low' }}">
                                        {{ number_format($stock) }} units
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5" style="color:#94a3b8;">
                                    <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                                    No products found for the selected filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reports->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="5" style="font-size:12px;letter-spacing:.05em;text-transform:uppercase;color:#64748b;">
                                Page Totals ({{ $reports->count() }} items)
                            </td>
                            <td class="text-end" style="color:#2563eb;">{{ number_format($totalSoldUnits) }}</td>
                            <td class="text-end" style="color:#2563eb;">₦{{ number_format($totalSoldAmount, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <div class="qb-report-footer">
                <span class="qb-footer-note">
                    <i class="fas fa-info-circle me-1"></i>
                    Sold units &amp; values reflect POS activity. Stock value = remaining qty × unit price.
                </span>
                <div>{{ $reports->links() }}</div>
            </div>

        </div>

    </div>
</div>
@endsection
