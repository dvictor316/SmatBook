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
            <section class="agent-card span-4 agent-tone-amber">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="agent-brand-mark" style="width:44px;height:44px;font-size:20px;background:#fff8e7;color:#f5a400;">♛</span>
                    <div>
                        <small class="fw-bold text-uppercase">Current Rank</small>
                        <h3 style="font-size:25px;letter-spacing:-.03em;">{{ $stats['rank'] }}</h3>
                    </div>
                </div>
                <span class="agent-pill"><i class="fa-solid fa-circle" style="color:#ffc247;font-size:8px;"></i> Level 1 · Keep going!</span>
                <div class="agent-card mt-3" style="box-shadow:none;background:#fbfdff;">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>XP Progress<br><span style="font-size:20px;">{{ number_format($stats['xp']) }}</span> / 1000 XP</strong>
                        <strong class="text-end">Next Rank<br>{{ $stats['next_rank'] }} <i class="fa-solid fa-angle-right"></i></strong>
                    </div>
                    <div class="agent-progress"><span style="width:{{ min(100, $stats['xp'] / 10) }}%"></span></div>
                    <div class="agent-stat-row mt-3">
                        <span class="agent-muted">Monthly Target:</span>
                        <strong style="color:var(--agent-red);">Sales Volume</strong>
                        <span>₦{{ number_format($stats['sales_volume']) }} / ₦{{ number_format($stats['target']) }}</span>
                    </div>
                </div>
            </section>

            <section class="agent-card span-2 agent-metric agent-tone-blue">
                <span class="icon"><i class="fa-solid fa-store"></i></span>
                <div class="label">Total Businesses</div>
                <div class="value">{{ number_format($stats['total_businesses']) }}</div>
                <small style="color:var(--agent-green);"><i class="fa-solid fa-arrow-up"></i> +{{ number_format($stats['new_businesses']) }} this month</small>
            </section>

            <section class="agent-card span-3 agent-tone-green">
                <h4>Commission Status</h4>
                <div class="agent-stat-list mt-3">
                    <div class="agent-stat-row"><span><i class="agent-dot"></i>Paid Commissions</span><strong>₦{{ number_format($stats['paid_commissions']) }}</strong></div>
                    <div class="agent-stat-row"><span><i class="agent-dot" style="background:var(--agent-amber);"></i>Unpaid / Pending</span><strong>₦{{ number_format($stats['pending_commissions']) }}</strong></div>
                </div>
            </section>

            <section class="agent-card span-3 agent-tone-purple">
                <h4>Gap To Target</h4>
                <div class="value" style="font-size:28px;margin-top:12px;">₦{{ number_format($stats['remaining_to_target']) }}</div>
                <small class="agent-muted">Remaining to close before hitting the monthly target.</small>
                <div class="agent-progress mt-3"><span style="width:{{ $stats['target_percent'] }}%"></span></div>
            </section>

            <section class="agent-card span-6 agent-tone-purple">
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

            <section class="agent-card span-3 agent-metric agent-tone-green">
                <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-user-plus"></i></span>
                <div class="label">New Businesses</div>
                <div class="value">{{ number_format($stats['new_businesses']) }}</div>
                <small>Added this month</small>
            </section>

            <section class="agent-card span-3 agent-metric agent-tone-amber">
                <span class="icon"><i class="fa-solid fa-clock"></i></span>
                <div class="label">Free Trials</div>
                <div class="value">{{ number_format($stats['free_trials']) }}</div>
                <a href="{{ route('agent.leads') }}" class="fw-bold">View details <i class="fa-solid fa-arrow-right"></i></a>
            </section>

            <section class="agent-card span-3 agent-tone-blue">
                <small class="fw-bold text-uppercase">Lead Conversion Rate</small>
                <div class="d-flex justify-content-between align-items-end">
                    <h3 style="font-size:26px;">{{ $stats['lead_conversion'] }}%</h3>
                    <div class="agent-bar-chart"><span style="height:22px;opacity:.25"></span><span style="height:30px;opacity:.45"></span><span style="height:42px;opacity:.7"></span><span style="height:54px"></span></div>
                </div>
                <small>Leads to paid customers</small>
            </section>
            <section class="agent-card span-3 agent-tone-green">
                <small class="fw-bold text-uppercase">Customer Retention Rate</small>
                <h3 style="font-size:26px;color:var(--agent-green);">{{ $stats['retention'] }}%</h3>
                <small>Active customers over total customers</small>
            </section>
            <section class="agent-card span-3 agent-tone-red">
                <small class="fw-bold text-uppercase">Churn Rate</small>
                <h3 style="font-size:26px;color:var(--agent-red);">{{ $stats['churn'] }}%</h3>
                <small>Target &lt; 5%</small>
            </section>

            <section class="agent-card span-4">
                <h4>Active vs Inactive</h4>
                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                    <div class="agent-donut" style="--value:{{ $stats['retention'] }};--color:var(--agent-green);"><strong>{{ $stats['active_customers'] + $stats['inactive_customers'] }}</strong></div>
                    <div>
                        <p><i class="agent-dot"></i>Active <strong>{{ $stats['active_customers'] }} ({{ $stats['retention'] }}%)</strong></p>
                        <p><i class="agent-dot" style="background:var(--agent-red);"></i>Inactive <strong>{{ $stats['inactive_customers'] }} ({{ $stats['churn'] }}%)</strong></p>
                    </div>
                </div>
            </section>

            <section class="agent-card span-4">
                <h4>Target vs. Actual Sales</h4>
                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                    <div class="agent-donut" style="--value:{{ $stats['target_percent'] }};--color:var(--agent-navy);"><strong>{{ $stats['target_percent'] }}%</strong></div>
                    <div>
                        <small>Target Sales</small>
                        <h3>₦{{ number_format($stats['target']) }}</h3>
                        <small>Actual Sales</small>
                        <h3 style="color:var(--agent-green);">₦{{ number_format($stats['sales_volume']) }}</h3>
                    </div>
                </div>
            </section>

            <section class="agent-card span-4 agent-tone-purple">
                <h4>Activity Intensity</h4>
                <div class="agent-heatmap mt-3">
                    @foreach([1,0,2,3,1,0,2,2,3,4,2,1,3,0,1,2,3,2,4,1,2] as $level)
                        <span class="agent-heat level-{{ $level }}"></span>
                    @endforeach
                </div>
                <small class="agent-muted d-block mt-3">Calls, visits, notes, and lead updates.</small>
            </section>

            <section class="agent-card span-8">
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
        </div>
    </div>
</div>
@endsection
