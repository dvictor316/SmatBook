@extends('layout.mainlayout')

@section('style')
<style>
    .spb-hotel-enterprise { background:#edf3f9; color:#10233f; }
    .hotel-hero { position:relative; overflow:hidden; border-radius:22px; padding:24px; margin-bottom:18px; background:radial-gradient(circle at 88% 12%,rgba(244,190,80,.45),transparent 24%),linear-gradient(135deg,#051f3d,#073f78 55%,#0f766e); color:#fff; box-shadow:0 22px 50px rgba(5,31,61,.22); }
    .hotel-hero:after { content:''; position:absolute; inset:auto -80px -120px auto; width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,.11); }
    .hotel-hero-grid { position:relative; z-index:1; display:grid; grid-template-columns:minmax(0,1.2fr) minmax(340px,.8fr); gap:18px; align-items:end; }
    .hotel-eyebrow { color:#f5d37c; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:12px; }
    .hotel-hero h2 { color:#fff; font-size:34px; font-weight:950; margin:4px 0 8px; }
    .hotel-hero p { color:#d9eaff; margin:0; }
    .hotel-command-actions { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .hotel-command-actions a { min-height:78px; display:flex; flex-direction:column; gap:6px; align-items:center; justify-content:center; text-align:center; color:#fff; text-decoration:none; font-weight:700; border-radius:14px; border:1px solid rgba(255,255,255,.26); background:rgba(255,255,255,.12); }
    .hotel-command-actions i { font-size:20px; }
    .hotel-command-actions a:hover { color:#fff; background:rgba(255,255,255,.22); }
    .hotel-panel { background:#fff; border:1px solid #d6e1ee; border-radius:18px; box-shadow:0 12px 32px rgba(15,23,42,.06); }
    .hotel-filter { display:flex; flex-wrap:wrap; gap:10px; justify-content:flex-end; padding:14px; margin-bottom:18px; }
    .hotel-kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
    .hotel-kpi { position:relative; overflow:hidden; min-height:136px; padding:17px; text-decoration:none; color:#10233f; }
    .hotel-kpi:before { content:''; position:absolute; inset:0 auto 0 0; width:6px; background:#0b5fb8; }
    .hotel-kpi.green:before { background:#16a34a; } .hotel-kpi.gold:before { background:#d4a23a; } .hotel-kpi.red:before { background:#dc2626; }
    .hotel-kpi span { color:#64748b; text-transform:uppercase; letter-spacing:.06em; font-size:12px; font-weight:700; }
    .hotel-kpi strong { display:block; font-size:34px; line-height:1; margin:10px 0 8px; }
    .hotel-kpi small { color:#64748b; }
    .hotel-suite { display:grid; grid-template-columns:240px minmax(0,1fr) 350px; gap:16px; margin-bottom:18px; }
    .hotel-left-nav { background:#082f55; color:#dbeafe; border-radius:18px; padding:14px; align-self:start; }
    .hotel-left-nav h5 { color:#fff; font-size:14px; text-transform:uppercase; letter-spacing:.12em; margin-bottom:12px; }
    .hotel-left-nav a { display:block; color:#dbeafe; text-decoration:none; padding:12px 10px; border-radius:10px; border:1px solid rgba(255,255,255,.1); margin-bottom:8px; }
    .hotel-left-nav a strong { color:#fff; }
    .hotel-desk-header { padding:16px; border-bottom:1px solid #e6edf5; display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; }
    .hotel-desk-list { padding:0 16px 12px; }
    .hotel-res-row { display:grid; grid-template-columns:90px minmax(0,1fr) 120px 120px 110px; gap:12px; align-items:center; padding:13px 0; border-bottom:1px solid #edf2f7; }
    .hotel-res-row:last-child { border-bottom:0; }
    .hotel-res-type { border-radius:999px; padding:7px 10px; font-weight:700; font-size:12px; text-align:center; background:#eff6ff; color:#1d4ed8; }
    .hotel-res-type.departure { background:#fff7ed; color:#9a4b06; }
    .hotel-room-pill { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:6px 10px; background:#f8fafc; border:1px solid #e3ebf3; font-weight:600; }
    .hotel-alert { display:flex; align-items:center; justify-content:space-between; gap:12px; border-radius:12px; padding:12px; text-decoration:none; color:#10233f; margin-bottom:10px; border:1px solid #d9e2ee; background:#f8fafc; }
    .hotel-alert[data-tone="danger"] { background:#fff1f2; border-color:#fecaca; color:#8a1010; }
    .hotel-alert[data-tone="warning"] { background:#fff7ed; border-color:#fed7aa; color:#8a4b08; }
    .hotel-alert[data-tone="info"] { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
    .hotel-board { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(360px,.75fr); gap:16px; margin-bottom:18px; }
    .hotel-chart-card { padding:16px; }
    .hotel-room-state { padding:16px; }
    .hotel-status-row, .hotel-finance-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #edf2f7; color:#10233f; text-decoration:none; }
    .hotel-status-row:last-child, .hotel-finance-row:last-child { border-bottom:0; }
    .hotel-status-dot { width:11px; height:11px; border-radius:50%; display:inline-block; margin-right:8px; background:#0b5fb8; }
    .hotel-status-dot.available { background:#16a34a; } .hotel-status-dot.reserved { background:#d4a23a; } .hotel-status-dot.dirty { background:#dc2626; } .hotel-status-dot.cleaning { background:#0ea5e9; } .hotel-status-dot.maintenance { background:#f97316; } .hotel-status-dot.out_of_order { background:#334155; }
    .hotel-service-centers { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
    .hotel-service-card { position:relative; min-height:150px; overflow:hidden; padding:17px; color:#10233f; text-decoration:none; }
    .hotel-service-card:after { content:''; position:absolute; right:-34px; bottom:-44px; width:118px; height:118px; border-radius:50%; background:rgba(11,95,184,.1); }
    .hotel-service-card span { color:#d4a23a; text-transform:uppercase; letter-spacing:.12em; font-size:12px; font-weight:700; }
    .hotel-service-card h5 { margin:10px 0 8px; font-weight:700; }
    .hotel-service-card p { color:#64748b; margin:0; }
    .hotel-bottom { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
    .hotel-bottom .hotel-panel { padding:16px; }
    .hotel-source-chip { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #edf2f7; }
    .hotel-source-chip:last-child { border-bottom:0; }
    @media(max-width:1399px){.hotel-suite{grid-template-columns:1fr}.hotel-left-nav{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.hotel-left-nav a{margin:0}.hotel-left-nav h5{grid-column:1/-1}.hotel-board{grid-template-columns:1fr}}
    @media(max-width:1199px){.hotel-hero-grid,.hotel-kpi-grid,.hotel-bottom,.hotel-service-centers{grid-template-columns:repeat(2,1fr)}.hotel-command-actions{grid-template-columns:repeat(2,1fr)}.hotel-res-row{grid-template-columns:1fr}}
    @media(max-width:767px){.hotel-hero-grid,.hotel-kpi-grid,.hotel-bottom,.hotel-service-centers,.hotel-command-actions,.hotel-left-nav{grid-template-columns:1fr}.hotel-hero h2{font-size:25px}}
</style>
@endsection

@section('content')
<div class="page-wrapper spb-hotel-enterprise">
    <div class="content container-fluid">
        <section class="hotel-hero">
            <div class="hotel-hero-grid">
                <div>
                    <div class="hotel-eyebrow">SmartProbook Hotel PMS · Enterprise Command</div>
                    <h2>Run front desk, rooms, guests, and hotel accounting from one screen.</h2>
                    <p>{{ $property?->name ?? 'All Properties' }} · Business Date {{ now()->format('d M Y') }} · {{ ucfirst(str_replace('_',' ', $rangeKey)) }} performance view.</p>
                </div>
                <div class="hotel-command-actions">
                    <a href="{{ route('hotel.reservations.create') }}"><i class="fe fe-calendar"></i><span>New Reservation</span></a>
                    <a href="{{ route('hotel.walkin.create') }}"><i class="fe fe-user-plus"></i><span>Walk-In</span></a>
                    <a href="{{ route('hotel.rooms.create') }}"><i class="fe fe-plus-square"></i><span>Add Room</span></a>
                    <a href="{{ route('customers.add') }}"><i class="fe fe-user"></i><span>Add Guest</span></a>
                    <a href="{{ route('hotel.checkin.index') }}"><i class="fe fe-log-in"></i><span>Check In</span></a>
                    <a href="{{ route('hotel.rooms.calendar') }}"><i class="fe fe-grid"></i><span>Room Calendar</span></a>
                    <a href="{{ route('hotel.checkout.index') }}"><i class="fe fe-credit-card"></i><span>Cashier Desk</span></a>
                    <a href="{{ route('hotel.service_centers.show', 'bar') }}"><i class="fe fe-shopping-cart"></i><span>Bar Order</span></a>
                    <a href="{{ route('hotel.service_centers.show', 'spa') }}"><i class="fe fe-heart"></i><span>Spa Booking</span></a>
                    <a href="{{ route('hotel.service_centers.show', 'gym') }}"><i class="fe fe-activity"></i><span>Gym Access</span></a>
                    <a href="{{ route('hotel.service_centers.show', 'ticketing') }}"><i class="fe fe-ticket"></i><span>Ticket/Event</span></a>
                    <a href="{{ route('hotel.rate_plans.index') }}"><i class="fe fe-tag"></i><span>Rate Plans</span></a>
                    <a href="{{ route('hotel.maintenance.index') }}"><i class="fe fe-tool"></i><span>Maintenance</span></a>
                    <a href="{{ route('hotel.setup.step1') }}"><i class="fe fe-settings"></i><span>Hotel Setup</span></a>
                    <a href="{{ route('hotel.reports.index') }}"><i class="fe fe-bar-chart-2"></i><span>Reports</span></a>
                </div>
            </div>
        </section>

        @include('hotel.partials.operations-action-deck', [
            'context' => 'dashboard',
            'title' => 'Today’s Hotel Command Deck',
            'subtitle' => 'Move from booking to check-in, service charges, payment, checkout, housekeeping and reports without hunting through menus.'
        ])

        <form method="GET" class="hotel-panel hotel-filter">
            <select name="property_id" class="form-select form-select-sm" style="max-width:220px">
                <option value="all" {{ !$propertyId ? 'selected' : '' }}>All Properties</option>
                @foreach($properties as $propertyOption)
                    <option value="{{ $propertyOption->id }}" {{ (int) $propertyId === (int) $propertyOption->id ? 'selected' : '' }}>{{ $propertyOption->name }}</option>
                @endforeach
            </select>
            <select name="range" class="form-select form-select-sm" style="max-width:170px">
                @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'last_7_days' => 'Last 7 Days', 'last_30_days' => 'Last 30 Days', 'this_month' => 'This Month', 'custom' => 'Custom Range'] as $key => $label)
                    <option value="{{ $key }}" {{ $rangeKey === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}" style="max-width:160px">
            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}" style="max-width:160px">
            <button class="btn btn-sm btn-primary">Refresh Dashboard</button>
        </form>

        <div class="hotel-kpi-grid">
            <a href="{{ route('hotel.rooms.status') }}" class="hotel-panel hotel-kpi"><span>Occupancy</span><strong>{{ number_format($occupancyRate, 1) }}%</strong><small>{{ $occupiedRooms }} of {{ $totalRooms }} rooms occupied</small></a>
            <a href="{{ route('hotel.rooms.index', ['status' => 'available']) }}" class="hotel-panel hotel-kpi green"><span>Available Rooms</span><strong>{{ $availableRooms }}</strong><small>Ready to sell now</small></a>
            <a href="{{ route('hotel.in_house') }}" class="hotel-panel hotel-kpi gold"><span>In-House Guests</span><strong>{{ $inHouseGuests }}</strong><small>Checked-in guest stays</small></a>
            <a href="{{ route('hotel.folios.index') }}" class="hotel-panel hotel-kpi red"><span>Outstanding Folios</span><strong>{{ number_format((float) $folioBalances, 2) }}</strong><small>Receivables linked to hotel accounting</small></a>
        </div>

        <div class="hotel-suite">
            <aside class="hotel-left-nav">
                <h5>Daily Workflow</h5>
                <a href="{{ route('hotel.frontdesk') }}"><strong>Front Desk</strong><br>Arrivals, departures, room board</a>
                <a href="{{ route('hotel.availability.index') }}"><strong>Availability</strong><br>Search and sell available rooms</a>
                <a href="{{ route('hotel.housekeeping.index') }}"><strong>Housekeeping</strong><br>Dirty rooms and assignments</a>
                <a href="{{ route('hotel.night_audit.index') }}"><strong>Night Audit</strong><br>Close business day</a>
            </aside>

            <main class="hotel-panel">
                <div class="hotel-desk-header">
                    <div><h5 class="mb-0">Today’s Reservation Desk</h5><small class="text-muted">Arrivals, departures, readiness and balance attention.</small></div>
                    <a href="{{ route('hotel.frontdesk') }}" class="btn btn-sm btn-outline-primary">Open Front Desk</a>
                </div>
                <div class="hotel-desk-list">
                    @forelse($arrivalsPanel->take(6) as $arrival)
                        <div class="hotel-res-row"><span class="hotel-res-type">Arrival</span><div><strong>{{ $arrival->customer?->customer_name ?? 'Walk-In' }}</strong><div class="small text-muted">{{ $arrival->roomType?->name ?? 'Room Type N/A' }}</div></div><div><span class="hotel-room-pill">Room {{ $arrival->room?->room_number ?? 'Unassigned' }}</span></div><div>{{ optional($arrival->arrival_date)->format('d M') }}</div><div>{{ (float) $arrival->deposit_received > 0 ? 'Deposit OK' : 'Deposit Due' }}</div></div>
                    @empty
                        <div class="text-muted py-3">No arrivals scheduled.</div>
                    @endforelse
                    @forelse($departuresPanel->take(6) as $departure)
                        @php $depBalance = (float) ($departure->balance ?? 0); @endphp
                        <div class="hotel-res-row"><span class="hotel-res-type departure">Departure</span><div><strong>{{ $departure->customer?->customer_name ?? 'Walk-In' }}</strong><div class="small text-muted">Checkout control</div></div><div><span class="hotel-room-pill">Room {{ $departure->room?->room_number ?? 'Unassigned' }}</span></div><div>{{ optional($departure->departure_date)->format('d M') }}</div><div class="{{ $depBalance > 0 ? 'text-danger' : 'text-success' }}">{{ $depBalance > 0 ? number_format($depBalance, 2).' due' : 'Clear' }}</div></div>
                    @empty
                    @endforelse
                </div>
            </main>

            <aside class="hotel-panel p-3">
                <h5>Action Alerts</h5>
                @forelse($managementAlerts as $alert)
                    <a href="{{ $alert['route'] }}" class="hotel-alert" data-tone="{{ $alert['tone'] }}"><span>{{ $alert['label'] }}</span><strong>{{ $alert['count'] }}</strong></a>
                @empty
                    <div class="alert alert-light mb-0">No active alerts. Operations look clean.</div>
                @endforelse
                <hr>
                <div class="hotel-finance-row"><span>Deposits Held</span><strong>{{ number_format((float) $reservationDeposits, 2) }}</strong></div>
                <div class="hotel-finance-row"><span>ADR</span><strong>{{ number_format((float) $adr, 2) }}</strong></div>
                <div class="hotel-finance-row"><span>RevPAR</span><strong>{{ number_format((float) $revpar, 2) }}</strong></div>
            </aside>
        </div>

        <section class="mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2"><div><h5 class="mb-0">Hotel Service Centers</h5><small class="text-muted">Restaurant, bar, gym/spa, room service and other chargeable hotel departments.</small></div><a href="{{ route('hotel.restaurant.pos') }}" class="btn btn-sm btn-outline-primary">Open Restaurant POS</a></div>
            <div class="hotel-service-centers">
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.restaurant.pos') }}"><span>Restaurant</span><h5>Restaurant POS</h5><p>Food sales, table service, room-posted meals and walk-in payments.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.service_centers.show', 'bar') }}"><span>Bar</span><h5>Bar & Lounge</h5><p>Drinks, lounge bills, guest folio postings and revenue tracking.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.service_centers.show', 'spa') }}"><span>Spa</span><h5>Spa & Wellness</h5><p>Treatments, packages and wellness services tied to folios.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.service_centers.show', 'gym') }}"><span>Gym</span><h5>Gym & Fitness</h5><p>Day passes, memberships and fitness service charges.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.service_centers.show', 'ticketing') }}"><span>Ticketing</span><h5>Events & Tickets</h5><p>Ticket sales, event access and guest room charge postings.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.room_service.index') }}"><span>Room Service</span><h5>In-Room Dining</h5><p>Kitchen orders posted directly to guest rooms.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.minibar.index') }}"><span>Minibar</span><h5>Minibar Control</h5><p>Room minibar usage and stock-linked charges.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.laundry.index') }}"><span>Laundry</span><h5>Laundry Desk</h5><p>Laundry orders, returns and guest billing activity.</p></a>
                <a class="hotel-panel hotel-service-card" href="{{ route('hotel.conference.index') }}"><span>Events</span><h5>Conference & Events</h5><p>Event bookings, meeting rooms and banquet-linked revenue.</p></a>
            </div>
        </section>

        <div class="hotel-board">
            <div class="hotel-panel hotel-chart-card"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2"><h5 class="mb-0">Forecast: occupancy and revenue</h5><span class="badge bg-light text-dark">{{ $fromDate }} to {{ $toDate }}</span></div><div id="hotel-occupancy-trend" style="min-height:330px"></div></div>
            <div class="hotel-panel hotel-room-state"><h5>Room State Stack</h5><div id="hotel-room-status-chart" style="min-height:210px"></div>@foreach($roomStatusBreakdown as $statusItem)<a class="hotel-status-row" href="{{ $statusItem['route'] }}"><span><i class="hotel-status-dot {{ $statusItem['key'] }}"></i>{{ $statusItem['label'] }}</span><strong>{{ $statusItem['count'] }}</strong></a>@endforeach</div>
        </div>

        <div class="hotel-bottom">
            <div class="hotel-panel"><h5>Revenue by Department</h5><div id="hotel-revenue-department" style="min-height:230px"></div></div>
            <div class="hotel-panel"><h5>Daily Activity Log</h5>@foreach($todayActivity as $label => $value)<div class="hotel-finance-row"><span>{{ ucwords(str_replace('_', ' ', $label)) }}</span><strong>{{ is_numeric($value) ? number_format((float) $value, is_float($value + 0) ? 2 : 0) : $value }}</strong></div>@endforeach</div>
            <div class="hotel-panel"><h5>Sources and Payments</h5>@forelse($reservationSourceRows as $source)<div class="hotel-source-chip"><span>{{ ucfirst((string) $source->source) }}</span><strong>{{ $source->total_count }}</strong></div>@empty<div class="text-muted">No reservation source data.</div>@endforelse<hr>@forelse($paymentMethodDistributionRows as $pay)<div class="hotel-source-chip"><span>{{ strtoupper((string) $pay->service_code) }}</span><strong>{{ number_format((float) $pay->total_amount, 2) }}</strong></div>@empty<div class="text-muted">No payment records.</div>@endforelse</div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('/assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
(function () {
    const occupancyLabels = @json(collect($occupancyTrend)->pluck('label')->values());
    const occupancyValues = @json(collect($occupancyTrend)->pluck('value')->values());
    const roomRevenueValues = @json(collect($revenueTrend)->pluck('room')->values());
    const otherRevenueValues = @json(collect($revenueTrend)->pluck('other')->values());
    const statusLabels = @json(collect($roomStatusBreakdown)->pluck('label')->values());
    const statusValues = @json(collect($roomStatusBreakdown)->pluck('count')->values());
    const trendEl = document.querySelector('#hotel-occupancy-trend');
    if (trendEl) new ApexCharts(trendEl,{chart:{type:'line',height:330,toolbar:{show:false}},stroke:{curve:'smooth',width:[4,0,0]},plotOptions:{bar:{borderRadius:5,columnWidth:'42%'}},series:[{name:'Occupancy %',type:'line',data:occupancyValues},{name:'Room Revenue',type:'column',data:roomRevenueValues},{name:'Other Revenue',type:'column',data:otherRevenueValues}],xaxis:{categories:occupancyLabels},colors:['#0b5fb8','#16a34a','#d4a23a'],legend:{position:'top'},grid:{borderColor:'#e5edf6'}}).render();
    const depEl = document.querySelector('#hotel-revenue-department');
    if (depEl) new ApexCharts(depEl,{chart:{type:'donut',height:230},series:@json(array_values($revenueByDepartment)),labels:@json(array_keys($revenueByDepartment)),legend:{position:'bottom'},colors:['#0b5fb8','#16a34a','#d4a23a','#7c3aed','#dc2626','#94a3b8']}).render();
    const roomStatusEl = document.querySelector('#hotel-room-status-chart');
    if (roomStatusEl) new ApexCharts(roomStatusEl,{chart:{type:'donut',height:210},series:statusValues,labels:statusLabels,legend:{show:false},colors:['#16a34a','#0b5fb8','#d4a23a','#dc2626','#0ea5e9','#f97316','#334155']}).render();
})();
</script>
@endsection
