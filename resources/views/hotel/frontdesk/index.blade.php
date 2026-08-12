@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Front Desk</h3>
            <div>
                <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary btn-sm">New Reservation</a>
                <a href="{{ route('hotel.walkin.create') }}" class="btn btn-success btn-sm">Walk-In</a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3"><div class="card"><div class="card-body">Available Rooms: <strong>{{ $availableCount }}</strong></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body">Occupied Rooms: <strong>{{ $occupiedCount }}</strong></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body">Reserved Rooms: <strong>{{ $reservedCount }}</strong></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body">Dirty Rooms: <strong>{{ $dirtyCount }}</strong></div></div></div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <h5>Arrivals</h5>
                @if($arrivals->isEmpty())
                    <div class="alert alert-info">No arrivals today.</div>
                @else
                    <ul class="list-group">@foreach($arrivals as $r)<li class="list-group-item"><a href="{{ route('hotel.reservations.show', $r->id) }}">{{ $r->reservation_number }}</a></li>@endforeach</ul>
                @endif
            </div>
            <div class="col-md-4">
                <h5>Departures</h5>
                @if($departures->isEmpty())
                    <div class="alert alert-info">No departures today.</div>
                @else
                    <ul class="list-group">@foreach($departures as $r)<li class="list-group-item">{{ $r->reservation_number }}</li>@endforeach</ul>
                @endif
            </div>
            <div class="col-md-4">
                <h5>In-House Guests</h5>
                @if($inHouse->isEmpty())
                    <div class="alert alert-info">No in-house guests.</div>
                @else
                    <ul class="list-group">@foreach($inHouse as $s)<li class="list-group-item">Stay #{{ $s->id }}</li>@endforeach</ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
