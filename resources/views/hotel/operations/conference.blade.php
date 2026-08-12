@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $visibleValue = collect($bookings->items())->sum(fn($booking) => (float) ($booking->total ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-users"></i> Conference & events</span>
                <h2>Event-linked reservations and organizer stays.</h2>
                <p>Keep conference, training, and event bookings visible separately from normal room reservations.</p>
                <div class="hotel-pms-actionbar">
                    <a href="{{ route('hotel.reservations.create', ['source' => 'conference']) }}" class="btn btn-light">New Event Reservation</a>
                    <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-light">Room Calendar</a>
                </div>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Event Bookings</small><strong>{{ $bookings->total() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Value</small><strong>{{ number_format($visibleValue, 2) }}</strong></div>
                <div class="hotel-pms-kpi"><small>Current Page</small><strong>{{ $bookings->count() }}</strong></div>
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Event Booking List</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Reservation</th><th>Guest/Organizer</th><th>Property</th><th>Arrival</th><th>Departure</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($bookings as $event)
                        <tr>
                            <td><strong>{{ $event->reservation_number }}</strong></td>
                            <td>{{ $event->customer?->customer_name ?? $event->customer?->name ?? 'N/A' }}</td>
                            <td>{{ $event->property?->name ?? 'N/A' }}</td>
                            <td>{{ optional($event->arrival_date)->format('d M Y') }}</td>
                            <td>{{ optional($event->departure_date)->format('d M Y') }}</td>
                            <td>{{ number_format((float) ($event->total ?? 0), 2) }}</td>
                            <td><span class="hotel-pms-pill gold">{{ ucfirst(str_replace('_',' ', (string)$event->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="hotel-pms-muted">No event-linked reservations found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $bookings->links() }}</div>
        </div>
    </div>
</div>
@endsection
