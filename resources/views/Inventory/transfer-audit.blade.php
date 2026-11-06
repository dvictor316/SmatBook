<?php $page = 'transfer-audit'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        .page-wrapper { margin: 0 !important; }
    }
</style>
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Stock transfer documents
            @endslot
        @endcomponent

        <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 no-print">
            <a href="{{ route('inventory.transfer.export', request()->query()) }}" class="btn btn-outline-success">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <a href="{{ route('inventory.transfer.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New transfer
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('inventory.transfer-audit') }}" class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="transfer-search">Search</label>
                        <input id="transfer-search" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Reference, product, or staff">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="transfer-from-branch">From branch</label>
                        <select id="transfer-from-branch" name="from_branch_id" class="form-select">
                            <option value="">All source branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] }}" @selected(($validated['from_branch_id'] ?? '') === (string) $branch['id'])>{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="transfer-to-branch">To branch</label>
                        <select id="transfer-to-branch" name="to_branch_id" class="form-select">
                            <option value="">All destination branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] }}" @selected(($validated['to_branch_id'] ?? '') === (string) $branch['id'])>{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-1 col-md-4">
                        <label class="form-label" for="transfer-status">Status</label>
                        <select id="transfer-status" name="status" class="form-select">
                            <option value="">All</option>
                            <option value="completed" @selected(($validated['status'] ?? '') === 'completed')>Completed</option>
                            <option value="reversed" @selected(($validated['status'] ?? '') === 'reversed')>Reversed</option>
                        </select>
                    </div>
                    <div class="col-xl-1 col-md-4">
                        <label class="form-label" for="transfer-month">Month</label>
                        <input id="transfer-month" type="month" name="month" value="{{ $month }}" class="form-control">
                    </div>
                    <div class="col-xl-1 col-md-4">
                        <label class="form-label" for="transfer-from-date">From</label>
                        <input id="transfer-from-date" type="date" name="from_date" value="{{ $fromDate }}" class="form-control">
                    </div>
                    <div class="col-xl-1 col-md-4">
                        <label class="form-label" for="transfer-to-date">To</label>
                        <input id="transfer-to-date" type="date" name="to_date" value="{{ $toDate }}" class="form-control">
                    </div>
                    <div class="col-xl-2 col-md-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a class="btn btn-light border" href="{{ route('inventory.transfer-audit') }}">Clear</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted text-uppercase small fw-bold">Transfer documents</div>
                    <div class="fs-3 fw-bold">{{ $summary->transfer_count ?? 0 }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted text-uppercase small fw-bold">Distinct products</div>
                    <div class="fs-3 fw-bold">{{ $summary->product_count ?? 0 }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted text-uppercase small fw-bold">Total quantity moved</div>
                    <div class="fs-3 fw-bold">{{ \App\Support\CompactNumber::format((float) ($summary->quantity_total ?? 0), 2) }}</div>
                </div></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Reference / date</th>
                                <th>Route</th>
                                <th>Products</th>
                                <th>Total quantity</th>
                                <th>Status</th>
                                <th>Created by</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                                <tr>
                                    <td>
                                        <a class="fw-semibold text-primary" href="{{ route('inventory.transfer.show', $transfer->id) }}">{{ $transfer->reference }}</a>
                                        <div class="small text-muted">{{ $transfer->transfer_date?->format('d M Y, h:i A') }}</div>
                                    </td>
                                    <td>
                                        <span>{{ $transfer->from_branch_name ?: 'Unknown source' }}</span>
                                        <i class="fas fa-arrow-right text-muted mx-1"></i>
                                        <span>{{ $transfer->to_branch_name ?: 'Unknown destination' }}</span>
                                    </td>
                                    <td>{{ $transfer->items_count }}</td>
                                    <td class="fw-semibold">{{ \App\Support\CompactNumber::format((float) ($transfer->items_sum_quantity ?? 0), 2) }}</td>
                                    <td>
                                        @if($transfer->status === 'reversed')
                                            <span class="badge bg-secondary">Reversed</span>
                                        @else
                                            <span class="badge bg-success">Completed</span>
                                        @endif
                                    </td>
                                    <td>{{ $transfer->initiator?->name ?? 'System' }}</td>
                                    <td class="no-print">
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('inventory.transfer.show', $transfer->id) }}" class="btn btn-sm btn-outline-primary">View / Print</a>
                                            @if($transfer->status === 'completed')
                                                <form method="POST" action="{{ route('inventory.transfer.destroy', $transfer->id) }}" onsubmit="return window.confirm('Reverse every line in this transfer? This will proceed only if all stock is available at the destination.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reverse</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No stock transfers match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $transfers->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
