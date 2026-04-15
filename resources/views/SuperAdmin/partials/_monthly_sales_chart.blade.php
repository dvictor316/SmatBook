<div class="card border-0 shadow-sm mt-4 monthly-sales-card">
    <div class="card-header border-0 py-3 monthly-sales-card__header">
        <h6 class="fw-bold mb-0" style="color:#1e293b;">Monthly Sales Analytics</h6>
    </div>
    <div class="card-body monthly-sales-card__body">
        <canvas id="monthlySalesChart" style="min-height: 300px;"></canvas>
    </div>
</div>

<style>
    .monthly-sales-card {
        overflow: hidden;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08);
    }

    .monthly-sales-card__header {
        background: transparent;
        border-bottom: 1px solid #e2e8f0;
    }

    .monthly-sales-card__body {
        background: #ffffff;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('monthlySalesChart').getContext('2d');
        
        // Data passed from DashboardController
        const salesData = @json($monthlySalesData);
        
        const labels = salesData.map(data => data.month);
        const totals = salesData.map(data => data.total_sales);

        const monthlyGradient = ctx.createLinearGradient(0, 0, 0, 320);
        monthlyGradient.addColorStop(0, 'rgba(29,78,216,0.18)');
        monthlyGradient.addColorStop(0.55, 'rgba(56,189,248,0.08)');
        monthlyGradient.addColorStop(1, 'rgba(14,165,233,0.01)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₦)',
                    data: totals,
                    borderColor: '#1d4ed8',
                    backgroundColor: monthlyGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBorderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#1d4ed8'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.92)',
                        titleColor: '#f8fafc',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(125,211,252,0.32)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₦' + Number(context.parsed.y || 0).toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.06)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#475569'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.06)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#475569',
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
</script>
