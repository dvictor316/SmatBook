<?php $page = 'credit-notes'; ?>
@extends('layout.mainlayout')

@section('style')
<style>
    .page-wrapper { background-color: #f4f7f6; }
    .card-table { border-radius: 15px; overflow: hidden; border: none; }
    @media print {
        .no-print, .dt-buttons, .dataTables_filter, .breadcrumb, .btn, .pagination-container, .header, .sidebar { 
            display: none !important; 
        }
        .page-wrapper { margin: 0 !important; padding: 0 !important; background: white !important; }
        .table td, .table th { border: 1px solid #ddd !important; }
    }
</style>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header --}}
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">{{ __('Credit Notes') }}</h3>
                    </div>
                    <div class="col-auto d-flex gap-2 no-print">
                        <button onclick="window.print()" class="btn btn-white text-dark border rounded-pill shadow-sm">
                            <i class="feather-printer me-1"></i> {{ __('Print') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Summary Card --}}
            <div class="row mb-4">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <p class="text-muted mb-1 small fw-bold text-uppercase">{{ __('Total Refunded') }}</p>
                            <h3 class="mb-0 fw-bold">₦{{ number_format($totalRefunded ?? 0, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Data Table --}}
            <div class="card card-table shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="creditNotesTable" class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Purchase No') }}</th>
                                    <th>{{ __('Supplier') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Qty') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchasereturns as $note)
                                    <tr>
                                        <td>{{ $note->Id }}</td>
                                        <td><span class="text-primary fw-bold">{{ $note->PurchaseNo }}</span></td>
                                        <td>{{ $note->VendorName ?? 'N/A' }}</td>
                                        <td>{{ $note->Product }}</td>
                                        <td class="fw-bold">₦{{ number_format($note->ReturnAmount, 2) }}</td>
                                        <td><span class="badge bg-info-light">{{ $note->ReturnQty }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($note->ReturnDate)->format('d M, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-5 text-muted">No database records found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($purchasereturns) && $purchasereturns->total() > 0)
                    <div class="d-flex justify-content-between align-items-center p-4 border-top no-print">
                        <div class="text-muted small">Showing {{ $purchasereturns->firstItem() }} to {{ $purchasereturns->lastItem() }}</div>
                        <div>{!! $purchasereturns->links('pagination::bootstrap-5') !!}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        if ($('#creditNotesTable').length > 0) {
            $('#creditNotesTable').DataTable({
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
