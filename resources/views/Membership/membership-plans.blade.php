<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $seoNoIndex = false;
        $seoType = 'website';
        $seoTitle = 'Membership Plans and Pricing';
        $seoDescription = 'Compare SmartProbook membership plans, pricing tiers, accounting tools, ERP modules, reporting features, and business upgrade options.';
        $seoKeywords = 'SmartProbook pricing, membership plans, accounting software pricing, ERP pricing, invoicing software plans, business software subscriptions';
        $seoCanonical = route('membership-plans');
    @endphp
    @include('layout.partials.seo-meta')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --muji-blue-deep: #0f172a; 
            --muji-blue-accent: #2563eb;
            --muji-blue-soft: #3b82f6;
            --muji-blue-soft-end: #4f46e5;
            --muji-blue-light: #f8fafc; 
            --muji-gold: #c5a059; 
            --muji-red: #e11d48; 
            --muji-text: #334155;
            --muji-border: #e2e8f0;
            --shadow-premium: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #fff; color: var(--muji-text); line-height: 1.5; overflow-x: hidden; font-size: 15px; }

        /* Professional Slim Nav */
        .spa-nav {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            padding: 1rem 0; border-bottom: 1px solid var(--muji-border);
            position: sticky; top: 0; z-index: 100;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .brand-lockup { display: inline-flex; align-items: center; gap: 12px; min-width: 0; text-decoration: none; }
        .brand-logo { height: 60px; width: auto; display: block; flex-shrink: 0; }
        .spb-nav-wordmark {
            font-weight: 800;
            color: #0b2a63;
            font-size: 2rem;
            letter-spacing: -0.8px;
            line-height: 1;
            white-space: nowrap;
            display: inline-flex;
            align-items: baseline;
        }
        .spb-nav-wordmark .smartpro {
            color: #0b2a63 !important;
        }
        .spb-nav-wordmark .book {
            color: #dc2626 !important;
        }

        /* Refined Hero */
        .membership-hero {
            background: linear-gradient(180deg, var(--muji-blue-light) 0%, #fff 100%);
            padding: 80px 0 100px; text-align: center;
        }

        .gold-label { 
            color: var(--muji-blue-accent); 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            font-size: 0.75rem; 
            margin-bottom: 1rem; 
            display: inline-block;
            background: rgba(37, 99, 235, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
        }
        .hero-title { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; color: var(--muji-blue-deep); letter-spacing: -1.5px; line-height: 1.1; }
        .hero-title span { color: var(--muji-blue-accent); }
        .hero-subtitle { color: #64748b; margin-top: 15px; font-size: 1.1rem; max-width: 600px; margin-left: auto; margin-right: auto; }

        /* Modern Toggle */
        .billing-toggle {
            display: inline-flex; align-items: center; justify-content: center; gap: 15px;
            margin-top: 40px; font-size: 0.9rem; font-weight: 600;
            background: #fff; padding: 8px 20px; border-radius: 50px; border: 1px solid var(--muji-border);
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
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
        .pricing-section { margin-top: -60px; padding-bottom: 80px; }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            align-items: stretch;
        }

        .plan-card {
            background: #fff; border: 1px solid var(--muji-border);
            padding: 40px 30px; border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex; flex-direction: column;
            position: relative;
        }

        .plan-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-premium); border-color: var(--muji-blue-accent); }
        
        .plan-card.featured { 
            border: 2px solid var(--muji-blue-accent); 
            background: #fff;
        }
        .plan-card.featured .popular-tag {
            position: absolute; top: -14px; left: 50%;
            transform: translateX(-50%); background: var(--muji-blue-accent);
            color: white; font-size: 0.7rem; padding: 4px 15px; font-weight: 700; border-radius: 20px;
            letter-spacing: 1px;
        }

        .plan-name { font-weight: 700; color: var(--muji-blue-deep); font-size: 1.25rem; margin-bottom: 8px; }
        .plan-desc { font-size: 0.9rem; color: #64748b; margin-bottom: 30px; line-height: 1.4; height: 40px; }
        
        .price-display { font-size: 2.25rem; font-weight: 800; color: var(--muji-blue-deep); margin-bottom: 12px; letter-spacing: -1px; }
        .price-display small { font-size: 0.9rem; color: #64748b; font-weight: 500; letter-spacing: 0; }
        .price-secondary { font-size: 1.05rem; font-weight: 800; color: var(--muji-gold); margin: -2px 0 24px; line-height: 1.4; }
        .price-secondary strong { color: var(--muji-gold); font-weight: 800; }
        .price-secondary span { color: var(--muji-gold); }

        .feature-list { list-style: none; margin-bottom: 40px; flex-grow: 1; }
        .feature-list li { padding: 10px 0; font-size: 0.85rem; display: flex; align-items: center; gap: 12px; color: #475569; }
        .feature-list i { color: var(--muji-blue-accent); font-size: 0.9rem; }
        .feature-list li.unavailable { color: #94a3b8; text-decoration: line-through; }
        .feature-list li.unavailable i { color: #cbd5e1; }

        .btn-uplink {
            background: linear-gradient(135deg, #14213d 0%, #1e3a5f 100%);
            color: #fff; text-decoration: none;
            padding: 16px; text-align: center; font-weight: 700; font-size: 0.9rem;
            border-radius: 16px; border: none; cursor: pointer; transition: 0.3s;
            box-shadow: 0 12px 22px -16px rgba(15, 23, 42, 0.7);
        }
        .btn-uplink:hover {
            background: linear-gradient(135deg, #1d3557 0%, #2563eb 100%);
            transform: translateY(-1px) scale(1.01);
            box-shadow: 0 18px 28px -18px rgba(37, 99, 235, 0.5);
        }
        
        .btn-outline {
            background: linear-gradient(135deg, var(--muji-blue-soft) 0%, var(--muji-blue-soft-end) 100%);
            color: #fff;
            box-shadow: 0 16px 26px -18px rgba(79, 70, 229, 0.5);
        }
        .btn-outline:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 20px 30px -18px rgba(37, 99, 235, 0.55);
        }

        .plan-card.featured .btn-uplink:not(.btn-gold) {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            box-shadow: 0 18px 30px -18px rgba(79, 70, 229, 0.45);
        }
        .btn-gold {
            background: linear-gradient(135deg, #f6d690 0%, #d4a34a 100%);
            color: #402b05;
            box-shadow: 0 16px 28px -18px rgba(197, 160, 89, 0.58);
        }
        .btn-gold:hover {
            background: linear-gradient(135deg, #f2ca6a 0%, #c58b2d 100%);
            color: #2f1f04;
            box-shadow: 0 20px 32px -18px rgba(197, 160, 89, 0.68);
        }

        /* Responsive */
        @media (max-width: 1100px) { .pricing-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 650px) {
            .pricing-grid { grid-template-columns: 1fr; }
            .hero-title { font-size: 2.5rem; }
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
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9);
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
            background: linear-gradient(135deg, #0f172a, #1e40af);
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
            color: #0f172a;
            background: #fff;
        }
        .custom-textarea { min-height: 110px; resize: vertical; }
        .custom-summary {
            margin-top: 12px;
            padding: 12px;
            border: 1px dashed #93c5fd;
            border-radius: 10px;
            background: #eff6ff;
            font-size: 0.82rem;
            color: #1e3a8a;
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
        .btn-send { background: #1d4ed8; color: #fff; flex: 1; }
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
            <span class="gold-label">Enterprise Solutions</span>
            <h1 class="hero-title">Select Your <span>Growth Infrastructure</span></h1>
            <p class="hero-subtitle">Professional accounting nodes designed for institutional precision and high-volume data management.</p>
            <div class="flash-wrap">
                @if(session('success'))
                    <div class="flash-msg success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="flash-msg error">{{ session('error') }}</div>
                @endif
            </div>
            
            <div class="billing-toggle">
                <span id="monthlyLabel" style="color: var(--muji-blue-accent)">Monthly</span>
                <label class="switch">
                    <input type="checkbox" id="billingSwitch" onchange="togglePricing()">
                    <span class="slider"></span>
                </label>
                <span id="annualLabel">Annual <span class="save-badge">2 Months Free</span></span>
            </div>
        </div>
    </header>

    <section class="pricing-section">
        <div class="container">
            <div class="pricing-grid">
                <!-- Basic -->
                <div class="plan-card">
                    <h3 class="plan-name">Basic Core</h3>
                    <p class="plan-desc">Perfect for small start-up workflows and agile teams.</p>
                    <div class="price-display">
                        <span id="price-basic-solo">₦3,000</span><small id="period-basic-solo">/mo</small>
                    </div>
                    <p class="price-secondary">2 users: <strong id="price-basic">₦5,500</strong><span id="period-basic">/mo</span></p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> 2 User Seats</li>
                        <li><i class="fas fa-check-circle"></i> Cloud Ledger Core</li>
                        <li><i class="fas fa-check-circle"></i> Basic Reporting</li>
                        <li><i class="fas fa-check-circle"></i> Sales Tracking</li>
                        <li class="unavailable"><i class="fas fa-times-circle"></i> Custom Subdomains</li>
                    </ul>
                    <div style="display:grid; gap:10px;">
                        <button onclick="handleSubscription('basic-solo')" class="btn-uplink btn-outline">Start 1 User</button>
                        <button onclick="handleSubscription('basic')" class="btn-uplink btn-gold">Start 2 Users</button>
                    </div>
                </div>

                <!-- Pro -->
                <div class="plan-card featured">
                    <div class="popular-tag">MOST POPULAR</div>
                    <h3 class="plan-name">Pro Engine</h3>
                    <p class="plan-desc">Advanced features for growing institutional entities.</p>
                    <div class="price-display">
                        <span id="price-pro-solo">₦7,000</span><small id="period-pro-solo">/mo</small>
                    </div>
                    <p class="price-secondary">3 users: <strong id="price-pro">₦19,500</strong><span id="period-pro">/mo</span></p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> 3 User Seats</li>
                        <li><i class="fas fa-check-circle"></i> Neural Forecasting</li>
                        <li><i class="fas fa-check-circle"></i> Multi-Currency Support</li>
                        <li><i class="fas fa-check-circle"></i> Advanced Inventory</li>
                        <li><i class="fas fa-check-circle"></i> Priority API Access</li>
                    </ul>
                    <div style="display:grid; gap:10px;">
                        <button onclick="handleSubscription('pro-solo')" class="btn-uplink btn-outline">Start 1 User</button>
                        <button onclick="handleSubscription('pro')" class="btn-uplink btn-gold">Start 3 Users</button>
                    </div>
                </div>

                <!-- Enterprise -->
                <div class="plan-card">
                    <h3 class="plan-name">Institutional</h3>
                    <p class="plan-desc">The standard for large corporations and high-node networks.</p>
                    <div class="price-display">
                        <span id="price-enterprise-solo">₦15,000</span><small id="period-enterprise-solo">/mo</small>
                    </div>
                    <p class="price-secondary">Unlimited users: <strong id="price-enterprise">₦28,500</strong><span id="period-enterprise">/mo</span></p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Unlimited User Seats</li>
                        <li><i class="fas fa-check-circle"></i> Private Instance</li>
                        <li><i class="fas fa-check-circle"></i> Dedicated Subdomain</li>
                        <li><i class="fas fa-check-circle"></i> Advanced Audit Logs</li>
                        <li><i class="fas fa-check-circle"></i> 24/7 Success Manager</li>
                    </ul>
                    <div style="display:grid; gap:10px;">
                        <button onclick="handleSubscription('enterprise-solo')" class="btn-uplink btn-outline">Start 1 User</button>
                        <button onclick="handleSubscription('enterprise')" class="btn-uplink btn-gold">Deploy Unlimited</button>
                    </div>
                </div>

                <!-- Bespoke -->
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
            </div>
        </div>
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
                        Cycle: Monthly | Team: 1-25
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
    /**
     * ALIGNED PRICING LOGIC: REWRITTEN FOR SUBSCRIPTIONCONTROLLER
     * This ensures the 'cycle' parameter is passed exactly as the controller 
     * expects to prevent the "Monthly Reset" bug.
     */
    
    // The base URL for your registration endpoint
    const registerUrl = "{{ route('saas-register-initial') }}";
    
    const prices = {
        monthly: {
            basic: '₦5,500',
            basicSolo: '₦3,000',
            pro: '₦19,500',
            proSolo: '₦7,000',
            enterprise: '₦28,500',
            enterpriseSolo: '₦15,000'
        },
        annual: {
            basic: '₦55,000',
            basicSolo: '₦30,000',
            pro: '₦195,000',
            proSolo: '₦70,000',
            enterprise: '₦285,000',
            enterpriseSolo: '₦150,000'
        }
    };

    function togglePricing() {
        const isAnnual = document.getElementById('billingSwitch').checked;
        const period = isAnnual ? 'annual' : 'monthly';
        const smallText = isAnnual ? '/yr' : '/mo';

        // UI Label feedback
        document.getElementById('monthlyLabel').style.color = isAnnual ? '#64748b' : 'var(--muji-blue-accent)';
        document.getElementById('annualLabel').style.color = isAnnual ? 'var(--muji-blue-accent)' : '#64748b';

        // Update Text
        document.getElementById('price-basic').innerText = prices[period].basic;
        document.getElementById('price-basic-solo').innerText = prices[period].basicSolo;
        document.getElementById('price-pro').innerText = prices[period].pro;
        document.getElementById('price-pro-solo').innerText = prices[period].proSolo;
        document.getElementById('price-enterprise').innerText = prices[period].enterprise;
        document.getElementById('price-enterprise-solo').innerText = prices[period].enterpriseSolo;
        
        document.getElementById('period-basic').innerText = smallText;
        document.getElementById('period-basic-solo').innerText = smallText;
        document.getElementById('period-pro').innerText = smallText;
        document.getElementById('period-pro-solo').innerText = smallText;
        document.getElementById('period-enterprise').innerText = smallText;
        document.getElementById('period-enterprise-solo').innerText = smallText;
    }

    let isNavigatingToPlan = false;

    function handleSubscription(plan) {
        if (plan === 'custom') {
            openCustomPlanModal();
            return;
        }

        if (isNavigatingToPlan) {
            return;
        }

        const modal = document.getElementById('loadingModal');
        const isAnnual = document.getElementById('billingSwitch').checked;
        
        // CRITICAL FIX: The Controller expects 'cycle', not 'billing_cycle' in some methods.
        // We provide both to be 100% safe.
        const cycleValue = isAnnual ? 'yearly' : 'monthly'; 

        isNavigatingToPlan = true;

        if (modal) {
            modal.style.display = 'flex';
        }
        document.getElementById('loadingText').innerText = `Syncing ${plan.toUpperCase()} Node...`;

        /**
         * REDIRECT LOGIC
         * Ensures smatbook.com/register?plan=pro&cycle=yearly
         * This hits SubscriptionController@showRegister
         */
        const queryParams = new URLSearchParams({ 
            plan: plan, 
            cycle: cycleValue,
            billing_cycle: cycleValue // Double-mapped for compatibility
        });

        window.location.assign(`${registerUrl}?${queryParams.toString()}`);
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
        const isAnnual = document.getElementById('billingSwitch')?.checked;
        const cycle = isAnnual ? 'Yearly' : 'Monthly';
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
