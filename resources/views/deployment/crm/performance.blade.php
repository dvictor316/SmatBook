@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
    <style>
        .perf-score { font-size:clamp(24px,3vw,30px) !important; }
        .perf-dashboard-chart { height:140px; display:flex; align-items:end; gap:8px; padding:16px 10px 8px; border-radius:18px; background:linear-gradient(180deg,#f8fbff,#eef5ff); border:1px solid #dbeafe; }
        .perf-dashboard-chart span { flex:1; border-radius:999px 999px 8px 8px; background:linear-gradient(180deg,#5b42f3,#246bfe); }
        .perf-zone-pill { display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid #e7edf5; border-radius:14px; padding:10px 12px; margin-top:9px; background:#fff; }
        .perf-money { font-size:13px; font-weight:900; color:var(--agent-green); }
    </style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Performance</h1>
                <p>Activation, retention, churn, zone health, and underperforming agents.</p>
            </div>
            <a href="{{ route('deployment.crm.reports') }}" class="agent-button soft"><i class="fa-solid fa-file-lines"></i> Advanced Reports</a>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-4 agent-tone-blue"><small class="fw-bold text-uppercase">Agent Activation Rate</small><div class="d-flex justify-content-between align-items-end"><h3 class="perf-score">{{ $stats['agent_activation_rate'] }}%</h3><div class="agent-bar-chart"><span style="height:22px;opacity:.25"></span><span style="height:32px;opacity:.5"></span><span style="height:44px;opacity:.75"></span><span style="height:58px"></span></div></div><small>New agents making at least one sale.</small></section>
            <section class="agent-card span-4 agent-tone-green"><small class="fw-bold text-uppercase">Customer Retention Rate</small><h3 class="perf-score" style="color:var(--agent-green);">{{ $stats['retention'] }}%</h3><small>Customers active against total state businesses.</small></section>
            <section class="agent-card span-4 agent-tone-red"><small class="fw-bold text-uppercase">State Churn Rate</small><h3 class="perf-score" style="color:var(--agent-red);">{{ $stats['churn'] }}%</h3><small>Businesses inactive in the state pipeline.</small></section>

            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h4>Performance Trend</h4>
                        <p class="agent-muted mb-0">Activation, retention, customer growth, and revenue progress.</p>
                    </div>
                    <span class="agent-pill">{{ $stats['customer_percent'] }}% customer target</span>
                </div>
                <div class="perf-dashboard-chart mt-3">
                    @foreach([18, 28, 34, max(12, $stats['agent_activation_rate']), max(12, $stats['retention']), max(12, $stats['customer_percent']), max(12, $stats['revenue_percent'])] as $height)
                        <span style="height:{{ min(100, $height) }}%;"></span>
                    @endforeach
                </div>
            </section>

            <section class="agent-card span-6">
                <h4>Underperforming Agents</h4>
                @forelse($underperformingAgents as $row)
                    <div class="agent-stat-row">
                        <span><span class="agent-initial d-inline-grid me-2" style="width:34px;height:34px;">{{ strtoupper(mb_substr($row['agent']->name ?? 'A', 0, 1)) }}</span>{{ $row['agent']->name }}<br><small>No Sales / Low Activity</small></span>
                        <span>@if($row['agent']->phone)<a href="tel:{{ $row['agent']->phone }}" class="agent-button soft"><i class="fa-solid fa-phone"></i></a>@endif</span>
                    </div>
                @empty
                    <p class="agent-muted mt-3">No underperforming agents right now.</p>
                @endforelse
            </section>

            <section class="agent-card span-6">
                <h4>Zone Performance Breakdown</h4>
                <div class="table-responsive mt-3">
                    <table class="table align-middle"><thead><tr><th>Zone</th><th>Agents</th><th>Leads</th><th>Revenue</th></tr></thead><tbody>
                        @forelse($zones as $zone)
                            <tr><td><strong>{{ $zone['zone'] }}</strong></td><td>{{ $zone['agents'] }}</td><td>{{ $zone['leads'] }}</td><td><span class="perf-money">₦{{ number_format($zone['revenue']) }}</span></td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center agent-muted py-4">No zone data yet.</td></tr>
                        @endforelse
                    </tbody></table>
                </div>
            </section>

            <section class="agent-card span-12">
                <h4>Weekly Agent Performance</h4>
                <div class="table-responsive mt-3">
                    <table class="table align-middle"><thead><tr><th>Agent Name</th><th>Zone</th><th>Sales</th><th>Leads</th><th>Conversions</th><th>Score</th></tr></thead><tbody>
                        @forelse($agentRows as $row)
                            <tr><td><strong>{{ $row['agent']->name }}</strong></td><td>{{ $row['zone'] }}</td><td><span class="perf-money">₦{{ number_format($row['sales']) }}</span></td><td>{{ $row['leads'] }}</td><td>{{ $row['converted'] }}</td><td><div class="agent-progress" style="min-width:110px;"><span style="width:{{ $row['performance'] }}%;"></span></div><small>{{ $row['performance'] }}%</small></td></tr>
                        @empty
                            <tr><td colspan="6" class="text-center agent-muted py-4">No agent performance data yet.</td></tr>
                        @endforelse
                    </tbody></table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
