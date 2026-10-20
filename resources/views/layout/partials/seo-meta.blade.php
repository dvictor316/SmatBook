@php
    $siteName = 'SmartProbook';
    $resolvedTitle = trim((string) ($seoTitle ?? $__env->yieldContent('title')));
    if ($resolvedTitle === '') {
        $resolvedTitle = $siteName;
    } elseif (stripos($resolvedTitle, $siteName) === false) {
        $resolvedTitle .= ' | ' . $siteName;
    }

    $resolvedDescription = trim((string) ($seoDescription ?? $__env->yieldContent('meta_description') ?? ''));
    if ($resolvedDescription === '') {
        $resolvedDescription = 'SmartProbook is a global AI-powered accounting and ERP platform for businesses, institutions, and deployment partners.';
    }

    $resolvedKeywords = trim((string) ($seoKeywords ?? $__env->yieldContent('meta_keywords') ?? ''));
    if ($resolvedKeywords === '') {
        $resolvedKeywords = 'SmartProbook, accounting software, ERP, bookkeeping, invoicing, payments, multi-currency, finance platform';
    }

    $resolvedType = trim((string) ($seoType ?? 'website'));
    $resolvedImage = trim((string) ($seoImage ?? asset('assets/img/logos.png')));
    $resolvedFavicon = asset('assets/img/log-favicon.png');
    $resolvedFaviconVersion = file_exists(public_path('assets/img/log-favicon.png')) ? filemtime(public_path('assets/img/log-favicon.png')) : null;
    if ($resolvedFaviconVersion) {
        $resolvedFavicon .= '?v=' . $resolvedFaviconVersion;
    }
    $resolvedUrl = url()->current();
    $resolvedCanonical = trim((string) ($seoCanonical ?? $resolvedUrl));

    if (\App\Support\SearchVisibility::isMarketingPage(request())) {
        $resolvedCanonical = \App\Support\SearchVisibility::canonical($resolvedCanonical);
    }
    $resolvedNoIndex = (bool) ($seoNoIndex ?? false) || !\App\Support\SearchVisibility::isIndexablePage(request());
    $robotsContent = $resolvedNoIndex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';

    $organizationJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => \App\Support\SearchVisibility::baseUrl() . '/',
        'logo' => asset('assets/img/logos.png'),
    ];

    $websiteJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => \App\Support\SearchVisibility::baseUrl() . '/',
    ];

    $softwareJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $siteName,
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'url' => \App\Support\SearchVisibility::baseUrl() . '/',
        'image' => $resolvedImage,
        'description' => $resolvedDescription,
    ];
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
<meta name="keywords" content="{{ $resolvedKeywords }}">
<meta name="robots" content="{{ $robotsContent }}">
<link rel="canonical" href="{{ $resolvedCanonical }}">
@if(config('app.google_site_verification'))
<meta name="google-site-verification" content="{{ config('app.google_site_verification') }}">
@endif
@if(config('app.bing_site_verification'))
<meta name="msvalidate.01" content="{{ config('app.bing_site_verification') }}">
@endif

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $resolvedType }}">
<meta property="og:title" content="{{ $resolvedTitle }}">
<meta property="og:description" content="{{ $resolvedDescription }}">
<meta property="og:url" content="{{ $resolvedCanonical }}">
<meta property="og:image" content="{{ $resolvedImage }}">
<meta property="og:image:alt" content="{{ $resolvedTitle }}">
<meta property="og:locale" content="{{ $seoLocale ?? 'en_NG' }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $resolvedTitle }}">
<meta name="twitter:description" content="{{ $resolvedDescription }}">
<meta name="twitter:image" content="{{ $resolvedImage }}">
<meta name="twitter:url" content="{{ $resolvedCanonical }}">
<link rel="icon" type="image/png" href="{{ $resolvedFavicon }}">
<link rel="shortcut icon" href="{{ $resolvedFavicon }}">

@unless($resolvedNoIndex)
<script type="application/ld+json">{!! json_encode($organizationJsonLd, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($websiteJsonLd, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@if(request()->routeIs('landing.index'))
<script type="application/ld+json">{!! json_encode($softwareJsonLd, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endunless
