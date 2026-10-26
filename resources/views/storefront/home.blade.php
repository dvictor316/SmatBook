<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#062354">
    <title>{{ $store->store_name }} | Online Store</title>
    <meta name="description" content="{{ str($store->description ?: 'Explore our latest products and shop online.')->limit(155) }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('assets/img/log-favicon.png') }}">
    <style>
        :root{--navy:#062354;--navy-deep:#041832;--red:#d7193f;--gold:#d7a928;--ink:#12233d;--muted:#65748b;--paper:#f5f7fb;--line:#e3e8f0}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        a{color:inherit}
        .store-nav{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.96);border-bottom:1px solid var(--line);backdrop-filter:blur(14px)}
        .nav-inner{max-width:1380px;margin:auto;padding:13px 26px;display:flex;align-items:center;justify-content:space-between;gap:24px}
        .brand{display:flex;align-items:center;gap:11px;color:var(--navy);font-size:1.03rem;font-weight:850;text-decoration:none;letter-spacing:.015em}
        .brand img{width:42px;height:42px;object-fit:contain}
        .nav-links{display:flex;align-items:center;gap:25px}
        .nav-links a{text-decoration:none;font-size:.9rem;font-weight:700;color:#52627a;transition:color .2s ease}
        .nav-links a:hover,.nav-links a[aria-current=page]{color:var(--navy)}
        .nav-gallery{padding:11px 16px;border-radius:4px;background:var(--gold);color:var(--navy)!important}
        .nav-about{padding:10px 13px;border:1px solid rgba(215,169,40,.55);border-radius:4px}
        .hero{position:relative;overflow:hidden;background:radial-gradient(ellipse at 73% 28%,rgba(215,169,40,.18),transparent 34%),linear-gradient(116deg,var(--navy-deep),#082d61 70%,#123e78);color:#fff}
        .hero:after{content:"";position:absolute;width:440px;height:440px;right:-145px;top:-210px;border:1px solid rgba(215,169,40,.32);border-radius:50%;box-shadow:0 0 0 36px rgba(215,169,40,.04),0 0 0 78px rgba(215,169,40,.035);pointer-events:none}
        .hero-inner{position:relative;z-index:1;max-width:1380px;margin:auto;min-height:530px;padding:68px 26px;display:grid;grid-template-columns:1fr 1.02fr;align-items:center;gap:64px}
        .eyebrow{display:flex;align-items:center;gap:10px;margin:0 0 20px;color:#f0ca66;font-size:.73rem;font-weight:850;letter-spacing:.19em;text-transform:uppercase}
        .eyebrow:before{content:"";width:28px;height:2px;background:var(--red)}
        .hero h1{max-width:650px;margin:0 0 20px;font-family:Georgia,"Times New Roman",serif;font-size:clamp(3rem,6.4vw,6rem);font-weight:500;line-height:.98;letter-spacing:-.045em}
        .hero-copy{max-width:565px;color:#d5dfed;font-size:1.04rem;line-height:1.8}
        .hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}
        .button{display:inline-flex;min-height:48px;align-items:center;justify-content:center;padding:0 21px;border:1px solid transparent;border-radius:4px;text-decoration:none;font-size:.88rem;font-weight:850;transition:transform .2s ease,background .2s ease,box-shadow .2s ease}
        .button:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(2,16,37,.2)}
        .button-gold{background:var(--gold);color:var(--navy)}
        .button-outline{border-color:rgba(255,255,255,.45);color:#fff}
        .hero-product{position:relative;min-height:380px;overflow:hidden;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);box-shadow:0 28px 70px rgba(0,0,0,.22)}
        .hero-product img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
        .hero-product:after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,rgba(3,17,38,.9),rgba(3,17,38,.02) 72%)}
        .hero-product-placeholder{position:absolute;inset:0;display:grid;place-items:center;background:linear-gradient(135deg,#123c75,#071c3c);color:#f0ca66;font-size:4rem}
        .hero-product-copy{position:absolute;z-index:1;right:0;bottom:0;left:0;padding:27px}
        .hero-product-copy h2{margin:0 0 7px;font-family:Georgia,"Times New Roman",serif;font-size:1.75rem;font-weight:500}
        .hero-product-copy p{max-width:520px;margin:0;color:#d5dfed;line-height:1.6}
        .hero-price{display:inline-block;margin-top:13px;color:#f0ca66;font-size:1.1rem;font-weight:850}
        .section{max-width:1380px;margin:auto;padding:76px 26px}
        .section-heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:25px}
        .section-heading h2{margin:0;color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:clamp(2rem,4vw,3.2rem);font-weight:500;letter-spacing:-.025em}
        .section-heading p{margin:8px 0 0;color:var(--muted)}
        .text-link{color:var(--red);font-size:.88rem;font-weight:850;text-decoration:none;white-space:nowrap}
        .text-link:hover{text-decoration:underline}
        .two-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}
        .tile{display:grid;grid-template-columns:1fr 1fr;min-height:285px;overflow:hidden;background:#fff;border:1px solid var(--line);box-shadow:0 10px 35px rgba(6,35,84,.055);transition:transform .25s ease,box-shadow .25s ease}
        .tile:hover,.editorial:hover,.small-card:hover{transform:translateY(-5px);box-shadow:0 20px 44px rgba(6,35,84,.12)}
        .tile-media,.editorial-media,.small-media{position:relative;overflow:hidden;background:#e7edf5}
        .tile-media img,.editorial-media img,.small-media img{width:100%;height:100%;object-fit:cover;transition:transform .45s ease}
        .tile:hover img,.editorial:hover img,.small-card:hover img{transform:scale(1.045)}
        .placeholder{display:grid;min-height:100%;place-items:center;color:#5073a1;font-size:2.8rem}
        .tile-body{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:24px}
        .tile-body h3,.editorial-body h3,.small-card h3{margin:0;color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:1.5rem;font-weight:500;line-height:1.2}
        .sku{margin:9px 0;color:var(--muted);font-size:.76rem;letter-spacing:.05em}
        .description{display:-webkit-box;overflow:hidden;-webkit-line-clamp:3;-webkit-box-orient:vertical;margin:0 0 14px;color:#5f6f85;font-size:.9rem;line-height:1.65}
        .price{margin:0 0 17px;color:var(--red);font-size:1.03rem;font-weight:900}
        .product-stock{margin:-9px 0 13px;color:var(--muted);font-size:.78rem;font-weight:700}
        .small-button{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;background:var(--navy);color:#fff;text-decoration:none;font-size:.8rem;font-weight:800;transition:background .2s ease}
        .small-button:hover{background:var(--red);color:#fff}
        .editorial-list{display:grid;gap:18px}
        .editorial{display:grid;grid-template-columns:.95fr 1.05fr;min-height:245px;overflow:hidden;background:#fff;border:1px solid var(--line);transition:transform .25s ease,box-shadow .25s ease}
        .editorial:nth-child(even){grid-template-columns:1.05fr .95fr}
        .editorial:nth-child(even) .editorial-media{order:2}
        .editorial-body{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:clamp(22px,4vw,45px)}
        .editorial-body .eyebrow{margin-bottom:11px;color:#a77b0d;font-size:.66rem}
        .editorial-body .description{max-width:570px;margin-top:12px}
        .small-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
        .small-card{overflow:hidden;background:#fff;border:1px solid var(--line);transition:transform .25s ease,box-shadow .25s ease}
        .small-media{height:230px}
        .small-card-body{padding:19px}
        .small-card h3{font-size:1.3rem}
        .collection-cta{margin:10px 26px 80px;padding:50px 28px;text-align:center;background:linear-gradient(110deg,var(--navy),#0b3979);color:#fff}
        .collection-cta h2{margin:0 0 11px;font-family:Georgia,"Times New Roman",serif;font-size:clamp(1.8rem,4vw,3rem);font-weight:500}
        .collection-cta p{margin:0 0 23px;color:#d5dfed}
        .empty-state{padding:50px 20px;text-align:center;color:var(--muted);background:#fff;border:1px dashed #b9c7d9}
        .footer{padding:30px 26px;background:var(--navy-deep);color:#d5dfed}
        .footer-inner{max-width:1380px;margin:auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px}
        .footer strong{color:#fff}
        .reveal{animation:rise-in .65s both}
        @keyframes rise-in{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:900px){.hero-inner{grid-template-columns:1fr;gap:30px;padding-top:54px}.hero-product{min-height:340px}.two-grid{grid-template-columns:1fr}.small-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:620px){.nav-inner{padding:10px 15px;gap:10px;flex-wrap:wrap}.brand{max-width:46%;font-size:.88rem}.brand span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.brand img{width:34px;height:34px}.nav-links{gap:7px}.nav-links a{font-size:.72rem}.nav-gallery{padding:9px 8px}.nav-about{padding:8px}.nav-home{display:none}.hero-inner{min-height:0;padding:50px 17px 38px}.hero h1{font-size:clamp(2.7rem,13vw,4.3rem)}.hero-product{min-height:300px}.section{padding:50px 16px}.section-heading{align-items:flex-start;flex-direction:column}.tile{grid-template-columns:1fr}.tile-media{height:220px}.tile-body{padding:19px}.editorial,.editorial:nth-child(even){grid-template-columns:1fr}.editorial:nth-child(even) .editorial-media{order:0}.editorial-media{height:220px}.editorial-body{padding:22px}.small-grid{grid-template-columns:1fr}.small-media{height:250px}.collection-cta{margin:0 16px 50px;padding:38px 20px}.footer{padding:25px 17px}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.reveal{animation:none!important}*,*:before,*:after{transition:none!important}}
    </style>
</head>
<body>
@php
    $customDomain = request()->routeIs('storefront.custom-domain*');
    $homeUrl = $customDomain ? route('storefront.custom-domain') : route('storefront.catalogue', $store->slug);
    $galleryUrl = $customDomain ? route('storefront.custom-domain.products') : route('storefront.products', $store->slug);
    $aboutUrl = $customDomain ? route('storefront.custom-domain.about') : route('storefront.about', $store->slug);
    $featuredProduct = $products->first();
    $twoGridProducts = $products->slice(1, 4);
    $editorialProducts = $products->slice(5, 2);
    $finalProducts = $products->slice(7, 3);
@endphp
<nav class="store-nav">
    <div class="nav-inner">
        <a class="brand" href="{{ $homeUrl }}">
            <img src="{{ $company->logo ? asset($company->logo) : asset('assets/img/logos.png') }}" alt="">
            <span>{{ $store->store_name }}</span>
        </a>
        <div class="nav-links">
            <a class="nav-home" href="{{ $homeUrl }}" aria-current="page">Home</a>
            <a class="nav-gallery" href="{{ $galleryUrl }}">Product Gallery</a>
            <a class="nav-about" href="{{ $aboutUrl }}">About Us</a>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="hero-inner">
        <div class="reveal">
            <p class="eyebrow">Welcome to our store</p>
            <h1>{{ $store->store_name }}</h1>
            <p class="hero-copy">{{ $store->description ?: 'Discover a considered collection of products, selected for quality and ready to explore.' }}</p>
            <div class="hero-actions">
                <a class="button button-gold" href="{{ $galleryUrl }}">Explore Product Gallery</a>
                @if($store->contact_phone)<a class="button button-outline" href="tel:{{ $store->contact_phone }}">Contact the store</a>@endif
            </div>
        </div>
        @if($featuredProduct)
            @php($featuredStock = max((float) ($featuredProduct->stock ?? 0), (float) ($featuredProduct->stock_quantity ?? 0)))
            <article class="hero-product reveal">
                @if($featuredProduct->image_url)
                    <img src="{{ $featuredProduct->image_url }}" alt="{{ $featuredProduct->name }}" fetchpriority="high">
                @else
                    <div class="hero-product-placeholder" aria-hidden="true">✦</div>
                @endif
                <div class="hero-product-copy">
                    <p class="eyebrow">Featured product</p>
                    <h2>{{ $featuredProduct->name }}</h2>
                    @if(filled($featuredProduct->description))<p>{{ \Illuminate\Support\Str::limit(strip_tags($featuredProduct->description), 135) }}</p>@endif
                    <span class="hero-price">{{ \App\Support\GeoCurrency::format((float) $featuredProduct->price, $company->currency_code ?: 'NGN', $company->currency_code ?: 'NGN') }}</span>
                    <span class="d-block small mt-1">{{ $featuredStock > 0 ? $featuredProduct->formatStockQuantity($featuredStock) . ' available' : 'Out of stock' }}</span>
                </div>
            </article>
        @else
            <div class="hero-product-placeholder" aria-hidden="true">✦</div>
        @endif
    </div>
</header>

<main>
    @if($products->isEmpty())
        <section class="section"><div class="empty-state">Our collection is being prepared. Please check back soon.</div></section>
    @else
        @if($twoGridProducts->isNotEmpty())
            <section class="section">
                <div class="section-heading"><div><p class="eyebrow">A closer look</p><h2>Explore the collection</h2><p>Selected products, with the details that matter.</p></div><a class="text-link" href="{{ $galleryUrl }}">View the full gallery &rarr;</a></div>
                <div class="two-grid">
                    @foreach($twoGridProducts as $product)
                        @php($productStock = max((float) ($product->stock ?? 0), (float) ($product->stock_quantity ?? 0)))
                        <article class="tile reveal">
                            <div class="tile-media">
                                @if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">@else<div class="placeholder" aria-hidden="true">✦</div>@endif
                            </div>
                            <div class="tile-body">
                                <h3>{{ $product->name }}</h3><p class="sku">{{ $product->sku }}</p>
                                @if(filled($product->description))<p class="description">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 130) }}</p>@endif
                                <p class="price">{{ \App\Support\GeoCurrency::format((float) $product->price, $company->currency_code ?: 'NGN', $company->currency_code ?: 'NGN') }}</p>
                                <p class="product-stock">{{ $productStock > 0 ? $product->formatStockQuantity($productStock) . ' available' : 'Out of stock' }}</p>
                                <a class="small-button" href="{{ $galleryUrl }}?q={{ urlencode($product->name) }}">See product</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if($editorialProducts->isNotEmpty())
            <section class="section">
                <div class="section-heading"><div><p class="eyebrow">Made to stand out</p><h2>Product stories</h2><p>Find the right fit for what you are looking for.</p></div></div>
                <div class="editorial-list">
                    @foreach($editorialProducts as $product)
                        @php($productStock = max((float) ($product->stock ?? 0), (float) ($product->stock_quantity ?? 0)))
                        <article class="editorial reveal">
                            <div class="editorial-media">
                                @if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">@else<div class="placeholder" aria-hidden="true">✦</div>@endif
                            </div>
                            <div class="editorial-body">
                                <p class="eyebrow">From our collection</p><h3>{{ $product->name }}</h3><p class="sku">{{ $product->sku }}</p>
                                <p class="description">{{ filled($product->description) ? \Illuminate\Support\Str::limit(strip_tags($product->description), 220) : 'Explore product details, current availability, and ordering options in our gallery.' }}</p>
                                <p class="price">{{ \App\Support\GeoCurrency::format((float) $product->price, $company->currency_code ?: 'NGN', $company->currency_code ?: 'NGN') }}</p>
                                <p class="product-stock">{{ $productStock > 0 ? $product->formatStockQuantity($productStock) . ' available' : 'Out of stock' }}</p>
                                <a class="small-button" href="{{ $galleryUrl }}?q={{ urlencode($product->name) }}">View details</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if($finalProducts->isNotEmpty())
            <section class="section">
                <div class="section-heading"><div><p class="eyebrow">More to discover</p><h2>More from the store</h2></div><a class="text-link" href="{{ $galleryUrl }}">Browse all products &rarr;</a></div>
                <div class="small-grid">
                    @foreach($finalProducts as $product)
                        @php($productStock = max((float) ($product->stock ?? 0), (float) ($product->stock_quantity ?? 0)))
                        <article class="small-card reveal">
                            <div class="small-media">
                                @if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">@else<div class="placeholder" aria-hidden="true">✦</div>@endif
                            </div>
                            <div class="small-card-body">
                                <h3>{{ $product->name }}</h3><p class="sku">{{ $product->sku }}</p>
                                @if(filled($product->description))<p class="description">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 100) }}</p>@endif
                                <p class="price">{{ \App\Support\GeoCurrency::format((float) $product->price, $company->currency_code ?: 'NGN', $company->currency_code ?: 'NGN') }}</p>
                                <p class="product-stock">{{ $productStock > 0 ? $product->formatStockQuantity($productStock) . ' available' : 'Out of stock' }}</p>
                                <a class="small-button" href="{{ $galleryUrl }}?q={{ urlencode($product->name) }}">See product</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="collection-cta reveal">
            <h2>Find something you will love</h2>
            <p>Browse every product currently listed by {{ $store->store_name }}.</p>
            <a class="button button-gold" href="{{ $galleryUrl }}">Visit Product Gallery</a>
        </section>
    @endif
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
