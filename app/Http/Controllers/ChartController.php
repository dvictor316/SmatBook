@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Dashboard</h1>

    <!-- Example Metric Cards (Now Dynamic) -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-light p-3">
                <h4>Active Companies</h4>
                {{-- Use the dynamic variable from the controller --}}
                <p class="display-4">{{ $activeCompanies ?? 0 }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light p-3">
                <h4>Inactive Companies</h4>
                {{-- Use the dynamic variable from the controller --}}
                <p class="display-4">{{ $inactiveCompanies ?? 0 }}</p>
            </div>
        </div>
        {{-- Add other metric cards as needed --}}
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Monthly Invoices Bar Chart -->
        <div class="col-md-6 mb-4">
            <div class="card p-3">
                <h5>Monthly Invoices Count</h5>
                {{-- ApexChart will render into this div --}}
                <div id="monthlyInvoicesChart" style="height: 350px;"></div>
            </div>
        </div>

        <!-- Company Status Doughnut Chart -->
        <div class="col-md-6 mb-4">
            <div class="card p-3">
                <h5>Company Status Overview</h5>
                 {{-- ApexChart will render into this div --}}
                <div id="companyStatusChart" style="height: 350px;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Include ApexCharts JS --}}
<script src="cdn.jsdelivr.net"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Injecting PHP variables into JavaScript using @json ---
        // Use @json directive for safe and proper encoding of arrays/collections
        const invoiceMonths = @json($invoiceMonths ?? []);
        const invoiceCounts = @json($invoiceCounts ?? []);
        const activeCompanies = {{ $activeCompanies ?? 0 }};
        const inactiveCompanies = {{ $inactiveCompanies ?? 0 }};

        // --- Monthly Invoices Bar Chart ---
        if (invoiceMonths.length > 0) {
            var invoiceOptions = {
                chart: { type: 'bar', height: 350, toolbar: { show: false } },
                series: [{ name: 'Invoices', data: invoiceCounts }],
                xaxis: { categories: invoiceMonths },
                title: { text: 'Monthly Invoices' },
                colors: ['#007bff'],
                plotOptions: { bar: { columnWidth: '45%', distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: false },
            };
            var invoiceChart = new ApexCharts(document.querySelector("#monthlyInvoicesChart"), invoiceOptions);
            invoiceChart.render();
        } else {
            document.querySelector("#monthlyInvoicesChart").innerHTML = '<p class="text-center text-muted p-4">No invoice data available.</p>';
        }

        // --- Company Status Doughnut Chart ---
        if (activeCompanies + inactiveCompanies > 0) {
            var statusOptions = {
                chart: { type: 'donut', height: 350 },
                series: [activeCompanies, inactiveCompanies],
                labels: ['Active Companies', 'Inactive Companies'],
                colors: ['#28a745', '#dc3545'], // Green for active, red for inactive
                responsive: [{
                    breakpoint: 480,
                    options: { chart: { width: 200 }, legend: { position: 'bottom' } }
                }]
            };
            var statusChart = new ApexCharts(document.querySelector("#companyStatusChart"), statusOptions);
            statusChart.render();
        } else {
             document.querySelector("#companyStatusChart").innerHTML = '<p class="text-center text-muted p-4">No company status data available.</p>';
        }
    });
</script>
@endpush
