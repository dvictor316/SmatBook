@extends('layout.mainlayout')

@section('content')

<style> 
    :root { 
        --deep-sapphire: #002347; 
        --smat-gold: #d4af37; 
        --crystal-blue: #f0f7ff; 
        --glass-bg: rgba(255, 255, 255, 0.9); 
        --bubble-accent: rgba(0, 35, 71, 0.05); 
    }

    /* Page Container with Bubble Background */
    .pos-content-area {
        margin-left: 0;
        width: 100%;
        max-width: 100%;
        padding: 40px;
        background-color: var(--crystal-blue);
        min-height: 100vh;
        position: relative;
        overflow-x: clip;
    }

    /* Aesthetic Bubbles */
    .pos-content-area::before, .pos-content-area::after {
        content: ''; position: absolute; border-radius: 50%; 
        background: var(--bubble-accent); filter: blur(60px); z-index: 0;
    }
    .pos-content-area::before { width: 500px; height: 500px; top: -150px; right: -100px; }
    .pos-content-area::after { width: 350px; height: 350px; bottom: -50px; left: -80px; }

    body.mini-sidebar .pos-content-area,
    body.sidebar-icon-only .pos-content-area {
        margin-left: 0;
        width: 100%;
        max-width: 100%;
    }

    @media (max-width: 991.98px) {
        .pos-content-area { margin-left: 0 !important; width: 100% !important; max-width: 100% !important; padding: 20px; }
    }

    /* Enterprise Header Styling */
    .enterprise-header {
        position: relative; z-index: 1;
        border-left: 6px solid var(--smat-gold);
        padding-left: 20px; margin-bottom: 40px;
        display: flex; justify-content: space-between; align-items: center;
    }

    /* Premium Card Design */
    .enterprise-card {
        border: none; border-radius: 25px;
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        box-shadow: 0 15px 35px rgba(0, 35, 71, 0.05);
        transition: transform 0.3s ease;
        z-index: 1; position: relative;
        height: 100%;
    }
    .enterprise-card:hover { transform: translateY(-5px); }

    .master-badge {
        background: linear-gradient(45deg, var(--deep-sapphire), #004a8f);
        color: white; font-size: 0.65rem;
        letter-spacing: 1px; font-weight: 800;
        padding: 6px 15px; border-radius: 50px;
        text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(0, 35, 71, 0.2);
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
        border: 1px solid #e5edf8;
        border-radius: 12px;
        background: #fff;
        padding: 10px 12px;
        height: 100%;
    }
    .mini-metric .label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
    .mini-metric .value { font-size: 0.88rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
    .metric-glass h4 { font-size: 1.2rem; line-height: 1.1; letter-spacing: -0.02em; }
    .activity-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #eef4fb;
    }
    .activity-row:last-child { border-bottom: none; }
    .insight-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 16px;
    }
    .insight-card {
        border: 1px solid #e6eef9;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.92);
        padding: 14px;
    }
    .insight-card .label {
        font-size: 10px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.08em;
        margin-bottom: 5px;
    }
    .insight-card .value {
        font-size: 0.9rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    .insight-card .note {
        margin-top: 6px;
        font-size: 0.77rem;
        color: #64748b;
    }

    .enterprise-card,
    .mini-metric,
    .insight-card {
        color: #0f172a !important;
    }

    .enterprise-card .text-white,
    .mini-metric .text-white,
    .insight-card .text-white {
        color: #0f172a !important;
    }

    .enterprise-card .text-muted,
    .mini-metric .text-muted,
    .insight-card .text-muted {
        color: #64748b !important;
    }

    @media (max-width: 991.98px) {
        .insight-grid {
            grid-template-columns: 1fr;
        }

        .enterprise-dashboard-actions {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .enterprise-dashboard-actions .btn,
        .enterprise-dashboard-actions .branch-chip {
            width: 100%;
            justify-content: center;
            text-align: center;
        }
    }

    .branch-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.95rem;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.08);
        color: #1d4ed8;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.02em;
    }

    /* Print Logic */
    @media print {
        .pos-content-area { margin-left: 0 !important; padding: 0 !important; background: white !important; }
        .sidebar, .header, .btn-print-action, .pos-content-area::before, .pos-content-area::after { display: none !important; }
        .enterprise-card { box-shadow: none !important; border: 1px solid #eee !important; }
    }
</style>

<div class="pos-content-area"> 
    @include('SuperAdmin.partials._subscription_status_banner')

    @php
        $salesRows = collect($monthlySalesData ?? []);
        $expenseRows = collect($monthlyExpenseData ?? []);
        $profitRows = collect($monthlyProfitData ?? []);
        $salesTotals = $salesRows->pluck('total_sales')->map(fn ($value) => (float) $value);
        $expenseTotals = $expenseRows->pluck('total_expenses')->map(fn ($value) => (float) $value);
        $profitTotals = $profitRows->pluck('total_profit')->map(fn ($value) => (float) $value);
        $bestMonthIndex = $salesTotals->count() ? $salesTotals->search($salesTotals->max()) : null;
        $bestMonthLabel = ($bestMonthIndex !== false && $bestMonthIndex !== null) ? ($salesRows[$bestMonthIndex]->month ?? 'N/A') : 'N/A';
        $salesRunRate = $salesTotals->count() ? ($salesTotals->sum() / max($salesTotals->count(), 1)) : 0;
        $expenseRunRate = $expenseTotals->count() ? ($expenseTotals->sum() / max($expenseTotals->count(), 1)) : 0;
        $profitRunRate = $profitTotals->count() ? ($profitTotals->sum() / max($profitTotals->count(), 1)) : 0;
        $inventoryValue = (float) ($metrics['inventoryValue'] ?? 0);
        $branchLabel = $dashboardBranchLabel ?? ($activeBranch['name'] ?? 'All Branches');
    @endphp

    {{-- 1. Master Header --}} 
    <div class="enterprise-header"> 
        <div> 
            <div class="d-flex align-items-center gap-2 mb-1"> 
                <h3 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Enterprise Node Control</h3> 
                <span class="master-badge"><i class="fas fa-crown me-1 text-warning"></i> Master Access</span> 
            </div> 
            <p class="text-muted small mb-0">
                Analytics Node: <strong>{{ request()->getHost() }}</strong> | 
                Domain: <code>{{ env('SESSION_DOMAIN', 'Enterprise_Core') }}</code>
            </p> 
        </div> 
        <div class="d-flex align-items-center gap-3 enterprise-dashboard-actions"> 
            <span class="branch-chip">
                <i class="fas fa-code-branch"></i>
                Active Branch: {{ $branchLabel }}
            </span>
            <a href="{{ route('branches.index') }}" class="btn btn-white shadow-sm border-0 px-4 py-2 btn-print-action" style="border-radius: 12px; font-weight: 800; color: var(--deep-sapphire);"> 
                <i class="fas fa-code-branch me-2 text-primary"></i> MANAGE BRANCHES
            </a> 
            <button onclick="printReport()" class="btn btn-white shadow-sm border-0 px-4 py-2 btn-print-action" style="border-radius: 12px; font-weight: 800; color: var(--deep-sapphire);"> 
                <i class="fas fa-file-pdf me-2 text-danger"></i> GENERATE MASTER REPORT 
            </button> 
            <img src="{{ asset('assets/img/logos.png') }}" style="height: 80px;" alt="Smat Logo"> 
        </div> 
    </div>

    {{-- 2. Full Metrics Integration --}}
    <div class="mb-5">
        @include('SuperAdmin.partials._metrics_all')
    </div>

    {{-- Enterprise Live Strip --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Platform Revenue', 'value' => '₦' . number_format($metrics['totalSales'] ?? 0, 0)],
            ['label' => 'Net Profit', 'value' => '₦' . number_format($metrics['netProfit'] ?? 0, 0)],
            ['label' => 'Expense Load', 'value' => number_format($metrics['expenseRatio'] ?? 0, 1) . '%'],
            ['label' => 'Inventory Value', 'value' => '₦' . number_format($inventoryValue, 0)],
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

    {{-- 3. Visual Analytics Row --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card enterprise-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">
                        <i class="fas fa-chart-line me-2 opacity-50 text-primary"></i> Monthly Performance Trend
                    </h5>
                    <div class="badge bg-soft-primary text-primary">Live Data Feed</div>
                </div>
                <div style="min-height: 400px;">
                    @include('SuperAdmin.partials._monthly_sales_chart')
                </div>
                <div class="insight-grid">
                    <div class="insight-card">
                        <div class="label">Best Month</div>
                        <div class="value">{{ $bestMonthLabel }}</div>
                        <div class="note">Highest revenue month in the current trend window.</div>
                    </div>
                    <div class="insight-card">
                        <div class="label">Revenue Run Rate</div>
                        <div class="value">₦{{ number_format($salesRunRate, 2) }}</div>
                        <div class="note">Average monthly revenue represented on the chart.</div>
                    </div>
                    <div class="insight-card">
                        <div class="label">Profit Run Rate</div>
                        <div class="value">₦{{ number_format($profitRunRate, 2) }}</div>
                        <div class="note">Average monthly profit after expenses.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card enterprise-card p-4">
                <h5 class="fw-bold mb-4" style="color: var(--deep-sapphire);">
                    <i class="fas fa-globe-africa me-2 opacity-50 text-success"></i> Global Market HeatMap
                </h5>
                <div class="text-center py-2">
                    @include('SuperAdmin.partials._heatmap')
                </div>
                <div class="mt-4 p-3 rounded-3 bg-light">
                    <p class="small text-muted mb-0">
                        <i class="fas fa-info-circle me-1"></i> Regional clusters are calculated based on IP-resolved node requests.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Operations Row --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card enterprise-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">
                        <i class="fas fa-file-invoice-dollar me-2 opacity-50 text-info"></i> Latest Invoices
                    </h5>
                    <span class="text-muted small">Updated 1m ago</span>
                </div>
                <div class="table-responsive">
                    @include('SuperAdmin.partials._latest_invoices')
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card enterprise-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">
                        <i class="fas fa-exclamation-triangle me-2 opacity-50 text-danger"></i> Low Stock Alerts
                    </h5>
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3">Restock All</button>
                </div>
                @include('SuperAdmin.partials._low_stock')
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card enterprise-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">
                        <i class="fas fa-chart-bar me-2 opacity-50 text-warning"></i> Top Product Performance
                    </h5>
                    <span class="text-muted small">{{ date('Y') }}</span>
                </div>
                <div style="height: 300px;">
                    <canvas id="enterpriseTopProductsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card enterprise-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">
                        <i class="fas fa-users me-2 opacity-50 text-primary"></i> Top Customers
                    </h5>
                    <span class="text-muted small">Live ranking</span>
                </div>
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
                                    <td colspan="3" class="text-center text-muted py-3">No customer spend data yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-12">
            <div class="card enterprise-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">
                        <i class="fas fa-stream me-2 opacity-50 text-info"></i> Operations Feed
                    </h5>
                    <span class="text-muted small">{{ count($activities ?? []) }} live events</span>
                </div>
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="d-grid gap-3">
                            <div class="mini-metric">
                                <div class="label">Cashflow Health</div>
                                <div class="value">{{ $dashboardHealth['cashflow'] ?? 'Healthy' }}</div>
                            </div>
                            <div class="mini-metric">
                                <div class="label">Inventory Health</div>
                                <div class="value">{{ $dashboardHealth['inventory'] ?? 'Healthy' }}</div>
                            </div>
                            <div class="mini-metric">
                                <div class="label">Margin Outlook</div>
                                <div class="value">{{ $dashboardHealth['margin'] ?? 'Stable' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        @forelse(($activities ?? collect())->take(6) as $activity)
                            <div class="activity-row">
                                <div>
                                    <div class="fw-bold small text-dark">{{ $activity->description }}</div>
                                    <div class="text-muted small">{{ optional($activity->created_at)->diffForHumans() }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="small fw-bold" style="color: var(--deep-sapphire);">₦{{ number_format((float) ($activity->amount ?? 0), 2) }}</div>
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
        const topProducts = @json($topProducts ?? []);
        const canvas = document.getElementById('enterpriseTopProductsChart');
        if (!canvas) return;

        const enterpriseBarGradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 300);
        enterpriseBarGradient.addColorStop(0, '#f8d66d');
        enterpriseBarGradient.addColorStop(0.5, '#d4af37');
        enterpriseBarGradient.addColorStop(1, '#8b5e18');

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: topProducts.map(p => p.name),
                datasets: [{
                    label: 'Qty Sold',
                    data: topProducts.map(p => Number(p.total_qty || 0)),
                    backgroundColor: enterpriseBarGradient,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.18)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>

@endsection
