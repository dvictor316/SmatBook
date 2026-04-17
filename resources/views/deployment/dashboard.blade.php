@extends('layout.mainlayout')

@section('title', 'Deployment Console')

@section('content')

<style>
    :root {
        --primary-color: #2563eb;
        --glass-border: #e2e8f0;
    }

    #deployment-wrapper {
        transition: margin-left 0.3s ease, padding-top 0.3s ease;
        padding: 1.5rem;
        min-height: 100vh;
        background: #f8fafc;
        max-width: 100%;
        overflow-x: clip;
    }

    @media (min-width: 992px) {
        #deployment-wrapper {
            margin-left: var(--sb-sidebar-w, 270px);
            width: calc(100% - var(--sb-sidebar-w, 270px));
            max-width: calc(100% - var(--sb-sidebar-w, 270px));
        }
        body.mini-sidebar #deployment-wrapper,
        body.sidebar-collapsed #deployment-wrapper,
        body.sidebar-icon-only #deployment-wrapper {
            margin-left: var(--sb-sidebar-collapsed, 80px);
            width: calc(100% - var(--sb-sidebar-collapsed, 80px));
            max-width: calc(100% - var(--sb-sidebar-collapsed, 80px));
        }
    }

    @media (max-width: 991.98px) {
        #deployment-wrapper { margin-left: 0; width: 100%; max-width: 100%; }
    }

    .glass-card {
        background: linear-gradient(160deg, rgba(255,255,255,0.98) 0%, rgba(248,251,255,0.96) 100%);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        text-decoration: none;
        display: block;
        color: inherit;
        position: relative;
        overflow: hidden;
    }

    .glass-card::after {
        content: '';
        position: absolute;
        inset: auto -22px -32px auto;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        pointer-events: none;
    }

    a.glass-card:hover, div.glass-card.clickable:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
        cursor: pointer;
    }

    .metric-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink:0; }
    .status-pill { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    .chart-box { position: relative; height: 210px; width: 100%; }
    .feed-item { border-left: 2px solid #e2e8f0; padding-left: 14px; position: relative; padding-bottom: 14px; }
    .feed-item:last-child { border-left: 2px solid transparent; }
    .feed-item::before { content: ""; position: absolute; left: -5px; top: 2px; width: 8px; height: 8px; border-radius: 50%; background: var(--primary-color); box-shadow: 0 0 0 3px #f1f5f9; }

    .commission-card {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
    }

    .revenue-card {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
    }

    .chart-panel {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.08), transparent 36%),
            linear-gradient(135deg, #0f172a 0%, #1d4ed8 56%, #0891b2 100%);
        color: #fff;
        border: none;
    }

    .chart-panel h6,
    .chart-panel .text-muted,
    .chart-panel small {
        color: inherit !important;
    }

    .chart-panel .form-select {
        background-color: rgba(255,255,255,0.12);
        border-color: rgba(255,255,255,0.2);
        color: #fff;
    }

    .chart-panel .form-select option {
        color: #0f172a;
    }

    @media (max-width: 576px) {
        #deployment-wrapper { padding: 1rem; }
    }
</style>

<div id="deployment-wrapper">

    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <li class="breadcrumb-item"><a href="{{ route('deployment.dashboard') }}" class="text-decoration-none">System</a></li>
                    <li class="breadcrumb-item active">Deployment Console</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">Manager Overview</h3>
            <span class="text-muted small">Real-time deployment & payment tracking</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('deployment.export') }}" class="btn btn-light border bg-white shadow-sm">
                <i class="fas fa-file-export me-2 text-secondary"></i> Export
            </a>
            <a href="{{ route('deployment.users.create') }}" class="btn btn-primary shadow-sm px-4">
                <i class="fas fa-rocket me-2"></i> New Registration
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $totalRevenue = $metrics['monthlyRevenue'] ?? 0;
            $commissionRate = (float) ($metrics['commissionRate'] ?? (\App\Http\Controllers\SuperAdmin\DeploymentManagerController::COMMISSION_RATE));
            $commissionEarned = (float) ($metrics['totalCommissions'] ?? (($totalRevenue * $commissionRate) / 100));
            $commissionPaid = (float) ($metrics['paidCommissions'] ?? 0);
            $commissionPending = (float) ($metrics['pendingCommissions'] ?? 0);
            $commissionProcessing = (float) ($metrics['processingPayouts'] ?? 0);
            $pendingPayments = $metrics['pendingPayments'] ?? 0;
            $payoutStatus = (string) ($metrics['payoutStatus'] ?? 'not_configured');
            $autoPayoutEnabled = (bool) ($metrics['autoPayoutEnabled'] ?? false);
            $minimumPayoutAmount = (float) ($metrics['minimumPayoutAmount'] ?? 5000);
        @endphp

        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-3 border-start border-3" style="border-start-color: #7c3aed !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-semibold mb-1" style="font-size:10px;letter-spacing:.6px;">TOTAL COMPANIES</p>
                        <div class="fw-bold text-dark" style="font-size:1.5rem;line-height:1;">{{ number_format($metrics['totalCompanies'] ?? 0) }}</div>
                        <div class="mt-1" style="font-size:11px;color:#7c3aed;font-weight:600;">
                            <i class="fas fa-arrow-up"></i> {{ ($metrics['totalCompanies'] ?? 0) > 0 ? '+12%' : '0%' }} <span class="text-muted fw-normal">vs last month</span>
                        </div>
                    </div>
                    <div class="metric-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fas fa-building"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-card revenue-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-white-50 fw-semibold mb-1" style="font-size:10px;letter-spacing:.6px;">TOTAL REVENUE</p>
                        <div class="fw-bold text-white" style="font-size:1.5rem;line-height:1;">₦{{ number_format($totalRevenue, 0) }}</div>
                        <div class="mt-1 text-white" style="font-size:11px;font-weight:600;">
                            <i class="fas fa-chart-line"></i> This Month <span class="text-white-50 fw-normal">all subs</span>
                        </div>
                    </div>
                    <div class="metric-icon" style="background:rgba(255,255,255,.2);color:#fff;"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-card commission-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-white-50 fw-semibold mb-1" style="font-size:10px;letter-spacing:.6px;">COMMISSION EARNED</p>
                        <div class="fw-bold text-white" style="font-size:1.5rem;line-height:1;">₦{{ number_format($commissionEarned, 0) }}</div>
                        <div class="mt-1 text-white" style="font-size:11px;font-weight:600;">
                            <i class="fas fa-check-circle"></i> {{ number_format($commissionRate, 1) }}% rate
                            <span class="text-white-50 fw-normal">· Paid ₦{{ number_format($commissionPaid, 0) }}</span>
                        </div>
                    </div>
                    <div class="metric-icon" style="background:rgba(255,255,255,.2);color:#fff;"><i class="fas fa-percentage"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-3 border-start border-3" style="border-start-color: #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-semibold mb-1" style="font-size:10px;letter-spacing:.6px;">ACTIVE SUBSCRIPTIONS</p>
                        <div class="fw-bold text-dark" style="font-size:1.5rem;line-height:1;">{{ number_format($metrics['activeSubscriptions'] ?? 0) }}</div>
                        <div class="mt-1" style="font-size:11px;color:#10b981;font-weight:600;">
                            <i class="fas fa-sync-alt"></i> Recurring <span class="text-muted fw-normal">monthly income</span>
                        </div>
                    </div>
                    <div class="metric-icon" style="background:#ecfdf5;color:#10b981;"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-3" style="background:linear-gradient(135deg,#fff7ed,#ffedd5);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:10px;font-weight:700;letter-spacing:.6px;color:#92400e;">AVAILABLE COMMISSION</span>
                    <span class="badge bg-warning text-dark" style="font-size:9px;">{{ $commissionPending > 0 ? 'YES' : 'NO' }}</span>
                </div>
                <div class="fw-bold text-warning" style="font-size:1.2rem;line-height:1.2;">₦{{ number_format($commissionPending, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">Ready for payout cycle</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-3" style="background:linear-gradient(135deg,#ecfeff,#cffafe);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:10px;font-weight:700;letter-spacing:.6px;color:#155e75;">TRIAL ACCOUNTS</span>
                    <span class="badge bg-info" style="font-size:9px;">{{ $metrics['trialCount'] ?? 0 }}</span>
                </div>
                <div class="fw-bold text-info" style="font-size:1.2rem;line-height:1.2;">{{ $metrics['trialCount'] ?? 0 }}</div>
                <div class="text-muted" style="font-size:11px;">Convert to paid</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-3" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:10px;font-weight:700;letter-spacing:.6px;color:#1e40af;">PROCESSING PAYOUTS</span>
                    <i class="fas fa-spinner text-primary" style="font-size:12px;"></i>
                </div>
                <div class="fw-bold text-primary" style="font-size:1.2rem;line-height:1.2;">₦{{ number_format($commissionProcessing, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">Awaiting settlement</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-3" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:10px;font-weight:700;letter-spacing:.6px;color:#065f46;">PAYOUT READINESS</span>
                    <i class="fas fa-university text-success" style="font-size:12px;"></i>
                </div>
                <div class="fw-bold text-success text-uppercase" style="font-size:1rem;line-height:1.2;">{{ str_replace('_', ' ', $payoutStatus) }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $autoPayoutEnabled ? 'Auto payout on' : 'Manual mode' }} · Min ₦{{ number_format($minimumPayoutAmount, 0) }}</div>
            </div>
        </div>
    </div>

    @php
        $pendingPaymentsValue = $metrics['pendingPaymentsValue']  ?? 0;
        $pendingPaymentsCount = $metrics['pendingPayments']        ?? 0;
        $expiringSoon         = $metrics['expiringSoonSubscriptions'] ?? 0;
        $activeSubsCount      = $metrics['activeSubscriptions']    ?? 0;
        $pendingValueRatio    = $totalRevenue > 0 ? min(($pendingPaymentsValue / max($totalRevenue,1)) * 100, 100) : 0;
        $expiryRatio          = $activeSubsCount > 0 ? min(($expiringSoon / max($activeSubsCount,1)) * 100, 100) : 0;
    @endphp
    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="glass-card p-3" style="background:linear-gradient(135deg,#f43f5e 0%,#fda4af 100%);border-left:5px solid #be185d;box-shadow:0 2px 16px 0 rgba(244,63,94,0.08);color:#fff;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <p style="font-size:10px;font-weight:700;letter-spacing:.6px;color:#fff;margin-bottom:2px;">UNCOLLECTED REVENUE</p>
                        <div class="fw-bold" style="font-size:1.55rem;line-height:1;color:#fff;">₦{{ number_format($pendingPaymentsValue, 0) }}</div>
                    </div>
                    <div class="metric-icon" style="background:#fda4af;color:#fff;"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="mb-2">
                    <div class="progress" style="height:4px;background:#fbcfe8;border-radius:99px;">
                        <div class="progress-bar" style="background:#fff;width:{{ $pendingValueRatio }}%;border-radius:99px;"></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px;color:#fff;">{{ $pendingPaymentsCount }} unpaid transaction{{ $pendingPaymentsCount != 1 ? 's' : '' }}</span>
                    <a href="{{ route('deployment.payments.pending') }}" style="font-size:11px;font-weight:700;color:#fff;text-decoration:none;">Collect Now <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="glass-card p-3" style="background:linear-gradient(135deg,#f59e0b 0%,#fef08a 100%);border-left:5px solid #b45309;box-shadow:0 2px 16px 0 rgba(245,158,11,0.08);color:#fff;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <p style="font-size:10px;font-weight:700;letter-spacing:.6px;color:#fff;margin-bottom:2px;">EXPIRING IN 7 DAYS</p>
                        <div class="fw-bold" style="font-size:1.55rem;line-height:1;color:#fff;">
                            {{ number_format($expiringSoon) }} <span style="font-size:.85rem;font-weight:400;">subscriptions</span>
                        </div>
                    </div>
                    <div class="metric-icon" style="background:#fef08a;color:#fff;"><i class="fas fa-hourglass-half"></i></div>
                </div>
                <div class="mb-2">
                    <div class="progress" style="height:4px;background:#fde68a;border-radius:99px;">
                        <div class="progress-bar" style="background:#fff;width:{{ $expiryRatio }}%;border-radius:99px;"></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px;color:#fff;">{{ $activeSubsCount > 0 ? number_format($expiryRatio, 1).'% of active' : 'No active subs' }}</span>
                    <a href="{{ route('deployment.subscription.overview') }}" style="font-size:11px;font-weight:700;color:#fff;text-decoration:none;">Renew <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border shadow-sm mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <strong class="d-block text-dark mb-1">Commission Payout Center</strong>
            <span class="text-muted small">Keep bank details updated, enable auto payout when ready, and monitor available, processing, and settled commission from the payout center.</span>
        </div>
        <a href="{{ route('deployment.commissions.index') }}" class="btn btn-outline-primary btn-sm">Open Payout Center</a>
    </div>

    @if($pendingPayments > 0)
    <div class="alert alert-warning d-flex align-items-center mb-4 border-0 shadow-sm">
        <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
        <div>
            <strong>Payment Action Required:</strong> {{ $pendingPayments }} {{ Str::plural('payment', $pendingPayments) }} pending verification. 
            <a href="{{ route('deployment.payments.pending') }}" class="alert-link ms-2 fw-bold">Review Payments →</a>
        </div>
    </div>
    @endif

    @if(($metrics['expiringSoonSubscriptions'] ?? 0) > 0 || ($metrics['expiredSubscriptions'] ?? 0) > 0)
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Subscription Expiry Warning</strong>
                <div class="small mt-1">
                    {{ $metrics['expiringSoonSubscriptions'] ?? 0 }} due in 7 days,
                    {{ $metrics['expiredSubscriptions'] ?? 0 }} already expired.
                </div>
            </div>
            <a href="{{ route('deployment.subscription.overview') }}" class="btn btn-sm btn-outline-dark">Open Subscriptions</a>
        </div>
        @if(!empty($expiringSubscriptions) && $expiringSubscriptions->count() > 0)
        <hr class="my-3">
        <div class="row g-2">
            @foreach($expiringSubscriptions as $expiringSub)
            <div class="col-lg-6">
                <div class="bg-white border rounded p-2 small d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-bell text-warning me-1"></i>
                        {{ $expiringSub->company->company_name ?? $expiringSub->user->name ?? 'Customer' }}
                    </span>
                    <span class="fw-bold text-danger">
                        {{ \Carbon\Carbon::parse($expiringSub->end_date)->format('M d, Y') }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <h6 class="fw-bold text-muted text-uppercase small mb-3">Quick Navigation</h6>
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <a href="{{ route('deployment.users.create') }}" class="glass-card p-3 text-center h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
                <div class="fw-bold text-dark">New Customer</div>
                <small class="text-muted">Register & Pay</small>
            </a>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <a href="{{ route('deployment.companies.index') }}" class="glass-card p-3 text-center h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-building fa-2x text-success mb-2"></i>
                <div class="fw-bold text-dark">My Companies</div>
                <small class="text-muted">{{ $metrics['totalCompanies'] ?? 0 }} Active</small>
            </a>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <a href="{{ route('deployment.payments.index') }}" class="glass-card p-3 text-center h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-money-check-alt fa-2x text-warning mb-2"></i>
                <div class="fw-bold text-dark">Payments</div>
                <small class="text-muted">Track Status</small>
            </a>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <a href="{{ route('deployment.commissions.index') }}" class="glass-card p-3 text-center h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-dollar-sign fa-2x text-info mb-2"></i>
                <div class="fw-bold text-dark">Commissions</div>
                <small class="text-muted">₦{{ number_format($commissionEarned, 0) }}</small>
            </a>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <a href="{{ route('deployment.reports.performance') }}" class="glass-card p-3 text-center h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-chart-bar fa-2x text-danger mb-2"></i>
                <div class="fw-bold text-dark">Reports</div>
                <small class="text-muted">Analytics</small>
            </a>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <a href="{{ route('deployment.settings') }}" class="glass-card p-3 text-center h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-cog fa-2x text-secondary mb-2"></i>
                <div class="fw-bold text-dark">Settings</div>
                <small class="text-muted">Configure</small>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="glass-card chart-panel p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold" style="font-size:.85rem;">Revenue & Commission Trend</div>
                        <div style="font-size:11px;opacity:.7;">Monthly performance</div>
                    </div>
                    <select class="form-select form-select-sm w-auto" style="font-size:11px;">
                        <option>This Month</option>
                        <option>Last 3 Months</option>
                        <option>Yearly</option>
                    </select>
                </div>
                <div class="chart-box"><canvas id="revenueChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass-card p-3">
                <div class="fw-bold mb-1" style="font-size:.85rem;">Payment Status</div>
                <div class="text-muted mb-3" style="font-size:11px;">Distribution by status</div>
                <div class="chart-box"><canvas id="paymentStatusChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass-card p-3">
                <div class="fw-bold mb-1" style="font-size:.85rem;">Plans Breakdown</div>
                <div class="text-muted mb-3" style="font-size:11px;">Subscriptions by plan</div>
                <div class="chart-box"><canvas id="planBreakdownChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="glass-card p-3" style="background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 55%,#0c4a6e 100%);border:none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-white" style="font-size:.9rem;">
                            <i class="fas fa-water me-2" style="color:#818cf8;"></i>Subscription Activity Wave
                        </div>
                        <div style="font-size:11px;color:rgba(255,255,255,.45);">Daily registrations &amp; revenue — last 30 days</div>
                    </div>
                    <div class="d-flex gap-3">
                        <span style="font-size:11px;color:rgba(255,255,255,.6);">
                            <span style="display:inline-block;width:14px;height:3px;background:#818cf8;border-radius:3px;vertical-align:middle;margin-right:4px;"></span>Subs
                        </span>
                        <span style="font-size:11px;color:rgba(255,255,255,.6);">
                            <span style="display:inline-block;width:14px;height:3px;background:#22d3ee;border-radius:3px;vertical-align:middle;margin-right:4px;"></span>Revenue
                        </span>
                    </div>
                </div>
                <div style="position:relative;height:160px;"><canvas id="waveActivityChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="glass-card overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <h6 class="fw-bold mb-0">Recent Subscriptions</h6>
                    <a href="{{ route('deployment.subscription.overview') }}" class="btn btn-xs btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Company</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Commission</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSubscriptions ?? [] as $subscription)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                            {{ strtoupper(substr($subscription->company->name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $subscription->company->name ?? 'N/A' }}</div>
                                            <div class="text-muted small">{{ $subscription->created_at->format('M d, Y') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $subscription->plan ?? 'Basic' }}</span></td>
                                <td><span class="fw-bold text-success">₦{{ number_format($subscription->amount ?? 0, 0) }}</span></td>
                                <td>
                                    @php
                                        $paymentClass = match($subscription->payment_status ?? 'pending') {
                                            'paid' => 'bg-success-subtle text-success',
                                            'pending' => 'bg-warning-subtle text-warning',
                                            default => 'bg-danger-subtle text-danger'
                                        };
                                    @endphp
                                    <span class="status-pill {{ $paymentClass }}">{{ ucfirst($subscription->payment_status ?? 'pending') }}</span>
                                </td>
                                <td>
                                    @php
                                        $commission = ($subscription->amount * $commissionRate) / 100;
                                    @endphp
                                    <span class="fw-bold text-info">₦{{ number_format($commission, 0) }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('deployment.subscription.history', $subscription->id) }}" class="btn btn-sm btn-light border" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3 d-block opacity-50"></i>
                                    <p class="mb-2">No subscriptions yet.</p>
                                    <a href="{{ route('deployment.users.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-2"></i>Register First Customer
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">Recent Activity</h6>
                    <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size: 6px;"></i> Live</span>
                </div>
                <div class="activity-feed" style="max-height: 300px; overflow-y: auto;">
                    @forelse(($recentActivities ?? collect())->take(8) as $activity)
                    <div class="feed-item">
                        <div class="fw-bold text-dark small">{{ $activity->description ?? 'Activity logged' }}</div>
                        <div class="text-muted small d-flex justify-content-between mt-1">
                            <span><i class="fas fa-user-circle me-1"></i> {{ auth()->user()->name }}</span>
                            <span>{{ isset($activity->created_at) ? $activity->created_at->diffForHumans() : 'Just now' }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3 small"><i class="fas fa-info-circle mb-2 d-block"></i>No recent activity.</div>
                    @endforelse
                </div>

                <div class="mt-4 p-3 bg-dark rounded-3 text-white">
                    <h6 class="fw-bold mb-3 small text-uppercase">Performance Stats</h6>
                    @php
                        $manager = \App\Models\DeploymentManager::where('user_id', auth()->id())->first();
                        $deploymentLimit = $manager->deployment_limit ?? 10;
                        $currentCount = $metrics['totalCompanies'] ?? 0;
                        $capacityPercent = $deploymentLimit > 0 ? ($currentCount / $deploymentLimit) * 100 : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Deployment Capacity</span>
                            <span>{{ $currentCount }}/{{ $deploymentLimit }}</span>
                        </div>
                        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar {{ $capacityPercent > 80 ? 'bg-warning' : 'bg-success' }}" style="width: {{ min($capacityPercent, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Commission Rate</span>
                            <span>{{ number_format($commissionRate, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-info" style="width: {{ min($commissionRate * 10, 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Revenue Target</span>
                            <span>{{ number_format(($totalRevenue / 100000) * 100, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-primary" style="width: {{ min(($totalRevenue / 100000) * 100, 100) }}%"></div>
                        </div>
                        <small class="text-white-50 mt-1 d-block">Target: ₦100,000/month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.font.family = "'Inter', -apple-system, sans-serif";
    Chart.defaults.color = '#64748b';

    const subscriptions = @json($recentSubscriptions ?? []);
    const commissionRate = {{ $commissionRate }};

    // Revenue & Commission Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const ctx = revenueCtx.getContext('2d');

        // Calculate monthly data
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const revenueData = {};
        const commissionData = {};

        subscriptions.forEach(s => {
            if(s.payment_status === 'paid') {
                const d = new Date(s.created_at);
                const k = `${monthNames[d.getMonth()]} ${d.getFullYear()}`;
                revenueData[k] = (revenueData[k] || 0) + (s.amount || 0);
                commissionData[k] = (commissionData[k] || 0) + ((s.amount || 0) * commissionRate / 100);
            }
        });

        const labels = Object.keys(revenueData).slice(-6);
        const revenueValues = labels.map(l => revenueData[l] || 0);
        const commissionValues = labels.map(l => commissionData[l] || 0);
        const revenueGradient = ctx.createLinearGradient(0, 0, 0, 260);
        revenueGradient.addColorStop(0, 'rgba(125, 211, 252, 0.45)');
        revenueGradient.addColorStop(0.45, 'rgba(59, 130, 246, 0.22)');
        revenueGradient.addColorStop(1, 'rgba(59, 130, 246, 0.04)');
        const commissionGradient = ctx.createLinearGradient(0, 0, 0, 260);
        commissionGradient.addColorStop(0, 'rgba(110, 231, 183, 0.42)');
        commissionGradient.addColorStop(0.45, 'rgba(16, 185, 129, 0.18)');
        commissionGradient.addColorStop(1, 'rgba(16, 185, 129, 0.03)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueValues.length ? revenueValues : [0],
                        borderColor: '#7dd3fc',
                        backgroundColor: revenueGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#7dd3fc',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Commission',
                        data: commissionValues.length ? commissionValues : [0],
                        borderColor: '#6ee7b7',
                        backgroundColor: commissionGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#6ee7b7',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [2, 4], color: 'rgba(255,255,255,0.12)' },
                        ticks: {
                            color: 'rgba(255,255,255,0.84)',
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: 'rgba(255,255,255,0.84)' }
                    }
                },
                plugins: { 
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            color: 'rgba(255,255,255,0.9)'
                        }
                    }, 
                    tooltip: { 
                        backgroundColor: 'rgba(15,23,42,0.92)', 
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₦' + context.parsed.y.toLocaleString();
                            }
                        }
                    } 
                }
            }
        });
    }

    // Payment Status Chart
    const paymentStatusCtx = document.getElementById('paymentStatusChart');
    if (paymentStatusCtx) {
        const statusCounts = { Paid: 0, Pending: 0, Failed: 0 };
        subscriptions.forEach(s => {
            const status = s.payment_status?.charAt(0).toUpperCase() + s.payment_status?.slice(1) || 'Pending';
            if (statusCounts.hasOwnProperty(status)) {
                statusCounts[status]++;
            }
        });

        new Chart(paymentStatusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusCounts),
                datasets: [{
                    data: Object.values(statusCounts),
                    backgroundColor: ['#2563eb', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            usePointStyle: true, 
                            padding: 20, 
                            font: { size: 12 } 
                        } 
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(ctx) {
                                const l = ctx.label || '';
                                const v = ctx.parsed || 0;
                                const t = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const p = t > 0 ? ((v / t) * 100).toFixed(1) : 0;
                                return `${l}: ${v} (${p}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Plans Breakdown Chart
    const planBreakdownCtx = document.getElementById('planBreakdownChart');
    if (planBreakdownCtx) {
        const planCounts = {};
        subscriptions.forEach(s => {
            const plan = s.plan_name || 'Unknown';
            planCounts[plan] = (planCounts[plan] || 0) + 1;
        });
        const planLabels = Object.keys(planCounts);
        const planData   = Object.values(planCounts);
        const palette    = ['#2563eb','#7c3aed','#059669','#d97706','#dc2626','#0891b2'];

        new Chart(planBreakdownCtx, {
            type: 'bar',
            data: {
                labels: planLabels,
                datasets: [{
                    label: 'Subscriptions',
                    data: planData,
                    backgroundColor: planLabels.map((_, i) => palette[i % palette.length]),
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 10,
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} subscription${ctx.parsed.y !== 1 ? 's' : ''}`
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 10 }, precision: 0 } }
                }
            }
        });
    }

    // 30-Day Wave Activity Chart
    const waveCtx = document.getElementById('waveActivityChart');
    if (waveCtx) {
        const today = new Date();
        const waveLabels  = [];
        const waveSubData = [];
        const waveRevData = [];

        for (let i = 29; i >= 0; i--) {
            const d = new Date(today);
            d.setDate(d.getDate() - i);
            const dayStr = d.toISOString().slice(0, 10);
            waveLabels.push(i % 5 === 0 ? d.toLocaleDateString('en-NG', { month: 'short', day: 'numeric' }) : '');
            let cnt = 0, rev = 0;
            subscriptions.forEach(s => {
                if (s.created_at && s.created_at.slice(0, 10) === dayStr) { cnt++; rev += parseFloat(s.amount || 0); }
            });
            waveSubData.push(cnt);
            waveRevData.push(rev);
        }

        const wc = waveCtx.getContext('2d');
        const subGrad = wc.createLinearGradient(0, 0, 0, 160);
        subGrad.addColorStop(0, 'rgba(129,140,248,0.55)');
        subGrad.addColorStop(1, 'rgba(129,140,248,0.0)');
        const revGrad = wc.createLinearGradient(0, 0, 0, 160);
        revGrad.addColorStop(0, 'rgba(34,211,238,0.45)');
        revGrad.addColorStop(1, 'rgba(34,211,238,0.0)');

        new Chart(waveCtx, {
            type: 'line',
            data: {
                labels: waveLabels,
                datasets: [
                    {
                        label: 'Subscriptions', data: waveSubData,
                        borderColor: '#818cf8', backgroundColor: subGrad,
                        fill: 'origin', tension: 0.45, pointRadius: 0, pointHoverRadius: 5,
                        borderWidth: 2, yAxisID: 'ySubs'
                    },
                    {
                        label: 'Revenue (₦)', data: waveRevData,
                        borderColor: '#22d3ee', backgroundColor: revGrad,
                        fill: 'origin', tension: 0.45, pointRadius: 0, pointHoverRadius: 5,
                        borderWidth: 2, yAxisID: 'yRev'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.95)',
                        padding: 10,
                        borderColor: 'rgba(129,140,248,0.3)',
                        borderWidth: 1,
                        titleColor: 'rgba(255,255,255,0.7)',
                        bodyColor: '#fff',
                        callbacks: {
                            label: ctx => ctx.datasetIndex === 0
                                ? ` ${ctx.parsed.y} sub${ctx.parsed.y !== 1 ? 's' : ''}`
                                : ` ₦${ctx.parsed.y.toLocaleString()}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.06)' },
                        ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 9 } },
                        border: { color: 'rgba(255,255,255,0.1)' }
                    },
                    ySubs: {
                        type: 'linear', position: 'left',
                        grid: { color: 'rgba(255,255,255,0.06)' },
                        ticks: { color: 'rgba(255,255,255,0.45)', font: { size: 9 }, precision: 0, maxTicksLimit: 4 },
                        border: { color: 'rgba(255,255,255,0.1)' }
                    },
                    yRev: {
                        type: 'linear', position: 'right',
                        grid: { display: false },
                        ticks: {
                            color: 'rgba(34,211,238,0.6)', font: { size: 9 }, maxTicksLimit: 4,
                            callback: v => '₦' + (v >= 1000 ? (v/1000).toFixed(1)+'k' : v)
                        },
                        border: { color: 'rgba(34,211,238,0.2)' }
                    }
                }
            }
        });
    }
});
</script>
@endpush
