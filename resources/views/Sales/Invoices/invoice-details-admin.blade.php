<?php $page = 'invoice-details-admin'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header d-print-none">
                <div class="content-invoice-header">
                    <h5>Invoice Management: #{{ $sale->invoice_no ?? $sale->id }}</h5>
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

            <div class="row">

                <div class="col-lg-8" id="printableArea">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">

                            <div class="p-4 bg-white rounded-top border-bottom">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <h6 class="text-muted text-uppercase small fw-bold">Customer</h6>
                                        <p class="h5 text-dark mb-1">{{ $sale->customer_name }}</p>
                                        <p class="text-muted small mb-0">{{ $sale->customer_email ?? 'No email provided' }}</p>
                                    </div>
                                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                                        <h6 class="text-muted text-uppercase small fw-bold">Invoice Details</h6>
                                        <p class="mb-1 text-dark">Date: <strong>{{ $sale->created_at->format('d M Y') }}</strong></p>

                                        <p class="mb-0 text-dark">Status: <strong class="text-uppercase">{{ $sale->effective_payment_status ?? $sale->payment_status }}</strong></p>
                                    </div>
                                </div>
                            </div>

                            @include('Sales.Invoices.invoice-details')

                            <div class="p-4 bg-white border-top">
                                <div class="row justify-content-end">
                                    <div class="col-sm-5">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Subtotal:</span>

                                            <span class="fw-bold text-dark">{{ number_format($sale->items->sum('subtotal'), 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Tax:</span>
                                            <span class="fw-bold text-dark">{{ number_format($sale->tax ?? 0, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Paid:</span>
                                            <span class="fw-bold text-dark">{{ number_format($sale->effective_paid ?? $sale->amount_paid ?? 0, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Due:</span>
                                            <span class="fw-bold {{ (($sale->effective_balance ?? $sale->balance ?? 0) > 0) ? 'text-danger' : 'text-success' }}">{{ number_format($sale->effective_balance ?? $sale->balance ?? 0, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-top pt-2">
                                            <span class="h5 mb-0">Total:</span>
                                            <span class="h5 mb-0 text-primary">{{ number_format($sale->effective_total ?? $sale->total, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 border-top bg-white rounded-bottom">
                                <div class="row align-items-center">
                                    <div class="col-sm-6">
                                        <div class="invoice-terms">
                                            <h6 class="fw-bold text-dark">Notes/Terms</h6>
                                            <p class="mb-0 text-muted small" style="max-width: 90%;">
                                                {{ $sale->notes ?? 'Thank you for your business.' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                                        <div class="invoice-signature">
                                            <p class="mb-2 text-muted">Authorised Signature</p>
                                            <div class="mt-2 mx-auto ms-sm-auto" style="border-bottom: 2px solid #eee; width: 180px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-print-none">
                    <div class="card timeline-card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">Admin Controls</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase text-muted">Update Status</label>
                                <select class="form-control select">
                                    @foreach(['paid', 'unpaid', 'partially paid', 'overdue', 'cancelled'] as $status)
                                        <option value="{{ $status }}" {{ (($sale->effective_payment_status ?? $sale->payment_status) == $status) ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="p-3 bg-light rounded mb-4">
                                <h6 class="small fw-bold text-uppercase text-muted mb-3">Order Info</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Reference:</span>
                                    <span class="fw-bold text-dark">{{ $sale->order_number ?? 'N/A' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Created:</span>
                                    <span class="text-dark">{{ $sale->created_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            <h6 class="small fw-bold text-uppercase text-muted mb-3">Activity</h6>
                            <ul class="activity-feed list-unstyled small ps-2">
                                <li class="feed-item border-start ps-3 pb-3 position-relative">
                                    <span class="feed-text">Invoice generated by <strong>System</strong></span>
                                    <div class="text-muted smaller">{{ $sale->created_at->format('d M, h:i A') }}</div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printInvoice() {
            window.print();
        }
    </script>

    <style>
        .activity-feed .feed-item::before {
            content: "";
            position: absolute;
            left: -5px;
            top: 0;
            width: 10px;
            height: 10px;
            background: #4b308b;
            border-radius: 50%;
        }

        @media print {
            .page-header, .sidebar, .header, .btn, .d-print-none, .timeline-card { 
                display: none !important; 
            }
            .page-wrapper { margin: 0 !important; padding: 0 !important; }
            .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        }
    </style>
@endsection
