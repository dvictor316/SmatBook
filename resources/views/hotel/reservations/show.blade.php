@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Reservation {{ $reservation->reservation_number }}</h3>
        <p>Guest: {{ $reservation->customer?->name ?? 'N/A' }}</p>
        <p>Arrival: {{ optional($reservation->arrival_date)->toDateString() }} | Departure: {{ optional($reservation->departure_date)->toDateString() }}</p>
        <p>Status: {{ $reservation->status }}</p>
        @if(in_array($reservation->status, ['reserved','confirmed']))
            <form action="{{ route('hotel.checkin', $reservation) }}" method="POST">
                @csrf
                <button class="btn btn-success">Check In</button>
            </form>
        @endif
    </div>
</div>
@endsection
