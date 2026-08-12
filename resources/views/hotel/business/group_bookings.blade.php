@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $totalGuests = collect($groups->items())->sum(fn($group) => (int) ($group->adults ?? 0) + (int) ($group->children ?? 0));
    $visibleValue = collect($groups->items())->sum(fn($group) => (float) ($group->total ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-user-check"></i> Group bookings</span>
                <h2>Rooming coordination for group-led stays.</h2>
                <p>See high-occupancy reservations, guest counts, financial value, and status without mixing them into individual bookings.</p>
                <div class="hotel-pms-actionbar">
                    <a href="{{ route('hotel.reservations.create') }}" class="btn btn-light">New Group Reservation</a>
                    <a href="{{ route('hotel.availability.index') }}" class="btn btn-outline-light">Check Availability</a>
                </div>
            </div>
            <div class="hotel-pms-kpis">
                <div class="hotel-pms-kpi"><small>Group Reservations</small><strong>{{ $groups->total() }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Guests</small><strong>{{ $totalGuests }}</strong></div>
                <div class="hotel-pms-kpi"><small>Visible Value</small><strong>{{ number_format($visibleValue, 2) }}</strong></div>
            </div>
            <div class="hotel-pms-card table-responsive">
                <h4 class="hotel-pms-card-title">Group Booking Queue</h4>
                <table class="table hotel-pms-table align-middle mb-0">
                    <thead><tr><th>Reservation</th><th>Lead Guest</th><th>Adults</th><th>Children</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td><strong>{{ $group->reservation_number }}</strong></td>
                            <td>{{ $group->customer?->customer_name ?? $group->customer?->name ?? 'N/A' }}</td>
                            <td>{{ $group->adults }}</td>
                            <td>{{ $group->children }}</td>
                            <td>{{ number_format((float)$group->total,2) }}</td>
                            <td><span class="hotel-pms-pill">{{ ucfirst(str_replace('_',' ',(string)$group->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="hotel-pms-muted">No group booking data found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $groups->links() }}</div>
        </div>
    </div>
</div>
@endsection
