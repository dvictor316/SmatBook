@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $palette = ['#ef4444','#f97316','#f59e0b','#22c55e','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#6366f1','#e11d48','#0ea5e9','#84cc16'];
@endphp
<style>
.ebc-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.ebc-table tbody td{font-size:.8rem;vertical-align:middle;}
.ebc-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;}
.ebc-bar-wrap{background:#f1f5f9;border-radius:4px;height:10px;overflow:hidden;min-width:60px;}
.ebc-bar-fill{height:10px;border-radius:4px;}
@media(max-width:768px){.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media(max-width:576px){.ebc-kpi{min-width:100%;}.ebc-table thead th,.ebc-table tbody td{font-size:.65rem;}}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Expenses by Category</h5>
                <p class="text-muted mb-0">Expense breakdown per category for the selected period.</p>
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

    <div class="card shadow-none border">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm ebc-table mb-0">
                    <thead><tr>
                        <th>#</th>
                        <th>Category</th>
                        <th class="text-center">Transactions</th>
                        <th class="text-end">Total</th>
                        <th style="min-width:140px">Visual</th>
                        <th class="text-end">% of Total</th>
                    </tr></thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        @php
                            $color = $palette[$i % count($palette)];
                            $pct = $grandTotal > 0 ? round(($row->total / $grandTotal) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><span class="ebc-dot" style="background:{{ $color }}"></span><span class="fw-bold">{{ $row->category ?? 'Uncategorised' }}</span></td>
                            <td class="text-center">{{ number_format($row->cnt) }}</td>
                            <td class="text-end fw-bold text-danger">{{ $fmt($row->total) }}</td>
                            <td>
                                <div class="ebc-bar-wrap"><div class="ebc-bar-fill" style="width:{{ $pct }}%;background:{{ $color }}"></div></div>
                            </td>
                            <td class="text-end small">{{ $pct }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No expense data for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                    <tfoot><tr class="table-active fw-bold">
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-center">{{ number_format(collect($rows)->sum('cnt')) }}</td>
                        <td class="text-end text-danger">{{ $fmt($grandTotal) }}</td>
                        <td colspan="2"></td>
                    </tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
