@extends('layout.mainlayout')

@section('style')
<style>
    .hk-page { background:#f4f4f2; }
    .hk-shell { display:grid; grid-template-columns:180px minmax(0,1fr); min-height:calc(100vh - 160px); }
    .hk-floors { background:#263f42; color:#fff; padding:18px 0; }
    .hk-floors a, .hk-floors span { display:block; padding:14px 20px; color:#fff; text-decoration:none; font-weight:700; }
    .hk-floors .active { background:#1d3033; border-left:6px solid #f5c242; }
    .hk-main { background:#fff; }
    .hk-titlebar { background:#d25b42; color:#fff; padding:20px 24px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .hk-tabs { display:flex; justify-content:flex-end; background:#f0f0f0; border-bottom:1px solid #cfd5db; }
    .hk-tabs span { padding:16px 22px; border-left:1px solid #cfd5db; font-weight:800; }
    .hk-tabs .active { background:#2f5054; color:#fff; }
    .hk-table th { background:#18587d; color:#fff; border:0; padding:14px; text-transform:uppercase; }
    .hk-table td { padding:12px 14px; vertical-align:middle; border-color:#e5e5e5; }
    .hk-table tbody tr:nth-child(even) { background:#ededed; }
    .hk-room { display:inline-flex; width:68px; height:42px; align-items:center; justify-content:center; border:1px solid #c8ced6; background:#fff; font-weight:900; }
    .hk-badge { display:inline-flex; align-items:center; justify-content:center; min-width:44px; padding:8px 10px; border-radius:4px; color:#fff; font-weight:900; }
    .hk-badge.dep { background:#7fb24d; } .hk-badge.arr { background:#6f589d; } .hk-badge.vip { background:#f2b13c; } .hk-badge.prio { background:#c7584f; } .hk-badge.stay { background:#947356; }
    @media(max-width:991px){.hk-shell{grid-template-columns:1fr}.hk-floors{display:flex;overflow:auto;padding:0}.hk-floors a,.hk-floors span{white-space:nowrap}.hk-tabs{justify-content:flex-start;overflow:auto}}
</style>
@endsection

@section('content')
<div class="page-wrapper hk-page">
    <div class="content container-fluid">
        <div class="hk-shell">
            <aside class="hk-floors"><span>ALL FLOORS</span><a class="active" href="#">FLOOR 1</a><a href="#">FLOOR 2</a><a href="#">FLOOR 3</a><a href="{{ route('hotel.frontdesk') }}">FRONT DESK</a></aside>
            <main class="hk-main">
                <div class="hk-titlebar"><div><h3 class="mb-0 text-white">Housekeeping</h3><small>Rooms on active floor and cleaning workload</small></div><a href="{{ route('hotel.frontdesk') }}" class="btn btn-light">Front Desk</a></div>
                <div class="hk-tabs"><span class="active">All</span><span>Special</span><span>Dirty</span><span>Stayovers</span><span>My Rooms</span></div>
                <div class="table-responsive"><table class="table hk-table mb-0"><thead><tr><th>Room</th><th>Occupancy</th><th>Special</th><th>Status</th><th>Cleaner</th><th>Alerts</th></tr></thead><tbody>
                    @foreach(['open' => 'Dirty', 'assigned' => 'Assigned', 'cleaning' => 'Cleaning', 'inspection' => 'Inspection', 'completed' => 'Clean'] as $statusKey => $label)
                        @forelse(($tasks->get($statusKey) ?? collect()) as $task)
                            <tr><td><span class="hk-room">{{ $task->room?->room_number ?? 'N/A' }}</span></td><td><i class="fe fe-user me-1"></i>{{ $task->stay?->customer?->customer_name ?? $task->stay?->customer?->name ?? 'Vacant' }}</td><td><span class="hk-badge {{ $task->priority === 'high' ? 'prio' : 'stay' }}">{{ $task->priority === 'high' ? 'PRIO' : 'STAY' }}</span></td><td><form method="POST" action="{{ route('hotel.housekeeping.tasks.complete', $task) }}">@csrf<button class="btn btn-sm {{ $statusKey === 'completed' ? 'btn-success' : 'btn-outline-dark' }}">{{ $label }}</button></form></td><td>{{ $task->assigned_to ?? 'Unassigned' }}</td><td>{{ $task->note ?: 'No alerts' }}</td></tr>
                        @empty
                        @endforelse
                    @endforeach
                    @foreach($departedDirtyRooms->take(12) as $room)
                        <tr><td><span class="hk-room">{{ $room->room_number }}</span></td><td><i class="fe fe-user me-1"></i>Vacant</td><td><span class="hk-badge dep">DEP</span></td><td><form method="POST" action="{{ route('hotel.housekeeping.rooms.clean', $room) }}">@csrf<button class="btn btn-sm btn-outline-dark">Dirty</button></form></td><td>Housekeeping</td><td>Departure clean</td></tr>
                    @endforeach
                    @if($tasks->flatten(1)->isEmpty() && $departedDirtyRooms->isEmpty())<tr><td colspan="6" class="text-muted">No housekeeping tasks require attention.</td></tr>@endif
                </tbody></table></div>
            </main>
        </div>
    </div>
</div>
@endsection
