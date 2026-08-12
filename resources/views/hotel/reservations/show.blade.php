@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Reservation {{ $reservation->reservation_number }}</h3>
        <p>Guest: {{ $reservation->customer?->name }}</p>
        <p>Arrival: {{ $reservation->arrival_date->toDateString() }} | Departure: {{ $reservation->departure_date->toDateString() }}</p>
        <p>Status: {{ $reservation->status }}</p>
        <form action="{{ route('hotel.checkin', $reservation) }}" method="POST">
            @csrf
            <button class="btn btn-success">Check In</button>
        </form>
    </div>
</div>
@endsection
