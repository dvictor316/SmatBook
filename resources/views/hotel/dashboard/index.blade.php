@extends('layout.mainlayout')

@section('style')
<style>
    .spb-pms-dashboard { background:#eef3f8; color:#142033; }
    .pms-command { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(320px,.9fr); gap:16px; }
    .pms-panel { background:#fff; border:1px solid #d9e2ee; border-radius:10px; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .pms-hero { background:linear-gradient(135deg,#05284f,#0b5fb8); color:#fff; border-radius:14px; padding:20px; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:18px; align-items:center; margin-bottom:16px; }
    .pms-hero h2 { color:#fff; margin:0; font-size:28px; font-weight:900; }
    .pms-hero small { color:#d8e8ff; text-transform:uppercase; letter-spacing:.13em; font-weight:800; }
    .pms-actions { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .pms-action { border:1px solid rgba(255,255,255,.32); background:rgba(255,255,255,.12); color:#fff; border-radius:10px; padding:12px; text-decoration:none; font-weight:900; text-align:center; }
    .pms-action:hover { color:#fff; background:rgba(255,255,255,.22); }
    .pms-filter { display:flex; flex-wrap:wrap; gap:10px; justify-content:flex-end; }
    .pms-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .pms-kpi { padding:15px; color:#142033; text-decoration:none; position:relative; overflow:hidden; }
    .pms-kpi:before { content:''; position:absolute; inset:0 auto 0 0; width:5px; background:#0b5fb8; }
    .pms-kpi.green:before { background:#16a34a; } .pms-kpi.gold:before { background:#d4a23a; } .pms-kpi.red:before { background:#dc2626; }
    .pms-kpi strong { display:block; font-size:28px; line-height:1; margin:7px 0 5px; }
    .pms-mini { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
    .pms-mini a, .pms-mini div { padding:12px; color:#142033; text-decoration:none; }
    .pms-board { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(320px,.8fr); gap:16px; margin-bottom:16px; }
    .pms-table th { background:#0c3f70; color:#fff; border:0; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
    .pms-table td { vertical-align:middle; font-size:13px; }
    .pms-status-list a, .pms-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px solid #edf1f5; color:#142033; text-decoration:none; }
    .pms-status-list a:last-child, .pms-row:last-child { border-bottom:0; }
    .pms-alert { display:flex; align-items:center; justify-content:space-between; gap:12px; border-radius:9px; padding:11px 12px; text-decoration:none; color:#142033; margin-bottom:9px; border:1px solid #d9e2ee; background:#f8fafc; }
    .pms-alert[data-tone="danger"] { background:#fff1f2; border-color:#fecaca; color:#8a1010; }
    .pms-alert[data-tone="warning"] { background:#fff7ed; border-color:#fed7aa; color:#8a4b08; }
    .pms-alert[data-tone="info"] { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
    .pms-charts { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(280px,.7fr); gap:16px; margin-bottom:16px; }
    .pms-bottom { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
    @media(max-width:1199px){.pms-command,.pms-board,.pms-charts{grid-template-columns:1fr}.pms-kpis{grid-template-columns:repeat(2,1fr)}.pms-mini,.pms-bottom{grid-template-columns:repeat(2,1fr)}.pms-hero{grid-template-columns:1fr}.pms-filter{justify-content:flex-start}}
    @media(max-width:767px){.pms-kpis,.pms-mini,.pms-bottom,.pms-actions{grid-template-columns:1fr}.pms-hero h2{font-size:22px}}
</style>
@endsection

@section('content')
<div class="page-wrapper spb-pms-dashboard">
    <div class="content container-fluid">
        <section class="pms-hero">
            <div>
                <small>SmartProbook Hotel PMS</small>
                <h2>Hotel command center</h2>
                <p class="mb-0">{{ $property?->name ?? 'All Properties' }} · Business Date {{ now()->format('d M Y') }} · {{ ucfirst(str_replace('_',' ', $rangeKey)) }}</p>
            </div>
            <div class="pms-actions">
                <a class="pms-action" href="{{ route('hotel.reservations.create') }}">New Reservation</a>
                <a class="pms-action" href="{{ route('hotel.walkin.create') }}">Walk-In</a>
                <a class="pms-action" href="{{ route('hotel.checkin.index') }}">Check-In</a>
                <a class="pms-action" href="{{ route('hotel.checkout.index') }}">Checkout</a>
            </div>
        </section>

        <form method="GET" class="pms-panel p-3 mb-3 pms-filter">
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
            <button class="btn btn-sm btn-primary">Refresh</button>
        </form>

        <div class="pms-kpis">
            <a href="{{ route('hotel.rooms.status') }}" class="pms-panel pms-kpi"><span class="text-muted">Occupancy</span><strong>{{ number_format($occupancyRate, 1) }}%</strong><small>{{ $occupiedRooms }} of {{ $totalRooms }} rooms occupied</small></a>
            <a href="{{ route('hotel.rooms.index', ['status' => 'available']) }}" class="pms-panel pms-kpi green"><span class="text-muted">Available Rooms</span><strong>{{ $availableRooms }}</strong><small>Ready to sell now</small></a>
            <a href="{{ route('hotel.in_house') }}" class="pms-panel pms-kpi gold"><span class="text-muted">In-House Guests</span><strong>{{ $inHouseGuests }}</strong><small>Checked-in guests</small></a>
            <a href="{{ route('hotel.folios.index') }}" class="pms-panel pms-kpi red"><span class="text-muted">Outstanding Balances</span><strong>{{ number_format((float) $folioBalances, 2) }}</strong><small>Open guest folios</small></a>
        </div>

        <div class="pms-mini">
            <a href="{{ route('hotel.frontdesk') }}" class="pms-panel"><small>Arrivals</small><h5 class="mb-0">{{ $todayArrivals }}</h5></a>
            <a href="{{ route('hotel.frontdesk') }}" class="pms-panel"><small>Departures</small><h5 class="mb-0">{{ $todayDepartures }}</h5></a>
            <a href="{{ route('hotel.rooms.calendar') }}" class="pms-panel"><small>Reserved</small><h5 class="mb-0">{{ $reservedRooms }}</h5></a>
            <a href="{{ route('hotel.housekeeping.index') }}" class="pms-panel"><small>Dirty</small><h5 class="mb-0">{{ $dirtyRooms }}</h5></a>
            <a href="{{ route('hotel.maintenance.index') }}" class="pms-panel"><small>Maintenance</small><h5 class="mb-0">{{ $maintenanceRooms + $outOfOrderRooms }}</h5></a>
            <a href="{{ route('hotel.reports.index') }}" class="pms-panel"><small>Revenue Today</small><h5 class="mb-0">{{ number_format((float) $todayRevenue, 2) }}</h5></a>
        </div>

        <div class="pms-board">
            <div class="pms-panel p-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Arrivals and departures</h5><a href="{{ route('hotel.frontdesk') }}" class="btn btn-sm btn-light">Open Front Desk</a></div>
                <div class="table-responsive">
                    <table class="table table-sm pms-table align-middle mb-0">
                        <thead><tr><th>Type</th><th>Guest</th><th>Room</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($arrivalsPanel->take(5) as $arrival)
                            <tr><td>Arrival</td><td>{{ $arrival->customer?->customer_name ?? 'Walk-In' }}</td><td>{{ $arrival->room?->room_number ?? 'Unassigned' }}</td><td>{{ optional($arrival->arrival_date)->format('d M') }}</td><td>{{ (float) $arrival->deposit_received > 0 ? 'Deposit received' : 'Deposit pending' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No arrivals scheduled.</td></tr>
                        @endforelse
                        @forelse($departuresPanel->take(5) as $departure)
                            @php $depBalance = (float) ($departure->balance ?? 0); @endphp
                            <tr><td>Departure</td><td>{{ $departure->customer?->customer_name ?? 'Walk-In' }}</td><td>{{ $departure->room?->room_number ?? 'Unassigned' }}</td><td>{{ optional($departure->departure_date)->format('d M') }}</td><td class="{{ $depBalance > 0 ? 'text-danger' : 'text-success' }}">{{ $depBalance > 0 ? number_format($depBalance, 2).' due' : 'Clear' }}</td></tr>
                        @empty
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pms-panel p-3">
                <h5>Management alerts</h5>
                @forelse($managementAlerts as $alert)
                    <a href="{{ $alert['route'] }}" class="pms-alert" data-tone="{{ $alert['tone'] }}"><span>{{ $alert['label'] }}</span><strong>{{ $alert['count'] }}</strong></a>
                @empty
                    <div class="alert alert-light mb-0">No active alerts. Operations look clean.</div>
                @endforelse
            </div>
        </div>

        <div class="pms-charts">
            <div class="pms-panel p-3"><h5>Occupancy and revenue trend</h5><div id="hotel-occupancy-trend" style="min-height:320px"></div></div>
            <div class="pms-panel p-3"><h5>Room status</h5><div id="hotel-room-status-chart" style="min-height:210px"></div><div class="pms-status-list mt-2">@foreach($roomStatusBreakdown as $statusItem)<a href="{{ $statusItem['route'] }}"><span>{{ $statusItem['label'] }}</span><strong>{{ $statusItem['count'] }}</strong></a>@endforeach</div></div>
        </div>

        <div class="pms-bottom">
            <div class="pms-panel p-3"><h5>Revenue by department</h5><div id="hotel-revenue-department" style="min-height:220px"></div></div>
            <div class="pms-panel p-3"><h5>Daily activity</h5>@foreach($todayActivity as $label => $value)<div class="pms-row"><span>{{ ucwords(str_replace('_', ' ', $label)) }}</span><strong>{{ is_numeric($value) ? number_format((float) $value, is_float($value + 0) ? 2 : 0) : $value }}</strong></div>@endforeach</div>
            <div class="pms-panel p-3"><h5>Source and payments</h5>@forelse($reservationSourceRows as $source)<div class="pms-row"><span>{{ ucfirst((string) $source->source) }}</span><strong>{{ $source->total_count }}</strong></div>@empty<div class="text-muted">No reservation source data.</div>@endforelse<hr>@forelse($paymentMethodDistributionRows as $pay)<div class="pms-row"><span>{{ strtoupper((string) $pay->service_code) }}</span><strong>{{ number_format((float) $pay->total_amount, 2) }}</strong></div>@empty<div class="text-muted">No payment records.</div>@endforelse</div>
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
    if (trendEl) new ApexCharts(trendEl,{chart:{type:'line',height:320,toolbar:{show:false}},stroke:{curve:'smooth',width:[3,0,0]},series:[{name:'Occupancy %',type:'line',data:occupancyValues},{name:'Room Revenue',type:'column',data:roomRevenueValues},{name:'Other Revenue',type:'column',data:otherRevenueValues}],xaxis:{categories:occupancyLabels},colors:['#0b5fb8','#16a34a','#d4a23a'],legend:{position:'top'}}).render();
    const depEl = document.querySelector('#hotel-revenue-department');
    if (depEl) new ApexCharts(depEl,{chart:{type:'donut',height:220},series:@json(array_values($revenueByDepartment)),labels:@json(array_keys($revenueByDepartment)),legend:{position:'bottom'},colors:['#0b5fb8','#16a34a','#d4a23a','#7c3aed','#dc2626','#94a3b8']}).render();
    const roomStatusEl = document.querySelector('#hotel-room-status-chart');
    if (roomStatusEl) new ApexCharts(roomStatusEl,{chart:{type:'donut',height:210},series:statusValues,labels:statusLabels,legend:{show:false},colors:['#16a34a','#0b5fb8','#d4a23a','#dc2626','#0ea5e9','#f97316','#334155']}).render();
})();
</script>
@endsection
