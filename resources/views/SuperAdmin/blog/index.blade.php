@extends('layout.mainlayout')

@section('title', 'Blog Posts')
@section('style')
<link rel="stylesheet" href="{{ asset('assets/css/blog-admin.css') }}?v={{ filemtime(public_path('assets/css/blog-admin.css')) }}">
@endsection
@section('content')
<div class="page-wrapper"><div class="content container-fluid blog-admin-page">
    <header class="blog-admin-header">
        <div>
            <span class="blog-admin-kicker">SmartProbook publishing</span>
            <h1>Blog studio</h1>
            <p>Publish practical insights and keep your community conversation healthy.</p>
        </div>
        <div class="blog-admin-actions">
            <a class="btn btn-light" href="{{ route('super_admin.blog.comments.index') }}"><i class="far fa-comments me-1" aria-hidden="true"></i> Comments</a>
            <a class="btn btn-warning" href="{{ route('super_admin.blog.create') }}"><i class="fas fa-plus me-1" aria-hidden="true"></i> New article</a>
        </div>
    </header>

    <section class="blog-admin-stats" aria-label="Blog overview">
        <div class="blog-admin-stat"><span class="blog-admin-stat-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></span><div><span>Total articles</span><strong>{{ $stats['total'] }}</strong></div></div>
        <div class="blog-admin-stat"><span class="blog-admin-stat-icon"><i class="fas fa-globe" aria-hidden="true"></i></span><div><span>Published</span><strong>{{ $stats['published'] }}</strong></div></div>
        <div class="blog-admin-stat"><span class="blog-admin-stat-icon"><i class="far fa-file-alt" aria-hidden="true"></i></span><div><span>Drafts</span><strong>{{ $stats['drafts'] }}</strong></div></div>
        <div class="blog-admin-stat"><span class="blog-admin-stat-icon"><i class="far fa-comments" aria-hidden="true"></i></span><div><span>Comments</span><strong>{{ $stats['comments'] }}</strong></div></div>
    </section>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <section class="blog-admin-panel" aria-labelledby="all-posts-title">
        <header class="blog-admin-panel-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div><h2 id="all-posts-title">All articles</h2><span class="text-muted small">Search, filter, and manage your publishing queue.</span></div>
            <span class="text-muted small">{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('result', $posts->total()) }}</span>
        </header>
        @include('SuperAdmin.blog.posts-table')
    </section>
</div></div>
@endsection
