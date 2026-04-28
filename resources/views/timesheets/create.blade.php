@extends('layout.mainlayout')

@section('title', 'Create Timesheet')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Create Timesheet</h3></div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('timesheets.store') }}" class="row g-3">
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
                <div class="col-md-3">
                    <label class="form-label">Week Start Date</label>
                    <input type="date" name="week_start_date" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control">
                </div>
                @for($i = 0; $i < 3; $i++)
                    <div class="col-md-2">
                        <label class="form-label">Entry Date</label>
                        <input type="date" name="entries[{{ $i }}][entry_date]" class="form-control" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Activity</label>
                        <input type="text" name="entries[{{ $i }}][activity_description]" class="form-control" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hours</label>
                        <input type="number" step="0.25" min="0.1" name="entries[{{ $i }}][hours]" class="form-control" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rate</label>
                        <input type="number" step="0.01" min="0" name="entries[{{ $i }}][hourly_rate]" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="entries[{{ $i }}][is_billable]" value="1" {{ $i === 0 ? 'checked' : '' }}>
                            <label class="form-check-label">Billable</label>
                        </div>
                    </div>
                @endfor
                <div class="col-12">
                    <button class="btn btn-primary">Save Timesheet</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
