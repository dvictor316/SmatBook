@extends('layout.landing_nav')

@section('title', 'SmartProbook Blog')
@section('meta_description', 'Read business insights and SmartProbook updates.')

@section('content')
<style>
    .blog-page { max-width: 1150px; margin: 0 auto; padding: 120px 20px 70px; color: #172a4a; }
    .blog-page h1 { font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 12px; }
    .blog-filters { display: flex; flex-wrap: wrap; gap: 10px; margin: 28px 0; }
    .blog-filters input, .blog-filters select, .blog-filters button { padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: white; }
    .blog-filters input { flex: 1 1 220px; min-width: 0; }
    .blog-filters button { background: #123b79; color: white; cursor: pointer; }
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 290px), 1fr)); gap: 20px; }
    .blog-card { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: white; }
    .blog-card img { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; }
    .blog-card-body { padding: 22px; }
    .blog-card h2 { font-size: 1.3rem; margin: 10px 0; overflow-wrap: anywhere; }
    .blog-card p { color: #52637d; overflow-wrap: anywhere; }
    .blog-meta { color: #496183; font-size: .88rem; }
    .blog-pagination { margin-top: 30px; }
</style>
<div class="blog-page">
    <h1>SmartProbook Blog</h1>
    <p>Insights and updates for your business.</p>
    <form class="blog-filters" method="GET" action="{{ route('blog.index') }}">
        <label class="visually-hidden" for="blog-search">Search blog posts</label>
        <input id="blog-search" type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Search posts">
        <label class="visually-hidden" for="blog-category">Category</label>
        <select id="blog-category" name="category">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        <button type="submit">Search</button>
        <a href="{{ route('blog.index') }}">Clear</a>
    </form>
    <div class="blog-grid">
        @forelse($posts as $post)
            <article class="blog-card">
                @if($post->cover_path)
                    <a href="{{ route('blog.show', $post->slug) }}"><img src="{{ route('media.public', ['path' => $post->cover_path]) }}" alt="" loading="lazy"></a>
                @endif
                <div class="blog-card-body">
                    <div class="blog-meta">{{ $post->category }} &middot; {{ $post->published_at->format('d M Y') }}</div>
                    <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                    <p>{{ $post->summary }}</p>
                    <a href="{{ route('blog.show', $post->slug) }}">Read article &rarr;</a>
                </div>
            </article>
        @empty
            <p>No published posts found.</p>
        @endforelse
    </div>
    <div class="blog-pagination">{{ $posts->links('pagination::simple-bootstrap-5') }}</div>
</div>
@endsection
