@extends('layout.mainlayout')

@section('style')
<style>
    .ew-frontdesk { background:#f6f8fb; color:#263238; }
    .ew-desk-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:14px; }
    .ew-desk-head h3 { color:#061b33; font-size:28px; font-weight:800; margin:0; }
    .ew-desk-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .ew-desk-actions .btn, .ew-compact-btn { min-height:34px; padding:6px 12px; border-radius:8px; font-size:13px; font-weight:800; line-height:1.2; }
    .ew-shell { display:grid; grid-template-columns:250px minmax(0,1fr); gap:14px; align-items:start; }
    .ew-rail, .ew-board, .ew-topbar, .ew-sidecard { background:#fff; border:1px solid #d9dee6; border-radius:8px; box-shadow:0 8px 22px rgba(15,23,42,.05); }
    .ew-rail { padding:14px; position:sticky; top:84px; }
    .ew-section { padding:0 0 12px; margin-bottom:12px; border-bottom:1px solid #e8edf3; }
    .ew-section:last-of-type { margin-bottom:0; }
    .ew-section h6 { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#64748b; margin-bottom:8px; font-weight:800; }
    .ew-status-list { display:grid; gap:6px; }
    .ew-check { display:flex; align-items:center; gap:7px; min-height:28px; color:#334155; margin:0; font-size:13px; font-weight:700; }
    .ew-check input { width:14px; height:14px; flex:0 0 auto; }
    .ew-filter-submit { width:100%; margin-top:2px; }
    .ew-main { min-width:0; }
    .ew-topbar { padding:10px; display:grid; grid-template-columns:minmax(220px,1fr) auto; gap:10px; align-items:center; margin-bottom:12px; }
    .ew-topbar-actions { display:flex; gap:7px; flex-wrap:wrap; justify-content:flex-end; }
    .ew-iconbtn { display:inline-flex; align-items:center; justify-content:center; min-height:34px; border:1px solid #d5dce6; background:#fff; color:#0b2f54; border-radius:8px; padding:6px 10px; font-size:13px; font-weight:800; text-decoration:none; white-space:nowrap; }
    .ew-iconbtn.primary { background:#17456f; color:#fff; border-color:#17456f; }
    .ew-iconbtn.green { background:#0f766e; color:#fff; border-color:#0f766e; }
    .ew-board { padding:14px; }
    .ew-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(154px,1fr)); gap:10px; }
    .ew-room { position:relative; min-height:168px; border:1px solid #d8dde5; border-radius:8px; background:#fff; padding:11px; color:#475569; overflow:hidden; display:flex; flex-direction:column; }
    .ew-room.available { background:#fbfffb; }
    .ew-room.occupied { background:#e9f5ff; border-color:#8fc9f4; }
    .ew-room.reserved { background:#edf7e8; border-color:#a8d89a; }
    .ew-room.dirty, .ew-room.maintenance, .ew-room.out_of_order { background:#fde8e8; border-color:#f3a1a1; }
    .ew-room.cleaning { background:#fff5df; border-color:#f2cc76; }
    .ew-room-head { display:flex; justify-content:space-between; align-items:center; gap:8px; font-size:12px; color:#1f2937; }
    .ew-room-more { color:#64748b; font-size:12px; }
    .ew-room-number { font-size:34px; line-height:1; font-weight:800; color:#17456f; margin-top:5px; letter-spacing:0; }
    .ew-room.occupied .ew-room-number { color:#1f7a3a; }
    .ew-room.dirty .ew-room-number, .ew-room.maintenance .ew-room-number, .ew-room.out_of_order .ew-room-number { color:#cf4a39; }
    .ew-room.cleaning .ew-room-number, .ew-room.reserved .ew-room-number { color:#d89020; }
    .ew-room-type { color:#334155; font-size:13px; font-weight:700; }
    .ew-guest { min-height:36px; margin-top:8px; font-size:12px; color:#64748b; }
    .ew-actions { display:flex; gap:6px; margin-top:auto; padding-top:10px; flex-wrap:wrap; }
    .ew-actions .btn { min-height:30px; padding:4px 8px; border-radius:999px; font-size:12px; font-weight:800; line-height:1.2; }
    .ew-right { display:grid; gap:12px; margin-top:14px; grid-template-columns:repeat(4,minmax(0,1fr)); }
    .ew-sidecard { padding:13px; min-height:150px; }
    .ew-sidecard h6 { color:#061b33; font-size:12px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .ew-line { padding:8px 0; border-bottom:1px solid #edf1f5; font-size:13px; }
    .ew-line:last-child { border-bottom:0; }
    @media(max-width:1199px){.ew-shell{grid-template-columns:1fr}.ew-rail{position:static;display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.ew-section{border:1px solid #e8edf3;border-radius:8px;padding:10px;margin:0}.ew-filter-submit{align-self:end}.ew-right{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:991px){.ew-topbar{grid-template-columns:1fr}.ew-topbar-actions{justify-content:flex-start}.ew-grid{grid-template-columns:repeat(auto-fill,minmax(145px,1fr))}}
    @media(max-width:767px){.ew-rail,.ew-right{grid-template-columns:1fr}.ew-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ew-room-number{font-size:30px}.ew-desk-head{align-items:flex-start}.ew-desk-actions{width:100%}.ew-desk-actions .btn{flex:1 1 auto}}
</style>
@endsection

@section('content')
<div class="page-wrapper ew-frontdesk">
    <div class="content container-fluid">
        <div class="ew-desk-head">
            <div>
                <h3 class="mb-1">Front Desk</h3>
                <p class="text-muted mb-0">Live PMS room board, arrivals, departures and desk actions.</p>
            </div>
            <div class="ew-desk-actions">
                <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary">New Reservation</a>
                <a href="{{ route('hotel.walkin.create') }}" class="btn btn-success">Walk-In</a>
                <a href="{{ route('hotel.checkin.index') }}" class="btn btn-outline-primary">Check-In</a>
                <a href="{{ route('hotel.checkout.index') }}" class="btn btn-outline-dark">Checkout</a>
            </div>
        </div>

        <div class="ew-shell">
            <aside class="ew-rail">
                <form method="GET" action="{{ route('hotel.frontdesk') }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <div class="ew-section">
                        <h6>Availability</h6>
                        <div class="ew-status-list">
                            <label class="ew-check"><input type="radio" name="status" value="" {{ $status === '' ? 'checked' : '' }}> All rooms</label>
                            <label class="ew-check"><input type="radio" name="status" value="available" {{ $status === 'available' ? 'checked' : '' }}> Available ({{ $availableCount }})</label>
                            <label class="ew-check"><input type="radio" name="status" value="occupied" {{ $status === 'occupied' ? 'checked' : '' }}> Occupied ({{ $occupiedCount }})</label>
                            <label class="ew-check"><input type="radio" name="status" value="reserved" {{ $status === 'reserved' ? 'checked' : '' }}> Reserved ({{ $reservedCount }})</label>
                            <label class="ew-check"><input type="radio" name="status" value="dirty" {{ $status === 'dirty' ? 'checked' : '' }}> Dirty ({{ $dirtyCount }})</label>
                        </div>
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
                    <button class="btn btn-dark ew-compact-btn ew-filter-submit">Apply Filters</button>
                </form>
            </aside>

            <main class="ew-main">
                <form method="GET" action="{{ route('hotel.frontdesk') }}" class="ew-topbar">
                    <input type="hidden" name="property_id" value="{{ $propertyId ?: 'all' }}">
                    <input type="hidden" name="floor" value="{{ $floor }}">
                    <input type="hidden" name="room_type_id" value="{{ $roomTypeId }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search guest, room or reservation...">
                    <div class="ew-topbar-actions">
                        <button class="ew-iconbtn primary" type="submit">Search</button>
                        <a class="ew-iconbtn" href="{{ route('hotel.rooms.status') }}">Room State</a>
                        <a class="ew-iconbtn green" href="{{ route('hotel.housekeeping.index') }}">Housekeeping</a>
                        <a class="ew-iconbtn" href="{{ route('hotel.folios.index') }}">Folios</a>
                        <a class="ew-iconbtn" href="{{ route('hotel.night_audit.index') }}">Night Audit</a>
                    </div>
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
                                    <div class="ew-room-head"><span>{{ ucfirst(str_replace('_',' ', $tileState)) }}</span><span class="ew-room-more">⋮</span></div>
                                    <div class="ew-room-number">{{ $room->room_number }}</div>
                                    <div class="ew-room-type">{{ $room->type?->name ?? 'Standard' }}</div>
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
