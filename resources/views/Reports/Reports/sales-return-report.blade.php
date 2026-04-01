<?php $page = 'sales-return-report'; ?>
@extends('layout.mainlayout')

@section('style')
<style>
    .card-table { border-radius: 15px; overflow: hidden; }
    #salesReturnTable { font-size: 0.875rem !important; }
    
    /* Pagination Styling */
    .pagination { margin-bottom: 0; gap: 4px; }
    .page-link { border-radius: 12px !important; border: 1px solid #d7e2f0; background: #f8f9fa; color: #102a5a; }
    .page-item.active .page-link { background-color: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.24); border-color: #2563eb; }
    .sales-return-summary h3 {
        color: #102a5a;
        font-size: 1.2rem;
        letter-spacing: -0.02em;
    }
    .sales-return-summary .dash-widget-icon {
        width: 52px;
        height: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    @media print {
        .no-print, .dt-buttons, .dataTables_filter, .breadcrumb, .btn, .pagination-container { 
            display: none !important; 
        }
        .page-wrapper { margin: 0; padding: 0; background: white !important; }
        .table { width: 100% !important; border-collapse: collapse; }
        .table th { background-color: #eee !important; color: #000 !important; }
        .table td, .table th { border: 1px solid #ddd !important; padding: 8px; }
    }
</style>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">{{ __('Sales Return Report') }}</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('index') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Reports') }}</li>
                        </ul>
                    </div>
                    <div class="col-auto d-flex gap-2 no-print">
                        <button onclick="window.print()" class="btn btn-white text-dark border rounded-pill shadow-sm">
                            <i class="feather-printer me-1"></i> {{ __('Print') }}
                        </button>
                    </div>
                </div>
            </div>

            @include('Reports.partials.context-strip', [
                'reportLabel' => 'Sales Return Report'
            ])

            {{-- Statistics Row --}}
            <div class="row mb-4">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card shadow-sm border-0 overflow-hidden sales-return-summary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="dash-widget-icon bg-danger-light rounded-circle p-3">
                                    <i class="feather-corner-up-left text-danger"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="text-muted mb-1 small fw-bold text-uppercase">{{ __('Total Refunded Amount') }}</p>
                                    <h3 class="mb-0 fw-bold">₦{{ number_format($totalRefunded ?? 0, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Section --}}
            <div class="no-print mb-4">
                @component('components.search-filter')
                @endcomponent
            </div>

            {{-- Main Table Card --}}
            <div class="card card-table shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="salesReturnTable" class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Refund Amount') }}</th>
                                    <th>{{ __('Qty') }}</th>
                                    <th>{{ __('Stock Status') }}</th>
                                    <th>{{ __('Return Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salesreturnreports as $report)
                                    <tr>
                                        <td>{{ $report->Id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img class="rounded me-2" width="38" 
                                                     src="{{ asset('assets/img/products/' . ($report->Image ?? 'default.png')) }}" 
                                                     alt="Product">
                                                <span class="fw-medium text-dark">{{ $report->Product }}</span>
                                            </div>
                                        </td>
                                        <td><code class="text-primary fw-bold">{{ $report->SKU }}</code></td>
                                        <td><span class="text-muted">{{ $report->Category ?? 'N/A' }}</span></td>
                                        <td class="text-danger fw-bold">₦{{ number_format($report->SoldAmount, 2) }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ number_format($report->SoldQty) }}</span></td>
                                        <td>
                                            @if($report->InstockQty <= 5)
                                                <span class="text-danger fw-bold"><i class="feather-alert-triangle me-1"></i>{{ $report->InstockQty }}</span>
                                            @else
                                                <span class="text-success">{{ $report->InstockQty }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="small text-muted">
                                                {{ \Carbon\Carbon::parse($report->DueDate)->format('d M, Y') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="feather-inbox display-4 mb-2"></i>
                                                <p>{{ __('No sales returns found for the selected criteria.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination Footer --}}
                    @if($salesreturnreports->hasPages() || $salesreturnreports->total() > 0)
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 border-top no-print">
                        <div class="text-muted small mb-3 mb-md-0">
                            {{ __('Showing') }} <strong>{{ $salesreturnreports->firstItem() }}</strong> 
                            {{ __('to') }} <strong>{{ $salesreturnreports->lastItem() }}</strong> 
                            {{ __('of') }} <strong>{{ $salesreturnreports->total() }}</strong> {{ __('entries') }}
                        </div>
                        <div class="pagination-container">
                            {!! $salesreturnreports->links('pagination::bootstrap-5') !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
{{-- Required Libraries for PDF/Excel Exports --}}
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        if ($('#salesReturnTable').length > 0) {
            $('#salesReturnTable').DataTable({
                "bFilter": true,
                "bInfo": false,
                "paging": false, // Handled by Laravel
                "ordering": true,
                "dom": '<"d-flex justify-content-between align-items-center p-3 border-bottom"Bf>t',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        className: 'btn btn-outline-success btn-sm me-2',
                        text: '<i class="feather-file me-1"></i> Excel',
                        title: 'Sales_Return_Report_{{ date("d_M_Y") }}',
                        exportOptions: { columns: ':not(.no-print)' }
                    },
                    {
                        extend: 'pdfHtml5',
                        className: 'btn btn-outline-danger btn-sm',
                        text: '<i class="feather-download me-1"></i> PDF',
                        title: 'Sales Return Report',
                        exportOptions: { columns: ':not(.no-print)' },
                        customize: function(doc) {
                            doc.styles.tableHeader.fillColor = '#f21136';
                            doc.styles.tableHeader.color = 'white';
                            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                        }
                    }
                ]
            });
        }
    });
</script>
@endsection
