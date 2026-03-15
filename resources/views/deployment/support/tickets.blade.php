@extends('layout.mainlayout')

@section('page-title', 'Support Tickets')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Support Tickets</h4>
            <p class="text-muted mb-0">Track issues you’ve logged for deployment follow-up and workspace support.</p>
        </div>
        <a href="{{ route('deployment.support.create-ticket') }}" class="btn btn-primary">New Ticket</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @forelse($tickets as $ticket)
                <div class="border rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <a href="{{ route('deployment.support.view-ticket', $ticket->id) }}" class="fw-semibold text-decoration-none">{{ $ticket->description }}</a>
                            <div class="small text-muted mt-1">Ticket #{{ $ticket->id }} · {{ ucfirst(str_replace('_', ' ', $ticket->action ?? 'ticket_created')) }}</div>
                            <div class="small text-muted">{{ optional($ticket->created_at)->diffForHumans() }}</div>
                        </div>
                        <span class="badge bg-info-subtle text-info">Open</span>
                    </div>
                </div>
            @empty
                <div class="py-5 text-center text-muted">No support tickets yet.</div>
            @endforelse

            @if(method_exists($tickets, 'links'))
                <div class="mt-3">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection
