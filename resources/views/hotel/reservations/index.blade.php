@extends('layout.mainlayout')

@section('style')
<style>
    .reservation-workspace { background:#f5f7fb; color:#172033; }
    .res-top { background:#fff; border:1px solid #dce4ef; border-radius:12px; padding:16px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .res-pipeline { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:8px; margin:14px 0; }
    .res-step { border-radius:10px; padding:10px; background:#fff; border:1px solid #dce4ef; text-align:center; font-weight:700; color:#172033; }
    .res-step span { display:block; font-size:12px; color:#64748b; font-weight:700; }
    .res-shell { display:grid; grid-template-columns:260px minmax(0,1fr); gap:14px; }
    .res-filter, .res-list { background:#fff; border:1px solid #dce4ef; border-radius:12px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .res-filter { padding:16px; align-self:start; }
    .res-card { display:grid; grid-template-columns:150px minmax(0,1fr) 160px 150px 145px; gap:12px; align-items:center; padding:14px; border-bottom:1px solid #edf1f6; }
    .res-card:last-child { border-bottom:0; }
    .res-number { color:#0b5fb8; font-weight:700; text-decoration:none; }
    .res-guest { font-size:16px; font-weight:700; }
    .res-date { background:#f8fafc; border:1px solid #e5eaf2; border-radius:8px; padding:8px; font-size:12px; }
    .res-money { text-align:right; }
    .res-actions { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
    @media(max-width:1199px){.res-pipeline{grid-template-columns:repeat(3,1fr)}.res-shell{grid-template-columns:1fr}.res-card{grid-template-columns:1fr}}
    @media(max-width:575px){.res-pipeline{grid-template-columns:1fr}.res-money{text-align:left}.res-actions{justify-content:flex-start}}
</style>
@endsection

@section('content')
@php
    $statusCounts = collect($reservations->items())->groupBy('status')->map->count();
@endphp
<div class="page-wrapper reservation-workspace">
    <div class="content container-fluid">
        <div class="res-top mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div><h3 class="mb-1">Reservations Control</h3><p class="text-muted mb-0">Booking pipeline, room assignment, deposits, and arrival readiness.</p></div>
                <div class="d-flex flex-wrap gap-2"><a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary">New Reservation</a><a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-primary">Calendar</a><a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-dark">Front Desk</a></div>
            </div>
            <div class="res-pipeline">
                @foreach(['inquiry','reserved','confirmed','checked_in','completed','cancelled','no_show'] as $pipeStatus)
                    <div class="res-step"><span>{{ ucfirst(str_replace('_',' ', $pipeStatus)) }}</span>{{ $statusCounts->get($pipeStatus, 0) }}</div>
                @endforeach
            </div>
        </div>

        <div class="res-shell">
            <aside class="res-filter">
                <h5>Filters</h5>
                <form method="GET" class="d-grid gap-2">
                    <label class="form-label mb-0 small">Property</label>
                    <select name="property_id" class="form-control">
                        <option value="">All Properties</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ (int) request('property_id', $propertyId) === (int) $property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                        @endforeach
                    </select>
                    <label class="form-label mb-0 small">From</label><input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    <label class="form-label mb-0 small">To</label><input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    <label class="form-label mb-0 small">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach(['inquiry','reserved','confirmed','checked_in','completed','cancelled','no_show'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $status)) }}</option>
                        @endforeach
                    </select>
                    <label class="form-label mb-0 small">Search</label><input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Reservation, guest, source">
                    <button class="btn btn-primary mt-2">Apply Filters</button>
                </form>
            </aside>

            <main class="res-list">
                @forelse($reservations as $r)
                    @php
                        $status = (string) $r->status;
                        $statusClass = match($status) {'confirmed' => 'bg-primary','checked_in' => 'bg-success','cancelled' => 'bg-danger','no_show' => 'bg-dark','completed' => 'bg-secondary', default => 'bg-info'};
                    @endphp
                    <div class="res-card">
                        <div><a class="res-number" href="{{ route('hotel.reservations.show', $r) }}">{{ $r->reservation_number }}</a><div class="small text-muted">{{ ucfirst((string) ($r->source ?: 'direct')) }}</div></div>
                        <div><div class="res-guest">{{ $r->customer?->customer_name ?? $r->customer?->name ?? 'N/A' }}</div><div class="small text-muted">{{ $r->roomType?->name ?? 'N/A' }} · Room {{ $r->room?->room_number ?? 'Unassigned' }}</div></div>
                        <div class="res-date"><strong>{{ optional($r->arrival_date)->format('d M Y') }}</strong><br>to {{ optional($r->departure_date)->format('d M Y') }} · {{ $r->nights }} nights</div>
                        <div class="res-money"><strong>{{ number_format((float) $r->total, 2) }}</strong><div class="small text-muted">Deposit {{ number_format((float) $r->deposit_received, 2) }} · Bal {{ number_format((float) $r->balance, 2) }}</div></div>
                        <div><span class="badge {{ $statusClass }} mb-2">{{ ucfirst(str_replace('_',' ', $status)) }}</span><div class="res-actions"><a href="{{ route('hotel.reservations.show', $r) }}" class="btn btn-sm btn-light border">Open</a>@if(in_array($status, ['reserved','confirmed']))<form method="POST" action="{{ route('hotel.checkin', $r) }}">@csrf<button class="btn btn-sm btn-success" {{ !$r->room_id ? 'disabled' : '' }}>Check In</button></form>@endif</div></div>
                    </div>
                @empty
                    <div class="p-4 text-muted">No reservations found for the selected period.</div>
                @endforelse
                <div class="p-3">{{ $reservations->links() }}</div>
            </main>
        </div>
    </div>
</div>
@endsection
