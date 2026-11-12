<article class="blog-card">
    <a class="blog-card-media" href="{{ route('blog.show', $post->slug) }}" aria-label="Read {{ $post->title }}">
        @if($post->cover_path)
            <img src="{{ route('media.public', ['path' => $post->cover_path]) }}" alt="{{ $post->title }}" loading="lazy">
        @else
            <i class="fas fa-book-open" aria-hidden="true"></i>
        @endif
    </a>
    <div class="blog-card-body">
        <a class="blog-card-category" href="{{ route('blog.index', ['category' => $post->category]) }}">{{ $post->category }}</a>
        <h3><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h3>
        <p>{{ $post->summary }}</p>
        <div class="blog-meta-row">
            <span><i class="far fa-calendar" aria-hidden="true"></i>{{ $post->published_at->format('d M Y') }}</span>
            <span><i class="far fa-clock" aria-hidden="true"></i>{{ $post->reading_time_minutes }} min read</span>
        </div>
        <a class="blog-read-link" href="{{ route('blog.show', $post->slug) }}">Read article <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</article>
