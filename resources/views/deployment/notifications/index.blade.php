@extends('layout.mainlayout')

@section('page-title', 'Notifications')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Deployment Notifications</h4>
            <p class="text-muted mb-0">Track alerts, approvals, payout updates, and workspace events in one place.</p>
        </div>
        <form method="POST" action="{{ route('deployment.notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Mark All as Read</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-info-subtle text-info">Unread: {{ number_format($unreadCount ?? 0) }}</span>
                <span class="text-muted small">{{ $notifications->total() ?? 0 }} notification(s)</span>
            </div>

            @forelse($notifications as $notification)
                <div class="border rounded-3 p-3 mb-3 {{ empty($notification->read_at) ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold mb-1">{{ data_get($notification->data, 'title', 'System Notification') }}</div>
                            <div class="text-muted small mb-2">{{ data_get($notification->data, 'message', 'A new update is available on your deployment workspace.') }}</div>
                            <div class="small text-muted">{{ optional($notification->created_at)->diffForHumans() }}</div>
                        </div>
                        @if(empty($notification->read_at))
                            <form method="POST" action="{{ route('deployment.notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                            </form>
                        @else
                            <span class="badge bg-success-subtle text-success">Read</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-5 text-center text-muted">
                    No notifications yet. New deployment alerts and payout updates will appear here.
                </div>
            @endforelse

            @if(method_exists($notifications, 'links'))
                <div class="mt-3">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection
