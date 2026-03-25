<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $routeName = Route::currentRouteName();
        $publicSeoMap = [
            'landing.index' => [
                'title' => 'AI Accounting Software, ERP and Business Automation',
                'description' => 'SmartProbook helps businesses manage accounting, invoicing, inventory, payments, reports, and operational workflows from one cloud platform.',
                'keywords' => 'AI accounting software, ERP software, invoicing software, inventory management software, business automation, accounting platform',
            ],
            'landing.about' => [
                'title' => 'About SmartProbook Accounting Platform',
                'description' => 'Learn how SmartProbook powers accounting, ERP operations, reporting, and deployment workflows for modern businesses and institutions.',
                'keywords' => 'about SmartProbook, accounting software company, ERP platform, business finance platform',
            ],
            'landing.contact' => [
                'title' => 'Contact SmartProbook',
                'description' => 'Contact SmartProbook for accounting software support, deployment partnerships, product demos, and enterprise onboarding.',
                'keywords' => 'contact SmartProbook, accounting software support, ERP support, software demo, deployment partnership',
            ],
            'landing.team' => [
                'title' => 'SmartProbook Team',
                'description' => 'Meet the SmartProbook team behind our AI-powered accounting software, ERP tools, and deployment infrastructure services.',
                'keywords' => 'SmartProbook team, accounting software leadership, ERP platform team',
            ],
            'landing.policy' => [
                'title' => 'SmartProbook Company Policy',
                'description' => 'Read SmartProbook company policies, compliance standards, user obligations, and platform governance information.',
                'keywords' => 'SmartProbook policy, company policy, software terms, platform compliance',
            ],
            'landing.projects.lahome' => [
                'title' => 'Lahome Properties Project',
                'description' => 'See how SmartProbook supports Lahome Properties with real estate operations, listings workflows, and digital business infrastructure.',
                'keywords' => 'Lahome Properties, real estate platform, property management software, SmartProbook projects',
            ],
            'landing.projects.master-jamb' => [
                'title' => 'Master JAMB CBT Platform Project',
                'description' => 'Explore the Master JAMB CBT project delivered through SmartProbook for exam readiness, practice testing, grading, and student performance analysis.',
                'keywords' => 'Master JAMB, CBT platform, exam software, e-learning assessment platform, SmartProbook projects',
            ],
            'landing.projects.payplus' => [
                'title' => 'PayPlus Payment Gateway Project',
                'description' => 'Discover the PayPlus payment gateway project and how SmartProbook supports secure transaction workflows and financial operations.',
                'keywords' => 'PayPlus, payment gateway, payment processing software, fintech platform, SmartProbook projects',
            ],
            'membership-plans' => [
                'title' => 'Membership Plans and Pricing',
                'description' => 'Compare SmartProbook membership plans, pricing tiers, accounting features, ERP modules, and business upgrade options.',
                'keywords' => 'SmartProbook pricing, membership plans, accounting software pricing, ERP pricing',
            ],
            'pricing' => [
                'title' => 'Pricing for SmartProbook',
                'description' => 'Explore SmartProbook pricing for accounting, ERP, inventory, invoicing, reporting, and business management workflows.',
                'keywords' => 'pricing, SmartProbook plans, accounting software pricing, ERP subscriptions',
            ],
        ];
        $publicSeo = $publicSeoMap[$routeName] ?? [];
        $seoNoIndex = false;
        $seoType = 'website';
        $seoTitle = $seoTitle ?? ($publicSeo['title'] ?? 'SmartProbook');
        $seoDescription = trim($__env->yieldContent('meta_description')) !== ''
            ? $__env->yieldContent('meta_description')
            : ($publicSeo['description'] ?? 'SmartProbook provides AI-powered accounting, enterprise reporting, global deployment workflows, and institutional-grade financial operations.');
        $seoKeywords = trim($__env->yieldContent('meta_keywords')) !== ''
            ? $__env->yieldContent('meta_keywords')
            : ($publicSeo['keywords'] ?? 'SmartProbook, accounting software, ERP software, invoicing, inventory, reporting');
    @endphp
    @include('layout.partials.seo-meta')
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logos.png') }}">
    <link rel="shortcut icon" href="{{ asset('assets/img/logos.png') }}">
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ===== SmartProbook 2026: ENTERPRISE CORE STYLING ===== */
        :root {
            --primary: #0062ff;      
            --primary-dark: #0046b8;
            --secondary: #ff9d00;    
            --accent-teal: #00d4ff;
            --accent-red: #e63946;
            --accent-gold: #f4a460;
            --dark: #020617;         
            --slate: #475569;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --gold: #f59e0b;
            --grad-main: linear-gradient(135deg, #020617 0%, #0046b8 100%);
            --grad-blue: linear-gradient(135deg, #0062ff 0%, #00d4ff 100%);
            --grad-accent: linear-gradient(135deg, #e63946 0%, #f4a460 100%);
            --footer-bg: #0b0f19;
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            outline: none; 
        }

        html { 
            scroll-behavior: smooth; 
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #ffffff; 
            color: var(--dark); 
            line-height: 1.8; 
            overflow-x: hidden;
            padding-top: 68px;
        }

        body,
        .nav-links {
            -webkit-overflow-scrolling: touch;
        }

        /* ===== NAVIGATION ===== */
        nav {
            position: fixed; 
            width: 100%; 
            top: 0; 
            z-index: 9999;
            background: rgba(255, 255, 255, 0.98); 
            backdrop-filter: blur(20px);
            border-bottom: 1px solid #e2e8f0; 
            height: 68px; 
            display: flex; 
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 98, 255, 0.08);
        }

        .nav-container { 
            max-width: 1500px; 
            margin: 0 auto; 
            width: 100%; 
            padding: 0 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        .logo-container { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            z-index: 10001; 
            text-decoration: none; 
        }

        .logo-text {
            font-size: 1.2rem;
            font-weight: 800;
            color: #0b2a63;
            letter-spacing: -0.3px;
            line-height: 1;
            white-space: nowrap;
        }

        .logo-text span {
            color: #dc2626;
        }

        .brand-img { 
            height: 56px; 
            width: auto; 
        }

        .nav-links { 
            display: flex; 
            list-style: none; 
            gap: 35px; 
            align-items: center; 
            flex-wrap: nowrap;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            flex: 0 0 auto;
        }

        .nav-links a { 
            text-decoration: none; 
            color: var(--dark); 
            font-weight: 700; 
            font-size: 0.85rem; 
            text-transform: uppercase; 
            transition: 0.3s ease; 
            letter-spacing: 0.5px; 
        }

        .nav-links a:hover, 
        .nav-links a.active { 
            color: var(--primary); 
        }

        .nav-demo-link {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            padding: 8px 12px !important;
            border-radius: 999px;
            background: rgba(0, 98, 255, 0.08);
            color: var(--primary) !important;
            border: 1px solid rgba(0, 98, 255, 0.16);
            white-space: nowrap;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .nav-demo-link:hover,
        .nav-demo-link.active {
            background: rgba(0, 98, 255, 0.14);
            color: var(--primary) !important;
        }

        .country-switcher {
            display: inline-flex;
            align-items: center;
        }

        .country-select {
            border: 1px solid #dbe3ef;
            background: #f8fbff;
            color: var(--dark);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            min-width: 120px;
        }

        .btn-portal {
            background: var(--grad-blue);
            color: white !important;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 98, 255, 0.2);
            font-size: 0.8rem;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }

        .btn-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 98, 255, 0.3);
            color: white;
        }

        /* ===== HAMBURGER MENU ===== */
        .hamburger { 
            display: none; 
            cursor: pointer; 
            flex-direction: column; 
            gap: 6px; 
            z-index: 10001; 
            border: none;
            background: none;
            padding: 0;
        }

        .hamburger span { 
            width: 28px; 
            height: 3px; 
            background: var(--dark); 
            border-radius: 4px; 
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            transform-origin: center;
        }

        .hamburger.active span:nth-child(1) {
            transform: translateY(9px) rotate(45deg);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
            transform: translateX(-10px);
        }

        .hamburger.active span:nth-child(3) {
            transform: translateY(-9px) rotate(-45deg);
        }

        /* ===== HERO SECTION ===== */
        .hero { 
            position: relative; 
            height: 90vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden; 
            background: #000; 
        }

        .hero-video { 
            position: absolute; 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            z-index: 0; 
            filter: brightness(0.3); 
        }

        .hero-content { 
            position: relative; 
            z-index: 1; 
            text-align: center; 
            color: var(--white); 
            padding: 0 20px; 
            max-width: 1100px; 
        }

        .hero-content h1 { 
            font-size: clamp(1.8rem, 4vw, 2.8rem); 
            font-weight: 900; 
            line-height: 1.1; 
            margin-bottom: 30px; 
            color: #fff !important; 
        }

        .hero-content h1 span { 
            background: var(--grad-accent); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
        }

        .hero-content p { 
            font-size: 1.25rem; 
            margin-bottom: 45px; 
            color: #cbd5e1; 
            max-width: 850px; 
            margin-inline: auto; 
            font-weight: 300; 
        }

        /* ===== BUTTONS ===== */
        .btn-main { 
            padding: 18px 40px; 
            border-radius: 12px; 
            font-weight: 800; 
            text-decoration: none; 
            display: inline-block; 
            transition: 0.3s ease; 
            border: none; 
            cursor: pointer; 
            font-size: 0.9rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }

        .btn-blue { 
            background: var(--grad-blue); 
            color: white; 
            box-shadow: 0 15px 30px rgba(0, 98, 255, 0.2); 
        }

        .btn-blue:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 20px 40px rgba(0, 98, 255, 0.4); 
            color: white;
        }

        .btn-accent { 
            background: var(--grad-accent); 
            color: white; 
            box-shadow: 0 15px 30px rgba(230, 57, 70, 0.2); 
        }

        .btn-accent:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 20px 40px rgba(230, 57, 70, 0.3); 
            color: white;
        }

        /* ===== CONTENT SECTIONS ===== */
        .section-padding { 
            padding: 140px 20px; 
        }

        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
        }

        .feature-row { 
            display: flex; 
            align-items: center; 
            gap: 100px; 
            margin-bottom: 140px; 
        }

        .feature-row.reverse { 
            flex-direction: row-reverse; 
        }

        .feature-text { 
            flex: 1.2; 
        }

        .feature-text h2 { 
            font-size: 2.8rem; 
            font-weight: 900; 
            margin-bottom: 30px; 
            line-height: 1.1; 
        }

        .feature-text h2 span { 
            background: var(--grad-accent); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
        }

        .feature-text p { 
            font-size: 1.15rem; 
            color: var(--slate); 
            margin-bottom: 25px; 
            text-align: justify; 
        }

        .feature-visual { 
            flex: 1; 
            border-radius: 40px; 
            overflow: hidden; 
            height: 500px; 
            box-shadow: 0 50px 100px rgba(0,0,0,0.12); 
            border: 3px solid rgba(230, 57, 70, 0.1); 
        }

        .feature-visual img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        /* ===== CLIENT MARQUEE ===== */
        .client-marquee { 
            background: var(--dark); 
            padding: 100px 0; 
            overflow: hidden; 
            position: relative; 
        }

        .client-marquee::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            height: 1px; 
            background: linear-gradient(90deg, transparent, var(--accent-red), transparent); 
        }

        .marquee-track { 
            display: flex; 
            width: calc(500px * 16); 
            animation: marqueeMove 45s linear infinite; 
            gap: 40px; 
        }

        @keyframes marqueeMove { 
            0% { transform: translateX(0); } 
            100% { transform: translateX(calc(-500px * 8)); } 
        }

        .client-card { 
            width: 500px; 
            background: rgba(255,255,255,0.03); 
            padding: 50px; 
            border-radius: 30px; 
            border: 1px solid rgba(255,255,255,0.07); 
            color: white; 
            flex-shrink: 0; 
            transition: 0.3s ease; 
        }

        .client-card:hover { 
            background: rgba(230, 57, 70, 0.05); 
            border-color: rgba(230, 57, 70, 0.2); 
        }

        .client-card img {
            height: 30px; 
            filter: brightness(0) invert(1); 
            margin-bottom: 25px; 
            opacity: 0.7; 
        }

        .client-card p { 
            font-size: 1rem; 
            font-style: italic; 
            opacity: 0.8; 
            line-height: 1.6; 
        }

        /* ===== PRICING GRID ===== */
        .pricing-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 30px; 
            margin-top: 80px; 
        }

        .price-card { 
            background: white; 
            padding: 60px 40px; 
            border-radius: 25px; 
            border: 2px solid #edf2f7; 
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); 
            display: flex; 
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .price-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--grad-blue);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }

        .price-card:hover {
            transform: translateY(-20px);
            box-shadow: 0 50px 100px rgba(0, 98, 255, 0.12);
            border-color: rgba(0, 98, 255, 0.3);
        }

        .price-card:hover::before {
            transform: scaleX(1);
        }

        .price-card.featured { 
            border: 2px solid var(--primary); 
            background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
            transform: scale(1.08);
            box-shadow: 0 60px 120px rgba(0, 98, 255, 0.2);
            position: relative;
        }

        .price-card.featured::before {
            height: 6px;
            background: var(--grad-accent);
            transform: scaleX(1);
        }

        .price-badge {
            display: inline-block;
            background: var(--grad-blue);
            color: white;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
            width: fit-content;
        }

        .price-card.featured .price-badge {
            background: var(--grad-accent);
        }

        .price-amount { 
            font-size: 3.2rem; 
            font-weight: 900; 
            margin: 20px 0 30px; 
            color: var(--dark);
            line-height: 1;
        }

        .price-amount span {
            font-size: 0.95rem;
            opacity: 0.5;
            font-weight: 400;
        }

        .price-description {
            font-size: 0.95rem;
            color: var(--slate);
            margin-bottom: 35px;
            line-height: 1.7;
        }

        .price-card h4 { 
            color: var(--primary); 
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .price-features {
            list-style: none;
            margin: 0 0 40px 0;
            padding: 0;
            flex-grow: 1;
        }

        .price-features li {
            padding: 14px 0;
            color: var(--slate);
            font-size: 0.95rem;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .price-features li:last-child {
            border-bottom: none;
        }

        .price-features i {
            color: var(--primary);
            font-size: 0.8rem;
            min-width: 16px;
        }

        .price-card.featured .price-features i {
            color: var(--accent-red);
        }

        /* ===== ACCENT DIVIDERS ===== */
        .accent-divider { 
            height: 2px; 
            background: linear-gradient(90deg, transparent, var(--accent-red), transparent); 
            margin: 60px 0; 
        }

        /* ===== MASTER FOOTER ===== */
        .master-footer { 
            background: var(--footer-bg); 
            color: white; 
            padding: 100px 0 50px; 
            position: relative; 
        }

        .master-footer::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            height: 2px; 
            background: linear-gradient(90deg, var(--accent-teal), var(--accent-red), var(--accent-gold), var(--accent-teal)); 
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .footer-grid { 
            display: grid; 
            grid-template-columns: 2fr 1.2fr 1.2fr 1.2fr 1.2fr; 
            gap: 60px; 
            margin-bottom: 60px;
            text-align: left;
        }

        .footer-col h5 { 
            font-size: 0.85rem; 
            letter-spacing: 2px; 
            color: var(--accent-teal); 
            margin-bottom: 30px; 
            text-transform: uppercase; 
            font-weight: 800; 
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-col h5::before { 
            content: '◆'; 
            color: var(--accent-gold); 
            font-size: 0.7rem;
        }

        .footer-col p {
            color: #cbd5e1;
            font-size: 0.95rem;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .footer-links { 
            list-style: none; 
        }

        .footer-links li { 
            margin-bottom: 15px; 
        }

        .footer-link { 
            color: #cbd5e1; 
            text-decoration: none; 
            transition: 0.3s ease; 
            display: inline-block;
            font-size: 0.95rem;
        }

        .footer-link:hover { 
            color: var(--accent-gold); 
            transform: translateX(5px); 
        }

        .footer-logo { 
            font-size: 1.8rem; 
            font-weight: 800; 
            color: white; 
            margin-bottom: 20px; 
            text-decoration: none;
        }

        .footer-logo span { 
            color: var(--accent-red); 
        }

        .footer-divider {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 50px 0 40px;
        }

        .footer-bottom {
            text-align: center;
            padding: 30px 0 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom p {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 1100px) {
            .pricing-grid { 
                grid-template-columns: repeat(2, 1fr); 
            }

            .footer-grid { 
                grid-template-columns: repeat(2, 1fr); 
            }
            
            .feature-row, 
            .feature-row.reverse { 
                flex-direction: column; 
                text-align: center; 
                gap: 50px;
            }
        }

        /* ===== TABLET LANDSCAPE / SMALL LAPTOP NAV TUNING ===== */
        @media (min-width: 992px) and (max-width: 1320px) {
            .nav-container {
                padding: 0 20px;
            }

            .logo-container {
                gap: 8px;
            }

            .brand-img {
                height: 44px;
            }

            .logo-text {
                font-size: 0.92rem;
            }

            .nav-links {
                gap: 14px;
                flex-wrap: nowrap;
            }

            .nav-links a {
                font-size: 0.74rem;
                letter-spacing: 0.18px;
                white-space: nowrap;
            }

            .nav-demo-link {
                padding: 7px 10px !important;
            }

            .btn-portal {
                padding: 9px 14px;
                font-size: 0.74rem;
                white-space: nowrap;
                border-radius: 9px;
            }
        }

        /* ===== TABLET LAYOUT ===== */
        @media (max-width: 991px) {
            html, body {
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }

            body {
                padding-top: 64px;
                touch-action: pan-y;
                overscroll-behavior-y: auto;
            }

            nav {
                height: 64px;
            }

            .nav-container {
                padding: 0 18px;
            }

            .hamburger { 
                display: flex; 
            }

            .nav-links { 
                position: fixed; 
                top: 64px; 
                left: -100%; 
                width: 100%; 
                max-width: 100%;
                height: calc(100vh - 64px); 
                background: white; 
                flex-direction: column; 
                padding: 22px 16px 28px; 
                gap: 0; 
                transition: left 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
                overflow-y: auto;
                overflow-x: hidden;
                overscroll-behavior: contain;
                -webkit-overflow-scrolling: touch;
                z-index: 9998;
                box-shadow: inset 0 2px 15px rgba(0, 0, 0, 0.05);
            }

            .nav-links.active { 
                left: 0; 
            }

            .nav-links li {
                width: 100%;
                border-bottom: 1px solid #f0f4ff;
                max-width: 100%;
            }

            .nav-links li:last-child {
                border-bottom: none;
            }

            .nav-links a {
                padding: 16px 14px;
                display: block;
                border-radius: 12px;
                transition: all 0.3s ease;
                color: var(--dark);
                font-size: 0.98rem;
                font-weight: 800;
                line-height: 1.35;
                letter-spacing: 0.02em;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .nav-demo-link {
                display: block !important;
                width: 100%;
                text-align: center;
                margin-top: 12px;
                padding: 15px 16px !important;
                border-radius: 12px;
            }

            .nav-links a:hover {
                background: rgba(0, 98, 255, 0.08);
                color: var(--primary);
                padding-left: 18px;
            }

            .nav-links a.active {
                background: rgba(0, 98, 255, 0.1);
                color: var(--primary);
                font-weight: 800;
            }

            .btn-portal {
                width: 100%;
                text-align: center;
                margin-top: 12px;
                padding: 15px 18px;
                border-radius: 12px;
                font-size: 0.96rem;
                font-weight: 800;
                line-height: 1.3;
                white-space: normal;
            }

            .country-switcher {
                width: 100%;
                margin-top: 10px;
            }

            .country-select {
                width: 100%;
                min-width: 0;
            }

            .pricing-grid, 
            .footer-grid { 
                grid-template-columns: 1fr; 
            }

            .hero-content h1 { 
                font-size: 1.8rem; 
            }

            .hero-content p {
                font-size: 1rem;
            }

            .feature-visual {
                height: 350px;
            }

            .feature-text h2 {
                font-size: 2rem;
            }

            .section-padding {
                padding: 80px 20px;
            }

            .footer-grid {
                gap: 30px;
            }
        }

        /* ===== MOBILE LAYOUT ===== */
        @media (max-width: 480px) {
            body {
                padding-top: 60px;
            }

            nav {
                height: 60px;
                padding: 0 15px;
            }

            .nav-container {
                padding: 0 12px;
            }

            .logo-text {
                font-size: 0.72rem;
                letter-spacing: -0.18px;
            }

            .brand-img { height: 36px; }

            .logo-container {
                gap: 6px;
            }

            .hamburger span {
                width: 26px;
                height: 2.5px;
            }

            .nav-links { 
                top: 60px;
                height: calc(100vh - 60px);
                padding: 18px 12px 24px;
            }

            .nav-links a {
                padding: 15px 12px;
                font-size: 0.92rem;
            }

            .nav-demo-link {
                padding: 14px 12px !important;
                font-size: 0.92rem;
            }

            .hero-content h1 {
                font-size: 1.4rem;
            }

            .hero-content p {
                font-size: 0.95rem;
                margin-bottom: 30px;
            }

            .feature-text h2 {
                font-size: 1.5rem;
            }

            .feature-text p {
                text-align: left;
            }

            .btn-main {
                padding: 14px 28px;
                font-size: 0.8rem;
            }

            .section-padding {
                padding: 60px 15px;
            }

            .feature-row {
                gap: 30px;
                margin-bottom: 80px;
            }

            .feature-visual {
                height: 280px;
            }

            .footer-col h5 {
                font-size: 0.75rem;
                margin-bottom: 20px;
            }

            .footer-col p {
                font-size: 0.85rem;
            }

            .footer-link {
                font-size: 0.85rem;
            }
        }

        /* ===== SMALL PHONE ===== */
        @media (max-width: 360px) {
            .nav-container {
                padding: 0 10px;
            }

            .logo-text {
                font-size: 0.6rem;
                letter-spacing: -0.12px;
            }

            .brand-img { height: 30px; }

            .logo-container {
                gap: 4px;
            }

            .nav-links a,
            .nav-demo-link,
            .btn-portal {
                font-size: 0.88rem;
            }

            .hero-content h1 {
                font-size: 1.2rem;
            }

            .feature-text h2 {
                font-size: 1.3rem;
            }

            .btn-main {
                padding: 12px 24px;
                font-size: 0.75rem;
            }

            .section-padding {
                padding: 50px 12px;
            }
        }

        /* Keep the Livewire/NProgress busy spinner visible on phones */
        #nprogress .spinner {
            top: calc(env(safe-area-inset-top, 0px) + 14px) !important;
            right: 14px !important;
            z-index: 10060 !important;
        }

        #nprogress .spinner-icon {
            width: 18px;
            height: 18px;
            border-width: 2px;
        }

        @media (max-width: 991.98px) {
            #nprogress .spinner {
                top: calc(env(safe-area-inset-top, 0px) + 76px) !important;
                right: 12px !important;
            }

            #nprogress .spinner-icon {
                width: 22px;
                height: 22px;
                border-width: 2.5px;
            }
        }
    </style>

</head>
<body>

<!-- ===== NAVIGATION ===== -->
<nav>
    <div class="nav-container">
        <a href="{{ url('/') }}" class="logo-container">
            <img src="{{ asset('assets/img/logos.png') }}" class="brand-img" alt="SmartProbook"> 
            <span class="logo-text">SmartPro<span>book</span></span>
        </a>
        
        <button class="hamburger" id="navTrigger" type="button" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <ul class="nav-links" id="mainMenu">
            <li><a href="{{ url('/') }}" class="{{ Route::is('landing.index') ? 'active' : '' }}">Home</a></li>
            <li><a href="{{ route('landing.about') }}" class="{{ Route::is('landing.about') ? 'active' : '' }}">About</a></li>
            <li><a href="{{ route('landing.team') }}" class="{{ Route::is('landing.team') ? 'active' : '' }}">Projects</a></li>
            <li><a href="{{ route('landing.contact') }}" class="{{ Route::is('landing.contact') ? 'active' : '' }}">Contact</a></li>
            <li><a href="{{ url('/#licensing') }}">Licensing</a></li>
            <li><a href="{{ route('landing.policy') }}" class="{{ Route::is('landing.policy') ? 'active' : '' }}">Policy</a></li>
            <li><a href="{{ route('landing.demo') }}" class="nav-demo-link {{ Route::is('landing.demo') ? 'active' : '' }}">Try Demo</a></li>
          
            <li><a href="{{ route('saas-login') }}" class="btn-portal">Client Portal</a></li>
        </ul>
    </div>
</nav>

<!-- ===== MAIN CONTENT ===== -->
<main>
    @yield('content')
</main>

<!-- ===== FOOTER ===== -->
<footer class="master-footer">
    <div class="footer-content">
        <div class="footer-grid">
            <!-- Company Info -->
            <div class="footer-col">
                <a href="{{ url('/') }}" class="footer-logo">SMARTPRO<span>BOOK</span></a>
                <p>Engineered for excellence. Registered under the laws of the Federal Republic of Nigeria. Global headquarters in Enugu Tech Hub.</p>
            </div>

            <!-- Ecosystem -->
            <div class="footer-col">
                <h5>Ecosystem</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('landing.about') }}" class="footer-link">Intelligence Solutions</a></li>
                    <li><a href="{{ route('landing.team') }}" class="footer-link">Other Projects</a></li>
                    <li><a href="{{ url('/#licensing') }}" class="footer-link">Licensing</a></li>
                </ul>
            </div>

            <!-- Governance -->
            <div class="footer-col">
                <h5>Governance</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('landing.policy') }}" class="footer-link">Company Policy</a></li>
                    <li><a href="{{ route('landing.policy') }}" class="footer-link">Privacy Policy</a></li>
                    <li><a href="{{ route('landing.policy') }}" class="footer-link">Terms & Conditions</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="footer-col">
                <h5>Services</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('membership-plans') }}" class="footer-link">Enterprise Solutions</a></li>
                    <li><a href="{{ route('landing.about') }}" class="footer-link">Risk Management</a></li>
                    <li><a href="{{ route('landing.contact') }}" class="footer-link">Consulting</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="footer-col">
                <h5>Contact</h5>
                <p>Enugu Tech Hub<br>Independence Layout<br>Enugu, Nigeria</p>
                <ul class="footer-links">
                    <li><a href="tel:+23464646306" class="footer-link">+234 646 463 06</a></li>
                    <li><a href="mailto:donvictorlive@gmail.com" class="footer-link">donvictorlive@gmail.com</a></li>
                </ul>
            </div>
        </div>

        <!-- Footer Divider -->
        <div class="footer-divider"></div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; 2026 SmartProbook Global Infrastructure Inc. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // Mobile menu toggle
    const navTrigger = document.getElementById('navTrigger');
    const mainMenu = document.getElementById('mainMenu');

    if (navTrigger && mainMenu) {
        // Toggle menu on hamburger click
        navTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            navTrigger.classList.toggle('active');
            mainMenu.classList.toggle('active');
        });

        // Close menu when a link is clicked
        mainMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                navTrigger.classList.remove('active');
                mainMenu.classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-container') && !e.target.closest('.nav-links')) {
                navTrigger.classList.remove('active');
                mainMenu.classList.remove('active');
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                navTrigger.classList.remove('active');
                mainMenu.classList.remove('active');
            }
        });

        // Active route highlighting
        const currentPath = window.location.pathname;
        mainMenu.querySelectorAll('a').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    }

    // Geo country/currency selector sync (shared across landing pages)
    const globalCountrySelector = document.getElementById('countrySelectorGlobal');
    const geoCountryMap = {
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

    const readLocaleRegion = () => {
        try {
            const locale = Intl.DateTimeFormat().resolvedOptions().locale || navigator.language || 'en-NG';
            const region = locale.split('-').pop().toUpperCase();
            return region.length === 2 ? region : 'NG';
        } catch (e) {
            return 'NG';
        }
    };

    const normalizeCountryCode = (rawCode) => {
        const code = String(rawCode || '').toUpperCase();
        const euRegions = ['FR', 'DE', 'ES', 'IT', 'PT', 'NL', 'BE', 'AT', 'IE', 'FI', 'SE', 'DK', 'PL', 'CZ', 'GR', 'RO', 'HU'];
        if (code === 'CN') return 'CN';
        if (euRegions.includes(code)) return 'EU';
        if (geoCountryMap[code]) return code;
        return 'NG';
    };

    const applyGlobalCountry = (countryCode) => {
        const normalized = normalizeCountryCode(countryCode);
        localStorage.setItem('smat_country', normalized);
        const oneYear = 60 * 60 * 24 * 365;
        document.cookie = `smat_country=${normalized}; path=/; max-age=${oneYear}; SameSite=Lax`;
        if (globalCountrySelector) globalCountrySelector.value = normalized;
        document.dispatchEvent(new CustomEvent('smat:geo-change', {
            detail: { country: normalized, ...geoCountryMap[normalized] }
        }));
    };

    const initGlobalCountry = async () => {
        if (!globalCountrySelector) return;

        const cookieMatch = document.cookie.match(/(?:^|;\s*)smat_country=([^;]+)/);
        const cookieCountry = cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
        const saved = localStorage.getItem('smat_country');
        const serverDefault = @json($geoCountry ?? 'NG');
        if (saved || cookieCountry) {
            applyGlobalCountry(saved || cookieCountry);
            return;
        }

        applyGlobalCountry(serverDefault || 'NG');
    };

    if (globalCountrySelector) {
        globalCountrySelector.addEventListener('change', function() {
            applyGlobalCountry(this.value);
        });
    }

    initGlobalCountry();
</script>

</body>
</html>
