@extends('layout.mainlayout')

@section('dashboard_variant', 'overview')

@section('page-title', 'Dashboard')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid py-4">
        @if(isset($subscription) && $subscription)
            @php
                $daysLeft = $subscription->daysRemaining();
                $expiryDate = $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('M d, Y') : 'N/A';
            @endphp
            <div class="alert {{ $subscription->isExpired() ? 'alert-danger' : ($daysLeft <= 7 ? 'alert-warning' : 'alert-info') }} mb-3">
                <strong><i class="fas fa-calendar-alt me-1"></i> Subscription Status:</strong>
                @if($subscription->isExpired())
                    Expired on {{ $expiryDate }}. Please renew now.
                @else
                    Active. Expires on {{ $expiryDate }} ({{ max(0, $daysLeft) }} days left).
                @endif
            </div>
        @endif
        <div class="spb-dashboard-heading">
            <div>
                <h1 class="mb-1">Welcome back{{ auth()->user()?->name ? ', ' . auth()->user()->name : '' }}</h1>
                <p class="text-muted mb-0">{{ now()->format('l, j F Y') }} &middot; Your business workspace</p>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h4 class="mb-2">Ready for your next task?</h4>
                <p class="text-muted mb-3">Use your workspace menu to open the tools available to your role, or return to your overview to review the latest business activity.</p>
                <div class="mt-3">
                    <a href="{{ url()->previous() }}" class="btn btn-light border">Go Back</a>
                    <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
