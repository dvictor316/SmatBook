<?php $page = 'purchase-report'; ?>
@extends('layout.mainlayout')

@section('content')
    <style>
        .purchase-report-metric .dash-widget-header {
            align-items: center;
            gap: 0.9rem;
        }
        .purchase-report-metric .dash-widget-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .purchase-report-metric .dash-count p {
            font-size: 0.76rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b !important;
        }
        .purchase-report-metric .dash-count h4 {
            font-size: 1.25rem;
            color: #102a5a;
            letter-spacing: -0.02em;
        }
        #purchase-report-table thead th {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #102a5a;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title') {{ __('Purchase Report') }} @endslot
            @endcomponent
            @include('Reports.partials.context-strip', [
                'reportLabel' => 'Procurement Report',
                'periodLabel' => request('start_date') || request('end_date')
                    ? 'Filtered Purchase Window'
                    : 'All Recorded Purchases',
            ])

            <div class="row">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card shadow-sm border-0 mb-4 purchase-report-metric">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon bg-info-light">
                                    <i class="feather-shopping-cart text-info"></i>
                                </span>
                                <div class="dash-count">
                                    <p class="text-muted mb-1">{{ __('Total Purchase Value') }}</p>
                                    <h4 class="mb-0 fw-bold">{{ number_format($totalSum, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 no-print">
                <div class="card-body">
                    <form action="{{ route('reports.purchase') }}" method="GET">
                        <div class="row gx-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">{{ __('Search Reference / Supplier') }}</label>
                                <input type="text" name="search" class="form-control" placeholder="Search reference or supplier" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">{{ __('From Date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">{{ __('To Date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="feather-filter me-1"></i> {{ __('Apply') }}
                                    </button>
                                    <a href="{{ route('reports.purchase') }}" class="btn btn-secondary w-100">
                                        {{ __('Reset') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

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
                                            <th>{{ __('Branch') }}</th>
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
                                                <td>
                                                    <span class="badge bg-light border text-primary">
                                                        {{ $item->BranchName ?? 'Workspace Default' }}
                                                    </span>
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
                                                <td colspan="7" class="text-center text-muted">No purchase records found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    Showing {{ $purchases->firstItem() ?? 0 }} to {{ $purchases->lastItem() ?? 0 }} of {{ $purchases->total() }} entries
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
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="feather-file me-1"></i> PDF',
                        className: 'btn btn-danger btn-sm me-2',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                    },
                    {
                        extend: 'print',
                        text: '<i class="feather-printer me-1"></i> Print',
                        className: 'btn btn-primary btn-sm',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
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
