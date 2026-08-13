@extends('layout.mainlayout')

@section('style')
<style>
    .availability-results { background:#f3f7fb; color:#172033; }
    .sell-hero { background:linear-gradient(135deg,#06264b,#0b5fb8); border-radius:18px; color:#fff; padding:20px; margin-bottom:16px; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .sell-hero h3 { color:#fff; margin:0; font-weight:700; }
    .sell-hero p { color:#dbeafe; margin:4px 0 0; }
    .sell-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
    .sell-room { background:#fff; border:1px solid #d9e4ef; border-radius:16px; overflow:hidden; box-shadow:0 12px 28px rgba(15,23,42,.06); }
    .sell-room-head { min-height:120px; padding:17px; background:linear-gradient(135deg,#e0f2fe,#f0fdf4); position:relative; }
    .sell-room-head:after { content:'AVAILABLE'; position:absolute; right:14px; top:14px; background:#16a34a; color:#fff; border-radius:999px; padding:6px 9px; font-size:11px; font-weight:700; }
    .sell-room-no { font-size:42px; color:#0b5fb8; line-height:1; font-weight:300; }
    .sell-room-body { padding:16px; }
    .sell-row { display:flex; justify-content:space-between; border-bottom:1px solid #edf2f7; padding:8px 0; }
    .sell-row:last-child { border-bottom:0; }
    .sell-total { background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:12px; margin:12px 0; display:flex; justify-content:space-between; }
</style>
@endsection

@section('content')
<div class="page-wrapper availability-results">
    <div class="content container-fluid">
        <section class="sell-hero">
            <div><h3>Available Rooms</h3><p>Choose a sellable room for the selected stay period and continue to reservation or walk-in.</p></div>
            <div class="d-flex gap-2 flex-wrap align-self-start"><a href="{{ route('hotel.availability.index') }}" class="btn btn-light">New Search</a><a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-warning">Calendar</a></div>
        </section>

        <div class="sell-grid">
            @forelse($rooms as $room)
                @php
                    $arrival = \Illuminate\Support\Carbon::parse(request('arrival_date'));
                    $departure = \Illuminate\Support\Carbon::parse(request('departure_date'));
                    $nights = max(1, $arrival->diffInDays($departure));
                    $rate = (float) ($room->base_rate_override ?: ($room->type?->base_rate ?? 0));
                    $estimate = $rate * $nights;
                @endphp
                <article class="sell-room">
                    <div class="sell-room-head"><div class="sell-room-no">{{ $room->room_number }}</div><h5 class="mb-1">{{ $room->type?->name ?? 'Room Type' }}</h5><small>Floor {{ $room->floor ?: 'N/A' }} · {{ ucfirst((string)$room->housekeeping_status) }}</small></div>
                    <div class="sell-room-body">
                        <div class="sell-row"><span>Capacity</span><strong>{{ $room->type?->max_occupancy ?? '-' }}</strong></div>
                        <div class="sell-row"><span>Rate per night</span><strong>{{ number_format($rate, 2) }}</strong></div>
                        <div class="sell-row"><span>Nights</span><strong>{{ $nights }}</strong></div>
                        <div class="sell-total"><span>Estimated total</span><strong>{{ number_format($estimate, 2) }}</strong></div>
                        <div class="d-grid gap-2"><a href="{{ route('hotel.reservations.create', ['room_id' => $room->id, 'room_type_id' => $room->room_type_id, 'arrival_date' => request('arrival_date'), 'departure_date' => request('departure_date')]) }}" class="btn btn-primary">Select Room</a><a href="{{ route('hotel.walkin.create') }}" class="btn btn-outline-success">Walk-In</a></div>
                    </div>
                </article>
            @empty
                <div class="alert alert-info mb-0">No rooms available for the selected dates and occupancy.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
