@extends('layout.app')

@section('title', 'Timesheets')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Timesheets</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Timesheets</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('timesheets.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Timesheet
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
                            <th>Employee</th><th>Period</th><th>Total Hours</th><th>Billable Hours</th><th>Status</th><th>Submitted</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($timesheets as $ts)
                            <tr>
                                <td>{{ $ts->employee->name ?? '—' }}</td>
                                <td>{{ $ts->week_start_date->format('d M Y') }} – {{ $ts->week_start_date->copy()->addDays(6)->format('d M Y') }}</td>
                                <td>{{ number_format($ts->total_hours ?? 0, 1) }}</td>
                                <td>{{ number_format($ts->billable_hours ?? 0, 1) }}</td>
                                <td>
                                    <span class="badge bg-{{ match($ts->status) {
                                        'draft' => 'secondary', 'submitted' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'secondary'
                                    } }}">{{ ucfirst($ts->status) }}</span>
                                </td>
                                <td>{{ $ts->approved_at ? $ts->approved_at->format('d M Y') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('timesheets.show', $ts) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($ts->status === 'draft')
                                        <form action="{{ route('timesheets.destroy', $ts) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this timesheet?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No timesheets found. <a href="{{ route('timesheets.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($timesheets->hasPages())
            <div class="card-footer">{{ $timesheets->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
