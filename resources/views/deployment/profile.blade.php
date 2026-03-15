
@extends('layout.mainlayout')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <img src="{{ $user->avatar_url }}" class="rounded-circle mb-3" alt="Profile" style="width:120px;height:120px;object-fit:cover;">
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <div class="text-muted small mb-3">{{ $manager->business_name ?? 'Deployment Manager' }}</div>
                    <form action="{{ route('deployment.profile.avatar') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <label class="form-label small text-muted">Update Avatar</label>
                        <input type="file" name="profile_photo" class="form-control mb-2" accept="image/*">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Upload Avatar</button>
                    </form>
                    <a href="{{ route('deployment.settings') }}" class="btn btn-primary btn-sm">Open Settings</a>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Profile Details</h5>
                    <form method="POST" action="{{ route('deployment.profile.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business Name</label>
                            <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $manager->business_name ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $manager->phone ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" rows="3" class="form-control">{{ old('address', $manager->address ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-uppercase text-muted fw-bold mb-2">Managed Clients</div>
                            <h4 class="mb-0">{{ number_format($stats['managed_companies'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-uppercase text-muted fw-bold mb-2">Active Subscriptions</div>
                            <h4 class="mb-0">{{ number_format($stats['active_subscriptions'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-uppercase text-muted fw-bold mb-2">Pending Payments</div>
                            <h4 class="mb-0">{{ number_format($stats['pending_payments'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
