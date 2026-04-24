@extends('layout.mainlayout')

@section('content')
    @php
        $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
        $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header d-print-none">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Invoices</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Invoices</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <button onclick="printReport()" class="btn btn-white text-black me-2 border">
                            <i class="fa fa-print me-1"></i> Print Report
                        </button>
                        <a href="{{ route('sales.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> New Invoice
                        </a>
                    </div>
                </div>
            </div>

            <div class="row d-print-none">
                @if(isset($invoicescards))
                    @foreach ($invoicescards as $card)
                        <div class="col-xl-3 col-lg-4 col-sm-6 col-12 d-flex">
                            <div class="card inovices-card w-100 shadow-sm border-0">
                                <div class="card-body">
                                    <div class="dash-widget-header">
                                        <span class="inovices-widget-icon {{ $card['class'] ?? 'bg-primary-light' }}">
                                            <i data-feather="{{ $card['icon'] ?? 'file-text' }}"></i>
                                        </span>
                                        <div class="dash-count text-end">
                                            <div class="dash-title text-muted small">{{ $card['title'] }}</div>
                                            <div class="dash-counts">
                                                <h4 class="fw-bold mb-0">
                                                    {{ \App\Support\GeoCurrency::format((float) ($card['amount'] ?? 0), 'NGN', $currencyCode, $currencyLocale) }}
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <p class="inovices-all mb-0 small">Items: 
                                            <span class="badge rounded-pill bg-light text-dark border">
                                                {{ $card['count'] ?? 0 }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="d-print-none">
                @component('components.search-filter')
                @endcomponent
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card card-table shadow-sm border-0" id="printableArea">
                        <div class="card-body p-4">

                            <div class="d-none d-print-block mb-4 text-center">
                                <h2 style="color: #4b308b;">Invoice Summary Report</h2>
                                <p class="text-muted">Generated on {{ date('d M Y, h:i A') }}</p>
                                <hr>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover datatable align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Invoice ID</th>
                                            <th>Customer</th>
                                            <th>Sales Person</th>
                                            <th>Created On</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th class="text-end d-print-none">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('invoice-details-admin', $invoice->id) }}" class="fw-bold text-primary">
                                                        {{ $invoice->invoice_no ?? '#'.$invoice->id }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="text-dark fw-medium">{{ $invoice->customer_name }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border-0">
                                                        <i class="far fa-user me-1 text-muted"></i>
                                                        {{ $invoice->user->name ?? 'System' }}
                                                    </span>
                                                </td>
                                                <td class="text-muted">{{ $invoice->created_at->format('d M Y') }}</td>
                                                <td class="fw-bold text-dark">
                                                    {{ \App\Support\GeoCurrency::format((float) ($invoice->effective_total ?? $invoice->total ?? 0), 'NGN', $currencyCode, $currencyLocale) }}
                                                </td>
                                                <td>
                                                    @php
                                                        $displayStatus = strtolower($invoice->effective_payment_status ?? $invoice->payment_status ?? $invoice->status);
                                                        $statusClass = match($displayStatus) {
                                                            'paid' => 'bg-success-light text-success',
                                                            'partial' => 'bg-info-light text-info',
                                                            'unpaid', 'pending' => 'bg-warning-light text-warning',
                                                            'cancelled' => 'bg-danger-light text-danger',
                                                            'overdue' => 'bg-soft-danger text-danger',
                                                            default => 'bg-light text-dark',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $statusClass }} rounded-1 text-uppercase">{{ $invoice->effective_payment_status ?? $invoice->payment_status ?? $invoice->status }}</span>
                                                </td>
                                                <td class="text-end d-print-none">
                                                    <div class="dropdown dropdown-action">
                                                        <button type="button" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                            <div class="dropdown-menu dropdown-menu-end">

                                                                <a class="dropdown-item" href="{{ route('invoices.edit', $invoice->id) }}">
                                                                    <i class="far fa-edit me-2"></i>Edit
                                                                </a>

                                                                <a class="dropdown-item" href="{{ route('invoice-details-admin', $invoice->id) }}">
                                                                    <i class="far fa-eye me-2"></i>View
                                                                </a>

                                                                @php
                                                                    $payStatus = strtolower($invoice->effective_payment_status ?? $invoice->payment_status ?? $invoice->status);
                                                                @endphp
                                                                @if($payStatus !== 'paid')
                                                                    <a class="dropdown-item text-success" href="{{ route('pay-online', ['id' => $invoice->id]) }}">
                                                                        <i class="fas fa-money-bill-wave me-2"></i>Pay
                                                                    </a>
                                                                @endif

                                                                <div class="dropdown-divider"></div>
                                                                <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST">
                                                                    @csrf 
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this invoice?')">
                                                                        <i class="far fa-trash-alt me-2"></i>Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center p-5 text-muted">No records found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 d-print-none">
                                {{ $invoices->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printReport() {
            const printContents = document.getElementById('printableArea').innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = `
                <html>
                    <head>
                        <title>Invoice Report - {{ date('Y-m-d') }}</title>
                        <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
                        <style>
                            body { background: white !important; padding: 30px; font-family: sans-serif; }
                            .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            .table th { background-color: #f8f9fa !important; color: #4b308b !important; -webkit-print-color-adjust: exact; border: 1px solid #dee2e6; }
                            .table td { border: 1px solid #dee2e6; padding: 10px; font-size: 12px; }
                            .text-danger { color: #dc3545 !important; }
                            .text-primary { color: #4b308b !important; text-decoration: none; }
                            .badge { border: 1px solid #eee; padding: 4px 8px; font-size: 10px; background: #fdfdfd !important; }
                            .d-print-none { display: none !important; }
                        </style>
                    </head>
                    <body>${printContents}</body>
                </html>`;

            window.print();
            document.body.innerHTML = originalContents;
            window.location.reload(); 
        }
    </script>
@endsection
