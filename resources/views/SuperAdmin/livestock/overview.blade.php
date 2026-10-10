@extends('layout.mainlayout')

@section('title', 'Livestock Management')

@section('content')
<style>
    .livestock-admin { padding: 20px; color: #10213f; }
    .livestock-admin .page-band { background: #082c57; color: #fff; border-left: 6px solid #ef4444; padding: 20px 22px; border-radius: 6px; }
    .livestock-admin .page-band h2 { color: #fff; margin: 0 0 5px; font-size: 1.55rem; }
    .livestock-admin .page-band p { margin: 0; color: #dbeafe; }
    .livestock-admin .filter-bar { background: #fff; border: 1px solid #d7e2ef; padding: 14px; margin: 16px 0; }
    .livestock-admin .metric-card { height: 100%; border: 1px solid #d7e2ef; border-top: 4px solid #1264b0; border-radius: 6px; background: #fff; padding: 15px; }
    .livestock-admin .metric-card.green { border-top-color: #07875f; }
    .livestock-admin .metric-card.red { border-top-color: #dc2626; }
    .livestock-admin .metric-card.gold { border-top-color: #d49300; }
    .livestock-admin .metric-label { color: #617087; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
    .livestock-admin .metric-value { color: #071d42; font-size: 1.42rem; font-weight: 800; margin-top: 4px; }
    .livestock-admin .data-panel { background: #fff; border: 1px solid #d7e2ef; margin-top: 18px; }
    .livestock-admin .data-panel header { padding: 14px 16px; border-bottom: 1px solid #d7e2ef; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .livestock-admin .data-panel h3 { margin: 0; font-size: 1rem; color: #082c57; }
    .livestock-admin .table { margin-bottom: 0; }
    .livestock-admin .table th { background: #edf5ff; color: #17345d; font-size: .75rem; text-transform: uppercase; white-space: nowrap; }
    .livestock-admin .empty-state { color: #66758b; padding: 28px; text-align: center; }
    @media (max-width: 767.98px) { .livestock-admin { padding: 12px; } .livestock-admin .page-band { padding: 17px; } }
</style>

<div class="livestock-admin">
    <section class="page-band">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2><i class="fas fa-cow me-2"></i>Livestock Management</h2>
                <p>Read-only platform oversight for licensed layer-farm tenants, production, cost and return performance.</p>
            </div>
            <span class="badge bg-light text-primary px-3 py-2">Tenant scoped</span>
        </div>
    </section>

    <form method="GET" action="{{ route('super_admin.livestock.index') }}" class="filter-bar d-flex flex-wrap align-items-end gap-3">
        <div class="flex-grow-1" style="min-width: 240px; max-width: 480px;">
            <label for="company_id" class="form-label fw-bold">Business workspace</label>
            <select id="company_id" name="company_id" class="form-select" onchange="this.form.submit()">
                <option value="">All livestock tenants</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected($selectedCompanyId === (int) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="text-muted small">Current-month production and operating results: {{ \Illuminate\Support\Carbon::parse($from)->format('d M') }} - {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</div>
    </form>

    <div class="row g-3">
        @foreach([
            ['Livestock tenants', number_format($metrics['tenants']), ''],
            ['Active farms', number_format($metrics['farms']), 'green'],
            ['Active flocks', number_format($metrics['active_flocks']), ''],
            ['Current birds', number_format($metrics['birds']), 'gold'],
            ['Good eggs this month', number_format($metrics['eggs']), 'green'],
            ['Feed consumed (kg)', number_format($metrics['feed_kg'], 2), 'gold'],
            ['Mortality this month', number_format($metrics['mortality']), 'red'],
            ['Operating return', '₦'.number_format($metrics['operating_return'], 2), $metrics['operating_return'] >= 0 ? 'green' : 'red'],
        ] as [$label, $value, $tone])
            <div class="col-6 col-lg-3">
                <div class="metric-card {{ $tone }}">
                    <div class="metric-label">{{ $label }}</div>
                    <div class="metric-value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mt-0">
        <div class="col-md-4"><div class="metric-card"><div class="metric-label">Capital expenditure</div><div class="metric-value">₦{{ number_format($metrics['capital'], 2) }}</div></div></div>
        <div class="col-md-4"><div class="metric-card gold"><div class="metric-label">Working capital</div><div class="metric-value">₦{{ number_format($metrics['working_capital'], 2) }}</div></div></div>
        <div class="col-md-4"><div class="metric-card red"><div class="metric-label">OPEX this month</div><div class="metric-value">₦{{ number_format($metrics['opex'], 2) }}</div></div></div>
    </div>

    <section class="data-panel">
        <header><h3>Farm and Flock Register</h3><span class="badge bg-primary">{{ $farmRows->count() }} farms</span></header>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Business</th><th>Farm</th><th>Location</th><th>Capacity</th><th>Active flocks</th><th>Current birds</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($farmRows as $farm)
                        <tr><td>{{ $farm->company_name }}</td><td><strong>{{ $farm->name }}</strong><br><small class="text-muted">{{ $farm->code }}</small></td><td>{{ $farm->location ?: 'Not set' }}</td><td>{{ number_format($farm->bird_capacity) }}</td><td>{{ number_format($farm->active_flocks) }}</td><td>{{ number_format($farm->current_birds) }}</td><td><span class="badge {{ $farm->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $farm->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">No livestock tenant data is available yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-xl-7">
            <section class="data-panel h-100">
                <header><h3>Recent Daily Production</h3></header>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Date</th><th>Farm</th><th>Birds</th><th>Good eggs</th><th>Feed kg</th><th>Hen-day</th></tr></thead>
                        <tbody>
                            @forelse($recentProduction as $row)
                                <tr><td>{{ \Illuminate\Support\Carbon::parse($row->production_date)->format('d M Y') }}</td><td>{{ $row->farm_name }}</td><td>{{ number_format($row->closing_birds) }}</td><td>{{ number_format($row->total_good_eggs) }}</td><td>{{ number_format($row->feed_kg, 2) }}</td><td>{{ number_format($row->hen_day_percent, 2) }}%</td></tr>
                            @empty
                                <tr><td colspan="6" class="empty-state">No production entries found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="data-panel h-100">
                <header><h3>Recent Costs and Revenue</h3></header>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Date</th><th>Type</th><th>Category</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @forelse($recentTransactions as $row)
                                <tr><td>{{ \Illuminate\Support\Carbon::parse($row->record_date)->format('d M') }}</td><td><span class="badge {{ $row->kind === 'Revenue' ? 'bg-success' : 'bg-danger' }}">{{ $row->kind }}</span></td><td>{{ \Illuminate\Support\Str::headline($row->category) }}</td><td class="text-end fw-bold">₦{{ number_format($row->amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="empty-state">No cost or revenue entries found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
