@extends('layout.mainlayout')

@section('page-title', 'Ticket Details')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Ticket #{{ $ticket->id }}</h4>
            <p class="text-muted mb-0">{{ $ticket->description }}</p>
        </div>
        <a href="{{ route('deployment.support.tickets') }}" class="btn btn-light border">Back to Tickets</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Status:</strong> Open</div>
                <div class="col-md-4"><strong>Action:</strong> {{ ucfirst(str_replace('_', ' ', $ticket->action ?? 'ticket_created')) }}</div>
                <div class="col-md-4"><strong>Created:</strong> {{ optional($ticket->created_at)->format('d M Y, h:i A') }}</div>
            </div>
            <hr>
            <div class="text-muted small mb-2">Issue Summary</div>
            <div class="lh-lg">{{ $ticket->description }}</div>
        </div>
    </div>
</div>
</div>
@endsection
