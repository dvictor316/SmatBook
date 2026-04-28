@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Departments</h5>
                    <p class="text-muted mb-0">Manage department structure, leadership, and status for the active branch.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('departments.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-1"></i> New Department
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
                                <th>Name</th><th>Code</th><th>Parent</th><th>Head</th><th>Employees</th><th>Status</th><th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dept)
                                <tr>
                                    <td>{{ $dept->name }}</td>
                                    <td>{{ $dept->code ?? '—' }}</td>
                                    <td>{{ $dept->parent->name ?? '—' }}</td>
                                    <td>{{ $dept->head->name ?? '—' }}</td>
                                    <td>{{ $dept->employees_count ?? 0 }}</td>
                                    <td>
                                        <span class="badge bg-{{ $dept->is_active ? 'success' : 'secondary' }}">{{ $dept->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                            <a href="{{ route('departments.edit', $dept) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <form action="{{ route('departments.destroy', $dept) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this department?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">No departments found. <a href="{{ route('departments.create') }}">Create one</a>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($departments->hasPages())
                <div class="card-footer">{{ $departments->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
