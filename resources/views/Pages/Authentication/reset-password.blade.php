<?php $page = 'reset-password'; ?>
@extends('layout.mainlayout')
@section('content')
<style>
    :root {
        --spa-surface: rgba(255, 255, 255, 0.95);
        --spa-aside: linear-gradient(145deg, #2348c7 0%, #1b2fb5 38%, #0a148a 100%);
        --spa-primary: #2563eb;
        --spa-gold: #ffe08a;
    }

    .header, .sidebar, .sidebar-two, .sidebar-three, .two-col-bar, .settings-icon, .footer, .nav-header, .breadcrumb {
        display: none !important;
    }

    html, body {
        min-height: 100%;
        overflow-x: hidden !important;
        overflow-y: auto !important;
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

    .auth-container {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        padding: 20px 14px 40px;
    }

    .login-card-custom {
        background: var(--spa-surface);
        border-radius: 24px;
        box-shadow: 0 30px 90px rgba(15, 23, 42, 0.12), 0 10px 24px rgba(37, 99, 235, 0.08);
        overflow: hidden;
        max-width: 980px;
        width: 100%;
        border: 1px solid rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(18px);
        margin: auto 0;
    }

    .branding-side {
        background: var(--spa-aside);
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
        padding: 10px 14px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 18px 40px rgba(7, 27, 77, 0.18);
    }

    .mobile-brand-lockup {
        display: none;
        margin-bottom: 16px;
    }

    .logo-img {
        height: 46px;
        width: auto;
    }

    .brand-panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .brand-name {
        font-size: 1.15rem;
        font-weight: 900;
        line-height: 1;
        color: #0b2b6d;
    }

    .brand-tagline {
        margin-top: 4px;
        font-size: 0.68rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #2563eb;
        font-weight: 700;
    }

    .branding-title {
        color: #fff;
        font-size: 1.9rem;
        line-height: 1.1;
        font-weight: 800;
        margin: 22px 0 10px;
    }

    .branding-copy {
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.7;
        font-size: 0.94rem;
        max-width: 32ch;
        margin: 0;
    }

    .form-panel {
        padding: 36px 32px;
        background: #fff;
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

    .form-control {
        border-radius: 14px;
        padding: 13px 16px;
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
        box-shadow: 0 16px 30px rgba(10, 20, 138, 0.28);
    }

    .btn-primary:hover {
        background: linear-gradient(145deg, #294fd3 0%, #2038c6 42%, #0e1a99 100%);
        color: #fff4c8;
    }

    .pass-shell {
        position: relative;
    }

    .pass-shell .toggle-password,
    .pass-shell .toggle-password-two {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        cursor: pointer;
    }

    @media (max-width: 767px) {
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

<div class="auth-container">
    <div class="login-card-custom">
        <div class="row g-0">
            <div class="col-lg-6 d-none d-lg-block branding-side">
                <div class="h-100 d-flex flex-column justify-content-center p-5 text-white">
                    <div class="brand-lockup">
                        <img src="{{ asset('/assets/img/logos.png') }}" class="logo-img" alt="SmartProbook">
                        <div class="brand-panel">
                            <span class="brand-name">SmartProbook</span>
                            <span class="brand-tagline">Secure Business Stack</span>
                        </div>
                    </div>
                    <h2 class="branding-title">Set a new password and return to the login page.</h2>
                    <p class="branding-copy">Create a stronger password for your SmartProbook workspace, then sign back in from the login page with your updated credentials.</p>
                </div>
            </div>

            <div class="col-lg-6 form-panel">
                <div class="mobile-brand-lockup">
                    <img src="{{ asset('/assets/img/logos.png') }}" class="logo-img" alt="SmartProbook">
                    <div class="brand-panel">
                        <span class="brand-name">SmartProbook</span>
                        <span class="brand-tagline">Secure Business Stack</span>
                    </div>
                </div>

                <span class="panel-kicker">Protected reset</span>
                <div class="form-shell">
                    <div class="mb-4">
                        <h3 class="fw-bold mb-2">Set New Password</h3>
                        <p class="text-muted mb-0">Set a new password and return to the login page.</p>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ $email ?? old('email') }}" required {{ !empty($email) ? 'readonly' : '' }}>
                            @error('email')
                                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">New Password</label>
                            <div class="pass-shell">
                                <input type="password" name="password" class="form-control pass-input @error('password') is-invalid @enderror" placeholder="••••••••" required autofocus>
                                <span class="fas fa-eye-slash toggle-password"></span>
                            </div>
                            @error('password')
                                <span class="text-danger small"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Confirm New Password</label>
                            <div class="pass-shell">
                                <input type="password" name="password_confirmation" class="form-control pass-input-two" placeholder="••••••••" required>
                                <span class="fas fa-eye-slash toggle-password-two"></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                            Update Password & Log In
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleFieldVisibility = (triggerSelector, inputSelector) => {
        const toggle = document.querySelector(triggerSelector);
        const input = document.querySelector(inputSelector);

        if (!toggle || !input) {
            return;
        }

        toggle.addEventListener('click', function () {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            toggle.classList.toggle('fa-eye', reveal);
            toggle.classList.toggle('fa-eye-slash', !reveal);
        });
    };

    toggleFieldVisibility('.toggle-password', '.pass-input');
    toggleFieldVisibility('.toggle-password-two', '.pass-input-two');
});
</script>

@endsection
