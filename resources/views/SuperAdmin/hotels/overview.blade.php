@extends('layout.mainlayout')

@section('style')
<style>
    .sa-hotel { background:#eef3f8; color:#09213d; }
    .sa-hero { background:linear-gradient(135deg,#06264a,#0b5fb8 58%,#0f766e); color:#fff; border-radius:18px; padding:22px; margin-bottom:16px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; box-shadow:0 18px 36px rgba(8,47,73,.18); }
    .sa-hero h2 { color:#fff; margin:0; font-size:31px; font-weight:900; }
    .sa-hero small { color:#d9eaff; text-transform:uppercase; letter-spacing:.14em; font-weight:900; }
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
    .sa-empty { border:1px dashed #afbdd0; border-radius:12px; padding:22px; background:#f8fafc; color:#64748b; }
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
    .sa-report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .sa-report { min-height:160px; background:#082f55; color:#fff; border-radius:14px; padding:18px; text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; }
    .sa-report span { color:#f1c15c; letter-spacing:.12em; text-transform:uppercase; font-size:12px; font-weight:900; }
    .sa-health { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .sa-health-row { display:flex; justify-content:space-between; align-items:center; padding:14px; border:1px solid #dbe4ef; border-radius:12px; background:#fff; }
    @media(max-width:1199px){.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid,.sa-service-grid,.sa-profile-grid,.sa-health{grid-template-columns:repeat(2,1fr)}.sa-workspace,.sa-cashier{grid-template-columns:1fr}.sa-board-row{grid-template-columns:1fr}}
    @media(max-width:767px){.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid,.sa-service-grid,.sa-profile-grid,.sa-health{grid-template-columns:1fr}.sa-hero h2{font-size:23px}}
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

        @if($panel === 'overview')
            <div class="sa-workspace">
                <aside class="sa-rail"><h5>Enterprise Monitor</h5><a href="{{ route('super_admin.hotels.index', ['panel' => 'rooms']) }}"><strong>Room Rack</strong><br>Availability, occupied and reserved</a><a href="{{ route('super_admin.hotels.index', ['panel' => 'reservations']) }}"><strong>Reservations</strong><br>Booking pipeline and assignments</a><a href="{{ route('super_admin.hotels.index', ['panel' => 'folios']) }}"><strong>Cashier</strong><br>Folios and receivables</a><a href="{{ route('super_admin.hotels.index', ['panel' => 'reports']) }}"><strong>Reports</strong><br>Platform PMS intelligence</a></aside>
                <main>
                    <div class="sa-service-grid">
                        <a class="sa-service" href="{{ route('super_admin.hotels.index', ['panel' => 'revenue']) }}"><span>Restaurant</span><h5>Restaurant POS</h5><p class="text-muted mb-0">Food sales and room-posted meals.</p></a>
                        <a class="sa-service" href="{{ route('super_admin.hotels.index', ['panel' => 'revenue']) }}"><span>Bar</span><h5>Bar & Lounge</h5><p class="text-muted mb-0">Drinks and lounge revenue.</p></a>
                        <a class="sa-service" href="{{ route('super_admin.hotels.index', ['panel' => 'revenue']) }}"><span>Spa/Gym</span><h5>Wellness Centers</h5><p class="text-muted mb-0">Spa, gym and fitness charges.</p></a>
                        <a class="sa-service" href="{{ route('super_admin.hotels.index', ['panel' => 'revenue']) }}"><span>Events</span><h5>Conference</h5><p class="text-muted mb-0">Events and banquet revenue.</p></a>
                    </div>
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
            <div class="sa-workspace"><aside class="sa-rail"><h5>Room State</h5><div><strong>{{ $availableRooms }}</strong><br>Available</div><div><strong>{{ $occupiedRooms }}</strong><br>Occupied</div><div><strong>{{ $reservedRooms }}</strong><br>Reserved</div><div><strong>{{ $totalRooms }}</strong><br>Total Rooms</div></aside><section class="sa-panel p-3"><h4 class="mb-3">Global Room Rack</h4>@if($panelRows->isEmpty())<div class="sa-empty">No rooms found yet. The rack will show each tenant room as a colored operational tile.</div>@else<div class="sa-room-rack">@foreach($panelRows as $row)@php $r=$rowArray($row); $state=(string)($r['operational_status'] ?? 'available'); @endphp<div class="sa-room {{ $state }}"><div class="d-flex justify-content-between"><span>{{ ucfirst(str_replace('_',' ', $state)) }}</span><span>⋮ □</span></div><div class="sa-room-num">{{ $r['room_number'] ?? ('#'.($r['id'] ?? '-')) }}</div><div class="small text-muted">Company {{ $r['company_id'] ?? '-' }} · Property {{ $r['property_id'] ?? '-' }}</div><div class="small">{{ ucfirst((string)($r['housekeeping_status'] ?? 'clean')) }} · Type {{ $r['room_type_id'] ?? '-' }}</div></div>@endforeach</div>@endif</section></div>
        @elseif($panel === 'room_types')
            <section class="sa-grid">@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-card"><span class="label">Room Type</span><h5>{{ $r['name'] ?? ('Type #'.($r['id'] ?? '-')) }}</h5><p class="text-muted">Company {{ $r['company_id'] ?? '-' }} · Property {{ $r['property_id'] ?? '-' }}</p><div class="d-flex justify-content-between"><span>Occupancy</span><strong>{{ $r['max_occupancy'] ?? '-' }}</strong></div><div class="d-flex justify-content-between"><span>Base Rate</span><strong>{{ $money($r['base_rate'] ?? 0) }}</strong></div></div>@empty<div class="sa-empty">No room types found. Room type cards will show occupancy and rate catalogue.</div>@endforelse</section>
        @elseif($panel === 'reservations')
            <section class="sa-panel p-3"><h4>Reservation Calendar Board</h4><div class="sa-calendar"><table class="table table-bordered"><thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Arrival</th><th>Departure</th><th>Status</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td><div class="sa-event {{ ($r['status'] ?? '') === 'confirmed' ? 'green' : 'gold' }}">{{ $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</div></td><td>Guest #{{ $r['customer_id'] ?? '-' }}</td><td>{{ $r['room_id'] ?? 'Unassigned' }}</td><td>{{ $r['arrival_date'] ?? '-' }}</td><td>{{ $r['departure_date'] ?? '-' }}</td><td><span class="badge {{ $statusBadge($r['status'] ?? 'reserved') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'reserved'))) }}</span></td></tr>@empty<tr><td colspan="6"><div class="sa-empty">No reservations found. This panel is the platform-level reservation operations board.</div></td></tr>@endforelse</tbody></table></div></section>
        @elseif($panel === 'stays')
            <section class="sa-panel sa-row-list"><div class="p-3 border-bottom"><h4 class="mb-0">In-House Guest Control</h4></div>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-board-row"><div><strong>Stay #{{ $r['id'] ?? '-' }}</strong><div class="small text-muted">Company {{ $r['company_id'] ?? '-' }}</div></div><div>Guest #{{ $r['customer_id'] ?? '-' }} · Room #{{ $r['room_id'] ?? 'N/A' }}</div><div>{{ $r['checkin_at'] ?? '-' }}</div><div><span class="badge {{ $statusBadge($r['status'] ?? 'checked_in') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'checked_in'))) }}</span></div></div>@empty<div class="p-4"><div class="sa-empty">No current stays found. This panel becomes an in-house guest register once rooms are occupied.</div></div>@endforelse</section>
        @elseif($panel === 'guests')
            <section class="sa-profile-grid">@forelse($panelRows as $row)@php $r=$rowArray($row); $name=$r['customer_name'] ?? $r['name'] ?? 'Guest'; @endphp<div class="sa-profile"><div class="sa-avatar">{{ strtoupper(substr((string)$name,0,1)) }}</div><div><h5 class="mb-1">{{ $name }}</h5><div class="text-muted small">{{ $r['phone'] ?? 'No phone' }} · {{ $r['email'] ?? 'No email' }}</div><span class="badge bg-light text-dark mt-2">Guest #{{ $r['id'] ?? '-' }}</span></div></div>@empty<div class="sa-empty">No guest profiles found. Guest CRM cards will appear here when hotel reservations/stays exist.</div>@endforelse</section>
        @elseif(in_array($panel, ['folios','deposits','revenue','hotel_transactions'], true))
            <section class="sa-cashier"><aside class="sa-cashier-side"><small>{{ strtoupper($panelTitle) }}</small><h3>{{ $money($outstandingReceivables) }}</h3><p>Outstanding receivables monitored from tenant folios and hotel transactions.</p></aside><main class="sa-panel p-3"><h4>Cashier Ledger</h4><div class="table-responsive"><table class="table table-sm sa-table align-middle mb-0"><thead><tr><th>Record</th><th>Company</th><th>Guest/Stay</th><th>Status/Type</th><th>Amount</th><th>Date</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td>{{ $r['folio_number'] ?? $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</td><td>{{ $r['company_id'] ?? '-' }}</td><td>{{ $r['customer_id'] ?? $r['stay_id'] ?? '-' }}</td><td>{{ $r['status'] ?? $r['type'] ?? $r['service_code'] ?? '-' }}</td><td>{{ $money($r['balance'] ?? $r['amount'] ?? $r['deposit_received'] ?? $r['total_amount'] ?? 0) }}</td><td>{{ $r['created_at'] ?? $r['service_date'] ?? $r['business_date'] ?? '-' }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No {{ strtolower($panelTitle) }} found.</td></tr>@endforelse</tbody></table></div></main><aside class="sa-pad"><div>Payment</div><div>Charge</div><div>Deposit</div><div>Transfer</div><div>POS</div><div>City Ledger</div></aside></section>
        @elseif(in_array($panel, ['housekeeping','maintenance','night_audits'], true))
            <section class="sa-kanban">@foreach(['open'=>'Open','assigned'=>'Assigned / Active','completed'=>'Completed','blocked'=>'Attention'] as $laneKey => $laneLabel)<div class="sa-lane"><h5>{{ $laneLabel }}</h5>@php $laneRows = $panelRows->filter(function($row) use ($rowArray,$laneKey){$r=$rowArray($row);$status=(string)($r['status'] ?? $r['severity'] ?? 'open');return $laneKey === 'blocked' ? in_array($status,['high','critical','failed','pending'],true) : ($laneKey === 'assigned' ? in_array($status,['assigned','in_progress','cleaning','inspection','running'],true) : ($laneKey === 'completed' ? in_array($status,['completed','resolved','closed'],true) : in_array($status,['open','new','pending'],true)));}); @endphp @forelse($laneRows->take(8) as $row)@php $r=$rowArray($row); @endphp<div class="sa-ticket {{ in_array(($r['severity'] ?? $r['status'] ?? ''), ['high','critical','failed'], true) ? 'danger' : '' }}"><strong>{{ $r['ticket_no'] ?? $r['task_no'] ?? $r['audit_date'] ?? ('#'.($r['id'] ?? '-')) }}</strong><div class="small text-muted">Room {{ $r['room_id'] ?? '-' }} · Company {{ $r['company_id'] ?? '-' }}</div><div>{{ $r['title'] ?? $r['description'] ?? $r['status'] ?? 'Hotel operation record' }}</div></div>@empty<div class="text-muted small">No {{ strtolower($laneLabel) }} items.</div>@endforelse</div>@endforeach</section>
        @elseif($panel === 'reports')
            <section class="sa-report-grid"><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'reservations']) }}"><span>Front Office</span><h4>Reservation Register</h4><p>All hotel reservation activity by tenant.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'folios']) }}"><span>Finance</span><h4>Folio Receivables</h4><p>Guest balances and folio exposure.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'revenue']) }}"><span>Revenue</span><h4>Hotel Revenue</h4><p>Daily totals and transaction count.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'housekeeping']) }}"><span>Rooms</span><h4>Housekeeping</h4><p>Room readiness workload.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'maintenance']) }}"><span>Engineering</span><h4>Maintenance</h4><p>Tickets and unavailable rooms.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'night_audits']) }}"><span>Audit</span><h4>Night Audits</h4><p>Business day close history.</p></a></section>
        @elseif($panel === 'settings')
            <section class="sa-health">@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-health-row"><div><strong>{{ str_replace('_',' ', $r['setting'] ?? 'Setting') }}</strong><div class="small text-muted">Tenant PMS dependency</div></div><span class="badge {{ ($r['status'] ?? '') === 'available' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst((string)($r['status'] ?? 'missing')) }}</span></div>@empty<div class="sa-empty">No hotel settings found.</div>@endforelse</section>
        @else
            <section class="sa-panel p-3"><h4>{{ $panelTitle }}</h4>@if($panelRows->isEmpty())<div class="sa-empty">No {{ strtolower($panelTitle) }} found.</div>@else<div class="table-responsive"><table class="table table-sm sa-table align-middle"><thead><tr>@foreach(array_keys($rowArray($panelRows->first())) as $col)<th>{{ str_replace('_',' ', $col) }}</th>@endforeach</tr></thead><tbody>@foreach($panelRows as $row)@php $r=$rowArray($row); @endphp<tr>@foreach($rowArray($panelRows->first()) as $col => $_)<td>{{ is_scalar($r[$col] ?? null) || is_null($r[$col] ?? null) ? ($r[$col] ?? '') : json_encode($r[$col]) }}</td>@endforeach</tr>@endforeach</tbody></table></div>@endif</section>
        @endif

        @if($isPaginator && $panel !== 'overview')<div class="mt-3">{{ $panelData->links() }}</div>@endif
    </div>
</div>
@endsection
