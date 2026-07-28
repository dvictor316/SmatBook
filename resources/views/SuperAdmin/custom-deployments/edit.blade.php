@extends('layout.mainlayout')

@section('content')
@php
    $company = $deployment->company;
    $owner = $deployment->user;
    $displayStatus = in_array(strtolower((string) $deployment->status), ['cancelled', 'expired'], true)
        || strtolower((string) ($owner?->status ?? '')) === 'suspended'
        ? 'Suspended'
        : 'Active';
@endphp
<style>
    .custom-edit-wrapper { margin-left: 250px; padding: 1.5rem; background: #f8fafc; min-height: 100vh; }
    body.mini-sidebar .custom-edit-wrapper { margin-left: 80px; }
    @media (max-width: 991px) { .custom-edit-wrapper { margin-left: 0 !important; } }
    .custom-edit-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 14px 34px rgba(15,23,42,.07); }
</style>

<div class="custom-edit-wrapper">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Edit Custom Deployment</h3>
            <p class="text-muted mb-0">Updates only this free/custom subscription and its linked owner/company.</p>
        </div>
        <a href="{{ route('super_admin.custom_deployments.index') }}" class="btn btn-light border fw-bold">Back to List</a>
    </div>

    <div class="custom-edit-card p-4">
        <form method="POST" action="{{ route('super_admin.custom_deployments.update', $deployment->id) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Owner Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $owner?->name ?? $deployment->subscriber_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Owner Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $owner?->email) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Business Name</label>
                    <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $company?->name ?? $company?->company_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Domain Prefix</label>
                    <input type="text" name="domain_prefix" class="form-control" value="{{ old('domain_prefix', $company?->domain_prefix ?? $company?->subdomain ?? $deployment->domain_prefix) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Plan Name</label>
                    <input type="text" name="plan_name" class="form-control" value="{{ old('plan_name', $deployment->plan_name ?? $deployment->plan ?? 'Custom Unlimited') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Billing Cycle</label>
                    <select name="billing_cycle" class="form-select" required>
                        <option value="monthly" @selected(old('billing_cycle', $deployment->billing_cycle) === 'monthly')>Monthly</option>
                        <option value="yearly" @selected(old('billing_cycle', $deployment->billing_cycle) === 'yearly')>Yearly</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">License Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount', $deployment->amount ?? 0) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">User Seats</label>
                    <input type="number" min="1" max="100000" name="user_limit" class="form-control" value="{{ old('user_limit', $deployment->user_limit ?? 100000) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Active" @selected(old('status', $displayStatus) === 'Active')>Active</option>
                        <option value="Suspended" @selected(old('status', $displayStatus) === 'Suspended')>Suspended</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('super_admin.custom_deployments.index') }}" class="btn btn-light border fw-bold">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
