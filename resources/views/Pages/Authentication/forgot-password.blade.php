<?php $page = 'forgot-password'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    :root {
        --spa-surface: rgba(255, 255, 255, 0.95);
        --spa-aside: linear-gradient(145deg, #2348c7 0%, #1b2fb5 38%, #0a148a 100%);
        --spa-primary: #2563eb;
        --spa-text: #0f172a;
        --spa-muted: #64748b;
        --spa-gold: #ffe08a;
    }

    html, body {
        min-height: 100%;
        margin: 0;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-y;
    }

    .main-wrapper,
    .main-wrapper.login-body {
        display: block !important;
        width: 100% !important;
        min-height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .page-wrapper, .content, .container-fluid {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .sidebar, .header, .navbar, .header-left, .header-right, .footer,
    .nav-header, .settings-icon, .breadcrumb {
        display: none !important;
        visibility: hidden !important;
    }

    /* Viewport */
    .smat-viewport {
        position: relative;
        top: 0;
        left: 50%;
        width: 100vw;
        min-height: 100vh;
        padding: 20px 15px 40px;
        background:
            radial-gradient(circle at top left, rgba(40, 195, 243, 0.28), transparent 24%),
            radial-gradient(circle at bottom right, rgba(29, 109, 255, 0.2), transparent 28%),
            linear-gradient(180deg, #f4f9ff 0%, #e8f2ff 100%);
        z-index: 900;
        display: grid !important;
        place-items: center !important;
        overflow: visible;
        transform: translateX(-50%);
        -webkit-overflow-scrolling: touch;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Floating bubbles */
    .bubble-bg {
        position: fixed;
        width: 100%;
        height: 100%;
        z-index: -1;
        top: 0;
        left: 0;
        pointer-events: none;
    }

    .bubble {
        position: absolute;
        border-radius: 50%;
        background:
            radial-gradient(circle at 30% 30%, rgba(255,255,255,0.28) 0%, rgba(255,255,255,0) 38%),
            radial-gradient(circle at 70% 65%, rgba(29,109,255,0.16) 0%, rgba(29,109,255,0) 44%),
            radial-gradient(circle, rgba(40,195,243,0.22) 0%, rgba(40,195,243,0) 72%);
        animation: floatBubble 25s infinite ease-in-out;
    }

    @keyframes floatBubble {
        0%, 100% { transform: translate(0,0) scale(1); }
        33%       { transform: translate(30px,-50px) scale(1.05); }
        66%       { transform: translate(-20px,20px) scale(0.95); }
    }

    /* Card */
    .smat-card {
        background: var(--spa-surface);
        width: min(calc(100vw - 40px), 900px) !important;
        max-width: 900px !important;
        border-radius: 24px;
        box-shadow: 0 30px 90px rgba(15,23,42,0.12), 0 10px 24px rgba(37,99,235,0.08);
        display: flex;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.8);
        backdrop-filter: blur(18px);
        margin: 24px auto !important;
        justify-self: center !important;
    }

    /* Left branding panel */
    .smat-aside {
        width: 40%;
        background: var(--spa-aside);
        padding: 28px 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
        color: #fff;
        position: relative;
    }

    .smat-aside::before {
        content: '';
        position: absolute;
        right: -70px;
        bottom: -90px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0) 72%);
        pointer-events: none;
    }

    .smat-aside::after {
        content: '';
        position: absolute;
        inset: 18px 18px auto auto;
        width: 118px;
        height: 118px;
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,0.14);
        opacity: 0.8;
        transform: rotate(10deg);
        pointer-events: none;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        margin-top: 18px;
        background: rgba(255,255,255,0.1);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.32);
        border-radius: 100px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        width: fit-content;
    }

    .status-dot {
        height: 6px;
        width: 6px;
        background: #fff;
        border-radius: 50%;
        margin-right: 8px;
        box-shadow: 0 0 8px rgba(255,255,255,0.45);
    }

    .aside-title {
        margin: 16px 0 10px;
        font-size: 1.55rem;
        line-height: 1.14;
        color: #fff;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .aside-copy {
        color: rgba(255,255,255,0.9);
        font-size: 0.88rem;
        line-height: 1.7;
        margin: 0;
        max-width: 30ch;
    }

    .aside-points { display: grid; gap: 10px; margin-top: 18px; }

    .aside-point {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 13px;
        border-radius: 14px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .aside-point i { color: #fff; margin-top: 3px; font-size: 13px; flex-shrink: 0; }
    .aside-point strong { display: block; color: #fff; font-size: 0.84rem; margin-bottom: 2px; }
    .aside-point span { color: rgba(255,250,240,0.9); font-size: 0.77rem; line-height: 1.55; }

    .side-footer-info {
        font-size: 10px;
        font-weight: 700;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 18px;
    }

    /* Right form panel */
    .smat-main {
        width: 60%;
        padding: 34px 34px 30px;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: #0f172a;
        position: relative;
    }

    .mobile-brand-lockup {
        display: none;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        padding: 10px 12px;
        border-radius: 16px;
        background: #f8fbff;
        border: 1px solid #dbeafe;
        box-shadow: 0 14px 28px rgba(37,99,235,0.08);
    }

    .panel-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        align-self: flex-start;
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
        box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
    }

    .form-shell {
        border: 1px solid #e5edf8;
        border-radius: 22px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        padding: 24px 22px;
        box-shadow: 0 18px 38px rgba(15,23,42,0.05);
    }

    .label-caps {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #173b92;
        margin-bottom: 6px;
        display: block;
        letter-spacing: 0.6px;
    }

    .input-smat {
        padding: 13px 16px;
        border-radius: 14px;
        border: 1px solid #dbe5f2;
        background: #fcfdfe;
        font-size: 13px;
        transition: all 0.2s;
        font-weight: 500;
        width: 100%;
        font-family: inherit;
    }

    .input-smat:focus {
        background: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.16);
        outline: none;
        transform: translateY(-1px);
    }

    .helper-box {
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #173b92;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 20px;
        font-size: 12px;
        line-height: 1.55;
    }

    .helper-box strong { color: #0f2f80; }

    .auth-alert {
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 16px;
        font-size: 13px;
        border: 1px solid transparent;
    }

    .auth-alert-success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
    .auth-alert-error   { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

    .btn-smat-navy {
        background: linear-gradient(180deg, #edf4ff 0%, #dce9ff 100%);
        color: #173b92;
        border: 1px solid #c7d8f8;
        padding: 14px;
        border-radius: 16px;
        width: 100%;
        font-weight: 800;
        font-size: 13px;
        transition: 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 14px 28px rgba(37,99,235,0.12);
        cursor: pointer;
        font-family: inherit;
    }

    .btn-smat-navy:hover {
        background: linear-gradient(180deg, #e4efff 0%, #d1e2ff 100%);
        color: #0f2f80;
        transform: translateY(-2px);
        box-shadow: 0 18px 32px rgba(37,99,235,0.16);
    }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 20px;
        font-size: 12px;
        color: #64748b;
        text-decoration: none;
        font-weight: 700;
    }

    .back-link span { color: #173b92; }
    .back-link:hover span { text-decoration: underline; }

    @media (max-width: 991px) {
        .smat-card {
            width: min(calc(100vw - 28px), 520px) !important;
            max-width: 520px !important;
            flex-direction: column;
            margin: 20px auto !important;
        }
        .smat-aside { display: none; }
        .smat-main { width: 100%; padding: 24px 20px; }
        .mobile-brand-lockup { display: inline-flex; }
    }

    @media (max-width: 480px) {
        .smat-main { padding: 20px 16px; }
        .form-shell { padding: 18px 16px; border-radius: 18px; }
        .btn-smat-navy { font-size: 12px; padding: 13px; }
    }
</style>

<div class="smat-viewport">

    <div class="bubble-bg">
        <div class="bubble" style="width:500px;height:500px;top:-150px;left:-100px;"></div>
        <div class="bubble" style="width:300px;height:300px;bottom:-50px;right:-50px;animation-delay:-5s;"></div>
    </div>

    <div class="smat-card">

        {{-- Left branding panel --}}
        <div class="smat-aside">
            <div>
                <x-auth-brand-lockup :logo="asset('/assets/img/logos.png')" theme="dark" size="lg" :tagline="'Secure Business Stack'" />
                <div class="status-badge"><span class="status-dot"></span> Recovery Mode</div>
                <h2 class="aside-title">Recover your workspace access.</h2>
                <p class="aside-copy">Enter the email attached to your workspace and we will send a secure reset link straight away.</p>
                <div class="aside-points">
                    <div class="aside-point">
                        <i class="fas fa-envelope-open-text"></i>
                        <div>
                            <strong>Fast recovery</strong>
                            <span>Reset instructions are sent straight to your registered business email.</span>
                        </div>
                    </div>
                    <div class="aside-point">
                        <i class="fas fa-shield-halved"></i>
                        <div>
                            <strong>Protected workflow</strong>
                            <span>Your account stays inside the SmartProbook blue authentication experience.</span>
                        </div>
                    </div>
                    <div class="aside-point">
                        <i class="fas fa-key"></i>
                        <div>
                            <strong>One-click reentry</strong>
                            <span>The secure link takes you straight back into your workspace.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="side-footer-info">Recovery Node v1.4.2</div>
        </div>

        {{-- Right form panel --}}
        <div class="smat-main">
            <div class="mobile-brand-lockup">
                <x-auth-brand-lockup :logo="asset('/assets/img/logos.png')" size="md" :tagline="'Secure Business Stack'" />
            </div>

            <span class="panel-kicker">Protected Recovery</span>

            <div class="form-shell">
                <div class="mb-4">
                    <h3 class="fw-bold mb-1" style="color:#0f172a;font-size:1.28rem;">Password Recovery</h3>
                    <p style="color:#64748b;font-size:13px;margin:0;">Enter your email to receive a secure reset link.</p>
                </div>

                @if (!session('reset_error') && session('reset_success'))
                    <div class="auth-alert auth-alert-success">
                        <i class="fas fa-check-circle me-2"></i>{{ session('reset_success') }}
                    </div>
                @endif

                @if (session('reset_error'))
                    <div class="auth-alert auth-alert-error">
                        <i class="fas fa-triangle-exclamation me-2"></i>{{ session('reset_error') }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="auth-alert auth-alert-success">
                        <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="auth-alert auth-alert-error">
                        <i class="fas fa-triangle-exclamation me-2"></i>{{ $errors->first() }}
                    </div>
                @endif

                <div class="helper-box">
                    <strong>Need access again?</strong> We will send your recovery link to the email attached to this SmartProbook workspace.
                </div>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="label-caps">Email Address</label>
                        <input type="email"
                               name="email"
                               class="input-smat @error('email') is-invalid @enderror"
                               placeholder="name@company.com"
                               value="{{ old('email') }}"
                               required
                               autofocus>
                        @error('email')
                            <div class="invalid-feedback d-block mt-1" style="font-size:12px;">
                                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-smat-navy">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                    </button>
                </form>

                <a href="{{ route('saas-login') }}" class="back-link">
                    <i class="fas fa-chevron-left me-1" style="font-size:10px;"></i> Back to <span>Sign In</span>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
