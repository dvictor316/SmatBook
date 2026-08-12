@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Hotel Settings</h3>
                <p class="text-muted mb-0">Property and operational configuration</p>
            </div>
            <a href="{{ route('hotel.setup.step1') }}" class="btn btn-outline-primary">Hotel Setup</a>
        </div>
        @if(!$property)
            <div class="alert alert-info">No property configuration found for this branch. Use Hotel Setup to configure your property.</div>
        @else
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item"><span class="nav-link active">Property</span></li>
                        <li class="nav-item"><span class="nav-link">Check-In / Checkout</span></li>
                        <li class="nav-item"><span class="nav-link">Policies</span></li>
                        <li class="nav-item"><span class="nav-link">Taxes & Charges</span></li>
                        <li class="nav-item"><span class="nav-link">Night Audit</span></li>
                    </ul>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Property:</strong><div>{{ $property->name }}</div></div>
                        <div class="col-md-6"><strong>Code:</strong><div>{{ $property->code }}</div></div>
                        <div class="col-md-6"><strong>Address:</strong><div>{{ $property->address ?: 'Not set' }}</div></div>
                        <div class="col-md-6"><strong>Timezone:</strong><div>{{ $property->timezone ?: 'Not set' }}</div></div>
                        <div class="col-md-6"><strong>Default Check-In:</strong><div>{{ $property->default_checkin_time ?: 'Not set' }}</div></div>
                        <div class="col-md-6"><strong>Default Checkout:</strong><div>{{ $property->default_checkout_time ?: 'Not set' }}</div></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
