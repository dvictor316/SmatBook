<?php $page = 'inventory-damages'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-3 no-print">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h4 class="fw-bold mb-1 report-page-title">Stock Damage Register</h4>
                    <p class="text-muted mb-0 report-page-subtitle">Track expired, spoiled, broken and written-off perishable stock.</p>
                </div>
                <div class="col-auto">
                    <button onclick="window.print()" class="btn btn-white border report-action-btn">
                        <i class="feather-printer me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>

        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Stock Damage Register',
        ])

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 damage-metric-card">
                    <div class="card-body p-3">
                        <p class="text-muted mb-1 fw-bold damage-metric-label">Damage Entries</p>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($damages->total()) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 damage-metric-card">
                    <div class="card-body p-3">
                        <p class="text-muted mb-1 fw-bold damage-metric-label">Units Damaged</p>
                        <h4 class="fw-bold text-warning mb-0">{{ number_format($totalDamagedQty, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-0 damage-metric-card damage-metric-card--loss">
                    <div class="card-body p-3">
                        <p class="mb-1 fw-bold damage-metric-label text-white">Estimated Cost Lost</p>
                        <h4 class="fw-bold text-white mb-0">{{ \App\Support\GeoCurrency::format($totalDamageValue, 'NGN', $currencyCode, $currencyLocale) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-none border mb-3 no-print">
            <div class="card-body p-2">
                <form action="{{ route('inventory.damages') }}" method="GET">
                    <div class="row gx-2 align-items-end">
                        <div class="col-md-4">
                            <label class="damage-filter-label">Search</label>
                            <input type="text" name="q" class="form-control form-control-sm border-0 bg-light" value="{{ $search }}" placeholder="Product, SKU, reason or note">
                        </div>
                        <div class="col-md-3">
                            <label class="damage-filter-label">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm border-0 bg-light" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="damage-filter-label">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm border-0 bg-light" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border shadow-none overflow-hidden">
            <div class="table-responsive">
                <table class="table table-sm mb-0 damage-table">
                    <thead>
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Branch</th>
                            <th>Reason</th>
                            <th>Notes</th>
                            <th class="text-end">Qty</th>
                            <th class="pe-3 text-end">Cost Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($damages as $damage)
                            @php
                                $lineValue = (float) ($damage->quantity ?? 0) * (float) ($damage->purchase_price ?? 0);
                                $reason = trim(str_replace('Damaged Stock -', '', (string) ($damage->reference ?? 'Damage')));
                            @endphp
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($damage->created_at)->format('d M Y, H:i') }}</td>
                                <td class="fw-bold text-dark">{{ $damage->product_name }}</td>
                                <td class="text-muted">{{ $damage->sku ?: 'N/A' }}</td>
                                <td>{{ $damage->branch_name ?: ($activeBranch['name'] ?? 'Workspace Default') }}</td>
                                <td><span class="badge bg-warning text-dark">{{ $reason !== '' ? $reason : 'Damage' }}</span></td>
                                <td class="text-muted">{{ $damage->remarks ?: '-' }}</td>
                                <td class="text-end fw-bold text-warning">{{ number_format((float) $damage->quantity, 2) }}</td>
                                <td class="pe-3 text-end fw-bold text-danger">{{ \App\Support\GeoCurrency::format($lineValue, 'NGN', $currencyCode, $currencyLocale) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No damaged stock has been recorded for the selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($damages->hasPages())
                <div class="p-3 border-top no-print">
                    {{ $damages->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .report-page-title { color: #102a5a; font-size: 1.35rem; }
    .report-page-subtitle { font-size: 0.95rem; }
    .damage-metric-card { min-height: 100%; border-radius: 8px; }
    .damage-metric-card--loss { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); }
    .damage-metric-label { font-size: 0.74rem; letter-spacing: 0.08em; text-transform: uppercase; }
    .damage-filter-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #102a5a;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .damage-table thead th {
        background: #f8fafc;
        color: #102a5a;
        font-size: 0.74rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }
    .damage-table tbody td { vertical-align: middle; }
    @media print {
        .no-print, .header, .sidebar { display: none !important; }
        .page-wrapper { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    }
</style>
@endsection
