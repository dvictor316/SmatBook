@extends('layout.mainlayout')

@section('content')
<style>
    /* FIXED SIDEBAR OFFSET & THEME COLORS */
    .pos-content-area {
        margin-left: 250px; 
        padding: 30px;
        transition: all 0.3s ease-in-out;
        background-color: #fdfaf0; 
        min-height: 100vh;
        margin-top: 60px;
    }

    body.mini-sidebar .pos-content-area { margin-left: 80px; }

    @media (max-width: 1200px) {
        .pos-content-area { margin-left: 0 !important; padding: 15px; }
    }

    /* Professional Header Styling */
    .report-header {
        border-left: 5px solid #d4af37;
        padding-left: 15px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Metric & Chart Card Enhancements */
    .metric-card, .chart-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        background: #fff;
        transition: transform 0.2s;
    }
    .metric-card:hover { transform: translateY(-5px); }
    .metric-title { font-size: 11px; font-weight: 800; color: #996515; text-transform: uppercase; margin-bottom: 8px; }
    .metric-value { font-size: 1.5rem; font-weight: 700; }
    
    .chart-card { padding: 20px; }
    .chart-card h5 {
        color: #0369a1;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    /* PRINT STYLES - Mandatory Requirement */
    @media print {
        .pos-content-area { margin-left: 0 !important; padding: 0 !important; background: white !important; }
        .sidebar, .header, .btn-print-action { display: none !important; }
        .metric-card, .chart-card { box-shadow: none !important; border: 1px solid #eee !important; }
    }
</style>

<div class="pos-content-area">
    {{-- Page Header --}}
    <div class="report-header">
        <div>
            <h3 class="fw-bold mb-0" style="color: #0369a1;">Business Analytics</h3>
            <p class="text-muted small mb-0">Overview of sales performance and inventory status.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-white btn-sm border shadow-sm btn-print-action">
                <i class="fas fa-print text-primary me-1"></i> Print Report
            </button>
            <span class="badge bg-white text-dark border p-2 shadow-sm d-none d-md-inline-block">
                <i class="fas fa-calendar-alt text-warning me-2"></i>{{ date('D, M d, Y') }}
            </span>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="row mb-4 g-3">
        @php
            $cards = [
                ['title' => "Today's Revenue", 'value' => '₦'.number_format($todayRevenue ?? 0, 0), 'icon' => 'fa-coins', 'color' => '#0369a1'],
                ['title' => 'Pending Balance', 'value' => '₦'.number_format($pendingAmount ?? 0, 0), 'icon' => 'fa-clock', 'color' => '#dc3545'],
                ['title' => 'Items Sold', 'value' => number_format($itemsSoldToday ?? 0), 'icon' => 'fa-shopping-cart', 'color' => '#d4af37'],
                ['title' => 'Total Orders', 'value' => number_format($totalOrders ?? 0), 'icon' => 'fa-file-invoice', 'color' => '#10b981'],
            ];
        @endphp

        @foreach($cards as $card)
        <div class="col-lg-3 col-md-6">
            <div class="card metric-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-title">{{ $card['title'] }}</div>
                        <div class="metric-value" style="color: {{ $card['color'] }}">{{ $card['value'] }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: {{ $card['color'] }}15; color: {{ $card['color'] }};">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Chart Row 1 --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card chart-card">
                <h5><i class="fas fa-chart-line me-2"></i>Monthly Sales Trend</h5>
                <div id="morrisLineSales" style="height: 300px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card chart-card">
                <h5><i class="fas fa-chart-pie me-2"></i>Sale Status</h5>
                <div id="morrisDonutSales" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    {{-- Chart Row 2 --}}
    <div class="row">
        <div class="col-md-7">
            <div class="card chart-card">
                <h5><i class="fas fa-chart-bar me-2"></i>Top Products/Services</h5>
                <div id="morrisBarProducts" style="height: 320px;"></div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card chart-card" style="height: 405px;">
                <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto; overflow-x: hidden;">
                    <ul class="list-group list-group-flush" id="recentActivityList">
                        @forelse($recentActivity as $activity)
                            <li class="list-group-item border-0 ps-0 mb-3 bg-transparent">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <div class="bg-light rounded p-2 text-center" style="min-width: 40px;">
                                            <i class="fas fa-receipt text-muted small"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong class="d-block text-dark small">{{ $activity['company_name'] }}</strong>
                                        <span class="text-muted d-block" style="font-size: 11px; line-height: 1.2;">{{ $activity['description'] }}</span>
                                        <small class="text-muted" style="font-size: 10px;">{{ $activity['created_at']->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted border-0 py-5">No recent activity</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.3.0/raphael.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css" />

<script>
$(document).ready(function() {
    function formatCurrencyNaira(value) {
        return '₦' + value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // 1. Line Chart
    const monthlySales = @json($monthlySalesData ?? []);
    if (monthlySales.length > 0) {
        new Morris.Line({
            element: 'morrisLineSales',
            data: monthlySales,
            xkey: 'period',
            ykeys: ['total_sales'],
            labels: ['Revenue'],
            lineColors: ['#0369a1'],
            pointFillColors: ['#ffffff'],
            pointStrokeColors: ['#d4af37'],
            gridTextColor: '#999',
            lineWidth: 3,
            parseTime: false,
            resize: true,
            yLabelFormat: function (y) { return formatCurrencyNaira(y); }
        });
    }

    // 2. Donut Chart
    const saleStatusData = @json($saleStatusData ?? []);
    if (saleStatusData.data && saleStatusData.data.length > 0) {
        new Morris.Donut({
            element: 'morrisDonutSales',
            data: saleStatusData.labels.map((label, index) => ({
                label: label,
                value: saleStatusData.data[index]
            })),
            colors: ['#10b981', '#0369a1', '#dc3545'],
            resize: true,
            labelColor: '#0369a1'
        });
    }

    // 3. Bar Chart
    const productLabels = @json($productSalesLabels ?? []);
    const productData = @json($productSalesData ?? []);
    if (productLabels.length > 0) {
        new Morris.Bar({
            element: 'morrisBarProducts',
            data: productLabels.map((label, index) => ({
                product: label.length > 15 ? label.substring(0, 12) + '...' : label,
                sales: productData[index]
            })),
            xkey: 'product',
            ykeys: ['sales'],
            labels: ['Revenue'],
            barColors: ['#d4af37'],
            xLabelAngle: 30,
            resize: true,
            gridTextColor: '#999',
            yLabelFormat: function (y) { return formatCurrencyNaira(y); }
        });
    }
});
</script>
@endpush