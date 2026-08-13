@extends('layout.mainlayout')

@section('style')
<style>
    .sa-hotel { background:#eef3f8; color:#09213d; }
    .sa-hero { background:linear-gradient(135deg,#06264a,#0b5fb8 58%,#0f766e); color:#fff; border-radius:18px; padding:22px; margin-bottom:16px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; box-shadow:0 18px 36px rgba(8,47,73,.18); }
    .page-wrapper.sa-hotel .sa-hero h2, body.spb-super-admin-theme .page-wrapper.sa-hotel .sa-hero h2, body:not(.login-body):not(.landing-page-body) .page-wrapper.sa-hotel .sa-hero h2 { color:#f5c451 !important; -webkit-text-fill-color:#f5c451 !important; margin:0; font-size:31px; font-weight:900; text-shadow:0 2px 16px rgba(0,0,0,.22); }
    .page-wrapper.sa-hotel .sa-hero p, body.spb-super-admin-theme .page-wrapper.sa-hotel .sa-hero p, body:not(.login-body):not(.landing-page-body) .page-wrapper.sa-hotel .sa-hero p { color:#fff !important; -webkit-text-fill-color:#fff !important; }
    .page-wrapper.sa-hotel .sa-hero small, body.spb-super-admin-theme .page-wrapper.sa-hotel .sa-hero small, body:not(.login-body):not(.landing-page-body) .page-wrapper.sa-hotel .sa-hero small { color:#f7d777 !important; -webkit-text-fill-color:#f7d777 !important; text-transform:uppercase; letter-spacing:.14em; font-weight:900; }
    .sa-panel, .sa-card, .sa-filter { background:#fff; border:1px solid #d8e2ee; border-radius:14px; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .sa-tabs { display:flex; gap:8px; overflow:auto; padding:12px; margin-bottom:16px; }
    .sa-tabs a { white-space:nowrap; border:1px solid #cbd8e8; border-radius:999px; padding:8px 13px; color:#0b2f54; text-decoration:none; font-weight:800; }
    .sa-tabs a.active { background:#0b5fb8; color:#fff; border-color:#0b5fb8; }
    .sa-filter { padding:14px; margin-bottom:16px; }
    .sa-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .sa-kpi { padding:16px; position:relative; overflow:hidden; min-height:120px; }
    .sa-kpi:before { content:''; position:absolute; inset:0 auto 0 0; width:5px; background:#0b5fb8; }
    .sa-kpi.green:before { background:#16a34a; } .sa-kpi.gold:before { background:#d4a23a; } .sa-kpi.red:before { background:#dc2626; }
    .sa-kpi strong { display:block; font-size:32px; line-height:1; margin:8px 0 5px; }
    .sa-workspace { display:grid; grid-template-columns:230px minmax(0,1fr); gap:16px; }
    .sa-rail { background:#0b2f54; color:#fff; border-radius:14px; padding:14px; align-self:start; }
    .sa-rail h5 { color:#fff; font-size:14px; text-transform:uppercase; letter-spacing:.1em; }
    .sa-rail a, .sa-rail div { display:block; color:#dbeafe; text-decoration:none; padding:10px 8px; border-bottom:1px solid rgba(255,255,255,.12); }
    .sa-rail strong { color:#fff; }
    .sa-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .sa-card { padding:16px; color:#09213d; text-decoration:none; min-height:145px; }
    .sa-card .label { color:#d4a23a; text-transform:uppercase; letter-spacing:.12em; font-size:12px; font-weight:900; }
    .sa-empty { grid-column:1 / -1; border:1px dashed #d4a23a; border-radius:12px; padding:22px; background:#fff8e1; color:#5a3d00; font-weight:800; }
    .sa-sample { border-style:dashed; background:#fffdf5; }
    .sa-room-rack { display:grid; grid-template-columns:repeat(auto-fill,minmax(132px,1fr)); gap:10px; }
    .sa-room { min-height:138px; border-radius:8px; border:1px solid #d8e2ee; background:#fff; padding:10px; position:relative; overflow:hidden; }
    .sa-room.available { background:#ecfdf3; } .sa-room.occupied { background:#e8f2ff; } .sa-room.reserved { background:#fff8e5; } .sa-room.maintenance, .sa-room.out_of_order { background:#fff1f2; }
    .sa-room-num { font-size:35px; font-weight:300; color:#0b5fb8; line-height:1; margin:7px 0; }
    .sa-calendar { overflow:auto; }
    .sa-calendar table { min-width:950px; border-collapse:separate; border-spacing:0; }
    .sa-calendar th { background:#0c3f70; color:#fff; border:0; font-size:12px; text-transform:uppercase; }
    .sa-calendar td { vertical-align:top; min-width:120px; height:78px; border-color:#dbe4ef; }
    .sa-event { border-radius:8px; padding:7px; color:#fff; font-size:12px; background:#0b5fb8; }
    .sa-event.gold { background:#d4a23a; color:#111827; } .sa-event.green { background:#16a34a; } .sa-event.red { background:#dc2626; }
    .sa-board-row { display:grid; grid-template-columns:150px minmax(0,1fr) 150px 160px; gap:12px; align-items:center; padding:13px 16px; border-bottom:1px solid #edf1f6; }
    .sa-board-row:last-child { border-bottom:0; }
    .sa-table th { background:#0c3f70; color:#fff; border:0; text-transform:uppercase; font-size:12px; }
    .sa-profile-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .sa-profile { display:grid; grid-template-columns:58px minmax(0,1fr); gap:12px; padding:14px; border:1px solid #dbe4ef; border-radius:12px; background:#fff; }
    .sa-avatar { width:58px; height:58px; border-radius:50%; background:#0b2f54; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:22px; }
    .sa-kanban { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .sa-lane { background:#f8fafc; border:1px solid #dbe4ef; border-radius:12px; padding:10px; min-height:250px; }
    .sa-lane h5 { font-size:14px; text-transform:uppercase; letter-spacing:.08em; color:#475569; }
    .sa-ticket { background:#fff; border:1px solid #e5eaf2; border-left:5px solid #0b5fb8; border-radius:10px; padding:10px; margin-bottom:9px; }
    .sa-ticket.danger { border-left-color:#dc2626; } .sa-ticket.gold { border-left-color:#d4a23a; } .sa-ticket.green { border-left-color:#16a34a; }
    .sa-cashier { display:grid; grid-template-columns:280px minmax(0,1fr) 260px; gap:14px; }
    .sa-cashier-side { background:#24333a; color:#fff; border-radius:14px; padding:16px; }
    .sa-cashier-side h3 { color:#fff; font-size:38px; }
    .sa-pad { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
    .sa-pad div { min-height:74px; border:1px solid #d8e2ee; border-radius:10px; display:flex; align-items:center; justify-content:center; text-align:center; font-weight:900; background:#fff; }
    .sa-service-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .sa-service { min-height:132px; padding:16px; border-radius:14px; border:1px solid #d8e2ee; background:#fff; color:#09213d; text-decoration:none; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .sa-service span { color:#d4a23a; text-transform:uppercase; letter-spacing:.12em; font-size:12px; font-weight:900; }
    .sa-action-disabled { opacity:.7; cursor:not-allowed; }
    .sa-report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .sa-report { min-height:160px; background:#082f55; color:#fff; border-radius:14px; padding:18px; text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; }
    .sa-report span { color:#f1c15c; letter-spacing:.12em; text-transform:uppercase; font-size:12px; font-weight:900; }
    .sa-report h4, .sa-report p, .sa-cashier-side h3, .sa-cashier-side p, .sa-cashier-side small { color:#fff !important; }
    .sa-health { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .sa-health-row { display:flex; justify-content:space-between; align-items:center; padding:14px; border:1px solid #dbe4ef; border-radius:12px; background:#fff; }
    .sa-room-admin { display:grid; grid-template-columns:220px minmax(0,1fr); gap:14px; }
    .sa-room-admin .sa-room-rack { grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); }
    .sa-room-admin .sa-room { min-height:165px; border-radius:6px; }
    .sa-room-admin .sa-room-num { font-size:42px; font-weight:300; }
    .sa-folio-register { display:grid; grid-template-columns:260px minmax(0,1fr); gap:14px; }
    .sa-folio-rail { background:#24333a; color:#fff; border-radius:6px; padding:14px; }
    .sa-folio-rail h3 { color:#fff; font-size:34px; margin:8px 0; }
    .sa-folio-rail a, .sa-folio-rail div { display:flex; justify-content:space-between; color:#fff; text-decoration:none; border-bottom:1px solid rgba(255,255,255,.14); padding:11px 0; font-weight:800; }
    .sa-maint-desk { display:grid; grid-template-columns:300px minmax(0,1fr); gap:14px; }
    .sa-maint-side { background:#111827; color:#fff; border-left:8px solid #d4a23a; border-radius:14px; padding:16px; }
    .sa-maint-side h4, .sa-maint-side p { color:#fff !important; }
    .sa-maint-ticket { display:grid; grid-template-columns:120px minmax(0,1fr) 120px 140px; gap:12px; align-items:center; padding:13px; border:1px solid #e5edf6; border-left:6px solid #94a3b8; border-radius:14px; background:#fff; margin-bottom:10px; }
    .sa-maint-ticket.danger { border-left-color:#dc2626; background:#fff7f7; }
    .sa-maint-room { font-size:30px; color:#0b5fb8; font-weight:300; line-height:1; }
    .sa-audit-command { background:#071d35; border-radius:18px; padding:16px; color:#dbeafe; }
    .sa-audit-command h4, .sa-audit-command p { color:#fff !important; }
    .sa-audit-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-top:12px; }
    .sa-audit-check { background:#fff; color:#172033; border:1px solid #d9e4ef; border-left:5px solid #0b5fb8; border-radius:14px; padding:14px; }
    .sa-audit-check.warning { border-left-color:#d4a23a; }
    .sa-audit-check.danger { border-left-color:#dc2626; }
    .sa-report-hub { background:#061d36; color:#fff; border-radius:18px; padding:16px; }
    .sa-report-hub h4, .sa-report-hub p { color:#fff !important; }
    .sa-report-hub .sa-report { background:#102f4d; }
    @media(max-width:1199px){.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid,.sa-service-grid,.sa-profile-grid,.sa-health,.sa-audit-grid{grid-template-columns:repeat(2,1fr)}.sa-workspace,.sa-cashier,.sa-room-admin,.sa-folio-register,.sa-maint-desk{grid-template-columns:1fr}.sa-board-row,.sa-maint-ticket{grid-template-columns:1fr}}
    @media(max-width:767px){.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid,.sa-service-grid,.sa-profile-grid,.sa-health,.sa-audit-grid{grid-template-columns:1fr}.page-wrapper.sa-hotel .sa-hero h2{font-size:23px}}
</style>
@endsection

@section('content')
@php
    $isPaginator = $panelData instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $panelRows = $isPaginator ? collect($panelData->items()) : collect($panelData);
    $rowArray = function ($row) {
        return $row instanceof \Illuminate\Database\Eloquent\Model ? $row->getAttributes() : (array) $row;
    };
    $money = fn($value) => number_format((float) ($value ?? 0), 2);
    $panelTitle = $panels[$panel] ?? ucfirst($panel);
    $statusBadge = function ($status) {
        return match((string) $status) {
            'active', 'available', 'completed', 'resolved', 'checked_in' => 'bg-success',
            'reserved', 'confirmed', 'paid' => 'bg-primary',
            'dirty', 'high', 'critical', 'failed', 'cancelled' => 'bg-danger',
            'pending', 'maintenance', 'open' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    };
@endphp
<div class="page-wrapper sa-hotel">
    <div class="content container-fluid">
        <section class="sa-hero">
            <div>
                <small>SmartProbook Hotel PMS · Super Admin Enterprise Monitor</small>
                <h2>{{ $panelTitle }}</h2>
                <p class="mb-0">Platform-level mirror of tenant hotel operations: room rack, reservation journey, guest folios, housekeeping, maintenance, night audit and reporting.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-self-start">
                <a href="{{ route('super_admin.hotels.index') }}" class="btn btn-light">Dashboard</a>
                <span class="btn btn-warning disabled text-dark">{{ strtoupper(str_replace('_',' ', $panel)) }}</span>
            </div>
        </section>

        <nav class="sa-panel sa-tabs">
            @foreach($panels as $panelKey => $panelLabel)
                <a class="{{ $panel === $panelKey ? 'active' : '' }}" href="{{ route('super_admin.hotels.index', $panelKey === 'overview' ? [] : ['panel' => $panelKey]) }}">{{ $panelLabel }}</a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('super_admin.hotels.index') }}" class="sa-filter d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="panel" value="{{ $panel }}">
            <div style="min-width:260px"><label class="form-label">Hotel Tenant</label><select name="company_id" class="form-control"><option value="">All Hotel Tenants</option>@foreach($hotelCompanies as $company)<option value="{{ $company->id }}" {{ (int) $selectedCompanyId === (int) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>@endforeach</select></div>
            <button class="btn btn-primary">Apply Filter</button>
        </form>

        @if($panel === 'overview')
            <div class="sa-kpis">
                <div class="sa-panel sa-kpi"><span>Total Hotel Tenants</span><strong>{{ $totalHotelTenants }}</strong><small>Hotel-enabled companies</small></div>
                <div class="sa-panel sa-kpi green"><span>Active Subscriptions</span><strong>{{ $activeHotelSubscriptions }}</strong><small>Paid active hotels</small></div>
                <div class="sa-panel sa-kpi gold"><span>Total Properties</span><strong>{{ $totalProperties }}</strong><small>Branches/properties</small></div>
                <div class="sa-panel sa-kpi red"><span>Total Rooms</span><strong>{{ $totalRooms }}</strong><small>Hotel room inventory</small></div>
                <div class="sa-panel sa-kpi green"><span>Available Rooms</span><strong>{{ $availableRooms }}</strong><small>Ready for sale</small></div>
                <div class="sa-panel sa-kpi"><span>Occupied Rooms</span><strong>{{ $occupiedRooms }}</strong><small>In-house guests</small></div>
                <div class="sa-panel sa-kpi gold"><span>Reserved Rooms</span><strong>{{ $reservedRooms }}</strong><small>Held inventory</small></div>
                <div class="sa-panel sa-kpi"><span>Revenue Today</span><strong>{{ $money($hotelRevenueToday) }}</strong><small>This month {{ $money($hotelRevenueThisMonth) }}</small></div>
            </div>

            <div class="sa-service-grid">
                @foreach($serviceCenters as $serviceKey => $serviceMeta)
                    @continue($serviceKey === 'all')
                    <a class="sa-service" href="{{ route('super_admin.hotels.index', ['panel' => 'services', 'service' => $serviceKey, 'company_id' => $selectedCompanyId]) }}">
                        <span>{{ strtoupper($serviceKey === 'room_service' ? 'Room Service' : $serviceKey) }}</span>
                        <h5>{{ $serviceMeta['label'] }}</h5>
                        <p class="text-muted mb-0">Monitor tenant {{ strtolower($serviceMeta['label']) }} charges, postings and revenue from Super Admin.</p>
                    </a>
                @endforeach
            </div>
            <div class="sa-workspace">
                <aside class="sa-rail"><h5>Enterprise Monitor</h5><a href="{{ route('super_admin.hotels.index', ['panel' => 'rooms']) }}"><strong>Room Rack</strong><br>Availability, occupied and reserved</a><a href="{{ route('super_admin.hotels.index', ['panel' => 'reservations']) }}"><strong>Reservations</strong><br>Booking pipeline and assignments</a><a href="{{ route('super_admin.hotels.index', ['panel' => 'folios']) }}"><strong>Cashier</strong><br>Folios and receivables</a><a href="{{ route('super_admin.hotels.index', ['panel' => 'reports']) }}"><strong>Reports</strong><br>Platform PMS intelligence</a></aside>
                <main>
                    <div class="sa-grid">
                    <a class="sa-card" href="{{ route('super_admin.hotels.index', ['panel' => 'tenants']) }}"><span class="label">01 Tenant Control</span><h5>Hotel Tenants</h5><p class="text-muted mb-0">Inspect hotel-enabled companies, plan access and subscription readiness.</p></a>
                    <a class="sa-card" href="{{ route('super_admin.hotels.index', ['panel' => 'properties']) }}"><span class="label">02 Properties</span><h5>Property Directory</h5><p class="text-muted mb-0">Monitor branches/properties under each hotel tenant.</p></a>
                    <a class="sa-card" href="{{ route('super_admin.hotels.index', ['panel' => 'rooms']) }}"><span class="label">03 Room Board</span><h5>Room State Grid</h5><p class="text-muted mb-0">Cloudbeds-style operational room visibility.</p></a>
                    <a class="sa-card" href="{{ route('super_admin.hotels.index', ['panel' => 'reservations']) }}"><span class="label">04 Reservations</span><h5>Booking Timeline</h5><p class="text-muted mb-0">Arrival, departure and status tracking.</p></a>
                    <a class="sa-card" href="{{ route('super_admin.hotels.index', ['panel' => 'housekeeping']) }}"><span class="label">05 Housekeeping</span><h5>Cleaning Board</h5><p class="text-muted mb-0">Dirty, assigned, cleaning and inspection lanes.</p></a>
                    <a class="sa-card" href="{{ route('super_admin.hotels.index', ['panel' => 'night_audits']) }}"><span class="label">06 Night Audit</span><h5>Close Day</h5><p class="text-muted mb-0">Audit history and close-day monitoring.</p></a>
                    </div>
                </main>
            </div>
        @elseif(in_array($panel, ['tenants','properties'], true))
            <section class="sa-grid">
                @forelse($panelRows as $row)
                    @php $r = $rowArray($row); @endphp
                    <div class="sa-card"><span class="label">{{ $panel === 'tenants' ? 'Hotel Tenant' : 'Property' }}</span><h5>{{ $r['name'] ?? ('Record #'.($r['id'] ?? '-')) }}</h5><p class="text-muted mb-2">Company {{ $r['company_id'] ?? $r['id'] ?? '-' }} · Branch {{ $r['branch_id'] ?? '-' }}</p><span class="badge {{ $statusBadge($r['status'] ?? 'active') }}">{{ ucfirst((string)($r['status'] ?? 'active')) }}</span></div>
                @empty
                    <div class="sa-empty">No {{ strtolower($panelTitle) }} found. When tenant hotel setup is completed, this area becomes a directory instead of cards.</div>
                @endforelse
            </section>
        @elseif($panel === 'rooms')
            <div class="sa-room-admin">
                <aside class="sa-rail"><h5>Room Inventory Mirror</h5><div><strong>{{ $availableRooms }}</strong><br>Available</div><div><strong>{{ $occupiedRooms }}</strong><br>Occupied</div><div><strong>{{ $reservedRooms }}</strong><br>Reserved</div><div><strong>{{ $totalRooms }}</strong><br>Total Rooms</div><a href="{{ route('super_admin.hotels.index', ['panel'=>'room_types', 'company_id'=>$selectedCompanyId]) }}"><strong>Room Types</strong><br>Rates and capacity catalogue</a></aside>
                <section class="sa-panel p-3"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h4 class="mb-1">Platform Front Desk / Room Board</h4><p class="text-muted mb-0">Super Admin mirror of tenant room stock, housekeeping state, operational state and property mapping.</p></div><span class="badge bg-light text-dark">{{ $panelRows->count() }} loaded</span></div>@if($panelRows->isEmpty())<div class="sa-empty">No rooms found yet. Use tenant hotel setup to add real rooms; this board will populate from tenant records.</div>@else<div class="sa-room-rack">@foreach($panelRows as $row)@php $r=$rowArray($row); $state=(string)($r['operational_status'] ?? 'available'); @endphp<div class="sa-room {{ $state }}"><div class="d-flex justify-content-between small"><span>{{ ucfirst(str_replace('_',' ', $state)) }}</span><span class="badge bg-light text-dark">Read-only</span></div><div class="sa-room-num">{{ $r['room_number'] ?? ('#'.($r['id'] ?? '-')) }}</div><strong>Property {{ $r['property_id'] ?? '-' }}</strong><div class="small text-muted">Company {{ $r['company_id'] ?? '-' }} · Type {{ $r['room_type_id'] ?? '-' }}</div><div class="small mt-2">Housekeeping: {{ ucfirst((string)($r['housekeeping_status'] ?? 'clean')) }}</div><div class="d-flex flex-wrap gap-1 mt-2"><button type="button" class="btn btn-sm btn-outline-secondary sa-action-disabled" disabled title="Use the tenant front desk to check in, check out, reserve or update housekeeping.">Tenant action</button></div></div>@endforeach</div>@endif</section>
            </div>
        @elseif($panel === 'room_types')
            <section class="sa-grid">@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-card"><span class="label">Room Type</span><h5>{{ $r['name'] ?? ('Type #'.($r['id'] ?? '-')) }}</h5><p class="text-muted">Company {{ $r['company_id'] ?? '-' }} · Property {{ $r['property_id'] ?? '-' }}</p><div class="d-flex justify-content-between"><span>Occupancy</span><strong>{{ $r['max_occupancy'] ?? '-' }}</strong></div><div class="d-flex justify-content-between"><span>Base Rate</span><strong>{{ $money($r['base_rate'] ?? 0) }}</strong></div></div>@empty<div class="sa-empty"><strong>No room types found yet.</strong><br>Use tenant hotel setup to add real room types; Super Admin will show the tenant catalogue here after setup.</div>@endforelse</section>
        @elseif($panel === 'room_calendar')
            <section class="sa-panel p-3"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h4 class="mb-1">Room Calendar Timeline</h4><p class="text-muted mb-0">Reservation, block and maintenance visibility across tenant hotels. Read-only here; tenant calendars handle assignments and changes.</p></div><button type="button" class="btn btn-outline-secondary sa-action-disabled" disabled title="Open the tenant Room Calendar to create blocks or assign rooms.">Tenant calendar actions only</button></div><div class="sa-calendar"><table class="table table-bordered"><thead><tr><th>Room</th><th>Guest</th><th>Arrival</th><th>Departure</th><th>Status</th><th>Deposit</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td>{{ $r['room_id'] ?? 'Unassigned' }}</td><td>Guest #{{ $r['customer_id'] ?? '-' }}</td><td>{{ $r['arrival_date'] ?? '-' }}</td><td>{{ $r['departure_date'] ?? '-' }}</td><td><span class="badge {{ $statusBadge($r['status'] ?? 'reserved') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'reserved'))) }}</span></td><td>{{ $money($r['deposit_received'] ?? 0) }}</td></tr>@empty<tr><td colspan="6"><div class="sa-empty">No reservations or calendar assignments found. Real tenant reservations will appear on this room calendar monitor.</div></td></tr>@endforelse</tbody></table></div></section>
        @elseif($panel === 'reservations')
            <section class="sa-panel p-3"><h4>Reservation Calendar Board</h4><div class="sa-calendar"><table class="table table-bordered"><thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Arrival</th><th>Departure</th><th>Status</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td><div class="sa-event {{ ($r['status'] ?? '') === 'confirmed' ? 'green' : 'gold' }}">{{ $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</div></td><td>Guest #{{ $r['customer_id'] ?? '-' }}</td><td>{{ $r['room_id'] ?? 'Unassigned' }}</td><td>{{ $r['arrival_date'] ?? '-' }}</td><td>{{ $r['departure_date'] ?? '-' }}</td><td><span class="badge {{ $statusBadge($r['status'] ?? 'reserved') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'reserved'))) }}</span></td></tr>@empty<tr><td colspan="6"><div class="sa-empty">No reservations found. This panel is the platform-level reservation operations board.</div></td></tr>@endforelse</tbody></table></div></section>
        @elseif($panel === 'availability')
            <section class="sa-grid">@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-card"><span class="label">Availability Signal</span><h5>{{ $r['reservation_number'] ?? ('Reservation #'.($r['id'] ?? '-')) }}</h5><p class="text-muted mb-2">Room {{ $r['room_id'] ?? 'Unassigned' }} · Type {{ $r['room_type_id'] ?? '-' }}</p><div class="d-flex justify-content-between"><span>Arrival</span><strong>{{ $r['arrival_date'] ?? '-' }}</strong></div><div class="d-flex justify-content-between"><span>Departure</span><strong>{{ $r['departure_date'] ?? '-' }}</strong></div><button type="button" class="btn btn-sm btn-outline-secondary mt-3 sa-action-disabled" disabled title="Run availability searches inside the tenant hotel workspace.">Tenant search only</button></div>@empty<div class="sa-empty">No active reservation demand found. Tenant availability searches use live room type and room inventory.</div>@endforelse</section>
        @elseif($panel === 'check_in')
            <section class="sa-panel p-3"><h4>Check-In Queue</h4><div class="table-responsive"><table class="table table-sm sa-table align-middle mb-0"><thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Arrival</th><th>Deposit</th><th>Action</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td>{{ $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</td><td>Guest #{{ $r['customer_id'] ?? '-' }}</td><td>{{ $r['room_id'] ?? 'Unassigned' }}</td><td>{{ $r['arrival_date'] ?? '-' }}</td><td>{{ $money($r['deposit_received'] ?? 0) }}</td><td><button type="button" class="btn btn-sm btn-outline-secondary sa-action-disabled" disabled title="Complete check-in from the tenant hotel Check-In desk.">Tenant check-in only</button></td></tr>@empty<tr><td colspan="6"><div class="sa-empty">No check-in queue found. Tenant arrivals will appear here when reservations exist.</div></td></tr>@endforelse</tbody></table></div></section>
        @elseif($panel === 'stays')
            <section class="sa-panel sa-row-list"><div class="p-3 border-bottom"><h4 class="mb-0">In-House Guest Control</h4></div>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-board-row"><div><strong>Stay #{{ $r['id'] ?? '-' }}</strong><div class="small text-muted">Company {{ $r['company_id'] ?? '-' }}</div></div><div>Guest #{{ $r['customer_id'] ?? '-' }} · Room #{{ $r['room_id'] ?? 'N/A' }}</div><div>{{ $r['checkin_at'] ?? '-' }}</div><div><span class="badge {{ $statusBadge($r['status'] ?? 'checked_in') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'checked_in'))) }}</span></div></div>@empty<div class="p-4"><div class="sa-empty">No current stays found. This panel becomes an in-house guest register once rooms are occupied.</div></div>@endforelse</section>
        @elseif($panel === 'checkout')
            <section class="sa-panel p-3"><h4>Checkout Settlement Queue</h4><div class="table-responsive"><table class="table table-sm sa-table align-middle mb-0"><thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Expected Checkout</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td>Stay #{{ $r['id'] ?? '-' }}</td><td>Guest #{{ $r['customer_id'] ?? '-' }}</td><td>{{ $r['room_id'] ?? '-' }}</td><td>{{ $r['expected_checkout_at'] ?? '-' }}</td><td><span class="badge {{ $statusBadge($r['status'] ?? 'checked_in') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'checked_in'))) }}</span></td><td><button type="button" class="btn btn-sm btn-outline-secondary sa-action-disabled" disabled title="Settle folios and complete checkout inside the tenant hotel workspace.">Tenant checkout only</button></td></tr>@empty<tr><td colspan="6"><div class="sa-empty">No checkout queue found. Checked-in tenant stays will appear here.</div></td></tr>@endforelse</tbody></table></div></section>
        @elseif($panel === 'guests')
            <section class="sa-profile-grid">@forelse($panelRows as $row)@php $r=$rowArray($row); $name=$r['customer_name'] ?? $r['name'] ?? 'Guest'; @endphp<div class="sa-profile"><div class="sa-avatar">{{ strtoupper(substr((string)$name,0,1)) }}</div><div><h5 class="mb-1">{{ $name }}</h5><div class="text-muted small">{{ $r['phone'] ?? 'No phone' }} · {{ $r['email'] ?? 'No email' }}</div><span class="badge bg-light text-dark mt-2">Guest #{{ $r['id'] ?? '-' }}</span></div></div>@empty<div class="sa-empty">No guest profiles found. Guest CRM cards will appear here when hotel reservations/stays exist.</div>@endforelse</section>
        @elseif($panel === 'services' || str_starts_with($panel, 'service_'))
            <section class="sa-panel p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="mb-1">Hotel Service Centers</h4>
                        <p class="text-muted mb-0">Super Admin mirror for Restaurant, Bar, Spa, Gym, Room Service, Minibar, Laundry and Events operations.</p>
                    </div>
                    <form method="GET" action="{{ route('super_admin.hotels.index') }}" class="d-flex gap-2">
                        <input type="hidden" name="panel" value="services">
                        @if($selectedCompanyId)<input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">@endif
                        <select name="service" class="form-control" onchange="this.form.submit()">
                            @foreach($serviceCenters as $serviceKey => $serviceMeta)
                                <option value="{{ $serviceKey }}" {{ $selectedServiceCenter === $serviceKey ? 'selected' : '' }}>{{ $serviceMeta['label'] }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="sa-service-grid">
                    @foreach($serviceCenters as $serviceKey => $serviceMeta)
                        @continue($serviceKey === 'all')
                        <a class="sa-service {{ $selectedServiceCenter === $serviceKey ? 'border-warning' : '' }}" href="{{ route('super_admin.hotels.index', ['panel' => 'services', 'service' => $serviceKey, 'company_id' => $selectedCompanyId]) }}">
                            <span>{{ strtoupper(str_replace('_',' ', $serviceKey)) }}</span>
                            <h5>{{ $serviceMeta['label'] }}</h5>
                            <p class="text-muted mb-0">Live service-code monitor for tenant hotel postings.</p>
                        </a>
                    @endforeach
                </div>
                <div class="table-responsive">
                    <table class="table table-sm sa-table align-middle mb-0">
                        <thead><tr><th>Posting</th><th>Company</th><th>Service</th><th>Type</th><th>Amount</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse($panelRows as $row)
                                @php $r=$rowArray($row); @endphp
                                <tr>
                                    <td>{{ $r['description'] ?? $r['folio_number'] ?? ('#'.($r['id'] ?? '-')) }}</td>
                                    <td>{{ $r['company_id'] ?? '-' }}</td>
                                    <td><span class="badge bg-warning text-dark">{{ $r['service_code'] ?? $r['department'] ?? 'SERVICE' }}</span></td>
                                    <td>{{ $r['type'] ?? $r['payment_method'] ?? '-' }}</td>
                                    <td>{{ $money($r['amount'] ?? $r['total_amount'] ?? 0) }}</td>
                                    <td>{{ $r['service_date'] ?? $r['created_at'] ?? $r['business_date'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="sa-empty">No service center postings found yet. The service cards are active and will populate from tenant hotel folio/POS postings when records exist.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @elseif(in_array($panel, ['folios','deposits'], true))
            <section class="sa-folio-register">
                <aside class="sa-folio-rail"><small>{{ strtoupper($panelTitle) }}</small><h3>{{ $money($outstandingReceivables) }}</h3><p>Platform cashier register for tenant hotel folios, deposits, payments and guest balances.</p><a href="{{ route('super_admin.hotels.index', ['panel'=>'folios', 'company_id'=>$selectedCompanyId]) }}"><span>Folios</span><strong>Open</strong></a><a href="{{ route('super_admin.hotels.index', ['panel'=>'deposits', 'company_id'=>$selectedCompanyId]) }}"><span>Deposits</span><strong>Trace</strong></a><a href="{{ route('super_admin.hotels.index', ['panel'=>'services', 'company_id'=>$selectedCompanyId]) }}"><span>Service Centers</span><strong>Audit</strong></a></aside>
                <main class="sa-panel p-3"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h4 class="mb-1">Cashier Ledger Monitor</h4><p class="text-muted mb-0">Read-only Super Admin view of tenant cashier activity and balances.</p></div><span class="badge bg-light text-dark">{{ $panelRows->count() }} loaded</span></div><div class="table-responsive"><table class="table table-sm sa-table align-middle mb-0"><thead><tr><th>Record</th><th>Company</th><th>Guest/Stay</th><th>Status/Type</th><th>Amount</th><th>Date</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td><strong>{{ $r['folio_number'] ?? $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</strong></td><td>{{ $r['company_id'] ?? '-' }}</td><td>{{ $r['customer_id'] ?? $r['stay_id'] ?? '-' }}</td><td><span class="badge {{ $statusBadge($r['status'] ?? $r['type'] ?? 'open') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? $r['type'] ?? $r['service_code'] ?? 'record'))) }}</span></td><td><strong>{{ $money($r['balance'] ?? $r['amount'] ?? $r['deposit_received'] ?? $r['total_amount'] ?? 0) }}</strong></td><td>{{ $r['created_at'] ?? $r['service_date'] ?? $r['business_date'] ?? '-' }}</td></tr>@empty<tr><td colspan="6"><div class="sa-empty">No {{ strtolower($panelTitle) }} found.</div></td></tr>@endforelse</tbody></table></div></main>
            </section>
        @elseif($panel === 'maintenance')
            <section class="sa-maint-desk"><aside class="sa-maint-side"><small>ENGINEERING MIRROR</small><h4>Maintenance Desk</h4><p>Platform view of tenant room issues, technician queues and unavailable-room risks.</p><div class="mt-3"><strong>{{ $panelRows->count() }}</strong><br>tickets loaded</div></aside><main class="sa-panel p-3"><h4 class="mb-3">Maintenance Tickets</h4>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-maint-ticket {{ in_array(($r['severity'] ?? $r['status'] ?? ''), ['high','critical','failed'], true) ? 'danger' : '' }}"><div><div class="sa-maint-room">{{ $r['room_id'] ?? '-' }}</div><small class="text-muted">Room</small></div><div><strong>{{ $r['ticket_no'] ?? ('#'.($r['id'] ?? '-')) }}</strong><div>{{ $r['title'] ?? $r['description'] ?? 'Maintenance record' }}</div><small class="text-muted">Company {{ $r['company_id'] ?? '-' }}</small></div><div><span class="badge {{ $statusBadge($r['severity'] ?? 'open') }}">{{ ucfirst((string)($r['severity'] ?? 'normal')) }}</span></div><div>{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'open'))) }}</div></div>@empty<div class="sa-empty">No maintenance tickets found.</div>@endforelse</main></section>
        @elseif($panel === 'night_audits')
            <section class="sa-audit-command"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h4 class="mb-1">Night Audit Command Center</h4><p class="mb-0">Platform close-day history and audit health across hotel tenants.</p></div><span class="btn btn-warning disabled text-dark">{{ $panelRows->count() }} audit rows</span></div><div class="sa-audit-grid">@forelse($panelRows->take(8) as $row)@php $r=$rowArray($row); @endphp<div class="sa-audit-check {{ in_array(($r['status'] ?? ''), ['failed','pending'], true) ? 'danger' : '' }}"><span class="text-muted small">Business Date</span><h5>{{ $r['audit_date'] ?? $r['business_date'] ?? ('#'.($r['id'] ?? '-')) }}</h5><div class="d-flex justify-content-between"><span>Status</span><strong>{{ ucfirst((string)($r['status'] ?? 'completed')) }}</strong></div><div class="d-flex justify-content-between"><span>Total</span><strong>{{ $money($r['total_amount'] ?? 0) }}</strong></div><small class="text-muted">Company {{ $r['company_id'] ?? '-' }}</small></div>@empty<div class="sa-empty">No night audits have been run yet.</div>@endforelse</div></section>
        @elseif($panel === 'housekeeping')
            <section class="sa-kanban">@foreach(['open'=>'Dirty','assigned'=>'Assigned','cleaning'=>'Cleaning','completed'=>'Clean'] as $laneKey => $laneLabel)<div class="sa-lane"><h5>{{ $laneLabel }}</h5>@php $laneRows = $panelRows->filter(function($row) use ($rowArray,$laneKey){$r=$rowArray($row);$status=(string)($r['status'] ?? 'open');return $laneKey === 'cleaning' ? in_array($status,['cleaning','inspection'],true) : ($laneKey === 'assigned' ? in_array($status,['assigned','in_progress'],true) : ($laneKey === 'completed' ? in_array($status,['completed','resolved','clean'],true) : in_array($status,['open','new','dirty','pending'],true)));}); @endphp @forelse($laneRows->take(8) as $row)@php $r=$rowArray($row); @endphp<div class="sa-ticket {{ ($r['priority'] ?? '') === 'high' ? 'danger' : '' }}"><strong>{{ $r['task_no'] ?? ('Task #'.($r['id'] ?? '-')) }}</strong><div class="small text-muted">Room {{ $r['room_id'] ?? '-' }} · Company {{ $r['company_id'] ?? '-' }}</div><div>{{ $r['note'] ?? $r['description'] ?? 'Housekeeping record' }}</div></div>@empty<div class="text-muted small">No {{ strtolower($laneLabel) }} items.</div>@endforelse</div>@endforeach</section>
        @elseif($panel === 'reports')
            <section class="sa-report-hub"><div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3"><div><small class="text-warning fw-bold">HOTEL REPORTS · SUPER ADMIN</small><h4 class="mb-1">Platform PMS Reports Centre</h4><p class="mb-0">Distinct report destinations for reservations, cashier, room readiness, engineering and audit oversight.</p></div><span class="btn btn-warning disabled text-dark">Enterprise Monitor</span></div><div class="sa-report-grid"><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'reservations']) }}"><span>Front Office</span><h4>Reservation Register</h4><p>All hotel reservation activity by tenant.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'folios']) }}"><span>Finance</span><h4>Folio Receivables</h4><p>Guest balances and folio exposure.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'services']) }}"><span>Services</span><h4>Service Centers</h4><p>Restaurant, bar, spa, gym, room service and events.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'housekeeping']) }}"><span>Rooms</span><h4>Housekeeping</h4><p>Room readiness workload.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'maintenance']) }}"><span>Engineering</span><h4>Maintenance</h4><p>Tickets and unavailable rooms.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'night_audits']) }}"><span>Audit</span><h4>Night Audits</h4><p>Business day close history.</p></a></div></section>
        @elseif($panel === 'settings')
            <section class="sa-health">@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-health-row"><div><strong>{{ str_replace('_',' ', $r['setting'] ?? 'Setting') }}</strong><div class="small text-muted">Tenant PMS dependency</div></div><span class="badge {{ ($r['status'] ?? '') === 'available' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst((string)($r['status'] ?? 'missing')) }}</span></div>@empty<div class="sa-empty">No hotel settings found.</div>@endforelse</section>
        @else
            <section class="sa-panel p-3"><h4>{{ $panelTitle }}</h4>@if($panelRows->isEmpty())<div class="sa-empty">No {{ strtolower($panelTitle) }} found.</div>@else<div class="table-responsive"><table class="table table-sm sa-table align-middle"><thead><tr>@foreach(array_keys($rowArray($panelRows->first())) as $col)<th>{{ str_replace('_',' ', $col) }}</th>@endforeach</tr></thead><tbody>@foreach($panelRows as $row)@php $r=$rowArray($row); @endphp<tr>@foreach($rowArray($panelRows->first()) as $col => $_)<td>{{ is_scalar($r[$col] ?? null) || is_null($r[$col] ?? null) ? ($r[$col] ?? '') : json_encode($r[$col]) }}</td>@endforeach</tr>@endforeach</tbody></table></div>@endif</section>
        @endif

        @if($isPaginator && $panel !== 'overview')<div class="mt-3">{{ $panelData->links() }}</div>@endif
    </div>
</div>
@endsection
