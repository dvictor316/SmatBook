@extends('layout.app')

@section('title', 'Milestone Billing')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Milestone Billing</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Milestones</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('milestones.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Milestone
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
                            <th>Project</th><th>Milestone</th><th>Amount</th><th>Due Date</th><th>Completion %</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($milestones as $ms)
                            <tr>
                                <td>{{ $ms->project->name ?? '—' }}</td>
                                <td>{{ $ms->name }}</td>
                                <td>{{ number_format($ms->billing_amount ?? 0, 2) }}</td>
                                <td>{{ $ms->due_date ? $ms->due_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar" style="width:{{ $ms->percentage ?? 0 }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $ms->percentage ?? 0 }}%</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ match($ms->status) {
                                        'pending' => 'warning', 'in_progress' => 'primary', 'completed' => 'success', 'billed' => 'info', default => 'secondary'
                                    } }}">{{ ucfirst(str_replace('_', ' ', $ms->status)) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('milestones.show', $ms) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if(in_array($ms->status, ['pending', 'in_progress'], true))
                                        <form action="{{ route('milestones.complete', $ms) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary me-1">Complete</button>
                                        </form>
                                    @endif
                                    @if($ms->status === 'completed')
                                        <form action="{{ route('milestones.invoice', $ms) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success me-1">Invoice</button>
                                        </form>
                                    @endif
                                    @if(!$ms->invoice_id)
                                        <form action="{{ route('milestones.destroy', $ms) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this milestone?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No milestones found. <a href="{{ route('milestones.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($milestones->hasPages())
            <div class="card-footer">{{ $milestones->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
