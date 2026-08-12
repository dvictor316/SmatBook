@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Hotel Overview</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active">Hotel module summary and live metrics</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Total Hotel Tenants</h5>
                        <h3>{{ $totalHotelTenants }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Active Hotel Subscriptions</h5>
                        <h3>{{ $activeHotelSubscriptions }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Total Properties</h5>
                        <h3>{{ $totalProperties }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Total Rooms</h5>
                        <h3>{{ $totalRooms }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Available Rooms</h5>
                        <h3>{{ $availableRooms }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Occupied Rooms</h5>
                        <h3>{{ $occupiedRooms }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Reserved Rooms</h5>
                        <h3>{{ $reservedRooms }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Reservations Today</h5>
                        <h3>{{ $todayReservations }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Current In-House Guests</h5>
                        <h3>{{ $currentInHouseGuests }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Hotel Revenue Today</h5>
                        <h3>{{ number_format($hotelRevenueToday, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Hotel Revenue This Month</h5>
                        <h3>{{ number_format($hotelRevenueThisMonth, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dash-card">
                    <div class="card-body">
                        <h5>Outstanding Receivables</h5>
                        <h3>{{ number_format($outstandingReceivables, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
