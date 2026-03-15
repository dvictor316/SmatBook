@extends('layout.mainlayout')

@section('page-title', ucfirst($category) . ' Help')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ ucfirst($category) }} Help</h4>
            <p class="text-muted mb-0">Articles and working notes for the {{ ucfirst($category) }} part of the deployment workflow.</p>
        </div>
        <a href="{{ route('deployment.help') }}" class="btn btn-light border">Back to Help Center</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @foreach($categoryArticles as $article)
                <div class="border rounded-3 p-3 mb-3">
                    <a href="{{ route('deployment.help.article', $article['slug']) }}" class="fw-semibold text-decoration-none">{{ $article['title'] }}</a>
                    <div class="text-muted small mt-1">{{ $article['summary'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
</div>
@endsection
