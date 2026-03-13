<?php $page = 'invoice-details'; ?>
@extends('layout.mainlayout')

@section('content')
    @php
        $invoiceLogoPath = \App\Models\Setting::where('key', 'invoice_logo')->value('value')
            ?: \App\Models\Setting::where('key', 'site_logo')->value('value');
        $invoiceLogoUrl = $invoiceLogoPath ? asset($invoiceLogoPath) : null;
        $brandName = \App\Models\Setting::where('key', 'company_name')->value('value')
            ?: (optional(auth()->user())->company->name ?? config('app.name', 'SmartProbook'));
        $appliedAmount = (float) ($sale->amount_paid ?? $sale->paid ?? 0);
        $changeAmount = (float) ($sale->change_amount ?? 0);
        $tenderedAmount = $appliedAmount + max(0, $changeAmount);
        $balanceDue = max(0, (float) ($sale->total ?? 0) - $appliedAmount);
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    {{-- Navigation and Action Header (Hidden on Print) --}}
                    <div class="page-header d-print-none">
                        <div class="content-invoice-header">
                            <h5>Invoice Details</h5>
                            <div class="list-btn">
                                <ul class="filter-list">
                                    <li>
                                        <a href="javascript:window.print()" class="btn btn-primary">
                                            <i class="fa fa-print me-2"></i> Print Invoice
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                                            <i class="fa fa-arrow-left me-2"></i> Back to List
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <div class="invoice-card border-0">
                                <div class="card-body p-0">
                                    
                                    {{-- 1. Logo and Status Banner --}}
                                    <div class="invoice-item invoice-item-one pb-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <div class="invoice-logo">
                                                    @if(!empty($sale->company_logo))
                                                        <img src="{{ asset('storage/' . $sale->company_logo) }}" alt="logo" style="max-height: 70px;">
                                                    @elseif($invoiceLogoUrl)
                                                        <img src="{{ $invoiceLogoUrl }}" alt="logo" style="max-height: 70px;">
                                                    @else
                                                        <h2 class="text-primary fw-bold mb-0">{{ $brandName }}</h2>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <div class="invoice-info">
                                                    {{-- Fixed: Changed 'status' to 'payment_status' and matched lowercase 'paid' --}}
                                                    <h1 class="{{ ($sale->payment_status == 'paid') ? 'text-success' : 'text-danger' }} fw-bold mb-1">
                                                        {{ strtoupper($sale->payment_status ?? 'UNPAID') }}
                                                    </h1>
                                                    <p class="text-muted mb-0">Invoice #{{ $sale->invoice_no ?? $sale->id }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. Date and Reference Bar --}}
                                    <div class="invoice-item invoice-item-date border-top border-bottom py-3 my-4 bg-light-soft">
                                        <div class="row text-center text-md-start">
                                            <div class="col-md-4">
                                                <p class="mb-0 text-muted small text-uppercase">Issue Date</p>
                                                <p class="mb-0 fw-bold">{{ $sale->created_at ? $sale->created_at->format('d M Y') : '---' }}</p>
                                            </div>
                                            <div class="col-md-4 text-md-center">
                                                <p class="mb-0 text-muted small text-uppercase">Reference</p>
                                                <p class="mb-0 fw-bold">{{ $sale->order_number ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 text-md-end">
                                                <p class="mb-0 text-muted small text-uppercase">Customer</p>
                                                <p class="mb-0 fw-bold">{{ $sale->display_customer_name }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. Populated Items Table --}}
                                    <div class="invoice-add-table mt-4">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr style="background-color: #f8f9fa;">
                                                        <th style="color: #4b308b; font-weight: 700;">Description</th>
                                                        <th style="color: #4b308b; font-weight: 700;">Unit Price</th>
                                                        <th style="color: #4b308b; font-weight: 700;">Qty</th>
                                                        <th style="color: #4b308b; font-weight: 700;">Discount</th>
                                                        <th style="color: #4b308b; font-weight: 700;" class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($sale->items as $item)
                                                        <tr style="background-color: #fcfcfc;">
                                                            <td class="text-dark fw-bold">
                                                                {{ $item->product->name ?? 'Product/Service' }}
                                                            </td>
                                                            {{-- Fixed: Using unit_price and quantity --}}
                                                            <td>{{ number_format($item->unit_price, 2) }}</td>
                                                            <td>{{ $item->qty ?? $item->quantity ?? 0 }}</td>
                                                            <td>{{ number_format($item->discount, 2) }}%</td>
                                                            <td class="text-end fw-bold text-dark">
                                                                {{-- Fixed: DB column is 'subtotal' --}}
                                                                {{ number_format($item->subtotal, 2) }}
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted p-4">No items listed.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- 4. Totals and Signatures --}}
                                    <div class="row mt-5 align-items-end">
                                        <div class="col-lg-7">
                                            <div class="invoice-notes p-3 bg-light rounded border-start border-primary border-4">
                                                <h6 class="fw-bold">Notes:</h6>
                                                <p class="text-muted small mb-0">{{ $sale->notes ?? 'Thank you for your business.' }}</p>
                                            </div>
                                            <div class="mt-4 pt-4">
                                                <p class="mb-0 text-muted">Authorised Signature</p>
                                                <div class="mt-2" style="border-bottom: 1px solid #ddd; width: 200px;"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-5">
                                            <div class="invoice-total-card p-3 border rounded bg-white shadow-sm">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Subtotal</span>
                                                    {{-- Fixed: Summing the 'subtotal' column --}}
                                                    <span class="fw-bold">{{ number_format($sale->items->sum('subtotal'), 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2 text-muted">
                                                    <span>Tax</span>
                                                    {{-- Fixed: DB column is 'tax' --}}
                                                    <span>{{ number_format($sale->tax ?? 0, 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2 text-muted">
                                                    <span>Amount Tendered</span>
                                                    <span>{{ number_format($tenderedAmount, 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2 text-muted">
                                                    <span>Applied to Sale</span>
                                                    <span>{{ number_format($appliedAmount, 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2 text-muted">
                                                    <span>Change</span>
                                                    <span>{{ number_format($changeAmount, 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2 text-muted">
                                                    <span>Balance Due</span>
                                                    <span>{{ number_format($balanceDue, 2) }}</span>
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h4 class="text-primary fw-bold mb-0">Total</h4>
                                                    {{-- Fixed: DB column is 'total' --}}
                                                    <h4 class="text-primary fw-bold mb-0">{{ number_format($sale->total, 2) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Print Management Styling --}}
    <style>
        .bg-light-soft { background-color: #fbfbfb; }
        
        @media print {
            .page-header, .sidebar, .header, .btn, .list-btn, .footer, .d-print-none {
                display: none !important;
            }
            .page-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; margin-left: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
            .content { padding: 0 !important; }
            body { background-color: #fff !important; }
            thead tr th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
            .bg-light-soft { background-color: #fbfbfb !important; -webkit-print-color-adjust: exact; }
        }
    </style>
@endsection
