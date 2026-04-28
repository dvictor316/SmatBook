@extends('layout.mainlayout')

@section('title', 'Create Report Schedule')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Create Report Schedule</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('report-schedules.index') }}">Report Schedules</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('report-schedules.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Schedule Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select" required>
                        <option value="">Select report</option>
                        @foreach(['financial_ratios', 'trial_balance', 'profit_and_loss', 'balance_sheet', 'cash_flow'] as $type)
                            <option value="{{ $type }}" @selected(old('report_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select" required>
                        @foreach(['daily', 'weekly', 'monthly', 'quarterly', 'annually'] as $frequency)
                            <option value="{{ $frequency }}" @selected(old('frequency', 'monthly') === $frequency)>{{ ucfirst($frequency) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select" required>
                        @foreach(['pdf', 'excel', 'csv'] as $format)
                            <option value="{{ $format }}" @selected(old('format', 'pdf') === $format)>{{ strtoupper($format) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Time of Day</label>
                    <input type="time" name="time_of_day" class="form-control" value="{{ old('time_of_day') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Recipients</label>
                    <input type="text" name="recipients" class="form-control" value="{{ old('recipients') }}" placeholder="email1@example.com,email2@example.com" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Day of Week</label>
                    <select name="day_of_week" class="form-select">
                        <option value="">Not applicable</option>
                        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index => $label)
                            <option value="{{ $index }}" @selected((string) old('day_of_week') === (string) $index)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Day of Month</label>
                    <input type="number" name="day_of_month" class="form-control" min="1" max="31" value="{{ old('day_of_month') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Parameters</label>
                    <textarea name="parameters" class="form-control" rows="4" placeholder='{"period":"monthly"}'>{{ old('parameters') }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))>
                        <label class="form-check-label" for="is_active">Active schedule</label>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary">Save Schedule</button>
                    <a href="{{ route('report-schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
