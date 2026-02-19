<div style="font-family: sans-serif; padding: 20px; border: 1px solid #eee;">
    <h2 style="color: #333;">Daily Executive Summary</h2>
    <p>Here is the financial performance for today, <strong>{{ date('d M Y') }}</strong>:</p>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 10px; border: 1px solid #eee;">Total Inflow (Revenue)</td>
            <td style="padding: 10px; border: 1px solid #eee; color: green; font-weight: bold;">₦{{ number_format($data['inflow'], 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #eee;">Total Outflow (Expenses)</td>
            <td style="padding: 10px; border: 1px solid #eee; color: red;">₦{{ number_format($data['outflow'], 2) }}</td>
        </tr>
        <tr style="background: #f9f9f9;">
            <td style="padding: 10px; border: 1px solid #eee; font-weight: bold;">Net Profit</td>
            <td style="padding: 10px; border: 1px solid #eee; font-weight: bold;">₦{{ number_format($data['profit'], 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #eee;">Net Profit Margin</td>
            <td style="padding: 10px; border: 1px solid #eee; font-weight: bold;">{{ $data['margin'] }}%</td>
        </tr>
    </table>

    <p style="margin-top: 20px; font-size: 12px; color: #777;">Sources: {{ $data['sources'] }}</p>
</div>