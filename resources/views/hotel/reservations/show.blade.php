@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Reservation {{ $reservation->reservation_number }}</h3>
            <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Calendar</a>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Quick View</h5></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Guest:</strong> {{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'N/A' }}</div>
                            <div class="col-md-6"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', (string) $reservation->status)) }}</div>
                            <div class="col-md-6"><strong>Room:</strong> {{ $reservation->room?->room_number ?? 'Unassigned' }}</div>
                            <div class="col-md-6"><strong>Room Type:</strong> {{ $reservation->roomType?->name ?? 'N/A' }}</div>
                            <div class="col-md-6"><strong>Arrival:</strong> {{ optional($reservation->arrival_date)->format('d M Y') }}</div>
                            <div class="col-md-6"><strong>Departure:</strong> {{ optional($reservation->departure_date)->format('d M Y') }}</div>
                            <div class="col-md-6"><strong>Total:</strong> {{ number_format((float) $reservation->total, 2) }}</div>
                            <div class="col-md-6"><strong>Deposit:</strong> {{ number_format((float) $reservation->deposit_received, 2) }}</div>
                            <div class="col-md-6"><strong>Balance:</strong> {{ number_format((float) $reservation->balance, 2) }}</div>
                            <div class="col-md-6"><strong>Special Requests:</strong> {{ $reservation->special_requests ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Operational Actions</h5></div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <a href="{{ route('hotel.reservations.index') }}" class="btn btn-light">View</a>
                            <a href="{{ route('hotel.reservations.create', ['room_type_id' => $reservation->room_type_id]) }}" class="btn btn-light">Duplicate</a>
                            @if(in_array((string)$reservation->status, ['reserved','confirmed']))
                                <form action="{{ route('hotel.checkin', $reservation) }}" method="POST">@csrf<button class="btn btn-success">Check In</button></form>
                            @endif
                            @if($stay)
                                <a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-warning">Checkout</a>
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('hotel.reservations.assign_room', $reservation) }}" class="border rounded p-2">
                                    @csrf
                                    <h6>Change Room / Assign Room</h6>
                                    <select name="room_id" class="form-control mb-2" required>
                                        <option value="">Select room</option>
                                        @foreach($availableRooms as $room)
                                            <option value="{{ $room->id }}" {{ (int)$reservation->room_id === (int)$room->id ? 'selected' : '' }}>{{ $room->room_number }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="reason" class="form-control mb-2" placeholder="Reason">
                                    <button class="btn btn-sm btn-outline-primary">Save Room</button>
                                </form>
                            </div>
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('hotel.reservations.extend', $reservation) }}" class="border rounded p-2">
                                    @csrf
                                    <h6>Extend Stay</h6>
                                    <div class="small mb-1">Current checkout: {{ optional($reservation->departure_date)->format('d M Y') }}</div>
                                    <input type="date" name="new_departure_date" class="form-control mb-2" required>
                                    <button class="btn btn-sm btn-outline-success">Extend</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Operational Timeline</h5></div>
                    <div class="card-body">
                        @forelse($events as $event)
                            <div class="border-start border-3 ps-2 mb-3">
                                <div class="small text-muted">{{ optional($event->created_at)->format('d M Y H:i') }}</div>
                                <div><strong>{{ $event->title }}</strong></div>
                                <div class="small">{{ $event->description }}</div>
                            </div>
                        @empty
                            <div class="alert alert-info mb-0">No timeline events yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
