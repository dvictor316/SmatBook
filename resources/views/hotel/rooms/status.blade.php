@extends('layout.mainlayout')

@section('style')
<style>
    .hotel-room-state { background:#f4f6f8; }
    .hotel-room-state .state-toolbar { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:center; margin-bottom:18px; }
    .hotel-room-state .state-counts { display:flex; gap:8px; flex-wrap:wrap; }
    .hotel-room-state .state-count { min-width:112px; background:#fff; border:1px solid #dce3eb; padding:12px; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,.04); }
    .hotel-room-state .state-count strong { display:block; font-size:28px; line-height:1; }
    .hotel-room-state .room-tile-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:4px; background:#fff; border:1px solid #dce3eb; padding:4px; }
    .hotel-room-state .room-block { min-height:150px; padding:18px; color:#fff; position:relative; display:flex; flex-direction:column; justify-content:space-between; text-decoration:none; }
    .hotel-room-state .room-block.available, .hotel-room-state .room-block.clean { background:#69b62f; }
    .hotel-room-state .room-block.dirty, .hotel-room-state .room-block.reserved { background:#e88a10; }
    .hotel-room-state .room-block.occupied { background:#1f77b4; }
    .hotel-room-state .room-block.maintenance, .hotel-room-state .room-block.out_of_order { background:#c91f26; }
    .hotel-room-state .room-number { font-size:42px; font-weight:300; line-height:1; }
    .hotel-room-state .room-type { font-size:15px; opacity:.95; }
    .hotel-room-state .room-footer { display:flex; justify-content:space-between; gap:10px; font-size:12px; text-transform:uppercase; opacity:.9; }
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
            <div class="d-flex gap-2 flex-wrap"><a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Open Calendar</a><a href="{{ route('hotel.frontdesk') }}" class="btn btn-primary">Front Desk</a></div>
        </div>
        <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['available','occupied','reserved','maintenance','out_of_order'] as $s)<option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Housekeeping</label><select name="housekeeping" class="form-control"><option value="">All</option>@foreach(['clean','dirty','cleaning','inspection'] as $h)<option value="{{ $h }}" {{ $housekeeping === $h ? 'selected' : '' }}>{{ ucfirst($h) }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label">Floor</label><select name="floor" class="form-control"><option value="">All</option>@foreach($floors as $f)<option value="{{ $f }}" {{ $floor === (string)$f ? 'selected' : '' }}>{{ $f }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Room Type</label><select name="room_type_id" class="form-control"><option value="0">All</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}" {{ (int)$roomTypeId === (int)$type->id ? 'selected' : '' }}>{{ $type->name }}</option>@endforeach</select></div><div class="col-md-1"><button class="btn btn-success w-100">Go</button></div></div></form>
        <div class="state-counts mb-3"><div class="state-count"><strong>{{ $statusTotals['available'] ?? 0 }}</strong><span>Available</span></div><div class="state-count"><strong>{{ $statusTotals['occupied'] ?? 0 }}</strong><span>Occupied</span></div><div class="state-count"><strong>{{ $statusTotals['reserved'] ?? 0 }}</strong><span>Reserved</span></div><div class="state-count"><strong>{{ $statusTotals['maintenance'] ?? 0 }}</strong><span>Maintenance</span></div><div class="state-count"><strong>{{ $rooms->total() }}</strong><span>All</span></div></div>
        <div class="room-tile-grid">
            @forelse($rooms as $room)
                @php $state = in_array((string)$room->operational_status, ['maintenance','out_of_order','occupied','reserved'], true) ? (string)$room->operational_status : (string)($room->housekeeping_status ?: $room->operational_status); @endphp
                <a href="{{ route('hotel.rooms.edit', $room) }}" class="room-block {{ $state }}">
                    <div><div class="room-number">{{ $room->room_number }}</div><div class="room-type">{{ $room->type?->name ?? 'Room' }}</div></div>
                    <div class="room-footer"><span>{{ strtoupper((string)$room->operational_status) }}</span><span>{{ strtoupper((string)$room->housekeeping_status) }}</span></div>
                </a>
            @empty
                <div class="alert alert-info m-3">No rooms configured.</div>
            @endforelse
        </div>
        <div class="mt-3">{{ $rooms->links() }}</div>
    </div>
</div>
@endsection
