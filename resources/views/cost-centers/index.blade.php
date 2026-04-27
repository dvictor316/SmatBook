@extends('layout.app')

@section('title', 'Cost Centers')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Cost Centers</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Cost Centers</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('cost-centers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Cost Center
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
                            <th>Name</th><th>Code</th><th>Type</th><th>Department</th><th>Budget</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($costCenters as $cc)
                            <tr>
                                <td>{{ $cc->name }}</td>
                                <td>{{ $cc->code ?? '—' }}</td>
                                <td>{{ ucfirst($cc->type ?? 'cost') }}</td>
                                <td>{{ $cc->department->name ?? '—' }}</td>
                                <td>{{ $cc->budget ? number_format($cc->budget, 2) : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $cc->is_active ? 'success' : 'secondary' }}">{{ $cc->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('cost-centers.edit', $cc) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('cost-centers.destroy', $cc) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this cost center?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No cost centers found. <a href="{{ route('cost-centers.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($costCenters->hasPages())
            <div class="card-footer">{{ $costCenters->links() }}</div>
        @endif
    </div>
</div>
@endsection
