@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @php
            $autoReference = 'CRP-' . now()->format('ymdHis') . '-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT);
            $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
            $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
            $currencySymbol = $geoCurrencySymbol ?? \App\Support\GeoCurrency::currentSymbol();
        @endphp
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Receive Payment</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customers.show', $customer->id) }}">{{ $customer->customer_name }}</a></li>
                        <li class="breadcrumb-item active">Receive Payment</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Customer
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Customer</p>
                        <h4 class="mb-0">{{ $customer->customer_name }}</h4>
                        <small class="text-muted">{{ $customer->phone ?: ($customer->email ?: 'No contact saved') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Outstanding Invoices</p>
                        <h4 class="mb-0">{{ \App\Support\GeoCurrency::format($outstandingInvoicesTotal, 'NGN', $currencyCode, $currencyLocale) }}</h4>
                        <small class="text-muted">{{ $outstandingSales->count() }} open invoice(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Opening Balance Due</p>
                        <h4 class="mb-0">{{ \App\Support\GeoCurrency::format((float) ($openingSnapshot['due'] ?? $outstandingOpeningBalance), 'NGN', $currencyCode, $currencyLocale) }}</h4>
                        <small class="text-muted">
                            Original: {{ \App\Support\GeoCurrency::format((float) ($openingSnapshot['original'] ?? $outstandingOpeningBalance), 'NGN', $currencyCode, $currencyLocale) }}
                            · Paid: {{ \App\Support\GeoCurrency::format((float) ($openingSnapshot['paid'] ?? 0), 'NGN', $currencyCode, $currencyLocale) }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                    <div class="card-body">
                        <p class="mb-1 text-white-50">Total Amount Due</p>
                        <h3 class="mb-0">{{ \App\Support\GeoCurrency::format(($outstandingInvoicesTotal + $outstandingOpeningBalance), 'NGN', $currencyCode, $currencyLocale) }}</h3>
                        <small class="text-white-50">
                            Invoices: {{ \App\Support\GeoCurrency::format($outstandingInvoicesTotal, 'NGN', $currencyCode, $currencyLocale) }}
                            · Opening Balance Due: {{ \App\Support\GeoCurrency::format($outstandingOpeningBalance, 'NGN', $currencyCode, $currencyLocale) }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1">Receive Payment for {{ $customer->customer_name }}</h5>
                <p class="text-muted mb-0">Enter the amount received and save. The system will allocate the payment automatically to outstanding invoices first, then to any opening balance due.</p>
            </div>
            <div class="card-body">
                @php $paymentHistory = $paymentHistory ?? collect(); @endphp
                <form method="POST" action="{{ route('customers.store-receive-payment', $customer->id) }}" id="customerReceivePaymentForm">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Deposit To</label>
                            <select name="payment_target" class="form-select">
                                <option value="">Select bank or account</option>
                                @foreach($paymentDestinations as $destination)
                                    <option value="{{ $destination->value }}" @selected(old('payment_target') === $destination->value)>{{ $destination->label }} ({{ $destination->type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Method</label>
                            <select name="method" class="form-select">
                                @foreach(['Bank Transfer', 'Cash', 'Cheque', 'POS', 'Other'] as $method)
                                    <option value="{{ $method }}" @selected(old('method', 'Bank Transfer') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference No.</label>
                            <input type="text" name="reference" class="form-control" value="{{ old('reference', $autoReference) }}" placeholder="Optional reference">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount Received</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currencySymbol }}</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    name="received_amount"
                                    class="form-control"
                                    value="{{ old('received_amount') }}"
                                    placeholder="0.00"
                                    data-received-amount
                                >
                            </div>
                            <small class="text-muted">This will be automatically allocated to outstanding invoices and then any opening balance.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" rows="2" class="form-control" placeholder="Optional note for this collection">{{ old('note') }}</textarea>
                        </div>
                    </div>





                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">Save Received Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1 d-flex align-items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-primary"></i>
                            Payment History
                        </h5>
                        <p class="text-muted mb-0">Opening balance and collections in running order with account, branch, method and running balance context.</p>
                    </div>
                    <div class="receive-report-stamp">
                        <div><span>Coverage:</span> {{ optional($reportMeta['period_from'] ?? null)->format('d M Y, h:i A') }} to {{ optional($reportMeta['period_to'] ?? null)->format('d M Y, h:i A') }}</div>
                        <div><span>Generated:</span> {{ optional($reportMeta['generated_at'] ?? null)->format('d M Y, h:i A') }}</div>
                        <div><span>Branch:</span> {{ $reportMeta['branch_name'] ?? 'Workspace Default' }}</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="receive-report-overview mb-4">
                    <div class="overview-item">
                        <span class="overview-label"><i class="fas fa-id-badge"></i> Customer Code</span>
                        <strong>{{ $reportMeta['customer_code'] ?? ('CL-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT)) }}</strong>
                    </div>
                    <div class="overview-item">
                        <span class="overview-label"><i class="fas fa-file-invoice"></i> Open Invoices</span>
                        <strong>{{ $reportMeta['open_invoice_count'] ?? $outstandingSales->count() }}</strong>
                    </div>
                    <div class="overview-item">
                        <span class="overview-label"><i class="fas fa-stream"></i> Ledger Rows</span>
                        <strong>{{ $reportMeta['history_count'] ?? ($paymentTimeline ?? collect())->count() }}</strong>
                    </div>
                    <div class="overview-item">
                        <span class="overview-label"><i class="fas fa-university"></i> Latest Account</span>
                        <strong>{{ $reportMeta['latest_account'] ?? 'Not assigned' }}</strong>
                    </div>
                    <div class="overview-item">
                        <span class="overview-label"><i class="fas fa-credit-card"></i> Latest Method</span>
                        <strong>{{ $reportMeta['latest_method'] ?? 'Not specified' }}</strong>
                    </div>
                    <div class="overview-item">
                        <span class="overview-label"><i class="fas fa-scale-balanced"></i> Balance Due</span>
                        <strong>{{ \App\Support\GeoCurrency::format(($outstandingInvoicesTotal + $outstandingOpeningBalance), 'NGN', $currencyCode, $currencyLocale) }}</strong>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 receive-history-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date / Time</th>
                                <th>Entry</th>
                                <th>Reference</th>
                                <th>Payment ID</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Deposit / Account</th>
                                <th>Branch</th>
                                <th>Note / Source</th>
                                <th class="text-end">Invoice</th>
                                <th class="text-end">Payment</th>
                                <th class="text-end">Running Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($paymentTimeline ?? collect()) as $entry)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ optional($entry['date'] ?? null)->format('d M Y') ?: \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}</div>
                                        <small class="text-muted">{{ optional($entry['date'] ?? null)->format('h:i A') ?: \Carbon\Carbon::parse($entry['date'])->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <span class="entry-pill">
                                            <i class="fas {{ $entry['entry_icon'] ?? 'fa-receipt' }}"></i>
                                            {{ $entry['source_label'] ?? 'Ledger Entry' }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">{{ $entry['reference'] }}</td>
                                    <td>{{ $entry['payment_id'] ?? '—' }}</td>
                                    <td>{{ $entry['type'] }}</td>
                                    <td>{{ $entry['method'] ?? '—' }}</td>
                                    <td>{{ $entry['account'] ?? '—' }}</td>
                                    <td>{{ $entry['branch'] ?? '—' }}</td>
                                    <td>
                                        <div>{{ $entry['note'] ?? '—' }}</div>
                                        <small class="text-muted">By {{ $entry['created_by'] ?? 'System' }}</small>
                                    </td>
                                    <td class="text-end fw-semibold text-danger">{{ \App\Support\GeoCurrency::format((float) ($entry['invoice_amount'] ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                    <td class="text-end fw-semibold text-success">{{ \App\Support\GeoCurrency::format((float) ($entry['payment_amount'] ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                    <td class="text-end fw-semibold">
                                        {{ isset($entry['running_balance']) && $entry['running_balance'] !== null ? \App\Support\GeoCurrency::format((float) $entry['running_balance'], 'NGN', $currencyCode, $currencyLocale) : '—' }}
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-{{ $entry['status_class'] ?? 'secondary' }}-subtle text-{{ $entry['status_class'] ?? 'secondary' }}">
                                            {{ $entry['status'] ?? 'Recorded' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-muted py-4">No payment history recorded for this customer yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .receive-report-stamp {
        font-size: 0.76rem;
        line-height: 1.55;
        color: #64748b;
        background: linear-gradient(135deg, #eff6ff, #f8fafc);
        border: 1px solid #dbeafe;
        border-radius: 16px;
        padding: 0.9rem 1rem;
        min-width: 280px;
    }

    .receive-report-stamp span,
    .overview-label {
        color: #334155;
        font-weight: 700;
    }

    .receive-report-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 0.85rem;
    }

    .overview-item {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 0.9rem 1rem;
        background: #f8fbff;
    }

    .overview-item strong {
        display: block;
        margin-top: 0.35rem;
        color: #0f172a;
        font-size: 0.92rem;
    }

    .receive-history-table {
        font-size: 0.82rem;
    }

    .receive-history-table thead th {
        font-size: 0.73rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .receive-history-table tbody td {
        vertical-align: top;
        padding-top: 0.9rem;
        padding-bottom: 0.9rem;
    }

    .entry-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-weight: 700;
        white-space: nowrap;
    }

    .bg-success-subtle { background-color: #dcfce7 !important; }
    .bg-warning-subtle { background-color: #fef3c7 !important; }
    .bg-danger-subtle { background-color: #fee2e2 !important; }
    .bg-info-subtle { background-color: #dbeafe !important; }
    .receive-history-table .text-success { color: #166534 !important; }
    .receive-history-table .text-warning { color: #92400e !important; }
    .receive-history-table .text-danger { color: #991b1b !important; }
    .receive-history-table .text-info { color: #1e3a8a !important; }

    @media (max-width: 991.98px) {
        .receive-report-stamp {
            min-width: auto;
        }

        .receive-history-table {
            font-size: 0.78rem;
        }
    }
</style>

@endsection
