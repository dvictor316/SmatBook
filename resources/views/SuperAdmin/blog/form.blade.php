@extends('layout.mainlayout')

@section('title', $post->exists ? 'Edit Blog Post' : 'New Blog Post')
@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="fw-bold mb-0">{{ $post->exists ? 'Edit Blog Post' : 'New Blog Post' }}</h4>
        <a href="{{ route('super_admin.blog.index') }}" class="btn btn-outline-secondary">Back to posts</a>
    </div>
    @if($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="card"><div class="card-body">
        <form method="POST" enctype="multipart/form-data" action="{{ $post->exists ? route('super_admin.blog.update', $post) : route('super_admin.blog.store') }}">
            @csrf
            @if($post->exists) @method('PUT') @endif
            <div class="mb-3"><label class="form-label" for="title">Title</label><input class="form-control" id="title" name="title" maxlength="200" value="{{ old('title', $post->title) }}" required></div>
            <div class="mb-3"><label class="form-label" for="slug">URL slug (optional)</label><input class="form-control" id="slug" name="slug" maxlength="200" value="{{ old('slug', $post->slug) }}" pattern="[a-z0-9]+(-[a-z0-9]+)*"><div class="form-text">Use lowercase letters, numbers and hyphens. Leave blank to generate a slug for a new post. Changing an existing slug changes its link.</div></div>
            <div class="mb-3"><label class="form-label" for="category">Category</label><input class="form-control" id="category" name="category" maxlength="100" value="{{ old('category', $post->category) }}" required></div>
            <div class="mb-3"><label class="form-label" for="summary">Summary</label><textarea class="form-control" id="summary" name="summary" maxlength="500" rows="3" required>{{ old('summary', $post->summary) }}</textarea></div>
            <div class="mb-3"><label class="form-label" for="content">Article content (plain text)</label><textarea class="form-control" id="content" name="content" maxlength="100000" rows="14" required>{{ old('content', $post->content) }}</textarea></div>
            <fieldset class="mb-3"><legend class="fs-5">Search and social sharing (optional)</legend>
                <div class="mb-3"><label class="form-label" for="meta-title">Meta title</label><input class="form-control" id="meta-title" name="meta_title" maxlength="200" value="{{ old('meta_title', $post->meta_title) }}"><div class="form-text">Defaults to the article title.</div></div>
                <div class="mb-3"><label class="form-label" for="meta-description">Meta description</label><textarea class="form-control" id="meta-description" name="meta_description" maxlength="500" rows="3">{{ old('meta_description', $post->meta_description) }}</textarea><div class="form-text">Defaults to the article summary.</div></div>
                <div><label class="form-label" for="meta-keywords">Meta keywords</label><input class="form-control" id="meta-keywords" name="meta_keywords" maxlength="500" value="{{ old('meta_keywords', $post->meta_keywords) }}"><div class="form-text">Separate keywords with commas.</div></div>
            </fieldset>
            <div class="mb-3"><label class="form-label" for="cover">Cover image (optional, JPG, PNG or WebP, up to 5 MB)</label><input class="form-control" id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                @if($post->cover_path)
                    <img src="{{ route('media.public', ['path' => $post->cover_path]) }}" alt="Current cover" class="img-thumbnail mt-2" style="max-width:180px">
                    <div class="form-check mt-2"><input class="form-check-input" id="remove-cover" type="checkbox" name="remove_cover" value="1" @checked(old('remove_cover'))><label class="form-check-label" for="remove-cover">Remove current cover</label></div>
                @endif
            </div>
            <div class="mb-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="draft" @selected(old('status', $post->status ?? 'draft') === 'draft')>Draft</option><option value="published" @selected(old('status', $post->status) === 'published')>Published</option></select></div>
            <button class="btn btn-primary" type="submit">Save post</button>
        </form>
    </div></div>
</div></div>
@endsection
