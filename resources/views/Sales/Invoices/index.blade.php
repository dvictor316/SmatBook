<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $sale->invoice_no }}</title>
    <link rel="stylesheet" href="{{ URL::asset('/assets/css/bootstrap.min.css') }}">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --light-gold: #fdfaf0;
            --border-gold: #e6d5a7;
            --text-gold: #b39b5d;
            --soft-blue: #f0f9ff;
            --deep-blue: #0369a1;
            --sweet-red: #ef4444;
            --sweet-red-hover: #dc2626;
            --success-green: #10b981;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body { 
            background-color: #fcfcfc; 
            margin: 0; 
            padding: 20px; 
            color: var(--gray-700);
        }
        
        .invoice-wrapper { 
            width: 210mm; 
            max-width: 100%;
            margin: 0 auto; 
            background-color: white; 
            padding: 40px; 
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        /* Top Controls */
        .no-print-controls { 
            display: flex; 
            justify-content: center; 
            gap: 10px; 
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn-action { 
            font-size: 13px; 
            padding: 10px 20px; 
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-print { background-color: var(--deep-blue); color: white; }
        .btn-print:hover { background-color: #075985; }
        
        .btn-pdf { background-color: var(--gray-700); color: white; }
        .btn-pdf:hover { background-color: #1f2937; }
        
        .btn-excel { background-color: var(--success-green); color: white; }
        .btn-excel:hover { background-color: #059669; }
        
        .btn-email { background-color: #9333ea; color: white; }
        .btn-email:hover { background-color: #7c3aed; }
        
        .btn-close-window { 
            background-color: var(--sweet-red); 
            color: white; 
            text-decoration: none; 
        }
        .btn-close-window:hover { 
            background-color: var(--sweet-red-hover); 
            color: white;
        }

        /* Header Section */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--deep-blue);
        }

        .company-info h4 {
            color: var(--deep-blue);
            font-weight: 800;
            margin-bottom: 5px;
            font-size: 24px;
        }

        .company-info p {
            margin: 0;
            color: var(--gray-700);
            font-size: 13px;
        }

        .invoice-info h3 {
            color: var(--text-gold);
            font-weight: 300;
            letter-spacing: 3px;
            margin: 0 0 5px 0;
            font-size: 28px;
        }

        .invoice-number {
            font-weight: 700;
            color: var(--gray-900);
            font-size: 14px;
        }

        /* Customer Section */
        .customer-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: 8px;
            border-left: 4px solid var(--text-gold);
        }

        .customer-info,
        .invoice-details {
            flex: 1;
        }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--gray-700);
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .customer-name {
            color: var(--deep-blue);
            font-size: 18px;
            font-weight: 700;
            margin: 5px 0;
        }

        .invoice-date {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .payment-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--soft-blue);
            color: var(--deep-blue);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }

        /* Table Styling */
        .table-custom { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            font-size: 13px;
        }

        .table-custom thead {
            background: linear-gradient(135deg, var(--soft-blue) 0%, #e0f2fe 100%);
        }

        .table-custom th { 
            color: var(--deep-blue); 
            padding: 14px 12px; 
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #0ea5e9;
        }

        .table-custom tbody tr {
            transition: background 0.2s;
        }

        .table-custom tbody tr:hover {
            background: var(--gray-50);
        }

        .table-custom td { 
            padding: 14px 12px; 
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .table-custom tbody tr:last-child td {
            border-bottom: 2px solid var(--gray-100);
        }

        .item-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        /* Summary Section */
        .invoice-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            gap: 30px;
        }

        .footer-left {
            flex: 1;
            max-width: 45%;
        }

        .words-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--gray-700);
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .amount-words {
            font-weight: 600;
            color: var(--text-gold);
            line-height: 1.5;
            font-size: 14px;
            font-style: italic;
        }

        .cashier-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-100);
        }

        .cashier-info p {
            margin: 5px 0;
            font-size: 13px;
        }

        .summary-box { 
            background: linear-gradient(135deg, var(--light-gold) 0%, #fef9e7 100%);
            padding: 25px; 
            border-radius: 12px; 
            min-width: 350px; 
            border: 2px solid var(--border-gold);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .summary-table { 
            width: 100%; 
            font-size: 14px; 
        }

        .summary-table tr {
            border-bottom: 1px dashed var(--border-gold);
        }

        .summary-table tr:last-child {
            border-bottom: none;
        }

        .summary-table td {
            padding: 10px 0;
        }

        .summary-table .label {
            color: var(--gray-700);
            font-weight: 500;
        }

        .summary-table .value {
            text-align: right;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .total-row { 
            color: var(--deep-blue); 
            font-weight: 800; 
            font-size: 20px; 
            border-top: 2px solid var(--text-gold) !important;
            border-bottom: 2px solid var(--text-gold) !important;
        }

        .total-row td {
            padding: 15px 0 !important;
        }

        .paid-row .value {
            color: var(--success-green);
        }

        .change-row .value {
            color: var(--deep-blue);
            font-weight: 700;
        }

        /* Footer Message */
        .thank-you {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px dashed var(--border-gold);
        }

        .thank-you p {
            font-weight: 700;
            color: var(--text-gold);
            letter-spacing: 2px;
            font-size: 13px;
            margin: 0;
        }

        /* Loading State */
        .loading {
            pointer-events: none;
            opacity: 0.6;
        }

        .loading::after {
            content: '...';
            animation: dots 1s steps(4, end) infinite;
        }

        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }

        /* Print Styles */
        @media print {
            .no-print-controls { 
                display: none !important; 
            }

            body { 
                background: white;
                padding: 0;
            }

            .invoice-wrapper { 
                width: 100% !important; 
                max-width: 100% !important;
                margin: 0 !important; 
                border: none !important; 
                padding: 20px !important;
                box-shadow: none !important;
            }

            .table-custom thead {
                background: var(--soft-blue) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-custom th { 
                color: var(--deep-blue) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .summary-box {
                background: var(--light-gold) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .invoice-wrapper {
                padding: 20px;
            }

            .invoice-header {
                flex-direction: column;
                gap: 20px;
            }

            .customer-section {
                flex-direction: column;
                gap: 20px;
            }

            .invoice-footer {
                flex-direction: column;
            }

            .footer-left {
                max-width: 100%;
            }

            .summary-box {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="invoice-wrapper" id="invoice_content">
        <!-- Action Buttons -->
        <div class="no-print-controls">
            <button onclick="printInvoice()" class="btn-action btn-print">
                🖨️ Print
            </button>
            <button onclick="exportPDF()" class="btn-action btn-pdf">
                📄 Save PDF
            </button>
            <button onclick="exportExcel()" class="btn-action btn-excel">
                📊 Excel
            </button>
            <button onclick="sendEmail()" class="btn-action btn-email">
                ✉️ Email
            </button>
            <a href="javascript:window.close()" class="btn-action btn-close-window">
                ✖️ Close
            </a>
        </div>

        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                @php
                    // Fetch company details from database
                    $companyDetails = null;
                    try {
                        $companyDetails = \DB::table('companies')->first();
                    } catch (\Exception $e) {
                        // Fallback if table doesn't exist
                    }
                @endphp
                <h4>{{ $companyDetails->name ?? $company->name ?? config('app.name', 'POS SYSTEM') }}</h4>
                <p>{{ $companyDetails->address ?? $company->address ?? 'Company Address Not Set' }}</p>
                <p>
                    {{ $companyDetails->phone ?? $company->phone ?? '' }} 
                    @if(($companyDetails->email ?? $company->email ?? false))
                        • {{ $companyDetails->email ?? $company->email }}
                    @endif
                </p>
            </div>
            <div class="invoice-info text-end">
                <h3>INVOICE</h3>
                <div class="invoice-number">#{{ $sale->invoice_no }}</div>
            </div>
        </div>

        <!-- Customer & Invoice Details -->
        <div class="customer-section">
            <div class="customer-info">
                <div class="section-label">Bill To</div>
                <div class="customer-name">{{ $sale->customer_name ?? 'Walk-in Customer' }}</div>
                @if($sale->customer_email ?? false)
                    <div style="font-size: 13px; color: var(--gray-700);">{{ $sale->customer_email }}</div>
                @endif
                @if($sale->customer_phone ?? false)
                    <div style="font-size: 13px; color: var(--gray-700);">{{ $sale->customer_phone }}</div>
                @endif
            </div>
            <div class="invoice-details text-end">
                <div class="section-label">Invoice Details</div>
                <div class="invoice-date">
                    {{ $sale->created_at ? $sale->created_at->format('d M, Y h:i A') : date('d M, Y h:i A') }}
                </div>
                <div class="payment-badge">
                    {{ strtoupper($sale->payment_method ?? 'Cash') }}
                </div>
                @if($sale->reference_no ?? false)
                    <div style="font-size: 12px; color: var(--gray-700); margin-top: 5px;">
                        Ref: {{ $sale->reference_no }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="table-custom" id="itemsTable">
            <thead>
                <tr>
                    <th style="text-align: left;">Item Description</th>
                    <th style="text-align: center; width: 80px;">Qty</th>
                    <th style="text-align: right; width: 100px;">Unit Price</th>
                    <th style="text-align: center; width: 80px;">Disc %</th>
                    <th style="text-align: center; width: 80px;">Tax %</th>
                    <th style="text-align: right; width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $calculatedSubtotal = 0;
                    $totalDiscount = 0;
                    $totalTax = 0;
                    
                    // First calculate all line items
                    foreach(($sale->items ?? []) as $item) {
                        $qty = (float)($item->qty ?? 0);
                        $unitPrice = (float)($item->unit_price ?? 0);
                        $discountPercent = (float)($item->discount ?? 0);
                        $taxPercent = (float)($item->tax ?? 0);
                        
                        $lineSubtotal = $qty * $unitPrice;
                        $discountAmount = ($lineSubtotal * $discountPercent) / 100;
                        $afterDiscount = $lineSubtotal - $discountAmount;
                        $taxAmount = ($afterDiscount * $taxPercent) / 100;
                        
                        $calculatedSubtotal += $lineSubtotal;
                        $totalDiscount += $discountAmount;
                        $totalTax += $taxAmount;
                    }
                    
                    // Calculate grand total
                    $grandTotal = $calculatedSubtotal - $totalDiscount + $totalTax;
                    
                    // Get amount paid - try multiple fields
                    $amountPaid = (float)(
                        $sale->amount_paid ?? 
                        $sale->paid_amount ?? 
                        $sale->amount ?? 
                        $sale->total_paid ?? 
                        $grandTotal
                    );
                    
                    // Calculate change
                    $changeAmount = max(0, $amountPaid - $grandTotal);
                @endphp

                @forelse($sale->items ?? [] as $item)
                    @php
                        $qty = (float)($item->qty ?? 0);
                        $unitPrice = (float)($item->unit_price ?? 0);
                        $discountPercent = (float)($item->discount ?? 0);
                        $taxPercent = (float)($item->tax ?? 0);
                        
                        // Calculate line total before discount
                        $lineSubtotal = $qty * $unitPrice;
                        
                        // Calculate discount amount
                        $discountAmount = ($lineSubtotal * $discountPercent) / 100;
                        
                        // Subtotal after discount
                        $afterDiscount = $lineSubtotal - $discountAmount;
                        
                        // Calculate tax amount
                        $taxAmount = ($afterDiscount * $taxPercent) / 100;
                        
                        // Final line total
                        $lineTotal = $afterDiscount + $taxAmount;
                    @endphp
                    <tr>
                        <td class="item-name">{{ $item->product->name ?? 'Unknown Product' }}</td>
                        <td style="text-align: center;">{{ number_format($qty, 0) }}</td>
                        <td style="text-align: right;">₦{{ number_format($unitPrice, 2) }}</td>
                        <td style="text-align: center; color: var(--sweet-red);">
                            {{ $discountPercent > 0 ? number_format($discountPercent, 1) . '%' : '-' }}
                        </td>
                        <td style="text-align: center; color: var(--deep-blue);">
                            {{ $taxPercent > 0 ? number_format($taxPercent, 1) . '%' : '-' }}
                        </td>
                        <td style="text-align: right; font-weight: 700;">₦{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--gray-700);">
                            No items found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer Section -->
        <div class="invoice-footer">
            <div class="footer-left">
                <div class="words-label">Amount in Words</div>
                <div class="amount-words">
                    @php
                        try {
                            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                            $words = ucwords($formatter->format($grandTotal)) . ' Naira Only';
                        } catch (\Exception $e) {
                            $words = 'Amount: ₦' . number_format($grandTotal, 2);
                        }
                    @endphp
                    {{ $sale->amount_in_words ?? $words }}
                </div>
                
                <div class="cashier-info">
                    <p><strong>Served by:</strong> {{ auth()->user()->name ?? 'Administrator' }}</p>
                    <p><strong>Date:</strong> {{ now()->format('d M, Y h:i A') }}</p>
                </div>
            </div>
            
            <div class="summary-section">
                <div class="summary-box">
                    <table class="summary-table">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="value">₦{{ number_format($calculatedSubtotal, 2) }}</td>
                        </tr>
                        @if($totalDiscount > 0)
                        <tr>
                            <td class="label">Discount</td>
                            <td class="value" style="color: var(--sweet-red);">-₦{{ number_format($totalDiscount, 2) }}</td>
                        </tr>
                        @endif
                        @if($totalTax > 0)
                        <tr>
                            <td class="label">Tax (VAT)</td>
                            <td class="value" style="color: var(--deep-blue);">+₦{{ number_format($totalTax, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-row">
                            <td>TOTAL</td>
                            <td class="value">₦{{ number_format($grandTotal, 2) }}</td>
                        </tr>
                        <tr class="paid-row">
                            <td class="label">Amount Paid</td>
                            <td class="value">₦{{ number_format($amountPaid, 2) }}</td>
                        </tr>
                        <tr class="change-row">
                            <td class="label">Change</td>
                            <td class="value">₦{{ number_format($changeAmount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Thank You Message -->
        <div class="thank-you">
            <p>*** THANK YOU FOR YOUR PATRONAGE ***</p>
            <p style="font-size: 11px; margin-top: 10px; font-weight: 400;">
                This is a computer-generated invoice
            </p>
        </div>
    </div>

<!-- Required Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    // Print Function
    function printInvoice() {
        window.print();
    }

    // Export to PDF
    async function exportPDF() {
        const button = event.target;
        button.classList.add('loading');
        button.disabled = true;

        try {
            const element = document.getElementById('invoice_content');
            const canvas = await html2canvas(element, {
                scale: 2,
                logging: false,
                useCORS: true
            });

            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });

            const imgWidth = 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
            pdf.save('Invoice_{{ $sale->invoice_no }}.pdf');
        } catch (error) {
            console.error('PDF export error:', error);
            alert('Failed to generate PDF. Please try printing instead.');
        } finally {
            button.classList.remove('loading');
            button.disabled = false;
        }
    }

    // Export to Excel
    function exportExcel() {
        const button = event.target;
        button.classList.add('loading');
        button.disabled = true;

        try {
            const table = document.getElementById("itemsTable");
            const wb = XLSX.utils.table_to_book(table, { sheet: "Invoice" });
            XLSX.writeFile(wb, "Invoice_{{ $sale->invoice_no }}.xlsx");
        } catch (error) {
            console.error('Excel export error:', error);
            alert('Failed to export to Excel.');
        } finally {
            button.classList.remove('loading');
            button.disabled = false;
        }
    }

    // Send Email
    function sendEmail() {
        const invoiceNo = '{{ $sale->invoice_no }}';
        
        @php
            $emailCompany = \DB::table('companies')->first();
        @endphp
        
        const companyName = '{{ $emailCompany->name ?? $company->name ?? config("app.name", "POS System") }}';
        const total = '{{ number_format($grandTotal, 2) }}';
        const paid = '{{ number_format($amountPaid, 2) }}';
        const change = '{{ number_format($changeAmount, 2) }}';

        const subject = `Invoice #${invoiceNo} from ${companyName}`;
        const body = `Dear Customer,

Please find below the details of your invoice:

Invoice Number: #${invoiceNo}
Date: {{ $sale->created_at ? $sale->created_at->format('d M, Y h:i A') : date('d M, Y h:i A') }}
Payment Method: {{ $sale->payment_method ?? 'Cash' }}

Total Amount: ₦${total}
Amount Paid: ₦${paid}
Change: ₦${change}

Thank you for your business!

---
${companyName}
{{ $emailCompany->address ?? $company->address ?? '' }}`;

        window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    }

    // Auto-print on load (optional)
    // window.onload = function() {
    //     setTimeout(() => window.print(), 500);
    // };
</script>
</body>
</html>