<?php $page = 'purchase-return-report'; ?>
@extends('layout.mainlayout')

@section('style')
<style>
    .page-wrapper { background-color: #f4f7f6; }
    .card-table { border-radius: 15px; overflow: hidden; border: none; }
    .pagination { margin-bottom: 0; gap: 4px; }
    .page-link { border-radius: 8px !important; border: none; background: #f8f9fa; color: #495057; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); }

    @media print {
        .no-print, .dt-buttons, .dataTables_filter, .breadcrumb, .btn, .pagination-container { 
            display: none !important; 
        }
        .page-wrapper { margin: 0; padding: 0; background: white !important; }
        .table { width: 100% !important; border-collapse: collapse; }
        .table td, .table th { border: 1px solid #ddd !important; padding: 8px; }
    }
</style>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">{{ __('Purchase Return Report') }}</h3>
                    </div>
                    <div class="col-auto d-flex gap-2 no-print">
                        <button onclick="window.print()" class="btn btn-white text-dark border rounded-pill shadow-sm">
                            <i class="feather-printer me-1"></i> {{ __('Print') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="dash-widget-icon bg-primary-light rounded-circle p-3">
                                    <i class="feather-arrow-left-circle text-primary"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="text-muted mb-1 small fw-bold text-uppercase">{{ __('Total Purchase Returns') }}</p>
                                    <h3 class="mb-0 fw-bold">₦{{ number_format($totalRefunded ?? 0, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="no-print mb-4">
                @component('components.search-filter')
                @endcomponent
            </div>

            <div class="card card-table shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="purchaseReturnTable" class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Purchase No') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Qty') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchasereturns as $report)
                                    <tr>
                                        <td>{{ $report->Id }}</td>
                                        <td><strong>{{ $report->PurchaseNo }}</strong></td>
                                        <td>{{ $report->Product }}</td>
                                        <td>{{ $report->VendorName ?? 'N/A' }}</td>
                                        <td>{{ number_format($report->ReturnQty) }}</td>
                                        <td class="fw-bold">₦{{ number_format($report->ReturnAmount, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($report->ReturnDate)->format('d M, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-5">{{ __('No records found') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($purchasereturns->total() > 0)
                    <div class="d-flex justify-content-between align-items-center p-4 border-top no-print">
                        <div class="text-muted small">
                            Showing {{ $purchasereturns->firstItem() }} to {{ $purchasereturns->lastItem() }} of {{ $purchasereturns->total() }}
                        </div>
                        <div class="pagination-container">
                            {!! $purchasereturns->links('pagination::bootstrap-5') !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
{{-- Exports Script --}}
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        if ($('#purchaseReturnTable').length > 0) {
            $('#purchaseReturnTable').DataTable({
                "paging": false,
                "dom": '<"d-flex justify-content-between align-items-center p-3 border-bottom"Bf>t',
                buttons: [
                    { extend: 'excelHtml5', className: 'btn btn-outline-success btn-sm me-2', text: 'Excel' },
                    { extend: 'pdfHtml5', className: 'btn btn-outline-danger btn-sm', text: 'PDF' }
                ]
            });
        }
    });
</script>
@endsection