@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Upsell Center</h1>
                <p>Dynamic upgrade targets, renewal pressure points, and plan positioning for your customers.</p>
            </div>
            <a href="{{ route('agent.registration.create') }}" class="agent-button">
                <i class="fa-solid fa-building-circle-check"></i> Register Business
            </a>
        </div>

        <div class="agent-grid mb-4">
            <section class="agent-card span-3 agent-metric agent-tone-blue">
                <span class="icon"><i class="fa-solid fa-rocket"></i></span>
                <div class="label">Live Opportunities</div>
                <div class="value">{{ number_format(collect($opportunities)->sum('count')) }}</div>
                <small>Upgrade and renewal conversations ready now</small>
            </section>
            <section class="agent-card span-3 agent-metric agent-tone-green">
                <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-flask"></i></span>
                <div class="label">Free Trials</div>
                <div class="value" style="color:var(--agent-green);">{{ number_format($stats['free_trials']) }}</div>
                <small>Best source for quick paid conversion</small>
            </section>
            <section class="agent-card span-3 agent-metric agent-tone-amber">
                <span class="icon" style="color:var(--agent-amber);background:#fff7e7;"><i class="fa-solid fa-money-bill-trend-up"></i></span>
                <div class="label">Sales Volume</div>
                <div class="value" style="color:var(--agent-amber);">NGN {{ number_format($stats['sales_volume']) }}</div>
                <small>Total paid volume linked to your accounts</small>
            </section>
            <section class="agent-card span-3 agent-metric agent-tone-purple">
                <span class="icon" style="color:var(--agent-purple);background:#f3f0ff;"><i class="fa-solid fa-bullseye"></i></span>
                <div class="label">Awaiting Payment</div>
                <div class="value" style="color:var(--agent-purple);">{{ number_format($pipeline['awaiting_payment']) }}</div>
                <small>Warmest leads to close fast</small>
            </section>
        </div>

        <div class="agent-grid mb-4">
            @foreach($opportunities as $item)
                <section class="agent-card span-3 agent-tone-{{ $item['accent'] }}">
                    <div class="agent-pill mb-3">{{ strtoupper($item['title']) }}</div>
                    <h3 style="font-size:28px;">{{ number_format($item['count']) }}</h3>
                    <p class="agent-muted mb-2">{{ $item['cta'] }}</p>
                    @if(!empty($item['plan']))
                        <small class="agent-muted">Best fit: {{ $item['plan'] }}</small>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="agent-grid mb-4">
            @foreach($planCards as $card)
                <section class="agent-card span-3 {{ !empty($card['featured']) ? 'agent-tone-blue' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <small class="agent-muted text-uppercase fw-bold">{{ $card['label'] }}</small>
                            <h3 class="mt-1">{{ $card['description'] }}</h3>
                        </div>
                        @if(!empty($card['featured']))
                            <span class="agent-pill">Hot</span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <strong style="font-size:24px;color:var(--agent-blue);">NGN {{ number_format($card['from_price']) }}</strong>
                        <small class="agent-muted"> from / month</small>
                    </div>
                    <div class="agent-stat-list">
                        @foreach(array_slice($card['benefits'], 0, 4) as $benefit)
                            <div class="agent-stat-row">
                                <span>{{ $benefit }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="agent-grid">
            <section class="agent-card span-7">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3>Current Customer Mix</h3>
                        <p class="agent-muted mb-0">Your real subscription base grouped by plan tier.</p>
                    </div>
                    <span class="agent-pill">{{ number_format($subscriptions->count()) }} accounts</span>
                </div>
                <div class="agent-stat-list">
                    @forelse($subscriptions->groupBy('tier') as $tier => $rows)
                        <div class="agent-stat-row">
                            <span>{{ ucfirst($tier) }}</span>
                            <strong>{{ number_format($rows->count()) }}</strong>
                        </div>
                    @empty
                        <div class="agent-stat-row">
                            <span>No active subscription history yet.</span>
                            <strong>0</strong>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="agent-card span-5">
                <h3 class="mb-3">Next Best Actions</h3>
                <div class="agent-stat-list">
                    <div class="agent-stat-row">
                        <span>Interested leads</span>
                        <strong>{{ number_format($pipeline['interested']) }}</strong>
                    </div>
                    <div class="agent-stat-row">
                        <span>Negotiating leads</span>
                        <strong>{{ number_format($pipeline['negotiating']) }}</strong>
                    </div>
                    <div class="agent-stat-row">
                        <span>Converted customers</span>
                        <strong>{{ number_format($pipeline['converted']) }}</strong>
                    </div>
                    <div class="agent-stat-row">
                        <span>Retention rate</span>
                        <strong>{{ number_format($stats['retention']) }}%</strong>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('agent.leads', ['status' => 'awaiting_payment']) }}" class="agent-button">
                        <i class="fa-solid fa-phone-volume"></i> Follow Up Warm Leads
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
