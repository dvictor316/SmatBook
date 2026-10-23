@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn ($value) => \App\Support\GeoCurrency::format((float) $value, 'NGN', $currencyCode, $currencyLocale);
@endphp
<style>
.esp-summary{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.esp-metric{flex:1 1 160px;border:1px solid #e2e8f0;background:#fff;padding:12px 16px;border-radius:6px}
.esp-metric-label{font-size:.72rem;font-weight:700;text-transform:uppercase;color:#64748b}
.esp-metric-value{font-size:1rem;font-weight:700;font-variant-numeric:tabular-nums}
.esp-table th,.esp-table td{white-space:nowrap;font-size:.8rem;vertical-align:middle}
@media print{.esp-print{display:none!important}}
</style>
<div class="page-wrapper"><div class="content container-fluid">
    <div class="page-header esp-print"><div class="content-page-header">
        <div><h5>Expected Stock Profit</h5><p class="text-muted mb-0">Current stock at retail price. Gross profit excludes expenses and taxes.</p></div>
        <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="feather-printer me-1"></i> Print</button>
    </div></div>

    <div class="esp-summary">
        <div class="esp-metric"><div class="esp-metric-label">Stock Cost</div><div class="esp-metric-value">{{ $fmt($summary['cost']) }}</div></div>
        <div class="esp-metric"><div class="esp-metric-label">Expected Sales</div><div class="esp-metric-value">{{ $fmt($summary['revenue']) }}</div></div>
        <div class="esp-metric"><div class="esp-metric-label">Expected Gross Profit</div><div class="esp-metric-value {{ $summary['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ $fmt($summary['profit']) }}</div></div>
        <div class="esp-metric"><div class="esp-metric-label">Units on Hand</div><div class="esp-metric-value">{{ number_format($summary['quantity']) }}</div></div>
    </div>

    @if($summary['incomplete_products'])
        <div class="alert alert-warning">{{ number_format($summary['incomplete_products']) }} product(s) have no purchase cost or retail price. They are listed below but excluded from the profit totals.</div>
    @endif

    <div class="table-responsive"><table class="table table-sm table-hover esp-table">
        <thead><tr><th>Product</th><th>SKU</th><th class="text-end">Qty</th><th class="text-end">Purchase Cost</th><th class="text-end">Retail Price</th><th class="text-end">Stock Cost</th><th class="text-end">Expected Sales</th><th class="text-end">Expected Profit</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->name }}</td><td>{{ $row->sku ?? '' }}</td><td class="text-end">{{ \App\Support\CompactNumber::format($row->qty, 2) }}</td>
                <td class="text-end">{{ $row->unit_cost > 0 ? $fmt($row->unit_cost) : 'Missing' }}</td>
                <td class="text-end">{{ $row->selling_price > 0 ? $fmt($row->selling_price) : 'Missing' }}</td>
                <td class="text-end">{{ $row->has_complete_pricing ? $fmt($row->total_value) : '-' }}</td>
                <td class="text-end">{{ $row->has_complete_pricing ? $fmt($row->expected_revenue) : '-' }}</td>
                <td class="text-end {{ $row->expected_profit < 0 ? 'text-danger' : '' }}">{{ $row->has_complete_pricing ? $fmt($row->expected_profit) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No stock on hand.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div></div>
@endsection
