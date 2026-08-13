@extends('layout.mainlayout')

@section('style')
<style>
    .ew-frontdesk { background:#f6f7f8; color:#263238; }
    .ew-shell { display:grid; grid-template-columns:230px minmax(0,1fr); gap:16px; }
    .ew-rail, .ew-board, .ew-topbar, .ew-sidecard { background:#fff; border:1px solid #d9dee6; box-shadow:0 6px 18px rgba(15,23,42,.05); }
    .ew-rail { border-radius:4px; padding:14px; }
    .ew-section { border-bottom:1px solid #e8edf3; padding:12px 0; }
    .ew-section:last-child { border-bottom:0; }
    .ew-section h6 { font-size:12px; text-transform:uppercase; letter-spacing:.08em; color:#4b5563; margin-bottom:10px; }
    .ew-check { display:flex; align-items:center; gap:8px; color:#4b5563; margin:7px 0; font-size:13px; }
    .ew-main { min-width:0; }
    .ew-topbar { border-radius:4px; padding:12px; display:grid; grid-template-columns:minmax(220px,1fr) repeat(5,auto); gap:10px; align-items:center; margin-bottom:14px; }
    .ew-iconbtn { border:1px solid #d5dce6; background:#fff; color:#0b2f54; border-radius:6px; padding:9px 12px; font-weight:600; }
    .ew-iconbtn.primary { background:#1d5fd1; color:#fff; border-color:#1d5fd1; }
    .ew-iconbtn.green { background:#159447; color:#fff; border-color:#159447; }
    .ew-board { border-radius:4px; padding:14px; }
    .ew-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(126px,1fr)); gap:10px; }
    .ew-room { position:relative; min-height:142px; border:1px solid #d8dde5; border-radius:5px; background:#fff; padding:9px; color:#475569; overflow:hidden; }
    .ew-room.available { background:#fbfffb; }
    .ew-room.occupied { background:#e9f5ff; border-color:#8fc9f4; }
    .ew-room.reserved { background:#edf7e8; border-color:#a8d89a; }
    .ew-room.dirty, .ew-room.maintenance, .ew-room.out_of_order { background:#fde8e8; border-color:#f3a1a1; }
    .ew-room.cleaning { background:#fff5df; border-color:#f2cc76; }
    .ew-room-head { display:flex; justify-content:space-between; align-items:center; gap:8px; font-size:12px; color:#1f2937; }
    .ew-room-number { font-size:34px; line-height:1; font-weight:300; color:#1f9bd1; margin-top:4px; }
    .ew-room.occupied .ew-room-number { color:#1f7a3a; }
    .ew-room.dirty .ew-room-number, .ew-room.maintenance .ew-room-number, .ew-room.out_of_order .ew-room-number { color:#cf4a39; }
    .ew-room.cleaning .ew-room-number, .ew-room.reserved .ew-room-number { color:#d89020; }
    .ew-guest { min-height:34px; font-size:12px; color:#64748b; }
    .ew-actions { display:flex; gap:4px; margin-top:7px; }
    .ew-actions .btn { padding:4px 6px; font-size:11px; }
    .ew-right { display:grid; gap:12px; margin-top:14px; grid-template-columns:repeat(4,minmax(0,1fr)); }
    .ew-sidecard { border-radius:4px; padding:13px; min-height:150px; }
    .ew-line { padding:8px 0; border-bottom:1px solid #edf1f5; font-size:13px; }
    .ew-line:last-child { border-bottom:0; }
    @media(max-width:1199px){.ew-shell{grid-template-columns:1fr}.ew-rail{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.ew-section{border:1px solid #e8edf3;padding:10px}.ew-topbar{grid-template-columns:1fr 1fr 1fr}.ew-right{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:767px){.ew-rail,.ew-topbar,.ew-right{grid-template-columns:1fr}.ew-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ew-room-number{font-size:28px}}
</style>
@endsection

@section('content')
<div class="page-wrapper ew-frontdesk">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="mb-1">Front Desk</h3>
                <p class="text-muted mb-0">Live PMS room board, arrivals, departures and desk actions.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary btn-sm">New Reservation</a>
                <a href="{{ route('hotel.walkin.create') }}" class="btn btn-success btn-sm">Walk-In</a>
                <a href="{{ route('hotel.checkin.index') }}" class="btn btn-outline-primary btn-sm">Check-In</a>
                <a href="{{ route('hotel.checkout.index') }}" class="btn btn-outline-dark btn-sm">Checkout</a>
            </div>
        </div>

        <div class="ew-shell">
            <aside class="ew-rail">
                <form method="GET" action="{{ route('hotel.frontdesk') }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <div class="ew-section">
                        <h6>Availability</h6>
                        <label class="ew-check"><input type="radio" name="status" value="" {{ $status === '' ? 'checked' : '' }}> All rooms</label>
                        <label class="ew-check"><input type="radio" name="status" value="available" {{ $status === 'available' ? 'checked' : '' }}> Available ({{ $availableCount }})</label>
                        <label class="ew-check"><input type="radio" name="status" value="occupied" {{ $status === 'occupied' ? 'checked' : '' }}> Occupied ({{ $occupiedCount }})</label>
                        <label class="ew-check"><input type="radio" name="status" value="reserved" {{ $status === 'reserved' ? 'checked' : '' }}> Reserved ({{ $reservedCount }})</label>
                        <label class="ew-check"><input type="radio" name="status" value="dirty" {{ $status === 'dirty' ? 'checked' : '' }}> Dirty ({{ $dirtyCount }})</label>
                    </div>
                    <div class="ew-section">
                        <h6>Room Type</h6>
                        <select name="room_type_id" class="form-control form-control-sm">
                            <option value="0">All Types</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}" {{ (int) $roomTypeId === (int) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ew-section">
                        <h6>Floor</h6>
                        <select name="floor" class="form-control form-control-sm">
                            <option value="">All Floors</option>
                            @foreach($floors as $floorOption)
                                <option value="{{ $floorOption }}" {{ $floor === (string) $floorOption ? 'selected' : '' }}>Floor {{ $floorOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ew-section">
                        <h6>Property</h6>
                        <select name="property_id" class="form-control form-control-sm">
                            <option value="all" {{ !$propertyId ? 'selected' : '' }}>All Properties</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" {{ (int)$propertyId === (int)$property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-dark btn-sm w-100 mt-2">Apply Filters</button>
                </form>
            </aside>

            <main class="ew-main">
                <form method="GET" action="{{ route('hotel.frontdesk') }}" class="ew-topbar">
                    <input type="hidden" name="property_id" value="{{ $propertyId ?: 'all' }}">
                    <input type="hidden" name="floor" value="{{ $floor }}">
                    <input type="hidden" name="room_type_id" value="{{ $roomTypeId }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search guest, room or reservation...">
                    <button class="ew-iconbtn primary" type="submit">Search</button>
                    <a class="ew-iconbtn" href="{{ route('hotel.rooms.status') }}">Room State</a>
                    <a class="ew-iconbtn green" href="{{ route('hotel.housekeeping.index') }}">Housekeeping</a>
                    <a class="ew-iconbtn" href="{{ route('hotel.folios.index') }}">Folios</a>
                    <a class="ew-iconbtn" href="{{ route('hotel.night_audit.index') }}">Night Audit</a>
                </form>

                <section class="ew-board">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div><strong>Room Board</strong><div class="small text-muted">Compact PMS view with live guest and room states</div></div>
                        <span class="badge bg-light text-dark">{{ $rooms->count() }} rooms loaded</span>
                    </div>
                    @if($rooms->isEmpty())
                        <div class="alert alert-info mb-0">No rooms configured for this property. <a href="{{ route('hotel.rooms.create') }}">Add Room</a></div>
                    @else
                        <div class="ew-grid">
                            @foreach($rooms as $room)
                                @php
                                    $activeStay = $activeStaysByRoom->get((int) $room->id);
                                    $roomReservation = ($roomReservationsToday->get((int) $room->id) ?? collect())->first();
                                    $tileState = $room->housekeeping_status === 'dirty' ? 'dirty' : (string) $room->operational_status;
                                    $guestName = $activeStay?->customer?->customer_name ?? $activeStay?->customer?->name ?? $roomReservation?->customer?->customer_name ?? $roomReservation?->customer?->name ?? null;
                                @endphp
                                <div class="ew-room {{ $tileState }}">
                                    <div class="ew-room-head"><span>{{ ucfirst(str_replace('_',' ', $tileState)) }}</span><span>⋮ □</span></div>
                                    <div class="ew-room-number">{{ $room->room_number }}</div>
                                    <div class="small">{{ $room->type?->name ?? 'Standard' }}</div>
                                    <div class="ew-guest mt-2">{{ $guestName ?: 'Vacant' }}<br><span>{{ ucfirst((string) $room->housekeeping_status) }}</span></div>
                                    <div class="ew-actions">
                                        @if($activeStay)
                                            <a href="{{ route('hotel.checkout.index', ['stay_id' => $activeStay->id]) }}" class="btn btn-light border">Folio</a>
                                            <a href="{{ route('hotel.checkout.index', ['stay_id' => $activeStay->id]) }}" class="btn btn-outline-dark">Out</a>
                                        @elseif($roomReservation)
                                            <a href="{{ route('hotel.reservations.show', $roomReservation) }}" class="btn btn-light border">View</a>
                                            <form method="POST" action="{{ route('hotel.checkin', $roomReservation) }}">@csrf<button class="btn btn-outline-success">In</button></form>
                                        @elseif($room->housekeeping_status === 'dirty')
                                            <form method="POST" action="{{ route('hotel.housekeeping.rooms.clean', $room) }}">@csrf<button class="btn btn-outline-primary">Clean</button></form>
                                        @else
                                            <a href="{{ route('hotel.reservations.create', ['room_id' => $room->id, 'room_type_id' => $room->room_type_id]) }}" class="btn btn-light border">Reserve</a>
                                            <a href="{{ route('hotel.walkin.create') }}" class="btn btn-outline-success">Walk-In</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <div class="ew-right">
                    <div class="ew-sidecard"><h6>Today's Arrivals</h6>@forelse($arrivals->take(4) as $r)<div class="ew-line"><strong>{{ $r->customer?->customer_name ?? 'Guest' }}</strong><br><span class="text-muted">{{ $r->room?->room_number ?? 'Unassigned' }} · {{ $r->roomType?->name ?? 'Room' }}</span></div>@empty<div class="text-muted small">No arrivals today.</div>@endforelse</div>
                    <div class="ew-sidecard"><h6>Today's Departures</h6>@forelse($departures->take(4) as $d)<div class="ew-line"><strong>{{ $d->customer?->customer_name ?? 'Guest' }}</strong><br><span class="text-muted">Room {{ $d->room?->room_number ?? 'N/A' }} · {{ number_format((float)($d->frontdesk_folio_balance ?? 0),2) }}</span></div>@empty<div class="text-muted small">No departures today.</div>@endforelse</div>
                    <div class="ew-sidecard"><h6>Priority Cleaning</h6>@forelse($priorityCleaning->take(4) as $task)<div class="ew-line"><strong>Room {{ $task->room?->room_number ?? 'N/A' }}</strong><br><span class="text-muted">{{ $task->note ?: 'Urgent turnaround' }}</span></div>@empty<div class="text-muted small">No priority cleaning.</div>@endforelse</div>
                    <div class="ew-sidecard"><h6>Waiting Rooms</h6>@forelse($waitingForRoom->take(4) as $reservation)<div class="ew-line"><strong>{{ $reservation->customer?->customer_name ?? 'Guest' }}</strong><br><span class="text-muted">{{ $reservation->roomType?->name ?? 'Room type' }}</span></div>@empty<div class="text-muted small">No waiting guests.</div>@endforelse</div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
