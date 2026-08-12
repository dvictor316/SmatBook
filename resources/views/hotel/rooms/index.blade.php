@extends('layout.mainlayout')

@section('style')
<style>
    .room-inventory { background:#f4f6f8; color:#172033; }
    .room-admin-top { display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px; }
    .room-admin-top h3 { font-weight:900; margin:0; }
    .room-admin-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
    .room-admin-stat { background:#fff; border:1px solid #dce3eb; border-radius:6px; padding:13px; box-shadow:0 6px 16px rgba(15,23,42,.04); }
    .room-admin-stat small { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-weight:900; }
    .room-admin-stat strong { display:block; font-size:29px; line-height:1; margin-top:6px; }
    .room-admin-shell { display:grid; grid-template-columns:220px minmax(0,1fr); gap:14px; }
    .room-admin-rail { background:#fff; border:1px solid #dce3eb; border-radius:6px; padding:14px; align-self:start; }
    .room-admin-rail a { display:block; color:#172033; text-decoration:none; padding:11px 0; border-bottom:1px solid #edf1f5; font-weight:800; }
    .room-board-panel { background:#fff; border:1px solid #dce3eb; border-radius:6px; padding:12px; box-shadow:0 8px 22px rgba(15,23,42,.05); }
    .room-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; }
    .room-admin-card { min-height:165px; border:1px solid #d8dde5; border-radius:6px; padding:11px; position:relative; overflow:hidden; background:#fff; color:#475569; }
    .room-admin-card.available { background:#f0fdf4; border-color:#86efac; }
    .room-admin-card.occupied { background:#eff6ff; border-color:#93c5fd; }
    .room-admin-card.reserved { background:#fff7ed; border-color:#fdba74; }
    .room-admin-card.dirty, .room-admin-card.maintenance, .room-admin-card.out_of_order { background:#fff1f2; border-color:#fca5a5; }
    .room-admin-number { font-size:40px; font-weight:300; color:#0b5fb8; line-height:1; }
    .room-admin-card.dirty .room-admin-number, .room-admin-card.maintenance .room-admin-number, .room-admin-card.out_of_order .room-admin-number { color:#dc2626; }
    .room-table th { background:#0b2f54; color:#fff; font-size:12px; text-transform:uppercase; }
    @media(max-width:991px){.room-admin-shell{grid-template-columns:1fr}.room-admin-stats{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.room-admin-stats{grid-template-columns:1fr}.room-card-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endsection

@section('content')
<div class="page-wrapper room-inventory">
    <div class="content container-fluid">
        <div class="room-admin-top">
            <div><h3>Room Inventory Board</h3><p class="text-muted mb-0">Manage room stock, operational status, housekeeping state, current guest and next reservation.</p></div>
            <div class="d-flex gap-2 flex-wrap"><a href="{{ route('hotel.rooms.index', ['view' => 'grid', 'status' => $status]) }}" class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary' }}">Grid</a><a href="{{ route('hotel.rooms.index', ['view' => 'table', 'status' => $status]) }}" class="btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary' }}">Table</a><a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary">New Room</a></div>
        </div>

        <div class="room-admin-stats">
            <div class="room-admin-stat"><small>Available</small><strong class="text-success">{{ $summary['available'] }}</strong></div>
            <div class="room-admin-stat"><small>Occupied</small><strong>{{ $summary['occupied'] }}</strong></div>
            <div class="room-admin-stat"><small>Dirty</small><strong class="text-warning">{{ $summary['dirty'] }}</strong></div>
            <div class="room-admin-stat"><small>Maintenance</small><strong class="text-danger">{{ $summary['maintenance'] }}</strong></div>
        </div>

        <div class="room-admin-shell">
            <aside class="room-admin-rail">
                <h5>Room Tools</h5>
                <a href="{{ route('hotel.frontdesk') }}">Front Desk Room Board</a>
                <a href="{{ route('hotel.rooms.status') }}">Color Room Status</a>
                <a href="{{ route('hotel.rooms.calendar') }}">Reservation Calendar</a>
                <a href="{{ route('hotel.housekeeping.index') }}">Housekeeping</a>
                <a href="{{ route('hotel.maintenance.index') }}">Maintenance</a>
            </aside>

            <main class="room-board-panel">
                @if($rooms->count() === 0)
                    <div class="alert alert-info">No rooms have been configured yet. <a href="{{ route('hotel.rooms.create') }}">Add First Room</a></div>
                @elseif($viewMode === 'grid')
                    <div class="room-card-grid">
                        @foreach($rooms as $room)
                            @php
                                $activeStay = $activeStays->get((int) $room->id);
                                $nextReservation = $nextReservations->get((int) $room->id);
                                $tileState = $room->housekeeping_status === 'dirty' ? 'dirty' : (string) $room->operational_status;
                            @endphp
                            <article class="room-admin-card {{ $tileState }}">
                                <div class="d-flex justify-content-between small"><span>{{ ucfirst(str_replace('_',' ', $tileState)) }}</span><span>⋮ □</span></div>
                                <div class="room-admin-number">{{ $room->room_number }}</div>
                                <strong>{{ $room->type?->name ?? 'No Type' }}</strong>
                                <div class="small text-muted">Floor {{ $room->floor ?: 'N/A' }} · {{ ucfirst((string) $room->housekeeping_status) }}</div>
                                <div class="small mt-2">Guest: {{ $activeStay?->customer?->customer_name ?? $activeStay?->customer?->name ?? 'Vacant' }}</div>
                                <div class="small">Next: {{ $nextReservation?->customer?->customer_name ?? $nextReservation?->customer?->name ?? 'None' }}</div>
                                <div class="d-flex gap-1 flex-wrap mt-2"><a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary">Edit</a><form method="POST" action="{{ route('hotel.rooms.destroy', $room) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Deactivate</button></form></div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="table-responsive"><table class="table table-sm room-table align-middle mb-0"><thead><tr><th>Room #</th><th>Type</th><th>Floor</th><th>Status</th><th>Housekeeping</th><th>Current Guest</th><th>Next Reservation</th><th>Actions</th></tr></thead><tbody>
                    @foreach($rooms as $room)
                        <tr><td><strong>{{ $room->room_number }}</strong></td><td>{{ $room->type?->name }}</td><td>{{ $room->floor }}</td><td>{{ ucfirst((string) $room->operational_status) }}</td><td>{{ ucfirst((string) $room->housekeeping_status) }}</td><td>{{ $activeStays->get((int) $room->id)?->customer?->customer_name ?? $activeStays->get((int) $room->id)?->customer?->name ?? 'Vacant' }}</td><td>{{ $nextReservations->get((int) $room->id)?->customer?->customer_name ?? $nextReservations->get((int) $room->id)?->customer?->name ?? 'None' }}</td><td><a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-info">Edit</a></td></tr>
                    @endforeach
                    </tbody></table></div>
                @endif
                <div class="mt-3">{{ $rooms->links() }}</div>
            </main>
        </div>
    </div>
</div>
@endsection
