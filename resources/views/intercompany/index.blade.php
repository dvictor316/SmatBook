@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Intercompany Transactions</h5>
                    <p class="text-muted mb-0">Track due-to and due-from transactions between related companies.</p>
                </div>
                <div>
                    <a href="{{ route('intercompany.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-1"></i> New Transaction
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Counter-party</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $txn)
                                <tr>
                                    <td>{{ $txn->reference_number ?: 'N/A' }}</td>
                                    <td>{{ str_replace('_', ' ', ucfirst($txn->transaction_type)) }}</td>
                                    <td>{{ $txn->counterpartyCompany?->name ?? 'N/A' }}</td>
                                    <td>{{ number_format((float) $txn->amount, 2) }}</td>
                                    <td>{{ $txn->currency ?? 'NGN' }}</td>
                                    <td>{{ $txn->transaction_date?->format('d M Y') ?: 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ match($txn->status) {
                                            'pending' => 'warning',
                                            'posted' => 'success',
                                            default => 'secondary'
                                        } }}">{{ ucfirst($txn->status ?? 'draft') }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                            @if($txn->status === 'pending')
                                                <form action="{{ route('intercompany.approve', $txn) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success">Approve</button>
                                                </form>
                                                <form action="{{ route('intercompany.destroy', $txn) }}" method="POST" class="d-inline"
                                                    onsubmit="return confirm('Delete this transaction?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Posted</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4 text-muted">No intercompany transactions found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($transactions->hasPages())
                <div class="card-footer">{{ $transactions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
