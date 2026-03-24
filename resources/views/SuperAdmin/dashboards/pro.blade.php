@extends('layout.mainlayout')

@section('content')

<style>
    :root {
        --deep-sapphire: #002347;
        --crystal-blue: #f8fbff;
        --bubble-accent: rgba(0, 35, 71, 0.04);
        --pro-purple: #6366f1;
        --pro-gradient: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
    }

    .pos-content-area {
        margin-left: var(--sb-sidebar-w, 270px); 
        width: calc(100% - var(--sb-sidebar-w, 270px));
        max-width: calc(100% - var(--sb-sidebar-w, 270px));
        padding: 40px; 
        background-color: var(--crystal-blue); 
        min-height: 100vh;
        position: relative;
        overflow-x: clip;
    }

    body.mini-sidebar .pos-content-area,
    body.sidebar-icon-only .pos-content-area {
        margin-left: var(--sb-sidebar-collapsed, 80px);
        width: calc(100% - var(--sb-sidebar-collapsed, 80px));
        max-width: calc(100% - var(--sb-sidebar-collapsed, 80px));
    }

    @media (max-width: 991.98px) {
        .pos-content-area {
            margin-left: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 20px;
        }
    }

    /* Professional Floating Bubbles */
    .pos-content-area::before {
        content: ''; position: absolute; width: 450px; height: 450px;
        background: var(--bubble-accent); filter: blur(70px);
        border-radius: 50%; top: -150px; right: -50px;
    }

    .pro-header {
        border-left: 6px solid var(--pro-purple);
        padding-left: 20px; 
        margin-bottom: 35px;
    }

    .glass-card-pro {
        border: none; 
        border-radius: 25px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 40px rgba(0, 35, 71, 0.04);
        transition: transform 0.3s ease;
        position: relative; 
        z-index: 1;
        height: 100%;
    }
    
    .glass-card-pro:hover { 
        transform: translateY(-5px); 
    }

    .glass-card-pro.metric-indigo {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
    }
    .glass-card-pro.metric-emerald {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
    }
    .glass-card-pro.metric-rose {
        background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
        color: #fff;
    }
    .glass-card-pro.metric-cyan {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        color: #fff;
    }
    .glass-card-pro.metric-indigo .text-muted,
    .glass-card-pro.metric-emerald .text-muted,
    .glass-card-pro.metric-rose .text-muted,
    .glass-card-pro.metric-cyan .text-muted {
        color: rgba(255,255,255,0.72) !important;
    }

    .teaser-lock {
        background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), 
                    url('https://www.transparenttextures.com/patterns/world-map.png');
        border: 2px dashed #cbd5e1; 
        border-radius: 25px;
        min-height: 350px;
    }

    .badge-pro {
        background: var(--pro-gradient); 
        color: white;
        font-size: 0.75rem; 
        font-weight: 800;
        padding: 6px 16px; 
        border-radius: 50px;
        text-transform: uppercase;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
    }

    .stat-icon-circle {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
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
        border: 1px solid rgba(99, 102, 241, 0.12);
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(244,242,255,0.94) 100%);
        padding: 10px 12px;
        height: 100%;
        box-shadow: 0 10px 20px rgba(67, 56, 202, 0.06);
    }
    .mini-metric .label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
    .mini-metric .value { font-size: 0.88rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
    .glass-card-pro h3 { font-size: 0.96rem; line-height: 1.1; letter-spacing: -0.02em; }
    .panel-card {
        border: 1px solid #e5edf8;
        border-radius: 18px;
        background: #fff;
        padding: 18px;
        box-shadow: 0 10px 30px rgba(0,35,71,0.05);
        height: 100%;
    }
    .activity-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #eef4fb;
    }
    .activity-row:last-child { border-bottom: none; }
    .insight-band {
        border: 1px solid #e5edf8;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(241,247,255,0.96) 100%);
        padding: 16px 18px;
        box-shadow: 0 10px 30px rgba(0,35,71,0.05);
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
        border: 1px solid rgba(99, 102, 241, 0.14);
        border-radius: 14px;
        background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(242,238,255,0.92) 100%);
        padding: 12px;
    }
    .spark-box canvas {
        width: 100% !important;
        height: 54px !important;
    }

    .glass-card-pro:not(.metric-indigo):not(.metric-emerald):not(.metric-rose):not(.metric-cyan),
    .panel-card,
    .mini-metric,
    .insight-band,
    .insight-item,
    .spark-box,
    .teaser-lock {
        color: #0f172a !important;
    }

    .glass-card-pro:not(.metric-indigo):not(.metric-emerald):not(.metric-rose):not(.metric-cyan) .text-white,
    .panel-card .text-white,
    .mini-metric .text-white,
    .insight-band .text-white,
    .insight-item .text-white,
    .spark-box .text-white,
    .teaser-lock .text-white {
        color: #0f172a !important;
    }

    .glass-card-pro:not(.metric-indigo):not(.metric-emerald):not(.metric-rose):not(.metric-cyan) .text-muted,
    .panel-card .text-muted,
    .mini-metric .text-muted,
    .insight-band .text-muted,
    .insight-item .text-muted,
    .spark-box .text-muted,
    .teaser-lock .text-muted {
        color: #64748b !important;
    }
    @media (max-width: 767.98px) {
        .spark-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="pos-content-area">
    @include('SuperAdmin.partials._subscription_status_banner')
    @php
        $inventoryValue = (float) ($metrics['inventoryValue'] ?? 0);
        $currentMonthSales = (float) ($metrics['currentMonthSales'] ?? 0);
        $salesGrowthRate = (float) ($metrics['salesGrowthRate'] ?? 0);
    @endphp

    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div class="pro-header">
            <div class="d-flex align-items-center gap-3 mb-1">
                <h3 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Professional Node Dashboard</h3>
                <span class="badge-pro">PRO TIER</span>
            </div>
            <p class="text-muted small mb-0">Domain: <code>{{ env('SESSION_DOMAIN', 'Live Node') }}</code> | Instance: <strong>{{ request()->getHost() }}</strong></p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button onclick="printReport()" class="btn btn-white shadow-sm border-0 px-4 py-2" style="border-radius: 12px; font-weight: 700; color: var(--deep-sapphire);">
                <i class="fas fa-print me-2 text-primary"></i> PRINT ANALYTICS
            </button>
            <img src="{{ asset('assets/img/logos.png') }}" style="height: 80px;">
        </div>
    </div>

    {{-- 1. Expanded Metrics (Pro Tier includes Expense/Profit Visibility) --}}
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="glass-card-pro metric-indigo p-4">
                <div class="stat-icon-circle" style="background: rgba(255,255,255,0.16); color: #fff;">
                    <i class="fas fa-wallet fa-lg"></i>
                </div>
                <div class="small fw-bold text-uppercase">Gross Revenue</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($metrics['todayRevenue'] ?? 0, 2) }}</h3>
                <span class="small"><i class="fas fa-caret-up"></i> Live from sales ledger</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card-pro metric-emerald p-4">
                <div class="stat-icon-circle" style="background: rgba(255,255,255,0.16); color: #fff;">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <div class="small fw-bold text-uppercase">Net Profit</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($metrics['netProfit'] ?? 0, 2) }}</h3>
                <span class="small">Real-time margin</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card-pro metric-rose p-4">
                <div class="stat-icon-circle" style="background: rgba(255,255,255,0.16); color: #fff;">
                    <i class="fas fa-receipt fa-lg"></i>
                </div>
                <div class="small fw-bold text-uppercase">Operating Expenses</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($metrics['totalExpenses'] ?? 0, 2) }}</h3>
                <span class="small"><i class="fas fa-arrow-up"></i> Dynamic from expenses table</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card-pro metric-cyan p-4">
                <div class="stat-icon-circle" style="background: rgba(255,255,255,0.16); color: #fff;">
                    <i class="fas fa-boxes fa-lg"></i>
                </div>
                <div class="small fw-bold text-uppercase">Inventory Assets</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($inventoryValue, 2) }}</h3>
                <span class="small">{{ number_format($metrics['activeStock'] ?? 0) }} units across categories</span>
            </div>
        </div>
    </div>

    {{-- 2. Advanced Sales Chart --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="glass-card-pro p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Financial Growth Trend</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="live-chip">Live</span>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Last 30 Days</button>
                        </div>
                    </div>
                </div>
                <div style="height: 350px;">
                    @include('SuperAdmin.partials._monthly_sales_chart')
                </div>
            </div>
        </div>
    </div>

    <div class="insight-band mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Profitability Insight Strip</h5>
            <span class="live-chip">Live</span>
        </div>
        <div class="spark-row mb-3">
            <div class="spark-box">
                <div class="label text-muted small fw-bold text-uppercase mb-2">Revenue Wave</div>
                <canvas id="proRevenueWave"></canvas>
            </div>
            <div class="spark-box">
                <div class="label text-muted small fw-bold text-uppercase mb-2">Expense Wave</div>
                <canvas id="proExpenseWave"></canvas>
            </div>
            <div class="spark-box">
                <div class="label text-muted small fw-bold text-uppercase mb-2">Profit Wave</div>
                <canvas id="proProfitWave"></canvas>
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
                    <div class="label">Paid vs Partial</div>
                    <div class="value">{{ number_format($metrics['paidInvoices'] ?? 0) }} / {{ number_format($metrics['partialInvoices'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="insight-item">
                    <div class="label">Margin Signal</div>
                    <div class="value">{{ $dashboardHealth['margin'] ?? 'Stable' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Metrics Strip --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Profit Margin', 'value' => number_format($metrics['profitMargin'] ?? 0, 1) . '%'],
            ['label' => 'Avg Order Value', 'value' => '₦' . number_format($metrics['avgOrderValue'] ?? 0, 0)],
            ['label' => 'Expense Ratio', 'value' => number_format($metrics['expenseRatio'] ?? 0, 1) . '%'],
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

    <div class="row g-4 mb-5">
        <div class="col-xl-8">
            <div class="glass-card-pro p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Revenue vs Expenses vs Profit</h5>
                    <small class="text-muted">{{ date('Y') }}</small>
                </div>
                <div style="height: 320px;">
                    <canvas id="proFinanceChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="glass-card-pro p-4 h-100">
                <h5 class="fw-bold mb-3" style="color: var(--deep-sapphire);">Top Customers</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th class="text-end">Invoices</th>
                                <th class="text-end">Spend</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($topCustomers ?? collect()) as $customer)
                                <tr>
                                    <td>{{ $customer->customer_name }}</td>
                                    <td class="text-end">{{ number_format($customer->invoices_count) }}</td>
                                    <td class="text-end">₦{{ number_format($customer->total_spend, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No customer data yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- 3. Invoices --}}
        <div class="col-xl-7">
            <div class="glass-card-pro p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Recent Node Invoices</h5>
                    <a href="{{ url('invoices') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">View Registry</a>
                </div>
                <div class="table-responsive">
                    @include('SuperAdmin.partials._latest_invoices')
                </div>
            </div>
        </div>
        
        {{-- 4. Enterprise Teaser --}}
        <div class="col-xl-5">
            <div class="teaser-lock h-100 p-5 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="mb-3">
                    <span class="fa-stack fa-3x">
                        <i class="fas fa-circle fa-stack-2x text-light"></i>
                        <i class="fas fa-globe-africa fa-stack-1x text-muted opacity-50"></i>
                        <i class="fas fa-lock fa-stack-1x text-danger" style="margin-top: 25px; margin-left: 25px; font-size: 1.5rem;"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark">Global HeatMap Locked</h4>
                <p class="text-muted px-3">Visualize your customer distribution and regional sales performance with Enterprise-grade geospatial analytics.</p>
                <div class="mt-3">
                    <a href="{{ route('membership-plans') }}" class="btn btn-primary px-5 py-2 shadow-lg" style="border-radius: 12px; font-weight: 700;">
                        UPGRADE TO ENTERPRISE <i class="fas fa-rocket ms-2"></i>
                    </a>
                </div>
                <div class="row g-2 w-100 mt-4">
                    <div class="col-6">
                        <div class="insight-item text-start">
                            <div class="label">Orders Today</div>
                            <div class="value">{{ number_format($metrics['totalOrders'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="insight-item text-start">
                            <div class="label">Low Stock</div>
                            <div class="value">{{ number_format($metrics['lowStockCount'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="insight-item text-start">
                            <div class="label">Sales Growth</div>
                            <div class="value">{{ number_format($salesGrowthRate, 1) }}%</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="insight-item text-start">
                            <div class="label">Cashflow Signal</div>
                            <div class="value">{{ $dashboardHealth['cashflow'] ?? 'Healthy' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-5">
            <div class="panel-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Inventory Alerts</h5>
                    <span class="live-chip">Live</span>
                </div>
                @include('SuperAdmin.partials._low_stock')
            </div>
        </div>
        <div class="col-xl-7">
            <div class="panel-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Recent Activity</h5>
                    <span class="text-muted small">{{ count($activities ?? []) }} entries</span>
                </div>
                @forelse(($activities ?? collect())->take(6) as $activity)
                    <div class="activity-row">
                        <div>
                            <div class="fw-bold small text-dark">{{ $activity->description }}</div>
                            <div class="text-muted small">{{ optional($activity->created_at)->diffForHumans() }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small fw-bold text-primary">₦{{ number_format((float) ($activity->amount ?? 0), 2) }}</div>
                            <div class="text-muted small text-uppercase">{{ $activity->status ?? 'paid' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small">No recent activity yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Global Print Script - 2025-12-30 Policy --}}
<script> 
    function printReport() { 
        window.print(); 
    } 
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sales = @json($monthlySalesData ?? []);
        const expenses = @json($monthlyExpenseData ?? []);
        const profit = @json($monthlyProfitData ?? []);

        const labels = profit.map(row => row.month);
        const salesMap = new Map(sales.map(row => [Number(row.month_num), Number(row.total_sales || 0)]));
        const expenseMap = new Map(expenses.map(row => [Number(row.month_num), Number(row.total_expenses || 0)]));
        const salesSeries = profit.map(row => salesMap.get(Number(row.month_num)) || 0);
        const expenseSeries = profit.map(row => expenseMap.get(Number(row.month_num)) || 0);
        const profitSeries = profit.map(row => Number(row.total_profit || 0));

        const proFinanceCtx = document.getElementById('proFinanceChart');
        if (!proFinanceCtx) return;

        const proFinanceGradient = proFinanceCtx.getContext('2d').createLinearGradient(0, 0, 0, 320);
        proFinanceGradient.addColorStop(0, 'rgba(99, 102, 241, 0.22)');
        proFinanceGradient.addColorStop(1, 'rgba(99, 102, 241, 0.02)');

        new Chart(proFinanceCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: salesSeries,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,0.14)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 3,
                        pointRadius: 3
                    },
                    {
                        label: 'Expenses',
                        data: expenseSeries,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.14)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 3,
                        pointRadius: 3
                    },
                    {
                        label: 'Profit',
                        data: profitSeries,
                        borderColor: '#4338ca',
                        backgroundColor: proFinanceGradient,
                        fill: false,
                        tension: 0.3,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#4338ca',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.92)',
                        callbacks: {
                            label: (context) => context.dataset.label + ': ₦' + Number(context.parsed.y || 0).toLocaleString()
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

        const revenueWaveCtx = document.getElementById('proRevenueWave');
        if (revenueWaveCtx) {
            new Chart(revenueWaveCtx.getContext('2d'), {
                ...sparkBase,
                data: {
                    labels,
                    datasets: [{
                        data: salesSeries,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,0.12)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2
                    }]
                }
            });
        }

        const expenseWaveCtx = document.getElementById('proExpenseWave');
        if (expenseWaveCtx) {
            new Chart(expenseWaveCtx.getContext('2d'), {
                ...sparkBase,
                data: {
                    labels,
                    datasets: [{
                        data: expenseSeries,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.12)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2
                    }]
                }
            });
        }

        const profitWaveCtx = document.getElementById('proProfitWave');
        if (profitWaveCtx) {
            new Chart(profitWaveCtx.getContext('2d'), {
                ...sparkBase,
                data: {
                    labels,
                    datasets: [{
                        data: profitSeries,
                        borderColor: '#4338ca',
                        backgroundColor: 'rgba(67,56,202,0.12)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2
                    }]
                }
            });
        }
    });
</script>

@endsection
