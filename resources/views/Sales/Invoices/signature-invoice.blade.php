@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        {{-- Page Header: Hidden during print --}}
        <div class="page-header no-print">
            <div class="content-page-header">
                <h5>Invoice Preview</h5>
                <div class="list-btn">
                    <ul class="filter-list">
                        <li>
                            <a class="btn btn-primary" href="javascript:window.print();">
                                <i class="fa fa-print me-2"></i>Print Invoice
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        {{-- Invoice Header --}}
                        <div class="invoice-item">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="invoice-logo">
                                        <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook Logo" style="max-width: 150px;">
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h2 class="text-uppercase text-primary">Invoice</h2>
                                    <h5>#{{ $invoice->invoice_number ?? 'N/A' }}</h5>
                                    <p class="text-muted mb-0">Date: {{ isset($invoice->created_at) ? $invoice->created_at->format('d M Y') : date('d M Y') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Client & Company Details --}}
                        <div class="invoice-item mt-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="invoice-name">Invoice To:</h6>
                                    <p class="invoice-details">
                                        <strong>{{ $invoice->customer->name ?? 'Walking Customer' }}</strong><br>
                                        {{ $invoice->customer->address ?? 'No Address Provided' }}<br>
                                        {{ $invoice->customer->phone ?? '' }}
                                    </p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6 class="invoice-name">Status:</h6>
                                    <span class="badge bg-success-light">{{ ucfirst($invoice->status ?? 'Paid') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <div class="table-responsive mt-4">
                            <table class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Item Description</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->items ?? [] as $item)
                                    <tr>
                                        <td>{{ $item->name ?? $item->product_name }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">{{ number_format($item->price, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->quantity * $item->price, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No items found for this invoice.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Calculation & Totals --}}
                        <div class="row mt-4">
                            <div class="col-md-7">
                                <div class="invoice-info">
                                    <h6>Notes:</h6>
                                    <p class="text-muted small">Thank you for your business. Please make payment within 15 days.</p>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="invoice-total-card">
                                    <div class="invoice-total-box">
                                        <div class="invoice-total-inner">
                                            <p>Subtotal <span>{{ number_format($invoice->subtotal ?? 0, 2) }}</span></p>
                                            <p>Tax <span>{{ number_format($invoice->tax ?? 0, 2) }}</span></p>
                                            <div class="status-toggle h4 text-end mt-2">
                                                <strong>Total: {{ number_format($invoice->total_amount ?? 0, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Dynamic Signature Section --}}
                        <div class="row mt-5">
                            <div class="col-md-6"></div>
                            <div class="col-md-6 text-end">
                                <div class="signature-display">
                                    <p class="mb-2">Authorized Signature</p>
                                    @if($signature)
                                        <div class="mb-2">
                                            <img src="{{ $signature->image_url }}" alt="Signature" style="max-width: 180px; max-height: 100px;">
                                        </div>
                                        <p class="mt-0"><strong>{{ $signature->name }}</strong></p>
                                    @else
                                        <div style="border-bottom: 2px solid #333; width: 200px; display: inline-block; height: 50px; margin-bottom: 5px;"></div>
                                        <p class="text-muted small">Signature Pending</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Printing Styles --}}
<style>
    @media print {
        .header, .sidebar, .no-print, .list-btn {
            display: none !important;
        }
        .page-wrapper { 
            margin: 0 !important; 
            padding: 0 !important; 
            left: 0 !important;
        }
        .card { 
            border: none !important; 
            box-shadow: none !important;
        }
        body {
            background-color: #fff !important;
        }
    }
</style>
@endsection