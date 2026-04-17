@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
@endphp
<style>
.sv-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.sv-table tbody td{font-size:.8rem;vertical-align:middle;}
.sv-kpi{flex:1;min-width:130px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.75rem 1rem;text-align:center;}
.sv-kpi-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:3px;}
.sv-kpi-val{font-size:.9rem;font-weight:800;font-variant-numeric:tabular-nums;}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Stock Valuation Report</h5>
                <p class="text-muted mb-0">Current inventory valued at cost price.</p>
            </div>
            <div class="list-btn"><ul class="filter-list">
                <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
            </ul></div>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="d-flex gap-2 flex-wrap mb-4">
        <div class="sv-kpi"><div class="sv-kpi-label">Total Stock Value</div><div class="sv-kpi-val" style="color:#3b82f6">{{ $fmt($totalValue) }}</div></div>
        <div class="sv-kpi"><div class="sv-kpi-label">Total Units</div><div class="sv-kpi-val" style="color:#7c3aed">{{ number_format($totalQty) }}</div></div>
        <div class="sv-kpi"><div class="sv-kpi-label">Products</div><div class="sv-kpi-val" style="color:#0891b2">{{ number_format(count($rows)) }}</div></div>
    </div>

    <div class="card shadow-none border">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm sv-table mb-0">
                    <thead><tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-end">Qty on Hand</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total Value</th>
                    </tr></thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-bold">{{ $row->name }}</td>
                            <td><code class="small">{{ $row->sku ?? '—' }}</code></td>
                            <td>{{ $row->category ?? 'Unassigned' }}</td>
                            <td class="text-end {{ $row->qty <= 0 ? 'text-danger' : '' }}">{{ number_format($row->qty, 0) }}</td>
                            <td class="text-end">{{ $fmt($row->unit_cost) }}</td>
                            <td class="text-end fw-bold">{{ $fmt($row->total_value) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No stock data available.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                    <tfoot><tr class="table-active fw-bold">
                        <td colspan="4" class="text-end">Totals</td>
                        <td class="text-end">{{ number_format($totalQty) }}</td>
                        <td></td>
                        <td class="text-end">{{ $fmt($totalValue) }}</td>
                    </tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
