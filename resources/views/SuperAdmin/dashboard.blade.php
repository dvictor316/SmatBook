@extends('layout.mainlayout')

@section('title', 'Super Admin Command Center')

@section('content')

{{-- 
    CUSTOM STYLES FOR SIDEBAR AWARENESS & LAYOUT FIXES
    -------------------------------------------------------
    Updated to resolve Navbar overlap issues.
--}}
<style>
    /* Base wrapper transition for smooth toggling */
    #main-content-wrapper {
        transition: margin-left 0.3s ease, width 0.3s ease;
        width: 100%;
        overflow-x: hidden;
        padding-top: 100px; 
    }

    /* DESKTOP: Fixed 250px Sidebar Offset */
    @media (min-width: 992px) {
        #main-content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        /* State when sidebar is toggled closed */
        body.sidebar-collapsed #main-content-wrapper,
        body.sidebar-icon-only #main-content-wrapper,
        body.mini-sidebar #main-content-wrapper {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
    }

    /* MOBILE: Full width, no offset, adjust padding */
    @media (max-width: 991.98px) {
        #main-content-wrapper {
            margin-left: 0;
            width: 100%;
            padding-top: 120px;
        }
    }

    /* Responsive Header Flex */
    .header-responsive-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }

    /* Heatmap Grid Styles */
    .heatmap-grid {
        display: grid;
        grid-template-columns: 40px repeat(24, 1fr);
        gap: 2px;
        font-size: 10px;
    }
    .heatmap-cell {
        height: 25px;
        border-radius: 2px;
        background-color: #ebedf2;
        transition: all 0.2s;
    }
    .heatmap-cell:hover {
        transform: scale(1.2);
        border: 1px solid #333;
        z-index: 10;
        cursor: pointer;
    }
    .heatmap-label {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 5px;
        font-weight: bold;
        color: #6c757d;
    }
    .heatmap-legend {
        display: flex;
        gap: 10px;
        align-items: center;
        font-size: 12px;
        margin-top: 10px;
    }
    .legend-box { width: 15px; height: 15px; border-radius: 2px; }

    /* Map Container Styles */
    #regionMap {
        height: 400px;
        width: 100%;
        border-radius: 8px;
    }

    /* Chart height consistency */
    .chart-container {
        position: relative;
        height: 350px;
    }

    /* Pulse animation for live indicators */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>

{{-- WRAPPER START --}}
<div id="main-content-wrapper" class="container-fluid px-4 pb-4">

    <div class="row">
        <div class="col-sm-12">
            <div class="home-tab">
                
                {{-- Header Section --}}
                <div class="header-responsive-flex border-bottom pb-3 mb-4">
                    <div class="d-flex align-items-center">
                        {{-- Mobile Toggle Button --}}
                        <button class="btn btn-light d-lg-none me-3" type="button" onclick="toggleSidebarMobile()">
                            <i class="mdi mdi-menu"></i>
                        </button>
                        <div>
                            <h3 class="fw-bold text-dark mb-1">Master Command Center</h3>
                            <p class="text-muted mb-0">
                                System-wide overview for domain: 
                                <span class="text-primary fw-bold">{{ env('SESSION_DOMAIN', 'Primary Cluster') }}</span>
                            </p>
                        </div>
                    </div>
                    
                    {{-- Sync Status Indicator --}}
                    @if(auth()->user()->role === 'deployment_manager')
                        @php
                            $isSynced = auth()->user()->is_verified == 1 && 
                                        auth()->user()->deploymentProfile?->status === 'active';
                        @endphp
                        <div class="px-0 px-md-4 py-2">
                            <div class="d-flex align-items-center p-2 rounded {{ $isSynced ? 'bg-soft-success border border-success' : 'bg-soft-danger border border-danger animate-pulse' }}">
                                <div class="flex-shrink-0">
                                    <i class="mdi {{ $isSynced ? 'mdi-check-circle text-success' : 'mdi-alert-circle text-danger' }} fs-5"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="mb-0 fw-bold" style="font-size: 0.75rem; color: {{ $isSynced ? '#0a5d1a' : '#842029' }}">
                                        {{ $isSynced ? 'ACCOUNT VERIFIED' : 'SYNC REQUIRED' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="btn-wrapper d-flex gap-2 flex-wrap">
                        <button type="button" onclick="shareDashboard()" class="btn btn-outline-secondary btn-icon-text">
                            <i class="mdi mdi-share-variant me-1"></i> Share
                        </button>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-icon-text">
                            <i class="mdi mdi-printer me-1"></i> Print
                        </button>
                        <a href="{{ route('super_admin.dashboard.export') }}" class="btn btn-primary text-white btn-icon-text me-0">
                            <i class="mdi mdi-download me-1"></i> Export Reports
                        </a>
                    </div>
                </div>

                <div class="tab-content tab-content-basic">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">

                        {{-- ROW 1: Key Financial & User Metrics --}}
                        <div class="row">
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card card-gradient shadow-sm border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="circle-shadow-primary bg-soft-primary p-3 rounded-circle">
                                                <i class="mdi mdi-currency-usd fs-4 text-primary"></i>
                                            </div>
                                            <div class="badge badge-success small">+12.5%</div>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="text-uppercase text-muted small fw-bold">Platform Revenue</h6>
                                            <h3 class="text-primary fw-bold mb-0">₦{{ number_format($metrics['platform_revenue'], 2) }}</h3>
                                            <p class="text-muted small mt-1">Total Verified Subscriptions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card card-gradient shadow-sm border-0">
                                    <div class="card-body">
                                        <div class="circle-shadow-success bg-soft-success p-3 rounded-circle d-inline-block">
                                            <i class="mdi mdi-check-decagram fs-4 text-success"></i>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="text-uppercase text-muted small fw-bold">Active Subscriptions</h6>
                                            <h3 class="text-success fw-bold mb-0">{{ number_format($metrics['active_subs']) }}</h3>
                                            <p class="text-muted small mt-1">Monthly Recurring Revenue</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card card-gradient shadow-sm border-0">
                                    <div class="card-body">
                                        <div class="circle-shadow-info bg-soft-info p-3 rounded-circle d-inline-block">
                                            <i class="mdi mdi-domain fs-4 text-info"></i>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="text-uppercase text-muted small fw-bold">Total Companies</h6>
                                            <h3 class="text-info fw-bold mb-0">{{ number_format($metrics['total_tenants']) }}</h3>
                                            <p class="text-muted small mt-1">Registered Organizations</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card card-gradient shadow-sm border-0">
                                    <div class="card-body">
                                        <div class="circle-shadow-warning bg-soft-warning p-3 rounded-circle d-inline-block">
                                            <i class="mdi mdi-account-group fs-4 text-warning"></i>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="text-uppercase text-muted small fw-bold">Total Users</h6>
                                            <h3 class="text-warning fw-bold mb-0">{{ number_format($metrics['total_users']) }}</h3>
                                            <p class="text-muted small mt-1">Active Accounts</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ROW 2: Operational Alerts --}}
                        <div class="row mt-2">
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded bg-light-danger border-left-danger shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-account-alert fs-2 text-danger me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 text-danger">{{ $metrics['pending_managers'] }}</h5>
                                                <small class="text-dark">Pending Managers</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded bg-white shadow-sm border-left-primary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-account-tie fs-2 text-primary me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 text-dark">{{ $metrics['active_managers'] }}</h5>
                                                <small class="text-muted">Active Managers</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded bg-white shadow-sm border-left-info">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-server-network-off fs-2 text-info me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 text-dark">{{ $metrics['pending_setups'] }}</h5>
                                                <small class="text-muted">Provisioning Queue</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded bg-white shadow-sm border-left-success">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-package-variant-closed fs-2 text-success me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 text-dark">₦{{ number_format($metrics['total_stock_val']) }}</h5>
                                                <small class="text-muted">Stock Valuation</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ROW 3: Primary Analytics - Pie, Line, Bar Charts --}}
                        <div class="row mt-4">
                            {{-- Revenue by Plan - Pie Chart --}}
                            <div class="col-lg-4 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <h4 class="card-title card-title-dash mb-3">Revenue by Plan</h4>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-4">Subscription distribution</p>
                                        <div class="chart-container">
                                            <canvas id="planStatsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- User Traffic & Engagement - Line Chart --}}
                            <div class="col-lg-4 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <h4 class="card-title card-title-dash mb-3">User Traffic & Engagement</h4>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-4">New companies vs new users</p>
                                        <div class="chart-container">
                                            <canvas id="trafficLineChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Monthly Sales Volume - Bar Chart --}}
                            <div class="col-lg-4 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <h4 class="card-title card-title-dash mb-3">Monthly Sales Volume</h4>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-4">Paid subscriptions count per month</p>
                                        <div class="chart-container">
                                            <canvas id="salesBarChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ROW 4: Revenue Chart & System Health --}}
                        <div class="row mt-4">
                            
                            {{-- LEFT COLUMN: Revenue Growth Chart --}}
                            <div class="col-lg-7">
                                
                                {{-- Revenue Growth Chart --}}
                                <div class="card card-rounded shadow-sm mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <h4 class="card-title card-title-dash">Revenue Growth Trajectory</h4>
                                                <p class="card-subtitle card-subtitle-dash">Year-over-year financial performance</p>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-light dropdown-toggle" type="button" id="yearFilter" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ date('Y') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="chartjs-wrapper mt-4">
                                            <canvas id="revenueTrendChart" height="120"></canvas>
                                        </div>
                                    </div>
                                </div>

                                {{-- System Capacity & Health Progress Bars --}}
                                <div class="card card-rounded shadow-sm mb-3">
                                    <div class="card-body">
                                        <h4 class="card-title card-title-dash mb-4">System Capacity & Health</h4>
                                        
                                        @php
                                            $activeTenantPct = $systemHealth['company_provisioning_rate'] ?? 0;
                                            $managerHealthPct = $systemHealth['manager_verification_rate'] ?? 0;
                                            $paymentSuccessPct = $systemHealth['payment_success_rate'] ?? 0;
                                            $verifiedUsersPct = $systemHealth['user_verification_rate'] ?? 0;
                                        @endphp

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Company Provisioning Rate</span>
                                                <span class="fw-bold text-dark small">{{ $activeTenantPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $activeTenantPct }}%" aria-valuenow="{{ $activeTenantPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Deployment Manager Verification</span>
                                                <span class="fw-bold text-dark small">{{ $managerHealthPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $managerHealthPct }}%" aria-valuenow="{{ $managerHealthPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Payment Success Rate</span>
                                                <span class="fw-bold text-dark small">{{ $paymentSuccessPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $paymentSuccessPct }}%" aria-valuenow="{{ $paymentSuccessPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Verified Users</span>
                                                <span class="fw-bold text-dark small">{{ $verifiedUsersPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $verifiedUsersPct }}%" aria-valuenow="{{ $verifiedUsersPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Subscription Activation</span>
                                                <span class="fw-bold text-dark small">{{ $activeTenantPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $activeTenantPct }}%" aria-valuenow="{{ $activeTenantPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Platform Pulse (fills dead space under capacity card) --}}
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">Platform Pulse</h5>
                                            <span class="badge bg-success-subtle text-success">Realtime</span>
                                        </div>
                                        <div class="row g-3">
                                            @foreach([
                                                ['label' => 'Pending Managers', 'value' => number_format($metrics['pending_managers'] ?? 0), 'color' => 'warning'],
                                                ['label' => 'Suspended Managers', 'value' => number_format($metrics['suspended_managers'] ?? 0), 'color' => 'danger'],
                                                ['label' => 'Verified Users', 'value' => number_format($metrics['verified_users'] ?? 0), 'color' => 'success'],
                                                ['label' => 'Total Companies', 'value' => number_format($metrics['total_companies'] ?? 0), 'color' => 'primary'],
                                            ] as $item)
                                                <div class="col-sm-6">
                                                    <div class="p-3 rounded border bg-light h-100">
                                                        <div class="small text-muted mb-1">{{ $item['label'] }}</div>
                                                        <div class="h5 mb-0 text-{{ $item['color'] }}">{{ $item['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN: Deployment Manager Authorization List --}}
                            <div class="col-lg-5 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4 class="card-title card-title-dash">Deployment Manager Authorization</h4>
                                            @if($metrics['pending_managers'] > 0)
                                                <span class="badge rounded-pill bg-danger">{{ $metrics['pending_managers'] }} Pending</span>
                                            @endif
                                        </div>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-3">Approve or decline deployment manager access</p>
                                        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                                            <table class="table table-hover align-middle">
                                                <thead class="bg-light sticky-top">
                                                    <tr>
                                                        <th>Manager Profile</th>
                                                        <th>Status</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($deployments as $manager)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-sm me-2 bg-soft-primary rounded-circle text-center fw-bold d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                                                    {{ strtoupper(substr($manager->manager_name, 0, 1)) }}
                                                                </div>
                                                                <div>
                                                                    <p class="fw-bold mb-0" style="font-size: 0.85rem;">{{ $manager->manager_name }}</p>
                                                                    <small class="text-muted">{{ $manager->email }}</small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if($manager->status == 'pending')
                                                                <span class="badge badge-dot bg-warning me-1"></span> Pending
                                                            @elseif($manager->status == 'active')
                                                                <span class="badge badge-dot bg-success me-1"></span> Active
                                                            @else
                                                                <span class="badge bg-secondary">{{ ucfirst($manager->status) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($manager->status == 'pending')
                                                                <form action="{{ route('super_admin.managers.approve', $manager->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-inverse-success btn-icon btn-sm" title="Approve"><i class="mdi mdi-check"></i></button>
                                                                </form>
                                                                <button type="button" class="btn btn-inverse-danger btn-icon btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#rejectModal{{$manager->id}}" title="Decline"><i class="mdi mdi-close"></i></button>
                                                                
                                                                {{-- Rejection Modal --}}
                                                                <div class="modal fade" id="rejectModal{{$manager->id}}" tabindex="-1">
                                                                    <div class="modal-dialog">
                                                                        <form class="modal-content" action="{{ route('super_admin.managers.reject', $manager->id) }}" method="POST">
                                                                            @csrf
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Decline Manager</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <label class="form-label">Reason for Rejection</label>
                                                                                <textarea name="reason" class="form-control" rows="3" required></textarea>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-danger text-white">Confirm</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <i class="mdi mdi-shield-check text-primary" title="Verified"></i>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="3" class="text-center py-4 text-muted">No pending manager authorizations.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-3 p-3 rounded border bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 fw-semibold">Top Performing Managers (Top 3)</h6>
                                                <span class="badge bg-primary-subtle text-primary">Live Rank</span>
                                            </div>
                                            @php
                                                $managerRows = $managerPerformance['rows'] ?? [];
                                                $managerMax = (float) ($managerPerformance['max'] ?? 1);
                                                $barColors = ['success', 'primary', 'info', 'warning', 'danger'];
                                            @endphp
                                            @if(!empty($managerRows))
                                                <div class="d-flex flex-column gap-2">
                                                    @foreach($managerRows as $i => $m)
                                                        @php
                                                            $score = (float) ($m['score'] ?? 0);
                                                            $pct = max(3, min(100, $managerMax > 0 ? ($score / $managerMax) * 100 : 0));
                                                            $c = $barColors[$i % count($barColors)];
                                                        @endphp
                                                        <div>
                                                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                                                <span class="text-dark fw-semibold">{{ \Illuminate\Support\Str::limit($m['name'] ?? 'Manager', 28) }}</span>
                                                                <span class="text-muted">{{ number_format($score, 0) }}</span>
                                                            </div>
                                                            <div class="progress" style="height: 9px;">
                                                                <div class="progress-bar bg-{{ $c }}" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ (int) $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="small text-muted mt-2">No manager performance data yet.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ROW 5: OPTIMIZED LAYOUT - New Company Registrations + Live Activity (LEFT) & Regional Map (RIGHT) --}}
                        <div class="row">
                            {{-- LEFT COLUMN: New Company Registrations + Live Platform Activity --}}
                            <div class="col-lg-5 grid-margin stretch-card">
                                
                                {{-- New Company Registrations (Scrollable) --}}
                                <div class="card card-rounded shadow-sm mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="card-title card-title-dash mb-1">New Company Registrations</h4>
                                                <p class="card-subtitle card-subtitle-dash mb-0 small">Latest organizations that purchased plans</p>
                                            </div>
                                            <a href="#" class="btn btn-link text-primary fw-bold text-decoration-none small">View All</a>
                                        </div>
                                        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                            <table class="table table-striped table-borderless table-sm">
                                                <thead class="sticky-top bg-white">
                                                    <tr class="text-muted border-bottom">
                                                        <th class="small">Company</th>
                                                        <th class="small">Owner</th>
                                                        <th class="small">Plan</th>
                                                        <th class="small">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($recentTenants as $tenant)
                                                    <tr>
                                                        <td class="fw-bold text-dark small">{{ $tenant->name }}</td>
                                                        <td class="small">{{ $tenant->user->name ?? 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge bg-soft-primary text-primary small">
                                                                {{ $tenant->subscription->plan_name ?? $tenant->subscription->plan ?? $tenant->plan ?? 'Basic' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $tenant->status == 'active' ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }} small">
                                                                {{ strtoupper($tenant->status) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="4" class="text-center p-4 text-muted small">No new registrations yet.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Live Platform Activity --}}
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div>
                                                <h4 class="card-title card-title-dash mb-1">Live Platform Activity</h4>
                                                <p class="card-subtitle card-subtitle-dash mb-0 small">Real-time transaction stream</p>
                                            </div>
                                            <span class="small">
                                                <i class="mdi mdi-circle-medium text-success animate-pulse"></i> Live
                                            </span>
                                        </div>
                                        <div class="list-wrapper" style="max-height: 320px; overflow-y: auto;">
                                            <ul class="bullet-line-list">
                                                @forelse($platformActivity as $activity)
                                                <li class="mb-2">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <span class="text-primary fw-bold small">{{ $activity->company->name ?? $activity->subscriber_name ?? 'System' }}</span>
                                                            <span class="text-dark small">completed a transaction</span>
                                                        </div>
                                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $activity->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Ref: #{{ str_pad($activity->id, 6, '0', STR_PAD_LEFT) }} | Amount: ₦{{ number_format($activity->amount ?? 0, 2) }}</p>
                                                </li>
                                                @empty
                                                <p class="text-center py-4 text-muted small">No recent activity detected.</p>
                                                @endforelse
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN: Real-time Regional Distribution Map --}}
                            <div class="col-lg-7 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="card-title card-title-dash">Regional Distribution</h4>
                                                <p class="card-subtitle card-subtitle-dash mb-0">Real-time geographic presence</p>
                                            </div>
                                            <span class="badge bg-opacity-10 bg-success text-success">
                                                <i class="mdi mdi-circle-medium animate-pulse"></i> Live
                                            </span>
                                        </div>
                                        <div id="regionMap"></div>
                                        <div class="table-responsive mt-3">
                                            <table class="table table-sm table-borderless">
                                                <thead>
                                                    <tr class="text-muted">
                                                        <th class="small">Region</th>
                                                        <th class="text-end small">Companies</th>
                                                        <th class="text-end small">Market %</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($countryData as $country => $count)
                                                    @php $percent = $metrics['total_tenants'] > 0 ? ($count / $metrics['total_tenants']) * 100 : 0; @endphp
                                                    <tr>
                                                        <td class="small">
                                                            <i class="mdi mdi-map-marker text-danger me-1"></i>
                                                            {{ $country }}
                                                        </td>
                                                        <td class="text-end fw-bold small">{{ $count }}</td>
                                                        <td class="text-end small">{{ round($percent, 1) }}%</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @php
                                            $topRegion = !empty($countryData) ? collect($countryData)->sortDesc()->keys()->first() : 'N/A';
                                            $regionsCount = !empty($countryData) ? count($countryData) : 0;
                                        @endphp
                                        <div class="mt-3 p-3 rounded-3 border bg-light" style="min-height: 220px;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 fw-bold text-dark">Regional Snapshot</h6>
                                                <span class="badge bg-primary-subtle text-primary">Live</span>
                                            </div>
                                            <div class="row g-2">
                                                @foreach([
                                                    ['label' => 'Top Region', 'value' => $topRegion],
                                                    ['label' => 'Regions Covered', 'value' => number_format($regionsCount)],
                                                    ['label' => 'Active Tenants', 'value' => number_format($metrics['total_tenants'] ?? 0)],
                                                    ['label' => 'Active Subs', 'value' => number_format($metrics['active_subs'] ?? 0)],
                                                ] as $item)
                                                    <div class="col-6">
                                                        <div class="small text-muted">{{ $item['label'] }}</div>
                                                        <div class="fw-semibold text-dark">{{ $item['value'] }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-3">
                                                <div class="small text-muted mb-1">Coverage Strength</div>
                                                @php
                                                    $coverageBase = max(1, (int)($metrics['total_tenants'] ?? 0));
                                                    $coveragePct = min(100, (int) round(($regionsCount / $coverageBase) * 100));
                                                @endphp
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $coveragePct }}%" aria-valuenow="{{ $coveragePct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="small text-muted mt-2">{{ $coveragePct }}% region-to-tenant spread</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ROW 6: HEAT MAP --}}
                        <div class="row">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <h4 class="card-title card-title-dash">Transaction Density Heatmap</h4>
                                                <p class="card-subtitle card-subtitle-dash">System activity distribution (Day vs Hour UTC)</p>
                                            </div>
                                            <span class="badge bg-opacity-10 bg-info text-info"><i class="mdi mdi-information-outline"></i> Live Data</span>
                                        </div>
                                        
                                        <div class="heatmap-container overflow-auto">
                                            <div class="heatmap-grid">
                                                {{-- Hours Header --}}
                                                <div></div> 
                                                @for($h=0; $h<24; $h++)
                                                    <div class="text-center text-muted small" style="font-size:9px;">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</div>
                                                @endfor

                                                {{-- Days Rows --}}
                                                @php $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; @endphp
                                                @foreach($days as $day)
                                                    <div class="heatmap-label">{{ $day }}</div>
                                                    @for($h=0; $h<24; $h++)
                                                        <div class="heatmap-cell" id="hm-{{$day}}-{{$h}}" title="{{$day}} {{str_pad($h, 2, '0', STR_PAD_LEFT)}}:00"></div>
                                                    @endfor
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="heatmap-legend">
                                            <span>Intensity:</span>
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="legend-box" style="background:#ebedf2"></div> <span class="text-muted">Low</span>
                                                <div class="legend-box" style="background:#b1d3fa"></div>
                                                <div class="legend-box" style="background:#52a2f5"></div>
                                                <div class="legend-box" style="background:#1F3BB3"></div> <span class="fw-bold">High</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- WRAPPER END --}}

@if(view()->exists('scripts.print_handler'))
    @include('scripts.print_handler')
@else
    <script>function printPage() { window.print(); }</script>
@endif

@endsection

@push('scripts')
{{-- Chart.js Library --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Leaflet Map Library --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

@php
    $dashboardChartSeries = $chartSeries ?? [
        'labels' => [],
        'revenue' => [],
        'orders' => [],
        'companies' => [],
        'users' => [],
    ];
    $dashboardActivityHeatmap = $activityHeatmap ?? [];
@endphp

<script>
    /**
     * Dashboard Analytics Handler & Sidebar Toggle Logic
     */
    function toggleSidebarMobile() {
        document.body.classList.toggle('sidebar-icon-only'); 
        window.dispatchEvent(new Event('resize'));
    }

    function shareDashboard() {
        const title = 'SmatBooks Master Dashboard';
        const text = 'Live platform analytics';
        const url = window.location.href;

        if (navigator.share) {
            navigator.share({ title, text, url }).catch(() => {
                fallbackCopy(url);
            });
            return;
        }

        fallbackCopy(url);
    }

    function fallbackCopy(url) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Dashboard link copied to clipboard.');
            }).catch(() => {
                window.prompt('Copy dashboard link:', url);
            });
            return;
        }

        const temp = document.createElement('input');
        temp.value = url;
        document.body.appendChild(temp);
        temp.select();
        temp.setSelectionRange(0, temp.value.length);

        try {
            const copied = document.execCommand('copy');
            if (copied) {
                alert('Dashboard link copied to clipboard.');
            } else {
                window.prompt('Copy dashboard link:', url);
            }
        } catch (e) {
            window.prompt('Copy dashboard link:', url);
        }

        document.body.removeChild(temp);
    }

    document.addEventListener("DOMContentLoaded", function() {
        const chartSeries = @json($dashboardChartSeries);
        const activityHeatmap = @json($dashboardActivityHeatmap);
        
        // --- 1. REVENUE TREND CHART (Line) ---
        const revenueCtx = document.getElementById('revenueTrendChart');
        if (revenueCtx) {
            const ctx = revenueCtx.getContext("2d");
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(31, 59, 179, 0.2)');
            gradient.addColorStop(1, 'rgba(31, 59, 179, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartSeries.labels,
                    datasets: [{
                        label: 'Gross Revenue',
                        data: chartSeries.revenue,
                        borderColor: '#1F3BB3',
                        backgroundColor: gradient,
                        pointBackgroundColor: '#1F3BB3',
                        pointBorderColor: '#fff',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₦' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f0f0f0', drawBorder: false },
                            ticks: {
                                callback: function(value) {
                                    return '₦' + value.toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // --- 2. PLAN REVENUE DISTRIBUTION (Doughnut/Pie) ---
        const planCtx = document.getElementById('planStatsChart');
        if (planCtx) {
            new Chart(planCtx.getContext("2d"), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode(array_keys($planStats)) !!},
                    datasets: [{
                        data: {!! json_encode(array_values($planStats)) !!},
                        backgroundColor: ['#1F3BB3', '#52CDFF', '#FFAB00', '#F95F53', '#7978E9'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { 
                        legend: { 
                            position: 'bottom', 
                            labels: { padding: 15, usePointStyle: true } 
                        }
                    }
                }
            });
        }

        // --- 3. TRAFFIC LINE CHART ---
        const trafficCtx = document.getElementById('trafficLineChart');
        if (trafficCtx) {
            new Chart(trafficCtx.getContext("2d"), {
                type: 'line',
                data: {
                    labels: chartSeries.labels,
                    datasets: [
                        {
                            label: 'New Companies',
                            data: chartSeries.companies,
                            borderColor: '#52CDFF',
                            backgroundColor: 'rgba(82, 205, 255, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'New Users',
                            data: chartSeries.users,
                            borderColor: '#1F3BB3',
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 4,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            position: 'top',
                            labels: { usePointStyle: true }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f0f0f0', drawBorder: false }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // --- 4. SALES BAR CHART ---
        const salesCtx = document.getElementById('salesBarChart');
        if (salesCtx) {
            new Chart(salesCtx.getContext("2d"), {
                type: 'bar',
                data: {
                    labels: chartSeries.labels,
                    datasets: [{
                        label: 'Paid Subscriptions',
                        data: chartSeries.orders,
                        backgroundColor: '#1F3BB3',
                        borderRadius: 6,
                        barPercentage: 0.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { display: true, color: '#f0f0f0', drawBorder: false }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // --- 5. REAL-TIME REGIONAL MAP (Leaflet) ---
        // --- 6. REAL-TIME REGIONAL MAP (Leaflet) ---
        const mapElement = document.getElementById('regionMap');
        if (mapElement) {
            // Initialize map
            const regionMap = L.map('regionMap').setView([20, 0], 2);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(regionMap);

            // Country data with approximate coordinates
            const countryCoordinates = {
                @foreach($countryData as $country => $count)
                '{{ $country }}': { 
                    @php
                        // Approximate coordinates for common countries
                        $coords = [
                            'United States' => ['lat' => 37.0902, 'lng' => -95.7129],
                            'Nigeria' => ['lat' => 9.0820, 'lng' => 8.6753],
                            'United Kingdom' => ['lat' => 55.3781, 'lng' => -3.4360],
                            'Canada' => ['lat' => 56.1304, 'lng' => -106.3468],
                            'Australia' => ['lat' => -25.2744, 'lng' => 133.7751],
                            'Germany' => ['lat' => 51.1657, 'lng' => 10.4515],
                            'France' => ['lat' => 46.2276, 'lng' => 2.2137],
                            'India' => ['lat' => 20.5937, 'lng' => 78.9629],
                            'China' => ['lat' => 35.8617, 'lng' => 104.1954],
                            'Japan' => ['lat' => 36.2048, 'lng' => 138.2529],
                            'Brazil' => ['lat' => -14.2350, 'lng' => -51.9253],
                            'South Africa' => ['lat' => -30.5595, 'lng' => 22.9375],
                        ];
                        $lat = $coords[$country]['lat'] ?? 0;
                        $lng = $coords[$country]['lng'] ?? 0;
                    @endphp
                    lat: {{ $lat }}, 
                    lng: {{ $lng }},
                    count: {{ $count }}
                },
                @endforeach
            };

            // Add markers for each country
            Object.keys(countryCoordinates).forEach(country => {
                const data = countryCoordinates[country];
                if (data.lat !== 0 && data.lng !== 0) {
                    const marker = L.circleMarker([data.lat, data.lng], {
                        radius: Math.max(8, Math.min(data.count * 2, 30)),
                        fillColor: '#1F3BB3',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.7
                    }).addTo(regionMap);

                    marker.bindPopup(`
                        <div style="text-align: center;">
                            <strong>${country}</strong><br>
                            Companies: <strong>${data.count}</strong>
                        </div>
                    `);
                }
            });

            // Auto-refresh markers every 30 seconds (simulated real-time)
            setInterval(() => {
                // In production, fetch new data via AJAX and update markers
                console.log('Map data refresh triggered');
            }, 30000);
        }

        // --- 6. HEATMAP VISUALIZATION ---
        const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        days.forEach(day => {
            for(let h=0; h<24; h++) {
                const cell = document.getElementById(`hm-${day}-${h}`);
                if(cell) {
                    const value = ((activityHeatmap[day] || {})[h]) || 0;
                    let intensity = 0;
                    if (value > 0 && value <= 2) intensity = 1;
                    else if (value > 2 && value <= 5) intensity = 2;
                    else if (value > 5) intensity = 3;
                    
                    if (intensity === 1) cell.style.backgroundColor = '#b1d3fa'; 
                    if (intensity === 2) cell.style.backgroundColor = '#52a2f5'; 
                    if (intensity === 3) cell.style.backgroundColor = '#1F3BB3'; 
                    if (!intensity) cell.style.backgroundColor = '#ebedf2';
                }
            }
        });

    });
</script>
@endpush
