@extends('layout.mainlayout')

@section('content')
<style>
    :root {
        --deep-sapphire: #002347;
        --crystal-blue: #f8fbff;
        --bubble-color: rgba(0, 35, 71, 0.04);
        --accent-orange: #ff8c00;
    }

    .pos-content-area {
        margin-left: 250px;
        padding: 40px;
        margin-top: 60px;
        background-color: var(--crystal-blue);
        min-height: 100vh;
        position: relative;
    }

    body.mini-sidebar .pos-content-area,
    body.sidebar-icon-only .pos-content-area {
        margin-left: 80px;
    }

    @media (max-width: 991.98px) {
        .pos-content-area {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 20px;
        }
    }

    /* Floating Bubbles for the Node */
    .pos-content-area::before {
        content: ''; position: absolute; width: 400px; height: 400px;
        background: var(--bubble-color); filter: blur(60px);
        border-radius: 50%; top: -100px; right: -50px;
    }

    .plan-badge {
        background: #e2e8f0; color: #475569;
        font-size: 0.7rem; font-weight: 800;
        padding: 4px 12px; border-radius: 50px;
        text-transform: uppercase; letter-spacing: 0.5px;
    }

    .metric-card-basic {
        border: none; border-radius: 20px;
        background: #fff; box-shadow: 0 10px 30px rgba(0,35,71,0.05);
        transition: transform 0.3s; padding: 25px;
        position: relative; z-index: 1;
        height: 100%;
    }

    .metric-card-basic:hover {
        transform: translateY(-5px);
    }

    .upgrade-banner {
        background: linear-gradient(135deg, var(--deep-sapphire) 0%, #004a8f 100%);
        border-radius: 20px; color: white; padding: 30px;
        position: relative; overflow: hidden; z-index: 1;
    }
    
    .upgrade-banner::after {
        content: '\f521'; font-family: 'Font Awesome 5 Free'; font-weight: 900;
        position: absolute; right: -20px; bottom: -20px;
        font-size: 8rem; opacity: 0.1;
    }

    .status-dot {
        height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px;
    }

    /* Working Domain Reference: env('SESSION_DOMAIN', null) */
</style>

<div class="pos-content-area">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Standard Node Dashboard</h3>
                <span class="plan-badge">Basic Plan</span>
            </div>
            <p class="text-muted small">Live analytics for <strong>{{ request()->getHost() }}</strong> | Domain: <code>{{ env('SESSION_DOMAIN', 'Localhost') }}</code></p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="printDashboard()" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="fas fa-print me-2"></i> Print Report
            </button>
            <img src="{{ asset('assets/img/smat14.png') }}" style="height: 35px;">
        </div>
    </div>

    {{-- Primary Metrics Row --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="metric-card-basic">
                <div class="text-muted small fw-bold mb-1">TODAY'S REVENUE</div>
                <h3 class="fw-bold mb-0" style="color: var(--deep-sapphire);">₦{{ number_format($metrics['todayRevenue'] ?? 0, 2) }}</h3>
                <p class="text-success small mb-0 mt-2"><i class="fas fa-arrow-up"></i> Live</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card-basic">
                <div class="text-muted small fw-bold mb-1">TOTAL INVOICES</div>
                <h3 class="fw-bold mb-0" style="color: #6f42c1;">{{ number_format($metrics['totalInvoices'] ?? 0) }}</h3>
                <p class="text-muted small mb-0 mt-2">Processed today</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card-basic">
                <div class="text-muted small fw-bold mb-1">ACTIVE CUSTOMERS</div>
                <h3 class="fw-bold mb-0" style="color: #0dcaf0;">{{ number_format($metrics['activeCustomers'] ?? 0) }}</h3>
                <p class="text-muted small mb-0 mt-2">Registered on node</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card-basic">
                <div class="text-muted small fw-bold mb-1">STOCK VALUE</div>
                <h3 class="fw-bold mb-0" style="color: var(--accent-orange);">{{ number_format($metrics['activeStock'] ?? 0) }}</h3>
                <p class="text-muted small mb-0 mt-2">Items in inventory</p>
            </div>
        </div>
    </div>

    {{-- Secondary Analytics Row --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card metric-card-basic p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Monthly Revenue Trend</h5>
                    <small class="text-muted">{{ date('Y') }}</small>
                </div>
                <div style="height: 280px;">
                    <canvas id="basicRevenueChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card metric-card-basic p-4">
                <h5 class="fw-bold mb-3" style="color: var(--deep-sapphire);">Top Products by Quantity</h5>
                <div style="height: 280px;">
                    <canvas id="basicTopProductsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Analytics Row --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card metric-card-basic p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Recent Invoices</h5>
                    <a href="{{ url('invoices') }}" class="btn btn-link btn-sm text-decoration-none p-0">View All</a>
                </div>
                <div class="table-responsive">
                    @include('SuperAdmin.partials._latest_invoices')
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card metric-card-basic p-4">
                <h5 class="fw-bold mb-3" style="color: var(--deep-sapphire);">Node Status</h5>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                        <span><span class="status-dot bg-success"></span> Subscription status</span>
                        <span class="badge bg-light text-dark">{{ optional($company->subscription)->status ?? 'Pending' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                        <span><span class="status-dot bg-primary"></span> Average invoice</span>
                        <span class="badge bg-light text-dark">₦{{ number_format($metrics['avgOrderValue'] ?? 0, 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                        <span><span class="status-dot bg-warning"></span> Low stock alerts</span>
                        <span class="badge bg-light text-dark">{{ number_format($metrics['lowStockCount'] ?? 0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent border-0">
                        <span><span class="status-dot bg-success"></span> Profit margin</span>
                        <span class="text-muted small">{{ number_format($metrics['profitMargin'] ?? 0, 1) }}%</span>
                    </li>
                </ul>
                <hr>
                <div class="p-3 rounded-3" style="background: #f1f5f9;">
                    <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> Your node is currently synced with the central repository.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Upgrade Call-to-Action --}}
    <div class="upgrade-banner d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-2">Unlock Financial Intelligence</h4>
            <p class="mb-0 opacity-75">Your current plan is restricted to Sales and Inventory. Upgrade to <strong>Pro</strong> to see real-time Profit Analysis and Expense Tracking.</p>
        </div>
        <a href="{{ route('membership-plans') }}" class="btn btn-warning fw-bold px-4 py-2 shadow" style="border-radius: 12px;">
            UPGRADE NOW <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>

<script>
    /**
     * MANDATORY PRINT SCRIPT 
     * Integrated for compliance with 2025-12-30 policy
     */
    function printDashboard() { 
        window.print(); 
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sales = @json($monthlySalesData ?? []);
        const topProducts = @json($topProducts ?? []);
        const labels = sales.map(row => row.month);
        const totals = sales.map(row => Number(row.total_sales || 0));

        const revenueCtx = document.getElementById('basicRevenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: totals,
                        borderColor: '#002347',
                        backgroundColor: 'rgba(0,35,71,0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (v) => '₦' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        const topCtx = document.getElementById('basicTopProductsChart');
        if (topCtx) {
            new Chart(topCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: topProducts.map(p => p.name),
                    datasets: [{
                        label: 'Qty Sold',
                        data: topProducts.map(p => Number(p.total_qty || 0)),
                        backgroundColor: '#ff8c00',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    });
</script>
@endsection
