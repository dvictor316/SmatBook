<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Damage Report</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9px; }
        h1 { color: #102a5a; font-size: 18px; margin: 0 0 4px; }
        h2 { color: #102a5a; font-size: 12px; margin: 18px 0 6px; }
        .meta { color: #64748b; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eaf1fb; color: #102a5a; font-size: 8px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #dbe3ef; padding: 5px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .empty { color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <h1>Stock Damage Register</h1>
    <div class="meta">
        Branch: {{ $activeBranch['name'] ?? 'All Branches' }}
        @if($fromDate || $toDate) | Period: {{ $fromDate ?: 'Beginning' }} to {{ $toDate ?: 'Present' }} @endif
        @if($search) | Search: {{ $search }} @endif
    </div>

    <h2>Damage History ({{ $damages->count() }} entries)</h2>
    <table>
        <thead><tr><th>Date</th><th>Product</th><th>SKU</th><th>Branch</th><th>Reason</th><th>Notes</th><th class="number">Qty</th><th class="number">Cost Value</th></tr></thead>
        <tbody>
            @forelse($damages as $damage)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($damage->created_at)->format('d M Y, H:i') }}</td>
                    <td>{{ $damage->product_name }}</td>
                    <td>{{ $damage->sku ?: 'N/A' }}</td>
                    <td>{{ $damage->branch_name ?: ($activeBranch['name'] ?? '') }}</td>
                    <td>{{ $damage->reason }}</td>
                    <td>{{ $damage->remarks ?: '-' }}</td>
                    <td class="number">{{ number_format((float) $damage->quantity, 2) }}</td>
                    <td class="number">{{ number_format((float) $damage->line_value, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No damaged stock has been recorded for the selected criteria.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Stock Status After Damages ({{ $stockStatusRows->count() }} products)</h2>
    <table>
        <thead><tr><th>Product</th><th>SKU</th><th class="number">Stock Before Damage</th><th class="number">Damaged Qty</th><th class="number">Balance In Store</th><th class="number">Damage Value</th></tr></thead>
        <tbody>
            @forelse($stockStatusRows as $row)
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->sku ?: 'N/A' }}</td>
                    <td class="number">{{ number_format((float) $row->stock_before_damage, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->damaged_qty, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->current_stock, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->damage_value, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No inventory stock status is available for the selected criteria.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
