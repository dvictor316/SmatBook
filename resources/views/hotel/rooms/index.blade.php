@extends('layout.mainlayout')

@section('style')
<style>
    .room-inventory { background:#f5f8fc; color:#0b1f36; }
    .room-admin-top { display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px; }
    .room-admin-top h3 { font-weight:700; margin:0; color:#061b33; }
    .room-admin-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .room-admin-stat { background:#fff; border:1px solid #d8e2ee; border-radius:16px; padding:14px; box-shadow:0 12px 28px rgba(15,23,42,.06); }
    .room-admin-stat small { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-weight:700; }
    .room-admin-stat strong { display:block; font-size:30px; line-height:1; margin-top:7px; }
    .room-admin-shell { display:grid; grid-template-columns:250px minmax(0,1fr); gap:16px; }
    .room-admin-rail { background:#082f55; border-radius:18px; padding:16px; align-self:start; color:#fff; box-shadow:0 18px 36px rgba(8,47,73,.16); }
    .room-admin-rail h5, .room-admin-rail p { color:#fff !important; }
    .room-admin-rail a { display:block; color:#dbeafe; text-decoration:none; padding:11px 0; border-top:1px solid rgba(255,255,255,.14); font-weight:600; }
    .room-board-panel { background:#fff; border:1px solid #d8e2ee; border-radius:18px; padding:14px; box-shadow:0 14px 32px rgba(15,23,42,.07); }
    .room-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(245px,1fr)); gap:14px; }
    .room-admin-card { min-height:315px; border:1px solid #d8e2ee; border-radius:18px; overflow:hidden; background:#fff; color:#475569; box-shadow:0 12px 28px rgba(15,23,42,.06); display:flex; flex-direction:column; }
    .room-photo { height:145px; background:linear-gradient(135deg,#dbeafe,#f8fafc); position:relative; overflow:hidden; cursor:pointer; }
    .room-photo img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .35s ease; }
    .room-photo:hover img { transform:scale(1.06); }
    .room-photo-fallback { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#0b5fb8; font-weight:700; font-size:44px; }
    .room-status-pill { position:absolute; left:12px; top:12px; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700; background:#fff; color:#061b33; box-shadow:0 8px 18px rgba(15,23,42,.12); }
    .room-panorama-pill { position:absolute; right:12px; bottom:12px; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700; background:#0b5fb8; color:#fff; }
    .room-card-body { padding:13px; flex:1; display:flex; flex-direction:column; }
    .room-admin-number { font-size:36px; font-weight:700; color:#061b33; line-height:1; }
    .room-table th { background:#061b33; color:#fff; font-size:12px; text-transform:uppercase; }
    .room-preview-modal .modal-dialog { max-width:980px; }
    .room-panorama-stage { min-height:430px; border-radius:20px; overflow:hidden; background:#061b33; position:relative; display:flex; align-items:center; justify-content:center; }
    .room-panorama-stage img { width:100%; height:430px; object-fit:cover; }
    .room-panorama-stage.panorama img { object-fit:cover; transform:scale(1.02); }
    .room-panorama-note { position:absolute; left:16px; bottom:16px; right:16px; background:rgba(6,27,51,.82); color:#fff; border-radius:14px; padding:12px 14px; }
    @media(max-width:991px){.room-admin-shell{grid-template-columns:1fr}.room-admin-stats{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.room-admin-stats,.room-card-grid{grid-template-columns:1fr}.room-panorama-stage,.room-panorama-stage img{height:300px;min-height:300px}}
</style>
@endsection

@section('content')
<div class="page-wrapper room-inventory">
    <div class="content container-fluid">
        <div class="room-admin-top">
            <div><h3>Room Inventory Board</h3><p class="text-muted mb-0">Add available rooms, show room photos, preview panorama views, and control room status.</p></div>
            <div class="d-flex gap-2 flex-wrap"><a href="{{ route('hotel.rooms.index', ['view' => 'grid', 'status' => $status]) }}" class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary' }}">Grid</a><a href="{{ route('hotel.rooms.index', ['view' => 'table', 'status' => $status]) }}" class="btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary' }}">Table</a><a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary">Add Room</a></div>
        </div>

        <div class="room-admin-stats">
            <div class="room-admin-stat"><small>Available</small><strong class="text-success">{{ $summary['available'] }}</strong></div>
            <div class="room-admin-stat"><small>Occupied</small><strong>{{ $summary['occupied'] }}</strong></div>
            <div class="room-admin-stat"><small>Dirty</small><strong class="text-warning">{{ $summary['dirty'] }}</strong></div>
            <div class="room-admin-stat"><small>Maintenance</small><strong class="text-danger">{{ $summary['maintenance'] }}</strong></div>
        </div>

        @include('hotel.partials.operations-action-deck', [
            'context' => 'rooms',
            'title' => 'Room Operations',
            'subtitle' => 'Add rooms and photos, inspect live status, reserve available rooms and hand rooms to housekeeping.'
        ])

        <div class="room-admin-shell">
            <aside class="room-admin-rail">
                <h5>Room Inventory</h5>
                <p>Rooms are added here with photos, panorama previews, status and housekeeping condition.</p>
                <a href="{{ route('hotel.rooms.create') }}">Add Available Room</a>
            </aside>

            <main class="room-board-panel">
                @if($rooms->count() === 0)
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-7"><div class="alert alert-info mb-0"><strong>No rooms configured yet.</strong><br>Add rooms from here, then upload room photos/panoramas so clients can preview before booking.</div></div>
                        <div class="col-lg-5 d-grid gap-2"><a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary">Add First Room</a></div>
                    </div>
                @elseif($viewMode === 'grid')
                    <div class="room-card-grid">
                        @foreach($rooms as $room)
                            @php
                                $activeStay = $activeStays->get((int) $room->id);
                                $nextReservation = $nextReservations->get((int) $room->id);
                                $tileState = $room->housekeeping_status === 'dirty' ? 'dirty' : (string) $room->operational_status;
                                $coverPath = $room->coverImage?->path ?: $room->room_image;
                                $roomImage = $coverPath ? asset('storage/'.$coverPath) : null;
                                $panoramaImage = $room->panorama_image ? asset('storage/'.$room->panorama_image) : $roomImage;
                            @endphp
                            <article class="room-admin-card">
                                <button type="button" class="room-photo border-0 p-0 w-100" data-bs-toggle="modal" data-bs-target="#roomPreview{{ $room->id }}">
                                    @if($roomImage)<img src="{{ $roomImage }}" alt="Room {{ $room->room_number }}">@else<div class="room-photo-fallback"><i class="fas fa-bed"></i></div>@endif
                                    <span class="room-status-pill">{{ ucfirst(str_replace('_',' ', $tileState)) }}</span>
                                    <span class="room-panorama-pill">Preview room</span>
                                </button>
                                <div class="room-card-body">
                                    <div class="d-flex justify-content-between gap-2"><div><div class="room-admin-number">{{ $room->room_number }}</div><strong>{{ $room->type?->name ?? 'No Type' }}</strong></div><span class="badge bg-light text-dark align-self-start">{{ ucfirst((string) $room->housekeeping_status) }}</span></div>
                                    <div class="small text-muted mt-2">Floor {{ $room->floor ?: 'N/A' }} - {{ $room->wing ?: 'Main Wing' }}</div>
                                    <div class="small mt-2">Guest: {{ $activeStay?->customer?->customer_name ?? $activeStay?->customer?->name ?? 'Vacant' }}</div>
                                    <div class="small">Next: {{ $nextReservation?->customer?->customer_name ?? $nextReservation?->customer?->name ?? 'None' }}</div>
                                    <div class="d-flex gap-1 flex-wrap mt-auto pt-3"><a href="{{ route('hotel.rooms.show', $room) }}" class="btn btn-sm btn-primary">Show</a><a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary">Edit</a><form method="POST" action="{{ route('hotel.rooms.destroy', $room) }}" onsubmit="return confirm('Deactivate this room?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Deactivate</button></form></div>
                                </div>
                            </article>
                            <div class="modal fade room-preview-modal hotel-preview-modal" id="roomPreview{{ $room->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header hotel-preview-header">
                                            <div>
                                                <small class="hotel-preview-eyebrow">Guest room preview</small>
                                                <h5 class="modal-title">Room {{ $room->room_number }} - {{ $room->type?->name ?? 'Hotel Room' }}</h5>
                                                <span>{{ $room->wing ?: 'Main Wing' }} - Floor {{ $room->floor ?: 'N/A' }} - {{ ucfirst(str_replace('_',' ', (string) $tileState)) }}</span>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center">
                                                @if($panoramaImage)
                                                    <a href="{{ $panoramaImage }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">Open full image</a>
                                                @endif
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="hotel-preview-viewer {{ $panoramaImage ? 'has-image' : 'is-empty' }}" @if($panoramaImage) style="--hotel-preview-image:url('{{ $panoramaImage }}')" @endif>
                                                @if($panoramaImage)
                                                    <div class="hotel-preview-media">
                                                        <img src="{{ $panoramaImage }}" alt="Room {{ $room->room_number }} preview">
                                                    </div>
                                                @else
                                                    <div class="sa-room-preview-empty">
                                                        <i class="fas fa-bed fa-3x mb-3"></i>
                                                        <h4>No room image uploaded yet</h4>
                                                        <p>Upload a room photo or panorama so clients can inspect this room before walking in.</p>
                                                    </div>
                                                @endif
                                                <div class="hotel-preview-controls">
                                                    <div>
                                                        <strong>{{ $room->type?->name ?? 'Room' }} - {{ number_format((float)($room->base_rate_override ?: ($room->type?->base_rate ?? 0)), 2) }}</strong>
                                                        <span>{{ $room->notes ?: 'Wide room preview for customer-facing inspection.' }}</span>
                                                    </div>
                                                    <div class="hotel-preview-status">
                                                        <span>{{ $room->panorama_image ? 'Panorama' : 'Photo' }}</span>
                                                        <i class="fas fa-circle-play"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="table-responsive"><table class="table table-sm room-table align-middle mb-0"><thead><tr><th>Room #</th><th>Photo</th><th>Type</th><th>Floor</th><th>Status</th><th>Housekeeping</th><th>Current Guest</th><th>Next Reservation</th><th>Actions</th></tr></thead><tbody>
                    @foreach($rooms as $room)
                        <tr><td><strong>{{ $room->room_number }}</strong></td><td>@if($room->room_image)<img src="{{ asset('storage/'.$room->room_image) }}" alt="" style="width:54px;height:38px;object-fit:cover;border-radius:8px">@else-@endif</td><td>{{ $room->type?->name }}</td><td>{{ $room->floor }}</td><td>{{ ucfirst((string) $room->operational_status) }}</td><td>{{ ucfirst((string) $room->housekeeping_status) }}</td><td>{{ $activeStays->get((int) $room->id)?->customer?->customer_name ?? $activeStays->get((int) $room->id)?->customer?->name ?? 'Vacant' }}</td><td>{{ $nextReservations->get((int) $room->id)?->customer?->customer_name ?? $nextReservations->get((int) $room->id)?->customer?->name ?? 'None' }}</td><td><a href="{{ route('hotel.rooms.show', $room) }}" class="btn btn-sm btn-primary">Show</a> <a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-info">Edit</a></td></tr>
                    @endforeach
                    </tbody></table></div>
                @endif
                <div class="mt-3">{{ $rooms->links() }}</div>
            </main>
        </div>
    </div>
</div>
@endsection
