@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $isPaginator = $groups instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $groupRows = $isPaginator ? collect($groups->items()) : collect($groups);
    $visibleGuests = $groupRows->sum(fn ($group) => (int) ($group->adults ?? 0) + (int) ($group->children ?? 0));
    $visibleValue = $groupRows->sum(fn ($group) => (float) ($group->total ?? 0));
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-directory-page">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-user-check"></i> Group Sales</span>
                    <h2>Group booking rooming list</h2>
                    <p>Manage group arrivals with room assignment, availability checks, booking values, and print-ready lists.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('hotel.availability.index') }}" class="btn btn-primary"><i class="fas fa-search me-1"></i> Check Availability</a>
                    <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-primary"><i class="fas fa-calendar-alt me-1"></i> Room Calendar</a>
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-1"></i> Print List</button>
                </div>
            </div>

            <div class="hotel-ledger-strip">
                <span>Groups: {{ $isPaginator ? $groups->total() : $groupRows->count() }}</span>
                <span>Visible guests: {{ $visibleGuests }}</span>
                <span>Visible value: {{ number_format($visibleValue, 2) }}</span>
            </div>

            <div class="hotel-directory-grid">
                @forelse($groups as $group)
                    <div class="hotel-directory-card">
                        <div class="d-flex justify-content-between gap-2">
                            <h5 class="mb-1">{{ $group->reservation_number }}</h5>
                            <span class="hotel-status-chip">{{ ucfirst(str_replace('_', ' ', (string) $group->status)) }}</span>
                        </div>
                        <div class="text-muted small">{{ $group->customer?->customer_name ?? $group->customer?->name ?? 'N/A' }}</div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Adults: <strong>{{ $group->adults }}</strong></span>
                            <span>Children: <strong>{{ $group->children }}</strong></span>
                        </div>
                        <div class="small text-muted mt-2">
                            {{ optional($group->arrival_date)->format('d M Y') }} - {{ optional($group->departure_date)->format('d M Y') }}
                        </div>
                        <div class="mt-3">Total: <strong>{{ number_format((float) $group->total, 2) }}</strong></div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ route('hotel.reservations.show', $group) }}" class="btn btn-sm btn-primary"><i class="fas fa-folder-open me-1"></i> Open</a>
                            <a href="{{ route('hotel.availability.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-bed me-1"></i> Rooms</a>
                        </div>
                    </div>
                @empty
                    <div class="hotel-type-panel">
                        <div class="hotel-type-panel-body text-muted">No group booking data found.</div>
                    </div>
                @endforelse
            </div>

            @if($isPaginator)
                <div class="mt-3">{{ $groups->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
