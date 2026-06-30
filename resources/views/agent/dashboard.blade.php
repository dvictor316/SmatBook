@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
@php
    $initials = collect(explode(' ', trim($user->name ?? 'Agent')))->filter()->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $salesTrend = collect($stats['sales_trend'] ?? []);
    $largestSalesMonth = max(1, (float) ($stats['largest_sales_month'] ?? 0));
    $pipelineBreakdown = collect($stats['pipeline_breakdown'] ?? []);
@endphp
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Welcome, {{ strtok($user->name ?? 'Agent', ' ') }}!</h1>
                <p>{{ now()->format('l, F j, Y') }} · Agent Dashboard</p>
            </div>
            <div class="agent-avatar">{{ $initials ?: 'AG' }}</div>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-3 agent-metric agent-tone-blue agent-kpi-card">
                <span class="icon"><i class="fa-solid fa-store"></i></span>
                <div class="label">Total Businesses</div>
                <div class="value">{{ number_format($stats['total_businesses']) }}</div>
                <small style="color:var(--agent-green);"><i class="fa-solid fa-arrow-up"></i> +{{ number_format($stats['new_businesses']) }} this month</small>
            </section>

            <section class="agent-card span-3 agent-metric agent-tone-green agent-kpi-card">
                <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-sack-dollar"></i></span>
                <div class="label">Paid Commissions</div>
                <div class="value">₦{{ number_format($stats['paid_commissions']) }}</div>
                <small>Pending: ₦{{ number_format($stats['pending_commissions']) }}</small>
            </section>

            <section class="agent-card span-3 agent-metric agent-tone-amber agent-kpi-card">
                <span class="icon"><i class="fa-solid fa-user-plus"></i></span>
                <div class="label">Lead Conversion</div>
                <div class="value">{{ $stats['lead_conversion'] }}%</div>
                <small>{{ number_format($stats['converted_leads']) }} converted from {{ number_format($stats['total_leads']) }} leads</small>
            </section>

            <section class="agent-card span-3 agent-metric agent-tone-purple agent-kpi-card">
                <span class="icon" style="color:var(--agent-purple);background:#f3f0ff;"><i class="fa-solid fa-bullseye"></i></span>
                <div class="label">Gap To Target</div>
                <div class="value">₦{{ number_format($stats['remaining_to_target']) }}</div>
                <small>{{ $stats['target_percent'] }}% of ₦{{ number_format($stats['target']) }} achieved</small>
            </section>

            <section class="agent-card span-8 agent-tone-purple">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h4>Monthly Paid Sales Trend</h4>
                        <small class="agent-muted">Last 6 months of paid subscription volume.</small>
                    </div>
                    <span class="agent-pill">Peak ₦{{ number_format($stats['largest_sales_month']) }}</span>
                </div>
                <div class="agent-bar-chart mt-3">
                    @foreach($salesTrend as $point)
                        <div class="agent-chart-col">
                            <span style="height:{{ max(12, (int) round((($point['amount'] ?? 0) / $largestSalesMonth) * 54)) }}px;opacity:{{ (($point['amount'] ?? 0) > 0) ? '1' : '.25' }}"></span>
                            <small>{{ $point['label'] ?? '-' }}</small>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="agent-card span-4">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h4>Customer Health</h4>
                        <small class="agent-muted">Retention vs churn across managed customers.</small>
                    </div>
                    <div class="agent-donut agent-donut-compact" style="--value:{{ $stats['retention'] }};--color:var(--agent-green);"><strong>{{ $stats['retention'] }}%</strong></div>
                </div>
                <div class="agent-stat-list mt-3">
                    <div class="agent-stat-row"><span><i class="agent-dot"></i>Active Customers</span><strong>{{ number_format($stats['active_customers']) }}</strong></div>
                    <div class="agent-stat-row"><span><i class="agent-dot" style="background:var(--agent-red);"></i>Inactive Customers</span><strong>{{ number_format($stats['inactive_customers']) }}</strong></div>
                    <div class="agent-stat-row"><span><i class="agent-dot" style="background:var(--agent-amber);"></i>Churn Rate</span><strong>{{ $stats['churn'] }}%</strong></div>
                </div>
            </section>

            <section class="agent-card span-6 agent-tone-amber">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="agent-brand-mark" style="width:44px;height:44px;font-size:20px;background:#fff8e7;color:#f5a400;">♛</span>
                    <div>
                        <small class="fw-bold text-uppercase">Current Rank</small>
                        <h3 style="font-size:25px;letter-spacing:-.03em;">{{ $stats['rank'] }}</h3>
                    </div>
                </div>
                <span class="agent-pill"><i class="fa-solid fa-circle" style="color:#ffc247;font-size:8px;"></i> Next: {{ $stats['next_rank'] }}</span>
                <div class="agent-card agent-nested-card mt-3">
                    <div class="d-flex justify-content-between align-items-end mb-2 gap-3">
                        <strong>XP Progress<br><span style="font-size:20px;">{{ number_format($stats['xp']) }}</span> / 1000 XP</strong>
                        <strong class="text-end">Sales Volume<br>₦{{ number_format($stats['sales_volume']) }}</strong>
                    </div>
                    <div class="agent-progress"><span style="width:{{ min(100, $stats['xp'] / 10) }}%"></span></div>
                    <div class="agent-stat-row mt-3">
                        <span class="agent-muted">Monthly Target</span>
                        <strong>₦{{ number_format($stats['target']) }}</strong>
                    </div>
                </div>
            </section>

            <section class="agent-card span-4 agent-tone-blue">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h4>Target vs Actual Sales</h4>
                        <small class="agent-muted">Progress toward the assigned monthly target.</small>
                    </div>
                    <div class="agent-donut agent-donut-compact" style="--value:{{ $stats['target_percent'] }};--color:var(--agent-navy);"><strong>{{ $stats['target_percent'] }}%</strong></div>
                </div>
                <div class="agent-stat-list mt-3">
                    <div class="agent-stat-row"><span><i class="agent-dot" style="background:var(--agent-blue);"></i>Target</span><strong>₦{{ number_format($stats['target']) }}</strong></div>
                    <div class="agent-stat-row"><span><i class="agent-dot"></i>Actual Sales</span><strong>₦{{ number_format($stats['sales_volume']) }}</strong></div>
                    <div class="agent-stat-row"><span><i class="agent-dot" style="background:var(--agent-amber);"></i>Free Trials</span><strong>{{ number_format($stats['free_trials']) }}</strong></div>
                </div>
            </section>

            <section class="agent-card span-6">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h4>Lead Pipeline Mix</h4>
                        <small class="agent-muted">Where current prospects are sitting in the funnel.</small>
                    </div>
                    <span class="agent-pill">{{ number_format($stats['total_leads']) }} leads</span>
                </div>
                <div class="agent-status-chart mt-3">
                    @forelse($pipelineBreakdown as $item)
                        <div class="agent-status-row">
                            <div class="agent-status-head">
                                <strong>{{ $item['label'] }}</strong>
                                <span>{{ number_format($item['count']) }}</span>
                            </div>
                            <div class="agent-status-track">
                                <span style="width:{{ max(6, (int) ($item['percent'] ?? 0)) }}%"></span>
                            </div>
                        </div>
                    @empty
                        <p class="agent-muted mb-0">No pipeline data yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="agent-card span-6 agent-tone-purple">
                <h4>Activity Intensity</h4>
                <div class="agent-heatmap mt-3">
                    @foreach([1,0,2,3,1,0,2,2,3,4,2,1,3,0,1,2,3,2,4,1,2] as $level)
                        <span class="agent-heat level-{{ $level }}"></span>
                    @endforeach
                </div>
                <small class="agent-muted d-block mt-3">Calls, visits, notes, and lead updates.</small>
                <div class="agent-stat-list mt-3">
                    <div class="agent-stat-row"><span><i class="agent-dot"></i>Recent Activity</span><strong>{{ number_format($stats['activity_count']) }}</strong></div>
                    <div class="agent-stat-row"><span><i class="agent-dot" style="background:var(--agent-purple);"></i>Hot Leads</span><strong>{{ number_format($stats['hot_leads']) }}</strong></div>
                    <div class="agent-stat-row"><span><i class="agent-dot" style="background:var(--agent-amber);"></i>Performance Score</span><strong>{{ $stats['performance_score'] }}/100</strong></div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
