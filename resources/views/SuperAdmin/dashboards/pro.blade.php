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
</style>

<div class="pos-content-area">
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
            <img src="{{ asset('assets/img/smat14.png') }}" style="height: 40px;">
        </div>
    </div>

    {{-- 1. Expanded Metrics (Pro Tier includes Expense/Profit Visibility) --}}
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="glass-card-pro p-4 border-bottom border-primary border-4">
                <div class="stat-icon-circle bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-wallet fa-lg"></i>
                </div>
                <div class="text-muted small fw-bold text-uppercase">Gross Revenue</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($metrics['todayRevenue'] ?? 0, 2) }}</h3>
                <span class="text-success small"><i class="fas fa-caret-up"></i> Live from sales ledger</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card-pro p-4 border-bottom border-success border-4">
                <div class="stat-icon-circle bg-success bg-opacity-10 text-success">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <div class="text-muted small fw-bold text-uppercase">Net Profit</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($metrics['netProfit'] ?? 0, 2) }}</h3>
                <span class="text-muted small">Real-time margin</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card-pro p-4 border-bottom border-danger border-4">
                <div class="stat-icon-circle bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-receipt fa-lg"></i>
                </div>
                <div class="text-muted small fw-bold text-uppercase">Operating Expenses</div>
                <h3 class="fw-bold mb-0">₦{{ number_format($metrics['totalExpenses'] ?? 0, 2) }}</h3>
                <span class="text-danger small"><i class="fas fa-arrow-up"></i> Dynamic from expenses table</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card-pro p-4 border-bottom border-info border-4">
                <div class="stat-icon-circle bg-info bg-opacity-10 text-info">
                    <i class="fas fa-boxes fa-lg"></i>
                </div>
                <div class="text-muted small fw-bold text-uppercase">Inventory Assets</div>
                <h3 class="fw-bold mb-0">{{ number_format($metrics['activeStock'] ?? 0) }}</h3>
                <span class="text-muted small">Units across categories</span>
            </div>
        </div>
    </div>

    {{-- 2. Advanced Sales Chart --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="glass-card-pro p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: var(--deep-sapphire);">Financial Growth Trend</h5>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Last 30 Days</button>
                    </div>
                </div>
                <div style="height: 350px;">
                    @include('SuperAdmin.partials._monthly_sales_chart')
                </div>
            </div>
        </div>
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

        new Chart(proFinanceCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: salesSeries,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,0.12)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Expenses',
                        data: expenseSeries,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.12)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Profit',
                        data: profitSeries,
                        borderColor: '#4338ca',
                        backgroundColor: 'rgba(67,56,202,0.1)',
                        fill: false,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
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
    });
</script>

@endsection
