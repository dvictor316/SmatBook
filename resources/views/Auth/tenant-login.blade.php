@extends('layout.mainlayout')

@section('page-title', 'Authtenant Login')

@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-4">
                <x-auth-brand-lockup :logo="asset('assets/img/logos.png')" size="md" :tagline="'Business Login'" />
            </div>
            <h4 class="mb-2">Authtenant Login</h4>
            <p class="text-muted mb-3">This module page is now wired and reachable. Replace content with final business UI as needed.</p>
            <div class="small text-muted">View file: <code>Auth/tenant-login.blade.php</code></div>
            <div class="mt-3">
                <a href="{{ url()->previous() }}" class="btn btn-light border">Go Back</a>
                <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
            </div>
        </div>
    </div>
</div>
@endsection
