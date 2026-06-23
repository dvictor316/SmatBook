@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Knowledge Base</h1>
                <p>Quick field guides for better prospecting, demos, trials, and conversion.</p>
            </div>
        </div>
        <div class="agent-grid">
            @foreach($modules as $module)
                <section class="agent-card span-4">
                    <span class="agent-pill mb-3">{{ $module['tag'] }}</span>
                    <h3>{{ $module['title'] }}</h3>
                    <p class="agent-muted mt-2">{{ $module['body'] }}</p>
                    <a class="agent-button soft" href="#"><i class="fa-solid fa-book-open"></i> Open Guide</a>
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection
