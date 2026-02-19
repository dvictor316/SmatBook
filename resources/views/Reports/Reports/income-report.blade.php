<?php $page = 'income-report'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        
        <div class="page-header mb-3 no-print">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="fw-bold mb-0" style="color: #1b2559; font-size: 16px;">Executive Cash Flow Report</h4>
                    <p class="text-muted mb-0" style="font-size: 11px;">Revenue & Expenditure Analysis</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm shadow-sm">
                        <button onclick="window.print()" class="btn btn-white border"><i class="feather-printer"></i> Print</button>
                        <button id="export_pdf" class="btn btn-white border text-danger"><i class="feather-file-text"></i> PDF</button>
                        <button id="export_excel" class="btn btn-white border text-success"><i class="feather-file"></i> Excel</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-none border mb-3 no-print">
            <div class="card-body p-2">
                <form action="{{ route('reports.income') }}" method="GET" class="row gx-2 align-items-end">
                    <div class="col-md-4">
                        <label class="fw-bold text-muted mb-1" style="font-size: 10px;">START DATE</label>
                        <input type="date" name="from_date" class="form-control form-control-sm border-0 bg-light" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold text-muted mb-1" style="font-size: 10px;">END DATE</label>
                        <input type="date" name="to_date" class="form-control form-control-sm border-0 bg-light" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" style="background: #1b2559; height: 31px;">UPDATE ANALYSIS</button>
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
                <div class="card border shadow-none mb-0"><div class="card-body p-3">
                    <p class="text-muted mb-0 fw-bold uppercase" style="font-size: 10px;">Total Inflow</p>
                    <h4 class="text-success fw-bold mb-0" style="font-size: 16px;">₦{{ number_format($tIn, 2) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0"><div class="card-body p-3">
                    <p class="text-muted mb-0 fw-bold uppercase" style="font-size: 10px;">Total Outflow</p>
                    <h4 class="text-danger fw-bold mb-0" style="font-size: 16px;">₦{{ number_format($tOut, 2) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 {{ $tNet >= 0 ? 'bg-indigo' : 'bg-danger' }} mb-0 text-white">
                    <div class="card-body p-3">
                        <p class="text-white mb-0 fw-bold uppercase" style="font-size: 10px; opacity: 1;">Net Profit Amount</p>
                        <h4 class="text-white fw-bold mb-0" style="font-size: 16px;">₦{{ number_format($tNet, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border shadow-none">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="incomeTable">
                    <thead style="background: #f8f9fc;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size: 11px;">Date</th>
                            <th class="py-2 text-muted" style="font-size: 11px;">Transaction Types</th>
                            <th class="py-2 text-end text-muted" style="font-size: 11px;">Inflow</th>
                            <th class="py-2 text-end text-muted" style="font-size: 11px;">Outflow</th>
                            <th class="pe-3 py-2 text-end text-muted" style="font-size: 11px;">Net</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 12px;">
                        @foreach($incomereports as $report)
                        <tr class="accounting-row">
                            <td class="ps-3 py-2 fw-bold text-dark">{{ $report['Date'] }}</td>
                            <td class="py-2">
                                @foreach(explode(', ', $report['TypeLabel']) as $label)
                                    <span class="badge border bg-white text-muted fw-normal" style="font-size: 9px;">{{ strtoupper($label) }}</span>
                                @endforeach
                            </td>
                            <td class="py-2 text-end text-success fw-bold">₦{{ number_format($report['IncomeAmount'], 0) }}</td>
                            <td class="py-2 text-end text-danger">₦{{ number_format($report['OutflowAmount'], 0) }}</td>
                            <td class="pe-3 py-2 text-end fw-bold {{ $report['NetProfit'] >= 0 ? 'text-indigo' : 'text-danger' }}">₦{{ number_format($report['NetProfit'], 0) }}</td>
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