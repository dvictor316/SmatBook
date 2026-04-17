@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
@endphp
<style>
.ts-kpi{flex:1;min-width:150px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.9rem 1rem;text-align:center;}
.ts-kpi-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:3px;}
.ts-kpi-val{font-size:.95rem;font-weight:800;font-variant-numeric:tabular-nums;}
@media(max-width:768px){.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media(max-width:576px){.ts-kpi{min-width:100%;}}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Tax Summary Report</h5>
                <p class="text-muted mb-0">Tax collected on sales vs tax paid on purchases for the period.</p>
            </div>
            <div class="list-btn"><ul class="filter-list">
                <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
            </ul></div>
        </div>
    </div>

    <div class="card shadow-none border mb-3 no-print">
        <div class="card-body p-2">
            <form action="{{ url()->current() }}" method="GET" class="d-flex flex-wrap align-items-end gap-2">
                <div><label class="form-label small fw-bold mb-1">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from }}"></div>
                <div><label class="form-label small fw-bold mb-1">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="{{ $to }}"></div>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="d-flex gap-2 flex-wrap mb-4">
        <div class="ts-kpi"><div class="ts-kpi-label">Tax on Sales (Output)</div><div class="ts-kpi-val" style="color:#16a34a">{{ $fmt($taxOnSales) }}</div></div>
        <div class="ts-kpi"><div class="ts-kpi-label">Tax on Purchases (Input)</div><div class="ts-kpi-val" style="color:#dc2626">{{ $fmt($taxOnPurchases) }}</div></div>
        <div class="ts-kpi">
            <div class="ts-kpi-label">Net Tax Liability</div>
            <div class="ts-kpi-val" style="color:{{ $netTaxLiability >= 0 ? '#7c3aed' : '#0891b2' }}">{{ $fmt(abs($netTaxLiability)) }}</div>
            <div class="mt-1" style="font-size:.6rem;color:#94a3b8">{{ $netTaxLiability >= 0 ? 'Amount owed to government' : 'Tax credit / refundable' }}</div>
        </div>
    </div>

    <div class="card shadow-none border">
        <div class="card-header bg-white py-2"><h6 class="mb-0 fw-bold">Tax Breakdown</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><td class="fw-semibold ps-3">Period</td><td class="text-end pe-3">{{ $from }} — {{ $to }}</td></tr>
                    <tr class="table-success">
                        <td class="ps-3 fw-bold"><i class="fas fa-arrow-up me-1"></i> Output Tax (Sales)</td>
                        <td class="text-end pe-3 fw-bold text-success">{{ $fmt($taxOnSales) }}</td>
                    </tr>
                    <tr class="table-danger">
                        <td class="ps-3 fw-bold"><i class="fas fa-arrow-down me-1"></i> Input Tax (Purchases)</td>
                        <td class="text-end pe-3 fw-bold text-danger">{{ $fmt($taxOnPurchases) }}</td>
                    </tr>
                    <tr class="table-active">
                        <td class="ps-3 fw-bold">Net Tax Liability (Output − Input)</td>
                        <td class="text-end pe-3 fw-bold" style="color:{{ $netTaxLiability >= 0 ? '#7c3aed' : '#0891b2' }}">
                            {{ $netTaxLiability >= 0 ? '+' : '-' }}{{ $fmt(abs($netTaxLiability)) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 py-2">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Net liability = Output Tax − Input Tax. A positive value means tax is owed; negative means an input credit applies.
            </small>
        </div>
    </div>
</div>
</div>
@endsection
