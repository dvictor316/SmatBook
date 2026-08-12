@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3 class="mb-3">Conference & Events</h3>
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
