<?php $page = 'stock-report'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        
        {{-- Header & Multi-Export Actions --}}
        <div class="page-header mb-3 no-print">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="fw-bold mb-0" style="color: #1b2559; font-size: 16px;">Stock & Valuation Ledger</h4>
                    <p class="text-muted mb-0" style="font-size: 11px;">Consolidated Purchase vs Sales Analysis</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm shadow-sm">
                        <button onclick="window.print()" class="btn btn-white border" style="font-size: 12px;">
                            <i class="feather-printer me-1"></i> Print
                        </button>
                        <button id="export_pdf" class="btn btn-white border text-danger" style="font-size: 12px;">
                            <i class="feather-file-text me-1"></i> PDF
                        </button>
                        <button id="export_excel" class="btn btn-white border text-success" style="font-size: 12px;">
                            <i class="feather-file me-1"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Inventory Valuation Report',
            'periodLabel' => 'Window: ' . $fromDate . ' to ' . $toDate,
        ])

        {{-- Filters --}}
        <div class="card shadow-none border mb-3 no-print">
            <div class="card-body p-2">
                @php
                    $stockRouteName = \Illuminate\Support\Facades\Route::has('stock-report')
                        ? 'stock-report'
                        : (\Illuminate\Support\Facades\Route::has('stock') ? 'stock' : null);
                @endphp
                <form action="{{ $stockRouteName ? route($stockRouteName) : url('/stock-report') }}" method="GET">
                    <div class="row gx-2 align-items-end">
                        <div class="col-md-3">
                            <label class="fw-bold text-muted mb-1" style="font-size: 9px;">START DATE</label>
                            <input type="date" name="from_date" class="form-control form-control-sm border-0 bg-light" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold text-muted mb-1" style="font-size: 9px;">END DATE</label>
                            <input type="date" name="to_date" class="form-control form-control-sm border-0 bg-light" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold text-muted mb-1" style="font-size: 9px;">PRODUCT FILTER</label>
                            <select name="product_id" class="form-select form-select-sm border-0 bg-light">
                                <option value="">All Inventory Items</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" style="background: #1b2559; height: 31px; font-size: 11px;">FILTER</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @php 
            $tValIn = $stockreports->sum('ValIn');
            $tValOut = $stockreports->sum('ValOut');
            $tNet = $tValIn - $tValOut;
        @endphp

        {{-- Summary Cards --}}
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card border shadow-none mb-0"><div class="card-body p-3">
                    <p class="text-muted mb-0 fw-bold uppercase" style="font-size: 10px;">Total Purchases (In)</p>
                    <h4 class="text-success fw-bold mb-0" style="font-size: 16px;">₦{{ number_format($tValIn, 2) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0"><div class="card-body p-3">
                    <p class="text-muted mb-0 fw-bold uppercase" style="font-size: 10px;">Total Sales (Out)</p>
                    <h4 class="text-danger fw-bold mb-0" style="font-size: 16px;">₦{{ number_format($tValOut, 2) }}</h4>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 {{ $tNet >= 0 ? 'bg-indigo' : 'bg-danger' }} mb-0 shadow-sm">
                    <div class="card-body p-3 text-white">
                        <p class="text-white mb-0 fw-bold uppercase" style="font-size: 10px; opacity: 1;">Net Valuation Change</p>
                        <h4 class="text-white fw-bold mb-0" style="font-size: 16px;">₦{{ number_format($tNet, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ledger Table --}}
        <div class="card border shadow-none overflow-hidden">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="stockTable">
                    <thead style="background: #f8f9fc;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size: 10px;">DATE</th>
                            <th class="py-2 text-center text-muted" style="font-size: 10px;">ORDERS</th>
                            <th class="py-2 text-end text-muted" style="font-size: 10px;">PURCHASE VALUE</th>
                            <th class="py-2 text-end text-muted" style="font-size: 10px;">SALES VALUE</th>
                            <th class="pe-3 py-2 text-end text-muted" style="font-size: 10px;">NET FLOW</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 11px;">
                        @forelse($stockreports as $report)
                        <tr class="accounting-row">
                            <td class="ps-3 py-2 fw-bold text-dark">{{ $report['Date'] }}</td>
                            <td class="py-2 text-center text-muted">{{ number_format($report['QtyIn'] + $report['QtyOut']) }}</td>
                            <td class="py-2 text-end text-success">₦{{ number_format($report['ValIn'], 2) }}</td>
                            <td class="py-2 text-end text-danger">₦{{ number_format($report['ValOut'], 2) }}</td>
                            <td class="pe-3 py-2 text-end fw-bold {{ $report['NetValue'] >= 0 ? 'text-indigo' : 'text-danger' }}">
                                ₦{{ number_format($report['NetValue'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-indigo { background: #1b2559 !important; }
    .text-indigo { color: #1b2559 !important; }
    .accounting-row:hover { background-color: #f8faff !important; cursor: pointer; }
    @media print { .no-print { display: none !important; } }
</style>
@endsection

@push('scripts')
{{-- Export Libraries --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<script>
    // Excel Logic
    document.getElementById('export_excel').addEventListener('click', function() {
        const table = document.getElementById("stockTable");
        const wb = XLSX.utils.table_to_book(table, { sheet: "StockValuation" });
        XLSX.writeFile(wb, "Stock_Report_{{ date('Y-m-d') }}.xlsx");
    });

    // PDF Logic
    document.getElementById('export_pdf').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4');
        
        doc.setFontSize(16);
        doc.text("Stock & Valuation Ledger", 40, 40);
        doc.setFontSize(10);
        doc.text("Report Period: {{ $fromDate }} to {{ $toDate }}", 40, 60);

        doc.autoTable({
            html: '#stockTable',
            startY: 80,
            theme: 'striped',
            headStyles: { fillColor: [27, 37, 89], fontSize: 9 },
            bodyStyles: { fontSize: 8 },
            margin: { left: 40, right: 40 }
        });

        doc.save("Stock_Report_{{ date('Y-m-d') }}.pdf");
    });
</script>
@endpush
