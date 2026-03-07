<?php $page = 'forgot-password'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    /* 1. Remove Sidebar and Navbar for this page */
    .header, .sidebar, .sidebar-two, .sidebar-three, .two-col-bar, .settings-icon { 
        display: none !important; 
    }
    
    /* 2. Reset Page Wrapper margins */
    .page-wrapper { 
        margin-left: 0 !important; 
        padding: 0 !important; 
        min-height: 100vh !important;
    }

    /* 3. Force No-Scroll Layout */
    body, html {
        min-height: 100%;
        background-color: #f4f7fe;
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card-custom {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        overflow: hidden;
        max-width: 900px;
        width: 100%;
        border: none;
    }

    .branding-side {
        background: linear-gradient(135deg, #3d5ee1 0%, #1e3bb3 100%);
        padding: 60px;
    }

    .form-control {
        border-radius: 10px;
        padding: 12px 15px;
        border: 1px solid #e1e1e1;
    }

    .form-control:focus {
        box-shadow: 0 0 0 3px rgba(61, 94, 225, 0.1);
        border-color: #3d5ee1;
    }

    .btn-primary {
        border-radius: 10px;
        background: #3d5ee1;
        border: none;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background: #2b46b9;
        transform: translateY(-1px);
    }

    @media (max-width: 767px) {
        .auth-wrapper {
            padding: 14px;
            align-items: flex-start;
        }
        .login-card-custom {
            border-radius: 14px;
            margin-top: 20px;
        }
    }
</style>

<div class="auth-wrapper">
    <div class="login-card-custom">
        <div class="row g-0">
            
            {{-- Left Side: Branding --}}
            <div class="col-lg-6 d-none d-lg-block branding-side">
                <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center text-white">
                    <img src="{{ asset('/assets/img/logo-placeholder.svg') }}" class="img-fluid mb-4" alt="Branding" style="max-height: 220px; filter: brightness(0) invert(1);">
                    <h2 class="fw-bold">Don't Worry!</h2>
                    <p class="opacity-75">It happens to everyone. Enter your details and we'll help you regain access to your SmartProbook workspace in seconds.</p>
                </div>
            </div>

            {{-- Right Side: Form --}}
            <div class="col-lg-6 p-4 p-md-5">
                <div class="text-center mb-5">
                    <img src="{{ asset('/assets/img/logo-placeholder.svg') }}" class="mb-4 d-lg-none" style="max-height: 40px;" alt="Logo">
                    <h3 class="fw-bold text-dark">Password Recovery</h3>
                    <p class="text-muted">Enter email to receive reset link</p>
                </div>

                {{-- Alert Messages --}}
                @if (session('status') || session('success'))
                    <div class="alert alert-success border-0 shadow-sm small py-3 mb-4">
                        <i class="fas fa-check-circle me-2"></i> {{ session('status') ?? session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="far fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 @error('email') is-invalid @enderror" 
                                   placeholder="name@company.com" value="{{ old('email') }}" required autofocus>
                        </div>
                        @error('email')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm mb-4">
                        Send Reset Link
                    </button>

                    <div class="text-center">
                        <a href="{{ route('saas-login') }}" class="text-decoration-none small text-muted">
                            <i class="fas fa-chevron-left me-1 small"></i> Back to <span class="text-primary fw-bold">Sign In</span>
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
