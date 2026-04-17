@extends('layout.landing_nav')

@section('content')


<div id="intelligenceCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070" alt="Corporate Intelligence">
            <div class="carousel-caption">
                <h6 class="text-uppercase mb-3">2026 Executive Briefing</h6>
                <h1>Defining <span class="text-accent">Financial Clarity.</span></h1>
                <p class="lead">Harnessing mathematical precision and unwavering ethical standards for the modern African enterprise.</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072" alt="Future Governance">
            <div class="carousel-caption">
                <h6 class="text-uppercase mb-3">Global Infrastructure</h6>
                <h1>The Future of <span class="text-accent">Governance.</span></h1>
                <p class="lead">Building a transparent ecosystem where every kobo is accounted for with IFRS precision.</p>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#intelligenceCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#intelligenceCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>


<header class="about-header">
    <div class="container text-center">
        <h6 class="section-label">Corporate Profile</h6>
        <h1 class="section-title">SmartProbook <span>Intelligence.</span></h1>
        <p class="section-subtitle">Our mission to redefine African corporate governance through mathematical precision and world-class financial infrastructure.</p>
    </div>
</header>


<section class="about-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=1000" class="about-image" alt="Mission">
            </div>
            <div class="col-lg-6">
                <div class="about-label">01. Our Mission</div>
                <h2 class="about-title">Democratizing Financial <span>Sovereignty.</span></h2>
                <p class="about-text">In an era where data is the new currency, SmartProbook's mission extends beyond simple bookkeeping. We are dedicated to providing Small and Medium Enterprises (SMEs) with the same level of financial sophistication typically reserved for Fortune 500 companies.</p>
                <p class="about-text">By automating the most complex aspects of IFRS and GAAP compliance, we eliminate the "intelligence gap" that hinders African businesses from securing international investment. We empower founders to lead with confidence, knowing their financial bedrock is unshakable and verified by global standards.</p>
                <a href="{{ route('landing.contact') }}" class="btn-main btn-blue">Learn More</a>
            </div>
        </div>
    </div>
</section>


<section class="about-section about-section-alt">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <div class="vision-card">
                    <div class="vision-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Global Vision 2030</h3>
                    <p>A "Financial Passport" that proves transparency to banks and regulators globally.</p>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="about-label">02. Our Vision</div>
                <h2 class="about-title">The Future of <span>African Governance.</span></h2>
                <p class="about-text">Our vision is to become the primary digital infrastructure for corporate governance across the African continent. We foresee a 2030 where SmartProbook is the standard—a "Financial Passport" that proves a company's transparency to banks, regulators, and stakeholders globally.</p>
                <p class="about-text">We are building a future where financial reporting is not a monthly chore but a real-time stream of intelligence. Through our "Network of Trust," we aim to connect thousands of verified businesses into a transparent ecosystem.</p>
                <a href="{{ route('landing.policy') }}" class="btn-main btn-blue">Explore Vision</a>
            </div>
        </div>
    </div>
</section>


<section class="about-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=1000" class="about-image" alt="Values">
            </div>
            <div class="col-lg-6">
                <div class="about-label">03. Our Values</div>
                <h2 class="about-title">Rooted in <span>Integrity.</span></h2>
                <p class="about-text"><strong>Integrity</strong> means our code has no "backdoors"—your data is private and immutable. <strong>Accuracy</strong> means we adhere to the Nigerian Financial Reporting Council's strictest guidelines, ensuring every calculation is verified to the last decimal.</p>
                <p class="about-text">We value <strong>Innovation</strong> that serves a purpose. While others chase trends, we focus on the <strong>Ethics of Accounting</strong>. Every update to SmartProbook is stress-tested against the IESBA Code of Ethics. We believe that by holding ourselves to the highest moral standards, we provide our users with a product that is not only functional but also fundamentally honorable.</p>
                <a href="{{ route('landing.team') }}" class="btn-main btn-blue">Our Values</a>
            </div>
        </div>
    </div>
</section>


<section class="about-section about-section-alt">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="section-label">Foundation</h6>
            <h2 class="section-title">Our <span>Core Values</span></h2>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4>Integrity</h4>
                <p>Transparent operations with zero compromises on data security and ethical standards.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <h4>Accuracy</h4>
                <p>Every figure verified to perfection, adhering to IFRS and GAAP guidelines.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h4>Innovation</h4>
                <p>Forward-thinking solutions that serve real business needs and drive growth.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h4>Accountability</h4>
                <p>Building trust through transparent practices and measurable results.</p>
            </div>
        </div>
    </div>
</section>


<style>
    /* ===== ABOUT PAGE STYLES ===== */
    .about-header {
        padding: 100px 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #f0f4ff 100%);
    }

    .section-label {
        color: var(--accent-red);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 3px;
        margin-bottom: 15px;
        display: block;
    }

    .section-title {
        font-size: 3.2rem;
        font-weight: 900;
        margin-bottom: 25px;
        line-height: 1.1;
        letter-spacing: -1px;
    }

    .section-title span {
        background: var(--grad-accent);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .section-subtitle {
        font-size: 1.15rem;
        color: var(--slate);
        max-width: 750px;
        margin: 0 auto;
        line-height: 1.8;
    }

    /* ===== CAROUSEL STYLES ===== */
    .hero-carousel {
        margin-top: 85px;
    }

    .carousel-item {
        height: 65vh;
        min-height: 500px;
        background-color: var(--dark);
        position: relative;
        overflow: hidden;
    }

    .carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.4;
    }

    .carousel-caption {
        position: absolute;
        bottom: 20%;
        left: 10%;
        right: auto;
        top: auto;
        text-align: left;
        transform: none;
        background: none;
        padding: 0;
        max-width: 800px;
        z-index: 10;
    }

    .carousel-caption h6 {
        color: var(--accent-gold);
        font-weight: 800;
        letter-spacing: 3px;
        margin-bottom: 20px;
    }

    .carousel-caption h1 {
        font-size: 4.2rem;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.1;
        color: white;
        margin-bottom: 20px;
    }

    .carousel-caption .text-accent {
        color: var(--accent-gold);
    }

    .carousel-caption p {
        font-size: 1.2rem;
        color: #cbd5e1;
        font-weight: 300;
    }

    .carousel-control-prev,
    .carousel-control-next {
        width: 60px;
        height: 60px;
        background: rgba(0, 98, 255, 0.2);
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
        bottom: auto;
        transition: 0.3s ease;
    }

    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        background: rgba(0, 98, 255, 0.4);
    }

    /* ===== ABOUT SECTIONS ===== */
    .about-section {
        padding: 120px 20px;
        background: white;
    }

    .about-section-alt {
        background: var(--light-bg);
    }

    .about-label {
        color: var(--accent-red);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 2px;
        margin-bottom: 15px;
        display: block;
    }

    .about-title {
        font-size: 2.8rem;
        font-weight: 900;
        margin-bottom: 30px;
        line-height: 1.2;
        letter-spacing: -0.5px;
    }

    .about-title span {
        background: var(--grad-accent);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .about-text {
        font-size: 1.05rem;
        color: var(--slate);
        line-height: 1.9;
        margin-bottom: 20px;
        text-align: justify;
    }

    .about-image {
        border-radius: 30px;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.12);
        width: 100%;
        height: 500px;
        object-fit: cover;
        border: 3px solid rgba(0, 98, 255, 0.1);
        transition: 0.4s ease;
    }

    .about-image:hover {
        box-shadow: 0 50px 100px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
    }

    /* ===== VISION CARD ===== */
    .vision-card {
        padding: 60px 50px;
        background: white;
        border-radius: 25px;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.08);
        text-align: center;
        transition: 0.4s ease;
        border: 2px solid transparent;
    }

    .vision-card:hover {
        border-color: var(--accent-red);
        transform: translateY(-10px);
        box-shadow: 0 50px 100px rgba(230, 57, 70, 0.12);
    }

    .vision-icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 30px;
        border-radius: 50%;
        background: var(--light-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s ease;
    }

    .vision-card:hover .vision-icon {
        background: linear-gradient(135deg, #0062ff 0%, #00d4ff 100%);
    }

    .vision-icon i {
        font-size: 2.5rem;
        color: var(--accent-gold);
        transition: 0.3s ease;
    }

    .vision-card:hover .vision-icon i {
        color: white;
    }

    .vision-card h3 {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 15px;
        color: var(--dark);
    }

    .vision-card p {
        color: var(--slate);
        font-size: 1rem;
        line-height: 1.8;
        margin: 0;
    }

    /* ===== VALUES GRID ===== */
    .values-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-top: 60px;
    }

    .value-card {
        padding: 50px 35px;
        background: white;
        border-radius: 20px;
        border: 1px solid #edf2f7;
        text-align: center;
        transition: 0.4s ease;
    }

    .value-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }

    .value-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 25px;
        border-radius: 50%;
        background: var(--light-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s ease;
    }

    .value-card:hover .value-icon {
        background: var(--grad-blue);
    }

    .value-icon i {
        font-size: 2rem;
        color: var(--primary);
        transition: 0.3s ease;
    }

    .value-card:hover .value-icon i {
        color: white;
    }

    .value-card h4 {
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: 15px;
        color: var(--dark);
    }

    .value-card p {
        color: var(--slate);
        font-size: 0.95rem;
        line-height: 1.7;
        margin: 0;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .section-title {
            font-size: 2.4rem;
        }

        .about-title {
            font-size: 2.2rem;
        }

        .carousel-caption h1 {
            font-size: 2.8rem;
        }

        .values-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .about-section {
            padding: 80px 20px;
        }

        .carousel-item {
            height: 50vh;
            min-height: 400px;
        }

        .carousel-caption {
            bottom: 15%;
            left: 5%;
        }

        .carousel-caption h1 {
            font-size: 1.8rem;
            margin-bottom: 12px;
        }

        .carousel-caption p {
            font-size: 0.95rem;
        }

        .section-title {
            font-size: 2rem;
        }

        .about-title {
            font-size: 1.8rem;
        }

        .about-image {
            height: 350px;
            margin-top: 30px;
        }

        .vision-card {
            padding: 40px 30px;
        }

        .values-grid {
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .row.g-5 {
            gap: 2rem !important;
        }
    }

    @media (max-width: 480px) {
        .carousel-caption h1 {
            font-size: 1.4rem;
        }

        .carousel-caption p {
            font-size: 0.85rem;
        }

        .section-title {
            font-size: 1.5rem;
        }

        .about-title {
            font-size: 1.4rem;
        }

        .about-text {
            font-size: 0.95rem;
        }

        .value-card {
            padding: 35px 25px;
        }

        .about-header {
            padding: 60px 20px;
        }
    }
</style>

@endsection
