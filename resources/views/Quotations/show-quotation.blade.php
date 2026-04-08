<?php $page = 'quotations'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Quotation Details</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('quotations') }}">Quotations</a></li>
                        <li class="breadcrumb-item active">{{ $quotation->quotation_id ?? ('Quotation #' . $quotation->id) }}</li>
                    </ul>
                </div>
                <div class="col-auto d-flex gap-2">
                    <a href="{{ route('quotations.download', $quotation->id) }}" class="btn btn-white border">
                        <i class="fe fe-download me-1"></i> Download
                    </a>
                    <a href="{{ route('quotations.convert-invoice', $quotation->id) }}" class="btn btn-primary">
                        <i class="fe fe-file-text me-1"></i> Convert to Invoice
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h5 class="mb-2">{{ $quotation->quotation_id ?? ('Quotation #' . $quotation->id) }}</h5>
                        <div class="text-muted">Customer</div>
                        <div class="fw-semibold">{{ $quotation->customer_name ?? $quotation->customer?->customer_name ?? $quotation->customer?->name ?? 'Walk-in Customer' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Issue Date</div>
                        <div class="fw-semibold">{{ $quotation->issue_date ? \Carbon\Carbon::parse($quotation->issue_date)->format('d M Y') : optional($quotation->created_at)->format('d M Y') }}</div>
                        <div class="text-muted mt-2">Valid Until</div>
                        <div class="fw-semibold">{{ $quotation->expiry_date ? \Carbon\Carbon::parse($quotation->expiry_date)->format('d M Y') : 'N/A' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Status</div>
                        <div class="fw-semibold">{{ $quotation->status ?? 'Pending' }}</div>
                        <div class="text-muted mt-2">Total</div>
                        <div class="fw-bold">₦{{ number_format((float) ($quotation->total ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Item</th>
                                <th>Price Level</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? 'Item' }}</td>
                                    <td>{{ ucfirst($item['price_level'] ?? 'retail') }}</td>
                                    <td>{{ $item['qty'] ?? 0 }}</td>
                                    <td>₦{{ number_format((float) ($item['rate'] ?? 0), 2) }}</td>
                                    <td>₦{{ number_format((float) ($item['discount'] ?? 0), 2) }}</td>
                                    <td>₦{{ number_format((float) ($item['tax'] ?? 0), 2) }}</td>
                                    <td class="text-end">₦{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No quotation items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-7">
                        <h6>Description / Note</h6>
                        <div class="text-muted">{{ $quotation->description ?? $quotation->note ?? 'No note added.' }}</div>
                    </div>
                    <div class="col-md-5">
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <strong>₦{{ number_format((float) ($quotation->subtotal ?? 0), 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount</span>
                                <strong>₦{{ number_format((float) ($quotation->discount ?? 0), 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax</span>
                                <strong>₦{{ number_format((float) ($quotation->tax ?? 0), 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total</span>
                                <strong>₦{{ number_format((float) ($quotation->total ?? 0), 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
