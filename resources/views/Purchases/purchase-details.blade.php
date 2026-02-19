<?php $page = 'purchase-details'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            
            {{-- Page Header --}}
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Purchase Details</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{url('index')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('purchases')}}">Purchases</a></li>
                            <li class="breadcrumb-item active">Details</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <div class="d-print-none"> 
                            {{-- Native Browser Print --}}
                            <button onclick="window.print()" class="btn btn-white text-black border me-1">
                                <i class="fe fe-printer"></i> Print
                            </button>
                            
                            {{-- Fast Browser-Side PDF --}}
                            <button onclick="generatePDF()" class="btn btn-white text-black border me-1">
                                <i class="fe fe-file-text"></i> PDF
                            </button>
                            
                            {{-- Fast Browser-Side Excel --}}
                            <button onclick="generateExcel()" class="btn btn-white text-black border">
                                <i class="fe fe-file"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- The Invoice Content (This ID 'invoice-content' is used for PDF generation) --}}
            <div class="row justify-content-center" id="invoice-content">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-table">
                                <div class="card-body">
                                    
                                    <div class="invoice-item invoice-item-one">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <div class="invoice-logo">
                                                    <img src="{{ asset($logo) }}" alt="logo" style="max-height: 70px;">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="invoice-info text-md-end">
                                                    <h1 class="text-uppercase text-primary">Purchase</h1>
                                                    <p class="mb-0">Ref: <strong>{{ $purchase->purchase_no }}</strong></p>
                                                    <p>Status: 
                                                        <span class="badge {{ $purchase->status == 'paid' ? 'bg-success-light' : 'bg-warning-light' }}">
                                                            {{ ucfirst($purchase->status ?? 'Pending') }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="invoice-item invoice-item-date border-bottom mb-3 pb-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="text-start invoice-details">
                                                    Date<span>: </span><strong>{{ $purchase->created_at->format('d M, Y') }}</strong>
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="invoice-info">
                                                    <strong class="customer-text-one">Vendor:</strong>
                                                    <p class="invoice-details-two">
                                                        {{ $purchase->vendor->name ?? 'N/A' }}<br>
                                                        {{ $purchase->vendor->address ?? 'No Address Provided' }}<br>
                                                        {{ $purchase->vendor->email ?? '' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="invoice-info text-md-end">
                                                    <strong class="customer-text-one">Payment Bank:</strong>
                                                    <p class="invoice-details-two">
                                                        {{ $purchase->bank->bank_name ?? 'Cash/Other' }}<br>
                                                        {{ $purchase->bank->account_no ?? '' }}<br>
                                                        {{ $purchase->bank->holder_name ?? '' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="invoice-item invoice-table-wrap">
                                        <div class="table-responsive">
                                            <table class="table table-center table-hover mb-4" id="items-table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th class="text-center">Qty</th>
                                                        <th>Rate</th>
                                                        <th>Tax</th>
                                                        <th class="text-end">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($purchase->items as $item)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                                            <p class="small text-muted mb-0">{{ $item->product->sku ?? '' }}</p>
                                                        </td>
                                                        <td class="text-center">{{ $item->qty }}</td>
                                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                                        <td>{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                                                        <td class="text-end font-weight-bold">{{ number_format($item->total_amount, 2) }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="5" class="text-center p-4">No items recorded.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-7 col-md-6">
                                            <div class="invoice-terms">
                                                <h6>Notes:</h6>
                                                <p class="mb-0 text-muted">{{ $purchase->notes ?? 'No special notes.' }}</p>
                                            </div>
                                        </div>
                                        <div class="col-lg-5 col-md-6">
                                            <div class="invoice-total-card">
                                                <div class="invoice-total-box">
                                                    <div class="invoice-total-inner">
                                                        <p>Subtotal <span>{{ number_format($purchase->total_amount - ($purchase->tax_amount ?? 0), 2) }}</span></p>
                                                        <p>Tax <span>{{ number_format($purchase->tax_amount ?? 0, 2) }}</span></p>
                                                    </div>
                                                    <div class="invoice-total-footer bg-light p-2">
                                                        <h4 class="mb-0">Grand Total <span>{{ number_format($purchase->total_amount, 2) }}</span></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="invoice-sign text-end mt-5">
                                        <p class="mb-0">Authorized By</p>
                                        <img src="{{ asset('assets/img/signature.png') }}" alt="sign" width="120">
                                        <span class="d-block mt-2">{{ auth()->user()->name ?? 'Administrator' }}</span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPTS FOR FAST EXPORT --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script>
        // PDF LOGIC
        function generatePDF() {
            const element = document.getElementById('invoice-content');
            const opt = {
                margin:       10,
                filename:     'Purchase_{{ $purchase->purchase_no }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }

        // EXCEL LOGIC
        function generateExcel() {
            let table = document.getElementById("items-table");
            let html = table.outerHTML;
            let url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            let link = document.createElement("a");
            link.download = "Purchase_{{ $purchase->purchase_no }}.xls";
            link.href = url;
            link.click();
        }
    </script>

    <style>
        @media print {
            .sidebar, .header, .page-header .d-print-none, .breadcrumb { display: none !important; }
            .page-wrapper { margin: 0 !important; padding: 0 !important; }
            .card { border: none !important; }
        }
    </style>
@endsection