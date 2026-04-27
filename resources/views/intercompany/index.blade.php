@extends('layout.app')

@section('title', 'Intercompany Transactions')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Intercompany Transactions</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Intercompany</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('intercompany.create') }}" class="btn btn-primary btn-sm">
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
                            <th>Reference</th><th>Type</th><th>Counter-party</th><th>Amount</th><th>Currency</th><th>Date</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr>
                                <td>{{ $txn->reference }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($txn->transaction_type)) }}</td>
                                <td>{{ $txn->counterpart_company_id }}</td>
                                <td>{{ number_format($txn->amount, 2) }}</td>
                                <td>{{ $txn->currency ?? 'NGN' }}</td>
                                <td>{{ $txn->transaction_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ match($txn->status) {
                                        'pending' => 'warning', 'matched' => 'success', 'unmatched' => 'danger', default => 'secondary'
                                    } }}">{{ ucfirst($txn->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('intercompany.show', $txn) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($txn->status === 'pending')
                                        <a href="{{ route('intercompany.edit', $txn) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                        <form action="{{ route('intercompany.destroy', $txn) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this transaction?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
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
@endsection
