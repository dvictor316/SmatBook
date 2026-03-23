<nav style="position: fixed; width: 100%; top: 0; z-index: 9999; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px); border-bottom: 1px solid #e2e8f0; height: 68px; display: flex; align-items: center;">
    <style>
        .brand-img {
            height: 56px !important;
            width: auto;
            object-fit: contain;
        }
        .spb-nav-wordmark {
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: #0b2a63;
            line-height: 1;
            white-space: nowrap;
        }
        .spb-nav-wordmark .book { color: #dc2626; }
        @media (max-width: 991px) {
            .brand-img { height: 44px !important; }
            .spb-nav-wordmark { font-size: .84rem; }
        }
        @media (max-width: 480px) {
            .brand-img { height: 32px !important; }
            .logo-container { gap: 4px; }
            .spb-nav-wordmark {
                display: inline-block;
                font-size: .62rem;
                letter-spacing: -0.15px;
            }
        }
        @media (max-width: 360px) {
            .brand-img { height: 28px !important; }
            .spb-nav-wordmark {
                font-size: .54rem;
                letter-spacing: -0.1px;
            }
        }
    </style>
    <div class="nav-container" style="max-width: 1400px; margin: 0 auto; width: 100%; padding: 0 40px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ url('/') }}" class="logo-container d-flex align-items-center text-decoration-none">
            <img src="{{ asset('assets/img/logos.png') }}" class="brand-img me-2" alt="SmartProbook"> 
            <span class="spb-nav-wordmark">SmartPro<span class="book">book</span></span>
        </a>
        
        <div class="hamburger d-lg-none" id="navTrigger" style="cursor: pointer; display: flex; flex-direction: column; gap: 5px;">
            <span style="width: 25px; height: 3px; background: #020617;"></span>
            <span style="width: 25px; height: 3px; background: #020617;"></span>
            <span style="width: 25px; height: 3px; background: #020617;"></span>
        </div>

        <ul class="nav-links d-none d-lg-flex list-unstyled m-0" id="mainMenu" style="gap: 25px; align-items: center;">
            <li><a href="{{ url('/#home') }}" style="text-decoration: none; font-weight: 700; color: #020617; font-size: 0.85rem; text-transform: uppercase;">Home</a></li>
            
            {{-- Dynamic Active Links --}}
            <li><a href="{{ route('landing.about') }}" class="{{ Route::is('landing.about') ? 'active-red' : '' }}" style="text-decoration: none; font-weight: 700; color: #020617; font-size: 0.85rem; text-transform: uppercase;">About Us</a></li>
            
            <li><a href="{{ route('landing.team') }}" class="{{ Route::is('landing.team') ? 'active-red' : '' }}" style="text-decoration: none; font-weight: 700; color: #020617; font-size: 0.85rem; text-transform: uppercase;">Other Projects</a></li>
            
            <li><a href="{{ url('/#licensing') }}" style="text-decoration: none; font-weight: 700; color: #020617; font-size: 0.85rem; text-transform: uppercase;">Licensing</a></li>
            
            <li><a href="{{ route('landing.policy') }}" class="{{ Route::is('landing.policy') ? 'active-red' : '' }}" style="text-decoration: none; font-weight: 700; color: #020617; font-size: 0.85rem; text-transform: uppercase;">Policy</a></li>
            
            {{-- The missing Contact link --}}
            <li><a href="{{ route('landing.contact') }}" class="{{ Route::is('landing.contact') ? 'active-red' : '' }}" style="text-decoration: none; font-weight: 700; color: #020617; font-size: 0.85rem; text-transform: uppercase;">Contact Us</a></li>
            
            <li>
                <a href="{{ route('saas-login') }}" style="padding: 12px 25px; color:white; background: #2563eb; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-block;">
                    Client Portal
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
    .nav-links a.active-red { color: #dc2626 !important; }
    .nav-links a:hover { color: #dc2626 !important; transition: 0.3s; }
</style>
