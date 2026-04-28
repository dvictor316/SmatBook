@extends('layout.mainlayout')

@section('title', 'Report Schedules')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Report Schedules</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Report Schedules</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('report-schedules.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Schedule
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
                            <th>Report Type</th><th>Frequency</th><th>Format</th><th>Recipients</th><th>Last Run</th><th>Next Run</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $schedule)
                            <tr>
                                <td>{{ str_replace('_', ' ', ucfirst($schedule->report_type)) }}</td>
                                <td>{{ ucfirst($schedule->frequency) }}</td>
                                <td>{{ strtoupper($schedule->format ?? 'pdf') }}</td>
                                <td>
                                    @if($schedule->recipients)
                                        @foreach(array_slice((array)$schedule->recipients, 0, 2) as $r)
                                            <span class="badge bg-light text-dark">{{ $r }}</span>
                                        @endforeach
                                        @if(count((array)$schedule->recipients) > 2)
                                            <span class="text-muted">+{{ count((array)$schedule->recipients) - 2 }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $schedule->last_run_at ? $schedule->last_run_at->format('d M Y H:i') : '—' }}</td>
                                <td>{{ $schedule->next_run_at ? $schedule->next_run_at->format('d M Y H:i') : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $schedule->is_active ? 'success' : 'secondary' }}">
                                        {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('report-schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('report-schedules.destroy', $schedule) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this schedule?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No report schedules found. <a href="{{ route('report-schedules.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($schedules->hasPages())
            <div class="card-footer">{{ $schedules->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
