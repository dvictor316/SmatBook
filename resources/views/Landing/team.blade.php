@extends('layout.landing_nav')

@section('content')

<!-- ===== TEAM PAGE STYLES ===== -->
<style>
    /* ===== RESET & BASE ===== */
    .sidebar, #sidebar, .sidebar-menu, .main-sidebar, aside {
        display: none !important;
    }
    .main-wrapper, .page-wrapper, #main-content {
        margin-left: 0 !important;
        padding-left: 0 !important;
        width: 100% !important;
    }
    body {
        padding-left: 0 !important;
        overflow-x: hidden;
    }

    /* ===== TEAM HEADER ===== */
    .team-header {
        margin-top: 85px;
        padding: 100px 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #f0f4ff 100%);
        text-align: center;
    }

    .team-label {
        color: var(--accent-red);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 3px;
        margin-bottom: 15px;
        display: block;
    }

    .team-title {
        font-size: 3.2rem;
        font-weight: 900;
        margin-bottom: 25px;
        line-height: 1.1;
        letter-spacing: -1px;
    }

    .team-title span {
        background: var(--grad-accent);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .team-subtitle {
        font-size: 1.15rem;
        color: var(--slate);
        max-width: 800px;
        margin: 0 auto;
        line-height: 1.8;
    }

    /* ===== CAROUSEL SECTION ===== */
    .carousel-section {
        padding: 100px 20px;
        background: linear-gradient(135deg, var(--dark) 0%, #1e3a5f 100%);
        color: white;
    }

    .carousel-header {
        text-align: center;
        margin-bottom: 60px;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    .carousel-header h2 {
        font-size: 2.8rem;
        font-weight: 900;
        margin-bottom: 20px;
        line-height: 1.1;
    }

    .carousel-header h2 span {
        color: var(--accent-gold);
    }

    .carousel-header p {
        font-size: 1.1rem;
        opacity: 0.8;
        max-width: 700px;
        margin: 0 auto;
    }

    /* ===== SWIPER CAROUSEL ===== */
    .swiper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .swiper-slide {
        height: auto;
        display: flex;
        align-items: stretch;
    }

    .team-card-carousel {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 25px;
        overflow: hidden;
        backdrop-filter: blur(10px);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .team-card-carousel:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(0, 98, 255, 0.3);
        transform: translateY(-10px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }

    .carousel-image {
        width: 100%;
        height: 300px;
        overflow: hidden;
        background: var(--light-bg);
    }

    .carousel-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: grayscale(100%);
        transition: all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .team-card-carousel:hover .carousel-image img {
        filter: grayscale(0%);
        transform: scale(1.08);
    }

    .carousel-info {
        padding: 35px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .carousel-badge {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--accent-gold);
        background: rgba(244, 164, 96, 0.1);
        padding: 6px 14px;
        border-radius: 50px;
        margin-bottom: 12px;
        width: fit-content;
        border: 1px solid rgba(244, 164, 96, 0.2);
    }

    .carousel-name {
        font-size: 1.3rem;
        font-weight: 900;
        color: white;
        margin-bottom: 6px;
        letter-spacing: -0.5px;
    }

    .carousel-description {
        font-size: 0.9rem;
        color: #cbd5e1;
        line-height: 1.6;
        margin-bottom: 20px;
        flex-grow: 1;
    }

    /* ===== CAROUSEL CONTROLS ===== */
    .swiper-button-next,
    .swiper-button-prev {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .swiper-button-next:hover,
    .swiper-button-prev:hover {
        background: var(--grad-accent);
        border-color: var(--accent-red);
        transform: scale(1.1);
    }

    .swiper-button-next::after,
    .swiper-button-prev::after {
        font-size: 1.5rem;
    }

    .swiper-pagination-bullet {
        background: rgba(255, 255, 255, 0.3);
        opacity: 1;
        transition: 0.3s ease;
    }

    .swiper-pagination-bullet-active {
        background: var(--accent-gold);
    }

    /* ===== TEAM SECTION ===== */
    .team-section {
        padding: 100px 20px;
        background: white;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* ===== TEAM GRID ===== */
    .team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
        margin-top: 60px;
    }

    /* ===== TEAM CARD ===== */
    .team-card {
        background: white;
        border-radius: 30px;
        overflow: hidden;
        border: 1px solid #edf2f7;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .team-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 50px 100px rgba(0, 0, 0, 0.15);
        border-color: rgba(0, 98, 255, 0.2);
    }

    /* ===== IMAGE CONTAINER ===== */
    .team-image-container {
        position: relative;
        overflow: hidden;
        height: 400px;
        background: var(--light-bg);
    }

    .team-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: grayscale(100%);
        transition: all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .team-card:hover img {
        filter: grayscale(0%);
        transform: scale(1.08);
    }

    /* ===== ROLE BADGE ===== */
    .role-badge {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--accent-gold);
        background: rgba(244, 164, 96, 0.1);
        padding: 8px 18px;
        border-radius: 50px;
        margin-bottom: 15px;
        border: 1px solid rgba(244, 164, 96, 0.2);
    }

    /* ===== TEAM INFO ===== */
    .team-info {
        padding: 40px 35px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .team-name {
        font-size: 1.4rem;
        font-weight: 900;
        color: var(--dark);
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .team-description {
        font-size: 0.95rem;
        color: var(--slate);
        line-height: 1.7;
        margin-bottom: 25px;
        flex-grow: 1;
    }

    /* ===== SOCIAL LINKS ===== */
    .social-links {
        display: flex;
        gap: 18px;
        padding-top: 20px;
        border-top: 1px solid #edf2f7;
    }

    .social-link {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--light-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--slate);
        text-decoration: none;
        font-size: 1.1rem;
        transition: 0.3s ease;
    }

    .social-link:hover {
        background: var(--grad-blue);
        color: white;
        transform: translateY(-3px);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1200px) {
        .team-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 35px;
        }

        .swiper {
            padding: 0 15px;
        }
    }

    @media (max-width: 768px) {
        .team-header {
            margin-top: 70px;
            padding: 80px 20px;
        }

        .team-title {
            font-size: 2.2rem;
        }

        .team-subtitle {
            font-size: 1rem;
        }

        .carousel-section {
            padding: 80px 20px;
        }

        .carousel-header h2 {
            font-size: 2rem;
        }

        .team-section {
            padding: 80px 20px;
        }

        .team-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .team-image-container {
            height: 350px;
        }

        .team-info {
            padding: 35px 30px;
        }

        .carousel-image {
            height: 250px;
        }

        .carousel-info {
            padding: 30px;
        }
    }

    @media (max-width: 480px) {
        .team-header {
            padding: 60px 15px;
        }

        .team-title {
            font-size: 1.6rem;
        }

        .carousel-section {
            padding: 60px 15px;
        }

        .carousel-header h2 {
            font-size: 1.5rem;
        }

        .team-image-container {
            height: 300px;
        }

        .team-info {
            padding: 30px 25px;
        }

        .team-name {
            font-size: 1.2rem;
        }

        .team-description {
            font-size: 0.9rem;
        }

        .carousel-image {
            height: 220px;
        }

        .carousel-name {
            font-size: 1.1rem;
        }

        .carousel-description {
            font-size: 0.85rem;
        }

        .carousel-info {
            padding: 25px;
        }

        .swiper-button-next,
        .swiper-button-prev {
            width: 40px;
            height: 40px;
        }
    }
</style>

<!-- ===== TEAM HEADER ===== -->
<section class="team-header">
    <div class="container">
        <h6 class="team-label">Platform Showcase</h6>
        <h1 class="team-title">Technical <span>Workspaces</span>.</h1>
        <p class="team-subtitle">A visual walk-through of SmartProbook modules used by deployment teams and enterprise finance operators.</p>
    </div>
</section>

<!-- ===== CAROUSEL SECTION ===== -->
<section class="carousel-section">
    <div class="carousel-header">
        <h2>Explore the <span>Modules</span></h2>
        <p>Core operational interfaces powering accounting, reporting, billing, and governance workflows</p>
    </div>

    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            @foreach([
                [
                    'img' => asset('assets/img/demo-one.png'),
                    'name' => 'Operations Dashboard',
                    'role' => 'Control Center',
                    'description' => 'Central workspace for daily KPIs, account position tracking, and rapid operational decisions.'
                ],
                [
                    'img' => asset('assets/img/demo-two.png'),
                    'name' => 'Analytics Reports',
                    'role' => 'Reporting Suite',
                    'description' => 'Decision-grade visuals for profitability, tax exposure, cashflow, and trend forecasting.'
                ],
                [
                    'img' => asset('assets/img/demo-three.png'),
                    'name' => 'Deployment Overview',
                    'role' => 'Infrastructure Ops',
                    'description' => 'Unified monitoring for provisioned clients, lifecycle milestones, and infrastructure state.'
                ],
                [
                    'img' => asset('assets/img/demo-four.png'),
                    'name' => 'Audit & Compliance',
                    'role' => 'Governance Panel',
                    'description' => 'Structured evidence trail with controls and policy-driven review checkpoints.'
                ],
                [
                    'img' => asset('assets/img/invoice-one.jpg'),
                    'name' => 'Invoice Workspace',
                    'role' => 'Billing Engine',
                    'description' => 'End-to-end invoicing, approvals, and payment-state tracking with traceable references.'
                ],
                [
                    'img' => asset('assets/img/invoice-two.jpg'),
                    'name' => 'Receipts & Collections',
                    'role' => 'Cash Management',
                    'description' => 'Collection workflows for receipts, settlements, and account reconciliation.'
                ],
            ] as $member)
                <div class="swiper-slide">
                    <div class="team-card-carousel">
                        <div class="carousel-image">
                            <img src="{{ $member['img'] }}" alt="{{ $member['name'] }}" loading="lazy">
                        </div>
                        <div class="carousel-info">
                            <span class="carousel-badge">{{ $member['role'] }}</span>
                            <h4 class="carousel-name">{{ $member['name'] }}</h4>
                            <p class="carousel-description">{{ $member['description'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</section>

<!-- ===== FULL TEAM SECTION ===== -->
<section class="team-section">
    <div class="container">
        <div style="text-align: center; margin-bottom: 60px;">
            <h2 style="font-size: 2.8rem; font-weight: 900; margin-bottom: 15px; color: #020617;">Complete Platform <span style="background: linear-gradient(135deg, #e63946 0%, #f4a460 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Gallery</span></h2>
            <p style="color: #475569; font-size: 1.1rem;">Preview the major workspaces across the SmartProbook application stack</p>
        </div>

        <div class="team-grid">
            @foreach([
                [
                    'img' => asset('assets/img/demo-one.png'),
                    'name' => 'Operations Dashboard',
                    'role' => 'Control Center',
                    'description' => 'Unified dashboard for transactions, performance snapshots, and global operational control.',
                    'social' => ['linkedin' => route('landing.about'), 'twitter' => route('landing.contact')]
                ],
                [
                    'img' => asset('assets/img/demo-two.png'),
                    'name' => 'Analytics Reports',
                    'role' => 'Reporting Suite',
                    'description' => 'Performance intelligence workspace for trend analysis, margin control, and executive reporting.',
                    'social' => ['linkedin' => route('landing.about')]
                ],
                [
                    'img' => asset('assets/img/demo-three.png'),
                    'name' => 'Deployment Overview',
                    'role' => 'Infrastructure Ops',
                    'description' => 'Deployment board for provisioning status, role workflows, and regional infrastructure readiness.',
                    'social' => ['github' => route('saas-login'), 'linkedin' => route('landing.contact')]
                ],
                [
                    'img' => asset('assets/img/demo-four.png'),
                    'name' => 'Audit & Compliance',
                    'role' => 'Governance Panel',
                    'description' => 'Audit-centric workflows with traceability, policy alignment, and structured approval checkpoints.',
                    'social' => ['linkedin' => route('landing.policy')]
                ],
                [
                    'img' => asset('assets/img/invoice-one.jpg'),
                    'name' => 'Invoice Workspace',
                    'role' => 'Billing Engine',
                    'description' => 'Invoice generation, approval routing, and customer payment lifecycle management in one interface.',
                    'social' => ['linkedin' => route('membership-plans')]
                ],
                [
                    'img' => asset('assets/img/invoice-two.jpg'),
                    'name' => 'Receipts & Collections',
                    'role' => 'Cash Management',
                    'description' => 'Collection management for receipts, posted payments, reconciliations, and treasury visibility.',
                    'social' => ['linkedin' => route('landing.contact')]
                ],
            ] as $member)
                <article class="team-card">
                    <div class="team-image-container">
                        <img src="{{ $member['img'] }}" alt="{{ $member['name'] }}" loading="lazy">
                    </div>

                    <div class="team-info">
                        <span class="role-badge">{{ $member['role'] }}</span>
                        <h3 class="team-name">{{ $member['name'] }}</h3>
                        <p class="team-description">{{ $member['description'] }}</p>

                        <div class="social-links">
                            @if(isset($member['social']['linkedin']))
                                <a href="{{ $member['social']['linkedin'] }}" class="social-link" title="LinkedIn" aria-label="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            @endif
                            @if(isset($member['social']['twitter']))
                                <a href="{{ $member['social']['twitter'] }}" class="social-link" title="Twitter" aria-label="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            @endif
                            @if(isset($member['social']['github']))
                                <a href="{{ $member['social']['github'] }}" class="social-link" title="GitHub" aria-label="GitHub">
                                    <i class="fab fa-github"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<!-- ===== SWIPER LIBRARY ===== -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    const swiper = new Swiper('.mySwiper', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
            1400: {
                slidesPerView: 4,
                spaceBetween: 30,
            }
        }
    });
</script>

@endsection
