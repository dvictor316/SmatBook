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
        $seoTitle = 'Smatprobook';
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

    @stack('scripts')

</body>
</html>
