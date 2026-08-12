@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Hotel Dashboard</h3>
        <div class="row">
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Total Rooms</small><h5>{{ $totalRooms }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Available</small><h5>{{ $availableRooms }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Occupied</small><h5>{{ $occupiedRooms }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Reserved</small><h5>{{ $reservedRooms }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Dirty</small><h5>{{ $dirtyRooms }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Maintenance</small><h5>{{ $maintenanceRooms }}</h5></div></div></div>
        </div>

        <div class="row mt-3">
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Today's Arrivals</small><h5>{{ $todayArrivals }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Today's Departures</small><h5>{{ $todayDepartures }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>In-House Guests</small><h5>{{ $inHouseGuests }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Deposits</small><h5>{{ number_format($reservationDeposits, 2) }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Folio Balances</small><h5>{{ number_format($folioBalances, 2) }}</h5></div></div></div>
            <div class="col-md-2"><div class="card"><div class="card-body"><small>Occupancy %</small><h5>{{ $occupancyRate }}%</h5></div></div></div>
        </div>
    </div>
</div>
@endsection
