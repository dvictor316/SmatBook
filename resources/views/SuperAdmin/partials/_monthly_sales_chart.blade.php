<div class="card border-0 shadow-sm mt-4 monthly-sales-card">
    <div class="card-header border-0 py-3 monthly-sales-card__header">
        <h6 class="fw-bold mb-0 text-white">Monthly Sales Analytics</h6>
    </div>
    <div class="card-body monthly-sales-card__body">
        <canvas id="monthlySalesChart" style="min-height: 300px;"></canvas>
    </div>
</div>

<style>
    .monthly-sales-card {
        overflow: hidden;
        border-radius: 24px;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.22), transparent 28%),
            linear-gradient(135deg, #0f172a 0%, #1d4ed8 52%, #06b6d4 100%);
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    }

    .monthly-sales-card__header {
        background: transparent;
    }

    .monthly-sales-card__body {
        background: linear-gradient(180deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%);
        border-top: 1px solid rgba(255,255,255,0.08);
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
        monthlyGradient.addColorStop(0, 'rgba(255,255,255,0.48)');
        monthlyGradient.addColorStop(0.45, 'rgba(125,211,252,0.24)');
        monthlyGradient.addColorStop(1, 'rgba(14,165,233,0.04)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₦)',
                    data: totals,
                    borderColor: '#f8fafc',
                    backgroundColor: monthlyGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBorderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#38bdf8'
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
                            color: 'rgba(255,255,255,0.08)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.8)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255,255,255,0.08)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.8)',
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
