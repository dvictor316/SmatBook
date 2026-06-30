@php
    $page = 'agent-settings';
    $hideSidebar = false;
@endphp

@extends('layout.mainlayout')

@section('content')
    @php
        $user = $user ?? auth()->user();
        $profilePhoto = $profilePhoto ?? ($user?->profile_photo_url ?? asset('assets/img/profiles/avatar-02.jpg'));
        $coverPhoto = $coverPhoto ?? ($user?->cover_photo_url ?? asset('assets/img/profiles/avatar-02.jpg'));
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="page-header">
                        <div class="content-page-header">
                            <h5>Account Settings</h5>
                            <p class="text-muted mb-0">Update your agent credentials, password, and profile photos from one place.</p>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm overflow-hidden">
                                <div class="position-relative">
                                    <img src="{{ $coverPhoto }}" alt="Cover photo" class="w-100" style="height: 160px; object-fit: cover;" onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">
                                    <div class="position-absolute top-100 start-50 translate-middle">
                                        <img src="{{ $profilePhoto }}" alt="Profile photo" class="rounded-circle border border-4 border-white shadow-sm" style="width: 96px; height: 96px; object-fit: cover;" onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">
                                    </div>
                                </div>
                                <div class="card-body pt-5 mt-3 text-center">
                                    <h5 class="mb-1">{{ $user->name }}</h5>
                                    <div class="text-muted small mb-3">{{ $user->email }}</div>
                                    <div class="badge bg-primary-subtle text-primary text-uppercase">{{ ucfirst((string) $user->role) }}</div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-body">
                                    <h6 class="mb-3">Profile Images</h6>
                                    <form action="{{ route('agent.profile.update.images') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                                        @csrf
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" name="profile_photo" class="form-control" accept="image/*">
                                        <button type="submit" class="btn btn-outline-primary btn-sm mt-3">Upload Profile Photo</button>
                                    </form>
                                    <form action="{{ route('agent.profile.update.images') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <label class="form-label">Cover Photo</label>
                                        <input type="file" name="cover_photo" class="form-control" accept="image/*">
                                        <button type="submit" class="btn btn-outline-primary btn-sm mt-3">Upload Cover Photo</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h5 class="mb-1">Personal Details</h5>
                                            <p class="text-muted small mb-0">Change your full name, email address, and phone number.</p>
                                        </div>
                                        <a href="{{ route('agent.profile') }}" class="btn btn-light btn-sm">View Profile</a>
                                    </div>

                                    <form method="POST" action="{{ route('agent.profile.update') }}" class="row g-3">
                                        @csrf
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="+234 701 555 6821">
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control bg-light" value="{{ ucfirst((string) $user->role) }}" readonly>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="mb-1">Password & Security</h5>
                                    <p class="text-muted small mb-3">Use your current password to set a new one.</p>

                                    <form method="POST" action="{{ route('agent.profile.password') }}" class="row g-3">
                                        @csrf
                                        <div class="col-md-4">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                            @error('current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" name="password_confirmation" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-outline-danger">Update Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
