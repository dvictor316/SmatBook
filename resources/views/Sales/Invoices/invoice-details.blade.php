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
                                        <a href="{{ route('invoice-details.print', ['id' => $sale->id, 'autoprint' => 1]) }}" target="_blank" rel="noopener" class="btn btn-primary">
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
