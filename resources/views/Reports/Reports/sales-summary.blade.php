@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $statuses = ['paid'=>'success','partial'=>'warning','unpaid'=>'danger'];
@endphp
<style>
.ss-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.8rem 1rem;text-align:center;}
.ss-kpi-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:3px;}
.ss-kpi-val{font-size:.9rem;font-weight:800;font-variant-numeric:tabular-nums;}
.ss-status-row{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;}
.ss-status-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.5rem .9rem;display:flex;flex-direction:column;align-items:center;min-width:90px;}
.ss-status-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;}
.ss-status-val{font-size:.82rem;font-weight:800;}
@media(max-width:768px){.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media(max-width:576px){.ss-kpi{min-width:100%;}.ss-table thead th,.ss-table tbody td{font-size:.65rem;}}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Sales Summary</h5>
                <p class="text-muted mb-0">High-level sales overview for the selected period.</p>
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
        <div class="ss-kpi"><div class="ss-kpi-label">Total Sales</div><div class="ss-kpi-val" style="color:#3b82f6">{{ $fmt($totalSales) }}</div></div>
        <div class="ss-kpi"><div class="ss-kpi-label">Total Paid</div><div class="ss-kpi-val" style="color:#16a34a">{{ $fmt($totalPaid) }}</div></div>
        <div class="ss-kpi"><div class="ss-kpi-label">Outstanding</div><div class="ss-kpi-val" style="color:#dc2626">{{ $fmt($totalBalance) }}</div></div>
        <div class="ss-kpi"><div class="ss-kpi-label">Invoice Count</div><div class="ss-kpi-val" style="color:#7c3aed">{{ number_format($totalCount) }}</div></div>
        <div class="ss-kpi"><div class="ss-kpi-label">Average Sale</div><div class="ss-kpi-val" style="color:#0891b2">{{ $fmt($avgSale) }}</div></div>
    </div>

    <div class="card shadow-none border mb-3">
        <div class="card-header bg-white py-2"><h6 class="mb-0 fw-bold">Breakdown by Payment Status</h6></div>
        <div class="card-body">
            <div class="ss-status-row">
                @foreach($byStatus as $status => $vals)
                @php $sc = $statuses[$status] ?? 'secondary'; @endphp
                <div class="ss-status-item">
                    <span class="ss-status-label">{{ ucfirst($status) }}</span>
                    <span class="ss-status-val text-{{ $sc }}">{{ number_format($vals['count']) }}</span>
                    <span class="small text-muted">{{ $fmt($vals['total']) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-none border">
        <div class="card-header bg-white py-2"><h6 class="mb-0 fw-bold">Period Summary</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><td class="fw-semibold ps-3">Period</td><td class="text-end pe-3">{{ $from }} — {{ $to }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Total Sales Amount</td><td class="text-end pe-3 fw-bold">{{ $fmt($totalSales) }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Total Collected</td><td class="text-end pe-3 text-success fw-bold">{{ $fmt($totalPaid) }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Total Outstanding</td><td class="text-end pe-3 text-danger fw-bold">{{ $fmt($totalBalance) }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Number of Invoices</td><td class="text-end pe-3">{{ number_format($totalCount) }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Average Sale Value</td><td class="text-end pe-3">{{ $fmt($avgSale) }}</td></tr>
                    <tr><td class="fw-semibold ps-3">Collection Rate</td>
                        <td class="text-end pe-3">
                            @php $rate = $totalSales > 0 ? round(($totalPaid / $totalSales) * 100, 1) : 0; @endphp
                            <span class="badge" style="background:{{ $rate >= 80 ? '#dcfce7' : ($rate >= 50 ? '#fef9c3' : '#fee2e2') }};color:{{ $rate >= 80 ? '#16a34a' : ($rate >= 50 ? '#a16207' : '#dc2626') }};font-size:.75rem;">{{ $rate }}%</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
