@php
    $siteName = config('app.name', 'Smatprobook');

    // User requested strict browser title branding.
    $resolvedTitle = $siteName;

    $resolvedDescription = trim((string) ($seoDescription ?? $__env->yieldContent('meta_description') ?? ''));
    if ($resolvedDescription === '') {
        $resolvedDescription = 'SmartProbook is a global AI-powered accounting and ERP platform for businesses, institutions, and deployment partners.';
    }

    $resolvedKeywords = trim((string) ($seoKeywords ?? $__env->yieldContent('meta_keywords') ?? ''));
    if ($resolvedKeywords === '') {
        $resolvedKeywords = 'SmartProbook, accounting software, ERP, bookkeeping, invoicing, payments, multi-currency, finance platform';
    }

    $resolvedType = trim((string) ($seoType ?? 'website'));
    $resolvedImage = trim((string) ($seoImage ?? asset('assets/img/logo-placeholder.svg')));
    $resolvedUrl = url()->current();

    $resolvedNoIndex = (bool) ($seoNoIndex ?? false);
    $robotsContent = $resolvedNoIndex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';

    $organizationJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => config('app.url'),
        'logo' => asset('assets/img/logo-placeholder.svg'),
        'sameAs' => [],
    ];

    $websiteJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => config('app.url'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => url('/').'?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
<meta name="keywords" content="{{ $resolvedKeywords }}">
<meta name="robots" content="{{ $robotsContent }}">
<link rel="canonical" href="{{ $resolvedUrl }}">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $resolvedType }}">
<meta property="og:title" content="{{ $resolvedTitle }}">
<meta property="og:description" content="{{ $resolvedDescription }}">
<meta property="og:url" content="{{ $resolvedUrl }}">
<meta property="og:image" content="{{ $resolvedImage }}">
<meta property="og:locale" content="en_US">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $resolvedTitle }}">
<meta name="twitter:description" content="{{ $resolvedDescription }}">
<meta name="twitter:image" content="{{ $resolvedImage }}">

<script type="application/ld+json">{!! json_encode($organizationJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($websiteJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
