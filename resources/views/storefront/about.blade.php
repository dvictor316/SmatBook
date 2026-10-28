<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#062354">
    <title>About {{ $store->store_name }} | Online Store</title>
    <meta name="description" content="{{ str($store->description ?: 'Learn more about ' . $store->store_name . '.')->limit(155) }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('assets/img/log-favicon.png') }}">
    <style>
        :root{--navy:#062354;--deep:#041832;--red:#d7193f;--gold:#d7a928;--ink:#12233d;--muted:#65748b;--line:#e3e8f0;--paper:#f5f7fb}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        .store-nav{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.96);border-bottom:1px solid var(--line);backdrop-filter:blur(12px)}
        .nav-inner{max-width:1260px;margin:auto;padding:13px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px}
        .brand{display:flex;align-items:center;gap:10px;color:var(--navy);font-weight:850;text-decoration:none}
        .brand img{width:40px;height:40px;object-fit:contain}
        .nav-links{display:flex;align-items:center;gap:14px}
        .nav-links a{padding:10px 13px;border-radius:3px;color:#52627a;font-size:.86rem;font-weight:750;text-decoration:none}
        .nav-links a:hover{color:var(--navy)}
        .nav-links .active{background:var(--gold);color:var(--navy)}
        .hero{position:relative;overflow:hidden;background:radial-gradient(circle at 78% 20%,rgba(215,169,40,.2),transparent 30%),linear-gradient(118deg,var(--deep),var(--navy) 62%,#123e78);color:#fff}
        .hero:after{content:"";position:absolute;width:420px;height:420px;right:-160px;bottom:-300px;border:1px solid rgba(215,169,40,.35);border-radius:50%;box-shadow:0 0 0 34px rgba(215,169,40,.05),0 0 0 72px rgba(215,169,40,.035)}
        .hero-inner{position:relative;z-index:1;max-width:1260px;min-height:390px;margin:auto;padding:78px 24px;display:flex;align-items:center}
        .hero-copy{max-width:800px}
        .eyebrow{display:flex;align-items:center;gap:10px;margin:0 0 18px;color:#f0ca66;font-size:.72rem;font-weight:850;letter-spacing:.19em;text-transform:uppercase}
        .eyebrow:before{content:"";width:27px;height:2px;background:var(--red)}
        h1,h2{font-family:Georgia,"Times New Roman",serif;font-weight:500}
        h1{margin:0 0 18px;font-size:clamp(3rem,7vw,5.4rem);line-height:.98;letter-spacing:-.04em}
        .hero-copy p{max-width:700px;margin:0;color:#d9e5f8;font-size:1.05rem;line-height:1.8}
        .content{max-width:1260px;margin:auto;padding:70px 24px 86px}
        .about-card{position:relative;overflow:hidden;max-width:920px;margin:-120px auto 54px;padding:clamp(28px,6vw,64px);background:#fff;border:1px solid var(--line);box-shadow:0 25px 70px rgba(6,35,84,.12);animation:rise-in .7s both}
        .about-card:before{content:"";position:absolute;top:0;left:0;width:5px;height:100%;background:linear-gradient(var(--gold),var(--red))}
        .about-card h2{margin:0 0 18px;color:var(--navy);font-size:clamp(1.8rem,4vw,2.6rem)}
        .about-copy{color:#4c5d74;font-size:1rem;line-height:1.9;white-space:pre-line}
        .contact-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}
        .contact-card{padding:25px;background:#fff;border:1px solid var(--line);box-shadow:0 12px 34px rgba(6,35,84,.05);animation:rise-in .65s both}
        .contact-card:nth-child(2){animation-delay:.1s}.contact-card:nth-child(3){animation-delay:.2s}
        .contact-icon{display:grid;width:43px;height:43px;margin-bottom:17px;place-items:center;background:#fff7dc;color:var(--red);font-size:1.1rem}
        .contact-card h3{margin:0 0 8px;color:var(--navy);font-size:.93rem;font-weight:850}
        .contact-card p,.contact-card a{margin:0;color:var(--muted);font-size:.9rem;line-height:1.65;text-decoration:none;overflow-wrap:anywhere}
        .contact-card a:hover{color:var(--red)}
        .cta{margin-top:46px;padding:42px 24px;text-align:center;background:linear-gradient(110deg,var(--navy),#0b3979);color:#fff}
        .cta h2{margin:0 0 10px;font-size:clamp(1.8rem,4vw,2.6rem)}
        .cta p{margin:0 0 21px;color:#d9e5f8}
        .button{display:inline-flex;min-height:47px;align-items:center;justify-content:center;padding:0 20px;border-radius:3px;background:var(--gold);color:var(--navy);font-size:.88rem;font-weight:850;text-decoration:none;transition:transform .2s ease,box-shadow .2s ease}
        .button:hover{transform:translateY(-2px);box-shadow:0 9px 22px rgba(0,0,0,.2);color:var(--navy)}
        .footer{padding:28px 24px;background:var(--deep);color:#d9e5f8}
        .footer-inner{max-width:1260px;margin:auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
        .footer strong{color:#fff}
        @keyframes rise-in{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:700px){.nav-inner{padding:10px 14px;flex-wrap:wrap}.brand{max-width:44%;font-size:.86rem}.brand span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.brand img{width:34px;height:34px}.nav-links{gap:2px;margin-left:auto}.nav-links a{padding:8px;font-size:.74rem}.nav-links a:first-child{display:none}.hero-inner{min-height:340px;padding:62px 18px}.content{padding:46px 16px 60px}.about-card{margin:-80px 0 36px;padding:30px 25px}.contact-grid{grid-template-columns:1fr}.cta{padding:34px 18px}.footer{padding:25px 16px}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation:none!important;transition:none!important}}
    </style>
    <link rel="stylesheet" href="{{ asset('css/storefront-theme.css') }}">
</head>
<body>
@php
    $customDomain = request()->routeIs('storefront.custom-domain*');
    $homeUrl = $customDomain ? route('storefront.custom-domain') : route('storefront.catalogue', $store->slug);
    $galleryUrl = $customDomain ? route('storefront.custom-domain.products') : route('storefront.products', $store->slug);
@endphp
<nav class="store-nav">
    <div class="nav-inner">
        <a class="brand" href="{{ $homeUrl }}"><img src="{{ $company->logo ? asset($company->logo) : asset('assets/img/logos.png') }}" alt=""><span>{{ $store->store_name }}</span></a>
        <div class="nav-links">
            <a href="{{ $homeUrl }}">Home</a>
            <a href="{{ $galleryUrl }}">Product Gallery</a>
            <a class="active" href="{{ request()->fullUrl() }}" aria-current="page">About Us</a>
        </div>
    </div>
</nav>
<header class="hero">
    <div class="hero-inner">
        <div class="hero-copy">
            <p class="eyebrow">The people behind the store</p>
            <h1>About {{ $store->store_name }}</h1>
            <p>Get to know the business, explore the products we offer, and reach our team whenever you need help.</p>
        </div>
    </div>
</header>
<main class="content">
    <section class="about-card">
        <p class="eyebrow">Our story</p>
        <h2>{{ $store->store_name }}</h2>
        <div class="about-copy">{{ $store->description ?: 'Welcome to our store. Browse our product gallery to see what is currently available, and contact our team if you have any questions.' }}</div>
    </section>
    <section class="contact-grid" aria-label="Business contact details">
        <article class="contact-card">
            <span class="contact-icon" aria-hidden="true">☎</span>
            <h3>Call our team</h3>
            @if($store->contact_phone)<a href="tel:{{ $store->contact_phone }}">{{ $store->contact_phone }}</a>@else<p>Contact details will be added soon.</p>@endif
        </article>
        <article class="contact-card">
            <span class="contact-icon" aria-hidden="true">✉</span>
            <h3>Email us</h3>
            @if($store->contact_email)<a href="mailto:{{ $store->contact_email }}">{{ $store->contact_email }}</a>@else<p>Contact details will be added soon.</p>@endif
        </article>
        <article class="contact-card">
            <span class="contact-icon" aria-hidden="true">✦</span>
            <h3>Orders and fulfilment</h3>
            <p>{{ $store->fulfilment_note ?: 'Our team will confirm availability, payment, and delivery details after you place an order request.' }}</p>
        </article>
    </section>
    <section class="cta">
        <h2>Ready to explore?</h2>
        <p>See the products currently available from {{ $store->store_name }}.</p>
        <a class="button" href="{{ $galleryUrl }}">Visit Product Gallery</a>
    </section>
</main>
<footer class="footer">
    <div class="footer-inner">
        <strong>{{ $store->store_name }}</strong>
        <span>{{ $store->contact_phone }}{{ $store->contact_phone && $store->contact_email ? ' | ' : '' }}{{ $store->contact_email }}</span>
        <span>Powered by SmartProbook</span>
    </div>
</footer>
</body>
</html>
