@extends('layout.mainlayout')

@section('title', $post->exists ? 'Edit Blog Post' : 'New Blog Post')
@section('style')
<link rel="stylesheet" href="{{ asset('assets/css/blog-admin.css') }}?v={{ filemtime(public_path('assets/css/blog-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-lite.min.css') }}">
@endsection
@section('content')
<div class="page-wrapper"><div class="content container-fluid blog-admin-page">
    <header class="blog-admin-header">
        <div>
            <span class="blog-admin-kicker">Blog studio</span>
            <h1>{{ $post->exists ? 'Edit your article' : 'Create an article' }}</h1>
            <p>Shape a useful story, then choose when it should appear on the public blog.</p>
        </div>
        <div class="blog-admin-actions">
            <a href="{{ route('super_admin.blog.index') }}" class="btn btn-light"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i> All articles</a>
            <a href="#manage-blog-posts" class="btn btn-warning">Manage posts</a>
        </div>
    </header>
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="blog-editor-form" method="POST" enctype="multipart/form-data" action="{{ $post->exists ? route('super_admin.blog.update', $post) : route('super_admin.blog.store') }}">
        @csrf
        @if($post->exists) @method('PUT') @endif
        <div class="blog-editor-grid">
            <div class="blog-editor-main">
                <section class="blog-admin-panel">
                    <header class="blog-admin-panel-header"><h2>Article content</h2></header>
                    <div class="blog-admin-panel-body">
                        <div class="mb-3"><label class="form-label" for="title">Title</label><input class="form-control" id="title" name="title" maxlength="200" value="{{ old('title', $post->title) }}" required></div>
                        <div class="mb-3"><label class="form-label" for="slug">URL slug <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="slug" name="slug" maxlength="200" value="{{ old('slug', $post->slug) }}" pattern="[a-z0-9]+(-[a-z0-9]+)*"><div class="form-text">Use lowercase letters, numbers, and hyphens. Leave blank to generate a slug for a new post. Changing an existing slug changes its link.</div></div>
                        <div class="mb-3"><label class="form-label" for="category">Category</label><input class="form-control" id="category" name="category" list="blog-categories" maxlength="100" value="{{ old('category', $post->category) }}" required><datalist id="blog-categories">@foreach($categories as $category)<option value="{{ $category }}">@endforeach</datalist></div>
                        <div class="mb-3"><label class="form-label" for="summary">Summary</label><textarea class="form-control" id="summary" name="summary" maxlength="500" rows="3" required>{{ old('summary', $post->summary) }}</textarea><div class="form-text">A concise introduction shown on the blog and in search previews.</div></div>
                        <div class="mb-0"><label class="form-label" for="content">Article content</label>
                            <input type="hidden" name="content_format" id="content-format" value="{{ old('content_format', $post->content_format ?? 'text') }}">
                            <textarea class="form-control" id="content" name="content" maxlength="100000" rows="14" required>{{ old('content', $post->content) }}</textarea>
                            <div class="form-text">Use the toolbar to format your article. Add the main image with the cover image field.</div>
                            <div id="blog-editor-error" class="alert alert-warning mt-2" role="alert" hidden></div>
                        </div>
                    </div>
                </section>

                <section class="blog-admin-panel">
                    <header class="blog-admin-panel-header"><h2>Search and social sharing</h2></header>
                    <div class="blog-admin-panel-body">
                        <p class="blog-admin-callout mb-3">Optional metadata helps search engines and social platforms describe your article. The title and summary are used when these fields are blank.</p>
                        <div class="mb-3"><label class="form-label" for="meta-title">Meta title</label><input class="form-control" id="meta-title" name="meta_title" maxlength="200" value="{{ old('meta_title', $post->meta_title) }}"><div class="form-text">Defaults to the article title.</div></div>
                        <div class="mb-3"><label class="form-label" for="meta-description">Meta description</label><textarea class="form-control" id="meta-description" name="meta_description" maxlength="500" rows="3">{{ old('meta_description', $post->meta_description) }}</textarea><div class="form-text">Defaults to the article summary.</div></div>
                        <div><label class="form-label" for="meta-keywords">Meta keywords</label><input class="form-control" id="meta-keywords" name="meta_keywords" maxlength="500" value="{{ old('meta_keywords', $post->meta_keywords) }}"><div class="form-text">Separate keywords with commas.</div></div>
                    </div>
                </section>
            </div>

            <aside class="blog-editor-sidebar">
                <section class="blog-admin-panel">
                    <header class="blog-admin-panel-header"><h2>Publishing</h2></header>
                    <div class="blog-admin-panel-body">
                        <div class="mb-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="draft" @selected(old('status', $post->status ?? 'draft') === 'draft')>Save as draft</option><option value="published" @selected(old('status', $post->status) === 'published')>Publish article</option></select></div>
                        <div class="blog-editor-actions">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1" aria-hidden="true"></i> {{ $post->exists ? 'Save changes' : 'Save article' }}</button>
                            @if($post->exists && $post->status === 'published')
                                <a class="btn btn-outline-secondary" href="{{ route('blog.show', $post->slug) }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1" aria-hidden="true"></i> View live article</a>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="blog-admin-panel">
                    <header class="blog-admin-panel-header"><h2>Cover image</h2></header>
                    <div class="blog-admin-panel-body">
                        <label class="form-label" for="cover">JPG, PNG, or WebP (up to 5 MB)</label>
                        <input class="form-control" id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        @if($post->cover_path)
                            <img src="{{ route('media.public', ['path' => $post->cover_path]) }}" alt="Current cover for {{ $post->title }}" class="blog-cover-preview">
                            <div class="form-check mt-3"><input class="form-check-input" id="remove-cover" type="checkbox" name="remove_cover" value="1" @checked(old('remove_cover'))><label class="form-check-label" for="remove-cover">Remove current cover</label></div>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </form>

    <section id="manage-blog-posts" class="blog-admin-panel mt-4" aria-labelledby="manage-blog-posts-heading">
        <header class="blog-admin-panel-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div><h2 id="manage-blog-posts-heading">Manage articles</h2><span class="text-muted small">Save changes before opening another post.</span></div>
            <a href="{{ route('super_admin.blog.index') }}" class="btn btn-sm btn-outline-secondary">View all articles</a>
        </header>
        @include('SuperAdmin.blog.posts-table')
    </section>
</div></div>
@endsection

@section('script')
<script src="{{ asset('assets/plugins/summernote/summernote-lite.min.js') }}"></script>
<script src="{{ asset('assets/js/blog-editor.js') }}?v={{ filemtime(public_path('assets/js/blog-editor.js')) }}"></script>
@endsection
