@extends('layout.mainlayout')

@section('style')
<style>
    .pms-maintenance { background:#eef3f6; color:#172033; }
    .maint-console { display:grid; grid-template-columns:300px minmax(0,1fr); gap:16px; }
    .maint-hero { background:#111827; color:#fff; border-radius:18px; padding:18px; margin-bottom:16px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; border-left:8px solid #d4a23a; }
    .maint-hero h3 { color:#fff; margin:0; font-weight:700; }
    .maint-hero p { color:#d1d5db; margin:4px 0 0; }
    .maint-stat-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
    .maint-stat { background:#fff; border:1px solid #dce4ef; border-radius:14px; padding:14px; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .maint-stat span { display:block; color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:11px; font-weight:700; }
    .maint-stat strong { display:block; font-size:30px; line-height:1; margin-top:8px; }
    .maint-panel { background:#fff; border:1px solid #dce4ef; border-radius:16px; box-shadow:0 12px 28px rgba(15,23,42,.06); overflow:hidden; }
    .maint-panel-head { padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5edf6; display:flex; justify-content:space-between; gap:10px; align-items:center; }
    .maint-ticket-form { padding:16px; display:grid; gap:10px; }
    .maint-conflict { padding:12px; margin:0 16px 12px; border-radius:12px; border:1px solid #fecaca; background:#fff1f2; }
    .maint-board { display:grid; gap:10px; padding:16px; }
    .maint-ticket { display:grid; grid-template-columns:120px minmax(0,1fr) 120px 130px 230px; gap:12px; align-items:center; padding:13px; border:1px solid #e5edf6; border-left:6px solid #94a3b8; border-radius:14px; background:#fff; }
    .maint-ticket.high, .maint-ticket.critical { border-left-color:#dc2626; background:#fff7f7; }
    .maint-ticket.medium { border-left-color:#d4a23a; }
    .maint-room { font-size:28px; font-weight:300; color:#0b5fb8; line-height:1; }
    .maint-priority { display:inline-flex; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; background:#eef2ff; color:#1d4ed8; }
    .maint-priority.hot { background:#fee2e2; color:#991b1b; }
    @media(max-width:1199px){.maint-console{grid-template-columns:1fr}.maint-ticket{grid-template-columns:1fr}.maint-stat-row{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.maint-stat-row{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="page-wrapper pms-maintenance">
    <div class="content container-fluid">
        <section class="maint-hero">
            <div>
                <h3>Engineering & Maintenance Desk</h3>
                <p>Track room faults, technician workflow, reservation conflicts, and out-of-order risk.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-self-start">
                <a href="{{ route('hotel.frontdesk') }}" class="btn btn-light">Front Desk</a>
                <a href="{{ route('hotel.rooms.status') }}" class="btn btn-warning">Room Status</a>
            </div>
        </section>

        <div class="maint-stat-row">
            <div class="maint-stat"><span>Open</span><strong>{{ $summary['open'] }}</strong></div>
            <div class="maint-stat"><span>Urgent</span><strong class="text-danger">{{ $summary['urgent'] }}</strong></div>
            <div class="maint-stat"><span>In Progress</span><strong>{{ $summary['in_progress'] }}</strong></div>
            <div class="maint-stat"><span>Completed Today</span><strong class="text-success">{{ $summary['completed_today'] }}</strong></div>
        </div>

        @include('hotel.partials.operations-action-deck', [
            'context' => 'maintenance',
            'title' => 'Engineering Actions',
            'subtitle' => 'Coordinate blocked rooms, front-desk impact, housekeeping handoff and guest folio follow-up.'
        ])

        <div class="maint-console">
            <aside class="maint-panel">
                <div class="maint-panel-head"><strong>Open Ticket</strong><span class="badge bg-dark">Engineering</span></div>
                <form method="POST" action="{{ route('hotel.maintenance.store') }}" class="maint-ticket-form">
                    @csrf
                    <div><label class="form-label">Room</label><select name="room_id" class="form-control" required><option value="">Select room</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_number }} - {{ $room->type?->name ?? 'No Type' }}</option>@endforeach</select></div>
                    <div><label class="form-label">Problem</label><input type="text" name="title" class="form-control" placeholder="AC not cooling, lock issue, water leak..." required></div>
                    <div><label class="form-label">Priority</label><select name="severity" class="form-control"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
                    <div><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" placeholder="Technician instruction or guest impact"></textarea></div>
                    <button class="btn btn-primary">Create Maintenance Ticket</button>
                </form>
                <div class="maint-panel-head"><strong>Reservation Conflicts</strong><span class="badge bg-danger">Risk</span></div>
                @forelse($reservationConflicts as $reservation)
                    <div class="maint-conflict"><strong>{{ $reservation->reservation_number }}</strong><div class="small">{{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'Guest' }}</div><div class="small text-danger">Room {{ $reservation->room?->room_number ?? 'N/A' }} is unavailable</div></div>
                @empty
                    <div class="p-3 text-muted">No future reservation conflicts detected.</div>
                @endforelse
            </aside>

            <main class="maint-panel">
                <div class="maint-panel-head"><div><strong>Maintenance Tickets</strong><div class="small text-muted">Engineering queue with live status update.</div></div><span class="badge bg-light text-dark">{{ $tickets->total() }} tickets</span></div>
                <div class="maint-board">
                    @forelse($tickets as $ticket)
                        <div class="maint-ticket {{ $ticket->severity }}">
                            <div><div class="maint-room">{{ $ticket->room?->room_number ?? 'N/A' }}</div><small class="text-muted">{{ $ticket->ticket_no }}</small></div>
                            <div><strong>{{ $ticket->title }}</strong><div class="small text-muted">{{ $ticket->description ?: 'No description supplied' }}</div></div>
                            <div><span class="maint-priority {{ in_array($ticket->severity, ['high','critical'], true) ? 'hot' : '' }}">{{ ucfirst((string) $ticket->severity) }}</span></div>
                            <div><strong>{{ ucfirst(str_replace('_', ' ', (string) $ticket->status)) }}</strong><div class="small text-muted">{{ optional($ticket->created_at)->format('d M H:i') }}</div></div>
                            <form method="POST" action="{{ route('hotel.maintenance.status', $ticket) }}" class="d-flex gap-1">
                                @csrf
                                <select name="status" class="form-control form-control-sm"><option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option><option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option><option value="resolved">Resolved</option><option value="cancelled">Cancelled</option></select>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </div>
                    @empty
                        <div class="p-4 text-muted">No maintenance tickets have been logged.</div>
                    @endforelse
                </div>
                <div class="p-3">{{ $tickets->links() }}</div>
            </main>
        </div>
    </div>
</div>
@endsection
