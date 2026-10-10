@extends('layout.mainlayout')

@section('title', 'Livestock Management')

@section('content')
<style>
    .livestock-admin-page { min-width: 0; overflow-x: clip; }
    .livestock-admin { color: #10213f; min-width: 0; }
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
    .livestock-admin .action-strip { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; margin: 0 0 16px; }
    .livestock-admin .action-strip .btn { min-height: 52px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; white-space: normal; }
    .livestock-admin .manage-note { border-left: 5px solid #d49300; background: #fff8df; padding: 12px 14px; margin-bottom: 16px; }
    .livestock-admin .modal .form-label { color: #17345d; font-weight: 700; }
    @media (max-width: 1199.98px) { .livestock-admin .action-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 767.98px) { .livestock-admin .page-band { padding: 17px; } .livestock-admin .action-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-wrapper livestock-admin-page">
<div class="content container-fluid">
<div class="livestock-admin">
    <section class="page-band">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2><i class="fas fa-cow me-2"></i>Livestock Management</h2>
                <p>Manage layer farms, flock production, costs, inventory and returns from the platform workspace.</p>
            </div>
            <span class="badge bg-light text-primary px-3 py-2">Full super-admin access</span>
        </div>
    </section>

    <div class="filter-bar d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><strong>{{ $selectedCompany->name }}</strong><div class="text-muted small">Premium platform workspace</div></div>
        <div class="text-muted small">Current-month production and operating results: {{ \Illuminate\Support\Carbon::parse($from)->format('d M') }} - {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><strong>Please correct the livestock form.</strong><div>{{ $errors->first() }}</div></div>
    @endif

    <div class="action-strip">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFarmModal"><i class="fas fa-plus"></i> Farm</button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFlockModal" @disabled($management['farms']->isEmpty())><i class="fas fa-feather"></i> Flock</button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addInvestmentModal" @disabled($management['farms']->isEmpty())><i class="fas fa-building-columns"></i> Investment</button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addOpexModal" @disabled($management['farms']->isEmpty())><i class="fas fa-receipt"></i> OPEX</button>
            <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#addRevenueModal" @disabled($management['farms']->isEmpty())><i class="fas fa-money-bill-trend-up"></i> Revenue</button>
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addProductionModal" @disabled($management['flocks']->where('status', 'active')->isEmpty())><i class="fas fa-clipboard-list"></i> Production</button>
            <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addInventoryModal" @disabled($management['farms']->isEmpty())><i class="fas fa-boxes-stacked"></i> Inventory</button>
    </div>
    @include('SuperAdmin.livestock._management-modals')

    <div class="row g-3">
        @foreach([
            ['Revenue this month', '₦'.number_format($metrics['revenue'], 2), 'green'],
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
        <header><h3>Flock Cycle Management</h3><span class="badge bg-primary">{{ $management['flocks']->count() }} flocks</span></header>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Farm</th><th>Batch</th><th>Breed</th><th>Placed</th><th>Opening</th><th>Current</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($management['flocks'] as $flock)
                        <tr><td>{{ $flock->farm_name }}</td><td><strong>{{ $flock->batch_code }}</strong></td><td>{{ $flock->breed ?: 'Not set' }}</td><td>{{ \Illuminate\Support\Carbon::parse($flock->placement_date)->format('d M Y') }}</td><td>{{ number_format($flock->opening_birds) }}</td><td>{{ number_format($flock->current_birds) }}</td><td><span class="badge {{ $flock->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($flock->status) }}</span></td><td class="text-end"><form method="POST" action="{{ route('super_admin.livestock.flocks.status', $flock->id) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $flock->status === 'active' ? 'closed' : 'active' }}"><button class="btn btn-sm {{ $flock->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $flock->status === 'active' ? 'Close' : 'Reopen' }}</button></form></td></tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">No flock cycles have been created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="data-panel">
        <header><h3>Managed Cost and Return Records</h3><span class="text-muted small">Delete is disabled for posted accounting records</span></header>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Date</th><th>Farm</th><th>Record</th><th>Description</th><th class="text-end">Amount / Output</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @foreach($management['investments']->take(15) as $record)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($record->cost_date)->format('d M Y') }}</td><td>{{ $record->farm_name }}</td><td><span class="badge bg-primary">Investment</span> {{ \Illuminate\Support\Str::headline($record->category) }}</td><td>{{ $record->description }}</td><td class="text-end">₦{{ number_format($record->cost, 2) }}</td><td class="text-end">@include('SuperAdmin.livestock._delete-record', ['type' => 'investment', 'id' => $record->id])</td></tr>
                    @endforeach
                    @foreach($management['opex']->take(15) as $record)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($record->expense_date)->format('d M Y') }}</td><td>{{ $record->farm_name }}</td><td><span class="badge bg-danger">OPEX</span> {{ \Illuminate\Support\Str::headline($record->category) }}</td><td>{{ $record->description ?: $record->vendor }}</td><td class="text-end">₦{{ number_format($record->amount, 2) }}</td><td class="text-end">@if(!$record->posted_at) @include('SuperAdmin.livestock._delete-record', ['type' => 'opex', 'id' => $record->id]) @else <span class="badge bg-secondary">Posted</span> @endif</td></tr>
                    @endforeach
                    @foreach($management['revenue']->take(15) as $record)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($record->revenue_date)->format('d M Y') }}</td><td>{{ $record->farm_name }}</td><td><span class="badge bg-success">Revenue</span> {{ \Illuminate\Support\Str::headline($record->source) }}</td><td>{{ $record->description ?: $record->customer }}</td><td class="text-end">₦{{ number_format($record->amount, 2) }}</td><td class="text-end">@if(!$record->posted_at) @include('SuperAdmin.livestock._delete-record', ['type' => 'revenue', 'id' => $record->id]) @else <span class="badge bg-secondary">Posted</span> @endif</td></tr>
                    @endforeach
                    @if($management['investments']->isEmpty() && $management['opex']->isEmpty() && $management['revenue']->isEmpty())
                        <tr><td colspan="6" class="empty-state">No managed cost or return records yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

    <section class="data-panel">
        <header><h3>Feed and Egg Inventory Movements</h3><span class="badge bg-secondary">{{ $management['inventory']->count() }} recent</span></header>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Date</th><th>Farm</th><th>Item</th><th>Movement</th><th class="text-end">Quantity</th><th>Reference</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($management['inventory'] as $record)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($record->movement_date)->format('d M Y') }}</td><td>{{ $record->farm_name }}</td><td>{{ ucfirst($record->item_type) }}</td><td>{{ \Illuminate\Support\Str::headline($record->movement_type) }}</td><td class="text-end fw-bold">{{ number_format($record->quantity, 3) }} {{ $record->unit }}</td><td>{{ $record->reference ?: '—' }}</td><td class="text-end">@if(!$record->source_id) @include('SuperAdmin.livestock._delete-record', ['type' => 'inventory', 'id' => $record->id]) @else <span class="badge bg-light text-dark">Automatic</span> @endif</td></tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">No inventory movements have been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="data-panel">
        <header><h3>Farm and Flock Register</h3><span class="badge bg-primary">{{ $farmRows->count() }} farms</span></header>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Farm</th><th>Location</th><th>Capacity</th><th>Active flocks</th><th>Current birds</th><th>Status</th><th class="text-end">Manage</th></tr></thead>
                <tbody>
                    @forelse($farmRows as $farm)
                        <tr><td><strong>{{ $farm->name }}</strong><br><small class="text-muted">{{ $farm->code }}</small></td><td>{{ $farm->location ?: 'Not set' }}</td><td>{{ number_format($farm->bird_capacity) }}</td><td>{{ number_format($farm->active_flocks) }}</td><td>{{ number_format($farm->current_birds) }}</td><td><span class="badge {{ $farm->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $farm->is_active ? 'Active' : 'Inactive' }}</span></td><td class="text-end"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editFarm{{ $farm->id }}" title="Edit farm"><i class="fas fa-pen"></i></button></td></tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">No farms yet. Use the Farm button above to create the first one.</td></tr>
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
                        <thead><tr><th>Date</th><th>Farm</th><th>Birds</th><th>Good eggs</th><th>Feed kg</th><th>Hen-day</th>@if($selectedCompany)<th class="text-end">Action</th>@endif</tr></thead>
                        <tbody>
                            @forelse($recentProduction as $row)
                                <tr><td>{{ \Illuminate\Support\Carbon::parse($row->production_date)->format('d M Y') }}</td><td>{{ $row->farm_name }}</td><td>{{ number_format($row->closing_birds) }}</td><td>{{ number_format($row->total_good_eggs) }}</td><td>{{ number_format($row->feed_kg, 2) }}</td><td>{{ number_format($row->hen_day_percent, 2) }}%</td>@if($selectedCompany)<td class="text-end">@include('SuperAdmin.livestock._delete-record', ['type' => 'production', 'id' => $row->id])</td>@endif</tr>
                            @empty
                                <tr><td colspan="{{ $selectedCompany ? 7 : 6 }}" class="empty-state">No production entries found.</td></tr>
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
</div>
</div>
@endsection
