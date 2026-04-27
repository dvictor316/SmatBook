@extends('layout.app')

@section('title', 'Forecasting')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Forecasting</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Forecasts</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('forecasting.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Forecast
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
                            <th>Name</th><th>Type</th><th>Period Start</th><th>Period End</th><th>Total Forecast</th><th>Status</th><th>Created</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecasts as $forecast)
                            <tr>
                                <td>{{ $forecast->name }}</td>
                                <td>{{ ucfirst($forecast->forecast_type ?? '—') }}</td>
                                <td>{{ $forecast->period_start->format('d M Y') }}</td>
                                <td>{{ $forecast->period_end->format('d M Y') }}</td>
                                <td>{{ number_format($forecast->items->sum('forecasted_amount') ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ match($forecast->status) {
                                        'draft' => 'secondary', 'active' => 'success', 'archived' => 'warning', default => 'secondary'
                                    } }}">{{ ucfirst($forecast->status) }}</span>
                                </td>
                                <td>{{ $forecast->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('forecasting.show', $forecast) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <a href="{{ route('forecasting.edit', $forecast) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('forecasting.destroy', $forecast) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this forecast?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No forecasts found. <a href="{{ route('forecasting.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($forecasts->hasPages())
            <div class="card-footer">{{ $forecasts->links() }}</div>
        @endif
    </div>
</div>
@endsection
