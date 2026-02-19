@extends('layout.mainlayout')

@section('page-title', 'Subscription History')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <h4 class="mb-3">Subscription History</h4>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Company:</strong> {{ $subscription->company->name ?? 'N/A' }}</div>
                <div class="col-md-4"><strong>Plan:</strong> {{ $subscription->plan_name ?? $subscription->plan ?? 'N/A' }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ $subscription->status ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong>Activity Logs</strong></div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Date</th><th>Description</th></tr></thead>
                <tbody>
                    @forelse($history as $item)
                        <tr>
                            <td>{{ $item->created_at ?? '-' }}</td>
                            <td>{{ $item->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No history found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
