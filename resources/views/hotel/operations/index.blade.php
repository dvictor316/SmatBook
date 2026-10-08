@extends('layout.mainlayout')

@section('style')
<style>
    .ops-page { background:#f4f7fb; min-height:100vh; }
    .ops-head { background:#08233f; color:#fff; border-left:5px solid #f0b323; padding:18px 20px; border-radius:6px; display:flex; justify-content:space-between; align-items:center; gap:16px; }
    .ops-head h3 { margin:0; color:#fff; font-weight:900; font-size:25px; }
    .ops-head p { margin:4px 0 0; color:#dbe7f3; }
    .ops-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .ops-kpi { background:#fff; border:1px solid #d9e3ef; border-radius:6px; padding:13px 15px; min-height:88px; }
    .ops-kpi span { color:#60738b; text-transform:uppercase; font-size:11px; font-weight:900; }
    .ops-kpi strong { display:block; color:#08233f; font-size:27px; line-height:1.15; }
    .ops-kpi.danger { border-left:4px solid #dc2626; }
    .ops-kpi.warning { border-left:4px solid #e59b11; }
    .ops-layout { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:12px; align-items:start; }
    .ops-panel { background:#fff; border:1px solid #d9e3ef; border-radius:6px; overflow:hidden; }
    .ops-panel__head { padding:13px 15px; border-bottom:1px solid #e6edf5; display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .ops-panel__head h5 { margin:0; color:#08233f; font-weight:900; }
    .ops-filter { display:grid; grid-template-columns:minmax(180px,1fr) repeat(3,150px) auto; gap:8px; padding:12px 15px; border-bottom:1px solid #e6edf5; }
    .ops-table th { background:#eef3f8; color:#43566d; text-transform:uppercase; font-size:11px; letter-spacing:.04em; white-space:nowrap; }
    .ops-table td { vertical-align:middle; font-size:13px; }
    .ops-badge { display:inline-flex; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:900; text-transform:uppercase; }
    .ops-badge.open,.ops-badge.assigned { background:#e8f1ff; color:#174e9d; }
    .ops-badge.in_progress { background:#fff3cd; color:#815d00; }
    .ops-badge.resolved { background:#dcfce7; color:#166534; }
    .ops-badge.cancelled,.ops-badge.on_hold { background:#eef0f3; color:#5c6570; }
    .priority-urgent { color:#b91c1c; font-weight:900; }
    .priority-high { color:#a16207; font-weight:900; }
    .ops-form { padding:15px; display:grid; gap:11px; }
    .ops-form label { display:block; margin-bottom:4px; color:#53657a; font-size:12px; font-weight:800; }
    .ops-form .form-control,.ops-form .form-select { min-height:40px; }
    .ops-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .sla-overdue { color:#b91c1c; font-weight:900; }
    @media(max-width:1199px){.ops-layout{grid-template-columns:1fr}.ops-kpis{grid-template-columns:repeat(2,1fr)}.ops-filter{grid-template-columns:1fr 1fr}}
    @media(max-width:575px){.ops-head{align-items:flex-start;flex-direction:column}.ops-kpis,.ops-filter,.ops-row{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="page-wrapper ops-page">
    <div class="content container-fluid">
        <header class="ops-head mb-3">
            <div><h3>Hotel Operations Centre</h3><p>Coordinate guest requests, arrivals, room readiness and engineering exceptions from one work queue.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('hotel.frontdesk') }}" class="btn btn-light"><i class="fas fa-concierge-bell me-1"></i> Front Desk</a><a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-warning"><i class="fas fa-calendar me-1"></i> Room Board</a></div>
        </header>

        <div class="ops-kpis mb-3">
            <div class="ops-kpi"><span>Active Requests</span><strong>{{ $summary['open'] }}</strong><small>Open service workload</small></div>
            <div class="ops-kpi danger"><span>Overdue SLA</span><strong>{{ $summary['overdue'] }}</strong><small>Needs immediate escalation</small></div>
            <div class="ops-kpi warning"><span>Urgent / High</span><strong>{{ $summary['urgent'] }}</strong><small>Priority guest work</small></div>
            <div class="ops-kpi"><span>Resolved Today</span><strong>{{ $summary['resolved_today'] }}</strong><small>Completed requests</small></div>
            <div class="ops-kpi"><span>Arrivals Today</span><strong>{{ $summary['arrivals'] }}</strong><small>Reserved and confirmed</small></div>
            <div class="ops-kpi"><span>Departures Today</span><strong>{{ $summary['departures'] }}</strong><small>Expected checkouts</small></div>
            <div class="ops-kpi warning"><span>Dirty Rooms</span><strong>{{ $summary['dirty_rooms'] }}</strong><small>Awaiting housekeeping</small></div>
            <div class="ops-kpi danger"><span>Engineering Open</span><strong>{{ $summary['maintenance'] }}</strong><small>Maintenance tickets</small></div>
        </div>

        <div class="ops-layout">
            <section class="ops-panel">
                <div class="ops-panel__head"><h5>Guest Service Work Queue</h5><span class="badge bg-primary">{{ $requests->total() }} requests</span></div>
                <form method="GET" class="ops-filter">
                    <input name="q" class="form-control" value="{{ request('q') }}" placeholder="Request, guest, room or subject">
                    <select name="status" class="form-select"><option value="">All statuses</option>@foreach(['open','assigned','in_progress','on_hold','resolved','cancelled'] as $value)<option value="{{ $value }}" @selected(request('status') === $value)>{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select>
                    <select name="department" class="form-select"><option value="">All departments</option>@foreach(['front_office','concierge','housekeeping','engineering','food_beverage','security','transport','management'] as $value)<option value="{{ $value }}" @selected(request('department') === $value)>{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select>
                    <select name="priority" class="form-select"><option value="">All priorities</option>@foreach(['urgent','high','normal','low'] as $value)<option value="{{ $value }}" @selected(request('priority') === $value)>{{ ucfirst($value) }}</option>@endforeach</select>
                    <button class="btn btn-primary"><i class="fas fa-filter"></i></button>
                </form>
                <div class="table-responsive">
                    <table class="table ops-table mb-0">
                        <thead><tr><th>Request</th><th>Guest / Room</th><th>Service</th><th>SLA</th><th>Owner</th><th>Status</th><th>Update</th></tr></thead>
                        <tbody>
                        @forelse($requests as $guestRequest)
                            @php $isOverdue = $guestRequest->due_at && $guestRequest->due_at->isPast() && !in_array($guestRequest->status, ['resolved','cancelled'], true); @endphp
                            <tr>
                                <td><strong>{{ $guestRequest->request_number }}</strong><br><small>{{ $guestRequest->subject }}</small></td>
                                <td>{{ $guestRequest->customer?->customer_name ?? $guestRequest->customer?->name ?? 'Non-room request' }}<br><small>Room {{ $guestRequest->room?->room_number ?? 'N/A' }}</small></td>
                                <td><span class="{{ 'priority-'.$guestRequest->priority }}">{{ strtoupper($guestRequest->priority) }}</span><br><small>{{ ucwords(str_replace('_',' ',$guestRequest->department)) }}</small></td>
                                <td class="{{ $isOverdue ? 'sla-overdue' : '' }}">{{ $guestRequest->due_at?->format('d M H:i') ?? 'Not set' }}<br><small>{{ $isOverdue ? 'OVERDUE' : ($guestRequest->due_at?->diffForHumans() ?? '') }}</small></td>
                                <td>{{ $guestRequest->assignee?->name ?? 'Unassigned' }}</td>
                                <td><span class="ops-badge {{ $guestRequest->status }}">{{ str_replace('_',' ',$guestRequest->status) }}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#requestUpdate{{ $guestRequest->id }}"><i class="fas fa-pen"></i></button>
                                    <div class="modal fade" id="requestUpdate{{ $guestRequest->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('hotel.operations.requests.update', $guestRequest) }}" class="modal-content">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Update {{ $guestRequest->request_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body ops-form"><div class="ops-row"><div><label>Status</label><select name="status" class="form-select" required>@foreach(['open','assigned','in_progress','on_hold','resolved','cancelled'] as $value)<option value="{{ $value }}" @selected($guestRequest->status === $value)>{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select></div><div><label>Priority</label><select name="priority" class="form-select">@foreach(['urgent','high','normal','low'] as $value)<option value="{{ $value }}" @selected($guestRequest->priority === $value)>{{ ucfirst($value) }}</option>@endforeach</select></div></div><div><label>Assigned Team Member</label><select name="assigned_to" class="form-select"><option value="">Unassigned</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected((int)$guestRequest->assigned_to === (int)$person->id)>{{ $person->name }}</option>@endforeach</select></div><div><label>Due At</label><input type="datetime-local" name="due_at" class="form-control" value="{{ $guestRequest->due_at?->format('Y-m-d\TH:i') }}"></div><div><label>Resolution / Handover Note</label><textarea name="resolution_note" class="form-control" rows="3">{{ $guestRequest->resolution_note }}</textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Update</button></div></form></div></div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No guest requests match this work queue.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($requests->hasPages())<div class="p-3">{{ $requests->links() }}</div>@endif
            </section>

            <aside class="ops-panel">
                <div class="ops-panel__head"><h5>Open Guest Request</h5><span class="badge bg-warning text-dark">SLA tracked</span></div>
                <form method="POST" action="{{ route('hotel.operations.requests.store') }}" class="ops-form">
                    @csrf
                    <div><label>Guest / Current Stay</label><select name="stay_id" class="form-select"><option value="">Non-room / General request</option>@foreach($stays as $stay)<option value="{{ $stay->id }}">Room {{ $stay->room?->room_number ?? 'N/A' }} - {{ $stay->customer?->customer_name ?? $stay->customer?->name ?? 'Guest' }}</option>@endforeach</select></div>
                    <div class="ops-row"><div><label>Category</label><select name="category" class="form-select" required>@foreach(['concierge','housekeeping','maintenance','room_service','laundry','transport','wakeup_call','lost_found','complaint','amenity','other'] as $value)<option value="{{ $value }}">{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select></div><div><label>Department</label><select name="department" class="form-select" required>@foreach(['front_office','concierge','housekeeping','engineering','food_beverage','security','transport','management'] as $value)<option value="{{ $value }}">{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select></div></div>
                    <div class="ops-row"><div><label>Priority</label><select name="priority" class="form-select" required><option value="normal">Normal - 60 min</option><option value="urgent">Urgent - 15 min</option><option value="high">High - 30 min</option><option value="low">Low - 3 hours</option></select></div><div><label>Assign To</label><select name="assigned_to" class="form-select"><option value="">Queue</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></div></div>
                    <div><label>Subject</label><input name="subject" class="form-control" maxlength="160" required placeholder="What does the guest need?"></div>
                    <div><label>Details</label><textarea name="details" class="form-control" rows="3" placeholder="Instructions, preferences or handover details"></textarea></div>
                    <div><label>Custom Due Time (optional)</label><input type="datetime-local" name="due_at" class="form-control"></div>
                    <button class="btn btn-primary w-100"><i class="fas fa-plus-circle me-1"></i> Open Request</button>
                </form>
            </aside>
        </div>

        <section class="ops-panel mt-3">
            <div class="ops-panel__head"><h5>Staff Shift Control</h5><span class="badge bg-dark">{{ $shifts->count() }} scheduled / recent</span></div>
            <div class="p-3">
                <form method="POST" action="{{ route('hotel.operations.shifts.store') }}" class="row g-2 align-items-end mb-3">@csrf
                    <div class="col-md-2"><label>Team Member</label><select name="user_id" class="form-select" required>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label>Date</label><input type="date" name="shift_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
                    <div class="col-md-2"><label>Department</label><select name="department" class="form-select">@foreach(['front_office','concierge','housekeeping','engineering','food_beverage','security','transport','management'] as $value)<option value="{{ $value }}">{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label>Shift</label><input name="shift_name" class="form-control" placeholder="Morning" required></div>
                    <div class="col-md-1"><label>Starts</label><input type="time" name="starts_at" class="form-control" required></div>
                    <div class="col-md-1"><label>Ends</label><input type="time" name="ends_at" class="form-control" required></div>
                    <div class="col-md-1"><label>Float</label><input type="number" step="0.01" min="0" name="opening_float" class="form-control" value="0"></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary">Schedule</button></div>
                </form>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Date</th><th>Team Member</th><th>Department</th><th>Hours</th><th>Status / Handover</th></tr></thead><tbody>
                    @forelse($shifts as $shift)<tr><td>{{ $shift->shift_date?->format('d M Y') }}</td><td>{{ $shift->user?->name }}</td><td>{{ ucwords(str_replace('_',' ',$shift->department)) }}</td><td>{{ substr($shift->starts_at,0,5) }} - {{ substr($shift->ends_at,0,5) }}</td><td><form method="POST" action="{{ route('hotel.operations.shifts.update', $shift) }}" class="d-flex gap-1">@csrf @method('PUT')<select name="status" class="form-select form-select-sm">@foreach(['scheduled','open','closed','cancelled'] as $value)<option value="{{ $value }}" @selected($shift->status === $value)>{{ ucfirst($value) }}</option>@endforeach</select><input name="handover_note" value="{{ $shift->handover_note }}" class="form-control form-control-sm" placeholder="Handover note"><button class="btn btn-sm btn-outline-primary">Save</button></form></td></tr>
                    @empty<tr><td colspan="5" class="text-muted">No staff shifts scheduled.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </section>
    </div>
</div>
@endsection
