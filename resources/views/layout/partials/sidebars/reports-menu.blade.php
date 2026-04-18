
@php
    $currentTab = request('tab', 'standard');
    $isReports  = Request::is('reports*');
@endphp

<li class="submenu {{ $isReports ? 'active subdrop' : '' }}">
    <a href="#">
        <i class="fe fe-bar-chart-2"></i>
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
        <li>
            <a href="{{ route('reports.hub') }}?tab=management"
               class="{{ $isReports && $currentTab === 'management' ? 'active' : '' }}">
                Management Reports
            </a>
        </li>
        <li>
            <a href="{{ route('reports.hub') }}?tab=custom"
               class="{{ $isReports && $currentTab === 'custom' ? 'active' : '' }}">
                Custom Reports
            </a>
        </li>
    </ul>
</li>

