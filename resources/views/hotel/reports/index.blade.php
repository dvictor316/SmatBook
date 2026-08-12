@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Hotel Reports Centre</h3>
            <a href="{{ route('hotel.night_audit.index') }}" class="btn btn-outline-secondary">Night Audit</a>
        </div>

        <div class="row g-3">
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">Front Office</small><h5>Arrivals, departures, reservations, no shows</h5><div class="d-grid gap-2"><a href="{{ route('hotel.frontdesk') }}" class="btn btn-light">Arrivals & Departures</a><a href="{{ route('hotel.reservations.index') }}" class="btn btn-light">Reservations</a></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">Financial</small><h5>Revenue, payments, deposits, balances</h5><div class="d-grid gap-2"><a href="{{ route('hotel.folios.index') }}" class="btn btn-light">Folio Ledger</a><a href="{{ route('hotel.deposits') }}" class="btn btn-light">Deposits</a><a href="{{ route('profit-loss') }}" class="btn btn-light">Profit & Loss</a></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">Operations</small><h5>Housekeeping, maintenance, room status</h5><div class="d-grid gap-2"><a href="{{ route('hotel.housekeeping.index') }}" class="btn btn-light">Housekeeping</a><a href="{{ route('hotel.maintenance.index') }}" class="btn btn-light">Maintenance</a><a href="{{ route('hotel.rooms.status') }}" class="btn btn-light">Room Status</a></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">Management</small><h5>Occupancy, ADR, RevPAR, performance</h5><div class="small text-muted mb-3">Current Occupancy: {{ $kpis['occupancy'] ?? 0 }} rooms</div><div class="small text-muted">Room Revenue Today: {{ number_format((float)($kpis['room_revenue_today'] ?? 0),2) }}</div></div></div></div>
        </div>
    </div>
</div>
@endsection
