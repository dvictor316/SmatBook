@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
    $statusColor = ['paid'=>'success','partial'=>'warning','unpaid'=>'danger','cancelled'=>'secondary'];
@endphp
<style>
.oi-kpi-strip{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.2rem;}
.oi-kpi{flex:1;min-width:130px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.75rem 1rem;text-align:center;}
.oi-kpi-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:3px;}
.oi-kpi-val{font-size:.9rem;font-weight:800;font-variant-numeric:tabular-nums;}
.oi-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;background:#f8fafc;}
.oi-table tbody td{font-size:.79rem;vertical-align:middle;}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Open Invoices Report</h5>
                <p class="text-muted mb-0">All unpaid and partially paid invoices within the period.</p>
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
                    <label class="form-label small fw-bold mb-1">From</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1">To</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    <div class="oi-kpi-strip">
        <div class="oi-kpi"><div class="oi-kpi-label">Open Invoices</div><div class="oi-kpi-val" style="color:#3b82f6">{{ number_format($totalCount) }}</div></div>
        <div class="oi-kpi"><div class="oi-kpi-label">Total Outstanding</div><div class="oi-kpi-val" style="color:#dc2626">{{ $fmt($totalOpen) }}</div></div>
    </div>

    <div class="card shadow-none border">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm oi-table mb-0">
                    <thead><tr>
                        <th>#</th>
                        <th>Invoice No.</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Status</th>
                    </tr></thead>
                    <tbody>
                        @forelse($invoices as $i => $inv)
                        @php $sc = $statusColor[$inv->payment_status] ?? 'secondary'; @endphp
                        <tr>
                            <td>{{ $invoices->firstItem() + $i }}</td>
                            <td class="fw-bold">{{ $inv->invoice_no ?? '#'.$inv->id }}</td>
                            <td>{{ $inv->customer_name ?? 'Walk‑in' }}</td>
                            <td>{{ \Carbon\Carbon::parse($inv->sale_date)->format('M d, Y') }}</td>
                            <td class="text-end">{{ $fmt($inv->total) }}</td>
                            <td class="text-end text-success">{{ $fmt($inv->amount_paid) }}</td>
                            <td class="text-end fw-bold text-danger">{{ $fmt($inv->balance) }}</td>
                            <td class="text-center"><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} fw-bold" style="font-size:.62rem">{{ ucfirst($inv->payment_status) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No open invoices found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer border-0 bg-white py-2">{{ $invoices->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
