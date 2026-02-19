@php
    $page = 'tax-sales';
    $totalTaxCollected = collect($taxsales)->sum('TaxAmount');
    $reportDate = date('Y-m-d');
@endphp

@extends('layout.mainlayout')

@section('style')
<style>
    /* Professional Typography & Font Reduction */
    .page-wrapper { background-color: #f8f9fa; }
    #taxSalesTable { font-size: 12.5px !important; }
    #taxSalesTable thead th { 
        font-size: 11px !important; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        background-color: #f1f5f9; 
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
    }
    .card-body h3 { font-size: 1.6rem; font-weight: 800; }
    .table-avatar a { color: #334155; font-weight: 600; }
    
    /* DataTables Button Styling */
    .dt-buttons { margin-bottom: 15px; gap: 5px; display: flex; }
    .dt-button { 
        border: 1px solid #d1d5db !important;
        background: #fff !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        border-radius: 4px !important;
        padding: 4px 12px !important;
        color: #374151 !important;
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

        {{-- Header --}}
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

        {{-- Navigation Tabs --}}
        <div class="report-tabs mb-4 no-print">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a href="{{ route('reports.tax-purchase') }}" class="nav-link text-muted">{{ __('Purchase Tax Report') }}</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.tax-sales') }}" class="nav-link active shadow-sm">{{ __('Sales Tax Report') }}</a>
                </li>
            </ul>
        </div>

        {{-- Summary Card --}}
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

        {{-- Search Filter --}}
        <div class="no-print mb-4">
            @component('components.search-filter')
            @endcomponent
        </div>

        {{-- Table Section --}}
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="taxSalesTable" class="table table-center table-hover datatable">
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
        const body = encodeURIComponent(
            "Sales Tax Summary Report\n" +
            "Generated: {{ $reportDate }}\n" +
            "Total Sales Tax Collected: ₦{{ number_format($totalTaxCollected, 2) }}\n\n" +
            "This report covers tax collected from customer invoices."
        );
        window.location.href = `mailto:?subject=Sales Tax Collected Report&body=${body}`;
    });
});
</script>
@endsection
