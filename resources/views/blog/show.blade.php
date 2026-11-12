@extends('layout.landing_nav')

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@section('meta_keywords', $seoKeywords)
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/blog.css') }}?v={{ filemtime(public_path('assets/css/blog.css')) }}">
@endsection

@section('content')
@php
    $articleUrl = route('blog.show', $post->slug);
    $shareUrl = urlencode($articleUrl);
    $shareTitle = urlencode($post->title);
    $authorName = $post->author?->name ?: 'SmartProbook Editorial';
@endphp
<main class="blog-site blog-article-page">
    <article class="blog-article">
        <a class="blog-breadcrumb" href="{{ route('blog.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> All insights</a>
        <header class="blog-article-header">
            <a class="blog-card-category" href="{{ route('blog.index', ['category' => $post->category]) }}">{{ $post->category }}</a>
            <h1>{{ $post->title }}</h1>
            <p class="blog-article-summary">{{ $post->summary }}</p>
            <div class="blog-meta-row">
                <span><i class="far fa-user" aria-hidden="true"></i>{{ $authorName }}</span>
                <span><i class="far fa-calendar" aria-hidden="true"></i>{{ $post->published_at->format('d M Y') }}</span>
                <span><i class="far fa-clock" aria-hidden="true"></i>{{ $post->reading_time_minutes }} min read</span>
            </div>
        </header>

        @if($post->cover_path)
            <img class="blog-article-cover" src="{{ route('media.public', ['path' => $post->cover_path]) }}" alt="{{ $post->title }}" fetchpriority="high">
        @endif

        <div class="blog-prose {{ $post->content_format === 'html' ? '' : 'blog-content-text' }}">
            @if($post->content_format === 'html')
                {!! app(\App\Support\BlogHtml::class)->clean($post->content) !!}
            @else
                {{ $post->content }}
            @endif
        </div>

        <nav class="blog-share" aria-label="Share this article">
            <strong>Share this article</strong>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f" aria-hidden="true"></i> Facebook</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-linkedin-in" aria-hidden="true"></i> LinkedIn</a>
            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-x-twitter" aria-hidden="true"></i> X</a>
            <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
            <a href="mailto:?subject={{ $shareTitle }}&body={{ $shareUrl }}"><i class="far fa-envelope" aria-hidden="true"></i> Email</a>
        </nav>
    </article>

    @if($relatedPosts->isNotEmpty())
        <section class="blog-related" aria-labelledby="related-articles-title">
            <div class="blog-section-heading">
                <div>
                    <span class="blog-section-eyebrow">Keep exploring</span>
                    <h2 id="related-articles-title">More ideas for you</h2>
                </div>
                <a class="blog-read-link" href="{{ route('blog.index', ['category' => $post->category]) }}">More in {{ $post->category }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="blog-grid">
                @foreach($relatedPosts as $relatedPost)
                    @include('blog.partials.post-card', ['post' => $relatedPost])
                @endforeach
            </div>
        </section>
    @endif

    <section class="blog-comments" aria-labelledby="blog-comments-title">
        <span class="blog-section-eyebrow">Join the conversation</span>
        <h2 id="blog-comments-title">Comments</h2>
        <p class="text-muted">Share a helpful thought or question with the community.</p>

        @forelse($comments as $comment)
            <article class="blog-comment" id="comment-{{ $comment->id }}">
                <div class="blog-comment-heading">
                    <strong>{{ $comment->name }}</strong>
                    <span class="blog-comment-date">{{ $comment->created_at->format('d M Y') }}</span>
                </div>
                <div class="blog-comment-body">{{ $comment->body }}</div>
                @foreach($comment->replies as $reply)
                    <div class="blog-reply">
                        <div class="blog-comment-heading">
                            <strong>{{ $reply->name }}</strong>
                            <span class="blog-comment-date">{{ $reply->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="blog-comment-body">{{ $reply->body }}</div>
                    </div>
                @endforeach
                <details class="blog-reply">
                    <summary>Reply</summary>
                    <form class="blog-comment-form" method="POST" action="{{ route('blog.comments.store', $post->slug) }}">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <input type="hidden" name="page" value="{{ $comments->currentPage() }}">
                        <label for="reply-name-{{ $comment->id }}">Your name</label>
                        <input id="reply-name-{{ $comment->id }}" name="name" value="{{ (string) old('parent_id') === (string) $comment->id ? old('name') : '' }}" maxlength="100" required>
                        <label for="reply-body-{{ $comment->id }}">Your reply</label>
                        <textarea id="reply-body-{{ $comment->id }}" name="body" rows="3" maxlength="2000" required>{{ (string) old('parent_id') === (string) $comment->id ? old('body') : '' }}</textarea>
                        <button type="submit">Post reply</button>
                    </form>
                </details>
            </article>
        @empty
            <p class="blog-empty-state">No comments yet. Start the conversation.</p>
        @endforelse

        @if($comments->hasPages())
            <div class="blog-pagination">{{ $comments->links('pagination::simple-bootstrap-5') }}</div>
        @endif
        @if(session('comment_success'))
            <div class="blog-alert blog-alert-success" role="status">{{ session('comment_success') }}</div>
        @endif
        @if($errors->any())
            <div class="blog-alert blog-alert-error" role="alert">
                <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form class="blog-comment-form" method="POST" action="{{ route('blog.comments.store', $post->slug) }}">
            @csrf
            <h3>Leave a comment</h3>
            <label for="comment-name">Your name</label>
            <input id="comment-name" name="name" value="{{ old('parent_id') ? '' : old('name') }}" maxlength="100" required>
            <label for="comment-body">Your comment</label>
            <textarea id="comment-body" name="body" rows="5" maxlength="2000" required>{{ old('parent_id') ? '' : old('body') }}</textarea>
            <button type="submit">Post comment</button>
        </form>
    </section>
</main>
@endsection
