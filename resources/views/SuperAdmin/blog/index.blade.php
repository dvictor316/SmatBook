@extends('layout.mainlayout')

@section('title', 'Blog Posts')
@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h4 class="fw-bold mb-1">Blog Posts</h4><p class="text-muted mb-0">Manage the public SmartProbook blog.</p></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('super_admin.blog.comments.index') }}">Comments</a>
            <a class="btn btn-primary" href="{{ route('super_admin.blog.create') }}">New post</a>
        </div>
    </div>
    @include('SuperAdmin.blog.posts-table')
</div></div>
@endsection
