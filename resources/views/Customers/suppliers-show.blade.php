<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">{{ $supplier->name ?? $supplier->supplier_name ?? $supplier->company_name ?? 'Supplier' }}</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                        <li class="breadcrumb-item active">History</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="{{ route('suppliers.pay', $supplier->id) }}" class="btn btn-success me-2">
                        <i class="far fa-credit-card me-1"></i>Pay Supplier
                    </a>
                    <a href="{{ route('suppliers.statement', $supplier->id) }}" class="btn btn-outline-secondary me-2">
                        <i class="far fa-file-alt me-1"></i>Statement
                    </a>
                    @if(Route::has('finance.follow-ups.index'))
                        <a href="{{ route('finance.follow-ups.index') }}" class="btn btn-outline-dark me-2">
                            <i class="far fa-calendar-check me-1"></i>Follow-Ups
                        </a>
                    @endif
                    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-outline-primary">
                        <i class="far fa-edit me-1"></i>Edit Supplier
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Purchases</p>
                        <h4 class="fw-bold mb-0">{{ number_format($summary['total_purchases']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Spend</p>
                        <h4 class="fw-bold mb-0">{{ number_format($summary['total_spend'], 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Items Received</p>
                        <h4 class="fw-bold mb-0">{{ number_format($summary['received_items']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Pending Items</p>
                        <h4 class="fw-bold mb-0">{{ number_format($summary['pending_items']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Opening Balance</p>
                        <h4 class="fw-bold mb-0">{{ number_format((float) ($summary['opening_balance_original'] ?? $supplier->opening_balance ?? 0), 2) }}</h4>
                        <small class="text-muted">as of {{ $supplier->opening_balance_date ?? '—' }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Outstanding Payables</p>
                        <h4 class="fw-bold mb-0">{{ number_format((float) ($summary['outstanding_payables'] ?? 0), 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Paid So Far</p>
                        <h4 class="fw-bold mb-0">{{ number_format((float) ($summary['total_paid'] ?? 0), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Purchase History</h5>
                <p class="text-muted small mb-0">Track purchase raised, received, and pending items for this supplier.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-center table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->id }}</td>
                                    <td>
                                        {{ optional($purchase->{$purchaseDateColumn} ?? $purchase->created_at)->format('M d, Y') ?? 'N/A' }}
                                    </td>
                                    <td>{{ $purchase->reference_no ?? $purchase->invoice_serial_no ?? $purchase->purchase_order_no ?? '—' }}</td>
                                    <td class="text-capitalize">
                                        {{ $receivedColumn ? ($purchase->{$receivedColumn} ?? 'pending') : 'pending' }}
                                        @php
                                            $items = $purchaseItemsByPurchase[$purchase->id] ?? collect();
                                            $totalQty = (float) $items->sum('qty');
                                            $receivedQty = (float) $items->sum('received_qty');
                                            $pct = $totalQty > 0 ? round(($receivedQty / $totalQty) * 100) : 0;
                                        @endphp
                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted">{{ $receivedQty }} received / {{ $totalQty }} total</small>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format((float) ($purchase->resolved_total_amount ?? ($totalColumn ? ($purchase->{$totalColumn} ?? 0) : 0)), 2) }}
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#purchase-items-{{ $purchase->id }}">
                                            Items
                                        </button>
                                        @if(Route::has('purchase-details'))
                                            <a href="{{ route('purchase-details', $purchase->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="collapse bg-light" id="purchase-items-{{ $purchase->id }}">
                                    <td colspan="6">
                                        <div class="p-3">
                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Item</th>
                                                            <th class="text-end">Qty</th>
                                                            <th class="text-end">Received</th>
                                                            <th class="text-end">Unit Price</th>
                                                            <th class="text-end">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse(($purchaseItemsByPurchase[$purchase->id] ?? collect()) as $item)
                                                            <tr>
                                                                <td>{{ $item->product_name }}</td>
                                                                <td class="text-end">{{ number_format((float) ($item->qty ?? 0)) }}</td>
                                                                <td class="text-end">{{ number_format((float) ($item->received_qty ?? 0)) }}</td>
                                                                <td class="text-end">{{ number_format((float) ($item->unit_price ?? 0), 2) }}</td>
                                                                <td class="text-end">{{ number_format((float) ($item->line_total ?? 0), 2) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center text-muted">No items recorded for this purchase.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No purchase history yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment History</h5>
                <p class="text-muted small mb-0">Review every payment already made against this supplier.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-center table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Purchase</th>
                                <th>Bank</th>
                                <th>Method</th>
                                <th class="text-end">Amount</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplierPayments as $payment)
                                <tr>
                                    <td>{{ optional($payment->payment_date)->format('M d, Y') ?: optional($payment->created_at)->format('M d, Y') }}</td>
                                    <td>{{ $payment->reference ?: ($payment->payment_group ?: '—') }}</td>
                                    <td>{{ $payment->purchase?->purchase_no ?: 'Manual supplier payment' }}</td>
                                    <td>{{ $payment->bank?->name ?: 'Not specified' }}</td>
                                    <td>{{ $payment->method ?: '—' }}</td>
                                    <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>{{ $payment->note ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No supplier payment history recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
