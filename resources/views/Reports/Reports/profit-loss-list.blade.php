@php
    $page = 'profit-loss-list';
    $reportDate = date('Y-m-d');
    
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
    #profitLossTable { font-size: 12.5px !important; border-collapse: separate; border-spacing: 0; }
    #profitLossTable thead th { 
        font-size: 0.75rem !important; 
        text-transform: uppercase; 
        letter-spacing: 0.08em; 
        background-color: #f1f5f9; 
        color: #102a5a;
        border-bottom: 1px solid #dbe7f5;
    }
    .card-body h3 { font-size: 1.18rem; font-weight: 800; letter-spacing: -0.2px; }
    .badge { font-size: 0.72rem; padding: 0.4rem 0.7rem; }
    .money-sm { font-size: 0.9rem !important; font-variant-numeric: tabular-nums; }
    .table-money { font-size: 0.82rem; font-variant-numeric: tabular-nums; }
    .report-metric-title { letter-spacing: 0.08em; }

    .dt-buttons { margin-bottom: 15px; gap: 5px; display: flex; }
    .dt-button { 
        border: 1px solid #d1d5db !important;
        background: #fff !important;
        font-size: 0.76rem !important;
        font-weight: 600 !important;
        border-radius: 999px !important;
        padding: 0.5rem 0.9rem !important;
        color: #102a5a !important;
    }
    .dt-button:hover { background: #f9fafb !important; }

    @media print {
        .no-print, .filter-card, .dataTables_filter, .dt-buttons, .pagination-wrapper { display: none !important; }
        .page-wrapper { margin: 0; padding: 0; background: white !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; margin-bottom: 10px !important; }
        .table { width: 100% !important; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Header Section --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">{{ __('Financial Statement (P&L)') }}</h3>
                    <ul class="breadcrumb" style="font-size: 12px;">
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
                <div class="card bg-success-light border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-success small text-uppercase fw-bold report-metric-title">{{ __('Overall Revenue') }}</h6>
                        <h3 class="mb-0 text-success money-sm">₦{{ number_format($grandIncome, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info-light border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-info small text-uppercase fw-bold report-metric-title">{{ __('Purchase Cost') }}</h6>
                        <h3 class="mb-0 text-info money-sm">₦{{ number_format($grandPurchaseExpense, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger-light border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-danger small text-uppercase fw-bold report-metric-title">{{ __('Total Expenses') }}</h6>
                        <div class="small text-muted mb-1">OpEx: ₦{{ number_format($grandOperatingExpense, 2) }}</div>
                        <h3 class="mb-0 text-danger money-sm">₦{{ number_format($grandExpense, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card {{ $grandNet >= 0 ? 'bg-primary-light' : 'bg-warning-light' }} border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="{{ $grandNet >= 0 ? 'text-primary' : 'text-warning' }} small text-uppercase fw-bold report-metric-title">{{ __('Net Profit/Loss') }}</h6>
                        <h3 class="mb-0 {{ $grandNet >= 0 ? 'text-primary' : 'text-warning' }} money-sm">₦{{ number_format($grandNet, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="card shadow-sm border-0 mb-4 filter-card no-print">
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
        <div class="card mb-4 border-0 shadow-sm">
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
                                    <td class="fw-bold text-muted">{{ \Carbon\Carbon::parse($item->report_date)->format('D, d M Y') }}</td>
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
                <div class="mt-3 pagination-wrapper no-print">
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
