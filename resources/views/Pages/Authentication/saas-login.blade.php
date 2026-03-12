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
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
    :root {
        --spa-bg: #eff5ff;
        --spa-surface: rgba(255, 255, 255, 0.95);
        --spa-aside: linear-gradient(145deg, #1d6dff 0%, #1f8fff 45%, #28c3f3 100%);
        --spa-border: #e2e8f0;
        --spa-primary: #2563eb;
        --spa-primary-dark: #1d4ed8;
        --spa-text: #0f172a;
        --spa-muted: #64748b;
        --spa-gold: #ffe08a;
    }

    html, body {
        min-height: 100%;
        overflow-x: hidden !important;
        overflow-y: auto !important;
    }

    /* 1. VIEWPORT & CENTERING FIX */
    .smat-viewport {
        position: relative;
        top: 0;
        left: 0;
        width: 100%;
        min-height: 100vh;
        padding: 20px 15px 40px;
        background:
            radial-gradient(circle at top left, rgba(40, 195, 243, 0.28), transparent 24%),
            radial-gradient(circle at bottom right, rgba(29, 109, 255, 0.2), transparent 28%),
            linear-gradient(180deg, #f4f9ff 0%, #e8f2ff 100%);
        z-index: 900;
        /* Flexbox for vertical centering */
        display: flex;
        flex-direction: column;
        overflow-y: auto; /* Allows scrolling if card is taller than screen */
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
            radial-gradient(circle at 70% 65%, rgba(29, 109, 255, 0.16) 0%, rgba(29, 109, 255, 0) 44%),
            radial-gradient(circle, rgba(40, 195, 243, 0.22) 0%, rgba(40, 195, 243, 0) 72%);
        animation: floatBubble 25s infinite ease-in-out;
    }

    @keyframes floatBubble {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.05); }
        66% { transform: translate(-20px, 20px) scale(0.95); }
    }

    /* 3. COMPACT PROFESSIONAL CARD */
    .smat-card {
        background: linear-gradient(150deg, #0c1f8d 0%, #0a1980 38%, #143ac0 100%);
        width: min(100%, 940px);
        max-width: 940px;
        min-height: 0;
        height: auto;
        border-radius: 24px;
        box-shadow: 0 34px 95px rgba(2, 12, 66, 0.35), 0 14px 30px rgba(23, 136, 255, 0.14);
        display: flex;
        overflow: hidden;
        border: 1px solid rgba(255, 224, 138, 0.22);
        
        /* KEY FIX: This centers it vertically but allows scrolling if needed */
        margin: auto auto 24px;
        backdrop-filter: blur(18px);
    }

    /* Side Panel (Branding) */
    .smat-aside {
        width: 38%;
        background: var(--spa-aside);
        padding: 32px 26px;
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
        margin-bottom: 8px;
        width: fit-content;
        max-width: 100%;
        padding: 10px 14px;
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
        font-size: clamp(1.08rem, 1.35vw, 1.35rem);
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
        color: #0b2b6d;
    }
    .brand-tagline {
        margin-top: 4px;
        font-size: 0.7rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #2563eb;
        font-weight: 700;
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
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-dot { height: 6px; width: 6px; background: #ffffff; border-radius: 50%; margin-right: 8px; box-shadow: 0 0 8px rgba(255,255,255,0.45); }
    .aside-title {
        margin: 18px 0 10px;
        font-size: 1.72rem;
        line-height: 1.14;
        color: #ffffff;
        font-weight: 800;
        letter-spacing: -0.03em;
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
    .aside-point strong { display: block; color: #ffffff; font-size: 0.88rem; margin-bottom: 2px; }
    .aside-point span { color: rgba(255, 250, 240, 0.92); font-size: 0.78rem; line-height: 1.55; }

    .side-footer-info { font-size: 10px; font-weight: 700; color: rgba(255, 255, 255, 0.56); text-transform: uppercase; letter-spacing: 1px; }

    /* Main Panel (Login Form) */
    .smat-main {
        width: 62%;
        padding: 34px 34px 30px;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.1), transparent 28%),
            radial-gradient(circle at bottom left, rgba(40, 195, 243, 0.12), transparent 34%),
            linear-gradient(180deg, #122a9f 0%, #0b1d7c 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: #ffffff;
        position: relative;
    }
    .panel-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        align-self: flex-start;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 224, 138, 0.12);
        color: var(--spa-gold);
        border: 1px solid rgba(255, 224, 138, 0.28);
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
        background: var(--spa-gold);
        box-shadow: 0 0 0 4px rgba(255, 224, 138, 0.12);
    }
    .form-shell {
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 22px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.04) 100%);
        padding: 22px 22px 20px;
        box-shadow: 0 18px 38px rgba(2, 12, 66, 0.2);
        backdrop-filter: blur(12px);
    }

    .login-instruction-box {
        border: 1px solid rgba(255, 224, 138, 0.24);
        background: rgba(255, 224, 138, 0.1);
        color: #fff8e8;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 18px;
        font-size: 12px;
        line-height: 1.55;
    }
    .login-instruction-box strong {
        color: var(--spa-gold);
    }

    .uplink-badge {
        font-size: 10px; background: rgba(255, 255, 255, 0.12); color: #ffffff;
        padding: 9px 12px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.18);
        margin-bottom: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px;
    }

    .logout-success {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 16px;
        font-size: 12px;
        font-weight: 700;
    }

    /* Input Styles */
    .label-caps {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        color: rgba(255, 255, 255, 0.72); margin-bottom: 6px; display: block; letter-spacing: 0.6px;
    }

    .input-smat {
        padding: 13px 16px; border-radius: 14px; border: 1px solid #dbe5f2;
        background: #fcfdfe; font-size: 13px; transition: all 0.2s; font-weight: 500;
    }

    .input-smat:focus {
        background: #fff; border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.16); outline: none;
        transform: translateY(-1px);
    }

    .pass-container { position: relative; }
    .toggle-eye { 
        position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
        cursor: pointer; color: #94a3b8; font-size: 14px;
    }

    /* Action Buttons */
    .btn-smat-navy {
        background: linear-gradient(145deg, #1d6dff 0%, #1f8fff 45%, #28c3f3 100%);
        color: var(--spa-gold); border: none; padding: 14px;
        border-radius: 16px; width: 100%; font-weight: 800; font-size: 13px;
        transition: 0.3s; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;
        box-shadow: 0 16px 30px rgba(37,99,235,0.24);
    }
    .btn-smat-navy:hover {
        background: var(--spa-primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(37,99,235,0.28);
    }

    .divider { position: relative; text-align: center; margin: 24px 0; border-top: 1px solid rgba(255, 255, 255, 0.16); }
    .divider span { 
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: #10258f; padding: 0 15px; font-size: 10px; color: rgba(255, 255, 255, 0.6); font-weight: 800;
    }

    .btn-social {
        background: #fff; border: 1px solid #dbe5f2; padding: 11px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 13px;
        font-weight: 700; color: #1e293b; text-decoration: none; transition: 0.2s;
    }
    .btn-social:hover { background: #f8fbff; border-color: #bfd6ff; transform: translateY(-1px); }

    .bottom-link {
        margin-top: 25px;
        text-align: center;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.72);
    }
    .bottom-link a { color: var(--spa-gold); text-decoration: none; font-weight: 800; }
    .bottom-actions {
        margin-top: 14px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .bottom-action-link {
        border: 1px solid #dbe5f2;
        border-radius: 14px;
        padding: 12px 14px;
        text-decoration: none;
        color: var(--spa-gold);
        font-size: 11px;
        font-weight: 800;
        background: linear-gradient(145deg, #1d6dff 0%, #1f8fff 45%, #28c3f3 100%);
        box-shadow: 0 14px 30px rgba(23, 136, 255, 0.22);
        transition: all 0.2s ease;
        text-align: center;
    }
    .bottom-link .bottom-action-link,
    .bottom-link .bottom-action-link:visited {
        color: var(--spa-gold) !important;
    }
    .bottom-action-link:hover {
        border-color: #93c5fd;
        color: var(--spa-gold);
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
            flex-direction: column;
            width: min(100%, 680px);
            max-width: 680px;
            height: auto;
            margin: 20px auto;
        }
        .smat-aside, .smat-main { width: 100%; padding: 22px 18px; }
        .logo-img { height: 46px; }
        .brand-lockup { gap: 7px; margin-bottom: 6px; }
        .brand-name { font-size: 1.1rem; }
        /* Reset viewport for mobile scrolling */
        .smat-viewport { display: block; overflow-y: auto; }
    }

    @media (max-width: 640px) {
        .smat-aside, .smat-main { padding: 18px 14px; }
        .bottom-actions { grid-template-columns: 1fr; }
        .btn-smat-navy { padding: 13px; font-size: 13px; }
        .btn-social { font-size: 12px; padding: 9px; }
        .form-shell { padding: 18px 16px; border-radius: 18px; }
    }
</style>

<div class="smat-viewport">
    
    <!-- Ultra Light Bubbles -->
    <div class="bubble-bg">
        <div class="bubble" style="width: 500px; height: 500px; top: -150px; left: -100px;"></div>
        <div class="bubble" style="width: 300px; height: 300px; bottom: -50px; right: -50px; animation-delay: -5s;"></div>
    </div>

    <!-- The Card -->
    <div class="smat-card">
        
        <!-- Sidebar Branding -->
        <div class="smat-aside">
            <div>
                <div class="brand-lockup">
                    <img src="{{ asset('/assets/img/logos.png') }}" alt="SmartProbook" class="logo-img">
                    <div class="brand-panel">
                        <span class="brand-name">SmartProbook</span>
                        <span class="brand-tagline">Secure Business Stack</span>
                    </div>
                </div>
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

        <!-- Login Form Panel -->
        <div class="smat-main">
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
                        <a href="{{ route('password.request', ['plan' => $persistedPlan, 'cycle' => $persistedCycle]) }}" class="text-decoration-none fw-bold" style="color: #2563eb; font-size: 10px; text-transform: uppercase;">Lost Key?</a>
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
                        <a href="{{ route('social.login', 'google') }}" class="btn-social">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" width="16" class="me-2"> Google
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('social.login', 'facebook') }}" class="btn-social">
                            <i class="fab fa-facebook-f me-2 text-primary" style="font-size: 16px;"></i> Facebook
                        </a>
                    </div>
                </div>

                <div class="bottom-link">
                    Choose your onboarding path
                    <div class="bottom-actions">
                        <a href="{{ route('membership-plans') }}" class="bottom-action-link">Buy a Plan</a>
                        <a href="{{ route('saas-register', ['type' => 'manager']) }}" class="bottom-action-link">Become a Partner</a>
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
