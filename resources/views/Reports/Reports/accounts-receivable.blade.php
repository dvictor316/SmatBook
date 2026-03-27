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
        $currencySymbol = '₦';
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
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
                                <h5>{{ $currencySymbol }}{{ number_format($totalDue ?? 0, 2) }}</h5>
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
                            <div class="table-responsive">
                                <table class="table table-center table-hover">
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
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($receivables as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="fw-semibold">{{ $row->customer_name }}</td>
                                                <td>
                                                    <div>{{ $row->email ?: 'No email' }}</div>
                                                    <small class="text-muted">{{ $row->phone ?: 'No phone' }}</small>
                                                </td>
                                                <td class="text-center">{{ number_format($row->invoice_count) }}</td>
                                                <td class="text-end">{{ $currencySymbol }}{{ number_format($row->total_invoiced, 2) }}</td>
                                                <td class="text-end">{{ $currencySymbol }}{{ number_format($row->total_paid, 2) }}</td>
                                                <td class="text-end">{{ $currencySymbol }}{{ number_format($row->opening_balance, 2) }}</td>
                                                <td class="text-end fw-bold text-danger">{{ $currencySymbol }}{{ number_format($row->total_due + $row->opening_balance, 2) }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('reports.customer-statement', $row->customer_id) }}" class="btn btn-sm btn-outline-primary">View Statement</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">No outstanding customer balances found.</td>
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
