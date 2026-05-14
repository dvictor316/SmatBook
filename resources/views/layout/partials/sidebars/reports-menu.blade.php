
@php
    $currentTab = request('tab', 'standard');
    $isReports = Request::is('reports*', 'trial-balance', 'balance-sheet', 'cash-flow')
        || request()->routeIs('reports.*', 'trial-balance', 'balance-sheet', 'balance-sheet-summary', 'balance-sheet-comparison');
    $reportAccess = $reportAccess ?? 'basic';
    $renderNested = (bool) ($nested ?? false);
    $canManagement = in_array($reportAccess, ['pro', 'professional', 'enterprise', 'full'], true);
    $canEnterprise = in_array($reportAccess, ['enterprise', 'full'], true);
@endphp

@if(!$renderNested)
    <li class="submenu {{ $isReports ? 'active subdrop' : '' }}">
        <a href="#">
            <i class="fas fa-chart-simple"></i>
            <span>Reports</span>
            <span class="menu-arrow"></span>
        </a>
        <ul>
@endif

@if(Route::has('reports.hub'))
    <li>
        <a href="{{ route('reports.hub') }}"
           class="reports-hub-link {{ request()->routeIs('reports.hub') && !request()->has('tab') ? 'active' : '' }}">
            Reports Hub
        </a>
    </li>
@endif

<li class="reports-group-header">Standard</li>
@if(Route::has('reports.hub'))
    <li><a href="{{ route('reports.hub') }}?tab=standard" class="{{ request()->routeIs('reports.hub') && $currentTab === 'standard' ? 'active' : '' }}">Standard Reports</a></li>
@endif

@if($canManagement)
    <li class="reports-group-header">Management</li>
    @if(Route::has('reports.hub'))
        <li><a href="{{ route('reports.hub') }}?tab=management" class="{{ request()->routeIs('reports.hub') && $currentTab === 'management' ? 'active' : '' }}">Management Reports</a></li>
    @endif
@endif

@if($canEnterprise)
    <li class="reports-group-header">Custom</li>
    @if(Route::has('reports.hub'))
        <li><a href="{{ route('reports.hub') }}?tab=custom" class="{{ request()->routeIs('reports.hub') && $currentTab === 'custom' ? 'active' : '' }}">Custom Reports</a></li>
    @endif
@endif

@if(!$renderNested)
        </ul>
    </li>
@endif
