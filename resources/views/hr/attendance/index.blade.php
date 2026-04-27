@extends('layout.app')

@section('title', 'Attendance')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Attendance</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Attendance</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('hr.attendance.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> Log Attendance
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
                            <th>Employee</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $record->employee->name ?? '—' }}</td>
                                <td>{{ $record->attendance_date->format('d M Y') }}</td>
                                <td>{{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('H:i') : '—' }}</td>
                                <td>{{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('H:i') : '—' }}</td>
                                <td>{{ $record->hours_worked ? number_format($record->hours_worked, 1) : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($record->status ?? 'present') {
                                        'present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'half_day' => 'info', default => 'secondary'
                                    } }}">{{ ucfirst($record->status ?? 'present') }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('hr.attendance.edit', $record) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('hr.attendance.destroy', $record) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this record?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No attendance records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($records->hasPages())
            <div class="card-footer">{{ $records->links() }}</div>
        @endif
    </div>
</div>
@endsection
