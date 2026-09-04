@extends('layout.mainlayout')

@section('style')
<style>
    .hotel-reports-hub { background:#f6f8fb; color:#102033; }
    .report-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:14px; flex-wrap:wrap; padding:18px 20px; border:1px solid #d9e4ef; border-radius:8px; background:#fff; margin-bottom:14px; box-shadow:0 10px 26px rgba(15,23,42,.06); }
    .report-hero h3 { color:#061b33; font-size:28px; font-weight:800; margin:0; }
    .report-hero p { color:#64748b; margin:4px 0 0; }
    .report-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .report-actions .btn { min-height:34px; padding:6px 12px; border-radius:8px; font-size:13px; font-weight:800; }
    .report-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
    .report-kpi, .report-panel, .report-tile { background:#fff; border:1px solid #d9e4ef; border-radius:8px; box-shadow:0 8px 22px rgba(15,23,42,.05); }
    .report-kpi { padding:14px; }
    .report-kpi span, .report-tile span { display:block; color:#9a6700; text-transform:uppercase; letter-spacing:.08em; font-size:11px; font-weight:800; }
    .report-kpi strong { display:block; color:#061b33; font-size:25px; line-height:1; margin-top:7px; }
    .report-layout { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(300px,.65fr); gap:14px; align-items:start; }
    .report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .report-tile { min-height:128px; padding:14px; color:#102033; text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; }
    .report-tile:hover { border-color:#17456f; color:#102033; transform:translateY(-1px); }
    .report-tile h4 { color:#061b33; font-size:17px; font-weight:800; margin:8px 0; }
    .report-tile p { color:#64748b; margin:0; font-size:13px; }
    .report-panel { padding:14px; }
    .report-panel h5 { color:#061b33; font-size:16px; font-weight:800; margin-bottom:10px; }
    .report-link-list { display:grid; gap:8px; }
    .report-link-list a { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 11px; border:1px solid #e5edf6; border-radius:8px; color:#102033; text-decoration:none; font-weight:700; }
    .report-link-list a:hover { background:#f8fbff; border-color:#17456f; color:#17456f; }
    .service-row { display:flex; justify-content:space-between; gap:10px; padding:9px 0; border-bottom:1px solid #edf2f7; }
    .service-row:last-child { border-bottom:0; }
    @media(max-width:1199px){.report-kpis{grid-template-columns:repeat(2,1fr)}.report-layout{grid-template-columns:1fr}.report-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.report-kpis,.report-grid{grid-template-columns:1fr}.report-hero h3{font-size:23px}}
</style>
@endsection

@section('content')
<div class="page-wrapper hotel-reports-hub">
    <div class="content container-fluid">
        <section class="report-hero">
            <div>
                <span class="text-warning fw-semibold">HOTEL REPORTS</span>
                <h3>Operational & Accounting Reports</h3>
                <p>Hotel front office, sales, cashier and accounting reports in one place.</p>
            </div>
            <div class="report-actions">
                <button type="button" onclick="window.print()" class="btn btn-outline-dark"><i class="fas fa-print me-1"></i> Print</button>
                <a href="{{ route('hotel.night_audit.index') }}" class="btn btn-warning">Night Audit</a>
                @if(Route::has('general-ledger'))<a href="{{ route('general-ledger') }}" class="btn btn-outline-primary">General Ledger</a>@endif
            </div>
        </section>

        <div class="report-kpis">
            <div class="report-kpi"><span>Room Revenue Today</span><strong>{{ number_format((float) $kpis['room_revenue_today'], 2) }}</strong></div>
            <div class="report-kpi"><span>Room Revenue Month</span><strong>{{ number_format((float) $kpis['room_revenue_month'], 2) }}</strong></div>
            <div class="report-kpi"><span>Service Revenue Month</span><strong>{{ number_format((float) $kpis['service_revenue_month'], 2) }}</strong></div>
            <div class="report-kpi"><span>Open Folio Balance</span><strong>{{ number_format((float) $kpis['folio_balance'], 2) }}</strong></div>
        </div>

        <div class="report-layout">
            <main>
                <div class="report-grid">
                    <a href="{{ route('hotel.frontdesk') }}" class="report-tile"><span>Front Office</span><h4>Arrivals & Departures</h4><p>{{ $kpis['arrivals_today'] }} arrivals · {{ $kpis['departures_today'] }} departures today.</p></a>
                    <a href="{{ route('hotel.reservations.index') }}" class="report-tile"><span>Reservations</span><h4>Reservation Register</h4><p>Booking status, room assignments and source tracking.</p></a>
                    <a href="{{ route('hotel.rooms.calendar') }}" class="report-tile"><span>Calendar</span><h4>Room Calendar</h4><p>Occupancy timeline, blocks and room assignments.</p></a>
                    <a href="{{ route('hotel.folios.index') }}" class="report-tile"><span>Cashier</span><h4>Folio Ledger</h4><p>{{ $kpis['open_folios'] }} open folios and checkout balances.</p></a>
                    <a href="{{ route('hotel.deposits') }}" class="report-tile"><span>Payments</span><h4>Deposit Register</h4><p>Pre-arrival deposit and funding gaps.</p></a>
                    <a href="{{ route('hotel.housekeeping.index') }}" class="report-tile"><span>Rooms</span><h4>Housekeeping Report</h4><p>Dirty, cleaning, inspection and ready rooms.</p></a>
                    <a href="{{ route('hotel.maintenance.index') }}" class="report-tile"><span>Engineering</span><h4>Maintenance Report</h4><p>Open tickets and out-of-order room risks.</p></a>
                    <a href="{{ route('hotel.booking_sources.index') }}" class="report-tile"><span>Sales</span><h4>Booking Sources</h4><p>Direct, OTA, corporate and channel performance.</p></a>
                    <a href="{{ route('hotel.guests') }}" class="report-tile"><span>CRM</span><h4>Guest Profiles</h4><p>Lifetime stays, spend and guest follow-up.</p></a>
                </div>
            </main>

            <aside class="report-panel">
                <h5>Accounting Reports</h5>
                <div class="report-link-list mb-3">
                    @if(Route::has('reports.profit-loss'))<a href="{{ route('reports.profit-loss') }}"><span>Profit & Loss</span><i class="fe fe-arrow-right"></i></a>@endif
                    @if(Route::has('reports.income'))<a href="{{ route('reports.income') }}"><span>Income Report</span><i class="fe fe-arrow-right"></i></a>@endif
                    @if(Route::has('reports.sales'))<a href="{{ route('reports.sales') }}"><span>Sales Report</span><i class="fe fe-arrow-right"></i></a>@endif
                    @if(Route::has('reports.payment'))<a href="{{ route('reports.payment') }}"><span>Payment Report</span><i class="fe fe-arrow-right"></i></a>@endif
                    @if(Route::has('reports.accounts-receivable'))<a href="{{ route('reports.accounts-receivable') }}"><span>Accounts Receivable</span><i class="fe fe-arrow-right"></i></a>@endif
                    @if(Route::has('balance-sheet'))<a href="{{ route('balance-sheet') }}"><span>Balance Sheet</span><i class="fe fe-arrow-right"></i></a>@endif
                    @if(Route::has('reports.cash-flow'))<a href="{{ route('reports.cash-flow') }}"><span>Cash Flow</span><i class="fe fe-arrow-right"></i></a>@endif
                </div>

                <h5>Service Revenue This Month</h5>
                @forelse($serviceRevenue as $row)
                    <div class="service-row"><span>{{ strtoupper(str_replace('_', ' ', (string) $row->service_code)) }}</span><strong>{{ number_format((float) $row->total_amount, 2) }}</strong></div>
                @empty
                    <div class="text-muted small">No service revenue posted this month.</div>
                @endforelse
            </aside>
        </div>
    </div>
</div>
@endsection
