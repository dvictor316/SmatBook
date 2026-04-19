@extends('layout.mainlayout')

@section('content')
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">

                <div class="card invoice-info-custom shadow-sm" id="printableArea">
                    <div class="card-body p-5">
                        <div class="invoice-item invoice-item-one">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="invoice-logo mb-3">
                                        <img src="{{ asset('assets/img/logos.png') }}" alt="logo" style="max-width: 150px;">
                                    </div>
                                    <div class="invoice-head">
                                        <h2 class="fw-bold text-primary" style="color: #4b308b !important;">Invoice</h2>
                                        <p class="mb-0"><strong>Invoice Number :</strong> {{ $invoice->invoice_no }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end d-print-none">

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
                                        <strong>Total Amount:</strong> {{ \App\Support\GeoCurrency::format((float) ($invoice->total ?? 0), 'NGN', $currencyCode, $currencyLocale) }}<br>
                                        <strong>Status:</strong> <span class="text-uppercase">{{ $invoice->status }}</span><br>
                                        <strong>Date:</strong> {{ $invoice->created_at->format('d M Y') }}<br>

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
                                        <td class="text-end py-3">{{ \App\Support\GeoCurrency::format((float) ($invoice->subtotal ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-3">Tax</td>
                                        <td class="text-end py-3">{{ \App\Support\GeoCurrency::format((float) ($invoice->tax ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                    </tr>
                                    <tr class="fw-bold fs-5" style="background-color: #f8f9fa;">
                                        <td class="py-3">Grand Total</td>
                                        <td class="text-end py-3" style="color: #4b308b;">{{ \App\Support\GeoCurrency::format((float) ($invoice->total ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

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
        window.print();
    }
</script>
@endsection
