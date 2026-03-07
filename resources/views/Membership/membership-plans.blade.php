<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smatprobook</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --muji-blue-deep: #0f172a; 
            --muji-blue-accent: #2563eb;
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
        
        .price-display { font-size: 2.25rem; font-weight: 800; color: var(--muji-blue-deep); margin-bottom: 30px; letter-spacing: -1px; }
        .price-display small { font-size: 0.9rem; color: #64748b; font-weight: 500; letter-spacing: 0; }

        .feature-list { list-style: none; margin-bottom: 40px; flex-grow: 1; }
        .feature-list li { padding: 10px 0; font-size: 0.85rem; display: flex; align-items: center; gap: 12px; color: #475569; }
        .feature-list i { color: var(--muji-blue-accent); font-size: 0.9rem; }
        .feature-list li.unavailable { color: #94a3b8; text-decoration: line-through; }
        .feature-list li.unavailable i { color: #cbd5e1; }

        .btn-uplink {
            background: var(--muji-blue-deep); color: #fff; text-decoration: none;
            padding: 16px; text-align: center; font-weight: 700; font-size: 0.9rem;
            border-radius: 12px; border: none; cursor: pointer; transition: 0.3s;
        }
        .btn-uplink:hover { background: var(--muji-blue-accent); transform: scale(1.02); }
        
        .btn-outline { background: #f1f5f9; color: var(--muji-blue-deep); }
        .btn-outline:hover { background: #e2e8f0; color: var(--muji-blue-deep); }

        .plan-card.featured .btn-uplink { background: var(--muji-blue-accent); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3); }

        /* Responsive */
        @media (max-width: 1100px) { .pricing-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 650px) { .pricing-grid { grid-template-columns: 1fr; } .hero-title { font-size: 2.5rem; } }

        /* Loader */
        #loadingModal {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9);
            display: none; align-items: center; justify-content: center; z-index: 10000; color: white;
        }
        .spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.1); border-top: 4px solid var(--muji-blue-accent); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <nav class="spa-nav">
        <div class="container flex-between">
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="{{ asset('assets/img/logo-placeholder.svg') }}" alt="SmartProbook Logo" style="height: 30px;">
                <span style="font-weight: 800; color: var(--muji-blue-deep); font-size: 1.1rem; letter-spacing: -0.5px;">SMARTPROBOOK</span>
            </div>
            <a href="/" style="text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.85rem; transition: 0.3s;">
                <i class="fas fa-arrow-left me-2"></i> Exit Setup
            </a>
        </div>
    </nav>

    <header class="membership-hero">
        <div class="container">
            <span class="gold-label">Enterprise Solutions</span>
            <h1 class="hero-title">Select Your <span>Growth Infrastructure</span></h1>
            <p class="hero-subtitle">Professional accounting nodes designed for institutional precision and high-volume data management.</p>
            
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
                        <span id="price-basic">₦3,000</span><small id="period-basic">/mo</small>
                    </div>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> 5 Executive Seats</li>
                        <li><i class="fas fa-check-circle"></i> Cloud Ledger Core</li>
                        <li><i class="fas fa-check-circle"></i> Basic Reporting</li>
                        <li><i class="fas fa-check-circle"></i> Sales Tracking</li>
                        <li class="unavailable"><i class="fas fa-times-circle"></i> Custom Subdomains</li>
                    </ul>
                    <button onclick="handleSubscription('basic')" class="btn-uplink btn-outline">Start Trial</button>
                </div>

                <!-- Pro -->
                <div class="plan-card featured">
                    <div class="popular-tag">MOST POPULAR</div>
                    <h3 class="plan-name">Pro Engine</h3>
                    <p class="plan-desc">Advanced features for growing institutional entities.</p>
                    <div class="price-display">
                        <span id="price-pro">₦7,000</span><small id="period-pro">/mo</small>
                    </div>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> 25 Executive Seats</li>
                        <li><i class="fas fa-check-circle"></i> Neural Forecasting</li>
                        <li><i class="fas fa-check-circle"></i> Multi-Currency Support</li>
                        <li><i class="fas fa-check-circle"></i> Advanced Inventory</li>
                        <li><i class="fas fa-check-circle"></i> Priority API Access</li>
                    </ul>
                    <button onclick="handleSubscription('pro')" class="btn-uplink">Initialize Uplink</button>
                </div>

                <!-- Enterprise -->
                <div class="plan-card">
                    <h3 class="plan-name">Institutional</h3>
                    <p class="plan-desc">The standard for large corporations and high-node networks.</p>
                    <div class="price-display">
                        <span id="price-enterprise">₦15,000</span><small id="period-enterprise">/mo</small>
                    </div>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Unlimited Nodes</li>
                        <li><i class="fas fa-check-circle"></i> Private Instance</li>
                        <li><i class="fas fa-check-circle"></i> Dedicated Subdomain</li>
                        <li><i class="fas fa-check-circle"></i> Advanced Audit Logs</li>
                        <li><i class="fas fa-check-circle"></i> 24/7 Success Manager</li>
                    </ul>
                    <button onclick="handleSubscription('enterprise')" class="btn-uplink btn-outline">Deploy Now</button>
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
                    <button onclick="handleSubscription('custom')" class="btn-uplink btn-outline">Consultation</button>
                </div>
            </div>
        </div>
    </section>

    <footer style="padding: 60px 0; border-top: 1px solid var(--muji-border); text-align: center; background: var(--muji-blue-light);">
        <div class="container">
            <p style="font-weight: 700; color: var(--muji-blue-deep); margin-bottom: 10px;">SMARTPROBOOK INTELLIGENCE</p>
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
<script>
    /**
     * ALIGNED PRICING LOGIC: REWRITTEN FOR SUBSCRIPTIONCONTROLLER
     * This ensures the 'cycle' parameter is passed exactly as the controller 
     * expects to prevent the "Monthly Reset" bug.
     */
    
    // The base URL for your registration endpoint
    const registerUrl = "{{ route('saas-register-initial') }}";
    
    const prices = {
        monthly: { basic: '₦3,000', pro: '₦7,000', enterprise: '₦15,000' },
        annual: { basic: '₦30,000', pro: '₦70,000', enterprise: '₦150,000' } // Adjusted Pro to 70k to match Controller Matrix
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
        document.getElementById('price-pro').innerText = prices[period].pro;
        document.getElementById('price-enterprise').innerText = prices[period].enterprise;
        
        document.getElementById('period-basic').innerText = smallText;
        document.getElementById('period-pro').innerText = smallText;
        document.getElementById('period-enterprise').innerText = smallText;
    }

    function handleSubscription(plan) {
        if (plan === 'custom') {
            window.location.href = "mailto:support@smatbook.com?subject=Enterprise Consultation";
            return;
        }

        const modal = document.getElementById('loadingModal');
        const isAnnual = document.getElementById('billingSwitch').checked;
        
        // CRITICAL FIX: The Controller expects 'cycle', not 'billing_cycle' in some methods.
        // We provide both to be 100% safe.
        const cycleValue = isAnnual ? 'yearly' : 'monthly'; 

        if (modal) modal.style.display = 'flex';
        document.getElementById('loadingText').innerText = `Syncing ${plan.toUpperCase()} Node...`;

        setTimeout(() => {
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

            window.location.href = `${registerUrl}?${queryParams.toString()}`;
        }, 800);
    }
</script>
</body>
</html>
