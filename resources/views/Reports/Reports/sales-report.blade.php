@extends('layout.mainlayout')

@section('content')
    @php
        $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
        $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    @endphp
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
            <div class="page-header no-print">
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
                            <li>
                                <button id="export_excel" class="btn btn-success w-auto">
                                    <i class="feather-file me-1"></i> {{ __('Excel') }}
                                </button>
                            </li>
                            <li>
                                <button id="export_pdf" class="btn btn-danger w-auto">
                                    <i class="feather-file-text me-1"></i> {{ __('PDF') }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            @include('Reports.partials.context-strip', [
                'reportLabel' => 'Sales Analytics Report',
                'periodLabel' => request('start_date') || request('end_date')
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
                                <h5>{{ \App\Support\GeoCurrency::format($salesreports->sum('SoldAmount'), 'NGN', $currencyCode, $currencyLocale) }}</h5>
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
                                <h5>{{ \App\Support\GeoCurrency::format($salesreports->count() > 0 ? ($salesreports->sum('SoldAmount') / $salesreports->count()) : 0, 'NGN', $currencyCode, $currencyLocale) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Filter Container --}}
            <div id="filter_inputs" class="card filter-card no-print" style="display: none;">
                <div class="card-body pb-0">
                    <form action="{{ route('reports.sales') }}" method="GET" class="pb-3">
                        <div class="row gx-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">{{ __('Search Product / SKU') }}</label>
                                <input type="text" name="search" class="form-control" placeholder="Search product or SKU" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">{{ __('From Date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">{{ __('To Date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="feather-filter me-1"></i> {{ __('Apply') }}
                                    </button>
                                    <a href="{{ route('reports.sales') }}" class="btn btn-secondary w-100">
                                        {{ __('Clear') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
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
                                            <th>{{ __('Running Total') }}</th>
                                            <th class="text-center">{{ __('Sold Qty') }}</th>
                                            <th class="text-center">{{ __('Instock Qty') }}</th>
                                            <th>{{ __('Date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $runningTotal = 0; @endphp
                                        @foreach ($salesreports as $report)
                                            @php $runningTotal += (float) $report->SoldAmount; @endphp
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="fw-bold">{{ $report->Product }}</td>
                                                <td><span class="badge badge-soft-secondary">{{ $report->SKU }}</span></td>
                                                <td>{{ $report->Category }}</td>
                                                <td>{{ \App\Support\GeoCurrency::format($report->SoldAmount, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                <td class="fw-bold text-primary">{{ \App\Support\GeoCurrency::format($runningTotal, 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                <td class="text-center">{{ $report->SoldQty }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ ($report->InstockQty <= 10) ? 'bg-danger-light text-danger' : 'bg-success-light text-success' }}">
                                                        {{ $report->InstockQty }}
                                                    </span>
                                                </td>
                                                <td>{{ $report->DueDate ? \Carbon\Carbon::parse($report->DueDate)->format('d M Y') : 'No sales yet' }}</td>
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

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
@endpush

@section('script')
<script>
$(document).ready(function() {
    // Toggle filter section
    $('#filter_search').on('click', function(e) {
        e.preventDefault();
        $('#filter_inputs').slideToggle("fast");
    });

    // Excel Export
    document.getElementById('export_excel').addEventListener('click', function() {
        const table = document.getElementById('sales-report-table');
        const wb = XLSX.utils.table_to_book(table, { sheet: 'SalesReport' });
        XLSX.writeFile(wb, 'Sales_Report_{{ date("Y-m-d") }}.xlsx');
    });

    // PDF Export
    document.getElementById('export_pdf').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'pt', 'a4');
        doc.setFontSize(14);
        doc.text('Sales Report', 40, 40);
        doc.setFontSize(9);
        doc.text('Generated: {{ now()->format("d M Y H:i") }}', 40, 56);
        doc.autoTable({
            html: '#sales-report-table',
            startY: 70,
            styles: { fontSize: 8, cellPadding: 3 },
            headStyles: { fillColor: [37, 99, 235], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [248, 250, 252] },
            margin: { left: 40, right: 40 },
        });
        doc.save('Sales_Report_{{ date("Y-m-d") }}.pdf');
    });
});
</script>
@endsection

