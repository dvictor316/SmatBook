@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
@endphp
<style>
.sbc-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.sbc-table tbody td{font-size:.8rem;vertical-align:middle;}
.sbc-bar{height:8px;border-radius:4px;background:#3b82f6;min-width:4px;transition:width .4s ease;}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Sales by Customer</h5>
                <p class="text-muted mb-0">Revenue breakdown per customer for the selected period.</p>
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
                <table class="table table-sm sbc-table mb-0">
                    <thead><tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th style="min-width:100px">Share</th>
                    </tr></thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        @php $pct = $grandTotal > 0 ? round(($row->total_amount / $grandTotal) * 100, 1) : 0; @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-bold">{{ $row->customer ?? 'Walk‑in' }}</td>
                            <td class="text-center">{{ number_format($row->invoice_count) }}</td>
                            <td class="text-end fw-bold">{{ $fmt($row->total_amount) }}</td>
                            <td class="text-end text-success">{{ $fmt($row->total_paid) }}</td>
                            <td class="text-end text-danger">{{ $fmt($row->total_balance) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="sbc-bar" style="width:{{ $pct }}%"></div>
                                    <span class="small">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No data available for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                    <tfoot><tr class="table-active fw-bold">
                        <td colspan="3" class="text-end">Grand Total</td>
                        <td class="text-end">{{ $fmt($grandTotal) }}</td>
                        <td class="text-end text-success">{{ $fmt(collect($rows)->sum('total_paid')) }}</td>
                        <td class="text-end text-danger">{{ $fmt(collect($rows)->sum('total_balance')) }}</td>
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
