{{-- resources/views/livewire/index-sales.blade.php --}}

{{-- WRAP ALL CONTENT IN A SINGLE PARENT DIV FOR LIVEWIRE COMPATIBILITY --}}
<div> 

    {{-- Row 1: The Stat Cards (Shadows removed, text bolded) --}}
    <div class="row">
        
        {{-- Card 1: Total Sales (Primary/Blue) --}}
        <div class="col-md-6 col-sm-6 col-lg-6 col-xl-3">
            <div class="card dash-widget">
                <div class="card-body">
                    <span class="dash-widget-icon bg-primary"><i class="fas fa-sack-dollar text-white"></i></span>
                    <div class="dash-widget-info">
                        <h3 class="h5 fw-bold">{{ $currencySymbol }}{{ number_format($salesStats['total_sales'], 2) }}</h3>
                        <span class="fw-bold">Total Sales</span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Card 2: Total Receipts (Success/Green) --}}
        <div class="col-md-6 col-sm-6 col-lg-6 col-xl-3">
            <div class="card dash-widget">
                <div class="card-body">
                    <span class="dash-widget-icon bg-success"><i class="fas fa-hand-holding-usd text-white"></i></span>
                    <div class="dash-widget-info">
                        <h3 class="h5 fw-bold">{{ $currencySymbol }}{{ number_format($salesStats['total_receipts'], 2) }}</h3>
                        <span class="fw-bold">Total Receipts</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Total Expenses (Warning/Yellow) --}}
        <div class="col-md-6 col-sm-6 col-lg-6 col-xl-3">
            <div class="card dash-widget">
                <div class="card-body">
                    <span class="dash-widget-icon bg-warning"><i class="fas fa-file-invoice-dollar text-white"></i></span>
                    <div class="dash-widget-info">
                        <h3 class="h5 fw-bold">{{ $currencySymbol }}{{ number_format($salesStats['total_expenses'], 2) }}</h3>
                        <span class="fw-bold">Total Expenses</span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Card 4: Total Earnings (Info/Light Blue) --}}
        <div class="col-md-6 col-sm-6 col-lg-6 col-xl-3">
            <div class="card dash-widget">
                <div class="card-body">
                    <span class="dash-widget-icon bg-info"><i class="fas fa-coins text-white"></i></span>
                    <div class="dash-widget-info">
                        <h3 class="h5 fw-bold">{{ $currencySymbol }}{{ number_format($salesStats['total_earnings'], 2) }}</h3>
                        <span class="fw-bold">Total Earnings</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Row 2: The Two Charts side-by-side (Shadows removed) --}}
    <div class="row">
        <div class="col-lg-6"> 
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-bold">Monthly Sales Overview</h5>
                </div>
                <div class="card-body">
                    <div id="salesOverviewChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6"> 
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-bold">Monthly Breakdown</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesBarChart" style="height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

</div> {{-- END OF SINGLE PARENT DIV --}}


@push('scripts')
<script>
document.addEventListener('livewire:initialized', () => {
    const monthlySalesData = @json($chartData); 
    const currency = @json($currencySymbol);
    const chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // --- 1. Line Chart Initialization (ApexCharts) ---
    if (typeof ApexCharts !== 'undefined') {
        var options = {
            series: [{ name: "Sales (Avg)", data: monthlySalesData }],
            chart: { height: 400, type: 'line', toolbar: { show: false } },
            xaxis: { categories: chartLabels },
            yaxis: { labels: { formatter: function (value) { return currency + value.toLocaleString(); } } },
            tooltip: { y: { formatter: function (value) { return currency + value.toLocaleString(); } } }
        };
        var chart = new ApexCharts(document.querySelector("#salesOverviewChart"), options);
        chart.render();
    }

    // --- 2. Bar Chart Initialization (Chart.js) ---
    if (typeof Chart !== 'undefined') {
        var ctxBar = document.getElementById('salesBarChart').getContext('2d');
        var salesBarChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{ label: 'Monthly Sales', data: monthlySalesData, backgroundColor: 'rgba(75, 192, 192, 0.8)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 1 }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
    }

});
</script>
@endpush
