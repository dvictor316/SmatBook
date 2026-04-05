@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center">
                <h2 class="mb-3">Server Error</h2>
                <p class="text-muted mb-4">
                    Something went wrong while processing your request.
                </p>

                @if (!empty($errorMessage))
                    <div class="alert alert-danger d-inline-block text-start">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ $errorMessage }}
                    </div>
                @elseif (session('error'))
                    <div class="alert alert-danger d-inline-block text-start">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <div class="mt-3">
                    <a href="{{ route('user.dashboard') }}" class="btn btn-primary">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
