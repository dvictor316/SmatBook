<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $currentPlanTier = $currentPlanTier ?? null;
        $suggestedUpgradePlan = $suggestedUpgradePlan ?? null;
        $planCards = \App\Models\Plan::marketingCardCatalog();
        $tierBenefits = collect($planCards)->mapWithKeys(fn ($card, $key) => [$key => $card['benefits']])->all();
        $planActions = [
            'starter' => $currentPlanTier === 'starter'
                ? ['primary' => 'Current Plan']
                : ['primary' => 'Select Starter'],
            'basic' => $currentPlanTier === 'basic'
                ? ['primary' => 'Current Plan']
                : ['primary' => 'Select Basic'],
            'pro' => $currentPlanTier === 'professional'
                ? ['secondary' => 'Current Plan', 'primary' => 'Upgrade to Enterprise']
                : ['secondary' => 'Start 3 Users', 'primary' => 'Start 5 Users'],
            'enterprise' => $currentPlanTier === 'enterprise'
                ? ['secondary' => 'Current Plan', 'primary' => 'Start 10 Users']
                : ['secondary' => 'Start 4 Users', 'primary' => 'Start 10 Users'],
        ];
        $seoNoIndex = $seoNoIndex ?? false;
        $seoType = 'website';
        $seoTitle = 'Membership Plans and Pricing';
        $seoDescription = 'Compare SmartProbook membership plans, pricing tiers, accounting tools, ERP modules, reporting features, and business upgrade options.';
        $seoKeywords = 'SmartProbook pricing, membership plans, accounting software pricing, ERP pricing, invoicing software plans, business software subscriptions';
        $seoCanonical = $seoCanonical ?? route('membership-plans');
    @endphp
    @include('layout.partials.seo-meta')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --muji-blue-deep: #061a44; 
            --muji-blue-accent: #0f3a8a;
            --muji-blue-soft: #2563eb;
            --muji-blue-soft-end: #0f3a8a;
            --muji-blue-light: #f7faff; 
            --muji-gold: #d7a928; 
            --muji-gold-soft: #fff1bf;
            --muji-red: #e11d48; 
            --muji-text: #334155;
            --muji-border: #d8e3f5;
            --shadow-premium: 0 24px 42px -26px rgba(6, 26, 68, 0.28), 0 12px 24px -20px rgba(215, 169, 40, 0.25);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #fff; color: var(--muji-text); line-height: 1.5; overflow-x: hidden; font-size: 15px; }

        /* Professional Slim Nav */
        .spa-nav {
            background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(10px);
            padding: 0.65rem 0; border-bottom: 1px solid rgba(15, 58, 138, 0.14);
            box-shadow: 0 16px 38px -32px rgba(6, 26, 68, 0.5);
            position: sticky; top: 0; z-index: 100;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .brand-lockup { display: inline-flex; align-items: center; gap: 12px; min-width: 0; text-decoration: none; }
        .brand-logo { height: 48px; width: auto; display: block; flex-shrink: 0; }
        .spb-nav-wordmark {
            font-weight: 800;
            color: var(--muji-blue-deep);
            font-size: 2rem;
            letter-spacing: -0.8px;
            line-height: 1;
            white-space: nowrap;
            display: inline-flex;
            align-items: baseline;
        }
        .spb-nav-wordmark .smartpro {
            color: var(--muji-blue-deep) !important;
        }
        .spb-nav-wordmark .book {
            color: #dc2626 !important;
        }

        /* Refined Hero */
        .membership-hero {
            background:
                radial-gradient(circle at 15% 10%, rgba(215, 169, 40, 0.16), transparent 30%),
                radial-gradient(circle at 85% 0%, rgba(37, 99, 235, 0.13), transparent 32%),
                linear-gradient(180deg, var(--muji-blue-light) 0%, #fff 100%);
            padding: 28px 0 44px; text-align: center;
        }

        .gold-label { 
            color: var(--muji-blue-accent); 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            font-size: 0.75rem; 
            margin-bottom: 1rem; 
            display: inline-block;
            background: rgba(215, 169, 40, 0.16);
            border: 1px solid rgba(215, 169, 40, 0.34);
            padding: 4px 12px;
            border-radius: 20px;
        }
        .hero-title { font-size: clamp(1.85rem, 4vw, 2.7rem); font-weight: 800; color: var(--muji-blue-deep); letter-spacing: 0; line-height: 1.1; }
        .hero-title span { color: var(--muji-blue-accent); }
        .hero-subtitle { color: #52647d; margin-top: 8px; font-size: 1rem; max-width: 820px; margin-left: auto; margin-right: auto; }

        .hero-plan-note {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 14px;
            padding: 8px 14px;
            border: 1px solid #a9dfc0;
            border-radius: 8px;
            background: #edf9f2;
            color: #075f42;
            font-size: 0.84rem;
            font-weight: 750;
        }
        .hero-plan-note .annual-note {
            padding-left: 10px;
            border-left: 1px solid #9bd3b4;
            color: var(--muji-blue-deep);
        }

        /* Modern Toggle */
        .billing-toggle {
            display: inline-flex; align-items: center; justify-content: center; gap: 15px;
            margin-top: 40px; font-size: 0.9rem; font-weight: 600;
            background: #fff; padding: 8px 20px; border-radius: 50px; border: 1px solid rgba(15, 58, 138, 0.16);
            box-shadow: 0 14px 30px -24px rgba(6, 26, 68, 0.45);
        }

        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1; transition: .4s; border-radius: 24px;
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--muji-blue-accent); }
        input:checked + .slider:before { transform: translateX(20px); }
        .save-badge { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; margin-left: 5px; }

        /* Pricing Cards */
        .pricing-section { margin-top: -22px; padding-bottom: 64px; }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            align-items: stretch;
        }

        .plan-card {
            background: #fff; border: 1px solid var(--muji-border);
            padding: 28px 22px; border-radius: 8px;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex; flex-direction: column;
            position: relative;
        }

        .plan-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-premium); border-color: rgba(215, 169, 40, 0.78); }

        .plan-card.featured { 
            border: 2px solid var(--muji-gold); 
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 24px 52px -34px rgba(6, 26, 68, 0.5);
        }
        .plan-card.featured .popular-tag {
            position: absolute; top: -14px; left: 50%;
            transform: translateX(-50%); background: linear-gradient(135deg, var(--muji-gold), #f5d36b);
            color: var(--muji-blue-deep); font-size: 0.7rem; padding: 4px 15px; font-weight: 800; border-radius: 20px;
            letter-spacing: 1px;
        }

        .coverage-panel {
            grid-column: span 3;
            min-height: 520px;
            position: relative;
            overflow: hidden;
            border: 1px solid #b8cceb;
            border-radius: 8px;
            background: #eaf6ff url('{{ asset('assets/img/smartprobook-global-coverage.webp') }}') center / cover no-repeat;
            box-shadow: 0 22px 48px -34px rgba(6, 26, 68, 0.5);
        }
        .coverage-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.97) 0%, rgba(255, 255, 255, 0.9) 35%, rgba(255, 255, 255, 0.15) 68%, rgba(255, 255, 255, 0) 100%);
        }
        .coverage-content {
            position: relative;
            z-index: 1;
            width: min(440px, 52%);
            padding: 42px;
        }
        .coverage-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #075f42;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .coverage-kicker::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #14a66f;
            box-shadow: 0 0 0 4px rgba(20, 166, 111, 0.14);
        }
        .coverage-title {
            margin-top: 18px;
            color: var(--muji-blue-deep);
            font-size: clamp(1.75rem, 3vw, 2.65rem);
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: 0;
        }
        .coverage-copy {
            margin-top: 16px;
            color: #435671;
            font-size: 0.96rem;
            line-height: 1.65;
        }
        .coverage-points {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 24px;
        }
        .coverage-point {
            padding: 7px 10px;
            border: 1px solid #bed0e8;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.92);
            color: #123664;
            font-size: 0.76rem;
            font-weight: 750;
        }

        .plan-name { font-weight: 700; color: var(--muji-blue-deep); font-size: 1.25rem; margin-bottom: 10px; }
        .plan-desc { font-size: 0.86rem; color: #64748b; margin-bottom: 16px; line-height: 1.45; min-height: 76px; }

        .price-display {
            display: grid;
            gap: 8px;
            margin-bottom: 18px;
            color: var(--muji-blue-deep);
        }
        .price-display .price-seat {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.2px;
            line-height: 1.2;
        }
        .price-display .price-amount {
            display: inline-flex;
            align-items: flex-end;
            gap: 4px;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.35px;
            line-height: 1;
        }
        .price-display .price-amount small {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .price-secondary { font-size: 0.9rem; font-weight: 800; color: #9a7412; margin: 0 0 12px; line-height: 1.4; }
        .price-secondary strong { color: var(--muji-gold); font-weight: 800; }
        .price-secondary span { color: var(--muji-gold); }

        .feature-list { list-style: none; margin: 10px 0 24px; flex-grow: 1; }
        .feature-list li { padding: 7px 0; font-size: 0.82rem; display: flex; align-items: flex-start; gap: 9px; color: #475569; }
        .feature-list i { color: var(--muji-blue-accent); font-size: 0.9rem; }
        .feature-list li.unavailable { color: #94a3b8; text-decoration: line-through; }
        .feature-list li.unavailable i { color: #cbd5e1; }

        .btn-uplink {
            background: linear-gradient(135deg, var(--muji-blue-deep) 0%, var(--muji-blue-accent) 100%);
            color: #fff; text-decoration: none;
            padding: 16px; text-align: center; font-weight: 700; font-size: 0.9rem;
            border-radius: 16px; border: 1px solid transparent; cursor: pointer; transition: 0.3s;
            box-shadow: 0 12px 22px -16px rgba(15, 58, 138, 0.7);
        }
        .btn-uplink:hover {
            background: #fff;
            color: var(--muji-blue-accent);
            border-color: var(--muji-gold);
            transform: translateY(-1px) scale(1.01);
            box-shadow: 0 18px 28px -18px rgba(215, 169, 40, 0.45);
        }
        .btn-uplink:disabled {
            opacity: 0.65;
            cursor: default;
            transform: none;
            box-shadow: none;
        }

        .btn-outline {
            background: #fff;
            color: var(--muji-blue-accent);
            border-color: rgba(15, 58, 138, 0.22);
            box-shadow: 0 16px 26px -20px rgba(15, 58, 138, 0.35);
        }
        .btn-outline:hover {
            background: linear-gradient(135deg, var(--muji-blue-deep) 0%, var(--muji-blue-accent) 100%);
            color: #fff;
            border-color: var(--muji-blue-accent);
            box-shadow: 0 20px 30px -18px rgba(15, 58, 138, 0.55);
        }

        .plan-card.featured .btn-uplink:not(.btn-gold) {
            background: linear-gradient(135deg, var(--muji-blue-deep) 0%, var(--muji-blue-accent) 100%);
            color: #fff;
            box-shadow: 0 18px 30px -18px rgba(15, 58, 138, 0.45);
        }
        .plan-card.featured .btn-uplink:not(.btn-gold):hover {
            background: #fff;
            color: var(--muji-blue-accent);
            border-color: var(--muji-gold);
        }
        .btn-gold {
            background: linear-gradient(135deg, var(--muji-gold-soft) 0%, var(--muji-gold) 100%);
            color: var(--muji-blue-deep);
            border-color: rgba(215, 169, 40, 0.55);
            box-shadow: 0 16px 28px -18px rgba(197, 160, 89, 0.58);
        }
        .btn-gold:hover {
            background: var(--muji-blue-deep);
            color: #fff;
            border-color: var(--muji-gold);
            box-shadow: 0 20px 32px -18px rgba(6, 26, 68, 0.68);
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .pricing-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .coverage-panel { grid-column: span 1; min-height: 520px; }
            .coverage-panel::after { background: rgba(255, 255, 255, 0.84); }
            .coverage-content { width: 100%; padding: 32px; }
        }
        @media (max-width: 650px) {
            .pricing-grid { grid-template-columns: 1fr; }
            .coverage-panel { min-height: 470px; background-position: 58% center; }
            .coverage-content { padding: 28px 24px; }
            .membership-hero { padding: 22px 0 38px; }
            .hero-title { font-size: 2rem; }
            .hero-plan-note { align-items: flex-start; text-align: left; }
            .brand-logo { height: 48px; }
            .spb-nav-wordmark { font-size: 1.55rem; }
        }
        @media (max-width: 420px) {
            .container { padding: 0 14px; }
            .brand-lockup { gap: 8px; }
            .brand-logo { height: 46px; }
            .spb-nav-wordmark {
                font-size: 1.35rem;
                letter-spacing: -0.5px;
            }
        }

        /* Loader */
        #loadingModal {
            position: fixed; inset: 0; background: rgba(6, 26, 68, 0.92);
            display: none; align-items: center; justify-content: center; z-index: 10000; color: white;
        }
        .spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.1); border-top: 4px solid var(--muji-blue-accent); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .flash-wrap { margin-top: 20px; }
        .flash-msg {
            margin: 0 auto 10px;
            max-width: 760px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .flash-msg.success { background: #ecfdf3; color: #166534; border: 1px solid #86efac; }
        .flash-msg.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .custom-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 11000;
            padding: 16px;
        }
        .custom-modal-backdrop.open { display: flex; }
        .custom-modal {
            width: min(760px, 100%);
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--muji-border);
            box-shadow: var(--shadow-premium);
            overflow: hidden;
        }
        .custom-modal-head {
            background: linear-gradient(135deg, var(--muji-blue-deep), var(--muji-blue-accent));
            color: #fff;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .custom-modal-head h5 { margin: 0; font-size: 1rem; font-weight: 800; letter-spacing: 0.2px; }
        .custom-close {
            border: 1px solid rgba(255,255,255,.3);
            background: transparent;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
        }
        .custom-modal-body { padding: 16px 18px 18px; }
        .custom-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .custom-group label {
            display: block;
            font-size: 0.74rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .custom-input, .custom-select, .custom-textarea {
            width: 100%;
            border: 1px solid var(--muji-border);
            border-radius: 10px;
            padding: 10px 11px;
            font-size: 0.9rem;
            color: var(--muji-blue-deep);
            background: #fff;
        }
        .custom-textarea { min-height: 110px; resize: vertical; }
        .custom-summary {
            margin-top: 12px;
            padding: 12px;
            border: 1px dashed rgba(215, 169, 40, 0.62);
            border-radius: 10px;
            background: #fff9e6;
            font-size: 0.82rem;
            color: var(--muji-blue-deep);
            font-weight: 600;
        }
        .custom-actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }
        .btn-modal {
            border: none;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.86rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-cancel { background: #f1f5f9; color: #334155; }
        .btn-send { background: var(--muji-blue-accent); color: #fff; flex: 1; }
        .btn-send:hover { background: #fff; color: var(--muji-blue-accent); box-shadow: inset 0 0 0 1px var(--muji-gold); }
        .btn-send:disabled { opacity: .7; cursor: not-allowed; }
        @media (max-width: 680px) {
            .custom-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="spa-nav">
        <div class="container flex-between">
            <a href="{{ url('/') }}" class="brand-lockup">
                <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook Logo" class="brand-logo">
                <span class="spb-nav-wordmark"><span class="smartpro">SmartPro</span><span class="book">book</span></span>
            </a>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" onclick="return exitSetup(event)" style="text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.85rem; transition: 0.3s;">
                <i class="fas fa-arrow-left me-2"></i> Exit Setup
            </a>
        </div>
    </nav>

    <header class="membership-hero">
        <div class="container">
            <h1 class="hero-title">Choose the Right <span>Plan for Your Business</span></h1>
            <p class="hero-subtitle">Annual plans for sales, accounting, inventory and growing teams.</p>
            <div class="hero-plan-note">
                <i class="fas fa-gift"></i>
                <span>First month free. Payment starts after your trial.</span>
                <span class="annual-note">Annual subscriptions</span>
            </div>
            @if(session('success') || session('error') || session('info') || $errors->any())
            <div class="flash-wrap">
                @if(session('success'))
                    <div class="flash-msg success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="flash-msg error">{{ session('error') }}</div>
                @endif
                @if(session('info'))
                    <div class="flash-msg success" style="background:#fff8db;color:#061a44;border:1px solid rgba(215,169,40,.45);">{{ session('info') }}</div>
                @endif
                @if($errors->any())
                    <div class="flash-msg error" style="text-align:left;">
                        @foreach($errors->all() as $error)
                            <div><i class="fas fa-exclamation-triangle me-2"></i>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif
        </div>
    </header>

    <section class="pricing-section">
        <div class="container">
            <div class="pricing-grid">
                <div class="plan-card">
                    <h3 class="plan-name">{{ $planCards['starter']['label'] }}</h3>
                    <p class="plan-desc">{{ $planCards['starter']['description'] }}</p>
                    <div class="price-display">
                        <span class="price-seat">1 user:</span>
                        <div class="price-amount">
                            <span id="price-starter-solo">₦20,000</span><small id="period-starter-solo">/year</small>
                        </div>
                    </div>
                    <p class="price-secondary">Renews at <strong>₦20,000/year</strong></p>
                    <p class="price-secondary">Extra user: <strong>₦30,000/year</strong></p>
                    <ul class="feature-list">
                        @foreach($tierBenefits['starter'] as $benefit)
                            <li><i class="fas fa-check-circle"></i> {{ $benefit }}</li>
                        @endforeach
                        <li class="unavailable"><i class="fas fa-times-circle"></i> Accounting reports and ledgers</li>
                        <li class="unavailable"><i class="fas fa-times-circle"></i> Payroll, tax, and bank reconciliation</li>
                    </ul>
                    <div style="display:grid; gap:10px;">
                        <button onclick="{{ $currentPlanTier === 'starter' ? '' : "handleSubscription('starter')" }}" class="btn-uplink btn-gold" {{ $currentPlanTier === 'starter' ? 'disabled' : '' }}>{{ $planActions['starter']['primary'] }}</button>
                    </div>
                </div>
                
                <div class="plan-card">
                    <h3 class="plan-name">{{ $planCards['basic']['label'] }}</h3>
                    <p class="plan-desc">{{ $planCards['basic']['description'] }}</p>
                    <div class="price-display">
                        <span class="price-seat">2 users:</span>
                        <div class="price-amount">
                            <span id="price-basic-solo">₦80,000</span><small id="period-basic-solo">/year</small>
                        </div>
                    </div>
                    <p class="price-secondary">Renews at <strong>₦80,000/year</strong></p>
                    <p class="price-secondary">Extra user: <strong>₦30,000/year</strong></p>
                    <ul class="feature-list">
                        @foreach($tierBenefits['basic'] as $benefit)
                            <li><i class="fas fa-check-circle"></i> {{ $benefit }}</li>
                        @endforeach
                    </ul>
                    <div style="display:grid; gap:10px;">
                        <button onclick="{{ $currentPlanTier === 'basic' ? '' : "handleSubscription('basic')" }}" class="btn-uplink btn-gold" {{ $currentPlanTier === 'basic' ? 'disabled' : '' }}>{{ $planActions['basic']['primary'] }}</button>
                    </div>
                </div>

                
                <div class="plan-card featured">
                    <div class="popular-tag">MOST POPULAR</div>
                    <h3 class="plan-name">{{ $planCards['pro']['label'] }}</h3>
                    <p class="plan-desc">{{ $planCards['pro']['description'] }}</p>
                    <div class="price-display">
                        <span class="price-seat">3 users:</span>
                        <div class="price-amount">
                            <span id="price-pro-solo">₦100,000</span><small id="period-pro-solo">/year</small>
                        </div>
                    </div>
                    <p class="price-secondary">5 users: <strong id="price-pro">₦150,000</strong><span>/year</span></p>
                    <p class="price-secondary">Renews at the selected tier price</p>
                    <p class="price-secondary">Extra user: <strong>₦30,000/year</strong></p>
                    <p class="price-secondary">Extra branch: <strong>₦50,000/year</strong></p>
                    <ul class="feature-list">
                        @foreach($tierBenefits['pro'] as $benefit)
                            <li><i class="fas fa-check-circle"></i> {{ $benefit }}</li>
                        @endforeach
                    </ul>
                    <div style="display:grid; gap:10px;">
                        <button onclick="{{ $currentPlanTier === 'professional' ? '' : "handleSubscription('pro-solo')" }}" class="btn-uplink btn-outline" {{ $currentPlanTier === 'professional' ? 'disabled' : '' }}>{{ $planActions['pro']['secondary'] }}</button>
                        <button onclick="handleSubscription('{{ $currentPlanTier === 'professional' ? 'enterprise' : 'pro' }}')" class="btn-uplink btn-gold">{{ $planActions['pro']['primary'] }}</button>
                    </div>
                </div>

                
                <div class="plan-card">
                    <h3 class="plan-name">{{ $planCards['enterprise']['label'] }}</h3>
                    <p class="plan-desc">{{ $planCards['enterprise']['description'] }}</p>
                    <div class="price-display">
                        <span class="price-seat">4 users:</span>
                        <div class="price-amount">
                            <span id="price-enterprise-solo">₦200,000</span><small id="period-enterprise-solo">/year</small>
                        </div>
                    </div>
                    <p class="price-secondary">10 users: <strong id="price-enterprise">₦300,000</strong><span>/year</span></p>
                    <p class="price-secondary">Renews at the selected tier price</p>
                    <p class="price-secondary">Extra user: <strong>₦30,000/year</strong></p>
                    <p class="price-secondary">Extra branch: <strong>₦50,000/year</strong></p>
                    <ul class="feature-list">
                        @foreach($tierBenefits['enterprise'] as $benefit)
                            <li><i class="fas fa-check-circle"></i> {{ $benefit }}</li>
                        @endforeach
                    </ul>
                    <div style="display:grid; gap:10px;">
                        <button onclick="{{ $currentPlanTier === 'enterprise' ? '' : "handleSubscription('enterprise-solo')" }}" class="btn-uplink btn-outline" {{ $currentPlanTier === 'enterprise' ? 'disabled' : '' }}>{{ $planActions['enterprise']['secondary'] }}</button>
                        <button onclick="handleSubscription('enterprise')" class="btn-uplink btn-gold">{{ $planActions['enterprise']['primary'] }}</button>
                    </div>
                </div>

                
                <div class="plan-card">
                    <h3 class="plan-name">Bespoke</h3>
                    <p class="plan-desc">Custom infrastructure built for unique compliance needs.</p>
                    <div class="price-display">
                        <span>Custom</span><small>SLA</small>
                    </div>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> White-label Interface</li>
                        <li><i class="fas fa-check-circle"></i> On-Premise Support</li>
                        <li><i class="fas fa-check-circle"></i> Proprietary Modules</li>
                        <li><i class="fas fa-check-circle"></i> Biometric Security</li>
                        <li><i class="fas fa-check-circle"></i> Dedicated Tech Team</li>
                    </ul>
                    <button onclick="handleSubscription('custom')" class="btn-uplink btn-outline">Request Custom Plan</button>
                </div>

                <aside class="coverage-panel" aria-label="SmartProbook global coverage outlook">
                    <div class="coverage-content">
                        <span class="coverage-kicker">Growing across markets</span>
                        <h2 class="coverage-title">Built in Africa.<br>Ready for the world.</h2>
                        <p class="coverage-copy">SmartProbook helps ambitious businesses run sales, finance and operations from one connected platform. Our foundation is local, while our product is designed for teams, branches and customers wherever business takes them.</p>
                        <div class="coverage-points">
                            <span class="coverage-point">Nigeria-rooted</span>
                            <span class="coverage-point">Global-ready</span>
                            <span class="coverage-point">Multi-branch</span>
                            <span class="coverage-point">Expanding reach</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section style="padding: 0 20px 48px; text-align:center; color:var(--muji-blue-deep);">
        <strong>Add-ons renew annually with your subscription.</strong>
        Extra branches are available only on Pro Engine and Institutional plans.
    </section>

    <footer style="padding: 60px 0; border-top: 1px solid var(--muji-border); text-align: center; background: var(--muji-blue-light);">
        <div class="container">
            <p style="font-weight: 700; color: var(--muji-blue-deep); margin-bottom: 10px;">SmartProbook INTELLIGENCE</p>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">Secure, AES-256 standard encrypted accounting deployment.</p>
            <p style="font-size: 0.8rem; color: #94a3b8;">© 2026 SmartProbook Enterprise. All rights reserved.</p>
        </div>
    </footer>

    <div id="loadingModal">
        <div style="text-align: center;">
            <div class="spinner"></div>
            <p id="loadingText" style="font-weight: 600; letter-spacing: 0.5px;">Establishing Encryption...</p>
        </div>
    </div>

    <div class="custom-modal-backdrop" id="customPlanBackdrop" aria-hidden="true">
        <div class="custom-modal" role="dialog" aria-modal="true" aria-labelledby="customPlanTitle">
            <div class="custom-modal-head">
                <h5 id="customPlanTitle">Custom Plan Consultation</h5>
                <button type="button" class="custom-close" onclick="closeCustomPlanModal()" aria-label="Close">×</button>
            </div>
            <div class="custom-modal-body">
                <form id="customPlanForm" action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="department" value="Bespoke Plan Consultation">
                    <input type="hidden" name="company_name" id="customCompanyHidden">
                    <div class="custom-grid">
                        <div class="custom-group">
                            <label>Full Name</label>
                            <input class="custom-input" type="text" name="fullname" id="customFullname" required>
                        </div>
                        <div class="custom-group">
                            <label>Work Email</label>
                            <input class="custom-input" type="email" name="email" id="customEmail" required>
                        </div>
                        <div class="custom-group">
                            <label>Organization</label>
                            <input class="custom-input" type="text" id="customCompany" placeholder="Company / Institution">
                        </div>
                        <div class="custom-group">
                            <label>Team Size</label>
                            <select class="custom-select" id="customTeamSize">
                                <option value="1-25">1-25</option>
                                <option value="26-100">26-100</option>
                                <option value="101-500">101-500</option>
                                <option value="500+">500+</option>
                            </select>
                        </div>
                    </div>
                    <div class="custom-group" style="margin-top:10px;">
                        <label>Requirements</label>
                        <textarea class="custom-textarea" name="message" id="customMessage" required placeholder="Tell us your exact modules, integrations, compliance needs, and timeline."></textarea>
                    </div>
                    <div class="custom-summary" id="customSummary">
                        Cycle: Annual | Team: 1-25
                    </div>
                    <div class="custom-actions">
                        <button type="button" class="btn-modal btn-cancel" onclick="closeCustomPlanModal()">Cancel</button>
                        <button type="submit" class="btn-modal btn-send" id="customSubmitBtn">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
    // Subscription checkout is annual-only.

    // The base URL for your registration endpoint
    const registerUrl = "{{ route('saas-register-initial') }}";
    const upgradeUrl = "{{ route('subscription.upgrade.redirect') }}";
    const userIsAuthenticated = @json(auth()->check());
    const suggestedUpgradePlan = @json($suggestedUpgradePlan);

    let isNavigatingToPlan = false;

    function handleSubscription(plan) {
        if (plan === 'custom') {
            openCustomPlanModal();
            return;
        }

        if (userIsAuthenticated && suggestedUpgradePlan && ['starter', 'basic', 'pro'].includes(plan) && plan !== suggestedUpgradePlan) {
            plan = suggestedUpgradePlan;
        }

        if (isNavigatingToPlan) {
            return;
        }

        const cycleValue = 'yearly';

        isNavigatingToPlan = true;

        const queryParams = new URLSearchParams({ 
            plan: plan, 
            cycle: cycleValue,
            billing_cycle: cycleValue
        });

        const destination = userIsAuthenticated ? upgradeUrl : registerUrl;
        window.location.assign(`${destination}?${queryParams.toString()}`);
    }

    function exitSetup(event) {
        if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) {
            event.preventDefault();
            window.history.back();
            return false;
        }

        return true;
    }

    function openCustomPlanModal() {
        const backdrop = document.getElementById('customPlanBackdrop');
        if (!backdrop) return;
        backdrop.classList.add('open');
        backdrop.setAttribute('aria-hidden', 'false');
        updateCustomSummary();
    }

    function closeCustomPlanModal() {
        const backdrop = document.getElementById('customPlanBackdrop');
        if (!backdrop) return;
        backdrop.classList.remove('open');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    function updateCustomSummary() {
        const cycle = 'Annual';
        const team = document.getElementById('customTeamSize')?.value || '1-25';
        const summary = document.getElementById('customSummary');
        if (summary) {
            summary.textContent = `Cycle: ${cycle} | Team: ${team} | Request type: Bespoke Plan Consultation`;
        }
    }

    document.getElementById('customTeamSize')?.addEventListener('change', updateCustomSummary);
    document.getElementById('billingSwitch')?.addEventListener('change', function () {
        updateCustomSummary();
    });

    document.getElementById('customPlanBackdrop')?.addEventListener('click', function (e) {
        if (e.target === this) closeCustomPlanModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeCustomPlanModal();
    });

    document.getElementById('customPlanForm')?.addEventListener('submit', function () {
        const submitBtn = document.getElementById('customSubmitBtn');
        const company = document.getElementById('customCompany')?.value || '';
        const companyHidden = document.getElementById('customCompanyHidden');
        if (companyHidden) companyHidden.value = company;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending Request...';
        }
    });
</script>
</body>
</html>
