@extends('layout.mainlayout')

@section('style')
<style>
    .frontdesk-shell {
        --fd-line: #dbe4f0;
        --fd-soft: #f5f7fb;
        --fd-blue: #2563eb;
        --fd-green: #16a34a;
        --fd-amber: #d97706;
        --fd-red: #dc2626;
    }
    .frontdesk-shell .fd-panel {
        background: #fff;
        border: 1px solid var(--fd-line);
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }
    .frontdesk-shell .fd-toolbar,
    .frontdesk-shell .fd-content {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(300px, 0.95fr);
        gap: 16px;
    }
    .frontdesk-shell .fd-filterbar {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
    }
    .frontdesk-shell .fd-room-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }
    .frontdesk-shell .fd-room-tile {
        border: 1px solid var(--fd-line);
        border-radius: 14px;
        padding: 12px;
        background: linear-gradient(180deg, #fff, #f9fbfd);
        min-height: 182px;
    }
    .frontdesk-shell .fd-room-tile[data-state="available"] { border-left: 4px solid var(--fd-green); }
    .frontdesk-shell .fd-room-tile[data-state="occupied"] { border-left: 4px solid var(--fd-blue); }
    .frontdesk-shell .fd-room-tile[data-state="reserved"] { border-left: 4px solid var(--fd-amber); }
    .frontdesk-shell .fd-room-tile[data-state="maintenance"],
    .frontdesk-shell .fd-room-tile[data-state="out_of_order"],
    .frontdesk-shell .fd-room-tile[data-state="dirty"] { border-left: 4px solid var(--fd-red); }
    .frontdesk-shell .fd-queue-item {
        padding: 10px 0;
        border-bottom: 1px solid var(--fd-line);
    }
    .frontdesk-shell .fd-queue-item:last-child { border-bottom: 0; }
    .frontdesk-shell .fd-alert {
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--fd-line);
        background: var(--fd-soft);
        margin-bottom: 8px;
    }
    @media (max-width: 1199px) {
        .frontdesk-shell .fd-toolbar,
        .frontdesk-shell .fd-content { grid-template-columns: 1fr; }
        .frontdesk-shell .fd-room-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .frontdesk-shell .fd-filterbar { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 767px) {
        .frontdesk-shell .fd-room-grid,
        .frontdesk-shell .fd-filterbar { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper frontdesk-shell">
    <div class="content container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h3 class="mb-0">Front Desk</h3>
                <p class="text-muted mb-0">Live hotel operations</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary btn-sm">Reserve</a>
                <a href="{{ route('hotel.walkin.create') }}" class="btn btn-success btn-sm">Walk-In</a>
                <a href="{{ route('hotel.checkin.index') }}" class="btn btn-outline-info btn-sm">Check In</a>
                <a href="{{ route('hotel.checkout.index') }}" class="btn btn-outline-warning btn-sm">Checkout</a>
            </div>
        </div>

        <div class="fd-toolbar mb-3">
            <div class="fd-panel p-3">
                <label class="form-label small mb-1">Search guest, room or reservation</label>
                <form method="GET" action="{{ route('hotel.frontdesk') }}" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="property_id" value="{{ $propertyId ?: 'all' }}">
                    <input type="hidden" name="floor" value="{{ $floor }}">
                    <input type="hidden" name="room_type_id" value="{{ $roomTypeId }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Guest, room, reservation, folio, receipt">
                    <button class="btn btn-outline-primary">Search</button>
                    <a class="btn btn-outline-secondary" href="{{ route('hotel.search', ['q' => $search, 'property_id' => $propertyId ?: 'all']) }}">Grouped Results</a>
                </form>
            </div>
            <form method="GET" action="{{ route('hotel.frontdesk') }}" class="fd-panel p-3">
                <div class="fd-filterbar">
                    <div>
                        <label class="form-label small mb-1">Property</label>
                <select name="property_id" class="form-control">
                    <option value="all" {{ !$propertyId ? 'selected' : '' }}>All Properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" {{ (int)$propertyId === (int)$property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                    @endforeach
                </select>
                    </div>
                    <div>
                        <label class="form-label small mb-1">Floor</label>
                        <select name="floor" class="form-control">
                            <option value="">All Floors</option>
                            @foreach($floors as $floorOption)
                                <option value="{{ $floorOption }}" {{ $floor === (string) $floorOption ? 'selected' : '' }}>{{ $floorOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small mb-1">Room Type</label>
                        <select name="room_type_id" class="form-control">
                            <option value="0">All Types</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}" {{ (int) $roomTypeId === (int) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-control">
                            <option value="">Any Status</option>
                            @foreach(['available','reserved','occupied','dirty','cleaning','maintenance','out_of_order'] as $statusOption)
                                <option value="{{ $statusOption }}" {{ $status === $statusOption ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $statusOption)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small mb-1">View</label>
                        <select name="view" class="form-control">
                            <option value="grid" {{ $viewMode === 'grid' ? 'selected' : '' }}>Grid View</option>
                            <option value="compact" {{ $viewMode === 'compact' ? 'selected' : '' }}>Compact View</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-end">
                        <button class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        @if($alerts->isNotEmpty())
            <div class="fd-panel p-3 mb-3">
                <h5 class="mb-3">Operational Alerts</h5>
                <div class="row g-2">
                    @foreach($alerts as $alert)
                        <div class="col-lg-4 col-md-6"><div class="fd-alert"><strong>{{ $alert['count'] }}</strong> {{ $alert['label'] }}</div></div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="fd-content">
            <div class="fd-panel p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Room Board</h5>
                        <small class="text-muted">{{ $availableCount }} available · {{ $occupiedCount }} occupied · {{ $reservedCount }} reserved · {{ $dirtyCount }} dirty</small>
                    </div>
                </div>
                @if($rooms->isEmpty())
                    <div class="alert alert-info mb-0">No rooms configured for this property. <a href="{{ route('hotel.rooms.create') }}">Add Room</a></div>
                @else
                    <div class="fd-room-grid">
                        @foreach($rooms as $room)
                            @php
                                $activeStay = $activeStaysByRoom->get((int) $room->id);
                                $roomReservation = ($roomReservationsToday->get((int) $room->id) ?? collect())->first();
                                $tileState = $room->housekeeping_status === 'dirty' ? 'dirty' : (string) $room->operational_status;
                            @endphp
                                <div class="fd-room-tile" data-state="{{ $tileState }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong>{{ $room->room_number }}</strong>
                                        <span class="badge {{ $room->operational_status === 'available' ? 'bg-success' : ($room->operational_status === 'occupied' ? 'bg-primary' : ($room->housekeeping_status === 'dirty' ? 'bg-danger' : 'bg-secondary')) }}">{{ strtoupper($room->housekeeping_status === 'dirty' ? 'DIRTY' : (string) $room->operational_status) }}</span>
                                    </div>
                                    <div class="text-muted small">{{ $room->type?->name ?? 'No Type' }}</div>
                                    <div class="small mb-1">Housekeeping: {{ ucfirst((string) $room->housekeeping_status) }}</div>
                                    <div class="small mb-1">Rate: {{ number_format((float) ($room->base_rate_override ?: $room->type?->base_rate ?? 0), 2) }}/night</div>
                                    @if($activeStay)
                                        <div class="small mb-1">Guest: {{ $activeStay->customer?->customer_name ?? $activeStay->customer?->name ?? 'In-House' }}</div>
                                        <div class="small mb-2">Checkout: {{ optional($activeStay->expected_checkout_at)->format('d M H:i') }}</div>
                                    @elseif($roomReservation)
                                        <div class="small mb-1">Arrival Today: {{ $roomReservation->customer?->customer_name ?? $roomReservation->customer?->name ?? 'Reserved' }}</div>
                                        <div class="small mb-2">{{ ucfirst((string) $roomReservation->status) }}</div>
                                    @else
                                        <div class="small mb-2 text-muted">No active guest</div>
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
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="d-grid gap-3">
                <div class="fd-panel p-3">
                    <h5 class="mb-3">Today's Arrivals</h5>
                    @forelse($arrivals->take(6) as $r)
                        <div class="fd-queue-item">
                            <div class="d-flex justify-content-between gap-2"><strong>{{ $r->customer?->customer_name ?? 'N/A' }}</strong><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',(string) $r->status)) }}</span></div>
                            <div class="small text-muted">{{ $r->roomType?->name ?? 'Room Type N/A' }} · {{ $r->room?->room_number ?? 'Unassigned room' }} · Deposit {{ (float) $r->deposit_received > 0 ? 'received' : 'pending' }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No arrivals today.</div>
                    @endforelse
                </div>
                <div class="fd-panel p-3">
                    <h5 class="mb-3">Today's Departures</h5>
                    @forelse($departures->take(6) as $d)
                        <div class="fd-queue-item">
                            <div class="d-flex justify-content-between gap-2"><strong>{{ $d->customer?->customer_name ?? 'N/A' }}</strong><span class="badge {{ ($d->frontdesk_payment_status ?? '') === 'outstanding' ? 'bg-danger' : 'bg-success' }}">{{ ucfirst((string) ($d->frontdesk_payment_status ?? 'unknown')) }}</span></div>
                            <div class="small text-muted">Room {{ $d->room?->room_number ?? 'TBD' }} · Balance {{ number_format((float) ($d->frontdesk_folio_balance ?? 0), 2) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No departures today.</div>
                    @endforelse
                </div>
                <div class="fd-panel p-3">
                    <h5 class="mb-3">Priority Cleaning</h5>
                    @forelse($priorityCleaning as $task)
                        <div class="fd-queue-item">
                            <div class="d-flex justify-content-between gap-2"><strong>Room {{ $task->room?->room_number ?? 'N/A' }}</strong><span class="badge bg-danger">Priority</span></div>
                            <div class="small text-muted">{{ $task->room?->type?->name ?? 'No Type' }} · {{ $task->note ?: 'Urgent turnaround' }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No priority cleaning tasks.</div>
                    @endforelse
                </div>
                <div class="fd-panel p-3">
                    <h5 class="mb-3">Waiting for Room</h5>
                    @forelse($waitingForRoom as $reservation)
                        <div class="fd-queue-item">
                            <strong>{{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'Guest' }}</strong>
                            <div class="small text-muted">{{ $reservation->roomType?->name ?? 'Room Type N/A' }} · Arrival {{ optional($reservation->arrival_date)->format('d M') }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No guests waiting for room assignment.</div>
                    @endforelse
                </div>
                <div class="fd-panel p-3">
                    <h5 class="mb-3">Outstanding Checkout</h5>
                    @forelse($inHouse->filter(fn($stay) => (float) ($stay->frontdesk_balance ?? 0) > 0)->take(6) as $stay)
                        <div class="fd-queue-item">
                            <div class="d-flex justify-content-between gap-2"><strong>{{ $stay->customer?->customer_name ?? 'Guest' }}</strong><span class="text-danger">{{ number_format((float) ($stay->frontdesk_balance ?? 0), 2) }}</span></div>
                            <div class="small text-muted">Room {{ $stay->room?->room_number ?? 'N/A' }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No outstanding checkout balances.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
