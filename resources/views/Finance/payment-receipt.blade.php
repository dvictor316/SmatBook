@extends('layout.mainlayout')

@section('content')
@php
    $sale = $payment->sale;
    $company = auth()->user()?->company;
    $brandName = $company?->company_name ?? $company?->name ?? config('app.name', 'SmartProbook');
    $brandAddress = $company?->address ?? 'No company address configured';
    $brandEmail = $company?->email ?? auth()->user()?->email;
    $brandPhone = $company?->phone ?? auth()->user()?->phone;
    $customerName = $sale?->display_customer_name
        ?? $payment->customer?->customer_name
        ?? $payment->customer?->name
        ?? 'Walk-in Customer';
    $items = $sale?->items ?? collect();
    $saleSubtotal = (float) ($sale->subtotal ?? $items->sum(fn ($item) => ((float) ($item->qty ?? 0)) * ((float) ($item->unit_price ?? 0))));
    $saleDiscount = (float) ($sale->discount ?? 0);
    $saleTax = (float) ($sale->tax ?? 0);
    $saleTotal = (float) ($sale->total ?? $payment->amount);
    $salePaid = (float) ($sale->amount_paid ?? $payment->amount);
    $saleBalance = (float) ($sale->balance ?? max(0, $saleTotal - $salePaid));
@endphp

<style>
    .payment-receipt-shell {
        max-width: 1120px;
        margin: 0 auto;
    }
    .payment-receipt-card {
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
        border: 1px solid #e8eefc;
        border-radius: 24px;
        box-shadow: 0 20px 55px rgba(31, 63, 125, 0.08);
        overflow: hidden;
    }
    .payment-receipt-top {
        background: linear-gradient(135deg, #0f4fd6 0%, #4e7bff 100%);
        color: #fff;
        padding: 32px 36px;
    }
    .payment-receipt-brand {
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-bottom: 8px;
    }
    .payment-receipt-kicker {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        opacity: 0.8;
    }
    .payment-receipt-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-top: 24px;
    }
    .payment-receipt-pill {
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 18px;
        padding: 14px 16px;
    }
    .payment-receipt-pill-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        opacity: 0.78;
        margin-bottom: 6px;
    }
    .payment-receipt-pill-value {
        font-size: 18px;
        font-weight: 700;
    }
    .payment-receipt-body {
        padding: 32px 36px 36px;
    }
    .payment-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #16315f;
        margin-bottom: 16px;
    }
    .payment-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 14px;
        margin-bottom: 28px;
    }
    .payment-detail-card {
        border: 1px solid #e7edf9;
        border-radius: 18px;
        background: #fff;
        padding: 16px 18px;
    }
    .payment-detail-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #8c9bb6;
        margin-bottom: 6px;
    }
    .payment-detail-value {
        font-size: 17px;
        font-weight: 700;
        color: #152b55;
        word-break: break-word;
    }
    .payment-items-wrap {
        border: 1px solid #e7edf9;
        border-radius: 20px;
        overflow: hidden;
        background: #fff;
    }
    .payment-items-table {
        width: 100%;
        margin: 0;
    }
    .payment-items-table thead th {
        background: #f2f6ff;
        color: #556b95;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border: 0;
        padding: 16px 18px;
    }
    .payment-items-table tbody td {
        padding: 16px 18px;
        border-color: #edf2fb;
        color: #20365f;
        vertical-align: middle;
    }
    .payment-items-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .payment-summary-box {
        margin-top: 24px;
        margin-left: auto;
        width: min(100%, 380px);
        border: 1px solid #e7edf9;
        border-radius: 20px;
        background: #fff;
        padding: 22px 24px;
    }
    .payment-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 12px;
        color: #4c638f;
    }
    .payment-summary-row strong {
        color: #152b55;
    }
    .payment-summary-row.total {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #edf2fb;
        font-size: 18px;
    }
    .payment-summary-row.balance strong {
        color: #dc3545;
    }
    .payment-note {
        margin-top: 24px;
        border: 1px dashed #cfdaf2;
        border-radius: 18px;
        padding: 18px 20px;
        background: #fbfdff;
        color: #4d6389;
    }
    @media print {
        .page-wrapper, .content, .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
        }
        .payment-receipt-card {
            box-shadow: none !important;
            border: none !important;
        }
        .d-print-none {
            display: none !important;
        }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="payment-receipt-shell">
            <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                <h4 class="mb-0">Payment Receipt</h4>
                <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
            </div>

            <div class="payment-receipt-card">
                <div class="payment-receipt-top">
                    <div class="row align-items-start">
                        <div class="col-lg-7">
                            <div class="payment-receipt-kicker">Official Receipt</div>
                            <div class="payment-receipt-brand">{{ $brandName }}</div>
                            <div>{{ $brandAddress }}</div>
                            <div>{{ $brandPhone }}{{ $brandEmail ? '  |  ' . $brandEmail : '' }}</div>
                        </div>
                        <div class="col-lg-5 mt-3 mt-lg-0 text-lg-end">
                            <div class="payment-receipt-kicker">Receipt Number</div>
                            <div class="payment-receipt-brand" style="font-size: 24px;">
                                {{ $payment->receipt_no ?? $payment->payment_id ?? ('PAY-' . $payment->id) }}
                            </div>
                        </div>
                    </div>

                    <div class="payment-receipt-meta">
                        <div class="payment-receipt-pill">
                            <div class="payment-receipt-pill-label">Payment Date</div>
                            <div class="payment-receipt-pill-value">{{ optional($payment->created_at)->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="payment-receipt-pill">
                            <div class="payment-receipt-pill-label">Amount Received</div>
                            <div class="payment-receipt-pill-value">₦{{ number_format((float) $payment->amount, 2) }}</div>
                        </div>
                        <div class="payment-receipt-pill">
                            <div class="payment-receipt-pill-label">Method</div>
                            <div class="payment-receipt-pill-value">{{ $payment->method ?? 'N/A' }}</div>
                        </div>
                        <div class="payment-receipt-pill">
                            <div class="payment-receipt-pill-label">Status</div>
                            <div class="payment-receipt-pill-value">{{ $payment->status ?? 'Pending' }}</div>
                        </div>
                    </div>
                </div>

                <div class="payment-receipt-body">
                    <div class="payment-section-title">Receipt Details</div>
                    <div class="payment-detail-grid">
                        <div class="payment-detail-card">
                            <div class="payment-detail-label">Customer</div>
                            <div class="payment-detail-value">{{ $customerName }}</div>
                        </div>
                        <div class="payment-detail-card">
                            <div class="payment-detail-label">Invoice / Sale Ref</div>
                            <div class="payment-detail-value">{{ $sale?->invoice_no ?? 'Direct Entry' }}</div>
                        </div>
                        <div class="payment-detail-card">
                            <div class="payment-detail-label">Recorded By</div>
                            <div class="payment-detail-value">{{ $payment->creator?->name ?? 'System' }}</div>
                        </div>
                        <div class="payment-detail-card">
                            <div class="payment-detail-label">Paid Into</div>
                            <div class="payment-detail-value">{{ $payment->account?->name ?? $payment->reference ?? 'Not specified' }}</div>
                        </div>
                    </div>

                    @if($items->isNotEmpty())
                        <div class="payment-section-title">Items Covered By This Receipt</div>
                        <div class="payment-items-wrap">
                            <div class="table-responsive">
                                <table class="table payment-items-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end">Tax</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            @php
                                                $qty = (float) ($item->qty ?? $item->quantity ?? 0);
                                                $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);
                                                $discount = (float) ($item->discount ?? 0);
                                                $tax = (float) ($item->tax ?? 0);
                                                $lineAmount = (float) ($item->total_price ?? (($qty * $unitPrice) - $discount + $tax));
                                            @endphp
                                            <tr>
                                                <td>{{ $item->product_name ?? $item->name ?? $item->product->name ?? 'Item' }}</td>
                                                <td class="text-center">{{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}</td>
                                                <td class="text-end">₦{{ number_format($unitPrice, 2) }}</td>
                                                <td class="text-end">₦{{ number_format($discount, 2) }}</td>
                                                <td class="text-end">₦{{ number_format($tax, 2) }}</td>
                                                <td class="text-end fw-semibold">₦{{ number_format($lineAmount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="payment-summary-box">
                        <div class="payment-summary-row">
                            <span>Subtotal</span>
                            <strong>₦{{ number_format($saleSubtotal, 2) }}</strong>
                        </div>
                        <div class="payment-summary-row">
                            <span>Discount</span>
                            <strong>₦{{ number_format($saleDiscount, 2) }}</strong>
                        </div>
                        <div class="payment-summary-row">
                            <span>Tax</span>
                            <strong>₦{{ number_format($saleTax, 2) }}</strong>
                        </div>
                        <div class="payment-summary-row total">
                            <span>Total Invoice Value</span>
                            <strong>₦{{ number_format($saleTotal, 2) }}</strong>
                        </div>
                        <div class="payment-summary-row">
                            <span>Total Paid So Far</span>
                            <strong>₦{{ number_format($salePaid, 2) }}</strong>
                        </div>
                        <div class="payment-summary-row balance">
                            <span>Balance Remaining</span>
                            <strong>₦{{ number_format($saleBalance, 2) }}</strong>
                        </div>
                    </div>

                    @if(!empty($payment->note))
                        <div class="payment-note">
                            <strong class="d-block mb-2">Note</strong>
                            {{ $payment->note }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
