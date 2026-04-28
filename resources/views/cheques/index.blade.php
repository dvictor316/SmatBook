@extends('layout.app')

@section('title', 'Cheque Register')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Cheque Register</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Cheques</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('cheques.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Cheque
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
                            <th>Cheque #</th><th>Type</th><th>Amount</th><th>Party</th><th>Bank</th><th>Date</th><th>Due Date</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $cheque)
                            <tr>
                                <td>{{ $cheque->cheque_number }}</td>
                                <td><span class="badge bg-{{ $cheque->type === 'receive' ? 'success' : 'primary' }}">{{ ucfirst($cheque->type) }}</span></td>
                                <td>{{ number_format($cheque->amount, 2) }} {{ $cheque->currency ?? 'NGN' }}</td>
                                <td>{{ $cheque->payee_name }}</td>
                                <td>{{ $cheque->bank?->name ?? '—' }}</td>
                                <td>{{ $cheque->cheque_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $cheque->due_date ? $cheque->due_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($cheque->status) {
                                        'pending' => 'warning', 'cleared' => 'success', 'bounced' => 'danger', 'cancelled', 'voided' => 'secondary', 'deposited' => 'info', default => 'secondary'
                                    } }}">{{ ucfirst($cheque->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('cheques.show', $cheque) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <a href="{{ route('cheques.edit', $cheque) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('cheques.destroy', $cheque) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this cheque?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4 text-muted">No cheques found. <a href="{{ route('cheques.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($cheques->hasPages())
            <div class="card-footer">{{ $cheques->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
