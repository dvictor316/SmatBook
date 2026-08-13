@extends('layout.mainlayout')

@section('style')
<style>
    .hk-page { background:#f5f8fc; color:#0b1f36; }
    .hk-wrap { padding:18px; }
    .hk-topbar { display:flex; justify-content:space-between; gap:14px; align-items:flex-end; margin-bottom:16px; }
    .hk-topbar h2 { margin:0; font-weight:700; color:#071f3d; }
    .hk-topbar p { margin:4px 0 0; color:#667085; font-size:15px; }
    .hk-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .hk-chip-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
    .hk-chip { border-radius:999px; padding:9px 14px; font-weight:700; border:1px solid transparent; background:#fff; box-shadow:0 8px 22px rgba(15,23,42,.06); }
    .hk-chip.clean { background:#dcfce7; color:#166534; }
    .hk-chip.occupied { background:#dbeafe; color:#1d4ed8; }
    .hk-chip.dirty { background:#fee2e2; color:#b91c1c; }
    .hk-chip.maintenance { background:#fef3c7; color:#92400e; }
    .hk-chip.arriving { background:#f3e8ff; color:#7e22ce; }
    .hk-layout { display:grid; grid-template-columns:285px minmax(0,1fr); gap:16px; align-items:start; }
    .hk-panel { background:#fff; border:1px solid #dfe8f3; border-radius:18px; box-shadow:0 16px 34px rgba(15,23,42,.07); }
    .hk-filter { padding:16px; position:sticky; top:92px; }
    .hk-filter h4 { font-weight:700; color:#061b33; margin-bottom:12px; }
    .hk-filter label { color:#475569; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.08em; }
    .hk-filter .form-control, .hk-filter .form-select { border-radius:12px; border-color:#d8e2ee; min-height:44px; }
    .hk-room-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:14px; }
    .hk-room-card { min-height:205px; border-radius:18px; border:2px solid #2563eb; background:#eff6ff; padding:18px; display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 12px 28px rgba(15,23,42,.06); }
    .hk-room-card.clean { border-color:#16a34a; background:#ecfdf3; }
    .hk-room-card.dirty { border-color:#dc2626; background:#fff1f2; }
    .hk-room-card.maintenance { border-color:#d97706; background:#fffbeb; }
    .hk-room-card.inspection { border-color:#7c3aed; background:#f5f3ff; }
    .hk-room-head { display:flex; justify-content:space-between; gap:8px; align-items:flex-start; }
    .hk-room-no { font-size:38px; line-height:1; font-weight:700; color:#061b33; }
    .hk-flag { font-size:18px; color:#7c3aed; }
    .hk-tag { display:inline-flex; align-items:center; border-radius:7px; padding:5px 9px; font-size:12px; font-weight:700; margin:2px 3px 2px 0; }
    .hk-tag.type { background:#e0efff; color:#2563eb; }
    .hk-tag.clean { background:#bbf7d0; color:#166534; }
    .hk-tag.occupied { background:#bfdbfe; color:#1d4ed8; }
    .hk-tag.dirty { background:#fecaca; color:#b91c1c; }
    .hk-tag.maintenance { background:#fde68a; color:#92400e; }
    .hk-tag.arriving { background:#ede9fe; color:#6d28d9; }
    .hk-guest { color:#334155; font-weight:700; font-size:18px; margin-top:12px; }
    .hk-meta { color:#64748b; line-height:1.45; }
    .hk-card-actions { display:flex; gap:8px; margin-top:14px; }
    .hk-card-actions .btn { border-radius:11px; font-weight:700; flex:1; }
    .hk-workbench { margin-top:18px; display:grid; grid-template-columns:minmax(0,1fr) 330px; gap:16px; }
    .hk-table-wrap { overflow:auto; }
    .hk-table { min-width:850px; margin:0; }
    .hk-table th { background:#071f3d; color:#fff; border:0; text-transform:uppercase; font-size:12px; letter-spacing:.04em; padding:12px; }
    .hk-table td { padding:12px; vertical-align:middle; border-color:#e8eef6; }
    .hk-table tbody tr:nth-child(even) { background:#f8fafc; }
    .hk-side-card { padding:16px; }
    .hk-side-card h4 { font-weight:700; color:#061b33; }
    .hk-mini-task { border:1px solid #e2e8f0; border-left:5px solid #d97706; border-radius:14px; padding:12px; margin-bottom:10px; background:#fff; }
    .hk-mini-task.high { border-left-color:#dc2626; background:#fff7f7; }
    @media(max-width:1199px){.hk-layout,.hk-workbench{grid-template-columns:1fr}.hk-filter{position:static}.hk-room-grid{grid-template-columns:repeat(auto-fill,minmax(220px,1fr))}}
    @media(max-width:767px){.hk-wrap{padding:12px}.hk-topbar{display:block}.hk-actions{margin-top:12px}.hk-room-grid{grid-template-columns:1fr}.hk-room-card{min-height:185px}.hk-room-no{font-size:32px}}
</style>
@endsection

@section('content')
@php
    $rooms = collect($rooms ?? []);
    $taskRows = collect($tasks ?? [])->flatten(1);
    $arrivalByRoom = collect($arrivalsWaitingForRoom ?? [])->filter(fn($reservation) => !empty($reservation->room_id))->keyBy('room_id');
    $taskByRoom = $taskRows->filter(fn($task) => !empty($task->room_id))->keyBy('room_id');
    $roomClass = function ($room, $task = null) {
        $operational = (string) ($room->operational_status ?? 'available');
        $housekeeping = (string) ($room->housekeeping_status ?? 'clean');
        $taskStatus = (string) ($task->status ?? '');
        if (in_array($operational, ['maintenance', 'out_of_order'], true)) return 'maintenance';
        if (in_array($taskStatus, ['cleaning', 'inspection'], true)) return 'inspection';
        if ($housekeeping === 'dirty') return 'dirty';
        return 'clean';
    };
    $statusLabel = function ($room, $task = null, $arrival = null) {
        $operational = (string) ($room->operational_status ?? 'available');
        $housekeeping = (string) ($room->housekeeping_status ?? 'clean');
        $taskStatus = (string) ($task->status ?? '');
        if (in_array($operational, ['maintenance', 'out_of_order'], true)) return 'Maintenance';
        if (in_array($taskStatus, ['cleaning', 'inspection'], true)) return ucfirst($taskStatus);
        if ($housekeeping === 'dirty' && $arrival) return 'Dirty - Guest Arriving';
        if ($housekeeping === 'dirty') return 'Dirty';
        if ($operational === 'occupied') return 'Occupied';
        return 'Vacant - Clean';
    };
@endphp
<div class="page-wrapper hk-page">
    <div class="content container-fluid hk-wrap">
        <div class="hk-topbar">
            <div>
                <h2>Housekeeping Board</h2>
                <p>Real-time room readiness, dirty rooms, arrivals and cleaner workload for {{ now()->format('l d M Y') }}.</p>
            </div>
            <div class="hk-actions">
                <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-primary">Front Desk</a>
                <a href="{{ route('hotel.room-calendar') }}" class="btn btn-outline-dark">Room Calendar</a>
            </div>
        </div>

        <div class="hk-chip-row">
            <span class="hk-chip clean">Vacant Clean: {{ $summary['vacant_clean'] ?? 0 }}</span>
            <span class="hk-chip occupied">Occupied: {{ $summary['occupied'] ?? 0 }}</span>
            <span class="hk-chip dirty">Dirty: {{ $summary['dirty'] ?? 0 }}</span>
            <span class="hk-chip maintenance">Maintenance: {{ $summary['maintenance'] ?? 0 }}</span>
            <span class="hk-chip arriving">Arriving: {{ $summary['arriving'] ?? 0 }}</span>
        </div>

        <div class="hk-layout">
            <aside class="hk-panel hk-filter">
                <h4>Housekeeping</h4>
                <form method="GET" action="{{ route('hotel.housekeeping.index') }}" class="mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="">All priorities</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High priority</option>
                        <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal priority</option>
                    </select>
                </form>
                <label class="form-label">Workload</label>
                <div class="d-grid gap-2">
                    <span class="btn btn-light text-start">Assigned: {{ $summary['assigned'] ?? 0 }}</span>
                    <span class="btn btn-light text-start">Cleaning: {{ $summary['cleaning'] ?? 0 }}</span>
                    <span class="btn btn-light text-start">Inspection: {{ $summary['inspection'] ?? 0 }}</span>
                    <span class="btn btn-light text-start">Clean rooms: {{ $summary['clean'] ?? 0 }}</span>
                </div>
                <hr>
                <p class="text-muted mb-0">Use the room cards to mark dirty rooms clean or open an ad-hoc clean task. Super Admin sees the same operational state as a read-only mirror.</p>
            </aside>

            <main>
                <section class="hk-room-grid">
                    @forelse($rooms as $room)
                        @php
                            $task = $taskByRoom->get($room->id);
                            $arrival = $arrivalByRoom->get($room->id);
                            $class = $roomClass($room, $task);
                            $label = $statusLabel($room, $task, $arrival);
                            $guest = $task?->stay?->customer?->customer_name ?? $task?->stay?->customer?->name ?? $arrival?->customer?->customer_name ?? $arrival?->customer?->name;
                        @endphp
                        <article class="hk-room-card {{ $class }}">
                            <div>
                                <div class="hk-room-head">
                                    <div class="hk-room-no">{{ $room->room_number }}</div>
                                    @if($arrival)<span class="hk-flag"><i class="fas fa-flag"></i></span>@endif
                                </div>
                                <div>
                                    <span class="hk-tag type">{{ $room->type?->name ?? 'Standard' }}</span>
                                    <span class="hk-tag {{ $class }}">{{ $label }}</span>
                                </div>
                                <div class="hk-guest">{{ $guest ?: ($class === 'clean' ? 'Ready for check-in' : 'No active guest') }}</div>
                                <div class="hk-meta">
                                    @if($arrival)
                                        Booking #{{ $arrival->reservation_number ?? $arrival->id }} - arrival today
                                    @elseif($task)
                                        {{ $task->note ?: ucfirst(str_replace('_', ' ', $task->task_type ?? 'Housekeeping task')) }}
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', (string) ($room->operational_status ?? 'available'))) }} room
                                    @endif
                                </div>
                            </div>
                            <div class="hk-card-actions">
                                @if($class === 'dirty' || $class === 'inspection')
                                    <form method="POST" action="{{ route('hotel.housekeeping.rooms.clean', $room) }}" class="flex-fill">@csrf<button class="btn btn-success w-100">Mark as Clean</button></form>
                                @else
                                    <form method="POST" action="{{ route('hotel.housekeeping.rooms.dirty', $room) }}" class="flex-fill">@csrf<button class="btn btn-outline-warning w-100">Mark Dirty</button></form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="hk-panel p-4 text-muted">No rooms found yet. Add hotel rooms first and this board will become your live housekeeping grid.</div>
                    @endforelse
                </section>

                <section class="hk-workbench">
                    <div class="hk-panel hk-table-wrap">
                        <table class="table hk-table">
                            <thead><tr><th>Room No</th><th>Room Type</th><th>Service Type</th><th>Room Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                @forelse($taskRows as $task)
                                    <tr>
                                        <td><strong>{{ $task->room?->room_number ?? 'N/A' }}</strong></td>
                                        <td>{{ $task->room?->type?->name ?? 'Room' }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', (string) ($task->task_type ?? 'Cleaning'))) }}</td>
                                        <td><span class="hk-tag {{ $task->priority === 'high' ? 'dirty' : 'occupied' }}">{{ ucfirst(str_replace('_', ' ', (string) $task->status)) }}</span></td>
                                        <td><form method="POST" action="{{ route('hotel.housekeeping.tasks.complete', $task) }}">@csrf<button class="btn btn-sm btn-primary">Finish Cleaning</button></form></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No housekeeping tasks require attention.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <aside class="hk-panel hk-side-card">
                        <h4>Priority Cleaning</h4>
                        @forelse(collect($priorityTasks ?? [])->take(6) as $task)
                            <div class="hk-mini-task {{ $task->priority === 'high' ? 'high' : '' }}">
                                <strong>Room {{ $task->room?->room_number ?? 'N/A' }}</strong>
                                <div class="text-muted small">{{ $task->note ?: ucfirst(str_replace('_', ' ', (string) ($task->task_type ?? 'Housekeeping task'))) }}</div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No high priority cleaning tasks.</p>
                        @endforelse
                    </aside>
                </section>
            </main>
        </div>
    </div>
</div>
@endsection
