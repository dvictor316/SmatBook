
@php 
    $page = 'saas-register'; 
@endphp
@extends('layout.mainlayout')

@section('content')
@php
    // Detect if this is a manager signup via URL query or session
    $isManager = request()->query('type') === 'manager' || session('reg_role') === 'deployment_manager';
    
    // Logic Alignment: Managers get 'Partner' plan, others get the passed $selectedPlan or Pro.
    $lookupPlan = $isManager ? 'Partner' : ($selectedPlan ?? session('selected_plan', 'pro'));
    $finalCycle = $isManager ? 'Lifetime' : ($billing_cycle ?? session('selected_cycle', 'monthly'));
    
    // Display Price Logic: Ensure the controller's $amount takes precedence.
    $displayPrice = $isManager ? 0 : ($amount ?? session('selected_amount', 0));
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
    :root {
        --spa-bg: #eff5ff;
        --spa-surface: rgba(255, 255, 255, 0.95);
        --spa-aside: linear-gradient(145deg, #2348c7 0%, #1b2fb5 38%, #0a148a 100%);
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

    .main-wrapper,
    .main-wrapper.login-body {
        margin: 0 !important;
        padding: 0 !important;
        min-height: 100% !important;
        overflow: visible !important;
    }

    .smat-viewport {
        position: relative;
        top: 0;
        left: 0;
        width: 100%;
        min-height: 100vh;
        padding: 16px 12px 40px;
        background:
            radial-gradient(circle at top left, rgba(40, 195, 243, 0.28), transparent 24%),
            radial-gradient(circle at bottom right, rgba(29, 109, 255, 0.2), transparent 28%),
            linear-gradient(180deg, #f4f9ff 0%, #e8f2ff 100%);
        z-index: 9999;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Force hide standard layout elements for a clean portal experience */
    .sidebar, .header, .navbar, .header-left, .header-right, .footer, .nav-header, .settings-icon, .breadcrumb { 
        display: none !important; 
        visibility: hidden !important;
    }

    .bubble-bg {
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: -1;
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

    .smat-card {
        background: var(--spa-surface);
        width: min(100%, 980px);
        max-width: 980px;
        min-height: 560px;
        border-radius: 24px;
        box-shadow: 0 30px 90px rgba(15, 23, 42, 0.12), 0 10px 24px rgba(37, 99, 235, 0.08);
        display: flex;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(18px);
        margin-bottom: 24px;
    }

    .smat-aside {
        width: 40%;
        background: var(--spa-aside);
        padding: 32px 26px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        gap: 18px;
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
        font-size: clamp(1.08rem, 1.45vw, 1.4rem);
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

    .step-badge {
        display: inline-block;
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

    .info-row { display: flex; justify-content: space-between; font-size: 11px; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    .info-label { color: rgba(255, 255, 255, 0.58); font-size: 9px; text-transform: uppercase; font-weight: 700; }
    .info-value { color: #ffffff; font-weight: 700; }

    .aside-meta {
        margin-top: 2px;
    }

    .amount-display {
        margin-top: 14px;
        padding: 16px;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }
    .amount-value { font-size: 1.6rem; font-weight: 800; color: #ffffff; letter-spacing: -0.04em; }
    .aside-points { margin-top: 18px; display: grid; gap: 10px; }
    .aside-point {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 13px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .aside-point i { color: #ffffff; margin-top: 2px; }
    .aside-point strong { display: block; color: #ffffff; font-size: 0.88rem; margin-bottom: 2px; }
    .aside-point span { color: rgba(255, 250, 240, 0.92); font-size: 0.78rem; line-height: 1.55; }

    .smat-main {
        width: 60%;
        padding: 34px 34px 30px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: #0f172a;
        position: relative;
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
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }
    .form-title { font-weight: 800; color: #0f172a; font-size: 1.7rem; margin-bottom: 6px; letter-spacing: -0.03em; }
    .form-subtitle { color: #64748b; font-size: 0.95rem; margin-bottom: 18px; line-height: 1.7; max-width: 44ch; }
    .form-shell {
        border: 1px solid #e5edf8;
        border-radius: 22px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        padding: 22px 22px 20px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
    }
    .info-banner {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #f8fbff;
        border: 1px solid #e0ecff;
        color: #173b92;
        font-size: 0.82rem;
        line-height: 1.6;
        margin-bottom: 18px;
    }
    .info-banner i { color: #173b92; }
    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .label-caps {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        color: #173b92; margin-bottom: 6px; display: block; letter-spacing: 0.6px;
    }

    .input-smat {
        padding: 13px 16px; border-radius: 14px; border: 1px solid #dbe5f2;
        background: #fcfdfe; font-size: 13px; transition: all 0.2s; font-weight: 500;
    }

    .input-smat:focus {
        background: #fff; border-color: var(--spa-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.16); outline: none;
        transform: translateY(-1px);
    }

    .pass-container { position: relative; }
    .toggle-eye { 
        position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
        cursor: pointer; color: #94a3b8; font-size: 14px;
    }

    .btn-smat-red {
        background: linear-gradient(145deg, #1d6dff 0%, #1f8fff 45%, #28c3f3 100%);
        color: var(--spa-gold); border: none; padding: 14px;
        border-radius: 16px; width: 100%; font-weight: 800; font-size: 13px;
        box-shadow: 0 16px 30px rgba(37, 99, 235, 0.24); transition: 0.3s;
        margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;
    }

    .btn-smat-red:hover {
        transform: translateY(-2px); box-shadow: 0 15px 30px rgba(37, 99, 235, 0.28); color: #fff;
        background: var(--spa-primary-dark);
    }

    .error-pill {
        background: #fff1f1; border-left: 4px solid #e11d48;
        color: #e11d48; padding: 12px; border-radius: 8px;
        margin-bottom: 20px; font-size: 13px; font-weight: 600; list-style: none;
    }

    .bottom-link { margin-top: 18px; text-align: center; font-size: 12px; color: #64748b; }
    .bottom-link a { color: var(--spa-primary); text-decoration: none; font-weight: 800; }
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
        color: var(--spa-gold) !important;
        font-size: 11px;
        font-weight: 800;
        background: linear-gradient(145deg, #1d6dff 0%, #1f8fff 45%, #28c3f3 100%);
        box-shadow: 0 14px 30px rgba(23, 136, 255, 0.22);
        transition: all 0.2s ease;
        text-align: center;
        display: block;
    }
    .bottom-link .bottom-action-link,
    .bottom-link .bottom-action-link:visited {
        color: var(--spa-gold) !important;
    }
    .bottom-action-link:hover {
        border-color: #93c5fd;
        color: var(--spa-gold) !important;
        transform: translateY(-2px);
    }

    .divider {
        position: relative;
        text-align: center;
        margin: 22px 0;
        border-top: 1px solid #f1f5f9;
    }
    .divider span {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        padding: 0 12px;
        font-size: 10px;
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
    }
    .btn-social {
        background: #fff;
        border: 1px solid #dbe5f2;
        padding: 11px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-social:hover {
        background: #f8fbff;
        border-color: #bfd6ff;
        transform: translateY(-1px);
    }

    @media (max-width: 991px) {
        .smat-card { width: min(100%, 560px); height: auto; margin: 0 auto; min-height: 0; }
        .smat-aside { display: none; }
        .smat-main { width: 100%; padding: 24px 20px; }
        .smat-viewport { padding-bottom: 24px; }
        .logo-img { height: 46px; }
        .brand-lockup { gap: 7px; margin-bottom: 6px; }
        .brand-name { font-size: 1.12rem; }
        .field-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .smat-card { border-radius: 20px; }
        .smat-aside, .smat-main { padding: 18px 14px; }
        .form-shell { padding: 18px 16px; border-radius: 18px; }
        .bottom-actions { grid-template-columns: 1fr; }
    }
</style>

<div class="smat-viewport">
    <div class="bubble-bg">
        <div class="bubble" style="width: 500px; height: 500px; top: -150px; left: -100px;"></div>
        <div class="bubble" style="width: 300px; height: 300px; bottom: -50px; right: -50px; animation-delay: -5s;"></div>
    </div>

    <div class="smat-card">
        <div class="smat-aside">
            <div>
                <div class="brand-lockup">
                    <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook" class="logo-img">
                    <div class="brand-panel">
                        <span class="brand-name">SmartProbook</span>
                        <span class="brand-tagline">Secure Business Stack</span>
                    </div>
                </div>
                <span class="step-badge">Step 01: Enrollment</span>
                <h2 class="aside-title">
                    {{ $isManager ? 'Deployment' : 'Administrator' }}<br>Registration
                </h2>
                <p class="aside-copy">
                    {{ $isManager ? 'Initialize your management node to deploy and monitor institutional clients.' : 'Begin your deployment by securing your institutional admin identity.' }}
                </p>
            </div>

            <div class="aside-meta">
                <div class="info-row">
                    <span class="info-label">Account Type</span>
                    <span class="info-value text-uppercase">{{ $isManager ? 'Deployment Manager' : 'Standard Admin' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Access Level</span>
                    <span class="info-value text-capitalize">{{ $isManager ? 'Master / Partner' : $lookupPlan }}</span>
                </div>
                
                <div class="amount-display text-center">
                    <span class="info-label" style="display: block; margin-bottom: 5px;">{{ $isManager ? 'Setup Status' : 'Final Commitment' }}</span>
                    <div class="amount-value">
                        @if($isManager)
                            FREE <span style="font-size: 0.8rem; color: #94a3b8;">(Partner)</span>
                        @else
                            {{-- Corrected pricing display with comma separation --}}
                            ₦{{ number_format($displayPrice, 2) }}
                        @endif
                    </div>
                </div>
                <div class="aside-points">
                    <div class="aside-point">
                        <i class="fas fa-shield-check"></i>
                        <div>
                            <strong>Identity-first onboarding</strong>
                            <span>Every registration is tied to a verified admin or deployment manager profile.</span>
                        </div>
                    </div>
                    <div class="aside-point">
                        <i class="fas fa-layer-group"></i>
                        <div>
                            <strong>Plan-aware provisioning</strong>
                            <span>Your selected plan and cycle are preserved before the account is activated.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="smat-main">
            <span class="panel-kicker">Protected onboarding</span>
            <h1 class="form-title">Create Account</h1>
            <p class="form-subtitle">Enter your details to initialize this {{ $isManager ? 'management' : 'terminal' }} node.</p>

            @if($errors->any())
                <div class="error-pill">
                    @foreach($errors->all() as $error)
                        <li><i class="fas fa-exclamation-triangle me-2"></i> {{ $error }}</li>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('saas-register.post') }}" method="POST" enctype="multipart/form-data" class="form-shell">
                @csrf
                <input type="hidden" name="role" value="{{ $isManager ? 'deployment_manager' : 'admin' }}">
                <input type="hidden" name="plan" value="{{ strtolower($lookupPlan) }}">
                <input type="hidden" name="billing_cycle" value="{{ strtolower($finalCycle) }}">
                <input type="hidden" name="amount" value="{{ $displayPrice }}">

                <div class="info-banner">
                    <i class="fas fa-sparkles"></i>
                    <span>{{ $isManager ? 'Partner registrations activate a deployment workspace with oversight tools.' : 'Your registration prepares a secure admin workspace aligned to the selected billing plan.' }}</span>
                </div>

                <div class="field-grid mb-3">
                    <div>
                        <label class="label-caps">{{ $isManager ? 'Partner Name' : 'Full Name / Entity' }}</label>
                        <input type="text" name="name" class="form-control input-smat w-100"
                               placeholder="{{ $isManager ? 'Management Entity' : 'Institutional Name' }}" value="{{ old('name') }}" required autofocus>
                    </div>
                    <div>
                        <label class="label-caps">Profile Photo (Optional)</label>
                        <input type="file" name="profile_photo" class="form-control input-smat w-100" accept="image/*">
                    </div>
                </div>

                <div class="field-grid mb-3">
                    <div>
                        <label class="label-caps">Email (or use phone below)</label>
                        <input type="email" name="email" class="form-control input-smat w-100"
                               placeholder="admin@terminal.com" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label class="label-caps">Phone (or use email above)</label>
                        <input type="text" name="phone" class="form-control input-smat w-100"
                               placeholder="+2348012345678" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="label-caps">Master Passcode</label>
                        <div class="pass-container">
                            <input type="password" name="password" id="p1" class="form-control input-smat w-100" placeholder="Min. 8 chars" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" title="Use at least 8 characters with letters and numbers." required>
                            <i class="far fa-eye toggle-eye" onclick="togglePass('p1', this)"></i>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:11px;">Use letters and numbers (symbol optional).</small>
                    </div>
                    <div class="col-md-6">
                        <label class="label-caps">Verify Passcode</label>
                        <div class="pass-container">
                            <input type="password" name="password_confirmation" id="p2" class="form-control input-smat w-100" placeholder="Repeat" minlength="8" required>
                            <i class="far fa-eye toggle-eye" onclick="togglePass('p2', this)"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-smat-red">
                    {{ $isManager ? 'Activate Manager Node' : 'Initialize Deployment' }} <i class="fas fa-shield-check ms-1"></i>
                </button>

                @if(!$isManager)
                    <div class="divider"><span>or sign up with</span></div>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('social.login', 'google') }}" class="btn-social">
                                <i class="fab fa-google me-2 text-danger"></i> Google
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('social.login', 'facebook') }}" class="btn-social">
                                <i class="fab fa-facebook-f me-2 text-primary"></i> Facebook
                            </a>
                        </div>
                    </div>
                @endif
                
                <div class="bottom-link">
                    Choose your onboarding path
                    <div class="bottom-actions">
                        <a href="{{ route('membership-plans') }}" class="bottom-action-link">Buy a Plan</a>
                        <a href="{{ route('saas-register', ['type' => 'manager']) }}" class="bottom-action-link">Become a Partner</a>
                    </div>
                    <br>
                    Already verified? <a href="{{ route('saas-login', ['plan' => strtolower((string) $lookupPlan), 'cycle' => strtolower((string) $finalCycle)]) }}">Login to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePass(id, el) {
        const input = document.getElementById(id);
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        el.classList.toggle('fa-eye', isPass);
        el.classList.toggle('fa-eye-slash', !isPass);
    }

    // Keep session warm while user is filling long registration form.
    (function keepSessionAlive() {
        const ping = () => {
            fetch("{{ route('session.ping') }}", {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).catch(() => {});
        };
        setTimeout(ping, 2000);
        setInterval(ping, 120000);
    })();
</script>

@endsection
