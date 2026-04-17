@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
    $maxVal = max(array_filter((array)$months, fn($v) => $v > 0) ?: [1]);
    $allYears = range(now()->year, now()->year - 4);
@endphp
<style>
.et-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.et-table tbody td{font-size:.8rem;vertical-align:middle;}
.et-bar-wrap{background:#f1f5f9;border-radius:4px;height:24px;overflow:hidden;min-width:60px;position:relative;}
.et-bar-fill{height:24px;border-radius:4px;background:#ef4444;display:flex;align-items:center;justify-content:center;}
.et-bar-lbl{font-size:.6rem;color:#fff;font-weight:700;white-space:nowrap;padding:0 4px;}
.et-kpi{flex:1;min-width:120px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.75rem 1rem;text-align:center;}
.et-kpi-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:3px;}
.et-kpi-val{font-size:.9rem;font-weight:800;font-variant-numeric:tabular-nums;}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Expense Trend</h5>
                <p class="text-muted mb-0">Month-by-month expense analysis for {{ $year }}.</p>
            </div>
            <div class="list-btn"><ul class="filter-list">
                <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
            </ul></div>
        </div>
    </div>

    <div class="card shadow-none border mb-3 no-print">
        <div class="card-body p-2">
            <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-end gap-2 flex-wrap">
                <div>
                    <label class="form-label small fw-bold mb-1">Year</label>
                    <select name="year" class="form-select form-select-sm">
                        @foreach($allYears as $y)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </form>
        </div>
    </div>

    {{-- KPI summary --}}
    @php
        $nonZero = array_filter((array)$months, fn($v) => $v > 0);
        $avgMonthly = count($nonZero) > 0 ? $grandTotal / count($nonZero) : 0;
        $peakMonth  = array_search(max((array)$months), (array)$months);
    @endphp
    <div class="d-flex gap-2 flex-wrap mb-4">
        <div class="et-kpi"><div class="et-kpi-label">Annual Total</div><div class="et-kpi-val" style="color:#ef4444">{{ $fmt($grandTotal) }}</div></div>
        <div class="et-kpi"><div class="et-kpi-label">Monthly Average</div><div class="et-kpi-val" style="color:#f97316">{{ $fmt($avgMonthly) }}</div></div>
        <div class="et-kpi"><div class="et-kpi-label">Peak Month</div><div class="et-kpi-val" style="color:#7c3aed">{{ $monthNames[$peakMonth] ?? '—' }}</div></div>
    </div>

    <div class="card shadow-none border">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm et-table mb-0">
                    <thead><tr>
                        <th>Month</th>
                        <th class="text-end">Amount</th>
                        <th style="min-width:200px">Bar Chart</th>
                        <th class="text-end">% of Annual</th>
                    </tr></thead>
                    <tbody>
                        @foreach($monthNames as $num => $name)
                        @php
                            $val = (float)($months[$num] ?? 0);
                            $pct = $grandTotal > 0 ? round(($val / $grandTotal) * 100, 1) : 0;
                            $barPct = $maxVal > 0 ? round(($val / $maxVal) * 100) : 0;
                        @endphp
                        <tr class="{{ $num == $peakMonth ? 'table-warning' : '' }}">
                            <td class="fw-bold">{{ $name }}</td>
                            <td class="text-end fw-semibold {{ $val > 0 ? 'text-danger' : 'text-muted' }}">{{ $val > 0 ? $fmt($val) : '—' }}</td>
                            <td>
                                @if($val > 0)
                                <div class="et-bar-wrap"><div class="et-bar-fill" style="width:{{ $barPct }}%"><span class="et-bar-lbl">{{ $pct }}%</span></div></div>
                                @else
                                <span class="text-muted small">No data</span>
                                @endif
                            </td>
                            <td class="text-end small">{{ $val > 0 ? $pct.'%' : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="table-active fw-bold">
                        <td>Annual Total</td>
                        <td class="text-end text-danger">{{ $fmt($grandTotal) }}</td>
                        <td></td>
                        <td class="text-end">100%</td>
                    </tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
