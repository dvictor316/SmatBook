@extends('layout.mainlayout')

@section('content')
{{-- Enhanced container with proper sidebar offset and modern card styling --}}
<div class="page-wrapper" style="padding-left: 280px;">
    <div class="content container-fluid" style="max-width: 1400px; margin: 0 auto;">
        
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">📊 Business Overview</h3>
                    <ul class="breadcrumb" style="background: transparent; padding: 0;">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary shadow-sm rounded-pill btn-print">
                        <i class="fas fa-print me-1"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            @php
                $cards = [
                    ['title' => 'Total Companies', 'value' => $totalCompanies ?? 0, 'icon' => 'fa-building', 'color' => 'text-primary'],
                    ['title' => 'Active Clients', 'value' => $activeCompanies ?? 0, 'icon' => 'fa-check-circle', 'color' => 'text-success'],
                    ['title' => 'Inactive', 'value' => $inactiveCompanies ?? 0, 'icon' => 'fa-times-circle', 'color' => 'text-danger'],
                    ['title' => 'New Today', 'value' => $newTodayCompanies ?? 0, 'icon' => 'fa-plus-square', 'color' => 'text-warning'],
                ];
            @endphp

            @foreach($cards as $card)
            <div class="col-xl-3 col-sm-6 col-12 mb-4">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">{{ $card['title'] }}</h6>
                                <h2 class="mb-0 fw-bold">{{ number_format($card['value']) }}</h2>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas {{ $card['icon'] }} {{ $card['color'] }} fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><i class="fas fa-chart-line text-primary me-2"></i>Monthly Sales Trend</h5>
                        <hr class="opacity-10">
                        <div id="morrisLineSales" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><i class="fas fa-chart-pie text-success me-2"></i>Client Status</h5>
                        <hr class="opacity-10">
                        <div id="morrisDonutStatus" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><i class="fas fa-shopping-bag text-warning me-2"></i>Top Products/Services Revenue</h5>
                        <hr class="opacity-10">
                        <div id="morrisBarProducts" style="height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-5 col-md-12 mb-4">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><i class="fas fa-file-invoice-dollar text-info me-2"></i>Financial Status</h5>
                        <hr class="opacity-10">
                        <div id="morrisDonutSales" style="height: 280px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-md-12 mb-4">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><i class="fas fa-history text-muted me-2"></i>Recent Activity</h5>
                        <hr class="opacity-10">
                        <div class="activity-feed" style="max-height: 280px; overflow-y: auto;">
                            <ul class="list-group list-group-flush">
                                @forelse($recentActivity as $activity)
                                    <li class="list-group-item border-0 px-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm bg-soft-primary text-primary rounded-circle me-3">
                                                <i class="fas fa-bell"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="mb-0 fw-bold text-dark">{{ $activity['company_name'] }}</p>
                                                <small class="text-muted">{{ $activity['description'] }}</small>
                                            </div>
                                            <small class="text-muted text-nowrap">{{ $activity['created_at']->diffForHumans() }}</small>
                                        </div>
                                    </li>
                                @empty
                                    <li class="list-group-item text-center text-muted border-0 py-5">
                                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="50" class="opacity-25 mb-3 d-block mx-auto">
                                        No recent activity recorded
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Custom Scrollbar for Recent Activity --}}
<style>
    .activity-feed::-webkit-scrollbar { width: 5px; }
    .activity-feed::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .bg-soft-primary { background-color: rgba(0, 123, 255, 0.1); width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; }
</style>

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
            lineColors: ['#007bff'],
            lineWidth: 3,
            parseTime: false,
            resize: true,
            yLabelFormat: function (y) { return formatCurrencyNaira(y); }
        });
    }

    // 2. Client Status Donut
    var activeCount = {{ $activeCompanies ?? 0 }};
    var inactiveCount = {{ $inactiveCompanies ?? 0 }};
    if ((activeCount + inactiveCount) > 0) {
        new Morris.Donut({
            element: 'morrisDonutStatus',
            data: [
                { label: 'Active', value: activeCount },
                { label: 'Inactive', value: inactiveCount }
            ],
            colors: ['#28a745', '#dc3545'],
            resize: true
        });
    }

    // 3. Bar Chart
    const productLabels = @json($productSalesLabels ?? []);
    const productData = @json($productSalesData ?? []);
    if (productLabels.length > 0) {
        const barData = productLabels.map((label, index) => ({
            product: label,
            sales: productData[index]
        }));
        new Morris.Bar({
            element: 'morrisBarProducts',
            data: barData,
            xkey: 'product',
            ykeys: ['sales'],
            labels: ['Revenue'],
            barColors: ['#fd7e14'],
            resize: true,
            yLabelFormat: function (y) { return formatCurrencyNaira(y); }
        });
    }

    // 4. Sales Status Donut
    const saleStatusData = @json($saleStatusData ?? []);
    const saleLabels = saleStatusData.labels ?? [];
    const saleValues = saleStatusData.data ?? [];
    if (saleValues.length > 0) {
        const salesData = saleLabels.map((label, index) => ({
            label: label,
            value: saleValues[index]
        }));
        new Morris.Donut({
            element: 'morrisDonutSales',
            data: salesData,
            colors: ['#28a745', '#007bff', '#dc3545'],
            resize: true
        });
    }

    // Integration of your required print script
    $('.btn-print').on('click', function() {
        window.print();
    });
});
</script>
@endpush