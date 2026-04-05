<?php $page = 'finance-recurring'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Recurring Transactions
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

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Create Recurring Template</h5>
                        <form action="{{ route('finance.recurring.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Monthly office rent">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Source Type</label>
                                <select name="source_type" id="recurring_source_type" class="form-select">
                                    <option value="expense" {{ old('source_type') === 'expense' ? 'selected' : '' }}>Expense</option>
                                    <option value="purchase" {{ old('source_type') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                                </select>
                            </div>
                            <div class="mb-3 recurring-source recurring-source-expense">
                                <label class="form-label">Expense Source</label>
                                <select name="source_id" class="form-select">
                                    <option value="">Select expense</option>
                                    @foreach(($expenseSources ?? []) as $expense)
                                        <option value="{{ $expense->id }}" {{ (string) old('source_id') === (string) $expense->id && old('source_type', 'expense') === 'expense' ? 'selected' : '' }}>
                                            {{ $expense->expense_id }} - {{ $expense->company_name }} - ₦{{ number_format((float) $expense->amount, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 recurring-source recurring-source-purchase d-none">
                                <label class="form-label">Purchase Source</label>
                                <select class="form-select recurring-purchase-select">
                                    <option value="">Select purchase</option>
                                    @foreach(($purchaseSources ?? []) as $purchase)
                                        <option value="{{ $purchase->id }}">
                                            {{ $purchase->purchase_no ?? ('PUR-' . $purchase->id) }} - {{ $purchase->supplier?->name ?? $purchase->vendor?->name ?? 'Supplier' }} - ₦{{ number_format((float) ($purchase->total_amount ?? 0), 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Frequency</label>
                                    <select name="frequency" class="form-select">
                                        @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('frequency', 'monthly') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Every</label>
                                    <input type="number" min="1" max="12" name="interval_value" value="{{ old('interval_value', 1) }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Starts On</label>
                                    <input type="date" name="starts_on" value="{{ old('starts_on', now()->toDateString()) }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Next Run On</label>
                                    <input type="date" name="next_run_on" value="{{ old('next_run_on', now()->addMonth()->toDateString()) }}" class="form-control">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control" placeholder="Optional scheduling notes">{{ old('notes') }}</textarea>
                            </div>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="auto_post" value="1" id="auto_post" {{ old('auto_post') ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_post">Auto-post when possible</label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="approval_required" value="1" id="approval_required" {{ old('approval_required', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="approval_required">Require approval before final posting</label>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 w-100">Save Template</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Saved Templates</h5>
                                <p class="text-muted mb-0">Branch-aware recurring purchase and expense automation.</p>
                            </div>
                            <a href="{{ route('finance.approvals.index') }}" class="btn btn-outline-primary">View Approval Queue</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Source</th>
                                        <th>Frequency</th>
                                        <th>Next Run</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($templates as $template)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $template->name }}</div>
                                                <small class="text-muted">{{ $template->branch_name ?: 'All active branch context' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark text-uppercase">{{ $template->source_type }}</span>
                                                <div class="small text-muted">{{ data_get($template->payload, 'source_reference', 'Source #' . $template->source_id) }}</div>
                                            </td>
                                            <td>{{ ucfirst($template->frequency) }} x{{ $template->interval_value }}</td>
                                            <td>{{ $template->next_run_on ? $template->next_run_on->format('d M Y') : 'Stopped' }}</td>
                                            <td>
                                                <span class="badge {{ $template->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($template->status) }}
                                                </span>
                                                @if($template->approval_required)
                                                    <div><small class="text-muted">Approval required</small></div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <form action="{{ route('finance.recurring.run', $template->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary">Run Now</button>
                                                    </form>
                                                    <form action="{{ route('finance.recurring.toggle', $template->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            {{ $template->status === 'active' ? 'Pause' : 'Resume' }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No recurring templates yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $templates->links() }}
                        </div>
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
        const typeSelect = document.getElementById('recurring_source_type');
        const expenseBlock = document.querySelector('.recurring-source-expense');
        const purchaseBlock = document.querySelector('.recurring-source-purchase');
        const expenseSelect = expenseBlock ? expenseBlock.querySelector('select[name="source_id"]') : null;
        const purchaseSelect = purchaseBlock ? purchaseBlock.querySelector('.recurring-purchase-select') : null;

        function syncSourceBlocks() {
            const type = typeSelect ? typeSelect.value : 'expense';
            if (expenseBlock) expenseBlock.classList.toggle('d-none', type !== 'expense');
            if (purchaseBlock) purchaseBlock.classList.toggle('d-none', type !== 'purchase');

            if (expenseSelect && purchaseSelect) {
                if (type === 'purchase') {
                    expenseSelect.disabled = true;
                    purchaseSelect.disabled = false;
                    expenseSelect.removeAttribute('name');
                    purchaseSelect.setAttribute('name', 'source_id');
                } else {
                    purchaseSelect.disabled = true;
                    expenseSelect.disabled = false;
                    purchaseSelect.removeAttribute('name');
                    expenseSelect.setAttribute('name', 'source_id');
                }
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', syncSourceBlocks);
            syncSourceBlocks();
        }
    })();
</script>
@endpush
