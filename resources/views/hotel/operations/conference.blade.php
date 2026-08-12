@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Conference & Events</h3>
                <p class="text-muted mb-0">Event-linked bookings and organizer activity</p>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Bookings</small><h4>{{ $bookings->total() }}</h4></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Current Page</small><h4>{{ $bookings->count() }}</h4></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Use Case</small><h4>Group/Event Stays</h4></div></div></div>
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Reservation</th><th>Guest/Organizer</th><th>Property</th><th>Arrival</th><th>Departure</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($bookings as $event)
                        <tr>
                            <td>{{ $event->reservation_number }}</td>
                            <td>{{ $event->customer?->customer_name ?? $event->customer?->name ?? 'N/A' }}</td>
                            <td>{{ $event->property?->name ?? 'N/A' }}</td>
                            <td>{{ optional($event->arrival_date)->format('d M Y') }}</td>
                            <td>{{ optional($event->departure_date)->format('d M Y') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ', (string)$event->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No event-linked reservations found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $bookings->links() }}</div>
    </div>
</div>
@endsection
