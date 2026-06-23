@php
    $agentNav = [
        ['label' => 'Home', 'route' => 'agent.dashboard', 'icon' => 'fa-solid fa-house'],
        ['label' => 'Leads', 'route' => 'agent.leads', 'icon' => 'fa-solid fa-user-plus'],
        ['label' => 'Free Trials', 'route' => 'agent.leads', 'icon' => 'fa-solid fa-clock', 'query' => ['status' => 'interested']],
        ['label' => 'Businesses', 'route' => 'agent.leads', 'icon' => 'fa-solid fa-store', 'query' => ['type' => 'company']],
        ['label' => 'Find Nearby', 'route' => 'agent.find-nearby', 'icon' => 'fa-solid fa-location-dot'],
        ['label' => 'Performance', 'route' => 'agent.performance', 'icon' => 'fa-solid fa-chart-line'],
        ['label' => 'Earnings', 'route' => 'agent.earnings', 'icon' => 'fa-solid fa-wallet'],
        ['label' => 'Knowledge Base', 'route' => 'agent.knowledge-base', 'icon' => 'fa-solid fa-graduation-cap'],
        ['label' => 'Content Hub', 'route' => 'agent.content-hub', 'icon' => 'fa-solid fa-photo-film'],
    ];
@endphp

<div class="sidebar agent-sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu agent-sidebar-menu">
            <div class="agent-brand">
                <span class="agent-brand-mark">P</span>
                <span>
                    <strong>Prokip</strong>
                    <small>Agent Portal</small>
                </span>
            </div>

            <p class="agent-menu-label">Menu</p>
            <ul>
                @foreach($agentNav as $item)
                    @php
                        $isActive = request()->routeIs($item['route']);
                        $url = route($item['route'], $item['query'] ?? []);
                    @endphp
                    <li>
                        <a href="{{ $url }}" class="{{ $isActive ? 'active' : '' }}">
                            <i class="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
                <li class="agent-soon">
                    <a href="javascript:void(0);">
                        <i class="fa-solid fa-rocket"></i>
                        <span>Upsell Center</span>
                        <small>Soon</small>
                    </a>
                </li>
                <li class="agent-logout">
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-agent').submit();">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                    <form id="logout-form-agent" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </li>
            </ul>
        </div>
    </div>
</div>
