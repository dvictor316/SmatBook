@extends('layout.mainlayout')

@section('style')
<style>
    .checkin-desk { background:#f7f8fb; color:#172033; }
    .journey-top { background:linear-gradient(135deg,#0b5fb8,#0f766e); color:#fff; border-radius:14px; padding:18px; margin-bottom:14px; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .journey-top h3 { color:#fff; margin:0; }
    .journey-steps { display:flex; gap:8px; flex-wrap:wrap; }
    .journey-steps span { background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.28); padding:8px 11px; border-radius:999px; font-weight:600; }
    .ci-shell { display:grid; grid-template-columns:minmax(0,1.4fr) 360px; gap:14px; }
    .ci-panel { background:#fff; border:1px solid #dce4ef; border-radius:12px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .ci-filter { padding:14px; margin-bottom:14px; }
    .arrival-card { border-bottom:1px solid #edf1f6; padding:15px; }
    .arrival-card:last-child { border-bottom:0; }
    .arrival-head { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap; }
    .arrival-name { font-size:17px; font-weight:700; }
    .arrival-details { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:9px; margin:12px 0; }
    .arrival-details div { background:#f8fafc; border:1px solid #e5eaf2; border-radius:8px; padding:9px; font-size:13px; }
    .ci-side { padding:16px; }
    .ci-rule { border-left:4px solid #0b5fb8; background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:10px; }
    @media(max-width:991px){.ci-shell{grid-template-columns:1fr}.arrival-details{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="page-wrapper checkin-desk">
    <div class="content container-fluid">
        <section class="journey-top">
            <div><h3>Check-In Desk</h3><p class="mb-0">Fast arrival workflow with room readiness, deposits, and guest requests.</p></div>
            <div class="journey-steps"><span>1 Guest</span><span>2 Room</span><span>3 Payment</span><span>4 Keys</span></div>
        </section>

        @include('hotel.partials.operations-action-deck', [
            'context' => 'check-in',
            'title' => 'Arrival Desk Actions',
            'subtitle' => 'Assign rooms, verify readiness, open folios and hand guests into the hotel service flow.'
        ])

        <form method="GET" class="ci-panel ci-filter row g-2 align-items-end">
            <div class="col-lg-5 col-md-6"><label class="form-label">Search</label><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Guest or reservation number"></div>
            <div class="col-lg-3 col-md-6"><label class="form-label">Arrival Date</label><input type="date" name="arrival" value="{{ request('arrival', now()->toDateString()) }}" class="form-control"></div>
            <div class="col-auto"><button class="btn btn-primary">Filter Queue</button></div>
            <div class="col-auto"><a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-secondary">Front Desk</a></div>
        </form>

        <div class="ci-shell">
            <main class="ci-panel">
                <div class="p-3 border-bottom"><h5 class="mb-0">Arrival Queue</h5><small class="text-muted">{{ $reservations->count() }} reservation(s) loaded</small></div>
                @forelse($reservations as $r)
                    @php $ready = $r->room_id && (!$r->room || $r->room->housekeeping_status !== 'dirty'); @endphp
                    <div class="arrival-card">
                        <div class="arrival-head"><div><div class="arrival-name">{{ $r->customer?->customer_name ?? $r->customer?->name ?? 'N/A' }}</div><div class="small text-muted">Reservation {{ $r->reservation_number }}</div></div><span class="badge {{ !$r->room_id ? 'bg-warning text-dark' : ($ready ? 'bg-success' : 'bg-danger') }}">{{ !$r->room_id ? 'Assign Room' : ($ready ? 'Ready' : 'Room Dirty') }}</span></div>
                        <div class="arrival-details"><div><strong>Stay</strong><br>{{ optional($r->arrival_date)->format('d M Y') }} to {{ optional($r->departure_date)->format('d M Y') }}</div><div><strong>Room</strong><br>{{ $r->room?->room_number ?? 'Unassigned' }} · {{ $r->roomType?->name ?? 'N/A' }}</div><div><strong>Deposit</strong><br>{{ number_format((float) $r->deposit_received, 2) }} / {{ number_format((float) ($r->deposit_required ?? 0), 2) }}</div></div>
                        <div class="d-flex gap-2 flex-wrap"><a href="{{ route('hotel.reservations.show', $r) }}" class="btn btn-sm btn-light border">Open Reservation</a><a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-sm btn-outline-primary">Assign Room</a><form method="POST" action="{{ route('hotel.checkin', $r) }}">@csrf<button class="btn btn-sm btn-success" {{ !$r->room_id ? 'disabled' : '' }}>Complete Check-In</button></form></div>
                    </div>
                @empty
                    <div class="p-4 text-muted">No pending check-ins found.</div>
                @endforelse
                <div class="p-3">{{ $reservations->links() }}</div>
            </main>
            <aside class="ci-panel ci-side">
                <h5>Arrival Guidance</h5>
                <div class="ci-rule"><strong>Room readiness</strong><p class="small text-muted mb-0">Assigned rooms should be clean before ordinary check-in. Dirty rooms route back to housekeeping.</p></div>
                <div class="ci-rule"><strong>Payment review</strong><p class="small text-muted mb-0">Confirm deposits, outstanding balances and payment method before keys are issued.</p></div>
                <div class="ci-rule"><strong>Guest service</strong><p class="small text-muted mb-0">Review requests and notes before completion so service handover is clear.</p></div>
                <div class="d-grid gap-2 mt-3"><a href="{{ route('hotel.housekeeping.index') }}" class="btn btn-outline-primary">Housekeeping</a><a href="{{ route('hotel.folios.index') }}" class="btn btn-outline-dark">Folios</a></div>
            </aside>
        </div>
    </div>
</div>
@endsection
