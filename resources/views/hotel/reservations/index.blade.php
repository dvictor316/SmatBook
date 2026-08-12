@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Reservations</h3>
        <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary mb-2">New Reservation</a>
        <table class="table">
            <thead><tr><th>#</th><th>Reservation</th><th>Guest</th><th>Arrival</th><th>Departure</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($reservations as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td><a href="{{ route('hotel.reservations.show', $r) }}">{{ $r->reservation_number }}</a></td>
                    <td>{{ $r->customer?->name }}</td>
                    <td>{{ $r->arrival_date->toDateString() }}</td>
                    <td>{{ $r->departure_date->toDateString() }}</td>
                    <td>{{ $r->status }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $reservations->links() }}
    </div>
</div>
@endsection
