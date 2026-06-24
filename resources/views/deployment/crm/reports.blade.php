@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
    <style>
        .report-money { font-size: clamp(20px, 2vw, 25px) !important; }
        .report-chart { height:140px; display:flex; align-items:end; gap:8px; padding:16px 10px 8px; background:linear-gradient(180deg,#f8fbff,#eef5ff); border:1px solid #dbeafe; border-radius:18px; }
        .report-chart span { flex:1; border-radius:999px 999px 8px 8px; background:linear-gradient(180deg,#0f65c9,#9cc7ff); }
        .report-kpi-strip { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
        .report-kpi-strip .agent-card h3 { font-size:22px !important; }
        @media(max-width:991px){ .report-kpi-strip { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:640px){ .report-kpi-strip { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Advanced Reports & Analytics</h1>
                <p>Deep dive into state performance, free trials, and detailed agent reports.</p>
            </div>
            <a href="{{ route('deployment.crm.overview') }}" class="agent-button soft"><i class="fa-solid fa-chart-pie"></i> Overview</a>
        </div>

        <div class="agent-tabs mb-4" id="reportTabs">
            <button class="active" type="button" data-target="stateAnalytics"><i class="fa-solid fa-chart-bar"></i> State Analytics</button>
            <button type="button" data-target="trialCenter"><i class="fa-solid fa-flask"></i> Free Trial Center</button>
            <button type="button" data-target="detailedReports"><i class="fa-solid fa-file-lines"></i> Detailed Reports</button>
        </div>

        <section id="stateAnalytics" class="report-panel">
            <div class="agent-grid">
                <section class="agent-card span-3 agent-metric agent-tone-green">
                    <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-coins"></i></span>
                    <div class="label">Total State Revenue</div>
                    <div class="value report-money">₦{{ number_format($stats['state_revenue']) }}</div>
                    <small style="color:var(--agent-green);">+{{ $stats['revenue_percent'] }}% of annual target</small>
                </section>
                <section class="agent-card span-3 agent-metric agent-tone-blue">
                    <span class="icon"><i class="fa-solid fa-map"></i></span>
                    <div class="label">Active Zones</div>
                    <div class="value">{{ $zones->count() }}</div>
                </section>
                <section class="agent-card span-3 agent-metric agent-tone-purple">
                    <span class="icon" style="color:var(--agent-purple);background:#f2f0ff;"><i class="fa-solid fa-user-tie"></i></span>
                    <div class="label">Total Agents</div>
                    <div class="value">{{ number_format($stats['total_agents']) }}</div>
                </section>
                <section class="agent-card span-3 agent-metric agent-tone-amber">
                    <span class="icon" style="color:var(--agent-amber);background:#fff8e8;"><i class="fa-solid fa-store"></i></span>
                    <div class="label">New Businesses</div>
                    <div class="value">{{ number_format($stats['new_businesses']) }}</div>
                </section>

                <section class="agent-card span-8">
                    <h4>Zone Performance Breakdown</h4>
                    <div class="table-responsive mt-3">
                        <table class="table align-middle">
                            <thead><tr><th>Zone</th><th>Agents</th><th>Leads</th><th>Revenue (Month)</th></tr></thead>
                            <tbody>
                                @forelse($zones as $zone)
                                    <tr>
                                        <td><strong>{{ $zone['zone'] }}</strong></td>
                                        <td>{{ $zone['agents'] }}</td>
                                        <td>{{ $zone['leads'] }}</td>
                                        <td style="color:var(--agent-green);font-weight:900;">₦{{ number_format($zone['revenue']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center agent-muted py-4">No zone assignments yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="agent-card span-4 agent-tone-purple">
                    <h4>State Mix</h4>
                    <p class="agent-muted">Revenue, lead volume, and active zones at a glance.</p>
                    <div class="agent-donut mx-auto mt-3" style="--value:{{ min(100, (int) $stats['revenue_percent']) }};--color:var(--agent-purple);"><strong>{{ $stats['revenue_percent'] }}%</strong></div>
                    <div class="agent-stat-row"><span>Agents</span><strong>{{ number_format($stats['total_agents']) }}</strong></div>
                    <div class="agent-stat-row"><span>Businesses</span><strong>{{ number_format($stats['total_businesses']) }}</strong></div>
                </section>
                <section class="agent-card span-12">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h4>Monthly State Trend</h4>
                            <p class="agent-muted mb-0">Revenue, active customers, trials, and lead creation in one compact chart.</p>
                        </div>
                        <span class="agent-pill">{{ $stats['revenue_percent'] }}% revenue target</span>
                    </div>
                    <div class="report-chart mt-3">
                        @foreach([24, 36, 29, 48, 42, 56, max(12, min(100, $stats['customer_percent'] + 20)), max(14, min(100, $stats['revenue_percent'] + 24))] as $height)
                            <span style="height:{{ $height }}%;"></span>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>

        <section id="trialCenter" class="report-panel d-none">
            <div class="agent-grid">
                <section class="agent-card span-3 text-center agent-tone-blue"><h3 style="font-size:22px;color:var(--agent-blue);">{{ $stats['free_trials'] }}</h3><strong>Trials Initiated</strong></section>
                <section class="agent-card span-3 text-center agent-tone-purple"><h3 style="font-size:22px;color:var(--agent-purple);">{{ max(0, $stats['free_trials'] - $stats['active_customers']) }}</h3><strong>Active Trials</strong></section>
                <section class="agent-card span-3 text-center agent-tone-amber"><h3 style="font-size:22px;color:var(--agent-amber);">{{ $hotLeads->count() }}</h3><strong>Highly Engaged</strong></section>
                <section class="agent-card span-3 text-center agent-tone-green"><h3 style="font-size:22px;color:var(--agent-green);">{{ $stats['active_customers'] }}</h3><strong>Converted This Month</strong></section>

                <section class="agent-card span-4">
                    <h4>Hot Leads (Likely to Convert)</h4>
                    <p class="agent-muted">Businesses with high engagement scores</p>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Business</th><th>Agent</th><th>Engagement</th></tr></thead>
                            <tbody>
                            @forelse($hotLeads as $lead)
                                <tr><td><strong>{{ $lead->business_name }}</strong></td><td>{{ optional($lead->agent)->name ?? '-' }}</td><td><span class="agent-pill" style="color:var(--agent-amber);">Medium</span></td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center agent-muted py-4">No hot leads yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="agent-card span-4 agent-tone-green">
                    <h4>Agent Trial Performance</h4>
                    @foreach($agentRows->take(8) as $row)
                        @php $rate = $row['leads'] > 0 ? round(($row['converted'] / $row['leads']) * 100) : 0; @endphp
                        <div class="agent-stat-row">
                            <span><span class="agent-initial d-inline-grid me-2" style="width:34px;height:34px;">{{ strtoupper(mb_substr($row['agent']->name ?? 'A', 0, 1)) }}</span>{{ $row['agent']->name }}<br><small>{{ $row['leads'] }} Trials · {{ $row['converted'] }} Converted</small></span>
                            <strong style="color:var(--agent-green);">{{ $rate }}%</strong>
                        </div>
                    @endforeach
                </section>
                <section class="agent-card span-4 agent-tone-amber">
                    <h4>Trial Funnel</h4>
                    <p class="agent-muted">Quick view of trial creation to conversion.</p>
                    <div class="agent-bar-chart mt-3">
                        @foreach([35, 48, 42, 58, 72, max(8, min(100, $stats['active_customers'] * 8))] as $height)
                            <span style="height:{{ $height }}%;"></span>
                        @endforeach
                    </div>
                    <div class="agent-stat-row"><span>Hot Leads</span><strong>{{ $hotLeads->count() }}</strong></div>
                    <div class="agent-stat-row"><span>Conversions</span><strong>{{ $stats['active_customers'] }}</strong></div>
                </section>
            </div>
        </section>

        <section id="detailedReports" class="report-panel d-none">
            <section class="agent-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <h4>Weekly Agent Performance</h4>
                    <button class="agent-button"><i class="fa-solid fa-download"></i> Export</button>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><select class="form-select" style="border-radius:14px;"><option>Weekly</option><option>Monthly</option><option>Quarterly</option></select></div>
                    <div class="col-md-3"><select class="form-select" style="border-radius:14px;"><option>All Agents</option>@foreach($agents as $agent)<option>{{ $agent->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><select class="form-select" style="border-radius:14px;"><option>All Businesses</option></select></div>
                    <div class="col-md-3"><input class="form-control" style="border-radius:14px;" placeholder="Search agent..."></div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Agent Name</th><th>Zone</th><th>Sales</th><th>Leads</th><th>Conversions</th><th>Performance</th></tr></thead>
                        <tbody>
                        @forelse($agentRows as $row)
                            <tr>
                                <td><strong>{{ $row['agent']->name }}</strong></td>
                                <td>{{ $row['zone'] }}</td>
                                <td style="color:var(--agent-red);font-weight:900;">₦{{ number_format($row['sales']) }}</td>
                                <td>{{ $row['leads'] }}</td>
                                <td>{{ $row['converted'] }}</td>
                                <td><div class="agent-progress" style="min-width:90px;"><span style="width:{{ $row['performance'] }}%;"></span></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center agent-muted py-4">No agent performance data yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#reportTabs button').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('#reportTabs button').forEach((item) => item.classList.remove('active'));
            document.querySelectorAll('.report-panel').forEach((panel) => panel.classList.add('d-none'));
            button.classList.add('active');
            document.getElementById(button.dataset.target).classList.remove('d-none');
        });
    });
});
</script>
@endsection
