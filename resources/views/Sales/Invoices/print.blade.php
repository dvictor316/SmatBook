<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $sale->invoice_no ?? $sale->id }}</title>
    <link rel="stylesheet" href="{{ URL::asset('/assets/css/bootstrap.min.css') }}">
    <style>
        body {
            background: #f8f9fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
        }

        .compact-invoice-shell {
            max-width: 820px;
            margin: 0 auto;
        }

        .compact-invoice-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .compact-invoice-brand {
            font-size: 20px;
            font-weight: 800;
            color: #4b308b;
            line-height: 1.15;
            margin-bottom: 8px;
        }

        .compact-invoice-meta-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .compact-invoice-meta-value {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }

        .compact-status {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .compact-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            background: #fff;
            height: 100%;
        }

        .compact-panel-muted {
            background: #f9fafb;
        }

        .compact-items-table {
            width: 100%;
            margin: 0;
        }

        .compact-items-table thead th {
            background: #f5f3ff;
            color: #4b308b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid #ddd;
            padding: 12px 10px;
        }

        .compact-items-table tbody td {
            padding: 12px 10px;
            border-color: #eceff4;
            vertical-align: top;
            font-size: 14px;
        }

        .compact-total-table td {
            padding: 8px 0;
            font-size: 14px;
        }

        .compact-total-table .total-row td {
            padding-top: 12px;
            border-top: 1px solid #d1d5db;
            font-size: 17px;
            font-weight: 800;
            color: #4b308b;
        }

        .compact-note {
            border-top: 1px dashed #d1d5db;
            padding-top: 14px;
            color: #6b7280;
            font-size: 13px;
        }

        @media print {
            @page {
                size: portrait;
                margin: 10mm;
            }

            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .compact-invoice-shell {
                max-width: none !important;
            }

            .compact-invoice-card {
                border: 0 !important;
                box-shadow: none !important;
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
            <div class="d-flex justify-content-end gap-2 mb-3 no-print">
                <a href="{{ $backUrl ?? url()->previous() }}" class="btn btn-outline-secondary">Back</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            </div>

            <div class="compact-invoice-card p-4 p-lg-5">
                <div class="row align-items-start gy-4">
                    <div class="col-md-7">
                        <div class="compact-invoice-brand">{{ $brandName }}</div>
                        <div class="text-muted small">{{ $brandAddress }}</div>
                        @if($brandPhone || $brandEmail)
                            <div class="text-muted small mt-1">
                                {{ $brandPhone }}{{ $brandPhone && $brandEmail ? ' | ' : '' }}{{ $brandEmail }}
                            </div>
                        @endif
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="compact-status {{ $displayStatus === 'paid' ? 'text-success' : ($displayStatus === 'partial' ? 'text-info' : 'text-danger') }}">
                            {{ strtoupper($displayStatus) }}
                        </div>
                        <div class="fs-4 fw-bold mt-2">Invoice</div>
                        <div class="text-muted">#{{ $sale->invoice_no ?? $sale->id }}</div>
                    </div>
                </div>

                <div class="row gy-3 mt-3">
                    <div class="col-md-4">
                        <div class="compact-panel compact-panel-muted">
                            <div class="compact-invoice-meta-label">Issue Date</div>
                            <div class="compact-invoice-meta-value">{{ optional($sale->created_at)->format('d M Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="compact-panel compact-panel-muted">
                            <div class="compact-invoice-meta-label">Reference</div>
                            <div class="compact-invoice-meta-value">{{ $sale->order_number ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="compact-panel compact-panel-muted">
                            <div class="compact-invoice-meta-label">Customer</div>
                            <div class="compact-invoice-meta-value">{{ $sale->display_customer_name }}</div>
                        </div>
                    </div>
                </div>

                @if(!empty($splitLines))
                    <div class="compact-panel mt-3">
                        <div class="row gy-3">
                            <div class="col-md-7">
                                <div class="compact-invoice-meta-label">Payment Breakdown</div>
                                <div class="fw-bold fs-5">Channels used for this receipt</div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <div class="compact-invoice-meta-label">Total Applied</div>
                                <div class="fs-3 fw-bold">₦{{ number_format($appliedAmount, 2) }}</div>
                            </div>
                        </div>

                        <div class="row gy-3 mt-1">
                            @foreach($splitLines as $line)
                                <div class="col-md-4">
                                    <div class="compact-panel compact-panel-muted">
                                        <div class="compact-invoice-meta-label">{{ $line['label'] }}</div>
                                        <div class="compact-invoice-meta-value">₦{{ number_format($line['amount'], 2) }}</div>
                                        @if($line['account'])
                                            <div class="text-muted small mt-1">{{ $line['account'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="table-responsive mt-4">
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
                                        <div class="fw-bold">{{ $item->product->name ?? $item->product_name ?? 'Item' }}</div>
                                    </td>
                                    <td class="text-center">
                                        {{ $soldQuantity }}
                                        <span class="text-muted small text-uppercase">{{ $soldUnitLabel }}</span>
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

                <div class="row justify-content-end mt-3">
                    <div class="col-md-5">
                        <div class="compact-panel">
                            <table class="w-100 compact-total-table">
                                <tr>
                                    <td class="text-muted">Subtotal</td>
                                    <td class="text-end fw-bold">₦{{ number_format($subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Discount</td>
                                    <td class="text-end fw-bold">₦{{ number_format($discount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tax</td>
                                    <td class="text-end fw-bold">₦{{ number_format($tax, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tendered</td>
                                    <td class="text-end fw-bold">₦{{ number_format($tenderedAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Applied</td>
                                    <td class="text-end fw-bold">₦{{ number_format($appliedAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Change</td>
                                    <td class="text-end fw-bold">₦{{ number_format($changeAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Balance Due</td>
                                    <td class="text-end fw-bold {{ $balanceDue > 0 ? 'text-danger' : 'text-success' }}">₦{{ number_format($balanceDue, 2) }}</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Total</td>
                                    <td class="text-end">₦{{ number_format((float) ($sale->total ?? 0), 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="compact-note mt-4">
                    {{ $sale->notes ?? 'Thank you for your business.' }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
