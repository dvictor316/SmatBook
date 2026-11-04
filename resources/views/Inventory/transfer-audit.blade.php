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

        <form method="GET" action="{{ route('inventory.transfer-audit') }}" class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label" for="transfer-search">Search</label>
                        <input id="transfer-search" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Product, branch, or staff">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="transfer-month">Month</label>
                        <input id="transfer-month" type="month" name="month" value="{{ $month }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="transfer-from-date">From</label>
                        <input id="transfer-from-date" type="date" name="from_date" value="{{ $fromDate }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="transfer-to-date">To</label>
                        <input id="transfer-to-date" type="date" name="to_date" value="{{ $toDate }}" class="form-control">
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Apply filters</button>
                        <a class="btn btn-light border" href="{{ route('inventory.transfer-audit') }}">Clear</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Transfers</div>
                        <div class="fs-3 fw-bold">{{ $summary->transfer_count ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Products Affected</div>
                        <div class="fs-3 fw-bold">{{ $summary->product_count ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Total Quantity</div>
                        <div class="fs-3 fw-bold">{{ \App\Support\CompactNumber::format((float) ($summary->quantity_total ?? 0), 2) }}</div>
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
                                <th>Details</th>
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
                                    <td class="fw-semibold">{{ \App\Support\CompactNumber::format((float) $audit->quantity, 2) }}</td>
                                    <td>{{ $audit->notes ?: 'Branch stock transfer' }}</td>
                                    <td>{{ $audit->initiator?->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No stock transfers logged yet.</td>
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
