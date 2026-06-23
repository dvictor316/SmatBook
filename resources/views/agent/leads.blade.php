@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Leads Center</h1>
                <p>Manage prospects, track performance, find businesses, and prepare billing.</p>
            </div>
            <button type="button" class="agent-button" data-bs-toggle="modal" data-bs-target="#agentLeadModal">
                <i class="fa-solid fa-plus"></i> Add New Lead
            </button>
        </div>

        <div class="agent-tabs mb-4">
            <a class="active" href="{{ route('agent.leads') }}"><i class="fa-solid fa-users"></i> Manage Leads</a>
            <a href="{{ route('deployment.geo.index') }}"><i class="fa-solid fa-location-dot"></i> Find Nearby</a>
            <a href="{{ route('agent.earnings') }}"><i class="fa-solid fa-file-invoice"></i> Invoices</a>
        </div>

        <div class="agent-grid mb-4">
            <section class="agent-card span-4 agent-metric agent-tone-blue">
                <span class="icon"><i class="fa-solid fa-users"></i></span>
                <div class="label">Total Leads</div>
                <div class="value">{{ number_format($stats['total_leads']) }}</div>
            </section>
            <section class="agent-card span-4 agent-metric agent-tone-green">
                <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-chart-line"></i></span>
                <div class="label">Conversion Rate</div>
                <div class="value" style="color:var(--agent-green);">{{ $stats['lead_conversion'] }}%</div>
            </section>
            <section class="agent-card span-4 agent-metric agent-tone-amber">
                <span class="icon" style="color:#f05d23;background:#fff3ec;"><i class="fa-solid fa-fire"></i></span>
                <div class="label">Hot Leads</div>
                <div class="value" style="color:#f05d23;">{{ number_format($stats['hot_leads']) }}</div>
                <small>Interested, negotiating, or awaiting payment</small>
            </section>
        </div>

        <form method="GET" class="agent-card mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-lg-5">
                    <div class="agent-field mb-0">
                        <input name="search" value="{{ request('search') }}" placeholder="Search leads by name, business, or phone">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['personal' => 'Personal', 'company' => 'Company', 'state_manager' => 'State Manager'] as $value => $label)
                            <a class="agent-button {{ request('type', 'personal') === $value ? '' : 'soft' }}" href="{{ route('agent.leads', array_merge(request()->except('page'), ['type' => $value])) }}">{{ $label }}</a>
                        @endforeach
                        <select name="status" class="form-select" style="max-width:170px;border-radius:14px;">
                            <option value="all">Status: All</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                        <select name="sort" class="form-select" style="max-width:170px;border-radius:14px;">
                            <option value="priority" @selected(request('sort') === 'priority')>Sort: Priority</option>
                            <option value="activity" @selected(request('sort') === 'activity')>Sort: Activity</option>
                            <option value="oldest" @selected(request('sort') === 'oldest')>Sort: Oldest</option>
                        </select>
                        <button class="agent-button soft" type="submit"><i class="fa-solid fa-filter"></i> Apply</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="agent-grid">
            @forelse($leads as $lead)
                <section class="agent-card span-4 agent-lead-card agent-tone-blue">
                    <div class="agent-initial">{{ strtoupper(mb_substr($lead->business_name, 0, 1)) }}</div>
                    <div>
                        <h4>{{ $lead->business_name }}</h4>
                        <small>{{ $lead->first_name }} {{ $lead->last_name }} @if($lead->address) · {{ \Illuminate\Support\Str::limit($lead->address, 55) }} @endif</small>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="agent-pill">{{ strtoupper(str_replace('_', ' ', $lead->status)) }}</span>
                            @if($lead->phone)<span class="agent-muted"><i class="fa-solid fa-phone"></i> {{ $lead->phone }}</span>@endif
                            <span class="agent-muted"><i class="fa-solid fa-note-sticky"></i> {{ $lead->source ? ucwords(str_replace('_', ' ', $lead->source)) : 'Manual' }}</span>
                        </div>
                        <div class="agent-actions">
                            @if($lead->phone)<a href="tel:{{ $lead->phone }}"><i class="fa-solid fa-phone"></i> Call</a>@endif
                            @if($lead->phone)<a class="chat" href="https://wa.me/{{ preg_replace('/\D+/', '', $lead->phone) }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Chat</a>@endif
                            <form method="POST" action="{{ route('agent.leads.update', $lead) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="converted">
                                <button type="submit"><i class="fa-solid fa-check"></i> Convert</button>
                            </form>
                            <form method="POST" action="{{ route('agent.leads.destroy', $lead) }}" class="d-inline" onsubmit="return confirm('Delete this lead?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="color:var(--agent-red);background:#fff3f7;"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <i class="fa-solid fa-angle-right agent-muted mt-2"></i>
                </section>
            @empty
                <section class="agent-card span-12 text-center py-5">
                    <h3>No leads yet</h3>
                    <p class="agent-muted">Add your first lead or use Find Nearby to discover businesses around you.</p>
                    <button type="button" class="agent-button" data-bs-toggle="modal" data-bs-target="#agentLeadModal"><i class="fa-solid fa-plus"></i> Add New Lead</button>
                </section>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $leads->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="agentLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('agent.leads.store') }}" class="modal-content agent-card" style="border-radius:26px;">
            @csrf
            <div class="modal-header border-0">
                <h3 class="modal-title">Add New Lead</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="agent-form-grid">
                    <div class="agent-field"><label>First Name</label><input name="first_name" placeholder="John"></div>
                    <div class="agent-field"><label>Last Name</label><input name="last_name" placeholder="Doe"></div>
                    <div class="agent-field" style="grid-column:1 / -1;"><label>Business Name</label><input name="business_name" placeholder="Business Name" required></div>
                    <div class="agent-field">
                        <label>Business Category</label>
                        <select name="business_category">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach
                        </select>
                    </div>
                    <div class="agent-field"><label>Phone Number</label><input name="phone" placeholder="+234 8012345678"></div>
                    <div class="agent-field"><label>Email Address</label><input type="email" name="email" placeholder="email@example.com"></div>
                    <div class="agent-field"><label>Status</label><select name="status">@foreach($statuses as $status)<option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
                    <div class="agent-field" style="grid-column:1 / -1;"><label>Address / Location</label><input name="address" placeholder="Street Address, City"></div>
                    <div class="agent-field">
                        <label>Lead Source</label>
                        <select name="source">
                            @foreach($sources as $source)<option value="{{ $source }}">{{ ucwords(str_replace('_', ' ', $source)) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="agent-field">
                        <label>Type</label>
                        <select name="lead_type">
                            <option value="personal">Personal</option>
                            <option value="company">Company</option>
                            <option value="state_manager">State Manager</option>
                        </select>
                    </div>
                    <div class="agent-field" style="grid-column:1 / -1;"><label>Note</label><textarea name="notes" rows="3" placeholder="Log a call, visit, or note..."></textarea></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="agent-button soft" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="agent-button">Add Lead</button>
            </div>
        </form>
    </div>
</div>
@endsection
