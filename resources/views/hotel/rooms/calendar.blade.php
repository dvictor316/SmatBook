@extends('layout.mainlayout')

@section('style')
<style>
    .calendar-shell {
        --cal-line: #d9e2ef;
        --cal-soft: #f5f7fb;
    }
    .calendar-shell .cal-panel {
        background: #fff;
        border: 1px solid var(--cal-line);
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }
    .calendar-shell .cal-toolbar {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 16px;
        margin-bottom: 16px;
    }
    .calendar-shell .cal-filter-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 10px;
    }
    .calendar-shell .cal-stage {
        overflow-x: auto;
    }
    .calendar-shell .cal-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1180px;
        margin-bottom: 0;
    }
    .calendar-shell .cal-table th,
    .calendar-shell .cal-table td {
        border-color: var(--cal-line);
        vertical-align: top;
    }
    .calendar-shell .cal-table th:first-child,
    .calendar-shell .cal-table td:first-child {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 2;
    }
    .calendar-shell .cal-room-label {
        min-width: 180px;
    }
    .calendar-shell .cal-empty-slot {
        min-height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background: linear-gradient(180deg, #fff, #f8fafc);
    }
    .calendar-shell .cal-block {
        min-height: 72px;
        border-radius: 12px;
        padding: 8px 10px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.12);
    }
    .calendar-shell .cal-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }
    .calendar-shell .cal-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #475569;
    }
    .calendar-shell .cal-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        display: inline-block;
    }
    @media (max-width: 1199px) {
        .calendar-shell .cal-toolbar { grid-template-columns: 1fr; }
        .calendar-shell .cal-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 767px) {
        .calendar-shell .cal-filter-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper calendar-shell">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Room Calendar</h3>
                <p class="text-muted mb-0">Timeline view of rooms, stays, and booking conflicts</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-secondary">Front Desk</a>
                <a href="{{ route('hotel.availability.index') }}" class="btn btn-outline-primary">Availability Search</a>
            </div>
        </div>

        <div class="cal-toolbar">
            <form method="GET" class="cal-panel p-3">
                <div class="cal-filter-grid">
                    <div>
                        <label class="form-label">Property</label>
                        <select name="property_id" class="form-control">
                            <option value="all" {{ !$propertyId ? 'selected' : '' }}>All Properties</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" {{ (int) $propertyId === (int) $property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Floor</label>
                        <select name="floor" class="form-control">
                            <option value="">All Floors</option>
                            @foreach($floors as $item)
                                <option value="{{ $item }}" {{ $floor === (string) $item ? 'selected' : '' }}>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Room Type</label>
                        <select name="room_type_id" class="form-control">
                            <option value="0">All Room Types</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}" {{ (int) $roomTypeId === (int) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Room Status</label>
                        <select name="room_status" class="form-control">
                            <option value="">All Room Statuses</option>
                            @foreach(['available','occupied','reserved','maintenance','out_of_order'] as $status)
                                <option value="{{ $status }}" {{ $roomStatus === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Reservation Status</label>
                        <select name="reservation_status" class="form-control">
                            <option value="">All</option>
                            @foreach(['inquiry','reserved','confirmed','checked_in','completed','cancelled','no_show'] as $status)
                                <option value="{{ $status }}" {{ $reservationStatus === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">View</label>
                        <select name="view" class="form-control">
                            <option value="7d" {{ $viewPreset === '7d' ? 'selected' : '' }}>7 Days</option>
                            <option value="14d" {{ $viewPreset === '14d' ? 'selected' : '' }}>14 Days</option>
                            <option value="30d" {{ $viewPreset === '30d' ? 'selected' : '' }}>30 Days</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Custom From</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-end mt-2">
                    <div>
                        <label class="form-label">Custom To</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                    </div>
                    <div>
                        <input type="hidden" name="start_date" value="{{ $start->toDateString() }}">
                        <button class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
            <div class="cal-panel p-3 d-grid gap-2 align-content-start">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('hotel.rooms.calendar', array_merge(request()->query(), ['nav' => 'prev', 'start_date' => $start->toDateString()])) }}">Previous</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('hotel.rooms.calendar', array_merge(request()->query(), ['nav' => 'today', 'start_date' => now()->toDateString()])) }}">Today</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('hotel.rooms.calendar', array_merge(request()->query(), ['nav' => 'next', 'start_date' => $start->toDateString()])) }}">Next</a>
                </div>
                <div class="text-muted small">{{ $start->format('d M Y') }} to {{ $end->format('d M Y') }}</div>
                <div class="badge bg-light text-dark text-start">Unassigned Reservations: {{ $unassignedReservations->count() }}</div>
            </div>
        </div>

        <div class="cal-panel p-3 mb-3">
            <div class="cal-legend">
                <span><i class="cal-dot" style="background:#e2e8f0"></i>Tentative</span>
                <span><i class="cal-dot" style="background:#0ea5e9"></i>Confirmed</span>
                <span><i class="cal-dot" style="background:#2563eb"></i>Guaranteed</span>
                <span><i class="cal-dot" style="background:#16a34a"></i>Checked In</span>
                <span><i class="cal-dot" style="background:#f59e0b"></i>Reserved</span>
                <span><i class="cal-dot" style="background:#dc2626"></i>Blocked/Maintenance</span>
                <span><i class="cal-dot" style="background:#334155"></i>Out of Order</span>
            </div>
            <div class="cal-stage">
                <table class="table table-sm table-bordered align-middle cal-table">
                    <thead>
                    <tr>
                        <th class="cal-room-label">Room</th>
                        @foreach($dates as $date)
                            <th class="text-center" style="min-width:116px;">
                                <div>{{ $date->format('d M') }}</div>
                                <small class="text-muted">{{ $date->format('D') }}</small>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($calendarRows as $row)
                        <tr>
                            <td class="cal-room-label">
                                <strong>{{ $row['room']->room_number }}</strong><br>
                                <small class="text-muted">{{ $row['room']->type?->name ?? 'No Type' }}</small><br>
                                <small class="text-muted">Floor: {{ $row['room']->floor ?: 'N/A' }}</small><br>
                                <span class="badge bg-light text-dark">{{ ucfirst((string) $row['room']->housekeeping_status) }} / {{ ucfirst(str_replace('_',' ', (string) $row['room']->operational_status)) }}</span>
                            </td>
                            @foreach($row['segments'] as $segment)
                                @if($segment['kind'] === 'empty')
                                    <td>
                                        <form method="POST" action="{{ route('hotel.rooms.calendar.quick_create') }}" class="d-grid">
                                            @csrf
                                            <input type="hidden" name="room_id" value="{{ $row['room']->id }}">
                                            <input type="hidden" name="arrival_date" value="{{ $segment['date'] }}">
                                            <input type="hidden" name="departure_date" value="{{ \Carbon\Carbon::parse($segment['date'])->addDay()->toDateString() }}">
                                            <button class="btn btn-link btn-sm cal-empty-slot text-decoration-none">+ Reserve</button>
                                        </form>
                                    </td>
                                    @continue
                                @endif

                                @php
                                    $statusClass = match($segment['status']) {
                                        'tentative' => 'bg-light text-dark border',
                                        'confirmed' => 'bg-info text-dark',
                                        'guaranteed' => 'bg-primary',
                                        'checked_in' => 'bg-success',
                                        'checked_out' => 'bg-secondary',
                                        'maintenance' => 'bg-warning text-dark',
                                        'out_of_order' => 'bg-dark',
                                        'blocked', 'renovation', 'vip_hold', 'management_hold', 'other' => 'bg-danger',
                                        default => 'bg-info',
                                    };
                                @endphp
                                <td colspan="{{ $segment['colspan'] }}" class="p-1">
                                    <div class="cal-block {{ $statusClass }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <strong class="small">{{ $segment['label'] }}</strong>
                                            <span class="small">{{ ucfirst(str_replace('_',' ', (string) $segment['status'])) }}</span>
                                        </div>
                                        <div class="small">{{ $segment['sub'] }}</div>
                                        @if(!empty($segment['arrival']) || !empty($segment['departure']))
                                            <div class="small">{{ $segment['arrival'] }} → {{ $segment['departure'] }}</div>
                                        @endif
                                        @if($segment['kind'] === 'reservation')
                                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                                <a class="btn btn-light btn-xs" href="{{ route('hotel.reservations.show', $segment['id']) }}">View</a>
                                                <a class="btn btn-light btn-xs" href="{{ route('hotel.reservations.show', $segment['id']) }}">Edit</a>
                                                <form method="POST" action="{{ route('hotel.reservations.assign_room', $segment['id']) }}" class="d-flex gap-1">
                                                    @csrf
                                                    <input type="hidden" name="room_id" value="{{ $row['room']->id }}">
                                                    <input type="hidden" name="reason" value="Calendar room assignment">
                                                    <button class="btn btn-light btn-xs">Change Room</button>
                                                </form>
                                            </div>
                                        @endif
                                        @if($segment['kind'] === 'stay')
                                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                                @if(!empty($segment['reservation_id']))
                                                    <a class="btn btn-light btn-xs" href="{{ route('hotel.reservations.show', $segment['reservation_id']) }}">Open Stay</a>
                                                @endif
                                                <a class="btn btn-light btn-xs" href="{{ route('hotel.checkout.index', ['stay_id' => $segment['id']]) }}">Checkout</a>
                                            </div>
                                        @endif
                                    </div>
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

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Room Assignment Required</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Reservation</th><th>Guest</th><th>Arrival</th><th>Room Type</th><th>Assign</th></tr></thead>
                            <tbody>
                            @forelse($unassignedReservations as $reservation)
                                <tr>
                                    <td><a href="{{ route('hotel.reservations.show', $reservation) }}">{{ $reservation->reservation_number }}</a></td>
                                    <td>{{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'N/A' }}</td>
                                    <td>{{ optional($reservation->arrival_date)->format('d M Y') }}</td>
                                    <td>{{ $reservation->roomType?->name ?? 'N/A' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('hotel.reservations.assign_room', $reservation) }}" class="d-flex gap-1">
                                            @csrf
                                            <select name="room_id" class="form-control form-control-sm" required>
                                                <option value="">Room</option>
                                                @foreach($calendarRows as $assignRow)
                                                    @if((int)($assignRow['room']->room_type_id ?? 0) === (int)($reservation->room_type_id ?? 0))
                                                        <option value="{{ $assignRow['room']->id }}">{{ $assignRow['room']->room_number }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="reason" value="Front desk assignment">
                                            <button class="btn btn-sm btn-outline-primary">Assign</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No unassigned confirmed reservations in range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Block Room</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hotel.rooms.calendar.block_room') }}" class="row g-2">
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-control" required>
                                    <option value="">Select room</option>
                                    @foreach($calendarRows as $blockRow)
                                        <option value="{{ $blockRow['room']->id }}">{{ $blockRow['room']->room_number }} - {{ $blockRow['room']->type?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="block_type" class="form-control" required>
                                    <option value="blocked">Blocked</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="renovation">Renovation</option>
                                    <option value="vip_hold">VIP Hold</option>
                                    <option value="management_hold">Management Hold</option>
                                    <option value="out_of_order">Out of Order</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">End</label><input type="date" name="end_date" class="form-control" required></div>
                            <div class="col-12"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control" placeholder="Operational reason"></div>
                            <div class="col-12"><button class="btn btn-outline-danger">Save Block</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
