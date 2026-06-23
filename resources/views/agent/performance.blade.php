@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Performance & Analytics</h1>
                <p>Track your metrics, earn rewards, and level up.</p>
            </div>
            <a href="{{ route('agent.leads') }}" class="agent-button soft"><i class="fa-solid fa-user-plus"></i> Manage Leads</a>
        </div>

        <div class="agent-tabs mb-4">
            <a class="active" href="{{ route('agent.performance') }}"><i class="fa-solid fa-chart-pie"></i> KPIs & Analytics</a>
            <a href="#"><i class="fa-solid fa-gift"></i> Rewards</a>
            <a href="#"><i class="fa-solid fa-trophy"></i> Rankings</a>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-5">
                <small class="fw-bold text-uppercase">Overall Performance Score</small>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mt-2">
                    <div>
                        <h3 style="font-size:58px;letter-spacing:-.07em;">{{ $stats['performance_score'] }}<span class="agent-muted" style="font-size:22px;">/100</span></h3>
                        <span class="agent-pill" style="color:{{ $stats['performance_score'] >= 60 ? 'var(--agent-green)' : 'var(--agent-red)' }};">
                            {{ $stats['performance_score'] >= 60 ? 'Healthy' : 'Needs Focus' }}
                        </span>
                    </div>
                    <div class="agent-donut" style="--value:{{ $stats['performance_score'] }};--color:var(--agent-navy);"><strong>{{ round($stats['performance_score']) }}%</strong></div>
                </div>
            </section>

            <section class="agent-card span-7">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <h4>Sales Volume Tracker</h4>
                        <small>Revenue trend over selected period</small>
                    </div>
                    <div class="agent-tabs" style="box-shadow:none;">
                        <button>Daily</button><button>Weekly</button><button class="active">Monthly</button>
                    </div>
                </div>
                <div class="d-flex align-items-end gap-3 mt-4" style="height:220px;">
                    @foreach([18, 28, 36, 52, 44, 74, max(8, $stats['target_percent'])] as $height)
                        <div style="flex:1;height:{{ $height }}%;border-radius:16px 16px 4px 4px;background:linear-gradient(180deg,var(--agent-navy),var(--agent-blue));"></div>
                    @endforeach
                </div>
            </section>

            <section class="agent-card span-4">
                <small class="fw-bold text-uppercase">Lead Conversion</small>
                <h3 style="font-size:44px;">{{ $stats['lead_conversion'] }}%</h3>
                <small style="color:var(--agent-red);"><i class="fa-solid fa-arrow-down"></i> Improve follow-up speed</small>
            </section>
            <section class="agent-card span-4">
                <small class="fw-bold text-uppercase">Reviews & Quality</small>
                <h3 style="font-size:44px;color:var(--agent-amber);">0.0 <span style="font-size:20px;">☆☆☆☆☆</span></h3>
                <small>Based on client interactions</small>
            </section>
            <section class="agent-card span-4">
                <small class="fw-bold text-uppercase">Upsell Impact</small>
                <h3 style="font-size:44px;">₦0</h3>
                <small>No upsell data available yet</small>
            </section>

            <section class="agent-card span-6">
                <h4>Customer Mix</h4>
                <div class="d-flex flex-wrap align-items-center gap-4 mt-4">
                    <div class="agent-donut" style="--value:{{ $stats['retention'] }};--color:var(--agent-green);"><strong>{{ $stats['active_customers'] + $stats['inactive_customers'] }}</strong></div>
                    <div>
                        <p><i class="agent-dot"></i>Active ({{ $stats['active_customers'] }})</p>
                        <p><i class="agent-dot" style="background:#dce3ee;"></i>Inactive ({{ $stats['inactive_customers'] }})</p>
                    </div>
                </div>
            </section>

            <section class="agent-card span-6">
                <h4>Activity Intensity</h4>
                <p class="agent-muted">Last 42 days of notes, calls, visits, and system events.</p>
                <div class="agent-heatmap mt-3">
                    @foreach($heatmap as $day)
                        <span class="agent-heat level-{{ $day['level'] }}" title="{{ $day['date'] }} · {{ $day['count'] }} activities"></span>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
