<?php $page = 'chart-morris'; ?>
@extends('layout.mainlayout')

@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="page-header">
                <div class="content-page-header">
                    <h5>Morris Chart Example (Dynamic Data)</h5>
                </div>	
            </div>
            <!-- /Page Header -->

            <div class="row">

                <!-- Line Chart (Monthly Sales) -->
                <div class="col-md-6">	
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Monthly Sales Line Chart</h5>
                        </div>
                        <div class="card-body">

                            <div id="morrisLineSales"></div>
                        </div>
                    </div>
                </div>
                <!-- /Line Chart -->

                <!-- Donut Chart (Company Status) -->
                <div class="col-md-6">	
                    <div class="card mb-0">
                        <div class="card-header">
                            <h5 class="card-title">Company Distribution Donut Chart</h5>
                        </div>
                        <div class="card-body">

                            <div id="morrisDonutStatus"></div>
                        </div>
                    </div>
                </div>
                <!-- /Donut Chart -->

                <div class="col-md-6">	
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Bar Chart Placeholder</h5>
                        </div>
                        <div class="card-body">
                            <div id="morrisBar1"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">	
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Area Chart Placeholder</h5>
                        </div>
                        <div class="card-body">
                            <div id="morrisArea1"></div>
                        </div>
                    </div>
                </div>

            </div>

        </div>			
    </div>
    <!-- /Page Wrapper -->
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Line Chart Data (Monthly Sales) ---
        // We use the data passed from the Laravel controller via Blade syntax
        const monthlySalesData = @json($monthlySales ?? []); // Use default empty array if variable isn't set

        // Transform data into the format Morris expects: [{ y: 'Month Name', a: 100, b: 90 }]
        const formattedSalesData = monthlySalesData.map(item => {
            return {
                y: item.month_name, // e.g., "January"
                a: item.total_sales // e.g., 5000.00
            };
        });

        if (typeof Morris !== 'undefined' && formattedSalesData.length > 0) {
            Morris.Line({
                element: 'morrisLineSales',
                data: formattedSalesData,
                xkey: 'y',
                ykeys: ['a'],
                labels: ['Total Sales'],
                lineColors: ['#4beeb4'],
                resize: true,
                parseTime: false // Set to false since xkey is a month name string, not a date object
            });
        }

        // --- 2. Donut Chart Data (Company Status) ---
        // Use the counts passed from the Laravel controller
        const activeCount = {{ $activeCompanies ?? 0 }};
        const inactiveCount = {{ $inactiveCompanies ?? 0 }};

        if (typeof Morris !== 'undefined' && (activeCount > 0 || inactiveCount > 0)) {
            Morris.Donut({
                element: 'morrisDonutStatus',
                data: [
                    {label: "Active Companies", value: activeCount},
                    {label: "Inactive Companies", value: inactiveCount}
                ],
                colors: ['#2eca8b', '#ff7849'],
                resize: true
            });
        }

        // --- 3. Placeholders for other charts (using dummy data as an example) ---
        if (typeof Morris !== 'undefined') {
             Morris.Bar({
                element: 'morrisBar1',
                data: [
                    {y: '2016', a: 100, b: 90},
                    {y: '2017', a: 75, b: 65},
                    {y: '2018', a: 50, b: 40},
                    {y: '2019', a: 75, b: 65},
                    {y: '2020', a: 50, b: 40},
                    {y: '2021', a: 75, b: 65},
                    {y: '2022', a: 100, b: 90}
                ],
                xkey: 'y',
                ykeys: ['a', 'b'],
                labels: ['Series A', 'Series B'],
                barColors: ['#009688', '#E67E22'],
                resize: true,
            });

             Morris.Area({
                element: 'morrisArea1',
                data: [
                    {period: '2010', iphone: 50, ipad: null, itouch: 80},
                    {period: '2011', iphone: 130, ipad: 100, itouch: 10},
                    {period: '2012', iphone: 30, ipad: 60, itouch: 120},
                    {period: '2013', iphone: 60, ipad: 200, itouch: 105},
                    {period: '2014', iphone: 180, ipad: 150, itouch: 85},
                    {period: '2015', iphone: 105, ipad: 100, itouch: 90},
                    {period: '2016', iphone: 250, ipad: 150, itouch: 30}
                ],
                xkey: 'period',
                ykeys: ['iphone', 'ipad', 'itouch'],
                labels: ['iPhone', 'iPad', 'iPod Touch'],
                lineColors: ['#009688', '#E67E22', '#F39C12'],
                resize: true
            });
        }

    });
</script>
@endpush
