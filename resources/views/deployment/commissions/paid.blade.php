@extends('layout.mainlayout')

@section('page-title', 'Paid Commissions')

@section('content')
<div class="sb-shell" id="commissions-wrapper">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Paid Commissions</h4>
            <a href="{{ route('deployment.commissions.index') }}" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-arrow-left me-1"></i> All Commissions
            </a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-3">Paid commission records will appear here.</p>
                <div class="mt-3">
                    <a href="{{ route('deployment.dashboard') }}" class="btn btn-primary">Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
