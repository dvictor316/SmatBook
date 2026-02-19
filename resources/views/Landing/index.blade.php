@extends('layout.landing_nav')

@section('content')
<!-- SmatBook Premium Enterprise UI: Blue, Gold, Crimson & Motion -->
<style>
    :root {
        --muji-blue-deep: #002347; 
        --muji-blue-light: #f4f8ff; 
        --muji-gold: #c5a059; 
        --muji-gold-bright: #ffdf91;
        --muji-red: #bc002d; 
        --muji-text-dark: #1d1d1f;
        --font-main: 'Inter', 'Segoe UI', sans-serif;
    }

    html { 
        scroll-behavior: smooth;
        scroll-padding-top: 80px;
    }

    body { 
        font-family: var(--font-main); 
        background-color: #fff; 
        color: var(--muji-text-dark); 
        overflow-x: hidden; 
    }

    /* Navigation */
    .muji-nav {
        background: rgba(255, 255, 255, 0.98);
        border-bottom: 2px solid var(--muji-gold);
        padding: 0.8rem 0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        z-index: 1050;
        transition: all 0.3s ease;
    }
    .muji-nav.scrolled {
        padding: 0.5rem 0;
        box-shadow: 0 6px 30px rgba(0,0,0,0.12);
    }
    .nav-link { 
        font-weight: 700; 
        color: var(--muji-blue-deep) !important; 
        font-size: 0.85rem; 
        text-transform: uppercase; 
        letter-spacing: 1px;
        transition: all 0.3s ease;
        position: relative;
    }
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 2px;
        background: var(--muji-gold);
        transition: width 0.3s ease;
    }
    .nav-link:hover::after {
        width: 80%;
    }

    .country-switcher {
        display: inline-flex;
        align-items: center;
    }

    .country-select {
        border: 1px solid #d1d9e6;
        background: #f8fbff;
        color: var(--muji-blue-deep);
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 0.72rem;
        font-weight: 800;
        min-width: 140px;
    }

    /* Client Portal Button */
    .btn-client-portal {
        background: linear-gradient(135deg, var(--muji-blue-deep) 0%, #004080 100%);
        color: white !important;
        border: 2px solid var(--muji-gold);
        padding: 10px 28px;
        font-weight: 800;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        font-size: 0.75rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 4px 15px rgba(0, 35, 71, 0.3);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-client-portal:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #004080 0%, var(--muji-blue-deep) 100%);
        box-shadow: 0 8px 25px rgba(0, 35, 71, 0.5);
        border-color: var(--muji-gold-bright);
        color: white !important;
    }

    /* Hero Section */
    .hero-command-center {
        background-color: var(--muji-blue-deep);
        min-height: 100vh;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        color: white;
        padding-top: 100px;
        padding-bottom: 50px;
    }
    .hero-video-bg {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        object-fit: cover;
        opacity: 0.35;
        z-index: 1;
    }
    .hero-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: radial-gradient(circle, rgba(0,35,71,0.2) 0%, rgba(0,35,71,0.95) 100%);
        z-index: 2;
    }
    .hero-content { position: relative; z-index: 3; width: 100%; }
    .hero-title { 
        font-size: clamp(3.2rem, 10vw, 6.5rem); 
        font-weight: 800; 
        background: linear-gradient(to right, #ffffff, var(--muji-gold-bright));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -3px;
        margin-bottom: 0;
        animation: fadeInUp 1s ease-out;
    }
    .hero-subtitle {
        font-weight: 900;
        color: var(--muji-gold-bright);
        text-shadow: 0 0 15px rgba(255, 223, 145, 0.6);
        letter-spacing: clamp(2px, 1vw, 6px);
        text-transform: uppercase;
        font-size: clamp(0.85rem, 2vw, 1.25rem);
        margin-top: 15px;
        display: block;
        animation: fadeInUp 1s ease-out 0.3s both;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 50px;
        animation: fadeInUp 1s ease-out 0.6s both;
    }

    .btn-muji-red {
        background: var(--muji-red);
        color: white !important;
        border: none;
        padding: 18px 50px;
        font-weight: 800;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 0.9rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 6px 20px rgba(188, 0, 45, 0.4);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        position: relative;
        overflow: hidden;
    }
    .btn-muji-red::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }
    .btn-muji-red:hover::before {
        left: 100%;
    }
    .btn-muji-red:hover { 
        transform: translateY(-4px) scale(1.02); 
        background: #960024; 
        box-shadow: 0 12px 30px rgba(188, 0, 45, 0.6);
        color: white !important;
    }

    .btn-partner {
        background: linear-gradient(135deg, var(--muji-gold) 0%, var(--muji-gold-bright) 100%);
        color: var(--muji-blue-deep) !important;
        border: 2px solid var(--muji-gold-bright);
        padding: 18px 50px;
        font-weight: 800;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 0.9rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 6px 20px rgba(197, 160, 89, 0.4);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }
    .btn-partner::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s ease;
    }
    .btn-partner:hover::before {
        left: 100%;
    }
    .btn-partner:hover {
        transform: translateY(-4px) scale(1.02);
        background: linear-gradient(135deg, var(--muji-gold-bright) 0%, #fff 100%);
        box-shadow: 0 12px 30px rgba(197, 160, 89, 0.6);
        color: var(--muji-blue-deep) !important;
    }
    .btn-partner i {
        font-size: 1.2rem;
        transition: transform 0.3s ease;
    }
    .btn-partner:hover i {
        transform: scale(1.2) rotate(10deg);
    }

    @media (max-width: 768px) {
        .cta-buttons {
            flex-direction: column;
            width: 100%;
            padding: 0 20px;
        }
        .btn-muji-red, .btn-partner {
            width: 100%;
            justify-content: center;
            padding: 16px 30px;
        }

        .country-switcher {
            width: 100%;
            margin: 8px 0;
        }

        .country-select {
            width: 100%;
            min-width: 0;
        }
    }

    /* Solution Cards */
    .utility-grid-5 { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
        gap: 30px; 
    }
    .sap-tile {
        background: var(--muji-blue-light);
        border: 1px solid #d1d9e6;
        position: relative;
        padding: 45px 25px;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%;
        overflow: hidden;
        border-radius: 8px;
    }
    .sap-tile::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; width: 0; height: 4px;
        background: var(--muji-gold);
        transition: width 0.4s ease-in-out;
    }
    .sap-tile:hover {
        background: #fff;
        transform: translateY(-12px);
        box-shadow: 0 20px 40px rgba(0,35,71,0.12);
        border-color: var(--muji-gold);
    }
    .sap-tile:hover::after { width: 100%; }
    .sap-tile i { transition: all 0.4s ease; }
    .sap-tile:hover i { 
        transform: scale(1.2) rotate(5deg); 
        color: var(--muji-gold) !important; 
    }

    /* Capabilities */
    .capability-img-frame {
        position: relative;
        padding: 12px;
        background: #fff;
        border: 2px solid var(--muji-gold);
        transition: all 0.4s ease;
        border-radius: 8px;
        overflow: hidden;
    }
    .capability-img-frame::before {
        content: ''; 
        position: absolute; 
        top: -8px; left: -8px; right: -8px; bottom: -8px;
        border: 1px solid var(--muji-gold-bright); 
        opacity: 0.3; 
        pointer-events: none;
        border-radius: 8px;
    }
    .capability-img-frame:hover { 
        transform: scale(1.02) rotate(1deg);
        box-shadow: 0 15px 40px rgba(0,35,71,0.15);
    }
    .capability-img-frame img {
        border-radius: 4px;
        transition: transform 0.4s ease;
    }
    .capability-img-frame:hover img {
        transform: scale(1.05);
    }

    /* Team Cards */
    .team-card { 
        border: 1px solid #eee; 
        background: #fff; 
        border-radius: 8px; 
        overflow: hidden; 
        transition: all 0.4s ease; 
        height: 100%;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .team-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.12);
    }
    .team-img-box { 
        height: 400px; 
        overflow: hidden; 
        border-bottom: 4px solid var(--muji-gold); 
        position: relative; 
    }
    .team-img-box img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        transition: all 0.6s ease; 
        filter: grayscale(30%); 
    }
    .team-card:hover img { 
        transform: scale(1.08); 
        filter: grayscale(0%); 
    }

    /* Testimonials */
    .testimonial-section { 
        background: linear-gradient(135deg, #001529 0%, var(--muji-blue-deep) 100%); 
        padding: 100px 0; 
        color: white; 
        overflow: hidden;
        position: relative;
    }
    .testimonial-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23c5a059" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        opacity: 0.3;
    }
    .muji-carousel-viewport { overflow: hidden; position: relative; }
    .muji-carousel-track { 
        display: flex; 
        gap: 25px; 
        animation: scrollInfinite 50s linear infinite; 
        width: max-content; 
    }
    @keyframes scrollInfinite { 
        0% { transform: translateX(0); } 
        100% { transform: translateX(-50%); } 
    }
    .muji-carousel-track:hover {
        animation-play-state: paused;
    }
    .testi-card-small { 
        width: 350px; 
        background: rgba(255,255,255,0.05); 
        border: 1px solid rgba(197, 160, 89, 0.4); 
        padding: 35px; 
        border-radius: 8px;
        flex-shrink: 0;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    .testi-card-small:hover {
        background: rgba(255,255,255,0.08);
        border-color: var(--muji-gold);
        transform: translateY(-5px);
    }

    /* Licensing */
    .license-box { 
        border: 1px solid #eee; 
        background: #fff; 
        padding: 45px 30px; 
        border-radius: 8px; 
        height: 100%; 
        display: flex; 
        flex-direction: column; 
        transition: all 0.4s ease; 
        border-top: 3px solid transparent;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .license-featured { 
        border: 2px solid var(--muji-gold); 
        background: var(--muji-blue-light); 
        transform: scale(1.03); 
        border-top-color: var(--muji-gold);
        box-shadow: 0 15px 40px rgba(197, 160, 89, 0.2);
    }
    .license-box:hover:not(.license-featured) { 
        border-top-color: var(--muji-gold); 
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    }

    /* Animated Footer Background */
    .unified-footer-block { 
        background: var(--muji-blue-light); 
        padding: 100px 0 40px; 
        border-top: 5px solid var(--muji-gold);
        position: relative;
        overflow: hidden;
    }
    
    .footer-animated-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        opacity: 0.08;
    }
    
    .footer-animated-bg::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            linear-gradient(45deg, transparent 30%, var(--muji-gold) 30%, var(--muji-gold) 70%, transparent 70%),
            linear-gradient(-45deg, transparent 30%, var(--muji-blue-deep) 30%, var(--muji-blue-deep) 70%, transparent 70%);
        background-size: 100px 100px;
        animation: footerMove 20s linear infinite;
        opacity: 0.3;
    }
    
    @keyframes footerMove {
        0% { background-position: 0 0, 0 0; }
        100% { background-position: 100px 100px, -100px -100px; }
    }
    
    .footer-content {
        position: relative;
        z-index: 1;
    }
    
    .map-frame { 
        border: 12px solid #fff; 
        border-radius: 8px; 
        box-shadow: 0 15px 45px rgba(0,0,0,0.1); 
        height: 100%; 
        min-height: 500px;
        overflow: hidden;
    }

    .gold-label { 
        color: var(--muji-gold); 
        font-weight: 800; 
        text-transform: uppercase; 
        letter-spacing: 2.5px; 
        font-size: 0.8rem; 
        display: block; 
    }

    .hover-gold:hover {
        color: var(--muji-gold) !important;
        transform: scale(1.1);
    }

    .stat-number {
        display: inline-block;
        transition: transform 0.3s ease;
    }
    .stat-number:hover {
        transform: scale(1.1);
    }

    .form-control:focus {
        border-color: var(--muji-gold);
        box-shadow: 0 0 0 0.2rem rgba(197, 160, 89, 0.25);
    }
</style>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top muji-nav" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#home">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" style="height: 40px;" class="me-2">
            <span class="fw-bold" style="color: var(--muji-blue-deep); letter-spacing: 1.5px;">SMATBOOK</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mujiNav">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="mujiNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link px-3" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#solutions">Solutions</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#capabilities">Capabilities</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#team">Other Projects</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#licensing">Licensing</a></li>
                <li class="nav-item country-switcher ms-lg-2">
                    <select class="country-select" id="countrySelectorLanding" aria-label="Country and currency">
                        <option value="NG">🇳🇬 Nigeria (NGN)</option>
                        <option value="US">🇺🇸 United States (USD)</option>
                        <option value="CN">🇨🇳 China (CNY)</option>
                        <option value="GB">🇬🇧 United Kingdom (GBP)</option>
                        <option value="EU">🇪🇺 Europe (EUR)</option>
                        <option value="CA">🇨🇦 Canada (CAD)</option>
                        <option value="IN">🇮🇳 India (INR)</option>
                        <option value="AE">🇦🇪 UAE (AED)</option>
                        <option value="ZA">🇿🇦 South Africa (ZAR)</option>
                        <option value="KE">🇰🇪 Kenya (KES)</option>
                        <option value="GH">🇬🇭 Ghana (GHS)</option>
                    </select>
                </li>
                <li class="nav-item ms-lg-4">
                    <a class="btn-client-portal" href="{{ route('saas-login') }}">
                        <i class="fas fa-user-lock"></i> CLIENT PORTAL
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-command-center" id="home">
    <video class="hero-video-bg" src="{{ asset('assets/video/new.mp4') }}" autoplay muted loop playsinline></video>
    <div class="hero-overlay"></div>
    <div class="container hero-content text-center">
        <h1 class="hero-title">Smat Book</h1>
        <span class="hero-subtitle">INSTITUTIONAL NEURAL ACCOUNTING & GLOBAL BOOK KEEPING</span>
        
        <p class="mx-auto mt-4 text-white px-3" style="max-width: 950px; font-size: 1.2rem; line-height: 1.9; opacity: 0.9;">
            SmatBook is a hyper-intelligent command center engineered for the granular complexities of global wealth governance. We bridge the structural gap between fragmented legacy data and high-velocity executive decision-making.
        </p>

        <div class="cta-buttons">
            <a href="#licensing" class="btn-muji-red smooth-scroll">
                <i class="fas fa-shopping-cart"></i>
                <span>Buy a Plan</span>
            </a>
            
            <a href="{{ route('saas-register', ['type' => 'manager']) }}" class="btn-partner">
                <i class="fas fa-handshake"></i>
                <span>Become a Partner</span>
            </a>
        </div>
    </div>
</section>

<!-- Stats Row -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3" data-aos="fade-up">
                <h2 class="fw-bold display-5 mb-0 stat-number" style="color: var(--muji-blue-deep);">60K+</h2>
                <p class="text-muted small text-uppercase fw-bold">Entities Managed</p>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                <h2 class="fw-bold display-5 mb-0 stat-number" style="color: var(--muji-blue-deep);">$12B+</h2>
                <p class="text-muted small text-uppercase fw-bold">Annual Volume</p>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                <h2 class="fw-bold display-5 mb-0 stat-number" style="color: var(--muji-blue-deep);">150+</h2>
                <p class="text-muted small text-uppercase fw-bold">Global Nodes</p>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                <h2 class="fw-bold display-5 mb-0 stat-number" style="color: var(--muji-blue-deep);">99.9%</h2>
                <p class="text-muted small text-uppercase fw-bold">Uptime SLA</p>
            </div>
        </div>
    </div>
</section>

<!-- Solutions Grid -->
<section class="py-5 bg-light" id="solutions">
    <div class="container py-5">
        <div class="text-center mb-5">
            <span class="gold-label mb-2">Operational Utility</span>
            <h2 class="fw-bold display-5" style="color: var(--muji-blue-deep);">Engine <span style="color: var(--muji-gold);">Capabilities</span></h2>
        </div>
        <div class="utility-grid-5">
            @php
            $utils = [
                ['icon' => 'fa-brain', 'title' => 'Neural Ledger Engine', 'text' => 'Utilizes transformer-based AI to handle multi-currency reconciliations across thousands of subsidiaries. Our engine reduces manual entry errors by 99.8% through autonomous pattern matching.'],
                ['icon' => 'fa-chart-line', 'title' => 'Predictive Forensics', 'text' => 'Execute high-fidelity Monte Carlo simulations to forecast capital requirements and mitigate liquidity risks. Transform historical data into actionable 24-month financial roadmaps.'],
                ['icon' => 'fa-fingerprint', 'title' => 'Sovereign Governance', 'text' => 'Institutional security protocols featuring Multi-Party Computation (MPC) and ZK-Proofs. Maintain absolute data sovereignty while ensuring total transparency for the executive board.'],
                ['icon' => 'fa-file-signature', 'title' => 'Autonomous Auditing', 'text' => 'Generate board-ready audits mapped to IFRS and GAAP standards. Real-time regulatory compliance allows for zero-latency fiscal reporting across global jurisdictions.']
             ];
            @endphp
            @foreach($utils as $u)
            <div class="sap-tile" data-aos="fade-up">
                <i class="fas {{ $u['icon'] }} mb-4" style="font-size: 2.2rem; color: var(--muji-blue-deep);"></i>
                <h5 class="fw-bold mb-3">{{ $u['title'] }}</h5>
                <p class="small text-muted mb-0" style="line-height: 1.7;">{{ $u['text'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Capabilities Section -->
<section class="py-5 bg-white" id="capabilities">
    <div class="container py-5">
        <!-- 01 Engine Depth -->
        <div class="row align-items-center g-5 mb-5 pb-5">
            <div class="col-lg-6">
                <div class="capability-img-frame" data-aos="fade-right">
                    <img src="https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg?auto=compress&cs=tinysrgb&w=800" class="img-fluid" alt="Analytics">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="gold-label mb-3">01 — ENGINE DEPTH</span>
                <h2 class="display-6 fw-bold mb-4" style="color: var(--muji-blue-deep);">Strategic <span style="color: var(--muji-gold);">Liquidity</span> Ecosystem</h2>
                <p class="text-muted mb-4" style="text-align: justify; line-height: 1.9;">
                    SmatBook's proprietary Neural Forecasting Core (NFC) transcends legacy bookkeeping systems by analyzing over 600 unique financial variables in real-time. By mapping historical account volatility against current receivables, our engine provides a surgical liquidity horizon with 98.4% predictive accuracy. This depth allows CFOs to transition from reactive management to proactive capital allocation, ensuring organizational resilience in volatile economic shifts.
                </p>
                <div class="p-4 rounded bg-light border-start border-4 border-warning shadow-sm">
                    <p class="mb-0 fst-italic small fw-bold">"We convert fragmented transaction streams into verified, high-definition foresight for the modern board."</p>
                </div>
            </div>
        </div>

        <!-- 02 Governance Structure -->
        <div class="row align-items-center g-5 pt-5">
            <div class="col-lg-6 order-lg-2">
                <div class="capability-img-frame" data-aos="fade-left">
                    <img src="https://images.pexels.com/photos/669619/pexels-photo-669619.jpeg?auto=compress&cs=tinysrgb&w=800" class="img-fluid" alt="Governance">
                </div>
            </div>
            <div class="col-lg-6 order-lg-1" data-aos="fade-right">
                <span class="gold-label mb-3">02 — GOVERNANCE STRUCTURE</span>
                <h2 class="display-6 fw-bold mb-4" style="color: var(--muji-blue-deep);">Institutional <span style="color: var(--muji-gold);">Sovereignty</span> Protocols</h2>
                <p class="text-muted mb-4" style="text-align: justify; line-height: 1.9;">
                    Designed for organizations with complex hierarchical needs, SmatBook implements a "Cellular Governance" model that guarantees total transparency without compromising individual business unit security. Each subsidiary operates within a fortified node, feeding into a master dashboard while maintaining SOC2 Type II compliance. Our infrastructure empowers organizations to navigate layers of management effortlessly, ensuring every naira is verified and every executive decision is backed by immutable data.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Other Projects -->
<section class="py-5 bg-light" id="team">
    <div class="container py-5">
        <div class="text-center mb-5">
            <span class="gold-label mb-2">Portfolio</span>
            <h2 class="fw-bold display-6" style="color: var(--muji-blue-deep);">Our <span style="color: var(--muji-gold);">Other Projects</span></h2>
        </div>
        <div class="row g-4">
            @php
            $team = [
                [
                    'name' => 'Lahome Properties',
                    'role' => 'Real Estate Platform',
                    'img' => 'https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?auto=compress&cs=tinysrgb&w=1200',
                    'bio' => 'A global real estate listing ecosystem for owners, surveyors, legal advisers, agents, and every key stakeholder in the property market.',
                    'link' => route('landing.projects.lahome'),
                ],
                [
                    'name' => 'Master JAMB',
                    'role' => 'CBT Examination Platform',
                    'img' => 'https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1200&auto=format&fit=crop',
                    'bio' => 'An online CBT platform for schools and institutions, built for exam readiness, timed assessments, and performance tracking.',
                    'link' => route('landing.projects.master-jamb'),
                ],
                [
                    'name' => 'PayPlus',
                    'role' => 'Payment Gateway',
                    'img' => 'https://images.pexels.com/photos/4968634/pexels-photo-4968634.jpeg?auto=compress&cs=tinysrgb&w=1200',
                    'bio' => 'A global payment gateway designed for secure processing of everyday transactions across web, mobile, and enterprise channels.',
                    'link' => route('landing.projects.payplus'),
                ],
            ];
            @endphp
            @foreach($team as $m)
            <div class="col-lg-4 col-md-6">
                <div class="team-card" data-aos="fade-up">
                    <div class="team-img-box"><img src="{{ $m['img'] }}" alt="{{ $m['name'] }}"></div>
                    <div class="p-4 text-center">
                        <h5 class="fw-bold mb-1">{{ $m['name'] }}</h5>
                        <p class="text-warning small text-uppercase mb-3 fw-bold">{{ $m['role'] }}</p>
                        <p class="small text-muted mb-4 px-2">{{ $m['bio'] }}</p>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <a href="{{ $m['link'] }}" class="btn btn-sm btn-outline-primary px-3">Learn More</a>
                            <a href="{{ route('landing.contact') }}" class="btn btn-sm btn-outline-dark px-3">Request Demo</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonial-section">
    <div class="container text-center mb-4">
        <span class="gold-label mb-2">Global Adoption</span>
        <h2 class="fw-bold h2 text-white">Institutional <span style="color: var(--muji-gold);">Validation</span></h2>
    </div>
    <div class="muji-carousel-viewport">
        <div class="muji-carousel-track">
            @php
            $tests = [
                // Nigerians
                ['name' => 'Chinedu Okafor', 'role' => 'CFO, Lagos Holdings', 'img' => 'https://images.unsplash.com/photo-1507591064344-4c6ce005b128?q=80&w=300&auto=format&fit=crop'],
                ['name' => 'Amina Bello', 'role' => 'Finance Director, Abuja Group', 'img' => 'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?q=80&w=300&auto=format&fit=crop'],
                // Americans
                ['name' => 'Michael Carter', 'role' => 'VP Finance, New York Capital', 'img' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=300&auto=format&fit=crop'],
                ['name' => 'Emily Johnson', 'role' => 'Controller, Austin Ventures', 'img' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=300&auto=format&fit=crop'],
                // Chinese
                ['name' => 'Li Wei', 'role' => 'Treasury Lead, Shanghai Trade', 'img' => 'https://images.unsplash.com/photo-1521119989659-a83eee488004?q=80&w=300&auto=format&fit=crop'],
                ['name' => 'Chen Ming', 'role' => 'Payments Director, Beijing Commerce', 'img' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=300&auto=format&fit=crop']
            ];
            $repeat = array_merge($tests, $tests, $tests);
            @endphp
            @foreach($repeat as $t)
            <div class="testi-card-small">
                <p class="testi-text" style="font-size: 0.9rem; opacity: 0.9; font-style: italic; margin-bottom: 25px;">"SmatBook's neural-ledgers have fundamentally changed how we manage our global hubs. Unmatched precision."</p>
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ $t['img'] }}" class="client-avatar-small" alt="" style="width: 45px; height: 45px; border-radius: 50%; border: 2px solid var(--muji-gold);">
                    <div>
                        <span class="d-block fw-bold text-white small" style="font-size: 0.85rem;">{{ $t['name'] }}</span>
                        <span class="text-warning tiny fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">{{ $t['role'] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Licensing -->
<section class="py-5 bg-white" id="licensing">
    <div class="container py-5">
        <div class="text-center mb-5">
            <span class="gold-label mb-2">Service Access</span>
            <h2 class="fw-bold display-6 mb-5" style="color: var(--muji-blue-deep);">Enterprise <span style="color: var(--muji-gold);">Licensing</span></h2>
        </div>
        <div class="row g-4">
            @php
            $plans = [
                'Basic' => ['ngn' => 3000000, 'feat' => false, 'benefits' => ['Centralized Ledgers', '5 Core User Access', 'Daily Cloud Backups', 'Standard Email Support', 'Unified Reporting']],
                'Pro' => ['ngn' => 7000000, 'feat' => true, 'benefits' => ['Neural Engine Core', '25 Premium User Access', 'Dedicated Priority Node', 'Real-time Analytics', 'Predictive Forecasting']],
                'Enterprise' => ['ngn' => 15000000, 'feat' => false, 'benefits' => ['Full Neural Automation', 'Unlimited Access Nodes', 'Advanced API Gateway', 'Custom Fiscal Reports', 'IFRS Compliance Mapping']],
                'Institution' => ['ngn' => null, 'feat' => false, 'benefits' => ['Private Hybrid Core', 'SLA Performance Guarantee', 'On-site Technical Support', 'Bespoke Integrations', 'Governance Training']]
            ];
            @endphp
            @foreach($plans as $name => $p)
            <div class="col-lg-3 col-md-6">
                <div class="license-box {{ $p['feat'] ? 'license-featured' : '' }}" data-aos="fade-up">
                    <h6 class="text-uppercase fw-bold text-muted mb-4 text-center" style="letter-spacing: 1px;">{{ $name }}</h6>
                    <h2 class="fw-bold mb-5 text-center" style="color: var(--muji-blue-deep);">
                        @if($p['ngn'])
                            <span class="geo-price" data-ngn="{{ $p['ngn'] }}">₦{{ number_format($p['ngn']) }}</span>
                        @else
                            <span>Bespoke</span>
                        @endif
                    </h2>
                    <ul class="list-unstyled mb-5 small text-muted flex-grow-1">
                        @foreach($p['benefits'] as $benefit)
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success mt-1 me-2"></i> 
                            <span>{{ $benefit }}</span>
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ url('/membership-plans') }}" class="btn {{ $p['feat'] ? 'btn-muji-red' : 'btn-outline-dark' }} w-100" style="border-radius: 4px; font-weight: 700;">
                        ACQUIRE SYSTEM
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- UNIFIED FOOTER WITH ANIMATED BACKGROUND -->
<footer class="unified-footer-block" id="contact">
    <!-- Animated Background Layer -->
    <div class="footer-animated-bg"></div>
    
    <!-- Footer Content -->
    <div class="container footer-content">
        <!-- Contact & Map -->
        <div class="row g-5 mb-5 pb-5 border-bottom" style="border-color: rgba(0,0,0,0.1) !important;">
            <div class="col-lg-5" data-aos="fade-right">
                <h2 class="fw-bold mb-4" style="color: var(--muji-blue-deep);">Uplink <span style="color: var(--muji-gold);">Support</span></h2>
                <p class="text-muted mb-4" style="line-height: 1.8;">Technical architects are available 24/7 for organizational assessment and rapid deployment. Contact us to initialize your institutional connection.</p>
                <div class="mb-5">
                    <p class="mb-2 small fw-bold">
                        <i class="fas fa-map-marker-alt text-warning me-3"></i> 
                        12 Independence Layout, Enugu, Nigeria
                    </p>
                    <p class="mb-2 small fw-bold">
                        <i class="fas fa-phone-alt text-warning me-3"></i> 
                        +234 646 463 06
                    </p>
                    <p class="small fw-bold">
                        <i class="fas fa-envelope text-warning me-3"></i> 
                        <a href="mailto:donvictorlive@gmail.com" class="text-decoration-none text-dark">donvictorlive@gmail.com</a>
                    </p>
                </div>
                @if(session('success'))
                    <div class="alert alert-success mb-3">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
                @endif
                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <input type="text" name="company_name" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Organization Name" value="{{ old('company_name') }}">
                    </div>
                    <div class="mb-3">
                        <input type="text" name="fullname" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Full Name" value="{{ old('fullname') }}" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Work Email" value="{{ old('email') }}" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="department" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Department (Optional)" value="{{ old('department') }}">
                    </div>
                    <div class="mb-3">
                        <textarea name="message" class="form-control rounded-1 border-0 shadow-sm" rows="4" placeholder="Brief Requirements" required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn-muji-red w-100">
                        <i class="fas fa-paper-plane me-2"></i>
                        INITIALIZE CONNECTION
                    </button>
                </form>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="map-frame">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15858.987654321!2d7.508333!3d6.458333!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1044a3d6f1a8e1e1%3A0x1234567890abcdef!2sIndependence%20Layout%2C%20Enugu!5e0!3m2!1sen!2sng!4v1234567890123" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>

        <!-- Footer Links Section -->
        <div class="row g-4 text-center text-md-start">
            <div class="col-lg-4">
                <h3 class="fw-bold mb-3" style="color: var(--muji-blue-deep); letter-spacing: 1px;">SMATBOOK</h3>
                <p class="small text-muted" style="max-width: 320px;">Global Institutional Accounting Intelligence. Engineered for modern wealth governance and predictive capital allocation. Nigeria Hub Node: ENU-NG-12.</p>
                <div class="d-flex justify-content-center justify-content-md-start gap-4 mt-4">
                    <a href="{{ route('landing.about') }}" class="text-dark opacity-50 hover-gold" style="transition: all 0.3s;">
                        <i class="fab fa-linkedin-in fa-lg"></i>
                    </a>
                    <a href="{{ route('landing.contact') }}" class="text-dark opacity-50 hover-gold" style="transition: all 0.3s;">
                        <i class="fab fa-twitter fa-lg"></i>
                    </a>
                    <a href="{{ route('landing.policy') }}" class="text-dark opacity-50 hover-gold" style="transition: all 0.3s;">
                        <i class="fab fa-facebook-f fa-lg"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-3 col-lg-2 ms-auto">
                <h6 class="fw-bold text-uppercase mb-4" style="color: var(--muji-blue-deep); letter-spacing: 1px;">Platform</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><a href="#solutions" class="text-muted text-decoration-none hover-gold" style="transition: all 0.3s;">Neural Engine Core</a></li>
                    <li class="mb-2"><a href="#capabilities" class="text-muted text-decoration-none hover-gold" style="transition: all 0.3s;">Security Hub</a></li>
                    <li class="mb-2"><a href="#licensing" class="text-muted text-decoration-none hover-gold" style="transition: all 0.3s;">API Documentation</a></li>
                </ul>
            </div>
            <div class="col-md-3 col-lg-2">
                <h6 class="fw-bold text-uppercase mb-4" style="color: var(--muji-blue-deep); letter-spacing: 1px;">Support</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><a href="{{ route('landing.contact') }}" class="text-muted text-decoration-none hover-gold" style="transition: all 0.3s;">Knowledge Base</a></li>
                    <li class="mb-2"><a href="{{ route('landing.contact') }}" class="text-muted text-decoration-none hover-gold" style="transition: all 0.3s;">SLA Status</a></li>
                    <li class="mb-2"><a href="{{ route('saas-login') }}" class="text-muted text-decoration-none hover-gold" style="transition: all 0.3s;">Global Deployment</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-5 pt-4 text-center border-top" style="border-color: rgba(0,0,0,0.05) !important;">
            <p class="small text-muted mb-0">© 2026 SmatBook Intelligence Enterprise. Licensed for Global Financial Governance.</p>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                const navCollapse = document.getElementById('mujiNav');
                if (navCollapse && navCollapse.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                    if (bsCollapse) {
                        bsCollapse.hide();
                    }
                }
            }
        });
    });

    // Navbar scrolled state
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });

    // Animate stats
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.stat-number').forEach(stat => {
        observer.observe(stat);
    });

    // Geo country/currency behavior for global pricing experience
    const countrySelector = document.getElementById('countrySelectorLanding');
    const countryMap = {
        NG: { currency: 'NGN', locale: 'en-NG' },
        US: { currency: 'USD', locale: 'en-US' },
        CN: { currency: 'CNY', locale: 'zh-CN' },
        GB: { currency: 'GBP', locale: 'en-GB' },
        EU: { currency: 'EUR', locale: 'en-IE' },
        CA: { currency: 'CAD', locale: 'en-CA' },
        IN: { currency: 'INR', locale: 'en-IN' },
        AE: { currency: 'AED', locale: 'en-AE' },
        ZA: { currency: 'ZAR', locale: 'en-ZA' },
        KE: { currency: 'KES', locale: 'en-KE' },
        GH: { currency: 'GHS', locale: 'en-GH' }
    };

    const staticNgnRates = {
        NGN: 1, USD: 0.00067, CNY: 0.0048, GBP: 0.00053, EUR: 0.00062,
        CAD: 0.00091, INR: 0.056, AED: 0.00246, ZAR: 0.0125, KES: 0.086, GHS: 0.0105
    };

    const regionFromLocale = () => {
        try {
            const locale = Intl.DateTimeFormat().resolvedOptions().locale || navigator.language || 'en-NG';
            const region = locale.split('-').pop().toUpperCase();
            return region.length === 2 ? region : 'NG';
        } catch (e) {
            return 'NG';
        }
    };

    const normalizeCountry = (raw) => {
        const code = String(raw || '').toUpperCase();
        const euRegions = ['FR', 'DE', 'ES', 'IT', 'PT', 'NL', 'BE', 'AT', 'IE', 'FI', 'SE', 'DK', 'PL', 'CZ', 'GR', 'RO', 'HU'];
        if (euRegions.includes(code)) return 'EU';
        if (countryMap[code]) return code;
        return 'NG';
    };

    const fetchRates = async () => {
        const cacheKey = 'smat_rates_ngn_v1';
        const cacheTimeKey = 'smat_rates_ngn_v1_time';
        const cached = sessionStorage.getItem(cacheKey);
        const cachedTime = Number(sessionStorage.getItem(cacheTimeKey) || 0);
        const fresh = cached && (Date.now() - cachedTime) < (6 * 60 * 60 * 1000);
        if (fresh) return JSON.parse(cached);

        try {
            const resp = await fetch('https://open.er-api.com/v6/latest/NGN', { cache: 'no-store' });
            const data = await resp.json();
            if (data?.rates) {
                sessionStorage.setItem(cacheKey, JSON.stringify(data.rates));
                sessionStorage.setItem(cacheTimeKey, String(Date.now()));
                return data.rates;
            }
        } catch (e) {}
        return staticNgnRates;
    };

    const renderGeoPrices = async (countryCode) => {
        const geo = countryMap[normalizeCountry(countryCode)] || countryMap.NG;
        const rates = await fetchRates();
        const rate = rates[geo.currency] || staticNgnRates[geo.currency] || 1;

        document.querySelectorAll('.geo-price').forEach((node) => {
            const ngnValue = Number(node.dataset.ngn || 0);
            const converted = ngnValue * rate;
            try {
                node.textContent = new Intl.NumberFormat(geo.locale, {
                    style: 'currency',
                    currency: geo.currency,
                    maximumFractionDigits: 0
                }).format(converted);
            } catch (e) {
                node.textContent = `${geo.currency} ${Math.round(converted).toLocaleString()}`;
            }
        });
    };

    const applyCountry = (countryCode) => {
        const normalized = normalizeCountry(countryCode);
        localStorage.setItem('smat_country', normalized);
        const oneYear = 60 * 60 * 24 * 365;
        document.cookie = `smat_country=${normalized}; path=/; max-age=${oneYear}; SameSite=Lax`;
        if (countrySelector) countrySelector.value = normalized;
        renderGeoPrices(normalized);
    };

    const initCountry = async () => {
        const cookieMatch = document.cookie.match(/(?:^|;\s*)smat_country=([^;]+)/);
        const cookieCountry = cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
        const saved = localStorage.getItem('smat_country');
        const serverDefault = @json($geoCountry ?? 'NG');
        if (saved || cookieCountry) {
            applyCountry(saved || cookieCountry);
            return;
        }

        applyCountry(serverDefault || 'NG');
    };

    if (countrySelector) {
        countrySelector.addEventListener('change', function() {
            applyCountry(this.value);
            document.dispatchEvent(new CustomEvent('smat:geo-change', {
                detail: { country: this.value, ...countryMap[normalizeCountry(this.value)] }
            }));
        });
    }

    document.addEventListener('smat:geo-change', (event) => {
        const code = normalizeCountry(event?.detail?.country);
        if (countrySelector && countrySelector.value !== code) {
            countrySelector.value = code;
            renderGeoPrices(code);
        }
    });

    initCountry();
});
</script>

@endsection
