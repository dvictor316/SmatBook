<?php $page = 'transfer-show'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        .page-wrapper { margin: 0 !important; }
        .card { box-shadow: none !important; }
    }
</style>
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Stock transfer details
            @endslot
        @endcomponent

        @if(session('success'))
            <div class="alert alert-success no-print">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger no-print">{{ session('error') }}</div>
        @endif

        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3 no-print">
            <a href="{{ route('inventory.transfer-audit') }}" class="btn btn-light border">
                <i class="fas fa-arrow-left me-1"></i> Transfer list
            </a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                @if($transfer->status === 'completed')
                    <form method="POST" action="{{ route('inventory.transfer.destroy', $transfer->id) }}" onsubmit="return window.confirm('Reverse every line in this transfer? This will proceed only if all stock is available at the destination.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Reverse entire transfer</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="text-muted small text-uppercase">Transfer reference</div>
                        <h3 class="mb-1">{{ $transfer->reference }}</h3>
                        <div class="text-muted">{{ $transfer->transfer_date?->format('d M Y, h:i A') }}</div>
                    </div>
                    <div class="text-end">
                        @if($transfer->status === 'reversed')
                            <span class="badge bg-secondary fs-6">Reversed</span>
                        @else
                            <span class="badge bg-success fs-6">Completed</span>
                        @endif
                        <div class="small text-muted mt-2">Recorded by {{ $transfer->initiator?->name ?? 'System' }}</div>
                    </div>
                </div>
                <hr>
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="text-muted small text-uppercase">From branch</div>
                        <div class="fw-semibold">{{ $transfer->from_branch_name ?: 'Unknown source' }}</div>
                    </div>
                    <div class="col-md-2 text-center d-none d-md-block pt-3">
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    <div class="col-md-5">
                        <div class="text-muted small text-uppercase">To branch</div>
                        <div class="fw-semibold">{{ $transfer->to_branch_name ?: 'Unknown destination' }}</div>
                    </div>
                    @if($transfer->notes)
                        <div class="col-12">
                            <div class="text-muted small text-uppercase">Notes</div>
                            <div>{{ $transfer->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Transferred items <span class="text-muted">({{ $transfer->items->count() }})</span></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Product</th>
                                <th>Quantity</th>
                                <th>Line note</th>
                                @if($transfer->status === 'completed')
                                    <th class="no-print">Return quantity</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfer->items as $item)
                                @php($remainingQuantity = max(0, round((float) $item->quantity - (float) ($item->reversals_sum_quantity ?? 0), 2)))
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $item->product?->name ?? 'Product deleted' }}</div>
                                        <small class="text-muted">{{ $item->product?->sku ?? 'No SKU' }}</small>
                                    </td>
                                    <td class="fw-semibold">{{ \App\Support\CompactNumber::format((float) $item->quantity, 2) }}</td>
                                    <td>{{ $item->notes ?: 'Stock transfer' }}</td>
                                    @if($transfer->status === 'completed')
                                        <td class="no-print">
                                            @if($item->product && $remainingQuantity > 0 && $item->from_branch_id && $item->to_branch_id)
                                                <form method="POST" action="{{ route('inventory.transfer-item.return', $item->id) }}" class="d-flex gap-2 align-items-center">
                                                    @csrf
                                                    <input type="number" name="quantity" class="form-control form-control-sm" style="max-width:130px" min="0.01" max="{{ $remainingQuantity }}" step="0.01" value="{{ $remainingQuantity }}" aria-label="Quantity to return">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Return</button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Fully returned</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $transfer->status === 'completed' ? 4 : 3 }}" class="text-center text-muted py-4">This document has no item lines.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="ps-3">Total quantity</th>
                                <th>{{ \App\Support\CompactNumber::format((float) $transfer->items->sum('quantity'), 2) }}</th>
                                <th colspan="{{ $transfer->status === 'completed' ? 2 : 1 }}"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
