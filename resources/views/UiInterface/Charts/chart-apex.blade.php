<?php $page = 'chart-apex'; ?>
@extends('layout.mainlayout')

@php
    $invoiceMonths = $invoiceMonths ?? [];
    $invoiceCounts = $invoiceCounts ?? [];
    $activeCompanies = $activeCompanies ?? 0;
    $inactiveCompanies = $inactiveCompanies ?? 0;
@endphp

@section('content')
			
            <div class="page-wrapper">
                <div class="content container-fluid">

					
					<div class="page-header">
						<div class="content-page-header">
							<h5>Charts (Dynamic ApexCharts)</h5>
						</div>	
					</div>
					

					<div class="row">

						
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Monthly Invoice Count (Dynamic Data)</h5>
								</div>
								<div class="card-body">
									<div id="s-line"></div>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-6">
							<div class="card mb-0">
								<div class="card-header">
									<h5 class="card-title">Company Status (Dynamic Data)</h5>
								</div>
								<div class="card-body">
									<div id="donut-chart"></div>
								</div>
							</div>
						</div>
						

						<div class="col-md-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Area Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="s-line-area"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Column Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="s-col"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Column Stacked Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="s-col-stacked"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Bar Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="s-bar"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Mixed Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="mixed-chart"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="card mb-0">
								<div class="card-header">
									<h5 class="card-title">Radial Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="radial-chart"></div>
								</div>
							</div>
						</div>

					</div>

				</div>			
			</div>
			
@endsection

@push('scripts')

<script>
$(document).ready(function() {
    // --- 1. Dynamic Simple Line Chart ---
    var sline = {
        chart: {
            height: 350,
            type: 'line',
            toolbar: { show: false },
        },
        series: [{
            name: 'Invoices Created',
            data: @json($invoiceCounts) // Injecting dynamic PHP data
        }],
        xaxis: {
            categories: @json($invoiceMonths), // Injecting dynamic PHP labels
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " invoices";
                }
            }
        }
    };
    var chart_line = new ApexCharts(document.querySelector("#s-line"), sline);
    chart_line.render();

    // --- 2. Dynamic Donut Chart ---
    var optionsDonut = {
        chart: {
            type: 'donut',
            height: 350,
        },
        series: [{{ $activeCompanies }}, {{ $inactiveCompanies }}], // Injecting dynamic PHP data
        labels: ['Active Companies', 'Inactive Companies'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };
    var chartDonut = new ApexCharts(document.querySelector("#donut-chart"), optionsDonut);
    chartDonut.render();

    // The rest of your theme's ApexChart JS initialization scripts for other IDs (s-line-area, s-col, etc.) go here...

});
</script>
@endpush
