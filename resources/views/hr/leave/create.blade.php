@extends('layout.app')

@section('title', 'Create Leave Request')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Create Leave Request</h3></div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hr.leave.store') }}" class="row g-3">
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
                <div class="col-md-4">
                    <label class="form-label">Leave Type</label>
                    <select name="leave_type_id" class="form-select" required>
                        <option value="">Select leave type</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="4"></textarea></div>
                <div class="col-12"><button class="btn btn-primary">Submit Request</button></div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
