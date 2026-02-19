@extends('layout.mainlayout')

@section('page-title', 'Message Details')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Message Details</h5>
                <a href="{{ route('messages.index') }}" class="btn btn-outline-primary">Back to Messages</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">{{ $message->subject ?: 'No Subject' }}</h5>
                    <span class="text-muted">{{ $message->created_at?->format('d M Y, h:i A') }}</span>
                </div>

                <div class="mb-3">
                    <div><strong>From:</strong> {{ $message->sender?->name ?? 'System' }} ({{ $message->sender?->email ?? 'N/A' }})</div>
                    <div><strong>To:</strong> {{ $message->receiver?->name ?? 'N/A' }} ({{ $message->receiver?->email ?? 'N/A' }})</div>
                </div>

                <hr>
                <p class="mb-0" style="white-space: pre-line;">{{ $message->content }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
