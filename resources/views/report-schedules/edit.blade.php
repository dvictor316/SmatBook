@extends('layout.mainlayout')

@section('title', 'Edit Report Schedule')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Edit Report Schedule</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('report-schedules.index') }}">Report Schedules</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('report-schedules.update', $reportSchedule) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Schedule Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $reportSchedule->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Report Type</label>
                    <input type="text" class="form-control" value="{{ ucwords(str_replace('_', ' ', $reportSchedule->report_type)) }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select" required>
                        @foreach(['daily', 'weekly', 'monthly', 'quarterly', 'annually'] as $frequency)
                            <option value="{{ $frequency }}" @selected(old('frequency', $reportSchedule->frequency) === $frequency)>{{ ucfirst($frequency) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select" required>
                        @foreach(['pdf', 'excel', 'csv'] as $format)
                            <option value="{{ $format }}" @selected(old('format', $reportSchedule->format) === $format)>{{ strtoupper($format) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Active</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected((string) old('is_active', (int) $reportSchedule->is_active) === '1')>Yes</option>
                        <option value="0" @selected((string) old('is_active', (int) $reportSchedule->is_active) === '0')>No</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Recipients</label>
                    <input type="text" name="recipients" class="form-control" value="{{ old('recipients', is_array($reportSchedule->recipients) ? implode(',', $reportSchedule->recipients) : $reportSchedule->recipients) }}" required>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary">Update Schedule</button>
                    <a href="{{ route('report-schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
