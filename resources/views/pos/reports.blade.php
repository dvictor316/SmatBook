@extends('layout.mainlayout')

@section('content')
@php
    $grandTotalStockValue = (float) $reports->sum(fn ($report) => ((float) ($report->instock_qty ?? 0)) * ((float) ($report->product_price ?? 0)));
    $totalSoldAmount = (float) $reports->sum(fn ($report) => (float) ($report->total_sold_amount ?? 0));
    $totalSoldUnits = (float) $reports->sum(fn ($report) => (float) ($report->total_sold_qty ?? 0));
    $catalogItems = (int) $reports->count();
@endphp

<style>
    .pos-report-hero {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #dbe7ff;
        border-radius: 22px;
        padding: 28px;
        box-shadow: 0 14px 40px rgba(37, 99, 235, 0.08);
    }

    .pos-report-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #eef4ff;
        color: #3156c8;
        font-size: 12px;
        font-weight: 700;
    }

    .pos-report-card {
        border: 1px solid #e6edf7;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .pos-metric-card {
        border: 1px solid #e6edf7;
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .pos-metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .pos-report-table thead th {
        background: #f5f8ff;
        border-bottom: 1px solid #dfe8f7;
        color: #5b6b87;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        padding: 16px 14px;
        text-transform: uppercase;
    }

    .pos-report-table tbody td {
        padding: 16px 14px;
        vertical-align: middle;
    }

    .pos-report-table tbody tr:hover {
        background: #fafcff;
    }

    .pos-report-sku {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
    }

    .pos-stock-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 92px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
    }

    .pos-stock-good {
        background: #dcfce7;
        color: #166534;
    }

    .pos-stock-low {
        background: #fee2e2;
        color: #b91c1c;
    }

    .pos-summary-card {
        border: 1px solid #e6edf7;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                POS Reports
            @endslot
        @endcomponent

        <div class="pos-report-hero mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="pos-report-chip">
                            <i class="fas fa-chart-line"></i>
                            Items Sold Report
                        </span>
                        <span class="pos-report-chip">
                            <i class="fas fa-calendar-alt"></i>
                            {{ now()->format('l, d M Y') }}
                        </span>
                    </div>
                    <h2 class="mb-2" style="color:#0f172a;font-weight:800;">POS performance in the same app style</h2>
                    <p class="mb-0 text-muted">Track sold units, sales value, and live stock position from your POS activity without leaving the main reporting experience.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <button onclick="print()" class="btn btn-white border shadow-sm">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="card pos-metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="text-muted small text-uppercase fw-bold">Total Units Sold</div>
                                <h3 class="mt-2 mb-0 fw-bold">{{ number_format($totalSoldUnits) }}</h3>
                            </div>
                            <span class="pos-metric-icon" style="background:#eef4ff;color:#3156c8;">
                                <i class="fas fa-box-open"></i>
                            </span>
                        </div>
                        <div class="text-muted small">All units sold across the filtered POS report set.</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card pos-metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="text-muted small text-uppercase fw-bold">Gross Sales Value</div>
                                <h3 class="mt-2 mb-0 fw-bold text-primary">₦{{ number_format($totalSoldAmount, 2) }}</h3>
                            </div>
                            <span class="pos-metric-icon" style="background:#eff6ff;color:#2563eb;">
                                <i class="fas fa-money-bill-wave"></i>
                            </span>
                        </div>
                        <div class="text-muted small">Combined sold valuation from reported POS item movement.</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card pos-metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="text-muted small text-uppercase fw-bold">Current Stock Value</div>
                                <h3 class="mt-2 mb-0 fw-bold text-success">₦{{ number_format($grandTotalStockValue, 2) }}</h3>
                            </div>
                            <span class="pos-metric-icon" style="background:#ecfdf5;color:#059669;">
                                <i class="fas fa-warehouse"></i>
                            </span>
                        </div>
                        <div class="text-muted small">Live inventory value based on remaining stock and current prices.</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card pos-metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="text-muted small text-uppercase fw-bold">Catalog Items</div>
                                <h3 class="mt-2 mb-0 fw-bold">{{ number_format($catalogItems) }}</h3>
                            </div>
                            <span class="pos-metric-icon" style="background:#fff7ed;color:#ea580c;">
                                <i class="fas fa-tags"></i>
                            </span>
                        </div>
                        <div class="text-muted small">Products currently represented in this POS sales report.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card pos-report-card mb-4">
            <div class="card-body">
                @component('components.search-filter') @endcomponent
            </div>
        </div>

        <div class="card pos-report-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table pos-report-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 70px;">#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Sold Units</th>
                                <th class="text-end">Sold Value</th>
                                <th class="text-center">Stock Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reports as $key => $report)
                                @php
                                    $stock = (float) ($report->instock_qty ?? 0);
                                @endphp
                                <tr>
                                    <td class="text-center text-muted">{{ $reports->firstItem() + $key }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $report->product_name ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span class="pos-report-sku">{{ $report->sku ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted fw-semibold">{{ $report->category_name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-end fw-bold">₦{{ number_format((float) ($report->product_price ?? 0), 2) }}</td>
                                    <td class="text-center">
                                        <span class="fw-bold text-primary">{{ number_format((float) ($report->total_sold_qty ?? 0)) }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">₦{{ number_format((float) ($report->total_sold_amount ?? 0), 2) }}</td>
                                    <td class="text-center">
                                        <span class="pos-stock-badge {{ $stock > 5 ? 'pos-stock-good' : 'pos-stock-low' }}">
                                            {{ number_format($stock) }} units
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-database fa-2x mb-3 d-block"></i>
                                            No POS report data found for the current filter.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <span class="text-muted small">Items sold summary generated from POS sales and live inventory balances.</span>
                <div>{{ $reports->links() }}</div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-7">
                <div class="card pos-summary-card h-100">
                    <div class="card-body">
                        <h5 class="fw-bold text-dark mb-3">POS Report Summary</h5>
                        <p class="text-muted mb-0">This page now follows the same visual structure as the rest of the app, while still summarizing sold units, sales value, and stock position from POS activity in a clean reporting layout.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card pos-summary-card h-100">
                    <div class="card-body text-lg-end">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Inventory Valuation</div>
                        <h3 class="fw-bold text-primary mb-1">₦{{ number_format($grandTotalStockValue, 2) }}</h3>
                        <div class="text-muted small">Based on current stock quantity multiplied by unit price.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
