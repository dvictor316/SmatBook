@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Manager Dashboard</h1>
                <p>State targets, agent activation, customer activity, and CRM health.</p>
            </div>
            <a href="{{ route('deployment.crm.agents') }}" class="agent-button"><i class="fa-solid fa-user-plus"></i> Invite Agent</a>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-12" style="background:linear-gradient(135deg,#062f68,#0a438d);color:#fff;">
                <div class="d-flex justify-content-between flex-wrap gap-3">
                    <div>
                        <small style="color:#bcd4f6;text-transform:uppercase;font-weight:900;">Annual Goals · {{ now()->year }}</small>
                        <h3 style="color:#fff;font-size:34px;">State Targets</h3>
                    </div>
                    <div class="agent-pill" style="background:rgba(255,255,255,.15);color:#fff;font-size:22px;">{{ $stats['days_left'] }} <small style="color:#d6e6ff;">days left</small></div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between"><span>Revenue Target ₦{{ number_format($stats['state_revenue']) }} / ₦{{ number_format($stats['revenue_target']) }}</span><strong>{{ $stats['revenue_percent'] }}% Achieved</strong></div>
                    <div class="agent-progress mt-2" style="background:rgba(255,255,255,.18);"><span style="width:{{ $stats['revenue_percent'] }}%;background:#18bf86;"></span></div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between"><span>Customer Acquisition</span><strong>{{ number_format($stats['total_businesses']) }} / {{ number_format($stats['customer_target']) }}</strong></div>
                    <div class="agent-progress mt-2" style="background:rgba(255,255,255,.18);"><span style="width:{{ $stats['customer_percent'] }}%;background:#f7a51e;"></span></div>
                </div>
            </section>

            <section class="agent-card span-3 agent-metric">
                <span class="icon"><i class="fa-solid fa-users"></i></span>
                <div class="label">Total Agents</div>
                <div class="value">{{ number_format($stats['total_agents']) }}</div>
                <small style="color:var(--agent-green);"><i class="fa-solid fa-arrow-up"></i> +{{ $stats['new_agents'] }} this month</small>
            </section>
            <section class="agent-card span-3 agent-metric">
                <span class="icon" style="color:var(--agent-purple);background:#f2f0ff;"><i class="fa-solid fa-store"></i></span>
                <div class="label">Total Businesses</div>
                <div class="value">{{ number_format($stats['total_businesses']) }}</div>
                <small style="color:var(--agent-green);">+{{ $stats['new_businesses'] }} New</small>
            </section>
            <section class="agent-card span-3 agent-metric">
                <span class="icon" style="color:var(--agent-navy);"><i class="fa-solid fa-money-bill"></i></span>
                <div class="label">State Revenue</div>
                <div class="value">₦{{ number_format($stats['state_revenue']) }}</div>
            </section>
            <section class="agent-card span-3 agent-metric">
                <span class="icon" style="color:var(--agent-amber);background:#fff8e8;"><i class="fa-solid fa-flask"></i></span>
                <div class="label">Free Trials</div>
                <div class="value">{{ number_format($stats['free_trials']) }}</div>
                <small style="color:var(--agent-red);">Monitor expiries</small>
            </section>

            <section class="agent-card span-4">
                <small class="fw-bold text-uppercase">Agent Activation Rate</small>
                <div class="d-flex justify-content-between align-items-end">
                    <h3 style="font-size:42px;">{{ $stats['agent_activation_rate'] }}%</h3>
                    <div class="agent-bar-chart"><span style="height:30px;opacity:.25"></span><span style="height:44px;opacity:.45"></span><span style="height:58px;opacity:.75"></span><span style="height:72px"></span></div>
                </div>
                <small>Agents making at least one sale</small>
            </section>
            <section class="agent-card span-4">
                <small class="fw-bold text-uppercase">Customer Retention Rate</small>
                <h3 style="font-size:42px;color:var(--agent-green);">{{ $stats['retention'] }}%</h3>
                <small>Customers active over total state customers</small>
            </section>
            <section class="agent-card span-4">
                <small class="fw-bold text-uppercase">State Churn Rate</small>
                <h3 style="font-size:42px;color:var(--agent-red);">{{ $stats['churn'] }}%</h3>
                <small>Business churn in state</small>
            </section>

            <section class="agent-card span-6">
                <h4>Customers Activity Status</h4>
                <div class="d-flex flex-wrap align-items-center gap-4 mt-4">
                    <div class="agent-donut" style="--value:{{ $stats['retention'] }};--color:var(--agent-green);"><strong>{{ $stats['active_customers'] + $stats['inactive_customers'] }}</strong></div>
                    <div>
                        <p><i class="agent-dot"></i>Active <strong>{{ $stats['active_customers'] }} ({{ $stats['retention'] }}%)</strong></p>
                        <p><i class="agent-dot" style="background:var(--agent-red);"></i>Inactive <strong>{{ $stats['inactive_customers'] }} ({{ $stats['churn'] }}%)</strong></p>
                    </div>
                </div>
            </section>

            <section class="agent-card span-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Underperforming Agents</h4>
                    <a href="{{ route('deployment.crm.agents') }}" class="fw-bold">View All</a>
                </div>
                @forelse($underperformingAgents as $row)
                    <div class="agent-stat-row">
                        <span><span class="agent-initial d-inline-grid me-2" style="width:34px;height:34px;">{{ strtoupper(mb_substr($row['agent']->name ?? 'A', 0, 1)) }}</span>{{ $row['agent']->name }}</span>
                        <span style="color:var(--agent-red);font-weight:900;">No Sales ({{ min($row['last_seen_days'], 30) }} Days)</span>
                        <span>
                            @if($row['agent']->phone)<a href="tel:{{ $row['agent']->phone }}"><i class="fa-solid fa-phone"></i></a>@endif
                        </span>
                    </div>
                @empty
                    <p class="agent-muted mb-0">No underperforming agents right now.</p>
                @endforelse
            </section>
        </div>
    </div>
</div>
@endsection
