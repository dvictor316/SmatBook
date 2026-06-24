@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Tickets</h1>
                <p>Track support requests from your state workspace and follow urgent issues.</p>
            </div>
            <a href="{{ route('deployment.support.create-ticket') }}" class="agent-button"><i class="fa-solid fa-plus"></i> New Ticket</a>
        </div>

        <div class="agent-grid mb-4">
            <section class="agent-card span-4 agent-metric agent-tone-blue"><span class="icon"><i class="fa-solid fa-ticket"></i></span><div class="label">Open Tickets</div><div class="value">{{ number_format($ticketStats['open']) }}</div></section>
            <section class="agent-card span-4 agent-metric agent-tone-red"><span class="icon" style="color:var(--agent-red);background:#fff2f6;"><i class="fa-solid fa-bolt"></i></span><div class="label">Urgent</div><div class="value">{{ number_format($ticketStats['urgent']) }}</div></section>
            <section class="agent-card span-4 agent-metric agent-tone-green"><span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-circle-check"></i></span><div class="label">Resolved</div><div class="value">{{ number_format($ticketStats['resolved']) }}</div></section>
        </div>

        <section class="agent-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h4>Recent Support Tickets</h4>
                    <p class="agent-muted mb-0">Requests created from your manager portal.</p>
                </div>
                <a href="{{ route('deployment.support.tickets') }}" class="agent-button soft">Open Full Desk</a>
            </div>
            <div class="agent-stat-list">
                @forelse($tickets as $ticket)
                    @php $props = json_decode((string) ($ticket->properties ?? '{}'), true) ?: []; @endphp
                    <div class="agent-card agent-tone-blue" style="box-shadow:none;">
                        <div class="d-flex justify-content-between gap-3 flex-wrap">
                            <div>
                                <h4>{{ $ticket->description ?? 'Support request' }}</h4>
                                <small>{{ $props['message'] ?? 'No message preview available.' }}</small>
                            </div>
                            <span class="agent-pill">{{ strtoupper($props['priority'] ?? 'normal') }}</span>
                        </div>
                        <div class="agent-actions">
                            <a href="{{ route('deployment.support.view-ticket', $ticket->id) }}"><i class="fa-solid fa-eye"></i> View</a>
                            <a href="{{ route('deployment.support.create-ticket') }}"><i class="fa-solid fa-reply"></i> Follow Up</a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <h3>No tickets yet</h3>
                        <p class="agent-muted">New support requests and replies will appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
