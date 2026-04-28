@extends('layout.app')

@section('title', 'Create Forecast')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Create Forecast</h3>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('forecasting.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        @foreach(['revenue', 'expense', 'cash_flow', 'sales'] as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Scenario</label>
                    <select name="scenario" class="form-select" required>
                        @foreach(['base', 'optimistic', 'pessimistic', 'custom'] as $scenario)
                            <option value="{{ $scenario }}">{{ ucfirst($scenario) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select" required>
                        @foreach(['weekly', 'monthly', 'quarterly', 'annually'] as $frequency)
                            <option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Period Start</label>
                    <input type="date" name="period_start" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Period End</label>
                    <input type="date" name="period_end" class="form-control" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Assumptions</label>
                    <textarea name="assumptions" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <h5 class="mb-3">Forecast Items</h5>
                </div>
                @for($i = 0; $i < 3; $i++)
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <input type="text" name="items[{{ $i }}][category]" class="form-control" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Period Date</label>
                        <input type="date" name="items[{{ $i }}][period_date]" class="form-control" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Forecast Amount</label>
                        <input type="number" step="0.01" min="0" name="items[{{ $i }}][forecast_amount]" class="form-control" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="items[{{ $i }}][notes]" class="form-control">
                    </div>
                @endfor
                <div class="col-12">
                    <button class="btn btn-primary">Save Forecast</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
