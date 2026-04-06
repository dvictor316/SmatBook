<?php $page = 'transfer-audit'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Stock Transfer Audit
            @endslot
        @endcomponent

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Transfers</div>
                        <div class="fs-3 fw-bold">{{ $audits->total() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Products Moved</div>
                        <div class="fs-3 fw-bold">{{ $audits->pluck('product_id')->filter()->unique()->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Total Quantity</div>
                        <div class="fs-3 fw-bold">{{ number_format((float) $audits->getCollection()->sum('quantity'), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Quantity</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($audits as $audit)
                                <tr>
                                    <td>{{ optional($audit->created_at)->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $audit->product?->name ?? 'Product deleted' }}</div>
                                        <small class="text-muted">{{ $audit->product?->sku ?? 'No SKU' }}</small>
                                    </td>
                                    <td>{{ $audit->from_branch_name ?: 'Unknown source' }}</td>
                                    <td>{{ $audit->to_branch_name ?: 'Unknown destination' }}</td>
                                    <td class="fw-semibold">{{ number_format((float) $audit->quantity, 2) }}</td>
                                    <td>{{ $audit->initiator?->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No stock transfers logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $audits->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
