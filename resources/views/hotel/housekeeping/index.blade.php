@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="mb-0">Housekeeping</h3>
                <p class="text-muted mb-0">Room cleaning and readiness workflow</p>
            </div>
            <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-secondary">Front Desk</a>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg col-md-4 col-6"><div class="card"><div class="card-body"><small class="text-muted">Dirty</small><h4>{{ $summary['dirty'] }}</h4></div></div></div>
            <div class="col-lg col-md-4 col-6"><div class="card"><div class="card-body"><small class="text-muted">Assigned</small><h4>{{ $summary['assigned'] }}</h4></div></div></div>
            <div class="col-lg col-md-4 col-6"><div class="card"><div class="card-body"><small class="text-muted">Cleaning</small><h4>{{ $summary['cleaning'] }}</h4></div></div></div>
            <div class="col-lg col-md-4 col-6"><div class="card"><div class="card-body"><small class="text-muted">Clean</small><h4>{{ $summary['clean'] }}</h4></div></div></div>
            <div class="col-lg col-md-4 col-6"><div class="card"><div class="card-body"><small class="text-muted">Inspection</small><h4>{{ $summary['inspection'] }}</h4></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Workflow Board</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach(['open' => 'Dirty', 'assigned' => 'Assigned', 'cleaning' => 'Cleaning', 'completed' => 'Clean', 'inspection' => 'Inspection'] as $statusKey => $label)
                                <div class="col-xl-2 col-lg-4 col-md-6">
                                    <div class="border rounded h-100 p-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>{{ $label }}</strong>
                                            <span class="badge bg-secondary">{{ $tasks->get($statusKey)?->count() ?? 0 }}</span>
                                        </div>
                                        @forelse(($tasks->get($statusKey) ?? collect())->take(8) as $task)
                                            <div class="border rounded bg-white p-2 mb-2">
                                                <div class="fw-semibold">Room {{ $task->room?->room_number ?? 'N/A' }}</div>
                                                <div class="small text-muted">{{ $task->room?->type?->name ?? 'No Type' }}</div>
                                                <div class="small">Priority: {{ ucfirst((string) $task->priority) }}</div>
                                                @if($task->stay?->customer)
                                                    <div class="small">Guest: {{ $task->stay->customer->customer_name ?? $task->stay->customer->name }}</div>
                                                @endif
                                                <div class="d-grid gap-1 mt-2">
                                                    @if($statusKey !== 'completed')
                                                        <form method="POST" action="{{ route('hotel.housekeeping.tasks.complete', $task) }}">@csrf<button class="btn btn-sm btn-outline-success w-100">Complete</button></form>
                                                    @endif
                                                    @if($task->room)
                                                        <form method="POST" action="{{ route('hotel.housekeeping.rooms.clean', $task->room) }}">@csrf<button class="btn btn-sm btn-light w-100">Mark Clean</button></form>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-muted small">No rooms in {{ strtolower($label) }}.</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Priority Cleaning</h5></div>
                    <div class="card-body">
                        @forelse($priorityTasks as $task)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">Room {{ $task->room?->room_number ?? 'N/A' }}</div>
                                <div class="small">{{ $task->note ?: 'Priority task' }}</div>
                            </div>
                        @empty
                            <div class="alert alert-light mb-0">No priority cleaning tasks right now.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Arrivals Waiting for Room</h5></div>
                    <div class="card-body">
                        @forelse($arrivalsWaitingForRoom as $reservation)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">{{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'Guest' }}</div>
                                <div class="small">{{ $reservation->roomType?->name ?? 'Room Type N/A' }} | Arrival {{ optional($reservation->arrival_date)->format('d M') }}</div>
                            </div>
                        @empty
                            <div class="alert alert-light mb-0">No arrivals are waiting for room readiness.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Dirty Rooms Ready for Action</h5></div>
                    <div class="card-body">
                        @forelse($departedDirtyRooms->take(10) as $room)
                            <form method="POST" action="{{ route('hotel.housekeeping.rooms.clean', $room) }}" class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center gap-2">
                                @csrf
                                <div>
                                    <div class="fw-semibold">Room {{ $room->room_number }}</div>
                                    <div class="small text-muted">{{ $room->type?->name ?? 'No Type' }}</div>
                                </div>
                                <button class="btn btn-sm btn-primary">Mark Clean</button>
                            </form>
                        @empty
                            <div class="alert alert-light mb-0">All rooms are currently clear. No housekeeping tasks require attention.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
