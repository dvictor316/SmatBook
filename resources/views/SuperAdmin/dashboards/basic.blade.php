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

    .metric-card-basic.metric-sky {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
    }
    .metric-card-basic.metric-violet {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        color: #fff;
    }
    .metric-card-basic.metric-cyan {
        background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
        color: #fff;
    }
    .metric-card-basic.metric-amber {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #fff;
    }
    .metric-card-basic.metric-sky .text-muted,
    .metric-card-basic.metric-violet .text-muted,
    .metric-card-basic.metric-cyan .text-muted,
    .metric-card-basic.metric-amber .text-muted {
        color: rgba(255,255,255,0.72) !important;
    }
    .metric-card-basic.metric-sky h3,
    .metric-card-basic.metric-violet h3,
    .metric-card-basic.metric-cyan h3,
    .metric-card-basic.metric-amber h3 {
        color: #fde68a !important;
        text-shadow: 0 1px 0 rgba(15, 23, 42, 0.16);
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

    .live-chip {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .4px;
        text-transform: uppercase;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(25, 135, 84, 0.12);
        color: #198754;
    }
    .mini-metric {
        border: 1px solid rgba(37, 99, 235, 0.1);
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(237,244,255,0.94) 100%);
        padding: 10px 12px;
        height: 100%;
        box-shadow: 0 10px 20px rgba(0,35,71,0.05);
    }
    .mini-metric .label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
    .mini-metric .value { font-size: 0.88rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
    .metric-card-basic h3 { font-size: 1rem; line-height: 1.1; letter-spacing: -0.02em; }
    .panel-card {
        border: 1px solid #e5edf8;
        border-radius: 18px;
        background: #fff;
        padding: 18px;
        box-shadow: 0 10px 30px rgba(0,35,71,0.05);
        height: 100%;
        position: relative;
        z-index: 1;
    }
    .activity-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #eef4fb;
    }
    .activity-row:last-child { border-bottom: none; }
    .health-chip {
        font-size: 11px;
        font-weight: 800;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eef6ff;
        color: #1d4ed8;
    }
    .insight-band {
        border: 1px solid #e5edf8;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(241,247,255,0.96) 100%);
        padding: 16px 18px;
        box-shadow: 0 10px 30px rgba(0,35,71,0.05);
        position: relative;
        z-index: 1;
    }
    .insight-item {
        border: 1px solid #e8eff8;
        border-radius: 14px;
        background: #fff;
        padding: 12px 14px;
        height: 100%;
    }
    .insight-item .label {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 4px;
    }
    .insight-item .value {
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--deep-sapphire);
        line-height: 1.1;
    }
    .spark-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .spark-box {
        border: 1px solid rgba(37, 99, 235, 0.12);
        border-radius: 14px;
        background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(238,245,255,0.92) 100%);
        padding: 12px;
    }
    .spark-box canvas {
        width: 100% !important;
        height: 54px !important;
    }
    @media (max-width: 767.98px) {
        .spark-row {
            grid-template-columns: 1fr;
        }
    }

    /* Working Domain Reference: env('SESSION_DOMAIN', null) */
</style>

<div class="pos-content-area">
    @include('SuperAdmin.partials._subscription_status_banner')
    @php
        $currentMonthSales = (float) ($metrics['currentMonthSales'] ?? 0);
        $salesGrowthRate = (float) ($metrics['salesGrowthRate'] ?? 0);
        $inventoryValue = (float) ($metrics['inventoryValue'] ?? 0);
    @endphp

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
            <img src="{{ asset('assets/img/logos.png') }}" style="height: 70px;">
        </div>
    </div>

    {{-- Primary Metrics Row --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="metric-card-basic metric-sky">
                <div class="small fw-bold mb-1">TODAY'S REVENUE</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($metrics['todayRevenue'] ?? 0, 2) }}</h3>
                <p class="small mb-0 mt-2"><i class="fas fa-arrow-up"></i> Live</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card-basic metric-violet">
                <div class="small fw-bold mb-1">TOTAL INVOICES</div>
                <h3 class="fw-bold mb-0">{{ number_format($metrics['totalInvoices'] ?? 0) }}</h3>
                <p class="small mb-0 mt-2">All recorded sales documents</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card-basic metric-cyan">
                <div class="small fw-bold mb-1">ACTIVE CUSTOMERS</div>
                <h3 class="fw-bold mb-0">{{ number_format($metrics['activeCustomers'] ?? 0) }}</h3>
                <p class="small mb-0 mt-2">Registered on node</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card-basic metric-amber">
                <div class="small fw-bold mb-1">INVENTORY VALUE</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($inventoryValue, 2) }}</h3>
                <p class="small mb-0 mt-2">{{ number_format($metrics['activeStock'] ?? 0) }} units in inventory</p>
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

    <div class="insight-band mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Node Insight Strip</h5>
            <span class="live-chip">Live</span>
        </div>
        <div class="spark-row mb-3">
            <div class="spark-box">
                <div class="label text-muted small fw-bold text-uppercase mb-2">Revenue Wave</div>
                <canvas id="basicRevenueWave"></canvas>
            </div>
            <div class="spark-box">
                <div class="label text-muted small fw-bold text-uppercase mb-2">Invoice Wave</div>
                <canvas id="basicInvoiceWave"></canvas>
            </div>
            <div class="spark-box">
                <div class="label text-muted small fw-bold text-uppercase mb-2">Items Wave</div>
                <canvas id="basicItemsWave"></canvas>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <div class="insight-item">
                    <div class="label">Pending Balance</div>
                    <div class="value">₦{{ number_format($metrics['pendingBalance'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="insight-item">
                    <div class="label">Items Sold Today</div>
                    <div class="value">{{ number_format($metrics['itemsSoldToday'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="insight-item">
                    <div class="label">Orders Today</div>
                    <div class="value">{{ number_format($metrics['totalOrders'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="insight-item">
                    <div class="label">Health Signal</div>
                    <div class="value">{{ $dashboardHealth['cashflow'] ?? 'Stable' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Metrics Strip --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Avg Order Value', 'value' => '₦' . number_format($metrics['avgOrderValue'] ?? 0, 0)],
            ['label' => 'Low Stock Alerts', 'value' => number_format($metrics['lowStockCount'] ?? 0)],
            ['label' => 'Profit Margin', 'value' => number_format($metrics['profitMargin'] ?? 0, 1) . '%'],
            ['label' => 'Month Revenue', 'value' => '₦' . number_format($currentMonthSales, 0)],
        ] as $m)
            <div class="col-sm-6 col-xl-3">
                <div class="mini-metric">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label">{{ $m['label'] }}</div>
                        <span class="live-chip">Live</span>
                    </div>
                    <div class="value">{{ $m['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Paid Invoices', 'value' => number_format($metrics['paidInvoices'] ?? 0)],
            ['label' => 'Partial Invoices', 'value' => number_format($metrics['partialInvoices'] ?? 0)],
            ['label' => 'Unpaid Invoices', 'value' => number_format($metrics['unpaidInvoices'] ?? 0)],
            ['label' => 'Margin Health', 'value' => $dashboardHealth['margin'] ?? 'Stable'],
        ] as $m)
            <div class="col-sm-6 col-xl-3">
                <div class="mini-metric">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label">{{ $m['label'] }}</div>
                        <span class="live-chip">Live</span>
                    </div>
                    <div class="value">{{ $m['value'] }}</div>
                </div>
            </div>
        @endforeach
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
                        <span class="badge bg-light text-dark">{{ optional(optional($company)->subscription)->status ?? 'Pending' }}</span>
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

    <div class="row g-4 mt-1">
        <div class="col-xl-5">
            <div class="panel-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Node Health</h5>
                    <span class="live-chip">Live</span>
                </div>
                <div class="d-grid gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold">Cashflow</span>
                        <span class="health-chip">{{ $dashboardHealth['cashflow'] ?? 'Healthy' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold">Inventory</span>
                        <span class="health-chip">{{ $dashboardHealth['inventory'] ?? 'Healthy' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold">Margin</span>
                        <span class="health-chip">{{ $dashboardHealth['margin'] ?? 'Stable' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold">Pending Balance</span>
                        <strong style="color: var(--deep-sapphire);">₦{{ number_format($metrics['pendingBalance'] ?? 0, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold">Sales Growth</span>
                        <strong style="color: var(--deep-sapphire);">{{ number_format($salesGrowthRate, 1) }}%</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="panel-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Recent Activity</h5>
                    <span class="text-muted small">{{ count($activities ?? []) }} entries</span>
                </div>
                @forelse(($activities ?? collect())->take(5) as $activity)
                    <div class="activity-row">
                        <div>
                            <div class="fw-bold small text-dark">{{ $activity->description }}</div>
                            <div class="text-muted small">{{ optional($activity->created_at)->diffForHumans() }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small fw-bold" style="color: var(--deep-sapphire);">₦{{ number_format((float) ($activity->amount ?? 0), 2) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small">No recent activity yet.</div>
                @endforelse
            </div>
        </div>
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
            const revenueGradient = revenueCtx.getContext('2d').createLinearGradient(0, 0, 0, 280);
            revenueGradient.addColorStop(0, 'rgba(37, 99, 235, 0.32)');
            revenueGradient.addColorStop(0.55, 'rgba(14, 165, 233, 0.18)');
            revenueGradient.addColorStop(1, 'rgba(14, 165, 233, 0.03)');

            new Chart(revenueCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: totals,
                        borderColor: '#2563eb',
                        backgroundColor: revenueGradient,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2563eb',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)',
                            callbacks: {
                                label: (context) => 'Revenue: ₦' + Number(context.parsed.y || 0).toLocaleString()
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.18)' },
                            ticks: {
                                callback: (v) => '₦' + Number(v).toLocaleString()
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const sparkBase = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                elements: { point: { radius: 0 } },
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: true }
                }
            }
        };

        const revenueWaveCtx = document.getElementById('basicRevenueWave');
        if (revenueWaveCtx) {
            new Chart(revenueWaveCtx.getContext('2d'), {
                ...sparkBase,
                data: {
                    labels,
                    datasets: [{
                        data: totals,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    }]
                }
            });
        }

        const invoiceWaveCtx = document.getElementById('basicInvoiceWave');
        if (invoiceWaveCtx) {
            new Chart(invoiceWaveCtx.getContext('2d'), {
                ...sparkBase,
                data: {
                    labels,
                    datasets: [{
                        data: totals.map(value => Math.max(1, Math.round(value / 5000))),
                        borderColor: '#ff8c00',
                        backgroundColor: 'rgba(255,140,0,0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    }]
                }
            });
        }

        const itemsWaveCtx = document.getElementById('basicItemsWave');
        if (itemsWaveCtx) {
            new Chart(itemsWaveCtx.getContext('2d'), {
                ...sparkBase,
                data: {
                    labels,
                    datasets: [{
                        data: totals.map(value => Math.max(1, Math.round(value / 2500))),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    }]
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
                        backgroundColor: [
                            '#2563eb',
                            '#7c3aed',
                            '#06b6d4',
                            '#f59e0b',
                            '#10b981',
                            '#ef4444'
                        ],
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
