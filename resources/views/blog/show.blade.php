@extends('layout.landing_nav')

@section('title', $post->title)
@section('meta_description', $post->summary)

@section('content')
<style>
    .blog-article { max-width: 850px; margin: 0 auto; padding: 120px 20px 70px; color: #172a4a; }
    .blog-article h1 { font-size: clamp(2rem, 5vw, 3rem); margin: 14px 0; overflow-wrap: anywhere; }
    .blog-article .blog-meta { color: #52637d; }
    .blog-article .blog-cover { width: 100%; max-height: 480px; object-fit: cover; border-radius: 14px; margin: 24px 0; }
    .blog-article .blog-content, .blog-article .comment-body { white-space: pre-wrap; overflow-wrap: anywhere; }
    .blog-article .blog-content { line-height: 1.8; margin: 30px 0; }
    .blog-comments { border-top: 1px solid #dbe3ee; padding-top: 28px; }
    .blog-comment { border-bottom: 1px solid #e2e8f0; padding: 16px 0; }
    .blog-comment-form { display: grid; gap: 10px; margin-top: 25px; }
    .blog-comment-form input, .blog-comment-form textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; }
    .blog-comment-form button { justify-self: start; border: 0; border-radius: 8px; background: #123b79; color: white; padding: 11px 18px; cursor: pointer; }
</style>
<article class="blog-article">
    <a href="{{ route('blog.index') }}">&larr; All posts</a>
    <h1>{{ $post->title }}</h1>
    <p class="blog-meta">{{ $post->category }} &middot; {{ $post->published_at->format('d M Y') }}</p>
    <p>{{ $post->summary }}</p>
    @if($post->cover_path)
        <img class="blog-cover" src="{{ route('media.public', ['path' => $post->cover_path]) }}" alt="">
    @endif
    <div class="blog-content">{{ $post->content }}</div>
    <section class="blog-comments">
        <h2>Comments</h2>
        @forelse($comments as $comment)
            <div class="blog-comment">
                <strong>{{ $comment->name }}</strong>
                <small class="blog-meta">{{ $comment->created_at->format('d M Y') }}</small>
                <div class="comment-body">{{ $comment->body }}</div>
            </div>
        @empty
            <p>No comments yet.</p>
        @endforelse
        {{ $comments->links('pagination::simple-bootstrap-5') }}
        @if(session('comment_success'))
            <div class="alert alert-success" role="status">{{ session('comment_success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form class="blog-comment-form" method="POST" action="{{ route('blog.comments.store', $post->slug) }}">
            @csrf
            <h3>Leave a comment</h3>
            <p>Comments are reviewed before appearing publicly.</p>
            <label for="comment-name">Name</label>
            <input id="comment-name" name="name" value="{{ old('name') }}" maxlength="100" required>
            <label for="comment-body">Comment</label>
            <textarea id="comment-body" name="body" rows="5" maxlength="2000" required>{{ old('body') }}</textarea>
            <button type="submit">Submit comment</button>
        </form>
    </section>
</article>
@endsection
