@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $isBalanced = abs(($totalAssets ?? 0) - (($totalLiabilities ?? 0) + ($totalEquity ?? 0))) < 1;
@endphp
<style>
.bss-wrap{max-width:820px;margin:0 auto 24px;}
.bss-card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 8px 24px rgba(15,23,42,.06);padding:1.5rem 1.75rem;}
.bss-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#2563eb;margin-bottom:2px;}
.bss-company{font-size:1.25rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;}
.bss-date{font-size:.75rem;color:#64748b;}
.bss-sep{border:0;border-top:1px solid #e2e8f0;margin:1.25rem 0;}
.bss-row{display:flex;justify-content:space-between;align-items:center;padding:.45rem 0;border-bottom:1px solid #f1f5f9;font-size:.82rem;}
.bss-row:last-child{border-bottom:none;}
.bss-row-label{color:#334155;font-weight:500;}
.bss-row-amount{font-weight:700;font-variant-numeric:tabular-nums;color:#0f172a;}
.bss-section-head{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:#2563eb;padding:6px 0 2px;display:flex;align-items:center;gap:6px;}
.bss-section-head::before{content:'';display:block;width:3px;height:12px;border-radius:2px;background:currentColor;}
.bss-total-row{display:flex;justify-content:space-between;font-weight:800;font-size:.84rem;padding:.55rem 0;background:#f0f7ff;border-radius:8px;padding:.45rem .75rem;margin-top:6px;}
.bss-total-row span:last-child{color:#2563eb;}
.bss-balanced{font-size:.75rem;text-align:center;padding:.55rem 1rem;border-radius:999px;font-weight:700;margin-top:1rem;}
.bss-balanced.ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.bss-balanced.err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;}
.kpi-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1rem 1.1rem;text-align:center;box-shadow:0 4px 12px rgba(15,23,42,.04);}
.kpi-label{font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px;}
.kpi-value{font-size:.92rem;font-weight:800;font-variant-numeric:tabular-nums;color:#0f172a;}
.kpi-card.blue .kpi-value{color:#2563eb;}
.kpi-card.teal .kpi-value{color:#0d9488;}
.kpi-card.green .kpi-value{color:#16a34a;}
@media(max-width:768px){.kpi-grid{grid-template-columns:1fr 1fr;}.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media(max-width:576px){.kpi-grid{grid-template-columns:1fr;}.bss-wrap{padding:0;}.bss-card{padding:1rem;border-radius:12px;}}
@media print{.no-print{display:none!important;}.page-wrapper{margin:0;padding:0;background:#fff!important;}.bss-card{box-shadow:none!important;border:1px solid #ccc!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">

    {{-- Header --}}
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Balance Sheet Summary</h5>
                <p class="text-muted mb-0">High-level snapshot of assets, liabilities, and equity.</p>
            </div>
            <div class="list-btn">
                <ul class="filter-list">
                    <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
                    <li><a class="btn btn-primary w-auto" href="{{ url()->current() }}"><i class="feather-refresh-ccw me-1"></i> Reset</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card shadow-none border mb-3 no-print">
        <div class="card-body p-2">
            <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-end gap-3 flex-wrap">
                <div>
                    <label class="form-label small fw-bold mb-1">As Of Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date', now()->toDateString()) }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi-card blue">
            <div class="kpi-label">Total Assets</div>
            <div class="kpi-value">{{ $fmt($totalAssets ?? 0) }}</div>
        </div>
        <div class="kpi-card teal">
            <div class="kpi-label">Total Liabilities</div>
            <div class="kpi-value">{{ $fmt($totalLiabilities ?? 0) }}</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-label">Total Equity</div>
            <div class="kpi-value">{{ $fmt($totalEquity ?? 0) }}</div>
        </div>
    </div>

    {{-- Summary Card --}}
    <div class="bss-wrap">
        <div class="bss-card">
            <div class="text-center mb-3">
                <div class="bss-title">Balance Sheet Summary</div>
                <div class="bss-company">{{ config('app.name', 'My Company') }}</div>
                <div class="bss-date">As of {{ \Carbon\Carbon::parse(request('date', now()->toDateString()))->format('F j, Y') }}</div>
            </div>
            <hr class="bss-sep">

            <div class="bss-section-head">Assets</div>
            <div class="bss-row">
                <span class="bss-row-label">Total Assets</span>
                <span class="bss-row-amount">{{ $fmt($totalAssets ?? 0) }}</span>
            </div>

            <div class="bss-section-head mt-3">Liabilities &amp; Equity</div>
            <div class="bss-row">
                <span class="bss-row-label">Total Liabilities</span>
                <span class="bss-row-amount">{{ $fmt($totalLiabilities ?? 0) }}</span>
            </div>
            <div class="bss-row">
                <span class="bss-row-label">Retained Earnings</span>
                <span class="bss-row-amount" style="color:{{ ($retainedEarnings ?? 0) >= 0 ? '#16a34a' : '#dc2626' }}">{{ $fmt($retainedEarnings ?? 0) }}</span>
            </div>
            <div class="bss-row">
                <span class="bss-row-label">Total Equity</span>
                <span class="bss-row-amount">{{ $fmt($totalEquity ?? 0) }}</span>
            </div>

            <div class="bss-total-row mt-2">
                <span>Total Liabilities &amp; Equity</span>
                <span>{{ $fmt(($totalLiabilities ?? 0) + ($totalEquity ?? 0)) }}</span>
            </div>

            <div class="bss-balanced {{ $isBalanced ? 'ok' : 'err' }}">
                @if($isBalanced)
                    <i class="fas fa-check-circle me-1"></i> Balance sheet is balanced
                @else
                    <i class="fas fa-exclamation-triangle me-1"></i> Balance sheet is out of balance — review your accounts
                @endif
            </div>
        </div>
    </div>

</div>
</div>
@endsection
