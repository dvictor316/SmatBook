@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h3 class="mb-0">Front Desk Command Board</h3>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary btn-sm">Reserve</a>
                <a href="{{ route('hotel.walkin.create') }}" class="btn btn-success btn-sm">Walk-In</a>
                <a href="{{ route('hotel.checkin.index') }}" class="btn btn-outline-info btn-sm">Check In</a>
                <a href="{{ route('hotel.checkout.index') }}" class="btn btn-outline-warning btn-sm">Checkout</a>
            </div>
        </div>

        <form method="GET" action="{{ route('hotel.frontdesk') }}" class="row g-2 align-items-center mb-3">
            <div class="col-lg-3 col-md-4">
                <select name="property_id" class="form-control">
                    <option value="all" {{ !$propertyId ? 'selected' : '' }}>All Properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" {{ (int)$propertyId === (int)$property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6 col-md-8">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Global search: guest, phone, email, reservation, room, folio, invoice, receipt">
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary">Search</button>
            </div>
            <div class="col-auto">
                <a class="btn btn-outline-secondary" href="{{ route('hotel.search', ['q' => $search, 'property_id' => $propertyId ?: 'all']) }}">Grouped Results</a>
            </div>
        </form>

        @if($alerts->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Operational Alerts</h5></div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($alerts as $alert)
                            <div class="col-xl-4 col-md-6">
                                <div class="alert mb-0 {{ $alert['severity'] === 'danger' ? 'alert-danger' : ($alert['severity'] === 'warning' ? 'alert-warning' : 'alert-info') }}">
                                    <strong>{{ $alert['count'] }}</strong> {{ $alert['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Available Rooms</small><h4>{{ $availableCount }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Occupied Rooms</small><h4>{{ $occupiedCount }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Reserved Rooms</small><h4>{{ $reservedCount }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Dirty Rooms</small><h4>{{ $dirtyCount }}</h4></div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Room Status Grid</h5></div>
            <div class="card-body">
                @if($rooms->isEmpty())
                    <div class="alert alert-info mb-0">No rooms available for front desk board.</div>
                @else
                    <div class="row g-3">
                        @foreach($rooms as $room)
                            @php
                                $activeStay = $activeStaysByRoom->get((int) $room->id);
                                $roomReservation = ($roomReservationsToday->get((int) $room->id) ?? collect())->first();
                            @endphp
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <div class="border rounded p-2 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong>{{ $room->room_number }}</strong>
                                        <span class="badge {{ $room->operational_status === 'available' ? 'bg-success' : ($room->operational_status === 'occupied' ? 'bg-danger' : 'bg-secondary') }}">{{ ucfirst((string) $room->operational_status) }}</span>
                                    </div>
                                    <div class="text-muted small">{{ $room->type?->name ?? 'No Type' }}</div>
                                    <div class="small mb-2">HK: {{ ucfirst((string) $room->housekeeping_status) }}</div>
                                    @if($activeStay)
                                        <div class="small mb-2">Guest: {{ $activeStay->customer?->customer_name ?? $activeStay->customer?->name ?? 'In-House' }}</div>
                                    @elseif($roomReservation)
                                        <div class="small mb-2">Arrival: {{ $roomReservation->customer?->customer_name ?? $roomReservation->customer?->name ?? 'Reserved' }}</div>
                                    @endif
                                    <div class="d-grid gap-1">
                                        @if($room->operational_status === 'available')
                                            <a href="{{ route('hotel.reservations.create', ['room_id' => $room->id, 'room_type_id' => $room->room_type_id]) }}" class="btn btn-sm btn-light">Reserve</a>
                                            <a href="{{ route('hotel.walkin.create') }}" class="btn btn-sm btn-outline-success">Walk-In</a>
                                        @elseif($room->operational_status === 'reserved' && $roomReservation)
                                            <a href="{{ route('hotel.reservations.show', $roomReservation) }}" class="btn btn-sm btn-light">View Reservation</a>
                                            <form method="POST" action="{{ route('hotel.checkin', $roomReservation) }}">@csrf<button class="btn btn-sm btn-outline-success w-100">Check In</button></form>
                                        @elseif($room->operational_status === 'occupied' && $activeStay)
                                            <a href="{{ route('hotel.checkout.index', ['stay_id' => $activeStay->id]) }}" class="btn btn-sm btn-light">Open Folio</a>
                                            <a href="{{ route('hotel.checkout.index', ['stay_id' => $activeStay->id]) }}" class="btn btn-sm btn-outline-warning">Checkout</a>
                                        @elseif($room->housekeeping_status === 'dirty')
                                            <form method="POST" action="{{ route('hotel.housekeeping.rooms.clean', $room) }}">@csrf<button class="btn btn-sm btn-outline-primary w-100">Mark Clean</button></form>
                                        @elseif(in_array($room->operational_status, ['maintenance', 'out_of_order']))
                                            <a href="{{ route('hotel.maintenance.index') }}" class="btn btn-sm btn-outline-dark">View Ticket</a>
                                        @else
                                            <a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-light">Open Room</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Today's Arrivals</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Reservation</th><th>Guest</th><th>Room Type</th><th>Room</th><th>Deposit</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            @forelse($arrivals as $r)
                                <tr>
                                    <td><a href="{{ route('hotel.reservations.show', $r->id) }}">{{ $r->reservation_number }}</a></td>
                                    <td>{{ $r->customer?->customer_name ?? 'N/A' }}</td>
                                    <td>{{ $r->roomType?->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($r->room_id)
                                            {{ $r->room?->room_number }}
                                        @else
                                            <span class="badge bg-warning text-dark">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>{{ (float) $r->deposit_received > 0 ? 'Received' : 'Pending' }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',(string) $r->status)) }}</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('hotel.reservations.show', $r) }}" class="btn btn-sm btn-light">Open</a>
                                            @if($r->room_id)
                                                <form method="POST" action="{{ route('hotel.checkin', $r) }}">@csrf<button class="btn btn-sm btn-outline-success">Check In</button></form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">No arrivals today.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Today's Departures</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Balance</th><th>Payment</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse($departures as $d)
                                <tr>
                                    <td>{{ $d->reservation_number }}</td>
                                    <td>{{ $d->customer?->customer_name ?? 'N/A' }}</td>
                                    <td>{{ $d->room?->room_number ?? 'TBD' }}</td>
                                    <td>{{ number_format((float) ($d->frontdesk_folio_balance ?? 0), 2) }}</td>
                                    <td>
                                        <span class="badge {{ ($d->frontdesk_payment_status ?? '') === 'outstanding' ? 'bg-danger' : 'bg-success' }}">{{ ucfirst((string) ($d->frontdesk_payment_status ?? 'unknown')) }}</span>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ',(string) $d->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No departures today.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">In-House Guests</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Stay</th><th>Guest</th><th>Room</th><th>Balance</th><th>Action</th></tr></thead>
                            <tbody>
                            @forelse($inHouse as $s)
                                <tr>
                                    <td>#{{ $s->id }}</td>
                                    <td>{{ $s->customer?->customer_name ?? 'N/A' }}</td>
                                    <td>{{ $s->room?->room_number ?? 'N/A' }}</td>
                                    <td>{{ number_format((float) ($s->frontdesk_balance ?? 0), 2) }}</td>
                                    <td><a href="{{ route('hotel.checkout.index', ['stay_id' => $s->id]) }}" class="btn btn-sm btn-outline-warning">Checkout</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No in-house guests.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Waiting / Pending Check-ins</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Reservation</th><th>Guest</th><th>Arrival</th><th>Action</th></tr></thead>
                            <tbody>
                            @forelse($pendingCheckins as $p)
                                <tr>
                                    <td>{{ $p->reservation_number }}</td>
                                    <td>{{ $p->customer?->customer_name ?? 'N/A' }}</td>
                                    <td>{{ optional($p->arrival_date)->format('d M Y') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('hotel.checkin', $p->id) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">Check In</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No pending check-ins.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
