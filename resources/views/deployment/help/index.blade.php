@extends('layout.mainlayout')

@section('page-title', 'Help Center')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Help Center</h4>
            <p class="text-muted mb-0">Quick answers for onboarding, renewals, commissions, payments, and support workflows.</p>
        </div>
        <a href="{{ route('deployment.support.create-ticket') }}" class="btn btn-primary">Open Ticket</a>
    </div>

    <div class="row g-4">
        @foreach($articles as $category => $items)
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-uppercase text-muted fw-bold mb-2">{{ ucfirst($category) }}</div>
                        <h5 class="mb-3">{{ ucfirst($category) }} Guides</h5>
                        @foreach($items as $item)
                            <div class="mb-3">
                                <a href="{{ route('deployment.help.article', $item['slug']) }}" class="fw-semibold text-decoration-none">{{ $item['title'] }}</a>
                                <div class="text-muted small">{{ $item['summary'] }}</div>
                            </div>
                        @endforeach
                        <a href="{{ route('deployment.help.category', $category) }}" class="btn btn-sm btn-outline-primary mt-2">Open Category</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
</div>
@endsection
