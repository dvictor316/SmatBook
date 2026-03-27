@extends('layout.mainlayout')

@section('content')
    <style>
        .statement-summary-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }
        .statement-table-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
        }
        .statement-table-card .table thead th {
            background: #f8fafc;
            font-size: 0.74rem;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.08em;
        }
        .statement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }
    </style>
    @php
        $currencySymbol = $customer->currency ?: '₦';
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="content-page-header statement-header">
                    <div>
                        <h5>{{ __('Customer Statement') }}</h5>
                        <p class="text-muted mb-0">Full ledger of invoices and payments for {{ $customer->customer_name }}.</p>
                    </div>
                    <div class="list-btn">
                        <ul class="filter-list">
                            <li>
                                <a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()">
                                    <i class="feather-printer me-1"></i> {{ __('Print') }}
                                </a>
                            </li>
                            <li>
                                <a class="btn btn-primary w-auto" href="{{ route('reports.accounts-receivable') }}">
                                    <i class="feather-arrow-left me-1"></i> {{ __('Back to Receivables') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card statement-summary-card bg-primary-light">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-primary">Total Invoiced</h6>
                                <h5>{{ $currencySymbol }}{{ number_format($totalInvoiced ?? 0, 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card statement-summary-card bg-success-light">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-success">Total Paid</h6>
                                <h5>{{ $currencySymbol }}{{ number_format($totalPaid ?? 0, 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card statement-summary-card bg-warning-light">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-warning">Balance Due</h6>
                                <h5>{{ $currencySymbol }}{{ number_format($balanceDue ?? 0, 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card statement-table-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            <th class="text-end">Running Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($entries as $entry)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                                                <td class="fw-semibold">{{ $entry['reference'] }}</td>
                                                <td>{{ $entry['type'] }}</td>
                                                <td>{{ $entry['description'] }}</td>
                                                <td class="text-end">{{ $entry['debit'] > 0 ? $currencySymbol . number_format($entry['debit'], 2) : '-' }}</td>
                                                <td class="text-end">{{ $entry['credit'] > 0 ? $currencySymbol . number_format($entry['credit'], 2) : '-' }}</td>
                                                <td class="text-end fw-bold">{{ $currencySymbol }}{{ number_format($entry['balance'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">No transactions recorded for this customer yet.</td>
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
