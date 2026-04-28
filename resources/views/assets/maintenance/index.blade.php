@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Asset Maintenance Logs</h5>
                    <p class="text-muted mb-0">Track servicing, repairs, and next maintenance dates for fixed assets.</p>
                </div>
                <div>
                    <a href="{{ route('assets.maintenance.create') }}" class="btn btn-primary">
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
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Cost</th>
                                <th>Performed By</th>
                                <th>Date</th>
                                <th>Next Due</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->asset->name ?? $log->asset->asset_code ?? '—' }}</td>
                                    <td>{{ str_replace('_', ' ', ucfirst($log->maintenance_type)) }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($log->description, 50) }}</td>
                                    <td>{{ $log->cost ? number_format($log->cost, 2) : '—' }}</td>
                                    <td>{{ $log->performed_by ?? '—' }}</td>
                                    <td>{{ $log->maintenance_date->format('d M Y') }}</td>
                                    <td>{{ $log->next_maintenance_date ? $log->next_maintenance_date->format('d M Y') : '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                            <a href="{{ route('assets.maintenance.show', $log) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="{{ route('assets.maintenance.edit', $log) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <form action="{{ route('assets.maintenance.destroy', $log) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this log?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
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
</div>
@endsection
