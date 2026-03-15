@extends('layout.mainlayout')

@section('page-title', $article['title'])

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="small text-uppercase text-muted fw-bold mb-1">Help Article</div>
            <h4 class="mb-1">{{ $article['title'] }}</h4>
            <p class="text-muted mb-0">{{ $article['summary'] }}</p>
        </div>
        <a href="{{ route('deployment.help') }}" class="btn btn-light border">Back to Help Center</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="lh-lg text-dark">{{ $article['content'] }}</div>
        </div>
    </div>
</div>
</div>
@endsection
