@php $page = 'domain-setup'; @endphp
@extends('layout.mainlayout')

@section('content')
@php
    $displayPrice = (float) ($subscription->amount ?? session('selected_amount', 0));
    $activeTier = $subscription->plan_name ?? 'Pro';
    $cycle = $subscription->billing_cycle ?? 'Monthly';
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    html, body {
        height: 100%;
        overflow: hidden !important;
    }

    .main-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        height: 100% !important;
        overflow: hidden !important;
    }

    /* 1. ABSOLUTE VIEWPORT CENTERING */
    .smat-viewport {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: #fdfeff; /* Ultra light crystal background */
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Hide standard layout noise */
    .sidebar, .header, .navbar, .header-left, .header-right, .footer, .nav-header, .settings-icon, .breadcrumb { 
        display: none !important; 
        visibility: hidden !important;
    }

    /* 2. SUBTLE MINIMALIST BUBBLES */
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

    /* 3. COMPACT PROFESSIONAL CARD (860px) */
    .smat-card {
        background: #ffffff;
        width: 90%;
        max-width: 860px; 
        min-height: 540px;
        border-radius: 32px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.03);
        display: flex;
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }

    /* Side Panel (Summary) */
    .smat-aside {
        width: 35%;
        background: #f9fbff;
        padding: 45px 35px;
        display: flex; flex-direction: column; justify-content: space-between;
        border-right: 1px solid #f1f5f9;
    }

    .logo-img { height: 38px; width: auto; margin-bottom: 20px; }

    .step-badge {
        display: inline-block;
        padding: 5px 12px;
        background: #ffffff;
        color: #3b82f6;
        border: 1px solid #e2e8f0;
        border-radius: 100px;
        font-size: 10px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-row { display: flex; justify-content: space-between; font-size: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .info-label { color: #94a3b8; font-size: 9px; text-transform: uppercase; font-weight: 700; }
    .info-value { color: #1e293b; font-weight: 700; }

    .amount-display { margin-top: 30px; padding: 20px; background: #fff; border-radius: 20px; border: 1px solid #f1f5f9; }
    .amount-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; letter-spacing: -1px; }

    /* Main Panel (Form) */
    .smat-main {
        width: 65%;
        padding: 50px 60px;
        background: #ffffff;
        display: flex; flex-direction: column;
    }

    .form-title { font-weight: 800; color: #0f172a; font-size: 1.6rem; margin-bottom: 5px; }
    .form-subtitle { color: #64748b; font-size: 14px; margin-bottom: 30px; }

    /* Input Styles */
    .label-caps {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        color: #94a3b8; margin-bottom: 8px; display: block; letter-spacing: 0.5px;
    }

    .input-smat {
        padding: 13px 16px; border-radius: 12px; border: 1px solid #e2e8f0;
        background: #fcfdfe; font-size: 14px; transition: all 0.2s; font-weight: 500;
    }

    .input-smat:focus {
        background: #fff; border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.04); outline: none;
    }

    .domain-suffix {
        background: #f8fafc; border: 1px solid #e2e8f0; border-left: none;
        border-radius: 0 12px 12px 0; font-weight: 700; color: #3b82f6;
        padding: 0 15px; font-size: 13px;
    }

    /* Red Action Button */
    .btn-smat-red {
        background: #ef4444; color: #fff; border: none; padding: 16px;
        border-radius: 16px; width: 100%; font-weight: 700; font-size: 15px;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.2); transition: 0.3s;
        margin-top: 10px;
    }

    .btn-smat-red:hover {
        transform: translateY(-2px); box-shadow: 0 15px 30px rgba(239, 68, 68, 0.3); color: #fff;
    }

    .error-pill {
        background: #fff1f1; border-left: 4px solid #ef4444;
        color: #ef4444; padding: 12px; border-radius: 8px;
        margin-bottom: 20px; font-size: 13px; font-weight: 600;
    }

    @media (max-width: 991px) {
        .smat-card { flex-direction: column; width: 95%; height: auto; margin: 20px 0; }
        .smat-aside, .smat-main { width: 100%; padding: 35px; }
        .smat-viewport { padding: 14px; align-items: flex-start; }
    }
</style>

<div class="smat-viewport">
    
    <!-- Minimal Bubbles -->
    <div class="bubble-bg">
        <div class="bubble" style="width: 500px; height: 500px; top: -150px; left: -100px;"></div>
        <div class="bubble" style="width: 300px; height: 300px; bottom: -50px; right: -50px; animation-delay: -5s;"></div>
    </div>

    <!-- The Card -->
    <div class="smat-card">
        
        <!-- Summary Panel -->
        <div class="smat-aside">
            <div>
                <img src="{{ asset('/assets/img/logo-placeholder.svg') }}" alt="SmartProbook" class="logo-img">
                <br>
                <span class="step-badge">Step 02: Identity</span>
                <h2 class="fw-bold mt-4 mb-2" style="font-size: 1.5rem; color: #0f172a; line-height: 1.2;">Node<br>Configuration</h2>
                <p class="small text-muted">Initialize your dedicated node on our secure infrastructure.</p>
            </div>

            <div>
                <div class="info-row"><span class="info-label">Selected Tier</span><span class="info-value">{{ strtoupper($activeTier) }}</span></div>
                <div class="info-row"><span class="info-label">Billing Cycle</span><span class="info-value">{{ strtoupper($cycle) }}</span></div>
                
                <div class="amount-display text-center">
                    <span class="info-label" style="display: block; margin-bottom: 5px;">Payable Amount</span>
                    <div class="amount-value">₦{{ number_format($displayPrice, 2) }}</div>
                </div>
                <div class="text-center mt-3">
                    <p class="mb-0 text-muted" style="font-size: 10px; font-weight: 700;">OPERATOR: {{ Auth::user()->email }}</p>
                </div>
            </div>
        </div>

        <!-- Form Panel -->
        <div class="smat-main">
            <h1 class="form-title">Identity Setup</h1>
            <p class="form-subtitle">Claim your unique subdomain and name your workspace.</p>

            @if ($errors->any())
                <div class="error-pill">
                    @foreach ($errors->all() as $error) 
                        <div><i class="fas fa-exclamation-circle me-1"></i> {{ $error }}</div> 
                    @endforeach
                </div>
            @endif

            <form action="{{ route('saas.store') }}" method="POST">
                @csrf
                <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">

                <div class="mb-3">
                    <label class="label-caps">Business / Entity Name</label>
                    <input type="text" name="customer_name" class="form-control input-smat w-100" 
                           placeholder="e.g. Acme Corporation" value="{{ old('customer_name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="label-caps">Subdomain Prefix (Your URL)</label>
                    <div class="input-group">
                        <input type="text" name="domain_prefix" id="subdomain_input" 
                               class="form-control input-smat" placeholder="my-business" 
                               value="{{ old('domain_prefix') }}" required>
                        <span class="input-group-text domain-suffix">.smatbook.com</span>
                    </div>
                    <small class="text-muted" style="font-size: 10px;">Lowercase, numbers, and dashes only.</small>
                </div>

                <div class="mb-5">
                    <label class="label-caps">Organization Size</label>
                    <select name="employees" class="form-select input-smat">
                        <option value="1-10">1 - 10 Personnel</option>
                        <option value="11-50">11 - 50 Personnel</option>
                        <option value="51-200">51 - 200 Personnel</option>
                        <option value="201+">201+ Personnel</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-smat-red">
                    Continue to Payment <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Real-time input cleaning for subdomains
    document.getElementById('subdomain_input').addEventListener('input', function(e) {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    });
</script>

@endsection
