@php($pwaManifestVersion = file_exists(public_path('manifest.webmanifest')) ? filemtime(public_path('manifest.webmanifest')) : time())
@php($pwaIconVersion = file_exists(public_path('assets/pwa/icon-192.png')) ? filemtime(public_path('assets/pwa/icon-192.png')) : time())
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v={{ $pwaManifestVersion }}">
<meta name="theme-color" content="#061a44">
<meta name="application-name" content="SmartProBook">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="SmartProBook">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="{{ asset('assets/pwa/icon-192.png') }}?v={{ $pwaIconVersion }}">
