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
                <p>Monitor team prospects, ownership, conversion status, and activity across the state.</p>
            </div>
            <a href="{{ route('deployment.geo.index') }}" class="agent-button"><i class="fa-solid fa-location-dot"></i> Find Nearby</a>
        </div>

        <div class="agent-tabs mb-4">
            <a class="active" href="{{ route('deployment.crm.leads') }}">Manage Leads</a>
            <a href="{{ route('deployment.geo.index') }}">Find Nearby</a>
            <a href="{{ route('deployment.invoices.index') }}">Invoices</a>
        </div>

        <div class="agent-grid mb-4">
            <section class="agent-card span-4 agent-metric"><span class="icon"><i class="fa-solid fa-users"></i></span><div class="label">Team Leads</div><div class="value">{{ number_format($stats['total_businesses']) }}</div></section>
            <section class="agent-card span-4 agent-metric"><span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-check"></i></span><div class="label">Converted</div><div class="value">{{ number_format($stats['active_customers']) }}</div></section>
            <section class="agent-card span-4 agent-metric"><span class="icon" style="color:#f05d23;background:#fff3ec;"><i class="fa-solid fa-fire"></i></span><div class="label">Hot Pipeline</div><div class="value">{{ $leads->getCollection()->whereIn('status', ['interested','meeting_scheduled','negotiating','awaiting_payment'])->count() }}</div></section>
        </div>

        <form method="GET" class="agent-card mb-4">
            <div class="row g-3">
                <div class="col-lg-4"><input class="form-control" name="search" value="{{ request('search') }}" style="border-radius:14px;" placeholder="Search business, phone, email, address..."></div>
                <div class="col-lg-3">
                    <select class="form-select" name="agent_id" style="border-radius:14px;">
                        <option value="">All Agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((int) request('agent_id') === (int) $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <select class="form-select" name="status" style="border-radius:14px;">
                        <option value="all">All Statuses</option>
                        @foreach(['new','interested','meeting_scheduled','demo_completed','negotiating','awaiting_payment','converted','lost'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2"><button class="agent-button w-100"><i class="fa-solid fa-filter"></i> Filter</button></div>
            </div>
        </form>

        <div class="agent-grid">
            @forelse($leads as $lead)
                <section class="agent-card span-6">
                    <div class="agent-lead-card">
                        <span class="agent-initial">{{ strtoupper(mb_substr($lead->business_name, 0, 1)) }}</span>
                        <div>
                            <h4>{{ $lead->business_name }}</h4>
                            <small>{{ optional($lead->agent)->name ?? 'Unassigned Agent' }} · {{ \Illuminate\Support\Str::limit($lead->address, 70) }}</small>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="agent-pill">{{ strtoupper(str_replace('_', ' ', $lead->status)) }}</span>
                                <span class="agent-muted">{{ ucwords(str_replace('_', ' ', $lead->source ?? 'manual')) }}</span>
                            </div>
                            <div class="agent-actions">
                                @if($lead->phone)<a href="tel:{{ $lead->phone }}"><i class="fa-solid fa-phone"></i> Call</a>@endif
                                @if($lead->phone)<a class="chat" href="https://wa.me/{{ preg_replace('/\D+/', '', $lead->phone) }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Chat</a>@endif
                                <a href="{{ route('deployment.crm.reports') }}"><i class="fa-solid fa-chart-line"></i> Report</a>
                            </div>
                        </div>
                    </div>
                </section>
            @empty
                <section class="agent-card span-12 text-center py-5">
                    <h3>No team leads yet</h3>
                    <p class="agent-muted">Agent-created leads and Find Nearby prospects will appear here.</p>
                </section>
            @endforelse
        </div>

        <div class="mt-4">{{ $leads->links() }}</div>
    </div>
</div>
@endsection
