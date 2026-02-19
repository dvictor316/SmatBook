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

    $persistedPlan = request('plan', session('selected_plan', 'enterprise'));
    $persistedCycle = request('billing_cycle', session('billing_cycle', 'monthly'));
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
    :root {
        --spa-bg: #f7faff;
        --spa-surface: #ffffff;
        --spa-aside: #eef4ff;
        --spa-border: #e2e8f0;
        --spa-primary: #2563eb;
        --spa-primary-dark: #1d4ed8;
        --spa-text: #0f172a;
        --spa-muted: #64748b;
    }

    /* 1. VIEWPORT & CENTERING FIX */
    .smat-viewport {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        padding: 20px 15px;
        background-color: var(--spa-bg);
        z-index: 900;
        /* Flexbox for vertical centering */
        display: flex;
        flex-direction: column;
        overflow-y: auto; /* Allows scrolling if card is taller than screen */
    }

    /* Hard reset wrapper overrides to prevent theme conflict */
    .main-wrapper.login-body {
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
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
        background: radial-gradient(circle, rgba(59, 130, 246, 0.04) 0%, rgba(59, 130, 246, 0) 70%);
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
        width: min(100%, 720px);
        max-width: 720px;
        min-height: 0;
        height: auto;
        border-radius: 14px;
        box-shadow: 0 26px 70px rgba(15, 23, 42, 0.12), 0 8px 22px rgba(15, 23, 42, 0.08);
        display: flex;
        overflow: hidden;
        border: 1px solid var(--spa-border);
        
        /* KEY FIX: This centers it vertically but allows scrolling if needed */
        margin: auto; 
    }

    /* Side Panel (Branding) */
    .smat-aside {
        width: 35%;
        background: var(--spa-aside);
        padding: 30px 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-right: 1px solid var(--spa-border);
    }

    .logo-img { height: 34px; width: auto; margin-bottom: 8px; }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        background: #ffffff;
        color: var(--spa-primary);
        border: 1px solid #dbeafe;
        border-radius: 100px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-dot { height: 6px; width: 6px; background: var(--spa-primary); border-radius: 50%; margin-right: 8px; box-shadow: 0 0 8px rgba(37,99,235,0.45); }

    .side-footer-info { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

    /* Main Panel (Login Form) */
    .smat-main {
        width: 65%;
        padding: 40px 32px;
        background: var(--spa-surface);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-instruction-box {
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1e3a8a;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 20px;
        font-size: 11px;
        line-height: 1.55;
    }
    .login-instruction-box strong {
        color: var(--spa-primary-dark);
    }

    .uplink-badge {
        font-size: 10px; background: #f0f7ff; color: #3b82f6; 
        padding: 8px 12px; border-radius: 10px; border: 1px solid #dbeafe;
        margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;
    }

    /* Input Styles */
    .label-caps {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        color: #94a3b8; margin-bottom: 6px; display: block; letter-spacing: 0.5px;
    }

    .input-smat {
        padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;
        background: #fcfdfe; font-size: 13px; transition: all 0.2s; font-weight: 500;
    }

    .input-smat:focus {
        background: #fff; border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.04); outline: none;
    }

    .pass-container { position: relative; }
    .toggle-eye { 
        position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
        cursor: pointer; color: #94a3b8; font-size: 14px;
    }

    /* Action Buttons */
    .btn-smat-navy {
        background: var(--spa-primary);
        color: #fff; border: none; padding: 12px;
        border-radius: 12px; width: 100%; font-weight: 700; font-size: 13px;
        transition: 0.3s; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;
        box-shadow: 0 10px 25px rgba(37,99,235,0.2);
    }
    .btn-smat-navy:hover {
        background: var(--spa-primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(37,99,235,0.28);
    }

    .divider { position: relative; text-align: center; margin: 24px 0; border-top: 1px solid #f1f5f9; }
    .divider span { 
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: #fff; padding: 0 15px; font-size: 10px; color: #cbd5e1; font-weight: 800;
    }

    .btn-social {
        background: #fff; border: 1px solid #e2e8f0; padding: 10px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 13px;
        font-weight: 700; color: #1e293b; text-decoration: none; transition: 0.2s;
    }
    .btn-social:hover { background: #f8fafc; border-color: #cbd5e1; }

    .bottom-link {
        margin-top: 25px;
        text-align: center;
        font-size: 13px;
        color: #64748b;
    }
    .bottom-link a { color: var(--spa-primary); text-decoration: none; font-weight: 800; }
    .bottom-actions {
        margin-top: 14px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .bottom-action-link {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 9px 10px;
        text-decoration: none;
        color: #334155;
        font-size: 11px;
        font-weight: 700;
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    .bottom-action-link:hover {
        border-color: #cbd5e1;
        color: #0f172a;
        transform: translateY(-1px);
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
            width: min(100%, 600px);
            max-width: 600px;
            height: auto;
            margin: 20px auto;
        }
        .smat-aside, .smat-main { width: 100%; padding: 24px 20px; }
        .logo-img { height: 44px; }
        /* Reset viewport for mobile scrolling */
        .smat-viewport { display: block; overflow-y: auto; }
    }

    @media (max-width: 640px) {
        .smat-aside, .smat-main { padding: 20px 16px; }
        .bottom-actions { grid-template-columns: 1fr; }
        .btn-smat-navy { padding: 13px; font-size: 13px; }
        .btn-social { font-size: 12px; padding: 9px; }
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
                <img src="{{ asset('/assets/img/smat12.png') }}" alt="SmatBook" class="logo-img">
                <br>
                <div class="status-badge"><span class="status-dot"></span> Secure Node Active</div>
                <h2 class="fw-bold mt-4 mb-2" style="font-size: 1.6rem; color: #0f172a; line-height: 1.2;">Authorized<br>Login</h2>
                <p class="small text-muted">Connect to your accounting nodes via secure encrypted uplink.</p>
            </div>

            <div class="side-footer-info">
                Connection v2.6.4
            </div>
        </div>

        <!-- Login Form Panel -->
        <div class="smat-main">
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

            <form action="{{ route('saas-login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="label-caps">Operator Email</label>
                    <input type="email" name="email" class="form-control input-smat w-100" 
                           placeholder="name@institution.com" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="label-caps m-0">Secure Passcode</label>
                        <a href="{{ route('password.request') }}" class="text-decoration-none fw-bold" style="color: #2563eb; font-size: 10px; text-transform: uppercase;">Lost Key?</a>
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
                        <a href="{{ route('membership-plans') }}" class="bottom-action-link">Deploy Infrastructure</a>
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