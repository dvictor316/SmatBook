@extends('layout.app')

@section('title', 'Timesheet Details')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h3 class="page-title">Timesheet</h3></div>
            <div class="col-auto d-flex gap-2">
                @if($timesheet->status === 'draft')
                    <form method="POST" action="{{ route('timesheets.submit', $timesheet) }}">
                        @csrf
                        <button class="btn btn-primary">Submit</button>
                    </form>
                @endif
                @if($timesheet->status === 'submitted')
                    <form method="POST" action="{{ route('timesheets.approve', $timesheet) }}">
                        @csrf
                        <button class="btn btn-success">Approve</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Employee:</strong> {{ $timesheet->employee->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Week Start:</strong> {{ $timesheet->week_start_date->format('d M Y') }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst($timesheet->status) }}</div>
                <div class="col-md-4"><strong>Total Hours:</strong> {{ number_format((float) $timesheet->total_hours, 2) }}</div>
                <div class="col-md-4"><strong>Billable Hours:</strong> {{ number_format((float) $timesheet->billable_hours, 2) }}</div>
                <div class="col-md-4"><strong>Billable Amount:</strong> {{ number_format((float) $timesheet->billable_amount, 2) }}</div>
                <div class="col-12"><strong>Notes:</strong> {{ $timesheet->notes ?: 'None' }}</div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Entries</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Activity</th><th>Hours</th><th>Billable</th><th>Rate</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach($timesheet->entries as $entry)
                            <tr>
                                <td>{{ $entry->entry_date?->format('d M Y') ?: '—' }}</td>
                                <td>{{ $entry->activity_description }}</td>
                                <td>{{ number_format((float) $entry->hours, 2) }}</td>
                                <td>{{ $entry->is_billable ? 'Yes' : 'No' }}</td>
                                <td>{{ number_format((float) ($entry->hourly_rate ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->line_total ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
