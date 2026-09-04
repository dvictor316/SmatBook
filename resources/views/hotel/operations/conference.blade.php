@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $bookingRows = $bookings->getCollection();
    $confirmed = $bookingRows->where('status', 'confirmed')->count();
    $expectedValue = $bookingRows->sum(fn($event) => (float) ($event->total ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-directory-page">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-users"></i> Events Desk</span>
                    <h2>Conference and event schedule</h2>
                    <p>Monitor event-linked reservations, organiser details, event value, and post banquet or ticket charges into guest folios.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-primary">Room Calendar</a>
                    <a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary">New Booking</a>
                </div>
            </div>

            <div class="hotel-op-kpis">
                <div class="hotel-op-kpi"><span>Events</span><strong>{{ $bookings->total() }}</strong></div>
                <div class="hotel-op-kpi"><span>Confirmed</span><strong>{{ $confirmed }}</strong></div>
                <div class="hotel-op-kpi"><span>Visible Value</span><strong>{{ number_format($expectedValue, 2) }}</strong></div>
                <div class="hotel-op-kpi"><span>Open Folios</span><strong>{{ $activeFolios->count() }}</strong></div>
            </div>

            <div class="hotel-op-board">
                <section>
                    <div class="hotel-op-cards mb-3">
                        @forelse($bookings as $event)
                            <article class="hotel-op-card">
                                <span class="hotel-status-chip gold">{{ ucfirst(str_replace('_', ' ', (string) $event->status)) }}</span>
                                <h5 class="mt-2">{{ $event->reservation_number }}</h5>
                                <p>{{ $event->customer?->customer_name ?? $event->customer?->name ?? 'N/A' }} at {{ $event->property?->name ?? 'N/A' }}</p>
                                <div class="hotel-op-actions">
                                    <span class="hotel-status-chip">{{ optional($event->arrival_date)->format('d M') }} - {{ optional($event->departure_date)->format('d M Y') }}</span>
                                    <strong>{{ number_format((float) ($event->total ?? 0), 2) }}</strong>
                                </div>
                            </article>
                        @empty
                            <div class="hotel-op-alert">No event-linked reservations found.</div>
                        @endforelse
                    </div>
                    <div class="mt-3">{{ $bookings->links() }}</div>
                </section>
                @include('hotel.partials.service-charge-form', ['center' => 'conference', 'title' => 'Conference / Event Sale', 'placeholder' => 'Hall rental, dinner ticket, event package'])
            </div>
        </div>
    </div>
</div>
@endsection
