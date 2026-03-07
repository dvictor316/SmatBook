@extends('layout.mainlayout')

@section('page-title', 'Dashboard')

@section('content')
<div class="container-fluid py-4">
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
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h4 class="mb-2">Dashboard</h4>
            <p class="text-muted mb-3">This module page is now wired and reachable. Replace content with final business UI as needed.</p>
            <div class="small text-muted">View file: <code>dashboard.blade.php</code></div>
            <div class="mt-3">
                <a href="{{ url()->previous() }}" class="btn btn-light border">Go Back</a>
                <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
            </div>
        </div>
    </div>
</div>
@endsection
