@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $diff = fn($a, $b) => (float)$a - (float)$b;
    $pct  = fn($a, $b) => $b != 0 ? round(((float)$a - (float)$b) / abs((float)$b) * 100, 1) : null;
@endphp
<style>
.bsc-card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 8px 24px rgba(15,23,42,.06);padding:1.5rem 1.75rem;max-width:900px;margin:0 auto 24px;}
.bsc-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#2563eb;text-align:center;margin-bottom:2px;}
.bsc-company{font-size:1.2rem;font-weight:800;color:#0f172a;text-align:center;letter-spacing:-.02em;}
.bsc-sep{border:0;border-top:1px solid #e2e8f0;margin:1.1rem 0;}
.bsc-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.82rem;}
.bsc-table th{font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b;padding:.6rem .7rem;background:#f8fafc;text-align:right;}
.bsc-table th:first-child{text-align:left;}
.bsc-table td{padding:.45rem .7rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:right;}
.bsc-table td:first-child{text-align:left;color:#334155;font-weight:500;}
.bsc-table tr.section-head td{font-size:.68rem;font-weight:800;text-transform:uppercase;color:#2563eb;padding-top:.9rem;padding-bottom:.2rem;border-bottom:none;background:transparent;}
.bsc-table tr.total-row td{font-weight:800;background:#f0f7ff;border-radius:6px;}
.bsc-tag-up{color:#16a34a;font-size:.72rem;font-weight:700;}
.bsc-tag-dn{color:#dc2626;font-size:.72rem;font-weight:700;}
.bsc-tag-flat{color:#94a3b8;font-size:.72rem;}
.bsc-amount{font-variant-numeric:tabular-nums;}
@media print{.no-print{display:none!important;}.page-wrapper{margin:0;padding:0;background:#fff!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">

    {{-- Header --}}
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Balance Sheet Comparison</h5>
                <p class="text-muted mb-0">Compare two balance sheet dates side by side.</p>
            </div>
            <div class="list-btn">
                <ul class="filter-list">
                    <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card shadow-none border mb-3 no-print">
        <div class="card-body p-2">
            <form action="{{ url()->current() }}" method="GET" class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <label class="form-label small fw-bold mb-1">Period A (Primary)</label>
                    <input type="date" name="date_a" class="form-control form-control-sm" value="{{ request('date_a', now()->toDateString()) }}">
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1">Period B (Comparison)</label>
                    <input type="date" name="date_b" class="form-control form-control-sm" value="{{ request('date_b', now()->subYear()->toDateString()) }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Compare</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    <div class="bsc-card">
        <div class="bsc-title">Balance Sheet Comparison</div>
        <div class="bsc-company">{{ config('app.name', 'My Company') }}</div>
        <hr class="bsc-sep">
        <table class="bsc-table">
            <thead>
                <tr>
                    <th style="width:44%">Account</th>
                    <th>{{ $dateA->format('M j, Y') }}</th>
                    <th>{{ $dateB->format('M j, Y') }}</th>
                    <th>Change</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                {{-- Assets --}}
                <tr class="section-head"><td colspan="5">Assets</td></tr>
                <tr>
                    <td>Total Assets</td>
                    <td class="bsc-amount">{{ $fmt($periodA['assets']) }}</td>
                    <td class="bsc-amount">{{ $fmt($periodB['assets']) }}</td>
                    @php $d = $diff($periodA['assets'],$periodB['assets']); $p = $pct($periodA['assets'],$periodB['assets']); @endphp
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $p !== null ? ($p >= 0 ? '+' : '').$p.'%' : '—' }}</td>
                </tr>

                {{-- Liabilities --}}
                <tr class="section-head"><td colspan="5">Liabilities</td></tr>
                <tr>
                    <td>Total Liabilities</td>
                    <td class="bsc-amount">{{ $fmt($periodA['liabilities']) }}</td>
                    <td class="bsc-amount">{{ $fmt($periodB['liabilities']) }}</td>
                    @php $d = $diff($periodA['liabilities'],$periodB['liabilities']); $p = $pct($periodA['liabilities'],$periodB['liabilities']); @endphp
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $p !== null ? ($p >= 0 ? '+' : '').$p.'%' : '—' }}</td>
                </tr>

                {{-- Equity --}}
                <tr class="section-head"><td colspan="5">Equity</td></tr>
                <tr>
                    <td>Retained Earnings</td>
                    <td class="bsc-amount">{{ $fmt($periodA['retained']) }}</td>
                    <td class="bsc-amount">{{ $fmt($periodB['retained']) }}</td>
                    @php $d = $diff($periodA['retained'],$periodB['retained']); $p = $pct($periodA['retained'],$periodB['retained']); @endphp
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $p !== null ? ($p >= 0 ? '+' : '').$p.'%' : '—' }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Equity</td>
                    <td class="bsc-amount">{{ $fmt($periodA['equity']) }}</td>
                    <td class="bsc-amount">{{ $fmt($periodB['equity']) }}</td>
                    @php $d = $diff($periodA['equity'],$periodB['equity']); $p = $pct($periodA['equity'],$periodB['equity']); @endphp
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $p !== null ? ($p >= 0 ? '+' : '').$p.'%' : '—' }}</td>
                </tr>

                {{-- Net --}}
                <tr class="section-head"><td colspan="5">Summary</td></tr>
                <tr class="total-row">
                    <td>Total Liabilities &amp; Equity</td>
                    <td class="bsc-amount">{{ $fmt($periodA['liabilities'] + $periodA['equity']) }}</td>
                    <td class="bsc-amount">{{ $fmt($periodB['liabilities'] + $periodB['equity']) }}</td>
                    @php $d = $diff($periodA['liabilities']+$periodA['equity'], $periodB['liabilities']+$periodB['equity']); @endphp
                    <td class="{{ $d >= 0 ? 'bsc-tag-up' : 'bsc-tag-dn' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="bsc-tag-flat">—</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
