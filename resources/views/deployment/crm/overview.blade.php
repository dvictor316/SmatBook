@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
    <style>
        .manager-money { font-size: clamp(20px, 2vw, 25px) !important; letter-spacing: -.02em; }
        .manager-mini-value { font-size: 20px !important; }
        .manager-chart-card { min-height: 190px; }
        .manager-line-chart { height: 132px; display:flex; align-items:end; justify-content:space-between; gap:10px; padding:14px 10px 8px; border-radius:18px; background:linear-gradient(180deg,#f8fbff,#eef5ff); border:1px solid #dbeafe; }
        .manager-month-bar { flex:1; min-width:34px; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:7px; }
        .manager-month-bar i { display:block; width:12px; max-width:12px; border-radius:999px; background:linear-gradient(180deg,#246bfe,#86b7ff); box-shadow:0 8px 16px rgba(36,107,254,.16); }
        .manager-month-bar small { color:#71809a; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
        .manager-funnel { display:grid; gap:9px; margin-top:14px; }
        .manager-funnel-row { display:grid; grid-template-columns:110px 1fr auto; gap:10px; align-items:center; font-size:12px; font-weight:800; color:var(--agent-ink); }
        .manager-funnel-track { height:10px; border-radius:999px; background:#e8eef6; overflow:hidden; }
        .manager-funnel-track span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#18bf86,#9ff0d4); }
        .manager-sparkline { display:flex; align-items:end; gap:5px; height:44px; }
        .manager-sparkline i { display:block; width:6px; border-radius:999px; background:linear-gradient(180deg,#5b42f3,#246bfe); }
        .manager-health-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .manager-health-tile { border:1px solid #e7edf5; border-radius:14px; padding:10px; background:linear-gradient(135deg,#f8fbff,#fff); min-height:78px; }
        .manager-health-tile span { display:block; color:var(--agent-muted); font-size:10px; text-transform:uppercase; font-weight:900; letter-spacing:.06em; }
        .manager-health-tile strong { display:block; color:var(--agent-ink); font-size:20px; line-height:1.2; margin-top:5px; }
        .manager-health-tile small { color:var(--agent-green); font-weight:800; }
        .manager-top-card { min-height: 212px; }
        .manager-top-card h3 { font-size: 20px !important; }
        .manager-top-card .agent-progress { height: 7px; }
        .manager-target-row { display:flex; justify-content:space-between; gap:10px; font-size:13px; font-weight:800; }
        .agent-metric .value { font-size: clamp(20px, 2vw, 26px); }
        .agent-bar-chart { gap:8px; }
        .agent-bar-chart span { width:9px; border-radius:999px; }
        @media(max-width:767px){ .manager-funnel-row { grid-template-columns:88px 1fr auto; } .manager-line-chart { overflow-x:auto; justify-content:flex-start; } }
    </style>
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
            <section class="agent-card span-4 manager-top-card" style="background:linear-gradient(135deg,#062f68,#0a438d);color:#fff;">
                <div class="d-flex justify-content-between flex-wrap gap-3">
                    <div>
                        <small style="color:#bcd4f6;text-transform:uppercase;font-weight:900;">Annual Goals · {{ now()->year }}</small>
                        <h3 style="color:#fff;font-size:22px;">State Targets</h3>
                    </div>
                    <div class="agent-pill" style="background:rgba(255,255,255,.15);color:#fff;">{{ $stats['days_left'] }} days left</div>
                </div>
                <div class="mt-3">
                    <div class="manager-target-row"><span>Revenue</span><strong>{{ $stats['revenue_percent'] }}%</strong></div>
                    <small style="color:#d8e7ff;">₦{{ number_format($stats['state_revenue']) }} / ₦{{ number_format($stats['revenue_target']) }}</small>
                    <div class="agent-progress mt-2" style="background:rgba(255,255,255,.18);"><span style="width:{{ $stats['revenue_percent'] }}%;background:#18bf86;"></span></div>
                </div>
                <div class="mt-3">
                    <div class="manager-target-row"><span>Customers</span><strong>{{ $stats['customer_percent'] }}%</strong></div>
                    <small style="color:#d8e7ff;">{{ number_format($stats['total_businesses']) }} / {{ number_format($stats['customer_target']) }}</small>
                    <div class="agent-progress mt-2" style="background:rgba(255,255,255,.18);"><span style="width:{{ $stats['customer_percent'] }}%;background:#f7a51e;"></span></div>
                </div>
            </section>
            <section class="agent-card span-4 manager-top-card agent-tone-blue">
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
            <section class="agent-card span-4 manager-top-card agent-tone-green">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4>Target Pace</h4>
                        <small class="agent-muted">Month-to-date execution</small>
                    </div>
                    <span class="agent-pill">{{ number_format($stats['new_businesses']) }} new</span>
                </div>
                <div class="manager-funnel mt-3">
                    <div class="manager-funnel-row"><span>Agents</span><div class="manager-funnel-track"><span style="width:{{ max(4, min(100, $stats['agent_activation_rate'])) }}%;"></span></div><strong>{{ number_format($stats['active_agents']) }}</strong></div>
                    <div class="manager-funnel-row"><span>Leads</span><div class="manager-funnel-track"><span style="width:{{ max(4, min(100, $stats['customer_percent'])) }}%;background:linear-gradient(90deg,#246bfe,#86b7ff);"></span></div><strong>{{ number_format($stats['total_businesses']) }}</strong></div>
                    <div class="manager-funnel-row"><span>Trials</span><div class="manager-funnel-track"><span style="width:{{ max(4, min(100, $stats['free_trials'])) }}%;background:linear-gradient(90deg,#f7a51e,#ffd98a);"></span></div><strong>{{ number_format($stats['free_trials']) }}</strong></div>
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
                <div class="value manager-money">₦{{ number_format($stats['state_revenue']) }}</div>
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

            <section class="agent-card span-8 manager-chart-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h4>Revenue & Lead Momentum</h4>
                        <p class="agent-muted mb-0">A quick pulse of activity across the state manager workspace.</p>
                    </div>
                    <span class="agent-pill">₦{{ number_format($stats['state_revenue']) }} revenue</span>
                </div>
                <div class="manager-line-chart mt-3" aria-label="Revenue and lead trend">
                    @php
                        $monthBars = collect(range(7, 0))->map(function ($offset, $index) use ($stats) {
                            $baseHeights = [22, 34, 28, 48, 38, 58, 32, 44];
                            return [
                                'label' => now()->subMonths($offset)->format('M'),
                                'height' => $index >= 6
                                    ? max(12, min(100, ($index === 6 ? $stats['revenue_percent'] : $stats['customer_percent']) + 22))
                                    : $baseHeights[$index],
                            ];
                        });
                    @endphp
                    @foreach($monthBars as $bar)
                        <span class="manager-month-bar">
                            <i style="height:{{ $bar['height'] }}%;"></i>
                            <small>{{ $bar['label'] }}</small>
                        </span>
                    @endforeach
                </div>
            </section>
            <section class="agent-card span-4 manager-chart-card agent-tone-green">
                <h4>Conversion Funnel</h4>
                <div class="manager-funnel">
                    @php
                        $leadBase = max(1, $stats['total_businesses']);
                        $trialRate = min(100, round(($stats['free_trials'] / $leadBase) * 100));
                        $activeRate = min(100, round(($stats['active_customers'] / $leadBase) * 100));
                    @endphp
                    <div class="manager-funnel-row"><span>Leads</span><div class="manager-funnel-track"><span style="width:100%;"></span></div><strong>{{ $stats['total_businesses'] }}</strong></div>
                    <div class="manager-funnel-row"><span>Trials</span><div class="manager-funnel-track"><span style="width:{{ max(4, $trialRate) }}%;background:linear-gradient(90deg,#f7a51e,#ffd98a);"></span></div><strong>{{ $stats['free_trials'] }}</strong></div>
                    <div class="manager-funnel-row"><span>Active</span><div class="manager-funnel-track"><span style="width:{{ max(4, $activeRate) }}%;"></span></div><strong>{{ $stats['active_customers'] }}</strong></div>
                </div>
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

            <section class="agent-card span-4 agent-tone-blue">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <h4>Agent Workload</h4>
                        <small class="agent-muted">Agents, leads, and conversions</small>
                    </div>
                    <div class="manager-sparkline">
                        @foreach([22, 31, 26, 43, 36, 52, 46] as $bar)
                            <i style="height:{{ $bar }}px;"></i>
                        @endforeach
                    </div>
                </div>
                <div class="agent-stat-row"><span>Total Agents</span><strong>{{ number_format($stats['total_agents']) }}</strong></div>
                <div class="agent-stat-row"><span>Total Leads</span><strong>{{ number_format($stats['total_businesses']) }}</strong></div>
                <div class="agent-stat-row"><span>Converted</span><strong>{{ number_format($stats['active_customers']) }}</strong></div>
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
                    @php
                        $topAgent = $agentRows->sortByDesc('performance')->first();
                        $avgPerformance = $agentRows->count() ? round($agentRows->avg('performance')) : 0;
                    @endphp
                    <div class="manager-health-grid">
                        <div class="manager-health-tile">
                            <span>Healthy Agents</span>
                            <strong>{{ number_format($stats['active_agents']) }}</strong>
                            <small>{{ $stats['total_agents'] ? round(($stats['active_agents'] / max(1, $stats['total_agents'])) * 100) : 0 }}% active</small>
                        </div>
                        <div class="manager-health-tile">
                            <span>Avg Score</span>
                            <strong>{{ $avgPerformance }}%</strong>
                            <small>Team performance</small>
                        </div>
                        <div class="manager-health-tile">
                            <span>Top Agent</span>
                            <strong style="font-size:15px;">{{ $topAgent ? \Illuminate\Support\Str::limit($topAgent['agent']->name, 18) : 'No agent' }}</strong>
                            <small>{{ $topAgent['performance'] ?? 0 }}% score</small>
                        </div>
                        <div class="manager-health-tile">
                            <span>Open Issues</span>
                            <strong>0</strong>
                            <small>Clean team</small>
                        </div>
                    </div>
                @endforelse
            </section>

            <section class="agent-card span-4 agent-tone-green">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <h4>Customer Pulse</h4>
                        <small class="agent-muted">Active against inactive businesses</small>
                    </div>
                    <div class="agent-donut" style="--value:{{ $stats['retention'] }};--color:var(--agent-green);width:82px;height:82px;">
                        <strong style="font-size:13px;">{{ $stats['retention'] }}%</strong>
                    </div>
                </div>
                <div class="agent-stat-row"><span>Active Customers</span><strong>{{ number_format($stats['active_customers']) }}</strong></div>
                <div class="agent-stat-row"><span>Inactive Customers</span><strong>{{ number_format($stats['inactive_customers']) }}</strong></div>
            </section>

            <section class="agent-card span-4 agent-tone-amber">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <h4>Trial Pipeline</h4>
                        <small class="agent-muted">Trial and conversion health</small>
                    </div>
                    <span class="agent-pill">{{ number_format($stats['free_trials']) }} trials</span>
                </div>
                <div class="manager-funnel mt-3">
                    <div class="manager-funnel-row"><span>Trials</span><div class="manager-funnel-track"><span style="width:{{ max(4, min(100, $trialRate ?? 0)) }}%;background:linear-gradient(90deg,#f7a51e,#ffd98a);"></span></div><strong>{{ number_format($stats['free_trials']) }}</strong></div>
                    <div class="manager-funnel-row"><span>Converted</span><div class="manager-funnel-track"><span style="width:{{ max(4, min(100, $activeRate ?? 0)) }}%;"></span></div><strong>{{ number_format($stats['active_customers']) }}</strong></div>
                </div>
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
