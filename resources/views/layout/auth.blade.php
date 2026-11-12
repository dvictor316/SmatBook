<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $seoNoIndex = true;
        $seoTitle = 'Sign in to SmartProbook';
    @endphp
    @include('layout.partials.seo-meta')
    @include('layout.partials.pwa-head')
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    @yield('style')
</head>
<body>
    <div class="main-wrapper login-body">
        <div id="spb-page-content">
            @yield('content')
        </div>
    </div>
    <script defer src="{{ asset('js/pos-offline-auth.js') }}?v={{ filemtime(public_path('js/pos-offline-auth.js')) }}"></script>
    <script src="{{ asset('assets/js/response-feedback.js') }}?v={{ filemtime(public_path('assets/js/response-feedback.js')) }}"></script>
</body>
</html>
