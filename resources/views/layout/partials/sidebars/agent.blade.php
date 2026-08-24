@php
    $agentNav = [
        ['label' => 'Home', 'route' => 'agent.dashboard', 'icon' => 'fa-solid fa-house'],
        ['label' => 'Leads', 'route' => 'agent.leads', 'icon' => 'fa-solid fa-user-plus'],
        ['label' => 'Free Trials', 'route' => 'agent.leads', 'icon' => 'fa-solid fa-clock', 'query' => ['status' => 'interested']],
        ['label' => 'Businesses', 'route' => 'agent.leads', 'icon' => 'fa-solid fa-store', 'query' => ['type' => 'company']],
        ['label' => 'Add Users', 'route' => 'agent.business-users.add', 'icon' => 'fa-solid fa-users-gear'],
        ['label' => 'Find Nearby', 'route' => 'agent.nearby-businesses', 'icon' => 'fa-solid fa-location-dot', 'active' => ['agent.find-nearby', 'agent.nearby-businesses']],
        ['label' => 'Performance', 'route' => 'agent.performance', 'icon' => 'fa-solid fa-chart-line'],
        ['label' => 'Earnings', 'route' => 'agent.earnings', 'icon' => 'fa-solid fa-wallet'],
        ['label' => 'Knowledge Base', 'route' => 'agent.knowledge-base', 'icon' => 'fa-solid fa-graduation-cap'],
        ['label' => 'Content Hub', 'route' => 'agent.content-hub', 'icon' => 'fa-solid fa-photo-film'],
        ['label' => 'Upsell Center', 'route' => 'agent.upsell-center', 'icon' => 'fa-solid fa-rocket'],
    ];
@endphp

<div class="sidebar agent-sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu agent-sidebar-menu">
            <div class="agent-brand">
                <span class="agent-brand-mark">S</span>
                <span>
                    <strong>SmartProbook</strong>
                    <small>Agent Portal</small>
                </span>
            </div>

            <p class="agent-menu-label">Menu</p>
            <ul>
                @foreach($agentNav as $item)
                    @php
                        $activeRoutes = $item['active'] ?? [$item['route']];
                        $isActive = collect($activeRoutes)->contains(fn ($route) => request()->routeIs($route));
                        $url = route($item['route'], $item['query'] ?? []);
                    @endphp
                    <li>
                        <a href="{{ $url }}" class="{{ $isActive ? 'active' : '' }}">
                            <i class="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
                <li class="agent-logout">
                    <a href="{{ route('logout') }}" class="agent-logout-link" onclick="event.preventDefault(); document.getElementById('logout-form-agent').submit();">
                        <i class="fa-solid fa-right-from-bracket agent-logout-icon"></i>
                        <span class="agent-logout-text">Logout</span>
                    </a>
                    <form id="logout-form-agent" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </li>
            </ul>
        </div>
    </div>
</div>
