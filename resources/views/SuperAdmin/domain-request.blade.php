@extends('layout.master')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    /* 1. FORCE THE PAGE TO THE ABSOLUTE CENTER */
    /* This "fixed" container ignores any margins/sidebars from your master layout */
    .master-provision-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: #f4f9ff; /* Very light blue */
        z-index: 9999; /* Ensure it stays above everything */
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow: hidden;
    }

    /* Hide standard layout UI */
    .sidebar, .header, .navbar, .header-left, .header-right, .footer, .nav-header { 
        display: none !important; 
        visibility: hidden !important;
    }

    /* 2. BEAUTIFUL FLOATING BUBBLES */
    .bubble-bg {
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .bubble {
        position: absolute;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0) 70%);
        animation: floatBubble 20s infinite ease-in-out;
    }

    @keyframes floatBubble {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(50px, -80px) scale(1.2); }
        66% { transform: translate(-40px, 40px) scale(0.8); }
    }

    /* 3. PROFESSIONAL COMPACT CARD */
    .smat-card {
        background: #ffffff;
        width: 90%;
        max-width: 920px; /* Elegant professional width */
        min-height: 580px;
        border-radius: 30px;
        box-shadow: 0 30px 100px rgba(15, 23, 42, 0.1);
        display: flex;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.7);
    }

    /* Side Panel */
    .smat-aside {
        width: 38%;
        background: linear-gradient(180deg, #f8faff 0%, #eef5ff 100%);
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-right: 1px solid #edf2f7;
    }

    .logo-img { height: 42px; width: auto; margin-bottom: 30px; }

    .step-badge {
        display: inline-block;
        padding: 6px 14px;
        background: #ffffff;
        color: #3b82f6;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }

    .summary-box {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        border: 1px solid #f1f5f9;
    }

    /* Main Panel */
    .smat-main {
        width: 62%;
        padding: 60px 70px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-title { font-weight: 800; color: #1e293b; font-size: 1.8rem; margin-bottom: 8px; }
    .form-subtitle { color: #64748b; font-size: 14px; margin-bottom: 40px; }

    /* Input Styles */
    .label-caps {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 10px;
        display: block;
        letter-spacing: 0.5px;
    }

    .input-smat {
        padding: 14px 18px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        font-size: 15px;
        transition: all 0.2s;
    }

    .input-smat:focus {
        background: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .subdomain-tag {
        background: #eff6ff;
        border: 1px solid #e2e8f0;
        border-left: none;
        border-radius: 0 14px 14px 0;
        font-weight: 700;
        color: #3b82f6;
        padding: 0 20px;
        font-size: 14px;
    }

    /* Primary Red Button */
    .btn-deploy-red {
        background: #ef4444;
        color: #fff;
        border: none;
        padding: 18px;
        border-radius: 16px;
        width: 100%;
        font-weight: 700;
        font-size: 16px;
        box-shadow: 0 15px 30px rgba(239, 68, 68, 0.2);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-deploy-red:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(239, 68, 68, 0.3);
        background: #dc2626;
        color: #fff;
    }

    @media (max-width: 991px) {
        .smat-card { flex-direction: column; width: 95%; height: auto; margin: 20px 0; }
        .smat-aside, .smat-main { width: 100%; padding: 40px; }
        .master-provision-overlay { position: absolute; height: auto; min-height: 100vh; overflow-y: auto; }
    }
</style>

<div class="master-provision-overlay">
    
    <!-- Bubbles -->
    <div class="bubble-bg">
        <div class="bubble" style="width: 500px; height: 500px; top: -150px; left: -100px;"></div>
        <div class="bubble" style="width: 400px; height: 400px; bottom: -100px; right: -50px; animation-delay: -4s;"></div>
        <div class="bubble" style="width: 250px; height: 250px; top: 20%; right: 10%; animation-delay: -8s;"></div>
    </div>

    <!-- The Card -->
    <div class="smat-card">
        
        <!-- Sidebar Info -->
        <div class="smat-aside">
            <div>
                <img src="{{ asset('assets/img/smat12.png') }}" alt="Logo" class="logo-img">
                <br>
                <div class="step-badge">Step 02: Provisioning</div>
                <h2 class="fw-800 mt-4 mb-3" style="font-size: 1.9rem; color: #0f172a; line-height: 1.2;">Workspace<br>Initialization</h2>
                <p class="small text-muted" style="line-height: 1.6;">Deploying your secure business node. Your subdomain will be your unique entry point.</p>
            </div>

            <div class="summary-box">
                <div class="row g-0">
                    <div class="col-6 border-end pe-3">
                        <span class="label-caps" style="font-size: 9px;">Selected Tier</span>
                        <div class="fw-bold text-primary" style="font-size: 15px;">{{ ucfirst($plan ?? ($subscription->plan_name ?? 'Business')) }}</div>
                    </div>
                    <div class="col-6 ps-3">
                        <span class="label-caps" style="font-size: 9px;">Total Fee</span>
                        <div class="fw-bold text-dark" style="font-size: 15px;">₦{{ number_format($selectedPrice ?? ($subscription->amount ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="smat-main">
            <h1 class="form-title">Setup Subdomain</h1>
            <p class="form-subtitle">Configure your unique workspace URL below.</p>

            <form action="{{ route('saas.store') }}" method="POST">
                @csrf
                <input type="hidden" name="subscription_id" value="{{ $subscription->id ?? '' }}">

                <div class="mb-4">
                    <label class="label-caps">Preferred Subdomain</label>
                    <div class="input-group">
                        <input type="text" name="domain_prefix" 
                               class="form-control input-smat @error('domain_prefix') is-invalid @enderror" 
                               placeholder="yourcompany" required
                               value="{{ old('domain_prefix', $subscription->domain_prefix ?? '') }}">
                        <span class="input-group-text subdomain-tag">.smatbook.com</span>
                    </div>
                    @error('domain_prefix')
                        <div class="text-danger small mt-2 fw-bold"><i class="fas fa-info-circle me-1"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <label class="label-caps">User Capacity</label>
                        <select name="employees" class="form-select input-smat">
                            <option value="1-10">1-10 Staff Members</option>
                            <option value="11-50">11-50 Staff Members</option>
                            <option value="51-200">51-200 Staff Members</option>
                            <option value="200+">200+ Staff Members</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="label-caps">Billing Cycle</label>
                        <input type="text" class="form-control input-smat bg-light" 
                               value="{{ ucfirst($cycle ?? ($subscription->billing_cycle ?? 'Monthly')) }}" readonly>
                    </div>
                </div>

                <button type="submit" class="btn btn-deploy-red">
                    Deploy Node & Proceed <i class="fas fa-arrow-right ms-2"></i>
                </button>

                <div class="text-center mt-4">
                    <a href="{{ url('/membership-plans') }}" class="text-decoration-none small fw-bold" style="color: #3b82f6;">
                         <i class="fas fa-chevron-left me-1"></i> Change Plan Selection
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection