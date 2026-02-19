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
        margin-left: 250px; 
        padding: 40px;
        background-color: var(--crystal-blue); 
        min-height: 100vh;
        margin-top: 60px;
        position: relative;
    }

    /* Aesthetic Bubbles */
    .pos-content-area::before, .pos-content-area::after {
        content: ''; position: absolute; border-radius: 50%; 
        background: var(--bubble-accent); filter: blur(60px); z-index: 0;
    }
    .pos-content-area::before { width: 500px; height: 500px; top: -150px; right: -100px; }
    .pos-content-area::after { width: 350px; height: 350px; bottom: -50px; left: -80px; }

    body.mini-sidebar .pos-content-area,
    body.sidebar-icon-only .pos-content-area { margin-left: 80px; }

    @media (max-width: 1200px) {
        .pos-content-area { margin-left: 0 !important; padding: 20px; }
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

    /* Print Logic */
    @media print {
        .pos-content-area { margin-left: 0 !important; padding: 0 !important; background: white !important; }
        .sidebar, .header, .btn-print-action, .pos-content-area::before, .pos-content-area::after { display: none !important; }
        .enterprise-card { box-shadow: none !important; border: 1px solid #eee !important; }
    }
</style>

<div class="pos-content-area"> 
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
        <div class="d-flex align-items-center gap-3"> 
            <button onclick="printReport()" class="btn btn-white shadow-sm border-0 px-4 py-2 btn-print-action" style="border-radius: 12px; font-weight: 800; color: var(--deep-sapphire);"> 
                <i class="fas fa-file-pdf me-2 text-danger"></i> GENERATE MASTER REPORT 
            </button> 
            <img src="{{ asset('assets/img/smat14.png') }}" style="height: 40px;" alt="Smat Logo"> 
        </div> 
    </div>

    {{-- 2. Full Metrics Integration --}}
    <div class="mb-5">
        @include('SuperAdmin.partials._metrics_all')
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

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: topProducts.map(p => p.name),
                datasets: [{
                    label: 'Qty Sold',
                    data: topProducts.map(p => Number(p.total_qty || 0)),
                    backgroundColor: '#d4af37',
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
    });
</script>

@endsection
