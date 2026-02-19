
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
        --spa-bg: #f7faff;
        --spa-surface: #ffffff;
        --spa-aside: #eef4ff;
        --spa-border: #e2e8f0;
        --spa-primary: #2563eb;
        --spa-primary-dark: #1d4ed8;
        --spa-text: #0f172a;
        --spa-muted: #64748b;
    }

    .smat-viewport {
        position: relative;
        width: 100%;
        min-height: 100vh;
        height: auto;
        padding: 16px 12px 20px;
        background-color: var(--spa-bg);
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
        background: radial-gradient(circle, rgba(59, 130, 246, 0.04) 0%, rgba(59, 130, 246, 0) 70%);
        animation: floatBubble 25s infinite ease-in-out;
    }

    @keyframes floatBubble {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.05); }
        66% { transform: translate(-20px, 20px) scale(0.95); }
    }

    .smat-card {
        background: var(--spa-surface);
        width: min(100%, 740px);
        max-width: 740px; 
        min-height: 500px;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.03);
        display: flex;
        overflow: hidden;
        border: 1px solid var(--spa-border);
    }

    .smat-aside {
        width: 35%;
        background: var(--spa-aside);
        padding: 30px 22px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-right: 1px solid var(--spa-border);
    }

    .logo-img { height: 34px; width: auto; margin-bottom: 12px; }

    .step-badge {
        display: inline-block;
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

    .info-row { display: flex; justify-content: space-between; font-size: 11px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
    .info-label { color: #94a3b8; font-size: 9px; text-transform: uppercase; font-weight: 700; }
    .info-value { color: #1e293b; font-weight: 700; }

    .amount-display { margin-top: 20px; padding: 14px; background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; }
    .amount-value { font-size: 1.45rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }

    .smat-main {
        width: 65%;
        padding: 26px 24px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-title { font-weight: 800; color: #0f172a; font-size: 1.35rem; margin-bottom: 4px; }
    .form-subtitle { color: #64748b; font-size: 12px; margin-bottom: 20px; }

    .label-caps {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        color: #94a3b8; margin-bottom: 6px; display: block; letter-spacing: 0.5px;
    }

    .input-smat {
        padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;
        background: #fcfdfe; font-size: 13px; transition: all 0.2s; font-weight: 500;
    }

    .input-smat:focus {
        background: #fff; border-color: var(--spa-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08); outline: none;
    }

    .pass-container { position: relative; }
    .toggle-eye { 
        position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
        cursor: pointer; color: #94a3b8; font-size: 14px;
    }

    .btn-smat-red {
        background: var(--spa-primary); color: #fff; border: none; padding: 12px;
        border-radius: 12px; width: 100%; font-weight: 700; font-size: 13px;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2); transition: 0.3s;
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

    .divider {
        position: relative;
        text-align: center;
        margin: 18px 0;
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
        border: 1px solid #e2e8f0;
        padding: 10px;
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
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    @media (max-width: 991px) {
        .smat-card { flex-direction: column; width: min(100%, 620px); height: auto; margin: 0 auto; min-height: 0; }
        .smat-aside, .smat-main { width: 100%; padding: 18px 14px; }
        .smat-viewport { position: relative; height: auto; min-height: 100vh; overflow-y: auto; }
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
                <img src="{{ asset('assets/img/smat15.png') }}" alt="SmatBook" class="logo-img">
                <br>
                <span class="step-badge">Step 01: Enrollment</span>
                <h2 class="fw-bold mt-4 mb-2" style="font-size: 1.5rem; color: #0f172a; line-height: 1.2;">
                    {{ $isManager ? 'Deployment' : 'Administrator' }}<br>Registration
                </h2>
                <p class="small text-muted">
                    {{ $isManager ? 'Initialize your management node to deploy and monitor institutional clients.' : 'Begin your deployment by securing your institutional admin identity.' }}
                </p>
            </div>

            <div>
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
            </div>
        </div>

        <div class="smat-main">
            <h1 class="form-title">Create Account</h1>
            <p class="form-subtitle">Enter your details to initialize this {{ $isManager ? 'management' : 'terminal' }} node.</p>

            @if($errors->any())
                <div class="error-pill">
                    @foreach($errors->all() as $error)
                        <li><i class="fas fa-exclamation-triangle me-2"></i> {{ $error }}</li>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('saas-register.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="role" value="{{ $isManager ? 'deployment_manager' : 'admin' }}">
                <input type="hidden" name="plan" value="{{ strtolower($lookupPlan) }}">
                <input type="hidden" name="billing_cycle" value="{{ strtolower($finalCycle) }}">
                <input type="hidden" name="amount" value="{{ $displayPrice }}">

                <div class="mb-3">
                    <label class="label-caps">{{ $isManager ? 'Partner Name' : 'Full Name / Entity' }}</label>
                    <input type="text" name="name" class="form-control input-smat w-100" 
                           placeholder="{{ $isManager ? 'Management Entity' : 'Institutional Name' }}" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="label-caps">Email Identifier</label>
                    <input type="email" name="email" class="form-control input-smat w-100" 
                           placeholder="admin@terminal.com" value="{{ old('email') }}" required>
                </div>

                <div class="mb-3">
                    <label class="label-caps">Profile Photo (Optional)</label>
                    <input type="file" name="profile_photo" class="form-control input-smat w-100" accept="image/*">
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="label-caps">Master Passcode</label>
                        <div class="pass-container">
                            <input type="password" name="password" id="p1" class="form-control input-smat w-100" placeholder="Min. 8 chars" required>
                            <i class="far fa-eye toggle-eye" onclick="togglePass('p1', this)"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="label-caps">Verify Passcode</label>
                        <div class="pass-container">
                            <input type="password" name="password_confirmation" id="p2" class="form-control input-smat w-100" placeholder="Repeat" required>
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
                    @if($isManager)
                        Want a standard account? <a href="{{ route('membership-plans') }}">Join as Customer</a>
                    @else
                        Interested in Partnering? <a href="{{ route('saas-register', ['type' => 'manager']) }}">Become a Manager</a>
                    @endif
                    <br><br>
                    Already verified? <a href="{{ route('saas-login') }}">Login to Dashboard</a>
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
</script>

@endsection
