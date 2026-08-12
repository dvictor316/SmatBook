@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Check-In</h3>
                <p class="text-muted mb-0">Guest arrival workflow and room readiness queue</p>
            </div>
            <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-secondary">Front Desk</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Guest, reservation number">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Arrival Date</label>
                        <input type="date" name="arrival" value="{{ request('arrival', now()->toDateString()) }}" class="form-control">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary">Filter Queue</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Arrival Queue</h5></div>
                    <div class="card-body">
                        @forelse($reservations as $r)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <div>
                                        <div class="fw-semibold">{{ $r->customer?->customer_name ?? $r->customer?->name ?? 'N/A' }}</div>
                                        <div class="small text-muted">Reservation {{ $r->reservation_number }}</div>
                                    </div>
                                    <span class="badge {{ !$r->room_id ? 'bg-warning text-dark' : (($r->room && $r->room->housekeeping_status === 'dirty') ? 'bg-danger' : 'bg-success') }}">
                                        {{ !$r->room_id ? 'Room Assignment Required' : (($r->room && $r->room->housekeeping_status === 'dirty') ? 'Room Not Ready' : 'Ready') }}
                                    </span>
                                </div>
                                <div class="row g-2 small mb-2">
                                    <div class="col-md-4"><strong>Arrival:</strong> {{ optional($r->arrival_date)->format('d M Y') }}</div>
                                    <div class="col-md-4"><strong>Departure:</strong> {{ optional($r->departure_date)->format('d M Y') }}</div>
                                    <div class="col-md-4"><strong>Deposit:</strong> {{ number_format((float) $r->deposit_received, 2) }}</div>
                                    <div class="col-md-4"><strong>Room:</strong> {{ $r->room?->room_number ?? 'Unassigned' }}</div>
                                    <div class="col-md-4"><strong>Room Type:</strong> {{ $r->roomType?->name ?? 'N/A' }}</div>
                                    <div class="col-md-4"><strong>Requests:</strong> {{ $r->special_requests ?: 'None' }}</div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('hotel.reservations.show', $r) }}" class="btn btn-sm btn-light">Open Reservation</a>
                                    <form method="POST" action="{{ route('hotel.checkin', $r) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success" {{ !$r->room_id ? 'disabled' : '' }}>Complete Check-In</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info mb-0">No pending check-ins found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Arrival Guidance</h5></div>
                    <div class="card-body">
                        <div class="border rounded p-3 mb-3">
                            <strong>Checklist</strong>
                            <ul class="mb-0 mt-2">
                                <li>Confirm guest identity and reservation.</li>
                                <li>Verify room assignment and readiness.</li>
                                <li>Confirm deposit and outstanding balance.</li>
                                <li>Review special requests before issuing keys.</li>
                            </ul>
                        </div>
                        <div class="border rounded p-3">
                            <strong>Room readiness rules</strong>
                            <p class="small text-muted mb-0">Dirty rooms require housekeeping completion before ordinary check-in. Unassigned reservations should be completed from Calendar or Reservation detail before final check-in.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $reservations->links() }}</div>
    </div>
</div>
@endsection
