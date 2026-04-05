<?php $page = 'finance-approvals'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Finance Approvals
            @endslot
        @endcomponent

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                                <option value="{{ $value }}" {{ ($status ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All types</option>
                            <option value="expense" {{ ($type ?? '') === 'expense' ? 'selected' : '' }}>Expense</option>
                            <option value="purchase" {{ ($type ?? '') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter Queue</button>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="{{ route('finance.recurring.index') }}" class="btn btn-outline-secondary w-100">Recurring Templates</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Request</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Requested By</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approvals as $approval)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $approval->title }}</div>
                                        <small class="text-muted">{{ $approval->reference_no ?: 'No reference' }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark text-uppercase">{{ $approval->approval_type }}</span></td>
                                    <td>₦{{ number_format((float) ($approval->amount ?? 0), 2) }}</td>
                                    <td>
                                        <div>{{ $approval->requester?->name ?? 'System' }}</div>
                                        <small class="text-muted">{{ $approval->branch_name ?: 'Current branch scope' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{
                                            $approval->status === 'approved' ? 'bg-success' :
                                            ($approval->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark')
                                        }}">
                                            {{ ucfirst($approval->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $approval->submitted_at?->format('d M Y h:i A') ?? 'N/A' }}</td>
                                    <td class="text-end">
                                        @if($approval->status === 'pending')
                                            <div class="d-flex justify-content-end gap-2">
                                                <form action="{{ route('finance.approvals.approve', $approval->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form action="{{ route('finance.approvals.reject', $approval->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="small text-muted">
                                                {{ $approval->approver?->name ?? 'Processed' }}
                                                @if($approval->acted_at)
                                                    <div>{{ $approval->acted_at->format('d M Y h:i A') }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No approval items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $approvals->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
