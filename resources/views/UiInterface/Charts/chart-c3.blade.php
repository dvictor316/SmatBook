<?php $page = 'chart-c3'; ?>
@extends('layout.mainlayout')

@php
    // Provide default values for safety
    $activeCompanies = $activeCompanies ?? 0;
    $inactiveCompanies = $inactiveCompanies ?? 0;
    $totalCompanies = $totalCompanies ?? 0;
    $companiesWithAddress = $companiesWithAddress ?? 0;
    $months = $months ?? [];
    $barChartData = $barChartData ?? [];
@endphp

@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>C3 Charts (Dynamic Data)</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					
					<div class="row">
					
						<!-- Multiple Bar Chart (Dynamic) -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Monthly Company Creation (Dynamic Data)</h5>
								</div>
								<div class="card-body">
									<div  id="chart-bar"></div>
								</div>
							</div>
						</div>
						<!-- /Chart -->

						<!-- Donut Chart (Dynamic) -->
						<div class="col-md-6">	
							<div class="card mb-0">
								<div class="card-header">
									<h5 class="card-title">Company Status Distribution (Dynamic Data)</h5>
								</div>
								<div class="card-body">
									<div id="chart-donut"></div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						
						{{-- Placeholders for other charts --}}

						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Bar Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="chart-bar-stacked"></div>
								</div>
							</div>
						</div>
						
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Horizontal Bar Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div  id="chart-bar-rotated" ></div>
								</div>
							</div>
						</div>
						
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Line Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="chart-sracked"></div>
								</div>
							</div>
						</div>
						
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Line Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="chart-spline-rotated"></div>
								</div>
							</div>
						</div>
						
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Line Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="chart-area-spline-sracked"></div>
								</div>
							</div>
						</div>
						
						<div class="col-md-6">	
							<div class="card mb-0">
								<div class="card-header">
									<h5 class="card-title">Pie Chart (Placeholder)</h5>
								</div>
								<div class="card-body">
									<div id="chart-pie"></div>
								</div>
							</div>
						</div>
						
					</div>
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection

@push('scripts')
{{-- Assuming C3/D3 JS libraries are loaded in your main layout file --}}
<script>
$(document).ready(function() {

    // --- 1. Dynamic Multiple Bar Chart ---
    c3.generate({
        bindto: '#chart-bar',
        data: {
            columns: [
                @json($barChartData), // Injecting dynamic PHP data
            ],
            type: 'bar'
        },
        axis: {
            x: {
                type: 'category',
                // Injecting dynamic PHP labels for months
                categories: @json($months) 
            }
        },
        bar: {
            width: {
                ratio: 0.5
            }
        }
    });


    // --- 2. Dynamic Donut Chart ---
    c3.generate({
        bindto: '#chart-donut',
        data: {
            columns: [
                // Injecting dynamic PHP data for counts
                ['Active Companies', {{ $activeCompanies }}],
                ['Inactive Companies', {{ $inactiveCompanies }}],
                ['With Address', {{ $companiesWithAddress }}],
            ],
            type : 'donut',
            onclick: function (d, i) { console.log("onclick", d, i); },
            onmouseover: function (d, i) { console.log("onmouseover", d, i); },
            onmouseout: function (d, i) { console.log("onmouseout", d, i); }
        },
        donut: {
            title: "Company Status"
        }
    });
    
    // The rest of your theme's C3 JS initialization scripts for other IDs go here...

});
</script>
@endpush
