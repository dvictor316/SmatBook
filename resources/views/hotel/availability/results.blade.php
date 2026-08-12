@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Availability Results</h3>
            <a href="{{ route('hotel.availability.index') }}" class="btn btn-outline-secondary">New Search</a>
        </div>

        <div class="row g-3">
            @forelse($rooms as $room)
                @php
                    $arrival = \Illuminate\Support\Carbon::parse(request('arrival_date'));
                    $departure = \Illuminate\Support\Carbon::parse(request('departure_date'));
                    $nights = max(1, $arrival->diffInDays($departure));
                    $rate = (float) ($room->base_rate_override ?: ($room->type?->base_rate ?? 0));
                    $estimate = $rate * $nights;
                @endphp
                <div class="col-xl-4 col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0">{{ $room->type?->name ?? 'Room Type' }}</h5>
                                <span class="badge bg-success">Available</span>
                            </div>
                            <p class="text-muted mb-2">Room {{ $room->room_number }}</p>
                            <div class="d-flex justify-content-between mb-1"><span>Capacity</span><strong>{{ $room->type?->max_occupancy ?? '-' }}</strong></div>
                            <div class="d-flex justify-content-between mb-1"><span>Rate</span><strong>{{ number_format($rate, 2) }}</strong></div>
                            <div class="d-flex justify-content-between mb-1"><span>Nights</span><strong>{{ $nights }}</strong></div>
                            <div class="d-flex justify-content-between mb-3"><span>Estimated Total</span><strong>{{ number_format($estimate, 2) }}</strong></div>
                            <div class="d-grid gap-2">
                                <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary btn-sm">Book Now</a>
                                <a href="{{ route('hotel.walkin.create') }}" class="btn btn-outline-success btn-sm">Walk-In</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">No rooms available for the selected dates and occupancy.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
