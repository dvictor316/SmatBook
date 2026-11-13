<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('storefront.partials.mobile-menu-bootstrap')
    <meta name="theme-color" content="#ffffff">
    <title>{{ $store->store_name }} | Online Store</title>
    <meta name="description" content="{{ str($store->description ?: 'Browse products and place an order online.')->limit(155) }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('assets/img/log-favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--navy:#062354;--blue:#1463d6;--red:#d7193f;--gold:#d7a928;--ink:#0b1830;--line:#dbe4f0;--soft:#f4f7fb}*{box-sizing:border-box}body{margin:0;background:var(--soft);color:var(--ink);font-family:Arial,sans-serif;letter-spacing:0}.store-nav{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20}.store-nav-inner{max-width:1320px;margin:auto;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{display:flex;align-items:center;gap:12px;color:var(--navy);text-decoration:none;font-weight:800;font-size:1.1rem}.brand img{width:46px;height:46px;object-fit:contain}.store-search{width:min(480px,55vw);display:flex}.store-search input{border-radius:5px 0 0 5px}.store-search button{border-radius:0 5px 5px 0}.store-head{background:var(--navy);color:#fff;padding:44px 20px}.store-head-inner{max-width:1320px;margin:auto}.store-head h1{font-size:clamp(1.8rem,4vw,3rem);font-weight:800;margin:0 0 10px}.store-head p{max-width:760px;color:#d9e5f8;margin:0}.store-main{max-width:1320px;margin:auto;padding:30px 20px 60px}.product-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.product{background:#fff;border:1px solid var(--line);border-radius:6px;overflow:hidden;display:flex;flex-direction:column;min-width:0}.product-media{aspect-ratio:4/3;background:#edf3fb;display:flex;align-items:center;justify-content:center;overflow:hidden}.product-media img{width:100%;height:100%;object-fit:cover}.product-media i{font-size:3rem;color:var(--blue)}.product-body{padding:16px;display:flex;flex-direction:column;gap:9px;flex:1}.product h2{font-size:1rem;font-weight:800;margin:0;overflow-wrap:anywhere}.price{font-size:1.25rem;font-weight:800;color:var(--navy)}.stock{font-size:.82rem;color:#53708f}.quantity{display:flex;align-items:center;gap:8px;margin-top:auto}.quantity input{width:82px}.checkout{margin-top:28px;background:#fff;border:1px solid var(--line);border-top:4px solid var(--red);padding:24px;border-radius:6px}.checkout h2{font-size:1.35rem;font-weight:800}.btn-order{background:var(--red);color:#fff;border:0;border-radius:5px;font-weight:800;padding:13px 22px}.btn-order:hover{background:#b91335;color:#fff}.store-footer{background:var(--navy);color:#d9e5f8;padding:28px 20px}.store-footer-inner{max-width:1320px;margin:auto;display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}@media(max-width:992px){.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:576px){.store-nav-inner{align-items:flex-start;flex-direction:column}.store-search{width:100%}.product-grid{grid-template-columns:1fr}.store-head{padding:32px 16px}.store-main{padding:22px 14px 44px}}
    </style>
    <style>
        :root{--navy:#062354;--blue:#1463d6;--red:#d7193f;--gold:#d7a928;--ink:#0b1830;--line:#dbe4f0;--soft:#f4f7fb}
        body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;background:var(--soft);color:var(--ink)}
        .store-nav{background:rgba(255,255,255,.96);border-color:var(--line);backdrop-filter:blur(12px)}
        .store-nav-inner,.store-head-inner,.store-main,.store-footer-inner{max-width:1440px}
        .store-nav-inner{padding-top:16px;padding-bottom:16px}
        .brand{color:var(--navy);letter-spacing:.02em}
        .brand img{width:42px;height:42px}
        .store-search input{border-color:#dbe4f0;padding:.72rem .9rem}
        .store-search button{background:var(--navy);border-color:var(--navy);color:#fff;padding-inline:1.1rem}
        .gallery-nav-links{display:flex;align-items:center;gap:18px;white-space:nowrap}
        .gallery-nav-links a{color:#52627a;font-size:.86rem;font-weight:750;text-decoration:none}
        .gallery-nav-links a:hover,.gallery-nav-links a[aria-current=page]{color:var(--navy)}
        .gallery-nav-links a[aria-current=page]{padding:10px 13px;background:var(--gold);color:var(--navy)}
        .gallery-nav-about{padding:9px 12px;border:1px solid rgba(215,169,40,.6);border-radius:3px}
        .store-head{position:relative;overflow:hidden;padding:clamp(58px,9vw,116px) 24px;background:radial-gradient(ellipse at 82% 18%,rgba(215,169,40,.2),transparent 36%),linear-gradient(112deg,#041832,#062354 62%,#123e78);border-bottom:1px solid rgba(215,169,40,.4)}
        .store-head:after{content:"";position:absolute;width:34vw;height:34vw;min-width:260px;min-height:260px;border:1px solid rgba(210,170,105,.25);border-radius:50%;right:-10vw;bottom:-26vw;box-shadow:0 0 0 32px rgba(210,170,105,.04),0 0 0 68px rgba(210,170,105,.035)}
        .store-head-inner{position:relative;z-index:1}
        .store-eyebrow{display:inline-flex;align-items:center;gap:10px;margin-bottom:20px;color:#f0ca66;font-size:.72rem;font-weight:800;letter-spacing:.2em;text-transform:uppercase}
        .store-eyebrow:before{content:"";width:30px;height:2px;background:var(--red)}
        .store-head h1{max-width:850px;margin-bottom:16px;font-family:Georgia,"Times New Roman",serif;font-size:clamp(2.7rem,7vw,5.6rem);font-weight:500;line-height:.98;letter-spacing:-.045em}
        .store-head p{max-width:610px;color:#d9e5f8;font-size:1.05rem;line-height:1.75}
        .store-main{padding:48px 24px 76px}
        .catalogue-heading{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:24px}
        .catalogue-heading h2{margin:0;color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:clamp(1.8rem,3vw,2.5rem);font-weight:500}
        .catalogue-heading p{margin:7px 0 0;color:#77766f}
        .catalogue-count{color:#77766f;font-size:.83rem;white-space:nowrap}
        .product-grid{grid-template-columns:minmax(0,1fr);gap:18px;max-width:1120px}
        .product{display:grid;grid-template-columns:minmax(260px,38%) minmax(0,1fr);border:1px solid #e3eaf3;border-radius:12px;background:#fff;transition:transform .2s ease,box-shadow .2s ease;animation:storefront-rise .55s both}
        .product:hover{transform:translateY(-3px);box-shadow:0 18px 42px rgba(6,35,84,.1)}
        .product-media{aspect-ratio:auto;min-height:320px;height:100%;background:linear-gradient(145deg,#edf3fb,#f9fbfe)}
        .product-media img{transition:transform .45s ease}
        .product:hover .product-media img{transform:scale(1.035)}
        .product-media i{color:var(--blue)}
        .product-body{min-width:0;gap:14px;padding:clamp(22px,3vw,38px)}
        .product-eyebrow{color:#8a6a17;font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .product h2{font-family:Georgia,"Times New Roman",serif;font-size:clamp(1.55rem,2.4vw,2.1rem);font-weight:500;line-height:1.15}
        .product-options-row{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;min-width:0;max-width:100%;padding-bottom:4px}
        .product-size-picker{display:grid;flex:0 1 190px;grid-template-columns:1fr;gap:7px;width:190px;max-width:100%}
        .product-size-picker label{color:var(--navy);font-size:.82rem;font-weight:800}
        .product-size-picker select{width:100%;min-width:0;min-height:44px;border:1px solid var(--gold);border-radius:8px;background:var(--paper);font-weight:700}
        .product-description{margin:0;color:#64748b;font-size:.95rem;line-height:1.7;white-space:pre-line}
        .product-meta{display:flex;flex:1 1 180px;flex-wrap:wrap;gap:8px;min-width:0;max-width:100%;padding-bottom:0}
        .product-meta span{display:inline-flex;max-width:100%;align-items:center;gap:6px;padding:7px 10px;border:1px solid #e7edf5;border-radius:999px;background:#f8fafc;color:#52627a;font-size:.78rem;overflow-wrap:anywhere}
        .product-available-sizes{display:flex;align-items:center;flex-wrap:wrap;gap:7px}
        .product-available-sizes-label{color:#64748b;font-size:.78rem;font-weight:800}
        .product-available-sizes span[data-available-size]{padding:5px 9px;border:1px solid var(--theme-line,#dbe4f0);border-radius:999px;background:var(--theme-wash,rgba(20,99,214,.12));color:var(--theme-primary,var(--blue));font-size:.76rem;font-weight:800}
        .product-purchase{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-top:auto;padding-top:18px;border-top:1px solid #edf0f5}
        .product-price-label{margin-bottom:4px;color:#75849a;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
        .price{color:var(--red);font-size:clamp(1.35rem,2vw,1.8rem);line-height:1.15}
        .stock{margin-top:7px;color:#536984}
        .stock.in-stock{color:#16834b}
        .stock.out-of-stock{color:#b4233b}
        .quantity{justify-content:flex-end;gap:10px;margin:0;padding:0;border:0}
        .quantity input{width:88px;min-height:44px;border-color:#dbe4f0}
        .add-to-cart{min-height:44px;padding-inline:18px;border-radius:5px;font-weight:750}
        .checkout{max-width:1060px;margin:42px auto 0;padding:0;overflow:hidden;border:1px solid var(--theme-line,#dbe4f0);border-radius:20px;background:#fff;box-shadow:0 24px 70px rgba(10,30,66,.13);animation:storefront-rise .65s .12s both}
        .checkout-heading{padding:clamp(30px,5vw,54px) 24px 30px;text-align:center;background:radial-gradient(ellipse at 50% 0,var(--theme-wash,rgba(20,99,214,.12)),transparent 68%),linear-gradient(180deg,#fff,#fbfcff);border-bottom:1px solid #e8edf5}
        .checkout-kicker{display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;padding:7px 13px;border:1px solid var(--theme-line,#dbe4f0);border-radius:999px;background:#fff;color:var(--theme-primary,var(--blue));font-size:.7rem;font-weight:850;letter-spacing:.14em;text-transform:uppercase}
        .checkout-heading h2{margin:0;color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:clamp(2rem,4vw,2.7rem);font-weight:500;line-height:1.1}
        .checkout-heading p{max-width:600px;margin:12px auto 0;color:#65748b;font-size:1rem;line-height:1.65}
        .checkout-content{padding:clamp(20px,4vw,40px);background:var(--theme-primary,#5b21b6);background:linear-gradient(120deg,var(--theme-primary,#5b21b6) 0%,color-mix(in srgb,var(--theme-primary,#5b21b6) 88%,#fff) 100%)}
        .checkout-cart-box{margin-bottom:34px;padding:clamp(16px,3vw,26px);border:1px solid rgba(255,255,255,.28);border-radius:15px;background:rgba(255,255,255,.14)}
        .checkout-cart-box .table-responsive{margin:0}
        .checkout-cart-box .table{margin:0;color:#f5f6fb}
        .checkout-cart-box .table thead th{padding:13px 12px;border-bottom-color:rgba(255,255,255,.22);color:#d5d9e8;font-size:.74rem;letter-spacing:.08em;text-transform:uppercase}
        .checkout-cart-box .table td,.checkout-cart-box .table tfoot th{padding:14px 12px;border-color:rgba(255,255,255,.16);color:#f5f6fb}
        .checkout-cart-box #cart-empty{padding:18px 20px!important;border:1px dashed #cbd6e5;border-radius:11px;background:#fff;color:#65748b!important;text-align:center}
        .checkout-details-heading{margin:0 0 22px;text-align:center}
        .checkout-details-heading .checkout-kicker{margin-bottom:10px}
        .checkout-details-heading h3{margin:0;color:#fff;font-family:Georgia,"Times New Roman",serif;font-size:clamp(1.65rem,3vw,2.1rem);font-weight:500}
        .checkout-details-heading p{margin:8px 0 0;color:#d0d4e4;font-size:.92rem}
        .checkout-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .checkout-field{display:flex;min-width:0;flex-direction:column;gap:8px;color:#f3f2fa;font-size:.88rem;font-weight:750}
        .checkout-field--wide{grid-column:1/-1}
        .checkout .form-control,.checkout .form-select{min-height:50px;border:1px solid #d5deeb;border-radius:10px;background-color:#fff;box-shadow:0 2px 6px rgba(20,40,70,.025);color:#26364d;font-weight:500;transition:border-color .18s ease,box-shadow .18s ease}
        .checkout textarea.form-control{min-height:104px;resize:vertical}
        .checkout .form-control:focus,.checkout .form-select:focus{border-color:var(--theme-primary,var(--blue));box-shadow:0 0 0 4px var(--theme-wash,rgba(20,99,214,.12))}
        .checkout-actions{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:24px;margin-top:26px;padding-top:22px;border-top:1px solid rgba(255,255,255,.2)}
        .checkout-actions-copy{display:grid;gap:8px}
        .checkout-actions-copy small{color:#d0d4e4;line-height:1.55}
        .checkout-buttons{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}
        .checkout-buttons .btn{min-height:48px;padding:12px 17px;border-radius:10px;font-weight:750}
        .btn-order{background:var(--theme-primary,var(--red));border:1px solid var(--theme-primary,var(--red));border-radius:10px;padding:13px 20px;color:#fff;font-weight:800;letter-spacing:.01em;box-shadow:0 8px 18px var(--theme-wash,rgba(20,99,214,.16))}
        .btn-order:hover{background:var(--theme-deep,var(--navy));border-color:var(--theme-deep,var(--navy));color:#fff}
        @media(max-width:760px){.checkout-actions{grid-template-columns:1fr}.checkout-buttons{justify-content:stretch}.checkout-buttons .btn,.checkout-buttons .btn-order{flex:1}}
        @media(max-width:576px){.checkout{margin-top:30px;border-radius:15px}.checkout-heading{padding:30px 18px 24px}.checkout-content{padding:18px 14px}.checkout-fields{grid-template-columns:1fr;gap:15px}.checkout-field--wide{grid-column:auto}.checkout-cart-box{margin-bottom:26px;padding:12px;border-radius:12px}.checkout-cart-box .table-responsive{font-size:.84rem}.checkout-actions{gap:16px}.checkout-buttons{flex-direction:column}.checkout-buttons .btn,.checkout-buttons .btn-order{width:100%}}
        .store-footer{background:var(--navy);color:#d9e5f8;padding:32px 24px}
        .store-footer-inner{align-items:center}
        .store-footer-inner strong{color:#fff}
        .alert{border-radius:3px}
        @keyframes storefront-rise{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:760px){.product{grid-template-columns:minmax(220px,36%) minmax(0,1fr)}.product-media{min-height:280px}.product-purchase{align-items:flex-start;flex-direction:column}.quantity{width:100%;justify-content:flex-start}}
        @media(max-width:576px){.store-nav-inner{align-items:center;flex-direction:row;flex-wrap:wrap;gap:10px}.brand{max-width:43%;min-width:0;font-size:.98rem}.brand span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.brand img{width:36px;height:36px}.gallery-nav-links{gap:7px;margin-left:auto}.gallery-nav-links a{font-size:.7rem}.gallery-nav-links a[aria-current=page],.gallery-nav-about{padding:7px}.store-search{width:100%;order:3}.store-search input{min-width:0}.store-search button{padding-inline:.8rem}.store-head{padding:54px 18px}.store-main{padding:34px 16px 52px}.product-grid{grid-template-columns:1fr;gap:16px}.product{grid-template-columns:1fr}.product-media{min-height:0;aspect-ratio:16/10}.product-body{gap:12px;padding:20px}.product-purchase{align-items:stretch}.quantity{justify-content:space-between}.quantity .add-to-cart{flex:1}.catalogue-heading{align-items:flex-start;flex-direction:column;gap:8px}.catalogue-count{white-space:normal}.store-category-nav{align-items:stretch;flex-direction:column}.store-category-nav select{width:100%}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation:none!important;transition:none!important}}
    </style>
    <link rel="stylesheet" href="{{ asset('css/storefront-theme.css') }}?v={{ filemtime(public_path('css/storefront-theme.css')) }}">
    @include('layout.partials.footer-styles')
    @include('layout.partials.contrast-adaptation')
</head>
<body data-store-theme="{{ $store->store_theme ?: 'classic' }}">
@php
    $customDomain = request()->routeIs('storefront.custom-domain*');
    $homeUrl = $customDomain ? route('storefront.custom-domain') : route('storefront.catalogue', $store->slug);
    $galleryUrl = $customDomain ? route('storefront.custom-domain.products') : route('storefront.products', $store->slug);
    $aboutUrl = $customDomain ? route('storefront.custom-domain.about') : route('storefront.about', $store->slug);
@endphp
<nav class="store-nav"><div class="store-nav-inner"><a class="brand" href="{{ $homeUrl }}"><img src="{{ $company->logo ? asset($company->logo) : asset('assets/img/logos.png') }}" alt=""><span>{{ $store->store_name }}</span></a><div class="store-menu">@include('storefront.partials.mobile-menu-button')<div class="gallery-nav-links" id="store-navigation"><a href="{{ $homeUrl }}">Home</a><a href="{{ $galleryUrl }}" aria-current="page">Product Gallery</a><a class="gallery-nav-about" href="{{ $aboutUrl }}">About Us</a><a href="#store-cart" class="btn btn-sm btn-outline-primary">Cart <span class="badge bg-primary" id="cart-count">0</span></a></div></div><form class="store-search" method="GET">@if($selectedCategoryId)<input type="hidden" name="category_id" value="{{ $selectedCategoryId }}">@endif<input class="form-control" name="q" value="{{ $search }}" placeholder="Search products"><button class="btn btn-primary" title="Search" aria-label="Search"><i class="fas fa-search"></i></button></form></div></nav>
@include('storefront.partials.mobile-menu-script')
<header class="store-head"><div class="store-head-inner"><span class="store-eyebrow">The official online store</span><h1>{{ $store->store_name }}</h1><p>{{ $store->description ?: 'Explore our available collection and send an order request directly to our team.' }}</p></div></header>
<main class="store-main">
    @if(session('store_order_success'))
        <div class="alert alert-success">
            @if(session('store_order_duplicate'))
                <strong>This order was already received.</strong> No duplicate was created. Your reference is {{ session('store_order_success') }}.
            @else
                <strong>Order received.</strong> Your reference is {{ session('store_order_success') }}. The business will contact you to confirm payment and fulfilment.
            @endif
            @if(session('store_whatsapp_link'))
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a class="btn btn-success" href="{{ session('store_whatsapp_link') }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp me-2"></i>Send order on WhatsApp</a>
                </div>
            @endif
        </div>
    @endif
    @if($errors->any())<div class="alert alert-danger"><strong>Your order needs attention.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @php
        $orderAction = $customDomain
            ? route('storefront.custom-domain.orders.store')
            : route('storefront.orders.store', $store->slug);
    @endphp
    @if($categories->isNotEmpty())
        <div class="store-category-dropdown">
            <details>
                <summary>{{ $selectedCategory?->name ?? 'All categories' }}</summary>
                <div class="store-category-dropdown-options" aria-label="All product categories">
                    <a href="{{ $galleryUrl }}@if($search !== '')?{{ http_build_query(['q' => $search]) }}@endif">
                        <span>All categories</span>
                    </a>
                    @foreach($categories as $category)
                        <a href="{{ $galleryUrl }}?{{ http_build_query(['category_id' => $category->id]) }}" @if($selectedCategoryId === (int) $category->id) aria-current="page" @endif>
                            <span>{{ $category->name }}</span>
                            <span class="store-category-count">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('item', (int) $category->products_count) }}</span>
                        </a>
                    @endforeach
                </div>
            </details>
        </div>
    @endif
    <div class="catalogue-heading"><div><h2>{{ $selectedCategory ? $selectedCategory->name : 'Available collection' }}</h2><p>Add products to your cart, review quantities and total, then submit your order. Only available products are shown.</p></div><span class="catalogue-count">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</span></div>
    <div class="product-grid">
        @forelse($products as $product)
            @php
                $available = (float) ($product->storefront_available_stock ?? max((float) ($product->stock ?? 0), (float) ($product->stock_quantity ?? 0)));
                $variants = $product->storefront_variants ?? collect([$product]);
                $hasSizeOptions = $variants->count() > 1;
                $displayName = trim($product->name . ($product->variantLabel() !== '' ? ' · ' . $product->variantLabel() : ''));
                $quantityStep = in_array(strtolower($product->stockUnitSymbol()), ['kg','g','litre','liter','l','m']) ? 0.01 : 1;
                $hasBrand = $variants->contains(fn ($variant) => filled($variant->brand));
                $hasSize = $variants->contains(fn ($variant) => filled($variant->fashion_size));
                $hasColor = $variants->contains(fn ($variant) => filled($variant->fashion_color));
                $availableSizes = $variants->pluck('fashion_size')->filter()->unique()->values();
            @endphp
            <article class="product" data-product-id="{{ $product->id }}" data-product-name="{{ $displayName }}" data-product-sku="{{ $product->sku }}" data-product-price="{{ (float) $product->price }}" data-product-max="{{ $available }}" data-product-step="{{ $quantityStep }}" data-product-stock-label="{{ $available > 0 ? $product->formatStockQuantity($available) . ' available' : 'Out of stock' }}" data-product-brand="{{ $product->brand }}" data-product-size="{{ $product->fashion_size }}" data-product-color="{{ $product->fashion_color }}">
                <div class="product-media">@if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $displayName }}" loading="lazy">@else<i class="fas fa-box-open" aria-hidden="true"></i>@endif</div>
                <div class="product-body">
                    <span class="product-eyebrow">Product details</span>
                    <h2>{{ $product->name }}</h2>
                    @if(filled($product->description))
                        <p class="product-description">{{ strip_tags($product->description) }}</p>
                    @endif
                    <div class="product-options-row">
                        @if($hasSizeOptions)
                            <div class="product-size-picker">
                                <label for="variant{{ $product->id }}">{{ $variants->contains(fn ($variant) => filled($variant->fashion_size)) ? 'Choose size' : 'Choose option' }}</label>
                                <select class="form-select product-variant-select" id="variant{{ $product->id }}" aria-label="Choose a size for {{ $product->name }}">
                                    @foreach($variants as $variant)
                                        @php
                                            $variantStock = (float) ($variant->storefront_available_stock ?? max((float) ($variant->stock ?? 0), (float) ($variant->stock_quantity ?? 0)));
                                            $variantName = trim($variant->name . ($variant->variantLabel() !== '' ? ' · ' . $variant->variantLabel() : ''));
                                            $variantOptionLabel = collect([
                                                filled($variant->fashion_size) ? 'Size ' . $variant->fashion_size : null,
                                                $variant->fashion_color,
                                            ])->filter()->implode(' · ');
                                            $variantStep = in_array(strtolower($variant->stockUnitSymbol()), ['kg','g','litre','liter','l','m']) ? 0.01 : 1;
                                        @endphp
                                        <option
                                            value="{{ $variant->id }}"
                                            data-product-name="{{ $variantName }}"
                                            data-product-sku="{{ $variant->sku }}"
                                            data-product-price="{{ (float) $variant->price }}"
                                            data-product-max="{{ $variantStock }}"
                                            data-product-step="{{ $variantStep }}"
                                            data-product-stock-label="{{ $variantStock > 0 ? $variant->formatStockQuantity($variantStock) . ' available' : 'Out of stock' }}"
                                            data-product-brand="{{ $variant->brand }}"
                                            data-product-size="{{ $variant->fashion_size }}"
                                            data-product-color="{{ $variant->fashion_color }}"
                                            @selected((int) $variant->id === (int) $product->id)
                                        >{{ $variantOptionLabel ?: 'Standard option' }}{{ $variantStock <= 0 ? ' - Out of stock' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="product-meta">
                            @if($hasBrand)<span data-product-meta="brand"><i class="fas fa-tag" aria-hidden="true"></i>Brand: {{ $product->brand }}</span>@endif
                            @if($hasSize)<span data-product-meta="size"><i class="fas fa-ruler" aria-hidden="true"></i>Size: {{ $product->fashion_size }}</span>@endif
                            @if($hasColor)<span data-product-meta="color"><i class="fas fa-palette" aria-hidden="true"></i>Colour: {{ $product->fashion_color }}</span>@endif
                        </div>
                    </div>
                    @if($hasSizeOptions && $availableSizes->isNotEmpty())
                        <div class="product-available-sizes" aria-label="Available sizes">
                            <span class="product-available-sizes-label">Available sizes:</span>
                            @foreach($availableSizes as $size)
                                <span data-available-size>{{ $size }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="product-purchase">
                        <div>
                            <div class="product-price-label">Price</div>
                            <div class="price product-price">{{ \App\Support\GeoCurrency::format((float)$product->price, $company->currency_code ?: 'NGN', $company->currency_code ?: 'NGN') }}</div>
                            <div class="stock product-stock {{ $available > 0 ? 'in-stock' : 'out-of-stock' }}">{{ $available > 0 ? $product->formatStockQuantity($available) . ' available' : 'Out of stock' }}</div>
                        </div>
                        <div class="quantity">
                            <label for="qty{{ $product->id }}" class="small fw-bold">Quantity</label>
                            <input id="qty{{ $product->id }}" class="form-control product-quantity" type="number" min="{{ $quantityStep }}" step="{{ $quantityStep }}" max="{{ $available }}" value="1" @disabled($available <= 0)>
                            <button type="button" class="btn btn-outline-primary add-to-cart" aria-label="Add {{ $displayName }} to cart" @disabled($available <= 0)><i class="fas fa-cart-plus me-1" aria-hidden="true"></i>Add to cart</button>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="alert alert-info grid-column-all">No products are currently available.</div>
        @endforelse
    </div>
        <form method="POST" action="{{ $orderAction }}" id="store-order-form">
            @csrf
            <input type="hidden" name="order_token" value="{{ $orderToken }}">
            <section class="checkout" id="store-cart">
                <header class="checkout-heading">
                    <span class="checkout-kicker">Simple, secure ordering</span>
                    <h2>Your Cart</h2>
                    <p>Review your items, add your contact details, and send your order in one simple step.</p>
                </header>
                <div class="checkout-content">
                    <div class="checkout-cart-box">
                        <div id="cart-empty" class="text-muted py-2">Your cart is empty. Add products from the collection above.</div>
                        <p id="cart-storage-warning" role="status" hidden></p>
                        <div class="table-responsive"><table class="table align-middle" id="cart-table" hidden><thead><tr><th>Product</th><th>Unit price</th><th style="width:130px">Quantity</th><th>Subtotal</th><th></th></tr></thead><tbody id="cart-items"></tbody><tfoot><tr><th colspan="3" class="text-end">Estimated total</th><th id="cart-total"></th><th></th></tr></tfoot></table></div>
                    </div>
                    <div id="cart-hidden-items"></div>
                    <div class="checkout-details-heading">
                        <span class="checkout-kicker">Almost there</span>
                        <h3>Order Details</h3>
                        <p>Let us know how to reach you and where to send your order.</p>
                    </div>
                    <div class="checkout-fields">
                        <label class="checkout-field">Full Name<input class="form-control" name="customer_name" value="{{ old('customer_name') }}" placeholder="Enter full name (optional)" autocomplete="name"></label>
                        <label class="checkout-field">Phone *<input class="form-control" name="customer_phone" value="{{ old('customer_phone') }}" type="tel" autocomplete="tel" required></label>
                        <label class="checkout-field">Payment / Collection<select class="form-select" name="payment_method"><option value="pay_on_delivery" @selected(old('payment_method', 'pay_on_delivery') === 'pay_on_delivery')>Pay on delivery</option><option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank transfer after confirmation</option><option value="pickup" @selected(old('payment_method') === 'pickup')>Pay on pickup</option></select></label>
                        <label class="checkout-field checkout-field--wide">Delivery Address<textarea class="form-control" name="delivery_address" rows="3" autocomplete="street-address">{{ old('delivery_address') }}</textarea></label>
                        <label class="checkout-field checkout-field--wide">Order Note<textarea class="form-control" name="customer_note" rows="3">{{ old('customer_note') }}</textarea></label>
                    </div>
                    <div class="checkout-actions">
                        <div class="checkout-actions-copy">
                            <small>{{ $store->fulfilment_note ?: 'Phone number is the only required detail. The business will confirm availability, payment, and delivery.' }}</small>
                            @if($orderContact['whatsapp'])
                                <small>WhatsApp requests do not reserve stock. Place an order to reserve the items in the store.</small>
                            @endif
                        </div>
                        <div class="checkout-buttons">
                            @if($orderContact['whatsapp'])
                                <button class="btn btn-success" id="send-cart-whatsapp" type="button" disabled><i class="fab fa-whatsapp me-2"></i>Send purchase request to WhatsApp</button>
                            @endif
                            <button class="btn-order" id="place-store-order" type="submit" disabled><i class="fas fa-paper-plane me-2"></i>Place Order &amp; Reserve Stock</button>
                        </div>
                    </div>
                </div>
            </section>
        </form>
    <div class="mt-4">{{ $products->links() }}</div>
</main>
@include('storefront.partials.footer')
<script>
    (() => {
        const key = 'smartprobook-cart-{{ $store->slug }}';
        const cart = new Map();
        const rows = document.getElementById('cart-items');
        const hiddenItems = document.getElementById('cart-hidden-items');
        const table = document.getElementById('cart-table');
        const empty = document.getElementById('cart-empty');
        const submit = document.getElementById('place-store-order');
        const orderForm = document.getElementById('store-order-form');
        const whatsappButton = document.getElementById('send-cart-whatsapp');
        const currency = @json($company->currency_code ?: 'NGN');
        const whatsappNumber = @json($orderContact['whatsapp']);
        const storeName = @json($store->store_name);
        const storageWarning = document.getElementById('cart-storage-warning');
        function warnStorage(error) {
            console.warn('Store cart could not access browser storage.', error);
            storageWarning.textContent = 'Your cart works on this page, but cannot be saved on this device. It may be lost if you leave or refresh.';
            storageWarning.hidden = false;
        }
        orderForm?.addEventListener('submit', () => {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting order...';
        });
        const products = new Map();
        function productData(element) {
            return {
                id: element.dataset.productId || element.value,
                name: element.dataset.productName || '',
                sku: element.dataset.productSku || '',
                price: Number(element.dataset.productPrice) || 0,
                max: Number(element.dataset.productMax) || 0,
                step: Number(element.dataset.productStep) || 1,
                stockLabel: element.dataset.productStockLabel || '',
                brand: element.dataset.productBrand || '',
                size: element.dataset.productSize || '',
                color: element.dataset.productColor || ''
            };
        }
        document.querySelectorAll('[data-product-id]').forEach((article) => {
            const product = productData(article);
            products.set(String(product.id), product);
            article.querySelectorAll('.product-variant-select option').forEach((option) => {
                const variant = productData(option);
                products.set(String(variant.id), variant);
            });
        });
        const formatMoney = (value) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value);

        function updateVariantCard(article, product) {
            article.dataset.productId = product.id;
            article.dataset.productName = product.name;
            article.dataset.productSku = product.sku;
            article.dataset.productPrice = String(product.price);
            article.dataset.productMax = String(product.max);
            article.dataset.productStep = String(product.step);
            article.dataset.productStockLabel = product.stockLabel;
            article.dataset.productBrand = product.brand;
            article.dataset.productSize = product.size;
            article.dataset.productColor = product.color;

            const price = article.querySelector('.product-price');
            const stock = article.querySelector('.product-stock');
            const quantity = article.querySelector('.product-quantity');
            const addButton = article.querySelector('.add-to-cart');
            if (price) price.textContent = formatMoney(product.price);
            if (stock) {
                stock.textContent = product.stockLabel;
                stock.classList.toggle('in-stock', product.max > 0);
                stock.classList.toggle('out-of-stock', product.max <= 0);
            }
            if (quantity) {
                quantity.min = String(product.step);
                quantity.step = String(product.step);
                quantity.max = String(product.max);
                quantity.value = String(Math.min(product.max || product.step, Math.max(product.step, Number(quantity.value) || product.step)));
                quantity.disabled = product.max <= 0;
            }
            if (addButton) {
                addButton.disabled = product.max <= 0;
                addButton.setAttribute('aria-label', `Add ${product.name} to cart`);
            }
            article.querySelectorAll('[data-product-meta]').forEach((meta) => {
                const labels = {
                    brand: product.brand ? `Brand: ${product.brand}` : '',
                    size: product.size ? `Size: ${product.size}` : '',
                    color: product.color ? `Colour: ${product.color}` : ''
                };
                const label = labels[meta.dataset.productMeta] || '';
                const icon = meta.querySelector('i');
                meta.hidden = !label;
                meta.replaceChildren(...(icon ? [icon, document.createTextNode(` ${label}`)] : [document.createTextNode(label)]));
            });
        }

        document.querySelectorAll('.product-variant-select').forEach((select) => {
            const article = select.closest('[data-product-id]');
            const initialProduct = products.get(String(select.value));
            if (article && initialProduct) updateVariantCard(article, initialProduct);
            select.addEventListener('change', () => {
                const product = products.get(String(select.value));
                if (article && product) updateVariantCard(article, product);
            });
        });

        try {
            const saved = JSON.parse(localStorage.getItem(key) || '[]');
            saved.forEach((item) => {
                const current = products.get(String(item.id));
                if (current) item = { ...item, ...current };
                item.step = Number(item.step) > 0 ? Number(item.step) : 1;
                if (item.id && Number(item.quantity) > 0 && Number(item.max) > 0) {
                    item.quantity = Math.min(Number(item.quantity), Number(item.max));
                    cart.set(String(item.id), item);
                }
            });
        } catch (error) {
            warnStorage(error);
        }

        function renderCart() {
            rows.replaceChildren();
            hiddenItems.replaceChildren();
            let total = 0;
            let count = 0;
            cart.forEach((item, id) => {
                const lineTotal = item.price * item.quantity;
                total += lineTotal;
                count += item.quantity;
                const row = document.createElement('tr');
                row.innerHTML = '<td><strong></strong><div class="small text-muted"></div></td><td></td><td><input type="number" class="form-control form-control-sm cart-quantity" min="1" step="1"></td><td></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-cart-item" aria-label="Remove item"><i class="fas fa-trash"></i></button></td>';
                row.querySelector('strong').textContent = item.name;
                row.querySelector('.small').textContent = item.sku || '';
                row.children[1].textContent = formatMoney(item.price);
                const quantityInput = row.querySelector('.cart-quantity');
                quantityInput.setAttribute('aria-label', `Quantity for ${item.name}`);
                quantityInput.min = item.step;
                quantityInput.step = item.step;
                quantityInput.max = item.max;
                quantityInput.value = item.quantity;
                row.children[3].textContent = formatMoney(lineTotal);
                quantityInput.addEventListener('change', () => {
                    const quantity = Math.min(item.max, Math.max(item.step, Number(quantityInput.value) || item.step));
                    item.quantity = quantity;
                    cart.set(id, item);
                    persistAndRender();
                });
                row.querySelector('.remove-cart-item').addEventListener('click', () => {
                    cart.delete(id);
                    persistAndRender();
                });
                rows.append(row);
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `items[${id}]`;
                hidden.value = String(item.quantity);
                hiddenItems.append(hidden);
            });
            table.hidden = cart.size === 0;
            empty.hidden = cart.size > 0;
            submit.disabled = cart.size === 0;
            if (whatsappButton) whatsappButton.disabled = cart.size === 0;
            document.getElementById('cart-total').textContent = formatMoney(total);
            document.getElementById('cart-count').textContent = String(count);
        }

        function persistAndRender() {
            try {
                localStorage.setItem(key, JSON.stringify(Array.from(cart.values())));
            } catch (error) {
                warnStorage(error);
            }
            renderCart();
        }

        document.querySelectorAll('.add-to-cart').forEach((button) => {
            button.addEventListener('click', () => {
                const article = button.closest('[data-product-id]');
                const item = products.get(article.dataset.productId);
                const quantity = Math.max(item.step, Number(article.querySelector('.product-quantity').value) || item.step);
                const existing = cart.get(item.id);
                item.quantity = Math.min(item.max, (existing?.quantity || 0) + quantity);
                cart.set(item.id, item);
                persistAndRender();
                document.getElementById('store-cart').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        function purchaseRequestMessage() {
            const customerName = document.querySelector('[name="customer_name"]');
            const customerPhone = document.querySelector('[name="customer_phone"]');
            customerName.value = customerName.value.trim();
            customerPhone.value = customerPhone.value.trim();
            if (!customerPhone.reportValidity()) return null;

            const total = Array.from(cart.values()).reduce((sum, item) => sum + item.price * item.quantity, 0);
            const itemLines = Array.from(cart.values()).map((item) =>
                `- ${item.name} (${item.sku || 'no SKU'}) x ${item.quantity} = ${formatMoney(item.price * item.quantity)}`
            ).join('\n');
            const lines = [
                `Hello ${storeName}, I would like to purchase these items:`,
                itemLines,
                `Estimated total: ${formatMoney(total)}`,
                `Phone: ${customerPhone.value}`,
                'Please confirm current stock, final price and fulfilment. I understand this request does not reserve stock.'
            ];
            if (customerName.value) lines.splice(3, 0, `Name: ${customerName.value}`);
            return lines.join('\n\n');
        }

        if (whatsappButton) {
            whatsappButton.addEventListener('click', () => {
                const message = purchaseRequestMessage();
                if (message) window.open(`https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`, '_blank', 'noopener,noreferrer');
            });
        }

        @if(session('store_order_success'))
            try {
                localStorage.removeItem(key);
            } catch (error) {
                warnStorage(error);
            }
            cart.clear();
        @endif
        renderCart();
    })();

    if('serviceWorker' in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('/pwa-sw.js').catch(()=>{}));}
</script>
@include('storefront.partials.enquiry-chat')
</body></html>
