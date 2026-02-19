<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Executive Summary - smat-book</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f9fc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <div style="max-width: 650px; margin: 20px auto; padding: 30px; border: 1px solid #e2e8f0; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        
        <div style="text-align: center; border-bottom: 3px solid #7F9CF5; padding-bottom: 15px; margin-bottom: 25px;">
            <h2 style="color: #2d3748; margin: 0; text-transform: uppercase; letter-spacing: 1px;">Executive Summary</h2>
            <p style="color: #718096; margin: 5px 0;">Date: <strong>{{ date('d M Y') }}</strong></p>
        </div>

        @if(count($data['unusualLogins']) > 0)
        <div style="background-color: #fff5f5; border-left: 5px solid #f56565; padding: 15px; margin-bottom: 25px;">
            <strong style="color: #c53030; display: block;">⚠️ SECURITY ALERT</strong>
            <span style="color: #742a2a; font-size: 14px;">{{ count($data['unusualLogins']) }} unusual admin logins detected outside business hours.</span>
        </div>
        @endif

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
            <tr style="background-color: #f8fafc;">
                <td style="padding: 12px; border: 1px solid #edf2f7; color: #4a5568;">Net Profit Today</td>
                <td style="padding: 12px; border: 1px solid #edf2f7; text-align: right; font-weight: bold; font-size: 18px; color: #2d3748;">
                    &#8358;{{ number_format($data['profit'], 2) }}
                </td>
            </tr>
            <tr>
                <td style="padding: 12px; border: 1px solid #edf2f7; color: #4a5568;">Performance vs Monthly Avg</td>
                <td style="padding: 12px; border: 1px solid #edf2f7; text-align: right; font-weight: bold; color: {{ $data['performance'] == 'Above Average' ? '#38a169' : '#dd6b20' }};">
                    {{ $data['performance'] }}
                </td>
            </tr>
        </table>

        <h3 style="color: #dd6b20; font-size: 16px; border-left: 4px solid #ed8936; padding-left: 10px; margin-bottom: 10px;">📦 Inventory Alerts</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 25px;">
            <tr style="background: #fffaf0; color: #9c4221;">
                <th style="padding: 10px; border: 1px solid #fbd38d; text-align: left;">Product Name</th>
                <th style="padding: 10px; border: 1px solid #fbd38d; text-align: right;">Stock Left</th>
            </tr>
            @forelse($data['lowStockItems'] as $item)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $item->name }}</td>
                <td style="padding: 10px; border: 1px solid #eee; text-align: right; color: #e53e3e; font-weight: bold;">
                    {{ $item->stock }}
                </td>
            </tr>
            @empty
            <tr><td colspan="2" style="padding: 15px; text-align: center; color: #a0aec0; border: 1px solid #eee;">Stock levels are currently optimal.</td></tr>
            @endforelse
        </table>

        <h3 style="color: #c53030; font-size: 16px; border-left: 4px solid #f56565; padding-left: 10px; margin-bottom: 10px;">📉 Expense Breakdown</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 25px;">
            <tr style="background: #fff5f5; color: #c53030;">
                <th style="padding: 10px; border: 1px solid #feb2b2; text-align: left;">Category</th>
                <th style="padding: 10px; border: 1px solid #feb2b2; text-align: right;">Amount</th>
            </tr>
            @forelse($data['expenseBreakdown'] as $exp)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $exp->category }}</td>
                <td style="padding: 10px; border: 1px solid #eee; text-align: right; font-weight: bold;">&#8358;{{ number_format($exp->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="padding: 15px; text-align: center; color: #a0aec0; border: 1px solid #eee;">No expenses recorded today.</td></tr>
            @endforelse
        </table>

        <h3 style="color: #2b6cb0; font-size: 16px; border-left: 4px solid #4299e1; padding-left: 10px; margin-bottom: 10px;">💰 Top 5 Sales</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <tr style="background: #ebf8ff; color: #2b6cb0;">
                <th style="padding: 10px; border: 1px solid #bee3f8; text-align: left;">Invoice / Ref</th>
                <th style="padding: 10px; border: 1px solid #bee3f8; text-align: right;">Total Value</th>
            </tr>
            @foreach($data['topSales'] as $sale)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $sale->invoice_no ?? 'ID: '.$sale->id }}</td>
                <td style="padding: 10px; border: 1px solid #eee; text-align: right; font-weight: bold;">&#8358;{{ number_format($sale->total, 2) }}</td>
            </tr>
            @endforeach
        </table>

        <div style="margin-top: 30px; font-size: 11px; color: #a0aec0; text-align: center; border-top: 1px solid #edf2f7; padding-top: 20px;">
            Generated by smat-book System | {{ date('Y-m-d H:i') }}<br>
            Monthly Avg Daily Profit: &#8358;{{ number_format($data['avgDailyProfit'], 2) }}
        </div>
    </div>

    <script type="text/javascript">
        window.onload = function() {
            // Script for printing pages in smat-book
            // window.print();
        };
    </script>
</body>
</html>