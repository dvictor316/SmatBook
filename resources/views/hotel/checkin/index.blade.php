@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="mb-0">Check-In Workspace</h3>
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

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Pending Check-In Queue</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Reservation</th>
                        <th>Guest</th>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th>Room</th>
                        <th>Deposit</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($reservations as $r)
                        <tr>
                            <td>{{ $r->reservation_number }}</td>
                            <td>{{ $r->customer?->customer_name ?? $r->customer?->name ?? 'N/A' }}</td>
                            <td>{{ optional($r->arrival_date)->format('d M Y') }}</td>
                            <td>{{ optional($r->departure_date)->format('d M Y') }}</td>
                            <td>{{ $r->room?->room_number ?? 'Unassigned' }}</td>
                            <td>{{ number_format((float) $r->deposit_received, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('hotel.checkin', $r) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Check In</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No pending check-ins found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $reservations->links() }}</div>
    </div>
</div>
@endsection
