<?php $page = 'purchase-report'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title') {{ __('Purchase Report') }} @endslot
            @endcomponent

            {{-- Summary Card: Now calculates based on the paginated data or total --}}
            <div class="row">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon bg-info-light">
                                    <i class="feather-shopping-cart text-info"></i>
                                </span>
                                <div class="dash-count">
                                    <p class="text-muted mb-1">{{ __('Total Purchase Value') }}</p>
                                    @php
                                        // sum() works on the collection provided by the controller
                                        $totalSum = $purchases->sum('Amount');
                                    @endphp
                                    <h4 class="mb-0 fw-bold">{{ number_format($totalSum, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Section (Search/Reset) --}}
            @component('components.search-filter')
                @slot('route') {{ route('purchase-report') }} @endslot
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table shadow-sm border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="purchase-report-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Reference') }}</th>
                                            <th>{{ __('Company / Supplier') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($purchases as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <span class="badge badge-soft-info">{{ $item->Reference }}</span>
                                                </td>
                                                <td class="fw-bold text-dark">
                                                    {{ $item->CompanyName ?? 'N/A' }}
                                                </td>
                                                <td class="fw-bold">
                                                    {{ number_format($item->Amount, 2) }}
                                                </td>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($item->Date)->format('d M Y') }}
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-success text-success">
                                                        {{ ucfirst($item->Type) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No purchase records found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            {{-- Pagination Links --}}
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    Showing {{ $purchases->firstItem() }} to {{ $purchases->lastItem() }} of {{ $purchases->total() }} entries
                                </div>
                                <div>
                                    {{ $purchases->appends(request()->query())->links() }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
{{-- DataTables Buttons & Export Tools --}}
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        if ($('#purchase-report-table').length > 0) {
            $('#purchase-report-table').DataTable({
                "bFilter": true,
                "paging": false, // False because we use Laravel Server-side Pagination
                "bInfo": false,
                "ordering": true,
                "dom": '<"row align-items-center"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>',
                "buttons": [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="feather-file-text me-1"></i> Excel',
                        className: 'btn btn-success btn-sm me-2',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="feather-file me-1"></i> PDF',
                        className: 'btn btn-danger btn-sm me-2',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                    },
                    {
                        extend: 'print',
                        text: '<i class="feather-printer me-1"></i> Print',
                        className: 'btn btn-primary btn-sm',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                    }
                ],
                "language": {
                    search: "_INPUT_",
                    searchPlaceholder: "Filter current page...",
                }
            });
        }
    });
</script>
@endsection