@extends('layout.app')

@section('title', 'Departments')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Departments</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Departments</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm">
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
                            <th>Name</th><th>Code</th><th>Parent</th><th>Manager</th><th>Budget</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                            <tr>
                                <td>{{ $dept->name }}</td>
                                <td>{{ $dept->code ?? '—' }}</td>
                                <td>{{ $dept->parent->name ?? '—' }}</td>
                                <td>{{ $dept->manager_name ?? '—' }}</td>
                                <td>{{ $dept->budget ? number_format($dept->budget, 2) : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $dept->is_active ? 'success' : 'secondary' }}">{{ $dept->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('departments.edit', $dept) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('departments.destroy', $dept) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this department?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
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
@endsection
