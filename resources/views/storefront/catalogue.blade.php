<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        .product-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
        .product{border:1px solid var(--line);border-radius:3px;background:#fff;transition:transform .2s ease,box-shadow .2s ease}
        .product:hover{transform:translateY(-4px);box-shadow:0 18px 42px rgba(6,35,84,.1)}
        .product-media{aspect-ratio:1/1;background:#edf3fb}
        .product-media img{transition:transform .45s ease}
        .product:hover .product-media img{transform:scale(1.035)}
        .product-media i{color:var(--blue)}
        .product-body{gap:10px;padding:20px}
        .product h2{font-family:Georgia,"Times New Roman",serif;font-size:1.3rem;font-weight:500;line-height:1.25}
        .product-description{margin:0;color:#64748b;font-size:.9rem;line-height:1.55}
        .price{color:var(--red);font-size:1.15rem}
        .stock{color:#536984}
        .quantity{justify-content:space-between;margin-top:10px;padding-top:14px;border-top:1px solid #edf0f5}
        .quantity input{width:92px;border-color:#dbe4f0}
        .checkout{margin-top:38px;padding:clamp(22px,4vw,38px);border:1px solid var(--line);border-top:3px solid var(--red);border-radius:3px;background:#fff}
        .checkout h2{color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:1.8rem;font-weight:500}
        .checkout .form-control,.checkout .form-select{min-height:46px;border-color:var(--line);border-radius:3px}
        .checkout textarea.form-control{min-height:auto}
        .btn-order{background:var(--red);border:1px solid var(--red);border-radius:3px;padding:14px 24px;letter-spacing:.03em}
        .btn-order:hover{background:var(--navy);border-color:var(--navy);color:#fff}
        .store-footer{background:var(--navy);color:#d9e5f8;padding:32px 24px}
        .store-footer-inner{align-items:center}
        .store-footer-inner strong{color:#fff}
        .alert{border-radius:3px}
        @media(max-width:992px){.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:576px){.store-nav-inner{align-items:center;flex-direction:row;flex-wrap:wrap;gap:10px}.brand{font-size:.98rem}.brand img{width:36px;height:36px}.gallery-nav-links{gap:8px;margin-left:auto}.gallery-nav-links a{font-size:.75rem}.gallery-nav-links a[aria-current=page]{padding:8px}.store-search{width:100%;order:3}.store-search input{min-width:0}.store-search button{padding-inline:.8rem}.store-head{padding:54px 18px}.store-main{padding:34px 16px 52px}.product-grid{grid-template-columns:1fr;gap:16px}.catalogue-heading{align-items:flex-start;flex-direction:column;gap:8px}.catalogue-count{white-space:normal}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body>
@php
    $customDomain = request()->routeIs('storefront.custom-domain*');
    $homeUrl = $customDomain ? route('storefront.custom-domain') : route('storefront.catalogue', $store->slug);
    $galleryUrl = $customDomain ? route('storefront.custom-domain.products') : route('storefront.products', $store->slug);
@endphp
<nav class="store-nav"><div class="store-nav-inner"><a class="brand" href="{{ $homeUrl }}"><img src="{{ $company->logo ? asset($company->logo) : asset('assets/img/logos.png') }}" alt=""><span>{{ $store->store_name }}</span></a><div class="gallery-nav-links"><a href="{{ $homeUrl }}">Home</a><a href="{{ $galleryUrl }}" aria-current="page">Product Gallery</a></div><form class="store-search" method="GET"><input class="form-control" name="q" value="{{ $search }}" placeholder="Search products"><button class="btn btn-primary" title="Search" aria-label="Search"><i class="fas fa-search"></i></button></form></div></nav>
<header class="store-head"><div class="store-head-inner"><span class="store-eyebrow">The official online store</span><h1>{{ $store->store_name }}</h1><p>{{ $store->description ?: 'Explore our available collection and send an order request directly to our team.' }}</p></div></header>
<main class="store-main">
    @if(session('store_order_success'))<div class="alert alert-success"><strong>Order received.</strong> Your reference is {{ session('store_order_success') }}. The business will contact you to confirm payment and fulfilment.</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Your order needs attention.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @php($orderAction = $customDomain ? route('storefront.custom-domain.orders.store') : route('storefront.orders.store', $store->slug))
    <div class="catalogue-heading"><div><h2>Available collection</h2><p>Choose in-stock items and quantities to request. Out-of-stock products are shown for reference.</p></div><span class="catalogue-count">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</span></div>
    <form method="POST" action="{{ $orderAction }}">@csrf
        <div class="product-grid">
            @forelse($products as $product)
                @php($available = max((float)($product->stock ?? 0), (float)($product->stock_quantity ?? 0)))
                <article class="product"><div class="product-media">@if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">@else<i class="fas fa-box-open" aria-hidden="true"></i>@endif</div><div class="product-body"><h2>{{ $product->name }}</h2><div class="small text-muted">{{ $product->sku }}</div>@if(filled($product->description))<p class="product-description">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 150) }}</p>@endif<div class="price">{{ \App\Support\GeoCurrency::format((float)$product->price, $company->currency_code ?: 'NGN', $company->currency_code ?: 'NGN') }}</div><div class="stock">{{ $available > 0 ? $product->formatStockQuantity($available) . ' available' : 'Out of stock' }}</div><div class="quantity"><label for="qty{{ $product->id }}" class="small fw-bold">Quantity</label><input id="qty{{ $product->id }}" class="form-control form-control-sm" type="number" name="items[{{ $product->id }}]" min="0" max="{{ $available }}" step="{{ in_array(strtolower($product->stockUnitSymbol()), ['kg','g','litre','liter','l','m']) ? '0.01' : '1' }}" value="0" @disabled($available <= 0)></div></div></article>
            @empty
                <div class="alert alert-info grid-column-all">No products are currently available.</div>
            @endforelse
        </div>
        @if($products->count())
        <section class="checkout"><h2>Order Details</h2><p class="text-muted">Select quantities above, then provide the contact details for confirmation.</p><div class="row g-3"><div class="col-md-6"><label class="form-label">Full Name *</label><input class="form-control" name="customer_name" value="{{ old('customer_name') }}" required></div><div class="col-md-6"><label class="form-label">Phone *</label><input class="form-control" name="customer_phone" value="{{ old('customer_phone') }}" required></div><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="customer_email" value="{{ old('customer_email') }}"></div><div class="col-md-6"><label class="form-label">Payment / Collection *</label><select class="form-select" name="payment_method" required><option value="pay_on_delivery">Pay on delivery</option><option value="bank_transfer">Bank transfer after confirmation</option><option value="pickup">Pay on pickup</option></select></div><div class="col-12"><label class="form-label">Delivery Address</label><textarea class="form-control" name="delivery_address" rows="2">{{ old('delivery_address') }}</textarea></div><div class="col-12"><label class="form-label">Order Note</label><textarea class="form-control" name="customer_note" rows="2">{{ old('customer_note') }}</textarea></div><div class="col-12 d-flex justify-content-between align-items-center gap-3 flex-wrap"><small class="text-muted">{{ $store->fulfilment_note ?: 'The business will confirm availability, payment, and delivery.' }}</small><button class="btn-order" type="submit"><i class="fas fa-paper-plane me-2"></i>Place Order</button></div></div></section>
        @endif
    </form>
    <div class="mt-4">{{ $products->links() }}</div>
</main>
<footer class="store-footer"><div class="store-footer-inner"><strong>{{ $store->store_name }}</strong><span>{{ $store->contact_phone }}{{ $store->contact_phone && $store->contact_email ? ' | ' : '' }}{{ $store->contact_email }}</span><span>Powered by SmartProbook</span></div></footer>
<script>if('serviceWorker' in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('/pwa-sw.js').catch(()=>{}));}</script>
</body></html>
