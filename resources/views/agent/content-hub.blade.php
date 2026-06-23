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
                <p>Reusable pitches, flyers, and demo support materials for agents.</p>
            </div>
        </div>
        <div class="agent-grid">
            @foreach($assets as $asset)
                <section class="agent-card span-4">
                    <div class="agent-brand-mark mb-3" style="background:{{ $asset['accent'] === 'green' ? '#eafff6' : ($asset['accent'] === 'amber' ? '#fff8e8' : '#eef5ff') }};color:{{ $asset['accent'] === 'green' ? 'var(--agent-green)' : ($asset['accent'] === 'amber' ? 'var(--agent-amber)' : 'var(--agent-blue)') }};">
                        <i class="fa-solid fa-photo-film"></i>
                    </div>
                    <h3>{{ $asset['title'] }}</h3>
                    <p class="agent-muted">{{ $asset['type'] }}</p>
                    <a class="agent-button soft" href="#"><i class="fa-solid fa-download"></i> Use Asset</a>
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection
