@extends('layout.master')

@section('content')
<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="text-center" style="max-width: 500px;">

        <div class="mb-4">
            <x-auth-brand-lockup :logo="asset('assets/img/logos.png')" size="lg" :tagline="'Business Stack'" />
        </div>

        <div class="mb-4">
            <i class="fas fa-shield-alt fa-4x text-gold" style="color: #c5a059; animation: pulse 2s infinite;"></i>
        </div>

        <h3 class="fw-bold" style="color: #002347;">Institutional Review in Progress</h3>
        <p class="text-muted small">
            Your request for a <strong>Custom SmartProbook Node</strong> has been received. 
            Because this tier involves bespoke institutional features, our management team 
            is currently calculating your activation parameters.
        </p>

        <div class="card bg-light border-0 py-3 mb-4">
            <span class="text-uppercase small fw-bold text-muted">Current Status</span>
            <span class="badge bg-warning text-dark mx-auto mt-2" style="width: fit-content;">AWAITING EXECUTIVE QUOTE</span>
        </div>

        <p class="small text-muted">
            You will receive an email notification once your quote is ready. 
            Once approved, your dashboard will automatically unlock.
        </p>

        <hr>

        <div class="d-flex justify-content-center gap-3">
            <a href="mailto:management@smartprobook.com" class="btn btn-outline-dark btn-sm">Contact Management</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none">Sign Out</button>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }
</style>
@endsection
