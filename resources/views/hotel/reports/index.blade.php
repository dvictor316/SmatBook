@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Hotel Reports Centre</h3>
            <a href="{{ route('hotel.night_audit.index') }}" class="btn btn-outline-secondary">Night Audit</a>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Arrivals Today</small><h5>{{ $kpis['arrivals_today'] ?? 0 }}</h5></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Departures Today</small><h5>{{ $kpis['departures_today'] ?? 0 }}</h5></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Current Occupancy</small><h5>{{ $kpis['occupancy'] ?? 0 }}</h5></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Room Revenue Today</small><h5>{{ number_format((float)($kpis['room_revenue_today'] ?? 0),2) }}</h5></div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Report Shortcuts</h5></div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('balance-sheet') }}" class="btn btn-light">Balance Sheet</a>
                <a href="{{ route('trial-balance') }}" class="btn btn-light">Trial Balance</a>
                <a href="{{ route('profit-loss') }}" class="btn btn-light">Profit & Loss</a>
                <a href="{{ route('hotel.deposits') }}" class="btn btn-light">Deposits</a>
                <a href="{{ route('hotel.folios.index') }}" class="btn btn-light">Folio Ledger</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Operational KPIs</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>KPI</th><th>Value</th></tr></thead>
                    <tbody>
                        <tr><td>Arrivals Today</td><td>{{ $kpis['arrivals_today'] ?? 0 }}</td></tr>
                        <tr><td>Departures Today</td><td>{{ $kpis['departures_today'] ?? 0 }}</td></tr>
                        <tr><td>Current Occupancy</td><td>{{ $kpis['occupancy'] ?? 0 }} rooms</td></tr>
                        <tr><td>Room Revenue Today</td><td>{{ number_format((float)($kpis['room_revenue_today'] ?? 0),2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
