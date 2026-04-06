<?php $page = 'finance-follow-ups'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Collections Follow-Ups
            @endslot
        @endcomponent

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small fw-bold">Follow-Ups</div><div class="fs-3 fw-bold">{{ $stats['total'] ?? 0 }}</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small fw-bold">Open</div><div class="fs-3 fw-bold">{{ $stats['open'] ?? 0 }}</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small fw-bold">Due Today</div><div class="fs-3 fw-bold">{{ $stats['due_today'] ?? 0 }}</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small fw-bold">Overdue</div><div class="fs-3 fw-bold text-danger">{{ $stats['overdue'] ?? 0 }}</div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Schedule Follow-Up</h5>
                        <form method="POST" action="{{ route('finance.follow-ups.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Party Type</label>
                                <select name="party_type" class="form-select" id="followUpPartyType">
                                    <option value="customer" {{ old('party_type', 'customer') === 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="supplier" {{ old('party_type') === 'supplier' ? 'selected' : '' }}>Supplier</option>
                                </select>
                            </div>
                            <div class="mb-3" data-followup-customer>
                                <label class="form-label">Customer</label>
                                <select name="customer_id" class="form-select">
                                    <option value="">Select customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->customer_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 d-none" data-followup-supplier>
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">Select supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name ?? $supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Call about overdue invoice">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" class="form-select">
                                        @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('priority', 'normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', now()->toDateString()) }}">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="4" class="form-control" placeholder="Capture what to discuss, negotiation points, and next promise date.">{{ old('notes') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 w-100">Save Follow-Up</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Open and Completed Follow-Ups</h5>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Due</th>
                                        <th>Party</th>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($followUps as $followUp)
                                        <tr>
                                            <td>{{ optional($followUp->due_date)->format('d M Y') ?: 'N/A' }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $followUp->party_type === 'customer' ? ($followUp->customer?->customer_name ?? 'Customer') : ($followUp->supplier?->name ?? $followUp->supplier?->supplier_name ?? 'Supplier') }}</div>
                                                <small class="text-muted text-capitalize">{{ $followUp->party_type }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $followUp->title }}</div>
                                                <small class="text-muted">{{ $followUp->notes ?: 'No extra note' }}</small>
                                            </td>
                                            <td><span class="badge bg-light text-dark text-capitalize">{{ $followUp->priority }}</span></td>
                                            <td>
                                                <span class="badge {{ $followUp->status === 'completed' ? 'bg-success' : ($followUp->status === 'cancelled' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                                                    {{ ucfirst($followUp->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                @if($followUp->status === 'open')
                                                    <form method="POST" action="{{ route('finance.follow-ups.complete', $followUp) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Complete</button>
                                                    </form>
                                                @else
                                                    <span class="small text-muted">{{ $followUp->completer?->name ?? 'Closed' }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">No follow-ups scheduled yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $followUps->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const typeSelect = document.getElementById('followUpPartyType');
        const customerBlock = document.querySelector('[data-followup-customer]');
        const supplierBlock = document.querySelector('[data-followup-supplier]');
        function syncFollowUpParty() {
            const value = typeSelect ? typeSelect.value : 'customer';
            customerBlock?.classList.toggle('d-none', value !== 'customer');
            supplierBlock?.classList.toggle('d-none', value !== 'supplier');
        }
        typeSelect?.addEventListener('change', syncFollowUpParty);
        syncFollowUpParty();
    })();
</script>
@endpush
