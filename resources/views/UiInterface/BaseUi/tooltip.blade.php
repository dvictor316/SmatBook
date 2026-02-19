<?php $page = 'tooltip'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Tooltip</h5>
						</div>	
					</div>
					<!-- /Page Header -->	
					
					<div class="row">
					
						<!-- Tooltip -->
						<div class="col-md-12">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Html Element</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="popover-list">
										<button class="example-popover btn btn-primary mb-0" type="button" data-container="body" data-bs-toggle="tooltip" data-bs-placement="top" title="Popover title">Hover Me</button>
									</div>
								</div>
							</div>
						</div>
						<!-- /Tooltip -->
					
						<!-- Popover -->
						<div class="col-md-12">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Direction Tooltip</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="tooltip-list">
										<button type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Tooltip on top">
										  Tooltip on top
										</button>
										<button type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="right" title="Tooltip on right">
										  Tooltip on right
										</button>
										<button type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tooltip on bottom">
										  Tooltip on bottom
										</button>
										<button type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="left" title="Tooltip on left">
										  Tooltip on left
										</button>
									</div>
								</div>
							</div>
						</div>
						<!-- /Popover -->
						
						<!-- Tooltip -->
						<div class="col-md-12">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Html Element</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="popover-list">
										<button type="button" class="btn btn-primary mb-0" data-bs-toggle="tooltip" data-bs-html="true" title="<em>Tooltip</em> <u>with</u> <b>HTML</b>">
										  Tooltip with HTML
										</button>
										<button type="button" class="btn btn-primary mb-0" data-bs-toggle="tooltip" data-bs-trigger="click" data-bs-html="true" data-bs-placement="bottom" title="<em>Tooltip</em> <u>with</u> <b>HTML</b>">
										  Click Me
										</button>
									</div>
								</div>
							</div>
						</div>
						<!-- /Tooltip -->
							
					</div>
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection