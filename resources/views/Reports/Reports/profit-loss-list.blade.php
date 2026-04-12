@php
    $page = 'profit-loss-list';
    $reportDate = date('Y-m-d');
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $currencySymbol = $geoCurrencySymbol ?? \App\Support\GeoCurrency::currentSymbol();
    
    // Subdomain parameter detection for all routes
    $currentSubdomain = request()->route('subdomain') ?? 'admin';
    $routeParams = ['subdomain' => $currentSubdomain];

    // Grand Totals for Summary Cards (From Controller)
    $grandIncome = $totals->total_income ?? 0;
    $grandOperatingExpense = $totals->total_operating_expense ?? 0;
    $grandPurchaseExpense = $totals->total_purchase_expense ?? 0;
    $grandExpense = $totals->total_expense ?? ($grandOperatingExpense + $grandPurchaseExpense);
    $grandNet = $grandIncome - $grandExpense;

    // Current Page Totals for Table Footer
    $pageIncome = $profitLossData->sum('income');
    $pageOperatingExpense = $profitLossData->sum('operating_expense');
    $pagePurchaseExpense = $profitLossData->sum('purchase_expense');
    $pageExpense = $profitLossData->sum('expense');
    $pageNet = $pageIncome - $pageExpense;
@endphp

@extends('layout.mainlayout')

@section('style')
<style>
    .pl-report-shell {
        color: #17315f;
    }
    .pl-page-header .page-title {
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 0.15rem;
    }
    .pl-page-header .breadcrumb {
        font-size: 0.74rem !important;
        margin-bottom: 0;
    }
    .pl-page-header .btn {
        font-size: 0.76rem !important;
        padding: 0.55rem 0.95rem !important;
    }
    .pl-summary-card {
        border: 1px solid rgba(191, 219, 254, 0.82) !important;
        border-radius: 18px !important;
        box-shadow: 0 14px 34px rgba(37, 99, 235, 0.08) !important;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 250, 255, 0.96)) !important;
    }
    .pl-summary-card .card-body {
        padding: 1.1rem 1.15rem !important;
    }
    .pl-summary-label {
        font-size: 0.68rem !important;
        letter-spacing: 0.08em;
        line-height: 1.3;
        margin-bottom: 0.4rem !important;
    }
    .pl-summary-card h3 {
        font-size: 1.05rem !important;
        line-height: 1.15;
        margin-bottom: 0;
    }
    .pl-summary-card .money-sm,
    .pl-summary-card h3.money-sm,
    .pl-summary-card .card-body h3.money-sm {
        font-size: 1.1rem !important;
        line-height: 1.18 !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
        word-break: normal;
        overflow-wrap: anywhere;
    }
    .pl-summary-meta {
        font-size: 0.72rem;
        line-height: 1.3;
    }
    .pl-filter-card {
        border: 1px solid rgba(191, 219, 254, 0.82) !important;
        border-radius: 20px !important;
        box-shadow: 0 16px 36px rgba(37, 99, 235, 0.08) !important;
    }
    .pl-filter-card .card-body {
        padding: 1rem 1.1rem !important;
    }
    .pl-filter-card .form-label {
        font-size: 0.68rem !important;
        letter-spacing: 0.08em;
        margin-bottom: 0.38rem;
    }
    .pl-filter-card .form-control {
        font-size: 0.83rem !important;
        min-height: 40px;
    }
    .pl-table-card {
        border: 1px solid rgba(191, 219, 254, 0.82) !important;
        border-radius: 22px !important;
        box-shadow: 0 18px 42px rgba(37, 99, 235, 0.08) !important;
        overflow: hidden;
    }
    .pl-table-card .card-body {
        padding: 0.95rem !important;
    }
    #profitLossTable {
        font-size: 11.5px !important;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0 !important;
    }
    #profitLossTable thead th { 
        font-size: 0.68rem !important; 
        text-transform: uppercase; 
        letter-spacing: 0.08em; 
        background-color: #f1f5f9; 
        color: #102a5a;
        border-bottom: 1px solid #dbe7f5;
        padding-top: 0.8rem !important;
        padding-bottom: 0.8rem !important;
    }
    #profitLossTable tbody td,
    #profitLossTable tfoot td {
        font-size: 0.78rem !important;
        padding-top: 0.82rem !important;
        padding-bottom: 0.82rem !important;
        vertical-align: middle;
    }
    #profitLossTable tbody tr:hover {
        background: rgba(59, 130, 246, 0.03);
    }
    #profitLossTable tfoot {
        background: #f8fbff !important;
    }
    .badge { font-size: 0.66rem; padding: 0.36rem 0.62rem; letter-spacing: 0.04em; }
    .money-sm { font-size: 1.1rem !important; font-variant-numeric: tabular-nums; }
    .table-money { font-size: 0.78rem; font-variant-numeric: tabular-nums; }
    .report-metric-title { letter-spacing: 0.08em; }
    .pl-date-cell {
        font-size: 0.76rem;
        color: #5f7395 !important;
    }
    .pl-pagination {
        font-size: 0.8rem;
    }

    .dt-buttons { margin-bottom: 15px; gap: 5px; display: flex; }
    .dt-button { 
        border: 1px solid #d1d5db !important;
        background: #fff !important;
        font-size: 0.72rem !important;
        font-weight: 600 !important;
        border-radius: 999px !important;
        padding: 0.46rem 0.82rem !important;
        color: #102a5a !important;
    }
    .dt-button:hover { background: #f9fafb !important; }
    div.dataTables_wrapper div.dataTables_filter input {
        font-size: 0.8rem !important;
        min-height: 36px;
    }
    div.dataTables_wrapper div.dataTables_filter label {
        font-size: 0.76rem !important;
    }

    @media print {
        .no-print, .filter-card, .dataTables_filter, .dt-buttons, .pagination-wrapper { display: none !important; }
        .page-wrapper { margin: 0; padding: 0; background: white !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; margin-bottom: 10px !important; }
        .table { width: 100% !important; }
    }

    @media (max-width: 991.98px) {
        .pl-page-header .page-title {
            font-size: 1.2rem;
        }
        .pl-summary-label {
            font-size: 0.6rem !important;
        }
        .pl-summary-card .money-sm,
        .pl-summary-card h3.money-sm,
        .pl-summary-card .card-body h3.money-sm {
            font-size: 0.92rem !important;
        }
        .pl-summary-card .card-body,
        .pl-filter-card .card-body,
        .pl-table-card .card-body {
            padding: 0.9rem !important;
        }
        #profitLossTable {
            font-size: 11px !important;
        }
        #profitLossTable thead th,
        #profitLossTable tbody td,
        #profitLossTable tfoot td {
            white-space: nowrap;
        }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid pl-report-shell">

        {{-- Header Section --}}
        <div class="page-header mb-4 pl-page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">{{ __('Financial Statement (P&L)') }}</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home', $routeParams) }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profit & Loss</li>
                    </ul>
                </div>
                <div class="col-auto d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-white btn-sm border shadow-sm">
                        <i class="fas fa-print"></i> {{ __('Print Report') }}
                    </button>
                    <button id="emailReportBtn" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-envelope"></i> {{ __('Email Summary') }}
                    </button>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Profit and Loss Report',
            'periodLabel' => request('start_date') || request('end_date')
                ? 'Filtered Financial Window'
                : 'Current Business Performance',
        ])

        {{-- Summary Cards (Grand Totals) --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card pl-summary-card">
                    <div class="card-body">
                        <h6 class="text-success text-uppercase fw-bold report-metric-title pl-summary-label">{{ __('Overall Revenue') }}</h6>
                        <h3 class="mb-0 text-success money-sm">{{ \App\Support\GeoCurrency::format($grandIncome, 'NGN', $currencyCode, $currencyLocale) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pl-summary-card">
                    <div class="card-body">
                        <h6 class="text-info text-uppercase fw-bold report-metric-title pl-summary-label">{{ __('Purchase Cost') }}</h6>
                        <h3 class="mb-0 text-info money-sm">{{ \App\Support\GeoCurrency::format($grandPurchaseExpense, 'NGN', $currencyCode, $currencyLocale) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pl-summary-card">
                    <div class="card-body">
                        <h6 class="text-danger text-uppercase fw-bold report-metric-title pl-summary-label">{{ __('Total Expenses') }}</h6>
                        <div class="text-muted mb-1 pl-summary-meta">OpEx: {{ \App\Support\GeoCurrency::format($grandOperatingExpense, 'NGN', $currencyCode, $currencyLocale) }}</div>
                        <h3 class="mb-0 text-danger money-sm">{{ \App\Support\GeoCurrency::format($grandExpense, 'NGN', $currencyCode, $currencyLocale) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pl-summary-card">
                    <div class="card-body">
                        <h6 class="{{ $grandNet >= 0 ? 'text-primary' : 'text-warning' }} text-uppercase fw-bold report-metric-title pl-summary-label">{{ __('Net Profit/Loss') }}</h6>
                        <h3 class="mb-0 {{ $grandNet >= 0 ? 'text-primary' : 'text-warning' }} money-sm">{{ \App\Support\GeoCurrency::format($grandNet, 'NGN', $currencyCode, $currencyLocale) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="card mb-4 filter-card no-print pl-filter-card">
            <div class="card-body">
                {{-- CORRECTED ROUTE: reports.profit-loss --}}
                <form method="GET" action="{{ route('reports.profit-loss', $routeParams) }}">
                    <div class="row align-items-end g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">{{ __('Start Date') }}</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">{{ __('End Date') }}</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-lg-4 col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                <i class="fas fa-filter"></i> {{ __('Apply Filter') }}
                            </button>
                            {{-- CORRECTED ROUTE: reports.profit-loss --}}
                            <a href="{{ route('reports.profit-loss', $routeParams) }}" class="btn btn-light btn-sm border">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data Table --}}
        <div class="card mb-4 pl-table-card">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="profitLossTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Transaction Date') }}</th>
                                <th>{{ __('Income (₦)') }}</th>
                                <th>{{ __('Purchases (₦)') }}</th>
                                <th>{{ __('OpEx (₦)') }}</th>
                                <th>{{ __('Expenses (₦)') }}</th>
                                <th>{{ __('Net (₦)') }}</th>
                                <th class="text-center">{{ __('Performance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profitLossData as $item)
                                @php $dailyProfit = $item->income - $item->expense; @endphp
                                <tr>
                                    <td class="fw-bold pl-date-cell">{{ \Carbon\Carbon::parse($item->report_date)->format('D, d M Y') }}</td>
                                    <td class="text-success fw-semibold table-money">+{{ number_format($item->income, 2) }}</td>
                                    <td class="text-info table-money">-{{ number_format($item->purchase_expense ?? 0, 2) }}</td>
                                    <td class="text-secondary table-money">-{{ number_format($item->operating_expense ?? 0, 2) }}</td>
                                    <td class="text-danger table-money">-{{ number_format($item->expense, 2) }}</td>
                                    <td class="fw-bold table-money {{ $dailyProfit >= 0 ? 'text-primary' : 'text-warning' }}">
                                        {{ number_format($dailyProfit, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if($dailyProfit > 0)
                                            <span class="badge bg-success-light text-success">PROFIT</span>
                                        @elseif($dailyProfit < 0)
                                            <span class="badge bg-danger-light text-danger">LOSS</span>
                                        @else
                                            <span class="badge bg-light text-muted">ZERO</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td>PAGE TOTALS</td>
                                <td class="text-success">₦{{ number_format($pageIncome, 2) }}</td>
                                <td class="text-info">₦{{ number_format($pagePurchaseExpense, 2) }}</td>
                                <td class="text-secondary">₦{{ number_format($pageOperatingExpense, 2) }}</td>
                                <td class="text-danger">₦{{ number_format($pageExpense, 2) }}</td>
                                <td colspan="2" class="{{ $pageNet >= 0 ? 'text-primary' : 'text-warning' }}">
                                    ₦{{ number_format($pageNet, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3 pagination-wrapper no-print pl-pagination">
                    {{ $profitLossData->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    if ($('#profitLossTable').length > 0) {
        $('#profitLossTable').DataTable({
            "bFilter": true,
            "bInfo": true,
            "paging": false,
            "autoWidth": false,
            "order": [[0, "desc"]],
            "language": {
                search: ' ',
                searchPlaceholder: "Search report date or amount..."
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'dt-button',
                    text: '<i class="fas fa-table me-1"></i> Excel',
                    title: 'Profit_Loss_Statement_{{ $reportDate }}',
                    footer: true
                },
                {
                    extend: 'pdfHtml5',
                    className: 'dt-button',
                    text: '<i class="fas fa-file-pdf me-1"></i> PDF',
                    title: 'Profit and Loss Statement',
                    footer: true,
                    orientation: 'portrait',
                    pageSize: 'A4',
                    customize: function(doc) {
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 10;
                    }
                }
            ]
        });
    }

    // Email Logic using Grand Totals
    $('#emailReportBtn').on('click', function() {
        const subject = "Business P&L Summary: {{ $reportDate }}";
        const body =
            "Hello,\n\nHere is the financial summary for the selected period:\n\n" +
            "Grand Total Revenue: ₦{{ number_format($grandIncome, 2) }}\n" +
            "Purchase Cost: ₦{{ number_format($grandPurchaseExpense, 2) }}\n" +
            "Operating Expenses: ₦{{ number_format($grandOperatingExpense, 2) }}\n" +
            "Grand Total Expenses: ₦{{ number_format($grandExpense, 2) }}\n" +
            "Net Profit/Loss: ₦{{ number_format($grandNet, 2) }}\n\n" +
            "Generated via SmartProbook.";
        const token = '{{ csrf_token() }}';
        const recipient = prompt('Send report to email (leave blank to use your account email):') ?? null;
        if (recipient === null) return;
        $('#emailReportBtn').prop('disabled', true).text('Sending...');
        fetch("{{ route('reports.email-report') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ subject, body, recipient: recipient.trim() || null })
        })
        .then(res => res.json())
        .then(data => alert(data.message || 'Email request sent.'))
        .catch(() => alert('Email failed. Please check mail settings.'))
        .finally(() => $('#emailReportBtn').prop('disabled', false).html('<i class="fas fa-envelope"></i> {{ __('Email Summary') }}'));
    });
});

/** Shared Printing Script **/
function printPage() {
    window.print();
}
</script>
@endsection
