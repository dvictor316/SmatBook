@extends('layout.app')

@section('title', 'Attendance')

@section('content')
<div class="page-wrapper">
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
                <a href="{{ route('hr.attendance.report') }}" class="btn btn-outline-secondary btn-sm me-1">
                    <i class="fe fe-bar-chart me-1"></i> Attendance Report
                </a>
                <a href="#attendance-form" class="btn btn-primary btn-sm">
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

    <div class="card mb-4" id="attendance-form">
        <div class="card-header"><h5 class="card-title mb-0">Record Attendance</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('hr.attendance.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="attendance_date" value="{{ $date }}" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Check In</label>
                    <input type="time" name="check_in_time" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Check Out</label>
                    <input type="time" name="check_out_time" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach(['present', 'absent', 'late', 'half_day', 'on_leave', 'holiday', 'remote'] as $status)
                            <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Method</label>
                    <select name="check_in_method" class="form-select">
                        <option value="">Select method</option>
                        @foreach(['manual', 'biometric', 'mobile', 'web'] as $method)
                            <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>

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
                                <td>{{ $record->check_in_time ? \Carbon\Carbon::parse($record->check_in_time)->format('H:i') : '—' }}</td>
                                <td>{{ $record->check_out_time ? \Carbon\Carbon::parse($record->check_out_time)->format('H:i') : '—' }}</td>
                                <td>{{ $record->hours_worked ? number_format($record->hours_worked, 1) : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($record->status ?? 'present') {
                                        'present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'half_day' => 'info', 'on_leave' => 'secondary', 'holiday' => 'primary', 'remote' => 'dark', default => 'secondary'
                                    } }}">{{ ucfirst(str_replace('_', ' ', $record->status ?? 'present')) }}</span>
                                </td>
                                <td class="text-end text-muted">{{ ucfirst($record->check_in_method ?? 'manual') }}</td>
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
</div>
@endsection
