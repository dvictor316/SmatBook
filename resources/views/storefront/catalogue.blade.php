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
        .product-body{gap:14px;padding:clamp(22px,3vw,38px)}
        .product-eyebrow{color:#8a6a17;font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .product h2{font-family:Georgia,"Times New Roman",serif;font-size:clamp(1.55rem,2.4vw,2.1rem);font-weight:500;line-height:1.15}
        .product-description{margin:0;color:#64748b;font-size:.95rem;line-height:1.7;white-space:pre-line}
        .product-meta{display:flex;flex-wrap:wrap;gap:8px}
        .product-meta span{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid #e7edf5;border-radius:999px;background:#f8fafc;color:#52627a;font-size:.78rem}
        .product-purchase{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-top:auto;padding-top:18px;border-top:1px solid #edf0f5}
        .product-price-label{margin-bottom:4px;color:#75849a;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
        .price{color:var(--red);font-size:clamp(1.35rem,2vw,1.8rem);line-height:1.15}
        .stock{margin-top:7px;color:#536984}
        .stock.in-stock{color:#16834b}
        .stock.out-of-stock{color:#b4233b}
        .quantity{justify-content:flex-end;gap:10px;margin:0;padding:0;border:0}
        .quantity input{width:88px;min-height:44px;border-color:#dbe4f0}
        .add-to-cart{min-height:44px;padding-inline:18px;border-radius:5px;font-weight:750}
        .checkout{margin-top:38px;padding:clamp(22px,4vw,38px);border:1px solid var(--line);border-top:3px solid var(--red);border-radius:3px;background:#fff;animation:storefront-rise .65s .12s both}
        .checkout h2{color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:1.8rem;font-weight:500}
        .checkout .form-control,.checkout .form-select{min-height:46px;border-color:var(--line);border-radius:3px}
        .checkout textarea.form-control{min-height:auto}
        .btn-order{background:var(--red);border:1px solid var(--red);border-radius:3px;padding:14px 24px;letter-spacing:.03em}
        .btn-order:hover{background:var(--navy);border-color:var(--navy);color:#fff}
        .store-footer{background:var(--navy);color:#d9e5f8;padding:32px 24px}
        .store-footer-inner{align-items:center}
        .store-footer-inner strong{color:#fff}
        .alert{border-radius:3px}
        @keyframes storefront-rise{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:760px){.product{grid-template-columns:minmax(220px,36%) minmax(0,1fr)}.product-media{min-height:280px}.product-purchase{align-items:flex-start;flex-direction:column}.quantity{width:100%;justify-content:flex-start}}
        @media(max-width:576px){.store-nav-inner{align-items:center;flex-direction:row;flex-wrap:wrap;gap:10px}.brand{max-width:43%;min-width:0;font-size:.98rem}.brand span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.brand img{width:36px;height:36px}.gallery-nav-links{gap:7px;margin-left:auto}.gallery-nav-links a{font-size:.7rem}.gallery-nav-links a[aria-current=page],.gallery-nav-about{padding:7px}.store-search{width:100%;order:3}.store-search input{min-width:0}.store-search button{padding-inline:.8rem}.store-head{padding:54px 18px}.store-main{padding:34px 16px 52px}.product-grid{grid-template-columns:1fr;gap:16px}.product{grid-template-columns:1fr}.product-media{min-height:0;aspect-ratio:16/10}.product-body{gap:12px;padding:20px}.product-purchase{align-items:stretch}.quantity{justify-content:space-between}.quantity .add-to-cart{flex:1}.catalogue-heading{align-items:flex-start;flex-direction:column;gap:8px}.catalogue-count{white-space:normal}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation:none!important;transition:none!important}}
    </style>
    <link rel="stylesheet" href="{{ asset('css/storefront-theme.css') }}?v={{ filemtime(public_path('css/storefront-theme.css')) }}">
</head>
<body data-store-theme="{{ $store->store_theme ?: 'classic' }}">
@php
    $customDomain = request()->routeIs('storefront.custom-domain*');
    $homeUrl = $customDomain ? route('storefront.custom-domain') : route('storefront.catalogue', $store->slug);
    $galleryUrl = $customDomain ? route('storefront.custom-domain.products') : route('storefront.products', $store->slug);
    $aboutUrl = $customDomain ? route('storefront.custom-domain.about') : route('storefront.about', $store->slug);
@endphp
<nav class="store-nav"><div class="store-nav-inner"><a class="brand" href="{{ $homeUrl }}"><img src="{{ $company->logo ? asset($company->logo) : asset('assets/img/logos.png') }}" alt=""><span>{{ $store->store_name }}</span></a><div class="gallery-nav-links"><a href="{{ $homeUrl }}">Home</a><a href="{{ $galleryUrl }}" aria-current="page">Product Gallery</a><a class="gallery-nav-about" href="{{ $aboutUrl }}">About Us</a><a href="#store-cart" class="btn btn-sm btn-outline-primary">Cart <span class="badge bg-primary" id="cart-count">0</span></a></div><form class="store-search" method="GET"><input class="form-control" name="q" value="{{ $search }}" placeholder="Search products"><button class="btn btn-primary" title="Search" aria-label="Search"><i class="fas fa-search"></i></button></form></div></nav>
<header class="store-head"><div class="store-head-inner"><span class="store-eyebrow">The official online store</span><h1>{{ $store->store_name }}</h1><p>{{ $store->description ?: 'Explore our available collection and send an order request directly to our team.' }}</p></div></header>
<main class="store-main">
    @if(session('store_order_success'))
        <div class="alert alert-success">
            <strong>Order received.</strong> Your reference is {{ session('store_order_success') }}. The business will contact you to confirm payment and fulfilment.
            @if(session('store_whatsapp_link') || session('store_sms_link') || session('store_email_link'))
                <div class="mt-3 d-flex flex-wrap gap-2">
                    @if(session('store_whatsapp_link'))<a class="btn btn-success" href="{{ session('store_whatsapp_link') }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp me-2"></i>Send order on WhatsApp</a>@endif
                    @if(session('store_sms_link'))<a class="btn btn-primary" href="{{ session('store_sms_link') }}"><i class="fas fa-comment-sms me-2"></i>Send order by SMS</a>@endif
                    @if(session('store_email_link'))<a class="btn btn-outline-primary" href="{{ session('store_email_link') }}"><i class="fas fa-envelope me-2"></i>Email order details</a>@endif
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
    <div class="catalogue-heading"><div><h2>Available collection</h2><p>Add products to your cart, review quantities and total, then submit your order. Out-of-stock products are shown for reference.</p></div><span class="catalogue-count">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</span></div>
    <div class="product-grid">
        @forelse($products as $product)
            @php
                $available = max((float)($product->stock ?? 0), (float)($product->stock_quantity ?? 0));
                $variantLabel = $product->variantLabel();
                $displayName = trim($product->name . ($variantLabel !== '' ? ' · ' . $variantLabel : ''));
                $quantityStep = in_array(strtolower($product->stockUnitSymbol()), ['kg','g','litre','liter','l','m']) ? 0.01 : 1;
            @endphp
            <article class="product" data-product-id="{{ $product->id }}" data-product-name="{{ $displayName }}" data-product-sku="{{ $product->sku }}" data-product-price="{{ (float) $product->price }}" data-product-max="{{ $available }}" data-product-step="{{ $quantityStep }}">
                <div class="product-media">@if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $displayName }}" loading="lazy">@else<i class="fas fa-box-open" aria-hidden="true"></i>@endif</div>
                <div class="product-body">
                    <span class="product-eyebrow">Product details</span>
                    <h2>{{ $displayName }}</h2>
                    @if(filled($product->description))
                        <p class="product-description">{{ strip_tags($product->description) }}</p>
                    @endif
                    <div class="product-meta">
                        @if(filled($product->sku))<span><i class="fas fa-barcode" aria-hidden="true"></i>SKU: {{ $product->sku }}</span>@endif
                        @if(filled($product->brand))<span><i class="fas fa-tag" aria-hidden="true"></i>{{ $product->brand }}</span>@endif
                        @if(filled($product->fashion_size))<span><i class="fas fa-ruler" aria-hidden="true"></i>Size: {{ $product->fashion_size }}</span>@endif
                        @if(filled($product->fashion_color))<span><i class="fas fa-palette" aria-hidden="true"></i>Colour: {{ $product->fashion_color }}</span>@endif
                    </div>
                    <div class="product-purchase">
                        <div>
                            <div class="product-price-label">Price</div>
                            <div class="price">{{ \App\Support\GeoCurrency::format((float)$product->price, $company->currency_code ?: 'NGN', $company->currency_code ?: 'NGN') }}</div>
                            <div class="stock {{ $available > 0 ? 'in-stock' : 'out-of-stock' }}">{{ $available > 0 ? $product->formatStockQuantity($available) . ' available' : 'Out of stock' }}</div>
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
    @if($products->count())
        <form method="POST" action="{{ $orderAction }}" id="store-order-form">
            @csrf
            <section class="checkout" id="store-cart">
                <h2>Your Cart</h2>
                <p class="text-muted">Review your items and total before providing contact details.</p>
                <div id="cart-empty" class="text-muted py-2">Your cart is empty. Add products from the collection above.</div>
                <div class="table-responsive"><table class="table align-middle" id="cart-table" hidden><thead><tr><th>Product</th><th>Unit price</th><th style="width:130px">Quantity</th><th>Subtotal</th><th></th></tr></thead><tbody id="cart-items"></tbody><tfoot><tr><th colspan="3" class="text-end">Estimated total</th><th id="cart-total"></th><th></th></tr></tfoot></table></div>
                <div id="cart-hidden-items"></div>
                <h3 class="mt-4">Order Details</h3>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Full Name *</label><input class="form-control" name="customer_name" value="{{ old('customer_name') }}" required></div>
                    <div class="col-md-6"><label class="form-label">Phone *</label><input class="form-control" name="customer_phone" value="{{ old('customer_phone') }}" required></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="customer_email" value="{{ old('customer_email') }}"></div>
                    <div class="col-md-6"><label class="form-label">Payment / Collection *</label><select class="form-select" name="payment_method" required><option value="pay_on_delivery">Pay on delivery</option><option value="bank_transfer">Bank transfer after confirmation</option><option value="pickup">Pay on pickup</option></select></div>
                    <div class="col-12"><label class="form-label">Delivery Address</label><textarea class="form-control" name="delivery_address" rows="2">{{ old('delivery_address') }}</textarea></div>
                    <div class="col-12"><label class="form-label">Order Note</label><textarea class="form-control" name="customer_note" rows="2">{{ old('customer_note') }}</textarea></div>
                    <div class="col-12 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <small class="text-muted">{{ $store->fulfilment_note ?: 'The business will confirm availability, payment, and delivery.' }}</small>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($orderContact['whatsapp'])
                                <button class="btn btn-success" id="send-cart-whatsapp" type="button" disabled><i class="fab fa-whatsapp me-2"></i>Send purchase request to WhatsApp</button>
                            @endif
                            @if($orderContact['sms'])
                                <button class="btn btn-outline-primary" id="send-cart-sms" type="button" disabled><i class="fas fa-comment-sms me-2"></i>Send purchase request by SMS</button>
                            @endif
                            @if($orderContact['email'])
                                <button class="btn btn-outline-primary" id="send-cart-email" type="button" disabled><i class="fas fa-envelope me-2"></i>Email purchase request</button>
                            @endif
                            <button class="btn-order" id="place-store-order" type="submit" disabled><i class="fas fa-paper-plane me-2"></i>Place Online Order</button>
                        </div>
                        @if($orderContact['whatsapp'] || $orderContact['sms'] || $orderContact['email'])
                            <small class="text-muted w-100">Send a request directly to the business, or place an online order to reserve stock. The business must confirm availability and fulfilment.</small>
                        @endif
                    </div>
                </div>
            </section>
        </form>
    @endif
    <div class="mt-4">{{ $products->links() }}</div>
</main>
<footer class="store-footer"><div class="store-footer-inner"><strong>{{ $store->store_name }}</strong><span>{{ $store->contact_phone }}{{ $store->contact_phone && $store->contact_email ? ' | ' : '' }}{{ $store->contact_email }}</span><span>Powered by SmartProbook</span></div></footer>
<script>
    (() => {
        const key = 'smartprobook-cart-{{ $store->slug }}';
        const cart = new Map();
        const rows = document.getElementById('cart-items');
        const hiddenItems = document.getElementById('cart-hidden-items');
        const table = document.getElementById('cart-table');
        const empty = document.getElementById('cart-empty');
        const submit = document.getElementById('place-store-order');
        const whatsappButton = document.getElementById('send-cart-whatsapp');
        const smsButton = document.getElementById('send-cart-sms');
        const emailButton = document.getElementById('send-cart-email');
        const currency = @json($company->currency_code ?: 'NGN');
        const whatsappNumber = @json($orderContact['whatsapp']);
        const smsNumber = @json($orderContact['sms']);
        const orderEmail = @json($orderContact['email']);
        const storeName = @json($store->store_name);
        const products = new Map(Array.from(document.querySelectorAll('[data-product-id]')).map((element) => [
            element.dataset.productId,
            {
                id: element.dataset.productId,
                name: element.dataset.productName,
                sku: element.dataset.productSku,
                price: Number(element.dataset.productPrice),
                max: Number(element.dataset.productMax),
                step: Number(element.dataset.productStep) || 1
            }
        ]));
        const formatMoney = (value) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value);

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
            localStorage.removeItem(key);
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
            if (smsButton) smsButton.disabled = cart.size === 0;
            if (emailButton) emailButton.disabled = cart.size === 0;
            document.getElementById('cart-total').textContent = formatMoney(total);
            document.getElementById('cart-count').textContent = String(count);
        }

        function persistAndRender() {
            localStorage.setItem(key, JSON.stringify(Array.from(cart.values())));
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
            if (!customerName.reportValidity() || !customerPhone.reportValidity()) return null;

            const total = Array.from(cart.values()).reduce((sum, item) => sum + item.price * item.quantity, 0);
            const itemLines = Array.from(cart.values()).map((item) =>
                `- ${item.name} (${item.sku || 'no SKU'}) x ${item.quantity} = ${formatMoney(item.price * item.quantity)}`
            ).join('\n');
            return [
                `Hello ${storeName}, I would like to purchase these items:`,
                itemLines,
                `Estimated total: ${formatMoney(total)}`,
                `Name: ${customerName.value}`,
                `Phone: ${customerPhone.value}`,
                'Please confirm current stock, final price and fulfilment. I understand this request does not reserve stock.'
            ].join('\n\n');
        }

        if (whatsappButton) {
            whatsappButton.addEventListener('click', () => {
                const message = purchaseRequestMessage();
                if (message) window.open(`https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`, '_blank', 'noopener,noreferrer');
            });
        }

        if (smsButton) {
            smsButton.addEventListener('click', () => {
                const message = purchaseRequestMessage();
                if (message) window.location.href = `sms:${smsNumber}?body=${encodeURIComponent(message)}`;
            });
        }

        if (emailButton) {
            emailButton.addEventListener('click', () => {
                const message = purchaseRequestMessage();
                if (message) {
                    window.location.href = `mailto:${orderEmail}?subject=${encodeURIComponent(`Purchase request for ${storeName}`)}&body=${encodeURIComponent(message)}`;
                }
            });
        }

        @if(session('store_order_success'))
            localStorage.removeItem(key);
            cart.clear();
        @endif
        renderCart();
    })();

    if('serviceWorker' in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('/pwa-sw.js').catch(()=>{}));}
</script>
</body></html>
