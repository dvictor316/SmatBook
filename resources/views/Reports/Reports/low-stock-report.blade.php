<?php $page = 'low-stock-report'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        
        {{-- Header Section --}}
        <div class="page-header mb-4 no-print">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="fw-bold mb-1" style="color: #4e5d78;">Inventory Analysis</h4>
                    <p class="text-muted mb-0" style="font-size: 12px;">Monitoring items below threshold ({{ $threshold }})</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group shadow-sm bg-white">
                        <button id="btn_email" class="btn btn-white border text-primary btn-sm"><i class="feather-mail me-1"></i> Email</button>
                        <button onclick="window.print()" class="btn btn-white border btn-sm"><i class="feather-printer me-1"></i> Print</button>
                        <button id="btn_pdf" class="btn btn-white border text-danger btn-sm"><i class="feather-file-text me-1"></i> PDF</button>
                        <button id="btn_excel" class="btn btn-white border text-success btn-sm"><i class="feather-file me-1"></i> Excel</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Light Summary Cards --}}
        @php $totalValuation = $products->sum(fn($p) => $p->stock * $p->purchase_price); @endphp
        <div class="row g-3 mb-4 no-print">
            <div class="col-md-4">
                <div class="card border shadow-none mb-0" style="border-left: 4px solid #3b82f6 !important;">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold" style="font-size: 10px;">TOTAL CRITICAL ITEMS</small>
                        <h4 class="mb-0 fw-bold">{{ $products->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0" style="border-left: 4px solid #10b981 !important;">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold" style="font-size: 10px;">VALUATION AT RISK</small>
                        <h4 class="mb-0 fw-bold">{{ number_format($totalValuation, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Table --}}
        <div class="card border shadow-none overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="reportTable">
                    <thead style="background: #f9fafb;">
                        <tr class="text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                            <th class="ps-4 py-3 text-muted">Product Name</th>
                            <th class="py-3 text-muted">SKU</th>
                            <th class="py-3 text-end text-muted">Stock</th>
                            <th class="py-3 text-end text-muted">Value</th>
                            <th class="py-3 text-end text-primary">To Order</th>
                            <th class="pe-4 py-3 text-center text-muted">Action</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 12px;">
                        @forelse ($products as $product)
                            @php $needed = max(0, $target - $product->stock); @endphp
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-dark">{{ $product->name }}</td>
                                <td class="py-3 text-muted">{{ $product->sku }}</td>
                                <td class="py-3 text-end">
                                    <span class="text-danger fw-bold">{{ number_format($product->stock) }}</span>
                                </td>
                                <td class="py-3 text-end text-muted">{{ number_format($product->stock * $product->purchase_price, 2) }}</td>
                                <td class="py-3 text-end fw-bold text-primary">+{{ number_format($needed) }}</td>
                                <td class="pe-4 py-3 text-center">
                                    <a href="{{ url('purchase-add') }}?product_id={{ $product->id }}&qty={{ $needed }}" 
                                       class="btn btn-danger btn-xs py-1 px-3 fw-bold" 
                                       style="background: #ef4444 !important; border: none; font-size: 10px;">
                                        RESTOCK
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted">All inventory is stable.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-white { background: #fff; }
    .btn-xs { padding: 2px 8px; border-radius: 4px; }
    @media print { .no-print { display: none !important; } }
</style>
@endsection

@push('scripts')
{{-- Libraries for Export --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<script>
    // 1. Email Handler
    document.getElementById('btn_email').addEventListener('click', function() {
        let btn = this;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        
        fetch("{{ route('reports.email-low-stock') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => alert(data.message))
        .finally(() => btn.innerHTML = '<i class="feather-mail me-1"></i> Email');
    });

    // 2. Excel Handler
    document.getElementById('btn_excel').addEventListener('click', function() {
        XLSX.writeFile(XLSX.utils.table_to_book(document.getElementById("reportTable")), "Stock_Report.xlsx");
    });

    // 3. PDF Handler
    document.getElementById('btn_pdf').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4');
        doc.text("Inventory Alert Report", 40, 40);
        doc.autoTable({ html: '#reportTable', startY: 60 });
        doc.save("Low_Stock_Report.pdf");
    });
</script>
@endpush