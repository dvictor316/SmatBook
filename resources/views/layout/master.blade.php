<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    @php
        $routeName = Route::currentRouteName();
        $seoNoIndex = in_array($routeName, [
            'login', 'register', 'saas-login', 'saas-register',
            'password.request', 'password.reset', 'password.update',
            'saas.checkout', 'saas.setup', 'saas.success', 'saas.payment.success', 'saas.payment.cancel',
        ], true);
        $seoTitle = 'SmartProbook';
    @endphp
    @include('layout.partials.seo-meta')

    @php
        /** 1. ROUTE & ASSET LOGIC */
        $assetGroups = [
            'invoices' => ['invoices', 'invoice-*', 'signature-invoice', 'recurring-invoices'],
            'editors'  => ['text-editor', 'add-products', 'edit-products', 'all-blogs'],
            'forms'    => ['form-*', 'add-customer', 'edit-customer', 'testimonials'],
            'maps'     => ['maps-vector']
        ];

        $isInvoice = Str::is($assetGroups['invoices'], $routeName);
        $isEditor  = Str::is($assetGroups['editors'], $routeName);
        $isForm    = Str::is($assetGroups['forms'], $routeName);
        $isMap     = Str::is($assetGroups['maps'], $routeName);

        /** 2. USER DATA */
        $user = Auth::user();
        $profileImg = ($user && $user->profile_picture) 
            ? asset('storage/profiles/'.$user->profile_picture) 
            : asset('assets/img/profiles/avatar-07.jpg');

        /** 3. SUBSCRIPTION LOGIC */
        $host = request()->getHost();
        $subdomain = explode('.', $host)[0];
        $domainRecord = \App\Models\Domain::where('domain_name', $subdomain)->first();
        $daysRemaining = $domainRecord ? now()->diffInDays($domainRecord->expiry_date, false) : 99;
    @endphp

    <link rel="shortcut icon" href="{{ asset('assets/img/logos.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">

    @if (file_exists(public_path('assets/plugins/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @endif

    @if($isInvoice) <link rel="stylesheet" href="{{ asset('assets/css/feather.css') }}"> @endif
    @if($isEditor) <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-lite.min.css') }}"> @endif
    @if($isForm) <link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/intlTelInput.css') }}"> @endif
    @if($isMap) <link rel="stylesheet" href="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.css') }}"> @endif

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @include('layout.partials.design-system')
    
    <style>
        .spb-page-loader {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(251, 191, 36, 0.12), transparent 28%),
                rgba(248, 251, 255, 0.96);
            backdrop-filter: blur(8px);
            z-index: 100000;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.28s ease, visibility 0.28s ease;
        }

        .spb-page-loader.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .spb-page-loader__card {
            min-width: min(88vw, 320px);
            max-width: 360px;
            padding: 22px 20px;
            border-radius: 22px;
            border: 1px solid rgba(191, 219, 254, 0.92);
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 22px 48px rgba(37, 99, 235, 0.12);
            text-align: center;
        }

        .spb-page-loader__brand {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 14px;
            color: #143b85;
            font-size: clamp(1rem, 2.6vw, 1.18rem);
            font-weight: 800;
        }

        .spb-page-loader__brand img {
            width: clamp(34px, 7vw, 46px);
            height: auto;
        }

        .spb-page-loader__spinner {
            width: clamp(44px, 10vw, 56px);
            height: clamp(44px, 10vw, 56px);
            margin: 0 auto 14px;
            border-radius: 50%;
            border: 4px solid rgba(37, 99, 235, 0.16);
            border-top-color: #2563eb;
            border-right-color: #f59e0b;
            animation: spb-loader-spin 0.8s linear infinite;
        }

        .spb-page-loader__title {
            color: #183153;
            font-size: clamp(0.96rem, 2.5vw, 1.05rem);
            font-weight: 800;
        }

        .spb-page-loader__text {
            margin-top: 6px;
            color: #5e7294;
            font-size: clamp(0.82rem, 2.15vw, 0.9rem);
            line-height: 1.5;
        }

        @keyframes spb-loader-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .subscription-banner {
            background: linear-gradient(90deg, #fff3cd 0%, #ffeeba 100%);
            color: #856404;
            padding: 10px;
            text-align: center;
            font-size: 14px;
            border-bottom: 1px solid #ffeeba;
            position: sticky;
            top: 0;
            z-index: 1050;
        }
        /* Adjust header position if banner is present */
        .has-banner .header { top: 40px !important; }
        .has-banner .sidebar { margin-top: 40px !important; }
    </style>
</head>
<body class="{{ ($domainRecord && $daysRemaining <= 7 && $daysRemaining >= 0) ? 'has-banner' : '' }}">
    <div id="spbPageLoader" class="spb-page-loader" aria-live="polite" aria-busy="true">
        <div class="spb-page-loader__card">
            <div class="spb-page-loader__brand">
                <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook logo">
                <span>SmartProbook</span>
            </div>
            <div class="spb-page-loader__spinner" aria-hidden="true"></div>
            <div class="spb-page-loader__title">Loading page</div>
            <div class="spb-page-loader__text">Preparing your workspace and content.</div>
        </div>
    </div>

    <div class="main-wrapper">
        
        {{-- 1. SUBSCRIPTION EXPIRY BANNER --}}
        @if($domainRecord && $daysRemaining <= 7 && $daysRemaining >= 0)
            <div class="subscription-banner shadow-sm d-flex align-items-center justify-content-center">
                <i class="fas fa-clock me-2"></i>
                <span>Your subscription expires in <strong>{{ ceil($daysRemaining) }} days</strong>.</span>
                <a href="{{ config('app.url') . '/checkout' }}" class="btn btn-warning btn-sm ms-3 py-0 px-3 fw-bold shadow-sm" style="border-radius: 50px;">
                    Renew Now
                </a>
            </div>
        @endif

        <header class="header {{ Route::is('index-two') ? 'header-two' : 'header-one' }}">
            <div class="header-left">
                <a href="{{ url('/') }}" class="logo">
                    <img src="{{ asset('assets/img/logos.png') }}" alt="Logo">
                </a>
                <a href="{{ url('/') }}" class="logo logo-small">
                    <img src="{{ asset('assets/img/logos.png') }}" alt="Logo">
                </a>
            </div>

            <a href="javascript:void(0);" id="toggle_btn"><i class="fas fa-bars"></i></a>
            <a class="mobile_btn" id="mobile_btn"><i class="fas fa-bars"></i></a>

            <div class="top-nav-search">
                <form>
                    <input type="text" class="form-control" placeholder="Search here">
                    <button class="btn" type="submit"><i class="fa fa-search"></i></button>
                </form>
            </div>

            <ul class="nav user-menu">
                <li class="nav-item dropdown has-arrow main-drop">
                    <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                        <span class="user-img">
                            <img src="{{ $profileImg }}" alt="Profile">
                            <span class="status online"></span>
                        </span>
                        <span>{{ $user->name ?? 'Admin' }}</span>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ url('profile') }}"><i data-feather="user" class="me-1"></i> Profile</a>
                        <a class="dropdown-item" href="{{ url('settings') }}"><i data-feather="settings" class="me-1"></i> Settings</a>
                        <hr class="m-0">
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i data-feather="log-out" class="me-1"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
            <form id="logout-form" action="{{ route('emergency.logout') }}" method="POST" class="d-none">@csrf</form>
        </header>

        <div class="two-col-bar" id="two-col-bar">
            <div class="sidebar sidebar-three" id="sidebar">
                <div class="sidebar-inner slimscroll">
                    <div id="sidebar-menu" class="sidebar-menu sidebar-menu-three">
                        <aside id="aside" class="ui-aside">
                            <ul class="tab nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="tablinks nav-link {{ Request::is('index', '/', 'customers*', 'inventory*', 'invoice*', 'purchases*') ? 'active' : '' }}" 
                                       id="home-tab" data-bs-toggle="tab" data-bs-target="#home" role="tab">
                                        <i class="fe fe-airplay"></i>
                                    </a>
                                </li>
                            </ul>
                        </aside>

                        <div class="tab-content tab-content-three">
                            <ul class="tab-pane {{ Request::is('index', '/', 'customers*', 'inventory*', 'invoice*', 'purchases*') ? 'show active' : '' }}" id="home">
                                <li class="menu-title"><span>Main Dashboard</span></li>
                                <li><a class="{{ Request::is('index', '/') ? 'active' : '' }}" href="{{ url('index') }}"><i class="fe fe-home"></i> <span>Admin Dashboard</span></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="content container-fluid">
                
                {{-- Global Flash Notifications --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Page Content Injection --}}
                @yield('content')
                
            </div>
        </div>

    </div>

    @if (!Route::is([
        'login',
        'register',
        'saas-login',
        'saas-register',
        'forgot-password',
        'reset-password',
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'saas.checkout',
    ]) && !request()->is('saas/checkout*', 'forgot-password*', 'reset-password*', 'password/*'))
        @include('layout.partials.ai-quick-agent')
    @endif

    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/feather/feather.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
    
    @if(!Route::is('index-two'))
        <script src="{{ asset('assets/js/layout.js') }}"></script>
    @endif

    <script src="{{ asset('assets/js/script.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof feather !== 'undefined') { feather.replace(); }
        });
    </script>

    <script>
        (function () {
            const loader = document.getElementById('spbPageLoader');
            if (!loader) return;

            const hideLoader = () => loader.classList.add('is-hidden');
            const showLoader = () => loader.classList.remove('is-hidden');

            const isRealNavigation = (element) => {
                if (!element) return false;

                if (element.closest('[data-bs-toggle], [data-toggle], [data-bs-dismiss], [data-dismiss], .dropdown-toggle, .mobile_btn, #toggle_btn, #mobile_btn')) {
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

            document.addEventListener('DOMContentLoaded', hideLoader, { once: true });
            window.addEventListener('load', hideLoader, { once: true });
            window.addEventListener('pageshow', hideLoader);
            window.addEventListener('beforeunload', showLoader);

            document.addEventListener('click', function (event) {
                const target = event.target.closest('a, button');
                if (!isRealNavigation(target)) return;
                showLoader();
            }, true);

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || form.dataset.noLoader !== undefined) return;
                showLoader();
            }, true);

            setTimeout(hideLoader, 1600);
        })();
    </script>

    @stack('scripts')

</body>
</html>
