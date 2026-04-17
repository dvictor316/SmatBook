@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $monthNames = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $totalIncome  = array_sum(array_column(is_array($months[1] ?? null) ? $months : array_map(fn($v)=>['income'=>0,'expense'=>0,'net'=>0], $months), 'income'));
    $totalExpense = array_sum(array_column(is_array($months[1] ?? null) ? $months : array_map(fn($v)=>['income'=>0,'expense'=>0,'net'=>0], $months), 'expense'));
    $totalNet     = array_sum(array_column(is_array($months[1] ?? null) ? $months : array_map(fn($v)=>['income'=>0,'expense'=>0,'net'=>0], $months), 'net'));
@endphp
<style>
.plm-card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 8px 24px rgba(15,23,42,.06);padding:1.5rem 1.75rem;margin-bottom:24px;overflow-x:auto;}
.plm-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#2563eb;text-align:center;}
.plm-company{font-size:1.2rem;font-weight:800;color:#0f172a;text-align:center;letter-spacing:-.02em;}
.plm-sep{border:0;border-top:1px solid #e2e8f0;margin:1.1rem 0;}
.plm-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.75rem;min-width:900px;}
.plm-table th{font-size:.63rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b;padding:.6rem .5rem;background:#f8fafc;text-align:right;white-space:nowrap;}
.plm-table th:first-child{text-align:left;min-width:120px;}
.plm-table td{padding:.4rem .5rem;border-bottom:1px solid #f1f5f9;text-align:right;font-variant-numeric:tabular-nums;}
.plm-table td:first-child{text-align:left;color:#334155;font-weight:600;}
.plm-table tr.total-row td{font-weight:800;background:#f0f7ff;}
.neg{color:#dc2626;}
.pos{color:#16a34a;}
@media print{.no-print{display:none!important;}.plm-card{box-shadow:none!important;border:1px solid #ccc!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Profit &amp; Loss by Month</h5>
                <p class="text-muted mb-0">Monthly breakdown of income, expenses, and net profit.</p>
            </div>
            <div class="list-btn"><ul class="filter-list">
                <li><a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()"><i class="feather-printer me-1"></i> Print</a></li>
            </ul></div>
        </div>
    </div>

    <div class="card shadow-none border mb-3 no-print">
        <div class="card-body p-2">
            <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-end gap-3">
                <div>
                    <label class="form-label small fw-bold mb-1">Year</label>
                    <select name="year" class="form-control form-control-sm" style="width:120px;">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    <div class="plm-card">
        <div class="plm-title">Profit &amp; Loss by Month — {{ $year }}</div>
        <div class="plm-company">{{ config('app.name', 'My Company') }}</div>
        <hr class="plm-sep">
        <table class="plm-table">
            <thead>
                <tr>
                    <th>Line Item</th>
                    @for($m=1;$m<=12;$m++)<th>{{ $monthNames[$m] }}</th>@endfor
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Income</td>
                    @for($m=1;$m<=12;$m++)<td>{{ $fmt($months[$m]['income']) }}</td>@endfor
                    <td><strong>{{ $fmt($totalIncome) }}</strong></td>
                </tr>
                <tr>
                    <td>Expenses</td>
                    @for($m=1;$m<=12;$m++)<td>{{ $fmt($months[$m]['expense']) }}</td>@endfor
                    <td><strong>{{ $fmt($totalExpense) }}</strong></td>
                </tr>
                <tr class="total-row">
                    <td>Net Profit</td>
                    @for($m=1;$m<=12;$m++)
                        @php $n = $months[$m]['net']; @endphp
                        <td class="{{ $n < 0 ? 'neg' : ($n > 0 ? 'pos' : '') }}">{{ $fmt($n) }}</td>
                    @endfor
                    <td class="{{ $totalNet < 0 ? 'neg' : 'pos' }}"><strong>{{ $fmt($totalNet) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
