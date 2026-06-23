@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
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
                <section class="agent-card span-3 agent-metric">
                    <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-coins"></i></span>
                    <div class="label">Total State Revenue</div>
                    <div class="value">₦{{ number_format($stats['state_revenue']) }}</div>
                    <small style="color:var(--agent-green);">+{{ $stats['revenue_percent'] }}% of annual target</small>
                </section>
                <section class="agent-card span-3 agent-metric">
                    <span class="icon"><i class="fa-solid fa-map"></i></span>
                    <div class="label">Active Zones</div>
                    <div class="value">{{ $zones->count() }}</div>
                </section>
                <section class="agent-card span-3 agent-metric">
                    <span class="icon" style="color:var(--agent-purple);background:#f2f0ff;"><i class="fa-solid fa-user-tie"></i></span>
                    <div class="label">Total Agents</div>
                    <div class="value">{{ number_format($stats['total_agents']) }}</div>
                </section>
                <section class="agent-card span-3 agent-metric">
                    <span class="icon" style="color:var(--agent-amber);background:#fff8e8;"><i class="fa-solid fa-store"></i></span>
                    <div class="label">New Businesses</div>
                    <div class="value">{{ number_format($stats['new_businesses']) }}</div>
                </section>

                <section class="agent-card span-12">
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
            </div>
        </section>

        <section id="trialCenter" class="report-panel d-none">
            <div class="agent-grid">
                <section class="agent-card span-3 text-center" style="background:#eef5ff;"><h3 style="font-size:48px;color:var(--agent-blue);">{{ $stats['free_trials'] }}</h3><strong>Trials Initiated</strong></section>
                <section class="agent-card span-3 text-center" style="background:#f4f1ff;"><h3 style="font-size:48px;color:var(--agent-purple);">{{ max(0, $stats['free_trials'] - $stats['active_customers']) }}</h3><strong>Active Trials</strong></section>
                <section class="agent-card span-3 text-center" style="background:#fff8e8;"><h3 style="font-size:48px;color:var(--agent-amber);">{{ $hotLeads->count() }}</h3><strong>Highly Engaged</strong></section>
                <section class="agent-card span-3 text-center" style="background:#eafff6;"><h3 style="font-size:48px;color:var(--agent-green);">{{ $stats['active_customers'] }}</h3><strong>Converted This Month</strong></section>

                <section class="agent-card span-6">
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

                <section class="agent-card span-6">
                    <h4>Agent Trial Performance</h4>
                    @foreach($agentRows->take(8) as $row)
                        @php $rate = $row['leads'] > 0 ? round(($row['converted'] / $row['leads']) * 100) : 0; @endphp
                        <div class="agent-stat-row">
                            <span><span class="agent-initial d-inline-grid me-2" style="width:34px;height:34px;">{{ strtoupper(mb_substr($row['agent']->name ?? 'A', 0, 1)) }}</span>{{ $row['agent']->name }}<br><small>{{ $row['leads'] }} Trials · {{ $row['converted'] }} Converted</small></span>
                            <strong style="color:var(--agent-green);">{{ $rate }}%</strong>
                        </div>
                    @endforeach
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
