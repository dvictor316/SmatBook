@extends('layout.mainlayout')
@section('content')
@php
    $currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $fmt = fn($n) => \App\Support\GeoCurrency::format((float)$n, 'NGN', $currencyCode, $currencyLocale);
@endphp
<style>
.pld-badge-income{background:#f0fdf4;color:#16a34a;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:999px;}
.pld-badge-expense{background:#fef2f2;color:#dc2626;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:999px;}
.pld-table thead th{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;background:#f8fafc;color:#0f172a;}
.pld-table tbody td{font-size:.8rem;vertical-align:middle;}
.pld-summary-strip{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;}
.pld-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.8rem 1rem;text-align:center;}
.pld-kpi-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:3px;}
.pld-kpi-val{font-size:.88rem;font-weight:800;font-variant-numeric:tabular-nums;}
@media(max-width:768px){.content-page-header{flex-direction:column;align-items:flex-start;gap:.5rem;}.list-btn{width:100%;}.list-btn .filter-list{flex-wrap:wrap;}}
@media(max-width:576px){.pld-kpi{min-width:100%;}.pld-table thead th,.pld-table tbody td{font-size:.7rem;}}
@media print{.no-print{display:none!important;}}
</style>

<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header no-print">
        <div class="content-page-header">
            <div>
                <h5>Profit &amp; Loss Detail</h5>
                <p class="text-muted mb-0">Line-by-line income and expense transactions for the period.</p>
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

    <div class="pld-summary-strip">
        <div class="pld-kpi"><div class="pld-kpi-label">Total Income</div><div class="pld-kpi-val" style="color:#16a34a">{{ $fmt($totalIncome) }}</div></div>
        <div class="pld-kpi"><div class="pld-kpi-label">Total Expenses</div><div class="pld-kpi-val" style="color:#dc2626">{{ $fmt($totalExpense) }}</div></div>
        <div class="pld-kpi"><div class="pld-kpi-label">Net Profit</div><div class="pld-kpi-val" style="color:{{ $netProfit >= 0 ? '#16a34a' : '#dc2626' }}">{{ $fmt($netProfit) }}</div></div>
    </div>

    @if($salesRows->count())
    <div class="card shadow-none border mb-4">
        <div class="card-header bg-white py-2"><h6 class="mb-0 fw-bold text-success"><i class="fas fa-arrow-up me-1"></i> Income Transactions</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm pld-table mb-0">
                    <thead><tr><th>#</th><th>Date</th><th>Reference</th><th>Customer</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach($salesRows as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->txn_date)->format('M d, Y') }}</td>
                            <td>{{ $row->reference ?? '—' }}</td>
                            <td>{{ $row->party ?? 'Walk-in' }}</td>
                            <td class="text-end fw-bold text-success">{{ $fmt($row->amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="4" class="fw-bold text-end">Total Income</td><td class="text-end fw-bold text-success">{{ $fmt($totalIncome) }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($expenseRows->count())
    <div class="card shadow-none border mb-4">
        <div class="card-header bg-white py-2"><h6 class="mb-0 fw-bold text-danger"><i class="fas fa-arrow-down me-1"></i> Expense Transactions</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm pld-table mb-0">
                    <thead><tr><th>#</th><th>Date</th><th>Reference</th><th>Payee</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach($expenseRows as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->txn_date)->format('M d, Y') }}</td>
                            <td>{{ $row->reference ?? '—' }}</td>
                            <td>{{ $row->party ?? 'N/A' }}</td>
                            <td class="text-end fw-bold text-danger">{{ $fmt($row->amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="4" class="fw-bold text-end">Total Expenses</td><td class="text-end fw-bold text-danger">{{ $fmt($totalExpense) }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if(!$salesRows->count() && !$expenseRows->count())
    <div class="text-center text-muted py-5"><i class="fas fa-search fa-2x mb-3 d-block" style="color:#cbd5e1;"></i>No transactions found for the selected period.</div>
    @endif
</div>
</div>
@endsection
