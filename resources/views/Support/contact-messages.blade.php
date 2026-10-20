<?php $page = 'contact-messages'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3 class="mb-2">Contact Messages</h3>
        <p class="text-muted mb-4">Website enquiries are saved here, including messages whose email notification failed.</p>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @forelse($messages as $message)
            <article class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div><strong>{{ $message->details['Name'] ?? 'Website visitor' }}</strong><div class="small text-muted">{{ $message->details['Email'] ?? '' }} · {{ $message->created_at->format('d M Y H:i') }}</div></div>
                    <span class="badge {{ $message->status === 'sent' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $message->status === 'sent' ? 'Submitted to mail service' : 'Email notification '.$message->status }}</span>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-2">{{ $message->details['Company'] ?? '' }} · {{ $message->details['Department'] ?? 'General' }} · To: {{ $message->recipient }}</div>
                    <p style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $message->details['Message'] ?? '' }}</p>
                    @if($message->status === 'failed')<p class="small text-danger">Email delivery failed. The enquiry is saved; check Email Settings and the server mail logs.</p>@endif
                    <form action="{{ route('contact-messages.delete', $message->id) }}" method="POST" onsubmit="return confirm('Delete this saved enquiry?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card"><div class="card-body text-muted">No contact enquiries received yet.</div></div>
        @endforelse
        {{ $messages->links() }}
    </div>
</div>
@endsection
