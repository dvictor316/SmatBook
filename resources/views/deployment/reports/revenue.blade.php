@extends('layout.mainlayout')

@section('page-title', 'Revenue Report')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Revenue Report</h4>
        <div class="d-flex gap-2">
            <a href="#" class="download-item btn btn-sm btn-outline-primary">Download CSV</a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fas fa-print me-1"></i>Print</button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Paid Subscriptions</div><div class="h3 mb-0">{{ $paidCount ?? 0 }}</div></div></div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total Revenue</div><div class="h3 mb-0">₦{{ number_format((float)($totalRevenue ?? 0), 2) }}</div></div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Month</th><th class="text-end">Revenue</th></tr></thead>
                <tbody>
                @forelse(($rows ?? collect()) as $row)
                    <tr><td>{{ $row->month }}</td><td class="text-end">₦{{ number_format((float)$row->total, 2) }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">No paid revenue records yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
