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
            <section class="agent-card span-8" style="background:linear-gradient(135deg,#062f68,#0a438d);color:#fff;">
                <div class="d-flex justify-content-between flex-wrap gap-3">
                    <div>
                        <small style="color:#bcd4f6;text-transform:uppercase;font-weight:900;">Annual Goals · {{ now()->year }}</small>
                        <h3 style="color:#fff;font-size:22px;">State Targets</h3>
                    </div>
                    <div class="agent-pill" style="background:rgba(255,255,255,.15);color:#fff;">{{ $stats['days_left'] }} days left</div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between"><span>Revenue Target ₦{{ number_format($stats['state_revenue']) }} / ₦{{ number_format($stats['revenue_target']) }}</span><strong>{{ $stats['revenue_percent'] }}% Achieved</strong></div>
                    <div class="agent-progress mt-2" style="background:rgba(255,255,255,.18);"><span style="width:{{ $stats['revenue_percent'] }}%;background:#18bf86;"></span></div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between"><span>Customer Acquisition</span><strong>{{ number_format($stats['total_businesses']) }} / {{ number_format($stats['customer_target']) }}</strong></div>
                    <div class="agent-progress mt-2" style="background:rgba(255,255,255,.18);"><span style="width:{{ $stats['customer_percent'] }}%;background:#f7a51e;"></span></div>
                </div>
            </section>
            <section class="agent-card span-4 agent-tone-blue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4>State Health</h4>
                        <small class="agent-muted">Activation, retention, churn</small>
                    </div>
                    <span class="agent-pill">{{ $stats['agent_activation_rate'] }}% active</span>
                </div>
                <div class="agent-bar-chart mt-3">
                    <span style="height:{{ max(12, $stats['agent_activation_rate']) }}%;background:linear-gradient(180deg,#246bfe,#86b7ff);"></span>
                    <span style="height:{{ max(12, $stats['retention']) }}%;background:linear-gradient(180deg,#18bf86,#9ff0d4);"></span>
                    <span style="height:{{ max(12, $stats['churn']) }}%;background:linear-gradient(180deg,#d91f5c,#ff9fbc);"></span>
                    <span style="height:{{ max(12, $stats['customer_percent']) }}%;background:linear-gradient(180deg,#f7a51e,#ffd98a);"></span>
                </div>
                <div class="d-flex gap-2 flex-wrap mt-3">
                    <span class="agent-pill">Retention {{ $stats['retention'] }}%</span>
                    <span class="agent-pill">Churn {{ $stats['churn'] }}%</span>
                </div>
            </section>

            <section class="agent-card span-3 agent-metric agent-tone-blue">
                <span class="icon"><i class="fa-solid fa-users"></i></span>
                <div class="label">Total Agents</div>
                <div class="value">{{ number_format($stats['total_agents']) }}</div>
                <small style="color:var(--agent-green);"><i class="fa-solid fa-arrow-up"></i> +{{ $stats['new_agents'] }} this month</small>
            </section>
            <section class="agent-card span-3 agent-metric agent-tone-purple">
                <span class="icon" style="color:var(--agent-purple);background:#f2f0ff;"><i class="fa-solid fa-store"></i></span>
                <div class="label">Total Businesses</div>
                <div class="value">{{ number_format($stats['total_businesses']) }}</div>
                <small style="color:var(--agent-green);">+{{ $stats['new_businesses'] }} New</small>
            </section>
            <section class="agent-card span-3 agent-metric agent-tone-green">
                <span class="icon" style="color:var(--agent-navy);"><i class="fa-solid fa-money-bill"></i></span>
                <div class="label">State Revenue</div>
                <div class="value">₦{{ number_format($stats['state_revenue']) }}</div>
            </section>
            <section class="agent-card span-3 agent-metric agent-tone-amber">
                <span class="icon" style="color:var(--agent-amber);background:#fff8e8;"><i class="fa-solid fa-flask"></i></span>
                <div class="label">Free Trials</div>
                <div class="value">{{ number_format($stats['free_trials']) }}</div>
                <small style="color:var(--agent-red);">Monitor expiries</small>
            </section>

            <section class="agent-card span-3 agent-tone-blue">
                <small class="fw-bold text-uppercase">Agent Activation Rate</small>
                <div class="d-flex justify-content-between align-items-end">
                    <h3 style="font-size:26px;">{{ $stats['agent_activation_rate'] }}%</h3>
                    <div class="agent-bar-chart"><span style="height:22px;opacity:.25"></span><span style="height:30px;opacity:.45"></span><span style="height:42px;opacity:.75"></span><span style="height:54px"></span></div>
                </div>
                <small>Agents making at least one sale</small>
            </section>
            <section class="agent-card span-3 agent-tone-green">
                <small class="fw-bold text-uppercase">Customer Retention Rate</small>
                <h3 style="font-size:26px;color:var(--agent-green);">{{ $stats['retention'] }}%</h3>
                <small>Customers active over total state customers</small>
            </section>
            <section class="agent-card span-3 agent-tone-red">
                <small class="fw-bold text-uppercase">State Churn Rate</small>
                <h3 style="font-size:26px;color:var(--agent-red);">{{ $stats['churn'] }}%</h3>
                <small>Business churn in state</small>
            </section>
            <section class="agent-card span-3 agent-tone-amber">
                <small class="fw-bold text-uppercase">Customer Target</small>
                <h3 style="font-size:26px;color:var(--agent-amber);">{{ $stats['customer_percent'] }}%</h3>
                <div class="agent-progress mt-2"><span style="width:{{ $stats['customer_percent'] }}%;background:var(--agent-amber);"></span></div>
                <small>{{ number_format($stats['total_businesses']) }} of {{ number_format($stats['customer_target']) }}</small>
            </section>

            <section class="agent-card span-4">
                <h4>Customers Activity Status</h4>
                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                    <div class="agent-donut" style="--value:{{ $stats['retention'] }};--color:var(--agent-green);"><strong>{{ $stats['active_customers'] + $stats['inactive_customers'] }}</strong></div>
                    <div>
                        <p><i class="agent-dot"></i>Active <strong>{{ $stats['active_customers'] }} ({{ $stats['retention'] }}%)</strong></p>
                        <p><i class="agent-dot" style="background:var(--agent-red);"></i>Inactive <strong>{{ $stats['inactive_customers'] }} ({{ $stats['churn'] }}%)</strong></p>
                    </div>
                </div>
            </section>

            <section class="agent-card span-4 agent-tone-purple">
                <h4>Monthly Mix</h4>
                <div class="agent-heatmap mt-3">
                    @foreach([1,2,0,3,1,4,2,0,1,3,2,4,1,2,0,2,3,4,2,1,3] as $level)
                        <span class="agent-heat level-{{ $level }}"></span>
                    @endforeach
                </div>
                <small class="agent-muted d-block mt-3">Activity intensity across agents and customer follow-ups.</small>
            </section>

            <section class="agent-card span-4">
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

            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4>Agent Performance Snapshot</h4>
                        <p class="agent-muted mb-0">Live CRM details for agents, clients, KYC status, zone, sales, and performance.</p>
                    </div>
                    <a href="{{ route('deployment.crm.agents') }}" class="agent-button soft">Manage Agents</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Zone</th>
                                <th>Clients</th>
                                <th>KYC / Status</th>
                                <th>Sales</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentRows->take(8) as $row)
                                <tr>
                                    <td>
                                        <strong>{{ $row['agent']->name }}</strong><br>
                                        <small class="agent-muted">{{ $row['agent']->phone ?? $row['agent']->email }}</small>
                                    </td>
                                    <td>{{ $row['zone'] }}</td>
                                    <td>{{ $row['clients'] }}</td>
                                    <td><span class="agent-pill" style="background:#eafff6;color:var(--agent-green);">KYC Approved</span> <span class="agent-pill">{{ strtoupper($row['status']) }}</span></td>
                                    <td><strong>₦{{ number_format($row['sales']) }}</strong></td>
                                    <td style="min-width:140px;"><div class="agent-progress"><span style="width:{{ $row['performance'] }}%;"></span></div><small>{{ $row['performance'] }}%</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center agent-muted py-4">No agents available yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
