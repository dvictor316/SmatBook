@extends('layout.mainlayout')

@section('content')
    <style>
        .sales-summary-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
        }
        .sales-summary-card h6 {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .sales-summary-card h5 {
            font-size: 1.2rem;
            color: #102a5a;
            letter-spacing: -0.02em;
        }
        .sales-table-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
        }
        .sales-table-card .table thead th {
            background: #f8fafc;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #102a5a;
            letter-spacing: 0.08em;
        }
        .pagination-wrapper {
            padding-top: 1rem;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Page Header --}}
            <div class="page-header">
                <div class="content-page-header">
                    <div>
                        <h5>{{ __('Sales Report') }}</h5>
                    </div>
                    <div class="list-btn">
                        <ul class="filter-list">
                            <li>
                                <a class="btn btn-filters w-auto" id="filter_search">
                                    <i class="feather-filter me-1"></i> {{ __('Filter') }}
                                </a>
                            </li>
                            <li>
                                <a class="btn btn-primary w-auto" href="{{ route('reports.sales') }}">
                                    <i class="feather-refresh-ccw me-1"></i> {{ __('Reset') }}
                                </a>
                            </li>
                            <li>
                                <a class="btn btn-secondary w-auto" href="javascript:void(0);" onclick="window.print()">
                                    <i class="feather-printer me-1"></i> {{ __('Print') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            @include('Reports.partials.context-strip', [
                'reportLabel' => 'Sales Analytics Report',
                'periodLabel' => request('from_date') || request('to_date')
                    ? 'Filtered Business Window'
                    : 'Current Workspace Activity',
            ])

            {{-- Summary Cards --}}
            <div class="row">
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card reporte-card bg-primary-light sales-summary-card">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-primary">Total Items Sold</h6>
                                <h5>{{ number_format($salesreports->sum('SoldQty')) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card reporte-card bg-success-light sales-summary-card">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-success">Total Sales Revenue</h6>
                                <h5>${{ number_format($salesreports->sum('SoldAmount'), 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card reporte-card bg-warning-light sales-summary-card">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-warning">Total Products</h6>
                                <h5>{{ $salesreports->unique('Product')->count() }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card reporte-card bg-info-light sales-summary-card">
                        <div class="card-body">
                            <div class="inform-item">
                                <h6 class="text-info">Avg Sale Value</h6>
                                <h5>${{ $salesreports->count() > 0 ? number_format($salesreports->sum('SoldAmount') / $salesreports->count(), 2) : '0.00' }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Filter Container --}}
            <div id="filter_inputs" class="card filter-card" style="display: none;">
                <div class="card-body pb-0">
                    @component('components.search-filter')
                    @endcomponent
                </div>
            </div>

            {{-- Main Sales Table --}}
            <div class="row">
                <div class="col-sm-12">
                    <div class="card sales-table-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="sales-report-table" class="table table-center table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Product') }}</th>
                                            <th>{{ __('SKU') }}</th>
                                            <th>{{ __('Category') }}</th>
                                            <th>{{ __('Sold Amount') }}</th>
                                            <th class="text-center">{{ __('Sold Qty') }}</th>
                                            <th class="text-center">{{ __('Instock Qty') }}</th>
                                            <th>{{ __('Date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($salesreports as $report)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="fw-bold">{{ $report->Product }}</td>
                                                <td><span class="badge badge-soft-secondary">{{ $report->SKU }}</span></td>
                                                <td>{{ $report->Category }}</td>
                                                <td>${{ number_format($report->SoldAmount, 2) }}</td>
                                                <td class="text-center">{{ $report->SoldQty }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ ($report->InstockQty <= 10) ? 'bg-danger-light text-danger' : 'bg-success-light text-success' }}">
                                                        {{ $report->InstockQty }}
                                                    </span>
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($report->DueDate)->format('d M Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                {{-- Pagination Links using Bootstrap 5.3 --}}
                                <div class="pagination-wrapper">
                                    {!! $salesreports->links('pagination::bootstrap-5') !!}
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
<script>
$(document).ready(function() {
    // Toggle filter section
    $('#filter_search').on('click', function(e) {
        e.preventDefault();
        $('#filter_inputs').slideToggle("fast");
    });
});
</script>
@endsection
