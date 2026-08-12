@extends('layout.mainlayout')

@section('style')
<style>
    .hotel-reports-hub { background:#06213a; color:#dbeafe; }
    .report-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:14px; flex-wrap:wrap; padding:20px; border:1px solid rgba(255,255,255,.14); border-radius:16px; background:linear-gradient(135deg,#082f55,#0b1f35); margin-bottom:16px; }
    .report-hero h3 { color:#fff; font-size:30px; font-weight:900; margin:0; }
    .report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .report-tile { min-height:170px; padding:18px; border-radius:14px; background:#102f4d; border:1px solid rgba(255,255,255,.13); color:#fff; text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; }
    .report-tile:hover { color:#fff; transform:translateY(-2px); }
    .report-tile span { color:#f1c15c; text-transform:uppercase; letter-spacing:.12em; font-size:12px; font-weight:900; }
    .report-tile p { color:#cbd5e1; margin:0; }
    @media(max-width:991px){.report-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:575px){.report-grid{grid-template-columns:1fr}.report-hero h3{font-size:23px}}
</style>
@endsection

@section('content')
<div class="page-wrapper hotel-reports-hub">
    <div class="content container-fluid">
        <section class="report-hero"><div><span class="text-warning fw-bold">HOTEL REPORTS</span><h3>Operational reports centre</h3><p class="mb-0">Front-office, housekeeping, folio, source and night-audit report destinations.</p></div><a href="{{ route('hotel.night_audit.index') }}" class="btn btn-warning">Night Audit</a></section>
        <div class="report-grid">
            <a href="{{ route('hotel.frontdesk') }}" class="report-tile"><span>Front Office</span><h4>Arrivals & Departures</h4><p>Daily guest movement and desk queue.</p></a>
            <a href="{{ route('hotel.reservations.index') }}" class="report-tile"><span>Reservations</span><h4>Reservation Register</h4><p>Booking status, room assignments and source tracking.</p></a>
            <a href="{{ route('hotel.rooms.calendar') }}" class="report-tile"><span>Calendar</span><h4>Room Calendar</h4><p>Timeline of occupancy, blocks and room assignments.</p></a>
            <a href="{{ route('hotel.folios.index') }}" class="report-tile"><span>Cashier</span><h4>Folio Ledger</h4><p>Guest balances, charges, payments and checkout flow.</p></a>
            <a href="{{ route('hotel.deposits') }}" class="report-tile"><span>Payments</span><h4>Deposit Register</h4><p>Pre-arrival deposit and funding gaps.</p></a>
            <a href="{{ route('hotel.housekeeping.index') }}" class="report-tile"><span>Rooms</span><h4>Housekeeping Report</h4><p>Dirty, cleaning, inspection and ready rooms.</p></a>
            <a href="{{ route('hotel.maintenance.index') }}" class="report-tile"><span>Engineering</span><h4>Maintenance Report</h4><p>Open tickets, out-of-order rooms and conflicts.</p></a>
            <a href="{{ route('hotel.booking_sources.index') }}" class="report-tile"><span>Sales</span><h4>Booking Sources</h4><p>Direct, OTA, corporate and channel performance.</p></a>
            <a href="{{ route('hotel.guests') }}" class="report-tile"><span>CRM</span><h4>Guest Profiles</h4><p>Lifetime stays, spend, balances and guest follow-up.</p></a>
        </div>
    </div>
</div>
@endsection
