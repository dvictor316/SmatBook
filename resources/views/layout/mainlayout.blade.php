@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Cache;
    use App\Models\Setting;

    $route = Route::currentRouteName();
    $isPlatformAdminPage = auth()->check()
        && auth()->user()->isSuperAdmin()
        && request()->routeIs('super_admin.*');
    // Platform pages do not borrow a client's global company settings or cache.
    $layoutSettings = $isPlatformAdminPage ? [] : Setting::businessProfile();
    $siteTitle = $layoutSettings['company_name'] ?? 'SmartProbook';
    $defaultFavicon = 'assets/img/log-favicon.png';
    $defaultFaviconUrl = asset($defaultFavicon);
    $defaultFaviconVersion = file_exists(public_path($defaultFavicon)) ? filemtime(public_path($defaultFavicon)) : null;
    $faviconPath = Setting::mediaUrl(
        $layoutSettings['favicon'] ?? null,
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
    $isPosWorkspace = request()->routeIs('sales.showPos')
        || request()->is('pos');
    $isHotelWorkspace = request()->routeIs('hotel.*', 'super_admin.hotels.*')
        || request()->is('hotel*', 'superadmin/hotels*');
    $isLivestockWorkspace = request()->routeIs('livestock.*', 'super_admin.livestock.*')
        || request()->is('livestock*', 'superadmin/livestock*');
    $hideSidebar = ($hideSidebar ?? false) || $isPosWorkspace;
    $bodyClasses = [];
    $dashboardVariant = trim($__env->yieldContent('dashboard_variant'));
    if ($dashboardVariant !== '') { $bodyClasses[] = 'spb-dashboard'; }

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

    if ($isPosWorkspace) {
        $bodyClasses[] = 'pos-terminal-workspace';
    }

    if ($isHotelWorkspace) {
        $bodyClasses[] = 'hotel-workspace';
    }

    if ($isLivestockWorkspace) {
        $bodyClasses[] = 'livestock-workspace';
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
    @include('layout.partials.pwa-head')
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">

    {{-- CRITICAL: Prevent theme flickering on load --}}
    <script>
        (function() {
            let savedSettings = {};
            try { savedSettings = JSON.parse(localStorage.getItem('themeSettings')) || {}; } catch (_) {}
            Object.keys(savedSettings).forEach(key => {
                document.documentElement.setAttribute(key, savedSettings[key]);
            });
        })();
    </script>

    <link rel="shortcut icon" href="{{ $faviconPath }}">

    @include('layout.partials.head')
    @include('layout.partials.design-system')
    @include('layout.partials.compact-number-script')
    @yield('style')
    @if($isHotelWorkspace)
        @php
            $hotelSkinPath = 'assets/css/hotel-keto-skin.css';
            $hotelSkinVersion = file_exists(public_path($hotelSkinPath)) ? filemtime(public_path($hotelSkinPath)) : null;
        @endphp
        <link rel="stylesheet" href="{{ asset($hotelSkinPath) }}{{ $hotelSkinVersion ? '?v='.$hotelSkinVersion : '' }}">
    @endif

    @if($hideSidebar)
    <style>
        .page-wrapper, .main-wrapper {
            margin-left: 0 !important;
            padding-left: 0 !important;
            width: 100% !important;
        }
    </style>
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/app-shell.css') }}?v=20261022-icon-contrast">
    @stack('styles')
    @include('layout.partials.responsive-guardrails')
    @if($dashboardVariant !== '')
        <link rel="stylesheet" href="{{ asset('assets/css/dashboard-polish.css') }}?v={{ filemtime(public_path('assets/css/dashboard-polish.css')) }}">
    @endif
    <script>
        if (!window.__spbNavigationBridgeRegistered) {
            window.__spbNavigationBridgeRegistered = true;
            document.addEventListener('livewire:navigated', function () {
                document.dispatchEvent(new CustomEvent('smartprobook:page-loaded', {
                    detail: { url: window.location.href }
                }));
            });
        }
    </script>
</head>

<body @if($dashboardVariant !== '') data-dashboard="{{ $dashboardVariant }}" @endif @if(!empty($bodyClasses)) class="{{ implode(' ', $bodyClasses) }}" @endif>
    {{-- MAIN WRAPPER --}}
    @if ($isPosWorkspace)
        <div class="main-wrapper pos-main-wrapper" style="width:100vw;max-width:100vw;margin:0;padding:0;overflow-x:hidden;">
    @elseif (!in_array($route, [
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
            'reset-password',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'lock-screen',
            'login',
            'register',
            'saas-login',
            'saas-register',
            'saas-register-initial',
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
    @elseif (in_array($route, ['forgot-password', 'reset-password', 'password.request', 'password.email', 'password.reset', 'password.update', 'lock-screen', 'login', 'register', 'saas-login', 'saas-register', 'saas-register-initial']))
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
            'saas-register-initial',
            'forgot-password',
            'reset-password',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
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
            'saas-register-initial',
            'forgot-password',
            'reset-password',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
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
    <div id="spb-page-content" data-spb-fast-region="content">
    @include('layout.partials.flash-messages')
    @if (($isDemoWorkspace ?? false) && isset($demoCompany) && $demoCompany)
        @php
            $demoWorkspaceNotice = trim((string) session('info', ''));
            $demoWorkspaceNotice = $demoWorkspaceNotice !== '' ? $demoWorkspaceNotice : null;
        @endphp
        <style>
            .demo-top-strip {
                padding: 10px 14px 6px;
                box-sizing: border-box;
            }
            .demo-top-strip__card {
                width: min(100%, 1080px);
                margin: 0 auto;
                border: 1px solid rgba(14, 116, 144, 0.12);
                border-radius: 18px;
                background: linear-gradient(180deg, #f7fbff 0%, #eef7ff 100%);
                box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
                padding: 12px 14px;
            }
            .demo-top-strip__stack {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                text-align: center;
            }
            .demo-top-strip__copy {
                display: flex;
                flex-direction: column;
                gap: 6px;
                max-width: 900px;
            }
            .demo-top-strip__line {
                font-size: 13px;
                line-height: 1.35;
                color: #0f172a;
            }
            .demo-top-strip__line--alert {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 7px 14px;
                border: 1px solid rgba(220, 38, 38, 0.22);
                border-radius: 999px;
                background: linear-gradient(135deg, rgba(254, 240, 138, 0.97) 0%, rgba(254, 202, 202, 0.97) 100%);
                color: #991b1b;
                font-size: 12.5px;
                font-weight: 700;
                box-shadow: 0 10px 22px rgba(217, 119, 6, 0.14);
            }
            .demo-top-strip__line strong {
                color: #0f3d91;
            }
            .demo-top-strip__line--alert strong {
                color: #7f1d1d;
            }
            .demo-top-strip__line--muted {
                color: #475569;
                font-size: 12.5px;
            }
            .demo-top-strip__actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }
            .demo-top-strip__actions .btn {
                border-radius: 999px;
                min-width: 92px;
                font-weight: 700;
                padding: 0.42rem 0.9rem;
            }
            @media (min-width: 1200px) {
                body:not(.sidebar-collapsed):not(.mini-sidebar):not(.sidebar-icon-only) .demo-top-strip {
                    margin-left: var(--sb-sidebar-w, 270px);
                    width: calc(100% - var(--sb-sidebar-w, 270px));
                }

                body.sidebar-collapsed .demo-top-strip,
                body.mini-sidebar .demo-top-strip,
                body.sidebar-icon-only .demo-top-strip {
                    margin-left: var(--sb-sidebar-collapsed, 80px);
                    width: calc(100% - var(--sb-sidebar-collapsed, 80px));
                }
            }
            @media (max-width: 767.98px) {
                .demo-top-strip {
                    padding: 8px 10px 4px;
                }
                .demo-top-strip__card {
                    padding: 10px 12px;
                    border-radius: 14px;
                }
                .demo-top-strip__line {
                    font-size: 12.5px;
                }
                .demo-top-strip__line--alert {
                    width: 100%;
                    border-radius: 14px;
                    padding: 8px 10px;
                    font-size: 11.5px;
                }
                .demo-top-strip__line--muted {
                    font-size: 12px;
                }
                .demo-top-strip__actions {
                    gap: 6px;
                }
                .demo-top-strip__actions .btn {
                    min-width: auto;
                    padding: 0.38rem 0.8rem;
                }
            }
        </style>
        <div class="demo-top-strip">
            <div class="demo-top-strip__card">
                <div class="demo-top-strip__stack">
                    <div class="demo-top-strip__copy">
                        @if($demoWorkspaceNotice)
                            <div class="demo-top-strip__line">
                                <strong>Workspace Notice:</strong> {{ $demoWorkspaceNotice }}
                            </div>
                        @endif
                        <div class="demo-top-strip__line demo-top-strip__line--alert">
                            <strong>Demo Mode:</strong> Sample data only. Live billing, payouts, subscriptions, backups, and admin-only actions are disabled.
                            @if($demoCompany->demo_expires_at)
                                <strong class="ms-1">Expires {{ $demoCompany->demo_expires_at->diffForHumans() }}.</strong>
                            @endif
                        </div>
                        @if (($isDemoWorkspace ?? false) && empty($demoPreviewCustomer))
                            <div class="demo-top-strip__line demo-top-strip__line--muted">
                                <strong>Dashboard Preview:</strong> Switch between Starter, Basic, Pro, and Enterprise to see how each demo workspace looks.
                                @if(!empty($demoPreviewPlan))
                                    <span class="ms-1 badge bg-primary">{{ strtoupper($demoPreviewPlan === 'professional' ? 'PRO' : $demoPreviewPlan) }}</span>
                                @endif
                            </div>
                        @else
                            <div class="demo-top-strip__line demo-top-strip__line--muted">
                                <strong>Preview Customer:</strong> {{ $demoPreviewCustomer->customer_name ?? 'Demo Customer' }}
                                <span class="ms-1 badge bg-primary">{{ strtoupper(($demoPreviewPlan ?? 'basic') === 'professional' ? 'PRO' : ($demoPreviewPlan ?? 'basic')) }}</span>
                                <span class="ms-1">Preview stays locked to demo-only customer data.</span>
                            </div>
                        @endif
                    </div>
                    @if (($isDemoWorkspace ?? false) && empty($demoPreviewCustomer))
                        <div class="demo-top-strip__actions">
                            <a href="{{ route('demo.workspace.plan', ['plan' => 'starter']) }}" class="btn btn-sm btn-outline-primary">Starter</a>
                            <a href="{{ route('demo.workspace.plan', ['plan' => 'basic']) }}" class="btn btn-sm btn-outline-primary">Basic</a>
                            <a href="{{ route('demo.workspace.plan', ['plan' => 'pro']) }}" class="btn btn-sm btn-outline-primary">Pro</a>
                            <a href="{{ route('demo.workspace.plan', ['plan' => 'enterprise']) }}" class="btn btn-sm btn-outline-primary">Enterprise</a>
                            @if(Route::has('hotel.dashboard') && \App\Support\HotelAccess::userIsHotelTenant(auth()->user()))
                                <a href="{{ route('hotel.dashboard') }}" class="btn btn-sm btn-outline-secondary">Hotel</a>
                            @endif
                            <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">Customers</a>
                            <a href="{{ route('reports.hub') }}" class="btn btn-sm btn-outline-secondary">Reports</a>
                        </div>
                    @else
                        <div class="demo-top-strip__actions">
                            <a href="{{ route('customers.demo-preview', ['id' => $demoPreviewCustomer->id, 'plan' => 'starter']) }}" class="btn btn-sm btn-outline-primary">Starter</a>
                            <a href="{{ route('customers.demo-preview', ['id' => $demoPreviewCustomer->id, 'plan' => 'basic']) }}" class="btn btn-sm btn-outline-primary">Basic</a>
                            <a href="{{ route('customers.demo-preview', ['id' => $demoPreviewCustomer->id, 'plan' => 'pro']) }}" class="btn btn-sm btn-outline-primary">Pro</a>
                            <a href="{{ route('customers.demo-preview', ['id' => $demoPreviewCustomer->id, 'plan' => 'enterprise']) }}" class="btn btn-sm btn-outline-primary">Enterprise</a>
                            <a href="{{ route('add-invoice', ['customer_id' => $demoPreviewCustomer->id]) }}" class="btn btn-sm btn-outline-secondary">New Invoice</a>
                            <a href="{{ route('sales.create', ['customer_id' => $demoPreviewCustomer->id]) }}" class="btn btn-sm btn-outline-secondary">New Sale</a>
                            <a href="{{ route('reports.customer-statement', $demoPreviewCustomer->id) }}" class="btn btn-sm btn-outline-secondary">Statement</a>
                            @if(Route::has('hotel.dashboard') && \App\Support\HotelAccess::userIsHotelTenant(auth()->user()))
                                <a href="{{ route('hotel.dashboard') }}" class="btn btn-sm btn-outline-secondary">Hotel</a>
                            @endif
                            <a href="{{ route('reports.hub') }}" class="btn btn-sm btn-outline-secondary">Reports</a>
                            <a href="{{ route('demo.customer-preview.stop') }}" class="btn btn-sm btn-outline-danger">Exit Preview</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    @php
        $needsTenantBranch = !in_array($route, [
            'landing.index', 'index-five', 'mail-pay-invoice', 'cashreceipt-1', 'cashreceipt-2', 'cashreceipt-3', 'cashreceipt-4',
            'invoice-four-a', 'invoice-one-a', 'invoice-three', 'invoice-two', 'forgot-password', 'reset-password', 'password.request', 'password.email', 'password.reset', 'password.update', 'lock-screen', 'login', 'register',
            'saas-login', 'saas-register', 'saas-register-initial', 'contact-messages',
            'manager.verification.form', 'manager.pending.notice', 'submit.verification', 'manager.submit.verification',
        ]) && !str_starts_with((string) $route, 'deployment.')
           && !str_starts_with((string) $route, 'agent.')
           && !str_starts_with((string) $route, 'reports.')
           && !str_starts_with((string) $route, 'saas.')
           && !$isPlatformAdminPage;
    @endphp
    @php
        $allBranchesScopeActive = session('active_branch_scope') === 'all';

        // If session lost tenant/branch (common on mobile), restore from the user model.
        if ($needsTenantBranch && auth()->check()) {
            $__user = auth()->user();
            if (!session('current_tenant_id') && !empty($__user->company_id)) {
                session(['current_tenant_id' => $__user->company_id]);
            }
            if (!$allBranchesScopeActive && !session('active_branch_id')) {
                app(\App\Support\ActiveBranchResolver::class)->ensureSession($__user);
            }
        }
    @endphp
    @if ($needsTenantBranch && (!session('current_tenant_id') || (!session('active_branch_id') && !$allBranchesScopeActive)))
        <div class="alert alert-info my-5 text-center" style="font-size:1.2em;">
            <strong>No data available.</strong><br>
            Please complete setup and select a branch to begin using your workspace.
        </div>
    @else
        @php
            $showDesktopBackButton = !in_array($route, [
                'landing.index',
                'index-five',
                'home',
                'user.dashboard',
                'tenant.dashboard',
                'deployment.dashboard',
                'workspace.business',
                'workspace.business.dashboard',
                'sales.showPos',
                'pos.return.show',
                'login',
                'register',
                'saas-login',
                'saas-register',
                'saas-register-initial',
                'forgot-password',
                'reset-password',
                'password.request',
                'password.email',
                'password.reset',
                'password.update',
                'lock-screen',
                'saas.checkout',
                'saas.setup',
                'saas.success',
            ], true)
            && !str_starts_with((string) $route, 'saas.')
            && !str_starts_with((string) $route, 'pos.')
            && !str_starts_with((string) $route, 'landing.')
            && !request()->is('saas/*', 'pos', 'pos/*');
        @endphp
        @if($showDesktopBackButton)
            <div class="spb-desktop-backbar is-visible" id="spbDesktopBackbar">
                <button type="button" class="spb-desktop-backbar__btn" id="spbDesktopBackButton" aria-label="Go back" title="Go back" data-fallback-url="{{ route('home') }}">
                    <i class="fa fa-arrow-left"></i>
                </button>
            </div>
        @endif
        @yield('content')
    @endif
    </div>

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
            'saas-register-initial',
            'forgot-password',
            'reset-password',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
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
            'reset-password',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'lock-screen',
            'login',
            'register',
            'saas-login',
            'saas-register',
            'saas-register-initial',
        ]))
        </div>
    @endif

    {{-- THEME SETTINGS --}}
    @if (
        (!$hideSidebar || $isPosWorkspace)
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
            'saas-register-initial',
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

    @include('layout.partials.pwa-install')
    <script src="{{ asset('js/pos-offline-auth.js') }}"></script>

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
    (function () {
        function setupDesktopBackButton() {
            var backbar = document.getElementById('spbDesktopBackbar');
            var backButton = document.getElementById('spbDesktopBackButton');
            if (!backbar || !backButton) return;

            function hasUsefulPreviousPage() {
                if (window.history.length > 1) return true;

                try {
                    if (!document.referrer) return false;
                    var referrer = new URL(document.referrer);
                    return referrer.origin === window.location.origin && referrer.href !== window.location.href;
                } catch (error) {
                    return false;
                }
            }

            backbar.classList.add('is-visible');

            if (backButton.dataset.bound === 'true') return;
            backButton.dataset.bound = 'true';
            backButton.addEventListener('click', function () {
                if (hasUsefulPreviousPage()) {
                    window.history.back();
                    return;
                }

                window.location.href = backButton.dataset.fallbackUrl || '{{ route('home') }}';
            });
        }

        document.addEventListener('DOMContentLoaded', setupDesktopBackButton);
        document.addEventListener('smartprobook:page-loaded', setupDesktopBackButton);
        window.addEventListener('resize', setupDesktopBackButton);
    })();
    </script>

    <script>
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.dropdown [data-bs-toggle="dropdown"]');

        if (!trigger) {
            return;
        }

        const href = (trigger.getAttribute('href') || '').trim().toLowerCase();

        if (href === '#' || href === 'javascript:void(0);') {
            event.preventDefault();
        }
    }, true);
    </script>

    <script>
    (function () {
        let mobileSheet = null;
        let mobileSheetPanel = null;
        let mobileSheetContent = null;
        let mobileSheetTitle = null;
        let mobileSheetOpen = false;
        let desktopMenu = null;
        let desktopMenuOpen = false;
        let desktopMenuTrigger = null;
        let lastFocusedTrigger = null;
        const floatingMenuState = new Map();
        const managedAncestorSelectors = [
            '.table-responsive',
            '.dataTables_wrapper',
            '.dataTables_scrollBody',
            '.card-body'
        ];

        function getManagedAncestors(dropdown) {
            return managedAncestorSelectors
                .map((selector) => dropdown.closest(selector))
                .filter((element, index, collection) => element && collection.indexOf(element) === index);
        }

        function isActionTrigger(trigger) {
            if (!trigger) {
                return false;
            }

            if (trigger.matches('.btn-action-icon, .action-icon, .product-action-trigger')) {
                return true;
            }

            if (trigger.dataset.sheetTitle) {
                return true;
            }

            const dropdown = trigger.closest('.dropdown-action');
            if (dropdown) {
                return true;
            }

            return !!trigger.closest('table, .table-responsive, .dataTables_wrapper');
        }

        function prefersNativeDesktopDropdown(trigger) {
            return !!trigger?.closest('[data-spb-native-desktop-dropdown="true"]');
        }

        function prefersClonedDesktopDropdown(trigger) {
            return !!trigger?.closest('[data-spb-desktop-clone-dropdown="true"]');
        }

        function shouldOpenDropdownContainers(trigger) {
            if (isPhoneSheetMode()) {
                return false;
            }

            if (!isActionTrigger(trigger)) {
                return true;
            }

            return prefersNativeDesktopDropdown(trigger);
        }

        function markActionMenu(trigger) {
            if (!isActionTrigger(trigger)) {
                return;
            }

            const menu = trigger?.closest('.dropdown')?.querySelector('.dropdown-menu');
            if (menu) {
                menu.classList.add('spb-action-dropdown-menu');
            }
        }

        function openDropdownContainers(dropdown) {
            getManagedAncestors(dropdown).forEach((element) => {
                element.classList.add('spb-dropdown-open');
            });
        }

        function closeDropdownContainers(dropdown) {
            getManagedAncestors(dropdown).forEach((element) => {
                element.classList.remove('spb-dropdown-open');
            });
        }

        function isPhoneSheetMode() {
            return window.matchMedia('(max-width: 767.98px)').matches;
        }

        function ensureDesktopMenu() {
            if (desktopMenu) {
                return;
            }

            desktopMenu = document.createElement('div');
            desktopMenu.className = 'dropdown-menu spb-action-dropdown-menu spb-desktop-action-menu';
            document.body.appendChild(desktopMenu);

            desktopMenu.addEventListener('click', function (event) {
                const action = event.target.closest('a.dropdown-item, button.dropdown-item');
                if (action && !action.hasAttribute('data-bs-toggle')) {
                    window.setTimeout(closeDesktopMenu, 40);
                }
            });
        }

        function closeDesktopMenu() {
            if (!desktopMenu) {
                return;
            }

            desktopMenu.classList.remove('show', 'is-open');
            desktopMenu.innerHTML = '';
            desktopMenuTrigger = null;
            desktopMenuOpen = false;
        }

        function positionDesktopMenu(trigger) {
            if (!desktopMenu || !trigger) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            const gutter = 12;
            const menuWidth = Math.max(desktopMenu.offsetWidth || 220, 220);
            const menuHeight = Math.max(desktopMenu.offsetHeight || 0, 0);
            let top = rect.bottom + 8;
            let left = rect.right - menuWidth;

            if (menuHeight && top + menuHeight > window.innerHeight - gutter) {
                top = Math.max(gutter, rect.top - menuHeight - 8);
            }

            if (left < gutter) {
                left = gutter;
            }

            if (left + menuWidth > window.innerWidth - gutter) {
                left = Math.max(gutter, window.innerWidth - menuWidth - gutter);
            }

            desktopMenu.style.top = `${top}px`;
            desktopMenu.style.left = `${left}px`;
            desktopMenu.style.right = 'auto';
            desktopMenu.style.bottom = 'auto';
            desktopMenu.style.minWidth = `${Math.max(rect.width, 190)}px`;
            desktopMenu.style.maxWidth = `min(320px, calc(100vw - ${gutter * 2}px))`;
            desktopMenu.style.transform = 'none';
            desktopMenu.style.inset = 'auto auto auto auto';
        }

        function openDesktopMenu(trigger, dropdown) {
            if (isPhoneSheetMode() || !trigger || !dropdown) {
                return false;
            }

            const sourceMenu = dropdown.querySelector('.dropdown-menu');
            if (!sourceMenu) {
                return false;
            }

            ensureDesktopMenu();

            if (desktopMenuOpen && desktopMenuTrigger === trigger) {
                closeDesktopMenu();
                return true;
            }

            const clonedMenu = sourceMenu.cloneNode(true);
            clonedMenu.classList.remove('show');
            clonedMenu.style.display = 'block';
            clonedMenu.style.position = 'static';
            clonedMenu.style.transform = 'none';
            clonedMenu.style.inset = 'auto';
            clonedMenu.style.margin = '0';
            clonedMenu.style.minWidth = '0';
            clonedMenu.style.maxWidth = 'none';

            desktopMenu.innerHTML = '';
            while (clonedMenu.firstChild) {
                desktopMenu.appendChild(clonedMenu.firstChild);
            }

            desktopMenuTrigger = trigger;
            desktopMenu.classList.add('show', 'is-open');
            desktopMenuOpen = true;
            positionDesktopMenu(trigger);
            return true;
        }

        function getActionMenu(trigger) {
            return trigger?.closest('.dropdown')?.querySelector('.dropdown-menu') || null;
        }

        function findFloatingMenuByTrigger(trigger) {
            for (const [menu, state] of floatingMenuState.entries()) {
                if (state.trigger === trigger) {
                    return { menu, state };
                }
            }

            return null;
        }

        function positionFloatingMenu(trigger, menu) {
            if (!trigger || !menu) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const menuWidth = Math.max(menu.offsetWidth || 220, 220);
            const menuHeight = Math.max(menu.offsetHeight || 0, 0);
            const gutter = 12;

            let top = rect.bottom + 8;
            if (menuHeight && top + menuHeight > viewportHeight - gutter) {
                top = Math.max(gutter, rect.top - menuHeight - 8);
            }

            let left = rect.right - menuWidth;
            if (left < gutter) {
                left = gutter;
            }
            if (left + menuWidth > viewportWidth - gutter) {
                left = Math.max(gutter, viewportWidth - menuWidth - gutter);
            }

            menu.style.position = 'fixed';
            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;
            menu.style.right = 'auto';
            menu.style.bottom = 'auto';
            menu.style.zIndex = '2100';
            menu.style.minWidth = `${Math.max(rect.width, 190)}px`;
            menu.style.maxWidth = `min(320px, calc(100vw - ${gutter * 2}px))`;
            menu.style.transform = 'none';
            menu.style.inset = 'auto auto auto auto';
        }

        function floatDesktopMenu(trigger) {
            if (isPhoneSheetMode() || !isActionTrigger(trigger) || prefersNativeDesktopDropdown(trigger)) {
                return;
            }

            const menu = getActionMenu(trigger);
            if (!menu || floatingMenuState.has(menu)) {
                if (menu) {
                    positionFloatingMenu(trigger, menu);
                }
                return;
            }

            const originalParent = menu.parentNode;
            const originalNextSibling = menu.nextSibling;

            document.body.appendChild(menu);
            menu.classList.add('spb-action-dropdown-menu', 'spb-action-dropdown-menu--floating');
            floatingMenuState.set(menu, {
                trigger,
                originalParent,
                originalNextSibling,
            });

            positionFloatingMenu(trigger, menu);
        }

        function restoreFloatingMenu(trigger) {
            const found = findFloatingMenuByTrigger(trigger);
            const menu = found?.menu || null;
            const state = found?.state || null;

            if (!menu || !state) {
                return;
            }

            if (state.originalNextSibling && state.originalNextSibling.parentNode === state.originalParent) {
                state.originalParent.insertBefore(menu, state.originalNextSibling);
            } else {
                state.originalParent.appendChild(menu);
            }

            menu.classList.remove('spb-action-dropdown-menu--floating');
            menu.style.position = '';
            menu.style.top = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.bottom = '';
            menu.style.zIndex = '';
            menu.style.minWidth = '';
            menu.style.maxWidth = '';
            menu.style.transform = '';
            menu.style.inset = '';
            menu.style.visibility = '';
            floatingMenuState.delete(menu);
        }

        function repositionOpenFloatingMenus() {
            floatingMenuState.forEach(function (state, menu) {
                if (menu.classList.contains('show')) {
                    positionFloatingMenu(state.trigger, menu);
                }
            });
        }

        function ensureMobileSheet() {
            if (mobileSheet) {
                return;
            }

            mobileSheet = document.createElement('div');
            mobileSheet.className = 'spb-mobile-action-sheet';
            mobileSheet.innerHTML = `
                <div class="spb-mobile-action-sheet__backdrop" data-spb-sheet-close="true"></div>
                <div class="spb-mobile-action-sheet__panel" role="dialog" aria-modal="true" aria-label="Action menu">
                    <div class="spb-mobile-action-sheet__handle"></div>
                    <div class="spb-mobile-action-sheet__title">Actions</div>
                    <div class="spb-mobile-action-sheet__content"></div>
                </div>
            `;

            document.body.appendChild(mobileSheet);
            mobileSheetPanel = mobileSheet.querySelector('.spb-mobile-action-sheet__panel');
            mobileSheetContent = mobileSheet.querySelector('.spb-mobile-action-sheet__content');
            mobileSheetTitle = mobileSheet.querySelector('.spb-mobile-action-sheet__title');

            mobileSheet.addEventListener('click', function (event) {
                if (event.target.closest('[data-spb-sheet-close="true"]')) {
                    closeMobileSheet();
                    return;
                }

                const action = event.target.closest('a.dropdown-item, button.dropdown-item');
                if (action && !action.hasAttribute('data-bs-toggle')) {
                    window.setTimeout(closeMobileSheet, 40);
                }
            });
        }

        function closeMobileSheet() {
            if (!mobileSheet) {
                return;
            }

            mobileSheet.classList.remove('is-open');
            document.body.classList.remove('spb-mobile-sheet-open');
            mobileSheetOpen = false;
            mobileSheetContent.innerHTML = '';

            if (lastFocusedTrigger && typeof lastFocusedTrigger.focus === 'function') {
                lastFocusedTrigger.focus({ preventScroll: true });
            }
        }

        function openMobileSheet(trigger, dropdown) {
            ensureMobileSheet();

            const sourceMenu = dropdown?.querySelector('.dropdown-menu');
            if (!sourceMenu) {
                return false;
            }

            const clonedMenu = sourceMenu.cloneNode(true);
            clonedMenu.classList.remove('show');
            clonedMenu.style.display = 'block';
            clonedMenu.style.position = 'static';
            clonedMenu.style.transform = 'none';
            clonedMenu.style.inset = 'auto';
            clonedMenu.style.margin = '0';
            clonedMenu.style.border = '0';
            clonedMenu.style.boxShadow = 'none';
            clonedMenu.style.minWidth = '0';
            clonedMenu.style.width = '100%';
            clonedMenu.style.background = 'transparent';
            clonedMenu.style.padding = '0';

            mobileSheetTitle.textContent = trigger.dataset.sheetTitle || trigger.getAttribute('aria-label') || 'Actions';
            mobileSheetContent.innerHTML = '';
            mobileSheetContent.appendChild(clonedMenu);

            lastFocusedTrigger = trigger;
            mobileSheet.classList.add('is-open');
            document.body.classList.add('spb-mobile-sheet-open');
            mobileSheetOpen = true;
            return true;
        }

        document.addEventListener('show.bs.dropdown', function (event) {
            const dropdown = event.target.closest('.dropdown');
            if (!dropdown) {
                return;
            }

            const trigger = event.relatedTarget || dropdown.querySelector('[data-bs-toggle="dropdown"]');
            markActionMenu(trigger);
            if (shouldOpenDropdownContainers(trigger)) {
                openDropdownContainers(dropdown);
            }

            // Hide temporarily to prevent position flash while we float the menu to body
            if (isActionTrigger(trigger) && !isPhoneSheetMode() && !prefersNativeDesktopDropdown(trigger)) {
                const menu = dropdown.querySelector('.dropdown-menu');
                if (menu) {
                    menu.style.visibility = 'hidden';
                }
            }
        });

        document.addEventListener('hide.bs.dropdown', function (event) {
            const dropdown = event.target.closest('.dropdown');
            if (!dropdown) {
                return;
            }

            const trigger = event.relatedTarget || dropdown.querySelector('[data-bs-toggle="dropdown"]');
            restoreFloatingMenu(trigger);
            if (shouldOpenDropdownContainers(trigger)) {
                closeDropdownContainers(dropdown);
            }
            // Reset visibility in case hide fires before shown (edge case)
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.visibility = '';
            }
        });

        document.addEventListener('shown.bs.dropdown', function (event) {
            const dropdown = event.target.closest('.dropdown');
            if (!dropdown) {
                return;
            }

            const trigger = event.relatedTarget || dropdown.querySelector('[data-bs-toggle="dropdown"]');
            floatDesktopMenu(trigger);
            // Restore visibility after floating and repositioning
            if (isActionTrigger(trigger) && !isPhoneSheetMode() && !prefersNativeDesktopDropdown(trigger)) {
                const found = findFloatingMenuByTrigger(trigger);
                const menu = found ? found.menu : dropdown.querySelector('.dropdown-menu');
                if (menu) {
                    menu.style.visibility = '';
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (desktopMenuOpen && desktopMenu && !event.target.closest('.spb-desktop-action-menu') && !event.target.closest('[data-spb-desktop-clone-dropdown="true"]')) {
                closeDesktopMenu();
            }

            const trigger = event.target.closest('.dropdown [data-bs-toggle="dropdown"]');
            if (!isActionTrigger(trigger)) {
                return;
            }

            markActionMenu(trigger);
            const dropdown = trigger.closest('.dropdown');

            if (isPhoneSheetMode()) {
                event.preventDefault();
                event.stopPropagation();
                openMobileSheet(trigger, dropdown);
                return;
            }

            if (prefersClonedDesktopDropdown(trigger)) {
                event.preventDefault();
                event.stopPropagation();
                openDesktopMenu(trigger, dropdown);
                return;
            }

            // Desktop: let Bootstrap handle show/hide naturally.
            // show.bs.dropdown hides the menu temporarily to prevent flash,
            // shown.bs.dropdown floats it to body and repositions it.
        }, true);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && desktopMenuOpen) {
                closeDesktopMenu();
                return;
            }

            if (event.key === 'Escape' && mobileSheetOpen) {
                closeMobileSheet();
            }
        });

        window.addEventListener('resize', function () {
            if (desktopMenuOpen && desktopMenuTrigger) {
                positionDesktopMenu(desktopMenuTrigger);
            }

            if (!isPhoneSheetMode() && mobileSheetOpen) {
                closeMobileSheet();
            }

            repositionOpenFloatingMenus();
        });

        window.addEventListener('scroll', function () {
            if (desktopMenuOpen && desktopMenuTrigger) {
                positionDesktopMenu(desktopMenuTrigger);
            }
        }, true);

        window.addEventListener('scroll', repositionOpenFloatingMenus, true);
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

                if (document.body.classList.contains('report-workspace')) {
                    const reportWorkspaceSelectors = [
                        '.page-wrapper .content.container-fluid',
                        '.page-wrapper .content',
                        '.content.container-fluid',
                        '.content',
                    ];

                    for (const selector of reportWorkspaceSelectors) {
                        const match = Array.from(document.querySelectorAll(selector)).find((el) => isVisible(el));
                        if (match) {
                            return match;
                        }
                    }
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

                if (link.matches('button[type="submit"], input[type="submit"]')) {
                    return;
                }

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
        window.SPBPageLoader = { show: function () {}, hide: function () {} };
    </script>

</body>
</html>
