<form class="blog-admin-filter" method="GET" action="{{ route('super_admin.blog.index') }}" role="search">
    <label class="visually-hidden" for="admin-blog-search">Search articles</label>
    <input class="form-control" id="admin-blog-search" type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Search title or summary">
    <label class="visually-hidden" for="admin-blog-status">Filter by status</label>
    <select class="form-select" id="admin-blog-status" name="status">
        <option value="">All statuses</option>
        <option value="published" @selected(request('status') === 'published')>Published</option>
        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
    </select>
    <label class="visually-hidden" for="admin-blog-category">Filter by category</label>
    <select class="form-select" id="admin-blog-category" name="category">
        <option value="">All categories</option>
        @foreach($categories as $category)
            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1" aria-hidden="true"></i> Filter</button>
    <a class="btn btn-outline-secondary" href="{{ route('super_admin.blog.index') }}">Clear</a>
</form>
<div class="blog-admin-table-wrap">
    <table class="table align-middle blog-admin-table">
        <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th>Comments</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($posts as $listedPost)
            <tr>
                <td><span class="blog-post-title">{{ $listedPost->title }}</span></td>
                <td>{{ $listedPost->category }}</td>
                <td><span class="blog-admin-status {{ $listedPost->status === 'published' ? 'is-published' : 'is-draft' }}">{{ $listedPost->status }}</span></td>
                <td>{{ $listedPost->published_at?->format('d M Y') ?? '—' }}</td>
                <td>{{ $listedPost->comments_count }}</td>
                <td><div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.blog.edit', $listedPost) }}">Edit</a>
                    @if($listedPost->status === 'published')<a class="btn btn-sm btn-outline-secondary" href="{{ route('blog.show', $listedPost->slug) }}">View</a>@endif
                    <form method="POST" action="{{ route('super_admin.blog.destroy', $listedPost) }}" onsubmit="return confirm('Delete this post and all its comments?');">
                        @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No blog posts match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($posts->hasPages())
    {{ $posts->links('pagination::simple-bootstrap-5') }}
@endif
