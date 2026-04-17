@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $bucketColors = ['0–30'=>'#22c55e','31–60'=>'#f59e0b','61–90'=>'#f97316','90+'=>'#ef4444'];
@endphp
<style>
.ar-badge{display:inline-block;font-size:.6rem;font-weight:800;padding:2px 8px;border-radius:999px;white-space:nowrap;}
.ar-bucket-card{flex:1;min-width:130px;border-radius:14px;padding:.75rem;text-align:center;border:1px solid transparent;}
.ar-bucket-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px;}
.ar-bucket-val{font-size:.88rem;font-weight:800;font-variant-numeric:tabular-nums;}
.ar-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.ar-table tbody td{font-size:.79rem;vertical-align:middle;}
@media(max-width:768px){.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Accounts Receivable Ageing Detail</h5>
                <p class="text-muted mb-0">Outstanding invoice ageing as of a given date.</p>
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
                    <label class="form-label small fw-bold mb-1">As Of Date</label>
                    <input type="date" name="as_of" class="form-control form-control-sm" value="{{ $asOf }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap mb-4">
        @foreach($buckets as $label => $amount)
        @php $color = $bucketColors[$label] ?? '#94a3b8'; @endphp
        <div class="ar-bucket-card" style="background:{{ $color }}15;border-color:{{ $color }}40;">
            <div class="ar-bucket-label" style="color:{{ $color }}">{{ $label }} days</div>
            <div class="ar-bucket-val" style="color:{{ $color }}">{{ $fmt($amount) }}</div>
        </div>
        @endforeach
        <div class="ar-bucket-card" style="background:#0f172a15;border-color:#0f172a40;">
            <div class="ar-bucket-label" style="color:#0f172a">Total Due</div>
            <div class="ar-bucket-val" style="color:#0f172a">{{ $fmt($totalDue) }}</div>
        </div>
    </div>

    <div class="card shadow-none border">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm ar-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice No.</th>
                            <th>Customer</th>
                            <th>Invoice Date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center">Age (days)</th>
                            <th class="text-center">Bucket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        @php
                            $bc = $bucketColors[$row->bucket] ?? '#94a3b8';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-bold">{{ $row->invoice_no ?? '—' }}</td>
                            <td>{{ $row->customer ?? 'Walk-in' }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->sale_date)->format('M d, Y') }}</td>
                            <td class="text-end">{{ $fmt($row->total) }}</td>
                            <td class="text-end text-success">{{ $fmt($row->paid) }}</td>
                            <td class="text-end fw-bold text-danger">{{ $fmt($row->balance) }}</td>
                            <td class="text-center">{{ $row->age_days }}</td>
                            <td class="text-center"><span class="ar-badge" style="background:{{ $bc }}20;color:{{ $bc }}">{{ $row->bucket }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No outstanding invoices found.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                    <tfoot><tr>
                        <td colspan="4" class="fw-bold text-end">Totals</td>
                        <td class="text-end fw-bold">{{ $fmt(collect($rows)->sum('total')) }}</td>
                        <td class="text-end fw-bold text-success">{{ $fmt(collect($rows)->sum('paid')) }}</td>
                        <td class="text-end fw-bold text-danger">{{ $fmt($totalDue) }}</td>
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
