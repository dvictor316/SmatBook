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
        background: linear-gradient(
            to bottom,
            rgba(0,35,71,0.55) 0%,
            rgba(0,35,71,0.40) 40%,
            rgba(0,35,71,0.70) 85%,
            rgba(0,35,71,0.90) 100%
        );
        z-index: 3;
    }
    .hero-content { position: relative; z-index: 4; width: 100%; }
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
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
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
        top: 0; left: -100%;
        width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }
    .btn-muji-red:hover::before { left: 100%; }
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
        top: 0; left: -100%;
        width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s ease;
    }
    .btn-partner:hover::before { left: 100%; }
    .btn-partner:hover {
        transform: translateY(-4px) scale(1.02);
        background: linear-gradient(135deg, var(--muji-gold-bright) 0%, #fff 100%);
        box-shadow: 0 12px 30px rgba(197, 160, 89, 0.6);
        color: var(--muji-blue-deep) !important;
    }
    .btn-partner i { font-size: 1.2rem; transition: transform 0.3s ease; }
    .btn-partner:hover i { transform: scale(1.2) rotate(10deg); }

    @media (max-width: 768px) {
        .cta-buttons { flex-direction: column; width: 100%; padding: 0 20px; }
        .btn-muji-red, .btn-partner { width: 100%; justify-content: center; padding: 16px 30px; }
        .country-switcher { width: 100%; margin: 8px 0; }
        .country-select { width: 100%; min-width: 0; }
    }

    /* Solution Cards */
    .utility-grid-5 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
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
    .sap-tile:hover { background: #fff; transform: translateY(-12px); box-shadow: 0 20px 40px rgba(0,35,71,0.12); border-color: var(--muji-gold); }
    .sap-tile:hover::after { width: 100%; }
    .sap-tile i { transition: all 0.4s ease; }
    .sap-tile:hover i { transform: scale(1.2) rotate(5deg); color: var(--muji-gold) !important; }

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
    .capability-img-frame:hover { transform: scale(1.02) rotate(1deg); box-shadow: 0 15px 40px rgba(0,35,71,0.15); }
    .capability-img-frame img { border-radius: 4px; transition: transform 0.4s ease; }
    .capability-img-frame:hover img { transform: scale(1.05); }

    /* Team Cards */
    .team-card { border: 1px solid #eee; background: #fff; border-radius: 8px; overflow: hidden; transition: all 0.4s ease; height: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .team-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
    .team-img-box { height: 400px; overflow: hidden; border-bottom: 4px solid var(--muji-gold); position: relative; }
    .team-img-box img { width: 100%; height: 100%; object-fit: cover; transition: all 0.6s ease; filter: grayscale(30%); }
    .team-card:hover img { transform: scale(1.08); filter: grayscale(0%); }

    /* Testimonials */
    .testimonial-section { background: linear-gradient(135deg, #001529 0%, var(--muji-blue-deep) 100%); padding: 100px 0; color: white; overflow: hidden; position: relative; }
    .testimonial-section::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23c5a059" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        opacity: 0.3;
    }
    .muji-carousel-viewport { overflow: hidden; position: relative; }
    .muji-carousel-track { display: flex; gap: 25px; animation: scrollInfinite 50s linear infinite; width: max-content; }
    @keyframes scrollInfinite { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    .muji-carousel-track:hover { animation-play-state: paused; }
    .testi-card-small { width: 350px; background: rgba(255,255,255,0.05); border: 1px solid rgba(197, 160, 89, 0.4); padding: 35px; border-radius: 8px; flex-shrink: 0; backdrop-filter: blur(10px); transition: all 0.3s ease; }
    .testi-card-small:hover { background: rgba(255,255,255,0.08); border-color: var(--muji-gold); transform: translateY(-5px); }

    /* Licensing */
    .license-box { border: 1px solid #eee; background: #fff; padding: 45px 30px; border-radius: 8px; height: 100%; display: flex; flex-direction: column; transition: all 0.4s ease; border-top: 3px solid transparent; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .license-featured { border: 2px solid var(--muji-gold); background: var(--muji-blue-light); transform: scale(1.03); border-top-color: var(--muji-gold); box-shadow: 0 15px 40px rgba(197, 160, 89, 0.2); }
    .license-box:hover:not(.license-featured) { border-top-color: var(--muji-gold); transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }

    /* Footer */
    .unified-footer-block { background: var(--muji-blue-light); padding: 100px 0 40px; border-top: 5px solid var(--muji-gold); position: relative; overflow: hidden; }
    .footer-animated-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; opacity: 0.08; }
    .footer-animated-bg::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(45deg, transparent 30%, var(--muji-gold) 30%, var(--muji-gold) 70%, transparent 70%), linear-gradient(-45deg, transparent 30%, var(--muji-blue-deep) 30%, var(--muji-blue-deep) 70%, transparent 70%);
        background-size: 100px 100px;
        animation: footerMove 20s linear infinite;
        opacity: 0.3;
    }
    @keyframes footerMove { 0% { background-position: 0 0, 0 0; } 100% { background-position: 100px 100px, -100px -100px; } }
    .footer-content { position: relative; z-index: 1; }
    .map-frame { border: 12px solid #fff; border-radius: 8px; box-shadow: 0 15px 45px rgba(0,0,0,0.1); height: 100%; min-height: 500px; overflow: hidden; }
    .gold-label { color: var(--muji-gold); font-weight: 800; text-transform: uppercase; letter-spacing: 2.5px; font-size: 0.8rem; display: block; }
    .hover-gold:hover { color: var(--muji-gold) !important; transform: scale(1.1); }
    .stat-number { display: inline-block; transition: transform 0.3s ease; }
    .stat-number:hover { transform: scale(1.1); }
    .form-control:focus { border-color: var(--muji-gold); box-shadow: 0 0 0 0.2rem rgba(197, 160, 89, 0.25); }

    /* ═══════════════════════════════════════════════
       DASHBOARD FEATURES SECTION STYLES (NEW)
    ═══════════════════════════════════════════════ */
    .feat-section { padding: 100px 0; background: #fff; position: relative; overflow: hidden; }
    .feat-section--alt { background: #f8faff; }
    .feat-section--dark { background: var(--muji-blue-deep); color: #fff; }

    .feat-eyebrow {
        display: inline-flex; align-items: center; gap: 10px;
        font-size: 0.72rem; font-weight: 800; letter-spacing: 3px;
        text-transform: uppercase; color: var(--muji-gold); margin-bottom: 16px;
    }
    .feat-eyebrow::before { content: ''; width: 30px; height: 2px; background: var(--muji-gold); display: block; }
    .feat-h2 { font-size: clamp(1.8rem, 3.5vw, 2.6rem); font-weight: 800; line-height: 1.15; color: var(--muji-blue-deep); margin-bottom: 20px; letter-spacing: -1px; }
    .feat-h2 .accent { color: var(--muji-gold); }
    .feat-section--dark .feat-h2 { color: #fff; }

    /* Dashboard Mockup */
    .dash-frame {
        background: #fff; border-radius: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,.04), 0 8px 24px rgba(0,35,71,.10), 0 32px 64px rgba(0,35,71,.12);
        overflow: hidden; position: relative;
        transform: perspective(1200px) rotateY(-4deg) rotateX(2deg);
        transition: transform .5s ease, box-shadow .5s ease;
    }
    .dash-frame:hover { transform: perspective(1200px) rotateY(0deg) rotateX(0deg); box-shadow: 0 40px 80px rgba(0,35,71,.18); }
    .dash-frame-alt { transform: perspective(1200px) rotateY(4deg) rotateX(2deg); }
    .dash-frame-alt:hover { transform: perspective(1200px) rotateY(0deg) rotateX(0deg); }

    .dash-titlebar { background: #f5f7fa; padding: 12px 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e8ecf0; }
    .dash-dot { width: 11px; height: 11px; border-radius: 50%; }
    .dash-dot-r { background: #ff5f57; } .dash-dot-y { background: #ffbd2e; } .dash-dot-g { background: #28c840; }
    .dash-titlebar-text { margin-left: 12px; font-size: 11px; font-weight: 600; color: #8a92a0; letter-spacing: .5px; }

    .dash-sidebar { background: var(--muji-blue-deep); width: 52px; padding: 16px 0; display: flex; flex-direction: column; align-items: center; gap: 18px; flex-shrink: 0; }
    .dash-sidebar-icon { width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,.1); display: flex; align-items: center; justify-content: center; }
    .dash-sidebar-icon.active { background: var(--muji-gold); }
    .dash-sidebar-icon svg { width: 14px; height: 14px; }
    .dash-main { flex: 1; padding: 20px; overflow: hidden; }
    .dash-header-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .dash-page-title { font-size: 14px; font-weight: 700; color: var(--muji-blue-deep); }
    .dash-badge { background: #f0f4ff; color: var(--muji-blue-deep); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; letter-spacing: .5px; }

    .dash-kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
    .dash-kpi { background: #f8faff; border: 1px solid #e8ecf4; border-radius: 10px; padding: 12px 14px; position: relative; overflow: hidden; }
    .dash-kpi::after { content: ''; position: absolute; top: 0; left: 0; width: 3px; height: 100%; background: var(--muji-gold); }
    .dash-kpi-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #8a92a0; margin-bottom: 6px; }
    .dash-kpi-val { font-size: 18px; font-weight: 800; color: var(--muji-blue-deep); line-height: 1; }
    .dash-kpi-sub { font-size: 9px; color: #22c55e; font-weight: 700; margin-top: 4px; }
    .dash-kpi-sub.neg { color: #ef4444; }

    .dash-chart-row { display: grid; grid-template-columns: 1.6fr 1fr; gap: 10px; margin-bottom: 16px; }
    .dash-chart-box { background: #fff; border: 1px solid #e8ecf4; border-radius: 10px; padding: 14px; }
    .dash-chart-title { font-size: 10px; font-weight: 700; color: var(--muji-blue-deep); margin-bottom: 12px; text-transform: uppercase; letter-spacing: .5px; }

    .dash-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .dash-table th { text-align: left; padding: 6px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #8a92a0; border-bottom: 1px solid #e8ecf4; }
    .dash-table td { padding: 8px; border-bottom: 1px solid #f0f4f8; color: #3d4a5c; font-weight: 500; }
    .dash-table tr:last-child td { border-bottom: none; }
    .dash-status { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 8px; font-weight: 700; }
    .dash-status.paid { background: #dcfce7; color: #15803d; }
    .dash-status.pending { background: #fef9c3; color: #854d0e; }
    .dash-status.due { background: #fee2e2; color: #991b1b; }

    /* Benefit list */
    .benefit-list { display: flex; flex-direction: column; gap: 14px; margin-top: 28px; }
    .benefit-item { display: flex; align-items: flex-start; gap: 14px; }
    .benefit-icon { width: 44px; height: 44px; border-radius: 12px; background: #f0f4ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: .3s; }
    .benefit-item:hover .benefit-icon { background: var(--muji-gold); transform: scale(1.1); }
    .benefit-icon svg { width: 20px; height: 20px; color: var(--muji-blue-deep); }
    .benefit-item:hover .benefit-icon svg { color: #fff; }
    .benefit-text h6 { font-size: 14px; font-weight: 700; color: var(--muji-blue-deep); margin-bottom: 3px; }
    .benefit-text p { font-size: 13px; color: #6b7280; line-height: 1.6; margin: 0; }

    /* Float badges */
    .float-badge { position: absolute; background: #fff; border-radius: 12px; padding: 10px 14px; box-shadow: 0 8px 24px rgba(0,0,0,.12); display: flex; align-items: center; gap: 10px; z-index: 10; animation: floatBob 4s ease-in-out infinite; }
    .float-badge-2 { animation-delay: 2s; }
    @keyframes floatBob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    .float-badge-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .float-badge-val { font-size: 16px; font-weight: 800; color: var(--muji-blue-deep); line-height: 1; }
    .float-badge-lbl { font-size: 10px; color: #8a92a0; font-weight: 600; }

    /* Feature cards strip */
    .feat-strip { background: linear-gradient(135deg, #f8faff 0%, #fff 100%); border-top: 1px solid #e8ecf4; border-bottom: 1px solid #e8ecf4; padding: 80px 0; }
    .strip-card { background: #fff; border: 1px solid #e8ecf4; border-radius: 16px; padding: 32px 28px; height: 100%; transition: all .4s cubic-bezier(.175,.885,.32,1.275); position: relative; overflow: hidden; }
    .strip-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(to right, var(--muji-blue-deep), var(--muji-gold)); transform: scaleX(0); transform-origin: left; transition: transform .4s ease; }
    .strip-card:hover { transform: translateY(-8px); box-shadow: 0 20px 48px rgba(0,35,71,.10); }
    .strip-card:hover::before { transform: scaleX(1); }
    .strip-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 24px; }
    .strip-card h5 { font-size: 16px; font-weight: 800; color: var(--muji-blue-deep); margin-bottom: 10px; }
    .strip-card p { font-size: 13px; color: #6b7280; line-height: 1.7; margin: 0; }

    /* Progress bars */
    .prog-bar-wrap { margin-bottom: 14px; }
    .prog-label { display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: var(--muji-blue-deep); }
    .prog-track { height: 8px; background: #f0f4f8; border-radius: 99px; overflow: hidden; }
    .prog-fill { height: 100%; border-radius: 99px; background: linear-gradient(to right, var(--muji-blue-deep), var(--muji-gold)); width: 0; transition: width 1.4s cubic-bezier(.4,0,.2,1); }
    .prog-fill.animated { width: var(--target-width); }
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
                <li class="nav-item"><a class="nav-link px-3" href="#platform-features">Features</a></li>
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

<!-- ═══ ANNOUNCEMENT BLINK BAR (fixed just below navbar) ═══ -->
<style>
    .announce-bar {
        position: fixed;
        top: 88px;
        left: 0; right: 0;
        z-index: 1049;
        background: linear-gradient(90deg, var(--muji-blue-deep) 0%, #003366 50%, var(--muji-blue-deep) 100%);
        border-bottom: 2px solid var(--muji-gold);
        border-top: 1px solid rgba(197,160,89,0.35);
        padding: 13px 0;
        box-shadow: 0 4px 16px rgba(0,35,71,0.22);
        height: 50px;
        overflow: hidden;
    }
    .announce-label {
        position: absolute; left: 0;
        background: var(--muji-gold);
        height: 50px;
        display: flex; align-items: center;
        padding: 0 22px;
        font-size: 11px; font-weight: 900;
        color: var(--muji-blue-deep);
        text-transform: uppercase; letter-spacing: 2px;
        white-space: nowrap;
        top: -13px;
    }
.announce-msg {
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 0.3px;
        display: flex; align-items: center; gap: 10px;
        opacity: 0;
        transform: translateX(-50%) translateY(8px);
        transition: opacity 0.5s ease, transform 0.5s ease;
        position: absolute;
        left: 50%;
        white-space: nowrap;
    }
    .announce-msg.active { opacity: 1; transform: translateX(-50%) translateY(0); }
    .announce-msg.exit   { opacity: 0; transform: translateX(-50%) translateY(-8px); }
    .announce-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--muji-gold);
        display: inline-block;
        animation: blinkDot 1s ease-in-out infinite;
        flex-shrink: 0;
    }
    @keyframes blinkDot {
        0%,100% { opacity: 1; transform: scale(1); }
        50%      { opacity: 0.3; transform: scale(0.6); }
    }
    body { padding-top: 138px !important; }
</style>

<div class="announce-bar">
    <div class="announce-label">⚡YPUR POSSIBILITIES</div>
    <div class="announce-bar-inner" id="announceContainer">
        <!-- Messages injected by JS -->
    </div>
</div>

<script>
(function() {
    const msgs = [
        { icon: '💼', text: 'Run Your Business. Know Your Money — Simple & Stress-Free' },
        { icon: '📊', text: 'Track Sales · Manage Stock · Pay Staff · Send Invoices' },
        { icon: '💰', text: 'Start from just ₦3,000/month — No Accounting Degree Needed' },
        { icon: '🇳🇬', text: 'Built for Global Businesses — Shops, Pharmacies, Schools & More' },
        { icon: '🔐', text: 'Your Data is Safe — Encrypted & Backed Up Every Hour' },
        { icon: '📱', text: 'Works on Any Device — Phone, Tablet or Laptop' },
        { icon: '🤝', text: 'Become a Partner — Earn 35% Commission on Every Sale' },
        { icon: '⚡', text: 'Real-Time Reports · Instant Receipts · One-Click Tax Summaries' },
    ];

    const container = document.getElementById('announceContainer');
    if (!container) return;

    // Build all message elements
    msgs.forEach((m, i) => {
        const el = document.createElement('div');
        el.className = 'announce-msg' + (i === 0 ? ' active' : '');
        el.innerHTML = `<span class="announce-dot"></span><span style="font-size:15px;">${m.icon}</span><span>${m.text}</span>`;
        container.appendChild(el);
    });

    let current = 0;
    const allMsgs = container.querySelectorAll('.announce-msg');

    setInterval(() => {
        const prev = current;
        current = (current + 1) % msgs.length;

        allMsgs[prev].classList.remove('active');
        allMsgs[prev].classList.add('exit');

        setTimeout(() => {
            allMsgs[prev].classList.remove('exit');
            allMsgs[current].classList.add('active');
        }, 500);
    }, 3500);
})();
</script>

<!-- Hero Section — Split Layout -->
<section id="home" style="
    min-height: 100vh;
    padding-top: 0;
    display: flex;
    align-items: stretch;
    overflow: hidden;
    position: relative;
">

    <!-- LEFT PANEL: Single clean dashboard card -->
    <div class="hero-left-panel" style="
        flex: 1;
        background: #eef1f7;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 40px 32px 80px;
    ">
        <!-- Subtle dot grid background -->
        <div style="position:absolute;inset:0;background-image:radial-gradient(circle,#c5a059 1px,transparent 1px);background-size:40px 40px;opacity:0.10;"></div>

        <!-- ONE SINGLE DASHBOARD CARD -->
        <div style="
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 12px 48px rgba(0,35,71,0.14), 0 2px 8px rgba(0,35,71,0.06);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            position: relative;
            z-index: 2;
        ">
            <!-- Card titlebar -->
            <div style="background:#f5f7fa; padding:14px 20px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #e8ecf0;">
                <div style="width:11px;height:11px;border-radius:50%;background:#ff5f57;"></div>
                <div style="width:11px;height:11px;border-radius:50%;background:#ffbd2e;"></div>
                <div style="width:11px;height:11px;border-radius:50%;background:#28c840;"></div>
                <span style="margin-left:12px;font-size:11px;font-weight:600;color:#8a92a0;letter-spacing:.5px;">SmatBook — Business Dashboard</span>
                <span style="margin-left:auto;background:#f0fff4;color:#15803d;font-size:9px;font-weight:800;padding:3px 10px;border-radius:20px;">● LIVE</span>
            </div>

            <!-- Card body -->
            <div style="padding:20px;">

                <!-- KPI Row -->
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px;">
                    <div style="background:#f8faff;border:1px solid #e8ecf4;border-radius:10px;padding:12px 14px;border-left:3px solid var(--muji-gold);">
                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#8a92a0;margin-bottom:5px;">Revenue</div>
                        <div style="font-size:17px;font-weight:900;color:var(--muji-blue-deep);line-height:1;">₦4.28M</div>
                        <div style="font-size:9px;color:#22c55e;font-weight:700;margin-top:4px;">↑ 24.8%</div>
                    </div>
                    <div style="background:#f8faff;border:1px solid #e8ecf4;border-radius:10px;padding:12px 14px;border-left:3px solid #002347;">
                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#8a92a0;margin-bottom:5px;">Invoices</div>
                        <div style="font-size:17px;font-weight:900;color:var(--muji-blue-deep);line-height:1;">24</div>
                        <div style="font-size:9px;color:#f59e0b;font-weight:700;margin-top:4px;">3 pending</div>
                    </div>
                    <div style="background:#f8faff;border:1px solid #e8ecf4;border-radius:10px;padding:12px 14px;border-left:3px solid #ef4444;">
                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#8a92a0;margin-bottom:5px;">Low Stock</div>
                        <div style="font-size:17px;font-weight:900;color:#ef4444;line-height:1;">3</div>
                        <div style="font-size:9px;color:#ef4444;font-weight:700;margin-top:4px;">Reorder now</div>
                    </div>
                </div>

                <!-- Revenue sparkline -->
                <div style="background:#f8faff;border:1px solid #e8ecf4;border-radius:12px;padding:14px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <span style="font-size:10px;font-weight:800;color:var(--muji-blue-deep);text-transform:uppercase;letter-spacing:.5px;">Monthly Revenue</span>
                        <span style="font-size:9px;color:#8a92a0;">Last 8 months</span>
                    </div>
                    <svg viewBox="0 0 300 70" style="width:100%;">
                        <defs>
                            <linearGradient id="spkGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#002347" stop-opacity=".18"/>
                                <stop offset="100%" stop-color="#002347" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <line x1="0" y1="15" x2="300" y2="15" stroke="#f0f4f8" stroke-width="1"/>
                        <line x1="0" y1="38" x2="300" y2="38" stroke="#f0f4f8" stroke-width="1"/>
                        <line x1="0" y1="60" x2="300" y2="60" stroke="#f0f4f8" stroke-width="1"/>
                        <path d="M0,58 L43,48 L86,42 L129,45 L172,30 L215,34 L258,20 L300,14 L300,70 L0,70Z" fill="url(#spkGrad)"/>
                        <polyline points="0,58 43,48 86,42 129,45 172,30 215,34 258,20 300,14" fill="none" stroke="#002347" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="300" cy="14" r="4" fill="#c5a059" stroke="#fff" stroke-width="2"/>
                        <text x="2" y="68" font-size="7" fill="#8a92a0">Jul</text>
                        <text x="82" y="68" font-size="7" fill="#8a92a0">Sep</text>
                        <text x="168" y="68" font-size="7" fill="#8a92a0">Nov</text>
                        <text x="254" y="68" font-size="7" fill="#c5a059" font-weight="700">Feb</text>
                    </svg>
                </div>

                <!-- Stock levels -->
                <div style="background:#f8faff;border:1px solid #e8ecf4;border-radius:12px;padding:14px;margin-bottom:14px;">
                    <div style="font-size:10px;font-weight:800;color:var(--muji-blue-deep);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Stock Levels</div>
                    <div style="display:flex;flex-direction:column;gap:7px;">
                        <div><div style="display:flex;justify-content:space-between;font-size:10px;font-weight:600;color:#3d4a5c;margin-bottom:3px;"><span>Paracetamol 500mg</span><span>87 units</span></div><div style="height:5px;background:#e8ecf4;border-radius:99px;"><div style="width:87%;height:100%;background:#002347;border-radius:99px;"></div></div></div>
                        <div><div style="display:flex;justify-content:space-between;font-size:10px;font-weight:600;color:#3d4a5c;margin-bottom:3px;"><span>Vitamin C Tabs</span><span>62 units</span></div><div style="height:5px;background:#e8ecf4;border-radius:99px;"><div style="width:62%;height:100%;background:#c5a059;border-radius:99px;"></div></div></div>
                        <div><div style="display:flex;justify-content:space-between;font-size:10px;font-weight:600;color:#3d4a5c;margin-bottom:3px;"><span>Zinc Sulphate</span><span style="color:#ef4444;font-weight:800;">9 units ⚠</span></div><div style="height:5px;background:#e8ecf4;border-radius:99px;"><div style="width:9%;height:100%;background:#ef4444;border-radius:99px;"></div></div></div>
                    </div>
                </div>

                <!-- Bottom row: Payroll + Donut -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div style="background:linear-gradient(135deg,var(--muji-blue-deep),#003d6b);border-radius:12px;padding:14px;">
                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.6);margin-bottom:6px;">Payroll Due</div>
                        <div style="font-size:20px;font-weight:900;color:#fff;">₦840K</div>
                        <div style="font-size:9px;color:var(--muji-gold);font-weight:700;margin-top:5px;">12 staff · Mar 1st</div>
                    </div>
                    <div style="background:#f8faff;border:1px solid #e8ecf4;border-radius:12px;padding:14px;text-align:center;">
                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#8a92a0;margin-bottom:6px;">Profit Split</div>
                        <svg viewBox="0 0 80 80" style="width:64px;height:64px;display:block;margin:0 auto;">
                            <circle cx="40" cy="40" r="28" fill="none" stroke="#f0f4f8" stroke-width="11"/>
                            <circle cx="40" cy="40" r="28" fill="none" stroke="#002347" stroke-width="11" stroke-dasharray="78 98" stroke-dashoffset="22" transform="rotate(-90 40 40)"/>
                            <circle cx="40" cy="40" r="28" fill="none" stroke="#c5a059" stroke-width="11" stroke-dasharray="46 130" stroke-dashoffset="-56" transform="rotate(-90 40 40)"/>
                            <circle cx="40" cy="40" r="22" fill="white"/>
                            <text x="40" y="37" text-anchor="middle" font-size="10" font-weight="900" fill="#002347">62%</text>
                            <text x="40" y="47" text-anchor="middle" font-size="6" fill="#8a92a0">profit</text>
                        </svg>
                    </div>
                </div>

            </div><!-- end card body -->
        </div><!-- end single card -->

        <!-- SCROLLING CURRENCY TICKER (bottom of left panel) -->
        <div style="position:absolute;bottom:0;left:0;right:0;background:var(--muji-blue-deep);border-top:2px solid var(--muji-gold);padding:10px 0;overflow:hidden;z-index:10;">
            <div id="hero-ticker-track" style="display:flex;gap:0;white-space:nowrap;animation:tickerScroll 28s linear infinite;width:max-content;">
                <!-- Filled by JS -->
            </div>
        </div>

    </div><!-- end left panel -->


    <!-- RIGHT PANEL: Dark background with circular text area -->
    <div class="hero-right-panel" style="
        width: 52%;
        background: var(--muji-blue-deep);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 80px 50px;
        overflow: hidden;
        min-height: 100vh;
    ">
        <!-- Background gold rings -->
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:600px; height:600px; border-radius:50%; border:1px solid rgba(197,160,89,0.08); pointer-events:none;"></div>
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:480px; height:480px; border-radius:50%; border:1px solid rgba(197,160,89,0.12); pointer-events:none;"></div>
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:350px; height:350px; border-radius:50%; border:1px solid rgba(197,160,89,0.18); pointer-events:none;"></div>

        <!-- Gold glow blob -->
        <div style="position:absolute; top:-80px; right:-80px; width:300px; height:300px; border-radius:50%; background:radial-gradient(circle, rgba(197,160,89,0.18) 0%, transparent 70%); pointer-events:none;"></div>
        <div style="position:absolute; bottom:-60px; left:-60px; width:250px; height:250px; border-radius:50%; background:radial-gradient(circle, rgba(197,160,89,0.12) 0%, transparent 70%); pointer-events:none;"></div>

        <!-- THE DARK CIRCLE with text -->
        <div class="hero-text-circle" style="
            position: relative;
            width: 540px; height: 540px;
            border-radius: 50%;
            background: radial-gradient(circle at 40% 40%, #003060 0%, #001529 60%, #000d1a 100%);
            border: 2px solid rgba(197,160,89,0.35);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center;
            padding: 60px 55px;
            box-shadow:
                0 0 0 12px rgba(197,160,89,0.06),
                0 0 0 28px rgba(197,160,89,0.03),
                0 40px 80px rgba(0,0,0,0.5);
            animation: circleGlow 4s ease-in-out infinite;
            z-index: 2;
        ">
            <!-- Smat Book title inside circle -->
            <div style="
                font-size: 14px; font-weight: 900;
                letter-spacing: 7px; text-transform: uppercase;
                color: var(--muji-gold);
                margin-bottom: 10px;
                opacity: 0.85;
            ">SMAT BOOK</div>

            <h1 style="
                font-size: clamp(1.9rem, 3.5vw, 2.8rem);
                font-weight: 900;
                color: #fff;
                line-height: 1.25;
                margin-bottom: 16px;
                letter-spacing: -0.5px;
            ">
                Run Your Business.<br>
                <span style="
                    background: linear-gradient(to right, var(--muji-gold), var(--muji-gold-bright));
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                ">Know Your Money.</span>
            </h1>

            <p style="
                font-size: 13px;
                color: rgba(255,255,255,0.78);
                line-height: 1.85;
                margin-bottom: 24px;
                font-weight: 500;
            ">
                Track sales, manage stock,<br>
                pay staff, send invoices —<br>
                <strong style="color:var(--muji-gold-bright); font-weight:800;">from just ₦3,000/month.</strong><br>
                No stress. No confusion.
            </p>

            <!-- CTAs inside circle -->
            <div style="display:flex; flex-direction:column; gap:10px; width:100%;">
                <a href="#licensing" class="btn-muji-red" style="justify-content:center; padding:13px 24px; font-size:0.8rem; border-radius:30px;">
                    <i class="fas fa-shopping-cart"></i> Start Today
                </a>
                <a href="{{ route('saas-register', ['type' => 'manager']) }}" style="
                    display:inline-flex; align-items:center; justify-content:center; gap:8px;
                    background: transparent;
                    color: var(--muji-gold) !important;
                    border: 1.5px solid rgba(197,160,89,0.5);
                    padding: 12px 24px;
                    font-weight: 800; border-radius: 30px;
                    text-transform: uppercase; letter-spacing: 1px;
                    font-size: 0.75rem; text-decoration:none;
                    transition: all 0.3s ease;
                " onmouseover="this.style.background='rgba(197,160,89,0.12)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-handshake"></i> Become a Partner
                </a>
            </div>
        </div>

    </div><!-- end right panel -->

</section>

<style>
/* Gadget float animations — all different timings for natural feel */
@keyframes gadgetFloat1 {
    0%,100% { transform: translateY(0px) rotate(0deg); }
    50%      { transform: translateY(-12px) rotate(0.5deg); }
}
@keyframes gadgetFloat2 {
    0%,100% { transform: translateY(0px); }
    50%      { transform: translateY(-18px); }
}
@keyframes gadgetFloat3 {
    0%,100% { transform: translateX(-50%) translateY(0px); }
    50%      { transform: translateX(-50%) translateY(-10px); }
}
@keyframes gadgetFloat4 {
    0%,100% { transform: translateY(0px) rotate(-0.5deg); }
    50%      { transform: translateY(-14px) rotate(0.5deg); }
}
@keyframes gadgetFloat5 {
    0%,100% { transform: translateY(0px); }
    33%      { transform: translateY(-8px); }
    66%      { transform: translateY(-16px); }
}
@keyframes circleGlow {
    0%,100% { box-shadow: 0 0 0 12px rgba(197,160,89,0.06), 0 0 0 28px rgba(197,160,89,0.03), 0 40px 80px rgba(0,0,0,0.5); }
    50%      { box-shadow: 0 0 0 16px rgba(197,160,89,0.10), 0 0 0 36px rgba(197,160,89,0.05), 0 40px 80px rgba(0,0,0,0.5), 0 0 60px rgba(197,160,89,0.12); }
}
@keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.5; transform:scale(1.4); }
}

@keyframes tickerScroll {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
/* Responsive: stack on mobile */
@media (max-width: 991px) {
    #home { flex-direction: column; }
    .hero-left-panel { min-height: 65vw !important; width: 100% !important; flex: none !important; }
    .hero-right-panel { width: 100% !important; min-height: auto !important; padding: 50px 24px 60px !important; }
    .hero-text-circle { width: 380px !important; height: 380px !important; padding: 44px 36px !important; }
    .hero-text-circle h1 { font-size: 1.35rem !important; }
}
@media (max-width: 576px) {
    .hero-text-circle { width: 320px !important; height: 320px !important; }
}
</style>

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

{{-- ═══════════════════════════════════════════════════════════
     ★ NEW: DASHBOARD FEATURES SHOWCASE (inserted after stats)
═══════════════════════════════════════════════════════════ --}}

<!-- FEATURE 1: Sales & Revenue Dashboard -->
<section class="feat-section" id="platform-features">
    <div class="container">
        <div class="text-center mb-5">
            <span class="feat-eyebrow" style="justify-content:center; display:inline-flex;">Platform Features</span>
            <h2 class="feat-h2 text-center">Everything your business needs, <span class="accent">built in.</span></h2>
            <p class="text-muted mx-auto" style="max-width:640px; font-size:15px; line-height:1.9;">
                SmatBook is not just bookkeeping software. It's a complete business management system — from your first sale to your annual tax filing.
            </p>
        </div>

        <div class="row align-items-center g-5">
            <!-- Dashboard Mockup -->
            <div class="col-lg-7 order-lg-1" data-aos="fade-right">
                <div class="position-relative" style="padding: 20px 20px 20px 0;">
                    <div class="float-badge" style="top: 0; left: -10px;">
                        <div class="float-badge-icon" style="background:#dcfce7;">
                            <svg width="18" height="18" fill="none" stroke="#15803d" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                        </div>
                        <div><div class="float-badge-val">+24.8%</div><div class="float-badge-lbl">Monthly Revenue</div></div>
                    </div>
                    <div class="float-badge float-badge-2" style="bottom: 0; right: -10px;">
                        <div class="float-badge-icon" style="background:#fef9c3;">
                            <svg width="18" height="18" fill="none" stroke="#b45309" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div><div class="float-badge-val">Real-time</div><div class="float-badge-lbl">Live data sync</div></div>
                    </div>
                    <div class="dash-frame">
                        <div class="dash-titlebar">
                            <div class="dash-dot dash-dot-r"></div><div class="dash-dot dash-dot-y"></div><div class="dash-dot dash-dot-g"></div>
                            <span class="dash-titlebar-text">SmatBook — Financial Command Center</span>
                        </div>
                        <div class="d-flex" style="min-height: 340px;">
                            <div class="dash-sidebar">
                                <div class="dash-sidebar-icon active"><svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
                                <div class="dash-sidebar-icon"><svg fill="none" stroke="rgba(255,255,255,.5)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
                                <div class="dash-sidebar-icon"><svg fill="none" stroke="rgba(255,255,255,.5)" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg></div>
                            </div>
                            <div class="dash-main">
                                <div class="dash-header-row">
                                    <span class="dash-page-title">Financial Overview</span>
                                    <span class="dash-badge">LIVE · {{ date('M Y') }}</span>
                                </div>
                                <div class="dash-kpi-row">
                                    <div class="dash-kpi"><div class="dash-kpi-label">Total Revenue</div><div class="dash-kpi-val">₦4.2M</div><div class="dash-kpi-sub">↑ 18.4% this month</div></div>
                                    <div class="dash-kpi"><div class="dash-kpi-label">Total Sales</div><div class="dash-kpi-val">1,248</div><div class="dash-kpi-sub">↑ 12.1% vs last</div></div>
                                    <div class="dash-kpi"><div class="dash-kpi-label">Outstanding</div><div class="dash-kpi-val">₦380K</div><div class="dash-kpi-sub neg">3 invoices due</div></div>
                                </div>
                                <div class="dash-chart-row">
                                    <div class="dash-chart-box">
                                        <div class="dash-chart-title">Monthly Revenue Trend</div>
                                        <svg viewBox="0 0 280 90" style="width:100%;">
                                            <defs>
                                                <linearGradient id="areaG" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#002347" stop-opacity=".15"/><stop offset="100%" stop-color="#002347" stop-opacity="0"/></linearGradient>
                                            </defs>
                                            <line x1="0" y1="20" x2="280" y2="20" stroke="#f0f4f8" stroke-width="1"/>
                                            <line x1="0" y1="50" x2="280" y2="50" stroke="#f0f4f8" stroke-width="1"/>
                                            <line x1="0" y1="75" x2="280" y2="75" stroke="#f0f4f8" stroke-width="1"/>
                                            <path d="M0,70 L35,55 L70,45 L105,50 L140,35 L175,40 L210,25 L245,30 L280,18 L280,90 L0,90 Z" fill="url(#areaG)"/>
                                            <polyline points="0,70 35,55 70,45 105,50 140,35 175,40 210,25 245,30 280,18" fill="none" stroke="#002347" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="280" cy="18" r="4" fill="#c5a059" stroke="#fff" stroke-width="2"/>
                                            <text x="0" y="88" font-size="7" fill="#8a92a0">Jul</text><text x="68" y="88" font-size="7" fill="#8a92a0">Sep</text><text x="138" y="88" font-size="7" fill="#8a92a0">Nov</text><text x="208" y="88" font-size="7" fill="#8a92a0">Jan</text><text x="258" y="88" font-size="7" fill="#c5a059">Feb</text>
                                        </svg>
                                    </div>
                                    <div class="dash-chart-box">
                                        <div class="dash-chart-title">Revenue Split</div>
                                        <svg viewBox="0 0 100 100" style="width:100%; max-height:100px;">
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#f0f4f8" stroke-width="16"/>
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#002347" stroke-width="16" stroke-dasharray="113 113" stroke-dashoffset="28" transform="rotate(-90 50 50)"/>
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#c5a059" stroke-width="16" stroke-dasharray="45 181" stroke-dashoffset="-85" transform="rotate(-90 50 50)"/>
                                            <circle cx="50" cy="50" r="26" fill="white"/>
                                            <text x="50" y="47" text-anchor="middle" font-size="11" font-weight="800" fill="#002347">68%</text>
                                            <text x="50" y="56" text-anchor="middle" font-size="7" fill="#8a92a0">Sales</text>
                                        </svg>
                                    </div>
                                </div>
                                <table class="dash-table">
                                    <tr><th>Customer</th><th>Amount</th><th>Status</th></tr>
                                    <tr><td>Adaobi Nwosu</td><td>₦85,000</td><td><span class="dash-status paid">Paid</span></td></tr>
                                    <tr><td>TechBridge Ltd</td><td>₦240,000</td><td><span class="dash-status pending">Pending</span></td></tr>
                                    <tr><td>Kalu Stores</td><td>₦62,500</td><td><span class="dash-status paid">Paid</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3 Benefit Cards -->
            <div class="col-lg-5 order-lg-2" data-aos="fade-left">
                <div class="feat-eyebrow">01 — Sales & Revenue</div>
                <h2 class="feat-h2">Know exactly <span class="accent">where every naira</span> is going</h2>
                <p class="text-muted" style="line-height:1.9; font-size:15px;">Get a live, bird's-eye view of your business finances. SmatBook's revenue dashboard gives you instant clarity on sales performance, outstanding invoices, and profit trends — all on one screen.</p>
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="strip-card p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="benefit-icon flex-shrink-0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px; font-weight:700; color:var(--muji-blue-deep);">Live Revenue Tracking</h6><p class="mb-0" style="font-size:12px; color:#6b7280; line-height:1.6;">See your sales totals update in real time as transactions happen across your business locations.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="strip-card p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="benefit-icon flex-shrink-0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px; font-weight:700; color:var(--muji-blue-deep);">Instant Invoice Management</h6><p class="mb-0" style="font-size:12px; color:#6b7280; line-height:1.6;">Generate, send, and track invoices automatically. Get notified the moment a client pays or a payment goes overdue.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="strip-card p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="benefit-icon flex-shrink-0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px; font-weight:700; color:var(--muji-blue-deep);">Multi-Currency Support</h6><p class="mb-0" style="font-size:12px; color:#6b7280; line-height:1.6;">Record and report in NGN, USD, GBP, EUR and more — perfect for businesses with international clients.</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURE 2: Inventory Control -->
<section class="feat-section feat-section--alt">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- 3 Benefit Cards LEFT -->
            <div class="col-lg-5" data-aos="fade-right">
                <div class="feat-eyebrow">02 — Inventory Control</div>
                <h2 class="feat-h2">Never run out of <span class="accent">stock again</span></h2>
                <p class="text-muted" style="line-height:1.9; font-size:15px;">SmatBook's inventory engine monitors every product in your store in real time. Set reorder thresholds, track expiry dates, and get alerts before stock runs dry.</p>
                <div style="margin-top:24px;">
                    <div class="prog-bar-wrap"><div class="prog-label"><span>Stock Accuracy</span><span>98.4%</span></div><div class="prog-track"><div class="prog-fill" style="--target-width:98.4%;"></div></div></div>
                    <div class="prog-bar-wrap"><div class="prog-label"><span>Waste Reduction</span><span>76%</span></div><div class="prog-track"><div class="prog-fill" style="--target-width:76%;"></div></div></div>
                    <div class="prog-bar-wrap"><div class="prog-label"><span>Reorder Automation</span><span>89%</span></div><div class="prog-track"><div class="prog-fill" style="--target-width:89%;"></div></div></div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="strip-card p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="benefit-icon flex-shrink-0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px; font-weight:700; color:var(--muji-blue-deep);">Smart Reorder Alerts</h6><p class="mb-0" style="font-size:12px; color:#6b7280; line-height:1.6;">Automated low-stock notifications so your team restocks before customers notice an empty shelf.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="strip-card p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="benefit-icon flex-shrink-0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px; font-weight:700; color:var(--muji-blue-deep);">Expiry Date Tracking</h6><p class="mb-0" style="font-size:12px; color:#6b7280; line-height:1.6;">Tag perishable items with expiry dates — SmatBook flags them before they become a liability.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="strip-card p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="benefit-icon flex-shrink-0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px; font-weight:700; color:var(--muji-blue-deep);">Supplier Management</h6><p class="mb-0" style="font-size:12px; color:#6b7280; line-height:1.6;">Keep a database of all your suppliers with pricing history, lead times, and contact details for quick reordering.</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Mockup RIGHT -->
            <div class="col-lg-7" data-aos="fade-left">
                <div class="position-relative" style="padding: 20px 0 20px 20px;">
                    <div class="float-badge" style="top: 0; right: -10px;">
                        <div class="float-badge-icon" style="background:#fee2e2;"><svg width="18" height="18" fill="none" stroke="#dc2626" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                        <div><div class="float-badge-val">3 Items</div><div class="float-badge-lbl">Low stock alert</div></div>
                    </div>
                    <div class="dash-frame dash-frame-alt">
                        <div class="dash-titlebar">
                            <div class="dash-dot dash-dot-r"></div><div class="dash-dot dash-dot-y"></div><div class="dash-dot dash-dot-g"></div>
                            <span class="dash-titlebar-text">SmatBook — Inventory Management</span>
                        </div>
                        <div class="d-flex" style="min-height: 360px;">
                            <div class="dash-sidebar">
                                <div class="dash-sidebar-icon"><svg fill="none" stroke="rgba(255,255,255,.5)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
                                <div class="dash-sidebar-icon active"><svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></div>
                            </div>
                            <div class="dash-main">
                                <div class="dash-header-row"><span class="dash-page-title">Inventory Overview</span><span class="dash-badge">482 SKUs</span></div>
                                <div class="dash-kpi-row">
                                    <div class="dash-kpi"><div class="dash-kpi-label">Total SKUs</div><div class="dash-kpi-val">482</div><div class="dash-kpi-sub">↑ 12 added today</div></div>
                                    <div class="dash-kpi"><div class="dash-kpi-label">Stock Value</div><div class="dash-kpi-val">₦2.1M</div><div class="dash-kpi-sub">↑ 5.4% this week</div></div>
                                    <div class="dash-kpi"><div class="dash-kpi-label">Low Stock</div><div class="dash-kpi-val" style="color:#ef4444">3</div><div class="dash-kpi-sub neg">Needs restocking</div></div>
                                </div>
                                <div class="dash-chart-box" style="margin-bottom:10px;">
                                    <div class="dash-chart-title">Top Products — Stock Levels</div>
                                    @php $products = [['Paracetamol 500mg',87,'#002347'],['Vitamin C Tabs',62,'#c5a059'],['Amoxicillin 250mg',18,'#ef4444'],['Ibuprofen 400mg',74,'#002347'],['Zinc Sulphate',9,'#ef4444']]; @endphp
                                    @foreach($products as $prod)
                                    <div style="margin-bottom:7px;">
                                        <div style="display:flex;justify-content:space-between;font-size:10px;margin-bottom:3px;font-weight:600;color:#3d4a5c;"><span>{{ $prod[0] }}</span><span>{{ $prod[1] }} units</span></div>
                                        <div style="height:6px;background:#f0f4f8;border-radius:99px;overflow:hidden;"><div style="width:{{ $prod[1] }}%;height:100%;background:{{ $prod[2] }};border-radius:99px;"></div></div>
                                    </div>
                                    @endforeach
                                </div>
                                <table class="dash-table">
                                    <tr><th>Product</th><th>Category</th><th>Qty</th><th>Status</th></tr>
                                    <tr><td>Paracetamol 500mg</td><td>Pharma</td><td>87</td><td><span class="dash-status paid">OK</span></td></tr>
                                    <tr><td>Zinc Sulphate</td><td>Vitamins</td><td>9</td><td><span class="dash-status due">Low</span></td></tr>
                                    <tr><td>Vitamin C Tabs</td><td>Vitamins</td><td>62</td><td><span class="dash-status paid">OK</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURE 3: Expenses & Reports (dark) -->
<section class="feat-section feat-section--dark">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Mockup LEFT -->
            <div class="col-lg-7" data-aos="fade-right">
                <div class="position-relative" style="padding:20px 20px 20px 0;">
                    <div class="float-badge" style="top:-10px; left:10px;">
                        <div class="float-badge-icon" style="background:#ede9fe;"><svg width="18" height="18" fill="none" stroke="#7c3aed" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                        <div><div class="float-badge-val">Auto</div><div class="float-badge-lbl">Bank reconciled</div></div>
                    </div>
                    <div class="dash-frame">
                        <div class="dash-titlebar">
                            <div class="dash-dot dash-dot-r"></div><div class="dash-dot dash-dot-y"></div><div class="dash-dot dash-dot-g"></div>
                            <span class="dash-titlebar-text">SmatBook — Expenses & Reports</span>
                        </div>
                        <div class="dash-main" style="padding:20px;">
                            <div class="dash-header-row"><span class="dash-page-title">P&L + Reports</span><span class="dash-badge" style="background:#f0fff4;color:#15803d;">✓ AUTO-GENERATED</span></div>
                            <div class="dash-kpi-row">
                                <div class="dash-kpi"><div class="dash-kpi-label">Total Expenses</div><div class="dash-kpi-val">₦1.4M</div><div class="dash-kpi-sub neg">↑ 6.2% vs last</div></div>
                                <div class="dash-kpi"><div class="dash-kpi-label">Net Profit</div><div class="dash-kpi-val">₦2.8M</div><div class="dash-kpi-sub">↑ 22.4% margin</div></div>
                                <div class="dash-kpi"><div class="dash-kpi-label">Reports Ready</div><div class="dash-kpi-val">6</div><div class="dash-kpi-sub">For this month</div></div>
                            </div>
                            <div class="dash-chart-box" style="margin-bottom:12px;">
                                <div class="dash-chart-title">Revenue vs Expenses — 12 Month View</div>
                                <svg viewBox="0 0 500 100" style="width:100%;">
                                    <defs>
                                        <linearGradient id="r2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#002347" stop-opacity=".2"/><stop offset="100%" stop-color="#002347" stop-opacity="0"/></linearGradient>
                                        <linearGradient id="e2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#c5a059" stop-opacity=".15"/><stop offset="100%" stop-color="#c5a059" stop-opacity="0"/></linearGradient>
                                    </defs>
                                    @foreach([15,40,65,90] as $gy2)<line x1="0" y1="{{ $gy2 }}" x2="500" y2="{{ $gy2 }}" stroke="#f0f4f8" stroke-width="1"/>@endforeach
                                    <path d="M0,80 L42,65 L84,58 L126,62 L168,50 L210,45 L252,48 L294,35 L336,38 L378,28 L420,25 L462,18 L500,12 L500,100 L0,100 Z" fill="url(#r2)"/>
                                    <polyline points="0,80 42,65 84,58 126,62 168,50 210,45 252,48 294,35 336,38 378,28 420,25 462,18 500,12" fill="none" stroke="#002347" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M0,95 L42,90 L84,85 L126,92 L168,82 L210,78 L252,84 L294,75 L336,79 L378,70 L420,67 L462,62 L500,58 L500,100 L0,100 Z" fill="url(#e2)"/>
                                    <polyline points="0,95 42,90 84,85 126,92 168,82 210,78 252,84 294,75 336,79 378,70 420,67 462,62 500,58" fill="none" stroke="#c5a059" stroke-width="2" stroke-dasharray="6,3" stroke-linecap="round" stroke-linejoin="round"/>
                                    @php $mL = ['M','A','M','J','J','A','S','O','N','D','J','F']; @endphp
                                    @foreach($mL as $mi2 => $ml2)<text x="{{ $mi2 * 42 + 4 }}" y="98" font-size="8" fill="#8a92a0">{{ $ml2 }}</text>@endforeach
                                    <rect x="370" y="5" width="8" height="3" fill="#002347" rx="1"/><text x="381" y="9" font-size="8" fill="#3d4a5c">Revenue</text>
                                    <rect x="370" y="14" width="8" height="2" fill="#c5a059" rx="1"/><text x="381" y="18" font-size="8" fill="#3d4a5c">Expenses</text>
                                </svg>
                            </div>
                            <table class="dash-table">
                                <tr><th>Report</th><th>Period</th><th>Status</th></tr>
                                <tr><td>Monthly P&L</td><td>Jan 2026</td><td><span class="dash-status paid">PDF Ready</span></td></tr>
                                <tr><td>VAT Summary</td><td>Q4 2025</td><td><span class="dash-status paid">XLSX Ready</span></td></tr>
                                <tr><td>Payroll Sheet</td><td>Jan 2026</td><td><span class="dash-status paid">PDF Ready</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3 Cards RIGHT -->
            <div class="col-lg-5" data-aos="fade-left">
                <div class="feat-eyebrow" style="color:var(--muji-gold);">03 — Expenses & Reports</div>
                <h2 class="feat-h2" style="color:#fff;">Board-ready reports <span class="accent">in one click</span></h2>
                <p style="color:rgba(255,255,255,.75); line-height:1.9; font-size:15px;">Stop spending weekends building spreadsheets. SmatBook generates polished financial reports automatically — daily, weekly, monthly, or on demand.</p>
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div style="background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:12px; padding:16px 18px;">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width:40px;height:40px;border-radius:10px;background:rgba(197,160,89,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px;font-weight:700;color:#fff;">Automatic Expense Categorization</h6><p class="mb-0" style="font-size:12px;color:rgba(255,255,255,.65);line-height:1.6;">SmatBook learns your spending patterns and auto-tags expenses to the right accounts without manual entry.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div style="background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:12px; padding:16px 18px;">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width:40px;height:40px;border-radius:10px;background:rgba(197,160,89,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px;font-weight:700;color:#fff;">One-Click Tax Reports</h6><p class="mb-0" style="font-size:12px;color:rgba(255,255,255,.65);line-height:1.6;">Generate VAT, PAYE, and annual tax summaries in seconds — fully formatted for FIRS submission.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div style="background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:12px; padding:16px 18px;">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width:40px;height:40px;border-radius:10px;background:rgba(197,160,89,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                                <div><h6 class="mb-1" style="font-size:14px;font-weight:700;color:#fff;">Bank Reconciliation</h6><p class="mb-0" style="font-size:12px;color:rgba(255,255,255,.65);line-height:1.6;">Import your bank statements and SmatBook matches every transaction automatically — zero manual reconciliation.</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6-Card Feature Strip -->
<section class="feat-strip">
    <div class="container">
        <div class="text-center mb-5">
            <span class="feat-eyebrow" style="justify-content:center; display:inline-flex;">Everything included</span>
            <h2 class="feat-h2 text-center">One platform. <span class="accent">Every function.</span></h2>
            <p class="text-muted mx-auto" style="max-width:600px; font-size:15px; line-height:1.9;">SmatBook brings together every tool your business needs to run — from staff management to customer records, POS to cloud backup.</p>
        </div>
        <div class="row g-4">
            @php $strips = [
                ['icon'=>'👥','bg'=>'#f0f4ff','title'=>'Staff & Payroll','desc'=>'Manage employee records, attendance, and process accurate payroll in minutes. Automatic PAYE deductions calculated for you.'],
                ['icon'=>'🧾','bg'=>'#fef9c3','title'=>'Receipts & POS','desc'=>'Turn any device into a point-of-sale terminal. Print or email branded receipts instantly after every sale.'],
                ['icon'=>'📊','bg'=>'#dcfce7','title'=>'Reports & Analytics','desc'=>'From daily sales summaries to quarterly board reports — generate any report with a single click, no accountant needed.'],
                ['icon'=>'🤝','bg'=>'#ffe4e6','title'=>'Customer CRM','desc'=>'Build detailed customer profiles, track purchase history, and send targeted promotions to your best buyers.'],
                ['icon'=>'🔐','bg'=>'#ede9fe','title'=>'Access Control','desc'=>'Create staff accounts with role-based permissions. Your cashier sees only the POS; your manager sees everything.'],
                ['icon'=>'☁️','bg'=>'#f0fdf4','title'=>'Cloud Backup','desc'=>'Your data is encrypted and backed up automatically every hour. Access your books from any device, anywhere in the world.'],
            ]; @endphp
            @foreach($strips as $s)
            <div class="col-lg-4 col-md-6" data-aos="fade-up">
                <div class="strip-card">
                    <div class="strip-icon" style="background:{{ $s['bg'] }};">{{ $s['icon'] }}</div>
                    <h5>{{ $s['title'] }}</h5>
                    <p>{{ $s['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     ★ END OF NEW FEATURES — original sections continue below
═══════════════════════════════════════════════════════════ --}}

<!-- Solutions Grid (ORIGINAL) -->
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

<!-- Capabilities Section (ORIGINAL) -->
<section class="py-5 bg-white" id="capabilities">
    <div class="container py-5">
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
                    SmatBook's proprietary Neural Forecasting Core (NFC) transcends legacy bookkeeping systems by analyzing over 600 unique financial variables in real-time. By mapping historical account volatility against current receivables, our engine provides a surgical liquidity horizon with 98.4% predictive accuracy.
                </p>
                <div class="p-4 rounded bg-light border-start border-4 border-warning shadow-sm">
                    <p class="mb-0 fst-italic small fw-bold">"We convert fragmented transaction streams into verified, high-definition foresight for the modern board."</p>
                </div>
            </div>
        </div>
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
                    Designed for organizations with complex hierarchical needs, SmatBook implements a "Cellular Governance" model that guarantees total transparency without compromising individual business unit security. Each subsidiary operates within a fortified node, feeding into a master dashboard while maintaining SOC2 Type II compliance.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Other Projects (ORIGINAL) -->
<section class="py-5 bg-light" id="team">
    <div class="container py-5">
        <div class="text-center mb-5">
            <span class="gold-label mb-2">Portfolio</span>
            <h2 class="fw-bold display-6" style="color: var(--muji-blue-deep);">Our <span style="color: var(--muji-gold);">Other Projects</span></h2>
        </div>
        <div class="row g-4">
            @php
            $team = [
                ['name'=>'Lahome Properties','role'=>'Real Estate Platform','img'=>'https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?auto=compress&cs=tinysrgb&w=1200','bio'=>'A global real estate listing ecosystem for owners, surveyors, legal advisers, agents, and every key stakeholder in the property market.','link'=>route('landing.projects.lahome')],
                ['name'=>'Master JAMB','role'=>'CBT Examination Platform','img'=>'https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1200&auto=format&fit=crop','bio'=>'An online CBT platform for schools and institutions, built for exam readiness, timed assessments, and performance tracking.','link'=>route('landing.projects.master-jamb')],
                ['name'=>'PayPlus','role'=>'Payment Gateway','img'=>'https://images.pexels.com/photos/4968634/pexels-photo-4968634.jpeg?auto=compress&cs=tinysrgb&w=1200','bio'=>'A global payment gateway designed for secure processing of everyday transactions across web, mobile, and enterprise channels.','link'=>route('landing.projects.payplus')],
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

<!-- Testimonials (ORIGINAL) -->
<section class="testimonial-section">
    <div class="container text-center mb-4">
        <span class="gold-label mb-2">Global Adoption</span>
        <h2 class="fw-bold h2 text-white">Institutional <span style="color: var(--muji-gold);">Validation</span></h2>
    </div>
    <div class="muji-carousel-viewport">
        <div class="muji-carousel-track">
            @php
            $tests = [
                ['name'=>'Chinedu Okafor','role'=>'CFO, Lagos Holdings','img'=>'https://images.unsplash.com/photo-1507591064344-4c6ce005b128?q=80&w=300&auto=format&fit=crop'],
                ['name'=>'Amina Bello','role'=>'Finance Director, Abuja Group','img'=>'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?q=80&w=300&auto=format&fit=crop'],
                ['name'=>'Michael Carter','role'=>'VP Finance, New York Capital','img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=300&auto=format&fit=crop'],
                ['name'=>'Emily Johnson','role'=>'Controller, Austin Ventures','img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=300&auto=format&fit=crop'],
                ['name'=>'Li Wei','role'=>'Treasury Lead, Shanghai Trade','img'=>'https://images.unsplash.com/photo-1521119989659-a83eee488004?q=80&w=300&auto=format&fit=crop'],
                ['name'=>'Chen Ming','role'=>'Payments Director, Beijing Commerce','img'=>'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=300&auto=format&fit=crop'],
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

<!-- Licensing (ORIGINAL) -->
<section class="py-5 bg-white" id="licensing">
    <div class="container py-5">
        <div class="text-center mb-5">
            <span class="gold-label mb-2">Service Access</span>
            <h2 class="fw-bold display-6 mb-5" style="color: var(--muji-blue-deep);">Enterprise <span style="color: var(--muji-gold);">Licensing</span></h2>
        </div>
        <div class="row g-4">
            @php
            $plans = [
                'Basic'       => ['ngn'=>3000,'feat'=>false,'benefits'=>['Centralized Ledgers','5 Core User Access','Daily Cloud Backups','Standard Email Support','Unified Reporting']],
                'Pro'         => ['ngn'=>7000,'feat'=>true, 'benefits'=>['Neural Engine Core','25 Premium User Access','Dedicated Priority Node','Real-time Analytics','Predictive Forecasting']],
                'Enterprise'  => ['ngn'=>15000,'feat'=>false,'benefits'=>['Full Neural Automation','Unlimited Access Nodes','Advanced API Gateway','Custom Fiscal Reports','IFRS Compliance Mapping']],
                'Institution' => ['ngn'=>null,'feat'=>false,'benefits'=>['Private Hybrid Core','SLA Performance Guarantee','On-site Technical Support','Bespoke Integrations','Governance Training']],
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
                    <a href="{{ url('/membership-plans') }}" class="btn {{ $p['feat'] ? 'btn-muji-red' : 'btn-outline-dark' }} w-100" style="border-radius: 4px; font-weight: 700;">ACQUIRE SYSTEM</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Footer (ORIGINAL) -->
<footer class="unified-footer-block" id="contact">
    <div class="footer-animated-bg"></div>
    <div class="container footer-content">
        <div class="row g-5 mb-5 pb-5 border-bottom" style="border-color: rgba(0,0,0,0.1) !important;">
            <div class="col-lg-5" data-aos="fade-right">
                <h2 class="fw-bold mb-4" style="color: var(--muji-blue-deep);">Uplink <span style="color: var(--muji-gold);">Support</span></h2>
                <p class="text-muted mb-4" style="line-height: 1.8;">Technical architects are available 24/7 for organizational assessment and rapid deployment. Contact us to initialize your institutional connection.</p>
                <div class="mb-5">
                    <p class="mb-2 small fw-bold"><i class="fas fa-map-marker-alt text-warning me-3"></i>12 Independence Layout, Enugu, Nigeria</p>
                    <p class="mb-2 small fw-bold"><i class="fas fa-phone-alt text-warning me-3"></i>+234 646 463 06</p>
                    <p class="small fw-bold"><i class="fas fa-envelope text-warning me-3"></i><a href="mailto:donvictorlive@gmail.com" class="text-decoration-none text-dark">donvictorlive@gmail.com</a></p>
                </div>
                @if(session('success'))<div class="alert alert-success mb-3">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger mb-3">{{ session('error') }}</div>@endif
                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <div class="mb-3"><input type="text" name="company_name" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Organization Name" value="{{ old('company_name') }}"></div>
                    <div class="mb-3"><input type="text" name="fullname" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Full Name" value="{{ old('fullname') }}" required></div>
                    <div class="mb-3"><input type="email" name="email" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Work Email" value="{{ old('email') }}" required></div>
                    <div class="mb-3"><input type="text" name="department" class="form-control rounded-1 py-3 border-0 shadow-sm" placeholder="Department (Optional)" value="{{ old('department') }}"></div>
                    <div class="mb-3"><textarea name="message" class="form-control rounded-1 border-0 shadow-sm" rows="4" placeholder="Brief Requirements" required>{{ old('message') }}</textarea></div>
                    <button type="submit" class="btn-muji-red w-100"><i class="fas fa-paper-plane me-2"></i>INITIALIZE CONNECTION</button>
                </form>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="map-frame">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15858.987654321!2d7.508333!3d6.458333!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1044a3d6f1a8e1e1%3A0x1234567890abcdef!2sIndependence%20Layout%2C%20Enugu!5e0!3m2!1sen!2sng!4v1234567890123" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
        <div class="row g-4 text-center text-md-start">
            <div class="col-lg-4">
                <h3 class="fw-bold mb-3" style="color: var(--muji-blue-deep); letter-spacing: 1px;">SMATBOOK</h3>
                <p class="small text-muted" style="max-width: 320px;">Global Institutional Accounting Intelligence. Engineered for modern wealth governance and predictive capital allocation. Nigeria Hub Node: ENU-NG-12.</p>
                <div class="d-flex justify-content-center justify-content-md-start gap-4 mt-4">
                    <a href="{{ route('landing.about') }}" class="text-dark opacity-50 hover-gold" style="transition: all 0.3s;"><i class="fab fa-linkedin-in fa-lg"></i></a>
                    <a href="{{ route('landing.contact') }}" class="text-dark opacity-50 hover-gold" style="transition: all 0.3s;"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="{{ route('landing.policy') }}" class="text-dark opacity-50 hover-gold" style="transition: all 0.3s;"><i class="fab fa-facebook-f fa-lg"></i></a>
                </div>
            </div>
            <div class="col-md-3 col-lg-2 ms-auto">
                <h6 class="fw-bold text-uppercase mb-4" style="color: var(--muji-blue-deep); letter-spacing: 1px;">Platform</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><a href="#solutions" class="text-muted text-decoration-none hover-gold">Neural Engine Core</a></li>
                    <li class="mb-2"><a href="#capabilities" class="text-muted text-decoration-none hover-gold">Security Hub</a></li>
                    <li class="mb-2"><a href="#licensing" class="text-muted text-decoration-none hover-gold">API Documentation</a></li>
                </ul>
            </div>
            <div class="col-md-3 col-lg-2">
                <h6 class="fw-bold text-uppercase mb-4" style="color: var(--muji-blue-deep); letter-spacing: 1px;">Support</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><a href="{{ route('landing.contact') }}" class="text-muted text-decoration-none hover-gold">Knowledge Base</a></li>
                    <li class="mb-2"><a href="{{ route('landing.contact') }}" class="text-muted text-decoration-none hover-gold">SLA Status</a></li>
                    <li class="mb-2"><a href="{{ route('saas-login') }}" class="text-muted text-decoration-none hover-gold">Global Deployment</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-5 pt-4 text-center border-top" style="border-color: rgba(0,0,0,0.05) !important;">
            <p class="small text-muted mb-0">© 2026 SmatBook Intelligence Enterprise. Licensed for Global Financial Governance.</p>
        </div>
    </div>
</footer>

<!-- JavaScript (ORIGINAL + progress bar animation) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
                const navCollapse = document.getElementById('mujiNav');
                if (navCollapse && navCollapse.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                    if (bsCollapse) bsCollapse.hide();
                }
            }
        });
    });

    // Navbar scroll state
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', function() {
        nav.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Stat animation
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => { if (entry.isIntersecting) entry.target.style.animation = 'fadeInUp 0.6s ease-out'; });
    }, { threshold: 0.5, rootMargin: '0px 0px -100px 0px' });
    document.querySelectorAll('.stat-number').forEach(stat => observer.observe(stat));

    // Progress bar animation
    const progFills = document.querySelectorAll('.prog-fill');
    const progObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('animated'); progObserver.unobserve(entry.target); } });
    }, { threshold: 0.3 });
    progFills.forEach(f => progObserver.observe(f));

    // Geo pricing
    const countrySelector = document.getElementById('countrySelectorLanding');
    const countryMap = {
        NG:{currency:'NGN',locale:'en-NG'},US:{currency:'USD',locale:'en-US'},CN:{currency:'CNY',locale:'zh-CN'},
        GB:{currency:'GBP',locale:'en-GB'},EU:{currency:'EUR',locale:'en-IE'},CA:{currency:'CAD',locale:'en-CA'},
        IN:{currency:'INR',locale:'en-IN'},AE:{currency:'AED',locale:'en-AE'},ZA:{currency:'ZAR',locale:'en-ZA'},
        KE:{currency:'KES',locale:'en-KE'},GH:{currency:'GHS',locale:'en-GH'}
    };
    const staticNgnRates = {NGN:1,USD:0.00067,CNY:0.0048,GBP:0.00053,EUR:0.00062,CAD:0.00091,INR:0.056,AED:0.00246,ZAR:0.0125,KES:0.086,GHS:0.0105};
    const regionFromLocale = () => { try { const locale = Intl.DateTimeFormat().resolvedOptions().locale||navigator.language||'en-NG'; return (locale.split('-').pop().toUpperCase().length===2?locale.split('-').pop().toUpperCase():'NG'); } catch(e){return'NG';} };
    const normalizeCountry = (raw) => { const code=String(raw||'').toUpperCase(); const eu=['FR','DE','ES','IT','PT','NL','BE','AT','IE','FI','SE','DK','PL','CZ','GR','RO','HU']; if(eu.includes(code))return'EU'; if(countryMap[code])return code; return'NG'; };
    const fetchRates = async () => { const cacheKey='smat_rates_ngn_v1'; const cached=sessionStorage.getItem(cacheKey); const cachedTime=Number(sessionStorage.getItem(cacheKey+'_time')||0); if(cached&&(Date.now()-cachedTime)<21600000)return JSON.parse(cached); try{const resp=await fetch('https://open.er-api.com/v6/latest/NGN',{cache:'no-store'});const data=await resp.json();if(data?.rates){sessionStorage.setItem(cacheKey,JSON.stringify(data.rates));sessionStorage.setItem(cacheKey+'_time',String(Date.now()));return data.rates;}}catch(e){}return staticNgnRates; };
    const renderGeoPrices = async (countryCode) => { const geo=countryMap[normalizeCountry(countryCode)]||countryMap.NG; const rates=await fetchRates(); const rate=rates[geo.currency]||staticNgnRates[geo.currency]||1; document.querySelectorAll('.geo-price').forEach(node=>{const ngnValue=Number(node.dataset.ngn||0);const converted=ngnValue*rate;try{node.textContent=new Intl.NumberFormat(geo.locale,{style:'currency',currency:geo.currency,maximumFractionDigits:0}).format(converted);}catch(e){node.textContent=`${geo.currency} ${Math.round(converted).toLocaleString()}`;}}); };
    const applyCountry = (countryCode) => { const normalized=normalizeCountry(countryCode); localStorage.setItem('smat_country',normalized); document.cookie=`smat_country=${normalized}; path=/; max-age=31536000; SameSite=Lax`; if(countrySelector)countrySelector.value=normalized; renderGeoPrices(normalized); };
    const initCountry = async () => { const cookieMatch=document.cookie.match(/(?:^|;\s*)smat_country=([^;]+)/); const cookieCountry=cookieMatch?decodeURIComponent(cookieMatch[1]):''; const saved=localStorage.getItem('smat_country'); const serverDefault=@json($geoCountry ?? 'NG'); if(saved||cookieCountry){applyCountry(saved||cookieCountry);return;} applyCountry(serverDefault||'NG'); };
    if(countrySelector){countrySelector.addEventListener('change',function(){applyCountry(this.value);document.dispatchEvent(new CustomEvent('smat:geo-change',{detail:{country:this.value,...countryMap[normalizeCountry(this.value)]}}));});}
    document.addEventListener('smat:geo-change',(event)=>{const code=normalizeCountry(event?.detail?.country);if(countrySelector&&countrySelector.value!==code){countrySelector.value=code;renderGeoPrices(code);}});
    // ═══════════════════════════════════════════════
    // LIVE FX RATES — Hero gadget + scrolling ticker
    // ═══════════════════════════════════════════════
    const FX_PAIRS = [
        { label: '🇺🇸 USD/NGN', base: 'USD', flag: '🇺🇸' },
        { label: '🇬🇧 GBP/NGN', base: 'GBP', flag: '🇬🇧' },
        { label: '🇪🇺 EUR/NGN', base: 'EUR', flag: '🇪🇺' },
        { label: '🇨🇳 CNY/NGN', base: 'CNY', flag: '🇨🇳' },
        { label: '🇨🇦 CAD/NGN', base: 'CAD', flag: '🇨🇦' },
        { label: '🇮🇳 INR/NGN', base: 'INR', flag: '🇮🇳' },
        { label: '🇦🇪 AED/NGN', base: 'AED', flag: '🇦🇪' },
        { label: '🇿🇦 ZAR/NGN', base: 'ZAR', flag: '🇿🇦' },
        { label: '🇰🇪 KES/NGN', base: 'KES', flag: '🇰🇪' },
        { label: '🇬🇭 GHS/NGN', base: 'GHS', flag: '🇬🇭' },
    ];

    // Fallback static NGN rates (1 unit of currency = X NGN)
    const FX_FALLBACK = {
        USD: 1620, GBP: 2050, EUR: 1740, CNY: 224,
        CAD: 1190, INR: 19.4, AED: 441, ZAR: 88, KES: 12.5, GHS: 106
    };

    let fxRates = { ...FX_FALLBACK };
    let prevRates = { ...FX_FALLBACK };

    async function fetchHeroFX() {
        try {
            const res = await fetch('https://open.er-api.com/v6/latest/NGN', { cache: 'no-store' });
            const data = await res.json();
            if (data && data.rates) {
                // Convert: 1 NGN = X foreign → so 1 foreign = 1/X NGN
                FX_PAIRS.forEach(p => {
                    const rate = data.rates[p.base];
                    if (rate && rate > 0) {
                        prevRates[p.base] = fxRates[p.base] || (1 / rate);
                        fxRates[p.base] = parseFloat((1 / rate).toFixed(2));
                    }
                });
            }
        } catch (e) {
            // Use fallback silently
        }
        updateHeroFXGrid();
        buildTicker();
    }

    function updateHeroFXGrid() {
        const gridPairs = ['USD/NGN', 'GBP/NGN', 'EUR/NGN', 'CNY/NGN'];
        const baseMap = { 'USD/NGN': 'USD', 'GBP/NGN': 'GBP', 'EUR/NGN': 'EUR', 'CNY/NGN': 'CNY' };
        document.querySelectorAll('#hero-fx-grid .fx-item').forEach(item => {
            const pair = item.dataset.pair;
            const base = baseMap[pair];
            const rate = fxRates[base] || FX_FALLBACK[base];
            const prev = prevRates[base] || rate;
            const up = rate >= prev;
            item.querySelector('.fx-val').textContent = '₦' + rate.toLocaleString('en-NG', { maximumFractionDigits: 1 });
            const chgEl = item.querySelector('.fx-chg');
            chgEl.textContent = up ? '▲' : '▼';
            chgEl.style.color = up ? '#22c55e' : '#ef4444';
        });
    }

    function buildTicker() {
        const track = document.getElementById('hero-ticker-track');
        if (!track) return;

        const items = FX_PAIRS.map(p => {
            const rate = fxRates[p.base] || FX_FALLBACK[p.base];
            const prev = prevRates[p.base] || rate;
            const up = rate >= prev;
            const arrow = up ? '▲' : '▼';
            const color = up ? '#22c55e' : '#ef4444';
            return `<span style="
                display:inline-flex; align-items:center; gap:8px;
                padding: 0 28px;
                font-size: 12px; font-weight: 700;
                color: #fff;
                border-right: 1px solid rgba(197,160,89,0.2);
            ">
                <span style="font-size:14px;">${p.flag}</span>
                <span style="color:rgba(255,255,255,.6); font-size:11px;">${p.label.split(' ')[1]}</span>
                <span style="color:#fff; font-weight:900;">₦${rate.toLocaleString('en-NG', { maximumFractionDigits: 1 })}</span>
                <span style="color:${color}; font-size:11px;">${arrow}</span>
            </span>`;
        }).join('');

        // Duplicate for seamless infinite scroll
        track.innerHTML = items + items;
    }

    // Initial load + refresh every 60s
    fetchHeroFX();
    setInterval(fetchHeroFX, 60000);

    initCountry();
});
</script>

@endsection
