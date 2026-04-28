@extends('layout.app')

@section('title', 'Attendance Report')

@section('content')
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Attendance Report</h3></div>
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('hr.attendance.report') }}" class="row g-3">
                <div class="col-md-4"><label class="form-label">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary">Run Report</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Employee</th><th>Days Logged</th><th>Hours Worked</th></tr></thead>
                    <tbody>
                        @forelse($records as $employeeRecords)
                            @php($first = $employeeRecords->first())
                            <tr>
                                <td>{{ $first?->employee->name ?? '—' }}</td>
                                <td>{{ $employeeRecords->count() }}</td>
                                <td>{{ number_format((float) $employeeRecords->sum('hours_worked'), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No attendance data found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
