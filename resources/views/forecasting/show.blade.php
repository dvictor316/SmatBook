@extends('layout.app')

@section('title', 'Forecast Details')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">{{ $forecast->name }}</h3>
            </div>
            <div class="col-auto">
                <a href="{{ route('forecasting.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>Type:</strong> {{ ucfirst($forecast->type) }}</div>
                <div class="col-md-3"><strong>Scenario:</strong> {{ ucfirst($forecast->scenario) }}</div>
                <div class="col-md-3"><strong>Frequency:</strong> {{ ucfirst($forecast->frequency) }}</div>
                <div class="col-md-3"><strong>Status:</strong> {{ ucfirst($forecast->status) }}</div>
                <div class="col-md-6"><strong>Period:</strong> {{ $forecast->period_start->format('d M Y') }} - {{ $forecast->period_end->format('d M Y') }}</div>
                <div class="col-md-6"><strong>Total Forecast:</strong> {{ number_format((float) $forecast->total_forecast_amount, 2) }}</div>
                <div class="col-12"><strong>Assumptions:</strong> {{ $forecast->assumptions ?: 'None' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Forecast Items</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Forecast</th>
                            <th>Actual</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecast->items as $item)
                            <tr>
                                <td>{{ $item->period_date?->format('d M Y') ?: '—' }}</td>
                                <td>{{ $item->category ?: '—' }}</td>
                                <td>{{ number_format((float) $item->forecast_amount, 2) }}</td>
                                <td>{{ number_format((float) $item->actual_amount, 2) }}</td>
                                <td>{{ $item->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No forecast items recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
