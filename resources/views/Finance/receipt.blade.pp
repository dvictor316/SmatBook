<!DOCTYPE html>
<html>
<head>
    <title>Receipt_{{ $payment->payment_id }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .card { border: none !important; }
        }
        .receipt-logo { font-size: 24px; font-weight: bold; color: #0d6efd; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="text-end mb-3 no-print">
                    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Receipt</button>
                    <a href="{{ route('payments.index') }}" class="btn btn-secondary">Back</a>
                </div>

                <div class="card shadow-sm border-0 p-5">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <div class="receipt-logo">YOUR COMPANY</div>
                            <p class="text-muted">123 Business Street<br>City, State, Zip</p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h2 class="text-uppercase text-primary">Receipt</h2>
                            <p class="mb-0"><strong>Receipt No:</strong> {{ $payment->payment_id }}</p>
                            <p><strong>Date:</strong> {{ $payment->created_at->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase fw-bold">Received From:</h6>
                            <p class="mb-1"><strong>{{ $payment->sale->customer->name ?? $payment->customer->customer_name ?? $payment->customer->name ?? 'Walk-in Customer' }}</strong></p>
                            <p class="text-muted">{{ $payment->sale->customer->email ?? $payment->customer->email ?? '' }}</p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h6 class="text-muted text-uppercase fw-bold">Payment Details:</h6>
                            <p class="mb-1"><strong>Method:</strong> {{ $payment->method }}</p>
                            <p class="mb-1"><strong>Reference:</strong> {{ $payment->reference ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <table class="table table-bordered mb-4">
                        <thead class="bg-light text-center">
                            <tr>
                                <th>Description</th>
                                <th width="150">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Payment for Sale Reference: {{ $payment->sale->sale_id ?? 'Direct' }}</td>
                                <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-end">Amount Paid:</th>
                                <th class="text-end text-primary fs-5">${{ number_format($payment->amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mt-5 pt-3 border-top text-center text-muted">
                        <p>Thank you for your business!</p>
                        <small>This is a computer-generated receipt and requires no signature.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
