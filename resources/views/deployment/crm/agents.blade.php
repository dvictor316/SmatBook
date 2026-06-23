@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Manage Agents & Customers</h1>
                <p>Track performance, manage KYC, assign zones, and view customer details.</p>
            </div>
            <button class="agent-button" data-bs-toggle="modal" data-bs-target="#inviteAgentModal"><i class="fa-solid fa-user-plus"></i> Invite Agent</button>
        </div>

        <div class="agent-tabs mb-4">
            <a class="active" href="{{ route('deployment.crm.agents') }}">Agents List</a>
            <a href="{{ route('deployment.companies.index') }}">All Customers</a>
            <a href="{{ route('deployment.crm.reports') }}">Zones</a>
        </div>

        <form method="GET" class="agent-card mb-4">
            <div class="row g-3">
                <div class="col-lg-6"><input name="search" value="{{ request('search') }}" class="form-control" style="border-radius:14px;" placeholder="Search agents..."></div>
                <div class="col-lg-6 d-flex flex-wrap gap-2">
                    @foreach(['all' => 'All', 'active' => 'Active', 'suspended' => 'Inactive'] as $value => $label)
                        <a class="agent-button {{ request('status', 'all') === $value ? '' : 'soft' }}" href="{{ route('deployment.crm.agents', ['status' => $value]) }}">{{ $label }}</a>
                    @endforeach
                    <button class="agent-button soft" type="submit"><i class="fa-solid fa-search"></i> Search</button>
                </div>
            </div>
        </form>

        <div class="agent-grid">
            @forelse($agentRows as $row)
                @php $agent = $row['agent']; @endphp
                <section class="agent-card span-4 agent-tone-blue">
                    <div class="d-flex justify-content-between gap-3">
                        <div class="d-flex gap-3">
                            <span class="agent-initial">{{ strtoupper(mb_substr($agent->name ?? 'A', 0, 1)) }}</span>
                            <div>
                                <h4>{{ $agent->name }}</h4>
                                <small>{{ $agent->phone ?? $agent->email }} @if($agent->phone)<a class="ms-2" href="https://wa.me/{{ preg_replace('/\D+/', '', $agent->phone) }}"><i class="fa-brands fa-whatsapp"></i></a> <a href="tel:{{ $agent->phone }}"><i class="fa-solid fa-phone"></i></a>@endif</small>
                                <br><small>Last seen: {{ $row['last_seen_days'] >= 999 ? 'No activity yet' : $row['last_seen_days'] . ' days ago' }}</small>
                            </div>
                        </div>
                        <span class="agent-pill" style="background:#eafff6;color:var(--agent-green);">{{ strtoupper($row['status']) }}</span>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-6"><div class="agent-card p-2 agent-tone-amber" style="box-shadow:none;"><small>Performance</small><div class="agent-progress mt-2"><span style="width:{{ $row['performance'] }}%;"></span></div><strong>{{ $row['performance'] }}%</strong></div></div>
                        <div class="col-6"><div class="agent-card p-2 agent-tone-green" style="box-shadow:none;"><small>Clients</small><br><strong>{{ $row['clients'] }}</strong></div></div>
                        <div class="col-6"><div class="agent-card p-2 agent-tone-red" style="box-shadow:none;"><small>Violations</small><br><strong>{{ $row['violations'] }}</strong></div></div>
                        <div class="col-6"><div class="agent-card p-2 agent-tone-purple" style="box-shadow:none;"><small>Zone</small><br><strong>{{ $row['zone'] }}</strong></div></div>
                    </div>

                    <div class="agent-actions">
                        <button data-bs-toggle="modal" data-bs-target="#profileAgent{{ $agent->id }}"><i class="fa-solid fa-user"></i> View Profile</button>
                        <button data-bs-toggle="modal" data-bs-target="#zoneAgent{{ $agent->id }}"><i class="fa-solid fa-location-dot"></i> Assign Zone</button>
                        <button data-bs-toggle="modal" data-bs-target="#violationAgent{{ $agent->id }}"><i class="fa-solid fa-triangle-exclamation"></i> Add Violation</button>
                        <form method="POST" action="{{ route($row['status'] === 'suspended' ? 'deployment.crm.agents.activate' : 'deployment.crm.agents.suspend', $agent) }}" class="d-inline">
                            @csrf
                            <button type="submit" style="color:var(--agent-red);background:#fff3f7;"><i class="fa-solid fa-ban"></i> {{ $row['status'] === 'suspended' ? 'Activate Agent' : 'Suspend Agent' }}</button>
                        </form>
                    </div>
                </section>

                <div class="modal fade" id="profileAgent{{ $agent->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content agent-card p-0" style="border-radius:26px;overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#062f68,#0a438d);color:#fff;padding:24px;">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="agent-initial" style="background:rgba(255,255,255,.18);color:#fff;">{{ strtoupper(mb_substr($agent->name ?? 'A', 0, 1)) }}</span>
                                    <div><h3 style="color:#fff;">{{ $agent->name }}</h3><p class="mb-0">{{ $agent->email }}</p><span class="agent-pill mt-2" style="background:#d6fff0;color:var(--agent-green);">KYC: APPROVED</span></div>
                                </div>
                            </div>
                            <div class="p-4">
                                <h4><i class="fa-solid fa-user-shield"></i> Personal Details</h4>
                                <div class="agent-stat-row"><span>Phone</span><strong>{{ $agent->phone ?? 'N/A' }}</strong></div>
                                <div class="agent-stat-row"><span>Role</span><strong>{{ ucfirst($agent->role) }}</strong></div>
                                <div class="agent-stat-row"><span>Status</span><strong>{{ strtoupper($row['status']) }}</strong></div>
                                <div class="agent-stat-row"><span>Zone</span><strong>{{ $row['zone'] }}</strong></div>
                                <button type="button" class="agent-button soft mt-3" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="zoneAgent{{ $agent->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form method="POST" action="{{ route('deployment.crm.agents.assign-zone', $agent) }}" class="modal-content agent-card">
                            @csrf
                            <h3>Assign Zone</h3>
                            <div class="agent-field mt-3"><label>Zone</label><select name="zone_id">@foreach($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach</select></div>
                            <div class="mt-3 d-flex gap-2 justify-content-end"><button type="button" class="agent-button soft" data-bs-dismiss="modal">Cancel</button><button class="agent-button">Assign</button></div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="violationAgent{{ $agent->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form method="POST" action="{{ route('deployment.crm.agents.violations.store', $agent) }}" class="modal-content agent-card">
                            @csrf
                            <h3>Add Violation</h3>
                            <div class="agent-field mt-3"><label>Title</label><input name="title" required placeholder="No sales in 30 days"></div>
                            <div class="agent-field mt-3"><label>Severity</label><select name="severity"><option>low</option><option selected>medium</option><option>high</option><option>critical</option></select></div>
                            <div class="agent-field mt-3"><label>Notes</label><textarea name="notes" rows="3"></textarea></div>
                            <div class="mt-3 d-flex gap-2 justify-content-end"><button type="button" class="agent-button soft" data-bs-dismiss="modal">Cancel</button><button class="agent-button">Record</button></div>
                        </form>
                    </div>
                </div>
            @empty
                <section class="agent-card span-12 text-center py-5"><h3>No agents yet</h3><p class="agent-muted">Invite agents to begin building the CRM field team.</p></section>
            @endforelse
        </div>
        <div class="mt-4">{{ $agents->links() }}</div>
    </div>
</div>

<div class="modal fade" id="inviteAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('deployment.crm.agents.invite') }}" class="modal-content agent-card">
            @csrf
            <h3>Invite Agent</h3>
            <div class="agent-field mt-3"><label>Full Name</label><input name="name" required></div>
            <div class="agent-field mt-3"><label>Email</label><input type="email" name="email" required></div>
            <div class="agent-field mt-3"><label>Phone</label><input name="phone"></div>
            <div class="agent-field mt-3"><label>Zone</label><select name="zone_id"><option value="">No zone yet</option>@foreach($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach</select></div>
            <div class="mt-3 d-flex gap-2 justify-content-end"><button type="button" class="agent-button soft" data-bs-dismiss="modal">Cancel</button><button class="agent-button">Invite Agent</button></div>
        </form>
    </div>
</div>
@endsection
