@extends('layout.mainlayout')

@section('page-title', 'Client Activity Report')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Client Activity Report</h4>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fas fa-print me-1"></i>Print</button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Action</th><th>Module</th><th>Date</th></tr></thead>
                <tbody>
                @forelse(($activities ?? collect()) as $activity)
                    <tr>
                        <td>{{ $activity->description ?? 'Activity' }}</td>
                        <td>{{ ucfirst($activity->module ?? 'general') }}</td>
                        <td>{{ optional($activity->created_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No activity logs found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($activities) && method_exists($activities, 'links'))
            <div class="card-footer bg-white">{{ $activities->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
