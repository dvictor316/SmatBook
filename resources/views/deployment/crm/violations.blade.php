@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Violations</h1>
                <p>Monitor agent warnings, no-sales periods, complaints, and corrective actions.</p>
            </div>
            <a href="{{ route('deployment.crm.agents') }}" class="agent-button"><i class="fa-solid fa-users"></i> Manage Agents</a>
        </div>

        <div class="agent-grid mb-4">
            <section class="agent-card span-3 agent-metric agent-tone-red"><span class="icon" style="color:var(--agent-red);background:#fff2f6;"><i class="fa-solid fa-triangle-exclamation"></i></span><div class="label">Open Violations</div><div class="value">{{ number_format($violations->where('status', 'open')->count()) }}</div></section>
            <section class="agent-card span-3 agent-metric agent-tone-amber"><span class="icon" style="color:var(--agent-amber);background:#fff7e7;"><i class="fa-solid fa-user-clock"></i></span><div class="label">Underperforming</div><div class="value">{{ number_format($agentRows->filter(fn($row) => $row['sales'] <= 0)->count()) }}</div></section>
            <section class="agent-card span-3 agent-metric agent-tone-blue"><span class="icon"><i class="fa-solid fa-users"></i></span><div class="label">Total Agents</div><div class="value">{{ number_format($stats['total_agents']) }}</div></section>
            <section class="agent-card span-3 agent-metric agent-tone-green"><span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-shield-check"></i></span><div class="label">Healthy Rate</div><div class="value">{{ max(0, 100 - $stats['churn']) }}%</div></section>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-7">
                <h4>Violation Log</h4>
                <div class="agent-stat-list mt-3">
                    @forelse($violations as $violation)
                        <div class="agent-card agent-tone-red" style="box-shadow:none;">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <div>
                                    <h4>{{ $violation->title }}</h4>
                                    <small>{{ $violation->agent_name ?? 'Unknown Agent' }} · {{ $violation->notes ?? 'No notes supplied.' }}</small>
                                </div>
                                <span class="agent-pill">{{ strtoupper($violation->severity ?? 'medium') }}</span>
                            </div>
                            <small class="agent-muted">{{ optional(\Carbon\Carbon::parse($violation->created_at))->format('d M Y') }} · {{ strtoupper($violation->status ?? 'open') }}</small>
                        </div>
                    @empty
                        <div class="text-center py-5"><h3>No violations recorded</h3><p class="agent-muted">Use Manage Agents to add a violation when needed.</p></div>
                    @endforelse
                </div>
            </section>

            <section class="agent-card span-5">
                <h4>Agents To Watch</h4>
                @forelse($agentRows->sortBy('performance')->take(8) as $row)
                    <div class="agent-stat-row">
                        <span><span class="agent-initial d-inline-grid me-2" style="width:34px;height:34px;">{{ strtoupper(mb_substr($row['agent']->name ?? 'A', 0, 1)) }}</span>{{ $row['agent']->name }}</span>
                        <strong style="color:{{ $row['violations'] ? 'var(--agent-red)' : 'var(--agent-amber)' }};">{{ $row['performance'] }}%</strong>
                    </div>
                @empty
                    <p class="agent-muted mb-0">No agents available yet.</p>
                @endforelse
            </section>
        </div>
    </div>
</div>
@endsection
