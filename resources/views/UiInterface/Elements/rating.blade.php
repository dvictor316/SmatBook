<?php $page = 'rating'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Rating</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
					
						<!-- Rating -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Default Rating</h5>
								</div>
								<div class="card-body card-buttons">
									<p>This is the most basic example of ratings.</p>
									<div class="rating rating-default"></div>
								</div>
							</div>
						</div>
						<!-- /Rating -->
						
						<!-- Rating -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Score</h5>
								</div>
								<div class="card-body card-buttons">
									<p>Stars with a saved rating.</p>
									<div class="rating rating-score"></div>
								</div>
							</div>
						</div>
						<!-- /Rating -->
						
						<!-- Rating -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Max Number</h5>
								</div>
								<div class="card-body card-buttons">
									<p>Change the max numbers of stars</p>
									<div class="rating rating-number"></div>
								</div>
							</div>
						</div>
						<!-- /Rating -->
						
						<!-- Rating -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Read Only</h5>
								</div>
								<div class="card-body card-buttons"> 
									<p>Prevent users from voting</p>
									<div class="rating rating-read-only2"></div>
								</div>
							</div>
						</div>
						<!-- /Rating -->
						
						<!-- Rating -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Half Rating</h5>
								</div>
								<div class="card-body card-buttons">
									<p>You can represent a float score as a half star icon.</p>
									<div class="rating rating-half-enable"></div>
								</div>
							</div>
						</div>
						<!-- /Rating -->
						
						<!-- Rating -->
						<div class="col-md-6">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Custom Icon</h5>
								</div>
								<div class="card-body card-buttons">
									<p>Use any icon you want.</p>
									<div class="rating rating-custom-icon"></div>
								</div>
							</div>
						</div>
						<!-- /Rating -->
							
					</div>
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection