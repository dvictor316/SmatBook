@extends('layout.mainlayout')

@section('page-title', 'Estimate Details')

@section('content')
@php
    $currencySymbol = config('app.currency_symbol', '₦');
    $status = strtolower((string) ($estimate->status ?? 'draft'));
    $badge = match ($status) {
        'sent' => 'success',
        'accepted' => 'primary',
        'declined' => 'danger',
        'expired' => 'danger',
        default => 'warning',
    };
    $issueDate = optional($estimate->issue_date)->format('d M Y') ?? '-';
    $expiryDate = optional($estimate->expiry_date)->format('d M Y') ?? '-';
    $subtotal = (float) ($estimate->subtotal ?? 0);
    $tax = (float) ($estimate->tax ?? 0);
    $discount = (float) ($estimate->discount ?? 0);
    $totalAmount = (float) ($estimate->amount ?? $estimate->total_amount ?? 0);
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">Estimate Details</h5>
                    <div class="text-muted small">
                        {{ $estimate->estimate_number ?? ('EST-' . str_pad($estimate->id, 5, '0', STR_PAD_LEFT)) }}
                        <span class="mx-2">•</span>
                        <span class="badge bg-soft-{{ $badge }} text-{{ $badge }}">{{ ucfirst($status) }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('estimates.index') }}" class="btn btn-light">Back</a>
                    <a href="{{ route('estimates.edit', $estimate->id) }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Edit
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fa-solid fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-1">Estimate Summary</h5>
                        <div class="text-muted small">Customer information and commercial terms.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Customer</div>
                                <div class="fw-semibold">{{ $estimate->customer?->name ?? 'N/A' }}</div>
                                <div class="text-muted small">{{ $estimate->customer?->email ?? $estimate->customer?->phone ?? 'No contact info' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Estimate Dates</div>
                                <div class="fw-semibold">Issued: {{ $issueDate }}</div>
                                <div class="text-muted small">Expires: {{ $expiryDate }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Status</div>
                                <span class="badge bg-soft-{{ $badge }} text-{{ $badge }}">{{ ucfirst($status) }}</span>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Last updated</div>
                                <div class="fw-semibold">{{ optional($estimate->updated_at)->format('d M Y, H:i') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-muted small mb-1">Notes</div>
                            <div class="bg-light border rounded p-3">
                                {{ $estimate->notes ?: 'No notes added yet.' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-1">Totals</h6>
                        <div class="text-muted small">Breakdown of estimate values.</div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">{{ $currencySymbol }}{{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tax</span>
                            <span class="fw-semibold">{{ $currencySymbol }}{{ number_format($tax, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Discount</span>
                            <span class="fw-semibold">-{{ $currencySymbol }}{{ number_format($discount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="fw-semibold">Total</span>
                            <span class="fw-bold">{{ $currencySymbol }}{{ number_format($totalAmount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-1">Next Steps</h6>
                        <div class="text-muted small">Keep the estimate moving.</div>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted small mb-0">
                            <li>Share the estimate with the customer.</li>
                            <li>Follow up before the expiry date.</li>
                            <li>Convert to invoice once approved.</li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-1">Quick Actions</h6>
                        <div class="text-muted small">Keep workflows tight.</div>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('estimates.edit', $estimate->id) }}" class="btn btn-outline-primary">
                            <i class="fa-solid fa-pen-to-square me-2"></i>Edit Estimate
                        </a>
                        <a href="{{ route('estimates.index') }}" class="btn btn-light">Back to Estimates</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
