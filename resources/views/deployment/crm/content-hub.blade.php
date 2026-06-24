@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Content Hub</h1>
                <p>Playbooks, scripts, policies, and training material for state operations.</p>
            </div>
            <a href="{{ route('deployment.crm.agents') }}" class="agent-button"><i class="fa-solid fa-user-graduate"></i> Coach Agents</a>
        </div>

        <div class="agent-grid mb-4">
            <section class="agent-card span-3 agent-metric agent-tone-blue"><span class="icon"><i class="fa-solid fa-book"></i></span><div class="label">Resources</div><div class="value">{{ $resources->count() }}</div></section>
            <section class="agent-card span-3 agent-metric agent-tone-green"><span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-users"></i></span><div class="label">Agents</div><div class="value">{{ number_format($stats['total_agents']) }}</div></section>
            <section class="agent-card span-3 agent-metric agent-tone-amber"><span class="icon" style="color:var(--agent-amber);background:#fff7e7;"><i class="fa-solid fa-flask"></i></span><div class="label">Trial Guides</div><div class="value">{{ number_format($stats['free_trials']) }}</div></section>
            <section class="agent-card span-3 agent-metric agent-tone-purple"><span class="icon" style="color:var(--agent-purple);background:#f3f0ff;"><i class="fa-solid fa-chart-line"></i></span><div class="label">Conversion</div><div class="value">{{ $stats['retention'] }}%</div></section>
        </div>

        <div class="agent-grid">
            @foreach($resources as $resource)
                <section class="agent-card span-6 agent-tone-blue">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <span class="agent-pill">{{ $resource['tag'] }}</span>
                            <h3 class="mt-3">{{ $resource['title'] }}</h3>
                            <p class="agent-muted mt-2">{{ $resource['body'] }}</p>
                        </div>
                        <span class="agent-initial"><i class="fa-solid fa-file-lines"></i></span>
                    </div>
                    <div class="agent-actions">
                        <button type="button"><i class="fa-solid fa-eye"></i> View</button>
                        <button type="button"><i class="fa-solid fa-share"></i> Share</button>
                        <button type="button"><i class="fa-solid fa-download"></i> Download</button>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection
