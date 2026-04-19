<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $sale->invoice_no ?? $sale->id }}</title>
    <link rel="stylesheet" href="{{ URL::asset('/assets/css/bootstrap.min.css') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --light-gold: #fdfaf0;
            --border-gold: #e6d5a7;
            --text-gold: #b39b5d;
            --soft-blue: #f0f9ff;
            --deep-blue: #0369a1;
            --success-green: #10b981;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: #fcfcfc;
            margin: 0;
            padding: 20px;
            color: var(--gray-700);
        }

        .compact-invoice-shell {
            max-width: 780px;
            margin: 0 auto;
        }

        .compact-invoice-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 26px !important;
        }

        .compact-invoice-brand {
            font-size: 20px;
            font-weight: 800;
            color: var(--deep-blue);
            line-height: 1.15;
            margin-bottom: 4px;
        }

        .compact-status {
            display: inline-block;
            padding: 4px 12px;
            background: var(--soft-blue);
            color: var(--deep-blue);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .compact-invoice-heading {
            font-size: 22px;
            font-weight: 300;
            letter-spacing: 2px;
            color: var(--text-gold);
            margin-top: 8px;
            line-height: 1;
        }

        .compact-invoice-number {
            font-size: 12px;
            font-weight: 700;
            color: var(--gray-900);
            margin-top: 6px;
        }

        .compact-panel {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fff;
            height: 100%;
        }

        .compact-panel-muted {
            background: var(--gray-50);
        }

        .compact-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(260px, 0.95fr);
            gap: 14px;
            align-items: stretch;
        }

        .compact-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .compact-split-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .compact-split-pill {
            border: 1px solid #dbe4f0;
            border-radius: 8px;
            padding: 8px 10px;
            background: #fff;
        }

        .compact-invoice-meta-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-600);
            margin-bottom: 6px;
        }

        .compact-invoice-meta-value {
            font-size: 15px;
            font-weight: 700;
            color: var(--deep-blue);
            line-height: 1.25;
        }

        .compact-summary-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--gray-900);
        }

        .compact-summary-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
        }

        .compact-items-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            font-size: 12px;
        }

        .compact-items-table thead th {
            background: linear-gradient(135deg, var(--soft-blue) 0%, #e0f2fe 100%);
            color: var(--deep-blue);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 2px solid #0ea5e9;
            padding: 9px 8px;
            font-weight: 800;
        }

        .compact-items-table tbody td {
            padding: 8px 8px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: top;
            font-size: 12px;
        }

        .compact-item-name {
            font-size: 12px;
            line-height: 1.2;
            font-weight: 600;
            color: var(--gray-900);
        }

        .compact-item-meta {
            font-size: 10px;
            line-height: 1.2;
            color: var(--gray-600);
        }

        .compact-contact-line {
            font-size: 11px;
            color: var(--gray-600);
            line-height: 1.35;
        }

        .compact-extra-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(220px, 0.8fr);
            gap: 14px;
            margin-top: 14px;
            align-items: stretch;
        }

        .compact-words-box {
            border-top: 1px dashed var(--border-gold);
            padding-top: 10px;
        }

        .compact-words-value {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-gold);
            line-height: 1.45;
            font-style: italic;
        }

        .compact-totals-panel {
            background: linear-gradient(135deg, var(--light-gold) 0%, #fef9e7 100%);
            border: 2px solid var(--border-gold);
        }

        .compact-total-table td {
            padding: 6px 0;
            font-size: 12px;
            border-bottom: 1px dashed var(--border-gold);
        }

        .compact-total-table tr:last-child td {
            border-bottom: 0;
        }

        .compact-total-table .total-row td {
            padding-top: 10px;
            border-top: 2px solid var(--text-gold);
            border-bottom: 2px solid var(--text-gold);
            font-size: 16px;
            font-weight: 800;
            color: var(--deep-blue);
        }

        .compact-note {
            border-top: 2px dashed var(--border-gold);
            padding-top: 12px;
            color: var(--text-gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-align: center;
        }

        .compact-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .compact-controls .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 10px 20px;
        }

        @media print {
            @page {
                size: portrait;
                margin: 8mm;
            }

            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .compact-invoice-card {
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
            }

            .compact-invoice-shell {
                max-width: none !important;
            }

            .compact-controls {
                display: none !important;
            }

            body {
                padding: 0 !important;
            }

            .compact-invoice-brand {
                font-size: 17px !important;
            }

            .compact-invoice-heading {
                font-size: 18px !important;
            }

            .compact-panel {
                padding: 9px 10px !important;
            }

            .compact-main-grid {
                grid-template-columns: minmax(0, 1.55fr) minmax(220px, 0.9fr) !important;
                gap: 10px !important;
            }

            .compact-extra-grid {
                grid-template-columns: minmax(0, 1.1fr) minmax(180px, 0.9fr) !important;
                gap: 10px !important;
                margin-top: 10px !important;
            }

            .compact-info-grid {
                gap: 8px !important;
                margin-top: 8px !important;
            }

            .compact-split-grid {
                gap: 6px !important;
                margin-top: 8px !important;
            }

            .compact-items-table thead th {
                padding: 8px 7px !important;
                font-size: 9px !important;
            }

            .compact-items-table tbody td {
                padding: 7px 7px !important;
                font-size: 11px !important;
            }

            .compact-total-table td {
                padding: 6px 0 !important;
                font-size: 12px !important;
            }

            .compact-total-table .total-row td {
                font-size: 15px !important;
            }

            .compact-summary-title {
                font-size: 16px !important;
            }
        }

        @media (max-width: 767.98px) {
            .compact-main-grid,
            .compact-info-grid,
            .compact-split-grid,
            .compact-extra-grid {
                grid-template-columns: 1fr;
            }

            .compact-invoice-card {
                border-radius: 6px;
                padding: 18px !important;
            }

            .compact-invoice-heading {
                font-size: 18px;
                letter-spacing: 1.5px;
            }
        }
    </style>
</head>
<body>
    @php
        $invoiceCompany = \App\Models\Company::find($sale->company_id) ?? optional(auth()->user())->company;
        $brandName = $invoiceCompany?->company_name
            ?: $invoiceCompany?->name
            ?: \App\Models\Setting::where('key', 'company_name')->value('value')
            ?: config('app.name', 'SmartProbook');
        $brandAddress = $invoiceCompany?->address
            ?: \App\Models\Setting::where('key', 'company_address')->value('value')
            ?: 'No address provided';
        $brandPhone = $invoiceCompany?->phone
            ?: \App\Models\Setting::where('key', 'company_phone')->value('value')
            ?: '';
        $brandEmail = $invoiceCompany?->email
            ?: \App\Models\Setting::where('key', 'company_email')->value('value')
            ?: '';
        $displayStatus = strtolower((string) ($sale->effective_payment_status ?? $sale->payment_status ?? 'unpaid'));
        $appliedAmount = (float) ($sale->effective_paid ?? $sale->amount_paid ?? $sale->paid ?? 0);
        $changeAmount = (float) ($sale->change_amount ?? 0);
        $tenderedAmount = $appliedAmount + max(0, $changeAmount);
        $balanceDue = (float) ($sale->effective_balance ?? max(0, (float) ($sale->total ?? 0) - $appliedAmount));
        $cashierName = $sale->cashier_name ?? $sale->user?->name ?? 'System';
        $amountInWords = $sale->amount_in_words_display ?? 'Zero Naira Only';
        $subtotal = (float) ($sale->items->sum('subtotal'));
        $tax = (float) ($sale->tax ?? 0);
        $discount = (float) ($sale->discount ?? 0);
        $paymentDetails = $sale->payment_details;
        if (is_string($paymentDetails)) {
            $paymentDetails = json_decode($paymentDetails, true);
        }
        $paymentDetails = is_array($paymentDetails) ? $paymentDetails : [];
        $splitDetails = is_array($paymentDetails['split'] ?? null) ? $paymentDetails['split'] : [];
        $splitLines = [];

        foreach ([
            ['key' => 'cash', 'label' => 'Cash', 'account' => null],
            ['key' => 'transfer', 'label' => 'Transfer', 'account' => $paymentDetails['transfer_account_name'] ?? null],
            ['key' => 'card', 'label' => 'POS', 'account' => $paymentDetails['card_account_name'] ?? null],
        ] as $line) {
            $amount = (float) ($splitDetails[$line['key']] ?? 0);
            if ($amount > 0) {
                $splitLines[] = [
                    'label' => $line['label'],
                    'amount' => $amount,
                    'account' => $line['account'],
                ];
            }
        }
    @endphp

    <div class="container py-4 py-lg-5">
        <div class="compact-invoice-shell">
            <div class="compact-controls no-print">
                <a href="{{ $backUrl ?? url()->previous() }}" class="btn btn-outline-secondary">Back</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            </div>

            <div class="compact-invoice-card p-3 p-lg-4">
                <div class="row align-items-start gy-3">
                    <div class="col-md-7">
                        <div class="compact-invoice-brand">{{ $brandName }}</div>
                        <div class="compact-contact-line">{{ $brandAddress }}</div>
                        @if($brandPhone || $brandEmail)
                            <div class="compact-contact-line mt-1">
                                @if($brandPhone)
                                    <strong>Phone:</strong> {{ $brandPhone }}
                                @endif
                                @if($brandPhone && $brandEmail)
                                    <span> | </span>
                                @endif
                                @if($brandEmail)
                                    <strong>Email:</strong> {{ $brandEmail }}
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="compact-status {{ $displayStatus === 'paid' ? 'text-success' : ($displayStatus === 'partial' ? 'text-info' : 'text-danger') }}">
                            {{ strtoupper($displayStatus) }}
                        </div>
                        <div class="compact-invoice-heading">INVOICE</div>
                        <div class="compact-invoice-number">#{{ $sale->invoice_no ?? $sale->id }}</div>
                    </div>
                </div>

                <div class="compact-main-grid mt-3">
                    <div class="compact-panel compact-panel-muted">
                        <div class="compact-invoice-meta-label">Invoice Details</div>

                        <div class="compact-info-grid">
                            <div class="compact-panel">
                                <div class="compact-invoice-meta-label">Issue Date</div>
                                <div class="compact-invoice-meta-value">{{ optional($sale->created_at)->format('d M Y') }}</div>
                            </div>

                            <div class="compact-panel">
                                <div class="compact-invoice-meta-label">Reference</div>
                                <div class="compact-invoice-meta-value">{{ $sale->order_number ?? 'N/A' }}</div>
                            </div>

                            <div class="compact-panel">
                                <div class="compact-invoice-meta-label">Customer</div>
                                <div class="compact-invoice-meta-value">{{ $sale->display_customer_name }}</div>
                            </div>
                        </div>

                        @if(!empty($splitLines))
                            <div class="compact-invoice-meta-label mt-3">Payment Breakdown</div>
                            <div class="compact-split-grid">
                                @foreach($splitLines as $line)
                                    <div class="compact-split-pill">
                                        <div class="compact-invoice-meta-label">{{ $line['label'] }}</div>
                                        <div class="compact-item-name">₦{{ number_format($line['amount'], 2) }}</div>
                                        @if($line['account'])
                                            <div class="compact-item-meta mt-1">{{ $line['account'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="compact-panel compact-totals-panel">
                        <div class="compact-summary-top">
                            <div>
                                <div class="compact-invoice-meta-label">Summary</div>
                                <div class="compact-summary-title">₦{{ number_format((float) ($sale->total ?? 0), 2) }}</div>
                            </div>
                            <div class="compact-status {{ $displayStatus === 'paid' ? 'text-success' : ($displayStatus === 'partial' ? 'text-info' : 'text-danger') }}">
                                {{ strtoupper($displayStatus) }}
                            </div>
                        </div>

                        <table class="w-100 compact-total-table">
                            <tr>
                                <td class="text-muted">Subtotal</td>
                                <td class="text-end fw-bold">₦{{ number_format($subtotal, 2) }}</td>
                            </tr>
                            @if(abs($discount) > 0.00001)
                                <tr>
                                    <td class="text-muted">Discount</td>
                                    <td class="text-end fw-bold">₦{{ number_format($discount, 2) }}</td>
                                </tr>
                            @endif
                            @if(abs($tax) > 0.00001)
                                <tr>
                                    <td class="text-muted">Tax</td>
                                    <td class="text-end fw-bold">₦{{ number_format($tax, 2) }}</td>
                                </tr>
                            @endif
                            @if(abs($tenderedAmount - $appliedAmount) > 0.00001)
                                <tr>
                                    <td class="text-muted">Tendered</td>
                                    <td class="text-end fw-bold">₦{{ number_format($tenderedAmount, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Applied</td>
                                <td class="text-end fw-bold">₦{{ number_format($appliedAmount, 2) }}</td>
                            </tr>
                            @if(abs($changeAmount) > 0.00001)
                                <tr>
                                    <td class="text-muted">Change</td>
                                    <td class="text-end fw-bold">₦{{ number_format($changeAmount, 2) }}</td>
                                </tr>
                            @endif
                            @if(abs($balanceDue) > 0.00001)
                                <tr>
                                    <td class="text-muted">Balance Due</td>
                                    <td class="text-end fw-bold text-danger">₦{{ number_format($balanceDue, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="total-row">
                                <td>Total</td>
                                <td class="text-end">₦{{ number_format((float) ($sale->total ?? 0), 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table compact-items-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->items as $item)
                                @php
                                    $qty = (float) ($item->qty ?? $item->quantity ?? 0);
                                    $soldUnitType = strtolower(trim((string) ($item->unit_type ?? 'unit')));
                                    $soldUnitLabel = match ($soldUnitType) {
                                        'carton' => 'ctn',
                                        'roll' => 'roll',
                                        'unit', 'pcs', 'piece', 'pieces', 'sachet' => 'pcs',
                                        default => $soldUnitType !== '' ? $soldUnitType : 'pcs',
                                    };
                                    $soldQuantity = rtrim(rtrim(number_format($qty, 2), '0'), '.');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold compact-item-name">{{ $item->product->name ?? $item->product_name ?? 'Item' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="compact-item-meta">
                                            {{ $soldQuantity }}
                                            <span class="text-muted text-uppercase">{{ $soldUnitLabel }}</span>
                                        </span>
                                    </td>
                                    <td class="text-end">₦{{ number_format((float) ($item->unit_price ?? 0), 2) }}</td>
                                    <td class="text-end">{{ number_format((float) ($item->discount ?? 0), 2) }}%</td>
                                    <td class="text-end fw-bold">₦{{ number_format((float) ($item->subtotal ?? $item->total_price ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No items listed.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="compact-extra-grid">
                    <div class="compact-panel compact-panel-muted">
                        <div class="compact-invoice-meta-label">Amount in Words</div>
                        <div class="compact-words-box">
                            <div class="compact-words-value">{{ $amountInWords }}</div>
                        </div>
                    </div>

                    <div class="compact-panel compact-panel-muted">
                        <div class="compact-invoice-meta-label">Cashier</div>
                        <div class="compact-invoice-meta-value">{{ $cashierName }}</div>
                    </div>
                </div>

                <div class="compact-note mt-4">
                    {{ $sale->notes ?? 'Thank you for your business.' }}
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    const autoPrintReceipt = {{ request()->boolean('autoprint') ? 'true' : 'false' }};
    let compactPrintLocked = false;

    function releaseCompactPrintLock() {
        compactPrintLocked = false;
    }

    function printCompactInvoice() {
        if (compactPrintLocked) {
            return;
        }

        compactPrintLocked = true;
        window.focus();
        window.setTimeout(() => window.print(), 120);
        window.setTimeout(releaseCompactPrintLock, 2500);
    }

    window.addEventListener('afterprint', releaseCompactPrintLock);

    if (autoPrintReceipt) {
        if (document.readyState === 'complete') {
            window.setTimeout(printCompactInvoice, 300);
        } else {
            window.addEventListener('load', () => window.setTimeout(printCompactInvoice, 300), { once: true });
        }
    }
</script>
</html>
