@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Room Status Board</h3>
            <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Open Calendar</a>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Available</small><h5>{{ $statusTotals['available'] ?? 0 }}</h5></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Occupied</small><h5>{{ $statusTotals['occupied'] ?? 0 }}</h5></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Reserved</small><h5>{{ $statusTotals['reserved'] ?? 0 }}</h5></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Out of Service</small><h5>{{ $statusTotals['out_of_service'] ?? 0 }}</h5></div></div></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    @forelse($rooms as $room)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="border rounded p-2 h-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>{{ $room->room_number }}</strong>
                                    <span class="badge {{ ($room->operational_status ?? '') === 'available' ? 'bg-success' : (($room->operational_status ?? '') === 'occupied' ? 'bg-danger' : 'bg-secondary') }}">{{ ucfirst((string) $room->operational_status) }}</span>
                                </div>
                                <div class="text-muted small">{{ $room->type?->name ?? 'No Type' }}</div>
                                <div class="small mb-2">HK: {{ ucfirst((string) $room->housekeeping_status) }}</div>
                                <a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary w-100">Manage</a>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><div class="alert alert-info mb-0">No rooms configured.</div></div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $rooms->links() }}</div>
    </div>
</div>
@endsection
