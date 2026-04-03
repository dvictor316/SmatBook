<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt_{{ $payment->payment_id }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .receipt-card { background: #fff; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .receipt-logo { font-size: 28px; font-weight: 800; color: #1a1a1a; letter-spacing: -1px; }
        .company-details { font-size: 13px; color: #6c757d; line-height: 1.6; }
        .table thead th { background-color: #fcfcfc; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .receipt-header { border-bottom: 2px solid #f8f9fa; padding-bottom: 20px; }
        
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff !important; padding: 0; }
            .receipt-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .container { max-width: 100% !important; width: 100% !important; }
        }
    </style>
</head>
<body>
    @php
        $receiptCompany = auth()->user()?->company;
        $receiptBrandName = $receiptCompany?->company_name
            ?? $receiptCompany?->name
            ?? \App\Models\Setting::where('key', 'company_name')->value('value')
            ?? 'SmartProbook';
        $receiptAddress = $receiptCompany?->address
            ?? \App\Models\Setting::where('key', 'company_address')->value('value')
            ?? config('app.address');
        $receiptEmail = $receiptCompany?->email
            ?? \App\Models\Setting::where('key', 'company_email')->value('value')
            ?? config('app.email');
        $receiptPhone = $receiptCompany?->phone
            ?? \App\Models\Setting::where('key', 'company_phone')->value('value')
            ?? config('app.phone');
    @endphp
    <div class="container py-5">
        <div class="d-flex justify-content-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary me-2 px-4">
                <i class="fas fa-print me-2"></i> Print Receipt
            </button>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary px-4">Back to List</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="receipt-card p-5">
                    
                    <div class="row receipt-header mb-5 align-items-top">
                        <div class="col-sm-6">
                            <div class="receipt-logo mb-2">{{ $receiptBrandName }}</div>
                            <div class="company-details">
                                <p class="mb-0">{{ $receiptAddress }}</p>
                                <p class="mb-0">{{ config('app.city') }}</p>
                                <p class="mb-0">Phone: {{ $receiptPhone }}</p>
                                <p class="mb-0">Email: {{ $receiptEmail }}</p>
                            </div>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-4 mt-sm-0">
                            <h1 class="text-uppercase fw-bold text-primary mb-1">Receipt</h1>
                            <p class="text-muted mb-3">Official Acknowledgment of Payment</p>
                            <div class="bg-light p-3 d-inline-block rounded shadow-sm text-start">
                                <p class="mb-1 small"><strong>Number:</strong> {{ $payment->payment_id }}</p>
                                <p class="mb-0 small"><strong>Date:</strong> {{ $payment->created_at->format('d M, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Received From:</h6>
                            <h5 class="mb-1">{{ $payment->sale->customer->name ?? $payment->customer->customer_name ?? $payment->customer->name ?? 'Walk-in Customer' }}</h5>
                            <p class="text-muted small mb-0">{{ $payment->sale->customer->email ?? $payment->customer->email ?? '' }}</p>
                            <p class="text-muted small">{{ $payment->sale->customer->phone ?? $payment->customer->phone ?? '' }}</p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Payment Method:</h6>
                            <p class="mb-1 text-dark fw-semibold">{{ $payment->method }}</p>
                            @if($payment->reference)
                                <p class="small text-muted mb-0">Ref: {{ $payment->reference }}</p>
                            @endif
                            <p class="small text-muted">Posted to: {{ $payment->account->name ?? 'General Ledger' }}</p>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="ps-3">Description</th>
                                    <th class="text-end pe-3" width="200">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="height: 100px;">
                                    <td class="ps-3 align-middle">
                                        <div class="fw-bold text-dark">Payment for Invoice #{{ $payment->sale->sale_id ?? 'Direct' }}</div>
                                        @if($payment->note)
                                            <div class="small text-muted mt-1">{{ $payment->note }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 align-middle fs-5">
                                        {{ number_format($payment->amount, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="text-end fw-bold bg-light">TOTAL PAID</td>
                                    <td class="text-end fw-bold bg-primary text-white pe-3 fs-4">
                                        ${{ number_format($payment->amount, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-5 pt-4 border-top">
                        <div class="row">
                            <div class="col-sm-8">
                                <p class="mb-0 fw-bold">Thank you for your business!</p>
                                <p class="text-muted small">This is an automated receipt generated by {{ $receiptBrandName }}.</p>
                            </div>
                            <div class="col-sm-4 text-center">
                                <div class="mt-3 border-bottom mx-auto" style="width: 150px;"></div>
                                <small class="text-muted">Authorized Signature</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
