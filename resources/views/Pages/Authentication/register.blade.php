<?php $page = 'register'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    /* 1. Global Reset for Auth Pages */
    .header, .sidebar, .footer { display: none !important; }
    .page-wrapper { margin-left: 0 !important; padding: 0 !important; }

    /* 2. Professional Layout Styling */
    .auth-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 20px;
    }

    .loginbox {
        width: 100%;
        max-width: 950px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        display: flex;
        overflow: hidden;
        min-height: 600px;
    }

    /* Left Branding Panel */
    .login-left-panel {
        flex: 1;
        background: #9c76feff; 
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 50px;
        text-align: center;
    }

    /* Right Form Panel */
    .login-right-panel {
        flex: 1.2;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Component Styling */
    .form-control { border-radius: 8px; padding: 12px; border: 1px solid #e0e0e0; }
    .btn-primary { background-color: #3d5ee1; border: none; border-radius: 8px; padding: 12px; font-weight: 600; transition: all 0.3s ease; }
    .btn-primary:hover { background-color: #2a49c9; transform: translateY(-1px); }
    .btn-google { background-color: #ffffff; color: #444; border: 1px solid #ddd; }
    .btn-facebook { background-color: #3b5998; color: white; border: none; }
    .btn-google:hover { background-color: #f8f9fa; }

    /* Responsive */
    @media (max-width: 991px) {
        .login-left-panel { display: none; }
        .loginbox { max-width: 500px; }
    }
</style>

<div class="auth-container">
    <div class="loginbox">
        <div class="login-left-panel">
            <img class="img-fluid mb-4" src="{{ asset('/assets/img/logo-placeholder.svg') }}" alt="SmartProbook" style="max-height: 70px;">
            <h2 class="fw-bold">Scale Your Business</h2>
            <p class="opacity-75">Join thousands of businesses managing their finances with SmartProbook's intelligent invoicing system.</p>
            <div class="mt-4">
                <span class="badge bg-white text-primary px-3 py-2">14-Day Free Trial</span>
            </div>
        </div>

        <div class="login-right-panel">
            <div class="mb-4">
                <h2 class="fw-bold text-dark mb-1">Create Account</h2>
                <p class="text-muted">Start your journey with us today.</p>
            </div>

            <form action="{{ route('saas-register.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label small fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="John Doe" required autofocus>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="name@company.com" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label small fw-semibold">Profile Photo (Optional)</label>
                        <input type="file" name="profile_photo" class="form-control @error('profile_photo') is-invalid @enderror" accept="image/*">
                        @error('profile_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" title="Use at least 8 characters with letters and numbers." required>
                        <small class="text-muted d-block mt-1">Use letters and numbers (symbol optional).</small>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" minlength="8" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <div class="form-check small">
                            <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" required>
                            <label class="form-check-label text-muted" for="terms">
                                I agree to the <a href="#" class="text-primary">Terms & Conditions</a>
                            </label>
                            @error('terms') <div class="invalid-feedback">You must agree before submitting.</div> @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-4 shadow-sm">Sign Up</button>
            </form>

            <div class="d-flex align-items-center mb-4">
                <hr class="flex-grow-1"> <span class="mx-3 small text-muted text-uppercase">Or register with</span> <hr class="flex-grow-1">
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <a href="{{ route('social.login', 'google') }}" class="btn btn-google w-100 py-2 d-flex align-items-center justify-content-center">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" width="16" class="me-2"> Google
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('social.login', 'facebook') }}" class="btn btn-facebook w-100 py-2 d-flex align-items-center justify-content-center">
                        <i class="fab fa-facebook-f me-2"></i> Facebook
                    </a>
                </div>
            </div>

            <p class="text-center mt-5 small text-muted">
                Already have an account? <a href="{{ route('saas-login') }}" class="text-primary fw-bold text-decoration-none">Log In</a>
            </p>
        </div>
    </div>
</div>
@endsection
