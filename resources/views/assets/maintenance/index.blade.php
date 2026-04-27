@extends('layout.app')

@section('title', 'Asset Maintenance Logs')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Asset Maintenance Logs</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Maintenance</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('assets.maintenance.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> Log Maintenance
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
                            <th>Asset</th><th>Type</th><th>Description</th><th>Cost</th><th>Performed By</th><th>Date</th><th>Next Due</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->asset_name }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($log->maintenance_type)) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($log->description, 50) }}</td>
                                <td>{{ $log->cost ? number_format($log->cost, 2) : '—' }}</td>
                                <td>{{ $log->performed_by ?? '—' }}</td>
                                <td>{{ $log->maintenance_date->format('d M Y') }}</td>
                                <td>{{ $log->next_maintenance_date ? $log->next_maintenance_date->format('d M Y') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('assets.maintenance.edit', $log) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('assets.maintenance.destroy', $log) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this log?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No maintenance logs found. <a href="{{ route('assets.maintenance.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
