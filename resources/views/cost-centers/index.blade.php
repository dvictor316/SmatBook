@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Cost Centers</h5>
                    <p class="text-muted mb-0">Manage cost center structure and optional department links for the active branch.</p>
                </div>
                <div>
                    <a href="{{ route('cost-centers.create') }}" class="btn btn-primary">
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
                                <th>Name</th><th>Code</th><th>Type</th><th>Department</th><th>Description</th><th>Status</th><th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costCenters as $cc)
                                <tr>
                                    <td>{{ $cc->name }}</td>
                                    <td>{{ $cc->code ?? '—' }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $cc->type ?? 'cost')) }}</td>
                                    <td>{{ $cc->department->name ?? '—' }}</td>
                                    <td>{{ $cc->description ? \Illuminate\Support\Str::limit($cc->description, 40) : '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $cc->is_active ? 'success' : 'secondary' }}">{{ $cc->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                            <a href="{{ route('cost-centers.edit', $cc) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <form action="{{ route('cost-centers.destroy', $cc) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this cost center?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
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
</div>
@endsection
