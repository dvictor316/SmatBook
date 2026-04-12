<?php $page = 'income-report'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        
        <div class="page-header mb-3 no-print">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="fw-bold mb-1 report-page-title">Executive Cash Flow Report</h4>
                    <p class="text-muted mb-0 report-page-subtitle">Revenue and expenditure analysis.</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm shadow-sm">
                        <button onclick="window.print()" class="btn btn-white border report-action-btn"><i class="feather-printer"></i> Print</button>
                        <button id="export_pdf" class="btn btn-white border text-danger report-action-btn"><i class="feather-file-text"></i> PDF</button>
                        <button id="export_excel" class="btn btn-white border text-success report-action-btn"><i class="feather-file"></i> Excel</button>
                    </div>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Income and Outflow Report',
            'periodLabel' => 'Window: ' . $fromDate . ' to ' . $toDate,
        ])

        <div class="card shadow-none border mb-3 no-print">
            <div class="card-body p-2">
                <form action="{{ route('reports.income') }}" method="GET" class="row gx-2 align-items-end">
                    <div class="col-md-4">
                        <label class="report-filter-label">Start Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm border-0 bg-light" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="report-filter-label">End Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm border-0 bg-light" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold report-filter-action">Update Analysis</button>
                    </div>
                </form>
            </div>
        </div>

        @php 
            $tIn = $incomereports->sum('IncomeAmount');
            $tOut = $incomereports->sum('OutflowAmount');
            $tNet = $tIn - $tOut;
        @endphp

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 report-metric-card"><div class="card-body p-3">
                    <p class="text-muted mb-1 fw-bold uppercase report-metric-label">Total Inflow</p>
                    <h4 class="text-success fw-bold mb-0 report-metric-value">{{ \App\Support\GeoCurrency::format($tIn, 'NGN', $currencyCode, $currencyLocale) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 report-metric-card"><div class="card-body p-3">
                    <p class="text-muted mb-1 fw-bold uppercase report-metric-label">Total Outflow</p>
                    <h4 class="text-danger fw-bold mb-0 report-metric-value">{{ \App\Support\GeoCurrency::format($tOut, 'NGN', $currencyCode, $currencyLocale) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 {{ $tNet >= 0 ? 'bg-indigo' : 'bg-danger' }} mb-0 text-white">
                    <div class="card-body p-3">
                        <p class="text-white mb-1 fw-bold uppercase report-metric-label report-metric-label--light">Net Profit Amount</p>
                        <h4 class="text-white fw-bold mb-0 report-metric-value">{{ \App\Support\GeoCurrency::format($tNet, 'NGN', $currencyCode, $currencyLocale) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border shadow-none">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="incomeTable">
                    <thead>
                        <tr>
                            <th class="ps-3 py-2 text-muted">Date</th>
                            <th class="py-2 text-muted">Transaction Types</th>
                            <th class="py-2 text-end text-muted">Inflow</th>
                            <th class="py-2 text-end text-muted">Outflow</th>
                            <th class="pe-3 py-2 text-end text-muted">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incomereports as $report)
                        <tr class="accounting-row">
                            <td class="ps-3 py-2 fw-bold text-dark">{{ $report['Date'] }}</td>
                            <td class="py-2">
                                @foreach(explode(', ', $report['TypeLabel']) as $label)
                                    <span class="badge border bg-white text-muted fw-normal report-type-badge">{{ strtoupper($label) }}</span>
                                @endforeach
                            </td>
                            <td class="py-2 text-end text-success fw-bold">{{ \App\Support\GeoCurrency::format($report['IncomeAmount'], 'NGN', $currencyCode, $currencyLocale) }}</td>
                            <td class="py-2 text-end text-danger">{{ \App\Support\GeoCurrency::format($report['OutflowAmount'], 'NGN', $currencyCode, $currencyLocale) }}</td>
                            <td class="pe-3 py-2 text-end fw-bold {{ $report['NetProfit'] >= 0 ? 'text-indigo' : 'text-danger' }}">{{ \App\Support\GeoCurrency::format($report['NetProfit'], 'NGN', $currencyCode, $currencyLocale) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-indigo { background: #1b2559 !important; }
    .text-indigo { color: #1b2559 !important; }
    .accounting-row:hover { background-color: #f0f4ff !important; cursor: pointer; }
    .report-page-title {
        color: #102a5a;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
    }
    .report-page-subtitle {
        font-size: 0.95rem;
    }
    .report-action-btn {
        font-size: 0.82rem;
    }
    .report-filter-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #102a5a;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .report-filter-action {
        min-height: 38px;
    }
    .report-metric-card {
        min-height: 100%;
    }
    .report-metric-label {
        font-size: 0.74rem;
        letter-spacing: 0.08em;
    }
    .report-metric-label--light {
        color: rgba(255, 255, 255, 0.86);
    }
    .report-metric-value {
        font-size: clamp(0.92rem, 1.7vw, 1.05rem);
        letter-spacing: -0.02em;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .report-type-badge {
        font-size: 0.68rem;
        padding: 0.35rem 0.55rem;
        letter-spacing: 0.04em;
    }
    #incomeTable thead th {
        font-size: 0.74rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    #incomeTable tbody td {
        font-size: 0.94rem;
    }
    @media (max-width: 767.98px) {
        .report-metric-value {
            font-size: 0.88rem;
        }
    }
    @media print { 
        .no-print { display: none !important; } 
        .table td, .table th { font-size: 10pt !important; border: 1px solid #eee !important; } 
    }
</style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<script>
    // EXCEL EXPORT - Fully Restored
    document.getElementById('export_excel').addEventListener('click', function() {
        var table = document.getElementById("incomeTable");
        var wb = XLSX.utils.table_to_book(table, {sheet: "Cash Flow"});
        XLSX.writeFile(wb, "Financial_Report_{{ date('Y-m-d') }}.xlsx");
    });

    // PDF EXPORT - Fully Restored
    document.getElementById('export_pdf').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        doc.text("Executive Cash Flow Report", 14, 15);
        doc.autoTable({ 
            html: '#incomeTable', 
            startY: 20,
            theme: 'striped',
            headStyles: { fillColor: [27, 37, 89] } 
        });
        doc.save("Report_{{ date('Y-m-d') }}.pdf");
    });
</script>
@endpush
