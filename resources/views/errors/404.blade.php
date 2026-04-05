@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center">
                <h2 class="mb-3">Page Not Found</h2>
                <p class="text-muted mb-4">
                    The page you are trying to reach is unavailable.
                </p>

                @php
                    $branchId = trim((string) session('active_branch_id', ''));
                    $branchName = trim((string) session('active_branch_name', ''));
                @endphp

                @if ($branchId === '' && $branchName === '')
                    <div class="alert alert-warning d-inline-block text-start">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please select an active branch to continue.
                    </div>
                    <div class="mt-3">
                        <a href="{{ \App\Support\SafeRoute::to('branches.index', '/settings/branches') }}" class="btn btn-primary">
                            Select Branch
                        </a>
                    </div>
                @else
                    <div class="mt-3">
                        <a href="{{ route('user.dashboard') }}" class="btn btn-primary">
                            Back to Dashboard
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
