<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $subscription->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; margin: 0; padding: 0; line-height: 1.5; }
        .container { width: 100%; padding: 30px; }
        .header-table { width: 100%; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
        .text-right { text-align: right; }
        .text-primary { color: #007bff; }
        .invoice-title { font-size: 28px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .info-table { width: 100%; margin-top: 30px; }
        .info-table td { width: 50%; vertical-align: top; }
        .item-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .item-table th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; padding: 12px; text-align: left; }
        .item-table td { border-bottom: 1px solid #eee; padding: 12px; }
        .totals-table { width: 35%; float: right; margin-top: 30px; border-collapse: collapse; }
        .totals-table td { padding: 10px; border: 1px solid #eee; }
        .bg-light { background-color: #f8f9fa; font-weight: bold; }
        .footer { margin-top: 100px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td>
                    <h1 class="text-primary" style="margin:0;">SmartProbook</h1>
                    <p style="margin:5px 0 0 0;">SaaS Subscription Management</p>
                </td>
                <td class="text-right">
                    <h2 class="invoice-title">RECEIPT</h2>
                    <p>Reference: #{{ $subscription->id }}</p>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td>
                    <h4 style="margin-bottom: 5px;">Billed To:</h4>
                    <strong>{{ $subscription->tenant->name ?? 'Valued Subscriber' }}</strong><br>
                    @php
                        $domainSuffix = ltrim(config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')), '.');
                    @endphp
                    Workspace: {{ $subscription->domain_prefix }}.{{ $domainSuffix }}<br>
                    Payment: {{ strtoupper($subscription->payment_gateway ?? 'Paystack') }}
                </td>
                <td class="text-right">
                    <h4 style="margin-bottom: 5px;">Details:</h4>
                    Date: {{ $subscription->created_at->format('d M Y') }}<br>
                    Status: <strong>{{ strtoupper($subscription->status) }}</strong><br>
                    Currency: NGN (₦)
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Cycle</th>
                    <th>Period</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $subscription->plan_name }} Plan</strong><br>
                        <small>License for workspace access</small>
                    </td>
                    <td>{{ ucfirst($subscription->billing_cycle) }}</td>
                    <td>{{ $subscription->start_date?->format('d/m/y') }} - {{ $subscription->end_date?->format('d/m/y') }}</td>
                    <td class="text-right">₦{{ number_format($subscription->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="bg-light">Subtotal</td>
                <td class="text-right">₦{{ number_format($subscription->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="bg-light">Tax (0%)</td>
                <td class="text-right">₦0.00</td>
            </tr>
            <tr style="background-color: #007bff; color: white;">
                <td><strong>Total Paid</strong></td>
                <td class="text-right"><strong>₦{{ number_format($subscription->amount, 2) }}</strong></td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        <div class="footer">
            <p>Thank you for choosing SmartProbook. This is a computer-generated receipt and requires no signature.</p>
            <p>Support: chat@smartprobook.com | Onitsha, Anambra State, Nigeria</p>
        </div>
    </div>
</body>
</html>
