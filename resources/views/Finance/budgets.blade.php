<?php $page = 'finance-budgets'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Budgets
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

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Create Budget</h5>
                        <form action="{{ route('finance.budgets.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Budget Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Q2 Marketing Spend">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account</label>
                                <select name="account_id" class="form-select">
                                    <option value="">Select account</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ (string) old('account_id') === (string) $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }} ({{ $account->type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Period Type</label>
                                <select name="period_type" class="form-select">
                                    @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly', 'custom' => 'Custom'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('period_type', 'monthly') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->startOfMonth()->toDateString()) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date', now()->endOfMonth()->toDateString()) }}">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Budget Amount</label>
                                <input type="number" name="amount" step="0.01" min="0" class="form-control" value="{{ old('amount') }}">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 w-100">Save Budget</button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="mb-3">Budget Summary</h6>
                        <div class="mb-2 d-flex justify-content-between"><span>Budget Count</span><strong>{{ number_format((int) ($summary['budget_count'] ?? 0)) }}</strong></div>
                        <div class="mb-2 d-flex justify-content-between"><span>Total Budget</span><strong>₦{{ number_format((float) ($summary['budget_total'] ?? 0), 2) }}</strong></div>
                        <div class="mb-2 d-flex justify-content-between"><span>Actual Total</span><strong>₦{{ number_format((float) ($summary['actual_total'] ?? 0), 2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Variance Total</span><strong>₦{{ number_format((float) ($summary['variance_total'] ?? 0), 2) }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Budget vs Actual</h5>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Budget</th>
                                        <th>Period</th>
                                        <th class="text-end">Budgeted</th>
                                        <th class="text-end">Actual</th>
                                        <th class="text-end">Variance</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($budgets as $budget)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $budget->name }}</div>
                                                <small class="text-muted">{{ $budget->account?->name ?? 'No account' }}</small>
                                            </td>
                                            <td>{{ $budget->start_date?->format('d M Y') }} - {{ $budget->end_date?->format('d M Y') }}</td>
                                            <td class="text-end">₦{{ number_format((float) $budget->amount, 2) }}</td>
                                            <td class="text-end">₦{{ number_format((float) ($budget->actual_amount ?? 0), 2) }}</td>
                                            <td class="text-end {{ ($budget->variance_amount ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                                ₦{{ number_format((float) ($budget->variance_amount ?? 0), 2) }}
                                                <div><small>{{ number_format((float) ($budget->utilization_pct ?? 0), 1) }}% used</small></div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $budget->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($budget->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <form action="{{ route('finance.budgets.toggle', $budget->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                        {{ $budget->status === 'active' ? 'Archive' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No budgets created yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $budgets->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
