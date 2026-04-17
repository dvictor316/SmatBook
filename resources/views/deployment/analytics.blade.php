@extends('layout.mainlayout')

@section('page-title', 'Deployment Analytics')

@php
    /**
     * SUBDOMAIN & ROLE LOGIC
     */
    $sub = $routeParams['subdomain'] ?? request()->route('subdomain') ?? 'admin';
    $isAdmin = auth()->user()->hasRole('super_admin');
@endphp

<style>
    /* ============================================
       LAYOUT ADJUSTMENTS FOR 270px SIDEBAR
       ============================================ */
    .page-content-wrapper {
        margin-left: 270px; /* Updated to 270px as requested */
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 25px;
        min-height: calc(100vh - 70px);
        background: #f8fafc;
    }

    body.sidebar-collapsed .page-content-wrapper { 
        margin-left: 80px; 
    }

    @media (max-width: 991px) {
        .page-content-wrapper { 
            margin-left: 0 !important; 
            padding: 15px; 
        }
    }

    /* Analytics Card Styling */
    .stat-card {
        border-radius: 12px;
        border: none;
        background: #fff;
        padding: 20px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); }

    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 15px;
    }

    .chart-container {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 25px;
    }

    .breadcrumb-container { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 20px; }
    .breadcrumb-container a { color: #1e40af; text-decoration: none; font-weight: 500; }
</style>

@section('content')
<div class="page-content-wrapper">

    <div class="breadcrumb-container d-print-none">
        <a href="{{ route('super_admin.dashboard', ['subdomain' => $sub]) }}">Dashboard</a>
        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
        <span class="text-muted">Analytics & Reports</span>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color:#0f172a;">Deployment Analytics</h4>
            <p class="text-muted small mb-0">Performance overview for your onboarded clients.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-white border shadow-sm btn-sm px-3" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Export PDF
            </button>
            <form action="" method="GET" id="filterForm">
                <select name="period" class="form-select form-select-sm border shadow-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="30" {{ request('period') == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="180" {{ request('period') == '180' ? 'selected' : '' }}>Last 6 Months</option>
                    <option value="365" {{ request('period') == '365' ? 'selected' : '' }}>This Year</option>
                </select>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box" style="background: #eff6ff; color: #1d4ed8;">
                    <i class="fas fa-building"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Companies</h6>
                <h3 class="fw-bold mb-0">{{ number_format($totalCompanies ?? 0) }}</h3>
                <div class="mt-2 small">
                    <span class="text-muted">Total onboarded</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box" style="background: #f0fdf4; color: #15803d;">
                    <i class="fas fa-users"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Active Users</h6>
                <h3 class="fw-bold mb-0">{{ number_format($totalUsers ?? 0) }}</h3>
                <div class="mt-2 small">
                    <span class="text-muted">Across all instances</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box" style="background: #fff7ed; color: #c2410c;">
                    <i class="fas fa-wallet"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Revenue</h6>
                <h3 class="fw-bold mb-0">₦{{ number_format($totalRevenue ?? 0, 2) }}</h3>
                <div class="mt-2 small">
                    <span class="text-muted">Accumulated earnings</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box" style="background: #fef2f2; color: #b91c1c;">
                    <i class="fas fa-clock"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Pending Approvals</h6>
                <h3 class="fw-bold mb-0">{{ $pendingApprovals ?? 0 }}</h3>
                <div class="mt-2 small">
                    <span class="text-muted">Awaiting activation</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="chart-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Revenue Trend</h5>
                    <i class="fas fa-chart-line text-muted"></i>
                </div>
                <div id="revenueChart" style="min-height: 350px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <h5 class="fw-bold mb-4">Clients by Status</h5>
                <div id="statusPieChart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>

    <div class="chart-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Top Performing Clients</h5>
            <a href="{{ route('deployment.companies.index', ['subdomain' => $sub]) }}" class="text-primary small text-decoration-none fw-bold">View All Clients</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-muted text-uppercase">
                        <th class="ps-3">Company Name</th>
                        <th>Onboard Date</th>
                        <th>User Count</th>
                        <th>Subscription</th>
                        <th class="text-end pe-3">Revenue (₦)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCompanies ?? [] as $company)
                    <tr>
                        <td class="ps-3 fw-bold text-dark">{{ $company->name }}</td>
                        <td>{{ $company->created_at->format('M d, Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $company->users_count ?? 0 }} Users</span></td>
                        <td>
                            @php $status = $company->subscription->status ?? 'inactive'; @endphp
                            <span class="badge rounded-pill {{ $status == 'active' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}" style="font-size: 11px;">
                                {{ ucfirst($status) }} - {{ $company->subscription->plan_name ?? 'No Plan' }}
                            </span>
                        </td>
                        <td class="text-end pe-3 fw-bold">₦{{ number_format($company->payments_sum_amount ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted small">
                            <i class="fas fa-inbox fa-2x mb-3 d-block opacity-25"></i>
                            No data available for top performing clients.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // --- Dynamic Revenue Trend Chart ---
        // Expecting $revenueTrendData as [val1, val2...] and $revenueTrendLabels as ['Jan', 'Feb'...]
        var revenueOptions = {
            series: [{
                name: 'Revenue',
                data: @json($revenueTrendData ?? [])
            }],
            chart: { height: 350, type: 'area', toolbar: { show: false }, zoom: { enabled: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3, colors: ['#1e40af'] },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 90, 100] } },
            xaxis: {
                categories: @json($revenueTrendLabels ?? []),
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            colors: ['#1e40af'],
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return "₦" + value.toLocaleString();
                    }
                }
            }
        };
        new ApexCharts(document.querySelector("#revenueChart"), revenueOptions).render();

        // --- Dynamic Status Distribution Chart ---
        // Expecting $statusCounts as ['active' => X, 'pending' => Y, 'suspended' => Z]
        var statusOptions = {
            series: [
                @json($statusCounts['active'] ?? 0), 
                @json($statusCounts['pending'] ?? 0), 
                @json($statusCounts['suspended'] ?? 0)
            ],
            chart: { type: 'donut', height: 350 },
            labels: ['Active', 'Pending', 'Suspended'],
            colors: ['#16a34a', '#3b82f6', '#dc2626'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '75%' } } }
        };
        new ApexCharts(document.querySelector("#statusPieChart"), statusOptions).render();
    });
</script>
@endsection