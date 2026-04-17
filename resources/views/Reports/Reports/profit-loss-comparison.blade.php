@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $diff = fn($a, $b) => (float)$a - (float)$b;
    $pct  = fn($a, $b) => $b != 0 ? round(((float)$a - (float)$b) / abs((float)$b) * 100, 1) : null;
    $netA = $periodA['net'] ?? 0;
    $netB = $periodB['net'] ?? 0;
@endphp
<style>
.plc-card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 8px 24px rgba(15,23,42,.06);padding:1.5rem 1.75rem;max-width:900px;margin:0 auto 24px;}
.plc-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#2563eb;text-align:center;}
.plc-company{font-size:1.2rem;font-weight:800;color:#0f172a;text-align:center;letter-spacing:-.02em;}
.plc-sep{border:0;border-top:1px solid #e2e8f0;margin:1.1rem 0;}
.plc-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.82rem;}
.plc-table th{font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b;padding:.6rem .7rem;background:#f8fafc;text-align:right;}
.plc-table th:first-child{text-align:left;}
.plc-table td{padding:.45rem .7rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:right;}
.plc-table td:first-child{text-align:left;color:#334155;font-weight:500;}
.plc-table tr.section-head td{font-size:.67rem;font-weight:800;text-transform:uppercase;color:#2563eb;padding-top:.9rem;padding-bottom:.2rem;border-bottom:none;}
.plc-table tr.total-row td{font-weight:800;background:#f0f7ff;border-radius:6px;}
.plc-up{color:#16a34a;font-size:.72rem;font-weight:700;}
.plc-dn{color:#dc2626;font-size:.72rem;font-weight:700;}
.plc-flat{color:#94a3b8;font-size:.72rem;}
@media(max-width:768px){.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media(max-width:576px){.plc-card{padding:1rem;border-radius:12px;}}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Profit &amp; Loss Comparison</h5>
                <p class="text-muted mb-0">Compare income, expenses, and net profit across two date ranges.</p>
            </div>
            <div class="list-btn"><ul class="filter-list">
                <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
            </ul></div>
        </div>
    </div>

    <div class="card shadow-none border mb-3 no-print">
        <div class="card-body p-2">
            <form action="{{ url()->current() }}" method="GET" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="form-label small fw-bold mb-1">Period A From</label>
                    <input type="date" name="from_a" class="form-control form-control-sm" value="{{ $fromA }}">
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1">Period A To</label>
                    <input type="date" name="to_a" class="form-control form-control-sm" value="{{ $toA }}">
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1">Period B From</label>
                    <input type="date" name="from_b" class="form-control form-control-sm" value="{{ $fromB }}">
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1">Period B To</label>
                    <input type="date" name="to_b" class="form-control form-control-sm" value="{{ $toB }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Compare</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    <div class="plc-card">
        <div class="plc-title">Profit &amp; Loss Comparison</div>
        <div class="plc-company">{{ config('app.name', 'My Company') }}</div>
        <hr class="plc-sep">
        <table class="plc-table">
            <thead>
                <tr>
                    <th style="width:40%">Line Item</th>
                    <th>{{ $fromA }} – {{ $toA }}</th>
                    <th>{{ $fromB }} – {{ $toB }}</th>
                    <th>Change</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                <tr class="section-head"><td colspan="5">Income</td></tr>
                <tr>
                    <td>Total Income</td>
                    @php $d = $diff($periodA['income'],$periodB['income']); $p = $pct($periodA['income'],$periodB['income']); @endphp
                    <td>{{ $fmt($periodA['income']) }}</td>
                    <td>{{ $fmt($periodB['income']) }}</td>
                    <td class="{{ $d >= 0 ? 'plc-up' : 'plc-dn' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="{{ $d >= 0 ? 'plc-up' : 'plc-dn' }}">{{ $p !== null ? ($p >= 0 ? '+' : '').$p.'%' : '—' }}</td>
                </tr>

                <tr class="section-head"><td colspan="5">Expenses</td></tr>
                <tr>
                    <td>Total Expenses</td>
                    @php $d = $diff($periodA['expense'],$periodB['expense']); $p = $pct($periodA['expense'],$periodB['expense']); @endphp
                    <td>{{ $fmt($periodA['expense']) }}</td>
                    <td>{{ $fmt($periodB['expense']) }}</td>
                    <td class="{{ $d >= 0 ? 'plc-dn' : 'plc-up' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="{{ $d >= 0 ? 'plc-dn' : 'plc-up' }}">{{ $p !== null ? ($p >= 0 ? '+' : '').$p.'%' : '—' }}</td>
                </tr>

                <tr class="section-head"><td colspan="5">Net Profit</td></tr>
                <tr class="total-row">
                    <td>Net Profit / (Loss)</td>
                    @php $d = $diff($netA,$netB); $p = $pct($netA,$netB); @endphp
                    <td style="color:{{ $netA >= 0 ? '#16a34a' : '#dc2626' }}">{{ $fmt($netA) }}</td>
                    <td style="color:{{ $netB >= 0 ? '#16a34a' : '#dc2626' }}">{{ $fmt($netB) }}</td>
                    <td class="{{ $d >= 0 ? 'plc-up' : 'plc-dn' }}">{{ $d >= 0 ? '+' : '' }}{{ $fmt($d) }}</td>
                    <td class="{{ $d >= 0 ? 'plc-up' : 'plc-dn' }}">{{ $p !== null ? ($p >= 0 ? '+' : '').$p.'%' : '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
