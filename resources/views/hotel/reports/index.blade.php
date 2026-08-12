@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-bar-chart"></i> Hotel reports centre</span>
                <h2>Board-ready hotel reports in one command centre.</h2>
                <p>Jump from operational reports to financial ledgers while monitoring arrivals, departures, occupancy, and room revenue.</p>
                <div class="hotel-pms-actionbar">
                    <a href="{{ route('hotel.night_audit.index') }}" class="btn btn-light">Night Audit</a>
                    <a href="{{ route('hotel.dashboard') }}" class="btn btn-outline-light">Dashboard</a>
                </div>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Arrivals Today</small><strong>{{ $kpis['arrivals_today'] ?? 0 }}</strong></div>
                <div class="hotel-pms-kpi"><small>Departures Today</small><strong>{{ $kpis['departures_today'] ?? 0 }}</strong></div>
                <div class="hotel-pms-kpi"><small>In-House</small><strong>{{ $kpis['occupancy'] ?? 0 }}</strong></div>
                <div class="hotel-pms-kpi"><small>Room Revenue</small><strong>{{ number_format((float)($kpis['room_revenue_today'] ?? 0),2) }}</strong></div>
            </div>
            <div class="hotel-pms-board">
                <div class="hotel-pms-lane"><h5>Front Office</h5><a href="{{ route('hotel.frontdesk') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Arrivals & Departures</strong><div class="small hotel-pms-muted">Daily movement report</div></a><a href="{{ route('hotel.reservations.index') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Reservation Register</strong><div class="small hotel-pms-muted">Booking list and status</div></a></div>
                <div class="hotel-pms-lane"><h5>Financial</h5><a href="{{ route('hotel.folios.index') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Folio Ledger</strong><div class="small hotel-pms-muted">Guest balances and charges</div></a><a href="{{ route('hotel.deposits') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Deposits</strong><div class="small hotel-pms-muted">Pre-arrival funds</div></a></div>
                <div class="hotel-pms-lane"><h5>Operations</h5><a href="{{ route('hotel.housekeeping.index') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Housekeeping</strong><div class="small hotel-pms-muted">Cleaning queue and room readiness</div></a><a href="{{ route('hotel.maintenance.index') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Maintenance</strong><div class="small hotel-pms-muted">Ticket and outage report</div></a></div>
                <div class="hotel-pms-lane"><h5>Management</h5><a href="{{ route('hotel.rooms.status') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Room Status</strong><div class="small hotel-pms-muted">Availability and operating state</div></a><a href="{{ route('hotel.booking_sources.index') }}" class="hotel-pms-ticket d-block text-decoration-none"><strong>Booking Sources</strong><div class="small hotel-pms-muted">Channel performance</div></a></div>
            </div>
        </div>
    </div>
</div>
@endsection
