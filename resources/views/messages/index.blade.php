@extends('layout.mainlayout')

@section('page-title', 'Messages')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Messages</h5>
                <a href="{{ route('inbox') }}" class="btn btn-primary">Open Inbox</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Subject</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $message)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $message->subject ?: 'No Subject' }}</div>
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($message->content, 75) }}</small>
                                    </td>
                                    <td>{{ $message->sender?->name ?? 'System' }}</td>
                                    <td>{{ $message->receiver?->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($message->read_at)
                                            <span class="badge bg-success-subtle text-success">Read</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Unread</span>
                                        @endif
                                    </td>
                                    <td>{{ $message->created_at?->format('d M Y, h:i A') }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('messages.show', $message->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">No messages available yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
