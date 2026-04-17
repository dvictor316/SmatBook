@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
@endphp
<style>
.sbp-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.sbp-table tbody td{font-size:.8rem;vertical-align:middle;}
.sbp-bar{height:8px;border-radius:4px;background:#8b5cf6;min-width:4px;}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Sales by Product</h5>
                <p class="text-muted mb-0">Revenue and quantity sold per product for the period.</p>
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
                <table class="table table-sm sbp-table mb-0">
                    <thead><tr>
                        <th>#</th>
                        <th>Product</th>
                        <th class="text-end">Qty Sold</th>
                        <th class="text-end">Revenue</th>
                        <th style="min-width:120px">Revenue Share</th>
                    </tr></thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        @php $pct = $grandTotal > 0 ? round(($row->revenue / $grandTotal) * 100, 1) : 0; @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-bold">{{ $row->product ?? 'Unknown' }}</td>
                            <td class="text-end">{{ number_format($row->qty_sold, 0) }}</td>
                            <td class="text-end fw-bold text-success">{{ $fmt($row->revenue) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="sbp-bar" style="width:{{ $pct }}%"></div>
                                    <span class="small">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No sales data available for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                    <tfoot><tr class="table-active fw-bold">
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-end">{{ number_format(collect($rows)->sum('qty_sold'), 0) }}</td>
                        <td class="text-end text-success">{{ $fmt($grandTotal) }}</td>
                        <td></td>
                    </tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
