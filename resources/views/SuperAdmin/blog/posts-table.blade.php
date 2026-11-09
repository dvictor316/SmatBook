<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th>Comments</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($posts as $listedPost)
        <tr>
            <td>{{ $listedPost->title }}</td><td>{{ $listedPost->category }}</td><td>{{ ucfirst($listedPost->status) }}</td>
            <td>{{ $listedPost->published_at?->format('d M Y') ?? '—' }}</td><td>{{ $listedPost->comments_count }}</td>
            <td><div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.blog.edit', $listedPost) }}">Edit</a>
                @if($listedPost->status === 'published')<a class="btn btn-sm btn-outline-secondary" href="{{ route('blog.show', $listedPost->slug) }}">View</a>@endif
                <form method="POST" action="{{ route('super_admin.blog.destroy', $listedPost) }}" onsubmit="return confirm('Delete this post and all its comments?');">
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
