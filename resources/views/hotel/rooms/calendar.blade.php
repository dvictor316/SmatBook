@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Room Calendar / Timeline</h3>
            <a href="{{ route('hotel.availability.index') }}" class="btn btn-outline-primary">Availability Search</a>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Room</th>
                        @foreach($dates as $date)
                            <th class="text-center">{{ $date->format('d M') }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>
                                <strong>{{ $room->room_number }}</strong><br>
                                <small class="text-muted">{{ $room->type?->name ?? 'No Type' }}</small>
                            </td>
                            @foreach($dates as $date)
                                @php
                                    $hasStay = $stays->first(function ($stay) use ($room, $date) {
                                        if ((int) $stay->room_id !== (int) $room->id) {
                                            return false;
                                        }
                                        $checkIn = optional($stay->checkin_at)->toDateString();
                                        $checkOut = optional($stay->actual_checkout_at)->toDateString() ?? now()->addDay()->toDateString();
                                        return $date->toDateString() >= $checkIn && $date->toDateString() <= $checkOut;
                                    });
                                    $hasReservation = $reservations->first(function ($res) use ($room, $date) {
                                        if ((int) $res->room_id !== (int) $room->id) {
                                            return false;
                                        }
                                        return $date->toDateString() >= optional($res->arrival_date)->toDateString()
                                            && $date->toDateString() <= optional($res->departure_date)->toDateString();
                                    });
                                    $status = $hasStay ? 'occupied' : ($hasReservation ? 'reserved' : 'available');
                                @endphp
                                <td class="text-center {{ $status === 'occupied' ? 'table-danger' : ($status === 'reserved' ? 'table-warning' : 'table-success') }}">
                                    {{ strtoupper(substr($status, 0, 1)) }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="100" class="text-muted">No room calendar data available.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
