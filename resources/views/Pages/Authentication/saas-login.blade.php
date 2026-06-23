@php
    $page = 'saas-login';
@endphp

@extends('layout.mainlayout')

@section('content')
@php
    $currentCompany = $company ?? \App\Models\Company::first();
    $clientLogo = asset('/assets/img/saas-login-smat15.png');

    if ($currentCompany && !empty($currentCompany->logo)) {
        $clientLogo = asset('storage/' . $currentCompany->logo);
    }

    $persistedPlan = strtolower((string) request('plan', session('selected_plan', '')));
    $persistedCycle = request('billing_cycle', request('cycle', session('selected_cycle', session('billing_cycle', 'monthly'))));
    $googleAuthUrl = route('social.login', [
        'provider' => 'google',
        'intent' => 'login',
        'plan' => $persistedPlan,
        'cycle' => strtolower((string) $persistedCycle),
    ]);
    $facebookAuthUrl = route('social.login', [
        'provider' => 'facebook',
        'intent' => 'login',
        'plan' => $persistedPlan,
        'cycle' => strtolower((string) $persistedCycle),
    ]);
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap');
    :root {
        --spa-bg: #fbfcff;
        --spa-surface: rgba(255, 255, 255, 0.99);
        --spa-aside: linear-gradient(145deg, #061a44 0%, #0f3a8a 58%, #2563eb 100%);
        --spa-border: #d8e0ec;
        --spa-primary: #0f3a8a;
        --spa-primary-dark: #061a44;
        --spa-text: #071b3f;
        --spa-muted: #475569;
        --spa-gold: #d7a928;
    }

    html, body {
        min-height: 100%;
        margin: 0;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-y;
    }

    /* 1. VIEWPORT & CENTERING FIX */
    .smat-viewport {
        position: relative;
        top: 0;
        left: 50%;
        width: 100vw;
        min-height: 100vh;
        padding: 20px 15px 40px;
        background:
            radial-gradient(circle at top left, rgba(215, 169, 40, 0.12), transparent 24%),
            radial-gradient(circle at bottom right, rgba(15, 58, 138, 0.07), transparent 30%),
            linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        z-index: 900;
        display: grid !important;
        place-items: center !important;
        overflow: visible;
        transform: translateX(-50%);
        -webkit-overflow-scrolling: touch;
        font-family: 'Manrope', sans-serif;
        font-optical-sizing: auto;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    /* Hard reset wrapper overrides to prevent theme conflict */
    .main-wrapper,
    .main-wrapper.login-body {
        display: block !important;
        width: 100% !important;
        min-height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .page-wrapper,
    .content,
    .container-fluid {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Hide standard layout noise */
    .sidebar, .header, .navbar, .header-left, .header-right, .footer, .nav-header, .settings-icon, .breadcrumb { 
        display: none !important; 
        visibility: hidden !important;
    }

    /* 2. SUBTLE MINIMALIST BUBBLES */
    .bubble-bg {
        position: fixed; /* Fixed so they don't scroll */
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
            radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.28) 0%, rgba(255, 255, 255, 0) 38%),
            radial-gradient(circle at 70% 65%, rgba(37, 99, 235, 0.16) 0%, rgba(37, 99, 235, 0) 44%),
            radial-gradient(circle, rgba(215, 169, 40, 0.18) 0%, rgba(215, 169, 40, 0) 72%);
        animation: floatBubble 25s infinite ease-in-out;
    }

    @keyframes floatBubble {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.05); }
        66% { transform: translate(-20px, 20px) scale(0.95); }
    }

    /* 3. COMPACT PROFESSIONAL CARD */
    .smat-card {
        background: var(--spa-surface);
        width: min(calc(100vw - 40px), 900px) !important;
        max-width: 900px !important;
        min-height: 0;
        height: auto;
        border-radius: 24px;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.10), 0 8px 22px rgba(15, 58, 138, 0.05);
        display: flex;
        overflow: hidden;
        border: 1px solid rgba(216, 224, 236, 0.95);

        /* KEY FIX: This centers it vertically but allows scrolling if needed */
        margin: 24px auto !important;
        backdrop-filter: blur(10px);
        justify-self: center !important;
    }

    /* Side Panel (Branding) */
    .smat-aside {
        width: 38%;
        background: var(--spa-aside);
        padding: 24px 22px;
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
        background: radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0) 72%);
        pointer-events: none;
    }
    .smat-aside::after {
        content: '';
        position: absolute;
        inset: 18px 18px auto auto;
        width: 118px;
        height: 118px;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        opacity: 0.8;
        transform: rotate(10deg);
        pointer-events: none;
    }

    .logo-img { height: 52px; width: auto; flex: 0 0 auto; filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.18)); }
    .brand-lockup {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 6px;
        width: fit-content;
        max-width: 100%;
        padding: 8px 12px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 18px 40px rgba(7, 27, 77, 0.18);
    }
    .brand-panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .brand-name {
        font-size: clamp(1.2rem, 1.4vw, 1.5rem);
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        color: #0b2b6d;
        letter-spacing: -0.02em;
    }
    .brand-tagline {
        margin-top: 4px;
        font-size: 0.75rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #2563eb;
        font-weight: 700;
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
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.08);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.32);
        border-radius: 100px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-dot { height: 6px; width: 6px; background: #ffffff; border-radius: 50%; margin-right: 8px; box-shadow: 0 0 8px rgba(255,255,255,0.45); }
    .aside-title {
        margin: 18px 0 10px;
        font-size: 1.72rem;
        line-height: 1.14;
        color: #ffffff;
        font-weight: 700;
        letter-spacing: -0.02em;
    }
    .aside-copy {
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.94rem;
        line-height: 1.75;
        margin: 0;
        max-width: 28ch;
    }
    .aside-points { display: grid; gap: 10px; margin-top: 18px; }
    .aside-point {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 13px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .aside-point i { color: #ffffff; margin-top: 2px; }
    .aside-point strong { display: block; color: #ffffff; font-size: 0.88rem; margin-bottom: 2px; font-weight: 600; }
    .aside-point span { color: rgba(255, 250, 240, 0.92); font-size: 0.78rem; line-height: 1.55; }

    .side-footer-info { font-size: 10px; font-weight: 600; color: rgba(255, 255, 255, 0.56); text-transform: uppercase; letter-spacing: 1px; }

    /* Main Panel (Login Form) */
    .smat-main {
        width: 62%;
        padding: 34px 34px 30px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: var(--spa-text);
        position: relative;
    }
    .panel-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        align-self: flex-start;
        padding: 6px 12px;
        border-radius: 999px;
        background: #ffffff;
        color: #0b2a63;
        border: 1px solid #d8e0ec;
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 14px;
    }
    .panel-kicker::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #173b92;
        box-shadow: 0 0 0 4px rgba(15, 58, 138, 0.08);
    }
    .form-shell {
        border: 1px solid #d8e0ec;
        border-radius: 22px;
        background: #ffffff;
        padding: 22px 22px 20px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.045);
    }

    .login-instruction-box {
        border: 1px solid #d8e0ec;
        background: #ffffff;
        color: #0b2a63;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 18px;
        font-size: 14px;
        line-height: 1.55;
        font-weight: 600;
    }
    .login-instruction-box strong {
        color: #061a44;
        font-weight: 600;
    }

    .uplink-badge {
        font-size: 13px; background: #ffffff; color: #0b2a63;
        padding: 9px 12px; border-radius: 12px; border: 1px solid #d8e0ec;
        margin-bottom: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px;
    }
    .uplink-badge strong {
        font-weight: 600;
    }

    .logout-success {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 16px;
        font-size: 14px;
        font-weight: 500;
    }

    /* Input Styles */
    .label-caps {
        font-size: 13px; font-weight: 700; text-transform: uppercase;
        color: #0b2a63; margin-bottom: 6px; display: block; letter-spacing: 0.5px;
    }

    .input-smat {
        padding: 13px 16px; border-radius: 14px; border: 1px solid #cfd8e6;
        background: #ffffff; font-size: 15px; transition: all 0.2s; font-weight: 500;
        color: #061a44;
    }

    .input-smat:focus {
        background: #fff; border-color: var(--spa-primary);
        box-shadow: 0 0 0 4px rgba(15, 58, 138, 0.10); outline: none;
        transform: translateY(-1px);
    }

    .pass-container { position: relative; }
    .toggle-eye { 
        position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
        cursor: pointer; color: #94a3b8; font-size: 14px;
    }

    /* Action Buttons */
    .btn-smat-navy {
        background: #ffffff;
        color: #0b2a63;
        border: 1px solid #cfd8e6;
        padding: 14px;
        border-radius: 16px;
        width: 100%;
        font-weight: 700;
        font-size: 15px;
        transition: 0.3s;
        margin-top: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
    }
    .btn-smat-navy:hover {
        background: #f8fafc;
        color: #061a44;
        transform: translateY(-2px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.10);
    }

    .divider { position: relative; text-align: center; margin: 24px 0; border-top: 1px solid #f1f5f9; }
    .divider span { 
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: #fff; padding: 0 15px; font-size: 10px; color: #64748b; font-weight: 700;
    }

    .btn-social {
        background: #ffffff;
        border: 1px solid #cfd8e6;
        padding: 11px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        gap: 10px;
        font-weight: 600;
        color: #0b2a63;
        text-decoration: none;
        transition: 0.2s;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
    }
    .btn-social:hover {
        background: #f8fafc;
        border-color: #b8c4d6;
        color: #061a44;
        transform: translateY(-1px);
    }
    .social-mark {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        flex: 0 0 28px;
    }
    .social-mark img {
        width: 16px;
        height: 16px;
        display: block;
    }
    .social-mark.facebook {
        color: #1877f2;
        font-size: 15px;
    }

    .bottom-link {
        margin-top: 25px;
        text-align: center;
        font-size: 13px;
        color: #475569;
        font-weight: 500;
    }
    .bottom-link a { color: var(--spa-primary); text-decoration: none; font-weight: 700; }
    .smat-main .fw-bold,
    .smat-main b,
    .smat-main strong {
        font-weight: 600 !important;
    }
    .bottom-actions {
        margin-top: 14px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .bottom-action-link {
        border: 1px solid #cfd8e6;
        border-radius: 14px;
        padding: 12px 14px;
        text-decoration: none;
        color: #0b2a63;
        font-size: 11px;
        font-weight: 600;
        background: #ffffff;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.055);
        transition: all 0.2s ease;
        text-align: center;
    }
    .bottom-link .bottom-action-link,
    .bottom-link .bottom-action-link:visited {
        color: #0b2a63 !important;
    }
    .bottom-action-link:hover {
        border-color: #b8c4d6;
        color: #061a44;
        transform: translateY(-2px);
    }

    .auth-alert {
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 14px;
        font-size: 13px;
        border: 1px solid transparent;
    }
    .auth-alert-success {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }
    .auth-alert-error {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    @media (max-width: 991px) {
        .smat-card {
            width: min(calc(100vw - 28px), 560px) !important;
            max-width: 560px !important;
            height: auto;
            margin: 20px auto !important;
        }
        .smat-aside { display: none; }
        .smat-main { width: 100%; padding: 24px 20px; }
        .logo-img { height: 46px; }
        .brand-lockup { gap: 7px; margin-bottom: 6px; }
        .brand-name { font-size: 1.1rem; }
        .mobile-brand-lockup { display: inline-flex; }
        /* Reset viewport for mobile scrolling */
        .smat-viewport { display: grid !important; place-items: center !important; overflow: visible; }
    }

    @media (max-width: 640px) {
        .smat-aside, .smat-main { padding: 18px 14px; }
        .bottom-actions { grid-template-columns: 1fr; }
        .btn-smat-navy { padding: 13px; font-size: 13px; }
        .btn-social { font-size: 12px; padding: 9px; }
        .form-shell { padding: 18px 16px; border-radius: 18px; }
        .mobile-brand-lockup .logo-img { height: 34px; }
        .mobile-brand-lockup .brand-name { font-size: 0.98rem; }
        .mobile-brand-lockup .brand-tagline { font-size: 0.62rem; }
    }
</style>

<script>
    (function () {
        const params = new URLSearchParams(window.location.search);
        const shouldFlush = params.has('flush') || params.has('expired') || params.has('logout');
        const navEntry = performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
        const navType = navEntry && navEntry.type ? navEntry.type : '';
        const pageRestoreKey = 'smat-login-bfcache-refresh';

        const clearClientState = function () {
            try {
                window.localStorage.clear();
            } catch (error) {}

            try {
                window.sessionStorage.clear();
            } catch (error) {}
        };

        if (shouldFlush) {
            clearClientState();
        }

        window.addEventListener('pageshow', function (event) {
            const restoredFromCache = event.persisted || navType === 'back_forward';

            if (!restoredFromCache) {
                return;
            }

            if (window.sessionStorage.getItem(pageRestoreKey)) {
                window.sessionStorage.removeItem(pageRestoreKey);
                return;
            }

            clearClientState();
            window.sessionStorage.setItem(pageRestoreKey, '1');
            window.location.replace(window.location.pathname + '?refresh=' + Date.now());
        });
    })();
</script>

<script>
    (function () {
        const bindLoginSubmitRefresh = function () {
            const form = document.querySelector('form[action="{{ route('saas-login.post') }}"]');

            if (!form || form.dataset.csrfRefreshBound === '1') {
                return;
            }

            form.dataset.csrfRefreshBound = '1';
            let isRefreshingToken = false;

            form.addEventListener('submit', async function (event) {
                if (isRefreshingToken) {
                    return;
                }

                event.preventDefault();
                isRefreshingToken = true;

                try {
                    const response = await fetch('{{ route('session.csrf-token') }}', {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to refresh CSRF token.');
                    }

                    const data = await response.json();
                    const tokenInput = form.querySelector('input[name="_token"]');

                    if (tokenInput && data.token) {
                        tokenInput.value = data.token;
                    }
                } catch (error) {
                    console.warn('CSRF refresh failed before login submit.', error);
                }

                form.submit();
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindLoginSubmitRefresh, { once: true });
            return;
        }

        bindLoginSubmitRefresh();
    })();
</script>

<div class="smat-viewport">

    
    <div class="bubble-bg">
        <div class="bubble" style="width: 500px; height: 500px; top: -150px; left: -100px;"></div>
        <div class="bubble" style="width: 300px; height: 300px; bottom: -50px; right: -50px; animation-delay: -5s;"></div>
    </div>

    
    <div class="smat-card">

        
        <div class="smat-aside">
            <div>
                <x-auth-brand-lockup :logo="asset('/assets/img/logos.png')" theme="dark" size="lg" :tagline="'Secure Business Stack'" />
                <div class="status-badge"><span class="status-dot"></span> Secure Node Active</div>
                <h2 class="aside-title">Authorized<br>Login</h2>
                <p class="aside-copy">Connect to your accounting nodes through a cleaner, secure sign-in channel built for finance teams.</p>
                <div class="aside-points">
                    <div class="aside-point">
                        <i class="fas fa-lock"></i>
                        <div>
                            <strong>Protected session access</strong>
                            <span>Sign in with your admin email or phone and continue exactly where your workspace left off.</span>
                        </div>
                    </div>
                    <div class="aside-point">
                        <i class="fas fa-wave-square"></i>
                        <div>
                            <strong>Live node continuity</strong>
                            <span>Your selected plan context and billing cycle stay attached through the login flow.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="side-footer-info">
                Connection v2.6.4
            </div>
        </div>

        
        <div class="smat-main">
            <div class="mobile-brand-lockup">
                <x-auth-brand-lockup :logo="asset('/assets/img/logos.png')" size="md" :tagline="'Secure Business Stack'" />
            </div>
            <span class="panel-kicker">Protected access</span>
            <form action="{{ route('saas-login.post') }}" method="POST" class="form-shell">
                @csrf
                <div class="login-instruction-box">
                    <strong>Get Started:</strong> Sign in or create account.
                </div>

                @if($persistedPlan !== 'enterprise' || $persistedCycle !== 'monthly')
                    <div class="uplink-badge">
                        <i class="fas fa-microchip"></i>
                        <span>UPLINK: <strong>{{ strtoupper($persistedPlan) }} NODE</strong> DETECTED</span>
                    </div>
                @endif

                @if(session('success'))
                    <div class="auth-alert auth-alert-success">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="auth-alert auth-alert-error">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="auth-alert auth-alert-error">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-3">
                    <label class="label-caps">Email or Phone</label>
                    <input type="text" name="login" class="form-control input-smat w-100" 
                           placeholder="name@institution.com or +2348012345678" value="{{ old('login', old('email')) }}" required autofocus>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="label-caps m-0">Secure Passcode</label>
                        <a href="{{ route('password.request', ['plan' => $persistedPlan, 'cycle' => $persistedCycle]) }}" class="text-decoration-none fw-bold" style="color: #dc2626; font-size: 10px; text-transform: uppercase;">Forgot Password?</a>
                    </div>
                    <div class="pass-container">
                        <input type="password" name="password" id="pass_input" class="form-control input-smat w-100" placeholder="••••••••" required>
                        <i class="far fa-eye-slash toggle-eye" id="eye_toggle"></i>
                    </div>
                </div>

                <button type="submit" class="btn-smat-navy">Initialize Terminal</button>

                <div class="divider">
                    <span>OAUTH ACCESS</span>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ $googleAuthUrl }}" class="btn-social">
                            <span class="social-mark">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google">
                            </span>
                            <span>Google</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ $facebookAuthUrl }}" class="btn-social">
                            <span class="social-mark facebook">
                                <i class="fab fa-facebook-f"></i>
                            </span>
                            <span>Facebook</span>
                        </a>
                    </div>
                </div>

                <div class="bottom-link">
                    Choose your onboarding path
                    <div class="bottom-actions">
                        <a href="{{ route('membership-plans') }}" class="bottom-action-link">Buy a Plan</a>
                        <a href="{{ route('saas-register', ['type' => 'partner']) }}" class="bottom-action-link">Become a Partner</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('eye_toggle').addEventListener('click', function() {
        const input = document.getElementById('pass_input');
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        this.classList.toggle('fa-eye', isPass);
        this.classList.toggle('fa-eye-slash', !isPass);
    });
</script>

@endsection
