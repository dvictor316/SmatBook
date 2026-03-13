@extends('layout.mainlayout')

@section('title', 'Deployment Console')

@section('content')

{{-- 
    DEPLOYMENT MANAGER DASHBOARD WITH PAYMENT TRACKING
    Fetches data from DeploymentManagerController
    Shows: Total Revenue, Commission Earned, Pending Payments, Active Subscriptions
--}}
<style>
    :root {
        --side-w: 270px;
        --side-w-collapsed: 70px;
        --primary-color: #2563eb;
        --glass-border: #e2e8f0;
    }

    #deployment-wrapper {
        transition: margin-left 0.3s ease, padding-top 0.3s ease;
        padding: 1.5rem;
        padding-top: 12px;
        min-height: 100vh;
        background: #f8fafc;
    }

    @media (min-width: 992px) {
        #deployment-wrapper { margin-left: var(--side-w); width: calc(100% - var(--side-w)); }
        body.mini-sidebar #deployment-wrapper,
        body.sidebar-collapsed #deployment-wrapper,
        body.sidebar-icon-only #deployment-wrapper { margin-left: var(--side-w-collapsed); width: calc(100% - var(--side-w-collapsed)); }
    }

    @media (max-width: 991.98px) {
        #deployment-wrapper { margin-left: 0; width: 100%; padding-top: 10px; }
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

    .metric-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .status-pill { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    .chart-box { position: relative; height: 260px; width: 100%; }
    .feed-item { border-left: 2px solid #e2e8f0; padding-left: 20px; position: relative; padding-bottom: 20px; }
    .feed-item:last-child { border-left: 2px solid transparent; }
    .feed-item::before { content: ""; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--primary-color); box-shadow: 0 0 0 4px #f1f5f9; }

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

    @media (max-width: 576px) {
        #deployment-wrapper { padding: 1rem; padding-top: 8px; }
    }
</style>

<div id="deployment-wrapper">
    
    {{-- Top Action Bar --}}
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

    {{-- PRIMARY METRICS WITH PAYMENT DATA --}}
    <div class="row g-3 mb-4">
        @php
            $totalRevenue = $metrics['monthlyRevenue'] ?? 0;
            $commissionRate = (float) ($metrics['commissionRate'] ?? (\App\Http\Controllers\SuperAdmin\DeploymentManagerController::COMMISSION_RATE));
            $commissionEarned = (float) ($metrics['totalCommissions'] ?? (($totalRevenue * $commissionRate) / 100));
            $commissionPaid = (float) ($metrics['paidCommissions'] ?? 0);
            $commissionPending = (float) ($metrics['pendingCommissions'] ?? 0);
            $pendingPayments = $metrics['pendingPayments'] ?? 0;
        @endphp

        {{-- Total Companies --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 border-start border-4" style="border-start-color: #7c3aed !important;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Total Companies</p>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($metrics['totalCompanies'] ?? 0) }}</h3>
                    </div>
                    <div class="metric-icon" style="background: #f5f3ff; color: #7c3aed;"><i class="fas fa-building"></i></div>
                </div>
                <div class="mt-3 small fw-bold" style="color: #7c3aed">
                    <i class="fas fa-arrow-up"></i> {{ ($metrics['totalCompanies'] ?? 0) > 0 ? '+12%' : '0%' }} <span class="text-muted fw-normal ms-1">vs last month</span>
                </div>
            </div>
        </div>

        {{-- Total Revenue Generated --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card revenue-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-white-50 fw-bold mb-1 small text-uppercase">Total Revenue</p>
                        <h3 class="fw-bold mb-0 text-white">₦{{ number_format($totalRevenue, 2) }}</h3>
                    </div>
                    <div class="metric-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-wallet"></i></div>
                </div>
                <div class="mt-3 small fw-bold text-white">
                    <i class="fas fa-chart-line"></i> This Month <span class="text-white-50 fw-normal ms-1">All subscriptions</span>
                </div>
            </div>
        </div>

        {{-- Commission Earned --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card commission-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-white-50 fw-bold mb-1 small text-uppercase">Commission Earned</p>
                        <h3 class="fw-bold mb-0 text-white">₦{{ number_format($commissionEarned, 2) }}</h3>
                    </div>
                    <div class="metric-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-percentage"></i></div>
                </div>
                <div class="mt-3 small fw-bold text-white">
                    <i class="fas fa-check-circle"></i> {{ number_format($commissionRate, 1) }}% Rate
                    <span class="text-white-50 fw-normal ms-1">Paid: ₦{{ number_format($commissionPaid, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Active Subscriptions --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 border-start border-4" style="border-start-color: #10b981 !important;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Active Subscriptions</p>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($metrics['activeSubscriptions'] ?? 0) }}</h3>
                    </div>
                    <div class="metric-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-user-check"></i></div>
                </div>
                <div class="mt-3 small fw-bold" style="color: #10b981">
                    <i class="fas fa-sync-alt"></i> Recurring <span class="text-muted fw-normal ms-1">monthly income</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Metrics Row --}}
    <div class="row g-3 mb-4">
        {{-- Pending Commission --}}
        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-4" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 small text-uppercase text-muted">Pending Commission</h6>
                    <span class="badge bg-warning">{{ $commissionPending > 0 ? 'YES' : 'NO' }}</span>
                </div>
                <h4 class="fw-bold text-warning mb-0">₦{{ number_format($commissionPending, 2) }}</h4>
                <small class="text-muted">Deducts automatically after payout</small>
            </div>
        </div>

        {{-- Trial Accounts --}}
        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-4" style="background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 small text-uppercase text-muted">Trial Accounts</h6>
                    <span class="badge bg-info">{{ $metrics['trialCount'] ?? 0 }}</span>
                </div>
                <h4 class="fw-bold text-info mb-0">{{ $metrics['trialCount'] ?? 0 }}</h4>
                <small class="text-muted">Convert to paid</small>
            </div>
        </div>

        {{-- Conversion Rate --}}
        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-4" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 small text-uppercase text-muted">Conversion Rate</h6>
                    <i class="fas fa-chart-line text-success"></i>
                </div>
                @php
                    $totalCompanies = $metrics['totalCompanies'] ?? 0;
                    $activeSubscriptions = $metrics['activeSubscriptions'] ?? 0;
                    $conversionRate = $totalCompanies > 0 ? ($activeSubscriptions / $totalCompanies) * 100 : 0;
                @endphp
                <h4 class="fw-bold text-success mb-0">{{ number_format($conversionRate, 1) }}%</h4>
                <small class="text-muted">Trial to paid</small>
            </div>
        </div>

        {{-- Average Deal Size --}}
        <div class="col-lg-3 col-md-6">
            <div class="glass-card p-4" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 small text-uppercase text-muted">Avg Deal Size</h6>
                    <i class="fas fa-dollar-sign text-primary"></i>
                </div>
                @php
                    $avgDealSize = $activeSubscriptions > 0 ? $totalRevenue / $activeSubscriptions : 0;
                @endphp
                <h4 class="fw-bold text-primary mb-0">₦{{ number_format($avgDealSize, 0) }}</h4>
                <small class="text-muted">Per subscription</small>
            </div>
        </div>
    </div>

    {{-- Payment Alert --}}
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

    {{-- QUICK ACTIONS --}}
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

    {{-- CHARTS --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h6 class="fw-bold mb-0">Revenue & Commission Trend</h6>
                        <small class="text-muted">Monthly performance tracking</small>
                    </div>
                    <select class="form-select form-select-sm w-auto">
                        <option>This Month</option>
                        <option>Last 3 Months</option>
                        <option>Yearly</option>
                    </select>
                </div>
                <div class="chart-box"><canvas id="revenueChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h6 class="fw-bold mb-1">Payment Status</h6>
                <small class="text-muted d-block mb-4">Distribution by status</small>
                <div class="chart-box"><canvas id="paymentStatusChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- TABLE & ACTIVITY --}}
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
                
                {{-- Stats Widget --}}
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

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueValues.length ? revenueValues : [0],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Commission',
                        data: commissionValues.length ? commissionValues : [0],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#10b981',
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
                        grid: { borderDash: [2, 4], color: '#f0f0f0' },
                        ticks: {
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        }
                    },
                    x: { grid: { display: false } }
                },
                plugins: { 
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }, 
                    tooltip: { 
                        backgroundColor: 'rgba(0,0,0,0.8)', 
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
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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
});
</script>
@endpush
