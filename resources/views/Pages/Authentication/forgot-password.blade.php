<?php $page = 'forgot-password'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    :root {
        --spa-surface: rgba(255, 255, 255, 0.95);
        --spa-aside: linear-gradient(145deg, #2348c7 0%, #1b2fb5 38%, #0a148a 100%);
        --spa-primary: #2563eb;
        --spa-text: #0f172a;
        --spa-muted: #64748b;
        --spa-gold: #ffe08a;
    }

    .header, .sidebar, .sidebar-two, .sidebar-three, .two-col-bar, .settings-icon, .footer, .nav-header, .breadcrumb { 
        display: none !important; 
    }

    body, html {
        min-height: 100%;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-y;
        background:
            radial-gradient(circle at top left, rgba(40, 195, 243, 0.28), transparent 24%),
            radial-gradient(circle at bottom right, rgba(29, 109, 255, 0.2), transparent 28%),
            linear-gradient(180deg, #f4f9ff 0%, #e8f2ff 100%);
    }

    .main-wrapper,
    .main-wrapper.login-body,
    .page-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        min-height: 100% !important;
        overflow: visible !important;
    }

    .main-wrapper.login-body {
        width: 100vw !important;
        max-width: 100vw !important;
        min-height: 100vh !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 14px 40px;
        position: relative;
        -webkit-overflow-scrolling: touch;
        width: 100vw;
        max-width: 100vw;
        margin: 0 auto;
        box-sizing: border-box;
    }

    .login-card-custom {
        background: var(--spa-surface);
        border-radius: 24px;
        box-shadow: 0 30px 90px rgba(15, 23, 42, 0.12), 0 10px 24px rgba(37, 99, 235, 0.08);
        overflow: hidden;
        max-width: 980px;
        width: min(980px, calc(100vw - 40px));
        border: 1px solid rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(18px);
        margin: 0 auto;
        flex: 0 1 auto;
    }

    .login-card-custom > .row {
        --bs-gutter-x: 0;
        --bs-gutter-y: 0;
        margin: 0;
    }

    .login-card-custom > .row > [class*="col-"] {
        padding: 0;
    }

    .branding-side {
        background: var(--spa-aside);
        padding: 30px 26px;
        position: relative;
    }

    .branding-side::before {
        content: '';
        position: absolute;
        right: -70px;
        bottom: -90px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0) 72%);
        pointer-events: none;
    }

    .brand-lockup,
    .mobile-brand-lockup {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 18px 40px rgba(7, 27, 77, 0.18);
    }

    .mobile-brand-lockup {
        display: none;
        margin-bottom: 18px;
    }

    .logo-img {
        height: 46px;
        width: auto;
        flex: 0 0 auto;
    }

    .brand-panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .brand-name {
        font-size: 1.02rem;
        font-weight: 900;
        line-height: 1;
        color: #0b2b6d;
    }

    .brand-tagline {
        margin-top: 4px;
        font-size: 0.62rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #2563eb;
        font-weight: 700;
    }

    .branding-title {
        color: #fff;
        font-size: 1.58rem;
        line-height: 1.1;
        font-weight: 800;
        margin: 18px 0 8px;
    }

    .branding-copy {
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.55;
        font-size: 0.84rem;
        max-width: 32ch;
        margin: 0;
    }

    .branding-points {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }

    .branding-point {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 10px 11px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.92);
    }

    .branding-point strong {
        display: block;
        color: #fff;
        font-size: 0.8rem;
        margin-bottom: 2px;
    }

    .form-panel {
        padding: 28px 26px;
        background: #fff;
    }

    .form-control {
        border-radius: 14px;
        padding: 11px 14px;
        border: 1px solid #dbe5f2;
        background: #fcfdfe;
    }

    .form-control:focus {
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.16);
        border-color: var(--spa-primary);
        background: #fff;
    }

    .btn-primary {
        border-radius: 16px;
        background: linear-gradient(145deg, #2348c7 0%, #1b2fb5 38%, #0a148a 100%);
        border: 1px solid rgba(255, 224, 138, 0.18);
        color: var(--spa-gold);
        transition: all 0.3s;
        box-shadow: 0 16px 30px rgba(10, 20, 138, 0.28);
    }

    .btn-primary:hover {
        background: linear-gradient(145deg, #294fd3 0%, #2038c6 42%, #0e1a99 100%);
        color: #fff4c8;
        transform: translateY(-2px);
    }

    .panel-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #eff6ff;
        color: #173b92;
        border: 1px solid #dbeafe;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .panel-kicker::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #173b92;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .form-shell {
        border: 1px solid #e5edf8;
        border-radius: 22px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        padding: 22px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
    }

    .helper-box {
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #173b92;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 18px;
        font-size: 12px;
        line-height: 1.55;
    }

    .helper-box strong {
        color: #0f2f80;
    }

    @media (max-width: 767px) {
        .auth-wrapper {
            padding: 14px;
            align-items: flex-start;
            width: 100%;
        }
        .login-card-custom {
            border-radius: 20px;
            width: min(100%, calc(100vw - 20px));
        }
        .mobile-brand-lockup {
            display: inline-flex;
        }
        .form-panel {
            padding: 22px 18px;
        }
        .logo-img {
            height: 34px;
        }
        .brand-name {
            font-size: 0.98rem;
        }
        .brand-tagline {
            font-size: 0.62rem;
        }
        .form-shell {
            padding: 18px 16px;
            border-radius: 18px;
        }
    }
</style>

<div class="auth-wrapper">
    <div class="login-card-custom">
        <div class="row g-0">

            <div class="col-lg-6 d-none d-lg-block branding-side">
                <div class="h-100 d-flex flex-column justify-content-center text-white">
                    <x-auth-brand-lockup :logo="asset('/assets/img/logos.png')" theme="dark" size="lg" :tagline="'Secure Business Stack'" />
                    <h2 class="branding-title">Recover your access without leaving the blue flow.</h2>
                    <p class="branding-copy">Enter the email attached to your workspace and we will send a secure reset link to continue onboarding or return to your dashboard.</p>
                    <div class="branding-points">
                        <div class="branding-point">
                            <i class="fas fa-envelope-open-text mt-1"></i>
                            <div>
                                <strong>Fast recovery</strong>
                                <span>Reset instructions are sent straight to the registered business email.</span>
                            </div>
                        </div>
                        <div class="branding-point">
                            <i class="fas fa-shield-check mt-1"></i>
                            <div>
                                <strong>Protected workflow</strong>
                                <span>Your account stays inside the same SmartProbook blue authentication experience.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 form-panel">
                <div class="mobile-brand-lockup">
                    <x-auth-brand-lockup :logo="asset('/assets/img/logos.png')" size="md" :tagline="'Secure Business Stack'" />
                </div>
                <span class="panel-kicker">Protected recovery</span>
                <div class="form-shell">
                    <div class="mb-4">
                        <h3 class="fw-bold text-dark mb-2">Password Recovery</h3>
                        <p class="text-muted mb-0">Enter your email to receive a secure reset link.</p>
                    </div>

                    @if (!session('reset_error') && session('reset_success'))
                        <div class="alert alert-success border-0 shadow-sm small py-3 mb-4">
                            <i class="fas fa-check-circle me-2"></i> {{ session('reset_success') }}
                        </div>
                    @endif

                    @if (session('reset_error'))
                        <div class="alert alert-danger border-0 shadow-sm small py-3 mb-4">
                            <i class="fas fa-triangle-exclamation me-2"></i> {{ session('reset_error') }}
                        </div>
                    @endif

                    <div class="helper-box">
                        <strong>Need access again?</strong> We’ll send your recovery link to the email attached to this SmartProbook workspace.
                    </div>

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
</div>
@endsection
