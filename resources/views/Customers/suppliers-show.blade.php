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
                                    </td>
                                    <td class="text-end">
                                        {{ number_format((float) ($totalColumn ? ($purchase->{$totalColumn} ?? 0) : 0), 2) }}
                                    </td>
                                    <td class="text-end">
                                        @if(Route::has('purchase-details'))
                                            <a href="{{ route('purchase-details', $purchase->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @endif
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
    </div>
</div>
@endsection
