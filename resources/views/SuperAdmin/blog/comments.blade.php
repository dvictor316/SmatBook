@extends('layout.mainlayout')

@section('title', 'Blog Comments')
@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h4 class="fw-bold mb-1">Blog Comments</h4><p class="text-muted mb-0">New comments and replies appear immediately. You can hide or delete them here.</p></div>
        <a href="{{ route('super_admin.blog.index') }}" class="btn btn-outline-secondary">Back to posts</a>
    </div>
    <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Post</th><th>Reader</th><th>Comment</th><th>Status</th><th>Received</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($comments as $comment)
            <tr>
                <td>{{ $comment->post->title }}</td><td>{{ $comment->name }}</td><td style="white-space:pre-wrap; overflow-wrap:anywhere; min-width:180px">{{ $comment->body }}</td>
                <td>{{ $comment->approved ? 'Visible' : 'Hidden' }}</td><td>{{ $comment->created_at->format('d M Y') }}</td>
                <td><div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('super_admin.blog.comments.update', $comment) }}">
                        @csrf @method('PATCH') <input type="hidden" name="approved" value="{{ $comment->approved ? 0 : 1 }}">
                        <button class="btn btn-sm btn-outline-primary" type="submit">{{ $comment->approved ? 'Hide' : 'Show' }}</button>
                    </form>
                    <form method="POST" action="{{ route('super_admin.blog.comments.destroy', $comment) }}" onsubmit="return confirm('Delete this comment and any replies?');">
                        @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No comments yet.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    {{ $comments->links('pagination::simple-bootstrap-5') }}
</div></div>
@endsection
