@extends('layout.mainlayout')

@section('style')
<style>
    .hotel-room-state { background:#f4f6f8; }
    .hotel-room-state .state-toolbar { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:center; margin-bottom:18px; }
    .hotel-room-state .state-counts { display:flex; gap:8px; flex-wrap:wrap; }
    .hotel-room-state .state-count { min-width:112px; background:#fff; border:1px solid #dce3eb; padding:12px; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,.04); }
    .hotel-room-state .state-count strong { display:block; font-size:28px; line-height:1; }
    .hotel-room-state .room-tile-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:14px; }
    .hotel-room-state .room-block { min-height:260px; background:#fff; border:1px solid #dce3eb; box-shadow:0 10px 24px rgba(15,35,70,.08); overflow:hidden; display:flex; flex-direction:column; }
    .hotel-room-state .room-strip { min-height:118px; padding:18px; color:#fff; display:flex; justify-content:space-between; align-items:flex-start; gap:14px; }
    .hotel-room-state .room-block.available .room-strip, .hotel-room-state .room-block.clean .room-strip { background:#208d54; }
    .hotel-room-state .room-block.dirty .room-strip, .hotel-room-state .room-block.reserved .room-strip { background:#d97706; }
    .hotel-room-state .room-block.occupied .room-strip { background:#1769aa; }
    .hotel-room-state .room-block.maintenance .room-strip, .hotel-room-state .room-block.out_of_order .room-strip { background:#b91c1c; }
    .hotel-room-state .room-number { font-size:46px; font-weight:700; line-height:1; letter-spacing:0; }
    .hotel-room-state .room-type { font-size:15px; opacity:.95; margin-top:6px; }
    .hotel-room-state .room-chip { display:inline-flex; align-items:center; justify-content:center; min-height:32px; padding:6px 10px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.35); color:#fff; font-size:12px; font-weight:700; text-transform:uppercase; white-space:nowrap; }
    .hotel-room-state .room-body { padding:16px; display:flex; flex-direction:column; gap:12px; flex:1; }
    .hotel-room-state .room-meta { display:grid; grid-template-columns:1fr 1fr; gap:8px; color:#536b88; font-size:13px; }
    .hotel-room-state .room-meta strong { display:block; color:#092452; font-size:14px; }
    .hotel-room-state .room-actions { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; margin-top:auto; }
    .hotel-room-state .room-actions .btn, .hotel-room-state .room-actions form { width:100%; }
    .hotel-room-state .room-actions .btn { min-height:42px; display:flex; align-items:center; justify-content:center; gap:8px; font-weight:700; }
    .hotel-room-state .inline-form { margin:0; }
    @media (max-width: 575.98px) {
        .hotel-room-state .room-tile-grid { grid-template-columns:1fr; }
        .hotel-room-state .room-actions { grid-template-columns:1fr; }
    }
</style>
@endsection

@section('content')
<div class="page-wrapper hotel-room-state">
    <div class="content container-fluid">
        <div class="state-toolbar">
            <div>
                <h3 class="mb-1">Room Status for Today</h3>
                <p class="text-muted mb-0">Colour-coded live room state board.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap"><button type="button" onclick="window.print()" class="btn btn-outline-secondary">Print Board</button><a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Open Calendar</a><a href="{{ route('hotel.frontdesk') }}" class="btn btn-primary">Front Desk</a></div>
        </div>
        <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['available','occupied','reserved','maintenance','out_of_order'] as $s)<option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Housekeeping</label><select name="housekeeping" class="form-control"><option value="">All</option>@foreach(['clean','dirty','cleaning','inspection'] as $h)<option value="{{ $h }}" {{ $housekeeping === $h ? 'selected' : '' }}>{{ ucfirst($h) }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label">Floor</label><select name="floor" class="form-control"><option value="">All</option>@foreach($floors as $f)<option value="{{ $f }}" {{ $floor === (string)$f ? 'selected' : '' }}>{{ $f }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Room Type</label><select name="room_type_id" class="form-control"><option value="0">All</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}" {{ (int)$roomTypeId === (int)$type->id ? 'selected' : '' }}>{{ $type->name }}</option>@endforeach</select></div><div class="col-md-1"><button class="btn btn-success w-100">Go</button></div></div></form>
        <div class="state-counts mb-3"><div class="state-count"><strong>{{ $statusTotals['available'] ?? 0 }}</strong><span>Available</span></div><div class="state-count"><strong>{{ $statusTotals['occupied'] ?? 0 }}</strong><span>Occupied</span></div><div class="state-count"><strong>{{ $statusTotals['reserved'] ?? 0 }}</strong><span>Reserved</span></div><div class="state-count"><strong>{{ $statusTotals['maintenance'] ?? 0 }}</strong><span>Maintenance</span></div><div class="state-count"><strong>{{ $rooms->total() }}</strong><span>All</span></div></div>
        <div class="room-tile-grid">
            @forelse($rooms as $room)
                @php $state = in_array((string)$room->operational_status, ['maintenance','out_of_order','occupied','reserved'], true) ? (string)$room->operational_status : (string)($room->housekeeping_status ?: $room->operational_status); @endphp
                <article class="room-block {{ $state }}">
                    <div class="room-strip">
                        <div>
                            <div class="room-number">{{ $room->room_number }}</div>
                            <div class="room-type">{{ $room->type?->name ?? 'Room' }}</div>
                        </div>
                        <span class="room-chip">{{ str_replace('_', ' ', (string)$room->operational_status) }}</span>
                    </div>
                    <div class="room-body">
                        <div class="room-meta">
                            <span><strong>Housekeeping</strong>{{ ucfirst((string)$room->housekeeping_status) }}</span>
                            <span><strong>Floor / Wing</strong>{{ $room->floor ?: 'Floor not set' }}{{ $room->wing ? ' - ' . $room->wing : '' }}</span>
                        </div>
                        <div class="room-actions">
                            <a href="{{ route('hotel.rooms.show', $room) }}" class="btn btn-outline-primary">View</a>
                            <a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-primary">Edit</a>
                            @if((string)$room->housekeeping_status !== 'clean')
                                <form method="POST" action="{{ route('hotel.housekeeping.rooms.clean', $room) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Mark Clean</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('hotel.housekeeping.rooms.dirty', $room) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">Mark Dirty</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('hotel.housekeeping.tasks.store') }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="room_id" value="{{ $room->id }}">
                                <input type="hidden" name="task_type" value="ad_hoc_clean">
                                <input type="hidden" name="priority" value="{{ in_array((string)$room->housekeeping_status, ['dirty','cleaning','inspection'], true) ? 'high' : 'normal' }}">
                                <input type="hidden" name="note" value="Opened from the room status board.">
                                <button type="submit" class="btn btn-outline-secondary">HK Task</button>
                            </form>
                            <form method="POST" action="{{ route('hotel.maintenance.store') }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="room_id" value="{{ $room->id }}">
                                <input type="hidden" name="title" value="Room {{ $room->room_number }} maintenance check">
                                <input type="hidden" name="description" value="Maintenance ticket opened from the room status board.">
                                <input type="hidden" name="severity" value="medium">
                                <button type="submit" class="btn btn-outline-danger">Maintenance</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="alert alert-info m-3">No rooms configured.</div>
            @endforelse
        </div>
        <div class="mt-3">{{ $rooms->links() }}</div>
    </div>
</div>
@endsection
