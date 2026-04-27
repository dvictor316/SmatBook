@extends('layout.app')

@section('title', 'Purchase Requisitions')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Purchase Requisitions</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Purchase Requisitions</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('purchase-requisitions.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New PR
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
                            <th>PR #</th><th>Title</th><th>Department</th><th>Required By</th><th>Priority</th><th>Status</th><th>Created</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requisitions as $pr)
                            <tr>
                                <td>{{ $pr->pr_number }}</td>
                                <td>{{ $pr->title }}</td>
                                <td>{{ $pr->department ?? '—' }}</td>
                                <td>{{ $pr->required_date ? $pr->required_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($pr->priority) {
                                        'low' => 'secondary', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger', default => 'secondary'
                                    } }}">{{ ucfirst($pr->priority) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ match($pr->status) {
                                        'draft' => 'secondary', 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'converted' => 'info', default => 'secondary'
                                    } }}">{{ ucfirst($pr->status) }}</span>
                                </td>
                                <td>{{ $pr->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('purchase-requisitions.show', $pr) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($pr->status === 'draft')
                                        <a href="{{ route('purchase-requisitions.edit', $pr) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                        <form action="{{ route('purchase-requisitions.destroy', $pr) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this PR?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No requisitions found. <a href="{{ route('purchase-requisitions.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($requisitions->hasPages())
            <div class="card-footer">{{ $requisitions->links() }}</div>
        @endif
    </div>
</div>
@endsection
