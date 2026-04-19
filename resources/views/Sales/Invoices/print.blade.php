<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $sale->invoice_no ?? $sale->id }}</title>
    <link rel="stylesheet" href="{{ URL::asset('/assets/css/bootstrap.min.css') }}">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #111827;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 24px;
        }

        .print-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 20px;
        }

        .invoice-print-root {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }

        .invoice-print-root .invoice-card,
        .invoice-print-root .invoice-print-shell,
        .invoice-print-root .card {
            border: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
        }

        .invoice-print-root .card-body {
            padding: 0 !important;
        }

        .invoice-print-root .bg-light-soft {
            background-color: #f5f5f5 !important;
        }

        .invoice-print-root .invoice-total-card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }

        .invoice-print-root .text-primary {
            color: #4b308b !important;
        }

        .invoice-print-root thead tr th {
            background-color: #f8f9fa !important;
            color: #4b308b !important;
        }

        .invoice-print-root tbody tr {
            background-color: #fcfcfc !important;
        }

        .invoice-print-root .border-start.border-primary {
            border-left-color: #4b308b !important;
        }

        @page {
            size: auto;
            margin: 8mm;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-toolbar {
                display: none !important;
            }

            .invoice-print-root {
                max-width: none;
            }
        }

        @media print and (orientation: landscape) {
            @page {
                size: landscape;
                margin: 6mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <a href="{{ $backUrl ?? url()->previous() }}" class="btn btn-outline-secondary">Back</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
    </div>

    <div class="invoice-print-root">
        @include('Sales.Invoices.partials.invoice-detail-content')
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 250);
        }, { once: true });
    </script>
</body>
</html>
