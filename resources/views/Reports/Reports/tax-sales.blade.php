@php
    $page = 'tax-sales';
    $totalTaxCollected = collect($taxsales)->sum('TaxAmount');
    $reportDate = date('Y-m-d');
@endphp

@extends('layout.mainlayout')

@section('style')
<style>
    #taxSalesTable { font-size: 12.5px !important; }
    #taxSalesTable thead th { 
        font-size: 0.75rem !important; 
        text-transform: uppercase; 
        letter-spacing: 0.08em; 
        background-color: #f1f5f9; 
        color: #102a5a;
        border-bottom: 1px solid #dbe7f5;
    }
    .card-body h3 {
        font-size: clamp(0.9rem, 1.7vw, 1.02rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .table-avatar a { color: #334155; font-weight: 600; }

    .dt-buttons { margin-bottom: 15px; gap: 5px; display: flex; }
    .dt-button { 
        border: 1px solid #d1d5db !important;
        background: #fff !important;
        font-size: 0.76rem !important;
        font-weight: 600 !important;
        border-radius: 999px !important;
        padding: 0.5rem 0.9rem !important;
        color: #374151 !important;
    }
    .tax-report-tabs .nav-link {
        border-radius: 999px;
        font-weight: 700;
        color: #102a5a;
    }
    .tax-report-tabs .nav-link.active {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
    }

    @media print {
        .no-print, .report-tabs, .dt-buttons, .dataTables_filter, .search-filter { display: none !important; }
        .page-wrapper { margin: 0; padding: 0; background: white; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header mb-3">
            <div class="row align-items-center">
                <div class="col">
                    @component('components.page-header')
                        @slot('title') {{ __('Tax Report') }} @endslot
                    @endcomponent
                </div>
                <div class="col-auto d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-white btn-sm border shadow-sm">
                        <i class="feather-printer"></i> {{ __('Print') }}
                    </button>
                    <button id="emailSalesTaxBtn" class="btn btn-primary btn-sm shadow-sm">
                        <i class="feather-mail"></i> {{ __('Email Report') }}
                    </button>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Sales Tax Report',
            'periodLabel' => 'Generated on ' . $reportDate,
        ])

        <div class="report-tabs mb-4 no-print tax-report-tabs">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a href="{{ route('reports.tax-purchase') }}" class="nav-link text-muted">{{ __('Purchase Tax Report') }}</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.tax-sales') }}" class="nav-link active shadow-sm">{{ __('Sales Tax Report') }}</a>
                </li>
            </ul>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-success-light">
                    <div class="card-body p-3">
                        <p class="small fw-bold text-uppercase text-success mb-1">{{ __('Total Sales Tax Collected') }}</p>
                        <h3 class="mb-0 text-success">₦{{ number_format($totalTaxCollected, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="no-print mb-4">
            <form method="GET" action="{{ route('reports.tax-sales') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Customer, Invoice..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">{{ __('From Date') }}</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">{{ __('To Date') }}</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.tax-sales') }}" class="btn btn-secondary btn-sm">{{ __('Clear') }}</a>
                </div>
            </form>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="taxSalesTable" class="table table-center table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Invoice No') }}</th>
                                <th>{{ __('Total (₦)') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Discount') }}</th>
                                <th>{{ __('Tax (₦)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($taxsales as $sale)
                                <tr>
                                    <td>{{ $sale['Id'] ?? $loop->iteration }}</td>
                                    <td>
                                        <h2 class="table-avatar">
                                            <a href="#" class="avatar avatar-xs me-2">
                                                <img class="avatar-img rounded-circle" 
                                                     src="{{ asset('assets/img/profiles/' . ($sale['Image'] ?? 'avatar-01.jpg')) }}" 
                                                     alt="User">
                                            </a>
                                            <a href="#">{{ $sale['Customer'] }}</a>
                                        </h2>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($sale['Date'])->format('d M, Y') }}</td>
                                    <td><span class="badge bg-soft-info text-info">{{ $sale['InvoiceNo'] }}</span></td>
                                    <td class="fw-semibold">{{ number_format($sale['TotalAmount'], 2) }}</td>
                                    <td>
                                        <small class="text-muted"><i class="feather-credit-card me-1"></i> {{ $sale['PaymentMethod'] }}</small>
                                    </td>
                                    <td class="text-muted">{{ number_format($sale['Discount'], 2) }}</td>
                                    <td class="text-success fw-bold">
                                        {{ number_format($sale['TaxAmount'], 2) }}
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="7" class="text-end">TOTAL SALES TAX:</td>
                                <td class="text-success">₦{{ number_format($totalTaxCollected, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    if ($('#taxSalesTable').length > 0) {
        $('#taxSalesTable').DataTable({
            "bFilter": true,
            "bInfo": true,
            "autoWidth": false,
            "order": [[2, "desc"]],
            "language": {
                search: ' ',
                searchPlaceholder: "Search sales tax..."
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'dt-button',
                    text: '<i class="feather-grid me-1"></i> Excel',
                    title: 'Sales_Tax_Report_{{ $reportDate }}',
                    footer: true
                },
                {
                    extend: 'pdfHtml5',
                    className: 'dt-button',
                    text: '<i class="feather-file me-1"></i> PDF',
                    title: 'Sales Tax Collected Report',
                    footer: true,
                    customize: function(doc) {
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 10;
                    }
                }
            ]
        });
    }

    // Email Logic
    $('#emailSalesTaxBtn').on('click', function() {
        const subject = "Sales Tax Collected Report";
        const body =
            "Sales Tax Summary Report\n" +
            "Generated: {{ $reportDate }}\n" +
            "Total Sales Tax Collected: ₦{{ number_format($totalTaxCollected, 2) }}\n\n" +
            "This report covers tax collected from customer invoices.";
        const token = '{{ csrf_token() }}';
        const recipient = prompt('Send report to email (leave blank to use your account email):') ?? null;
        if (recipient === null) return;
        $('#emailSalesTaxBtn').prop('disabled', true).text('Sending...');
        fetch("{{ route('reports.email-report') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                subject,
                body,
                recipient: recipient.trim() || null,
                report_html: window.captureReportEmailHtml ? window.captureReportEmailHtml() : ''
            })
        })
        .then(res => res.json())
        .then(data => alert(data.message || 'Email request sent.'))
        .catch(() => alert('Email failed. Please check mail settings.'))
        .finally(() => $('#emailSalesTaxBtn').prop('disabled', false).html('<i class="feather-mail"></i> {{ __('Email Report') }}'));
    });
});
</script>
@endsection
