@extends('layout.mainlayout')

@section('title', 'Blog Posts')
@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h4 class="fw-bold mb-1">Blog Posts</h4><p class="text-muted mb-0">Manage the public SmartProbook blog.</p></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('super_admin.blog.comments.index') }}">Comments</a>
            <a class="btn btn-primary" href="{{ route('super_admin.blog.create') }}">New post</a>
        </div>
    </div>
    <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th>Comments</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($posts as $post)
            <tr>
                <td>{{ $post->title }}</td><td>{{ $post->category }}</td><td>{{ ucfirst($post->status) }}</td>
                <td>{{ $post->published_at?->format('d M Y') ?? '—' }}</td><td>{{ $post->comments_count }}</td>
                <td><div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.blog.edit', $post) }}">Edit</a>
                    @if($post->status === 'published')<a class="btn btn-sm btn-outline-secondary" href="{{ route('blog.show', $post->slug) }}">View</a>@endif
                    <form method="POST" action="{{ route('super_admin.blog.destroy', $post) }}" onsubmit="return confirm('Delete this post and all its comments?');">
                        @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No blog posts yet.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    {{ $posts->links('pagination::simple-bootstrap-5') }}
</div></div>
@endsection
