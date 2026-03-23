@php
    $page = 'tax-purchase';
    $totalTax = collect($taxpurchases)->sum('TaxAmount');
    $reportDate = date('Y-m-d');
@endphp

@extends('layout.mainlayout')

@section('style')
<style>
    #taxPurchaseTable { font-size: 12.5px !important; }
    #taxPurchaseTable thead th { 
        font-size: 0.75rem !important; 
        text-transform: uppercase; 
        letter-spacing: 0.08em; 
        background-color: #f1f5f9; 
        color: #102a5a;
        border-bottom: 1px solid #dbe7f5;
    }
    .card-body h3 { font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em; }
    
    .dt-buttons { margin-bottom: 15px; gap: 5px; display: flex; }
    .dt-button { 
        border: 1px solid #d1d5db !important;
        background: #fff !important;
        font-size: 0.76rem !important;
        font-weight: 600 !important;
        border-radius: 999px !important;
        padding: 0.5rem 0.9rem !important;
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

        {{-- Header --}}
        <div class="page-header mb-3">
            <div class="row align-items-center">
                <div class="col">
                    @component('components.page-header')
                        @slot('title') {{ __('Tax Report') }} @endslot
                    @endcomponent
                </div>
                <div class="col-auto d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-white btn-sm border shadow-sm"><i class="feather-printer"></i> Print</button>
                    <button id="emailTaxBtn" class="btn btn-primary btn-sm shadow-sm"><i class="feather-mail"></i> Email Summary</button>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Purchase Tax Report',
            'periodLabel' => 'Generated on ' . $reportDate,
        ])

        {{-- Tabs --}}
        <div class="report-tabs mb-4 no-print tax-report-tabs">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a href="{{ route('reports.tax-purchase') }}" class="nav-link active shadow-sm">{{ __('Purchase Tax Report') }}</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.tax-sales') }}" class="nav-link text-muted">{{ __('Sales Tax Report') }}</a>
                </li>
            </ul>
        </div>

        {{-- Summary Card --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-primary-light">
                    <div class="card-body p-3">
                        <p class="small fw-bold text-uppercase text-primary mb-1">{{ __('Total Purchase Tax') }}</p>
                        <h3 class="mb-0 text-primary">₦{{ number_format($totalTax, 2) }}</h3>
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
                    <table id="taxPurchaseTable" class="table table-center table-hover datatable">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Supplier') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Ref No') }}</th>
                                <th>{{ __('Total (₦)') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Discount') }}</th>
                                <th>{{ __('Tax (₦)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($taxpurchases as $tax)
                                <tr>
                                    <td>{{ $tax['Id'] ?? $loop->iteration }}</td>
                                    <td class="fw-bold text-dark">{{ $tax['Supplier'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($tax['Date'])->format('d M, Y') }}</td>
                                    <td><span class="text-muted">{{ $tax['RefNo'] }}</span></td>
                                    <td class="fw-semibold">{{ number_format($tax['TotalAmount'], 2) }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $tax['PaymentMethod'] }}
                                        </span>
                                    </td>
                                    <td class="text-danger">-{{ number_format($tax['Discount'], 2) }}</td>
                                    <td class="text-primary fw-bold">
                                        {{ number_format($tax['TaxAmount'], 2) }}
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="7" class="text-end">PAGE TOTAL TAX:</td>
                                <td class="text-primary">₦{{ number_format($totalTax, 2) }}</td>
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
    if ($('#taxPurchaseTable').length > 0) {
        $('#taxPurchaseTable').DataTable({
            "bFilter": true,
            "bInfo": true,
            "autoWidth": false,
            "order": [[2, "desc"]],
            "language": {
                search: ' ',
                searchPlaceholder: "Search records..."
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'dt-button',
                    text: '<i class="feather-grid me-1"></i> Excel',
                    title: 'Purchase_Tax_Report_{{ $reportDate }}',
                    footer: true
                },
                {
                    extend: 'pdfHtml5',
                    className: 'dt-button',
                    text: '<i class="feather-file me-1"></i> PDF',
                    title: 'Purchase Tax Report',
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
    $('#emailTaxBtn').on('click', function() {
        const body = encodeURIComponent(
            "Purchase Tax Summary\n" +
            "Date Generated: {{ $reportDate }}\n" +
            "Total Purchase Tax: ₦{{ number_format($totalTax, 2) }}\n\n" +
            "Please log in to the portal to view full breakdown."
        );
        window.location.href = `mailto:?subject=Purchase Tax Report&body=${body}`;
    });
});
</script>
@endsection
