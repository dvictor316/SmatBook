<?php $page = 'expenses'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    .expense-chip {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cbd5e1;
    }
    .expense-status-paid {
        background: #dcfce7;
        color: #166534;
    }
    .expense-status-pending {
        background: #fef9c3;
        color: #854d0e;
    }
    .expense-status-overdue {
        background: #fee2e2;
        color: #991b1b;
    }
</style>
<div class="page-wrapper">
    <div class="content container-fluid">

        @component('components.page-header')
            @slot('title')
                Expenses & Ledger Posting
            @endslot
        @endcomponent
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#add_expenses">
                <i class="fas fa-plus-circle me-1"></i> Add Expense
            </button>
            <div class="text-muted small">
                <i class="fas fa-info-circle"></i> Entries marked as "Paid" automatically update your Trial Balance.
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Expense ID</th>
                                        <th>Vendor/Company</th>
                                        <th>Amount</th>
                                        <th>Payment Source</th>
                                        <th>Category (Debit)</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($expenses as $expense)
                                        <tr>
                                            <td>{{ $expense->created_at->format('d M Y') }}</td>
                                            <td><span class="text-dark fw-bold">{{ $expense->expense_id }}</span></td>
                                            <td>
                                                <div class="fw-bold">{{ $expense->company_name }}</div>
                                                <small class="text-muted">{{ $expense->reference ?? 'No Reference' }}</small>
                                            </td>
                                            <td>
                                                <strong class="text-danger">₦{{ number_format((float) $expense->amount, 2) }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-outline-secondary">
                                                    <i class="fas fa-university me-1"></i> {{ $expense->payment_mode }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge expense-chip">
                                                    {{ $expense->category }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $statusClass = [
                                                        'Paid' => 'expense-status-paid',
                                                        'Pending' => 'expense-status-pending',
                                                        'Overdue' => 'expense-status-overdue'
                                                    ][$expense->status] ?? 'expense-chip';
                                                @endphp
                                                <span class="badge {{ $statusClass }}">
                                                    {{ $expense->status }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-v"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_expense_{{ $expense->id }}">
                                                            <i class="far fa-eye me-2 text-info"></i>View
                                                        </a>
                                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_expense_{{ $expense->id }}">
                                                            <i class="far fa-edit me-2 text-primary"></i>Edit
                                                        </a>
                                                        <span class="dropdown-item text-muted"><i class="far fa-user me-2"></i>{{ $expense->creator?->name ?? 'System' }}</span>
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this record?')">
                                                                <i class="far fa-trash-alt me-2"></i>Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No expenses yet. Add your first expense above.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            
                            <div class="d-flex justify-content-center mt-4">
                                {{ $expenses->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add_expenses" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Record New Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vendor / Company *</label>
                            <input type="text" class="form-control" name="company_name" list="expense-party-list" placeholder="Who are you paying?" required>
                            <datalist id="expense-party-list">
                                @foreach(($partyOptions ?? []) as $party)
                                    <option value="{{ $party }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" class="form-control" name="amount" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark mb-0">Expense Category (Debit Account) *</label>
                                <a href="javascript:void(0);" class="small fw-semibold" data-bs-toggle="modal" data-bs-target="#quick_add_category_modal">
                                    <i class="fas fa-plus-circle me-1"></i>Add Category
                                </a>
                            </div>
                            <select class="form-select border-primary" name="account_id" required>
                                <option value="">-- Choose Category --</option>
                                @if(($categories ?? collect())->isNotEmpty())
                                    <optgroup label="Expense Categories">
                                        @foreach($categories as $cat)
                                            <option value="cat:{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                <optgroup label="Chart of Accounts (Expense)">
                                    @foreach($expenseAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->code }})</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <small class="text-muted">This defines where the money is spent.</small>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark mb-0">Paid From (Credit Account) *</label>
                                <a href="javascript:void(0);" class="small fw-semibold" data-bs-toggle="modal" data-bs-target="#quick_add_bank_modal">
                                    <i class="fas fa-plus-circle me-1"></i>Add Bank/Cash
                                </a>
                            </div>
                            <select class="form-select border-success" name="payment_account_id" required>
                                <option value="">-- Choose Bank/Cash --</option>
                                @foreach($assetAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->code }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">This defines where the money is coming from.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reference #</label>
                            <input type="text" class="form-control" name="reference" placeholder="Ref/Inv Number">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="Paid">Paid (Update Ledger)</option>
                                <option value="Pending">Pending (Draft)</option>
                                <option value="Overdue">Overdue</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Receipt Attachment</label>
                            <input class="form-control" type="file" name="image" accept="image/*">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Description / Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Briefly describe the purpose of this expense..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">Post Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quick_add_bank_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Bank / Cash Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('expenses.quick-add-bank') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. GTBank Current" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Account Number</label>
                        <input type="text" name="account_number" class="form-control" placeholder="Optional">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Opening Balance</label>
                        <input type="number" step="0.01" min="0" name="balance" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Bank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quick_add_category_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Expense Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('expenses.quick-add-category') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label fw-bold">Category Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Internet & Data" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($expenses as $expense)
<div class="modal fade" id="view_expense_{{ $expense->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="far fa-file-alt me-2"></i>Expense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-6"><small class="text-muted">Expense ID</small><div class="fw-semibold">{{ $expense->expense_id }}</div></div>
                    <div class="col-6"><small class="text-muted">Date</small><div class="fw-semibold">{{ optional($expense->created_at)->format('d M Y h:i A') }}</div></div>
                    <div class="col-12"><small class="text-muted">Vendor / Company</small><div class="fw-semibold">{{ $expense->company_name }}</div></div>
                    <div class="col-6"><small class="text-muted">Amount</small><div class="fw-semibold">₦{{ number_format((float) $expense->amount, 2) }}</div></div>
                    <div class="col-6"><small class="text-muted">Status</small><div class="fw-semibold">{{ $expense->status }}</div></div>
                    <div class="col-6"><small class="text-muted">Category</small><div class="fw-semibold">{{ $expense->category ?? 'N/A' }}</div></div>
                    <div class="col-6"><small class="text-muted">Paid From</small><div class="fw-semibold">{{ $expense->payment_mode ?? 'N/A' }}</div></div>
                    <div class="col-12"><small class="text-muted">Reference</small><div class="fw-semibold">{{ $expense->reference ?? 'N/A' }}</div></div>
                    <div class="col-12"><small class="text-muted">Notes</small><div class="fw-semibold">{{ $expense->notes ?? 'N/A' }}</div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_expense_{{ $expense->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('expenses.update', $expense->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vendor / Company *</label>
                            <input type="text" class="form-control" name="company_name" value="{{ $expense->company_name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" class="form-control" name="amount" step="0.01" value="{{ (float) $expense->amount }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark mb-0">Expense Category (Debit Account) *</label>
                                <a href="javascript:void(0);" class="small fw-semibold" data-bs-toggle="modal" data-bs-target="#quick_add_category_modal">
                                    <i class="fas fa-plus-circle me-1"></i>Add Category
                                </a>
                            </div>
                            <select class="form-select border-primary" name="account_id" required>
                                <option value="">-- Choose Category --</option>
                                @if(($categories ?? collect())->isNotEmpty())
                                    <optgroup label="Expense Categories">
                                        @foreach($categories as $cat)
                                            <option value="cat:{{ $cat->id }}" {{ (int) ($expense->category_id ?? 0) === (int) $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                <optgroup label="Chart of Accounts (Expense)">
                                    @foreach($expenseAccounts as $acc)
                                        <option value="{{ $acc->id }}" {{ strtolower((string) $expense->category) === strtolower((string) $acc->name) ? 'selected' : '' }}>
                                            {{ $acc->name }} ({{ $acc->code }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark mb-0">Paid From (Credit Account) *</label>
                                <a href="javascript:void(0);" class="small fw-semibold" data-bs-toggle="modal" data-bs-target="#quick_add_bank_modal">
                                    <i class="fas fa-plus-circle me-1"></i>Add Bank/Cash
                                </a>
                            </div>
                            <select class="form-select border-success" name="payment_account_id" required>
                                <option value="">-- Choose Bank/Cash --</option>
                                @foreach($assetAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ strtolower((string) $expense->payment_mode) === strtolower((string) $acc->name) ? 'selected' : '' }}>
                                        {{ $acc->name }} ({{ $acc->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reference #</label>
                            <input type="text" class="form-control" name="reference" value="{{ $expense->reference }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="Paid" {{ $expense->status === 'Paid' ? 'selected' : '' }}>Paid</option>
                                <option value="Pending" {{ $expense->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Overdue" {{ $expense->status === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Receipt Attachment</label>
                            <input class="form-control" type="file" name="image" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description / Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ $expense->notes }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Update Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection
