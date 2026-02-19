@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                {{-- ID used by the custom print script --}}
                <div class="card invoice-info-custom shadow-sm" id="printableArea">
                    <div class="card-body p-5">
                        <div class="invoice-item invoice-item-one">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="invoice-logo mb-3">
                                        <img src="{{ asset('assets/img/smat14.png') }}" alt="logo" style="max-width: 150px;">
                                    </div>
                                    <div class="invoice-head">
                                        <h2 class="fw-bold text-primary" style="color: #4b308b !important;">Invoice</h2>
                                        <p class="mb-0"><strong>Invoice Number :</strong> {{ $invoice->invoice_no }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end d-print-none">
                                    {{-- Custom Print Button --}}
                                    <button onclick="printInvoice()" class="btn btn-primary">
                                        <i class="fa fa-print me-1"></i> Print Invoice
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="invoice-item invoice-item-two mt-5">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted text-uppercase small fw-bold">Invoice To :</h6>
                                    <p class="invoice-details-two">
                                        <strong class="text-dark fs-5">{{ $invoice->customer_name }}</strong><br>
                                        {{ $invoice->customer->address ?? 'No Address Provided' }}<br>
                                        {{ $invoice->customer->phone ?? '' }}
                                    </p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6 class="text-muted text-uppercase small fw-bold">Payment Details :</h6>
                                    <p class="invoice-details-two">
                                        <strong>Total Amount:</strong> ${{ number_format($invoice->total, 2) }}<br>
                                        <strong>Status:</strong> <span class="text-uppercase">{{ $invoice->status }}</span><br>
                                        <strong>Date:</strong> {{ $invoice->created_at->format('d M Y') }}<br>
                                        {{-- Added Sales Person --}}
                                        <strong>Sales Person:</strong> {{ $invoice->user->name ?? 'System' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="py-3">Description</th>
                                        <th class="text-end py-3">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="py-3">Subtotal</td>
                                        <td class="text-end py-3">${{ number_format($invoice->subtotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-3">Tax</td>
                                        <td class="text-end py-3">${{ number_format($invoice->tax, 2) }}</td>
                                    </tr>
                                    <tr class="fw-bold fs-5" style="background-color: #f8f9fa;">
                                        <td class="py-3">Grand Total</td>
                                        <td class="text-end py-3" style="color: #4b308b;">${{ number_format($invoice->total, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- Print-only Footer --}}
                        <div class="d-none d-print-block mt-5 text-center">
                            <hr>
                            <p class="text-muted small">Thank you for your business!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function printInvoice() {
        const printContents = document.getElementById('printableArea').innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = `
            <html>
                <head>
                    <title>Invoice_{{ $invoice->invoice_no }}</title>
                    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
                    <style>
                        body { background: white !important; padding: 40px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
                        .invoice-info-custom { border: none !important; }
                        .text-primary { color: #4b308b !important; }
                        .table th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
                        .d-print-none { display: none !important; }
                        .card { border: none !important; }
                    </style>
                </head>
                <body>${printContents}</body>
            </html>`;

        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload(); 
    }
</script>
@endsection