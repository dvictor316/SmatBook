@extends('layout.mainlayout')

@section('title', 'Blog Comments')
@section('style')
<link rel="stylesheet" href="{{ asset('assets/css/blog-admin.css') }}?v={{ filemtime(public_path('assets/css/blog-admin.css')) }}">
@endsection
@section('content')
<div class="page-wrapper"><div class="content container-fluid blog-admin-page">
    <header class="blog-admin-header">
        <div>
            <span class="blog-admin-kicker">Community moderation</span>
            <h1>Blog comments</h1>
            <p>New comments and replies appear immediately. Hide or remove content here.</p>
        </div>
        <div class="blog-admin-actions"><a href="{{ route('super_admin.blog.index') }}" class="btn btn-light"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back to articles</a></div>
    </header>
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    <section class="blog-admin-panel" aria-labelledby="blog-comments-heading">
        <header class="blog-admin-panel-header"><h2 id="blog-comments-heading">Recent conversations</h2></header>
        <div class="blog-admin-table-wrap">
            <table class="table align-middle blog-admin-table">
                <thead><tr><th>Article</th><th>Reader</th><th>Comment</th><th>Status</th><th>Received</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($comments as $comment)
                    <tr>
                        <td><span class="blog-post-title">{{ $comment->post->title }}</span></td>
                        <td>{{ $comment->name }}</td>
                        <td><div class="blog-admin-comment">{{ $comment->body }}</div></td>
                        <td><span class="blog-admin-status {{ $comment->approved ? 'is-published' : 'is-draft' }}">{{ $comment->approved ? 'Visible' : 'Hidden' }}</span></td>
                        <td>{{ $comment->created_at->format('d M Y') }}</td>
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
            </table>
        </div>
    </section>
    @if($comments->hasPages())
        {{ $comments->links('pagination::simple-bootstrap-5') }}
    @endif
</div></div>
@endsection
