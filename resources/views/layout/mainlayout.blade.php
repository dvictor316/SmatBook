@php
    use Illuminate\Support\Facades\Route;
    $route = Route::currentRouteName();
    $siteTitle = \App\Models\Setting::where('key', 'company_name')->value('value') ?: 'SmartProbook';
    $defaultFavicon = 'assets/img/log-favicon.png';
    $defaultFaviconUrl = asset($defaultFavicon);
    $defaultFaviconVersion = file_exists(public_path($defaultFavicon)) ? filemtime(public_path($defaultFavicon)) : null;
    $faviconPath = \App\Models\Setting::mediaUrl(
        \App\Models\Setting::where('key', 'favicon')->value('value'),
        $defaultFaviconUrl
    );
    if ($faviconPath === $defaultFaviconUrl && $defaultFaviconVersion) {
        $faviconPath .= '?v=' . $defaultFaviconVersion;
    }
    $requestPath = request()->path();
    $isReportWorkspace = request()->routeIs('reports.*')
        || request()->is('reports*')
        || request()->is('*report*')
        || in_array($requestPath, ['cash-flow', 'balance-sheet', 'trial-balance', 'general-ledger', 'tax-sales', 'tax-purchase'], true)
        || str_contains($requestPath, 'report');
    $isDashboardWorkspace = request()->routeIs('home', 'dashboard', 'deployment.dashboard', 'workspace.business', 'workspace.business.dashboard')
        || in_array($requestPath, ['home', 'dashboard', 'deployment/dashboard', 'workspace/business', 'workspace/business/dashboard'], true);

    // Initialize visibility variables to prevent "undefined" errors
    $hideNavbar = $hideNavbar ?? false;
    $hideSidebar = $hideSidebar ?? false;
    $bodyClasses = [];

    if ($route === 'chat') {
        $bodyClasses[] = 'chat-page';
    } elseif ($route === 'mail-pay-invoice') {
        $bodyClasses[] = 'invoice-center-pay';
    } elseif (in_array($route, ['cashreceipt-1', 'cashreceipt-2', 'cashreceipt-3', 'cashreceipt-4', 'invoice-five', 'invoice-four-a', 'invoice-three', 'invoice-two', 'invoice-one-a'], true)) {
        $bodyClasses[] = 'no-stickybar';
    } elseif ($route === 'error-404') {
        $bodyClasses[] = 'error-page';
    } elseif ($route === 'landing.index') {
        $bodyClasses[] = 'landing-page-body';
    }

    if ($isReportWorkspace) {
        $bodyClasses[] = 'report-workspace';
    }

    if ($isDashboardWorkspace) {
        $bodyClasses[] = 'dashboard-workspace';
    }
@endphp

<!DOCTYPE html>

@if (!Route::is(['index-two', 'landing.index']))
    <html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="lg" data-sidebar-image="none">
@else
    <html lang="en">
@endif

<head>
    @livewireStyles

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $seoNoIndex = $seoNoIndex ?? true;
        $seoTitle = $seoTitle ?? ($siteTitle . ' Dashboard');
    @endphp
    @include('layout.partials.seo-meta')
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">

    {{-- CRITICAL: Prevent theme flickering on load --}}
    <script>
        (function() {
            const savedSettings = JSON.parse(localStorage.getItem('themeSettings')) || {};
            Object.keys(savedSettings).forEach(key => {
                document.documentElement.setAttribute(key, savedSettings[key]);
            });
        })();
    </script>

    <link rel="shortcut icon" href="{{ $faviconPath }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css" 
          integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @include('layout.partials.head')
    @include('layout.partials.design-system')
    @yield('style')

    {{-- GLOBAL PRINT STYLES --}}
    <style>

        {{-- FIX: Automatically remove sidebar margin if sidebar is hidden --}}
        @if($hideSidebar)
        .page-wrapper, .main-wrapper {
            margin-left: 0 !important;
            padding-left: 0 !important;
            width: 100% !important;
        }
        @endif

        @media print {
            .header,
            .sidebar,
            .two-col-bar,
            .settings-icon,
            .ai-agent-launcher,
            #ai-quick-agent-offcanvas,
            #aiAssistantIntroModal,
            .ai-bot-figure,
            .ai-human-avatar,
            .ai-agent-launcher *,
            #sidebar-overlay,
            .btn,
            .footer,
            .theme-settings-offcanvas,
            .sidebar-settings,
            .modal,
            .modal-backdrop,
            .offcanvas,
            .dropdown-menu,
            .tooltip,
            .popover,
            .no-print,
            .d-print-none {
                display: none !important;
            }
            html,
            body {
                background: #fff !important;
                overflow: visible !important;
                height: auto !important;
            }
            .main-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                overflow: visible !important;
                position: static !important;
            }
            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                overflow: visible !important;
                position: static !important;
            }
            .content,
            .container-fluid,
            .container {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                overflow: visible !important;
                position: static !important;
            }

            body.print-scope-active * {
                visibility: hidden !important;
            }

            body.print-scope-active .print-scope-target,
            body.print-scope-active .print-scope-target * {
                visibility: visible !important;
            }

            body.print-scope-active .print-scope-target {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                border: 0 !important;
                box-shadow: none !important;
                overflow: visible !important;
            }

            body.print-scope-active .print-scope-target .card,
            body.print-scope-active .print-scope-target .shadow,
            body.print-scope-active .print-scope-target .shadow-sm,
            body.print-scope-active .print-scope-target .shadow-lg,
            body.print-scope-active .print-scope-target .shadow-2xl {
                box-shadow: none !important;
            }

            body.print-scope-active .print-scope-target .rounded,
            body.print-scope-active .print-scope-target .rounded-lg,
            body.print-scope-active .print-scope-target .rounded-xl,
            body.print-scope-active .print-scope-target .rounded-2xl {
                border-radius: 0 !important;
            }

            body.print-scope-active .print-scope-target .no-print,
            body.print-scope-active .print-scope-target .btn,
            body.print-scope-active .print-scope-target button,
            body.print-scope-active .print-scope-target input,
            body.print-scope-active .print-scope-target select,
            body.print-scope-active .print-scope-target textarea,
            body.print-scope-active .print-scope-target .dt-buttons,
            body.print-scope-active .print-scope-target .dataTables_filter,
            body.print-scope-active .print-scope-target .dataTables_length,
            body.print-scope-active .print-scope-target .dataTables_paginate,
            body.print-scope-active .print-scope-target .pagination,
            body.print-scope-active .print-scope-target .pagination-container,
            body.print-scope-active .print-scope-target .modal,
            body.print-scope-active .print-scope-target .modal-backdrop,
            body.print-scope-active .print-scope-target .offcanvas,
            body.print-scope-active .print-scope-target .dropdown-menu,
            body.print-scope-active .print-scope-target .tooltip,
            body.print-scope-active .print-scope-target .popover,
            body.print-scope-active .print-scope-target .swal2-container {
                display: none !important;
            }

            body.print-scope-active .print-scope-target i[class*="fa-"],
            body.print-scope-active .print-scope-target i[class*="fe-"],
            body.print-scope-active .print-scope-target i[class*="feather"] {
                display: none !important;
            }
        }

        /* NON-OBSTRUCTIVE LAYOUT: Let each view handle its own spacing */
        .main-wrapper {
            position: relative;
            min-height: 100vh;
        }

        /* Header should be positioned properly without blocking content */
        .header {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        /* Sidebar positioning handled by theme, not layout */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 999;
        }

        /* Default content area - views can override this */
        .page-wrapper {
            position: relative;
            margin-left: var(--sb-sidebar-w, 270px) !important;
            width: calc(100% - var(--sb-sidebar-w, 270px)) !important;
            max-width: calc(100% - var(--sb-sidebar-w, 270px)) !important;
            box-sizing: border-box;
            padding-top: 0 !important;
            margin-top: 0 !important;
            min-height: calc(100vh - var(--sb-header-h, 76px));
            min-width: 0;
            transition: margin-left 0.3s ease, width 0.3s ease, max-width 0.3s ease, padding 0.3s ease;
            overflow-x: clip;
        }

        body.sidebar-collapsed .page-wrapper,
        body.mini-sidebar .page-wrapper,
        body.sidebar-icon-only .page-wrapper {
            margin-left: var(--sb-sidebar-collapsed, 80px) !important;
            width: calc(100% - var(--sb-sidebar-collapsed, 80px)) !important;
            max-width: calc(100% - var(--sb-sidebar-collapsed, 80px)) !important;
        }

        /* If legacy mini-sidebar is accidentally left on, force full width when not collapsed. */
        body.mini-sidebar:not(.sidebar-collapsed):not(.sidebar-icon-only) .page-wrapper {
            margin-left: var(--sb-sidebar-w, 270px) !important;
            width: calc(100% - var(--sb-sidebar-w, 270px)) !important;
            max-width: calc(100% - var(--sb-sidebar-w, 270px)) !important;
        }

        @media (max-width: 991px) {
            .page-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        .page-wrapper .content.container-fluid {
            box-sizing: border-box;
            padding-top: 0 !important;
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper {
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.06), transparent 24%),
                radial-gradient(circle at left 20%, rgba(245, 158, 11, 0.05), transparent 20%),
                linear-gradient(180deg, #f8fbff 0%, #f4f8ff 46%, #f9fbff 100%);
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .content,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .content.container-fluid {
            width: 100%;
            max-width: 1560px;
            margin: 0 auto;
            padding: 20px 18px 32px !important;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-header {
            margin-bottom: 18px;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-header h3,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-title,
        body:not(.login-body):not(.landing-page-body) .page-wrapper h1:first-child,
        body:not(.login-body):not(.landing-page-body) .page-wrapper h2:first-child {
            letter-spacing: -0.02em;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper {
            font-size: 0.95rem;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-header h3,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-title,
        body:not(.login-body):not(.landing-page-body) .page-wrapper h1:first-child,
        body:not(.login-body):not(.landing-page-body) .page-wrapper h2:first-child {
            font-size: clamp(1.08rem, 1rem + 0.42vw, 1.5rem) !important;
            line-height: 1.18;
            font-weight: 800;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-header p,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-header .text-muted,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .page-header .small,
        body:not(.login-body):not(.landing-page-body) .page-wrapper p.small,
        body:not(.login-body):not(.landing-page-body) .page-wrapper small,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .small {
            font-size: 0.78rem !important;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .card-title,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .card h4,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .card h5,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .content h4,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .content h5 {
            font-size: 1rem !important;
            line-height: 1.25;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .form-label,
        body:not(.login-body):not(.landing-page-body) .page-wrapper label.small,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .small.fw-bold {
            font-size: 0.72rem !important;
            letter-spacing: 0.05em;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .form-control,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .form-select,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .input-group-text,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .btn,
        body:not(.login-body):not(.landing-page-body) .page-wrapper select,
        body:not(.login-body):not(.landing-page-body) .page-wrapper input,
        body:not(.login-body):not(.landing-page-body) .page-wrapper textarea {
            font-size: 0.88rem;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .card,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .metric-card,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .chart-card,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .stat-card,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .kpi-card {
            border-radius: 22px;
            border: 1px solid rgba(191, 219, 254, 0.82);
            box-shadow: 0 18px 42px rgba(37, 99, 235, 0.08);
            overflow: hidden;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .card .card-header {
            padding: 1rem 1.3rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 250, 255, 0.92));
            border-bottom: 1px solid rgba(191, 219, 254, 0.65);
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .card .card-body {
            padding: 1.2rem 1.35rem;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .table-responsive {
            border-radius: 18px;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .table > :not(caption) > * > * {
            padding-top: 0.88rem;
            padding-bottom: 0.88rem;
            vertical-align: middle;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .table thead th {
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #607495;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .table tbody td {
            color: #183153;
            font-size: 0.9rem;
        }

        body:not(.login-body):not(.landing-page-body) .page-wrapper .badge,
        body:not(.login-body):not(.landing-page-body) .page-wrapper .status-badge {
            font-size: 0.74rem !important;
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            .page-wrapper,
            .pos-full-page-wrapper,
            .pos-content-area,
            .report-page-wrapper,
            #main-content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            body:not(.login-body):not(.landing-page-body) .page-wrapper .content,
            body:not(.login-body):not(.landing-page-body) .page-wrapper .content.container-fluid {
                padding: 16px 14px 26px !important;
            }
        }

        @media (max-width: 991.98px) {
            body:not(.login-body):not(.landing-page-body) .page-wrapper .content,
            body:not(.login-body):not(.landing-page-body) .page-wrapper .content.container-fluid {
                padding: 16px 14px 26px !important;
            }

            body:not(.login-body):not(.landing-page-body) .page-wrapper .card .card-body {
                padding: 1rem 1rem;
            }
        }

        :root {
            --spb-sidebar-bg-top: #f8fbff;
            --spb-sidebar-bg-mid: #f2f7ff;
            --spb-sidebar-bg-bottom: #fcfeff;
            --spb-sidebar-border: rgba(148, 184, 255, 0.22);
            --spb-sidebar-text: #17315f;
            --spb-sidebar-muted: #7b8cab;
            --spb-sidebar-icon: #2f63d9;
            --spb-sidebar-hover-bg: rgba(255, 255, 255, 0.88);
            --spb-sidebar-active-bg: linear-gradient(135deg, rgba(55, 114, 255, 0.12) 0%, rgba(139, 92, 246, 0.07) 100%);
            --spb-sidebar-active-border: rgba(59, 130, 246, 0.22);
            --spb-sidebar-active-text: #0f2f6e;
            --spb-sidebar-shadow: 0 18px 40px rgba(66, 109, 194, 0.09);
        }

        .sidebar,
        .deployment-sidebar {
            background:
                radial-gradient(circle at 18% 16%, rgba(255, 255, 255, 0.92) 0, rgba(255, 255, 255, 0.92) 38px, transparent 39px),
                radial-gradient(circle at 82% 11%, rgba(191, 219, 254, 0.18) 0, rgba(191, 219, 254, 0.18) 58px, transparent 59px),
                radial-gradient(circle at 78% 36%, rgba(255, 255, 255, 0.84) 0, rgba(255, 255, 255, 0.84) 48px, transparent 49px),
                radial-gradient(circle at 14% 70%, rgba(219, 234, 254, 0.22) 0, rgba(219, 234, 254, 0.22) 64px, transparent 65px),
                radial-gradient(circle at 88% 82%, rgba(238, 242, 255, 0.36) 0, rgba(238, 242, 255, 0.36) 70px, transparent 71px),
                linear-gradient(180deg, var(--spb-sidebar-bg-top) 0%, var(--spb-sidebar-bg-mid) 48%, var(--spb-sidebar-bg-bottom) 100%) !important;
            border-right: 1px solid var(--spb-sidebar-border) !important;
            box-shadow: var(--spb-sidebar-shadow);
            overflow: hidden;
        }

        .sidebar::before,
        .deployment-sidebar::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 28% 24%, rgba(255, 255, 255, 0.34) 0, rgba(255, 255, 255, 0.34) 6px, transparent 7px),
                radial-gradient(circle at 72% 42%, rgba(96, 165, 250, 0.10) 0, rgba(96, 165, 250, 0.10) 5px, transparent 6px),
                radial-gradient(circle at 46% 78%, rgba(255, 255, 255, 0.24) 0, rgba(255, 255, 255, 0.24) 5px, transparent 6px),
                radial-gradient(circle at 18% 88%, rgba(147, 197, 253, 0.10) 0, rgba(147, 197, 253, 0.10) 4px, transparent 5px);
            opacity: 0.78;
        }

        .sidebar .sidebar-inner,
        .deployment-sidebar .sidebar-content,
        .deployment-sidebar .sidebar-inner {
            background: transparent !important;
        }

        .sidebar .sidebar-menu,
        .deployment-sidebar .sidebar-menu {
            background: transparent !important;
            padding-top: 10px;
            padding-bottom: 20px;
        }

        .sidebar .sidebar-menu .menu-title,
        .deployment-sidebar .sidebar-menu .menu-title {
            margin-top: 12px;
            margin-bottom: 6px;
            padding: 0 20px;
        }

        .sidebar .sidebar-menu .menu-title span,
        .deployment-sidebar .sidebar-menu .menu-title span {
            color: var(--spb-sidebar-muted) !important;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: none;
        }

        /* ── QB-style reports group headers inside submenu ─────────── */
        .sidebar .sidebar-menu ul li.reports-group-header,
        .deployment-sidebar .sidebar-menu ul li.reports-group-header {
            font-size: 0.68rem !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.09em !important;
            color: #94a3b8 !important;
            padding: 10px 16px 3px !important;
            margin: 0 !important;
            pointer-events: none !important;
            cursor: default !important;
            border-top: 1px solid rgba(148,163,184,.15);
        }
        .sidebar .sidebar-menu ul li.reports-group-header:first-child,
        .deployment-sidebar .sidebar-menu ul li.reports-group-header:first-child {
            border-top: none;
        }
        /* Hub shortcut link inside reports dropdown */
        .sidebar .sidebar-menu ul a.reports-hub-link {
            font-weight: 800 !important;
            color: var(--spb-sidebar-accent, #3b82f6) !important;
        }

        .sidebar .sidebar-menu ul li > a,
        .deployment-sidebar .sidebar-menu ul li > a,
        .deployment-sidebar .dm-menu-link {
            color: var(--spb-sidebar-text) !important;
            border-radius: 16px !important;
            margin: 4px 12px;
            padding: 12px 14px !important;
            font-weight: 700;
            letter-spacing: -0.01em;
            transition: background 0.22s ease, color 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease;
            position: relative;
            z-index: 1;
        }

        .sidebar .sidebar-menu ul li > a i,
        .sidebar .sidebar-menu ul li > a .menu-arrow,
        .deployment-sidebar .sidebar-menu ul li > a i,
        .deployment-sidebar .sidebar-menu ul li > a .menu-arrow,
        .deployment-sidebar .dm-menu-icon,
        .deployment-sidebar .dm-menu-arrow {
            color: var(--spb-sidebar-icon) !important;
        }

        .sidebar .sidebar-menu ul li > a span,
        .deployment-sidebar .sidebar-menu ul li > a span,
        .deployment-sidebar .dm-menu-text {
            color: inherit !important;
        }

        .sidebar .sidebar-menu ul li > a:hover,
        .sidebar .sidebar-menu ul li > a.subdrop,
        .sidebar .sidebar-menu ul li.active > a,
        .deployment-sidebar .sidebar-menu ul li > a:hover,
        .deployment-sidebar .sidebar-menu ul li > a.subdrop,
        .deployment-sidebar .sidebar-menu ul li.active > a,
        .deployment-sidebar .dm-menu-link:hover,
        .deployment-sidebar .dm-menu-link.active {
            background: var(--spb-sidebar-hover-bg) !important;
            color: var(--spb-sidebar-active-text) !important;
            box-shadow: 0 10px 24px rgba(83, 126, 210, 0.10);
            transform: translateX(1px);
        }

        .sidebar .sidebar-menu ul li.submenu ul li a,
        .deployment-sidebar .sidebar-menu ul li.submenu ul li a,
        .deployment-sidebar .dm-submenu a {
            margin-left: 22px;
            margin-right: 12px;
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            color: #3c527f !important;
            font-weight: 600;
            border-radius: 14px !important;
            background: rgba(255, 255, 255, 0.34);
        }

        .sidebar .sidebar-menu ul li.submenu ul li a:hover,
        .sidebar .sidebar-menu ul li.submenu ul li a.active,
        .deployment-sidebar .sidebar-menu ul li.submenu ul li a:hover,
        .deployment-sidebar .sidebar-menu ul li.submenu ul li a.active,
        .deployment-sidebar .dm-submenu a:hover,
        .deployment-sidebar .dm-submenu a.active {
            background: rgba(255, 255, 255, 0.76) !important;
            color: var(--spb-sidebar-active-text) !important;
        }

        .sidebar .sidebar-menu ul li.active > a,
        .sidebar .sidebar-menu ul li > a.subdrop,
        .deployment-sidebar .sidebar-menu ul li.active > a,
        .deployment-sidebar .sidebar-menu ul li > a.subdrop,
        .deployment-sidebar .dm-menu-link.active {
            background: var(--spb-sidebar-active-bg) !important;
            border: 1px solid var(--spb-sidebar-active-border);
        }

        .sidebar .sidebar-menu ul li.active > a::before,
        .deployment-sidebar .sidebar-menu ul li.active > a::before,
        .deployment-sidebar .dm-menu-link.active::before {
            content: "";
            position: absolute;
            left: -1px;
            top: 12px;
            bottom: 12px;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(180deg, #2563eb 0%, #7c3aed 100%);
        }

        .sidebar .sidebar-menu::-webkit-scrollbar,
        .sidebar .sidebar-inner::-webkit-scrollbar,
        .deployment-sidebar::-webkit-scrollbar,
        .deployment-sidebar .sidebar-content::-webkit-scrollbar {
            width: 10px;
        }

        .sidebar .sidebar-menu::-webkit-scrollbar-track,
        .sidebar .sidebar-inner::-webkit-scrollbar-track,
        .deployment-sidebar::-webkit-scrollbar-track,
        .deployment-sidebar .sidebar-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.42);
            border-radius: 999px;
        }

        .sidebar .sidebar-menu::-webkit-scrollbar-thumb,
        .sidebar .sidebar-inner::-webkit-scrollbar-thumb,
        .deployment-sidebar::-webkit-scrollbar-thumb,
        .deployment-sidebar .sidebar-content::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(96, 165, 250, 0.72) 0%, rgba(129, 140, 248, 0.72) 100%);
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.58);
        }

        @media (max-width: 991.98px) {
            .sidebar,
            .deployment-sidebar {
                box-shadow: 0 24px 48px rgba(30, 64, 175, 0.22);
            }
        }

        .btn {
            border-radius: 999px !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease !important;
        }

        .btn:hover {
            transform: translateY(-1px);
            filter: saturate(1.05);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
        }

        @media (max-width: 991.98px) {
            html, body {
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }

            body {
                touch-action: pan-y;
                overscroll-behavior-y: auto;
            }

            .page-wrapper {
                margin-left: 0 !important;
                -webkit-overflow-scrolling: touch;
            }

            .page-wrapper .content.container-fluid {
                padding-top: 0 !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
                -webkit-overflow-scrolling: touch;
            }

            .main-wrapper,
            .mobile-search-overlay,
            #sidebar,
            #deploymentSidebar,
            .deployment-sidebar {
                -webkit-overflow-scrolling: touch;
            }

            #sidebar,
            #deploymentSidebar,
            .deployment-sidebar,
            .mobile-search-overlay {
                overscroll-behavior: contain;
            }
        }

        @media (max-width: 575.98px) {
            .btn {
                min-height: 40px;
            }
        }

        .dashboard-workspace {
            --dash-bg-top: #f7fbff;
            --dash-bg-mid: #f5f7ff;
            --dash-bg-bottom: #fffdf7;
            --dash-card-border: rgba(172, 193, 255, 0.24);
            --dash-card-shadow: 0 18px 38px rgba(37, 99, 235, 0.10);
            --dash-title: #20335f;
            --dash-muted: #7b88a6;
        }

        .dashboard-workspace .page-wrapper {
            background:
                radial-gradient(920px 280px at 8% 0%, rgba(96, 165, 250, 0.12) 0%, rgba(96, 165, 250, 0) 58%),
                radial-gradient(900px 260px at 92% 4%, rgba(250, 204, 21, 0.10) 0%, rgba(250, 204, 21, 0) 56%),
                linear-gradient(180deg, var(--dash-bg-top) 0%, var(--dash-bg-mid) 50%, var(--dash-bg-bottom) 100%) !important;
        }

        .dashboard-workspace .card,
        .dashboard-workspace .metric-card,
        .dashboard-workspace .chart-card,
        .dashboard-workspace .stat-card,
        .dashboard-workspace .kpi-card,
        .dashboard-workspace .metric-node {
            border: 1px solid var(--dash-card-border) !important;
            border-radius: 22px !important;
            box-shadow: var(--dash-card-shadow) !important;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 251, 255, 0.96) 100%) !important;
            overflow: hidden;
            position: relative;
        }

        .dashboard-workspace .card::before,
        .dashboard-workspace .metric-card::before,
        .dashboard-workspace .chart-card::before,
        .dashboard-workspace .stat-card::before,
        .dashboard-workspace .kpi-card::before,
        .dashboard-workspace .metric-node::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #2563eb 0%, #7c3aed 42%, #f59e0b 100%);
            opacity: 0.9;
        }

        .dashboard-workspace .card .card-body,
        .dashboard-workspace .metric-card,
        .dashboard-workspace .chart-card,
        .dashboard-workspace .stat-card,
        .dashboard-workspace .kpi-card,
        .dashboard-workspace .metric-node,
        .dashboard-workspace .dash-count,
        .dashboard-workspace .dash-widget-header {
            color: var(--dash-title) !important;
        }

        .dashboard-workspace .card .card-body .text-white,
        .dashboard-workspace .metric-card .text-white,
        .dashboard-workspace .chart-card .text-white,
        .dashboard-workspace .stat-card .text-white,
        .dashboard-workspace .kpi-card .text-white,
        .dashboard-workspace .metric-node .text-white {
            color: var(--dash-title) !important;
        }

        .dashboard-workspace .card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link):not(.dash-widget-icon):not(.kpi-icon),
        .dashboard-workspace .metric-card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link):not(.dash-widget-icon):not(.kpi-icon),
        .dashboard-workspace .chart-card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link):not(.dash-widget-icon):not(.kpi-icon),
        .dashboard-workspace .stat-card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link):not(.dash-widget-icon):not(.kpi-icon),
        .dashboard-workspace .kpi-card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link):not(.dash-widget-icon):not(.kpi-icon),
        .dashboard-workspace .metric-node .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link):not(.dash-widget-icon):not(.kpi-icon),
        .dashboard-workspace .panel-card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link),
        .dashboard-workspace .mini-metric .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link),
        .dashboard-workspace .insight-band .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link),
        .dashboard-workspace .insight-item .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link),
        .dashboard-workspace .spark-box .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link),
        .dashboard-workspace .teaser-lock .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link),
        .dashboard-workspace .enterprise-card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link),
        .dashboard-workspace .insight-card .text-white:not(.btn):not(.badge):not(.page-link):not(.dropdown-item):not(.nav-link) {
            color: var(--dash-title) !important;
        }

        .dashboard-workspace .card .text-white-50,
        .dashboard-workspace .metric-card .text-white-50,
        .dashboard-workspace .chart-card .text-white-50,
        .dashboard-workspace .stat-card .text-white-50,
        .dashboard-workspace .kpi-card .text-white-50,
        .dashboard-workspace .metric-node .text-white-50,
        .dashboard-workspace .panel-card .text-white-50,
        .dashboard-workspace .mini-metric .text-white-50,
        .dashboard-workspace .insight-band .text-white-50,
        .dashboard-workspace .insight-item .text-white-50,
        .dashboard-workspace .spark-box .text-white-50,
        .dashboard-workspace .teaser-lock .text-white-50,
        .dashboard-workspace .enterprise-card .text-white-50,
        .dashboard-workspace .insight-card .text-white-50,
        .dashboard-workspace .card .text-light:not(.btn):not(.badge),
        .dashboard-workspace .metric-card .text-light:not(.btn):not(.badge),
        .dashboard-workspace .chart-card .text-light:not(.btn):not(.badge),
        .dashboard-workspace .stat-card .text-light:not(.btn):not(.badge),
        .dashboard-workspace .kpi-card .text-light:not(.btn):not(.badge),
        .dashboard-workspace .metric-node .text-light:not(.btn):not(.badge),
        .dashboard-workspace .panel-card .text-light:not(.btn):not(.badge),
        .dashboard-workspace .mini-metric .text-light:not(.btn):not(.badge),
        .dashboard-workspace .insight-band .text-light:not(.btn):not(.badge),
        .dashboard-workspace .insight-item .text-light:not(.btn):not(.badge),
        .dashboard-workspace .spark-box .text-light:not(.btn):not(.badge),
        .dashboard-workspace .teaser-lock .text-light:not(.btn):not(.badge),
        .dashboard-workspace .enterprise-card .text-light:not(.btn):not(.badge),
        .dashboard-workspace .insight-card .text-light:not(.btn):not(.badge) {
            color: var(--dash-muted) !important;
        }

        .dashboard-workspace .dash-title,
        .dashboard-workspace .metric-title,
        .dashboard-workspace .metric-label,
        .dashboard-workspace .kpi-label {
            color: var(--dash-muted) !important;
            font-weight: 800 !important;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dashboard-workspace .dash-counts p,
        .dashboard-workspace .metric-value,
        .dashboard-workspace .kpi-value,
        .dashboard-workspace .stat-card h3,
        .dashboard-workspace .stat-card h4,
        .dashboard-workspace .stat-card .value {
            color: var(--dash-title) !important;
            font-weight: 900 !important;
            letter-spacing: -0.03em;
        }

        .dashboard-workspace .dash-widget-icon,
        .dashboard-workspace .kpi-icon {
            border-radius: 18px !important;
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.18);
        }

        .dashboard-workspace .dash-widget-icon,
        .dashboard-workspace .dash-widget-icon *,
        .dashboard-workspace .kpi-icon,
        .dashboard-workspace .kpi-icon * {
            color: #fff !important;
        }

        .dashboard-workspace .dash-widget-icon.bg-primary,
        .dashboard-workspace .kpi-card.blue .kpi-icon {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%) !important;
            color: #fff !important;
        }

        .dashboard-workspace .dash-widget-icon.bg-success,
        .dashboard-workspace .kpi-card.green .kpi-icon {
            background: linear-gradient(135deg, #10b981 0%, #22c55e 100%) !important;
            color: #fff !important;
        }

        .dashboard-workspace .dash-widget-icon.bg-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%) !important;
            color: #fff !important;
        }

        .dashboard-workspace .dash-widget-icon.bg-info,
        .dashboard-workspace .kpi-card.red .kpi-icon {
            background: linear-gradient(135deg, #ec4899 0%, #ef4444 100%) !important;
            color: #fff !important;
        }

        .dashboard-workspace .metric-node:nth-child(1),
        .dashboard-workspace .card:nth-child(4n+1) {
            background: linear-gradient(180deg, #ffffff 0%, #eef6ff 100%) !important;
        }

        .dashboard-workspace .metric-node:nth-child(2),
        .dashboard-workspace .card:nth-child(4n+2) {
            background: linear-gradient(180deg, #ffffff 0%, #f5f0ff 100%) !important;
        }

        .dashboard-workspace .metric-node:nth-child(3),
        .dashboard-workspace .card:nth-child(4n+3) {
            background: linear-gradient(180deg, #ffffff 0%, #fff6e9 100%) !important;
        }

        .dashboard-workspace .metric-node:nth-child(4),
        .dashboard-workspace .card:nth-child(4n+4) {
            background: linear-gradient(180deg, #ffffff 0%, #edfdf4 100%) !important;
        }

        .report-workspace {
            --report-bg: #f4f7fb;
            --report-card-bg: #ffffff;
            --report-card-border: #d9e3f0;
            --report-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
            --report-accent: #1d4ed8;
            --report-accent-deep: #0f2d5c;
            --report-accent-soft: #eef4ff;
            --report-gold: #b9872f;
            --report-text: #1e293b;
            --report-muted: #64748b;
        }

        .report-workspace .page-wrapper {
            background: linear-gradient(180deg, #f8fbff 0%, var(--report-bg) 100%) !important;
            min-height: calc(100vh - var(--sb-header-h, 76px));
        }

        .report-workspace .page-wrapper .content.container-fluid {
            padding-top: 20px !important;
            padding-bottom: 32px !important;
        }

        .report-workspace .page-header {
            margin-bottom: 1rem;
        }

        .report-workspace .page-header > .content-page-header,
        .report-workspace .page-header > .row {
            padding: 1.15rem 1.25rem;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--report-card-border);
            border-radius: 22px;
            box-shadow: var(--report-shadow);
            margin: 0;
        }

        .report-workspace .page-header h3,
        .report-workspace .page-header h4,
        .report-workspace .page-header h5,
        .report-workspace .content-page-header h5,
        .report-workspace .page-title {
            margin: 0;
            color: var(--report-accent-deep);
            font-size: clamp(1.2rem, 1.1rem + 0.5vw, 1.7rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .report-workspace .page-header p,
        .report-workspace .page-header .breadcrumb,
        .report-workspace .page-header .text-muted,
        .report-workspace .page-header .small {
            color: var(--report-muted) !important;
        }

        .report-workspace .list-btn .filter-list {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin: 0;
        }

        .report-workspace .card,
        .report-workspace .card-table,
        .report-workspace .report-container,
        .report-workspace .filter-card,
        .report-workspace .smart-filter-card {
            border-radius: 22px !important;
            border: 1px solid var(--report-card-border) !important;
            background: var(--report-card-bg) !important;
            box-shadow: var(--report-shadow) !important;
        }

        .report-workspace .card-body,
        .report-workspace .card-footer {
            padding: 1.2rem 1.25rem;
        }

        .report-workspace .card-footer {
            border-top: 1px solid #e6edf7 !important;
            background: transparent !important;
        }

        .report-workspace .table-responsive {
            border-radius: 20px;
        }

        .report-workspace .table {
            color: var(--report-text);
            margin-bottom: 0;
        }

        .report-workspace .table thead th {
            background: #f5f9ff !important;
            color: var(--report-accent-deep) !important;
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-bottom: 1px solid #dbe7f5 !important;
            padding-top: 0.95rem !important;
            padding-bottom: 0.95rem !important;
            vertical-align: middle;
        }

        .report-workspace .table tbody td,
        .report-workspace .table tfoot td {
            padding-top: 0.95rem !important;
            padding-bottom: 0.95rem !important;
            border-color: #ebf1f7 !important;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .report-workspace .table tbody tr:hover {
            background: #f8fbff !important;
        }

        .report-workspace .form-control,
        .report-workspace .form-select {
            min-height: 46px;
            border-radius: 14px;
            border-color: #d6e0ec;
            color: var(--report-text);
            background: #fbfdff;
            box-shadow: none;
        }

        .report-workspace .form-control:focus,
        .report-workspace .form-select:focus {
            border-color: rgba(29, 78, 216, 0.4);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
            background: #fff;
        }

        .report-workspace .form-label,
        .report-workspace label.small,
        .report-workspace .small.fw-bold {
            color: var(--report-accent-deep) !important;
            font-size: 0.76rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .report-workspace .btn-primary,
        .report-workspace .btn.btn-primary,
        .report-workspace .btn.btn-dark,
        .report-workspace .btn-outline-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            border-color: #1d4ed8 !important;
            color: #fff !important;
        }

        .report-workspace .btn-secondary,
        .report-workspace .btn-outline-secondary,
        .report-workspace .btn-light,
        .report-workspace .btn-white,
        .report-workspace .btn-filters {
            background: #ffffff !important;
            border: 1px solid #d7e2f0 !important;
            color: var(--report-accent-deep) !important;
        }

        .report-workspace .btn-success,
        .report-workspace .btn-outline-success:hover {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%) !important;
            border-color: #0f766e !important;
            color: #fff !important;
        }

        .report-workspace .badge,
        .report-workspace .status-badge,
        .report-workspace .badge-soft-secondary {
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
            font-weight: 700;
        }

        .report-workspace .pagination {
            gap: 0.35rem;
        }

        .report-workspace .pagination .page-link {
            border-radius: 12px;
            border-color: #d7e2f0;
            color: var(--report-accent-deep);
            min-width: 40px;
            text-align: center;
            box-shadow: none;
        }

        .report-workspace .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-color: #1d4ed8;
            color: #fff;
        }

        .report-workspace .dataTables_filter input,
        .report-workspace .dataTables_length select {
            border-radius: 14px !important;
            border-color: #d7e2f0 !important;
        }

        .report-workspace .dt-buttons .btn {
            margin-right: 0.45rem;
        }

        @media (max-width: 991.98px) {
            .report-workspace .page-header > .content-page-header,
            .report-workspace .page-header > .row {
                padding: 1rem;
                border-radius: 18px;
            }
        }

        @media (max-width: 575.98px) {
            .report-workspace .page-wrapper .content.container-fluid {
                padding-top: 14px !important;
                padding-bottom: 24px !important;
            }

            .report-workspace .card,
            .report-workspace .card-table,
            .report-workspace .report-container,
            .report-workspace .filter-card,
            .report-workspace .smart-filter-card {
                border-radius: 18px !important;
            }

            .report-workspace .card-body,
            .report-workspace .card-footer {
                padding: 1rem;
            }

            .report-workspace .table thead th,
            .report-workspace .table tbody td,
            .report-workspace .table tfoot td {
                font-size: 0.85rem !important;
            }
        }

        /* Keep the Livewire/NProgress busy spinner visible on mobile */
        #nprogress .spinner {
            top: calc(env(safe-area-inset-top, 0px) + 14px) !important;
            right: 14px !important;
            z-index: 10060 !important;
        }

        #nprogress .spinner-icon {
            width: 18px;
            height: 18px;
            border-width: 2px;
        }

        @media (max-width: 991.98px) {
            #nprogress .spinner {
                top: calc(env(safe-area-inset-top, 0px) + 72px) !important;
                right: 12px !important;
            }

            #nprogress .spinner-icon {
                width: 22px;
                height: 22px;
                border-width: 2.5px;
            }
        }
    </style>
    <style>
        @media (min-width: 768px) {
            .btn-action-icon,
            .action-icon {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
            }

            .btn-action-icon::after,
            .action-icon::after {
                content: "";
            }
        }

        /* Feather icon fallback to Font Awesome when feather webfont is missing */
        .fe {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900;
        }
        .fe-filter:before { content: "\f0b0"; }
        .fe-download:before { content: "\f019"; }
        .fe-printer:before { content: "\f02f"; }
        .fe-settings:before { content: "\f013"; }
        .fe-grid:before { content: "\f00a"; }
        .fe-list:before { content: "\f03a"; }
        .fe-refresh-ccw:before { content: "\f2f1"; }
        .fe-rotate-ccw:before { content: "\f2ea"; }
        .fe-check-square:before { content: "\f14a"; }
        .fe-inbox:before { content: "\f01c"; }
        .fe-plus-circle:before { content: "\f055"; }
        .fe-plus:before { content: "\2b"; }
        .fe-trash-2:before { content: "\f2ed"; }
        .fe-edit:before { content: "\f044"; }
        .fe-search:before { content: "\f002"; }
        .fe-calendar:before { content: "\f133"; }
        .fe-upload:before { content: "\f093"; }
        .fe-mail:before { content: "\f0e0"; }
        .fe-message-square:before { content: "\f4ad"; }
        .fe-clipboard:before { content: "\f328"; }

        .cal-icon:after,
        .cal-icon-info:after {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            content: "\f133";
        }

        [class^="feather-"],
        [class*=" feather-"] {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            font-style: normal;
            line-height: 1;
        }

        .feather-printer:before { content: "\f02f"; }
        .feather-file-text:before { content: "\f15c"; }
        .feather-file:before { content: "\f15b"; }
        .feather-download:before { content: "\f019"; }
        .feather-upload:before { content: "\f093"; }
        .feather-mail:before { content: "\f0e0"; }
        .feather-filter:before { content: "\f0b0"; }
        .feather-refresh-ccw:before { content: "\f2f1"; }
        .feather-shopping-cart:before { content: "\f07a"; }
        .feather-trending-down:before { content: "\f063"; }
        .feather-corner-up-left:before { content: "\f148"; }
        .feather-alert-triangle:before { content: "\f071"; }
        .feather-inbox:before { content: "\f01c"; }
        .feather-credit-card:before { content: "\f09d"; }
        .feather-grid:before { content: "\f00a"; }
        .feather-arrow-left:before { content: "\f060"; }
        .feather-arrow-left-circle:before { content: "\f0a8"; }
    </style>
    @stack('styles')
</head>

<body @if(!empty($bodyClasses)) class="{{ implode(' ', $bodyClasses) }}" @endif>
    </div>

    {{-- MAIN WRAPPER --}}
    @if (!in_array($route, [
            'landing.index',
            'index-five',
            'mail-pay-invoice',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
            'invoice-four-a',
            'invoice-one-a',
            'invoice-three',
            'invoice-two',
            'forgot-password',
            'lock-screen',
            'login',
            'register',
            'saas-login',
            'saas-register',
        ]))
        <div class="main-wrapper">
    @elseif ($route === 'landing.index')
        <div class="main-wrapper landing-wrapper" style="width: 100%; margin: 0; padding: 0;">
    @elseif ($route === 'invoice-four-a')
        <div class="main-wrapper invoice-four">
    @elseif ($route === 'invoice-one-a')
        <div class="main-wrapper invoice-one">
    @elseif ($route === 'invoice-three')
        <div class="main-wrapper invoice-three">
    @elseif ($route === 'invoice-two')
        <div class="main-wrapper invoice-two">
    @elseif ($route === 'index-five')
        <div class="main-wrapper container">
    @elseif (in_array($route, ['forgot-password', 'lock-screen', 'login', 'register', 'saas-login', 'saas-register']))
        <div class="main-wrapper login-body">
    @endif

    {{-- HEADER --}}
    @if (!$hideNavbar && !in_array($route, [
            'landing.index',
            'signature-preview-invoice',
            'mail-pay-invoice',
            'pay-online',
            'login',
            'register',
            'saas-login',
            'invoice-subscription',
            'saas-register',
            'forgot-password',
            'lock-screen',
            'error-404',
            'invoice-one-a',
            'invoice-two',
            'invoice-three',
            'invoice-four-a',
            'invoice-five',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
        ]))
        @include('layout.partials.header')
    @endif

    {{-- SIDEBAR --}}
    @if (!$hideSidebar && !in_array($route, [
            'landing.index',
            'signature-preview-invoice',
            'mail-pay-invoice',
            'pay-online',
            'login',
            'register',
            'saas-login',
            'invoice-subscription',
            'saas-register',
            'forgot-password',
            'lock-screen',
            'error-404',
            'invoice-one-a',
            'invoice-two',
            'invoice-three',
            'invoice-four-a',
            'invoice-five',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
        ]))
        @include('layout.partials.sidebar')
    @endif

    {{-- OPTIONAL TWO-COLUMN SIDEBAR --}}
    @if ($route === 'index-four')
        @include('layout.partials.two-col-sidebar')
    @endif

    {{-- MAIN PAGE CONTENT --}}
    @include('layout.partials.flash-messages')
    @yield('content')

    @php
        $skipGlobalModals = in_array($route, [
            'landing.index',
            'landing.about',
            'landing.contact',
            'landing.team',
            'landing.policy',
            'login',
            'register',
            'saas-login',
            'saas-register',
            'forgot-password',
            'lock-screen',
            'error-404',
            'saas.checkout',
            'saas.setup',
            'saas.success',
            'saas.payment.success',
            'saas.payment.cancel',
        ], true) || request()->is('saas/*');
    @endphp

    @unless($skipGlobalModals)
        {{-- Heavy global modals: load only where needed to reduce payload/render time --}}
        @component('components.add-modal-popup') @endcomponent
        @component('components.edit-modal-popup') @endcomponent
        @component('components.modal-popup') @endcomponent
    @endunless

    {{-- CLOSE WRAPPER --}}
    @if (!in_array($route, [
            'mail-pay-invoice',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
            'index-five',
            'forgot-password',
            'lock-screen',
            'login',
            'register',
            'saas-login',
            'saas-register',
        ]))
        </div>
    @endif

    {{-- THEME SETTINGS --}}
    @if (
        !$hideSidebar
        && !in_array($route, [
            'landing.index',
            'index-two',
            'index-three',
            'index-four',
            'index-five',
            'signature-preview-invoice',
            'mail-pay-invoice',
            'pay-online',
            'login',
            'register',
            'saas-login',
            'invoice-subscription',
            'saas-register',
            'forgot-password',
            'reset-password',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'saas.checkout',
            'lock-screen',
            'error-404',
            'invoice-one-a',
            'invoice-two',
            'invoice-three',
            'invoice-four-a',
            'invoice-five',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
        ])
        && !request()->is('saas/checkout*', 'forgot-password*', 'reset-password*', 'password/*')
    )
        @include('layout.partials.ai-quick-agent')
    @endif

    {{-- FOOTER SCRIPTS --}}
    @include('layout.partials.footer-scripts')
    @yield('script')
    @stack('scripts')

    @livewireScripts

    {{-- GLOBAL SESSION EXPIRY HANDLER --}}
    {{-- Catches 401/419 from ALL requests (jQuery, fetch, Livewire) and redirects to login --}}
    @php
        $__host      = request()->getHost();
        $__main      = ltrim(config('app.domain') ?: (parse_url(config('app.url'), PHP_URL_HOST) ?: 'smartprobook.com'), '.');
        $__loginUrl  = ($__host !== $__main && str_contains($__host, $__main))
                        ? route('login')
                        : route('saas-login');
    @endphp
    <script>
    (function () {
        var loginUrl = '{{ $__loginUrl }}';
        var redirecting = false;

        function goToLogin() {
            if (redirecting) return;
            redirecting = true;
            window.location.href = loginUrl;
        }

        function isAuthError(status) {
            return status === 401 || status === 419;
        }

        // ── jQuery AJAX global error handler ─────────────────────────────
        if (window.jQuery) {
            jQuery(document).ajaxError(function (event, xhr) {
                if (isAuthError(xhr.status)) {
                    goToLogin();
                }
            });
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                if (window.jQuery) {
                    jQuery(document).ajaxError(function (event, xhr) {
                        if (isAuthError(xhr.status)) {
                            goToLogin();
                        }
                    });
                }
            });
        }

        // ── Fetch interceptor ─────────────────────────────────────────────
        var _nativeFetch = window.fetch;
        window.fetch = function () {
            return _nativeFetch.apply(this, arguments).then(function (response) {
                if (isAuthError(response.status)) {
                    goToLogin();
                }
                return response;
            });
        };

        // ── Livewire v2 hook ──────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Livewire) {
                try {
                    // Livewire v3
                    Livewire.hook('request', function ({ fail }) {
                        fail(function ({ status }) {
                            if (isAuthError(status)) goToLogin();
                        });
                    });
                } catch (e) {
                    try {
                        // Livewire v2
                        Livewire.onError(function (statusCode) {
                            if (isAuthError(statusCode)) {
                                goToLogin();
                                return false;
                            }
                        });
                    } catch (e2) {}
                }
            }
        });
    })();
    </script>


    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const html = document.documentElement;
        const themeOffcanvas = document.getElementById('theme-settings-offcanvas');
        
        if (!themeOffcanvas) return;

        const inputs = themeOffcanvas.querySelectorAll('input');
        const resetBtn = document.getElementById('reset-layout');

        function syncUI() {
            inputs.forEach(input => {
                const attrName = input.name;
                const attrValue = html.getAttribute(attrName);
                if (attrValue && input.value === attrValue) {
                    input.checked = true;
                }
                if (input.id === 'rtl') {
                    input.checked = html.getAttribute('dir') === 'rtl';
                }
            });
        }
        syncUI();

        inputs.forEach(input => {
            input.addEventListener('change', function () {
                let name = this.name;
                let value = this.value;

                if (this.id === 'rtl') {
                    name = 'dir';
                    value = this.checked ? 'rtl' : 'ltr';
                }

                html.setAttribute(name, value);

                const currentSettings = JSON.parse(localStorage.getItem('themeSettings')) || {};
                currentSettings[name] = value;
                localStorage.setItem('themeSettings', JSON.stringify(currentSettings));
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                localStorage.removeItem('themeSettings');
                window.location.reload();
            });
        }
    });
    </script>

    <script>
        (function () {
            const nativePrint = typeof window.print === 'function' ? window.print.bind(window) : null;
            const printState = {
                active: false,
                lastTriggeredAt: 0,
                releaseTimer: null,
                afterPrintBound: false,
                target: null,
            };

            function isVisible(el) {
                if (!el) {
                    return false;
                }

                const style = window.getComputedStyle(el);
                return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetParent !== null;
            }

            function clearPrintScope() {
                if (printState.target) {
                    printState.target.classList.remove('print-scope-target');
                    printState.target = null;
                }

                document.body.classList.remove('print-scope-active');
            }

            function releasePrintLock() {
                printState.active = false;
                if (printState.releaseTimer) {
                    clearTimeout(printState.releaseTimer);
                    printState.releaseTimer = null;
                }
                clearPrintScope();
            }

            function resolveTarget(target) {
                if (!target) {
                    return null;
                }

                if (target instanceof Element) {
                    return target;
                }

                if (typeof target === 'string') {
                    return document.querySelector(target);
                }

                return null;
            }

            function findBestPrintScope(explicitTarget) {
                const resolvedExplicitTarget = resolveTarget(explicitTarget);
                if (resolvedExplicitTarget && isVisible(resolvedExplicitTarget)) {
                    return resolvedExplicitTarget;
                }

                const selectors = [
                    '[data-print-scope]',
                    '#report-container',
                    '#invoice-content',
                    '.report-container',
                    '.receipt-sheet:not(.hidden)',
                    '.invoice-content',
                    '.invoice-item',
                ];

                for (const selector of selectors) {
                    const candidates = Array.from(document.querySelectorAll(selector));
                    const match = candidates.find((el) => isVisible(el));
                    if (match) {
                        return match;
                    }
                }

                return null;
            }

            function activatePrintScope(target) {
                if (document.body.classList.contains('receipt-print-active')) {
                    return null;
                }

                const scope = findBestPrintScope(target);
                if (!scope) {
                    return null;
                }

                clearPrintScope();
                scope.classList.add('print-scope-target');
                document.body.classList.add('print-scope-active');
                printState.target = scope;

                return scope;
            }

            function sanitizeExportClone(clone) {
                clone.querySelectorAll([
                    'script',
                    'style',
                    '.no-print',
                    '.btn',
                    'button',
                    'input',
                    'select',
                    'textarea',
                    '.dt-buttons',
                    '.dataTables_filter',
                    '.dataTables_length',
                    '.dataTables_paginate',
                    '.pagination',
                    '.pagination-container',
                    '.modal',
                    '.modal-backdrop',
                    '.offcanvas',
                    '.dropdown-menu',
                    '.tooltip',
                    '.popover',
                    '.swal2-container',
                    '.ai-agent-launcher',
                    '.sidebar',
                    '.header',
                    '.footer',
                ].join(',')).forEach((node) => node.remove());

                clone.querySelectorAll('i[class*="fa-"], i[class*="fe-"], i[class*="feather"]').forEach((icon) => icon.remove());

                return clone;
            }

            function mountSanitizedExportNode(source) {
                const mount = document.createElement('div');
                mount.className = 'smartprobook-export-shell';
                mount.style.position = 'fixed';
                mount.style.left = '-100000px';
                mount.style.top = '0';
                mount.style.width = '1200px';
                mount.style.background = '#ffffff';
                mount.style.padding = '24px';
                mount.style.zIndex = '-1';
                mount.appendChild(sanitizeExportClone(source.cloneNode(true)));
                document.body.appendChild(mount);

                return mount;
            }

            function triggerPrint(target) {
                if (!nativePrint) {
                    return;
                }

                const now = Date.now();
                if (printState.active || (now - printState.lastTriggeredAt) < 1500) {
                    return;
                }

                printState.active = true;
                printState.lastTriggeredAt = now;

                const scope = activatePrintScope(target);
                if (!scope) {
                    clearPrintScope();
                }

                if (!printState.afterPrintBound) {
                    window.addEventListener('afterprint', releasePrintLock);
                    printState.afterPrintBound = true;
                }

                printState.releaseTimer = window.setTimeout(releasePrintLock, 3000);
                nativePrint();
            }

            window.smartProbookTriggerPrint = triggerPrint;
            window.smartProbookExportElementToPdf = function (target, options = {}) {
                const source = findBestPrintScope(target);
                if (!source) {
                    return triggerPrint();
                }

                const filename = options.filename || ((document.title || 'report').replace(/\s+/g, '_') + '.pdf');
                if (window.html2pdf) {
                    const mount = mountSanitizedExportNode(source);
                    const exportNode = mount.firstElementChild;

                    return window.html2pdf()
                        .set({
                            margin: options.margin ?? 0.4,
                            filename,
                            image: { type: 'jpeg', quality: 0.98 },
                            html2canvas: { scale: 2, useCORS: true },
                            jsPDF: {
                                unit: options.unit || 'in',
                                format: options.format || 'a4',
                                orientation: options.orientation || 'portrait',
                            },
                        })
                        .from(exportNode)
                        .save()
                        .finally(() => mount.remove());
                }

                return triggerPrint(source);
            };
            window.print = function () {
                triggerPrint();
            };

            function isDummyHref(el) {
                const href = (el.getAttribute('href') || '').trim().toLowerCase();
                return href === '' || href === '#' || href === 'javascript:void(0);' || href === 'javascript:void(0)';
            }

            function firstVisibleTable() {
                const tables = Array.from(document.querySelectorAll('table'));
                return tables.find((table) => {
                    const style = window.getComputedStyle(table);
                    return style.display !== 'none' && style.visibility !== 'hidden' && table.offsetParent !== null;
                }) || null;
            }

            function tableToMatrix(table) {
                return Array.from(table.querySelectorAll('tr')).map((row) => {
                    return Array.from(row.querySelectorAll('th,td')).map((cell) => {
                        return (cell.innerText || '').replace(/\s+/g, ' ').trim();
                    });
                }).filter((row) => row.length);
            }

            function downloadBlob(content, filename, type) {
                const blob = new Blob([content], { type });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            function findVisibleActionButton(selectors) {
                for (const selector of selectors) {
                    const button = Array.from(document.querySelectorAll(selector)).find((el) => isVisible(el));
                    if (button) {
                        return button;
                    }
                }

                return null;
            }

            function exportTable(format) {
                const table = firstVisibleTable();
                if (!table) {
                    if (format === 'pdf') {
                        triggerPrint();
                    }
                    return;
                }

                const rows = tableToMatrix(table);
                const safeTitle = (document.title || 'report').toLowerCase().replace(/[^a-z0-9]+/g, '_');

                if (format === 'excel') {
                    const tsv = rows.map((row) => row.map((col) => col.replace(/\t/g, ' ')).join('\t')).join('\n');
                    downloadBlob(tsv, safeTitle + '.xls', 'application/vnd.ms-excel;charset=utf-8;');
                    return;
                }

                if (format === 'csv') {
                    const csv = rows.map((row) => row.map((col) => {
                        const escaped = String(col).replace(/"/g, '""');
                        return `"${escaped}"`;
                    }).join(',')).join('\n');
                    downloadBlob(csv, safeTitle + '.csv', 'text/csv;charset=utf-8;');
                    return;
                }

                triggerPrint();
            }

            window.triggerAction = function (index) {
                const actionMap = {
                    0: ['.buttons-excel', '.buttons-excelHtml5', '.dt-button.buttons-excel'],
                    1: ['.buttons-pdf', '.buttons-pdfHtml5', '.dt-button.buttons-pdf'],
                    2: ['.buttons-print', '.dt-button.buttons-print'],
                };

                const button = findVisibleActionButton(actionMap[index] || []);
                if (button) {
                    button.click();
                    return;
                }

                if (index === 0) {
                    return exportTable('excel');
                }

                if (index === 1) {
                    return window.smartProbookExportElementToPdf();
                }

                triggerPrint();
            };

            function findFilterPanel(link) {
                const wrapper = link.closest('.content') || document;
                const candidates = Array.from(wrapper.querySelectorAll('#filter_inputs, .filter-card'));
                const visibleCandidate = candidates.find((el) => el.offsetParent !== null);
                return visibleCandidate || candidates[0] || null;
            }

            function toggleFilterPanel(panel) {
                if (!panel) return;
                if (window.jQuery) {
                    window.jQuery(panel).stop(true, true).slideToggle(150);
                    return;
                }
                const isHidden = window.getComputedStyle(panel).display === 'none';
                panel.style.display = isHidden ? 'block' : 'none';
            }

            function isFilterTrigger(link) {
                if (!link.classList.contains('popup-toggle') && !link.classList.contains('btn-filters')) return false;
                const title = ((link.getAttribute('title') || link.getAttribute('data-bs-original-title') || '')).toLowerCase();
                const text = (link.textContent || '').toLowerCase();
                const icon = link.querySelector('.fe-filter, img[alt="filter"], .filter-img-top');
                return title.includes('filter') || text.includes('filter') || !!icon;
            }

            function dedupeHeaderActions() {
                document.querySelectorAll('.filter-list').forEach((list) => {
                    const seen = new Set();
                    list.querySelectorAll('a.btn-filters, a.popup-toggle').forEach((btn) => {
                        const raw = ((btn.getAttribute('title') || btn.getAttribute('data-bs-original-title') || btn.textContent || '') || '')
                            .toLowerCase()
                            .replace(/\s+/g, ' ')
                            .trim();
                        if (!raw) return;
                        const key = raw.includes('filter') ? 'filter'
                            : raw.includes('print') ? 'print'
                            : raw.includes('download') ? 'download'
                            : raw;
                        if (seen.has(key) && (key === 'filter' || key === 'print' || key === 'download')) {
                            const li = btn.closest('li');
                            if (li) li.style.display = 'none';
                            return;
                        }
                        seen.add(key);
                    });
                });
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a,button');
                if (!link) return;

                const downloadItem = event.target.closest('.download-item');
                if (downloadItem) {
                    event.preventDefault();
                    const text = (downloadItem.textContent || '').toLowerCase();
                    if (text.includes('pdf')) return exportTable('pdf');
                    if (text.includes('excel') || text.includes('xls')) return exportTable('excel');
                    return exportTable('csv');
                }

                const hasPrintIcon = !!link.querySelector('.fe-printer, .fa-print, .feather-printer');
                const title = ((link.getAttribute('title') || link.getAttribute('data-bs-original-title') || '')).toLowerCase();
                const text = (link.textContent || '').toLowerCase();
                const inlineHandler = ((link.getAttribute('onclick') || '')).toLowerCase();
                const hasInlinePrintHandler = inlineHandler.includes('window.print')
                    || inlineHandler.includes('printpage(')
                    || inlineHandler.includes('print(');
                const looksLikePrint = title.includes('print') || text.includes('print') || hasPrintIcon;

                if (looksLikePrint && isDummyHref(link)) {
                    if (hasInlinePrintHandler) {
                        return;
                    }
                    event.preventDefault();
                    triggerPrint();
                    return;
                }

                if (isFilterTrigger(link) && isDummyHref(link)) {
                    event.preventDefault();
                    toggleFilterPanel(findFilterPanel(link));
                    return;
                }

                if (link.hasAttribute('download') && isDummyHref(link)) {
                    event.preventDefault();
                    const lowerText = (link.textContent || '').toLowerCase();
                    if (lowerText.includes('pdf')) return exportTable('pdf');
                    if (lowerText.includes('excel') || lowerText.includes('xls')) return exportTable('excel');
                    exportTable('csv');
                }
            });

            document.addEventListener('DOMContentLoaded', dedupeHeaderActions);
        })();
    </script>

    {{-- PREFERENCE SCRIPT: PRINTING (As requested in profile) --}}
    <script>
        window.onbeforeprint = function() {
            console.log("Preparing SmartProbook page for professional printing.");
        };
    </script>

    <script>
        (function () {
            const originalTitle = document.title || 'SmartProbook';
            let loadingTitleTimer = null;

            const showLoadingTitle = () => {
                if (loadingTitleTimer) {
                    clearTimeout(loadingTitleTimer);
                }

                loadingTitleTimer = window.setTimeout(function () {
                    document.title = 'Loading SmartProbook...';
                }, 90);
            };

            const restoreTitle = () => {
                if (loadingTitleTimer) {
                    clearTimeout(loadingTitleTimer);
                    loadingTitleTimer = null;
                }
                document.title = originalTitle;
            };

            const isRealNavigation = (element) => {
                if (!element) return false;

                if (element.closest('[data-bs-toggle], [data-toggle], [data-bs-dismiss], [data-dismiss], .dropdown-toggle, .popup-toggle, .mobile_btn, #toggle_btn, #mobile_btn')) {
                    return false;
                }

                if (element.tagName === 'A') {
                    const href = (element.getAttribute('href') || '').trim();
                    if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                        return false;
                    }
                    if (element.hasAttribute('download') || element.target === '_blank' || element.dataset.noLoader !== undefined) {
                        return false;
                    }
                    return true;
                }

                if (element.tagName === 'BUTTON') {
                    const form = element.form;
                    if (!form || element.type === 'button' || element.dataset.noLoader !== undefined) {
                        return false;
                    }
                    return !element.closest('.modal, .offcanvas');
                }

                return false;
            };

            window.SPBPageLoader = {
                show: showLoadingTitle,
                hide: restoreTitle,
            };

            document.addEventListener('DOMContentLoaded', restoreTitle, { once: true });
            window.addEventListener('load', restoreTitle, { once: true });
            window.addEventListener('pageshow', restoreTitle);
            window.addEventListener('beforeunload', showLoadingTitle);

            document.addEventListener('click', function (event) {
                const target = event.target.closest('a, button');
                if (!isRealNavigation(target)) return;
                showLoadingTitle();
            }, true);

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || form.dataset.noLoader !== undefined) return;
                showLoadingTitle();
            }, true);

            setTimeout(restoreTitle, 1600);
        })();
    </script>

</body>
</html>
