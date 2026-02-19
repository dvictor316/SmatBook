@extends('layout.mainlayout')

@section('page-title', 'Performance Report')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Performance Report</h4>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fas fa-print me-1"></i>Print</button>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total Deployed</div><div class="h3 mb-0">{{ $report['totalDeployed'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Active Now</div><div class="h3 mb-0">{{ $report['activeNow'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Pending</div><div class="h3 mb-0">{{ $report['pendingNow'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Suspended</div><div class="h3 mb-0">{{ $report['suspendedNow'] ?? 0 }}</div></div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Latest Companies</h6>
            <a href="#" class="download-item btn btn-sm btn-outline-primary">Download CSV</a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Company</th><th>Plan</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                @forelse(($recentCompanies ?? collect()) as $company)
                    <tr>
                        <td>{{ $company->name }}</td>
                        <td>{{ $company->subscription->plan_name ?? $company->subscription->plan ?? $company->plan ?? 'Basic' }}</td>
                        <td>{{ ucfirst($company->status ?? 'n/a') }}</td>
                        <td>{{ optional($company->created_at)->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">No records.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
