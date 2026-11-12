@extends('layout.landing_nav')

@section('title', 'SmartProbook Blog')
@section('meta_description', 'Practical business insights, product guidance, and SmartProbook updates for growing teams.')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/blog.css') }}?v={{ filemtime(public_path('assets/css/blog.css')) }}">
@endsection

@section('content')
<main class="blog-site blog-index">
    <header class="blog-hero">
        <div class="blog-hero-copy">
            <span class="blog-eyebrow">The SmartProbook Journal</span>
            <h1>Ideas for your next stage of growth.</h1>
            <p>Clear advice, practical guides, and product insights to help you run a stronger business.</p>
            <form class="blog-search" method="GET" action="{{ route('blog.index') }}" role="search">
                <label class="visually-hidden" for="blog-search">Search articles</label>
                <input id="blog-search" type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Search business insights">
                <label class="visually-hidden" for="blog-category">Filter by category</label>
                <select id="blog-category" name="category">
                    <option value="">All topics</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <button type="submit"><i class="fas fa-search" aria-hidden="true"></i> Search</button>
            </form>
        </div>
        <aside class="blog-hero-aside">
            <i class="fas fa-lightbulb" aria-hidden="true"></i>
            <strong>Built for business owners</strong>
            <span>Fresh thinking on finance, operations, inventory, and the tools that help your team do more.</span>
        </aside>
    </header>

    <nav class="blog-categories" aria-label="Article categories">
        <a class="blog-category-chip {{ request('category') ? '' : 'is-active' }}" href="{{ route('blog.index') }}">All insights</a>
        @foreach($categories as $category)
            <a class="blog-category-chip {{ request('category') === $category ? 'is-active' : '' }}" href="{{ route('blog.index', array_filter(['category' => $category, 'search' => request('search')])) }}">
                {{ $category }}
            </a>
        @endforeach
    </nav>

    @if($featuredPost)
        <section aria-labelledby="featured-article-title">
            <div class="blog-section-heading">
                <div>
                    <span class="blog-section-eyebrow">Editor's pick</span>
                    <h2 id="featured-article-title">A good place to start</h2>
                </div>
            </div>
            <article class="blog-featured">
                <a class="blog-featured-media" href="{{ route('blog.show', $featuredPost->slug) }}" aria-label="Read {{ $featuredPost->title }}">
                    @if($featuredPost->cover_path)
                        <img src="{{ route('media.public', ['path' => $featuredPost->cover_path]) }}" alt="{{ $featuredPost->title }}" fetchpriority="high">
                    @else
                        <i class="fas fa-book-open" aria-hidden="true"></i>
                    @endif
                </a>
                <div class="blog-featured-body">
                    <span class="blog-section-eyebrow">{{ $featuredPost->category }}</span>
                    <h2><a href="{{ route('blog.show', $featuredPost->slug) }}">{{ $featuredPost->title }}</a></h2>
                    <p>{{ $featuredPost->summary }}</p>
                    <div class="blog-meta-row">
                        <span><i class="far fa-calendar" aria-hidden="true"></i>{{ $featuredPost->published_at->format('d M Y') }}</span>
                        <span><i class="far fa-clock" aria-hidden="true"></i>{{ $featuredPost->reading_time_minutes }} min read</span>
                        <span><i class="far fa-user" aria-hidden="true"></i>{{ $featuredPost->author?->name ?: 'SmartProbook Editorial' }}</span>
                    </div>
                    <a class="blog-primary-link" href="{{ route('blog.show', $featuredPost->slug) }}">Read the featured article <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>
        </section>
    @endif

    <section aria-labelledby="latest-articles-title">
        <div class="blog-section-heading">
            <div>
                <span class="blog-section-eyebrow">{{ request('search') || request('category') ? 'Your results' : 'From the journal' }}</span>
                <h2 id="latest-articles-title">{{ request('search') || request('category') ? 'Articles for you' : 'Latest articles' }}</h2>
            </div>
            @if(request('search') || request('category'))
                <a class="blog-read-link" href="{{ route('blog.index') }}">Clear filters <i class="fas fa-times" aria-hidden="true"></i></a>
            @else
                <p>{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('article', $posts->total()) }}</p>
            @endif
        </div>

        <div class="blog-grid">
            @forelse($posts as $post)
                @include('blog.partials.post-card', ['post' => $post])
            @empty
                <div class="blog-empty-state">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No articles found</strong>
                    <p class="mb-0">Try a different search or browse all insights.</p>
                </div>
            @endforelse
        </div>
        @if($posts->hasPages())
            <div class="blog-pagination">{{ $posts->links('pagination::simple-bootstrap-5') }}</div>
        @endif
    </section>
</main>
@endsection
