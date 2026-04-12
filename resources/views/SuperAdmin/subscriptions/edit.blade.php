@extends('layout.mainlayout')

@section('content') @php $page = 'subscription'; @endphp

<style> /* Clean Light Design */ .page-wrapper { background-color: #f8f9fa !important; min-height: 100vh; color: #333333; transition: all 0.2s ease-in-out; }

/* Typography: Dark Fonts for Readability on White/Light backgrounds */
.page-title, .card-title, label, h4, h5, .breadcrumb-item.active { 
    color: #1b1e21 !important; 
    font-weight: 700;
}

.text-muted, .breadcrumb-item a { color: #6c757d !important; }

/* Card Styling: White background with Soft Shadow */
.card {
    background-color: #ffffff !important;
    border: 1px solid #e3e6f0 !important;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
}

.card-header {
    border-bottom: 1px solid #eff2f7 !important;
    background-color: #ffffff;
    padding: 1.25rem;
}

/* Form Inputs: White background with Dark text */
.form-control, .form-select {
    background-color: #ffffff !important;
    border: 1px solid #d1d3e2 !important;
    color: #495057 !important;
    height: 45px;
}

.form-control:focus, .form-select:focus {
    border-color: #ff9b44 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 155, 68, 0.15);
    color: #495057 !important;
}

.form-control:disabled {
    background-color: #eaecf4 !important;
    color: #6e707e !important;
    cursor: not-allowed;
}

/* Input Group Text */
.input-group-text {
    background-color: #f8f9fa !important;
    border: 1px solid #d1d3e2 !important;
    color: #6e707e !important;
}

/* Buttons */
.btn-primary {
    background-color: #ff9b44 !important;
    border-color: #ff9b44 !important;
    color: #fff !important;
    font-weight: 600;
    padding: 10px 25px;
}

.btn-primary:hover {
    background-color: #e68a39 !important;
}

.btn-secondary {
    background-color: #858796 !important;
    border-color: #858796 !important;
    color: #ffffff !important;
}

/* Status Labels */
.info-label {
    color: #ff9b44 !important;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    display: block;
    margin-bottom: 4px;
}
</style>

<div class="page-wrapper"> <div class="content container-fluid">

    {{-- Header Section --}}
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Edit Subscription Details</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('super_admin.subscriptions.index') }}">Subscriptions</a></li>
                    <li class="breadcrumb-item active">Subscriber ID: {{ $subscription->id }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-8">
            <form action="{{ route('super_admin.subscriptions.update', $subscription->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Billing & Account Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- MySQL: subscriber_name --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subscriber / Business Name</label>
                                <input type="text" class="form-control" value="{{ $subscription->subscriber_name ?? 'N/A' }}" disabled>
                            </div>

                            {{-- MySQL: domain_prefix --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subdomain Access</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ $subscription->domain_prefix ?? 'pending' }}" disabled>
                                    @php
                                        $domainSuffix = ltrim(config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')), '.');
                                    @endphp
                                    <span class="input-group-text">.{{ $domainSuffix }}</span>
                                </div>
                            </div>

                            {{-- MySQL: plan_name --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Subscription Plan</label>
                                <input type="text" class="form-control" value="{{ $subscription->plan_name }}" disabled>
                            </div>

                            {{-- MySQL: billing_cycle --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Billing Cycle</label>
                                <input type="text" class="form-control" value="{{ ucfirst($subscription->billing_cycle) }}" disabled>
                            </div>

                            {{-- MySQL: amount --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount (NGN)</label>
                                <input type="text" class="form-control" value="₦{{ number_format($subscription->amount, 2) }}" disabled>
                            </div>

                            <div class="col-12"><hr class="my-3" style="border-top: 1px solid #eff2f7;"></div>

                            {{-- EDITABLE FIELDS --}}
                            
                            {{-- MySQL: status --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" {{ strtolower($subscription->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="Pending" {{ $subscription->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="Awaiting Payment" {{ $subscription->status == 'Awaiting Payment' ? 'selected' : '' }}>Awaiting Payment</option>
                                    <option value="Expired" {{ $subscription->status == 'Expired' ? 'selected' : '' }}>Expired</option>
                                </select>
                            </div>

                            {{-- MySQL: payment_status --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Verification</label>
                                <select name="payment_status" class="form-select">
                                    <option value="paid" {{ $subscription->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="unpaid" {{ $subscription->payment_status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                </select>
                            </div>

                            {{-- MySQL: start_date --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subscription Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $subscription->start_date }}">
                            </div>

                            {{-- MySQL: end_date --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subscription Expiry Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $subscription->end_date }}">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="{{ route('super_admin.subscriptions.index') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Sidebar Meta Information --}}
        <div class="col-xl-4 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transaction Metadata</h5>
                </div>
                <div class="card-body">
                    {{-- MySQL: payment_gateway --}}
                    <div class="mb-4">
                        <span class="info-label">Payment Gateway</span>
                        <h4 class="mb-0">{{ $subscription->payment_gateway ?? 'Manual Entry' }}</h4>
                    </div>

                    {{-- MySQL: payment_reference --}}
                    <div class="mb-4">
                        <span class="info-label">Reference ID</span>
                        <p class="text-dark fw-bold">{{ $subscription->payment_reference ?? 'N/A' }}</p>
                    </div>

                    {{-- MySQL: employee_size --}}
                    <div class="mb-4">
                        <span class="info-label">Employee Capacity</span>
                        <h4 class="mb-0">{{ $subscription->employee_size ?? '0' }} Staff Units</h4>
                    </div>

                    {{-- MySQL: created_at --}}
                    <div class="pt-3 border-top">
                        <span class="info-label">Record Created</span>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($subscription->created_at)->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div> @endsection
