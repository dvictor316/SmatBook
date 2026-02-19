<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal & Subscriptions | SmatBook Global</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --executive-red: #dc2626;
            --gold: #f59e0b;
            --dark-navy: #020617;
            --off-white: #fcfcfd;
            --smat-blue: #2563eb;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--off-white); 
            color: var(--dark-navy);
            line-height: 1.8;
        }

        /* --- NAVIGATION --- */
        nav {
            position: fixed; width: 100%; top: 0; z-index: 9999;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid #e2e8f0; height: 90px; display: flex; align-items: center;
        }
        .nav-container { max-width: 1400px; margin: 0 auto; width: 100%; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; }
        .logo-text { font-size: 1.6rem; font-weight: 800; color: var(--dark-navy); text-decoration: none; letter-spacing: -1px; }
        .logo-text span { color: var(--executive-red); }
        .nav-links { display: flex; list-style: none; gap: 25px; margin: 0; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--dark-navy); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
        .nav-links a:hover { color: var(--executive-red); }
        .nav-links a.active { color: var(--gold) !important; }

        /* --- HERO CAROUSEL --- */
        .hero-carousel { margin-top: 90px; }
        .carousel-item { height: 45vh; min-height: 400px; background-color: var(--dark-navy); }
        .carousel-item img { object-fit: cover; height: 100%; width: 100%; opacity: 0.4; }
        .carousel-caption { bottom: 20%; text-align: left; max-width: 850px; left: 10%; z-index: 10; }
        .carousel-caption h1 { font-size: 3.5rem; font-weight: 800; letter-spacing: -2px; }

        /* --- CONTENT --- */
        .section-padding { padding: 100px 0; }
        .premium-card { border-radius: 30px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.05); transition: 0.4s; }
        .premium-card:hover { transform: translateY(-10px); box-shadow: 0 40px 80px rgba(0,0,0,0.1); }
        .text-gold { color: var(--gold); }
        .text-red { color: var(--executive-red); }

        /* --- FOOTER --- */
        .landing-footer { background: var(--dark-navy); color: #94a3b8; padding: 100px 0 40px; }
        .footer-logo { font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 20px; display: block; text-decoration: none; }
        .footer-logo span { color: var(--executive-red); }
        .footer-link { color: #94a3b8; text-decoration: none; transition: 0.3s; display: block; margin-bottom: 12px; font-weight: 500; }
        .footer-link:hover { color: var(--gold); transform: translateX(5px); }

        @media (max-width: 991px) { .nav-links { display: none; } .carousel-caption h1 { font-size: 2.5rem; } }
    </style>
</head>
<body>

<nav>
    <div class="nav-container">
        <a href="{{ url('/') }}" class="logo-text d-flex align-items-center text-decoration-none">
            <img src="{{ asset('assets/img/smat12.png') }}" class="brand-img me-2" alt="SmatBook" style="height: 40px;">
            <div class="logo-text">SMAT<span>BOOK</span></div>
        </a>
        <ul class="nav-links" id="mainMenu">
            <li><a href="{{ url('/#home') }}">Home</a></li>
            <li><a href="{{ route('landing.about') }}">About Us</a></li>
            <li><a href="{{ route('landing.team') }}">Other Projects</a></li>
            <li><a href="{{ url('/#licensing') }}">Licensing</a></li>
            <li><a href="{{ route('landing.policy') }}">Policy</a></li>
            <li><a href="{{ route('saas-login') }}" class="active" style="background: var(--smat-blue); color: white !important; padding: 12px 25px; border-radius: 8px;">Client Portal</a></li>
        </ul>
    </div>
</nav>

<div id="subCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=2011" alt="Subscriptions">
            <div class="carousel-caption">
                <h6 class="text-gold fw-bold text-uppercase mb-3" style="letter-spacing: 5px;">Enterprise Access</h6>
                <h1>Secure <span class="text-gold">Client Portal.</span></h1>
                <p class="lead text-white-50">Manage your corporate licenses, subscriptions, and financial intelligence tools from one unified dashboard.</p>
            </div>
        </div>
    </div>
</div>

<section class="section-padding">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h6 class="text-red fw-bold text-uppercase mb-3" style="letter-spacing: 5px;">Coming Soon</h6>
                <h2 class="display-4 fw-bold mb-4">Your Financial <span class="text-gold">Command Center.</span></h2>
                <p class="lead text-muted mb-5">We are finalizing our automated subscription management module. Soon, you will be able to scale your operations, add entities, and manage global compliance from this portal.</p>
                
                <div class="row g-4 text-start">
                    <div class="col-md-6">
                        <div class="card premium-card p-4 h-100">
                            <i class="fas fa-layer-group fa-2x text-gold mb-3"></i>
                            <h4 class="fw-bold">Multi-Entity Sync</h4>
                            <p class="small text-muted">Manage different branches or companies under a single master subscription with consolidated reporting.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card premium-card p-4 h-100">
                            <i class="fas fa-file-invoice-dollar fa-2x text-red mb-3"></i>
                            <h4 class="fw-bold">Automated Invoicing</h4>
                            <p class="small text-muted">Direct IFRS-compliant billing and real-time licensing updates for your corporate audit trails.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="landing-footer">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <a href="{{ url('/') }}" class="footer-logo">SMAT<span>BOOK</span></a>
                <p>Engineered for excellence. The premier financial intelligence ecosystem for modern African enterprises.</p>
            </div>
            <div class="col-lg-3 offset-lg-1">
                <h5 class="text-white fw-bold mb-4">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="{{ url('/#home') }}" class="footer-link">Home</a></li>
                    <li><a href="{{ route('landing.about') }}" class="footer-link">About Us</a></li>
                    <li><a href="{{ route('landing.team') }}" class="footer-link">Other Projects</a></li>
                    <li><a href="{{ route('landing.policy') }}" class="footer-link">Policy</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h5 class="text-white fw-bold mb-4">Global HQ</h5>
                <p>Enugu Tech Hub, Independence Layout,<br>Enugu, Nigeria.</p>
                <p>Email: info@smatbook.com</p>
            </div>
        </div>
        <hr style="border-color: rgba(255,255,255,0.1); margin: 60px 0 30px;">
        <p class="small text-center mb-0">&copy; 2026 SmatBook Global Infrastructure Inc. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</body>
</html>
