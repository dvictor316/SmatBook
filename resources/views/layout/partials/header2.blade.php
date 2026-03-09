@php
    $user = Auth::user();
    $notifications = [];
    
    // 1. Optimized Notification Logic
    if ($user && Schema::hasTable('notifications')) {
        $notifications = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User') // Ensure this matches your User model path
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }
    
    // 2. Profile Image & Avatar Fallback Logic
    $defaultAvatar = asset('assets/img/profiles/avatar-07.jpg');
    $profileImagePath = $user?->avatar_url ?: $defaultAvatar;
@endphp

<style>
    /* =========================================
       HEADER MAIN LAYOUT
       ========================================= */
    .header {
        display: flex;
        align-items: center;
        padding: 0 20px; /* Adjusted padding */
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        height: 76px;
        position: sticky;
        top: 0;
        z-index: 1000;
        transition: all 0.2s ease-in-out;
    }

    /* Logo Area - Matches Sidebar Width */
    .header-left {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        width: 270px; /* Fixed to match your sidebar width request */
        height: 76px;
        padding: 0 15px;
        transition: all 0.2s ease-in-out;
        position: relative;
    }

    /* Logo Image Styling */
    .header-logo img {
        height: 52px;
        width: auto;
        max-width: 100%;
        transition: all 0.2s;
    }
    .spb-wordmark {
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -0.3px;
        line-height: 1;
        color: #0b2a63;
        white-space: nowrap;
    }
    .spb-wordmark .book { color: #dc2626; }

    /* Toggle Button */
    .header-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 8px;
        color: #64748b;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s;
        /* Positioned to sit between logo area and content */
        margin-left: -19px; 
        z-index: 1001;
        background: #fff;
        border: 1px solid #e2e8f0;
    }

    .header-toggle:hover {
        background: #f1f5f9;
        color: #3b82f6;
    }

    /* Hamburger Animation Icons */
    .toggle-bars {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 18px;
        height: 14px;
    }

    .bar-icon {
        width: 100%;
        height: 2px;
        background: currentColor;
        border-radius: 2px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: center;
    }

    /* =========================================
       TOGGLE STATE (COLLAPSED)
       ========================================= */
    /* When body has 'sidebar-collapsed', shrink header-left */
    body.sidebar-collapsed .header-left {
        width: 80px; /* Shrink to icon width */
    }

    body.sidebar-collapsed .header-logo img {
        max-width: 100px;
    }
    body.sidebar-collapsed .spb-wordmark,
    body.mini-sidebar .spb-wordmark {
        display: none;
    }

    /* Animate Hamburger to X */
    body.sidebar-collapsed .bar-icon:nth-child(1) {
        transform: translateY(6px) rotate(45deg);
    }

    body.sidebar-collapsed .bar-icon:nth-child(2) {
        opacity: 0;
        transform: scaleX(0);
    }

    body.sidebar-collapsed .bar-icon:nth-child(3) {
        transform: translateY(-6px) rotate(-45deg);
    }

    /* =========================================
       SEARCH BAR
       ========================================= */
    .header-spacer {
        flex: 1;
        min-width: 20px;
    }

    .header-search-wrapper {
        flex: 2;
        max-width: 600px;
        display: flex;
        justify-content: center;
    }

    .header-search {
        position: relative;
        width: 100%;
    }

    .header-search input {
        width: 100%;
        padding: 10px 40px 10px 20px;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        font-size: 13px;
        background: #f8fafc;
        color: #334155;
        transition: all 0.2s;
    }

    .header-search input:focus {
        outline: none;
        background: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .header-search .search-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    /* Search Results Dropdown */
    .search-results {
        position: absolute;
        top: calc(100% + 10px);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-height: 400px;
        overflow-y: auto;
        display: none;
        z-index: 1100;
    }

    .search-results.show {
        display: block;
    }

    .search-result-item {
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #334155;
        transition: background 0.15s;
    }

    .search-result-item:hover {
        background: #f8fafc;
        color: #3b82f6;
    }

    .search-no-results {
        padding: 30px;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
    }

    /* =========================================
       RIGHT HEADER ACTIONS
       ========================================= */
    .header-actions {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-left: 20px;
    }

    /* Country Flag */
    .country-selector {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 8px;
        text-decoration: none;
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
        transition: background 0.2s;
    }

    .country-selector:hover {
        background: #f1f5f9;
    }

    /* Notifications */
    .notification-bell {
        position: relative;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #64748b;
        font-size: 20px;
        transition: all 0.2s;
    }

    .notification-bell:hover {
        background: #f1f5f9;
        color: #3b82f6;
    }

    .notification-bell .badge {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        padding: 0;
        border: 1px solid #fff;
    }

    /* User Profile */
    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 4px;
        border-radius: 30px;
        border: 1px solid transparent;
        transition: all 0.2s;
        text-decoration: none;
    }

    .user-profile:hover {
        background: #f1f5f9;
        border-color: #e2e8f0;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        line-height: 1.3;
        margin-right: 10px;
    }

    .user-name {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
    }

    .user-role {
        font-size: 11px;
        color: #94a3b8;
    }

    /* Notifications Dropdown CSS */
    .notifications-dropdown {
        width: 350px;
        padding: 0;
        border: none;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .notifications-header {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notifications-title { font-weight: 600; color: #1e293b; }
    .mark-read { font-size: 12px; color: #3b82f6; text-decoration: none; }
    .notification-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f8fafc;
        display: block;
        transition: background 0.2s;
    }
    .notification-item:hover { background: #f8fafc; }
    .notification-text { font-size: 13px; color: #334155; margin-bottom: 4px; }
    .notification-time { font-size: 11px; color: #94a3b8; }
    .notifications-footer { padding: 12px; text-align: center; border-top: 1px solid #e2e8f0; }
    .notifications-footer a { font-size: 13px; font-weight: 500; text-decoration: none; color: #3b82f6; }

    /* Mobile Search */
    .mobile-search-btn {
        display: none;
        background: none;
        border: none;
        font-size: 18px;
        color: #64748b;
    }
    .mobile-search-overlay {
        position: fixed;
        top: 70px; left: 0; right: 0;
        background: white;
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        display: none;
        z-index: 999;
    }
    .mobile-search-overlay.active { display: block; }

    /* Responsive */
    @media (max-width: 991px) {
        .header-left { width: auto; border: none; }
        .header-search-wrapper, .header-spacer, .user-info { display: none; }
        .mobile-search-btn { display: block; }
        .header-toggle { margin-left: 10px; }
        .spb-wordmark {
            font-size: 0.88rem;
            letter-spacing: -0.2px;
        }
    }
</style>

<div class="header d-print-none">

    {{-- 1. Logo Section (Matches Sidebar Width) --}}
    <div class="header-left">
        <a href="{{ url('/') }}" class="header-logo">
            <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook Logo">
        </a>
        <span class="spb-wordmark">SmartPro<span class="book">book</span></span>
    </div>

    {{-- 2. Toggle Button --}}
    <a href="javascript:void(0);" id="toggle_btn" class="header-toggle">
        <span class="toggle-bars">
            <span class="bar-icon"></span>
            <span class="bar-icon"></span>
            <span class="bar-icon"></span>
        </span>
    </a>

    {{-- 3. Spacer --}}
    <div class="header-spacer"></div>

    {{-- 4. Search Bar --}}
    <div class="header-search-wrapper">
        <div class="header-search">
            <input type="text" id="globalSearch" placeholder="Search..." autocomplete="off">
            <i class="fas fa-search search-icon"></i>
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>

    {{-- 5. Right Actions --}}
    <div class="header-actions">
        
        {{-- Mobile Search Toggle --}}
        <button class="mobile-search-btn" id="mobileSearchToggle">
            <i class="fas fa-search"></i>
        </button>

        {{-- Country Selector --}}
        <div class="dropdown">
            <a href="#" class="country-selector" data-bs-toggle="dropdown">
                <img src="{{ asset('assets/img/flags/ng.png') }}" alt="Nigeria" width="20">
                <span class="d-none d-md-inline">NG</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="javascript:void(0);" class="dropdown-item active"><span class="me-2">🇳🇬</span>Nigeria (NGN)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇺🇸</span>United States (USD)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇨🇳</span>China (CNY)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇬🇧</span>United Kingdom (GBP)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇪🇺</span>Europe (EUR)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇨🇦</span>Canada (CAD)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇮🇳</span>India (INR)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇦🇪</span>UAE (AED)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇿🇦</span>South Africa (ZAR)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇰🇪</span>Kenya (KES)</a>
                <a href="javascript:void(0);" class="dropdown-item"><span class="me-2">🇬🇭</span>Ghana (GHS)</a>
            </div>
        </div>

        {{-- Notifications --}}
        <div class="dropdown">
            <a href="#" class="notification-bell" data-bs-toggle="dropdown">
                <i class="far fa-bell"></i>
                @if(count($notifications) > 0)
                    <span class="badge"></span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-end notifications-dropdown">
                <div class="notifications-header">
                    <div class="notifications-title">Notifications</div>
                    <a href="javascript:void(0)" class="mark-read">Mark all as read</a>
                </div>
                <div class="notifications-body">
                    @forelse($notifications as $notification)
                        @php $data = json_decode($notification->data, true); @endphp
                        <a href="#" class="notification-item">
                            <div class="notification-text">
                                {{ $data['message'] ?? 'New system update available' }}
                            </div>
                            <div class="notification-time">
                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-center text-muted" style="font-size: 13px;">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            No new notifications
                        </div>
                    @endforelse
                </div>
                @if(count($notifications) > 0)
                <div class="notifications-footer">
                    <a href="#">View all notifications</a>
                </div>
                @endif
            </div>
        </div>

        {{-- User Profile --}}
        @auth
        <div class="dropdown">
            <a href="#" class="user-profile" data-bs-toggle="dropdown">
                <img src="{{ $profileImagePath }}" alt="{{ $user->name }}" class="user-avatar">
                <div class="user-info">
                    <span class="user-name">{{ $user->name }}</span>
                    <span class="user-role">{{ $user->role ?? 'Admin' }}</span>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="{{ url('profile') }}">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
                <a class="dropdown-item" href="{{ url('settings') }}">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="javascript:void(0);"
                   onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();">
                    <i class="fas fa-sign-out-alt me-2"></i> Log Out
                </a>
            </div>
        </div>
        @endauth
    </div>
</div>

{{-- Mobile Search Overlay --}}
<div class="mobile-search-overlay" id="mobileSearchOverlay">
    <div class="header-search">
        <input type="text" id="mobileGlobalSearch" placeholder="Search..." autocomplete="off">
        <i class="fas fa-search search-icon"></i>
        <div class="search-results" id="mobileSearchResults"></div>
    </div>
</div>

{{-- Logout Form --}}
<form id="logout-form-header" action="{{ route('emergency.logout') }}" method="POST" class="d-none">
    @csrf
</form>

{{-- JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================================
    // 1. FIXED TOGGLE FUNCTIONALITY
    // ============================================================
    const toggleBtn = document.getElementById('toggle_btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Toggle the class on the body that CSS responds to
            document.body.classList.toggle('sidebar-collapsed');
            
            // Persist state in LocalStorage
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Dispatch resize event to fix charts/tables
            window.dispatchEvent(new Event('resize'));
        });

        // Restore saved state on load
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    }

    // ============================================================
    // 2. SEARCH FUNCTIONALITY (Preserved)
    // ============================================================
    const searchConfig = { minChars: 2, debounceDelay: 300 };
    const searchableData = {
        pages: [
            { title: 'Dashboard', url: '{{ url("/") }}', icon: 'home' },
            { title: 'Customers', url: '{{ url("/customers") }}', icon: 'users' },
            { title: 'Invoices', url: '{{ url("/invoices") }}', icon: 'file-text' },
            { title: 'Settings', url: '{{ url("/settings") }}', icon: 'cog' },
        ]
    };

    function performSearch(query, container) {
        if (query.length < searchConfig.minChars) {
            container.classList.remove('show');
            return;
        }
        
        // Simple client-side filter
        const lowerQuery = query.toLowerCase();
        const results = searchableData.pages.filter(item => item.title.toLowerCase().includes(lowerQuery));
        
        let html = '';
        if (results.length > 0) {
            results.forEach(item => {
                html += `
                    <a href="${item.url}" class="search-result-item">
                        <i class="fas fa-${item.icon} me-3 text-muted"></i>
                        <span>${item.title}</span>
                    </a>`;
            });
        } else {
            html = '<div class="search-no-results">No results found</div>';
        }
        
        container.innerHTML = html;
        container.classList.add('show');
    }

    // Bind Search Inputs
    ['globalSearch', 'mobileGlobalSearch'].forEach(id => {
        const input = document.getElementById(id);
        const resultsId = id === 'globalSearch' ? 'searchResults' : 'mobileSearchResults';
        const results = document.getElementById(resultsId);
        
        if (input && results) {
            input.addEventListener('input', (e) => performSearch(e.target.value, results));
            
            // Hide on outside click
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !results.contains(e.target)) {
                    results.classList.remove('show');
                }
            });
        }
    });

    // Mobile Overlay Toggle
    const mobileBtn = document.getElementById('mobileSearchToggle');
    const mobileOverlay = document.getElementById('mobileSearchOverlay');
    if(mobileBtn && mobileOverlay) {
        mobileBtn.addEventListener('click', () => {
            mobileOverlay.classList.toggle('active');
            if(mobileOverlay.classList.contains('active')) {
                document.getElementById('mobileGlobalSearch').focus();
            }
        });
    }
});
</script>
