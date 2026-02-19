@extends('layout.mainlayout')

@section('content') @php $page = 'purchase-transaction'; @endphp

<style> /* Professional Design System */ .page-wrapper { background-color: #ffffff !important; min-height: 100vh; color: #1a1a1a; }

/* Clean Card Architecture */
.card {
    background-color: #ffffff !important;
    border: 1px solid #edf2f7 !important;
    border-radius: 10px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    margin-bottom: 1.5rem;
}

/* Table &amp; Headers */
.thead-light th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 1rem !important;
    border-bottom: 2px solid #e2e8f0 !important;
}

.table td {
    padding: 1rem !important;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}

/* Interactive Elements */
.btn-primary {
    background-color: #ff9b44 !important;
    border-color: #ff9b44 !important;
    font-weight: 600;
}

.btn-filters {
    background: #ffffff;
    border: 1px solid #d1d3e2;
    color: #334155;
}

/* High-Contrast Badges */
.badge { font-weight: 600; border-radius: 6px; }
.bg-light-success { background-color: #dcfce7 !important; color: #166534 !important; }
.bg-light-warning { background-color: #fef9c3 !important; color: #854d0e !important; }
.bg-light-danger { background-color: #fee2e2 !important; color: #991b1b !important; }

@media print {
    .btn, .filter-list, .dropdown-action, .page-header .list-btn { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .page-wrapper { padding: 0 !important; }
}
</style>

<div class="page-wrapper"> <div class="content container-fluid">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="page-header">
        <div class="content-page-header">
            <h5 class="fw-bold">{{ __('Purchase Transactions') }}</h5>
            <div class="list-btn">
                <ul class="filter-list">
                    <li>
                        <a class="btn btn-filters w-auto" id="filter_search">
                            <i class="feather-search me-1"></i> {{ __('Search') }}
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary w-auto" href="{{ route('purchase-transaction') }}">
                            <i class="feather-refresh-ccw me-1"></i> {{ __('Reset') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div id="filter_inputs" class="card filter-card" style="{{ request('search') || request('start_date') ? '' : 'display: none;' }}">
        <div class="card-body">
            <form action="{{ route('purchase-transaction') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="mb-2 text-dark fw-semibold">Reference / Purchase No</label>
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="mb-2 text-dark fw-semibold">From Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="mb-2 text-dark fw-semibold">To Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-center table-hover mb-0" id="transaction-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Purchase #</th>
                                    <th>Created On</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($purchasereports)
                                    @forelse ($purchasereports as $transaction)
                                        <tr>
                                            <td>#{{ $transaction->id }}</td> 
                                            <td class="fw-bold text-primary">{{ $transaction->purchase_no ?? $transaction->payment_reference }}</td>
                                            <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y') }}</td> 
                                            <td class="fw-bold text-dark">₦{{ number_format($transaction->amount ?? $transaction->total_amount, 2) }}</td>
                                            <td>
                                                @php
                                                    $status = strtolower($transaction->status ?? $transaction->payment_status);
                                                    $badge = match($status) {
                                                        'paid', 'active' => 'bg-light-success text-success',
                                                        'pending', 'awaiting payment' => 'bg-light-warning text-warning',
                                                        'cancelled', 'unpaid', 'expired' => 'bg-light-danger text-danger',
                                                        default => 'bg-light-info text-info',
                                                    };
                                                @endphp
                                                <span class="badge {{ $badge }} px-3 py-2">{{ ucfirst($status) }}</span>
                                            </td>
                                            <td class="text-end">
                                                @if(($transaction->source_type ?? '') === 'inventory_history')
                                                    <span class="badge bg-light text-muted border">Read-only</span>
                                                @else
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <a class="dropdown-item" href="{{ route('super_admin.subscriptions.show', $transaction->id) }}"><i class="far fa-eye me-2"></i>View Details</a>
                                                            <a class="dropdown-item" href="{{ route('super_admin.subscriptions.edit', $transaction->id) }}"><i class="far fa-edit me-2"></i>Update Status</a>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal_{{ $transaction->id }}"><i class="far fa-trash-alt me-2"></i>Delete Record</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Delete Modal --}}
                                        @if(($transaction->source_type ?? '') !== 'inventory_history')
                                        <div class="modal fade" id="delete_modal_{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0">
                                                    <form action="{{ route('purchases.destroy', $transaction->id) }}" method="POST">
                                                        @csrf @method('DELETE')
                                                        <div class="modal-body text-center p-5">
                                                            <i class="fas fa-exclamation-triangle fa-4x text-danger mb-4"></i>
                                                            <h4 class="fw-bold">Delete Transaction?</h4>
                                                            <p class="text-muted">You are about to delete <strong>{{ $transaction->purchase_no }}</strong>. This action is irreversible.</p>
                                                            <div class="mt-4">
                                                                <button type="button" class="btn btn-light me-2 px-4" data-bs-dismiss="modal">Keep Record</button>
                                                                <button type="submit" class="btn btn-danger px-4">Confirm Delete</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted p-5">
                                                <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                                No Transactions Found
                                            </td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center text-danger p-5">
                                            <i class="fas fa-bug fa-2x mb-2"></i><br>
                                            <strong>Error:</strong> Variable <code>$purchasereports</code> is not defined in the controller.
                                        </td>
                                    </tr>
                                @endisset
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Pagination --}}
            @isset($purchasereports)
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">Total Transactions: {{ $purchasereports->total() }}</div>
                    <div>
                        {{ $purchasereports->appends(request()->query())->links() }}
                    </div>
                </div>
            @endisset
        </div>
    </div>
</div>
