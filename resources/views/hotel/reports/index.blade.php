@extends('layout.mainlayout')

@section('style')
<style>
    .hotel-reports-hub { background:#f5f8fc; color:#102033; }
    .reports-shell { display:grid; gap:14px; }
    .reports-hero { position:relative; overflow:hidden; border-radius:8px; background:#082f55; background-image:linear-gradient(90deg,rgba(4,16,31,.90),rgba(4,16,31,.50)),url('/assets/img/hotel-keto/banner2.jpg'); background-size:cover; background-position:center; color:#fff; padding:20px; box-shadow:0 18px 38px rgba(15,23,42,.14); display:grid; grid-template-columns:minmax(0,1fr) minmax(330px,420px); gap:20px; align-items:end; }
    .reports-hero__eyebrow, .report-section-label { color:#f7c948; text-transform:uppercase; letter-spacing:.14em; font-size:12px; font-weight:900; }
    .reports-hero h3 { color:#fff; margin:6px 0 5px; font-size:32px; font-weight:900; letter-spacing:0; }
    .reports-hero p { color:#e7efff; margin:0; max-width:780px; font-size:15px; }
    .reports-filter { background:rgba(255,255,255,.94); border:1px solid rgba(255,255,255,.55); border-radius:8px; padding:13px; color:#082345; box-shadow:0 16px 34px rgba(0,0,0,.16); }
    .reports-filter label { font-size:12px; font-weight:800; color:#52667f; margin-bottom:4px; }
    .reports-filter .form-control { min-height:38px; border-radius:8px; }
    .reports-filter__actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
    .reports-filter__actions .btn { min-height:38px; border-radius:8px; font-weight:800; }
    .report-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .report-kpi { min-height:116px; background:#fff; border:1px solid #d9e4ef; border-radius:8px; padding:14px; box-shadow:0 10px 24px rgba(15,23,42,.06); display:flex; flex-direction:column; justify-content:space-between; }
    .report-kpi span { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:11px; font-weight:900; }
    .report-kpi strong { color:#061b33; font-size:27px; line-height:1; font-weight:900; }
    .report-kpi small { color:#64748b; font-weight:700; }
    .reports-grid { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(330px,.75fr); gap:14px; align-items:start; }
    .report-card { background:#fff; border:1px solid #d9e4ef; border-radius:8px; box-shadow:0 10px 24px rgba(15,23,42,.05); overflow:hidden; }
    .report-card__head { padding:14px 16px; border-bottom:1px solid #e7eef7; display:flex; justify-content:space-between; gap:12px; align-items:center; }
    .report-card__head h5 { margin:0; color:#061b33; font-size:17px; font-weight:900; }
    .report-card__body { padding:16px; }
    .report-action-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .report-action { min-height:134px; border:1px solid #d8e3f0; border-radius:8px; padding:13px; background-color:#fff; background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/room1.jpg'); background-size:cover; background-position:center; color:#0b2443; text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; }
    .report-action:hover { color:#0b2443; border-color:#0d4faa; transform:translateY(-1px); }
    .report-action:nth-child(2) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/banner1.jpg'); }
    .report-action:nth-child(3) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/room2.jpg'); }
    .report-action:nth-child(4) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/gallery5.jpg'); }
    .report-action:nth-child(5) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/gallery3.jpg'); }
    .report-action:nth-child(6) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/room6.jpg'); }
    .report-action:nth-child(7) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/gallery6.jpg'); }
    .report-action:nth-child(8) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/banner3.jpg'); }
    .report-action:nth-child(9) { background-image:linear-gradient(90deg,rgba(255,255,255,.96),rgba(255,255,255,.76)),url('/assets/img/hotel-keto/gallery7.jpg'); }
    .report-action i { color:#d39a11; font-size:18px; }
    .report-action h4 { color:#061b33; font-size:17px; font-weight:900; margin:8px 0 5px; }
    .report-action p { color:#536b88; margin:0; font-size:13px; line-height:1.35; }
    .chart-bars { min-height:230px; display:flex; align-items:flex-end; gap:8px; border-bottom:1px solid #dce6f2; padding-top:12px; }
    .chart-day { flex:1; min-width:20px; display:flex; flex-direction:column; align-items:center; gap:6px; }
    .chart-stack { width:100%; min-height:10px; max-width:42px; display:flex; flex-direction:column; justify-content:flex-end; border-radius:8px 8px 0 0; overflow:hidden; background:#e8eef7; }
    .chart-seg.room { background:#1255b5; min-height:5px; }
    .chart-seg.service { background:#d39a11; min-height:5px; }
    .chart-label { font-size:10px; color:#64748b; font-weight:800; white-space:nowrap; }
    .mini-list { display:grid; gap:9px; }
    .mini-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:10px; align-items:center; padding:10px; border:1px solid #e7eef7; border-radius:8px; background:#fbfdff; }
    .mini-row strong { color:#061b33; }
    .mini-row span, .mini-row small { color:#64748b; }
    .metric-pill { display:inline-flex; align-items:center; justify-content:center; min-height:27px; padding:4px 9px; border-radius:999px; background:#eef5ff; color:#0f4db8; font-size:12px; font-weight:900; white-space:nowrap; }
    .status-stack { display:grid; gap:8px; }
    .status-line { display:grid; grid-template-columns:120px minmax(0,1fr) 44px; gap:9px; align-items:center; }
    .status-track { height:10px; border-radius:999px; background:#e8eef7; overflow:hidden; }
    .status-fill { height:100%; border-radius:999px; background:#1255b5; }
    .accounting-links { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .accounting-links a { min-height:44px; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border:1px solid #d8e3f0; border-radius:8px; color:#0b2443; text-decoration:none; font-weight:800; background:#fff; }
    .accounting-links a:hover { color:#0f4db8; border-color:#0f4db8; background:#f8fbff; }
    .report-table { min-width:720px; }
    .report-table th { color:#52667f; text-transform:uppercase; letter-spacing:.07em; font-size:11px; }
    @media(max-width:1199px){.reports-hero,.reports-grid{grid-template-columns:1fr}.report-kpis{grid-template-columns:repeat(2,1fr)}.report-action-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.reports-hero{padding:16px}.reports-hero h3{font-size:25px}.report-kpis,.report-action-grid,.accounting-links{grid-template-columns:1fr}.chart-bars{overflow-x:auto}.chart-day{min-width:34px}}
    @media print {.header,.sidebar,.spb-desktop-backbar,.reports-filter,.reports-filter__actions,.ai-agent-launcher{display:none!important}.page-wrapper{margin:0!important}.report-card,.report-kpi,.reports-hero{box-shadow:none!important}.reports-hero{color:#061b33;background:#fff!important;border:1px solid #d9e4ef}.reports-hero h3,.reports-hero p{color:#061b33}}
</style>
@endsection

@section('content')
@php
    $totalRooms = max(1, (int) $roomState->sum('total_count'));
    $occupiedRooms = (int) optional($roomState->firstWhere('status_name', 'occupied'))->total_count;
    $occupancyRate = round(($occupiedRooms / $totalRooms) * 100);
    $dailyMax = max(1, (float) $dailyRevenue->max(fn ($row) => (float) $row->room_total + (float) $row->service_total));
    $serviceTotal = (float) $serviceRevenue->sum('total_amount');
    $paymentTotal = abs((float) $paymentRevenue->sum('total_amount'));
@endphp
<div class="page-wrapper hotel-reports-hub">
    <div class="content container-fluid reports-shell">
        <section class="reports-hero">
            <div>
                <div class="reports-hero__eyebrow">SmartProBook Hotel PMS</div>
                <h3>Reports & Analytics</h3>
                <p>Revenue, occupancy, cashier movement, folio exposure and accounting traces for the selected hotel period.</p>
            </div>
            <form method="GET" action="{{ route('hotel.reports.index') }}" class="reports-filter">
                <div class="row g-2">
                    <div class="col-6"><label>From</label><input type="date" name="from" class="form-control" value="{{ $reportFrom }}"></div>
                    <div class="col-6"><label>To</label><input type="date" name="to" class="form-control" value="{{ $reportTo }}"></div>
                </div>
                <div class="reports-filter__actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Apply</button>
                    <button type="button" onclick="window.print()" class="btn btn-outline-dark"><i class="fas fa-print me-1"></i> Print</button>
                    <a href="{{ route('hotel.night_audit.index') }}" class="btn btn-warning">Night Audit</a>
                </div>
            </form>
        </section>

        <div class="report-kpis">
            <div class="report-kpi"><span>Room Revenue</span><strong>{{ number_format((float) $kpis['room_revenue_month'], 2) }}</strong><small>{{ number_format((float) $kpis['room_revenue_today'], 2) }} today</small></div>
            <div class="report-kpi"><span>Service Revenue</span><strong>{{ number_format((float) $kpis['service_revenue_month'], 2) }}</strong><small>{{ $serviceRevenue->sum('tx_count') }} service postings</small></div>
            <div class="report-kpi"><span>Occupancy</span><strong>{{ $occupancyRate }}%</strong><small>{{ $occupiedRooms }} occupied of {{ $totalRooms }} rooms</small></div>
            <div class="report-kpi"><span>Open Exposure</span><strong>{{ number_format((float) $kpis['folio_balance'], 2) }}</strong><small>{{ $kpis['open_folios'] }} open folios</small></div>
        </div>

        <div class="reports-grid">
            <main class="reports-shell">
                <section class="report-card">
                    <div class="report-card__head"><div><div class="report-section-label">Command Desk</div><h5>Operational Report Shortcuts</h5></div><span class="metric-pill">{{ $reportFrom }} - {{ $reportTo }}</span></div>
                    <div class="report-card__body">
                        <div class="report-action-grid">
                            <a href="{{ route('hotel.frontdesk') }}" class="report-action"><div><i class="fas fa-concierge-bell"></i><h4>Front Desk</h4><p>{{ $kpis['arrivals_today'] }} arrivals and {{ $kpis['departures_today'] }} departures due today.</p></div><span class="metric-pill">Open</span></a>
                            <a href="{{ route('hotel.reservations.index') }}" class="report-action"><div><i class="fas fa-calendar-check"></i><h4>Reservations</h4><p>Booking register, source performance and arrival controls.</p></div><span class="metric-pill">Review</span></a>
                            <a href="{{ route('hotel.rooms.calendar') }}" class="report-action"><div><i class="fas fa-bed"></i><h4>Room Calendar</h4><p>Timeline of occupancy, stayover, blocked and vacant rooms.</p></div><span class="metric-pill">Timeline</span></a>
                            <a href="{{ route('hotel.folios.index') }}" class="report-action"><div><i class="fas fa-file-invoice-dollar"></i><h4>Guest Folios</h4><p>Open balances, cashier charges and payment audit trail.</p></div><span class="metric-pill">Cashier</span></a>
                            <a href="{{ route('hotel.deposits') }}" class="report-action"><div><i class="fas fa-wallet"></i><h4>Deposits</h4><p>Advance collections and unapplied guest deposits.</p></div><span class="metric-pill">Funds</span></a>
                            <a href="{{ route('hotel.housekeeping.index') }}" class="report-action"><div><i class="fas fa-broom"></i><h4>Housekeeping</h4><p>Dirty, clean, inspection and room-readiness work queue.</p></div><span class="metric-pill">Rooms</span></a>
                            <a href="{{ route('hotel.maintenance.index') }}" class="report-action"><div><i class="fas fa-tools"></i><h4>Maintenance</h4><p>Engineering tickets, room locks and out-of-order exposure.</p></div><span class="metric-pill">Risk</span></a>
                            <a href="{{ route('hotel.booking_sources.index') }}" class="report-action"><div><i class="fas fa-chart-line"></i><h4>Booking Sources</h4><p>Direct, OTA, corporate and channel revenue review.</p></div><span class="metric-pill">Sales</span></a>
                            <a href="{{ route('hotel.guests') }}" class="report-action"><div><i class="fas fa-user-friends"></i><h4>Guest Profiles</h4><p>Guest history, lifetime spend and service follow-up.</p></div><span class="metric-pill">CRM</span></a>
                        </div>
                    </div>
                </section>

                <section class="report-card">
                    <div class="report-card__head"><div><div class="report-section-label">Revenue Trend</div><h5>Room vs Service Daily Revenue</h5></div><span class="metric-pill">{{ number_format((float) $kpis['room_revenue_month'] + $serviceTotal, 2) }}</span></div>
                    <div class="report-card__body">
                        <div class="chart-bars">
                            @forelse($dailyRevenue as $day)
                                @php
                                    $roomHeight = max(5, ((float) $day->room_total / $dailyMax) * 205);
                                    $serviceHeight = max(5, ((float) $day->service_total / $dailyMax) * 205);
                                    $dayLabel = $day->report_date ? \Illuminate\Support\Carbon::parse($day->report_date)->format('d M') : '-';
                                @endphp
                                <div class="chart-day" title="{{ $dayLabel }}: {{ number_format((float) $day->room_total + (float) $day->service_total, 2) }}"><div class="chart-stack"><div class="chart-seg service" style="height:{{ $serviceHeight }}px"></div><div class="chart-seg room" style="height:{{ $roomHeight }}px"></div></div><div class="chart-label">{{ $dayLabel }}</div></div>
                            @empty
                                <div class="text-muted">No revenue postings found for this period.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="report-card">
                    <div class="report-card__head"><h5>Recent Folio Postings</h5><span class="metric-pill">{{ $recentPostings->count() }} loaded</span></div>
                    <div class="table-responsive">
                        <table class="table report-table align-middle mb-0">
                            <thead><tr><th>Posting</th><th>Guest / Room</th><th>Service</th><th>Type</th><th class="text-end">Amount</th><th>Date</th></tr></thead>
                            <tbody>
                                @forelse($recentPostings as $posting)
                                    @php $postingDate = $posting->service_date ? \Illuminate\Support\Carbon::parse($posting->service_date)->format('d M Y') : '-'; @endphp
                                    <tr><td><strong>{{ $posting->description ?: 'Hotel posting' }}</strong></td><td>{{ $posting->folio?->customer?->customer_name ?? $posting->folio?->customer?->name ?? 'Guest' }} · Room {{ $posting->folio?->stay?->room?->room_number ?? 'N/A' }}</td><td><span class="metric-pill">{{ strtoupper(str_replace('_', ' ', (string) ($posting->service_code ?: 'OTHER'))) }}</span></td><td>{{ ucfirst(str_replace('_', ' ', (string) $posting->type)) }}</td><td class="text-end fw-bold">{{ number_format((float) $posting->amount, 2) }}</td><td>{{ $postingDate }}</td></tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No postings found for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <aside class="reports-shell">
                <section class="report-card">
                    <div class="report-card__head"><h5>Service Revenue</h5><span class="metric-pill">{{ number_format($serviceTotal, 2) }}</span></div>
                    <div class="report-card__body mini-list">@forelse($serviceRevenue as $row)<div class="mini-row"><div><strong>{{ strtoupper(str_replace('_', ' ', (string) $row->service_code)) }}</strong><br><small>{{ $row->tx_count }} postings</small></div><span class="fw-bold">{{ number_format((float) $row->total_amount, 2) }}</span></div>@empty<div class="text-muted">No service revenue posted in this period.</div>@endforelse</div>
                </section>

                <section class="report-card">
                    <div class="report-card__head"><h5>Room State</h5><span class="metric-pill">{{ $totalRooms }} rooms</span></div>
                    <div class="report-card__body status-stack">@forelse($roomState as $state)@php $statePercent = round(((int) $state->total_count / $totalRooms) * 100); @endphp<div class="status-line"><strong>{{ ucfirst(str_replace('_', ' ', (string) $state->status_name)) }}</strong><div class="status-track"><div class="status-fill" style="width:{{ $statePercent }}%"></div></div><span class="text-end fw-bold">{{ $state->total_count }}</span></div>@empty<div class="text-muted">No room status data available.</div>@endforelse</div>
                </section>

                <section class="report-card">
                    <div class="report-card__head"><h5>Folio Exposure</h5><span class="metric-pill">{{ number_format((float) $kpis['folio_balance'], 2) }}</span></div>
                    <div class="report-card__body mini-list">@forelse($folioExposure as $folio)<div class="mini-row"><div><strong>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'Guest' }}</strong><br><small>Room {{ $folio->stay?->room?->room_number ?? 'N/A' }} · {{ $folio->folio_number ?? ('Folio #' . $folio->id) }}</small></div><span class="fw-bold text-danger">{{ number_format((float) $folio->balance, 2) }}</span></div>@empty<div class="text-muted">No open folio exposure for this period.</div>@endforelse</div>
                </section>

                <section class="report-card">
                    <div class="report-card__head"><h5>Cashier Trace</h5><span class="metric-pill">{{ number_format($paymentTotal, 2) }}</span></div>
                    <div class="report-card__body mini-list">@forelse($paymentRevenue as $row)<div class="mini-row"><div><strong>{{ strtoupper(str_replace('_', ' ', (string) $row->payment_code)) }}</strong><br><small>{{ $row->tx_count }} entries</small></div><span class="fw-bold">{{ number_format(abs((float) $row->total_amount), 2) }}</span></div>@empty<div class="text-muted">No cashier payments found for this period.</div>@endforelse</div>
                </section>

                <section class="report-card">
                    <div class="report-card__head"><h5>Accounting Reports</h5><span class="metric-pill">Finance</span></div>
                    <div class="report-card__body accounting-links">
                        @if(Route::has('reports.profit-loss'))<a href="{{ route('reports.profit-loss') }}"><span>Profit & Loss</span><i class="fe fe-arrow-right"></i></a>@endif
                        @if(Route::has('reports.income'))<a href="{{ route('reports.income') }}"><span>Income Report</span><i class="fe fe-arrow-right"></i></a>@endif
                        @if(Route::has('reports.sales'))<a href="{{ route('reports.sales') }}"><span>Sales Report</span><i class="fe fe-arrow-right"></i></a>@endif
                        @if(Route::has('reports.payment'))<a href="{{ route('reports.payment') }}"><span>Payment Report</span><i class="fe fe-arrow-right"></i></a>@endif
                        @if(Route::has('reports.accounts-receivable'))<a href="{{ route('reports.accounts-receivable') }}"><span>Receivables</span><i class="fe fe-arrow-right"></i></a>@endif
                        @if(Route::has('balance-sheet'))<a href="{{ route('balance-sheet') }}"><span>Balance Sheet</span><i class="fe fe-arrow-right"></i></a>@endif
                        @if(Route::has('reports.cash-flow'))<a href="{{ route('reports.cash-flow') }}"><span>Cash Flow</span><i class="fe fe-arrow-right"></i></a>@endif
                        @if(Route::has('general-ledger'))<a href="{{ route('general-ledger') }}"><span>General Ledger</span><i class="fe fe-arrow-right"></i></a>@endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
