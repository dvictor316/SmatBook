<?php $page = 'invoice-details'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="page-header d-print-none">
                        <div class="content-invoice-header">
                            <h5>Invoice Details</h5>
                            <div class="list-btn">
                                <ul class="filter-list">
                                    <li>
                                        <a href="javascript:void(0)" onclick="printInvoice()" class="btn btn-primary">
                                            <i class="fa fa-print me-2"></i> Print Invoice
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                                            <i class="fa fa-arrow-left me-2"></i> Back to List
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div id="printableArea" data-print-scope>
                        @include('Sales.Invoices.partials.invoice-detail-content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let invoicePrintInProgress = false;

        function printInvoice() {
            if (invoicePrintInProgress) {
                return;
            }

            const target = document.getElementById('printableArea');
            if (!target) {
                window.print();
                return;
            }

            const printWindow = window.open('', '_blank', 'noopener,noreferrer,width=1280,height=900');
            if (!printWindow) {
                window.print();
                return;
            }

            const bootstrapHref = @json(URL::asset('/assets/css/bootstrap.min.css'));
            const title = @json('Invoice #' . ($sale->invoice_no ?? $sale->id));
            const content = target.innerHTML;
            invoicePrintInProgress = true;

            printWindow.document.open();
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>${title}</title>
                    <link rel="stylesheet" href="${bootstrapHref}">
                    <style>
                        html, body {
                            margin: 0;
                            padding: 0;
                            background: #ffffff;
                            color: #111827;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }

                        body {
                            padding: 24px;
                            font-family: Arial, Helvetica, sans-serif;
                        }

                        .invoice-print-root {
                            width: 100%;
                            max-width: 1180px;
                            margin: 0 auto;
                        }

                        .invoice-print-root .invoice-card,
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
                            html, body {
                                overflow: visible !important;
                            }

                            body {
                                padding: 0;
                            }

                            .invoice-print-root {
                                max-width: none;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="invoice-print-root">${content}</div>
                    <script>
                        window.addEventListener('load', function () {
                            setTimeout(function () {
                                window.focus();
                                window.print();
                            }, 180);
                        }, { once: true });

                        window.addEventListener('afterprint', function () {
                            window.close();
                        }, { once: true });
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.addEventListener('beforeunload', function () {
                invoicePrintInProgress = false;
            }, { once: true });

            setTimeout(function () {
                invoicePrintInProgress = false;
            }, 5000);
        }
    </script>

    <style>
        .bg-light-soft { background-color: #fbfbfb; }

        @media print {
            .invoice-total-card { border: 1px solid #ddd !important; }
            .invoice-item-date.bg-light-soft { background-color: #f5f5f5 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .text-primary { color: #4b308b !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            thead tr th { background-color: #f8f9fa !important; color: #4b308b !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            tbody tr { background-color: #fcfcfc !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .border-start.border-primary { border-left-color: #4b308b !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .col-lg-5, .col-lg-7 { flex: 0 0 auto; }
            .page-wrapper,
            .content,
            .container-fluid,
            .card,
            .card-body {
                margin: 0 !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                box-shadow: none !important;
                border: 0 !important;
            }
        }
    </style>
@endsection
