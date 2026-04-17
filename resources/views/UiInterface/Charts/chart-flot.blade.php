<?php $page = 'chart-flot'; ?>
@extends('layout.mainlayout')

@php
    // Provide default values for safety
    $flotPieData = $flotPieData ?? [];
    $flotBarData = $flotBarData ?? [];
@endphp

@section('content')
			
            <div class="page-wrapper">
                <div class="content container-fluid">

					
					<div class="page-header">
						<div class="content-page-header">
							<h5>Flot Chart (Dynamic Data)</h5>
						</div>	
					</div>
					

					<div class="row">

						
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Monthly Company Creation (Dynamic Data)</h5>
								</div>
								<div class="card-body">
									<div class="h-250" id="flotBar1"></div>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-6">	
							<div class="card mb-0">
								<div class="card-header">
									<h5 class="card-title">Company Status Distribution (Dynamic Data)</h5>
								</div>
								<div class="card-body">
									<div class="h-250" id="flotPie2"></div>
								</div>
							</div>
						</div>
						

						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Bar Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div  class="h-250" id="flotBar2"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Line Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div  class="h-250" id="flotLine1"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Line Chart Points (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div class="h-250" id="flotLine2"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Area Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div class="h-250" id="flotArea1"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Area Chart Points (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div class="h-250" id="flotArea2"></div>
								</div>
							</div>
						</div>

						<div class="col-md-6">	
							<div class="card mb-0">
								<div class="card-header">
									<h5 class="card-title">Pie Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div class="h-250" id="flotPie1"></div>
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

    // --- 1. Dynamic Bar Chart (Monthly Company Creation) ---
    // Data is passed as [[1, count1], [2, count2], ...]
    var barData = @json($flotBarData);

    // Define options
    var barOptions = {
        series: {
            bars: {
                show: true,
                barWidth: 0.6,
                fill: true,
                fillColor: '#4C7BAF'
            }
        },
        xaxis: {
            // Use mode: "categories" if using text labels, otherwise numeric
            ticks: [[1, "Jan"], [2, "Feb"], [3, "Mar"], [4, "Apr"], [5, "May"], [6, "Jun"], 
                    [7, "Jul"], [8, "Aug"], [9, "Sep"], [10, "Oct"], [11, "Nov"], [12, "Dec"]]
        },
        grid: { hoverable: true },
        tooltip: true,
        tooltipOpts: { content: "Month %x.1: %y new companies" }
    };

    $.plot($("#flotBar1"), [barData], barOptions);

    // --- 2. Dynamic Donut Chart (Company Status Distribution) ---
    // Data is passed as [{ label: "Active", data: 10 }, ...]
    var donutData = @json($flotPieData);

    var donutOptions = {
        series: {
            pie: {
                show: true,
                innerRadius: 0.5, // Makes it a donut
                label: {
                    show: true,
                    radius: 2/3,
                    formatter: function(label, series){
                        return '<div style="font-size:11px;text-align:center;padding:4px;color:white;">' + label + '<br/>' + Math.round(series.percent) + '%</div>';
                    },
                    threshold: 0.1
                }
            }
        },
        legend: { show: true },
        grid: { hoverable: true }
    };

    $.plot($("#flotPie2"), donutData, donutOptions);

    // The rest of your theme's Flot JS initialization scripts for other IDs go here...
});
</script>
@endpush
