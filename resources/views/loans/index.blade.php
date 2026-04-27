@extends('layout.app')

@section('title', 'Loans & Overdraft')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Loans &amp; Overdraft</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Loans</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('loans.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Loan
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
                            <th>Reference</th><th>Type</th><th>Principal</th><th>Outstanding</th><th>Rate %</th><th>Lender/Bank</th><th>Start Date</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loans as $loan)
                            <tr>
                                <td>{{ $loan->reference }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($loan->loan_type)) }}</td>
                                <td>{{ number_format($loan->principal_amount, 2) }}</td>
                                <td>{{ number_format($loan->outstanding_balance, 2) }}</td>
                                <td>{{ $loan->interest_rate }}%</td>
                                <td>{{ $loan->lender_name }}</td>
                                <td>{{ $loan->start_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ match($loan->status) {
                                        'active' => 'success', 'fully_paid' => 'secondary', 'defaulted' => 'danger', default => 'warning'
                                    } }}">{{ ucfirst($loan->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <a href="{{ route('loans.edit', $loan) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this loan?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4 text-muted">No loans found. <a href="{{ route('loans.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($loans->hasPages())
            <div class="card-footer">{{ $loans->links() }}</div>
        @endif
    </div>
</div>
@endsection
