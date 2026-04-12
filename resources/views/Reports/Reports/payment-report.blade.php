@php
    $page = 'payment-report';
    $reportDate = date('d-M-Y');
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $currencySymbol = $geoCurrencySymbol ?? \App\Support\GeoCurrency::currentSymbol();
    
    // Safety checks
    $paymentsExists = isset($payments) && $payments->count() > 0;
    $pageTotal = $paymentsExists ? $payments->sum('amount') : 0;
    $totalAmount = $totalAmount ?? 0; 
@endphp

@extends('layout.mainlayout')

@section('style')
<style>
    #paymentReportTable { font-size: 0.875rem !important; }
    .money-sm { font-size: 0.92rem; font-variant-numeric: tabular-nums; }
    .money-cell { font-size: 0.82rem; font-variant-numeric: tabular-nums; }
    #paymentReportTable thead th { 
        background-color: #f8f9fa;
        color: #334155;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        border-top: none;
    }
    
    /* Pagination Styling Fixes */
    .pagination { margin-bottom: 0; }
    .page-link { padding: 0.5rem 0.85rem; color: #6366f1; }
    .page-item.active .page-link { background-color: #6366f1; border-color: #6366f1; }
    
    /* Summary Card Styling */
    .card-total {
        background: linear-gradient(135deg, #0f2d5c 0%, #2563eb 100%);
        color: white;
        border: none;
    }

    @media print {
        .no-print, .dt-buttons, .dataTables_filter, .search-filter, .breadcrumb, .btn { 
            display: none !important; 
        }
        .page-wrapper { margin: 0; padding: 0; background: white !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table { width: 100% !important; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dee2e6 !important; padding: 8px; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid" data-print-scope>

        {{-- Page Header --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">{{ __('Payment Report') }}</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('index') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Reports') }}</li>
                    </ul>
                </div>
                <div class="col-auto d-flex gap-2 no-print">
                    <button id="emailReport" class="btn btn-outline-primary btn-sm rounded-pill">
                        <i class="fas fa-envelope me-1"></i> {{ __('Email') }}
                    </button>
                    <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill shadow-sm">
                        <i class="fas fa-print me-1"></i> {{ __('Print Report') }}
                    </button>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Payment Activity Report',
            'periodLabel' => request('from_date') || request('to_date')
                ? 'Filtered Payment Window'
                : 'All Recorded Payments',
        ])

        {{-- Top Summary Stats --}}
        <div class="row mb-4">
            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card card-total shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1 opacity-75 small fw-bold uppercase">{{ __('Grand Total') }}</p>
                                <h3 class="mb-0 fw-bold money-sm">{{ \App\Support\GeoCurrency::format($totalAmount, 'NGN', $currencyCode, $currencyLocale) }}</h3>
                            </div>
                            <div class="rounded-circle bg-white bg-opacity-25 p-2">
                                <i class="fas fa-database text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search Filter --}}
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body p-3">
                <form action="{{ route('reports.payment') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ __('From Date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ __('To Date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ __('Method') }}</label>
                            <select name="method" class="form-select">
                                <option value="">All Methods</option>
                                @foreach(($methodOptions ?? []) as $method)
                                    <option value="{{ $method }}" {{ request('method') === $method ? 'selected' : '' }}>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                @foreach(($statusOptions ?? []) as $status)
                                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-dark btn-sm px-4">
                                <i class="fas fa-filter me-1"></i> {{ __('Filter Results') }}
                            </button>
                            <a href="{{ route('reports.payment') }}" class="btn btn-light btn-sm px-4 border">
                                {{ __('Reset') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Table --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="paymentReportTable" class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Payment ID') }}</th>
                                <th>{{ __('Invoice') }}</th>
                                <th>{{ __('Date & Time') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Running Total') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Channel') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="no-print">{{ __('Created By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $runningTotal = 0; @endphp
                            @forelse($payments as $payment)
                            @php $runningTotal += (float) ($payment->amount ?? 0); @endphp
                            <tr>
                                <td class="fw-bold text-dark">{{ $payment->payment_id }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        #{{ optional($payment->sale)->invoice_no ?? $payment->sale_id }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ \Carbon\Carbon::parse($payment->created_at)->format('d M, Y') }}</div>
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($payment->created_at)->format('h:i A') }}</div>
                                </td>
                                <td class="fw-bold text-dark money-cell">{{ \App\Support\GeoCurrency::format($payment->amount, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                <td class="fw-bold text-primary money-cell">{{ \App\Support\GeoCurrency::format($runningTotal, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                <td>
                                    <span class="text-secondary small">
                                        <i class="fas fa-credit-card me-1"></i> {{ $payment->method }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $payment->resolved_channel ?? 'Not specified' }}
                                </td>
                                <td>
                                    @php
                                        $rowStatus = $payment->resolved_status ?? $payment->status ?? 'Pending';
                                    @endphp
                                    @if($rowStatus === 'Completed')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Completed</span>
                                    @elseif($rowStatus === 'Partial')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2">Partial</span>
                                    @elseif(in_array($rowStatus, ['Failed', 'Cancelled'], true))
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">{{ $rowStatus }}</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2">Pending</span>
                                    @endif
                                </td>
                                <td class="no-print text-muted small">
                                    {{ $payment->creator->name ?? 'System' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-circle-exclamation display-4 d-block mb-3"></i>
                                        <p>{{ __('No payment records found for the selected range.') }}</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($paymentsExists)
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="5" class="text-end text-uppercase small text-muted">{{ __('Page Total') }}</td>
                                <td class="text-primary money-cell">{{ \App\Support\GeoCurrency::format($pageTotal, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr class="table-primary border-top">
                                <td colspan="5" class="text-end text-uppercase small">{{ __('Filtered Grand Total') }}</td>
                                <td class="text-dark money-cell">{{ \App\Support\GeoCurrency::format($totalAmount, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                
                {{-- Bootstrap 5.3 Pagination Wrapper --}}
                @if($paymentsExists)
                <div class="card-footer bg-white border-top-0 pt-0 pb-4 no-print">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="text-muted small">
                            Showing <strong>{{ $payments->firstItem() }}</strong> to <strong>{{ $payments->lastItem() }}</strong> of <strong>{{ $payments->total() }}</strong> records
                        </div>
                        <div class="pagination-container">
                            {{ $payments->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
{{-- DataTables Buttons for Excel/PDF Export --}}
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        if ($('#paymentReportTable').length > 0) {
            $('#paymentReportTable').DataTable({
                "bFilter": true,
                "bInfo": false,
                "paging": false, // Using Laravel Pagination instead
                "ordering": true,
                "dom": '<"d-flex justify-content-between align-items-center p-3 border-bottom"Bf>t',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        className: 'btn btn-outline-secondary btn-sm me-2',
                        text: '<i class="feather-download me-1"></i> Excel',
                        title: 'Payment_Report_{{ $reportDate }}',
                        footer: true,
                        exportOptions: {
                            columns: ':not(.no-print)',
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        className: 'btn btn-outline-secondary btn-sm',
                        text: '<i class="feather-file-text me-1"></i> PDF',
                        title: 'Payment Report - {{ $reportDate }}',
                        footer: true,
                        exportOptions: {
                            columns: ':not(.no-print)',
                        },
                        customize: function(doc) {
                            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                        }
                    }
                ]
            });
        }

        // Email Feature
        $('#emailReport').on('click', function() {
            const subject = "Payment Report: {{ $reportDate }}";
            const body = "Attached is the payment report. Total Received: {{ $currencySymbol }}{{ number_format($totalAmount, 2) }}";
            const token = '{{ csrf_token() }}';
            const recipient = prompt('Send report to email (leave blank to use your account email):') ?? null;
            if (recipient === null) return;
            $('#emailReport').prop('disabled', true).text('Sending...');
            fetch("{{ route('reports.email-report') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ subject, body, recipient: recipient.trim() || null })
            })
            .then(res => res.json())
            .then(data => alert(data.message || 'Email request sent.'))
            .catch(() => alert('Email failed. Please check mail settings.'))
            .finally(() => $('#emailReport').prop('disabled', false).html('<i class="fas fa-envelope me-1"></i> {{ __('Email') }}'));
        });
    });
</script>
@endsection
