<?php $page = 'chart-js'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Chartjs</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
					
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Bar Chart</h5>
								</div>
								<div class="card-body">
									<div>
										<canvas id="chartBar1" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Transparency </h5>
								</div>
								<div class="card-body">
									<div>
										<canvas id="chartBar2" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
									<div class="card-header">
										<h5 class="card-title">Gradient Bar Chart</h5>
									</div>
									<div class="card-body">
										<div>
											<canvas id="chartBar3" class="h-300"></canvas>
										</div>
									</div>
								</div>
						</div>
						<!-- /Chart -->
						
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
									<div class="card-header">
										<h5 class="card-title">Horizontal Bar Chart</h5>
									</div>
									<div class="card-body">
										<div class="chartjs-wrapper-demo">
											<canvas id="chartBar4" class="h-300"></canvas>
										</div>
									</div>
								</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Horizontal Bar Chart Style2</h5>
								</div>
								<div class="card-body">
									<div class="chartjs-wrapper-demo">
										<canvas id="chartBar5" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Vertical Stacked Bar Chart</h5>
								</div>
								<div class="card-body">
									<div class="chartjs-wrapper-demo">
										<canvas id="chartStacked1" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Horizontal Stacked Bar Chart</h5>
								</div>
								<div class="card-body">
									<div class="chartjs-wrapper-demo">
										<canvas id="chartStacked2" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Line Chart</h5>
								</div>
								<div class="card-body">
									<div class="chartjs-wrapper-demo">
										<canvas id="chartLine1" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Donut Chart</h5>
								</div>
								<div class="card-body">
									<div class="chartjs-wrapper-demo">
										<canvas id="chartPie" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Pie Chart</h5>
								</div>
								<div class="card-body">
									<div class="chartjs-wrapper-demo">
										<canvas id="chartDonut" class="h-300"></canvas>
									</div>
								</div>
							</div>
						</div>
						<!-- /Chart -->
						
						<!-- Chart -->
						<div class="col-md-6">	
							<div class="card mb-0">
									<div class="card-header">
										<h5 class="card-title">Area Chart</h5>
									</div>
									<div class="card-body">
										<div class="chartjs-wrapper-demo">
											<canvas id="chartArea1" class="h-300"></canvas>
										</div>
									</div>
								</div>
						</div>
						<!-- /Chart -->
							
					</div>
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection