@extends('layout.mainlayout')

@section('style')
<style>
    .sa-hotel { background:#eef3f8; color:#09213d; }
    .sa-hero { background:linear-gradient(135deg,#05284f,#0b5fb8); color:#fff; border-radius:18px; padding:22px; margin-bottom:16px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .sa-hero h2 { color:#fff; margin:0; font-size:30px; font-weight:900; }
    .sa-hero small { color:#d9eaff; text-transform:uppercase; letter-spacing:.14em; font-weight:900; }
    .sa-panel, .sa-card, .sa-filter { background:#fff; border:1px solid #d8e2ee; border-radius:14px; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .sa-tabs { display:flex; gap:8px; overflow:auto; padding:12px; margin-bottom:16px; }
    .sa-tabs a { white-space:nowrap; border:1px solid #cbd8e8; border-radius:999px; padding:8px 13px; color:#0b2f54; text-decoration:none; font-weight:800; }
    .sa-tabs a.active { background:#0b5fb8; color:#fff; border-color:#0b5fb8; }
    .sa-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .sa-kpi { padding:16px; position:relative; overflow:hidden; }
    .sa-kpi:before { content:''; position:absolute; inset:0 auto 0 0; width:5px; background:#0b5fb8; }
    .sa-kpi.green:before { background:#16a34a; } .sa-kpi.gold:before { background:#d4a23a; } .sa-kpi.red:before { background:#dc2626; }
    .sa-kpi strong { display:block; font-size:30px; line-height:1; margin-top:8px; }
    .sa-filter { padding:14px; margin-bottom:16px; }
    .sa-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .sa-card { padding:16px; }
    .sa-card h5 { margin-bottom:8px; }
    .sa-room-rack { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:10px; }
    .sa-room { min-height:130px; border-radius:8px; border:1px solid #d8e2ee; background:#fff; padding:10px; position:relative; }
    .sa-room.available { background:#ecfdf3; } .sa-room.occupied { background:#e8f2ff; } .sa-room.reserved { background:#fff8e5; } .sa-room.maintenance, .sa-room.out_of_order { background:#fff1f2; }
    .sa-room-num { font-size:32px; font-weight:300; color:#0b5fb8; }
    .sa-board-row { display:grid; grid-template-columns:150px minmax(0,1fr) 150px 150px; gap:12px; align-items:center; padding:13px 16px; border-bottom:1px solid #edf1f6; }
    .sa-board-row:last-child { border-bottom:0; }
    .sa-table th { background:#0c3f70; color:#fff; border:0; text-transform:uppercase; font-size:12px; }
    .sa-kanban { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .sa-lane { background:#f8fafc; border:1px solid #dbe4ef; border-radius:12px; padding:10px; min-height:240px; }
    .sa-lane h5 { font-size:14px; text-transform:uppercase; letter-spacing:.08em; color:#475569; }
    .sa-ticket { background:#fff; border:1px solid #e5eaf2; border-left:5px solid #0b5fb8; border-radius:10px; padding:10px; margin-bottom:9px; }
    .sa-ticket.danger { border-left-color:#dc2626; } .sa-ticket.gold { border-left-color:#d4a23a; } .sa-ticket.green { border-left-color:#16a34a; }
    .sa-report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .sa-report { min-height:150px; background:#082f55; color:#fff; border-radius:14px; padding:18px; text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; }
    .sa-report span { color:#f1c15c; letter-spacing:.12em; text-transform:uppercase; font-size:12px; font-weight:900; }
    .sa-row-list .sa-board-row:nth-child(even) { background:#f8fafc; }
    @media(max-width:1199px){.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid{grid-template-columns:repeat(2,1fr)}.sa-board-row{grid-template-columns:1fr}}
    @media(max-width:767px){.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid{grid-template-columns:1fr}.sa-hero h2{font-size:23px}}
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
@endphp
<div class="page-wrapper sa-hotel">
    <div class="content container-fluid">
        <section class="sa-hero">
            <div><small>Super Admin Hotel Management</small><h2>{{ $panels[$panel] ?? 'Hotel Dashboard' }}</h2><p class="mb-0">Global hotel tenants, properties, room operations, folios, reports and PMS health.</p></div>
            <div class="d-flex flex-wrap gap-2 align-self-start"><a href="{{ route('super_admin.hotels.index') }}" class="btn btn-light">Dashboard</a><span class="btn btn-warning disabled text-dark">{{ strtoupper($panel) }}</span></div>
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
            <div class="sa-panel sa-kpi"><span>Total Hotel Tenants</span><strong>{{ $totalHotelTenants }}</strong></div>
            <div class="sa-panel sa-kpi green"><span>Active Subscriptions</span><strong>{{ $activeHotelSubscriptions }}</strong></div>
            <div class="sa-panel sa-kpi gold"><span>Total Properties</span><strong>{{ $totalProperties }}</strong></div>
            <div class="sa-panel sa-kpi red"><span>Total Rooms</span><strong>{{ $totalRooms }}</strong></div>
            <div class="sa-panel sa-kpi green"><span>Available Rooms</span><strong>{{ $availableRooms }}</strong></div>
            <div class="sa-panel sa-kpi"><span>Occupied Rooms</span><strong>{{ $occupiedRooms }}</strong></div>
            <div class="sa-panel sa-kpi gold"><span>Reserved Rooms</span><strong>{{ $reservedRooms }}</strong></div>
            <div class="sa-panel sa-kpi"><span>Reservations Today</span><strong>{{ $todayReservations }}</strong></div>
        </div>

        @if($panel === 'overview')
            <div class="sa-grid">
                <a class="sa-card text-decoration-none" href="{{ route('super_admin.hotels.index', ['panel' => 'tenants']) }}"><h5>Tenant Control</h5><p class="text-muted mb-0">Review hotel-enabled companies and subscription status.</p></a>
                <a class="sa-card text-decoration-none" href="{{ route('super_admin.hotels.index', ['panel' => 'rooms']) }}"><h5>Room Rack</h5><p class="text-muted mb-0">Global room state across properties.</p></a>
                <a class="sa-card text-decoration-none" href="{{ route('super_admin.hotels.index', ['panel' => 'reservations']) }}"><h5>Reservation Operations</h5><p class="text-muted mb-0">Monitor booking pipeline, arrivals and dates.</p></a>
                <a class="sa-card text-decoration-none" href="{{ route('super_admin.hotels.index', ['panel' => 'folios']) }}"><h5>Cashier / Folios</h5><p class="text-muted mb-0">Track guest balances and receivables.</p></a>
                <a class="sa-card text-decoration-none" href="{{ route('super_admin.hotels.index', ['panel' => 'housekeeping']) }}"><h5>Housekeeping</h5><p class="text-muted mb-0">Cleaning and room-readiness workload.</p></a>
                <a class="sa-card text-decoration-none" href="{{ route('super_admin.hotels.index', ['panel' => 'reports']) }}"><h5>Reports</h5><p class="text-muted mb-0">Revenue and operating reports by tenant.</p></a>
            </div>
        @elseif(in_array($panel, ['rooms','room_types'], true))
            <section class="sa-panel p-3"><h4 class="mb-3">{{ $panel === 'rooms' ? 'Global Room Rack' : 'Room Type Catalogue' }}</h4>@if($panelRows->isEmpty())<div class="alert alert-info mb-0">No {{ strtolower($panels[$panel]) }} found.</div>@else<div class="sa-room-rack">@foreach($panelRows as $row)@php $r=$rowArray($row); $state=(string)($r['operational_status'] ?? 'available'); @endphp<div class="sa-room {{ $state }}"><div class="d-flex justify-content-between"><span>{{ ucfirst(str_replace('_',' ', $state)) }}</span><span>□</span></div><div class="sa-room-num">{{ $r['room_number'] ?? $r['name'] ?? ('#'.($r['id'] ?? '-')) }}</div><div class="small text-muted">Company {{ $r['company_id'] ?? '-' }} · Property {{ $r['property_id'] ?? '-' }}</div><div class="small">{{ $r['housekeeping_status'] ?? ($r['max_occupancy'] ?? 'PMS item') }}</div></div>@endforeach</div>@endif</section>
        @elseif(in_array($panel, ['reservations','stays'], true))
            <section class="sa-panel sa-row-list"><div class="p-3 border-bottom"><h4 class="mb-0">{{ $panel === 'reservations' ? 'Reservation Operations Board' : 'Current Stay Board' }}</h4></div>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-board-row"><div><strong>{{ $r['reservation_number'] ?? ('Stay #'.($r['id'] ?? '-')) }}</strong><div class="small text-muted">Company {{ $r['company_id'] ?? '-' }}</div></div><div>Guest #{{ $r['customer_id'] ?? '-' }} · Room #{{ $r['room_id'] ?? 'Unassigned' }}</div><div>{{ $r['arrival_date'] ?? $r['checkin_at'] ?? $r['created_at'] ?? '-' }}</div><div><span class="badge bg-primary">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'active'))) }}</span></div></div>@empty<div class="p-4 text-muted">No {{ strtolower($panels[$panel]) }} found.</div>@endforelse</section>
        @elseif(in_array($panel, ['folios','deposits','revenue','hotel_transactions'], true))
            <section class="sa-panel p-3"><h4>{{ $panels[$panel] }} Cashier Ledger</h4><div class="table-responsive"><table class="table table-sm sa-table align-middle mb-0"><thead><tr><th>Record</th><th>Company</th><th>Guest/Stay</th><th>Status/Type</th><th>Amount</th><th>Date</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td>{{ $r['folio_number'] ?? $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</td><td>{{ $r['company_id'] ?? '-' }}</td><td>{{ $r['customer_id'] ?? $r['stay_id'] ?? '-' }}</td><td>{{ $r['status'] ?? $r['type'] ?? $r['service_code'] ?? '-' }}</td><td>{{ $money($r['balance'] ?? $r['amount'] ?? $r['deposit_received'] ?? $r['total_amount'] ?? 0) }}</td><td>{{ $r['created_at'] ?? $r['service_date'] ?? $r['business_date'] ?? '-' }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No {{ strtolower($panels[$panel]) }} found.</td></tr>@endforelse</tbody></table></div></section>
        @elseif(in_array($panel, ['housekeeping','maintenance','night_audits'], true))
            <section class="sa-kanban">@foreach(['open'=>'Open','assigned'=>'Assigned / Active','completed'=>'Completed','blocked'=>'Attention'] as $laneKey => $laneLabel)<div class="sa-lane"><h5>{{ $laneLabel }}</h5>@foreach($panelRows->filter(function($row) use ($rowArray,$laneKey){$r=$rowArray($row);$status=(string)($r['status'] ?? $r['severity'] ?? 'open');return $laneKey === 'blocked' ? in_array($status,['high','critical','failed','pending'],true) : ($laneKey === 'assigned' ? in_array($status,['assigned','in_progress','cleaning','inspection','running'],true) : ($laneKey === 'completed' ? in_array($status,['completed','resolved','closed'],true) : in_array($status,['open','new','pending'],true)));})->take(8) as $row)@php $r=$rowArray($row); @endphp<div class="sa-ticket {{ in_array(($r['severity'] ?? $r['status'] ?? ''), ['high','critical','failed'], true) ? 'danger' : '' }}"><strong>{{ $r['ticket_no'] ?? $r['task_no'] ?? $r['audit_date'] ?? ('#'.($r['id'] ?? '-')) }}</strong><div class="small text-muted">Room {{ $r['room_id'] ?? '-' }} · Company {{ $r['company_id'] ?? '-' }}</div><div>{{ $r['title'] ?? $r['description'] ?? $r['status'] ?? 'Hotel operation record' }}</div></div>@endforeach</div>@endforeach</section>
        @elseif($panel === 'reports')
            <section class="sa-report-grid"><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'reservations']) }}"><span>Front Office</span><h4>Reservation Register</h4><p>All hotel reservation activity by tenant.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'folios']) }}"><span>Finance</span><h4>Folio Receivables</h4><p>Guest balances and folio exposure.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'revenue']) }}"><span>Revenue</span><h4>Hotel Revenue</h4><p>Daily totals and transaction count.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'housekeeping']) }}"><span>Rooms</span><h4>Housekeeping</h4><p>Room readiness workload.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'maintenance']) }}"><span>Engineering</span><h4>Maintenance</h4><p>Tickets and unavailable rooms.</p></a><a class="sa-report" href="{{ route('super_admin.hotels.index', ['panel'=>'night_audits']) }}"><span>Audit</span><h4>Night Audits</h4><p>Business day close history.</p></a></section>
        @else
            <section class="sa-panel p-3"><h4>{{ $panels[$panel] ?? ucfirst($panel) }}</h4>@if($panelRows->isEmpty())<div class="alert alert-info mb-0">No {{ strtolower($panels[$panel] ?? $panel) }} found.</div>@else<div class="table-responsive"><table class="table table-sm sa-table align-middle"><thead><tr>@foreach(array_keys($rowArray($panelRows->first())) as $col)<th>{{ str_replace('_',' ', $col) }}</th>@endforeach</tr></thead><tbody>@foreach($panelRows as $row)@php $r=$rowArray($row); @endphp<tr>@foreach($rowArray($panelRows->first()) as $col => $_)<td>{{ is_scalar($r[$col] ?? null) || is_null($r[$col] ?? null) ? ($r[$col] ?? '') : json_encode($r[$col]) }}</td>@endforeach</tr>@endforeach</tbody></table></div>@endif</section>
        @endif

        @if($isPaginator && $panel !== 'overview')<div class="mt-3">{{ $panelData->links() }}</div>@endif
    </div>
</div>
@endsection
