@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="mb-0">Maintenance</h3>
                <p class="text-muted mb-0">Room issues, technicians, and service resolution</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Open</small><h4>{{ $summary['open'] }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Urgent</small><h4>{{ $summary['urgent'] }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">In Progress</small><h4>{{ $summary['in_progress'] }}</h4></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><small class="text-muted">Completed Today</small><h4>{{ $summary['completed_today'] }}</h4></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Open Ticket</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hotel.maintenance.store') }}" class="row g-2">
                            @csrf
                            <div class="col-12"><label class="form-label">Room</label><select name="room_id" class="form-control" required><option value="">Select room</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_number }} - {{ $room->type?->name ?? 'No Type' }}</option>@endforeach</select></div>
                            <div class="col-12"><label class="form-label">Problem</label><input type="text" name="title" class="form-control" required></div>
                            <div class="col-12"><label class="form-label">Priority</label><select name="severity" class="form-control"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
                            <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                            <div class="col-12"><button class="btn btn-primary">Create Ticket</button></div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Reservation Conflicts</h5></div>
                    <div class="card-body">
                        @forelse($reservationConflicts as $reservation)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">{{ $reservation->reservation_number }}</div>
                                <div class="small">{{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'Guest' }}</div>
                                <div class="small text-danger">Room {{ $reservation->room?->room_number ?? 'N/A' }} is unavailable</div>
                            </div>
                        @empty
                            <div class="alert alert-light mb-0">No future reservation conflicts detected.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Maintenance Tickets</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Ticket</th><th>Room</th><th>Problem</th><th>Priority</th><th>Opened</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->ticket_no }}</td>
                                    <td>{{ $ticket->room?->room_number ?? 'N/A' }}</td>
                                    <td>{{ $ticket->title }}</td>
                                    <td><span class="badge {{ in_array($ticket->severity, ['high', 'critical']) ? 'bg-danger' : 'bg-secondary' }}">{{ ucfirst((string) $ticket->severity) }}</span></td>
                                    <td>{{ optional($ticket->created_at)->format('d M H:i') }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', (string) $ticket->status)) }}</span></td>
                                    <td>
                                        <form method="POST" action="{{ route('hotel.maintenance.status', $ticket) }}" class="d-flex gap-1">
                                            @csrf
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">No maintenance tickets have been logged.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-3">{{ $tickets->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
