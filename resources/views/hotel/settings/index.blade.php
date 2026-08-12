@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-pms-shell">
            <div class="hotel-pms-hero">
                <span class="hotel-pms-eyebrow"><i class="fe fe-settings"></i> Property settings</span>
                <h2>Hotel configuration for the active branch.</h2>
                <p>Review property identity, operating times, timezone, and setup status before managing rooms and rates.</p>
                <div class="hotel-pms-actionbar">
                    <a href="{{ route('hotel.setup.step1') }}" class="btn btn-light">Hotel Setup</a>
                    <a href="{{ route('hotel.rooms.index') }}" class="btn btn-outline-light">Rooms</a>
                    <a href="{{ route('hotel.rate_plans.index') }}" class="btn btn-outline-light">Rate Plans</a>
                </div>
            </div>
            @if(!$property)
                <div class="hotel-pms-card"><div class="alert alert-info mb-0">No property configuration found for this branch. Use Hotel Setup to configure your property.</div></div>
            @else
                <div class="hotel-pms-kpis">
                    <div class="hotel-pms-kpi"><small>Property</small><strong>{{ $property->name }}</strong></div>
                    <div class="hotel-pms-kpi"><small>Code</small><strong>{{ $property->code ?: 'N/A' }}</strong></div>
                    <div class="hotel-pms-kpi"><small>Timezone</small><strong>{{ $property->timezone ?: 'N/A' }}</strong></div>
                </div>
                <div class="hotel-pms-card">
                    <h4 class="hotel-pms-card-title">Property Profile</h4>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="hotel-pms-ticket h-100"><small class="hotel-pms-muted">Address</small><div class="fw-semibold">{{ $property->address ?: 'Not set' }}</div></div></div>
                        <div class="col-md-3"><div class="hotel-pms-ticket h-100"><small class="hotel-pms-muted">Default Check-In</small><div class="fw-semibold">{{ $property->default_checkin_time ?: 'Not set' }}</div></div></div>
                        <div class="col-md-3"><div class="hotel-pms-ticket h-100"><small class="hotel-pms-muted">Default Checkout</small><div class="fw-semibold">{{ $property->default_checkout_time ?: 'Not set' }}</div></div></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
