<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $sale->receipt_no ?? $sale->invoice_no ?? $sale->id }}</title>
    <style>
        :root { --paper-width: {{ $paperWidth }}mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #edf0f4; color: #000; }
        body { font-family: "Courier New", Courier, monospace; font-size: {{ $paperWidth === 58 ? '10px' : '11px' }}; line-height: 1.25; }
        .receipt-toolbar {
            width: min(100%, 720px); margin: 16px auto; padding: 10px; display: flex;
            flex-wrap: wrap; justify-content: center; gap: 8px; font-family: Arial, sans-serif;
        }
        .receipt-toolbar a, .receipt-toolbar button {
            border: 1px solid #164e9b; background: #fff; color: #123d77; padding: 8px 12px;
            border-radius: 5px; font-weight: 700; cursor: pointer; text-decoration: none;
        }
        .receipt-toolbar .primary { background: #164e9b; color: #fff; }
        .thermal-receipt {
            width: var(--paper-width); min-height: 80mm; margin: 0 auto 24px; padding: {{ $paperWidth === 58 ? '3mm 2mm 7mm' : '4mm 3mm 8mm' }};
            background: #fff; box-shadow: 0 4px 24px rgba(15, 23, 42, .16); overflow: hidden;
        }
        .center { text-align: center; }
        .brand { font-family: Arial, sans-serif; font-size: {{ $paperWidth === 58 ? '14px' : '17px' }}; font-weight: 800; line-height: 1.15; overflow-wrap: anywhere; }
        .branch { margin-top: 2px; font-weight: 700; text-transform: uppercase; }
        .contact { margin-top: 3px; font-size: .92em; overflow-wrap: anywhere; }
        .receipt-title { margin-top: 8px; font-weight: 800; font-size: 1.15em; }
        .rule { border-top: 1px dashed #000; margin: 7px 0; }
        .double-rule { border-top: 2px solid #000; margin: 7px 0; }
        .meta-row, .total-row, .payment-row { display: flex; justify-content: space-between; gap: 7px; margin: 2px 0; }
        .meta-row span:first-child, .total-row span:first-child, .payment-row span:first-child { flex: 0 0 auto; }
        .meta-row span:last-child, .total-row span:last-child, .payment-row span:last-child { min-width: 0; text-align: right; overflow-wrap: anywhere; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items th { padding: 2px 1px 4px; border-bottom: 1px solid #000; text-align: right; font-size: .92em; }
        .items th:first-child { width: {{ $paperWidth === 58 ? '40%' : '44%' }}; text-align: left; }
        .items th:nth-child(2) { width: 18%; }
        .items th:nth-child(3) { width: 19%; }
        .items th:nth-child(4) { width: {{ $paperWidth === 58 ? '23%' : '19%' }}; }
        .items td { padding: 5px 1px 2px; text-align: right; vertical-align: top; overflow-wrap: anywhere; }
        .items td:first-child { text-align: left; font-weight: 700; }
        .item-code td { padding-top: 0; padding-bottom: 3px; font-size: .86em; font-weight: 400 !important; }
        .strong { font-weight: 800; }
        .grand-total { font-family: Arial, sans-serif; font-size: 1.18em; font-weight: 900; padding: 3px 0; }
        .footer { margin-top: 9px; text-align: center; font-family: Arial, sans-serif; }
        .receipt-reference { margin-top: 7px; font-family: "Courier New", monospace; font-weight: 700; overflow-wrap: anywhere; }
        @page { size: {{ $paperWidth }}mm auto; margin: 0; }
        @media print {
            html, body { width: var(--paper-width); background: #fff !important; }
            .receipt-toolbar { display: none !important; }
            .thermal-receipt { width: var(--paper-width); margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
@php
    $brandName = $company?->company_name ?: $company?->name ?: config('app.name', 'SmartProBook');
    $brandAddress = $company?->address ?: \App\Models\Setting::where('key', 'company_address')->value('value');
    $brandPhone = $company?->phone ?: \App\Models\Setting::where('key', 'company_phone')->value('value');
    $branchName = $sale->branch_label ?: 'Main Branch';
    $customerName = $sale->display_customer_name ?: 'Walk-in Customer';
    $customerPhone = $sale->customer?->phone;
    $cashierName = $sale->cashier_name ?? $sale->user?->name ?? 'System';
    $currencySymbol = $currencySymbol ?? \App\Support\GeoCurrency::currentSymbol();
    $subtotal = (float) ($sale->subtotal ?? $sale->items->sum('subtotal'));
    $discount = (float) ($sale->discount ?? 0);
    $tax = (float) ($sale->tax ?? 0);
    $total = (float) ($sale->total ?? 0);
    $paid = (float) ($sale->effective_paid ?? $sale->amount_paid ?? $sale->paid ?? 0);
    $change = max(0, (float) ($sale->change_amount ?? 0));
    $tendered = $paid + $change;
    $balance = (float) ($sale->effective_balance ?? max(0, $total - $paid));
    $paymentDetails = is_array($sale->payment_details) ? $sale->payment_details : [];
    $split = is_array($paymentDetails['split'] ?? null) ? $paymentDetails['split'] : [];
    $receiptFooter = \App\Models\Setting::where('key', 'receipt_footer')->value('value') ?: 'Thank you for your patronage.';
    $money = fn ($amount) => $currencySymbol . number_format((float) $amount, 2);
@endphp

<div class="receipt-toolbar">
    <button type="button" class="primary" onclick="printThermalReceipt()">Print {{ $paperWidth }}mm</button>
    <a href="{{ route('sales.invoice.print', $sale->id) }}?format=thermal&amp;paper=58">58mm</a>
    <a href="{{ route('sales.invoice.print', $sale->id) }}?format=thermal&amp;paper=80">80mm</a>
    <a href="{{ route('sales.invoice.print', $sale->id) }}">Standard</a>
    <button type="button" onclick="window.close()">Close</button>
</div>

<main class="thermal-receipt">
    <header class="center">
        <div class="brand">{{ $brandName }}</div>
        <div class="branch">{{ $branchName }}</div>
        @if($brandAddress)<div class="contact">{{ $brandAddress }}</div>@endif
        @if($brandPhone)<div class="contact">Tel: {{ $brandPhone }}</div>@endif
        <div class="receipt-title">SALES RECEIPT</div>
    </header>

    <div class="rule"></div>
    <div class="meta-row"><span>Receipt:</span><span>{{ $sale->receipt_no ?? $sale->invoice_no ?? $sale->id }}</span></div>
    <div class="meta-row"><span>Invoice:</span><span>{{ $sale->invoice_no ?? $sale->id }}</span></div>
    <div class="meta-row"><span>Date:</span><span>{{ optional($sale->created_at)->format('d/m/Y h:i A') }}</span></div>
    <div class="meta-row"><span>Customer:</span><span>{{ $customerName }}</span></div>
    @if($customerPhone)<div class="meta-row"><span>Mobile:</span><span>{{ $customerPhone }}</span></div>@endif
    <div class="meta-row"><span>Cashier:</span><span>{{ $cashierName }}</span></div>
    <div class="rule"></div>

    <table class="items">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Price</th><th>Amount</th></tr>
        </thead>
        <tbody>
        @forelse($sale->items as $item)
            @php
                $quantity = (float) ($item->qty ?? 0);
                $quantityLabel = rtrim(rtrim(number_format($quantity, 3), '0'), '.');
                $unitType = strtolower(trim((string) ($item->unit_type ?? 'unit')));
                $unitLabel = match ($unitType) {
                    'carton' => 'ctn', 'kilogram', 'kilograms' => 'kg', 'piece', 'pieces', 'unit' => 'pcs',
                    default => $unitType !== '' ? $unitType : 'pcs',
                };
                $productName = $item->product?->name ?? $item->product_name ?? 'Item';
                $productCode = $item->product?->sku ?? null;
                $lineTotal = (float) ($item->total_price ?? 0);
            @endphp
            <tr>
                <td>{{ $productName }}</td>
                <td>{{ $quantityLabel }} {{ $unitLabel }}</td>
                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                <td>{{ number_format($lineTotal, 2) }}</td>
            </tr>
            @if($productCode)
                <tr class="item-code"><td colspan="4">SKU: {{ $productCode }}</td></tr>
            @endif
        @empty
            <tr><td colspan="4" class="center">No items</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="double-rule"></div>
    <div class="total-row"><span>Subtotal</span><span>{{ $money($subtotal) }}</span></div>
    @if($discount > 0)<div class="total-row"><span>Discount</span><span>-{{ $money($discount) }}</span></div>@endif
    @if($tax > 0)<div class="total-row"><span>Tax</span><span>{{ $money($tax) }}</span></div>@endif
    <div class="total-row grand-total"><span>TOTAL</span><span>{{ $money($total) }}</span></div>
    @if($tendered > $paid)<div class="total-row"><span>Tendered</span><span>{{ $money($tendered) }}</span></div>@endif
    <div class="total-row strong"><span>Paid</span><span>{{ $money($paid) }}</span></div>
    @if($balance > 0)<div class="total-row strong"><span>Balance</span><span>{{ $money($balance) }}</span></div>@endif
    @if($change > 0)<div class="total-row strong"><span>Change</span><span>{{ $money($change) }}</span></div>@endif

    <div class="rule"></div>
    <div class="payment-row"><span>Payment:</span><span>{{ strtoupper(str_replace('_', ' ', (string) ($sale->payment_method ?: 'cash'))) }}</span></div>
    @foreach(['cash' => 'Cash', 'transfer' => 'Transfer', 'card' => 'POS/Card'] as $key => $label)
        @if((float) ($split[$key] ?? 0) > 0)
            <div class="payment-row"><span>{{ $label }}</span><span>{{ $money($split[$key]) }}</span></div>
        @endif
    @endforeach

    <footer class="footer">
        <div class="rule"></div>
        <div class="strong">{{ $receiptFooter }}</div>
        <div class="receipt-reference">{{ $sale->receipt_no ?? $sale->invoice_no ?? $sale->id }}</div>
        <div style="margin-top: 5px; font-size: .85em;">Powered by SmartProBook</div>
    </footer>
</main>

<script>
    let thermalPrintLocked = false;
    function printThermalReceipt() {
        if (thermalPrintLocked) return;
        thermalPrintLocked = true;
        window.focus();
        window.setTimeout(() => window.print(), 120);
        window.setTimeout(() => { thermalPrintLocked = false; }, 2500);
    }
    window.addEventListener('afterprint', () => { thermalPrintLocked = false; });
    if (@json(request()->boolean('autoprint'))) {
        window.addEventListener('load', () => window.setTimeout(printThermalReceipt, 300), { once: true });
    }
</script>
</body>
</html>
