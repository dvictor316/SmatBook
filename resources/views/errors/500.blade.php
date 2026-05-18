@extends('layout.mainlayout')

@section('style')
<style>
    .report-ui-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: #2563eb;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        transition: background .15s, box-shadow .15s;
        box-shadow: 0 2px 8px rgba(37,99,235,.22);
    }
    .report-ui-btn:hover {
        background: #1d4ed8;
        box-shadow: 0 4px 12px rgba(37,99,235,.3);
        color: #fff;
        text-decoration: none;
    }
</style>
@endsection

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
                    <a href="{{ route('user.dashboard') }}" class="report-ui-btn">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
