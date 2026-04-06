<?php $page = 'expense-claims'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Expense Claims & Reimbursements
            @endslot
        @endcomponent

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
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
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Claims</div>
                        <div class="fs-3 fw-bold">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Pending Review</div>
                        <div class="fs-3 fw-bold">{{ $stats['pending'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Approved</div>
                        <div class="fs-3 fw-bold">{{ $stats['approved'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-bold">Awaiting Reimbursement</div>
                        <div class="fs-5 fw-bold">₦{{ number_format((float) ($stats['pending_amount'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="text-muted small">Track staff claims, approve requests, and post reimbursements cleanly into expenses.</div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addExpenseClaimModal">
                <i class="fas fa-plus-circle me-1"></i> Submit Claim
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Claim</th>
                                <th>Employee</th>
                                <th>Project</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Reimbursement</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($claims as $claim)
                                <tr>
                                    <td>{{ optional($claim->expense_date)->format('d M Y') ?: 'N/A' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $claim->title }}</div>
                                        <small class="text-muted">{{ $claim->category ?: 'General claim' }}</small>
                                    </td>
                                    <td>{{ $claim->claimant?->name ?? 'Unknown user' }}</td>
                                    <td>{{ $claim->project?->name ?? 'Not linked' }}</td>
                                    <td class="fw-semibold">₦{{ number_format((float) $claim->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', (string) $claim->status) }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', (string) $claim->reimbursement_status) }}</div>
                                        <small class="text-muted">{{ $claim->reimbursementAccount?->name ?? 'Not paid yet' }}</small>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown dropdown-action">
                                            <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                @if($claim->status === 'pending')
                                                    <form action="{{ route('finance.expense-claims.approve', $claim) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="fas fa-check me-2"></i>Approve
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('finance.expense-claims.reject', $claim) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-times me-2"></i>Reject
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($claim->status === 'approved')
                                                    <button type="button" class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#reimburseClaimModal{{ $claim->id }}">
                                                        <i class="fas fa-wallet me-2"></i>Reimburse
                                                    </button>
                                                @endif
                                                <span class="dropdown-item text-muted">
                                                    <i class="fas fa-note-sticky me-2"></i>{{ $claim->notes ?: 'No extra note' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No expense claims yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $claims->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addExpenseClaimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Expense Claim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('finance.expense-claims.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Claim Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expense Date</label>
                            <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="Travel, logistics, client visit..." required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select">
                                <option value="">Not linked to a project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}{{ $project->client_name ? ' - ' . $project->client_name : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4" placeholder="Add the business reason and what was spent."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Claim</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($claims as $claim)
    @if($claim->status === 'approved')
        <div class="modal fade" id="reimburseClaimModal{{ $claim->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reimburse Claim</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('finance.expense-claims.reimburse', $claim) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <div class="fw-semibold">{{ $claim->title }}</div>
                                <div class="text-muted small">{{ $claim->claimant?->name ?? 'Unknown user' }}{{ $claim->project?->name ? ' • ' . $claim->project->name : '' }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pay From Account</label>
                                <select name="payment_account_id" class="form-select" required>
                                    <option value="">Choose account</option>
                                    @foreach($paymentAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="alert alert-info mb-0">
                                This will create a paid expense entry and post the reimbursement into your ledger.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Reimburse ₦{{ number_format((float) $claim->amount, 2) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
