
@php
    $currentTab = request('tab', 'standard');
    $isReports  = Request::is('reports*');
    $reportAccess = $reportAccess ?? 'basic';
    if (in_array($reportAccess, ['full', 'enterprise'], true)) {
        $allowedTabs = ['standard', 'management', 'custom'];
    } elseif ($reportAccess === 'pro') {
        $allowedTabs = ['standard', 'management'];
    } else {
        $allowedTabs = ['standard'];
    }
@endphp

<li class="submenu {{ $isReports ? 'active subdrop' : '' }}">
    <a href="#">
        <i class="fas fa-chart-simple"></i>
        <span>Reports</span>
        <span class="menu-arrow"></span>
    </a>
    <ul>
        <li>
            <a href="{{ route('reports.hub') }}?tab=standard"
               class="{{ $isReports && $currentTab === 'standard' ? 'active' : '' }}">
                Standard Reports
            </a>
        </li>
        @if(in_array('management', $allowedTabs, true))
            <li>
                <a href="{{ route('reports.hub') }}?tab=management"
                   class="{{ $isReports && $currentTab === 'management' ? 'active' : '' }}">
                    Management Reports
                </a>
            </li>
        @endif
        @if(in_array('custom', $allowedTabs, true))
            <li>
                <a href="{{ route('reports.hub') }}?tab=custom"
                   class="{{ $isReports && $currentTab === 'custom' ? 'active' : '' }}">
                    Custom Reports
                </a>
            </li>
        @endif
    </ul>
</li>
