@extends('layout.mainlayout')

@section('page-title', 'Chat Thread')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Chat with {{ $targetUser->name ?? $targetUser->email }}</h5>
                    <small class="text-muted">{{ $targetUser->email }}</small>
                </div>
                <a href="{{ route('messages.index') }}" class="btn btn-outline-primary">Back to Messages</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body" style="max-height: 420px; overflow-y: auto;">
                @forelse($messages as $msg)
                    @php
                        $isMe = $msg->user_id === auth()->id();
                    @endphp
                    <div class="d-flex {{ $isMe ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                        <div class="p-3 rounded-3 {{ $isMe ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                            <div class="small fw-semibold mb-1">
                                {{ $isMe ? 'You' : ($msg->sender?->name ?? $msg->sender?->email ?? 'User') }}
                                <span class="ms-2 text-muted" style="font-size: 11px;">{{ $msg->created_at?->format('d M, h:i A') }}</span>
                            </div>
                            <div style="white-space: pre-line;">{{ $msg->content }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No messages yet. Start the conversation.</div>
                @endforelse
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="receiver_id" value="{{ $targetUser->id }}">
                    <input type="hidden" name="subject" value="Chat with {{ $targetUser->name ?? $targetUser->email }}">
                    <div class="input-group">
                        <textarea name="content" class="form-control" rows="2" placeholder="Type your message..." required></textarea>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
