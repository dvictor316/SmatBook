@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Rooms</h3>
                <p class="text-muted mb-0">Visual room inventory and operational availability</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('hotel.rooms.index', ['view' => 'grid', 'status' => $status]) }}" class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary' }}">Grid</a>
                <a href="{{ route('hotel.rooms.index', ['view' => 'table', 'status' => $status]) }}" class="btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary' }}">Table</a>
                <a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary">New Room</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Available</small><h4>{{ $summary['available'] }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Occupied</small><h4>{{ $summary['occupied'] }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Dirty</small><h4>{{ $summary['dirty'] }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Maintenance</small><h4>{{ $summary['maintenance'] }}</h4></div></div></div>
        </div>

        @if($rooms->count() === 0)
            <div class="alert alert-info">No rooms have been configured yet. <a href="{{ route('hotel.rooms.create') }}">Add First Room</a></div>
        @elseif($viewMode === 'grid')
            <div class="row g-3">
                @foreach($rooms as $room)
                    @php $activeStay = $activeStays->get((int) $room->id); $nextReservation = $nextReservations->get((int) $room->id); @endphp
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card h-100"><div class="card-body">
                            <div class="d-flex justify-content-between mb-2"><h5 class="mb-0">Room {{ $room->room_number }}</h5><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_',' ',(string)$room->operational_status)) }}</span></div>
                            <div class="text-muted small mb-2">{{ $room->type?->name ?? 'No Type' }} | Floor {{ $room->floor ?: 'N/A' }}</div>
                            <div class="small mb-1">Housekeeping: {{ ucfirst((string) $room->housekeeping_status) }}</div>
                            <div class="small mb-1">Rate: {{ number_format((float)($room->base_rate_override ?: $room->type?->base_rate), 2) }}</div>
                            <div class="small mb-2">Current Guest: {{ $activeStay?->customer?->customer_name ?? $activeStay?->customer?->name ?? 'Vacant' }}</div>
                            <div class="small mb-2">Next Reservation: {{ $nextReservation?->customer?->customer_name ?? $nextReservation?->customer?->name ?? 'None' }}</div>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('hotel.rooms.destroy', $room) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Deactivate</button></form>
                            </div>
                        </div></div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card"><div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Room #</th><th>Type</th><th>Floor</th><th>Status</th><th>Housekeeping</th><th>Current Guest</th><th>Next Reservation</th><th>Actions</th></tr></thead>
                    <tbody>
                    @foreach($rooms as $room)
                        <tr>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->type?->name }}</td>
                            <td>{{ $room->floor }}</td>
                            <td>{{ ucfirst((string) $room->operational_status) }}</td>
                            <td>{{ ucfirst((string) $room->housekeeping_status) }}</td>
                            <td>{{ $activeStays->get((int) $room->id)?->customer?->customer_name ?? $activeStays->get((int) $room->id)?->customer?->name ?? 'Vacant' }}</td>
                            <td>{{ $nextReservations->get((int) $room->id)?->customer?->customer_name ?? $nextReservations->get((int) $room->id)?->customer?->name ?? 'None' }}</td>
                            <td><a href="{{ route('hotel.rooms.edit', $room) }}" class="btn btn-sm btn-info">Edit</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div></div>
        @endif
        <div class="mt-3">{{ $rooms->links() }}</div>
    </div>
</div>
@endsection
