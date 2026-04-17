@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
@endphp
<style>
.ps-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.8rem 1rem;text-align:center;}
.ps-kpi-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:3px;}
.ps-kpi-val{font-size:.9rem;font-weight:800;font-variant-numeric:tabular-nums;}
@media(max-width:768px){.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media(max-width:576px){.ps-kpi{min-width:100%;}.ps-table thead th,.ps-table tbody td{font-size:.65rem;}}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Purchase Summary</h5>
                <p class="text-muted mb-0">High-level purchase overview for the selected period.</p>
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

    <div class="d-flex gap-2 flex-wrap mb-4">
        <div class="ps-kpi"><div class="ps-kpi-label">Total Purchased</div><div class="ps-kpi-val" style="color:#0891b2">{{ $fmt($totalAmount) }}</div></div>
        <div class="ps-kpi"><div class="ps-kpi-label">Order Count</div><div class="ps-kpi-val" style="color:#7c3aed">{{ number_format($totalCount) }}</div></div>
        <div class="ps-kpi"><div class="ps-kpi-label">Average Order</div><div class="ps-kpi-val" style="color:#d97706">{{ $fmt($avgPurchase) }}</div></div>
    </div>

    <div class="card shadow-none border">
        <div class="card-header bg-white py-2"><h6 class="mb-0 fw-bold">Period Summary</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><td class="fw-semibold ps-3">Period</td><td class="text-end pe-3">{{ $from }} — {{ $to }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Total Purchase Amount</td><td class="text-end pe-3 fw-bold">{{ $fmt($totalAmount) }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Number of Orders</td><td class="text-end pe-3">{{ number_format($totalCount) }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Average Order Value</td><td class="text-end pe-3">{{ $fmt($avgPurchase) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
