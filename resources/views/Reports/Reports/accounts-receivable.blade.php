@extends('layout.mainlayout')

@section('content')
    <style>
        .receivable-summary-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }
        .receivable-summary-card h6 {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .receivable-table-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
        }
        .receivable-table-card .table thead th {
            background: #f8fafc;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.08em;
        }
    </style>
    @php
        $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
        $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header no-print">
                <div class="content-page-header">
                    <div>
                        <h5>{{ __('Accounts Receivable') }}</h5>
                        <p class="text-muted mb-0">Outstanding customer balances from unpaid invoices and opening credit balances.</p>
                    </div>
                    <div class="list-btn">
                        <ul class="filter-list">
                            <li>
                                <a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()">
                                    <i class="feather-printer me-1"></i> {{ __('Print') }}
                                </a>
                            </li>
                            <li>
                                <button id="export_excel" class="btn btn-success w-auto">
                                    <i class="feather-file me-1"></i> {{ __('Excel') }}
                                </button>
                            </li>
                            <li>
                                <button id="export_pdf" class="btn btn-danger w-auto">
                                    <i class="feather-file-text me-1"></i> {{ __('PDF') }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card receivable-summary-card bg-primary-light">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-primary">Total Receivable</h6>
                                <h5>{{ \App\Support\GeoCurrency::format($totalDue ?? 0, 'NGN', $currencyCode, $currencyLocale) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card receivable-summary-card bg-warning-light">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-warning">Customers Owing</h6>
                                <h5>{{ number_format($receivables->count()) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card receivable-summary-card bg-success-light">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-success">Active Branch</h6>
                                <h5>{{ $activeBranch['name'] ?? 'All Branches' }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card receivable-table-card">
                        <div class="card-body">
                            <form method="GET" action="{{ route('reports.accounts-receivable') }}" class="row g-3 mb-3 no-print">
                                <div class="col-md-3">
                                    <label class="form-label">Search Customer</label>
                                    <input type="text" name="customer_name" class="form-control" placeholder="Customer name..." value="{{ $filters['customer_name'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Filter Type</label>
                                    <select name="type" class="form-select">
                                        <option value="all" {{ ($filters['type'] ?? 'all') === 'all' ? 'selected' : '' }}>All Receivables</option>
                                        <option value="invoices" {{ ($filters['type'] ?? '') === 'invoices' ? 'selected' : '' }}>Customers With Invoices</option>
                                        <option value="opening" {{ ($filters['type'] ?? '') === 'opening' ? 'selected' : '' }}>Opening Balance Only</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                                    @if(($filters['customer_name'] ?? '') !== '' || ($filters['type'] ?? 'all') !== 'all' || ($filters['start_date'] ?? '') !== '' || ($filters['end_date'] ?? '') !== '')
                                        <a href="{{ route('reports.accounts-receivable') }}" class="btn btn-outline-secondary">Clear</a>
                                    @endif
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="ar-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th class="text-center">Invoices</th>
                                            <th class="text-end">Invoiced</th>
                                            <th class="text-end">Paid</th>
                                            <th class="text-end">Opening Balance</th>
                                            <th class="text-end">Balance Due</th>
                                            <th class="text-end">Running Total</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $runningTotal = 0; @endphp
                                        @forelse ($receivables as $row)
                                            @php $runningTotal += (float) ($row->total_due + $row->opening_balance); @endphp
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="fw-semibold">{{ $row->customer_name }}</td>
                                                <td>
                                                    <div>{{ $row->email ?: 'No email' }}</div>
                                                    <small class="text-muted">{{ $row->phone ?: 'No phone' }}</small>
                                                </td>
                                                <td class="text-center">{{ number_format($row->invoice_count) }}</td>
                                                <td class="text-end">{{ \App\Support\GeoCurrency::format($row->total_invoiced, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                <td class="text-end">{{ \App\Support\GeoCurrency::format($row->total_paid, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                <td class="text-end">{{ \App\Support\GeoCurrency::format($row->opening_balance, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                <td class="text-end fw-bold text-danger">{{ \App\Support\GeoCurrency::format($row->total_due + $row->opening_balance, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ \App\Support\GeoCurrency::format($runningTotal, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <a href="{{ route('customers.receive-payment', $row->customer_id) }}" class="btn btn-sm btn-primary">Receive Payment</a>
                                                        <a href="{{ route('reports.customer-statement', $row->customer_id) }}" class="btn btn-sm btn-outline-primary">View Statement</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">No outstanding customer balances found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
<script>
document.getElementById('export_excel').addEventListener('click', function() {
    const table = document.getElementById('ar-table');
    const wb = XLSX.utils.table_to_book(table, { sheet: 'AccountsReceivable' });
    XLSX.writeFile(wb, 'Accounts_Receivable_{{ date("Y-m-d") }}.xlsx');
});
document.getElementById('export_pdf').addEventListener('click', function() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');
    doc.setFontSize(14);
    doc.text('Accounts Receivable', 40, 40);
    doc.setFontSize(9);
    doc.text('Generated: {{ now()->format("d M Y H:i") }}', 40, 56);
    doc.autoTable({
        html: '#ar-table',
        startY: 70,
        styles: { fontSize: 7, cellPadding: 3 },
        headStyles: { fillColor: [37, 99, 235], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [248, 250, 252] },
        margin: { left: 40, right: 40 },
    });
    doc.save('Accounts_Receivable_{{ date("Y-m-d") }}.pdf');
});
</script>
@endpush
